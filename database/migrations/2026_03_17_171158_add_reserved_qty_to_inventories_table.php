<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_quick_boxes', function (Blueprint $table) {
            if (!Schema::hasColumn('wms_quick_boxes', 'reserved_units')) {
                $table->unsignedInteger('reserved_units')->default(0)->after('current_units');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wms_quick_boxes', function (Blueprint $table) {
            if (Schema::hasColumn('wms_quick_boxes', 'reserved_units')) {
                $table->dropColumn('reserved_units');
            }
        });
    }
};