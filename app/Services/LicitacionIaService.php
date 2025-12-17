<?php

namespace App\Services;

use App\Models\LicitacionPropuesta;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LicitacionIaService
{
    /**
     * Procesa un split con OpenAI y CREA items.
     * ✅ Retorna array de IDs creados para poder hacer matching SYNC solo a esos renglones.
     */
    public function processSplitWithAi(LicitacionPropuesta $propuesta, int $splitIndex): array
    {
        // ✅ SYNC sin que PHP mate la ejecución en splits pesados
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('default_socket_timeout', '900');

        $pdf = $propuesta->licitacionPdf;
        if (!$pdf) {
            throw new \Exception('La propuesta no tiene PDF asociado.');
        }

        $meta   = $pdf->meta ?? [];
        $splits = $meta['splits'] ?? [];

        if (!isset($splits[$splitIndex])) {
            throw new \Exception("Split {$splitIndex} no existe.");
        }

        $split = $splits[$splitIndex];
        $path  = $split['path'] ?? null;

        if (!$path || !Storage::exists($path)) {
            throw new \Exception('No se encontró el PDF recortado para este split.');
        }

        // ✅ OpenAI config
        $apiKey  = config('services.openai.api_key') ?: config('services.openai.key');
        $baseUrl = rtrim(config('services.openai.base_url', 'https://api.openai.com'), '/');

        if (!$apiKey) {
            throw new \Exception('Falta API key de OpenAI.');
        }

        // ✅ Timeouts y retries (configurables)
        $timeoutSec         = (int) config('services.openai.timeout', 720);          // total request timeout
        $connectTimeoutSec  = (int) config('services.openai.connect_timeout', 60);   // connect/handshake timeout
        $maxRetriesPerModel = (int) config('services.openai.max_retries_per_model', 3);
        $retryBaseDelayMs   = (int) config('services.openai.retry_base_delay_ms', 700);

        // ✅ SOLO GPT-5 para extracción principal
        $primary   = 'gpt-5-2025-08-07';
        $fallbacks = config('services.openai.fallbacks', ['gpt-5-chat-latest']);
        $models    = array_values(array_unique(array_merge([$primary], (array) $fallbacks)));

        // ✅ modelo barato/rápido SOLO para reparar JSON
        $repairModel = config('services.openai.json_repair_model', 'gpt-5-mini');

        // ==========================================================
        // 1) Subir PDF
        // ==========================================================
        try {
            $upload = Http::withToken($apiKey)
                ->timeout(max(420, $timeoutSec))
                ->connectTimeout($connectTimeoutSec)
                ->attach('file', Storage::get($path), basename($path))
                ->post($baseUrl . '/v1/files', [
                    'purpose' => 'user_data',
                ]);
        } catch (ConnectionException $e) {
            Log::error('Timeout/Connection subiendo PDF a OpenAI', [
                'error' => $e->getMessage(),
                'path'  => $path,
            ]);
            throw new \Exception('No se pudo subir el PDF a OpenAI (timeout de red).');
        }

        if (!$upload->ok()) {
            Log::error('Error subiendo PDF a OpenAI', [
                'status' => $upload->status(),
                'body'   => $upload->body(),
            ]);
            throw new \Exception('No se pudo subir el PDF a OpenAI.');
        }

        $fileId = $upload->json('id');
        if (!$fileId) {
            throw new \Exception('OpenAI no devolvió file_id.');
        }

        // ✅ Espera + validación de propiedad
        $this->waitForFileReady($apiKey, $baseUrl, $fileId, $timeoutSec, $connectTimeoutSec);

        // ==========================================================
        // 2) Prompt (SIN resumir: texto literal)
        // ==========================================================
        $systemPrompt = <<<TXT
Eres un asistente experto en análisis de licitaciones públicas en México.

Tarea:
- Analiza el PDF adjunto (puede contener texto, tablas o escaneos).
- Extrae TODOS los renglones solicitados (productos/servicios), SIN OMITIR NINGUNO.
- Ignora: encabezados, bases legales, firmas, sellos, texto administrativo, notas generales.

Reglas IMPORTANTES para NO perder renglones:
- Si hay tablas: cada FILA de producto/servicio = 1 item.
- Si hay “Partida / Renglón / No. / Concepto”: inclúyelos dentro de "descripcion" si ayudan a identificar el renglón.
- Si un renglón viene partido en varias líneas, júntalo en una sola "descripcion".

MUY IMPORTANTE SOBRE "descripcion":
- "descripcion" debe ser una COPIA TEXTUAL del contenido del PDF.
- NO resumas, NO reformules, NO cambies el orden de las palabras.
- NO elimines palabras, NO sustituyas por sinónimos, NO agregues texto inventado.
- Solo puedes:
  - Unir líneas cortadas por saltos de línea.
  - Normalizar espacios en blanco (ej. reemplazar saltos de línea por un solo espacio).
- Mantén las mismas palabras, en el mismo orden, con el mismo contenido semántico.

Sobre otros campos:
- Si no se ve claramente cantidad o unidad, usa cantidad=1 y unidad=null.
- "precio_referencia" siempre null (no lo inventes).

DEVUELVE ÚNICAMENTE JSON VÁLIDO (sin markdown, sin explicación) con esta forma EXACTA:

{
  "items": [
    {
      "descripcion": "Texto literal del renglón copiado del PDF",
      "cantidad": 1,
      "unidad": "PIEZA / CAJA / SERVICIO / etc (o null)",
      "precio_referencia": null
    }
  ]
}
TXT;

        // ==========================================================
        // 3) Llamada a Responses con retries (incluye cURL 28)
        // ==========================================================
        $response  = null;
        $lastErr   = null;
        $usedModel = null;

        foreach ($models as $tryModel) {
            $usedModel = $tryModel;
            $attempts  = 0;

            while ($attempts < max(1, $maxRetriesPerModel)) {
                $attempts++;

                try {
                    $response = Http::withToken($apiKey)
                        ->timeout($timeoutSec)               // timeout total
                        ->connectTimeout($connectTimeoutSec) // connect timeout
                        ->withHeaders(['Content-Type' => 'application/json'])
                        ->post($baseUrl . '/v1/responses', [
                            'model'        => $tryModel,
                            'instructions' => $systemPrompt,
                            'input'        => [[
                                'role'    => 'user',
                                'content' => [
                                    [
                                        'type' => 'input_text',
                                        'text' => 'Devuelve SOLO el JSON solicitado. No omitas ningún renglón. MUY IMPORTANTE: la "descripcion" debe ser copia TEXTUAL del PDF, sin resumir ni cambiar palabras.',
                                    ],
                                    ['type' => 'input_file', 'file_id' => $fileId],
                                ],
                            ]],
                            'max_output_tokens' => 9000,
                        ]);
                } catch (ConnectionException $e) {
                    $lastErr = [
                        'model'   => $tryModel,
                        'attempt' => $attempts,
                        'type'    => 'connection_exception',
                        'error'   => $e->getMessage(),
                    ];

                    Log::warning('OpenAI timeout/connection (reintentando)', $lastErr);

                    $sleepMs = $retryBaseDelayMs * $attempts;
                    usleep($sleepMs * 1000);
                    continue;
                }

                if ($response && $response->ok()) {
                    Log::info('OpenAI OK', [
                        'model'        => $tryModel,
                        'propuesta_id' => $propuesta->id,
                        'split_index'  => $splitIndex,
                    ]);
                    break 2;
                }

                $lastErr = [
                    'model'   => $tryModel,
                    'status'  => $response?->status(),
                    'attempt' => $attempts,
                    'body'    => $response?->body(),
                ];
                Log::warning('OpenAI intento falló', $lastErr);

                $status = (int) ($response?->status() ?? 0);
                $body   = (string) ($response?->body() ?? '');

                if ($status >= 500 || $status === 429 || str_contains($body, 'file ownership')) {
                    $sleepMs = $retryBaseDelayMs * $attempts;
                    usleep($sleepMs * 1000);
                    continue;
                }

                // 4xx real: no vale la pena reintentar este modelo
                break;
            }
        }

        if (!$response || !$response->ok()) {
            Log::error('Error OpenAI Responses (todos los modelos fallaron)', $lastErr ?? []);
            throw new \Exception('Error procesando PDF con OpenAI.');
        }

        // ==========================================================
        // 4) Texto crudo
        // ==========================================================
        $raw = $this->extractOutputText($response->json());
        if (!$raw) {
            throw new \Exception('OpenAI no devolvió texto interpretable.');
        }

        $raw = $this->cleanupJsonText($raw);

        // ==========================================================
        // 5) Parse + repair si falla
        // ==========================================================
        $data = $this->decodeJsonLenient($raw);

        if (!is_array($data)) {
            Log::warning('JSON inválido de OpenAI (primer intento)', [
                'model' => $usedModel,
                'raw'   => mb_substr($raw, 0, 8000),
            ]);

            $rawFixed = $this->repairJsonWithOpenAi(
                $apiKey,
                $baseUrl,
                $repairModel,
                $raw,
                $timeoutSec,
                $connectTimeoutSec
            );
            $rawFixed = $this->cleanupJsonText($rawFixed);

            $data = $this->decodeJsonLenient($rawFixed);

            if (!is_array($data)) {
                Log::warning('JSON inválido de OpenAI (después de repair)', [
                    'raw_fixed' => mb_substr($rawFixed, 0, 8000),
                ]);
                throw new \Exception('La IA no devolvió JSON válido.');
            }
        }

        // normalizar items
        $items = [];
        if (isset($data['items']) && is_array($data['items'])) {
            $items = $data['items'];
        } elseif (array_is_list($data)) {
            $items = $data;
        }

        if (empty($items)) {
            Log::warning('La IA no devolvió items[] en el JSON', [
                'propuesta_id' => $propuesta->id,
                'split_index'  => $splitIndex,
                'raw_snippet'  => mb_substr($raw ?? '', 0, 2000),
            ]);
            throw new \Exception('La IA no devolvió items[] en el JSON.');
        }

        // ==========================================================
        // 6) Guardar items
        // ==========================================================
        $createdIds = [];

        foreach ($items as $row) {
            if (!is_array($row)) {
                continue;
            }

            $desc = trim((string)($row['descripcion'] ?? ''));
            if ($desc === '') {
                continue;
            }

            $qty = $row['cantidad'] ?? 1;
            if (is_string($qty)) {
                $clean = preg_replace('/[^0-9]/', '', $qty);
                $qty   = is_numeric($clean) ? (int) $clean : 1;
            }
            $qty = max(1, (int) $qty);

            $unidad = $row['unidad'] ?? null;
            if (is_string($unidad)) {
                $unidad = trim($unidad);
                if ($unidad === '') {
                    $unidad = null;
                }
            }

            $created = $propuesta->items()->create([
                'licitacion_request_item_id' => null,
                'product_id'                 => null,

                'descripcion_raw'            => $desc,
                'match_score'                => null,
                'motivo_seleccion'           => null,

                'unidad_propuesta'           => $unidad,
                'cantidad_propuesta'         => $qty,
                'precio_unitario'            => null,
                'subtotal'                   => 0,
                'notas'                      => null,
            ]);

            $createdIds[] = $created->id;
        }

        if (empty($createdIds)) {
            throw new \Exception('No se extrajeron renglones después de procesar el JSON.');
        }

        Log::info('Extracción IA completada', [
            'propuesta_id' => $propuesta->id,
            'split_index'  => $splitIndex,
            'items_count'  => count($createdIds),
        ]);

        return $createdIds;
    }

    /**
     * Espera a que OpenAI “reconozca” el archivo antes de usarlo en Responses.
     */
    private function waitForFileReady(
        string $apiKey,
        string $baseUrl,
        string $fileId,
        int $timeoutSec = 60,
        int $connectTimeoutSec = 30
    ): void {
        usleep(350000); // 0.35s

        $tries    = 0;
        $maxTries = 10;

        while ($tries < $maxTries) {
            $tries++;

            try {
                $r = Http::withToken($apiKey)
                    ->timeout(min(60, max(15, $timeoutSec)))
                    ->connectTimeout($connectTimeoutSec)
                    ->get($baseUrl . '/v1/files/' . $fileId);
            } catch (ConnectionException $e) {
                usleep(250000 * $tries);
                continue;
            }

            if ($r->ok()) {
                return;
            }

            usleep(250000 * $tries); // 0.25 / 0.5 / 0.75 / ...
        }
    }

    /**
     * Extrae el texto principal de la respuesta de /v1/responses
     */
    private function extractOutputText(array $json): string
    {
        if (isset($json['output_text']) && is_string($json['output_text']) && trim($json['output_text']) !== '') {
            return trim($json['output_text']);
        }

        $raw = '';

        if (isset($json['output']) && is_array($json['output'])) {
            foreach ($json['output'] as $out) {
                if (($out['type'] ?? null) === 'message') {
                    foreach (($out['content'] ?? []) as $c) {
                        if (($c['type'] ?? null) === 'output_text' && isset($c['text'])) {
                            $raw .= $c['text'];
                        }
                    }
                }
            }
        }

        if (!$raw && isset($json['output'][0]['content'][0]['text'])) {
            $raw = (string) $json['output'][0]['content'][0]['text'];
        }

        return trim((string) $raw);
    }

    /**
     * Limpia texto “ensuciado” (markdown, texto antes/después del JSON)
     */
    private function cleanupJsonText(string $raw): string
    {
        $raw = trim($raw);

        // quitar fences ```json ... ```
        $raw = preg_replace('/^```(?:json)?/i', '', $raw);
        $raw = preg_replace('/```$/', '', $raw);
        $raw = trim($raw);

        // recortar antes del primer { o [
        $firstObj = strpos($raw, '{');
        $firstArr = strpos($raw, '[');

        $start = null;
        if ($firstObj !== false && $firstArr !== false) {
            $start = min($firstObj, $firstArr);
        } elseif ($firstObj !== false) {
            $start = $firstObj;
        } elseif ($firstArr !== false) {
            $start = $firstArr;
        }

        if ($start !== null) {
            $raw = substr($raw, $start);
        }

        // recortar hasta el último } o ]
        $lastObj = strrpos($raw, '}');
        $lastArr = strrpos($raw, ']');

        $end = null;
        if ($lastObj !== false && $lastArr !== false) {
            $end = max($lastObj, $lastArr);
        } elseif ($lastObj !== false) {
            $end = $lastObj;
        } elseif ($lastArr !== false) {
            $end = $lastArr;
        }

        if ($end !== null) {
            $raw = substr($raw, 0, $end + 1);
        }

        return trim($raw);
    }

    /**
     * Decodifica JSON incluso si viene como string con escapes: "{ \"items\": [...] }"
     */
    private function decodeJsonLenient(string $raw)
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $data = json_decode($raw, true);
        if (is_array($data)) {
            return $data;
        }

        // a veces viene JSON dentro de un string con comillas escapadas
        if (str_starts_with($raw, '"') && str_ends_with($raw, '"')) {
            $unquoted = json_decode($raw, true); // quita escapes
            if (is_string($unquoted)) {
                $unquoted = $this->cleanupJsonText($unquoted);
                $data2    = json_decode($unquoted, true);
                if (is_array($data2)) {
                    return $data2;
                }
            }
        }

        return null;
    }

    /**
     * Segunda llamada a OpenAI para reparar JSON (cuando viene cortado o malformado)
     */
    private function repairJsonWithOpenAi(
        string $apiKey,
        string $baseUrl,
        string $model,
        string $raw,
        int $timeoutSec = 180,
        int $connectTimeoutSec = 30
    ): string {
        $prompt = <<<TXT
Devuelve ÚNICAMENTE JSON VÁLIDO (sin texto extra, sin markdown).
Repara el siguiente contenido para que sea JSON válido con la forma EXACTA:

{
  "items": [
    {"descripcion":"", "cantidad":1, "unidad":null, "precio_referencia":null}
  ]
}

Contenido a reparar:
TXT;

        try {
            $resp = Http::withToken($apiKey)
                ->timeout(max(180, $timeoutSec))
                ->connectTimeout($connectTimeoutSec)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($baseUrl . '/v1/responses', [
                    'model'        => $model,
                    'instructions' => 'Eres un validador y reparador de JSON. Regresa SOLO JSON válido.',
                    'input'        => [[
                        'role'    => 'user',
                        'content' => [
                            ['type' => 'input_text', 'text' => $prompt . "\n\n" . $raw],
                        ],
                    ]],
                    'max_output_tokens' => 4500,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('OpenAI repair JSON timeout/connection', ['error' => $e->getMessage()]);
            return $raw;
        }

        if (!$resp->ok()) {
            Log::warning('OpenAI repair JSON falló', [
                'status' => $resp->status(),
                'body'   => $resp->body(),
            ]);
            return $raw;
        }

        $fixed = $this->extractOutputText($resp->json());
        return $fixed ?: $raw;
    }
}
