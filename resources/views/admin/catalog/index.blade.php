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
{{-- Pantalla preparada para modo oscuro: el layout no la fuerza a claro --}}
@section('tema_oscuro', '1')
@section('title','Productos Web')

@push('styles')
@include('partials.ui-tokens')
<style>
  html,body{ background:var(--ui-surface-2); }
  .wrap{ max-width:1280px; margin-inline:auto; padding:0 16px 48px; color:var(--ui-ink); }

  /* ===================== Encabezado ===================== */
  .head{ display:flex; gap:16px; align-items:flex-end; justify-content:space-between; flex-wrap:wrap; margin:10px 0 16px; }
  .title{ margin:0; font-size:22px; font-weight:700; letter-spacing:-.015em; line-height:1.2; color:var(--ui-ink); text-wrap:balance; }
  .muted{ color:var(--ui-muted); }
  .subtxt{ margin:6px 0 0; max-width:72ch; font-size:14px; line-height:1.5; text-wrap:pretty; }
  .head-actions{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

  .btn{ display:inline-flex; align-items:center; justify-content:center; gap:6px; height:34px; padding:0 12px;
        border:1px solid var(--ui-accent); border-radius:var(--ui-r); background:var(--ui-accent); color:#fff;
        font:inherit; font-size:13px; font-weight:600; line-height:1; cursor:pointer; text-decoration:none; white-space:nowrap;
        transition:background var(--ui-fast) var(--ui-ease), border-color var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease); }
  .btn:hover{ background:var(--ui-accent-hover); border-color:var(--ui-accent-hover); color:#fff; }
  .btn:focus-visible{ outline:2px solid var(--ui-accent); outline-offset:2px; }
  .btn-sm{ height:30px; padding:0 10px; font-size:12.5px; }
  .btn-ghost, .btn-soft{ background:var(--ui-surface); border-color:var(--ui-border-strong); color:var(--ui-ink-2); }
  .btn-ghost:hover, .btn-soft:hover{ background:var(--ui-surface-3); border-color:var(--ui-border-strong); color:var(--ui-ink); }
  .ico{ display:inline-flex; width:15px; height:15px; }
  .ico svg{ width:15px; height:15px; display:block; }

  /* ===================== Tooltips ===================== */
  .tt{ position:relative; display:inline-flex; }
  .tt .tt-bubble{ position:absolute; left:50%; bottom:calc(100% + 8px); transform:translateX(-50%) translateY(2px);
                  padding:6px 9px; border-radius:var(--ui-r-sm); background:var(--ui-tip-bg); color:var(--ui-tip-ink);
                  font-size:12px; font-weight:500; white-space:nowrap; opacity:0; pointer-events:none;
                  z-index:var(--ui-z-tip); transition:opacity var(--ui-fast) var(--ui-ease), transform var(--ui-fast) var(--ui-ease); }
  .tt:hover .tt-bubble, .tt:focus-within .tt-bubble{ opacity:1; transform:translateX(-50%) translateY(0); }
  @media (hover:none){ .tt .tt-bubble{ display:none !important; } }

  .iconbtn-wrap{ display:inline-flex; position:relative; }
  .iconbtn{ display:inline-grid; place-items:center; width:30px; height:30px; border:1px solid transparent; border-radius:var(--ui-r-sm);
            background:none; color:var(--ui-muted); cursor:pointer;
            transition:background var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease), border-color var(--ui-fast) var(--ui-ease); }
  .iconbtn svg{ width:16px; height:16px; }
  .iconbtn:hover{ background:var(--ui-surface-3); color:var(--ui-ink); border-color:var(--ui-border); }
  .iconbtn:focus-visible{ outline:2px solid var(--ui-accent); outline-offset:1px; }
  .iconbtn.is-danger{ color:var(--ui-danger-ink); }
  .iconbtn.is-danger:hover{ background:var(--ui-danger-soft); border-color:var(--ui-danger); }

  /* ===================== Resumen ===================== */
  .kpis{ display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); margin-bottom:14px;
         background:var(--ui-surface); border:1px solid var(--ui-border); border-radius:var(--ui-r-lg);
         box-shadow:var(--ui-shadow-xs); overflow:hidden; }
  .kpi{ display:flex; align-items:baseline; gap:8px; flex-wrap:wrap; padding:13px 16px; border-left:1px solid var(--ui-border); min-width:0; }
  .kpi:first-child{ border-left:0; }
  .kpi .kpi-ico{ display:none; }
  .kpi b{ display:block; width:100%; font-size:21px; font-weight:700; letter-spacing:-.02em; line-height:1.15;
          color:var(--ui-ink); font-variant-numeric:tabular-nums; }
  .kpi span{ font-size:12.5px; color:var(--ui-muted); }
  .kpi.is-amb b{ color:var(--ui-warn-ink); }
  .kpi.is-red b{ color:var(--ui-danger-ink); }
  .kpi a{ margin-left:auto; font-size:12.5px; font-weight:600; color:var(--ui-accent-ink); text-decoration:none; white-space:nowrap; }
  .kpi a:hover{ text-decoration:underline; }

  /* ===================== Filtros ===================== */
  .filters{ padding:12px; background:var(--ui-surface); border:1px solid var(--ui-border);
            border-radius:var(--ui-r-lg); box-shadow:var(--ui-shadow-xs); }
  .filters-row{ display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; }

  /* Buscador */
  .search-wrap{ position:relative; flex:1 1 460px; min-width:240px; max-width:680px; }
  .search{ display:flex; align-items:center; gap:8px; height:36px; padding:0 8px 0 11px;
           background:var(--ui-surface); border:1px solid var(--ui-border-strong); border-radius:var(--ui-r);
           transition:border-color var(--ui-fast) var(--ui-ease), box-shadow var(--ui-fast) var(--ui-ease); }
  .search:hover{ border-color:var(--ui-faint); }
  .search:focus-within{ border-color:var(--ui-accent); box-shadow:0 0 0 3px var(--ui-accent-ring); }
  .search .sico{ flex:0 0 auto; display:grid; place-items:center; color:var(--ui-muted); }
  .search .sico svg{ width:16px; height:16px; }
  .search input{ flex:1; min-width:0; border:0; outline:0; background:transparent; color:var(--ui-ink);
                 font:inherit; font-size:14px; }
  .search input::placeholder{ color:var(--ui-muted); }
  .search input::-webkit-search-cancel-button{ -webkit-appearance:none; }
  .search .kbd{ flex:0 0 auto; padding:1px 6px; border:1px solid var(--ui-border); border-radius:var(--ui-r-sm);
                background:var(--ui-surface-2); color:var(--ui-muted); font-size:11.5px; font-weight:600; font-family:inherit; }
  .search:focus-within .kbd, .search.has-text .kbd{ display:none; }
  .search .sclear{ flex:0 0 auto; display:none; place-items:center; width:24px; height:24px; border:0; border-radius:999px;
                   background:var(--ui-surface-3); color:var(--ui-muted); cursor:pointer; }
  .search .sclear svg{ width:13px; height:13px; }
  .search .sclear:hover{ background:var(--ui-border); color:var(--ui-ink); }
  .search.has-text .sclear{ display:grid; }
  .search .sspin{ flex:0 0 auto; width:14px; height:14px; border-radius:50%; display:none;
                  border:2px solid var(--ui-border-strong); border-top-color:var(--ui-accent); animation:spin .7s linear infinite; }
  .search.is-loading .sspin{ display:block; }
  @keyframes spin{ to{ transform:rotate(360deg); } }
  .search-hint{ margin:7px 0 0 2px; font-size:12.5px; color:var(--ui-muted); }
  .search-hint b{ padding:0 4px; border:1px solid var(--ui-border); border-radius:4px;
                  background:var(--ui-surface-2); color:var(--ui-ink-2); font-weight:600; }

  /* Herramientas */
  .filter-tools{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-left:auto; }

  .tabs{ display:inline-flex; align-items:center; gap:2px; padding:2px; border:1px solid var(--ui-border);
         border-radius:var(--ui-r); background:var(--ui-surface-2); user-select:none; }
  .tab{ appearance:none; border:0; background:transparent; height:30px; padding:0 10px; border-radius:var(--ui-r-sm);
        cursor:pointer; font:inherit; font-size:13px; font-weight:500; color:var(--ui-muted);
        display:inline-flex; align-items:center; gap:5px; white-space:nowrap;
        transition:background var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease); }
  .tab:hover{ color:var(--ui-ink); }
  .tab:focus-visible{ outline:2px solid var(--ui-accent); outline-offset:-2px; }
  .tab.is-active{ background:var(--ui-surface); color:var(--ui-ink); font-weight:600; box-shadow:var(--ui-shadow-xs); }
  .tab .n{ padding:0 5px; border-radius:999px; background:var(--ui-surface-3); color:var(--ui-muted);
           font-size:11px; font-weight:600; font-variant-numeric:tabular-nums; }
  .tab.is-active .n{ background:var(--ui-accent-soft); color:var(--ui-accent-ink); }

  .chip{ display:inline-flex; align-items:center; gap:7px; height:34px; padding:0 12px; border-radius:var(--ui-r);
         border:1px solid var(--ui-border-strong); background:var(--ui-surface); color:var(--ui-ink-2);
         font:inherit; font-size:13px; font-weight:500; cursor:pointer; user-select:none; white-space:nowrap;
         transition:background var(--ui-fast) var(--ui-ease), border-color var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease); }
  .chip:hover{ background:var(--ui-surface-3); color:var(--ui-ink); }
  .chip input{ width:15px; height:15px; margin:0; accent-color:var(--ui-accent); }
  .chip.is-on{ background:var(--ui-accent-soft); border-color:var(--ui-accent); color:var(--ui-accent-ink); }
  .chip .n{ padding:0 6px; border-radius:999px; background:var(--ui-accent); color:#fff; font-size:11px; font-weight:600; }
  .chip .n[hidden]{ display:none; }

  /* Más filtros */
  .adv{ display:none; margin-top:12px; padding-top:12px; border-top:1px solid var(--ui-border); }
  .adv.is-open{ display:block; }
  .adv-grid{ display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:12px 14px; }
  .fld{ display:flex; flex-direction:column; gap:5px; min-width:0; }
  .fld label{ font-size:12.5px; font-weight:500; color:var(--ui-ink-2); }
  .fld select, .fld input{ width:100%; height:34px; border:1px solid var(--ui-border-strong); border-radius:var(--ui-r);
                           background:var(--ui-surface); padding:0 9px; font:inherit; font-size:13.5px; color:var(--ui-ink); outline:0;
                           transition:border-color var(--ui-fast) var(--ui-ease), box-shadow var(--ui-fast) var(--ui-ease); }
  .fld select:hover, .fld input:hover{ border-color:var(--ui-faint); }
  .fld select.fld-multi{ height:auto; padding:5px; }
  .fld select.fld-multi option{ padding:5px 8px; border-radius:6px; }
  .fld select.fld-multi option:checked{ background:var(--ui-accent-soft); color:var(--ui-accent-ink); font-weight:600; }
  .fld select:focus, .fld input:focus{ border-color:var(--ui-accent); box-shadow:0 0 0 3px var(--ui-accent-ring); }
  .fld .range{ display:flex; align-items:center; gap:6px; }
  .fld .range span{ color:var(--ui-muted); }

  /* Filtros activos */
  .active-filters{ display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-top:12px; }
  .active-filters .lbl{ font-size:12.5px; color:var(--ui-muted); }
  .afchip{ display:inline-flex; align-items:center; gap:6px; height:26px; padding:0 4px 0 9px; border-radius:var(--ui-r-sm);
           background:var(--ui-accent-soft); color:var(--ui-accent-ink); font-size:12.5px; font-weight:500; text-decoration:none; }
  .afchip b{ font-weight:600; }
  .afchip .x{ display:grid; place-items:center; width:18px; height:18px; border-radius:var(--ui-r-sm); color:var(--ui-accent-ink); }
  .afchip .x svg{ width:10px; height:10px; }
  .afchip:hover .x{ background:var(--ui-surface); }
  .afclear{ height:26px; padding:0 8px; display:inline-flex; align-items:center; border-radius:var(--ui-r-sm);
            font-size:12.5px; font-weight:500; color:var(--ui-muted); text-decoration:none; }
  .afclear:hover{ background:var(--ui-surface-3); color:var(--ui-danger-ink); }

  /* El escáner envuelve el input en .scan-campo; que crezca para llenar la barra
     y así el placeholder no se corte. */
  .search .scan-campo{ flex:1; min-width:0; display:flex; align-items:center; }

  /* ===================== Tabla ===================== */
  #listado{ position:relative; transition:opacity var(--ui-fast) var(--ui-ease); }
  #listado.is-cargando{ opacity:.5; pointer-events:none; }
  .table-wrap{ margin-top:14px; overflow:visible; background:var(--ui-surface);
               border:1px solid var(--ui-border); border-radius:var(--ui-r-lg); box-shadow:var(--ui-shadow-xs); }
  table{ width:100%; border-collapse:collapse; font-size:13.5px; }
  th{ position:sticky; top:0; z-index:var(--ui-z-sticky); padding:9px 14px; text-align:left; white-space:nowrap;
      font-size:12.5px; font-weight:500; color:var(--ui-muted); background:var(--ui-surface-2);
      border-bottom:1px solid var(--ui-border); }
  td{ padding:11px 14px; border-bottom:1px solid var(--ui-border); vertical-align:middle; }
  tbody tr:last-child td{ border-bottom:0; }
  tbody tr{ transition:background var(--ui-fast) var(--ui-ease); }
  tbody tr:hover td{ background:var(--ui-surface-2); }
  th a.sort{ display:inline-flex; align-items:center; gap:3px; color:inherit; text-decoration:none; }
  th a.sort svg{ width:13px; height:13px; opacity:0; transition:opacity var(--ui-fast) var(--ui-ease); }
  th a.sort:hover{ color:var(--ui-ink); }
  th a.sort:hover svg{ opacity:.5; }
  th a.sort.is-active{ color:var(--ui-ink); font-weight:600; }
  th a.sort.is-active svg{ opacity:1; }

  td.img-cell, th.img-cell{ width:60px; max-width:60px; }
  .thumbbox{ display:grid; place-items:center; width:44px; height:44px; overflow:hidden;
             border:1px solid var(--ui-border); border-radius:var(--ui-r); background:var(--ui-surface-3); }
  .thumbbox img{ width:100%; height:100%; object-fit:cover; display:block; }

  .name{ display:flex; flex-direction:column; gap:3px; min-width:220px; }
  .name strong{ font-size:13.5px; font-weight:600; line-height:1.3; color:var(--ui-ink); }
  .name mark{ padding:0 1px; border-radius:3px; background:var(--ui-accent-soft); color:var(--ui-accent-ink); }
  .meta{ display:flex; gap:10px; flex-wrap:wrap; align-items:center; font-size:12.5px; color:var(--ui-muted); }
  .meta .k{ color:var(--ui-muted); }
  .meta .v{ color:var(--ui-ink-2); font-weight:500; }
  .badges{ display:flex; gap:5px; flex-wrap:wrap; margin-top:3px; }

  .badge{ display:inline-flex; align-items:center; gap:5px; padding:1px 7px; border-radius:999px; white-space:nowrap;
          font-size:11.5px; font-weight:600; background:var(--ui-surface-3); color:var(--ui-ink-2); }
  .badge .dot{ width:5px; height:5px; border-radius:999px; background:var(--ui-faint); }
  .b-live{ background:var(--ui-ok-soft); color:var(--ui-ok-ink); }
  .b-live .dot{ background:var(--ui-ok); }
  .b-draft .dot{ background:var(--ui-faint); }
  .b-hidden{ background:var(--ui-surface-3); color:var(--ui-ink-2); }
  .b-hidden .dot{ background:var(--ui-ink-2); }
  .b-sample{ background:var(--ui-warn-soft); color:var(--ui-warn-ink); }
  .b-sample .dot{ background:var(--ui-warn); }
  .b-star{ background:var(--ui-accent-soft); color:var(--ui-accent-ink); }
  .b-star .dot{ background:var(--ui-accent); }
  .b-crit{ background:var(--ui-danger-soft); color:var(--ui-danger-ink); }
  .b-crit .dot{ background:var(--ui-danger); }
  .b-holder{ background:var(--ui-warn-soft); color:var(--ui-warn-ink); }
  .b-holder .dot{ background:var(--ui-warn); }
  .b-chan{ background:var(--ui-surface-3); color:var(--ui-muted); font-weight:500; }
  .b-chan.is-err{ background:var(--ui-danger-soft); color:var(--ui-danger-ink); }
  /* El layout define una .dot global (insignia de notificaciones); aquí se neutraliza. */
  .badge .dot, .stock-pill .dot{ position:static; display:inline-block; flex:0 0 auto; min-width:0; padding:0;
                                 box-shadow:none; animation:none; transform:none; opacity:1; }

  .cat{ font-size:13px; color:var(--ui-ink-2); }
  .cat small{ display:block; margin-top:1px; font-size:12px; color:var(--ui-muted); }

  .price{ font-weight:600; color:var(--ui-ink); white-space:nowrap; font-variant-numeric:tabular-nums; }
  .sale{ font-weight:600; color:var(--ui-ok-ink); white-space:nowrap; font-variant-numeric:tabular-nums; }
  .muted-sm{ font-size:12.5px; color:var(--ui-muted); }

  .stock-pill{ display:inline-flex; align-items:center; gap:6px; padding:2px 8px; border-radius:999px; white-space:nowrap;
               background:var(--ui-surface-3); color:var(--ui-ink-2); font-size:13px; font-weight:600; font-variant-numeric:tabular-nums; }
  .stock-pill .dot{ width:5px; height:5px; border-radius:999px; background:var(--ui-ok); }
  .stock-pill.is-critical{ background:var(--ui-danger-soft); color:var(--ui-danger-ink); }
  .stock-pill.is-critical .dot{ background:var(--ui-danger); }
  .stock-pill.is-empty{ background:var(--ui-surface-3); color:var(--ui-muted); }
  .stock-pill.is-empty .dot{ background:var(--ui-faint); }
  .stock-meta{ margin-top:4px; font-size:12px; color:var(--ui-muted); white-space:nowrap; font-variant-numeric:tabular-nums; }

  /* Filas con aviso: tinte parejo y un punto de color en la primera celda. */
  tr.is-critical-row td{ background:var(--ui-danger-soft) !important; }
  tr.is-critical-row:hover td{ background:color-mix(in srgb, var(--ui-danger) 14%, var(--ui-surface)) !important; }
  tr.is-sample-row td{ background:var(--ui-warn-soft) !important; }
  tr.is-sample-row:hover td{ background:color-mix(in srgb, var(--ui-warn) 16%, var(--ui-surface)) !important; }
  tr.is-sample-row.is-critical-row td{ background:var(--ui-warn-soft) !important; }
  tr.is-sample-row .thumbbox{ border-color:var(--ui-warn); }
  tr.is-critical-row .thumbbox{ border-color:var(--ui-danger); }

  .legend{ display:inline-flex; align-items:center; gap:14px; font-size:12.5px; color:var(--ui-muted); }
  .legend span{ display:inline-flex; align-items:center; gap:6px; }
  .legend .sw{ width:12px; height:12px; border-radius:4px; border:1px solid var(--ui-border); }
  .legend .sw-red{ background:var(--ui-danger-soft); border-color:var(--ui-danger); }
  .legend .sw-amb{ background:var(--ui-warn-soft); border-color:var(--ui-warn); }

  .actions{ display:flex; gap:2px; justify-content:flex-end; flex-wrap:wrap; }

  .empty{ padding:52px 20px; text-align:center; }
  .empty .eico{ display:inline-grid; place-items:center; width:40px; height:40px; margin-bottom:12px;
                border-radius:var(--ui-r); background:var(--ui-surface-3); color:var(--ui-muted); }
  .empty .eico svg{ width:20px; height:20px; }
  .empty h3{ margin:0 0 4px; font-size:15px; font-weight:600; color:var(--ui-ink); }
  .empty p{ margin:0 auto 16px; max-width:52ch; font-size:13.5px; line-height:1.5; color:var(--ui-muted); }

  .foot{ display:flex; align-items:center; justify-content:space-between; gap:14px; margin:14px 2px; flex-wrap:wrap; }
  .foot-left{ display:flex; align-items:center; gap:16px; flex-wrap:wrap; font-size:13px; color:var(--ui-muted); }
  .perpage{ display:inline-flex; align-items:center; gap:6px; font-size:13px; color:var(--ui-muted); }
  .perpage select{ height:28px; padding:0 6px; border:1px solid var(--ui-border-strong); border-radius:var(--ui-r-sm);
                   background:var(--ui-surface); font:inherit; font-size:12.5px; color:var(--ui-ink); }

  /* ===================== Modales ===================== */
  .stock-modal, .dl-modal{ position:fixed; inset:0; display:flex; align-items:center; justify-content:center;
                           z-index:var(--ui-z-modal); pointer-events:none; opacity:0; transition:opacity var(--ui-fast) var(--ui-ease); }
  .stock-modal.is-open, .dl-modal.is-open{ pointer-events:auto; opacity:1; }
  .stock-modal__overlay, .dl-modal__overlay{ position:absolute; inset:0; background:oklch(0.23 0.02 262 / .45); }
  .stock-modal__card, .dl-modal__card{ position:relative; z-index:1; width:min(400px, calc(100vw - 24px));
                                       background:var(--ui-surface); border:1px solid var(--ui-border); border-radius:var(--ui-r-lg);
                                       box-shadow:var(--ui-shadow-pop); padding:16px; }
  .stock-modal__head, .dl-modal__head{ display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:12px; }
  .stock-modal__title, .dl-modal__title{ margin:0; font-size:15px; font-weight:600; color:var(--ui-ink); }
  .stock-modal__subtitle, .dl-modal__subtitle{ margin:3px 0 0; font-size:13px; color:var(--ui-muted); }
  .stock-modal__close, .dl-modal__close{ display:grid; place-items:center; width:28px; height:28px; border:0;
                                         border-radius:var(--ui-r-sm); background:none; color:var(--ui-muted); cursor:pointer; }
  .stock-modal__close svg, .dl-modal__close svg{ width:15px; height:15px; }
  .stock-modal__close:hover, .dl-modal__close:hover{ background:var(--ui-surface-3); color:var(--ui-ink); }
  .stock-field-label{ margin-bottom:5px; font-size:12.5px; font-weight:500; color:var(--ui-ink-2); }
  .stock-input-wrap{ display:flex; align-items:center; gap:8px; }
  .stock-input{ flex:1; height:36px; padding:0 10px; border:1px solid var(--ui-border-strong); border-radius:var(--ui-r);
                background:var(--ui-surface); font:inherit; font-size:14px; text-align:right; color:var(--ui-ink); outline:0;
                font-variant-numeric:tabular-nums; }
  .stock-input:focus{ border-color:var(--ui-accent); box-shadow:0 0 0 3px var(--ui-accent-ring); }
  .stock-modal__foot{ display:flex; justify-content:flex-end; gap:8px; margin-top:16px; }
  .dl-modal__body{ display:grid; gap:8px; }
  .dl-modal__body .btn{ justify-content:flex-start; height:38px; }

  /* ===================== Móvil ===================== */
  .fab{ position:fixed; right:16px; bottom:18px; z-index:var(--ui-z-fab); display:none; place-items:center;
        width:52px; height:52px; border:1px solid var(--ui-border); border-radius:999px;
        background:var(--ui-surface); box-shadow:var(--ui-shadow-pop); cursor:pointer; }
  .fab svg{ width:20px; height:20px; color:var(--ui-ink-2); }
  .fab .fab-n{ position:absolute; top:-3px; right:-3px; min-width:19px; height:19px; padding:0 5px; border-radius:999px;
               background:var(--ui-accent); color:#fff; font-size:11px; font-weight:600; display:none; align-items:center; justify-content:center; }
  .fab .fab-n.is-on{ display:inline-flex; }

  .sheet-overlay{ position:fixed; inset:0; z-index:var(--ui-z-backdrop); background:oklch(0.23 0.02 262 / .45);
                  opacity:0; pointer-events:none; transition:opacity var(--ui-fast) var(--ui-ease); }
  .sheet{ position:fixed; left:0; right:0; bottom:-100%; z-index:var(--ui-z-modal); max-height:88vh; overflow:auto;
          padding:10px 14px 20px; background:var(--ui-surface); border-top:1px solid var(--ui-border);
          border-radius:var(--ui-r-lg) var(--ui-r-lg) 0 0; box-shadow:var(--ui-shadow-pop);
          transition:bottom 240ms var(--ui-ease); }
  .sheet .grab{ width:36px; height:4px; margin:2px auto 12px; border-radius:999px; background:var(--ui-border-strong); }
  .sheet .sheet-title{ display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:12px; }
  .sheet .sheet-title h3{ margin:0; font-size:15px; font-weight:600; }
  .sheet .sheet-close{ display:grid; place-items:center; width:30px; height:30px; border:0; border-radius:var(--ui-r-sm);
                       background:none; color:var(--ui-muted); cursor:pointer; }
  .sheet .sheet-close svg{ width:16px; height:16px; }
  .sheet-body{ display:grid; gap:14px; }
  .sheet-body .filter-tools{ display:grid; gap:10px; }
  .sheet-body .tabs{ width:100%; overflow-x:auto; scrollbar-width:none; justify-content:flex-start; }
  .sheet-body .tabs::-webkit-scrollbar{ height:0; }
  .sheet-body .tab{ height:36px; }
  .sheet-body .chip{ justify-content:center; height:40px; }
  .sheet-body .adv{ display:block; margin-top:0; padding-top:0; border-top:0; }
  .sheet-body .adv-grid{ grid-template-columns:1fr 1fr; }
  .sheet-actions{ display:grid; gap:8px; margin-top:16px; }
  .sheet-actions .btn{ height:42px; justify-content:center; }
  .sheet-open .sheet-overlay{ opacity:1; pointer-events:auto; }
  .sheet-open .sheet{ bottom:0; }

  @media (max-width: 1100px){
    .kpis{ grid-template-columns:repeat(2, minmax(0,1fr)); }
    .kpi:nth-child(odd){ border-left:0; }
    .kpi:nth-child(n+3){ border-top:1px solid var(--ui-border); }
  }
  @media (max-width: 760px){
    .wrap{ padding:0 12px 90px; }
    body{ padding-bottom:80px; }
    .fab{ display:grid; }
    .title{ font-size:19px; }
    .head .tt-new, .head .tt-download, .head .tt-analytics{ display:none !important; }
    .search{ height:42px; }
    .search input{ font-size:16px; } /* 16px evita el zoom automático de iOS */
    .search .kbd, .search-hint, .adv-toggle{ display:none !important; }
    .active-filters{ margin-top:10px; }

    /* Cada producto pasa a ser una ficha */
    .table-wrap{ border:0; background:transparent; box-shadow:none; overflow:visible; }
    table, thead, tbody, th, td, tr{ display:block; }
    thead{ display:none; }
    tbody tr{ position:relative; margin:10px 0; padding:12px; background:var(--ui-surface);
              border:1px solid var(--ui-border); border-radius:var(--ui-r-lg); box-shadow:var(--ui-shadow-xs); }
    tbody tr:hover td, tbody tr td{ background:transparent !important; border:0; padding:0; }
    tr.is-critical-row{ border-color:var(--ui-danger); background:var(--ui-danger-soft); }
    tr.is-sample-row{ border-color:var(--ui-warn); background:var(--ui-warn-soft); }
    tr.is-sample-row.is-critical-row{ border-color:var(--ui-danger); background:var(--ui-warn-soft); }
    td + td{ margin-top:8px; }
    td.img-cell{ width:auto !important; max-width:none !important; margin-bottom:10px; }
    .thumbbox{ width:60px; height:60px; }
    .name{ min-width:0; }
    .actions{ justify-content:flex-start; gap:4px; margin-top:12px; padding-top:10px; border-top:1px solid var(--ui-border); }
    .iconbtn{ width:40px; height:40px; border:1px solid var(--ui-border); }
    .foot{ justify-content:center; }
    .foot-left{ justify-content:center; width:100%; }
  }

  /* ===================== SweetAlert ===================== */
  .swal2-popup.sa-popup{ border-radius:var(--ui-r-lg); padding:22px; border:1px solid var(--ui-border); background:var(--ui-surface); color:var(--ui-ink);
                         box-shadow:var(--ui-shadow-pop); font-family:inherit; }
  .swal2-icon{ box-shadow:none !important; }
  .swal2-popup.sa-popup .swal2-icon{ margin-top:0; margin-bottom:6px; }
  .swal2-title.sa-title{ margin:6px 0 2px; font-size:17px; font-weight:600; color:var(--ui-ink); }
  .swal2-html-container.sa-text{ margin:4px 0 0; font-size:13.5px; color:var(--ui-muted); }
  .swal2-actions{ margin-top:18px; gap:8px; }
  .swal2-confirm.sa-confirm, .swal2-cancel.sa-cancel{ height:36px; padding:0 16px; border-radius:var(--ui-r);
                                                      font-size:13px; font-weight:600; box-shadow:none; }
  .swal2-confirm.sa-confirm{ background:var(--ui-accent); color:#fff; border:0; }
  .swal2-confirm.sa-confirm:hover{ background:var(--ui-accent-hover); }
  .swal2-cancel.sa-cancel{ background:var(--ui-surface); color:var(--ui-ink-2); border:1px solid var(--ui-border-strong); }
  .swal2-cancel.sa-cancel:hover{ background:var(--ui-surface-3); }
  .swal2-popup.sa-toast{ border-radius:var(--ui-r); padding:10px 14px; border:1px solid var(--ui-border);
                         background:var(--ui-tip-bg); color:var(--ui-tip-ink); box-shadow:var(--ui-shadow-pop); }
  .swal2-popup.sa-toast .swal2-title.sa-toast-title{ font-size:13.5px; font-weight:500; color:inherit; }
  .swal2-popup.sa-toast .swal2-icon{ margin:0 8px 0 0; transform:scale(.8); }

  /* ===================== Paginación ===================== */
  .pagi{ display:flex; align-items:center; justify-content:flex-end; gap:4px; flex-wrap:wrap; }
  .pagi .page{ display:inline-flex; align-items:center; justify-content:center; min-width:32px; height:32px; padding:0 9px;
               border:1px solid transparent; border-radius:var(--ui-r-sm); background:none; color:var(--ui-ink-2);
               font-size:13px; font-weight:500; text-decoration:none; font-variant-numeric:tabular-nums;
               transition:background var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease); }
  .pagi .page:hover{ background:var(--ui-surface-3); color:var(--ui-ink); }
  .pagi .page.is-active{ background:var(--ui-accent); color:#fff; font-weight:600; }
  .pagi .page.is-disabled{ opacity:.35; pointer-events:none; }
  .pagi .page.is-ellipsis{ pointer-events:none; color:var(--ui-muted); }
  .pagi .page svg{ width:15px; height:15px; display:block; }
  @media (max-width: 760px){ .pagi{ justify-content:center; } .pagi .page{ min-width:40px; height:40px; } }

  /* ===================== Paleta extra ===================== */
  :root{ --c-slate:#94a3b8; --c-violet:#a78bfa; }

  /* ===================== Resumen: tarjetas con gráfica integrada ===================== */
  .stats{ display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:12px; margin-bottom:16px; }
  .stat{ display:flex; flex-direction:column; gap:10px; padding:18px; background:var(--ui-surface);
         border:1px solid var(--ui-border); border-radius:18px; min-width:0; }
  .stat-head{ display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
  .stat-label{ font-size:12.5px; font-weight:500; color:var(--ui-muted); letter-spacing:-.01em; }
  .stat-num{ font-size:27px; font-weight:600; letter-spacing:-.025em; line-height:1; color:var(--ui-ink); font-variant-numeric:tabular-nums; }
  .stat-cap{ margin-top:-4px; font-size:12px; color:var(--ui-muted); }
  .stat-link{ font-size:12px; font-weight:500; color:var(--ui-accent-ink); text-decoration:none; white-space:nowrap; }
  .stat-link:hover{ text-decoration:underline; }
  .stat-row{ display:flex; align-items:center; justify-content:space-between; gap:14px; }

  /* Dona compacta */
  .donut{ --seg:conic-gradient(var(--ui-ok) 0 360deg); position:relative; width:54px; height:54px; flex:0 0 auto; border-radius:50%; background:var(--seg); }
  .donut::after{ content:""; position:absolute; inset:8px; border-radius:50%; background:var(--ui-surface); }
  .donut-hole{ position:absolute; inset:0; display:grid; place-content:center; z-index:1; font-size:12px; font-weight:600; color:var(--ui-ink-2); font-variant-numeric:tabular-nums; }

  /* Leyenda */
  .leg{ list-style:none; margin:0; padding:0; display:flex; flex-wrap:wrap; gap:5px 14px; }
  .leg li{ display:flex; align-items:center; gap:7px; font-size:12px; color:var(--ui-muted); white-space:nowrap; }
  .leg b{ color:var(--ui-ink-2); font-weight:600; font-variant-numeric:tabular-nums; }
  .leg i{ width:8px; height:8px; border-radius:3px; flex:0 0 auto; }

  /* Barra apilada (salud de stock) */
  .stackbar{ display:flex; width:100%; height:8px; border-radius:999px; overflow:hidden; background:var(--ui-surface-3); }
  .stackbar .seg{ height:100%; }
  .stackbar .seg-ok{ background:var(--ui-ok); }
  .stackbar .seg-amb{ background:var(--ui-warn); }
  .stackbar .seg-red{ background:var(--ui-danger); }

  /* Mini barras (alcance) */
  .mbar + .mbar{ margin-top:12px; }
  .mbar-top{ display:flex; align-items:baseline; justify-content:space-between; font-size:12px; color:var(--ui-muted); margin-bottom:6px; }
  .mbar-top b{ color:var(--ui-ink-2); font-weight:600; font-variant-numeric:tabular-nums; }
  .mbar-track{ height:6px; border-radius:999px; background:var(--ui-surface-3); overflow:hidden; }
  .mbar-fill{ display:block; height:100%; border-radius:999px; }
  .mbar-blue{ background:var(--ui-accent); }
  .mbar-violet{ background:var(--c-violet); }

  @media (max-width: 900px){ .stats{ grid-template-columns:repeat(2, minmax(0,1fr)); } }
  @media (max-width: 560px){ .stats{ grid-template-columns:1fr; } }

  /* ===================== Vista de tarjetas ===================== */
  .viewtabs .tab svg{ width:15px; height:15px; }
  .pcards{ display:grid; grid-template-columns:repeat(auto-fill, minmax(238px, 1fr)); gap:14px; margin-top:14px; }
  .pcard{ position:relative; display:flex; flex-direction:column; background:var(--ui-surface);
          border:1px solid var(--ui-border); border-radius:16px; overflow:hidden;
          transition:border-color var(--ui-fast) var(--ui-ease), box-shadow var(--ui-fast) var(--ui-ease); }
  .pcard:hover{ border-color:var(--ui-border-strong); box-shadow:var(--ui-shadow-xs); }
  .pcard.is-critical{ border-color:var(--ui-danger); }
  .pcard.is-sample{ border-color:var(--ui-warn); }
  .pcard-media{ position:relative; aspect-ratio:4/3; background:var(--ui-surface-3); overflow:hidden; }
  .pcard-media img{ width:100%; height:100%; object-fit:cover; display:block; }
  .pcard-badges{ position:absolute; top:8px; left:8px; display:flex; gap:5px; flex-wrap:wrap; max-width:calc(100% - 52px); }
  .pcard-menu{ position:absolute; top:8px; right:8px; }
  .pcard-body{ display:flex; flex-direction:column; gap:9px; padding:13px 14px 14px; flex:1; }
  .pcard-title{ font-size:14px; font-weight:600; line-height:1.3; color:var(--ui-ink); text-decoration:none; }
  .pcard-title:hover{ color:var(--ui-accent-ink); }
  .pcard-title mark{ padding:0 1px; border-radius:3px; background:var(--ui-accent-soft); color:var(--ui-accent-ink); }
  .pcard-meta{ display:flex; gap:6px 12px; flex-wrap:wrap; font-size:12px; color:var(--ui-muted); }
  .pcard-meta .v{ color:var(--ui-ink-2); font-weight:500; }
  .pcard-foot{ display:flex; align-items:center; justify-content:space-between; gap:10px; margin-top:auto; padding-top:10px; border-top:1px solid var(--ui-border); }
  .pcard-price{ font-weight:600; color:var(--ui-ink); font-variant-numeric:tabular-nums; }
  .pcard-price .sale{ color:var(--ui-ok-ink); }
  .pcard-price .was{ font-size:11.5px; font-weight:500; color:var(--ui-muted); text-decoration:line-through; }
  .pcard-date{ font-size:11.5px; color:var(--ui-muted); display:inline-flex; align-items:center; gap:5px; white-space:nowrap; }
  .pcard-date svg{ width:12px; height:12px; }

  /* Fecha en la fila (tabla) */
  .rowdate{ display:flex; flex-direction:column; gap:2px; font-size:12px; color:var(--ui-muted); white-space:nowrap; }
  .rowdate .v{ color:var(--ui-ink-2); font-weight:500; }

  /* ===================== Menú de acciones (tres puntitos) ===================== */
  .actions-cell{ text-align:right; }
  .rowmenu{ position:relative; display:inline-flex; }
  .rowmenu-btn{ display:inline-grid; place-items:center; width:30px; height:30px; border:0; border-radius:999px;
                background:none; color:var(--ui-muted); cursor:pointer;
                transition:background var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease); }
  .rowmenu-btn:hover,
  .rowmenu-btn.is-open{ background:var(--ui-surface-3); color:var(--ui-ink); }
  .rowmenu-btn svg{ width:18px; height:18px; }
  .pcard-menu .rowmenu-btn{ background:none; color:#fff; filter:drop-shadow(0 1px 2px rgba(0,0,0,.55)); }
  .pcard-menu .rowmenu-btn:hover,
  .pcard-menu .rowmenu-btn.is-open{ background:oklch(1 0 0 / .2); color:#fff; filter:none; }

  .rowmenu-pop{ position:fixed; z-index:var(--ui-z-pop); min-width:210px; max-width:264px; padding:6px;
                background:var(--ui-surface); border:1px solid var(--ui-border); border-radius:var(--ui-r);
                box-shadow:var(--ui-shadow-pop); display:none; }
  .rowmenu-pop.is-open{ display:block; }
  .rowmenu-pop .rm-sep{ height:1px; margin:5px 4px; background:var(--ui-border); }
  .rowmenu-pop .rm-lbl{ padding:5px 9px 3px; font-size:10.5px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--ui-muted); }
  .rm-item{ display:flex; align-items:center; gap:10px; width:100%; padding:8px 9px; border:0; border-radius:var(--ui-r-sm);
            background:none; color:var(--ui-ink-2); font:inherit; font-size:13px; font-weight:500; text-align:left; cursor:pointer;
            text-decoration:none; white-space:nowrap; transition:background var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease); }
  .rm-item svg{ width:16px; height:16px; flex:0 0 auto; color:var(--ui-muted); }
  .rm-item:hover{ background:var(--ui-surface-3); color:var(--ui-ink); }
  .rm-item:hover svg{ color:var(--ui-ink-2); }
  .rm-item.is-danger{ color:var(--ui-danger-ink); }
  .rm-item.is-danger svg{ color:var(--ui-danger-ink); }
  .rm-item.is-danger:hover{ background:var(--ui-danger-soft); }
  .rowmenu-pop form{ margin:0; }

  /* ===================== Reveal al hacer scroll ===================== */
  .reveal{ opacity:0; transform:translateY(14px);
           transition:opacity .5s var(--ui-ease), transform .5s var(--ui-ease);
           transition-delay:calc(min(var(--i, 0), 6) * 45ms); }
  .reveal.is-in{ opacity:1; transform:none; }

  @media (prefers-reduced-motion: reduce){
    #listado, .btn, .chip, .tab, .iconbtn, .sheet, .sheet-overlay,
    .stock-modal, .dl-modal, .search, .fld input, .fld select, .pagi .page, th a.sort svg,
    .pcard, .rowmenu-btn, .rm-item{ transition:none !important; }
    .reveal{ opacity:1 !important; transform:none !important; transition:none !important; }
  }
</style>
@endpush

@section('content')
@php
  $st = (string) ($filters['status'] ?? '');
@endphp

<div class="wrap">
  @include('admin.catalog._toast')

  {{-- ===================== Encabezado ===================== --}}
  <div class="head">
    <div>
      <h1 class="title" id="tituloLista">{{ $tituloLista }}</h1>
    </div>

    <div class="head-actions">
      <button type="button" class="tour-abrir" data-tour-start="inventario">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="9"/><path d="M9.6 9.4a2.5 2.5 0 1 1 3.3 2.9c-.6.2-.9.8-.9 1.4v.3"/><path d="M12 17h.01"/>
        </svg>
        ¿Cómo funciona?
      </button>

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
  <div id="kpisBox" data-tour="kpis">
    @include('admin.catalog._kpis')
  </div>

  {{-- ===================== Filtros ===================== --}}
  <div class="filters">
    <form id="filtersForm" method="GET" action="{{ route('admin.catalog.index') }}" autocomplete="off">
      <div class="filters-row">
        <div class="search-wrap" data-tour="buscar">
          <div class="search {{ $filters['s'] !== '' ? 'has-text' : '' }}" id="searchBox">
            <span class="sico">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            </span>
            <input id="sInput" type="search" name="s"
                   placeholder="Buscar o escanear producto…"
                   value="{{ $filters['s'] }}" autocomplete="off" spellcheck="false" aria-label="Buscar productos"
                   data-scan data-scan-enviar="no"
                   data-scan-titulo="Escanea el producto"
                   data-scan-ayuda="Apunta al código de barras de la caja o del producto. La lista se filtra sola." />
            <span class="sspin" aria-hidden="true"></span>
            <button type="button" class="sclear" id="sClear" aria-label="Limpiar búsqueda">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
          </div>
        </div>

        {{-- Todo lo que sigue se mueve a la hoja inferior en móvil --}}
        <div class="filter-tools" id="filterTools" data-tour="filtros">

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

          <div class="tt">
            <span class="tt-bubble">Cambiar entre lista y tarjetas</span>
            <div class="tabs viewtabs" role="tablist" aria-label="Modo de vista">
              <button type="button" class="tab {{ $view === 'list' ? 'is-active' : '' }}" data-view="list" aria-label="Ver como lista">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                Lista
              </button>
              <button type="button" class="tab {{ $view === 'cards' ? 'is-active' : '' }}" data-view="cards" aria-label="Ver como tarjetas">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                Tarjetas
              </button>
            </div>
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

              <div class="fld" style="grid-column:1 / -1;">
                <label for="fExclude">Excluir categorías (y sus subcategorías)</label>
                <select id="fExclude" name="exclude_cats[]" form="filtersForm" data-filtro multiple size="5" class="fld-multi">
                  @foreach($categorias as $c)
                    <option value="{{ $c->id }}" @selected(in_array((int) $c->id, (array) ($filters['exclude_cats'] ?? []), true))>{{ $c->full_path ?: $c->name }}</option>
                  @endforeach
                </select>
                <span class="hint" style="display:block;margin-top:6px;">
                  Quita esas categorías del listado, del <b>Valor del inventario</b> y del PDF. Mantén Ctrl (Cmd en Mac) para elegir varias; para ver solo papelería, excluye Juguetes y las demás.
                </span>
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
      <input type="hidden" name="view" id="viewInput" value="{{ $view }}">
    </form>
  </div>

  {{-- ===================== Lista ===================== --}}
  <div id="listado" aria-live="polite" data-tour="lista">
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
  const viewInput    = document.getElementById('viewInput');
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
    new FormData(form).forEach((v, k)=>{ v = String(v).trim(); if (v === '') return; if (k.endsWith('[]')) p.append(k, v); else p.set(k, v); });
    if (p.get('per_page') === '20') p.delete('per_page');
    if (p.get('sort') === 'recent') p.delete('sort');
    if (p.get('view') === 'list') p.delete('view');
    p.delete('page');
    Object.entries(extra || {}).forEach(([k, v])=>{ if (v === null || v === '') p.delete(k); else p.set(k, v); });
    const qs = p.toString();
    return INDEX_URL + (qs ? '?' + qs : '');
  }

  async function cargar(url, opciones){
    opciones = opciones || {};
    if (typeof rmClose === 'function') rmClose();   // cierra el menú de acciones antes de rehacer la lista
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
      if (typeof revelar === 'function') revelar();
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
    document.querySelectorAll('.tab[data-view]').forEach(b => b.classList.toggle('is-active', b.dataset.view === viewInput.value));
    featured.closest('.chip').classList.toggle('is-on', featured.checked);

    const avanzados = ['category','brand','stock','ml','price_min','price_max'].filter(n => (form.elements[n]?.value || '') !== '').length
                    + ((form.elements['sort']?.value || 'recent') !== 'recent' ? 1 : 0)
                    + (document.querySelectorAll('#fExclude option:checked').length > 0 ? 1 : 0);
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
    const excluidas = p.getAll('exclude_cats[]');
    document.querySelectorAll('#fExclude option').forEach(o => { o.selected = excluidas.includes(o.value); });
    viewInput.value = p.get('view') === 'cards' ? 'cards' : 'list';
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
  document.querySelectorAll('.tab[data-view]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      if (viewInput.value === btn.dataset.view) return;
      viewInput.value = btn.dataset.view;
      marcarTabs();
      aplicarFiltros();
    });
  });
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

  // =============== Menú de acciones "tres puntitos" (delegado) ===============
  // El panel se saca al <body> mientras está abierto para que no lo recorte el
  // scroll de la tabla ni lo descoloque el transform de una tarjeta.
  let rmOpen = null;
  function rmClose(){
    if (!rmOpen) return;
    const { btn, pop, home } = rmOpen;
    pop.classList.remove('is-open');
    btn.classList.remove('is-open');
    btn.setAttribute('aria-expanded', 'false');
    if (home && pop.parentElement !== home) home.appendChild(pop);
    rmOpen = null;
  }
  function rmPosition(btn, pop){
    pop.style.visibility = 'hidden';
    pop.classList.add('is-open');
    const r = btn.getBoundingClientRect();
    const pw = pop.offsetWidth, ph = pop.offsetHeight, gap = 6, m = 8;
    let left = r.right - pw;
    left = Math.max(m, Math.min(left, window.innerWidth - m - pw));
    let top = r.bottom + gap;
    if (top + ph > window.innerHeight - m) top = Math.max(m, r.top - gap - ph);
    pop.style.left = Math.round(left) + 'px';
    pop.style.top  = Math.round(top) + 'px';
    pop.style.visibility = '';
  }
  document.addEventListener('click', (e)=>{
    const btn = e.target.closest('.rowmenu-btn');
    if (btn){
      e.preventDefault();
      const home = btn.parentElement;
      const pop = home.querySelector('.rowmenu-pop');
      if (!pop) return;
      const yaAbierto = rmOpen && rmOpen.pop === pop;
      rmClose();
      if (!yaAbierto){
        document.body.appendChild(pop);
        rmOpen = { btn, pop, home };
        btn.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
        rmPosition(btn, pop);
      }
      return;
    }
    // Clic dentro del panel: dejar que el enlace/submit haga lo suyo.
    if (e.target.closest('.rowmenu-pop')) return;
    rmClose();
  });
  window.addEventListener('scroll', rmClose, true);
  window.addEventListener('resize', rmClose);
  document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') rmClose(); });

  // =============== Reveal al hacer scroll (tabla y tarjetas) ===============
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let revObserver = null;
  if (!reduceMotion && 'IntersectionObserver' in window){
    revObserver = new IntersectionObserver((entries)=>{
      entries.forEach(en=>{
        if (en.isIntersecting){ en.target.classList.add('is-in'); revObserver.unobserve(en.target); }
      });
    }, { rootMargin: '0px 0px -6% 0px', threshold: 0.06 });
  }
  function revelar(){
    const items = listado.querySelectorAll('.reveal:not(.is-in)');
    if (!revObserver){ items.forEach(el=>el.classList.add('is-in')); return; }
    items.forEach(el=>revObserver.observe(el));
  }
  revelar(); // primera carga

  @if(session('ok'))
    if (window.showToast) window.showToast(@json(session('ok')), 'ok');
  @endif
  @if(session('error'))
    if (window.showToast) window.showToast(@json(session('error')), 'error');
  @endif
})();
</script>
@endpush

@push('scripts')
  @include('partials.ui-tour')
  @include('partials.ui-scanner')
  <script>
    UITour.registrar('inventario', {
      version: 1,
      auto: true,
      pasos: [
        { el: '[data-tour="buscar"]', titulo: 'Busca o escanea',
          texto: 'Escribe lo que sea: nombre, SKU, marca o código de barras. La lista se filtra al momento, sin recargar. Con el icono de cámara puedes leer el código con el celular.' },
        { el: '[data-tour="filtros"]', titulo: 'Filtra por estado',
          texto: 'Publicado, borrador u oculto; catálogo o muestras. En Más filtros están categoría, marca, existencias y precio.' },
        { el: '[data-tour="kpis"]', titulo: 'Lo que hay que atender',
          texto: 'Stock crítico y sin existencia traen un enlace Ver que deja la lista con solo esos productos.' },
        { el: '[data-tour="lista"]', titulo: 'La lista',
          texto: 'Cámbiala entre Lista y Tarjetas con el botón de arriba. Las filas rojas están en stock crítico y las ámbar son muestras. Con el botón de tres puntitos (⋮) de cada producto abres el menú: ver, ajustar stock, editar, publicar, Mercado Libre y eliminar.' },
      ],
    });
  </script>
@endpush
