<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_quick_boxes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('catalog_item_id')->constrained('catalog_items')->cascadeOnDelete();

            $table->string('batch_code', 60)->index();
            $table->string('label_code', 90)->unique();

            $table->unsignedInteger('box_number')->default(1);
            $table->unsignedInteger('boxes_in_batch')->default(1);
            $table->unsignedInteger('units_per_box')->default(1);

            $table->string('status', 20)->default('available')->index(); // available|shipped|cancelled

            $table->timestamp('received_at')->nullable();
            $table->timestamp('shipped_at')->nullable();

            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shipped_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['warehouse_id', 'status']);
            $table->index(['catalog_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_quick_boxes');
    }
};