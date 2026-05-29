<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('remisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjudicacion_id')
                ->constrained('adjudicaciones')
                ->cascadeOnDelete();

            $table->string('folio')->nullable();
            $table->date('fecha')->nullable();

            // borrador | emitida | entregada | cancelada
            $table->string('status')->default('borrador');

            $table->string('recibe_nombre')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('pdf_path')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remisiones');
    }
};