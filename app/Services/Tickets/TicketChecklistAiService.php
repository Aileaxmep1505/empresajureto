<?php

namespace App\Services\Tickets;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TicketChecklistAiService
{
    /**
     * Genera checklist con IA y regresa:
     * [
     *   'title' => '...',
     *   'keywords' => [...],
     *   'items' => [
     *     ['title'=>'...', 'detail'=>null|'...', 'recommended'=>true|false],
     *   ],
     *   'meta' => [...]
     * ]
     *
     * Abierto: dejamos que OpenAI genere 100% con base en title/description/area.
     * Sin temperature para evitar errores en modelos que no lo soportan.
     * Responses API + JSON Schema estricto.
     */
    public function generateChecklist(string $title, string $description, string $area): array
    {
        $title = trim($title);
        $description = trim($description);
        $areaOriginal = trim($area);

        if ($title === '') {
            return $this->fallbackChecklist('(sin título)', $areaOriginal, 'Título vacío');
        }

        $prompt = $this->buildPrompt($title, $description, $areaOriginal);
        $rawText = $this->callAiProvider($prompt);

        if (!is_string($rawText) || trim($rawText) === '') {
            Log::warning('Checklist IA: respuesta vacía', [
                'area_original' => $areaOriginal,
                'title' => $title,
            ]);

            return $this->fallbackChecklist($title, $areaOriginal, 'IA devolvió texto vacío');
        }

        $parsed = $this->tryJsonDecode($rawText);

        if (!$parsed) {
            $jsonBlock = $this->extractFirstJsonObject($rawText);
            if ($jsonBlock) {
                $parsed = $this->tryJsonDecode($jsonBlock);
            }
        }

        if (!$parsed || !is_array($parsed)) {
            Log::warning('Checklist IA: no se pudo parsear JSON', [
                'area_original' => $areaOriginal,
                'title' => $title,
                'snippet' => mb_substr($rawText, 0, 900),
            ]);

            return $this->fallbackChecklist($title, $areaOriginal, 'No se pudo parsear JSON');
        }

        $out = $this->normalizeChecklistPayload($parsed, $title);

        $out['items'] = array_slice($out['items'], 0, 10);
        $out['keywords'] = array_slice($out['keywords'], 0, 8);

        $out['meta'] = [
            'model' => $this->resolveModel(),
            'area_original' => $areaOriginal,
        ];

        return $out;
    }

    /**
     * Prompt abierto.
     */
    private function buildPrompt(string $title, string $description, string $area): string
    {
        $desc = trim($description) !== '' ? trim($description) : '(sin descripción)';
        $area = trim($area) !== '' ? trim($area) : '(sin área)';

        return <<<PROMPT
Genera un checklist práctico y accionable para resolver un ticket.

INSTRUCCIONES:
- El checklist debe basarse únicamente en el TÍTULO, la DESCRIPCIÓN y el ÁREA.
- No inventes datos concretos que no existan (nombres de sistemas, proveedores, módulos, IDs, etc.) a menos que estén en el ticket.
- Sí puedes proponer pasos razonables y específicos, pero manteniéndolos verificables.
- Produce entre 8 y 10 items.
- Cada item debe ser una acción concreta (verbo + objeto) y, si aplica, un detalle corto (1–2 líneas).

Devuelve ÚNICAMENTE un JSON válido que cumpla exactamente este formato:
{
  "title": "Checklist: ...",
  "keywords": ["..."],
  "items": [
    { "title": "Acción concreta", "detail": "detalle corto y específico", "recommended": true }
  ]
}

CONTEXTO:
- Área: "{$area}"
- Título: "{$title}"
- Descripción: "{$desc}"
PROMPT;
    }

