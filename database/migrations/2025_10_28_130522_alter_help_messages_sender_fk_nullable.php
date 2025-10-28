<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('help_messages', function (Blueprint $table) {
            // Si existe la FK vieja, la soltamos. Ajusta el nombre si es distinto:
            if (Schema::hasColumn('help_messages', 'sender_id')) {
                $table->dropForeign(['sender_id']); // asume nombre convencional
            }
        });

        Schema::table('help_messages', function (Blueprint $table) {
            // Asegurar tipo y nulabilidad
            $table->unsignedBigInteger('sender_id')->nullable()->change();

            // Volver a crear la FK con nullOnDelete
            $table->foreign('sender_id')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('help_messages', function (Blueprint $table) {
            $table->dropForeign(['sender_id']);
            // Si quieres revertir a NOT NULL, hazlo aquí (no recomendable):
            // $table->unsignedBigInteger('sender_id')->nullable(false)->change();
            $table->foreign('sender_id')
                ->references('id')->on('users')
                ->restrictOnDelete();
        });
    }
};
