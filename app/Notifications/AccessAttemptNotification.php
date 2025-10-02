<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class AccessAttemptNotification extends Notification
{
    public function __construct(
        public string $email,
        public string $ip,
        public string $userAgent,
        public string $status // 'success' | 'failed' | 'lockout'
    ) {}

    public function via($notifiable): array
    {
        // Guardamos en BD para el panel (añade 'mail' si quieres correo).
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $title = match ($this->status) {
            'success' => 'Inicio de sesión',
            'lockout' => 'Bloqueo por intentos',
            default   => 'Intento de acceso',
        };

        $message = match ($this->status) {
            'success' => "Acceso correcto de {$this->email}",
            'lockout' => "Bloqueo por intentos de {$this->email}",
            default   => "Intento fallido de {$this->email}",
        };

        return [
            'title'   => $title,
            'message' => $message,
            'email'   => $this->email,
            'ip'      => $this->ip,
            'ua'      => $this->userAgent,
            'status'  => $this->status,
            'url'     => route('admin.users.index'), // cambia si quieres otro destino
        ];
    }
}
