<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            if (!Schema::hasColumn('catalog_items', 'unit_measure')) {
                $table->string('unit_measure', 50)->default('pieza')->after('stock_max');
            }
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            if (Schema::hasColumn('catalog_items', 'unit_measure')) {
                $table->dropColumn('unit_measure');
            }
        });
    }
};