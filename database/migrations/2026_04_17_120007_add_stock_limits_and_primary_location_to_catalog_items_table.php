<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            if (!Schema::hasColumn('catalog_items', 'stock_min')) {
                $table->unsignedInteger('stock_min')->nullable()->after('stock');
            }

            if (!Schema::hasColumn('catalog_items', 'stock_max')) {
                $table->unsignedInteger('stock_max')->nullable()->after('stock_min');
            }

            if (!Schema::hasColumn('catalog_items', 'primary_location_id')) {
                $table->unsignedBigInteger('primary_location_id')->nullable()->after('category_product_id');
                $table->foreign('primary_location_id')
                    ->references('id')
                    ->on('locations')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            if (Schema::hasColumn('catalog_items', 'primary_location_id')) {
                try {
                    $table->dropForeign(['primary_location_id']);
                } catch (\Throwable $e) {
                }

                $table->dropColumn('primary_location_id');
            }

            if (Schema::hasColumn('catalog_items', 'stock_max')) {
                $table->dropColumn('stock_max');
            }

            if (Schema::hasColumn('catalog_items', 'stock_min')) {
                $table->dropColumn('stock_min');
            }
        });
    }
};