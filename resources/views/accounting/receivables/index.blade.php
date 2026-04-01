@extends('layouts.app')
@section('title','Cuentas por Cobrar')

@section('content')
@include('accounting.partials.ui')

@php
  use Carbon\Carbon;

  $today = Carbon::today();

  $statusLabels = [
    'pendiente' => 'Pendiente',
    'parcial'   => 'Parcial',
    'cobrado'   => 'Cobrado',
    'vencido'   => 'Vencido',
    'cancelado' => 'Cancelado',
  ];

  $docTypeLabels = [
    'factura'         => 'Facturas',
    'nota_credito'    => 'Notas de Crédito',
    'cargo_adicional' => 'Cargos Adicionales',
    'anticipo'        => 'Anticipos',
  ];

  $collectionLabels = [
    'sin_gestion'  => 'Sin gestión',
    'en_gestion'   => 'En gestión',
    'promesa_pago' => 'Promesa de pago',
    'litigio'      => 'En litigio',
    'incobrable'   => 'Incobrable',
  ];

  $allItems = $items->getCollection();

  $effectiveStatus = function($r) use ($today) {
      if (($r->status ?? null) === 'pendiente' && !empty($r->due_date) && Carbon::parse($r->due_date)->lt($today)) {
          return 'vencido';
      }
      return $r->status ?? 'pendiente';
  };

  $saldo = fn($r) => max((float)($r->amount ?? 0) - (float)($r->amount_paid ?? 0), 0);

  $active = $allItems->filter(fn($r) => !in_array($effectiveStatus($r), ['cobrado', 'cancelado'], true));

  $morosos = $active
      ->filter(fn($r) => !empty($r->due_date) && Carbon::parse($r->due_date)->lt($today))
      ->pluck('client_name')
      ->filter()
      ->unique()
      ->count();

  $totalCobradoMes = $allItems
      ->filter(fn($r) => ($r->status ?? null) === 'cobrado' && !empty($r->payment_date) && Carbon::parse($r->payment_date)->isSameMonth($today))
      ->sum(fn($r) => (float)($r->amount ?? 0));

  $countByType = [];
  foreach (['factura','nota_credito','cargo_adicional','anticipo'] as $dt) {
      $countByType[$dt] = $active->where('document_type', $dt)->count();
  }
  $countByType['all'] = $active->count();

  $agingBuckets = [
      '0-30 días'  => 0.0,
      '31-60 días' => 0.0,
      '61-90 días' => 0.0,
      '90+ días'   => 0.0,
  ];

  foreach ($active as $r) {
      if (empty($r->due_date)) continue;
      $due = Carbon::parse($r->due_date);
      if (!$due->lt($today)) continue;

      $days = $due->diffInDays($today);
      $amt = $saldo($r);

      if ($days <= 30) $agingBuckets['0-30 días'] += $amt;
      elseif ($days <= 60) $agingBuckets['31-60 días'] += $amt;
      elseif ($days <= 90) $agingBuckets['61-90 días'] += $amt;
      else $agingBuckets['90+ días'] += $amt;
  }

  $agingMax = max(array_sum($agingBuckets), 1);

  $topDebtors = $active
      ->groupBy(fn($r) => $r->client_name ?: 'Sin nombre')
      ->map(fn($rows, $name) => [
          'name' => $name,
          'total' => $rows->sum(fn($r) => $saldo($r)),
      ])
      ->sortByDesc('total')
      ->take(6)
      ->values();

  $docTypeTotals = collect(['factura','nota_credito','cargo_adicional','anticipo'])
      ->map(function($type) use ($active, $saldo) {
          $subset = $active->where('document_type', $type);
          return [
              'type' => $type,
              'count' => $subset->count(),
              'total' => $subset->sum(fn($r) => $saldo($r)),
          ];
      });

  $currentDocType = request('document_type', 'all');
  $currentStatus = request('status', 'all');
  $currentCollection = request('collection_status', 'all');
  $currentSort = request('sort', 'due_date');

  $docMenuLinks = [
    'all' => ['label' => 'Todos', 'icon' => 'grid'],
    'factura' => ['label' => 'Facturas', 'icon' => 'file'],
    'nota_credito' => ['label' => 'Notas de Crédito', 'icon' => 'receipt'],
    'cargo_adicional' => ['label' => 'Cargos Adicionales', 'icon' => 'card'],
    'anticipo' => ['label' => 'Anticipos', 'icon' => 'briefcase'],
  ];

  $docRowIcon = function($type) {
      return match($type) {
          'factura' => 'file',
          'nota_credito' => 'receipt',
          'cargo_adicional' => 'card',
          'anticipo' => 'briefcase',
          default => 'dot',
      };
  };
