<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('licitacion_contabilidad', function (Blueprint $table) {
            $table->id();

            $table->foreignId('licitacion_id')
                ->constrained('licitaciones')
                ->cascadeOnDelete();

            $table->decimal('monto_inversion_estimado', 15, 2)->nullable();
            $table->decimal('costo_total', 15, 2)->nullable();
            $table->json('detalle_costos')->nullable(); // por producto
            $table->decimal('utilidad_estimada', 15, 2)->nullable();
            $table->text('notas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licitacion_contabilidad');
    }
};
