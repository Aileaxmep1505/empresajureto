<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('catalog_items', function (Blueprint $table) {
        $table->foreignId('category_product_id')
            ->nullable()
            ->after('status')
            ->constrained('category_products')
            ->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('catalog_items', function (Blueprint $table) {
        $table->dropConstrainedForeignId('category_product_id');
    });
}
};
