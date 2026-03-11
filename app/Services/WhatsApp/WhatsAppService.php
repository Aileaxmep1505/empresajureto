<?php

namespace App\Services\WhatsApp;

use App\Models\AgendaEvent;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WaConversation;
use App\Models\WaMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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

    protected function apiUrl(): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            config('whatsapp.version'),
            config('whatsapp.phone_number_id')
        );
    }

    public function sendTemplate(
        string $to,
        string $templateName,
        array $bodyParams = [],
        ?string $lang = null,
        array $headerParams = [],
        ?WaConversation $conversation = null
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

        return $this->sendRaw(
            $to,
            [
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
            ],
            'template',
            $conversation,
            $templateName
        );
    }

    public function sendText(string $to, string $text, ?WaConversation $conversation = null): array
    {
        if (!$this->enabled()) {
            return ['ok' => false, 'reason' => 'whatsapp_disabled'];
        }

        $to = $this->normalizePhone($to);

        if ($to === '') {
            return ['ok' => false, 'reason' => 'invalid_phone'];
        }

        return $this->sendRaw(
            $to,
            [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $this->cleanText($text),
                ],
            ],
            'text',
            $conversation
        );
    }

    public function sendButtons(string $to, string $body, array $buttons, ?WaConversation $conversation = null): array
    {
        if (!$this->enabled()) {
            return ['ok' => false, 'reason' => 'whatsapp_disabled'];
        }

        $to = $this->normalizePhone($to);

        if ($to === '') {
            return ['ok' => false, 'reason' => 'invalid_phone'];
        }

        $buttonPayload = collect($buttons)->take(3)->values()->map(function ($btn, $i) {
            return [
                'type' => 'reply',
                'reply' => [
                    'id' => (string)($btn['id'] ?? ('btn_' . ($i + 1))),
                    'title' => Str::limit((string)($btn['title'] ?? 'Opción'), 20, ''),
                ],
            ];
        })->all();

        return $this->sendRaw(
            $to,
            [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'interactive',
                'interactive' => [
                    'type' => 'button',
                    'body' => [
                        'text' => $this->cleanText($body),
                    ],
                    'action' => [
                        'buttons' => $buttonPayload,
                    ],
                ],
            ],
            'interactive',
            $conversation
        );
    }

    protected function sendRaw(
        string $to,
        array $payload,
        string $messageType,
        ?WaConversation $conversation = null,
        ?string $templateName = null
    ): array {
        try {
            $response = Http::timeout(20)
                ->retry(2, 300)
                ->withToken(config('whatsapp.token'))
                ->post($this->apiUrl(), $payload);

            $json = $response->json();

            if ($response->successful()) {
                Log::info('whatsapp.outbound.accepted', [
                    'to' => $to,
                    'message_type' => $messageType,
                    'template' => $templateName,
                    'request_payload' => $payload,
                    'response' => $json,
                ]);

                $waMessageId   = data_get($json, 'messages.0.id');
                $messageStatus = data_get($json, 'messages.0.message_status', 'accepted');

                $conversation = $conversation ?: $this->findOrCreateConversationByPhone($to);

                try {
                    $messageData = [
                        'conversation_id' => $conversation->id,
                        'user_id'         => $conversation->user_id,
                        'direction'       => 'outbound',
                        'message_type'    => $messageType,
                        'wa_message_id'   => $waMessageId,
                        'text'            => $messageType === 'text' ? data_get($payload, 'text.body') : null,
                        'status'          => $messageStatus,
                        'payload'         => $json,
                        'meta'            => [
                            'template_name'   => $templateName,
                            'request_payload' => $payload,
                            'to'              => $to,
                            'phone_number_id' => config('whatsapp.phone_number_id'),
                        ],
                    ];

                    if (Schema::hasColumn('wa_messages', 'sent_at')) {
                        $messageData['sent_at'] = now();
                    }

                    if (Schema::hasColumn('wa_messages', 'from_wa_id')) {
                        $messageData['from_wa_id'] = config('whatsapp.phone_number_id');
                    }

                    if (Schema::hasColumn('wa_messages', 'to_wa_id')) {
                        $messageData['to_wa_id'] = $to;
                    }

                    WaMessage::create($messageData);
                } catch (\Throwable $dbEx) {
                    Log::warning('whatsapp.outbound.message_log_failed', [
                        'to' => $to,
                        'message_type' => $messageType,
                        'template' => $templateName,
                        'wa_message_id' => $waMessageId,
                        'db_error' => $dbEx->getMessage(),
                    ]);
                }

                try {
                    $preview = $messageType === 'text'
                        ? (string) data_get($payload, 'text.body', '')
                        : '[' . strtoupper($messageType) . ']';

                    $meta = (array) ($conversation->meta ?? []);
                    $meta['last_message_preview'] = Str::limit($preview, 200);

                    $conversation->update([
                        'last_message_at' => now(),
                        'meta' => $meta,
                    ]);
                } catch (\Throwable $convEx) {
                    Log::warning('whatsapp.outbound.conversation_update_failed', [
                        'to' => $to,
                        'message_type' => $messageType,
                        'template' => $templateName,
                        'db_error' => $convEx->getMessage(),
                    ]);
                }

                return [
                    'ok' => true,
                    'status' => $response->status(),
                    'data' => $json,
                    'wa_message_id' => $waMessageId,
                ];
            }

            Log::warning('whatsapp.outbound.failed', [
                'to' => $to,
                'message_type' => $messageType,
                'template' => $templateName,
                'request_payload' => $payload,
                'status' => $response->status(),
                'response' => $json,
            ]);

            return [
                'ok' => false,
                'status' => $response->status(),
                'data' => $json,
            ];
        } catch (\Throwable $e) {
            Log::error('whatsapp.outbound.exception', [
                'to' => $to,
                'message_type' => $messageType,
                'template' => $templateName,
                'request_payload' => $payload ?? null,
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'reason' => 'exception',
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function findOrCreateConversationByPhone(string $to): WaConversation
    {
        $to = $this->normalizePhone($to);

        $conversation = WaConversation::query()
            ->where('phone', $to)
            ->where('channel', 'whatsapp')
            ->first();

        if ($conversation) {
            return $conversation;
        }

        return WaConversation::create([
            'user_id' => $this->findUserIdByPhone($to),
            'phone' => $to,
            'channel' => 'whatsapp',
            'status' => 'bot',
            'last_message_at' => now(),
            'meta' => [
                'created_by' => 'outbound_service',
            ],
        ]);
    }

    protected function findUserIdByPhone(string $phone): ?int
    {
        $phone = $this->normalizePhone($phone);

        $query = User::query();

        $hasWhatsappPhone = Schema::hasColumn('users', 'whatsapp_phone');
        $hasPhone = Schema::hasColumn('users', 'phone');
        $hasTelefono = Schema::hasColumn('users', 'telefono');

        if (!$hasWhatsappPhone && !$hasPhone && !$hasTelefono) {
            return null;
        }

        $query->where(function ($q) use ($phone, $hasWhatsappPhone, $hasPhone, $hasTelefono) {
            if ($hasWhatsappPhone) {
                $q->orWhere('whatsapp_phone', $phone);
            }

            if ($hasPhone) {
                $q->orWhere('phone', $phone);
            }

            if ($hasTelefono) {
                $q->orWhere('telefono', $phone);
            }
        });

        $user = $query->first(['id']);

        return $user?->id;
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
            [$firstName]
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
            [$firstName]
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
            [$firstName]
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
                Str::limit((string) $event->title, 80),
                $startAt,
                Str::limit($description, 120),
            ],
            null,
            [$this->firstName($user->name)]
        );
    }

    protected function userPhone(User $user): ?string
    {
        $candidates = [];

        if (isset($user->whatsapp_phone)) {
            $candidates[] = $user->whatsapp_phone;
        }

        if (isset($user->phone)) {
            $candidates[] = $user->phone;
        }

        if (isset($user->telefono)) {
            $candidates[] = $user->telefono;
        }

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizePhone((string) $candidate);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return null;
    }

    protected function canSendToUser(User $user): bool
    {
        if (!$this->enabled()) {
            return false;
        }

        return $this->userPhone($user) !== null;
    }

    public function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?: '';
    }

    protected function cleanText($value): string
    {
        return Str::limit(
            preg_replace('/\s+/', ' ', trim((string) $value)),
            1000,
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