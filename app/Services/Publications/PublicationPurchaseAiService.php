<?php

namespace App\Services\Publications;

use App\Models\Publication;
use App\Models\PurchaseDocument;
use App\Models\PurchaseItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicationPurchaseAiService
{
    /* =========================
     | VALIDACIONES
     ========================= */
    public function storeRules(): array
    {
        return [
            'title'       => ['required','string','max:200'],
            'description' => ['nullable','string','max:5000'],

            // ✅ multi
            'files'       => ['nullable','array','min:1','max:25'],
            'files.*'     => ['file','max:51200','mimes:jpg,jpeg,png,webp,pdf'],

            // ✅ compat single viejo
            'file'        => ['nullable','file','max:51200'],

            'pinned'      => ['nullable'],
            'ai_extract'  => ['nullable','boolean'],
            'ai_skip'     => ['nullable'],

            // ✅ payload single (1 archivo)
            'ai_payload'       => ['nullable','string'],
            // ✅ payload multi (muchos archivos)
            'ai_payload_bulk'  => ['nullable','string'],
            'file_fps'         => ['nullable','array'],
            'file_fps.*'       => ['nullable','string','max:500'],

            'category'    => ['required','string','in:compra,venta'],
        ];
    }

    public function extractRules(): array
    {
        return [
            'file'     => ['required','file','max:20480','mimes:jpg,jpeg,png,webp,pdf'],
            'category' => ['required','string','in:compra,venta'],
        ];
    }

    public function saveRules(): array
    {
        return [
            'publication_id' => ['nullable','integer'],
            'payload'        => ['required','array'],
            'category'       => ['required','string','in:compra,venta'],
        ];
    }

    /* =========================
     | INDEX (analytics)
     ========================= */
    public function buildIndexData(): array
    {
        $pinned = Publication::query()->where('pinned', true)->latest('created_at')->get();
        $latest = Publication::query()->where('pinned', false)->latest('created_at')->paginate(12);

        $totalSpentCompra = (float) PurchaseDocument::where('category', 'compra')->sum('total');
        $totalSpentVenta  = (float) PurchaseDocument::where('category', 'venta')->sum('total');
        $totalSpent       = (float) PurchaseDocument::sum('total');

        $endDay   = now()->endOfDay();
        $startDay = now()->subDays(29)->startOfDay();

        $months = [];
        $mCursor = now()->startOfMonth()->subMonths(11);
        for ($i=0; $i<12; $i++) {
            $months[] = $mCursor->format('Y-m');
            $mCursor->addMonth();
        }

        $chartLabels = [];
        foreach ($months as $m) {
            try {
                $dt = Carbon::createFromFormat('Y-m', $m);
                $chartLabels[] = $dt->translatedFormat('M Y');
            } catch (\Exception $e) {
                $chartLabels[] = $m;
            }
        }

        $monthlyRaw = PurchaseDocument::selectRaw("
                category,
                DATE_FORMAT(COALESCE(document_datetime, created_at), '%Y-%m') as month_id,
                SUM(total) as total
            ")
            ->whereIn('category', ['compra', 'venta'])
            ->whereRaw("COALESCE(document_datetime, created_at) >= ?", [now()->startOfMonth()->subMonths(11)->startOfDay()])
            ->groupBy('category', 'month_id')
            ->orderBy('month_id', 'asc')
            ->get();

        $monthlyCompra = array_fill(0, count($months), 0.0);
        $monthlyVenta  = array_fill(0, count($months), 0.0);
        $monthIndex    = array_flip($months);

        foreach ($monthlyRaw as $row) {
            $idx = $monthIndex[$row->month_id] ?? null;
            if ($idx === null) continue;
            if ($row->category === 'compra') $monthlyCompra[$idx] = (float) $row->total;
            if ($row->category === 'venta')  $monthlyVenta[$idx]  = (float) $row->total;
        }

        $chartValues = $monthlyCompra;

        $days = [];
        $dCursor = $startDay->copy();
        for ($i=0; $i<30; $i++) {
            $days[] = $dCursor->toDateString();
            $dCursor->addDay();
        }

        $dailyLabels = collect($days)->map(fn ($d) => Carbon::parse($d)->format('d/m'))->values()->all();

        $dailyRaw = PurchaseDocument::selectRaw("
                category,
                DATE(COALESCE(document_datetime, created_at)) as day,
                SUM(total) as total
            ")
            ->whereIn('category', ['compra', 'venta'])
            ->whereRaw("COALESCE(document_datetime, created_at) >= ?", [$startDay])
            ->whereRaw("COALESCE(document_datetime, created_at) <= ?", [$endDay])
            ->groupBy('category', 'day')
            ->orderBy('day', 'asc')
            ->get();

        $dailyCompra = array_fill(0, count($days), 0.0);
        $dailyVenta  = array_fill(0, count($days), 0.0);
        $dayIndex    = array_flip($days);

        foreach ($dailyRaw as $row) {
            $key = is_string($row->day) ? $row->day : (string)$row->day;
            $idx = $dayIndex[$key] ?? null;
            if ($idx === null) continue;
            if ($row->category === 'compra') $dailyCompra[$idx] = (float) $row->total;
            if ($row->category === 'venta')  $dailyVenta[$idx]  = (float) $row->total;
        }

        $dailyValues = collect($dailyCompra);

        $topProductsCompra = PurchaseItem::selectRaw("purchase_documents.category, purchase_items.item_name, SUM(purchase_items.line_total) as total_spent")
            ->join('purchase_documents', 'purchase_items.purchase_document_id', '=', 'purchase_documents.id')
            ->where('purchase_documents.category', 'compra')
            ->whereNotNull('purchase_items.item_name')
            ->where('purchase_items.item_name', '!=', '')
            ->groupBy('purchase_documents.category', 'purchase_items.item_name')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();

        $topProductsVenta = PurchaseItem::selectRaw("purchase_documents.category, purchase_items.item_name, SUM(purchase_items.line_total) as total_spent")
            ->join('purchase_documents', 'purchase_items.purchase_document_id', '=', 'purchase_documents.id')
            ->where('purchase_documents.category', 'venta')
            ->whereNotNull('purchase_items.item_name')
            ->where('purchase_items.item_name', '!=', '')
            ->groupBy('purchase_documents.category', 'purchase_items.item_name')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();

        $prodChartDataCompra = $topProductsCompra->map(fn ($item) => ['x' => Str::limit($item->item_name, 25), 'y' => (float) $item->total_spent])->values()->all();
        $prodChartDataVenta  = $topProductsVenta->map(fn ($item) => ['x' => Str::limit($item->item_name, 25), 'y' => (float) $item->total_spent])->values()->all();

        $prodChartData = $prodChartDataCompra;

        $allPurchases = PurchaseItem::select(
                'purchase_items.*',
                'purchase_documents.document_datetime',
                'purchase_documents.created_at as doc_created_at',
                'purchase_documents.supplier_name',
                'purchase_documents.category'
            )
            ->join('purchase_documents', 'purchase_items.purchase_document_id', '=', 'purchase_documents.id')
            ->whereIn('purchase_documents.category', ['compra', 'venta'])
            ->orderByRaw("COALESCE(purchase_documents.document_datetime, purchase_documents.created_at) DESC")
            ->limit(200)
            ->get();

        $topSuppliersCompra = PurchaseDocument::selectRaw("supplier_name, COUNT(*) as count, SUM(total) as total_amount")
            ->where('category', 'compra')
            ->whereNotNull('supplier_name')
            ->where('supplier_name', '!=', '')
            ->groupBy('supplier_name')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get();

        $topSuppliersVenta = PurchaseDocument::selectRaw("supplier_name, COUNT(*) as count, SUM(total) as total_amount")
            ->where('category', 'venta')
            ->whereNotNull('supplier_name')
            ->where('supplier_name', '!=', '')
            ->groupBy('supplier_name')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get();

        $topSuppliers = $topSuppliersCompra;

        return compact(
            'pinned',
            'latest',
            'totalSpent',
            'totalSpentCompra',
            'totalSpentVenta',
            'chartLabels',
            'chartValues',
            'monthlyCompra',
            'monthlyVenta',
            'dailyLabels',
            'dailyValues',
            'dailyCompra',
            'dailyVenta',
            'prodChartData',
            'prodChartDataCompra',
            'prodChartDataVenta',
            'allPurchases',
            'topSuppliers',
            'topSuppliersCompra',
            'topSuppliersVenta'
        );
    }

    /* =========================
     | STORE (single o multi)
     | - Si viene ai_payload_bulk: guarda lo que ya editaste en UI (NO batch, NO re-extract)
     ========================= */
    public function storeFromRequest(Request $request): array
    {
        $files = $request->file('files', []);
        if (empty($files) && $request->hasFile('file')) {
            $files = [$request->file('file')];
        }
        if (empty($files)) {
            return ['message' => 'Sube al menos 1 archivo.'];
        }

        $category = (string)$request->category;
        $isMulti = count($files) > 1;

        $aiSkip = (string)($request->input('ai_skip', '0')) === '1';
        $aiPayloadRaw = trim((string)($request->input('ai_payload', '')));

        // ✅ multi payload (ya editado en UI)
        $aiPayloadBulkRaw = trim((string)($request->input('ai_payload_bulk', '')));
        $bulkMap = [];
        if ($aiPayloadBulkRaw !== '') {
            $decoded = json_decode($aiPayloadBulkRaw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $row) {
                    if (!is_array($row)) continue;
                    $fp = trim((string)($row['fp'] ?? ''));
                    if ($fp === '') continue;
                    $bulkMap[$fp] = $row;
                }
            }
        }

        // ✅ fps enviados desde front (ordenados)
        $fileFps = $request->input('file_fps', []);
        if (!is_array($fileFps)) $fileFps = [];

        // ai_payload => 1 archivo (tu flujo AJAX single)
        if (!$aiSkip && $aiPayloadRaw !== '' && count($files) !== 1) {
            return ['message' => 'Si usas ai_payload, sube solo 1 archivo.'];
        }

        $created = 0;
        $processed = 0;
        $skippedDocs = 0;

        foreach ($files as $i => $file) {
            /** @var UploadedFile $file */
            $pub = $this->storeOnePublicationFile(
                file: $file,
                title: (string)$request->title,
                description: (string)($request->description ?? ''),
                category: $category,
                pinned: (bool)$request->boolean('pinned'),
                isMulti: $isMulti
            );

            $created++;

            // (A) ai_payload_bulk: usa lo que ya editaste en UI (preferencia máxima)
            if (!$aiSkip && !empty($bulkMap)) {
                $fp = trim((string)($fileFps[$i] ?? ''));
                if ($fp === '') $fp = $this->fingerprintServer($file);

                $payload = $bulkMap[$fp] ?? null;

                if (is_array($payload)) {
                    try {
                        $normalized = $this->normalizeAiPurchase($payload);
                        if (!empty($normalized['items'])) {
                            $this->persistPurchaseDocumentFromAi($normalized, $pub->id, $category);
                            $processed++;
                        } else {
                            $skippedDocs++;
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Publication store: bulk payload persist failed (ignored)', [
                            'publication_id' => $pub->id,
                            'error' => $e->getMessage(),
                        ]);
                        $skippedDocs++;
                    }
                } else {
                    // no hubo payload para ese archivo
                    $skippedDocs++;
                }

                continue; // ya no hacemos IA server-side en bulk
            }

            // (B) ai_payload single (solo 1 archivo)
            if (!$aiSkip && $aiPayloadRaw !== '') {
                try {
                    $payload = json_decode($aiPayloadRaw, true);
                    if (is_array($payload)) {
                        $normalized = $this->normalizeAiPurchase($payload);
                        if (!empty($normalized['items'])) {
                            $this->persistPurchaseDocumentFromAi($normalized, $pub->id, $category);
                            $processed++;
                        } else {
                            $skippedDocs++;
                        }
                    } else {
                        $skippedDocs++;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Publication store: ai_payload persist failed (ignored)', [
                        'publication_id' => $pub->id,
                        'error' => $e->getMessage(),
                    ]);
                    $skippedDocs++;
                }

                return [
                    'redirect_route' => 'publications.index',
                    'redirect_params' => [],
                    'message' => 'Publicación subida correctamente.',
                ];
            }

            // (C) ai_skip => solo subir archivos
            if ($aiSkip) {
                continue;
            }

            // (D) IA server-side “normal” (por archivo) si NO hubo payload
            $kind = (string)($pub->kind ?? '');
            if ($request->boolean('ai_extract') && in_array($kind, ['pdf','image'], true)) {
                try {
                    $absolute = Storage::disk('public')->path($pub->file_path);
                    $ai = $this->aiExtractSingleLocalFile($absolute, $pub->original_name, $category);

                    if ($ai && !empty($ai['items'])) {
                        $this->persistPurchaseDocumentFromAi($ai, $pub->id, $category);
                        $processed++;
                    } else {
                        $skippedDocs++;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Publication store: AI extract failed (ignored)', [
                        'publication_id' => $pub->id,
                        'error' => $e->getMessage(),
                    ]);
                    $skippedDocs++;
                }
            }
        }

        $msg = "Listo. Archivos subidos: {$created}. Documentos guardados: {$processed}.";
        if ($skippedDocs > 0) $msg .= " Sin items/omitidos: {$skippedDocs}.";

        return [
            'redirect_route' => 'publications.index',
            'redirect_params' => [],
            'message' => $msg,
        ];
    }

    private function fingerprintServer(UploadedFile $file): string
    {
        $name = (string) $file->getClientOriginalName();
        $size = (string) ($file->getSize() ?: 0);
        $type = (string) ($file->getClientMimeType() ?: '');
        return "{$name}|{$size}|{$type}";
    }

    private function storeOnePublicationFile(
        UploadedFile $file,
        string $title,
        string $description,
        string $category,
        bool $pinned,
        bool $isMulti
    ): Publication {
        $folder = 'publications/' . now()->format('Y/m');

        $safeBaseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        $name = $safeBaseName . '-' . Str::random(8) . ($ext ? ".{$ext}" : '');

        $path = $file->storeAs($folder, $name, 'public');

        $mime = $file->getClientMimeType();
        $kind = $this->detectKind($mime, $ext);

        // ✅ si sube varios, diferenciamos por nombre de archivo (sin batch)
        $fileTitle = $title;
        if ($isMulti) {
            $fileTitle = Str::limit($title . ' — ' . $file->getClientOriginalName(), 200, '');
        }

        return Publication::create([
            'title'         => $fileTitle,
            'description'   => $description,
            'file_path'     => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $mime,
            'size'          => $file->getSize() ?: 0,
            'extension'     => $ext,
            'kind'          => $kind,
            'pinned'        => $pinned,
            'created_by'    => Auth::id(),
            'category'      => $category,

            // ✅ ya no usamos batch (pero si tu DB tiene la columna, dejamos null)
            'batch_key'     => null,
        ]);
    }

    /* =========================
     | IA: EXTRACT (AJAX)
     ========================= */
    public function extractNormalizedFromUploadedFile(UploadedFile $file, string $category): array
    {
        $ai = $this->aiExtractSingleLocalFile(
            $file->getRealPath(),
            $file->getClientOriginalName(),
            $category
        );

        return $this->normalizeAiPurchase($ai);
    }

    public function saveExtractedPayload(array $payload, ?int $publicationId, string $category): PurchaseDocument
    {
        $normalized = $this->normalizeAiPurchase($payload);
        if (empty($normalized['items'])) {
            throw new \RuntimeException('No hay items para guardar.');
        }

        return $this->persistPurchaseDocumentFromAi($normalized, $publicationId, $category);
    }

    /* =========================
     | IA Core (1 file)
     ========================= */
    public function aiExtractSingleLocalFile(string $absolutePath, string $originalName, string $category = 'compra'): array
    {
        $apiKey = (string) (
            config('services.openai.api_key')
            ?: config('services.openai.key')
            ?: config('openai.api_key')
            ?: config('openai.key')
            ?: ''
        );

        $baseUrl = rtrim((string) (
            config('services.openai.base_url')
            ?: config('openai.base_url')
            ?: 'https://api.openai.com/v1'
        ), '/');
        if (!str_ends_with($baseUrl, '/v1')) $baseUrl .= '/v1';

        $modelId = (string) (
            config('services.openai.model')
            ?: config('openai.primary')
            ?: config('openai.model')
            ?: 'gpt-4.1'
        );

        $timeout = (int) (
            config('services.openai.timeout')
            ?: config('openai.timeout')
            ?: 300
        );

        if ($apiKey === '') {
            throw new \RuntimeException('Missing OpenAI API key.');
        }

        // 1) upload file -> OpenAI Files (purpose user_data)
        $upload = Http::withToken($apiKey)
            ->timeout($timeout)
            ->attach('file', file_get_contents($absolutePath), $originalName)
            ->post($baseUrl . '/files', ['purpose' => 'user_data']);

        if (!$upload->ok()) {
            Log::warning('AI file upload error', ['status' => $upload->status(), 'body' => $upload->body()]);
            throw new \RuntimeException('Error subiendo archivo a OpenAI.');
        }

        $fileId = $upload->json('id');
        if (!$fileId) throw new \RuntimeException('OpenAI no regresó file_id.');

        $system = $this->buildExtractorSystemPromptStrictTableOnly($category);

        $call = function (string $userText) use ($apiKey, $baseUrl, $modelId, $fileId, $system, $timeout): array {
            $resp = Http::withToken($apiKey)
                ->timeout($timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($baseUrl . '/responses', [
                    'model'        => $modelId,
                    'instructions' => $system,
                    'input'        => [[
                        'role'    => 'user',
                        'content' => [
                            ['type' => 'input_text', 'text' => $userText],
                            ['type' => 'input_file', 'file_id' => $fileId],
                        ],
                    ]],
                    'max_output_tokens' => 12000,
                ]);

            if (!$resp->ok()) {
                Log::warning('AI responses error', ['status' => $resp->status(), 'body' => $resp->body()]);
                throw new \RuntimeException('La IA respondió con error.');
            }

            return $this->parseOpenAiJsonFromResponses((array)$resp->json());
        };

        $data1 = $call("Extrae TODOS los renglones REALES de la TABLA de conceptos en TODAS las páginas. NO inventes. Devuelve JSON.");

        $items = is_array($data1['items'] ?? null) ? $data1['items'] : [];
        $doc   = is_array($data1['document'] ?? null) ? $data1['document'] : [];

        $items = $this->filterAndDedupeExtractedItems($items);

        // second pass si casi vacío
        if (count($items) <= 1) {
            $already = $this->buildAlreadyExtractedHints($items, 20);

            $data2 = $call(
                "Parece que faltan renglones. NO repitas items.\n" .
                "Ya tengo:\n{$already}\n\n" .
                "Busca en TODAS las páginas y devuelve SOLO ITEMS NUEVOS REALES de la tabla. Si no hay, regresa items=[]."
            );

            $items2 = is_array($data2['items'] ?? null) ? $data2['items'] : [];
            $items2 = $this->filterAndDedupeExtractedItems($items2);

            $items = array_merge($items, $items2);
            $items = $this->filterAndDedupeExtractedItems($items);
        }

        return [
            'document' => $doc ?: [
                'document_type' => 'otro',
                'supplier_name' => null,
                'counterparty_rfc' => null,
                'uuid' => null,
                'serie' => null,
                'folio' => null,
                'currency' => 'MXN',
                'document_datetime' => null,
                'subtotal' => 0,
                'tax' => 0,
                'total' => 0,
            ],
            'items' => $items,
            'notes' => $data1['notes'] ?? ['warnings' => [], 'confidence' => 0.0],
        ];
    }

    private function buildExtractorSystemPromptStrictTableOnly(string $category): string
    {
        $partyHint = $category === 'venta'
            ? "En VENTA: supplier_name debe ser el CLIENTE / RECEPTOR."
            : "En COMPRA: supplier_name debe ser el PROVEEDOR / EMISOR.";

        return <<<TXT
Eres un extractor experto de documentos contables de {$category} (México). El archivo puede tener VARIAS PÁGINAS.

{$partyHint}

A) CABECERA:
- Detecta: supplier_name, counterparty_rfc, uuid (si es CFDI), serie, folio, document_datetime, currency, subtotal, tax, total.
- Si no se ve, null. NO inventes.

B) TABLA DE CONCEPTOS (estricto):
- Extrae ÚNICAMENTE renglones REALES de la TABLA.
- Ignora sellos, firmas, anotaciones y textos fuera de tabla.
- NO inventes renglones. NO metas IVA como item.

