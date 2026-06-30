<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shipments')) {
            return;
        }

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('order_id')->nullable()->index();

            $table->string('provider')->default('envia.com');
            $table->string('mode')->default('sandbox');

            $table->string('carrier')->nullable();
            $table->string('carrier_key')->nullable()->index();
            $table->string('service')->nullable();

            $table->string('tracking_number')->nullable()->index();
            $table->text('tracking_url')->nullable();
            $table->text('label_url')->nullable();

            $table->string('status')->default('created')->index();
            $table->string('status_label')->default('Guía generada');

            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency', 10)->default('MXN');

            $table->json('destination')->nullable();
            $table->json('last_tracking_event')->nullable();
            $table->timestamp('last_tracked_at')->nullable();

            $table->json('raw_response')->nullable();
            $table->json('tracking_raw')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
