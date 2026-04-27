<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de cotización inteligente para licitaciones gubernamentales.
 *
 * NO contiene reglas hardcodeadas de familias de productos.
 * La IA actúa como cotizador experto con sentido común.
 */
class AiMatchingService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected int    $timeout;

    public function __construct()
    {
        $this->apiKey   = config('services.openai.api_key');
        $this->baseUrl  = rtrim(config('services.openai.base_url', 'https://api.openai.com'), '/');
        $this->model    = config('services.openai.json_repair_model', 'gpt-4o-mini');
        $this->timeout  = 35;
    }

    // =========================================================
    //  MÉTODO PRINCIPAL
    // =========================================================

    /**
     * Valida semánticamente los candidatos pre-filtrados por SQL.
     *
     * Retorna solo los candidatos que la IA aprueba como cotizables
     * para el ítem solicitado, enriquecidos con 'ai_score' y 'ai_razon'.
     *
     * @param  string $descripcionOriginal  Texto exacto de la licitación
     * @param  string $unidadSolicitada     Unidad del ítem (PIEZA, CAJA, etc.)
     * @param  array  $candidates           Pre-filtrados por score léxico
     * @return array  Candidatos aprobados con 'ai_score' y 'ai_razon'
     */
    public function validateCandidates(
        string $descripcionOriginal,
        string $unidadSolicitada,
        array  $candidates
    ): array {
        if (empty($candidates)) {
            return [];
        }

        // Construir listado compacto para el prompt
        $productList = [];
        foreach ($candidates as $idx => $row) {
            $p = $row['product'];
            $productList[] = [
                'idx'         => $idx,
                'id'          => $p->id,
                'nombre'      => (string) ($p->name        ?? ''),
                'sku'         => (string) ($p->sku         ?? ''),
                'categoria'   => (string) ($p->category    ?? ''),
                'marca'       => (string) ($p->brand       ?? ''),
                'tags'        => (string) ($p->tags        ?? ''),
                'descripcion' => mb_substr((string) ($p->description ?? ''), 0, 200),
                'unidad'      => (string) ($p->unit        ?? ''),
                'material'    => (string) ($p->material    ?? ''),
            ];
        }

        $productJson = json_encode($productList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ['role' => 'user',   'content' => $this->userPrompt($descripcionOriginal, $unidadSolicitada, $productJson)],
        ];

        try {
            $aiResults = $this->callOpenAI($messages);
        } catch (\Throwable $e) {
            Log::error('[AiMatchingService] Error OpenAI', [
                'error' => $e->getMessage(),
                'item'  => $descripcionOriginal,
            ]);
            return $this->fallback($candidates);
        }

        if (empty($aiResults)) {
            return $this->fallback($candidates);
        }

        // Cruzar resultados de IA con la colección original
        $aiMap    = collect($aiResults)->keyBy('idx');
        $approved = [];

        foreach ($candidates as $idx => $row) {
            $ai = $aiMap->get($idx);

            if (! $ai || ! ($ai['aprobado'] ?? false) || (int) ($ai['score'] ?? 0) < 50) {
                continue;
            }

            $approved[] = array_merge($row, [
                'ai_score' => (int)    ($ai['score'] ?? 0),
                'ai_razon' => (string) ($ai['razon'] ?? 'Aprobado por IA'),
            ]);
        }

        return $approved;
    }

    // =========================================================
    //  PROMPTS
    // =========================================================

    protected function systemPrompt(): string
    {
        return <<<'SYSTEM'
Eres un COTIZADOR EXPERTO en papelería, útiles escolares y material de oficina para licitaciones gubernamentales en México con 20 años de experiencia.

Tu trabajo es revisar los productos candidatos de un catálogo y decidir cuáles son cotizables para el artículo que pide la licitación.

CÓMO RAZONAR (sentido común de cotizador):

1. LEE la descripción completa del artículo solicitado. Extrae mentalmente:
   - ¿Qué TIPO de producto es? (lápiz, borrador, folder, cinta, etc.)
   - ¿Qué CARACTERÍSTICAS específicas pide? (bicolor, de madera, no tóxico, con imán, etc.)
   - ¿Qué PRESENTACIÓN pide? (caja c/12, paquete, pieza individual, etc.)
   - ¿Qué MATERIAL? (madera, plástico, etc.)

2. Compara cada candidato contra esas dimensiones:
   - TIPO correcto = requisito mínimo (si no coincide, RECHAZA sin importar nada más)
   - CARACTERÍSTICAS similares = sube el score
   - PRESENTACIÓN compatible = sube el score (un producto en pieza puede ser equivalente a uno en caja si es el mismo artículo)
   - MATERIAL compatible = sube el score

3. EJEMPLOS DE TU RAZONAMIENTO:
   - Piden "LÁPIZ BICOLOR AZUL Y ROJO DE MADERA C/12": aprueba lápices bicolor aunque sean de 1 pieza (mismo tipo), rechaza marcadores aunque sean azul/rojo (tipo diferente).
   - Piden "BORRADOR PARA PIZARRÓN": aprueba borradores de pizarrón, rechaza gomas de lápiz y correctores (tipos distintos aunque se llamen similar).
   - Piden "BLOCK DE BANDERITAS ADHESIVAS": aprueba blocks de banderitas/señaladores, rechaza silicón aunque tenga el mismo número de gramos.
   - Piden "FOLDER TAMAÑO CARTA": aprueba folders de carta, rechaza carpetas de argollas y separadores.

4. SOBRE LA PRESENTACIÓN: si piden CAJA C/12 y tu catálogo tiene el mismo producto en pieza, el cotizador compra 12 piezas. Eso ES cotizable. Da score alto si el tipo es correcto.

5. SOBRE SCORES:
   - 90-100: Tipo exacto + características muy similares o iguales
   - 70-89:  Tipo correcto + algunas características coinciden
   - 50-69:  Tipo correcto pero características parcialmente distintas (material, color, etc.)
   - 0-49:   Tipo incorrecto → aprobado: false

RESPONDE ÚNICAMENTE con JSON válido. Sin texto extra, sin markdown, sin explicaciones fuera del JSON.
SYSTEM;
    }

    protected function userPrompt(string $descripcion, string $unidad, string $productJson): string
    {
        return <<<USER
ARTÍCULO SOLICITADO EN LA LICITACIÓN:
Descripción: {$descripcion}
Unidad de medida: {$unidad}

PRODUCTOS CANDIDATOS DEL CATÁLOGO:
{$productJson}

Como cotizador experto, evalúa si cada candidato es cotizable para este artículo.

Devuelve TODOS los candidatos en este JSON exacto:
{
  "razonamiento_item": "Aquí explicas brevemente qué tipo de producto es el artículo solicitado y qué características clave buscas",
  "resultados": [
    {
      "idx": 0,
      "product_id": 123,
      "aprobado": true,
      "score": 92,
      "razon": "Es el mismo tipo de producto (lápiz bicolor) con características compatibles"
    },
    {
      "idx": 1,
      "product_id": 456,
      "aprobado": false,
      "score": 0,
      "razon": "Es un marcador, no un lápiz. Tipo de producto incorrecto."
    }
  ]
}

INCLUYE absolutamente TODOS los candidatos en "resultados".
USER;
    }

    // =========================================================
    //  LLAMADA A OPENAI
    // =========================================================

    protected function callOpenAI(array $messages): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])
        ->timeout($this->timeout)
        ->post($this->baseUrl . '/v1/chat/completions', [
            'model'           => $this->model,
            'messages'        => $messages,
            'max_tokens'      => 2500,
            'temperature'     => 0,               // Sin creatividad: máxima consistencia
            'response_format' => ['type' => 'json_object'], // JSON mode nativo
        ]);

        if (! $response->successful()) {
            Log::warning('[AiMatchingService] OpenAI error HTTP', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('OpenAI HTTP ' . $response->status());
        }

        $raw    = $response->json('choices.0.message.content', '{}');
        $parsed = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! isset($parsed['resultados'])) {
            Log::warning('[AiMatchingService] JSON inválido de OpenAI', [
                'raw'  => $raw,
                'item' => $messages[1]['content'] ?? '',
            ]);
            return [];
        }

        // Log del razonamiento de la IA para depuración
        if (isset($parsed['razonamiento_item'])) {
            Log::info('[AiMatchingService] IA razonó: ' . $parsed['razonamiento_item']);
        }

        return $parsed['resultados'];
    }

    // =========================================================
    //  FALLBACK (si OpenAI no responde)
    // =========================================================

    /**
     * Si la IA no responde, aplicamos umbral léxico muy alto (≥ 68)
     * para evitar falsos positivos mientras la IA no esté disponible.
     */
    protected function fallback(array $candidates): array
    {
        return collect($candidates)
            ->filter(fn ($row) => (float) $row['score'] >= 68)
            ->map(fn ($row) => array_merge($row, [
                'ai_score' => (int) $row['score'],
                'ai_razon' => 'Coincidencia léxica alta (IA temporalmente no disponible)',
            ]))
            ->sortByDesc('ai_score')
            ->values()
            ->all();
    }
}