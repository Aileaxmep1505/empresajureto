<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pick_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pick_wave_id')->constrained('pick_waves')->cascadeOnDelete();
            $table->foreignId('catalog_item_id')->constrained('catalog_items')->cascadeOnDelete();

            $table->integer('requested_qty')->default(1);
            $table->integer('picked_qty')->default(0);

            // Sugerida por sistema (para ruta)
            $table->foreignId('suggested_location_id')->nullable()
                ->constrained('locations')
                ->nullOnDelete();

            // 0=pending, 1=done, 2=skipped
            $table->unsignedTinyInteger('status')->default(0);

            // Clave para ordenar ruta (ej: "0010-0003-0002-...")
            $table->string('sort_key', 80)->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['pick_wave_id','status']);
            $table->index(['catalog_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pick_items');
    }
};
