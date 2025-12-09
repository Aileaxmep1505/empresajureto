<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licitacion_propuesta_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('licitacion_propuesta_id');
            $table->unsignedBigInteger('licitacion_request_item_id');
            $table->unsignedBigInteger('product_id')->nullable(); // de tabla products

            $table->unsignedTinyInteger('match_score')->nullable(); // 0-100
            $table->string('motivo_seleccion')->nullable();

            $table->string('unidad_propuesta', 50)->nullable();
            $table->decimal('cantidad_propuesta', 15, 2)->nullable();
            $table->decimal('precio_unitario', 15, 2)->nullable();
            $table->decimal('subtotal', 15, 2)->nullable();

            $table->text('notas')->nullable();

            $table->timestamps();

            $table->index('licitacion_propuesta_id');
            $table->index('licitacion_request_item_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licitacion_propuesta_items');
    }
};
