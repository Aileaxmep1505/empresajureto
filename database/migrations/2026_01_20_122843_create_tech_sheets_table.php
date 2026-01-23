<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tech_sheets', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('reference')->nullable();
            $table->string('identification')->nullable(); // texto corto tipo "Cargador universal para pilas"
            $table->text('user_description')->nullable();
            $table->string('image_path')->nullable();

            // Datos generados por IA
            $table->text('ai_description')->nullable(); // descripción formal
            $table->json('ai_features')->nullable();    // arreglo de características
            $table->json('ai_specs')->nullable();       // arreglo de especificaciones (nombre/valor)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tech_sheets');
    }
};
