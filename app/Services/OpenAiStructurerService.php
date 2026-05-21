<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiStructurerService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = (string) env('OPENAI_API_KEY', '');
        $this->model  = (string) env('OPENAI_MODEL', 'gpt-4o-mini');
    }

    /**
     * Estructura el texto completo de la licitación en JSON con:
     *  - ficha: datos clave
     *  - fechas_clave: eventos importantes
     *  - resumen_ejecutivo: preguntas/respuestas
     *  - partidas: array de items
     */
    public function structureProject(string $rawText): array
    {
        if (!$this->apiKey) {
            throw new \Exception('Falta OPENAI_API_KEY en .env');
        }

        $compact = mb_substr($rawText, 0, 60000);

        $systemPrompt = 'Eres un asistente que extrae información de licitaciones públicas mexicanas. Responde ÚNICAMENTE JSON válido, sin markdown.';

        $userPrompt = <<<PROMPT
Analiza el texto de esta licitación y devuelve UN SOLO JSON con esta estructura exacta:

{
  "ficha": {
    "numero_licitacion": "...",
    "tipo_evento": "...",
    "organismo": "...",
    "objeto_licitacion": "...",
    "medio_participacion": "..."
  },
  "fechas_clave": {
    "fecha_publicacion": "...",
    "junta_aclaraciones": "...",
    "presentacion_apertura": "...",
    "fallo": "...",
    "vigencia_contrato": "..."
  },
  "resumen_ejecutivo": [
    {"pregunta": "¿Cuánto tiempo tengo para implementar?", "respuesta": "..."},
    {"pregunta": "¿Es necesario demostrar experiencia previa o acreditar experiencia?", "respuesta": "..."},
    {"pregunta": "¿Se mencionan penas convencionales, multas, deducciones u otras sanciones en caso de incumplimiento?", "respuesta": "..."},
    {"pregunta": "¿Cuál es el periodo de garantía a ofertar?", "respuesta": "..."},
    {"pregunta": "¿Cuál es el sistema de evaluación?", "respuesta": "..."},
    {"pregunta": "¿Se requieren cartas de apoyo?", "respuesta": "..."},
    {"pregunta": "¿Se deben entregar muestras físicas?", "respuesta": "..."}
  ],
  "partidas": [
    {"numero": 1, "descripcion": "...", "unidad": "...", "cantidad": 0}
  ],
  "checklist_sugerido": [
    {"item": "...", "checked": false}
  ]
}

Reglas:
- Si un dato no se encuentra, escribe exactamente: "No se encontró información"
- No inventes datos
- Las fechas en formato dd/mm/aaaa cuando sea posible
- En resumen_ejecutivo, responde cada pregunta basándote SOLO en el texto
- Las partidas son los items/productos/servicios solicitados

Texto de la licitación:
$compact
PROMPT;

        $response = Http::timeout(180)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userPrompt],
                ],
                'temperature' => 0.1,
                'response_format' => ['type' => 'json_object'],
            ]);

        if (!$response->successful()) {
            Log::error('OpenAI structurer error', ['body' => $response->body()]);
            throw new \Exception('Error de OpenAI: ' . $response->body());
        }

        $content = $response->json('choices.0.message.content') ?? '';

        $parsed = json_decode($content, true);
        if (!is_array($parsed)) {
            throw new \Exception('OpenAI devolvió JSON inválido');
        }

        return $parsed;
    }

    /**
     * Chat libre sobre la licitación (para tab "Análisis de Bases")
     */
    public function chat(string $rawText, array $history, string $userMessage): string
    {
        if (!$this->apiKey) {
            throw new \Exception('Falta OPENAI_API_KEY en .env');
        }

        $compact = mb_substr($rawText, 0, 60000);

        $messages = [
            [
                'role' => 'system',
                'content' => "Eres un asistente experto en licitaciones públicas. Responde SOLO basándote en el siguiente documento. Si no encuentras información, dilo claramente.\n\nDOCUMENTO:\n$compact",
            ],
        ];

        foreach ($history as $h) {
            $messages[] = ['role' => $h['role'], 'content' => $h['content']];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $response = Http::timeout(120)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.2,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Error de OpenAI: ' . $response->body());
        }

        return $response->json('choices.0.message.content') ?? 'Sin respuesta';
    }
}