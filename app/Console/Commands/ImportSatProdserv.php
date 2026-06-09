<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSatProdserv extends Command
{
    protected $signature = 'sat:import-prodserv {path}';
    protected $description = 'Importa el catálogo c_ClaveProdServ del SAT (con palabras similares) desde un CSV.';

    public function handle()
    {
        $path = $this->argument('path');
        if (!is_file($path)) {
            $this->error('No se encontró el archivo: ' . $path);
            return 1;
        }

        // Detecta separador (coma o punto y coma).
        $firstLine = (string) fgets(fopen($path, 'r'));
        $delim = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        $fh = fopen($path, 'r');
        $buffer = [];
        $count = 0;

        while (($row = fgetcsv($fh, 0, $delim)) !== false) {
            // Columna A = clave (8 dígitos). Si no, busca en toda la fila.
            $clave = trim((string) ($row[0] ?? ''));
            if (!preg_match('/^\d{8}$/', $clave)) {
                $clave = null;
                foreach ($row as $cell) {
                    $c = trim((string) $cell);
                    if (preg_match('/^\d{8}$/', $c)) { $clave = $c; break; }
                }
            }
            if (!$clave) {
                continue; // encabezado o fila inválida
            }

            $descripcion = $this->utf8(trim((string) ($row[1] ?? '')));
            if ($descripcion === '') {
                // respaldo: el texto más largo de la fila
                foreach ($row as $cell) {
                    $c = $this->utf8(trim((string) $cell));
                    if (!preg_match('/^\d{8}$/', $c) && mb_strlen($c) > mb_strlen($descripcion)) {
                        $descripcion = $c;
                    }
                }
            }

            $buffer[] = [
                'clave' => $clave,
                'descripcion' => mb_substr($descripcion, 0, 500),
                'palabras_similares' => $this->utf8(trim((string) ($row[8] ?? ''))) ?: null,
                'incluir_iva' => $this->utf8(trim((string) ($row[2] ?? ''))) ?: null,
                'incluir_ieps' => $this->utf8(trim((string) ($row[3] ?? ''))) ?: null,
            ];

            if (count($buffer) >= 1000) {
                DB::table('sat_prodserv')->upsert($buffer, ['clave'], ['descripcion', 'palabras_similares', 'incluir_iva', 'incluir_ieps']);
                $count += count($buffer);
                $buffer = [];
            }
        }

        if ($buffer) {
            DB::table('sat_prodserv')->upsert($buffer, ['clave'], ['descripcion', 'palabras_similares', 'incluir_iva', 'incluir_ieps']);
            $count += count($buffer);
        }

        fclose($fh);
        $this->info("Listo. Claves importadas/actualizadas: {$count}");
        return 0;
    }

    /** Repara acentos si el CSV no vino en UTF-8. */
    private function utf8(string $s): string
    {
        if ($s !== '' && !mb_check_encoding($s, 'UTF-8')) {
            return mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
        }
        return $s;
    }
}