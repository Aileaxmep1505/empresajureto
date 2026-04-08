<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            if (!Schema::hasColumn('agenda_events', 'end_at')) {
                $table->dateTime('end_at')->nullable()->after('start_at');
            }

            if (!Schema::hasColumn('agenda_events', 'all_day')) {
                $table->boolean('all_day')->default(false)->after('timezone');
            }

            if (!Schema::hasColumn('agenda_events', 'completed')) {
                $table->boolean('completed')->default(false)->after('all_day');
            }

            if (!Schema::hasColumn('agenda_events', 'color')) {
                $table->string('color', 30)->default('indigo')->after('completed');
            }

            if (!Schema::hasColumn('agenda_events', 'category')) {
                $table->string('category', 50)->default('general')->after('color');
            }

            if (!Schema::hasColumn('agenda_events', 'priority')) {
                $table->string('priority', 20)->default('media')->after('category');
            }

            if (!Schema::hasColumn('agenda_events', 'location')) {
                $table->string('location', 255)->nullable()->after('priority');
            }

            if (!Schema::hasColumn('agenda_events', 'notes')) {
                $table->text('notes')->nullable()->after('location');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            $cols = [
                'end_at',
                'all_day',
                'completed',
                'color',
                'category',
                'priority',
                'location',
                'notes',
            ];

            foreach ($cols as $col) {
                if (Schema::hasColumn('agenda_events', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};