<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ✅ Asegurar que existan tablas antes de intentar FK
        if (!Schema::hasTable('pick_waves') || !Schema::hasTable('pick_items')) {
            return;
        }

        // ✅ Asegurar InnoDB (si alguna quedó en otro engine, MySQL no permite FK)
        try { DB::statement("ALTER TABLE `pick_items` ENGINE=InnoDB"); } catch (\Throwable $e) {}
        try { DB::statement("ALTER TABLE `pick_waves` ENGINE=InnoDB"); } catch (\Throwable $e) {}

        // ✅ Detectar el tipo EXACTO de pick_items.id (int/bigint, unsigned, etc)
        $col = DB::selectOne("
            SELECT COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'pick_items'
              AND COLUMN_NAME = 'id'
            LIMIT 1
        ");

        if (!$col || empty($col->COLUMN_TYPE)) {
            return;
        }

        $idType = $col->COLUMN_TYPE; // ej: 'bigint unsigned' o 'int unsigned'

        // ✅ Forzar que pick_waves.current_pick_item_id sea del MISMO tipo que pick_items.id
        // (esto arregla el errno:150 por mismatch)
        DB::statement("ALTER TABLE `pick_waves` MODIFY `current_pick_item_id` {$idType} NULL");

        // ✅ Asegurar índice en la columna FK (por si MySQL no lo crea por alguna razón)
        $idx = DB::selectOne("
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'pick_waves'
              AND INDEX_NAME = 'pick_waves_current_pick_item_id_index'
            LIMIT 1
        ");

        if (!$idx) {
            try { DB::statement("ALTER TABLE `pick_waves` ADD INDEX `pick_waves_current_pick_item_id_index` (`current_pick_item_id`)"); } catch (\Throwable $e) {}
        }

        // ✅ Si existe FK previo (por intentos anteriores), lo tiramos
        try {
            DB::statement("ALTER TABLE `pick_waves` DROP FOREIGN KEY `pick_waves_current_pick_item_id_foreign`");
        } catch (\Throwable $e) {}

        // ✅ Crear FK ya con tipos compatibles
        DB::statement("
            ALTER TABLE `pick_waves`
            ADD CONSTRAINT `pick_waves_current_pick_item_id_foreign`
            FOREIGN KEY (`current_pick_item_id`)
            REFERENCES `pick_items`(`id`)
            ON DELETE SET NULL
        ");
    }

    public function down(): void
    {
        if (!Schema::hasTable('pick_waves')) {
            return;
        }

        try {
            DB::statement("ALTER TABLE `pick_waves` DROP FOREIGN KEY `pick_waves_current_pick_item_id_foreign`");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE `pick_waves` DROP INDEX `pick_waves_current_pick_item_id_index`");
        } catch (\Throwable $e) {}
    }
};
