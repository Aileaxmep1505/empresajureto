<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            // Identificadores / estado
            if (!Schema::hasColumn('orders','stripe_session_id')) {
                $t->string('stripe_session_id')->nullable()->index()->after('status');
            }

            // Envío (snapshot de la opción elegida)
            if (!Schema::hasColumn('orders','shipping_code'))        $t->string('shipping_code')->nullable()->after('shipping');
            if (!Schema::hasColumn('orders','shipping_name'))        $t->string('shipping_name')->nullable()->after('shipping_code');
            if (!Schema::hasColumn('orders','shipping_service'))     $t->string('shipping_service')->nullable()->after('shipping_name');
            if (!Schema::hasColumn('orders','shipping_eta'))         $t->string('shipping_eta')->nullable()->after('shipping_service');
            if (!Schema::hasColumn('orders','shipping_store_pays'))  $t->boolean('shipping_store_pays')->default(false)->after('shipping_eta');
            if (!Schema::hasColumn('orders','shipping_carrier_cost'))$t->decimal('shipping_carrier_cost', 12, 2)->nullable()->after('shipping_store_pays');

            // Dirección de envío (snapshot)
            if (!Schema::hasColumn('orders','shipping_address_json')) $t->json('shipping_address_json')->nullable()->after('shipping_carrier_cost');

            // Facturación (snapshot mínimo para timbrado posterior)
            if (!Schema::hasColumn('orders','invoice_rfc'))      $t->string('invoice_rfc')->nullable()->after('customer_address');
            if (!Schema::hasColumn('orders','invoice_razon'))    $t->string('invoice_razon')->nullable()->after('invoice_rfc');
            if (!Schema::hasColumn('orders','invoice_uso_cfdi')) $t->string('invoice_uso_cfdi')->nullable()->after('invoice_razon');
            if (!Schema::hasColumn('orders','invoice_regimen'))  $t->string('invoice_regimen')->nullable()->after('invoice_uso_cfdi');
            if (!Schema::hasColumn('orders','invoice_zip'))      $t->string('invoice_zip')->nullable()->after('invoice_regimen');

            // CFDI provider (si timbras)
            if (!Schema::hasColumn('orders','invoice_provider'))  $t->string('invoice_provider')->nullable()->after('invoice_zip');
            if (!Schema::hasColumn('orders','invoice_id'))        $t->string('invoice_id')->nullable()->after('invoice_provider');

            // Acelera filtros por estado
            if (!Schema::hasColumn('orders','_idx_status')) {
                $t->index('status','orders_status_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            // No drop para no perder datos; si quieres revertir, descomenta:
            // $t->dropColumn(['stripe_session_id','shipping_code','shipping_name','shipping_service','shipping_eta',
            //   'shipping_store_pays','shipping_carrier_cost','shipping_address_json',
            //   'invoice_rfc','invoice_razon','invoice_uso_cfdi','invoice_regimen','invoice_zip',
            //   'invoice_provider','invoice_id']);
            // $t->dropIndex('orders_status_index');
        });
    }
};
