<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // País del cliente (MEX por defecto)
            if (!Schema::hasColumn('clients', 'pais')) {
                $table->string('pais', 3)
                    ->nullable()
                    ->default('MEX')
                    ->after('telefono');
            }

            // Razón social / nombre registrado en el SAT
            if (!Schema::hasColumn('clients', 'razon_social')) {
                $table->string('razon_social')
                    ->nullable()
                    ->after('nombre');
            }

            // Régimen Fiscal (c_RegimenFiscal del SAT, ej: 601, 612...)
            if (!Schema::hasColumn('clients', 'regimen_fiscal')) {
                $table->string('regimen_fiscal', 3)
                    ->nullable()
                    ->after('rfc');
            }

            // Número exterior
            if (!Schema::hasColumn('clients', 'num_exterior')) {
                $table->string('num_exterior', 20)
                    ->nullable()
                    ->after('calle');
            }

            // Número interior
            if (!Schema::hasColumn('clients', 'num_interior')) {
                $table->string('num_interior', 20)
                    ->nullable()
                    ->after('num_exterior');
            }

            // Municipio
            if (!Schema::hasColumn('clients', 'municipio')) {
                $table->string('municipio', 120)
                    ->nullable()
                    ->after('ciudad');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'pais')) {
                $table->dropColumn('pais');
            }
            if (Schema::hasColumn('clients', 'razon_social')) {
                $table->dropColumn('razon_social');
            }
            if (Schema::hasColumn('clients', 'regimen_fiscal')) {
                $table->dropColumn('regimen_fiscal');
            }
            if (Schema::hasColumn('clients', 'num_exterior')) {
                $table->dropColumn('num_exterior');
            }
            if (Schema::hasColumn('clients', 'num_interior')) {
                $table->dropColumn('num_interior');
            }
            if (Schema::hasColumn('clients', 'municipio')) {
                $table->dropColumn('municipio');
            }
        });
    }
};
