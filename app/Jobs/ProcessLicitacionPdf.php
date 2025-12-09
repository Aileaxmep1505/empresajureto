<?php

namespace App\Jobs;

use App\Models\LicitacionPdf;
use App\Models\LicitacionPdfPage;
use App\Services\LicitacionIaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class ProcessLicitacionPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \App\Models\LicitacionPdf
     */
    public LicitacionPdf $pdf;

    /**
     * Create a new job instance.
     */
    public function __construct(LicitacionPdf $pdf)
    {
        // Serializa solo el ID, no todo el modelo completo
        $this->pdf = $pdf;
    }

    /**
     * Execute the job.
     *
     * @param  \App\Services\LicitacionIaService  $ia
     * @return void
     */
    public function handle(LicitacionIaService $ia): void
    {
        // Volvemos a cargar el modelo por si cambió algo
        $pdf = LicitacionPdf::find($this->pdf->id);

        if (! $pdf) {
            Log::warning('ProcessLicitacionPdf: PDF no encontrado', [
                'id' => $this->pdf->id,
            ]);
            return;
        }

        try {
            $pdf->update(['status' => 'processing']);
        } catch (\Throwable $e) {
            Log::error('ProcessLicitacionPdf: error actualizando status a processing', [
                'id'        => $pdf->id,
                'exception' => $e->getMessage(),
            ]);
        }

        // Ruta física del archivo PDF
        $storagePath = $pdf->original_path;

        if (! $storagePath || ! Storage::exists($storagePath)) {
            Log::error('ProcessLicitacionPdf: archivo no existe en storage', [
                'id'   => $pdf->id,
                'path' => $storagePath,
            ]);

            $pdf->update([
                'status' => 'error',
                'meta'   => [
                    'error' => 'Archivo PDF no encontrado en storage.',
                ],
            ]);

            return;
        }

        $fullPath = Storage::path($storagePath);

        try {
            $parser = new Parser();
            $parsedPdf = $parser->parseFile($fullPath);
            $pages = $parsedPdf->getPages();
        } catch (\Throwable $e) {
            Log::error('ProcessLicitacionPdf: error al parsear PDF', [
                'id'        => $pdf->id,
                'exception' => $e->getMessage(),
            ]);

            $pdf->update([
                'status' => 'error',
                'meta'   => [
                    'error' => 'Error al leer el PDF: ' . $e->getMessage(),
                ],
            ]);

            return;
        }

        $totalPages = count($pages);
        $pdf->pages()->delete(); // por si se re-procesa

        $createdPages = 0;
        $totalItems   = 0;

        foreach ($pages as $index => $pageObj) {
            try {
                $pageNumber = $index + 1;
                $rawText    = $pageObj->getText();

                // Crea el registro de página
                $page = LicitacionPdfPage::create([
                    'licitacion_pdf_id' => $pdf->id,
                    'page_number'       => $pageNumber,
                    'raw_text'          => $rawText,
                    'tokens_count'      => null,
                    'status'            => 'pending',
                    'error_message'     => null,
                ]);

                $createdPages++;

                // Llamamos a la IA para extraer renglones de esta página
                $page->update(['status' => 'sent_to_ai']);

                $createdItems = $ia->extractItemsFromPageAndPersist(
                    $page,
                    licitacionId: $pdf->licitacion_id,
                    requisicionId: $pdf->requisicion_id
                );

                $totalItems += count($createdItems);

                $page->update(['status' => 'done']);
            } catch (\Throwable $e) {
                Log::error('ProcessLicitacionPdf: error procesando página', [
                    'pdf_id'  => $pdf->id,
                    'page'    => $index + 1,
                    'message' => $e->getMessage(),
                ]);

                if (isset($page) && $page instanceof LicitacionPdfPage) {
                    $page->update([
                        'status'        => 'error',
                        'error_message' => $e->getMessage(),
                    ]);
                }
            }
        }

        $pdf->update([
            'pages_count' => $totalPages,
            'status'      => 'items_extracted', // ya creamos licitacion_request_items
            'meta'        => [
                'processed_pages' => $createdPages,
                'total_items'     => $totalItems,
            ],
        ]);

        Log::info('ProcessLicitacionPdf: procesamiento completado', [
            'pdf_id'          => $pdf->id,
            'pages'           => $totalPages,
            'created_pages'   => $createdPages,
            'total_items'     => $totalItems,
        ]);
    }
}
