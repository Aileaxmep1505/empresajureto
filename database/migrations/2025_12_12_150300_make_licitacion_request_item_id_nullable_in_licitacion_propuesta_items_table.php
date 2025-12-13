<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('licitacion_propuesta_items', function (Blueprint $table) {
            // volver nullable
            if (Schema::hasColumn('licitacion_propuesta_items', 'licitacion_request_item_id')) {
                $table->unsignedBigInteger('licitacion_request_item_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('licitacion_propuesta_items', function (Blueprint $table) {
            if (Schema::hasColumn('licitacion_propuesta_items', 'licitacion_request_item_id')) {
                $table->unsignedBigInteger('licitacion_request_item_id')->nullable(false)->change();
            }
        });
    }
};
