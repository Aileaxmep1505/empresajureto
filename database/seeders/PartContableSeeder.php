<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Company;
use App\Models\DocumentSection;
use App\Models\DocumentSubtype;

class PartContableSeeder extends Seeder
{
    public function run(): void
    {
        // Empresas de ejemplo
        $companies = [
            'Empresa A', 'Empresa B', 'Empresa C',
            'Empresa D', 'Empresa E', 'Empresa F',
        ];

        foreach ($companies as $c) {
            Company::firstOrCreate(
                ['slug' => Str::slug($c)],
                ['name' => $c, 'rfc' => null]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SECCIONES PRINCIPALES
        | (Coinciden con los tabs de la vista)
        |--------------------------------------------------------------------------
        */
        $secAnnual   = DocumentSection::firstOrCreate(
            ['key' => 'declaracion_anual'],
            ['name' => 'Declaración Anual']
        );

        $secMonthly  = DocumentSection::firstOrCreate(
            ['key' => 'declaracion_mensual'],
            ['name' => 'Declaración Mensual']
        );

        $secConst    = DocumentSection::firstOrCreate(
            ['key' => 'constancias'],
            ['name' => 'Constancias / Opiniones']
        );

        $secFin      = DocumentSection::firstOrCreate(
            ['key' => 'estados_financieros'],
            ['name' => 'Estados Financieros']
        );

        /*
        |--------------------------------------------------------------------------
        | SUBTIPOS (SUB-TABS) POR SECCIÓN
        |--------------------------------------------------------------------------
        */

        // Declaración Anual
        $annualSubtypes = [
            ['key' => 'acuse_anual',       'name' => 'Acuse anual'],
            ['key' => 'pago_anual',        'name' => 'Pago anual'],
            ['key' => 'declaracion_anual', 'name' => 'Declaración anual'],
        ];

        foreach ($annualSubtypes as $s) {
            DocumentSubtype::firstOrCreate(
                ['section_id' => $secAnnual->id, 'key' => $s['key']],
                ['name' => $s['name']]
            );
        }

        // Declaración Mensual
        $monthlySubtypes = [
            ['key' => 'acuse_mensual',       'name' => 'Acuse mensual'],
            ['key' => 'pago_mensual',        'name' => 'Pago mensual'],
            ['key' => 'declaracion_mensual', 'name' => 'Declaración mensual'],
        ];

        foreach ($monthlySubtypes as $s) {
            DocumentSubtype::firstOrCreate(
                ['section_id' => $secMonthly->id, 'key' => $s['key']],
                ['name' => $s['name']]
            );
        }

        // Constancias / Opiniones
        $constSubtypes = [
            ['key' => 'csf',            'name' => 'Constancia de situación fiscal'],
            ['key' => 'opinion_nl',     'name' => 'Opinión estatal Nuevo León'],
            ['key' => 'opinion_edomex', 'name' => 'Opinión estatal Estado de México'],
            ['key' => '32d_sat',        'name' => '32-D SAT'],
            ['key' => 'infonavit',      'name' => 'INFONAVIT'],
            ['key' => 'opinion_imss',   'name' => 'Opinión IMSS'],
        ];

        foreach ($constSubtypes as $s) {
            DocumentSubtype::firstOrCreate(
                ['section_id' => $secConst->id, 'key' => $s['key']],
                ['name' => $s['name']]
            );
        }

        // Estados Financieros
        $finSubtypes = [
            ['key' => 'balance_general',   'name' => 'Balance general'],
            ['key' => 'estado_resultados', 'name' => 'Estado de resultados'],
        ];

        foreach ($finSubtypes as $s) {
            DocumentSubtype::firstOrCreate(
                ['section_id' => $secFin->id, 'key' => $s['key']],
                ['name' => $s['name']]
            );
        }
    }
}
