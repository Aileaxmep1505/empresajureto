<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TicketSubmittedForReview extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    public function via($notifiable): array
    {
        return ['database']; // o ['mail','database']
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'  => 'ticket_submitted_for_review',
            'id'    => $this->ticket->id,
            'folio' => $this->ticket->folio,
            'title' => $this->ticket->title,
            'url'   => route('tickets.show', $this->ticket),
            'msg'   => "El ticket {$this->ticket->folio} fue finalizado y requiere tu revisión.",
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ticket por revisar: {$this->ticket->folio}")
            ->line("El ticket fue finalizado y requiere tu revisión.")
            ->action('Ver ticket', route('tickets.show', $this->ticket));
    }
}