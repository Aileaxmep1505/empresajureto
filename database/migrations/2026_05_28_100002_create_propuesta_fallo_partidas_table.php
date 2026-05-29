<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('propuesta_fallo_partidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propuesta_fallo_id')
                ->constrained('propuesta_fallos')
                ->cascadeOnDelete();

            $table->foreignId('propuesta_comercial_item_id')
                ->nullable()
                ->constrained('propuesta_comercial_items')
                ->nullOnDelete();

            $table->string('partida_label')->nullable();
            $table->text('descripcion')->nullable();
            $table->decimal('cantidad', 14, 2)->nullable();

            // jureto | competidor | desierto
            $table->string('ganador')->nullable();
            $table->string('empresa_ganadora')->nullable();

            $table->decimal('precio_ganador', 14, 2)->nullable();
            $table->decimal('nuestro_precio', 14, 2)->nullable();
            $table->decimal('diferencia', 14, 2)->nullable();   // nuestro_precio - precio_ganador

            $table->text('motivo')->nullable();                 // por qué ganaron

            // ai | manual  (para revertir por partida)
            $table->string('source')->default('ai');

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propuesta_fallo_partidas');
    }
};