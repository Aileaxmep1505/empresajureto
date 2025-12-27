<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Inventory;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WmsSearchController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [ new Middleware('auth') ];
    }

    /**
     * GET /admin/wms/search/products?q=...&warehouse_id=...&from_code=...
     * Devuelve productos con ubicaciones y un "recommended_location" + "nav".
     */
    public function products(Request $r)
    {
        $data = $r->validate([
            'q' => ['required','string','max:255'],
            'warehouse_id' => ['nullable','integer','exists:warehouses,id'],
            'from_code' => ['nullable','string','max:80'],
            'limit' => ['nullable','integer','min:1','max:50'],
        ]);

        $limit = (int)($data['limit'] ?? 20);
        $q = trim($data['q']);

        $fromLoc = null;
        if (!empty($data['from_code'])) {
            $fromLoc = Location::where('code', $data['from_code'])->first();
        }

        $items = CatalogItem::query()
            ->select(['id','name','sku','price','primary_location_id','brand_name','model_name','meli_gtin'])
            ->where(function($qq) use ($q) {
                $qq->where('name','like',"%{$q}%")
                   ->orWhere('sku','like',"%{$q}%")
                   ->orWhere('meli_gtin','like',"%{$q}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();

        // Preload primary locations
        $primaryLocIds = $items->pluck('primary_location_id')->filter()->unique()->values()->all();
        $primaryLocs = Location::whereIn('id', $primaryLocIds)->get()->keyBy('id');

        $out = [];
        foreach ($items as $it) {
            // Ubicaciones con stock (y opcionalmente filtrado por warehouse)
            $invQ = Inventory::query()
                ->where('catalog_item_id', $it->id)
                ->where('qty','>',0)
                ->with('location:id,warehouse_id,code,aisle,section,stand,rack,level,bin,type');

            if (!empty($data['warehouse_id'])) {
                $invQ->whereHas('location', fn($lq) => $lq->where('warehouse_id', (int)$data['warehouse_id']));
            }

            $rows = $invQ->get();

            $locations = $rows->map(function($row){
                return [
                    'location_id' => $row->location_id,
                    'code' => $row->location->code ?? null,
                    'qty'  => (int)$row->qty,
                ];
            })->values();

            $totalQty = $rows->sum('qty');

            // recommended: 1) primary si tiene stock, 2) mayor stock, 3) null
            $recommended = null;

            if ($it->primary_location_id) {
                $primaryRow = $rows->firstWhere('location_id', $it->primary_location_id);
                if ($primaryRow) {
                    $recommended = [
                        'location_id' => $primaryRow->location_id,
                        'code' => $primaryRow->location->code,
                        'qty' => (int)$primaryRow->qty,
                        'reason' => 'primary_with_stock',
                    ];
                }
            }

            if (!$recommended && $rows->count()) {
                $best = $rows->sortByDesc('qty')->first();
                $recommended = [
                    'location_id' => $best->location_id,
                    'code' => $best->location->code,
                    'qty' => (int)$best->qty,
                    'reason' => 'max_stock',
                ];
            }

            $nav = null;
            if ($fromLoc && $recommended) {
                $toLoc = Location::find($recommended['location_id']);
                if ($toLoc) {
                    $nav = $this->buildNav($fromLoc, $toLoc);
                }
            }

            $primary = $it->primary_location_id ? ($primaryLocs[$it->primary_location_id] ?? null) : null;

            $out[] = [
                'id' => $it->id,
                'name' => $it->name,
                'sku' => $it->sku,
                'price' => $it->price,
                'meli_gtin' => $it->meli_gtin,
                'primary_location' => $primary ? [
                    'id' => $primary->id,
                    'code' => $primary->code,
                ] : null,
                'total_qty' => (int)$totalQty,
                'locations' => $locations,
                'recommended_location' => $recommended,
                'nav' => $nav, // <- esto alimenta el botón “Llévame”
            ];
        }

        return response()->json([
            'ok' => true,
            'from' => $fromLoc ? ['id'=>$fromLoc->id,'code'=>$fromLoc->code] : null,
            'results' => $out,
        ]);
    }

    /**
     * GET /admin/wms/nav?from_code=A-01...&to_code=B-03...
     * Devuelve instrucciones simples (ruta por pasillo/sección).
     */
    public function nav(Request $r)
    {
        $data = $r->validate([
            'from_code' => ['required','string','max:80'],
            'to_code' => ['required','string','max:80'],
        ]);

        $from = Location::where('code', $data['from_code'])->first();
        $to   = Location::where('code', $data['to_code'])->first();

        if (!$from || !$to) {
            return response()->json(['ok'=>false,'error'=>'Ubicación inválida.'], 404);
        }

        return response()->json([
            'ok' => true,
            'nav' => $this->buildNav($from, $to),
        ]);
    }

    private function buildNav(Location $from, Location $to): array
    {
        // Ruta simple “humana” (no mapa): pasillo + sección
        $fromA = (string)($from->aisle ?? '');
        $toA   = (string)($to->aisle ?? '');
        $fromS = (string)($from->section ?? '');
        $toS   = (string)($to->section ?? '');

        $dist = $this->distanceScore($from, $to);

        $steps = [];
        $steps[] = "Estás en: {$from->code}";
        if ($fromA !== '' && $toA !== '' && $fromA !== $toA) {
            $steps[] = "Cambia al pasillo {$toA}.";
        }
        if ($toS !== '' && $fromS !== $toS) {
            $steps[] = "Ve a la sección {$toS}.";
        }
        $steps[] = "Llega a la ubicación: {$to->code}";

        return [
            'from' => ['code' => $from->code],
            'to' => ['code' => $to->code],
            'distance_score' => $dist,
            'steps' => $steps,
        ];
    }

    private function distanceScore(Location $a, Location $b): int
    {
        $ai = $this->aisleIndex($a->aisle);
        $bi = $this->aisleIndex($b->aisle);

        $as = is_numeric($a->section) ? (int)$a->section : 0;
        $bs = is_numeric($b->section) ? (int)$b->section : 0;

        // Peso fuerte al pasillo
        return abs($ai - $bi) * 100 + abs($as - $bs);
    }

    private function aisleIndex(?string $aisle): int
    {
        $aisle = strtoupper(trim((string)$aisle));
        if ($aisle === '') return 0;

        // A-Z => 1..26
        if (preg_match('/^[A-Z]$/', $aisle)) {
            return ord($aisle) - ord('A') + 1;
        }

        // numérico si existe
        if (is_numeric($aisle)) return (int)$aisle;

        return 0;
    }
}
