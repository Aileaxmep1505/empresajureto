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
                }
            }

            // ===== WhatsApp =====
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
                            optional($event->start_at)->setTimezone($event->timezone ?? 'America/Mexico_City')->format('d/m/Y H:i'),
                            $event->timezone ?? 'America/Mexico_City',
                        ];

                        if (method_exists($wa, 'sendTemplate')) {
                            $resp = $wa->sendTemplate($event->attendee_phone, $templateName, $params, 'es');
                            Log::info('WhatsApp template response', ['event_id' => $event->id, 'response' => $resp]);
                        }
                    }
                } catch (Throwable $waEx) {
                    Log::error('Error enviando WhatsApp (template)', ['event_id' => $event->id, 'error' => $waEx->getMessage()]);
                }
            }

            // ===== Marcar envío y calcular siguiente =====
            $event->last_reminder_sent_at = now('UTC');
            $event->advanceAfterSending();
            $event->save();

            Log::info("SendAgendaReminderJob: terminado correctamente", ['event_id' => $event->id]);
        } catch (Throwable $e) {
            Log::error('SendAgendaReminderJob excepción', ['event_id' => $event->id, 'error' => $e->getMessage()]);
            report($e);
            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('SendAgendaReminderJob failed', ['error' => $e->getMessage(), 'event_id' => $this->event->id ?? null]);
        report($e);
    }
}
