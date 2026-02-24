<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppCloud
{
    protected string $token;
    protected string $version;
    protected string $phoneNumberId;

    public function __construct()
    {
        $this->token         = (string) config('whatsapp.token');
        $this->version       = (string) config('whatsapp.version', 'v21.0');
        $this->phoneNumberId = (string) config('whatsapp.phone_number_id');
    }

    protected function baseUrl(): string
    {
        return "https://graph.facebook.com/{$this->version}";
    }

    protected function client()
    {
        return Http::withToken($this->token)->acceptJson()->asJson();
    }

    /** Convierte MX 10 dígitos a 521XXXXXXXXXX (sin +) */
    public function toMxE164(string $mxOrE164): string
    {
        $digits = preg_replace('/\D+/', '', $mxOrE164) ?: '';

        if (str_starts_with($digits, '521') && strlen($digits) >= 13) return $digits;
        if (str_starts_with($digits, '52')  && strlen($digits) >= 12) return $digits;

        if (strlen($digits) === 10) return '521' . $digits;

        return $digits;
    }

    /** Enviar texto libre (ventana 24h) */
    public function sendText(string $toE164, string $message, bool $previewUrl = false): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'   => $toE164,
            'type' => 'text',
            'text' => [
                'body' => $message,
                'preview_url' => $previewUrl,
            ],
        ];

        return $this->client()
            ->post($this->baseUrl() . "/{$this->phoneNumberId}/messages", $payload)
            ->throw()
            ->json();
    }

    /** Marcar mensaje como leído (opcional) */
    public function markAsRead(string $waMessageId): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $waMessageId,
        ];

        return $this->client()
            ->post($this->baseUrl() . "/{$this->phoneNumberId}/messages", $payload)
            ->throw()
            ->json();
    }
}