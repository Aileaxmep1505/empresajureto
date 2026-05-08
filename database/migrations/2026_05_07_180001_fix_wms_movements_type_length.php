<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wms_movements')) {
            Schema::table('wms_movements', function (Blueprint $table) {
                if (Schema::hasColumn('wms_movements', 'type')) {
                    $table->string('type', 80)->change();
                }

                if (!Schema::hasColumn('wms_movements', 'reference')) {
                    $table->string('reference', 160)->nullable()->after('note');
                }

                if (!Schema::hasColumn('wms_movements', 'meta')) {
                    $table->json('meta')->nullable()->after('reference');
                }
            });
        }

        if (Schema::hasTable('wms_movement_lines')) {
            Schema::table('wms_movement_lines', function (Blueprint $table) {
                if (!Schema::hasColumn('wms_movement_lines', 'line_uid')) {
                    $table->string('line_uid', 160)->nullable()->after('id');
                }

                if (!Schema::hasColumn('wms_movement_lines', 'source_type')) {
                    $table->string('source_type', 40)->nullable()->after('qty');
                }

                if (!Schema::hasColumn('wms_movement_lines', 'meta')) {
                    $table->json('meta')->nullable()->after('inv_after');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('wms_movements') && Schema::hasColumn('wms_movements', 'type')) {
            Schema::table('wms_movements', function (Blueprint $table) {
                $table->string('type', 30)->change();
            });
        }
    }
};