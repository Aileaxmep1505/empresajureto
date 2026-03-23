<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_shipments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pick_wave_id')->constrained('pick_waves')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();

            $table->string('shipment_number', 60)->unique();
            $table->string('order_number', 120)->nullable()->index();
            $table->string('task_number', 120)->nullable()->index();

            $table->string('vehicle_plate', 40)->nullable();
            $table->string('vehicle_name', 120)->nullable();
            $table->string('driver_name', 120)->nullable();
            $table->string('driver_phone', 60)->nullable();
            $table->string('route_name', 120)->nullable();

            $table->foreignId('operator_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status', 40)->default('draft')->index();

            $table->unsignedInteger('expected_lines')->default(0);
            $table->unsignedInteger('scanned_lines')->default(0);

            $table->unsignedInteger('expected_qty')->default(0);
            $table->unsignedInteger('loaded_qty')->default(0);
            $table->unsignedInteger('missing_qty')->default(0);
            $table->unsignedInteger('extra_qty')->default(0);

            $table->unsignedInteger('expected_boxes')->default(0);
            $table->unsignedInteger('loaded_boxes')->default(0);
            $table->unsignedInteger('missing_boxes')->default(0);

            $table->timestamp('loading_started_at')->nullable();
            $table->timestamp('loading_completed_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();

            $table->string('signed_by_name', 120)->nullable();
            $table->string('signed_by_role', 120)->nullable();
            $table->longText('signature_data')->nullable();

            $table->text('notes')->nullable();
            $table->json('meta')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_shipments');
    }
};