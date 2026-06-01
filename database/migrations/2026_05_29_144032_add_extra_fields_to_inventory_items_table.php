<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('internal_code')->nullable()->after('serial_number');
            $table->string('department')->nullable()->after('location');
            $table->string('supplier')->nullable()->after('department');
            $table->date('purchase_date')->nullable()->after('supplier');
            $table->decimal('purchase_cost', 12, 2)->nullable()->after('purchase_date');
            $table->date('warranty_until')->nullable()->after('purchase_cost');
            $table->string('processor')->nullable()->after('warranty_until');
            $table->string('ram')->nullable()->after('processor');
            $table->string('storage')->nullable()->after('ram');
            $table->string('operating_system')->nullable()->after('storage');
            $table->string('mac_address')->nullable()->after('operating_system');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn([
                'internal_code', 'department', 'supplier', 'purchase_date',
                'purchase_cost', 'warranty_until', 'processor', 'ram',
                'storage', 'operating_system', 'mac_address',
            ]);
        });
    }
};