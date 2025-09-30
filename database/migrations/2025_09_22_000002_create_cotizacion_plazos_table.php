<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cotizacion_plazos', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();

            $table->foreignId('cotizacion_id')
                  ->constrained('cotizaciones')
                  ->cascadeOnDelete();

            $table->unsignedSmallInteger('numero'); // 1,2,3...
            $table->date('vence_el');
            $table->decimal('monto', 12, 2);
            $table->boolean('pagado')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_plazos');
    }
};
