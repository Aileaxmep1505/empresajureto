<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LicitacionPdf;
use App\Models\LicitacionPdfChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LicitacionPdfAiController extends Controller
{
    public function show(LicitacionPdf $licitacionPdf)
    {
        $messages = LicitacionPdfChatMessage::where('licitacion_pdf_id', $licitacionPdf->id)
            ->where('user_id', auth()->id())
            ->orderBy('id')
            ->limit(200)
            ->get();

        return view('admin.licitacion_pdfs.ai', [
            'pdf' => $licitacionPdf,
            'messages' => $messages,
        ]);
    }

    public function message(Request $request, LicitacionPdf $licitacionPdf)
    {
        try {
            $data = $request->validate([
                'message' => ['required', 'string', 'max:5000'],
            ]);

            $text = trim($data['message']);

            // Guardar mensaje del usuario
            LicitacionPdfChatMessage::create([
                'licitacion_pdf_id' => $licitacionPdf->id,
                'user_id' => auth()->id(),
                'role' => 'user',
                'content' => $text,
            ]);

            // Historial SOLO de este PDF y este usuario
            $history = LicitacionPdfChatMessage::where('licitacion_pdf_id', $licitacionPdf->id)
                ->where('user_id', auth()->id())
                ->orderByDesc('id')
                ->limit(16)
                ->get()
                ->reverse()
                ->values();

            // Vector store exclusivo para este PDF
            $vectorStoreId = $this->ensureVectorStoreForPdf($licitacionPdf);

            // Preguntar usando file_search SOLO contra ese store
            $answer = $this->askWithFileSearch($vectorStoreId, $history);

            // Guardar respuesta
            LicitacionPdfChatMessage::create([
                'licitacion_pdf_id' => $licitacionPdf->id,
                'user_id' => auth()->id(),
                'role' => 'assistant',
                'content' => $answer,
            ]);

            return response()->json([
                'ok' => true,
                'answer' => $answer,
            ]);
        } catch (\Throwable $e) {
            Log::error('PDF AI chat error', [
                'pdf_id' => $licitacionPdf->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function openaiKey(): string
    {
        // OJO: en tu services.php la llave es 'api_key'
        $key = (string) config('services.openai.api_key');

        if (!$key) {
            throw new \RuntimeException('Falta OPENAI_API_KEY en .env (o config cache viejo).');
        }

        return $key;
    }

    private function openaiBaseUrl(): string
    {
        return rtrim((string) config('services.openai.base_url', env('OPENAI_BASE_URL', 'https://api.openai.com')), '/');
    }

    private function openaiModel(): string
    {
        // Respeta tu .env (OPENAI_PRIMARY_MODEL)
        return (string) (config('services.openai.model')
            ?: config('services.openai.primary')
            ?: env('OPENAI_PRIMARY_MODEL', 'gpt-5-2025-08-07'));
    }

    private function ensureVectorStoreForPdf(LicitacionPdf $pdf): string
    {
        $apiKey  = $this->openaiKey();
        $baseUrl = $this->openaiBaseUrl();

        $meta = $pdf->meta ?? [];

        // Ya existe store para este PDF
        if (!empty($meta['openai_vector_store_id'])) {
            // Opcional: esperar a que esté listo por si quedó a medias
            $this->waitForVectorStoreReady($meta['openai_vector_store_id']);
            return $meta['openai_vector_store_id'];
        }

        if (!$pdf->original_path || !Storage::exists($pdf->original_path)) {
            throw new \RuntimeException('No existe el PDF físico.');
        }

        $fullPath = Storage::path($pdf->original_path);

        // A) subir archivo a OpenAI Files
        $fileResp = Http::withToken($apiKey)
            ->timeout((int) env('OPENAI_TIMEOUT', 300))
            ->attach('file', file_get_contents($fullPath), $pdf->original_filename ?? ("pdf_{$pdf->id}.pdf"))
            ->post($baseUrl . '/v1/files', [
                'purpose' => 'assistants',
            ]);

        if (!$fileResp->ok()) {
            throw new \RuntimeException('Error subiendo PDF a OpenAI: ' . $fileResp->status() . ' ' . $fileResp->body());
        }

        $openaiFileId = $fileResp->json('id');
        if (!$openaiFileId) {
            throw new \RuntimeException('OpenAI no devolvió file_id (respuesta inesperada).');
        }

        // B) crear vector store con ese file_id (store exclusivo por PDF)
        $vsResp = Http::withToken($apiKey)
            ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
            ->timeout((int) env('OPENAI_TIMEOUT', 300))
            ->post($baseUrl . '/v1/vector_stores', [
                'name' => 'licitacion_pdf_' . $pdf->id,
                'file_ids' => [$openaiFileId],
            ]);

        if (!$vsResp->ok()) {
            throw new \RuntimeException('Error creando vector store: ' . $vsResp->status() . ' ' . $vsResp->body());
        }

        $vectorStoreId = $vsResp->json('id');
        if (!$vectorStoreId) {
            throw new \RuntimeException('OpenAI no devolvió vector_store_id (respuesta inesperada).');
        }

        // Esperar a que termine de procesar el PDF (muy importante)
        $this->waitForVectorStoreReady($vectorStoreId);

        // Guardar en meta
        $meta['openai_file_id'] = $openaiFileId;
        $meta['openai_vector_store_id'] = $vectorStoreId;

        $pdf->meta = $meta;
        $pdf->save();

        return $vectorStoreId;
    }

    private function waitForVectorStoreReady(string $vectorStoreId): void
    {
        $apiKey  = $this->openaiKey();
        $baseUrl = $this->openaiBaseUrl();

        // 20 intentos * 1s = 20s (ajusta si quieres)
        for ($i = 0; $i < 20; $i++) {
            $r = Http::withToken($apiKey)
                ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
                ->timeout(30)
                ->get($baseUrl . "/v1/vector_stores/{$vectorStoreId}");

            if ($r->ok()) {
                $counts = $r->json('file_counts') ?: [];
                $inProgress = (int) ($counts['in_progress'] ?? 0);
                $failed     = (int) ($counts['failed'] ?? 0);

                if ($inProgress === 0 && $failed === 0) {
                    return; // listo
                }
            }

            usleep(1000 * 1000); // 1s
        }

        // Si no quedó listo, no truena: solo avisa por log
        Log::warning('Vector store sigue en progreso', ['vector_store_id' => $vectorStoreId]);
    }

    private function askWithFileSearch(string $vectorStoreId, $history): string
    {
        $apiKey  = $this->openaiKey();
        $baseUrl = $this->openaiBaseUrl();
        $model   = $this->openaiModel();

        $input = [];
        $input[] = [
            'role' => 'system',
            'content' => 'Eres un asistente experto. Responde SOLO con base en el PDF cargado. Si no está en el PDF, dilo.',
        ];

        foreach ($history as $m) {
            $input[] = [
                'role' => $m->role === 'assistant' ? 'assistant' : 'user',
                'content' => $m->content,
            ];
        }

        $resp = Http::withToken($apiKey)
            ->timeout((int) env('OPENAI_TIMEOUT', 300))
            ->post($baseUrl . '/v1/responses', [
                'model' => $model,
                'input' => $input,
                'tools' => [[
                    'type' => 'file_search',
                    'vector_store_ids' => [$vectorStoreId],
                    'max_num_results' => 6,
                ]],
                // útil para depurar/citas (opcional)
                // 'include' => ['file_search_call.results'],
            ]);

        if (!$resp->ok()) {
            return 'Error llamando IA: ' . $resp->status() . ' ' . $resp->body();
        }

        $json = $resp->json();

        // ✅ FIX: extraer texto correctamente (no siempre existe output_text en raíz)
        $text = $this->extractResponseText($json);

        if (!$text) {
            Log::warning('OpenAI response sin texto', ['json' => $json]);
            return 'No pude generar respuesta.';
        }

        return $text;
    }

    private function extractResponseText(array $json): ?string
    {
        // A veces existe directo:
        if (!empty($json['output_text']) && is_string($json['output_text'])) {
            $t = trim($json['output_text']);
            return $t !== '' ? $t : null;
        }

        // Normalmente viene en output[] -> message -> content[] -> output_text.text
        $out = $json['output'] ?? null;
        if (!is_array($out)) return null;

        $parts = [];

        foreach ($out as $item) {
            if (!is_array($item)) continue;
            if (($item['type'] ?? null) !== 'message') continue;
            if (($item['role'] ?? null) !== 'assistant') continue;

            $content = $item['content'] ?? [];
            if (!is_array($content)) continue;

            foreach ($content as $c) {
                if (!is_array($c)) continue;

                // output_text
                if (($c['type'] ?? null) === 'output_text' && isset($c['text']) && is_string($c['text'])) {
                    $parts[] = $c['text'];
                    continue;
                }

                // fallback: si algún modelo manda 'text' sin type
                if (isset($c['text']) && is_string($c['text'])) {
                    $parts[] = $c['text'];
                }
            }
        }

        $final = trim(implode("\n", $parts));
        return $final !== '' ? $final : null;
    }
}
