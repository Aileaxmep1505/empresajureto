<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AgendaEvent;
use App\Jobs\SendAgendaReminderJob;
use Illuminate\Support\Facades\Log;

class RunAgenda extends Command
{
    /**
     * Comando para ejecutar los recordatorios de agenda.
     *
     * --limit indica cuántos eventos máximo procesar por corrida.
     */
    protected $signature = 'agenda:run {--limit=200}';

    /**
     * Descripción que aparece en "php artisan list".
     */
    protected $description = 'Envía recordatorios de agenda (correo y WhatsApp) de forma inmediata, sin cola';

    public function handle(): int
    {
        // Usamos la zona horaria de la app (config/app.php → timezone)
        $now = now(config('app.timezone', 'America/Mexico_City'));
        $limit = (int) $this->option('limit');

        Log::info("agenda:run → buscando eventos con next_reminder_at <= {$now}");
        $this->info("Buscando eventos con next_reminder_at <= {$now}");

        // Buscar eventos que ya deberían haberse recordado
        $events = AgendaEvent::query()
            ->whereNotNull('next_reminder_at')
            ->where('next_reminder_at', '<=', $now)
            ->where(function ($q) {
                $q->where('send_email', true)
                  ->orWhere('send_whatsapp', true);
            })
            ->orderBy('next_reminder_at')
            ->take($limit)
            ->get();

        $count = $events->count();
        Log::info('agenda:run → eventos encontrados', ['count' => $count]);
        $this->info("Eventos a notificar: {$count}");

        foreach ($events as $event) {
            $this->info("Enviando recordatorio INMEDIATO para event_id={$event->id} → {$event->title}");

            try {
                // 🔹 Ejecuta el Job al instante, SIN pasar por la cola
                SendAgendaReminderJob::dispatchSync($event);

                Log::info('agenda:run → Job ejecutado en modo sync', [
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

        $this->info('Terminó agenda:run');
        Log::info('agenda:run → terminado');

        return self::SUCCESS;
    }
}
