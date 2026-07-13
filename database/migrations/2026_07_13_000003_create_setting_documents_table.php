<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('setting_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('section', 60);
            $table->string('document_key', 100);
            $table->string('type', 100)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->date('expires_at')->nullable();
            $table->enum('validation_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
            $table->unique(['user_id', 'section', 'document_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setting_documents');
    }
};
