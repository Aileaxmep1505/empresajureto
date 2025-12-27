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
use Illuminate\Support\Str;

class WmsLayoutController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [ new Middleware('auth') ];
    }

    public function editor(Request $r)
    {
        $warehouses = Warehouse::query()->orderBy('id')->get(['id','name']);
        $warehouseId = (int)($r->get('warehouse_id') ?: ($warehouses->first()->id ?? 1));

        return view('admin.wms.layout', compact('warehouses', 'warehouseId'));
    }

    public function heatmap(Request $r)
    {
        $warehouses = Warehouse::query()->orderBy('id')->get(['id','name']);
        $warehouseId = (int)($r->get('warehouse_id') ?: ($warehouses->first()->id ?? 1));

        return view('admin.wms.heatmap', compact('warehouses', 'warehouseId'));
    }

    /**
     * Devuelve locations con meta (x,y,w,h) para dibujar el plano.
     */
    public function data(Request $r)
    {
        $data = $r->validate([
            'warehouse_id' => ['required','integer','exists:warehouses,id'],
        ]);

        $locs = Location::query()
            ->where('warehouse_id', (int)$data['warehouse_id'])
            ->orderBy('id')
            ->get();

        return response()->json([
            'ok' => true,
            'locations' => $locs->map(fn($l) => [
                'id' => $l->id,
                'type' => $l->type,
                'code' => $l->code,
                'name' => $l->name,
                'aisle' => $l->aisle,
                'section' => $l->section,
                'stand' => $l->stand,
                'rack' => $l->rack,
                'level' => $l->level,
                'bin' => $l->bin,
                'meta' => $l->meta ?: [],
            ])->values(),
        ]);
    }

    /**
     * Crear o actualizar una “celda” (ubicación) del layout.
     * meta: {x,y,w,h, color?, notes?}
     */
    public function upsertCell(Request $r)
    {
        $data = $r->validate([
            'warehouse_id' => ['required','integer','exists:warehouses,id'],
            'id' => ['nullable','integer','exists:locations,id'],
            'type' => ['required','string','max:30'],
            'code' => ['required','string','max:80'],
            'name' => ['nullable','string','max:120'],

            'aisle' => ['nullable','string','max:20'],
            'section' => ['nullable','string','max:20'],
            'stand' => ['nullable','string','max:20'],
            'rack' => ['nullable','string','max:20'],
            'level' => ['nullable','string','max:20'],
            'bin' => ['nullable','string','max:20'],

            'meta' => ['nullable','array'],
            'meta.x' => ['nullable','integer','min:0','max:9999'],
            'meta.y' => ['nullable','integer','min:0','max:9999'],
            'meta.w' => ['nullable','integer','min:1','max:9999'],
            'meta.h' => ['nullable','integer','min:1','max:9999'],
            'meta.color' => ['nullable','string','max:40'],
            'meta.notes' => ['nullable','string','max:500'],
        ]);

        // evitar duplicados por warehouse
        $exists = Location::query()
            ->where('warehouse_id', (int)$data['warehouse_id'])
            ->where('code', $data['code'])
            ->when(!empty($data['id']), fn($q) => $q->where('id', '!=', (int)$data['id']))
            ->exists();

        if ($exists) {
            return response()->json(['ok'=>false,'error'=>'Ese código ya existe en esta bodega.'], 422);
        }

        $loc = !empty($data['id'])
            ? Location::findOrFail((int)$data['id'])
            : new Location();

        $loc->warehouse_id = (int)$data['warehouse_id'];
        $loc->type = $data['type'];
        $loc->code = $data['code'];
        $loc->name = $data['name'] ?? null;

        $loc->aisle = $data['aisle'] ?? null;
        $loc->section = $data['section'] ?? null;
        $loc->stand = $data['stand'] ?? null;
        $loc->rack = $data['rack'] ?? null;
        $loc->level = $data['level'] ?? null;
        $loc->bin = $data['bin'] ?? null;

        $loc->meta = $data['meta'] ?? ($loc->meta ?: []);
        $loc->save();

        return response()->json(['ok'=>true,'location'=>[
            'id'=>$loc->id,'code'=>$loc->code,'type'=>$loc->type,'meta'=>$loc->meta
        ]]);
    }

    /**
     * Generador rápido de racks:
     * crea N ubicaciones con posiciones en grid (x,y) automático.
     */
    public function generateRack(Request $r)
    {
        $data = $r->validate([
            'warehouse_id' => ['required','integer','exists:warehouses,id'],
            'prefix' => ['required','string','max:20'], // ej "A"
            'stand' => ['nullable','string','max:20'],  // ej "01"
            'rack_count' => ['required','integer','min:1','max:200'],
            'levels' => ['required','integer','min:1','max:10'],
            'bins' => ['required','integer','min:1','max:10'],

            'start_x' => ['required','integer','min:0','max:9999'],
            'start_y' => ['required','integer','min:0','max:9999'],
            'cell_w' => ['required','integer','min:1','max:9999'],
            'cell_h' => ['required','integer','min:1','max:9999'],
            'gap_x' => ['required','integer','min:0','max:9999'],
            'gap_y' => ['required','integer','min:0','max:9999'],
            'direction' => ['required','in:right,down'],
        ]);

        $created = 0;
        $warehouseId = (int)$data['warehouse_id'];

        $x = (int)$data['start_x'];
        $y = (int)$data['start_y'];

        for ($rIdx=1; $rIdx <= (int)$data['rack_count']; $rIdx++) {
            for ($lvl=1; $lvl <= (int)$data['levels']; $lvl++) {
                for ($bin=1; $bin <= (int)$data['bins']; $bin++) {
                    // Código humano: A-01-R03-L02-B01
                    $stand = $data['stand'] ? str_pad((string)$data['stand'], 2, '0', STR_PAD_LEFT) : '00';
                    $code = "{$data['prefix']}-{$stand}-R".str_pad((string)$rIdx,2,'0',STR_PAD_LEFT)
                          ."-L".str_pad((string)$lvl,2,'0',STR_PAD_LEFT)
                          ."-B".str_pad((string)$bin,2,'0',STR_PAD_LEFT);

                    // si ya existe, saltar
                    $exists = Location::query()
                        ->where('warehouse_id', $warehouseId)
                        ->where('code', $code)
                        ->exists();

                    if ($exists) continue;

                    $loc = new Location();
                    $loc->warehouse_id = $warehouseId;
                    $loc->type = 'bin';
                    $loc->code = $code;
                    $loc->aisle = $data['prefix'];
                    $loc->stand = $stand;
                    $loc->rack = str_pad((string)$rIdx,2,'0',STR_PAD_LEFT);
                    $loc->level = str_pad((string)$lvl,2,'0',STR_PAD_LEFT);
                    $loc->bin = str_pad((string)$bin,2,'0',STR_PAD_LEFT);

                    $loc->meta = [
                        'x' => $x,
                        'y' => $y,
                        'w' => (int)$data['cell_w'],
                        'h' => (int)$data['cell_h'],
                    ];

                    $loc->save();
                    $created++;
                }
            }

            // mover a siguiente rack
            if ($data['direction'] === 'right') {
                $x += (int)$data['cell_w'] + (int)$data['gap_x'];
            } else {
                $y += (int)$data['cell_h'] + (int)$data['gap_y'];
            }
        }

        return response()->json(['ok'=>true,'created'=>$created]);
    }

    /**
     * Heatmap data:
     * - inv_qty: suma Inventory.qty por location
     * - primary_stock: suma CatalogItem.stock por primary_location_id
     */
    public function heatmapData(Request $r)
    {
        $data = $r->validate([
            'warehouse_id' => ['required','integer','exists:warehouses,id'],
            'metric' => ['nullable','in:inv_qty,primary_stock'],
        ]);

        $warehouseId = (int)$data['warehouse_id'];
        $metric = $data['metric'] ?? 'primary_stock';

        $locs = Location::query()
            ->where('warehouse_id', $warehouseId)
            ->get(['id','code','type','meta']);

        // agregados
        $inv = Inventory::query()
            ->whereHas('location', fn($q)=>$q->where('warehouse_id',$warehouseId))
            ->selectRaw('location_id, SUM(qty) as qty')
            ->groupBy('location_id')
            ->pluck('qty','location_id');

        $primary = CatalogItem::query()
            ->whereNotNull('primary_location_id')
            ->whereIn('primary_location_id', $locs->pluck('id'))
            ->selectRaw('primary_location_id as location_id, SUM(stock) as stock')
            ->groupBy('primary_location_id')
            ->pluck('stock','location_id');

        $cells = $locs->map(function($l) use ($inv, $primary, $metric) {
            $m = $l->meta ?: [];
            $val = 0;
            if ($metric === 'inv_qty') {
                $val = (int)($inv[$l->id] ?? 0);
            } else {
                $val = (int)($primary[$l->id] ?? 0);
            }

            return [
                'id' => $l->id,
                'code' => $l->code,
                'type' => $l->type,
                'x' => (int)($m['x'] ?? 0),
                'y' => (int)($m['y'] ?? 0),
                'w' => (int)($m['w'] ?? 1),
                'h' => (int)($m['h'] ?? 1),
                'value' => $val,
            ];
        })->values();

        $max = (int)($cells->max('value') ?? 0);

        return response()->json([
            'ok' => true,
            'metric' => $metric,
            'max' => $max,
            'cells' => $cells,
        ]);
    }
}
