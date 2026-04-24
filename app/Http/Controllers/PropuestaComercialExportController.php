<?php

namespace App\Http\Controllers;

use App\Models\PropuestaComercial;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PropuestaComercialExportController extends Controller
{
    public function word(PropuestaComercial $propuestaComercial)
    {
        $propuestaComercial->load([
            'items.matches.product',
            'items.productoSeleccionado',
            'aiRun',
        ]);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);

        $section = $phpWord->addSection([
            'marginTop' => 800,
            'marginBottom' => 800,
            'marginLeft' => 900,
            'marginRight' => 900,
        ]);

        $titleStyle = ['bold' => true, 'size' => 16];
        $subtitleStyle = ['bold' => true, 'size' => 12];
        $mutedStyle = ['size' => 9, 'color' => '666666'];
        $textStyle = ['size' => 10];

        $section->addText('Propuesta Comercial', $titleStyle);
        $section->addTextBreak(1);

        $section->addText('Datos generales', $subtitleStyle);
        $section->addText('Título: ' . ($propuestaComercial->titulo ?: 'Sin título'), $textStyle);
        $section->addText('Folio: ' . ($propuestaComercial->folio ?: '—'), $textStyle);
        $section->addText('Cliente: ' . ($propuestaComercial->cliente ?: '—'), $textStyle);
        $section->addText('Estatus: ' . strtoupper($propuestaComercial->status), $textStyle);
        $section->addText('Creada: ' . optional($propuestaComercial->created_at)->format('d/m/Y H:i'), $textStyle);
        $section->addTextBreak(1);

        $meta = $propuestaComercial->meta ?? [];

        $section->addText('Resumen del documento', $subtitleStyle);
        $section->addText($meta['resumen'] ?? 'Sin resumen', $textStyle);
        $section->addTextBreak(1);

        if (!empty($meta['anexos'])) {
            $section->addText('Anexos', $subtitleStyle);
            foreach ($meta['anexos'] as $anexo) {
                if (is_array($anexo)) {
                    $section->addListItem(
                        ($anexo['nombre'] ?? 'Anexo') . ' - ' . ($anexo['descripcion'] ?? ''),
                        0,
                        $textStyle
                    );
                } else {
                    $section->addListItem((string) $anexo, 0, $textStyle);
                }
            }
            $section->addTextBreak(1);
        }

        if (!empty($meta['fechas_clave'])) {
            $section->addText('Fechas clave', $subtitleStyle);
            foreach ($meta['fechas_clave'] as $fecha) {
                $texto = ($fecha['tipo'] ?? 'Fecha')
                    . ' | ' . ($fecha['fecha'] ?? '—')
                    . ' | ' . ($fecha['hora'] ?? '—')
                    . ' | ' . ($fecha['descripcion'] ?? '—');
                $section->addListItem($texto, 0, $textStyle);
            }
            $section->addTextBreak(1);
        }

        if (!empty($meta['penalizaciones'])) {
            $section->addText('Penalizaciones', $subtitleStyle);
            foreach ($meta['penalizaciones'] as $pena) {
                $texto = ($pena['descripcion'] ?? 'Penalización')
                    . ' | ' . ($pena['penalización'] ?? '—');
                $section->addListItem($texto, 0, $textStyle);
            }
            $section->addTextBreak(1);
        }

        $section->addText('Parámetros comerciales', $subtitleStyle);
        $section->addText('Utilidad: ' . number_format((float) $propuestaComercial->porcentaje_utilidad, 2) . '%', $textStyle);
        $section->addText('Descuento: ' . number_format((float) $propuestaComercial->porcentaje_descuento, 2) . '%', $textStyle);
        $section->addText('Impuesto: ' . number_format((float) $propuestaComercial->porcentaje_impuesto, 2) . '%', $textStyle);
        $section->addTextBreak(1);

        $section->addText('Renglones', $subtitleStyle);

        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => 'DDDDDD',
            'cellMargin' => 80,
        ];

        $headerCellStyle = ['bgColor' => 'F3F4F6'];
        $headerTextStyle = ['bold' => true, 'size' => 10];

        $phpWord->addTableStyle('PropuestaItemsTable', $tableStyle);
        $table = $section->addTable('PropuestaItemsTable');

        $table->addRow();
        $table->addCell(900, $headerCellStyle)->addText('#', $headerTextStyle);
        $table->addCell(1700, $headerCellStyle)->addText('Partida', $headerTextStyle);
        $table->addCell(4200, $headerCellStyle)->addText('Descripción solicitada', $headerTextStyle);
        $table->addCell(1500, $headerCellStyle)->addText('Unidad', $headerTextStyle);
        $table->addCell(1300, $headerCellStyle)->addText('Cant.', $headerTextStyle);
        $table->addCell(2400, $headerCellStyle)->addText('Producto seleccionado', $headerTextStyle);
        $table->addCell(1400, $headerCellStyle)->addText('Costo', $headerTextStyle);
        $table->addCell(1400, $headerCellStyle)->addText('Precio', $headerTextStyle);
        $table->addCell(1600, $headerCellStyle)->addText('Subtotal', $headerTextStyle);

        foreach ($propuestaComercial->items as $item) {
            $table->addRow();

            $table->addCell(900)->addText((string) $item->sort, $textStyle);
            $table->addCell(1700)->addText(
                'P: ' . ($item->partida_numero ?: '—') . ' / S: ' . ($item->subpartida_numero ?: '—'),
                $textStyle
            );
            $table->addCell(4200)->addText($item->descripcion_original ?: '—', $textStyle);
            $table->addCell(1500)->addText($item->unidad_solicitada ?: '—', $textStyle);
            $table->addCell(1300)->addText((string) ($item->cantidad_cotizada ?: $item->cantidad_maxima ?: $item->cantidad_minima ?: '—'), $textStyle);
            $table->addCell(2400)->addText($item->productoSeleccionado->name ?? 'Sin seleccionar', $textStyle);
            $table->addCell(1400)->addText('$' . number_format((float) $item->costo_unitario, 2), $textStyle);
            $table->addCell(1400)->addText('$' . number_format((float) $item->precio_unitario, 2), $textStyle);
            $table->addCell(1600)->addText('$' . number_format((float) $item->subtotal, 2), $textStyle);
        }

        $section->addTextBreak(1);
        $section->addText('Totales', $subtitleStyle);
        $section->addText('Subtotal: $' . number_format((float) $propuestaComercial->subtotal, 2), $textStyle);
        $section->addText('Descuento: $' . number_format((float) $propuestaComercial->descuento_total, 2), $textStyle);
        $section->addText('Impuesto: $' . number_format((float) $propuestaComercial->impuesto_total, 2), $textStyle);
        $section->addText('Total: $' . number_format((float) $propuestaComercial->total, 2), ['bold' => true, 'size' => 11]);

        $fileName = 'propuesta_comercial_' . $propuestaComercial->id . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'pc_word_');
        WordIOFactory::createWriter($phpWord, 'Word2007')->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public function excel(PropuestaComercial $propuestaComercial)
    {
        $propuestaComercial->load([
            'items.matches.product',
            'items.productoSeleccionado',
            'aiRun',
        ]);

        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Propuesta');

        $sheet->setCellValue('A1', 'Propuesta Comercial');
        $sheet->setCellValue('A2', 'Título');
        $sheet->setCellValue('B2', $propuestaComercial->titulo ?: 'Sin título');
        $sheet->setCellValue('A3', 'Folio');
        $sheet->setCellValue('B3', $propuestaComercial->folio ?: '—');
        $sheet->setCellValue('A4', 'Cliente');
        $sheet->setCellValue('B4', $propuestaComercial->cliente ?: '—');
        $sheet->setCellValue('A5', 'Estatus');
        $sheet->setCellValue('B5', strtoupper($propuestaComercial->status));

        $sheet->setCellValue('A7', '#');
        $sheet->setCellValue('B7', 'Partida');
        $sheet->setCellValue('C7', 'Subpartida');
        $sheet->setCellValue('D7', 'Descripción solicitada');
        $sheet->setCellValue('E7', 'Unidad');
        $sheet->setCellValue('F7', 'Cantidad mínima');
        $sheet->setCellValue('G7', 'Cantidad máxima');
        $sheet->setCellValue('H7', 'Cantidad cotizada');
        $sheet->setCellValue('I7', 'Producto seleccionado');
        $sheet->setCellValue('J7', 'SKU');
        $sheet->setCellValue('K7', 'Score match');
        $sheet->setCellValue('L7', 'Costo unitario');
        $sheet->setCellValue('M7', 'Precio unitario');
        $sheet->setCellValue('N7', 'Subtotal');

        $row = 8;
        foreach ($propuestaComercial->items as $item) {
            $sheet->setCellValue("A{$row}", $item->sort);
            $sheet->setCellValue("B{$row}", $item->partida_numero);
            $sheet->setCellValue("C{$row}", $item->subpartida_numero);
            $sheet->setCellValue("D{$row}", $item->descripcion_original);
            $sheet->setCellValue("E{$row}", $item->unidad_solicitada);
            $sheet->setCellValue("F{$row}", (float) $item->cantidad_minima);
            $sheet->setCellValue("G{$row}", (float) $item->cantidad_maxima);
            $sheet->setCellValue("H{$row}", (float) $item->cantidad_cotizada);
            $sheet->setCellValue("I{$row}", $item->productoSeleccionado->name ?? 'Sin seleccionar');
            $sheet->setCellValue("J{$row}", $item->productoSeleccionado->sku ?? '');
            $sheet->setCellValue("K{$row}", (float) $item->match_score);
            $sheet->setCellValue("L{$row}", (float) $item->costo_unitario);
            $sheet->setCellValue("M{$row}", (float) $item->precio_unitario);
            $sheet->setCellValue("N{$row}", (float) $item->subtotal);
            $row++;
        }

        $row += 1;
        $sheet->setCellValue("L{$row}", 'Subtotal');
        $sheet->setCellValue("M{$row}", (float) $propuestaComercial->subtotal);
        $row++;
        $sheet->setCellValue("L{$row}", 'Descuento');
        $sheet->setCellValue("M{$row}", (float) $propuestaComercial->descuento_total);
        $row++;
        $sheet->setCellValue("L{$row}", 'Impuesto');
        $sheet->setCellValue("M{$row}", (float) $propuestaComercial->impuesto_total);
        $row++;
        $sheet->setCellValue("L{$row}", 'Total');
        $sheet->setCellValue("M{$row}", (float) $propuestaComercial->total);

        foreach (range('A', 'N') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A7:N7')->getFont()->setBold(true);

        $fileName = 'propuesta_comercial_' . $propuestaComercial->id . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'pc_xlsx_');

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}