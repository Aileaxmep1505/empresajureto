<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TechSheetAiService
{
    public function generate(array $data): ?array
    {
        try {
            $payload = [
                'nombre_producto'     => $data['product_name'] ?? null,
                'descripcion_usuario' => $data['user_description'] ?? null,
                'marca'               => $data['brand'] ?? null,
                'modelo'              => $data['model'] ?? null,
                'referencia'          => $data['reference'] ?? null,
                'identificacion'      => $data['identification'] ?? null,
            ];

            $system = "Eres un experto en redacción de fichas técnicas de productos electrónicos.
Genera textos claros, técnicos pero entendibles.
Si no tienes datos suficientes, escribe información genérica y segura (sin inventar números muy específicos).
SIEMPRE responde en JSON válido.";

            $user = "Con la siguiente información del producto, genera:

- descripcion_formal: 1 párrafo en tono profesional.
- caracteristicas: lista de bullet points (frases cortas).
- especificaciones: lista de objetos {\"nombre\": string, \"valor\": string}.

Responde SOLO con un JSON con esta estructura:

{
  \"descripcion_formal\": \"...\",
  \"caracteristicas\": [\"...\", \"...\"],
  \"especificaciones\": [
    {\"nombre\": \"Voltaje de entrada\", \"valor\": \"100–240 V ~ 50/60 Hz\"},
    {\"nombre\": \"Tipo de baterías\", \"valor\": \"AA, AAA, C, D, 9V recargables\"}
  ]
}

Datos del producto:\n\n" . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            // ==========================
            // Config desde config/services.php
            // ==========================
            $apiKey  = config('services.openai.api_key');
            $baseUrl = rtrim(config('services.openai.base_url', 'https://api.openai.com'), '/');
            $model   = config('services.openai.primary', config('services.openai.model', 'gpt-5-2025-08-07'));

            if (! $apiKey) {
                Log::error('TechSheetAiService: falta OPENAI_API_KEY en .env');
                return null;
            }

            $endpoint = $baseUrl . '/v1/chat/completions';

            $response = Http::withToken($apiKey)
                ->timeout(config('services.openai.timeout', 300))
                ->connectTimeout(config('services.openai.connect_timeout', 30))
                ->post($endpoint, [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user',   'content' => $user],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (! $response->ok()) {
                Log::error('TechSheetAiService error HTTP', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $content = $response->json('choices.0.message.content');

            $json = json_decode($content, true);

            if (! is_array($json)) {
                Log::warning('TechSheetAiService: respuesta no es JSON', ['content' => $content]);
                return null;
            }

            return [
                'ai_description' => $json['descripcion_formal'] ?? null,
                'ai_features'    => $json['caracteristicas'] ?? [],
                'ai_specs'       => $json['especificaciones'] ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error('TechSheetAiService exception', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
