<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('licitaciones', function (Blueprint $t) {
            $t->json('recordatorio_emails')->nullable()->after('fecha_limite_preguntas');
        });
    }

    public function down(): void
    {
        Schema::table('licitaciones', function (Blueprint $t) {
            $t->dropColumn('recordatorio_emails');
        });
    }
};
