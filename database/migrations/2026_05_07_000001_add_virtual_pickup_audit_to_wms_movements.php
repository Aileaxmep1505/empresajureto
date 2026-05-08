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
                if (!Schema::hasColumn('wms_movements', 'reference')) {
                    $table->string('reference')->nullable()->after('note');
                }

                if (!Schema::hasColumn('wms_movements', 'meta')) {
                    $table->json('meta')->nullable()->after('reference');
                }
            });
        }

        if (Schema::hasTable('wms_movement_lines')) {
            Schema::table('wms_movement_lines', function (Blueprint $table) {
                if (!Schema::hasColumn('wms_movement_lines', 'line_uid')) {
                    $table->string('line_uid')->nullable()->after('id');
                }

                if (!Schema::hasColumn('wms_movement_lines', 'source_type')) {
                    $table->string('source_type')->nullable()->after('qty');
                }

                if (!Schema::hasColumn('wms_movement_lines', 'meta')) {
                    $table->json('meta')->nullable()->after('inv_after');
                }
            });
        }

        /**
         * Si tu location_id NO permite null y te truena al guardar movimientos virtuales,
         * activa esto. Si ya permite null, déjalo comentado.
         */
        /*
        if (Schema::hasTable('wms_movement_lines') && Schema::hasColumn('wms_movement_lines', 'location_id')) {
            Schema::table('wms_movement_lines', function (Blueprint $table) {
                $table->unsignedBigInteger('location_id')->nullable()->change();
            });
        }
        */
    }

    public function down(): void
    {
        if (Schema::hasTable('wms_movement_lines')) {
            Schema::table('wms_movement_lines', function (Blueprint $table) {
                if (Schema::hasColumn('wms_movement_lines', 'line_uid')) {
                    $table->dropColumn('line_uid');
                }

                if (Schema::hasColumn('wms_movement_lines', 'source_type')) {
                    $table->dropColumn('source_type');
                }

                if (Schema::hasColumn('wms_movement_lines', 'meta')) {
                    $table->dropColumn('meta');
                }
            });
        }

        if (Schema::hasTable('wms_movements')) {
            Schema::table('wms_movements', function (Blueprint $table) {
                if (Schema::hasColumn('wms_movements', 'reference')) {
                    $table->dropColumn('reference');
                }

                if (Schema::hasColumn('wms_movements', 'meta')) {
                    $table->dropColumn('meta');
                }
            });
        }
    }
};