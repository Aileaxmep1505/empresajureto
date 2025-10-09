<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('ventas') && !Schema::hasColumn('ventas', 'utilidad_global')) {
            Schema::table('ventas', function (Blueprint $table) {
                // Porcentaje (ej. 25.00). Ajusta precisión si lo prefieres.
                $table->decimal('utilidad_global', 8, 2)->default(0)->after('moneda');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ventas') && Schema::hasColumn('ventas', 'utilidad_global')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropColumn('utilidad_global');
            });
        }
    }
};
