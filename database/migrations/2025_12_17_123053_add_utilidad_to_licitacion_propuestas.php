<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('licitacion_propuestas', function (Blueprint $table) {
            if (!Schema::hasColumn('licitacion_propuestas', 'utilidad_global_pct')) {
                $table->decimal('utilidad_global_pct', 5, 2)->nullable()->after('total');
            }
            if (!Schema::hasColumn('licitacion_propuestas', 'utilidad_total')) {
                $table->decimal('utilidad_total', 14, 2)->default(0)->after('utilidad_global_pct');
            }
        });
    }

    public function down(): void
    {
        Schema::table('licitacion_propuestas', function (Blueprint $table) {
            if (Schema::hasColumn('licitacion_propuestas', 'utilidad_global_pct')) {
                $table->dropColumn('utilidad_global_pct');
            }
            if (Schema::hasColumn('licitacion_propuestas', 'utilidad_total')) {
                $table->dropColumn('utilidad_total');
            }
        });
    }
};
