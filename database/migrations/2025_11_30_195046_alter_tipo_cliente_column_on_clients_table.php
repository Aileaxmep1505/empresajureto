<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cambiamos tipo_cliente a VARCHAR(20) NULL
        // para poder usar: gobierno, empresa, particular, otro, etc.
        DB::statement("
            ALTER TABLE clients
            MODIFY tipo_cliente VARCHAR(20) NULL
        ");
    }

    public function down(): void
    {
        // ⚠️ Ajusta esto si tu enum original era distinto
        DB::statement("
            ALTER TABLE clients
            MODIFY tipo_cliente ENUM('gobierno','empresa') NULL
        ");
    }
};
