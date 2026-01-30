<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LicitacionPdf;
use App\Models\LicitacionPdfChatMessage;
use App\Models\LicitacionPdfChecklist;
use App\Models\LicitacionPdfChecklistItem;
use App\Services\IlovePdfService;
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
      'pdf'      => $licitacionPdf,
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
        'user_id'           => auth()->id(),
        'role'              => 'user',
        'content'           => $text,
      ]);

      $history = LicitacionPdfChatMessage::where('licitacion_pdf_id', $licitacionPdf->id)
        ->where('user_id', auth()->id())
        ->orderByDesc('id')
        ->limit(16)
        ->get()
        ->reverse()
        ->values();

      $vectorStoreId = $this->ensureVectorStoreForPdf($licitacionPdf);

      [$answer, $source] = $this->askWithFileSearchTop1($licitacionPdf, $vectorStoreId, $history);

      $payload = [
        'licitacion_pdf_id' => $licitacionPdf->id,
        'user_id'           => auth()->id(),
        'role'              => 'assistant',
        'content'           => $answer,
      ];
      $payload['sources'] = $source ? [$source] : null;

      LicitacionPdfChatMessage::create($payload);

      return response()->json([
        'ok'     => true,
        'answer' => $answer,
        'source' => $source,
      ]);
    } catch (\Throwable $e) {
      Log::error('PDF AI chat error', [
        'pdf_id'  => $licitacionPdf->id,
        'user_id' => auth()->id(),
        'error'   => $e->getMessage(),
      ]);

      return response()->json([
        'ok'      => false,
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  public function viewer(Request $request, LicitacionPdf $licitacionPdf)
  {
    $page = max(1, (int) $request->query('page', 1));
    $q    = (string) $request->query('q', '');

    return view('admin.licitacion_pdfs.viewer', [
      'pdf'    => $licitacionPdf,
      'pdfUrl' => route('admin.licitacion-pdfs.preview', ['licitacionPdf' => $licitacionPdf->id]),
      'page'   => $page,
      'q'      => $q,
    ]);
  }

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

    $binary   = $pdf->Output('S');
    $filename = 'notas_pdf_' . $licitacionPdf->id . '.pdf';

    return response($binary, 200, [
      'Content-Type'        => 'application/pdf',
      'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ]);
  }

  /**
   * Pantalla checklist
   */
  public function checklist(LicitacionPdf $licitacionPdf)
  {
    $checklist = LicitacionPdfChecklist::where('licitacion_pdf_id', $licitacionPdf->id)
      ->where('user_id', auth()->id())
      ->first();

    $items = [];
    if ($checklist) {
      $items = LicitacionPdfChecklistItem::where('checklist_id', $checklist->id)
        ->orderBy('section')
        ->orderBy('sort')
        ->get();
    }

    return view('admin.licitacion_pdfs.checklist', [
      'pdf'       => $licitacionPdf,
      'checklist' => $checklist,
      'items'     => $items,
    ]);
  }

  /**
   * Genera checklist con IA (disciplinada: solo lo que está en el PDF)
   * y redirige a la pantalla checklist.
   */
  public function generateChecklist(Request $request, LicitacionPdf $licitacionPdf)
  {
    try {
      $vectorStoreId = $this->ensureVectorStoreForPdf($licitacionPdf);

      $checklist = LicitacionPdfChecklist::updateOrCreate(
        ['licitacion_pdf_id' => $licitacionPdf->id, 'user_id' => auth()->id()],
        ['title' => 'Checklist — ' . ($licitacionPdf->original_filename ?? ('PDF #' . $licitacionPdf->id))]
      );

      // limpiamos items anteriores para regenerar limpio
      LicitacionPdfChecklistItem::where('checklist_id', $checklist->id)->delete();

      $json = $this->askChecklistStrictJson($licitacionPdf, $vectorStoreId);

      $decoded = json_decode($json, true);
      if (!is_array($decoded)) {
        throw new \RuntimeException('La IA no devolvió JSON válido.');
      }

      $title = trim((string)($decoded['title'] ?? 'Checklist del PDF'));
      $meta  = $decoded['meta'] ?? [];

      $checklist->title = $title;
      $checklist->meta  = array_merge([
        'generated_at' => now()->toISOString(),
        'strict_mode'  => true,
      ], is_array($meta) ? $meta : []);
      $checklist->save();

      $sections = $decoded['sections'] ?? [];
      if (!is_array($sections) || empty($sections)) {
        throw new \RuntimeException('El JSON no contiene secciones.');
      }

      $sortGlobal = 0;

      foreach ($sections as $sec) {
        $secName = trim((string)($sec['name'] ?? 'Sección'));
        $secItems = $sec['items'] ?? [];
        if (!is_array($secItems)) continue;

        foreach ($secItems as $item) {
          $sortGlobal++;
          $this->persistChecklistItemRecursive($checklist->id, $secName, $item, null, $sortGlobal);
        }
      }

      return response()->json([
        'ok' => true,
        'redirect' => route('admin.licitacion-pdfs.ai.checklist', $licitacionPdf),
      ]);
    } catch (\Throwable $e) {
      Log::error('Checklist generation failed', [
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
   * Toggle / notas item
   */
  public function updateChecklistItem(Request $request, LicitacionPdfChecklistItem $item)
  {
    $data = $request->validate([
      'done'  => ['nullable', 'boolean'],
      'notes' => ['nullable', 'string', 'max:5000'],
    ]);

    // seguridad: item debe ser del usuario actual
    $checklist = LicitacionPdfChecklist::where('id', $item->checklist_id)
      ->where('user_id', auth()->id())
      ->firstOrFail();

    if (array_key_exists('done', $data)) {
      $item->done = (bool)$data['done'];
    }
    if (array_key_exists('notes', $data)) {
      $item->notes = trim((string)$data['notes']);
    }

    $item->save();

    // progreso simple
    $total = LicitacionPdfChecklistItem::where('checklist_id', $checklist->id)->count();
    $done  = LicitacionPdfChecklistItem::where('checklist_id', $checklist->id)->where('done', true)->count();

    return response()->json([
      'ok' => true,
      'item' => [
        'id' => $item->id,
        'done' => (bool)$item->done,
        'notes' => $item->notes,
      ],
      'progress' => [
        'done' => $done,
        'total' => $total,
        'pct' => $total ? round(($done / $total) * 100) : 0,
      ],
    ]);
  }

  private function persistChecklistItemRecursive(int $checklistId, string $section, array $node, ?int $parentId, int &$sortGlobal): void
  {
    $code = isset($node['code']) ? trim((string)$node['code']) : null;
    $text = trim((string)($node['text'] ?? ''));

    if ($text === '') {
      return;
    }

    $required = array_key_exists('required', $node) ? (bool)$node['required'] : true;

    $evidence = null;
    if (isset($node['evidence']) && is_array($node['evidence'])) {
      $page = $node['evidence']['page'] ?? null;
      $excerpt = $node['evidence']['excerpt'] ?? null;
      $evidence = [
        'page' => is_numeric($page) ? max(1, (int)$page) : null,
        'excerpt' => $excerpt ? mb_substr(trim((string)$excerpt), 0, 600) : null,
      ];
    }

    $row = LicitacionPdfChecklistItem::create([
      'checklist_id' => $checklistId,
      'section'      => $section,
      'code'         => $code ?: null,
      'text'         => $text,
      'required'     => $required,
      'parent_id'    => $parentId,
      'sort'         => $sortGlobal,
      'done'         => false,
      'notes'        => null,
      'evidence'     => $evidence,
    ]);

    $children = $node['children'] ?? [];
    if (is_array($children) && !empty($children)) {
      foreach ($children as $child) {
        if (!is_array($child)) continue;
        $sortGlobal++;
        $this->persistChecklistItemRecursive($checklistId, $section, $child, $row->id, $sortGlobal);
      }
    }
  }

  /**
   * IA: genera JSON estricto con checklist
   */
  private function askChecklistStrictJson(LicitacionPdf $pdf, string $vectorStoreId): string
  {
    $apiKey = config('services.openai.api_key') ?: env('OPENAI_API_KEY');
    if (!$apiKey) throw new \RuntimeException('Falta OPENAI_API_KEY en .env');

    $baseUrl   = rtrim(config('services.openai.base_url') ?: env('OPENAI_BASE_URL', 'https://api.openai.com'), '/');
    $timeout   = (int) (config('services.openai.timeout') ?: env('OPENAI_TIMEOUT', 180));
    $projectId = config('services.openai.project_id') ?: env('OPENAI_PROJECT_ID');

    $model = config('services.openai.primary')
      ?: config('services.openai.model')
      ?: env('OPENAI_PRIMARY_MODEL', 'gpt-5-2025-08-07');

    $headers = [];
    if ($projectId) $headers['OpenAI-Project'] = $projectId;

    $schema = [
      'title' => 'string',
      'meta' => [
        'model' => 'string',
        'discipline' => 'string',
      ],
      'sections' => [
        [
          'name' => 'string',
          'items' => [
            [
              'code' => 'string|null',
              'text' => 'string',
              'required' => 'boolean',
              'children' => 'array|null',
              'evidence' => [
                'page' => 'number|null',
                'excerpt' => 'string|null'
              ]
            ]
          ]
        ]
      ]
    ];

    $system = ""
      . "Eres un analista de licitaciones extremadamente disciplinado.\n"
      . "Tu única fuente es el PDF (herramienta file_search). NO infieras, NO inventes, NO completes con conocimiento externo.\n"
      . "Tu tarea: generar un checklist completo y profesional de TODOS los requisitos documentales y de forma de entrega.\n"
      . "Reglas:\n"
      . "1) Extrae TODO lo que el documento exige presentar/entregar.\n"
      . "2) Conserva códigos si existen (ej. TEC-01), si no existen, crea códigos consistentes (ENT-01, ECO-01, ADM-01) por sección.\n"
      . "3) Separa por secciones claras.\n"
      . "4) Para cada item incluye evidencia (page si aparece en metadata; si no, null) y un excerpt corto del PDF.\n"
      . "5) Si algo no está explícito, NO lo incluyas.\n"
      . "6) Devuelve SOLO JSON válido. Sin texto extra. Sin markdown.\n"
      . "Esquema objetivo (orientativo): " . json_encode($schema, JSON_UNESCAPED_UNICODE) . "\n";

    $resp = Http::withToken($apiKey)
      ->withHeaders($headers)
      ->timeout($timeout)
      ->post($baseUrl . '/v1/responses', [
        'model' => $model,
        'input' => [[
          'role' => 'system',
          'content' => $system
        ],[
          'role' => 'user',
          'content' => "Genera el checklist del PDF: " . ($pdf->original_filename ?? ('PDF #' . $pdf->id))
        ]],
        'tools' => [[
          'type'             => 'file_search',
          'vector_store_ids' => [$vectorStoreId],
          'max_num_results'  => 20,
        ]],
        'include' => ['file_search_call.results'],
      ]);

    if (!$resp->ok()) {
      throw new \RuntimeException('Error llamando IA: ' . $resp->status() . ' ' . $resp->body());
    }

    $json = $resp->json();
    $out = trim((string)($json['output_text'] ?? ''));

    if ($out === '') {
      // fallback por si viene en output->message->content
      $parts = [];
      foreach (($json['output'] ?? []) as $o) {
        if (!is_array($o)) continue;
        if (($o['type'] ?? null) === 'message' && !empty($o['content']) && is_array($o['content'])) {
          foreach ($o['content'] as $c) {
            if (!is_array($c)) continue;
            if (($c['type'] ?? null) === 'output_text' && isset($c['text'])) $parts[] = $c['text'];
            if (($c['type'] ?? null) === 'text' && isset($c['text'])) $parts[] = is_array($c['text']) ? ($c['text']['value'] ?? '') : $c['text'];
          }
        }
      }
      $out = trim(implode("\n", array_filter($parts)));
    }

    // hard-trim para evitar basura antes/después del JSON
    $out = $this->extractFirstJsonObject($out);
    if ($out === '') {
      throw new \RuntimeException('La IA no devolvió JSON.');
    }

    return $out;
  }

  private function extractFirstJsonObject(string $s): string
  {
    $s = trim($s);
    if ($s === '') return '';

    $start = strpos($s, '{');
    $end   = strrpos($s, '}');

    if ($start === false || $end === false || $end <= $start) return '';
    return trim(substr($s, $start, $end - $start + 1));
  }

  /**
   * Asegura vector store (incluye OCR una vez)
   */
  private function ensureVectorStoreForPdf(LicitacionPdf $pdf): string
  {
    $apiKey = config('services.openai.api_key') ?: env('OPENAI_API_KEY');
    if (!$apiKey) throw new \RuntimeException('Falta OPENAI_API_KEY en .env');

    $meta = $pdf->meta ?? [];

    if (!empty($meta['openai_vector_store_id'])) {
      return $meta['openai_vector_store_id'];
    }

    if (!$pdf->original_path || !Storage::exists($pdf->original_path)) {
      throw new \RuntimeException('No existe el PDF físico.');
    }

    $baseUrl   = rtrim(config('services.openai.base_url') ?: env('OPENAI_BASE_URL', 'https://api.openai.com'), '/');
    $timeout   = (int) (config('services.openai.timeout') ?: env('OPENAI_TIMEOUT', 120));
    $projectId = config('services.openai.project_id') ?: env('OPENAI_PROJECT_ID');

    $headers = [];
    if ($projectId) $headers['OpenAI-Project'] = $projectId;

    $fullPath = Storage::path($pdf->original_path);

    $needsOcr = empty($meta['ocr_searchable_pdf']) || !$meta['ocr_searchable_pdf'];
    if ($needsOcr) {
      try {
        $ilove = app(IlovePdfService::class);
        $binary = $ilove->ocrPdfBinary($fullPath, ['spa', 'eng']);
        Storage::put($pdf->original_path, $binary);
        clearstatcache(true, $fullPath);

        $meta['ocr_searchable_pdf'] = true;
        $pdf->meta = $meta;
        $pdf->save();
      } catch (\Throwable $e) {
        Log::warning('iLovePDF OCR falló, se continúa sin OCR', [
          'pdf_id' => $pdf->id,
          'error'  => $e->getMessage(),
        ]);
      }
    }

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
        'name'     => 'licitacion_pdf_' . $pdf->id,
        'file_ids' => [$openaiFileId],
      ]);

    if (!$vsResp->ok()) {
      throw new \RuntimeException('Error creando vector store: ' . $vsResp->status() . ' ' . $vsResp->body());
    }

    $vectorStoreId = $vsResp->json('id');
    if (!$vectorStoreId) throw new \RuntimeException('OpenAI no devolvió vector_store_id.');

    $meta['openai_file_id']         = $openaiFileId;
    $meta['openai_vector_store_id'] = $vectorStoreId;

    $pdf->meta = $meta;
    $pdf->save();

    return $vectorStoreId;
  }

  /**
   * SOLO 1 fuente para chat normal
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
      'role'    => 'system',
      'content' =>
        "Responde CLARO y directo SOLO con base en el PDF.\n" .
        "Si no viene en el PDF, dilo.\n" .
        "NO pongas links dentro del texto de la respuesta.\n",
    ]];

    foreach ($history as $m) {
      $input[] = [
        'role'    => $m->role === 'assistant' ? 'assistant' : 'user',
        'content' => $m->content,
      ];
    }

    $resp = Http::withToken($apiKey)
      ->withHeaders($headers)
      ->timeout($timeout)
      ->post($baseUrl . '/v1/responses', [
        'model'   => $model,
        'input'   => $input,
        'tools'   => [[
          'type'              => 'file_search',
          'vector_store_ids'  => [$vectorStoreId],
          'max_num_results'   => 6,
        ]],
        'include' => ['file_search_call.results'],
      ]);

    if (!$resp->ok()) {
      return ['Error llamando IA: ' . $resp->status() . ' ' . $resp->body(), null];
    }

    $json = $resp->json();

    $answer = trim((string) ($json['output_text'] ?? ''));
    if ($answer === '') {
      $parts = [];
      foreach (($json['output'] ?? []) as $out) {
        if (!is_array($out)) continue;
        if (($out['type'] ?? null) === 'message' && !empty($out['content']) && is_array($out['content'])) {
          foreach ($out['content'] as $c) {
            if (!is_array($c)) continue;
            if (($c['type'] ?? null) === 'output_text' && isset($c['text'])) $parts[] = $c['text'];
            if (($c['type'] ?? null) === 'text' && isset($c['text'])) $parts[] = is_array($c['text']) ? ($c['text']['value'] ?? '') : $c['text'];
          }
        }
      }
      $answer = trim(implode("\n", array_filter($parts)));
    }
    if ($answer === '') $answer = 'No pude generar respuesta.';

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

    usort($results, function ($a, $b) {
      $sa = is_array($a) ? ($a['score'] ?? 0) : 0;
      $sb = is_array($b) ? ($b['score'] ?? 0) : 0;
      return $sb <=> $sa;
    });

    $r = $results[0];

    $filename = $r['filename'] ?? ($r['file_name'] ?? 'PDF');
    $score    = $r['score'] ?? null;

    $excerpt = '';
    if (isset($r['content']) && is_array($r['content'])) {
      $first   = $r['content'][0] ?? null;
      $excerpt = is_array($first) ? (string) ($first['text'] ?? '') : (string) $first;
    } elseif (isset($r['text'])) {
      $excerpt = (string) $r['text'];
    } elseif (isset($r['snippet'])) {
      $excerpt = (string) $r['snippet'];
    }
    $excerpt = trim(preg_replace("/\s+/", " ", $excerpt));

    $page = 1;
    if (isset($r['metadata']) && is_array($r['metadata'])) {
      $p = $r['metadata']['page'] ?? $r['metadata']['page_number'] ?? $r['metadata']['page_index'] ?? null;
      if (is_numeric($p)) $page = max(1, (int) $p);
    }

    $highlight = $this->makeHighlightQuery($excerpt);

    $url = route('admin.licitacion-pdfs.ai.viewer', [
      'licitacionPdf' => $pdf->id,
      'page'          => $page,
      'q'             => $highlight,
    ]);

    $source = [
      'label'     => $filename . " (pág. {$page})",
      'url'       => $url,
      'score'     => $score,
      'excerpt'   => mb_substr($excerpt, 0, 450) . (mb_strlen($excerpt) > 450 ? '…' : ''),
      'page'      => $page,
      'highlight' => $highlight,
      'filename'  => $filename,
    ];

    return [$answer, $source];
  }

  private function makeHighlightQuery(string $excerpt): string
  {
    $t = trim($excerpt);
    if ($t === '') return '';

    $t = mb_substr($t, 0, 160);

    $pos = mb_strpos($t, '.');
    if ($pos !== false && $pos > 40) {
      $t = mb_substr($t, 0, $pos);
    }

    return trim($t);
  }
}
