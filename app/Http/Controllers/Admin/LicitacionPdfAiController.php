<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LicitacionPdf;
use App\Models\LicitacionPdfChatMessage;
use App\Models\LicitacionPdfChecklist;
use App\Models\LicitacionPdfChecklistItem;
use App\Services\IlovePdfService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class LicitacionPdfAiController extends Controller
{
  // =========================================================
  // Config / Helpers HTTP + Cache
  // =========================================================
  private function openAiConfig(): array
  {
    $apiKey = config('services.openai.api_key') ?: env('OPENAI_API_KEY');
    if (!$apiKey) throw new \RuntimeException('Falta OPENAI_API_KEY en .env');

    $baseUrl = rtrim(config('services.openai.base_url') ?: env('OPENAI_BASE_URL', 'https://api.openai.com'), '/');

    $timeout = (int) (config('services.openai.timeout') ?: env('OPENAI_TIMEOUT', 180));
    $timeout = max(30, $timeout);

    $connectTimeout = (int) (config('services.openai.connect_timeout') ?: env('OPENAI_CONNECT_TIMEOUT', 30));
    $connectTimeout = max(5, $connectTimeout);

    $projectId = config('services.openai.project_id') ?: env('OPENAI_PROJECT_ID');

    // modelo principal (para checklist)
    $primaryModel = config('services.openai.primary')
      ?: config('services.openai.model')
      ?: env('OPENAI_PRIMARY_MODEL', 'gpt-5-2025-08-07');

    // modelo rápido (para chat normal)
    $fastModel = config('services.openai.fast')
      ?: env('OPENAI_FAST_MODEL', 'gpt-5-mini');

    $headers = [];
    if ($projectId) $headers['OpenAI-Project'] = $projectId;

    return [
      'apiKey'         => $apiKey,
      'baseUrl'        => $baseUrl,
      'timeout'        => $timeout,
      'connectTimeout' => $connectTimeout,
      'headers'        => $headers,
      'primaryModel'   => $primaryModel,
      'fastModel'      => $fastModel,
    ];
  }

  /**
   * Ejecuta request a OpenAI con retry/backoff ante 429/5xx y ConnectionException.
   */
  private function openAiRequest(string $method, string $url, array $payload = [], array $headers = [], array $files = []): \Illuminate\Http\Client\Response
  {
    $cfg = $this->openAiConfig();

    $maxAttempts = (int) (config('services.openai.max_retries', 4) ?: env('OPENAI_MAX_RETRIES', 4));
    $maxAttempts = max(1, $maxAttempts);

    $baseDelayMs = (int) (config('services.openai.retry_base_delay_ms', 800) ?: env('OPENAI_RETRY_BASE_DELAY_MS', 800));
    $baseDelayMs = max(200, $baseDelayMs);

    $attempt  = 0;
    $lastResp = null;
    $lastEx   = null;

    while ($attempt < $maxAttempts) {
      $attempt++;

      try {
        $req = Http::withToken($cfg['apiKey'])
          ->withHeaders(array_merge($cfg['headers'], $headers))
          ->connectTimeout($cfg['connectTimeout'])
          ->timeout($cfg['timeout']);

        if (!empty($files)) {
          foreach ($files as $f) {
            $req = $req->attach($f['name'], $f['contents'], $f['filename'] ?? null);
          }
        }

        $resp = $req->{$method}($url, $payload);
        $lastResp = $resp;

        if ($resp->ok()) return $resp;

        $status = (int) $resp->status();

        // retry solo en temporales
        if (in_array($status, [429, 500, 502, 503, 504], true)) {
          $sleepMs = (int) ($baseDelayMs * $attempt);
          usleep($sleepMs * 1000);
          continue;
        }

        return $resp;
      } catch (ConnectionException $e) {
        $lastEx = $e;
        $sleepMs = (int) ($baseDelayMs * $attempt);
        usleep($sleepMs * 1000);
        continue;
      }
    }

    if ($lastEx) {
      throw new \RuntimeException('Error de red/timeout al llamar OpenAI: ' . $lastEx->getMessage());
    }

    return $lastResp ?? throw new \RuntimeException('Error desconocido llamando OpenAI.');
  }

  private function isServiceUnavailableMessage(string $msg): bool
  {
    $m = mb_strtolower($msg);
    return str_contains($m, '503') || str_contains($m, 'service unavailable') || str_contains($m, '<html');
  }

  private function normalizeQuestion(string $s): string
  {
    $s = mb_strtolower(trim($s));
    $s = preg_replace('/\s+/', ' ', $s);
    return (string) $s;
  }

  private function chatCacheKey(int $pdfId, int $userId, string $question): string
  {
    return 'pdfchat:' . $pdfId . ':' . $userId . ':' . md5($question);
  }

  // =========================================================
  // UI
  // =========================================================
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
      $userId = (int) auth()->id();

      // ✅ cache para preguntas repetidas (responde instantáneo)
      $normQ = $this->normalizeQuestion($text);
      $cacheKey = $this->chatCacheKey((int)$licitacionPdf->id, $userId, $normQ);

      if ($cached = Cache::get($cacheKey)) {
        // guarda también el mensaje del usuario (para historial)
        LicitacionPdfChatMessage::create([
          'licitacion_pdf_id' => $licitacionPdf->id,
          'user_id'           => $userId,
          'role'              => 'user',
          'content'           => $text,
        ]);

        LicitacionPdfChatMessage::create([
          'licitacion_pdf_id' => $licitacionPdf->id,
          'user_id'           => $userId,
          'role'              => 'assistant',
          'content'           => (string)($cached['answer'] ?? ''),
          'sources'           => !empty($cached['source']) ? [$cached['source']] : null,
        ]);

        return response()->json([
          'ok'     => true,
          'answer' => (string)($cached['answer'] ?? ''),
          'source' => $cached['source'] ?? null,
          'cached' => true,
        ]);
      }

      // guardar mensaje usuario
      LicitacionPdfChatMessage::create([
        'licitacion_pdf_id' => $licitacionPdf->id,
        'user_id'           => $userId,
        'role'              => 'user',
        'content'           => $text,
      ]);

      // ✅ historial más corto = más rápido
      $history = LicitacionPdfChatMessage::where('licitacion_pdf_id', $licitacionPdf->id)
        ->where('user_id', $userId)
        ->orderByDesc('id')
        ->limit(10) // antes 16
        ->get()
        ->reverse()
        ->values();

      // vector store (si no existe, se crea con OCR opcional)
      $vectorStoreId = $this->ensureVectorStoreForPdf($licitacionPdf);

      // ✅ chat usa modelo FAST y menos resultados
      [$answer, $source] = $this->askWithFileSearchTop1($licitacionPdf, $vectorStoreId, $history, true);

      LicitacionPdfChatMessage::create([
        'licitacion_pdf_id' => $licitacionPdf->id,
        'user_id'           => $userId,
        'role'              => 'assistant',
        'content'           => $answer,
        'sources'           => $source ? [$source] : null,
      ]);

      // cache 12h (ajusta a gusto)
      Cache::put($cacheKey, ['answer' => $answer, 'source' => $source], now()->addHours(12));

      return response()->json([
        'ok'     => true,
        'answer' => $answer,
        'source' => $source,
        'cached' => false,
      ]);
    } catch (\Throwable $e) {
      Log::error('PDF AI chat error', [
        'pdf_id'  => $licitacionPdf->id,
        'user_id' => auth()->id(),
        'error'   => $e->getMessage(),
      ]);

      $status = $this->isServiceUnavailableMessage($e->getMessage()) ? 503 : 500;

      return response()->json([
        'ok'      => false,
        'message' => $status === 503 ? 'Servicio temporalmente no disponible. Intenta de nuevo.' : $e->getMessage(),
      ], $status);
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

  public function generateChecklist(Request $request, LicitacionPdf $licitacionPdf)
  {
    try {
      $vectorStoreId = $this->ensureVectorStoreForPdf($licitacionPdf);

      $checklist = LicitacionPdfChecklist::updateOrCreate(
        ['licitacion_pdf_id' => $licitacionPdf->id, 'user_id' => auth()->id()],
        ['title' => 'Checklist — ' . ($licitacionPdf->original_filename ?? ('PDF #' . $licitacionPdf->id))]
      );

      LicitacionPdfChecklistItem::where('checklist_id', $checklist->id)->delete();

      // ✅ checklist usa modelo fuerte
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

      $status = $this->isServiceUnavailableMessage($e->getMessage()) ? 503 : 500;

      return response()->json([
        'ok' => false,
        'message' => $status === 503 ? 'Servicio temporalmente no disponible. Intenta de nuevo.' : $e->getMessage(),
      ], $status);
    }
  }

  public function updateChecklistItem(Request $request, LicitacionPdfChecklistItem $item)
  {
    $data = $request->validate([
      'done'  => ['nullable', 'boolean'],
      'notes' => ['nullable', 'string', 'max:5000'],
    ]);

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

  // =========================================================
  // IA Calls
  // =========================================================
  private function askChecklistStrictJson(LicitacionPdf $pdf, string $vectorStoreId): string
  {
    $cfg = $this->openAiConfig();

    $schema = [
      'title' => 'string',
      'meta' => ['model' => 'string', 'discipline' => 'string'],
      'sections' => [[
        'name' => 'string',
        'items' => [[
          'code' => 'string|null',
          'text' => 'string',
          'required' => 'boolean',
          'children' => 'array|null',
          'evidence' => ['page' => 'number|null', 'excerpt' => 'string|null'],
        ]],
      ]],
    ];

    $system = ""
      . "Eres un analista de licitaciones extremadamente disciplinado.\n"
      . "Tu única fuente es el PDF (herramienta file_search). NO infieras, NO inventes.\n"
      . "Devuelve SOLO JSON válido. Sin markdown.\n"
      . "Esquema objetivo (orientativo): " . json_encode($schema, JSON_UNESCAPED_UNICODE) . "\n";

    $url = $cfg['baseUrl'] . '/v1/responses';

    $resp = $this->openAiRequest('post', $url, [
      'model' => $cfg['primaryModel'],
      'input' => [[
        'role' => 'system',
        'content' => $system
      ], [
        'role' => 'user',
        'content' => "Genera el checklist del PDF: " . ($pdf->original_filename ?? ('PDF #' . $pdf->id))
      ]],
      'tools' => [[
        'type'             => 'file_search',
        'vector_store_ids' => [$vectorStoreId],
        'max_num_results'  => 12, // ✅ menos = más rápido
      ]],
      'include' => ['file_search_call.results'],
    ]);

    if (!$resp->ok()) {
      throw new \RuntimeException('Error llamando IA: ' . $resp->status() . ' ' . $resp->body());
    }

    $json = $resp->json();
    $out = trim((string)($json['output_text'] ?? ''));

    if ($out === '') {
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

    $out = $this->extractFirstJsonObject($out);
    if ($out === '') throw new \RuntimeException('La IA no devolvió JSON.');

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
    $cfg = $this->openAiConfig();

    $meta = $pdf->meta ?? [];

    if (!empty($meta['openai_vector_store_id'])) {
      return $meta['openai_vector_store_id'];
    }

    if (!$pdf->original_path || !Storage::exists($pdf->original_path)) {
      throw new \RuntimeException('No existe el PDF físico.');
    }

    $fullPath = Storage::path($pdf->original_path);

    // OCR opcional (si falla, seguimos)
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

    // 1) subir file a OpenAI (con retries)
    $fileUrl = $cfg['baseUrl'] . '/v1/files';

    $fileResp = $this->openAiRequest(
      'post',
      $fileUrl,
      ['purpose' => 'assistants'],
      [],
      [[
        'name'     => 'file',
        'contents' => file_get_contents($fullPath),
        'filename' => $pdf->original_filename ?? ("pdf_{$pdf->id}.pdf"),
      ]]
    );

    if (!$fileResp->ok()) {
      throw new \RuntimeException('Error subiendo PDF a OpenAI: ' . $fileResp->status() . ' ' . $fileResp->body());
    }

    $openaiFileId = $fileResp->json('id');
    if (!$openaiFileId) throw new \RuntimeException('OpenAI no devolvió file_id.');

    // 2) crear vector store
    $vsUrl = $cfg['baseUrl'] . '/v1/vector_stores';

    $vsResp = $this->openAiRequest('post', $vsUrl, [
      'name'     => 'licitacion_pdf_' . $pdf->id,
      'file_ids' => [$openaiFileId],
    ], [
      'OpenAI-Beta' => 'assistants=v2',
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
   * ✅ Chat: FAST model y menos resultados
   * $fast=true usa modelo rápido + menos resultados
   */
  private function askWithFileSearchTop1(LicitacionPdf $pdf, string $vectorStoreId, $history, bool $fast = false): array
  {
    $cfg = $this->openAiConfig();

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

    $url = $cfg['baseUrl'] . '/v1/responses';

    $resp = $this->openAiRequest('post', $url, [
      'model'   => $fast ? $cfg['fastModel'] : $cfg['primaryModel'],
      'input'   => $input,
      'tools'   => [[
        'type'              => 'file_search',
        'vector_store_ids'  => [$vectorStoreId],
        'max_num_results'   => $fast ? 3 : 6, // ✅ menos = más rápido
      ]],
      'include' => ['file_search_call.results'],
    ]);

    if (!$resp->ok()) {
      $body = (string) $resp->body();
      return ['Error llamando IA: ' . $resp->status() . ' ' . $body, null];
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
