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
        \App\Console\Commands\RunAgenda::class, // tu comando de agenda
    ];

    /**
     * Define la programación de comandos recurrentes.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Establece la zona horaria explícitamente
        $schedule->timezone('America/Mexico_City');

        // Escaneo de SLA de tickets cada 15 minutos
        $schedule->command('tickets:sla-scan')
                 ->everyFifteenMinutes()
                 ->runInBackground()
                 ->withoutOverlapping();

        // Agenda: envía recordatorios cada minuto
        $schedule->command('agenda:run --limit=200')
                 ->everyMinute()
                 ->runInBackground()
                 ->withoutOverlapping();

        // Cola: procesa trabajos pendientes cada minuto
        $schedule->command('queue:work --once --tries=3 --timeout=90')
                 ->everyMinute()
                 ->runInBackground()
                 ->withoutOverlapping();

        // Ejemplo opcional: sincronización de conocimiento diaria a las 3:00 am
        // $schedule->command('knowledge:sync --rebuild')->dailyAt('03:00');
    }

    /**
     * Registra todos los comandos de consola.
     */
    protected function commands(): void
    {
        // Carga automática de comandos en app/Console/Commands
        $this->load(__DIR__ . '/Commands');

        // Permite definir closures de comandos en routes/console.php
        require base_path('routes/console.php');
    }
}
