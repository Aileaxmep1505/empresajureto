<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\PickItem;
use App\Models\PickWave;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WmsPickingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [ new Middleware('auth') ];
    }

    /**
     * POST /admin/wms/pick/waves
     * body:
     * - warehouse_id
     * - items: [ {catalog_item_id, qty} ... ]
     * - assign_to_me? boolean
     */
    public function createWave(Request $r)
    {
        $data = $r->validate([
            'warehouse_id' => ['required','exists:warehouses,id'],
            'items' => ['required','array','min:1'],
            'items.*.catalog_item_id' => ['required','exists:catalog_items,id'],
            'items.*.qty' => ['required','integer','min:1'],
            'assign_to_me' => ['nullable','boolean'],
        ]);

        return DB::transaction(function () use ($r, $data) {
            $code = 'PICK-'.str_pad((string)random_int(1, 999999), 6, '0', STR_PAD_LEFT);

            // evitar colisión (raro)
            while (PickWave::where('code', $code)->exists()) {
                $code = 'PICK-'.str_pad((string)random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            }

            $wave = PickWave::create([
                'warehouse_id' => (int)$data['warehouse_id'],
                'code' => $code,
                'status' => 0,
                'assigned_to' => ($data['assign_to_me'] ?? false) ? $r->user()->id : null,
                'meta' => [
                    'created_via' => 'api',
                ],
            ]);

            // Crear pick items con suggested_location_id (mejor ubicación)
            foreach ($data['items'] as $row) {
                $itemId = (int)$row['catalog_item_id'];
                $qty    = (int)$row['qty'];

                $suggested = $this->suggestLocationForItem($itemId, (int)$data['warehouse_id']);

                $sortKey = $suggested ? $this->makeSortKey($suggested) : '9999-9999-9999-9999';

                PickItem::create([
                    'pick_wave_id' => $wave->id,
                    'catalog_item_id' => $itemId,
                    'requested_qty' => $qty,
                    'picked_qty' => 0,
                    'suggested_location_id' => $suggested?->id,
                    'status' => 0,
                    'sort_key' => $sortKey,
                ]);
            }

            // Ordenar ruta (recalcular sort_key si quieres, y set current_pick_item_id)
            $next = PickItem::where('pick_wave_id', $wave->id)
                ->orderBy('sort_key')
                ->orderBy('id')
                ->first();

            $wave->current_pick_item_id = $next?->id;
            $wave->save();

            return response()->json([
                'ok' => true,
                'wave' => $wave->fresh(),
            ]);
        });
    }

    /**
     * POST /admin/wms/pick/waves/{wave}/start
     */
    public function startWave(Request $r, PickWave $wave)
    {
        if ($wave->status !== 0) {
            return response()->json(['ok'=>false,'error'=>'Wave ya iniciada o finalizada.'], 422);
        }

        $wave->status = 1;
        $wave->assigned_to = $wave->assigned_to ?? $r->user()->id;
        $wave->started_at = now();
        $wave->save();

        return response()->json(['ok'=>true,'wave'=>$wave->fresh()]);
    }

    /**
     * GET /admin/wms/pick/waves/{wave}/next
     * Devuelve el siguiente pick pendiente y qué ubicación se espera escanear.
     */
    public function next(Request $r, PickWave $wave)
    {
        $this->ensureAccess($r, $wave);

        $next = PickItem::where('pick_wave_id', $wave->id)
            ->where('status', 0)
            ->orderBy('sort_key')
            ->orderBy('id')
            ->with(['item:id,name,sku,meli_gtin,price,primary_location_id', 'suggestedLocation:id,code'])
            ->first();

        if (!$next) {
            return response()->json([
                'ok' => true,
                'done' => true,
                'message' => 'No hay más productos por surtir.',
            ]);
        }

        $wave->current_pick_item_id = $next->id;
        $wave->save();

        return response()->json([
            'ok' => true,
            'done' => false,
            'wave' => [
                'id' => $wave->id,
                'code' => $wave->code,
                'status' => $wave->status,
                'current_location' => $wave->currentLocation ? ['id'=>$wave->currentLocation->id,'code'=>$wave->currentLocation->code] : null,
            ],
            'pick' => $this->pickPayload($next),
        ]);
    }

    /**
     * POST /admin/wms/pick/waves/{wave}/scan-location
     * body: code
     * Valida que sea la ubicación correcta (o un padre) y fija current_location_id.
     */
    public function scanLocation(Request $r, PickWave $wave)
    {
        $this->ensureAccess($r, $wave);

        $data = $r->validate([
            'code' => ['required','string','max:80'],
        ]);

        $scanned = Location::where('code', $data['code'])
            ->where('warehouse_id', $wave->warehouse_id)
            ->first();

        if (!$scanned) {
            return response()->json(['ok'=>false,'error'=>'Ubicación no encontrada en esta bodega.'], 404);
        }

        $currentPick = $this->currentPickItem($wave);
        if (!$currentPick) {
            return response()->json(['ok'=>false,'error'=>'No hay pick activo.'], 422);
        }

        $expected = $currentPick->suggestedLocation ?: null;
        if (!$expected) {
            return response()->json(['ok'=>false,'error'=>'Este pick no tiene ubicación sugerida.'], 422);
        }

        // Permitir escanear exactamente la ubicación o un ancestro (ej. escanear el stand en vez del bin)
        if (!$this->isSameOrAncestor($scanned, $expected)) {
            return response()->json([
                'ok' => false,
                'error' => 'Ubicación incorrecta. Esperada: '.$expected->code,
                'expected' => ['code' => $expected->code],
            ], 422);
        }

        $wave->current_location_id = $scanned->id;
        $wave->save();

        return response()->json([
            'ok' => true,
            'current_location' => ['id'=>$scanned->id,'code'=>$scanned->code],
            'pick' => $this->pickPayload($currentPick->fresh(['item','suggestedLocation'])),
        ]);
    }

    /**
     * POST /admin/wms/pick/waves/{wave}/scan-item
     * body:
     * - barcode_or_sku (string)  (puede ser GTIN o SKU)
     * - qty (int)
     *
     * Requiere: ya haber escaneado ubicación (current_location_id) válida.
     */
    public function scanItem(Request $r, PickWave $wave)
    {
        $this->ensureAccess($r, $wave);

        $data = $r->validate([
            'barcode_or_sku' => ['required','string','max:120'],
            'qty' => ['required','integer','min:1'],
        ]);

        $currentPick = $this->currentPickItem($wave);
        if (!$currentPick) {
            return response()->json(['ok'=>false,'error'=>'No hay pick activo.'], 422);
        }

        $expectedLoc = $currentPick->suggestedLocation;
        if (!$expectedLoc) {
            return response()->json(['ok'=>false,'error'=>'Pick sin ubicación sugerida.'], 422);
        }

        // Verificar que la ubicación actual sea correcta (o ancestro)
        if (!$wave->current_location_id) {
            return response()->json(['ok'=>false,'error'=>'Primero escanea la ubicación.'], 422);
        }

        $scannedLoc = Location::find($wave->current_location_id);
        if (!$scannedLoc || !$this->isSameOrAncestor($scannedLoc, $expectedLoc)) {
            return response()->json([
                'ok'=>false,
                'error'=>'Ubicación actual no coincide. Escanea la ubicación correcta.',
                'expected' => ['code'=>$expectedLoc->code],
            ], 422);
        }

        // Validar producto (barcode = GTIN o SKU)
        $scan = trim($data['barcode_or_sku']);
        $expectedItem = $currentPick->item;

        $isMatch = false;
        if ($expectedItem->meli_gtin && $scan === (string)$expectedItem->meli_gtin) $isMatch = true;
        if ($expectedItem->sku && strcasecmp($scan, (string)$expectedItem->sku) === 0) $isMatch = true;
        if (is_numeric($scan) && $expectedItem->id === (int)$scan) $isMatch = true; // fallback: id

        if (!$isMatch) {
            return response()->json([
                'ok'=>false,
                'error'=>'Producto incorrecto. Escanea el SKU/GTIN del producto esperado.',
                'expected' => [
                    'catalog_item_id' => $expectedItem->id,
                    'name' => $expectedItem->name,
                    'sku' => $expectedItem->sku,
                    'meli_gtin' => $expectedItem->meli_gtin,
                ],
            ], 422);
        }

        $qty = (int)$data['qty'];

        return DB::transaction(function () use ($r, $wave, $currentPick, $expectedLoc, $qty) {

            $pick = PickItem::where('id', $currentPick->id)->lockForUpdate()->first();
            if (!$pick || $pick->status !== 0) {
                return response()->json(['ok'=>false,'error'=>'Pick ya no está disponible.'], 422);
            }

            $remaining = max(0, (int)$pick->requested_qty - (int)$pick->picked_qty);
            if ($remaining <= 0) {
                $pick->status = 1;
                $pick->save();
                return response()->json(['ok'=>true,'message'=>'Este pick ya estaba completo.']);
            }

            if ($qty > $remaining) {
                return response()->json([
                    'ok'=>false,
                    'error'=>"Cantidad excede lo pendiente. Pendiente: {$remaining}",
                ], 422);
            }

            // Descontar inventario del BIN sugerido (exacto)
            $inv = Inventory::where('location_id', $expectedLoc->id)
                ->where('catalog_item_id', $pick->catalog_item_id)
                ->lockForUpdate()
                ->first();

            if (!$inv || (int)$inv->qty < $qty) {
                $available = $inv ? (int)$inv->qty : 0;
                return response()->json([
                    'ok'=>false,
                    'error'=>"Stock insuficiente en {$expectedLoc->code}. Disponible: {$available}",
                ], 422);
            }

            $inv->qty -= $qty;
            $inv->updated_by = $r->user()->id;
            $inv->save();

            $pick->picked_qty += $qty;

            if ((int)$pick->picked_qty >= (int)$pick->requested_qty) {
                $pick->status = 1;
            }

            $pick->save();

            InventoryMovement::create([
                'type' => 'pick',
                'catalog_item_id' => $pick->catalog_item_id,
                'from_location_id' => $expectedLoc->id,
                'to_location_id' => null,
                'qty' => $qty,
                'user_id' => $r->user()->id,
                'notes' => "Pick wave {$wave->code}",
                'meta' => [
                    'pick_wave_id' => $wave->id,
                    'pick_item_id' => $pick->id,
                ],
            ]);

            return response()->json([
                'ok' => true,
                'pick' => $this->pickPayload($pick->fresh(['item','suggestedLocation'])),
            ]);
        });
    }

    /**
     * POST /admin/wms/pick/waves/{wave}/finish
     */
    public function finish(Request $r, PickWave $wave)
    {
        $this->ensureAccess($r, $wave);

        $pending = PickItem::where('pick_wave_id', $wave->id)->where('status', 0)->count();
        if ($pending > 0) {
            return response()->json([
                'ok'=>false,
                'error'=>"Aún hay picks pendientes: {$pending}",
            ], 422);
        }

        $wave->status = 2;
        $wave->finished_at = now();
        $wave->save();

        return response()->json(['ok'=>true,'wave'=>$wave->fresh()]);
    }

    /* ===================== Helpers ===================== */

    private function ensureAccess(Request $r, PickWave $wave): void
    {
        // Si quieres hacerlo estricto: solo asignado o admin
        if ($wave->assigned_to && $wave->assigned_to !== $r->user()->id) {
            abort(403, 'Wave asignada a otro usuario.');
        }
    }

    private function currentPickItem(PickWave $wave): ?PickItem
    {
        if ($wave->current_pick_item_id) {
            return PickItem::where('id', $wave->current_pick_item_id)
                ->with(['item:id,name,sku,meli_gtin,price', 'suggestedLocation:id,code,warehouse_id,parent_id'])
                ->first();
        }

        return PickItem::where('pick_wave_id', $wave->id)
            ->where('status', 0)
            ->orderBy('sort_key')
            ->orderBy('id')
            ->with(['item:id,name,sku,meli_gtin,price', 'suggestedLocation:id,code,warehouse_id,parent_id'])
            ->first();
    }

    private function pickPayload(PickItem $pick): array
    {
        $item = $pick->item;
        $loc  = $pick->suggestedLocation;

        $remaining = max(0, (int)$pick->requested_qty - (int)$pick->picked_qty);

        return [
            'pick_item_id' => $pick->id,
            'catalog_item_id' => $pick->catalog_item_id,
            'status' => (int)$pick->status,
            'requested_qty' => (int)$pick->requested_qty,
            'picked_qty' => (int)$pick->picked_qty,
            'remaining_qty' => (int)$remaining,
            'expected_location' => $loc ? ['id'=>$loc->id,'code'=>$loc->code] : null,
            'item' => $item ? [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'meli_gtin' => $item->meli_gtin,
                'price' => $item->price,
            ] : null,
        ];
    }

    private function suggestLocationForItem(int $catalogItemId, int $warehouseId): ?Location
    {
        // 1) Primary location si tiene stock
        $item = CatalogItem::select(['id','primary_location_id'])->find($catalogItemId);

        if ($item && $item->primary_location_id) {
            $inv = Inventory::where('catalog_item_id', $catalogItemId)
                ->where('location_id', $item->primary_location_id)
                ->where('qty','>',0)
                ->first();

            $loc = Location::where('id', $item->primary_location_id)
                ->where('warehouse_id', $warehouseId)
                ->first();

            if ($inv && $loc) return $loc;
        }

        // 2) Ubicación con más stock dentro de la bodega
        $best = Inventory::where('catalog_item_id', $catalogItemId)
            ->where('qty','>',0)
            ->whereHas('location', fn($q) => $q->where('warehouse_id', $warehouseId))
            ->with('location')
            ->orderByDesc('qty')
            ->first();

        return $best?->location;
    }

    private function makeSortKey(Location $loc): string
    {
        // Para ordenar ruta: pasillo, sección, stand, etc.
        $a = $this->aisleIndex($loc->aisle);
        $s = is_numeric($loc->section) ? (int)$loc->section : 0;

        // stand/rack/level/bin pueden ser "S2", "R1", "N4", "B07"
        $stand = $this->extractNum($loc->stand);
        $rack  = $this->extractNum($loc->rack);
        $level = $this->extractNum($loc->level);
        $bin   = $this->extractNum($loc->bin);

        return sprintf('%04d-%04d-%04d-%04d-%04d-%04d', $a, $s, $stand, $rack, $level, $bin);
    }

    private function aisleIndex(?string $aisle): int
    {
        $aisle = strtoupper(trim((string)$aisle));
        if ($aisle === '') return 9999;

        if (preg_match('/^[A-Z]$/', $aisle)) {
            return ord($aisle) - ord('A') + 1;
        }
        if (is_numeric($aisle)) return (int)$aisle;

        return 9999;
    }

    private function extractNum(?string $v): int
    {
        $v = (string)$v;
        if ($v === '') return 0;
        preg_match('/(\d+)/', $v, $m);
        return isset($m[1]) ? (int)$m[1] : 0;
    }

    private function isSameOrAncestor(Location $scanned, Location $expected): bool
    {
        if ($scanned->id === $expected->id) return true;

        // caminar hacia arriba desde expected y ver si encontramos scanned
        $cur = $expected;
        $guard = 0;
        while ($cur && $cur->parent_id && $guard < 30) {
            $guard++;
            if ($cur->parent_id === $scanned->id) return true;
            $cur = Location::find($cur->parent_id);
        }
        return false;
    }
}
