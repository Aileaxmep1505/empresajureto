<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LicitacionPdf;
use App\Models\LicitacionPdfPage;
use App\Services\IlovePdfService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class LicitacionPdfController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [ new Middleware('auth') ];
    }

    public function index(Request $request)
    {
        $query = LicitacionPdf::query()->latest();

        if ($request->filled('licitacion_id'))  $query->where('licitacion_id', $request->integer('licitacion_id'));
        if ($request->filled('requisicion_id')) $query->where('requisicion_id', $request->integer('requisicion_id'));
        if ($request->filled('status'))         $query->where('status', $request->get('status'));

        $pdfs = $query->paginate(20);

        return view('admin.licitacion_pdfs.index', compact('pdfs'));
    }

    public function create()
    {
        return view('admin.licitacion_pdfs.create');
    }

    /**
     * SUBIR: intenta contar con FPDI.
     * Si FPDI falla (escaneados/problemáticos), repara con iLovePDF y vuelve a contar.
     */
    public function store(Request $request, IlovePdfService $ilovePdf)
    {
        $data = $request->validate([
            'licitacion_id'  => ['nullable', 'integer'],
            'requisicion_id' => ['nullable', 'integer'],
            'pdf'            => ['required', 'file', 'mimes:pdf', 'max:512000'], // 500MB (OJO: en KB)
        ]);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $data['pdf'];

        // Guarda archivo
        $path = $file->store('licitaciones/pdfs');

        $fullPath = Storage::path($path);

        // 1) Contar páginas con FPDI
        $pageCount = $this->countPagesFpdiSafe($fullPath);

        // 2) Si FPDI no pudo (0/1 suele ser sospechoso en escaneados),
        //    reparamos con iLovePDF y sustituimos el archivo original
        if ($pageCount <= 1) {
            try {
                Log::info('FPDI pageCount sospechoso, intentando repair con iLovePDF', [
                    'path' => $path,
                    'pageCount' => $pageCount,
                ]);

                $repairedBinary = $ilovePdf->repairPdfBinary($fullPath);

                // Sobrescribe el PDF en storage (mismo path)
                Storage::put($path, $repairedBinary);

                clearstatcache(true, $fullPath);

                // Re-contar
                $pageCount = $this->countPagesFpdiSafe($fullPath);
            } catch (\Throwable $e) {
                Log::warning('iLovePDF repair falló (se sigue con pageCount actual)', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // JAMÁS null (tu error SQL)
        $pageCount = (int) max(0, $pageCount);

        $pdf = LicitacionPdf::create([
            'licitacion_id'     => $data['licitacion_id'] ?? null,
            'requisicion_id'    => $data['requisicion_id'] ?? null,
            'original_filename' => $file->getClientOriginalName(),
            'original_path'     => $path,
            'pages_count'       => $pageCount,
            'status'            => 'uploaded',
            'meta'              => [],
        ]);

        // Crear registros de páginas
        if ($pageCount > 0) {
            $exists = LicitacionPdfPage::where('licitacion_pdf_id', $pdf->id)->count();
            if ($exists === 0) {
                for ($p = 1; $p <= $pageCount; $p++) {
                    LicitacionPdfPage::create([
                        'licitacion_pdf_id' => $pdf->id,
                        'page_number'       => $p,
                        'text'              => null,
                    ]);
                }
            }
        }

        return redirect()
            ->route('admin.licitacion-pdfs.show', $pdf)
            ->with('status', 'PDF subido correctamente. Ahora define los rangos a recortar.');
    }

    public function show(LicitacionPdf $licitacionPdf)
    {
        $pageCount = (int) ($licitacionPdf->pages_count ?: 0);

        if ($pageCount <= 0 && $licitacionPdf->original_path) {
            $full = Storage::path($licitacionPdf->original_path);
            $pageCount = (int) $this->countPagesFpdiSafe($full);
            if ($pageCount <= 0) $pageCount = 1;

            $licitacionPdf->update(['pages_count' => $pageCount]);
        }

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

    public function preview(LicitacionPdf $licitacionPdf)
    {
        if (!$licitacionPdf->original_path) abort(404);

        $filePath = Storage::path($licitacionPdf->original_path);
        if (!file_exists($filePath)) abort(404);

        return response()->file($filePath, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$licitacionPdf->original_filename.'"',
        ]);
    }

    /**
     * RECORTAR:
     * - Intenta FPDI normal
     * - Si truena o el PDF es “especial”, usa iLovePDF split ranges
     */
    public function split(Request $request, LicitacionPdf $licitacionPdf, IlovePdfService $ilovePdf)
    {
        $data = $request->validate([
            'from' => ['required', 'integer', 'min:1'],
            'to'   => ['required', 'integer', 'min:1'],
        ]);

        $from = (int) $data['from'];
        $to   = (int) $data['to'];
        if ($from > $to) [$from, $to] = [$to, $from];

        if (!$licitacionPdf->original_path) {
            return back()->with('status', 'No se encontró el archivo original.');
        }

        $filePath = Storage::path($licitacionPdf->original_path);
        if (!file_exists($filePath)) {
            return back()->with('status', 'El archivo físico no existe en el servidor.');
        }

        // Nombre
        $baseName = pathinfo($licitacionPdf->original_filename, PATHINFO_FILENAME);
        $downloadName = sprintf('%s_p%s-%s.pdf', $baseName, $from, $to);

        // Ruta guardado
        $relativePath = 'licitaciones/pdfs/splits/'.$licitacionPdf->id.'/'.$downloadName;
        Storage::makeDirectory(dirname($relativePath));

        // 1) Intento con FPDI
        try {
            $pdf = new Fpdi();
            $totalPages = $pdf->setSourceFile($filePath);

            $from = max(1, $from);
            $to   = min((int) $totalPages, $to);

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

            $binary = $pdf->Output('S');
            Storage::put($relativePath, $binary);
        } catch (\Throwable $e) {
            // 2) Fallback: iLovePDF split
            try {
                Log::warning('FPDI split falló, usando iLovePDF split ranges', [
                    'pdf_id' => $licitacionPdf->id,
                    'error'  => $e->getMessage(),
                ]);

                $ranges = "{$from}-{$to}";
                $binary = $ilovePdf->splitRangesBinary($filePath, $ranges, true);

                Storage::put($relativePath, $binary);
            } catch (\Throwable $e2) {
                Log::error('iLovePDF split también falló', [
                    'pdf_id' => $licitacionPdf->id,
                    'error'  => $e2->getMessage(),
                ]);

                return back()->with('error', 'No se pudo recortar: '.$e2->getMessage());
            }
        }

        // Guardar en meta->splits
        $meta   = $licitacionPdf->meta ?? [];
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
            ->with('status', "Se generó un PDF recortado para las páginas {$from}–{$to}.");
    }

    public function downloadSplit(Request $request, LicitacionPdf $licitacionPdf, int $index, string $format, IlovePdfService $ilovePdf)
    {
        $meta   = $licitacionPdf->meta ?? [];
        $splits = $meta['splits'] ?? [];

        if (!isset($splits[$index])) abort(404, 'Recorte no encontrado.');

        $split = $splits[$index];
        $pdfRelativePath = $split['path'] ?? null;

        if (!$pdfRelativePath || !Storage::exists($pdfRelativePath)) {
            abort(404, 'Archivo de recorte no encontrado.');
        }

        $baseName = $split['filename'] ?? ('recorte_'.$licitacionPdf->id.'_pags_'.$split['from'].'-'.$split['to']);
        $baseName = pathinfo($baseName, PATHINFO_FILENAME);

        // En tu caso, aquí puedes dejarlo igual que antes.
        // (Si también quieres Word/Excel por iLovePDF, se hace con tool officepdf/extract, pero es otra ruta.)
        if ($format === 'pdf') {
            return Storage::download($pdfRelativePath, $baseName.'.pdf', [
                'Content-Type' => 'application/pdf',
            ]);
        }

        abort(400, 'Formato no soportado.');
    }

    public function edit(LicitacionPdf $licitacionPdf)
    {
        return view('admin.licitacion_pdfs.edit', ['pdf' => $licitacionPdf]);
    }

    public function update(Request $request, LicitacionPdf $licitacionPdf)
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $licitacionPdf->update($data);

        return back()->with('status', 'Registro de PDF actualizado.');
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

    // ========================= helpers =========================

    protected function countPagesFpdiSafe(string $fullPath): int
    {
        try {
            $fpdi = new Fpdi();
            $count = $fpdi->setSourceFile($fullPath);
            return (int) $count;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
