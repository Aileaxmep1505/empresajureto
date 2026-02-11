<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            // Amazon / SP-API
            if (!Schema::hasColumn('catalog_items', 'amazon_sku')) {
                $table->string('amazon_sku', 120)->nullable()->after('sku');
            }

            if (!Schema::hasColumn('catalog_items', 'amazon_asin')) {
                $table->string('amazon_asin', 32)->nullable()->after('amazon_sku');
            }

            if (!Schema::hasColumn('catalog_items', 'amazon_product_type')) {
                $table->string('amazon_product_type', 80)->nullable()->after('amazon_asin');
            }

            if (!Schema::hasColumn('catalog_items', 'amazon_status')) {
                $table->string('amazon_status', 40)->nullable()->after('amazon_product_type');
            }

            if (!Schema::hasColumn('catalog_items', 'amazon_synced_at')) {
                $table->timestamp('amazon_synced_at')->nullable()->after('amazon_status');
            }

            if (!Schema::hasColumn('catalog_items', 'amazon_last_error')) {
                $table->longText('amazon_last_error')->nullable()->after('amazon_synced_at');
            }

            if (!Schema::hasColumn('catalog_items', 'amazon_listing_response')) {
                $table->longText('amazon_listing_response')->nullable()->after('amazon_last_error');
            }

            // Índices útiles
            if (Schema::hasColumn('catalog_items', 'amazon_sku')) {
                $table->index('amazon_sku');
            }
            if (Schema::hasColumn('catalog_items', 'amazon_asin')) {
                $table->index('amazon_asin');
            }
            if (Schema::hasColumn('catalog_items', 'amazon_synced_at')) {
                $table->index('amazon_synced_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            // Ojo: primero elimina índices si existen
            try { $table->dropIndex(['amazon_sku']); } catch (\Throwable $e) {}
            try { $table->dropIndex(['amazon_asin']); } catch (\Throwable $e) {}
            try { $table->dropIndex(['amazon_synced_at']); } catch (\Throwable $e) {}

            // Luego columnas
            if (Schema::hasColumn('catalog_items', 'amazon_listing_response')) {
                $table->dropColumn('amazon_listing_response');
            }
            if (Schema::hasColumn('catalog_items', 'amazon_last_error')) {
                $table->dropColumn('amazon_last_error');
            }
            if (Schema::hasColumn('catalog_items', 'amazon_synced_at')) {
                $table->dropColumn('amazon_synced_at');
            }
            if (Schema::hasColumn('catalog_items', 'amazon_status')) {
                $table->dropColumn('amazon_status');
            }
            if (Schema::hasColumn('catalog_items', 'amazon_product_type')) {
                $table->dropColumn('amazon_product_type');
            }
            if (Schema::hasColumn('catalog_items', 'amazon_asin')) {
                $table->dropColumn('amazon_asin');
            }
            if (Schema::hasColumn('catalog_items', 'amazon_sku')) {
                $table->dropColumn('amazon_sku');
            }
        });
    }
};
