<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Teléfono para WhatsApp (E.164 recomendado: +521XXXXXXXXXX)
            $table->string('phone', 30)->nullable()->after('email');

            // opcional: para búsquedas rápidas / evitar duplicados (si te sirve)
            // $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // si agregaste index, primero dropIndex:
            // $table->dropIndex(['phone']);
            $table->dropColumn('phone');
        });
    }
};
