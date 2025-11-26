<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licitaciones', function (Blueprint $table) {
            // Solo la añadimos si no existe
            if (!Schema::hasColumn('licitaciones', 'fecha_acta_apertura')) {
                // Solo fecha (no datetime)
                $table->date('fecha_acta_apertura')
                    ->nullable()
                    ->after('fecha_apertura_propuesta');
            }
        });
    }

    public function down(): void
    {
        Schema::table('licitaciones', function (Blueprint $table) {
            if (Schema::hasColumn('licitaciones', 'fecha_acta_apertura')) {
                $table->dropColumn('fecha_acta_apertura');
            }
        });
    }
};
