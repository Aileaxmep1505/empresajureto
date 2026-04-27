<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propuesta_comercial_external_matches', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('propuesta_comercial_item_id');

            $table->unsignedInteger('rank')->default(1);

            $table->string('source')->nullable();
            $table->string('title');
            $table->string('seller')->nullable();

            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 10)->nullable();

            $table->text('url');

            $table->decimal('score', 8, 2)->default(0);
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index('propuesta_comercial_item_id', 'pc_ext_item_idx');
            $table->index('score', 'pc_ext_score_idx');

            $table->foreign('propuesta_comercial_item_id', 'pc_ext_item_fk')
                ->references('id')
                ->on('propuesta_comercial_items')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propuesta_comercial_external_matches');
    }
};