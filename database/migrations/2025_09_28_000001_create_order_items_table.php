<?php
// database/migrations/2025_09_28_000001_create_order_items_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('order_items', function(Blueprint $t){
      $t->id();
      $t->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
      $t->foreignId('product_id')->constrained('products'); // ajusta si tu tabla difiere
      $t->string('name');
      $t->string('sku')->nullable();
      $t->decimal('price',12,2);
      $t->unsignedInteger('qty');
      $t->decimal('amount',12,2);
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('order_items'); }
};
