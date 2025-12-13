<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('licitacion_propuesta_items', function (Blueprint $table) {
            if (!Schema::hasColumn('licitacion_propuesta_items', 'suggested_products')) {
                $table->json('suggested_products')->nullable()->after('descripcion_raw');
            }
            if (!Schema::hasColumn('licitacion_propuesta_items', 'match_status')) {
                $table->string('match_status', 30)->nullable()->after('match_score');
            }
            if (!Schema::hasColumn('licitacion_propuesta_items', 'match_reason')) {
                $table->text('match_reason')->nullable()->after('motivo_seleccion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('licitacion_propuesta_items', function (Blueprint $table) {
            if (Schema::hasColumn('licitacion_propuesta_items', 'suggested_products')) $table->dropColumn('suggested_products');
            if (Schema::hasColumn('licitacion_propuesta_items', 'match_status')) $table->dropColumn('match_status');
            if (Schema::hasColumn('licitacion_propuesta_items', 'match_reason')) $table->dropColumn('match_reason');
        });
    }
};
