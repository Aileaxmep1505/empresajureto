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

class AlertsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('auth')];
    }

    public function index(Request $request)
    {
        $email = Auth::user()?->email;
        $today = Carbon::today();

        $companies = Company::orderBy('name')->get();
        $companyId = $request->filled('company_id') ? (int)$request->company_id : null;

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

        $receivables = $rx->get();
        $payables = $px->get();

        $urgentPayments = $payables->filter(function ($p) use ($today) {
            if (in_array($p->status, ['pagado','cancelado'], true)) return false;
            $due = $p->due_date ? Carbon::parse($p->due_date) : null;
            if (!$due) return false;
            return in_array($p->status, ['urgente','atrasado'], true) || $due->lt($today);
        })->sortBy(fn($p) => $p->due_date)->values();

        $overdueReceivables = $receivables->filter(function ($r) use ($today) {
            if (in_array($r->status, ['cobrado','cancelado'], true)) return false;
            $due = $r->due_date ? Carbon::parse($r->due_date) : null;
            return $due ? $due->lt($today) : false;
        })->sortBy(fn($r) => $r->due_date)->values();

        return view('accounting.alerts', compact('companies','companyId','urgentPayments','overdueReceivables'));
    }
}