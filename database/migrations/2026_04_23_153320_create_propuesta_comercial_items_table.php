<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propuesta_comercial_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('propuesta_comercial_id')->index();

            $table->unsignedInteger('sort')->default(0);
            $table->unsignedInteger('partida_numero')->nullable();
            $table->unsignedInteger('subpartida_numero')->nullable();

            $table->text('descripcion_original');
            $table->string('unidad_solicitada')->nullable();

            $table->decimal('cantidad_minima', 18, 2)->nullable();
            $table->decimal('cantidad_maxima', 18, 2)->nullable();
            $table->decimal('cantidad_cotizada', 18, 2)->nullable();

            $table->unsignedBigInteger('producto_seleccionado_id')->nullable()->index();
            $table->decimal('match_score', 8, 2)->nullable();

            $table->decimal('costo_unitario', 18, 2)->nullable();
            $table->decimal('precio_unitario', 18, 2)->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);

            $table->string('status')->default('pending'); // pending|matched|priced|ignored
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propuesta_comercial_items');
    }
};