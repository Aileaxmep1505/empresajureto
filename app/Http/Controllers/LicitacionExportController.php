<?php

namespace App\Http\Controllers;

use App\Models\Licitacion;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class LicitacionExportController extends Controller
{
    public function exportPreguntasPdf(Licitacion $licitacion)
    {
        $licitacion->load('preguntas.usuario');

        $pdf = Pdf::loadView('licitaciones.exports.preguntas_pdf', [
            'licitacion' => $licitacion,
            'preguntas'  => $licitacion->preguntas->sortBy('id'),
        ])->setPaper('letter', 'portrait');

        return $pdf->download("preguntas_licitacion_{$licitacion->id}.pdf");
    }

    public function exportPreguntasWord(Licitacion $licitacion)
    {
        $licitacion->load('preguntas.usuario');

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addText(
            "Preguntas de la licitación: {$licitacion->titulo}",
            ['bold' => true, 'size' => 14]
        );
        $section->addTextBreak(1);

        foreach ($licitacion->preguntas->sortBy('id') as $i => $p) {
            $n = $i + 1;

            $section->addText("{$n}. {$p->texto_pregunta}", ['size' => 12]);
            $section->addText(
                "Referencia a bases: " . ($p->notas_internas ?: '—'),
                ['italic' => true, 'size' => 10, 'color' => '666666']
            );

            $meta = trim(
                ($p->usuario->name ?? 'Usuario') .
                ' | ' .
                optional($p->fecha_pregunta)->format('d/m/Y H:i')
            );

            $section->addText($meta, ['size' => 9, 'color' => '999999']);
            $section->addTextBreak(1);
        }

        $tmp = storage_path("app/tmp_preguntas_{$licitacion->id}.docx");
        if (!is_dir(dirname($tmp))) @mkdir(dirname($tmp), 0775, true);

        IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);

        return response()
            ->download($tmp, "preguntas_licitacion_{$licitacion->id}.docx")
            ->deleteFileAfterSend(true);
    }
}
