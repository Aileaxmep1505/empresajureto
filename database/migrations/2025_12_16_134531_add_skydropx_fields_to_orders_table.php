<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            // ✅ Si ya existe, NO la vuelvas a agregar
            if (!Schema::hasColumn('orders', 'shipping_label_url')) {
                $table->string('shipping_label_url')->nullable()->after('shipping_code');
            }

            if (!Schema::hasColumn('orders', 'shipping_label_pdf_url')) {
                $table->string('shipping_label_pdf_url')->nullable()->after('shipping_label_url');
            }

            if (!Schema::hasColumn('orders', 'shipping_tracking_url')) {
                $table->string('shipping_tracking_url')->nullable()->after('shipping_label_pdf_url');
            }

            if (!Schema::hasColumn('orders', 'shipment_status')) {
                $table->string('shipment_status')->nullable()->after('status');
            }

            if (!Schema::hasColumn('orders', 'shipping_provider')) {
                $table->string('shipping_provider')->nullable()->after('shipping_name');
            }

            if (!Schema::hasColumn('orders', 'shipping_rate_id')) {
                $table->string('shipping_rate_id')->nullable()->after('shipping_service');
            }

            if (!Schema::hasColumn('orders', 'shipping_meta')) {
                $table->json('shipping_meta')->nullable()->after('address_json');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            // ⚠️ down seguro: solo dropea si existe
            $cols = [
                'shipping_label_url',
                'shipping_label_pdf_url',
                'shipping_tracking_url',
                'shipment_status',
                'shipping_provider',
                'shipping_rate_id',
                'shipping_meta',
            ];

            foreach ($cols as $c) {
                if (Schema::hasColumn('orders', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
