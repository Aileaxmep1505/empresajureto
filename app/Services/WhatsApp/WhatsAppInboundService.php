<?php

namespace App\Services\WhatsApp;

use App\Models\User;
use App\Models\WaConversation;
use App\Models\WaMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WhatsAppInboundService
{
    public function process(array $payload): void
    {
        Log::info('whatsapp.webhook.received', [
            'payload' => $payload,
        ]);

        foreach (($payload['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];

                $this->processStatuses($value);
                $this->processMessages($value);
            }
        }
    }

    protected function processStatuses(array $value): void
    {
        foreach (($value['statuses'] ?? []) as $status) {
            $waMessageId = (string) ($status['id'] ?? '');
            $newStatus   = (string) ($status['status'] ?? '');
            $recipientId = (string) ($status['recipient_id'] ?? '');
            $timestamp   = (string) ($status['timestamp'] ?? '');
            $errors      = $status['errors'] ?? [];
            $conversation = $status['conversation'] ?? null;
            $pricing      = $status['pricing'] ?? null;

            if ($waMessageId === '' || $newStatus === '') {
                Log::warning('whatsapp.webhook.status.invalid', [
                    'status_payload' => $status,
                ]);
                continue;
            }

            $msg = WaMessage::where('wa_message_id', $waMessageId)->first();

            if (!$msg) {
                Log::warning('whatsapp.webhook.status.not_found', [
                    'wa_message_id' => $waMessageId,
                    'status' => $newStatus,
                    'recipient_id' => $recipientId,
                    'status_payload' => $status,
                ]);
                continue;
            }

            $dt = null;
            if ($timestamp !== '' && ctype_digit($timestamp)) {
                $dt = Carbon::createFromTimestamp((int) $timestamp);
            }

            $meta = is_array($msg->meta) ? $msg->meta : [];

            $meta['last_status_webhook'] = $status;
            $meta['recipient_id'] = $recipientId;

            if ($dt) {
                $meta['last_status_at'] = $dt->toDateTimeString();
            }

            if (!empty($conversation)) {
                $meta['conversation'] = $conversation;
            }

            if (!empty($pricing)) {
                $meta['pricing'] = $pricing;
            }

            if (!empty($errors)) {
                $meta['errors'] = $errors;
            }

            $history = $meta['status_history'] ?? [];
            if (!is_array($history)) {
                $history = [];
            }

            $history[] = [
                'status' => $newStatus,
                'timestamp' => $dt ? $dt->toDateTimeString() : null,
                'recipient_id' => $recipientId,
                'errors' => $errors,
            ];

            $meta['status_history'] = $history;

            $update = [
                'status' => $newStatus,
                'payload' => $status,
                'meta' => $meta,
            ];

            if ($dt) {
                if ($newStatus === 'sent' && Schema::hasColumn('wa_messages', 'sent_at')) {
                    $update['sent_at'] = $dt;
                }

                if ($newStatus === 'delivered' && Schema::hasColumn('wa_messages', 'delivered_at')) {
                    $update['delivered_at'] = $dt;
                }

                if ($newStatus === 'read' && Schema::hasColumn('wa_messages', 'read_at')) {
                    $update['read_at'] = $dt;
                }

                if ($newStatus === 'failed' && Schema::hasColumn('wa_messages', 'failed_at')) {
                    $update['failed_at'] = $dt;
                }
            }

            $msg->update($update);

            Log::info('whatsapp.webhook.status', [
                'message_id'   => $waMessageId,
                'recipient_id' => $recipientId,
                'status'       => $newStatus,
                'conversation' => $conversation,
                'pricing'      => $pricing,
                'errors'       => $errors,
            ]);
        }
    }

    protected function processMessages(array $value): void
    {
        $businessPhone = $this->normalize((string) config('whatsapp.phone_number', ''));
        $displayPhone  = $this->normalize((string) data_get($value, 'metadata.display_phone_number', ''));

        foreach (($value['messages'] ?? []) as $message) {
            Log::info('whatsapp.webhook.inbound_message.raw', $message);

            $type = (string) ($message['type'] ?? 'text');
            $from = $this->normalize((string) ($message['from'] ?? ''));
            $waMessageId = (string) ($message['id'] ?? '');

            if ($from === '' || $waMessageId === '') {
                Log::warning('whatsapp.webhook.invalid_inbound_message', [
                    'from' => $from,
                    'wa_message_id' => $waMessageId,
                    'message' => $message,
                ]);
                continue;
            }

            if (
                ($businessPhone !== '' && $from === $businessPhone) ||
                ($displayPhone !== '' && $from === $displayPhone)
            ) {
                Log::info('whatsapp.webhook.ignored_own_message', [
                    'from' => $from,
                    'business_phone' => $businessPhone,
                    'display_phone' => $displayPhone,
                    'wa_message_id' => $waMessageId,
                ]);
                continue;
            }

            $alreadyExists = WaMessage::where('wa_message_id', $waMessageId)
                ->where('direction', 'inbound')
                ->exists();

            if ($alreadyExists) {
                Log::info('whatsapp.webhook.duplicate_message_ignored', [
                    'wa_message_id' => $waMessageId,
                    'from' => $from,
                ]);
                continue;
            }

            if (!in_array($type, ['text', 'button', 'interactive'], true)) {
                Log::info('whatsapp.webhook.unsupported_message_type_ignored', [
                    'type' => $type,
                    'from' => $from,
                    'wa_message_id' => $waMessageId,
                ]);
                continue;
            }

            $text = $this->extractText($message);

            if ($text === '') {
                Log::info('whatsapp.webhook.empty_text_ignored', [
                    'type' => $type,
                    'from' => $from,
                    'wa_message_id' => $waMessageId,
                ]);
                continue;
            }

            $user = $this->findUserByPhone($from);

            Log::info('whatsapp.webhook.user_match', [
                'from'    => $from,
                'user_id' => $user?->id,
            ]);

            $conversation = WaConversation::firstOrCreate(
                ['phone' => $from, 'channel' => 'whatsapp'],
                [
                    'user_id'         => $user?->id,
                    'status'          => 'bot',
                    'last_message_at' => now(),
                ]
            );

            $conversation->update([
                'user_id'         => $conversation->user_id ?: $user?->id,
                'last_message_at' => now(),
            ]);

            $waMessage = WaMessage::create([
                'conversation_id' => $conversation->id,
                'user_id'         => $user?->id,
                'direction'       => 'inbound',
                'message_type'    => $type,
                'wa_message_id'   => $waMessageId,
                'text'            => $text,
                'status'          => 'received',
                'payload'         => $message,
            ]);

            Log::info('whatsapp.webhook.message_saved', [
                'conversation_id' => $conversation->id,
                'wa_message_id'   => $waMessage->wa_message_id,
                'text'            => $waMessage->text,
            ]);

            app(WhatsAppAiAssistantService::class)->handleInbound(
                $conversation,
                $user,
                $waMessage
            );
        }
    }

    protected function extractText(array $message): string
    {
        $type = $message['type'] ?? 'text';

        $text = match ($type) {
            'text' => trim((string) data_get($message, 'text.body', '')),
            'button' => trim((string) data_get($message, 'button.text', '')),
            'interactive' => trim((string) (
                data_get($message, 'interactive.button_reply.title')
                ?: data_get($message, 'interactive.list_reply.title')
                ?: ''
            )),
            default => '',
        };

        return preg_replace('/\s+/', ' ', $text) ?: '';
    }

    protected function findUserByPhone(string $phone): ?User
    {
        $variants = $this->phoneVariants($phone);

        $hasWhatsappPhone = Schema::hasColumn('users', 'whatsapp_phone');
        $hasPhone = Schema::hasColumn('users', 'phone');

        if (!$hasWhatsappPhone && !$hasPhone) {
            Log::warning('whatsapp.webhook.no_phone_columns_on_users');
            return null;
        }

        return User::query()
            ->where(function ($q) use ($variants, $hasWhatsappPhone, $hasPhone) {
                foreach ($variants as $variant) {
                    if ($hasWhatsappPhone) {
                        $q->orWhere('whatsapp_phone', $variant);
                    }

                    if ($hasPhone) {
                        $q->orWhere('phone', $variant);
                    }
                }
            })
            ->first();
    }

    protected function phoneVariants(string $phone): array
    {
        $phone = $this->normalize($phone);
        $variants = [$phone];

        if (str_starts_with($phone, '521') && strlen($phone) === 13) {
            $variants[] = '52' . substr($phone, 3);
            $variants[] = substr($phone, 3);
        }

        if (str_starts_with($phone, '52') && strlen($phone) === 12) {
            $variants[] = '521' . substr($phone, 2);
            $variants[] = substr($phone, 2);
        }

        if (strlen($phone) === 10) {
            $variants[] = '52' . $phone;
            $variants[] = '521' . $phone;
        }

        return array_values(array_unique(array_filter($variants)));
    }

    protected function normalize(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?: '';
    }
}