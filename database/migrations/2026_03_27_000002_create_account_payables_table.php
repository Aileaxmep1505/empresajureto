<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_payables', function (Blueprint $table) {
            $table->id();

            // ✅ Company
            $table->foreignId('company_id')->constrained('companies')->cascadeOnUpdate()->restrictOnDelete();

            // opcional: folio/documento proveedor
            $table->string('folio')->nullable()->index();

            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            $table->string('supplier_name')->nullable();

            $table->string('title'); // Nombre del pago / cuenta por pagar
            $table->text('description')->nullable();

            $table->string('category', 40)->default('otros');  // impuestos|cuentas_por_pagar|servicios|nomina|seguros|retenciones|otros
            $table->string('frequency', 20)->default('unico'); // unico|mensual|bimestral|trimestral|semestral|anual

            $table->decimal('amount', 16, 2);
            $table->decimal('amount_paid', 16, 2)->default(0);
            $table->string('currency', 3)->default('MXN');

            $table->date('issue_date')->nullable();
            $table->date('due_date')->index();
            $table->date('payment_date')->nullable();

            // ✅ Incluye URGENTE como en tu schema Payment
            $table->string('status', 20)->default('pendiente')->index(); // pendiente|urgente|parcial|pagado|atrasado|cancelado

            $table->string('payment_method', 30)->nullable(); // transferencia|efectivo|tarjeta|cheque|otro
            $table->string('bank_reference')->nullable();

            $table->string('evidence_url')->nullable();
            $table->json('documents')->nullable();
            $table->json('document_names')->nullable();

            $table->date('retention_expiry')->nullable();
            $table->text('notes')->nullable();

            $table->unsignedInteger('reminder_days_before')->default(3);

            // opcional para ligar con tu módulo de gastos actual
            $table->unsignedBigInteger('expense_id')->nullable()->index();

            $table->string('created_by')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status', 'due_date'], 'ap_company_status_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_payables');
    }
};