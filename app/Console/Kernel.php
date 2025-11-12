<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Lista de comandos registrados manualmente.
     *
     * Aquí puedes registrar clases de comandos personalizados
     * que no quieras que Laravel descubra automáticamente.
     *
     * @var array<class-string>
     */
    protected $commands = [
        \App\Console\Commands\SyncKnowledge::class,
        \App\Console\Commands\RunAgenda::class, // 👈 tu comando de agenda
    ];

    /**
     * Define el schedule (programación) de comandos recurrentes.
     *
     * Este método se ejecuta cuando corre `php artisan schedule:run`
     */
    protected function schedule(Schedule $schedule): void
    {
        // Escaneo de SLA de tickets cada 15 minutos
        $schedule->command('tickets:sla-scan')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping()
                 ->runInBackground();

        // 👇 Agenda: envía recordatorios cada minuto
        $schedule->command('agenda:run --limit=200')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->runInBackground();

        // Ejemplo opcional: sincronización de conocimiento diaria a las 3:00am
        // $schedule->command('knowledge:sync --rebuild')->dailyAt('03:00');
    }

    /**
     * Registra todos los comandos y rutas de consola.
     */
    protected function commands(): void
    {
        // Carga automática de comandos en app/Console/Commands
        $this->load(__DIR__ . '/Commands');

        // Permite definir closures de comandos en routes/console.php
        require base_path('routes/console.php');
    }
}
