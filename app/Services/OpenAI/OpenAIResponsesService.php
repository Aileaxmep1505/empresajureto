<?php

namespace App\Services\OpenAI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIResponsesService
{
    public function enabled(): bool
    {
        return filled(config('services.openai.api_key'))
            && filled(config('services.openai.primary'));
    }

    public function create(array $payload): array
    {
        if (!$this->enabled()) {
            return ['ok' => false, 'reason' => 'openai_disabled'];
        }

        $baseUrl = rtrim((string) config('services.openai.base_url', 'https://api.openai.com'), '/');
        $apiKey = (string) config('services.openai.api_key');
        $timeout = (int) config('services.openai.timeout', 120);
        $connectTimeout = (int) config('services.openai.connect_timeout', 30);

        try {
            $request = Http::timeout($timeout)
                ->connectTimeout($connectTimeout)
                ->withToken($apiKey)
                ->acceptJson();

            if (filled(config('services.openai.org_id'))) {
                $request = $request->withHeaders([
                    'OpenAI-Organization' => config('services.openai.org_id'),
                ]);
            }

            if (filled(config('services.openai.project_id'))) {
                $request = $request->withHeaders([
                    'OpenAI-Project' => config('services.openai.project_id'),
                ]);
            }

            $response = $request->post($baseUrl.'/v1/responses', $payload);

            if (!$response->successful()) {
                Log::warning('openai.responses.failed', [
                    'status' => $response->status(),
                    'body' => $response->json() ?: $response->body(),
                ]);

                return [
                    'ok' => false,
                    'status' => $response->status(),
                    'data' => $response->json(),
                ];
            }

            $json = $response->json();

            return [
                'ok' => true,
                'status' => $response->status(),
                'data' => $json,
                'id' => (string) data_get($json, 'id', ''),
                'text' => (string) data_get($json, 'output_text', ''),
            ];
        } catch (\Throwable $e) {
            Log::error('openai.responses.exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'reason' => 'exception',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function ask(string $instructions, string $userText, ?string $previousResponseId = null, array $extra = []): array
    {
        $payload = array_merge([
            'model' => (string) config('services.openai.primary', 'gpt-5-2025-08-07'),
            'instructions' => $instructions,
            'input' => [
                [
                    'role' => 'user',
                    'content' => $userText,
                ],
            ],
        ], $extra);

        if ($previousResponseId) {
            $payload['previous_response_id'] = $previousResponseId;
        }

        return $this->create($payload);
    }
}