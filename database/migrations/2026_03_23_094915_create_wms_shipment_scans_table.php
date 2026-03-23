<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_shipment_scans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipment_id')->constrained('wms_shipments')->cascadeOnDelete();
            $table->foreignId('shipment_line_id')->nullable()->constrained('wms_shipment_lines')->nullOnDelete();

            $table->string('scan_type', 40)->index();
            $table->string('scan_value', 150)->index();

            $table->unsignedInteger('qty')->default(0);
            $table->string('box_label', 150)->nullable()->index();

            $table->string('result', 40)->default('accepted')->index();
            $table->string('message', 255)->nullable();

            $table->json('payload')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_shipment_scans');
    }
};