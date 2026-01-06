<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('route_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('route_plans','sequence_locked')) {
                $table->boolean('sequence_locked')->default(false)->after('status');
            }
            if (!Schema::hasColumn('route_plans','start_lat')) {
                $table->decimal('start_lat', 10, 7)->nullable()->after('sequence_locked');
            }
            if (!Schema::hasColumn('route_plans','start_lng')) {
                $table->decimal('start_lng', 10, 7)->nullable()->after('start_lat');
            }
            if (!Schema::hasColumn('route_plans','started_at')) {
                $table->timestamp('started_at')->nullable()->after('start_lng');
            }
        });

        Schema::table('route_stops', function (Blueprint $table) {
            if (!Schema::hasColumn('route_stops','done_at')) {
                $table->timestamp('done_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('route_stops','eta_seconds')) {
                $table->integer('eta_seconds')->nullable()->after('sequence_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('route_plans', function (Blueprint $table) {
            if (Schema::hasColumn('route_plans','sequence_locked')) $table->dropColumn('sequence_locked');
            if (Schema::hasColumn('route_plans','start_lat')) $table->dropColumn('start_lat');
            if (Schema::hasColumn('route_plans','start_lng')) $table->dropColumn('start_lng');
            if (Schema::hasColumn('route_plans','started_at')) $table->dropColumn('started_at');
        });

        Schema::table('route_stops', function (Blueprint $table) {
            if (Schema::hasColumn('route_stops','done_at')) $table->dropColumn('done_at');
            if (Schema::hasColumn('route_stops','eta_seconds')) $table->dropColumn('eta_seconds');
        });
    }
};
