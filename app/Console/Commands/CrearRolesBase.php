<?php

namespace App\Console\Commands;

use App\Support\CatalogoPermisos;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Crea/actualiza los roles de arranque con permisos razonables (Spatie).
 *
 * Es idempotente y NO pisa lo ya configurado: si un rol ya existe, se respetan
 * sus permisos tal como estén. Con --rehacer se reescriben. El rol "admin"
 * puede todo por Gate::before, pero también recibe todos los permisos.
 */
class CrearRolesBase extends Command
{
    protected $signature = 'roles:base {--rehacer : Reescribe los permisos de los roles que ya existen}';

    protected $description = 'Crea los roles de arranque (ventas, almacén, contabilidad, licitaciones…) con permisos razonables';

    /** @return array<string, array<int, string>> */
    private function plantillas(): array
    {
        $todos = CatalogoPermisos::llaves();

        return [
            'admin' => $todos,

            'manager' => array_values(array_filter($todos, fn ($p) => str_ends_with($p, '.ver')
                || in_array($p, [
                    'catalogo.crear', 'catalogo.editar', 'catalogo.stock', 'catalogo.publicar',
                    'cotizaciones.crear', 'cotizaciones.editar',
                    'pedidos.gestionar', 'clientes.crear', 'clientes.editar',
                    'proveedores.gestionar', 'licitaciones.gestionar', 'contabilidad.gestionar',
                    'agenda.gestionar', 'tickets.gestionar', 'wms.recibir', 'wms.mover', 'wms.picking', 'wms.conteos',
                ], true))),

            'ventas' => [
                'clientes.ver', 'clientes.crear', 'clientes.editar',
                'cotizaciones.ver', 'cotizaciones.crear', 'cotizaciones.editar',
                'pedidos.ver', 'catalogo.ver', 'agenda.ver', 'agenda.gestionar', 'tickets.ver', 'tickets.crear',
            ],

            'almacen' => [
                'wms.ver', 'wms.recibir', 'wms.mover', 'wms.picking', 'wms.conteos', 'wms.escanear', 'wms.analiticas',
                'catalogo.ver', 'catalogo.editar', 'catalogo.stock', 'pedidos.ver', 'tickets.ver',
            ],

            'contabilidad' => [
                'contabilidad.ver', 'contabilidad.gestionar',
                'cotizaciones.ver', 'pedidos.ver', 'clientes.ver', 'proveedores.ver',
            ],

            'licitaciones' => [
                'licitaciones.ver', 'licitaciones.gestionar', 'cotizaciones.ver', 'clientes.ver', 'agenda.ver',
            ],

            'viewer' => array_values(array_filter($todos, fn ($p) => str_ends_with($p, '.ver'))),
        ];
    }

    public function handle(): int
    {
        $guard = config('auth.defaults.guard', 'web');

        if (Permission::where('guard_name', $guard)->whereIn('name', CatalogoPermisos::llaves())->count() === 0) {
            $this->error('No hay permisos en la base. Corre primero: php artisan permisos:sincronizar');

            return self::FAILURE;
        }

        foreach ($this->plantillas() as $name => $permisos) {
            $role = Role::firstOrNew(['name' => $name, 'guard_name' => $guard]);
            $eraNuevo = ! $role->exists;

            if (! $eraNuevo && ! $this->option('rehacer')) {
                $this->line("  = {$name} ya existe, se deja como está ({$role->permissions()->count()} permisos)");
                continue;
            }

            if ($eraNuevo) {
                $role->save();
            }

            $role->syncPermissions($permisos);
            $this->line(sprintf('  %s %s · %d permisos', $eraNuevo ? '+' : '~', $name, count($permisos)));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->newLine();
        $this->info('Listo. Ajusta lo que sobre o falte en la pantalla de Usuarios / Roles.');

        return self::SUCCESS;
    }
}
