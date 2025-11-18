<?php

namespace App\Http\Controllers;

use App\Models\Licitacion;
use Barryvdh\DomPDF\Facade\Pdf;

class LicitacionExportController extends Controller
{
    public function exportPreguntasPdf(Licitacion $licitacion)
    {
        $preguntas = $licitacion->preguntas()->orderBy('fecha_pregunta')->get();

        $pdf = Pdf::loadView('licitaciones.preguntas_pdf', [
            'licitacion' => $licitacion,
            'preguntas'  => $preguntas,
        ]);

        return $pdf->download('preguntas_licitacion_'.$licitacion->id.'.pdf');
    }
}
