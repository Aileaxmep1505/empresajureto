<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Almacena el vector de embedding como JSON (array de floats)
            $table->json('embedding')->nullable()->after('description');
            $table->timestamp('embedding_updated_at')->nullable()->after('embedding');
            $table->index('embedding_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['embedding', 'embedding_updated_at']);
        });
    }
};