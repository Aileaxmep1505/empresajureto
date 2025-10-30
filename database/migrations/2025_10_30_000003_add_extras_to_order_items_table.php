<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $t) {
            if (!Schema::hasColumn('order_items','image_url')) $t->string('image_url')->nullable()->after('sku');
            if (!Schema::hasColumn('order_items','meta'))      $t->json('meta')->nullable()->after('image_url'); // variantes, notas, etc.
            if (!Schema::hasColumn('order_items','currency'))  $t->string('currency',3)->default('MXN')->after('amount');
            if (!Schema::hasColumn('order_items','tax_rate'))  $t->decimal('tax_rate',5,4)->nullable()->after('currency'); // ej. 0.1600
            if (!Schema::hasColumn('order_items','discount'))  $t->decimal('discount',12,2)->default(0)->after('tax_rate');
            // índice por si no lo tenías
            $t->index('order_id', 'order_items_order_id_idx');
        });
    }

    public function down(): void
    {
        // opcional: no dropear para no perder datos
    }
};
