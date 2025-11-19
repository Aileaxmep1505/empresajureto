<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items_originales', function (Blueprint $table) {
            $table->id();

            // Archivo de donde salió este item
            $table->foreignId('licitacion_file_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->string('requisicion')->index();          // CA-0232-2025
            $table->string('partida')->nullable();
            $table->string('clave_verificacion')->nullable()->index();
            $table->string('descripcion_bien');
            $table->text('especificaciones')->nullable();
            $table->decimal('cantidad', 15, 2);
            $table->string('unidad_medida', 50);

            // Se puede capturar marca/modelo en este nivel también
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();

            // Para embeddings de OpenAI si los usas
            $table->json('embedding')->nullable();

            // Relación al item global (una vez fusionado)
            $table->foreignId('item_global_id')
                  ->nullable()
                  ->constrained('items_globales')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items_originales');
    }
};
