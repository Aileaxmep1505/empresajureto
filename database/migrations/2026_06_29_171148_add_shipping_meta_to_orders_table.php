<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'shipping_meta')) {
                $table->json('shipping_meta')->nullable()->after('shipment_status');
            }

            if (!Schema::hasColumn('orders', 'shipping_tracking_url')) {
                $table->string('shipping_tracking_url')->nullable()->after('shipping_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'shipping_meta')) {
                $table->dropColumn('shipping_meta');
            }

            if (Schema::hasColumn('orders', 'shipping_tracking_url')) {
                $table->dropColumn('shipping_tracking_url');
            }
        });
    }
};