<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            // Todo opcional
            $t->string('name')->nullable();
            $t->string('sku')->nullable();
            $t->string('supplier_sku')->nullable();
            $t->string('unit')->nullable();
            $t->decimal('weight', 10, 3)->nullable();
            $t->decimal('cost', 12, 2)->nullable();
            $t->decimal('price', 12, 2)->nullable();
            $t->decimal('market_price', 12, 2)->nullable();
            $t->decimal('bid_price', 12, 2)->nullable();
            $t->string('dimensions')->nullable();
            $t->string('color')->nullable();
            $t->unsignedInteger('pieces_per_unit')->nullable();
            $t->boolean('active')->default(true);

            $t->string('brand')->nullable();
            $t->string('category')->nullable();
            $t->string('material')->nullable();

            $t->text('description')->nullable();
            $t->text('notes')->nullable();
            $t->string('tags')->nullable();      // coma-separadas

            $t->string('image_path')->nullable(); // storage path
            $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('products');
    }
};
