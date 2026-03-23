<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WmsLayoutController extends Controller implements HasMiddleware
{
    private const RACK_COUNT = 3;
    private const LEVEL_COUNT = 4;
    private const MIN_VISIBLE_POSITIONS = 8;
    private const UNIQUE_ZONES = ['incoming', 'fast_flow', 'picking', 'dispatch'];

    public static function middleware(): array
    {
        return [new Middleware('auth')];
    }

    public function editor(Request $r)
    {
        $warehouses = Warehouse::query()->orderBy('id')->get(['id', 'name']);
        $warehouseId = (int) ($r->get('warehouse_id') ?: ($warehouses->first()->id ?? 1));

        return view('admin.wms.layout', compact('warehouses', 'warehouseId'));
    }

    public function heatmap(Request $r)
    {
        $warehouses = Warehouse::query()->orderBy('id')->get(['id', 'name']);
        $warehouseId = (int) ($r->get('warehouse_id') ?: ($warehouses->first()->id ?? 1));

        return view('admin.wms.heatmap', compact('warehouses', 'warehouseId'));
    }

    public function data(Request $r)
    {
        $data = $r->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
        ]);

        $warehouseId = (int) $data['warehouse_id'];
        $locations = $this->buildLocationsForWarehouse($warehouseId);
        $suggestion = $this->findNextAvailableSlot($locations);

        return response()->json([
            'ok' => true,
            'structure' => [
                'rack_count' => self::RACK_COUNT,
                'level_count' => self::LEVEL_COUNT,
            ],
            'next_slot' => $suggestion,
            'locations' => $locations->map(fn (array $loc) => [
                'id' => $loc['id'],
                'warehouse_id' => $loc['warehouse_id'],
                'type' => $loc['type'],
                'zone' => $loc['zone'],
                'zone_label' => $loc['zone_label'],
                'code' => $loc['code'],
                'name' => $loc['name'],
                'aisle' => $loc['aisle'],
                'section' => $loc['section'],
                'stand' => $loc['stand'],
                'rack' => $loc['rack'],
                'rack_key' => $loc['rack_key'],
                'level' => $loc['level'],
                'bin' => $loc['bin'],
                'position' => $loc['position'],
                'meta' => $loc['meta'],
                'inferred' => $loc['inferred'],
            ])->values(),
        ]);
    }

    public function suggestSlot(Request $r)
    {
        $data = $r->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
        ]);

        $locations = $this->buildLocationsForWarehouse((int) $data['warehouse_id']);

        return response()->json([
            'ok' => true,
            'suggestion' => $this->findNextAvailableSlot($locations),
        ]);
    }

    public function availableOptions(Request $r)
    {
        $data = $r->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);

        $warehouseId = (int) $data['warehouse_id'];
        $ignoreId = !empty($data['id']) ? (int) $data['id'] : null;

        $rows = $this->buildLocationsForWarehouse($warehouseId);

        return response()->json([
            'ok' => true,
            'slots' => $this->buildAvailableSlots($rows, $ignoreId),
            'zones' => $this->buildAvailableZones($rows, $ignoreId),
            'next_slot' => $this->findNextAvailableSlot($rows, $ignoreId),
        ]);
    }

    public function upsertCell(Request $r)
    {
        $data = $r->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'id' => ['nullable', 'integer', 'exists:locations,id'],

            'type' => ['required', 'string', 'max:30'],
            'code' => ['nullable', 'string', 'max:80'],
            'name' => ['nullable', 'string', 'max:120'],

            'aisle' => ['nullable', 'string', 'max:20'],
            'section' => ['nullable', 'string', 'max:20'],
            'stand' => ['nullable', 'string', 'max:20'],
            'rack' => ['nullable', 'string', 'max:20'],
            'level' => ['nullable', 'string', 'max:20'],
            'bin' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'integer', 'min:1', 'max:999999'],

            'meta' => ['nullable', 'array'],
            'meta.notes' => ['nullable', 'string', 'max:500'],
            'meta.capacity' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'meta.max_capacity' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'meta.color' => ['nullable', 'string', 'max:40'],
            'meta.status' => ['nullable', 'string', 'max:40'],
            'meta.x' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'meta.y' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'meta.w' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'meta.h' => ['nullable', 'integer', 'min:1', 'max:999999'],
        ]);

        $warehouseId = (int) $data['warehouse_id'];
        $type = $this->normalizeType((string) $data['type']);

        $location = !empty($data['id'])
            ? Location::query()->where('id', (int) $data['id'])->firstOrFail()
            : new Location();

        $existingMeta = $this->normalizeMeta($location->meta);
        $meta = array_merge($existingMeta, $data['meta'] ?? []);

        if (array_key_exists('notes', $meta)) {
            $meta['notes'] = filled($meta['notes']) ? trim((string) $meta['notes']) : null;
        }

        if (array_key_exists('color', $meta)) {
            $meta['color'] = filled($meta['color']) ? trim((string) $meta['color']) : null;
        }

        if (array_key_exists('status', $meta)) {
            $meta['status'] = filled($meta['status']) ? trim((string) $meta['status']) : null;
        }

        if (array_key_exists('capacity', $meta)) {
            $meta['capacity'] = (int) $meta['capacity'];
        }

        if (array_key_exists('max_capacity', $meta)) {
            $meta['max_capacity'] = (int) $meta['max_capacity'];
        }

        if (!isset($meta['capacity']) && !isset($meta['max_capacity'])) {
            $meta['capacity'] = (int) ($existingMeta['capacity'] ?? 100);
            $meta['max_capacity'] = (int) ($existingMeta['max_capacity'] ?? $meta['capacity']);
        } elseif (!isset($meta['capacity']) && isset($meta['max_capacity'])) {
            $meta['capacity'] = (int) $meta['max_capacity'];
        } elseif (!isset($meta['max_capacity']) && isset($meta['capacity'])) {
            $meta['max_capacity'] = (int) $meta['capacity'];
        }

        $location->warehouse_id = $warehouseId;
        $location->type = $type;
        $location->aisle = filled($data['aisle'] ?? null) ? trim((string) $data['aisle']) : null;
        $location->section = filled($data['section'] ?? null) ? trim((string) $data['section']) : null;
        $location->stand = filled($data['stand'] ?? null) ? trim((string) $data['stand']) : null;

        if (in_array($type, self::UNIQUE_ZONES, true)) {
            $existsZone = Location::query()
                ->where('warehouse_id', $warehouseId)
                ->where('type', $type)
                ->when($location->exists, fn ($q) => $q->where('id', '!=', $location->id))
                ->exists();

            if ($existsZone) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Esa zona ya tiene una ubicación registrada en esta bodega.',
                ], 422);
            }

            $rawCode = Str::upper(trim((string) ($data['code'] ?? ('ZONE-' . Str::upper($type)))));
            $code = $this->ensureUniqueCode($rawCode, $location->exists ? (int) $location->id : null);

            $name = filled($data['name'] ?? null)
                ? trim((string) $data['name'])
                : $this->zoneLabel($type);

            $meta = $this->decorateMetaForHeatmap($type, $meta, 0, 0, 0);

            $location->code = $code;
            $location->name = $name;
            $location->rack = null;
            $location->level = null;
            $location->bin = null;
            $location->meta = array_merge($meta, ['created_from' => 'fixed_heatmap']);
            $location->save();

            return response()->json([
                'ok' => true,
                'location' => $this->serializeLocation($location),
            ]);
        }

        $rack = $this->toIndex($data['rack'] ?? null, 0);
        $level = $this->toIndex($data['level'] ?? null, 0);
        $position = $this->toIndex($data['bin'] ?? ($data['position'] ?? null), 0);

        if ($rack < 1 || $level < 1 || $position < 1) {
            $suggestion = $this->findNextAvailableSlot(
                $this->buildLocationsForWarehouse($warehouseId),
                $location->exists ? (int) $location->id : null
            );

            $rack = (int) $suggestion['rack'];
            $level = (int) $suggestion['level'];
            $position = (int) $suggestion['position'];
        }

        if ($rack < 1 || $rack > self::RACK_COUNT) {
            return response()->json([
                'ok' => false,
                'error' => 'Rack inválido. Debe estar entre 1 y ' . self::RACK_COUNT . '.',
            ], 422);
        }

        if ($level < 1 || $level > self::LEVEL_COUNT) {
            return response()->json([
                'ok' => false,
                'error' => 'Nivel inválido. Debe estar entre 1 y ' . self::LEVEL_COUNT . '.',
            ], 422);
        }

        if ($position < 1) {
            return response()->json([
                'ok' => false,
                'error' => 'Posición inválida.',
            ], 422);
        }

        $slotExists = Location::query()
            ->where('warehouse_id', $warehouseId)
            ->whereNotIn('type', self::UNIQUE_ZONES)
            ->where('rack', (string) $rack)
            ->where('level', (string) $level)
            ->where('bin', (string) $position)
            ->when($location->exists, fn ($q) => $q->where('id', '!=', $location->id))
            ->exists();

        if ($slotExists) {
            return response()->json([
                'ok' => false,
                'error' => "La posición Rack {$rack} · Nivel {$level} · Posición {$position} ya está ocupada.",
            ], 422);
        }

        $rawCode = Str::upper(trim((string) ($data['code'] ?? $this->buildRackCode($rack, $level, $position))));
        $code = $this->ensureUniqueCode($rawCode, $location->exists ? (int) $location->id : null);

        $name = filled($data['name'] ?? null)
            ? trim((string) $data['name'])
            : "Rack {$rack} · Nivel {$level} · Posición {$position}";

        $meta = $this->decorateMetaForHeatmap('rack', $meta, $rack, $level, $position);

        $location->code = $code;
        $location->name = $name;
        $location->rack = (string) $rack;
        $location->level = (string) $level;
        $location->bin = (string) $position;
        $location->meta = array_merge($meta, ['created_from' => 'fixed_heatmap']);
        $location->save();

        return response()->json([
            'ok' => true,
            'location' => $this->serializeLocation($location),
        ]);
    }

    public function storeFromHeatmap(Request $r)
    {
        return $this->upsertCell($r);
    }

    public function deleteCell(Request $r)
    {
        $data = $r->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'id' => ['required', 'integer', 'exists:locations,id'],
        ]);

        $location = Location::query()
            ->where('warehouse_id', (int) $data['warehouse_id'])
            ->where('id', (int) $data['id'])
            ->first();

        if (!$location) {
            return response()->json([
                'ok' => false,
                'error' => 'No encontrado en esa bodega.',
            ], 404);
        }

        $location->delete();

        return response()->json(['ok' => true]);
    }

    public function generateRack(Request $r)
    {
        $data = $r->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'positions' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'bins' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'capacity' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'prefix' => ['nullable', 'string', 'max:20'],
            'stand' => ['nullable', 'string', 'max:20'],
        ]);

        $warehouseId = (int) $data['warehouse_id'];
        $positions = (int) ($data['positions'] ?? $data['bins'] ?? self::MIN_VISIBLE_POSITIONS);
        $capacity = (int) ($data['capacity'] ?? 100);
        $prefix = filled($data['prefix'] ?? null) ? Str::upper(trim((string) $data['prefix'])) : null;
        $stand = filled($data['stand'] ?? null) ? trim((string) $data['stand']) : null;

        $created = 0;
        $skipped = 0;

        for ($rack = 1; $rack <= self::RACK_COUNT; $rack++) {
            for ($level = 1; $level <= self::LEVEL_COUNT; $level++) {
                for ($position = 1; $position <= $positions; $position++) {
                    $exists = Location::query()
                        ->where('warehouse_id', $warehouseId)
                        ->whereNotIn('type', self::UNIQUE_ZONES)
                        ->where('rack', (string) $rack)
                        ->where('level', (string) $level)
                        ->where('bin', (string) $position)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    $meta = $this->decorateMetaForHeatmap('rack', [
                        'capacity' => $capacity,
                        'max_capacity' => $capacity,
                        'generated' => true,
                        'generated_from' => 'fixed_heatmap_bulk',
                    ], $rack, $level, $position);

                    $location = new Location();
                    $location->warehouse_id = $warehouseId;
                    $location->type = 'bin';
                    $location->aisle = $prefix;
                    $location->stand = $stand;
                    $location->rack = (string) $rack;
                    $location->level = (string) $level;
                    $location->bin = (string) $position;
                    $location->code = $this->ensureUniqueCode(
                        $this->buildRackCode($rack, $level, $position, $prefix, $stand)
                    );
                    $location->name = "Rack {$rack} · Nivel {$level} · Posición {$position}";
                    $location->meta = $meta;
                    $location->save();

                    $created++;
                }
            }
        }

        return response()->json([
            'ok' => true,
            'created' => $created,
            'skipped' => $skipped,
            'structure' => [
                'rack_count' => self::RACK_COUNT,
                'level_count' => self::LEVEL_COUNT,
                'positions' => $positions,
            ],
        ]);
    }

    public function heatmapData(Request $r)
    {
        $data = $r->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'metric' => ['nullable', 'in:inv_qty,primary_stock'],
        ]);

        $warehouseId = (int) $data['warehouse_id'];
        $metric = (string) ($data['metric'] ?? 'inv_qty');

        $rows = $this->buildLocationsForWarehouse($warehouseId);
        $locationIds = $rows->pluck('id')->filter()->values();

        $inventoryMap = collect();
        if (
            Schema::hasTable('inventories') &&
            Schema::hasColumn('inventories', 'location_id') &&
            Schema::hasColumn('inventories', 'qty')
        ) {
            $inventoryMap = Inventory::query()
                ->whereIn('location_id', $locationIds)
                ->selectRaw('location_id, SUM(qty) as qty')
                ->groupBy('location_id')
                ->pluck('qty', 'location_id');
        }

        $primaryMap = collect();
        if (
            Schema::hasTable('catalog_items') &&
            Schema::hasColumn('catalog_items', 'primary_location_id') &&
            Schema::hasColumn('catalog_items', 'stock')
        ) {
            $primaryMap = CatalogItem::query()
                ->whereNotNull('primary_location_id')
                ->whereIn('primary_location_id', $locationIds)
                ->selectRaw('primary_location_id as location_id, SUM(stock) as stock')
                ->groupBy('primary_location_id')
                ->pluck('stock', 'location_id');
        }

        $productMap = [];
        if (
            Schema::hasTable('catalog_items') &&
            Schema::hasColumn('catalog_items', 'primary_location_id') &&
            Schema::hasColumn('catalog_items', 'name')
        ) {
            $cols = ['primary_location_id', 'name'];

            if (Schema::hasColumn('catalog_items', 'sku')) {
                $cols[] = 'sku';
            }

            $items = CatalogItem::query()
                ->whereNotNull('primary_location_id')
                ->whereIn('primary_location_id', $locationIds)
                ->orderBy('id')
                ->get($cols);

            foreach ($items as $item) {
                $locId = (int) $item->primary_location_id;

                if (!isset($productMap[$locId])) {
                    $productMap[$locId] = [
                        'product_name' => (string) ($item->name ?? ''),
                        'product_sku' => (string) ($item->sku ?? ''),
                        'product_count' => 1,
                    ];
                } else {
                    $productMap[$locId]['product_count']++;
                }
            }
        }

        $rackLocationMap = [];
        $zoneLocationMap = [];

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $invQty = (int) ($inventoryMap[$id] ?? 0);
            $primaryStock = (int) ($primaryMap[$id] ?? 0);
            $value = $metric === 'primary_stock' ? $primaryStock : $invQty;
            $capacity = (int) ($row['meta']['max_capacity'] ?? $row['meta']['capacity'] ?? 100);
            $product = $productMap[$id] ?? [
                'product_name' => '',
                'product_sku' => '',
                'product_count' => 0,
            ];

            $payload = [
                'id' => $id,
                'location_id' => $id,
                'placeholder' => false,
                'code' => $row['code'],
                'location_code' => $row['code'],
                'name' => $row['name'],
                'display_name' => $row['name'],
                'type' => $row['type'],
                'zone' => $row['zone'],
                'zone_label' => $row['zone_label'],
                'aisle' => $row['aisle'],
                'section' => $row['section'],
                'stand' => $row['stand'],
                'rack' => $row['rack'],
                'rack_key' => $row['rack_key'],
                'rack_number' => $this->toIndex($row['rack'], 0),
                'level' => (int) $row['level'],
                'position' => (int) $row['position'],
                'bin' => $row['bin'],
                'x' => (int) ($row['meta']['x'] ?? 0),
                'y' => (int) ($row['meta']['y'] ?? 0),
                'w' => max(1, (int) ($row['meta']['w'] ?? 1)),
                'h' => max(1, (int) ($row['meta']['h'] ?? 1)),
                'value' => $value,
                'inv_qty' => $invQty,
                'primary_stock' => $primaryStock,
                'max_capacity' => $capacity,
                'capacity' => $capacity,
                'status' => $this->resolveStatus($row, $value, $capacity),
                'product_name' => $product['product_name'],
                'product_sku' => $product['product_sku'],
                'product_count' => (int) $product['product_count'],
                'notes' => (string) ($row['meta']['notes'] ?? ''),
                'color' => $row['meta']['color'] ?? null,
                'inferred' => (bool) $row['inferred'],
            ];

            if ($row['zone'] === 'rack') {
                $rack = (int) $payload['rack_number'];
                $level = (int) $payload['level'];
                $position = (int) $payload['position'];

                if (
                    $rack >= 1 && $rack <= self::RACK_COUNT &&
                    $level >= 1 && $level <= self::LEVEL_COUNT &&
                    $position >= 1
                ) {
                    $rackLocationMap[$this->slotKey($rack, $level, $position)] = $payload;
                }
            } elseif (in_array($row['zone'], self::UNIQUE_ZONES, true)) {
                if (!isset($zoneLocationMap[$row['zone']])) {
                    $payload['display_boxes'] = $this->zoneDisplayBoxes($value, $capacity);
                    $zoneLocationMap[$row['zone']] = $payload;
                }
            }
        }

        $maxExistingPosition = collect($rackLocationMap)->map(fn ($row) => (int) ($row['position'] ?? 0))->max() ?? 0;
        $visiblePositions = max(self::MIN_VISIBLE_POSITIONS, (int) $maxExistingPosition);

        $slots = [];
        for ($rack = 1; $rack <= self::RACK_COUNT; $rack++) {
            for ($level = 1; $level <= self::LEVEL_COUNT; $level++) {
                for ($position = 1; $position <= $visiblePositions; $position++) {
                    $key = $this->slotKey($rack, $level, $position);

                    if (isset($rackLocationMap[$key])) {
                        $slots[] = $rackLocationMap[$key];
                    } else {
                        $meta = $this->buildSyntheticMetaForRack($rack, $level, $position);

                        $slots[] = [
                            'id' => null,
                            'location_id' => null,
                            'placeholder' => true,
                            'code' => $this->buildRackCode($rack, $level, $position),
                            'location_code' => $this->buildRackCode($rack, $level, $position),
                            'name' => "Slot disponible {$rack}-{$level}-{$position}",
                            'display_name' => "Slot disponible {$rack}-{$level}-{$position}",
                            'type' => 'bin',
                            'zone' => 'rack',
                            'zone_label' => 'Rack',
                            'aisle' => null,
                            'section' => null,
                            'stand' => null,
                            'rack' => (string) $rack,
                            'rack_key' => 'RACK-' . $rack,
                            'rack_number' => $rack,
                            'level' => $level,
                            'position' => $position,
                            'bin' => (string) $position,
                            'x' => (int) $meta['x'],
                            'y' => (int) $meta['y'],
                            'w' => (int) $meta['w'],
                            'h' => (int) $meta['h'],
                            'value' => 0,
                            'inv_qty' => 0,
                            'primary_stock' => 0,
                            'max_capacity' => 0,
                            'capacity' => 0,
                            'status' => 'empty',
                            'product_name' => '',
                            'product_sku' => '',
                            'product_count' => 0,
                            'notes' => '',
                            'color' => null,
                            'inferred' => false,
                        ];
                    }
                }
            }
        }

        $zones = [];
        foreach (self::UNIQUE_ZONES as $zone) {
            if (isset($zoneLocationMap[$zone])) {
                $zones[] = $zoneLocationMap[$zone];
            } else {
                $meta = $this->buildSyntheticMetaForZone($zone, 1);

                $zones[] = [
                    'id' => null,
                    'location_id' => null,
                    'placeholder' => true,
                    'code' => '—',
                    'location_code' => '—',
                    'name' => $this->zoneLabel($zone),
                    'display_name' => $this->zoneLabel($zone),
                    'type' => $zone,
                    'zone' => $zone,
                    'zone_label' => $this->zoneLabel($zone),
                    'aisle' => null,
                    'section' => null,
                    'stand' => null,
                    'rack' => null,
                    'rack_key' => null,
                    'rack_number' => 0,
                    'level' => 0,
                    'position' => 0,
                    'bin' => null,
                    'x' => (int) $meta['x'],
                    'y' => (int) $meta['y'],
                    'w' => (int) $meta['w'],
                    'h' => (int) $meta['h'],
                    'value' => 0,
                    'inv_qty' => 0,
                    'primary_stock' => 0,
                    'max_capacity' => 0,
                    'capacity' => 0,
                    'status' => 'empty',
                    'product_name' => '',
                    'product_sku' => '',
                    'product_count' => 0,
                    'notes' => '',
                    'color' => null,
                    'inferred' => false,
                    'display_boxes' => 0,
                ];
            }
        }

        $realCells = collect($rackLocationMap)->values()->concat(collect($zoneLocationMap)->values())->values();
        $nextSlot = $this->findNextAvailableSlot($rows);

        return response()->json([
            'ok' => true,
            'metric' => $metric,
            'max' => (int) ($realCells->max('value') ?? 0),
            'positions_per_rack' => $visiblePositions,
            'structure' => [
                'rack_count' => self::RACK_COUNT,
                'level_count' => self::LEVEL_COUNT,
                'visible_positions' => $visiblePositions,
            ],
            'summary' => [
                'real_rack_locations' => count($rackLocationMap),
                'real_zone_locations' => count($zoneLocationMap),
                'total_real_locations' => count($rackLocationMap) + count($zoneLocationMap),
                'next_slot' => $nextSlot,
            ],
            'cells' => $realCells->values(),
            'slots' => collect($slots)->values(),
            'zones' => collect($zones)->values(),
        ]);
    }

    private function buildLocationsForWarehouse(int $warehouseId): Collection
    {
        $locations = Location::query()
            ->where('warehouse_id', $warehouseId)
            ->orderBy('id')
            ->get();

        return $locations->map(function (Location $loc) {
            $type = $this->normalizeType($loc->type);
            $zone = $this->resolveZone($loc, $type);

            $slot = $this->extractSlotFromLocation($loc);
            $rack = $zone === 'rack' ? $slot['rack'] : 0;
            $level = $zone === 'rack' ? $slot['level'] : 0;
            $position = $zone === 'rack' ? $slot['position'] : 0;

            $meta = $this->normalizeMeta($loc->meta);
            $meta = $this->decorateMetaForHeatmap($zone, $meta, $rack, $level, $position);

            return [
                'id' => (int) $loc->id,
                'warehouse_id' => (int) $loc->warehouse_id,
                'type' => $type,
                'zone' => $zone,
                'zone_label' => $this->zoneLabel($zone),
                'code' => (string) $loc->code,
                'name' => (string) ($loc->name ?: $loc->code),
                'aisle' => filled($loc->aisle) ? (string) $loc->aisle : null,
                'section' => filled($loc->section) ? (string) $loc->section : null,
                'stand' => filled($loc->stand) ? (string) $loc->stand : null,
                'rack' => $zone === 'rack' && $rack > 0 ? (string) $rack : null,
                'rack_key' => $zone === 'rack' && $rack > 0 ? ('RACK-' . $rack) : (string) $loc->code,
                'level' => $zone === 'rack' ? $level : 0,
                'bin' => $zone === 'rack' && $position > 0 ? (string) $position : null,
                'position' => $zone === 'rack' ? $position : 0,
                'meta' => $meta,
                'inferred' => (bool) $slot['inferred'],
            ];
        })->sortBy(function (array $row) {
            if ($row['zone'] === 'rack') {
                return sprintf(
                    'rack-%02d-%02d-%06d-%06d',
                    (int) ($row['rack'] ?? 0),
                    (int) ($row['level'] ?? 0),
                    (int) ($row['position'] ?? 0),
                    (int) $row['id']
                );
            }

            return sprintf('%s-%06d', $row['zone'], (int) $row['id']);
        })->values();
    }

    private function serializeLocation(Location $loc): array
    {
        $type = $this->normalizeType($loc->type);
        $zone = $this->resolveZone($loc, $type);

        $slot = $this->extractSlotFromLocation($loc);
        $rack = $zone === 'rack' ? $slot['rack'] : 0;
        $level = $zone === 'rack' ? $slot['level'] : 0;
        $position = $zone === 'rack' ? $slot['position'] : 0;

        $meta = $this->normalizeMeta($loc->meta);
        $meta = $this->decorateMetaForHeatmap($zone, $meta, $rack, $level, $position);

        return [
            'id' => (int) $loc->id,
            'warehouse_id' => (int) $loc->warehouse_id,
            'type' => $type,
            'zone' => $zone,
            'zone_label' => $this->zoneLabel($zone),
            'code' => (string) $loc->code,
            'name' => (string) ($loc->name ?: $loc->code),
            'aisle' => filled($loc->aisle) ? (string) $loc->aisle : null,
            'section' => filled($loc->section) ? (string) $loc->section : null,
            'stand' => filled($loc->stand) ? (string) $loc->stand : null,
            'rack' => $zone === 'rack' && $rack > 0 ? (string) $rack : null,
            'rack_key' => $zone === 'rack' && $rack > 0 ? ('RACK-' . $rack) : (string) $loc->code,
            'level' => $zone === 'rack' ? $level : 0,
            'bin' => $zone === 'rack' && $position > 0 ? (string) $position : null,
            'position' => $zone === 'rack' ? $position : 0,
            'meta' => $meta,
            'inferred' => (bool) $slot['inferred'],
        ];
    }

    private function buildAvailableSlots(Collection $locations, ?int $ignoreLocationId = null): array
    {
        $used = [];

        foreach ($locations as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($ignoreLocationId && $id === $ignoreLocationId) {
                continue;
            }

            if (($row['zone'] ?? '') !== 'rack') {
                continue;
            }

            $rack = (int) ($row['rack'] ?? 0);
            $level = (int) ($row['level'] ?? 0);
            $position = (int) ($row['position'] ?? 0);

            if ($rack < 1 || $level < 1 || $position < 1) {
                continue;
            }

            $used[$rack][$level][$position] = true;
        }

        $slots = [];

        for ($rack = 1; $rack <= self::RACK_COUNT; $rack++) {
            for ($level = 1; $level <= self::LEVEL_COUNT; $level++) {
                $positions = array_keys($used[$rack][$level] ?? []);
                sort($positions);

                $nextPosition = 1;
                foreach ($positions as $pos) {
                    $pos = (int) $pos;
                    if ($pos === $nextPosition) {
                        $nextPosition++;
                    } elseif ($pos > $nextPosition) {
                        break;
                    }
                }

                $meta = $this->buildSyntheticMetaForRack($rack, $level, $nextPosition);

                $slots[] = [
                    'rack' => $rack,
                    'level' => $level,
                    'position' => $nextPosition,
                    'code' => $this->buildRackCode($rack, $level, $nextPosition),
                    'label' => "Rack {$rack} · Nivel {$level} · Posición {$nextPosition}",
                    'x' => $meta['x'],
                    'y' => $meta['y'],
                    'w' => $meta['w'],
                    'h' => $meta['h'],
                ];
            }
        }

        return $slots;
    }

    private function buildAvailableZones(Collection $locations, ?int $ignoreLocationId = null): array
    {
        $used = [];

        foreach ($locations as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($ignoreLocationId && $id === $ignoreLocationId) {
                continue;
            }

            $zone = (string) ($row['zone'] ?? '');
            if (in_array($zone, self::UNIQUE_ZONES, true)) {
                $used[$zone] = true;
            }
        }

        $zones = [];

        foreach (self::UNIQUE_ZONES as $zone) {
            $meta = $this->buildSyntheticMetaForZone($zone, 1);

            $zones[] = [
                'type' => $zone,
                'zone' => $zone,
                'zone_label' => $this->zoneLabel($zone),
                'code' => 'ZONE-' . Str::upper($zone),
                'available' => !isset($used[$zone]),
                'x' => $meta['x'],
                'y' => $meta['y'],
                'w' => $meta['w'],
                'h' => $meta['h'],
            ];
        }

        return $zones;
    }

    private function findNextAvailableSlot(Collection $locations, ?int $ignoreLocationId = null): array
    {
        $used = [];

        foreach ($locations as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($ignoreLocationId && $id === $ignoreLocationId) {
                continue;
            }

            if (($row['zone'] ?? '') !== 'rack') {
                continue;
            }

            $rack = (int) ($row['rack'] ?? 0);
            $level = (int) ($row['level'] ?? 0);
            $position = (int) ($row['position'] ?? 0);

            if (
                $rack < 1 || $rack > self::RACK_COUNT ||
                $level < 1 || $level > self::LEVEL_COUNT ||
                $position < 1
            ) {
                continue;
            }

            $used[$rack][$level][$position] = true;
        }

        $best = null;

        for ($rack = 1; $rack <= self::RACK_COUNT; $rack++) {
            for ($level = 1; $level <= self::LEVEL_COUNT; $level++) {
                $positions = array_keys($used[$rack][$level] ?? []);
                sort($positions);

                $nextPosition = 1;
                foreach ($positions as $pos) {
                    $pos = (int) $pos;
                    if ($pos === $nextPosition) {
                        $nextPosition++;
                    } elseif ($pos > $nextPosition) {
                        break;
                    }
                }

                $usedCount = count($positions);

                $candidate = [
                    'rack' => $rack,
                    'level' => $level,
                    'position' => $nextPosition,
                    'used_count' => $usedCount,
                    'code' => $this->buildRackCode($rack, $level, $nextPosition),
                    'label' => "Rack {$rack} · Nivel {$level} · Posición {$nextPosition}",
                ];

                if ($best === null) {
                    $best = $candidate;
                    continue;
                }

                if ($candidate['used_count'] < $best['used_count']) {
                    $best = $candidate;
                    continue;
                }

                if ($candidate['used_count'] === $best['used_count']) {
                    if ($candidate['rack'] < $best['rack']) {
                        $best = $candidate;
                        continue;
                    }

                    if ($candidate['rack'] === $best['rack'] && $candidate['level'] < $best['level']) {
                        $best = $candidate;
                    }
                }
            }
        }

        return $best ?? [
            'rack' => 1,
            'level' => 1,
            'position' => 1,
            'used_count' => 0,
            'code' => $this->buildRackCode(1, 1, 1),
            'label' => 'Rack 1 · Nivel 1 · Posición 1',
        ];
    }

    private function extractSlotFromLocation(Location $loc): array
    {
        $rack = $this->toIndex($loc->rack, 0);
        $level = $this->toIndex($loc->level, 0);
        $position = $this->toIndex($loc->bin, 0);
        $inferred = false;

        if ($rack < 1 || $level < 1 || $position < 1) {
            $code = (string) $loc->code;

            if (preg_match('/R[\-\s_]?(\d+).*L[\-\s_]?(\d+).*(?:P|B)[\-\s_]?(\d+)/i', $code, $m)) {
                $rack = $rack > 0 ? $rack : (int) $m[1];
                $level = $level > 0 ? $level : (int) $m[2];
                $position = $position > 0 ? $position : (int) $m[3];
                $inferred = true;
            }
        }

        return [
            'rack' => $rack,
            'level' => $level,
            'position' => $position,
            'inferred' => $inferred,
        ];
    }

    private function normalizeType(?string $type): string
    {
        $type = Str::slug((string) $type, '_');

        return match ($type) {
            'pick', 'picker', 'picking_area' => 'picking',
            'fastflow', 'fast-flow', 'fast_flow_area' => 'fast_flow',
            'receiving', 'receiver', 'recepcion', 'entrante' => 'incoming',
            'shipping', 'dispatch_area', 'despacho', 'embarque' => 'dispatch',
            'rack', 'slot', 'location', 'ubicacion', 'almacenamiento', 'bin' => 'bin',
            default => $type !== '' ? $type : 'bin',
        };
    }

    private function resolveZone(Location $loc, string $type): string
    {
        if (in_array($type, self::UNIQUE_ZONES, true)) {
            return $type;
        }

        if (
            filled($loc->rack) ||
            filled($loc->level) ||
            filled($loc->bin) ||
            in_array($type, ['bin', 'rack', 'slot', 'location'], true)
        ) {
            return 'rack';
        }

        $blob = Str::slug(trim(
            implode(' ', array_filter([
                $loc->type,
                $loc->code,
                $loc->name,
                $loc->section,
            ]))
        ), '_');

        if (str_contains($blob, 'fast_flow') || str_contains($blob, 'fastflow')) {
            return 'fast_flow';
        }

        if (str_contains($blob, 'picking') || str_contains($blob, 'pick')) {
            return 'picking';
        }

        if (str_contains($blob, 'incoming') || str_contains($blob, 'recepcion') || str_contains($blob, 'entrante')) {
            return 'incoming';
        }

        if (str_contains($blob, 'dispatch') || str_contains($blob, 'despacho') || str_contains($blob, 'embarque')) {
            return 'dispatch';
        }

        return 'rack';
    }

    private function zoneLabel(string $zone): string
    {
        return match ($zone) {
            'rack' => 'Rack',
            'picking' => 'Picking',
            'fast_flow' => 'Fast Flow',
            'incoming' => 'Entrante',
            'dispatch' => 'Despacho',
            default => 'General',
        };
    }

    private function buildRackCode(int $rack, int $level, int $position, ?string $prefix = null, ?string $stand = null): string
    {
        if ($prefix) {
            $standPart = filled($stand) ? ('-' . str_pad((string) $stand, 2, '0', STR_PAD_LEFT)) : '';
            return Str::upper($prefix)
                . $standPart
                . '-R' . str_pad((string) $rack, 2, '0', STR_PAD_LEFT)
                . '-L' . str_pad((string) $level, 2, '0', STR_PAD_LEFT)
                . '-P' . str_pad((string) $position, 3, '0', STR_PAD_LEFT);
        }

        return 'R' . str_pad((string) $rack, 2, '0', STR_PAD_LEFT)
            . '-L' . str_pad((string) $level, 2, '0', STR_PAD_LEFT)
            . '-P' . str_pad((string) $position, 3, '0', STR_PAD_LEFT);
    }

    private function slotKey(int $rack, int $level, int $position): string
    {
        return $rack . '-' . $level . '-' . $position;
    }

    private function normalizeMeta(mixed $meta): array
    {
        if (is_array($meta)) {
            return $meta;
        }

        if (is_string($meta) && trim($meta) !== '') {
            $decoded = json_decode($meta, true);
            return is_array($decoded) ? $decoded : [];
        }

        if ($meta instanceof \JsonSerializable) {
            $data = $meta->jsonSerialize();
            return is_array($data) ? $data : [];
        }

        return [];
    }

    private function toIndex(mixed $value, int $default = 0): int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $default;
        }

        if (is_numeric($value)) {
            return max($default, (int) $value);
        }

        if (preg_match('/(\d+)/', $value, $m)) {
            return max($default, (int) $m[1]);
        }

        return $default;
    }

    private function resolveStatus(array $row, int $value, int $capacity): string
    {
        $metaStatus = Str::slug((string) ($row['meta']['status'] ?? ''), '_');

        if ($metaStatus !== '') {
            return $metaStatus;
        }

        if ($value <= 0) {
            return 'empty';
        }

        if ($capacity <= 0) {
            return 'occupied';
        }

        $ratio = $value / max(1, $capacity);

        return match (true) {
            $ratio >= 1 => 'full',
            $ratio >= 0.85 => 'high',
            $ratio >= 0.50 => 'medium',
            default => 'low',
        };
    }

    private function decorateMetaForHeatmap(string $zone, array $meta, int $rack, int $level, int $position): array
    {
        $fallback = $zone === 'rack'
            ? $this->buildSyntheticMetaForRack($rack, $level, $position)
            : $this->buildSyntheticMetaForZone($zone, 1);

        return [
            'x' => (int) ($meta['x'] ?? $fallback['x']),
            'y' => (int) ($meta['y'] ?? $fallback['y']),
            'w' => max(1, (int) ($meta['w'] ?? $fallback['w'])),
            'h' => max(1, (int) ($meta['h'] ?? $fallback['h'])),
            'capacity' => (int) ($meta['capacity'] ?? $meta['max_capacity'] ?? 100),
            'max_capacity' => (int) ($meta['max_capacity'] ?? $meta['capacity'] ?? 100),
            'notes' => $meta['notes'] ?? null,
            'color' => $meta['color'] ?? null,
            'status' => $meta['status'] ?? null,
            'created_from' => $meta['created_from'] ?? null,
        ];
    }

    private function buildSyntheticMetaForRack(int $rack, int $level, int $position): array
    {
        $rack = max(1, $rack);
        $level = max(1, $level);
        $position = max(1, $position);

        return [
            'x' => 80 + (($rack - 1) * 640) + (($position - 1) * 56),
            'y' => 80 + (($level - 1) * 42),
            'w' => 48,
            'h' => 30,
        ];
    }

    private function buildSyntheticMetaForZone(string $zone, int $index = 1): array
    {
        $laneMap = [
            'incoming' => 610,
            'fast_flow' => 520,
            'picking' => 430,
            'dispatch' => 700,
        ];

        $laneY = (int) ($laneMap[$zone] ?? 790);
        $idx = max(0, $index - 1);

        return [
            'x' => 80 + (($idx % 10) * 74),
            'y' => $laneY + ((int) floor($idx / 10) * 56),
            'w' => 64,
            'h' => 42,
        ];
    }

    private function zoneDisplayBoxes(int $value, int $capacity): int
    {
        if ($value <= 0) {
            return 0;
        }

        if ($capacity <= 0) {
            return min(10, max(1, (int) ceil($value / 10)));
        }

        $boxes = (int) ceil(($value / max(1, $capacity)) * 8);

        return max(1, min(10, $boxes));
    }

    private function ensureUniqueCode(string $baseCode, ?int $ignoreId = null): string
    {
        $baseCode = Str::upper(trim($baseCode));
        $code = $baseCode;
        $i = 1;

        while (
            Location::query()
                ->whereRaw('UPPER(code) = ?', [$code])
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $code = $baseCode . '-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $i++;
        }

        return $code;
    }
}