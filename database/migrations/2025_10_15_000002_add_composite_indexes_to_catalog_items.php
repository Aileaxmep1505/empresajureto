<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('catalog_items')) {
            Schema::table('catalog_items', function (Blueprint $table) {
                try { $table->index(['status', 'is_featured']); } catch (\Throwable $e) {}
                try { $table->index(['category_id', 'status']); } catch (\Throwable $e) {}
                try { $table->index(['brand_id', 'status']); } catch (\Throwable $e) {}
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('catalog_items')) {
            Schema::table('catalog_items', function (Blueprint $table) {
                // Silencioso: algunos motores no requieren dropIndex con nombre exacto
            });
        }
    }
};
