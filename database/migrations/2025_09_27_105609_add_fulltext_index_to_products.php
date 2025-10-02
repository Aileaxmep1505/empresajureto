<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Asegura motor compatible (InnoDB) y charset
        DB::statement('ALTER TABLE `products` ENGINE=InnoDB');

        // Construye lista de columnas de texto que existan
        $candidates = [
            'name','nombre','descripcion',
            'category','categoria',
            'brand','marca',
            'color','material',
            'sku','supplier_sku',
            'tags',
            'unit','unidad',
        ];

        $cols = [];
        foreach ($candidates as $c) {
            if (Schema::hasColumn('products', $c)) {
                $cols[] = "`{$c}`";
            }
        }

        if (!empty($cols)) {
            // El nombre del índice puede ser el que prefieras
            $indexName = 'ft_products_all';

            // Si ya existe, no lo dupliques
            $exists = DB::selectOne("
                SELECT 1
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                  AND table_name = 'products'
                  AND index_name = ?
                  AND index_type = 'FULLTEXT'
                LIMIT 1
            ", [$indexName]);

            if (!$exists) {
                DB::statement("ALTER TABLE `products` ADD FULLTEXT `{$indexName}` (".implode(',', $cols).")");
            }
        }
    }

    public function down(): void
    {
        $indexName = 'ft_products_all';
        // Borra el índice si existe
        $exists = DB::selectOne("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'products'
              AND index_name = ?
              AND index_type = 'FULLTEXT'
            LIMIT 1
        ", [$indexName]);

        if ($exists) {
            DB::statement("ALTER TABLE `products` DROP INDEX `{$indexName}`");
        }
    }
};
