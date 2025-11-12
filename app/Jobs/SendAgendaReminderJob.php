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
use Illuminate\Support\Facades\Log;
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
        if (!$event) {
            Log::warning("SendAgendaReminderJob: evento no encontrado o eliminado.");
            return;
        }

        Log::info("SendAgendaReminderJob: iniciando for event_id={$event->id}, start_at={$event->start_at}, tz={$event->timezone}");

        try {
            // ===== Correo =====
            if ($event->send_email && $event->attendee_email) {
                Mail::to($event->attendee_email)->send(new AgendaReminderMail($event));
                Log::info("Agenda: correo enviado a {$event->attendee_email} (event {$event->id})");
            } else {
                Log::info("Agenda: correo SKIP (send_email={$event->send_email}, attendee_email={$event->attendee_email})");
            }

            // ===== WhatsApp (si está activo y hay teléfono) =====
            if ($event->send_whatsapp && $event->attendee_phone) {
                try {
                    if (class_exists(\App\Services\WhatsAppService::class)) {
                        $wa = app(\App\Services\WhatsAppService::class);

                        // FORZAR uso de plantilla "agenda_recordatorio"
                        $templateName = config('services.whatsapp.template_agenda', 'agenda_recordatorio');

                        $params = [
                            $event->attendee_name ?: 'Cliente',
                            $event->title,
                            $event->start_at->setTimezone($event->timezone ?? 'America/Mexico_City')->format('d/m/Y H:i'),
                            $event->timezone ?? 'America/Mexico_City',
                        ];

                        if (method_exists($wa, 'sendTemplate')) {
                            $resp = $wa->sendTemplate($event->attendee_phone, $templateName, $params, 'es');
                            Log::info('WhatsApp template response', ['event'=>$event->id, 'response'=>$resp]);
                        } elseif (method_exists($wa, 'sendMessage')) {
                            // Fallback a texto libre si no hay template
                            $texto = "📌 Recordatorio: {$event->title}\nFecha: ".$event->start_at->setTimezone($event->timezone ?? 'America/Mexico_City')->format('d/m/Y H:i');
                            $resp = $wa->sendMessage($event->attendee_phone, $texto);
                            Log::info('WhatsApp sendMessage response', ['event'=>$event->id, 'response'=>$resp]);
                        } else {
                            Log::warning('WhatsAppService no tiene métodos esperados', ['class'=>get_class($wa)]);
                        }
                    } else {
                        Log::warning('WhatsAppService no registrado; omitiendo WA para event '.$event->id);
                    }
                } catch (Throwable $waEx) {
                    Log::error('Error enviando WhatsApp: '.$waEx->getMessage(), ['event'=>$event->id, 'exception'=>$waEx]);
                }
            } else {
                Log::info("Agenda: WhatsApp SKIP (send_whatsapp={$event->send_whatsapp}, attendee_phone={$event->attendee_phone})");
            }

            // ===== Marcar envío y preparar siguiente ciclo =====
            $event->last_reminder_sent_at = now('UTC');
            $event->advanceAfterSending();
            $event->save();

            Log::info("SendAgendaReminderJob: terminado correctamente para event {$event->id}");

        } catch (Throwable $e) {
            Log::error('SendAgendaReminderJob: excepción: '.$e->getMessage(), ['event'=>$event->id, 'exception'=>$e]);
            report($e);
            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        report($e);
        Log::error('SendAgendaReminderJob failed', ['exception'=>$e]);
    }
}
