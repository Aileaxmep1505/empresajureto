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

        $apiKey  = config('services.openai.api_key') ?: config('services.openai.key');
        $baseUrl = rtrim(config('services.openai.base_url', 'https://api.openai.com'), '/');
        $model   = config('services.openai.model_extract', 'gpt-4.1-mini');

        if (!$apiKey) {
            throw new \Exception('Falta API key de OpenAI.');
        }

        // 1) subir pdf
        $upload = Http::withToken($apiKey)
            ->timeout(240)
            ->attach('file', Storage::get($path), basename($path))
            ->post($baseUrl . '/v1/files', ['purpose' => 'user_data']);

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

        // 2) prompt
        $systemPrompt = <<<TXT
Eres un asistente experto en análisis de licitaciones públicas en México.

Analiza el PDF adjunto (puede contener texto, tablas o escaneos).
Extrae TODOS los renglones solicitados (productos/servicios).
Ignora encabezados, bases legales, firmas, sellos, texto administrativo.

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

Reglas:
- Solo JSON. Nada más.
- cantidad: número entero. Si no se ve, usa 1.
- unidad: texto o null.
- No inventes datos.
TXT;

        // 3) responses
        $response = Http::withToken($apiKey)
            ->timeout(300)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($baseUrl . '/v1/responses', [
                'model'        => $model,
                'instructions' => $systemPrompt,
                'input'        => [[
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'input_text', 'text' => 'Devuelve SOLO el JSON solicitado.'],
                        ['type' => 'input_file', 'file_id' => $fileId],
                    ],
                ]],
                'temperature'       => 0.0,
                'max_output_tokens' => 7000,
            ]);

        if (!$response->ok()) {
            Log::error('Error OpenAI Responses', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \Exception('Error procesando PDF con OpenAI.');
        }

        // 4) texto
        $raw = $this->extractOutputText($response->json());
        if (!$raw) {
            throw new \Exception('OpenAI no devolvió texto interpretable.');
        }

        $raw = $this->cleanupJsonText($raw);

        // 5) parse + repair si falla
        $data = $this->decodeJsonLenient($raw);

        if (!is_array($data)) {
            Log::warning('JSON inválido de OpenAI (primer intento)', [
                'raw' => mb_substr($raw, 0, 8000),
            ]);

            $rawFixed = $this->repairJsonWithOpenAi($apiKey, $baseUrl, $model, $raw);
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

        // 6) guardar items (usa tus columnas reales)
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

            // ✅ OJO: aquí NO metemos columnas que no existen en tu tabla.
            // Tu tabla tiene: product_id, descripcion_raw, match_score, motivo_seleccion, unidad_propuesta, cantidad_propuesta, precio_unitario, subtotal, notas.
            // (Si luego agregas suggested_products/match_status/match_reason, ya lo puedes añadir.)
            $created = $propuesta->items()->create([
                'licitacion_request_item_id' => null,   // debe ser nullable en BD
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

    private function extractOutputText(array $json): string
    {
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
                'temperature'       => 0.0,
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
