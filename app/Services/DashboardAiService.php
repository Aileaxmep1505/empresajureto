<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class DashboardAiService
{
    public function getDailyInspirationalPhrase(string $userName = 'Usuario'): ?string
    {
        try {
            $apiKey = config('services.openai.api_key');
            $baseUrl = rtrim(config('services.openai.base_url', 'https://api.openai.com'), '/');
            $model = config('services.openai.primary', 'gpt-5-2025-08-07');
            $timeout = (int) config('services.openai.timeout', 300);
            $connectTimeout = (int) config('services.openai.connect_timeout', 30);

            Log::info('Dashboard IA: iniciando generación de frase', [
                'has_api_key' => !blank($apiKey),
                'base_url' => $baseUrl,
                'model' => $model,
                'timeout' => $timeout,
                'connect_timeout' => $connectTimeout,
            ]);

            if (blank($apiKey)) {
                Log::warning('Dashboard IA: OPENAI_API_KEY vacío o no cargado');
                return null;
            }

            $payload = [
                'model' => $model,
                'reasoning_effort' => 'minimal',
                'max_completion_tokens' => 120,
                'messages' => [
                    [
                        'role' => 'developer',
                        'content' => 'Genera únicamente una frase inspiradora corta en español para un dashboard empresarial. Debe ser elegante, natural, profesional y fresca. No uses comillas. No uses emojis. No uses listas. No uses saludo. No pongas el nombre de la persona. Máximo 18 palabras. Devuelve solo la frase.'
                    ],
                    [
                        'role' => 'user',
                        'content' => 'Genera una frase inspiradora distinta para hoy.'
                    ],
                ],
            ];

            $response = Http::timeout($timeout)
                ->connectTimeout($connectTimeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ])
                ->post($baseUrl . '/v1/chat/completions', $payload);

            Log::info('Dashboard IA: respuesta recibida', [
                'status' => $response->status(),
                'ok' => $response->successful(),
                'body' => $response->body(),
            ]);

            if (!$response->successful()) {
                Log::error('Dashboard IA: OpenAI devolvió error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $json = $response->json();

            $text = data_get($json, 'choices.0.message.content');
            $finishReason = data_get($json, 'choices.0.finish_reason');
            $reasoningTokens = data_get($json, 'usage.completion_tokens_details.reasoning_tokens');

            Log::info('Dashboard IA: contenido extraído', [
                'text' => $text,
                'finish_reason' => $finishReason,
                'reasoning_tokens' => $reasoningTokens,
            ]);

            if (!is_string($text) || trim($text) === '') {
                Log::warning('Dashboard IA: contenido vacío o inválido', [
                    'finish_reason' => $finishReason,
                    'reasoning_tokens' => $reasoningTokens,
                ]);
                return null;
            }

            $text = trim($text);
            $text = str_replace(["\r", "\n"], ' ', $text);
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text, " \t\n\r\0\x0B\"'“”");

            if ($text === '') {
                Log::warning('Dashboard IA: texto quedó vacío después de limpiar');
                return null;
            }

            if (Str::length($text) > 160) {
                $text = Str::limit($text, 157, '...');
            }

            Log::info('Dashboard IA: frase final generada', [
                'phrase' => $text,
            ]);

            return $text;
        } catch (Throwable $e) {
            Log::error('Dashboard IA: excepción al generar frase', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            report($e);

            return null;
        }
    }
}