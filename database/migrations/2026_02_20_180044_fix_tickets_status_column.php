<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Normaliza valores viejos si existían (por si venías de otro flujo)
        // Ajusta/borra los que no apliquen según tu historial.
        DB::table('tickets')->where('status', 'revision')->update(['status' => 'revision']);
        DB::table('tickets')->where('status', 'proceso')->update(['status' => 'progreso']);
        DB::table('tickets')->where('status', 'finalizado')->update(['status' => 'completado']);
        DB::table('tickets')->where('status', 'cerrado')->update(['status' => 'completado']);
        DB::table('tickets')->whereNull('status')->update(['status' => 'pendiente']);

        // 2) Cambiar columna a VARCHAR(20) default 'pendiente'
        // Usamos SQL directo para no depender de doctrine/dbal y evitar problemas con ENUM.
        DB::statement("ALTER TABLE `tickets` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT 'pendiente'");

        // 3) (Opcional) Índice para filtros por status (si quieres)
        // Schema::table('tickets', function (Blueprint $table) {
        //     $table->index('status');
        // });
    }

    public function down(): void
    {
        // Reversa: dejarlo como VARCHAR sin default (o como estaba).
        // (Si quieres volver a ENUM, dilo y te lo dejo exacto.)
        DB::statement("ALTER TABLE `tickets` MODIFY `status` VARCHAR(20) NULL");
    }
};