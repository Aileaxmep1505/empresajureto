<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiMatchingService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;

    public function __construct()
    {
        $this->apiKey  = config('services.openai.api_key');
        $this->baseUrl = rtrim(config('services.openai.base_url', 'https://api.openai.com'), '/');
        $this->model   = config('services.openai.json_repair_model', 'gpt-4.1-nano-2025-04-14');
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
            $productList[] = [
                'idx'         => $idx,
                'id'          => $p->id,
                'nombre'      => (string) ($p->name        ?? ''),
                'sku'         => (string) ($p->sku         ?? ''),
                'categoria'   => (string) ($p->category    ?? ''),
                'marca'       => (string) ($p->brand       ?? ''),
                'tags'        => (string) ($p->tags        ?? ''),
                'descripcion' => mb_substr((string) ($p->description ?? ''), 0, 250),
                'material'    => (string) ($p->material    ?? ''),
                'color'       => (string) ($p->color       ?? ''),
                'unidad'      => (string) ($p->unit        ?? ''),
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

        // Log para ver exactamente qué aprobó/rechazó la IA
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

            // Umbral: aprobado por la IA Y score >= 40
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

    protected function systemPrompt(): string
    {
        return <<<'SYSTEM'
Eres un COTIZADOR EXPERTO en material de oficina, papelería, tecnología y artículos escolares para licitaciones gubernamentales de México, con 20 años de experiencia.

Tu trabajo: analizar el artículo solicitado, extraer TODAS sus características, y evaluar cada producto candidato para decidir si se puede cotizar.

PASO 1 — DESCOMPÓN EL ARTÍCULO EN CARACTERÍSTICAS:
  • TIPO DE PRODUCTO       → ¿qué es exactamente? (sé específico: "cartulina opalina", no solo "cartulina")
  • CARACTERÍSTICAS CLAVE  → gramaje, medidas, capacidades, specs técnicas
  • MATERIAL               → si se especifica
  • COLOR                  → si se especifica
  • PRESENTACIÓN           → pieza, caja c/12, paquete c/100, etc.

PASO 2 — EVALÚA CADA CANDIDATO:

REGLA ABSOLUTA — EL TIPO ES INNEGOCIABLE:
  Tipo diferente → aprobado: false, score: 0.
  Ejemplos de rechazo OBLIGATORIO:
    • Piden "cartulina opalina"  → rechaza carpeta pressboard aunque sea tamaño carta
    • Piden "calculadora"        → rechaza despachador de cinta aunque diga "12"
    • Piden "lápiz bicolor"      → rechaza marcador aunque sea azul y rojo
    • Piden "banderitas adhesivas" → rechaza silicón aunque tenga el mismo número

SI EL TIPO SÍ COINCIDE — PUNTÚA:
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
  Si no hay producto idéntico, aprueba el que más se adapte aunque no sea igual.
  Una "cartulina opalina blanca 220g" SÍ sirve si piden "cartulina opalina blanca 125g"
  (mismo tipo, gramaje diferente → score ~65, aprobado: true).
  Una "cartulina opalina tamaño carta" SÍ sirve si piden "cartulina opalina en pliego"
  (mismo tipo, medidas diferentes → score ~55, aprobado: true).
  REGLA: si el TIPO coincide y el producto PUEDE usarse para lo mismo, aprueba con el score real.
  Solo rechaza cuando el tipo es COMPLETAMENTE diferente.

RESPONDE ÚNICAMENTE con JSON puro. Sin texto adicional, sin markdown, sin explicaciones fuera del JSON.
SYSTEM;
    }

    protected function userPrompt(string $descripcion, string $unidad, string $productJson): string
    {
        return <<<USER
ARTÍCULO SOLICITADO:
Descripción: {$descripcion}
Unidad: {$unidad}

CANDIDATOS DEL CATÁLOGO:
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
      "coincidencias": ["mismo tipo: cartulina opalina", "tamaño carta coincide"],
      "diferencias": ["gramaje 220g vs 125g solicitado"],
      "razon": "Cotizable: misma familia de producto con gramaje diferente"
    },
    {
      "idx": 1,
      "product_id": 456,
      "aprobado": false,
      "score": 0,
      "coincidencias": [],
      "diferencias": ["tipo incorrecto: es carpeta pressboard, no cartulina opalina"],
      "razon": "No cotizable: tipo completamente diferente"
    }
  ]
}
USER;
    }

    protected function callOpenAI(array $messages): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])
        ->timeout(40)
        ->post($this->baseUrl . '/v1/chat/completions', [
            'model'       => $this->model,
            'messages'    => $messages,
            'max_tokens'  => 3000,
            'temperature' => 0,
        ]);

        if (! $response->successful()) {
            Log::warning('[AiMatchingService] OpenAI HTTP error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('OpenAI HTTP ' . $response->status());
        }

        $raw = $response->json('choices.0.message.content', '{}');

        // Limpiar markdown fences
        $clean = preg_replace('/```json\s*/i', '', $raw);
        $clean = preg_replace('/```\s*/', '', $clean);
        $clean = trim($clean);

        // Extraer solo el bloque JSON si hay texto extra
        if (! str_starts_with($clean, '{')) {
            preg_match('/\{.+\}/s', $clean, $matches);
            $clean = $matches[0] ?? '{}';
        }

        $parsed = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! isset($parsed['resultados'])) {
            Log::warning('[AiMatchingService] JSON invalido', [
                'raw'   => mb_substr($raw, 0, 500),
                'error' => json_last_error_msg(),
            ]);
            return [];
        }

        if (isset($parsed['caracteristicas_solicitadas'])) {
            Log::info('[AiMatchingService] Características detectadas', $parsed['caracteristicas_solicitadas']);
        }

        return $parsed['resultados'];
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