@endphp

<style>
  .rcv-wrap{
    max-width:1380px;
    margin:0 auto;
    padding:8px 0 28px;
  }

  .rcv-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:18px;
    margin-bottom:20px;
  }

  .rcv-title{
    margin:0;
    font-size:2rem;
    line-height:1.08;
    font-weight:900;
    letter-spacing:-.03em;
    color:#0f172a;
  }

  .rcv-sub{
    margin-top:8px;
    color:#64748b;
    font-size:1rem;
  }

  .rcv-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
  }

  .rcv-btn{
    text-decoration:none;
    border:1px solid #dbe3ee;
    background:#fff;
    color:#0f172a;
    border-radius:14px;
    padding:11px 15px;
    font-weight:900;
    box-shadow:0 4px 14px rgba(15,23,42,.04);
    transition:.22s ease;
  }

  .rcv-btn:hover{
    transform:translateY(-2px);
    color:#0f172a;
    border-color:#cbd5e1;
    box-shadow:0 10px 24px rgba(15,23,42,.08);
  }

  .rcv-btn.primary{
    display:inline-flex;
    align-items:center;
    gap:10px;
    background:linear-gradient(180deg,#1e3a8a 0%, #172554 100%);
    border-color:#172554;
    color:#fff;
    padding:12px 18px;
    border-radius:16px;
    box-shadow:
      0 12px 24px rgba(23,37,84,.18),
      inset 0 1px 0 rgba(255,255,255,.08);
  }

  .rcv-btn.primary:hover{
    color:#fff;
    transform:translateY(-2px);
    box-shadow:
      0 16px 30px rgba(23,37,84,.24),
      inset 0 1px 0 rgba(255,255,255,.10);
    filter:saturate(1.04);
  }

  .rcv-btn.primary .btn-icon{
    width:18px;
    height:18px;
    opacity:.95;
  }

  .rcv-alert{
    margin-bottom:16px;
    background:#ecfdf5;
    color:#047857;
    border:1px solid #a7f3d0;
    border-radius:18px;
    padding:14px 16px;
    font-weight:800;
  }

  .rcv-kpis{
    display:grid;
    grid-template-columns:repeat(4, minmax(0,1fr));
    gap:14px;
    margin-bottom:18px;
  }

  .rcv-kpi{
    border-radius:20px;
    padding:18px;
    border:1px solid;
    box-shadow:0 10px 28px rgba(15,23,42,.05);
    transition:.22s ease;
  }

  .rcv-kpi:hover{
    transform:translateY(-2px);
    box-shadow:0 16px 36px rgba(15,23,42,.08);
  }

  .rcv-kpi .icon{
    width:20px;
    height:20px;
    margin-bottom:10px;
    display:block;
  }

  .rcv-kpi .value{
    font-size:2rem;
    line-height:1;
    font-weight:900;
    letter-spacing:-.03em;
    margin-bottom:7px;
  }

  .rcv-kpi .label{
    font-size:.94rem;
    font-weight:800;
  }

  .rcv-kpi.blue{ background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8; }
  .rcv-kpi.rose{ background:#fff1f2; border-color:#fecdd3; color:#be123c; }
  .rcv-kpi.amber{ background:#fffbeb; border-color:#fde68a; color:#b45309; }
  .rcv-kpi.green{ background:#ecfdf5; border-color:#a7f3d0; color:#047857; }

  .rcv-docmenu,
  .rcv-toolbar-card,
  .rcv-side-card,
  .rcv-empty,
  .rcv-card{
    background:#fff;
    border:1px solid #e8edf5;
    border-radius:22px;
    box-shadow:0 10px 28px rgba(15,23,42,.05);
  }

  .rcv-docmenu{
    padding:16px;
    margin-bottom:18px;
  }

  .rcv-pills,
  .rcv-subpills{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
  }

  .rcv-pills{
    margin-bottom:12px;
  }

  .rcv-sep{
    height:1px;
    background:#eef2f7;
    margin:12px 0;
  }

  .rcv-pill,
  .rcv-subpill{
    display:inline-flex;
    align-items:center;
    gap:9px;
    text-decoration:none;
    border-radius:999px;
    border:1px solid #dbe3ee;
    background:#fff;
    color:#64748b;
    font-weight:800;
    transition:.22s ease;
    position:relative;
    overflow:hidden;
  }

  .rcv-pill::before,
  .rcv-subpill::before{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(135deg, rgba(37,99,235,.08), rgba(15,23,42,.02));
    opacity:0;
    transition:.22s ease;
  }

  .rcv-pill > *,
  .rcv-subpill > *{
    position:relative;
    z-index:1;
  }

  .rcv-pill{
    padding:11px 15px;
    font-size:.92rem;
  }

  .rcv-subpill{
    padding:8px 12px;
    font-size:.82rem;
  }

  .rcv-pill:hover,
  .rcv-subpill:hover{
    transform:translateY(-2px);
    border-color:#cbd5e1;
    color:#0f172a;
    box-shadow:0 8px 20px rgba(15,23,42,.07);
  }

  .rcv-pill:hover::before,
  .rcv-subpill:hover::before{
    opacity:1;
  }

  .rcv-pill.active{
    background:#0f172a;
    color:#fff;
    border-color:#0f172a;
    box-shadow:0 10px 24px rgba(15,23,42,.14);
  }

  .rcv-subpill.active{
    background:linear-gradient(180deg,#2563eb,#1d4ed8);
    color:#fff;
    border-color:#2563eb;
    box-shadow:0 10px 22px rgba(37,99,235,.18);
  }

  .rcv-pill-icon,
  .rcv-chip-icon,
  .rcv-inline-icon{
    width:16px;
    height:16px;
    flex-shrink:0;
  }

  .rcv-pill-count{
    min-width:22px;
    height:22px;
    padding:0 7px;
    border-radius:999px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    background:rgba(255,255,255,.18);
    font-size:.75rem;
    font-weight:900;
  }

  .rcv-pill:not(.active) .rcv-pill-count{
    background:#f1f5f9;
    color:#475569;
  }

  .rcv-main{
    display:grid;
    grid-template-columns:minmax(0, 2fr) minmax(320px, 1fr);
    gap:20px;
    align-items:start;
  }

  /* ===== Toolbar sin card / limpio ===== */
.rcv-toolbar-card{
  background: transparent;
  border: 0;
  box-shadow: none;
  border-radius: 0;
  padding: 0;
  margin: 0 0 10px 0;
}

/* fila principal: buscador + ordenar */
.rcv-toolbar{
  display: grid;
  grid-template-columns: minmax(0, 1fr) 245px;
  gap: 16px;
  align-items: center;
}

/* segunda fila */
.rcv-toolbar-bottom{
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 14px;
  margin-top: 12px;
}

/* ocultar limpiar filtros */
.rcv-clear,
.rcv-toolbar-bottom > div:last-child{
  display: none !important;
}

/* si solo queda company, que no se estire raro */
.rcv-toolbar-bottom .rcv-select-wrap{
  width: 280px;
  max-width: 100%;
}

/* envolturas */
.rcv-search,
.rcv-select-wrap{
  position: relative;
  width: 100%;
}

/* estilos base */
.rcv-search input,
.rcv-select,
.rcv-company{
  width: 100%;
  height: 46px;
  border: 1px solid #d9dee7;
  background: #ffffff;
  border-radius: 15px;
  outline: none;
  color: #0f172a;
  font-size: 15px;
  font-weight: 500;
  transition:
    border-color .22s ease,
    box-shadow .22s ease,
    background-color .22s ease,
    transform .22s ease;
  box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
}

/* buscador */
.rcv-search input{
  padding: 0 16px 0 46px;
}

.rcv-search input::placeholder{
  color: #64748b;
  font-weight: 500;
}

.rcv-search .icon{
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  width: 18px;
  height: 18px;
  color: #7c8798;
  pointer-events: none;
}

.rcv-search::after{
  content: "";
  position: absolute;
  left: 38px;
  top: 50%;
  transform: translateY(-50%);
  width: 1px;
  height: 18px;
  background: #e7ebf2;
  pointer-events: none;
}

/* selects */
.rcv-select,
.rcv-company{
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  padding: 0 42px 0 16px;
  cursor: pointer;
  background-image: none;
}

.rcv-select-wrap .caret{
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  color: #7c8798;
  pointer-events: none;
}

/* hover */
.rcv-search input:hover,
.rcv-select:hover,
.rcv-company:hover{
  border-color: #c9d2df;
  background: #fff;
}

/* focus */
.rcv-search input:focus,
.rcv-select:focus,
.rcv-company:focus{
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
  background: #fff;
}

/* resultados */
.rcv-toolbar-meta{
  display: flex;
  align-items: center;
  justify-content: flex-start;
  margin-top: 10px;
}

.rcv-results{
  margin: 0;
  font-size: .82rem;
  line-height: 1.2;
  color: #64748b;
  font-weight: 500;
}

/* responsive */
@media (max-width: 860px){
  .rcv-toolbar{
    grid-template-columns: 1fr;
  }

  .rcv-toolbar-bottom{
    margin-top: 10px;
  }

  .rcv-toolbar-bottom .rcv-select-wrap{
    width: 100%;
  }

  .rcv-search input,
  .rcv-select,
  .rcv-company{
    height: 44px;
    font-size: 14px;
  }
}

  .rcv-field:focus,
  .rcv-search input:focus,
  .rcv-select:focus,
  .rcv-company:focus{
    border-color:#c7d2fe;
    background:#ffffff;
    box-shadow:
      0 0 0 4px rgba(37,99,235,.08),
      0 10px 25px rgba(37,99,235,.08);
  }

  .rcv-search .icon{
    position:absolute;
    left:16px;
    top:50%;
    transform:translateY(-50%);
    color:#64748b;
    width:18px;
    height:18px;
    pointer-events:none;
  }

  .rcv-search::after{
    content:"";
    position:absolute;
    left:42px;
    top:50%;
    transform:translateY(-50%);
    width:1px;
    height:20px;
    background:#e2e8f0;
    pointer-events:none;
  }

  .rcv-select-wrap{
    position:relative;
  }

  .rcv-select-wrap .caret{
    position:absolute;
    right:16px;
    top:50%;
    transform:translateY(-50%);
    width:16px;
    height:16px;
    color:#64748b;
    pointer-events:none;
  }

  .rcv-toolbar-meta{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-top:12px;
    padding-top:12px;
    border-top:1px solid #eef2f7;
  }

  .rcv-results{
    font-size:.84rem;
    color:#64748b;
    margin:0;
    font-weight:700;
  }

  .rcv-clear{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    height:42px;
    padding:0 14px;
    border-radius:14px;
    text-decoration:none;
    border:1px solid #e2e8f0;
    background:#fff;
    color:#334155;
    font-size:.88rem;
    font-weight:800;
    transition:.2s ease;
    white-space:nowrap;
  }

  .rcv-clear:hover{
    color:#0f172a;
    border-color:#cbd5e1;
    transform:translateY(-1px);
    box-shadow:0 8px 18px rgba(15,23,42,.06);
  }

  .rcv-list{
    display:flex;
    flex-direction:column;
    gap:12px;
  }

  .rcv-card{
    padding:18px;
    text-decoration:none;
    color:inherit;
    transition:.22s ease;
  }

  .rcv-card:hover{
    transform:translateY(-2px);
    box-shadow:0 18px 34px rgba(15,23,42,.09);
    border-color:#dbe3ee;
  }

  .rcv-card-top{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:14px;
    margin-bottom:12px;
  }

  .rcv-client{
    font-size:1.02rem;
    font-weight:900;
    color:#0f172a;
    margin-bottom:4px;
  }

  .rcv-meta{
    color:#64748b;
    font-size:.9rem;
  }

  .rcv-right{
    text-align:right;
  }

  .rcv-amount{
    font-size:1.08rem;
    font-weight:900;
    color:#0f172a;
  }

  .rcv-amount-sub{
    font-size:.84rem;
    color:#64748b;
    margin-top:4px;
  }

  .rcv-badges{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin-bottom:12px;
  }

  .rcv-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    border-radius:999px;
    padding:6px 11px;
    font-size:.8rem;
    font-weight:900;
    border:1px solid;
  }

  .rcv-badge.gray{ background:#f8fafc; color:#475569; border-color:#e2e8f0; }
  .rcv-badge.blue{ background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
  .rcv-badge.amber{ background:#fffbeb; color:#b45309; border-color:#fde68a; }
  .rcv-badge.rose{ background:#fff1f2; color:#be123c; border-color:#fecdd3; }
  .rcv-badge.green{ background:#ecfdf5; color:#047857; border-color:#a7f3d0; }
  .rcv-badge.slate{ background:#f8fafc; color:#334155; border-color:#e2e8f0; }

  .rcv-card-foot{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding-top:12px;
    border-top:1px solid #eef2f7;
  }

  .rcv-card-foot-left{
    display:flex;
    flex-wrap:wrap;
    gap:14px;
    color:#64748b;
    font-size:.88rem;
    font-weight:700;
  }

  .rcv-card-actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
  }

  .rcv-mini-btn{
    text-decoration:none;
    border:1px solid #dbe3ee;
    background:#fff;
    color:#0f172a;
    border-radius:12px;
    padding:8px 12px;
    font-size:.84rem;
    font-weight:900;
    transition:.2s ease;
  }

  .rcv-mini-btn:hover{
    transform:translateY(-1px);
    border-color:#cbd5e1;
    box-shadow:0 8px 18px rgba(15,23,42,.07);
    color:#0f172a;
  }

  .rcv-side-card{
    padding:18px;
    transition:.2s ease;
  }

  .rcv-side-card:hover{
    box-shadow:0 16px 34px rgba(15,23,42,.08);
  }

  .rcv-side-card + .rcv-side-card{
    margin-top:14px;
  }

  .rcv-side-title{
    font-size:1rem;
    font-weight:900;
    color:#0f172a;
    margin-bottom:14px;
  }

  .rcv-aging-list{
    display:flex;
    flex-direction:column;
    gap:10px;
  }

  .rcv-aging-item{
    border:1px solid #e8edf5;
    border-radius:16px;
    padding:12px 13px;
    background:#fafcff;
    transition:.2s ease;
  }

  .rcv-aging-item:hover{
    border-color:#dbe3ee;
    transform:translateY(-1px);
  }

  .rcv-aging-top{
    display:flex;
    justify-content:space-between;
    gap:10px;
    margin-bottom:8px;
    font-size:.88rem;
    font-weight:900;
    color:#334155;
  }

  .rcv-progress{
    height:8px;
    background:#e2e8f0;
    border-radius:999px;
    overflow:hidden;
  }

  .rcv-progress > span{
    display:block;
    height:100%;
    border-radius:999px;
  }

  .rcv-progress.blue > span{ background:#3b82f6; }
  .rcv-progress.amber > span{ background:#f59e0b; }
  .rcv-progress.orange > span{ background:#f97316; }
  .rcv-progress.rose > span{ background:#e11d48; }

  .rcv-topdebtor{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:.9rem;
    padding:8px 0;
  }

  .rcv-rank{
    width:24px;
    height:24px;
    border-radius:999px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:.75rem;
    font-weight:900;
    flex-shrink:0;
  }

  .rcv-rank.one{ background:#ffe4e6; color:#be123c; }
  .rcv-rank.two{ background:#ffedd5; color:#c2410c; }
  .rcv-rank.other{ background:#f1f5f9; color:#475569; }

  .rcv-topdebtor-name{
    flex:1;
    min-width:0;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    color:#64748b;
  }

  .rcv-topdebtor-total{
    font-weight:900;
    color:#0f172a;
    white-space:nowrap;
  }

  .rcv-docrow{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:.9rem;
    padding:6px 0;
  }

  .rcv-docrow-name{
    flex:1;
    color:#64748b;
  }

  .rcv-docrow-total{
    font-weight:900;
    color:#0f172a;
    white-space:nowrap;
  }

  .rcv-docrow-count{
    color:#94a3b8;
    font-size:.78rem;
    min-width:30px;
    text-align:right;
  }

  .rcv-empty{
    padding:48px 20px;
    text-align:center;
    color:#64748b;
  }

  .rcv-empty-ico{
    width:42px;
    height:42px;
    opacity:.35;
    margin:0 auto 10px;
    display:block;
  }

  .rcv-pagination{
    margin-top:16px;
  }

  .rcv-svg{
    width:100%;
    height:100%;
    display:block;
  }

  @media (max-width: 1100px){
    .rcv-main{
      grid-template-columns:1fr;
    }
  }

  @media (max-width: 860px){
    .rcv-kpis{
      grid-template-columns:repeat(2, minmax(0,1fr));
    }

    .rcv-head{
      flex-direction:column;
      align-items:stretch;
    }

    .rcv-toolbar{
      grid-template-columns:1fr;
    }

    .rcv-toolbar-bottom{
      grid-template-columns:1fr;
    }

    .rcv-toolbar-meta{
      flex-direction:column;
      align-items:flex-start;
    }

    .rcv-card-top,
    .rcv-card-foot{
      flex-direction:column;
      align-items:flex-start;
    }

    .rcv-right{
      text-align:left;
    }
  }

  @media (max-width: 560px){
    .rcv-kpis{
      grid-template-columns:1fr;
    }

    .rcv-search input,
    .rcv-select,
    .rcv-company{
      height:50px;
      border-radius:16px;
      font-size:.96rem;
    }
  }
</style>

@php
  function rcvIcon($name) {
      $icons = [
          'plus' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" class="rcv-svg"><path d="M12 5v14"/><path d="M5 12h14"/></svg>',
          'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="rcv-svg"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
          'grid' => '<svg viewBox="0 0 24 24" fill="currentColor" class="rcv-svg"><rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="8" rx="2"/><rect x="3" y="13" width="8" height="8" rx="2"/><rect x="13" y="13" width="8" height="8" rx="2"/></svg>',
          'file' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="rcv-svg"><path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7z"/><path d="M14 2v5h5"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>',
          'receipt' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="rcv-svg"><path d="M6 3h12v18l-2-1.5L14 21l-2-1.5L10 21l-2-1.5L6 21z"/><path d="M9 8h6"/><path d="M9 12h6"/><path d="M9 16h4"/></svg>',
          'card' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="rcv-svg"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M2 10h20"/><path d="M6 15h3"/></svg>',
          'briefcase' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="rcv-svg"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M3 12h18"/></svg>',
          'trend' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" class="rcv-svg"><path d="M3 17l6-6 4 4 7-8"/><path d="M14 7h6v6"/></svg>',
          'alert' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" class="rcv-svg"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.8 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.8a2 2 0 0 0-3.4 0z"/></svg>',
          'clock' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" class="rcv-svg"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
          'check' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" class="rcv-svg"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 5-5"/></svg>',
          'calendar' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="rcv-svg"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4"/><path d="M8 3v4"/><path d="M3 10h18"/></svg>',
          'money' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="rcv-svg"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6"/></svg>',
          'chevron-down' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="rcv-svg"><path d="m6 9 6 6 6-6"/></svg>',
          'dot' => '<svg viewBox="0 0 24 24" fill="currentColor" class="rcv-svg"><circle cx="12" cy="12" r="4"/></svg>',
      ];
      return $icons[$name] ?? $icons['dot'];
  }
@endphp

<div class="rcv-wrap">
  <div class="rcv-head">
    <div>
      <h1 class="rcv-title">Cuentas por Cobrar</h1>
      <div class="rcv-sub">{{ $items->total() }} registros · {{ $morosos }} clientes con saldo vencido</div>
    </div>

    
  </div>

  @if(session('success'))
    <div class="rcv-alert">{{ session('success') }}</div>
  @endif

  <div class="rcv-kpis">
    <div class="rcv-kpi blue">
      <span class="icon">{!! rcvIcon('trend') !!}</span>
      <div class="value">${{ number_format($totPending ?? 0, 0) }}</div>
      <div class="label">Cartera Total</div>
    </div>

    <div class="rcv-kpi rose">
      <span class="icon">{!! rcvIcon('alert') !!}</span>
      <div class="value">${{ number_format($totOverdue ?? 0, 0) }}</div>
      <div class="label">Cartera Vencida</div>
    </div>

    <div class="rcv-kpi amber">
      <span class="icon">{!! rcvIcon('clock') !!}</span>
      <div class="value">{{ $morosos }}</div>
      <div class="label">Clientes Morosos</div>
    </div>

    <div class="rcv-kpi green">
      <span class="icon">{!! rcvIcon('check') !!}</span>
      <div class="value">${{ number_format($totalCobradoMes ?? 0, 0) }}</div>
      <div class="label">Cobrado este mes</div>
    </div>
  </div>

  <div class="rcv-docmenu">
    <div class="rcv-pills">
      @foreach($docMenuLinks as $key => $meta)
        @php
          $qs = request()->query();
          $qs['document_type'] = $key;
          if ($key === 'all') unset($qs['document_type']);
          $activeDoc = ($currentDocType === $key) || ($key === 'all' && !request('document_type'));
        @endphp
        <a href="{{ route('accounting.receivables.index', $qs) }}" class="rcv-pill {{ $activeDoc ? 'active' : '' }}">
          <span class="rcv-pill-icon">{!! rcvIcon($meta['icon']) !!}</span>
          <span>{{ $meta['label'] }}</span>
          @if(($countByType[$key] ?? 0) > 0)
            <span class="rcv-pill-count">{{ $countByType[$key] }}</span>
          @endif
        </a>
      @endforeach
    </div>

    <div class="rcv-sep"></div>

    <div class="rcv-subpills" style="margin-bottom:10px">
      <span style="font-size:.8rem;color:#64748b;font-weight:800;align-self:center;">Filtrar:</span>
      @foreach(['all'=>'Todos los estados','pendiente'=>'Pendiente','parcial'=>'Parcial','vencido'=>'Vencido','cobrado'=>'Cobrado','cancelado'=>'Cancelado'] as $key => $label)
        @php
          $qs = request()->query();
          $qs['status'] = $key;
          if ($key === 'all') unset($qs['status']);
          $activeSt = ($currentStatus === $key) || ($key === 'all' && !request('status'));
        @endphp
        <a href="{{ route('accounting.receivables.index', $qs) }}" class="rcv-subpill {{ $activeSt ? 'active' : '' }}">
          {{ $label }}
        </a>
      @endforeach
    </div>

    <div class="rcv-subpills">
      <span style="font-size:.8rem;color:#64748b;font-weight:800;align-self:center;">Cobranza:</span>
      @foreach(['all'=>'Toda la cartera','sin_gestion'=>'Sin gestión','en_gestion'=>'En gestión','promesa_pago'=>'Promesa de pago','litigio'=>'En litigio','incobrable'=>'Incobrable'] as $key => $label)
        @php
          $qs = request()->query();
          $qs['collection_status'] = $key;
          if ($key === 'all') unset($qs['collection_status']);
          $activeCol = ($currentCollection === $key) || ($key === 'all' && !request('collection_status'));
        @endphp
        <a href="{{ route('accounting.receivables.index', $qs) }}" class="rcv-subpill {{ $activeCol ? 'active' : '' }}">
          {{ $label }}
        </a>
      @endforeach
    </div>
  </div>

  <div class="rcv-main">
    <div>
      <form method="GET" action="{{ route('accounting.receivables.index') }}" class="rcv-toolbar-card" id="rcvFiltersForm" autocomplete="off">
        <input type="hidden" name="document_type" value="{{ request('document_type') }}">
        <input type="hidden" name="collection_status" value="{{ request('collection_status') }}">
        <input type="hidden" name="status" value="{{ request('status') }}">

        <div class="rcv-toolbar">
          <div class="rcv-search">
            <span class="icon">{!! rcvIcon('search') !!}</span>
            <input
              type="text"
              name="search"
              id="rcvSearchInput"
              value="{{ request('search') }}"
              placeholder="Buscar cliente, folio, concepto..."
            >
          </div>

          <div class="rcv-select-wrap">
            <select name="sort" class="rcv-select rcv-auto-submit">
              <option value="due_date" @selected($currentSort === 'due_date')>Ordenar: Vencimiento</option>
              <option value="amount" @selected($currentSort === 'amount')>Ordenar: Monto</option>
              <option value="client" @selected($currentSort === 'client')>Ordenar: Cliente</option>
            </select>
            <span class="caret">{!! rcvIcon('chevron-down') !!}</span>
          </div>
        </div>

        <div class="rcv-toolbar-bottom">
          <div class="rcv-select-wrap">
            <select name="company_id" class="rcv-company rcv-auto-submit">
              <option value="">Todas las compañías</option>
              @foreach($companies as $c)
                <option value="{{ $c->id }}" @selected(request('company_id')==$c->id)>{{ $c->name }}</option>
              @endforeach
            </select>
            <span class="caret">{!! rcvIcon('chevron-down') !!}</span>
          </div>

          <div style="display:flex;justify-content:flex-end;">
            <a class="rcv-clear" href="{{ route('accounting.receivables.index') }}">Limpiar filtros</a>
          </div>
        </div>

        <div class="rcv-toolbar-meta">
          <div class="rcv-results">{{ $items->count() }} resultado(s) en esta página</div>
        </div>
      </form>

      @if($items->count() === 0)
        <div class="rcv-empty">
          <span class="rcv-empty-ico">{!! rcvIcon('file') !!}</span>
          <div style="font-weight:900;margin-bottom:6px">No hay cuentas por cobrar</div>
          <div style="font-size:.92rem">
            {{ request()->hasAny(['search','company_id','status','document_type','collection_status']) ? 'No se encontraron resultados con los filtros actuales.' : 'Crea tu primera cuenta por cobrar para comenzar.' }}
          </div>
        </div>
      @else
        <div class="rcv-list">
          @foreach($items as $r)
            @php
              $rowSaldo = $saldo($r);
              $rowStatus = $effectiveStatus($r);
              $rowDue = !empty($r->due_date) ? Carbon::parse($r->due_date) : null;
              $daysLate = ($rowDue && $rowDue->lt($today)) ? $rowDue->diffInDays($today) : 0;

              $statusClass = match($rowStatus) {
                'cobrado' => 'green',
                'vencido' => 'rose',
                'parcial' => 'blue',
                'cancelado' => 'slate',
                default => 'amber',
              };

              $collectionStatus = $r->collection_status ?? null;
            @endphp

            <div class="rcv-card">
              <div class="rcv-card-top">
                <div>
                  <div class="rcv-client">{{ $r->client_name }}</div>
                  <div class="rcv-meta">
                    {{ $r->company?->name ?? '—' }}
                    · {{ $r->folio ?: 'Sin folio' }}
                    @if(!empty($r->description))
                      · {{ \Illuminate\Support\Str::limit($r->description, 58) }}
                    @endif
                  </div>
                </div>

                <div class="rcv-right">
                  <div class="rcv-amount">${{ number_format($rowSaldo, 2) }}</div>
                  <div class="rcv-amount-sub">Saldo · Total ${{ number_format((float)$r->amount, 2) }}</div>
                </div>
              </div>

              <div class="rcv-badges">
                <span class="rcv-badge {{ $statusClass }}">{{ $statusLabels[$rowStatus] ?? ucfirst($rowStatus) }}</span>

                <span class="rcv-badge gray">
                  {{ $docTypeLabels[$r->document_type ?? 'factura'] ?? ucfirst(str_replace('_',' ', $r->document_type ?? 'factura')) }}
                </span>

                @if($collectionStatus)
                  <span class="rcv-badge blue">
                    {{ $collectionLabels[$collectionStatus] ?? ucfirst(str_replace('_',' ', $collectionStatus)) }}
                  </span>
                @endif

                @if(!empty($r->priority))
                  <span class="rcv-badge amber">Prioridad {{ ucfirst($r->priority) }}</span>
                @endif
              </div>

              <div class="rcv-card-foot">
                <div class="rcv-card-foot-left">
                  <span style="display:inline-flex;align-items:center;gap:6px;">
                    <span class="rcv-inline-icon">{!! rcvIcon('calendar') !!}</span>
                    <span>Vence: {{ $rowDue ? $rowDue->format('d/m/Y') : '—' }}</span>
                  </span>

                  <span style="display:inline-flex;align-items:center;gap:6px;">
                    <span class="rcv-inline-icon">{!! rcvIcon('money') !!}</span>
                    <span>Cobrado: ${{ number_format((float)$r->amount_paid, 2) }}</span>
                  </span>

                  @if($rowStatus === 'vencido')
                    <span style="color:#e11d48;font-weight:900">{{ $daysLate }} día(s) vencido</span>
                  @endif
                </div>

                <div class="rcv-card-actions">
                  <a class="rcv-mini-btn" href="{{ route('accounting.receivables.show',$r) }}">Ver</a>
                  <a class="rcv-mini-btn" href="{{ route('accounting.receivables.edit',$r) }}">Editar</a>
                </div>
              </div>
            </div>
          @endforeach
        </div>

        <div class="rcv-pagination">
          {{ $items->links() }}
        </div>
      @endif
    </div>

    <div>
      <div class="rcv-side-card">
        <div class="rcv-side-title">Antigüedad de Cartera (Aging)</div>
        <div class="rcv-aging-list">
          @php
            $agingColors = [
              '0-30 días' => 'blue',
              '31-60 días' => 'amber',
              '61-90 días' => 'orange',
              '90+ días' => 'rose',
            ];
          @endphp

          @foreach($agingBuckets as $label => $value)
            @php
              $pct = $agingMax > 0 ? ($value / $agingMax) * 100 : 0;
            @endphp
            <div class="rcv-aging-item">
              <div class="rcv-aging-top">
                <span>{{ $label }}</span>
                <span>${{ number_format($value, 0) }}</span>
              </div>
              <div class="rcv-progress {{ $agingColors[$label] ?? 'blue' }}">
                <span style="width: {{ max($pct, $value > 0 ? 6 : 0) }}%"></span>
              </div>
            </div>
          @endforeach
        </div>
      </div>

      <div class="rcv-side-card">
        <div class="rcv-side-title">Top Deudores</div>
        <div style="display:flex;flex-direction:column;gap:10px;">
          @forelse($topDebtors as $i => $debtor)
            <div class="rcv-topdebtor">
              <span class="rcv-rank {{ $i === 0 ? 'one' : ($i === 1 ? 'two' : 'other') }}">{{ $i + 1 }}</span>
              <span class="rcv-topdebtor-name">{{ $debtor['name'] }}</span>
              <span class="rcv-topdebtor-total">${{ number_format($debtor['total'], 0) }}</span>
            </div>
          @empty
            <div style="font-size:.88rem;color:#64748b;">Sin saldos pendientes</div>
          @endforelse
        </div>
      </div>

      <div class="rcv-side-card">
        <div class="rcv-side-title">Por tipo de documento</div>
        <div style="display:flex;flex-direction:column;gap:10px;">
          @foreach($docTypeTotals as $row)
            <div class="rcv-docrow">
              <span class="rcv-inline-icon">{!! rcvIcon($docRowIcon($row['type'])) !!}</span>
              <span class="rcv-docrow-name">{{ $docTypeLabels[$row['type']] ?? ucfirst(str_replace('_',' ', $row['type'])) }}</span>
              <span class="rcv-docrow-count">{{ $row['count'] }}</span>
              <span class="rcv-docrow-total">${{ number_format($row['total'], 0) }}</span>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('rcvFiltersForm');
  if (!form) return;

  const searchInput = document.getElementById('rcvSearchInput');
  const autoSubmitFields = form.querySelectorAll('.rcv-auto-submit');

  let debounceTimer = null;

  const submitForm = () => {
    if (!form) return;
    form.submit();
  };

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(submitForm, 420);
    });

    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        clearTimeout(debounceTimer);
        submitForm();
      }
    });
  }

  autoSubmitFields.forEach(function(field) {
    field.addEventListener('change', submitForm);
  });
});
</script>
@endsection