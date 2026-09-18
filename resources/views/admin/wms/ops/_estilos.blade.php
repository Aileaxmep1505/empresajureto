{{-- Estilos compartidos de las operaciones del WMS. Todo va con prefijo ops-
     para no chocar con clases globales del layout (p. ej. .dot o .btn). --}}
@include('partials.ui-tokens')
<style>
  .ops-wrap{ max-width:1200px; margin-inline:auto; padding:0 16px 48px; color:var(--ui-ink); }

  /* ---------- Encabezado ---------- */
  .ops-head{ display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap; margin:10px 0 18px; }
  .ops-back{ display:inline-flex; align-items:center; gap:4px; margin-bottom:8px; font-size:13px; font-weight:500;
             color:var(--ui-muted); text-decoration:none; transition:color var(--ui-fast) var(--ui-ease); }
  .ops-back:hover{ color:var(--ui-ink); }
  .ops-back svg{ width:14px; height:14px; }
  .ops-title{ margin:0; font-size:22px; font-weight:700; letter-spacing:-.015em; line-height:1.2; text-wrap:balance; }
  .ops-sub{ margin:6px 0 0; max-width:72ch; color:var(--ui-muted); font-size:14px; line-height:1.5; text-wrap:pretty; }

  /* ---------- Pestañas: subrayadas, sin caja ---------- */
  .ops-tabs{ display:flex; gap:20px; margin:0 0 20px; border-bottom:1px solid var(--ui-border); overflow-x:auto; scrollbar-width:none; }
  .ops-tabs::-webkit-scrollbar{ height:0; }
  .ops-tab{ display:inline-flex; align-items:center; gap:7px; padding:10px 0; margin-bottom:-1px; border-bottom:2px solid transparent;
            text-decoration:none; font-size:13.5px; font-weight:500; color:var(--ui-muted); white-space:nowrap;
            transition:color var(--ui-fast) var(--ui-ease), border-color var(--ui-fast) var(--ui-ease); }
  .ops-tab svg{ width:15px; height:15px; }
  .ops-tab:hover{ color:var(--ui-ink); }
  .ops-tab:focus-visible{ outline:2px solid var(--ui-accent); outline-offset:2px; border-radius:var(--ui-r-sm); }
  .ops-tab.is-active{ color:var(--ui-ink); font-weight:600; border-bottom-color:var(--ui-accent); }

  .ops-flash{ margin:0 0 16px; padding:10px 12px; border-radius:var(--ui-r); font-size:13.5px; font-weight:500; border:1px solid var(--ui-border); }
  .ops-flash.ok{ background:var(--ui-ok-soft); color:var(--ui-ok-ink); }
  .ops-flash.err{ background:var(--ui-danger-soft); color:var(--ui-danger-ink); }

  /* ---------- Pasos del proceso ---------- */
  .ops-pasos{ margin:0 0 20px; border:1px solid var(--ui-border); border-radius:var(--ui-r-lg); background:var(--ui-surface-2); overflow:hidden; }
  .ops-pasos-toggle{ display:flex; align-items:center; gap:7px; width:100%; padding:11px 14px; border:0; background:none;
                     font:inherit; font-size:13px; font-weight:600; color:var(--ui-ink-2); cursor:pointer; text-align:left; }
  .ops-pasos-toggle:hover{ color:var(--ui-ink); }
  .ops-pasos-toggle:focus-visible{ outline:2px solid var(--ui-accent); outline-offset:-2px; }
  .ops-pasos-chev{ width:15px; height:15px; color:var(--ui-muted); transition:transform var(--ui-fast) var(--ui-ease); }
  .ops-pasos.is-cerrado .ops-pasos-chev{ transform:rotate(-90deg); }
  .ops-pasos.is-cerrado .ops-pasos-lista{ display:none; }

  .ops-pasos-lista{ display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:14px 20px;
                    margin:0; padding:4px 16px 16px; list-style:none; counter-reset:none; }
  .ops-pasos-lista li{ display:flex; gap:9px; align-items:flex-start; min-width:0; }
  .ops-pasos-n{ flex:0 0 auto; display:flex; align-items:center; justify-content:center; width:20px; height:20px; margin-top:1px;
                border-radius:999px; background:var(--ui-accent-soft); color:var(--ui-accent-ink); font-size:11.5px; font-weight:700;
                font-variant-numeric:tabular-nums; }
  .ops-pasos-txt{ min-width:0; font-size:13px; line-height:1.5; color:var(--ui-muted); }
  .ops-pasos-txt b{ display:block; font-weight:600; color:var(--ui-ink); }

  /* ---------- Indicadores: una sola franja dividida, no cuatro tarjetas ---------- */
  .ops-kpis{ display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); margin-bottom:20px; background:var(--ui-surface);
             border:1px solid var(--ui-border); border-radius:var(--ui-r-lg); box-shadow:var(--ui-shadow-xs); overflow:hidden; }
  .ops-kpi{ padding:14px 16px; border-left:1px solid var(--ui-border); min-width:0; }
  .ops-kpi:first-child{ border-left:0; }
  .ops-kpi b{ display:block; font-size:22px; font-weight:700; letter-spacing:-.02em; line-height:1.15; color:var(--ui-ink);
            font-variant-numeric:tabular-nums; white-space:nowrap; }
  .ops-kpi > span{ display:block; margin-top:3px; font-size:12.5px; color:var(--ui-muted); line-height:1.35; }
  /* Valor que aún no se puede mostrar (p. ej. un conteo a ciegas abierto) */
  .ops-kpi .pend{ display:inline-block; font-size:13px; font-weight:500; color:var(--ui-muted); letter-spacing:0; }
  .ops-kpi.rojo b{ color:var(--ui-danger-ink); } .ops-kpi.ambar b{ color:var(--ui-warn-ink); }
  .ops-kpi.verde b{ color:var(--ui-ok-ink); } .ops-kpi.azul b{ color:var(--ui-ink); }

  /* ---------- Secciones ---------- */
  .ops-card{ background:var(--ui-surface); border:1px solid var(--ui-border); border-radius:var(--ui-r-lg);
             box-shadow:var(--ui-shadow-xs); margin-bottom:20px; overflow:hidden; }
  .ops-card-head{ display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; padding:14px 16px; border-bottom:1px solid var(--ui-border); }
  .ops-card-head h2{ margin:0; font-size:14.5px; font-weight:600; color:var(--ui-ink); }
  .ops-card-head p{ margin:2px 0 0; font-size:13px; color:var(--ui-muted); max-width:80ch; }
  .ops-card-body{ padding:16px; }
  .ops-actions{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

  /* ---------- Tablas ---------- */
  .ops-table-wrap{ overflow:auto; }
  .ops-table{ width:100%; border-collapse:collapse; font-size:13.5px; }
  .ops-table th{ text-align:left; padding:9px 16px; font-size:12.5px; font-weight:500; color:var(--ui-muted);
                 background:var(--ui-surface-2); border-bottom:1px solid var(--ui-border); white-space:nowrap; }
  .ops-table td{ padding:11px 16px; border-bottom:1px solid var(--ui-border); vertical-align:middle; }
  .ops-table tr:last-child td{ border-bottom:0; }
  .ops-table tbody tr{ transition:background var(--ui-fast) var(--ui-ease); }
  .ops-table tbody tr:hover td{ background:var(--ui-surface-2); }
  .ops-table .num{ text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
  .ops-prod{ font-weight:600; line-height:1.3; color:var(--ui-ink); }
  .ops-prod small{ display:block; margin-top:1px; font-weight:400; font-size:12.5px; color:var(--ui-muted); }
  .ops-loc{ display:inline-block; padding:1px 6px; border-radius:var(--ui-r-sm); background:var(--ui-surface-3); color:var(--ui-ink-2);
            font-size:12px; font-weight:500; font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; white-space:nowrap; }
  .ops-flecha{ color:var(--ui-faint); margin:0 4px; }

  .ops-pill{ display:inline-flex; align-items:center; padding:1px 8px; border-radius:999px; font-size:12px; font-weight:600; white-space:nowrap;
             background:var(--ui-surface-3); color:var(--ui-ink-2); }
  .ops-pill.rojo{ background:var(--ui-danger-soft); color:var(--ui-danger-ink); }
  .ops-pill.ambar{ background:var(--ui-warn-soft); color:var(--ui-warn-ink); }
  .ops-pill.verde{ background:var(--ui-ok-soft); color:var(--ui-ok-ink); }
  .ops-pill.azul{ background:var(--ui-accent-soft); color:var(--ui-accent-ink); }

  /* ---------- Botones ---------- */
  .ops-btn{ display:inline-flex; align-items:center; justify-content:center; gap:6px; height:34px; padding:0 12px; border-radius:var(--ui-r); cursor:pointer;
            border:1px solid var(--ui-border-strong); background:var(--ui-surface); color:var(--ui-ink-2); font:inherit; font-size:13px; font-weight:600;
            text-decoration:none; white-space:nowrap;
            transition:background var(--ui-fast) var(--ui-ease), border-color var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease); }
  .ops-btn:hover{ background:var(--ui-surface-3); color:var(--ui-ink); }
  .ops-btn:focus-visible{ outline:2px solid var(--ui-accent); outline-offset:2px; }
  .ops-btn svg{ width:15px; height:15px; }
  .ops-btn.is-primary{ background:var(--ui-accent); border-color:var(--ui-accent); color:#fff; }
  .ops-btn.is-primary:hover{ background:var(--ui-accent-hover); border-color:var(--ui-accent-hover); color:#fff; }
  .ops-btn.is-danger{ color:var(--ui-danger-ink); }
  .ops-btn.is-danger:hover{ background:var(--ui-danger-soft); border-color:var(--ui-danger); }
  .ops-btn.is-sm{ height:30px; padding:0 10px; font-size:12.5px; }
  .ops-btn:disabled{ opacity:.45; cursor:not-allowed; }

  /* ---------- Formularios ---------- */
  .ops-grid{ display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px 16px; }
  .ops-field{ display:flex; flex-direction:column; gap:6px; min-width:0; }
  .ops-field.span-2{ grid-column:span 2; }
  .ops-field label{ font-size:13px; font-weight:500; color:var(--ui-ink-2); }
  .ops-field input, .ops-field select, .ops-field textarea, .ops-input{
    width:100%; min-height:34px; border:1px solid var(--ui-border-strong); border-radius:var(--ui-r); background:var(--ui-surface); padding:6px 10px;
    font:inherit; font-size:13.5px; color:var(--ui-ink); outline:0;
    transition:border-color var(--ui-fast) var(--ui-ease), box-shadow var(--ui-fast) var(--ui-ease); }
  .ops-field input::placeholder, .ops-input::placeholder{ color:var(--ui-muted); }
  .ops-field input:hover, .ops-field select:hover, .ops-field textarea:hover, .ops-input:hover{ border-color:var(--ui-faint); }
  .ops-field input:focus, .ops-field select:focus, .ops-field textarea:focus, .ops-input:focus{ border-color:var(--ui-accent); box-shadow:0 0 0 3px var(--ui-accent-ring); }
  .ops-field select[multiple]{ padding:4px; }
  .ops-field small{ color:var(--ui-muted); font-size:12.5px; line-height:1.4; }
  .ops-check{ display:inline-flex; align-items:center; gap:8px; font-size:13.5px; font-weight:500; color:var(--ui-ink); cursor:pointer; }
  .ops-check input{ width:15px; height:15px; min-height:0; accent-color:var(--ui-accent); }
  .ops-qty{ width:88px; text-align:right; font-variant-numeric:tabular-nums; }

  .ops-empty{ padding:40px 20px; text-align:center; }
  .ops-empty h3{ margin:0 0 4px; font-size:14px; font-weight:600; color:var(--ui-ink); }
  .ops-empty p{ margin:0 auto; max-width:56ch; font-size:13.5px; color:var(--ui-muted); line-height:1.5; }

  .ops-progress{ height:6px; border-radius:999px; background:var(--ui-surface-3); overflow:hidden; }
  .ops-progress > i{ display:block; height:100%; border-radius:999px; background:var(--ui-accent); transition:width 250ms var(--ui-ease); }

  @media (max-width: 900px){
    .ops-kpis{ grid-template-columns:repeat(2, minmax(0,1fr)); }
    .ops-kpi:nth-child(odd){ border-left:0; }
    .ops-kpi:nth-child(n+3){ border-top:1px solid var(--ui-border); }
    .ops-field.span-2{ grid-column:auto; }
  }
  @media (max-width: 640px){
    .ops-wrap{ padding:0 12px 90px; }
    .ops-title{ font-size:19px; }
    .ops-table th, .ops-table td{ padding:10px 12px; }
  }
  @media (prefers-reduced-motion: reduce){
    .ops-btn, .ops-tab, .ops-back, .ops-table tbody tr, .ops-progress > i, .ops-field input, .ops-field select, .ops-input{ transition:none; }
  }
</style>
