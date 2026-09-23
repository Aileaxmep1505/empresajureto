<?php

namespace App\Console\Commands;

use App\Support\CatalogoPermisos;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Pone la tabla de permisos (Spatie) al día con el catálogo del código.
 *
 * Se corre después de agregar o quitar un permiso en CatalogoPermisos.
 * Los que ya no existen en el código se avisan pero NO se borran solos
 * (borrarlos quitaría permisos concedidos a roles reales); usa --limpiar.
 */
class SincronizarPermisos extends Command
{
    protected $signature = 'permisos:sincronizar {--limpiar : Borra también los permisos que ya no están en el código}';

    protected $description = 'Sincroniza la tabla de permisos con el catálogo del código';

    public function handle(): int
    {
        $guard = config('auth.defaults.guard', 'web');
        $enCodigo = CatalogoPermisos::llaves();
        $nuevos = 0;

        foreach ($enCodigo as $llave) {
            $permiso = Permission::firstOrNew(['name' => $llave, 'guard_name' => $guard]);
            if (! $permiso->exists) {
                $permiso->save();
                $nuevos++;
            }
        }

        $this->info("Permisos nuevos: {$nuevos} · total en el código: " . count($enCodigo));

        $sobrantes = Permission::whereNotIn('name', $enCodigo)->where('guard_name', $guard)->get();

        if ($sobrantes->isNotEmpty()) {
            $this->warn('Hay ' . $sobrantes->count() . ' permiso(s) en la base que ya no están en el código:');
            foreach ($sobrantes as $p) {
                $this->line('  - ' . $p->name . ' (asignado a ' . $p->roles()->count() . ' rol/es)');
            }

            if ($this->option('limpiar')) {
                Permission::whereIn('id', $sobrantes->pluck('id'))->delete();
                $this->info('Borrados.');
            } else {
                $this->line('Se conservan. Usa --limpiar si de verdad quieres borrarlos.');
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return self::SUCCESS;
    }
}
