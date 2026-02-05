<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('expenses', function (Blueprint $table) {
      $table->id();

      $table->foreignId('expense_category_id')->nullable()->constrained()->nullOnDelete();

      // Si es gasto vehicular, se liga a vehicle
      $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();

      // Si es nómina, se liga a period y/o user
      $table->foreignId('payroll_period_id')->nullable()->constrained()->nullOnDelete();

      // Registrado por
      $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

      // A quién se pagó (proveedor/empleado/etc.)
      $table->string('vendor')->nullable();

      $table->date('expense_date');               // fecha del gasto
      $table->string('concept');                  // “Gasolina Pemex”, “Renta oficina”, “Compra impresora”
      $table->text('description')->nullable();

      $table->decimal('amount', 12, 2);           // monto
      $table->string('currency', 3)->default('MXN');

      // efectivo|transferencia|tarjeta|otro
      $table->string('payment_method', 30)->nullable();

      // pagado|pendiente|reembolsable|reembolsado|cancelado
      $table->string('status', 30)->default('pagado');

      // para filtros y reportes
      $table->string('tags')->nullable();         // “gasolina, ruta norte, emergencia”
      $table->timestamps();

      $table->index(['expense_date']);
      $table->index(['vehicle_id', 'expense_date']);
      $table->index(['payroll_period_id', 'expense_date']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('expenses');
  }
};
