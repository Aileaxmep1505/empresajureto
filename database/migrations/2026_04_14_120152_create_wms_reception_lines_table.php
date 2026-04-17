<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wms_reception_lines', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('reception_id');
            $table->unsignedBigInteger('catalog_item_id')->nullable();

            $table->string('sku', 120)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('description', 1000);
            $table->integer('quantity')->default(1);

            $table->string('lot', 120)->nullable();
            $table->string('condition', 50)->default('bueno');

            $table->boolean('is_new_product')->default(false);

            $table->timestamps();

            $table->index('reception_id');
            $table->index('catalog_item_id');
            $table->index('sku');
            $table->index('condition');

            $table->foreign('reception_id')
                ->references('id')
                ->on('wms_receptions')
                ->cascadeOnDelete();

            $table->foreign('catalog_item_id')
                ->references('id')
                ->on('catalog_items')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wms_reception_lines', function (Blueprint $table) {
            $table->dropForeign(['reception_id']);
            $table->dropForeign(['catalog_item_id']);
        });

        Schema::dropIfExists('wms_reception_lines');
    }
};