<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LicitacionPdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use App\Services\IlovePdfService;

class LicitacionPdfController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    /**
     * Listado de PDFs.
     */
    public function index(Request $request)
    {
        $query = LicitacionPdf::query()->latest();

        if ($request->filled('licitacion_id')) {
            $query->where('licitacion_id', $request->integer('licitacion_id'));
        }

        if ($request->filled('requisicion_id')) {
            $query->where('requisicion_id', $request->integer('requisicion_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $pdfs = $query->paginate(20);

        return view('admin.licitacion_pdfs.index', compact('pdfs'));
    }

    /**
     * Formulario para subir PDF.
     */
    public function create()
    {
        return view('admin.licitacion_pdfs.create');
    }

    /**
     * Guarda el PDF y manda al paso de separar.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'licitacion_id'  => ['nullable', 'integer'],
            'requisicion_id' => ['nullable', 'integer'],
            'pdf'            => ['required', 'file', 'mimes:pdf', 'max:30720'],
        ]);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $data['pdf'];

        // Guarda archivo
        $path = $file->store('licitaciones/pdfs');

        // Contar páginas
        $pageCount = 0;
        try {
            $fpdi = new Fpdi();
            $pageCount = $fpdi->setSourceFile(Storage::path($path));
        } catch (\Throwable $e) {
            $pageCount = 0;
        }

        $pdf = LicitacionPdf::create([
            'licitacion_id'     => $data['licitacion_id'] ?? null,
            'requisicion_id'    => $data['requisicion_id'] ?? null,
            'original_filename' => $file->getClientOriginalName(),
            'original_path'     => $path,
            'pages_count'       => $pageCount,
            'status'            => 'uploaded',
            'meta'              => [],
        ]);

        return redirect()
            ->route('admin.licitacion-pdfs.show', $pdf)
            ->with('status', 'PDF subido correctamente. Ahora define los rangos a recortar.');
    }

    /**
     * Vista de separación de rangos.
     */
    public function show(LicitacionPdf $licitacionPdf)
    {
        // Aseguramos pages_count
        $pageCount = $licitacionPdf->pages_count ?: 0;
        if ($pageCount === 0 && $licitacionPdf->original_path) {
            try {
                $fpdi = new Fpdi();
                $pageCount = $fpdi->setSourceFile(Storage::path($licitacionPdf->original_path));
                $licitacionPdf->update(['pages_count' => $pageCount]);
            } catch (\Throwable $e) {
                $pageCount = 1;
            }
        }
        if ($pageCount <= 0) {
            $pageCount = 1;
        }

        // Splits guardados en meta
        $meta = $licitacionPdf->meta ?? [];
        $splitsArray = $meta['splits'] ?? [];

        $splits = collect($splitsArray)
            ->values()
            ->map(function ($split, $idx) {
                $split['index'] = $idx;
                return $split;
            })
            ->sortByDesc('created_at')
            ->values();

        return view('admin.licitacion_pdfs.show', [
            'pdf'       => $licitacionPdf,
            'pageCount' => $pageCount,
            'splits'    => $splits,
        ]);
    }

    /**
     * Vista previa del PDF en el iframe.
     */
    public function preview(LicitacionPdf $licitacionPdf)
    {
        if (!$licitacionPdf->original_path) {
            abort(404);
        }

        $filePath = Storage::path($licitacionPdf->original_path);

        if (!file_exists($filePath)) {
            abort(404);
        }

        return response()->file($filePath, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$licitacionPdf->original_filename.'"',
        ]);
    }

    /**
     * Generar un PDF recortado y guardarlo en disco.
     * No se devuelve directo: se guarda y se agrega al meta->splits.
     */
    public function split(Request $request, LicitacionPdf $licitacionPdf)
    {
        $data = $request->validate([
            'from' => ['required', 'integer', 'min:1'],
            'to'   => ['required', 'integer', 'min:1'],
        ]);

        $from = (int) $data['from'];
        $to   = (int) $data['to'];

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        if (!$licitacionPdf->original_path) {
            return back()->with('status', 'No se encontró el archivo original.');
        }

        $filePath = Storage::path($licitacionPdf->original_path);
        if (!file_exists($filePath)) {
            return back()->with('status', 'El archivo físico no existe en el servidor.');
        }

        // Crear nuevo PDF recortado localmente con FPDI
        $pdf = new Fpdi();
        $totalPages = $pdf->setSourceFile($filePath);

        $from = max(1, $from);
        $to   = min($totalPages, $to);

        if ($from > $to) {
            return back()->with('status', 'Rango de páginas inválido.');
        }

        for ($pageNo = $from; $pageNo <= $to; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);
        }

        $baseName = pathinfo($licitacionPdf->original_filename, PATHINFO_FILENAME);
        $downloadName = sprintf('%s_p%s-%s.pdf', $baseName, $from, $to);

        // Ruta donde guardaremos los recortes
        $relativePath = 'licitaciones/pdfs/splits/'.$licitacionPdf->id.'/'.$downloadName;
        Storage::makeDirectory(dirname($relativePath));

        // Guardar contenido
        $binary = $pdf->Output('S');
        Storage::put($relativePath, $binary);

        // Guardar en meta -> splits
        $meta = $licitacionPdf->meta ?? [];
        $splits = $meta['splits'] ?? [];

        $splits[] = [
            'from'       => $from,
            'to'         => $to,
            'page_count' => $to - $from + 1,
            'path'       => $relativePath,
            'filename'   => $downloadName,
            'created_at' => now()->toDateTimeString(),
        ];

        $meta['splits'] = $splits;
        $licitacionPdf->meta = $meta;
        $licitacionPdf->save();

        return redirect()
            ->route('admin.licitacion-pdfs.show', $licitacionPdf)
            ->with('status', 'Se generó un PDF recortado para las páginas '.$from.'–'.$to.'.');
    }

    /**
     * Descargar uno de los recortes generados en PDF / Word / Excel.
     *
     * Ruta sugerida:
     * admin.licitacion-pdfs.splits.download
     * /admin/licitacion-pdfs/{licitacionPdf}/splits/{index}/{format}
     */
    public function downloadSplit(
        Request $request,
        LicitacionPdf $licitacionPdf,
        int $index,
        string $format,
        IlovePdfService $ilovePdf
    ) {
        $meta   = $licitacionPdf->meta ?? [];
        $splits = $meta['splits'] ?? [];

        if (!isset($splits[$index])) {
            abort(404, 'Recorte no encontrado.');
        }

        $split = $splits[$index];

        $pdfRelativePath = $split['path'] ?? null;
        if (!$pdfRelativePath || !Storage::exists($pdfRelativePath)) {
            abort(404, 'Archivo de recorte no encontrado.');
        }

        $pdfFullPath = Storage::path($pdfRelativePath);

        // Nombre base
        $baseName = $split['filename'] ?? ('recorte_'.$licitacionPdf->id.'_pags_'.$split['from'].'-'.$split['to']);
        $baseName = pathinfo($baseName, PATHINFO_FILENAME);

        switch ($format) {
            case 'pdf':
                // Descargar el PDF recortado original
                return Storage::download(
                    $pdfRelativePath,
                    $baseName.'.pdf',
                    ['Content-Type' => 'application/pdf']
                );

            case 'word':
                // Conversión a Word usando IlovePdfService (texto plano)
                $tmpPath = $ilovePdf->pdfToWord($pdfFullPath, $baseName);

                if (!$tmpPath || !file_exists($tmpPath)) {
                    abort(500, 'No se pudo convertir el archivo a Word.');
                }

                return response()
                    ->download($tmpPath, $baseName.'.docx')
                    ->deleteFileAfterSend(true);

            case 'excel':
                // Conversión a Excel usando IlovePdfService (texto plano)
                $tmpPath = $ilovePdf->pdfToExcel($pdfFullPath, $baseName);

                if (!$tmpPath || !file_exists($tmpPath)) {
                    abort(500, 'No se pudo convertir el archivo a Excel.');
                }

                return response()
                    ->download($tmpPath, $baseName.'.xlsx')
                    ->deleteFileAfterSend(true);

            default:
                abort(400, 'Formato no soportado.');
        }
    }

    public function edit(LicitacionPdf $licitacionPdf)
    {
        return view('admin.licitacion_pdfs.edit', [
            'pdf' => $licitacionPdf,
        ]);
    }

    public function update(Request $request, LicitacionPdf $licitacionPdf)
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $licitacionPdf->update($data);

        return redirect()
            ->back()
            ->with('status', 'Registro de PDF actualizado.');
    }

    public function destroy(LicitacionPdf $licitacionPdf)
    {
        if ($licitacionPdf->original_path && Storage::exists($licitacionPdf->original_path)) {
            Storage::delete($licitacionPdf->original_path);
        }

        $licitacionPdf->delete();

        return redirect()
            ->route('admin.licitacion-pdfs.index')
            ->with('status', 'PDF eliminado.');
    }
}
