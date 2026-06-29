{{-- ✅ PÉGALO COMPLETO
   - Buscador: ya NO te corta al escribir. Debounce 800ms + el cursor vuelve solo al
     final del texto después de recargar, así puedes seguir escribiendo.
   - Muestras: filtro de 3 pestañas (Catálogo / Muestras / Todos). Por defecto NO se ven muestras.
   - Las filas de muestra se ven con tinte ámbar y badge más claro (incluye quién la tiene).
--}}
@extends('layouts.app')
@section('title','Productos Web')

@push('styles')
<style>
  :root{
    --ink:#0f172a; --muted:#64748b; --bg:#f7fafc;
    --line:#e8eef6; --surface:#ffffff;
    --shadow:0 12px 30px rgba(13, 23, 38, .06);
    --r:16px;

    --acc:#34d399;
    --acc-ink:#065f46;
    --acc-soft:rgba(52,211,153,.14);
    --acc-ring:rgba(52,211,153,.28);

    --g1:rgba(52,211,153,.12);
    --g2:rgba(251,191,36,.10);
    --g3:rgba(148,163,184,.10);

    --tt-bg:#111827;
    --tt-fg:#ffffff;
  }

  html,body{background:var(--bg)}
  .wrap{max-width:1200px; margin-inline:auto; padding:0 14px}
  .card{background:var(--surface); border:1px solid var(--line); border-radius:var(--r); box-shadow:var(--shadow);}

  .head{
    display:flex; gap:14px; align-items:flex-start;
    justify-content:space-between; flex-wrap:wrap;
    margin:14px 0 10px;
  }
  .title{font-weight:900; color:var(--ink); letter-spacing:-.02em; margin:0}
  .muted{color:var(--muted)}
  .subtxt{margin-top:6px;font-size:.92rem;max-width:70ch}
  .head-actions{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
  }

  .btn{
    display:inline-flex; align-items:center; justify-content:center; gap:10px;
    border:1px solid transparent;
    cursor:pointer; text-decoration:none;
    font-weight:800;
    border-radius:14px;
    padding:10px 14px;
    transition:transform .12s ease, box-shadow .12s ease, background .12s ease, color .12s ease, border-color .12s ease;
    box-shadow:0 10px 22px rgba(15,23,42,.06);
    user-select:none;
    background:var(--acc-soft);
    color:var(--acc-ink);
    border-color:var(--acc-ring);
  }
  .btn:hover{
    transform:translateY(-1px);
    background:#fff;
    color:#111827;
    border-color:var(--line);
    box-shadow:0 14px 28px rgba(15,23,42,.08);
  }
  .btn:active{ transform:translateY(0); box-shadow:0 10px 22px rgba(15,23,42,.06); }
  .btn-sm{ padding:8px 10px; border-radius:12px; font-size:.92rem; }
  .btn-ghost{
    background:#f9fafb;
    border-color:#e5e7eb;
    color:#4b5563;
    box-shadow:none;
  }
  .btn-ghost:hover{ background:#f3f4f6; }
  .btn-soft{
    background:#f9fafb;
    border-color:#e5e7eb;
    color:#111827;
  }
  .btn-soft:hover{ background:#ffffff; }

  .ico{ width:18px; height:18px; display:inline-block; }
  .ico svg{ width:18px; height:18px; display:block; }

  .tt{ position:relative; display:inline-flex; }
  .tt .tt-bubble{
    position:absolute; left:50%; bottom:calc(100% + 10px);
    transform:translateX(-50%);
    background:var(--tt-bg); color:var(--tt-fg);
    font-size:12px; font-weight:700;
    padding:8px 10px; border-radius:12px;
    white-space:nowrap; opacity:0; pointer-events:none;
    box-shadow:0 14px 30px rgba(0,0,0,.18);
    transition:opacity .14s ease, transform .14s ease;
    z-index:20;
  }
  .tt .tt-bubble:before{
    content:""; position:absolute; left:50%; bottom:-6px;
    width:12px; height:12px; background:var(--tt-bg);
    transform:translateX(-50%) rotate(45deg); border-radius:2px;
  }
  .tt:hover .tt-bubble{ opacity:1; transform:translateX(-50%) translateY(-2px); }
  @media (hover:none){ .tt .tt-bubble{ display:none !important; } }

  .iconbtn-wrap{ display:inline-flex; position:relative; }
  .iconbtn{
    width:38px; height:38px;
    border-radius:12px;
    border:1px solid var(--line);
    background:#fff;
    display:inline-grid;
    place-items:center;
    cursor:pointer;
    transition:transform .12s ease, box-shadow .12s ease, background .12s ease;
    box-shadow:0 8px 18px rgba(15,23,42,.05);
  }
  .iconbtn:hover{ transform:translateY(-1px); background:#fff; box-shadow:0 12px 24px rgba(15,23,42,.08); }
  .iconbtn svg{ width:18px; height:18px; }

  .filters{
    margin-top:12px;
    padding:12px;
    border-radius:18px;
    border:1px solid rgba(232,238,246,.9);
    background:
      radial-gradient(900px 140px at 12% 0%, var(--g1), transparent 62%),
      radial-gradient(860px 160px at 88% 0%, var(--g2), transparent 60%),
      radial-gradient(520px 120px at 55% 0%, var(--g3), transparent 60%),
      #ffffff;
    box-shadow:0 18px 44px rgba(15,23,42,.08);
  }
  .filters-row{display:flex; gap:12px; align-items:center; justify-content:space-between; flex-wrap:wrap;}

  .search{
    display:flex; align-items:center; gap:10px;
    flex:1; min-width:0; width:min(100%, 560px);
    background:#fff;
    border:1px solid rgba(232,238,246,.95);
    border-radius:999px;
    padding:10px 12px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.9), 0 10px 18px rgba(15,23,42,.05);
    transition:border-color .14s ease, box-shadow .14s ease, transform .14s ease;
  }
  .search:focus-within{
    border-color:var(--acc-ring);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.95), 0 14px 26px rgba(52,211,153,.14);
    transform: translateY(-1px);
  }
  .search .sico{ color:#94a3b8; width:22px; display:grid; place-items:center; }
  .search input{border:0; outline:0; background:transparent; width:100%; color:var(--ink); font-weight:500;}

  .filter-tools{ display:inline-flex; gap:12px; align-items:center; flex-wrap:wrap; }

  .tabs{
    display:inline-flex; align-items:center; gap:6px;
    padding:6px; border-radius:999px;
    border:1px solid rgba(232,238,246,.95);
    background:rgba(255,255,255,.86);
    box-shadow:0 10px 18px rgba(15,23,42,.04);
    user-select:none;
  }
  .tab{
    appearance:none; border:0; background:transparent;
    padding:9px 12px; border-radius:999px;
    cursor:pointer; font-weight:700; color:#334155;
    transition: background .12s ease, color .12s ease, transform .12s ease, box-shadow .12s ease;
    white-space:nowrap;
  }
  .tab:hover{ background:rgba(52,211,153,.10); transform: translateY(-1px); box-shadow:0 10px 18px rgba(15,23,42,.05); }
  .tab.is-active{ background:var(--acc-soft); color:var(--acc-ink); box-shadow:0 12px 22px rgba(52,211,153,.12); }

  /* Pestañas de muestras: acento ámbar para distinguirlas del estado */
  .tabs.tabs-samples .tab.is-active{
    background:#fef3c7; color:#92400e; box-shadow:0 12px 22px rgba(245,158,11,.16);
  }
  .tabs.tabs-samples .tab:hover{ background:rgba(245,158,11,.12); }

  .chip{
    display:inline-flex; align-items:center; gap:10px;
    padding:10px 14px; border-radius:999px;
    border:1px solid rgba(232,238,246,.95);
    background:rgba(255,255,255,.86);
    box-shadow:0 10px 18px rgba(15,23,42,.04);
    font-weight:700; color:#334155; cursor:pointer;
    user-select:none;
    transition: transform .12s ease, box-shadow .12s ease, background .12s ease, border-color .12s ease;
    white-space:nowrap;
  }
  .chip:hover{ transform: translateY(-1px); box-shadow:0 14px 22px rgba(15,23,42,.06); background:#fff; }
  .chip input{ width:16px; height:16px; accent-color: var(--acc); }

  .table-wrap{ margin-top:12px; overflow:auto; border-radius:14px; border:1px solid var(--line); }
  table{ width:100%; border-collapse:collapse; font-size:.95rem; background:#fff }
  th, td{ padding:12px 12px; border-bottom:1px solid var(--line); vertical-align:middle; }
  th{ font-weight:900; text-align:left; color:var(--ink); background:#fbfdff; white-space:nowrap; }
  tr:hover td{ background:#fcfdfd }

  td.img-cell, th.img-cell{ width:72px; max-width:72px; }
  .thumbbox{ width:56px; height:56px; border-radius:12px; border:1px solid var(--line); background:#f6f8fc; overflow:hidden; display:grid; place-items:center; }
  .thumbbox img{ width:100%; height:100%; object-fit:cover; display:block; }

  .name{ display:flex; flex-direction:column; gap:4px; min-width:260px; }
  .name strong{ color:var(--ink); font-weight:900; line-height:1.2 }
  .meta{ display:flex; gap:10px; flex-wrap:wrap; align-items:center; font-size:.84rem; color:var(--muted); }
  .meta .k{ color:#64748b; font-weight:800; }
  .meta .v{ color:#334155; font-weight:800; }

  .badge{
    display:inline-flex; align-items:center; gap:8px;
    padding:6px 10px; border-radius:999px;
    font-weight:900; font-size:.78rem;
    border:1px solid var(--line);
    background:#f1f5f9; color:#334155;
  }
  .badge .dot{ width:8px; height:8px; border-radius:999px; background:#cbd5e1; }
  .b-live{ background:rgba(134,239,172,.22); border-color:rgba(134,239,172,.40); color:#065f46; }
  .b-live .dot{ background:#22c55e; }
  .b-draft .dot{ background:#94a3b8; }
  .b-hidden{ background:rgba(254,202,202,.26); border-color:rgba(254,202,202,.55); color:#991b1b; }
  .b-hidden .dot{ background:#ef4444; }
  .b-sample{ background:#fef3c7; border-color:#fde68a; color:#92400e; }
  .b-sample .dot{ background:#f59e0b; }

  .price{ font-weight:900; color:var(--ink); }
  .sale{ color:#16a34a; font-weight:900; }
  .muted-sm{ color:var(--muted); font-size:.85rem; }

  .stock-pill{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:7px 10px;
    border-radius:999px;
    border:1px solid rgba(232,238,246,.95);
    background:#ffffff;
    color:#334155;
    font-weight:900;
    white-space:nowrap;
  }
  .stock-pill .dot{
    width:8px;
    height:8px;
    border-radius:999px;
    background:#22c55e;
  }
  .stock-pill.is-critical{
    background:#ffebeb;
    color:#b91c1c;
    border-color:rgba(255,74,74,.24);
  }
  .stock-pill.is-critical .dot{ background:#ff4a4a; }
  .stock-pill.is-empty{
    background:#f8fafc;
    color:#64748b;
  }
  .stock-pill.is-empty .dot{ background:#94a3b8; }
  .stock-meta{
    margin-top:6px;
    font-size:.78rem;
    color:var(--muted);
    font-weight:800;
    white-space:nowrap;
  }
  .stock-critical-badge{ margin-top:6px; }
  tr.is-critical-row td{
    background:linear-gradient(90deg, rgba(255,235,235,.72), #ffffff 42%) !important;
  }
  /* Fila de muestra: tinte ámbar para reconocerla de un vistazo */
  tr.is-sample-row td{
    background:linear-gradient(90deg, rgba(254,243,199,.58), #ffffff 46%) !important;
  }

  .actions{ display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }

  .foot{display:flex; align-items:center; justify-content:space-between; gap:12px; margin:16px 4px; flex-wrap:wrap;}

  .stock-modal,
  .dl-modal{
    position:fixed;
    inset:0;
    display:flex;
    align-items:center;
    justify-content:center;
    z-index:1200;
    pointer-events:none;
    opacity:0;
    transition:opacity .18s ease;
  }
  .dl-modal{ z-index:1150; }
  .stock-modal.is-open,
  .dl-modal.is-open{
    pointer-events:auto;
    opacity:1;
  }
  .stock-modal__overlay,
  .dl-modal__overlay{
    position:absolute;
    inset:0;
    background:rgba(15,23,42,.45);
    backdrop-filter:blur(4px);
  }
  .stock-modal__card,
  .dl-modal__card{
    position:relative;
    z-index:1;
    width:100%;
    max-width:380px;
    background:#ffffff;
    border-radius:18px;
    box-shadow:0 24px 70px rgba(15,23,42,.45);
    border:1px solid rgba(226,232,240,.9);
    padding:18px 18px 16px;
  }
  .stock-modal__card{ max-width:360px; }

  .stock-modal__head,
  .dl-modal__head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:10px;
    margin-bottom:10px;
  }
  .stock-modal__title,
  .dl-modal__title{
    margin:0;
    font-size:15px;
    font-weight:900;
    letter-spacing:-.01em;
    color:var(--ink);
  }
  .stock-modal__subtitle,
  .dl-modal__subtitle{
    margin:4px 0 0;
    font-size:.85rem;
    color:var(--muted);
  }
  .stock-modal__close,
  .dl-modal__close{
    width:32px;
    height:32px;
    border-radius:12px;
    border:1px solid var(--line);
    background:#fff;
    display:grid;
    place-items:center;
    cursor:pointer;
    box-shadow:0 8px 20px rgba(15,23,42,.12);
  }
  .stock-modal__close svg,
  .dl-modal__close svg{ width:16px; height:16px; }

  .stock-modal__body{ margin-top:8px; }
  .stock-field-label{
    font-size:.78rem;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.08em;
    color:#94a3b8;
    margin-bottom:4px;
  }
  .stock-input-wrap{
    display:flex;
    align-items:center;
    gap:8px;
  }
  .stock-input{
    flex:1;
    border-radius:999px;
    border:1px solid var(--line);
    padding:8px 12px;
    font-size:.95rem;
    text-align:right;
  }
  .stock-input:focus{
    outline:none;
    border-color:var(--acc-ring);
    box-shadow:0 0 0 1px var(--acc-soft);
  }
  .stock-modal__foot{
    display:flex;
    justify-content:flex-end;
    gap:8px;
    margin-top:16px;
  }

  .dl-modal__body{
    margin-top:8px;
    display:grid;
    gap:10px;
  }

  @media (max-width: 760px){
    .wrap{ padding:0 10px; }
    body{ padding-bottom: 86px; }

    .filter-tools{ display:none !important; }

    .head .tt-new,
    .head .tt-download{
      display:none !important;
    }

    .table-wrap{ border:0; background:transparent; overflow:visible; box-shadow:none; }
    table, thead, tbody, th, td, tr{ display:block; }
    thead{ display:none; }
    table{ background:transparent; }

    tbody tr{
      background:#fff;
      border:1px solid var(--line);
      border-radius:16px;
      box-shadow:0 14px 30px rgba(15,23,42,.06);
      padding:12px;
      margin:12px 0;
    }
    /* En móvil resaltamos la card de muestra con borde ámbar */
    tbody tr.is-sample-row{
      border-color:#fde68a;
      box-shadow:0 14px 30px rgba(245,158,11,.12);
    }
    tbody td{ border:0; padding:0; background:transparent !important; }
    .stock-mobile-space{ margin-top:10px; }

    td.img-cell{ width:auto !important; max-width:none !important; margin-bottom:10px; }
    .thumbbox{ width:74px; height:74px; border-radius:16px; }

    .actions{ justify-content:flex-start; margin-top:12px; padding-top:10px; border-top:1px dashed rgba(232,238,246,.9); }
    .iconbtn{ width:44px; height:44px; border-radius:16px; }

    .stock-modal__card,
    .dl-modal__card{
      max-width:92%;
    }
  }

  .fab{
    position:fixed; right:16px; bottom:18px;
    width:58px; height:58px; border-radius:999px;
    border:1px solid rgba(232,238,246,.9);
    background:rgba(255,255,255,.92);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    box-shadow:0 18px 44px rgba(15,23,42,.18);
    display:none; place-items:center;
    z-index:1000; cursor:pointer;
    transition:transform .14s ease, box-shadow .14s ease;
  }
  .fab:hover{ transform: translateY(-2px); box-shadow:0 22px 54px rgba(15,23,42,.22); }
  .fab svg{ width:22px; height:22px; color: var(--acc-ink); }

  .sheet-overlay{
    position:fixed; inset:0;
    background:rgba(15,23,42,.42);
    opacity:0; pointer-events:none;
    transition:opacity .18s ease;
    z-index:1001;
  }
  .sheet{
    position:fixed; left:0; right:0;
    bottom:-85%;
    background:#fff;
    border-top-left-radius:20px;
    border-top-right-radius:20px;
    border:1px solid rgba(232,238,246,.9);
    box-shadow:0 -18px 50px rgba(15,23,42,.25);
    z-index:1002;
    transition: bottom .22s ease;
    padding:12px 14px 16px;
  }
  .sheet .grab{ width:44px; height:5px; border-radius:999px; background:#e5e7eb; margin:4px auto 10px; }
  .sheet .sheet-title{ display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px; }
  .sheet .sheet-title h3{ margin:0; font-size:15px; font-weight:900; color:var(--ink); }
  .sheet .sheet-close{
    width:36px; height:36px; border-radius:12px;
    border:1px solid var(--line);
    background:#fff;
    display:grid; place-items:center;
    cursor:pointer;
    box-shadow:0 10px 18px rgba(15,23,42,.06);
    transition:transform .12s ease, box-shadow .12s ease;
  }
  .sheet .sheet-close:hover{ transform:translateY(-1px); box-shadow:0 14px 24px rgba(15,23,42,.08); }
  .sheet .sheet-close svg{ width:18px; height:18px; }

  .sheet .sf{ display:grid; gap:12px; }
  .sheet .tabs{
    width:100%;
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
    justify-content:flex-start;
  }
  .sheet .tabs::-webkit-scrollbar{ height:0; }
  .sheet .chip{ width:100%; justify-content:center; }
  .sheet .btn{ width:100%; justify-content:center; padding:12px 14px; border-radius:16px; }

  .sheet-section-label{
    font-size:.78rem;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.08em;
    color:#9ca3af;
    margin-top:4px;
  }

  .sheet-open .sheet-overlay{ opacity:1; pointer-events:auto; }
  .sheet-open .sheet{ bottom:0; }

  @media (max-width: 760px){ .fab{ display:grid; } }

  .swal2-popup.sa-popup{
    border-radius:18px;
    padding:24px 24px 20px;
    box-shadow:0 22px 60px rgba(15,23,42,.32);
    border:1px solid rgba(226,232,240,.95);
    font-family:inherit;
  }
  .swal2-icon{ box-shadow:none !important; }
  .swal2-popup.sa-popup .swal2-icon{
    margin-top:0;
    margin-bottom:6px;
  }
  .swal2-title.sa-title{
    margin:6px 0 2px;
    font-size:1.35rem;
    font-weight:800;
    letter-spacing:-.01em;
    color:var(--ink);
  }
  .swal2-html-container.sa-text{
    margin:4px 0 0;
    font-size:.95rem;
    color:var(--muted);
  }
  .swal2-actions{
    margin-top:18px;
    gap:10px;
  }
  .swal2-confirm.sa-confirm,
  .swal2-cancel.sa-cancel{
    border-radius:999px;
    font-weight:700;
    font-size:.9rem;
    padding:9px 18px;
    box-shadow:none;
  }
  .swal2-confirm.sa-confirm{
    background:var(--acc-ink);
    color:#fff;
    border:0;
  }
  .swal2-confirm.sa-confirm:hover{ filter:brightness(1.05); }
  .swal2-cancel.sa-cancel{
    background:#f9fafb;
    color:#4b5563;
    border:1px solid #e5e7eb;
  }
  .swal2-cancel.sa-cancel:hover{ background:#f3f4f6; }

  .swal2-popup.sa-toast{
    border-radius:999px;
    padding:10px 14px;
    box-shadow:0 18px 44px rgba(15,23,42,.35);
    border:1px solid rgba(148,163,184,.35);
    background:rgba(15,23,42,.96);
    color:#e5e7eb;
  }
  .swal2-popup.sa-toast .swal2-title.sa-toast-title{
    font-size:.9rem;
    font-weight:600;
  }
  .swal2-popup.sa-toast .swal2-icon{
    margin:0 8px 0 0;
    transform:scale(.8);
  }
  .swal2-popup.sa-toast .swal2-icon.swal2-success{
    border-color:#22c55e;
    color:#bbf7d0;
  }
  .swal2-popup.sa-toast .swal2-icon.swal2-error{
    border-color:#fecaca;
    color:#fecaca;
  }

  .pagi{
    display:flex;
    align-items:center;
    justify-content:flex-end;
    gap:8px;
    flex-wrap:wrap;
  }

  .pagi .page{
    height:40px;
    min-width:40px;
    padding:0 12px;
    border-radius:14px;
    border:1px solid var(--line);
    background:#fff;
    color:#334155;
    font-weight:900;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    text-decoration:none;
    box-shadow:0 10px 18px rgba(15,23,42,.05);
    transition:transform .12s ease, box-shadow .12s ease, background .12s ease, border-color .12s ease;
  }

  .pagi .page:hover{
    transform:translateY(-1px);
    box-shadow:0 14px 26px rgba(15,23,42,.08);
    border-color:rgba(52,211,153,.28);
  }

  .pagi .page.is-active{
    background:var(--acc-soft);
    border-color:var(--acc-ring);
    color:var(--acc-ink);
    box-shadow:0 14px 26px rgba(52,211,153,.12);
  }

  .pagi .page.is-disabled{
    opacity:.45;
    pointer-events:none;
    box-shadow:none;
  }

  .pagi .page.is-ellipsis{
    opacity:.8;
    pointer-events:none;
    box-shadow:none;
  }

  .pagi .page svg{
    width:18px;
    height:18px;
    display:block;
  }

  @media (max-width: 760px){
    .pagi{ justify-content:center; }
    .pagi .page{ height:44px; min-width:44px; border-radius:16px; }
  }
</style>
@endpush

@section('content')
@php
  $st = (string) request('status', '');

  $unitLabel = function ($item) {
      if (!$item) {
          return 'Pieza';
      }

      if (method_exists($item, 'unitMeasureLabel')) {
          return $item->unitMeasureLabel();
      }

      $unit = strtolower(trim((string) ($item->unit_measure ?? 'pieza')));

      $labels = [
          'pieza'   => 'Pieza',
          'caja'    => 'Caja',
          'paquete' => 'Paquete',
          'rollo'   => 'Rollo',
          'juego'   => 'Juego',
          'kit'     => 'Kit',
          'bolsa'   => 'Bolsa',
          'par'     => 'Par',
          'set'     => 'Set',
          'display' => 'Display',
          'docena'  => 'Docena',
          'metro'   => 'Metro',
          'litro'   => 'Litro',
      ];

      return $labels[$unit] ?? ucfirst($unit ?: 'pieza');
  };

  // Modo de muestras: '' = catálogo, 'only' = solo muestras, 'all' = todos
  $samplesMode = (string) request('samples', '');
  $samplesOnly = $samplesMode === 'only';
  $hasFilters  = request()->hasAny(['s','status','featured_only','samples']);

  // Título según el modo
  $tituloLista = match ($samplesMode) {
      'only' => 'Muestras Jureto',
      'all'  => 'Inventario Jureto (todos)',
      default => 'Inventario Jureto',
  };
@endphp

<div class="wrap">

  <div class="head">
    <div>
      <h1 class="title">{{ $tituloLista }}</h1>
      <p class="muted subtxt">
        @if($samplesMode === 'only')
          Productos marcados como muestra (no se venden ni se publican en marketplaces).
        @elseif($samplesMode === 'all')
          Catálogo de venta y muestras juntos. Las muestras aparecen resaltadas en ámbar.
        @else
          Gestiona el catálogo público y sincroniza con Mercado Libre con acciones rápidas.
        @endif
      </p>
    </div>

    <div class="head-actions">
      <div class="tt tt-download">
        <span class="tt-bubble">Ver resumen profesional del inventario</span>
        <a href="{{ route('admin.catalog.analytics') }}" class="btn btn-sm">
          <span class="ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 19V5"/>
              <path d="M4 19h16"/>
              <path d="M8 16V9"/>
              <path d="M13 16V6"/>
              <path d="M18 16v-4"/>
            </svg>
          </span>
          Analíticas
        </a>
      </div>

      <div class="tt tt-download">
        <span class="tt-bubble">Descargar listado (Excel o PDF)</span>
        <button type="button" class="btn btn-sm" id="downloadOpenBtn">
          <span class="ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 3v12"/><path d="M7 11l5 5 5-5"/><path d="M5 19h14"/>
            </svg>
          </span>
          Descargar
        </button>
      </div>

      <div class="tt tt-new">
        <span class="tt-bubble">Crear nuevo producto</span>
        <a href="{{ route('admin.catalog.create') }}" class="btn">
          <span class="ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 5v14M5 12h14"/>
            </svg>
          </span>
          Nuevo
        </a>
      </div>
    </div>
  </div>

  <div class="filters">
    <form id="filtersForm" method="GET" action="{{ route('admin.catalog.index') }}" class="filters-row">
      <div class="tt" style="flex:1; min-width:0;">
        <span class="tt-bubble">Buscar por nombre o SKU</span>
        <div class="search">
          <span class="sico">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
            </svg>
          </span>
          <input id="sInput" type="search" name="s" placeholder="Buscar por nombre o SKU…" value="{{ request('s') }}" autocomplete="off" />
        </div>
      </div>

      <div class="filter-tools">
        <div class="tt">
          <span class="tt-bubble">Filtrar por estado</span>
          <div class="tabs" role="tablist" aria-label="Estado">
            <button type="button" class="tab {{ $st==='' ? 'is-active' : '' }}" data-status="">Todos</button>
            <button type="button" class="tab {{ $st==='1' ? 'is-active' : '' }}" data-status="1">Publicado</button>
            <button type="button" class="tab {{ $st==='0' ? 'is-active' : '' }}" data-status="0">Borrador</button>
            <button type="button" class="tab {{ $st==='2' ? 'is-active' : '' }}" data-status="2">Oculto</button>
          </div>
        </div>

        <div class="tt">
          <span class="tt-bubble">Mostrar solo destacados</span>
          <label class="chip">
            <input id="featuredInput" type="checkbox" name="featured_only" value="1" @checked(request()->boolean('featured_only'))>
            Destacados
          </label>
        </div>

        <div class="tt">
          <span class="tt-bubble">Catálogo, solo muestras o todos juntos</span>
          <div class="tabs tabs-samples" role="tablist" aria-label="Muestras">
            <button type="button" class="tab {{ $samplesMode==='' ? 'is-active' : '' }}" data-samples="">Catálogo</button>
            <button type="button" class="tab {{ $samplesMode==='only' ? 'is-active' : '' }}" data-samples="only">Muestras</button>
            <button type="button" class="tab {{ $samplesMode==='all' ? 'is-active' : '' }}" data-samples="all">Todos</button>
          </div>
        </div>

        @if($hasFilters)
          <div class="tt">
            <span class="tt-bubble">Quitar filtros</span>
            <a href="{{ route('admin.catalog.index') }}" class="btn btn-sm" style="padding:10px 12px;">
              <span class="ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M3 12a9 9 0 1 0 9-9"/><path d="M3 4v8h8"/>
                </svg>
              </span>
              Limpiar
            </a>
          </div>
        @endif
      </div>

      <input type="hidden" name="status" id="statusInput" value="{{ $st }}">
      <input type="hidden" name="samples" id="samplesInput" value="{{ $samplesMode }}">
    </form>
  </div>

  <div class="table-wrap card">
    <table>
      <thead>
        <tr>
          <th class="img-cell">Img</th>
          <th>Producto</th>
          <th>Precio</th>
          <th>Stock</th>
          <th>Estado</th>
          <th>Destacado</th>
          <th>Publicado</th>
          <th style="text-align:right;">Acciones</th>
        </tr>
      </thead>

      <tbody>
        @forelse($items as $it)
          @php
            $imgPath = $it->photo_1 ?: ($it->photo_2 ?: $it->photo_3);
            $imgUrl  = $imgPath
              ? \Illuminate\Support\Facades\Storage::url($imgPath)
              : asset('images/placeholder.png');

            $stockActual = (float)($it->stock ?? 0);
            $stockMinimo = $it->stock_min !== null ? (float)$it->stock_min : null;
            $stockMaximo = $it->stock_max !== null ? (float)$it->stock_max : null;
            $stockCritico = $stockMinimo !== null && $stockActual <= $stockMinimo;
            $sinStock = $stockActual <= 0;
            $stockUnit = $unitLabel($it);

            $esMuestra = (bool)($it->is_sample ?? false);
            $muestraLabel = ($esMuestra && method_exists($it, 'sampleStatusLabel')) ? $it->sampleStatusLabel() : null;
            $muestraHolder = $esMuestra ? trim((string)($it->sample_holder ?? '')) : '';

            // Prioridad de color de fila: crítico > muestra
            $rowClass = $stockCritico ? 'is-critical-row' : ($esMuestra ? 'is-sample-row' : '');
          @endphp

          <tr class="{{ $rowClass }}">
            <td class="img-cell">
              <div class="thumbbox">
                <img
                  src="{{ $imgUrl }}"
                  alt="Imagen de {{ $it->name }}"
                  loading="lazy"
                  onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}';"
                >
              </div>
            </td>

            <td>
              <div class="name">
                <strong>{{ $it->name }}</strong>
                <div class="meta">
                  <span><span class="k">SKU:</span> <span class="v">{{ $it->sku ?: '—' }}</span></span>
                  <span><span class="k">Slug:</span> <span class="v">{{ $it->slug }}</span></span>
                  @if($it->meli_item_id || $it->meli_status)
                    <span><span class="k">ML ID:</span> <span class="v">{{ $it->meli_item_id ?: '—' }}</span></span>
                  @endif
                </div>

                <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:6px;">
                  @if($esMuestra)
                    <span class="badge b-sample">
                      <span class="dot"></span>Muestra{{ $muestraLabel ? ' · '.$muestraLabel : '' }}
                    </span>
                    @if($muestraHolder !== '')
                      <span class="badge" style="background:#fff7ed;border-color:#fed7aa;color:#9a3412;">
                        <span class="dot" style="background:#fb923c"></span>Con: {{ $muestraHolder }}
                      </span>
                    @endif
                  @endif

                  @if($it->status === 1)
                    <span class="badge b-live"><span class="dot"></span>Publicado</span>
                  @elseif($it->status === 2)
                    <span class="badge b-hidden"><span class="dot"></span>Oculto</span>
                  @else
                    <span class="badge b-draft"><span class="dot"></span>Borrador</span>
                  @endif

                  @if($it->is_featured)
                    <span class="badge" style="background:rgba(52,211,153,.16);border-color:rgba(52,211,153,.28);color:#065f46;">
                      <span class="dot" style="background:#22c55e"></span>Destacado
                    </span>
                  @endif

                  @if($stockCritico)
                    <span class="badge" style="background:#ffebeb;border-color:rgba(255,74,74,.24);color:#ff4a4a;">
                      <span class="dot" style="background:#ff4a4a"></span>Stock crítico
                    </span>
                  @endif
                </div>

                @if(!empty($it->meli_last_error))
                  <details style="margin-top:8px;">
                    <summary class="muted-sm" style="cursor:pointer;color:#b91c1c;font-weight:800;">Error Mercado Libre</summary>
                    <div class="muted-sm" style="margin-top:6px;color:#7f1d1d; white-space:normal; max-width:740px;">
                      {{ $it->meli_last_error }}
                    </div>
                  </details>
                @endif
              </div>
            </td>

            <td>
              @if(!is_null($it->sale_price))
                <div class="sale">${{ number_format($it->sale_price,2) }}</div>
                <div class="muted-sm" style="text-decoration:line-through;">${{ number_format($it->price,2) }}</div>
              @else
                <div class="price">${{ number_format($it->price,2) }}</div>
              @endif
            </td>

            <td class="stock-mobile-space">
              <span class="stock-pill {{ $stockCritico ? 'is-critical' : '' }} {{ $sinStock ? 'is-empty' : '' }}">
                <span class="dot"></span>
                {{ number_format($stockActual, 0) }} {{ $stockUnit }}
              </span>

              <div class="stock-meta">
                Mín: {{ $stockMinimo !== null ? number_format($stockMinimo, 0) . ' ' . $stockUnit : '—' }}
                · Máx: {{ $stockMaximo !== null ? number_format($stockMaximo, 0) . ' ' . $stockUnit : '—' }}
              </div>

              @if($stockCritico)
                <div class="stock-critical-badge">
                  <span class="badge" style="background:#ffebeb;border-color:rgba(255,74,74,.24);color:#ff4a4a;">
                    <span class="dot" style="background:#ff4a4a"></span>Stock crítico
                  </span>
                </div>
              @endif
            </td>

            <td>
              @if($esMuestra)
                <span class="badge b-sample"><span class="dot"></span>Muestra</span>
              @elseif($it->status === 1)
                <span class="badge b-live"><span class="dot"></span>Publicado</span>
              @elseif($it->status === 2)
                <span class="badge b-hidden"><span class="dot"></span>Oculto</span>
              @else
                <span class="badge b-draft"><span class="dot"></span>Borrador</span>
              @endif
            </td>

            <td>
              @if($it->is_featured)
                <span class="badge" style="background:rgba(52,211,153,.16);border-color:rgba(52,211,153,.28);color:#065f46;">
                  <span class="dot" style="background:#22c55e"></span>Sí
                </span>
              @else
                <span class="muted">—</span>
              @endif
            </td>

            <td>
              <span class="muted">{{ $it->published_at ? $it->published_at->format('Y-m-d H:i') : '—' }}</span>
            </td>

            <td style="text-align:right;">
              <div class="actions">
                <span class="tt iconbtn-wrap">
                  <span class="tt-bubble">Vista Previa</span>
                  <a class="iconbtn" href="{{ route('catalog.preview', $it) }}" target="_blank">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12z"/>
                      <circle cx="12" cy="12" r="3"/>
                    </svg>
                  </a>
                </span>

                <span class="tt iconbtn-wrap">
                  <span class="tt-bubble">Actualizar stock</span>
                  <button type="button"
                          class="iconbtn js-open-stock"
                          data-name="{{ $it->name }}"
                          data-stock="{{ (float)($it->stock ?? 0) }}"
                          data-action="{{ route('admin.catalog.stock.update', $it) }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <rect x="3" y="4" width="18" height="14" rx="2"/>
                      <path d="M8 9h8M8 13h4"/>
                    </svg>
                  </button>
                </span>

                <span class="tt iconbtn-wrap">
                  <span class="tt-bubble">Editar</span>
                  <a class="iconbtn" href="{{ route('admin.catalog.edit', $it) }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                    </svg>
                  </a>
                </span>

                <span class="tt iconbtn-wrap">
                  <span class="tt-bubble">{{ $it->status == 1 ? 'Ocultar' : 'Publicar' }}</span>
                  <form method="POST"
                        action="{{ route('admin.catalog.toggle', $it) }}"
                        class="js-sa-confirm"
                        data-sa-title="¿Cambiar estado de publicación?"
                        data-sa-text="Se actualizará el estado de este producto en el sitio web."
                        data-sa-icon="question">
                    @csrf @method('PATCH')
                    <button class="iconbtn" type="submit">
                      @if($it->status == 1)
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12z"/><path d="M3 3l18 18"/>
                        </svg>
                      @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M3 11v2"/><path d="M5 10v4"/><path d="M7 9v6"/><path d="M9 8l10-3v14l-10-3V8z"/><path d="M11 16l1 4"/>
                        </svg>
                      @endif
                    </button>
                  </form>
                </span>

                @unless($esMuestra)
                  <span class="tt iconbtn-wrap">
                    <span class="tt-bubble">ML: Publicar/Actualizar</span>
                    <form method="POST"
                          action="{{ route('admin.catalog.meli.publish', $it) }}"
                          class="js-sa-confirm"
                          data-sa-title="¿Enviar a Mercado Libre?"
                          data-sa-text="Se publicará o actualizará el anuncio en Mercado Libre."
                          data-sa-icon="info">
                      @csrf
                      <button class="iconbtn" type="submit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M12 21V8"/><path d="M7 12l5-5 5 5"/><path d="M20 21H4"/>
                        </svg>
                      </button>
                    </form>
                  </span>

                  @if($it->meli_item_id)
                    <span class="tt iconbtn-wrap">
                      <span class="tt-bubble">ML: Ver</span>
                      <a class="iconbtn" href="{{ route('admin.catalog.meli.view', $it) }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M14 3h7v7"/><path d="M10 14L21 3"/><path d="M21 14v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6"/>
                        </svg>
                      </a>
                    </span>

                    <span class="tt iconbtn-wrap">
                      <span class="tt-bubble">ML: Pausar</span>
                      <form method="POST"
                            action="{{ route('admin.catalog.meli.pause', $it) }}"
                            class="js-sa-confirm"
                            data-sa-title="¿Pausar en Mercado Libre?"
                            data-sa-text="El anuncio quedará pausado."
                            data-sa-icon="warning">
                        @csrf
                        <button class="iconbtn" type="submit">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/>
                          </svg>
                        </button>
                      </form>
                    </span>

                    <span class="tt iconbtn-wrap">
                      <span class="tt-bubble">ML: Activar</span>
                      <form method="POST"
                            action="{{ route('admin.catalog.meli.activate', $it) }}"
                            class="js-sa-confirm"
                            data-sa-title="¿Activar en Mercado Libre?"
                            data-sa-text="El anuncio volverá a estar activo."
                            data-sa-icon="success">
                        @csrf
                        <button class="iconbtn" type="submit">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="8 5 19 12 8 19 8 5"/>
                          </svg>
                        </button>
                      </form>
                    </span>
                  @endif
                @endunless

                <span class="tt iconbtn-wrap">
                  <span class="tt-bubble">Eliminar</span>
                  <form method="POST"
                        action="{{ route('admin.catalog.destroy', $it) }}"
                        class="js-sa-confirm"
                        data-sa-title="¿Eliminar producto?"
                        data-sa-text="Esta acción no se puede deshacer."
                        data-sa-icon="error">
                    @csrf @method('DELETE')
                    <button class="iconbtn" type="submit" style="border-color:rgba(254,202,202,.75);">
                      <svg viewBox="0 0 24 24" fill="none" stroke="#b91c1c" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        <line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>
                      </svg>
                    </button>
                  </form>
                </span>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="muted" style="text-align:center; padding:28px;">
              @if($samplesMode === 'only')
                No hay muestras registradas.
              @else
                No hay productos que coincidan con el filtro.
              @endif
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="foot">
    <div class="muted">
      Mostrando {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} de {{ $items->total() }} registros
    </div>
    <div>
      @php
        $items->appends(request()->query());
        $links = $items->toArray()['links'] ?? [];
      @endphp

      <nav class="pagi" aria-label="Paginación">
        @foreach($links as $link)
          @php
            $label = strip_tags($link['label']);
            $isPrev = $loop->first;
            $isNext = $loop->last;
            $isDots = ($label === '...' || $label === '…');
            $url = $link['url'];
            $active = (bool)($link['active'] ?? false);
            $disabled = is_null($url) && !$active && !$isDots;
          @endphp

          @if($isPrev)
            <a class="page {{ $disabled ? 'is-disabled' : '' }}"
               href="{{ $url ?: 'javascript:void(0)' }}"
               aria-label="Anterior">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 18l-6-6 6-6"/>
              </svg>
            </a>
          @elseif($isNext)
            <a class="page {{ $disabled ? 'is-disabled' : '' }}"
               href="{{ $url ?: 'javascript:void(0)' }}"
               aria-label="Siguiente">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 6l6 6-6 6"/>
              </svg>
            </a>
          @elseif($isDots)
            <span class="page is-ellipsis" aria-hidden="true">…</span>
          @else
            <a class="page {{ $active ? 'is-active' : '' }} {{ $url ? '' : 'is-disabled' }}"
               href="{{ $url ?: 'javascript:void(0)' }}"
               aria-label="Página {{ $label }}">
              {{ $label }}
            </a>
          @endif
        @endforeach
      </nav>
    </div>
  </div>
</div>

<div id="stockModal" class="stock-modal">
  <div class="stock-modal__overlay"></div>
  <div class="stock-modal__card">
    <div class="stock-modal__head">
      <div>
        <h3 class="stock-modal__title">Ajustar stock</h3>
        <p class="stock-modal__subtitle" id="stockProductName">Producto</p>
      </div>
      <button type="button" class="stock-modal__close" id="stockCloseBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6L6 18M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <form id="stockForm" method="POST" action="">
      @csrf
      @method('PATCH')

      <div class="stock-modal__body">
        <div class="stock-field-label">Existencia actual</div>
        <div class="stock-input-wrap">
          <input type="number"
                 step="0.01"
                 min="0"
                 name="stock"
                 id="stockInput"
                 class="stock-input"
                 placeholder="0.00">
          <span class="muted-sm">unid.</span>
        </div>
      </div>

      <div class="stock-modal__foot">
        <button type="button" class="btn btn-sm btn-ghost" id="stockCancelBtn">Cancelar</button>
        <button type="submit" class="btn btn-sm">Guardar</button>
      </div>
    </form>
  </div>
</div>

<div id="downloadModal" class="dl-modal">
  <div class="dl-modal__overlay"></div>
  <div class="dl-modal__card">
    <div class="dl-modal__head">
      <div>
        <h3 class="dl-modal__title">Descargar listado</h3>
        <p class="dl-modal__subtitle">Incluye los productos con los filtros actuales.</p>
      </div>
      <button type="button" class="dl-modal__close" id="downloadCloseBtn" aria-label="Cerrar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6L6 18M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <div class="dl-modal__body">
      <a href="{{ route('admin.catalog.export.excel', request()->query()) }}"
         class="btn">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 3h16v18H4z"/><path d="M8 7l8 10"/><path d="M16 7L8 17"/>
          </svg>
        </span>
        Excel (.xlsx)
      </a>

      <a href="{{ route('admin.catalog.analytics.pdf', request()->query()) }}"
         class="btn btn-soft">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 19V5"/>
            <path d="M4 19h16"/>
            <path d="M8 16V9"/>
            <path d="M13 16V6"/>
            <path d="M18 16v-4"/>
          </svg>
        </span>
        PDF profesional de analíticas
      </a>

      <a href="{{ route('admin.catalog.export.pdf', request()->query()) }}"
         class="btn btn-soft">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M6 2h9l5 5v15H6z"/><path d="M15 2v5h5"/><path d="M9 13h3"/><path d="M9 17h6"/>
          </svg>
        </span>
        PDF listado simple
      </a>
    </div>
  </div>
</div>

<button class="fab" id="fabOpen" type="button" aria-label="Abrir filtros">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M12 5v14M5 12h14"/>
  </svg>
</button>

<div class="sheet-overlay" id="sheetOverlay" aria-hidden="true"></div>

<div class="sheet" id="sheet" role="dialog" aria-modal="true" aria-label="Filtros" aria-hidden="true">
  <div class="grab"></div>
  <div class="sheet-title">
    <h3>Filtros</h3>
    <button class="sheet-close" type="button" id="sheetClose" aria-label="Cerrar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 6L6 18M6 6l12 12"/>
      </svg>
    </button>
  </div>

  <form id="sheetForm" method="GET" action="{{ route('admin.catalog.index') }}" class="sf">
    <input type="hidden" name="s" id="sMirror" value="{{ request('s') }}">

    <div class="sheet-section-label">Estado</div>
    <div class="tabs" role="tablist" aria-label="Estado (móvil)">
      <button type="button" class="tab {{ $st==='' ? 'is-active' : '' }}" data-status="">Todos</button>
      <button type="button" class="tab {{ $st==='1' ? 'is-active' : '' }}" data-status="1">Publicado</button>
      <button type="button" class="tab {{ $st==='0' ? 'is-active' : '' }}" data-status="0">Borrador</button>
      <button type="button" class="tab {{ $st==='2' ? 'is-active' : '' }}" data-status="2">Oculto</button>
    </div>

    <input type="hidden" name="status" id="statusSheet" value="{{ $st }}">

    <div class="sheet-section-label">Muestras</div>
    <div class="tabs tabs-samples" role="tablist" aria-label="Muestras (móvil)">
      <button type="button" class="tab {{ $samplesMode==='' ? 'is-active' : '' }}" data-samples-sheet="">Catálogo</button>
      <button type="button" class="tab {{ $samplesMode==='only' ? 'is-active' : '' }}" data-samples-sheet="only">Muestras</button>
      <button type="button" class="tab {{ $samplesMode==='all' ? 'is-active' : '' }}" data-samples-sheet="all">Todos</button>
    </div>

    <input type="hidden" name="samples" id="samplesSheetInput" value="{{ $samplesMode }}">

    <label class="chip">
      <input id="featuredSheet" type="checkbox" name="featured_only" value="1" @checked(request()->boolean('featured_only'))>
      Destacados
    </label>

    <a href="{{ route('admin.catalog.create') }}" class="btn">
      <span class="ico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 5v14M5 12h14"/>
        </svg>
      </span>
      Nuevo producto
    </a>

    @if($hasFilters)
      <a href="{{ route('admin.catalog.index') }}" class="btn btn-sm" style="padding:12px 14px;">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 12a9 9 0 1 0 9-9"/><path d="M3 4v8h8"/>
          </svg>
        </span>
        Limpiar filtros
      </a>
    @endif

    <a href="{{ route('admin.catalog.analytics') }}" class="btn btn-soft">
      <span class="ico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 19V5"/>
          <path d="M4 19h16"/>
          <path d="M8 16V9"/>
          <path d="M13 16V6"/>
          <path d="M18 16v-4"/>
        </svg>
      </span>
      Ver analíticas
    </a>

    <div class="sheet-section-label">Descargar listado</div>

    <a href="{{ route('admin.catalog.export.excel', request()->query()) }}"
       class="btn">
      <span class="ico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 3h16v18H4z"/><path d="M8 7l8 10"/><path d="M16 7L8 17"/>
        </svg>
      </span>
      Excel (.xlsx)
    </a>

    <a href="{{ route('admin.catalog.analytics.pdf', request()->query()) }}"
       class="btn btn-soft">
      <span class="ico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 19V5"/>
          <path d="M4 19h16"/>
          <path d="M8 16V9"/>
          <path d="M13 16V6"/>
          <path d="M18 16v-4"/>
        </svg>
      </span>
      PDF profesional de analíticas
    </a>

    <a href="{{ route('admin.catalog.export.pdf', request()->query()) }}"
       class="btn btn-soft">
      <span class="ico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M6 2h9l5 5v15H6z"/><path d="M15 2v5h5"/><path d="M9 13h3"/><path d="M9 17h6"/>
        </svg>
      </span>
      PDF listado simple
    </a>
  </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function(){
  function debounce(fn, wait){
    let t; return (...args)=>{ clearTimeout(t); t=setTimeout(()=>fn(...args), wait); };
  }
  function isMobile(){ return window.matchMedia('(max-width: 760px)').matches; }

  const form = document.getElementById('filtersForm');
  const sInput = document.getElementById('sInput');
  const statusInput = document.getElementById('statusInput');
  const tabs = Array.from(document.querySelectorAll('#filtersForm .tab[data-status]'));
  const featured = document.getElementById('featuredInput');

  // Pestañas de muestras (desktop)
  const samplesTabs = Array.from(document.querySelectorAll('#filtersForm [data-samples]'));
  const samplesHidden = document.getElementById('samplesInput');

  // --- BUSCADOR: no recargar tan rápido + devolver el foco después de recargar ---
  // Subimos el debounce a 800ms para que no te corte mientras escribes.
  const submitDebounced = debounce(()=> form?.submit(), 800);
  sInput?.addEventListener('input', submitDebounced);

  // Al cargar, si hay texto en el buscador, le devolvemos el foco
  // y ponemos el cursor al final para que puedas seguir escribiendo sin perder nada.
  window.addEventListener('DOMContentLoaded', ()=>{
    if(sInput && sInput.value){
      sInput.focus();
      const v = sInput.value;
      // truco para mover el cursor al final del texto
      sInput.value = '';
      sInput.value = v;
    }
  });

  tabs.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      tabs.forEach(x=>x.classList.remove('is-active'));
      btn.classList.add('is-active');
      if(statusInput) statusInput.value = btn.dataset.status ?? '';
      form?.submit();
    });
  });

  featured?.addEventListener('change', ()=> form?.submit());

  samplesTabs.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      samplesTabs.forEach(x=>x.classList.remove('is-active'));
      btn.classList.add('is-active');
      if(samplesHidden) samplesHidden.value = btn.dataset.samples ?? '';
      form?.submit();
    });
  });

  const root = document.documentElement;
  const fab = document.getElementById('fabOpen');
  const sheet = document.getElementById('sheet');
  const overlay = document.getElementById('sheetOverlay');
  const closeBtn = document.getElementById('sheetClose');

  function openSheet(){
    if(!isMobile()) return;
    root.classList.add('sheet-open');
    sheet?.setAttribute('aria-hidden','false');
    overlay?.setAttribute('aria-hidden','false');
  }
  function closeSheet(){
    root.classList.remove('sheet-open');
    sheet?.setAttribute('aria-hidden','true');
    overlay?.setAttribute('aria-hidden','true');
  }

  fab?.addEventListener('click', openSheet);
  overlay?.addEventListener('click', closeSheet);
  closeBtn?.addEventListener('click', closeSheet);
  document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') closeSheet(); });
  window.addEventListener('resize', ()=>{ if(!isMobile()) closeSheet(); });

  const sheetForm = document.getElementById('sheetForm');
  const sMirror = document.getElementById('sMirror');
  const statusSheet = document.getElementById('statusSheet');
  const tabSheet = Array.from(document.querySelectorAll('#sheetForm .tab[data-status]'));
  const featuredSheet = document.getElementById('featuredSheet');

  // Pestañas de muestras (móvil)
  const samplesSheetTabs = Array.from(document.querySelectorAll('#sheetForm [data-samples-sheet]'));
  const samplesSheetHidden = document.getElementById('samplesSheetInput');

  function syncSearchToSheet(){
    if(!sMirror || !sInput) return;
    sMirror.value = sInput.value || '';
  }
  sInput?.addEventListener('input', syncSearchToSheet);
  syncSearchToSheet();

  tabSheet.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      tabSheet.forEach(x=>x.classList.remove('is-active'));
      btn.classList.add('is-active');
      if(statusSheet) statusSheet.value = btn.dataset.status ?? '';
      syncSearchToSheet();
      sheetForm?.submit();
    });
  });

  featuredSheet?.addEventListener('change', ()=>{
    syncSearchToSheet();
    sheetForm?.submit();
  });

  samplesSheetTabs.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      samplesSheetTabs.forEach(x=>x.classList.remove('is-active'));
      btn.classList.add('is-active');
      if(samplesSheetHidden) samplesSheetHidden.value = btn.dataset.samplesSheet ?? '';
      syncSearchToSheet();
      sheetForm?.submit();
    });
  });

  const stockModal = document.getElementById('stockModal');
  const stockOverlay = stockModal?.querySelector('.stock-modal__overlay');
  const stockCloseBtn = document.getElementById('stockCloseBtn');
  const stockCancelBtn = document.getElementById('stockCancelBtn');
  const stockForm = document.getElementById('stockForm');
  const stockInput = document.getElementById('stockInput');
  const stockProductName = document.getElementById('stockProductName');

  function openStockModal(btn){
    if(!stockModal || !btn) return;
    const name  = btn.getAttribute('data-name') || 'Producto';
    const stock = btn.getAttribute('data-stock') || '0';
    const action = btn.getAttribute('data-action') || '';

    if(action){
      stockForm.setAttribute('action', action);
    }
    stockProductName.textContent = name;
    stockInput.value = stock;
    stockModal.classList.add('is-open');
    stockInput.focus();
    stockInput.select();
  }
  function closeStockModal(){
    stockModal?.classList.remove('is-open');
  }

  document.querySelectorAll('.js-open-stock').forEach(btn=>{
    btn.addEventListener('click', ()=> openStockModal(btn));
  });
  stockOverlay?.addEventListener('click', closeStockModal);
  stockCloseBtn?.addEventListener('click', closeStockModal);
  stockCancelBtn?.addEventListener('click', closeStockModal);

  const dlModal = document.getElementById('downloadModal');
  const dlOverlay = dlModal?.querySelector('.dl-modal__overlay');
  const dlOpenBtn = document.getElementById('downloadOpenBtn');
  const dlCloseBtn = document.getElementById('downloadCloseBtn');

  function openDownloadModal(){
    if(!dlModal) return;
    dlModal.classList.add('is-open');
  }
  function closeDownloadModal(){
    dlModal?.classList.remove('is-open');
  }

  dlOpenBtn?.addEventListener('click', openDownloadModal);
  dlOverlay?.addEventListener('click', closeDownloadModal);
  dlCloseBtn?.addEventListener('click', closeDownloadModal);

  document.addEventListener('keydown', (e)=>{
    if(e.key === 'Escape'){
      closeStockModal();
      closeDownloadModal();
    }
  });

  const saForms = document.querySelectorAll('form.js-sa-confirm');
  saForms.forEach(formEl=>{
    formEl.addEventListener('submit', function(e){
      e.preventDefault();
      if(!window.Swal){
        return formEl.submit();
      }
      const title = formEl.dataset.saTitle || '¿Estás seguro?';
      const text  = formEl.dataset.saText  || '';
      const icon  = formEl.dataset.saIcon  || 'warning';

      Swal.fire({
        title: title,
        text: text,
        icon: icon,
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        buttonsStyling:false,
        customClass:{
          popup:'sa-popup',
          title:'sa-title',
          htmlContainer:'sa-text',
          confirmButton:'sa-confirm',
          cancelButton:'sa-cancel',
        }
      }).then((result)=>{
        if(result.isConfirmed){
          formEl.submit();
        }
      });
    });
  });

  @if(session('ok'))
    if(window.Swal){
      Swal.fire({
        toast:true,
        position:'top-end',
        icon:'success',
        title:@json(session('ok')),
        showConfirmButton:false,
        timer:2600,
        timerProgressBar:true,
        buttonsStyling:false,
        customClass:{
          popup:'sa-toast',
          title:'sa-toast-title'
        }
      });
    }
  @endif

  @if(session('error'))
    if(window.Swal){
      Swal.fire({
        toast:true,
        position:'top-end',
        icon:'error',
        title:@json(session('error')),
        showConfirmButton:false,
        timer:3200,
        timerProgressBar:true,
        buttonsStyling:false,
        customClass:{
          popup:'sa-toast',
          title:'sa-toast-title'
        }
      });
    }
  @endif

})();
</script>
@endpush