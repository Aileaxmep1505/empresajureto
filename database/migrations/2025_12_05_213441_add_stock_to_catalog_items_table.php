<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            // Stock simple a nivel producto (sin almacenes, global)
            $table->integer('stock')
                ->default(0)
                ->after('sale_price'); // lo puedes mover de lugar si quieres
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropColumn('stock');
        });
    }
};
