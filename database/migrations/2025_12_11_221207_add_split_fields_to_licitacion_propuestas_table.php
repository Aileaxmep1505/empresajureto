<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licitacion_propuestas', function (Blueprint $table) {
            // 👇 Solo creamos la columna si NO existe ya
            if (!Schema::hasColumn('licitacion_propuestas', 'licitacion_pdf_id')) {
                $table->unsignedBigInteger('licitacion_pdf_id')
                    ->nullable()
                    ->after('requisicion_id');
            }

            if (!Schema::hasColumn('licitacion_propuestas', 'start_split_index')) {
                $table->unsignedInteger('start_split_index')
                    ->nullable()
                    ->after('licitacion_pdf_id');
            }

            if (!Schema::hasColumn('licitacion_propuestas', 'current_split_index')) {
                $table->unsignedInteger('current_split_index')
                    ->nullable()
                    ->after('start_split_index');
            }

            if (!Schema::hasColumn('licitacion_propuestas', 'processed_split_indexes')) {
                $table->json('processed_split_indexes')
                    ->nullable()
                    ->after('current_split_index');
            }

            if (!Schema::hasColumn('licitacion_propuestas', 'merged_at')) {
                $table->timestamp('merged_at')
                    ->nullable()
                    ->after('total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('licitacion_propuestas', function (Blueprint $table) {
            // Ojo: solo dropeamos columnas que sí existan, por si acaso
            if (Schema::hasColumn('licitacion_propuestas', 'merged_at')) {
                $table->dropColumn('merged_at');
            }
            if (Schema::hasColumn('licitacion_propuestas', 'processed_split_indexes')) {
                $table->dropColumn('processed_split_indexes');
            }
            if (Schema::hasColumn('licitacion_propuestas', 'current_split_index')) {
                $table->dropColumn('current_split_index');
            }
            if (Schema::hasColumn('licitacion_propuestas', 'start_split_index')) {
                $table->dropColumn('start_split_index');
            }

            // Solo la quitamos si esta migration fue quien la creó,
            // pero como ya la tenías antes, lo normal es NO tocarla.
            // if (Schema::hasColumn('licitacion_propuestas', 'licitacion_pdf_id')) {
            //     $table->dropColumn('licitacion_pdf_id');
            // }
        });
    }
};
