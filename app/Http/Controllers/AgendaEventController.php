<?php

namespace App\Http\Controllers;

use App\Models\AgendaEvent;
use App\Jobs\SendAgendaReminderJob;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AgendaEventController extends Controller
{
    public function calendar()
    {
        return view('agenda.calendar');
    }

    public function feed(Request $r)
    {
        $start = $r->query('start');
        $end   = $r->query('end');

        $query = AgendaEvent::query()
            ->when($start, fn($q) => $q->where('start_at', '>=', $start))
            ->when($end,   fn($q) => $q->where('start_at', '<=', $end));

        $events = $query->get()
            ->filter(fn($e) => (bool)$e->start_at)
            ->map(function (AgendaEvent $e) {
                // devolver start en ISO usando la zona del evento (para FullCalendar)
                $startIso = $e->start_at ? $e->start_at->toIso8601String() : null;
                $nextIso  = $e->next_reminder_at ? $e->next_reminder_at->toIso8601String() : null;

                return [
                    'id'    => $e->id,
                    'title' => $e->title,
                    'start' => $startIso,
                    'extendedProps' => [
                        'description'            => $e->description,
                        'timezone'               => $e->timezone,
                        'repeat_rule'            => $e->repeat_rule,
                        'remind_offset_minutes'  => $e->remind_offset_minutes,
                        'attendee_name'          => $e->attendee_name,
                        'attendee_email'         => $e->attendee_email,
                        'attendee_phone'         => $e->attendee_phone,
                        'send_email'             => (bool)$e->send_email,
                        'send_whatsapp'          => (bool)$e->send_whatsapp,
                        'next_reminder_at'       => $nextIso,
                    ],
                ];
            })->values();

        return response()->json($events);
    }

    /**
     * Convierte input datetime-local (YYYY-MM-DDTHH:mm) interpretándolo en la timezone dada,
     * y devuelve string 'Y-m-d H:i:s' (naive local) para guardar tal cual en DB.
     */
    protected function datetimeLocalToDbString(string $input, string $timezone): string
    {
        $input = trim($input);

        try {
            // Si viene con segundos o Z/offset, intentar parsear y formatear en tz
            if (preg_match('/T\d{2}:\d{2}:\d{2}/', $input)
                || preg_match('/(Z|[+\-]\d{2}:?\d{2})$/i', $input)) {

                $dt = Carbon::parse($input)->setTimezone($timezone);
            } else {
                // formato datetime-local "Y-m-dTH:i"
                $dt = Carbon::createFromFormat('Y-m-d\TH:i', $input, $timezone);
            }

            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable $ex) {
            Log::warning("datetimeLocalToDbString fallback parse '{$input}' tz '{$timezone}': ".$ex->getMessage());
            // fallback: parse genérico
            $dt = Carbon::parse($input)->setTimezone($timezone);
            return $dt->format('Y-m-d H:i:s');
        }
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'title'                 => ['required','string','max:180'],
            'description'           => ['nullable','string','max:2000'],
            'start_at'              => ['required','string'],
            'remind_offset_minutes' => ['required','integer','min:1','max:10080'],
            'repeat_rule'           => ['required','in:none,daily,weekly,monthly'],
            'timezone'              => ['required','string','max:80'],
            'attendee_name'         => ['nullable','string','max:160'],
            'attendee_email'        => ['nullable','email','max:160'],
            'attendee_phone'        => ['nullable','string','max:30'],
            'send_email'            => ['nullable','boolean'],
            'send_whatsapp'         => ['nullable','boolean'],
        ]);

        $data['send_email']    = (bool)($data['send_email'] ?? false);
        $data['send_whatsapp'] = (bool)($data['send_whatsapp'] ?? false);
        $tz = $data['timezone'] ?: config('app.timezone');

        // Convertir la entrada (datetime-local) a string "Y-m-d H:i:s"
        $data['start_at'] = $this->datetimeLocalToDbString($data['start_at'], $tz);

        $event = new AgendaEvent($data);
        $event->computeNextReminder(); // calcula start_at y next_reminder_at
        $event->save();

        return response()->json(['ok' => true, 'id' => $event->id]);
    }

    public function show(AgendaEvent $agenda)
    {
        return response()->json($agenda);
    }

    public function update(Request $r, AgendaEvent $agenda)
    {
        $data = $r->validate([
            'title'                 => ['required','string','max:180'],
            'description'           => ['nullable','string','max:2000'],
            'start_at'              => ['required','string'],
            'remind_offset_minutes' => ['required','integer','min:1','max:10080'],
            'repeat_rule'           => ['required','in:none,daily,weekly,monthly'],
            'timezone'              => ['required','string','max:80'],
            'attendee_name'         => ['nullable','string','max:160'],
            'attendee_email'        => ['nullable','email','max:160'],
            'attendee_phone'        => ['nullable','string','max:30'],
            'send_email'            => ['nullable','boolean'],
            'send_whatsapp'         => ['nullable','boolean'],
        ]);

        $data['send_email']    = (bool)($data['send_email'] ?? false);
        $data['send_whatsapp'] = (bool)($data['send_whatsapp'] ?? false);
        $tz = $data['timezone'] ?: config('app.timezone');

        $data['start_at'] = $this->datetimeLocalToDbString($data['start_at'], $tz);

        $agenda->fill($data);
        $agenda->computeNextReminder();
        $agenda->save();

        return response()->json(['ok' => true]);
    }

    public function destroy(AgendaEvent $agenda)
    {
        $agenda->delete();
        return response()->json(['ok' => true]);
    }

    public function move(Request $r, AgendaEvent $agenda)
    {
        $data = $r->validate([
            'start_at' => ['required','string'],
            'timezone' => ['nullable','string'],
        ]);

        $tz = $data['timezone'] ?? $agenda->timezone ?? config('app.timezone');

        $agenda->start_at = $this->datetimeLocalToDbString($data['start_at'], $tz);
        $agenda->computeNextReminder();
        $agenda->save();

        return response()->json(['ok' => true]);
    }
}
