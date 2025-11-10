<?php

namespace App\Http\Controllers;

use App\Models\AgendaEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgendaEventController extends Controller
{
    /** Vista: Calendario */
    public function calendar()
    {
        return view('agenda.calendar');
    }

    /** Feed JSON para FullCalendar (rango visible) */
    public function feed(Request $r)
    {
        $start = $r->query('start'); // ISO
        $end   = $r->query('end');   // ISO

        $query = AgendaEvent::query()
            ->when($start, fn($q)=>$q->where('start_at', '>=', $start))
            ->when($end,   fn($q)=>$q->where('start_at', '<=', $end));

        $events = $query->get()->map(function(AgendaEvent $e){
            // FullCalendar espera "start" y opcional "end" (usaremos start solo)
            return [
                'id'    => $e->id,
                'title' => $e->title,
                'start' => $e->start_at->toIso8601String(),
                'extendedProps' => [
                    'description' => $e->description,
                    'timezone'    => $e->timezone,
                    'repeat_rule' => $e->repeat_rule,
                    'remind_offset_minutes' => $e->remind_offset_minutes,
                    'attendee_name'  => $e->attendee_name,
                    'attendee_email' => $e->attendee_email,
                    'attendee_phone' => $e->attendee_phone,
                    'send_email'     => $e->send_email,
                    'send_whatsapp'  => $e->send_whatsapp,
                    'next_reminder_at' => optional($e->next_reminder_at)->toIso8601String(),
                ],
            ];
        });

        return response()->json($events);
    }

    /** Crear (AJAX) */
    public function store(Request $r)
    {
        $data = $r->validate([
            'title' => ['required','string','max:180'],
            'description' => ['nullable','string','max:2000'],
            'start_at' => ['required','date'],
            'remind_offset_minutes' => ['required','integer','min:1','max:10080'],
            'repeat_rule' => ['required','in:none,daily,weekly,monthly'],
            'timezone' => ['required','string','max:80'],
            'attendee_name' => ['nullable','string','max:160'],
            'attendee_email' => ['nullable','email','max:160'],
            'attendee_phone' => ['nullable','string','max:30'],
            'send_email' => ['nullable','boolean'],
            'send_whatsapp' => ['nullable','boolean'],
        ]);

        $data['send_email'] = (bool)($data['send_email'] ?? false);
        $data['send_whatsapp'] = (bool)($data['send_whatsapp'] ?? false);

        $event = new AgendaEvent($data);
        $event->computeNextReminder();
        $event->save();

        return response()->json(['ok'=>true,'id'=>$event->id]);
    }

    /** Mostrar (AJAX opcional) */
    public function show(AgendaEvent $agenda)
    {
        return response()->json($agenda);
    }

    /** Editar (AJAX) */
    public function update(Request $r, AgendaEvent $agenda)
    {
        $data = $r->validate([
            'title' => ['required','string','max:180'],
            'description' => ['nullable','string','max:2000'],
            'start_at' => ['required','date'],
            'remind_offset_minutes' => ['required','integer','min:1','max:10080'],
            'repeat_rule' => ['required','in:none,daily,weekly,monthly'],
            'timezone' => ['required','string','max:80'],
            'attendee_name' => ['nullable','string','max:160'],
            'attendee_email' => ['nullable','email','max:160'],
            'attendee_phone' => ['nullable','string','max:30'],
            'send_email' => ['nullable','boolean'],
            'send_whatsapp' => ['nullable','boolean'],
        ]);

        $data['send_email'] = (bool)($data['send_email'] ?? false);
        $data['send_whatsapp'] = (bool)($data['send_whatsapp'] ?? false);

        $agenda->fill($data);
        $agenda->computeNextReminder();
        $agenda->save();

        return response()->json(['ok'=>true]);
    }

    /** Eliminar (AJAX) */
    public function destroy(AgendaEvent $agenda)
    {
        $agenda->delete();
        return response()->json(['ok'=>true]);
    }

    /** Drag & drop / Resize (actualiza start_at rápidamente) */
    public function move(Request $r, AgendaEvent $agenda)
    {
        $data = $r->validate([
            'start_at' => ['required','date'],
        ]);

        // Solo cambia fecha/hora de inicio y recalcula recordatorio
        $agenda->start_at = $data['start_at'];
        $agenda->computeNextReminder();
        $agenda->save();

        return response()->json(['ok'=>true]);
    }
}
