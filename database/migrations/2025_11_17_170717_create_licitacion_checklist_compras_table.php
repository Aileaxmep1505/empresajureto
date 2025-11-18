<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('licitacion_checklist_compras', function (Blueprint $table) {
            $table->id();

            $table->foreignId('licitacion_id')
                ->constrained('licitaciones')
                ->cascadeOnDelete();

            $table->string('descripcion_item');
            $table->boolean('completado')->default(false);
            $table->date('fecha_entregado')->nullable();
            $table->string('entregado_por')->nullable();
            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licitacion_checklist_compras');
    }
};
