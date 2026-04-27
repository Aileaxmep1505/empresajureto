<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductSearchStrategyService
{
    public function build(
        string $descripcion,
        string $unidad = '',
        mixed $cantidadMinima = null,
        mixed $cantidadMaxima = null,
        mixed $cantidadCotizada = null
    ): array {
        $descripcion = trim($descripcion);

        if ($descripcion === '') {
            return $this->fallbackStrategy(
                descripcion: '',
                unidad: $unidad,
                cantidadMinima: $cantidadMinima,
                cantidadMaxima: $cantidadMaxima,
                cantidadCotizada: $cantidadCotizada
            );
        }

        $apiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');

        if (!$apiKey) {
            return $this->fallbackStrategy(
                descripcion: $descripcion,
                unidad: $unidad,
                cantidadMinima: $cantidadMinima,
                cantidadMaxima: $cantidadMaxima,
                cantidadCotizada: $cantidadCotizada
            );
        }

        try {
            $response = Http::timeout(35)
                ->retry(1, 500)
                ->withToken($apiKey)
                ->acceptJson()
                ->post('https://api.openai.com/v1/responses', [
                    'model' => env('OPENAI_MATCH_MODEL', 'gpt-4o-mini'),
                    'input' => [
                        [
                            'role' => 'system',
                            'content' => [
                                [
                                    'type' => 'input_text',
                                    'text' => 'Eres un experto comprador B2B, analista de catálogo, normalizador de productos y especialista en búsqueda de productos para cotizaciones empresariales y gubernamentales en México. Debes devolver únicamente JSON válido y cumplir estrictamente el esquema solicitado.',
                                ],
                            ],
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'input_text',
                                    'text' => $this->prompt(
                                        descripcion: $descripcion,
                                        unidad: $unidad,
                                        cantidadMinima: $cantidadMinima,
                                        cantidadMaxima: $cantidadMaxima,
                                        cantidadCotizada: $cantidadCotizada
                                    ),
                                ],
                            ],
                        ],
                    ],
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'product_search_strategy',
                            'strict' => true,
                            'schema' => $this->jsonSchema(),
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                Log::warning('[ProductSearchStrategyService] OpenAI HTTP error', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 1500),
                ]);

                return $this->fallbackStrategy(
                    descripcion: $descripcion,
                    unidad: $unidad,
                    cantidadMinima: $cantidadMinima,
                    cantidadMaxima: $cantidadMaxima,
                    cantidadCotizada: $cantidadCotizada
                );
            }

            $json = $response->json();

            $text = data_get($json, 'output.0.content.0.text');

            if (!$text) {
                $text = data_get($json, 'output_text');
            }

            if (!$text && isset($json['output']) && is_array($json['output'])) {
                foreach ($json['output'] as $output) {
                    foreach (($output['content'] ?? []) as $content) {
                        if (($content['type'] ?? null) === 'output_text' && !empty($content['text'])) {
                            $text = $content['text'];
                            break 2;
                        }
                    }
                }
            }

            $strategy = json_decode((string) $text, true);

            if (!is_array($strategy)) {
                Log::warning('[ProductSearchStrategyService] Respuesta no JSON', [
                    'raw' => mb_substr((string) $text, 0, 1500),
                ]);

                return $this->fallbackStrategy(
                    descripcion: $descripcion,
                    unidad: $unidad,
                    cantidadMinima: $cantidadMinima,
                    cantidadMaxima: $cantidadMaxima,
                    cantidadCotizada: $cantidadCotizada
                );
            }

            return $this->sanitizeStrategy($strategy, $descripcion, $unidad, $cantidadMinima, $cantidadMaxima, $cantidadCotizada);

        } catch (\Throwable $e) {
            Log::error('[ProductSearchStrategyService] Error generando estrategia', [
                'descripcion' => $descripcion,
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackStrategy(
                descripcion: $descripcion,
                unidad: $unidad,
                cantidadMinima: $cantidadMinima,
                cantidadMaxima: $cantidadMaxima,
                cantidadCotizada: $cantidadCotizada
            );
        }
    }

    protected function prompt(
        string $descripcion,
        string $unidad,
        mixed $cantidadMinima,
        mixed $cantidadMaxima,
        mixed $cantidadCotizada
    ): string {
        return <<<PROMPT
Eres un experto comprador B2B, analista de catálogo y especialista en búsqueda de productos para cotizaciones gubernamentales y empresariales en México.

Tu tarea es analizar una partida solicitada y convertirla en una estrategia de búsqueda precisa, abierta y no limitada a categorías predefinidas.

No inventes productos.
No reemplaces el producto solicitado por otro de uso diferente.
No aceptes productos sustitutos si cambian la función principal.
No te bases en una lista fija de familias.
Debes entender el producto por su función, material, medidas, presentación, color, compatibilidad, capacidad y uso.

DATOS DE LA PARTIDA:

DESCRIPCIÓN:
{$descripcion}

UNIDAD:
{$unidad}

CANTIDAD MÍNIMA:
{$cantidadMinima}

CANTIDAD MÁXIMA:
{$cantidadMaxima}

CANTIDAD COTIZADA:
{$cantidadCotizada}

REGLAS IMPORTANTES:

1. Identifica el producto principal real.
2. Quita ruido de la descripción como:
   - pieza
   - caja
   - presentación
   - color
   - estándar
   - diseño
   - suministro
   - solicitado
   - partida
   - paquete
   - marca genérica si no es obligatoria

3. Conserva atributos críticos:
   - medida
   - diámetro
   - capacidad
   - voltaje
   - amperaje
   - compatibilidad
   - modelo
   - talla
   - color
   - material
   - presentación
   - cantidad por caja
   - tipo de producto

4. Las palabras obligatorias deben representar lo que sí o sí debe coincidir o ser equivalente.

5. Los sinónimos válidos deben ser equivalentes reales del mismo producto.

6. Las palabras prohibidas deben ser productos parecidos pero incorrectos.

7. Las queries_catalogo deben ser cortas y útiles para buscar en base de datos.

8. Las queries_compra deben estar listas para buscar en internet y comprar en México.

9. No limites el análisis a papelería. Puede ser:
   - ferretería
   - limpieza
   - tecnología
   - consumibles
   - mobiliario
   - equipo médico
   - refacciones
   - papelería
   - oficina
   - seguridad
   - herramientas
   - eléctricos
   - plomería
   - alimentos
   - mantenimiento
   - uniformes
   - cualquier producto B2B

10. Si la descripción es ambigua, crea queries más específicas conservando la intención de compra.

11. No devuelvas explicaciones fuera del JSON.
PROMPT;
    }

    protected function jsonSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'producto_principal' => [
                    'type' => 'string',
                    'description' => 'Nombre exacto del producto solicitado, sin cantidades ni texto de relleno.',
                ],
                'categoria_probable' => [
                    'type' => 'string',
                    'description' => 'Categoría general inferida del producto.',
                ],
                'intencion_compra' => [
                    'type' => 'string',
                    'description' => 'Qué quiere comprar realmente el cliente.',
                ],
                'funcion_principal' => [
                    'type' => 'string',
                    'description' => 'Para qué sirve el producto.',
                ],
                'atributos_clave' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'material' => ['type' => ['string', 'null']],
                        'color' => ['type' => ['string', 'null']],
                        'medida' => ['type' => ['string', 'null']],
                        'capacidad' => ['type' => ['string', 'null']],
                        'presentacion' => ['type' => ['string', 'null']],
                        'compatibilidad' => ['type' => ['string', 'null']],
                        'modelo' => ['type' => ['string', 'null']],
                        'marca_requerida' => ['type' => ['string', 'null']],
                        'otros' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                    ],
                    'required' => [
                        'material',
                        'color',
                        'medida',
                        'capacidad',
                        'presentacion',
                        'compatibilidad',
                        'modelo',
                        'marca_requerida',
                        'otros',
                    ],
                ],
                'palabras_obligatorias' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'sinonimos_validos' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'palabras_prohibidas' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'queries_catalogo' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'queries_compra' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'criterios_aceptacion' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'criterios_rechazo' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'confianza' => [
                    'type' => 'number',
                ],
            ],
            'required' => [
                'producto_principal',
                'categoria_probable',
                'intencion_compra',
                'funcion_principal',
                'atributos_clave',
                'palabras_obligatorias',
                'sinonimos_validos',
                'palabras_prohibidas',
                'queries_catalogo',
                'queries_compra',
                'criterios_aceptacion',
                'criterios_rechazo',
                'confianza',
            ],
        ];
    }

    protected function sanitizeStrategy(
        array $strategy,
        string $descripcion,
        string $unidad,
        mixed $cantidadMinima,
        mixed $cantidadMaxima,
        mixed $cantidadCotizada
    ): array {
        $productoPrincipal = trim((string) ($strategy['producto_principal'] ?? ''));

        if ($productoPrincipal === '') {
            $productoPrincipal = $descripcion;
        }

        $strategy['producto_principal'] = $productoPrincipal;
        $strategy['categoria_probable'] = trim((string) ($strategy['categoria_probable'] ?? 'general'));
        $strategy['intencion_compra'] = trim((string) ($strategy['intencion_compra'] ?? 'comprar producto solicitado'));
        $strategy['funcion_principal'] = trim((string) ($strategy['funcion_principal'] ?? 'no determinada'));
        $strategy['confianza'] = (float) ($strategy['confianza'] ?? 50);

        $strategy['atributos_clave'] = array_merge([
            'material' => null,
            'color' => null,
            'medida' => null,
            'capacidad' => null,
            'presentacion' => null,
            'compatibilidad' => null,
            'modelo' => null,
            'marca_requerida' => null,
            'otros' => [],
        ], is_array($strategy['atributos_clave'] ?? null) ? $strategy['atributos_clave'] : []);

        if (!is_array($strategy['atributos_clave']['otros'] ?? null)) {
            $strategy['atributos_clave']['otros'] = [];
        }

        foreach ([
            'palabras_obligatorias',
            'sinonimos_validos',
            'palabras_prohibidas',
            'queries_catalogo',
            'queries_compra',
            'criterios_aceptacion',
            'criterios_rechazo',
        ] as $key) {
            $strategy[$key] = collect($strategy[$key] ?? [])
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if (empty($strategy['palabras_obligatorias'])) {
            $strategy['palabras_obligatorias'] = $this->extractUsefulWords($productoPrincipal);
        }

        if (empty($strategy['queries_catalogo'])) {
            $strategy['queries_catalogo'] = [
                $productoPrincipal,
                trim($productoPrincipal . ' ' . $unidad),
            ];
        }

        if (empty($strategy['queries_compra'])) {
            $strategy['queries_compra'] = [
                trim($productoPrincipal . ' comprar México'),
                trim($productoPrincipal . ' proveedor México'),
                trim($productoPrincipal . ' precio México'),
            ];
        }

        $cantidad = $cantidadCotizada ?: $cantidadMaxima ?: $cantidadMinima;

        if ($cantidad && is_numeric($cantidad) && (float) $cantidad > 1) {
            $strategy['queries_compra'] = collect($strategy['queries_compra'])
                ->map(function ($query) use ($cantidad, $unidad) {
                    $query = trim((string) $query);

                    if (!str_contains(Str::lower($query), (string) ((int) $cantidad))) {
                        $query .= ' ' . (int) $cantidad;
                    }

                    if ($unidad !== '') {
                        $query .= ' ' . $unidad;
                    }

                    return trim($query);
                })
                ->unique()
                ->values()
                ->all();
        }

        if (empty($strategy['criterios_aceptacion'])) {
            $strategy['criterios_aceptacion'] = [
                'Debe corresponder al mismo producto solicitado.',
                'Debe conservar función principal, medida, color, material, compatibilidad y presentación cuando estén indicados.',
            ];
        }

        if (empty($strategy['criterios_rechazo'])) {
            $strategy['criterios_rechazo'] = [
                'Rechazar productos con función principal distinta.',
                'Rechazar productos que solo coincidan por material, color, presentación o cantidad.',
            ];
        }

        $strategy['_meta'] = [
            'generated_by' => 'openai_strategy',
            'descripcion_original' => $descripcion,
            'unidad' => $unidad,
            'cantidad_minima' => $cantidadMinima,
            'cantidad_maxima' => $cantidadMaxima,
            'cantidad_cotizada' => $cantidadCotizada,
        ];

        return $strategy;
    }

    protected function fallbackStrategy(
        string $descripcion,
        string $unidad,
        mixed $cantidadMinima,
        mixed $cantidadMaxima,
        mixed $cantidadCotizada
    ): array {
        $descripcion = trim($descripcion);
        $base = trim($descripcion . ' ' . $unidad);
        $usefulWords = $this->extractUsefulWords($descripcion);
        $cantidad = $cantidadCotizada ?: $cantidadMaxima ?: $cantidadMinima;

        $queryBase = $base;

        if ($cantidad && is_numeric($cantidad) && (float) $cantidad > 1) {
            $queryBase = trim($queryBase . ' ' . (int) $cantidad);
        }

        return [
            'producto_principal' => $descripcion,
            'categoria_probable' => 'general',
            'intencion_compra' => 'comprar el producto solicitado',
            'funcion_principal' => 'no determinada',
            'atributos_clave' => [
                'material' => null,
                'color' => null,
                'medida' => null,
                'capacidad' => null,
                'presentacion' => null,
                'compatibilidad' => null,
                'modelo' => null,
                'marca_requerida' => null,
                'otros' => [],
            ],
            'palabras_obligatorias' => $usefulWords,
            'sinonimos_validos' => [],
            'palabras_prohibidas' => [],
            'queries_catalogo' => [
                $descripcion,
                $base,
                implode(' ', array_slice($usefulWords, 0, 6)),
            ],
            'queries_compra' => [
                trim($queryBase . ' comprar México'),
                trim($queryBase . ' proveedor México'),
                trim($queryBase . ' precio México'),
            ],
            'criterios_aceptacion' => [
                'Debe corresponder al mismo producto solicitado.',
                'Debe conservar función principal, medida, color, material y presentación cuando estén indicados.',
            ],
            'criterios_rechazo' => [
                'Rechazar productos con función principal distinta.',
                'Rechazar productos que solo coincidan por palabras genéricas.',
            ],
            'confianza' => 50,
            '_meta' => [
                'generated_by' => 'fallback_strategy',
                'descripcion_original' => $descripcion,
                'unidad' => $unidad,
                'cantidad_minima' => $cantidadMinima,
                'cantidad_maxima' => $cantidadMaxima,
                'cantidad_cotizada' => $cantidadCotizada,
            ],
        ];
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