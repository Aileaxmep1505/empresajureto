<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,          // roles (incluye 'admin')
            CategorySeeder::class,       // categorías iniciales
            PartContableSeeder::class,   // tus partidas contables

            // ✅ WMS (sin CatalogItemSeeder porque ya tienes productos)
            WarehouseSeeder::class,      // crea bodegas
            LocationSeeder::class,       // crea ubicaciones con meta[x,y,w,h] para el layout
            InventorySeeder::class,      // mete stock en ubicaciones usando tus CatalogItems existentes
        ]);
    }
}
