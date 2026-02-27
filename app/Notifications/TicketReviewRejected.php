<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketReviewRejected extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket, public string $reason) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'   => 'ticket_review_rejected',
            'id'     => $this->ticket->id,
            'folio'  => $this->ticket->folio,
            'title'  => $this->ticket->title,
            'reason' => $this->reason,
            'url'    => route('tickets.work', $this->ticket),
            'msg'    => "El ticket {$this->ticket->folio} fue reabierto. Motivo: ".$this->reason,
        ];
    }
}