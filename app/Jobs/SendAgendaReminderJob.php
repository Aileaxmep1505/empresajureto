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
    public int $timeout = 120;

    public function __construct(public AgendaEvent $event) {}

    public function handle(): void
    {
        $event = $this->event->fresh();
        if (!$event) {
            Log::warning("SendAgendaReminderJob: evento no encontrado (posiblemente eliminado).");
            return;
        }

        Log::info("SendAgendaReminderJob: iniciando", [
            'event_id' => $event->id,
            'start_at' => optional($event->start_at)->toDateTimeString(),
            'timezone' => $event->timezone,
        ]);

        try {
            // ===== Correo =====
            if ($event->send_email && $event->attendee_email) {
                try {
                    Mail::to($event->attendee_email)->send(new AgendaReminderMail($event));
                    Log::info("Agenda: correo enviado", ['event_id' => $event->id, 'to' => $event->attendee_email]);
                } catch (Throwable $mailEx) {
                    Log::error("Agenda: error enviando correo", ['event_id' => $event->id, 'error' => $mailEx->getMessage()]);
                    // no rethrow; permitimos que WA intente igualmente
                }
            } else {
                Log::info("Agenda: correo omitido (send_email o attendee_email faltante)", ['event_id' => $event->id]);
            }

            // ===== WhatsApp (FORZAR plantilla) =====
            if ($event->send_whatsapp && $event->attendee_phone) {
                try {
                    if (! class_exists(\App\Services\WhatsAppService::class)) {
                        Log::warning('WhatsAppService no disponible; omitiendo WA', ['event_id' => $event->id]);
                    } else {
                        $wa = app(\App\Services\WhatsAppService::class);

                        $templateName = config('services.whatsapp.template_agenda', 'agenda_recordatorio');

                        $params = [
                            $event->attendee_name ?: 'Cliente',
                            $event->title,
                            // formateamos fecha/hora en la zona del evento
                            optional($event->start_at)->setTimezone($event->timezone ?? 'America/Mexico_City')->format('d/m/Y H:i'),
                            $event->timezone ?? 'America/Mexico_City',
                        ];

                        if (method_exists($wa, 'sendTemplate')) {
                            $resp = $wa->sendTemplate($event->attendee_phone, $templateName, $params, 'es');
                            Log::info('WhatsApp template response', ['event_id' => $event->id, 'response' => $resp]);
                        } else {
                            // no hacemos sendText automáticamente para evitar ventanas 24h, etc.
                            Log::error('WhatsAppService no implementa sendTemplate(); no se enviará WA para evitar fallback', ['event_id' => $event->id, 'class' => get_class($wa)]);
                        }
                    }
                } catch (Throwable $waEx) {
                    Log::error('Error enviando WhatsApp (template)', ['event_id' => $event->id, 'error' => $waEx->getMessage()]);
                }
            } else {
                Log::info("Agenda: WhatsApp omitido (send_whatsapp o attendee_phone faltante)", ['event_id' => $event->id]);
            }

            // ===== Marcar envío y programar siguiente =====
            $event->last_reminder_sent_at = now('UTC');
            $event->advanceAfterSending(); // prepara siguiente ciclo según repeat_rule
            $event->save();

            Log::info("SendAgendaReminderJob: terminado correctamente", ['event_id' => $event->id]);
        } catch (Throwable $e) {
            Log::error('SendAgendaReminderJob excepción', ['event_id' => $event->id, 'error' => $e->getMessage()]);
            report($e);
            throw $e; // para que la cola pueda reintentar según $tries
        }
    }

    public function failed(Throwable $e): void
    {
        report($e);
        Log::error('SendAgendaReminderJob failed', ['error' => $e->getMessage(), 'event_id' => $this->event->id ?? null]);
    }
}
