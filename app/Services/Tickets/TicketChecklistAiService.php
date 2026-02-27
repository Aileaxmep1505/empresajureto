<?php

namespace App\Services\Tickets;

use RuntimeException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TicketChecklistAiService
{
    /**
     * Genera checklist con IA y regresa un array listo para JSON response:
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

        // ✅ Normaliza el área (evita que todo caiga en "sistemas" por variantes)
        $area = $this->normalizeArea($areaOriginal);

        // ✅ Prompt por área (NO sesgado a sistemas)
        $prompt = $this->buildPrompt($title, $description, $area);

        // ✅ Llamada real a IA (Responses API) -> regresa string JSON
        $rawText = $this->callAiProvider($prompt);

        if (!is_string($rawText) || trim($rawText) === '') {
            // Si de plano vino vacío, fallback por área
            return $this->fallbackChecklist($title, $area, 'Respuesta IA vacía');
        }

        // 1) Intento directo JSON
        $parsed = $this->tryJsonDecode($rawText);

        // 2) Si falla, intentar extraer bloque JSON embebido
        if (!$parsed) {
            $jsonBlock = $this->extractFirstJsonObject($rawText);
            if ($jsonBlock) {
                $parsed = $this->tryJsonDecode($jsonBlock);
            }
        }

        // 3) Si sigue fallando, error (igual a tu comportamiento) + fallback opcional si prefieres
        if (!$parsed || !is_array($parsed)) {
            Log::warning('Checklist IA: no se pudo parsear JSON', [
                'area' => $area,
                'area_original' => $areaOriginal,
                'title' => $title,
                'snippet' => mb_substr($rawText, 0, 800),
            ]);

            throw new RuntimeException('No se pudo parsear JSON del checklist IA.');
        }

        // ✅ Normalizar estructura
        $out = $this->normalizeChecklistPayload($parsed, $title, $area);

        // ✅ Si por alguna razón queda vacío, fallback suave
        if (empty($out['items'])) {
            return $this->fallbackChecklist($title, $area, 'Checklist vacío tras normalización');
        }

        // ✅ límite para UI
        $out['items'] = array_slice($out['items'], 0, 12);

        return $out;
    }

    /**
     * ✅ Prompt estricto y ESPECÍFICO: JSON puro.
     * Además: guía por área para evitar que “Ventas/Compras/Almacén” terminen con tareas tipo Blade/CSS.
     */
    private function buildPrompt(string $title, string $description, string $area): string
    {
        $desc = trim($description) !== '' ? trim($description) : '(sin descripción)';

        $areaGuide = $this->areaGuidance($area);

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

REGLAS MUY IMPORTANTES:
1) SOLO puedes proponer acciones DIRECTAMENTE relacionadas con el título y la descripción.
2) PROHIBIDO incluir tareas genéricas de proceso como:
   - documentar, informar, notificar, comunicar, reunión, seguimiento, retro, reporte, bitácora
   - "validar" sin decir QUÉ validar exactamente
   - "revisar" sin decir QUÉ revisar exactamente
3) Cada item debe ser una acción que alguien pueda ejecutar y terminar (deliverable claro).
4) NO inventes sistemas/módulos/tecnologías que no estén mencionados.
   Si no hay info suficiente, haz items mínimos pero concretos y ejecutables para el área.
5) Deben ser 6 a 10 items. Nada más.
6) "detail" debe ser corto (1–2 líneas) y aterrizado. Si no aplica, usa null.
7) Evita "pasos de cierre" (documentación/notificación). Este checklist es SOLO ejecución.

PISTA PARA HACERLO MÁS ESPECÍFICO (OBLIGATORIO):
- Extrae del título 3–6 palabras clave y conviértelas en subtareas directas.
- Si el título es "rediseñar", entonces enfócate SOLO en UI/UX del elemento citado (layout, spacing, responsive, colores, tipografía, componentes).

