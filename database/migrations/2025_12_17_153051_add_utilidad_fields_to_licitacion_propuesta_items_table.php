<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('licitacion_propuesta_items', function (Blueprint $table) {
        // si quieres que sean NULL por defecto (recomendado)
        $table->decimal('utilidad_pct', 8, 2)->nullable()->after('subtotal');
        $table->decimal('utilidad_monto', 15, 2)->nullable()->after('utilidad_pct');
        $table->decimal('subtotal_con_utilidad', 15, 2)->nullable()->after('utilidad_monto');

        $table->decimal('costo', 15, 2)->nullable()->after('subtotal_con_utilidad');
        $table->decimal('costo_jureto', 15, 2)->nullable()->after('costo');
    });
}

public function down()
{
    Schema::table('licitacion_propuesta_items', function (Blueprint $table) {
        $table->dropColumn([
            'utilidad_pct',
            'utilidad_monto',
            'subtotal_con_utilidad',
            'costo',
            'costo_jureto',
        ]);
    });
}

    
};
