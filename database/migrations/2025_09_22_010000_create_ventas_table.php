<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id(); // Folio de venta (puedes iniciar en 2000 si quieres)
            $table->foreignId('cliente_id')->constrained('clients')->cascadeOnUpdate()->restrictOnDelete();

            // Relación con la cotización que originó la venta
            $table->foreignId('cotizacion_id')->nullable()->constrained('cotizaciones')->nullOnDelete();

            $table->string('estado')->default('abierta'); // abierta|pagada|cancelada
            $table->text('notas')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('envio', 12, 2)->default(0);
            $table->decimal('iva', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->string('moneda', 10)->default('MXN');

            // Si quieres replicar la config del financiamiento
            $table->json('financiamiento_config')->nullable();

            $table->timestamps();
        });

        // (Opcional) Iniciar folio en 2000 para diferenciar de cotizaciones
        // DB::statement('ALTER TABLE ventas AUTO_INCREMENT = 2000;');
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
