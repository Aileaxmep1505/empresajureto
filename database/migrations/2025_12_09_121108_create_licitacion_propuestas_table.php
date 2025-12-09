<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licitacion_propuestas', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('licitacion_id')->nullable();
            $table->unsignedBigInteger('requisicion_id')->nullable();

            $table->string('codigo', 50)->nullable(); // PRO-2025-0001
            $table->string('titulo')->nullable();
            $table->string('moneda', 10)->default('MXN');
            $table->date('fecha')->nullable();

            // draft, revisar, enviada, adjudicada, no_adjudicada
            $table->string('status', 50)->default('draft');

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('iva', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            $table->timestamps();

            $table->index('licitacion_id');
            $table->index('requisicion_id');
            $table->index('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licitacion_propuestas');
    }
};