JSON ÚNICAMENTE:

{
  "document": {
    "document_type": "ticket|factura|remision|otro",
    "supplier_name": null,
    "counterparty_rfc": null,
    "uuid": null,
    "serie": null,
    "folio": null,
    "currency": "MXN",
    "document_datetime": "YYYY-MM-DD HH:MM:SS",
    "subtotal": 0,
    "tax": 0,
    "total": 0
  },
  "items": [
    {
      "item_raw": "",
      "item_name": "",
      "qty": 1,
      "unit": "",
      "unit_price": null,
      "line_total": null,
      "ai_meta": { "prodserv": null }
    }
  ],
  "notes": { "warnings": [], "confidence": 0.0 }
}

Reglas:
- JSON únicamente, sin markdown.
- unit_price y line_total: número si se ve; si no se ve, null (NO 0).
- NO devuelvas filas “fantasma”.
TXT;
    }

    private function parseOpenAiJsonFromResponses(array $json): array
    {
        $rawText = '';

        if (isset($json['output']) && is_array($json['output'])) {
            foreach ($json['output'] as $outItem) {
                if (($outItem['type'] ?? null) === 'message' && isset($outItem['content'])) {
                    foreach ($outItem['content'] as $c) {
                        if (($c['type'] ?? null) === 'output_text' && isset($c['text'])) {
                            $rawText .= $c['text'];
                        }
                    }
                }
            }
        } elseif (isset($json['choices'][0]['message']['content'])) {
            $rawText = $json['choices'][0]['message']['content'];
        }

        $rawText = trim((string)$rawText);
        $rawText = preg_replace('/^```json\s*|\s*```$/', '', $rawText);

        if ($rawText === '') throw new \RuntimeException('No se pudo leer salida de IA.');

        $data = json_decode($rawText, true);
        if (!is_array($data)) {
            Log::warning('AI invalid JSON', ['raw' => mb_substr($rawText, 0, 2000)]);
            throw new \RuntimeException('La IA no devolvió JSON válido.');
        }

        return $data;
    }

    /* =========================
     | NORMALIZE + PERSIST
     ========================= */
    public function normalizeAiPurchase(array $payload): array
    {
        $doc = $payload['document'] ?? [];
        $items = $payload['items'] ?? [];

        $document = [
            'document_type'     => $doc['document_type'] ?? 'otro',
            'supplier_name'     => $doc['supplier_name'] ?? null,

            // ✅ extras para agrupar / dedupe
            'counterparty_rfc'  => $doc['counterparty_rfc'] ?? null,
            'uuid'              => $doc['uuid'] ?? null,
            'serie'             => $doc['serie'] ?? null,
            'folio'             => $doc['folio'] ?? null,

            'currency'          => $doc['currency'] ?? 'MXN',
            'document_datetime' => $doc['document_datetime'] ?? null,
            'subtotal'          => $this->toMoney($doc['subtotal'] ?? 0),
            'tax'               => $this->toMoney($doc['tax'] ?? 0),
            'total'             => $this->toMoney($doc['total'] ?? 0),
        ];

        $items = $this->filterAndDedupeExtractedItems(is_array($items) ? $items : []);

        $sumLines = 0.0;
        $normItems = [];

        foreach ($items as $it) {
            $qty = $this->toQty($it['qty'] ?? 1);
            $uP  = $this->toMoney($it['unit_price'] ?? 0, 4);
            $lT  = $this->toMoney($it['line_total'] ?? 0);

            if ($lT <= 0 && $uP > 0 && $qty > 0) $lT = round($qty * $uP, 2);
            if ($uP <= 0 && $lT > 0 && $qty > 0) $uP = round($lT / $qty, 4);

            $sumLines += $lT;

            $normItems[] = [
                'item_raw'   => $it['item_raw'] ?? null,
                'item_name'  => $this->cleanText($it['item_name'] ?? ($it['item_raw'] ?? '')),
                'qty'        => $qty,
                'unit'       => $it['unit'] ?? null,
                'unit_price' => $uP,
                'line_total' => $lT,
                'ai_meta'    => $it['ai_meta'] ?? null,
            ];
        }

        if (($document['total'] ?? 0) <= 0 && $sumLines > 0) {
            $document['total'] = round($sumLines, 2);
        }

        $stats = [
            'items_count' => count($normItems),
            'sum_lines'   => round($sumLines, 2),
            'avg_ticket'  => count($normItems) ? round($sumLines / max(1, count($normItems)), 2) : 0,
            'top_items'   => $this->topItems($normItems, 8),
        ];

        return [
            'document' => $document,
            'items'    => $normItems,
            'stats'    => $stats,
            'notes'    => $payload['notes'] ?? null,
        ];
    }

    public function persistPurchaseDocumentFromAi(array $normalized, ?int $publicationId = null, string $category = 'compra'): PurchaseDocument
    {
        $norm = $this->normalizeAiPurchase($normalized);

        $doc = $norm['document'] ?? [];
        $items = $norm['items'] ?? [];

        $cfdi = [
            'party_rfc' => $doc['counterparty_rfc'] ?? null,
            'uuid'      => $doc['uuid'] ?? null,
            'serie'     => $doc['serie'] ?? null,
            'folio'     => $doc['folio'] ?? null,
        ];

        // ✅ dedupe por UUID
        $uuid = is_string($cfdi['uuid'] ?? null) ? trim((string)$cfdi['uuid']) : '';
        if ($uuid !== '') {
            $exists = PurchaseDocument::query()
                ->where('ai_meta->cfdi->uuid', $uuid)
                ->first();
            if ($exists) return $exists;
        }

        $purchase = PurchaseDocument::create([
            'publication_id'    => $publicationId,
            'created_by'        => Auth::id(),
            'source_kind'       => 'upload',
            'category'          => $category,
            'document_type'     => $doc['document_type'] ?? 'otro',
            'supplier_name'     => $doc['supplier_name'] ?? null,
            'currency'          => $doc['currency'] ?? 'MXN',
            'document_datetime' => $doc['document_datetime'] ?: null,
            'subtotal'          => $this->toMoney($doc['subtotal'] ?? 0),
            'tax'               => $this->toMoney($doc['tax'] ?? 0),
            'total'             => $this->toMoney($doc['total'] ?? 0),
            'ai_meta'           => [
                'notes' => $norm['notes'] ?? null,
                'stats' => $norm['stats'] ?? null,
                'cfdi'  => $cfdi,
            ],
        ]);

        foreach ($items as $it) {
            PurchaseItem::create([
                'purchase_document_id' => $purchase->id,
                'item_name'   => $it['item_name'] ?? null,
                'item_raw'    => $it['item_raw'] ?? null,
                'unit'        => $it['unit'] ?? null,
                'qty'         => (float)($it['qty'] ?? 1),
                'unit_price'  => (float)($it['unit_price'] ?? 0),
                'line_total'  => (float)($it['line_total'] ?? 0),
                'ai_meta'     => $it['ai_meta'] ?? null,
            ]);
        }

        return $purchase;
    }

    /* =========================
     | ITEMS CLEAN + DEDUPE
     ========================= */
    private function filterAndDedupeExtractedItems(array $items): array
    {
        $clean = [];

        foreach ($items as $it) {
            if (!is_array($it)) continue;

            $raw  = trim((string)($it['item_raw'] ?? ''));
            $name = trim((string)($it['item_name'] ?? ''));

            if ($raw === '' && $name === '') continue;
            if ($this->looksLikeTaxLine($raw) || $this->looksLikeTaxLine($name)) continue;

            $qty = $this->toQty($it['qty'] ?? 1);

            $unitPrice = $it['unit_price'] ?? null;
            $lineTotal = $it['line_total'] ?? null;

            $uP = (is_null($unitPrice) || $unitPrice === '') ? null : $this->toMoney($unitPrice, 4);
            $lT = (is_null($lineTotal) || $lineTotal === '') ? null : $this->toMoney($lineTotal, 2);

            if (($uP === null || $uP <= 0) || ($lT === null || $lT <= 0)) {
                $vals = $this->moneyValuesFromText($raw);
                if (count($vals) >= 2) {
                    if ($uP === null || $uP <= 0) $uP = round($vals[count($vals)-2], 4);
                    if ($lT === null || $lT <= 0) $lT = round($vals[count($vals)-1], 2);
                }
            }

            $prodserv = $it['ai_meta']['prodserv'] ?? null;
            $prodserv = is_string($prodserv) ? trim($prodserv) : null;

            $hasUP = (!is_null($uP) && $uP > 0);
            $hasLT = (!is_null($lT) && $lT > 0);

            if (!$hasUP && !$hasLT) continue;

            if ($hasUP && $hasLT) {
                $calc = round($qty * $uP, 2);
                $diff = abs($calc - $lT);
                $tol = max(1.00, $lT * 0.02);
                if ($diff > $tol) continue;
            } else {
                if ($hasLT && !$hasUP && $qty > 0) $uP = round($lT / $qty, 4);
                if ($hasUP && !$hasLT && $qty > 0) $lT = round($qty * $uP, 2);
            }

            $clean[] = [
                'item_raw'   => $raw ?: $name,
                'item_name'  => $this->cleanText($name ?: $raw),
                'qty'        => $qty,
                'unit'       => $it['unit'] ?? null,
                'unit_price' => $uP,
                'line_total' => $lT,
                'ai_meta'    => ['prodserv' => $prodserv],
            ];
        }

        $seen = [];
        $out = [];
        foreach ($clean as $it) {
            $k = md5(mb_strtolower(
                trim((string)$it['item_name']) . '|' .
                (string)$it['qty'] . '|' .
                (string)$it['unit_price'] . '|' .
                (string)$it['line_total']
            ));
            if (isset($seen[$k])) continue;
            $seen[$k] = true;
            $out[] = $it;
        }

        return $out;
    }

    private function topItems(array $items, int $limit = 8): array
    {
        $agg = [];
        foreach ($items as $it) {
            $key = mb_strtolower(trim($it['item_name'] ?? ''));
            if ($key === '') continue;
            if (!isset($agg[$key])) {
                $agg[$key] = ['item_name' => $it['item_name'], 'qty' => 0, 'spent' => 0];
            }
            $agg[$key]['qty']   += (float)($it['qty'] ?? 0);
            $agg[$key]['spent'] += (float)($it['line_total'] ?? 0);
        }

        usort($agg, fn($a,$b) => $b['spent'] <=> $a['spent']);
        return array_slice(array_values($agg), 0, $limit);
    }

    private function buildAlreadyExtractedHints(array $items, int $limit = 20): string
    {
        if (empty($items)) return '(vacío)';
        $slice = array_slice($items, 0, $limit);

        $lines = [];
        foreach ($slice as $it) {
            if (!is_array($it)) continue;
            $raw = trim((string)($it['item_raw'] ?? $it['item_name'] ?? ''));
            $qty = (string)($it['qty'] ?? '');
            $up  = (string)($it['unit_price'] ?? '');
            $lt  = (string)($it['line_total'] ?? '');
            $raw = mb_substr($raw, 0, 140);
            $lines[] = "- {$raw} | qty={$qty} up={$up} total={$lt}";
        }

        return implode("\n", $lines);
    }

    private function moneyValuesFromText(?string $text): array
    {
        $text = (string)$text;
        if ($text === '') return [];

        preg_match_all('/\$?\s*([0-9]{1,3}(?:,[0-9]{3})*(?:\.[0-9]{2})|[0-9]+(?:\.[0-9]{2}))/u', $text, $m);

        $vals = [];
        foreach (($m[1] ?? []) as $v) {
            $v = str_replace(',', '', $v);
            if (is_numeric($v)) $vals[] = (float)$v;
        }
        return $vals;
    }

    private function looksLikeTaxLine(?string $raw): bool
    {
        $raw = mb_strtolower((string)$raw);
        return str_contains($raw, 'iva') || str_contains($raw, 'impuesto') || str_contains($raw, 'tax');
    }

    private function toMoney($val, int $decimals = 2): float
    {
        if (is_null($val)) return 0.0;

        if (is_string($val)) {
            $clean = preg_replace('/[^0-9.,-]/', '', $val);
            $clean = str_replace(',', '', $clean);
            if (!is_numeric($clean)) return 0.0;
            $val = (float)$clean;
        }

        if (!is_numeric($val)) return 0.0;
        return round((float)$val, $decimals);
    }

    private function toQty($val): float
    {
        if (is_null($val) || $val === '') return 1.0;

        if (is_string($val)) {
            $clean = preg_replace('/[^0-9.,-]/', '', $val);
            $clean = str_replace(',', '.', $clean);
            if (!is_numeric($clean)) return 1.0;
            $val = (float)$clean;
        }

        $q = (float)$val;
        if ($q <= 0) $q = 1.0;
        return round($q, 3);
    }

    private function cleanText(string $s): string
    {
        $s = trim(preg_replace('/\s+/', ' ', $s));
        return mb_substr($s, 0, 255);
    }

    private function detectKind(?string $mime, string $ext): string
    {
        $mime = (string)$mime;
        $ext = strtolower($ext);

        if (str_starts_with($mime, 'image/')) return 'image';
        if (str_starts_with($mime, 'video/')) return 'video';
        if ($mime === 'application/pdf' || $ext === 'pdf') return 'pdf';

        $docExt = ['doc','docx','odt','rtf'];
        $xlsExt = ['xls','xlsx','csv','ods'];
        if (in_array($ext, $docExt, true)) return 'doc';
        if (in_array($ext, $xlsExt, true)) return 'sheet';

        return 'file';
    }
}