<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Checamos antes del closure qué columnas existen para usar AFTER de forma segura
        $hasMoneda  = Schema::hasColumn('ventas', 'moneda');
        $hasTotal   = Schema::hasColumn('ventas', 'total');
        $hasEstatus = Schema::hasColumn('ventas', 'estatus');
        $hasEstado  = Schema::hasColumn('ventas', 'estado');
        $hasDesc    = Schema::hasColumn('ventas', 'descuento');

        Schema::table('ventas', function (Blueprint $table) use ($hasMoneda, $hasTotal, $hasEstatus, $hasEstado, $hasDesc) {

            // tipo_cambio (decimal 10,4) por defecto 1
            if (!Schema::hasColumn('ventas', 'tipo_cambio')) {
                $col = $table->decimal('tipo_cambio', 10, 4)->default(1);
                if ($hasMoneda) { $col->after('moneda'); }
            }

            // impuestos (si no existe ni 'impuestos' ni 'iva', creamos 'impuestos')
            if (!Schema::hasColumn('ventas', 'impuestos') && !Schema::hasColumn('ventas', 'iva')) {
                $col = $table->decimal('impuestos', 12, 2)->default(0);
                if ($hasDesc) { $col->after('descuento'); }
            }

            // estatus (solo si NO hay 'estatus' NI 'estado')
            if (!Schema::hasColumn('ventas', 'estatus') && !Schema::hasColumn('ventas', 'estado')) {
                $col = $table->string('estatus', 32)->default('pendiente');
                if ($hasTotal) { $col->after('total'); }
            }

            // fecha (si no existe fecha ni fecha_venta)
            if (!Schema::hasColumn('ventas', 'fecha') && !Schema::hasColumn('ventas', 'fecha_venta')) {
                $col = $table->dateTime('fecha')->nullable();
                // posicionado opcional si existe estatus/estado
                if ($hasEstatus) {
                    $col->after('estatus');
                } elseif ($hasEstado) {
                    $col->after('estado');
                }
            }

            // user_id (FK a users)
            if (!Schema::hasColumn('ventas', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            if (Schema::hasColumn('ventas', 'user_id')) {
                // dropConstrainedForeignId está disponible en versiones modernas
                $table->dropConstrainedForeignId('user_id');
            }
            if (Schema::hasColumn('ventas', 'fecha'))       { $table->dropColumn('fecha'); }
            if (Schema::hasColumn('ventas', 'estatus'))     { $table->dropColumn('estatus'); }
            if (Schema::hasColumn('ventas', 'impuestos'))   { $table->dropColumn('impuestos'); }
            if (Schema::hasColumn('ventas', 'tipo_cambio')) { $table->dropColumn('tipo_cambio'); }
        });
    }
};
