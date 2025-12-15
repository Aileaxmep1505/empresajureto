<?php

namespace App\Services;

use App\Models\LicitacionPropuesta;
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
        // ✅ SYNC sin que PHP mate la ejecución
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

        // ✅ SOLO GPT-5 (según tus modelos visibles)
        // Tip: pon esto en services.openai.primary / fallbacks (pero aquí dejamos defaults seguros).
        $primary   = config('services.openai.primary', 'gpt-5-chat-latest');
        $fallbacks = config('services.openai.fallbacks', ['gpt-5-2025-08-07', 'gpt-5-mini']);
        $models    = array_values(array_filter(array_merge([$primary], (array)$fallbacks)));

        // ✅ modelo barato/rápido para repair
        $repairModel = config('services.openai.json_repair_model', 'gpt-5-mini');

        // ==========================================================
        // 1) Subir PDF
        // ==========================================================
        $upload = Http::withToken($apiKey)
            ->timeout(420)
            ->connectTimeout(30)
            ->attach('file', Storage::get($path), basename($path))
            ->post($baseUrl . '/v1/files', [
                // purpose recomendado para uso “ad-hoc” en prompts con archivos
                'purpose' => 'user_data',
            ]);

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

        // ✅ Espera + validación de propiedad (mitiga “Unknown error while validating file ownership”)
        $this->waitForFileReady($apiKey, $baseUrl, $fileId);

        // ==========================================================
        // 2) Prompt (mejorado para renglones)
        // ==========================================================
        $systemPrompt = <<<TXT
Eres un asistente experto en análisis de licitaciones públicas en México.

Tarea:
- Analiza el PDF adjunto (puede contener texto, tablas o escaneos).
- Extrae TODOS los renglones solicitados (productos/servicios).
- Ignora: encabezados, bases legales, firmas, sellos, texto administrativo, notas generales.

Importante:
- Si el documento tiene tablas, cada fila/celda relevante debe convertirse en un item.
- Si un renglón viene partido en varias líneas, júntalo en una sola "descripcion".
- NO inventes unidades ni cantidades: si no se ve, usa cantidad=1 y unidad=null.

DEVUELVE ÚNICAMENTE JSON VÁLIDO (sin markdown, sin explicación) con esta forma EXACTA:

