<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('period');
            $table->enum('type', [
                'balance_general',
                'estado_resultados',
                'flujo_efectivo',
                'notas',
                'otro',
            ])->default('otro');
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_statements');
    }
};