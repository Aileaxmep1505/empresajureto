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

  :root {
    --bg: #f4f5f7;
    --card: #ffffff;
    --input-bg: #f9fafb;
    --ink-dark: #0f172a;
    --ink: #334155;
    --muted: #64748b;
    --muted-light: #94a3b8;
    --line: #e2e8f0;
    --blue: #007aff;
    --blue-soft: #eff6ff;
    --success: #15803d;
    --success-soft: #f0fdf4;
    --danger: #ef4444;
    --danger-soft: #fef2f2;
    --warning: #c2410c;
    --warning-soft: #fff7ed;
  }

  body {
    background-color: var(--bg);
  }

  .acc-dash {
    font-family: 'Quicksand', sans-serif;
    color: var(--ink);
    max-width: 1400px;
    margin: 0 auto;
    padding: 32px 16px;
  }

  /* HEADERS */
  .acc-head2 {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
  }

  .acc-title {
    font-size: 2.25rem;
    font-weight: 700;
    color: var(--ink-dark);
    letter-spacing: -0.02em;
    margin: 0 0 4px 0;
  }

  .acc-sub {
    color: var(--muted);
    font-size: 1.05rem;
    font-weight: 500;
  }

  /* TOOLBAR & BUTTONS */
  .acc-filtersWrap {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 32px;
  }

  .acc-filters2 {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .acc-filter-btn {
    background: transparent;
    color: var(--muted);
    border: none;
    border-radius: 8px;
    padding: 0 20px;
    height: 42px;
    font-family: 'Quicksand', sans-serif;
    font-weight: 600;
    font-size: 0.95rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    cursor: pointer;
  }

  .acc-filter-btn:hover {
    background: #f9fafb;
    color: var(--ink-dark);
    transform: translateY(-1px);
  }

  .acc-filter-btn:active {
    transform: scale(0.98);
  }

  /* CUSTOM SELECT RULES (Regla 7) */
  .custom-select-wrapper {
    position: relative;
    min-width: 260px;
    user-select: none;
  }
  .custom-select-trigger {
    height: 42px;
    background: var(--input-bg);
    border: 1px solid var(--line);
    border-radius: 8px;
    padding: 0 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--ink);
    cursor: pointer;
    transition: all 0.2s ease;
  }
  .custom-select-wrapper.open .custom-select-trigger {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }
  .custom-select-trigger svg {
    width: 18px;
    height: 18px;
    color: var(--muted-light);
    transition: transform 0.2s ease;
  }
  .custom-select-wrapper.open .custom-select-trigger svg {
    transform: rotate(180deg);
  }
  .custom-select-options {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    width: 100%;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-8px) scale(0.98);
    transition: all 0.2s ease;
    z-index: 50;
    overflow: hidden;
  }
  .custom-select-wrapper.open .custom-select-options {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scale(1);
  }
  .custom-select-option {
    padding: 12px 16px;
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--ink);
    cursor: pointer;
    transition: background 0.15s ease;
  }
  .custom-select-option:hover {
    background: var(--input-bg);
    color: var(--blue);
  }
  .custom-select-option.selected {
    font-weight: 700;
    color: var(--blue);
    background: var(--blue-soft);
  }

  /* COMMON CARDS */
  .acc-kpi, .acc-mini, .acc-panel, .acc-item, .acc-reminders, .acc-empty {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    text-decoration: none;
    display: block;
    color: inherit;
  }

  .acc-kpi:hover, .acc-mini:hover, .acc-item:hover, .acc-reminders:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.04);
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
    margin-bottom: 16px;
  }

  .acc-kpi-icon {
    width: 44px;
    height: 44px;
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
    font-size: 2.1rem;
    line-height: 1.1;
    font-weight: 700;
    color: var(--ink-dark);
    margin: 0 0 6px 0;
    letter-spacing: -0.02em;
  }

  .acc-kpi-sub {
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--muted);
  }

  /* Theme mappings for KPIs */
  .acc-kpi.top-blue .acc-kpi-icon { background: var(--blue-soft); color: var(--blue); }
  .acc-kpi.top-amber .acc-kpi-icon { background: var(--warning-soft); color: var(--warning); }
  .acc-kpi.top-rose .acc-kpi-icon { background: var(--success-soft); color: var(--success); }
  .acc-kpi.top-red .acc-kpi-icon { background: var(--danger-soft); color: var(--danger); }

  /* MINI KPIs */
  .acc-mini {
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
  }

  .acc-mini-l .icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    margin-bottom: 12px;
  }

  .acc-mini-l .name {
    font-size: 1rem;
    font-weight: 700;
    color: var(--ink-dark);
    margin-bottom: 2px;
  }

  .acc-mini-l .amount {
    font-size: 0.9rem;
    color: var(--muted);
    font-weight: 500;
  }

  .acc-mini-r {
    font-size: 1.85rem;
    font-weight: 700;
    letter-spacing: -0.02em;
  }

  /* Theme mappings for Mini KPIs */
  .acc-mini.red .icon, .acc-mini.soft-red .icon { background: var(--danger-soft); color: var(--danger); }
  .acc-mini.red .acc-mini-r, .acc-mini.soft-red .acc-mini-r { color: var(--danger); }
  
  .acc-mini.yellow .icon { background: var(--warning-soft); color: var(--warning); }
  .acc-mini.yellow .acc-mini-r { color: var(--warning); }
  
  .acc-mini.green .icon { background: var(--success-soft); color: var(--success); }
  .acc-mini.green .acc-mini-r { color: var(--success); }

  /* ANALYTICS (Charts & Aging) */
  .acc-analytics {
    display: grid;
    grid-template-columns: minmax(0, 2.2fr) minmax(340px, 1fr);
    gap: 24px;
    margin: 32px 0 24px;
  }

  .acc-panel {
    padding: 24px 28px;
  }

  .acc-panel-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--ink-dark);
    margin-bottom: 24px;
  }

  .acc-chart-box {
    position: relative;
    width: 100%;
    height: 320px;
  }

  .acc-aging-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
  }

  .acc-aging-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .acc-aging-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .acc-aging-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--ink);
  }

  .acc-aging-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--muted);
    font-size: 0.85rem;
    font-weight: 500;
  }

  .acc-aging-meta strong {
    color: var(--ink-dark);
    font-size: 0.95rem;
    font-weight: 700;
  }

  .acc-progress {
    width: 100%;
    height: 6px;
    border-radius: 999px;
    background: var(--line);
    overflow: hidden;
  }

  .acc-progress > span {
    display: block;
    height: 100%;
    border-radius: 999px;
    transition: width 0.4s ease;
  }

  /* Aging Colors Map */
  .acc-aging-item.green .acc-progress > span { background: var(--success); }
  .acc-aging-item.yellow .acc-progress > span { background: var(--warning); }
  .acc-aging-item.orange .acc-progress > span { background: #f97316; }
  .acc-aging-item.red .acc-progress > span { background: var(--danger); }
  .acc-aging-item.darkred .acc-progress > span { background: #991b1b; }

  /* LIST SECTIONS */
  .acc-lists {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 24px;
    margin-bottom: 32px;
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
    color: var(--ink-dark);
    letter-spacing: -0.01em;
  }

  .acc-link-all {
    color: var(--blue);
    font-weight: 600;
    font-size: 0.95rem;
    text-decoration: none;
    transition: color 0.2s;
  }

  .acc-link-all:hover {
    text-decoration: underline;
  }

  .acc-item {
    padding: 24px;
    margin-bottom: 16px;
  }

  .acc-item-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
  }

  /* BADGES */
  .acc-badges {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .acc-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 999px;
    padding: 6px 14px;
    font-size: 0.8rem;
    font-weight: 700;
    border: none;
  }

  .acc-badge.danger { background: var(--danger-soft); color: var(--danger); }
  .acc-badge.warning { background: var(--warning-soft); color: var(--warning); }
  .acc-badge.info { background: var(--blue-soft); color: var(--blue); }
  .acc-badge.gray { background: var(--input-bg); color: var(--muted); border: 1px solid var(--line); }

  .acc-item-amount {
    text-align: right;
  }

  .acc-item-amount strong {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--ink-dark);
  }

  .acc-item-amount span {
    color: var(--muted);
    font-size: 0.85rem;
    font-weight: 600;
    margin-top: 2px;
    display: block;
  }

  .acc-item-main {
    margin-bottom: 20px;
  }

  .acc-item-main .title {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--ink-dark);
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
  }

  .acc-item-foot .arr {
    color: var(--muted-light);
    font-size: 1.2rem;
    line-height: 1;
    transition: transform 0.2s ease, color 0.2s ease;
  }
  
  .acc-item:hover .acc-item-foot .arr {
    transform: translateX(4px);
    color: var(--blue);
  }

  /* EMPTY STATES */
  .acc-empty {
    padding: 48px 24px;
    text-align: center;
    color: var(--muted);
    font-weight: 500;
    font-size: 1rem;
    background: transparent;
    border: 1px dashed var(--muted-light);
    box-shadow: none;
  }
  
  .acc-empty:hover {
    transform: none;
    box-shadow: none;
  }

  /* REMINDERS */
  .acc-reminders {
    padding: 24px 28px;
  }

  .acc-rem-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 24px;
  }

  .acc-rem-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--ink-dark);
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
    font-size: 0.85rem;
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
    background: var(--input-bg);
    transform: translateY(-2px);
    border-color: var(--blue-soft);
  }

  .acc-rem-item .l {
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .acc-rem-item .ico {
    font-size: 1.25rem;
    color: var(--danger);
  }

  .acc-rem-item .tx strong {
    display: block;
    color: var(--ink-dark);
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 2px;
  }

  .acc-rem-item .tx span {
    color: var(--muted);
    font-weight: 500;
    font-size: 0.9rem;
  }

  .acc-rem-item .r {
    color: var(--danger);
    font-weight: 600;
    font-size: 0.95rem;
  }

  /* RESPONSIVE */
  @media (max-width: 1200px){
    .acc-grid-4, .acc-lists {
      grid-template-columns: repeat(2, minmax(0,1fr));
    }
    .acc-analytics {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 768px){
    .acc-dash {
      padding: 24px 12px;
    }
    .acc-head2 {
      flex-direction: column;
      gap: 12px;
    }
    .acc-filtersWrap {
      justify-content: flex-start;
    }
    .acc-filters2 {
      width: 100%;
      flex-direction: column;
      align-items: stretch;
    }
    .custom-select-wrapper {
      width: 100%;
    }
    .acc-filter-btn {
      width: 100%;
      background: var(--card);
      border: 1px solid var(--line);
    }
    .acc-grid-4, .acc-lists {
      grid-template-columns: 1fr;
    }
    .acc-item-head, .acc-item-foot {
      flex-direction: column;
      align-items: flex-start;
    }
    .acc-item-amount {
      text-align: left;
      margin-top: 12px;
    }
    .acc-item-foot .left {
      gap: 8px;
      flex-direction: column;
      align-items: flex-start;
      margin-bottom: 12px;
    }
    .acc-item-foot .arr { align-self: flex-end; }
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
    <form class="acc-filters2" method="GET" action="{{ route('accounting.dashboard') }}" id="dashFilterForm">
      <!-- SELECT NATIVO (Se oculta y se reemplaza visualmente con JS) -->
      <select name="company_id" class="acc-select">
        <option value="">Todas las compañías</option>
        @foreach($companies as $c)
          <option value="{{ $c->id }}" @selected($companyId==$c->id)>{{ $c->name }}</option>
        @endforeach
      </select>
      
      <a class="acc-filter-btn" href="{{ route('accounting.dashboard') }}">Limpiar filtros</a>
    </form>
  </div>

  <div class="acc-grid-4">
    <a class="acc-kpi top-blue" href="{{ route('accounting.receivables.index', $q(['scope'=>'open'])) }}">
      <div class="acc-kpi-top">
        <div class="acc-kpi-icon">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H7M17 7V17"></path></svg>
        </div>
        <div class="acc-kpi-label">Por cobrar</div>
      </div>
      <div class="acc-kpi-value">{{ $fmt0($totalPorCobrar ?? 0) }}</div>
      <div class="acc-kpi-sub">Cobrado este mes: {{ $fmt0($cobradoMes ?? 0) }}</div>
    </a>

    <a class="acc-kpi top-amber" href="{{ route('accounting.payables.index', $q(['scope'=>'open'])) }}">
      <div class="acc-kpi-top">
        <div class="acc-kpi-icon">
             <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 7L7 17M7 17H17M7 17V7"></path></svg>
        </div>
        <div class="acc-kpi-label">Por pagar</div>
      </div>
      <div class="acc-kpi-value">{{ $fmt0($totalPorPagar ?? 0) }}</div>
      <div class="acc-kpi-sub">Pagado este mes: {{ $fmt0($pagadoMes ?? 0) }}</div>
    </a>

    <a class="acc-kpi top-rose" href="{{ route('accounting.dashboard', $q()) }}#cashflow">
      <div class="acc-kpi-top">
        <div class="acc-kpi-icon">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div class="acc-kpi-label">Balance neto</div>
      </div>
      <div class="acc-kpi-value">
        {{ $balanceNetoValue < 0 ? '-' : '' }}{{ $fmt0(abs($balanceNetoValue)) }}
      </div>
      <div class="acc-kpi-sub">{{ $balanceNetoValue >= 0 ? 'Posición favorable' : 'Déficit proyectado' }}</div>
    </a>

    <a class="acc-kpi top-red" href="{{ route('accounting.alerts', $q()) }}">
      <div class="acc-kpi-top">
        <div class="acc-kpi-icon">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </div>
        <div class="acc-kpi-label">Alertas</div>
      </div>
      <div class="acc-kpi-value">{{ (int)($alertsCount ?? 0) }}</div>
      <div class="acc-kpi-sub">{{ $urgentPayments->count() }} pagos · {{ $overdueReceivables->count() }} cobros</div>
    </a>
  </div>

  <div class="acc-grid-4">
    <a class="acc-mini red" href="{{ route('accounting.receivables.index', $q(['scope'=>'overdue'])) }}">
      <div class="acc-mini-l">
        <div class="icon">
             <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div class="name">Atrasados</div>
        <div class="amount">{{ $fmt2($atrasadosMonto) }}</div>
      </div>
      <div class="acc-mini-r">{{ $atrasadosCount }}</div>
    </a>

    <a class="acc-mini soft-red" href="{{ route('accounting.payables.index', $q(['scope'=>'urgent'])) }}">
      <div class="acc-mini-l">
        <div class="icon">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div class="name">Urgentes</div>
        <div class="amount">{{ $fmt2($urgentesMonto) }}</div>
      </div>
      <div class="acc-mini-r">{{ $urgentesCount }}</div>
    </a>

    <a class="acc-mini yellow" href="{{ route('accounting.payables.index', $q(['scope'=>'upcoming'])) }}">
      <div class="acc-mini-l">
        <div class="icon">
             <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
        </div>
        <div class="name">Pendientes</div>
        <div class="amount">{{ $fmt2($pendientesMonto) }}</div>
      </div>
      <div class="acc-mini-r">{{ $pendientesCount }}</div>
    </a>

    <a class="acc-mini green" href="{{ route('accounting.dashboard', $q()) }}#upcoming">
      <div class="acc-mini-l">
        <div class="icon">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
        </div>
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
        <a class="acc-link-all" href="{{ route('accounting.payables.index', $q(['scope'=>'urgent'])) }}">Ver todos</a>
      </div>

      @if($urgentPayments->count())
        @foreach($urgentPayments->take(4) as $p)
          @php
            $saldo = max((float)$p->amount - (float)$p->amount_paid, 0);
            $meta = $cardMeta('payable', $p);
            $dueDate = $formatDueDate($p->due_date);
            $toneClass = $meta['tone'] === 'danger' ? 'danger' : 'warning';
          @endphp

          <a class="acc-item" href="{{ route('accounting.payables.show',$p) }}">
            <div class="acc-item-head">
              <div class="acc-badges">
                <span class="acc-badge {{ $toneClass }}">{{ $meta['status'] }}</span>
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
              <div class="arr">›</div>
            </div>
          </a>
        @endforeach
      @else
        <div class="acc-empty">No hay pagos urgentes en este momento.</div>
      @endif
    </div>

    <div>
      <div class="acc-list-title right">
        <h3>COBROS VENCIDOS</h3>
        <a class="acc-link-all" href="{{ route('accounting.receivables.index', $q(['scope'=>'overdue'])) }}">Ver todos</a>
      </div>

      @if($overdueReceivables->count())
        @foreach($overdueReceivables->take(4) as $r)
          @php
            $saldo = max((float)$r->amount - (float)$r->amount_paid, 0);
            $meta = $cardMeta('receivable', $r);
            $dueDate = $formatDueDate($r->due_date);
            $partial = ((float)$r->amount_paid > 0 && (float)$r->amount_paid < (float)$r->amount);
          @endphp

          <a class="acc-item" href="{{ route('accounting.receivables.show',$r) }}">
            <div class="acc-item-head">
              <div class="acc-badges">
                <span class="acc-badge {{ $partial ? 'info' : 'warning' }}">{{ $partial ? 'Parcial' : 'Factura' }}</span>
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
              <div class="arr">›</div>
            </div>
          </a>
        @endforeach
      @else
        <div class="acc-empty">No tienes cobros vencidos. ¡Excelente!</div>
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

          <a class="acc-item" href="{{ route('accounting.payables.show',$p) }}">
            <div class="acc-item-head">
              <div class="acc-badges">
                <span class="acc-badge {{ $meta['tone'] === 'danger' ? 'danger' : 'warning' }}">{{ $meta['status'] }}</span>
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
              <div class="arr">›</div>
            </div>
          </a>
        @endforeach
      @else
        <div class="acc-empty">Sin pagos próximos registrados.</div>
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

          <a class="acc-item" href="{{ route('accounting.receivables.show',$r) }}">
            <div class="acc-item-head">
              <div class="acc-badges">
                <span class="acc-badge info">{{ $meta['status'] }}</span>
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
              <div class="arr">›</div>
            </div>
          </a>
        @endforeach
      @else
        <div class="acc-empty">Sin cobros próximos registrados.</div>
      @endif
    </div>
  </div>

  <div class="acc-reminders">
    <div class="acc-rem-head">
      <div class="acc-rem-title">
        <svg width="22" height="22" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
        <span>Recordatorios</span>
      </div>
      <div class="acc-rem-count">{{ $reminders->count() }}</div>
    </div>

    @forelse($reminders as $rem)
      <a href="{{ $rem['url'] }}" class="acc-rem-item">
        <div class="l">
          <div class="ico">
              <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          </div>
          <div class="tx">
            <strong>{{ $rem['title'] }}</strong>
            <span>{{ $fmt2($rem['amount']) }}</span>
          </div>
        </div>
        <div class="r">{{ $rem['meta']['due_text'] }}</div>
      </a>
    @empty
      <div class="acc-empty">No hay recordatorios pendientes. Todo está al día.</div>
    @endforelse
  </div>
</div>

<!-- SCRIPTS (Mantiene JS original de Chart y agrega Custom Select UI) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  // ===== LÓGICA DE CUSTOM SELECT (Regla 7) =====
  document.addEventListener('DOMContentLoaded', function() {
    const selects = document.querySelectorAll('select.acc-select');
    
    selects.forEach(select => {
      // Ocultar select nativo
      select.style.display = 'none';

      // Crear envoltorio
      const wrapper = document.createElement('div');
      wrapper.className = 'custom-select-wrapper';
      select.parentNode.insertBefore(wrapper, select);
      wrapper.appendChild(select);

      // Crear botón/trigger visual
      const trigger = document.createElement('div');
      trigger.className = 'custom-select-trigger';
      
      const selectedOptionText = select.options.length > 0 ? select.options[select.selectedIndex].text : '';
      trigger.innerHTML = `<span>${selectedOptionText}</span>
                           <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>`;
      wrapper.appendChild(trigger);

      // Crear contenedor de opciones
      const optionsContainer = document.createElement('div');
      optionsContainer.className = 'custom-select-options';
      wrapper.appendChild(optionsContainer);

      // Poblar opciones
      Array.from(select.options).forEach((option, index) => {
        const optionDiv = document.createElement('div');
        optionDiv.className = 'custom-select-option' + (option.selected ? ' selected' : '');
        optionDiv.textContent = option.text;
        
        optionDiv.addEventListener('click', function(e) {
          e.stopPropagation();
          // Asignar valor al select nativo
          select.value = option.value;
          // Actualizar UI
          trigger.querySelector('span').textContent = option.text;
          wrapper.querySelectorAll('.custom-select-option').forEach(opt => opt.classList.remove('selected'));
          optionDiv.classList.add('selected');
          wrapper.classList.remove('open');
          
          // Disparar evento change manualmente (para ejecutar onchange="this.form.submit()")
          select.dispatchEvent(new Event('change', { bubbles: true }));
        });
        
        optionsContainer.appendChild(optionDiv);
      });

      // Toggle dropdown
      trigger.addEventListener('click', function(e) {
        e.stopPropagation();
        // Cerrar otros dropdowns abiertos
        document.querySelectorAll('.custom-select-wrapper').forEach(w => {
          if (w !== wrapper) w.classList.remove('open');
        });
        wrapper.classList.toggle('open');
      });
    });

    // Cerrar clickeando afuera
    document.addEventListener('click', function() {
      document.querySelectorAll('.custom-select-wrapper').forEach(w => w.classList.remove('open'));
    });
  });

  // ===== LÓGICA DE CHART.JS (Intacta con variables de color del tema) =====
  const labels = @json($labels ?? []);
  const incoming = @json($inByDay ?? []);
  const outgoing = @json($outByDay ?? []);
  const net = @json($netByDay ?? []);

  const ctx = document.getElementById('cashflowChart');

  if (ctx) {
    if (window.cashflowChartInstance) {
      window.cashflowChartInstance.destroy();
    }

    // Colores mapeados al sistema de diseño
    const colorBlue = '#007aff';
    const colorWarning = '#c2410c';
    const colorSuccess = '#15803d';

    window.cashflowChartInstance = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Por Cobrar',
            data: incoming,
            backgroundColor: 'rgba(0, 122, 255, 0.85)',
            borderColor: colorBlue,
            borderWidth: 1,
            borderRadius: 6,
            maxBarThickness: 28,
          },
          {
            label: 'Por Pagar',
            data: outgoing,
            backgroundColor: 'rgba(194, 65, 12, 0.85)',
            borderColor: colorWarning,
            borderWidth: 1,
            borderRadius: 6,
            maxBarThickness: 28,
          },
          {
            type: 'line',
            label: 'Neto',
            data: net,
            borderColor: colorSuccess,
            backgroundColor: 'rgba(21, 128, 61, 0.1)',
            borderWidth: 3,
            tension: 0.35,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: colorSuccess,
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            fill: true,
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
              boxWidth: 8,
              padding: 20,
              color: '#64748b',
              font: {
                size: 13,
                weight: '600',
                family: "'Quicksand', sans-serif"
              }
            }
          },
          tooltip: {
            backgroundColor: 'rgba(15, 23, 42, 0.9)',
            titleFont: { family: "'Quicksand', sans-serif", size: 13 },
            bodyFont: { family: "'Quicksand', sans-serif", size: 14, weight: '600' },
            padding: 12,
            cornerRadius: 8,
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
              color: '#94a3b8',
              font: {
                weight: '600',
                family: "'Quicksand', sans-serif"
              }
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(226, 232, 240, 0.6)'
            },
            ticks: {
              color: '#94a3b8',
              font: {
                  family: "'Quicksand', sans-serif",
                  weight: '600'
              },
              callback: function(value) {
                return '$' + Number(value).toLocaleString('es-MX');
              }
            },
            border: { display: false }
          }
        }
      }
    });
  }
</script>
@endsection