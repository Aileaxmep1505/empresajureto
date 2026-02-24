<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function dropForeignKeyByColumn(string $table, string $column): void
    {
        $db = DB::getDatabaseName();

        $rows = DB::select("
            SELECT CONSTRAINT_NAME AS fk_name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$db, $table, $column]);

        foreach ($rows as $r) {
            if (!empty($r->fk_name)) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$r->fk_name}`");
            }
        }
    }

    private function dropIndexesByColumn(string $table, string $column): void
    {
        $db = DB::getDatabaseName();

        $rows = DB::select("
            SELECT DISTINCT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND INDEX_NAME <> 'PRIMARY'
        ", [$db, $table, $column]);

        foreach ($rows as $r) {
            if (!empty($r->INDEX_NAME)) {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$r->INDEX_NAME}`");
            }
        }
    }

    /**
     * 🔥 Mata TODAS las foreign keys en el schema actual que referencien $referencedTable
     * (sin importar desde qué tabla vengan).
     */
    private function dropAllForeignKeysReferencing(string $referencedTable): void
    {
        $db = DB::getDatabaseName();

        $rows = DB::select("
            SELECT
                kcu.TABLE_NAME AS table_name,
                kcu.CONSTRAINT_NAME AS constraint_name
            FROM information_schema.KEY_COLUMN_USAGE kcu
            WHERE kcu.TABLE_SCHEMA = ?
              AND kcu.REFERENCED_TABLE_NAME = ?
        ", [$db, $referencedTable]);

        foreach ($rows as $r) {
            if (!empty($r->table_name) && !empty($r->constraint_name)) {
                DB::statement("ALTER TABLE `{$r->table_name}` DROP FOREIGN KEY `{$r->constraint_name}`");
            }
        }
    }

    public function up(): void
    {
        // 0) Migrar owner_id -> assignee_id si aplica
        if (Schema::hasColumn('tickets','owner_id') && Schema::hasColumn('tickets','assignee_id')) {
            DB::statement("UPDATE tickets SET assignee_id = owner_id WHERE assignee_id IS NULL AND owner_id IS NOT NULL");
        }

        // 1) Quitar FK/índices en tickets para columnas a borrar
        if (Schema::hasColumn('tickets','client_id')) {
            $this->dropForeignKeyByColumn('tickets','client_id');
            $this->dropIndexesByColumn('tickets','client_id');
        }
        if (Schema::hasColumn('tickets','owner_id')) {
            $this->dropForeignKeyByColumn('tickets','owner_id');
            $this->dropIndexesByColumn('tickets','owner_id');
        }

        // 2) Borrar columnas viejas de tickets
        Schema::table('tickets', function (Blueprint $table) {
            $drops = [
                'client_id',
                'client_name',
                'opened_at',
                'closed_at',
                'progress',
                'meta',
                'owner_id',

                // por si existen
                'type',
                'numero_licitacion',
                'monto_propuesta',
                'estatus_adjudicacion',
                'licitacion_phase',
                'quick_notes',
            ];

            foreach ($drops as $col) {
                if (Schema::hasColumn('tickets', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        // 3) ticket_documents: quitar stage_id
        if (Schema::hasTable('ticket_documents') && Schema::hasColumn('ticket_documents','stage_id')) {
            $this->dropForeignKeyByColumn('ticket_documents','stage_id');
            $this->dropIndexesByColumn('ticket_documents','stage_id');

            Schema::table('ticket_documents', function (Blueprint $table) {
                if (Schema::hasColumn('ticket_documents','stage_id')) {
                    $table->dropColumn('stage_id');
                }
            });
        }

        /*
         |--------------------------------------------------------------------------
         | 4) 🔥 IMPORTANTÍSIMO:
         |    Antes de dropear tablas viejas, matamos FKs que las referencien.
         |--------------------------------------------------------------------------
         */
        $legacyTables = [
            'ticket_checklist_items',
            'ticket_checklists',
            'ticket_stages',
            'ticket_links',
            'ticket_followers',
            'ticket_sla_events',
        ];

        // Primero dropea FKs que APUNTEN a esas tablas
        foreach ($legacyTables as $t) {
            if (Schema::hasTable($t)) {
                $this->dropAllForeignKeysReferencing($t);
            }
        }

        /*
         |--------------------------------------------------------------------------
         | 5) Dropear tablas en orden (hijas -> padre) por si aún hay relaciones internas
         |--------------------------------------------------------------------------
         */
        $dropOrder = [
            'ticket_checklist_items',
            'ticket_checklists',
            'ticket_sla_events',
            'ticket_links',
            'ticket_followers',
            'ticket_stages',
        ];

        foreach ($dropOrder as $t) {
            if (Schema::hasTable($t)) {
                Schema::drop($t);
            }
        }
    }

    public function down(): void
    {
        // down completo sería recrear el sistema anterior.
    }
};