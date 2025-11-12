<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppService
{
    protected string $apiVersion;
    protected string $phoneId;
    protected string $token;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiVersion = config('services.whatsapp.api_version', env('WHATSAPP_API_VERSION', 'v21.0'));
        $this->phoneId    = config('services.whatsapp.phone_number_id', env('WHATSAPP_PHONE_NUMBER_ID'));
        $this->token      = config('services.whatsapp.token', env('WHATSAPP_ACCESS_TOKEN'));
        $this->baseUrl    = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneId}/messages";
    }

    /**
     * Envía una plantilla (Cloud API).
     * @param string $toE164  Número receptor en E.164 (sin +), ej. 521220...
     * @param string $templateName  Nombre exacto de la plantilla aprobada
     * @param array $params  Parámetros de texto para el body (array de strings)
     * @param string $lang  Código de idioma (ej. 'es')
     * @return array respuesta JSON o array con 'error'
     */
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

        try {
            $res = Http::withToken($this->token)
                ->acceptJson()
                ->post($this->baseUrl, $payload);

            $json = $res->json();

            if ($res->failed()) {
                Log::error('WhatsApp API error (template)', [
                    'status' => $res->status(),
                    'response' => $json,
                    'payload' => $payload,
                ]);
                return ['error' => $json ?? 'unknown'];
            }

            Log::info('WhatsApp API success (template)', ['response' => $json]);
            return $json ?? [];
        } catch (Throwable $ex) {
            Log::error('WhatsAppService exception (template): '.$ex->getMessage(), ['payload' => $payload]);
            return ['error' => $ex->getMessage()];
        }
    }

    /**
     * Envío de texto libre (usar solo cuando aplique - ventana 24h).
     */
    public function sendText(string $toE164, string $text): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $toE164,
            'type' => 'text',
            'text' => ['body' => $text],
        ];

        try {
            $res = Http::withToken($this->token)
                ->acceptJson()
                ->post($this->baseUrl, $payload);

            $json = $res->json();
            if ($res->failed()) {
                Log::error('WhatsApp text error', ['status' => $res->status(), 'response' => $json]);
                return ['error' => $json ?? 'unknown'];
            }
            Log::info('WhatsApp text sent', ['response' => $json]);
            return $json ?? [];
        } catch (Throwable $ex) {
            Log::error('WhatsAppService exception (text): '.$ex->getMessage(), ['payload' => $payload]);
            return ['error' => $ex->getMessage()];
        }
    }
}
