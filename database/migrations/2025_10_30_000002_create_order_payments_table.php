<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();

            $t->string('provider')->default('stripe');     // stripe|paypal|...
            $t->string('status')->nullable();              // succeeded|failed|refunded...
            $t->decimal('amount', 12, 2)->default(0);
            $t->string('currency', 3)->default('MXN');

            $t->string('intent_id')->nullable();           // pi_xxx
            $t->string('charge_id')->nullable();           // ch_xxx
            $t->string('receipt_url')->nullable();
            $t->json('raw')->nullable();                   // payload completo del PSP

            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
