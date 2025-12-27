<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();

            // putaway|pick|transfer|adjust|cycle_count
            $table->string('type', 30);

            $table->foreignId('catalog_item_id')->constrained('catalog_items')->cascadeOnDelete();

            $table->foreignId('from_location_id')->nullable()
                ->constrained('locations')
                ->nullOnDelete();

            $table->foreignId('to_location_id')->nullable()
                ->constrained('locations')
                ->nullOnDelete();

            $table->integer('qty'); // siempre positivo, el tipo indica la intención
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['type', 'created_at']);
            $table->index(['catalog_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
