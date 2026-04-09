<?php

namespace App\Notifications;

use App\Models\AgendaEvent;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AgendaReminderSystemNotification extends Notification
{
    use Queueable;

    public function __construct(public AgendaEvent $event)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $tz = $this->event->timezone ?: 'America/Mexico_City';

        $startAt = $this->event->start_at
            ? Carbon::parse($this->event->start_at, $tz)->timezone($tz)
            : null;

        $formattedDate = $startAt
            ? $startAt->translatedFormat('d/m/Y h:i A')
            : 'Sin fecha';

        return [
            'title'    => 'Recordatorio de agenda',
            'message'  => "Tienes un recordatorio: {$this->event->title} · {$formattedDate}",
            'status'   => 'info',
            'event_id' => $this->event->id,
            'url'      => route('agenda.calendar'),
        ];
    }
}