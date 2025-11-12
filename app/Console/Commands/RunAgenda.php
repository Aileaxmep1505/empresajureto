<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AgendaEvent;
use Carbon\Carbon;

class RunAgenda extends Command
{
    protected $signature = 'agenda:run {--limit=200}';
    protected $description = 'Envía recordatorios de agenda';

    public function handle()
    {
        $limit = (int) $this->option('limit');

        // Hora actual en la zona de México
        $now = now('America/Mexico_City');

        $this->info("Buscando eventos con next_reminder_at <= {$now}");

        // Usar el modelo correcto: AgendaEvent
        $events = AgendaEvent::where('next_reminder_at', '<=', $now)
                             ->take($limit)
                             ->get();

        $this->info("Eventos a notificar: {$events->count()}");

        foreach ($events as $event) {
            // Aquí despacha tus notificaciones (correo, WhatsApp, etc.)
            $this->info("Despachado SYNC event_id={$event->id} -> {$event->title}");
        }

        $this->info("Terminó agenda:run");
    }
}
