<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WaConversation;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WaConversationController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $conversations = WaConversation::query()
            ->with(['user', 'agent'])
            ->withCount('messages')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('phone', 'like', "%{$q}%")
                        ->orWhere('status', 'like', "%{$q}%")
                        ->orWhereHas('user', function ($userQ) use ($q) {
                            $userQ->where('name', 'like', "%{$q}%");
                        })
                        ->orWhereHas('agent', function ($agentQ) use ($q) {
                            $agentQ->where('name', 'like', "%{$q}%");
                        });
                });
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.whatsapp.index', compact('conversations'));
    }

    public function show(WaConversation $conversation)
    {
        $conversation->load([
            'user',
            'agent',
            'messages' => function ($q) {
                $q->orderByDesc('created_at')
                  ->limit(100);
            },
        ]);

        if ($conversation->relationLoaded('messages')) {
            $conversation->setRelation(
                'messages',
                $conversation->messages->sortBy('created_at')->values()
            );
        }

        return view('admin.whatsapp.show', compact('conversation'));
    }

    public function take(Request $request, WaConversation $conversation)
    {
        $conversation->update([
            'status' => 'human',
            'assigned_to' => auth()->id(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Conversación tomada.',
                'conversation' => [
                    'id' => $conversation->id,
                    'status' => $conversation->status,
                    'assigned_to' => $conversation->assigned_to,
                ],
            ]);
        }

        return back()->with('ok', 'Conversación tomada.');
    }

    public function reply(Request $request, WaConversation $conversation): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'max:1000'],
        ]);

        $result = app(WhatsAppService::class)->sendText(
            $conversation->phone,
            $data['text'],
            $conversation
        );

        if (!($result['ok'] ?? false)) {
            $message = (string) ($result['message'] ?? 'No se pudo enviar el mensaje.');

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'status' => 'error',
                    'message' => $message,
                ], 422);
            }

            return back()->with('err', $message);
        }

        $conversation->update([
            'status' => 'human',
            'assigned_to' => auth()->id(),
            'last_message_at' => now(),
        ]);

        $payload = [
            'ok' => true,
            'status' => 'success',
            'message' => 'Mensaje enviado.',
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
                'assigned_to' => $conversation->assigned_to,
                'last_message_at' => optional($conversation->last_message_at)?->toDateTimeString(),
            ],
            'sent' => [
                'text' => $data['text'],
                'phone' => $conversation->phone,
                'wa_message_id' => $result['data']['messages'][0]['id'] ?? null,
            ],
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($payload);
        }

        return back()->with('ok', 'Mensaje enviado.');
    }

    public function close(Request $request, WaConversation $conversation)
    {
        $conversation->update([
            'status' => 'closed',
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Conversación cerrada.',
                'conversation' => [
                    'id' => $conversation->id,
                    'status' => $conversation->status,
                ],
            ]);
        }

        return back()->with('ok', 'Conversación cerrada.');
    }
}