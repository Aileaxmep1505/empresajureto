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
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  :root {
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
  }

  /* TIPOGRAFÍA Y RESET GENERAL */
  .rcv-wrap {
    font-family: 'Quicksand', sans-serif;
    font-weight: 500;
    max-width: 1380px;
    margin: 0 auto;
    padding: 24px 0 40px;
    color: var(--text-main);
    background-color: var(--bg);
  }

  * {
    box-sizing: border-box;
  }

  /* HEADERS */
  .rcv-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 24px;
  }

  .rcv-title {
    margin: 0;
    font-size: 2.2rem;
    line-height: 1.1;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--ink);
  }

  .rcv-sub {
    margin-top: 6px;
    color: var(--muted);
    font-size: 1rem;
    font-weight: 500;
  }

  /* BOTONES */
  .rcv-btn {
    text-decoration: none;
    font-family: 'Quicksand', sans-serif;
    font-weight: 600;
    transition: all 0.2s ease;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
  }
  
  .rcv-btn:active, .rcv-mini-btn:active {
    transform: scale(0.98);
  }

  .rcv-btn.primary {
    background-color: var(--blue);
    color: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 12px 24px;
    font-size: 1rem;
    box-shadow: 0 4px 12px rgba(0, 122, 255, 0.15);
  }

  .rcv-btn.primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 122, 255, 0.2);
  }

  .rcv-mini-btn {
    text-decoration: none;
    background-color: transparent;
    color: var(--text-main);
    border: 1px solid var(--line);
    border-radius: 8px;
    padding: 8px 16px;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.2s ease;
  }

  .rcv-mini-btn:hover {
    background-color: var(--bg);
    transform: translateY(-1px);
    color: var(--ink);
  }

  .rcv-mini-btn.outline {
    background-color: var(--card);
    color: var(--blue);
    border-color: var(--blue);
  }

  .rcv-mini-btn.outline:hover {
    background-color: var(--blue-soft);
  }

  /* ALERTAS */
  .rcv-alert {
    margin-bottom: 24px;
    background: var(--success-soft);
    color: var(--success);
    border-radius: 12px;
    padding: 16px 24px;
    font-weight: 600;
    border: 1px solid rgba(21, 128, 61, 0.2);
  }

  /* TARJETAS (Cards) Y ESTRUCTURA BASE */
  .rcv-card, .rcv-side-card, .rcv-docmenu, .rcv-kpi {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    transition: all 0.25s ease;
  }

  .rcv-card {
    padding: 24px;
    text-decoration: none;
    display: block;
    color: inherit;
  }

  .rcv-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.04);
  }

  .rcv-side-card {
    padding: 24px;
    margin-bottom: 16px;
  }

  /* KPIs */
  .rcv-kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 24px;
  }

  .rcv-kpi {
    padding: 24px;
  }

  .rcv-kpi .icon {
    width: 24px;
    height: 24px;
    margin-bottom: 16px;
    display: block;
  }

  .rcv-kpi .value {
    font-size: 2rem;
    line-height: 1;
    font-weight: 700;
    color: var(--ink);
    margin-bottom: 8px;
    letter-spacing: -0.02em;
  }

  .rcv-kpi .label {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--muted);
  }

  /* Colores de Iconos en KPI (Minimalista) */
  .rcv-kpi.blue .icon { color: var(--blue); }
  .rcv-kpi.rose .icon { color: var(--danger); }
  .rcv-kpi.amber .icon { color: var(--warning); }
  .rcv-kpi.green .icon { color: var(--success); }

  /* NAVEGACIÓN Y PESTAÑAS (Pills) */
  .rcv-docmenu {
    padding: 24px;
    margin-bottom: 24px;
  }

  .rcv-pills, .rcv-subpills {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
  }
  
  .rcv-pills {
    margin-bottom: 16px;
  }

  .rcv-sep {
    height: 1px;
    background: var(--line);
    margin: 16px 0;
  }

  .rcv-pill, .rcv-subpill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    border-radius: 999px;
    background: transparent;
    color: var(--muted);
    font-weight: 600;
    transition: all 0.2s ease;
    border: 1px solid transparent;
  }

  .rcv-pill {
    padding: 10px 18px;
    font-size: 0.95rem;
  }

  .rcv-subpill {
    padding: 8px 16px;
    font-size: 0.85rem;
  }

  .rcv-pill:hover, .rcv-subpill:hover {
    background: var(--bg);
    color: var(--text-main);
  }
  
  .rcv-pill:active, .rcv-subpill:active {
    transform: scale(0.98);
  }

  .rcv-pill.active, .rcv-subpill.active {
    background: var(--blue);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 122, 255, 0.15);
  }

  .rcv-pill-icon, .rcv-chip-icon, .rcv-inline-icon {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
  }

  .rcv-pill-count {
    min-width: 24px;
    height: 24px;
    padding: 0 8px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    font-size: 0.75rem;
    font-weight: 700;
    margin-left: 4px;
  }

  .rcv-pill:not(.active) .rcv-pill-count {
    background: var(--line);
    color: var(--muted);
  }

  /* LAYOUT PRINCIPAL */
  .rcv-main {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
    gap: 24px;
    align-items: start;
  }

  /* TOOLBAR (Filtros y Búsqueda) */
  .rcv-toolbar-card {
    background: transparent;
    border: none;
    box-shadow: none;
    margin-bottom: 24px;
  }

  .rcv-toolbar {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 240px;
    gap: 16px;
    align-items: center;
  }

  .rcv-toolbar-bottom {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 16px;
    margin-top: 16px;
  }

  .rcv-search, .rcv-select-wrap {
    position: relative;
    width: 100%;
  }

  .rcv-search input, .rcv-select, .rcv-company {
    width: 100%;
    height: 48px;
    border: 1px solid var(--line);
    background: var(--card);
    border-radius: 8px;
    outline: none;
    color: var(--ink);
    font-size: 1rem;
    font-family: 'Quicksand', sans-serif;
    font-weight: 600;
    transition: all 0.2s ease;
  }

  .rcv-search input {
    padding: 0 16px 0 48px;
  }

  .rcv-search input::placeholder {
    color: var(--muted);
    font-weight: 500;
  }

  .rcv-search .icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    color: var(--muted);
    pointer-events: none;
  }

  .rcv-select, .rcv-company {
    appearance: none;
    padding: 0 42px 0 16px;
    cursor: pointer;
  }

  .rcv-select-wrap .caret {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: var(--muted);
    pointer-events: none;
  }

  /* Focus en Inputs */
  .rcv-search input:focus, .rcv-select:focus, .rcv-company:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  /* Clear Filters */
  .rcv-clear {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 48px;
    padding: 0 20px;
    border-radius: 8px;
    text-decoration: none;
    background: transparent;
    color: var(--muted);
    font-size: 0.95rem;
    font-weight: 600;
    transition: all 0.2s ease;
  }

  .rcv-clear:hover {
    background: var(--card);
    color: var(--text-main);
  }

  .rcv-toolbar-meta {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    margin-top: 16px;
  }

  .rcv-results {
    font-size: 0.9rem;
    color: var(--muted);
    font-weight: 600;
  }

  /* LISTADO Y TARJETAS DE CONTENIDO */
  .rcv-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .rcv-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
  }

  .rcv-client {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--ink);
    margin-bottom: 6px;
    letter-spacing: -0.01em;
  }

  .rcv-meta {
    color: var(--muted);
    font-size: 0.95rem;
  }

  .rcv-right {
    text-align: right;
  }

  .rcv-amount {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--ink);
    letter-spacing: -0.02em;
  }

  .rcv-amount-sub {
    font-size: 0.85rem;
    color: var(--muted);
    margin-top: 6px;
  }

  /* BADGES (Etiquetas Minimalistas Pastel) */
  .rcv-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
  }

  .rcv-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 0.85rem;
    font-weight: 700;
  }

  .rcv-badge.green  { background: var(--success-soft); color: var(--success); }
  .rcv-badge.rose   { background: var(--danger-soft); color: var(--danger); }
  .rcv-badge.blue   { background: var(--blue-soft); color: var(--blue); }
  .rcv-badge.amber  { background: var(--warning-soft); color: var(--warning); }
  .rcv-badge.gray   { background: var(--bg); color: var(--muted); border: 1px solid var(--line); }
  .rcv-badge.slate  { background: var(--bg); color: var(--text-main); border: 1px solid var(--line); }

  .rcv-card-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--line);
  }

  .rcv-card-foot-left {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    color: var(--muted);
    font-size: 0.9rem;
    font-weight: 600;
  }

  .rcv-card-actions {
    display: flex;
    gap: 12px;
  }

  /* WIDGETS LATERALES */
  .rcv-side-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--ink);
    margin-bottom: 20px;
  }

  .rcv-aging-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .rcv-aging-item {
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 16px;
    background: var(--card);
  }

  .rcv-aging-top {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 12px;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-main);
  }

  .rcv-progress {
    height: 6px;
    background: var(--bg);
    border-radius: 999px;
    overflow: hidden;
  }

  .rcv-progress > span {
    display: block;
    height: 100%;
    border-radius: 999px;
  }

  .rcv-progress.blue > span   { background: var(--blue); }
  .rcv-progress.amber > span  { background: var(--warning); }
  .rcv-progress.orange > span { background: #f97316; }
  .rcv-progress.rose > span   { background: var(--danger); }

  .rcv-topdebtor {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.95rem;
    padding: 10px 0;
  }

  .rcv-rank {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 700;
    flex-shrink: 0;
  }

  .rcv-rank.one   { background: var(--danger-soft); color: var(--danger); }
  .rcv-rank.two   { background: var(--warning-soft); color: var(--warning); }
  .rcv-rank.other { background: var(--bg); color: var(--muted); }

  .rcv-topdebtor-name {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: var(--text-main);
    font-weight: 600;
  }

  .rcv-topdebtor-total {
    font-weight: 700;
    color: var(--ink);
    white-space: nowrap;
  }

  .rcv-docrow {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.95rem;
    padding: 10px 0;
  }

  .rcv-docrow-name {
    flex: 1;
    color: var(--text-main);
    font-weight: 600;
  }

  .rcv-docrow-total {
    font-weight: 700;
    color: var(--ink);
    white-space: nowrap;
  }

  .rcv-docrow-count {
    color: var(--muted);
    font-size: 0.85rem;
    font-weight: 700;
    min-width: 32px;
    text-align: right;
  }

  .rcv-empty {
    padding: 64px 24px;
    text-align: center;
    color: var(--muted);
    background: var(--card);
    border-radius: 16px;
    border: 1px solid var(--line);
  }

  .rcv-empty-ico {
    width: 48px;
    height: 48px;
    color: var(--line);
    margin: 0 auto 16px;
    display: block;
  }

  .rcv-pagination {
    margin-top: 24px;
  }

  .rcv-svg {
    width: 100%;
    height: 100%;
    display: block;
  }

  /* MEDIA QUERIES */
  @media (max-width: 1100px) {
    .rcv-main {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 860px) {
    .rcv-kpis {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .rcv-head {
      flex-direction: column;
      align-items: stretch;
    }

    .rcv-toolbar, .rcv-toolbar-bottom {
      grid-template-columns: 1fr;
    }

    .rcv-toolbar-meta {
      flex-direction: column;
      align-items: flex-start;
    }

    .rcv-card-top, .rcv-card-foot {
      flex-direction: column;
      align-items: flex-start;
    }

    .rcv-right {
      text-align: left;
    }
  }

  @media (max-width: 560px) {
    .rcv-kpis {
      grid-template-columns: 1fr;
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

    <div class="rcv-subpills" style="margin-bottom:12px">
      <span style="font-size:0.85rem;color:var(--muted);font-weight:700;align-self:center;">Filtrar:</span>
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
      <span style="font-size:0.85rem;color:var(--muted);font-weight:700;align-self:center;">Cobranza:</span>
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
          <div class="rcv-select-wrap" style="max-width:320px;">
            <select name="company_id" class="rcv-company rcv-auto-submit">
              <option value="">Todas las compañías</option>
              @foreach($companies as $c)
                <option value="{{ $c->id }}" @selected(request('company_id')==$c->id)>{{ $c->name }}</option>
              @endforeach
            </select>
            <span class="caret">{!! rcvIcon('chevron-down') !!}</span>
          </div>

          <div style="display:flex;justify-content:flex-end;flex:1;">
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
          <div style="font-weight:700;margin-bottom:8px;font-size:1.1rem;color:var(--ink);">No hay cuentas por cobrar</div>
          <div style="font-size:0.95rem">
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
                    <span style="color:var(--danger);font-weight:700">{{ $daysLate }} día(s) vencido</span>
                  @endif
                </div>

                <div class="rcv-card-actions">
                  <a class="rcv-mini-btn outline" href="{{ route('accounting.receivables.show',$r) }}">Ver</a>
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
        <div style="display:flex;flex-direction:column;gap:12px;">
          @forelse($topDebtors as $i => $debtor)
            <div class="rcv-topdebtor">
              <span class="rcv-rank {{ $i === 0 ? 'one' : ($i === 1 ? 'two' : 'other') }}">{{ $i + 1 }}</span>
              <span class="rcv-topdebtor-name">{{ $debtor['name'] }}</span>
              <span class="rcv-topdebtor-total">${{ number_format($debtor['total'], 0) }}</span>
            </div>
          @empty
            <div style="font-size:0.95rem;color:var(--muted);">Sin saldos pendientes</div>
          @endforelse
        </div>
      </div>

      <div class="rcv-side-card">
        <div class="rcv-side-title">Por tipo de documento</div>
        <div style="display:flex;flex-direction:column;gap:12px;">
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