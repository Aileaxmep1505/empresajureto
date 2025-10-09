<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // === ventas: inversion_total, ganancia_estimada ===
        if (Schema::hasTable('ventas')) {
            Schema::table('ventas', function (Blueprint $table) {
                if (!Schema::hasColumn('ventas', 'inversion_total')) {
                    $table->decimal('inversion_total', 14, 2)->default(0)->after('iva');
                }
                if (!Schema::hasColumn('ventas', 'ganancia_estimada')) {
                    $table->decimal('ganancia_estimada', 14, 2)->default(0)->after('inversion_total');
                }

                // Índices útiles (opcionales)
                if (!Schema::hasColumn('ventas', 'cliente_id')) {
                    // nada: ya existe en tu esquema
                } else {
                    $table->index('cliente_id', 'ventas_cliente_id_idx');
                }
                if (Schema::hasColumn('ventas', 'cotizacion_id')) {
                    $table->index('cotizacion_id', 'ventas_cotizacion_id_idx');
                }
            });
        }

        // === venta_productos: cost, importe_sin_iva, iva_monto ===
        if (Schema::hasTable('venta_productos')) {
            Schema::table('venta_productos', function (Blueprint $table) {
                if (!Schema::hasColumn('venta_productos', 'cost')) {
                    $table->decimal('cost', 14, 2)->nullable()->after('precio_unitario');
                }
                if (!Schema::hasColumn('venta_productos', 'importe_sin_iva')) {
                    $table->decimal('importe_sin_iva', 14, 2)->nullable()->after('descuento');
                }
                if (!Schema::hasColumn('venta_productos', 'iva_monto')) {
                    $table->decimal('iva_monto', 14, 2)->nullable()->after('importe_sin_iva');
                }

                if (Schema::hasColumn('venta_productos', 'venta_id')) {
                    $table->index('venta_id', 'venta_productos_venta_id_idx');
                }
                if (Schema::hasColumn('venta_productos', 'producto_id')) {
                    $table->index('producto_id', 'venta_productos_producto_id_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ventas')) {
            Schema::table('ventas', function (Blueprint $table) {
                if (Schema::hasColumn('ventas', 'ganancia_estimada')) {
                    $table->dropColumn('ganancia_estimada');
                }
                if (Schema::hasColumn('ventas', 'inversion_total')) {
                    $table->dropColumn('inversion_total');
                }
                // limpia índices opcionales
                if ($this->indexExists('ventas', 'ventas_cliente_id_idx')) {
                    $table->dropIndex('ventas_cliente_id_idx');
                }
                if ($this->indexExists('ventas', 'ventas_cotizacion_id_idx')) {
                    $table->dropIndex('ventas_cotizacion_id_idx');
                }
            });
        }

        if (Schema::hasTable('venta_productos')) {
            Schema::table('venta_productos', function (Blueprint $table) {
                if (Schema::hasColumn('venta_productos', 'iva_monto')) {
                    $table->dropColumn('iva_monto');
                }
                if (Schema::hasColumn('venta_productos', 'importe_sin_iva')) {
                    $table->dropColumn('importe_sin_iva');
                }
                if (Schema::hasColumn('venta_productos', 'cost')) {
                    $table->dropColumn('cost');
                }
                if ($this->indexExists('venta_productos', 'venta_productos_venta_id_idx')) {
                    $table->dropIndex('venta_productos_venta_id_idx');
                }
                if ($this->indexExists('venta_productos', 'venta_productos_producto_id_idx')) {
                    $table->dropIndex('venta_productos_producto_id_idx');
                }
            });
        }
    }

    // Helper pequeño para evitar excepciones en dropIndex si no existe
    private function indexExists(string $table, string $index): bool
    {
        try {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $doctrineTable = $sm->listTableDetails($table);
            return $doctrineTable->hasIndex($index);
        } catch (\Throwable $e) {
            return false;
        }
    }
};
