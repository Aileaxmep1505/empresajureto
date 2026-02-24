<?php

namespace App\Http\Controllers;

use App\Models\WaConversation;
use App\Models\WaMessage;
use Illuminate\Http\Request;
use App\Services\WhatsAppCloud;
use Illuminate\Support\Str;

class WhatsAppInboxController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string)$request->query('q',''));

        $convs = WaConversation::query()
            ->when($q !== '', function($qq) use ($q){
                $qq->where('wa_id','like',"%{$q}%")
                   ->orWhere('name','like',"%{$q}%");
            })
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return view('whatsapp.index', compact('convs','q'));
    }

    public function show(WaConversation $conversation)
    {
        $messages = WaMessage::where('conversation_id', $conversation->id)
            ->orderBy('id')
            ->limit(300)
            ->get();

        // marcar como leído “en tu sistema”
        if ($conversation->unread_count > 0) {
            $conversation->unread_count = 0;
            $conversation->save();
        }

        return view('whatsapp.show', [
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    public function send(Request $request, WaConversation $conversation, WhatsAppCloud $wa)
    {
        $data = $request->validate([
            'message' => ['required','string','max:4096'],
        ]);

        $to = $conversation->wa_id; // ya es E164 sin +

        $res = $wa->sendText($to, $data['message']);

        $wamid = data_get($res, 'messages.0.id');

        WaMessage::create([
            'conversation_id' => $conversation->id,
            'direction' => 'out',
            'wa_message_id' => $wamid,
            'from_wa_id' => config('whatsapp.phone_number_id'),
            'to_wa_id' => $to,
            'type' => 'text',
            'body' => $data['message'],
            'payload' => $res,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $conversation->last_message_preview = Str::limit($data['message'], 200);
        $conversation->last_message_at = now();
        $conversation->save();

        return redirect()->route('whatsapp.show', $conversation->id);
    }
}