<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_product_section_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('home_product_section_id')
                ->constrained('home_product_sections')
                ->cascadeOnDelete();

            $table->foreignId('catalog_item_id')
                ->constrained('catalog_items')
                ->cascadeOnDelete();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['home_product_section_id', 'catalog_item_id'], 'home_section_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_product_section_items');
    }
};