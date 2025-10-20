<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('shipping_addresses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Contacto
            $t->string('contact_name')->nullable();
            $t->string('phone')->nullable();
            $t->string('phone_ext')->nullable();

            // Tipo y campos principales
            $t->string('address_type')->nullable(); // Casa, Oficina, etc
            $t->string('street');                   // Calle
            $t->string('ext_number')->nullable();   // Num exterior
            $t->string('int_number')->nullable();   // Num interior
            $t->string('colony')->nullable();       // Colonia
            $t->string('postal_code', 10);

            // Autocompletar por CP
            $t->string('state')->nullable();
            $t->string('municipality')->nullable();

            // Entre calles y referencias
            $t->string('between_street_1')->nullable();
            $t->string('between_street_2')->nullable();
            $t->text('references')->nullable();

            $t->boolean('is_default')->default(true);

            $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('shipping_addresses');
    }
};
