<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('licitacion_archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('licitacion_id')
                ->constrained('licitaciones')
                ->cascadeOnDelete();

            $table->string('tipo'); // convocatoria, acta_antecedente, acta_apertura, fallo, contrato, otro
            $table->string('path');
            $table->string('nombre_original');
            $table->string('mime_type')->nullable();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licitacion_archivos');
    }
};
