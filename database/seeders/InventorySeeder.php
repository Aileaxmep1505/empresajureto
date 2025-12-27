<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\CatalogItem;
use App\Models\Location;
use App\Models\Inventory;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $items = CatalogItem::query()->select('id')->orderBy('id')->get();
        $locs  = Location::query()->select('id','warehouse_id')->orderBy('id')->get();

        if ($items->isEmpty() || $locs->isEmpty()) {
            $this->command?->warn('Faltan catalog_items o locations. Corre CatalogItemSeeder y LocationSeeder primero.');
            return;
        }

        // Limpia inventario (opcional)
        // Inventory::query()->truncate();

        // Insert por lotes
        $rows = [];
        $now = now();

        foreach ($items as $idx => $it) {
            // A cada producto le asignamos 1-3 ubicaciones con stock
            $spots = rand(1, 3);

            for ($k = 0; $k < $spots; $k++) {
                $loc = $locs[($idx * 3 + $k) % $locs->count()];
                $qty = rand(1, 40);

                $rows[] = [
                    'catalog_item_id' => $it->id,
                    'location_id'     => $loc->id,
                    'qty'             => $qty,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }
        }

        // Insert masivo usando la tabla real del modelo Inventory
        // (por si tu tabla no se llama "inventories" por default)
        $table = (new Inventory)->getTable();

        // Para evitar duplicados si corres el seeder varias veces:
        // 1) borra filas existentes de esos items, o
        // 2) truncate arriba, o
        // 3) mete lógica updateOrInsert (más lento).
        DB::table($table)->insert($rows);
    }
}
