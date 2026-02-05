<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('expense_categories', function (Blueprint $table) {
      $table->id();
      $table->string('name')->unique();     // Gasolina, Renta, Internet, Limpieza, Viáticos, etc.
      $table->string('slug')->unique();
      $table->string('type')->default('operativo'); // operativo|vehicular|nomina|otro (para filtrar)
      $table->boolean('active')->default(true);
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists('expense_categories');
  }
};
