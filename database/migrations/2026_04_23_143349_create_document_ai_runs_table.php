<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_ai_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('licitacion_pdf_id')->index();
            $table->string('python_job_id')->unique();
            $table->string('filename')->nullable();
            $table->unsignedInteger('pages_per_chunk')->default(5);
            $table->string('status')->default('queued'); // queued|processing|completed|failed
            $table->text('error')->nullable();
            $table->json('result_json')->nullable();
            $table->json('structured_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_ai_runs');
    }
};