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
        'end_at',
        'remind_offset_minutes',
        'repeat_rule',
        'timezone',

        'user_ids',

        'send_email',
        'send_whatsapp',

        'all_day',
        'completed',
        'color',
        'category',
        'priority',
        'location',
        'notes',

        'next_reminder_at',
        'last_reminder_sent_at',
    ];

    protected $casts = [
        'send_email'          => 'boolean',
        'send_whatsapp'       => 'boolean',
        'all_day'             => 'boolean',
        'completed'           => 'boolean',
        'user_ids'            => 'array',
        'last_reminder_sent_at' => 'datetime',
    ];

    public function setStartAtAttribute($value)
    {
        $this->attributes['start_at'] = $this->normalizeDateTimeValue($value);
    }

    public function getStartAtAttribute($value)
    {
        return $this->castDateTimeFromStorage($value);
    }

    public function setEndAtAttribute($value)
    {
        $this->attributes['end_at'] = $this->normalizeDateTimeValue($value);
    }

    public function getEndAtAttribute($value)
    {
        return $this->castDateTimeFromStorage($value);
    }

    public function setNextReminderAtAttribute($value)
    {
        $this->attributes['next_reminder_at'] = $this->normalizeDateTimeValue($value);
    }

    public function getNextReminderAtAttribute($value)
    {
        return $this->castDateTimeFromStorage($value);
    }

    protected function normalizeDateTimeValue($value): ?string
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_string($value)) {
            $value = trim($value);

            if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
                return str_replace('T', ' ', $value) . ':00';
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $value)) {
                return Carbon::parse($value)->format('Y-m-d H:i:s');
            }
        }

        return $value;
    }

    protected function castDateTimeFromStorage($value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        $tz = $this->timezone ?: config('app.timezone');

        return Carbon::createFromFormat('Y-m-d H:i:s', $value, $tz)->setTimezone($tz);
    }

    public function computeNextReminder(): void
    {
        $tz = $this->timezone ?: config('app.timezone');

        /** @var Carbon|null $start */
        $start = $this->start_at;

        if (!$start || !$this->remind_offset_minutes) {
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

        if (!$start || !$this->remind_offset_minutes) {
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

        if ($this->end_at) {
            $durationMinutes = $this->start_at && $this->end_at
                ? $this->start_at->diffInMinutes($this->end_at, false)
                : null;

            if ($durationMinutes !== null && $durationMinutes > 0) {
                $newEnd = $start->copy()->addMinutes($durationMinutes);
                $this->attributes['end_at'] = $newEnd->format('Y-m-d H:i:s');
            }
        }

        $next = $start->copy()->subMinutes((int) $this->remind_offset_minutes);
        $this->attributes['next_reminder_at'] = $next->format('Y-m-d H:i:s');
    }
}