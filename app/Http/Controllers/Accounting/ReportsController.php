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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

        $companies = Company::query()
            ->orderBy('name')
            ->get();

        $search = trim((string) $request->get('search', ''));
        $companyId = $request->filled('company_id') ? (int) $request->company_id : null;

        $payments = AccountPayable::query()
            ->with('company')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->when($request->filled('frequency'), fn ($q) => $q->where('frequency', $request->frequency))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
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
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
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

        /*
        |--------------------------------------------------------------------------
        | HELPERS
        |--------------------------------------------------------------------------
        */

        $detectColumn = function (string $table, array $candidates): ?string {
            foreach ($candidates as $column) {
                if (Schema::hasColumn($table, $column)) {
                    return $column;
                }
            }
            return null;
        };

        $applyCompanyFilter = function ($query, string $table, ?int $companyId) {
            if (!$companyId) {
                return $query;
            }

            foreach (['company_id', 'companies_id', 'empresa_id'] as $companyColumn) {
                if (Schema::hasColumn($table, $companyColumn)) {
                    $query->where($companyColumn, $companyId);
                    break;
                }
            }

            return $query;
        };

        $sumFromCandidateTables = function (
            array $tables,
            array $dateCandidates,
            array $amountCandidates,
            Carbon $start,
            Carbon $end,
            ?int $companyId = null,
            ?callable $extraFilter = null
        ) use ($detectColumn, $applyCompanyFilter): float {
            foreach ($tables as $table) {
                if (!Schema::hasTable($table)) {
                    continue;
                }

                $dateColumn = $detectColumn($table, $dateCandidates);
                $amountColumn = $detectColumn($table, $amountCandidates);

                if (!$dateColumn || !$amountColumn) {
                    continue;
                }

                try {
                    $q = DB::table($table);
                    $q = $applyCompanyFilter($q, $table, $companyId);
                    $q->whereBetween($dateColumn, [$start->toDateTimeString(), $end->toDateTimeString()]);

                    if ($extraFilter) {
                        $extraFilter($q, $table);
                    }

                    return (float) $q->sum($amountColumn);
                } catch (\Throwable $e) {
                    continue;
                }
            }

            return 0.0;
        };

        $safeDate = function ($value) {
            try {
                return $value ? Carbon::parse($value) : null;
            } catch (\Throwable $e) {
                return null;
            }
        };

        $balance = fn ($r) => max((float) ($r->amount ?? 0) - (float) ($r->amount_paid ?? 0), 0);

        $receivableEffectiveStatus = function ($r) use ($safeDate, $tz) {
            $today = Carbon::now($tz)->startOfDay();
            $raw = Str::lower((string) ($r->status ?? 'pendiente'));
            $due = $safeDate($r->due_date);

            if (!in_array($raw, ['cobrado', 'cancelado'], true) && $due && $due->lt($today)) {
                return 'atrasado';
            }

            return $raw ?: 'pendiente';
        };

        $paymentEffectiveStatus = function ($p) use ($safeDate, $tz) {
            $today = Carbon::now($tz)->startOfDay();
            $raw = Str::lower((string) ($p->status ?? 'pendiente'));
            $due = $safeDate($p->due_date);

            if (!in_array($raw, ['pagado', 'cancelado'], true) && $due && $due->lt($today)) {
                return 'atrasado';
            }

            return $raw ?: 'pendiente';
        };

        /*
        |--------------------------------------------------------------------------
        | RENTABILIDAD / VENTAS / GASTOS / COMPRAS-PUBLICACIONES
        |--------------------------------------------------------------------------
        */

        $salesInPeriod = $sumFromCandidateTables(
            ['ventas', 'venta', 'sales'],
            ['fecha_venta', 'sale_date', 'sold_at', 'created_at', 'fecha', 'date'],
            ['total_neto', 'total', 'importe_total', 'monto_total', 'amount', 'subtotal'],
            $from,
            $to,
            $companyId
        );

        $expensesInPeriod = 0.0;

        if (Schema::hasTable('expenses')) {
            try {
                $expenseDateColumn = $detectColumn('expenses', [
                    'expense_date',
                    'performed_at',
                    'created_at',
                    'fecha',
                    'date',
                ]);

                $expenseAmountColumn = $detectColumn('expenses', [
                    'amount',
                    'total',
                    'importe',
                    'monto',
                ]);

                if ($expenseDateColumn && $expenseAmountColumn) {
                    $qExpenses = DB::table('expenses');
                    $qExpenses = $applyCompanyFilter($qExpenses, 'expenses', $companyId);
                    $qExpenses->whereBetween($expenseDateColumn, [$from->toDateTimeString(), $to->toDateTimeString()]);

                    if (Schema::hasColumn('expenses', 'entry_kind')) {
                        $qExpenses->where(function ($w) {
                            $w->whereNull('entry_kind')
                                ->orWhere('entry_kind', 'gasto');
                        });
                    }

                    if (Schema::hasColumn('expenses', 'status')) {
                        $qExpenses->where(function ($w) {
                            $w->whereNull('status')
                                ->orWhere('status', 'paid')
                                ->orWhere('status', 'pagado')
                                ->orWhere('status', 'approved')
                                ->orWhere('status', 'aprobado');
                        });
                    }

                    $expensesInPeriod = (float) $qExpenses->sum($expenseAmountColumn);
                }
            } catch (\Throwable $e) {
                $expensesInPeriod = 0.0;
            }
        }

        $publicationPurchasesInPeriod = $sumFromCandidateTables(
            [
                'publicaciones',
                'publication_purchases',
                'publication_purchase_ai',
                'publicationpurchaseais',
                'publication_purchase_ais',
            ],
            ['publication_date', 'purchase_date', 'fecha', 'created_at', 'date'],
            ['total', 'amount', 'cost', 'price', 'importe', 'monto'],
            $from,
            $to,
            $companyId
        );

        $grossProfitPeriod = $salesInPeriod - $publicationPurchasesInPeriod;
        $netProfitPeriod   = $salesInPeriod - $expensesInPeriod - $publicationPurchasesInPeriod;
        $profitMargin      = $salesInPeriod > 0
            ? round(($netProfitPeriod / $salesInPeriod) * 100, 1)
            : 0;

        $profitSummary = [
            'sales'         => $salesInPeriod,
            'expenses'      => $expensesInPeriod,
            'publications'  => $publicationPurchasesInPeriod,
            'gross_profit'  => $grossProfitPeriod,
            'net_profit'    => $netProfitPeriod,
            'margin'        => $profitMargin,
        ];

        /*
        |--------------------------------------------------------------------------
        | HISTÓRICO 6 MESES DE RENTABILIDAD
        |--------------------------------------------------------------------------
        */

        $monthlyProfit = collect();

        for ($i = 5; $i >= 0; $i--) {
            $monthStart = $from->copy()->startOfMonth()->subMonths($i);
            $monthEnd   = $monthStart->copy()->endOfMonth();

            $mSales = $sumFromCandidateTables(
                ['ventas', 'venta', 'sales'],
                ['fecha_venta', 'sale_date', 'sold_at', 'created_at', 'fecha', 'date'],
                ['total_neto', 'total', 'importe_total', 'monto_total', 'amount', 'subtotal'],
                $monthStart,
                $monthEnd,
                $companyId
            );

            $mExpenses = 0.0;

            if (Schema::hasTable('expenses')) {
                try {
                    $expenseDateColumn = $detectColumn('expenses', [
                        'expense_date',
                        'performed_at',
                        'created_at',
                        'fecha',
                        'date',
                    ]);

                    $expenseAmountColumn = $detectColumn('expenses', [
                        'amount',
                        'total',
                        'importe',
                        'monto',
                    ]);

                    if ($expenseDateColumn && $expenseAmountColumn) {
                        $qExpenses = DB::table('expenses');
                        $qExpenses = $applyCompanyFilter($qExpenses, 'expenses', $companyId);
                        $qExpenses->whereBetween($expenseDateColumn, [$monthStart->toDateTimeString(), $monthEnd->toDateTimeString()]);

                        if (Schema::hasColumn('expenses', 'entry_kind')) {
                            $qExpenses->where(function ($w) {
                                $w->whereNull('entry_kind')
                                    ->orWhere('entry_kind', 'gasto');
                            });
                        }

                        if (Schema::hasColumn('expenses', 'status')) {
                            $qExpenses->where(function ($w) {
                                $w->whereNull('status')
                                    ->orWhere('status', 'paid')
                                    ->orWhere('status', 'pagado')
                                    ->orWhere('status', 'approved')
                                    ->orWhere('status', 'aprobado');
                            });
                        }

                        $mExpenses = (float) $qExpenses->sum($expenseAmountColumn);
                    }
                } catch (\Throwable $e) {
                    $mExpenses = 0.0;
                }
            }

            $mPublications = $sumFromCandidateTables(
                [
                    'publicaciones',
                    'publication_purchases',
                    'publication_purchase_ai',
                    'publicationpurchaseais',
                    'publication_purchase_ais',
                ],
                ['publication_date', 'purchase_date', 'fecha', 'created_at', 'date'],
                ['total', 'amount', 'cost', 'price', 'importe', 'monto'],
                $monthStart,
                $monthEnd,
                $companyId
            );

            $mNetProfit = $mSales - $mExpenses - $mPublications;

            $monthlyProfit->push([
                'month_key'    => $monthStart->format('Y-m'),
                'month_label'  => Str::ucfirst($monthStart->translatedFormat('M y')),
                'sales'        => $mSales,
                'expenses'     => $mExpenses,
                'publications' => $mPublications,
                'net_profit'   => $mNetProfit,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | FLUJO DE CAJA PROYECTADO + PRÓXIMOS COBROS
        |--------------------------------------------------------------------------
        */

        $receivablesOpen = $receivables
            ->filter(fn ($r) => !in_array($receivableEffectiveStatus($r), ['cobrado', 'cancelado'], true))
            ->values();

        $paymentsOpen = $payments
            ->filter(fn ($p) => !in_array($paymentEffectiveStatus($p), ['pagado', 'cancelado'], true))
            ->values();

        $cashFlowProjection = collect();

        for ($i = 0; $i < 6; $i++) {
            $monthStart = $from->copy()->startOfMonth()->addMonths($i);
            $monthEnd   = $monthStart->copy()->endOfMonth();

            $receivablesMonth = $receivablesOpen
                ->filter(function ($r) use ($safeDate, $monthStart, $monthEnd) {
                    $due = $safeDate($r->due_date);
                    return $due && $due->betweenIncluded($monthStart, $monthEnd);
                })
                ->values();

            $paymentsMonth = $paymentsOpen
                ->filter(function ($p) use ($safeDate, $monthStart, $monthEnd) {
                    $due = $safeDate($p->due_date);
                    return $due && $due->betweenIncluded($monthStart, $monthEnd);
                })
                ->values();

            $incomingDue = $receivablesMonth->sum(fn ($r) => $balance($r));
            $outgoingDue = $paymentsMonth->sum(fn ($p) => (float) ($p->amount ?? 0));

            $collectedReal = $receivables
                ->filter(function ($r) use ($safeDate, $monthStart) {
                    $paymentDate = $safeDate($r->payment_date);
                    return Str::lower((string) ($r->status ?? '')) === 'cobrado'
                        && $paymentDate
                        && $paymentDate->isSameMonth($monthStart);
                })
                ->sum(fn ($r) => (float) ($r->amount ?? 0));

            $nextCollections = $receivablesMonth
                ->sortBy('due_date')
                ->take(5)
                ->map(function ($r) use ($balance, $safeDate) {
                    $due = $safeDate($r->due_date);

                    return [
                        'client'    => trim((string) ($r->client_name ?? 'Sin nombre')) ?: 'Sin nombre',
                        'folio'     => (string) ($r->folio ?? '—'),
                        'amount'    => $balance($r),
                        'due_date'  => $due ? $due->format('Y-m-d') : null,
                        'due_label' => $due ? $due->translatedFormat('d M Y') : 'Sin fecha',
                        'status'    => (string) ($r->status ?? 'pendiente'),
                    ];
                })
                ->values();

            $cashFlowProjection->push([
                'month_key'         => $monthStart->format('Y-m'),
                'month_label'       => Str::ucfirst($monthStart->translatedFormat('M y')),
                'incoming_due'      => $incomingDue,
                'outgoing_due'      => $outgoingDue,
                'collected_real'    => $collectedReal,
                'projected_net'     => $incomingDue - $outgoingDue,
                'receivables_count' => $receivablesMonth->count(),
                'payments_count'    => $paymentsMonth->count(),
                'next_collections'  => $nextCollections,
            ]);
        }

        $upcomingCollections = $receivablesOpen
            ->filter(function ($r) use ($safeDate, $from) {
                $due = $safeDate($r->due_date);
                return $due && $due->gte($from->copy()->startOfDay());
            })
            ->sortBy('due_date')
            ->take(12)
            ->map(function ($r) use ($balance, $safeDate) {
                $due = $safeDate($r->due_date);

                return [
                    'client'      => trim((string) ($r->client_name ?? 'Sin nombre')) ?: 'Sin nombre',
                    'folio'       => (string) ($r->folio ?? '—'),
                    'amount'      => $balance($r),
                    'due_date'    => $due ? $due->format('Y-m-d') : null,
                    'due_label'   => $due ? $due->translatedFormat('d M Y') : 'Sin fecha',
                    'status'      => (string) ($r->status ?? 'pendiente'),
                    'description' => (string) ($r->description ?? ''),
                ];
            })
            ->values();

        return view('accounting.reports.index', compact(
            'payments',
            'receivables',
            'agendaEvents',
            'companies',
            'dateFrom',
            'dateTo',
            'profitSummary',
            'monthlyProfit',
            'cashFlowProjection',
            'upcomingCollections'
        ));
    }
}