<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AgendaEvent;
use App\Jobs\SendAgendaReminderJob;
use Illuminate\Support\Facades\Log;

class RunAgenda extends Command
{
    protected $signature = 'agenda:run {--limit=200} {--window=5}';
    protected $description = 'Envía recordatorios de agenda';

    public function handle()
    {
        $limit  = (int) $this->option('limit');
        $window = max(1, (int) $this->option('window'));

        $tz  = config('app.timezone', 'America/Mexico_City');
        $now = now($tz);
        $from = $now->copy()->subMinutes($window);

        // ✅ IMPORTANT: comparar como strings "Y-m-d H:i:s" (porque guardas naive local)
        $nowDb  = $now->format('Y-m-d H:i:s');
        $fromDb = $from->format('Y-m-d H:i:s');

        Log::info("agenda:run → ventana {$window} min. Buscando eventos con next_reminder_at entre {$fromDb} y {$nowDb}");
        $this->info("Buscando eventos con next_reminder_at entre {$fromDb} y {$nowDb}");

        $events = AgendaEvent::query()
            ->whereNotNull('next_reminder_at')
            ->whereBetween('next_reminder_at', [$fromDb, $nowDb])
            ->where(function ($q) {
                $q->where('send_email', true)
                  ->orWhere('send_whatsapp', true);
            })
            ->orderBy('next_reminder_at')
            ->limit($limit)
            ->get();

        Log::info("agenda:run → eventos encontrados", ['count' => $events->count()]);
        $this->info("Eventos a notificar: {$events->count()}");

        foreach ($events as $event) {
            $this->info("Enviando recordatorio para event_id={$event->id} → {$event->title}");

            try {
                // Modo sync (sin cola)
                (new SendAgendaReminderJob($event->id))->handle();

                Log::info("agenda:run → Job ejecutado en modo sync", [
                    'event_id' => $event->id,
                    'title'    => $event->title,
                ]);
            } catch (\Throwable $e) {
                Log::error('agenda:run → error ejecutando SendAgendaReminderJob en modo sync', [
                    'event_id' => $event->id,
                    'title'    => $event->title,
                    'error'    => $e->getMessage(),
                ]);

                $this->error("Error enviando recordatorio para ID {$event->id}: {$e->getMessage()}");
            }
        }

        $this->info("Terminó agenda:run");
        Log::info("agenda:run → terminado");
    }
}
