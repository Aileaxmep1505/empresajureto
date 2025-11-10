<?php
// app/Http/Controllers/Tickets/TicketCommentController.php
namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Ticket, TicketComment, TicketAudit, TicketFollower, User};
use App\Notifications\TicketMentioned;

class TicketCommentController extends Controller
{
  public function store(Request $r, Ticket $ticket){
    $data = $r->validate(['body'=>['required','string']]);

    // Parse @menciones (simple)
    preg_match_all('/@([A-Za-z0-9._-]+)/', $data['body'], $m);
    $usernames = collect($m[1] ?? [])->unique()->values();

    $mentionedIds = User::whereIn('name', $usernames)->pluck('id')->all();
    $comment = TicketComment::create([
      'ticket_id'=>$ticket->id,'user_id'=>auth()->id(),'body'=>$data['body'],'mentions'=>$mentionedIds
    ]);

    TicketAudit::create(['ticket_id'=>$ticket->id,'user_id'=>auth()->id(),'action'=>'comment_added','diff'=>['comment_id'=>$comment->id]]);

    // Notificar mencionados
    if (!empty($mentionedIds)){
      $users = User::whereIn('id', $mentionedIds)->get();
      foreach ($users as $u) $u->notify(new TicketMentioned($ticket, $comment));
    }

    // Auto-follow
    TicketFollower::firstOrCreate(['ticket_id'=>$ticket->id,'user_id'=>auth()->id()]);

    return back()->with('ok','Comentario agregado');
  }
}
