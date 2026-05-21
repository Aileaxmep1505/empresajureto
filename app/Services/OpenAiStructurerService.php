<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiStructurerService
{
    protected string $apiKey;
    protected string $primaryModel;
    protected array $fallbackModels;

    public function __construct()
    {
        $this->apiKey = (string) env('OPENAI_API_KEY', '');

        // Soporta el patrón nuevo (PRIMARY/FALLBACK) y cae al viejo (OPENAI_MODEL)
        $this->primaryModel = (string) env('OPENAI_PRIMARY_MODEL', env('OPENAI_MODEL', 'gpt-4o'));

        $rawFallbacks = (string) env('OPENAI_FALLBACK_MODELS', '');
        $this->fallbackModels = array_values(array_filter(array_map('trim', explode(',', $rawFallbacks))));
    }

    /**
     * Estructura el texto de licitación en JSON (ficha, fechas, resumen ejecutivo, partidas, checklist)
     */
    public function structureProject(string $rawText): array
    {
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

Texto de la licitación:
$compact
PROMPT;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userPrompt],
        ];

        $result = $this->tryModels($messages, expectJson: true);

        if (!is_array($result)) {
            throw new \Exception('OpenAI no devolvió JSON estructurado');
        }

        return $result;
    }

    /**
     * Chat libre sobre la licitación (tab "Análisis de Bases")
     */
  public function chat(string $rawText, array $history, string $userMessage): string
{
    $compact = mb_substr($rawText, 0, 60000);

    $systemContent = <<<SYS
Eres un asistente experto en licitaciones publicas mexicanas. Responde SOLO basandote en el documento provisto. Si no encuentras informacion, dilo claramente.

FORMATO DE RESPUESTA:
- Cuando la pregunta pida COMPARAR, LISTAR, RESUMIR varios items, o cuando los datos sean naturalmente tabulares (archivos, partidas, fechas, requisitos, etc.) responde con UNA TABLA EN MARKDOWN.
- Ejemplo de tabla:

| Columna 1 | Columna 2 | Columna 3 |
| --- | --- | --- |
| valor 1 | valor 2 | valor 3 |
| valor 4 | valor 5 | valor 6 |

- Si la pregunta pide algo conciso (un dato puntual, un si/no, una explicacion corta), responde en texto natural sin tabla.
- Si necesitas listar pasos o requisitos cortos, usa bullets con "-".
- NO uses code blocks (```), solo markdown plano.

DOCUMENTO:
$compact
SYS;

    $messages = [
        ['role' => 'system', 'content' => $systemContent],
    ];

    foreach ($history as $h) {
        $messages[] = ['role' => $h['role'], 'content' => $h['content']];
    }

    $messages[] = ['role' => 'user', 'content' => $userMessage];

    $result = $this->tryModels($messages, expectJson: false);

    return is_string($result) ? $result : 'Sin respuesta';
}

    /**
     * Intenta el modelo primario y va cayendo a los fallbacks si falla.
     */
    protected function tryModels(array $messages, bool $expectJson): mixed
    {
        if (!$this->apiKey) {
            throw new \Exception('Falta OPENAI_API_KEY en .env');
        }

        $modelsToTry = array_unique(array_merge([$this->primaryModel], $this->fallbackModels));
        $lastError = null;

        foreach ($modelsToTry as $model) {
            try {
                Log::info("OpenAI: probando modelo {$model}");
                return $this->callOpenAI($model, $messages, $expectJson);
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                Log::warning("OpenAI falló con modelo {$model}: {$msg}");
                $lastError = $e;

                // Si el error NO es del modelo (es de auth, red, etc.) no intentes más
                $modelRelated = str_contains($msg, 'model_not_found')
                    || str_contains($msg, 'does not have access')
                    || str_contains($msg, 'invalid_request_error')
                    || str_contains($msg, 'not supported')
                    || str_contains($msg, 'Unsupported');

                if (!$modelRelated) {
                    throw $e;
                }
            }
        }

        throw $lastError ?? new \Exception('Todos los modelos OpenAI fallaron');
    }

    /**
     * Llamada cruda a OpenAI con manejo de diferencias entre modelos viejos (gpt-4, gpt-3.5)
     * y modelos nuevos (gpt-5, o1) que NO soportan temperature ni response_format.
     */
    protected function callOpenAI(string $model, array $messages, bool $expectJson): mixed
    {
        $isReasoningOrGpt5 = str_starts_with($model, 'gpt-5')
            || str_starts_with($model, 'o1')
            || str_starts_with($model, 'o3');

        $payload = [
            'model'    => $model,
            'messages' => $messages,
        ];

        // Modelos viejos: soportan temperature y response_format JSON mode
        if (!$isReasoningOrGpt5) {
            $payload['temperature'] = 0.1;
            if ($expectJson) {
                $payload['response_format'] = ['type' => 'json_object'];
            }
        }

        $response = Http::timeout(180)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        if (!$response->successful()) {
            $body = $response->body();
            Log::error('OpenAI call falló', ['model' => $model, 'body' => $body]);
            throw new \Exception($body);
        }

        $content = $response->json('choices.0.message.content') ?? '';

        if (!$expectJson) {
            return $content;
        }

        // Limpiar bloques markdown si vienen (GPT-5 a veces los mete)
        $clean = trim($content);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
        $clean = preg_replace('/\s*```$/', '', $clean);

        $parsed = json_decode($clean, true);

        if (!is_array($parsed)) {
            // intentar extraer el primer bloque {...} si vino con texto alrededor
            if (preg_match('/\{.*\}/s', $clean, $m)) {
                $parsed = json_decode($m[0], true);
            }
        }

        if (!is_array($parsed)) {
            throw new \Exception("Modelo {$model} devolvió JSON inválido: " . mb_substr($content, 0, 300));
        }

        return $parsed;
    }
}