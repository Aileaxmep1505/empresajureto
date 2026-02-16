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
use Illuminate\Support\Facades\DB;

class PublicationController extends Controller
{
    public function index()
    {
        // ----------------------------------------------------
        // 1. Publicaciones (Fijadas y Recientes)
        // ----------------------------------------------------
        $pinned = Publication::query()->where('pinned', true)->latest('created_at')->get();
        $latest = Publication::query()->where('pinned', false)->latest('created_at')->paginate(12);

        // ----------------------------------------------------
        // 2. Estadísticas Generales
        // ----------------------------------------------------
        $totalSpent = PurchaseDocument::sum('total');

        // ----------------------------------------------------
        // 3. Gráfica 1: Tendencia Mensual (Area Chart)
        // ----------------------------------------------------
        $monthlyData = PurchaseDocument::selectRaw("DATE_FORMAT(document_datetime, '%Y-%m') as month_id, SUM(total) as total")
            ->whereNotNull('document_datetime')
            ->groupBy('month_id')->orderBy('month_id', 'asc')->get();

        $chartLabels = []; 
        $chartValues = [];
        foreach($monthlyData as $row) {
            try { 
                $dt = Carbon::createFromFormat('Y-m', $row->month_id); 
                $chartLabels[] = $dt->translatedFormat('M Y'); 
            } catch (\Exception $e) { 
                $chartLabels[] = $row->month_id; 
            }
            $chartValues[] = (float) $row->total;
        }

        // ----------------------------------------------------
        // 4. Gráfica 2: Gasto por Día - Últimos 30 días (Bar Chart)
        // ----------------------------------------------------
        $dailyData = PurchaseDocument::selectRaw("DATE(document_datetime) as day, SUM(total) as total")
            ->whereNotNull('document_datetime')
            ->where('document_datetime', '>=', now()->subDays(30))
            ->groupBy('day')->orderBy('day', 'asc')->get();
        
        $dailyLabels = $dailyData->map(fn($d) => Carbon::parse($d->day)->format('d/m'));
        $dailyValues = $dailyData->pluck('total');

        // ----------------------------------------------------
        // 5. Gráfica 3: Top 10 Productos (CORREGIDO)
        // ----------------------------------------------------
        // Se usa estructura {x, y} para que la gráfica muestre NOMBRES y no números.
        $topProductsData = PurchaseItem::selectRaw("item_name, SUM(line_total) as total_spent")
            ->whereNotNull('item_name')
            ->where('item_name', '!=', '') 
            ->groupBy('item_name')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();
        
        $prodChartData = $topProductsData->map(function($item) {
            return [
                'x' => Str::limit($item->item_name, 25), // Nombre del producto (eje Y)
                'y' => (float) $item->total_spent        // Valor gastado (barra)
            ];
        });

        // ----------------------------------------------------
        // 6. Tabla General: Últimas 100 compras desglosadas
        // ----------------------------------------------------
        $allPurchases = PurchaseItem::select(
                'purchase_items.*', 
                'purchase_documents.document_datetime', 
                'purchase_documents.supplier_name'
            )
            ->join('purchase_documents', 'purchase_items.purchase_document_id', '=', 'purchase_documents.id')
            ->orderByDesc('purchase_documents.document_datetime')
            ->limit(100)
            ->get();

        // ----------------------------------------------------
        // 7. Top Proveedores
        // ----------------------------------------------------
        $topSuppliers = PurchaseDocument::selectRaw("supplier_name, COUNT(*) as count, SUM(total) as total_amount")
            ->whereNotNull('supplier_name')
            ->where('supplier_name', '!=', '')
            ->groupBy('supplier_name')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get();

        return view('publications.index', compact(
            'pinned', 'latest', 
            'totalSpent', 
            'chartLabels', 'chartValues', 
            'dailyLabels', 'dailyValues', 
            'prodChartData', // Variable corregida para la gráfica de productos
            'allPurchases', 
            'topSuppliers'
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
        ]);

        if ($request->boolean('ai_extract') && in_array($kind, ['pdf','image'], true)) {
            try {
                $absolute = Storage::disk('public')->path($pub->file_path);
                $ai = $this->aiExtractSingleLocalFile($absolute, $pub->original_name);

                if ($ai && !empty($ai['items'])) {
                    $this->persistPurchaseDocumentFromAi($ai, $pub->id);
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
            'file' => ['required','file','max:20480','mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        $file = $request->file('file');
        if (!$file) {
            return response()->json(['error' => 'No se recibió archivo.'], 422);
        }

        try {
            $ai = $this->aiExtractSingleLocalFile($file->getRealPath(), $file->getClientOriginalName());

            if (!$ai || empty($ai['items'])) {
                return response()->json(['error' => 'La IA no pudo detectar conceptos de compra.'], 422);
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
        ]);

        $payload = $request->input('payload');

        $normalized = $this->normalizeAiPurchase($payload);
        if (empty($normalized['items'])) {
            return response()->json(['error' => 'No hay items para guardar.'], 422);
        }

        $doc = $this->persistPurchaseDocumentFromAi($normalized, $request->input('publication_id'));

        return response()->json([
            'ok' => true,
            'purchase_document_id' => $doc->id,
        ]);
    }

    /* ==========================
     | IA: Core extractor (1 file)
     ========================== */
    private function aiExtractSingleLocalFile(string $absolutePath, string $originalName): array
    {
        // Configuración OpenAI
        $apiKey  = config('openai.api_key') ?: env('OPENAI_API_KEY'); 
        $baseUrl = config('openai.base_url', 'https://api.openai.com');
        $modelId = config('openai.primary', 'gpt-5-2025-08-07'); 

        if (!$apiKey) throw new \RuntimeException('Missing OpenAI API key.');

        // 1) Subir archivo
        $upload = Http::withToken($apiKey)
            ->attach('file', file_get_contents($absolutePath), $originalName)
            ->post($baseUrl.'/v1/files', ['purpose' => 'user_data']);

        if (!$upload->ok()) {
            Log::warning('AI file upload error', ['status'=>$upload->status(),'body'=>$upload->body()]);
            throw new \RuntimeException('Error subiendo archivo a OpenAI.');
        }

        $fileId = $upload->json('id');
        if (!$fileId) throw new \RuntimeException('OpenAI no regresó file_id.');

        $fileInputs = [[ 'type' => 'input_file', 'file_id' => $fileId ]];

        // 2) Prompt
        $systemPrompt = <<<TXT
Eres un extractor experto de compras (México).
A partir de un PDF o imagen (ticket/factura/remisión):
- Detecta TODOS los renglones de productos/servicios comprados.
- Extrae cantidades, precios unitarios y totales por renglón si existen.
- Detecta fecha/hora del documento si aparece y proveedor/tienda.
- Si hay IVA, subtotal/total, extrae también.

RESPONDE EXCLUSIVAMENTE un JSON válido con esta forma:

{
  "document": {
    "document_type": "ticket|factura|remision|otro",
    "supplier_name": "",
    "currency": "MXN",
    "document_datetime": "YYYY-MM-DD HH:MM:SS" ,
    "subtotal": 0,
    "tax": 0,
    "total": 0
  },
  "items": [
    {
      "item_raw": "texto original del renglón",
      "item_name": "nombre limpio y entendible",
      "qty": 1,
      "unit": "pza|kg|lt|... (si se ve)",
      "unit_price": 0,
      "line_total": 0
    }
  ],
  "notes": {
    "warnings": [],
    "confidence": 0.0
  }
}

Reglas:
- JSON únicamente. Sin markdown.
- qty numérico.
- unit_price numérico (MXN).
- line_total numérico.
- Si no aparece fecha, document_datetime = null.
TXT;

        $userText = "Extrae los conceptos comprados y genera el JSON solicitado (document + items).";

        // NOTA: Tokens altos y SIN temperature para evitar errores 400 y cortes
        $resp = Http::withToken($apiKey)
            ->timeout(config('openai.timeout', 120)) 
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($baseUrl.'/v1/responses', [
                'model'             => $modelId,
                'instructions'      => $systemPrompt,
                'input'             => [[
                    'role'    => 'user',
                    'content' => array_merge([
                        ['type'=>'input_text','text'=>$userText],
                    ], $fileInputs),
                ]],
                'max_output_tokens' => 10000, 
            ]);

        if (!$resp->ok()) {
            Log::warning('AI responses error', ['status'=>$resp->status(),'body'=>$resp->body()]);
            throw new \RuntimeException('La IA respondió con error.');
        }

        $json = $resp->json();

        // Extraer output
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
        } 
        else if (isset($json['choices'][0]['message']['content'])) {
            $rawText = $json['choices'][0]['message']['content'];
        }

        $rawText = trim($rawText);
        $rawText = preg_replace('/^```json\s*|\s*```$/', '', $rawText);

        if ($rawText === '') throw new \RuntimeException('No se pudo leer salida de IA.');

        $data = json_decode($rawText, true);
        if (!is_array($data)) {
            Log::warning('AI invalid JSON', ['raw'=>$rawText]);
            throw new \RuntimeException('La IA no devolvió JSON válido.');
        }

        return $data;
    }

    /* ==========================================
     | Normalización + Stats
     ========================================== */
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

        $normItems = [];
        $sumLines = 0;

        foreach ($items as $row) {
            if (!is_array($row)) continue;

            $qty  = $this->toQty($row['qty'] ?? 1);
            $uP   = $this->toMoney($row['unit_price'] ?? 0, 4);
            $lTot = $this->toMoney($row['line_total'] ?? 0);

            if ($lTot <= 0 && $uP > 0 && $qty > 0) {
                $lTot = round($qty * $uP, 2);
            }

            $sumLines += $lTot;

            $normItems[] = [
                'item_raw'    => $row['item_raw'] ?? null,
                'item_name'   => $this->cleanText($row['item_name'] ?? ($row['item_raw'] ?? '')),
                'qty'         => $qty,
                'unit'        => $row['unit'] ?? null,
                'unit_price'  => $uP,
                'line_total'  => $lTot,
                'ai_meta'     => $row['ai_meta'] ?? null,
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

    private function persistPurchaseDocumentFromAi(array $normalized, ?int $publicationId = null): PurchaseDocument
    {
        $doc = $normalized['document'] ?? [];
        $items = $normalized['items'] ?? [];

        $purchase = PurchaseDocument::create([
            'publication_id'    => $publicationId,
            'created_by'        => Auth::id(),
            'source_kind'       => 'upload',
            'document_type'     => $doc['document_type'] ?? 'otro',
            'supplier_name'     => $doc['supplier_name'] ?? null,
            'currency'          => $doc['currency'] ?? 'MXN',
            'document_datetime' => $doc['document_datetime'] ?: null,
            'subtotal'          => $this->toMoney($doc['subtotal'] ?? 0),
            'tax'               => $this->toMoney($doc['tax'] ?? 0),
            'total'             => $this->toMoney($doc['total'] ?? 0),
            'ai_meta'           => [
                'notes' => $normalized['notes'] ?? null,
                'stats' => $normalized['stats'] ?? null,
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