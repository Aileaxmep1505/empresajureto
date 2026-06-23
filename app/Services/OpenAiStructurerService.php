<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OpenAiStructurerService
{
    protected string $apiKey;
    protected string $primaryModel;
    protected array $fallbackModels;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = (string) env('OPENAI_API_KEY', '');
        $this->baseUrl = rtrim((string) env('OPENAI_BASE_URL', 'https://api.openai.com'), '/');

        // Soporta patrón nuevo y viejo.
        $this->primaryModel = (string) env('OPENAI_PRIMARY_MODEL', env('OPENAI_MODEL', 'gpt-5.4-mini'));

        $rawFallbacks = (string) env('OPENAI_FALLBACK_MODELS', '');
        $this->fallbackModels = array_values(array_filter(array_map('trim', explode(',', $rawFallbacks))));
    }

    /**
     * Estructura el texto de licitación en JSON.
     * Importante: después del modelo aplica fallback determinístico leyendo el texto crudo.
     */
    public function structureProject(string $rawText): array
    {
        $rawText = $this->normalizeText($rawText);
        $compact = mb_substr($rawText, 0, (int) env('OPENAI_STRUCTURE_CHARS', 120000));

        $systemPrompt = 'Eres un asistente experto en extracción de información de licitaciones públicas mexicanas. Responde ÚNICAMENTE JSON válido, sin markdown, sin explicaciones y sin texto adicional.';

        $userPrompt = <<<PROMPT
Analiza el texto de esta licitación y devuelve UN SOLO JSON válido con esta estructura exacta:

{
  "ficha": {
    "numero_licitacion": "...",
    "tipo_evento": "...",
    "organismo": "...",
    "objeto_licitacion": "...",
    "medio_participacion": "...",
    "moneda_pago": "...",
    "condiciones_pago": "..."
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
    {"pregunta": "¿Se deben entregar muestras físicas?", "respuesta": "..."},
    {"pregunta": "¿Es necesario entregar documentación regulatoria?", "respuesta": "..."},
    {"pregunta": "¿A qué hospitales o instituciones se deben entregar los productos o prestar los servicios?", "respuesta": "..."},
    {"pregunta": "¿Existe subrogación en caso de fallas del equipo?", "respuesta": "..."},
    {"pregunta": "¿Se requiere la documentación técnica en español o se permiten traducciones simples?", "respuesta": "..."},
    {"pregunta": "¿Cómo se realiza la adjudicación?", "respuesta": "..."},
    {"pregunta": "¿Se menciona si el evento está bajo tratados de libre comercio?", "respuesta": "..."},
    {"pregunta": "¿Cuál es la vigencia o duración del contrato?", "respuesta": "..."},
    {"pregunta": "¿Cuáles son los plazos de entrega y las condiciones para cumplir con las entregas?", "respuesta": "..."},
    {"pregunta": "¿Es necesario realizar una visita a las instalaciones de la convocante?", "respuesta": "..."}
  ],
  "partidas": [
    {"numero": 1, "descripcion": "...", "unidad": "...", "cantidad": 0}
  ],
  "checklist_sugerido": [
    {
      "requisito": "Nombre del requisito a presentar",
      "descripcion": "Detalle de qué debe contener el documento",
      "criterio_cumplimiento": "Qué se necesita exactamente para considerar cumplido este requisito según el PDF",
      "formato": "No aplica",
      "categoria": "Legal-Administrativo",
      "aplicabilidad": "Único",
      "obligatorio": "Sí",
      "cumplimiento": "-",
      "status": "Pendiente",
      "prioridad": "Media",
      "fuente": "archivo.pdf",
      "pagina": 1,
      "cita": "texto literal del documento que respalda el requisito"
    }
  ],
  "citas": {
    "ficha.numero_licitacion": {"cita": "texto literal", "fuente": "archivo.pdf", "pagina": 1},
    "ficha.tipo_evento": {"cita": "texto literal", "fuente": "archivo.pdf", "pagina": 1},
    "ficha.organismo": {"cita": "texto literal", "fuente": "archivo.pdf", "pagina": 1},
    "ficha.objeto_licitacion": {"cita": "texto literal", "fuente": "archivo.pdf", "pagina": 1},
    "ficha.medio_participacion": {"cita": "texto literal", "fuente": "archivo.pdf", "pagina": 1},
    "ficha.moneda_pago": {"cita": "texto literal", "fuente": "archivo.pdf", "pagina": 1},
    "ficha.condiciones_pago": {"cita": "texto literal", "fuente": "archivo.pdf", "pagina": 1}
  }
}

Reglas obligatorias:
- Responde SOLO JSON válido.
- No inventes datos.
- Si un dato no se encuentra, usa exactamente "No se encontró información".
- Las fechas deben ir en formato dd/mm/aaaa cuando sea posible.
- En resumen_ejecutivo devuelve EXACTAMENTE las 16 preguntas anteriores, en el mismo orden.
- Si una pregunta no aplica o no está en el documento, inclúyela con respuesta "No se encontró información".
- Para moneda de pago busca expresiones como "moneda nacional", "pesos mexicanos", "M.N.", "forma de pago", "condiciones y formas de pago".
- Para condiciones de pago busca la sección "CONDICIONES Y FORMAS DE PAGO", "forma de pago", "factura", "pago correspondiente", "orden de suministro", "entrega de bienes".
- En "citas" pon texto LITERAL del documento que respalda cada campo.
- Cada cita debe tener máximo 350 caracteres.
- El campo "fuente" debe ser el nombre EXACTO del archivo si aparece en encabezados tipo "--- DOCUMENTO: nombre.pdf ---".
- El campo "pagina" debe tomarse de marcas tipo "[PAGINA X]" cuando existan.
- checklist_sugerido debe incluir entre 20 y 50 requisitos cuando el documento tenga requisitos suficientes.
- Cada requisito debe incluir fuente, pagina, cita y criterio_cumplimiento.

Texto de la licitación:
{$compact}
PROMPT;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        $result = $this->tryModels($messages, expectJson: true);

        if (!is_array($result)) {
            throw new \Exception('OpenAI no devolvió JSON estructurado');
        }

        return $this->postprocessStructuredData($result, $rawText);
    }

    /**
     * Chat libre sobre la licitación.
     */
    public function chat(string $rawText, array $history, string $userMessage): string
    {
        $compact = mb_substr($this->normalizeText($rawText), 0, (int) env('OPENAI_CHAT_CHARS', 120000));

        $systemContent = <<<SYS
Eres un asistente experto en licitaciones públicas mexicanas. Responde SOLO basándote en el documento provisto.

REGLAS:
- Si el dato aparece en el documento, responde con el dato directo y explica brevemente dónde se encontró.
- Si el dato no aparece explícitamente, dilo claramente y, solo después, puedes dar una inferencia marcada como inferencia.
- No inventes datos.
- Para datos puntuales no uses tabla.
- Usa tablas solo cuando el usuario pida comparar/listar varios elementos con columnas.
- Usa markdown simple, sin bloques de código.

DOCUMENTO:
{$compact}
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
     * Chat con mensajes ya construidos.
     */
    public function chatRaw(array $messages): string
    {
        $result = $this->tryModels($messages, expectJson: false);

        return is_string($result) ? $result : 'Sin respuesta';
    }

    protected function tryModels(array $messages, bool $expectJson): mixed
    {
        if (!$this->apiKey) {
            throw new \Exception('Falta OPENAI_API_KEY en .env');
        }

        $modelsToTry = array_values(array_unique(array_filter(array_merge([$this->primaryModel], $this->fallbackModels))));
        $lastError = null;

        foreach ($modelsToTry as $model) {
            try {
                Log::info("OpenAI: probando modelo {$model}");
                return $this->callOpenAI($model, $messages, $expectJson);
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                Log::warning("OpenAI falló con modelo {$model}: {$msg}");
                $lastError = $e;

                $modelRelated = str_contains($msg, 'model_not_found')
                    || str_contains($msg, 'does not have access')
                    || str_contains($msg, 'invalid_request_error')
                    || str_contains($msg, 'not supported')
                    || str_contains($msg, 'Unsupported')
                    || str_contains($msg, 'Unknown parameter')
                    || str_contains($msg, 'temperature')
                    || str_contains($msg, 'response_format');

                if (!$modelRelated) {
                    throw $e;
                }
            }
        }

        throw $lastError ?? new \Exception('Todos los modelos OpenAI fallaron');
    }

    protected function callOpenAI(string $model, array $messages, bool $expectJson): mixed
    {
        $isReasoningOrGpt5 = str_starts_with($model, 'gpt-5')
            || str_starts_with($model, 'o1')
            || str_starts_with($model, 'o3')
            || str_starts_with($model, 'o4');

        if ($isReasoningOrGpt5) {
            return $this->callResponsesApi($model, $messages, $expectJson);
        }

        return $this->callChatCompletionsApi($model, $messages, $expectJson);
    }

    protected function callResponsesApi(string $model, array $messages, bool $expectJson): mixed
    {
        $input = collect($messages)->map(function ($message) {
            return [
                'role' => $message['role'] ?? 'user',
                'content' => $message['content'] ?? '',
            ];
        })->values()->all();

        $payload = [
            'model' => $model,
            'input' => $input,
        ];

        if ($expectJson) {
            $payload['text'] = [
                'format' => [
                    'type' => 'json_object',
                ],
            ];
        }

        $response = Http::timeout((int) env('OPENAI_TIMEOUT', 300))
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($this->baseUrl . '/v1/responses', $payload);

        if (!$response->successful()) {
            $body = $response->body();
            Log::error('OpenAI Responses call falló', ['model' => $model, 'body' => $body]);
            throw new \Exception($body);
        }

        $payload = $response->json();
        $content = $this->extractTextFromResponsesPayload($payload);

        if (!$expectJson) {
            return $content;
        }

        return $this->parseModelJson($model, $content);
    }

    protected function callChatCompletionsApi(string $model, array $messages, bool $expectJson): mixed
    {
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.1,
        ];

        if ($expectJson) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = Http::timeout((int) env('OPENAI_TIMEOUT', 300))
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($this->baseUrl . '/v1/chat/completions', $payload);

        if (!$response->successful()) {
            $body = $response->body();
            Log::error('OpenAI Chat Completions call falló', ['model' => $model, 'body' => $body]);
            throw new \Exception($body);
        }

        $content = $response->json('choices.0.message.content') ?? '';

        if (!$expectJson) {
            return $content;
        }

        return $this->parseModelJson($model, $content);
    }

    protected function extractTextFromResponsesPayload(array $payload): string
    {
        if (isset($payload['output_text']) && is_string($payload['output_text']) && trim($payload['output_text']) !== '') {
            return trim($payload['output_text']);
        }

        $parts = [];

        foreach (($payload['output'] ?? []) as $item) {
            foreach (($item['content'] ?? []) as $block) {
                if (isset($block['text']) && is_string($block['text']) && trim($block['text']) !== '') {
                    $parts[] = trim($block['text']);
                }
            }
        }

        return trim(implode("\n", $parts));
    }

    protected function parseModelJson(string $model, string $content): array
    {
        $clean = trim($content);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
        $clean = preg_replace('/\s*```$/', '', $clean);

        $parsed = json_decode($clean, true);

        if (!is_array($parsed) && preg_match('/\{.*\}/s', $clean, $match)) {
            $parsed = json_decode($match[0], true);
        }

        if (!is_array($parsed)) {
            throw new \Exception("Modelo {$model} devolvió JSON inválido: " . mb_substr($content, 0, 300));
        }

        return $parsed;
    }

    protected function postprocessStructuredData(array $structured, string $rawText): array
    {
        $structured['ficha'] = $structured['ficha'] ?? [];
        $structured['fechas_clave'] = $structured['fechas_clave'] ?? [];
        $structured['citas'] = is_array($structured['citas'] ?? null) ? $structured['citas'] : [];

        $fallbacks = $this->extractDeterministicFallbacks($rawText);

        foreach (($fallbacks['ficha'] ?? []) as $key => $value) {
            if ($this->isEmptyValue($structured['ficha'][$key] ?? null) && !$this->isEmptyValue($value)) {
                $structured['ficha'][$key] = $value;
            }
        }

        foreach (($fallbacks['fechas_clave'] ?? []) as $key => $value) {
            if ($this->isEmptyValue($structured['fechas_clave'][$key] ?? null) && !$this->isEmptyValue($value)) {
                $structured['fechas_clave'][$key] = $value;
            }
        }

        foreach (($structured['ficha'] ?? []) as $key => $value) {
            $citationKey = "ficha.{$key}";
            if (!$this->isEmptyValue($value) && empty($structured['citas'][$citationKey])) {
                $evidence = $this->findEvidenceForValue($rawText, $value);
                if ($evidence) {
                    $structured['citas'][$citationKey] = $evidence;
                }
            }
        }

        foreach (($structured['fechas_clave'] ?? []) as $key => $value) {
            $citationKey = "fechas_clave.{$key}";
            if (!$this->isEmptyValue($value) && empty($structured['citas'][$citationKey])) {
                $evidence = $this->findEvidenceForValue($rawText, $value);
                if ($evidence) {
                    $structured['citas'][$citationKey] = $evidence;
                }
            }
        }

        return $structured;
    }

    protected function extractDeterministicFallbacks(string $rawText): array
    {
        $text = $this->normalizeText($rawText);
        $ficha = [];
        $fechas = [];

        $ficha['numero_licitacion'] = $this->firstMatch($text, [
            '~(?:n[uú]mero\s+de\s+licitaci[oó]n|no\.?\s*(?:de\s*)?(?:licitaci[oó]n|procedimiento)|procedimiento\s*(?:no\.?|n[uú]m\.?)|expediente)\s*[:#]?\s*([A-Z0-9][A-Z0-9\/\-.]{4,})~iu',
            '~\b((?:IA|LA|LPN|LPI|AA|AD)[\-\/]?[A-Z0-9\-\/\.]{5,})\b~iu',
        ], 90);

        $ficha['tipo_evento'] = $this->detectFirstNeedle($text, [
            'Invitación a cuando menos tres personas',
            'Licitación Pública Nacional',
            'Licitación Pública Internacional',
            'Licitación Pública',
            'Adjudicación Directa',
        ]);

        $organismo = $this->firstMatch($text, [
            '~(Consejo\s+Nacional\s+de\s+Fomento\s+Educativo)~iu',
            '~\b(CONAFE)\b~iu',
            '~(Secretar[ií]a\s+de\s+Educaci[oó]n\s+P[uú]blica)~iu',
            '~(Secretar[ií]a\s+de\s+Marina)~iu',
            '~(Instituto\s+[A-ZÁÉÍÓÚÑ][A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]{8,120})~u',
        ], 160);

        if (Str::upper((string) $organismo) === 'CONAFE') {
            $organismo = 'Consejo Nacional de Fomento Educativo';
        }

        $ficha['organismo'] = $organismo;

        $ficha['objeto_licitacion'] = $this->firstMatch($text, [
            '~objeto\s+(?:de\s+)?(?:la\s+)?(?:licitaci[oó]n|contrataci[oó]n|procedimiento)\s*[:\-\n]\s*([^.;]{12,260})~iu',
            '~(adquisici[oó]n\s+de\s+(?:materiales|bienes|servicios|[úu]tiles)[^.;]{5,240})~iu',
            '~(contrataci[oó]n\s+de\s+(?:servicios|bienes|materiales)[^.;]{5,240})~iu',
            '~(servicio\s+de\s+[^.;]{10,240})~iu',
        ], 260);

        $medio = null;
        if (preg_match('~\b(electr[oó]nic[ao]|CompraNet)\b~iu', $text)) {
            $medio = 'Electrónica';
        } elseif (preg_match('~\bpresencial\b~iu', $text)) {
            $medio = 'Presencial';
        } elseif (!empty($ficha['tipo_evento'])) {
            $medio = $ficha['tipo_evento'];
        }
        $ficha['medio_participacion'] = $medio;

        if (preg_match('~moneda\s+nacional\s*\(([^)]+)\)~iu', $text, $m)) {
            $ficha['moneda_pago'] = 'Moneda nacional (' . $this->clean($m[1]) . ')';
        } elseif (preg_match('~\bpesos\s+mexicanos\b~iu', $text)) {
            $ficha['moneda_pago'] = 'Pesos mexicanos';
        } elseif (preg_match('~\bmoneda\s+nacional\b~iu', $text)) {
            $ficha['moneda_pago'] = 'Moneda nacional';
        } else {
            $ficha['moneda_pago'] = null;
        }

        $ficha['condiciones_pago'] = $this->firstMatch($text, [
            '~CONDICIONES\s+Y\s+FORMAS\s+DE\s+PAGO\s*(.+?)(?=\s+(?:\d+\.\s+[A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s]{6,}|P[aá]gina\s+\d+|--- DOCUMENTO:|$))~isu',
            '~(?:forma|condiciones)\s+de\s+pago\s*[:\-\n]\s*(.+?)(?=\s+(?:\d+\.\s+[A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s]{6,}|P[aá]gina\s+\d+|--- DOCUMENTO:|$))~isu',
            '~(el\s+pago\s+correspondiente\s+se\s+realizar[aá]\s+.+?)(?=\s+(?:\d+\.\s+[A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s]{6,}|P[aá]gina\s+\d+|--- DOCUMENTO:|$))~isu',
        ], 620);

        $fechas['junta_aclaraciones'] = $this->firstDateNear($text, 'junta de aclaraciones');
        $fechas['presentacion_apertura'] = $this->firstDateNear($text, 'presentación y apertura') ?: $this->firstDateNear($text, 'presentacion y apertura');
        $fechas['fallo'] = $this->firstDateNear($text, 'fallo');

        return [
            'ficha' => array_filter($ficha, fn ($v) => !$this->isEmptyValue($v)),
            'fechas_clave' => array_filter($fechas, fn ($v) => !$this->isEmptyValue($v)),
        ];
    }

    protected function firstDateNear(string $text, string $needle): ?string
    {
        $pos = mb_stripos($text, $needle);
        if ($pos === false) {
            return null;
        }

        $chunk = mb_substr($text, max(0, $pos - 180), 700);

        if (preg_match('~\b(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})\b~u', $chunk, $m)) {
            $year = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
            return str_pad($m[1], 2, '0', STR_PAD_LEFT) . '/' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '/' . $year;
        }

        return null;
    }

    protected function firstMatch(string $text, array $patterns, int $limit = 260): ?string
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $value = $this->clean($m[1] ?? $m[0] ?? '');
                $value = trim($value, " \t\n\r\0\x0B:-–—.");

                if ($value !== '') {
                    return Str::limit($value, $limit, '');
                }
            }
        }

        return null;
    }

    protected function detectFirstNeedle(string $text, array $needles): ?string
    {
        $low = Str::lower($text);

        foreach ($needles as $needle) {
            if (Str::contains($low, Str::lower($needle))) {
                return $needle;
            }
        }

        return null;
    }

    protected function findEvidenceForValue(string $rawText, mixed $value): ?array
    {
        $valueText = $this->clean($value);
        if ($this->isEmptyValue($valueText)) {
            return null;
        }

        $rawLow = Str::lower($rawText);
        $valueLow = Str::lower($valueText);
        $pos = mb_stripos($rawLow, $valueLow);

        if ($pos === false) {
            $words = preg_split('/\W+/u', $valueLow, -1, PREG_SPLIT_NO_EMPTY);
            $words = array_values(array_filter($words, fn ($w) => mb_strlen($w) >= 4));

            for ($size = min(8, count($words)); $size >= 3; $size--) {
                for ($i = 0; $i <= count($words) - $size; $i++) {
                    $phrase = implode(' ', array_slice($words, $i, $size));
                    $try = mb_stripos($rawLow, $phrase);
                    if ($try !== false) {
                        $pos = $try;
                        break 2;
                    }
                }
            }
        }

        if ($pos === false) {
            return null;
        }

        $source = $this->sourceAndPageAt($rawText, (int) $pos);
        $quote = $this->makeQuote($rawText, (int) $pos);

        return [
            'cita' => $quote,
            'fuente' => $source['fuente'],
            'pagina' => $source['pagina'],
        ];
    }

    protected function sourceAndPageAt(string $rawText, int $pos): array
    {
        $before = mb_substr($rawText, 0, $pos);
        $fuente = '';
        $pagina = null;

        if (preg_match_all('~--- DOCUMENTO:\s*(.*?)\s*---~u', $before, $docs) && !empty($docs[1])) {
            $fuente = trim(end($docs[1]));
        }

        if (preg_match_all('~\[PAGINA\s+(\d+)\]~iu', $before, $pages) && !empty($pages[1])) {
            $pagina = (int) end($pages[1]);
        }

        return [
            'fuente' => $fuente,
            'pagina' => $pagina,
        ];
    }

    protected function makeQuote(string $rawText, int $pos, int $length = 350): string
    {
        $start = max(0, $pos - 100);
        $quote = mb_substr($rawText, $start, $length);
        $quote = preg_replace('~--- DOCUMENTO:\s*.*?\s*---~u', '', $quote);
        $quote = preg_replace('~\[PAGINA\s+\d+\]~iu', '', $quote);

        return Str::limit($this->clean($quote), 350, '');
    }

    protected function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        return trim($text);
    }

    protected function clean(mixed $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)));
    }

    protected function isEmptyValue(mixed $value): bool
    {
        $text = $this->clean($value);
        $low = Str::lower($text);

        return $text === ''
            || in_array($low, [
                'no se encontro informacion',
                'no se encontró información',
                'no aplica',
                'n/a',
                'na',
                '-',
                '—',
                'sin dato',
                'sin informacion',
                'sin información',
            ], true);
    }
}
