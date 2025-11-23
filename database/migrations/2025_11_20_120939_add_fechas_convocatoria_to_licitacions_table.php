<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('licitaciones', function (Blueprint $table) {
            $table->json('fechas_convocatoria')
                  ->nullable()
                  ->after('fecha_convocatoria');
        });
    }

    public function down(): void
    {
        Schema::table('licitaciones', function (Blueprint $table) {
            $table->dropColumn('fechas_convocatoria');
        });
    }
};
