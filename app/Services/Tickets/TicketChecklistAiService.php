<?php

namespace App\Services\Tickets;

use RuntimeException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TicketChecklistAiService
{
    /**
     * Genera checklist con IA y regresa:
     * [
     *   'title' => '...',
     *   'items' => [
     *     ['title'=>'...', 'detail'=>null|'...', 'recommended'=>true|false],
     *   ]
     * ]
     */
    public function generateChecklist(string $title, string $description, string $area): array
    {
        $title = trim($title);
        $description = trim($description);
        $areaOriginal = trim($area);

        $area = $this->normalizeArea($areaOriginal);

        // ✅ 2 intentos: el segundo es “anti-genérico” más agresivo
        $attempts = [
            ['mode' => 'normal'],
            ['mode' => 'strict'],
        ];

        foreach ($attempts as $idx => $cfg) {
            $prompt = $this->buildPrompt($title, $description, $area, $cfg['mode']);

            $rawText = $this->callAiProvider($prompt);

            if (!is_string($rawText) || trim($rawText) === '') {
                Log::warning('Checklist IA: respuesta vacía', [
                    'area' => $area,
                    'area_original' => $areaOriginal,
                    'title' => $title,
                    'attempt' => $idx + 1,
                ]);
                continue;
            }

            $parsed = $this->tryJsonDecode($rawText);

            if (!$parsed) {
                $jsonBlock = $this->extractFirstJsonObject($rawText);
                if ($jsonBlock) $parsed = $this->tryJsonDecode($jsonBlock);
            }

            if (!$parsed || !is_array($parsed)) {
                Log::warning('Checklist IA: no se pudo parsear JSON', [
                    'area' => $area,
                    'area_original' => $areaOriginal,
                    'title' => $title,
                    'attempt' => $idx + 1,
                    'snippet' => mb_substr($rawText, 0, 900),
                ]);
                continue;
            }

            $out = $this->normalizeChecklistPayload($parsed, $title, $area);

            // ✅ filtro anti-genérico (si viene muy plantilla, reintenta)
            $out['items'] = $this->removeGenericItems($out['items']);
            $out['items'] = array_values($out['items']);

            // ✅ Si quedan pocos items, reintenta (no rellenes con base)
            if (count($out['items']) < 6) {
                Log::warning('Checklist IA: muy pocos items tras limpieza, reintentando', [
                    'area' => $area,
                    'title' => $title,
                    'attempt' => $idx + 1,
                    'items_after_clean' => count($out['items']),
                ]);
                continue;
            }

            // ✅ límite para UI
            $out['items'] = array_slice($out['items'], 0, 12);

            return $out;
        }

        // ✅ Si falló IA, fallback por área (pero específico)
        return $this->fallbackChecklist($title, $area, 'IA falló o devolvió checklist genérico');
    }

    /**
     * Prompt por área + anti-genérico en modo strict
     */
    private function buildPrompt(string $title, string $description, string $area, string $mode = 'normal'): string
    {
        $desc = trim($description) !== '' ? trim($description) : '(sin descripción)';
        $areaGuide = $this->areaGuidance($area);

        $antiGeneric = '';
        if ($mode === 'strict') {
            $antiGeneric = <<<TXT

REGLA EXTRA ANTI-GENÉRICA (OBLIGATORIA):
- PROHIBIDO usar frases plantilla como:
  "Identificar alcance", "Extraer palabras clave", "Ejecutar el entregable principal",
  "Verificar el resultado", "Resolver detalles restantes", "Prueba final del flujo".
- Cada item debe mencionar un objeto concreto del trabajo (ej: "Anexo técnico", "formato", "firma", "vigencia", "PDF legible",
  "matriz de cumplimiento", "requisito", "numeración", "sellos", "RFC", "garantía", "catálogo", etc.) según el área.
- Si el área es Licitaciones, incluye tareas de revisión documental REAL: requisitos, anexos, vigencias, firmas, sellos, formatos, legibilidad, consistencia, matriz de cumplimiento.
TXT;
        }

        return <<<PROMPT
Eres un asistente que crea un checklist de EJECUCIÓN (tareas accionables y terminables) para resolver un ticket.
Devuelve ÚNICAMENTE un JSON válido (sin texto extra, sin markdown, sin ```).

Formato exacto:
{
  "title": "Checklist específico: ...",
  "items": [
    { "title": "Acción concreta", "detail": "detalle corto y específico", "recommended": true }
  ]
}

Contexto:
- Título: "{$title}"
- Área: "{$area}"
- Descripción: "{$desc}"

GUÍA POR ÁREA (OBLIGATORIO SEGUIR):
{$areaGuide}

REGLAS:
1) SOLO acciones DIRECTAMENTE relacionadas con el título y la descripción.
2) PROHIBIDO incluir tareas genéricas de proceso como:
   - documentar, informar, notificar, comunicar, reunión, seguimiento, retro, reporte, bitácora
3) "revisar/validar" SOLO si dices QUÉ revisar/validar exactamente (objeto concreto).
4) NO inventes sistemas/módulos/tecnologías que no estén mencionados.
5) Deben ser 8 a 10 items.
6) "detail" corto (1–2 líneas). Si no aplica, null.

OBLIGATORIO PARA QUE SEA ESPECÍFICO:
- Extrae del título y descripción 4–8 palabras clave y úsalo para crear subtareas.
- Cada item debe referirse a un documento/artefacto/objeto concreto del área (no “pasos plantilla”).

{$antiGeneric}

Responde con el JSON.
PROMPT;
    }

    /**
     * ✅ Llamada real a OpenAI Responses API con Structured Outputs (JSON Schema).
     * Requiere OPENAI_API_KEY en .env
     */
    private function callAiProvider(string $prompt): string
    {
        $apiKey = (string) env('OPENAI_API_KEY', '');
        if ($apiKey === '') {
            Log::warning('Checklist IA: falta OPENAI_API_KEY');
            return '';
        }

        $model = (string) env('OPENAI_MODEL', 'gpt-5-mini');

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
            'temperature' => 0.35,
            'tool_choice' => 'none',
        ];

        try {
            $res = Http::timeout(35)
                ->withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->post('https://api.openai.com/v1/responses', $payload);

            if (!$res->ok()) {
                Log::warning('Checklist IA: OpenAI no OK', [
                    'status' => $res->status(),
                    'body' => mb_substr((string) $res->body(), 0, 1600),
                ]);
                return '';
            }

            $data = $res->json();

            $text = $this->extractFirstOutputText($data);
            return is_string($text) ? trim($text) : '';
        } catch (\Throwable $e) {
            Log::error('Checklist IA: error llamando OpenAI', [
                'msg' => $e->getMessage(),
            ]);
            return '';
        }
    }

    private function extractFirstOutputText($data): ?string
    {
        if (!is_array($data)) return null;

        $output = $data['output'] ?? null;
        if (!is_array($output)) return null;

        foreach ($output as $item) {
            if (!is_array($item)) continue;
            $content = $item['content'] ?? null;
            if (!is_array($content)) continue;

            foreach ($content as $c) {
                if (!is_array($c)) continue;
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
            'required' => ['title', 'items'],
            'properties' => [
                'title' => ['type' => 'string', 'minLength' => 3, 'maxLength' => 140],
                'items' => [
                    'type' => 'array',
                    'minItems' => 8,
                    'maxItems' => 10,
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['title', 'detail', 'recommended'],
                        'properties' => [
                            'title' => ['type' => 'string', 'minLength' => 3, 'maxLength' => 140],
                            'detail' => [
                                'anyOf' => [
                                    ['type' => 'string', 'minLength' => 3, 'maxLength' => 260],
                                    ['type' => 'null'],
                                ],
                            ],
                            'recommended' => ['type' => 'boolean'],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function normalizeArea(string $area): string
    {
        $a = mb_strtolower(trim($area));
        $a = preg_replace('/\s+/u', ' ', $a) ?? $a;
        $a = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $a);

        $map = [
            'ti' => 'sistemas',
            'it' => 'sistemas',
            'sistema' => 'sistemas',
            'sistemas' => 'sistemas',
            'soporte' => 'sistemas',
            'soporte tecnico' => 'sistemas',
            'desarrollo' => 'sistemas',

            'venta' => 'ventas',
            'ventas' => 'ventas',
            'comercial' => 'ventas',

            'compra' => 'compras',
            'compras' => 'compras',
            'abastecimiento' => 'compras',

            'almacen' => 'almacen',
            'bodega' => 'almacen',
            'inventarios' => 'almacen',

            'logistica' => 'logistica',
            'envios' => 'logistica',
            'embarques' => 'logistica',

            'licitacion' => 'licitaciones',
            'licitaciones' => 'licitaciones',

            'administracion' => 'administracion',
            'admin' => 'administracion',
            'contabilidad' => 'administracion',
            'finanzas' => 'administracion',

            'mantenimiento' => 'mantenimiento',
            'servicio' => 'mantenimiento',
            'tecnico' => 'mantenimiento',
        ];

        return $map[$a] ?? trim($area);
    }

    private function areaGuidance(string $area): string
    {
        $a = mb_strtolower(trim($area));
        $a = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $a);

        $guides = [
            'licitaciones' => "- Enfócate en revisión documental real: bases, anexos, formatos, vigencias, firmas, sellos, consistencia y legibilidad.\n- Ejemplos válidos: revisar anexos obligatorios, verificar vigencia de constancias, validar que firmas/sellos correspondan, checar que PDFs sean legibles y completos, cruzar requisitos contra una matriz de cumplimiento.\n- NO uses pasos plantilla tipo “identificar alcance”.",
            'ventas' => "- Enfócate en tareas comerciales concretas: requisitos del cliente, cotización/propuesta, cantidades/modelos, condiciones.\n- Evita tareas de software si no se menciona.",
            'compras' => "- Enfócate en especificaciones, comparación de proveedores, tiempos, condiciones, compatibilidad.",
            'almacen' => "- Enfócate en ubicación/recepción/surtido/conteos/empaque/verificación de piezas.",
            'logistica' => "- Enfócate en dirección/ventana/guía/paquetería/recolección/entrega.",
            'administracion' => "- Enfócate en facturas/pagos/contratos/registros internos ligados al ticket.",
            'mantenimiento' => "- Enfócate en diagnóstico/intervención/prueba funcional del equipo.",
            'sistemas' => "- Enfócate en tareas técnicas de software (vistas, rutas, controller, validaciones, UI/UX).",
        ];

        return $guides[$a] ?? "- Enfócate en tareas ejecutables propias del área indicada, con objetos concretos.";
    }

    private function tryJsonDecode(string $raw): ?array
    {
        $raw = trim($raw);
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
        $raw = preg_replace('/^```(json)?/i', '', $raw) ?? $raw;
        $raw = preg_replace('/```$/', '', $raw) ?? $raw;
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
        if ($text === '') return null;

        if (preg_match('/```json\s*(\{.*\})\s*```/is', $text, $m)) return trim($m[1]);
        if (preg_match('/```\s*(\{.*\})\s*```/is', $text, $m)) return trim($m[1]);

        $start = strpos($text, '{');
        if ($start === false) return null;

        $level = 0;
        $inString = false;
        $escape = false;

        $len = strlen($text);
        for ($i = $start; $i < $len; $i++) {
            $ch = $text[$i];

            if ($inString) {
                if ($escape) { $escape = false; continue; }
                if ($ch === '\\') { $escape = true; continue; }
                if ($ch === '"') $inString = false;
                continue;
            }

            if ($ch === '"') { $inString = true; continue; }

            if ($ch === '{') $level++;
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

    private function normalizeChecklistPayload(array $data, string $fallbackTitle, string $area): array
    {
        $title = isset($data['title']) && is_string($data['title']) && trim($data['title']) !== ''
            ? trim($data['title'])
            : "Checklist para {$fallbackTitle}";

        $itemsRaw = $data['items'] ?? [];
        if (!is_array($itemsRaw)) $itemsRaw = [];

        $items = [];
        foreach ($itemsRaw as $it) {
            if (!is_array($it)) continue;

            $t = isset($it['title']) ? trim((string)$it['title']) : '';
            if ($t === '') continue;

            $d = $it['detail'] ?? null;
            $d = is_null($d) ? null : trim((string)$d);
            if ($d === '') $d = null;

            $rec = $it['recommended'] ?? true;
            $rec = is_bool($rec) ? $rec : (in_array((string)$rec, ['1','true','si','sí','yes'], true));

            $items[] = [
                'title' => $t,
                'detail' => $d,
                'recommended' => $rec,
            ];
        }

        // Deduplicar por title
        $seen = [];
        $clean = [];
        foreach ($items as $it) {
            $k = mb_strtolower(trim($it['title']));
            if (isset($seen[$k])) continue;
            $seen[$k] = true;
            $clean[] = $it;
        }

        return [
            'title' => $title,
            'items' => $clean,
        ];
    }

    /**
     * Elimina items plantilla típicos que te estaban contaminando el checklist.
     */
    private function removeGenericItems(array $items): array
    {
        $banned = [
            'identificar alcance',
            'extraer palabras clave',
            'ejecutar el entregable principal',
            'verificar el resultado',
            'resolver detalles restantes',
            'prueba final del flujo',
            'delimitar que se va a entregar',
            'convertir 3–6 keywords',
            'subtareas concretas',
        ];

        $out = [];
        foreach ($items as $it) {
            $t = mb_strtolower(trim((string)($it['title'] ?? '')));
            $d = mb_strtolower(trim((string)($it['detail'] ?? '')));

            $isGeneric = false;
            foreach ($banned as $x) {
                if ($x !== '' && (str_contains($t, $x) || str_contains($d, $x))) {
                    $isGeneric = true;
                    break;
                }
            }

            if ($isGeneric) continue;
            $out[] = $it;
        }

        return $out;
    }

    private function fallbackChecklist(string $ticketTitle, string $area, string $reason): array
    {
        Log::info('Checklist IA fallback', [
            'area' => $area,
            'title' => $ticketTitle,
            'reason' => $reason,
        ]);

        return [
            'title' => "Checklist para {$ticketTitle}",
            'items' => array_slice($this->fallbackItemsByArea($area, $ticketTitle), 0, 10),
        ];
    }

    /**
     * Fallback específico por área (sin plantilla general).
     */
    private function fallbackItemsByArea(string $area, string $ticketTitle): array
    {
        $a = mb_strtolower(trim($area));
        $a = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $a);

        if ($a === 'licitaciones') {
            return [
                ['title'=>'Descargar/ubicar bases y anexos del proceso', 'detail'=>'Asegurar que tienes el paquete completo de la licitación referida.', 'recommended'=>true],
                ['title'=>'Revisar listado de requisitos obligatorios', 'detail'=>'Identificar documentos, formatos y anexos requeridos por la convocatoria.', 'recommended'=>true],
                ['title'=>'Verificar vigencias de constancias y documentos', 'detail'=>'Checar fechas (opinión, constancias, identificaciones, poderes, etc.).', 'recommended'=>true],
                ['title'=>'Validar firmas, sellos y representación legal', 'detail'=>'Confirmar que firmantes y poderes coinciden con lo exigido.', 'recommended'=>true],
                ['title'=>'Revisar consistencia de datos entre documentos', 'detail'=>'Razón social, RFC, domicilios, números de expediente, folios.', 'recommended'=>true],
                ['title'=>'Validar legibilidad y completitud de PDFs', 'detail'=>'Que no falten páginas, anexos, firmas; que se lea bien.', 'recommended'=>true],
                ['title'=>'Cruzar requisitos vs expediente (matriz simple)', 'detail'=>'Marcar cumplido/no cumplido y detectar faltantes reales.', 'recommended'=>true],
                ['title'=>'Señalar errores concretos encontrados', 'detail'=>'Lista puntual de errores: documento X falta página Y / vigencia vencida / firma faltante.', 'recommended'=>true],
            ];
        }

        // fallback genérico por otras áreas, pero sin plantilla repetitiva
        return [
            ['title'=>"Desglosar el pedido del ticket: {$ticketTitle}", 'detail'=>'Convertir el título/descr en pasos concretos del área.', 'recommended'=>true],
            ['title'=>'Identificar insumos/documentos/objetos necesarios', 'detail'=>'Lista de lo que se requiere para ejecutar (según el área).', 'recommended'=>true],
            ['title'=>'Ejecutar la acción principal del ticket', 'detail'=>'Hacer el cambio/entregable que pide el ticket, con evidencia si aplica.', 'recommended'=>true],
            ['title'=>'Verificar resultado contra lo solicitado', 'detail'=>'Confirmar que cumple lo descrito (sin pasos de reporte genérico).', 'recommended'=>true],
            ['title'=>'Resolver faltantes o inconsistencias detectadas', 'detail'=>'Ajustar lo necesario para que quede completo.', 'recommended'=>true],
            ['title'=>'Validación final operativa', 'detail'=>'Probar/confirmar el flujo real del área.', 'recommended'=>true],
        ];
    }
}