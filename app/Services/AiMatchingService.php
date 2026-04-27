<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de validación semántica de productos para matching de licitaciones.
 *
 * Usa OpenAI para determinar si cada producto candidato es del mismo
 * tipo funcional que el artículo solicitado, evitando falsos positivos
 * por coincidencias numéricas o léxicas superficiales.
 */
class AiMatchingService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $model;
    protected int    $timeout;

    public function __construct()
    {
        $this->apiKey  = config('services.openai.api_key');
        $this->baseUrl = rtrim(config('services.openai.base_url', 'https://api.openai.com'), '/');
        $this->model   = config('services.openai.json_repair_model', 'gpt-4o-mini'); // modelo barato y rápido
        $this->timeout = 30;
    }

    // =========================================================
    //  MÉTODO PRINCIPAL
    // =========================================================

    /**
     * Recibe la descripción del ítem solicitado y una colección de candidatos
     * pre-filtrados por score léxico, y retorna solo los que la IA aprueba.
     *
     * @param  string $descripcionOriginal  Descripción del ítem de la licitación
     * @param  string $unidadSolicitada     Unidad del ítem (PIEZA, CAJA, etc.)
     * @param  array  $candidates           Array de ['product' => Product, 'score' => float, ...]
     * @return array  Mismo formato de $candidates, enriquecido con 'ai_score' y 'ai_razon'
     */
    public function validateCandidates(
        string $descripcionOriginal,
        string $unidadSolicitada,
        array $candidates
    ): array {
        if (empty($candidates)) {
            return [];
        }

        // Preparar listado compacto para el prompt
        $productList = [];
        foreach ($candidates as $idx => $row) {
            $p = $row['product'];
            $productList[] = [
                'idx'         => $idx,
                'id'          => $p->id,
                'nombre'      => (string) $p->name,
                'sku'         => (string) ($p->sku ?? ''),
                'categoria'   => (string) ($p->category ?? ''),
                'marca'       => (string) ($p->brand ?? ''),
                'tags'        => (string) ($p->tags ?? ''),
                'descripcion' => mb_substr((string) ($p->description ?? ''), 0, 150),
            ];
        }

        $productJson = json_encode($productList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $messages = [
            [
                'role'    => 'system',
                'content' => $this->buildSystemPrompt(),
            ],
            [
                'role'    => 'user',
                'content' => $this->buildUserPrompt($descripcionOriginal, $unidadSolicitada, $productJson),
            ],
        ];

        try {
            $aiResults = $this->callOpenAI($messages);
        } catch (\Throwable $e) {
            Log::error('[AiMatchingService] Error llamando a OpenAI', [
                'error' => $e->getMessage(),
                'item'  => $descripcionOriginal,
            ]);
            return $this->fallback($candidates);
        }

        if (empty($aiResults)) {
            return $this->fallback($candidates);
        }

        // Cruzar resultados con la colección original
        $aiMap    = collect($aiResults)->keyBy('idx');
        $approved = [];

        foreach ($candidates as $idx => $row) {
            $aiResult = $aiMap->get($idx);

            if (! $aiResult) {
                continue;
            }

            $aprobado = (bool) ($aiResult['aprobado'] ?? false);
            $aiScore  = (int)  ($aiResult['score']    ?? 0);

            if (! $aprobado || $aiScore < 50) {
                continue;
            }

            $approved[] = array_merge($row, [
                'ai_score' => $aiScore,
                'ai_razon' => (string) ($aiResult['razon'] ?? 'Aprobado por IA'),
            ]);
        }

        return $approved;
    }

    // =========================================================
    //  PROMPTS
    // =========================================================

    protected function buildSystemPrompt(): string
    {
        return <<<SYSTEM
Eres un experto en catálogos de papelería, material de oficina y artículos escolares para licitaciones gubernamentales en México.

Tu única tarea es determinar si cada producto candidato es del mismo TIPO FUNCIONAL que el artículo solicitado.

REGLAS ABSOLUTAS:
1. Aprueba SOLO si el producto es el mismo tipo de artículo (misma función, misma familia).
2. Las coincidencias NUMÉRICAS (pesos, medidas, cantidades) NO son criterio válido de aprobación.
3. Ignora el score léxico previo. Razona desde el nombre, categoría y descripción del producto.
4. Rechaza sin piedad productos de familia diferente aunque compartan palabras.
5. Responde ÚNICAMENTE con JSON válido sin markdown, sin texto extra.

EJEMPLOS DE RECHAZO OBLIGATORIO:
- Item: "BLOCK DE BANDERITAS ADHESIVAS"  →  Rechaza: silicón, marcador, cinta, pegamento
- Item: "SILICÓN LÍQUIDO"               →  Rechaza: block, banderitas, señales, papel
- Item: "BORRADOR PARA PIZARRÓN"        →  Rechaza: goma de lápiz, corrector, líquido corrector
- Item: "GOMA PARA LÁPIZ"              →  Rechaza: borrador de pizarrón, corrector líquido
- Item: "FOLDERS"                       →  Rechaza: archivero, carpeta de argollas, separadores
SYSTEM;
    }

    protected function buildUserPrompt(string $descripcion, string $unidad, string $productJson): string
    {
        return <<<USER
ARTÍCULO SOLICITADO EN LA LICITACIÓN:
Descripción: {$descripcion}
Unidad: {$unidad}

PRODUCTOS CANDIDATOS DEL CATÁLOGO:
{$productJson}

Evalúa cada candidato y determina si es del mismo tipo funcional que el artículo solicitado.

Responde con JSON exactamente en este formato (incluye TODOS los candidatos):
{
  "resultados": [
    {
      "idx": 0,
      "product_id": 123,
      "aprobado": true,
      "score": 90,
      "razon": "Es exactamente el mismo tipo porque..."
    },
    {
      "idx": 1,
      "product_id": 456,
      "aprobado": false,
      "score": 0,
      "razon": "Es de familia diferente porque..."
    }
  ]
}

IMPORTANTE:
- "aprobado": true SOLO si es el mismo tipo funcional de producto.
- "score": 0-100 (0 si no aplica, ≥50 si apruebas).
- Incluye TODOS los candidatos en "resultados".
USER;
    }

    // =========================================================
    //  LLAMADA A OPENAI
    // =========================================================

    protected function callOpenAI(array $messages): array
    {
        $url = $this->baseUrl . '/v1/chat/completions';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])
        ->timeout($this->timeout)
        ->post($url, [
            'model'           => $this->model,
            'messages'        => $messages,
            'max_tokens'      => 2000,
            'temperature'     => 0,           // 0 = máxima consistencia, sin creatividad
            'response_format' => ['type' => 'json_object'],  // JSON mode nativo de OpenAI
        ]);

        if (! $response->successful()) {
            Log::warning('[AiMatchingService] OpenAI respondió con error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('OpenAI API error: ' . $response->status());
        }

        $raw    = $response->json('choices.0.message.content', '{}');
        $parsed = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! isset($parsed['resultados'])) {
            Log::warning('[AiMatchingService] JSON inválido recibido de OpenAI', ['raw' => $raw]);
            return [];
        }

        return $parsed['resultados'];
    }

    // =========================================================
    //  FALLBACK (si OpenAI no responde)
    // =========================================================

    /**
     * Si la IA no está disponible, usamos el score léxico con umbral alto
     * para evitar falsos positivos.
     */
    protected function fallback(array $candidates): array
    {
        return collect($candidates)
            ->filter(fn ($row) => (float) $row['score'] >= 65)
            ->map(fn ($row) => array_merge($row, [
                'ai_score' => (int) $row['score'],
                'ai_razon' => 'Coincidencia léxica alta (IA no disponible)',
            ]))
            ->values()
            ->all();
    }
}