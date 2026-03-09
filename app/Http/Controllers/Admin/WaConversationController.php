<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WaConversation;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;

class WaConversationController extends Controller
{
    public function index()
    {
        $conversations = WaConversation::with(['user', 'agent'])
            ->withCount('messages')
            ->latest('last_message_at')
            ->paginate(20);

        return view('admin.whatsapp.index', compact('conversations'));
    }

    public function show(WaConversation $conversation)
    {
        $conversation->load([
            'user',
            'agent',
            'messages' => fn($q) => $q->latest()->limit(100),
        ]);

        return view('admin.whatsapp.show', compact('conversation'));
    }

    public function take(WaConversation $conversation)
    {
        $conversation->update([
            'status' => 'human',
            'assigned_to' => auth()->id(),
        ]);

        return back()->with('ok', 'Conversación tomada.');
    }

    public function reply(Request $request, WaConversation $conversation)
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'max:1000'],
        ]);

        $result = app(WhatsAppService::class)->sendText(
            $conversation->phone,
            $data['text'],
            $conversation
        );

        if (!$result['ok']) {
            return back()->with('err', 'No se pudo enviar el mensaje.');
        }

        $conversation->update([
            'status' => 'human',
            'assigned_to' => auth()->id(),
            'last_message_at' => now(),
        ]);

        return back()->with('ok', 'Mensaje enviado.');
    }

    public function close(WaConversation $conversation)
    {
        $conversation->update([
            'status' => 'closed',
        ]);

        return back()->with('ok', 'Conversación cerrada.');
    }
}