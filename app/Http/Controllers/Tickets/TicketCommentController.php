<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Ticket, TicketComment, TicketAudit, User};
use App\Notifications\TicketMentioned;

class TicketCommentController extends Controller
{
    public function store(Request $r, Ticket $ticket)
    {
        $data = $r->validate([
            'body' => ['required', 'string'],
        ]);

        // Parse @menciones (simple): @usuario
        preg_match_all('/@([A-Za-z0-9._-]+)/', $data['body'], $m);
        $usernames = collect($m[1] ?? [])->unique()->values();

        // Nota: aquí usas name como "username". Si quieres usar username real (columna),
        // cámbialo a ->whereIn('username', $usernames)
        $mentionedIds = User::whereIn('name', $usernames)->pluck('id')->all();

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'body'      => $data['body'],
            'mentions'  => $mentionedIds,
        ]);

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'comment_added',
            'diff'      => ['comment_id' => $comment->id],
        ]);

        // Notificar mencionados (si existe Notification)
        if (!empty($mentionedIds) && class_exists(TicketMentioned::class)) {
            $users = User::whereIn('id', $mentionedIds)->get();
            foreach ($users as $u) {
                $u->notify(new TicketMentioned($ticket, $comment));
            }
        }

        return $r->wantsJson()
            ? response()->json(['ok' => true, 'comment_id' => $comment->id])
            : back()->with('ok', 'Comentario agregado');
    }
}