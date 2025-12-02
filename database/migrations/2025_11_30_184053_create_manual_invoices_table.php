<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_invoices', function (Blueprint $table) {
            $table->id();

            // Relación con clientes
            $table->foreignId('client_id')
                  ->constrained('clients')
                  ->cascadeOnUpdate()
                  ->restrictOnDelete();

            // Tipo de CFDI: Ingreso / Egreso / Pago
            $table->enum('type', ['I','E','P'])->default('I');

            // Serie / folio internos (pueden venir de Facturapi después)
            $table->string('serie')->nullable();
            $table->unsignedInteger('folio')->nullable();

            // Totales
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            $table->string('currency', 3)->default('MXN');

            // Estado interno
            // draft  = sólo en tu sistema, sin timbrar en Facturapi
            // valid  = timbrada correctamente
            // pending_cancel = cancelación solicitada
            // cancelled = cancelada
            $table->enum('status', ['draft', 'valid', 'pending_cancel', 'cancelled'])
                  ->default('draft');

            // Facturapi
            $table->string('facturapi_id')->nullable();
            $table->string('facturapi_uuid')->nullable();
            $table->string('verification_url')->nullable();
            $table->string('facturapi_status')->nullable();
            $table->string('cancellation_status')->nullable();
            $table->dateTime('stamped_at')->nullable();

            // Snapshot del cliente al momento de facturar (por si luego cambian sus datos)
            $table->string('receiver_name')->nullable();
            $table->string('receiver_rfc', 13)->nullable();
            $table->string('receiver_email')->nullable();

            // Observaciones internas
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_invoices');
    }
};
