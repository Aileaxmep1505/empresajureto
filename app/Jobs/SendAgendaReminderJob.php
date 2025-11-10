<?php

namespace App\Jobs;

use App\Mail\AgendaReminderMail;
use App\Models\AgendaEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAgendaReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(public AgendaEvent $event) {}

    public function handle(): void
    {
        $event = $this->event->fresh();
        if (!$event) return;

        try {
            // ===== Correo =====
            if ($event->send_email && $event->attendee_email) {
                Mail::to($event->attendee_email)->send(new AgendaReminderMail($event));
            }

            // ===== WhatsApp (opcional) =====
            if ($event->send_whatsapp && $event->attendee_phone) {
                // Resolver dinámicamente y solo si existe
                $wa = null;
                try {
                    if (class_exists(\App\Services\WhatsAppService::class)) {
                        $wa = app(\App\Services\WhatsAppService::class);
                    }
                } catch (\Throwable $e) {
                    // si no existe/bound, lo ignoramos
                    $wa = null;
                }

                if ($wa) {
                    $texto = "📌 *Recordatorio*\n".
                             "Evento: {$event->title}\n".
                             ($event->description ? "Detalle: {$event->description}\n" : "").
                             "Fecha/Hora: ".$event->start_at->setTimezone($event->timezone)->format('d/m/Y H:i')." ({$event->timezone})";
                    // Ajusta al método real que uses
                    $wa->sendMessage($event->attendee_phone, $texto);
                }
            }

            // Marcar enviado y preparar siguiente ciclo
            $event->last_reminder_sent_at = now();
            $event->advanceAfterSending();
            $event->save();

        } catch (Throwable $e) {
            // Deja rastro en logs si algo falla
            report($e);
            throw $e; // permite reintentos
        }
    }

    public function failed(Throwable $e): void
    {
        report($e);
    }
}
