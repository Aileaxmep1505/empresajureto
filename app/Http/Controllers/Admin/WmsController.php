<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class WmsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [ new Middleware('auth') ];
    }

    /* =========================
     |  WAREHOUSES
     =========================*/

    // GET /admin/wms/warehouses
    public function warehousesIndex()
    {
        return response()->json([
            'ok' => true,
            'warehouses' => Warehouse::orderBy('id')->get(),
        ]);
    }

    // POST /admin/wms/warehouses
    public function warehousesStore(Request $r)
    {
        $data = $r->validate([
            'name' => ['required','string','max:255'],
            'code' => ['required','string','max:40','unique:warehouses,code'],
            'meta' => ['nullable','array'],
        ]);

        $w = Warehouse::create($data);

        return response()->json(['ok' => true, 'warehouse' => $w]);
    }

    /* =========================
     |  LOCATIONS
     =========================*/

    // GET /admin/wms/locations?s=...&warehouse_id=...
    public function locationsIndex(Request $r)
    {
        $q = Location::query()->with('warehouse');

        if ($r->filled('warehouse_id')) {
            $q->where('warehouse_id', (int)$r->warehouse_id);
        }

        $s = trim((string)$r->get('s',''));
        if ($s !== '') {
            $q->where(function($qq) use ($s) {
                $qq->where('code','like',"%{$s}%")
                   ->orWhere('name','like',"%{$s}%")
                   ->orWhere('aisle','like',"%{$s}%")
                   ->orWhere('section','like',"%{$s}%");
            });
        }

        return response()->json([
            'ok' => true,
            'locations' => $q->orderBy('code')->paginate(30)->withQueryString(),
        ]);
    }

    // POST /admin/wms/locations
    public function locationsStore(Request $r)
    {
        $data = $r->validate([
            'warehouse_id' => ['required','exists:warehouses,id'],
            'parent_id'    => ['nullable','exists:locations,id'],
            'type'         => ['required','string','max:30'],
            'code'         => ['required','string','max:80','unique:locations,code'],
            'aisle'        => ['nullable','string','max:10'],
            'section'      => ['nullable','string','max:10'],
            'stand'        => ['nullable','string','max:10'],
            'rack'         => ['nullable','string','max:10'],
            'level'        => ['nullable','string','max:10'],
            'bin'          => ['nullable','string','max:10'],
            'name'         => ['nullable','string','max:255'],
            'meta'         => ['nullable','array'],
        ]);

        $loc = Location::create($data);

        return response()->json(['ok' => true, 'location' => $loc]);
    }

    // PUT /admin/wms/locations/{location}
    public function locationsUpdate(Request $r, Location $location)
    {
        $data = $r->validate([
            'parent_id' => ['nullable','exists:locations,id'],
            'type'      => ['nullable','string','max:30'],
            'code'      => ['nullable','string','max:80','unique:locations,code,'.$location->id],
            'aisle'     => ['nullable','string','max:10'],
            'section'   => ['nullable','string','max:10'],
            'stand'     => ['nullable','string','max:10'],
            'rack'      => ['nullable','string','max:10'],
            'level'     => ['nullable','string','max:10'],
            'bin'       => ['nullable','string','max:10'],
            'name'      => ['nullable','string','max:255'],
            'meta'      => ['nullable','array'],
        ]);

        $location->update($data);

        return response()->json(['ok' => true, 'location' => $location->fresh()]);
    }

    // DELETE /admin/wms/locations/{location}
    public function locationsDestroy(Location $location)
    {
        $location->delete();
        return response()->json(['ok' => true]);
    }

    // GET /admin/wms/locations/scan?code=A-03-S2...
    public function locationScan(Request $r)
    {
        $r->validate([
            'code' => ['required','string','max:80'],
        ]);

        $loc = Location::where('code', $r->code)
            ->with('warehouse')
            ->first();

        if (!$loc) {
            return response()->json(['ok' => false, 'error' => 'Ubicación no encontrada.'], 404);
        }

        return $this->locationShow($loc);
    }

    // GET /admin/wms/locations/{location}
    public function locationShow(Location $location)
    {
        $location->load('warehouse', 'parent', 'children');

        $rows = Inventory::where('location_id', $location->id)
            ->with('item:id,name,sku,price,primary_location_id')
            ->orderByDesc('qty')
            ->get();

        return response()->json([
            'ok' => true,
            'location' => $location,
            'inventory' => $rows,
        ]);
    }

    /* =========================
     |  INVENTORY ACTIONS
     =========================*/

    /**
     * Ajustar inventario en una ubicación (set o delta)
     * POST /admin/wms/inventory/adjust
     * body:
     * - location_id
     * - catalog_item_id
     * - mode: set|delta
     * - qty: int (si set) o delta (si delta)
     * - min_qty? (opcional)
     * - notes? (opcional)
     * - movement_type? adjust|putaway|pick|cycle_count (opcional)
     */
    public function inventoryAdjust(Request $r)
    {
        $data = $r->validate([
            'location_id'     => ['required','exists:locations,id'],
            'catalog_item_id' => ['required','exists:catalog_items,id'],
            'mode'            => ['required','in:set,delta'],
            'qty'             => ['required','integer'],
            'min_qty'         => ['nullable','integer','min:0'],
            'notes'           => ['nullable','string','max:2000'],
            'movement_type'   => ['nullable','string','max:30'],
        ]);

        $movementType = $data['movement_type'] ?? 'adjust';

        return DB::transaction(function () use ($r, $data, $movementType) {
            $locId  = (int)$data['location_id'];
            $itemId = (int)$data['catalog_item_id'];

            $row = Inventory::where('location_id', $locId)
                ->where('catalog_item_id', $itemId)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                $row = Inventory::create([
                    'location_id'     => $locId,
                    'catalog_item_id' => $itemId,
                    'qty'             => 0,
                    'min_qty'         => 0,
                    'updated_by'      => $r->user()->id,
                ]);
                $row->refresh();
            }

            $before = (int)$row->qty;

            if ($data['mode'] === 'set') {
                $newQty = max(0, (int)$data['qty']);
            } else {
                $newQty = max(0, $before + (int)$data['qty']);
            }

            $row->qty = $newQty;
            if (array_key_exists('min_qty', $data) && $data['min_qty'] !== null) {
                $row->min_qty = (int)$data['min_qty'];
            }
            $row->updated_by = $r->user()->id;
            $row->save();

            InventoryMovement::create([
                'type'            => $movementType,
                'catalog_item_id' => $itemId,
                'from_location_id'=> null,
                'to_location_id'  => $locId,
                'qty'             => abs($newQty - $before),
                'user_id'         => $r->user()->id,
                'notes'           => $data['notes'] ?? null,
                'meta'            => [
                    'mode'   => $data['mode'],
                    'before' => $before,
                    'after'  => $newQty,
                ],
            ]);

            return response()->json([
                'ok' => true,
                'inventory' => $row->fresh(),
            ]);
        });
    }

    /**
     * Transferencia (mover stock de una ubicación a otra)
     * POST /admin/wms/inventory/transfer
     * body:
     * - catalog_item_id
     * - from_location_id
     * - to_location_id
     * - qty (positivo)
     * - notes?
     */
    public function inventoryTransfer(Request $r)
    {
        $data = $r->validate([
            'catalog_item_id'   => ['required','exists:catalog_items,id'],
            'from_location_id'  => ['required','exists:locations,id'],
            'to_location_id'    => ['required','exists:locations,id','different:from_location_id'],
            'qty'               => ['required','integer','min:1'],
            'notes'             => ['nullable','string','max:2000'],
        ]);

        return DB::transaction(function () use ($r, $data) {
            $itemId = (int)$data['catalog_item_id'];
            $fromId = (int)$data['from_location_id'];
            $toId   = (int)$data['to_location_id'];
            $qty    = (int)$data['qty'];

            $from = Inventory::where('location_id', $fromId)
                ->where('catalog_item_id', $itemId)
                ->lockForUpdate()
                ->first();

            if (!$from || (int)$from->qty < $qty) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Stock insuficiente en la ubicación origen.',
                ], 422);
            }

            $to = Inventory::where('location_id', $toId)
                ->where('catalog_item_id', $itemId)
                ->lockForUpdate()
                ->first();

            if (!$to) {
                $to = Inventory::create([
                    'location_id'     => $toId,
                    'catalog_item_id' => $itemId,
                    'qty'             => 0,
                    'min_qty'         => 0,
                    'updated_by'      => $r->user()->id,
                ]);
                $to->refresh();
            }

            $from->qty -= $qty;
            $from->updated_by = $r->user()->id;
            $from->save();

            $to->qty += $qty;
            $to->updated_by = $r->user()->id;
            $to->save();

            InventoryMovement::create([
                'type'            => 'transfer',
                'catalog_item_id' => $itemId,
                'from_location_id'=> $fromId,
                'to_location_id'  => $toId,
                'qty'             => $qty,
                'user_id'         => $r->user()->id,
                'notes'           => $data['notes'] ?? null,
            ]);

            return response()->json([
                'ok' => true,
                'from' => $from->fresh(),
                'to' => $to->fresh(),
            ]);
        });
    }

    /**
     * Asignar ubicación principal al producto (para búsqueda rápida)
     * POST /admin/wms/items/{catalogItem}/primary-location
     * body: location_id
     */
    public function setPrimaryLocation(Request $r, CatalogItem $catalogItem)
    {
        $data = $r->validate([
            'location_id' => ['nullable','exists:locations,id'],
        ]);

        $catalogItem->primary_location_id = $data['location_id'] ?? null;
        $catalogItem->save();

        return response()->json([
            'ok' => true,
            'item' => $catalogItem->fresh(),
        ]);
    }
}
