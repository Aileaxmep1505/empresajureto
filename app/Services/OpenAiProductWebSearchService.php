<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiProductWebSearchService
{
    public function searchPurchaseLinks(
        string $descripcion,
        string $unidad = '',
        mixed $cantidadMinima = null,
        mixed $cantidadMaxima = null,
        mixed $cantidadCotizada = null,
        array $strategy = [],
        int $limit = 7
    ): array {
        $limit = max(1, min($limit, 7));

        if (!filter_var(env('ENABLE_OPENAI_WEB_PRODUCT_SEARCH', true), FILTER_VALIDATE_BOOL)) {
            return [];
        }

        $apiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');

        if (!$apiKey) {
            return [];
        }

        $prompt = $this->buildPrompt(
            descripcion: $descripcion,
            unidad: $unidad,
            cantidadMinima: $cantidadMinima,
            cantidadMaxima: $cantidadMaxima,
            cantidadCotizada: $cantidadCotizada,
            strategy: $strategy,
            limit: $limit
        );

        $payloadBase = [
            'model' => env('OPENAI_WEB_SEARCH_MODEL', env('OPENAI_MATCH_MODEL', 'gpt-4o-mini')),
            'input' => [
                [
                    'role' => 'system',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => 'Eres un comprador B2B experto en México. Debes buscar links reales de compra en internet. No inventes URLs. Devuelve únicamente JSON válido.',
                        ],
                    ],
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => $prompt,
                        ],
                    ],
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'purchase_links',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'results' => [
                                'type' => 'array',
                                'maxItems' => $limit,
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'properties' => [
                                        'title' => ['type' => 'string'],
                                        'seller' => ['type' => ['string', 'null']],
                                        'source' => ['type' => 'string'],
                                        'url' => ['type' => 'string'],
                                        'price' => ['type' => ['number', 'null']],
                                        'currency' => ['type' => ['string', 'null']],
                                        'reason' => ['type' => 'string'],
                                        'confidence' => ['type' => 'number'],
                                    ],
                                    'required' => [
                                        'title',
                                        'seller',
                                        'source',
                                        'url',
                                        'price',
                                        'currency',
                                        'reason',
                                        'confidence',
                                    ],
                                ],
                            ],
                        ],
                        'required' => ['results'],
                    ],
                ],
            ],
        ];

        /*
         * Algunos proyectos/API usan web_search_preview y otros web_search.
         * Probamos ambos para evitar que falle por nombre de herramienta.
         */
        foreach (['web_search_preview', 'web_search'] as $toolType) {
            $payload = $payloadBase;
            $payload['tools'] = [
                [
                    'type' => $toolType,
                ],
            ];

            try {
                $response = Http::timeout(55)
                    ->retry(1, 900)
                    ->withToken($apiKey)
                    ->acceptJson()
                    ->post('https://api.openai.com/v1/responses', $payload);

                if (!$response->successful()) {
                    Log::warning('[OpenAiProductWebSearchService] OpenAI web search HTTP error', [
                        'tool_type' => $toolType,
                        'status' => $response->status(),
                        'body' => mb_substr($response->body(), 0, 1500),
                    ]);

                    continue;
                }

                $json = $response->json();
                $text = $this->extractOutputText($json);

                if (!$text) {
                    Log::warning('[OpenAiProductWebSearchService] Sin output_text', [
                        'tool_type' => $toolType,
                        'response' => mb_substr(json_encode($json, JSON_UNESCAPED_UNICODE), 0, 2000),
                    ]);

                    continue;
                }

                $decoded = json_decode($text, true);

                if (!is_array($decoded)) {
                    Log::warning('[OpenAiProductWebSearchService] JSON inválido', [
                        'tool_type' => $toolType,
                        'text' => mb_substr($text, 0, 1500),
                    ]);

                    continue;
                }

                $results = collect($decoded['results'] ?? [])
                    ->map(fn ($row) => $this->normalizeRow($row, $strategy))
                    ->filter(fn ($row) => $row['title'] !== '' && $row['url'] !== '')
                    ->filter(fn ($row) => $this->isValidUrl($row['url']))
                    ->filter(fn ($row) => (float) $row['score'] >= 55)
                    ->unique(fn ($row) => $this->normalizeUrl($row['url']))
                    ->sortByDesc('score')
                    ->take($limit)
                    ->values()
                    ->all();

                if (!empty($results)) {
                    return $results;
                }
            } catch (\Throwable $e) {
                Log::error('[OpenAiProductWebSearchService] Error buscando links con OpenAI web search', [
                    'tool_type' => $toolType,
                    'descripcion' => $descripcion,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
        }

        return [];
    }

    protected function buildPrompt(
        string $descripcion,
        string $unidad,
        mixed $cantidadMinima,
        mixed $cantidadMaxima,
        mixed $cantidadCotizada,
        array $strategy,
        int $limit
    ): string {
        $productoPrincipal = $strategy['producto_principal'] ?? $descripcion;
        $categoria = $strategy['categoria_probable'] ?? 'general';
        $obligatorias = implode(', ', $strategy['palabras_obligatorias'] ?? []);
        $sinonimos = implode(', ', $strategy['sinonimos_validos'] ?? []);
        $prohibidas = implode(', ', $strategy['palabras_prohibidas'] ?? []);
        $aceptacion = implode(' | ', $strategy['criterios_aceptacion'] ?? []);
        $rechazo = implode(' | ', $strategy['criterios_rechazo'] ?? []);
        $queries = implode(' | ', $strategy['queries_compra'] ?? []);

        return <<<PROMPT
Busca en internet links reales de compra para México.

Necesito máximo {$limit} resultados. Devuelve solo resultados que sí parezcan comprables o cotizables.

PRODUCTO SOLICITADO:
{$descripcion}

PRODUCTO PRINCIPAL NORMALIZADO:
{$productoPrincipal}

CATEGORÍA PROBABLE:
{$categoria}

UNIDAD:
{$unidad}

CANTIDAD MÍNIMA:
{$cantidadMinima}

CANTIDAD MÁXIMA:
{$cantidadMaxima}

CANTIDAD COTIZADA:
{$cantidadCotizada}

PALABRAS OBLIGATORIAS O EQUIVALENTES:
{$obligatorias}

SINÓNIMOS VÁLIDOS:
{$sinonimos}

PALABRAS PROHIBIDAS:
{$prohibidas}

CRITERIOS DE ACEPTACIÓN:
{$aceptacion}

CRITERIOS DE RECHAZO:
{$rechazo}

QUERIES SUGERIDAS:
{$queries}

TIENDAS O FUENTES PRIORITARIAS:
- Amazon México
- Mercado Libre México
- Walmart México
- Chedraui
- Office Depot México
- Pedidos.com
- DC Mayorista
- Tony Superpapelerías
- Marchand
- Papelerías o distribuidores mexicanos
- Proveedores B2B mexicanos

REGLAS ESTRICTAS:
1. Devuelve solo URLs reales encontradas en la web.
2. No inventes URLs.
3. Prioriza links directos de producto.
4. Si no hay suficientes links directos, puedes incluir páginas de búsqueda internas de una tienda, pero solo si son útiles.
5. No aceptes productos con función principal distinta.
6. No aceptes productos que solo coincidan por color, material, cantidad o presentación.
7. Si el producto solicitado tiene medida, color, modelo, compatibilidad o material, prioriza resultados que lo respeten.
8. No metas resultados de otro país si hay opciones de México.
9. Devuelve JSON estricto con results.
PROMPT;
    }

    protected function extractOutputText(array $json): ?string
    {
        $text = data_get($json, 'output_text');

        if ($text) {
            return $text;
        }

        foreach (($json['output'] ?? []) as $output) {
            foreach (($output['content'] ?? []) as $content) {
                if (!empty($content['text'])) {
                    return $content['text'];
                }
            }
        }

        return null;
    }

    protected function normalizeRow(array $row, array $strategy): array
    {
        $url = trim((string) ($row['url'] ?? ''));
        $confidence = (float) ($row['confidence'] ?? 70);

        return [
            'source' => trim((string) ($row['source'] ?? 'OpenAI Web Search')),
            'title' => trim((string) ($row['title'] ?? '')),
            'seller' => $row['seller'] ?? null,
            'price' => isset($row['price']) && is_numeric($row['price']) ? (float) $row['price'] : null,
            'currency' => $row['currency'] ?: 'MXN',
            'url' => $url,
            'score' => round(max(min($confidence, 100), 0), 2),
            'strategy_ok' => true,
            'family_ok' => true,
            'family' => $strategy['categoria_probable'] ?? null,
            'matched_tokens' => $strategy['palabras_obligatorias'] ?? [],
            'missing_tokens' => [],
            'forbidden_hits' => [],
            'thumbnail' => null,
            'condition' => null,
            'raw' => [
                'provider' => 'openai_web_search',
                'reason' => $row['reason'] ?? null,
                'strategy' => $strategy,
            ],
            'unidad_coincide' => true,
        ];
    }

    protected function isValidUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (!$host) {
            return false;
        }

        $badHosts = [
            'google.com',
            'www.google.com',
            'google.com.mx',
            'www.google.com.mx',
        ];

        /*
         * Permitimos Google solo si es fallback del otro servicio.
         * Aquí queremos links encontrados por web search, no resultados genéricos.
         */
        return !in_array(strtolower($host), $badHosts, true);
    }

    protected function normalizeUrl(string $url): string
    {
        $parts = parse_url(trim($url));

        if (!$parts || empty($parts['host'])) {
            return trim($url);
        }

        return strtolower(($parts['scheme'] ?? 'https') . '://' . $parts['host'] . ($parts['path'] ?? ''));
    }
}