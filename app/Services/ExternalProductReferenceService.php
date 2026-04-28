<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExternalProductReferenceService
{
    public function __construct(
        protected ProductSearchStrategyService $strategyService,
        protected OpenAiProductWebSearchService $openAiWebSearchService
    ) {}

    public function searchTop3(
        string $descripcion,
        string $unidad = '',
        mixed $cantidadMinima = null,
        mixed $cantidadMaxima = null,
        mixed $cantidadCotizada = null
    ): array {
        return $this->searchTopN(
            descripcion: $descripcion,
            unidad: $unidad,
            cantidadMinima: $cantidadMinima,
            cantidadMaxima: $cantidadMaxima,
            cantidadCotizada: $cantidadCotizada,
            limit: (int) env('EXTERNAL_RESULTS_LIMIT', 7)
        );
    }

    public function searchTopN(
        string $descripcion,
        string $unidad = '',
        mixed $cantidadMinima = null,
        mixed $cantidadMaxima = null,
        mixed $cantidadCotizada = null,
        int $limit = 7
    ): array {
        $descripcion = trim($descripcion);
        $limit = max(1, min($limit, 7));

        if ($descripcion === '') {
            return [];
        }

        $strategy = $this->strategyService->build(
            descripcion: $descripcion,
            unidad: $unidad,
            cantidadMinima: $cantidadMinima,
            cantidadMaxima: $cantidadMaxima,
            cantidadCotizada: $cantidadCotizada
        );

        Log::info('[ExternalProductReferenceService] Estrategia IA generada', [
            'descripcion' => $descripcion,
            'producto_principal' => $strategy['producto_principal'] ?? null,
            'categoria_probable' => $strategy['categoria_probable'] ?? null,
            'queries_compra' => $strategy['queries_compra'] ?? [],
            'queries_catalogo' => $strategy['queries_catalogo'] ?? [],
            'palabras_obligatorias' => $strategy['palabras_obligatorias'] ?? [],
            'palabras_prohibidas' => $strategy['palabras_prohibidas'] ?? [],
        ]);

        /*
         * CAPA 1:
         * OpenAI con búsqueda web intenta encontrar links reales de compra.
         */
        $openAiWebResults = $this->openAiWebSearchService->searchPurchaseLinks(
            descripcion: $descripcion,
            unidad: $unidad,
            cantidadMinima: $cantidadMinima,
            cantidadMaxima: $cantidadMaxima,
            cantidadCotizada: $cantidadCotizada,
            strategy: $strategy,
            limit: $limit
        );

        if (!empty($openAiWebResults)) {
            return collect($openAiWebResults)
                ->take($limit)
                ->values()
                ->all();
        }

        /*
         * CAPA 2:
         * Mercado Libre API, si no está bloqueada.
         */
        $queriesCompra = $this->buildQueriesCompra(
            descripcion: $descripcion,
            unidad: $unidad,
            cantidadMinima: $cantidadMinima,
            cantidadMaxima: $cantidadMaxima,
            cantidadCotizada: $cantidadCotizada,
            strategy: $strategy
        );

        $rawResults = collect();

        foreach ($queriesCompra->take(3) as $query) {
            foreach ($this->searchMercadoLibre($query) as $result) {
                $rawResults->push($result);
            }
        }

        $validResults = $rawResults
            ->filter(fn ($row) => !empty($row['url']) && !empty($row['title']))
            ->unique(fn ($row) => $this->normalizeUrl((string) $row['url']))
            ->map(fn ($row) => $this->scoreResult(
                row: $row,
                descripcion: $descripcion,
                unidad: $unidad,
                strategy: $strategy
            ))
            ->filter(fn ($row) => $row['score'] >= 55 && $row['strategy_ok'] === true)
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->all();

        if (!empty($validResults)) {
            return $validResults;
        }

        /*
         * CAPA 3:
         * Links inteligentes por tienda. No son inventados como producto,
         * son URLs de búsqueda listas para comprar/cotizar.
         */
        return $this->fallbackReferenceLinks(
            descripcion: $descripcion,
            unidad: $unidad,
            cantidadMinima: $cantidadMinima,
            cantidadMaxima: $cantidadMaxima,
            cantidadCotizada: $cantidadCotizada,
            strategy: $strategy,
            limit: $limit
        );
    }

    protected function buildQueriesCompra(
        string $descripcion,
        string $unidad,
        mixed $cantidadMinima,
        mixed $cantidadMaxima,
        mixed $cantidadCotizada,
        array $strategy
    ) {
        $queries = collect($strategy['queries_compra'] ?? [])
            ->map(fn ($query) => trim((string) $query))
            ->filter()
            ->unique()
            ->values();

        if ($queries->isEmpty()) {
            $producto = trim((string) ($strategy['producto_principal'] ?? $descripcion));

            $queries = collect([
                trim($producto . ' comprar México'),
                trim($producto . ' proveedor México'),
                trim($producto . ' precio México'),
            ]);
        }

        $cantidad = $cantidadCotizada ?: $cantidadMaxima ?: $cantidadMinima;

        return $queries
            ->map(function ($query) use ($cantidad, $unidad) {
                $query = trim((string) $query);

                if ($cantidad && is_numeric($cantidad) && (float) $cantidad > 1) {
                    $cantidadInt = (string) ((int) $cantidad);

                    if (!str_contains($query, $cantidadInt)) {
                        $query .= ' ' . $cantidadInt;
                    }
                }

                if ($unidad !== '' && !str_contains(Str::lower($query), Str::lower($unidad))) {
                    $query .= ' ' . $unidad;
                }

                return trim($query);
            })
            ->filter()
            ->unique()
            ->values();
    }

    protected function searchMercadoLibre(string $query): array
    {
        try {
            $response = Http::timeout(15)
                ->retry(1, 500)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0 JuretoQuotationBot/1.0',
                ])
                ->get('https://api.mercadolibre.com/sites/MLM/search', [
                    'q' => $query,
                    'limit' => 30,
                ]);

            if (!$response->successful()) {
                Log::warning('[ExternalProductReferenceService] MercadoLibre HTTP error', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                    'query' => $query,
                ]);

                return [];
            }

            $json = $response->json();

            return collect($json['results'] ?? [])
                ->map(function ($item) use ($query) {
                    return [
                        'source' => 'Mercado Libre',
                        'title' => trim((string) ($item['title'] ?? '')),
                        'seller' => data_get($item, 'seller.nickname'),
                        'price' => isset($item['price']) ? (float) $item['price'] : null,
                        'currency' => $item['currency_id'] ?? 'MXN',
                        'url' => (string) ($item['permalink'] ?? ''),
                        'condition' => $item['condition'] ?? null,
                        'thumbnail' => $item['thumbnail'] ?? null,
                        'search_query' => $query,
                        'raw' => [
                            'provider' => 'mercadolibre',
                            'id' => $item['id'] ?? null,
                            'category_id' => $item['category_id'] ?? null,
                            'available_quantity' => $item['available_quantity'] ?? null,
                            'sold_quantity' => $item['sold_quantity'] ?? null,
                            'listing_type_id' => $item['listing_type_id'] ?? null,
                        ],
                    ];
                })
                ->filter(fn ($item) => $item['title'] !== '' && $item['url'] !== '')
                ->values()
                ->all();

        } catch (\Throwable $e) {
            Log::error('[ExternalProductReferenceService] Error buscando en MercadoLibre', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    protected function scoreResult(
        array $row,
        string $descripcion,
        string $unidad,
        array $strategy
    ): array {
        $titleOriginal = (string) ($row['title'] ?? '');
        $title = $this->normalize($titleOriginal);
        $query = $this->normalize($descripcion);

        $productoPrincipal = $this->normalize((string) ($strategy['producto_principal'] ?? $descripcion));

        $palabrasObligatorias = collect($strategy['palabras_obligatorias'] ?? [])
            ->map(fn ($word) => $this->normalize((string) $word))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $sinonimosValidos = collect($strategy['sinonimos_validos'] ?? [])
            ->map(fn ($word) => $this->normalize((string) $word))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $palabrasProhibidas = collect($strategy['palabras_prohibidas'] ?? [])
            ->map(fn ($word) => $this->normalize((string) $word))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $score = 0;
        $matched = [];
        $missing = [];
        $forbiddenHits = [];

        foreach ($palabrasProhibidas as $badWord) {
            if ($badWord !== '' && $this->containsTextOrWords($title, $badWord)) {
                $forbiddenHits[] = $badWord;
            }
        }

        if (!empty($forbiddenHits)) {
            return [
                'source' => $row['source'] ?? 'Internet',
                'title' => $titleOriginal,
                'seller' => $row['seller'] ?? null,
                'price' => $row['price'] ?? null,
                'currency' => $row['currency'] ?? 'MXN',
                'url' => $row['url'] ?? '',
                'score' => 0,
                'strategy_ok' => false,
                'family_ok' => false,
                'family' => $strategy['categoria_probable'] ?? null,
                'matched_tokens' => [],
                'missing_tokens' => $palabrasObligatorias,
                'forbidden_hits' => $forbiddenHits,
                'thumbnail' => $row['thumbnail'] ?? null,
                'condition' => $row['condition'] ?? null,
                'raw' => [
                    'reason' => 'Descartado por palabras prohibidas generadas por IA.',
                    'search_query' => $row['search_query'] ?? null,
                    'strategy' => $strategy,
                    'original_raw' => $row['raw'] ?? null,
                ],
                'unidad_coincide' => false,
            ];
        }

        if ($productoPrincipal !== '' && $this->containsPhraseLoose($title, $productoPrincipal)) {
            $score += 35;
            $matched[] = $productoPrincipal;
        }

        foreach ($palabrasObligatorias as $word) {
            if ($word === '') {
                continue;
            }

            if ($this->containsTextOrWords($title, $word)) {
                $matched[] = $word;
                $score += 16;
            } else {
                $missing[] = $word;
            }
        }

        foreach ($sinonimosValidos as $synonym) {
            if ($synonym === '') {
                continue;
            }

            if ($this->containsTextOrWords($title, $synonym)) {
                $matched[] = $synonym;
                $score += 12;
            }
        }

        foreach ($this->extractUsefulWords($descripcion) as $word) {
            if ($this->containsTextOrWords($title, $word)) {
                $matched[] = $word;
                $score += 5;
            }
        }

        $requiredCount = max(count($palabrasObligatorias), 1);
        $requiredMatched = count(array_intersect(
            array_map(fn ($v) => $this->normalize($v), $matched),
            $palabrasObligatorias
        ));

        $coverage = $requiredMatched / $requiredCount;

        if ($coverage >= 0.85) {
            $score += 25;
        } elseif ($coverage >= 0.65) {
            $score += 15;
        } elseif ($coverage >= 0.40) {
            $score += 6;
        } else {
            $score -= 35;
        }

        similar_text($query, $title, $similarity);
        $score += min((float) $similarity * 0.18, 14);

        if (!empty($row['price'])) {
            $score += 4;
        }

        $unidadNorm = $this->normalize($unidad);
        $unidadCoincide = false;

        if ($unidadNorm !== '' && $this->containsTextOrWords($title, $unidadNorm)) {
            $unidadCoincide = true;
            $score += 3;
        }

        $score = round(max(min($score, 100), 0), 2);

        $strategyOk = true;

        if (count($palabrasObligatorias) >= 2 && $coverage < 0.35 && empty($sinonimosValidos)) {
            $strategyOk = false;
        }

        if ($score < 55) {
            $strategyOk = false;
        }

        return [
            'source' => $row['source'] ?? 'Internet',
            'title' => $titleOriginal,
            'seller' => $row['seller'] ?? null,
            'price' => $row['price'] ?? null,
            'currency' => $row['currency'] ?? 'MXN',
            'url' => $row['url'] ?? '',
            'score' => $score,
            'strategy_ok' => $strategyOk,
            'family_ok' => $strategyOk,
            'family' => $strategy['categoria_probable'] ?? null,
            'matched_tokens' => array_values(array_unique($matched)),
            'missing_tokens' => array_values(array_unique($missing)),
            'forbidden_hits' => [],
            'thumbnail' => $row['thumbnail'] ?? null,
            'condition' => $row['condition'] ?? null,
            'raw' => [
                'search_query' => $row['search_query'] ?? null,
                'strategy' => $strategy,
                'coverage' => $coverage,
                'similarity' => $similarity,
                'original_raw' => $row['raw'] ?? null,
            ],
            'unidad_coincide' => $unidadCoincide,
        ];
    }

    protected function fallbackReferenceLinks(
        string $descripcion,
        string $unidad,
        mixed $cantidadMinima,
        mixed $cantidadMaxima,
        mixed $cantidadCotizada,
        array $strategy,
        int $limit = 7
    ): array {
        $queries = collect($strategy['queries_compra'] ?? [])
            ->map(fn ($query) => trim((string) $query))
            ->filter()
            ->unique()
            ->values();

        if ($queries->isEmpty()) {
            $base = trim(($strategy['producto_principal'] ?? $descripcion) . ' ' . $unidad);

            $queries = collect([
                trim($base . ' comprar México'),
                trim($base . ' proveedor México'),
                trim($base . ' precio México'),
            ]);
        }

        while ($queries->count() < $limit) {
            $base = trim((string) ($strategy['producto_principal'] ?? $descripcion));

            $queries->push(match ($queries->count()) {
                0 => $base . ' comprar México',
                1 => $base . ' proveedor México',
                2 => $base . ' precio México',
                3 => $base . ' tienda en línea México',
                4 => $base . ' distribuidor México',
                5 => $base . ' mayoreo México',
                default => $base . ' cotizar México',
            });

            $queries = $queries->unique()->values();
        }

        $cantidad = $cantidadCotizada ?: $cantidadMaxima ?: $cantidadMinima;

        $sources = [
            ['source' => 'Mercado Libre', 'score' => 90, 'url_type' => 'mercadolibre', 'prefix' => 'Buscar en Mercado Libre'],
            ['source' => 'Google Shopping', 'score' => 86, 'url_type' => 'google_shopping', 'prefix' => 'Buscar en Google Shopping'],
            ['source' => 'Amazon México', 'score' => 84, 'url_type' => 'amazon', 'prefix' => 'Buscar en Amazon México'],
            ['source' => 'Walmart México', 'score' => 82, 'url_type' => 'walmart', 'prefix' => 'Buscar en Walmart México'],
            ['source' => 'Office Depot', 'score' => 80, 'url_type' => 'office_depot_google', 'prefix' => 'Buscar en Office Depot'],
            ['source' => 'Pedidos.com', 'score' => 78, 'url_type' => 'pedidos_google', 'prefix' => 'Buscar en Pedidos.com'],
            ['source' => 'DC Mayorista', 'score' => 76, 'url_type' => 'dcmayorista_google', 'prefix' => 'Buscar en DC Mayorista'],
            ['source' => 'Tony Superpapelerías', 'score' => 74, 'url_type' => 'tony_google', 'prefix' => 'Buscar en Tony Superpapelerías'],
            ['source' => 'Marchand', 'score' => 72, 'url_type' => 'marchand_google', 'prefix' => 'Buscar en Marchand'],
            ['source' => 'Chedraui', 'score' => 70, 'url_type' => 'chedraui_google', 'prefix' => 'Buscar en Chedraui'],
            ['source' => 'Proveedores México', 'score' => 68, 'url_type' => 'proveedores_google', 'prefix' => 'Buscar proveedores alternos'],
        ];

        return collect($sources)
            ->take($limit)
            ->map(function ($sourceConfig, $index) use ($queries, $strategy, $descripcion, $unidad, $cantidad) {
                $query = $queries->get($index) ?: $queries->first();
                $query = trim((string) $query);

                if ($cantidad && is_numeric($cantidad) && (float) $cantidad > 1 && !str_contains($query, (string) ((int) $cantidad))) {
                    $query .= ' ' . (int) $cantidad;
                }

                if ($unidad !== '' && !str_contains(Str::lower($query), Str::lower($unidad))) {
                    $query .= ' ' . $unidad;
                }

                $query = trim($query);

                return [
                    'source' => $sourceConfig['source'],
                    'title' => $sourceConfig['prefix'] . ': ' . $query,
                    'seller' => null,
                    'price' => null,
                    'currency' => 'MXN',
                    'url' => $this->buildSearchUrl($sourceConfig['url_type'], $query),
                    'score' => $sourceConfig['score'],
                    'strategy_ok' => true,
                    'family_ok' => true,
                    'family' => $strategy['categoria_probable'] ?? null,
                    'matched_tokens' => $strategy['palabras_obligatorias'] ?? [],
                    'missing_tokens' => [],
                    'forbidden_hits' => [],
                    'thumbnail' => null,
                    'condition' => null,
                    'raw' => [
                        'fallback' => true,
                        'reason' => 'No hubo links directos confiables desde OpenAI web search o Mercado Libre. Se generó link de búsqueda inteligente por tienda.',
                        'query' => $query,
                        'cantidad' => $cantidad,
                        'unidad' => $unidad,
                        'strategy' => $strategy,
                        'descripcion_original' => $descripcion,
                    ],
                    'unidad_coincide' => $unidad !== '',
                ];
            })
            ->values()
            ->all();
    }

    protected function buildSearchUrl(string $type, string $query): string
    {
        $query = trim($query);

        return match ($type) {
            'mercadolibre' => 'https://listado.mercadolibre.com.mx/' . str_replace('+', '-', urlencode($query)),
            'google_shopping' => 'https://www.google.com/search?tbm=shop&q=' . urlencode($query),
            'amazon' => 'https://www.amazon.com.mx/s?k=' . urlencode($query),
            'walmart' => 'https://www.walmart.com.mx/search?q=' . urlencode($query),
            'office_depot_google' => 'https://www.google.com/search?q=' . urlencode('site:officedepot.com.mx ' . $query),
            'pedidos_google' => 'https://www.google.com/search?q=' . urlencode('site:pedidos.com ' . $query),
            'dcmayorista_google' => 'https://www.google.com/search?q=' . urlencode('site:dcmayorista.com.mx ' . $query),
            'tony_google' => 'https://www.google.com/search?q=' . urlencode('site:tony.com.mx ' . $query),
            'marchand_google' => 'https://www.google.com/search?q=' . urlencode('site:marchand.com.mx ' . $query),
            'chedraui_google' => 'https://www.google.com/search?q=' . urlencode('site:chedraui.com.mx ' . $query),
            'proveedores_google' => 'https://www.google.com/search?q=' . urlencode($query . ' proveedor distribuidor mayoreo México'),
            default => 'https://www.google.com/search?q=' . urlencode($query),
        };
    }

    protected function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);

        if (!$parts || empty($parts['host'])) {
            return $url;
        }

        return strtolower(($parts['scheme'] ?? 'https') . '://' . $parts['host'] . ($parts['path'] ?? ''));
    }

    protected function containsTextOrWords(string $haystack, string $needle): bool
    {
        $haystack = $this->normalize($haystack);
        $needle = $this->normalize($needle);

        if ($needle === '') {
            return false;
        }

        if (str_contains($haystack, $needle)) {
            return true;
        }

        $words = collect(preg_split('/\s+/', $needle))
            ->map(fn ($word) => trim((string) $word))
            ->filter(fn ($word) => $word !== '' && mb_strlen($word) >= 3)
            ->values();

        if ($words->isEmpty()) {
            return false;
        }

        $matched = 0;

        foreach ($words as $word) {
            if ($this->wordIn($haystack, $word)) {
                $matched++;
            }
        }

        return $matched >= max(1, (int) ceil($words->count() * 0.65));
    }

    protected function containsPhraseLoose(string $haystack, string $phrase): bool
    {
        $haystack = $this->normalize($haystack);
        $phrase = $this->normalize($phrase);

        if ($phrase === '') {
            return false;
        }

        if (str_contains($haystack, $phrase)) {
            return true;
        }

        $phraseWords = collect(preg_split('/\s+/', $phrase))
            ->map(fn ($word) => trim((string) $word))
            ->filter(fn ($word) => $word !== '' && mb_strlen($word) >= 3)
            ->values();

        if ($phraseWords->isEmpty()) {
            return false;
        }

        $matched = 0;

        foreach ($phraseWords as $word) {
            if ($this->wordIn($haystack, $word)) {
                $matched++;
            }
        }

        return $matched >= max(1, (int) ceil($phraseWords->count() * 0.70));
    }

    protected function wordIn(string $haystack, string $needle): bool
    {
        $haystack = ' ' . $this->normalize($haystack) . ' ';
        $needle = $this->normalize($needle);

        if ($needle === '') {
            return false;
        }

        foreach ($this->tokenVariants($needle) as $variant) {
            if ($variant === '') {
                continue;
            }

            if (preg_match('/\b' . preg_quote($variant, '/') . '\b/u', $haystack)) {
                return true;
            }
        }

        return false;
    }

    protected function tokenVariants(string $token): array
    {
        $token = $this->normalize($token);
        $singular = $this->singularize($token);

        $variants = [
            $token,
            $singular,
            $singular . 's',
            $singular . 'es',
        ];

        if (Str::endsWith($singular, 'z')) {
            $variants[] = mb_substr($singular, 0, -1) . 'ces';
        }

        return array_values(array_unique(array_filter($variants)));
    }

    protected function singularize(string $token): string
    {
        $token = trim($token);

        if (mb_strlen($token) <= 4) {
            return $token;
        }

        if (Str::endsWith($token, 'ces')) {
            return mb_substr($token, 0, -3) . 'z';
        }

        if (Str::endsWith($token, 'es')) {
            return mb_substr($token, 0, -2);
        }

        if (Str::endsWith($token, 's')) {
            return mb_substr($token, 0, -1);
        }

        return $token;
    }

    protected function extractUsefulWords(string $text): array
    {
        $text = $this->normalize($text);

        $stopWords = collect([
            'con', 'sin', 'para', 'por', 'del', 'las', 'los', 'una', 'uno', 'unos', 'unas',
            'pieza', 'piezas', 'pza', 'pzas', 'caja', 'cajas', 'paquete', 'paquetes',
            'presentacion', 'presentaciones', 'color', 'colores', 'tipo', 'diseno', 'diseño',
            'estandar', 'standard', 'medida', 'medidas', 'marca', 'modelo', 'material',
            'producto', 'productos', 'solicitado', 'solicitada', 'suministro', 'servicio',
            'incluye', 'incluido', 'tamano', 'tamaño', 'grande', 'chico', 'mediano',
            'capacidad', 'compacta', 'ligera', 'rigida', 'rigidas', 'base', 'alta', 'baja',
            'rapido', 'rapida', 'cm', 'mm', 'kg', 'gr', 'gramos', 'diametro',
        ]);

        return collect(preg_split('/\s+/', $text))
            ->map(fn ($word) => trim((string) $word))
            ->filter(fn ($word) => $word !== '' && mb_strlen($word) >= 3)
            ->reject(fn ($word) => is_numeric($word))
            ->reject(fn ($word) => $stopWords->contains($word))
            ->unique()
            ->values()
            ->all();
    }

    protected function normalize(string $text): string
    {
        $text = Str::lower($text);

        $text = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $text
        );

        $text = str_replace(['wire-o', 'post-it'], ['wireo', 'postit'], $text);
        $text = preg_replace('/[^a-z0-9\s\/\.\-]+/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }
}