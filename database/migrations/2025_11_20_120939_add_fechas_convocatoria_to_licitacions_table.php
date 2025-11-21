<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('licitacions', function (Blueprint $t) {
            // Guarda múltiples fechas seleccionadas en paso 1
            $t->json('fechas_convocatoria')->nullable()->after('fecha_convocatoria');
        });

        /**
         * Si tu columna "modalidad" es ENUM en MySQL, descomenta esto.
         * Si es string/varchar, NO necesitas tocar DB, solo validación.
         */
        // DB::statement("ALTER TABLE licitacions MODIFY modalidad ENUM('presencial','en_linea','mixta') NOT NULL");
    }

    public function down(): void
    {
        Schema::table('licitacions', function (Blueprint $t) {
            $t->dropColumn('fechas_convocatoria');
        });

        // Si usaste ENUM arriba, revierte:
        // DB::statement("ALTER TABLE licitacions MODIFY modalidad ENUM('presencial','en_linea') NOT NULL");
    }
};
