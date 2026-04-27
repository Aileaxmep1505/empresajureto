<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExternalProductReferenceService
{
    public function searchTop3(
        string $descripcion,
        string $unidad = '',
        mixed $cantidadMinima = null,
        mixed $cantidadMaxima = null,
        mixed $cantidadCotizada = null
    ): array {
        $descripcion = trim($descripcion);

        if ($descripcion === '') {
            return [];
        }

        $query = $this->buildQuery(
            descripcion: $descripcion,
            unidad: $unidad,
            cantidadMinima: $cantidadMinima,
            cantidadMaxima: $cantidadMaxima,
            cantidadCotizada: $cantidadCotizada,
        );

        $rawResults = $this->searchMercadoLibre($query);

        if (empty($rawResults)) {
            return [];
        }

        $family = $this->detectFamily($descripcion);

        return collect($rawResults)
            ->map(fn ($row) => $this->scoreResult($row, $descripcion, $unidad, $family))
            ->filter(fn ($row) => $row['score'] >= 55 && $row['family_ok'] === true)
            ->sortByDesc('score')
            ->take(3)
            ->values()
            ->all();
    }

    protected function searchMercadoLibre(string $query): array
    {
        try {
            $response = Http::timeout(15)
                ->retry(2, 400)
                ->acceptJson()
                ->get('https://api.mercadolibre.com/sites/MLM/search', [
                    'q' => $query,
                    'limit' => 30,
                ]);

            if (! $response->successful()) {
                Log::warning('[ExternalProductReferenceService] MercadoLibre HTTP error', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                    'query' => $query,
                ]);

                return [];
            }

            $json = $response->json();

            return collect($json['results'] ?? [])
                ->map(function ($item) {
                    return [
                        'source' => 'Mercado Libre',
                        'title' => trim((string) ($item['title'] ?? '')),
                        'seller' => data_get($item, 'seller.nickname'),
                        'price' => isset($item['price']) ? (float) $item['price'] : null,
                        'currency' => $item['currency_id'] ?? 'MXN',
                        'url' => (string) ($item['permalink'] ?? ''),
                        'condition' => $item['condition'] ?? null,
                        'thumbnail' => $item['thumbnail'] ?? null,
                        'raw' => [
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

    protected function buildQuery(
        string $descripcion,
        string $unidad = '',
        mixed $cantidadMinima = null,
        mixed $cantidadMaxima = null,
        mixed $cantidadCotizada = null
    ): string {
        $text = $this->normalize($descripcion);
        $tokens = $this->extractTokens($text);
        $family = $this->detectFamily($descripcion);

        $important = collect();

        if ($family) {
            foreach ($family['must_query'] as $word) {
                if ($this->containsLike($text, $word) || $this->isVeryImportantFamilyWord($family['name'], $word)) {
                    $important->push($word);
                }
            }
        }

        foreach ($tokens as $token) {
            $important->push($token);
        }

        $unidadNorm = $this->normalize($unidad);

        if ($unidadNorm !== '' && ! in_array($unidadNorm, ['pieza', 'piezas', 'pza', 'pzas'], true)) {
            $important->push($unidadNorm);
        }

        $cantidad = $cantidadCotizada ?: $cantidadMaxima ?: $cantidadMinima;

        if ($cantidad && is_numeric($cantidad) && (float) $cantidad > 1) {
            $important->push((string) ((int) $cantidad));
        }

        return $important
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->take(10)
            ->implode(' ');
    }

    protected function scoreResult(array $row, string $descripcion, string $unidad, ?array $family): array
    {
        $title = $this->normalize((string) ($row['title'] ?? ''));
        $query = $this->normalize($descripcion);
        $tokens = $this->extractTokens($query);

        $score = 0;
        $matched = [];
        $missing = [];

        $familyOk = true;
        $familyName = null;

        if ($family) {
            $familyOk = false;
            $familyName = $family['name'];

            $positiveHits = 0;
            $negativeHits = 0;

            foreach ($family['positive'] as $word) {
                if ($this->containsLike($title, $word)) {
                    $positiveHits++;
                }
            }

            foreach ($family['negative'] as $word) {
                if ($this->containsLike($title, $word)) {
                    $negativeHits++;
                }
            }

            if ($positiveHits > 0 && $negativeHits === 0) {
                $familyOk = true;
                $score += 50 + min($positiveHits * 10, 30);
            }
        }

        foreach ($tokens as $token) {
            if ($this->containsLike($title, $token)) {
                $matched[] = $token;
                $score += 10;
            } else {
                $missing[] = $token;
            }
        }

        $coverage = count($tokens) > 0
            ? count(array_unique($matched)) / max(count($tokens), 1)
            : 0;

        if ($coverage >= 0.85) {
            $score += 18;
        } elseif ($coverage >= 0.65) {
            $score += 12;
        } elseif ($coverage >= 0.45) {
            $score += 6;
        } else {
            $score -= 25;
        }

        if ($this->containsPhrase($title, $query)) {
            $score += 20;
        }

        similar_text($query, $title, $similarity);
        $score += min((float) $similarity * 0.18, 14);

        $unidadNorm = $this->normalize($unidad);
        $unidadCoincide = false;

        if ($unidadNorm !== '') {
            $unidadCoincide = $this->containsLike($title, $unidadNorm);

            if ($unidadCoincide) {
                $score += 3;
            }
        }

        $score = round(max(min($score, 100), 0), 2);

        return [
            'source' => $row['source'] ?? 'Internet',
            'title' => $row['title'] ?? '',
            'seller' => $row['seller'] ?? null,
            'price' => $row['price'] ?? null,
            'currency' => $row['currency'] ?? 'MXN',
            'url' => $row['url'] ?? '',
            'score' => $score,
            'family_ok' => $familyOk,
            'family' => $familyName,
            'matched_tokens' => array_values(array_unique($matched)),
            'missing_tokens' => array_values(array_unique($missing)),
            'thumbnail' => $row['thumbnail'] ?? null,
            'condition' => $row['condition'] ?? null,
            'raw' => $row['raw'] ?? null,
            'unidad_coincide' => $unidadCoincide,
        ];
    }

    protected function detectFamily(string $description): ?array
    {
        $text = $this->normalize($description);

        foreach ($this->families() as $family) {
            foreach ($family['triggers'] as $trigger) {
                if ($this->containsLike($text, $trigger) || str_contains($text, $this->normalize($trigger))) {
                    return $family;
                }
            }
        }

        return null;
    }

    protected function families(): array
    {
        return [
            [
                'name' => 'arillo_encuadernacion',
                'triggers' => ['arillo', 'arillos', 'anillo', 'anillos', 'aro', 'aros', 'espiral', 'wire', 'wireo', 'wire-o', 'engargolar', 'encuadernar', 'encuadernacion'],
                'must_query' => ['arillo', 'wireo', 'encuadernacion', 'encuadernar', 'metalico'],
                'positive' => ['arillo', 'arillos', 'anillo', 'anillos', 'aro', 'aros', 'espiral', 'wire', 'wireo', 'wire-o', 'engargolar', 'encuadernar', 'encuadernacion'],
                'negative' => ['engrapadora', 'grapa', 'grapas', 'perforadora', 'perforador', 'cuaderno', 'libreta', 'lapiz', 'pluma', 'boligrafo', 'marcador', 'silicon', 'pegamento', 'folder', 'carpeta'],
            ],
            [
                'name' => 'banderitas_adhesivas',
                'triggers' => ['banderita', 'banderitas', 'senalador', 'senaladores', 'separador', 'separadores', 'postit', 'post-it', 'post it', 'nota adhesiva', 'notas adhesivas'],
                'must_query' => ['banderitas', 'adhesivas', 'senaladores', 'postit'],
                'positive' => ['banderita', 'banderitas', 'senalador', 'senaladores', 'separador', 'separadores', 'postit', 'post-it', 'nota', 'notas', 'adhesiva', 'adhesivas', 'pagina', 'paginas'],
                'negative' => ['silicon', 'silicón', 'pegamento', 'resistol', 'lapiz', 'pluma', 'boligrafo', 'cartulina', 'cuaderno', 'engrapadora', 'perforadora'],
            ],
            [
                'name' => 'cartulina_papel',
                'triggers' => ['cartulina', 'cartulinas', 'opalina', 'opalinas', 'papel', 'hoja', 'hojas'],
                'must_query' => ['cartulina', 'opalina', 'papel'],
                'positive' => ['cartulina', 'cartulinas', 'opalina', 'opalinas', 'papel', 'hoja', 'hojas', 'carta', 'oficio', 'blanca', 'blanco'],
                'negative' => ['lapiz', 'pluma', 'boligrafo', 'marcador', 'silicon', 'pegamento', 'engrapadora', 'perforadora'],
            ],
            [
                'name' => 'engrapadora_grapas',
                'triggers' => ['engrapadora', 'engrapadoras', 'grapa', 'grapas', 'engrapar'],
                'must_query' => ['engrapadora', 'grapas'],
                'positive' => ['engrapadora', 'engrapadoras', 'grapa', 'grapas', 'engrapar'],
                'negative' => ['arillo', 'encuadernacion', 'espiral', 'wire', 'cartulina', 'banderita', 'silicon'],
            ],
            [
                'name' => 'perforadora',
                'triggers' => ['perforadora', 'perforador', 'perforar'],
                'must_query' => ['perforadora', 'perforador'],
                'positive' => ['perforadora', 'perforador', 'perforar', 'orificio', 'orificios'],
                'negative' => ['arillo', 'encuadernacion', 'espiral', 'wire', 'cartulina', 'banderita', 'silicon'],
            ],
            [
                'name' => 'silicon_pegamento',
                'triggers' => ['silicon', 'silicón', 'pegamento', 'adhesivo liquido', 'resistol'],
                'must_query' => ['silicon', 'pegamento', 'adhesivo'],
                'positive' => ['silicon', 'silicón', 'pegamento', 'adhesivo', 'liquido', 'resistol'],
                'negative' => ['banderita', 'senalador', 'cartulina', 'arillo', 'espiral', 'cuaderno', 'lapiz', 'pluma'],
            ],
            [
                'name' => 'lapiz_pluma_marcador',
                'triggers' => ['lapiz', 'lapices', 'pluma', 'plumas', 'boligrafo', 'boligrafos', 'marcador', 'marcadores'],
                'must_query' => ['lapiz', 'pluma', 'boligrafo', 'marcador'],
                'positive' => ['lapiz', 'lapices', 'pluma', 'plumas', 'boligrafo', 'boligrafos', 'marcador', 'marcadores'],
                'negative' => ['cartulina', 'opalina', 'hoja', 'papel', 'arillo', 'banderita', 'silicon', 'engrapadora', 'perforadora'],
            ],
            [
                'name' => 'calculadora',
                'triggers' => ['calculadora', 'calculadoras'],
                'must_query' => ['calculadora'],
                'positive' => ['calculadora', 'calculadoras', 'cientifica', 'basica', 'digitos'],
                'negative' => ['despachador', 'cinta', 'lapiz', 'pluma', 'cartulina', 'cuaderno', 'marcador'],
            ],
            [
                'name' => 'folder_carpeta',
                'triggers' => ['folder', 'folders', 'carpeta', 'carpetas'],
                'must_query' => ['folder', 'carpeta'],
                'positive' => ['folder', 'folders', 'carpeta', 'carpetas', 'expediente', 'broche'],
                'negative' => ['cartulina', 'opalina', 'lapiz', 'pluma', 'arillo', 'banderita', 'silicon'],
            ],
        ];
    }

    protected function isVeryImportantFamilyWord(string $familyName, string $word): bool
    {
        $mainWords = [
            'arillo_encuadernacion' => ['arillo', 'wireo', 'encuadernacion'],
            'banderitas_adhesivas' => ['banderitas', 'senaladores', 'postit'],
            'cartulina_papel' => ['cartulina', 'opalina'],
            'engrapadora_grapas' => ['engrapadora'],
            'perforadora' => ['perforadora'],
            'silicon_pegamento' => ['silicon', 'pegamento'],
            'lapiz_pluma_marcador' => ['lapiz', 'pluma', 'boligrafo', 'marcador'],
            'calculadora' => ['calculadora'],
            'folder_carpeta' => ['folder', 'carpeta'],
        ];

        return in_array($word, $mainWords[$familyName] ?? [], true);
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

        $text = preg_replace('/[^a-z0-9\s\/]+/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    protected function extractTokens(string $text): array
    {
        $stopWords = collect([
            'con', 'sin', 'para', 'por', 'del', 'las', 'los', 'una', 'uno', 'unos', 'unas',
            'pieza', 'piezas', 'pza', 'pzas', 'caja', 'cajas', 'paquete', 'paquetes',
            'presentacion', 'presentaciones', 'color', 'colores', 'tipo', 'diseno', 'diseño',
            'estandar', 'standard', 'medida', 'medidas', 'marca', 'modelo', 'material',
            'producto', 'productos', 'solicitado', 'solicitada', 'suministro', 'servicio',
            'incluye', 'incluido', 'tamano', 'tamaño', 'grande', 'chico', 'mediano',
            'capacidad', 'compacta', 'ligera', 'rigida', 'rigidas', 'base', 'alta', 'baja',
            'rapido', 'rapida', 'cm', 'mm', 'kg', 'gr', 'gramos',
        ]);

        return collect(preg_split('/\s+/', $text))
            ->map(fn ($token) => trim((string) $token))
            ->filter(fn ($token) => $token !== '' && mb_strlen($token) >= 3)
            ->reject(fn ($token) => is_numeric($token))
            ->reject(fn ($token) => $stopWords->contains($token))
            ->map(fn ($token) => $this->singularize($token))
            ->unique()
            ->values()
            ->all();
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

    protected function containsLike(string $haystack, string $needle): bool
    {
        $haystack = ' ' . $this->normalize($haystack) . ' ';

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

    protected function containsPhrase(string $haystack, string $phrase): bool
    {
        $haystack = $this->normalize($haystack);
        $phrase = $this->normalize($phrase);

        return $phrase !== '' && str_contains($haystack, $phrase);
    }
}