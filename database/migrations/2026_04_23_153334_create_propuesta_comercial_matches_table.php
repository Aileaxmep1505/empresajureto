<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propuesta_comercial_matches', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('propuesta_comercial_item_id')->index();
            $table->unsignedBigInteger('product_id')->index();

            $table->unsignedTinyInteger('rank')->default(1); // 1, 2, 3
            $table->decimal('score', 8, 2)->default(0);

            $table->boolean('unidad_coincide')->default(false);
            $table->boolean('seleccionado')->default(false);

            $table->text('motivo')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propuesta_comercial_matches');
    }
};