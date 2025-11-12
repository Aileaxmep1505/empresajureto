<?php

namespace App\Console\Commands;

use App\Jobs\SendAgendaReminderJob;
use App\Models\AgendaEvent;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunAgenda extends Command
{
    protected $signature = 'agenda:run {--limit=200 : Máximo de eventos a procesar en esta corrida} {--sync : Ejecutar los jobs de forma síncrona (útil si no tiene queue worker)}';
    protected $description = 'Despacha recordatorios de agenda pendientes (por next_reminder_at <= now).';

    public function handle(): int
    {
        $nowUtc = Carbon::now('UTC');
        $limit = (int) $this->option('limit');

        $this->info("Buscando eventos con next_reminder_at <= {$nowUtc->toDateTimeString()} (UTC)...");

        $due = AgendaEvent::query()
            ->whereNotNull('next_reminder_at')
            ->where('next_reminder_at', '<=', $nowUtc)
            ->orderBy('next_reminder_at', 'asc')
            ->limit($limit)
            ->get();

        $count = $due->count();
        $this->info("Eventos a notificar: {$count}");

        if ($count === 0) {
            return self::SUCCESS;
        }

        foreach ($due as $event) {
            try {
                // Pre-reserva para evitar re-despachos simultáneos
                $event->next_reminder_at = null;
                $event->save();

                if ($this->option('sync')) {
                    // Envío inmediato y síncrono (útil en dev o sin workers)
                    SendAgendaReminderJob::dispatchSync($event);
                    $this->line("Despachado SYNC event_id={$event->id} -> {$event->title}");
                } else {
                    // Despacha asíncrono (necesita queue worker)
                    SendAgendaReminderJob::dispatch($event);
                    $this->line("Despachado ASYNC event_id={$event->id} -> {$event->title}");
                }
            } catch (\Throwable $ex) {
                Log::error('Error despachando reminder: '.$ex->getMessage(), ['event_id'=>$event->id, 'exception'=>$ex]);
                $this->error("Error al despachar event_id={$event->id}: {$ex->getMessage()}");
            }
        }

        $this->info("Terminó agenda:run");

        return self::SUCCESS;
    }
}
