<?php

namespace App\Services\Publications;

use App\Models\Publication;
use App\Models\PurchaseDocument;
use App\Models\PurchaseItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicationPurchaseAiService
{
    public function validationMessages(): array
    {
        return [
            'files.array'          => 'Debes subir uno o más archivos válidos.',
            'files.min'            => 'Debes subir al menos 1 archivo.',
            'files.max'            => 'Solo puedes subir hasta 25 archivos por lote.',
            'files.*.file'         => 'Cada elemento debe ser un archivo válido.',
            'files.*.max'          => 'Cada archivo puede pesar hasta 50 MB.',
            'file.file'            => 'El archivo no es válido.',
            'file.max'             => 'El archivo puede pesar hasta 50 MB.',
            'title.required'       => 'El título es obligatorio.',
            'category.required'    => 'Debes seleccionar el tipo de operación.',
            'category.in'          => 'La categoría seleccionada no es válida.',
            'payload.required'     => 'No se recibió la información a guardar.',
            'payload.array'        => 'El payload recibido no es válido.',
            'company_id.required'  => 'Debes seleccionar una compañía para la venta.',
            'company_id.exists'    => 'La compañía seleccionada no existe.',
            'due_date.date'        => 'La fecha de vencimiento no es válida.',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'file'          => 'archivo',
            'files'         => 'archivos',
            'files.*'       => 'archivo',
            'title'         => 'título',
            'description'   => 'descripción',
            'category'      => 'categoría',
            'payload'       => 'payload',
            'publication_id'=> 'publicación',
            'company_id'    => 'compañía',
            'due_date'      => 'fecha de vencimiento',
            'credit_days'   => 'días de crédito',
            'amount_paid'   => 'monto pagado',
            'status'        => 'estatus',
            'priority'      => 'prioridad',
            'collection_status' => 'estado de cobranza',
            'reminder_days_before' => 'días previos para recordatorio',
            'interest_rate' => 'tasa de interés',
            'assigned_to'   => 'asignado a',
            'notes'         => 'notas',
            'tags'          => 'etiquetas',
        ];
    }

    public function storeRules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],

            'files'       => ['nullable', 'array', 'min:1', 'max:25'],
            'files.*'     => ['file', 'max:51200'],

            'file'        => ['nullable', 'file', 'max:51200'],

            'pinned'      => ['nullable'],
            'ai_extract'  => ['nullable', 'boolean'],
            'ai_skip'     => ['nullable'],

            'ai_payload'      => ['nullable', 'string'],
            'ai_payload_bulk' => ['nullable', 'string'],
            'file_fps'        => ['nullable', 'array'],
            'file_fps.*'      => ['nullable', 'string', 'max:500'],

            'category'    => ['required', 'string', 'in:compra,venta'],

            'company_id'  => ['nullable', 'exists:companies,id'],
            'due_date'    => ['nullable', 'date'],
            'credit_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'status'      => ['nullable', 'string', 'in:pending,partial,paid,overdue,cancelled,pendiente,parcial,cobrado,vencido,cancelado'],
            'priority'    => ['nullable', 'in:alta,media,baja'],
            'collection_status' => ['nullable', 'in:sin_gestion,en_gestion,promesa_pago,litigio,incobrable'],
            'reminder_days_before' => ['nullable', 'integer', 'min:0', 'max:365'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'assigned_to' => ['nullable', 'string', 'max:255'],
            'notes'       => ['nullable', 'string'],
            'tags'        => ['nullable', 'array'],
            'tags.*'      => ['string', 'max:50'],
        ];
    }

    public function extractRules(): array
    {
        return [
            'file'     => ['required', 'file', 'max:51200'],
            'category' => ['required', 'string', 'in:compra,venta'],
        ];
    }

    public function saveRules(): array
    {
        return [
            'publication_id' => ['nullable', 'integer'],
            'payload'        => ['required', 'array'],
            'category'       => ['required', 'string', 'in:compra,venta'],
            'company_id'     => ['nullable', 'exists:companies,id'],
            'due_date'       => ['nullable', 'date'],
            'credit_days'    => ['nullable', 'integer', 'min:0', 'max:3650'],
            'amount_paid'    => ['nullable', 'numeric', 'min:0'],
            'status'         => ['nullable', 'string', 'in:pending,partial,paid,overdue,cancelled,pendiente,parcial,cobrado,vencido,cancelado'],
            'priority'       => ['nullable', 'in:alta,media,baja'],
            'collection_status' => ['nullable', 'in:sin_gestion,en_gestion,promesa_pago,litigio,incobrable'],
            'reminder_days_before' => ['nullable', 'integer', 'min:0', 'max:365'],
            'interest_rate'  => ['nullable', 'numeric', 'min:0'],
            'assigned_to'    => ['nullable', 'string', 'max:255'],
            'notes'          => ['nullable', 'string'],
            'tags'           => ['nullable', 'array'],
            'tags.*'         => ['string', 'max:50'],
        ];
    }

    public function buildIndexData(): array
    {
        $pinned = Publication::query()->where('pinned', true)->latest('created_at')->get();
        $latest = Publication::query()->where('pinned', false)->latest('created_at')->paginate(12);

        $totalSpentCompra = (float) PurchaseDocument::where('category', 'compra')->sum('total');
        $totalSpentVenta  = (float) PurchaseDocument::where('category', 'venta')->sum('total');
        $totalSpent       = (float) PurchaseDocument::sum('total');

        $endDay   = now()->endOfDay();
        $startDay = now()->subDays(29)->startOfDay();

        $months = [];
        $mCursor = now()->startOfMonth()->subMonths(11);
        for ($i=0; $i<12; $i++) {
            $months[] = $mCursor->format('Y-m');
            $mCursor->addMonth();
        }

        $chartLabels = [];
        foreach ($months as $m) {
            try {
                $dt = Carbon::createFromFormat('Y-m', $m);
                $chartLabels[] = $dt->translatedFormat('M Y');
            } catch (\Exception $e) {
                $chartLabels[] = $m;
            }
        }

        $monthlyRaw = PurchaseDocument::selectRaw("
                category,
                DATE_FORMAT(COALESCE(document_datetime, created_at), '%Y-%m') as month_id,
                SUM(total) as total
            ")
            ->whereIn('category', ['compra', 'venta'])
            ->whereRaw("COALESCE(document_datetime, created_at) >= ?", [now()->startOfMonth()->subMonths(11)->startOfDay()])
            ->groupBy('category', 'month_id')
            ->orderBy('month_id', 'asc')
            ->get();

        $monthlyCompra = array_fill(0, count($months), 0.0);
        $monthlyVenta  = array_fill(0, count($months), 0.0);
        $monthIndex    = array_flip($months);

        foreach ($monthlyRaw as $row) {
            $idx = $monthIndex[$row->month_id] ?? null;
            if ($idx === null) continue;
            if ($row->category === 'compra') $monthlyCompra[$idx] = (float) $row->total;
            if ($row->category === 'venta')  $monthlyVenta[$idx]  = (float) $row->total;
        }

        $chartValues = $monthlyCompra;

        $days = [];
        $dCursor = $startDay->copy();
        for ($i=0; $i<30; $i++) {
            $days[] = $dCursor->toDateString();
            $dCursor->addDay();
        }

        $dailyLabels = collect($days)->map(fn ($d) => Carbon::parse($d)->format('d/m'))->values()->all();

        $dailyRaw = PurchaseDocument::selectRaw("
                category,
                DATE(COALESCE(document_datetime, created_at)) as day,
                SUM(total) as total
            ")
            ->whereIn('category', ['compra', 'venta'])
            ->whereRaw("COALESCE(document_datetime, created_at) >= ?", [$startDay])
            ->whereRaw("COALESCE(document_datetime, created_at) <= ?", [$endDay])
            ->groupBy('category', 'day')
            ->orderBy('day', 'asc')
            ->get();

        $dailyCompra = array_fill(0, count($days), 0.0);
        $dailyVenta  = array_fill(0, count($days), 0.0);
        $dayIndex    = array_flip($days);

        foreach ($dailyRaw as $row) {
            $key = is_string($row->day) ? $row->day : (string)$row->day;
            $idx = $dayIndex[$key] ?? null;
            if ($idx === null) continue;
            if ($row->category === 'compra') $dailyCompra[$idx] = (float) $row->total;
            if ($row->category === 'venta')  $dailyVenta[$idx]  = (float) $row->total;
        }

        $dailyValues = collect($dailyCompra);

        $topProductsCompra = PurchaseItem::selectRaw("purchase_documents.category, purchase_items.item_name, SUM(purchase_items.line_total) as total_spent")
            ->join('purchase_documents', 'purchase_items.purchase_document_id', '=', 'purchase_documents.id')
            ->where('purchase_documents.category', 'compra')
            ->whereNotNull('purchase_items.item_name')
            ->where('purchase_items.item_name', '!=', '')
            ->groupBy('purchase_documents.category', 'purchase_items.item_name')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();

        $topProductsVenta = PurchaseItem::selectRaw("purchase_documents.category, purchase_items.item_name, SUM(purchase_items.line_total) as total_spent")
            ->join('purchase_documents', 'purchase_items.purchase_document_id', '=', 'purchase_documents.id')
            ->where('purchase_documents.category', 'venta')
            ->whereNotNull('purchase_items.item_name')
            ->where('purchase_items.item_name', '!=', '')
            ->groupBy('purchase_documents.category', 'purchase_items.item_name')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();

        $prodChartDataCompra = $topProductsCompra->map(fn ($item) => ['x' => Str::limit($item->item_name, 25), 'y' => (float) $item->total_spent])->values()->all();
        $prodChartDataVenta  = $topProductsVenta->map(fn ($item) => ['x' => Str::limit($item->item_name, 25), 'y' => (float) $item->total_spent])->values()->all();

        $prodChartData = $prodChartDataCompra;

        $allPurchases = PurchaseItem::select(
                'purchase_items.*',
                'purchase_documents.document_datetime',
                'purchase_documents.created_at as doc_created_at',
                'purchase_documents.supplier_name',
                'purchase_documents.category'
            )
            ->join('purchase_documents', 'purchase_items.purchase_document_id', '=', 'purchase_documents.id')
            ->whereIn('purchase_documents.category', ['compra', 'venta'])
            ->orderByRaw("COALESCE(purchase_documents.document_datetime, purchase_documents.created_at) DESC")
            ->limit(200)
            ->get();

        $topSuppliersCompra = PurchaseDocument::selectRaw("supplier_name, COUNT(*) as count, SUM(total) as total_amount")
            ->where('category', 'compra')
            ->whereNotNull('supplier_name')
            ->where('supplier_name', '!=', '')
            ->groupBy('supplier_name')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get();

        $topSuppliersVenta = PurchaseDocument::selectRaw("supplier_name, COUNT(*) as count, SUM(total) as total_amount")
            ->where('category', 'venta')
            ->whereNotNull('supplier_name')
            ->where('supplier_name', '!=', '')
            ->groupBy('supplier_name')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get();

        $topSuppliers = $topSuppliersCompra;

        return compact(
            'pinned',
            'latest',
            'totalSpent',
            'totalSpentCompra',
            'totalSpentVenta',
            'chartLabels',
            'chartValues',
            'monthlyCompra',
            'monthlyVenta',
            'dailyLabels',
            'dailyValues',
            'dailyCompra',
            'dailyVenta',
            'prodChartData',
            'prodChartDataCompra',
            'prodChartDataVenta',
            'allPurchases',
            'topSuppliers',
            'topSuppliersCompra',
            'topSuppliersVenta'
        );
    }

    public function storeFromRequest(Request $request): array
    {
        $files = $request->file('files', []);
        if (empty($files) && $request->hasFile('file')) {
            $files = [$request->file('file')];
        }
        if (empty($files)) {
            return ['message' => 'Sube al menos 1 archivo.'];
        }

        $category = (string)$request->category;
        $isMulti = count($files) > 1;

        $aiSkip = (string)($request->input('ai_skip', '0')) === '1';
        $aiPayloadRaw = trim((string)($request->input('ai_payload', '')));

        $aiPayloadBulkRaw = trim((string)($request->input('ai_payload_bulk', '')));
        $bulkMap = [];
        if ($aiPayloadBulkRaw !== '') {
            $decoded = json_decode($aiPayloadBulkRaw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $row) {
                    if (!is_array($row)) continue;
                    $fp = trim((string)($row['fp'] ?? ''));
                    if ($fp === '') continue;
                    $bulkMap[$fp] = $row;
                }
            }
        }

        $fileFps = $request->input('file_fps', []);
        if (!is_array($fileFps)) $fileFps = [];

        if (!$aiSkip && $aiPayloadRaw !== '' && count($files) !== 1) {
            return ['message' => 'Si usas ai_payload, sube solo 1 archivo.'];
        }

        $accountingContext = $this->buildAccountingContextFromRequest($request, $category);

        $created = 0;
        $processed = 0;
        $skippedDocs = 0;
        $createdPurchaseDocumentIds = [];

        foreach ($files as $i => $file) {
            /** @var UploadedFile $file */
            $pub = $this->storeOnePublicationFile(
                file: $file,
                title: (string)$request->title,
                description: (string)($request->description ?? ''),
                category: $category,
                pinned: (bool)$request->boolean('pinned'),
                isMulti: $isMulti
            );

            $created++;

            if (!$aiSkip && !empty($bulkMap)) {
                $fp = trim((string)($fileFps[$i] ?? ''));
                if ($fp === '') $fp = $this->fingerprintServer($file);

                $payload = $bulkMap[$fp] ?? null;

                if (is_array($payload)) {
                    try {
                        $normalized = $this->normalizeAiPurchase($payload);
                        if (!empty($normalized['items'])) {
                            $savedDoc = $this->persistPurchaseDocumentFromAi(
                                $normalized,
                                $pub->id,
                                $category,
                                $accountingContext
                            );
                            $createdPurchaseDocumentIds[] = $savedDoc->id;
                            $processed++;
                        } else {
                            $skippedDocs++;
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Publication store: bulk payload persist failed (ignored)', [
                            'publication_id' => $pub->id,
                            'error' => $e->getMessage(),
                        ]);
                        $skippedDocs++;
                    }
                } else {
                    $skippedDocs++;
                }

                continue;
            }

            if (!$aiSkip && $aiPayloadRaw !== '') {
                try {
                    $payload = json_decode($aiPayloadRaw, true);
                    if (is_array($payload)) {
                        $normalized = $this->normalizeAiPurchase($payload);
                        if (!empty($normalized['items'])) {
                            $savedDoc = $this->persistPurchaseDocumentFromAi(
                                $normalized,
                                $pub->id,
                                $category,
                                $accountingContext
                            );
                            $createdPurchaseDocumentIds[] = $savedDoc->id;
                            $processed++;
                        } else {
                            $skippedDocs++;
                        }
                    } else {
                        $skippedDocs++;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Publication store: ai_payload persist failed (ignored)', [
                        'publication_id' => $pub->id,
                        'error' => $e->getMessage(),
                    ]);
                    $skippedDocs++;
                }

                return [
                    'redirect_route' => 'publications.index',
                    'redirect_params' => [],
                    'message' => 'Publicación subida correctamente.',
                    'created_purchase_document_ids' => array_values(array_unique($createdPurchaseDocumentIds)),
                ];
            }

            if ($aiSkip) {
                continue;
            }

            $kind = (string)($pub->kind ?? '');
            if ($request->boolean('ai_extract') && $this->canAttemptAiExtraction($pub)) {
                try {
                    $absolute = Storage::disk('public')->path($pub->file_path);
                    $ai = $this->aiExtractSingleLocalFile($absolute, $pub->original_name, $category);

                    if ($ai && !empty($ai['items'])) {
                        $savedDoc = $this->persistPurchaseDocumentFromAi(
                            $ai,
                            $pub->id,
                            $category,
                            $accountingContext
                        );
                        $createdPurchaseDocumentIds[] = $savedDoc->id;
                        $processed++;
                    } else {
                        $skippedDocs++;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Publication store: AI extract failed (ignored)', [
                        'publication_id' => $pub->id,
                        'error' => $e->getMessage(),
                    ]);
                    $skippedDocs++;
                }
            }
        }

        $msg = "Listo. Archivos subidos: {$created}. Documentos guardados: {$processed}.";
        if ($skippedDocs > 0) $msg .= " Sin items/omitidos: {$skippedDocs}.";

        return [
            'redirect_route' => 'publications.index',
            'redirect_params' => [],
            'message' => $msg,
            'created_purchase_document_ids' => array_values(array_unique($createdPurchaseDocumentIds)),
        ];
    }

    private function buildAccountingContextFromRequest(Request $request, string $category): array
    {
        if ($category !== 'venta') {
            return [];
        }

        return [
            'company_id' => $request->input('company_id'),
            'due_date' => $request->input('due_date'),
            'credit_days' => $request->input('credit_days'),
            'amount_paid' => $request->input('amount_paid'),
            'status' => $request->input('status', 'pendiente'),
            'priority' => $request->input('priority', 'media'),
            'collection_status' => $request->input('collection_status', 'sin_gestion'),
            'reminder_days_before' => $request->input('reminder_days_before', 5),
            'interest_rate' => $request->input('interest_rate', 0),
            'assigned_to' => $request->input('assigned_to'),
            'notes' => $request->input('notes'),
            'tags' => $request->input('tags'),
        ];
    }

    private function fingerprintServer(UploadedFile $file): string
    {
        $name = (string) $file->getClientOriginalName();
        $size = (string) ($file->getSize() ?: 0);
        $type = (string) ($file->getClientMimeType() ?: '');
        return "{$name}|{$size}|{$type}";
    }

    private function storeOnePublicationFile(
        UploadedFile $file,
        string $title,
        string $description,
        string $category,
        bool $pinned,
        bool $isMulti
    ): Publication {
        $folder = 'publications/' . now()->format('Y/m');

        $safeBaseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        $name = $safeBaseName . '-' . Str::random(8) . ($ext ? ".{$ext}" : '');

        $path = $file->storeAs($folder, $name, 'public');

        $mime = $this->guessMimeType($file->getRealPath(), $ext);
        $kind = $this->detectKind($mime, $ext);

        $fileTitle = $title;
        if ($isMulti) {
            $fileTitle = Str::limit($title . ' — ' . $file->getClientOriginalName(), 200, '');
        }

        return Publication::create([
            'title'         => $fileTitle,
            'description'   => $description,
            'file_path'     => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $mime,
            'size'          => $file->getSize() ?: 0,
            'extension'     => $ext,
            'kind'          => $kind,
            'pinned'        => $pinned,
            'created_by'    => Auth::id(),
            'category'      => $category,
            'batch_key'     => null,
        ]);
    }

    public function extractNormalizedFromUploadedFile(UploadedFile $file, string $category): array
    {
        $ai = $this->aiExtractSingleLocalFile(
            $file->getRealPath(),
            $file->getClientOriginalName(),
            $category
        );

        return $this->normalizeAiPurchase($ai);
    }

    public function saveExtractedPayload(
        array $payload,
        ?int $publicationId,
        string $category,
        array $accountingContext = []
    ): PurchaseDocument {
        $normalized = $this->normalizeAiPurchase($payload);
        if (empty($normalized['items'])) {
            throw new \RuntimeException('No hay items para guardar.');
        }

        return $this->persistPurchaseDocumentFromAi(
            $normalized,
            $publicationId,
            $category,
            $accountingContext
        );
    }

    public function aiExtractSingleLocalFile(string $absolutePath, string $originalName, string $category = 'compra'): array
    {
        $cfg = $this->openAiConfig();

        if ($cfg['api_key'] === '') {
            throw new \RuntimeException('Missing OpenAI API key.');
        }

        if (!is_file($absolutePath)) {
            throw new \RuntimeException('El archivo temporal no existe.');
        }

        $prepared = $this->prepareFileForAi($absolutePath, $originalName);
        $system = $this->buildExtractorSystemPromptStrictTableOnly($category);

        Log::info('AI extract strategy', [
            'original_name' => $originalName,
            'prepared_name' => $prepared['original_name'],
            'mime'          => $prepared['mime'],
            'ext'           => $prepared['ext'],
            'kind'          => $prepared['kind'],
            'strategy'      => $prepared['strategy'],
            'repaired_pdf'  => $prepared['repaired_pdf'],
            'text_length'   => mb_strlen((string) $prepared['text']),
        ]);

        $fileId = null;

        try {
            if (in_array($prepared['strategy'], ['pdf_file', 'image_file'], true)) {
                $fileId = $this->uploadFileToOpenAi($prepared['path'], $prepared['original_name'], $cfg);

                $data = $this->extractUsingResponsesWithRetries(
                    cfg: $cfg,
                    fileId: $fileId,
                    system: $system,
                    userText: $this->buildUserExtractionPrompt((string) $prepared['text']),
                    inputType: $prepared['strategy'] === 'image_file' ? 'input_image' : 'input_file'
                );
            } else {
                $text = trim((string) $prepared['text']);
                if ($text === '') {
                    throw new \RuntimeException('No se pudo obtener texto legible del archivo para enviarlo a la IA.');
                }

                $data = $this->extractFromTextOnly(
                    cfg: $cfg,
                    system: $system,
                    originalName: $prepared['original_name'],
                    extractedText: $text
                );
            }

            return $this->finalizeAiExtractResult($data);
        } finally {
            if ($fileId) {
                $this->deleteOpenAiFile($fileId, $cfg);
            }

            if (!empty($prepared['temp_cleanup']) && is_file($prepared['temp_cleanup'])) {
                @unlink($prepared['temp_cleanup']);
            }
        }
    }

    private function openAiConfig(): array
    {
        $baseUrl = rtrim((string) (
            config('services.openai.base_url')
            ?: config('openai.base_url')
            ?: 'https://api.openai.com/v1'
        ), '/');

        if (!str_ends_with($baseUrl, '/v1')) {
            $baseUrl .= '/v1';
        }

        return [
            'api_key'        => (string) (
                config('services.openai.api_key')
                ?: config('services.openai.key')
                ?: config('openai.api_key')
                ?: config('openai.key')
                ?: ''
            ),
            'base_url'       => $baseUrl,
            'model'          => (string) (
                config('services.openai.model')
                ?: config('openai.primary')
                ?: config('openai.model')
                ?: 'gpt-4.1'
            ),
            'timeout'        => (int) (
                config('services.openai.timeout')
                ?: config('openai.timeout')
                ?: 300
            ),
            'retries'        => (int) (
                config('services.openai.retries')
                ?: config('openai.retries')
                ?: 3
            ),
            'retry_sleep_ms' => (int) (
                config('services.openai.retry_sleep_ms')
                ?: config('openai.retry_sleep_ms')
                ?: 1200
            ),
        ];
    }

    private function uploadFileToOpenAi(string $absolutePath, string $originalName, array $cfg): string
    {
        $lastStatus = null;
        $lastBody = null;

        for ($attempt = 1; $attempt <= max(1, (int) $cfg['retries']); $attempt++) {
            $upload = Http::withToken($cfg['api_key'])
                ->timeout((int) $cfg['timeout'])
                ->attach('file', file_get_contents($absolutePath), $originalName)
                ->post($cfg['base_url'] . '/files', ['purpose' => 'user_data']);

            if ($upload->successful()) {
                $fileId = (string) $upload->json('id');
                if ($fileId !== '') {
                    return $fileId;
                }
            }

            $lastStatus = $upload->status();
            $lastBody = $upload->body();

            if ($attempt < (int) $cfg['retries']) {
                usleep(((int) $cfg['retry_sleep_ms']) * 1000);
            }
        }

        Log::warning('AI file upload error', [
            'status' => $lastStatus,
            'body'   => $lastBody,
        ]);

        throw new \RuntimeException('Error subiendo archivo a OpenAI.');
    }

    private function callResponsesExtract(array $cfg, string $fileId, string $system, string $userText, string $inputType = 'input_file'): array
    {
        $content = [
            ['type' => 'input_text', 'text' => $userText],
            ['type' => $inputType, 'file_id' => $fileId],
        ];

        $lastStatus = null;
        $lastBody = null;
        $lastError = null;

        for ($attempt = 1; $attempt <= max(1, (int) $cfg['retries']); $attempt++) {
            try {
                $resp = Http::withToken($cfg['api_key'])
                    ->timeout((int) $cfg['timeout'])
                    ->withHeaders([
                        'Accept'       => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                    ->post($cfg['base_url'] . '/responses', [
                        'model'             => $cfg['model'],
                        'instructions'      => $system,
                        'input'             => [[
                            'role'    => 'user',
                            'content' => $content,
                        ]],
                        'max_output_tokens' => 6500,
                    ]);

                if ($resp->successful()) {
                    return $this->parseOpenAiJsonFromResponses((array) $resp->json());
                }

                $lastStatus = $resp->status();
                $lastBody = $resp->body();
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }

            if ($attempt < (int) $cfg['retries']) {
                usleep(((int) $cfg['retry_sleep_ms']) * 1000);
            }
        }

        Log::warning('AI responses error', [
            'status' => $lastStatus,
            'body'   => $lastBody,
            'error'  => $lastError,
        ]);

        throw new \RuntimeException('La IA respondió con error.');
    }

    private function deleteOpenAiFile(string $fileId, array $cfg): void
    {
        try {
            Http::withToken($cfg['api_key'])
                ->timeout(30)
                ->delete($cfg['base_url'] . '/files/' . $fileId);
        } catch (\Throwable $e) {
            Log::info('AI file cleanup skipped', [
                'file_id' => $fileId,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private function canAttemptAiExtraction(Publication $publication): bool
    {
        $kind = (string) ($publication->kind ?? '');
        return in_array($kind, ['pdf', 'image', 'doc', 'sheet', 'text', 'file'], true);
    }

    private function prepareFileForAi(string $absolutePath, string $originalName): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION) ?: pathinfo($absolutePath, PATHINFO_EXTENSION));
        $mime = $this->guessMimeType($absolutePath, $ext);
        $kind = $this->detectKind($mime, $ext);

        $preparedPath = $absolutePath;
        $preparedName = $originalName;
        $tempCleanup = null;
        $repairedPdf = false;

        if ($kind === 'pdf') {
            $repaired = $this->repairPdfToTempIfNeeded($absolutePath, $originalName);
            if ($repaired) {
                $preparedPath = $repaired['path'];
                $preparedName = $repaired['name'];
                $tempCleanup = $repaired['path'];
                $repairedPdf = true;
            }
        }

        $text = '';
        $strategy = 'text_only';

        if ($kind === 'image') {
            $strategy = 'image_file';
        } elseif ($kind === 'pdf') {
            $strategy = 'pdf_file';
            $text = $this->extractLocalTextFromFile($preparedPath, $preparedName, 'application/pdf', 'pdf');
        } else {
            $text = $this->extractLocalTextFromFile($preparedPath, $preparedName, $mime, $ext);
        }

        return [
            'path'         => $preparedPath,
            'original_name'=> $preparedName,
            'mime'         => $mime,
            'ext'          => $ext,
            'kind'         => $kind,
            'strategy'     => $strategy,
            'text'         => $text,
            'repaired_pdf' => $repairedPdf,
            'temp_cleanup' => $tempCleanup,
        ];
    }

    private function buildUserExtractionPrompt(string $extraText = ''): string
    {
        $base = 'Extrae TODOS los renglones REALES de la tabla de conceptos en TODAS las páginas. No inventes. Devuelve JSON.';
        $extraText = trim($extraText);

        if ($extraText === '') {
            return $base;
        }

        $extraText = mb_substr($extraText, 0, 120000);

        return $base . "\n\nTexto local de apoyo extraído del archivo:\n" . $extraText;
    }

    private function extractUsingResponsesWithRetries(array $cfg, string $fileId, string $system, string $userText, string $inputType): array
    {
        $data1 = $this->callResponsesExtract(
            cfg: $cfg,
            fileId: $fileId,
            system: $system,
            userText: $userText,
            inputType: $inputType
        );

        $items = is_array($data1['items'] ?? null) ? $data1['items'] : [];
        $doc   = is_array($data1['document'] ?? null) ? $data1['document'] : [];
        $notes = is_array($data1['notes'] ?? null) ? $data1['notes'] : ['warnings' => [], 'confidence' => 0.0];

        $items = $this->filterAndDedupeExtractedItems($items);

        if (count($items) <= 1) {
            $already = $this->buildAlreadyExtractedHints($items, 20);

            $data2 = $this->callResponsesExtract(
                cfg: $cfg,
                fileId: $fileId,
                system: $system,
                userText: "Parece que faltan renglones. No repitas items.\nYa tengo:\n{$already}\n\nBusca en TODAS las páginas y devuelve SOLO items nuevos reales de la tabla. Si no hay, regresa items=[].",
                inputType: $inputType
            );

            $items2 = is_array($data2['items'] ?? null) ? $data2['items'] : [];
            $items2 = $this->filterAndDedupeExtractedItems($items2);

            $items = array_merge($items, $items2);
            $items = $this->filterAndDedupeExtractedItems($items);

            $notes['warnings'] = array_values(array_unique(array_merge(
                (array) ($notes['warnings'] ?? []),
                (array) data_get($data2, 'notes.warnings', [])
            )));
            $notes['confidence'] = max(
                (float) ($notes['confidence'] ?? 0),
                (float) data_get($data2, 'notes.confidence', 0)
            );

            if (empty($doc) && is_array($data2['document'] ?? null)) {
                $doc = $data2['document'];
            }
        }

        return [
            'document' => $doc,
            'items'    => $items,
            'notes'    => $notes,
        ];
    }

    private function extractFromTextOnly(array $cfg, string $system, string $originalName, string $extractedText): array
    {
        $data1 = $this->callResponsesExtractFromText(
            cfg: $cfg,
            system: $system,
            text: "Archivo: {$originalName}\n\n" . mb_substr($extractedText, 0, 120000)
        );

        $items = is_array($data1['items'] ?? null) ? $data1['items'] : [];
        $doc   = is_array($data1['document'] ?? null) ? $data1['document'] : [];
        $notes = is_array($data1['notes'] ?? null) ? $data1['notes'] : ['warnings' => [], 'confidence' => 0.0];

        $items = $this->filterAndDedupeExtractedItems($items);

        if (count($items) <= 1 && mb_strlen($extractedText) > 500) {
            $already = $this->buildAlreadyExtractedHints($items, 20);

            $data2 = $this->callResponsesExtractFromText(
                cfg: $cfg,
                system: $system,
                text: "Archivo: {$originalName}\n\nParece que faltan renglones. No repitas items.\nYa tengo:\n{$already}\n\nTexto del archivo:\n" . mb_substr($extractedText, 0, 120000)
            );

            $items2 = is_array($data2['items'] ?? null) ? $data2['items'] : [];
            $items2 = $this->filterAndDedupeExtractedItems($items2);
            $items = array_merge($items, $items2);
            $items = $this->filterAndDedupeExtractedItems($items);

            $notes['warnings'] = array_values(array_unique(array_merge(
                (array) ($notes['warnings'] ?? []),
                (array) data_get($data2, 'notes.warnings', [])
            )));
            $notes['confidence'] = max(
                (float) ($notes['confidence'] ?? 0),
                (float) data_get($data2, 'notes.confidence', 0)
            );

            if (empty($doc) && is_array($data2['document'] ?? null)) {
                $doc = $data2['document'];
            }
        }

        return [
            'document' => $doc,
            'items'    => $items,
            'notes'    => $notes,
        ];
    }

    private function finalizeAiExtractResult(array $data): array
    {
        $items = $this->filterAndDedupeExtractedItems((array) ($data['items'] ?? []));
        $doc   = is_array($data['document'] ?? null) ? $data['document'] : [];
        $notes = is_array($data['notes'] ?? null) ? $data['notes'] : ['warnings' => [], 'confidence' => 0.0];

        if (empty($items)) {
            $notes['warnings'] = array_values(array_unique(array_merge(
                (array) ($notes['warnings'] ?? []),
                ['La IA no detectó conceptos automáticamente.']
            )));
        }

        return [
            'document' => $doc ?: [
                'document_type'     => 'otro',
                'supplier_name'     => null,
                'counterparty_rfc'  => null,
                'uuid'              => null,
                'serie'             => null,
                'folio'             => null,
                'currency'          => 'MXN',
                'document_datetime' => null,
                'subtotal'          => 0,
                'tax'               => 0,
                'total'             => 0,
            ],
            'items' => $items,
            'notes' => $notes,
        ];
    }

    private function callResponsesExtractFromText(array $cfg, string $system, string $text): array
    {
        $lastStatus = null;
        $lastBody = null;
        $lastError = null;

        for ($attempt = 1; $attempt <= max(1, (int) $cfg['retries']); $attempt++) {
            try {
                $resp = Http::withToken($cfg['api_key'])
                    ->timeout((int) $cfg['timeout'])
                    ->withHeaders([
                        'Accept'       => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                    ->post($cfg['base_url'] . '/responses', [
                        'model'             => $cfg['model'],
                        'instructions'      => $system,
                        'input'             => [[
                            'role'    => 'user',
                            'content' => [
                                ['type' => 'input_text', 'text' => $text],
                            ],
                        ]],
                        'max_output_tokens' => 6500,
                    ]);

                if ($resp->successful()) {
                    return $this->parseOpenAiJsonFromResponses((array) $resp->json());
                }

                $lastStatus = $resp->status();
                $lastBody = $resp->body();
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }

            if ($attempt < (int) $cfg['retries']) {
                usleep(((int) $cfg['retry_sleep_ms']) * 1000);
            }
        }

        Log::warning('AI text responses error', [
            'status' => $lastStatus,
            'body'   => $lastBody,
            'error'  => $lastError,
        ]);

        throw new \RuntimeException('La IA respondió con error.');
    }

    private function repairPdfToTempIfNeeded(string $absolutePath, string $originalName): ?array
    {
        $head = @file_get_contents($absolutePath, false, null, 0, 4096);
        if (!is_string($head) || $head === '') {
            return null;
        }

        $offset = strpos($head, '%PDF-');
        if ($offset === false || $offset === 0) {
            return null;
        }

        $raw = @file_get_contents($absolutePath);
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $fullOffset = strpos($raw, '%PDF-');
        if ($fullOffset === false) {
            return null;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'pdf_fix_');
        if (!$tmp) {
            return null;
        }

        $tmpPdf = $tmp . '.pdf';
        @rename($tmp, $tmpPdf);
        @file_put_contents($tmpPdf, substr($raw, $fullOffset));

        return [
            'path' => $tmpPdf,
            'name' => preg_match('/\.pdf$/i', $originalName) ? $originalName : ($originalName . '.pdf'),
        ];
    }

    private function extractLocalTextFromFile(string $absolutePath, string $originalName, string $mime, string $ext): string
    {
        $ext = strtolower($ext);
        $textExt = ['txt', 'csv', 'tsv', 'json', 'xml', 'html', 'htm', 'md', 'log'];

        if (in_array($ext, $textExt, true) || str_starts_with((string) $mime, 'text/')) {
            return $this->readMostlyTextFile($absolutePath);
        }

        if ($ext === 'pdf' || $mime === 'application/pdf' || $mime === 'application/x-pdf') {
            $text = $this->extractPdfText($absolutePath);
            if ($text !== '') {
                return $text;
            }
        }

        if ($ext === 'docx') {
            return $this->extractDocxText($absolutePath);
        }

        if (in_array($ext, ['xlsx', 'xlsm'], true)) {
            return $this->extractXlsxText($absolutePath);
        }

        if (in_array($ext, ['csv', 'tsv'], true)) {
            return $this->readMostlyTextFile($absolutePath);
        }

        return $this->readMostlyTextFile($absolutePath);
    }

    private function readMostlyTextFile(string $absolutePath): string
    {
        $raw = @file_get_contents($absolutePath);
        if (!is_string($raw) || $raw === '') {
            return '';
        }

        if (!$this->looksTextual($raw)) {
            return '';
        }

        $enc = mb_detect_encoding($raw, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true) ?: 'UTF-8';
        $text = @mb_convert_encoding($raw, 'UTF-8', $enc);
        $text = is_string($text) ? $text : $raw;
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim((string) $text);
    }

    private function looksTextual(string $raw): bool
    {
        $sample = substr($raw, 0, 4096);
        if ($sample === '') {
            return false;
        }

        $len = strlen($sample);
        $printable = 0;

        for ($i = 0; $i < $len; $i++) {
            $ord = ord($sample[$i]);
            if (($ord >= 32 && $ord <= 126) || in_array($ord, [9, 10, 13], true) || $ord >= 128) {
                $printable++;
            }
        }

        return ($printable / max(1, $len)) >= 0.75;
    }

    private function extractPdfText(string $absolutePath): string
    {
        try {
            $text = $this->extractPdfTextWithLibrary($absolutePath);
            if ($text !== '') {
                return $text;
            }
        } catch (\Throwable $e) {
            Log::info('PDF local extract: library parser skipped', ['error' => $e->getMessage()]);
        }

        try {
            $text = $this->extractPdfTextWithPdftotext($absolutePath);
            if ($text !== '') {
                return $text;
            }
        } catch (\Throwable $e) {
            Log::info('PDF local extract: pdftotext skipped', ['error' => $e->getMessage()]);
        }

        try {
            return $this->extractPdfTextFromRawStreams($absolutePath);
        } catch (\Throwable $e) {
            Log::info('PDF local extract: raw stream fallback skipped', ['error' => $e->getMessage()]);
            return '';
        }
    }

    private function extractPdfTextWithLibrary(string $absolutePath): string
    {
        if (class_exists('\\Smalot\\PdfParser\\Parser')) {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($absolutePath);
            $text = trim((string) $pdf->getText());
            if ($text !== '') {
                return $this->normalizeExtractedText($text);
            }
        }

        return '';
    }

    private function extractPdfTextWithPdftotext(string $absolutePath): string
    {
        if (!$this->canUseShellExec()) {
            return '';
        }

        $pdftotext = trim((string) @\shell_exec('command -v pdftotext 2>/dev/null'));
        if ($pdftotext === '') {
            return '';
        }

        $tmp = tempnam(sys_get_temp_dir(), 'pdf_txt_');
        if (!$tmp) {
            return '';
        }

        $out = $tmp . '.txt';
        @\shell_exec(escapeshellcmd($pdftotext) . ' -layout ' . escapeshellarg($absolutePath) . ' ' . escapeshellarg($out) . ' 2>/dev/null');
        $text = @file_get_contents($out);
        @unlink($out);

        return is_string($text) ? $this->normalizeExtractedText($text) : '';
    }

    private function extractPdfTextFromRawStreams(string $absolutePath): string
    {
        $raw = @file_get_contents($absolutePath);
        if (!is_string($raw) || $raw === '') {
            return '';
        }

        $chunks = [];

        if (preg_match_all('/\(([^\)]{3,})\)/s', $raw, $m1) && !empty($m1[1])) {
            foreach ($m1[1] as $value) {
                $chunks[] = $value;
            }
        }

        if (preg_match_all('/<([0-9A-Fa-f]{6,})>/s', $raw, $m2) && !empty($m2[1])) {
            foreach ($m2[1] as $hex) {
                if (strlen($hex) % 2 !== 0) {
                    $hex .= '0';
                }
                $decoded = @hex2bin($hex);
                if (is_string($decoded) && $decoded !== '') {
                    $chunks[] = $decoded;
                }
            }
        }

        $text = implode("\n", $chunks);
        return $this->normalizeExtractedText($text);
    }

    private function canUseShellExec(): bool
    {
        if (!function_exists('shell_exec')) {
            return false;
        }

        $disabled = (string) ini_get('disable_functions');
        if ($disabled === '') {
            return true;
        }

        $disabledList = array_map('trim', explode(',', $disabled));
        return !in_array('shell_exec', $disabledList, true);
    }

    private function normalizeExtractedText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]+/u', ' ', $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = preg_replace('/\n{3,}/u', "\n\n", $text);
        return trim((string) $text);
    }

    private function extractDocxText(string $absolutePath): string
    {
        if (!class_exists('ZipArchive')) {
            return '';
        }

        $zip = new \ZipArchive();
        if ($zip->open($absolutePath) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();

        if (!is_string($xml) || $xml === '') {
            return '';
        }

        $xml = str_replace(['</w:p>', '</w:tr>', '</w:tc>'], ["\n", "\n", "\t"], $xml);
        $text = strip_tags($xml);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim((string) $text);
    }

    private function extractXlsxText(string $absolutePath): string
    {
        if (!class_exists('ZipArchive')) {
            return '';
        }

        $zip = new \ZipArchive();
        if ($zip->open($absolutePath) !== true) {
            return '';
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if (is_string($sharedXml) && $sharedXml !== '') {
            $sx = @simplexml_load_string($sharedXml);
            if ($sx) {
                foreach ($sx->si as $si) {
                    $parts = [];
                    if (isset($si->t)) {
                        $parts[] = (string) $si->t;
                    } else {
                        foreach ($si->r as $r) {
                            $parts[] = (string) $r->t;
                        }
                    }
                    $sharedStrings[] = implode('', $parts);
                }
            }
        }

        $texts = [];
        for ($i = 1; $i <= 20; $i++) {
            $sheetXml = $zip->getFromName("xl/worksheets/sheet{$i}.xml");
            if (!is_string($sheetXml) || $sheetXml === '') {
                continue;
            }

            $sx = @simplexml_load_string($sheetXml);
            if (!$sx || !isset($sx->sheetData)) {
                continue;
            }

            foreach ($sx->sheetData->row as $row) {
                $cells = [];
                foreach ($row->c as $c) {
                    $type = (string) ($c['t'] ?? '');
                    $value = (string) ($c->v ?? '');
                    if ($type === 's' && $value !== '' && isset($sharedStrings[(int) $value])) {
                        $cells[] = $sharedStrings[(int) $value];
                    } else {
                        $cells[] = $value;
                    }
                }
                $line = trim(implode("\t", array_filter($cells, static fn($v) => $v !== '')));
                if ($line !== '') {
                    $texts[] = $line;
                }
            }
        }

        $zip->close();

        return trim(implode("\n", $texts));
    }

    private function guessMimeType(string $absolutePath, string $ext = ''): string
    {
        $mime = '';

        try {
            $mime = (string) (mime_content_type($absolutePath) ?: '');
        } catch (\Throwable $e) {
            $mime = '';
        }

        if (($mime === '' || $mime === 'application/octet-stream') && function_exists('finfo_open')) {
            try {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $detected = finfo_file($finfo, $absolutePath);
                    finfo_close($finfo);
                    if (is_string($detected) && $detected !== '') {
                        $mime = $detected;
                    }
                }
            } catch (\Throwable $e) {
                //
            }
        }

        $ext = strtolower($ext);

        if (($mime === '' || $mime === 'application/octet-stream') && $ext === 'pdf') {
            return 'application/pdf';
        }

        if (($mime === '' || $mime === 'application/octet-stream') && in_array($ext, ['jpg', 'jpeg'], true)) {
            return 'image/jpeg';
        }

        if (($mime === '' || $mime === 'application/octet-stream') && $ext === 'png') {
            return 'image/png';
        }

        if (($mime === '' || $mime === 'application/octet-stream') && $ext === 'webp') {
            return 'image/webp';
        }

        return $mime !== '' ? $mime : 'application/octet-stream';
    }

    private function buildExtractorSystemPromptStrictTableOnly(string $category): string
    {
        $partyHint = $category === 'venta'
            ? "En VENTA: supplier_name debe ser el CLIENTE / RECEPTOR."
            : "En COMPRA: supplier_name debe ser el PROVEEDOR / EMISOR.";

        return <<<TXT
Eres un extractor experto de documentos contables de {$category} (México). El archivo puede tener VARIAS PÁGINAS.

{$partyHint}

A) CABECERA:
- Detecta: supplier_name, counterparty_rfc, uuid (si es CFDI), serie, folio, document_datetime, currency, subtotal, tax, total.
- Si no se ve, null. NO inventes.

B) TABLA DE CONCEPTOS (estricto):
- Extrae ÚNICAMENTE renglones REALES de la TABLA.
- Ignora sellos, firmas, anotaciones y textos fuera de tabla.
- NO inventes renglones. NO metas IVA como item.

JSON ÚNICAMENTE:

{
  "document": {
    "document_type": "ticket|factura|remision|otro",
    "supplier_name": null,
    "counterparty_rfc": null,
    "uuid": null,
    "serie": null,
    "folio": null,
    "currency": "MXN",
    "document_datetime": "YYYY-MM-DD HH:MM:SS",
    "subtotal": 0,
    "tax": 0,
    "total": 0
  },
  "items": [
    {
      "item_raw": "",
      "item_name": "",
      "qty": 1,
      "unit": "",
      "unit_price": null,
      "line_total": null,
      "ai_meta": { "prodserv": null }
    }
  ],
  "notes": { "warnings": [], "confidence": 0.0 }
}

Reglas:
- JSON únicamente, sin markdown.
- unit_price y line_total: número si se ve; si no se ve, null (NO 0).
- NO devuelvas filas “fantasma”.
TXT;
    }

    private function parseOpenAiJsonFromResponses(array $json): array
    {
        $rawText = '';

        if (isset($json['output']) && is_array($json['output'])) {
            foreach ($json['output'] as $outItem) {
                if (($outItem['type'] ?? null) === 'message' && isset($outItem['content'])) {
                    foreach ($outItem['content'] as $c) {
                        if (($c['type'] ?? null) === 'output_text' && isset($c['text'])) {
                            $rawText .= $c['text'];
                        }
                    }
                }
            }
        } elseif (isset($json['choices'][0]['message']['content'])) {
            $rawText = is_string($json['choices'][0]['message']['content'])
                ? $json['choices'][0]['message']['content']
                : json_encode($json['choices'][0]['message']['content'], JSON_UNESCAPED_UNICODE);
        }

        $rawText = trim((string) $rawText);
        $rawText = preg_replace('/^```json\s*|\s*```$/', '', $rawText);

        if ($rawText === '') {
            throw new \RuntimeException('No se pudo leer salida de IA.');
        }

        $data = json_decode($rawText, true);
        if (is_array($data)) {
            return $data;
        }

        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $rawText, $m)) {
            $data = json_decode((string) $m[0], true);
            if (is_array($data)) {
                return $data;
            }
        }

        Log::warning('AI invalid JSON', ['raw' => mb_substr($rawText, 0, 2000)]);
        throw new \RuntimeException('La IA no devolvió JSON válido.');
    }

    public function normalizeAiPurchase(array $payload): array
    {
        $doc = $payload['document'] ?? [];
        $items = $payload['items'] ?? [];

        $document = [
            'document_type'     => $doc['document_type'] ?? 'otro',
            'supplier_name'     => $doc['supplier_name'] ?? null,
            'counterparty_rfc'  => $doc['counterparty_rfc'] ?? null,
            'uuid'              => $doc['uuid'] ?? null,
            'serie'             => $doc['serie'] ?? null,
            'folio'             => $doc['folio'] ?? null,
            'currency'          => $doc['currency'] ?? 'MXN',
            'document_datetime' => $doc['document_datetime'] ?? null,
            'subtotal'          => $this->toMoney($doc['subtotal'] ?? 0),
            'tax'               => $this->toMoney($doc['tax'] ?? 0),
            'total'             => $this->toMoney($doc['total'] ?? 0),
        ];

        $items = $this->filterAndDedupeExtractedItems(is_array($items) ? $items : []);

        $sumLines = 0.0;
        $normItems = [];

        foreach ($items as $it) {
            $qty = $this->toQty($it['qty'] ?? 1);
            $uP  = $this->toMoney($it['unit_price'] ?? 0, 4);
            $lT  = $this->toMoney($it['line_total'] ?? 0);

            if ($lT <= 0 && $uP > 0 && $qty > 0) $lT = round($qty * $uP, 2);
            if ($uP <= 0 && $lT > 0 && $qty > 0) $uP = round($lT / $qty, 4);

            $sumLines += $lT;

            $normItems[] = [
                'item_raw'   => $it['item_raw'] ?? null,
                'item_name'  => $this->cleanText($it['item_name'] ?? ($it['item_raw'] ?? '')),
                'qty'        => $qty,
                'unit'       => $it['unit'] ?? null,
                'unit_price' => $uP,
                'line_total' => $lT,
                'ai_meta'    => $it['ai_meta'] ?? null,
            ];
        }

        if (($document['total'] ?? 0) <= 0 && $sumLines > 0) {
            $document['total'] = round($sumLines, 2);
        }

        $stats = [
            'items_count' => count($normItems),
            'sum_lines'   => round($sumLines, 2),
            'avg_ticket'  => count($normItems) ? round($sumLines / max(1, count($normItems)), 2) : 0,
            'top_items'   => $this->topItems($normItems, 8),
        ];

        return [
            'document' => $document,
            'items'    => $normItems,
            'stats'    => $stats,
            'notes'    => $payload['notes'] ?? null,
        ];
    }

    public function persistPurchaseDocumentFromAi(
        array $normalized,
        ?int $publicationId = null,
        string $category = 'compra',
        array $accountingContext = []
    ): PurchaseDocument {
        $norm = $this->normalizeAiPurchase($normalized);

        $doc = $norm['document'] ?? [];
        $items = $norm['items'] ?? [];

        $cfdi = [
            'party_rfc' => $doc['counterparty_rfc'] ?? null,
            'uuid'      => $doc['uuid'] ?? null,
            'serie'     => $doc['serie'] ?? null,
            'folio'     => $doc['folio'] ?? null,
        ];

        $accounting = $this->normalizeAccountingContext(
            $accountingContext,
            $category,
            $doc['document_datetime'] ?? null
        );

        $uuid = is_string($cfdi['uuid'] ?? null) ? trim((string)$cfdi['uuid']) : '';
        if ($uuid !== '') {
            $exists = PurchaseDocument::query()
                ->where('ai_meta->cfdi->uuid', $uuid)
                ->first();

            if ($exists) {
                if ($category === 'venta' && !empty($accounting)) {
                    $this->mergeAccountingIntoExistingPurchaseDocument($exists, $accounting);
                }
                return $exists;
            }
        }

        $aiMeta = [
            'notes' => $norm['notes'] ?? null,
            'stats' => $norm['stats'] ?? null,
            'cfdi'  => $cfdi,
        ];

        if (!empty($accounting)) {
            $aiMeta['accounting'] = $accounting;
        }

        $purchase = PurchaseDocument::create([
            'publication_id'    => $publicationId,
            'created_by'        => Auth::id(),
            'source_kind'       => 'upload',
            'category'          => $category,
            'document_type'     => $doc['document_type'] ?? 'otro',
            'supplier_name'     => $doc['supplier_name'] ?? null,
            'currency'          => $doc['currency'] ?? 'MXN',
            'document_datetime' => $doc['document_datetime'] ?: null,
            'subtotal'          => $this->toMoney($doc['subtotal'] ?? 0),
            'tax'               => $this->toMoney($doc['tax'] ?? 0),
            'total'             => $this->toMoney($doc['total'] ?? 0),
            'ai_meta'           => $aiMeta,
        ]);

        foreach ($items as $it) {
            PurchaseItem::create([
                'purchase_document_id' => $purchase->id,
                'item_name'   => $it['item_name'] ?? null,
                'item_raw'    => $it['item_raw'] ?? null,
                'unit'        => $it['unit'] ?? null,
                'qty'         => (float)($it['qty'] ?? 1),
                'unit_price'  => (float)($it['unit_price'] ?? 0),
                'line_total'  => (float)($it['line_total'] ?? 0),
                'ai_meta'     => $it['ai_meta'] ?? null,
            ]);
        }

        return $purchase;
    }

    private function normalizeAccountingContext(
        array $context,
        string $category,
        ?string $documentDatetime = null
    ): array {
        if ($category !== 'venta') {
            return [];
        }

        $companyId = (int) ($context['company_id'] ?? 0);
        if ($companyId <= 0) {
            return [];
        }

        $creditDays = max((int) ($context['credit_days'] ?? 15), 0);

        $issueDate = now()->toDateString();
        try {
            if (!empty($documentDatetime)) {
                $issueDate = Carbon::parse($documentDatetime)->toDateString();
            }
        } catch (\Throwable $e) {
            //
        }

        $dueDate = null;
        if (!empty($context['due_date'])) {
            try {
                $dueDate = Carbon::parse($context['due_date'])->toDateString();
            } catch (\Throwable $e) {
                $dueDate = null;
            }
        }

        if (!$dueDate) {
            $dueDate = Carbon::parse($issueDate)->addDays($creditDays)->toDateString();
        }

        return [
            'company_id' => $companyId,
            'due_date' => $dueDate,
            'credit_days' => $creditDays,
            'amount_paid' => (float) ($context['amount_paid'] ?? 0),
            'status' => (string) ($context['status'] ?? 'pendiente'),
            'priority' => (string) ($context['priority'] ?? 'media'),
            'collection_status' => (string) ($context['collection_status'] ?? 'sin_gestion'),
            'reminder_days_before' => (int) ($context['reminder_days_before'] ?? 5),
            'interest_rate' => (float) ($context['interest_rate'] ?? 0),
            'assigned_to' => $context['assigned_to'] ?? null,
            'notes' => $context['notes'] ?? null,
            'tags' => $context['tags'] ?? null,
            'linked_from' => 'publications',
        ];
    }

    private function mergeAccountingIntoExistingPurchaseDocument(PurchaseDocument $doc, array $accounting): void
    {
        $aiMeta = is_array($doc->ai_meta) ? $doc->ai_meta : [];
        $current = (array) data_get($aiMeta, 'accounting', []);

        $merged = array_merge($current, array_filter(
            $accounting,
            fn ($v) => $v !== null && $v !== ''
        ));

        data_set($aiMeta, 'accounting', $merged);

        $doc->ai_meta = $aiMeta;
        $doc->save();
    }

    private function filterAndDedupeExtractedItems(array $items): array
    {
        $clean = [];

        foreach ($items as $it) {
            if (!is_array($it)) continue;

            $raw  = trim((string)($it['item_raw'] ?? ''));
            $name = trim((string)($it['item_name'] ?? ''));

            if ($raw === '' && $name === '') continue;
            if ($this->looksLikeTaxLine($raw) || $this->looksLikeTaxLine($name)) continue;

            $qty = $this->toQty($it['qty'] ?? 1);

            $unitPrice = $it['unit_price'] ?? null;
            $lineTotal = $it['line_total'] ?? null;

            $uP = (is_null($unitPrice) || $unitPrice === '') ? null : $this->toMoney($unitPrice, 4);
            $lT = (is_null($lineTotal) || $lineTotal === '') ? null : $this->toMoney($lineTotal, 2);

            if (($uP === null || $uP <= 0) || ($lT === null || $lT <= 0)) {
                $vals = $this->moneyValuesFromText($raw);
                if (count($vals) >= 2) {
                    if ($uP === null || $uP <= 0) $uP = round($vals[count($vals)-2], 4);
                    if ($lT === null || $lT <= 0) $lT = round($vals[count($vals)-1], 2);
                }
            }

            $prodserv = $it['ai_meta']['prodserv'] ?? null;
            $prodserv = is_string($prodserv) ? trim($prodserv) : null;

            $hasUP = (!is_null($uP) && $uP > 0);
            $hasLT = (!is_null($lT) && $lT > 0);

            if (!$hasUP && !$hasLT) continue;

            if ($hasUP && $hasLT) {
                $calc = round($qty * $uP, 2);
                $diff = abs($calc - $lT);
                $tol = max(1.00, $lT * 0.02);
                if ($diff > $tol) continue;
            } else {
                if ($hasLT && !$hasUP && $qty > 0) $uP = round($lT / $qty, 4);
                if ($hasUP && !$hasLT && $qty > 0) $lT = round($qty * $uP, 2);
            }

            $clean[] = [
                'item_raw'   => $raw ?: $name,
                'item_name'  => $this->cleanText($name ?: $raw),
                'qty'        => $qty,
                'unit'       => $it['unit'] ?? null,
                'unit_price' => $uP,
                'line_total' => $lT,
                'ai_meta'    => ['prodserv' => $prodserv],
            ];
        }

        $seen = [];
        $out = [];
        foreach ($clean as $it) {
            $k = md5(mb_strtolower(
                trim((string)$it['item_name']) . '|' .
                (string)$it['qty'] . '|' .
                (string)$it['unit_price'] . '|' .
                (string)$it['line_total']
            ));
            if (isset($seen[$k])) continue;
            $seen[$k] = true;
            $out[] = $it;
        }

        return $out;
    }

    private function topItems(array $items, int $limit = 8): array
    {
        $agg = [];
        foreach ($items as $it) {
            $key = mb_strtolower(trim($it['item_name'] ?? ''));
            if ($key === '') continue;
            if (!isset($agg[$key])) {
                $agg[$key] = ['item_name' => $it['item_name'], 'qty' => 0, 'spent' => 0];
            }
            $agg[$key]['qty']   += (float)($it['qty'] ?? 0);
            $agg[$key]['spent'] += (float)($it['line_total'] ?? 0);
        }

        usort($agg, fn($a,$b) => $b['spent'] <=> $a['spent']);
        return array_slice(array_values($agg), 0, $limit);
    }

    private function buildAlreadyExtractedHints(array $items, int $limit = 20): string
    {
        if (empty($items)) return '(vacío)';
        $slice = array_slice($items, 0, $limit);

        $lines = [];
        foreach ($slice as $it) {
            if (!is_array($it)) continue;
            $raw = trim((string)($it['item_raw'] ?? $it['item_name'] ?? ''));
            $qty = (string)($it['qty'] ?? '');
            $up  = (string)($it['unit_price'] ?? '');
            $lt  = (string)($it['line_total'] ?? '');
            $raw = mb_substr($raw, 0, 140);
            $lines[] = "- {$raw} | qty={$qty} up={$up} total={$lt}";
        }

        return implode("\n", $lines);
    }

    private function moneyValuesFromText(?string $text): array
    {
        $text = (string)$text;
        if ($text === '') return [];

        preg_match_all('/\$?\s*([0-9]{1,3}(?:,[0-9]{3})*(?:\.[0-9]{2})|[0-9]+(?:\.[0-9]{2}))/u', $text, $m);

        $vals = [];
        foreach (($m[1] ?? []) as $v) {
            $v = str_replace(',', '', $v);
            if (is_numeric($v)) $vals[] = (float)$v;
        }
        return $vals;
    }

    private function looksLikeTaxLine(?string $raw): bool
    {
        $raw = mb_strtolower((string)$raw);
        return str_contains($raw, 'iva') || str_contains($raw, 'impuesto') || str_contains($raw, 'tax');
    }

    private function toMoney($val, int $decimals = 2): float
    {
        if (is_null($val)) return 0.0;

        if (is_string($val)) {
            $clean = preg_replace('/[^0-9.,-]/', '', $val);
            $clean = str_replace(',', '', $clean);
            if (!is_numeric($clean)) return 0.0;
            $val = (float)$clean;
        }

        if (!is_numeric($val)) return 0.0;
        return round((float)$val, $decimals);
    }

    private function toQty($val): float
    {
        if (is_null($val) || $val === '') return 1.0;

        if (is_string($val)) {
            $clean = preg_replace('/[^0-9.,-]/', '', $val);
            $clean = str_replace(',', '.', $clean);
            if (!is_numeric($clean)) return 1.0;
            $val = (float)$clean;
        }

        $q = (float)$val;
        if ($q <= 0) $q = 1.0;
        return round($q, 3);
    }

    private function cleanText(string $s): string
    {
        $s = trim(preg_replace('/\s+/', ' ', $s));
        return mb_substr($s, 0, 255);
    }

    private function detectKind(?string $mime, string $ext): string
    {
        $mime = (string) $mime;
        $ext = strtolower($ext);

        if (str_starts_with($mime, 'image/')) return 'image';
        if (str_starts_with($mime, 'video/')) return 'video';
        if (in_array($mime, ['application/pdf', 'application/x-pdf'], true) || $ext === 'pdf') return 'pdf';

        $textExt = ['txt', 'csv', 'tsv', 'json', 'xml', 'html', 'htm', 'md', 'log'];
        $docExt = ['doc', 'docx', 'odt', 'rtf'];
        $xlsExt = ['xls', 'xlsx', 'xlsm', 'ods'];

        if (str_starts_with($mime, 'text/') || in_array($ext, $textExt, true)) return 'text';
        if (in_array($ext, $docExt, true)) return 'doc';
        if (in_array($ext, $xlsExt, true)) return 'sheet';

        return 'file';
    }
}