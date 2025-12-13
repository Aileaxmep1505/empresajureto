<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('licitacion_propuesta_items', function (Blueprint $table) {
            if (!Schema::hasColumn('licitacion_propuesta_items', 'descripcion_raw')) {
                $table->longText('descripcion_raw')->nullable()->after('product_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('licitacion_propuesta_items', function (Blueprint $table) {
            if (Schema::hasColumn('licitacion_propuesta_items', 'descripcion_raw')) {
                $table->dropColumn('descripcion_raw');
            }
        });
    }
};
