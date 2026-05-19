<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            if (!Schema::hasColumn('catalog_items', 'content_quantity')) {
                $table->unsignedInteger('content_quantity')->default(1)->after('unit_measure');
            }

            if (!Schema::hasColumn('catalog_items', 'content_unit_measure')) {
                $table->string('content_unit_measure', 50)->default('pieza')->after('content_quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            if (Schema::hasColumn('catalog_items', 'content_unit_measure')) {
                $table->dropColumn('content_unit_measure');
            }

            if (Schema::hasColumn('catalog_items', 'content_quantity')) {
                $table->dropColumn('content_quantity');
            }
        });
    }
};