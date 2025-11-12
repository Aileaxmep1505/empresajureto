<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $apiVersion;
    protected string $phoneId;
    protected string $token;
    protected string $base;

    public function __construct()
    {
        $this->apiVersion = config('services.whatsapp.version', env('WHATSAPP_API_VERSION', 'v21.0'));
        $this->phoneId    = config('services.whatsapp.phone_id', env('WHATSAPP_PHONE_NUMBER_ID'));
        $this->token      = config('services.whatsapp.token', env('WHATSAPP_ACCESS_TOKEN'));
        $this->base       = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneId}/messages";
    }

    public function sendTemplate(string $toE164, string $templateName, array $params = [], string $lang = 'es'): array
    {
        $components = [];
        if (!empty($params)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(fn($v) => ['type' => 'text', 'text' => (string)$v], $params),
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $toE164,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $lang],
                'components' => $components,
            ],
        ];

        $res = Http::withToken($this->token)
            ->acceptJson()
            ->post($this->base, $payload);

        $json = $res->json();

        if ($res->failed()) {
            Log::error('WhatsApp API error', [
                'status' => $res->status(),
                'response' => $json,
                'payload' => $payload,
            ]);
        } else {
            Log::info('WhatsApp API success', ['response' => $json]);
        }

        return $json ?? [];
    }

    /** Text libre (solo si está en ventana 24h) */
    public function sendText(string $toE164, string $text): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $toE164,
            'type' => 'text',
            'text' => ['body' => $text],
        ];

        $res = Http::withToken($this->token)->acceptJson()->post($this->base, $payload);
        $json = $res->json();
        if ($res->failed()) {
            Log::error('WhatsApp text error', ['status'=>$res->status(),'resp'=>$json]);
        } else {
            Log::info('WhatsApp text sent', ['resp'=>$json]);
        }
        return $json ?? [];
    }
}
