<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Artisan commands registrados manualmente.
     *
     * @var array<class-string>
     */
    protected $commands = [
        \App\Console\Commands\SyncKnowledge::class,
        // Agrega aquí otros comandos manuales...
    ];

    /**
     * Define la programación (scheduler) de comandos.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Ejemplo: comando inspiracional cada hora
        // $schedule->command('inspire')->hourly();

        // (Opcional) Reindexar conocimiento todos los días a las 03:00
        // $schedule->command('knowledge:sync --rebuild')->dailyAt('03:00');
    }

    /**
     * Registra los comandos de la aplicación.
     */
    protected function commands(): void
    {
        // Carga automática de todos los comandos en app/Console/Commands
        $this->load(__DIR__ . '/Commands');

        // Rutas para comandos definidos en routes/console.php
        require base_path('routes/console.php');
    }
}
