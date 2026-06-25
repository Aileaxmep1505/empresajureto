<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->string('shopify_product_id')->nullable()->after('amazon_listing_response');
            $table->string('shopify_variant_id')->nullable();
            $table->string('shopify_inventory_item_id')->nullable();
            $table->string('shopify_location_id')->nullable();
            $table->timestamp('shopify_synced_at')->nullable();
            $table->text('shopify_last_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropColumn([
                'shopify_product_id',
                'shopify_variant_id',
                'shopify_inventory_item_id',
                'shopify_location_id',
                'shopify_synced_at',
                'shopify_last_error',
            ]);
        });
    }
};