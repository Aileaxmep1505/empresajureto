<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\HelpTicket;
use App\Models\HelpMessage;
use App\Services\AIAssistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HelpCenterController extends Controller
{
  public function create() {
    $last = HelpTicket::where('user_id', Auth::id())->latest('last_activity_at')->first();
    return view('web.ayuda', ['ticket' => $last]);
  }

  public function start(Request $r) {
    $data = $r->validate([
      'subject'  => 'required|string|max:160',
      'category' => 'nullable|string|max:80',
      'priority' => 'nullable|in:low,normal,high',
      'message'  => 'required|string|min:6',
    ]);

    $ticket = HelpTicket::create([
      'user_id' => Auth::id(),
      'subject' => $data['subject'],
      'category'=> $data['category'] ?? null,
      'priority'=> $data['priority'] ?? 'normal',
      'status'  => 'new',
      'last_activity_at' => now(),
    ]);

    $userMsg = HelpMessage::create([
      'ticket_id'   => $ticket->id,
      'sender_type' => 'user',
      'sender_id'   => Auth::id(),
      'body'        => $data['message'],
    ]);

    try {
      $aiText = AIAssistant::answer($data['message'], [
        'ticket_subject' => $ticket->subject,
        'category' => $ticket->category,
        'priority' => $ticket->priority,
      ]);
    } catch (\Throwable $e) {
      Log::error('AI error: '.$e->getMessage());
      $aiText = "No pude responder automáticamente. Pulsa **“Contactar a un humano”**.";
    }

    $aiMsg = HelpMessage::create([
      'ticket_id'   => $ticket->id,
      'sender_type' => 'ai',
      'body'        => $aiText,
    ]);

    $ticket->update(['status'=>'ai_answered','last_activity_at'=>now()]);
    $ticket->load('messages.sender');

    if ($r->expectsJson()) {
      return response()->json([
        'ok' => true,
        'ticket' => [
          'id' => $ticket->id,
          'subject' => $ticket->subject,
          'status' => $ticket->status,
        ],
        'messages' => $ticket->messages->map(fn($m)=>[
          'id'=>$m->id,
          'type'=>$m->sender_type,
          'body'=>$m->body,
          'is_solution'=>$m->is_solution,
          'created_at'=>$m->created_at->format('Y-m-d H:i:s')
        ]),
      ]);
    }

    return redirect()->route('help.show', $ticket);
  }

  public function show(HelpTicket $ticket) {
    abort_unless($ticket->user_id === Auth::id(), 403);
    $ticket->load('messages.sender');
    return view('web.ayuda', compact('ticket'));
  }

  public function message(Request $r, HelpTicket $ticket) {
    abort_unless($ticket->user_id === Auth::id(), 403);

    $r->validate(['message'=>'required|string|min:2']);

    $userMsg = HelpMessage::create([
      'ticket_id'   => $ticket->id,
      'sender_type' => 'user',
      'sender_id'   => Auth::id(),
      'body'        => (string) $r->string('message'),
    ]);

    $ticket->update(['status'=>'waiting_user','last_activity_at'=>now()]);

    $aiMsg = null;
    try {
      $aiText = AIAssistant::answer((string)$r->string('message'), [
        'ticket_subject' => $ticket->subject,
        'category' => $ticket->category,
        'priority' => $ticket->priority,
      ]);
      $aiMsg = HelpMessage::create([
        'ticket_id'=>$ticket->id,'sender_type'=>'ai','body'=>$aiText
      ]);
      $ticket->update(['status'=>'ai_answered','last_activity_at'=>now()]);
    } catch (\Throwable $e) {
      Log::error('AI error: '.$e->getMessage());
    }

    if ($r->expectsJson()) {
      return response()->json([
        'ok'=>true,
        'status'=>$ticket->status,
        'appended'=>array_values(array_filter([
          $userMsg ? [
            'id'=>$userMsg->id,'type'=>'user','body'=>$userMsg->body,
            'created_at'=>$userMsg->created_at->format('Y-m-d H:i:s'),
          ] : null,
          $aiMsg ? [
            'id'=>$aiMsg->id,'type'=>'ai','body'=>$aiMsg->body,
            'created_at'=>$aiMsg->created_at->format('Y-m-d H:i:s'),
          ] : null
        ])),
      ]);
    }

    return back();
  }

  public function escalar(Request $r, HelpTicket $ticket) {
    abort_unless($ticket->user_id === Auth::id(), 403);
    $ticket->update(['status'=>'pending_agent','last_activity_at'=>now()]);
    $sys = HelpMessage::create([
      'ticket_id'=>$ticket->id,
      'sender_type'=>'system',
      'body'=>'El caso fue escalado. Un agente te responderá aquí mismo.',
      'meta'=>['reason'=>'user_escalation']
    ]);

    if ($r->expectsJson()) {
      return response()->json([
        'ok'=>true,
        'status'=>'pending_agent',
        'appended'=>[[
          'id'=>$sys->id,'type'=>'system','body'=>$sys->body,
          'created_at'=>$sys->created_at->format('Y-m-d H:i:s')
        ]],
      ]);
    }

    return back()->with('ok','Hemos escalado tu caso. Te escribiremos aquí.');
  }
}
