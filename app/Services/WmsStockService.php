<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;

/**
 * Movimientos de stock que usan las operaciones nuevas del WMS
 * (reabastecimiento, conteos, cross-docking).
 *
 * Sigue las mismas reglas que el resto del WMS:
 *   - inventory        = existencia por ubicación
 *   - catalog_items.stock = existencia global
 *   - inventory_movements = bitácora
 */
class WmsStockService
{
    /**
     * Mueve unidades entre dos ubicaciones. El stock global no cambia.
     *
     * @throws \RuntimeException si el origen no tiene suficiente
     */
    public function transfer(int $itemId, int $fromId, int $toId, int $qty, int $userId, string $type = 'transfer', ?string $notes = null, array $meta = []): void
    {
        if ($qty <= 0) {
            throw new \RuntimeException('La cantidad a mover debe ser mayor a cero.');
        }

        if ($fromId === $toId) {
            throw new \RuntimeException('El origen y el destino son la misma ubicación.');
        }

        DB::transaction(function () use ($itemId, $fromId, $toId, $qty, $userId, $type, $notes, $meta) {
            $from = Inventory::where('location_id', $fromId)->where('catalog_item_id', $itemId)->lockForUpdate()->first();

            if (! $from || (int) $from->qty < $qty) {
                $hay = (int) ($from->qty ?? 0);
                throw new \RuntimeException("Stock insuficiente en la ubicación origen (hay {$hay}, se piden {$qty}).");
            }

            $to = $this->fila($itemId, $toId, $userId);

            $from->qty = (int) $from->qty - $qty;
            $from->updated_by = $userId;
            $from->save();

            $to->qty = (int) $to->qty + $qty;
            $to->updated_by = $userId;
            $to->save();

            InventoryMovement::create([
                'type' => $type,
                'catalog_item_id' => $itemId,
                'from_location_id' => $fromId,
                'to_location_id' => $toId,
                'qty' => $qty,
                'user_id' => $userId,
                'notes' => $notes,
                'meta' => $meta ?: null,
            ]);
        });
    }

    /**
     * Deja una ubicación con la cantidad contada y arrastra la diferencia al
     * stock global, para que ambos sigan cuadrando.
     *
     * @return int diferencia aplicada (positiva = sobraba, negativa = faltaba)
     */
    public function setLocationQty(int $itemId, int $locationId, int $newQty, int $userId, string $type = 'count_adjust', ?string $notes = null, array $meta = []): int
    {
        $newQty = max(0, $newQty);

        return DB::transaction(function () use ($itemId, $locationId, $newQty, $userId, $type, $notes, $meta) {
            $row = $this->fila($itemId, $locationId, $userId);
            $before = (int) $row->qty;
            $delta = $newQty - $before;

            if ($delta === 0) {
                return 0;
            }

            $row->qty = $newQty;
            $row->updated_by = $userId;
            $row->save();

            $item = CatalogItem::whereKey($itemId)->lockForUpdate()->first();
            $stockBefore = (int) ($item->stock ?? 0);

            if ($item) {
                $item->stock = max(0, $stockBefore + $delta);
                $item->save();
            }

            InventoryMovement::create([
                'type' => $type,
                'catalog_item_id' => $itemId,
                'from_location_id' => null,
                'to_location_id' => $locationId,
                'qty' => abs($delta),
                'user_id' => $userId,
                'notes' => $notes,
                'meta' => $meta + [
                    'before' => $before,
                    'after' => $newQty,
                    'delta' => $delta,
                    'stock_before' => $stockBefore,
                    'stock_after' => (int) ($item->stock ?? $stockBefore),
                ],
            ]);

            return $delta;
        });
    }

    /** Fila de inventario (bloqueada) de un producto en una ubicación; la crea en cero si no existe. */
    private function fila(int $itemId, int $locationId, int $userId): Inventory
    {
        $row = Inventory::where('location_id', $locationId)->where('catalog_item_id', $itemId)->lockForUpdate()->first();

        if (! $row) {
            $row = Inventory::create([
                'location_id' => $locationId,
                'catalog_item_id' => $itemId,
                'qty' => 0,
                'min_qty' => 0,
                'updated_by' => $userId,
            ]);
            $row->refresh();
        }

        return $row;
    }
}
