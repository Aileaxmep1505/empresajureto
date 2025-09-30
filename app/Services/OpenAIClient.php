<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class OpenAIClient
{
    private Client $http;
    private string $base;
    private string $key;
    private string $model;

    public function __construct()
    {
        $this->base  = config('services.openai.base');
        $this->key   = config('services.openai.key');
        $this->model = config('services.openai.model');
        $this->http  = new Client([
            'base_uri' => $this->base,
            'timeout'  => 120,
        ]);
    }

    /** Sube un archivo (PDF) a OpenAI y devuelve file_id */
    public function uploadFile(string $path, string $purpose = 'assistants')
    {
        $res = $this->http->post('files', [
            'headers' => ['Authorization' => "Bearer {$this->key}"],
            'multipart' => [
                ['name' => 'file', 'contents' => fopen($path, 'r'), 'filename' => basename($path)],
                ['name' => 'purpose', 'contents' => $purpose],
            ]
        ]);
        return json_decode((string) $res->getBody(), true);
    }

    /**
     * Llama a Responses API con el PDF como adjunto.
     * La instrucción pide a la IA detectar en qué páginas viene la info y extraer ESTRUCTURADO.
     */
    public function extractFromPdf(string $fileId, array $hints = [])
    {
        $system = "Eres un analista de licitaciones. Lee el PDF adjunto, aunque tenga anexos y ruido.
- Identifica en qué páginas aparece la 'base de cotización'.
- Extrae CLIENTE (nombre, dependencia/unidad, contacto si existe).
- Extrae LISTA DE ÍTEMS: {descripcion, cantidad, unidad?, especificaciones?, referencias?, plazo_entrega?}.
- Si hay formatos tabulares, normaliza columnas y convierte cantidades a número.
- Devuelve JSON estricto con: {cliente:{...}, items:[...], notas:[], paginasRelevantes:[nro...]}.
- Si faltan datos, deja null o []. No inventes.";

        // Estructura para Responses API (texto + archivo)
        $payload = [
            'model' => $this->model,
            'input' => [
                [
                    'role' => 'system',
                    'content' => [
                        ['type' => 'text', 'text' => $system],
                    ]
                ],
                [
                    'role' => 'user',
                    'content' => array_merge(
                        [
                            ['type' => 'input_text', 'text' => 'Analiza el PDF y responde SOLO con un JSON válido.'],
                            ['type' => 'input_file', 'file_id' => $fileId],
                        ],
                        $hints ? [['type' => 'input_text', 'text' => 'Pistas del usuario: '.json_encode($hints, JSON_UNESCAPED_UNICODE)]] : []
                    )
                ]
            ],
            // Opcional: pide JSON formal
            'response_format' => ['type' => 'json_object'],
        ];

        $res = $this->http->post('responses', [
            'headers' => [
                'Authorization' => "Bearer {$this->key}",
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode($payload),
        ]);

        return json_decode((string) $res->getBody(), true);
    }
}
