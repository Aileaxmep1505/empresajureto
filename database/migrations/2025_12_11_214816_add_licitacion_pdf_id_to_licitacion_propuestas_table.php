<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licitacion_propuestas', function (Blueprint $table) {
            // nullable para no romper registros existentes
            $table->unsignedBigInteger('licitacion_pdf_id')
                ->nullable()
                ->after('requisicion_id');

            $table->foreign('licitacion_pdf_id')
                ->references('id')
                ->on('licitacion_pdfs')
                ->nullOnDelete(); // si borras el PDF, se pone null
        });
    }

    public function down(): void
    {
        Schema::table('licitacion_propuestas', function (Blueprint $table) {
            $table->dropForeign(['licitacion_pdf_id']);
            $table->dropColumn('licitacion_pdf_id');
        });
    }
};
