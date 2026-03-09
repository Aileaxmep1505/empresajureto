<?php

namespace App\Services\WhatsApp;

use App\Models\User;
use App\Models\WaConversation;
use App\Models\WaMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WhatsAppInboundService
{
    public function process(array $payload): void
    {
        Log::info('whatsapp.webhook.received', $payload);

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
            $waMessageId = $status['id'] ?? null;

            if (!$waMessageId) {
                continue;
            }

            WaMessage::where('wa_message_id', $waMessageId)->update([
                'status'  => $status['status'] ?? null,
                'payload' => $status,
            ]);

            Log::info('whatsapp.webhook.status', [
                'message_id'   => $waMessageId,
                'recipient_id' => $status['recipient_id'] ?? null,
                'status'       => $status['status'] ?? null,
                'errors'       => $status['errors'] ?? [],
            ]);
        }
    }

    protected function processMessages(array $value): void
    {
        foreach (($value['messages'] ?? []) as $message) {
            Log::info('whatsapp.webhook.inbound_message', $message);

            $from = preg_replace('/\D+/', '', (string) ($message['from'] ?? ''));

            if ($from === '') {
                Log::warning('whatsapp.webhook.empty_from', ['message' => $message]);
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

            $text = $this->extractText($message);

            $waMessage = WaMessage::create([
                'conversation_id' => $conversation->id,
                'user_id'         => $user?->id,
                'direction'       => 'inbound',
                'message_type'    => $message['type'] ?? 'text',
                'wa_message_id'   => $message['id'] ?? null,
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

        return match ($type) {
            'text' => trim((string) data_get($message, 'text.body', '')),
            'button' => trim((string) data_get($message, 'button.text', '')),
            'interactive' => trim((string) (
                data_get($message, 'interactive.button_reply.title')
                ?: data_get($message, 'interactive.list_reply.title')
                ?: ''
            )),
            default => '',
        };
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
        $phone = preg_replace('/\D+/', '', $phone);
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
}