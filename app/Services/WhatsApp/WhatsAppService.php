<?php

namespace App\Services\WhatsApp;

use App\Models\Ticket;
use App\Models\User;
use App\Models\AgendaEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppService
{
    public function enabled(): bool
    {
        return (bool) config('whatsapp.enabled')
            && filled(config('whatsapp.token'))
            && filled(config('whatsapp.phone_number_id'))
            && filled(config('whatsapp.version'));
    }

    public function sendTemplate(
        string $to,
        string $templateName,
        array $bodyParams = [],
        ?string $lang = null,
        array $headerParams = []
    ): array {
        if (!$this->enabled()) {
            return ['ok' => false, 'reason' => 'whatsapp_disabled'];
        }

        $to = $this->normalizePhone($to);

        if ($to === '') {
            return ['ok' => false, 'reason' => 'invalid_phone'];
        }

        $components = [];

        if (!empty($headerParams)) {
            $components[] = [
                'type' => 'header',
                'parameters' => collect($headerParams)->map(function ($value) {
                    return [
                        'type' => 'text',
                        'text' => $this->cleanText($value),
                    ];
                })->values()->all(),
            ];
        }

        if (!empty($bodyParams)) {
            $components[] = [
                'type' => 'body',
                'parameters' => collect($bodyParams)->map(function ($value) {
                    return [
                        'type' => 'text',
                        'text' => $this->cleanText($value),
                    ];
                })->values()->all(),
            ];
        }

        $url = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            config('whatsapp.version'),
            config('whatsapp.phone_number_id')
        );

        try {
            $response = Http::timeout(20)
                ->retry(2, 300)
                ->withToken(config('whatsapp.token'))
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'template',
                    'template' => [
                        'name' => $templateName,
                        'language' => [
                            'code' => $lang ?: config('whatsapp.default_lang', 'es_MX'),
                        ],
                        'components' => $components,
                    ],
                ]);

            if ($response->successful()) {
                Log::info('whatsapp.template.sent', [
                    'to'       => $to,
                    'template' => $templateName,
                    'response' => $response->json(),
                ]);

                return [
                    'ok'     => true,
                    'status' => $response->status(),
                    'data'   => $response->json(),
                ];
            }

            Log::warning('whatsapp.template.failed', [
                'to'       => $to,
                'template' => $templateName,
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'ok'     => false,
                'status' => $response->status(),
                'data'   => $response->json(),
            ];
        } catch (\Throwable $e) {
            Log::error('whatsapp.template.exception', [
                'to'       => $to,
                'template' => $templateName,
                'message'  => $e->getMessage(),
            ]);

            return [
                'ok'      => false,
                'reason'  => 'exception',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function sendTicketCreatedToUser(User $user, Ticket $ticket): array
    {
        $phone = $this->userPhone($user);

        if ($phone === null || !$this->canSendToUser($user)) {
            return ['ok' => false, 'reason' => 'user_not_eligible'];
        }

        $firstName = $this->firstName($user->name);

        return $this->sendTemplate(
            $phone,
            config('whatsapp.templates.ticket_created', 'ticket_created_v1'),
            [
                (string) $ticket->folio,
                Str::limit((string) $ticket->title, 60),
                $this->humanizeLabel((string) $ticket->area),
                $this->humanizeLabel((string) $ticket->priority),
            ],
            null,
            [
                $firstName,
            ]
        );
    }

    public function sendTicketStatusToUser(User $user, Ticket $ticket, string $statusLabel, ?string $actorName = null): array
    {
        $phone = $this->userPhone($user);

        if ($phone === null || !$this->canSendToUser($user)) {
            return ['ok' => false, 'reason' => 'user_not_eligible'];
        }

        $firstName = $this->firstName($user->name);

        return $this->sendTemplate(
            $phone,
            config('whatsapp.templates.ticket_status', 'ticket_status_update_v1'),
            [
                (string) $ticket->folio,
                Str::limit((string) $ticket->title, 60),
                Str::limit($statusLabel, 40),
                Str::limit((string) ($actorName ?: optional($ticket->assignee)->name ?: 'Sistema'), 40),
            ],
            null,
            [
                $firstName,
            ]
        );
    }

    public function sendTicketCommentToUser(User $user, Ticket $ticket, string $authorName, string $comment): array
    {
        $phone = $this->userPhone($user);

        if ($phone === null || !$this->canSendToUser($user)) {
            return ['ok' => false, 'reason' => 'user_not_eligible'];
        }

        $firstName = $this->firstName($user->name);

        return $this->sendTemplate(
            $phone,
            config('whatsapp.templates.ticket_comment', 'ticket_comment_v1'),
            [
                Str::limit((string) $ticket->title, 60),
                Str::limit($authorName, 40),
                Str::limit(preg_replace('/\s+/', ' ', trim($comment)), 120),
            ],
            null,
            [
                $firstName,
            ]
        );
    }

    public function sendAgendaReminderToUser(User $user, AgendaEvent $event): array
    {
        $phone = $this->userPhone($user);

        if ($phone === null || !$this->canSendToUser($user)) {
            return ['ok' => false, 'reason' => 'user_not_eligible'];
        }

        $tz = $event->timezone ?: 'America/Mexico_City';
        $startAt = $event->start_at
            ? Carbon::parse($event->start_at, $tz)->timezone($tz)->format('d/m/Y h:i A')
            : 'Sin fecha';

        $description = trim((string) ($event->description ?? ''));
        if ($description === '') {
            $description = 'Sin descripción';
        }

        return $this->sendTemplate(
            $phone,
            config('whatsapp.templates.agenda_reminder', 'agenda_reminder_v1'),
            [
                $this->firstName($user->name),
                Str::limit((string) $event->title, 80),
                $startAt,
                Str::limit($description, 120),
            ]
        );
    }

    protected function userPhone(User $user): ?string
    {
        $value = $user->whatsapp_phone
            ?? $user->phone
            ?? $user->telefono
            ?? null;

        $value = $this->normalizePhone((string) $value);

        return $value !== '' ? $value : null;
    }

    protected function canSendToUser(User $user): bool
    {
        if (!$this->enabled()) {
            return false;
        }

        return $this->userPhone($user) !== null;
    }

    protected function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?: '';
    }

    protected function cleanText($value): string
    {
        return Str::limit(
            preg_replace('/\s+/', ' ', trim((string) $value)),
            200,
            ''
        );
    }

    protected function humanizeLabel(string $value): string
    {
        $value = str_replace(['_', '-'], ' ', $value);
        return Str::title($value);
    }

    protected function firstName(?string $fullName): string
    {
        $fullName = trim((string) $fullName);

        if ($fullName === '') {
            return 'Usuario';
        }

        $parts = preg_split('/\s+/', $fullName) ?: [];

        $first = trim((string) ($parts[0] ?? 'Usuario'));

        return Str::limit($first, 25, '');
    }
}