<?php

// database/migrations/2025_11_02_000001_add_shipping_label_url_to_orders.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('orders', function (Blueprint $t) {
            $t->string('shipping_label_url')->nullable()->after('shipping_eta');
        });
    }
    public function down(): void {
        Schema::table('orders', function (Blueprint $t) {
            $t->dropColumn('shipping_label_url');
        });
    }
};
