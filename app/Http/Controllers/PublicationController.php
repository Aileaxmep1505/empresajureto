<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use App\Models\PurchaseDocument;
use App\Services\Publications\PublicationPurchaseAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicationController extends Controller
{
    public function __construct(
        protected PublicationPurchaseAiService $svc
    ) {}

    public function index()
    {
        $data = $this->svc->buildIndexData(); // KPI + charts + tablas
        return view('publications.index', $data);
    }

    public function create()
    {
        return view('publications.create');
    }

    public function store(Request $request)
    {
        /**
         * ✅ IMPORTANT:
         * - Tu vista manda files[] (multi) y/o ai_payload_bulk ya editado
         * - Si viene ai_payload_bulk: NO queremos batch, NO queremos re-extraer IA.
         */
        if ($request->filled('ai_payload_bulk')) {
            $request->merge([
                'ai_extract' => '0',
                'ai_skip'    => '0',
            ]);
        }

        // ✅ Validación: tu service debe soportar files[].
        // Si tu service aún valida "file", cámbialo a "files" (abajo te digo).
        $request->validate($this->svc->storeRules());

        $result = $this->svc->storeFromRequest($request);

        // ✅ Ya NO queremos batch aunque el service lo intente
        $redirectRoute = (string) ($result['redirect_route'] ?? '');

        if ($redirectRoute && !in_array($redirectRoute, ['publications.batch','publications.batch.show'], true)) {
            return redirect()
                ->route($redirectRoute, $result['redirect_params'] ?? [])
                ->with('ok', $result['message'] ?? 'Publicación subida correctamente.');
        }

        // ✅ Default: SIEMPRE index (sin batch)
        return redirect()
            ->route('publications.index')
            ->with('ok', $result['message'] ?? 'Publicación subida correctamente.');
    }

    public function show(Publication $publication)
    {
        $purchaseDocs = PurchaseDocument::query()
            ->where('publication_id', $publication->id)
            ->with('items')
            ->latest('id')
            ->get();

        return view('publications.show', compact('publication', 'purchaseDocs'));
    }

    public function download(Publication $publication)
    {
        if (!Storage::disk('public')->exists($publication->file_path)) abort(404);
        return Storage::disk('public')->download($publication->file_path, $publication->original_name);
    }

    public function destroy(Publication $publication)
    {
        if ($publication->file_path && Storage::disk('public')->exists($publication->file_path)) {
            Storage::disk('public')->delete($publication->file_path);
        }
        $publication->delete();

        return redirect()->route('publications.index')->with('ok', 'Publicación eliminada.');
    }

    /* ============================================================
     | IA (1) EXTRACT (AJAX) - 1 archivo (tu vista lo usa para cada file)
     ============================================================ */
    public function aiExtractFromUpload(Request $request)
    {
        $request->validate($this->svc->extractRules());

        $file = $request->file('file');
        if (!$file) return response()->json(['error' => 'No se recibió archivo.'], 422);

        try {
            $normalized = $this->svc->extractNormalizedFromUploadedFile($file, $request->category);

            if (empty($normalized['items'])) {
                return response()->json(['error' => 'La IA no pudo detectar conceptos.'], 422);
            }

            $doc   = (array) ($normalized['document'] ?? []);
            $stats = (array) ($normalized['stats'] ?? []);
            $notes = (array) ($normalized['notes'] ?? []);

            // ✅ Resumen para mostrar “qué recabó”
            $summary = [
                'file_name'           => $file->getClientOriginalName(),
                'supplier_name'       => $doc['supplier_name'] ?? null,
                'operation_datetime'  => $doc['document_datetime'] ?? null, // ✅ fecha operación detectada
                'subtotal'            => $doc['subtotal'] ?? 0,
                'tax'                 => $doc['tax'] ?? 0,
                'total'               => $doc['total'] ?? 0,
                'items_count'         => (int) ($stats['items_count'] ?? count($normalized['items'] ?? [])),
                'confidence'          => data_get($notes, 'confidence', null),
                'warnings'            => data_get($notes, 'warnings', []),
            ];

            return response()->json([
                'summary'  => $summary,
                'document' => $doc,
                'items'    => $normalized['items'],
                'stats'    => $stats,
                'notes'    => $notes,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Error al contactar la IA.'], 500);
        }
    }

    /* ============================================================
     | IA (2) SAVE (AJAX) - opcional si un día quieres guardar por AJAX
     ============================================================ */
    public function aiSaveExtracted(Request $request)
    {
        $request->validate($this->svc->saveRules());

        $payload = (array) $request->input('payload', []);
        $publicationId = $request->input('publication_id');
        $category = (string) $request->category;

        try {
            $doc = $this->svc->saveExtractedPayload($payload, $publicationId, $category);

            return response()->json([
                'ok' => true,
                'purchase_document_id' => $doc->id,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'No se pudo guardar.'], 500);
        }
    }
}