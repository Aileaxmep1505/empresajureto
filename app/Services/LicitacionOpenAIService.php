<?php

namespace App\Services;

use OpenAI;

class LicitacionOpenAIService
{
    /**
     * Cliente de OpenAI.
     *
     * @var \OpenAI\Client
     */
    protected $client;

    public function __construct()
    {
        // Usa tu API key desde .env: OPENAI_API_KEY=...
        $this->client = OpenAI::client(env('OPENAI_API_KEY'));
    }

    /**
     * Convierte el texto de una tabla de licitación en items estructurados.
     *
     * @param  string  $textoTabla  Texto crudo de la tabla (un bloque de ~40 líneas)
     * @param  string  $requisicion Requisición detectada (ej. CA-0232-2025)
     * @return array   Arreglo de items listos para guardar en la BD
     */
    public function extraerItemsDesdeTabla(string $textoTabla, string $requisicion): array
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
            'required' => ['items'],
            'additionalProperties' => false,
        ];

        $prompt = "
Eres un asistente que EXTRAe TODOS los renglones de una tabla de licitación.

La tabla puede tener muchas filas. TU OBJETIVO ES:

- LEER TODO EL TEXTO del bloque.
- IDENTIFICAR CADA RENGLÓN DE PRODUCTO.
- DEVOLVER UN ITEM POR CADA RENGLÓN. NO omitas filas aunque parezcan muy similares.
- Si una fila está cortada en varias líneas, debes unirlas y formar un SOLO item.

REGLAS DE MAPEO:

- Usa la requisición '$requisicion' en el campo 'requisicion' si no viene explícita.
- 'partida' es la columna PARTIDA (si existe; si no, pon \"\").
- 'clave_verificacion' es la columna CLAVE DE VERIFICACIÓN (si no hay, deja \"\").
- 'descripcion' viene del nombre del bien (BIENES SOLICITADOS).
- 'especificaciones' viene del texto descriptivo detallado (ESPECIFICACIONES).
- 'cantidad' debe ser numérica (sin comas, sin texto).
- 'unidad' viene de UNIDAD DE MEDIDA.
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

        $response = $this->client->responses()->create([
            // Modelo más potente para extracción de tablas
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

        // En la Responses API, el SDK expone un helper `output_text`
        $json = $response->output_text ?? $response->outputText ?? null;

        if (! $json) {
            return [];
        }

        $data = json_decode($json, true);

        if (! is_array($data) || ! isset($data['items']) || ! is_array($data['items'])) {
            return [];
        }

        return $data['items'];
    }
}
