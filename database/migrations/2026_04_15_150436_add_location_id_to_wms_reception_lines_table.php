<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wms_reception_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('wms_reception_lines', 'location_id')) {
                $table->unsignedBigInteger('location_id')->nullable()->after('catalog_item_id');

                $table->foreign('location_id')
                    ->references('id')
                    ->on('locations')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('wms_reception_lines', function (Blueprint $table) {
            if (Schema::hasColumn('wms_reception_lines', 'location_id')) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            }
        });
    }
};