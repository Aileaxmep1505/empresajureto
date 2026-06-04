<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE propuesta_aclaracion_preguntas
            MODIFY producto_solicitado TEXT NULL,
            MODIFY producto_sugerido TEXT NULL,
            MODIFY pregunta_generada TEXT NULL,
            MODIFY justificacion TEXT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE propuesta_aclaracion_preguntas
            MODIFY producto_solicitado VARCHAR(255) NULL,
            MODIFY producto_sugerido VARCHAR(255) NULL,
            MODIFY pregunta_generada TEXT NULL,
            MODIFY justificacion TEXT NULL
        ");
    }
};