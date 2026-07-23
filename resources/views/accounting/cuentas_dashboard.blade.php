@extends('layouts.app')
@section('title','Dashboard · Cuentas')

@section('content')
@include('accounting.partials.ui')

@php
  use Carbon\Carbon;
  use Carbon\CarbonInterface;

  $q = fn(array $extra = []) => array_filter(
      array_merge(['company_id' => $companyId], $extra),
      fn($v) => $v !== null && $v !== ''
  );

  $fmt0 = fn($n) => '$' . number_format((float) $n, 0);
  $fmt2 = fn($n) => '$' . number_format((float) $n, 2);

  $balanceNetoValue = (float)($balanceNeto ?? 0);

  $atrasadosCount = (int) ($overdueReceivables->count() ?? 0);
  $urgentesCount  = (int) ($urgentPayments->count() ?? 0);
  $pendientesCount = (int) (
      ($upcomingPayments->count() ?? 0) +
      ($upcomingReceivables->count() ?? 0)
  );

  $pagadosCount = (int) (
      (($pagadoMes ?? 0) > 0 ? 1 : 0) +
      (($cobradoMes ?? 0) > 0 ? 1 : 0)
  );

  $atrasadosMonto = (float) $overdueReceivables->sum(fn($r) => max((float)$r->amount - (float)$r->amount_paid, 0));
  $urgentesMonto  = (float) $urgentPayments->sum(fn($p) => max((float)$p->amount - (float)$p->amount_paid, 0));
  $pendientesMonto = (float) (
      $upcomingPayments->sum(fn($p) => max((float)$p->amount - (float)$p->amount_paid, 0)) +
      $upcomingReceivables->sum(fn($r) => max((float)$r->amount - (float)$r->amount_paid, 0))
  );
  $pagadosMonto = (float)(($pagadoMes ?? 0) + ($cobradoMes ?? 0));

  $agingMap = collect($aging ?? []);
  $agingBuckets = [
      'Al corriente' => (float) ($agingMap['Al corriente'] ?? $agingMap['Corriente'] ?? 0),
      '1-30 días'    => (float) ($agingMap['1-30 días'] ?? $agingMap['1-30'] ?? 0),
      '31-60 días'   => (float) ($agingMap['31-60 días'] ?? $agingMap['31-60'] ?? 0),
      '61-90 días'   => (float) ($agingMap['61-90 días'] ?? $agingMap['61-90'] ?? 0),
      '+90 días'     => (float) ($agingMap['+90 días'] ?? $agingMap['90+'] ?? $agingMap['Más de 90 días'] ?? 0),
  ];
  $agingTotal = max(array_sum($agingBuckets), 1);

  $toCarbon = function ($value) {
      if (blank($value)) {
          return null;
      }

      if ($value instanceof CarbonInterface) {
          return $value->copy();
      }

      if ($value instanceof \DateTimeInterface) {
          return Carbon::instance($value);
      }

      try {
          return Carbon::parse($value);
      } catch (\Throwable $e) {
          return null;
      }
  };

  $formatDueDate = function ($value) use ($toCarbon) {
      $date = $toCarbon($value);
      return $date ? $date->translatedFormat('d M Y') : 'Sin fecha';
  };

  $today = now()->startOfDay();

  $cardMeta = function ($type, $item) use ($today, $toCarbon) {
      $due = $toCarbon($item->due_date ?? null);
      $days = $due ? $today->diffInDays($due->copy()->startOfDay(), false) : null;
      $isOverdue = $days !== null && $days < 0;
      $daysLate = abs((int)$days);

      $futureText = $days === null
          ? 'Sin fecha de vencimiento'
          : ($days === 0 ? 'Vence hoy' : ($days === 1 ? '1 día' : "{$days} días"));

      if ($type === 'payable') {
          if ($isOverdue) {
              return ['status' => 'Atrasado', 'cycle' => 'Pago', 'due_text' => "{$daysLate} días atrasado", 'tone' => 'danger'];
          }
          if ($days !== null && $days <= 3) {
              return ['status' => 'Urgente', 'cycle' => 'Pago', 'due_text' => $futureText, 'tone' => 'danger'];
          }
          return ['status' => 'Pendiente', 'cycle' => 'Pago', 'due_text' => $futureText, 'tone' => 'warning'];
      }

      if ($isOverdue) {
          return ['status' => 'Atrasado', 'cycle' => 'Factura', 'due_text' => "{$daysLate} días atrasado", 'tone' => 'danger'];
      }

      $paid = (float)($item->amount_paid ?? 0);
      $amount = (float)($item->amount ?? 0);
      $partial = $paid > 0 && $paid < $amount;

      return [
          'status' => $partial ? 'Parcial' : 'Pendiente',
          'cycle' => 'Factura',
          'due_text' => $futureText,
          'tone' => $partial ? 'info' : 'warning'
      ];
  };

  $reminders = collect()
      ->merge($overdueReceivables->take(3)->map(function($r) use ($cardMeta) {
          return [
              'title' => $r->client_name ?: 'Cobro',
              'amount' => max((float)$r->amount - (float)$r->amount_paid, 0),
              'meta' => $cardMeta('receivable', $r),
              'url' => route('accounting.receivables.show', $r),
          ];
      }))
      ->merge($urgentPayments->take(3)->map(function($p) use ($cardMeta) {
          return [
              'title' => $p->title ?: 'Pago',
              'amount' => max((float)$p->amount - (float)$p->amount_paid, 0),
              'meta' => $cardMeta('payable', $p),
              'url' => route('accounting.payables.show', $p),
          ];
      }))
      ->take(6);
