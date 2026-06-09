<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('propuesta_resultado_items');
        Schema::dropIfExists('propuesta_resultados');

        Schema::create('propuesta_resultados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propuesta_comercial_id')
                ->constrained('propuestas_comerciales')
                ->cascadeOnDelete();

            $table->string('folio')->nullable();
            $table->string('titulo')->nullable();
            $table->string('cliente')->nullable();

            $table->integer('total_partidas')->default(0);
            $table->integer('ganadas_count')->default(0);
            $table->integer('perdidas_count')->default(0);

            $table->decimal('subtotal_ganadas', 14, 2)->default(0);
            $table->decimal('total_ganadas', 14, 2)->default(0);

            $table->string('status')->default('generada');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('propuesta_resultado_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propuesta_resultado_id')
                ->constrained('propuesta_resultados')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('propuesta_comercial_item_id')->nullable();

            $table->integer('sort')->default(0);
            $table->string('partida_numero')->nullable();
            $table->string('descripcion_original', 1000)->nullable();
            $table->string('unidad_solicitada', 50)->nullable();
            $table->decimal('cantidad', 14, 2)->default(0);

            $table->decimal('costo_unitario', 14, 2)->default(0);
            $table->decimal('precio_unitario', 14, 2)->default(0);
            $table->decimal('precio_ofertado', 14, 2)->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);

            $table->string('resultado')->default('ganada'); // ganada | perdida

            $table->text('motivo_perdida')->nullable();
            $table->string('proveedor_ganador')->nullable();
            $table->decimal('precio_ganador', 14, 2)->nullable();
            $table->decimal('diferencia_monto', 14, 2)->nullable();
            $table->decimal('diferencia_pct', 8, 2)->nullable();
            $table->text('analisis_ia')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['propuesta_resultado_id', 'resultado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propuesta_resultado_items');
        Schema::dropIfExists('propuesta_resultados');
    }
};