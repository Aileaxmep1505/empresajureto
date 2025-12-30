<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewMailNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $folder,
        public int $uid,
        public string $from,
        public string $subject,
        public string $snippet = '',
        public ?string $dateTxt = null,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $title = 'Nuevo correo';
        $message = trim($this->from.' · '.$this->subject);
        if ($this->snippet) $message .= ' — '.$this->snippet;

        // URL que tu campanita ya sabe abrir
        $url = route('mail.index').'?folder='.urlencode(strtoupper($this->folder)).'&uid='.$this->uid;

        return [
            'status'  => 'info',      // info | warn | error
            'title'   => $title,
            'message' => $message,
            'url'     => $url,

            // Extra útil (por si luego lo quieres mostrar)
            'meta' => [
                'folder'  => strtoupper($this->folder),
                'uid'     => $this->uid,
                'from'    => $this->from,
                'subject' => $this->subject,
                'when'    => $this->dateTxt,
            ],
        ];
    }
}
