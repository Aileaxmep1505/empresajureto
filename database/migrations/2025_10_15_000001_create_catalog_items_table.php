<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('catalog_items')) {
            Schema::create('catalog_items', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('sku')->nullable()->index();
                $table->decimal('price', 12, 2)->default(0);
                $table->decimal('sale_price', 12, 2)->nullable();
                $table->tinyInteger('status')->default(0)->index(); // 0=borrador,1=publicado,2=oculto
                $table->string('image_url')->nullable();
                $table->json('images')->nullable();
                $table->boolean('is_featured')->default(false)->index();
                $table->unsignedBigInteger('brand_id')->nullable()->index();
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->text('excerpt')->nullable();
                $table->longText('description')->nullable();
                $table->timestamp('published_at')->nullable()->index();
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_items');
    }
};
