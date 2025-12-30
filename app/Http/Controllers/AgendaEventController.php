<?php

namespace App\Http\Controllers;

use App\Models\AgendaEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AgendaEventController extends Controller
{
    public function calendar()
    {
        return view('agenda.calendar');
    }

    /**
     * Endpoint para llenar el selector del modal (chips).
     * route('agenda.users')
     *
     * Importante: regresamos phone también para mostrarlo en el chip.
     */
    public function users()
    {
        return response()->json(
            User::query()
                ->select(['id','name','email','phone'])
                ->orderBy('name')
                ->get()
        );
    }

    public function feed(Request $r)
    {
        $start = $r->query('start');
        $end   = $r->query('end');

        $startDb = null;
        $endDb   = null;

        try {
            if ($start) $startDb = Carbon::parse($start)->format('Y-m-d H:i:s');
            if ($end)   $endDb   = Carbon::parse($end)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            Log::warning('Agenda feed: no se pudo parsear rango start/end: '.$e->getMessage());
        }

        $query = AgendaEvent::query()
            ->when($startDb, fn($q) => $q->where('start_at', '>=', $startDb))
            ->when($endDb,   fn($q) => $q->where('start_at', '<=', $endDb));

        $events = $query->get()
            ->filter(fn($e) => (bool) $e->start_at)
            ->map(function (AgendaEvent $e) {
                $startIso = $e->start_at ? $e->start_at->toIso8601String() : null;
                $nextIso  = $e->next_reminder_at ? $e->next_reminder_at->toIso8601String() : null;

                return [
                    'id'    => $e->id,
                    'title' => $e->title,
                    'start' => $startIso,
                    'extendedProps' => [
                        'description'           => $e->description,
                        'timezone'              => $e->timezone,
                        'repeat_rule'           => $e->repeat_rule,
                        'remind_offset_minutes' => $e->remind_offset_minutes,

                        // ✅ destinatarios por usuarios
                        'user_ids'              => $e->user_ids ?? [],

                        // ✅ siempre true en tu UI nueva (backend lo fuerza)
                        'send_email'            => (bool) $e->send_email,
                        'send_whatsapp'         => (bool) $e->send_whatsapp,

                        'next_reminder_at'      => $nextIso,
                    ],
                ];
            })->values();

        return response()->json($events);
    }

    /**
     * Convierte input datetime (ISO o datetime-local) interpretándolo en la timezone dada,
     * y devuelve string 'Y-m-d H:i:s' para guardar en DB.
     */
    protected function datetimeLocalToDbString(string $input, string $timezone): string
    {
        $input = trim($input);

        try {
            if (preg_match('/T\d{2}:\d{2}:\d{2}/', $input) || preg_match('/(Z|[+\-]\d{2}:?\d{2})$/i', $input)) {
                $dt = Carbon::parse($input)->setTimezone($timezone);
            } else {
                $dt = Carbon::createFromFormat('Y-m-d\TH:i', $input, $timezone);
            }

            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable $ex) {
            Log::warning("datetimeLocalToDbString fallback parse '{$input}' tz '{$timezone}': ".$ex->getMessage());
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

            'user_ids'              => ['required','array','min:1'],
            'user_ids.*'            => ['integer','exists:users,id'],

            'send_email'            => ['nullable','boolean'],
            'send_whatsapp'         => ['nullable','boolean'],
        ]);

        $tz = 'America/Mexico_City';
        $data['timezone'] = $tz;

        // ✅ SIEMPRE
        $data['send_email'] = true;
        $data['send_whatsapp'] = true;

        $data['user_ids'] = array_values(array_unique(array_map('intval', $data['user_ids'])));
        $data['start_at'] = $this->datetimeLocalToDbString($data['start_at'], $tz);

        $event = new AgendaEvent($data);
        $event->computeNextReminder();
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

            'user_ids'              => ['required','array','min:1'],
            'user_ids.*'            => ['integer','exists:users,id'],

            'send_email'            => ['nullable','boolean'],
            'send_whatsapp'         => ['nullable','boolean'],
        ]);

        $tz = 'America/Mexico_City';

        $data['timezone'] = $tz;
        $data['send_email'] = true;
        $data['send_whatsapp'] = true;
        $data['user_ids'] = array_values(array_unique(array_map('intval', $data['user_ids'])));
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
        ]);

        $tz = $agenda->timezone ?: 'America/Mexico_City';

        $agenda->start_at = $this->datetimeLocalToDbString($data['start_at'], $tz);
        $agenda->computeNextReminder();
        $agenda->save();

        return response()->json(['ok' => true]);
    }
}
