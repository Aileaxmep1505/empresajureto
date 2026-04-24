<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propuestas_comerciales', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('licitacion_pdf_id')->nullable()->index();
            $table->unsignedBigInteger('document_ai_run_id')->nullable()->index();

            $table->string('titulo')->nullable();
            $table->string('folio')->nullable();
            $table->string('cliente')->nullable();

            $table->decimal('porcentaje_utilidad', 8, 2)->default(0);
            $table->decimal('porcentaje_descuento', 8, 2)->default(0);
            $table->decimal('porcentaje_impuesto', 8, 2)->default(16.00);

            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('descuento_total', 18, 2)->default(0);
            $table->decimal('impuesto_total', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);

            $table->string('status')->default('draft'); // draft|matched|priced|completed
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propuestas_comerciales');
    }
};