<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id(); // Folio = id (arranca en 1000)
            // 🔧 Apunta a 'clients' (no 'clientes')
            $table->foreignId('cliente_id')
                  ->constrained('clients')
                  ->cascadeOnUpdate()
                  ->restrictOnDelete();

            $table->string('estado')->default('borrador'); // borrador|enviada|aprobada|rechazada|convertida
            $table->text('notas')->nullable();

            // Totales
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('envio', 12, 2)->default(0);
            $table->decimal('iva', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Extras
            $table->string('moneda', 10)->default('MXN');
            $table->unsignedSmallInteger('validez_dias')->default(15);
            $table->date('vence_el')->nullable();

            // Config de financiamiento
            $table->json('financiamiento_config')->nullable();

            $table->timestamps();
        });

        // Folio inicia en 1000
        DB::statement('ALTER TABLE cotizaciones AUTO_INCREMENT = 1000;');
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
