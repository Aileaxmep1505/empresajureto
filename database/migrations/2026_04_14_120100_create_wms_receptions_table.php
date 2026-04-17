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
        Schema::create('wms_receptions', function (Blueprint $table) {
            $table->id();

            $table->string('folio', 120)->unique();

            $table->unsignedBigInteger('deliverer_user_id')->nullable();
            $table->unsignedBigInteger('receiver_user_id')->nullable();

            $table->string('deliverer_name', 255);
            $table->string('receiver_name', 255);

            $table->dateTime('reception_date')->nullable();

            $table->longText('observations')->nullable();

            $table->string('status', 50)->default('pendiente');

            $table->string('signature_token', 120)->nullable()->unique();

            $table->longText('delivered_signature')->nullable();
            $table->longText('received_signature')->nullable();

            $table->longText('products')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->index('deliverer_user_id');
            $table->index('receiver_user_id');
            $table->index('created_by');
            $table->index('status');
            $table->index('reception_date');

            $table->foreign('deliverer_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('receiver_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wms_receptions', function (Blueprint $table) {
            $table->dropForeign(['deliverer_user_id']);
            $table->dropForeign(['receiver_user_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('wms_receptions');
    }
};