<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiMatchingService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;

    /** Cache en memoria para no repetir expansiones de la misma descripción. */
    protected array $expansionCache = [];

    public function __construct()
    {
        $this->apiKey  = config('services.openai.api_key');
        $this->baseUrl = rtrim(config('services.openai.base_url', 'https://api.openai.com'), '/');
        $this->model   = config('services.openai.match_model', 'gpt-5.1');
    }

    /**
     * Usa la IA para entender QUÉ es el producto y devolver sinónimos / variantes
     * comerciales con las que podría aparecer en un catálogo mexicano.
     * Ej: "bote de basura" -> ["cesto de basura", "papelera", "contenedor de basura", ...]
     *     "boligrafo"       -> ["lapicero", "pluma", "boligrafo"]
     *
     * Devuelve ['tipo' => string, 'terminos' => string[]].
     */
    public function expandSearchTerms(string $descripcion, string $unidad = ''): array
    {
        $descripcion = trim($descripcion);

        if ($descripcion === '') {
            return ['tipo' => '', 'terminos' => []];
        }

        $cacheKey = mb_strtolower($descripcion . '|' . $unidad);

        if (isset($this->expansionCache[$cacheKey])) {
            return $this->expansionCache[$cacheKey];
        }

        try {
            $clean = $this->rawCompletion([
                ['role' => 'system', 'content' => $this->expansionSystemPrompt()],
                ['role' => 'user',   'content' => $this->expansionUserPrompt($descripcion, $unidad)],
            ], 600);

            $parsed = json_decode($clean, true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($parsed)) {
                return $this->expansionCache[$cacheKey] = ['tipo' => '', 'terminos' => []];
            }

            $tipo = (string) ($parsed['tipo'] ?? '');

            $terminos = collect($parsed['terminos'] ?? $parsed['sinonimos'] ?? [])
                ->map(fn ($t) => trim((string) $t))
                ->filter(fn ($t) => $t !== '')
                ->unique()
                ->values()
                ->all();

            Log::info('[AiMatchingService] Expansión semántica', [
                'item'     => mb_substr($descripcion, 0, 80),
                'tipo'     => $tipo,
                'terminos' => $terminos,
            ]);

            return $this->expansionCache[$cacheKey] = [
                'tipo'     => $tipo,
                'terminos' => $terminos,
            ];
        } catch (\Throwable $e) {
            Log::warning('[AiMatchingService] Error en expansión semántica', [
                'error' => $e->getMessage(),
                'item'  => $descripcion,
            ]);

            return $this->expansionCache[$cacheKey] = ['tipo' => '', 'terminos' => []];
        }
    }

    public function validateCandidates(
        string $descripcionOriginal,
        string $unidadSolicitada,
        array  $candidates
    ): array {
        if (empty($candidates)) {
            return [];
        }

        $productList = [];
        foreach ($candidates as $idx => $row) {
            $p = $row['product'];

            $matchedTokens = $row['matched_tokens'] ?? [];
            $missingTokens = $row['missing_tokens'] ?? [];
            $coverage      = isset($row['coverage']) ? (int) round(((float) $row['coverage']) * 100) : null;

            $productList[] = [
                'idx'                       => $idx,
                'id'                        => $p->id,
                'nombre'                    => (string) ($p->name        ?? ''),
                'sku'                       => (string) ($p->sku         ?? ''),
                'sku_proveedor'             => (string) ($p->supplier_sku ?? ''),
                'categoria'                 => (string) ($p->category    ?? ''),
                'marca'                     => (string) ($p->brand       ?? ''),
                'modelo'                    => (string) ($p->model       ?? $p->modelo ?? ''),
                'tags'                      => (string) ($p->tags        ?? ''),
                'descripcion'               => mb_substr((string) ($p->description ?? ''), 0, 250),
                'material'                  => (string) ($p->material    ?? ''),
                'color'                     => (string) ($p->color       ?? ''),
                'unidad'                    => (string) ($p->unit        ?? $p->unidad ?? ''),

                // Pistas del análisis previo (solo orientativas; la descripción manda).
                'cobertura_descripcion_pct' => $coverage,
                'palabras_que_coinciden'    => array_values($matchedTokens),
                'palabras_faltantes'        => array_values($missingTokens),
            ];
        }

        $productJson = json_encode($productList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        try {
            $aiResults = $this->callOpenAI([
                ['role' => 'system', 'content' => $this->systemPrompt()],
                ['role' => 'user',   'content' => $this->userPrompt($descripcionOriginal, $unidadSolicitada, $productJson)],
            ]);
        } catch (\Throwable $e) {
            Log::error('[AiMatchingService] Error OpenAI', [
                'error' => $e->getMessage(),
                'item'  => $descripcionOriginal,
            ]);
            return $this->fallback($candidates);
        }

        if (empty($aiResults)) {
            Log::warning('[AiMatchingService] Sin resultados de IA, usando fallback', [
                'item' => $descripcionOriginal,
            ]);
            return $this->fallback($candidates);
        }

        Log::info('[AiMatchingService] Resultados IA', [
            'item'      => mb_substr($descripcionOriginal, 0, 80),
            'total'     => count($aiResults),
            'aprobados' => collect($aiResults)->where('aprobado', true)->count(),
            'detalle'   => collect($aiResults)->map(fn($r) => [
                'idx'      => $r['idx'] ?? '?',
                'aprobado' => $r['aprobado'] ?? false,
                'score'    => $r['score'] ?? 0,
                'razon'    => mb_substr($r['razon'] ?? '', 0, 60),
            ])->all(),
        ]);

        $aiMap    = collect($aiResults)->keyBy('idx');
        $approved = [];

        foreach ($candidates as $idx => $row) {
            $ai = $aiMap->get($idx);

            if (! $ai || ! ($ai['aprobado'] ?? false) || (int) ($ai['score'] ?? 0) < 30) {
                continue;
            }

            $approved[] = array_merge($row, [
                'ai_score' => (int)    ($ai['score'] ?? 0),
                'ai_razon' => (string) ($ai['razon'] ?? 'Aprobado'),
            ]);
        }

        return $approved;
    }

    protected function expansionSystemPrompt(): string
    {
        return <<<'SYSTEM'
Eres un comprador experto en catálogos de papelería, oficina, tecnología y artículos escolares en México.

Dada la descripción de un artículo solicitado en una licitación, identifica QUÉ ES (el tipo de producto)
y devuelve los nombres comerciales, sinónimos y variantes con los que ESE MISMO producto podría aparecer
en un catálogo mexicano.

USA SENTIDO COMÚN del rubro:
  • "bote de basura"  → cesto de basura, papelera, contenedor de basura, basurero
  • "boligrafo"       → lapicero, pluma, bolígrafo
  • "engrapadora"     → grapadora, cosedora
  • "folder"          → carpeta, fólder
  • "plumon"          → marcador, plumón, rotulador

REGLAS:
  • Incluye singular y plural cuando aplique.
  • Incluye la palabra original del usuario.
  • SOLO sinónimos del MISMO tipo de producto. NUNCA agregues productos de otro tipo
    (si piden "libreta", jamás incluyas "lapicero"; si piden "bote de basura", jamás incluyas "escoba").
  • Máximo ~12 términos, los más útiles para buscar en catálogo.

Responde ÚNICAMENTE con JSON puro, sin markdown ni texto extra.
SYSTEM;
    }

    protected function expansionUserPrompt(string $descripcion, string $unidad): string
    {
        return <<<USER
ARTÍCULO SOLICITADO:
Descripción: {$descripcion}
Unidad: {$unidad}

Responde SOLO con este JSON (sin texto extra, sin ```):
{
  "tipo": "nombre claro del tipo de producto",
  "terminos": ["sinonimo 1", "variante 2", "nombre comercial 3"]
}
USER;
    }

    protected function systemPrompt(): string
    {
        return <<<'SYSTEM'
Eres un COTIZADOR EXPERTO en material de oficina, papelería, tecnología y artículos escolares para licitaciones gubernamentales de México, con 20 años de experiencia.

Tu trabajo: analizar el artículo solicitado, extraer TODAS sus características, y evaluar cada producto candidato para decidir si se puede cotizar.

LEE LA DESCRIPCIÓN COMPLETA Y USA SENTIDO COMÚN DEL RUBRO:
  Un mismo producto tiene varios nombres comerciales. Si el TIPO es el mismo, aunque el nombre
  sea distinto, ES VÁLIDO:
    • "bote de basura" = "cesto de basura" = "papelera" = "basurero"   → mismo tipo, cotizable
    • "bolígrafo" = "lapicero" = "pluma"                                → mismo tipo, cotizable
    • "fólder" = "carpeta"                                              → mismo tipo, cotizable
  Pero el sentido común también RECHAZA lo que no es lo mismo:
    • Piden "libreta" y ofrecen "lapicero"   → tipo distinto, NO cotizable (score 0)
    • Piden "bote de basura" y ofrecen "escoba" → tipo distinto, NO cotizable (score 0)

PISTAS LÉXICAS (solo orientativas, NO decisivas):
  Cada candidato puede traer "cobertura_descripcion_pct", "palabras_que_coinciden" y
  "palabras_faltantes". Son resultados de un análisis de texto previo y solo te orientan.
  • Cobertura alta NO garantiza que sea correcto (puede ser el tipo equivocado).
  • Cobertura baja NO descarta (puede ser un sinónimo: "cesto" cuando pidieron "bote").
  Decide tú leyendo nombre, categoría, marca, modelo y descripción.

PASO 1 — DESCOMPÓN EL ARTÍCULO EN CARACTERÍSTICAS:
  • TIPO DE PRODUCTO       → ¿qué es exactamente? (incluye sus sinónimos mentales)
  • CARACTERÍSTICAS CLAVE  → gramaje, medidas, capacidades, specs técnicas
  • MATERIAL               → si se especifica
  • COLOR                  → si se especifica
  • PRESENTACIÓN           → pieza, caja c/12, paquete c/100, etc.

PASO 2 — EVALÚA CADA CANDIDATO:

REGLA ABSOLUTA — EL TIPO ES INNEGOCIABLE:
  Tipo realmente diferente → aprobado: false, score: 0.
  (Pero recuerda: un sinónimo NO es un tipo diferente. "Cesto" y "bote" de basura son el MISMO tipo.)

SI EL TIPO SÍ COINCIDE (incluyendo sinónimos) — PUNTÚA:
  Base tipo correcto                          → +50
  Característica técnica coincide exacta      → +10 c/u
  Característica técnica similar              → +5  c/u
  Material coincide                           → +8
  Color coincide exacto                       → +6
  Color parcial                               → +3
  Presentación compatible                     → +5
  Marca coincide                              → +4
  Característica diferente pero aceptable     → -5
  Característica incompatible                 → -10

UMBRALES:
  ≥ 80 → Ideal · 60-79 → Buena opción · 30-59 → Válido · < 30 → No cotizable

PRINCIPIO CLAVE:
  Si no hay producto idéntico, aprueba el que más se adapte aunque no sea igual, siempre que sea
  el MISMO tipo de producto (o un sinónimo claro). Solo rechaza cuando el tipo es realmente distinto.

RESPONDE ÚNICAMENTE con JSON puro. Sin texto adicional, sin markdown, sin explicaciones fuera del JSON.
SYSTEM;
    }

    protected function userPrompt(string $descripcion, string $unidad, string $productJson): string
    {
        return <<<USER
ARTÍCULO SOLICITADO (analiza la descripción COMPLETA y con sentido común):
Descripción: {$descripcion}
Unidad: {$unidad}

CANDIDATOS DEL CATÁLOGO:
(cada uno puede incluir "cobertura_descripcion_pct", "palabras_que_coinciden" y "palabras_faltantes" como PISTAS orientativas)
{$productJson}

Responde SOLO con este JSON (sin texto extra, sin ```, solo el JSON puro):
{
  "caracteristicas_solicitadas": {
    "tipo": "descripción específica del tipo, ej: cartulina opalina blanca",
    "tecnicas": ["gramaje 125g", "tamaño carta"],
    "material": "...",
    "color": "...",
    "presentacion": "paquete c/100 piezas"
  },
  "resultados": [
    {
      "idx": 0,
      "product_id": 123,
      "aprobado": true,
      "score": 78,
      "coincidencias": ["mismo tipo (sinónimo): piden 'bote de basura', es 'cesto de basura'"],
      "diferencias": ["capacidad 20L vs 15L solicitado"],
      "razon": "Cotizable: mismo tipo de producto bajo otro nombre comercial"
    },
    {
      "idx": 1,
      "product_id": 456,
      "aprobado": false,
      "score": 0,
      "coincidencias": [],
      "diferencias": ["tipo incorrecto: piden libreta y es un lapicero"],
      "razon": "No cotizable: tipo completamente diferente"
    }
  ]
}
USER;
    }

    protected function callOpenAI(array $messages): array
    {
        $clean = $this->rawCompletion($messages, 3000);

        $parsed = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! isset($parsed['resultados'])) {
            Log::warning('[AiMatchingService] JSON invalido', [
                'raw'   => mb_substr($clean, 0, 500),
                'error' => json_last_error_msg(),
            ]);
            return [];
        }

        if (isset($parsed['caracteristicas_solicitadas'])) {
            Log::info('[AiMatchingService] Características detectadas', $parsed['caracteristicas_solicitadas']);
        }

        return $parsed['resultados'];
    }

    /**
     * Llama a OpenAI y devuelve el contenido limpio (sin fences markdown,
     * recortado al bloque JSON). Reutilizado por validación y expansión.
     *
     * Soporta dos familias:
     *  - gpt-5.x (razonamiento): sin temperature, con max_completion_tokens + reasoning_effort.
     *  - gpt-4.x: con temperature 0 y max_tokens.
     */
    protected function rawCompletion(array $messages, int $maxTokens = 3000): string
    {
        $isGpt5 = str_starts_with($this->model, 'gpt-5');

        $payload = [
            'model'    => $this->model,
            'messages' => $messages,
        ];

        if ($isGpt5) {
            // Holgura para el razonamiento interno y esfuerzo bajo para que sea rápido.
            $payload['max_completion_tokens'] = max($maxTokens * 2, 5000);
            $payload['reasoning_effort'] = 'low';
        } else {
            $payload['temperature'] = 0;
            $payload['max_tokens']  = $maxTokens;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])
        ->timeout(60)
        ->post($this->baseUrl . '/v1/chat/completions', $payload);

        if (! $response->successful()) {
            Log::warning('[AiMatchingService] OpenAI HTTP error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('OpenAI HTTP ' . $response->status());
        }

        $raw = $response->json('choices.0.message.content', '{}');

        $clean = preg_replace('/```json\s*/i', '', $raw);
        $clean = preg_replace('/```\s*/', '', $clean);
        $clean = trim($clean);

        if (! str_starts_with($clean, '{')) {
            preg_match('/\{.+\}/s', $clean, $matches);
            $clean = $matches[0] ?? '{}';
        }

        return $clean;
    }

    protected function fallback(array $candidates): array
    {
        return collect($candidates)
            ->filter(fn ($row) => (float) ($row['score'] ?? 0) >= 65)
            ->map(fn ($row) => array_merge($row, [
                'ai_score' => (int) ($row['score'] ?? 0),
                'ai_razon' => 'Coincidencia léxica alta (IA no disponible)',
            ]))
            ->sortByDesc('ai_score')
            ->values()
            ->all();
    }
}