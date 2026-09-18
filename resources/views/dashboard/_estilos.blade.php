@include('partials.ui-tokens')
<style>
    /* ===================== Encabezado del tablero ===================== */
    .dash-top { display:flex; align-items:flex-end; justify-content:space-between; gap:16px;
                flex-wrap:wrap; margin:6px 0 20px; }
    .dash-hero { min-width:0; }
    .dash-fecha { margin:0 0 4px; font-size:13px; font-weight:500; color:var(--ui-muted); }
    .dash-saludo { margin:0; font-size:24px; font-weight:700; letter-spacing:-.015em;
                   line-height:1.2; color:var(--ui-ink); text-wrap:balance; }
    .dash-frase { margin:6px 0 0; max-width:68ch; color:var(--ui-muted); font-size:14px; line-height:1.5; text-wrap:pretty; }
    .dash-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

    .dash-aviso { margin:0 0 16px; padding:10px 12px; border:1px solid var(--ui-border);
                  border-radius:var(--ui-r); background:var(--ui-ok-soft); color:var(--ui-ok-ink);
                  font-size:13px; font-weight:500; }

    /* Botones */
    .dash-btn { display:inline-flex; align-items:center; gap:6px; height:34px; padding:0 12px;
                border:1px solid var(--ui-accent); border-radius:var(--ui-r); background:var(--ui-accent);
                color:#fff; font:inherit; font-size:13px; font-weight:600; line-height:1;
                cursor:pointer; text-decoration:none; white-space:nowrap;
                transition:background var(--ui-fast) var(--ui-ease), border-color var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease); }
    .dash-btn:hover { background:var(--ui-accent-hover); border-color:var(--ui-accent-hover); }
    .dash-btn:focus-visible { outline:2px solid var(--ui-accent); outline-offset:2px; }
    .dash-btn:disabled { opacity:.45; cursor:not-allowed; }
    .dash-btn--ghost { background:var(--ui-surface); border-color:var(--ui-border-strong); color:var(--ui-ink-2); }
    .dash-btn--ghost:hover { background:var(--ui-surface-3); border-color:var(--ui-border-strong); color:var(--ui-ink); }
    .dash-btn .msi { font-size:16px; }
    .dash-btn svg { flex:0 0 auto; }

    .dash-vacio { padding:56px 20px; text-align:center; background:var(--ui-surface);
                  border:1px dashed var(--ui-border-strong); border-radius:var(--ui-r-lg); }
    .dash-vacio .ico { display:inline-flex; width:40px; height:40px; align-items:center; justify-content:center;
                       border-radius:var(--ui-r); background:var(--ui-surface-3); color:var(--ui-muted); }
    .dash-vacio .ico svg { width:20px; height:20px; }
    .dash-vacio h3 { margin:14px 0 4px; font-size:15px; font-weight:600; color:var(--ui-ink); }
    .dash-vacio p { margin:0 0 16px; color:var(--ui-muted); font-size:13.5px; }

    /* ===================== Rejilla =====================
       Cuatro columnas en escritorio; se reacomoda hasta una sola. */
    .dash-grid { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr));
                 grid-auto-rows:var(--dash-row, 66px); gap:14px; align-items:stretch; }
    .dash-w { position:relative; min-width:0;
              grid-column:span var(--w, 1); grid-row:span var(--h, 2); }

    .dash-w.is-volando { position:fixed; z-index:var(--ui-z-pop); margin:0; pointer-events:none;
                         opacity:.97; box-shadow:var(--ui-shadow-pop); border-radius:var(--ui-r-lg); }

    .dash-hueco { grid-column:span var(--w, 1); grid-row:span var(--h, 2);
                  border:1.5px dashed var(--ui-accent); border-radius:var(--ui-r-lg);
                  background:var(--ui-accent-soft); }
    .dash-hueco[hidden] { display:none; }

    @media (max-width:1200px) {
        .dash-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); }
        .dash-w[data-w="3"], .dash-w[data-w="4"] { grid-column:span 2; }
    }
    @media (max-width:640px) {
        .dash-grid { grid-template-columns:1fr; gap:12px; grid-auto-rows:auto; }
        /* Se repite el selector con atributo porque la regla de 1200px gana por especificidad. */
        .dash-w,
        .dash-w[data-w="2"], .dash-w[data-w="3"], .dash-w[data-w="4"] { grid-column:span 1; }
        .dash-w { grid-row:auto; }
        .dash-w > .dw { height:auto; min-height:0; overflow:visible; }
        .dash-grid.is-editando .dash-handle,
        .dash-grid.is-editando .dash-medida { display:none; }
        .dash-top { align-items:flex-start; }
        .dash-actions { width:100%; }
        .dash-actions .dash-btn { flex:1; justify-content:center; }
    }

    /* ===================== Tarjeta ===================== */
    .dw { display:flex; flex-direction:column; height:100%; padding:16px; overflow:auto;
          background:var(--ui-surface); border:1px solid var(--ui-border); border-radius:var(--ui-r-lg);
          box-shadow:var(--ui-shadow-xs); scrollbar-width:none; -ms-overflow-style:none;
          transition:border-color var(--ui-fast) var(--ui-ease); }
    .dw::-webkit-scrollbar { width:0; height:0; }
    .dw-head { display:flex; align-items:center; gap:8px; margin-bottom:12px; }
    .dw-head h3 { margin:0; font-size:13px; font-weight:600; color:var(--ui-ink-2); }
    .dw-head .dw-ico { display:flex; align-items:center; justify-content:center; width:26px; height:26px;
                       border-radius:var(--ui-r-sm); flex:0 0 auto; background:var(--ui-surface-3); color:var(--ui-ink-2); }
    .dw-head .dw-ico svg { width:15px; height:15px; }
    .dw-head .dw-ico .msi { font-size:16px; }
    /* El color del icono es lo único que distingue el tipo; el fondo se queda neutro. */
    .dw-ico.verde { color:var(--ui-ok-ink); }
    .dw-ico.ambar { color:var(--ui-warn-ink); }
    .dw-ico.rojo { color:var(--ui-danger-ink); }
    .dw-head .dw-link { margin-left:auto; color:var(--ui-muted); font-size:12.5px; font-weight:500;
                        text-decoration:none; white-space:nowrap; transition:color var(--ui-fast) var(--ui-ease); }
    .dw-head .dw-link:hover { color:var(--ui-accent-ink); }

    .dw-num { font-size:28px; font-weight:700; line-height:1.1; letter-spacing:-.02em; color:var(--ui-ink);
              font-variant-numeric:tabular-nums; }
    .dw-sub { margin-top:4px; color:var(--ui-muted); font-size:13px; }
    .dw-pie { margin-top:auto; padding-top:12px; color:var(--ui-muted); font-size:12.5px; }

    .dw-filas { display:flex; flex-direction:column; }
    .dw-fila { display:flex; align-items:center; gap:12px; padding:9px 0; color:var(--ui-ink); text-decoration:none; }
    .dw-fila + .dw-fila { border-top:1px solid var(--ui-border); }
    .dw-fila-txt { flex:1; min-width:0; display:flex; flex-direction:column; gap:2px; }
    .dw-fila-t { font-size:13.5px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .dw-fila-s { color:var(--ui-muted); font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .dw-fila-v { font-size:13px; font-weight:600; white-space:nowrap; font-variant-numeric:tabular-nums; }
    .dw-fila-v.es-alerta { color:var(--ui-danger-ink); }
    a.dw-fila:hover .dw-fila-t { color:var(--ui-accent-ink); }

    .dw-badge { display:inline-flex; align-items:center; padding:1px 7px; border-radius:999px;
                background:var(--ui-surface-3); color:var(--ui-ink-2); font-size:11.5px; font-weight:600; white-space:nowrap; }
    .dw-badge.verde { background:var(--ui-ok-soft); color:var(--ui-ok-ink); }
    .dw-badge.ambar { background:var(--ui-warn-soft); color:var(--ui-warn-ink); }
    .dw-badge.rojo { background:var(--ui-danger-soft); color:var(--ui-danger-ink); }
    .dw-badge.azul { background:var(--ui-accent-soft); color:var(--ui-accent-ink); }

    .dw-vacio { margin:0; padding:20px 0; text-align:center; color:var(--ui-muted); font-size:13px; }

    /* Desglose que aparece al crecer la tarjeta: cifras separadas por una
       línea, no cajas dentro de la tarjeta. */
    .dw-mini { display:grid; grid-template-columns:repeat(auto-fit, minmax(84px, 1fr));
               gap:12px 16px; margin-top:14px; padding-top:12px; border-top:1px solid var(--ui-border); }
    .dw-mini > div { min-width:0; }
    .dw-mini b { display:block; font-size:16px; font-weight:700; letter-spacing:-.01em; color:var(--ui-ink);
                 font-variant-numeric:tabular-nums; }
    .dw-mini span { display:block; margin-top:1px; color:var(--ui-muted); font-size:12px; }
    .dw-mini b.es-sube { color:var(--ui-ok-ink); }
    .dw-mini b.es-baja { color:var(--ui-danger-ink); }

    .dw-sep { margin:16px 0 6px; padding-top:12px; border-top:1px solid var(--ui-border);
              color:var(--ui-ink-2); font-size:12.5px; font-weight:600; }

    .dw-tabla { width:100%; border-collapse:collapse; font-size:13px; }
    .dw-tabla td { padding:7px 0; border-bottom:1px solid var(--ui-border); }
    .dw-tabla tr:last-child td { border-bottom:0; }
    .dw-tabla-e { color:var(--ui-ink); max-width:0; width:100%;
                  overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .dw-tabla-x { padding-left:10px; color:var(--ui-muted); font-size:12px; white-space:nowrap; }
    .dw-tabla-v { padding-left:10px; text-align:right; font-weight:600; white-space:nowrap; font-variant-numeric:tabular-nums; }
    .dw-tabla-v.es-alerta { color:var(--ui-danger-ink); }

    .dw-barras { display:flex; align-items:flex-end; gap:10px; height:150px; padding-top:8px; }
    .dw-barra { flex:1; min-width:0; display:flex; flex-direction:column; justify-content:flex-end;
                align-items:center; gap:6px; height:100%; }
    .dw-barra .b { width:100%; max-width:44px; border-radius:4px 4px 0 0; background:var(--ui-accent);
                   min-height:3px; transition:height 300ms var(--ui-ease); }
    .dw-barra .e { color:var(--ui-muted); font-size:11.5px; }
    .dw-barra .v { color:var(--ui-ink-2); font-size:11px; font-weight:600; font-variant-numeric:tabular-nums; }

    /* Accesos directos: filas de enlace, sin caja propia (van dentro de una tarjeta). */
    .dw-accesos { display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:2px 8px; }
    .dw-acceso { position:relative; display:flex; align-items:center; gap:10px; padding:7px 8px; margin:0 -8px;
                 border-radius:var(--ui-r); color:var(--ui-ink); text-decoration:none; font-size:13px; font-weight:500;
                 transition:background var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease); }
    .dw-acceso:hover { background:var(--ui-surface-3); }
    .dw-acceso:focus-visible { outline:2px solid var(--ui-accent); outline-offset:-2px; }
    .dw-acceso .dw-acceso-ico { display:flex; align-items:center; justify-content:center; width:26px; height:26px;
                                flex:0 0 auto; border-radius:var(--ui-r-sm); background:var(--ui-surface-3); color:var(--ui-ink-2);
                                transition:background var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease); }
    .dw-acceso .dw-acceso-ico .msi { font-size:16px; }
    .dw-acceso:hover .dw-acceso-ico { background:var(--ui-accent-soft); color:var(--ui-accent-ink); }
    .dw-acceso-txt { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .dw-acceso-badge { margin-left:auto; padding:1px 6px; border-radius:999px; background:var(--ui-accent-soft);
                       color:var(--ui-accent-ink); font-size:10.5px; font-weight:600; position:static; }

    .msi { font-family:'Material Symbols Outlined'; font-weight:400; font-style:normal; font-size:24px;
           line-height:1; letter-spacing:normal; text-transform:none; display:inline-block;
           white-space:nowrap; word-wrap:normal; direction:ltr;
           -webkit-font-feature-settings:'liga'; -webkit-font-smoothing:antialiased; }

    /* ===================== Modo edición ===================== */
    .dash-ayuda { margin:-6px 0 14px; color:var(--ui-muted); font-size:13px; }
    .dash-ayuda[hidden] { display:none; }

    .dash-grid.is-editando { touch-action:none; user-select:none; }
    .dash-grid.is-editando .dash-w { cursor:grab; }
    .dash-grid.is-editando .dash-w:active { cursor:grabbing; }
    .dash-grid.is-editando .dash-w > .dw { border-style:dashed; border-color:var(--ui-border-strong); box-shadow:none; }
    .dash-grid.is-editando .dash-w:hover > .dw { border-color:var(--ui-accent); }
    .dash-grid.is-editando .dw-fila,
    .dash-grid.is-editando .dw-acceso,
    .dash-grid.is-editando .dw-link { pointer-events:none; }
    .dash-grid.is-editando .dw { overflow:hidden; }
    .dash-w.is-volando .dw-barra .b { transition:none; }

    .dash-quitar { display:none; position:absolute; top:-8px; right:-8px; z-index:6;
                   align-items:center; justify-content:center; width:22px; height:22px;
                   padding:0; border:1px solid var(--ui-border-strong); border-radius:50%;
                   background:var(--ui-surface); color:var(--ui-muted); cursor:pointer; box-shadow:var(--ui-shadow-xs);
                   transition:background var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease), border-color var(--ui-fast) var(--ui-ease); }
    .dash-quitar svg { width:11px; height:11px; }
    .dash-quitar:hover { background:var(--ui-danger); border-color:var(--ui-danger); color:#fff; }
    .dash-grid.is-editando .dash-quitar { display:flex; }

    .dash-medida { display:none; position:absolute; top:8px; right:12px; z-index:6;
                   padding:1px 6px; border-radius:var(--ui-r-sm); background:var(--ui-ink);
                   color:#fff; font-size:11px; font-weight:600; font-variant-numeric:tabular-nums; }
    .dash-grid.is-editando .dash-medida { display:block; }

    .dash-handle { display:none; position:absolute; right:-4px; bottom:-4px; z-index:6;
                   align-items:center; justify-content:center; width:22px; height:22px;
                   border-radius:var(--ui-r-sm); background:var(--ui-surface); border:1px solid var(--ui-border-strong);
                   color:var(--ui-muted); cursor:nwse-resize; touch-action:none; box-shadow:var(--ui-shadow-xs); }
    .dash-handle svg { width:11px; height:11px; }
    .dash-grid.is-editando .dash-handle { display:flex; }
    .dash-handle:hover { border-color:var(--ui-accent); color:var(--ui-accent-ink); }

    .dash-guardar { position:sticky; bottom:12px; z-index:var(--ui-z-sticky); display:flex; align-items:center; gap:8px;
                    margin-top:18px; padding:10px 12px; border:1px solid var(--ui-border-strong); border-radius:var(--ui-r-lg);
                    background:var(--ui-surface); box-shadow:var(--ui-shadow-pop); }
    .dash-guardar[hidden] { display:none; }
    .dash-guardar-txt { flex:1; min-width:0; color:var(--ui-ink-2); font-size:13px; font-weight:500; }

    /* ===================== Modal de agregar ===================== */
    .dash-modal { padding:0; border:0; background:transparent; max-width:none; max-height:none; }
    .dash-modal::backdrop { background:oklch(0.23 0.02 262 / .45); }

    .dash-modal-box { display:flex; flex-direction:column; width:min(520px, calc(100vw - 28px));
                      max-height:min(86vh, 700px); background:var(--ui-surface);
                      border:1px solid var(--ui-border); border-radius:var(--ui-r-lg);
                      box-shadow:var(--ui-shadow-pop); overflow:hidden; font-family:inherit; color:var(--ui-ink); }

    .dash-modal-head { display:flex; align-items:flex-start; gap:12px; padding:16px 18px;
                       border-bottom:1px solid var(--ui-border); }
    .dash-modal-head h3 { margin:0; font-size:15px; font-weight:600; }
    .dash-modal-head p { margin:3px 0 0; color:var(--ui-muted); font-size:13px; }
    .dash-modal-x { margin-left:auto; display:inline-flex; align-items:center; justify-content:center;
                    width:28px; height:28px; padding:0; border:0; border-radius:var(--ui-r-sm); background:none;
                    color:var(--ui-muted); cursor:pointer; flex:0 0 auto; }
    .dash-modal-x svg { width:16px; height:16px; }
    .dash-modal-x:hover { background:var(--ui-surface-3); color:var(--ui-ink); }

    .dash-modal-body { flex:1; min-height:0; overflow-y:auto; padding:8px 10px 12px; }

    .dash-add-sec { margin:12px 8px 4px; color:var(--ui-muted); font-size:12px; font-weight:600; }
    .dash-add-sec:first-child { margin-top:4px; }

    .dash-add { display:flex; align-items:center; gap:12px; width:100%; padding:9px 8px;
                border:0; border-radius:var(--ui-r); background:none;
                color:var(--ui-ink); font-family:inherit; text-align:left; cursor:pointer;
                transition:background var(--ui-fast) var(--ui-ease); }
    .dash-add:hover { background:var(--ui-surface-3); }
    .dash-add:focus-visible { outline:2px solid var(--ui-accent); outline-offset:-2px; }
    .dash-add-txt { flex:1; min-width:0; }
    .dash-add-name { display:block; font-size:13.5px; font-weight:600; }
    .dash-add-desc { display:block; margin-top:1px; color:var(--ui-muted); font-size:12.5px; line-height:1.4; }
    .dash-add svg { width:15px; height:15px; flex:0 0 auto; color:var(--ui-faint); }
    .dash-add:hover svg { color:var(--ui-accent-ink); }

    .dash-modal-foot { display:flex; align-items:center; gap:8px; padding:12px 18px;
                       border-top:1px solid var(--ui-border); background:var(--ui-surface-2); }
    .dash-foot-sep { flex:1; }

    @media (max-width:600px) {
        .dash-modal-foot { flex-wrap:wrap; }
        .dash-modal-foot .dash-btn { flex:1; justify-content:center; }
        .dash-foot-sep { display:none; }
        .dash-guardar { flex-wrap:wrap; }
        .dash-guardar-txt { flex:1 1 100%; }
        .dash-guardar .dash-btn { flex:1; justify-content:center; }
    }
    @media (prefers-reduced-motion:reduce) {
        .dw, .dw-barra .b, .dw-acceso, .dw-acceso-ico, .dash-btn, .dash-add, .dash-quitar { transition:none; }
    }
</style>
