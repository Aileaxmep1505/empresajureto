<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_shipment_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipment_id')->constrained('wms_shipments')->cascadeOnDelete();

            $table->string('pick_line_id', 80)->nullable()->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();

            $table->string('product_name', 255);
            $table->string('product_sku', 120)->nullable()->index();
            $table->string('batch_code', 120)->nullable()->index();

            $table->string('location_code', 120)->nullable();
            $table->string('staging_location_code', 120)->nullable();

            $table->boolean('is_fastflow')->default(false);
            $table->unsignedInteger('phase')->default(1);

            $table->unsignedInteger('expected_qty')->default(0);
            $table->unsignedInteger('loaded_qty')->default(0);
            $table->unsignedInteger('missing_qty')->default(0);
            $table->unsignedInteger('extra_qty')->default(0);

            $table->unsignedInteger('expected_boxes')->default(0);
            $table->unsignedInteger('loaded_boxes')->default(0);
            $table->unsignedInteger('missing_boxes')->default(0);

            $table->string('status', 40)->default('pending')->index();

            $table->string('reason_code', 80)->nullable();
            $table->text('reason_note')->nullable();

            $table->json('expected_boxes_json')->nullable();
            $table->json('loaded_boxes_json')->nullable();
            $table->json('expected_allocations_json')->nullable();
            $table->json('loaded_allocations_json')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique(['shipment_id', 'pick_line_id'], 'wms_shipment_lines_ship_pickline_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_shipment_lines');
    }
};