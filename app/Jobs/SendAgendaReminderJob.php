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

    /** Número de reintentos y timeout */
    public int $tries = 3;
    public int $timeout = 120;

    /** ID del evento (nullable para evitar error con jobs viejos) */
    public ?int $eventId = null;

    /**
     * Recibimos SOLO el ID del evento, no el modelo completo.
     */
    public function __construct(int $eventId)
    {
        $this->eventId = $eventId;
    }

    public function handle(): void
    {
        if (!$this->eventId) {
            Log::warning('SendAgendaReminderJob: eventId nulo, abortando.');
            return;
        }

        // Volvemos a cargar el evento desde la BD
        $event = AgendaEvent::find($this->eventId);

        if (!$event) {
            Log::warning("SendAgendaReminderJob: evento no encontrado (posiblemente eliminado).", [
                'event_id' => $this->eventId,
            ]);
            return;
        }

        Log::info("SendAgendaReminderJob: iniciando", [
            'event_id'        => $event->id,
            'title'           => $event->title,
            'start_at'        => optional($event->start_at)->toDateTimeString(),
            'timezone'        => $event->timezone,
            'send_email'      => $event->send_email,
            'send_whatsapp'   => $event->send_whatsapp,
            'attendee_email'  => $event->attendee_email,
            'attendee_phone'  => $event->attendee_phone,
            'next_reminder_at'=> (string) $event->next_reminder_at,
        ]);

        try {
            // ==============
            //  CORREO
            // ==============
            if ($event->send_email && $event->attendee_email) {
                try {
                    Mail::to($event->attendee_email)->send(
                        new AgendaReminderMail($event)
                    );

                    Log::info("Agenda: correo enviado", [
                        'event_id' => $event->id,
                        'to'       => $event->attendee_email,
                    ]);
                } catch (Throwable $mailEx) {
                    // NO tumba el job, solo loguea
                    Log::error("Agenda: error enviando correo", [
                        'event_id' => $event->id,
                        'error'    => $mailEx->getMessage(),
                    ]);
                }
            }

            // ==============
            //  WHATSAPP
            // ==============
            if ($event->send_whatsapp && $event->attendee_phone) {
                try {
                    if (! class_exists(\App\Services\WhatsAppService::class)) {
                        Log::warning('WhatsAppService no disponible; omitiendo WA', [
                            'event_id' => $event->id,
                        ]);
                    } else {
                        $wa = app(\App\Services\WhatsAppService::class);
                        $templateName = config('services.whatsapp.template_agenda', 'agenda_recordatorio');

                        $params = [
                            $event->attendee_name ?: 'Cliente',
                            $event->title,
                            optional($event->start_at)
                                ? $event->start_at
                                    ->copy()
                                    ->setTimezone($event->timezone ?? 'America/Mexico_City')
                                    ->format('d/m/Y H:i')
                                : '',
                            $event->timezone ?? 'America/Mexico_City',
                        ];

                        if (method_exists($wa, 'sendTemplate')) {
                            $resp = $wa->sendTemplate(
                                $event->attendee_phone,
                                $templateName,
                                $params,
                                'es'
                            );

                            Log::info('WhatsApp template response', [
                                'event_id' => $event->id,
                                'response' => $resp,
                            ]);
                        } else {
                            Log::warning('WhatsAppService::sendTemplate no existe', [
                                'event_id' => $event->id,
                            ]);
                        }
                    }
                } catch (Throwable $waEx) {
                    Log::error('Error enviando WhatsApp (template)', [
                        'event_id' => $event->id,
                        'error'    => $waEx->getMessage(),
                    ]);
                }
            }

            // ==============
            //  ACTUALIZAR SIGUIENTE RECORDATORIO
            // ==============
            $event->last_reminder_sent_at = now('UTC');

            if (method_exists($event, 'advanceAfterSending')) {
                $event->advanceAfterSending();
            } elseif (method_exists($event, 'computeNextReminder')) {
                $event->computeNextReminder();
            }

            $event->save();

            Log::info("SendAgendaReminderJob: terminado correctamente", [
                'event_id'        => $event->id,
                'next_reminder_at'=> (string) $event->next_reminder_at,
            ]);
        } catch (Throwable $e) {
            Log::error('SendAgendaReminderJob excepción', [
                'event_id' => $this->eventId,
                'error'    => $e->getMessage(),
            ]);
            report($e);
            throw $e; // para que se marque como failed si algo muy grave truena
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('SendAgendaReminderJob failed', [
            'event_id' => $this->eventId,
            'error'    => $e->getMessage(),
        ]);
        report($e);
    }
}
