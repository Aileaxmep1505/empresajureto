<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AgendaEvent;
use App\Jobs\SendAgendaReminderJob;
use Illuminate\Support\Facades\Log;

class RunAgenda extends Command
{
    protected $signature = 'agenda:run {--limit=200}';
    protected $description = 'Envía recordatorios de agenda';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        $now   = now('America/Mexico_City'); // o config('app.timezone')

        Log::info("agenda:run → buscando eventos con next_reminder_at <= {$now}");
        $this->info("Buscando eventos con next_reminder_at <= {$now}");

        $events = AgendaEvent::query()
            ->whereNotNull('next_reminder_at')
            ->where('next_reminder_at', '<=', $now)
            ->where(function ($q) {
                $q->where('send_email', true)
                  ->orWhere('send_whatsapp', true);
            })
            ->take($limit)
            ->get();

        Log::info("agenda:run → eventos encontrados", ['count' => $events->count()]);
        $this->info("Eventos a notificar: {$events->count()}");

        foreach ($events as $event) {
            SendAgendaReminderJob::dispatch($event);
            Log::info("agenda:run → Job despachado", [
                'event_id' => $event->id,
                'title'    => $event->title,
            ]);
            $this->info("Job despachado para event_id={$event->id} -> {$event->title}");
        }

        $this->info("Terminó agenda:run");
        Log::info("agenda:run → terminado");
    }
}
