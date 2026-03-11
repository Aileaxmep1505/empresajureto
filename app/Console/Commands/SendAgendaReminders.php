<?php

namespace App\Console\Commands;

use App\Models\AgendaEvent;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAgendaReminders extends Command
{
    protected $signature = 'agenda:send-reminders
                            {--force : Envía aunque next_reminder_at no haya llegado}
                            {--event_id= : Procesa solo un evento}
                            {--user_id= : Envía solo a un usuario específico}
                            {--dry-run : Solo muestra qué enviaría, sin mandar nada}';

    protected $description = 'Envía recordatorios pendientes de agenda por WhatsApp';

    public function handle(): int
    {
        $tz    = 'America/Mexico_City';
        $nowMx = now($tz);

        $force   = (bool) $this->option('force');
        $eventId = $this->option('event_id');
        $userId  = $this->option('user_id');
        $dryRun  = (bool) $this->option('dry-run');

        $query = AgendaEvent::query()
            ->where('send_whatsapp', true)
            ->whereNotNull('next_reminder_at');

        if (!$force) {
            $query->where('next_reminder_at', '<=', $nowMx->format('Y-m-d H:i:s'));
        }

        if (!empty($eventId)) {
            $query->where('id', (int) $eventId);
        }

        $events = $query
            ->orderBy('next_reminder_at')
            ->get();

        if ($events->isEmpty()) {
            $this->info('Sin recordatorios pendientes.');
            return self::SUCCESS;
        }

        $wa = app(WhatsAppService::class);
        $sent = 0;
        $processed = 0;

        foreach ($events as $event) {
            $processed++;

            $userIds = collect($event->user_ids ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            if (!empty($userId)) {
                $userIds = $userIds->filter(fn ($id) => $id === (int) $userId)->values();
            }

            $this->line("Evento #{$event->id} - {$event->title}");

            if ($userIds->isEmpty()) {
                $this->warn("  - Sin usuarios válidos.");
                if (!$force) {
                    $this->advanceNextReminder($event);
                    $event->save();
                }
                continue;
            }

            $users = User::whereIn('id', $userIds)->get();

            if ($users->isEmpty()) {
                $this->warn("  - No se encontraron usuarios.");
                if (!$force) {
                    $this->advanceNextReminder($event);
                    $event->save();
                }
                continue;
            }

            foreach ($users as $user) {
                if (empty($user->phone)) {
                    $this->warn("  - Usuario {$user->id} sin teléfono.");
                    Log::warning('agenda.whatsapp.reminder.user_without_phone', [
                        'agenda_event_id' => $event->id,
                        'user_id'         => $user->id,
                    ]);
                    continue;
                }

                if ($dryRun) {
                    $this->info("  - DRY RUN: enviaría a {$user->name} ({$user->phone})");
                    Log::info('agenda.whatsapp.reminder.dry_run', [
                        'agenda_event_id'   => $event->id,
                        'user_id'           => $user->id,
                        'user_phone'        => $user->phone,
                        'event_title'       => $event->title,
                        'next_reminder_at'  => $event->next_reminder_at,
                    ]);
                    continue;
                }

                try {
                    $result = $wa->sendAgendaReminderToUser($user, $event);

                    Log::info('agenda.whatsapp.reminder', [
                        'agenda_event_id' => $event->id,
                        'user_id'         => $user->id,
                        'result'          => $result,
                    ]);

                    if (!empty($result['ok'])) {
                        $sent++;
                        $this->info("  - OK enviado a {$user->name} ({$user->phone})");
                    } else {
                        $this->error("  - ERROR al enviar a {$user->name} ({$user->phone})");
                    }
                } catch (\Throwable $e) {
                    Log::error('agenda.whatsapp.reminder.exception', [
                        'agenda_event_id' => $event->id,
                        'user_id'         => $user->id,
                        'message'         => $e->getMessage(),
                    ]);

                    $this->error("  - EXCEPCIÓN con {$user->name}: {$e->getMessage()}");
                }
            }

            // En modo force NO avanzamos la fecha automáticamente,
            // para que puedas probar varias veces sin que desaparezca.
            if (!$force && !$dryRun) {
                $this->advanceNextReminder($event);
                $event->save();
            }
        }

        $this->newLine();
        $this->info("Eventos procesados: {$processed}");
        $this->info("Envíos exitosos: {$sent}");

        return self::SUCCESS;
    }

    protected function advanceNextReminder(AgendaEvent $event): void
    {
        if (empty($event->next_reminder_at)) {
            return;
        }

        $tz = $event->timezone ?: 'America/Mexico_City';
        $next = Carbon::parse($event->next_reminder_at, $tz);
        $now  = now($tz);

        switch ((string) $event->repeat_rule) {
            case 'daily':
                do {
                    $next->addDay();
                } while ($next->lte($now));
                $event->next_reminder_at = $next->format('Y-m-d H:i:s');
                break;

            case 'weekly':
                do {
                    $next->addWeek();
                } while ($next->lte($now));
                $event->next_reminder_at = $next->format('Y-m-d H:i:s');
                break;

            case 'monthly':
                do {
                    $next->addMonthNoOverflow();
                } while ($next->lte($now));
                $event->next_reminder_at = $next->format('Y-m-d H:i:s');
                break;

            case 'none':
            default:
                $event->next_reminder_at = null;
                break;
        }
    }
}