<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        // ✅ Si la tabla no existe, no rompas migraciones
        if (!Schema::hasTable('wms_movements')) {
            return;
        }

        Schema::table('wms_movements', function (Blueprint $table) {

            if (!Schema::hasColumn('wms_movements', 'authorized_name')) {
                $table->string('authorized_name')->nullable()->after('note');
            }

            if (!Schema::hasColumn('wms_movements', 'authorized_role')) {
                $table->string('authorized_role')->nullable()->after('authorized_name');
            }

            if (!Schema::hasColumn('wms_movements', 'delivered_name')) {
                $table->string('delivered_name')->nullable()->after('authorized_role');
            }

            if (!Schema::hasColumn('wms_movements', 'received_name')) {
                $table->string('received_name')->nullable()->after('delivered_name');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('wms_movements')) {
            return;
        }

        Schema::table('wms_movements', function (Blueprint $table) {

            // bajar seguro: solo dropea las que existan
            $cols = ['authorized_name','authorized_role','delivered_name','received_name'];

            foreach ($cols as $col) {
                if (Schema::hasColumn('wms_movements', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
