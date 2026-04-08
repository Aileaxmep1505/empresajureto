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
        'send_email'            => 'boolean',
        'send_whatsapp'         => 'boolean',
        'all_day'               => 'boolean',
        'completed'             => 'boolean',
        'user_ids'              => 'array',
        'last_reminder_sent_at' => 'datetime',
    ];

    public function setStartAtAttribute($value): void
    {
        $this->attributes['start_at'] = $this->normalizeDateTimeValue($value);
    }

    public function getStartAtAttribute($value): ?Carbon
    {
        return $this->castDateTimeFromStorage($value);
    }

    public function setEndAtAttribute($value): void
    {
        $this->attributes['end_at'] = $this->normalizeDateTimeValue($value);
    }

    public function getEndAtAttribute($value): ?Carbon
    {
        return $this->castDateTimeFromStorage($value);
    }

    public function setNextReminderAtAttribute($value): void
    {
        $this->attributes['next_reminder_at'] = $this->normalizeDateTimeValue($value);
    }

    public function getNextReminderAtAttribute($value): ?Carbon
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

            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $value)) {
                return Carbon::parse($value)->format('Y-m-d H:i:s');
            }
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    protected function castDateTimeFromStorage($value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        $tz = $this->timezone ?: config('app.timezone', 'America/Mexico_City');

        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', $value, $tz)->setTimezone($tz);
        } catch (\Throwable $e) {
            return Carbon::parse($value, $tz)->setTimezone($tz);
        }
    }

    public function computeNextReminder(): void
    {
        $tz = $this->timezone ?: config('app.timezone', 'America/Mexico_City');

        /** @var Carbon|null $start */
        $start = $this->start_at;

        if (!$start || !$this->remind_offset_minutes) {
            $this->attributes['next_reminder_at'] = null;
            return;
        }

        $start = $start->copy()->setTimezone($tz);
        $reminder = $start->copy()->subMinutes((int) $this->remind_offset_minutes);

        $this->attributes['start_at'] = $start->format('Y-m-d H:i:s');
        $this->attributes['next_reminder_at'] = $reminder->format('Y-m-d H:i:s');
    }

    public function advanceAfterSending(): void
    {
        $tz = $this->timezone ?: config('app.timezone', 'America/Mexico_City');

        /** @var Carbon|null $currentStart */
        $currentStart = $this->start_at;

        /** @var Carbon|null $currentEnd */
        $currentEnd = $this->end_at;

        if (!$currentStart || !$this->remind_offset_minutes) {
            $this->attributes['next_reminder_at'] = null;
            return;
        }

        if ($this->repeat_rule === 'none') {
            $this->attributes['next_reminder_at'] = null;
            return;
        }

        $currentStart = $currentStart->copy()->setTimezone($tz);
        $currentEnd   = $currentEnd ? $currentEnd->copy()->setTimezone($tz) : null;

        // calcular duración antes de modificar start_at
        $durationMinutes = null;
        if ($currentEnd && $currentEnd->gte($currentStart)) {
            $durationMinutes = $currentStart->diffInMinutes($currentEnd);
        }

        $newStart = $currentStart->copy();

        switch ($this->repeat_rule) {
            case 'daily':
                $newStart->addDay();
                break;

            case 'weekly':
                $newStart->addWeek();
                break;

            case 'monthly':
                $newStart->addMonthNoOverflow();
                break;

            default:
                $this->attributes['next_reminder_at'] = null;
                return;
        }

        $this->attributes['start_at'] = $newStart->format('Y-m-d H:i:s');

        if ($durationMinutes !== null && $durationMinutes > 0) {
            $newEnd = $newStart->copy()->addMinutes($durationMinutes);
            $this->attributes['end_at'] = $newEnd->format('Y-m-d H:i:s');
        }

        $nextReminder = $newStart->copy()->subMinutes((int) $this->remind_offset_minutes);
        $this->attributes['next_reminder_at'] = $nextReminder->format('Y-m-d H:i:s');
    }
}