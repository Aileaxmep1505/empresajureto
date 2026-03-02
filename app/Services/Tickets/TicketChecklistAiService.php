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
     */
    public function generateChecklist(string $title, string $description, string $area): array
    {
        $title = trim($title);
        $description = trim($description);
        $areaOriginal = trim($area);

        $areaNorm = $this->normalizeArea($areaOriginal);

        // Keywords base (heurística) para “amarrar” el checklist
        $seedKeywords = $this->extractKeywords($title, $description);
        $seedKeywords = $this->areaBoostKeywords($areaNorm, $seedKeywords);

        $attempts = [
            ['mode' => 'normal'],
            ['mode' => 'strict_feedback'],
            ['mode' => 'strict_feedback'], // 3er intento igual pero con feedback más duro
        ];

        $lastReject = [];

        foreach ($attempts as $idx => $cfg) {

            $prompt = $this->buildPrompt(
                $title,
                $description,
                $areaNorm,
                $cfg['mode'],
                $seedKeywords,
                $lastReject
            );

            $rawText = $this->callAiProvider($prompt);

            if (!is_string($rawText) || trim($rawText) === '') {
                Log::warning('Checklist IA: respuesta vacía', [
                    'area' => $areaNorm,
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
                    'area' => $areaNorm,
                    'area_original' => $areaOriginal,
                    'title' => $title,
                    'attempt' => $idx + 1,
                    'snippet' => mb_substr($rawText, 0, 900),
                ]);
                continue;
            }

            $out = $this->normalizeChecklistPayload($parsed, $title, $areaNorm);

            // 1) Limpieza de plantilla típica
            $out['items'] = $this->removeGenericItems($out['items']);
            $out['items'] = array_values($out['items']);

            // 2) Validación “amarrada” por keywords + objetos del área
            $rules = $this->areaRules($areaNorm);
            $validation = $this->validateItemsAgainstRules(
                $out['items'],
                $out['keywords'],
                $rules['mustContainAny'] ?? [],
                $rules['banContains'] ?? []
            );

            $out['items'] = $validation['kept'];
            $lastReject = $validation['rejected_reasons'];

            // Si quedan pocos items, reintenta con feedback
            if (count($out['items']) < 8) {
                Log::warning('Checklist IA: muy pocos items tras validación, reintentando', [
                    'area' => $areaNorm,
                    'title' => $title,
                    'attempt' => $idx + 1,
                    'kept' => count($out['items']),
                    'rejected' => count($validation['rejected']),
                ]);
                continue;
            }

            // Limitar a UI
            $out['items'] = array_slice($out['items'], 0, 10);

            // meta útil (debug opcional)
            $out['meta'] = [
                'area_normalized' => $areaNorm,
                'keywords' => $out['keywords'],
                'attempt_used' => $idx + 1,
            ];

            return $out;
        }

        // Fallback por área (específico)
        return $this->fallbackChecklist($title, $areaNorm, 'IA falló o devolvió checklist genérico');
    }

    /**
     * Prompt por área + anti-genérico con feedback
     */
    private function buildPrompt(
        string $title,
        string $description,
        string $area,
        string $mode,
        array $seedKeywords,
        array $lastReject
    ): string
    {
        $desc = trim($description) !== '' ? trim($description) : '(sin descripción)';

        $guide = $this->areaGuidance($area);
        $rules = $this->areaRules($area);

        $must = $rules['mustContainAny'] ?? [];
        $ban  = $rules['banContains'] ?? [];

        $mustLine = !empty($must)
            ? "- Cada item DEBE mencionar al menos UNO de estos objetos del área: " . implode(', ', $must)
            : "- Cada item DEBE mencionar un objeto concreto del área (documento/campo/artefacto), no pasos abstractos.";

        $banLine = !empty($ban)
            ? "- PROHIBIDO mencionar estos términos (si aparecen, el item se rechaza): " . implode(', ', $ban)
            : "";

        $feedbackBlock = '';
        if ($mode === 'strict_feedback') {
            $bad = $this->formatRejectFeedback($lastReject);
            $feedbackBlock = <<<TXT

FEEDBACK (RECHAZOS DEL INTENTO ANTERIOR):
{$bad}

INSTRUCCIÓN OBLIGATORIA:
- Reescribe TODO el checklist evitando exactamente esos patrones.
- No uses sinónimos de "revisión final", "validar resultado", "ajustes finales" sin decir QUÉ objeto se valida.
TXT;
        }

        $seedKw = !empty($seedKeywords) ? implode(', ', $seedKeywords) : '';

        return <<<PROMPT
Eres un asistente que crea un checklist de EJECUCIÓN: tareas accionables, terminables y verificables para resolver un ticket.
Devuelve ÚNICAMENTE un JSON válido (sin texto extra, sin markdown, sin ```).

Formato exacto:
{
  "title": "Checklist específico: ...",
  "keywords": ["..."],
  "items": [
    { "title": "Acción concreta", "detail": "detalle corto y específico", "recommended": true }
  ]
}

Contexto:
- Título: "{$title}"
- Área: "{$area}"
- Descripción: "{$desc}"

GUÍA POR ÁREA (OBLIGATORIO SEGUIR):
{$guide}

REGLAS GENERALES (OBLIGATORIAS):
1) SOLO acciones DIRECTAMENTE relacionadas con el título y descripción (no inventes cosas fuera del ticket).
2) PROHIBIDO tareas genéricas de proceso como: documentar, informar, notificar, comunicar, reunión, seguimiento, retro, reporte.
3) "revisar/validar" SOLO si indicas QUÉ se revisa EXACTAMENTE (objeto concreto).
4) No inventes sistemas/módulos/tecnologías que no estén mencionados.
5) Deben ser 8 a 10 items.
6) detail corto (1–2 líneas). Si no aplica, null.

AMARRE PARA EVITAR PLANTILLAS (OBLIGATORIO):
- Genera "keywords" con 4 a 8 palabras clave extraídas del título/descr.
- Cada item debe incluir AL MENOS 1 keyword en title o detail.
{$mustLine}
{$banLine}

KEYWORDS SEMILLA (si te ayudan, úsalo, pero puedes mejorarlas):
{$seedKw}

{$feedbackBlock}

Responde con el JSON.
PROMPT;
    }

    /**
     * ✅ Llamada a OpenAI Responses API con Structured Outputs (JSON Schema).
     * Requiere OPENAI_API_KEY en .env
     */
    private function callAiProvider(string $prompt): string
    {
        $apiKey = (string) env('OPENAI_API_KEY', '');
        if ($apiKey === '') {
            Log::warning('Checklist IA: falta OPENAI_API_KEY');
            return '';
        }

        // ✅ Usa PRIMARY primero (tu caso), luego OPENAI_MODEL, luego fallback
        $model = (string) (env('OPENAI_PRIMARY_MODEL') ?: env('OPENAI_MODEL', 'gpt-5-mini'));

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
            'temperature' => 0.20,
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
            'required' => ['title', 'keywords', 'items'],
            'properties' => [
                'title' => ['type' => 'string', 'minLength' => 3, 'maxLength' => 140],
                'keywords' => [
                    'type' => 'array',
                    'minItems' => 4,
                    'maxItems' => 8,
                    'items' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 30],
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

    /**
     * Guidance por área (NO mezclado)
     */
    private function areaGuidance(string $area): string
    {
        $a = $this->normalizeArea($area);

        $guides = [
            'licitaciones' =>
                "- Enfócate en revisión documental REAL: bases, anexos, formatos, vigencias, firmas, sellos, legibilidad y consistencia.\n".
                "- Incluye matriz de cumplimiento, listado de faltantes, validación de PDFs completos y vigentes.\n".
                "- NO uses pasos abstractos tipo “definir alcance” o “revisión final” sin objeto concreto.",

            'ventas' =>
                "- Enfócate en tareas comerciales concretas: requisitos del cliente, propuesta/cotización, cantidades, modelos, precios, condiciones, vigencias.\n".
                "- Si hay documentos: orden de compra, cotización, ficha técnica, términos.\n".
                "- No metas tareas de software si no se menciona.",

            'compras' =>
                "- Enfócate en especificaciones, comparación de proveedores, tiempos de entrega, condiciones, compatibilidades, garantías.\n".
                "- Incluye validación de cotizaciones, SKU/modelo, Incoterms si aplica, y criterios de selección.",

            'almacen' =>
                "- Enfócate en recepción, ubicación, surtido, conteos, empaque, verificación de piezas y diferencias.\n".
                "- Incluye evidencia como folios de entrada/salida, conteo ciego, y checklist por SKU.",

            'logistica' =>
                "- Enfócate en dirección, ventana de entrega, guía, paquetería, recolección, tracking, entrega y POD.\n".
                "- Incluye validación de peso/volumen, embalaje, y restricciones.",

            'administracion' =>
                "- Enfócate en facturas, pagos, contratos, altas/bajas, registros internos, conciliaciones ligadas al ticket.\n".
                "- Incluye RFC/razón social, CFDI/UUID, fechas, montos, cuentas, y requisitos fiscales si aplica.",

            'mantenimiento' =>
                "- Enfócate en diagnóstico, intervención, refacciones, calibración, prueba funcional y criterios de aceptación.\n".
                "- Incluye mediciones/lecturas, checklist de seguridad, y evidencia del funcionamiento.",

            'sistemas' =>
                "- Enfócate en tareas técnicas CONCRETAS ligadas al ticket.\n".
                "- Si el ticket habla de FOLIAR/NOMENCLATURA: define prefijos por sección, regla de consecutivo, formato del folio, validaciones (duplicados), migración/actualización, UI para capturar/mostrar folio, exportación/consulta y pruebas con ejemplos reales.\n".
                "- NO metas licitaciones/ventas/compras aquí salvo que el ticket lo pida explícitamente.",
        ];

        return $guides[$a] ?? "- Enfócate en tareas ejecutables propias del área indicada, con objetos concretos del trabajo.";
    }

    /**
     * Reglas duras por área: objetos mínimos y términos prohibidos.
     * Esto es lo que evita “plantillas”.
     */
    private function areaRules(string $area): array
    {
        $a = $this->normalizeArea($area);

        $baseBan = [
            'identificar alcance','definir alcance','alcance',
            'extraer palabras clave','palabras clave','keywords',
            'ejecutar el entregable','entregable principal',
            'verificar el resultado','revisión final','validación final','ajustes finales',
            'resolver detalles restantes','pendientes',
            'reunión','seguimiento','reporte','bitácora','notificar','informar','comunicar'
        ];

        $rules = [
            'licitaciones' => [
                'mustContainAny' => ['bases','anexo','formato','vigencia','firma','sello','pdf','legible','matriz','requisito','expediente','convocatoria'],
                'banContains' => $baseBan,
            ],
            'ventas' => [
                'mustContainAny' => ['cotización','propuesta','precio','vigencia','cantidad','modelo','condiciones','cliente','OC','orden de compra','entrega'],
                'banContains' => $baseBan,
            ],
            'compras' => [
                'mustContainAny' => ['cotización','proveedor','precio','tiempo de entrega','garantía','especificación','modelo','sku','comparativo','condiciones'],
                'banContains' => $baseBan,
            ],
            'almacen' => [
                'mustContainAny' => ['recepción','ubicación','surtido','conteo','sku','lote','piezas','empaque','entrada','salida','diferencias'],
                'banContains' => $baseBan,
            ],
            'logistica' => [
                'mustContainAny' => ['guía','paquetería','recolección','tracking','POD','ventana','dirección','peso','volumen','embalaje','entrega'],
                'banContains' => $baseBan,
            ],
            'administracion' => [
                'mustContainAny' => ['factura','cfdi','uuid','pago','monto','rfc','razón social','contrato','cuenta','conciliación','fecha'],
                'banContains' => $baseBan,
            ],
            'mantenimiento' => [
                'mustContainAny' => ['diagnóstico','refacción','calibración','prueba','lectura','medición','seguridad','falla','equipo','bitácora técnica'],
                'banContains' => $baseBan,
            ],
            'sistemas' => [
                // objetos técnicos (pero generales)
                'mustContainAny' => ['campo','tabla','validación','unique','migración','script','ui','vista','ruta','controller','endpoint','exportación','consecutivo','prefijo','sección','folio','nomenclatura'],
                'banContains' => $baseBan,
            ],
        ];

        return $rules[$a] ?? [
            'mustContainAny' => [],
            'banContains' => $baseBan,
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

        $kw = $data['keywords'] ?? [];
        if (!is_array($kw)) $kw = [];
        $keywords = [];
        foreach ($kw as $k) {
            $k = trim((string)$k);
            if ($k !== '') $keywords[] = $k;
        }
        $keywords = array_values(array_unique($keywords));
        $keywords = array_slice($keywords, 0, 8);
        if (count($keywords) < 4) {
            $keywords = $this->extractKeywords($fallbackTitle, '');
        }

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
            'keywords' => $keywords,
            'items' => $clean,
        ];
    }

    /**
     * Elimina items plantilla típicos (básico).
     */
    private function removeGenericItems(array $items): array
    {
        $banned = [
            'identificar alcance',
            'definir alcance',
            'extraer palabras clave',
            'ejecutar el entregable',
            'entregable principal',
            'verificar el resultado',
            'resolver detalles restantes',
            'revisión final',
            'validación final',
            'ajustes finales',
            'prueba final',
            'seguimiento',
            'reporte',
            'bitácora',
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

    /**
     * Validación fuerte: cada item debe tener:
     * - al menos 1 keyword
     * - al menos 1 "objeto del área" (mustContainAny) si viene definido
     * - no contener banContains
     */
    private function validateItemsAgainstRules(array $items, array $keywords, array $mustContainAny, array $banContains): array
    {
        $kwNorm = array_map(fn($k) => $this->norm($k), $keywords);
        $mustNorm = array_map(fn($x) => $this->norm($x), $mustContainAny);
        $banNorm  = array_map(fn($x) => $this->norm($x), $banContains);

        $kept = [];
        $rejected = [];
        $reasons = [];

        foreach ($items as $it) {
            $title = (string)($it['title'] ?? '');
            $detail = (string)($it['detail'] ?? '');
            $blob = $this->norm($title.' '.$detail);

            // ban terms
            $hitBan = null;
            foreach ($banNorm as $b) {
                if ($b !== '' && str_contains($blob, $b)) { $hitBan = $b; break; }
            }
            if ($hitBan) {
                $rejected[] = $it;
                $reasons[] = "Rechazado por término prohibido: '{$hitBan}' en item '{$title}'";
                continue;
            }

            // keyword required
            $hitKw = false;
            foreach ($kwNorm as $k) {
                if ($k !== '' && str_contains($blob, $k)) { $hitKw = true; break; }
            }
            if (!$hitKw) {
                $rejected[] = $it;
                $reasons[] = "Rechazado por NO usar keywords en item '{$title}'";
                continue;
            }

            // mustContainAny (objeto de área)
            if (!empty($mustNorm)) {
                $hitObj = false;
                foreach ($mustNorm as $m) {
                    if ($m !== '' && str_contains($blob, $m)) { $hitObj = true; break; }
                }
                if (!$hitObj) {
                    $rejected[] = $it;
                    $reasons[] = "Rechazado por NO mencionar objeto del área en item '{$title}'";
                    continue;
                }
            }

            $kept[] = $it;
        }

        return [
            'kept' => $kept,
            'rejected' => $rejected,
            'rejected_reasons' => array_slice($reasons, 0, 12),
        ];
    }

    private function formatRejectFeedback(array $reasons): string
    {
        if (empty($reasons)) return "- (sin feedback previo)";
        $lines = [];
        foreach ($reasons as $r) {
            $lines[] = "- ".$r;
        }
        return implode("\n", array_slice($lines, 0, 12));
    }

    /**
     * Keywords heurísticas (sin IA) para amarrar.
     */
    private function extractKeywords(string $title, string $description): array
    {
        $text = trim($title.' '.$description);
        $text = $this->norm($text);

        // stopwords básicas ES
        $stop = array_flip([
            'de','la','el','los','las','y','o','u','a','en','con','sin','para','por','del','al',
            'un','una','unos','unas','se','que','es','son','ser','todo','toda','todos','todas',
            'etc','et','c','p','pm','am','opcional','media','alta','baja'
        ]);

        $text = preg_replace('/[^a-z0-9\s\-]/', ' ', $text) ?? $text;
        $parts = preg_split('/\s+/', $text) ?: [];

        $bag = [];
        foreach ($parts as $w) {
            $w = trim($w);
            if ($w === '' || strlen($w) < 3) continue;
            if (isset($stop[$w])) continue;
            $bag[] = $w;
        }

        // frecuencia simple
        $freq = [];
        foreach ($bag as $w) $freq[$w] = ($freq[$w] ?? 0) + 1;
        arsort($freq);

        $top = array_keys($freq);
        $top = array_slice($top, 0, 8);

        // si está muy pobre
        if (count($top) < 4) {
            $top = array_slice(array_unique($bag), 0, 8);
        }

        return array_values($top);
    }

    /**
     * Boost por área: agrega palabras que suelen ser objetos concretos del área
     * (NO mezcla entre áreas; solo suma vocabulario del área actual)
     */
    private function areaBoostKeywords(string $area, array $keywords): array
    {
        $a = $this->normalizeArea($area);
        $boost = [];

        if ($a === 'sistemas') {
            $boost = ['campo','validacion','tabla','consecutivo','prefijo','seccion','folio','nomenclatura'];
        } elseif ($a === 'licitaciones') {
            $boost = ['bases','anexo','formato','vigencia','firma','sello','matriz','requisito'];
        } elseif ($a === 'ventas') {
            $boost = ['cotizacion','propuesta','precio','vigencia','cliente','condiciones'];
        } elseif ($a === 'compras') {
            $boost = ['proveedor','cotizacion','comparativo','garantia','especificacion','modelo'];
        } elseif ($a === 'almacen') {
            $boost = ['sku','conteo','recepcion','ubicacion','surtido','empaque'];
        } elseif ($a === 'logistica') {
            $boost = ['guia','tracking','recoleccion','entrega','paqueteria','pod'];
        } elseif ($a === 'administracion') {
            $boost = ['factura','cfdi','uuid','rfc','pago','monto','contrato'];
        } elseif ($a === 'mantenimiento') {
            $boost = ['diagnostico','prueba','refaccion','calibracion','falla','equipo'];
        }

        $mix = array_merge($keywords, $boost);
        $mix = array_values(array_unique(array_filter($mix, fn($x) => is_string($x) && trim($x) !== '')));
        return array_slice($mix, 0, 10);
    }

    private function norm(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $s);
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
        return $s;
    }

    private function fallbackChecklist(string $ticketTitle, string $area, string $reason): array
    {
        Log::info('Checklist IA fallback', [
            'area' => $area,
            'title' => $ticketTitle,
            'reason' => $reason,
        ]);

        $keywords = $this->areaBoostKeywords($area, $this->extractKeywords($ticketTitle, ''));

        return [
            'title' => "Checklist para {$ticketTitle}",
            'keywords' => array_slice($keywords, 0, 8),
            'items' => array_slice($this->fallbackItemsByArea($area, $ticketTitle), 0, 10),
            'meta' => [
                'area_normalized' => $this->normalizeArea($area),
                'fallback_reason' => $reason,
            ],
        ];
    }

    /**
     * Fallback específico por área (NO plantilla genérica mezclada).
     */
    private function fallbackItemsByArea(string $area, string $ticketTitle): array
    {
        $a = $this->normalizeArea($area);

        if ($a === 'licitaciones') {
            return [
                ['title'=>'Ubicar bases, anexos y formatos obligatorios', 'detail'=>'Asegurar paquete completo (bases + anexos + formatos).', 'recommended'=>true],
                ['title'=>'Armar matriz de cumplimiento de requisitos', 'detail'=>'Listar requisito por requisito y marcar evidencia.', 'recommended'=>true],
                ['title'=>'Verificar vigencias de constancias/documentos', 'detail'=>'Fechas vigentes según convocatoria.', 'recommended'=>true],
                ['title'=>'Validar firmas/sellos y representación legal', 'detail'=>'Que firmantes/poderes correspondan a lo exigido.', 'recommended'=>true],
                ['title'=>'Revisar consistencia de razón social/RFC/domicilio', 'detail'=>'Que coincida en todo el expediente.', 'recommended'=>true],
                ['title'=>'Validar PDFs completos y legibles', 'detail'=>'Sin páginas faltantes y con firmas visibles.', 'recommended'=>true],
                ['title'=>'Detectar faltantes concretos por requisito', 'detail'=>'Documento X faltante / anexo Y incompleto / vigencia vencida.', 'recommended'=>true],
                ['title'=>'Corregir/solicitar reposición de documentos específicos', 'detail'=>'Sustituir PDFs o anexos puntuales detectados.', 'recommended'=>true],
            ];
        }

        if ($a === 'sistemas') {
            return [
                ['title'=>"Definir secciones a foliar según ticket", 'detail'=>'Ej: Proveedores, Clientes, Documentos (y otras secciones reales del sistema).', 'recommended'=>true],
                ['title'=>'Definir formato de nomenclatura por sección', 'detail'=>'Ej: PVD-000001, CLI-000001, DOC-000001 (prefijo + consecutivo).', 'recommended'=>true],
                ['title'=>'Definir regla de consecutivo', 'detail'=>'Global vs por sección vs por año; documentar regla en el ticket.', 'recommended'=>true],
                ['title'=>'Agregar/ajustar campos o tabla para folio', 'detail'=>'Campo folio + sección + restricción UNIQUE para evitar duplicados.', 'recommended'=>true],
                ['title'=>'Implementar generación automática de folio', 'detail'=>'Al crear registro: asignar prefijo + consecutivo correspondiente.', 'recommended'=>true],
                ['title'=>'Script para foliar registros existentes', 'detail'=>'Backfill sin colisiones; registrar conteos por sección.', 'recommended'=>true],
                ['title'=>'Validaciones de colisión/duplicado', 'detail'=>'Si existe folio, bloquear o recalcular según regla.', 'recommended'=>true],
                ['title'=>'UI para visualizar/filtrar por folio', 'detail'=>'Mostrar folio en listados y detalle; filtro por sección.', 'recommended'=>true],
                ['title'=>'Exportación/consulta para auditoría', 'detail'=>'Listado por sección con folios asignados (CSV/PDF si aplica).', 'recommended'=>true],
                ['title'=>'Pruebas con casos reales', 'detail'=>'Probar alta/edición/importación y validar que el consecutivo no se rompe.', 'recommended'=>true],
            ];
        }

        if ($a === 'ventas') {
            return [
                ['title'=>'Confirmar requisitos del cliente del ticket', 'detail'=>'Producto/servicio, cantidades, modelo y restricciones.', 'recommended'=>true],
                ['title'=>'Preparar cotización con vigencia', 'detail'=>'Precio, moneda, vigencia, condiciones.', 'recommended'=>true],
                ['title'=>'Validar condiciones comerciales', 'detail'=>'Entrega, forma de pago, garantías y tiempos.', 'recommended'=>true],
                ['title'=>'Alinear datos de cliente (razón social/RFC/dirección)', 'detail'=>'Que coincidan con OC o solicitud.', 'recommended'=>true],
                ['title'=>'Adjuntar ficha técnica/alcances específicos', 'detail'=>'Solo lo que aplica al producto/servicio del ticket.', 'recommended'=>true],
                ['title'=>'Confirmar aceptación del cliente', 'detail'=>'Aprobación de cotización o cambios solicitados.', 'recommended'=>true],
                ['title'=>'Registrar orden/OC si aplica', 'detail'=>'Capturar folio/OC y fechas.', 'recommended'=>true],
                ['title'=>'Confirmar fecha compromiso de entrega', 'detail'=>'Fecha real vs lead time.', 'recommended'=>true],
            ];
        }

        if ($a === 'compras') {
            return [
                ['title'=>'Definir especificación exacta de compra', 'detail'=>'Modelo/SKU, cantidades y compatibilidad.', 'recommended'=>true],
                ['title'=>'Solicitar/validar cotizaciones comparables', 'detail'=>'Mismo alcance, misma unidad y condiciones.', 'recommended'=>true],
                ['title'=>'Comparativo de proveedores', 'detail'=>'Precio, tiempo, garantía, condiciones.', 'recommended'=>true],
                ['title'=>'Validar garantía y políticas de devolución', 'detail'=>'Por proveedor y por producto.', 'recommended'=>true],
                ['title'=>'Confirmar tiempo de entrega y penalizaciones', 'detail'=>'Fechas comprometidas y riesgos.', 'recommended'=>true],
                ['title'=>'Seleccionar proveedor con criterio explícito', 'detail'=>'Justificación basada en comparativo.', 'recommended'=>true],
                ['title'=>'Generar OC con datos correctos', 'detail'=>'Razón social, términos, partida/SKU.', 'recommended'=>true],
                ['title'=>'Confirmar recepción esperada', 'detail'=>'Fecha, almacén destino, requisitos de entrega.', 'recommended'=>true],
            ];
        }

        if ($a === 'almacen') {
            return [
                ['title'=>'Preparar lista de SKUs/piezas del ticket', 'detail'=>'Con cantidades esperadas por SKU.', 'recommended'=>true],
                ['title'=>'Recepción y verificación contra documento', 'detail'=>'Comparar contra factura/remisión/OC.', 'recommended'=>true],
                ['title'=>'Conteo físico (idealmente doble verificación)', 'detail'=>'Registrar diferencias y evidencia.', 'recommended'=>true],
                ['title'=>'Ubicación en almacén', 'detail'=>'Asignar ubicaciones y etiquetado si aplica.', 'recommended'=>true],
                ['title'=>'Surtido según requerimiento', 'detail'=>'Picklist por SKU y cantidad.', 'recommended'=>true],
                ['title'=>'Verificación previa a empaque', 'detail'=>'Confirmar piezas correctas y completas.', 'recommended'=>true],
                ['title'=>'Empaque y etiquetado', 'detail'=>'Protección y rotulado conforme a envío.', 'recommended'=>true],
                ['title'=>'Registrar salida/transferencia', 'detail'=>'Folio de salida y responsable.', 'recommended'=>true],
            ];
        }

        if ($a === 'logistica') {
            return [
                ['title'=>'Confirmar dirección y ventana de entrega', 'detail'=>'Datos completos y restricciones de acceso.', 'recommended'=>true],
                ['title'=>'Definir paquetería/servicio', 'detail'=>'Costo, tiempo y cobertura.', 'recommended'=>true],
                ['title'=>'Generar guía', 'detail'=>'Peso/volumen correctos y referencias.', 'recommended'=>true],
                ['title'=>'Programar recolección', 'detail'=>'Fecha/hora y contacto de entrega.', 'recommended'=>true],
                ['title'=>'Tracking y monitoreo', 'detail'=>'Revisar estatus y alertas.', 'recommended'=>true],
                ['title'=>'Gestionar incidencias', 'detail'=>'Retardo, reintento, daño, domicilio incorrecto.', 'recommended'=>true],
                ['title'=>'Confirmar entrega y POD', 'detail'=>'Recibo/firma/evidencia de entrega.', 'recommended'=>true],
                ['title'=>'Cerrar envío con datos finales', 'detail'=>'Guía, fecha real, incidencia si hubo.', 'recommended'=>true],
            ];
        }

        if ($a === 'administracion') {
            return [
                ['title'=>'Validar datos fiscales', 'detail'=>'Razón social, RFC, domicilio fiscal, régimen si aplica.', 'recommended'=>true],
                ['title'=>'Preparar factura/CFDI', 'detail'=>'Conceptos, impuestos, uso CFDI, método/forma de pago.', 'recommended'=>true],
                ['title'=>'Validar monto y fecha vs orden/contrato', 'detail'=>'Que coincida con lo pactado.', 'recommended'=>true],
                ['title'=>'Emitir CFDI y obtener UUID', 'detail'=>'Guardar folio/UUID para referencia.', 'recommended'=>true],
                ['title'=>'Conciliar contra pago/estado de cuenta', 'detail'=>'Registrar pago y fecha de aplicación.', 'recommended'=>true],
                ['title'=>'Guardar soporte documental', 'detail'=>'Contrato/OC/CFDI/confirmación de pago.', 'recommended'=>true],
                ['title'=>'Registrar en sistema interno', 'detail'=>'Asiento/registro según procedimiento.', 'recommended'=>true],
                ['title'=>'Validar saldo y cierre', 'detail'=>'Que quede cerrado el movimiento del ticket.', 'recommended'=>true],
            ];
        }

        if ($a === 'mantenimiento') {
            return [
                ['title'=>'Levantar diagnóstico del equipo', 'detail'=>'Síntoma, condiciones y evidencia inicial.', 'recommended'=>true],
                ['title'=>'Identificar causa probable', 'detail'=>'Pruebas/lecturas específicas.', 'recommended'=>true],
                ['title'=>'Definir intervención', 'detail'=>'Refacción/calibración/ajuste requerido.', 'recommended'=>true],
                ['title'=>'Ejecutar mantenimiento correctivo/preventivo', 'detail'=>'Pasos técnicos aplicables.', 'recommended'=>true],
                ['title'=>'Calibrar si aplica', 'detail'=>'Parámetros y tolerancias.', 'recommended'=>true],
                ['title'=>'Prueba funcional', 'detail'=>'Checklist de funcionamiento y seguridad.', 'recommended'=>true],
                ['title'=>'Registrar lecturas finales', 'detail'=>'Antes/después para evidencia técnica.', 'recommended'=>true],
                ['title'=>'Criterio de aceptación', 'detail'=>'Qué significa “queda listo” para el ticket.', 'recommended'=>true],
            ];
        }

        // Unknown area: fallback sin mezclar, pero con objetos concretos genéricos
        return [
            ['title'=>"Listar objetos concretos del ticket: {$ticketTitle}", 'detail'=>'Documentos/campos/artefactos reales involucrados.', 'recommended'=>true],
            ['title'=>'Definir formato/estructura requerida', 'detail'=>'Cómo debe verse el entregable final.', 'recommended'=>true],
            ['title'=>'Preparar insumos necesarios', 'detail'=>'Datos/documentos exactos para ejecutar.', 'recommended'=>true],
            ['title'=>'Ejecutar acción principal del ticket', 'detail'=>'Realizar el cambio/entrega solicitada.', 'recommended'=>true],
            ['title'=>'Validar contra criterio del ticket', 'detail'=>'Indicar QUÉ se valida específicamente.', 'recommended'=>true],
            ['title'=>'Corregir inconsistencias concretas', 'detail'=>'Ajustes puntuales detectados.', 'recommended'=>true],
            ['title'=>'Evidencia mínima de cierre', 'detail'=>'Captura/listado/folio/archivo final según aplique.', 'recommended'=>true],
            ['title'=>'Cerrar ticket con entregable final', 'detail'=>'Entregable listo y verificable.', 'recommended'=>true],
        ];
    }
}