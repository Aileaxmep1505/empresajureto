<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('driver_positions', function (Blueprint $table) {
            if (!Schema::hasColumn('driver_positions', 'received_at')) {
                $table->timestamp('received_at')->nullable()->index()->after('captured_at');
            }

            if (!Schema::hasColumn('driver_positions', 'app_state')) {
                $table->string('app_state', 30)->nullable()->after('received_at'); // foreground|background|killed
            }

            if (!Schema::hasColumn('driver_positions', 'battery')) {
                $table->unsignedTinyInteger('battery')->nullable()->after('app_state'); // 0-100
            }

            if (!Schema::hasColumn('driver_positions', 'network')) {
                $table->string('network', 30)->nullable()->after('battery'); // wifi|cell|none
            }

            if (!Schema::hasColumn('driver_positions', 'is_mocked')) {
                $table->boolean('is_mocked')->nullable()->after('network'); // GPS falso
            }

            // snap to road (opcional pero recomendado)
            if (!Schema::hasColumn('driver_positions', 'snap_lat')) {
                $table->decimal('snap_lat', 10, 7)->nullable()->after('is_mocked');
            }
            if (!Schema::hasColumn('driver_positions', 'snap_lng')) {
                $table->decimal('snap_lng', 10, 7)->nullable()->after('snap_lat');
            }
            if (!Schema::hasColumn('driver_positions', 'snap_distance_m')) {
                $table->unsignedInteger('snap_distance_m')->nullable()->after('snap_lng');
            }
        });
    }

    public function down(): void
    {
        Schema::table('driver_positions', function (Blueprint $table) {
            foreach ([
                'received_at','app_state','battery','network','is_mocked',
                'snap_lat','snap_lng','snap_distance_m',
            ] as $col) {
                if (Schema::hasColumn('driver_positions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};