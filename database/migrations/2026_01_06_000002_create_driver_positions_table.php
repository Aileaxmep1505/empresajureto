<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('driver_positions')) return;

        Schema::create('driver_positions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->index(); // chofer (users.id)

            $table->decimal('lat', 10, 7)->index();
            $table->decimal('lng', 10, 7)->index();

            $table->float('accuracy')->nullable(); // metros aprox
            $table->float('speed')->nullable();    // m/s
            $table->float('heading')->nullable();  // grados

            $table->timestamp('captured_at')->index();
            $table->timestamps();

            // Si quieres FK:
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_positions');
    }
};
