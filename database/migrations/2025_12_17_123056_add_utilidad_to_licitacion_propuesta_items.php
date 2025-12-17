<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('licitacion_propuesta_items', function (Blueprint $table) {
            if (!Schema::hasColumn('licitacion_propuesta_items', 'utilidad_pct')) {
                $table->decimal('utilidad_pct', 5, 2)->nullable()->after('precio_unitario');
            }
            if (!Schema::hasColumn('licitacion_propuesta_items', 'utilidad_total')) {
                $table->decimal('utilidad_total', 14, 2)->default(0)->after('utilidad_pct');
            }
        });
    }

    public function down(): void
    {
        Schema::table('licitacion_propuesta_items', function (Blueprint $table) {
            if (Schema::hasColumn('licitacion_propuesta_items', 'utilidad_pct')) {
                $table->dropColumn('utilidad_pct');
            }
            if (Schema::hasColumn('licitacion_propuesta_items', 'utilidad_total')) {
                $table->dropColumn('utilidad_total');
            }
        });
    }
};
