<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Relación con cotizaciones (opcional si ya existe)
            if (!Schema::hasColumn('ventas', 'cotizacion_id')) {
                $table->unsignedBigInteger('cotizacion_id')->nullable()->after('cliente_id');

                // Agrega FK sólo si existe la tabla de cotizaciones
                if (Schema::hasTable('cotizaciones')) {
                    $table->foreign('cotizacion_id')
                          ->references('id')->on('cotizaciones')
                          ->nullOnDelete();
                }
            }

            // Config de financiamiento (lo usas en convertirAVenta)
            if (!Schema::hasColumn('ventas', 'financiamiento_config')) {
                $table->json('financiamiento_config')->nullable()->after('estado');
            }

            // Datos de factura/timbrado
            if (!Schema::hasColumn('ventas', 'serie')) {
                $table->string('serie', 10)->nullable()->after('total');
            }
            if (!Schema::hasColumn('ventas', 'folio')) {
                $table->unsignedInteger('folio')->nullable()->after('serie');
            }
            if (!Schema::hasColumn('ventas', 'factura_id')) {
                $table->string('factura_id')->nullable()->after('folio');
            }
            if (!Schema::hasColumn('ventas', 'factura_uuid')) {
                $table->string('factura_uuid', 64)->nullable()->after('factura_id');
            }
            if (!Schema::hasColumn('ventas', 'factura_pdf_url')) {
                $table->string('factura_pdf_url')->nullable()->after('factura_uuid');
            }
            if (!Schema::hasColumn('ventas', 'factura_xml_url')) {
                $table->string('factura_xml_url')->nullable()->after('factura_pdf_url');
            }
            if (!Schema::hasColumn('ventas', 'timbrada_en')) {
                $table->timestamp('timbrada_en')->nullable()->after('factura_xml_url');
            }

            // Índices útiles
            if (Schema::hasColumn('ventas', 'factura_uuid')) {
                $table->unique('factura_uuid', 'ventas_factura_uuid_unique');
            }
            if (Schema::hasColumn('ventas', 'factura_id')) {
                $table->index('factura_id', 'ventas_factura_id_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Quitar índices antes de columnas
            if (Schema::hasColumn('ventas', 'factura_uuid')) {
                $table->dropUnique('ventas_factura_uuid_unique');
            }
            if (Schema::hasColumn('ventas', 'factura_id')) {
                $table->dropIndex('ventas_factura_id_idx');
            }

            foreach ([
                'timbrada_en',
                'factura_xml_url',
                'factura_pdf_url',
                'factura_uuid',
                'factura_id',
                'folio',
                'serie',
                'financiamiento_config',
            ] as $col) {
                if (Schema::hasColumn('ventas', $col)) {
                    $table->dropColumn($col);
                }
            }

            // FK + columna de cotizacion_id
            if (Schema::hasColumn('ventas', 'cotizacion_id')) {
                // Si tu versión de Laravel lo soporta:
                try { $table->dropConstrainedForeignId('cotizacion_id'); }
                catch (\Throwable $e) {
                    // Fallback genérico
                    try { $table->dropForeign(['cotizacion_id']); } catch (\Throwable $e) {}
                    $table->dropColumn('cotizacion_id');
                    return;
                }
                // Si dropConstrainedForeignId no borró la columna:
                if (Schema::hasColumn('ventas', 'cotizacion_id')) {
                    $table->dropColumn('cotizacion_id');
                }
            }
        });
    }
};
