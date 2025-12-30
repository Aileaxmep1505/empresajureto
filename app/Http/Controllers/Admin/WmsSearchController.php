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

            $totalQty = $rows->sum('qty');

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
     * ✅ GET /admin/wms/locations/scan?code=...  OR ?id=... OR ?raw=...
     * Acepta:
     * - Código ubicación (A-S01-R01-L03-B01)
     * - QR con URL (http://.../admin/wms/locations/6/...)
     * - raw cualquiera (intenta extraer code o id)
     */
    public function locationScan(Request $r)
    {
        $data = $r->validate([
            'id'  => ['nullable','integer','min:1'],
            'code'=> ['nullable','string','max:120'],
            'raw' => ['nullable','string','max:2000'],
        ]);

        $id   = (int)($data['id'] ?? 0);
        $code = trim((string)($data['code'] ?? ''));
        $raw  = trim((string)($data['raw'] ?? ''));

        // Si viene RAW, intenta extraer ID o CODE
        if (!$id && $raw) {
            $id = $this->extractLocationIdFromRaw($raw) ?: 0;
        }
        if ($code === '' && $raw) {
            $code = $this->extractLocationCodeFromRaw($raw);
        }

        $loc = null;

        if ($id) {
            $loc = Location::query()->find($id);
        }

        if (!$loc && $code !== '') {
            $loc = Location::query()->where('code', $code)->first();
        }

        if (!$loc) {
            return response()->json(['ok'=>false,'error'=>'Ubicación no encontrada.'], 404);
        }

        return response()->json([
            'ok' => true,
            'location' => [
                'id' => $loc->id,
                'warehouse_id' => $loc->warehouse_id ?? null,
                'code' => $loc->code,
                'type' => $loc->type ?? null,
                'aisle' => $loc->aisle ?? null,
                'section' => $loc->section ?? null,
                'stand' => $loc->stand ?? null,
                'rack' => $loc->rack ?? null,
                'level' => $loc->level ?? null,
                'bin' => $loc->bin ?? null,
            ],
        ]);
    }

    /**
     * ✅ GET /admin/wms/products/scan?raw=...
     * Acepta:
     * - Código de barras (EAN/UPC/Code128) => normalmente números
     * - SKU
     * - QR con URL (..../catalog-items/123.. o ..../products/123.. o ..../items/123..)
     *
     * Devuelve el item (id, name, sku, gtin) listo para meterlo al buscador.
     */
    public function productScan(Request $r)
    {
        $data = $r->validate([
            'id'  => ['nullable','integer','min:1'],
            'sku' => ['nullable','string','max:255'],
            'gtin'=> ['nullable','string','max:255'],
            'raw' => ['nullable','string','max:2000'],
        ]);

        $id   = (int)($data['id'] ?? 0);
        $sku  = trim((string)($data['sku'] ?? ''));
        $gtin = trim((string)($data['gtin'] ?? ''));
        $raw  = trim((string)($data['raw'] ?? ''));

        // Si viene raw, intenta sacar ID desde URL
        if (!$id && $raw) {
            $id = $this->extractCatalogItemIdFromRaw($raw) ?: 0;
        }

        // Si no hay sku/gtin pero hay raw, úsalo
        if ($sku === '' && $gtin === '' && $raw) {
            $candidate = $this->extractCodeLikeFromRaw($raw);
            // Si parece código de barras (solo dígitos) -> GTIN
            if ($candidate !== '' && preg_match('/^\d{8,18}$/', $candidate)) {
                $gtin = $candidate;
            } else {
                $sku = $candidate ?: $raw;
            }
        }

        $q = CatalogItem::query()->select(['id','name','sku','meli_gtin','price']);

        $item = null;

        if ($id) {
            $item = $q->find($id);
        }

        if (!$item && $gtin !== '') {
            $item = (clone $q)->where('meli_gtin', $gtin)->first();
        }

        if (!$item && $sku !== '') {
            // SKU exacto primero, luego like
            $item = (clone $q)->where('sku', $sku)->first()
                 ?: (clone $q)->where('sku','like',"%{$sku}%")->orderBy('sku')->first();
        }

        if (!$item) {
            return response()->json(['ok'=>false,'error'=>'Producto no encontrado.'], 404);
        }

        return response()->json([
            'ok' => true,
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'gtin' => $item->meli_gtin,
                'price' => $item->price,
            ],
        ]);
    }

    // =========================
    // Helpers: parsing RAW
    // =========================

    private function extractLocationIdFromRaw(string $raw): ?int
    {
        // /locations/6  o /locations/6/page
        if (preg_match('~\/locations\/(\d+)~i', $raw, $m)) {
            return (int)$m[1];
        }
        return null;
    }

    private function extractCatalogItemIdFromRaw(string $raw): ?int
    {
        // Soporta varias rutas posibles
        // /catalog-items/123  /catalog_items/123  /products/123  /items/123
        if (preg_match('~\/(catalog-items|catalog_items|products|items)\/(\d+)~i', $raw, $m)) {
            return (int)$m[2];
        }
        return null;
    }

    private function extractLocationCodeFromRaw(string $raw): string
    {
        // Extrae algo como A-S01-R01-L03-B01 / A-03-S2-R1-N4-B07 etc.
        if (preg_match('~([A-Z0-9]+(?:-[A-Z0-9]+){3,10})~i', $raw, $m)) {
            return strtoupper($m[1]);
        }
        return '';
    }

    private function extractCodeLikeFromRaw(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') return '';

        // 1) Si viene URL, intenta extraer algo "code-like" dentro
        if (preg_match('~([A-Z0-9]+(?:-[A-Z0-9]+){3,10})~i', $raw, $m)) {
            return strtoupper($m[1]);
        }

        // 2) Si viene solo números (barcode)
        if (preg_match('~^\d{6,18}$~', $raw)) {
            return $raw;
        }

        return $raw;
    }

    // =========================
    // Nav helpers (igual que tu versión)
    // =========================
    private function buildNav(Location $from, Location $to): array
    {
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

        return abs($ai - $bi) * 100 + abs($as - $bs);
    }

    private function aisleIndex(?string $aisle): int
    {
        $aisle = strtoupper(trim((string)$aisle));
        if ($aisle === '') return 0;

        if (preg_match('/^[A-Z]$/', $aisle)) {
            return ord($aisle) - ord('A') + 1;
        }

        if (is_numeric($aisle)) return (int)$aisle;

        return 0;
    }
}
