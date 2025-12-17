<?php

namespace App\Jobs;

use App\Models\LicitacionPropuesta;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MatchLicitacionPropuestaItems implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $propuestaId) {}

    public function handle(): void
    {
        $propuesta = LicitacionPropuesta::with(['items'])->find($this->propuestaId);
        if (!$propuesta) {
            return;
        }

        $apiKey  = config('services.openai.api_key') ?: config('services.openai.key');
        $baseUrl = rtrim(config('services.openai.base_url', 'https://api.openai.com'), '/');

        // ✅ FORZAR GPT-5-2025-08-07 (nada de 4.1 / nada de mini)
        $model = 'gpt-5-2025-08-07';

        if (!$apiKey) {
            Log::warning('MatchLicitacionPropuestaItems: falta OpenAI API key');
            return;
        }

        foreach ($propuesta->items as $item) {

            // ✅ Si ya lo decidiste manualmente o ya está aplicado a product_id → no tocar
            if (!empty($item->manual_selected) || !empty($item->product_id)) {
                continue;
            }

            $queryText = trim((string)($item->descripcion_raw ?? ''));
            if ($queryText === '') {
                continue;
            }

            // =========================
            // 1) CANDIDATOS desde BD
            // =========================
            $cands = $this->findCandidates($queryText);

            if ($cands->isEmpty()) {
                // sin candidatos: lo dejamos como pending (sin sugerencia)
                $item->match_status = 'pending';
                $item->save();
                continue;
            }

            // payload candidatos (máx 12)
            $payloadCands = $cands->map(fn($p) => [
                'id'          => (int)$p->id,
                'sku'         => (string)($p->sku ?? ''),
                'name'        => (string)($p->name ?? ''),
                'brand'       => (string)($p->brand ?? ''),
                'unit'        => (string)($p->unit ?? ''),
                'description' => $p->description ? mb_substr((string)$p->description, 0, 240) : null,
                'price'       => is_numeric($p->price) ? (float)$p->price : null,
            ])->values()->all();

            // =========================
            // 2) OpenAI decide best_id
            // =========================
            $instructions = <<<TXT
Eres un experto en compras y catálogo (México).
Debes elegir el mejor producto del catálogo para cubrir el renglón de licitación.

Devuelve SOLO JSON válido:
{
  "best_id": 123,
  "score": 0-100,
  "reason": "breve"
}

Reglas:
- best_id debe ser UNO de los candidatos (o null si ninguno sirve).
- score alto solo si coincide tipo/medida/unidad/marca.
- Si ninguno sirve, best_id=null y score<=30.
TXT;

            $userText =
                "RENGLÓN LICITACIÓN:\n{$queryText}\n\n".
                "CANDIDATOS (JSON):\n".
                json_encode($payloadCands, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

            $resp = Http::withToken($apiKey)
                ->timeout(180)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($baseUrl.'/v1/responses', [
                    'model'        => $model,
                    'instructions' => $instructions,
                    'input'        => [[
                        'role'    => 'user',
                        'content' => [
                            ['type' => 'input_text', 'text' => $userText],
                        ],
                    ]],
                    // ⚠️ GPT-5: NO mandar temperature
                    'max_output_tokens' => 900,
                ]);

            if (!$resp->ok()) {
                Log::warning('Match IA falló', [
                    'item_id' => $item->id,
                    'status'  => $resp->status(),
                    'body'    => $resp->body(),
                ]);
                continue;
            }

            $raw = $this->extractOutputText($resp->json());
            $raw = $this->cleanupJsonText($raw);

            $data = json_decode($raw, true);
            if (!is_array($data)) {
                Log::warning('Match IA: JSON inválido', [
                    'item_id' => $item->id,
                    'raw'     => mb_substr((string)$raw, 0, 2500),
                ]);
                continue;
            }

            $bestId = $data['best_id'] ?? null;
            $score  = (int)($data['score'] ?? 0);
            $reason = trim((string)($data['reason'] ?? ''));

            // asegurar que bestId sea candidato
            $candIds = array_map(fn($x) => (int)$x['id'], $payloadCands);
            if ($bestId !== null && !in_array((int)$bestId, $candIds, true)) {
                $bestId = null;
                $score  = min($score, 30);
                $reason = $reason ?: 'best_id fuera de candidatos';
            }

            // =========================
            // 3) Guardar sugerencia (NO aplicar automático)
            // =========================
            $item->suggested_product_id = $bestId ? (int)$bestId : null;
            $item->match_score          = $score;
            $item->match_reason         = $reason ?: null;
            $item->match_status         = ($bestId && $score >= 40) ? 'suggested' : 'pending';
            $item->manual_selected      = false;
            $item->save();
        }
    }

    private function findCandidates(string $queryText)
    {
        // LIKE directo (limit 12)
        $cands = Product::query()
            ->select(['id','sku','name','description','brand','unit','price'])
            ->where(function($q) use ($queryText){
                $short = mb_substr($queryText, 0, 90);
                $q->where('name', 'like', '%'.$short.'%')
                  ->orWhere('sku', 'like', '%'.$short.'%')
                  ->orWhere('description', 'like', '%'.$short.'%');
            })
            ->limit(12)
            ->get();

        if ($cands->isNotEmpty()) {
            return $cands;
        }

        // keywords 3 palabras (>=4 chars)
        $words = preg_split('/\s+/', mb_strtolower($queryText));
        $words = array_values(array_filter($words, fn($w)=> mb_strlen($w) >= 4));
        $words = array_slice($words, 0, 3);

        if (empty($words)) {
            return $cands;
        }

        $q2 = Product::query()->select(['id','sku','name','description','brand','unit','price']);
        foreach ($words as $w) {
            $q2->where(function($q) use ($w){
                $q->where('name','like','%'.$w.'%')
                  ->orWhere('description','like','%'.$w.'%')
                  ->orWhere('sku','like','%'.$w.'%');
            });
        }

        return $q2->limit(12)->get();
    }

    private function extractOutputText(array $json): string
    {
        $raw = '';

        // ✅ Algunas respuestas traen output_text directo
        if (isset($json['output_text']) && is_string($json['output_text']) && trim($json['output_text']) !== '') {
            return trim($json['output_text']);
        }

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
            $raw = (string)$json['output'][0]['content'][0]['text'];
        }

        return trim((string)$raw);
    }

    private function cleanupJsonText(?string $raw): string
    {
        $raw = trim((string)$raw);
        if ($raw === '') return '';

        // quitar fences
        $raw = preg_replace('/^```(?:json)?/i', '', $raw);
        $raw = preg_replace('/```$/', '', $raw);
        $raw = trim($raw);

        // recortar a primer { o [
        $firstObj = strpos($raw, '{');
        $firstArr = strpos($raw, '[');

        $start = null;
        if ($firstObj !== false && $firstArr !== false) $start = min($firstObj, $firstArr);
        elseif ($firstObj !== false) $start = $firstObj;
        elseif ($firstArr !== false) $start = $firstArr;

        if ($start !== null) {
            $raw = substr($raw, $start);
        }

        // recortar a último } o ]
        $lastObj = strrpos($raw, '}');
        $lastArr = strrpos($raw, ']');

        $end = null;
        if ($lastObj !== false && $lastArr !== false) $end = max($lastObj, $lastArr);
        elseif ($lastObj !== false) $end = $lastObj;
        elseif ($lastArr !== false) $end = $lastArr;

        if ($end !== null) {
            $raw = substr($raw, 0, $end + 1);
        }

        return trim($raw);
    }
}
