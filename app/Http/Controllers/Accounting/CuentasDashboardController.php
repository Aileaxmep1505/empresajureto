<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CuentasDashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        // ✅ Laravel 12 style (sin $this->middleware())
        return [new Middleware('auth')];
    }

    public function index(Request $request)
    {
        $email = Auth::user()?->email;
        $today = Carbon::today();

        $companies = Company::orderBy('name')->get();
        $companyId = $request->filled('company_id') ? (int) $request->company_id : null;

        $rx = AccountReceivable::query()->with('company');
        $px = AccountPayable::query()->with('company');

        if ($email) {
            $rx->where('created_by', $email);
            $px->where('created_by', $email);
        }
        if ($companyId) {
            $rx->where('company_id', $companyId);
            $px->where('company_id', $companyId);
        }

        $receivables = (clone $rx)->get();
        $payables    = (clone $px)->get();

        $saldoR = fn($r) => max((float)$r->amount - (float)$r->amount_paid, 0.0);
        $saldoP = fn($p) => max((float)$p->amount - (float)$p->amount_paid, 0.0);

        // KPIs
        $totalPorCobrar = $receivables
            ->filter(fn($r) => !in_array($r->status, ['cobrado', 'cancelado'], true))
            ->sum(fn($r) => $saldoR($r));

        $totalPorPagar = $payables
            ->filter(fn($p) => !in_array($p->status, ['pagado', 'cancelado'], true))
            ->sum(fn($p) => $saldoP($p));

        $balanceNeto = $totalPorCobrar - $totalPorPagar;

        $cobradoMes = $receivables
            ->filter(fn($r) => $r->status === 'cobrado' && $r->payment_date && Carbon::parse($r->payment_date)->isSameMonth($today))
            ->sum(fn($r) => (float)$r->amount);

        $pagadoMes = $payables
            ->filter(fn($p) => $p->status === 'pagado' && $p->payment_date && Carbon::parse($p->payment_date)->isSameMonth($today))
            ->sum(fn($p) => (float)$p->amount);

        // Alertas
        $urgentPayments = $payables->filter(function ($p) use ($today) {
            if (in_array($p->status, ['pagado', 'cancelado'], true)) return false;
            $due = $p->due_date ? Carbon::parse($p->due_date) : null;
            if (!$due) return false;
            return in_array($p->status, ['urgente', 'atrasado'], true) || $due->lt($today);
        })->sortBy(fn($p) => $p->due_date)->values();

        $overdueReceivables = $receivables->filter(function ($r) use ($today) {
            if (in_array($r->status, ['cobrado', 'cancelado'], true)) return false;
            $due = $r->due_date ? Carbon::parse($r->due_date) : null;
            return $due ? $due->lt($today) : false;
        })->sortBy(fn($r) => $r->due_date)->values();

        // Próximos 15 días
        $upcomingPayments = $payables->filter(function ($p) use ($today) {
            if (in_array($p->status, ['pagado', 'cancelado'], true)) return false;
            $due = $p->due_date ? Carbon::parse($p->due_date) : null;
            if (!$due) return false;
            return $due->gte($today) && $today->diffInDays($due) <= 15;
        })->sortBy(fn($p) => $p->due_date)->values();

        $upcomingReceivables = $receivables->filter(function ($r) use ($today) {
            if (in_array($r->status, ['cobrado', 'cancelado'], true)) return false;
            $due = $r->due_date ? Carbon::parse($r->due_date) : null;
            if (!$due) return false;
            return $due->gte($today) && $today->diffInDays($due) <= 15;
        })->sortBy(fn($r) => $r->due_date)->values();

        // Aging (vencido)
        $aging = ['0-30'=>0.0,'31-60'=>0.0,'61-90'=>0.0,'90+'=>0.0];
        foreach ($receivables as $r) {
            if (in_array($r->status, ['cobrado','cancelado'], true)) continue;
            $due = $r->due_date ? Carbon::parse($r->due_date) : null;
            if (!$due || !$due->lt($today)) continue;

            $days = $due->diffInDays($today);
            $amt = $saldoR($r);

            if ($days <= 30) $aging['0-30'] += $amt;
            elseif ($days <= 60) $aging['31-60'] += $amt;
            elseif ($days <= 90) $aging['61-90'] += $amt;
            else $aging['90+'] += $amt;
        }

        // Flujo 30 días
        $labels=[]; $inByDay=[]; $outByDay=[]; $netByDay=[];
        for ($i=0; $i<=30; $i++) {
            $d = $today->copy()->addDays($i);
            $key = $d->toDateString();
            $labels[] = $d->format('d/m');

            $incoming = $receivables->filter(fn($r) =>
                !in_array($r->status, ['cobrado','cancelado'], true) &&
                $r->due_date && Carbon::parse($r->due_date)->toDateString() === $key
            )->sum(fn($r) => $saldoR($r));

            $outgoing = $payables->filter(fn($p) =>
                !in_array($p->status, ['pagado','cancelado'], true) &&
                $p->due_date && Carbon::parse($p->due_date)->toDateString() === $key
            )->sum(fn($p) => $saldoP($p));

            $inByDay[] = (float)$incoming;
            $outByDay[] = (float)$outgoing;
            $netByDay[] = (float)($incoming - $outgoing);
        }

        $alertsCount = $urgentPayments->count() + $overdueReceivables->count();

        return view('accounting.cuentas_dashboard', compact(
            'companies','companyId',
            'totalPorCobrar','totalPorPagar','balanceNeto','cobradoMes','pagadoMes','alertsCount',
            'urgentPayments','overdueReceivables','upcomingPayments','upcomingReceivables',
            'aging','labels','inByDay','outByDay','netByDay'
        ));
    }
}