<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketReviewApproved extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket, public ?int $rating = null) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'   => 'ticket_review_approved',
            'id'     => $this->ticket->id,
            'folio'  => $this->ticket->folio,
            'title'  => $this->ticket->title,
            'rating' => $this->rating,
            'url'    => route('tickets.show', $this->ticket),
            'msg'    => "Tu ticket {$this->ticket->folio} fue aprobado" . ($this->rating ? " (calificación {$this->rating}/5)." : "."),
        ];
    }
}