@endphp

<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  .acc-dash {
    font-family: 'Quicksand', sans-serif;
    font-weight: 500;
    color: #333333;
    --bg: #f9fafb;
    --card: #ffffff;
    --ink: #111111;
    --text-main: #333333;
    --muted: #888888;
    --line: #ebebeb;
    --blue: #007aff;
    --blue-soft: #e6f0ff;
    --success: #15803d;
    --success-soft: #e6ffe6;
    --danger: #ff4a4a;
    --danger-soft: #ffebeb;
    --warning: #b45309;
    --warning-soft: #fffbeb;
    
    max-width: 1380px;
    margin: 0 auto;
    padding: 24px 0 40px;
  }

  /* HEADERS & FILTERS */
  .acc-head2 {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
  }

  .acc-title {
    font-size: 2.2rem;
    line-height: 1.1;
    font-weight: 700;
    color: var(--ink);
    letter-spacing: -0.02em;
    margin: 0;
  }

  .acc-sub {
    margin-top: 6px;
    color: var(--muted);
    font-size: 1rem;
    font-weight: 500;
  }

  .acc-filtersWrap {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 24px;
  }

  .acc-filters2 {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
  }

  .acc-select {
    min-width: 240px;
    height: 44px;
    border: 1px solid var(--line);
    background: var(--card);
    color: var(--ink);
    border-radius: 8px;
    padding: 0 16px;
    font-family: 'Quicksand', sans-serif;
    font-weight: 600;
    font-size: 0.95rem;
    outline: none;
    transition: all 0.2s ease;
    cursor: pointer;
  }

  .acc-select:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .acc-filter-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 44px;
    background: transparent;
    color: var(--muted);
    border: none;
    border-radius: 8px;
    padding: 0 20px;
    font-family: 'Quicksand', sans-serif;
    font-weight: 600;
    font-size: 0.95rem;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .acc-filter-btn:hover {
    background: var(--card);
    color: var(--ink);
  }
  
  .acc-filter-btn:active {
    transform: scale(0.98);
  }

  /* REUSABLE CARD BASE */
  .acc-kpi, .acc-mini, .acc-panel, .acc-item, .acc-reminders, .acc-empty {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    transition: all 0.25s ease;
    text-decoration: none;
    display: block;
  }

  .acc-kpi:hover, .acc-mini:hover, .acc-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.04);
  }

  /* GRIDS */
  .acc-grid-4 {
    display: grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 24px;
    margin-bottom: 24px;
  }

  /* LARGE KPIs */
  .acc-kpi {
    padding: 24px;
  }

  .acc-kpi-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
  }

  .acc-kpi-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 700;
  }

  .acc-kpi-label {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--muted);
    text-align: right;
  }

  .acc-kpi-value {
    font-size: 2.2rem;
    line-height: 1.1;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--ink);
    margin: 12px 0 8px;
  }

  .acc-kpi-sub {
    font-size: 0.9rem;
    color: var(--muted);
    font-weight: 500;
  }

  /* KPI Colors (Apple-style pastel icon backgrounds) */
  .acc-kpi.top-blue .acc-kpi-icon { background: var(--blue-soft); color: var(--blue); }
  .acc-kpi.top-amber .acc-kpi-icon { background: var(--warning-soft); color: var(--warning); }
  .acc-kpi.top-rose .acc-kpi-icon { background: var(--success-soft); color: var(--success); } /* Balance neto mapped to green */
  .acc-kpi.top-red .acc-kpi-icon { background: var(--danger-soft); color: var(--danger); }

  /* MINI KPIs */
  .acc-mini {
    padding: 20px 24px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
  }

  .acc-mini-l .icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    margin-bottom: 12px;
  }

  .acc-mini-l .name {
    font-size: 1rem;
    font-weight: 700;
    color: var(--ink);
  }

  .acc-mini-l .amount {
    font-size: 0.95rem;
    color: var(--muted);
    margin-top: 4px;
    font-weight: 500;
  }

  .acc-mini-r {
    font-size: 1.8rem;
    line-height: 1;
    font-weight: 700;
  }

  /* Mini KPI Colors */
  .acc-mini.red .icon, .acc-mini.soft-red .icon { background: var(--danger-soft); color: var(--danger); }
  .acc-mini.red .acc-mini-r, .acc-mini.soft-red .acc-mini-r { color: var(--danger); }
  .acc-mini.yellow .icon { background: var(--warning-soft); color: var(--warning); }
  .acc-mini.yellow .acc-mini-r { color: var(--warning); }
  .acc-mini.green .icon { background: var(--success-soft); color: var(--success); }
  .acc-mini.green .acc-mini-r { color: var(--success); }

  /* ANALYTICS PANELS */
  .acc-analytics {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
    gap: 24px;
    margin: 32px 0 24px;
  }

  .acc-panel {
    padding: 24px;
  }

  .acc-panel-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--ink);
    margin-bottom: 24px;
  }

  .acc-chart-box {
    position: relative;
    width: 100%;
    height: 320px;
  }

  /* AGING LIST */
  .acc-aging-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .acc-aging-item {
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 16px;
    background: var(--card);
  }

  .acc-aging-top {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    margin-bottom: 12px;
  }

  .acc-aging-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--text-main);
  }

  .acc-aging-meta {
    display: flex;
    gap: 12px;
    align-items: center;
    color: var(--muted);
    font-weight: 600;
    font-size: 0.9rem;
  }

  .acc-aging-meta strong {
    color: var(--ink);
    font-size: 1rem;
    font-weight: 700;
  }

  .acc-progress {
    width: 100%;
    height: 6px;
    border-radius: 999px;
    background: var(--bg);
    overflow: hidden;
  }

  .acc-progress > span {
    display: block;
    height: 100%;
    border-radius: 999px;
  }

  /* Aging Colors */
  .acc-aging-item.green .acc-progress > span { background: var(--success); }
  .acc-aging-item.yellow .acc-progress > span { background: var(--warning); }
  .acc-aging-item.orange .acc-progress > span { background: #f97316; }
  .acc-aging-item.red .acc-progress > span { background: var(--danger); }
  .acc-aging-item.darkred .acc-progress > span { background: #991b1b; }

  /* LIST SECTIONS */
  .acc-lists {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 24px;
    margin-bottom: 24px;
  }

  .acc-list-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
    padding: 0 4px;
  }

  .acc-list-title h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    letter-spacing: -0.01em;
    color: var(--ink);
  }

  .acc-link-all {
    text-decoration: none;
    color: var(--blue);
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.2s ease;
  }

  .acc-link-all:hover {
    text-decoration: underline;
  }

  .acc-item {
    padding: 20px 24px;
    margin-bottom: 16px;
  }

  .acc-item-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
  }

  .acc-badges {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  /* BADGES */
  .acc-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 0.85rem;
    font-weight: 700;
    line-height: 1;
    border: none;
  }

  .acc-badge.danger { background: var(--danger-soft); color: var(--danger); }
  .acc-badge.warning { background: var(--warning-soft); color: var(--warning); }
  .acc-badge.info { background: var(--blue-soft); color: var(--blue); }
  .acc-badge.gray { background: var(--bg); color: var(--muted); border: 1px solid var(--line); }

  .acc-item-amount {
    text-align: right;
  }

  .acc-item-amount strong {
    display: block;
    font-size: 1.25rem;
    line-height: 1.1;
    font-weight: 700;
    color: var(--ink);
  }

  .acc-item-amount span {
    color: var(--muted);
    font-size: 0.9rem;
    font-weight: 600;
    margin-top: 4px;
    display: block;
  }

  .acc-item-main {
    margin-bottom: 16px;
  }

  .acc-item-main .title {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--ink);
    margin-bottom: 4px;
  }

  .acc-item-main .sub {
    font-size: 0.95rem;
    color: var(--muted);
    font-weight: 500;
  }

  .acc-item-foot {
    padding-top: 16px;
    border-top: 1px solid var(--line);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    color: var(--muted);
    font-size: 0.9rem;
    font-weight: 600;
  }

  .acc-item-foot .left {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
  }

  .acc-item-foot .late {
    color: var(--danger);
    font-weight: 700;
  }

  .acc-empty {
    padding: 40px 24px;
    text-align: center;
    color: var(--muted);
    font-weight: 600;
    font-size: 0.95rem;
  }

  /* REMINDERS */
  .acc-reminders {
    padding: 24px;
  }

  .acc-rem-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 20px;
  }

  .acc-rem-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--ink);
  }

  .acc-rem-count {
    min-width: 28px;
    height: 28px;
    padding: 0 8px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--danger-soft);
    color: var(--danger);
    font-weight: 700;
    font-size: 0.9rem;
  }

  .acc-rem-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 12px;
    text-decoration: none;
    background: var(--card);
    border: 1px solid var(--line);
    transition: all 0.2s ease;
  }

  .acc-rem-item:hover {
    background: var(--bg);
    transform: translateY(-1px);
  }

  .acc-rem-item .l {
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .acc-rem-item .ico {
    font-size: 1.2rem;
    color: var(--danger);
  }

  .acc-rem-item .tx strong {
    display: block;
    color: var(--ink);
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 4px;
  }

  .acc-rem-item .tx span {
    color: var(--muted);
    font-weight: 600;
    font-size: 0.9rem;
  }

  .acc-rem-item .r {
    color: var(--danger);
    font-weight: 700;
    font-size: 0.95rem;
    white-space: nowrap;
  }

  /* RESPONSIVE */
  @media (max-width: 1200px){
    .acc-grid-4,
    .acc-lists {
      grid-template-columns: repeat(2, minmax(0,1fr));
    }
    .acc-analytics {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 780px){
    .acc-head2,
    .acc-filtersWrap {
      flex-direction: column;
      align-items: stretch;
    }
    .acc-grid-4,
    .acc-lists {
      grid-template-columns: 1fr;
    }
    .acc-select {
      width: 100%;
    }
    .acc-kpi-value {
      font-size: 1.8rem;
    }
    .acc-item-head,
    .acc-item-foot {
      flex-direction: column;
      align-items: flex-start;
    }
    .acc-item-amount {
      text-align: left;
      margin-top: 12px;
    }
  }
</style>

<div class="acc-dash">
  <div class="acc-head2">
    <div>
      <h1 class="acc-title">Dashboard</h1>
      <div class="acc-sub">{{ \Carbon\Carbon::now()->translatedFormat('l d \\d\\e F, Y') }}</div>
    </div>
  </div>

  <div class="acc-filtersWrap">
    <form class="acc-filters2" method="GET" action="{{ route('accounting.dashboard') }}">
      <select name="company_id" class="acc-select" onchange="this.form.submit()">
        <option value="">Todas las compañías</option>
        @foreach($companies as $c)
          <option value="{{ $c->id }}" @selected($companyId==$c->id)>{{ $c->name }}</option>
        @endforeach
      </select>
      <a class="acc-filter-btn" href="{{ route('accounting.dashboard') }}">Limpiar</a>
    </form>
  </div>

  <div class="acc-grid-4">
    <a class="acc-kpi top-blue" href="{{ route('accounting.receivables.index', $q(['scope'=>'open'])) }}">
      <div class="acc-kpi-top">
        <div class="acc-kpi-icon">↗</div>
        <div class="acc-kpi-label">Por cobrar</div>
      </div>
      <div class="acc-kpi-value">{{ $fmt0($totalPorCobrar ?? 0) }}</div>
      <div class="acc-kpi-sub">Cobrado este mes: {{ $fmt0($cobradoMes ?? 0) }}</div>
    </a>

    <a class="acc-kpi top-amber" href="{{ route('accounting.payables.index', $q(['scope'=>'open'])) }}">
      <div class="acc-kpi-top">
        <div class="acc-kpi-icon">↘</div>
        <div class="acc-kpi-label">Por pagar</div>
      </div>
      <div class="acc-kpi-value">{{ $fmt0($totalPorPagar ?? 0) }}</div>
      <div class="acc-kpi-sub">Pagado este mes: {{ $fmt0($pagadoMes ?? 0) }}</div>
    </a>

    <a class="acc-kpi top-rose" href="{{ route('accounting.dashboard', $q()) }}#cashflow">
      <div class="acc-kpi-top">
        <div class="acc-kpi-icon">$</div>
        <div class="acc-kpi-label">Balance neto</div>
      </div>
      <div class="acc-kpi-value">
        {{ $balanceNetoValue < 0 ? '-' : '' }}{{ $fmt0(abs($balanceNetoValue)) }}
      </div>
      <div class="acc-kpi-sub">{{ $balanceNetoValue >= 0 ? 'Posición favorable' : 'Déficit proyectado' }}</div>
    </a>

    <a class="acc-kpi top-red" href="{{ route('accounting.alerts', $q()) }}">
      <div class="acc-kpi-top">
        <div class="acc-kpi-icon">⚠</div>
        <div class="acc-kpi-label">Alertas</div>
      </div>
      <div class="acc-kpi-value">{{ (int)($alertsCount ?? 0) }}</div>
      <div class="acc-kpi-sub">{{ $urgentPayments->count() }} pagos · {{ $overdueReceivables->count() }} cobros vencidos</div>
    </a>
  </div>

  <div class="acc-grid-4">
    <a class="acc-mini red" href="{{ route('accounting.receivables.index', $q(['scope'=>'overdue'])) }}">
      <div class="acc-mini-l">
        <span class="icon">⚠</span>
        <div class="name">Atrasados</div>
        <div class="amount">{{ $fmt2($atrasadosMonto) }}</div>
      </div>
      <div class="acc-mini-r">{{ $atrasadosCount }}</div>
    </a>

    <a class="acc-mini soft-red" href="{{ route('accounting.payables.index', $q(['scope'=>'urgent'])) }}">
      <div class="acc-mini-l">
        <span class="icon">🕒</span>
        <div class="name">Urgentes</div>
        <div class="amount">{{ $fmt2($urgentesMonto) }}</div>
      </div>
      <div class="acc-mini-r">{{ $urgentesCount }}</div>
    </a>

    <a class="acc-mini yellow" href="{{ route('accounting.payables.index', $q(['scope'=>'upcoming'])) }}">
      <div class="acc-mini-l">
        <span class="icon">↗</span>
        <div class="name">Pendientes</div>
        <div class="amount">{{ $fmt2($pendientesMonto) }}</div>
      </div>
      <div class="acc-mini-r">{{ $pendientesCount }}</div>
    </a>

    <a class="acc-mini green" href="{{ route('accounting.dashboard', $q()) }}#upcoming">
      <div class="acc-mini-l">
        <span class="icon">✓</span>
        <div class="name">Pagados</div>
        <div class="amount">{{ $fmt2($pagadosMonto) }}</div>
      </div>
      <div class="acc-mini-r">{{ $pagadosCount }}</div>
    </a>
  </div>

  <div class="acc-analytics" id="cashflow">
    <div class="acc-panel">
      <div class="acc-panel-title">Flujo de Caja Proyectado</div>
      <div class="acc-chart-box">
        <canvas id="cashflowChart"></canvas>
      </div>
    </div>

    <div class="acc-panel">
      <div class="acc-panel-title">Antigüedad de Cartera</div>

      <div class="acc-aging-list">
        @php
          $agingColors = [
            'Al corriente' => 'green',
            '1-30 días'    => 'yellow',
            '31-60 días'   => 'orange',
            '61-90 días'   => 'red',
            '+90 días'     => 'darkred',
          ];
        @endphp

        @foreach($agingBuckets as $label => $value)
          @php
            $width = $agingTotal > 0 ? (($value / $agingTotal) * 100) : 0;
            $docsCount = 0;
          @endphp
          <div class="acc-aging-item {{ $agingColors[$label] ?? 'yellow' }}">
            <div class="acc-aging-top">
              <div class="acc-aging-name">{{ $label }}</div>
              <div class="acc-aging-meta">
                <strong>{{ $fmt0($value) }}</strong>
                <span>{{ $docsCount }} doc.</span>
              </div>
            </div>
            <div class="acc-progress">
              <span style="width: {{ max($width, $value > 0 ? 6 : 0) }}%"></span>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <div class="acc-lists" id="alerts">
    <div>
      <div class="acc-list-title left">
        <h3>PAGOS URGENTES / ATRASADOS</h3>
        <a class="acc-link-all" href="{{ route('accounting.payables.index', $q(['scope'=>'urgent'])) }}">Ver todos →</a>
      </div>

      @if($urgentPayments->count())
        @foreach($urgentPayments->take(4) as $p)
          @php
            $saldo = max((float)$p->amount - (float)$p->amount_paid, 0);
            $meta = $cardMeta('payable', $p);
            $dueDate = $formatDueDate($p->due_date);
            $toneClass = $meta['tone'] === 'danger' ? 'danger' : 'warning';
          @endphp

          <a class="acc-item {{ $toneClass }}" href="{{ route('accounting.payables.show',$p) }}">
            <div class="acc-item-head">
              <div class="acc-badges">
                <span class="acc-badge {{ $meta['tone'] === 'danger' ? 'danger' : 'warning' }}">● {{ $meta['status'] }}</span>
                <span class="acc-badge gray">{{ $meta['cycle'] }}</span>
                @if(!empty($p->reference))
                  <span class="acc-badge gray">#{{ $p->reference }}</span>
                @endif
              </div>
              <div class="acc-item-amount">
                <strong>{{ $fmt2($saldo) }}</strong>
                <span>MXN</span>
              </div>
            </div>

            <div class="acc-item-main">
              <div class="title">{{ $p->title }}</div>
              <div class="sub">{{ $p->company?->name ?? 'Cuenta por Pagar' }}</div>
            </div>

            <div class="acc-item-foot">
              <div class="left">
                <span>📅 {{ $dueDate }}</span>
                <span class="late">{{ $meta['due_text'] }}</span>
                @if(!empty($p->attachments_count))
                  <span>📎 {{ $p->attachments_count }}</span>
                @endif
              </div>
              <div>›</div>
            </div>
          </a>
        @endforeach
      @else
        <div class="acc-empty">Sin pagos urgentes</div>
      @endif
    </div>

    <div>
      <div class="acc-list-title right">
        <h3>COBROS VENCIDOS</h3>
        <a class="acc-link-all" href="{{ route('accounting.receivables.index', $q(['scope'=>'overdue'])) }}">Ver todos →</a>
      </div>

      @if($overdueReceivables->count())
        @foreach($overdueReceivables->take(4) as $r)
          @php
            $saldo = max((float)$r->amount - (float)$r->amount_paid, 0);
            $meta = $cardMeta('receivable', $r);
            $dueDate = $formatDueDate($r->due_date);
            $partial = ((float)$r->amount_paid > 0 && (float)$r->amount_paid < (float)$r->amount);
          @endphp

          <a class="acc-item {{ $partial ? 'info' : 'warning' }}" href="{{ route('accounting.receivables.show',$r) }}">
            <div class="acc-item-head">
              <div class="acc-badges">
                <span class="acc-badge {{ $partial ? 'info' : 'warning' }}">● {{ $partial ? 'Parcial' : 'Factura' }}</span>
                <span class="acc-badge gray">{{ $meta['cycle'] }}</span>
                @if(!empty($r->reference))
                  <span class="acc-badge gray">#{{ $r->reference }}</span>
                @endif
              </div>
              <div class="acc-item-amount">
                <strong>{{ $fmt2($saldo) }}</strong>
                <span>de {{ $fmt0($r->amount ?? 0) }} MXN</span>
              </div>
            </div>

            <div class="acc-item-main">
              <div class="title">{{ $r->client_name }}</div>
              <div class="sub">{{ $r->company?->name ?? 'Cuenta por Cobrar' }}</div>
            </div>

            <div class="acc-item-foot">
              <div class="left">
                <span>📅 {{ $dueDate }}</span>
                <span class="late">{{ $meta['due_text'] }}</span>
                @if(!empty($r->attachments_count))
                  <span>📎 {{ $r->attachments_count }}</span>
                @endif
              </div>
              <div>›</div>
            </div>
          </a>
        @endforeach
      @else
        <div class="acc-empty">Sin cobros vencidos</div>
      @endif
    </div>
  </div>

  <div class="acc-lists" id="upcoming">
    <div>
      @if($upcomingPayments->count())
        @foreach($upcomingPayments->take(2) as $p)
          @php
            $saldo = max((float)$p->amount - (float)$p->amount_paid, 0);
            $meta = $cardMeta('payable', $p);
            $dueDate = $formatDueDate($p->due_date);
          @endphp

          <a class="acc-item {{ $meta['tone'] === 'danger' ? 'danger' : 'warning' }}" href="{{ route('accounting.payables.show',$p) }}">
            <div class="acc-item-head">
              <div class="acc-badges">
                <span class="acc-badge {{ $meta['tone'] === 'danger' ? 'danger' : 'warning' }}">● {{ $meta['status'] }}</span>
                <span class="acc-badge gray">Mensual</span>
              </div>
              <div class="acc-item-amount">
                <strong>{{ $fmt2($saldo) }}</strong>
                <span>MXN</span>
              </div>
            </div>

            <div class="acc-item-main">
              <div class="title">{{ $p->title }}</div>
              <div class="sub">{{ $p->company?->name ?? 'Pago próximo' }}</div>
            </div>

            <div class="acc-item-foot">
              <div class="left">
                <span>📅 {{ $dueDate }}</span>
                <span class="late">{{ $meta['due_text'] }}</span>
              </div>
              <div>›</div>
            </div>
          </a>
        @endforeach
      @else
        <div class="acc-empty">Sin pagos próximos</div>
      @endif
    </div>

    <div>
      @if($upcomingReceivables->count())
        @foreach($upcomingReceivables->take(2) as $r)
          @php
            $saldo = max((float)$r->amount - (float)$r->amount_paid, 0);
            $meta = $cardMeta('receivable', $r);
            $dueDate = $formatDueDate($r->due_date);
          @endphp

          <a class="acc-item info" href="{{ route('accounting.receivables.show',$r) }}">
            <div class="acc-item-head">
              <div class="acc-badges">
                <span class="acc-badge info">● {{ $meta['status'] }}</span>
                <span class="acc-badge gray">Factura</span>
              </div>
              <div class="acc-item-amount">
                <strong>{{ $fmt2($saldo) }}</strong>
                <span>MXN</span>
              </div>
            </div>

            <div class="acc-item-main">
              <div class="title">{{ $r->client_name }}</div>
              <div class="sub">{{ $r->company?->name ?? 'Cobro próximo' }}</div>
            </div>

            <div class="acc-item-foot">
              <div class="left">
                <span>📅 {{ $dueDate }}</span>
                <span>{{ $meta['due_text'] }}</span>
              </div>
              <div>›</div>
            </div>
          </a>
        @endforeach
      @else
        <div class="acc-empty">Sin cobros próximos</div>
      @endif
    </div>
  </div>

  <div class="acc-reminders">
    <div class="acc-rem-head">
      <div class="acc-rem-title">
        <span style="color:var(--blue)">◌</span>
        <span>Recordatorios</span>
      </div>
      <div class="acc-rem-count">{{ $reminders->count() }}</div>
    </div>

    @forelse($reminders as $rem)
      <a href="{{ $rem['url'] }}" class="acc-rem-item">
        <div class="l">
          <div class="ico">⚠</div>
          <div class="tx">
            <strong>{{ $rem['title'] }}</strong>
            <span>{{ $fmt2($rem['amount']) }}</span>
          </div>
        </div>
        <div class="r">{{ $rem['meta']['due_text'] }}</div>
      </a>
    @empty
      <div class="acc-empty" style="border-style:dashed;">Sin recordatorios pendientes</div>
    @endforelse
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  const labels = @json($labels ?? []);
  const incoming = @json($inByDay ?? []);
  const outgoing = @json($outByDay ?? []);
  const net = @json($netByDay ?? []);

  const ctx = document.getElementById('cashflowChart');

  if (ctx) {
    if (window.cashflowChartInstance) {
      window.cashflowChartInstance.destroy();
    }

    window.cashflowChartInstance = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Por Cobrar',
            data: incoming,
            backgroundColor: 'rgba(37, 99, 235, 0.85)',
            borderColor: 'rgba(37, 99, 235, 1)',
            borderWidth: 1,
            borderRadius: 8,
            maxBarThickness: 28,
          },
          {
            label: 'Por Pagar',
            data: outgoing,
            backgroundColor: 'rgba(245, 158, 11, 0.90)',
            borderColor: 'rgba(245, 158, 11, 1)',
            borderWidth: 1,
            borderRadius: 8,
            maxBarThickness: 28,
          },
          {
            type: 'line',
            label: 'Neto',
            data: net,
            borderColor: 'rgba(21, 128, 61, 1)',
            backgroundColor: 'rgba(21, 128, 61, 0.12)',
            borderWidth: 3,
            tension: 0.35,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: 'rgba(21, 128, 61, 1)',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            fill: false,
            yAxisID: 'y'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        interaction: {
          mode: 'index',
          intersect: false
        },
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              usePointStyle: true,
              boxWidth: 10,
              padding: 18,
              color: '#888888',
              font: {
                size: 12,
                weight: '700',
                family: "'Quicksand', sans-serif"
              }
            }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                return `${context.dataset.label}: $${Number(context.raw || 0).toLocaleString('es-MX')}`;
              }
            }
          }
        },
        scales: {
          x: {
            stacked: false,
            grid: {
              display: false
            },
            ticks: {
              color: '#888888',
              font: {
                weight: '700',
                family: "'Quicksand', sans-serif"
              }
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(235, 235, 235, 1)'
            },
            ticks: {
              color: '#888888',
              font: {
                  family: "'Quicksand', sans-serif",
                  weight: '600'
              },
              callback: function(value) {
                return '$' + Number(value).toLocaleString('es-MX');
              }
            }
          }
        }
      }
    });
  }
</script>
@endsection