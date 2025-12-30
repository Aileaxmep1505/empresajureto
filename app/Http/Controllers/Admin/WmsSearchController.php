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

        $primaryLocIds = $items->pluck('primary_location_id')->filter()->unique()->values()->all();
        $primaryLocs = Location::whereIn('id', $primaryLocIds)->get()->keyBy('id');

        $out = [];
        foreach ($items as $it) {
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

            $totalQty = (int)$rows->sum('qty');

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
                if ($toLoc) $nav = $this->buildNav($fromLoc, $toLoc);
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
                'total_qty' => $totalQty,
                'locations' => $locations,
                'recommended_location' => $recommended,
                'nav' => $nav,
            ];
        }

        return response()->json([
            'ok' => true,
            'from' => $fromLoc ? ['id'=>$fromLoc->id,'code'=>$fromLoc->code] : null,
            'results' => $out,
        ]);
    }

    /**
     * GET /admin/wms/nav?from_code=...&to_code=...
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

    /**
     * ✅ GET /admin/wms/locations/scan?code=... | ?id=... | ?raw=...
     * Acepta: código tipo A-S01-R01-L03-B01, URL que contenga /locations/{id}, o raw.
     */
    public function locationScan(Request $r)
    {
        $data = $r->validate([
            'code' => ['nullable','string','max:120'],
            'id'   => ['nullable','integer'],
            'raw'  => ['nullable','string','max:1000'],
        ]);

        $code = $data['code'] ?? null;
        $id   = $data['id'] ?? null;
        $raw  = $data['raw'] ?? null;

        if (!$code && !$id && $raw) {
            $tok = $this->extractLocationToken($raw);
            if ($tok['type'] === 'code') $code = $tok['value'];
            if ($tok['type'] === 'id')   $id   = (int)$tok['value'];
            if (!$code && !$id && $tok['type'] === 'raw') $code = $tok['value'];
        }

        $loc = null;
        if ($id) {
            $loc = Location::find($id);
        } elseif ($code) {
            $loc = Location::where('code', $code)->first();
        }

        if (!$loc) {
            return response()->json(['ok'=>false,'error'=>'Ubicación no encontrada.'], 404);
        }

        return response()->json([
            'ok' => true,
            'location' => [
                'id' => $loc->id,
                'code' => $loc->code,
            ],
        ]);
    }

    /**
     * ✅ GET /admin/wms/products/scan?raw=...
     * Acepta: SKU, GTIN (meli_gtin), o URL que traiga /catalog-items/{id} o /products/{id}.
     */
    public function productScan(Request $r)
    {
        $data = $r->validate([
            'raw' => ['required','string','max:1000'],
        ]);

        $raw = trim($data['raw']);
        if ($raw === '') {
            return response()->json(['ok'=>false,'error'=>'Lectura vacía.'], 422);
        }

        $id = null;
        if (preg_match('~/catalog-items/(\d+)~i', $raw, $m)) $id = (int)$m[1];
        if (!$id && preg_match('~/products/(\d+)~i', $raw, $m)) $id = (int)$m[1];

        $item = null;

        if ($id) {
            $item = CatalogItem::find($id);
        }

        if (!$item) {
            $token = $raw;

            // Si trae URL, intenta sacar el último segmento como token
            if (filter_var($raw, FILTER_VALIDATE_URL)) {
                $parts = parse_url($raw);
                $path = $parts['path'] ?? '';
                $seg = collect(explode('/', trim($path,'/')))->last();
                if ($seg) $token = $seg;
            }

            $item = CatalogItem::query()
                ->select(['id','name','sku','meli_gtin'])
                ->where('sku', $token)
                ->orWhere('meli_gtin', $token)
                ->orWhere('sku', $raw)
                ->orWhere('meli_gtin', $raw)
                ->first();
        }

        if (!$item) {
            return response()->json(['ok'=>false,'error'=>'Producto no encontrado.'], 404);
        }

        return response()->json([
            'ok' => true,
            'item' => [
                'id'   => $item->id,
                'name' => $item->name,
                'sku'  => $item->sku,
                'gtin' => $item->meli_gtin,
            ],
        ]);
    }

    private function extractLocationToken(string $raw): array
    {
        $v = trim($raw);
        if ($v === '') return ['type'=>'empty','value'=>''];

        // Código con guiones (A-S01-R01-L03-B01)
        if (preg_match('/([A-Z0-9]+(?:-[A-Z0-9]+){3,10})/i', $v, $m)) {
            return ['type'=>'code','value'=>strtoupper($m[1])];
        }

        // URL /locations/{id}
        if (preg_match('~/locations/(\d+)~i', $v, $m)) {
            return ['type'=>'id','value'=>$m[1]];
        }

        return ['type'=>'raw','value'=>$v];
    }

    private function buildNav(Location $from, Location $to): array
    {
        $fromA = (string)($from->aisle ?? '');
        $toA   = (string)($to->aisle ?? '');
        $fromS = (string)($from->section ?? '');
        $toS   = (string)($to->section ?? '');

        $dist = $this->distanceScore($from, $to);

        $steps = [];
        $steps[] = "Estás en: {$from->code}";
        if ($fromA !== '' && $toA !== '' && $fromA !== $toA) $steps[] = "Cambia al pasillo {$toA}.";
        if ($toS !== '' && $fromS !== $toS) $steps[] = "Ve a la sección {$toS}.";
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

        return abs($ai - $bi) * 100 + abs($as - $bs);
    }

    private function aisleIndex(?string $aisle): int
    {
        $aisle = strtoupper(trim((string)$aisle));
        if ($aisle === '') return 0;

        if (preg_match('/^[A-Z]$/', $aisle)) return ord($aisle) - ord('A') + 1;
        if (is_numeric($aisle)) return (int)$aisle;

        return 0;
    }
}
