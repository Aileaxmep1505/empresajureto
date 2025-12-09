<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licitacion_pdfs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('licitacion_id')->nullable();
            $table->unsignedBigInteger('requisicion_id')->nullable();

            $table->string('original_filename');
            $table->string('original_path');
            $table->unsignedInteger('pages_count')->default(0);

            // uploaded, parsed, items_extracted, proposal_ready
            $table->string('status', 50)->default('uploaded');
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index('licitacion_id');
            $table->index('requisicion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licitacion_pdfs');
    }
};
