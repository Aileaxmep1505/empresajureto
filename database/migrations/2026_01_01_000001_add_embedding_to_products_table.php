<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'embedding')) {
            Schema::table('products', function (Blueprint $table) {
                $table->json('embedding')->nullable()->after('description');
            });
        }

        if (!Schema::hasColumn('products', 'embedding_updated_at')) {
            Schema::table('products', function (Blueprint $table) {
                $table->timestamp('embedding_updated_at')->nullable()->after('embedding');
            });
        }

        if (
            Schema::hasColumn('products', 'embedding_updated_at') &&
            !$this->indexExists('products', 'products_embedding_updated_at_index')
        ) {
            Schema::table('products', function (Blueprint $table) {
                $table->index('embedding_updated_at', 'products_embedding_updated_at_index');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('products', 'products_embedding_updated_at_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('products_embedding_updated_at_index');
            });
        }

        if (Schema::hasColumn('products', 'embedding_updated_at')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('embedding_updated_at');
            });
        }

        if (Schema::hasColumn('products', 'embedding')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('embedding');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();

        $result = DB::select(
            '
            SELECT COUNT(1) AS total
            FROM information_schema.statistics
            WHERE table_schema = ?
              AND table_name = ?
              AND index_name = ?
            ',
            [$database, $table, $index]
        );

        return ((int) ($result[0]->total ?? 0)) > 0;
    }
};