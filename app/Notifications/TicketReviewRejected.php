<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TicketReviewRejected extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket, public string $reason) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
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
            'msg'    => "El ticket {$this->ticket->folio} fue reabierto. Motivo: " . $this->reason,
            'status' => 'error',
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $folio = $this->ticket->folio ?? ('#' . $this->ticket->id);

        return (new MailMessage)
            ->subject("Ticket rechazado: {$folio}")
            ->greeting("Hola {$notifiable->name},")
            ->line("El ticket {$folio} fue rechazado en revisión y se reabrió.")
            ->line("Motivo: {$this->reason}")
            ->action('Abrir ticket', route('tickets.work', $this->ticket));
    }
}