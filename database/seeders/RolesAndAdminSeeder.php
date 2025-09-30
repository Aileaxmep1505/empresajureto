<?php
namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;


class RolesAndAdminSeeder extends Seeder
{
public function run(): void
{
foreach (['admin','editor','user'] as $r) {
Role::firstOrCreate(['name'=>$r]);
}


if (!User::where('email','admin@example.com')->exists()) {
$admin = User::create([
'name' => 'Super Admin',
'email' => 'admin@example.com',
'password' => Hash::make('Admin123*'),
'status' => 'approved',
'approved_at' => now(),
]);
$admin->assignRole('admin');
}
}
}