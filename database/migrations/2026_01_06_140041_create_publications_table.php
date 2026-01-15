<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('publications', function (Blueprint $table) {
            $table->id();

            $table->string('title', 200);
            $table->text('description')->nullable();

            $table->string('file_path');            // storage path
            $table->string('original_name');        // nombre original
            $table->string('mime_type', 180)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('extension', 30)->nullable();

            $table->string('kind', 30)->default('file'); // image|video|pdf|doc|sheet|file
            $table->boolean('pinned')->default(false);

            $table->unsignedBigInteger('created_by')->nullable(); // opcional

            $table->timestamps();

            $table->index(['pinned', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};
