<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('adjudicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propuesta_comercial_id')
                ->constrained('propuestas_comerciales')
                ->cascadeOnDelete();

            $table->foreignId('propuesta_fallo_id')
                ->nullable()
                ->constrained('propuesta_fallos')
                ->nullOnDelete();

            $table->foreignId('client_id')
                ->nullable()
                ->constrained('clients')
                ->nullOnDelete();

            $table->string('cliente_nombre')->nullable();   // respaldo en texto
            $table->string('folio')->nullable();
            $table->date('fecha')->nullable();

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('descuento_total', 14, 2)->default(0);
            $table->decimal('porcentaje_impuesto', 8, 2)->default(16);
            $table->decimal('impuesto_total', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            // borrador | confirmada | remisionada | cerrada
            $table->string('status')->default('borrador');

            $table->text('observaciones')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjudicaciones');
    }
};