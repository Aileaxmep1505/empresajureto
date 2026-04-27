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
        $this->model   = config('services.openai.json_repair_model', 'gpt-4o-mini');
    }

    public function validateCandidates(
        string $descripcionOriginal,
        string $unidadSolicitada,
        array  $candidates
    ): array {
        if (empty($candidates)) { return []; }

        $productList = [];
        foreach ($candidates as $idx => $row) {
            $p = $row['product'];
            $productList[] = [
                'idx'                  => $idx,
                'id'                   => $p->id,
                'nombre'               => (string) ($p->name        ?? ''),
                'sku'                  => (string) ($p->sku         ?? ''),
                'categoria'            => (string) ($p->category    ?? ''),
                'marca'                => (string) ($p->brand       ?? ''),
                'tags'                 => (string) ($p->tags        ?? ''),
                'descripcion'          => mb_substr((string) ($p->description ?? ''), 0, 250),
                'material'             => (string) ($p->material    ?? ''),
                'color'                => (string) ($p->color       ?? ''),
                'unidad'               => (string) ($p->unit        ?? ''),
                'similitud_semantica'  => round((float) ($row['similarity'] ?? 0) * 100, 1) . '%',
            ];
        }

        $productJson = json_encode($productList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        try {
            $aiResults = $this->callOpenAI([
                ['role' => 'system', 'content' => $this->systemPrompt()],
                ['role' => 'user',   'content' => $this->userPrompt($descripcionOriginal, $unidadSolicitada, $productJson)],
            ]);
        } catch (\Throwable $e) {
            Log::error('[AiMatchingService] Error OpenAI', ['error' => $e->getMessage()]);
            return $this->fallback($candidates);
        }

        if (empty($aiResults)) { return $this->fallback($candidates); }

        $aiMap = collect($aiResults)->keyBy('idx');
        $approved = [];

        foreach ($candidates as $idx => $row) {
            $ai = $aiMap->get($idx);
            if (! $ai || ! ($ai['aprobado'] ?? false) || (int) ($ai['score'] ?? 0) < 45) { continue; }

            $approved[] = array_merge($row, [
                'ai_score'         => (int)   ($ai['score']         ?? 0),
                'ai_razon'         => (string) ($ai['razon']         ?? 'Aprobado'),
                'ai_coincidencias' => (array)  ($ai['coincidencias'] ?? []),
                'ai_diferencias'   => (array)  ($ai['diferencias']   ?? []),
                'unidad_coincide'  => $row['unidad_coincide'] ?? false,
            ]);
        }

        return $approved;
    }

    protected function systemPrompt(): string
    {
        return <<<'SYSTEM'
Eres un COTIZADOR EXPERTO en material de oficina, papelería, tecnología y artículos escolares para licitaciones gubernamentales de México, con 20 años de experiencia.

Estos candidatos ya fueron pre-seleccionados por similitud semántica — son los más parecidos al artículo solicitado en todo el catálogo.

Tu trabajo: decidir si cada candidato es COTIZABLE evaluando característica por característica.

═══════════════════════════════════════════════════════════
PASO 1 — DESCOMPÓN EL ARTÍCULO EN CARACTERÍSTICAS
═══════════════════════════════════════════════════════════
  • TIPO DE PRODUCTO       → ¿qué es exactamente?
  • CARACTERÍSTICAS CLAVE  → specs técnicas, funciones, capacidades
  • MATERIAL               → si se especifica
  • COLOR                  → si se especifica
  • PRESENTACIÓN           → pieza, caja c/12, paquete, etc.
  • MARCA                  → si se especifica

═══════════════════════════════════════════════════════════
PASO 2 — EVALÚA CADA CANDIDATO (suma y resta puntos)
═══════════════════════════════════════════════════════════

REGLA ABSOLUTA: Si el TIPO no coincide → aprobado: false, score: 0.

SI EL TIPO SÍ COINCIDE:
  Base tipo correcto                        → +50
  Característica técnica coincide exacta    → +10 c/u
  Característica técnica similar (no igual) → +5 c/u
  Material coincide                         → +8
  Color coincide exacto                     → +6
  Color parcial (tiene ese color entre otros) → +3
  Presentación compatible                   → +5
  Marca coincide                            → +4
  Característica técnica diferente          → -5
  Característica técnica incompatible       → -10

UMBRALES:
  ≥ 80 → Ideal
  60-79 → Buena opción
  45-59 → Opción válida
  < 45  → No cotizable

PRINCIPIO CLAVE — MEJOR ALTERNATIVA DISPONIBLE:
  Si no hay producto idéntico, el que más se adapta ES cotizable.
  Ejemplo: piden "celular android 10 pulgadas azul", tienes
  "Samsung android 10.5 pulgadas blanco y azul" → APRUEBA (~82 pts).

RESPONDE ÚNICAMENTE con JSON válido. Sin markdown, sin texto extra.
SYSTEM;
    }

    protected function userPrompt(string $descripcion, string $unidad, string $productJson): string
    {
        return <<<USER
ARTÍCULO SOLICITADO:
Descripción: {$descripcion}
Unidad: {$unidad}

CANDIDATOS (pre-seleccionados por similitud semántica):
{$productJson}

Devuelve TODOS los candidatos en este JSON exacto:
{
  "caracteristicas_solicitadas": {
    "tipo": "...",
    "tecnicas": ["..."],
    "material": "...",
    "color": "...",
    "presentacion": "..."
  },
  "resultados": [
    {
      "idx": 0,
      "product_id": 123,
      "aprobado": true,
      "score": 85,
      "coincidencias": ["mismo tipo", "característica X coincide"],
      "diferencias": ["material diferente pero aceptable"],
      "razon": "Es cotizable porque..."
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
            'model'           => $this->model,
            'messages'        => $messages,
            'max_tokens'      => 3000,
            'temperature'     => 0,
            'response_format' => ['type' => 'json_object'],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI HTTP ' . $response->status());
        }

        $raw    = $response->json('choices.0.message.content', '{}');
        $parsed = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! isset($parsed['resultados'])) {
            Log::warning('[AiMatchingService] JSON inválido', ['raw' => $raw]);
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
            ->filter(fn ($row) => (float) ($row['similarity'] ?? 0) >= 0.72)
            ->map(fn ($row) => array_merge($row, [
                'ai_score'         => (int) round((float) ($row['similarity'] ?? 0) * 100),
                'ai_razon'         => 'Alta similitud semántica (IA no disponible)',
                'ai_coincidencias' => [],
                'ai_diferencias'   => [],
            ]))
            ->sortByDesc('ai_score')
            ->values()
            ->all();
    }
}