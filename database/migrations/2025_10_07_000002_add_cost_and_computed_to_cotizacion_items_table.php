<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cotizacion_productos', function (Blueprint $table) {

            // COSTO base
            if (!Schema::hasColumn('cotizacion_productos', 'cost')) {
                $table->decimal('cost', 12, 2)->default(0)
                      ->after('cantidad');
            }

            // Ya tienes: precio_unitario, descuento, iva_porcentaje, importe
            // Agregamos snapshots:
            if (!Schema::hasColumn('cotizacion_productos', 'importe_sin_iva')) {
                $table->decimal('importe_sin_iva', 12, 2)->default(0)
                      ->after('iva_porcentaje');
            }
            if (!Schema::hasColumn('cotizacion_productos', 'iva_monto')) {
                $table->decimal('iva_monto', 12, 2)->default(0)
                      ->after('importe_sin_iva');
            }
            if (!Schema::hasColumn('cotizacion_productos', 'importe_total')) {
                $table->decimal('importe_total', 12, 2)->default(0)
                      ->after('iva_monto');
            }

            // Índices: omito crearlos para no chocar con existentes.
            // Si los requieres, agrégalos en una migración separada.
        });
    }

    public function down(): void
    {
        Schema::table('cotizacion_productos', function (Blueprint $table) {
            if (Schema::hasColumn('cotizacion_productos', 'cost'))            $table->dropColumn('cost');
            if (Schema::hasColumn('cotizacion_productos', 'importe_sin_iva')) $table->dropColumn('importe_sin_iva');
            if (Schema::hasColumn('cotizacion_productos', 'iva_monto'))       $table->dropColumn('iva_monto');
            if (Schema::hasColumn('cotizacion_productos', 'importe_total'))   $table->dropColumn('importe_total');
        });
    }
};
