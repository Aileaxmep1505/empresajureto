@extends('layouts.app')
@section('title','Reportes')

@section('content')
@include('accounting.partials.ui')

@php
    use Carbon\Carbon;
    use Illuminate\Support\Collection;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;

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
    $toDate = Carbon::parse($dateTo, $tz)->endOfDay();

    $payCategoryLabels = [
        'impuestos'          => 'Impuestos',
        'cuentas_por_cobrar' => 'Cuentas por Cobrar',
        'servicios'          => 'Servicios',
        'nomina'             => 'Nómina',
        'seguros'            => 'Seguros',
        'retenciones'        => 'Retenciones',
        'otros'              => 'Otros',
    ];

    $frequencyLabels = [
        'unico'      => 'Único',
        'mensual'    => 'Mensual',
        'bimestral'  => 'Bimestral',
        'trimestral' => 'Trimestral',
        'semestral'  => 'Semestral',
        'anual'      => 'Anual',
    ];

    $docTypeLabels = [
        'factura'         => 'Facturas',
        'nota_credito'    => 'Notas de Crédito',
        'cargo_adicional' => 'Cargos Adicionales',
        'anticipo'        => 'Anticipos',
    ];

    $paymentStatusLabels = [
        'pendiente' => 'Pendiente',
        'urgente'   => 'Urgente',
        'atrasado'  => 'Atrasado',
        'pagado'    => 'Pagado',
        'cancelado' => 'Cancelado',
        'parcial'   => 'Parcial',
    ];

    $receivableStatusLabels = [
        'pendiente' => 'Pendiente',
        'parcial'   => 'Parcial',
        'cobrado'   => 'Cobrado',
        'vencido'   => 'Vencido',
        'cancelado' => 'Cancelado',
        'atrasado'  => 'Vencido',
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

    $documentsCount = function ($row) {
        $documents = data_get($row, 'documents');

        if ($documents instanceof Collection) return $documents->count();
        if (is_array($documents)) return count($documents);
        if (is_numeric(data_get($row, 'documents_count'))) return (int) data_get($row, 'documents_count');
        if (is_numeric(data_get($row, 'attachments_count'))) return (int) data_get($row, 'attachments_count');
        if (!empty(data_get($row, 'document_path')) || !empty(data_get($row, 'file_path')) || !empty(data_get($row, 'evidence_url'))) return 1;

        return 0;
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
        '0-30 días'  => 0,
        '31-60 días' => 0,
        '61-90 días' => 0,
        '90+ días'   => 0,
    ];

    foreach ($receivablesOpen as $r) {
        $due = $safeDate($r->due_date);
        if (!$due || !$due->lt($today)) continue;

        $days = $due->diffInDays($today);
        $amt = $balance($r);

        if ($days <= 30) $agingBuckets['0-30 días'] += $amt;
        elseif ($days <= 60) $agingBuckets['31-60 días'] += $amt;
        elseif ($days <= 90) $agingBuckets['61-90 días'] += $amt;
        else $agingBuckets['90+ días'] += $amt;
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

    $byDocMax = max((float)($byDocType->max('total') ?: 1), 1);

    $topDebtors = $receivablesOpen
        ->groupBy(fn($r) => trim((string)($r->client_name ?? 'Sin nombre')) ?: 'Sin nombre')
        ->map(fn($rows, $name) => [
            'name' => $name,
            'balance' => $rows->sum(fn($r) => $balance($r)),
            'docs' => $rows->count(),
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
            'mes' => Str::ucfirst($m->translatedFormat('M y')),
            'cobrado' => $cobrado,
            'pagado' => $pagado,
        ]);
    }

    $monthlyRecoveryMax = max((float)($monthlyRecovery->max(fn($x) => max($x['cobrado'], $x['pagado'])) ?: 1), 1);

    $cashFlow = collect();
    for ($i = 0; $i < 6; $i++) {
        $m = $fromDate->copy()->startOfMonth()->addMonths($i);

        $inDue = $receivablesOpen->filter(function ($r) use ($safeDate, $m) {
            $due = $safeDate($r->due_date);
            return $due && $due->isSameMonth($m);
        })->sum(fn($r) => $balance($r));

        $outDue = $paymentsOpen->filter(function ($p) use ($safeDate, $m) {
            $due = $safeDate($p->due_date);
            return $due && $due->isSameMonth($m);
        })->sum(fn($p) => (float)($p->amount ?? 0));

        $collected = $receivables->filter(function ($r) use ($safeDate, $m) {
            $paymentDate = $safeDate($r->payment_date);
            return Str::lower((string)($r->status ?? '')) === 'cobrado'
                && $paymentDate
                && $paymentDate->isSameMonth($m);
        })->sum(fn($r) => (float)($r->amount ?? 0));

        $paid = $payments->filter(function ($p) use ($safeDate, $m) {
            $paymentDate = $safeDate($p->payment_date);
            return Str::lower((string)($p->status ?? '')) === 'pagado'
                && $paymentDate
                && $paymentDate->isSameMonth($m);
        })->sum(fn($p) => (float)($p->amount ?? 0));

        $cashFlow->push([
            'month' => Str::ucfirst($m->translatedFormat('F Y')),
            'incoming_due' => $inDue,
            'outgoing_due' => $outDue,
            'collected' => $collected,
            'paid' => $paid,
            'net' => $inDue - $outDue,
        ]);
    }

    $timelineKeys = collect()
        ->merge($agendaEvents->map(fn($e) => optional($e->start_at)->format('Y-m-d')))
        ->merge($paymentsDueInPeriod->map(fn($p) => optional($safeDate($p->due_date))->format('Y-m-d')))
        ->merge($receivablesDueInPeriod->map(fn($r) => optional($safeDate($r->due_date))->format('Y-m-d')))
        ->filter()
        ->unique()
        ->sort()
        ->values();

    $timeline = $timelineKeys->map(function ($dateKey) use ($agendaEvents, $paymentsDueInPeriod, $receivablesDueInPeriod, $safeDate, $balance) {
        $date = Carbon::parse($dateKey);

        $eventsDay = $agendaEvents->filter(fn($e) => optional($e->start_at)?->format('Y-m-d') === $dateKey)->values();
        $payablesDay = $paymentsDueInPeriod->filter(fn($p) => optional($safeDate($p->due_date))?->format('Y-m-d') === $dateKey)->values();
        $receivablesDay = $receivablesDueInPeriod->filter(fn($r) => optional($safeDate($r->due_date))?->format('Y-m-d') === $dateKey)->values();

        return [
            'date' => $date,
            'events' => $eventsDay,
            'payables' => $payablesDay,
            'receivables' => $receivablesDay,
            'payables_total' => $payablesDay->sum(fn($p) => (float)($p->amount ?? 0)),
            'receivables_total' => $receivablesDay->sum(fn($r) => $balance($r)),
        ];
    })->values();

    $nextAgenda = $agendaEvents
        ->filter(fn($e) => optional($e->start_at) && $e->start_at->gte($today))
        ->sortBy('start_at')
        ->take(6)
        ->values();

    $routeFirst = function (array $names, $params = [], $fallback = '#') {
        foreach ($names as $name) {
            if (Route::has($name)) {
                return route($name, $params);
            }
        }
        return $fallback;
    };

    $sections = [
        'financiero' => 'Financiero',
        'operativo'  => 'Operativo',
        'clientes'   => 'Clientes',
        'flujo'      => 'Flujo',
        'agenda'     => 'Agenda',
    ];
@endphp

<style>
  .rpt-wrap{
    max-width:1540px;
    margin:0 auto;
    padding:8px 0 30px;
  }

  .rpt-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:18px;
    margin-bottom:18px;
  }

  .rpt-title{
    margin:0;
    font-size:2rem;
    line-height:1.05;
    font-weight:900;
    letter-spacing:-.03em;
    color:#0f172a;
  }

  .rpt-sub{
    margin-top:8px;
    color:#64748b;
    font-size:1rem;
  }

  .rpt-sub b{
    color:#0f172a;
  }

  .rpt-card,
  .rpt-filter,
  .rpt-stat,
  .rpt-box,
  .rpt-mini,
  .rpt-line{
    background:#fff;
    border:1px solid #e8edf5;
    border-radius:22px;
    box-shadow:0 10px 28px rgba(15,23,42,.045);
  }

  .rpt-filter{
    padding:16px;
    margin-bottom:16px;
  }

  .rpt-filter-grid{
    display:grid;
    grid-template-columns:1.2fr 180px 180px 230px;
    gap:12px;
    align-items:end;
  }

  .rpt-field{
    display:flex;
    flex-direction:column;
    gap:7px;
  }

  .rpt-label{
    font-size:.78rem;
    font-weight:800;
    color:#64748b;
    letter-spacing:.02em;
    text-transform:uppercase;
  }

  .rpt-input,
  .rpt-select{
    width:100%;
    height:42px;
    border:1px solid #dce4ef;
    background:#fff;
    border-radius:14px;
    padding:0 14px;
    outline:none;
    color:#0f172a;
    font-size:.94rem;
    font-weight:600;
    transition:.2s ease;
    box-shadow:0 1px 2px rgba(15,23,42,.02);
  }

  .rpt-input:focus,
  .rpt-select:focus{
    border-color:#93c5fd;
    box-shadow:0 0 0 4px rgba(59,130,246,.10);
  }

  .rpt-kpis{
    display:grid;
    grid-template-columns:repeat(5,minmax(0,1fr));
    gap:14px;
    margin-bottom:18px;
  }

  .rpt-stat{
    padding:18px;
    transition:.22s ease;
  }

  .rpt-stat:hover{
    transform:translateY(-2px);
    box-shadow:0 16px 34px rgba(15,23,42,.08);
  }

  .rpt-stat .eyebrow{
    font-size:.78rem;
    font-weight:800;
    color:#64748b;
    margin-bottom:10px;
    text-transform:uppercase;
    letter-spacing:.04em;
  }

  .rpt-stat .value{
    font-size:1.8rem;
    line-height:1;
    font-weight:900;
    letter-spacing:-.03em;
    color:#0f172a;
    margin-bottom:6px;
  }

  .rpt-stat .meta{
    color:#64748b;
    font-size:.88rem;
    font-weight:700;
  }

  .rpt-stat.blue{ background:#eff6ff; border-color:#bfdbfe; }
  .rpt-stat.rose{ background:#fff1f2; border-color:#fecdd3; }
  .rpt-stat.amber{ background:#fffbeb; border-color:#fde68a; }
  .rpt-stat.green{ background:#ecfdf5; border-color:#a7f3d0; }
  .rpt-stat.slate{ background:#f8fafc; border-color:#e2e8f0; }

  .rpt-tabs{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-bottom:16px;
  }

  .rpt-tab{
    border:1px solid #dbe3ee;
    background:#fff;
    color:#64748b;
    min-height:40px;
    padding:0 15px;
    border-radius:999px;
    font-size:.9rem;
    font-weight:800;
    cursor:pointer;
    transition:.2s ease;
  }

  .rpt-tab:hover{
    transform:translateY(-1px);
    border-color:#cbd5e1;
    color:#0f172a;
    box-shadow:0 8px 20px rgba(15,23,42,.06);
  }

  .rpt-tab.active{
    background:#0f172a;
    color:#fff;
    border-color:#0f172a;
    box-shadow:0 12px 24px rgba(15,23,42,.16);
  }

  .rpt-pane{
    display:none;
  }

  .rpt-pane.active{
    display:block;
  }

  .rpt-grid-2{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:16px;
  }

  .rpt-grid-3{
    display:grid;
    grid-template-columns:1.25fr .95fr .95fr;
    gap:16px;
  }

  .rpt-box{
    padding:18px;
  }

  .rpt-box-title{
    font-size:1rem;
    font-weight:900;
    color:#0f172a;
    margin-bottom:14px;
  }

  .rpt-box-sub{
    margin-top:-8px;
    margin-bottom:14px;
    font-size:.86rem;
    color:#64748b;
  }

  .rpt-list{
    display:flex;
    flex-direction:column;
    gap:10px;
  }

  .rpt-line{
    padding:12px 13px;
    background:#fbfcfe;
  }

  .rpt-line-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:8px;
    font-size:.88rem;
    font-weight:800;
    color:#334155;
  }

  .rpt-progress{
    height:10px;
    background:#e9eff7;
    border-radius:999px;
    overflow:hidden;
  }

  .rpt-progress > span{
    display:block;
    height:100%;
    border-radius:999px;
  }

  .rpt-progress.blue > span{ background:#3b82f6; }
  .rpt-progress.green > span{ background:#10b981; }
  .rpt-progress.amber > span{ background:#f59e0b; }
  .rpt-progress.rose > span{ background:#f43f5e; }

  .rpt-bars{
    display:grid;
    grid-template-columns:repeat(6,minmax(0,1fr));
    gap:12px;
    align-items:end;
    min-height:240px;
  }

  .rpt-bar-col{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:8px;
  }

  .rpt-bar-stack{
    width:100%;
    height:180px;
    display:flex;
    align-items:flex-end;
    justify-content:center;
    gap:8px;
  }

  .rpt-bar{
    width:24px;
    border-radius:10px 10px 6px 6px;
    min-height:8px;
    transition:.25s ease;
  }

  .rpt-bar.green{ background:#10b981; }
  .rpt-bar.amber{ background:#f59e0b; }
  .rpt-bar.blue{ background:#3b82f6; }
  .rpt-bar.rose{ background:#f43f5e; }

  .rpt-bar-label{
    font-size:.82rem;
    color:#64748b;
    font-weight:800;
    text-align:center;
  }

  .rpt-bar-meta{
    font-size:.72rem;
    color:#94a3b8;
    text-align:center;
  }

  .rpt-key{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:12px;
  }

  .rpt-key span{
    display:inline-flex;
    align-items:center;
    gap:8px;
    font-size:.82rem;
    color:#64748b;
    font-weight:700;
  }

  .rpt-key i{
    width:10px;
    height:10px;
    border-radius:999px;
    display:inline-block;
  }

  .rpt-table{
    width:100%;
    border-collapse:separate;
    border-spacing:0 10px;
  }

  .rpt-table th{
    text-align:left;
    font-size:.78rem;
    color:#94a3b8;
    text-transform:uppercase;
    letter-spacing:.04em;
    padding:0 12px 4px;
  }

  .rpt-table td{
    background:#fbfcfe;
    border-top:1px solid #e8edf5;
    border-bottom:1px solid #e8edf5;
    padding:13px 12px;
    font-size:.9rem;
    color:#0f172a;
  }

  .rpt-table td:first-child{
    border-left:1px solid #e8edf5;
    border-radius:14px 0 0 14px;
  }

  .rpt-table td:last-child{
    border-right:1px solid #e8edf5;
    border-radius:0 14px 14px 0;
  }

  .rpt-chip{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:6px 10px;
    border-radius:999px;
    font-size:.78rem;
    font-weight:900;
    border:1px solid transparent;
    white-space:nowrap;
  }

  .rpt-chip::before{
    content:"";
    width:7px;
    height:7px;
    border-radius:999px;
    background:currentColor;
  }

  .rpt-chip.blue{ background:#eff6ff; color:#2563eb; border-color:#bfdbfe; }
  .rpt-chip.green{ background:#ecfdf5; color:#059669; border-color:#a7f3d0; }
  .rpt-chip.amber{ background:#fffbeb; color:#b45309; border-color:#fde68a; }
  .rpt-chip.rose{ background:#fff1f2; color:#e11d48; border-color:#fecdd3; }
  .rpt-chip.slate{ background:#f8fafc; color:#475569; border-color:#e2e8f0; }

  .rpt-flex{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
  }

  .rpt-muted{ color:#64748b; }
  .rpt-right{ text-align:right; }
  .rpt-strong{ font-weight:900; color:#0f172a; }
  .rpt-danger{ color:#e11d48; font-weight:800; }
  .rpt-ok{ color:#059669; font-weight:800; }

  .rpt-timeline{
    display:flex;
    flex-direction:column;
    gap:12px;
  }

  .rpt-day{
    background:#fbfcfe;
    border:1px solid #e8edf5;
    border-radius:18px;
    padding:14px;
  }

  .rpt-day-head{
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:flex-start;
    margin-bottom:10px;
  }

  .rpt-day-title{
    font-size:.98rem;
    font-weight:900;
    color:#0f172a;
  }

  .rpt-day-sub{
    margin-top:3px;
    color:#64748b;
    font-size:.84rem;
  }

  .rpt-day-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:10px;
  }

  .rpt-mini{
    padding:12px;
    background:#fff;
  }

  .rpt-mini .label{
    font-size:.76rem;
    color:#94a3b8;
    text-transform:uppercase;
    letter-spacing:.04em;
    font-weight:800;
    margin-bottom:8px;
  }

  .rpt-mini .value{
    font-size:1rem;
    font-weight:900;
    color:#0f172a;
    margin-bottom:4px;
  }

  .rpt-mini .sub{
    font-size:.82rem;
    color:#64748b;
  }

  .rpt-empty{
    padding:34px 18px;
    text-align:center;
    color:#64748b;
    background:#fbfcfe;
    border:1px dashed #dbe3ee;
    border-radius:18px;
  }

  .rpt-empty b{
    color:#0f172a;
  }

  @media (max-width:1200px){
    .rpt-kpis{
      grid-template-columns:repeat(2,minmax(0,1fr));
    }

    .rpt-filter-grid,
    .rpt-grid-2,
    .rpt-grid-3{
      grid-template-columns:1fr;
    }
  }

  @media (max-width:860px){
    .rpt-head{
      flex-direction:column;
      align-items:stretch;
    }

    .rpt-kpis{
      grid-template-columns:1fr;
    }

    .rpt-bars{
      grid-template-columns:repeat(3,minmax(0,1fr));
    }

    .rpt-day-grid{
      grid-template-columns:1fr;
    }
  }
</style>

<div class="rpt-wrap">
  <div class="rpt-head">
    <div>
      <h1 class="rpt-title">Reportes</h1>
      <div class="rpt-sub">
        Análisis financiero y operativo ligado al rango de agenda
        · <b>{{ $fromDate->format('d/m/Y') }}</b> al <b>{{ $toDate->format('d/m/Y') }}</b>
      </div>
    </div>
  </div>

  <form method="GET" action="{{ route('accounting.reports.index') }}" class="rpt-filter" id="rptFilterForm">
    <div class="rpt-filter-grid">
      <div class="rpt-field">
        <label class="rpt-label">Compañía</label>
        <select name="company_id" class="rpt-select rpt-auto-submit">
          <option value="">Todas las compañías</option>
          @foreach($companies as $company)
            <option value="{{ $company->id }}" @selected(request('company_id') == $company->id)>
              {{ $company->name }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="rpt-field">
        <label class="rpt-label">Desde</label>
        <input type="date" name="date_from" value="{{ $dateFrom }}" class="rpt-input rpt-auto-submit">
      </div>

      <div class="rpt-field">
        <label class="rpt-label">Hasta</label>
        <input type="date" name="date_to" value="{{ $dateTo }}" class="rpt-input rpt-auto-submit">
      </div>

      <div class="rpt-field">
        <label class="rpt-label">Buscar</label>
        <input type="text" name="search" value="{{ request('search') }}" class="rpt-input" placeholder="Cliente, pago, folio, concepto...">
      </div>
    </div>
  </form>

  <div class="rpt-kpis">
    <div class="rpt-stat blue">
      <div class="eyebrow">Cobros por vencer</div>
      <div class="value">{{ $money0($receivablesDueInPeriod->sum(fn($r) => $balance($r))) }}</div>
      <div class="meta">{{ $receivablesDueInPeriod->count() }} documentos dentro del período</div>
    </div>

    <div class="rpt-stat amber">
      <div class="eyebrow">Pagos por vencer</div>
      <div class="value">{{ $money0($paymentsDueInPeriod->sum(fn($p) => (float)($p->amount ?? 0))) }}</div>
      <div class="meta">{{ $paymentsDueInPeriod->count() }} obligaciones dentro del período</div>
    </div>

    <div class="rpt-stat slate">
      <div class="eyebrow">Eventos agenda</div>
      <div class="value">{{ $agendaEvents->count() }}</div>
      <div class="meta">{{ $agendaDays->count() }} día(s) con actividad agendada</div>
    </div>

    <div class="rpt-stat green">
      <div class="eyebrow">Cobertura agenda cobros</div>
      <div class="value">{{ $receivableCoverageRate }}%</div>
      <div class="meta">{{ $receivableAgendaCoverageCount }} de {{ $receivablesDueInPeriod->count() }} vencimientos con agenda</div>
    </div>

    <div class="rpt-stat rose">
      <div class="eyebrow">Cobertura agenda pagos</div>
      <div class="value">{{ $paymentCoverageRate }}%</div>
      <div class="meta">{{ $paymentAgendaCoverageCount }} de {{ $paymentsDueInPeriod->count() }} vencimientos con agenda</div>
    </div>
  </div>

  <div class="rpt-tabs" id="rptTabs">
    @foreach($sections as $key => $label)
      <button type="button" class="rpt-tab {{ $loop->first ? 'active' : '' }}" data-tab="{{ $key }}">{{ $label }}</button>
    @endforeach
  </div>

  {{-- FINANCIERO --}}
  <div class="rpt-pane active" data-pane="financiero">
    <div class="rpt-grid-2">
      <div class="rpt-box">
        <div class="rpt-box-title">Indicadores financieros</div>
        <div class="rpt-list">
          <div class="rpt-line">
            <div class="rpt-flex">
              <span class="rpt-muted">Cartera total activa</span>
              <span class="rpt-strong">{{ $money($totalCartera) }}</span>
            </div>
          </div>

          <div class="rpt-line">
            <div class="rpt-flex">
              <span class="rpt-muted">Cartera vencida</span>
              <span class="rpt-danger">{{ $money($carteraVencida) }}</span>
            </div>
          </div>

          <div class="rpt-line">
            <div class="rpt-flex">
              <span class="rpt-muted">Total por pagar abierto</span>
              <span class="rpt-strong">{{ $money($payablesOpenTotal) }}</span>
            </div>
          </div>

          <div class="rpt-line">
            <div class="rpt-flex">
              <span class="rpt-muted">Pagos abiertos vencidos</span>
              <span class="rpt-danger">{{ $money($payablesOverdueTotal) }}</span>
            </div>
          </div>

          <div class="rpt-line">
            <div class="rpt-flex">
              <span class="rpt-muted">DSO</span>
              <span class="rpt-strong">{{ $dso }} días</span>
            </div>
          </div>

          <div class="rpt-line">
            <div class="rpt-flex">
              <span class="rpt-muted">Efectividad de cobranza</span>
              <span class="{{ $recoveryRate >= 80 ? 'rpt-ok' : ($recoveryRate >= 60 ? 'rpt-strong' : 'rpt-danger') }}">{{ $recoveryRate }}%</span>
            </div>
          </div>
        </div>
      </div>

      <div class="rpt-box">
        <div class="rpt-box-title">Antigüedad de cartera</div>
        <div class="rpt-list">
          @foreach($agingBuckets as $label => $value)
            @php
              $pct = ($value / $agingMax) * 100;
              $color = match($label) {
                '0-30 días' => 'blue',
                '31-60 días' => 'amber',
                '61-90 días' => 'rose',
                default => 'rose',
              };
            @endphp
            <div class="rpt-line">
              <div class="rpt-line-top">
                <span>{{ $label }}</span>
                <span>{{ $money0($value) }}</span>
              </div>
              <div class="rpt-progress {{ $color }}">
                <span style="width: {{ max($pct, $value > 0 ? 7 : 0) }}%"></span>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    <div class="rpt-box" style="margin-top:16px;">
      <div class="rpt-box-title">Saldo por tipo de documento</div>
      <div class="rpt-list">
        @foreach($byDocType as $row)
          @php
            $pct = ($row['total'] / $byDocMax) * 100;
          @endphp
          <div class="rpt-line">
            <div class="rpt-line-top">
              <span>{{ $docTypeLabels[$row['label']] ?? Str::ucfirst(str_replace('_',' ', $row['label'])) }} · {{ $row['count'] }} doc.</span>
              <span>{{ $money0($row['total']) }}</span>
            </div>
            <div class="rpt-progress blue">
              <span style="width: {{ max($pct, $row['total'] > 0 ? 7 : 0) }}%"></span>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- OPERATIVO --}}
  <div class="rpt-pane" data-pane="operativo">
    <div class="rpt-box">
      <div class="rpt-box-title">Recuperación por mes</div>
      <div class="rpt-box-sub">Cobrado vs pagado con base en fechas reales registradas</div>

      <div class="rpt-key">
        <span><i style="background:#10b981"></i> Cobrado</span>
        <span><i style="background:#f59e0b"></i> Pagado</span>
      </div>

      <div class="rpt-bars">
        @foreach($monthlyRecovery as $row)
          @php
            $hCobrado = ($row['cobrado'] / $monthlyRecoveryMax) * 100;
            $hPagado = ($row['pagado'] / $monthlyRecoveryMax) * 100;
          @endphp
          <div class="rpt-bar-col">
            <div class="rpt-bar-stack">
              <div class="rpt-bar green" style="height: {{ max($hCobrado, $row['cobrado'] > 0 ? 6 : 0) }}%"></div>
              <div class="rpt-bar amber" style="height: {{ max($hPagado, $row['pagado'] > 0 ? 6 : 0) }}%"></div>
            </div>
            <div class="rpt-bar-label">{{ $row['mes'] }}</div>
            <div class="rpt-bar-meta">C {{ $money0($row['cobrado']) }} · P {{ $money0($row['pagado']) }}</div>
          </div>
        @endforeach
      </div>
    </div>

    <div class="rpt-grid-2" style="margin-top:16px;">
      <div class="rpt-box">
        <div class="rpt-box-title">Operación de cobranza</div>
        <table class="rpt-table">
          <thead>
            <tr>
              <th>Concepto</th>
              <th class="rpt-right">Valor</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Documentos abiertos</td>
              <td class="rpt-right">{{ $receivablesOpen->count() }}</td>
            </tr>
            <tr>
              <td>Documentos vencidos</td>
              <td class="rpt-right">{{ $receivablesOpen->filter(fn($r) => optional($safeDate($r->due_date))?->lt($today))->count() }}</td>
            </tr>
            <tr>
              <td>Pagos parciales</td>
              <td class="rpt-right">{{ $receivables->where('status', 'parcial')->count() }}</td>
            </tr>
            <tr>
              <td>Cobrados en el período</td>
              <td class="rpt-right">
                {{ $receivables->filter(fn($r) => optional($safeDate($r->payment_date))?->betweenIncluded($fromDate, $toDate) && Str::lower((string)($r->status ?? '')) === 'cobrado')->count() }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="rpt-box">
        <div class="rpt-box-title">Operación de pagos</div>
        <table class="rpt-table">
          <thead>
            <tr>
              <th>Concepto</th>
              <th class="rpt-right">Valor</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Total por pagar abierto</td>
              <td class="rpt-right">{{ $money0($payablesOpenTotal) }}</td>
            </tr>
            <tr>
              <td>Pagados en el período</td>
              <td class="rpt-right">
                {{ $money0($payments->filter(fn($p) => optional($safeDate($p->payment_date))?->betweenIncluded($fromDate, $toDate) && Str::lower((string)($p->status ?? '')) === 'pagado')->sum('amount')) }}
              </td>
            </tr>
            <tr>
              <td>Urgentes / atrasados</td>
              <td class="rpt-right">
                {{ $paymentsOpen->filter(fn($p) => in_array($paymentEffectiveStatus($p), ['urgente','atrasado'], true))->count() }}
              </td>
            </tr>
            <tr>
              <td>Pagados sin comprobante</td>
              <td class="rpt-right">
                {{ $payments->filter(fn($p) => Str::lower((string)($p->status ?? '')) === 'pagado' && $documentsCount($p) === 0)->count() }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- CLIENTES --}}
  <div class="rpt-pane" data-pane="clientes">
    <div class="rpt-box">
      <div class="rpt-box-title">Top 10 deudores</div>

      @if($topDebtors->isEmpty())
        <div class="rpt-empty"><b>Sin saldos pendientes.</b></div>
      @else
        <div class="rpt-list">
          @foreach($topDebtors as $index => $debtor)
            @php
              $pct = ($debtor['balance'] / $topDebtorsMax) * 100;
            @endphp
            <div class="rpt-line">
              <div class="rpt-line-top">
                <span>#{{ $index + 1 }} · {{ $debtor['name'] }} · {{ $debtor['docs'] }} doc.</span>
                <span>{{ $money0($debtor['balance']) }}</span>
              </div>
              <div class="rpt-progress blue">
                <span style="width: {{ max($pct, $debtor['balance'] > 0 ? 7 : 0) }}%"></span>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  {{-- FLUJO --}}
  <div class="rpt-pane" data-pane="flujo">
    <div class="rpt-box">
      <div class="rpt-box-title">Flujo de caja proyectado</div>
      <div class="rpt-box-sub">Proyección de 6 meses a partir del mes inicial del filtro</div>

      <table class="rpt-table">
        <thead>
          <tr>
            <th>Mes</th>
            <th class="rpt-right">Por cobrar</th>
            <th class="rpt-right">Por pagar</th>
            <th class="rpt-right">Cobrado</th>
            <th class="rpt-right">Pagado</th>
            <th class="rpt-right">Neto</th>
          </tr>
        </thead>
        <tbody>
          @foreach($cashFlow as $row)
            <tr>
              <td>{{ $row['month'] }}</td>
              <td class="rpt-right">{{ $money0($row['incoming_due']) }}</td>
              <td class="rpt-right">{{ $money0($row['outgoing_due']) }}</td>
              <td class="rpt-right">{{ $money0($row['collected']) }}</td>
              <td class="rpt-right">{{ $money0($row['paid']) }}</td>
              <td class="rpt-right {{ $row['net'] >= 0 ? 'rpt-ok' : 'rpt-danger' }}">{{ $money0($row['net']) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- AGENDA --}}
  <div class="rpt-pane" data-pane="agenda">
    <div class="rpt-grid-3">
      <div class="rpt-box">
        <div class="rpt-box-title">Línea de tiempo agenda vs vencimientos</div>

        @if($timeline->isEmpty())
          <div class="rpt-empty"><b>No hay movimientos en el rango seleccionado.</b></div>
        @else
          <div class="rpt-timeline">
            @foreach($timeline as $day)
              <div class="rpt-day">
                <div class="rpt-day-head">
                  <div>
                    <div class="rpt-day-title">{{ Str::ucfirst($day['date']->translatedFormat('l d \d\e F')) }}</div>
                    <div class="rpt-day-sub">
                      {{ $day['events']->count() }} evento(s) agenda ·
                      {{ $day['receivables']->count() }} cobro(s) ·
                      {{ $day['payables']->count() }} pago(s)
                    </div>
                  </div>

                  <div class="rpt-right">
                    @if($day['receivables_total'] > 0)
                      <div class="rpt-ok">{{ $money0($day['receivables_total']) }} por cobrar</div>
                    @endif
                    @if($day['payables_total'] > 0)
                      <div class="rpt-danger">{{ $money0($day['payables_total']) }} por pagar</div>
                    @endif
                  </div>
                </div>

                <div class="rpt-day-grid">
                  <div class="rpt-mini">
                    <div class="label">Agenda</div>
                    <div class="value">{{ $day['events']->count() }}</div>
                    <div class="sub">
                      @if($day['events']->count())
                        {{ Str::limit($day['events']->pluck('title')->implode(' · '), 90) }}
                      @else
                        Sin eventos agendados
                      @endif
                    </div>
                  </div>

                  <div class="rpt-mini">
                    <div class="label">Cobros del día</div>
                    <div class="value">{{ $day['receivables']->count() }}</div>
                    <div class="sub">
                      {{ $day['receivables_total'] > 0 ? $money0($day['receivables_total']) : 'Sin cobros venciendo' }}
                    </div>
                  </div>

                  <div class="rpt-mini">
                    <div class="label">Pagos del día</div>
                    <div class="value">{{ $day['payables']->count() }}</div>
                    <div class="sub">
                      {{ $day['payables_total'] > 0 ? $money0($day['payables_total']) : 'Sin pagos venciendo' }}
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>

      <div class="rpt-box">
        <div class="rpt-box-title">Próximos eventos de agenda</div>

        @if($nextAgenda->isEmpty())
          <div class="rpt-empty">No hay próximos eventos en este rango.</div>
        @else
          <div class="rpt-list">
            @foreach($nextAgenda as $event)
              <div class="rpt-line">
                <div class="rpt-flex">
                  <div>
                    <div class="rpt-strong">{{ $event->title }}</div>
                    <div class="rpt-muted" style="font-size:.84rem;">
                      {{ optional($event->start_at)->translatedFormat('d M Y · H:i') }}
                    </div>
                  </div>
                  <span class="rpt-chip blue">{{ $event->repeat_rule ?: 'none' }}</span>
                </div>
              </div>
            @endforeach
          </div>
        @endif

        <div class="rpt-box-title" style="margin-top:18px;">Cobertura del período</div>
        <div class="rpt-list">
          <div class="rpt-line">
            <div class="rpt-flex">
              <span class="rpt-muted">Cobros con agenda</span>
              <span class="rpt-ok">{{ $receivableAgendaCoverageCount }}/{{ $receivablesDueInPeriod->count() }}</span>
            </div>
          </div>
          <div class="rpt-line">
            <div class="rpt-flex">
              <span class="rpt-muted">Pagos con agenda</span>
              <span class="rpt-ok">{{ $paymentAgendaCoverageCount }}/{{ $paymentsDueInPeriod->count() }}</span>
            </div>
          </div>
          <div class="rpt-line">
            <div class="rpt-flex">
              <span class="rpt-muted">Días con eventos</span>
              <span class="rpt-strong">{{ $agendaDays->count() }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="rpt-box">
        <div class="rpt-box-title">Pendientes sin agenda</div>

        <div class="rpt-box-sub">Vencimientos del rango que no tienen evento en la misma fecha.</div>

        <div class="rpt-list">
          <div class="rpt-line">
            <div class="rpt-flex">
              <span class="rpt-muted">Cobros sin agenda</span>
              <span class="rpt-danger">{{ $receivablesWithoutAgenda->count() }}</span>
            </div>
          </div>

          @foreach($receivablesWithoutAgenda->take(4) as $r)
            <div class="rpt-line">
              <div class="rpt-strong" style="font-size:.9rem;">{{ $r->client_name ?: 'Cliente' }}</div>
              <div class="rpt-muted" style="font-size:.83rem;">
                {{ optional($safeDate($r->due_date))?->format('d/m/Y') ?: 'Sin fecha' }} · {{ $money0($balance($r)) }}
              </div>
            </div>
          @endforeach

          <div class="rpt-line" style="margin-top:6px;">
            <div class="rpt-flex">
              <span class="rpt-muted">Pagos sin agenda</span>
              <span class="rpt-danger">{{ $paymentsWithoutAgenda->count() }}</span>
            </div>
          </div>

          @foreach($paymentsWithoutAgenda->take(4) as $p)
            <div class="rpt-line">
              <div class="rpt-strong" style="font-size:.9rem;">{{ $p->title ?: ($p->concept ?: 'Pago') }}</div>
              <div class="rpt-muted" style="font-size:.83rem;">
                {{ optional($safeDate($p->due_date))?->format('d/m/Y') ?: 'Sin fecha' }} · {{ $money0((float)($p->amount ?? 0)) }}
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('rptFilterForm');
  if (form) {
    const autoFields = form.querySelectorAll('.rpt-auto-submit');
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

  const tabs = document.querySelectorAll('.rpt-tab');
  const panes = document.querySelectorAll('.rpt-pane');

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      const target = tab.dataset.tab;

      tabs.forEach(t => t.classList.remove('active'));
      panes.forEach(p => p.classList.remove('active'));

      tab.classList.add('active');
      const pane = document.querySelector('.rpt-pane[data-pane="' + target + '"]');
      if (pane) pane.classList.add('active');
    });
  });
});
</script>
@endsection