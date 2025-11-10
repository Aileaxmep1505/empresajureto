<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\{Ticket, TicketComment};

class TicketMentioned extends Notification
{
  use Queueable;
  public function __construct(public Ticket $ticket, public TicketComment $comment) {}

  public function via($notifiable){ return ['mail']; /* agrega 'database' o WhatsAppService si gustas */ }

  public function toMail($notifiable){
    return (new MailMessage)
      ->subject('Te mencionaron en un ticket: '.$this->ticket->folio)
      ->line('Comentario: "'.$this->comment->body.'"')
      ->action('Ver ticket', route('tickets.show',$this->ticket));
  }
}
