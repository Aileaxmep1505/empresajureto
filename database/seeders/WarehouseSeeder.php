<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        // Bodega principal
        Warehouse::updateOrCreate(
            ['id' => 1],
            [
                'code' => 'WH-001',
                'name' => 'Bodega Principal',
            ]
        );

        // (Opcional) otra bodega de ejemplo
        Warehouse::updateOrCreate(
            ['id' => 2],
            [
                'code' => 'WH-002',
                'name' => 'Bodega Secundaria',
            ]
        );
    }
}
