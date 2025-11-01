<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    public function up(): void
    {
        // 1) Crear tabla categories si no existe
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 190);
                $table->string('slug', 190)->unique();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->boolean('is_primary')->default(false);
                $table->unsignedInteger('position')->default(0);
                $table->string('image_url')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['is_primary', 'position']);
                $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();
            });
        }

        // 2) Asegurar category_id en catalog_items + índice + FK (todo idempotente)
        if (Schema::hasTable('catalog_items')) {
            Schema::table('catalog_items', function (Blueprint $table) {
                if (!Schema::hasColumn('catalog_items', 'category_id')) {
                    $table->unsignedBigInteger('category_id')->nullable()->after('brand_id');
                }
            });

            // Índice (evita duplicarlo)
            if (Schema::hasColumn('catalog_items', 'category_id') &&
                !$this->indexExists('catalog_items', 'catalog_items_category_id_index')) {

                Schema::table('catalog_items', function (Blueprint $table) {
                    $table->index('category_id', 'catalog_items_category_id_index');
                });
            }

            // FK opcional (envuelta en try por si ya existe o hay datos huérfanos)
            if (Schema::hasColumn('catalog_items', 'category_id')) {
                try {
                    Schema::table('catalog_items', function (Blueprint $table) {
                        $table->foreign('category_id')
                              ->references('id')->on('categories')
                              ->nullOnDelete();
                    });
                } catch (\Throwable $e) {
                    // Ignorar si ya existe o si falla por datos actuales
                }
            }
        }
    }

    public function down(): void
    {
        // Quitar FK e índice de catalog_items si existen
        if (Schema::hasTable('catalog_items') && Schema::hasColumn('catalog_items', 'category_id')) {
            try {
                Schema::table('catalog_items', function (Blueprint $table) {
                    $table->dropForeign(['category_id']);
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('catalog_items', function (Blueprint $table) {
                    $table->dropIndex('catalog_items_category_id_index');
                });
            } catch (\Throwable $e) {}
            // Nota: no removemos la columna category_id en down() para no romper datos existentes.
        }

        Schema::dropIfExists('categories');
    }
};
