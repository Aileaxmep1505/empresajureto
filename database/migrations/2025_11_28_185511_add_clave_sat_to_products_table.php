<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'clave_sat')) {
                $table->string('clave_sat', 30)
                      ->nullable()
                      ->after('material'); // la deja junto a material
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'clave_sat')) {
                $table->dropColumn('clave_sat');
            }
        });
    }
};
