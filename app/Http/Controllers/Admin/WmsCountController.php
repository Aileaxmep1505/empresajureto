<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WmsCount;
use App\Models\WmsCountLine;
use App\Services\WmsStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Conteos de inventario.
 *
 * Se arma una lista de (ubicación, producto) con lo que el sistema cree que
 * hay; el almacenista captura lo que encuentra (a ciegas si se quiere) y al
 * cerrar se ajustan las diferencias, tanto en la ubicación como en el stock global.
 */
class WmsCountController extends Controller
{
    public function index()
    {
        return view('admin.wms.ops.counts', [
            'conteos' => WmsCount::with(['warehouse', 'assignedUser'])
                ->withCount(['lines', 'lines as contadas_count' => fn ($q) => $q->whereNotNull('counted_qty')])
                ->latest('id')->paginate(15),
            'bodegas' => Warehouse::orderBy('name')->get(['id', 'name', 'code']),
            'ubicaciones' => Location::orderBy('code')->get(['id', 'warehouse_id', 'code', 'name']),
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
            'exactitud' => $this->exactitudGlobal(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'scope' => ['required', 'in:' . implode(',', array_keys(WmsCount::SCOPES))],
            'location_ids' => ['nullable', 'array'],
            'location_ids.*' => ['integer', 'exists:locations,id'],
            'sample_size' => ['nullable', 'integer', 'min:1', 'max:500'],
            'blind' => ['nullable', 'boolean'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $bodegaLocs = Location::where('warehouse_id', $data['warehouse_id'])->pluck('id');
        $filas = Inventory::query()->whereIn('location_id', $bodegaLocs);

        switch ($data['scope']) {
            case 'ubicaciones':
                $ids = array_values(array_intersect($data['location_ids'] ?? [], $bodegaLocs->all()));
                if (! $ids) {
                    return back()->withInput()->with('error', 'Elige al menos una ubicación de esa bodega.');
                }
                $filas->whereIn('location_id', $ids);
                break;

            case 'criticos':
                $filas->whereIn('catalog_item_id', CatalogItem::whereNotNull('stock_min')->whereColumn('stock', '<=', 'stock_min')->pluck('id'));
                break;

            case 'aleatorio':
                $filas->where('qty', '>', 0)->inRandomOrder()->limit((int) ($data['sample_size'] ?? 10));
                break;

            case 'todo':
                $filas->where('qty', '>', 0);
                break;
        }

        $filas = $filas->get();

        if ($filas->isEmpty() && $data['scope'] !== 'ubicaciones') {
            return back()->withInput()->with('error', 'No hay existencias que contar con ese criterio.');
        }

        $conteo = DB::transaction(function () use ($data, $filas, $request) {
            $conteo = WmsCount::create([
                'folio' => WmsCount::nextFolio(),
                'warehouse_id' => $data['warehouse_id'],
                'scope' => $data['scope'],
                'status' => 'abierto',
                'blind' => (bool) ($data['blind'] ?? false),
                'assigned_user_id' => $data['assigned_user_id'] ?? null,
                'created_by' => $request->user()->id,
                'notes' => $data['notes'] ?? null,
                'meta' => ['location_ids' => $data['location_ids'] ?? []],
            ]);

            foreach ($filas as $fila) {
                WmsCountLine::create([
                    'count_id' => $conteo->id,
                    'location_id' => $fila->location_id,
                    'catalog_item_id' => $fila->catalog_item_id,
                    'expected_qty' => (int) $fila->qty,
                ]);
            }

            return $conteo;
        });

        return redirect()->route('admin.wms.counts.show', $conteo)->with('ok', "Conteo {$conteo->folio} creado con {$filas->count()} renglones.");
    }

    public function show(WmsCount $count)
    {
        $count->load(['warehouse', 'assignedUser', 'creator']);

        $lineas = $count->lines()->with(['item', 'location', 'counter'])->get()
            ->sortBy(fn ($l) => ($l->location->code ?? '') . '|' . ($l->item->name ?? ''))->values();

        return view('admin.wms.ops.count_show', [
            'conteo' => $count,
            'lineas' => $lineas,
            'resumen' => $this->resumen($lineas, $count->blind && $count->isOpen()),
            'ubicaciones' => Location::where('warehouse_id', $count->warehouse_id)->orderBy('code')->get(['id', 'code']),
        ]);
    }

    /** Guarda lo contado en un renglón (AJAX). */
    public function saveLine(Request $request, WmsCount $count, WmsCountLine $line)
    {
        abort_unless($line->count_id === $count->id, 404);

        if (! $count->isOpen()) {
            return response()->json(['ok' => false, 'error' => 'El conteo ya está cerrado.'], 422);
        }

        $data = $request->validate([
            'counted_qty' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'note' => ['nullable', 'string', 'max:300'],
        ]);

        $line->counted_qty = $data['counted_qty'] ?? null;
        $line->note = $data['note'] ?? $line->note;
        $line->counted_by = $line->counted_qty === null ? null : $request->user()->id;
        $line->counted_at = $line->counted_qty === null ? null : now();
        $line->save();

        $lineas = $count->lines()->get();

        return response()->json([
            'ok' => true,
            // A ciegas no se devuelve nada que delate lo esperado.
            'variance' => $count->blind ? null : $line->variance,
            'expected' => $count->blind ? null : $line->expected_qty,
            'resumen' => $this->resumen($lineas, $count->blind),
        ]);
    }

    /** Agrega un producto que apareció en una ubicación y el sistema no esperaba. */
    public function addLine(Request $request, WmsCount $count)
    {
        if (! $count->isOpen()) {
            return back()->with('error', 'El conteo ya está cerrado.');
        }

        $data = $request->validate([
            'location_id' => ['required', 'exists:locations,id'],
            'catalog_item_id' => ['required', 'exists:catalog_items,id'],
            'counted_qty' => ['required', 'integer', 'min:0', 'max:10000000'],
        ]);

        $esperado = (int) Inventory::where('location_id', $data['location_id'])
            ->where('catalog_item_id', $data['catalog_item_id'])->value('qty');

        WmsCountLine::updateOrCreate(
            ['count_id' => $count->id, 'location_id' => $data['location_id'], 'catalog_item_id' => $data['catalog_item_id']],
            ['expected_qty' => $esperado, 'counted_qty' => $data['counted_qty'], 'counted_by' => $request->user()->id, 'counted_at' => now(), 'note' => 'Agregado durante el conteo']
        );

        return back()->with('ok', 'Renglón agregado al conteo.');
    }

    /** Búsqueda de productos para "agregar encontrado" (JSON). */
    public function items(Request $request)
    {
        $s = trim((string) $request->get('q', ''));

        if (mb_strlen($s) < 2) {
            return response()->json(['items' => []]);
        }

        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $s) . '%';

        return response()->json([
            'items' => CatalogItem::where(fn ($q) => $q->where('name', 'like', $like)->orWhere('sku', 'like', $like))
                ->orderBy('name')->limit(10)->get(['id', 'name', 'sku']),
        ]);
    }

    /** Cierra el conteo y aplica los ajustes de las diferencias. */
    public function close(Request $request, WmsCount $count, WmsStockService $stock)
    {
        if (! $count->isOpen()) {
            return back()->with('error', 'El conteo ya estaba cerrado.');
        }

        $ajustados = 0;
        $unidades = 0;

        DB::transaction(function () use ($count, $stock, $request, &$ajustados, &$unidades) {
            foreach ($count->lines()->whereNotNull('counted_qty')->where('adjusted', false)->get() as $linea) {
                if ($linea->variance === 0) {
                    continue;
                }

                $delta = $stock->setLocationQty(
                    $linea->catalog_item_id, $linea->location_id, (int) $linea->counted_qty,
                    $request->user()->id, 'count_adjust', 'Conteo ' . $count->folio,
                    ['count_id' => $count->id, 'line_id' => $linea->id, 'expected' => $linea->expected_qty]
                );

                $linea->update(['adjusted' => true]);
                $ajustados++;
                $unidades += abs($delta);
            }

            $count->update(['status' => 'cerrado', 'closed_by' => $request->user()->id, 'closed_at' => now()]);
        });

        return redirect()->route('admin.wms.counts.show', $count)
            ->with('ok', "Conteo cerrado. Se ajustaron {$ajustados} renglones ({$unidades} unidades de diferencia).");
    }

    public function cancel(WmsCount $count)
    {
        if ($count->isOpen()) {
            $count->update(['status' => 'cancelado']);
        }

        return redirect()->route('admin.wms.counts.index')->with('ok', 'Conteo cancelado. No se ajustó nada.');
    }

    // ===================== Apoyo =====================

    /** Con $oculto (conteo a ciegas abierto) solo se informa el avance, no las diferencias. */
    private function resumen($lineas, bool $oculto = false): array
    {
        $contadas = $lineas->filter(fn ($l) => $l->counted_qty !== null);
        $conDif = $contadas->filter(fn ($l) => $l->variance !== 0);

        if ($oculto) {
            return [
                'total' => $lineas->count(),
                'contadas' => $contadas->count(),
                'avance' => $lineas->count() ? (int) round($contadas->count() / $lineas->count() * 100) : 0,
                'con_diferencia' => null, 'sobrantes' => null, 'faltantes' => null, 'exactitud' => null,
            ];
        }

        return [
            'total' => $lineas->count(),
            'contadas' => $contadas->count(),
            'avance' => $lineas->count() ? (int) round($contadas->count() / $lineas->count() * 100) : 0,
            'con_diferencia' => $conDif->count(),
            'sobrantes' => (int) $conDif->filter(fn ($l) => $l->variance > 0)->sum('variance'),
            'faltantes' => (int) abs($conDif->filter(fn ($l) => $l->variance < 0)->sum('variance')),
            'exactitud' => $contadas->count() ? (int) round(($contadas->count() - $conDif->count()) / $contadas->count() * 100) : null,
        ];
    }

    /** Exactitud de inventario de los conteos cerrados en los últimos 90 días. */
    private function exactitudGlobal(): ?int
    {
        $lineas = WmsCountLine::whereNotNull('counted_qty')
            ->whereHas('count', fn ($q) => $q->where('status', 'cerrado')->where('closed_at', '>=', now()->subDays(90)))
            ->get(['expected_qty', 'counted_qty']);

        if ($lineas->isEmpty()) {
            return null;
        }

        $bien = $lineas->filter(fn ($l) => $l->counted_qty === $l->expected_qty)->count();

        return (int) round($bien / $lineas->count() * 100);
    }
}
