<?php
// database/migrations/2025_09_30_000000_add_image_url_to_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'image_url')) {
                $table->text('image_url')->nullable()->after('image_path');
            }
        });
    }

    public function down(): void {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'image_url')) {
                $table->dropColumn('image_url');
            }
        });
    }
};
