<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\MatchLicitacionPropuestaItems;
use App\Models\LicitacionPropuesta;
use App\Models\LicitacionPropuestaItem;
use App\Models\LicitacionPdf;
use App\Models\Product;
use App\Services\LicitacionIaService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class LicitacionPropuestaController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [ new Middleware('auth') ];
    }

    public function index(Request $request)
    {
        $query = LicitacionPropuesta::query()->latest('fecha');

        if ($request->filled('licitacion_id'))  $query->where('licitacion_id', $request->integer('licitacion_id'));
        if ($request->filled('requisicion_id')) $query->where('requisicion_id', $request->integer('requisicion_id'));
        if ($request->filled('status'))         $query->where('status', $request->get('status'));

        $propuestas = $query->paginate(20);

        return view('admin.licitacion_propuestas.index', compact('propuestas'));
    }

    public function create(Request $request)
    {
        $licitacionId    = $request->integer('licitacion_id');
        $requisicionId   = $request->integer('requisicion_id');
        $licitacionPdfId = $request->integer('licitacion_pdf_id');

        $licitacionPdf = $licitacionPdfId ? LicitacionPdf::find($licitacionPdfId) : null;

        $pdfSplits = [];
        if ($licitacionPdf) {
            $meta = $licitacionPdf->meta ?? [];
            $pdfSplits = $meta['splits'] ?? [];
            if ($pdfSplits instanceof Collection) $pdfSplits = $pdfSplits->toArray();
            if (!is_array($pdfSplits)) $pdfSplits = [];
        }

        return view('admin.licitacion_propuestas.create', compact(
            'licitacionId',
            'requisicionId',
            'licitacionPdf',
            'licitacionPdfId',
            'pdfSplits'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'licitacion_pdf_id' => ['required', 'integer'],
            'titulo'            => ['nullable', 'string', 'max:255'],
            'moneda'            => ['nullable', 'string', 'max:10'],
            'fecha'             => ['nullable', 'date'],
        ]);

        $pdf = LicitacionPdf::findOrFail($data['licitacion_pdf_id']);

        $propuesta = LicitacionPropuesta::create([
            'licitacion_id'           => $pdf->licitacion_id,
            'requisicion_id'          => $pdf->requisicion_id,
            'licitacion_pdf_id'       => $pdf->id,
            'codigo'                  => $this->generarCodigo(),
            'titulo'                  => $data['titulo'] ?? 'Propuesta económica comparativa',
            'moneda'                  => $data['moneda'] ?? 'MXN',
            'fecha'                   => $data['fecha'] ?? now()->toDateString(),
            'status'                  => 'draft',
            'processed_split_indexes' => [],
            'merge_status'            => 'pending',
            'subtotal'                => 0,
            'iva'                     => 0,
            'total'                   => 0,
        ]);

        $routeName = Route::has('admin.licitacion-propuestas.show')
            ? 'admin.licitacion-propuestas.show'
            : (Route::has('licitacion-propuestas.show') ? 'licitacion-propuestas.show' : null);

        if ($routeName) {
            return redirect()
                ->route($routeName, ['licitacionPropuesta' => $propuesta->id])
                ->with('status', 'Propuesta creada. Procesa cada requisición con IA.');
        }

        return back()->with('status', 'Propuesta creada.');
    }

    public function show(LicitacionPropuesta $licitacionPropuesta)
    {
        $propuesta = $licitacionPropuesta->load([
            'items.requestItem.page',
            'items.product',
            'licitacionPdf',
        ]);

        $licitacionPdf = $propuesta->licitacionPdf;

        // ==========================================================
        // ✅ 5 coincidencias por item desde TU tabla products
        // ==========================================================
        $candidatesByItem = [];
        foreach ($propuesta->items as $item) {
            if ($item->product_id) continue;

            $text = trim((string)($item->descripcion_raw ?: ($item->requestItem?->line_raw ?? '')));
            if ($text === '') continue;

            $candidatesByItem[$item->id] = $this->findCandidates($text, 5);
        }

        // ==========================================================
        // Splits (para UI)
        // ==========================================================
        $splitsInfo = [];
        $allSplitsProcessed = false;

        if ($licitacionPdf) {
            $meta = $licitacionPdf->meta ?? [];
            $rawSplits = $meta['splits'] ?? [];
            if ($rawSplits instanceof Collection) $rawSplits = $rawSplits->toArray();
            if (!is_array($rawSplits)) $rawSplits = [];

            $processed = $propuesta->processed_split_indexes ?? [];
            if ($processed instanceof Collection) $processed = $processed->toArray();
            if (!is_array($processed)) $processed = [];

            foreach ($rawSplits as $idx => $split) {
                $from = $split['from'] ?? ($split['from_page'] ?? null);
                $to   = $split['to']   ?? ($split['to_page']   ?? null);

                $pages = $split['page_count'] ?? ($split['pages_count'] ?? null);
                if (!$pages && is_numeric($from) && is_numeric($to)) {
                    $pages = ((int)$to - (int)$from + 1);
                }

                $splitsInfo[] = [
                    'index' => $idx,
                    'from'  => $from,
                    'to'    => $to,
                    'pages' => $pages,
                    'state' => in_array($idx, $processed, true) ? 'done' : 'pending',
                ];
            }

            $allSplitsProcessed = !empty($splitsInfo)
                && collect($splitsInfo)->every(fn ($s) => ($s['state'] ?? null) === 'done');
        }

        return view('admin.licitacion_propuestas.show', [
            'propuesta'          => $propuesta,
            'licitacionPdf'      => $licitacionPdf,
            'splitsInfo'         => $splitsInfo,
            'allSplitsProcessed' => $allSplitsProcessed,
            'candidatesByItem'   => $candidatesByItem,
        ]);
    }

    public function processSplit(
        Request $request,
        LicitacionPropuesta $licitacionPropuesta,
        int $splitIndex,
        LicitacionIaService $iaService
    ) {
        $propuesta = $licitacionPropuesta;

        if (!$propuesta->licitacion_pdf_id || !$propuesta->licitacionPdf) {
            Log::warning('Intento de procesar split sin PDF asociado', [
                'propuesta_id' => $propuesta->id,
                'split_index'  => $splitIndex,
            ]);
            return back()->with('error', 'Esta propuesta no tiene PDF asociado.');
        }

        try {
            // ✅ IA: crea items y retorna IDs creados (para que puedas hacer matching SYNC solo a esos)
            $createdIds = $iaService->processSplitWithAi($propuesta, $splitIndex);

            // marcar split como procesado
            $processed = $propuesta->processed_split_indexes ?? [];
            if ($processed instanceof Collection) $processed = $processed->toArray();
            if (!is_array($processed)) $processed = [];

            if (!in_array($splitIndex, $processed, true)) $processed[] = $splitIndex;

            $propuesta->processed_split_indexes = $processed;
            $propuesta->save();

            // ⚠️ Esto te estaba tronando por timeout cuando había muchos items.
            // Si lo quieres dejar en SYNC, al menos limítalo a los IDs recién creados:
            if (!empty($createdIds)) {
                MatchLicitacionPropuestaItems::dispatchSync($propuesta->id, $createdIds);
            }

            return redirect()
                ->route('admin.licitacion-propuestas.show', ['licitacionPropuesta' => $propuesta->id])
                ->with('status', "Split {$splitIndex} procesado.");
        } catch (\Throwable $e) {
            Log::error('Error al procesar split de licitación con IA', [
                'propuesta_id' => $propuesta->id,
                'split_index'  => $splitIndex,
                'error'        => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    public function applyMatch(Request $request, LicitacionPropuestaItem $item)
    {
        $data = $request->validate([
            'product_id'      => ['required', 'integer'],
            'precio_unitario' => ['nullable', 'numeric', 'min:0'],
        ]);

        $item->product_id = (int) $data['product_id'];

        if (array_key_exists('precio_unitario', $data) && $data['precio_unitario'] !== null) {
            $item->precio_unitario = (float) $data['precio_unitario'];
        } else {
            $item->loadMissing('product');
            $item->precio_unitario = (float) ($item->product->price ?? 0);
        }

        $qty = (float) ($item->cantidad_propuesta ?? 0);
        $pu  = (float) ($item->precio_unitario ?? 0);
        $item->subtotal = $qty * $pu;

        if (empty($item->motivo_seleccion)) $item->motivo_seleccion = 'Seleccionado manualmente';
        $item->save();

        $this->recalcTotals($item->propuesta);

        return back()->with('status', 'Producto aplicado.');
    }

    public function rejectMatch(Request $request, LicitacionPropuestaItem $item)
    {
        $item->product_id      = null;
        $item->match_score     = null;
        $item->precio_unitario = null;
        $item->subtotal        = 0;

        if (empty($item->motivo_seleccion)) $item->motivo_seleccion = 'No aplica / rechazado';
        $item->save();

        $this->recalcTotals($item->propuesta);

        return back()->with('status', 'Marcado como NO aplica.');
    }

    public function mergeGlobal(Request $request, LicitacionPropuesta $licitacionPropuesta)
    {
        $propuesta = $licitacionPropuesta;

        $pdf = $propuesta->licitacionPdf;
        $splits = $pdf ? (($pdf->meta ?? [])['splits'] ?? []) : [];
        if ($splits instanceof Collection) $splits = $splits->toArray();
        if (!is_array($splits)) $splits = [];

        $processed = $propuesta->processed_split_indexes ?? [];
        if ($processed instanceof Collection) $processed = $processed->toArray();
        if (!is_array($processed)) $processed = [];

        if (count($splits) === 0) return back()->with('error', 'No hay splits configurados.');
        if (count($processed) < count($splits)) return back()->with('error', 'Faltan splits por procesar.');

        $this->recalcTotals($propuesta);
        $propuesta->merge_status = 'merged';
        $propuesta->save();

        return back()->with('status', 'Merge global realizado. Totales actualizados.');
    }

    public function edit(LicitacionPropuesta $licitacionPropuesta)
    {
        $licitacionPropuesta->load(['items.requestItem.page', 'items.product']);
        return view('admin.licitacion_propuestas.edit', ['propuesta' => $licitacionPropuesta]);
    }

    public function update(Request $request, LicitacionPropuesta $licitacionPropuesta)
    {
        $data = $request->validate([
            'titulo' => ['nullable', 'string', 'max:255'],
            'moneda' => ['nullable', 'string', 'max:10'],
            'fecha'  => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $licitacionPropuesta->update($data);

        return back()->with('status', 'Propuesta actualizada.');
    }

    public function destroy(LicitacionPropuesta $licitacionPropuesta)
    {
        $licitacionPropuesta->delete();
        return back()->with('status', 'Propuesta eliminada.');
    }

    protected function recalcTotals(LicitacionPropuesta $propuesta): void
    {
        $subtotal = (float) $propuesta->items()->sum('subtotal');
        $iva      = $subtotal * 0.16;
        $total    = $subtotal + $iva;

        $propuesta->subtotal = $subtotal;
        $propuesta->iva      = $iva;
        $propuesta->total    = $total;
        $propuesta->save();
    }

    protected function generarCodigo(): string
    {
        $nextId = (LicitacionPropuesta::max('id') ?? 0) + 1;
        return 'PRO-' . now()->format('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    // ==========================================================
    // ✅ Helpers: busca candidatos en products (máx 5)
    // ==========================================================
    protected function findCandidates(string $text, int $limit = 5): Collection
    {
        $limit = max(1, min(5, $limit));

        $keywords = $this->extractKeywords($text);

        // 1) intento: frase corta (primeros 60 chars)
        $short = mb_substr($this->normalizeText($text), 0, 60);

        $q = Product::query()
            ->select(['id', 'sku', 'name', 'brand', 'unit', 'price'])
            ->where('active', true);

        if ($short !== '') {
            $q->where(function ($qq) use ($short) {
                $qq->where('name', 'like', "%{$short}%")
                    ->orWhere('sku', 'like', "%{$short}%")
                    ->orWhere('description', 'like', "%{$short}%")
                    ->orWhere('tags', 'like', "%{$short}%")
                    ->orWhere('brand', 'like', "%{$short}%");
            });
        }

        $cands = $q->limit($limit)->get();

        // 2) si no encontró, buscar por keywords (AND suave)
        if ($cands->isEmpty() && !empty($keywords)) {
            $q2 = Product::query()
                ->select(['id', 'sku', 'name', 'brand', 'unit', 'price'])
                ->where('active', true);

            foreach ($keywords as $w) {
                $q2->where(function ($qq) use ($w) {
                    $qq->where('name', 'like', "%{$w}%")
                        ->orWhere('sku', 'like', "%{$w}%")
                        ->orWhere('description', 'like', "%{$w}%")
                        ->orWhere('tags', 'like', "%{$w}%")
                        ->orWhere('brand', 'like', "%{$w}%");
                });
            }

            $cands = $q2->limit($limit)->get();
        }

        // 3) fallback: OR por keywords
        if ($cands->isEmpty() && !empty($keywords)) {
            $q3 = Product::query()
                ->select(['id', 'sku', 'name', 'brand', 'unit', 'price'])
                ->where('active', true)
                ->where(function ($qq) use ($keywords) {
                    foreach ($keywords as $w) {
                        $qq->orWhere('name', 'like', "%{$w}%")
                            ->orWhere('sku', 'like', "%{$w}%")
                            ->orWhere('description', 'like', "%{$w}%")
                            ->orWhere('tags', 'like', "%{$w}%")
                            ->orWhere('brand', 'like', "%{$w}%");
                    }
                });

            $cands = $q3->limit($limit)->get();
        }

        return $cands;
    }

    protected function extractKeywords(string $text): array
    {
        $t = mb_strtolower($this->normalizeText($text));
        $t = preg_replace('/[^a-z0-9áéíóúñü\s]/iu', ' ', $t);
        $parts = preg_split('/\s+/', trim($t)) ?: [];

        $stop = [
            'de','del','la','las','el','los','y','o','u','en','con','para','por','a','al','un','una','unos','unas',
            'tipo','color','medida','medidas','aprox','aproximadas','fabricada','fabricado','incluye','incluido',
            'capacidad','hasta','garantia','meses','años','debera','cumplir','norma','nmx','nom','ance',
            'cm','mm','pulgadas','pulgada','mts','metro','metros','volt','volts','hertz','hz','amperes','cc'
        ];

        $words = [];
        foreach ($parts as $w) {
            if (mb_strlen($w) < 4) continue;
            if (in_array($w, $stop, true)) continue;
            $words[] = $w;
        }

        $words = array_values(array_unique($words));
        return array_slice($words, 0, 6);
    }

    protected function normalizeText(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return $text;
    }
        // ==========================================================
    // ✅ AJAX: buscador server-side (products) para dropdown/modal
    // ==========================================================
    public function searchProducts(Request $request)
    {
        $q     = trim((string) $request->get('q', ''));
        $page  = max(1, (int) $request->get('page', 1));
        $limit = min(30, max(10, (int) $request->get('limit', 20)));

        $query = Product::query()
            ->select(['id','sku','name','brand','unit','price','cost']) // 👈 cost si existe
            ->where('active', true)
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('sku', 'like', "%{$q}%")
                      ->orWhere('brand', 'like', "%{$q}%")
                      ->orWhere('description', 'like', "%{$q}%")
                      ->orWhere('tags', 'like', "%{$q}%");
                });
            })
            ->orderBy('name');

        $total = (clone $query)->count();

        $items = $query->forPage($page, $limit)->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'text' => trim(($p->sku ? $p->sku.' — ' : '').$p->name.' — '.$p->brand),
                'meta' => [
                    'sku'   => $p->sku,
                    'name'  => $p->name,
                    'brand' => $p->brand,
                    'unit'  => $p->unit,
                    'price' => (float) ($p->price ?? 0),
                    'cost'  => (float) ($p->cost ?? 0), // si no tienes cost, quedará 0
                ],
            ];
        });

        return response()->json([
            'results' => $items,
            'pagination' => [
                'more' => ($page * $limit) < $total,
            ],
        ]);
    }

    // ==========================================================
    // ✅ AJAX: aplicar producto seleccionado + autocalcular
    // ==========================================================
    public function applyProductAjax(Request $request, LicitacionPropuestaItem $item)
    {
        $data = $request->validate([
            'product_id'      => ['required', 'integer', 'exists:products,id'],
            'precio_unitario' => ['nullable', 'numeric', 'min:0'],
            'motivo'          => ['nullable', 'string', 'max:255'],
        ]);

        $productId = (int) $data['product_id'];

        // cargar relaciones necesarias
        $item->loadMissing(['propuesta', 'product']);

        $product = Product::select(['id','sku','name','brand','unit','price','cost'])
            ->where('active', true)
            ->findOrFail($productId);

        // aplicar
        $item->product_id = $product->id;

        // precio unitario: si viene manual, úsalo; si no, usa price del producto
        if (array_key_exists('precio_unitario', $data) && $data['precio_unitario'] !== null) {
            $item->precio_unitario = (float) $data['precio_unitario'];
        } else {
            $item->precio_unitario = (float) ($product->price ?? 0);
        }

        // ✅ costo automático (si tu tabla tiene columna)
        // Si tu columna se llama diferente (ej: costo_jureto), cámbiala aquí:
        if (property_exists($item, 'costo') || \Illuminate\Support\Facades\Schema::hasColumn($item->getTable(), 'costo')) {
            $item->costo = (float) ($product->cost ?? 0);
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn($item->getTable(), 'costo_jureto')) {
            $item->costo_jureto = (float) ($product->cost ?? 0);
        }

        // subtotal
        $qty = (float) ($item->cantidad_propuesta ?? 0);
        $pu  = (float) ($item->precio_unitario ?? 0);
        $item->subtotal = $qty * $pu;

        $item->match_score = $item->match_score ?? null;

        $item->motivo_seleccion = $data['motivo']
            ?? ($item->motivo_seleccion ?: 'Seleccionado manualmente');

        $item->save();

        // recalcular totales propuesta
        $this->recalcTotals($item->propuesta);

        return response()->json([
            'ok' => true,
            'row' => [
                'item_id'        => $item->id,
                'product_id'     => $product->id,
                'sku'            => $product->sku,
                'name'           => $product->name,
                'brand'          => $product->brand,
                'unit'           => $product->unit,
                'cost'           => (float) ($product->cost ?? 0),
                'precio_unitario'=> (float) ($item->precio_unitario ?? 0),
                'subtotal'       => (float) ($item->subtotal ?? 0),
            ],
            'totals' => [
                'subtotal' => (float) $item->propuesta->subtotal,
                'iva'      => (float) $item->propuesta->iva,
                'total'    => (float) $item->propuesta->total,
            ],
        ]);
    }

}
