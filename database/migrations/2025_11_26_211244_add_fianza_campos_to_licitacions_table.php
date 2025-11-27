<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('licitaciones', function (Blueprint $t) {
            if (!Schema::hasColumn('licitaciones', 'tipo_fianza')) {
                $t->string('tipo_fianza')->nullable()->after('fecha_fianza');
            }

            if (!Schema::hasColumn('licitaciones', 'observaciones_contrato')) {
                $t->text('observaciones_contrato')->nullable()->after('tipo_fianza');
            }

            if (!Schema::hasColumn('licitaciones', 'fechas_cobro')) {
                $t->json('fechas_cobro')->nullable()->after('observaciones_contrato');
            }
        });
    }

    public function down(): void
    {
        Schema::table('licitaciones', function (Blueprint $t) {
            if (Schema::hasColumn('licitaciones', 'fechas_cobro')) {
                $t->dropColumn('fechas_cobro');
            }
            if (Schema::hasColumn('licitaciones', 'observaciones_contrato')) {
                $t->dropColumn('observaciones_contrato');
            }
            if (Schema::hasColumn('licitaciones', 'tipo_fianza')) {
                $t->dropColumn('tipo_fianza');
            }
        });
    }
};
