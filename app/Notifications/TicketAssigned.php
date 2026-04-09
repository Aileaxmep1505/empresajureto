<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TicketAssigned extends Notification
{
    use Queueable;

    /**
     * Ticket que se asignó al usuario.
     */
    public function __construct(public Ticket $ticket)
    {
    }

    /**
     * Guardar en panel interno + enviar correo.
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Payload que se guarda en notifications->data
     */
    public function toDatabase($notifiable): array
    {
        $routeName   = 'tickets.work';
        $routeParams = ['ticket' => $this->ticket->id];

        return [
            'type'         => 'ticket_assigned',
            'title'        => 'Nuevo ticket asignado',
            'message'      => sprintf(
                'Se te asignó el ticket %s: %s',
                $this->ticket->folio ?? ('#' . $this->ticket->id),
                $this->ticket->title ?? $this->ticket->subject ?? ''
            ),
            'status'       => 'info',
            'url'          => route($routeName, $routeParams),
            'route'        => $routeName,
            'route_params' => $routeParams,
            'ticket_id'    => $this->ticket->id,
            'folio'        => $this->ticket->folio,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $folio = $this->ticket->folio ?? ('#' . $this->ticket->id);
        $title = $this->ticket->title ?? $this->ticket->subject ?? 'Sin título';

        return (new MailMessage)
            ->subject("Nuevo ticket asignado: {$folio}")
            ->greeting("Hola {$notifiable->name},")
            ->line("Se te asignó un nuevo ticket.")
            ->line("**Folio:** {$folio}")
            ->line("**Título:** {$title}")
            ->action('Abrir ticket', route('tickets.work', ['ticket' => $this->ticket->id]))
            ->line('Revisa el ticket para comenzar a trabajarlo.');
    }
}