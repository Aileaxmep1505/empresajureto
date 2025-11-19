<?php

namespace App\Services;

use OpenAI;
use Illuminate\Support\Facades\Log;

class LicitacionOpenAIService
{
    /**
     * Cliente de OpenAI (solo como respaldo).
     *
     * @var \OpenAI\Client|null
     */
    protected $client;

    public function __construct()
    {
        $key = config('services.openai.key', env('OPENAI_API_KEY'));

        if ($key) {
            $this->client = OpenAI::client($key);
        } else {
            $this->client = null;
        }
    }

    /**
     * Convierte el texto de una tabla de licitación en items estructurados.
     *
     * 1) Intenta primero un parser DETERMNÍSTICO hecho a la medida del formato
     *    de los anexos (PARTIDA / CLAVE / ESPECIFICACIONES / CANTIDAD / UNIDAD).
     * 2) Si no logra extraer nada, usa OpenAI como respaldo.
     *
     * @param  string  $textoTabla  Texto crudo de la tabla (de una requisición)
     * @param  string  $requisicion Requisición detectada (ej. CA-0232-2025)
     * @return array   Arreglo de items normalizados listos para BD / Excel
     */
    public function extraerItemsDesdeTabla(string $textoTabla, string $requisicion): array
    {
        // 1) Parser hecho a mano (formato Gobierno EdoMex)
        $items = $this->parseFormatoGobierno($textoTabla, $requisicion);

        if (!empty($items)) {
            return $items;
        }

        // 2) Respaldo con OpenAI (por si llega otro formato distinto)
        if (!$this->client) {
            // Sin API key, no podemos llamar a OpenAI
            return [];
        }

        return $this->extraerConOpenAI($textoTabla, $requisicion);
    }

