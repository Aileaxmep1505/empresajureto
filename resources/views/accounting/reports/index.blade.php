@extends('layouts.app')
@section('title','Reportes')

@section('content')
@include('accounting.partials.ui')

@php
    use Carbon\Carbon;
    use Illuminate\Support\Collection;
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;

    $tz = 'America/Mexico_City';
    $today = Carbon::now($tz)->startOfDay();

    $money = fn($value) => '$' . number_format((float) $value, 2);
    $money0 = fn($value) => '$' . number_format((float) $value, 0);

    $safeDate = function ($value) {
        try {
            return $value ? Carbon::parse($value) : null;
        } catch (\Throwable $e) {
            return null;
        }
    };

    $fromDate = Carbon::parse($dateFrom, $tz)->startOfDay();
    $toDate   = Carbon::parse($dateTo, $tz)->endOfDay();

    $docTypeLabels = [
        'factura'         => 'Factura',
        'nota_credito'    => 'Nota de crédito',
        'cargo_adicional' => 'Cargo adicional',
        'anticipo'        => 'Anticipo',
    ];

    $balance = fn($r) => max((float)($r->amount ?? 0) - (float)($r->amount_paid ?? 0), 0);

    $paymentEffectiveStatus = function ($p) use ($today, $safeDate) {
        $raw = Str::lower((string)($p->status ?? 'pendiente'));
        $due = $safeDate($p->due_date);

        if (!in_array($raw, ['pagado', 'cancelado'], true) && $due && $due->lt($today)) {
            return 'atrasado';
        }

        return $raw ?: 'pendiente';
    };

    $receivableEffectiveStatus = function ($r) use ($today, $safeDate) {
        $raw = Str::lower((string)($r->status ?? 'pendiente'));
        $due = $safeDate($r->due_date);

        if (!in_array($raw, ['cobrado', 'cancelado'], true) && $due && $due->lt($today)) {
            return 'atrasado';
        }

        return $raw ?: 'pendiente';
    };

    $payments = collect($payments ?? []);
    $receivables = collect($receivables ?? []);
    $agendaEvents = collect($agendaEvents ?? []);

    $paymentsOpen = $payments->filter(fn($p) => !in_array($paymentEffectiveStatus($p), ['pagado', 'cancelado'], true));
    $receivablesOpen = $receivables->filter(fn($r) => !in_array($receivableEffectiveStatus($r), ['cobrado', 'cancelado'], true));

    $paymentsDueInPeriod = $paymentsOpen->filter(function ($p) use ($safeDate, $fromDate, $toDate) {
        $due = $safeDate($p->due_date);
        return $due && $due->betweenIncluded($fromDate, $toDate);
    })->values();

    $receivablesDueInPeriod = $receivablesOpen->filter(function ($r) use ($safeDate, $fromDate, $toDate) {
        $due = $safeDate($r->due_date);
        return $due && $due->betweenIncluded($fromDate, $toDate);
    })->values();

    $agendaDays = $agendaEvents
        ->map(fn($e) => optional($e->start_at)->format('Y-m-d'))
        ->filter()
        ->unique()
        ->values();

    $paymentAgendaCoverageCount = $paymentsDueInPeriod->filter(function ($p) use ($safeDate, $agendaDays) {
        $due = $safeDate($p->due_date);
        return $due && $agendaDays->contains($due->format('Y-m-d'));
    })->count();

    $receivableAgendaCoverageCount = $receivablesDueInPeriod->filter(function ($r) use ($safeDate, $agendaDays) {
        $due = $safeDate($r->due_date);
        return $due && $agendaDays->contains($due->format('Y-m-d'));
    })->count();

    $paymentCoverageRate = $paymentsDueInPeriod->count() > 0 ? round(($paymentAgendaCoverageCount / $paymentsDueInPeriod->count()) * 100) : 0;
    $receivableCoverageRate = $receivablesDueInPeriod->count() > 0 ? round(($receivableAgendaCoverageCount / $receivablesDueInPeriod->count()) * 100) : 0;

    $paymentsWithoutAgenda = $paymentsDueInPeriod->filter(function ($p) use ($safeDate, $agendaDays) {
        $due = $safeDate($p->due_date);
        return !$due || !$agendaDays->contains($due->format('Y-m-d'));
    })->values();

    $receivablesWithoutAgenda = $receivablesDueInPeriod->filter(function ($r) use ($safeDate, $agendaDays) {
        $due = $safeDate($r->due_date);
        return !$due || !$agendaDays->contains($due->format('Y-m-d'));
    })->values();

    $totalCartera = $receivablesOpen->sum(fn($r) => $balance($r));

    $carteraVencida = $receivablesOpen->filter(function ($r) use ($safeDate, $today) {
        $due = $safeDate($r->due_date);
        return $due && $due->lt($today);
    })->sum(fn($r) => $balance($r));

    $payablesOpenTotal = $paymentsOpen->sum(fn($p) => (float)($p->amount ?? 0));

    $payablesOverdueTotal = $paymentsOpen->filter(function ($p) use ($safeDate, $today) {
        $due = $safeDate($p->due_date);
        return $due && $due->lt($today);
    })->sum(fn($p) => (float)($p->amount ?? 0));

    $cobradoUlt90 = $receivables->filter(function ($r) use ($safeDate, $today) {
        $paymentDate = $safeDate($r->payment_date);
        return Str::lower((string)($r->status ?? '')) === 'cobrado'
            && $paymentDate
            && $paymentDate->gte($today->copy()->subDays(90));
    })->sum(fn($r) => (float)($r->amount ?? 0));

    $dso = $cobradoUlt90 > 0 ? round(($totalCartera / $cobradoUlt90) * 90) : 0;

    $targetLast3 = $receivables->filter(function ($r) use ($safeDate, $today) {
        $due = $safeDate($r->due_date);
        return $due && $due->gte($today->copy()->subMonths(3));
    })->sum(fn($r) => (float)($r->amount ?? 0));

    $recoveredLast3 = $receivables->filter(function ($r) use ($safeDate, $today) {
        $paymentDate = $safeDate($r->payment_date);
        return Str::lower((string)($r->status ?? '')) === 'cobrado'
            && $paymentDate
            && $paymentDate->gte($today->copy()->subMonths(3));
    })->sum(fn($r) => (float)($r->amount ?? 0));

    $recoveryRate = $targetLast3 > 0 ? round(($recoveredLast3 / $targetLast3) * 100) : 0;

    $agingBuckets = [
        'Al corriente' => 0,
        '1-30 días'    => 0,
        '31-60 días'   => 0,
        '61-90 días'   => 0,
        '90+ días'     => 0,
    ];

    foreach ($receivablesOpen as $r) {
        $due = $safeDate($r->due_date);
        $amt = $balance($r);

        if (!$due || !$due->lt($today)) {
            $agingBuckets['Al corriente'] += $amt;
            continue;
        }

        $days = $due->diffInDays($today);

        if ($days <= 30) $agingBuckets['1-30 días'] += $amt;
        elseif ($days <= 60) $agingBuckets['31-60 días'] += $amt;
        elseif ($days <= 90) $agingBuckets['61-90 días'] += $amt;
        else $agingBuckets['90+ días'] += $amt;
    }

    $agingCounts = [
        'Al corriente' => $receivablesOpen->filter(function($r) use($safeDate, $today){
            $due = $safeDate($r->due_date);
            return !$due || !$due->lt($today);
        })->count(),
        '1-30 días'  => 0,
        '31-60 días' => 0,
        '61-90 días' => 0,
        '90+ días'   => 0,
    ];

    foreach ($receivablesOpen as $r) {
        $due = $safeDate($r->due_date);
        if (!$due || !$due->lt($today)) continue;

        $days = $due->diffInDays($today);
        if ($days <= 30) $agingCounts['1-30 días']++;
        elseif ($days <= 60) $agingCounts['31-60 días']++;
        elseif ($days <= 90) $agingCounts['61-90 días']++;
        else $agingCounts['90+ días']++;
    }

    $agingMax = max(max($agingBuckets), 1);

    $byDocType = collect(['factura','nota_credito','cargo_adicional','anticipo'])
        ->map(function ($type) use ($receivablesOpen, $balance) {
            $subset = $receivablesOpen->where('document_type', $type);
            return [
                'label' => $type,
                'count' => $subset->count(),
                'total' => $subset->sum(fn($r) => $balance($r)),
            ];
        })
        ->sortByDesc('total')
        ->values();

    $docTypeTotal = max((float) $byDocType->sum('total'), 1);
    $topDoc = $byDocType->first();
    $topDocLabel = $topDoc ? ($docTypeLabels[$topDoc['label']] ?? Str::ucfirst(str_replace('_',' ',$topDoc['label']))) : 'Sin datos';
    $topDocPercent = $topDoc ? round(($topDoc['total'] / $docTypeTotal) * 100) : 0;

    $topDebtors = $receivablesOpen
        ->groupBy(fn($r) => trim((string)($r->client_name ?? 'Sin nombre')) ?: 'Sin nombre')
        ->map(fn($rows, $name) => [
            'name'    => $name,
            'balance' => $rows->sum(fn($r) => $balance($r)),
            'docs'    => $rows->count(),
        ])
        ->sortByDesc('balance')
        ->take(10)
        ->values();

    $topDebtorsMax = max((float)($topDebtors->max('balance') ?: 1), 1);

    $monthlyRecovery = collect();
    for ($i = 5; $i >= 0; $i--) {
        $m = $fromDate->copy()->startOfMonth()->subMonths($i);

        $cobrado = $receivables->filter(function ($r) use ($safeDate, $m) {
            $paymentDate = $safeDate($r->payment_date);
            return Str::lower((string)($r->status ?? '')) === 'cobrado'
                && $paymentDate
                && $paymentDate->isSameMonth($m);
        })->sum(fn($r) => (float)($r->amount ?? 0));

        $pagado = $payments->filter(function ($p) use ($safeDate, $m) {
            $paymentDate = $safeDate($p->payment_date);
            return Str::lower((string)($p->status ?? '')) === 'pagado'
                && $paymentDate
                && $paymentDate->isSameMonth($m);
        })->sum(fn($p) => (float)($p->amount ?? 0));

        $monthlyRecovery->push([
            'mes'     => Str::ucfirst($m->translatedFormat('M y')),
            'cobrado' => $cobrado,
            'pagado'  => $pagado,
        ]);
    }

    $monthlyRecoveryMax = max((float)($monthlyRecovery->max(fn($x) => max($x['cobrado'], $x['pagado'])) ?: 1), 1);

    $cashFlow = collect();
    for ($i = 0; $i < 6; $i++) {
        $m = $fromDate->copy()->startOfMonth()->addMonths($i);

        $incomingDue = $receivablesOpen->filter(function ($r) use ($safeDate, $m) {
            $due = $safeDate($r->due_date);
            return $due && $due->isSameMonth($m);
        })->sum(fn($r) => $balance($r));

        $outgoingDue = $paymentsOpen->filter(function ($p) use ($safeDate, $m) {
            $due = $safeDate($p->due_date);
            return $due && $due->isSameMonth($m);
        })->sum(fn($p) => (float)($p->amount ?? 0));

        $collected = $receivables->filter(function ($r) use ($safeDate, $m) {
            $paymentDate = $safeDate($r->payment_date);
            return Str::lower((string)($r->status ?? '')) === 'cobrado'
                && $paymentDate
                && $paymentDate->isSameMonth($m);
        })->sum(fn($r) => (float)($r->amount ?? 0));

        $cashFlow->push([
            'month'        => Str::ucfirst($m->translatedFormat('M y')),
            'incoming_due' => $incomingDue,
            'outgoing_due' => $outgoingDue,
            'collected'    => $collected,
            'net'          => $incomingDue - $outgoingDue,
        ]);
    }

    $cashFlowMax = max((float)($cashFlow->max(fn($x) => max($x['incoming_due'], $x['outgoing_due'], $x['collected'])) ?: 1), 1);

    $companyId = request('company_id');

    $detectDateColumn = function (string $table, array $candidates) {
        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) return $column;
        }
        return null;
    };

    $detectAmountColumn = function (string $table, array $candidates) {
        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) return $column;
        }
        return null;
    };

    $applyCompanyFilter = function ($query, string $table) use ($companyId) {
        if (!$companyId) return $query;

        foreach (['company_id', 'companies_id', 'empresa_id'] as $companyColumn) {
            if (Schema::hasColumn($table, $companyColumn)) {
                $query->where($companyColumn, $companyId);
                break;
            }
        }

        return $query;
    };

    $sumByDetectedTable = function (
        array $tables,
        array $dateCandidates,
        array $amountCandidates,
        ?callable $extraFilter = null
    ) use ($fromDate, $toDate, $applyCompanyFilter, $detectDateColumn, $detectAmountColumn) {
        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) continue;

            $dateColumn = $detectDateColumn($table, $dateCandidates);
            $amountColumn = $detectAmountColumn($table, $amountCandidates);

            if (!$dateColumn || !$amountColumn) continue;

            try {
                $q = DB::table($table);
                $q = $applyCompanyFilter($q, $table);
                $q->whereBetween($dateColumn, [$fromDate->toDateTimeString(), $toDate->toDateTimeString()]);

                if ($extraFilter) {
                    $extraFilter($q, $table);
                }

                return (float) $q->sum($amountColumn);
            } catch (\Throwable $e) {
            }
        }

        return 0.0;
    };

    $salesInPeriod = $sumByDetectedTable(
        ['ventas', 'venta', 'sales'],
        ['fecha_venta', 'sale_date', 'sold_at', 'created_at', 'fecha'],
        ['total_neto', 'total', 'importe_total', 'monto_total', 'amount']
    );

    $expensesInPeriod = 0.0;
    if (Schema::hasTable('expenses')) {
        try {
            $expenseDateColumn = null;
            foreach (['expense_date', 'performed_at', 'created_at', 'fecha'] as $c) {
                if (Schema::hasColumn('expenses', $c)) {
                    $expenseDateColumn = $c;
                    break;
                }
            }

            $expenseAmountColumn = null;
            foreach (['amount', 'total', 'importe'] as $c) {
                if (Schema::hasColumn('expenses', $c)) {
                    $expenseAmountColumn = $c;
                    break;
                }
            }

            if ($expenseDateColumn && $expenseAmountColumn) {
                $qExpenses = DB::table('expenses');
                $qExpenses = $applyCompanyFilter($qExpenses, 'expenses');
                $qExpenses->whereBetween($expenseDateColumn, [$fromDate->toDateTimeString(), $toDate->toDateTimeString()]);

                if (Schema::hasColumn('expenses', 'entry_kind')) {
                    $qExpenses->where(function ($w) {
                        $w->whereNull('entry_kind')->orWhere('entry_kind', 'gasto');
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

    $publicationPurchasesInPeriod = $sumByDetectedTable(
        ['publicaciones', 'publication_purchases', 'publication_purchase_ai', 'publicationpurchaseais', 'publication_purchase_ais'],
        ['publication_date', 'purchase_date', 'fecha', 'created_at', 'date'],
        ['total', 'amount', 'cost', 'price', 'importe', 'monto']
    );

    $grossProfitPeriod = $salesInPeriod - $publicationPurchasesInPeriod;
    $netProfitPeriod = $salesInPeriod - $expensesInPeriod - $publicationPurchasesInPeriod;
    $profitMargin = $salesInPeriod > 0 ? round(($netProfitPeriod / $salesInPeriod) * 100, 1) : 0;

    $profitCards = [
        [
            'theme' => 'blue',
            'title' => 'Vendido en el período',
            'value' => $money0($salesInPeriod),
            'meta'  => 'Ventas registradas del rango filtrado',
            'icon'  => '<svg viewBox="0 0 24 24" fill="none"><path d="M12 2v20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M17 6.5c0-1.93-2.24-3.5-5-3.5s-5 1.57-5 3.5S9.24 10 12 10s5 1.57 5 3.5S14.76 17 12 17s-5-1.57-5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        ],
        [
            'theme' => 'rose',
            'title' => 'Gastos del período',
            'value' => $money0($expensesInPeriod),
            'meta'  => 'Solo gastos, excluyendo movimientos de caja',
            'icon'  => '<svg viewBox="0 0 24 24" fill="none"><path d="M3 6h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M8 6V4.8A1.8 1.8 0 0 1 9.8 3h4.4A1.8 1.8 0 0 1 16 4.8V6" stroke="currentColor" stroke-width="1.8"/><path d="M19 6l-1 13a2 2 0 0 1-2 1H8a2 2 0 0 1-2-1L5 6" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
        ],
        [
            'theme' => 'amber',
            'title' => 'Compras / publicaciones',
            'value' => $money0($publicationPurchasesInPeriod),
            'meta'  => 'Costo detectado en publicaciones/compras',
            'icon'  => '<svg viewBox="0 0 24 24" fill="none"><path d="M3 7h18l-2 10H5L3 7z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2zM17 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" stroke="currentColor" stroke-width="1.8"/><path d="M7 10h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        ],
        [
            'theme' => $netProfitPeriod >= 0 ? 'green' : 'rose',
            'title' => 'Utilidad neta',
            'value' => $money0($netProfitPeriod),
            'meta'  => ($salesInPeriod > 0 ? $profitMargin . '% de margen' : 'Sin ventas en el período'),
            'icon'  => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 16l5-5 4 4 7-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 7h6v6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        ],
    ];

    $profitCompare = collect([
        ['label' => 'Ventas', 'value' => $salesInPeriod, 'theme' => 'blue'],
        ['label' => 'Gastos', 'value' => $expensesInPeriod, 'theme' => 'rose'],
        ['label' => 'Compras/Publicaciones', 'value' => $publicationPurchasesInPeriod, 'theme' => 'amber'],
        ['label' => 'Utilidad', 'value' => max($netProfitPeriod, 0), 'theme' => 'green'],
    ]);

    $profitCompareMax = max((float)($profitCompare->max('value') ?: 1), 1);

    $monthlyProfit = collect();
    for ($i = 5; $i >= 0; $i--) {
        $mStart = $fromDate->copy()->startOfMonth()->subMonths($i);
        $mEnd   = $mStart->copy()->endOfMonth();

        $sumForMonth = function (
            array $tables,
            array $dateCandidates,
            array $amountCandidates,
            ?callable $extraFilter = null
        ) use ($mStart, $mEnd, $applyCompanyFilter, $detectDateColumn, $detectAmountColumn) {
            foreach ($tables as $table) {
                if (!Schema::hasTable($table)) continue;

                $dateColumn = $detectDateColumn($table, $dateCandidates);
                $amountColumn = $detectAmountColumn($table, $amountCandidates);

                if (!$dateColumn || !$amountColumn) continue;

                try {
                    $q = DB::table($table);
                    $q = $applyCompanyFilter($q, $table);
                    $q->whereBetween($dateColumn, [$mStart->toDateTimeString(), $mEnd->toDateTimeString()]);

                    if ($extraFilter) {
                        $extraFilter($q, $table);
                    }

                    return (float) $q->sum($amountColumn);
                } catch (\Throwable $e) {
                }
            }

            return 0.0;
        };

        $mSales = $sumForMonth(
            ['ventas', 'venta', 'sales'],
            ['fecha_venta', 'sale_date', 'sold_at', 'created_at', 'fecha'],
            ['total_neto', 'total', 'importe_total', 'monto_total', 'amount']
        );

        $mExpenses = 0.0;
        if (Schema::hasTable('expenses')) {
            try {
                $expenseDateColumn = null;
                foreach (['expense_date', 'performed_at', 'created_at', 'fecha'] as $c) {
                    if (Schema::hasColumn('expenses', $c)) {
                        $expenseDateColumn = $c;
                        break;
                    }
                }

                $expenseAmountColumn = null;
                foreach (['amount', 'total', 'importe'] as $c) {
                    if (Schema::hasColumn('expenses', $c)) {
                        $expenseAmountColumn = $c;
                        break;
                    }
                }

                if ($expenseDateColumn && $expenseAmountColumn) {
                    $qExpenses = DB::table('expenses');
                    $qExpenses = $applyCompanyFilter($qExpenses, 'expenses');
                    $qExpenses->whereBetween($expenseDateColumn, [$mStart->toDateTimeString(), $mEnd->toDateTimeString()]);

                    if (Schema::hasColumn('expenses', 'entry_kind')) {
                        $qExpenses->where(function ($w) {
                            $w->whereNull('entry_kind')->orWhere('entry_kind', 'gasto');
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

        $mPublications = $sumForMonth(
            ['publicaciones', 'publication_purchases', 'publication_purchase_ai', 'publicationpurchaseais', 'publication_purchase_ais'],
            ['publication_date', 'purchase_date', 'fecha', 'created_at', 'date'],
            ['total', 'amount', 'cost', 'price', 'importe', 'monto']
        );

        $mUtility = $mSales - $mExpenses - $mPublications;

        $monthlyProfit->push([
            'mes' => Str::ucfirst($mStart->translatedFormat('M y')),
            'ventas' => $mSales,
            'gastos' => $mExpenses,
            'compras' => $mPublications,
            'utilidad' => $mUtility,
        ]);
    }

    $monthlyProfitMax = max((float)($monthlyProfit->max(fn($x) => max($x['ventas'], $x['gastos'], $x['compras'], max($x['utilidad'], 0))) ?: 1), 1);

    $financialCards = [
        [
            'theme' => 'blue',
            'title' => 'Cartera Total',
            'value' => $money0($totalCartera),
            'meta'  => $receivablesOpen->count().' documento(s) activos',
            'icon'  => '<svg viewBox="0 0 24 24" fill="none"><path d="M8 3h5l5 5v13H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h2z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M13 3v5h5" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
        ],
        [
            'theme' => 'rose',
            'title' => 'Cartera Vencida',
            'value' => $money0($carteraVencida),
            'meta'  => ($totalCartera > 0 ? round(($carteraVencida / $totalCartera) * 100) : 0).'% del total',
            'icon'  => '<svg viewBox="0 0 24 24" fill="none"><path d="M12 9v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M12 17h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
        ],
        [
            'theme' => 'green',
            'title' => 'DSO',
            'value' => $dso.' días',
            'meta'  => $dso <= 30 ? 'Óptimo' : ($dso <= 60 ? 'Controlado' : 'Seguimiento'),
            'icon'  => '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        ],
        [
            'theme' => 'amber',
            'title' => 'Efectividad Cobranza',
            'value' => $recoveryRate.'%',
            'meta'  => 'Últimos 3 meses',
            'icon'  => '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        ],
    ];

    $sections = [
        'financiero'   => 'Financiero',
        'operativo'    => 'Operativo',
        'rentabilidad' => 'Rentabilidad',
        'clientes'     => 'Clientes',
        'flujo'        => 'Flujo de Caja',
    ];

    $sectionIcons = [
        'financiero'   => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 16l5-5 4 4 7-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 7h6v6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'operativo'    => '<svg viewBox="0 0 24 24" fill="none"><path d="M5 19V10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M12 19V5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M19 19v-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'rentabilidad' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 15l4-4 4 3 7-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M18 6h3v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'clientes'     => '<svg viewBox="0 0 24 24" fill="none"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="9.5" cy="7" r="3.5" stroke="currentColor" stroke-width="1.8"/><path d="M20 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'flujo'        => '<svg viewBox="0 0 24 24" fill="none"><path d="M12 2v20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M17 6.5c0-1.93-2.24-3.5-5-3.5s-5 1.57-5 3.5S9.24 10 12 10s5 1.57 5 3.5S14.76 17 12 17s-5-1.57-5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    ];

    $docTypePalette = ['#4F7CF3', '#7AA2FF', '#A9C2FF', '#D7E4FF'];
    $docGradient = '';
    $docProgress = 0;

    if ($byDocType->sum('total') > 0) {
        $parts = [];
        foreach ($byDocType->values() as $i => $row) {
            $pct = round(($row['total'] / $docTypeTotal) * 100, 2);
            $start = $docProgress;
            $docProgress += $pct;
            $color = $docTypePalette[$i] ?? '#E2E8F0';
            $parts[] = "{$color} {$start}% {$docProgress}%";
        }
        $docGradient = 'conic-gradient(' . implode(', ', $parts) . ')';
    } else {
        $docGradient = 'conic-gradient(#E5E7EB 0% 100%)';
    }

    $cashFlowRows = collect($cashFlowProjection ?? []);
    $upcomingRows = collect($upcomingCollections ?? []);
    $cashFlowChartMax = max((float) ($cashFlowRows->max(fn($x) => max(
      (float)($x['incoming_due'] ?? 0),
      (float)($x['outgoing_due'] ?? 0),
      (float)($x['collected_real'] ?? 0),
      max((float)($x['projected_net'] ?? 0), 0)
    )) ?: 1), 1);
@endphp

<style>
  .rpv2{
    --bg:#f5f7fb;
    --panel:#ffffff;
    --text:#0f172a;
    --muted:#7a8597;
    --muted-2:#94a3b8;
    --line:#e9eef5;
    --line-strong:#dbe4ef;
    --shadow:0 10px 30px rgba(15,23,42,.045);
    --shadow-hover:0 18px 40px rgba(15,23,42,.07);
    --radius-xl:28px;
    --radius-lg:24px;
    --radius-md:18px;
    --blue:#4f7cf3;
    --blue-soft:#eef4ff;
    --rose:#ee6b7b;
    --rose-soft:#fff2f4;
    --green:#17a673;
    --green-soft:#eefbf5;
    --amber:#e49a25;
    --amber-soft:#fff8eb;
  }

  .rpv2 *{ box-sizing:border-box; }

  .rpv2-wrap{
    width:100%;
    max-width:1580px;
    margin:0 auto;
    padding:4px 0 40px;
  }

  .rpv2-head{ margin-bottom:14px; }

  .rpv2-title{
    margin:0;
    color:var(--text);
    font-size:2.2rem;
    line-height:1;
    font-weight:700;
    letter-spacing:-.03em;
  }

  .rpv2-sub{
    margin-top:10px;
    color:#66758c;
    font-size:1rem;
    font-weight:500;
  }

  .rpv2-filter{ margin:12px 0 18px; }

  .rpv2-filter-grid{
    display:grid;
    grid-template-columns:1.1fr 150px 150px 220px;
    gap:10px;
    align-items:end;
  }

  .rpv2-field{
    display:flex;
    flex-direction:column;
    gap:6px;
  }

  .rpv2-label{
    padding-left:2px;
    font-size:.73rem;
    letter-spacing:.03em;
    color:#94a3b8;
    text-transform:uppercase;
    font-weight:500;
  }

  .rpv2-input,
  .rpv2-select{
    width:100%;
    height:42px;
    border-radius:14px;
    border:1px solid var(--line-strong);
    background:rgba(255,255,255,.88);
    padding:0 14px;
    outline:none;
    color:#334155;
    font-size:.92rem;
    font-weight:500;
    transition:.2s ease;
    box-shadow:0 1px 1px rgba(15,23,42,.02);
  }

  .rpv2-input::placeholder{
    color:#a0aec0;
    font-weight:400;
  }

  .rpv2-input:focus,
  .rpv2-select:focus{
    border-color:#bfd2ff;
    box-shadow:0 0 0 4px rgba(79,124,243,.08);
    background:#fff;
  }

  .rpv2-tabs{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-bottom:20px;
  }

  .rpv2-tab{
    min-height:46px;
    padding:0 18px;
    border-radius:999px;
    border:1px solid var(--line-strong);
    background:rgba(255,255,255,.72);
    color:#5f6d82;
    font-size:.98rem;
    font-weight:500;
    display:inline-flex;
    align-items:center;
    gap:10px;
    cursor:pointer;
    transition:.22s ease;
    backdrop-filter:blur(10px);
  }

  .rpv2-tab svg{
    width:18px;
    height:18px;
    display:block;
  }

  .rpv2-tab:hover{
    transform:translateY(-1px);
    box-shadow:var(--shadow);
    border-color:#cfd8e5;
    color:#334155;
  }

  .rpv2-tab.active{
    background:#0f172a;
    color:#fff;
    border-color:#0f172a;
    box-shadow:0 12px 26px rgba(15,23,42,.15);
  }

  .rpv2-pane{ display:none; }
  .rpv2-pane.active{ display:block; }

  .rpv2-kpis{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:14px;
    margin-bottom:22px;
  }

  .rpv2-kpi{
    position:relative;
    overflow:hidden;
    border-radius:22px;
    border:1px solid var(--line);
    background:#fff;
    padding:18px 18px 17px;
    box-shadow:var(--shadow);
    transition:.24s ease;
  }

  .rpv2-kpi:hover{
    transform:translateY(-2px);
    box-shadow:var(--shadow-hover);
  }

  .rpv2-kpi.blue{ background:var(--blue-soft); border-color:#d9e6ff; color:#2d62ea; }
  .rpv2-kpi.rose{ background:var(--rose-soft); border-color:#ffd7dd; color:#df4d62; }
  .rpv2-kpi.green{ background:var(--green-soft); border-color:#d1f2e1; color:#109a69; }
  .rpv2-kpi.amber{ background:var(--amber-soft); border-color:#fde7b5; color:#d88411; }

  .rpv2-kpi-icon{
    width:28px;
    height:28px;
    margin-bottom:12px;
    display:flex;
    align-items:center;
    justify-content:center;
  }

  .rpv2-kpi-icon svg{
    width:24px;
    height:24px;
    display:block;
  }

  .rpv2-kpi-value{
    font-size:1.95rem;
    line-height:1;
    font-weight:600;
    letter-spacing:-.03em;
    margin-bottom:7px;
  }

  .rpv2-kpi-title{
    font-size:.96rem;
    font-weight:500;
    margin-bottom:3px;
  }

  .rpv2-kpi-meta{
    font-size:.84rem;
    font-weight:400;
    color:rgba(15,23,42,.52);
  }

  .rpv2-grid-2{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:18px;
  }

  .rpv2-card{
    background:var(--panel);
    border:1px solid var(--line);
    border-radius:24px;
    box-shadow:var(--shadow);
    padding:20px;
  }

  .rpv2-card-title{
    color:var(--text);
    font-size:1.06rem;
    font-weight:600;
    margin-bottom:16px;
    letter-spacing:-.01em;
  }

  .rpv2-card-sub{
    color:#8b97a8;
    font-size:.88rem;
    font-weight:400;
    margin-top:-8px;
    margin-bottom:14px;
  }

  .rpv2-stack{
    display:flex;
    flex-direction:column;
    gap:12px;
  }

  .rpv2-aging{
    border-radius:18px;
    padding:15px 15px 13px;
    border:1px solid;
    transition:.2s ease;
  }

  .rpv2-aging:hover{ transform:translateY(-1px); }

  .rpv2-aging.current{ background:#effbf5; border-color:#cdeedc; }
  .rpv2-aging.yellow{ background:#fff8e9; border-color:#f8df9c; }
  .rpv2-aging.orange{ background:#fff2e8; border-color:#f5cfab; }
  .rpv2-aging.red{ background:#fff2f0; border-color:#f3c3bb; }
  .rpv2-aging.darkred{ background:#fff1f4; border-color:#f1c3cf; }

  .rpv2-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:11px;
  }

  .rpv2-row-left{
    font-size:.95rem;
    font-weight:500;
    color:#334155;
  }

  .rpv2-row-right{
    display:flex;
    align-items:baseline;
    gap:10px;
    flex-wrap:wrap;
    justify-content:flex-end;
  }

  .rpv2-amount{
    font-size:.97rem;
    font-weight:600;
    color:#1e293b;
  }

  .rpv2-docs{
    font-size:.9rem;
    font-weight:400;
    color:#7b8796;
  }

  .rpv2-progress{
    position:relative;
    height:8px;
    border-radius:999px;
    background:rgba(15,23,42,.09);
    overflow:visible;
  }

  .rpv2-progress > span{
    display:block;
    height:100%;
    border-radius:999px;
    transition:width .6s ease;
  }

  .rpv2-progress.green > span{ background:#1dbf73; }
  .rpv2-progress.yellow > span{ background:#f2b320; }
  .rpv2-progress.orange > span{ background:#ee9345; }
  .rpv2-progress.red > span{ background:#eb6d4f; }
  .rpv2-progress.darkred > span{ background:#df5d82; }
  .rpv2-progress.blue > span{ background:#4f7cf3; }

  .rpv2-donut-wrap{
    display:grid;
    grid-template-columns:220px 1fr;
    gap:18px;
    align-items:center;
  }

  .rpv2-donut-box{
    display:flex;
    align-items:center;
    justify-content:center;
    min-height:250px;
  }

  .rpv2-donut{
    width:180px;
    height:180px;
    border-radius:999px;
    position:relative;
    background:{!! $docGradient !!};
    box-shadow:inset 0 0 0 1px rgba(15,23,42,.05);
  }

  .rpv2-donut::before{
    content:"";
    position:absolute;
    inset:34px;
    background:#fff;
    border-radius:999px;
    box-shadow:0 0 0 1px rgba(15,23,42,.05);
  }

  .rpv2-donut-center{
    position:absolute;
    inset:0;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-direction:column;
    z-index:2;
    text-align:center;
    pointer-events:none;
  }

  .rpv2-donut-center .v1{
    font-size:1.15rem;
    color:#0f172a;
    font-weight:600;
  }

  .rpv2-donut-center .v2{
    font-size:.82rem;
    color:#7a8597;
    font-weight:400;
    margin-top:4px;
  }

  .rpv2-doc-list{
    display:flex;
    flex-direction:column;
    gap:12px;
  }

  .rpv2-doc-item{
    padding:12px 0;
    border-bottom:1px solid #edf2f7;
  }

  .rpv2-doc-item:last-child{ border-bottom:none; }

  .rpv2-doc-top{
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:center;
    margin-bottom:9px;
  }

  .rpv2-doc-name{
    display:flex;
    align-items:center;
    gap:10px;
    min-width:0;
    color:#334155;
    font-size:.94rem;
    font-weight:500;
  }

  .rpv2-doc-dot{
    width:10px;
    height:10px;
    border-radius:999px;
    flex:0 0 10px;
  }

  .rpv2-doc-meta{
    color:#7b8796;
    font-size:.86rem;
    font-weight:400;
    white-space:nowrap;
  }

  .rpv2-chart-card{ padding:22px 24px; }

  .rpv2-chart-area{
    position:relative;
    height:305px;
    margin-top:12px;
  }

  .rpv2-chart-grid{
    position:absolute;
    inset:0 0 30px 0;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
  }

  .rpv2-chart-grid span{
    border-top:1px dashed #e8edf4;
    flex:1;
  }

  .rpv2-chart-grid span:last-child{
    border-bottom:1px solid #8792a3;
  }

  .rpv2-chart-bars{
    position:absolute;
    inset:0 12px 34px 12px;
    display:grid;
    grid-template-columns:repeat(6,minmax(0,1fr));
    gap:16px;
    align-items:end;
  }

  .rpv2-chart-col{
    position:relative;
    height:100%;
    display:flex;
    flex-direction:column;
    justify-content:flex-end;
    align-items:center;
    gap:10px;
  }

  .rpv2-chart-pair{
    width:100%;
    height:100%;
    display:flex;
    justify-content:center;
    align-items:flex-end;
    gap:8px;
  }

  .rpv2-bar{
    width:22px;
    min-height:2px;
    border-radius:7px 7px 0 0;
    position:relative;
    transition:transform .18s ease, opacity .18s ease;
    animation:rpv2Grow .65s ease both;
    cursor:pointer;
  }

  .rpv2-bar:hover{
    transform:translateY(-2px);
    opacity:.94;
  }

  .rpv2-bar.green{ background:#1dbf73; }
  .rpv2-bar.amber{ background:#f2a01f; }
  .rpv2-bar.blue{ background:#4f7cf3; }
  .rpv2-bar.rose{ background:#ee6b7b; }

  .rpv2-chart-label{
    font-size:.9rem;
    color:#6f7c8e;
    font-weight:400;
  }

  .rpv2-legend{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:18px;
    margin-top:14px;
    flex-wrap:wrap;
  }

  .rpv2-legend span{
    display:inline-flex;
    align-items:center;
    gap:8px;
    color:#637184;
    font-size:.94rem;
    font-weight:400;
  }

  .rpv2-legend i{
    width:15px;
    height:11px;
    border-radius:3px;
    display:inline-block;
  }

  .rpv2-summary-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:18px;
    margin-top:18px;
  }

  .rpv2-soft{
    background:#f8fafc;
    border:1px solid #eef3f8;
    border-radius:18px;
    padding:14px 16px;
  }

  .rpv2-soft-item{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:12px 0;
    border-bottom:1px solid #ebf0f6;
  }

  .rpv2-soft-item:last-child{ border-bottom:none; }

  .rpv2-soft-label{
    font-size:.94rem;
    color:#6b7788;
    font-weight:400;
  }

  .rpv2-soft-value{
    font-size:.98rem;
    color:#0f172a;
    font-weight:500;
  }

  .rpv2-soft-value.blue{ color:#3666ea; }
  .rpv2-soft-value.rose{ color:#de5770; }
  .rpv2-soft-value.green{ color:#129667; }
  .rpv2-soft-value.amber{ color:#d88a15; }

  .rpv2-top-list{
    display:flex;
    flex-direction:column;
    gap:16px;
  }

  .rpv2-top-item{ padding-top:2px; }

  .rpv2-top-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    margin-bottom:10px;
  }

  .rpv2-top-left{
    display:flex;
    align-items:center;
    gap:12px;
    min-width:0;
  }

  .rpv2-rank{
    width:32px;
    height:32px;
    border-radius:999px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#fdecef;
    color:#d05571;
    font-size:.9rem;
    font-weight:500;
  }

  .rpv2-client{
    min-width:0;
    color:#334155;
    font-size:.98rem;
    font-weight:400;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }

  .rpv2-top-amount{
    color:#0f172a;
    font-size:1rem;
    font-weight:600;
    white-space:nowrap;
  }

  .rpv2-top-track{
    position:relative;
    height:8px;
    border-radius:999px;
    background:#dbe8ff;
  }

  .rpv2-top-track span{
    display:block;
    height:100%;
    background:#4f7cf3;
    border-radius:999px;
  }

  .rpv2-note{
    margin-top:18px;
    background:#fff8e8;
    border:1px solid #f0d58b;
    border-radius:18px;
    padding:18px 20px;
  }

  .rpv2-note-title{
    color:#9a5a08;
    font-size:1rem;
    font-weight:600;
    margin-bottom:10px;
  }

  .rpv2-note ul{
    margin:0;
    padding-left:18px;
    color:#b26b10;
  }

  .rpv2-note li{
    margin:6px 0;
    font-weight:400;
  }

  .rpv2-empty{
    padding:26px 18px;
    text-align:center;
    color:#7b8796;
    background:#f8fafc;
    border:1px dashed #d9e2ec;
    border-radius:18px;
    font-weight:400;
  }

  .rpv2-tip{ position:relative; }

  .rpv2-tip::after{
    content:attr(data-tip);
    position:absolute;
    left:50%;
    bottom:calc(100% + 12px);
    transform:translateX(-50%) translateY(4px);
    background:rgba(15,23,42,.96);
    color:#fff;
    padding:8px 10px;
    border-radius:10px;
    font-size:.78rem;
    line-height:1.35;
    white-space:nowrap;
    pointer-events:none;
    opacity:0;
    visibility:hidden;
    transition:.18s ease;
    box-shadow:0 10px 24px rgba(15,23,42,.18);
    z-index:30;
    font-weight:400;
  }

  .rpv2-tip::before{
    content:"";
    position:absolute;
    left:50%;
    bottom:calc(100% + 6px);
    transform:translateX(-50%);
    border:6px solid transparent;
    border-top-color:rgba(15,23,42,.96);
    opacity:0;
    visibility:hidden;
    transition:.18s ease;
    z-index:30;
  }

  .rpv2-tip:hover::after,
  .rpv2-tip:hover::before{
    opacity:1;
    visibility:visible;
    transform:translateX(-50%) translateY(0);
  }

  .rpv2-tip.side::after{
    left:auto;
    right:0;
    transform:translateY(4px);
  }

  .rpv2-tip.side::before{
    left:auto;
    right:18px;
    transform:none;
  }

  .rpv2-tip.side:hover::after{ transform:translateY(0); }

  .rpv2-compare-list{
    display:flex;
    flex-direction:column;
    gap:14px;
  }

  .rpv2-compare-item{
    padding:10px 0;
    border-bottom:1px solid #edf2f7;
  }

  .rpv2-compare-item:last-child{
    border-bottom:none;
  }

  .rpv2-compare-head{
    display:flex;
    justify-content:space-between;
    gap:12px;
    margin-bottom:8px;
    align-items:center;
  }

  .rpv2-compare-name{
    font-size:.95rem;
    color:#334155;
    font-weight:500;
  }

  .rpv2-compare-value{
    font-size:.96rem;
    color:#0f172a;
    font-weight:600;
  }

  .rpv2-compare-track{
    height:10px;
    border-radius:999px;
    background:#edf2f7;
    overflow:hidden;
  }

  .rpv2-compare-track span{
    display:block;
    height:100%;
    border-radius:999px;
  }

  .rpv2-compare-track.blue span{ background:#4f7cf3; }
  .rpv2-compare-track.rose span{ background:#ee6b7b; }
  .rpv2-compare-track.amber span{ background:#f2a01f; }
  .rpv2-compare-track.green span{ background:#1dbf73; }

  .rpv2-soft-item > div{
    min-width:0;
  }

  .rpv2-soft-item > div span:first-child{
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }

  @keyframes rpv2Grow{
    from{ transform:scaleY(.2); transform-origin:bottom; opacity:.35; }
    to{ transform:scaleY(1); transform-origin:bottom; opacity:1; }
  }

  @media (max-width: 1220px){
    .rpv2-kpis,
    .rpv2-grid-2,
    .rpv2-summary-grid,
    .rpv2-filter-grid{
      grid-template-columns:1fr 1fr;
    }

    .rpv2-donut-wrap{
      grid-template-columns:1fr;
    }
  }

  @media (max-width: 860px){
    .rpv2-title{ font-size:1.9rem; }

    .rpv2-kpis,
    .rpv2-grid-2,
    .rpv2-summary-grid,
    .rpv2-filter-grid{
      grid-template-columns:1fr;
    }

    .rpv2-chart-bars{ gap:10px; }
    .rpv2-bar{ width:16px; }
  }
</style>

<div class="rpv2">
  <div class="rpv2-wrap">
    <div class="rpv2-head">
      <h1 class="rpv2-title">Reportes</h1>
      <div class="rpv2-sub">
        Análisis financiero y operativo · {{ Str::ucfirst($fromDate->translatedFormat('F Y')) }}
      </div>
    </div>

    <form method="GET" action="{{ route('accounting.reports.index') }}" class="rpv2-filter" id="rpv2FilterForm">
      <div class="rpv2-filter-grid">
        <div class="rpv2-field">
          <label class="rpv2-label">Compañía</label>
          <select name="company_id" class="rpv2-select rpv2-auto-submit">
            <option value="">Todas las compañías</option>
            @foreach($companies as $company)
              <option value="{{ $company->id }}" @selected(request('company_id') == $company->id)>
                {{ $company->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="rpv2-field">
          <label class="rpv2-label">Desde</label>
          <input type="date" name="date_from" value="{{ $dateFrom }}" class="rpv2-input rpv2-auto-submit">
        </div>

        <div class="rpv2-field">
          <label class="rpv2-label">Hasta</label>
          <input type="date" name="date_to" value="{{ $dateTo }}" class="rpv2-input rpv2-auto-submit">
        </div>

        <div class="rpv2-field">
          <label class="rpv2-label">Buscar</label>
          <input type="text" name="search" value="{{ request('search') }}" class="rpv2-input" placeholder="Cliente, folio, concepto...">
        </div>
      </div>
    </form>

    <div class="rpv2-tabs" id="rpv2Tabs">
      @foreach($sections as $key => $label)
        <button type="button" class="rpv2-tab {{ $loop->first ? 'active' : '' }}" data-tab="{{ $key }}">
          {!! $sectionIcons[$key] ?? '' !!}
          <span>{{ $label }}</span>
        </button>
      @endforeach
    </div>

    {{-- FINANCIERO --}}
    <div class="rpv2-pane active" data-pane="financiero">
      <div class="rpv2-kpis">
        @foreach($financialCards as $card)
          <div class="rpv2-kpi {{ $card['theme'] }}">
            <div class="rpv2-kpi-icon">{!! $card['icon'] !!}</div>
            <div class="rpv2-kpi-value">{{ $card['value'] }}</div>
            <div class="rpv2-kpi-title">{{ $card['title'] }}</div>
            <div class="rpv2-kpi-meta">{{ $card['meta'] }}</div>
          </div>
        @endforeach
      </div>

      <div class="rpv2-grid-2">
        <div class="rpv2-card">
          <div class="rpv2-card-title">Antigüedad de Cartera (Aging)</div>

          <div class="rpv2-stack">
            @foreach($agingBuckets as $label => $value)
              @php
                $pct = ($value / $agingMax) * 100;

                $theme = match($label){
                  'Al corriente' => 'current',
                  '1-30 días' => 'yellow',
                  '31-60 días' => 'orange',
                  '61-90 días' => 'red',
                  default => 'darkred',
                };

                $progressTheme = match($label){
                  'Al corriente' => 'green',
                  '1-30 días' => 'yellow',
                  '31-60 días' => 'orange',
                  '61-90 días' => 'red',
                  default => 'darkred',
                };
              @endphp

              <div class="rpv2-aging {{ $theme }}">
                <div class="rpv2-row">
                  <div class="rpv2-row-left">{{ $label }}</div>
                  <div class="rpv2-row-right">
                    <span class="rpv2-amount">{{ $money0($value) }}</span>
                    <span class="rpv2-docs">{{ $agingCounts[$label] ?? 0 }} doc.</span>
                  </div>
                </div>

                <div
                  class="rpv2-progress {{ $progressTheme }} rpv2-tip side"
                  data-tip="{{ $label }} · {{ $money0($value) }} · {{ $agingCounts[$label] ?? 0 }} documento(s)"
                >
                  <span style="width: {{ max($pct, $value > 0 ? 6 : 0) }}%"></span>
                </div>
              </div>
            @endforeach
          </div>
        </div>

        <div class="rpv2-card">
          <div class="rpv2-card-title">Por Tipo de Documento</div>

          <div class="rpv2-donut-wrap">
            <div class="rpv2-donut-box">
              <div
                class="rpv2-donut rpv2-tip"
                data-tip="{{ $topDocLabel }} · {{ $topDocPercent }}% del total"
              >
                <div class="rpv2-donut-center">
                  <div class="v1">{{ $topDocPercent }}%</div>
                  <div class="v2">{{ $topDocLabel }}</div>
                </div>
              </div>
            </div>

            <div class="rpv2-doc-list">
              @foreach($byDocType as $i => $row)
                @php
                  $pct = $docTypeTotal > 0 ? round(($row['total'] / $docTypeTotal) * 100) : 0;
                  $color = $docTypePalette[$i] ?? '#E2E8F0';
                  $label = $docTypeLabels[$row['label']] ?? Str::ucfirst(str_replace('_',' ', $row['label']));
                @endphp

                <div class="rpv2-doc-item">
                  <div class="rpv2-doc-top">
                    <div class="rpv2-doc-name">
                      <span class="rpv2-doc-dot" style="background:{{ $color }};"></span>
                      <span>{{ $label }}</span>
                    </div>
                    <div class="rpv2-doc-meta">{{ $money0($row['total']) }} · {{ $pct }}%</div>
                  </div>

                  <div
                    class="rpv2-progress blue rpv2-tip side"
                    data-tip="{{ $label }} · {{ $money0($row['total']) }} · {{ $row['count'] }} documento(s) · {{ $pct }}%"
                  >
                    <span style="width: {{ max($pct, $row['total'] > 0 ? 6 : 0) }}%; background: {{ $color }};"></span>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- OPERATIVO --}}
    <div class="rpv2-pane" data-pane="operativo">
      <div class="rpv2-card rpv2-chart-card">
        <div class="rpv2-card-title">Recuperación por mes</div>

        <div class="rpv2-chart-area">
          <div class="rpv2-chart-grid">
            <span></span><span></span><span></span><span></span><span></span>
          </div>

          <div class="rpv2-chart-bars">
            @foreach($monthlyRecovery as $row)
              @php
                $hCobrado = ($row['cobrado'] / $monthlyRecoveryMax) * 100;
                $hPagado  = ($row['pagado'] / $monthlyRecoveryMax) * 100;
              @endphp

              <div class="rpv2-chart-col">
                <div class="rpv2-chart-pair">
                  <div
                    class="rpv2-bar green rpv2-tip"
                    style="height: {{ max($hCobrado, $row['cobrado'] > 0 ? 3 : 0) }}%;"
                    data-tip="{{ $row['mes'] }} · Cobrado {{ $money0($row['cobrado']) }}"
                  ></div>

                  <div
                    class="rpv2-bar amber rpv2-tip"
                    style="height: {{ max($hPagado, $row['pagado'] > 0 ? 3 : 0) }}%;"
                    data-tip="{{ $row['mes'] }} · Pagado {{ $money0($row['pagado']) }}"
                  ></div>
                </div>
                <div class="rpv2-chart-label">{{ $row['mes'] }}</div>
              </div>
            @endforeach
          </div>
        </div>

        <div class="rpv2-legend">
          <span><i style="background:#1dbf73"></i> Cobrado</span>
          <span><i style="background:#f2a01f"></i> Pagado</span>
        </div>
      </div>

      <div class="rpv2-summary-grid">
        <div class="rpv2-card">
          <div class="rpv2-card-title">Resumen de Cartera</div>

          <div class="rpv2-soft">
            <div class="rpv2-soft-item">
              <span class="rpv2-soft-label">Total facturas activas</span>
              <span class="rpv2-soft-value blue">{{ $receivablesOpen->count() }}</span>
            </div>
            <div class="rpv2-soft-item">
              <span class="rpv2-soft-label">Documentos vencidos</span>
              <span class="rpv2-soft-value rose">
                {{ $receivablesOpen->filter(fn($r) => optional($safeDate($r->due_date))?->lt($today))->count() }}
              </span>
            </div>
            <div class="rpv2-soft-item">
              <span class="rpv2-soft-label">Cobrado en el período</span>
              <span class="rpv2-soft-value green">
                {{ $money0($receivables->filter(fn($r) => optional($safeDate($r->payment_date))?->betweenIncluded($fromDate, $toDate) && Str::lower((string)($r->status ?? '')) === 'cobrado')->sum('amount')) }}
              </span>
            </div>
            <div class="rpv2-soft-item">
              <span class="rpv2-soft-label">Cobertura de agenda</span>
              <span class="rpv2-soft-value amber">{{ $receivableCoverageRate }}%</span>
            </div>
          </div>
        </div>

        <div class="rpv2-card">
          <div class="rpv2-card-title">Pagos - Resumen</div>

          <div class="rpv2-soft">
            <div class="rpv2-soft-item">
              <span class="rpv2-soft-label">Total por pagar</span>
              <span class="rpv2-soft-value rose">{{ $money0($payablesOpenTotal) }}</span>
            </div>
            <div class="rpv2-soft-item">
              <span class="rpv2-soft-label">Pagos vencidos</span>
              <span class="rpv2-soft-value rose">{{ $money0($payablesOverdueTotal) }}</span>
            </div>
            <div class="rpv2-soft-item">
              <span class="rpv2-soft-label">Pagado en el período</span>
              <span class="rpv2-soft-value amber">
                {{ $money0($payments->filter(fn($p) => optional($safeDate($p->payment_date))?->betweenIncluded($fromDate, $toDate) && Str::lower((string)($p->status ?? '')) === 'pagado')->sum('amount')) }}
              </span>
            </div>
            <div class="rpv2-soft-item">
              <span class="rpv2-soft-label">Sin agenda</span>
              <span class="rpv2-soft-value">{{ $paymentsWithoutAgenda->count() }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- RENTABILIDAD --}}
    <div class="rpv2-pane" data-pane="rentabilidad">
      <div class="rpv2-kpis">
        @foreach($profitCards as $card)
          <div class="rpv2-kpi {{ $card['theme'] }}">
            <div class="rpv2-kpi-icon">{!! $card['icon'] !!}</div>
            <div class="rpv2-kpi-value">{{ $card['value'] }}</div>
            <div class="rpv2-kpi-title">{{ $card['title'] }}</div>
            <div class="rpv2-kpi-meta">{{ $card['meta'] }}</div>
          </div>
        @endforeach
      </div>

      <div class="rpv2-grid-2">
        <div class="rpv2-card">
          <div class="rpv2-card-title">Comparación del período</div>
          <div class="rpv2-card-sub">Ventas vs gastos vs compras/publicaciones vs utilidad</div>

          <div class="rpv2-compare-list">
            @foreach($profitCompare as $row)
              @php
                $pct = $profitCompareMax > 0 ? ($row['value'] / $profitCompareMax) * 100 : 0;
              @endphp

              <div class="rpv2-compare-item">
                <div class="rpv2-compare-head">
                  <div class="rpv2-compare-name">{{ $row['label'] }}</div>
                  <div class="rpv2-compare-value">{{ $money0($row['value']) }}</div>
                </div>

                <div
                  class="rpv2-compare-track {{ $row['theme'] }} rpv2-tip side"
                  data-tip="{{ $row['label'] }} · {{ $money0($row['value']) }}"
                >
                  <span style="width: {{ max($pct, $row['value'] > 0 ? 6 : 0) }}%"></span>
                </div>
              </div>
            @endforeach
          </div>
        </div>

        <div class="rpv2-card">
          <div class="rpv2-card-title">Resumen de utilidad</div>

          <div class="rpv2-soft">
            <div class="rpv2-soft-item">
              <span class="rpv2-soft-label">Ventas del período</span>
              <span class="rpv2-soft-value blue">{{ $money0($salesInPeriod) }}</span>
            </div>
            <div class="rpv2-soft-item">
              <span class="rpv2-soft-label">Menos gastos</span>
              <span class="rpv2-soft-value rose">{{ $money0($expensesInPeriod) }}</span>
            </div>
            <div class="rpv2-soft-item">
              <span class="rpv2-soft-label">Menos compras/publicaciones</span>
              <span class="rpv2-soft-value amber">{{ $money0($publicationPurchasesInPeriod) }}</span>
            </div>
            <div class="rpv2-soft-item">
              <span class="rpv2-soft-label">Utilidad bruta</span>
              <span class="rpv2-soft-value blue">{{ $money0($grossProfitPeriod) }}</span>
            </div>
            <div class="rpv2-soft-item">
              <span class="rpv2-soft-label">Utilidad neta</span>
              <span class="rpv2-soft-value {{ $netProfitPeriod >= 0 ? 'green' : 'rose' }}">
                {{ $money0($netProfitPeriod) }}
              </span>
            </div>
            <div class="rpv2-soft-item">
              <span class="rpv2-soft-label">Margen neto</span>
              <span class="rpv2-soft-value {{ $netProfitPeriod >= 0 ? 'green' : 'rose' }}">
                {{ $profitMargin }}%
              </span>
            </div>
          </div>
        </div>
      </div>

      <div class="rpv2-card rpv2-chart-card" style="margin-top:18px;">
        <div class="rpv2-card-title">Evolución mensual de rentabilidad</div>
        <div class="rpv2-card-sub">Ventas, gastos, compras/publicaciones y utilidad por mes</div>

        <div class="rpv2-chart-area">
          <div class="rpv2-chart-grid">
            <span></span><span></span><span></span><span></span><span></span>
          </div>

          <div class="rpv2-chart-bars">
            @foreach($monthlyProfit as $row)
              @php
                $hVentas   = ($row['ventas'] / $monthlyProfitMax) * 100;
                $hGastos   = ($row['gastos'] / $monthlyProfitMax) * 100;
                $hCompras  = ($row['compras'] / $monthlyProfitMax) * 100;
                $hUtilidad = (max($row['utilidad'], 0) / $monthlyProfitMax) * 100;
              @endphp

              <div class="rpv2-chart-col">
                <div class="rpv2-chart-pair">
                  <div
                    class="rpv2-bar blue rpv2-tip"
                    style="height: {{ max($hVentas, $row['ventas'] > 0 ? 3 : 0) }}%;"
                    data-tip="{{ $row['mes'] }} · Ventas {{ $money0($row['ventas']) }}"
                  ></div>

                  <div
                    class="rpv2-bar rose rpv2-tip"
                    style="height: {{ max($hGastos, $row['gastos'] > 0 ? 3 : 0) }}%;"
                    data-tip="{{ $row['mes'] }} · Gastos {{ $money0($row['gastos']) }}"
                  ></div>

                  <div
                    class="rpv2-bar amber rpv2-tip"
                    style="height: {{ max($hCompras, $row['compras'] > 0 ? 3 : 0) }}%;"
                    data-tip="{{ $row['mes'] }} · Compras/Publicaciones {{ $money0($row['compras']) }}"
                  ></div>

                  <div
                    class="rpv2-bar green rpv2-tip"
                    style="height: {{ max($hUtilidad, $row['utilidad'] > 0 ? 3 : 0) }}%;"
                    data-tip="{{ $row['mes'] }} · Utilidad {{ $money0($row['utilidad']) }}"
                  ></div>
                </div>
                <div class="rpv2-chart-label">{{ $row['mes'] }}</div>
              </div>
            @endforeach
          </div>
        </div>

        <div class="rpv2-legend">
          <span><i style="background:#4f7cf3"></i> Ventas</span>
          <span><i style="background:#ee6b7b"></i> Gastos</span>
          <span><i style="background:#f2a01f"></i> Compras/Publicaciones</span>
          <span><i style="background:#1dbf73"></i> Utilidad</span>
        </div>
      </div>

      <div class="rpv2-note">
        <div class="rpv2-note-title">Fórmula usada</div>
        <ul>
          <li>Utilidad bruta = ventas - compras/publicaciones.</li>
          <li>Utilidad neta = ventas - gastos - compras/publicaciones.</li>
          <li>Si alguna tabla no existe en tu sistema, ese bloque se toma como 0 para no romper el reporte.</li>
        </ul>
      </div>
    </div>

    {{-- CLIENTES --}}
    <div class="rpv2-pane" data-pane="clientes">
      <div class="rpv2-card">
        <div class="rpv2-card-title">Top 10 Deudores</div>

        @if($topDebtors->isEmpty())
          <div class="rpv2-empty">No hay clientes con saldo pendiente.</div>
        @else
          <div class="rpv2-top-list">
            @foreach($topDebtors as $index => $debtor)
              @php
                $pct = ($debtor['balance'] / $topDebtorsMax) * 100;
              @endphp

              <div class="rpv2-top-item">
                <div class="rpv2-top-head">
                  <div class="rpv2-top-left">
                    <span class="rpv2-rank">{{ $index + 1 }}</span>
                    <div class="rpv2-client">{{ $debtor['name'] }} <span style="color:#7b8796;">({{ $debtor['docs'] }} doc.)</span></div>
                  </div>
                  <div class="rpv2-top-amount">{{ $money0($debtor['balance']) }}</div>
                </div>

                <div
                  class="rpv2-top-track rpv2-tip side"
                  data-tip="{{ $debtor['name'] }} · {{ $money0($debtor['balance']) }} · {{ $debtor['docs'] }} documento(s)"
                >
                  <span style="width: {{ max($pct, $debtor['balance'] > 0 ? 7 : 0) }}%"></span>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>

    {{-- FLUJO --}}
    <div class="rpv2-pane" data-pane="flujo">
      <div class="rpv2-card rpv2-chart-card">
        <div class="rpv2-card-title">Flujo de Caja Proyectado (6 meses)</div>
        <div class="rpv2-card-sub">Próximos cobros, pagos comprometidos, cobrado real y neto proyectado por mes</div>

        <div class="rpv2-chart-area">
          <div class="rpv2-chart-grid">
            <span></span><span></span><span></span><span></span><span></span>
          </div>

          <div class="rpv2-chart-bars">
            @foreach($cashFlowRows as $row)
              @php
                $hIncoming = (($row['incoming_due'] ?? 0) / $cashFlowChartMax) * 100;
                $hCollected = (($row['collected_real'] ?? 0) / $cashFlowChartMax) * 100;
                $hOutgoing = (($row['outgoing_due'] ?? 0) / $cashFlowChartMax) * 100;
                $hNet = ((max(($row['projected_net'] ?? 0), 0)) / $cashFlowChartMax) * 100;
              @endphp

              <div class="rpv2-chart-col">
                <div class="rpv2-chart-pair">
                  <div
                    class="rpv2-bar blue rpv2-tip"
                    style="height: {{ max($hIncoming, ($row['incoming_due'] ?? 0) > 0 ? 3 : 0) }}%;"
                    data-tip="{{ $row['month_label'] }} · Próximo por cobrar {{ $money0($row['incoming_due'] ?? 0) }}"
                  ></div>

                  <div
                    class="rpv2-bar green rpv2-tip"
                    style="height: {{ max($hCollected, ($row['collected_real'] ?? 0) > 0 ? 3 : 0) }}%;"
                    data-tip="{{ $row['month_label'] }} · Cobrado real {{ $money0($row['collected_real'] ?? 0) }}"
                  ></div>

                  <div
                    class="rpv2-bar amber rpv2-tip"
                    style="height: {{ max($hOutgoing, ($row['outgoing_due'] ?? 0) > 0 ? 3 : 0) }}%;"
                    data-tip="{{ $row['month_label'] }} · Próximo por pagar {{ $money0($row['outgoing_due'] ?? 0) }}"
                  ></div>

                  <div
                    class="rpv2-bar rose rpv2-tip"
                    style="height: {{ max($hNet, ($row['projected_net'] ?? 0) > 0 ? 3 : 0) }}%;"
                    data-tip="{{ $row['month_label'] }} · Neto proyectado {{ $money0($row['projected_net'] ?? 0) }}"
                  ></div>
                </div>

                <div class="rpv2-chart-label">{{ $row['month_label'] }}</div>
              </div>
            @endforeach
          </div>
        </div>

        <div class="rpv2-legend">
          <span><i style="background:#4f7cf3"></i> Próximo por cobrar</span>
          <span><i style="background:#1dbf73"></i> Cobrado real</span>
          <span><i style="background:#f2a01f"></i> Próximo por pagar</span>
          <span><i style="background:#ee6b7b"></i> Neto proyectado</span>
        </div>
      </div>

      <div class="rpv2-summary-grid">
        <div class="rpv2-card">
          <div class="rpv2-card-title">Próximos cobros</div>
          <div class="rpv2-card-sub">Lo siguiente que debes cobrar según vencimiento</div>

          @if($upcomingRows->isEmpty())
            <div class="rpv2-empty">No hay cobros próximos registrados.</div>
          @else
            <div class="rpv2-top-list">
              @foreach($upcomingRows as $index => $item)
                @php
                  $maxUpcoming = max((float)($upcomingRows->max('amount') ?: 1), 1);
                  $pct = (($item['amount'] ?? 0) / $maxUpcoming) * 100;
                @endphp

                <div class="rpv2-top-item">
                  <div class="rpv2-top-head">
                    <div class="rpv2-top-left">
                      <span class="rpv2-rank">{{ $index + 1 }}</span>
                      <div class="rpv2-client">
                        {{ $item['client'] }}
                        <span style="color:#7b8796;"> · {{ $item['folio'] }}</span>
                      </div>
                    </div>
                    <div class="rpv2-top-amount">{{ $money0($item['amount'] ?? 0) }}</div>
                  </div>

                  <div style="display:flex; justify-content:space-between; gap:12px; margin-bottom:8px; color:#7b8796; font-size:.88rem;">
                    <span>Vence: {{ $item['due_label'] ?? 'Sin fecha' }}</span>
                    <span>{{ $item['status'] ?? 'pendiente' }}</span>
                  </div>

                  <div
                    class="rpv2-top-track rpv2-tip side"
                    data-tip="{{ $item['client'] }} · {{ $item['due_label'] ?? 'Sin fecha' }} · {{ $money0($item['amount'] ?? 0) }}"
                  >
                    <span style="width: {{ max($pct, ($item['amount'] ?? 0) > 0 ? 7 : 0) }}%"></span>
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>

        <div class="rpv2-card">
          <div class="rpv2-card-title">Resumen proyectado</div>

          @php
            $nextMonth = $cashFlowRows->first();
          @endphp

          <div class="rpv2-soft">
            <div class="rpv2-soft-item">
              <span class="rpv2-soft-label">Próximo mes</span>
              <span class="rpv2-soft-value blue">{{ $nextMonth['month_label'] ?? '—' }}</span>
            </div>
            <div class="rpv2-soft-item">
              <span class="rpv2-soft-label">Por cobrar siguiente mes</span>
              <span class="rpv2-soft-value blue">{{ $money0($nextMonth['incoming_due'] ?? 0) }}</span>
            </div>
            <div class="rpv2-soft-item">
              <span class="rpv2-soft-label">Por pagar siguiente mes</span>
              <span class="rpv2-soft-value amber">{{ $money0($nextMonth['outgoing_due'] ?? 0) }}</span>
            </div>
            <div class="rpv2-soft-item">
              <span class="rpv2-soft-label">Cobrado real siguiente mes</span>
              <span class="rpv2-soft-value green">{{ $money0($nextMonth['collected_real'] ?? 0) }}</span>
            </div>
            <div class="rpv2-soft-item">
              <span class="rpv2-soft-label">Neto proyectado siguiente mes</span>
              <span class="rpv2-soft-value {{ ($nextMonth['projected_net'] ?? 0) >= 0 ? 'green' : 'rose' }}">
                {{ $money0($nextMonth['projected_net'] ?? 0) }}
              </span>
            </div>
            <div class="rpv2-soft-item">
              <span class="rpv2-soft-label">Cobros próximos listados</span>
              <span class="rpv2-soft-value">{{ $upcomingRows->count() }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="rpv2-card" style="margin-top:18px;">
        <div class="rpv2-card-title">Próximos cobros por mes</div>
        <div class="rpv2-card-sub">Detalle rápido de lo que viene por cobrar en cada mes proyectado</div>

        @if($cashFlowRows->isEmpty())
          <div class="rpv2-empty">No hay flujo proyectado disponible.</div>
        @else
          <div class="rpv2-grid-2">
            @foreach($cashFlowRows as $row)
              <div class="rpv2-soft">
                <div class="rpv2-soft-item">
                  <span class="rpv2-soft-label">{{ $row['month_label'] }}</span>
                  <span class="rpv2-soft-value blue">{{ $money0($row['incoming_due'] ?? 0) }}</span>
                </div>

                @if(collect($row['next_collections'] ?? [])->isEmpty())
                  <div class="rpv2-empty" style="padding:16px 12px; margin-top:10px;">Sin cobros proyectados.</div>
                @else
                  @foreach($row['next_collections'] as $item)
                    <div class="rpv2-soft-item">
                      <div style="display:flex; flex-direction:column; gap:2px;">
                        <span class="rpv2-soft-label" style="color:#334155;">{{ $item['client'] }}</span>
                        <span style="font-size:.82rem; color:#94a3b8;">{{ $item['folio'] }} · {{ $item['due_label'] }}</span>
                      </div>
                      <span class="rpv2-soft-value blue">{{ $money0($item['amount'] ?? 0) }}</span>
                    </div>
                  @endforeach
                @endif
              </div>
            @endforeach
          </div>
        @endif
      </div>

      <div class="rpv2-note">
        <div class="rpv2-note-title">Cómo leer este flujo</div>
        <ul>
          <li>Próximo por cobrar: saldo pendiente con vencimiento en ese mes.</li>
          <li>Cobrado real: lo efectivamente cobrado según la fecha de pago real.</li>
          <li>Próximo por pagar: obligaciones abiertas con vencimiento en ese mes.</li>
          <li>Neto proyectado: próximo por cobrar menos próximo por pagar.</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('rpv2FilterForm');

  if (form) {
    const autoFields = form.querySelectorAll('.rpv2-auto-submit');
    autoFields.forEach(function (field) {
      field.addEventListener('change', function () {
        form.submit();
      });
    });

    const search = form.querySelector('input[name="search"]');
    let debounce = null;

    if (search) {
      search.addEventListener('input', function () {
        clearTimeout(debounce);
        debounce = setTimeout(function () {
          form.submit();
        }, 420);
      });

      search.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          clearTimeout(debounce);
          form.submit();
        }
      });
    }
  }

  const tabs = document.querySelectorAll('.rpv2-tab');
  const panes = document.querySelectorAll('.rpv2-pane');

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      const target = tab.dataset.tab;

      tabs.forEach(t => t.classList.remove('active'));
      panes.forEach(p => p.classList.remove('active'));

      tab.classList.add('active');

      const pane = document.querySelector('.rpv2-pane[data-pane="' + target + '"]');
      if (pane) pane.classList.add('active');
    });
  });
});
</script>
@endsection