<?php
// database/migrations/2025_09_28_000000_create_orders_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('orders', function(Blueprint $t){
      $t->id();
      $t->string('customer_name');
      $t->string('customer_email');
      $t->string('customer_phone')->nullable();
      $t->string('customer_address')->nullable();
      $t->string('currency', 8)->default('MXN');
      $t->decimal('subtotal',12,2)->default(0);
      $t->decimal('shipping',12,2)->default(0);
      $t->decimal('tax',12,2)->default(0);
      $t->decimal('total',12,2)->default(0);
      $t->string('status', 32)->default('pending');
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('orders'); }
};
