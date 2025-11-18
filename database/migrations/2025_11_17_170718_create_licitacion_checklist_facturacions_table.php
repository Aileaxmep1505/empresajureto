<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('licitacion_checklist_facturacion', function (Blueprint $table) {
            $table->id();

            $table->foreignId('licitacion_id')
                ->constrained('licitaciones')
                ->cascadeOnDelete();

            $table->boolean('tiene_factura')->default(false);
            $table->date('fecha_factura')->nullable();
            $table->decimal('monto_factura', 15, 2)->nullable();
            $table->string('evidencia_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licitacion_checklist_facturacion');
    }
};
