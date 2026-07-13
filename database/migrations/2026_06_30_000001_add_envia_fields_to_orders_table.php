<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'shipping_provider')) $table->string('shipping_provider')->nullable()->after('total');
            if (!Schema::hasColumn('orders', 'shipping_name')) $table->string('shipping_name')->nullable()->after('shipping_provider');
            if (!Schema::hasColumn('orders', 'shipping_carrier')) $table->string('shipping_carrier')->nullable()->after('shipping_name');
            if (!Schema::hasColumn('orders', 'shipping_service')) $table->string('shipping_service')->nullable()->after('shipping_carrier');
            if (!Schema::hasColumn('orders', 'shipping_eta')) $table->string('shipping_eta')->nullable()->after('shipping_service');
            if (!Schema::hasColumn('orders', 'shipping_amount')) $table->decimal('shipping_amount', 12, 2)->default(0)->after('shipping_eta');
            if (!Schema::hasColumn('orders', 'shipping_logo_url')) $table->text('shipping_logo_url')->nullable()->after('shipping_amount');
            if (!Schema::hasColumn('orders', 'shipping_rate_code')) $table->string('shipping_rate_code')->nullable()->after('shipping_logo_url');
            if (!Schema::hasColumn('orders', 'shipping_status')) $table->string('shipping_status')->nullable()->after('shipping_rate_code');
            if (!Schema::hasColumn('orders', 'tracking_number')) $table->string('tracking_number')->nullable()->after('shipping_status');
            if (!Schema::hasColumn('orders', 'tracking_url')) $table->text('tracking_url')->nullable()->after('tracking_number');
            if (!Schema::hasColumn('orders', 'label_url')) $table->text('label_url')->nullable()->after('tracking_url');
            if (!Schema::hasColumn('orders', 'shipping_raw')) $table->longText('shipping_raw')->nullable()->after('label_url');
            if (!Schema::hasColumn('orders', 'envia_payload')) $table->longText('envia_payload')->nullable()->after('shipping_raw');
        });
    }

    public function down(): void
    {
        //
    }
};
