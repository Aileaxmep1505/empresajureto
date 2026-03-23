<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use App\Models\PurchaseDocument;
use App\Services\Publications\PublicationPurchaseAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PublicationController extends Controller
{
    public function __construct(
        protected PublicationPurchaseAiService $svc
    ) {}

    public function index()
    {
        $data = $this->svc->buildIndexData();
        return view('publications.index', $data);
    }

    public function create()
    {
        return view('publications.create');
    }

    public function store(Request $request)
    {
        if ($request->filled('ai_payload_bulk')) {
            $request->merge([
                'ai_extract' => '0',
                'ai_skip'    => '0',
            ]);
        }

        $request->validate(
            $this->svc->storeRules(),
            $this->svc->validationMessages(),
            $this->svc->validationAttributes()
        );

        $result = $this->svc->storeFromRequest($request);

        $redirectRoute = (string) ($result['redirect_route'] ?? '');

        if ($redirectRoute && !in_array($redirectRoute, ['publications.batch', 'publications.batch.show'], true)) {
            return redirect()
                ->route($redirectRoute, $result['redirect_params'] ?? [])
                ->with('ok', $result['message'] ?? 'Publicación subida correctamente.');
        }

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
        if (!Storage::disk('public')->exists($publication->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($publication->file_path, $publication->original_name);
    }

    public function destroy(Publication $publication)
    {
        if ($publication->file_path && Storage::disk('public')->exists($publication->file_path)) {
            Storage::disk('public')->delete($publication->file_path);
        }

        $publication->delete();

        return redirect()
            ->route('publications.index')
            ->with('ok', 'Publicación eliminada.');
    }

    public function aiExtractFromUpload(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            $this->svc->extractRules(),
            $this->svc->validationMessages(),
            $this->svc->validationAttributes()
        );

        if ($validator->fails()) {
            $file = $request->file('file');

            Log::warning('Publications AI extract: validation failed', [
                'file_name' => $file?->getClientOriginalName(),
                'mime'      => $file?->getClientMimeType(),
                'ext'       => $file?->getClientOriginalExtension(),
                'size'      => $file?->getSize(),
                'errors'    => $validator->errors()->toArray(),
            ]);

            return response()->json([
                'error'   => $validator->errors()->first() ?: 'No se pudo validar el archivo.',
                'errors'  => $validator->errors(),
                'message' => 'Verifica que se haya enviado un archivo real y que no exceda el tamaño permitido.',
            ], 422);
        }

        $file = $request->file('file');
        if (!$file) {
            return response()->json(['error' => 'No se recibió archivo.'], 422);
        }

        Log::info('Publications AI extract: received file', [
            'file_name' => $file->getClientOriginalName(),
            'mime'      => $file->getClientMimeType(),
            'ext'       => $file->getClientOriginalExtension(),
            'size'      => $file->getSize(),
            'category'  => (string) $request->input('category', 'compra'),
        ]);

        try {
            $normalized = $this->svc->extractNormalizedFromUploadedFile(
                $file,
                (string) $request->input('category', 'compra')
            );

            $doc   = (array) ($normalized['document'] ?? []);
            $items = is_array($normalized['items'] ?? null) ? $normalized['items'] : [];
            $stats = (array) ($normalized['stats'] ?? []);
            $notes = (array) ($normalized['notes'] ?? []);

            $warnings = array_values(array_filter((array) data_get($notes, 'warnings', [])));
            if (empty($items)) {
                $warnings[] = 'No se detectaron conceptos automáticamente. Puedes capturarlos o editarlos manualmente.';
            }

            $summary = [
                'file_name'          => $file->getClientOriginalName(),
                'supplier_name'      => $doc['supplier_name'] ?? null,
                'operation_datetime' => $doc['document_datetime'] ?? null,
                'subtotal'           => $doc['subtotal'] ?? 0,
                'tax'                => $doc['tax'] ?? 0,
                'total'              => $doc['total'] ?? 0,
                'items_count'        => (int) ($stats['items_count'] ?? count($items)),
                'confidence'         => data_get($notes, 'confidence', null),
                'warnings'           => $warnings,
            ];

            return response()->json([
                'ok'       => true,
                'summary'  => $summary,
                'document' => $doc,
                'items'    => $items,
                'stats'    => $stats,
                'notes'    => array_merge($notes, ['warnings' => $warnings]),
                'warning'  => empty($items)
                    ? 'No se detectaron conceptos automáticamente. Puedes capturarlos manualmente antes de guardar.'
                    : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Publications AI extract failed', [
                'file_name' => $file->getClientOriginalName(),
                'mime'      => $file->getClientMimeType(),
                'ext'       => $file->getClientOriginalExtension(),
                'size'      => $file->getSize(),
                'error'     => $e->getMessage(),
            ]);

            $message = $e->getMessage();
            $status = str_contains(mb_strtolower($message), 'texto legible')
                ? 422
                : 500;

            return response()->json([
                'error' => $status === 422
                    ? 'El archivo se subió, pero no se pudo convertir a un formato legible para la IA. Puedes guardarlo y capturar los conceptos manualmente.'
                    : 'No se pudo analizar el archivo con IA. Reintenta en unos segundos o usa captura manual.',
            ], $status);
        }
    }

    public function aiSaveExtracted(Request $request)
    {
        $request->validate(
            $this->svc->saveRules(),
            $this->svc->validationMessages(),
            $this->svc->validationAttributes()
        );

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
            Log::warning('Publications AI save failed', [
                'publication_id' => $publicationId,
                'error'          => $e->getMessage(),
            ]);

            return response()->json(['error' => 'No se pudo guardar.'], 500);
        }
    }
}
