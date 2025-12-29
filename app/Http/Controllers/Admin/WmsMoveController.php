<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Warehouse;
use App\Models\WmsMovement;
use App\Models\WmsMovementLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WmsMoveController extends Controller
{
    public function view(Request $r)
    {
        $warehouses = Warehouse::query()->orderBy('id')->get();
        $warehouseId = (int)($r->get('warehouse_id') ?? ($warehouses->first()->id ?? 1));
        return view('admin.wms.move', compact('warehouses', 'warehouseId'));
    }

    public function movementsView(Request $r)
    {
        $warehouses = Warehouse::query()->orderBy('id')->get();
        $warehouseId = (int)($r->get('warehouse_id') ?? ($warehouses->first()->id ?? 1));
        return view('admin.wms.movements', compact('warehouses', 'warehouseId'));
    }

    public function products(Request $r)
    {
        $q = trim((string)$r->get('q', ''));
        $warehouseId = (int)($r->get('warehouse_id', 1));
        $limit = min(30, max(5, (int)$r->get('limit', 12)));

        if ($q === '') return response()->json(['ok'=>true, 'items'=>[]]);

        $items = CatalogItem::query()
            ->select(['id','name','sku','meli_gtin','stock','primary_location_id'])
            ->where(function($qq) use ($q){
                $qq->where('name', 'like', "%{$q}%")
                  ->orWhere('sku', 'like', "%{$q}%")
                  ->orWhere('meli_gtin', 'like', "%{$q}%")
                  ->orWhere('id', $q);
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();

        $locIds = $items->pluck('primary_location_id')->filter()->unique()->values()->all();
        $primaryLocMap = Location::query()
            ->whereIn('id', $locIds)
            ->where('warehouse_id', $warehouseId)
            ->get(['id','code'])
            ->keyBy('id');

        $ids = $items->pluck('id')->all();
        $bestLocByItem = Inventory::query()
            ->selectRaw('catalog_item_id, location_id, SUM(qty) as qty_sum')
            ->whereIn('catalog_item_id', $ids)
            ->whereIn('location_id', Location::query()->where('warehouse_id', $warehouseId)->pluck('id'))
            ->groupBy('catalog_item_id','location_id')
            ->get()
            ->groupBy('catalog_item_id')
            ->map(fn($rows)=> $rows->sortByDesc('qty_sum')->first());

        $out = $items->map(function($it) use ($primaryLocMap, $bestLocByItem){
            $rec = null;

            if ($it->primary_location_id && isset($primaryLocMap[$it->primary_location_id])) {
                $rec = [
                    'location_id' => (int)$it->primary_location_id,
                    'code' => (string)$primaryLocMap[$it->primary_location_id]->code,
                    'why' => 'primary',
                ];
            } else {
                $best = $bestLocByItem[$it->id] ?? null;
                if ($best) {
                    $loc = Location::query()->find($best->location_id);
                    if ($loc) {
                        $rec = [
                            'location_id' => (int)$loc->id,
                            'code' => (string)$loc->code,
                            'why' => 'most_qty',
                        ];
                    }
                }
            }

            return [
                'id' => (int)$it->id,
                'name' => (string)$it->name,
                'sku' => $it->sku,
                'gtin' => $it->meli_gtin,
                'stock' => (int)$it->stock,
                'recommended' => $rec,
            ];
        })->values();

        return response()->json(['ok'=>true, 'items'=>$out]);
    }

    public function commit(Request $r)
    {
        $p = $r->validate([
            'warehouse_id' => ['required','integer','exists:warehouses,id'],
            'type' => ['required','in:in,out'],
            'note' => ['nullable','string','max:500'],

            // ✅ NUEVO: datos para el PDF (nombres para firmas)
            'authorized_name' => ['nullable','string','max:120'],
            'authorized_role' => ['nullable','string','max:120'],
            'delivered_name'  => ['nullable','string','max:120'],
            'received_name'   => ['nullable','string','max:120'],

            'lines' => ['required','array','min:1','max:300'],
            'lines.*.catalog_item_id' => ['required','integer','exists:catalog_items,id'],
            'lines.*.qty' => ['required','integer','min:1','max:1000000'],
            'lines.*.location_id' => ['nullable','integer','exists:locations,id'],
        ]);

        $warehouseId = (int)$p['warehouse_id'];
        $type = $p['type'];
        $lines = $p['lines'];

        $note = $p['note'] ?? null;
        $authorizedName = $p['authorized_name'] ?? null;
        $authorizedRole = $p['authorized_role'] ?? null;
        $deliveredName  = $p['delivered_name'] ?? null;
        $receivedName   = $p['received_name'] ?? null;

        $whLocIds = Location::query()->where('warehouse_id', $warehouseId)->pluck('id')->all();
        $whLocSet = array_flip($whLocIds);

        foreach ($lines as $ln) {
            if (!empty($ln['location_id']) && !isset($whLocSet[(int)$ln['location_id']])) {
                return response()->json(['ok'=>false,'error'=>'Una ubicación no pertenece a esta bodega.'], 422);
            }
        }

        try {
            $result = DB::transaction(function() use (
                $warehouseId, $type, $lines, $note,
                $authorizedName, $authorizedRole, $deliveredName, $receivedName
            ) {

                $movement = WmsMovement::query()->create([
                    'warehouse_id' => $warehouseId,
                    'user_id' => auth()->id(),
                    'type' => $type,
                    'note' => $note,

                    'authorized_name' => $authorizedName,
                    'authorized_role' => $authorizedRole,
                    'delivered_name'  => $deliveredName,
                    'received_name'   => $receivedName,
                ]);

                $itemIds = collect($lines)->pluck('catalog_item_id')->unique()->values()->all();
                $items = CatalogItem::query()->whereIn('id', $itemIds)->lockForUpdate()->get()->keyBy('id');

                // OUT: validar stock global suficiente
                if ($type === 'out') {
                    foreach ($lines as $ln) {
                        $it = $items[(int)$ln['catalog_item_id']];
                        $qty = (int)$ln['qty'];
                        if ((int)$it->stock < $qty) {
                            throw new \RuntimeException("Stock global insuficiente para: {$it->name}. (Hay {$it->stock}, pides {$qty})");
                        }
                    }
                }

                $routePlan = []; // por location

                foreach ($lines as $ln) {
                    $itemId = (int)$ln['catalog_item_id'];
                    $qty = (int)$ln['qty'];
                    $it = $items[$itemId];

                    // resolver ubicación
                    $locationId = (int)($ln['location_id'] ?? 0);

                    if (!$locationId && $it->primary_location_id) {
                        $pl = (int)$it->primary_location_id;
                        if (Location::query()->where('warehouse_id', $warehouseId)->where('id', $pl)->exists()) $locationId = $pl;
                    }

                    if (!$locationId) {
                        $best = Inventory::query()
                            ->selectRaw('location_id, SUM(qty) as qty_sum')
                            ->where('catalog_item_id', $itemId)
                            ->whereIn('location_id', Location::query()->where('warehouse_id', $warehouseId)->pluck('id'))
                            ->groupBy('location_id')
                            ->orderByDesc('qty_sum')
                            ->first();
                        if ($best) $locationId = (int)$best->location_id;
                    }

                    if (!$locationId) {
                        throw new \RuntimeException("No se pudo determinar ubicación para: {$it->name}. Selecciona una ubicación.");
                    }

                    $loc = Location::query()->findOrFail($locationId);

                    // Inventory row lock
                    $inv = Inventory::query()
                        ->where('catalog_item_id', $itemId)
                        ->where('location_id', $locationId)
                        ->lockForUpdate()
                        ->first();

                    if (!$inv) {
                        $inv = Inventory::query()->create([
                            'catalog_item_id' => $itemId,
                            'location_id' => $locationId,
                            'qty' => 0,
                            'min_qty' => 0,
                            'updated_by' => auth()->id(),
                        ]);
                        $inv->refresh();
                    }

                    $stockBefore = (int)$it->stock;
                    $invBefore = (int)$inv->qty;

                    if ($type === 'out') {
                        if ($invBefore < $qty) {
                            throw new \RuntimeException("Inventario insuficiente en {$loc->code} para {$it->name}. (Hay {$invBefore}, pides {$qty})");
                        }

                        $inv->qty = $invBefore - $qty;
                        $inv->updated_by = auth()->id();
                        $inv->save();

                        $it->stock = $stockBefore - $qty;
                        $it->save();
                    } else { // in
                        $inv->qty = $invBefore + $qty;
                        $inv->updated_by = auth()->id();
                        $inv->save();

                        $it->stock = $stockBefore + $qty;
                        $it->save();
                    }

                    WmsMovementLine::query()->create([
                        'movement_id' => $movement->id,
                        'catalog_item_id' => $itemId,
                        'location_id' => $locationId,
                        'qty' => $qty,
                        'stock_before' => $stockBefore,
                        'stock_after' => (int)$it->stock,
                        'inv_before' => $invBefore,
                        'inv_after' => (int)$inv->qty,
                    ]);

                    // plan de ruta
                    if (!isset($routePlan[$locationId])) {
                        $routePlan[$locationId] = [
                            'location_id' => $locationId,
                            'code' => $loc->code,
                            'lines' => [],
                        ];
                    }

                    $routePlan[$locationId]['lines'][] = [
                        'catalog_item_id' => $itemId,
                        'name' => $it->name,
                        'sku' => $it->sku,
                        'qty' => $qty,
                    ];
                }

                $routePlan = collect($routePlan)->sortBy('code')->values()->all();

                return [
                    'movement_id' => $movement->id,
                    'route' => $routePlan,
                ];
            });

            return response()->json([
                'ok' => true,
                'movement_id' => $result['movement_id'],
                'route' => $result['route'],
                'pdf_url' => route('admin.wms.movements.pdf', ['movement' => $result['movement_id']]),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    // ✅ PDF del movimiento
    public function movementPdf(WmsMovement $movement)
    {
        $movement->load([
            'warehouse:id,name,code',
            'user:id,name',
            'lines.item:id,name,sku,meli_gtin',
            'lines.location:id,code',
        ]);

        $route = $movement->lines
            ->groupBy(fn($l) => optional($l->location)->code ?: '—')
            ->map(function($rows, $code){
                return [
                    'code' => $code,
                    'total_qty' => $rows->sum('qty'),
                    'lines' => $rows->map(fn($l)=>[
                        'name' => optional($l->item)->name,
                        'sku' => optional($l->item)->sku,
                        'qty' => (int)$l->qty,
                    ])->values()->all(),
                ];
            })
            ->sortBy('code')
            ->values()
            ->all();

        $pdf = \PDF::loadView('admin.wms.movement_pdf', [
            'movement' => $movement,
            'route' => $route,
        ])->setPaper('a4');

        $name = 'movimiento-'.$movement->id.'.pdf';
        return $pdf->stream($name);
    }

    // Tabla historial (API)
    public function movementsData(Request $r)
    {
        $p = $r->validate([
            'warehouse_id' => ['required','integer','exists:warehouses,id'],
            'type' => ['nullable','in:in,out'],
            'q' => ['nullable','string','max:120'],
            'from' => ['nullable','date'],
            'to' => ['nullable','date'],
        ]);

        $warehouseId = (int)$p['warehouse_id'];
        $type = $p['type'] ?? null;
        $q = trim((string)($p['q'] ?? ''));
        $from = $p['from'] ?? null;
        $to = $p['to'] ?? null;

        $query = WmsMovementLine::query()
            ->whereHas('movement', function($m) use ($warehouseId, $type, $from, $to){
                $m->where('warehouse_id', $warehouseId);
                if ($type) $m->where('type', $type);
                if ($from) $m->whereDate('created_at', '>=', $from);
                if ($to) $m->whereDate('created_at', '<=', $to);
            })
            ->with([
                'movement:id,type,user_id,created_at,note',
                'item:id,name,sku,meli_gtin',
                'location:id,code',
            ])
            ->orderByDesc('id')
            ->limit(500);

        if ($q !== '') {
            $query->whereHas('item', function($qq) use ($q){
                $qq->where('name','like',"%{$q}%")
                   ->orWhere('sku','like',"%{$q}%")
                   ->orWhere('meli_gtin','like',"%{$q}%")
                   ->orWhere('id',$q);
            });
        }

        $rows = $query->get()->map(function($l){
            return [
                'when' => optional($l->movement)->created_at?->format('Y-m-d H:i:s'),
                'type' => optional($l->movement)->type,
                'item_id' => $l->catalog_item_id,
                'name' => optional($l->item)->name,
                'sku' => optional($l->item)->sku,
                'gtin' => optional($l->item)->meli_gtin,
                'location' => optional($l->location)->code,
                'qty' => (int)$l->qty,
                'stock_before' => (int)($l->stock_before ?? 0),
                'stock_after' => (int)($l->stock_after ?? 0),
                'inv_before' => (int)($l->inv_before ?? 0),
                'inv_after' => (int)($l->inv_after ?? 0),
                'note' => optional($l->movement)->note,
            ];
        });

        return response()->json(['ok'=>true,'rows'=>$rows]);
    }
}
