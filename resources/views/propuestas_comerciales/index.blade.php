@extends('layouts.app')
@section('content_class', 'content--flush')
@section('content')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  .jureto-dashboard-page {
    --bg: #f6f7f9;
    --card: #ffffff;
    --ink: #1a1a1a;
    --ink-soft: #555;
    --muted: #8a8f98;
    --line: #ececef;
    --line-soft: #f2f3f5;
    --blue: #007aff;
    --blue-strong: #0a6cff;
    --blue-soft: #eaf2ff;
    --success: #15803d;
    --success-soft: #e7f7ec;
    --danger: #e5484d;
    --danger-soft: #fdecec;
    --warning: #a16207;
    --warning-soft: #fef6df;
    --violet: #6b5bff;

    /* Emil-style custom easing curves */
    --ease-out: cubic-bezier(0.23, 1, 0.32, 1);
    --ease-in-out: cubic-bezier(0.77, 0, 0.175, 1);

    min-height: 100vh;
    background:
      radial-gradient(1200px 480px at 78% -10%, #eef3ff 0%, rgba(238,243,255,0) 60%),
      var(--bg);
    color: var(--ink);
    font-family: 'Quicksand', sans-serif;
    padding: 34px 0 72px;
    -webkit-font-smoothing: antialiased;
  }

  .jureto-dashboard-page,
  .jureto-dashboard-page * { box-sizing: border-box; }

  .jureto-dashboard-page .num { font-variant-numeric: tabular-nums; }

  .jureto-dashboard-page .dash-wrap {
    width: 90vw;
    max-width: 1600px;
    margin: 0 auto;
  }

  /* ---------- Entrance animation (page load, staggered) ---------- */
  .jureto-dashboard-page .reveal {
    opacity: 0;
    transform: translateY(14px);
    animation: dashReveal .55s var(--ease-out) forwards;
  }
  @keyframes dashReveal {
    to { opacity: 1; transform: translateY(0); }
  }

  /* ---------- Header ---------- */
  .jureto-dashboard-page .dash-top {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
    margin-bottom: 36px;
  }
  .jureto-dashboard-page .eyebrow {
    margin: 0 0 12px;
    color: var(--muted);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .18em;
    text-transform: uppercase;
  }
  .jureto-dashboard-page .dash-title {
    margin: 0;
    color: #0d0d0d;
    font-size: 38px;
    line-height: 1.02;
    font-weight: 700;
    letter-spacing: -.045em;
  }
  .jureto-dashboard-page .dash-subtitle {
    margin: 12px 0 0;
    color: var(--muted);
    font-size: 15px;
    font-weight: 500;
    max-width: 540px;
    line-height: 1.5;
  }
  .jureto-dashboard-page .dash-actions { display: flex; gap: 12px; }

  /* ---------- Buttons ---------- */
  .jureto-dashboard-page .btn {
    appearance: none;
    border: 1px solid transparent;
    min-height: 50px;
    padding: 0 22px;
    border-radius: 14px;
    background: transparent;
    color: var(--ink);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    font-family: 'Quicksand', sans-serif;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: transform .18s var(--ease-out), box-shadow .22s var(--ease-out), background .2s ease, color .2s ease;
    white-space: nowrap;
  }
  .jureto-dashboard-page .btn:active { transform: scale(.97); }
  .jureto-dashboard-page .btn svg { width: 18px; height: 18px; }

  .jureto-dashboard-page .btn-primary {
    background: var(--blue);
    border-color: var(--blue);
    color: #fff;
    box-shadow: 0 10px 24px rgba(0,122,255,.18);
  }
  .jureto-dashboard-page .btn-secondary {
    background: var(--card);
    border-color: var(--line);
    color: var(--ink-soft);
    box-shadow: 0 2px 6px rgba(0,0,0,.03);
  }

  @media (hover: hover) and (pointer: fine) {
    .jureto-dashboard-page .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 16px 34px rgba(0,122,255,.26);
    }
    .jureto-dashboard-page .btn-secondary:hover {
      border-color: #dcdde1;
      color: #111;
      transform: translateY(-1px);
    }
  }

  /* ---------- KPI stats ---------- */
  .jureto-dashboard-page .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 24px;
  }
  .jureto-dashboard-page .stat-card {
    position: relative;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 20px;
    padding: 24px 24px 22px;
    box-shadow: 0 4px 14px rgba(20,20,40,.03);
    transition: transform .24s var(--ease-out), box-shadow .24s var(--ease-out);
    overflow: hidden;
  }
  @media (hover: hover) and (pointer: fine) {
    .jureto-dashboard-page .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 22px 44px rgba(20,20,40,.07);
    }
  }
  .jureto-dashboard-page .stat-card.is-blue {
    background: linear-gradient(150deg, #1f8bff 0%, var(--blue) 55%, #0064e0 100%);
    border-color: transparent;
    color: #fff;
    box-shadow: 0 18px 38px rgba(0,122,255,.28);
  }
  .jureto-dashboard-page .stat-card.is-blue::after {
    content: "";
    position: absolute;
    width: 180px; height: 180px;
    right: -50px; top: -70px;
    background: radial-gradient(circle, rgba(255,255,255,.22), rgba(255,255,255,0) 70%);
    pointer-events: none;
  }
  .jureto-dashboard-page .stat-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
  }
  .jureto-dashboard-page .stat-label {
    margin: 0;
    color: var(--muted);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .14em;
    text-transform: uppercase;
  }
  .jureto-dashboard-page .stat-card.is-blue .stat-label { color: rgba(255,255,255,.82); }
  .jureto-dashboard-page .stat-ico {
    width: 38px; height: 38px;
    border-radius: 11px;
    display: grid; place-items: center;
    background: var(--blue-soft);
    color: var(--blue);
    flex: none;
  }
  .jureto-dashboard-page .stat-ico svg { width: 19px; height: 19px; }
  .jureto-dashboard-page .stat-card.is-blue .stat-ico {
    background: rgba(255,255,255,.18);
    color: #fff;
  }
  .jureto-dashboard-page .stat-value {
    margin: 0;
    color: #0d0d0d;
    font-size: 31px;
    line-height: 1;
    font-weight: 700;
    letter-spacing: -.045em;
  }
  .jureto-dashboard-page .stat-card.is-blue .stat-value { color: #fff; }
  .jureto-dashboard-page .stat-foot {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-top: 14px;
  }
  .jureto-dashboard-page .trend {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 12.5px;
    font-weight: 700;
  }
  .jureto-dashboard-page .trend svg { width: 13px; height: 13px; }
  .jureto-dashboard-page .trend.up { background: var(--success-soft); color: var(--success); }
  .jureto-dashboard-page .trend.down { background: var(--danger-soft); color: var(--danger); }
  .jureto-dashboard-page .trend.flat { background: #f0f1f3; color: #777; }
  .jureto-dashboard-page .stat-card.is-blue .trend { background: rgba(255,255,255,.2); color: #fff; }
  .jureto-dashboard-page .stat-caption {
    color: var(--muted);
    font-size: 13px;
    font-weight: 600;
  }
  .jureto-dashboard-page .stat-card.is-blue .stat-caption { color: rgba(255,255,255,.78); }

  /* ---------- Analytics row ---------- */
  .jureto-dashboard-page .analytics-grid {
    display: grid;
    grid-template-columns: 1.9fr 1fr;
    gap: 20px;
    margin-bottom: 36px;
  }
  .jureto-dashboard-page .panel {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 22px;
    padding: 24px 26px;
    box-shadow: 0 4px 14px rgba(20,20,40,.03);
  }
  .jureto-dashboard-page .panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 22px;
  }
  .jureto-dashboard-page .panel-title {
    margin: 0;
    color: #0d0d0d;
    font-size: 17px;
    font-weight: 700;
    letter-spacing: -.02em;
  }
  .jureto-dashboard-page .panel-sub {
    margin: 4px 0 0;
    color: var(--muted);
    font-size: 13px;
    font-weight: 500;
  }
  .jureto-dashboard-page .legend {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: var(--muted);
    font-size: 13px;
    font-weight: 600;
  }
  .jureto-dashboard-page .legend i {
    width: 9px; height: 9px; border-radius: 3px;
    background: var(--blue); display: inline-block;
  }

  /* Chart */
  .jureto-dashboard-page .chart {
    width: 100%;
    height: auto;
    display: block;
    overflow: visible;
  }
  .jureto-dashboard-page .chart-line {
    fill: none;
    stroke: var(--blue);
    stroke-width: 2.5;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-dasharray: var(--len, 1200);
    stroke-dashoffset: var(--len, 1200);
    animation: drawLine 1.1s var(--ease-in-out) .25s forwards;
  }
  @keyframes drawLine { to { stroke-dashoffset: 0; } }
  .jureto-dashboard-page .chart-dot {
    fill: #fff;
    stroke: var(--blue);
    stroke-width: 2.5;
    opacity: 0;
    animation: dotIn .3s var(--ease-out) forwards;
  }
  @keyframes dotIn { to { opacity: 1; } }
  .jureto-dashboard-page .chart-xlabel {
    fill: var(--muted);
    font-size: 12px;
    font-weight: 600;
    text-anchor: middle;
  }
  .jureto-dashboard-page .chart-empty {
    text-align: center;
    color: var(--muted);
    font-size: 14px;
    font-weight: 500;
    padding: 40px 0;
  }

  /* Status distribution */
  .jureto-dashboard-page .dist-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 18px;
  }
  .jureto-dashboard-page .dist-row:last-child { margin-bottom: 0; }
  .jureto-dashboard-page .dist-label {
    flex: none;
    width: 96px;
    color: var(--ink-soft);
    font-size: 13.5px;
    font-weight: 600;
  }
  .jureto-dashboard-page .dist-track {
    flex: 1;
    height: 9px;
    border-radius: 999px;
    background: var(--line-soft);
    overflow: hidden;
  }
  .jureto-dashboard-page .dist-fill {
    height: 100%;
    border-radius: 999px;
    width: 0;
    transition: width 1s var(--ease-out) .3s;
  }
  .jureto-dashboard-page .dist-val {
    flex: none;
    width: 30px;
    text-align: right;
    color: #0d0d0d;
    font-size: 14px;
    font-weight: 700;
  }

  /* ---------- Recent quotes ---------- */
  .jureto-dashboard-page .section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 18px;
  }
  .jureto-dashboard-page .section-title {
    margin: 0;
    color: #0d0d0d;
    font-size: 20px;
    font-weight: 700;
    letter-spacing: -.025em;
  }
  .jureto-dashboard-page .view-all {
    color: var(--muted);
    text-decoration: none;
    font-size: 14px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: color .2s ease, transform .2s var(--ease-out);
  }
  .jureto-dashboard-page .view-all svg { width: 15px; height: 15px; }
  @media (hover: hover) and (pointer: fine) {
    .jureto-dashboard-page .view-all:hover { color: var(--blue); transform: translateX(3px); }
  }

  .jureto-dashboard-page .recent-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 4px 14px rgba(20,20,40,.03);
    margin-bottom: 32px;
  }
  .jureto-dashboard-page .quote-head {
    display: grid;
    grid-template-columns: minmax(190px, 230px) minmax(0, 1fr) 130px 120px 32px;
    gap: 18px;
    padding: 14px 30px;
    background: #fafbfc;
    border-bottom: 1px solid var(--line);
    color: var(--muted);
    font-size: 11.5px;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
  }
  .jureto-dashboard-page .quote-head span:nth-child(4),
  .jureto-dashboard-page .quote-head span:nth-child(3) { text-align: right; }
  .jureto-dashboard-page .quote-head span:nth-child(3) { text-align: left; }

  .jureto-dashboard-page .quote-row {
    display: grid;
    grid-template-columns: minmax(190px, 230px) minmax(0, 1fr) 130px 120px 32px;
    align-items: center;
    gap: 18px;
    min-height: 78px;
    padding: 14px 30px;
    text-decoration: none;
    color: inherit;
    border-bottom: 1px solid var(--line-soft);
    transition: background .18s ease;
  }
  .jureto-dashboard-page .quote-row:last-child { border-bottom: 0; }
  @media (hover: hover) and (pointer: fine) {
    .jureto-dashboard-page .quote-row:hover { background: #fafbff; }
  }

  .jureto-dashboard-page .quote-id { display: flex; align-items: center; gap: 13px; }
  .jureto-dashboard-page .avatar {
    flex: none;
    width: 40px; height: 40px;
    border-radius: 12px;
    display: grid; place-items: center;
    background: linear-gradient(140deg, #eef2ff, #e3ecff);
    color: var(--blue-strong);
    font-size: 14px;
    font-weight: 700;
  }
  .jureto-dashboard-page .quote-folio {
    color: #0d0d0d;
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 4px;
    line-height: 1.2;
  }
  .jureto-dashboard-page .quote-date {
    color: var(--muted);
    font-size: 12.5px;
    font-weight: 600;
  }
  .jureto-dashboard-page .quote-code {
    color: #0d0d0d;
    font-size: 15px;
    font-weight: 600;
    margin-bottom: 4px;
  }
  .jureto-dashboard-page .quote-note {
    color: var(--muted);
    font-size: 13px;
    font-weight: 500;
    max-width: 520px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .jureto-dashboard-page .badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 28px;
    padding: 5px 13px;
    border-radius: 999px;
    font-size: 12.5px;
    font-weight: 700;
    white-space: nowrap;
  }
  .jureto-dashboard-page .badge::before {
    content: "";
    width: 6px; height: 6px; border-radius: 999px;
    background: currentColor;
  }
  .jureto-dashboard-page .badge-draft,
  .jureto-dashboard-page .badge-pending { background: #f1f2f4; color: #5a5f68; }
  .jureto-dashboard-page .badge-approved,
  .jureto-dashboard-page .badge-aprobada,
  .jureto-dashboard-page .badge-accepted { background: var(--success-soft); color: var(--success); }
  .jureto-dashboard-page .badge-priced { background: var(--warning-soft); color: var(--warning); }
  .jureto-dashboard-page .badge-rejected,
  .jureto-dashboard-page .badge-error { background: var(--danger-soft); color: var(--danger); }
  .jureto-dashboard-page .badge-matched,
  .jureto-dashboard-page .badge-processing { background: var(--blue-soft); color: var(--blue); }

  .jureto-dashboard-page .quote-money { text-align: right; }
  .jureto-dashboard-page .quote-total {
    color: #0d0d0d;
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 4px;
  }
  .jureto-dashboard-page .quote-margin {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    color: var(--success);
    font-size: 12.5px;
    font-weight: 700;
  }
  .jureto-dashboard-page .quote-margin svg { width: 12px; height: 12px; }
  .jureto-dashboard-page .quote-margin.neg { color: var(--danger); }
  .jureto-dashboard-page .quote-arrow {
    color: #c2c5cc;
    display: grid; place-items: center;
    transition: color .2s ease, transform .2s var(--ease-out);
  }
  .jureto-dashboard-page .quote-arrow svg { width: 18px; height: 18px; }
  @media (hover: hover) and (pointer: fine) {
    .jureto-dashboard-page .quote-row:hover .quote-arrow { color: var(--blue); transform: translateX(4px); }
  }

  /* ---------- Empty state ---------- */
  .jureto-dashboard-page .empty-state {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 22px;
    padding: 60px 24px;
    text-align: center;
    box-shadow: 0 4px 14px rgba(20,20,40,.03);
  }
  .jureto-dashboard-page .empty-ico {
    width: 64px; height: 64px;
    margin: 0 auto 18px;
    border-radius: 18px;
    display: grid; place-items: center;
    background: var(--blue-soft);
    color: var(--blue);
  }
  .jureto-dashboard-page .empty-ico svg { width: 30px; height: 30px; }
  .jureto-dashboard-page .empty-title {
    margin: 0;
    color: #0d0d0d;
    font-size: 22px;
    font-weight: 700;
  }
  .jureto-dashboard-page .empty-text {
    margin: 10px auto 24px;
    color: var(--muted);
    font-size: 15px;
    line-height: 1.7;
    max-width: 480px;
  }

  /* ---------- Footer metrics ---------- */
  .jureto-dashboard-page .foot-metrics {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
  }
  .jureto-dashboard-page .foot-card {
    display: flex;
    align-items: center;
    gap: 14px;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 18px 20px;
    box-shadow: 0 4px 14px rgba(20,20,40,.03);
  }
  .jureto-dashboard-page .foot-ico {
    width: 40px; height: 40px;
    border-radius: 12px;
    display: grid; place-items: center;
    flex: none;
    background: var(--blue-soft);
    color: var(--blue);
  }
  .jureto-dashboard-page .foot-ico svg { width: 19px; height: 19px; }
  .jureto-dashboard-page .foot-num {
    color: #0d0d0d;
    font-size: 22px;
    font-weight: 700;
    line-height: 1;
    letter-spacing: -.03em;
  }
  .jureto-dashboard-page .foot-text {
    color: var(--muted);
    font-size: 13px;
    font-weight: 600;
    margin-top: 4px;
  }

  .jureto-dashboard-page .pagination-wrap { margin-top: 28px; }

  /* ---------- Responsive ---------- */
  @media (max-width: 1100px) {
    .jureto-dashboard-page .dash-wrap { width: 94vw; }
    .jureto-dashboard-page .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .jureto-dashboard-page .analytics-grid { grid-template-columns: 1fr; }
    .jureto-dashboard-page .quote-head { display: none; }
    .jureto-dashboard-page .quote-row {
      grid-template-columns: minmax(150px, 1fr) auto auto;
    }
    .jureto-dashboard-page .quote-main { grid-column: 1 / -1; padding-left: 53px; }
    .jureto-dashboard-page .quote-money { grid-column: 2 / 3; }
    .jureto-dashboard-page .quote-arrow { grid-column: 3 / 4; }
  }

  @media (max-width: 720px) {
    .jureto-dashboard-page { padding: 22px 0 46px; }
    .jureto-dashboard-page .dash-wrap { width: calc(100vw - 24px); }
    .jureto-dashboard-page .dash-top { flex-direction: column; align-items: stretch; margin-bottom: 26px; }
    .jureto-dashboard-page .dash-title { font-size: 32px; }
    .jureto-dashboard-page .dash-actions { display: grid; grid-template-columns: 1fr 1fr; }
    .jureto-dashboard-page .btn { width: 100%; }
    .jureto-dashboard-page .stats-grid { grid-template-columns: 1fr; gap: 14px; }
    .jureto-dashboard-page .quote-row { grid-template-columns: 1fr auto; padding: 16px 18px; }
    .jureto-dashboard-page .quote-main { padding-left: 0; }
    .jureto-dashboard-page .quote-money { grid-column: 1 / 2; }
    .jureto-dashboard-page .quote-arrow { grid-column: 2 / 3; grid-row: 1 / 4; align-self: center; }
    .jureto-dashboard-page .foot-metrics { grid-template-columns: 1fr; }
    .jureto-dashboard-page .panel { padding: 20px; }
  }

  /* ---------- Reduced motion ---------- */
  @media (prefers-reduced-motion: reduce) {
    .jureto-dashboard-page .reveal { animation: none; opacity: 1; transform: none; }
    .jureto-dashboard-page .chart-line { animation: none; stroke-dashoffset: 0; }
    .jureto-dashboard-page .chart-dot { animation: none; opacity: 1; }
    .jureto-dashboard-page .dist-fill { transition: none; }
    .jureto-dashboard-page * { scroll-behavior: auto; }
  }
</style>

@php
  $proposalsSource = $propuestasComerciales ?? $propuestas ?? collect();

  $isPaginator = is_object($proposalsSource) && method_exists($proposalsSource, 'getCollection');
  $proposalsCollection = $isPaginator ? $proposalsSource->getCollection() : collect($proposalsSource);

  $allForStats = isset($allPropuestasComerciales)
      ? collect($allPropuestasComerciales)
      : $proposalsCollection;

  $fmtMoney = function ($n) {
      $n = (float) $n;
      if (abs($n) >= 1000000) return '$' . rtrim(rtrim(number_format($n / 1000000, 1), '0'), '.') . 'M';
      if (abs($n) >= 1000)    return '$' . rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'k';
      return '$' . number_format($n, 0);
  };
  $fmtMoneyFull = fn($n) => '$' . number_format((float) $n, 0);

  $proposalTotal = function ($proposal) {
      return (float) ($proposal->total ?? $proposal->subtotal ?? $proposal->subtotal_venta ?? 0);
  };

  $proposalCost = function ($proposal) {
      if (isset($proposal->subtotal_costo)) return (float) $proposal->subtotal_costo;
      if (isset($proposal->items)) {
          return (float) collect($proposal->items)->sum(function ($item) {
              $qty  = (float) ($item->cantidad_cotizada ?? $item->quantity ?? 0);
              $cost = (float) ($item->costo_unitario ?? $item->cost ?? 0);
              return $qty * $cost;
          });
      }
      return 0;
  };

  $proposalProfit = function ($proposal) use ($proposalTotal, $proposalCost) {
      if (isset($proposal->utilidad_total)) return (float) $proposal->utilidad_total;
      if (isset($proposal->profit)) return (float) $proposal->profit;
      return $proposalTotal($proposal) - $proposalCost($proposal);
  };

  // Defensive Carbon parser
  $dateOf = function ($p) {
      try { return $p->created_at ? \Carbon\Carbon::parse($p->created_at) : null; }
      catch (\Throwable $e) { return null; }
  };

  $totalQuotes     = (int) $allForStats->count();
  $quotedAmount    = (float) $allForStats->sum(fn($p) => $proposalTotal($p));
  $estimatedProfit = (float) $allForStats->sum(fn($p) => $proposalProfit($p));
  $estimatedCost   = (float) $allForStats->sum(fn($p) => $proposalCost($p));
  $avgMargin       = $estimatedCost > 0 ? round(($estimatedProfit / $estimatedCost) * 100) : 0;

  // --- Month-over-month trends (real deltas instead of static captions) ---
  $now = \Carbon\Carbon::now();
  $thisMonth = $allForStats->filter(fn($p) => optional($dateOf($p))->isSameMonth($now));
  $lastMonth = $allForStats->filter(fn($p) => optional($dateOf($p))->isSameMonth($now->copy()->subMonth()));

  $pct = function ($cur, $prev) {
      if ($prev > 0) return (int) round((($cur - $prev) / $prev) * 100);
      return $cur > 0 ? 100 : 0;
  };

  $trendQuotes = $pct($thisMonth->count(), $lastMonth->count());
  $trendAmount = $pct($thisMonth->sum(fn($p) => $proposalTotal($p)), $lastMonth->sum(fn($p) => $proposalTotal($p)));
  $trendProfit = $pct($thisMonth->sum(fn($p) => $proposalProfit($p)), $lastMonth->sum(fn($p) => $proposalProfit($p)));

  // --- 6-month series for the chart ---
  $series = collect(range(5, 0))->map(function ($i) use ($allForStats, $proposalTotal, $dateOf, $now) {
      $m = $now->copy()->subMonths($i);
      $sum = $allForStats->filter(function ($p) use ($dateOf, $m) {
          $d = $dateOf($p);
          return $d && $d->month === $m->month && $d->year === $m->year;
      })->sum(fn($p) => $proposalTotal($p));
      return ['label' => ucfirst($m->locale('es')->isoFormat('MMM')), 'value' => (float) $sum];
  })->values();

  $maxVal = max(1, $series->max('value'));
  $hasChartData = $series->sum('value') > 0;

  // Chart geometry (viewBox 0 0 720 220, padding inset)
  $vbW = 720; $vbH = 220; $pad = 24;
  $plotW = $vbW - $pad * 2;
  $plotH = $vbH - $pad * 2 - 18; // leave room for x labels
  $n = $series->count();
  $pts = [];
  foreach ($series as $idx => $row) {
      $x = $pad + ($n > 1 ? ($idx / ($n - 1)) * $plotW : $plotW / 2);
      $y = $pad + $plotH - (($row['value'] / $maxVal) * $plotH);
      $pts[] = ['x' => round($x, 1), 'y' => round($y, 1), 'label' => $row['label']];
  }
  $linePath = '';
  foreach ($pts as $k => $p) { $linePath .= ($k === 0 ? 'M' : 'L') . $p['x'] . ' ' . $p['y'] . ' '; }
  $areaPath = $linePath . 'L' . end($pts)['x'] . ' ' . ($pad + $plotH) . ' L' . $pts[0]['x'] . ' ' . ($pad + $plotH) . ' Z';
  // approximate path length for the draw animation
  $lineLen = 0;
  for ($k = 1; $k < count($pts); $k++) {
      $lineLen += sqrt(pow($pts[$k]['x'] - $pts[$k-1]['x'], 2) + pow($pts[$k]['y'] - $pts[$k-1]['y'], 2));
  }
  $lineLen = (int) ($lineLen + 40);

  // --- Status distribution ---
  $statusOf = fn($p) => strtolower((string) ($p->status ?? 'draft'));
  $countIn = fn($keys) => $allForStats->filter(fn($p) => in_array($statusOf($p), $keys, true))->count();

  $draftsCount   = $countIn(['draft', 'borrador', 'pending', 'pendiente']);
  $approvedCount = $countIn(['approved', 'aprobada', 'accepted', 'aceptada']);
  $pricedCount   = $countIn(['priced', 'cotizada']);
  $rejectedCount = $countIn(['rejected', 'rechazada']);

  $distData = [
      ['label' => 'Aprobadas',  'value' => $approvedCount, 'color' => 'var(--success)'],
      ['label' => 'Cotizadas',  'value' => $pricedCount,   'color' => 'var(--warning)'],
      ['label' => 'Borradores', 'value' => $draftsCount,   'color' => '#9aa0aa'],
      ['label' => 'Rechazadas', 'value' => $rejectedCount, 'color' => 'var(--danger)'],
  ];
  $distMax = max(1, $totalQuotes);

  try {
      $productsCount = class_exists(\App\Models\Product::class) ? \App\Models\Product::count() : 0;
  } catch (\Throwable $e) {
      $productsCount = 0;
  }

  $recentProposals = $proposalsCollection->sortByDesc(fn($p) => $p->created_at ?? now())->take(8);

  $statusLabel = function ($status) {
      return match (strtolower((string) ($status ?: 'draft'))) {
          'approved', 'aprobada', 'accepted', 'aceptada' => 'Aprobada',
          'priced', 'cotizada' => 'Cotizada',
          'matched' => 'Analizada',
          'processing', 'procesando' => 'Procesando',
          'rejected', 'rechazada' => 'Rechazada',
          default => 'Borrador',
      };
  };

  $statusClass = function ($status) {
      return match (strtolower((string) ($status ?: 'draft'))) {
          'approved', 'aprobada', 'accepted', 'aceptada' => 'badge-approved',
          'priced', 'cotizada' => 'badge-priced',
          'matched' => 'badge-matched',
          'processing', 'procesando' => 'badge-processing',
          'rejected', 'rechazada' => 'badge-rejected',
          default => 'badge-draft',
      };
  };

  $proposalFolio = fn($p) => $p->titulo ?? $p->folio_cotizacion ?? $p->folio
      ?? ('COT-' . strtoupper(substr(md5(($p->id ?? '') . ($p->created_at ?? '')), 0, 8)));

  $proposalCode = fn($p) => $p->folio ?? $p->codigo ?? $p->licitacion_codigo
      ?? ('TEOA' . str_pad((string) ($p->id ?? 0), 8, '0', STR_PAD_LEFT));

  $proposalNote = fn($p) => $p->descripcion ?? $p->notas ?? $p->cliente ?? $p->filename ?? 'Sin descripción';

  $initials = function ($text) {
      $text = trim((string) $text);
      if ($text === '') return '··';
      $parts = preg_split('/[\s\-_]+/', $text);
      $a = mb_substr($parts[0] ?? '', 0, 1);
      $b = mb_substr($parts[1] ?? ($parts[0] ?? ''), 0, 1);
      return mb_strtoupper($a . $b);
  };

  $showRoute   = fn($p) => route('propuestas-comerciales.show', $p);
  $createRoute = route('propuestas-comerciales.create');
@endphp

<div class="jureto-dashboard-page">
  <div class="dash-wrap">

    {{-- Header --}}
    <div class="dash-top reveal" style="animation-delay:0ms">
      <div>
        <p class="eyebrow">Panel general</p>
        <h1 class="dash-title">Dashboard</h1>
        <p class="dash-subtitle">
          Resumen ejecutivo de tus cotizaciones comerciales, utilidad estimada y estado de aprobación.
        </p>
      </div>
      <div class="dash-actions">
        <a href="{{ route('propuestas-comerciales.index') }}" class="btn btn-secondary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
          Ver todas
        </a>
        <a href="{{ $createRoute }}" class="btn btn-primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Nueva cotización
        </a>
      </div>
    </div>

    {{-- KPIs --}}
    <div class="stats-grid">
      @php
        $kpis = [
          ['label'=>'Cotizaciones','value'=>number_format($totalQuotes),'count'=>$totalQuotes,'trend'=>$trendQuotes,'caption'=>'vs mes anterior','blue'=>false,
           'ico'=>'<path d="M9 12h6M9 16h6M9 8h2"/><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>'],
          ['label'=>'Monto cotizado','value'=>$fmtMoney($quotedAmount),'count'=>null,'trend'=>$trendAmount,'caption'=>'vs mes anterior','blue'=>false,
           'ico'=>'<rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/>'],
          ['label'=>'Utilidad estimada','value'=>$fmtMoney($estimatedProfit),'count'=>null,'trend'=>$trendProfit,'caption'=>'utilidad neta','blue'=>true,
           'ico'=>'<path d="M3 17l6-6 4 4 8-8"/><path d="M21 7h-6M21 7v6"/>'],
          ['label'=>'Margen promedio','value'=>$avgMargin.'%','count'=>$avgMargin,'suffix'=>'%','trend'=>null,'caption'=>'sobre costo','blue'=>false,
           'ico'=>'<line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>'],
        ];
      @endphp

      @foreach($kpis as $i => $k)
        <div class="stat-card reveal {{ $k['blue'] ? 'is-blue' : '' }}" style="animation-delay:{{ 60 + $i*60 }}ms">
          <div class="stat-head">
            <p class="stat-label">{{ $k['label'] }}</p>
            <span class="stat-ico">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $k['ico'] !!}</svg>
            </span>
          </div>
          <p class="stat-value num"
             @if(!is_null($k['count'])) data-count="{{ $k['count'] }}" data-suffix="{{ $k['suffix'] ?? '' }}" @endif>
            {{ $k['value'] }}
          </p>
          <div class="stat-foot">
            @if(!is_null($k['trend']))
              @php $t = $k['trend']; $cls = $t > 0 ? 'up' : ($t < 0 ? 'down' : 'flat'); @endphp
              <span class="trend {{ $cls }}">
                @if($t > 0)
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 14 12 8 18 14"/></svg>
                @elseif($t < 0)
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 10 12 16 18 10"/></svg>
                @endif
                {{ abs($t) }}%
              </span>
            @endif
            <span class="stat-caption">{{ $k['caption'] }}</span>
          </div>
        </div>
      @endforeach
    </div>

    {{-- Analytics --}}
    <div class="analytics-grid">
      <div class="panel reveal" style="animation-delay:300ms">
        <div class="panel-head">
          <div>
            <h2 class="panel-title">Monto cotizado</h2>
            <p class="panel-sub">Últimos 6 meses</p>
          </div>
          <span class="legend"><i></i> Cotizado</span>
        </div>

        @if($hasChartData)
          <svg class="chart" viewBox="0 0 {{ $vbW }} {{ $vbH }}" preserveAspectRatio="none" role="img" aria-label="Gráfico de monto cotizado por mes">
            <defs>
              <linearGradient id="chartFill" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#007aff" stop-opacity="0.18"/>
                <stop offset="100%" stop-color="#007aff" stop-opacity="0"/>
              </linearGradient>
            </defs>
            <path d="{{ $areaPath }}" fill="url(#chartFill)"/>
            <path class="chart-line" style="--len:{{ $lineLen }}" d="{{ $linePath }}"/>
            @foreach($pts as $k => $p)
              <circle class="chart-dot" cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="4" style="animation-delay:{{ 600 + $k*90 }}ms"/>
              <text class="chart-xlabel" x="{{ $p['x'] }}" y="{{ $vbH - 4 }}">{{ $p['label'] }}</text>
            @endforeach
          </svg>
        @else
          <p class="chart-empty">Aún no hay datos suficientes para mostrar la tendencia.</p>
        @endif
      </div>

      <div class="panel reveal" style="animation-delay:360ms">
        <div class="panel-head">
          <div>
            <h2 class="panel-title">Distribución</h2>
            <p class="panel-sub">Por estado</p>
          </div>
        </div>
        @foreach($distData as $d)
          @php $w = $totalQuotes > 0 ? round(($d['value'] / $distMax) * 100) : 0; @endphp
          <div class="dist-row">
            <span class="dist-label">{{ $d['label'] }}</span>
            <span class="dist-track">
              <span class="dist-fill" style="background:{{ $d['color'] }}" data-width="{{ $w }}"></span>
            </span>
            <span class="dist-val num">{{ $d['value'] }}</span>
          </div>
        @endforeach
      </div>
    </div>

    {{-- Recent quotes --}}
    <div class="section-head reveal" style="animation-delay:420ms">
      <h2 class="section-title">Cotizaciones recientes</h2>
      @if($isPaginator && method_exists($proposalsSource, 'url'))
        <a href="{{ $proposalsSource->url(1) }}" class="view-all">
          Ver todas
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
      @else
        <a href="{{ route('propuestas-comerciales.index') }}" class="view-all">
          Ver todas
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
      @endif
    </div>

    @if($recentProposals->count())
      <div class="recent-card reveal" style="animation-delay:460ms">
        <div class="quote-head">
          <span>Cotización</span>
          <span>Detalle</span>
          <span>Estado</span>
          <span>Total</span>
          <span></span>
        </div>

        @foreach($recentProposals as $proposal)
          @php
            $cost   = $proposalCost($proposal);
            $profit = $proposalProfit($proposal);
            $margin = $cost > 0 ? round(($profit / $cost) * 100) : 0;
            $status = $proposal->status ?? 'draft';
          @endphp

          <a href="{{ $showRoute($proposal) }}" class="quote-row">
            <div class="quote-id">
              <span class="avatar">{{ $initials($proposalFolio($proposal)) }}</span>
              <div>
                <div class="quote-folio">{{ $proposalFolio($proposal) }}</div>
                <div class="quote-date">{{ optional($proposal->created_at)->format('d M Y') ?? 'Sin fecha' }}</div>
              </div>
            </div>

            <div class="quote-main">
              <div class="quote-code">{{ $proposalCode($proposal) }}</div>
              <div class="quote-note">{{ $proposalNote($proposal) }}</div>
            </div>

            <div>
              <span class="badge {{ $statusClass($status) }}">{{ $statusLabel($status) }}</span>
            </div>

            <div class="quote-money">
              <div class="quote-total num">{{ $fmtMoneyFull($proposalTotal($proposal)) }}</div>
              <div class="quote-margin {{ $margin < 0 ? 'neg' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  @if($margin < 0)<polyline points="6 10 12 16 18 10"/>@else<polyline points="6 14 12 8 18 14"/>@endif
                </svg>
                {{ $margin }}%
              </div>
            </div>

            <div class="quote-arrow">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </div>
          </a>
        @endforeach
      </div>
    @else
      <div class="empty-state reveal" style="animation-delay:460ms">
        <div class="empty-ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
        </div>
        <h2 class="empty-title">Aún no tienes cotizaciones</h2>
        <p class="empty-text">
          Crea tu primera cotización comercial, analiza partidas con IA y calcula márgenes automáticamente.
        </p>
        <a href="{{ $createRoute }}" class="btn btn-primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Nueva cotización
        </a>
      </div>
    @endif

    {{-- Footer metrics --}}
    <div class="foot-metrics reveal" style="animation-delay:520ms">
      <div class="foot-card">
        <span class="foot-ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
        </span>
        <div>
          <div class="foot-num num" data-count="{{ $productsCount }}">{{ number_format($productsCount) }}</div>
          <div class="foot-text">productos en catálogo</div>
        </div>
      </div>
      <div class="foot-card">
        <span class="foot-ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
        </span>
        <div>
          <div class="foot-num num" data-count="{{ $draftsCount }}">{{ number_format($draftsCount) }}</div>
          <div class="foot-text">borradores pendientes</div>
        </div>
      </div>
      <div class="foot-card">
        <span class="foot-ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </span>
        <div>
          <div class="foot-num num" data-count="{{ $approvedCount }}">{{ number_format($approvedCount) }}</div>
          <div class="foot-text">aceptadas</div>
        </div>
      </div>
    </div>

    @if($isPaginator)
      <div class="pagination-wrap">{{ $proposalsSource->links() }}</div>
    @endif

  </div>
</div>

<script>
(function () {
  var page = document.querySelector('.jureto-dashboard-page');
  if (!page) return;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Animate distribution bars in
  requestAnimationFrame(function () {
    page.querySelectorAll('.dist-fill').forEach(function (el) {
      el.style.width = (el.getAttribute('data-width') || 0) + '%';
    });
  });

  if (reduce) return;

  // Animated counters for numeric KPIs
  function animateCount(el) {
    var target = parseFloat(el.getAttribute('data-count')) || 0;
    var suffix = el.getAttribute('data-suffix') || '';
    var dur = 900, start = performance.now();
    function tick(now) {
      var p = Math.min((now - start) / dur, 1);
      var eased = 1 - Math.pow(1 - p, 3); // ease-out cubic
      el.textContent = Math.round(target * eased).toLocaleString('es-MX') + suffix;
      if (p < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (e.isIntersecting) { animateCount(e.target); observer.unobserve(e.target); }
    });
  }, { threshold: 0.4 });

  page.querySelectorAll('[data-count]').forEach(function (el) { observer.observe(el); });
})();
</script>
@endsection