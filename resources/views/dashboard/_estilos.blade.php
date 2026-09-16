<style>
    /* ===================== Encabezado del tablero ===================== */
    .dash-top { display:flex; align-items:flex-end; justify-content:space-between; gap:16px;
                flex-wrap:wrap; margin:4px 0 18px; }
    .dash-hero { min-width:0; }
    .dash-fecha { margin:0 0 6px; font-size:11.5px; font-weight:700; letter-spacing:.14em;
                  text-transform:uppercase; color:var(--primary); }
    .dash-saludo { margin:0; font-size:clamp(1.5rem, 2.2vw, 2rem); font-weight:700;
                   letter-spacing:-.02em; line-height:1.1; color:var(--text); }
    .dash-nombre { background-image:linear-gradient(90deg, #6d28d9, #9333ea, #2563eb, #06b6d4);
                   -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; }
    .dash-frase { margin:8px 0 0; max-width:720px; color:var(--muted); font-size:14px; line-height:1.5; }
    .dash-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

    .dash-aviso { margin:0 0 14px; padding:10px 14px; border:1px solid var(--success);
                  border-radius:12px; background:var(--success-soft); color:#065f46; font-size:13px; }

    /* Botones propios del tablero (el layout no trae un .btn genérico) */
    .dash-btn { display:inline-flex; align-items:center; gap:7px; padding:9px 14px;
                border:1px solid var(--primary); border-radius:12px; background:var(--primary);
                color:#fff; font:inherit; font-size:13.5px; font-weight:600; line-height:1;
                cursor:pointer; text-decoration:none; white-space:nowrap;
                transition:filter .15s ease, background .15s ease, border-color .15s ease; }
    .dash-btn:hover { filter:brightness(1.05); }
    .dash-btn:disabled { opacity:.5; cursor:not-allowed; }
    .dash-btn--ghost { background:var(--surface); border-color:var(--border); color:var(--text); }
    .dash-btn--ghost:hover { background:var(--surface-soft); border-color:var(--primary); filter:none; }
    .dash-btn .msi { font-size:17px; }

    .dash-vacio { padding:48px 20px; text-align:center; background:var(--surface);
                  border:1px dashed var(--border-strong); border-radius:16px; }
    .dash-vacio .ico { display:inline-flex; width:52px; height:52px; align-items:center; justify-content:center;
                       border-radius:14px; background:var(--primary-soft); color:var(--primary); }
    .dash-vacio .ico svg { width:24px; height:24px; }
    .dash-vacio h3 { margin:14px 0 4px; font-size:17px; }
    .dash-vacio p { margin:0 0 16px; color:var(--muted); font-size:13.5px; }

    /* ===================== Rejilla del tablero =====================
       Cuatro columnas en escritorio. Al angostarse se reacomoda hasta
       quedar en una sola columna. */
    .dash-grid { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr));
                 grid-auto-rows:var(--dash-row, 66px); gap:16px; align-items:stretch; }
    .dash-w { position:relative; min-width:0;
              grid-column:span var(--w, 1); grid-row:span var(--h, 2); }

    /* La tarjeta que se está arrastrando se despega y sigue al dedo. */
    .dash-w.is-volando { position:fixed; z-index:60; margin:0; pointer-events:none;
                         opacity:.95; transform:rotate(.6deg);
                         box-shadow:0 22px 50px rgba(17,24,39,.24); border-radius:16px; }

    /* Hueco que marca dónde va a caer */
    .dash-hueco { grid-column:span var(--w, 1); grid-row:span var(--h, 2);
                  border:2px dashed var(--primary); border-radius:16px;
                  background:var(--primary-soft); opacity:.6; }
    .dash-hueco[hidden] { display:none; }

    @media (max-width:1200px) {
        .dash-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); }
        /* Con dos columnas, 3 y 4 de ancho se recortan a 2. */
        .dash-w[data-w="3"], .dash-w[data-w="4"] { grid-column:span 2; }
    }
    @media (max-width:640px) {
        .dash-grid { grid-template-columns:1fr; gap:14px; }

        /* Una sola columna, sin excepciones. Hay que repetir el selector con
           atributo porque la regla de 1200px lo usa y gana por especificidad. */
        .dash-w,
        .dash-w[data-w="2"], .dash-w[data-w="3"], .dash-w[data-w="4"] { grid-column:span 1; }

        /* En el teléfono la altura la manda el contenido, no lo configurado
           en escritorio: con alto fijo el texto se cortaba a la mitad. */
        .dash-grid { grid-auto-rows:auto; }
        .dash-w { grid-row:auto; }
        .dash-w > .dw { height:auto; min-height:0; overflow:visible; }

        /* Ancho y alto no significan nada en una sola columna: al editar solo
           se reordena y se quitan tarjetas. */
        .dash-grid.is-editando .dash-handle,
        .dash-grid.is-editando .dash-medida { display:none; }

        .dash-top { align-items:flex-start; }
        .dash-actions { width:100%; }
        .dash-actions .dash-btn { flex:1; justify-content:center; }
    }

    /* ===================== Tarjeta ===================== */
    /* Con alto fijo, lo que no quepa se desplaza dentro de la tarjeta en vez
       de desbordarse sobre la de abajo. La barra se oculta a la vista. */
    .dw { display:flex; flex-direction:column; height:100%; padding:18px; overflow:auto;
          background:var(--surface); border:1px solid var(--border); border-radius:16px;
          box-shadow:var(--shadow-sm); scrollbar-width:none; -ms-overflow-style:none; }
    .dw::-webkit-scrollbar { width:0; height:0; }
    .dw-head { display:flex; align-items:center; gap:10px; margin-bottom:14px; }
    .dw-head h3 { margin:0; font-size:14.5px; font-weight:700; letter-spacing:-.01em; color:var(--text); }
    .dw-head .dw-ico { display:flex; align-items:center; justify-content:center; width:34px; height:34px;
                       border-radius:10px; flex:0 0 auto; background:var(--primary-soft); color:var(--primary); }
    .dw-head .dw-ico svg { width:17px; height:17px; }
    .dw-head .dw-ico .msi { font-size:19px; }
    .dw-ico.verde { background:var(--success-soft); color:var(--success); }
    .dw-ico.ambar { background:var(--warning-soft); color:var(--warning); }
    .dw-ico.rojo { background:var(--danger-soft); color:var(--danger); }
    .dw-head .dw-link { margin-left:auto; color:var(--primary); font-size:12.5px; font-weight:600;
                        text-decoration:none; white-space:nowrap; }
    .dw-head .dw-link:hover { text-decoration:underline; }

    .dw-num { font-size:30px; font-weight:700; line-height:1.05; letter-spacing:-.02em; color:var(--text); }
    .dw-sub { margin-top:6px; color:var(--muted); font-size:13px; }
    .dw-pie { margin-top:auto; padding-top:12px; color:var(--muted); font-size:12.5px; }

    .dw-filas { display:flex; flex-direction:column; }
    .dw-fila { display:flex; align-items:center; gap:12px; padding:10px 0; color:var(--text); text-decoration:none; }
    .dw-fila + .dw-fila { border-top:1px solid var(--border); }
    .dw-fila-txt { flex:1; min-width:0; display:flex; flex-direction:column; gap:1px; }
    .dw-fila-t { font-size:13.5px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .dw-fila-s { color:var(--muted); font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .dw-fila-v { font-size:13.5px; font-weight:700; white-space:nowrap; }
    .dw-fila-v.es-alerta { color:var(--danger); }
    a.dw-fila:hover .dw-fila-t { color:var(--primary); }

    .dw-badge { display:inline-flex; align-items:center; padding:2px 9px; border-radius:999px;
                background:var(--surface-soft); border:1px solid var(--border);
                color:var(--muted); font-size:11.5px; font-weight:600; white-space:nowrap; }
    .dw-badge.verde { background:var(--success-soft); border-color:transparent; color:#047857; }
    .dw-badge.ambar { background:var(--warning-soft); border-color:transparent; color:#b45309; }
    .dw-badge.rojo { background:var(--danger-soft); border-color:transparent; color:var(--danger-2); }
    .dw-badge.azul { background:var(--primary-soft); border-color:transparent; color:var(--primary-2); }

    .dw-vacio { margin:0; padding:20px 0; text-align:center; color:var(--muted); font-size:13px; }

    /* Bloques que solo aparecen cuando la tarjeta crece */
    .dw-mini { display:grid; grid-template-columns:repeat(auto-fit, minmax(90px, 1fr));
               gap:10px; margin-top:14px; }
    .dw-mini > div { padding:11px; border:1px solid var(--border); border-radius:10px; background:var(--surface-soft); }
    .dw-mini b { display:block; font-size:19px; font-weight:700; letter-spacing:-.01em; }
    .dw-mini span { color:var(--muted); font-size:12px; }
    .dw-mini b.es-sube { color:var(--success); }
    .dw-mini b.es-baja { color:var(--danger); }

    .dw-sep { margin:16px 0 8px; padding-top:12px; border-top:1px solid var(--border);
              color:var(--muted); font-size:11.5px; font-weight:700;
              letter-spacing:.04em; text-transform:uppercase; }

    .dw-tabla { width:100%; border-collapse:collapse; font-size:13px; }
    .dw-tabla td { padding:7px 0; border-bottom:1px solid var(--border); }
    .dw-tabla tr:last-child td { border-bottom:0; }
    .dw-tabla-e { color:var(--text); max-width:0; width:100%;
                  overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .dw-tabla-x { padding-left:10px; color:var(--muted); font-size:12px; white-space:nowrap; }
    .dw-tabla-v { padding-left:10px; text-align:right; font-weight:700; white-space:nowrap; }
    .dw-tabla-v.es-alerta { color:var(--danger); }

    .dw-barras { display:flex; align-items:flex-end; gap:10px; height:150px; padding-top:8px; }
    .dw-barra { flex:1; min-width:0; display:flex; flex-direction:column; justify-content:flex-end;
                align-items:center; gap:8px; height:100%; }
    .dw-barra .b { width:100%; border-radius:7px 7px 3px 3px; background:var(--primary);
                   min-height:3px; transition:height .3s ease; }
    .dw-barra .e { color:var(--muted); font-size:11.5px; }
    .dw-barra .v { color:var(--text); font-size:11px; font-weight:700; }

    /* Accesos directos: botones con ícono de Material Symbols */
    .dw-accesos { display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:10px; }
    .dw-acceso { position:relative; display:flex; align-items:center; gap:10px; padding:11px 12px;
                 border:1px solid var(--border); border-radius:12px; background:var(--surface);
                 color:var(--text); text-decoration:none; font-size:13px; font-weight:600;
                 transition:border-color .15s ease, background .15s ease, transform .15s ease; }
    .dw-acceso:hover { background:var(--primary-soft); border-color:var(--primary); transform:translateY(-1px); }
    .dw-acceso .dw-acceso-ico { display:flex; align-items:center; justify-content:center; width:32px; height:32px;
                                flex:0 0 auto; border-radius:9px; background:var(--surface-soft); color:var(--primary); }
    .dw-acceso .dw-acceso-ico .msi { font-size:19px; }
    .dw-acceso:hover .dw-acceso-ico { background:#fff; }
    .dw-acceso-txt { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .dw-acceso-badge { position:absolute; top:-7px; right:8px; padding:1px 7px; border-radius:999px;
                       background:linear-gradient(90deg, #3b82f6, #6366f1); color:#fff;
                       font-size:9.5px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }

    .msi { font-family:'Material Symbols Outlined'; font-weight:400; font-style:normal; font-size:24px;
           line-height:1; letter-spacing:normal; text-transform:none; display:inline-block;
           white-space:nowrap; word-wrap:normal; direction:ltr;
           -webkit-font-feature-settings:'liga'; -webkit-font-smoothing:antialiased; }

    /* ===================== Modo edición ===================== */
    .dash-ayuda { margin:-4px 0 14px; color:var(--muted); font-size:13px; }
    .dash-ayuda[hidden] { display:none; }

    /* Al editar, la tarjeta se marca, se arrastra y no navega. */
    .dash-grid.is-editando { touch-action:none; user-select:none; }
    .dash-grid.is-editando .dash-w { cursor:grab; }
    .dash-grid.is-editando .dash-w:active { cursor:grabbing; }
    .dash-grid.is-editando .dash-w > .dw { outline:1px dashed var(--border-strong); outline-offset:3px; }
    .dash-grid.is-editando .dash-w:hover > .dw { outline-color:var(--primary); }
    .dash-grid.is-editando .dw-fila,
    .dash-grid.is-editando .dw-acceso,
    .dash-grid.is-editando .dw-link { pointer-events:none; }
    .dash-grid.is-editando .dw { overflow:hidden; }
    .dash-w.is-volando .dw-barra .b { transition:none; }

    /* ✕ para quitar */
    .dash-quitar { display:none; position:absolute; top:-8px; right:-8px; z-index:6;
                   align-items:center; justify-content:center; width:24px; height:24px;
                   padding:0; border:1px solid var(--border); border-radius:50%;
                   background:var(--surface); color:var(--muted); cursor:pointer;
                   box-shadow:0 4px 12px rgba(17,24,39,.14); }
    .dash-quitar svg { width:12px; height:12px; }
    .dash-quitar:hover { background:var(--danger); border-color:var(--danger); color:#fff; }
    .dash-grid.is-editando .dash-quitar { display:flex; }

    /* Medida en celdas mientras se ajusta */
    .dash-medida { display:none; position:absolute; top:8px; left:8px; z-index:6;
                   padding:2px 7px; border-radius:6px; background:var(--primary);
                   color:#fff; font-size:11px; font-weight:700; letter-spacing:.02em; }
    .dash-grid.is-editando .dash-medida { display:block; }

    /* Asa de la esquina: cambia ancho y alto a la vez */
    .dash-handle { display:none; position:absolute; right:-3px; bottom:-3px; z-index:6;
                   align-items:center; justify-content:center; width:24px; height:24px;
                   border-radius:8px; background:var(--surface); border:1px solid var(--border);
                   color:var(--muted); cursor:nwse-resize; touch-action:none;
                   box-shadow:0 4px 12px rgba(17,24,39,.14); }
    .dash-handle svg { width:12px; height:12px; }
    .dash-grid.is-editando .dash-handle { display:flex; }
    .dash-handle:hover { border-color:var(--primary); color:var(--primary); }

    /* Barra de guardado */
    .dash-guardar { position:sticky; bottom:12px; z-index:30; display:flex; align-items:center; gap:10px;
                    margin-top:18px; padding:12px 14px; border:1px solid var(--border); border-radius:14px;
                    background:rgba(255,255,255,.92);
                    backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px);
                    box-shadow:0 -4px 24px rgba(17,24,39,.08); }
    .dash-guardar[hidden] { display:none; }
    .dash-guardar-txt { flex:1; min-width:0; color:var(--muted); font-size:13px; }

    /* ===================== Modal de agregar ===================== */
    .dash-modal { padding:0; border:0; background:transparent; max-width:none; max-height:none; }
    .dash-modal::backdrop { background:rgba(2,6,23,.5); backdrop-filter:blur(2px); -webkit-backdrop-filter:blur(2px); }

    .dash-modal-box { display:flex; flex-direction:column; width:min(560px, calc(100vw - 28px));
                      max-height:min(86vh, 720px); background:var(--surface);
                      border:1px solid var(--border); border-radius:16px;
                      box-shadow:0 24px 60px rgba(17,24,39,.22); overflow:hidden;
                      font-family:inherit; color:var(--text); }

    .dash-modal-head { display:flex; align-items:flex-start; gap:12px; padding:18px 20px;
                       border-bottom:1px solid var(--border); }
    .dash-modal-head h3 { margin:0; font-size:17px; font-weight:700; letter-spacing:-.01em; }
    .dash-modal-head p { margin:3px 0 0; color:var(--muted); font-size:13px; }
    .dash-modal-x { margin-left:auto; display:inline-flex; align-items:center; justify-content:center;
                    width:32px; height:32px; padding:0; border:0; border-radius:8px; background:none;
                    color:var(--muted); cursor:pointer; flex:0 0 auto; }
    .dash-modal-x svg { width:17px; height:17px; }
    .dash-modal-x:hover { background:var(--surface-soft); color:var(--text); }

    .dash-modal-body { flex:1; min-height:0; overflow-y:auto; padding:10px 12px; }

    .dash-add-sec { margin:12px 12px 4px; color:var(--muted); font-size:11px; font-weight:700;
                    letter-spacing:.08em; text-transform:uppercase; }
    .dash-add-sec:first-child { margin-top:4px; }

    .dash-add { display:flex; align-items:center; gap:12px; width:100%; padding:11px 12px;
                border:1px solid transparent; border-radius:10px; background:none;
                color:var(--text); font-family:inherit; text-align:left; cursor:pointer; }
    .dash-add + .dash-add { margin-top:2px; }
    .dash-add:hover { background:var(--surface-soft); border-color:var(--border); }
    .dash-add-txt { flex:1; min-width:0; }
    .dash-add-name { display:block; font-size:14px; font-weight:600; }
    .dash-add-desc { display:block; margin-top:1px; color:var(--muted); font-size:12.5px; line-height:1.4; }
    .dash-add svg { width:16px; height:16px; flex:0 0 auto; color:var(--muted); }
    .dash-add:hover svg { color:var(--primary); }

    .dash-modal-foot { display:flex; align-items:center; gap:10px; padding:14px 20px;
                       border-top:1px solid var(--border); background:var(--surface-soft); }
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
        .dw-barra .b, .dw-acceso, .dash-btn { transition:none; }
    }
</style>
