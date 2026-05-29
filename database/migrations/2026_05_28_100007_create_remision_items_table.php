<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('remision_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remision_id')
                ->constrained('remisiones')
                ->cascadeOnDelete();

            $table->foreignId('adjudicacion_item_id')
                ->nullable()
                ->constrained('adjudicacion_items')
                ->nullOnDelete();

            $table->integer('sort')->default(0);
            $table->text('descripcion');
            $table->string('unidad')->nullable();
            $table->decimal('cantidad', 14, 2)->default(1);
            $table->decimal('precio_unitario', 14, 2)->nullable();
            $table->decimal('subtotal', 14, 2)->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remision_items');
    }
};