Ahora responde con el JSON.
PROMPT;
    }

    /**
     * ✅ Llamada real a OpenAI Responses API usando Structured Outputs (JSON Schema)
     * Docs:
     * - Responses API: :contentReference[oaicite:0]{index=0}
     * - Migración /v1/responses recomendado: :contentReference[oaicite:1]{index=1}
     */
    private function callAiProvider(string $prompt): string
    {
        $apiKey = (string) env('OPENAI_API_KEY', '');
        if ($apiKey === '') {
            Log::warning('Checklist IA: falta OPENAI_API_KEY');
            return '';
        }

        $model = (string) env('OPENAI_MODEL', 'gpt-5-mini');

        // ✅ JSON Schema (strict) para forzar estructura
        $schema = $this->checklistJsonSchema();

        $payload = [
            'model' => $model,
            // input puede ser string directo (texto) :contentReference[oaicite:2]{index=2}
            'input' => $prompt,

            // ✅ Structured Outputs: text.format = json_schema :contentReference[oaicite:3]{index=3}
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'ticket_checklist',
                    'description' => 'Checklist técnico por área para ejecutar un ticket. JSON estricto.',
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],

            // Opcional: baja variación para consistencia
            'temperature' => 0.3,

            // No herramientas
            'tool_choice' => 'none',
        ];

        try {
            $res = Http::timeout(30)
                ->withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->post('https://api.openai.com/v1/responses', $payload);

            if (!$res->ok()) {
                Log::warning('Checklist IA: OpenAI no OK', [
                    'status' => $res->status(),
                    'body' => mb_substr((string) $res->body(), 0, 1200),
                ]);
                return '';
            }

            $data = $res->json();

            // ✅ La respuesta trae "output" con items tipo "message" y "output_text"
            // (extraemos el primer output_text que contenga JSON)
            $text = $this->extractFirstOutputText($data);

            return is_string($text) ? trim($text) : '';
        } catch (\Throwable $e) {
            Log::error('Checklist IA: error llamando OpenAI', [
                'msg' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Extrae el primer "output_text.text" de la Responses API.
     */
    private function extractFirstOutputText($data): ?string
    {
        if (!is_array($data)) return null;

        // Caso común: $data['output'] = [ { type:"message", content:[{type:"output_text", text:"..."}] } ]
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

        // Fallback por si cambió el shape o viene directo
        if (isset($data['text']) && is_string($data['text'])) return $data['text'];

        return null;
    }

    /**
     * JSON Schema estricto para checklist.
     */
    private function checklistJsonSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['title', 'items'],
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'minLength' => 3,
                    'maxLength' => 140,
                ],
                'items' => [
                    'type' => 'array',
                    'minItems' => 6,
                    'maxItems' => 10,
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['title', 'detail', 'recommended'],
                        'properties' => [
                            'title' => [
                                'type' => 'string',
                                'minLength' => 3,
                                'maxLength' => 120,
                            ],
                            'detail' => [
                                'anyOf' => [
                                    ['type' => 'string', 'minLength' => 3, 'maxLength' => 220],
                                    ['type' => 'null'],
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

    /**
     * ✅ Normaliza el área para que de verdad se use “todas las áreas”
     * y no te llegue variado y caiga mal.
     */
    private function normalizeArea(string $area): string
    {
        $a = mb_strtolower(trim($area));

        // quita dobles espacios
        $a = preg_replace('/\s+/u', ' ', $a) ?? $a;

        // sin acentos (básico)
        $a = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $a);

        $map = [
            // sistemas
            'ti' => 'sistemas',
            'it' => 'sistemas',
            'sistema' => 'sistemas',
            'sistemas' => 'sistemas',
            'soporte' => 'sistemas',
            'soporte tecnico' => 'sistemas',
            'desarrollo' => 'sistemas',

            // ventas
            'venta' => 'ventas',
            'ventas' => 'ventas',
            'comercial' => 'ventas',

            // compras
            'compra' => 'compras',
            'compras' => 'compras',
            'abastecimiento' => 'compras',

            // almacén
            'almacen' => 'almacen',
            'almacen general' => 'almacen',
            'bodega' => 'almacen',
            'inventarios' => 'almacen',

            // logística
            'logistica' => 'logistica',
            'envios' => 'logistica',
            'embarques' => 'logistica',

            // licitaciones
            'licitacion' => 'licitaciones',
            'licitaciones' => 'licitaciones',

            // administración
            'administracion' => 'administracion',
            'admin' => 'administracion',
            'contabilidad' => 'administracion',
            'finanzas' => 'administracion',

            // mantenimiento
            'mantenimiento' => 'mantenimiento',
            'servicio' => 'mantenimiento',
            'tecnico' => 'mantenimiento',
        ];

        return $map[$a] ?? trim($area); // si no match, respeta lo que venga
    }

    /**
     * Guía concreta por área para que la IA NO se vaya a “Blade/CSS” cuando no toca.
     */
    private function areaGuidance(string $area): string
    {
        $a = mb_strtolower(trim($area));
        $a = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $a);

        $guides = [
            'sistemas' => "- Enfócate en tareas técnicas de software: vistas, rutas, controladores, consultas, validaciones, UI/UX del sistema.\n- Ejemplos válidos: ajustar Blade/CSS, corregir validación Request, corregir query, arreglar export PDF/Excel, corregir permiso/rol, etc.",
            'ventas' => "- Enfócate en ejecución comercial: cotización, propuesta, seguimiento al cliente, confirmación de requisitos, entrega de información concreta.\n- Ejemplos válidos: armar cotización con partidas, confirmar cantidades/modelos, calcular totales/IVA si aplica, coordinar aprobación del cliente, preparar orden/folio en sistema (si el ticket lo menciona).",
            'compras' => "- Enfócate en abastecimiento: especificaciones, comparación de proveedores, tiempos, condiciones, compatibilidad.\n- Ejemplos válidos: solicitar 2–3 cotizaciones, comparar lead time, validar compatibilidad modelo/serie, confirmar existencia, preparar OC (si se menciona).",
            'almacen' => "- Enfócate en operaciones de almacén: ubicación, conteo, surtido, recepción, empaque, verificación de piezas.\n- Ejemplos válidos: localizar SKU, conteo físico, revisar accesorios, preparar surtido, etiquetar/embalar, registrar entrada/salida (si se menciona).",
            'logistica' => "- Enfócate en ejecución de envío/entrega: dirección, ventana, guía, recolección, evidencia de entrega.\n- Ejemplos válidos: confirmar datos de entrega, cotizar paquetería, generar guía, programar recolección, confirmar estatus en tránsito.",
            'licitaciones' => "- Enfócate en requisitos y expediente: documentos obligatorios, formatos, fechas, armado de entregables.\n- Ejemplos válidos: checklist de requisitos, armar carpeta por apartado, validar vigencias, preparar anexos específicos.",
            'administracion' => "- Enfócate en ejecución administrativa: facturas, pagos, contratos, registros internos.\n- Ejemplos válidos: validar CFDI/UUID (si aplica), preparar factura/recibo, registrar póliza/movimiento, revisar soporte del ticket.",
            'mantenimiento' => "- Enfócate en servicio técnico de equipo: diagnóstico, refacción, calibración, prueba funcional.\n- Ejemplos válidos: pruebas específicas, inspección de componente, reemplazo, ajuste, prueba final, evidencia técnica (solo si el ticket lo pide).",
        ];

        return $guides[$a] ?? "- Enfócate en tareas ejecutables propias del área indicada. No uses ejemplos de sistemas si no aplica.";
    }

    /**
     * json_decode tolerante: limpia BOM y espacios.
     */
    private function tryJsonDecode(string $raw): ?array
    {
        $raw = trim($raw);

        // Quitar BOM si existe
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;

        // Quitar fences comunes si el modelo insistió
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

    /**
     * Extrae el primer objeto JSON {...} de un texto, incluso si hay texto antes/después.
     */
    private function extractFirstJsonObject(string $text): ?string
    {
        $text = trim($text);
        if ($text === '') return null;

        // Si viene como ```json ... ```
        if (preg_match('/```json\s*(\{.*\})\s*```/is', $text, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/```\s*(\{.*\})\s*```/is', $text, $m)) {
            return trim($m[1]);
        }

        // Búsqueda por balanceo de llaves (robusto)
        $start = strpos($text, '{');
        if ($start === false) return null;

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

    /**
     * Normaliza a tu estructura final.
     */
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

        // Si la IA devolvió muy pocos, completamos suave según área (sin "documentar/notificar")
        if (count($items) < 4) {
            $items = array_merge($items, $this->fallbackItemsByArea($area));
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
     * Fallback general si falla IA.
     */
    private function fallbackChecklist(string $ticketTitle, string $area, string $reason): array
    {
        Log::info('Checklist IA fallback', [
            'area' => $area,
            'title' => $ticketTitle,
            'reason' => $reason,
        ]);

        return [
            'title' => "Checklist para {$ticketTitle}",
            'items' => array_slice($this->fallbackItemsByArea($area), 0, 10),
        ];
    }

    /**
     * ✅ Fallback sin "documentar/informar/notificar".
     * Enfocado a ejecución y entregables.
     */
    private function fallbackItemsByArea(string $area): array
    {
        $area = mb_strtolower(trim($area));
        $area = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $area);

        $base = [
            ['title'=>'Identificar alcance exacto del ticket', 'detail'=>'Delimitar qué se va a entregar (resultado final).', 'recommended'=>true],
            ['title'=>'Extraer palabras clave del título', 'detail'=>'Convertir 3–6 keywords en subtareas concretas.', 'recommended'=>true],
            ['title'=>'Ejecutar el entregable principal', 'detail'=>'Resolver lo solicitado sin agregar pasos de proceso genérico.', 'recommended'=>true],
            ['title'=>'Verificar el resultado contra el pedido', 'detail'=>'Confirmar que cumple el título/descr tal cual.', 'recommended'=>true],
            ['title'=>'Resolver detalles restantes', 'detail'=>'Ajustes finos necesarios para que quede cerrado.', 'recommended'=>true],
            ['title'=>'Prueba final del flujo', 'detail'=>'Probar escenario real (según el área).', 'recommended'=>true],
        ];

        $map = [
            'sistemas' => [
                ['title'=>'Reproducir el problema exacto', 'detail'=>'Identificar ruta/vista/paso donde falla o dónde se modifica.', 'recommended'=>true],
                ['title'=>'Ubicar archivos involucrados', 'detail'=>'Blade/JS/CSS/Controller/Routes relacionados.', 'recommended'=>true],
                ['title'=>'Aplicar cambio en el archivo correcto', 'detail'=>'Editar exactamente lo relacionado con el ticket.', 'recommended'=>true],
                ['title'=>'Probar caso principal end-to-end', 'detail'=>'Crear/editar/guardar/visualizar según aplique.', 'recommended'=>true],
            ],
            'ventas' => [
                ['title'=>'Confirmar requerimiento exacto del cliente', 'detail'=>'Cantidad, modelo, fechas y condición solicitada.', 'recommended'=>true],
                ['title'=>'Armar entregable comercial', 'detail'=>'Cotización/propuesta/respuesta con datos concretos del ticket.', 'recommended'=>true],
                ['title'=>'Validar números clave', 'detail'=>'Totales, IVA si aplica, vigencia si el ticket lo pide.', 'recommended'=>true],
            ],
            'compras' => [
                ['title'=>'Confirmar especificaciones exactas', 'detail'=>'Modelo/marca/cantidad/compatibilidad según ticket.', 'recommended'=>true],
                ['title'=>'Solicitar cotizaciones comparables', 'detail'=>'Mismo alcance: precio, lead time, condiciones.', 'recommended'=>true],
                ['title'=>'Seleccionar opción y preparar compra', 'detail'=>'Dejar lista la opción ganadora según criterios del ticket.', 'recommended'=>true],
            ],
            'almacen' => [
                ['title'=>'Ubicar producto o material', 'detail'=>'Localizar y confirmar existencia/estado/accesorios.', 'recommended'=>true],
                ['title'=>'Preparar surtido o recepción', 'detail'=>'Conteo, empaque, etiquetas, verificación rápida.', 'recommended'=>true],
                ['title'=>'Registrar movimiento si aplica', 'detail'=>'Entrada/salida/ajuste solo si el ticket lo menciona.', 'recommended'=>true],
            ],
            'logistica' => [
                ['title'=>'Confirmar dirección y ventana', 'detail'=>'Datos completos para ejecutar el envío/entrega.', 'recommended'=>true],
                ['title'=>'Generar guía y coordinar', 'detail'=>'Paquetería, recolección, folio/guía.', 'recommended'=>true],
                ['title'=>'Confirmar estatus de envío', 'detail'=>'Seguimiento operativo (sin reportes genéricos).', 'recommended'=>true],
            ],
            'licitaciones' => [
                ['title'=>'Listar requisitos aplicables', 'detail'=>'Documentos y formatos obligatorios del caso.', 'recommended'=>true],
                ['title'=>'Armar expediente por secciones', 'detail'=>'Organizar anexos y evidencias requeridas.', 'recommended'=>true],
                ['title'=>'Validar vigencias críticas', 'detail'=>'Fechas, firmas, sellos, formatos requeridos.', 'recommended'=>true],
            ],
            'administracion' => [
                ['title'=>'Revisar soporte exacto', 'detail'=>'Factura/contrato/solicitud ligada al ticket.', 'recommended'=>true],
                ['title'=>'Preparar movimiento administrativo', 'detail'=>'Factura/pago/registro según aplique al ticket.', 'recommended'=>true],
                ['title'=>'Verificar consistencia de datos', 'detail'=>'Folio, montos, RFC/UUID si aplica y si fue mencionado.', 'recommended'=>true],
            ],
            'mantenimiento' => [
                ['title'=>'Diagnóstico puntual', 'detail'=>'Pruebas específicas según síntomas del ticket.', 'recommended'=>true],
                ['title'=>'Ejecutar intervención', 'detail'=>'Correctivo/preventivo/refacción según ticket.', 'recommended'=>true],
                ['title'=>'Prueba funcional final', 'detail'=>'Confirmar operación y resolver fallas remanentes.', 'recommended'=>true],
            ],
        ];

        // Primero items del área, luego base
        return array_values(array_merge($map[$area] ?? [], $base));
    }
}