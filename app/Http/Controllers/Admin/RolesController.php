<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CatalogoPermisos;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesController extends Controller
{
    private function guard(): string
    {
        return config('auth.defaults.guard', 'web');
    }

    public function index()
    {
        $roles = Role::withCount(['permissions', 'users'])->orderBy('name')->get();

        return view('admin.roles.index', [
            'roles' => $roles,
            'totalPermisos' => count(CatalogoPermisos::llaves()),
        ]);
    }

    public function edit(Role $role)
    {
        $asignados = $role->permissions->pluck('name')->all();

        return view('admin.roles.edit', [
            'role' => $role,
            'grupos' => CatalogoPermisos::grupos(),
            'asignados' => $asignados,
            'esAdmin' => $role->name === 'admin',
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'permisos' => ['array'],
            'permisos.*' => ['string'],
        ]);

        // Solo se aceptan llaves reales del catálogo.
        $seleccion = collect($data['permisos'] ?? [])
            ->filter(fn ($p) => CatalogoPermisos::existe($p))
            ->values()->all();

        $role->syncPermissions($seleccion);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')
            ->with('ok', 'Permisos de «' . $role->name . '» actualizados.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
        ]);

        $name = Str::slug($data['name'], '_');
        if ($name === '') {
            return back()->with('error', 'Nombre de rol no válido.');
        }

        Role::firstOrCreate(['name' => $name, 'guard_name' => $this->guard()]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.roles.edit', $name)
            ->with('ok', 'Rol «' . $name . '» creado. Marca sus permisos.');
    }

    public function destroy(Role $role)
    {
        // Roles base que no conviene borrar.
        if (in_array($role->name, ['admin', 'user', 'cliente_web'], true)) {
            return back()->with('error', 'Ese rol es del sistema y no se puede eliminar.');
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')->with('ok', 'Rol eliminado.');
    }
}
