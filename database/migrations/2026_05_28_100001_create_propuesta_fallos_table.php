<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('propuesta_fallos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propuesta_comercial_id')
                ->constrained('propuestas_comerciales')
                ->cascadeOnDelete();

            $table->string('numero_acta')->nullable();
            $table->date('fecha_fallo')->nullable();
            $table->string('file_path')->nullable();          // PDF del acta

            // pending | won | lost | partial
            $table->string('resultado')->default('pending');

            // IA / OCR
            $table->unsignedBigInteger('document_ai_run_id')->nullable();
            $table->string('ocr_status')->nullable();          // pending|processing|done|failed
            $table->longText('ocr_text')->nullable();          // texto crudo del acta

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propuesta_fallos');
    }
};