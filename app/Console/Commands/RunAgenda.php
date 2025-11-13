<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AgendaEvent;
use App\Jobs\SendAgendaReminderJob;

class RunAgenda extends Command
{
    protected $signature = 'agenda:run {--limit=200}';
    protected $description = 'Envía recordatorios de agenda';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        $now = now('America/Mexico_City'); // coincide con APP_TIMEZONE

        $this->info("Buscando eventos con next_reminder_at <= {$now}");

        $events = AgendaEvent::where('next_reminder_at', '<=', $now)
                             ->take($limit)
                             ->get();

        $this->info("Eventos a notificar: {$events->count()}");

        foreach ($events as $event) {
            SendAgendaReminderJob::dispatch($event);
            $this->info("Job despachado para event_id={$event->id} -> {$event->title}");
        }

        $this->info("Terminó agenda:run");
    }
}
