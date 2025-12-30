<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AgendaEvent extends Model
{
    protected $fillable = [
        'title',
        'description',
        'start_at',
        'remind_offset_minutes',
        'repeat_rule',
        'timezone',

        // ✅ NUEVO: usuarios destinatarios
        'user_ids',

        // ✅ canales (los fuerzas en controller)
        'send_email',
        'send_whatsapp',

        'next_reminder_at',
        'last_reminder_sent_at',
    ];

    protected $casts = [
        'send_email'    => 'boolean',
        'send_whatsapp' => 'boolean',

        // ✅ NUEVO
        'user_ids'      => 'array',
    ];

    public function setStartAtAttribute($value)
    {
        if ($value instanceof Carbon) {
            $this->attributes['start_at'] = $value->format('Y-m-d H:i:s');
            return;
        }

        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
            $this->attributes['start_at'] = str_replace('T', ' ', $value) . ':00';
            return;
        }

        $this->attributes['start_at'] = $value;
    }

    public function getStartAtAttribute($value)
    {
        if (! $value) return null;
        $tz = $this->timezone ?: config('app.timezone');
        return Carbon::createFromFormat('Y-m-d H:i:s', $value, $tz)->setTimezone($tz);
    }

    public function setNextReminderAtAttribute($value)
    {
        if ($value instanceof Carbon) {
            $this->attributes['next_reminder_at'] = $value->format('Y-m-d H:i:s');
            return;
        }
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $value)) {
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

    public function computeNextReminder(): void
    {
        $tz = $this->timezone ?: config('app.timezone');

        /** @var Carbon|null $start */
        $start = $this->start_at;

        if (! $start || ! $this->remind_offset_minutes) {
            $this->next_reminder_at = null;
            return;
        }

        $start = $start->copy()->setTimezone($tz);

        $reminder = $start->copy()->subMinutes((int) $this->remind_offset_minutes);

        $this->attributes['start_at'] = $start->format('Y-m-d H:i:s');
        $this->attributes['next_reminder_at'] = $reminder->format('Y-m-d H:i:s');
    }

    public function advanceAfterSending(): void
    {
        $tz = $this->timezone ?: config('app.timezone');

        /** @var Carbon|null $start */
        $start = $this->start_at;

        if (! $start || ! $this->remind_offset_minutes) {
            $this->next_reminder_at = null;
            return;
        }

        if ($this->repeat_rule === 'none') {
            $this->next_reminder_at = null;
            return;
        }

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
                $this->next_reminder_at = null;
                return;
        }

        $this->attributes['start_at'] = $start->format('Y-m-d H:i:s');

        $next = $start->copy()->subMinutes((int) $this->remind_offset_minutes);
        $this->attributes['next_reminder_at'] = $next->format('Y-m-d H:i:s');
    }
}
