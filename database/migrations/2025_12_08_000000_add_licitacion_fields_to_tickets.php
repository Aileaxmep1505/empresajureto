<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ====== AGREGAR NUEVOS CAMPOS ======
        Schema::table('tickets', function (Blueprint $table) {
            // Proceso dentro de la licitación
            if (!Schema::hasColumn('tickets', 'licitacion_phase')) {
                $table->string('licitacion_phase', 80)
                    ->nullable()
                    ->after('type'); // justo después de type
            }

            // Notas rápidas que sí se guardan
            if (!Schema::hasColumn('tickets', 'quick_notes')) {
                $table->text('quick_notes')
                    ->nullable()
                    ->after('monto_propuesta');
            }
        });

        // ====== QUITAR CAMPOS QUE YA NO SE USAN EN TICKET ======
        Schema::table('tickets', function (Blueprint $table) {
            // Estos campos estaban en el modelo viejo y ya no los necesitamos
            if (Schema::hasColumn('tickets', 'fecha_entrega_docs')) {
                $table->dropColumn('fecha_entrega_docs');
            }
            if (Schema::hasColumn('tickets', 'fecha_apertura_tecnica')) {
                $table->dropColumn('fecha_apertura_tecnica');
            }
            if (Schema::hasColumn('tickets', 'fecha_apertura_economica')) {
                $table->dropColumn('fecha_apertura_economica');
            }
        });
    }

    public function down(): void
    {
        // Revertir: volver a crear los campos eliminados
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'fecha_entrega_docs')) {
                $table->date('fecha_entrega_docs')->nullable()
                    ->after('monto_propuesta');
            }
            if (!Schema::hasColumn('tickets', 'fecha_apertura_tecnica')) {
                $table->date('fecha_apertura_tecnica')->nullable()
                    ->after('fecha_entrega_docs');
            }
            if (!Schema::hasColumn('tickets', 'fecha_apertura_economica')) {
                $table->date('fecha_apertura_economica')->nullable()
                    ->after('fecha_apertura_tecnica');
            }
        });

        // Quitar campos nuevos
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'licitacion_phase')) {
                $table->dropColumn('licitacion_phase');
            }
            if (Schema::hasColumn('tickets', 'quick_notes')) {
                $table->dropColumn('quick_notes');
            }
        });
    }
};
