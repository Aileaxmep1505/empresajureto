<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'embedding')) {
                $table->json('embedding')->nullable()->after('description');
            }

            if (!Schema::hasColumn('products', 'embedding_updated_at')) {
                $table->timestamp('embedding_updated_at')->nullable()->after('embedding');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            /*
             | Evita crear índice duplicado si la columna ya existía
             | pero el índice no.
             */
            if (Schema::hasColumn('products', 'embedding_updated_at')) {
                try {
                    $table->index('embedding_updated_at', 'products_embedding_updated_at_index');
                } catch (\Throwable $e) {
                    //
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            try {
                $table->dropIndex('products_embedding_updated_at_index');
            } catch (\Throwable $e) {
                //
            }

            if (Schema::hasColumn('products', 'embedding_updated_at')) {
                $table->dropColumn('embedding_updated_at');
            }

            if (Schema::hasColumn('products', 'embedding')) {
                $table->dropColumn('embedding');
            }
        });
    }
};