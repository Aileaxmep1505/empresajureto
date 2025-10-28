<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Volver status/priority "flexibles" para poder limpiar datos
        DB::statement("ALTER TABLE help_tickets MODIFY status   VARCHAR(32) NOT NULL DEFAULT 'open'");
        DB::statement("ALTER TABLE help_tickets MODIFY priority VARCHAR(32) NOT NULL DEFAULT 'normal'");
        DB::statement("ALTER TABLE help_tickets MODIFY resolved_by_id BIGINT UNSIGNED NULL");

        // 2) Normalizar valores EXISTENTES que no encajen al nuevo ENUM
        // Mapea sinónimos comunes en español; todo lo desconocido -> 'open'
        DB::statement("
            UPDATE help_tickets
            SET status = CASE
                WHEN status IN ('open','waiting_user','waiting_agent','escalated','closed') THEN status
                WHEN status IN ('nuevo','nueva','abierto','aperto','inicio','created','creado') THEN 'open'
                WHEN status IN ('espera_usuario','pendiente_usuario','user_wait','await_user') THEN 'waiting_user'
                WHEN status IN ('espera_agente','pendiente_agente','agent_wait','await_agent','asignado') THEN 'waiting_agent'
                WHEN status IN ('escalado','derivado','nivel2','l2','l3') THEN 'escalated'
                WHEN status IN ('cerrado','finalizado','closed_ok','done') THEN 'closed'
                ELSE 'open'
            END
        ");

        // Priority: mapea y limpia; lo desconocido -> 'normal'
        DB::statement("
            UPDATE help_tickets
            SET priority = CASE
                WHEN priority IN ('low','normal','high','urgent') THEN priority
                WHEN priority IN ('baja') THEN 'low'
                WHEN priority IN ('alta') THEN 'high'
                WHEN priority IN ('urgente','inmediata','critical','critico','crítico') THEN 'urgent'
                ELSE 'normal'
            END
        ");

        // 3) Volver a ENUM ya con datos limpios
        DB::statement("
            ALTER TABLE help_tickets
            MODIFY status ENUM('open','waiting_user','waiting_agent','escalated','closed')
            NOT NULL DEFAULT 'open'
        ");

        DB::statement("
            ALTER TABLE help_tickets
            MODIFY priority ENUM('low','normal','high','urgent')
            NOT NULL DEFAULT 'normal'
        ");
    }

    public function down(): void
    {
        // Reversión simple
        DB::statement("ALTER TABLE help_tickets MODIFY status   VARCHAR(32) NOT NULL DEFAULT 'open'");
        DB::statement("ALTER TABLE help_tickets MODIFY priority VARCHAR(32) NOT NULL DEFAULT 'normal'");
        // (no tocamos resolved_by_id en down)
    }
};
