<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_invoice_items', function (Blueprint $table) {
            $table->id();

            // Encabezado de factura
            $table->foreignId('manual_invoice_id')
                  ->constrained('manual_invoices')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();

            // Producto de tu catálogo (puede quedar null si lo borras)
            $table->foreignId('product_id')
                  ->nullable()
                  ->constrained('products')
                  ->cascadeOnUpdate()
                  ->nullOnDelete();

            // Snapshot de datos del producto
            $table->string('description');
            $table->string('sku')->nullable();
            $table->string('unit')->nullable();      // unidad textual "PZA", "CJ", etc.
            $table->string('unit_code', 10)->nullable(); // clave SAT unidad
            $table->string('product_key', 10)->nullable(); // clave SAT prod/serv

            // Cantidades y montos
            $table->decimal('quantity', 14, 3)->default(1);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            // IVA simple (si ocupas mas impuestos luego se puede separar)
            $table->decimal('tax_rate', 5, 2)->default(0); // 16.00, 8.00, 0, etc.

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_invoice_items');
    }
};
