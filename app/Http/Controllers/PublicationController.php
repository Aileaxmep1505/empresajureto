<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use App\Models\PurchaseDocument;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PublicationController extends Controller
{
    public function index()
    {
        $pinned = Publication::query()->where('pinned', true)->latest('created_at')->get();
        $latest = Publication::query()->where('pinned', false)->latest('created_at')->paginate(12);

        // =========================
        // ✅ KPI Compras vs Ventas
        // =========================
        $totalSpentCompra = (float) PurchaseDocument::where('category', 'compra')->sum('total');
        $totalSpentVenta  = (float) PurchaseDocument::where('category', 'venta')->sum('total');
        $totalSpent       = (float) PurchaseDocument::sum('total'); // compat

        // ==========================================
        // ✅ Rango fijo para gráficas (siempre hay labels)
        // - Mensual: últimos 12 meses
        // - Diario: últimos 30 días
        // ==========================================
        $endDay   = now()->endOfDay();
        $startDay = now()->subDays(29)->startOfDay(); // 30 días contando hoy

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

        // ==========================================
        // ✅ Mensual: usa fecha efectiva:
        // COALESCE(document_datetime, created_at)
        // ==========================================
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

        // compat con tu vista vieja
        $chartValues = $monthlyCompra;

        // ==========================================
        // ✅ Diario: últimos 30 días, usando fecha efectiva
        // y generando SIEMPRE los 30 labels
        // ==========================================
        $days = [];
        $dCursor = $startDay->copy();
        for ($i=0; $i<30; $i++) {
            $days[] = $dCursor->toDateString(); // YYYY-MM-DD
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

        // compat con tu vista vieja
        $dailyValues = collect($dailyCompra);

        // ==========================================
        // ✅ Top 10 Productos: compras vs ventas
        // ==========================================
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

        $prodChartDataCompra = $topProductsCompra->map(function ($item) {
            return ['x' => Str::limit($item->item_name, 25), 'y' => (float) $item->total_spent];
        })->values()->all();

        $prodChartDataVenta = $topProductsVenta->map(function ($item) {
            return ['x' => Str::limit($item->item_name, 25), 'y' => (float) $item->total_spent];
        })->values()->all();

        // compat
        $prodChartData = $prodChartDataCompra;

        // ==========================================
        // ✅ Tabla: últimos movimientos (compras y ventas)
        // (ordenado por fecha efectiva)
        // ==========================================
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

        // ==========================================
        // ✅ Top Proveedores: (separado por categoría)
        // ==========================================
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

        $topSuppliers = $topSuppliersCompra; // compat

        return view('publications.index', compact(
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
        ));
    }

    public function create()
    {
        return view('publications.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => ['required','string','max:200'],
            'description' => ['nullable','string','max:5000'],
            'file'        => ['required','file','max:51200'],
            'pinned'      => ['nullable'],
            'ai_extract'  => ['nullable','boolean'],
            'ai_skip'     => ['nullable'],
            'ai_payload'  => ['nullable','string'],
            'category'    => ['required','string','in:compra,venta'],
        ]);

        $file = $request->file('file');
        $folder = 'publications/' . now()->format('Y/m');
        $safeBaseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        $name = $safeBaseName . '-' . Str::random(8) . ($ext ? ".{$ext}" : '');
        $path = $file->storeAs($folder, $name, 'public');

        $mime = $file->getClientMimeType();
        $kind = $this->detectKind($mime, $ext);

        $pub = Publication::create([
            'title'         => $request->title,
            'description'   => $request->description,
            'file_path'     => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $mime,
            'size'          => $file->getSize() ?: 0,
            'extension'     => $ext,
            'kind'          => $kind,
            'pinned'        => (bool) $request->boolean('pinned'),
            'created_by'    => Auth::id(),
            'category'      => $request->category,
        ]);

        $aiSkip = (string)($request->input('ai_skip', '0')) === '1';
        $aiPayloadRaw = trim((string)($request->input('ai_payload', '')));

        if (!$aiSkip && $aiPayloadRaw !== '') {
            try {
                $payload = json_decode($aiPayloadRaw, true);

                if (is_array($payload)) {
                    $normalized = $this->normalizeAiPurchase($payload);

                    if (!empty($normalized['items'])) {
                        $this->persistPurchaseDocumentFromAi($normalized, $pub->id, $request->category);
                    } else {
                        Log::warning('Publication store: ai_payload vacío (items=0)', ['publication_id' => $pub->id]);
                    }
                } else {
                    Log::warning('Publication store: ai_payload no es JSON', ['publication_id' => $pub->id]);
                }
            } catch (\Throwable $e) {
                Log::warning('Publication store: ai_payload persist failed (ignored)', [
                    'publication_id' => $pub->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return redirect()->route('publications.index')->with('ok', 'Publicación subida correctamente.');
        }

        if ($aiSkip) {
            return redirect()->route('publications.index')->with('ok', 'Publicación subida correctamente.');
        }

        if ($request->boolean('ai_extract') && in_array($kind, ['pdf','image'], true)) {
            try {
                $absolute = Storage::disk('public')->path($pub->file_path);
                $ai = $this->aiExtractSingleLocalFile($absolute, $pub->original_name, $request->category);

                if ($ai && !empty($ai['items'])) {
                    $this->persistPurchaseDocumentFromAi($ai, $pub->id, $request->category);
                }
            } catch (\Throwable $e) {
                Log::warning('Publication store: AI extract failed (ignored)', [
                    'publication_id' => $pub->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('publications.index')->with('ok', 'Publicación subida correctamente.');
    }

    public function show(Publication $publication)
    {
        $purchaseDocs = PurchaseDocument::query()
            ->where('publication_id', $publication->id)
            ->with('items')
            ->latest('id')
            ->get();

        return view('publications.show', compact('publication','purchaseDocs'));
    }

    public function download(Publication $publication)
    {
        if (!Storage::disk('public')->exists($publication->file_path)) abort(404);
        return Storage::disk('public')->download($publication->file_path, $publication->original_name);
    }

    public function destroy(Publication $publication)
    {
        if ($publication->file_path && Storage::disk('public')->exists($publication->file_path)) {
            Storage::disk('public')->delete($publication->file_path);
        }
        $publication->delete();

        return redirect()->route('publications.index')->with('ok', 'Publicación eliminada.');
    }

    /* ============================================================
     | IA (1) EXTRACT
     ============================================================ */
    public function aiExtractFromUpload(Request $request)
    {
        $request->validate([
            'file'     => ['required','file','max:20480','mimes:jpg,jpeg,png,webp,pdf'],
            'category' => ['required','string','in:compra,venta'],
        ]);

        $file = $request->file('file');
        if (!$file) return response()->json(['error' => 'No se recibió archivo.'], 422);

        try {
            $ai = $this->aiExtractSingleLocalFile(
                $file->getRealPath(),
                $file->getClientOriginalName(),
                $request->category
            );

            if (!$ai || empty($ai['items'])) {
                return response()->json(['error' => 'La IA no pudo detectar conceptos.'], 422);
            }

            $normalized = $this->normalizeAiPurchase($ai);

            return response()->json([
                'document' => $normalized['document'],
                'items'    => $normalized['items'],
                'stats'    => $normalized['stats'],
            ]);
        } catch (\Throwable $e) {
            Log::error('AI extract upload error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error al contactar la IA.'], 500);
        }
    }

    /* ============================================================
     | IA (2) SAVE
     ============================================================ */
    public function aiSaveExtracted(Request $request)
    {
        $request->validate([
            'publication_id' => ['nullable','integer'],
            'payload'        => ['required','array'],
            'category'       => ['required','string','in:compra,venta'],
        ]);

        $payload = $request->input('payload');

        $normalized = $this->normalizeAiPurchase($payload);
        if (empty($normalized['items'])) {
            return response()->json(['error' => 'No hay items para guardar.'], 422);
        }

        $doc = $this->persistPurchaseDocumentFromAi($normalized, $request->input('publication_id'), $request->category);

        return response()->json([
            'ok' => true,
            'purchase_document_id' => $doc->id,
        ]);
    }

    /* ==========================
     | IA: Core extractor (1 file)
     ========================== */
    private function aiExtractSingleLocalFile(string $absolutePath, string $originalName, string $category = 'compra'): array
    {
        $apiKey  = config('openai.api_key') ?: env('OPENAI_API_KEY');
        $baseUrl = rtrim(config('openai.base_url', 'https://api.openai.com'), '/');
        $modelId = config('openai.primary', 'gpt-4.1');

        if (!$apiKey) throw new \RuntimeException('Missing OpenAI API key.');

        $upload = Http::withToken($apiKey)
            ->timeout(config('openai.timeout', 300))
            ->attach('file', file_get_contents($absolutePath), $originalName)
            ->post($baseUrl . '/v1/files', ['purpose' => 'user_data']);

        if (!$upload->ok()) {
            Log::warning('AI file upload error', ['status' => $upload->status(), 'body' => $upload->body()]);
            throw new \RuntimeException('Error subiendo archivo a OpenAI.');
        }

        $fileId = $upload->json('id');
        if (!$fileId) throw new \RuntimeException('OpenAI no regresó file_id.');

        $system = $this->buildExtractorSystemPromptStrictTableOnly($category);

        $call = function(string $userText) use ($apiKey, $baseUrl, $modelId, $fileId, $system): array {
            $resp = Http::withToken($apiKey)
                ->timeout(config('openai.timeout', 300))
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($baseUrl . '/v1/responses', [
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

            return $this->parseOpenAiJsonFromResponses($resp->json());
        };

        $data1 = $call("Extrae TODOS los renglones REALES de la TABLA de conceptos en TODAS las páginas. NO inventes. Devuelve JSON.");
        $items = is_array($data1['items'] ?? null) ? $data1['items'] : [];
        $doc   = is_array($data1['document'] ?? null) ? $data1['document'] : [];

        $items = $this->filterAndDedupeExtractedItems($items);

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
        return <<<TXT
Eres un extractor experto de documentos contables de {$category} (México).
El archivo puede tener VARIAS PÁGINAS.

INSTRUCCIONES ESTRICTAS (para evitar “scan de más”):
- Extrae ÚNICAMENTE renglones REALES de la TABLA de conceptos/productos/servicios.
- Ignora sellos, firmas, anotaciones, stamps, “recibido”, marcas, textos fuera de la tabla.
- No inventes renglones. Si no lo ves, NO lo pongas.
- NO metas líneas de IVA como item (IVA va en document.tax).
- Cada item debe venir de una fila de tabla con evidencia de números:
  - Si hay Precio Unitario e Importe, deben venir con 2 decimales cuando aplique.
  - Si no puedes leer un monto con claridad, pon null (NO 0) y agrega warning.
- Si el documento trae “Clave ProdServ”, inclúyela dentro de item_raw o en ai_meta.prodserv.

RESPONDE EXCLUSIVAMENTE JSON válido:

{
  "document": {
    "document_type": "ticket|factura|remision|otro",
    "supplier_name": "",
    "currency": "MXN",
    "document_datetime": "YYYY-MM-DD HH:MM:SS",
    "subtotal": 0,
    "tax": 0,
    "total": 0
  },
  "items": [
    {
      "item_raw": "texto exacto del renglón de tabla",
      "item_name": "concepto limpio",
      "qty": 1,
      "unit": "pza|caja|kg|lt|...",
      "unit_price": null,
      "line_total": null,
      "ai_meta": {
        "prodserv": null
      }
    }
  ],
  "notes": {
    "warnings": [],
    "confidence": 0.0
  }
}

Reglas:
- JSON únicamente, sin markdown.
- qty numérico.
- unit_price y line_total: número si se ve; si no se ve, null (NO 0).
- NO devuelvas filas “fantasma”.
TXT;
    }

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
                'ai_meta'    => [
                    'prodserv' => $prodserv,
                ],
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

    private function normalizeAiPurchase(array $payload): array
    {
        $doc = $payload['document'] ?? [];
        $items = $payload['items'] ?? [];

        $document = [
            'document_type'     => $doc['document_type'] ?? 'otro',
            'supplier_name'     => $doc['supplier_name'] ?? null,
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

        if ($document['total'] <= 0 && $sumLines > 0) {
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

    private function persistPurchaseDocumentFromAi(array $normalized, ?int $publicationId = null, string $category = 'compra'): PurchaseDocument
    {
        $norm = $this->normalizeAiPurchase($normalized);

        $doc = $norm['document'] ?? [];
        $items = $norm['items'] ?? [];

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
