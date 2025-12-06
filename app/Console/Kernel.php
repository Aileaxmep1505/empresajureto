<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Comandos personalizados registrados manualmente.
     *
     * @var array<class-string>
     */
    protected $commands = [
        \App\Console\Commands\SyncKnowledge::class,
        \App\Console\Commands\RunAgenda::class,
    ];

    /**
     * Define la programación de comandos recurrentes.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Usa la zona horaria de la app
        $schedule->timezone(config('app.timezone', 'America/Mexico_City'));

        // Escaneo de SLA de tickets cada 15 minutos
        $schedule->command('tickets:sla-scan')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/tickets-sla.log'));

        // Agenda: envía recordatorios cada minuto
        $schedule->command('agenda:run --limit=200')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/agenda.log'));

        // 👇 Ya NO programes aquí el queue:work, porque ya lo tienes como cron aparte
        // $schedule->command('queue:work --once --tries=3 --timeout=90')
        //          ->everyMinute()
        //          ->withoutOverlapping()
        //          ->appendOutputTo(storage_path('logs/queue-worker.log'));

        // Test: para verificar que el scheduler corre
        $schedule->call(function () {
                \Illuminate\Support\Facades\Log::info('⏰ Scheduler vivo: ' . now());
            })
            ->everyFiveMinutes()
            ->appendOutputTo(storage_path('logs/scheduler-ping.log'));
    }

    /**
     * Registra todos los comandos de consola.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
