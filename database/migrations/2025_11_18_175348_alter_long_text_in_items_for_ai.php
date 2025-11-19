<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items_originales', function (Blueprint $table) {
            // Pasar de string(255) a text para permitir descripciones largas
            $table->text('descripcion_bien')->change();
            $table->text('especificaciones')->nullable()->change();
        });

        // Si tu tabla se llama distinto, ajusta el nombre aquí
        Schema::table('items_globales', function (Blueprint $table) {
            $table->text('descripcion_global')->change();
            $table->text('especificaciones_global')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Volver a string(255) si hicieras rollback (opcional, pero lo dejo definido)
        Schema::table('items_originales', function (Blueprint $table) {
            $table->string('descripcion_bien', 255)->change();
            $table->string('especificaciones', 255)->nullable()->change();
        });

        Schema::table('items_globales', function (Blueprint $table) {
            $table->string('descripcion_global', 255)->change();
            $table->string('especificaciones_global', 255)->nullable()->change();
        });
    }
};
