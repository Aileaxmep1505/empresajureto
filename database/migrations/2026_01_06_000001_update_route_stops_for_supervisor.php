<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('route_stops', function (Blueprint $table) {
            // Relación
            if (!Schema::hasColumn('route_stops', 'route_plan_id')) {
                $table->unsignedBigInteger('route_plan_id')->index();
            }

            // Datos básicos del punto
            if (!Schema::hasColumn('route_stops', 'name')) {
                $table->string('name', 180)->nullable();
            }

            // Coordenadas
            if (!Schema::hasColumn('route_stops', 'lat')) {
                $table->decimal('lat', 10, 7)->nullable()->index();
            }
            if (!Schema::hasColumn('route_stops', 'lng')) {
                $table->decimal('lng', 10, 7)->nullable()->index();
            }

            // Orden / secuencia calculada
            if (!Schema::hasColumn('route_stops', 'sequence_index')) {
                $table->integer('sequence_index')->nullable()->index();
            }

            // ETA opcional (si lo usas en UI)
            if (!Schema::hasColumn('route_stops', 'eta_seconds')) {
                $table->integer('eta_seconds')->nullable();
            }

            // Estado y completado
            if (!Schema::hasColumn('route_stops', 'status')) {
                $table->string('status', 20)->default('pending')->index(); // pending|done
            }
            if (!Schema::hasColumn('route_stops', 'done_at')) {
                $table->timestamp('done_at')->nullable()->index();
            }

            // Extras útiles para auditoría/supervisor
            if (!Schema::hasColumn('route_stops', 'provider_id')) {
                $table->unsignedBigInteger('provider_id')->nullable()->index();
            }
            if (!Schema::hasColumn('route_stops', 'address')) {
                $table->string('address', 700)->nullable();
            }
            if (!Schema::hasColumn('route_stops', 'calle')) {
                $table->string('calle', 250)->nullable();
            }
            if (!Schema::hasColumn('route_stops', 'colonia')) {
                $table->string('colonia', 250)->nullable();
            }
            if (!Schema::hasColumn('route_stops', 'ciudad')) {
                $table->string('ciudad', 250)->nullable();
            }
            if (!Schema::hasColumn('route_stops', 'estado')) {
                $table->string('estado', 250)->nullable();
            }
            if (!Schema::hasColumn('route_stops', 'cp')) {
                $table->string('cp', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('route_stops', function (Blueprint $table) {
            // Normalmente no conviene dropear columnas en down en producción.
            // Déjalo vacío o elimina solo si estás seguro.
        });
    }
};