{
  "items": [
    {
      "descripcion": "Descripción completa del renglón",
      "cantidad": 1,
      "unidad": "PIEZA / CAJA / SERVICIO / etc (o null)",
      "precio_referencia": null
    }
  ]
}
TXT;

        // ==========================================================
        // 3) Responses (SIN temperature; GPT-5 puede rechazarlo)
        // ==========================================================
        $response  = null;
        $lastErr   = null;
        $usedModel = null;

        foreach ($models as $tryModel) {
            $usedModel = $tryModel;

            $attempts = 0;
            $maxAttempts = 3;

            while ($attempts < $maxAttempts) {
                $attempts++;

                $response = Http::withToken($apiKey)
                    ->timeout(720)
                    ->connectTimeout(30)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($baseUrl . '/v1/responses', [
                        'model'        => $tryModel,
                        'instructions' => $systemPrompt,
                        'input'        => [[
                            'role'    => 'user',
                            'content' => [
                                ['type' => 'input_text', 'text' => 'Devuelve SOLO el JSON solicitado.'],
                                ['type' => 'input_file', 'file_id' => $fileId],
                            ],
                        ]],
                        // ⚠️ GPT-5: NO mandar temperature/presence_penalty
                        'max_output_tokens' => 6500,
                    ]);

                if ($response->ok()) {
                    Log::info('OpenAI OK', [
                        'model' => $tryModel,
                        'propuesta_id' => $propuesta->id,
                        'split_index' => $splitIndex,
                    ]);
                    break 2;
                }

                $lastErr = [
                    'model'   => $tryModel,
                    'status'  => $response->status(),
                    'attempt' => $attempts,
                    'body'    => $response->body(),
                ];
                Log::warning('OpenAI intento falló', $lastErr);

                $status = $response->status();
                $body   = $response->body();

                // Reintentos útiles:
                // - 5xx
                // - 429 rate limit
                // - ownership validation intermitente (a veces lo tira como 500)
                if ($status >= 500 || $status === 429 || str_contains($body, 'file ownership')) {
                    usleep(700000 * $attempts); // 0.7s / 1.4s / 2.1s
                    continue;
                }

                // 4xx “real”: no reintentar este modelo
                break;
            }
        }

        if (!$response || !$response->ok()) {
            Log::error('Error OpenAI Responses (todos los modelos fallaron)', $lastErr ?? []);
            throw new \Exception('Error procesando PDF con OpenAI.');
        }

        // ==========================================================
        // 4) Texto
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
                'raw' => mb_substr($raw, 0, 8000),
            ]);

            $rawFixed = $this->repairJsonWithOpenAi($apiKey, $baseUrl, $repairModel, $raw);
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
            throw new \Exception('La IA no devolvió items[] en el JSON.');
        }

        // ==========================================================
        // 6) Guardar items
        // ==========================================================
        $createdIds = [];

        foreach ($items as $row) {
            if (!is_array($row)) continue;

            $desc = trim((string)($row['descripcion'] ?? ''));
            if ($desc === '') continue;

            $qty = $row['cantidad'] ?? 1;
            if (is_string($qty)) {
                $clean = preg_replace('/[^0-9]/', '', $qty);
                $qty = is_numeric($clean) ? (int)$clean : 1;
            }
            $qty = max(1, (int)$qty);

            $unidad = $row['unidad'] ?? null;
            if (is_string($unidad)) {
                $unidad = trim($unidad);
                if ($unidad === '') $unidad = null;
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

        return $createdIds;
    }

    /**
     * Espera a que OpenAI “reconozca” el archivo antes de usarlo en Responses.
     * Mitiga errores intermitentes de ownership/validación.
     */
    private function waitForFileReady(string $apiKey, string $baseUrl, string $fileId): void
    {
        // Espera mínima + retries cortos
        usleep(350000); // 0.35s

        $tries = 0;
        $maxTries = 6;

        while ($tries < $maxTries) {
            $tries++;

            $r = Http::withToken($apiKey)
                ->timeout(30)
                ->connectTimeout(10)
                ->get($baseUrl . '/v1/files/' . $fileId);

            if ($r->ok()) return;

            // si falla, dormimos un poco y reintentamos
            usleep(250000 * $tries); // 0.25 / 0.5 / 0.75 / ...
        }
    }

    private function extractOutputText(array $json): string
    {
        // ✅ Algunas respuestas traen output_text directo
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

    private function cleanupJsonText(string $raw): string
    {
        $raw = trim($raw);

        // quitar fences
        $raw = preg_replace('/^```(?:json)?/i', '', $raw);
        $raw = preg_replace('/```$/', '', $raw);
        $raw = trim($raw);

        // recortar antes del primer { o [
        $firstObj = strpos($raw, '{');
        $firstArr = strpos($raw, '[');

        $start = null;
        if ($firstObj !== false && $firstArr !== false) $start = min($firstObj, $firstArr);
        elseif ($firstObj !== false) $start = $firstObj;
        elseif ($firstArr !== false) $start = $firstArr;

        if ($start !== null) $raw = substr($raw, $start);

        // recortar hasta el último } o ]
        $lastObj = strrpos($raw, '}');
        $lastArr = strrpos($raw, ']');

        $end = null;
        if ($lastObj !== false && $lastArr !== false) $end = max($lastObj, $lastArr);
        elseif ($lastObj !== false) $end = $lastObj;
        elseif ($lastArr !== false) $end = $lastArr;

        if ($end !== null) $raw = substr($raw, 0, $end + 1);

        return trim($raw);
    }

    /**
     * Decodifica JSON incluso si viene como string con escapes: "{ \"items\": [...] }"
     */
    private function decodeJsonLenient(string $raw)
    {
        $raw = trim($raw);
        if ($raw === '') return null;

        $data = json_decode($raw, true);
        if (is_array($data)) return $data;

        // a veces viene JSON dentro de string con comillas escapadas
        if (str_starts_with($raw, '"') && str_ends_with($raw, '"')) {
            $unquoted = json_decode($raw, true); // quita escapes
            if (is_string($unquoted)) {
                $unquoted = $this->cleanupJsonText($unquoted);
                $data2 = json_decode($unquoted, true);
                if (is_array($data2)) return $data2;
            }
        }

        return null;
    }

    /**
     * 2º llamada a OpenAI para reparar JSON (cuando viene cortado o malformado)
     */
    private function repairJsonWithOpenAi(string $apiKey, string $baseUrl, string $model, string $raw): string
    {
        $prompt = <<<TXT
Devuelve ÚNICAMENTE JSON VÁLIDO (sin texto extra, sin markdown).
Repara el siguiente contenido para que sea JSON válido con forma EXACTA:

{
  "items": [
    {"descripcion":"", "cantidad":1, "unidad":null, "precio_referencia":null}
  ]
}

Contenido a reparar:
TXT;

        $resp = Http::withToken($apiKey)
            ->timeout(180)
            ->connectTimeout(30)
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
