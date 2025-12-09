<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

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
     * Solo la guardamos en base de datos (panel interno).
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Payload que se guarda en la tabla notifications->data (JSON).
     */
    public function toDatabase($notifiable)
    {
        // Vista de trabajo del ticket (no la de configuración)
        $routeName   = 'tickets.work';
        $routeParams = ['ticket' => $this->ticket->id];

        return [
            'title'        => 'Nuevo ticket asignado',
            'message'      => sprintf(
                'Se te asignó el ticket %s: %s',
                $this->ticket->folio ?? ('#' . $this->ticket->id),
                $this->ticket->title ?? $this->ticket->subject ?? ''
            ),
            'status'       => 'info', // info | warn | error (para el color de la pill)

            // 🔗 URL que usará el panel de notificaciones para redirigir
            'url'          => route($routeName, $routeParams),

            // Opcional, por si luego quieres reconstruir la URL desde el front
            'route'        => $routeName,
            'route_params' => $routeParams,
        ];
    }
}
