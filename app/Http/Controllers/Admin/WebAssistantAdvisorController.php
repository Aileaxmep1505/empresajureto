<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebAssistantConversation;
use App\Models\WebAssistantMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebAssistantAdvisorController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'open');

        $query = WebAssistantConversation::query()
            ->with(['customer:id,name,email', 'advisor:id,name,email', 'latestMessage'])
            ->withCount('messages')
            ->where(function ($q) {
                $q->whereNotNull('handoff_requested_at')
                    ->orWhereIn('handoff_status', ['waiting', 'active', 'closed']);
            });

        if ($status === 'waiting') {
            $query->where('handoff_status', 'waiting');
        } elseif ($status === 'active') {
            $query->where('handoff_status', 'active');
        } elseif ($status === 'closed') {
            $query->where('handoff_status', 'closed');
        } else {
            $query->whereIn('handoff_status', ['waiting', 'active']);
        }

        $conversations = $query
            ->orderByRaw("CASE WHEN handoff_status = 'waiting' THEN 0 WHEN handoff_status = 'active' THEN 1 ELSE 2 END")
            ->latest('updated_at')
            ->paginate(30)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'items' => $conversations->getCollection()->map(fn ($conversation) => $this->conversationPayload($conversation))->values(),
            ]);
        }

        return view('admin.web-assistant.conversations', [
            'conversations' => $conversations,
            'status' => $status,
        ]);
    }

    public function show(WebAssistantConversation $conversation)
    {
        $conversation->load(['customer:id,name,email', 'advisor:id,name,email']);

        $messages = $conversation->messages()
            ->oldest()
            ->get()
            ->map(fn ($message) => $this->messagePayload($message))
            ->values();

        return response()->json([
            'ok' => true,
            'conversation' => $this->conversationPayload($conversation),
            'messages' => $messages,
        ]);
    }

    public function take(WebAssistantConversation $conversation)
    {
        $conversation->forceFill([
            'handoff_status' => 'active',
            'advisor_id' => Auth::id(),
            'advisor_joined_at' => $conversation->advisor_joined_at ?: now(),
            'status' => 'active',
            'closed_at' => null,
        ])->save();

        WebAssistantMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'system',
            'content' => 'Un asesor tomó la conversación.',
            'metadata' => [
                'advisor_id' => Auth::id(),
                'advisor_name' => Auth::user()?->name,
            ],
        ]);

        return response()->json([
            'ok' => true,
            'conversation' => $this->conversationPayload($conversation->fresh(['customer:id,name,email', 'advisor:id,name,email'])),
        ]);
    }

    public function reply(Request $request, WebAssistantConversation $conversation)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        if ($conversation->handoff_status !== 'active') {
            $conversation->forceFill([
                'handoff_status' => 'active',
                'advisor_id' => Auth::id(),
                'advisor_joined_at' => now(),
                'status' => 'active',
                'closed_at' => null,
            ])->save();
        }

        if (! $conversation->advisor_id) {
            $conversation->forceFill([
                'advisor_id' => Auth::id(),
                'advisor_joined_at' => now(),
            ])->save();
        }

        $message = WebAssistantMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'advisor',
            'content' => trim($data['message']),
            'metadata' => [
                'advisor_id' => Auth::id(),
                'advisor_name' => Auth::user()?->name,
            ],
        ]);

        $conversation->forceFill([
            'last_advisor_message_at' => now(),
            'updated_at' => now(),
            'status' => 'active',
            'handoff_status' => 'active',
        ])->save();

        return response()->json([
            'ok' => true,
            'message' => $this->messagePayload($message),
            'conversation' => $this->conversationPayload($conversation->fresh(['customer:id,name,email', 'advisor:id,name,email'])),
        ]);
    }

    public function close(Request $request, WebAssistantConversation $conversation)
    {
        $note = trim((string) $request->input('note', ''));

        if ($note !== '') {
            WebAssistantMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'advisor',
                'content' => $note,
                'metadata' => [
                    'advisor_id' => Auth::id(),
                    'advisor_name' => Auth::user()?->name,
                    'closing_note' => true,
                ],
            ]);
        }

        $conversation->forceFill([
            'handoff_status' => 'closed',
            'status' => 'closed',
            'closed_at' => now(),
            'advisor_id' => $conversation->advisor_id ?: Auth::id(),
            'last_advisor_message_at' => now(),
        ])->save();

        WebAssistantMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'system',
            'content' => 'La conversación fue cerrada por el asesor.',
            'metadata' => [
                'advisor_id' => Auth::id(),
                'advisor_name' => Auth::user()?->name,
            ],
        ]);

        return response()->json([
            'ok' => true,
            'conversation' => $this->conversationPayload($conversation->fresh(['customer:id,name,email', 'advisor:id,name,email'])),
        ]);
    }

    private function conversationPayload(WebAssistantConversation $conversation): array
    {
        $conversation->loadMissing(['customer:id,name,email', 'advisor:id,name,email', 'latestMessage']);

        return [
            'id' => $conversation->id,
            'title' => $conversation->title ?: 'Conversación sin título',
            'status' => $conversation->status,
            'handoff_status' => $conversation->handoff_status ?: 'bot',
            'messages_count' => $conversation->messages_count ?? $conversation->messages()->count(),
            'customer' => [
                'id' => $conversation->customer?->id,
                'name' => $conversation->customer?->name ?: 'Cliente invitado',
                'email' => $conversation->customer?->email,
                'session_id' => $conversation->session_id,
            ],
            'advisor' => [
                'id' => $conversation->advisor?->id,
                'name' => $conversation->advisor?->name,
                'email' => $conversation->advisor?->email,
            ],
            'latest_message' => $conversation->latestMessage ? [
                'role' => $conversation->latestMessage->role,
                'content' => $conversation->latestMessage->content,
                'time' => optional($conversation->latestMessage->created_at)->format('H:i'),
            ] : null,
            'created_at' => optional($conversation->created_at)->format('d/m/Y H:i'),
            'updated_at' => optional($conversation->updated_at)->format('d/m/Y H:i'),
            'time' => optional($conversation->updated_at)->format('H:i'),
        ];
    }

    private function messagePayload(WebAssistantMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'metadata' => $message->metadata ?: [],
            'time' => optional($message->created_at)->format('H:i'),
            'created_at' => optional($message->created_at)->format('d/m/Y H:i'),
        ];
    }
}
