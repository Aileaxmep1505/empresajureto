<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\AgendaEvent;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ReportsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('auth')];
    }

    public function index(Request $request)
    {
        $tz = 'America/Mexico_City';

        $dateFrom = $request->get('date_from')
            ?: Carbon::now($tz)->startOfMonth()->toDateString();

        $dateTo = $request->get('date_to')
            ?: Carbon::now($tz)->endOfMonth()->toDateString();

        try {
            $from = Carbon::parse($dateFrom, $tz)->startOfDay();
        } catch (\Throwable $e) {
            $from = Carbon::now($tz)->startOfMonth();
            $dateFrom = $from->toDateString();
        }

        try {
            $to = Carbon::parse($dateTo, $tz)->endOfDay();
        } catch (\Throwable $e) {
            $to = Carbon::now($tz)->endOfMonth();
            $dateTo = $to->toDateString();
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            $dateFrom = $from->toDateString();
            $dateTo = $to->toDateString();
        }

        $companies = Company::query()->orderBy('name')->get();

        $search = trim((string) $request->get('search', ''));

        $payments = AccountPayable::query()
            ->with('company')
            ->when($request->filled('company_id'), fn($q) => $q->where('company_id', (int) $request->company_id))
            ->when($request->filled('category'), fn($q) => $q->where('category', $request->category))
            ->when($request->filled('frequency'), fn($q) => $q->where('frequency', $request->frequency))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('concept', 'like', "%{$search}%")
                        ->orWhere('folio', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhere('vendor_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('due_date')
            ->get();

        $receivables = AccountReceivable::query()
            ->with('company')
            ->when($request->filled('company_id'), fn($q) => $q->where('company_id', (int) $request->company_id))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('client_name', 'like', "%{$search}%")
                        ->orWhere('folio', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%");
                });
            })
            ->orderBy('due_date')
            ->get();

        $agendaEvents = AgendaEvent::query()
            ->whereBetween('start_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('start_at')
            ->get();

        return view('accounting.reports.index', compact(
            'payments',
            'receivables',
            'agendaEvents',
            'companies',
            'dateFrom',
            'dateTo'
        ));
    }
}