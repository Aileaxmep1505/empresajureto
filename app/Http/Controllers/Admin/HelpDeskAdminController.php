<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HelpTicket;
use App\Models\HelpMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HelpDeskAdminController extends Controller
{
  public function index(Request $r) {
    $q = HelpTicket::query()->with('user')
      ->when($r->filled('status'), fn($qq)=>$qq->where('status',$r->status))
      ->orderByRaw("FIELD(status,'pending_agent','ai_answered','waiting_user','new','agent_answered','closed')")
      ->orderByDesc('last_activity_at');

    $tickets = $q->paginate(20);
    return view('admin.ayuda.index', compact('tickets'));
  }

  public function show(HelpTicket $ticket) {
    $ticket->load(['user','messages.sender']);
    return view('admin.ayuda.show', compact('ticket'));
  }

  public function reply(Request $r, HelpTicket $ticket) {
    $data = $r->validate([
      'body'=>'required|string|min:2',
      'solve'=>'nullable|boolean',
    ]);

    HelpMessage::create([
      'ticket_id'=>$ticket->id,
      'sender_type'=>'agent',
      'sender_id'=>Auth::id(),
      'body'=>$data['body'],
      'is_solution'=>$r->boolean('solve'),
    ]);

    $ticket->update([
      'status'=> $r->boolean('solve') ? 'closed' : 'agent_answered',
      'resolved_by_id'=> $r->boolean('solve') ? Auth::id() : null,
      'last_activity_at'=>now(),
    ]);

    return back()->with('ok','Respuesta enviada.');
  }

  public function close(HelpTicket $ticket) {
    $ticket->update(['status'=>'closed','resolved_by_id'=>Auth::id(),'last_activity_at'=>now()]);
    return back()->with('ok','Ticket cerrado.');
  }
}
