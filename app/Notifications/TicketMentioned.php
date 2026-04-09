<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Ticket;
use App\Models\TicketComment;

class TicketMentioned extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket, public TicketComment $comment) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'      => 'ticket_mentioned',
            'title'     => 'Te mencionaron en un ticket',
            'message'   => 'Te mencionaron en el ticket ' . ($this->ticket->folio ?? ('#' . $this->ticket->id)),
            'status'    => 'info',
            'ticket_id' => $this->ticket->id,
            'folio'     => $this->ticket->folio,
            'comment_id'=> $this->comment->id,
            'url'       => route('tickets.show', $this->ticket),
            'msg'       => 'Comentario: "' . $this->comment->body . '"',
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $folio = $this->ticket->folio ?? ('#' . $this->ticket->id);

        return (new MailMessage)
            ->subject("Te mencionaron en un ticket: {$folio}")
            ->greeting("Hola {$notifiable->name},")
            ->line("Te mencionaron en el ticket {$folio}.")
            ->line('Comentario:')
            ->line('"' . $this->comment->body . '"')
            ->action('Ver ticket', route('tickets.show', $this->ticket));
    }
}