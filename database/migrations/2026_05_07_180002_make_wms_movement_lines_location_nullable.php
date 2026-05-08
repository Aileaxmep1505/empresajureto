<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wms_movement_lines') && Schema::hasColumn('wms_movement_lines', 'location_id')) {
            Schema::table('wms_movement_lines', function (Blueprint $table) {
                $table->unsignedBigInteger('location_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('wms_movement_lines') && Schema::hasColumn('wms_movement_lines', 'location_id')) {
            Schema::table('wms_movement_lines', function (Blueprint $table) {
                $table->unsignedBigInteger('location_id')->nullable(false)->change();
            });
        }
    }
};