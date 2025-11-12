<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\SyncKnowledge::class,
        \App\Console\Commands\RunAgenda::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Escaneo SLA tickets
        $schedule->command('tickets:sla-scan')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping()
                 ->runInBackground();

        // Agenda: envía recordatorios cada minuto, ajustando zona horaria
        $schedule->command('agenda:run --limit=200')
                 ->everyMinute()
                 ->timezone('America/Mexico_City') // ✅ zona horaria México
                 ->withoutOverlapping()
                 ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
