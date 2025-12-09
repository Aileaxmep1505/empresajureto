<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licitacion_request_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('licitacion_id')->nullable();
            $table->unsignedBigInteger('requisicion_id')->nullable();
            $table->unsignedBigInteger('licitacion_pdf_page_id')->nullable();

            $table->text('line_raw');        // texto tal cual del PDF
            $table->text('descripcion')->nullable(); // opcional, si lo partes
            $table->decimal('cantidad', 15, 2)->nullable();
            $table->string('unidad', 50)->nullable();
            $table->unsignedInteger('renglon')->nullable();

            // pending_match, matched, discarded
            $table->string('status', 50)->default('pending_match');

            $table->timestamps();

            $table->index('licitacion_id');
            $table->index('requisicion_id');
            $table->index('licitacion_pdf_page_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licitacion_request_items');
    }
};
