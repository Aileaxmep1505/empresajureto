<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    public function up(): void
    {
        // Evita duplicados del índice de Stripe
        if (Schema::hasColumn('orders', 'stripe_session_id')
            && !$this->indexExists('orders', 'orders_stripe_session_id_index')) {

            Schema::table('orders', function (Blueprint $table) {
                $table->index('stripe_session_id', 'orders_stripe_session_id_index');
            });
        }

        // ... (deja aquí cualquier otro cambio de esta migración)
    }

    public function down(): void
    {
        if ($this->indexExists('orders', 'orders_stripe_session_id_index')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('orders_stripe_session_id_index');
            });
        }

        // ... (revierte aquí otros cambios si aplican)
    }
};
