<?php

namespace App\Console\Commands;

use App\Jobs\SendAgendaReminderJob;
use App\Models\AgendaEvent;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RunAgenda extends Command
{
    protected $signature = 'agenda:run';
    protected $description = 'Despacha recordatorios de agenda pendientes';

    public function handle(): int
    {
        $nowUtc = Carbon::now('UTC');

        $due = AgendaEvent::query()
            ->whereNotNull('next_reminder_at')
            ->where('next_reminder_at', '<=', $nowUtc)
            ->limit(200)
            ->get();

        $this->info("Eventos a notificar: {$due->count()}");

        foreach ($due as $event) {
            // Despachar job (cola)
            SendAgendaReminderJob::dispatch($event);
            // Evitar repetidos en la misma corrida si tarda la cola
            $event->next_reminder_at = null;
            $event->save();
        }

        return self::SUCCESS;
    }
}
