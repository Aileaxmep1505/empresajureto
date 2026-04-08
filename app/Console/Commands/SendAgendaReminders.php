<?php

namespace App\Jobs;

use App\Mail\AgendaReminderMail;
use App\Models\AgendaEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAgendaReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $eventId)
    {
    }

    public function handle(): void
    {
        $event = AgendaEvent::find($this->eventId);

        if (!$event) {
            Log::warning('agenda.reminder.job.event_not_found', [
                'event_id' => $this->eventId,
            ]);
            return;
        }

        $tz = $event->timezone ?: 'America/Mexico_City';

        $userIds = collect($event->user_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            Log::warning('agenda.reminder.job.no_user_ids', [
                'event_id' => $event->id,
                'title'    => $event->title,
            ]);

            $this->advanceNextReminderAndSave($event, $tz);
            return;
        }

        $users = User::query()
            ->whereIn('id', $userIds)
            ->get();

        if ($users->isEmpty()) {
            Log::warning('agenda.reminder.job.users_not_found', [
                'event_id' => $event->id,
                'title'    => $event->title,
                'user_ids' => $userIds->all(),
            ]);

            $this->advanceNextReminderAndSave($event, $tz);
            return;
        }

        $sentAnyEmail = false;

        foreach ($users as $user) {
            if ($event->send_email) {
                if (empty($user->email)) {
                    Log::warning('agenda.reminder.job.user_without_email', [
                        'event_id' => $event->id,
                        'user_id'  => $user->id,
                        'name'     => $user->name,
                    ]);
                } else {
                    try {
                        Mail::to($user->email)->send(new AgendaReminderMail($event, $user));

                        Log::info('agenda.reminder.job.email_sent', [
                            'event_id' => $event->id,
                            'user_id'  => $user->id,
                            'email'    => $user->email,
                            'title'    => $event->title,
                        ]);

                        $sentAnyEmail = true;
                    } catch (\Throwable $e) {
                        Log::error('agenda.reminder.job.email_error', [
                            'event_id' => $event->id,
                            'user_id'  => $user->id,
                            'email'    => $user->email,
                            'error'    => $e->getMessage(),
                        ]);
                    }
                }
            }

            // WhatsApp lo dejamos para el siguiente paso
        }

        $event->last_reminder_sent_at = now($tz)->format('Y-m-d H:i:s');

        // Solo avanzamos el siguiente recordatorio si al menos se mandó algo
        // o si no quieres que se repita indefinidamente aunque falle un usuario.
        if ($event->send_email) {
            $this->advanceNextReminder($event, $tz);
        }

        $event->save();

        Log::info('agenda.reminder.job.finished', [
            'event_id'        => $event->id,
            'title'           => $event->title,
            'sent_any_email'  => $sentAnyEmail,
            'next_reminder_at'=> $event->next_reminder_at,
        ]);
    }

    protected function advanceNextReminderAndSave(AgendaEvent $event, string $tz): void
    {
        $this->advanceNextReminder($event, $tz);
        $event->save();
    }

    protected function advanceNextReminder(AgendaEvent $event, string $tz): void
    {
        if (empty($event->next_reminder_at)) {
            return;
        }

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