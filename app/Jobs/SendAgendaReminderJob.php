<?php

namespace App\Jobs;

use App\Mail\AgendaReminderMail;
use App\Models\AgendaEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAgendaReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de intentos máximos si se ejecuta en cola.
     */
    public int $tries = 3;

    /**
     * Timeout por intento (segundos).
     */
    public int $timeout = 120;

    /**
     * ID del evento de agenda a procesar.
     */
    public int $eventId;

    /**
     * Recibe solo el ID del evento.
     */
    public function __construct(int $eventId)
    {
        $this->eventId = $eventId;
    }

    /**
     * Lógica principal del Job: enviar correo y WhatsApp.
     */
    public function handle(): void
    {
        // Buscar el evento más reciente por si se modificó
        $event = AgendaEvent::find($this->eventId);

        if (! $event) {
            Log::warning('SendAgendaReminderJob: evento no encontrado', [
                'event_id' => $this->eventId,
            ]);
            return;
        }

        Log::info('SendAgendaReminderJob: iniciando', [
            'event_id'        => $event->id,
            'title'           => $event->title,
            'start_at'        => optional($event->start_at)->toDateTimeString(),
            'timezone'        => $event->timezone,
            'send_email'      => $event->send_email,
            'send_whatsapp'   => $event->send_whatsapp,
            'attendee_email'  => $event->attendee_email,
            'attendee_phone'  => $event->attendee_phone,
            'next_reminder_at'=> optional($event->next_reminder_at)->toDateTimeString(),
        ]);

        try {
            // ===== Correo =====
            if ($event->send_email && $event->attendee_email) {
                try {
                    Mail::to($event->attendee_email)->send(new AgendaReminderMail($event));

                    Log::info('Agenda: correo enviado', [
                        'event_id' => $event->id,
                        'to'       => $event->attendee_email,
                    ]);
                } catch (Throwable $mailEx) {
                    Log::error('Agenda: error enviando correo', [
                        'event_id' => $event->id,
                        'error'    => $mailEx->getMessage(),
                    ]);
                }
            }

            // ===== WhatsApp =====
            if ($event->send_whatsapp && $event->attendee_phone) {
                try {
                    if (! class_exists(\App\Services\WhatsAppService::class)) {
                        Log::warning('WhatsAppService no disponible; omitiendo WhatsApp', [
                            'event_id' => $event->id,
                        ]);
                    } else {
                        $wa = app(\App\Services\WhatsAppService::class);
                        $templateName = config('services.whatsapp.template_agenda', 'agenda_recordatorio');

                        // 👉 Parametros para tu plantilla:
                        // Hola {{1}}, te recordamos tu evento: *{{2}}*.
                        // Fecha/Hora: {{3}} ({{4}})
                        $labelEvento = "ID {$event->id} - {$event->title}";

                        $params = [
                            $event->attendee_name ?: 'Cliente',                                // {{1}}
                            $labelEvento,                                                      // {{2}}
                            optional($event->start_at)
                                ->setTimezone($event->timezone ?? config('app.timezone'))
                                ->format('d/m/Y H:i'),                                        // {{3}}
                            $event->timezone ?? config('app.timezone', 'America/Mexico_City'), // {{4}}
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

            // ===== Marcar envío y calcular siguiente recordatorio =====
            // puedes guardar en timezone app o en UTC, como prefieras
            $event->last_reminder_sent_at = now(config('app.timezone'))->format('Y-m-d H:i:s');
            $event->advanceAfterSending();
            $event->save();

            Log::info('SendAgendaReminderJob: terminado correctamente', [
                'event_id'        => $event->id,
                'next_reminder_at'=> optional($event->next_reminder_at)->toDateTimeString(),
            ]);
        } catch (Throwable $e) {
            Log::error('SendAgendaReminderJob excepción', [
                'event_id' => $event->id ?? $this->eventId,
                'error'    => $e->getMessage(),
            ]);

            report($e);
            throw $e;
        }
    }

    /**
     * Se ejecuta si el job falla definitivamente en cola.
     */
    public function failed(Throwable $e): void
    {
        Log::error('SendAgendaReminderJob failed', [
            'event_id' => $this->eventId,
            'error'    => $e->getMessage(),
        ]);

        report($e);
    }
}
