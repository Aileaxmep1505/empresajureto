<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items_globales', function (Blueprint $table) {
            $table->id();
            $table->string('clave_verificacion')->nullable()->index();
            $table->string('descripcion_global');
            $table->text('especificaciones_global')->nullable();
            $table->string('unidad_medida', 50)->nullable();
            $table->decimal('cantidad_total', 15, 2)->default(0);
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->json('requisiciones')->nullable(); // lista de requisiciones donde aparece
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items_globales');
    }
};
