<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Company;
use App\Models\Publication;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CuentasDashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('auth')];
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $email = $user?->email;
        $userId = $user?->id;

        $today = Carbon::today();

        $companies = Company::orderBy('name')->get();
        $companyId = $request->filled('company_id') ? (int) $request->company_id : null;

        /*
        |--------------------------------------------------------------------------
        | IMPORTANTE:
        | Antes de consultar cuentas por cobrar, sincronizamos las publicaciones
        | de tipo venta para que existan en account_receivables.
        |--------------------------------------------------------------------------
        */
        $this->syncVentaPublicationsToReceivables($companyId, $email, $userId);

        $rx = AccountReceivable::query()->with('company');
        $px = AccountPayable::query()->with('company');

        /*
        |--------------------------------------------------------------------------
        | Filtro por usuario
        | OJO: si created_by guarda ID, usamos ID.
        | Si guarda email, usamos email.
        |--------------------------------------------------------------------------
        */
        if ($userId || $email) {
            $rx->where(function ($q) use ($userId, $email) {
                if ($userId) {
                    $q->orWhere('created_by', $userId);
                }

                if ($email) {
                    $q->orWhere('created_by', $email);
                }
            });

            $px->where(function ($q) use ($userId, $email) {
                if ($userId) {
                    $q->orWhere('created_by', $userId);
                }

                if ($email) {
                    $q->orWhere('created_by', $email);
                }
            });
        }

        if ($companyId) {
            $rx->where('company_id', $companyId);
            $px->where('company_id', $companyId);
        }

        $receivables = (clone $rx)->get();
        $payables    = (clone $px)->get();

        $saldoR = fn($r) => max((float) $r->amount - (float) $r->amount_paid, 0.0);
        $saldoP = fn($p) => max((float) $p->amount - (float) $p->amount_paid, 0.0);

        $totalPorCobrar = $receivables
            ->filter(fn($r) => !in_array($r->status, ['cobrado', 'cancelado'], true))
            ->sum(fn($r) => $saldoR($r));

        $totalPorPagar = $payables
            ->filter(fn($p) => !in_array($p->status, ['pagado', 'cancelado'], true))
            ->sum(fn($p) => $saldoP($p));

        $balanceNeto = $totalPorCobrar - $totalPorPagar;

        $cobradoMes = $receivables
            ->filter(fn($r) =>
                $r->status === 'cobrado'
                && $r->payment_date
                && Carbon::parse($r->payment_date)->isSameMonth($today)
            )
            ->sum(fn($r) => (float) $r->amount);

        $pagadoMes = $payables
            ->filter(fn($p) =>
                $p->status === 'pagado'
                && $p->payment_date
                && Carbon::parse($p->payment_date)->isSameMonth($today)
            )
            ->sum(fn($p) => (float) $p->amount);

        $urgentPayments = $payables->filter(function ($p) use ($today) {
            if (in_array($p->status, ['pagado', 'cancelado'], true)) {
                return false;
            }

            $due = $p->due_date ? Carbon::parse($p->due_date) : null;

            if (!$due) {
                return false;
            }

            return in_array($p->status, ['urgente', 'atrasado'], true) || $due->lt($today);
        })->sortBy(fn($p) => $p->due_date)->values();

        $overdueReceivables = $receivables->filter(function ($r) use ($today) {
            if (in_array($r->status, ['cobrado', 'cancelado'], true)) {
                return false;
            }

            $due = $r->due_date ? Carbon::parse($r->due_date) : null;

            return $due ? $due->lt($today) : false;
        })->sortBy(fn($r) => $r->due_date)->values();

        $upcomingPayments = $payables->filter(function ($p) use ($today) {
            if (in_array($p->status, ['pagado', 'cancelado'], true)) {
                return false;
            }

            $due = $p->due_date ? Carbon::parse($p->due_date) : null;

            if (!$due) {
                return false;
            }

            return $due->gte($today) && $today->diffInDays($due) <= 15;
        })->sortBy(fn($p) => $p->due_date)->values();

        $upcomingReceivables = $receivables->filter(function ($r) use ($today) {
            if (in_array($r->status, ['cobrado', 'cancelado'], true)) {
                return false;
            }

            $due = $r->due_date ? Carbon::parse($r->due_date) : null;

            if (!$due) {
                return false;
            }

            return $due->gte($today) && $today->diffInDays($due) <= 15;
        })->sortBy(fn($r) => $r->due_date)->values();

        $aging = [
            '0-30'  => 0.0,
            '31-60' => 0.0,
            '61-90' => 0.0,
            '90+'   => 0.0,
        ];

        foreach ($receivables as $r) {
            if (in_array($r->status, ['cobrado', 'cancelado'], true)) {
                continue;
            }

            $due = $r->due_date ? Carbon::parse($r->due_date) : null;

            if (!$due || !$due->lt($today)) {
                continue;
            }

            $days = $due->diffInDays($today);
            $amt = $saldoR($r);

            if ($days <= 30) {
                $aging['0-30'] += $amt;
            } elseif ($days <= 60) {
                $aging['31-60'] += $amt;
            } elseif ($days <= 90) {
                $aging['61-90'] += $amt;
            } else {
                $aging['90+'] += $amt;
            }
        }

        $labels = [];
        $inByDay = [];
        $outByDay = [];
        $netByDay = [];

        for ($i = 0; $i <= 30; $i++) {
            $d = $today->copy()->addDays($i);
            $key = $d->toDateString();

            $labels[] = $d->format('d/m');

            $incoming = $receivables->filter(fn($r) =>
                !in_array($r->status, ['cobrado', 'cancelado'], true)
                && $r->due_date
                && Carbon::parse($r->due_date)->toDateString() === $key
            )->sum(fn($r) => $saldoR($r));

            $outgoing = $payables->filter(fn($p) =>
                !in_array($p->status, ['pagado', 'cancelado'], true)
                && $p->due_date
                && Carbon::parse($p->due_date)->toDateString() === $key
            )->sum(fn($p) => $saldoP($p));

            $inByDay[] = (float) $incoming;
            $outByDay[] = (float) $outgoing;
            $netByDay[] = (float) ($incoming - $outgoing);
        }

        $alertsCount = $urgentPayments->count() + $overdueReceivables->count();

        return view('accounting.cuentas_dashboard', compact(
            'companies',
            'companyId',
            'totalPorCobrar',
            'totalPorPagar',
            'balanceNeto',
            'cobradoMes',
            'pagadoMes',
            'alertsCount',
            'urgentPayments',
            'overdueReceivables',
            'upcomingPayments',
            'upcomingReceivables',
            'aging',
            'labels',
            'inByDay',
            'outByDay',
            'netByDay'
        ));
    }

    private function syncVentaPublicationsToReceivables(?int $companyId, ?string $email, $userId): void
    {
        if (!class_exists(Publication::class)) {
            return;
        }

        $publicationModel = new Publication();
        $publicationTable = $publicationModel->getTable();

        $receivableModel = new AccountReceivable();
        $receivableTable = $receivableModel->getTable();

        if (!Schema::hasTable($publicationTable) || !Schema::hasTable($receivableTable)) {
            return;
        }

        $publicationColumns = Schema::getColumnListing($publicationTable);
        $receivableColumns = Schema::getColumnListing($receivableTable);

        $saleColumn = collect([
            'type',
            'publication_type',
            'operation_type',
            'operation',
            'transaction_type',
        ])->first(fn($column) => in_array($column, $publicationColumns, true));

        if (!$saleColumn) {
            return;
        }

        $query = Publication::query()
            ->whereIn($saleColumn, ['venta', 'Venta', 'VENTA', 'sale', 'Sale', 'SALE']);

        if ($companyId && in_array('company_id', $publicationColumns, true)) {
            $query->where('company_id', $companyId);
        }

        if (($userId || $email) && in_array('created_by', $publicationColumns, true)) {
            $query->where(function ($q) use ($userId, $email) {
                if ($userId) {
                    $q->orWhere('created_by', $userId);
                }

                if ($email) {
                    $q->orWhere('created_by', $email);
                }
            });
        }

        $publications = $query->get();

        foreach ($publications as $publication) {
            $reference = 'PUB-' . $publication->id;

            $exists = AccountReceivable::query()
                ->when(
                    in_array('publication_id', $receivableColumns, true),
                    fn($q) => $q->orWhere('publication_id', $publication->id)
                )
                ->when(
                    in_array('reference', $receivableColumns, true),
                    fn($q) => $q->orWhere('reference', $reference)
                )
                ->exists();

            if ($exists) {
                continue;
            }

            $amount = $this->firstAvailableValue($publication, [
                'amount',
                'price',
                'total',
                'sale_price',
                'selling_price',
                'final_price',
            ], 0);

            if ((float) $amount <= 0) {
                continue;
            }

            $receivable = new AccountReceivable();

            $payload = [
                'publication_id' => $publication->id,
                'company_id'    => $publication->company_id ?? null,
                'client_name'   => $this->firstAvailableValue($publication, [
                    'client_name',
                    'customer_name',
                    'buyer_name',
                    'contact_name',
                    'name',
                    'title',
                ], 'Cliente / Venta'),
                'title'         => $this->firstAvailableValue($publication, [
                    'title',
                    'name',
                    'description',
                ], 'Venta de publicación'),
                'reference'     => $reference,
                'amount'        => (float) $amount,
                'amount_paid'   => 0,
                'status'        => 'pendiente',
                'due_date'      => $this->firstAvailableValue($publication, [
                    'due_date',
                    'payment_due_date',
                    'expires_at',
                    'created_at',
                ], now()->addDays(15)),
                'created_by'    => $publication->created_by ?? $email ?? $userId,
            ];

            foreach ($payload as $column => $value) {
                if (in_array($column, $receivableColumns, true)) {
                    $receivable->{$column} = $value;
                }
            }

            $receivable->save();
        }
    }

    private function firstAvailableValue($model, array $fields, $default = null)
    {
        foreach ($fields as $field) {
            if (isset($model->{$field}) && $model->{$field} !== '') {
                return $model->{$field};
            }
        }

        return $default;
    }
}