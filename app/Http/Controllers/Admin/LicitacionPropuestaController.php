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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

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

        // ======================== 5 coincidencias por item ========================
        $candidatesByItem = [];
        foreach ($propuesta->items as $item) {
            if ($item->product_id) continue;

            $text = trim((string)($item->descripcion_raw ?: ($item->requestItem?->line_raw ?? '')));
            if ($text === '') continue;

            $candidatesByItem[$item->id] = $this->findCandidatesSmart($text, 5);
        }

        // ======================== Splits para UI ========================
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

        $meta   = $propuesta->licitacionPdf->meta ?? [];
        $splits = $meta['splits'] ?? [];
        if ($splits instanceof Collection) $splits = $splits->toArray();
        if (!is_array($splits) || !isset($splits[$splitIndex])) {
            return back()->with('error', "Split {$splitIndex} no existe en meta->splits.");
        }

        $processed = $propuesta->processed_split_indexes ?? [];
        if ($processed instanceof Collection) $processed = $processed->toArray();
        if (!is_array($processed)) $processed = [];

        if (in_array($splitIndex, $processed, true)) {
            return back()->with('status', "Split {$splitIndex} ya estaba procesado.");
        }

        try {
            $createdIds = $iaService->processSplitWithAi($propuesta, $splitIndex);

            $processed[] = $splitIndex;
            $propuesta->processed_split_indexes = array_values(array_unique($processed));
            $propuesta->save();

            if (!empty($createdIds)) {
                MatchLicitacionPropuestaItems::dispatchSync($propuesta->id, $createdIds);
            }

            return redirect()
                ->route('admin.licitacion-propuestas.show', ['licitacionPropuesta' => $propuesta->id])
                ->with('status', "Split {$splitIndex} procesado. Items creados: " . count($createdIds));
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
        $subtotalBase = $qty * $pu;
        $item->subtotal = $subtotalBase;

        if (Schema::hasColumn($item->getTable(), 'utilidad_pct')) {
            $pct = (float) ($item->utilidad_pct ?? 0);
            $util = $subtotalBase * ($pct / 100.0);
            if (Schema::hasColumn($item->getTable(), 'utilidad_monto')) {
                $item->utilidad_monto = $util;
            }
            if (Schema::hasColumn($item->getTable(), 'subtotal_con_utilidad')) {
                $item->subtotal_con_utilidad = $subtotalBase + $util;
            }
        }

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

        if (Schema::hasColumn($item->getTable(), 'utilidad_pct')) {
            $item->utilidad_pct = 0;
        }
        if (Schema::hasColumn($item->getTable(), 'utilidad_monto')) {
            $item->utilidad_monto = 0;
        }
        if (Schema::hasColumn($item->getTable(), 'subtotal_con_utilidad')) {
            $item->subtotal_con_utilidad = 0;
        }

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

    // ======================== CRUD renglones manuales ========================

    public function storeItem(Request $request, LicitacionPropuesta $licitacionPropuesta)
    {
        $data = $request->validate([
            'descripcion_raw'    => ['required', 'string'],
            'unidad_propuesta'   => ['nullable', 'string', 'max:50'],
            'cantidad_propuesta' => ['nullable', 'numeric', 'min:0'],
            'precio_unitario'    => ['nullable', 'numeric', 'min:0'],
            'utilidad_pct'       => ['nullable', 'numeric', 'min:0'],
        ]);

        $qty = (float) ($data['cantidad_propuesta'] ?? 0);
        $pu  = (float) ($data['precio_unitario'] ?? 0);
        $subtotalBase = $qty * $pu;
        $pct  = (float) ($data['utilidad_pct'] ?? 0);
        $util = $subtotalBase * ($pct / 100.0);
        $subtotalConUtil = $subtotalBase + $util;

        $item = new LicitacionPropuestaItem();
        $item->licitacion_propuesta_id    = $licitacionPropuesta->id;
        $item->licitacion_request_item_id = null;
        $item->product_id                 = null;
        $item->descripcion_raw            = $data['descripcion_raw'];
        $item->unidad_propuesta           = $data['unidad_propuesta'] ?? null;
        $item->cantidad_propuesta         = $qty;
        $item->precio_unitario            = $pu;
        $item->subtotal                   = $subtotalBase;

        if (Schema::hasColumn($item->getTable(), 'utilidad_pct')) {
            $item->utilidad_pct = $pct;
        }
        if (Schema::hasColumn($item->getTable(), 'utilidad_monto')) {
            $item->utilidad_monto = $util;
        }
        if (Schema::hasColumn($item->getTable(), 'subtotal_con_utilidad')) {
            $item->subtotal_con_utilidad = $subtotalConUtil;
        }

        $item->save();

        $this->recalcTotals($licitacionPropuesta);

        return back()->with('status', 'Renglón agregado.');
    }

    public function updateItem(Request $request, LicitacionPropuestaItem $item)
    {
        $data = $request->validate([
            'descripcion_raw'    => ['nullable', 'string'],
            'unidad_propuesta'   => ['nullable', 'string', 'max:50'],
            'cantidad_propuesta' => ['nullable', 'numeric', 'min:0'],
            'precio_unitario'    => ['nullable', 'numeric', 'min:0'],
            'utilidad_pct'       => ['nullable', 'numeric', 'min:0'],
        ]);

        if (isset($data['descripcion_raw'])) {
            $item->descripcion_raw = $data['descripcion_raw'];
        }
        if (isset($data['unidad_propuesta'])) {
            $item->unidad_propuesta = $data['unidad_propuesta'];
        }
        if (isset($data['cantidad_propuesta'])) {
            $item->cantidad_propuesta = (float) $data['cantidad_propuesta'];
        }
        if (isset($data['precio_unitario'])) {
            $item->precio_unitario = (float) $data['precio_unitario'];
        }

        $qty = (float) ($item->cantidad_propuesta ?? 0);
        $pu  = (float) ($item->precio_unitario ?? 0);
        $subtotalBase = $qty * $pu;
        $item->subtotal = $subtotalBase;

        $pct = isset($data['utilidad_pct'])
            ? (float) $data['utilidad_pct']
            : (float) ($item->utilidad_pct ?? 0);

        if (Schema::hasColumn($item->getTable(), 'utilidad_pct')) {
            $item->utilidad_pct = max(0, $pct);
        }
        $util = $subtotalBase * (max(0, $pct) / 100.0);

        if (Schema::hasColumn($item->getTable(), 'utilidad_monto')) {
            $item->utilidad_monto = $util;
        }
        if (Schema::hasColumn($item->getTable(), 'subtotal_con_utilidad')) {
            $item->subtotal_con_utilidad = $subtotalBase + $util;
        }

        $item->save();

        $this->recalcTotals($item->propuesta);

        return back()->with('status', 'Renglón actualizado.');
    }

    public function destroyItem(LicitacionPropuestaItem $item)
    {
        $propuesta = $item->propuesta;
        $item->delete();

        if ($propuesta) {
            $this->recalcTotals($propuesta);
        }

        return back()->with('status', 'Renglón eliminado.');
    }

    // ======================== AJAX utilidad por renglón ========================

    public function updateItemUtilityAjax(Request $request, LicitacionPropuestaItem $item)
    {
        $data = $request->validate([
            'utilidad_pct' => ['nullable', 'numeric', 'min:0'],
        ]);

        $item->loadMissing('propuesta');

        $pct = isset($data['utilidad_pct']) ? max(0, (float) $data['utilidad_pct']) : 0.0;

        if (!Schema::hasColumn($item->getTable(), 'utilidad_pct')) {
            return response()->json([
                'ok'    => false,
                'error' => 'La columna utilidad_pct no existe en la tabla de items.',
            ], 422);
        }

        $qty = (float) ($item->cantidad_propuesta ?? 0);
        $pu  = (float) ($item->precio_unitario ?? 0);
        $subtotalBase = $qty * $pu;

        $item->subtotal     = $subtotalBase;
        $item->utilidad_pct = $pct;

        $utilidadMonto = $subtotalBase * ($pct / 100.0);

        if (Schema::hasColumn($item->getTable(), 'utilidad_monto')) {
            $item->utilidad_monto = $utilidadMonto;
        }
        if (Schema::hasColumn($item->getTable(), 'subtotal_con_utilidad')) {
            $item->subtotal_con_utilidad = $subtotalBase + $utilidadMonto;
        }

        $item->save();

        $this->recalcTotals($item->propuesta);
        $propuesta = $item->propuesta->fresh();

        $itemsBuilder = $propuesta->items();
        $subtotalBaseTotal = (float) $itemsBuilder->sum('subtotal');
        $utilidadTotal = Schema::hasColumn((new LicitacionPropuestaItem)->getTable(), 'utilidad_monto')
            ? (float) $propuesta->items()->sum('utilidad_monto')
            : 0.0;

        return response()->json([
            'ok' => true,
            'row' => [
                'item_id'               => $item->id,
                'utilidad_pct'          => (float) ($item->utilidad_pct ?? 0),
                'subtotal_base'         => (float) ($item->subtotal ?? 0),
                'utilidad_monto'        => (float) ($item->utilidad_monto ?? 0),
                'subtotal_con_utilidad' => (float) ($item->subtotal_con_utilidad ?? (($item->subtotal ?? 0) + ($item->utilidad_monto ?? 0))),
            ],
            'totals' => [
                'subtotal_base'         => (float) ($propuesta->subtotal_base ?? $subtotalBaseTotal),
                'utilidad'              => (float) ($propuesta->utilidad_total ?? $utilidadTotal),
                'subtotal_con_utilidad' => (float) $propuesta->subtotal,
                'iva'                   => (float) $propuesta->iva,
                'total'                 => (float) $propuesta->total,
            ],
        ]);
    }

    // ======================== Exportar PDF / Word ========================

    public function exportPdf(LicitacionPropuesta $licitacionPropuesta)
    {
        $propuesta = $licitacionPropuesta->load([
            'items.requestItem.page',
            'items.product',
        ]);

        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            abort(501, 'Exportar a PDF no está configurado.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.licitacion_propuestas.export_pdf',
            ['propuesta' => $propuesta]
        );

        return $pdf->download('propuesta-'.$propuesta->codigo.'.pdf');
    }

    public function exportWord(LicitacionPropuesta $licitacionPropuesta)
    {
        $propuesta = $licitacionPropuesta->load([
            'items.requestItem.page',
            'items.product',
        ]);

        $html = view('admin.licitacion_propuestas.export_word', [
            'propuesta' => $propuesta,
        ])->render();

        return response($html)
            ->header('Content-Type', 'application/msword')
            ->header('Content-Disposition', 'attachment; filename="propuesta-'.$propuesta->codigo.'.doc"');
    }

    // ======================== Totales (con utilidad) ========================

    protected function recalcTotals(LicitacionPropuesta $propuesta): void
    {
        $itemsBuilder = $propuesta->items();
        $itemsTable   = (new LicitacionPropuestaItem())->getTable();
        $propTable    = $propuesta->getTable();

        $hasUtilidadMonto   = Schema::hasColumn($itemsTable, 'utilidad_monto');
        $hasSubtotalConUtil = Schema::hasColumn($itemsTable, 'subtotal_con_utilidad');

        $subtotalBase = (float) $itemsBuilder->sum('subtotal');
        $utilidadTotal = $hasUtilidadMonto
            ? (float) $propuesta->items()->sum('utilidad_monto')
            : 0.0;

        $subtotalConUtil = $hasSubtotalConUtil
            ? (float) $propuesta->items()->sum('subtotal_con_utilidad')
            : ($subtotalBase + $utilidadTotal);

        if (Schema::hasColumn($propTable, 'subtotal_base')) {
            $propuesta->subtotal_base = $subtotalBase;
        }
        if (Schema::hasColumn($propTable, 'utilidad_total')) {
            $propuesta->utilidad_total = $utilidadTotal;
        }

        $ivaRate = (float) (config('app.iva_rate', 0.16));

        $propuesta->subtotal = $subtotalConUtil;
        $propuesta->iva      = $subtotalConUtil * $ivaRate;
        $propuesta->total    = $propuesta->subtotal + $propuesta->iva;
        $propuesta->save();
    }

    protected function generarCodigo(): string
    {
        $nextId = (LicitacionPropuesta::max('id') ?? 0) + 1;
        return 'PRO-' . now()->format('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    // ======================== SMART MATCHING ========================

    protected function findCandidatesSmart(string $text, int $limit = 5): Collection
    {
        $limit = max(1, min(10, $limit));
        $raw   = trim($text);
        $norm  = $this->norm($raw);
        if ($norm === '') return collect();

        $cacheKey = 'lp_matchplan:' . md5($norm);
        $plan = Cache::remember($cacheKey, now()->addDays(7), function () use ($raw, $norm) {
            $codes = $this->extractCodes($raw);
            $ai = $this->aiBuildSearchPlan($raw);

            return [
                'codes'     => $codes,
                'type'      => $ai['type'] ?? null,
                'keywords'  => $ai['keywords'] ?? [],
                'negatives' => $ai['negatives'] ?? [],
                'brand'     => $ai['brand'] ?? null,
                'notes'     => $ai['notes'] ?? null,
            ];
        });

        $codes     = $plan['codes'] ?? [];
        $type      = $plan['type'] ?? null;
        $keywords  = array_values(array_unique(array_filter($plan['keywords'] ?? [])));
        $negatives = array_values(array_unique(array_filter($plan['negatives'] ?? [])));
        $brandHint = $plan['brand'] ?? null;

        // 0) códigos (SKU)
        if (!empty($codes)) {
            $qCode = Product::query()
                ->select(['id','sku','name','brand','unit','price'])
                ->where('active', true)
                ->where(function ($qq) use ($codes) {
                    foreach ($codes as $c) {
                        $qq->orWhere('sku', $c)
                           ->orWhere('sku', 'like', "%{$c}%");
                    }
                })
                ->limit($limit)
                ->get();

            if ($qCode->isNotEmpty()) return $qCode;
        }

        $q = Product::query()
            ->select(['id','sku','name','brand','unit','price','description','tags'])
            ->where('active', true);

        if ($type) {
            $q->where(function ($qq) use ($type) {
                $qq->where('name', 'like', "%{$type}%")
                   ->orWhere('tags', 'like', "%{$type}%")
                   ->orWhere('description', 'like', "%{$type}%");
            });
        }

        if ($brandHint) {
            $q->where(function ($qq) use ($brandHint) {
                $qq->where('brand', 'like', "%{$brandHint}%")
                   ->orWhere('name', 'like', "%{$brandHint}%");
            });
        }

        if (!empty($keywords)) {
            $q->where(function ($qq) use ($keywords) {
                foreach (array_slice($keywords, 0, 10) as $w) {
                    $qq->orWhere('name', 'like', "%{$w}%")
                       ->orWhere('sku', 'like', "%{$w}%")
                       ->orWhere('tags', 'like', "%{$w}%")
                       ->orWhere('description', 'like', "%{$w}%")
                       ->orWhere('brand', 'like', "%{$w}%");
                }
            });
        }

        $scoreSql = [];
        $bind = [];

        if ($type) {
            $scoreSql[] = "CASE WHEN name LIKE ? THEN 120 ELSE 0 END"; $bind[] = "%{$type}%";
            $scoreSql[] = "CASE WHEN tags LIKE ? THEN 70 ELSE 0 END";  $bind[] = "%{$type}%";
            $scoreSql[] = "CASE WHEN description LIKE ? THEN 30 ELSE 0 END"; $bind[] = "%{$type}%";
        }

        if ($brandHint) {
            $scoreSql[] = "CASE WHEN brand LIKE ? THEN 30 ELSE 0 END"; $bind[] = "%{$brandHint}%";
            $scoreSql[] = "CASE WHEN name LIKE ? THEN 15 ELSE 0 END";  $bind[] = "%{$brandHint}%";
        }

        foreach (array_slice($keywords, 0, 10) as $w) {
            $scoreSql[] = "CASE WHEN name LIKE ? THEN 25 ELSE 0 END"; $bind[] = "%{$w}%";
            $scoreSql[] = "CASE WHEN sku LIKE ? THEN 18 ELSE 0 END";  $bind[] = "%{$w}%";
            $scoreSql[] = "CASE WHEN tags LIKE ? THEN 12 ELSE 0 END"; $bind[] = "%{$w}%";
            $scoreSql[] = "CASE WHEN description LIKE ? THEN 6 ELSE 0 END"; $bind[] = "%{$w}%";
        }

        foreach (array_slice($negatives, 0, 10) as $bad) {
            $scoreSql[] = "CASE WHEN name LIKE ? OR tags LIKE ? OR description LIKE ? THEN -250 ELSE 0 END";
            $bind[] = "%{$bad}%"; $bind[] = "%{$bad}%"; $bind[] = "%{$bad}%";
        }

        // ✅ FIX (solo esto): evita que entre "0" / valores raros y evita ORDER BY 0
        $scoreSql = array_values(array_filter($scoreSql, fn ($x) => is_string($x) && trim($x) !== ''));
        $scoreExpr = '(' . implode(' + ', $scoreSql ?: ['1']) . ')';
        $q->orderByRaw($scoreExpr . ' DESC', $bind);

        $cands = $q->limit(max($limit * 8, 40))->get()->take($limit)->values();

        if ($cands->isEmpty() && !empty($keywords)) {
            $q2 = Product::query()
                ->select(['id','sku','name','brand','unit','price'])
                ->where('active', true)
                ->where(function ($qq) use ($keywords) {
                    foreach (array_slice($keywords, 0, 8) as $w) {
                        $qq->orWhere('name', 'like', "%{$w}%")
                           ->orWhere('sku', 'like', "%{$w}%")
                           ->orWhere('tags', 'like', "%{$w}%")
                           ->orWhere('description', 'like', "%{$w}%");
                    }
                })
                ->limit($limit)
                ->get();

            return $q2;
        }

        return $cands;
    }

    protected function aiBuildSearchPlan(string $rawText): array
    {
        $apiKey  = config('services.openai.api_key') ?: config('services.openai.key');
        $baseUrl = rtrim(config('services.openai.base_url', 'https://api.openai.com'), '/');

        $timeout = (int) config('services.openai.timeout', 35);
        $connectTimeout = (int) config('services.openai.connect_timeout', 10);

        $model = config('services.openai.plan_model', config('services.openai.primary', 'gpt-5-2025-08-07'));

        if (!$apiKey) {
            return $this->localPlanFallback($rawText);
        }

        $sys = <<<SYS
Eres un asistente de matching para un catálogo de papelería/oficina.
Tu tarea: entender el "solicitado" y generar un plan de búsqueda en base de datos.

Devuelve SOLO JSON válido con esta forma:
{
  "type": "palabra ancla del tipo de producto (ej. perforadora, engrapadora, toner...) o null",
  "brand": "marca si aparece o null",
  "keywords": ["palabras útiles para buscar (sin genéricos)"],
  "negatives": ["palabras que indicarían otro tipo (para penalizar)"],
  "notes": "muy corto"
}

Reglas:
- keywords: máximo 10, sin palabras genéricas (acero, base, garantía, etc.)
- type: 1 palabra preferible (en singular)
- negatives: máximo 8
- No inventes marca.
SYS;

        try {
            $resp = Http::withToken($apiKey)
                ->timeout($timeout)
                ->connectTimeout($connectTimeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($baseUrl . '/v1/responses', [
                    'model' => $model,
                    'instructions' => $sys,
                    'input' => [[
                        'role' => 'user',
                        'content' => [
                            ['type' => 'input_text', 'text' => $rawText],
                        ],
                    ]],
                    'max_output_tokens' => 600,
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'MatchPlan',
                            'strict' => true,
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'type' => ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
                                    'brand' => ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
                                    'keywords' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    'negatives' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    'notes' => ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
                                ],
                                'required' => ['type','brand','keywords','negatives','notes'],
                                'additionalProperties' => false,
                            ],
                        ],
                    ],
                ]);

            if (!$resp->ok()) {
                Log::warning('aiBuildSearchPlan falló', ['status' => $resp->status(), 'body' => $resp->body()]);
                return $this->localPlanFallback($rawText);
            }

            $raw = $this->extractOutputText($resp->json());
            $raw = $this->cleanupJsonText($raw);
            $data = $this->decodeJsonLenient($raw);

            if (!is_array($data)) return $this->localPlanFallback($rawText);

            $data['type'] = isset($data['type']) && is_string($data['type']) ? trim($data['type']) : null;
            $data['brand'] = isset($data['brand']) && is_string($data['brand']) ? trim($data['brand']) : null;

            $data['keywords'] = is_array($data['keywords'] ?? null) ? array_values(array_filter(array_map('strval', $data['keywords']))) : [];
            $data['negatives'] = is_array($data['negatives'] ?? null) ? array_values(array_filter(array_map('strval', $data['negatives']))) : [];

            return $data;
        } catch (\Throwable $e) {
            Log::warning('aiBuildSearchPlan exception', ['e' => $e->getMessage()]);
            return $this->localPlanFallback($rawText);
        }
    }

    protected function localPlanFallback(string $text): array
    {
        $norm = $this->norm($text);
        $kw = $this->basicKeywords($norm);

        $type = null;
        foreach ([
            'perforadora','engrapadora','grapadora','tijeras','cutter','marcador','resaltador',
            'toner','cartucho','papel','etiquetas','carpeta','folder','archivero',
            'silicon','silicón','pegamento'
        ] as $k) {
            if (str_contains($norm, $k)) { $type = $k; break; }
        }

        $neg = [];
        if ($type === 'perforadora') $neg = ['silicon','silicón','pegamento','adhesivo','corrector','cinta'];
        if ($type === 'silicón' || $type === 'silicon') $neg = ['perforadora','engrapadora','grapadora','tijeras'];

        return [
            'type' => $type,
            'brand' => null,
            'keywords' => $kw,
            'negatives' => $neg,
            'notes' => 'fallback',
        ];
    }

    protected function extractCodes(string $text): array
    {
        $codes = [];

        if (preg_match_all('/\b\d{8,14}\b/', $text, $m)) {
            foreach ($m[0] as $c) $codes[] = $c;
        }

        if (preg_match_all('/\b[A-Z]{1,4}-[A-Z0-9]{1,6}(?:-[A-Z0-9]{1,6})+\b/i', $text, $m2)) {
            foreach ($m2[0] as $c) $codes[] = $c;
        }

        $codes = array_values(array_unique($codes));
        return array_slice($codes, 0, 6);
    }

    protected function basicKeywords(string $norm): array
    {
        $parts = preg_split('/\s+/', trim($norm)) ?: [];

        $stop = [
            'de','del','la','las','el','los','y',' o','u','en','con','para','por','a','al','un','una','unos','unas',
            'tipo','color','medida','medidas','aprox','aproximadas','fabricada','fabricado','incluye','incluido',
            'capacidad','hasta','garantia','garantía','meses','años','ano','debera','deberá','cumplir','norma',
            'cm','mm','pulgadas','pulgada','mts','metro','metros','volt','volts','hertz','hz','amperes','cc',
            'acero','base','integrada','integrado','antiderrapante','regleta','separacion','separación',
        ];

        $keep = [];
        foreach ($parts as $w) {
            $w = trim($w);
            if ($w === '') continue;
            if (mb_strlen($w) < 4) continue;
            if (in_array($w, $stop, true)) continue;
            $keep[] = $w;
        }

        $keep = array_values(array_unique($keep));
        return array_slice($keep, 0, 10);
    }

    protected function norm(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $s = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $s);
        $s = preg_replace('/\s+/u', ' ', $s);
        return trim($s);
    }

    protected function extractOutputText(array $json): string
    {
        if (isset($json['output_text']) && is_string($json['output_text']) && trim($json['output_text']) !== '') {
            return trim($json['output_text']);
        }

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

    protected function cleanupJsonText(string $raw): string
    {
        $raw = trim($raw);

        $raw = preg_replace('/^```(?:json)?/i', '', $raw);
        $raw = preg_replace('/```$/', '', $raw);
        $raw = trim($raw);

        $firstObj = strpos($raw, '{');
        $firstArr = strpos($raw, '[');

        $start = null;
        if ($firstObj !== false && $firstArr !== false) $start = min($firstObj, $firstArr);
        elseif ($firstObj !== false) $start = $firstObj;
        elseif ($firstArr !== false) $start = $firstArr;

        if ($start !== null) $raw = substr($raw, $start);

        $lastObj = strrpos($raw, '}');
        $lastArr = strrpos($raw, ']');

        $end = null;
        if ($lastObj !== false && $lastArr !== false) $end = max($lastObj, $lastArr);
        elseif ($lastObj !== false) $end = $lastObj;
        elseif ($lastArr !== false) $end = $lastArr;

        if ($end !== null) $raw = substr($raw, 0, $end + 1);

        return trim($raw);
    }

    protected function decodeJsonLenient(string $raw)
    {
        $raw = trim($raw);
        if ($raw === '') return null;

        $data = json_decode($raw, true);
        if (is_array($data)) return $data;

        if (str_starts_with($raw, '"') && str_ends_with($raw, '"')) {
            $unquoted = json_decode($raw, true);
            if (is_string($unquoted)) {
                $unquoted = $this->cleanupJsonText($unquoted);
                $data2 = json_decode($unquoted, true);
                if (is_array($data2)) return $data2;

                $data3 = json_decode(stripslashes($unquoted), true);
                if (is_array($data3)) return $data3;
            }
        }

        $raw2 = stripslashes($raw);
        $data4 = json_decode($raw2, true);
        if (is_array($data4)) return $data4;

        return null;
    }

    // ======================== AJAX buscador de productos ========================

    public function searchProducts(Request $request)
    {
        $q     = trim((string) $request->get('q', ''));
        $page  = max(1, (int) $request->get('page', 1));
        $limit = min(30, max(10, (int) $request->get('limit', 20)));

        $query = Product::query()
            ->select(['id','sku','name','brand','unit','price','cost'])
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
                    'cost'  => (float) ($p->cost ?? 0),
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

    // ======================== AJAX aplicar producto ========================

    public function applyProductAjax(Request $request, LicitacionPropuestaItem $item)
    {
        $data = $request->validate([
            'product_id'      => ['required', 'integer', 'exists:products,id'],
            'precio_unitario' => ['nullable', 'numeric', 'min:0'],
            'motivo'          => ['nullable', 'string', 'max:255'],
            // 'utilidad_pct'   => ['nullable', 'numeric', 'min:0'], // opcional si luego quieres usarla
        ]);

        $productId = (int) $data['product_id'];

        $item->loadMissing(['propuesta', 'product']);

        $product = Product::select(['id','sku','name','brand','unit','price','cost'])
            ->where('active', true)
            ->findOrFail($productId);

        $item->product_id = $product->id;

        if (array_key_exists('precio_unitario', $data) && $data['precio_unitario'] !== null) {
            $item->precio_unitario = (float) $data['precio_unitario'];
        } else {
            $item->precio_unitario = (float) ($product->price ?? 0);
        }

        if (Schema::hasColumn($item->getTable(), 'costo')) {
            $item->costo = (float) ($product->cost ?? 0);
        }
        if (Schema::hasColumn($item->getTable(), 'costo_jureto')) {
            $item->costo_jureto = (float) ($product->cost ?? 0);
        }

        $qty = (float) ($item->cantidad_propuesta ?? 0);
        $pu  = (float) ($item->precio_unitario ?? 0);
        $subtotalBase = $qty * $pu;
        $item->subtotal = $subtotalBase;

        $pct = Schema::hasColumn($item->getTable(), 'utilidad_pct')
            ? (float) ($item->utilidad_pct ?? 0)
            : 0.0;

        $util = $subtotalBase * ($pct / 100.0);

        if (Schema::hasColumn($item->getTable(), 'utilidad_monto')) {
            $item->utilidad_monto = $util;
        }
        if (Schema::hasColumn($item->getTable(), 'subtotal_con_utilidad')) {
            $item->subtotal_con_utilidad = $subtotalBase + $util;
        }

        $item->motivo_seleccion = $data['motivo']
            ?? ($item->motivo_seleccion ?: 'Seleccionado manualmente');

        $item->save();

        $this->recalcTotals($item->propuesta);
        $propuesta = $item->propuesta->fresh();

        $itemsBuilder = $propuesta->items();
        $subtotalBaseTotal = (float) $itemsBuilder->sum('subtotal');
        $utilidadTotal = Schema::hasColumn((new LicitacionPropuestaItem)->getTable(), 'utilidad_monto')
            ? (float) $propuesta->items()->sum('utilidad_monto')
            : 0.0;

        return response()->json([
            'ok' => true,
            'row' => [
                'item_id'               => $item->id,
                'product_id'            => $product->id,
                'sku'                   => $product->sku,
                'name'                  => $product->name,
                'brand'                 => $product->brand,
                'unit'                  => $product->unit,
                'cost'                  => (float) ($product->cost ?? 0),
                'precio_unitario'       => (float) ($item->precio_unitario ?? 0),
                'subtotal'              => (float) ($item->subtotal ?? 0),
                'utilidad_pct'          => (float) ($item->utilidad_pct ?? 0),
                'utilidad_monto'        => (float) ($item->utilidad_monto ?? 0),
                'subtotal_con_utilidad' => (float) ($item->subtotal_con_utilidad ?? (($item->subtotal ?? 0) + ($item->utilidad_monto ?? 0))),
            ],
            'totals' => [
                'subtotal_base'         => (float) ($propuesta->subtotal_base ?? $subtotalBaseTotal),
                'utilidad'              => (float) ($propuesta->utilidad_total ?? $utilidadTotal),
                'subtotal_con_utilidad' => (float) $propuesta->subtotal,
                'iva'                   => (float) $propuesta->iva,
                'total'                 => (float) $propuesta->total,
            ],
        ]);
    }

    public function merge(Request $request, LicitacionPropuesta $licitacionPropuesta)
    {
        // Simplemente reusa la lógica de mergeGlobal
        return $this->mergeGlobal($request, $licitacionPropuesta);
    }
}
