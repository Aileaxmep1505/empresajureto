<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_receivables', function (Blueprint $table) {
            $table->id();

            // ✅ Company
            $table->foreignId('company_id')->constrained('companies')->cascadeOnUpdate()->restrictOnDelete();

            $table->string('folio')->nullable()->index();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->string('client_name');
            $table->text('description')->nullable();

            $table->string('document_type', 40)->default('factura'); // factura|nota_credito|cargo_adicional|anticipo
            $table->string('category', 40)->default('factura');      // factura|honorarios|renta|servicios|producto|otro

            $table->decimal('amount', 16, 2);
            $table->decimal('amount_paid', 16, 2)->default(0);

            $table->string('currency', 3)->default('MXN');

            $table->date('issue_date')->nullable();
            $table->date('due_date')->index();
            $table->date('payment_date')->nullable();

            $table->string('status', 20)->default('pendiente')->index(); // pendiente|parcial|cobrado|vencido|cancelado
            $table->string('priority', 10)->default('media');            // alta|media|baja

            $table->string('payment_method', 30)->nullable(); // transferencia|efectivo|tarjeta|cheque|otro
            $table->string('bank_reference')->nullable();

            $table->unsignedInteger('credit_days')->default(30);
            $table->decimal('interest_rate', 8, 2)->nullable();

            $table->string('assigned_to')->nullable();
            $table->string('collection_status', 20)->default('sin_gestion'); // sin_gestion|en_gestion|promesa_pago|litigio|incobrable

            $table->string('evidence_url')->nullable();
            $table->json('documents')->nullable();
            $table->json('document_names')->nullable();

            $table->text('notes')->nullable();
            $table->unsignedInteger('reminder_days_before')->default(5);
            $table->json('tags')->nullable();

            // estilo “RLS” de tu schema (por email)
            $table->string('created_by')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status', 'due_date'], 'ar_company_status_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_receivables');
    }
};