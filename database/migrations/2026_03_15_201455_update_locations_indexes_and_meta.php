<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {

            if (!Schema::hasColumn('locations', 'meta')) {
                $table->json('meta')->nullable()->after('qr_secret');
            }

            $table->index('warehouse_id', 'locations_warehouse_id_idx');
            $table->index('parent_id', 'locations_parent_id_idx');
            $table->index('type', 'locations_type_idx');
            $table->index('code', 'locations_code_idx');

            $table->unique(['warehouse_id', 'code'], 'locations_warehouse_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {

            $table->dropUnique('locations_warehouse_code_unique');
            $table->dropIndex('locations_warehouse_id_idx');
            $table->dropIndex('locations_parent_id_idx');
            $table->dropIndex('locations_type_idx');
            $table->dropIndex('locations_code_idx');

        });
    }
};