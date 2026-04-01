@extends('layouts.app')
@section('title','Dashboard · Cuentas')

@section('content')
@include('accounting.partials.ui')

@php
  use Carbon\Carbon;

  $q = fn(array $extra = []) => array_filter(array_merge(['company_id'=>$companyId], $extra), fn($v)=>$v!==null && $v!=='');

  $fmt0 = fn($n) => '$' . number_format((float)$n, 0);
  $fmt2 = fn($n) => '$' . number_format((float)$n, 2);

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

  $today = now()->startOfDay();

  $cardMeta = function ($type, $item) use ($today) {
      $due = optional($item->due_date);
      $days = $due ? $today->diffInDays(Carbon::parse($due), false) : null;
      $isOverdue = $days !== null && $days < 0;
      $daysLate = abs((int)$days);

      if ($type === 'payable') {
          if ($isOverdue) {
              return ['status' => 'Atrasado', 'cycle' => 'Pago', 'due_text' => "{$daysLate} días atrasado", 'tone' => 'danger'];
          }
          if ($days !== null && $days <= 3) {
              return ['status' => 'Urgente', 'cycle' => 'Pago', 'due_text' => $days === 0 ? 'Vence hoy' : ($days === 1 ? '1 día' : "{$days} días"), 'tone' => 'danger'];
          }
          return ['status' => 'Pendiente', 'cycle' => 'Pago', 'due_text' => $days === 0 ? 'Vence hoy' : ($days === 1 ? '1 día' : "{$days} días"), 'tone' => 'warning'];
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
          'due_text' => $days === 0 ? 'Vence hoy' : ($days === 1 ? '1 día' : "{$days} días"),
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
  .acc-dash{
    --bg:#f6f8fc;
    --card:#ffffff;
    --line:#e8edf5;
    --text:#0f172a;
    --muted:#64748b;

    --blue:#2563eb;
    --blue-bg:#eef4ff;
    --blue-br:#cfe0ff;

    --amber:#d97706;
    --amber-bg:#fff7e8;
    --amber-br:#f3dc9f;

    --rose:#e11d48;
    --rose-bg:#fff1f2;
    --rose-br:#fecdd3;

    --red:#ef4444;
    --red-bg:#fff1f2;
    --red-br:#fecaca;

    --yellow:#f59e0b;
    --yellow-bg:#fff9e8;
    --yellow-br:#fde68a;

    --green:#059669;
    --green-bg:#ecfdf5;
    --green-br:#a7f3d0;

    --shadow:0 10px 30px rgba(15,23,42,.06);
    --radius:22px;
  }

  .acc-dash{
    padding: 6px 0 26px;
  }

  .acc-head2{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:16px;
    margin-bottom:18px;
  }

  .acc-title{
    font-size:2rem;
    line-height:1.1;
    font-weight:900;
    color:var(--text);
    letter-spacing:-.03em;
    margin:0;
  }

  .acc-sub{
    margin-top:6px;
    color:var(--muted);
    font-size:1.05rem;
  }

  .acc-actions2,
  .acc-filters2{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
  }

  .acc-btn2,
  .acc-filter-btn{
    border:none;
    background:#fff;
    color:var(--text);
    border:1px solid var(--line);
    border-radius:14px;
    padding:10px 14px;
    font-weight:800;
    text-decoration:none;
    box-shadow:0 4px 14px rgba(15,23,42,.04);
    transition:.2s ease;
  }

  .acc-btn2:hover,
  .acc-filter-btn:hover{
    transform:translateY(-1px);
    color:var(--text);
  }

  .acc-btn2.primary{
    background:linear-gradient(180deg,#2563eb,#1d4ed8);
    color:#fff;
    border-color:#1d4ed8;
  }

  .acc-filtersWrap{
    display:flex;
    justify-content:flex-end;
    margin-bottom:18px;
  }

  .acc-select{
    min-width:220px;
    border:1px solid var(--line);
    background:#fff;
    color:var(--text);
    border-radius:14px;
    padding:10px 14px;
    font-weight:700;
    outline:none;
  }

  .acc-grid-4{
    display:grid;
    grid-template-columns:repeat(4, minmax(0,1fr));
    gap:16px;
    margin-bottom:16px;
  }

  .acc-kpi{
    display:block;
    text-decoration:none;
    border-radius:22px;
    padding:20px 20px 18px;
    border:1px solid var(--line);
    background:#fff;
    box-shadow:var(--shadow);
    transition:.2s ease;
  }

  .acc-kpi:hover{
    transform:translateY(-2px);
  }

  .acc-kpi.top-blue{ background:var(--blue-bg); border-color:var(--blue-br); }
  .acc-kpi.top-amber{ background:var(--amber-bg); border-color:var(--amber-br); }
  .acc-kpi.top-rose{ background:#fff5f7; border-color:#fbcfe8; }
  .acc-kpi.top-red{ background:var(--rose-bg); border-color:var(--rose-br); }

  .acc-kpi-top{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:10px;
    margin-bottom:8px;
  }

  .acc-kpi-icon{
    font-size:1.45rem;
    line-height:1;
  }

  .acc-kpi-label{
    font-size:1.05rem;
    font-weight:800;
    text-align:right;
  }

  .acc-kpi.top-blue .acc-kpi-label,
  .acc-kpi.top-blue .acc-kpi-icon,
  .acc-kpi.top-blue .acc-kpi-value,
  .acc-kpi.top-blue .acc-kpi-sub{ color:var(--blue); }

  .acc-kpi.top-amber .acc-kpi-label,
  .acc-kpi.top-amber .acc-kpi-icon,
  .acc-kpi.top-amber .acc-kpi-value,
  .acc-kpi.top-amber .acc-kpi-sub{ color:var(--amber); }

  .acc-kpi.top-rose .acc-kpi-label,
  .acc-kpi.top-rose .acc-kpi-icon,
  .acc-kpi.top-rose .acc-kpi-value,
  .acc-kpi.top-rose .acc-kpi-sub{ color:#be123c; }

  .acc-kpi.top-red .acc-kpi-label,
  .acc-kpi.top-red .acc-kpi-icon,
  .acc-kpi.top-red .acc-kpi-value,
  .acc-kpi.top-red .acc-kpi-sub{ color:#f43f5e; }

  .acc-kpi-value{
    font-size:2.15rem;
    line-height:1;
    font-weight:900;
    letter-spacing:-.04em;
    margin:10px 0 8px;
  }

  .acc-kpi-sub{
    font-size:1rem;
    opacity:.95;
  }

  .acc-mini{
    background:#fff;
    border:1px solid var(--line);
    border-radius:20px;
    padding:18px 20px;
    box-shadow:var(--shadow);
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:14px;
    text-decoration:none;
  }

  .acc-mini.red{ background:#fff4f5; border-color:#ffd4da; }
  .acc-mini.soft-red{ background:#fff7f7; border-color:#ffdcdc; }
  .acc-mini.yellow{ background:#fffbea; border-color:#fde68a; }
  .acc-mini.green{ background:#effcf6; border-color:#bbf7d0; }

  .acc-mini-l .icon{
    font-size:1.3rem;
    margin-bottom:10px;
    display:block;
  }

  .acc-mini-l .name{
    font-size:1.05rem;
    font-weight:900;
    color:var(--text);
  }

  .acc-mini-l .amount{
    font-size:1rem;
    color:var(--muted);
    margin-top:4px;
  }

  .acc-mini-r{
    font-size:2.1rem;
    line-height:1;
    font-weight:900;
    color:#ef4444;
  }

  .acc-mini.yellow .acc-mini-r{ color:#f59e0b; }
  .acc-mini.green .acc-mini-r{ color:#059669; }
  .acc-mini.red .acc-mini-r,
  .acc-mini.soft-red .acc-mini-r{ color:#e11d48; }

  .acc-analytics{
    display:grid;
    grid-template-columns:minmax(0, 2fr) minmax(320px, 1fr);
    gap:22px;
    margin:30px 0 26px;
  }

  .acc-panel{
    background:#fff;
    border:1px solid var(--line);
    border-radius:22px;
    box-shadow:var(--shadow);
    padding:24px 26px;
  }

  .acc-panel-title{
    font-size:1.2rem;
    font-weight:900;
    color:var(--text);
    margin-bottom:18px;
  }

  .acc-chart-box{
    position:relative;
    width:100%;
    height:320px;
  }

  .acc-aging-list{
    display:flex;
    flex-direction:column;
    gap:14px;
  }

  .acc-aging-item{
    border-radius:18px;
    padding:16px 16px 14px;
    border:1px solid var(--line);
  }

  .acc-aging-item.green{ background:#eefcf3; border-color:#a7f3d0; }
  .acc-aging-item.yellow{ background:#fff9ea; border-color:#fde68a; }
  .acc-aging-item.orange{ background:#fff5eb; border-color:#fdba74; }
  .acc-aging-item.red{ background:#fff5f5; border-color:#fecaca; }
  .acc-aging-item.darkred{ background:#fff1f2; border-color:#fda4af; }

  .acc-aging-top{
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:center;
    margin-bottom:10px;
  }

  .acc-aging-name{
    font-weight:900;
    font-size:1rem;
  }

  .acc-aging-meta{
    display:flex;
    gap:10px;
    align-items:center;
    color:var(--muted);
    font-weight:700;
  }

  .acc-aging-meta strong{
    color:var(--text);
    font-size:1rem;
  }

  .acc-progress{
    width:100%;
    height:8px;
    border-radius:999px;
    background:rgba(100,116,139,.15);
    overflow:hidden;
  }

  .acc-progress > span{
    display:block;
    height:100%;
    border-radius:999px;
  }

  .acc-aging-item.green .acc-progress > span{ background:#22c55e; }
  .acc-aging-item.yellow .acc-progress > span{ background:#fbbf24; }
  .acc-aging-item.orange .acc-progress > span{ background:#fb923c; }
  .acc-aging-item.red .acc-progress > span{ background:#ef4444; }
  .acc-aging-item.darkred .acc-progress > span{ background:#e11d48; }

  .acc-lists{
    display:grid;
    grid-template-columns:minmax(0, 1fr) minmax(0, 1fr);
    gap:24px;
    margin-bottom:26px;
  }

  .acc-list-title{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin:0 0 14px;
  }

  .acc-list-title h3{
    margin:0;
    font-size:1.05rem;
    font-weight:900;
    letter-spacing:.01em;
  }

  .acc-list-title.left h3{ color:#f43f5e; }
  .acc-list-title.right h3{ color:#2563eb; }

  .acc-link-all{
    text-decoration:none;
    color:#2563eb;
    font-weight:800;
  }

  .acc-item{
    display:block;
    text-decoration:none;
    background:#fff;
    border:1px solid var(--line);
    border-left:5px solid transparent;
    border-radius:20px;
    box-shadow:var(--shadow);
    padding:18px 20px 16px;
    margin-bottom:14px;
    transition:.2s ease;
  }

  .acc-item:hover{
    transform:translateY(-1px);
  }

  .acc-item.danger{ border-left-color:#f43f5e; }
  .acc-item.warning{ border-left-color:#fbbf24; }
  .acc-item.info{ border-left-color:#60a5fa; }

  .acc-item-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    margin-bottom:12px;
  }

  .acc-badges{
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
  }

  .acc-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    border-radius:999px;
    padding:5px 12px;
    font-size:.92rem;
    font-weight:800;
    line-height:1;
  }

  .acc-badge.danger{ background:#ffe4e6; color:#dc2626; border:1px solid #fecdd3; }
  .acc-badge.warning{ background:#fef3c7; color:#d97706; border:1px solid #fde68a; }
  .acc-badge.info{ background:#dbeafe; color:#2563eb; border:1px solid #bfdbfe; }
  .acc-badge.gray{ background:#eef2f7; color:#64748b; border:1px solid #e2e8f0; }

  .acc-item-amount{
    text-align:right;
  }

  .acc-item-amount strong{
    display:block;
    font-size:1.2rem;
    line-height:1.1;
    font-weight:900;
    color:var(--text);
  }

  .acc-item-amount span{
    color:var(--muted);
    font-size:.98rem;
  }

  .acc-item-main{
    margin-bottom:14px;
  }

  .acc-item-main .title{
    font-size:1rem;
    font-weight:900;
    color:var(--text);
    margin-bottom:3px;
  }

  .acc-item-main .sub{
    font-size:.98rem;
    color:var(--muted);
  }

  .acc-item-foot{
    padding-top:12px;
    border-top:1px solid #edf1f7;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    color:var(--muted);
    font-weight:700;
  }

  .acc-item-foot .left{
    display:flex;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
  }

  .acc-item-foot .late{
    color:#ef4444;
    font-weight:900;
  }

  .acc-empty{
    background:#fff;
    border:1px solid var(--line);
    border-radius:20px;
    box-shadow:var(--shadow);
    padding:32px 20px;
    text-align:center;
    color:var(--muted);
    font-weight:700;
  }

  .acc-reminders{
    background:#fff;
    border:1px solid var(--line);
    border-radius:24px;
    box-shadow:var(--shadow);
    padding:22px 22px 10px;
  }

  .acc-rem-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:14px;
  }

  .acc-rem-title{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:1.05rem;
    font-weight:900;
    color:var(--text);
  }

  .acc-rem-count{
    min-width:30px;
    height:30px;
    padding:0 10px;
    border-radius:999px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#ffe4e6;
    color:#ef4444;
    font-weight:900;
  }

  .acc-rem-item{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    border-radius:18px;
    padding:16px 18px;
    margin-bottom:12px;
    text-decoration:none;
    background:#fff3f4;
    border:1px solid #ffd7dc;
  }

  .acc-rem-item .l{
    display:flex;
    align-items:center;
    gap:12px;
  }

  .acc-rem-item .ico{
    font-size:1.1rem;
    color:#ef4444;
  }

  .acc-rem-item .tx strong{
    display:block;
    color:var(--text);
    font-size:1rem;
    line-height:1.15;
  }

  .acc-rem-item .tx span{
    color:var(--muted);
    font-weight:700;
  }

  .acc-rem-item .r{
    color:#e11d48;
    font-weight:900;
    white-space:nowrap;
  }

  @media (max-width: 1200px){
    .acc-grid-4,
    .acc-lists{
      grid-template-columns:repeat(2, minmax(0,1fr));
    }
    .acc-analytics{
      grid-template-columns:1fr;
    }
  }

  @media (max-width: 780px){
    .acc-head2,
    .acc-filtersWrap{
      flex-direction:column;
      align-items:stretch;
    }
    .acc-grid-4,
    .acc-lists{
      grid-template-columns:1fr;
    }
    .acc-kpi-value{
      font-size:1.8rem;
    }
    .acc-item-head,
    .acc-item-foot{
      flex-direction:column;
      align-items:flex-start;
    }
    .acc-item-amount{
      text-align:left;
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
        <h3>● PAGOS URGENTES / ATRASADOS</h3>
        <a class="acc-link-all" href="{{ route('accounting.payables.index', $q(['scope'=>'urgent'])) }}">Ver todos →</a>
      </div>

      @if($urgentPayments->count())
        @foreach($urgentPayments->take(4) as $p)
          @php
            $saldo = max((float)$p->amount - (float)$p->amount_paid, 0);
            $meta = $cardMeta('payable', $p);
            $dueDate = optional($p->due_date)->format('d M Y');
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
        <h3>● COBROS VENCIDOS</h3>
        <a class="acc-link-all" href="{{ route('accounting.receivables.index', $q(['scope'=>'overdue'])) }}">Ver todos →</a>
      </div>

      @if($overdueReceivables->count())
        @foreach($overdueReceivables->take(4) as $r)
          @php
            $saldo = max((float)$r->amount - (float)$r->amount_paid, 0);
            $meta = $cardMeta('receivable', $r);
            $dueDate = optional($r->due_date)->format('d M Y');
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
            $dueDate = optional($p->due_date)->format('d M Y');
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
            $dueDate = optional($r->due_date)->format('d M Y');
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
        <span style="color:#2563eb">◌</span>
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
      <div class="acc-empty" style="box-shadow:none;border-style:dashed;">Sin recordatorios pendientes</div>
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
            borderColor: 'rgba(16, 185, 129, 1)',
            backgroundColor: 'rgba(16, 185, 129, 0.12)',
            borderWidth: 3,
            tension: 0.35,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: 'rgba(16, 185, 129, 1)',
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
              color: '#475569',
              font: {
                size: 12,
                weight: '700'
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
              color: '#64748b',
              font: {
                weight: '700'
              }
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(148, 163, 184, 0.15)'
            },
            ticks: {
              color: '#64748b',
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