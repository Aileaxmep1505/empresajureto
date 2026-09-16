{{--
   Inventario Jureto (admin.catalog.index)

   - Búsqueda en vivo SIN recargar: al escribir, la lista se filtra por AJAX y lo
     buscado se resalta en los resultados. Igual pestañas, filtros, orden,
     chips y paginación (todo lo que lleve data-ajax).
   - La URL se actualiza al vuelo (history), así F5 o "atrás" conservan el filtro.
   - Los parciales _lista, _kpis y _chips son lo que el servidor manda de vuelta.
   - En móvil, las herramientas de filtro se mueven a la hoja inferior (mismo
     formulario, sin duplicar controles).
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

    --amb:#f59e0b; --amb-ink:#92400e; --amb-soft:#fef3c7;
    --red:#ef4444; --red-ink:#b91c1c; --red-soft:#ffebeb;

    --tt-bg:#111827;
    --tt-fg:#ffffff;
  }

  html,body{background:var(--bg)}
  .wrap{max-width:1240px; margin-inline:auto; padding:0 14px}
  .card{background:var(--surface); border:1px solid var(--line); border-radius:var(--r); box-shadow:var(--shadow);}

  /* ===================== Encabezado ===================== */
  .head{ display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; margin:14px 0 12px; }
  .title{font-weight:900; color:var(--ink); letter-spacing:-.02em; margin:0}
  .muted{color:var(--muted)}
  .subtxt{margin-top:6px;font-size:.92rem;max-width:70ch}
  .head-actions{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; }

  .btn{
    display:inline-flex; align-items:center; justify-content:center; gap:10px;
    border:1px solid transparent; cursor:pointer; text-decoration:none;
    font-weight:800; border-radius:14px; padding:10px 14px; font-family:inherit; font-size:.95rem;
    transition:transform .12s ease, box-shadow .12s ease, background .12s ease, color .12s ease, border-color .12s ease;
    box-shadow:0 10px 22px rgba(15,23,42,.06); user-select:none;
    background:var(--acc-soft); color:var(--acc-ink); border-color:var(--acc-ring);
  }
  .btn:hover{ transform:translateY(-1px); background:#fff; color:#111827; border-color:var(--line); box-shadow:0 14px 28px rgba(15,23,42,.08); }
  .btn:active{ transform:translateY(0); box-shadow:0 10px 22px rgba(15,23,42,.06); }
  .btn-sm{ padding:8px 10px; border-radius:12px; font-size:.92rem; }
  .btn-ghost{ background:#f9fafb; border-color:#e5e7eb; color:#4b5563; box-shadow:none; }
  .btn-ghost:hover{ background:#f3f4f6; }
  .btn-soft{ background:#f9fafb; border-color:#e5e7eb; color:#111827; }
  .btn-soft:hover{ background:#ffffff; }

  .ico{ width:18px; height:18px; display:inline-block; }
  .ico svg{ width:18px; height:18px; display:block; }

  /* ===================== Tooltips ===================== */
  .tt{ position:relative; display:inline-flex; }
  .tt .tt-bubble{
    position:absolute; left:50%; bottom:calc(100% + 10px); transform:translateX(-50%);
    background:var(--tt-bg); color:var(--tt-fg); font-size:12px; font-weight:700;
    padding:8px 10px; border-radius:12px; white-space:nowrap; opacity:0; pointer-events:none;
    box-shadow:0 14px 30px rgba(0,0,0,.18); transition:opacity .14s ease, transform .14s ease; z-index:20;
  }
  .tt .tt-bubble:before{
    content:""; position:absolute; left:50%; bottom:-6px; width:12px; height:12px;
    background:var(--tt-bg); transform:translateX(-50%) rotate(45deg); border-radius:2px;
  }
  .tt:hover .tt-bubble{ opacity:1; transform:translateX(-50%) translateY(-2px); }
  @media (hover:none){ .tt .tt-bubble{ display:none !important; } }

  .iconbtn-wrap{ display:inline-flex; position:relative; }
  .iconbtn{
    width:36px; height:36px; border-radius:11px; border:1px solid var(--line); background:#fff;
    display:inline-grid; place-items:center; cursor:pointer; color:#334155;
    transition:transform .12s ease, box-shadow .12s ease, background .12s ease, border-color .12s ease;
    box-shadow:0 6px 14px rgba(15,23,42,.04);
  }
  .iconbtn:hover{ transform:translateY(-1px); border-color:var(--acc-ring); box-shadow:0 12px 24px rgba(15,23,42,.08); }
  .iconbtn svg{ width:17px; height:17px; }
  .iconbtn.is-danger{ border-color:rgba(254,202,202,.75); color:var(--red-ink); }
  .iconbtn.is-danger:hover{ background:#fff5f5; border-color:#fca5a5; }

  /* ===================== Resumen (KPIs) ===================== */
  .kpis{ display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:10px; margin-bottom:12px; }
  .kpi{
    display:flex; align-items:center; gap:12px; padding:12px 14px;
    background:#fff; border:1px solid var(--line); border-radius:14px; box-shadow:0 8px 18px rgba(15,23,42,.04);
  }
  .kpi .kpi-ico{ width:38px; height:38px; border-radius:11px; display:grid; place-items:center; flex:0 0 auto; background:var(--acc-soft); color:var(--acc-ink); }
  .kpi .kpi-ico svg{ width:18px; height:18px; }
  .kpi.is-amb .kpi-ico{ background:var(--amb-soft); color:var(--amb-ink); }
  .kpi.is-red .kpi-ico{ background:var(--red-soft); color:var(--red-ink); }
  .kpi.is-blue .kpi-ico{ background:#eaf1ff; color:#1f56cf; }
  .kpi b{ display:block; font-size:1.15rem; font-weight:900; color:var(--ink); letter-spacing:-.01em; line-height:1.1; }
  .kpi span{ font-size:.78rem; color:var(--muted); font-weight:700; }
  .kpi a{ margin-left:auto; font-size:.78rem; color:var(--acc-ink); font-weight:800; text-decoration:none; white-space:nowrap; }
  .kpi a:hover{ text-decoration:underline; }

  /* ===================== Filtros ===================== */
  .filters{
    padding:12px; border-radius:18px; border:1px solid rgba(232,238,246,.9);
    background:
      radial-gradient(900px 140px at 12% 0%, rgba(52,211,153,.12), transparent 62%),
      radial-gradient(860px 160px at 88% 0%, rgba(251,191,36,.10), transparent 60%),
      #ffffff;
    box-shadow:0 18px 44px rgba(15,23,42,.08);
  }
  .filters-row{display:flex; gap:12px; align-items:center; justify-content:space-between; flex-wrap:wrap;}

  /* --- Buscador --- */
  .search-wrap{ position:relative; flex:1; min-width:260px; }
  .search{
    display:flex; align-items:center; gap:8px; background:#fff;
    border:1px solid rgba(232,238,246,.95); border-radius:999px; padding:8px 10px 8px 14px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.9), 0 10px 18px rgba(15,23,42,.05);
    transition:border-color .14s ease, box-shadow .14s ease;
  }
  .search:focus-within{ border-color:var(--acc-ring); box-shadow: inset 0 1px 0 rgba(255,255,255,.95), 0 14px 26px rgba(52,211,153,.14); }
  .search .sico{ color:#94a3b8; width:20px; display:grid; place-items:center; flex:0 0 auto; }
  .search .sico svg{ width:18px; height:18px; }
  .search input{ border:0; outline:0; background:transparent; width:100%; min-width:0; color:var(--ink); font-weight:500; font-size:.95rem; font-family:inherit; }
  .search input::-webkit-search-cancel-button{ -webkit-appearance:none; }
  .search .kbd{
    flex:0 0 auto; font-size:.72rem; font-weight:800; color:#94a3b8; border:1px solid var(--line);
    border-radius:7px; padding:2px 7px; background:#f8fafc; font-family:inherit;
  }
  .search:focus-within .kbd{ display:none; }
  .search .sclear{
    flex:0 0 auto; width:28px; height:28px; border-radius:999px; border:0; background:#f1f5f9; color:#64748b;
    display:none; place-items:center; cursor:pointer;
  }
  .search .sclear svg{ width:14px; height:14px; }
  .search .sclear:hover{ background:#e2e8f0; color:#0f172a; }
  .search.has-text .sclear{ display:grid; }
  .search.has-text .kbd{ display:none; }
  .search .sspin{
    flex:0 0 auto; width:16px; height:16px; border-radius:50%; display:none;
    border:2px solid #cbd5e1; border-top-color:var(--acc-ink); animation:spin .7s linear infinite;
  }
  .search.is-loading .sspin{ display:block; }
  @keyframes spin{ to{ transform:rotate(360deg); } }
  .search-hint{ margin:6px 0 0 14px; font-size:.78rem; color:#94a3b8; font-weight:700; }
  .search-hint b{ color:var(--acc-ink); }

  /* --- Herramientas --- */
  .filter-tools{ display:inline-flex; gap:10px; align-items:center; flex-wrap:wrap; }

  .tabs{
    display:inline-flex; align-items:center; gap:4px; padding:5px; border-radius:999px;
    border:1px solid rgba(232,238,246,.95); background:rgba(255,255,255,.86);
    box-shadow:0 10px 18px rgba(15,23,42,.04); user-select:none;
  }
  .tab{
    appearance:none; border:0; background:transparent; padding:8px 12px; border-radius:999px;
    cursor:pointer; font-weight:700; color:#334155; font-family:inherit; font-size:.9rem;
    display:inline-flex; align-items:center; gap:6px; white-space:nowrap;
    transition: background .12s ease, color .12s ease, box-shadow .12s ease;
  }
  .tab:hover{ background:rgba(52,211,153,.10); }
  .tab.is-active{ background:var(--acc-soft); color:var(--acc-ink); box-shadow:0 12px 22px rgba(52,211,153,.12); }
  .tab .n{ font-size:.7rem; font-weight:900; padding:1px 6px; border-radius:999px; background:#eef2f7; color:#64748b; }
  .tab.is-active .n{ background:#fff; color:var(--acc-ink); }
  .tabs.tabs-samples .tab.is-active{ background:var(--amb-soft); color:var(--amb-ink); box-shadow:0 12px 22px rgba(245,158,11,.16); }
  .tabs.tabs-samples .tab:hover{ background:rgba(245,158,11,.12); }

  .chip{
    display:inline-flex; align-items:center; gap:8px; padding:9px 13px; border-radius:999px;
    border:1px solid rgba(232,238,246,.95); background:rgba(255,255,255,.86);
    box-shadow:0 10px 18px rgba(15,23,42,.04); font-weight:700; color:#334155; cursor:pointer;
    user-select:none; white-space:nowrap; font-size:.9rem; font-family:inherit;
    transition: box-shadow .12s ease, background .12s ease, border-color .12s ease;
  }
  .chip:hover{ box-shadow:0 14px 22px rgba(15,23,42,.06); background:#fff; }
  .chip input{ width:15px; height:15px; accent-color: var(--acc); margin:0; }
  .chip.is-on{ background:var(--acc-soft); border-color:var(--acc-ring); color:var(--acc-ink); }
  .chip .n{ font-size:.7rem; font-weight:900; padding:1px 6px; border-radius:999px; background:var(--acc-ink); color:#fff; }

  /* --- Panel de más filtros --- */
  .adv{ display:none; margin-top:12px; padding-top:12px; border-top:1px dashed rgba(232,238,246,.95); }
  .adv.is-open{ display:block; }
  .adv-grid{ display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:10px; }
  .fld{ display:flex; flex-direction:column; gap:5px; min-width:0; }
  .fld label{ font-size:.72rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:#94a3b8; }
  .fld select, .fld input{
    width:100%; border:1px solid var(--line); border-radius:12px; background:#fff; padding:9px 10px;
    font:inherit; font-size:.9rem; font-weight:600; color:var(--ink); outline:0;
    transition:border-color .12s ease, box-shadow .12s ease;
  }
  .fld select:focus, .fld input:focus{ border-color:var(--acc-ring); box-shadow:0 0 0 3px var(--acc-soft); }
  .fld .range{ display:flex; align-items:center; gap:6px; }
  .fld .range span{ color:#94a3b8; font-weight:800; }

  /* --- Chips de filtros activos --- */
  .active-filters{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:12px; }
  .active-filters .lbl{ font-size:.78rem; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; }
  .afchip{
    display:inline-flex; align-items:center; gap:6px; padding:5px 6px 5px 11px; border-radius:999px;
    background:var(--acc-soft); border:1px solid var(--acc-ring); color:var(--acc-ink);
    font-size:.82rem; font-weight:800; text-decoration:none;
  }
  .afchip b{ font-weight:900; }
  .afchip .x{ width:20px; height:20px; border-radius:999px; display:grid; place-items:center; background:rgba(255,255,255,.7); color:var(--acc-ink); }
  .afchip .x svg{ width:11px; height:11px; }
  .afchip:hover .x{ background:#fff; }
  .afclear{ font-size:.82rem; font-weight:800; color:var(--red-ink); text-decoration:none; padding:5px 8px; border-radius:999px; }
  .afclear:hover{ background:var(--red-soft); }

  /* ===================== Tabla ===================== */
  #listado{ position:relative; transition:opacity .15s ease; }
  #listado.is-cargando{ opacity:.55; pointer-events:none; }
  .table-wrap{ margin-top:12px; overflow:auto; border-radius:14px; border:1px solid var(--line); }
  table{ width:100%; border-collapse:collapse; font-size:.95rem; background:#fff }
  th, td{ padding:12px 12px; border-bottom:1px solid var(--line); vertical-align:middle; }
  th{ font-weight:900; text-align:left; color:var(--ink); background:#fbfdff; white-space:nowrap; position:sticky; top:0; z-index:2; font-size:.88rem; }
  tr:hover td{ background:#fcfdfd }
  th a.sort{ color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px; }
  th a.sort svg{ width:14px; height:14px; opacity:.35; }
  th a.sort:hover svg{ opacity:.8; }
  th a.sort.is-active{ color:var(--acc-ink); }
  th a.sort.is-active svg{ opacity:1; }

  td.img-cell, th.img-cell{ width:72px; max-width:72px; }
  .thumbbox{ width:56px; height:56px; border-radius:12px; border:1px solid var(--line); background:#f6f8fc; overflow:hidden; display:grid; place-items:center; }
  .thumbbox img{ width:100%; height:100%; object-fit:cover; display:block; }

  .name{ display:flex; flex-direction:column; gap:4px; min-width:260px; }
  .name strong{ color:var(--ink); font-weight:900; line-height:1.2 }
  .name mark{ background:rgba(52,211,153,.32); color:inherit; border-radius:3px; padding:0 1px; }
  .meta{ display:flex; gap:10px; flex-wrap:wrap; align-items:center; font-size:.82rem; color:var(--muted); }
  .meta .k{ color:#64748b; font-weight:800; }
  .meta .v{ color:#334155; font-weight:800; }
  .badges{ display:flex; gap:6px; flex-wrap:wrap; margin-top:4px; }

  .badge{
    display:inline-flex; align-items:center; gap:7px; padding:4px 9px; border-radius:999px;
    font-weight:900; font-size:.74rem; border:1px solid var(--line); background:#f1f5f9; color:#334155;
    white-space:nowrap;
  }
  .badge .dot{ width:7px; height:7px; border-radius:999px; background:#cbd5e1; }
  .b-live{ background:rgba(134,239,172,.22); border-color:rgba(134,239,172,.40); color:#065f46; }
  .b-live .dot{ background:#22c55e; }
  .b-draft .dot{ background:#94a3b8; }
  .b-hidden{ background:rgba(254,202,202,.26); border-color:rgba(254,202,202,.55); color:#991b1b; }
  .b-hidden .dot{ background:#ef4444; }
  .b-sample{ background:#fde68a; border-color:#fcd34d; color:#78350f; }
  .b-sample .dot{ background:#d97706; }
  .b-star{ background:rgba(52,211,153,.16); border-color:rgba(52,211,153,.28); color:#065f46; }
  .b-star .dot{ background:#22c55e; }
  .b-crit{ background:#fee2e2; border-color:#fca5a5; color:#991b1b; }
  .b-crit .dot{ background:#dc2626; }
  .b-holder{ background:#fff7ed; border-color:#fed7aa; color:#9a3412; }
  .b-holder .dot{ background:#fb923c; }
  .b-chan{ background:#f8fafc; color:#475569; font-weight:800; }
  .b-chan.is-err{ background:var(--red-soft); color:var(--red-ink); border-color:rgba(255,74,74,.24); }

  .cat{ font-weight:800; color:#334155; font-size:.88rem; }
  .cat small{ display:block; color:var(--muted); font-weight:700; font-size:.78rem; margin-top:2px; }

  .price{ font-weight:900; color:var(--ink); white-space:nowrap; }
  .sale{ color:#16a34a; font-weight:900; white-space:nowrap; }
  .muted-sm{ color:var(--muted); font-size:.85rem; }

  .stock-pill{
    display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:999px;
    border:1px solid rgba(232,238,246,.95); background:#ffffff; color:#334155; font-weight:900; white-space:nowrap;
  }
  .stock-pill .dot{ width:8px; height:8px; border-radius:999px; background:#22c55e; }
  .stock-pill.is-critical{ background:var(--red-soft); color:var(--red-ink); border-color:rgba(255,74,74,.24); }
  .stock-pill.is-critical .dot{ background:#ff4a4a; }
  .stock-pill.is-empty{ background:#f8fafc; color:#64748b; }
  .stock-pill.is-empty .dot{ background:#94a3b8; }
  .stock-meta{ margin-top:6px; font-size:.76rem; color:var(--muted); font-weight:800; white-space:nowrap; }

  /* Filas con aviso: tinte parejo en toda la fila + barra de color a la izquierda. */
  tr.is-critical-row td{ background:#fff5f5 !important; }
  tr.is-critical-row:hover td{ background:#ffecec !important; }
  tr.is-critical-row td:first-child{ box-shadow:inset 4px 0 0 var(--red); }
  tr.is-critical-row .stock-pill.is-critical{ background:#fee2e2; border-color:#fca5a5; color:#991b1b; }

  tr.is-sample-row td{ background:#fffbeb !important; }
  tr.is-sample-row:hover td{ background:#fef3c7 !important; }
  tr.is-sample-row td:first-child{ box-shadow:inset 4px 0 0 var(--amb); }
  tr.is-sample-row .thumbbox{ border-color:#fde68a; }

  /* Muestra con stock crítico: la fila sigue ámbar, pero la barra y la pastilla avisan en rojo. */
  tr.is-sample-row.is-critical-row td{ background:#fffbeb !important; }
  tr.is-sample-row.is-critical-row:hover td{ background:#fef3c7 !important; }
  tr.is-sample-row.is-critical-row td:first-child{ box-shadow:inset 4px 0 0 var(--red); }

  .legend{ display:inline-flex; align-items:center; gap:14px; font-size:.8rem; color:var(--muted); font-weight:700; }
  .legend span{ display:inline-flex; align-items:center; gap:6px; }
  .legend .sw{ width:14px; height:14px; border-radius:4px; border:1px solid var(--line); border-left-width:4px; }
  .legend .sw-red{ background:#fff5f5; border-left-color:var(--red); }
  .legend .sw-amb{ background:#fffbeb; border-left-color:var(--amb); }

  .actions{ display:grid; grid-template-columns:repeat(2, 36px); gap:6px; justify-content:end; }
  .actions.is-wide{ grid-template-columns:repeat(3, 36px); }

  .empty{ text-align:center; padding:40px 20px; color:var(--muted); }
  .empty .eico{ width:52px; height:52px; border-radius:14px; background:#f1f5f9; display:inline-grid; place-items:center; color:#94a3b8; margin-bottom:10px; }
  .empty .eico svg{ width:24px; height:24px; }
  .empty h3{ margin:0 0 4px; color:var(--ink); font-size:1.05rem; }
  .empty p{ margin:0 0 14px; font-size:.9rem; }

  .foot{display:flex; align-items:center; justify-content:space-between; gap:12px; margin:16px 4px; flex-wrap:wrap;}
  .foot-left{ display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
  .perpage{ display:inline-flex; align-items:center; gap:6px; font-size:.88rem; color:var(--muted); font-weight:700; }
  .perpage select{ border:1px solid var(--line); border-radius:10px; padding:6px 8px; font:inherit; font-weight:800; color:var(--ink); background:#fff; }

  /* ===================== Modales ===================== */
  .stock-modal, .dl-modal{
    position:fixed; inset:0; display:flex; align-items:center; justify-content:center;
    z-index:1200; pointer-events:none; opacity:0; transition:opacity .18s ease;
  }
  .dl-modal{ z-index:1150; }
  .stock-modal.is-open, .dl-modal.is-open{ pointer-events:auto; opacity:1; }
  .stock-modal__overlay, .dl-modal__overlay{ position:absolute; inset:0; background:rgba(15,23,42,.45); backdrop-filter:blur(4px); }
  .stock-modal__card, .dl-modal__card{
    position:relative; z-index:1; width:100%; max-width:380px; background:#ffffff; border-radius:18px;
    box-shadow:0 24px 70px rgba(15,23,42,.45); border:1px solid rgba(226,232,240,.9); padding:18px 18px 16px;
  }
  .stock-modal__card{ max-width:360px; }
  .stock-modal__head, .dl-modal__head{ display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:10px; }
  .stock-modal__title, .dl-modal__title{ margin:0; font-size:15px; font-weight:900; letter-spacing:-.01em; color:var(--ink); }
  .stock-modal__subtitle, .dl-modal__subtitle{ margin:4px 0 0; font-size:.85rem; color:var(--muted); }
  .stock-modal__close, .dl-modal__close{
    width:32px; height:32px; border-radius:12px; border:1px solid var(--line); background:#fff;
    display:grid; place-items:center; cursor:pointer; box-shadow:0 8px 20px rgba(15,23,42,.12);
  }
  .stock-modal__close svg, .dl-modal__close svg{ width:16px; height:16px; }
  .stock-modal__body{ margin-top:8px; }
  .stock-field-label{ font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; margin-bottom:4px; }
  .stock-input-wrap{ display:flex; align-items:center; gap:8px; }
  .stock-input{ flex:1; border-radius:999px; border:1px solid var(--line); padding:8px 12px; font-size:.95rem; text-align:right; font-family:inherit; }
  .stock-input:focus{ outline:none; border-color:var(--acc-ring); box-shadow:0 0 0 1px var(--acc-soft); }
  .stock-modal__foot{ display:flex; justify-content:flex-end; gap:8px; margin-top:16px; }
  .dl-modal__body{ margin-top:8px; display:grid; gap:10px; }

  /* ===================== Móvil ===================== */
  .fab{
    position:fixed; right:16px; bottom:18px; width:58px; height:58px; border-radius:999px;
    border:1px solid rgba(232,238,246,.9); background:rgba(255,255,255,.92);
    backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
    box-shadow:0 18px 44px rgba(15,23,42,.18); display:none; place-items:center; z-index:1000; cursor:pointer;
  }
  .fab svg{ width:22px; height:22px; color: var(--acc-ink); }
  .fab .fab-n{
    position:absolute; top:-4px; right:-4px; min-width:22px; height:22px; padding:0 6px; border-radius:999px;
    background:var(--acc-ink); color:#fff; font-size:.72rem; font-weight:900; display:none; align-items:center; justify-content:center;
  }
  .fab .fab-n.is-on{ display:inline-flex; }

  .sheet-overlay{ position:fixed; inset:0; background:rgba(15,23,42,.42); opacity:0; pointer-events:none; transition:opacity .18s ease; z-index:1001; }
  .sheet{
    position:fixed; left:0; right:0; bottom:-100%; max-height:88vh; overflow:auto;
    background:#fff; border-top-left-radius:20px; border-top-right-radius:20px;
    border:1px solid rgba(232,238,246,.9); box-shadow:0 -18px 50px rgba(15,23,42,.25);
    z-index:1002; transition: bottom .22s ease; padding:12px 14px 20px;
  }
  .sheet .grab{ width:44px; height:5px; border-radius:999px; background:#e5e7eb; margin:4px auto 10px; }
  .sheet .sheet-title{ display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px; }
  .sheet .sheet-title h3{ margin:0; font-size:15px; font-weight:900; color:var(--ink); }
  .sheet .sheet-close{ width:36px; height:36px; border-radius:12px; border:1px solid var(--line); background:#fff; display:grid; place-items:center; cursor:pointer; }
  .sheet .sheet-close svg{ width:18px; height:18px; }
  .sheet-body{ display:grid; gap:12px; }
  .sheet-body .filter-tools{ display:grid; gap:10px; }
  .sheet-body .tabs{ width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch; justify-content:flex-start; }
  .sheet-body .tabs::-webkit-scrollbar{ height:0; }
  .sheet-body .chip{ justify-content:center; }
  .sheet-body .adv{ display:block; margin-top:0; padding-top:0; border-top:0; }
  .sheet-body .adv-grid{ grid-template-columns:1fr 1fr; }
  .sheet-body .btn{ justify-content:center; }
  .sheet-actions{ display:grid; gap:10px; margin-top:4px; }
  .sheet-open .sheet-overlay{ opacity:1; pointer-events:auto; }
  .sheet-open .sheet{ bottom:0; }

  @media (max-width: 1100px){
    .kpis{ grid-template-columns:repeat(2, minmax(0,1fr)); }
  }
  @media (max-width: 760px){
    .wrap{ padding:0 10px; }
    body{ padding-bottom: 86px; }
    .fab{ display:grid; }
    .head .tt-new, .head .tt-download, .head .tt-analytics{ display:none !important; }
    .kpis{ gap:8px; }
    .kpi{ padding:10px 12px; }
    .kpi b{ font-size:1rem; }
    .kpi a{ display:none; }
    .search .kbd, .search-hint{ display:none; }
    .adv-toggle{ display:none !important; }
    .active-filters{ margin-top:10px; }

    .table-wrap{ border:0; background:transparent; overflow:visible; box-shadow:none; }
    table, thead, tbody, th, td, tr{ display:block; }
    thead{ display:none; }
    table{ background:transparent; }
    tbody tr{ background:#fff; border:1px solid var(--line); border-radius:16px; box-shadow:0 14px 30px rgba(15,23,42,.06); padding:12px; margin:12px 0; }
    tbody tr.is-critical-row{ border-color:#fecaca; border-left:5px solid var(--red); background:#fff5f5; box-shadow:0 14px 30px rgba(239,68,68,.10); }
    tbody tr.is-sample-row{ border-color:#fde68a; border-left:5px solid var(--amb); background:#fffbeb; box-shadow:0 14px 30px rgba(245,158,11,.12); }
    tbody tr.is-sample-row.is-critical-row{ border-left-color:var(--red); background:#fffbeb; }
    tbody td{ border:0; padding:0; background:transparent !important; box-shadow:none !important; }
    td + td{ margin-top:10px; }
    td.img-cell{ width:auto !important; max-width:none !important; margin-bottom:10px; }
    .thumbbox{ width:74px; height:74px; border-radius:16px; }
    .name{ min-width:0; }
    .actions{ display:flex; flex-wrap:wrap; justify-content:flex-start; margin-top:12px; padding-top:10px; border-top:1px dashed rgba(232,238,246,.9); }
    .iconbtn{ width:44px; height:44px; border-radius:16px; }
    .stock-modal__card, .dl-modal__card{ max-width:92%; }
    .foot{ justify-content:center; }
  }

  /* ===================== SweetAlert ===================== */
  .swal2-popup.sa-popup{ border-radius:18px; padding:24px 24px 20px; box-shadow:0 22px 60px rgba(15,23,42,.32); border:1px solid rgba(226,232,240,.95); font-family:inherit; }
  .swal2-icon{ box-shadow:none !important; }
  .swal2-popup.sa-popup .swal2-icon{ margin-top:0; margin-bottom:6px; }
  .swal2-title.sa-title{ margin:6px 0 2px; font-size:1.35rem; font-weight:800; letter-spacing:-.01em; color:var(--ink); }
  .swal2-html-container.sa-text{ margin:4px 0 0; font-size:.95rem; color:var(--muted); }
  .swal2-actions{ margin-top:18px; gap:10px; }
  .swal2-confirm.sa-confirm, .swal2-cancel.sa-cancel{ border-radius:999px; font-weight:700; font-size:.9rem; padding:9px 18px; box-shadow:none; }
  .swal2-confirm.sa-confirm{ background:var(--acc-ink); color:#fff; border:0; }
  .swal2-confirm.sa-confirm:hover{ filter:brightness(1.05); }
  .swal2-cancel.sa-cancel{ background:#f9fafb; color:#4b5563; border:1px solid #e5e7eb; }
  .swal2-cancel.sa-cancel:hover{ background:#f3f4f6; }
  .swal2-popup.sa-toast{ border-radius:999px; padding:10px 14px; box-shadow:0 18px 44px rgba(15,23,42,.35); border:1px solid rgba(148,163,184,.35); background:rgba(15,23,42,.96); color:#e5e7eb; }
  .swal2-popup.sa-toast .swal2-title.sa-toast-title{ font-size:.9rem; font-weight:600; }
  .swal2-popup.sa-toast .swal2-icon{ margin:0 8px 0 0; transform:scale(.8); }
  .swal2-popup.sa-toast .swal2-icon.swal2-success{ border-color:#22c55e; color:#bbf7d0; }
  .swal2-popup.sa-toast .swal2-icon.swal2-error{ border-color:#fecaca; color:#fecaca; }

  /* ===================== Paginación ===================== */
  .pagi{ display:flex; align-items:center; justify-content:flex-end; gap:8px; flex-wrap:wrap; }
  .pagi .page{
    height:40px; min-width:40px; padding:0 12px; border-radius:14px; border:1px solid var(--line); background:#fff;
    color:#334155; font-weight:900; display:inline-flex; align-items:center; justify-content:center; gap:8px;
    text-decoration:none; box-shadow:0 10px 18px rgba(15,23,42,.05);
    transition:transform .12s ease, box-shadow .12s ease, background .12s ease, border-color .12s ease;
  }
  .pagi .page:hover{ transform:translateY(-1px); box-shadow:0 14px 26px rgba(15,23,42,.08); border-color:rgba(52,211,153,.28); }
  .pagi .page.is-active{ background:var(--acc-soft); border-color:var(--acc-ring); color:var(--acc-ink); }
  .pagi .page.is-disabled{ opacity:.45; pointer-events:none; box-shadow:none; }
  .pagi .page.is-ellipsis{ opacity:.8; pointer-events:none; box-shadow:none; }
  .pagi .page svg{ width:18px; height:18px; display:block; }
  @media (max-width: 760px){ .pagi{ justify-content:center; } .pagi .page{ height:44px; min-width:44px; border-radius:16px; } }
</style>
@endpush

@section('content')
@php
  $st = (string) ($filters['status'] ?? '');
@endphp

<div class="wrap">

  {{-- ===================== Encabezado ===================== --}}
  <div class="head">
    <div>
      <h1 class="title" id="tituloLista">{{ $tituloLista }}</h1>
      <p class="muted subtxt">Gestiona el catálogo público y sincroniza con Mercado Libre con acciones rápidas. Las muestras salen resaltadas en ámbar.</p>
    </div>

    <div class="head-actions">
      <div class="tt tt-analytics">
        <span class="tt-bubble">Ver resumen profesional del inventario</span>
        <a href="{{ route('admin.catalog.analytics') }}" class="btn btn-sm btn-soft">
          <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16V9"/><path d="M13 16V6"/><path d="M18 16v-4"/></svg></span>
          Analíticas
        </a>
      </div>

      <div class="tt tt-download">
        <span class="tt-bubble">Descargar listado (Excel o PDF) con los filtros actuales</span>
        <button type="button" class="btn btn-sm btn-soft" id="downloadOpenBtn">
          <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12"/><path d="M7 11l5 5 5-5"/><path d="M5 19h14"/></svg></span>
          Descargar
        </button>
      </div>

      <div class="tt tt-new">
        <span class="tt-bubble">Crear nuevo producto</span>
        <a href="{{ route('admin.catalog.create') }}" class="btn">
          <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg></span>
          Nuevo
        </a>
      </div>
    </div>
  </div>

  {{-- ===================== Resumen ===================== --}}
  <div class="kpis" id="kpisBox">
    @include('admin.catalog._kpis')
  </div>

  {{-- ===================== Filtros ===================== --}}
  <div class="filters">
    <form id="filtersForm" method="GET" action="{{ route('admin.catalog.index') }}" autocomplete="off">
      <div class="filters-row">
        <div class="search-wrap">
          <div class="search {{ $filters['s'] !== '' ? 'has-text' : '' }}" id="searchBox">
            <span class="sico">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            </span>
            <input id="sInput" type="search" name="s"
                   placeholder="Escribe para filtrar: nombre, SKU, marca, código de barras o ID de ML…"
                   value="{{ $filters['s'] }}" autocomplete="off" spellcheck="false" aria-label="Buscar productos" />
            <span class="sspin" aria-hidden="true"></span>
            <span class="kbd" aria-hidden="true">/</span>
            <button type="button" class="sclear" id="sClear" aria-label="Limpiar búsqueda">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="search-hint">La lista se filtra mientras escribes, sin recargar. <b>/</b> enfoca el buscador · <b>Esc</b> lo limpia.</div>
        </div>

        {{-- Todo lo que sigue se mueve a la hoja inferior en móvil --}}
        <div class="filter-tools" id="filterTools">
          <div class="tt">
            <span class="tt-bubble">Filtrar por estado</span>
            <div class="tabs" role="tablist" aria-label="Estado">
              <button type="button" class="tab {{ $st==='' ? 'is-active' : '' }}" data-status="">Todos <span class="n" data-n="all">{{ $totalEstados }}</span></button>
              <button type="button" class="tab {{ $st==='1' ? 'is-active' : '' }}" data-status="1">Publicado <span class="n" data-n="1">{{ (int) ($porEstado[1] ?? 0) }}</span></button>
              <button type="button" class="tab {{ $st==='0' ? 'is-active' : '' }}" data-status="0">Borrador <span class="n" data-n="0">{{ (int) ($porEstado[0] ?? 0) }}</span></button>
              <button type="button" class="tab {{ $st==='2' ? 'is-active' : '' }}" data-status="2">Oculto <span class="n" data-n="2">{{ (int) ($porEstado[2] ?? 0) }}</span></button>
            </div>
          </div>

          <div class="tt">
            <span class="tt-bubble">Catálogo, solo muestras o todos juntos</span>
            <div class="tabs tabs-samples" role="tablist" aria-label="Muestras">
              <button type="button" class="tab {{ $samplesMode==='' ? 'is-active' : '' }}" data-samples="">Catálogo</button>
              <button type="button" class="tab {{ $samplesMode==='only' ? 'is-active' : '' }}" data-samples="only">Muestras</button>
              <button type="button" class="tab {{ $samplesMode==='all' ? 'is-active' : '' }}" data-samples="all">Todos</button>
            </div>
          </div>

          <div class="tt">
            <span class="tt-bubble">Mostrar solo destacados</span>
            <label class="chip {{ $filters['featured_only'] ? 'is-on' : '' }}">
              <input id="featuredInput" type="checkbox" name="featured_only" value="1" form="filtersForm" @checked($filters['featured_only'])>
              Destacados
            </label>
          </div>

          <button type="button" class="chip adv-toggle {{ $advActivos > 0 ? 'is-on' : '' }}" id="advToggle" aria-expanded="{{ $advActivos > 0 ? 'true' : 'false' }}" aria-controls="advPanel">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
            Más filtros
            <span class="n" id="advCount" @if($advActivos === 0) hidden @endif>{{ $advActivos }}</span>
          </button>

          {{-- Panel de filtros avanzados --}}
          <div class="adv {{ $advActivos > 0 ? 'is-open' : '' }}" id="advPanel" style="flex-basis:100%;">
            <div class="adv-grid">
              <div class="fld">
                <label for="fCategory">Categoría</label>
                <select id="fCategory" name="category" form="filtersForm" data-filtro>
                  <option value="">Todas</option>
                  @foreach($categorias as $c)
                    <option value="{{ $c->id }}" @selected((string)$filters['category'] === (string)$c->id)>{{ $c->full_path ?: $c->name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="fld">
                <label for="fBrand">Marca</label>
                <select id="fBrand" name="brand" form="filtersForm" data-filtro>
                  <option value="">Todas</option>
                  @foreach($marcas as $m)
                    <option value="{{ $m }}" @selected($filters['brand'] === $m)>{{ $m }}</option>
                  @endforeach
                </select>
              </div>

              <div class="fld">
                <label for="fStock">Existencias</label>
                <select id="fStock" name="stock" form="filtersForm" data-filtro>
                  <option value="" @selected($filters['stock']==='')>Todas</option>
                  <option value="critical" @selected($filters['stock']==='critical')>Stock crítico (≤ mínimo)</option>
                  <option value="empty" @selected($filters['stock']==='empty')>Sin existencia</option>
                  <option value="ok" @selected($filters['stock']==='ok')>Con existencia sana</option>
                </select>
              </div>

              <div class="fld">
                <label for="fMl">Mercado Libre</label>
                <select id="fMl" name="ml" form="filtersForm" data-filtro>
                  <option value="" @selected($filters['ml']==='')>Cualquiera</option>
                  <option value="yes" @selected($filters['ml']==='yes')>Publicados en ML</option>
                  <option value="no" @selected($filters['ml']==='no')>Sin publicar en ML</option>
                  <option value="error" @selected($filters['ml']==='error')>Con error de sincronización</option>
                </select>
              </div>

              <div class="fld">
                <label>Precio</label>
                <div class="range">
                  <input type="number" name="price_min" form="filtersForm" min="0" step="0.01" placeholder="Mín" value="{{ $filters['price_min'] }}" data-filtro-texto>
                  <span>–</span>
                  <input type="number" name="price_max" form="filtersForm" min="0" step="0.01" placeholder="Máx" value="{{ $filters['price_max'] }}" data-filtro-texto>
                </div>
              </div>

              <div class="fld">
                <label for="fSort">Ordenar por</label>
                <select id="fSort" name="sort" form="filtersForm" data-filtro>
                  @foreach($etqOrden as $k => $v)
                    <option value="{{ $k }}" @selected($sortKey === $k)>{{ $v }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Chips de filtros activos --}}
      <div id="chipsBox">
        @include('admin.catalog._chips')
      </div>

      <input type="hidden" name="status" id="statusInput" value="{{ $st }}">
      <input type="hidden" name="samples" id="samplesInput" value="{{ $samplesMode }}">
      <input type="hidden" name="per_page" id="perPageInput" value="{{ $perPage }}">
    </form>
  </div>

  {{-- ===================== Lista ===================== --}}
  <div id="listado" aria-live="polite">
    @include('admin.catalog._lista')
  </div>
</div>

{{-- ===================== Modal: stock ===================== --}}
<div id="stockModal" class="stock-modal">
  <div class="stock-modal__overlay"></div>
  <div class="stock-modal__card">
    <div class="stock-modal__head">
      <div>
        <h3 class="stock-modal__title">Ajustar stock</h3>
        <p class="stock-modal__subtitle" id="stockProductName">Producto</p>
      </div>
      <button type="button" class="stock-modal__close" id="stockCloseBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <form id="stockForm" method="POST" action="">
      @csrf
      @method('PATCH')
      <div class="stock-modal__body">
        <div class="stock-field-label">Existencia actual</div>
        <div class="stock-input-wrap">
          <input type="number" step="0.01" min="0" name="stock" id="stockInput" class="stock-input" placeholder="0.00">
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

{{-- ===================== Modal: descargar ===================== --}}
<div id="downloadModal" class="dl-modal">
  <div class="dl-modal__overlay"></div>
  <div class="dl-modal__card">
    <div class="dl-modal__head">
      <div>
        <h3 class="dl-modal__title">Descargar listado</h3>
        <p class="dl-modal__subtitle">Incluye los productos con los filtros actuales.</p>
      </div>
      <button type="button" class="dl-modal__close" id="downloadCloseBtn" aria-label="Cerrar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>

    {{-- Los enlaces se rearman al abrir con los filtros vigentes --}}
    <div class="dl-modal__body">
      <a href="{{ route('admin.catalog.export.excel') }}" class="btn" data-export>
        <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 3h16v18H4z"/><path d="M8 7l8 10"/><path d="M16 7L8 17"/></svg></span>
        Excel (.xlsx)
      </a>
      <a href="{{ route('admin.catalog.analytics.pdf') }}" class="btn btn-soft" data-export>
        <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16V9"/><path d="M13 16V6"/><path d="M18 16v-4"/></svg></span>
        PDF profesional de analíticas
      </a>
      <a href="{{ route('admin.catalog.export.pdf') }}" class="btn btn-soft" data-export>
        <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h9l5 5v15H6z"/><path d="M15 2v5h5"/><path d="M9 13h3"/><path d="M9 17h6"/></svg></span>
        PDF listado simple
      </a>
    </div>
  </div>
</div>

{{-- ===================== Móvil: botón + hoja de filtros ===================== --}}
<button class="fab" id="fabOpen" type="button" aria-label="Abrir filtros">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
  <span class="fab-n {{ $hasFilters ? 'is-on' : '' }}" id="fabCount">{{ count($activos) }}</span>
</button>

<div class="sheet-overlay" id="sheetOverlay" aria-hidden="true"></div>

<div class="sheet" id="sheet" role="dialog" aria-modal="true" aria-label="Filtros" aria-hidden="true">
  <div class="grab"></div>
  <div class="sheet-title">
    <h3>Filtros</h3>
    <button class="sheet-close" type="button" id="sheetClose" aria-label="Cerrar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
  </div>

  {{-- Aquí se mueve #filterTools cuando la pantalla es angosta --}}
  <div class="sheet-body" id="sheetBody"></div>

  <div class="sheet-actions">
    <button type="button" class="btn" id="sheetApply">Ver resultados</button>
    <a href="{{ route('admin.catalog.index') }}" class="btn btn-sm btn-ghost" data-ajax>Limpiar filtros</a>
    <a href="{{ route('admin.catalog.create') }}" class="btn btn-soft">
      <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg></span>
      Nuevo producto
    </a>
    <a href="{{ route('admin.catalog.analytics') }}" class="btn btn-soft">Ver analíticas</a>
    <button type="button" class="btn btn-soft" id="downloadOpenBtnSheet">Descargar listado</button>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function(){
  function debounce(fn, wait){ let t; return (...args)=>{ clearTimeout(t); t=setTimeout(()=>fn(...args), wait); }; }
  function isMobile(){ return window.matchMedia('(max-width: 760px)').matches; }

  const INDEX_URL = @json(route('admin.catalog.index'));
  const form      = document.getElementById('filtersForm');
  const sInput    = document.getElementById('sInput');
  const sBox      = document.getElementById('searchBox');
  const listado   = document.getElementById('listado');
  const kpisBox   = document.getElementById('kpisBox');
  const chipsBox  = document.getElementById('chipsBox');
  const titulo    = document.getElementById('tituloLista');
  const statusInput  = document.getElementById('statusInput');
  const samplesInput = document.getElementById('samplesInput');
  const perPageInput = document.getElementById('perPageInput');
  const featured  = document.getElementById('featuredInput');
  const advToggle = document.getElementById('advToggle');
  const advPanel  = document.getElementById('advPanel');
  const advCount  = document.getElementById('advCount');
  const fabCount  = document.getElementById('fabCount');

  // =============== Carga por AJAX (sin recargar la página) ===============
  let ctrl = null;

  // URL del listado a partir de lo que hay en el formulario; solo viajan
  // los parámetros con valor, y nunca la página (un filtro nuevo empieza en la 1).
  function urlDesdeForm(extra){
    const p = new URLSearchParams();
    new FormData(form).forEach((v, k)=>{ v = String(v).trim(); if (v !== '') p.set(k, v); });
    if (p.get('per_page') === '20') p.delete('per_page');
    if (p.get('sort') === 'recent') p.delete('sort');
    p.delete('page');
    Object.entries(extra || {}).forEach(([k, v])=>{ if (v === null || v === '') p.delete(k); else p.set(k, v); });
    const qs = p.toString();
    return INDEX_URL + (qs ? '?' + qs : '');
  }

  async function cargar(url, opciones){
    opciones = opciones || {};
    if (ctrl) ctrl.abort();
    const mio = ctrl = new AbortController();

    listado.classList.add('is-cargando');
    sBox.classList.add('is-loading');

    try {
      const r = await fetch(url, {
        signal: ctrl.signal,
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      });
      if (!r.ok) throw new Error('HTTP ' + r.status);
      const d = await r.json();

      listado.innerHTML = d.lista;
      kpisBox.innerHTML = d.kpis;
      chipsBox.innerHTML = d.chips;
      if (titulo && d.titulo) titulo.textContent = d.titulo;
      pintarConteos(d.estados || {}, d.totalEstados || 0);
      fabCount.textContent = d.activos || 0;
      fabCount.classList.toggle('is-on', (d.activos || 0) > 0);

      // La barra de direcciones refleja el filtro: F5 y "atrás" lo conservan.
      if (opciones.historial === 'push') history.pushState({ url }, '', url);
      else if (opciones.historial !== 'none') history.replaceState({ url }, '', url);

      if (opciones.scroll) listado.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } catch (err) {
      // Si algo falla (sesión vencida, error del servidor) se cae a la carga normal.
      if (err.name !== 'AbortError') window.location.href = url;
    } finally {
      // Solo la petición más reciente apaga el "cargando"; una cancelada no.
      if (ctrl === mio){
        listado.classList.remove('is-cargando');
        sBox.classList.remove('is-loading');
      }
    }
  }

  function pintarConteos(estados, total){
    document.querySelectorAll('[data-n]').forEach(el=>{
      const k = el.dataset.n;
      el.textContent = k === 'all' ? total : (estados[k] || 0);
    });
  }

  function marcarTabs(){
    document.querySelectorAll('.tab[data-status]').forEach(b => b.classList.toggle('is-active', b.dataset.status === statusInput.value));
    document.querySelectorAll('.tab[data-samples]').forEach(b => b.classList.toggle('is-active', b.dataset.samples === samplesInput.value));
    featured.closest('.chip').classList.toggle('is-on', featured.checked);

    const avanzados = ['category','brand','stock','ml','price_min','price_max'].filter(n => (form.elements[n]?.value || '') !== '').length
                    + ((form.elements['sort']?.value || 'recent') !== 'recent' ? 1 : 0);
    advCount.textContent = avanzados;
    advCount.hidden = avanzados === 0;
    advToggle.classList.toggle('is-on', avanzados > 0);
    if (avanzados > 0) advPanel.classList.add('is-open');
  }

  // Deja los controles del formulario como dice una URL (al quitar un chip,
  // al usar "atrás", etc.).
  function sincronizarForm(url){
    const p = new URL(url, window.location.origin).searchParams;
    const set = (n, v)=>{ const el = form.elements[n]; if (el) el.value = v; };
    set('s', p.get('s') || '');
    statusInput.value = p.get('status') || '';
    samplesInput.value = p.get('samples') || '';
    featured.checked = p.get('featured_only') === '1';
    ['category','brand','stock','ml','price_min','price_max'].forEach(n => set(n, p.get(n) || ''));
    set('sort', p.get('sort') || 'recent');
    perPageInput.value = p.get('per_page') || '20';
    document.querySelectorAll('[data-perpage]').forEach(s => s.value = perPageInput.value);
    pintarEstadoBusqueda();
    marcarTabs();
  }

  function aplicarFiltros(opciones){ cargar(urlDesdeForm(), opciones); }
  const aplicarConPausa = debounce(()=> aplicarFiltros(), 220);

  // =============== Buscador ===============
  function pintarEstadoBusqueda(){ sBox.classList.toggle('has-text', (sInput.value || '').trim() !== ''); }
  pintarEstadoBusqueda();

  sInput.addEventListener('input', ()=>{ pintarEstadoBusqueda(); aplicarConPausa(); });
  sInput.addEventListener('keydown', (e)=>{
    if (e.key === 'Enter'){ e.preventDefault(); aplicarFiltros(); }
    if (e.key === 'Escape' && sInput.value){ e.preventDefault(); sInput.value = ''; pintarEstadoBusqueda(); aplicarFiltros(); }
  });
  document.getElementById('sClear').addEventListener('click', ()=>{
    sInput.value = ''; pintarEstadoBusqueda(); aplicarFiltros(); sInput.focus();
  });

  // Atajo "/" para ir al buscador desde cualquier parte de la página
  document.addEventListener('keydown', (e)=>{
    const enCampo = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement?.tagName || '') || document.activeElement?.isContentEditable;
    if (e.key === '/' && !enCampo){ e.preventDefault(); sInput.focus(); sInput.select(); }
  });

  // El formulario nunca se envía "de verdad": todo va por AJAX.
  form.addEventListener('submit', (e)=>{ e.preventDefault(); aplicarFiltros(); });

  // =============== Pestañas y controles ===============
  document.querySelectorAll('.tab[data-status]').forEach(btn=>{
    btn.addEventListener('click', ()=>{ statusInput.value = btn.dataset.status ?? ''; marcarTabs(); aplicarFiltros(); });
  });
  document.querySelectorAll('.tab[data-samples]').forEach(btn=>{
    btn.addEventListener('click', ()=>{ samplesInput.value = btn.dataset.samples ?? ''; marcarTabs(); aplicarFiltros(); });
  });
  featured.addEventListener('change', ()=>{ marcarTabs(); aplicarFiltros(); });
  document.querySelectorAll('[data-filtro]').forEach(el => el.addEventListener('change', ()=>{ marcarTabs(); aplicarFiltros(); }));
  document.querySelectorAll('[data-filtro-texto]').forEach(el=>{
    el.addEventListener('input', ()=>{ marcarTabs(); aplicarConPausa(); });
    el.addEventListener('keydown', (e)=>{ if (e.key === 'Enter'){ e.preventDefault(); aplicarFiltros(); } });
  });

  advToggle.addEventListener('click', ()=>{
    const open = advPanel.classList.toggle('is-open');
    advToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) advPanel.querySelector('select, input')?.focus();
  });

  // Enlaces que se resuelven por AJAX: chips, orden, paginación, KPIs, vacíos.
  document.addEventListener('click', (e)=>{
    const a = e.target.closest('a[data-ajax]');
    if (!a || !a.getAttribute('href') || a.getAttribute('href').startsWith('javascript:')) return;
    e.preventDefault();
    const url = a.href;
    sincronizarForm(url);
    closeSheet();
    cargar(url, { historial: 'push', scroll: a.closest('.pagi') !== null });
  });

  // Tamaño de página (el select vive dentro de la lista y se rehace en cada carga)
  document.addEventListener('change', (e)=>{
    const sel = e.target.closest('[data-perpage]');
    if (!sel) return;
    perPageInput.value = sel.value;
    aplicarFiltros();
  });

  window.addEventListener('popstate', ()=>{
    sincronizarForm(window.location.href);
    cargar(window.location.href, { historial: 'none' });
  });

  // =============== Móvil: mover las herramientas a la hoja ===============
  const root = document.documentElement;
  const sheet = document.getElementById('sheet');
  const sheetBody = document.getElementById('sheetBody');
  const overlay = document.getElementById('sheetOverlay');
  const tools = document.getElementById('filterTools');
  const toolsHome = tools.parentNode;
  const toolsNext = tools.nextSibling;

  function colocarHerramientas(){
    if (isMobile()){
      if (tools.parentNode !== sheetBody) sheetBody.appendChild(tools);
    } else if (tools.parentNode !== toolsHome){
      toolsHome.insertBefore(tools, toolsNext);
    }
  }
  colocarHerramientas();
  window.addEventListener('resize', debounce(()=>{ colocarHerramientas(); if (!isMobile()) closeSheet(); }, 120));

  function openSheet(){
    if (!isMobile()) return;
    colocarHerramientas();
    root.classList.add('sheet-open');
    sheet.setAttribute('aria-hidden','false');
    overlay.setAttribute('aria-hidden','false');
  }
  function closeSheet(){
    root.classList.remove('sheet-open');
    sheet.setAttribute('aria-hidden','true');
    overlay.setAttribute('aria-hidden','true');
  }
  document.getElementById('fabOpen').addEventListener('click', openSheet);
  overlay.addEventListener('click', closeSheet);
  document.getElementById('sheetClose').addEventListener('click', closeSheet);
  document.getElementById('sheetApply').addEventListener('click', ()=>{ closeSheet(); aplicarFiltros({ scroll: true }); });

  // =============== Modal de stock (delegado: las filas se rehacen) ===============
  const stockModal = document.getElementById('stockModal');
  const stockForm = document.getElementById('stockForm');
  const stockInput = document.getElementById('stockInput');
  const stockProductName = document.getElementById('stockProductName');

  document.addEventListener('click', (e)=>{
    const btn = e.target.closest('.js-open-stock');
    if (!btn) return;
    const action = btn.getAttribute('data-action') || '';
    if (action) stockForm.setAttribute('action', action);
    stockProductName.textContent = btn.getAttribute('data-name') || 'Producto';
    stockInput.value = btn.getAttribute('data-stock') || '0';
    stockModal.classList.add('is-open');
    stockInput.focus(); stockInput.select();
  });
  function closeStockModal(){ stockModal.classList.remove('is-open'); }
  stockModal.querySelector('.stock-modal__overlay').addEventListener('click', closeStockModal);
  document.getElementById('stockCloseBtn').addEventListener('click', closeStockModal);
  document.getElementById('stockCancelBtn').addEventListener('click', closeStockModal);

  // =============== Modal de descarga ===============
  const dlModal = document.getElementById('downloadModal');
  function openDownloadModal(){
    closeSheet();
    // Los enlaces llevan los filtros que estén puestos en ese momento.
    const qs = new URL(urlDesdeForm(), window.location.origin).search;
    dlModal.querySelectorAll('a[data-export]').forEach(a=>{
      const base = a.href.split('?')[0];
      a.href = base + qs;
    });
    dlModal.classList.add('is-open');
  }
  function closeDownloadModal(){ dlModal.classList.remove('is-open'); }
  document.getElementById('downloadOpenBtn')?.addEventListener('click', openDownloadModal);
  document.getElementById('downloadOpenBtnSheet')?.addEventListener('click', openDownloadModal);
  dlModal.querySelector('.dl-modal__overlay').addEventListener('click', closeDownloadModal);
  document.getElementById('downloadCloseBtn').addEventListener('click', closeDownloadModal);
  document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape'){ closeStockModal(); closeDownloadModal(); closeSheet(); } });

  // =============== Confirmaciones (delegadas) ===============
  document.addEventListener('submit', function(e){
    const formEl = e.target.closest('form.js-sa-confirm');
    if (!formEl) return;
    e.preventDefault();
    if (!window.Swal) return formEl.submit();
    Swal.fire({
      title: formEl.dataset.saTitle || '¿Estás seguro?',
      text: formEl.dataset.saText || '',
      icon: formEl.dataset.saIcon || 'warning',
      showCancelButton: true, confirmButtonText: 'Sí, continuar', cancelButtonText: 'Cancelar',
      reverseButtons: true, buttonsStyling: false,
      customClass: { popup:'sa-popup', title:'sa-title', htmlContainer:'sa-text', confirmButton:'sa-confirm', cancelButton:'sa-cancel' }
    }).then((result)=>{ if (result.isConfirmed) formEl.submit(); });
  });

  @if(session('ok'))
    if (window.Swal) Swal.fire({ toast:true, position:'top-end', icon:'success', title:@json(session('ok')), showConfirmButton:false, timer:2600, timerProgressBar:true, buttonsStyling:false, customClass:{ popup:'sa-toast', title:'sa-toast-title' } });
  @endif
  @if(session('error'))
    if (window.Swal) Swal.fire({ toast:true, position:'top-end', icon:'error', title:@json(session('error')), showConfirmButton:false, timer:3200, timerProgressBar:true, buttonsStyling:false, customClass:{ popup:'sa-toast', title:'sa-toast-title' } });
  @endif
})();
</script>
@endpush
