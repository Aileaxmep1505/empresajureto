<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TicketReviewApproved extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket, public ?int $rating = null) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
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
            'status' => 'success',
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $folio = $this->ticket->folio ?? ('#' . $this->ticket->id);

        $mail = (new MailMessage)
            ->subject("Ticket aprobado: {$folio}")
            ->greeting("Hola {$notifiable->name},")
            ->line("Tu ticket {$folio} fue aprobado.");

        if ($this->rating) {
            $mail->line("Calificación: {$this->rating}/5");
        }

        return $mail
            ->action('Ver ticket', route('tickets.show', $this->ticket))
            ->line('El ticket ya quedó validado.');
    }
}