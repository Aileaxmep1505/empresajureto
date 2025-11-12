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

    // NOTA: no casteamos 'start_at' ni 'next_reminder_at' a datetime automáticamente
    // para evitar conversiones automáticas inesperadas. Usaremos accessors/mutators.
    protected $casts = [
        'send_email' => 'boolean',
        'send_whatsapp' => 'boolean',
    ];

    /**
     * Mutator: recibimos string "Y-m-d H:i:s" en DB.
     * Cuando se setea start_at en código, lo dejamos tal cual (string).
     */
    public function setStartAtAttribute($value)
    {
        // Si nos pasan Carbon o DateTime, normalizamos al formato 'Y-m-d H:i:s' sin timezone.
        if ($value instanceof Carbon) {
            $this->attributes['start_at'] = $value->format('Y-m-d H:i:s');
            return;
        }

        // Si viene en formato datetime-local "Y-m-d\TH:i", convertir a "Y-m-d H:i:s"
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
            $this->attributes['start_at'] = str_replace('T', ' ', $value) . ':00';
            return;
        }

        // Guardar tal cual (si ya es "Y-m-d H:i:s")
        $this->attributes['start_at'] = $value;
    }

    /**
     * Accessor: cuando pides $model->start_at te devolvemos Carbon en la zona del evento.
     * Si no hay timezone, usamos config('app.timezone').
     */
    public function getStartAtAttribute($value)
    {
        if (! $value) return null;
        $tz = $this->timezone ?: config('app.timezone');
        return Carbon::createFromFormat('Y-m-d H:i:s', $value, $tz)->setTimezone($tz);
    }

    /**
     * Mutator/Accessor para next_reminder_at: guardamos y devolvemos en la misma zona local.
     */
    public function setNextReminderAtAttribute($value)
    {
        if ($value instanceof Carbon) {
            $this->attributes['next_reminder_at'] = $value->format('Y-m-d H:i:s');
            return;
        }
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $value)) {
            // ISO -> convertir a naive local (no offset)
            $this->attributes['next_reminder_at'] = Carbon::parse($value)->format('Y-m-d H:i:s');
            return;
        }
        $this->attributes['next_reminder_at'] = $value;
    }

    public function getNextReminderAtAttribute($value)
    {
        if (! $value) return null;
        $tz = $this->timezone ?: config('app.timezone');
        return Carbon::createFromFormat('Y-m-d H:i:s', $value, $tz)->setTimezone($tz);
    }

    /** Recalcula next_reminder_at (trabaja en la zona local del evento; guarda valores "naive" en DB) */
    public function computeNextReminder(): void
    {
        // Asegurar start_at existe y es Carbon (local tz) via accessor
        $tz = $this->timezone ?: config('app.timezone');
        $start = $this->start_at; // accessor devuelve Carbon o null

        if (! $start) {
            $this->next_reminder_at = null;
            return;
        }

        // start ya es Carbon en zona $tz; clonar para manipular
        $start = $start->copy()->setTimezone($tz);
        $reminder = $start->copy()->subMinutes((int)$this->remind_offset_minutes);

        $now = Carbon::now($tz);

        // Si pasó, y tiene regla, avanzar hasta la siguiente ocurrencia que deje reminder >= now
        if ($reminder->lt($now) && $this->repeat_rule !== 'none') {
            // intentar avanzar hasta quedar en futuro (limit guard rails)
            $attempts = 0;
            while ($reminder->lt($now) && $attempts < 3650) { // safety: no loop infinito
                match($this->repeat_rule) {
                    'daily'   => $start->addDay(),
                    'weekly'  => $start->addWeek(),
                    'monthly' => $start->addMonth(),
                    default   => null
                };
                $reminder = $start->copy()->subMinutes((int)$this->remind_offset_minutes);
                $attempts++;
            }
        }

        // Guardar start_at y next_reminder_at como strings "Y-m-d H:i:s" (naive local)
        // start puede haber cambiado (por repetición)
        $this->attributes['start_at'] = $start->format('Y-m-d H:i:s');
        $this->attributes['next_reminder_at'] = $reminder->format('Y-m-d H:i:s');
    }

    /** Después de enviar (marcar enviado y avanzar si es repetitivo) */
    public function advanceAfterSending(): void
    {
        $tz = $this->timezone ?: config('app.timezone');
        $start = $this->start_at; // Carbon local or null
        if (! $start) {
            $this->next_reminder_at = null;
            return;
        }

        if ($this->repeat_rule === 'none') {
            $this->next_reminder_at = null;
            return;
        }

        // Avanzar el start según regla
        match($this->repeat_rule) {
            'daily'   => $start = $start->copy()->addDay(),
            'weekly'  => $start = $start->copy()->addWeek(),
            'monthly' => $start = $start->copy()->addMonth(),
            default   => $start = $start->copy()
        };

        $this->attributes['start_at'] = $start->format('Y-m-d H:i:s');
        $next = $start->copy()->subMinutes((int)$this->remind_offset_minutes);
        $this->attributes['next_reminder_at'] = $next->format('Y-m-d H:i:s');
    }
}
