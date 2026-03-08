<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\{Ticket, TicketComment, TicketAudit, User};
use App\Notifications\TicketMentioned;
use App\Services\WhatsApp\WhatsAppService;

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

        // WhatsApp a stakeholders del ticket, excepto al autor del comentario
        $wa = app(WhatsAppService::class);
        $authorName = optional(auth()->user())->name ?: 'Sistema';

        $recipientIds = collect([
            $ticket->created_by ?? null,
            $ticket->assignee_id ?? null,
            Schema::hasColumn('tickets', 'assigned_by') ? $ticket->assigned_by : null,
        ])->filter()
          ->unique()
          ->reject(fn ($id) => (int) $id === (int) auth()->id())
          ->values();

        $users = $recipientIds->isNotEmpty()
            ? User::whereIn('id', $recipientIds)->get()
            : collect();

        foreach ($users as $user) {
            $wa->sendTicketCommentToUser(
                $user,
                $ticket,
                $authorName,
                $comment->body ?? ''
            );
        }

        return $r->wantsJson()
            ? response()->json(['ok' => true, 'comment_id' => $comment->id])
            : back()->with('ok', 'Comentario agregado');
    }
}