    /**
     * Llamada a OpenAI Responses API con Structured Outputs.
     */
    private function callAiProvider(string $prompt): string
    {
        $apiKey = (string) config('services.openai.api_key', '');
        if ($apiKey === '') {
            Log::warning('Checklist IA: falta OPENAI_API_KEY');
            return '';
        }

        $model = $this->resolveModel();
        $baseUrl = rtrim((string) config('services.openai.base_url', 'https://api.openai.com'), '/');
        $projectId = trim((string) config('services.openai.project_id', ''));

        $payload = [
            'model' => $model,
            'input' => $prompt,
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'ticket_checklist',
                    'strict' => true,
                    'schema' => $this->checklistJsonSchema(),
                ],
            ],
            'tool_choice' => 'none',
        ];

        try {
            $request = Http::timeout(35)
                ->withToken($apiKey)
                ->acceptJson()
                ->asJson();

            if ($projectId !== '') {
                $request = $request->withHeaders([
                    'OpenAI-Project' => $projectId,
                ]);
            }

            $res = $request->post($baseUrl . '/v1/responses', $payload);

            if (!$res->ok()) {
                Log::warning('Checklist IA: OpenAI no OK', [
                    'status' => $res->status(),
                    'body' => mb_substr((string) $res->body(), 0, 1600),
                    'model' => $model,
                ]);

                return '';
            }

            $data = $res->json();
            $text = $this->extractFirstOutputText($data);

            return is_string($text) ? trim($text) : '';
        } catch (\Throwable $e) {
            Log::error('Checklist IA: error llamando OpenAI', [
                'msg' => $e->getMessage(),
                'model' => $model,
            ]);

            return '';
        }
    }

    private function resolveModel(): string
    {
        return (string) (
            config('services.openai.primary_model')
            ?: config('services.openai.model')
            ?: 'gpt-5-2025-08-07'
        );
    }

    private function extractFirstOutputText($data): ?string
    {
        if (!is_array($data)) {
            return null;
        }

        $output = $data['output'] ?? null;
        if (!is_array($output)) {
            return null;
        }

        foreach ($output as $item) {
            if (!is_array($item)) {
                continue;
            }

            $content = $item['content'] ?? null;
            if (!is_array($content)) {
                continue;
            }

            foreach ($content as $c) {
                if (!is_array($c)) {
                    continue;
                }

                if (($c['type'] ?? null) === 'output_text' && isset($c['text']) && is_string($c['text'])) {
                    return $c['text'];
                }
            }
        }

        return null;
    }

    private function checklistJsonSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['title', 'keywords', 'items'],
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'minLength' => 3,
                    'maxLength' => 140,
                ],
                'keywords' => [
                    'type' => 'array',
                    'minItems' => 4,
                    'maxItems' => 8,
                    'items' => [
                        'type' => 'string',
                        'minLength' => 2,
                        'maxLength' => 30,
                    ],
                ],
                'items' => [
                    'type' => 'array',
                    'minItems' => 8,
                    'maxItems' => 10,
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['title', 'detail', 'recommended'],
                        'properties' => [
                            'title' => [
                                'type' => 'string',
                                'minLength' => 3,
                                'maxLength' => 140,
                            ],
                            'detail' => [
                                'anyOf' => [
                                    [
                                        'type' => 'string',
                                        'minLength' => 3,
                                        'maxLength' => 260,
                                    ],
                                    [
                                        'type' => 'null',
                                    ],
                                ],
                            ],
                            'recommended' => [
                                'type' => 'boolean',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function tryJsonDecode(string $raw): ?array
    {
        $raw = trim($raw);
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
        $raw = preg_replace('/^\s*```(json)?/i', '', $raw) ?? $raw;
        $raw = preg_replace('/```\s*$/', '', $raw) ?? $raw;
        $raw = trim($raw);

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            return is_array($data) ? $data : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function extractFirstJsonObject(string $text): ?string
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        if (preg_match('/```json\s*(\{.*\})\s*```/is', $text, $m)) {
            return trim($m[1]);
        }

        if (preg_match('/```\s*(\{.*\})\s*```/is', $text, $m)) {
            return trim($m[1]);
        }

        $start = strpos($text, '{');
        if ($start === false) {
            return null;
        }

        $level = 0;
        $inString = false;
        $escape = false;

        $len = strlen($text);

        for ($i = $start; $i < $len; $i++) {
            $ch = $text[$i];

            if ($inString) {
                if ($escape) {
                    $escape = false;
                    continue;
                }

                if ($ch === '\\') {
                    $escape = true;
                    continue;
                }

                if ($ch === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($ch === '"') {
                $inString = true;
                continue;
            }

            if ($ch === '{') {
                $level++;
            }

            if ($ch === '}') {
                $level--;

                if ($level === 0) {
                    $candidate = substr($text, $start, $i - $start + 1);
                    return trim($candidate);
                }
            }
        }

        return null;
    }

    private function normalizeChecklistPayload(array $data, string $fallbackTitle): array
    {
        $title = isset($data['title']) && is_string($data['title']) && trim($data['title']) !== ''
            ? trim($data['title'])
            : "Checklist: {$fallbackTitle}";

        $kw = $data['keywords'] ?? [];
        if (!is_array($kw)) {
            $kw = [];
        }

        $keywords = [];
        foreach ($kw as $k) {
            $k = trim((string) $k);
            if ($k !== '') {
                $keywords[] = $k;
            }
        }

        $keywords = array_values(array_unique($keywords));

        if (count($keywords) < 4) {
            $keywords = $this->fallbackKeywordsFromTitle($fallbackTitle);
        }

        $keywords = array_slice($keywords, 0, 8);

        $itemsRaw = $data['items'] ?? [];
        if (!is_array($itemsRaw)) {
            $itemsRaw = [];
        }

        $items = [];
        foreach ($itemsRaw as $it) {
            if (!is_array($it)) {
                continue;
            }

            $t = isset($it['title']) ? trim((string) $it['title']) : '';
            if ($t === '') {
                continue;
            }

            $d = $it['detail'] ?? null;
            $d = is_null($d) ? null : trim((string) $d);
            if ($d === '') {
                $d = null;
            }

            $rec = $it['recommended'] ?? true;
            $rec = is_bool($rec) ? $rec : in_array((string) $rec, ['1', 'true', 'si', 'sí', 'yes'], true);

            $items[] = [
                'title' => $t,
                'detail' => $d,
                'recommended' => $rec,
            ];
        }

        $seen = [];
        $clean = [];

        foreach ($items as $it) {
            $k = mb_strtolower(trim($it['title']));
            if (isset($seen[$k])) {
                continue;
            }

            $seen[$k] = true;
            $clean[] = $it;
        }

        if (count($clean) < 8) {
            $clean = array_pad($clean, 8, [
                'title' => 'Completar tarea pendiente del ticket',
                'detail' => null,
                'recommended' => true,
            ]);
        }

        return [
            'title' => $title,
            'keywords' => $keywords,
            'items' => $clean,
        ];
    }

    private function fallbackKeywordsFromTitle(string $title): array
    {
        $t = mb_strtolower(trim($title));
        $t = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'u', 'n'], $t);
        $t = preg_replace('/[^a-z0-9\s\-]/', ' ', $t) ?? $t;
        $t = preg_replace('/\s+/u', ' ', $t) ?? $t;

        $parts = preg_split('/\s+/', trim($t)) ?: [];
        $stop = array_flip([
            'de', 'la', 'el', 'los', 'las', 'y', 'o', 'u', 'a', 'en', 'con', 'sin',
            'para', 'por', 'del', 'al', 'un', 'una', 'unos', 'unas', 'se', 'que',
            'es', 'son', 'ser',
        ]);

        $out = [];
        foreach ($parts as $w) {
            if ($w === '' || strlen($w) < 3) {
                continue;
            }

            if (isset($stop[$w])) {
                continue;
            }

            $out[] = $w;
        }

        $out = array_values(array_unique($out));

        return array_slice($out, 0, 8);
    }

    private function fallbackChecklist(string $ticketTitle, string $area, string $reason): array
    {
        Log::info('Checklist IA fallback', [
            'area' => $area,
            'title' => $ticketTitle,
            'reason' => $reason,
        ]);

        return [
            'title' => "Checklist: {$ticketTitle}",
            'keywords' => array_slice($this->fallbackKeywordsFromTitle($ticketTitle), 0, 8),
            'items' => [
                [
                    'title' => 'Revisar el título y la descripción del ticket',
                    'detail' => 'Asegurar que estén completos y claros.',
                    'recommended' => true,
                ],
                [
                    'title' => 'Identificar el objetivo exacto del ticket',
                    'detail' => 'Qué debe quedar listo al terminar.',
                    'recommended' => true,
                ],
                [
                    'title' => 'Listar insumos necesarios',
                    'detail' => 'Datos, archivos o accesos requeridos.',
                    'recommended' => true,
                ],
                [
                    'title' => 'Ejecutar la acción principal',
                    'detail' => 'Realizar el cambio o gestión solicitada.',
                    'recommended' => true,
                ],
                [
                    'title' => 'Verificar el resultado',
                    'detail' => 'Confirmar que cumple lo pedido en el ticket.',
                    'recommended' => true,
                ],
                [
                    'title' => 'Documentar evidencia mínima',
                    'detail' => 'Captura, folio, archivo o referencia final.',
                    'recommended' => true,
                ],
                [
                    'title' => 'Resolver ajustes detectados',
                    'detail' => 'Corregir puntos puntuales si aparecen.',
                    'recommended' => true,
                ],
                [
                    'title' => 'Cerrar el ticket',
                    'detail' => 'Dejar el entregable final listo.',
                    'recommended' => true,
                ],
            ],
            'meta' => [
                'model' => $this->resolveModel(),
                'area_original' => $area,
                'fallback_reason' => $reason,
            ],
        ];
    }
}