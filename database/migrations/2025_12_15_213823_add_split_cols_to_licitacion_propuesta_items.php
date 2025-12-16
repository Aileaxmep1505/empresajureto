<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licitacion_propuesta_items', function (Blueprint $table) {
            if (!Schema::hasColumn('licitacion_propuesta_items', 'split_index')) {
                $table->unsignedInteger('split_index')->nullable()->index()->after('licitacion_propuesta_id');
            }
            if (!Schema::hasColumn('licitacion_propuesta_items', 'split_order')) {
                $table->unsignedInteger('split_order')->nullable()->index()->after('split_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('licitacion_propuesta_items', function (Blueprint $table) {
            if (Schema::hasColumn('licitacion_propuesta_items', 'split_order')) {
                $table->dropIndex(['split_order']);
                $table->dropColumn('split_order');
            }
            if (Schema::hasColumn('licitacion_propuesta_items', 'split_index')) {
                $table->dropIndex(['split_index']);
                $table->dropColumn('split_index');
            }
        });
    }
};
