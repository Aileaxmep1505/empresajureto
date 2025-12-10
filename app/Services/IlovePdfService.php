<?php

namespace App\Services;

use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class IlovePdfService
{
    /**
     * Convierte un PDF a DOCX (texto plano) en una ruta temporal
     * y devuelve la ruta ABSOLUTA del archivo generado.
     *
     * @param  string  $pdfFullPath  Ruta ABSOLUTA del PDF recortado.
     * @param  string  $baseName     Nombre base para el archivo (sin extensión).
     * @return string                Ruta ABSOLUTA del .docx generado.
     */
    public function pdfToWord(string $pdfFullPath, string $baseName): string
    {
        $outputDir = $this->ensureTmpDir();
        $outputPath = $outputDir . DIRECTORY_SEPARATOR . $baseName . '.docx';

        // Extraer texto del PDF
        $parser = new PdfParser();
        $pdf    = $parser->parseFile($pdfFullPath);
        $text   = $pdf->getText();

        // Crear documento Word
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $lines = preg_split("/\r\n|\n|\r/", $text);
        foreach ($lines as $line) {
            $section->addText($line);
        }

        $writer = WordIOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($outputPath);

        return $outputPath;
    }

    /**
     * Convierte un PDF a XLSX (una línea por fila en la columna A)
     * y devuelve la ruta ABSOLUTA del archivo generado.
     *
     * @param  string  $pdfFullPath  Ruta ABSOLUTA del PDF recortado.
     * @param  string  $baseName     Nombre base para el archivo (sin extensión).
     * @return string                Ruta ABSOLUTA del .xlsx generado.
     */
    public function pdfToExcel(string $pdfFullPath, string $baseName): string
    {
        $outputDir  = $this->ensureTmpDir();
        $outputPath = $outputDir . DIRECTORY_SEPARATOR . $baseName . '.xlsx';

        // Extraer texto del PDF
        $parser = new PdfParser();
        $pdf    = $parser->parseFile($pdfFullPath);
        $text   = $pdf->getText();

        // Crear hoja de cálculo
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $lines = preg_split("/\r\n|\n|\r/", $text);
        $row   = 1;

        foreach ($lines as $line) {
            $sheet->setCellValue('A' . $row, $line);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($outputPath);

        return $outputPath;
    }

    /**
     * Asegura que exista storage/app/tmp y devuelve su ruta.
     */
    protected function ensureTmpDir(): string
    {
        $dir = storage_path('app/tmp');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return realpath($dir) ?: $dir;
    }
}
