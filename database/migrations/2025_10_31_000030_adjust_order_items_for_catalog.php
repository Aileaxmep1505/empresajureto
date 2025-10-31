<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $table = 'order_items';

    private function indexExists(string $indexName): bool
    {
        $db = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT 1
               FROM INFORMATION_SCHEMA.STATISTICS
              WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME   = ?
                AND INDEX_NAME   = ?
              LIMIT 1',
            [$db, $this->table, $indexName]
        );
        return (bool) $row;
    }

    private function makeNullableIfNeeded(string $column, string $sqlType): void
    {
        $db = DB::getDatabaseName();
        $col = DB::selectOne(
            'SELECT IS_NULLABLE
               FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME   = ?
                AND COLUMN_NAME  = ?
              LIMIT 1',
            [$db, $this->table, $column]
        );

        if ($col && strtoupper((string)$col->IS_NULLABLE) === 'NO') {
            DB::statement(sprintf(
                'ALTER TABLE `%s` MODIFY `%s` %s NULL',
                $this->table, $column, $sqlType
            ));
        }
    }

    public function up(): void
    {
        $t = $this->table;

        // 1) Columnas
        Schema::table($t, function (Blueprint $tb) use ($t) {
            if (!Schema::hasColumn($t, 'catalog_item_id')) {
                $tb->unsignedBigInteger('catalog_item_id')->nullable()->after('order_id');
            }

            // Campos comunes (solo si faltan; seguros para re-ejecutar)
            if (!Schema::hasColumn($t, 'name'))      $tb->string('name', 190)->nullable();
            if (!Schema::hasColumn($t, 'sku'))       $tb->string('sku', 64)->nullable();
            if (!Schema::hasColumn($t, 'price'))     $tb->decimal('price', 10, 2)->default(0);
            if (!Schema::hasColumn($t, 'qty'))       $tb->integer('qty')->default(1);
            if (!Schema::hasColumn($t, 'amount'))    $tb->decimal('amount', 10, 2)->default(0);
            if (!Schema::hasColumn($t, 'currency'))  $tb->char('currency', 3)->default('MXN');
            if (!Schema::hasColumn($t, 'tax_rate'))  $tb->decimal('tax_rate', 5, 2)->default(0);
            if (!Schema::hasColumn($t, 'discount'))  $tb->decimal('discount', 10, 2)->default(0);
            if (!Schema::hasColumn($t, 'meta'))      $tb->json('meta')->nullable();
        });

        // 2) Si existe product_id, volverlo NULL (sin requerir doctrine/dbal)
        if (Schema::hasColumn($t, 'product_id')) {
            // Ajusta al tipo real si difiere (aquí asumimos UNSIGNED BIGINT)
            $this->makeNullableIfNeeded('product_id', 'BIGINT UNSIGNED');
        }

        // 3) Migración de datos product_id -> catalog_item_id (solo si catalog está NULL)
        if (Schema::hasColumn($t, 'product_id') && Schema::hasColumn($t, 'catalog_item_id')) {
            DB::statement("
                UPDATE `{$t}`
                   SET `catalog_item_id` = `product_id`
                 WHERE `catalog_item_id` IS NULL
                   AND `product_id` IS NOT NULL
            ");
        }

        // 4) Índices (solo si faltan)
        if (Schema::hasColumn($t, 'order_id') && !$this->indexExists('order_items_order_id_index')) {
            Schema::table($t, fn (Blueprint $tb) => $tb->index('order_id', 'order_items_order_id_index'));
        }
        if (Schema::hasColumn($t, 'catalog_item_id') && !$this->indexExists('order_items_catalog_item_id_index')) {
            Schema::table($t, fn (Blueprint $tb) => $tb->index('catalog_item_id', 'order_items_catalog_item_id_index'));
        }
        if (Schema::hasColumn($t, 'product_id') && !$this->indexExists('order_items_product_id_index')) {
            Schema::table($t, fn (Blueprint $tb) => $tb->index('product_id', 'order_items_product_id_index'));
        }
    }

    public function down(): void
    {
        $t = $this->table;

        // Eliminar índices añadidos
        foreach ([
            'order_items_product_id_index',
            'order_items_catalog_item_id_index',
            'order_items_order_id_index',
        ] as $idx) {
            if ($this->indexExists($idx)) {
                Schema::table($t, fn (Blueprint $tb) => $tb->dropIndex($idx));
            }
        }

        // Quitar columnas agregadas (respetamos product_id existente)
        Schema::table($t, function (Blueprint $tb) use ($t) {
            if (Schema::hasColumn($t, 'catalog_item_id')) $tb->dropColumn('catalog_item_id');
            if (Schema::hasColumn($t, 'meta'))            $tb->dropColumn('meta');
            if (Schema::hasColumn($t, 'discount'))        $tb->dropColumn('discount');
            if (Schema::hasColumn($t, 'tax_rate'))        $tb->dropColumn('tax_rate');
            if (Schema::hasColumn($t, 'currency'))        $tb->dropColumn('currency');
            if (Schema::hasColumn($t, 'amount'))          $tb->dropColumn('amount');
            if (Schema::hasColumn($t, 'qty'))             $tb->dropColumn('qty');
            if (Schema::hasColumn($t, 'price'))           $tb->dropColumn('price');
            if (Schema::hasColumn($t, 'sku'))             $tb->dropColumn('sku');
            if (Schema::hasColumn($t, 'name'))            $tb->dropColumn('name');
        });

        // NOTA: no revertimos product_id a NOT NULL para evitar romper datos.
    }
};
