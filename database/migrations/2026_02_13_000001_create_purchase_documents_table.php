<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('publication_id')->nullable()->constrained('publications')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('source_kind', 30)->default('upload'); // upload | manual | import
            $table->string('document_type', 30)->nullable();      // ticket | factura | remision | otro
            $table->string('supplier_name')->nullable();
            $table->string('currency', 10)->default('MXN');

            $table->dateTime('document_datetime')->nullable();

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            $table->json('ai_meta')->nullable(); // raw json, warnings, confidence, etc.
            $table->timestamps();

            $table->index(['document_datetime']);
            $table->index(['supplier_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_documents');
    }
};