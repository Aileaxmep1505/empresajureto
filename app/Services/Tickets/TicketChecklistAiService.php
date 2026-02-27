<?php

namespace App\Services\Tickets;

use RuntimeException;
use Illuminate\Support\Facades\Log;

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
        $area = trim($area);

        // ✅ Prompt MUY estricto: JSON puro y nada más
        $prompt = $this->buildPrompt($title, $description, $area);

        // 🔁 Aquí debes llamar a TU proveedor/cliente de IA
        // La idea: $rawText = texto que devuelve el modelo (string)
        $rawText = $this->callAiProvider($prompt);

        if (!is_string($rawText) || trim($rawText) === '') {
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

        // 3) Si sigue fallando, fallback (mantengo tu mismo mensaje para que coincida con lo que ya viste)
        if (!$parsed || !is_array($parsed)) {
            Log::warning('Checklist IA: no se pudo parsear JSON', [
                'area' => $area,
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
     * ✅ Prompt estricto y ESPECÍFICO: JSON puro y nada más.
     * Evita pasos genéricos tipo "documentar / informar / notificar".
     */
    private function buildPrompt(string $title, string $description, string $area): string
    {
        $desc = trim($description) !== '' ? trim($description) : '(sin descripción)';

        return <<<PROMPT
Eres un asistente que crea un checklist TÉCNICO y ESPECÍFICO para ejecutar un ticket.
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

REGLAS MUY IMPORTANTES:
1) SOLO puedes proponer acciones DIRECTAMENTE relacionadas con el título y la descripción.
2) PROHIBIDO incluir tareas genéricas de proceso como:
   - documentar, informar, notificar, comunicar, reunión, seguimiento, retro, reporte, bitácora
   - "validar" sin decir QUÉ validar exactamente
   - "revisar" sin decir QUÉ revisar exactamente
3) Cada item debe ser una acción que alguien pueda ejecutar y terminar (deliverable claro).
4) NO inventes sistemas/módulos/tecnologías que no estén mencionados. Si no hay info suficiente, haz items mínimos y concretos.
5) Usa lenguaje de trabajo (ejemplos permitidos):
   - "Ajustar CSS de X", "Corregir ruta Y", "Agregar botón Z", "Cambiar texto en Blade", "Agregar validación en Request", etc.
6) Deben ser 6 a 10 items. Nada más.
7) "detail" debe ser corto (1–2 líneas) y aterrizado. Si no aplica, usa null.

PISTA PARA HACERLO MÁS ESPECÍFICO (OBLIGATORIO):
- Extrae del título 3–6 palabras clave (ej: "rediseñar vista tickets create") y conviértelas en subtareas directas.
- Si el título es "rediseñar", entonces enfócate SOLO en rediseño UI/UX de lo que el título diga (layout, spacing, responsive, colores, tipografía, componentes).
- No agregues pasos de "cierre" (documentación/notificación). Este checklist es SOLO para la ejecución.

Ahora responde con el JSON.
PROMPT;
    }

    /**
     * 👇 Reemplaza esta función con tu implementación real.
     * Debe regresar STRING con la respuesta del modelo.
     */
    private function callAiProvider(string $prompt): string
    {
        /**
         * EJEMPLO (NO USAR TAL CUAL):
         * return app(OpenAiClient::class)->chat($prompt);
         */
        if (function_exists('app')) {
            // Si ya tenías una integración previa, aquí debes conectarla.
        }

        // Si llegas aquí es que no lo conectaste: regresamos vacío para fallback.
        return '';
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

        $base = [
            ['title'=>'Identificar componente exacto a modificar', 'detail'=>'Con base en el título/descr: vista, módulo o sección.', 'recommended'=>true],
            ['title'=>'Ubicar archivos involucrados', 'detail'=>'Blade / Controller / CSS / rutas relacionadas.', 'recommended'=>true],
            ['title'=>'Aplicar cambios puntuales', 'detail'=>'Solo lo solicitado, sin agregar extras.', 'recommended'=>true],
            ['title'=>'Ajustar responsive', 'detail'=>'Verificar móvil y desktop en la misma vista.', 'recommended'=>true],
            ['title'=>'Verificar que no se rompa el flujo', 'detail'=>'Crear/editar/guardar si aplica.', 'recommended'=>true],
            ['title'=>'Pulir detalles visuales', 'detail'=>'Espaciados, alineación, textos.', 'recommended'=>true],
        ];

        $map = [
            'sistemas' => [
                ['title'=>'Reproducir el problema exacto', 'detail'=>'Identificar ruta/vista/paso donde falla o dónde se modifica.', 'recommended'=>true],
                ['title'=>'Aplicar cambio en el archivo correcto', 'detail'=>'Editar Blade/JS/CSS/Controller exactamente relacionado.', 'recommended'=>true],
                ['title'=>'Probar el caso principal', 'detail'=>'Verificar que el caso solicitado funciona end-to-end.', 'recommended'=>true],
                ['title'=>'Probar regresión rápida', 'detail'=>'Revisar que no rompió algo cercano (listado, show, work, etc.).', 'recommended'=>true],
            ],
            'ventas' => [
                ['title'=>'Validar datos específicos del ticket', 'detail'=>'Cliente/requerimiento/cantidad/fechas si aplica.', 'recommended'=>true],
                ['title'=>'Preparar el entregable solicitado', 'detail'=>'Cotización / respuesta / seguimiento del caso en sistema.', 'recommended'=>true],
            ],
            'compras' => [
                ['title'=>'Confirmar especificaciones exactas', 'detail'=>'Modelo/marca/cantidad/compatibilidad según ticket.', 'recommended'=>true],
                ['title'=>'Solicitar y comparar cotizaciones', 'detail'=>'Precio/tiempo/condiciones según necesidad del ticket.', 'recommended'=>true],
            ],
            'almacen' => [
                ['title'=>'Ubicar producto exacto', 'detail'=>'Encontrar físicamente y validar estado/accesorios.', 'recommended'=>true],
                ['title'=>'Preparar entrega/recepción', 'detail'=>'Empaque y verificación rápida.', 'recommended'=>true],
            ],
            'logistica' => [
                ['title'=>'Confirmar dirección y ventana de entrega', 'detail'=>'Datos concretos para ejecutar el envío.', 'recommended'=>true],
                ['title'=>'Generar guía y coordinar envío', 'detail'=>'Paquetería/horario/folio.', 'recommended'=>true],
            ],
            'licitaciones' => [
                ['title'=>'Revisar requisitos específicos', 'detail'=>'Documentos obligatorios y fechas del caso.', 'recommended'=>true],
                ['title'=>'Armar expediente con checklist técnico', 'detail'=>'Solo requisitos aplicables al caso.', 'recommended'=>true],
            ],
            'administracion' => [
                ['title'=>'Revisar soporte exacto', 'detail'=>'Facturas/contratos/solicitudes relacionadas.', 'recommended'=>true],
                ['title'=>'Registrar movimiento en sistema', 'detail'=>'Capturar folio o cambio necesario.', 'recommended'=>true],
            ],
            'mantenimiento' => [
                ['title'=>'Diagnóstico puntual', 'detail'=>'Síntomas/pruebas específicas del equipo.', 'recommended'=>true],
                ['title'=>'Ejecutar mantenimiento', 'detail'=>'Correctivo/preventivo según ticket.', 'recommended'=>true],
                ['title'=>'Prueba funcional', 'detail'=>'Confirmar operación final.', 'recommended'=>true],
            ],
        ];

        // Primero items del área, luego base
        return array_values(array_merge($map[$area] ?? [], $base));
    }
}