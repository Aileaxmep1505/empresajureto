<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_product_sections', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();

            /*
             * manual = eliges productos uno por uno
             * category = toma productos de una categoría
             */
            $table->enum('source_type', ['manual', 'category'])->default('manual');

            $table->foreignId('category_product_id')
                ->nullable()
                ->constrained('category_products')
                ->nullOnDelete();

            $table->unsignedInteger('products_limit')->default(12);

            $table->boolean('is_active')->default(true);

            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_product_sections');
    }
};