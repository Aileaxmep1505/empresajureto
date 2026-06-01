<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_item_id')
                ->constrained('inventory_items')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->integer('quantity')->default(1);
            $table->longText('signature'); // firma (base64)
            $table->string('folio')->nullable();
            $table->text('notes')->nullable();

            $table->string('status')->default('activa'); // activa | devuelta

            // Devolución
            $table->text('return_reason')->nullable();
            $table->text('return_details')->nullable();
            $table->enum('return_condition', ['excelente', 'bueno', 'regular', 'malo', 'dañado'])->nullable();
            $table->timestamp('returned_at')->nullable();

            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_assignments');
    }
};