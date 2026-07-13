<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'stripe_refund_id')) {
                $table->string('stripe_refund_id')->nullable()->after('stripe_payment_intent');
            }

            if (!Schema::hasColumn('orders', 'stripe_refund_status')) {
                $table->string('stripe_refund_status')->nullable()->after('stripe_refund_id');
            }

            if (!Schema::hasColumn('orders', 'refunded_amount')) {
                $table->decimal('refunded_amount', 12, 2)->default(0)->after('total');
            }

            if (!Schema::hasColumn('orders', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('refunded_amount');
            }

            if (!Schema::hasColumn('orders', 'stripe_refund_raw')) {
                $table->json('stripe_refund_raw')->nullable()->after('refunded_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'stripe_refund_raw')) {
                $table->dropColumn('stripe_refund_raw');
            }

            if (Schema::hasColumn('orders', 'refunded_at')) {
                $table->dropColumn('refunded_at');
            }

            if (Schema::hasColumn('orders', 'refunded_amount')) {
                $table->dropColumn('refunded_amount');
            }

            if (Schema::hasColumn('orders', 'stripe_refund_status')) {
                $table->dropColumn('stripe_refund_status');
            }

            if (Schema::hasColumn('orders', 'stripe_refund_id')) {
                $table->dropColumn('stripe_refund_id');
            }
        });
    }
};