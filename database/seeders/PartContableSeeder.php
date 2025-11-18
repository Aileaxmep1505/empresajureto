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
        $companies = [
            'Empresa A', 'Empresa B', 'Empresa C',
            'Empresa D', 'Empresa E', 'Empresa F'
        ];

        foreach ($companies as $c) {
            Company::firstOrCreate(
                ['slug' => Str::slug($c)],
                ['name' => $c, 'rfc' => null]
            );
        }

        // Secciones (evita duplicados con firstOrCreate)
        $secAnnual = DocumentSection::firstOrCreate(['key' => 'declaracion_anual'], ['name' => 'Declaración Anual']);
        $secMonthly = DocumentSection::firstOrCreate(['key' => 'declaracion_mensual'], ['name' => 'Declaración Mensual']);
        $secAcuse = DocumentSection::firstOrCreate(['key' => 'acuse'], ['name' => 'Acuse']);
        $secPayments = DocumentSection::firstOrCreate(['key' => 'pagos'], ['name' => 'Pagos']);

        // Subtipos para la sección mensual (no duplican)
        $subtypes = [
            ['key'=>'32d_sat','name'=>'32D SAT'],
            ['key'=>'opinion_imss','name'=>'Opinión IMSS'],
            ['key'=>'infonavit','name'=>'Infonavit'],
            ['key'=>'contribucion_estatal','name'=>'Contribución Estatal'],
            ['key'=>'balance_general','name'=>'Balance General'],
            ['key'=>'constancia_situacion_fiscal','name'=>'Constancia de Situación Fiscal'],
        ];

        foreach ($subtypes as $s) {
            DocumentSubtype::firstOrCreate(
                ['section_id' => $secMonthly->id, 'key' => $s['key']],
                ['name' => $s['name']]
            );
        }
    }
}
