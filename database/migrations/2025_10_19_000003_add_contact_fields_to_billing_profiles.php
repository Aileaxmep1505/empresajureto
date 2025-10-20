<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('billing_profiles', function (Blueprint $table) {
            // Datos de contacto / domicilio fiscal ampliado
            $table->string('contacto', 120)->nullable()->after('uso_cfdi');
            $table->string('telefono', 30)->nullable()->after('contacto');
            $table->string('direccion', 190)->nullable()->after('telefono');
            $table->string('colonia', 120)->nullable()->after('direccion');
            $table->string('estado', 120)->nullable()->after('colonia');

            // Preferencia de método de pago mostrado en el modal (solo informativo)
            $table->string('metodo_pago', 40)->nullable()->after('estado');
        });
    }

    public function down(): void {
        Schema::table('billing_profiles', function (Blueprint $table) {
            $table->dropColumn(['contacto','telefono','direccion','colonia','estado','metodo_pago']);
        });
    }
};
