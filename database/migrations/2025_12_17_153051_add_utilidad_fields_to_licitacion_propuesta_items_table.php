<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('licitacion_propuesta_items', function (Blueprint $table) {

            if (!Schema::hasColumn('licitacion_propuesta_items', 'utilidad_pct')) {
                $table->decimal('utilidad_pct', 8, 2)->nullable()->after('subtotal');
            }

            if (!Schema::hasColumn('licitacion_propuesta_items', 'utilidad_monto')) {
                $table->decimal('utilidad_monto', 15, 2)->nullable()->after('utilidad_pct');
            }

            if (!Schema::hasColumn('licitacion_propuesta_items', 'subtotal_con_utilidad')) {
                $table->decimal('subtotal_con_utilidad', 15, 2)->nullable()->after('utilidad_monto');
            }

            if (!Schema::hasColumn('licitacion_propuesta_items', 'costo')) {
                $table->decimal('costo', 15, 2)->nullable()->after('subtotal_con_utilidad');
            }

            if (!Schema::hasColumn('licitacion_propuesta_items', 'costo_jureto')) {
                $table->decimal('costo_jureto', 15, 2)->nullable()->after('costo');
            }
        });
    }

    public function down()
    {
        Schema::table('licitacion_propuesta_items', function (Blueprint $table) {

            $cols = [
                'utilidad_pct',
                'utilidad_monto',
                'subtotal_con_utilidad',
                'costo',
                'costo_jureto',
            ];

            // bajar “seguro”: solo dropea las que existan
            foreach ($cols as $col) {
                if (Schema::hasColumn('licitacion_propuesta_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
