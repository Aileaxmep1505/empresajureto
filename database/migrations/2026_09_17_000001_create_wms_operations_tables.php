<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operaciones nuevas del WMS:
 *   - Reabastecimiento (tareas de mover reserva -> picking)
 *   - Conteos de inventario (cíclicos / por ubicación) con sus líneas
 *   - Cross-docking (mercancía recibida asignada directo a una ola de picking)
 *   - Citas de andén (patio / muelles)
 *   - Ajustes del WMS (metas de productividad, lista de andenes)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_replenishment_tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $t->foreignId('catalog_item_id')->constrained('catalog_items')->cascadeOnDelete();
            $t->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $t->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $t->unsignedInteger('qty_suggested');
            $t->unsignedInteger('qty_moved')->default(0);
            $t->string('priority', 12)->default('normal');   // alta | media | normal
            $t->string('status', 16)->default('pendiente');  // pendiente | hecha | cancelada
            $t->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('completed_at')->nullable();
            $t->string('notes', 500)->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();

            $t->index(['status', 'priority']);
        });

        Schema::create('wms_counts', function (Blueprint $t) {
            $t->id();
            $t->string('folio', 30)->unique();
            $t->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $t->string('scope', 20);                          // ubicaciones | criticos | aleatorio | todo
            $t->string('status', 16)->default('abierto');     // abierto | cerrado | cancelado
            $t->boolean('blind')->default(true);              // conteo a ciegas: no se ve lo esperado
            $t->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('closed_at')->nullable();
            $t->string('notes', 500)->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
        });

        Schema::create('wms_count_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('count_id')->constrained('wms_counts')->cascadeOnDelete();
            $t->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $t->foreignId('catalog_item_id')->constrained('catalog_items')->cascadeOnDelete();
            $t->integer('expected_qty')->default(0);
            $t->integer('counted_qty')->nullable();
            $t->boolean('adjusted')->default(false);
            $t->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('counted_at')->nullable();
            $t->string('note', 300)->nullable();
            $t->timestamps();

            $t->unique(['count_id', 'location_id', 'catalog_item_id'], 'wms_count_lines_unica');
        });

        Schema::create('wms_crossdock_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('reception_id')->constrained('wms_receptions')->cascadeOnDelete();
            $t->foreignId('reception_line_id')->constrained('wms_reception_lines')->cascadeOnDelete();
            $t->foreignId('pick_wave_id')->constrained('pick_waves')->cascadeOnDelete();
            $t->string('wave_line_id', 80)->nullable();       // line_id dentro del JSON de la ola
            $t->foreignId('catalog_item_id')->constrained('catalog_items')->cascadeOnDelete();
            $t->unsignedInteger('qty');
            $t->foreignId('staging_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $t->string('status', 16)->default('asignado');    // asignado | en_anden | entregado | cancelado
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('staged_at')->nullable();
            $t->timestamp('delivered_at')->nullable();
            $t->string('notes', 500)->nullable();
            $t->timestamps();

            $t->index(['status', 'pick_wave_id']);
        });

        Schema::create('wms_dock_appointments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $t->string('dock', 60);
            $t->string('type', 10)->default('entrada');       // entrada | salida
            $t->string('carrier', 120)->nullable();
            $t->string('vehicle_plate', 30)->nullable();
            $t->string('driver_name', 120)->nullable();
            $t->string('reference', 120)->nullable();         // OC, folio de embarque, etc.
            $t->dateTime('scheduled_at');
            $t->unsignedSmallInteger('duration_min')->default(60);
            $t->string('status', 16)->default('programada');  // programada | llego | en_anden | terminada | no_llego | cancelada
            $t->dateTime('arrived_at')->nullable();
            $t->dateTime('started_at')->nullable();
            $t->dateTime('finished_at')->nullable();
            $t->string('notes', 500)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->index(['scheduled_at', 'dock']);
        });

        Schema::create('wms_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key', 80)->unique();
            $t->json('value')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_settings');
        Schema::dropIfExists('wms_dock_appointments');
        Schema::dropIfExists('wms_crossdock_assignments');
        Schema::dropIfExists('wms_count_lines');
        Schema::dropIfExists('wms_counts');
        Schema::dropIfExists('wms_replenishment_tasks');
    }
};
