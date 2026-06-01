<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_category_id')
                ->constrained('inventory_categories')
                ->cascadeOnDelete();

            $table->string('name');
            $table->enum('type', ['activo_fijo', 'consumible']);

            // Solo activos fijos
            $table->enum('asset_status', ['disponible', 'asignado', 'en_reparacion', 'dado_de_baja'])->nullable();
            $table->enum('condition', ['nuevo', 'bueno', 'regular', 'malo'])->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();

            // Stock (consumibles usan min/max)
            $table->integer('stock')->default(0);
            $table->integer('stock_min')->default(0);
            $table->integer('stock_max')->default(0);

            $table->string('unit', 50)->nullable();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->string('photo')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};