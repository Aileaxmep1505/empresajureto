<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\Warehouse;
use App\Models\WmsQuickBox;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class WmsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('auth')];
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
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', 'unique:warehouses,code'],
            'meta' => ['nullable', 'array'],
        ]);

        $w = Warehouse::create($data);

        return response()->json([
            'ok' => true,
            'warehouse' => $w,
        ]);
    }

    /* =========================
     |  LOCATIONS
     =========================*/

    // GET /admin/wms/locations?s=...&warehouse_id=...
    public function locationsIndex(Request $r)
    {
        $q = Location::query()->with('warehouse');

        if ($r->filled('warehouse_id')) {
            $q->where('warehouse_id', (int) $r->warehouse_id);
        }

        $s = trim((string) $r->get('s', ''));
        if ($s !== '') {
            $q->where(function ($qq) use ($s) {
                $qq->where('code', 'like', "%{$s}%")
                    ->orWhere('name', 'like', "%{$s}%")
                    ->orWhere('aisle', 'like', "%{$s}%")
                    ->orWhere('section', 'like', "%{$s}%")
                    ->orWhere('stand', 'like', "%{$s}%")
                    ->orWhere('rack', 'like', "%{$s}%")
                    ->orWhere('level', 'like', "%{$s}%")
                    ->orWhere('bin', 'like', "%{$s}%");
            });
        }

        $locations = $q->orderBy('code')->paginate(50)->withQueryString();

        $locations->getCollection()->transform(function ($loc) {
            $meta = is_array($loc->meta) ? $loc->meta : [];

            $inventory = Inventory::query()
                ->where('location_id', $loc->id)
                ->with('item:id,name,sku')
                ->orderByDesc('qty')
                ->get();

            $fastBoxes = WmsQuickBox::query()
                ->with('item:id,name,sku')
                ->where('location_id', $loc->id)
                ->whereIn('status', ['available', 'partial'])
                ->orderByDesc('current_units')
                ->get();

            $inventoryQty = (int) $inventory->sum('qty');
            $fastFlowQty = (int) $fastBoxes->sum('current_units');
            $qtyTotal = $inventoryQty + $fastFlowQty;

            $capacity = max(1, (int) ($meta['capacity'] ?? 100));
            $occupancyPercent = min(100, (int) round(($qtyTotal / $capacity) * 100));

            $status = $meta['status'] ?? null;
            if (!$status) {
                if ($qtyTotal <= 0) {
                    $status = 'vacía';
                } elseif ($occupancyPercent >= 100) {
                    $status = 'lleno';
                } elseif ($occupancyPercent >= 80) {
                    $status = 'alta';
                } else {
                    $status = 'disponible';
                }
            }

            $zone = $meta['zone'] ?? null;
            if (!$zone) {
                $type = strtolower((string) $loc->type);
                $zone = match ($type) {
                    'recepcion', 'recepción' => 'Recepción',
                    'picking' => 'Picking',
                    'shipping', 'expedicion', 'expedición' => 'Expedición',
                    'quarantine', 'cuarentena' => 'Cuarentena',
                    'fast_flow' => 'Fast Flow',
                    default => 'Almacenamiento',
                };
            }

            $topInventoryItems = $inventory->take(3)->map(function ($row) {
                return [
                    'name' => $row->item->name ?? 'Producto',
                    'sku'  => $row->item->sku ?? null,
                    'qty'  => (int) $row->qty,
                    'source' => 'inventory',
                ];
            });

            $topFastItems = $fastBoxes->take(3)->map(function ($box) {
                return [
                    'name' => $box->item->name ?? 'Producto',
                    'sku'  => $box->item->sku ?? null,
                    'qty'  => (int) $box->current_units,
                    'source' => 'fastflow',
                ];
            });

            $topItems = $topInventoryItems
                ->concat($topFastItems)
                ->sortByDesc('qty')
                ->take(3)
                ->values();

            $loc->ui = [
                'zone' => $zone,
                'capacity' => $capacity,
                'qty_total' => $qtyTotal,
                'inventory_qty_total' => $inventoryQty,
                'fastflow_qty_total' => $fastFlowQty,
                'occupancy_percent' => $occupancyPercent,
                'status' => $status,
                'position_label' => collect([
                    $loc->aisle,
                    $loc->section,
                    $loc->stand,
                    $loc->rack,
                    $loc->level,
                    $loc->bin,
                ])->filter(fn ($v) => filled($v))->implode(' / '),
                'top_items' => $topItems,
                'items_count' => $inventory->count() + $fastBoxes->count(),
            ];

            return $loc;
        });

        return response()->json([
            'ok' => true,
            'locations' => $locations,
        ]);
    }

    // POST /admin/wms/locations
    public function locationsStore(Request $r)
    {
        $data = $r->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'parent_id'    => ['nullable', 'exists:locations,id'],
            'type'         => ['required', 'string', 'max:30'],
            'code'         => ['required', 'string', 'max:80', 'unique:locations,code'],
            'aisle'        => ['nullable', 'string', 'max:10'],
            'section'      => ['nullable', 'string', 'max:10'],
            'stand'        => ['nullable', 'string', 'max:10'],
            'rack'         => ['nullable', 'string', 'max:10'],
            'level'        => ['nullable', 'string', 'max:10'],
            'bin'          => ['nullable', 'string', 'max:10'],
            'name'         => ['nullable', 'string', 'max:255'],
            'meta'         => ['nullable', 'array'],
        ]);

        $loc = Location::create($data);

        return response()->json([
            'ok' => true,
            'location' => $loc,
        ]);
    }

    // PUT /admin/wms/locations/{location}
    public function locationsUpdate(Request $r, Location $location)
    {
        $data = $r->validate([
            'parent_id' => ['nullable', 'exists:locations,id'],
            'type'      => ['nullable', 'string', 'max:30'],
            'code'      => ['nullable', 'string', 'max:80', 'unique:locations,code,' . $location->id],
            'aisle'     => ['nullable', 'string', 'max:10'],
            'section'   => ['nullable', 'string', 'max:10'],
            'stand'     => ['nullable', 'string', 'max:10'],
            'rack'      => ['nullable', 'string', 'max:10'],
            'level'     => ['nullable', 'string', 'max:10'],
            'bin'       => ['nullable', 'string', 'max:10'],
            'name'      => ['nullable', 'string', 'max:255'],
            'meta'      => ['nullable', 'array'],
        ]);

        $location->update($data);

        return response()->json([
            'ok' => true,
            'location' => $location->fresh(),
        ]);
    }

    // DELETE /admin/wms/locations/{location}
    public function locationsDestroy(Location $location)
    {
        $location->delete();

        return response()->json([
            'ok' => true,
        ]);
    }

    // GET /admin/wms/locations/scan?code=A-03-S2...
    public function locationScan(Request $r)
    {
        $r->validate([
            'code' => ['required', 'string', 'max:80'],
        ]);

        $loc = Location::where('code', $r->code)
            ->with('warehouse')
            ->first();

        if (!$loc) {
            return response()->json([
                'ok' => false,
                'error' => 'Ubicación no encontrada.',
            ], 404);
        }

        return $this->locationShow($loc);
    }

    // GET /admin/wms/locations/{location}
    public function locationShow(Location $location)
    {
        $location->load('warehouse', 'parent', 'children');

        $rows = Inventory::query()
            ->where('location_id', $location->id)
            ->with('item:id,name,sku,price,primary_location_id')
            ->orderByDesc('qty')
            ->get();

        $fastBoxes = WmsQuickBox::query()
            ->with('item:id,name,sku')
            ->where('location_id', $location->id)
            ->orderByDesc('current_units')
            ->get();

        $meta = is_array($location->meta) ? $location->meta : [];

        $inventoryQty = (int) $rows->sum('qty');
        $fastflowQty = (int) $fastBoxes->sum('current_units');
        $qtyTotal = $inventoryQty + $fastflowQty;

        $capacity = max(1, (int) ($meta['capacity'] ?? 100));
        $occupancyPercent = min(100, (int) round(($qtyTotal / $capacity) * 100));

        $status = $meta['status'] ?? null;
        if (!$status) {
            if ($qtyTotal <= 0) {
                $status = 'vacía';
            } elseif ($occupancyPercent >= 100) {
                $status = 'lleno';
            } elseif ($occupancyPercent >= 80) {
                $status = 'alta';
            } else {
                $status = 'disponible';
            }
        }

        return response()->json([
            'ok' => true,
            'location' => $location,
            'summary' => [
                'capacity' => $capacity,
                'qty_total' => $qtyTotal,
                'inventory_qty_total' => $inventoryQty,
                'fastflow_qty_total' => $fastflowQty,
                'occupancy_percent' => $occupancyPercent,
                'status' => $status,
                'zone' => $meta['zone'] ?? 'Almacenamiento',
                'position_label' => collect([
                    $location->aisle,
                    $location->section,
                    $location->stand,
                    $location->rack,
                    $location->level,
                    $location->bin,
                ])->filter(fn ($v) => filled($v))->implode(' / '),
            ],
            'inventory' => $rows,
            'fastflow' => $fastBoxes,
        ]);
    }

    /* =========================
     |  HEATMAP
     =========================*/

    // GET /admin/wms/heatmap/data?warehouse_id=1&metric=inv_qty
    public function heatmapData(Request $r)
    {
        $warehouseId = (int) $r->get('warehouse_id', 1);
        $metric = (string) $r->get('metric', 'inv_qty');

        $locations = Location::query()
            ->where('warehouse_id', $warehouseId)
            ->with([
                'inventoryRows.item:id,name,sku,stock',
            ])
            ->orderBy('rack')
            ->orderBy('level')
            ->orderBy('bin')
            ->get();

        $fastBoxesByLocation = WmsQuickBox::query()
            ->selectRaw('location_id, SUM(current_units) as total_units')
            ->whereNotNull('location_id')
            ->whereIn('status', ['available', 'partial'])
            ->groupBy('location_id')
            ->pluck('total_units', 'location_id');

        $rackLike = [];
        $picking = [];
        $fastFlow = [];
        $incoming = [];

        foreach ($locations as $loc) {
            $meta = is_array($loc->meta) ? $loc->meta : [];

            $inventoryQty = (int) $loc->inventoryRows->sum('qty');
            $fastQty = (int) ($fastBoxesByLocation[$loc->id] ?? 0);
            $qtyTotal = $inventoryQty + $fastQty;

            $capacity = max(1, (int) ($meta['capacity'] ?? 100));

            $topInventory = $loc->inventoryRows
                ->sortByDesc('qty')
                ->first();

            $productName = $topInventory?->item?->name;
            $productSku  = $topInventory?->item?->sku;

            $zoneRaw = strtolower(trim((string) ($meta['zone'] ?? $loc->type ?? 'almacenamiento')));
            $zone = match ($zoneRaw) {
                'picking' => 'picking',
                'fast_flow', 'fastflow', 'fast flow' => 'fast_flow',
                'incoming', 'entrante', 'recepcion', 'recepción' => 'incoming',
                default => 'rack',
            };

            $rackValue = trim((string) ($loc->rack ?? ''));
            $levelValue = (int) preg_replace('/[^0-9]/', '', (string) ($loc->level ?? 0));
            $binValue = (int) preg_replace('/[^0-9]/', '', (string) ($loc->bin ?? 0));

            $primaryStock = (int) ($topInventory?->item?->stock ?? 0);
            $value = $metric === 'primary_stock'
                ? ($primaryStock > 0 ? $primaryStock : $qtyTotal)
                : $qtyTotal;

            $ratio = $capacity > 0 ? ($qtyTotal / $capacity) : 0;

            $status = 'empty';
            if ($qtyTotal > 0) {
                if ($ratio >= 0.90) {
                    $status = 'full';
                } elseif ($ratio >= 0.60) {
                    $status = 'high';
                } else {
                    $status = 'partial';
                }
            }

            $row = [
                'id' => $loc->id,
                'code' => $loc->code,
                'location_code' => $loc->code,
                'name' => $loc->name,
                'product_name' => $productName,
                'product_sku' => $productSku,
                'rack_raw' => $rackValue,
                'level_raw' => $loc->level,
                'bin_raw' => $loc->bin,
                'rack_number' => $rackValue,
                'level' => $levelValue > 0 ? $levelValue : 1,
                'position' => $binValue > 0 ? $binValue : null,
                'zone' => $zone,
                'capacity' => $capacity,
                'max_capacity' => $capacity,
                'inventory_qty' => $inventoryQty,
                'fastflow_qty' => $fastQty,
                'qty_total' => $qtyTotal,
                'primary_stock' => $primaryStock,
                'value' => (int) $value,
                'status' => $status,
                'meta' => $meta,
            ];

            if ($zone === 'picking') {
                $picking[] = $row;
            } elseif ($zone === 'fast_flow') {
                $fastFlow[] = $row;
            } elseif ($zone === 'incoming') {
                $incoming[] = $row;
            } else {
                $rackLike[] = $row;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Normalizar racks reales a 3 racks visuales
        |--------------------------------------------------------------------------
        */
        $distinctRacks = collect($rackLike)
            ->pluck('rack_raw')
            ->filter(fn ($v) => filled($v))
            ->unique()
            ->values();

        $rackMap = [];
        foreach ($distinctRacks as $i => $rackName) {
            if ($i >= 3) {
                break;
            }
            $rackMap[(string) $rackName] = $i + 1;
        }

        $groupedByRackLevel = [];
        foreach ($rackLike as &$row) {
            $rawRack = (string) ($row['rack_raw'] ?? '');

            if ($rawRack !== '' && isset($rackMap[$rawRack])) {
                $row['rack_number'] = $rackMap[$rawRack];
            } else {
                $row['rack_number'] = 1;
            }

            $row['level'] = max(1, min(4, (int) ($row['level'] ?? 1)));

            $groupedByRackLevel[$row['rack_number']][$row['level']][] = $row['id'];
        }
        unset($row);

        /*
        |--------------------------------------------------------------------------
        | Asignar posiciones si no existen
        |--------------------------------------------------------------------------
        */
        $rackLikeById = collect($rackLike)->keyBy('id');

        foreach ($groupedByRackLevel as $rackNumber => $levels) {
            foreach ($levels as $levelNumber => $ids) {
                $rows = collect($ids)
                    ->map(fn ($id) => $rackLikeById[$id])
                    ->sortBy(function ($item) {
                        $pos = $item['position'];
                        if ($pos !== null) {
                            return (int) $pos;
                        }

                        return (string) ($item['code'] ?? '');
                    })
                    ->values();

                foreach ($rows as $idx => $item) {
                    $pos = (int) ($item['position'] ?? 0);

                    if ($pos <= 0) {
                        $pos = $idx + 1;
                    }

                    $item['position'] = max(1, min(8, $pos));
                    $rackLikeById[$item['id']] = $item;
                }
            }
        }

        $rackLike = $rackLikeById->values()->all();

        /*
        |--------------------------------------------------------------------------
        | Zonas inferiores: limitar y acomodar
        |--------------------------------------------------------------------------
        */
        $normalizeZone = function (array $rows, string $zoneName) {
            return collect($rows)
                ->values()
                ->map(function ($row, $i) use ($zoneName) {
                    $row['zone'] = $zoneName;
                    $row['position'] = $i + 1;
                    return $row;
                })
                ->all();
        };

        $picking = $normalizeZone($picking, 'picking');
        $fastFlow = $normalizeZone($fastFlow, 'fast_flow');
        $incoming = $normalizeZone($incoming, 'incoming');

        $cells = array_values(array_merge($rackLike, $picking, $fastFlow, $incoming));
        $max = (int) (collect($cells)->max('value') ?? 0);

        return response()->json([
            'ok' => true,
            'metric' => $metric,
            'max' => $max,
            'cells' => $cells,
        ]);
    }

    /* =========================
     |  PRODUCT LOOKUP
     =========================*/

    // GET /admin/wms/products/lookup?s=...
    public function productLookup(Request $r)
    {
        $term = trim((string) $r->get('s', ''));

        if ($term === '') {
            return response()->json([
                'ok' => true,
                'query' => '',
                'items' => [],
            ]);
        }

        $items = CatalogItem::query()
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");

                if (is_numeric($term)) {
                    $q->orWhere('id', (int) $term);
                }
            })
            ->orderBy('name')
            ->limit(30)
            ->get();

        $results = $items->map(function ($item) {
            $inventoryRows = Inventory::query()
                ->with([
                    'location:id,warehouse_id,code,name,type,aisle,section,stand,rack,level,bin,meta',
                    'location.warehouse:id,name,code',
                ])
                ->where('catalog_item_id', $item->id)
                ->orderByDesc('qty')
                ->get();

            $fastBoxes = WmsQuickBox::query()
                ->with([
                    'warehouse:id,name,code',
                    'location:id,code,name,type,aisle,section,stand,rack,level,bin,meta',
                ])
                ->where('catalog_item_id', $item->id)
                ->whereIn('status', ['available', 'partial'])
                ->orderByDesc('id')
                ->limit(100)
                ->get();

            $locationRows = $inventoryRows->map(function ($row) {
                $loc = $row->location;
                $meta = is_array($loc?->meta) ? $loc?->meta : [];

                return [
                    'inventory_id' => $row->id,
                    'qty' => (int) $row->qty,
                    'min_qty' => (int) ($row->min_qty ?? 0),
                    'location' => [
                        'id' => $loc?->id,
                        'code' => $loc?->code,
                        'name' => $loc?->name,
                        'type' => $loc?->type,
                        'warehouse_name' => $loc?->warehouse?->name,
                        'warehouse_code' => $loc?->warehouse?->code,
                        'aisle' => $loc?->aisle,
                        'section' => $loc?->section,
                        'stand' => $loc?->stand,
                        'rack' => $loc?->rack,
                        'level' => $loc?->level,
                        'bin' => $loc?->bin,
                        'zone' => $meta['zone'] ?? null,
                        'position_label' => collect([
                            $loc?->aisle,
                            $loc?->section,
                            $loc?->stand,
                            $loc?->rack,
                            $loc?->level,
                            $loc?->bin,
                        ])->filter(fn ($v) => filled($v))->implode(' / '),
                    ],
                ];
            })->values();

            $fastFlowRows = $fastBoxes->map(function ($box) {
                $loc = $box->location;
                $meta = is_array($loc?->meta) ? $loc?->meta : [];

                return [
                    'id' => $box->id,
                    'batch_code' => $box->batch_code,
                    'label_code' => $box->label_code,
                    'box_number' => (int) $box->box_number,
                    'boxes_in_batch' => (int) $box->boxes_in_batch,
                    'units_per_box' => (int) $box->units_per_box,
                    'current_units' => (int) $box->current_units,
                    'status' => $box->status,
                    'warehouse_name' => $box->warehouse?->name,
                    'location_code' => $loc?->code,
                    'location_name' => $loc?->name,
                    'location_zone' => $meta['zone'] ?? ($loc?->type ?: null),
                    'position_label' => collect([
                        $loc?->aisle,
                        $loc?->section,
                        $loc?->stand,
                        $loc?->rack,
                        $loc?->level,
                        $loc?->bin,
                    ])->filter(fn ($v) => filled($v))->implode(' / '),
                    'received_at' => optional($box->received_at)->format('Y-m-d H:i'),
                ];
            })->values();

            return [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'slug' => $item->slug,
                'price' => (float) ($item->price ?? 0),
                'sale_price' => $item->sale_price !== null ? (float) $item->sale_price : null,
                'stock' => (int) ($item->stock ?? 0),
                'primary_location_id' => $item->primary_location_id,
                'total_inventory_units' => (int) $inventoryRows->sum('qty'),
                'inventory_locations_count' => (int) $inventoryRows->count(),
                'fastflow_boxes_count' => (int) $fastBoxes->count(),
                'fastflow_units_count' => (int) $fastBoxes->sum('current_units'),
                'locations' => $locationRows,
                'fastflow' => $fastFlowRows,
            ];
        })->values();

        return response()->json([
            'ok' => true,
            'query' => $term,
            'items' => $results,
        ]);
    }

    /* =========================
     |  INVENTORY ACTIONS
     =========================*/

    /**
     * Ajustar inventario en una ubicación (set o delta)
     * POST /admin/wms/inventory/adjust
     */
    public function inventoryAdjust(Request $r)
    {
        $data = $r->validate([
            'location_id'     => ['required', 'exists:locations,id'],
            'catalog_item_id' => ['required', 'exists:catalog_items,id'],
            'mode'            => ['required', 'in:set,delta'],
            'qty'             => ['required', 'integer'],
            'min_qty'         => ['nullable', 'integer', 'min:0'],
            'notes'           => ['nullable', 'string', 'max:2000'],
            'movement_type'   => ['nullable', 'string', 'max:30'],
        ]);

        $movementType = $data['movement_type'] ?? 'adjust';

        return DB::transaction(function () use ($r, $data, $movementType) {
            $locId = (int) $data['location_id'];
            $itemId = (int) $data['catalog_item_id'];

            $row = Inventory::query()
                ->where('location_id', $locId)
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

            $before = (int) $row->qty;

            if ($data['mode'] === 'set') {
                $newQty = max(0, (int) $data['qty']);
            } else {
                $newQty = max(0, $before + (int) $data['qty']);
            }

            $row->qty = $newQty;

            if (array_key_exists('min_qty', $data) && $data['min_qty'] !== null) {
                $row->min_qty = (int) $data['min_qty'];
            }

            $row->updated_by = $r->user()->id;
            $row->save();

            InventoryMovement::create([
                'type'             => $movementType,
                'catalog_item_id'  => $itemId,
                'from_location_id' => null,
                'to_location_id'   => $locId,
                'qty'              => abs($newQty - $before),
                'user_id'          => $r->user()->id,
                'notes'            => $data['notes'] ?? null,
                'meta'             => [
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
     */
    public function inventoryTransfer(Request $r)
    {
        $data = $r->validate([
            'catalog_item_id'   => ['required', 'exists:catalog_items,id'],
            'from_location_id'  => ['required', 'exists:locations,id'],
            'to_location_id'    => ['required', 'exists:locations,id', 'different:from_location_id'],
            'qty'               => ['required', 'integer', 'min:1'],
            'notes'             => ['nullable', 'string', 'max:2000'],
        ]);

        return DB::transaction(function () use ($r, $data) {
            $itemId = (int) $data['catalog_item_id'];
            $fromId = (int) $data['from_location_id'];
            $toId   = (int) $data['to_location_id'];
            $qty    = (int) $data['qty'];

            $from = Inventory::query()
                ->where('location_id', $fromId)
                ->where('catalog_item_id', $itemId)
                ->lockForUpdate()
                ->first();

            if (!$from || (int) $from->qty < $qty) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Stock insuficiente en la ubicación origen.',
                ], 422);
            }

            $to = Inventory::query()
                ->where('location_id', $toId)
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
                'type'             => 'transfer',
                'catalog_item_id'  => $itemId,
                'from_location_id' => $fromId,
                'to_location_id'   => $toId,
                'qty'              => $qty,
                'user_id'          => $r->user()->id,
                'notes'            => $data['notes'] ?? null,
            ]);

            return response()->json([
                'ok' => true,
                'from' => $from->fresh(),
                'to' => $to->fresh(),
            ]);
        });
    }

    /**
     * Asignar ubicación principal al producto
     * POST /admin/wms/items/{catalogItem}/primary-location
     */
    public function setPrimaryLocation(Request $r, CatalogItem $catalogItem)
    {
        $data = $r->validate([
            'location_id' => ['nullable', 'exists:locations,id'],
        ]);

        $catalogItem->primary_location_id = $data['location_id'] ?? null;
        $catalogItem->save();

        return response()->json([
            'ok' => true,
            'item' => $catalogItem->fresh(),
        ]);
    }
}