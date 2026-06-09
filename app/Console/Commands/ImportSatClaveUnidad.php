<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSatClaveUnidad extends Command
{
    protected $signature = 'sat:import-clave-unidad {path}';
    protected $description = 'Importa el catálogo c_ClaveUnidad del SAT desde un CSV.';

    public function handle()
    {
        $path = $this->argument('path');
        if (!is_file($path)) {
            $this->error('No se encontró el archivo: ' . $path);
            return 1;
        }

        $firstLine = (string) fgets(fopen($path, 'r'));
        $delim = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        $fh = fopen($path, 'r');
        $buffer = [];
        $count = 0;

        while (($row = fgetcsv($fh, 0, $delim)) !== false) {
            $clave = trim((string) ($row[0] ?? ''));

            // Salta encabezado y claves inválidas (la clave es corta alfanumérica: H87, XPK, KGM...).
            if ($clave === '' || mb_strtolower($clave) === 'c_claveunidad') {
                continue;
            }
            if (!preg_match('/^[A-Za-z0-9]{1,10}$/', $clave)) {
                continue;
            }

            $nombre = $this->utf8(trim((string) ($row[1] ?? '')));
            if ($nombre === '') {
                continue;
            }

            $simbolo = $this->utf8(trim((string) ($row[6] ?? ''))) ?: null;

            $buffer[] = [
                'clave' => $clave,
                'nombre' => mb_substr($nombre, 0, 255),
                'simbolo' => $simbolo ? mb_substr($simbolo, 0, 50) : null,
            ];

            if (count($buffer) >= 500) {
                DB::table('sat_clave_unidad')->upsert($buffer, ['clave'], ['nombre', 'simbolo']);
                $count += count($buffer);
                $buffer = [];
            }
        }

        if ($buffer) {
            DB::table('sat_clave_unidad')->upsert($buffer, ['clave'], ['nombre', 'simbolo']);
            $count += count($buffer);
        }

        fclose($fh);
        $this->info("Listo. Claves de unidad importadas: {$count}");
        return 0;
    }

    private function utf8(string $s): string
    {
        if ($s !== '' && !mb_check_encoding($s, 'UTF-8')) {
            return mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
        }
        return $s;
    }
}