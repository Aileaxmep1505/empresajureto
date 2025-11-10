<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        // 1) Asegurar columnas (nullable primero)
        Schema::table('tickets', function (Blueprint $t) {
            if (!Schema::hasColumn('tickets', 'title')) {
                $t->string('title')->nullable()->after('folio');
            }
            if (!Schema::hasColumn('tickets', 'created_by')) {
                $t->unsignedBigInteger('created_by')->nullable()->after('title');
                $t->index('created_by', 'tickets_created_by_index');
            }
        });

        // 2) Usuario fallback
        $fallbackId = DB::table('users')->value('id');
        if (!$fallbackId) {
            $fallbackId = DB::table('users')->insertGetId([
                'name'       => 'System',
                'email'      => 'system+'.Str::random(8).'@local.test',
                'password'   => bcrypt(Str::random(32)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3) Backfill de title si viene null
        DB::table('tickets')->whereNull('title')
            ->update(['title' => DB::raw("CONCAT('Ticket ', COALESCE(folio, id))")]);

        // 4) Copiar owner_id -> created_by SOLO si owner existe en users
        if (Schema::hasColumn('tickets', 'owner_id')) {
            DB::statement("
                UPDATE tickets t
                SET t.created_by = t.owner_id
                WHERE t.created_by IS NULL
                  AND t.owner_id IS NOT NULL
                  AND EXISTS (SELECT 1 FROM users u WHERE u.id = t.owner_id)
            ");
        }

        // 5) Rellenar restantes con fallback
        DB::table('tickets')->whereNull('created_by')->update(['created_by' => $fallbackId]);

        // 6) Blindaje extra (por si hay created_by huérfanos)
        DB::statement("
            UPDATE tickets t
            LEFT JOIN users u ON u.id = t.created_by
            SET t.created_by = {$fallbackId}
            WHERE u.id IS NULL
        ");

        // 7) Volver NOT NULL
        Schema::table('tickets', function (Blueprint $t) {
            $t->unsignedBigInteger('created_by')->nullable(false)->change();
        });

        // 8) Agregar la FK SOLO si no existe (sin intentar dropear nada en up)
        $fkExists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', 'tickets')
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->where('CONSTRAINT_NAME', 'tickets_created_by_foreign')
            ->exists();

        if (!$fkExists) {
            DB::statement("
                ALTER TABLE tickets
                ADD CONSTRAINT tickets_created_by_foreign
                FOREIGN KEY (created_by) REFERENCES users(id)
                ON DELETE CASCADE
            ");
        }
    }

    public function down(): void
    {
        // Quitar FK si existe
        $fkExists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', 'tickets')
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->where('CONSTRAINT_NAME', 'tickets_created_by_foreign')
            ->exists();

        if ($fkExists) {
            DB::statement("ALTER TABLE tickets DROP FOREIGN KEY tickets_created_by_foreign");
        }

        Schema::table('tickets', function (Blueprint $t) {
            try { $t->dropIndex('tickets_created_by_index'); } catch (\Throwable $e) {}
            if (Schema::hasColumn('tickets','created_by')) $t->dropColumn('created_by');
            if (Schema::hasColumn('tickets','title'))      $t->dropColumn('title');
        });
    }
};
