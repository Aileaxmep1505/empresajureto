<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Relación / datos del cliente
            if (!Schema::hasColumn('orders', 'billing_profile_id')) {
                $table->unsignedBigInteger('billing_profile_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('orders', 'customer_name')) {
                $table->string('customer_name', 190)->nullable()->after('billing_profile_id');
            }
            if (!Schema::hasColumn('orders', 'customer_email')) {
                $table->string('customer_email', 190)->nullable()->after('customer_name');
            }

            // Totales
            if (!Schema::hasColumn('orders', 'shipping_amount')) {
                $table->decimal('shipping_amount', 10, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('orders', 'tax')) {
                $table->decimal('tax', 10, 2)->default(0)->after('shipping_amount');
            }
            if (!Schema::hasColumn('orders', 'currency')) {
                $table->string('currency', 3)->default('MXN')->after('tax');
            }
            if (!Schema::hasColumn('orders', 'status')) {
                $table->string('status', 32)->default('pending')->after('currency');
            }

            // Dirección y envío
            if (!Schema::hasColumn('orders', 'address_json')) {
                $table->json('address_json')->nullable()->after('status');
            }
            if (!Schema::hasColumn('orders', 'shipping_code')) {
                $table->string('shipping_code', 80)->nullable()->after('address_json');
            }
            if (!Schema::hasColumn('orders', 'shipping_name')) {
                $table->string('shipping_name', 120)->nullable()->after('shipping_code');
            }
            if (!Schema::hasColumn('orders', 'shipping_service')) {
                $table->string('shipping_service', 120)->nullable()->after('shipping_name');
            }
            if (!Schema::hasColumn('orders', 'shipping_eta')) {
                $table->string('shipping_eta', 120)->nullable()->after('shipping_service');
            }
            if (!Schema::hasColumn('orders', 'shipping_store_pays')) {
                $table->boolean('shipping_store_pays')->default(false)->after('shipping_eta');
            }
            if (!Schema::hasColumn('orders', 'shipping_carrier_cost')) {
                $table->decimal('shipping_carrier_cost', 10, 2)->default(0)->after('shipping_store_pays');
            }

            // Stripe + Factura
            if (!Schema::hasColumn('orders', 'stripe_session_id')) {
                $table->string('stripe_session_id', 191)->nullable()->after('shipping_carrier_cost');
            }
            if (!Schema::hasColumn('orders', 'stripe_payment_intent')) {
                $table->string('stripe_payment_intent', 191)->nullable()->after('stripe_session_id');
            }
            if (!Schema::hasColumn('orders', 'invoice_id')) {
                $table->string('invoice_id', 191)->nullable()->after('stripe_payment_intent');
            }
        });

        // (Índices opcionales; evita unique para no fallar si ya existen)
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'user_id')) {
                $table->index('user_id');
            }
            if (Schema::hasColumn('orders', 'stripe_session_id')) {
                $table->index('stripe_session_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $cols = [
                'billing_profile_id',
                'customer_name',
                'customer_email',
                'shipping_amount',
                'tax',
                'currency',
                'status',
                'address_json',
                'shipping_code',
                'shipping_name',
                'shipping_service',
                'shipping_eta',
                'shipping_store_pays',
                'shipping_carrier_cost',
                'stripe_session_id',
                'stripe_payment_intent',
                'invoice_id',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
