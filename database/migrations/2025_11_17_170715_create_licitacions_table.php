<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('licitaciones', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->date('fecha_convocatoria')->nullable();
            $table->string('modalidad')->nullable(); // presencial / en_linea

            // Junta de aclaraciones
            $table->dateTime('fecha_junta_aclaraciones')->nullable();
            $table->dateTime('fecha_limite_preguntas')->nullable();
            $table->string('lugar_junta')->nullable();
            $table->string('link_junta')->nullable();

            // Apertura y muestras
            $table->dateTime('fecha_apertura_propuesta')->nullable();
            $table->boolean('requiere_muestras')->default(false);
            $table->dateTime('fecha_entrega_muestras')->nullable();
            $table->string('lugar_entrega_muestras')->nullable();

            // Fallo
            $table->string('resultado')->nullable(); // ganado / no_ganado
            $table->text('observaciones_fallo')->nullable();
            $table->dateTime('fecha_fallo')->nullable();

            // Presentación fallo (si ganó)
            $table->dateTime('fecha_presentacion_fallo')->nullable();
            $table->string('lugar_presentacion_fallo')->nullable();
            $table->text('docs_presentar_fallo')->nullable();

            // Contrato
            $table->date('fecha_emision_contrato')->nullable();
            $table->date('fecha_fianza')->nullable();

            // Control del flujo
            $table->string('estatus')->default('borrador'); // borrador, en_proceso, cerrado, etc.
            $table->unsignedTinyInteger('current_step')->default(1);

            // Relación con usuario creador
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licitaciones');
    }
};
