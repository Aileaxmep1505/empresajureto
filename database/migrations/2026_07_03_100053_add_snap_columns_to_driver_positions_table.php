<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_positions', function (Blueprint $table) {
            if (!Schema::hasColumn('driver_positions', 'snap_lat')) {
                $table->decimal('snap_lat', 10, 7)->nullable()->after('lng');
            }

            if (!Schema::hasColumn('driver_positions', 'snap_lng')) {
                $table->decimal('snap_lng', 10, 7)->nullable()->after('snap_lat');
            }

            if (!Schema::hasColumn('driver_positions', 'snap_place_id')) {
                $table->string('snap_place_id')->nullable()->after('snap_lng');
            }
        });
    }

    public function down(): void
    {
        Schema::table('driver_positions', function (Blueprint $table) {
            if (Schema::hasColumn('driver_positions', 'snap_place_id')) {
                $table->dropColumn('snap_place_id');
            }

            if (Schema::hasColumn('driver_positions', 'snap_lng')) {
                $table->dropColumn('snap_lng');
            }

            if (Schema::hasColumn('driver_positions', 'snap_lat')) {
                $table->dropColumn('snap_lat');
            }
        });
    }
};