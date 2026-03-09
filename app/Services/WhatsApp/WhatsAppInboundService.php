<?php

namespace App\Services\WhatsApp;

use App\Models\User;
use App\Models\WaConversation;
use App\Models\WaMessage;
use Illuminate\Support\Facades\Log;

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
                'status' => $status['status'] ?? null,
                'payload' => $status,
            ]);

            Log::info('whatsapp.webhook.status', [
                'message_id' => $waMessageId,
                'recipient_id' => $status['recipient_id'] ?? null,
                'status' => $status['status'] ?? null,
                'errors' => $status['errors'] ?? [],
            ]);
        }
    }

    protected function processMessages(array $value): void
    {
        foreach (($value['messages'] ?? []) as $message) {
            $from = preg_replace('/\D+/', '', (string)($message['from'] ?? ''));

            if ($from === '') {
                continue;
            }

            $user = $this->findUserByPhone($from);

            $conversation = WaConversation::firstOrCreate(
                ['phone' => $from, 'channel' => 'whatsapp'],
                [
                    'user_id' => $user?->id,
                    'status' => 'bot',
                    'last_message_at' => now(),
                ]
            );

            $conversation->update([
                'user_id' => $conversation->user_id ?: $user?->id,
                'last_message_at' => now(),
            ]);

            $text = $this->extractText($message);

            $waMessage = WaMessage::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user?->id,
                'direction' => 'inbound',
                'message_type' => $message['type'] ?? 'text',
                'wa_message_id' => $message['id'] ?? null,
                'text' => $text,
                'status' => 'received',
                'payload' => $message,
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

        return User::query()
            ->where(function ($q) use ($variants) {
                foreach ($variants as $variant) {
                    $q->orWhere('whatsapp_phone', $variant)
                      ->orWhere('phone', $variant)
                      ->orWhere('telefono', $variant);
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