    /**
     * Parser DETERMNÍSTICO para el formato:
     *
     * PARTIDA
     * <número>
     * <CLAVE> <DESCRIPCIÓN - ESPECIFICACIONES...>
     * ...
     * <CANTIDAD> <UNIDAD>
     */
    protected function parseFormatoGobierno(string $textoTabla, string $requisicion): array
    {
        $linesRaw = preg_split("/\r\n|\n|\r/", $textoTabla);
        $lines    = [];

        // 1) Limpiamos líneas vacías y quitamos encabezados repetidos
        foreach ($linesRaw as $line) {
            $trim = trim($line);
            if ($trim === '') {
                continue;
            }

            $upper = mb_strtoupper($trim, 'UTF-8');

            // Encabezados típicos que no son datos
            if (str_contains($upper, 'PARTIDA CLAVE DE VERIFICACIÓN')
                || str_contains($upper, 'PARTIDA CLAVE DE VERIFICACION')
                || str_starts_with($upper, 'PARTIDA')
                || str_starts_with($upper, 'UNIDAD DE')
                || str_starts_with($upper, 'CANTIDAD')
            ) {
                continue;
            }

            // Encabezado de requisición ya viene por fuera
            if (str_starts_with($upper, 'REQUISICIÓN') || str_starts_with($upper, 'REQUISICION')) {
                continue;
            }

            $lines[] = $trim;
        }

        if (empty($lines)) {
            return [];
        }

        // 2) Detectamos líneas que son solo el número de PARTIDA
        $partidaIndices = [];
        foreach ($lines as $i => $line) {
            if (preg_match('/^\d{1,3}$/', $line)) {
                $partidaIndices[] = $i;
            }
        }

        if (empty($partidaIndices)) {
            return [];
        }

        $items = [];

        // 3) Para cada PARTIDA, tomamos el bloque hasta la siguiente PARTIDA
        for ($idx = 0; $idx < count($partidaIndices); $idx++) {
            $start = $partidaIndices[$idx];
            $end   = ($idx + 1 < count($partidaIndices))
                ? $partidaIndices[$idx + 1]
                : count($lines);

            $chunkLines = array_slice($lines, $start, $end - $start);
            if (count($chunkLines) < 2) {
                continue;
            }

            // Línea 0: número de partida
            $partidaLine = array_shift($chunkLines);
            $partidaNum  = trim($partidaLine);

            // Primera línea útil después de partida (puede haber líneas vacías, ya las quitamos antes)
            $firstDataLine = array_shift($chunkLines);
            if ($firstDataLine === null) {
                continue;
            }

            $clave = '';
            $bodyFirst = $firstDataLine;

            // Posible formato: "2111013368 CLIP PLASTIFICADO ..."
            if (preg_match('/^(\d{6,})\s+(.*)$/u', $firstDataLine, $m)) {
                $clave      = $m[1];
                $bodyFirst  = $m[2]; // resto de la línea sin la clave
            }

            // 4) Buscar la línea de CANTIDAD + UNIDAD desde abajo
            $cantidad = 0.0;
            $unidad   = '';
            $qtyIndex = null;

            for ($i = count($chunkLines) - 1; $i >= 0; $i--) {
                $candidate = trim($chunkLines[$i]);

                // patrón tipo: "1,000 PAQUETE", "300 CAJA", "3,000  CAJA"
                if (preg_match('/^(\d[\d.,]*)\s+([A-ZÁÉÍÓÚÑa-záéíóúñ\/]+)\s*$/u', $candidate, $m)) {
                    $qtyStr = str_replace([',', ' '], '', $m[1]);
                    $cantidad = (float) $qtyStr;
                    $unidad   = mb_strtoupper($m[2], 'UTF-8');
                    $qtyIndex = $i;
                    break;
                }
            }

            if ($qtyIndex === null) {
                // No detectamos cantidad/unidad, saltamos este bloque
                continue;
            }

            // 5) El cuerpo descriptivo son todas las líneas entre
            //    bodyFirst y la línea anterior a cantidad
            $bodyLines = [$bodyFirst];
            for ($i = 0; $i < $qtyIndex; $i++) {
                $bodyLines[] = trim($chunkLines[$i]);
            }

            // Unimos todo en un solo texto
            $bodyText = trim(preg_replace('/\s+/', ' ', implode(' ', $bodyLines)));

            // 6) Separamos DESCRIPCIÓN y ESPECIFICACIONES
            $descripcion      = $bodyText;
            $especificaciones = '';

            if (str_contains($bodyText, ' - ')) {
                [$descripcion, $especificaciones] = explode(' - ', $bodyText, 2);
                $descripcion      = trim($descripcion);
                $especificaciones = trim($especificaciones);
            } elseif (preg_match('/^(.+?\.)\s*(.+)$/u', $bodyText, $m)) {
                // Primer oración como nombre, resto como especificaciones
                $descripcion      = trim($m[1]);
                $especificaciones = trim($m[2]);
            }

            // 7) Normalizamos el item
            $items[] = [
                'requisicion'        => (string) ($requisicion ?: ''),
                'partida'            => (string) $partidaNum,
                'clave_verificacion' => $clave !== '' ? $clave : 'SIN-CLAVE',
                'descripcion'        => $descripcion,
                'especificaciones'   => $especificaciones,
                'cantidad'           => $cantidad,
                'unidad'             => $unidad,
            ];
        }

        return $items;
    }

