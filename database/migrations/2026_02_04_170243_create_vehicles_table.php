<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('vehicles', function (Blueprint $table) {
      $table->id();

      $table->string('plate')->unique();          // Placa
      $table->string('brand')->nullable();        // Marca (opcional)
      $table->string('model');                    // Modelo
      $table->unsignedSmallInteger('year');       // Año
      $table->string('vin')->nullable();          // NIV/VIN opcional
      $table->string('nickname')->nullable();     // Alias “Camioneta 1” opcional

      // Fechas “últimas” (se pueden actualizar desde eventos)
      $table->date('last_verification_at')->nullable();
      $table->date('last_service_at')->nullable();

      // Próximos vencimientos / recordatorios
      $table->date('next_verification_due_at')->nullable();
      $table->date('next_service_due_at')->nullable();
      $table->date('tenencia_due_at')->nullable();
      $table->date('circulation_card_due_at')->nullable();

      $table->text('notes')->nullable();

      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists('vehicles');
  }
};
