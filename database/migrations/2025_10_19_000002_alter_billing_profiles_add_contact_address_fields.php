<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('billing_profiles', function (Blueprint $table) {
            // Ampliar longitudes para guardar textos completos en lugar de códigos
            // Nota: requiere doctrine/dbal para ->change() en la mayoría de setups.
            $table->string('regimen', 200)->nullable()->change();
            $table->string('uso_cfdi', 200)->nullable()->change();

            // ===== Nuevos campos (contacto y domicilio fiscal) =====
            $table->string('contact_name', 120)->nullable()->after('email');
            $table->string('phone', 30)->nullable()->after('contact_name');

            $table->string('street', 180)->nullable()->after('zip');
            $table->string('ext_number', 30)->nullable()->after('street');
            $table->string('int_number', 30)->nullable()->after('ext_number');
            $table->string('colony', 120)->nullable()->after('int_number');
            $table->string('state', 120)->nullable()->after('colony');
            $table->string('municipality', 120)->nullable()->after('state');

            // Método de pago preferido para factura (visual/registro)
            $table->string('payment_method', 50)->nullable()->default('Tarjeta')->after('municipality');
        });
    }

    public function down(): void
    {
        Schema::table('billing_profiles', function (Blueprint $table) {
            // Revertir longitudes
            $table->string('regimen', 5)->nullable()->change();
            $table->string('uso_cfdi', 3)->nullable()->change();

            // Quitar campos nuevos
            $table->dropColumn([
                'contact_name',
                'phone',
                'street',
                'ext_number',
                'int_number',
                'colony',
                'state',
                'municipality',
                'payment_method',
            ]);
        });
    }
};
