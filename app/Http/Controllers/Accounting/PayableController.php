<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountPayable;
use App\Models\AgendaEvent;
use App\Models\Company;
use App\Services\Accounting\AccountStateService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PayableController extends Controller implements HasMiddleware
{
    public function __construct(private AccountStateService $state)
    {
        //
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index(Request $request)
    {
        $companies = Company::orderBy('name')->get();

        $q = AccountPayable::query()->with('company')->orderByDesc('due_date');

        if ($request->filled('company_id')) {
            $q->where('company_id', (int) $request->company_id);
        }

        $email = Auth::user()?->email;
        if ($email) {
            $q->where('created_by', $email);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $q->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $q->where(function ($w) use ($s) {
                $w->where('title', 'like', "%{$s}%")
                  ->orWhere('supplier_name', 'like', "%{$s}%")
                  ->orWhere('folio', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%");
            });
        }

        $items = $q->paginate(20)->withQueryString();

        $sumQuery = clone $q;
        $all = $sumQuery->get();
        $totPending = $all->sum(fn($p) => max((float) $p->amount - (float) $p->amount_paid, 0));
        $totOverdue = $all->whereIn('status', ['atrasado'])->sum(fn($p) => max((float) $p->amount - (float) $p->amount_paid, 0));

        return view('accounting.payables.index', compact('items', 'companies', 'totPending', 'totOverdue'));
    }

    public function create(Request $request)
    {
        $companies = Company::orderBy('name')->get();
        $presetCompanyId = $request->get('company_id');

        $item = new AccountPayable([
            'issue_date' => now()->toDateString(),
            'currency' => 'MXN',
            'status' => 'pendiente',
            'frequency' => 'unico',
            'category' => 'otros',
            'reminder_days_before' => 3,
            'company_id' => $presetCompanyId,
        ]);

        return view('accounting.payables.create', compact('item', 'companies'));
    }

    public function store(Request $request)
    {
        $data = $this->validatePayable($request);
        $data['created_by'] = Auth::user()?->email;

        [$docs, $names] = $this->storeDocuments($request, 'accounting/payables');
        if ($docs) {
            $data['documents'] = $docs;
        }
        if ($names) {
            $data['document_names'] = $names;
        }

        if ($request->hasFile('evidence')) {
            $path = $request->file('evidence')->store('accounting/payables/evidence', 'public');
            $data['evidence_url'] = Storage::disk('public')->url($path);
        }

        $p = AccountPayable::create($data);
        $this->state->recalc('payable', $p->id);

        try {
            $this->syncAgendaForPayable($p);
        } catch (\Throwable $e) {
            Log::warning('No se pudo sincronizar agenda para payable '.$p->id.': '.$e->getMessage());
        }

        return redirect()->route('accounting.payables.show', $p)->with('success', 'Cuenta por pagar creada y agregada a la agenda.');
    }

    public function show(AccountPayable $payable)
    {
        $this->assertOwner($payable);

        $payable->load(['company', 'movements']);
        return view('accounting.payables.show', ['item' => $payable]);
    }

    public function edit(AccountPayable $payable)
    {
        $this->assertOwner($payable);

        $companies = Company::orderBy('name')->get();
        return view('accounting.payables.edit', ['item' => $payable, 'companies' => $companies]);
    }

    public function update(Request $request, AccountPayable $payable)
    {
        $this->assertOwner($payable);

        $data = $this->validatePayable($request);

        $docs = $payable->documents ?? [];
        $names = $payable->document_names ?? [];

        [$newDocs, $newNames] = $this->storeDocuments($request, 'accounting/payables');
        if ($newDocs) {
            $docs = array_merge($docs, $newDocs);
        }
        if ($newNames) {
            $names = array_merge($names, $newNames);
        }

        $data['documents'] = $docs ?: null;
        $data['document_names'] = $names ?: null;

        if ($request->hasFile('evidence')) {
            $path = $request->file('evidence')->store('accounting/payables/evidence', 'public');
            $data['evidence_url'] = Storage::disk('public')->url($path);
        }

        $payable->update($data);
        $this->state->recalc('payable', $payable->id);

        try {
            $this->syncAgendaForPayable($payable->fresh());
        } catch (\Throwable $e) {
            Log::warning('No se pudo actualizar agenda para payable '.$payable->id.': '.$e->getMessage());
        }

        return redirect()->route('accounting.payables.show', $payable)->with('success', 'Cuenta por pagar actualizada y sincronizada con agenda.');
    }

    public function destroy(AccountPayable $payable)
    {
        $this->assertOwner($payable);

        try {
            $this->deleteAgendaForPayable($payable);
        } catch (\Throwable $e) {
            Log::warning('No se pudo eliminar agenda para payable '.$payable->id.': '.$e->getMessage());
        }

        $payable->delete();

        return redirect()->route('accounting.payables.index')->with('success', 'Cuenta por pagar eliminada.');
    }

    private function validatePayable(Request $request): array
    {
        return $request->validate([
            'company_id' => ['required', 'exists:companies,id'],

            'title' => ['required', 'string', 'max:255'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'string', 'max:100'],
            'folio' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],

            'category' => ['required', 'in:impuestos,cuentas_por_pagar,servicios,nomina,seguros,retenciones,otros'],
            'frequency' => ['required', 'in:unico,mensual,bimestral,trimestral,semestral,anual'],

            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'in:MXN,USD,EUR'],

            'issue_date' => ['nullable', 'date'],
            'due_date' => ['required', 'date'],

            'status' => ['required', 'in:pendiente,urgente,parcial,pagado,atrasado,cancelado'],

            'payment_method' => ['nullable', 'in:transferencia,efectivo,tarjeta,cheque,otro'],
            'bank_reference' => ['nullable', 'string', 'max:255'],

            'retention_expiry' => ['nullable', 'date'],

            'notes' => ['nullable', 'string'],
            'reminder_days_before' => ['nullable', 'integer', 'min:0', 'max:365'],

            'expense_id' => ['nullable', 'integer', 'min:1'],
            'evidence_url' => ['nullable', 'string', 'max:1000'],
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

    private function assertOwner(AccountPayable $p): void
    {
        $email = Auth::user()?->email;

        if ($email && $p->created_by && $p->created_by !== $email) {
            abort(403);
        }
    }

    private function syncAgendaForPayable(AccountPayable $payable): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        $event = $this->findAgendaEventForPayable($payable);

        if (
            empty($payable->due_date) ||
            in_array((string) $payable->status, ['pagado', 'cancelado'], true)
        ) {
            if ($event) {
                $event->delete();
            }
            return;
        }

        $startAt = Carbon::parse($payable->due_date, 'America/Mexico_City')->setTime(9, 0, 0);

        $title = 'Pago';
        if (!empty($payable->title)) {
            $title .= ': '.$payable->title;
        }
        if (!empty($payable->supplier_name)) {
            $title .= ' · '.$payable->supplier_name;
        }
        if (!empty($payable->folio)) {
            $title .= ' · Folio '.$payable->folio;
        }

        $categoryLabel = match((string) $payable->category) {
            'impuestos' => 'Impuestos',
            'cuentas_por_pagar' => 'Cuentas por pagar',
            'servicios' => 'Servicios',
            'nomina' => 'Nómina',
            'seguros' => 'Seguros',
            'retenciones' => 'Retenciones',
            default => 'Otros',
        };

        $frequencyLabel = match((string) $payable->frequency) {
            'mensual' => 'Mensual',
            'bimestral' => 'Bimestral',
            'trimestral' => 'Trimestral',
            'semestral' => 'Semestral',
            'anual' => 'Anual',
            default => 'Único',
        };

        $companyName = $payable->relationLoaded('company')
            ? ($payable->company?->name ?? '—')
            : (Company::query()->whereKey($payable->company_id)->value('name') ?? '—');

        $marker = $this->agendaMarkerForPayable($payable->id);

        $descriptionParts = array_filter([
            'Evento generado automáticamente desde Cuentas por Pagar.',
            'ID pago: '.$payable->id,
            'Compañía: '.$companyName,
            !empty($payable->supplier_name) ? 'Proveedor: '.$payable->supplier_name : null,
            !empty($payable->folio) ? 'Folio: '.$payable->folio : null,
            'Categoría: '.$categoryLabel,
            'Frecuencia: '.$frequencyLabel,
            'Monto: $'.number_format((float) $payable->amount, 2).' '.($payable->currency ?: 'MXN'),
            'Vencimiento: '.$startAt->format('d/m/Y H:i'),
            !empty($payable->bank_reference) ? 'Referencia bancaria: '.$payable->bank_reference : null,
            !empty($payable->description) ? 'Descripción: '.$payable->description : null,
            !empty($payable->notes) ? 'Notas: '.$payable->notes : null,
            $marker,
        ]);

        $data = [
            'title' => mb_substr($title, 0, 180),
            'description' => mb_substr(implode("\n", $descriptionParts), 0, 2000),
            'start_at' => $startAt->format('Y-m-d H:i:s'),
            'timezone' => 'America/Mexico_City',
            'repeat_rule' => $this->mapPayableFrequencyToAgendaRule((string) $payable->frequency),
            'remind_offset_minutes' => max(((int) ($payable->reminder_days_before ?? 3)) * 1440, 1),
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

    private function deleteAgendaForPayable(AccountPayable $payable): void
    {
        $event = $this->findAgendaEventForPayable($payable);

        if ($event) {
            $event->delete();
        }
    }

    private function findAgendaEventForPayable(AccountPayable $payable): ?AgendaEvent
    {
        $marker = $this->agendaMarkerForPayable($payable->id);

        return AgendaEvent::query()
            ->where('description', 'like', '%'.$marker.'%')
            ->latest('id')
            ->first();
    }

    private function agendaMarkerForPayable(int $payableId): string
    {
        return '[AUTO_PAYABLE_ID:'.$payableId.']';
    }

    private function mapPayableFrequencyToAgendaRule(string $frequency): string
    {
        return match ($frequency) {
            'mensual' => 'monthly',
            default => 'none',
        };
    }
}