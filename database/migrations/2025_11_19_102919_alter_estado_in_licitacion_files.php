<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licitacion_files', function (Blueprint $table) {
            // Aseguramos que 'estado' sea un string suficientemente largo
            // y que acepte 'procesado_parcial' sin truncarlo.
            $table->string('estado', 30)
                  ->default('pendiente')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('licitacion_files', function (Blueprint $table) {
            // Si antes lo tenías de 10 o 15 caracteres, pon aquí lo que usabas.
            // Ejemplo (ajusta al valor original):
            $table->string('estado', 15)
                  ->default('pendiente')
                  ->change();
        });
    }
};
