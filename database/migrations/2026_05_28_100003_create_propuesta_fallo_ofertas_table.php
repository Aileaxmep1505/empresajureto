<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('propuesta_fallo_ofertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propuesta_fallo_partida_id')
                ->constrained('propuesta_fallo_partidas')
                ->cascadeOnDelete();

            $table->string('empresa');
            $table->boolean('es_jureto')->default(false);
            $table->boolean('gano')->default(false);

            $table->decimal('precio', 14, 2)->nullable();
            $table->decimal('cantidad', 14, 2)->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propuesta_fallo_ofertas');
    }
};