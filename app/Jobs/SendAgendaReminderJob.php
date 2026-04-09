<?php

namespace App\Jobs;

use App\Mail\AgendaReminderMail;
use App\Models\AgendaEvent;
use App\Models\User;
use App\Notifications\AgendaReminderSystemNotification;
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

    public function __construct(public int $eventId) {}

    public int $tries   = 3;
    public int $timeout = 120;

    /**
     * Normaliza teléfono a México fijo (52 + 10 dígitos).
     * Acepta:
     *  - "2205381046" -> "522205381046"
     *  - "+52 220 538 1046" -> "522205381046"
     *  - "52 2205381046" -> "522205381046"
     *  - Cualquier cosa rara -> intenta tomar últimos 10 y prefijar 52
     */
    protected function normalizeMxPhone(?string $value): ?string
    {
        if (!$value) return null;

        $digits = preg_replace('/\D+/', '', $value);
        if (!$digits) return null;

        if (strlen($digits) === 10) {
            return '52' . $digits;
        }

        if (substr($digits, 0, 2) === '52' && strlen($digits) >= 12) {
            return '52' . substr($digits, -10);
        }

        $last10 = substr($digits, -10);
        if (strlen($last10) === 10) {
            return '52' . $last10;
        }

        return null;
    }

    public function handle(): void
    {
        $event = AgendaEvent::find($this->eventId);

        if (! $event) {
            Log::warning("SendAgendaReminderJob: evento no encontrado", ['event_id' => $this->eventId]);
            return;
        }

        $tz = $event->timezone ?: config('app.timezone', 'America/Mexico_City');
        $userIds = is_array($event->user_ids) ? $event->user_ids : [];

        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        $userIds = array_filter($userIds);

        Log::info("SendAgendaReminderJob: iniciando", [
            'event_id' => $event->id,
            'title'    => $event->title,
            'timezone' => $tz,
            'send_email'    => (bool) $event->send_email,
            'send_whatsapp' => (bool) $event->send_whatsapp,
            'user_ids' => $userIds,
            'next_reminder_at' => optional($event->next_reminder_at)->toDateTimeString(),
        ]);

        if (!count($userIds)) {
            Log::warning("SendAgendaReminderJob: evento sin user_ids, no se envía", ['event_id' => $event->id]);

            $event->last_reminder_sent_at = now('UTC');
            $event->advanceAfterSending();
            $event->save();
            return;
        }

        $users = User::query()
            ->whereIn('id', $userIds)
            ->get(['id', 'name', 'email', 'phone']);

        $fechaFormateada = $event->start_at
            ? $event->start_at->setTimezone($tz)->format('d/m/Y H:i')
            : '';

        try {
            // ========= CORREO (a muchos) =========
            if ($event->send_email) {
                foreach ($users as $u) {
                    if (!$u->email) {
                        Log::warning("Agenda: usuario sin email, omitido", [
                            'event_id' => $event->id,
                            'user_id'  => $u->id,
                        ]);
                        continue;
                    }

                    try {
                        Mail::to($u->email)->send(new AgendaReminderMail($event, $u));

                        Log::info("Agenda: correo enviado", [
                            'event_id' => $event->id,
                            'to'       => $u->email,
                            'user_id'  => $u->id,
                        ]);
                    } catch (Throwable $mailEx) {
                        Log::error("Agenda: error enviando correo", [
                            'event_id' => $event->id,
                            'user_id'  => $u->id,
                            'error'    => $mailEx->getMessage(),
                        ]);
                    }
                }
            }

            // ========= NOTIFICACIÓN INTERNA DEL SISTEMA =========
            foreach ($users as $u) {
                try {
                    $u->notify(new AgendaReminderSystemNotification($event));

                    Log::info("Agenda: notificación interna enviada", [
                        'event_id' => $event->id,
                        'user_id'  => $u->id,
                        'title'    => $event->title,
                    ]);
                } catch (Throwable $notifyEx) {
                    Log::error("Agenda: error enviando notificación interna", [
                        'event_id' => $event->id,
                        'user_id'  => $u->id,
                        'error'    => $notifyEx->getMessage(),
                    ]);
                }
            }

            // ========= WHATSAPP (a muchos) =========
            if ($event->send_whatsapp) {
                if (!class_exists(\App\Services\WhatsAppService::class)) {
                    Log::warning('WhatsAppService no disponible; omitiendo WA', ['event_id' => $event->id]);
                } else {
                    $wa = app(\App\Services\WhatsAppService::class);
                    $templateName = config('services.whatsapp.template_agenda', 'agenda_recordatorio');

                    foreach ($users as $u) {
                        $to = $this->normalizeMxPhone($u->phone);

                        if (!$to) {
                            Log::warning("Agenda: usuario sin phone válido MX, omitido WA", [
                                'event_id' => $event->id,
                                'user_id'  => $u->id,
                                'phone'    => $u->phone,
                            ]);
                            continue;
                        }

                        try {
                            $params = [
                                $u->name ?: 'Usuario',
                                $event->title,
                                $fechaFormateada,
                                $tz,
                            ];

                            if (method_exists($wa, 'sendTemplate')) {
                                $resp = $wa->sendTemplate($to, $templateName, $params, 'es');

                                Log::info('WhatsApp template response', [
                                    'event_id' => $event->id,
                                    'user_id'  => $u->id,
                                    'phone'    => $to,
                                    'raw_phone'=> $u->phone,
                                    'response' => $resp,
                                ]);
                            } else {
                                Log::warning('WhatsAppService::sendTemplate no existe', ['event_id' => $event->id]);
                            }
                        } catch (Throwable $waEx) {
                            Log::error('Error enviando WhatsApp (template)', [
                                'event_id' => $event->id,
                                'user_id'  => $u->id,
                                'phone'    => $to,
                                'error'    => $waEx->getMessage(),
                            ]);
                        }
                    }
                }
            }

            // ========= MARCAR COMO ENVIADO Y AVANZAR =========
            $event->last_reminder_sent_at = now('UTC');
            $event->advanceAfterSending();
            $event->save();

            Log::info("SendAgendaReminderJob: terminado correctamente", [
                'event_id' => $event->id,
                'next_reminder_at' => optional($event->next_reminder_at)->toDateTimeString(),
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

    public function failed(Throwable $e): void
    {
        Log::error('SendAgendaReminderJob failed', [
            'event_id' => $this->eventId,
            'error'    => $e->getMessage(),
        ]);
        report($e);
    }
}