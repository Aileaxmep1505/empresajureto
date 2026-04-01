<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountReceivable;
use App\Models\AgendaEvent;
use App\Models\Company;
use App\Services\Accounting\AccountStateService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ReceivableController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('auth')];
    }

    public function __construct(private AccountStateService $state)
    {
        //
    }

    public function index(Request $request)
    {
        $companies = Company::orderBy('name')->get();

        $q = AccountReceivable::query()->with('company')->orderByDesc('due_date');

        if ($request->filled('company_id')) {
            $q->where('company_id', (int) $request->company_id);
        }

        $email = Auth::user()?->email;
        if ($email) {
            $q->where('created_by', $email);
        }

        $scope = $request->get('scope');
        $today = Carbon::today();

        if ($scope === 'open') {
            $q->whereNotIn('status', ['cobrado', 'cancelado']);
        }

        if ($scope === 'overdue') {
            $q->whereNotIn('status', ['cobrado', 'cancelado'])
              ->whereDate('due_date', '<', $today);
        }

        if ($scope === 'upcoming') {
            $q->whereNotIn('status', ['cobrado', 'cancelado'])
              ->whereDate('due_date', '>=', $today)
              ->whereDate('due_date', '<=', $today->copy()->addDays(15));
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $status = $this->normalizeStatus((string) $request->status);
            $q->where('status', $status);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $q->where(function ($w) use ($s) {
                $w->where('client_name', 'like', "%{$s}%")
                  ->orWhere('folio', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%");
            });
        }

        $items = $q->paginate(20)->withQueryString();

        $all = (clone $q)->get();
        $totPending = $all->sum(fn ($r) => max((float) $r->amount - (float) $r->amount_paid, 0));
        $totOverdue = $all->where('status', 'vencido')->sum(fn ($r) => max((float) $r->amount - (float) $r->amount_paid, 0));

        return view('accounting.receivables.index', compact('items', 'companies', 'totPending', 'totOverdue'));
    }

    public function create(Request $request)
    {
        $companies = Company::orderBy('name')->get();
        $presetCompanyId = $request->get('company_id');

        $item = new AccountReceivable([
            'issue_date' => now()->toDateString(),
            'currency' => 'MXN',
            'priority' => 'media',
            'status' => 'pendiente',
            'document_type' => 'factura',
            'category' => 'factura',
            'reminder_days_before' => 5,
            'company_id' => $presetCompanyId,
            'amount_paid' => 0,
            'collection_status' => 'sin_gestion',
        ]);

        return view('accounting.receivables.create', compact('item', 'companies'));
    }

    public function store(Request $request)
    {
        $data = $this->prepareReceivableData($request);
        $data['created_by'] = Auth::user()?->email;

        [$docs, $names] = $this->storeDocuments($request, 'accounting/receivables');
        if ($docs) {
            $data['documents'] = $docs;
        }
        if ($names) {
            $data['document_names'] = $names;
        }

        $evidenceUrl = $this->storeEvidence($request, 'accounting/receivables/evidence');
        if ($evidenceUrl) {
            $data['evidence_url'] = $evidenceUrl;
        }

        $data = $this->filterToExistingColumns($data);

        $r = AccountReceivable::create($data);
        $this->state->recalc('receivable', $r->id);

        try {
            $this->syncAgendaForReceivable($r->fresh('company'));
        } catch (\Throwable $e) {
            Log::warning('No se pudo sincronizar agenda para receivable '.$r->id.': '.$e->getMessage());
        }

        return redirect()
            ->route('accounting.receivables.show', $r)
            ->with('success', 'Cuenta por cobrar creada y agregada a la agenda.');
    }

    public function show(AccountReceivable $receivable)
    {
        $this->assertOwner($receivable);

        $receivable->load(['company', 'movements']);
        return view('accounting.receivables.show', ['item' => $receivable]);
    }

    public function edit(AccountReceivable $receivable)
    {
        $this->assertOwner($receivable);

        $companies = Company::orderBy('name')->get();
        return view('accounting.receivables.edit', ['item' => $receivable, 'companies' => $companies]);
    }

    public function update(Request $request, AccountReceivable $receivable)
    {
        $this->assertOwner($receivable);

        $data = $this->prepareReceivableData($request);

        $docs = is_array($receivable->documents) ? $receivable->documents : [];
        $names = is_array($receivable->document_names) ? $receivable->document_names : [];

        [$newDocs, $newNames] = $this->storeDocuments($request, 'accounting/receivables');
        if ($newDocs) {
            $docs = array_merge($docs, $newDocs);
        }
        if ($newNames) {
            $names = array_merge($names, $newNames);
        }

        $data['documents'] = $docs ?: null;
        $data['document_names'] = $names ?: null;

        $evidenceUrl = $this->storeEvidence($request, 'accounting/receivables/evidence');
        if ($evidenceUrl) {
            $data['evidence_url'] = $evidenceUrl;
        }

        $data = $this->filterToExistingColumns($data);

        $receivable->update($data);
        $this->state->recalc('receivable', $receivable->id);

        try {
            $this->syncAgendaForReceivable($receivable->fresh('company'));
        } catch (\Throwable $e) {
            Log::warning('No se pudo actualizar agenda para receivable '.$receivable->id.': '.$e->getMessage());
        }

        return redirect()
            ->route('accounting.receivables.show', $receivable)
            ->with('success', 'Cuenta por cobrar actualizada y sincronizada con agenda.');
    }

    public function destroy(AccountReceivable $receivable)
    {
        $this->assertOwner($receivable);

        try {
            $this->deleteAgendaForReceivable($receivable);
        } catch (\Throwable $e) {
            Log::warning('No se pudo eliminar agenda para receivable '.$receivable->id.': '.$e->getMessage());
        }

        $receivable->delete();

        return redirect()
            ->route('accounting.receivables.index')
            ->with('success', 'Cuenta por cobrar eliminada.');
    }

    private function prepareReceivableData(Request $request): array
    {
        $validated = $this->validateReceivable($request);

        $amount = (float) ($validated['amount'] ?? 0);
        $amountPaid = (float) ($validated['amount_paid'] ?? 0);

        $status = $this->normalizeStatus((string) ($validated['status'] ?? 'pendiente'));
        $category = $this->normalizeCategory((string) ($validated['category'] ?? 'factura'));
        $frequency = $this->normalizeFrequency((string) ($validated['frequency'] ?? 'monthly'));

        if ($status === 'cobrado' && $amountPaid <= 0) {
            $amountPaid = $amount;
        }

        if ($amount > 0 && $amountPaid >= $amount && $status !== 'cancelado') {
            $status = 'cobrado';
        } elseif ($amountPaid > 0 && $amountPaid < $amount && $status === 'pendiente') {
            $status = 'parcial';
        }

        $title = trim((string) ($validated['title'] ?? ''));
        $clientName = trim((string) ($validated['client_name'] ?? $title));
        $reference = trim((string) ($validated['reference'] ?? ''));
        $invoiceNumber = trim((string) ($validated['invoice_number'] ?? ''));
        $folio = trim((string) ($validated['folio'] ?? ($invoiceNumber !== '' ? $invoiceNumber : $reference)));

        $description = isset($validated['description']) ? trim((string) $validated['description']) : '';
        if ($description === '') {
            $description = $title;
        }

        $paidAt = $validated['paid_at'] ?? null;
        if ($status === 'cobrado' && empty($paidAt)) {
            $paidAt = now()->toDateString();
        }
        if ($status !== 'cobrado') {
            $paidAt = null;
        }

        $issueDate = $validated['issued_at'] ?? $validated['issue_date'] ?? now()->toDateString();

        $creditDays = 0;
        if (isset($validated['credit_days']) && $validated['credit_days'] !== null && $validated['credit_days'] !== '') {
            $creditDays = max((int) $validated['credit_days'], 0);
        } else {
            try {
                $issue = Carbon::parse($issueDate);
                $due = Carbon::parse($validated['due_date']);
                $creditDays = max($issue->diffInDays($due, false), 0);
            } catch (\Throwable $e) {
                $creditDays = 0;
            }
        }

        $interestRate = $validated['interest_rate'] ?? 0;
        if ($interestRate === null || $interestRate === '') {
            $interestRate = 0;
        }

        $collectionStatus = $validated['collection_status'] ?? 'sin_gestion';
        if ($collectionStatus === null || $collectionStatus === '') {
            $collectionStatus = 'sin_gestion';
        }

        $data = [
            'company_id' => (int) $validated['company_id'],

            'client_name' => $clientName !== '' ? $clientName : 'Sin cliente',
            'client_id' => $validated['client_id'] ?? null,
            'folio' => $folio !== '' ? $folio : null,
            'description' => $description !== '' ? $description : null,

            'document_type' => $this->resolveDocumentType($category),
            'category' => $category,

            'amount' => $amount,
            'amount_paid' => $amountPaid,
            'currency' => $validated['currency'] ?? 'MXN',

            'issue_date' => $issueDate,
            'due_date' => $validated['due_date'],
            'paid_at' => $paidAt,

            'status' => $status,
            'priority' => $validated['priority'] ?? 'media',

            'payment_method' => $validated['payment_method'] ?? null,
            'bank_reference' => $validated['bank_reference'] ?? null,

            'credit_days' => $creditDays,
            'interest_rate' => $interestRate,

            'assigned_to' => $validated['assigned_to'] ?? null,
            'collection_status' => $collectionStatus,

            'notes' => $validated['notes'] ?? null,
            'reminder_days_before' => (int) ($validated['reminder_days_before'] ?? 3),
            'tags' => $validated['tags'] ?? null,

            'title' => $title !== '' ? $title : null,
            'reference' => $reference !== '' ? $reference : null,
            'invoice_number' => $invoiceNumber !== '' ? $invoiceNumber : null,
            'vendor_name' => $validated['vendor_name'] ?? null,
            'frequency' => $frequency,
        ];

        return $data;
    }

    private function validateReceivable(Request $request): array
    {
        return $request->validate([
            'company_id' => ['required', 'exists:companies,id'],

            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'in:factura,cliente,servicio,proyecto,anticipo,suscripcion,otros,honorarios,renta,servicios,producto,otro'],
            'frequency' => ['required', 'string', 'in:one_time,weekly,biweekly,monthly,bimonthly,quarterly,semiannual,annual,unico,semanal,quincenal,mensual,bimestral,trimestral,semestral,anual'],
            'status' => ['required', 'string', 'in:pending,partial,paid,overdue,cancelled,pendiente,parcial,cobrado,vencido,cancelado'],
            'amount' => ['required', 'numeric', 'min:0'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'in:MXN,USD,EUR'],
            'due_date' => ['required', 'date'],
            'paid_at' => ['nullable', 'date'],
            'issued_at' => ['nullable', 'date'],
            'reminder_days_before' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string'],

            'evidence_file' => ['nullable', 'file', 'max:10240'],
            'evidence' => ['nullable', 'file', 'max:10240'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['file', 'max:15360'],

            'client_name' => ['nullable', 'string', 'max:255'],
            'client_id' => ['nullable', 'string', 'max:100'],
            'folio' => ['nullable', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['nullable', 'date'],

            'priority' => ['nullable', 'in:alta,media,baja'],
            'payment_method' => ['nullable', 'in:transferencia,efectivo,tarjeta,cheque,otro'],
            'bank_reference' => ['nullable', 'string', 'max:255'],
            'credit_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'assigned_to' => ['nullable', 'string', 'max:255'],
            'collection_status' => ['nullable', 'in:sin_gestion,en_gestion,promesa_pago,litigio,incobrable'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ]);
    }

    private function storeDocuments(Request $request, string $folder): array
    {
        if (!$request->hasFile('documents')) {
            return [[], []];
        }

        $urls = [];
        $names = [];

        foreach ((array) $request->file('documents') as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $path = $file->store($folder, 'public');
            $urls[] = Storage::disk('public')->url($path);
            $names[] = $file->getClientOriginalName();
        }

        return [$urls, $names];
    }

    private function storeEvidence(Request $request, string $folder): ?string
    {
        $file = null;

        if ($request->hasFile('evidence_file')) {
            $file = $request->file('evidence_file');
        } elseif ($request->hasFile('evidence')) {
            $file = $request->file('evidence');
        }

        if (!$file || !$file->isValid()) {
            return null;
        }

        $path = $file->store($folder, 'public');
        return Storage::disk('public')->url($path);
    }

    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'pendiente',
            'partial' => 'parcial',
            'paid' => 'cobrado',
            'overdue' => 'vencido',
            'cancelled' => 'cancelado',
            default => $status,
        };
    }

    private function normalizeCategory(string $category): string
    {
        return match ($category) {
            'cliente' => 'factura',
            'servicio' => 'servicios',
            'proyecto' => 'servicios',
            'suscripcion' => 'servicios',
            'anticipo' => 'otro',
            'otros' => 'otro',
            default => $category,
        };
    }

    private function normalizeFrequency(string $frequency): string
    {
        return match ($frequency) {
            'unico' => 'one_time',
            'semanal' => 'weekly',
            'quincenal' => 'biweekly',
            'mensual' => 'monthly',
            'bimestral' => 'bimonthly',
            'trimestral' => 'quarterly',
            'semestral' => 'semiannual',
            'anual' => 'annual',
            default => $frequency,
        };
    }

    private function resolveDocumentType(string $category): string
    {
        return match ($category) {
            'otro' => 'anticipo',
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

    private function assertOwner(AccountReceivable $r): void
    {
        $email = Auth::user()?->email;

        if ($email && $r->created_by && $r->created_by !== $email) {
            abort(403);
        }
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

        $docTypeLabel = match ((string) $receivable->document_type) {
            'factura' => 'Factura',
            'nota_credito' => 'Nota de crédito',
            'cargo_adicional' => 'Cargo adicional',
            'anticipo' => 'Anticipo',
            default => 'Documento',
        };

        $categoryLabel = match ((string) $receivable->category) {
            'factura' => 'Factura',
            'honorarios' => 'Honorarios',
            'renta' => 'Renta',
            'servicios' => 'Servicios',
            'producto' => 'Producto',
            default => 'Otro',
        };

        $priorityLabel = match ((string) $receivable->priority) {
            'alta' => 'Alta',
            'baja' => 'Baja',
            default => 'Media',
        };

        $companyName = $receivable->relationLoaded('company')
            ? ($receivable->company?->name ?? '—')
            : (Company::query()->whereKey($receivable->company_id)->value('name') ?? '—');

        $marker = $this->agendaMarkerForReceivable($receivable->id);

        $descriptionParts = array_filter([
            'Evento generado automáticamente desde Cuentas por Cobrar.',
            'ID cobro: '.$receivable->id,
            'Compañía: '.$companyName,
            'Cliente: '.($receivable->client_name ?: '—'),
            !empty($receivable->folio) ? 'Folio: '.$receivable->folio : null,
            'Tipo documento: '.$docTypeLabel,
            'Categoría: '.$categoryLabel,
            'Prioridad: '.$priorityLabel,
            'Monto: $'.number_format((float) $receivable->amount, 2).' '.($receivable->currency ?: 'MXN'),
            'Saldo actual: $'.number_format(max((float) $receivable->amount - (float) $receivable->amount_paid, 0), 2).' '.($receivable->currency ?: 'MXN'),
            'Vencimiento: '.$startAt->format('d/m/Y H:i'),
            !empty($receivable->assigned_to) ? 'Asignado a: '.$receivable->assigned_to : null,
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

    private function deleteAgendaForReceivable(AccountReceivable $receivable): void
    {
        $event = $this->findAgendaEventForReceivable($receivable);

        if ($event) {
            $event->delete();
        }
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