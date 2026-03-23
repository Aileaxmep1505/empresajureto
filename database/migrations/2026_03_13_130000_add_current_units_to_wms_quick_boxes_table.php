<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_quick_boxes', function (Blueprint $table) {
            if (!Schema::hasColumn('wms_quick_boxes', 'current_units')) {
                $table->unsignedInteger('current_units')->default(0)->after('units_per_box');
            }
        });

        DB::table('wms_quick_boxes')
            ->where('current_units', 0)
            ->update([
                'current_units' => DB::raw('units_per_box'),
            ]);
    }

    public function down(): void
    {
        Schema::table('wms_quick_boxes', function (Blueprint $table) {
            if (Schema::hasColumn('wms_quick_boxes', 'current_units')) {
                $table->dropColumn('current_units');
            }
        });
    }
};