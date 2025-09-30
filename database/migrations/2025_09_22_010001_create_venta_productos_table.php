<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('venta_productos', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();

            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();

            // Snapshot de la línea
            $table->string('descripcion')->nullable();
            $table->decimal('cantidad', 12, 2)->default(1);
            $table->decimal('precio_unitario', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('iva_porcentaje', 5, 2)->default(16.00);
            $table->decimal('importe', 12, 2)->default(0); // total línea (con IVA)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_productos');
    }
};
