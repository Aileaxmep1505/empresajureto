<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            // Columna para mapear categorías desde config/catalog.php
            $table->string('category_key', 120)->nullable()->index()->after('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropIndex(['category_key']);
            $table->dropColumn('category_key');
        });
    }
};
