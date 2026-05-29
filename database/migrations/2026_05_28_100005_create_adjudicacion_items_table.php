<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('adjudicacion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjudicacion_id')
                ->constrained('adjudicaciones')
                ->cascadeOnDelete();

            $table->foreignId('propuesta_comercial_item_id')
                ->nullable()
                ->constrained('propuesta_comercial_items')
                ->nullOnDelete();

            $table->integer('sort')->default(0);
            $table->text('descripcion');
            $table->string('unidad')->nullable();
            $table->decimal('cantidad', 14, 2)->default(1);
            $table->decimal('costo_unitario', 14, 2)->nullable();
            $table->decimal('precio_unitario', 14, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjudicacion_items');
    }
};