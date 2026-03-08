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
    protected $signature = 'agenda:send-reminders';
    protected $description = 'Envía recordatorios pendientes de agenda por WhatsApp';

    public function handle(): int
    {
        $nowMx = now('America/Mexico_City')->format('Y-m-d H:i:s');

        $events = AgendaEvent::query()
            ->where('send_whatsapp', true)
            ->whereNotNull('next_reminder_at')
            ->where('next_reminder_at', '<=', $nowMx)
            ->orderBy('next_reminder_at')
            ->get();

        if ($events->isEmpty()) {
            $this->info('Sin recordatorios pendientes.');
            return self::SUCCESS;
        }

        $wa = app(WhatsAppService::class);
        $sent = 0;

        foreach ($events as $event) {
            $userIds = collect($event->user_ids ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            if ($userIds->isEmpty()) {
                $this->advanceNextReminder($event);
                $event->save();
                continue;
            }

            $users = User::whereIn('id', $userIds)->get();

            foreach ($users as $user) {
                $result = $wa->sendAgendaReminderToUser($user, $event);

                Log::info('agenda.whatsapp.reminder', [
                    'agenda_event_id' => $event->id,
                    'user_id'         => $user->id,
                    'result'          => $result,
                ]);

                if (!empty($result['ok'])) {
                    $sent++;
                }
            }

            $this->advanceNextReminder($event);
            $event->save();
        }

        $this->info("Recordatorios procesados. Envíos exitosos: {$sent}");

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