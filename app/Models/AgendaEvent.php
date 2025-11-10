<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AgendaEvent extends Model
{
    protected $fillable = [
        'title','description','start_at','remind_offset_minutes','repeat_rule','timezone',
        'attendee_name','attendee_email','attendee_phone',
        'send_email','send_whatsapp','next_reminder_at','last_reminder_sent_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'next_reminder_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
        'send_email' => 'boolean',
        'send_whatsapp' => 'boolean',
    ];

    /** Inicializa y recalcula el próximo recordatorio */
    public function computeNextReminder(): void
    {
        $tz = $this->timezone ?: 'America/Mexico_City';
        $start = $this->start_at->clone()->setTimezone($tz);
        $reminder = $start->copy()->subMinutes($this->remind_offset_minutes);

        // Si ya pasó el evento y es repetitivo, mover siguiente start_at según regla
        $now = Carbon::now($tz);
        while ($reminder->lt($now)) {
            if ($this->repeat_rule === 'none') break;

            match($this->repeat_rule) {
                'daily'   => $start->addDay(),
                'weekly'  => $start->addWeek(),
                'monthly' => $start->addMonth(),
                default   => null
            };
            $reminder = $start->copy()->subMinutes($this->remind_offset_minutes);
        }

        // Guardar potencial nuevo start_at si se movió por repetición
        if ($start->ne($this->start_at)) {
            $this->start_at = $start->setTimezone('UTC');
        }

        $this->next_reminder_at = $reminder->setTimezone('UTC');
    }

    /** Avanza al siguiente ciclo post-envío (para reglas repetidas) */
    public function advanceAfterSending(): void
    {
        $tz = $this->timezone ?: 'America/Mexico_City';
        $start = $this->start_at->clone()->setTimezone($tz);

        if ($this->repeat_rule === 'none') {
            // Un solo recordatorio por evento simple
            $this->next_reminder_at = null;
            return;
        }

        match($this->repeat_rule) {
            'daily'   => $start->addDay(),
            'weekly'  => $start->addWeek(),
            'monthly' => $start->addMonth(),
            default   => null
        };

        $this->start_at = $start->clone()->setTimezone('UTC');
        $this->next_reminder_at = $start->clone()->subMinutes($this->remind_offset_minutes)->setTimezone('UTC');
    }
}
