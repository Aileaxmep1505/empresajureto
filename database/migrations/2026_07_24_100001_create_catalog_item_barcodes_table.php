<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Presentaciones de venta / empaques de un producto.
 *
 * Un mismo CatalogItem (mismo SKU) puede recibirse y venderse en varias
 * presentaciones (pieza suelta, caja con 99, etc.). Cada presentación tiene:
 *  - su propio código de barras (para escanear en WMS/picking),
 *  - un factor de piezas base (units),
 *  - y opcionalmente su propio precio de venta en la web.
 *
 * El stock SIEMPRE se cuenta en piezas base. Escanear/comprar una presentación
 * suma o descuenta `units` piezas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_item_barcodes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('catalog_item_id')
                ->constrained('catalog_items')
                ->cascadeOnDelete();

            // Código de barras de esta presentación (caja 192838721, suelto 178378217, ...)
            $table->string('barcode', 120)->nullable();

            // box | inner | piece
            $table->string('pack_type', 20)->default('piece');

            // Etiqueta visible: "Pieza", "Caja con 99", "Paquete x12"
            $table->string('label', 120)->nullable();

            // Piezas base que representa UN escaneo / UNA unidad de esta presentación
            $table->unsignedInteger('units')->default(1);

            // Precio de venta de ESTA presentación (nullable = usa base * units)
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();

            // ¿Se ofrece esta presentación en la tienda web?
            $table->boolean('is_sellable_web')->default(false);

            // Presentación principal del producto (para escaneo por defecto)
            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->index('catalog_item_id');
            // El código, cuando existe, debe ser único en todo el catálogo.
            $table->unique('barcode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_item_barcodes');
    }
};
