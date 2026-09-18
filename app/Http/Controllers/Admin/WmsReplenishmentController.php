<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\User;
use App\Models\WmsReplenishmentTask;
use App\Services\WmsDemandService;
use App\Services\WmsStockService;
use Illuminate\Http\Request;

/**
 * Reabastecimiento.
 *
 * Revisa la ubicación de picking (primary_location) de cada producto y, si
 * quedó en o por debajo de su mínimo, propone bajar mercancía de las
 * ubicaciones de reserva. Lo que ya no tiene reserva sale en "Comprar".
 */
class WmsReplenishmentController extends Controller
{
    public function index(WmsDemandService $demanda)
    {
        $pendientes = $demanda->unidadesPendientes();

        return view('admin.wms.ops.replenishment', [
            'sugerencias' => $this->sugerencias($pendientes),
            'compras' => $this->compras($pendientes),
            'tareas' => WmsReplenishmentTask::with(['item', 'fromLocation', 'toLocation', 'assignedUser'])
                ->where('status', 'pendiente')
                ->orderByRaw("FIELD(priority, 'alta', 'media', 'normal')")
                ->orderBy('id')
                ->get(),
            'hechas' => WmsReplenishmentTask::with(['item', 'fromLocation', 'toLocation', 'completedBy'])
                ->where('status', 'hecha')
                ->latest('completed_at')
                ->limit(12)
                ->get(),
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Convierte en tareas las sugerencias marcadas (o todas). */
    public function generate(Request $request, WmsDemandService $demanda)
    {
        $data = $request->validate([
            'sel' => ['nullable', 'array'],
            'sel.*' => ['string'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $marcadas = array_flip($data['sel'] ?? []);
        $creadas = 0;

        foreach ($this->sugerencias($demanda->unidadesPendientes()) as $s) {
            foreach ($s['fuentes'] as $f) {
                $clave = $s['item']->id . '-' . $f['location']->id . '-' . $s['to']->id;

                if ($marcadas && ! isset($marcadas[$clave])) {
                    continue;
                }

                WmsReplenishmentTask::create([
                    'warehouse_id' => $s['to']->warehouse_id,
                    'catalog_item_id' => $s['item']->id,
                    'from_location_id' => $f['location']->id,
                    'to_location_id' => $s['to']->id,
                    'qty_suggested' => $f['mover'],
                    'priority' => $s['priority'],
                    'status' => 'pendiente',
                    'assigned_user_id' => $data['assigned_user_id'] ?? null,
                    'created_by' => $request->user()->id,
                    'meta' => ['pick_qty' => $s['pickQty'], 'min' => $s['min'], 'max' => $s['max'], 'demanda' => $s['demanda']],
                ]);

                $creadas++;
            }
        }

        return back()->with($creadas ? 'ok' : 'error', $creadas ? "Se generaron {$creadas} tareas de reabastecimiento." : 'No había sugerencias para generar.');
    }

    /** Ejecuta la tarea: mueve el stock de reserva a picking. */
    public function complete(Request $request, WmsReplenishmentTask $task, WmsStockService $stock)
    {
        $data = $request->validate(['qty' => ['required', 'integer', 'min:1', 'max:1000000']]);

        if ($task->status !== 'pendiente') {
            return back()->with('error', 'Esa tarea ya no está pendiente.');
        }

        if (! $task->from_location_id || ! $task->to_location_id) {
            return back()->with('error', 'La tarea perdió su ubicación de origen o destino.');
        }

        try {
            $stock->transfer(
                $task->catalog_item_id, $task->from_location_id, $task->to_location_id, (int) $data['qty'],
                $request->user()->id, 'replenishment', 'Reabastecimiento #' . $task->id, ['task_id' => $task->id]
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $task->update([
            'qty_moved' => (int) $data['qty'],
            'status' => 'hecha',
            'completed_by' => $request->user()->id,
            'completed_at' => now(),
        ]);

        return back()->with('ok', 'Reabastecimiento aplicado: ' . (int) $data['qty'] . ' unidades movidas.');
    }

    public function cancel(WmsReplenishmentTask $task)
    {
        if ($task->status === 'pendiente') {
            $task->update(['status' => 'cancelada']);
        }

        return back()->with('ok', 'Tarea cancelada.');
    }

    // ===================== Cálculo =====================

    /**
     * @param array<int,int> $pendientes unidades pedidas por olas abiertas, por producto
     * @return array<int, array<string, mixed>>
     */
    private function sugerencias(array $pendientes): array
    {
        $items = CatalogItem::query()
            ->whereNotNull('primary_location_id')
            ->with('primaryLocation')
            ->get();

        if ($items->isEmpty()) {
            return [];
        }

        $filas = Inventory::whereIn('catalog_item_id', $items->pluck('id'))->get()->groupBy('catalog_item_id');
        $ubicaciones = Location::whereIn('id', $filas->flatten()->pluck('location_id')->unique())->get()->keyBy('id');

        // Lo que ya tiene tarea abierta no se vuelve a sugerir.
        $conTarea = WmsReplenishmentTask::where('status', 'pendiente')->get()
            ->map(fn ($t) => $t->catalog_item_id . '-' . $t->to_location_id)->flip();

        $salida = [];

        foreach ($items as $item) {
            $to = $item->primaryLocation;

            if (! $to || isset($conTarea[$item->id . '-' . $to->id])) {
                continue;
            }

            $delItem = $filas->get($item->id, collect());
            $enPicking = $delItem->firstWhere('location_id', $to->id);

            $pickQty = (int) ($enPicking->qty ?? 0);
            $min = (int) (($enPicking->min_qty ?? 0) ?: ($item->stock_min ?? 0));
            $demanda = (int) ($pendientes[$item->id] ?? 0);

            // Se repone si cayó al mínimo, o si las olas abiertas piden más de lo que hay en picking.
            if ($min <= 0 && $demanda <= $pickQty) {
                continue;
            }
            if ($pickQty > $min && $demanda <= $pickQty) {
                continue;
            }

            $max = (int) ($item->stock_max ?? 0);
            if ($max <= $min) {
                $max = max($min * 2, $min + 1);
            }

            $need = max($max - $pickQty, $demanda - $pickQty);
            if ($need <= 0) {
                continue;
            }

            // Reservas: otras ubicaciones de la misma bodega con existencia.
            $fuentes = [];
            $falta = $need;

            foreach ($delItem->where('location_id', '!=', $to->id)->where('qty', '>', 0)->sortByDesc('qty') as $fila) {
                $loc = $ubicaciones->get($fila->location_id);

                if (! $loc || $loc->warehouse_id !== $to->warehouse_id || $falta <= 0) {
                    continue;
                }

                $mover = min($falta, (int) $fila->qty);
                $fuentes[] = ['location' => $loc, 'qty' => (int) $fila->qty, 'mover' => $mover];
                $falta -= $mover;
            }

            if (! $fuentes) {
                continue; // sin reserva: sale en "Comprar"
            }

            $salida[] = [
                'item' => $item,
                'to' => $to,
                'pickQty' => $pickQty,
                'min' => $min,
                'max' => $max,
                'need' => $need,
                'cubierto' => $need - $falta,
                'demanda' => $demanda,
                'priority' => $pickQty <= 0 || $demanda > $pickQty ? 'alta' : ($pickQty <= intdiv($min, 2) ? 'media' : 'normal'),
                'fuentes' => $fuentes,
            ];
        }

        usort($salida, fn ($a, $b) => array_search($a['priority'], ['alta', 'media', 'normal']) <=> array_search($b['priority'], ['alta', 'media', 'normal']));

        return $salida;
    }

    /** Productos cuyo stock global ya está en o bajo su mínimo: hay que comprar. */
    private function compras(array $pendientes): array
    {
        return CatalogItem::query()
            ->whereNotNull('stock_min')->where('stock_min', '>', 0)
            ->whereColumn('stock', '<=', 'stock_min')
            ->where('is_sample', false)
            ->orderBy('stock')
            ->limit(100)
            ->get()
            ->map(function (CatalogItem $item) use ($pendientes) {
                $max = (int) ($item->stock_max ?? 0);
                $objetivo = $max > (int) $item->stock_min ? $max : (int) $item->stock_min * 2;
                $demanda = (int) ($pendientes[$item->id] ?? 0);

                return [
                    'item' => $item,
                    'stock' => (int) $item->stock,
                    'min' => (int) $item->stock_min,
                    'objetivo' => $objetivo,
                    'demanda' => $demanda,
                    'comprar' => max(0, $objetivo - (int) $item->stock) + max(0, $demanda - (int) $item->stock),
                ];
            })
            ->all();
    }
}
