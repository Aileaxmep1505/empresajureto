<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('licitacion_propuesta_items', function (Blueprint $table) {

            // 1) nullable para permitir items creados solo por IA (sin requestItem)
            if (Schema::hasColumn('licitacion_propuesta_items', 'licitacion_request_item_id')) {
                try {
                    $table->unsignedBigInteger('licitacion_request_item_id')->nullable()->change();
                } catch (\Throwable $e) {
                    // Si falla, instala doctrine/dbal:
                    // composer require doctrine/dbal
                }
            }

            // 2) Producto sugerido (sin aplicar todavía)
            if (!Schema::hasColumn('licitacion_propuesta_items', 'suggested_product_id')) {
                $table->unsignedBigInteger('suggested_product_id')->nullable()->after('product_id');
                $table->index('suggested_product_id');
            }

            // 3) Estado del matching: suggested | applied | rejected | null
            if (!Schema::hasColumn('licitacion_propuesta_items', 'match_status')) {
                $table->string('match_status', 30)->nullable()->after('match_score');
                $table->index('match_status');
            }

            // 4) Razón del match (texto corto)
            if (!Schema::hasColumn('licitacion_propuesta_items', 'match_reason')) {
                $table->text('match_reason')->nullable()->after('match_status');
            }

            // 5) Si ya lo decidió el usuario (aplicó o rechazó)
            if (!Schema::hasColumn('licitacion_propuesta_items', 'manual_selected')) {
                $table->boolean('manual_selected')->default(false)->after('match_reason');
                $table->index('manual_selected');
            }
        });
    }

    public function down(): void
    {
        Schema::table('licitacion_propuesta_items', function (Blueprint $table) {

            if (Schema::hasColumn('licitacion_propuesta_items', 'manual_selected')) {
                $table->dropIndex(['manual_selected']);
                $table->dropColumn('manual_selected');
            }

            if (Schema::hasColumn('licitacion_propuesta_items', 'match_reason')) {
                $table->dropColumn('match_reason');
            }

            if (Schema::hasColumn('licitacion_propuesta_items', 'match_status')) {
                $table->dropIndex(['match_status']);
                $table->dropColumn('match_status');
            }

            if (Schema::hasColumn('licitacion_propuesta_items', 'suggested_product_id')) {
                $table->dropIndex(['suggested_product_id']);
                $table->dropColumn('suggested_product_id');
            }

            // No regresamos licitacion_request_item_id a NOT NULL para no romper datos
        });
    }
};
