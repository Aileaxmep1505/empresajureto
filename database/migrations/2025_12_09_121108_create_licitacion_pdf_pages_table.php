<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licitacion_pdf_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('licitacion_pdf_id');

            $table->unsignedInteger('page_number'); // empieza en 1
            $table->longText('raw_text')->nullable();
            $table->unsignedInteger('tokens_count')->nullable();

            // pending, sent_to_ai, done, error
            $table->string('status', 50)->default('pending');
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index('licitacion_pdf_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licitacion_pdf_pages');
    }
};
