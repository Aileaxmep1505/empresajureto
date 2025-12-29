<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wms_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('type', 10); // in | out
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('wms_movement_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('movement_id')->index();
            $table->unsignedBigInteger('catalog_item_id')->index();
            $table->unsignedBigInteger('location_id')->index();
            $table->integer('qty'); // siempre positivo, el type define si suma o resta
            $table->integer('stock_before')->nullable();
            $table->integer('stock_after')->nullable();
            $table->integer('inv_before')->nullable();
            $table->integer('inv_after')->nullable();
            $table->timestamps();

            $table->foreign('movement_id')->references('id')->on('wms_movements')->onDelete('cascade');
            $table->foreign('catalog_item_id')->references('id')->on('catalog_items')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_movement_lines');
        Schema::dropIfExists('wms_movements');
    }
};
