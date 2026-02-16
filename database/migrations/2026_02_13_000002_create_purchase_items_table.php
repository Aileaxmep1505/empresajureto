<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_document_id')->constrained('purchase_documents')->cascadeOnDelete();

            $table->string('item_name')->nullable();       // concepto limpio
            $table->string('item_raw')->nullable();        // renglón original
            $table->string('unit')->nullable();            // pza, kg, lt, etc.

            $table->decimal('qty', 14, 3)->default(1);
            $table->decimal('unit_price', 14, 4)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);

            $table->json('ai_meta')->nullable(); // gtin, sku, confidence, etc.
            $table->timestamps();

            $table->index(['item_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};