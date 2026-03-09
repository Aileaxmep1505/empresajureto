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

    protected function baseUrl(): string
    {
        return rtrim((string) config('services.openai.base_url', 'https://api.openai.com'), '/');
    }

    protected function apiKey(): string
    {
        return (string) config('services.openai.api_key');
    }

    protected function primaryModel(): string
    {
        return (string) config('services.openai.primary', 'gpt-5-2025-08-07');
    }

    protected function request()
    {
        $timeout = (int) config('services.openai.timeout', 120);
        $connectTimeout = (int) config('services.openai.connect_timeout', 30);

        $request = Http::timeout($timeout)
            ->connectTimeout($connectTimeout)
            ->withToken($this->apiKey())
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

        return $request;
    }

    public function ask(string $instructions, string $userText, ?string $previousResponseId = null, array $extra = []): array
    {
        if (!$this->enabled()) {
            return ['ok' => false, 'reason' => 'openai_disabled'];
        }

        $payload = array_merge([
            'model' => $this->primaryModel(),
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

        try {
            $response = $this->request()->post($this->baseUrl().'/v1/responses', $payload);

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

            Log::info('openai.responses.ok', [
                'id' => data_get($json, 'id'),
                'model' => data_get($json, 'model'),
                'has_output_text' => filled((string) data_get($json, 'output_text', '')),
            ]);

            return [
                'ok' => true,
                'id' => (string) data_get($json, 'id', ''),
                'status' => $response->status(),
                'data' => $json,
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

    /**
     * Router semántico con JSON schema estricto usando Chat Completions.
     */
    public function routeStructured(string $instructions, array $input): array
    {
        if (!$this->enabled()) {
            return ['ok' => false, 'reason' => 'openai_disabled'];
        }

        $payload = [
            'model' => $this->primaryModel(),
            'messages' => [
                [
                    'role' => 'developer',
                    'content' => $instructions,
                ],
                [
                    'role' => 'user',
                    'content' => json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'intent_router',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'intent' => [
                                'type' => 'string',
                                'enum' => [
                                    'company_info',
                                    'help',
                                    'agenda_query',
                                    'ticket_query',
                                    'ticket_priority',
                                    'catalog_query',
                                    'catalog_low_stock',
                                    'catalog_featured',
                                    'marketplace_summary',
                                    'tickets_by_area',
                                    'handoff_human',
                                    'general_internal_assistant',
                                ],
                            ],
                            'confidence' => [
                                'type' => 'number',
                                'minimum' => 0,
                                'maximum' => 1,
                            ],
                            'needs_db' => ['type' => 'boolean'],
                            'time_scope' => [
                                'type' => ['string', 'null'],
                                'enum' => [null, 'today', 'this_week', 'this_month', 'next'],
                            ],
                            'user_scope' => [
                                'type' => ['string', 'null'],
                                'enum' => [null, 'self', 'team', 'global'],
                            ],
                            'focus' => [
                                'type' => ['string', 'null'],
                                'enum' => [null, 'summary', 'detail', 'upcoming', 'urgent', 'low_stock', 'featured', 'market'],
                            ],
                            'ticket_folio' => [
                                'type' => ['string', 'null'],
                            ],
                            'area' => [
                                'type' => ['string', 'null'],
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'minimum' => 1,
                                'maximum' => 12,
                            ],
                        ],
                        'required' => [
                            'intent',
                            'confidence',
                            'needs_db',
                            'time_scope',
                            'user_scope',
                            'focus',
                            'ticket_folio',
                            'area',
                            'limit',
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = $this->request()->post($this->baseUrl().'/v1/chat/completions', $payload);

            if (!$response->successful()) {
                Log::warning('openai.router.failed', [
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
            $content = (string) data_get($json, 'choices.0.message.content', '');

            Log::info('openai.router.ok', [
                'model' => data_get($json, 'model'),
                'content' => $content,
            ]);

            $decoded = json_decode($content, true);

            if (!is_array($decoded)) {
                Log::warning('openai.router.invalid_json', [
                    'content' => $content,
                ]);

                return ['ok' => false, 'reason' => 'invalid_json'];
            }

            return [
                'ok' => true,
                'data' => $decoded,
                'raw' => $content,
            ];
        } catch (\Throwable $e) {
            Log::error('openai.router.exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'reason' => 'exception',
                'message' => $e->getMessage(),
            ];
        }
    }
}