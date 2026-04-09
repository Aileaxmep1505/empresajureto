<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProjectBoardController extends Controller
{
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | DEMO BASE
        |--------------------------------------------------------------------------
        | Esta primera versión va lista para funcionar aunque todavía no tengas
        | tablas creadas. Después te doy la migración/modelos para conectarlo
        | a base de datos real.
        |--------------------------------------------------------------------------
        */

        $columns = collect([
            [
                'id'    => 1,
                'name'  => 'Análisis de Bases',
                'color' => 'blue',
                'projects' => [
                    [
                        'id' => 101,
                        'name' => 'LA MARINA PAPE',
                        'priority' => 'Normal',
                        'start_date' => '2026-04-07',
                        'assigned' => 'S',
                        'labels' => [],
                        'starred' => false,
                    ],
                    [
                        'id' => 102,
                        'name' => 'ADF-009-2026 GUANAJUATO',
                        'priority' => 'Normal',
                        'start_date' => '2026-03-31',
                        'assigned' => 'G',
                        'labels' => [],
                        'starred' => false,
                    ],
                    [
                        'id' => 103,
                        'name' => 'analisis partidas gto',
                        'priority' => 'Normal',
                        'start_date' => '2026-03-25',
                        'assigned' => 'A',
                        'labels' => [],
                        'starred' => false,
                    ],
                    [
                        'id' => 104,
                        'name' => 'propuesta cancún',
                        'priority' => 'Alta',
                        'start_date' => '2026-03-20',
                        'assigned' => 'J',
                        'labels' => ['Urgente'],
                        'starred' => true,
                    ],
                    [
                        'id' => 105,
                        'name' => 'licitación material escolar',
                        'priority' => 'Baja',
                        'start_date' => '2026-03-15',
                        'assigned' => 'M',
                        'labels' => [],
                        'starred' => false,
                    ],
                    [
                        'id' => 106,
                        'name' => 'seguimiento proveedor norte',
                        'priority' => 'Normal',
                        'start_date' => '2026-03-10',
                        'assigned' => 'R',
                        'labels' => [],
                        'starred' => false,
                    ],
                ],
            ],
            [
                'id'    => 2,
                'name'  => 'Revisión',
                'color' => 'orange',
                'projects' => [],
            ],
            [
                'id'    => 3,
                'name'  => 'Participa',
                'color' => 'green',
                'projects' => [],
            ],
            [
                'id'    => 4,
                'name'  => 'No participa',
                'color' => 'red',
                'projects' => [],
            ],
            [
                'id'    => 5,
                'name'  => 'Ganado',
                'color' => 'purple',
                'projects' => [
                    [
                        'id' => 201,
                        'name' => 'suministro oficina central',
                        'priority' => 'Normal',
                        'start_date' => '2026-03-01',
                        'assigned' => 'L',
                        'labels' => ['Ganado'],
                        'starred' => true,
                    ],
                ],
            ],
            [
                'id'    => 6,
                'name'  => 'Perdido',
                'color' => 'gray',
                'projects' => [],
            ],
            [
                'id'    => 7,
                'name'  => 'Desierta',
                'color' => 'rose',
                'projects' => [],
            ],
        ])->map(function ($column) {
            $column['count'] = count($column['projects']);
            return $column;
        });

        $openColumnId = (int) $request->get('open', 1);

        return view('projects.index', [
            'columns'      => $columns,
            'openColumnId' => $openColumnId,
        ]);
    }
}