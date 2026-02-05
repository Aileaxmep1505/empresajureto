<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('payroll_entries', function (Blueprint $table) {
      $table->id();

      $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();

      // Sueldo base, bonos, descuentos, etc.
      $table->decimal('gross_amount', 12, 2)->default(0);
      $table->decimal('deductions', 12, 2)->default(0);
      $table->decimal('net_amount', 12, 2)->default(0);

      // pagado|pendiente
      $table->string('status', 20)->default('pendiente');

      $table->string('notes')->nullable();
      $table->timestamps();

      $table->unique(['payroll_period_id','user_id']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('payroll_entries');
  }
};
