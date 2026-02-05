<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('payroll_periods', function (Blueprint $table) {
      $table->id();

      // quincenal|mensual
      $table->string('frequency', 20)->default('quincenal');

      $table->date('start_date');
      $table->date('end_date');

      // abierto|cerrado|pagado
      $table->string('status', 20)->default('abierto');

      $table->string('title')->nullable(); // “Quincena 1 Feb 2026”
      $table->timestamps();

      $table->index(['start_date', 'end_date']);
      $table->unique(['frequency','start_date','end_date']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('payroll_periods');
  }
};
