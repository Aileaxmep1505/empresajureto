<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('vehicle_documents', function (Blueprint $table) {
      $table->id();
      $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();

      // tarjeta_circulacion|seguro|tenencia|verificacion|factura|otro
      $table->string('type', 40);

      $table->string('original_name')->nullable();
      $table->string('mime_type', 120)->nullable();
      $table->unsignedBigInteger('size')->nullable();
      $table->string('path'); // storage path (public)

      $table->timestamps();

      $table->index(['vehicle_id','type']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('vehicle_documents');
  }
};
