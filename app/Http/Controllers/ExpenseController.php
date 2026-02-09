<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Vehicle;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class ExpenseController extends Controller
{
    /**
     * Base para armar el link absoluto del QR (URL pública de tu sistema).
     * Ej: https://tudominio.com
     */
    private const QR_BASE = 'https://ai.jureto.com.mx'; // <-- cámbialo a tu dominio

    /* ====================== VISTAS ====================== */

    public function index(Request $request)
    {
        $categories = ExpenseCategory::query()->orderBy('name')->get(['id', 'name']);

        $vehiclePlateCol = Schema::hasColumn('vehicles', 'plate') ? 'plate'
            : (Schema::hasColumn('vehicles', 'placas') ? 'placas' : 'id');

        $vehicles = Vehicle::query()
            ->orderBy($vehiclePlateCol)
            ->get()
            ->map(function ($v) use ($vehiclePlateCol) {
                $v->plate_label = $v->{$vehiclePlateCol} ?? ('#' . $v->id);
                return $v;
            });

        $periods = collect();
        // Si tienes tabla payroll_periods descomenta:
        // if (Schema::hasTable('payroll_periods')) {
        //     $periods = DB::table('payroll_periods')->orderByDesc('id')->get();
        // }

        return view('accounting.expenses.index', [
            'categories' => $categories,
            'vehicles'   => $vehicles,
            'periods'    => $periods,
        ]);
    }

    public function create()
    {
        $vehiclePlateCol = Schema::hasColumn('vehicles', 'plate') ? 'plate'
            : (Schema::hasColumn('vehicles', 'placas') ? 'placas' : 'id');

        $categories = ExpenseCategory::query()->orderBy('name')->get(['id', 'name']);

        $vehicles = Vehicle::query()
            ->orderBy($vehiclePlateCol)
            ->get()
            ->map(function ($v) use ($vehiclePlateCol) {
                $v->plate_label = $v->{$vehiclePlateCol} ?? ('#' . $v->id);
                return $v;
            });

        $people   = User::orderBy('name')->get(['id', 'name']);
        $managers = User::orderBy('name')->get(['id', 'name']); // si quieres filtrar admins, hazlo aquí

        return view('accounting.expenses.create', [
            'categories' => $categories,
            'vehicles'   => $vehicles,
            'people'     => $people,
            'managers'   => $managers,
            'now'        => now()->format('Y-m-d\TH:i'),
        ]);
    }

    /* ====================== API PARA DASHBOARD ====================== */

    public function apiMetrics(Request $request)
    {
        $q = $this->baseFilteredQuery($request);

        $currency = Schema::hasColumn('expenses', 'currency')
            ? (clone $q)->whereNotNull('currency')->value('currency')
            : 'MXN';

        $count = (clone $q)->count();
        $sum   = (clone $q)->sum('amount');

        $paidSum = null;
        $pendingSum = null;
        $canceledSum = null;
        if (Schema::hasColumn('expenses', 'status')) {
            $paidSum     = (clone $q)->where('status', 'paid')->sum('amount');
            $pendingSum  = (clone $q)->where('status', 'pending')->sum('amount');
            $canceledSum = (clone $q)->where('status', 'canceled')->sum('amount');
        }

        return response()->json([
            'count'        => (int) $count,
            'sum'          => (float) $sum,
            'currency'     => $currency ?: 'MXN',
            'paid_sum'     => $paidSum !== null ? (float) $paidSum : null,
            'pending_sum'  => $pendingSum !== null ? (float) $pendingSum : null,
            'canceled_sum' => $canceledSum !== null ? (float) $canceledSum : null,
        ]);
    }

    public function apiList(Request $request)
    {
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = (int) $request->input('per_page', 20);
        $perPage = $perPage < 1 ? 20 : ($perPage > 200 ? 200 : $perPage);

        $q = $this->baseFilteredQuery($request);

        if (Schema::hasColumn('expenses', 'performed_at')) $q->orderByDesc('performed_at');
        else $q->orderByDesc('expense_date');

        $q->orderByDesc('id');

        $total = (clone $q)->count();
        $rows  = $q->forPage($page, $perPage)->get();

        $catIds = [];
        if (Schema::hasColumn('expenses', 'expense_category_id')) {
            $catIds = $rows->pluck('expense_category_id')->filter()->unique()->values()->all();
        }
        $cats = $catIds
            ? ExpenseCategory::whereIn('id', $catIds)->get(['id', 'name'])->keyBy('id')
            : collect();

        $vehIds = [];
        if (Schema::hasColumn('expenses', 'vehicle_id')) {
            $vehIds = $rows->pluck('vehicle_id')->filter()->unique()->values()->all();
        }

        $vehiclePlateCol = Schema::hasColumn('vehicles', 'plate') ? 'plate'
            : (Schema::hasColumn('vehicles', 'placas') ? 'placas' : 'id');

        $vehs = $vehIds
            ? Vehicle::whereIn('id', $vehIds)->get(['id', $vehiclePlateCol])->keyBy('id')
            : collect();

        $data = $rows->map(function ($e) use ($cats, $vehs, $vehiclePlateCol) {
            $currency = Schema::hasColumn('expenses', 'currency') ? ($e->currency ?: 'MXN') : 'MXN';

            $category = null;
            if (Schema::hasColumn('expenses', 'expense_category_id') && $e->expense_category_id) {
                $c = $cats->get($e->expense_category_id);
                if ($c) $category = ['id' => $c->id, 'name' => $c->name];
            }

            $vehicle = null;
            if (Schema::hasColumn('expenses', 'vehicle_id') && $e->vehicle_id) {
                $v = $vehs->get($e->vehicle_id);
                if ($v) $vehicle = ['id' => $v->id, 'plate' => $v->{$vehiclePlateCol} ?? ('#' . $v->id)];
            }

            $evidenceUrl = null;
            $evidenceMime = null;
            if (Schema::hasColumn('expenses', 'attachment_path') && $e->attachment_path) {
                $evidenceUrl = Storage::disk('public')->url($e->attachment_path);
                $evidenceMime = Schema::hasColumn('expenses', 'attachment_mime') ? ($e->attachment_mime ?: null) : null;
            }

            $mgrSigUrl = null;
            $ctpSigUrl = null;

            if (Schema::hasColumn('expenses', 'admin_signature_path') && $e->admin_signature_path) {
                $mgrSigUrl = Storage::disk('public')->url($e->admin_signature_path);
            }
            if (Schema::hasColumn('expenses', 'manager_signature_path') && $e->manager_signature_path) {
                $mgrSigUrl = Storage::disk('public')->url($e->manager_signature_path);
            }

            if (Schema::hasColumn('expenses', 'receiver_signature_path') && $e->receiver_signature_path) {
                $ctpSigUrl = Storage::disk('public')->url($e->receiver_signature_path);
            }
            if (Schema::hasColumn('expenses', 'counterparty_signature_path') && $e->counterparty_signature_path) {
                $ctpSigUrl = Storage::disk('public')->url($e->counterparty_signature_path);
            }

            // ✅ PDF del recibo (fusionado con evidencias)
            $pdfUrl = route('expenses.pdf', ['expense' => $e->id]);

            return [
                'id' => (int) $e->id,
                'concept' => $e->concept ?? 'Gasto',
                'description' => $e->description ?? null,
                'amount' => (float) ($e->amount ?? 0),
                'currency' => $currency,
                'status' => Schema::hasColumn('expenses', 'status') ? ($e->status ?? null) : null,
                'payment_method' => Schema::hasColumn('expenses', 'payment_method') ? ($e->payment_method ?? null) : null,

                'expense_date' => $e->expense_date ?? null,
                'performed_at' => Schema::hasColumn('expenses', 'performed_at') ? ($e->performed_at ?? null) : null,

                'vendor' => Schema::hasColumn('expenses', 'vendor') ? ($e->vendor ?? null) : null,

                'category' => $category,
                'vehicle'  => $vehicle,

                'has_evidence' => (bool) $evidenceUrl,
                'evidence_url' => $evidenceUrl,
                'evidence_mime' => $evidenceMime,

                'manager_signature_url' => $mgrSigUrl,
                'counterparty_signature_url' => $ctpSigUrl,

                'nip_approved_at' => Schema::hasColumn('expenses', 'nip_approved_at') ? ($e->nip_approved_at ?? null) : null,

                'pdf_url' => $pdfUrl,
            ];
        })->values();

        $lastPage = (int) ceil($total / $perPage);

        return response()->json([
            'data' => $data,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => max(1, $lastPage),
                'total' => (int) $total,
            ],
        ]);
    }

    /* ====================== STORE (GASTO NORMAL) ====================== */

    public function store(Request $request)
    {
        // Tu UI manda: entry_kind = 'gasto' o 'caja'
        // En BD/Controller lo manejamos como: gasto o movimiento
        $entryKind = $request->input('entry_kind', 'gasto');
        if ($entryKind === 'caja') $entryKind = 'movimiento';

        if ($entryKind === 'movimiento') {
            abort(422, 'El movimiento se guarda desde Directo/QR.');
        }

        $type = $request->input('expense_type', 'vehiculo'); // tu UI ya no usa general

        $rules = [
            'entry_kind'    => ['nullable', 'in:gasto,caja,movimiento'],
            'expense_type'  => ['required', 'in:general,vehiculo,nomina'],
            'concept'       => ['required', 'string', 'max:180'],
            'expense_date'  => ['required', 'date'],
            'amount'        => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'status'        => ['nullable', 'string', 'max:40'],
            'description'   => ['nullable', 'string', 'max:5000'],
            'attachment'    => ['nullable', 'file'], // evidencia única para gasto

            'receiver_signature' => ['required', 'string'],
            'admin_signature'    => ['required', 'string'],
        ];

        if ($type === 'general') {
            $rules['expense_category_id'] = ['required', 'integer', 'exists:expense_categories,id'];
        }
        if ($type === 'vehiculo') {
            $rules['vehicle_id'] = ['required', 'integer', 'exists:vehicles,id'];
            $rules['vehicle_category'] = ['required', 'string', 'max:80'];
        }
        if ($type === 'nomina') {
            $rules['payroll_category'] = ['required', 'string', 'max:80'];
            $rules['payroll_period']   = ['required', 'string', 'max:80'];
        }

        $data = $request->validate($rules);

        $expense = new Expense();

        if (Schema::hasColumn('expenses', 'created_by')) {
            $expense->created_by = auth()->id();
        }

        $expense->concept        = $data['concept'];
        $expense->expense_date   = $data['expense_date'];
        $expense->amount         = $data['amount'];
        if (Schema::hasColumn('expenses', 'currency')) $expense->currency = 'MXN';
        if (Schema::hasColumn('expenses', 'payment_method')) $expense->payment_method = $data['payment_method'] ?? 'transfer';
        if (Schema::hasColumn('expenses', 'status')) $expense->status = $data['status'] ?? 'paid';
        if (Schema::hasColumn('expenses', 'description')) $expense->description = $data['description'] ?? null;

        if (Schema::hasColumn('expenses', 'vendor')) $expense->vendor = null;
        if (Schema::hasColumn('expenses', 'tags'))   $expense->tags = null;

        if (Schema::hasColumn('expenses', 'entry_kind')) {
            $expense->entry_kind = 'gasto';
        }

        if (Schema::hasColumn('expenses', 'expense_type')) {
            $expense->expense_type = $type;
        }

        if (Schema::hasColumn('expenses', 'expense_category_id')) $expense->expense_category_id = null;
        if (Schema::hasColumn('expenses', 'vehicle_id')) $expense->vehicle_id = null;

        if ($type === 'general' && Schema::hasColumn('expenses', 'expense_category_id')) {
            $expense->expense_category_id = (int) $data['expense_category_id'];
        }
        if ($type === 'vehiculo') {
            if (Schema::hasColumn('expenses', 'vehicle_id')) $expense->vehicle_id = (int) $data['vehicle_id'];
            if (Schema::hasColumn('expenses', 'vehicle_category')) $expense->vehicle_category = $data['vehicle_category'];
        }
        if ($type === 'nomina') {
            if (Schema::hasColumn('expenses', 'payroll_category')) $expense->payroll_category = $data['payroll_category'];
            if (Schema::hasColumn('expenses', 'payroll_period')) $expense->payroll_period = $data['payroll_period'];
        }

        if (Schema::hasColumn('expenses', 'receiver_signature_path')) {
            $expense->receiver_signature_path = $this->storeDataUrl($data['receiver_signature'], 'signatures');
        }
        if (Schema::hasColumn('expenses', 'admin_signature_path')) {
            $expense->admin_signature_path = $this->storeDataUrl($data['admin_signature'], 'signatures');
        }

        $expense->save();

        // Evidencia única (gasto)
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store("expenses/{$expense->id}", 'public');

            if (Schema::hasColumn('expenses', 'attachment_path'))  $expense->attachment_path = $path;
            if (Schema::hasColumn('expenses', 'attachment_name'))  $expense->attachment_name = $file->getClientOriginalName();
            if (Schema::hasColumn('expenses', 'attachment_mime'))  $expense->attachment_mime = $file->getClientMimeType();
            if (Schema::hasColumn('expenses', 'attachment_size'))  $expense->attachment_size = $file->getSize();

            // Si tu tabla tiene evidence_paths (JSON), también lo guardamos
            if (Schema::hasColumn('expenses', 'evidence_paths')) {
                $expense->evidence_paths = json_encode([$path], JSON_UNESCAPED_SLASHES);
            }

            if ($expense->isDirty()) $expense->save();
        }

        return redirect()->route('expenses.index')->with('ok', 'Gasto creado');
    }

    /* ==========================================================
       MOVIMIENTOS: NOMBRES QUE TU UI/RUTAS ESTÁN ESPERANDO
       (AHORA: CUALQUIERA PUEDE AUTORIZAR CON SU PROPIO NIP)
       ========================================================== */

    // Fondo para caja: route('expenses.movement.allocation.store')
    public function storeAllocation(Request $req)
    {
        $req->validate([
            'performed_at'      => ['nullable', 'date'],
            'amount'            => ['required', 'numeric', 'min:0.01'],
            'purpose'           => ['nullable', 'string', 'max:255'],
            'manager_id'        => ['required', 'integer', 'exists:users,id'],
            'boss_id'           => ['required', 'integer', 'exists:users,id'],
            'manager_signature' => ['required', 'string'],
            // evidencias opcionales
            'evidence'          => ['nullable', 'array'],
            'evidence.*'        => ['file'],
        ]);

        $me = auth()->user();
        if (!$me) abort(403, 'No autenticado.');

        return DB::transaction(function () use ($req, $me) {
            $createdAt = $req->performed_at ? Carbon::parse($req->performed_at) : now();
            $mgrSig = $this->storeDataUrl($req->manager_signature, 'signatures');

            $e = new Expense();
            if (Schema::hasColumn('expenses', 'created_by')) $e->created_by = $me->id;

            if (Schema::hasColumn('expenses', 'entry_kind')) $e->entry_kind = 'movimiento';
            if (Schema::hasColumn('expenses', 'expense_type')) $e->expense_type = 'movimiento';

            $e->concept = 'Fondo para caja';
            if (Schema::hasColumn('expenses', 'description')) $e->description = $req->purpose ?: 'Fondo para caja';

            $e->expense_date = $createdAt->toDateString();
            if (Schema::hasColumn('expenses', 'performed_at')) $e->performed_at = $createdAt;

            $e->amount = $req->amount;
            if (Schema::hasColumn('expenses', 'currency')) $e->currency = 'MXN';

            if (Schema::hasColumn('expenses', 'movement_manager_id')) $e->movement_manager_id = (int) $req->manager_id;
            if (Schema::hasColumn('expenses', 'movement_boss_id')) $e->movement_boss_id = (int) $req->boss_id;

            if (Schema::hasColumn('expenses', 'manager_signature_path')) {
                $e->manager_signature_path = $mgrSig;
            } elseif (Schema::hasColumn('expenses', 'admin_signature_path')) {
                $e->admin_signature_path = $mgrSig;
            }

            $e->save();

            // Guardar evidencias (opcionales) y registrar rutas (attachment + evidence_paths)
            $this->storeMultipleEvidencesIfAny($e, $req, 'evidence', "expenses/{$e->id}/allocation");

            return response()->json(['ok' => true, 'id' => $e->id]);
        });
    }

    // Entrega DIRECTO: route('expenses.movement.disbursement.direct')
    public function storeDisbursementDirect(Request $req)
    {
        $req->validate([
            'performed_at' => ['nullable', 'date'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'purpose'      => ['required', 'string', 'max:255'],
            'self_receive' => ['nullable', 'in:0,1'],
            'receiver_id'  => ['nullable', 'integer', 'exists:users,id'],
            'nip'          => ['required', 'digits_between:4,8'],
            'counterparty_signature' => ['required', 'string'],
            'manager_id'   => ['nullable', 'integer', 'exists:users,id'],

            // ✅ evidencias opcionales (foto/pdf/etc)
            'evidence'     => ['nullable', 'array'],
            'evidence.*'   => ['file'],
        ]);

        $me = auth()->user();
        if (!$me) abort(403, 'No autenticado.');

        // ✅ CUALQUIERA AUTORIZA CON SU PROPIO NIP
        $this->assertUserPinOrFail($me, (string) $req->nip);

        $self = $req->boolean('self_receive');
        if (!$self && !$req->filled('receiver_id')) abort(422, 'Selecciona el usuario que recibe.');

        return DB::transaction(function () use ($req, $me, $self) {
            $createdAt = $req->performed_at ? Carbon::parse($req->performed_at) : now();
            $sig = $this->storeDataUrl($req->counterparty_signature, 'signatures');

            $e = new Expense();
            if (Schema::hasColumn('expenses', 'created_by')) $e->created_by = $me->id;

            if (Schema::hasColumn('expenses', 'entry_kind')) $e->entry_kind = 'movimiento';
            if (Schema::hasColumn('expenses', 'expense_type')) $e->expense_type = 'movimiento';

            $e->concept = 'Entrega';
            if (Schema::hasColumn('expenses', 'description')) $e->description = $req->purpose;

            $e->expense_date = $createdAt->toDateString();
            if (Schema::hasColumn('expenses', 'performed_at')) $e->performed_at = $createdAt;

            $e->amount = $req->amount;
            if (Schema::hasColumn('expenses', 'currency')) $e->currency = 'MXN';

            if (Schema::hasColumn('expenses', 'movement_receiver_id')) {
                $e->movement_receiver_id = $self ? $me->id : (int) $req->receiver_id;
            }
            if (Schema::hasColumn('expenses', 'movement_self_receive')) {
                $e->movement_self_receive = $self ? 1 : 0;
            }

            if (Schema::hasColumn('expenses', 'counterparty_signature_path')) {
                $e->counterparty_signature_path = $sig;
            } elseif (Schema::hasColumn('expenses', 'receiver_signature_path')) {
                $e->receiver_signature_path = $sig;
            }

            if (Schema::hasColumn('expenses', 'nip_approved_by')) $e->nip_approved_by = $me->id;
            if (Schema::hasColumn('expenses', 'nip_approved_at')) $e->nip_approved_at = now();

            $e->save();

            // ✅ evidencias opcionales
            $this->storeMultipleEvidencesIfAny($e, $req, 'evidence', "expenses/{$e->id}/disbursement");

            return response()->json(['ok' => true, 'id' => $e->id]);
        });
    }

    // Entrega QR START: route('expenses.movement.disbursement.qr.start')
    public function startDisbursementQr(Request $req)
    {
        $req->validate([
            'performed_at' => ['nullable', 'date'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'purpose'      => ['required', 'string', 'max:255'],
            'self_receive' => ['nullable', 'in:0,1'],
            'receiver_id'  => ['nullable', 'integer', 'exists:users,id'],
            'nip'          => ['required', 'digits_between:4,8'],
            'manager_id'   => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $me = auth()->user();
        if (!$me) abort(403, 'No autenticado.');

        // ✅ CUALQUIERA AUTORIZA CON SU PROPIO NIP
        $this->assertUserPinOrFail($me, (string) $req->nip);

        $self = $req->boolean('self_receive');
        if (!$self && !$req->filled('receiver_id')) abort(422, 'Selecciona el usuario que recibe.');

        return DB::transaction(function () use ($req, $me, $self) {
            $createdAt = $req->performed_at ? Carbon::parse($req->performed_at) : now();
            $token     = Str::uuid()->toString();
            $expires   = now()->addMinutes(20);

            $e = new Expense();
            if (Schema::hasColumn('expenses', 'created_by')) $e->created_by = $me->id;

            if (Schema::hasColumn('expenses', 'entry_kind')) $e->entry_kind = 'movimiento';
            if (Schema::hasColumn('expenses', 'expense_type')) $e->expense_type = 'movimiento';

            $e->concept = 'Entrega';
            if (Schema::hasColumn('expenses', 'description')) $e->description = $req->purpose;

            $e->expense_date = $createdAt->toDateString();
            if (Schema::hasColumn('expenses', 'performed_at')) $e->performed_at = $createdAt;

            $e->amount = $req->amount;
            if (Schema::hasColumn('expenses', 'currency')) $e->currency = 'MXN';

            if (Schema::hasColumn('expenses', 'movement_receiver_id')) {
                $e->movement_receiver_id = $self ? $me->id : (int) $req->receiver_id;
            }
            if (Schema::hasColumn('expenses', 'movement_self_receive')) {
                $e->movement_self_receive = $self ? 1 : 0;
            }

            // ✅ Aprobado por el usuario que metió su NIP
            if (Schema::hasColumn('expenses', 'nip_approved_by')) $e->nip_approved_by = $me->id;
            if (Schema::hasColumn('expenses', 'nip_approved_at')) $e->nip_approved_at = now();

            if (Schema::hasColumn('expenses', 'qr_token')) $e->qr_token = $token;
            if (Schema::hasColumn('expenses', 'qr_expires_at')) $e->qr_expires_at = $expires;

            $e->save();

            $relative = route('expenses.movements.qr.show', ['token' => $token], false);
            $url = $this->absoluteQr($relative);

            return response()->json(['ok' => true, 'id' => $e->id, 'token' => $token, 'url' => $url]);
        });
    }

    // Pantalla del usuario (QR): route('expenses.movements.qr.show')
    public function showMovementQrForm($token)
    {
        $e = Expense::where('qr_token', $token)->whereNull('acknowledged_at')->firstOrFail();
        abort_if($e->qr_expires_at && now()->greaterThan($e->qr_expires_at), 410, 'El QR ha expirado.');

        return view('accounting.expenses.qr-ack', ['expense' => $e]);
    }

    // POST confirmar firma QR: route('expenses.movements.qr.ack')
    public function ackMovementWithQr(Request $req, $token)
    {
        $req->validate([
            'purpose'   => ['required', 'string', 'max:255'],
            'signature' => ['required', 'string'],

            // ✅ (opcional) evidencias desde celular si después lo agregas al form
            'evidence'   => ['nullable', 'array'],
            'evidence.*' => ['file'],
        ]);

        return DB::transaction(function () use ($req, $token) {
            $e = Expense::where('qr_token', $token)
                ->whereNull('acknowledged_at')
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($e->qr_expires_at && now()->greaterThan($e->qr_expires_at), 410, 'El QR ha expirado.');

            $sig = $this->storeDataUrl($req->signature, 'signatures');

            if (Schema::hasColumn('expenses', 'description')) $e->description = $req->purpose;

            if (Schema::hasColumn('expenses', 'counterparty_signature_path')) {
                $e->counterparty_signature_path = $sig;
            } elseif (Schema::hasColumn('expenses', 'receiver_signature_path')) {
                $e->receiver_signature_path = $sig;
            }

            if (Schema::hasColumn('expenses', 'acknowledged_at')) $e->acknowledged_at = now();
            if (Schema::hasColumn('expenses', 'qr_token')) $e->qr_token = null;
            if (Schema::hasColumn('expenses', 'qr_expires_at')) $e->qr_expires_at = null;

            $e->save();

            // ✅ evidencias opcionales (si algún día las mandas desde QR)
            $this->storeMultipleEvidencesIfAny($e, $req, 'evidence', "expenses/{$e->id}/disbursement-qr");

            return response()->json(['ok' => true, 'id' => $e->id]);
        });
    }

    // Status polling: GET /expenses/movements/qr/status/{token}
    public function movementQrStatus($token)
    {
        $e = Expense::where('qr_token', $token)->first();

        if (!$e) {
            $recent = Expense::whereNull('qr_token')
                ->whereNotNull('acknowledged_at')
                ->where('acknowledged_at', '>', now()->subMinutes(30))
                ->latest()
                ->first();

            return ['acknowledged' => (bool) $recent, 'expired' => false];
        }

        $expired = $e->qr_expires_at ? now()->greaterThan($e->qr_expires_at) : false;
        return ['acknowledged' => (bool) $e->acknowledged_at, 'expired' => $expired];
    }

    // Devolución: route('expenses.movement.return.store')
    public function storeReturn(Request $req)
    {
        $req->validate([
            'performed_at' => ['nullable', 'date'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'purpose'      => ['required', 'string', 'max:255'],
            'counterparty_id' => ['required', 'integer', 'exists:users,id'],
            'manager_id'      => ['required', 'integer', 'exists:users,id'],
            'counterparty_signature' => ['required', 'string'],
            'manager_signature'      => ['required', 'string'],
            'evidence' => ['required', 'array', 'min:1'],
            'evidence.*' => ['file'],
        ]);

        $me = auth()->user();
        if (!$me) abort(403, 'No autenticado.');

        return DB::transaction(function () use ($req, $me) {
            $createdAt = $req->performed_at ? Carbon::parse($req->performed_at) : now();

            $ctpSig = $this->storeDataUrl($req->counterparty_signature, 'signatures');
            $mgrSig = $this->storeDataUrl($req->manager_signature, 'signatures');

            $e = new Expense();
            if (Schema::hasColumn('expenses', 'created_by')) $e->created_by = $me->id;

            if (Schema::hasColumn('expenses', 'entry_kind')) $e->entry_kind = 'movimiento';
            if (Schema::hasColumn('expenses', 'expense_type')) $e->expense_type = 'movimiento';

            $e->concept = 'Devolución';
            if (Schema::hasColumn('expenses', 'description')) $e->description = $req->purpose;

            $e->expense_date = $createdAt->toDateString();
            if (Schema::hasColumn('expenses', 'performed_at')) $e->performed_at = $createdAt;

            $e->amount = $req->amount;
            if (Schema::hasColumn('expenses', 'currency')) $e->currency = 'MXN';

            if (Schema::hasColumn('expenses', 'movement_manager_id')) $e->movement_manager_id = (int) $req->manager_id;
            if (Schema::hasColumn('expenses', 'movement_counterparty_id')) $e->movement_counterparty_id = (int) $req->counterparty_id;

            if (Schema::hasColumn('expenses', 'manager_signature_path')) {
                $e->manager_signature_path = $mgrSig;
            } elseif (Schema::hasColumn('expenses', 'admin_signature_path')) {
                $e->admin_signature_path = $mgrSig;
            }

            if (Schema::hasColumn('expenses', 'counterparty_signature_path')) {
                $e->counterparty_signature_path = $ctpSig;
            } elseif (Schema::hasColumn('expenses', 'receiver_signature_path')) {
                $e->receiver_signature_path = $ctpSig;
            }

            $e->save();

            // ✅ Guardar TODAS las evidencias y registrarlas (attachment_path + evidence_paths)
            $this->storeMultipleEvidencesIfAny($e, $req, 'evidence', "expenses/{$e->id}/returns", true);

            return response()->json(['ok' => true, 'id' => $e->id]);
        });
    }

    /* ====================== PDF RECIBO (RECIBO + EVIDENCIAS FUSIONADAS) ====================== */

    /**
     * Descarga un recibo PDF usando resources/views/pdfs/transaction.blade.php
     * y ANEXA las evidencias (imagenes como páginas + PDFs fusionados).
     *
     * Requiere:
     * composer require setasign/fpdi setasign/fpdf
     */
    public function pdfReceipt(Expense $expense)
    {
        // Detectar tipo (allocation/disbursement/return) por concept
        $type = $this->expenseToTrxType($expense);

        // Determinar manager/counterparty según tipo
        $managerId = null;
        $counterpartyId = null;

        if ($type === 'allocation') {
            $managerId      = $expense->movement_manager_id ?? $expense->created_by;
            $counterpartyId = $expense->movement_boss_id ?? null;
        } elseif ($type === 'disbursement') {
            $managerId      = $expense->movement_manager_id ?? $expense->created_by;
            $counterpartyId = $expense->movement_receiver_id ?? null;
        } else { // return
            $managerId      = $expense->movement_manager_id ?? $expense->created_by;
            $counterpartyId = $expense->movement_counterparty_id ?? null;
        }

        $manager = $managerId ? User::find($managerId) : null;
        $counterparty = $counterpartyId ? User::find($counterpartyId) : null;

        // Construir objeto $trx compatible con tu PDF
        $trx = (object) [
            'id' => $expense->id,
            'type' => $type,
            'amount' => (float) ($expense->amount ?? 0),
            'created_at' => $expense->performed_at ?? $expense->created_at,
            'acknowledged_at' => $expense->acknowledged_at ?? null,

            'purpose' => $expense->description ?? $expense->concept ?? null,

            'manager_id' => $managerId,
            'counterparty_id' => $counterpartyId,
            'manager' => $manager,
            'counterparty' => $counterparty,

            // Firmas
            'manager_signature_path' => $expense->manager_signature_path
                ?? $expense->admin_signature_path
                ?? null,

            'counterparty_signature_path' => $expense->counterparty_signature_path
                ?? $expense->receiver_signature_path
                ?? null,

            // Evidencias para el blade (solo si tú quieres listar ahí también)
            'evidence_paths' => $this->getEvidencePathsFromExpense($expense),
        ];

        $folio = 'TRX-' . str_pad((string) $trx->id, 6, '0', STR_PAD_LEFT);

        // 1) Generar PDF base (recibo) a archivo temporal
        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) @mkdir($tmpDir, 0775, true);

        $basePdfPath = $tmpDir . '/receipt_' . $trx->id . '_' . uniqid() . '.pdf';

        Pdf::loadView('pdfs.transaction', compact('trx'))
            ->setPaper('letter', 'portrait')
            ->save($basePdfPath);

        // 2) Fusionar recibo + evidencias en un solo PDF
        $final = new Fpdi('P', 'mm', 'Letter');

        // 2.1 Importa páginas del recibo base
        $this->fpdiImportPdf($final, $basePdfPath);

        // 2.2 Anexa evidencias (imagenes/pdfs/otros)
        $paths = $this->getEvidencePathsFromExpense($expense);

        foreach ($paths as $relPath) {
            if (!$relPath) continue;

            $relPath = ltrim($relPath, '/');

            // Ruta física real del disk public
            try {
                $abs = Storage::disk('public')->path($relPath);
            } catch (\Throwable $e) {
                $abs = null;
            }

            if (!$abs || !is_file($abs)) {
                $this->fpdiAddTextPage($final, "Evidencia no encontrada:\n" . basename($relPath));
                continue;
            }

            $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));

            if ($ext === 'pdf') {
                $this->fpdiImportPdf($final, $abs);
                continue;
            }

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $this->fpdiAddImagePage($final, $abs);
                continue;
            }

            $this->fpdiAddTextPage($final, "Evidencia adjunta (no embebible):\n" . basename($relPath));
        }

        $out = $final->Output('S'); // string PDF

        @unlink($basePdfPath);

        return response($out, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Recibo-' . $folio . '.pdf"',
        ]);
    }

    private function expenseToTrxType(Expense $e): string
    {
        $concept = (string) ($e->concept ?? '');

        if (stripos($concept, 'Fondo') !== false) return 'allocation';
        if (stripos($concept, 'Entrega') !== false) return 'disbursement';
        if (stripos($concept, 'Devolución') !== false || stripos($concept, 'Devolucion') !== false) return 'return';

        // fallback:
        return 'disbursement';
    }

    /* ====================== FILTROS BASE ====================== */

    private function baseFilteredQuery(Request $request)
    {
        $q = Expense::query();

        $needle = trim((string) $request->input('q', ''));
        if ($needle !== '') {
            $q->where(function ($w) use ($needle) {
                if (Schema::hasColumn('expenses', 'concept')) $w->orWhere('concept', 'like', "%{$needle}%");
                if (Schema::hasColumn('expenses', 'description')) $w->orWhere('description', 'like', "%{$needle}%");
                if (Schema::hasColumn('expenses', 'vendor')) $w->orWhere('vendor', 'like', "%{$needle}%");
                if (Schema::hasColumn('expenses', 'tags')) $w->orWhere('tags', 'like', "%{$needle}%");
            });
        }

        $cat = $request->input('category_id');
        if ($cat && Schema::hasColumn('expenses', 'expense_category_id')) {
            $q->where('expense_category_id', (int) $cat);
        }

        $veh = $request->input('vehicle_id');
        if ($veh && Schema::hasColumn('expenses', 'vehicle_id')) {
            $q->where('vehicle_id', (int) $veh);
        }

        $period = $request->input('payroll_period_id');
        if ($period) {
            if (Schema::hasColumn('expenses', 'payroll_period_id')) $q->where('payroll_period_id', (int) $period);
            elseif (Schema::hasColumn('expenses', 'payroll_period')) $q->where('payroll_period', (string) $period);
        }

        $status = $request->input('status');
        if ($status && Schema::hasColumn('expenses', 'status')) {
            $q->where('status', (string) $status);
        }

        $from = $request->input('from');
        if ($from && Schema::hasColumn('expenses', 'expense_date')) {
            $q->whereDate('expense_date', '>=', $from);
        }
        $to = $request->input('to');
        if ($to && Schema::hasColumn('expenses', 'expense_date')) {
            $q->whereDate('expense_date', '<=', $to);
        }

        return $q;
    }

    /* ====================== HELPERS PIN + QR + FIRMA ====================== */

    private function storeDataUrl(?string $dataUrl, string $folder): ?string
    {
        if (!$dataUrl || !str_contains($dataUrl, ',')) return null;
        [$meta, $content] = explode(',', $dataUrl, 2);
        $ext  = str_contains($meta, 'image/png') ? 'png' : 'jpg';
        $path = $folder . '/' . uniqid('', true) . ".$ext";
        Storage::disk('public')->put($path, base64_decode($content));
        return $path;
    }

    private function absoluteQr(string $relativePath): string
    {
        return rtrim(self::QR_BASE, '/') . $relativePath;
    }

    private function checkPinFlexible(string $plain, ?string $stored): bool
    {
        if (!$stored) return false;

        if (Str::startsWith($stored, ['$2y$', '$2a$', '$2b$'])) return Hash::check($plain, $stored);
        if (Str::startsWith($stored, ['$argon2id$', '$argon2i$'])) return password_verify($plain, $stored);

        if (config('app.allow_legacy_md5', false) && preg_match('/^[a-f0-9]{32}$/i', $stored)) {
            return hash_equals(strtolower($stored), md5($plain));
        }
        return false;
    }

    /**
     * ✅ Validación de NIP para CUALQUIER usuario (su propio NIP)
     */
    private function assertUserPinOrFail(User $user, string $nip): void
    {
        if (!$this->checkPinFlexible($nip, $user->approval_pin_hash ?? null)) {
            abort(422, 'NIP incorrecto.');
        }

        // Rehash a bcrypt si viene legacy o needsRehash
        if (!Str::startsWith((string) $user->approval_pin_hash, ['$2y$', '$2a$', '$2b$'])) {
            $user->approval_pin_hash = Hash::make($nip);
            $user->save();
        } else {
            if (Hash::needsRehash($user->approval_pin_hash)) {
                $user->approval_pin_hash = Hash::make($nip);
                $user->save();
            }
        }
    }

    /* ====================== EVIDENCIAS (MULTI) ====================== */

    /**
     * Obtiene las rutas de evidencias desde:
     * - evidence_paths (JSON) si existe
     * - y/o attachment_path si existe
     */
    private function getEvidencePathsFromExpense(Expense $e): array
    {
        $paths = [];

        if (Schema::hasColumn('expenses', 'evidence_paths') && !empty($e->evidence_paths)) {
            $raw = $e->evidence_paths;
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) $paths = array_merge($paths, $decoded);
            } elseif (is_array($raw)) {
                $paths = array_merge($paths, $raw);
            }
        }

        if (Schema::hasColumn('expenses', 'attachment_path') && $e->attachment_path) {
            $paths[] = $e->attachment_path;
        }

        // unique y limpios
        $paths = array_values(array_unique(array_filter(array_map('strval', $paths))));
        return $paths;
    }

    /**
     * Guarda múltiples evidencias desde un input file array (ej: evidence[])
     * y persiste:
     * - attachment_path = primer archivo (si $setFirstAsAttachment = true o si está vacío)
     * - evidence_paths (JSON) si la columna existe
     */
    private function storeMultipleEvidencesIfAny(Expense $e, Request $req, string $inputName, string $folder, bool $setFirstAsAttachment = false): void
    {
        if (!$req->hasFile($inputName)) return;

        $files = $req->file($inputName, []);
        if (!is_array($files) || empty($files)) return;

        $stored = [];

        foreach ($files as $file) {
            if (!$file) continue;
            $path = $file->store($folder, 'public');
            $stored[] = $path;
        }

        if (empty($stored)) return;

        // attachment_path (primero)
        if (Schema::hasColumn('expenses', 'attachment_path')) {
            if ($setFirstAsAttachment || !$e->attachment_path) {
                $e->attachment_path = $stored[0];
                if (Schema::hasColumn('expenses', 'attachment_name')) $e->attachment_name = $files[0]->getClientOriginalName();
                if (Schema::hasColumn('expenses', 'attachment_mime')) $e->attachment_mime = $files[0]->getClientMimeType();
                if (Schema::hasColumn('expenses', 'attachment_size')) $e->attachment_size = $files[0]->getSize();
            }
        }

        // evidence_paths JSON
        if (Schema::hasColumn('expenses', 'evidence_paths')) {
            $prev = $this->getEvidencePathsFromExpense($e);
            $merged = array_values(array_unique(array_merge($prev, $stored)));
            $e->evidence_paths = json_encode($merged, JSON_UNESCAPED_SLASHES);
        }

        if ($e->isDirty()) $e->save();
    }

    /* ====================== PDF MERGE HELPERS (FPDI) ====================== */

    /**
     * Importa todas las páginas de un PDF al documento final.
     */
    private function fpdiImportPdf(Fpdi $pdf, string $sourcePath): void
    {
        try {
            $pageCount = $pdf->setSourceFile($sourcePath);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tplId = $pdf->importPage($pageNo);
                $size  = $pdf->getTemplateSize($tplId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);
            }
        } catch (\Throwable $e) {
            $this->fpdiAddTextPage($pdf, "No se pudo anexar PDF:\n" . basename($sourcePath) . "\n" . $e->getMessage());
        }
    }

    /**
     * Agrega una página Letter y coloca una imagen escalada.
     */
    private function fpdiAddImagePage(Fpdi $pdf, string $imagePath): void
    {
        $pdf->AddPage('P', 'Letter');

        // Letter en mm (aprox)
        $pageW = 216; // 8.5in
        $pageH = 279; // 11in
        $m = 10;      // margen
        $maxW = $pageW - ($m * 2);
        $maxH = $pageH - ($m * 2);

        $info = @getimagesize($imagePath);
        if (!$info) {
            $this->fpdiAddTextPage($pdf, "No se pudo leer imagen:\n" . basename($imagePath));
            return;
        }

        $imgW = (float) $info[0];
        $imgH = (float) $info[1];

        $ratio = min($maxW / $imgW, $maxH / $imgH);
        $w = $imgW * $ratio;
        $h = $imgH * $ratio;

        $x = ($pageW - $w) / 2;
        $y = ($pageH - $h) / 2;

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetXY($m, 6);
        $pdf->Cell(0, 6, 'Evidencia: ' . basename($imagePath), 0, 1);

        $pdf->Image($imagePath, $x, $y, $w, $h);
    }

    /**
     * Agrega una página con texto (para errores o evidencias no embebibles).
     */
    private function fpdiAddTextPage(Fpdi $pdf, string $text): void
    {
        $pdf->AddPage('P', 'Letter');
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->SetTextColor(15, 23, 42);

        $pdf->SetXY(12, 20);
        $pdf->MultiCell(0, 6, "ANEXO / EVIDENCIA", 0, 'L');

        $pdf->SetFont('Helvetica', '', 11);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->SetXY(12, 34);
        $pdf->MultiCell(0, 6, $text, 0, 'L');
    }
}
