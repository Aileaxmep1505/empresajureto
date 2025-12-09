<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licitacion_pdfs', function (Blueprint $table) {
            if (!Schema::hasColumn('licitacion_pdfs', 'pages_count')) {
                $table->unsignedInteger('pages_count')
                    ->default(0)
                    ->after('original_path');
            }

            if (!Schema::hasColumn('licitacion_pdfs', 'meta')) {
                $table->json('meta')
                    ->nullable()
                    ->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('licitacion_pdfs', function (Blueprint $table) {
            if (Schema::hasColumn('licitacion_pdfs', 'pages_count')) {
                $table->dropColumn('pages_count');
            }
            if (Schema::hasColumn('licitacion_pdfs', 'meta')) {
                $table->dropColumn('meta');
            }
        });
    }
};
