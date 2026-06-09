<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Agrega solo las columnas que faltan a la tabla EXISTENTE.
        Schema::table('adjudicaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('adjudicaciones', 'titulo')) {
                $table->string('titulo')->nullable()->after('folio');
            }
            if (!Schema::hasColumn('adjudicaciones', 'cliente')) {
                $table->string('cliente')->nullable()->after('titulo');
            }
            if (!Schema::hasColumn('adjudicaciones', 'total_partidas')) {
                $table->integer('total_partidas')->default(0);
            }
            if (!Schema::hasColumn('adjudicaciones', 'ganadas_count')) {
                $table->integer('ganadas_count')->default(0);
            }
            if (!Schema::hasColumn('adjudicaciones', 'perdidas_count')) {
                $table->integer('perdidas_count')->default(0);
            }
            if (!Schema::hasColumn('adjudicaciones', 'subtotal_ganadas')) {
                $table->decimal('subtotal_ganadas', 14, 2)->default(0);
            }
            if (!Schema::hasColumn('adjudicaciones', 'total_ganadas')) {
                $table->decimal('total_ganadas', 14, 2)->default(0);
            }
        });

        // 2) Crea la tabla hija solo si no existe.
        if (!Schema::hasTable('adjudicacion_items')) {
            Schema::create('adjudicacion_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('adjudicacion_id')
                    ->constrained('adjudicaciones')
                    ->cascadeOnDelete();

                $table->unsignedBigInteger('propuesta_comercial_item_id')->nullable();

                $table->integer('sort')->default(0);
                $table->string('partida_numero')->nullable();
                $table->string('descripcion_original', 1000)->nullable();
                $table->string('unidad_solicitada', 50)->nullable();
                $table->decimal('cantidad', 14, 2)->default(0);

                $table->decimal('costo_unitario', 14, 2)->default(0);
                $table->decimal('precio_unitario', 14, 2)->default(0);
                $table->decimal('subtotal', 14, 2)->default(0);

                // ganada | perdida
                $table->string('resultado')->default('ganada');

                // Antecedente / análisis de las perdidas
                $table->text('motivo_perdida')->nullable();
                $table->string('proveedor_ganador')->nullable();
                $table->decimal('precio_ganador', 14, 2)->nullable();
                $table->decimal('precio_ofertado', 14, 2)->nullable();
                $table->decimal('diferencia_monto', 14, 2)->nullable();
                $table->decimal('diferencia_pct', 8, 2)->nullable();
                $table->text('analisis_ia')->nullable();

                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['adjudicacion_id', 'resultado']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('adjudicacion_items');

        Schema::table('adjudicaciones', function (Blueprint $table) {
            foreach (['titulo', 'cliente', 'total_partidas', 'ganadas_count', 'perdidas_count', 'subtotal_ganadas', 'total_ganadas'] as $col) {
                if (Schema::hasColumn('adjudicaciones', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};