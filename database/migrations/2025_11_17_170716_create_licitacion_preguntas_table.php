<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('licitacion_preguntas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('licitacion_id')
                ->constrained('licitaciones')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('texto_pregunta');
            $table->text('notas_internas')->nullable();

            $table->dateTime('fecha_pregunta')->nullable();
            $table->text('texto_respuesta')->nullable();
            $table->dateTime('fecha_respuesta')->nullable();

            $table->boolean('esta_bloqueada')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licitacion_preguntas');
    }
};
