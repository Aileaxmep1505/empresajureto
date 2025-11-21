<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('catalog_ai_intake_files', function (Blueprint $t) {
      $t->id();

      $t->foreignId('intake_id')
        ->constrained('catalog_ai_intakes')
        ->cascadeOnDelete();

      $t->string('disk', 30)->default('public');
      $t->string('path');                   // storage path ej intakes/1/img.jpg
      $t->string('original_name')->nullable();
      $t->string('mime', 80)->nullable();
      $t->unsignedBigInteger('size')->default(0);
      $t->unsignedInteger('page_no')->default(1);

      $t->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('catalog_ai_intake_files');
  }
};
