<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillProviderCodes extends Command
{
    protected $signature = 'providers:backfill-codes {--dry : Solo mostrar sin guardar}';
    protected $description = 'Asigna folios PROV-00001 a proveedores existentes sin code';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');

        // Tomar el último folio ya usado (si existe)
        $maxNum = DB::table('providers')
            ->whereNotNull('code')
            ->where('code', 'like', 'PROV-%')
            ->selectRaw("MAX(CAST(SUBSTRING(code, 6) AS UNSIGNED)) as m")
            ->value('m');

        $counter = ((int) $maxNum);

        // Traer proveedores sin code (null o vacío)
        $rows = DB::table('providers')
            ->select('id')
            ->where(function ($q) {
                $q->whereNull('code')->orWhere('code', '=', '');
            })
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('✅ No hay proveedores sin folio (code).');
            return self::SUCCESS;
        }

        $this->info("Encontrados: {$rows->count()} proveedores sin folio.");
        $this->info("Último folio actual: PROV-" . str_pad((string)$counter, 5, '0', STR_PAD_LEFT));

        $updates = [];

        foreach ($rows as $r) {
            $counter++;
            $code = 'PROV-' . str_pad((string)$counter, 5, '0', STR_PAD_LEFT);
            $updates[] = ['id' => $r->id, 'code' => $code];
        }

        // Preview
        $this->line("Ejemplo (primeros 10):");
        foreach (array_slice($updates, 0, 10) as $u) {
            $this->line("  id={$u['id']} -> {$u['code']}");
        }

        if ($dry) {
            $this->warn('🟡 DRY RUN: No se guardó nada.');
            return self::SUCCESS;
        }

        // Guardar con transacción
        DB::transaction(function () use ($updates) {
            foreach ($updates as $u) {
                DB::table('providers')
                    ->where('id', $u['id'])
                    ->update(['code' => $u['code']]);
            }
        });

        $this->info('✅ Listo: folios asignados a proveedores existentes.');
        return self::SUCCESS;
    }
}