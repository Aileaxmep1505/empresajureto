<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LicitacionPdf;
use App\Models\LicitacionPdfChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

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

            LicitacionPdfChatMessage::create([
                'licitacion_pdf_id' => $licitacionPdf->id,
                'user_id' => auth()->id(),
                'role' => 'user',
                'content' => $text,
            ]);

            $history = LicitacionPdfChatMessage::where('licitacion_pdf_id', $licitacionPdf->id)
                ->where('user_id', auth()->id())
                ->orderByDesc('id')
                ->limit(16)
                ->get()
                ->reverse()
                ->values();

            $vectorStoreId = $this->ensureVectorStoreForPdf($licitacionPdf);

            // ✅ AHORA: respuesta + UNA sola fuente (la mejor)
            [$answer, $source] = $this->askWithFileSearchTop1($licitacionPdf, $vectorStoreId, $history);

            $payload = [
                'licitacion_pdf_id' => $licitacionPdf->id,
                'user_id' => auth()->id(),
                'role' => 'assistant',
                'content' => $answer,
            ];

            // si tu tabla tiene JSON "sources"
            $payload['sources'] = $source ? [$source] : null;

            LicitacionPdfChatMessage::create($payload);

            return response()->json([
                'ok' => true,
                'answer' => $answer,
                // ✅ SOLO UNA
                'source' => $source,
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

    /**
     * Viewer PDF.js con highlight (página + query)
     */
    public function viewer(Request $request, LicitacionPdf $licitacionPdf)
    {
        $page = max(1, (int) $request->query('page', 1));
        $q = (string) $request->query('q', '');

        return view('admin.licitacion_pdfs.viewer', [
            'pdf' => $licitacionPdf,
            'pdfUrl' => route('admin.licitacion-pdfs.preview', ['licitacionPdf' => $licitacionPdf->id]),
            'page' => $page,
            'q' => $q,
        ]);
    }

    /**
     * Descargar notas como PDF.
     */
    public function notesPdf(Request $request, LicitacionPdf $licitacionPdf)
    {
        $data = $request->validate([
            'notes' => ['required', 'string', 'max:50000'],
        ]);

        $notes = trim($data['notes']);
        if ($notes === '') {
            return response()->json(['ok' => false, 'message' => 'Notas vacías.'], 422);
        }

        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(true, 14);
        $pdf->AddPage('P', 'A4');

        $pdf->SetFont('Helvetica', 'B', 14);
        $title = 'Notas — ' . ($licitacionPdf->original_filename ?? ('PDF #' . $licitacionPdf->id));
        $pdf->MultiCell(0, 8, utf8_decode($title));
        $pdf->Ln(2);

        $pdf->SetFont('Helvetica', '', 11);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->MultiCell(0, 6, utf8_decode($notes));

        $binary = $pdf->Output('S');
        $filename = 'notas_pdf_' . $licitacionPdf->id . '.pdf';

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function ensureVectorStoreForPdf(LicitacionPdf $pdf): string
    {
        $apiKey = config('services.openai.api_key') ?: env('OPENAI_API_KEY');
        if (!$apiKey) throw new \RuntimeException('Falta OPENAI_API_KEY en .env');

        $meta = $pdf->meta ?? [];
        if (!empty($meta['openai_vector_store_id'])) return $meta['openai_vector_store_id'];

        if (!$pdf->original_path || !Storage::exists($pdf->original_path)) {
            throw new \RuntimeException('No existe el PDF físico.');
        }

        $baseUrl   = rtrim(config('services.openai.base_url') ?: env('OPENAI_BASE_URL', 'https://api.openai.com'), '/');
        $timeout   = (int) (config('services.openai.timeout') ?: env('OPENAI_TIMEOUT', 120));
        $projectId = config('services.openai.project_id') ?: env('OPENAI_PROJECT_ID');

        $headers = [];
        if ($projectId) $headers['OpenAI-Project'] = $projectId;

        $fullPath = Storage::path($pdf->original_path);

        $fileResp = Http::withToken($apiKey)
            ->withHeaders($headers)
            ->timeout($timeout)
            ->attach('file', file_get_contents($fullPath), $pdf->original_filename ?? ("pdf_{$pdf->id}.pdf"))
            ->post($baseUrl . '/v1/files', ['purpose' => 'assistants']);

        if (!$fileResp->ok()) {
            throw new \RuntimeException('Error subiendo PDF a OpenAI: ' . $fileResp->status() . ' ' . $fileResp->body());
        }

        $openaiFileId = $fileResp->json('id');
        if (!$openaiFileId) throw new \RuntimeException('OpenAI no devolvió file_id.');

        $vsResp = Http::withToken($apiKey)
            ->withHeaders(array_merge($headers, ['OpenAI-Beta' => 'assistants=v2']))
            ->timeout($timeout)
            ->post($baseUrl . '/v1/vector_stores', [
                'name' => 'licitacion_pdf_' . $pdf->id,
                'file_ids' => [$openaiFileId],
            ]);

        if (!$vsResp->ok()) {
            throw new \RuntimeException('Error creando vector store: ' . $vsResp->status() . ' ' . $vsResp->body());
        }

        $vectorStoreId = $vsResp->json('id');
        if (!$vectorStoreId) throw new \RuntimeException('OpenAI no devolvió vector_store_id.');

        $meta['openai_file_id'] = $openaiFileId;
        $meta['openai_vector_store_id'] = $vectorStoreId;

        $pdf->meta = $meta;
        $pdf->save();

        return $vectorStoreId;
    }

    /**
     * ✅ SOLO 1 fuente: la de mayor score.
     * Devuelve [answer, source|null]
     */
    private function askWithFileSearchTop1(LicitacionPdf $pdf, string $vectorStoreId, $history): array
    {
        $apiKey = config('services.openai.api_key') ?: env('OPENAI_API_KEY');
        if (!$apiKey) throw new \RuntimeException('Falta OPENAI_API_KEY en .env');

        $baseUrl   = rtrim(config('services.openai.base_url') ?: env('OPENAI_BASE_URL', 'https://api.openai.com'), '/');
        $timeout   = (int) (config('services.openai.timeout') ?: env('OPENAI_TIMEOUT', 120));
        $projectId = config('services.openai.project_id') ?: env('OPENAI_PROJECT_ID');

        $model = config('services.openai.primary')
            ?: config('services.openai.model')
            ?: env('OPENAI_PRIMARY_MODEL', 'gpt-5-2025-08-07');

        $headers = [];
        if ($projectId) $headers['OpenAI-Project'] = $projectId;

        $input = [[
            'role' => 'system',
            'content' =>
                "Responde CLARO y directo SOLO con base en el PDF.\n" .
                "Si no viene en el PDF, dilo.\n" .
                "NO pongas links dentro del texto de la respuesta.\n",
        ]];

        foreach ($history as $m) {
            $input[] = [
                'role' => $m->role === 'assistant' ? 'assistant' : 'user',
                'content' => $m->content,
            ];
        }

        $resp = Http::withToken($apiKey)
            ->withHeaders($headers)
            ->timeout($timeout)
            ->post($baseUrl . '/v1/responses', [
                'model' => $model,
                'input' => $input,
                'tools' => [[
                    'type' => 'file_search',
                    'vector_store_ids' => [$vectorStoreId],
                    'max_num_results' => 6,
                ]],
                'include' => ['file_search_call.results'],
            ]);

        if (!$resp->ok()) {
            return ['Error llamando IA: ' . $resp->status() . ' ' . $resp->body(), null];
        }

        $json = $resp->json();

        // ✅ output_text + fallback
        $answer = trim((string)($json['output_text'] ?? ''));
        if ($answer === '' && !empty($json['output']) && is_array($json['output'])) {
            $parts = [];
            foreach ($json['output'] as $out) {
                if (!is_array($out)) continue;
                if (($out['type'] ?? null) === 'message' && !empty($out['content']) && is_array($out['content'])) {
                    foreach ($out['content'] as $c) {
                        if (!is_array($c)) continue;
                        if (($c['type'] ?? null) === 'output_text' && isset($c['text'])) $parts[] = $c['text'];
                        if (($c['type'] ?? null) === 'text' && isset($c['text'])) {
                            $parts[] = is_array($c['text']) ? ($c['text']['value'] ?? '') : $c['text'];
                        }
                    }
                }
            }
            $answer = trim(implode("\n", array_filter($parts)));
        }
        if ($answer === '') $answer = 'No pude generar respuesta.';

        // results
        $results = [];
        foreach (($json['output'] ?? []) as $item) {
            if (!is_array($item)) continue;
            if (($item['type'] ?? null) === 'file_search_call') {
                $results = $item['results'] ?? [];
                break;
            }
        }

        if (empty($results)) {
            return [$answer, null];
        }

        // ✅ ordena por score desc y toma TOP 1
        usort($results, function ($a, $b) {
            $sa = is_array($a) ? ($a['score'] ?? 0) : 0;
            $sb = is_array($b) ? ($b['score'] ?? 0) : 0;
            return $sb <=> $sa;
        });
        $r = $results[0];

        $filename = $r['filename'] ?? ($r['file_name'] ?? 'PDF');
        $score    = $r['score'] ?? null;

        // excerpt
        $excerpt = '';
        if (isset($r['content']) && is_array($r['content'])) {
            $first = $r['content'][0] ?? null;
            $excerpt = is_array($first) ? (string)($first['text'] ?? '') : (string)$first;
        } elseif (isset($r['text'])) {
            $excerpt = (string)$r['text'];
        } elseif (isset($r['snippet'])) {
            $excerpt = (string)$r['snippet'];
        }
        $excerpt = trim(preg_replace("/\s+/", " ", $excerpt));

        // page
        $page = 1;
        if (isset($r['metadata']) && is_array($r['metadata'])) {
            $p = $r['metadata']['page'] ?? $r['metadata']['page_number'] ?? $r['metadata']['page_index'] ?? null;
            if (is_numeric($p)) $page = max(1, (int)$p);
        }

        // highlight “corto” (para buscar en viewer sin URL gigante)
        $highlight = $this->makeHighlightQuery($excerpt);

        // link al viewer con highlight (NO al preview nativo)
        $url = route('admin.licitacion-pdfs.ai.viewer', [
            'licitacionPdf' => $pdf->id,
            'page' => $page,
            'q' => $highlight,
        ]);

        $source = [
            'label' => $filename . " (pág. {$page})",
            'url' => $url,
            'score' => $score,
            'excerpt' => mb_substr($excerpt, 0, 450) . (mb_strlen($excerpt) > 450 ? '…' : ''),
            'page' => $page,
            'highlight' => $highlight,
            'filename' => $filename,
        ];

        return [$answer, $source];
    }

    private function makeHighlightQuery(string $excerpt): string
    {
        $t = trim($excerpt);
        if ($t === '') return '';

        // corta a 160
        $t = mb_substr($t, 0, 160);

        // intenta cortar por punto
        $pos = mb_strpos($t, '.');
        if ($pos !== false && $pos > 40) {
            $t = mb_substr($t, 0, $pos);
        }

        return trim($t);
    }
}
