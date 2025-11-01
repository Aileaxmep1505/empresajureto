<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // Principales (como en tu screenshot)
            ['name'=>'Artículos de Papelería',        'slug'=>'papeleria',         'is_primary'=>true, 'position'=>1],
            ['name'=>'Hojas para imprimir',           'slug'=>'hojas',             'is_primary'=>true, 'position'=>2],
            ['name'=>'Hardware',                       'slug'=>'hardware',          'is_primary'=>true, 'position'=>3],
            ['name'=>'Computadoras Laptop',            'slug'=>'laptops',           'is_primary'=>true, 'position'=>4],
            ['name'=>'Equipo de Cómputo para Oficina', 'slug'=>'equipo-de-computo', 'is_primary'=>true, 'position'=>5],
            ['name'=>'Computadoras de Escritorio',     'slug'=>'desktops',          'is_primary'=>true, 'position'=>6],
            ['name'=>'Monitores',                      'slug'=>'monitores',         'is_primary'=>true, 'position'=>7],
            ['name'=>'Impresoras Brother',             'slug'=>'impresoras-brother','is_primary'=>true, 'position'=>8],
            ['name'=>'Impresoras Epson',               'slug'=>'impresoras-epson',  'is_primary'=>true, 'position'=>9],
            ['name'=>'Tienda Oficial HP',              'slug'=>'hp',                'is_primary'=>true, 'position'=>10],
            ['name'=>'Productos para Oficina',         'slug'=>'oficina',           'is_primary'=>true, 'position'=>11],
            ['name'=>'Muebles para Oficina',           'slug'=>'muebles',           'is_primary'=>true, 'position'=>12],
        ];

        foreach ($data as $row) {
            Category::updateOrCreate(['slug'=>$row['slug']], $row);
        }
    }
}
