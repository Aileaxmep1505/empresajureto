@extends('layouts.app')
@section('content_class', 'content--flush')
@section('content')
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #f9fafb;
    --card: #ffffff;
    --input-bg: #f9fafb;

    --ink-dark: #111111;
    --ink: #333333;
    --muted: #888888;
    --muted-light: #b8b8b8;

    --line: #ebebeb;

    --blue: #007aff;
    --blue-soft: #e6f0ff;

    --success: #15803d;
    --success-soft: #e6ffe6;

    --danger: #ff4a4a;
    --danger-soft: #ffebeb;

    --warning: #c2410c;
    --warning-soft: #fff7ed;
  }

  /* ===== BASE & TYPOGRAPHY ===== */
  .fac-page {
    font-family: 'Quicksand', sans-serif;
    background: var(--bg);
    color: var(--ink);
    min-height: 100vh;
    padding: 28px 20px;
    font-weight: 500;
    -webkit-font-smoothing: antialiased;
  }
  .fac-page * { box-sizing: border-box; }
  .fac-wrap { max-width: 1180px; margin: 0 auto; }

  .fac-page h1 {
    color: var(--ink-dark);
    font-size: 22px;
    margin: 0 0 6px;
    font-weight: 700;
    letter-spacing: -0.4px;
  }
  .fac-sub {
    color: var(--muted);
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 24px;
  }

  .back-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: var(--muted);
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 18px;
    text-decoration: none;
    transition: color 0.2s ease;
  }
  .back-link:hover { color: var(--blue); }

  /* ===== ALERTS & STATUS ===== */
  .test-banner {
    background: var(--warning-soft);
    color: var(--warning);
    border: 1px solid rgba(194, 65, 12, 0.18);
    border-radius: 12px;
    padding: 14px 18px;
    margin-bottom: 18px;
    font-size: 13px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .resolve-status {
    font-size: 13px;
    font-weight: 600;
    color: var(--blue);
    margin-bottom: 18px;
    min-height: 18px;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  /* ===== TOOLBAR & BUTTONS ===== */
  .fac-toolbar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 24px;
    align-items: center;
  }

  .btn {
    font-family: inherit;
    font-weight: 700;
    height: 42px;
    padding: 0 20px;
    border-radius: 8px;
    border: 1px solid transparent;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 13.5px;
    text-decoration: none;
    transition: transform 0.18s ease, background 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, color 0.2s ease;
  }
  .btn:hover { transform: translateY(-1px); }
  .btn:active { transform: scale(0.98); }
  .btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

  .btn-primary { background: var(--blue); color: var(--card); }
  .btn-primary:hover { background: #0066d6; box-shadow: 0 4px 12px rgba(0, 122, 255, 0.22); }

  .btn-outline { background: var(--card); color: var(--blue); border-color: var(--blue); }
  .btn-outline:hover { background: var(--blue-soft); box-shadow: 0 4px 12px rgba(0,122,255,0.08); }

  .btn-ghost { background: transparent; color: var(--muted); border: 1px solid var(--line); }
  .btn-ghost:hover { background: var(--input-bg); color: var(--ink-dark); }

  /* Icon Buttons */
  .btn-icon {
    flex: 0 0 auto;
    width: 36px;
    height: 36px;
    padding: 0;
    border-radius: 8px;
    background: var(--input-bg);
    border: 1px solid var(--line);
    color: var(--muted);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.18s ease, background 0.2s ease, color 0.2s ease, border-color 0.2s ease;
  }
  .btn-icon:hover { background: var(--card); color: var(--blue); border-color: var(--blue); transform: translateY(-1px); }
  .btn-icon:active { transform: scale(0.96); }

  /* ===== TABLE CONTAINER ===== */
  .fac-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .fac-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.04);
  }
  .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin-bottom: 12px;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
  }
  th {
    text-align: left;
    font-size: 11px;
    color: var(--muted);
    font-weight: 700;
    padding: 12px 10px;
    border-bottom: 1px solid var(--line);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
  }
  td {
    font-size: 13.5px;
    color: var(--ink);
    padding: 12px 10px;
    border-bottom: 1px solid var(--line);
    vertical-align: top;
  }
  tbody tr { transition: background 0.15s ease; }
  tbody tr:hover td { background: #fcfcfd; }
  tr:last-child td { border-bottom: none; }

  .tr { text-align: right; }
  .tc { text-align: center; }

  /* ===== INPUTS IN TABLE ===== */
  .inp {
    font-family: inherit;
    font-weight: 600;
    font-size: 13px;
    height: 36px;
    padding: 0 12px;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 8px;
    color: var(--ink-dark);
    width: 100%;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
  }
  .inp::placeholder { color: var(--muted-light); font-weight: 500; }
  .inp:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft); }

  .w-qty { width: 78px; }
  .w-price { width: 98px; }
  .w-clave { width: 104px; min-width: 90px; }

  .clave-cell {
    display: flex;
    gap: 6px;
    align-items: center;
  }

  /* Nombre/descripción oficial debajo de la clave */
  .clave-name {
    font-size: 11px;
    color: var(--muted);
    font-weight: 600;
    margin-top: 5px;
    line-height: 1.35;
    max-width: 200px;
    word-break: break-word;
  }
  .clave-name:empty { display: none; }

  /* Custom Checkbox */
  input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: var(--blue);
    cursor: pointer;
  }

  /* ===== TOTALS ===== */
  .fac-totals {
    display: flex;
    justify-content: flex-end;
    gap: 32px;
    font-size: 14px;
    font-weight: 600;
    color: var(--muted);
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--line);
    flex-wrap: wrap;
  }
  .fac-totals span { display: flex; align-items: center; gap: 8px; }
  .fac-totals strong { color: var(--ink-dark); font-size: 17px; font-weight: 700; }

  /* ===== MODALS ===== */
  .modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
    background: rgba(17,17,17,0.35);
    backdrop-filter: blur(3px);
  }
  .modal-backdrop.show { display: flex; }

  .modal {
    width: min(720px, 100%);
    max-height: calc(100vh - 32px);
    background: var(--card);
    border-radius: 16px;
    border: 1px solid var(--line);
    box-shadow: 0 24px 50px -12px rgba(17,17,17,0.18);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: modalEnter 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    opacity: 0;
  }
  @keyframes modalEnter {
    0% { opacity: 0; transform: scale(0.97) translateY(10px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
  }

  .modal-head {
    padding: 18px 24px;
    border-bottom: 1px solid var(--line);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--card);
  }
  .modal-head h2 { margin: 0; font-size: 17px; color: var(--ink-dark); font-weight: 700; letter-spacing: -0.3px; }
  .modal-close {
    border: 0;
    background: transparent;
    cursor: pointer;
    color: var(--muted);
    padding: 6px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s ease, color 0.2s ease;
  }
  .modal-close:hover { background: var(--input-bg); color: var(--ink-dark); }

  .modal-body {
    padding: 24px;
    overflow-y: auto !important;
  }
  .modal-body::-webkit-scrollbar { width: 6px; }
  .modal-body::-webkit-scrollbar-thumb { background: var(--line); border-radius: 6px; }

  .modal-foot {
    padding: 18px 24px;
    border-top: 1px solid var(--line);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    background: var(--input-bg);
    flex-wrap: wrap;
  }

  /* Modal Content Specifics */
  .res-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
  }
  .res-box {
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 18px;
    text-align: center;
    background: var(--card);
  }
  .res-box .v { font-size: 20px; font-weight: 700; color: var(--ink-dark); letter-spacing: -0.4px; }
  .res-box .l { font-size: 11px; color: var(--muted); font-weight: 700; text-transform: uppercase; margin-top: 6px; letter-spacing: 0.04em; }

  pre.json {
    background: var(--input-bg);
    color: var(--ink-dark);
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 16px;
    font-size: 12px;
    line-height: 1.6;
    overflow: auto;
    max-height: 300px;
    white-space: pre;
    font-family: 'SF Mono', ui-monospace, monospace;
  }

  /* Búsqueda Clave Results */
  .cl-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border: 1px solid var(--line);
    border-radius: 12px;
    margin-bottom: 10px;
    background: var(--card);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
  }
  .cl-row:hover { border-color: var(--blue); box-shadow: 0 4px 12px rgba(0,122,255,0.06); }
  .cl-row strong { color: var(--ink-dark); font-size: 13.5px; display: block; }
  .cl-desc { font-size: 12.5px; color: var(--muted); margin-top: 4px; line-height: 1.5; }
  .cl-empty { color: var(--muted-light); font-size: 14px; font-weight: 600; text-align: center; padding: 28px; border: 1px dashed var(--line); border-radius: 12px; }

  /* ===== TOOLTIP ===== */
  .ui-tooltip {
    position: fixed;
    z-index: 10000;
    background: var(--ink-dark);
    color: #fff;
    font-family: 'Quicksand', sans-serif;
    font-size: 11.5px;
    font-weight: 600;
    letter-spacing: 0.1px;
    padding: 6px 10px;
    border-radius: 8px;
    white-space: nowrap;
    pointer-events: none;
    opacity: 0;
    transform: translateY(4px);
    transition: opacity 0.16s ease, transform 0.16s ease;
    box-shadow: 0 6px 18px rgba(17,17,17,0.2);
  }
  .ui-tooltip.show { opacity: 1; transform: translateY(0); }
  .ui-tooltip::after {
    content: '';
    position: absolute;
    left: var(--arrow, 50%);
    transform: translateX(-50%);
    border: 5px solid transparent;
  }
  .ui-tooltip.top::after { top: 100%; border-top-color: var(--ink-dark); }
  .ui-tooltip.bottom::after { bottom: 100%; border-bottom-color: var(--ink-dark); }

  /* Loaders */
  .loader {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(0,0,0,0.15);
    border-radius: 50%;
    border-top-color: currentColor;
    animation: spin 0.8s linear infinite;
  }
  .btn-primary .loader { border-color: rgba(255,255,255,0.35); border-top-color: #fff; }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* ===== RESPONSIVE ===== */
  @media (max-width: 640px) {
    .fac-page { padding: 20px 14px; }
    .fac-toolbar { flex-direction: column; align-items: stretch; }
    .fac-toolbar .btn { width: 100%; }
    .fac-card { padding: 18px; }
    .fac-totals { flex-direction: column; align-items: flex-end; gap: 8px; }
    .modal-head, .modal-body, .modal-foot { padding-left: 18px; padding-right: 18px; }
    .res-grid { grid-template-columns: 1fr 1fr; }
    .cl-row { flex-direction: column; align-items: stretch; text-align: left; }
    .cl-row .btn { width: 100%; }
  }
</style>

<div class="fac-page">
  <div class="fac-wrap">
    <a href="{{ route('propuestas-comerciales.resultado.show', $resultado) }}" class="back-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
      Volver al resultado
    </a>

    <h1>Facturación · {{ $folio }}</h1>
    <p class="fac-sub">Cliente: <strong style="color:var(--ink-dark);">{{ $cliente }}</strong> &nbsp;·&nbsp; IVA {{ number_format($ivaPct,0) }}% &nbsp;·&nbsp; Solo partidas ganadas</p>

    <div class="test-banner">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      MODO PRUEBA: No se envía a Facturapi. Al facturar verás el JSON generado.
    </div>
    <div class="resolve-status" id="resolveStatus"></div>

    <div class="fac-toolbar">
      <button type="button" class="btn btn-primary" onclick="facturar('completo')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/></svg>
        Facturar completo (todas)
      </button>
      <button type="button" class="btn btn-outline" onclick="facturar('partes')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M7 12h10"/><path d="M10 18h4"/></svg>
        Facturar seleccionadas
      </button>
      <button type="button" class="btn btn-ghost" id="btnRebuscarTodas" onclick="rebuscarTodas()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
        Rebuscar todas (IA)
      </button>
    </div>

    <div class="fac-card">
      @if(count($ganadas))
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th class="tc"><input type="checkbox" id="chkAll" onchange="toggleAll(this)" checked></th>
                <th>#</th>
                <th>Descripción</th>
                <th class="tc">Unidad</th>
                <th class="tr">Cantidad</th>
                <th class="tr">P. Unit.</th>
                <th class="tr">Importe</th>
                <th>Clave ProdServ</th>
                <th>Clave Unidad</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @foreach($ganadas as $g)
                <tr class="fac-row" data-desc="{{ $g['desc'] }}" data-unidad="{{ $g['unidad'] }}">
                  <td class="tc"><input type="checkbox" class="f-check" checked></td>
                  <td style="font-weight:700; color:var(--ink-dark);">{{ $g['num'] }}</td>
                  <td style="min-width:240px;">{{ $g['desc'] }}</td>
                  <td class="tc">{{ $g['unidad'] }}</td>
                  <td class="tr"><input type="number" step="0.01" min="0" class="inp w-qty f-qty tr" value="{{ $g['cantidad'] }}" oninput="recalc()"></td>
                  <td class="tr"><input type="number" step="0.01" min="0" class="inp w-price f-price tr" value="{{ $g['precio'] }}" oninput="recalc()"></td>
                  <td class="tr"><strong class="f-importe" style="color:var(--ink-dark);">${{ number_format($g['importe'],2) }}</strong></td>
                  <td>
                    <div class="clave-cell">
                      <input type="text" class="inp w-clave f-prodserv" value="{{ $g['clave_prodserv'] }}" placeholder="Buscando..." onchange="lookupClaveName(this,'prodserv')">
                      <button type="button" class="btn-icon" aria-label="Buscar ProdServ" data-tip="Buscar clave ProdServ (SAT)" onclick="openClaveModal(this,'prodserv')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                      </button>
                    </div>
                    <div class="clave-name f-prodserv-name">{{ $g['nombre_prodserv'] ?? '' }}</div>
                  </td>
                  <td>
                    <div class="clave-cell">
                      <input type="text" class="inp w-clave f-claveunidad" value="{{ $g['clave_unidad'] }}" onchange="lookupClaveName(this,'unidad')">
                      <button type="button" class="btn-icon" aria-label="Buscar Unidad" data-tip="Buscar clave de Unidad (SAT)" onclick="openClaveModal(this,'unidad')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                      </button>
                    </div>
                    <div class="clave-name f-claveunidad-name">{{ $g['nombre_unidad'] ?? '' }}</div>
                  </td>
                  <td>
                    <button type="button" class="btn-icon" aria-label="Volver a buscar" data-tip="Volver a buscar claves con IA" onclick="rebuscar(this)">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
                    </button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="fac-totals">
          <span>Subtotal: <strong id="tSub">$0.00</strong></span>
          <span>IVA: <strong id="tIva">$0.00</strong></span>
          <span>Total: <strong id="tTot">$0.00</strong></span>
        </div>
      @else
        <p style="color:var(--muted-light); padding:28px; text-align:center; font-weight:600;">No hay partidas ganadas para facturar.</p>
      @endif
    </div>
  </div>

  {{-- Modal resultado prueba --}}
  <div class="modal-backdrop" id="facModal">
    <div class="modal">
      <div class="modal-head">
        <h2 id="facModalTitle">Resultado (prueba)</h2>
        <button type="button" class="modal-close" aria-label="Cerrar" data-tip="Cerrar" onclick="closeFacModal()">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
      <div class="modal-body" id="facModalBody"></div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" data-tip="Cerrar sin facturar" onclick="closeFacModal()">Cerrar</button>
        <button type="button" class="btn btn-primary" data-tip="Descargar el JSON generado" onclick="downloadJson()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
          Descargar JSON
        </button>
      </div>
    </div>
  </div>

  {{-- Modal búsqueda manual de clave --}}
  <div class="modal-backdrop" id="claveModal">
    <div class="modal">
      <div class="modal-head">
        <h2 id="claveModalTitle">Buscar clave</h2>
        <button type="button" class="modal-close" aria-label="Cerrar" data-tip="Cerrar" onclick="closeClaveModal()">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
      <div class="modal-body">
        <input type="text" class="inp" id="claveSearchInput" placeholder="Escribe para buscar..." style="height:44px; margin-bottom:16px;" oninput="scheduleClaveSearch()">
        <div class="resolve-status" id="claveSearchStatus" style="margin-bottom:12px;"></div>
        <div id="claveResults"></div>
      </div>
    </div>
  </div>
</div>

<script>
  const csrfToken = @json(csrf_token());
  const pruebaUrl = @json(route('propuestas-comerciales.resultado.facturar.prueba', $resultado));
  const rebuscarUrl = @json(route('propuestas-comerciales.resultado.facturar.rebuscar', $resultado));
  const resolverUrl = @json(route('propuestas-comerciales.resultado.facturar.resolver', $resultado));
  const buscarUrl = @json(route('propuestas-comerciales.resultado.facturar.buscar', $resultado));
  const ivaPct = @json($ivaPct);
  const folio = @json($folio);
  let lastPayload = null;

  // Estado del modal de búsqueda manual
  let claveTargetInput = null;
  let claveTipo = 'prodserv';
  let claveSearchTimer = null;
  let claveResults = [];

  const money = n => '$' + Number(n||0).toLocaleString('es-MX',{minimumFractionDigits:2, maximumFractionDigits:2});
  function escapeHtml(v){ return String(v ?? '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'","&#039;"); }

  function toggleAll(el) {
    document.querySelectorAll('.f-check').forEach(c => c.checked = el.checked);
  }

  function recalc() {
    let sub = 0;
    document.querySelectorAll('.fac-row').forEach(row => {
      const qty = Number(row.querySelector('.f-qty').value || 0);
      const price = Number(row.querySelector('.f-price').value || 0);
      const imp = qty * price;
      row.querySelector('.f-importe').textContent = money(imp);
      sub += imp;
    });
    const iva = sub * (Number(ivaPct) / 100);
    document.getElementById('tSub').textContent = money(sub);
    document.getElementById('tIva').textContent = money(iva);
    document.getElementById('tTot').textContent = money(sub + iva);
  }

  // Pone los nombres oficiales (debajo de cada clave) desde una respuesta de la IA.
  function setRowNames(row, it) {
    const pn = row.querySelector('.f-prodserv-name');
    const un = row.querySelector('.f-claveunidad-name');
    if (pn && it.nombre_prodserv !== undefined) pn.textContent = it.nombre_prodserv || '';
    if (un && it.nombre_unidad !== undefined) un.textContent = it.nombre_unidad || '';
  }

  function collectRows(tipo) {
    const rows = [...document.querySelectorAll('.fac-row')].filter(row => {
      return tipo === 'completo' ? true : row.querySelector('.f-check').checked;
    });
    return rows.map(row => ({
      descripcion: row.dataset.desc,
      unidad: row.dataset.unidad,
      cantidad: row.querySelector('.f-qty').value,
      precio: row.querySelector('.f-price').value,
      clave_prodserv: row.querySelector('.f-prodserv').value || '01010101',
      clave_unidad: row.querySelector('.f-claveunidad').value || 'H87',
    }));
  }

  async function facturar(tipo) {
    const items = collectRows(tipo);
    if (!items.length) { alert('Selecciona al menos una partida.'); return; }

    try {
      const resp = await fetch(pruebaUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ tipo, items })
      });
      const data = await resp.json();
      if (!resp.ok || !data.ok) throw new Error(data.message || 'Error en la prueba.');

      lastPayload = data.facturapi_payload;
      const r = data.resumen;

      document.getElementById('facModalTitle').textContent =
        tipo === 'completo' ? 'Factura completa (prueba)' : 'Factura por partes (prueba)';

      document.getElementById('facModalBody').innerHTML = `
        <div class="res-grid">
          <div class="res-box"><div class="v">${r.partidas}</div><div class="l">Partidas</div></div>
          <div class="res-box"><div class="v">${money(r.subtotal)}</div><div class="l">Subtotal</div></div>
          <div class="res-box"><div class="v">${money(r.iva)}</div><div class="l">IVA ${Number(r.iva_pct).toFixed(0)}%</div></div>
          <div class="res-box"><div class="v">${money(r.total)}</div><div class="l">Total</div></div>
        </div>
        <p style="font-size:13px; color:var(--muted); margin:0 0 10px; font-weight:600;">Esto se enviaría a Facturapi (modo prueba):</p>
        <pre class="json">${escapeHtml(JSON.stringify(data.facturapi_payload, null, 2))}</pre>
      `;
      document.getElementById('facModal').classList.add('show');
    } catch (e) {
      alert(e.message);
    }
  }

  async function rebuscar(btn) {
    const row = btn.closest('.fac-row');
    const old = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<span class="loader"></span>';
    try {
      const resp = await fetch(rebuscarUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ descripcion: row.dataset.desc, unidad: row.dataset.unidad })
      });
      const data = await resp.json();
      if (!resp.ok || !data.ok) throw new Error(data.message || 'Error al re-buscar.');
      row.querySelector('.f-prodserv').value = data.clave_prodserv;
      row.querySelector('.f-claveunidad').value = data.clave_unidad;
      setRowNames(row, data);
    } catch (e) {
      alert(e.message);
    } finally {
      btn.disabled = false; btn.innerHTML = old;
    }
  }

  // Rebusca TODAS las filas con IA (forzando, sin caché).
  async function rebuscarTodas() {
    const rows = [...document.querySelectorAll('.fac-row')];
    if (!rows.length) return;

    const btn = document.getElementById('btnRebuscarTodas');
    const status = document.getElementById('resolveStatus');
    const old = btn.innerHTML;
    btn.disabled = true;

    const total = rows.length;
    let done = 0;
    const size = 8;

    for (let i = 0; i < rows.length; i += size) {
      const chunk = rows.slice(i, i + size);
      const items = chunk.map(r => ({ desc: r.dataset.desc, unidad: r.dataset.unidad }));
      if (status) status.innerHTML = `<span class="loader"></span> Rebuscando claves SAT con IA... ${done}/${total}`;

      try {
        const resp = await fetch(resolverUrl, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
          body: JSON.stringify({ items, force: true })
        });
        const data = await resp.json();
        if (data.ok && Array.isArray(data.items)) {
          chunk.forEach((r, k) => {
            const it = data.items[k] || {};
            if (it.clave_prodserv) r.querySelector('.f-prodserv').value = it.clave_prodserv;
            if (it.clave_unidad)  r.querySelector('.f-claveunidad').value = it.clave_unidad;
            setRowNames(r, it);
          });
        }
      } catch (e) { /* siguiente lote */ }

      done += chunk.length;
    }

    if (status) {
      status.innerHTML = `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--success);"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Rebúsqueda completa (${total}). Revisa las claves y sus nombres.
      `;
    }
    btn.disabled = false; btn.innerHTML = old;
  }

  /* ===== Búsqueda manual de clave (modal) ===== */
  function openClaveModal(btn, tipo) {
    const row = btn.closest('.fac-row');
    claveTipo = tipo;
    claveTargetInput = tipo === 'prodserv' ? row.querySelector('.f-prodserv') : row.querySelector('.f-claveunidad');

    document.getElementById('claveModalTitle').textContent =
      tipo === 'prodserv' ? 'Buscar Clave ProdServ (SAT)' : 'Buscar Clave de Unidad (SAT)';

    const input = document.getElementById('claveSearchInput');
    // Prefill: prodserv con la descripción; unidad con la unidad de la fila.
    input.value = tipo === 'prodserv' ? (row.dataset.desc || '') : (row.dataset.unidad || '');

    document.getElementById('claveResults').innerHTML = '';
    document.getElementById('claveSearchStatus').textContent = '';
    claveResults = [];
    document.getElementById('claveModal').classList.add('show');

    scheduleClaveSearch(50);
    setTimeout(() => input.focus(), 100);
  }

  function closeClaveModal() {
    document.getElementById('claveModal').classList.remove('show');
    claveTargetInput = null;
  }

  function scheduleClaveSearch(delay = 350) {
    clearTimeout(claveSearchTimer);
    claveSearchTimer = setTimeout(runClaveSearch, delay);
  }

  async function runClaveSearch() {
    const q = document.getElementById('claveSearchInput').value.trim();
    const status = document.getElementById('claveSearchStatus');
    const box = document.getElementById('claveResults');

    if (!q) { box.innerHTML = ''; status.textContent = 'Escribe para buscar.'; claveResults = []; return; }

    status.innerHTML = '<span class="loader"></span> Buscando...';
    try {
      const resp = await fetch(buscarUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ tipo: claveTipo, q })
      });
      const data = await resp.json();
      if (!resp.ok || !data.ok) throw new Error(data.message || 'Error en la búsqueda.');

      claveResults = data.results || [];
      status.textContent = `${claveResults.length} resultado(s)`;

      if (!claveResults.length) {
        box.innerHTML = '<p class="cl-empty">Sin resultados. Prueba con otra palabra.</p>';
        return;
      }

      box.innerHTML = claveResults.map((r, idx) => `
        <div class="cl-row">
          <div style="min-width:0;">
            <strong>${escapeHtml(r.clave)}</strong>
            <div class="cl-desc">${escapeHtml(r.texto)}</div>
          </div>
          <button type="button" class="btn btn-outline" data-tip="Usar esta clave" onclick="usarClaveByIndex(${idx})">Usar</button>
        </div>
      `).join('');
    } catch (e) {
      status.textContent = e.message;
    }
  }

  function usarClaveByIndex(idx) {
    const r = claveResults[idx];
    if (r && claveTargetInput) {
      claveTargetInput.value = r.clave;
      const nameEl = claveTargetInput.closest('td')?.querySelector('.clave-name');
      if (nameEl) nameEl.textContent = r.texto || '';
    }
    closeClaveModal();
  }

  // Al escribir/pegar una clave a mano, busca su nombre oficial y lo muestra debajo.
  async function lookupClaveName(input, tipo) {
    const clave = input.value.trim();
    const nameEl = input.closest('td')?.querySelector('.clave-name');
    if (!nameEl) return;
    if (!clave) { nameEl.textContent = ''; return; }

    try {
      const resp = await fetch(buscarUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ tipo, q: clave })
      });
      const data = await resp.json();
      if (data.ok && Array.isArray(data.results)) {
        const hit = data.results.find(r => String(r.clave) === clave);
        nameEl.textContent = hit ? hit.texto : '';
      }
    } catch (e) { /* sin nombre */ }
  }

  // Cerrar modales al hacer clic fuera
  document.getElementById('facModal').addEventListener('click', e => { if (e.target.id === 'facModal') closeFacModal(); });
  document.getElementById('claveModal').addEventListener('click', e => { if (e.target.id === 'claveModal') closeClaveModal(); });

  /* ===== Auto-resolver ClaveProdServ + Unidad por lotes (IA) ===== */
  async function autoResolverClaves() {
    const rows = [...document.querySelectorAll('.fac-row')]
      .filter(r => !r.querySelector('.f-prodserv').value.trim());

    const status = document.getElementById('resolveStatus');
    if (!rows.length) { if (status) status.textContent = ''; return; }

    const total = rows.length;
    let done = 0;
    const size = 8;

    for (let i = 0; i < rows.length; i += size) {
      const chunk = rows.slice(i, i + size);
      const items = chunk.map(r => ({ desc: r.dataset.desc, unidad: r.dataset.unidad }));
      if (status) {
        status.innerHTML = `<span class="loader"></span> Buscando claves SAT con IA... ${done}/${total}`;
      }

      try {
        const resp = await fetch(resolverUrl, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
          body: JSON.stringify({ items })
        });
        const data = await resp.json();
        if (data.ok && Array.isArray(data.items)) {
          chunk.forEach((r, k) => {
            const it = data.items[k] || {};
            if (it.clave_prodserv) r.querySelector('.f-prodserv').value = it.clave_prodserv;
            if (it.clave_unidad)  r.querySelector('.f-claveunidad').value = it.clave_unidad;
            setRowNames(r, it);
          });
        }
      } catch (e) { /* siguiente lote */ }

      done += chunk.length;
    }

    if (status) {
      status.innerHTML = `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--success);"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Claves SAT listas (${total}). Revísalas; usa la búsqueda manual o "Rebuscar todas" si alguna no es correcta.
      `;
    }
  }

  function closeFacModal() {
    document.getElementById('facModal').classList.remove('show');
  }

  function downloadJson() {
    if (!lastPayload) return;
    const blob = new Blob([JSON.stringify(lastPayload, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = 'factura_prueba_' + folio + '.json';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  }

  /* ===== Tooltips flotantes (no se cortan con overflow) ===== */
  (function(){
    let tipEl = null, tipTarget = null;

    function position(){
      if (!tipEl || !tipTarget) return;
      const r = tipTarget.getBoundingClientRect();
      const tw = tipEl.offsetWidth, th = tipEl.offsetHeight;
      let placeBottom = false;
      let top = r.top - th - 10;
      if (top < 8) { top = r.bottom + 10; placeBottom = true; }
      let left = r.left + r.width / 2 - tw / 2;
      left = Math.max(8, Math.min(left, window.innerWidth - tw - 8));
      tipEl.style.top = top + 'px';
      tipEl.style.left = left + 'px';
      tipEl.classList.toggle('bottom', placeBottom);
      tipEl.classList.toggle('top', !placeBottom);
      tipEl.style.setProperty('--arrow', ((r.left + r.width / 2) - left) + 'px');
    }

    function show(el){
      const text = el.getAttribute('data-tip');
      if (!text) return;
      hide();
      tipEl = document.createElement('div');
      tipEl.className = 'ui-tooltip';
      tipEl.textContent = text;
      document.body.appendChild(tipEl);
      tipTarget = el;
      position();
      requestAnimationFrame(() => { if (tipEl) tipEl.classList.add('show'); });
    }

    function hide(){
      if (tipEl) { tipEl.remove(); tipEl = null; tipTarget = null; }
    }

    document.addEventListener('mouseover', e => {
      const el = e.target.closest('[data-tip]');
      if (el && el !== tipTarget) show(el);
    });
    document.addEventListener('mouseout', e => {
      const el = e.target.closest('[data-tip]');
      if (el && !el.contains(e.relatedTarget)) hide();
    });
    document.addEventListener('click', hide, true);
    window.addEventListener('scroll', hide, true);
    window.addEventListener('resize', hide);
  })();

  recalc();
  document.addEventListener('DOMContentLoaded', autoResolverClaves);
</script>
@endsection