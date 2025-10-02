<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['admin','manager','editor','viewer'] as $name) {
            Role::firstOrCreate(['name'=>$name,'guard_name'=>$guard]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
