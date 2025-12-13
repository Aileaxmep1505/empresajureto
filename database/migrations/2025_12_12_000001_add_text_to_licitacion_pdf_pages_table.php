<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('licitacion_pdf_pages')) {
            // Si la tabla no existe, no hacemos nada aquí (porque tú ya la tienes).
            return;
        }

        Schema::table('licitacion_pdf_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('licitacion_pdf_pages', 'text')) {
                $table->longText('text')->nullable()->after('page_number');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('licitacion_pdf_pages')) return;

        Schema::table('licitacion_pdf_pages', function (Blueprint $table) {
            if (Schema::hasColumn('licitacion_pdf_pages', 'text')) {
                $table->dropColumn('text');
            }
        });
    }
};
