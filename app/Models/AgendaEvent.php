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
    // para evitar conversiones automáticas inesperadas. Usamos accessors/mutators.
    protected $casts = [
        'send_email'   => 'boolean',
        'send_whatsapp'=> 'boolean',
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

    /**
     * Recalcula next_reminder_at.
     *
     * 🔹 IMPORTANTE:
     * - SIEMPRE fija el primer recordatorio en start_at - offset.
     * - YA NO se “brinca” automáticamente al siguiente día si el reminder quedó en el pasado.
     *   Eso permite que si creas uno muy cerca (ej. 2 min antes), igual lo dispare hoy.
     */
    public function computeNextReminder(): void
    {
        $tz = $this->timezone ?: config('app.timezone');

        /** @var Carbon|null $start */
        $start = $this->start_at; // accessor devuelve Carbon o null

        if (! $start || ! $this->remind_offset_minutes) {
            $this->next_reminder_at = null;
            return;
        }

        // Aseguramos que trabajamos en la zona del evento
        $start = $start->copy()->setTimezone($tz);

        // Recordatorio = start - offset (sin lógica de "si ya pasó")
        $reminder = $start->copy()->subMinutes((int) $this->remind_offset_minutes);

        // Guardar start_at y next_reminder_at como strings "Y-m-d H:i:s" (naive local)
        $this->attributes['start_at'] = $start->format('Y-m-d H:i:s');
        $this->attributes['next_reminder_at'] = $reminder->format('Y-m-d H:i:s');
    }

    /**
     * Después de enviar el recordatorio:
     *  - Si NO hay repetición → se apaga (next_reminder_at = null).
     *  - Si hay repetición diaria/semanal/mensual → mueve start_at y recalcula next_reminder_at.
     */
    public function advanceAfterSending(): void
    {
        $tz = $this->timezone ?: config('app.timezone');

        /** @var Carbon|null $start */
        $start = $this->start_at; // accessor (Carbon) o null

        if (! $start || ! $this->remind_offset_minutes) {
            $this->next_reminder_at = null;
            return;
        }

        if ($this->repeat_rule === 'none') {
            // Solo un recordatorio
            $this->next_reminder_at = null;
            return;
        }

        // Avanzar el start según regla, en la zona del evento
        $start = $start->copy()->setTimezone($tz);

        switch ($this->repeat_rule) {
            case 'daily':
                $start->addDay();
                break;
            case 'weekly':
                $start->addWeek();
                break;
            case 'monthly':
                $start->addMonth();
                break;
            default:
                // regla desconocida → no repetimos
                $this->next_reminder_at = null;
                return;
        }

        // Nuevo start y nuevo reminder
        $this->attributes['start_at'] = $start->format('Y-m-d H:i:s');

        $next = $start->copy()->subMinutes((int) $this->remind_offset_minutes);
        $this->attributes['next_reminder_at'] = $next->format('Y-m-d H:i:s');
    }
}
