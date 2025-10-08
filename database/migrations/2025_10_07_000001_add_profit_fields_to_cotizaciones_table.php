<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {

            // % de utilidad global
            if (!Schema::hasColumn('cotizaciones', 'utilidad_global')) {
                $table->decimal('utilidad_global', 8, 2)->default(0)
                      ->after('cliente_id');
            }

            // métricas internas
            if (!Schema::hasColumn('cotizaciones', 'inversion_total')) {
                $table->decimal('inversion_total', 12, 2)->default(0)
                      ->after('total');
            }
            if (!Schema::hasColumn('cotizaciones', 'ganancia_estimada')) {
                $table->decimal('ganancia_estimada', 12, 2)->default(0)
                      ->after('inversion_total');
            }

            // Opcionales solo si NO existen en tu esquema actual
            if (!Schema::hasColumn('cotizaciones', 'validez_dias')) {
                $table->unsignedInteger('validez_dias')->default(15)
                      ->after('ganancia_estimada');
            }
            if (!Schema::hasColumn('cotizaciones', 'notas')) {
                $table->text('notas')->nullable()
                      ->after('validez_dias');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            if (Schema::hasColumn('cotizaciones', 'utilidad_global')) {
                $table->dropColumn('utilidad_global');
            }
            if (Schema::hasColumn('cotizaciones', 'inversion_total')) {
                $table->dropColumn('inversion_total');
            }
            if (Schema::hasColumn('cotizaciones', 'ganancia_estimada')) {
                $table->dropColumn('ganancia_estimada');
            }
            // No tocamos validez_dias / notas en down.
        });
    }
};
