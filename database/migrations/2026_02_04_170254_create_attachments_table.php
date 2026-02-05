<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('attachments', function (Blueprint $table) {
      $table->id();

      // Polimórfico: puede ser Expense, PayrollEntry, VehicleEvent, etc.
      $table->morphs('attachable');

      $table->string('disk')->default('public'); // public|s3 etc
      $table->string('path');                    // ruta en storage
      $table->string('original_name')->nullable();
      $table->string('mime_type')->nullable();
      $table->unsignedBigInteger('size_bytes')->nullable();

      $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
      $table->timestamps();

      $table->index(['mime_type']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('attachments');
  }
};
