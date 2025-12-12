<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('licitacion_propuestas', function (Blueprint $table) {
            // Puedes ajustar el tipo y el default según tu lógica
            $table->string('merge_status', 30)
                ->default('pending') // o 'none', 'idle', etc.
                ->after('status');   // después de la columna status (ajusta si quieres)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licitacion_propuestas', function (Blueprint $table) {
            $table->dropColumn('merge_status');
        });
    }
};
