<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('licitacion_eventos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('licitacion_id')
                ->constrained('licitaciones')
                ->cascadeOnDelete();

            $table->foreignId('agenda_event_id')
                ->constrained('agenda_events')
                ->cascadeOnDelete();

            $table->string('tipo'); // junta_aclaraciones, recordatorio_preguntas, apertura_propuesta, entrega_muestras, etc.

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licitacion_eventos');
    }
};
