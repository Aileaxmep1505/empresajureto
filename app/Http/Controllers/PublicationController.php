<?php

namespace App\Http\Controllers;

use App\Models\AccountReceivable;
use App\Models\AgendaEvent;
use App\Models\Company;
use App\Models\Publication;
use App\Models\PurchaseDocument;
use App\Services\Accounting\AccountStateService;
use App\Services\Publications\PublicationPurchaseAiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PublicationController extends Controller
{
    public function __construct(
        protected PublicationPurchaseAiService $svc,
        protected AccountStateService $state
    ) {}

    public function index()
    {
        $data = $this->svc->buildIndexData();
        return view('publications.index', $data);
    }

    public function create()
    {
        $companies = Company::query()->orderBy('name')->get();
        return view('publications.create', compact('companies'));
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

        $category = (string) $request->input('category', 'compra');

        $request->validate([
            'company_id' => $category === 'venta'
                ? ['required', 'exists:companies,id']
                : ['nullable', 'exists:companies,id'],
            'due_date' => ['nullable', 'date'],
            'credit_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['nullable', 'in:alta,media,baja'],
            'collection_status' => ['nullable', 'in:sin_gestion,en_gestion,promesa_pago,litigio,incobrable'],
            'reminder_days_before' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        $result = $this->svc->storeFromRequest($request);

        if ($category === 'venta') {
            try {
                $this->syncReturnedSaleDocuments((array) $result);
            } catch (\Throwable $e) {
                Log::warning('Publications store: no se pudo sincronizar cuentas por cobrar de venta', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

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

        $linkedReceivables = collect();

        if ($purchaseDocs->isNotEmpty()) {
            $linkedReceivables = AccountReceivable::query()
                ->where('source_type', PurchaseDocument::class)
                ->whereIn('source_id', $purchaseDocs->pluck('id')->all())
                ->get()
                ->keyBy('source_id');
        }

        return view('publications.show', compact('publication', 'purchaseDocs', 'linkedReceivables'));
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
                'mime'      => $file?->getClientMimeType(),
                'ext'       => $file?->getClientOriginalExtension(),
                'size'      => $file?->getSize(),
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
        $category = (string) $request->input('category', 'compra');

        $request->validate(
            array_merge(
                $this->svc->saveRules(),
                [
                    'company_id' => $category === 'venta'
                        ? ['required', 'exists:companies,id']
                        : ['nullable', 'exists:companies,id'],
                    'due_date' => ['nullable', 'date'],
                    'credit_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
                    'amount_paid' => ['nullable', 'numeric', 'min:0'],
                    'priority' => ['nullable', 'in:alta,media,baja'],
                    'collection_status' => ['nullable', 'in:sin_gestion,en_gestion,promesa_pago,litigio,incobrable'],
                    'reminder_days_before' => ['nullable', 'integer', 'min:0', 'max:365'],
                    'interest_rate' => ['nullable', 'numeric', 'min:0'],
                    'assigned_to' => ['nullable', 'string', 'max:255'],
                    'notes' => ['nullable', 'string'],
                    'tags' => ['nullable', 'array'],
                    'tags.*' => ['string', 'max:50'],
                ]
            ),
            $this->svc->validationMessages(),
            array_merge(
                $this->svc->validationAttributes(),
                [
                    'company_id' => 'compañía',
                    'due_date' => 'fecha de vencimiento',
                    'credit_days' => 'días de crédito',
                    'amount_paid' => 'monto pagado',
                    'priority' => 'prioridad',
                    'collection_status' => 'estado de cobranza',
                    'reminder_days_before' => 'días previos para recordatorio',
                    'interest_rate' => 'tasa de interés',
                    'assigned_to' => 'asignado a',
                    'notes' => 'notas',
                    'tags' => 'etiquetas',
                ]
            )
        );

        $payload = (array) $request->input('payload', []);
        $publicationId = $request->input('publication_id');

        try {
            $doc = $this->svc->saveExtractedPayload(
                $payload,
                $publicationId,
                $category,
                [
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
                ]
            );

            $receivable = null;

            if ($category === 'venta') {
                $receivable = $this->syncReceivableFromPurchaseDocument($doc);
            }

            return response()->json([
                'ok' => true,
                'purchase_document_id' => $doc->id,
                'account_receivable_id' => $receivable?->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Publications AI save failed', [
                'publication_id' => $publicationId,
                'error'          => $e->getMessage(),
            ]);

            return response()->json(['error' => 'No se pudo guardar.'], 500);
        }
    }

    private function syncReturnedSaleDocuments(array $result): void
    {
        $ids = array_values(array_filter(array_map(
            'intval',
            (array) ($result['created_purchase_document_ids'] ?? [])
        )));

        if (empty($ids)) {
            return;
        }

        $docs = PurchaseDocument::query()
            ->whereIn('id', $ids)
            ->where('category', 'venta')
            ->get();

        foreach ($docs as $doc) {
            $this->syncReceivableFromPurchaseDocument($doc);
        }
    }

    private function syncReceivableFromPurchaseDocument(PurchaseDocument $doc): ?AccountReceivable
    {
        if ((string) $doc->category !== 'venta') {
            return null;
        }

        $aiMeta = is_array($doc->ai_meta) ? $doc->ai_meta : [];
        $cfdi = (array) data_get($aiMeta, 'cfdi', []);
        $accounting = (array) data_get($aiMeta, 'accounting', []);

        $companyId = (int) ($accounting['company_id'] ?? 0);
        if ($companyId <= 0) {
            throw new \RuntimeException('No se encontró company_id para ligar la venta a cuentas por cobrar.');
        }

        $issueDate = $this->resolveIssueDateFromPurchaseDocument($doc);

        $creditDays = max((int) ($accounting['credit_days'] ?? 15), 0);
        $dueDate = !empty($accounting['due_date'])
            ? Carbon::parse($accounting['due_date'])->toDateString()
            : Carbon::parse($issueDate)->addDays($creditDays)->toDateString();

        $amount = (float) ($doc->total ?? 0);
        $amountPaid = (float) ($accounting['amount_paid'] ?? 0);

        $status = $this->normalizeReceivableStatus(
            (string) ($accounting['status'] ?? 'pendiente'),
            $amount,
            $amountPaid
        );

        $serie = trim((string) ($cfdi['serie'] ?? ''));
        $folioOnly = trim((string) ($cfdi['folio'] ?? ''));
        $folio = trim(($serie !== '' ? $serie.' ' : '').$folioOnly);

        if ($folio === '') {
            $folio = 'VENTA-'.$doc->id;
        }

        $clientName = trim((string) ($doc->supplier_name ?? ''));
        if ($clientName === '') {
            $clientName = 'Cliente venta';
        }

        $description = 'Documento de venta generado desde Publicaciones';
        if (!empty($doc->document_type)) {
            $description .= ' · Tipo: '.$doc->document_type;
        }

        $receivable = AccountReceivable::query()->firstOrNew([
            'source_type' => PurchaseDocument::class,
            'source_id'   => (int) $doc->id,
        ]);

        $data = [
            'company_id'           => $companyId,

            'source_module'        => 'publications_venta',
            'source_type'          => PurchaseDocument::class,
            'source_id'            => (int) $doc->id,
            'source_folio'         => $folio,

            'client_name'          => $clientName,
            'client_id'            => $cfdi['party_rfc'] ?? null,
            'folio'                => $folio,
            'description'          => $description,

            'document_type'        => $this->mapPurchaseDocumentTypeToReceivable((string) ($doc->document_type ?? 'otro')),
            'category'             => 'factura',

            'amount'               => $amount,
            'amount_paid'          => $amountPaid,
            'currency'             => (string) ($doc->currency ?: 'MXN'),

            'issue_date'           => $issueDate,
            'due_date'             => $dueDate,
            'paid_at'              => $status === 'cobrado' ? now()->toDateString() : null,

            'status'               => $status,
            'priority'             => (string) ($accounting['priority'] ?? 'media'),

            'payment_method'       => null,
            'bank_reference'       => null,

            'credit_days'          => $creditDays,
            'interest_rate'        => (float) ($accounting['interest_rate'] ?? 0),

            'assigned_to'          => $accounting['assigned_to'] ?? null,
            'collection_status'    => (string) ($accounting['collection_status'] ?? 'sin_gestion'),

            'notes'                => $this->buildReceivableNotesFromPurchaseDocument($doc, $cfdi, $accounting),
            'reminder_days_before' => (int) ($accounting['reminder_days_before'] ?? 5),
            'tags'                 => $accounting['tags'] ?? null,

            'title'                => 'Venta '.$folio,
            'reference'            => $cfdi['uuid'] ?? $folio,
            'invoice_number'       => $folio,
            'vendor_name'          => null,
            'frequency'            => 'one_time',
        ];

        $data = $this->filterToExistingColumns($data);

        if (!$receivable->exists) {
            $data['created_by'] = Auth::user()?->email;
        }

        $receivable->fill($data);
        $receivable->save();

        $this->state->recalc('receivable', $receivable->id);
        $this->syncAgendaForReceivable($receivable->fresh('company'));

        return $receivable;
    }

    private function buildReceivableNotesFromPurchaseDocument(
        PurchaseDocument $doc,
        array $cfdi = [],
        array $accounting = []
    ): string {
        $notes = [
            'Generado automáticamente desde Publicaciones > Venta.',
            'PurchaseDocument ID: '.$doc->id,
        ];

        if (!empty($cfdi['uuid'])) {
            $notes[] = 'UUID: '.$cfdi['uuid'];
        }

        if (!empty($cfdi['party_rfc'])) {
            $notes[] = 'RFC cliente: '.$cfdi['party_rfc'];
        }

        if (!empty($doc->supplier_name)) {
            $notes[] = 'Cliente: '.$doc->supplier_name;
        }

        if (!empty($doc->document_datetime)) {
            try {
                $notes[] = 'Fecha documento: '.Carbon::parse($doc->document_datetime)->format('d/m/Y H:i');
            } catch (\Throwable $e) {
                //
            }
        }

        if (!empty($accounting['notes'])) {
            $notes[] = 'Notas: '.$accounting['notes'];
        }

        return mb_substr(implode("\n", $notes), 0, 1900);
    }

    private function resolveIssueDateFromPurchaseDocument(PurchaseDocument $doc): string
    {
        try {
            if (!empty($doc->document_datetime)) {
                return Carbon::parse($doc->document_datetime)->toDateString();
            }
        } catch (\Throwable $e) {
            //
        }

        try {
            if (!empty($doc->created_at)) {
                return Carbon::parse($doc->created_at)->toDateString();
            }
        } catch (\Throwable $e) {
            //
        }

        return now()->toDateString();
    }

    private function normalizeReceivableStatus(string $status, float $amount, float $amountPaid): string
    {
        $status = match ($status) {
            'pending'   => 'pendiente',
            'partial'   => 'parcial',
            'paid'      => 'cobrado',
            'overdue'   => 'vencido',
            'cancelled' => 'cancelado',
            default     => $status,
        };

        if ($status === 'cancelado') {
            return 'cancelado';
        }

        if ($amount > 0 && $amountPaid >= $amount) {
            return 'cobrado';
        }

        if ($amountPaid > 0 && $amountPaid < $amount) {
            return 'parcial';
        }

        return 'pendiente';
    }

    private function mapPurchaseDocumentTypeToReceivable(string $documentType): string
    {
        return match ($documentType) {
            'factura' => 'factura',
            'ticket' => 'factura',
            'remision' => 'factura',
            default => 'factura',
        };
    }

    private function filterToExistingColumns(array $data): array
    {
        $model = new AccountReceivable();
        $table = $model->getTable();
        $columns = Schema::getColumnListing($table);

        return array_filter(
            $data,
            fn ($key) => in_array($key, $columns, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function syncAgendaForReceivable(AccountReceivable $receivable): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        $event = $this->findAgendaEventForReceivable($receivable);

        if (
            empty($receivable->due_date) ||
            in_array((string) $receivable->status, ['cobrado', 'cancelado'], true)
        ) {
            if ($event) {
                $event->delete();
            }
            return;
        }

        $startAt = Carbon::parse($receivable->due_date, 'America/Mexico_City')->setTime(9, 0, 0);

        $title = 'Cobro';
        if (!empty($receivable->client_name)) {
            $title .= ': '.$receivable->client_name;
        }
        if (!empty($receivable->folio)) {
            $title .= ' · Folio '.$receivable->folio;
        }

        $companyName = $receivable->relationLoaded('company')
            ? ($receivable->company?->name ?? '—')
            : (Company::query()->whereKey($receivable->company_id)->value('name') ?? '—');

        $marker = $this->agendaMarkerForReceivable($receivable->id);

        $descriptionParts = array_filter([
            'Evento generado automáticamente desde Publicaciones / Venta.',
            'ID cobro: '.$receivable->id,
            'Compañía: '.$companyName,
            'Cliente: '.($receivable->client_name ?: '—'),
            !empty($receivable->folio) ? 'Folio: '.$receivable->folio : null,
            'Monto: $'.number_format((float) $receivable->amount, 2).' '.($receivable->currency ?: 'MXN'),
            'Saldo actual: $'.number_format(max((float) $receivable->amount - (float) $receivable->amount_paid, 0), 2).' '.($receivable->currency ?: 'MXN'),
            'Vencimiento: '.$startAt->format('d/m/Y H:i'),
            !empty($receivable->collection_status) ? 'Estado cobranza: '.$receivable->collection_status : null,
            !empty($receivable->description) ? 'Descripción: '.$receivable->description : null,
            !empty($receivable->notes) ? 'Notas: '.$receivable->notes : null,
            $marker,
        ]);

        $data = [
            'title' => mb_substr($title, 0, 180),
            'description' => mb_substr(implode("\n", $descriptionParts), 0, 2000),
            'start_at' => $startAt->format('Y-m-d H:i:s'),
            'timezone' => 'America/Mexico_City',
            'repeat_rule' => 'none',
            'remind_offset_minutes' => max(((int) ($receivable->reminder_days_before ?? 5)) * 1440, 1),
            'user_ids' => [$userId],
            'send_email' => true,
            'send_whatsapp' => true,
        ];

        if ($event) {
            $event->fill($data);
        } else {
            $event = new AgendaEvent($data);
        }

        $event->computeNextReminder();
        $event->save();
    }

    private function findAgendaEventForReceivable(AccountReceivable $receivable): ?AgendaEvent
    {
        $marker = $this->agendaMarkerForReceivable($receivable->id);

        return AgendaEvent::query()
            ->where('description', 'like', '%'.$marker.'%')
            ->latest('id')
            ->first();
    }

    private function agendaMarkerForReceivable(int $receivableId): string
    {
        return '[AUTO_RECEIVABLE_ID:'.$receivableId.']';
    }
}