<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->foreignId('primary_location_id')
                ->nullable()
                ->after('id')
                ->constrained('locations')
                ->nullOnDelete();

            $table->index('primary_location_id');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_location_id');
        });
    }
};