    /**
     * Respaldo: usa OpenAI para extraer items cuando el parser
     * determinístico no encuentra nada útil.
     */
    protected function extraerConOpenAI(string $textoTabla, string $requisicion): array
    {
        // JSON Schema del resultado que queremos
        $schema = [
            'type'       => 'object',
            'properties' => [
                'items' => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'requisicion'        => ['type' => 'string'],
                            'partida'            => ['type' => 'string'],
                            'clave_verificacion' => ['type' => 'string'],
                            'descripcion'        => ['type' => 'string'],
                            'especificaciones'   => ['type' => 'string'],
                            'cantidad'           => ['type' => 'number'],
                            'unidad'             => ['type' => 'string'],
                        ],
                        'required'   => [
                            'requisicion',
                            'partida',
                            'clave_verificacion',
                            'descripcion',
                            'especificaciones',
                            'cantidad',
                            'unidad',
                        ],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required'             => ['items'],
            'additionalProperties' => false,
        ];

        $prompt = "
Eres un asistente experto en licitaciones que EXTRAe TODOS los renglones de una tabla.

La tabla puede tener muchas filas. TU OBJETIVO ES:

- LEER TODO EL TEXTO del bloque.
- IDENTIFICAR CADA RENGLÓN DE PRODUCTO.
- DEVOLVER UN ITEM POR CADA RENGLÓN. NO omitas filas aunque parezcan similares.
- Si una fila está cortada en varias líneas, debes unirlas y formar un SOLO item.

REGLAS DE MAPEO:

- Usa la requisición '$requisicion' en el campo 'requisicion' si no viene explícita.
- 'partida' es el número de la partida (1, 2, 3, ...).
- 'clave_verificacion' es el código numérico de la columna CLAVE DE VERIFICACIÓN (si no hay, deja \"\").
- 'descripcion' viene del nombre del bien (BIENES SOLICITADOS).
- 'especificaciones' viene del texto descriptivo detallado (ESPECIFICACIONES).
- 'cantidad' debe ser numérica (sin comas, sin texto).
- 'unidad' viene de UNIDAD DE MEDIDA (PIEZA, CAJA, PAQUETE, etc.).
- Ignora filas de encabezado repetidas (PARTIDA / CLAVE / BIENES / ESPECIFICACIONES / CANTIDAD / UNIDAD).
- Si algún campo no existe en la tabla, rellénalo con \"\" (o 0 en el caso de cantidad).

MUY IMPORTANTE:
- No devuelvas comentarios ni texto adicional, SOLO el JSON.
- No te limites a las primeras filas: revisa TODO el bloque y extrae todas las filas de producto.

Tabla (bloque de texto):
--------------------
$textoTabla
--------------------
";

        try {
            $response = $this->client->responses()->create([
                'model' => 'gpt-4.1',
                'input' => $prompt,
                'text'  => [
                    'format' => [
                        'type'   => 'json_schema',
                        'name'   => 'items_from_table',
                        'strict' => true,
                        'schema' => $schema,
                    ],
                ],
            ]);

            $json = $response->output_text ?? $response->outputText ?? null;

            if (is_array($json)) {
                $json = implode("\n", $json);
            }

            if (!is_string($json) || trim($json) === '') {
                return [];
            }

            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Error al decodificar JSON de OpenAI en extraerConOpenAI', [
                    'error'       => json_last_error_msg(),
                    'requisicion' => $requisicion,
                ]);
                return [];
            }

            if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
                return [];
            }

            $itemsNormalizados = [];

            foreach ($data['items'] as $raw) {
                $itemsNormalizados[] = [
                    'requisicion'        => isset($raw['requisicion']) && $raw['requisicion'] !== ''
                        ? (string) $raw['requisicion']
                        : (string) $requisicion,
                    'partida'            => (string) ($raw['partida'] ?? ''),
                    'clave_verificacion' => (string) ($raw['clave_verificacion'] ?? ''),
                    'descripcion'        => trim((string) ($raw['descripcion'] ?? '')),
                    'especificaciones'   => trim((string) ($raw['especificaciones'] ?? '')),
                    'cantidad'           => (float) ($raw['cantidad'] ?? 0),
                    'unidad'             => (string) ($raw['unidad'] ?? ''),
                ];
            }

            return $itemsNormalizados;
        } catch (\Throwable $e) {
            Log::error('Error en LicitacionOpenAIService::extraerConOpenAI', [
                'message'     => $e->getMessage(),
                'requisicion' => $requisicion,
            ]);

            return [];
        }
    }

    /**
     * Prepara un arreglo plano (con cabeceras) listo para exportarse a Excel.
     */
    public function prepararFilasParaExcel(array $items): array
    {
        $rows = [];

        foreach ($items as $item) {
            $rows[] = [
                'REQUISICION'        => $item['requisicion']        ?? '',
                'PARTIDA'            => $item['partida']            ?? '',
                'CLAVE_VERIFICACION' => $item['clave_verificacion'] ?? '',
                'DESCRIPCION'        => $item['descripcion']        ?? '',
                'ESPECIFICACIONES'   => $item['especificaciones']   ?? '',
                'CANTIDAD'           => $item['cantidad']           ?? 0,
                'UNIDAD'             => $item['unidad']             ?? '',
            ];
        }

        return $rows;
    }
}
