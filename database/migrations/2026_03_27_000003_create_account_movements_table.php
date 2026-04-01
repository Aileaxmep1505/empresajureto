<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_movements', function (Blueprint $table) {
            $table->id();

            // ✅ Company (para filtrar rápido)
            $table->foreignId('company_id')->constrained('companies')->cascadeOnUpdate()->restrictOnDelete();

            // incoming (cobro/abono a CxC) | outgoing (pago a CxP)
            $table->string('direction', 10)->index();

            // receivable | payable
            $table->string('related_type', 20)->index();
            $table->unsignedBigInteger('related_id')->index();

            $table->date('movement_date')->index();
            $table->decimal('amount', 16, 2);

            $table->string('currency', 3)->default('MXN');
            $table->string('method', 30)->nullable();
            $table->string('reference')->nullable();

            $table->string('status', 20)->default('aplicado')->index(); // aplicado|reversado|cancelado

            $table->string('evidence_url')->nullable();
            $table->json('documents')->nullable();
            $table->json('document_names')->nullable();
            $table->text('notes')->nullable();

            // opcional: si al pagar registras también gasto en tu sistema actual
            $table->unsignedBigInteger('expense_id')->nullable()->index();

            $table->string('created_by')->nullable()->index();

            $table->timestamps();

            $table->index(['company_id', 'related_type', 'related_id', 'status'], 'am_company_related_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_movements');
    }
};