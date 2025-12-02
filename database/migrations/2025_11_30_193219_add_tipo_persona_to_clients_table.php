<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Persona física / moral (para temas fiscales)
            if (!Schema::hasColumn('clients', 'tipo_persona')) {
                $table->enum('tipo_persona', ['fisica', 'moral'])
                    ->nullable()
                    ->after('tipo_cliente'); // o donde te acomode
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'tipo_persona')) {
                $table->dropColumn('tipo_persona');
            }
        });
    }
};
