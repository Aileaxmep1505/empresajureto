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
            RolesSeeder::class,     // ← crea los roles (incluye 'admin')

            // PermissionsSeeder::class, // opcional, si luego quieres permisos finos
        ]);
    }
}
