@extends('layouts.app')
@section('content_class', 'content--flush')
@section('content')
@php
  // Marca y modelo ofertado por partida (item_id => brand/model)
  $propuestaComercial->loadMissing(['items.matches.product', 'items.productoSeleccionado']);
  $ofertaInfo = $propuestaComercial->items->mapWithKeys(function ($item) {
      $selectedMatch = $item->matches->firstWhere('seleccionado', true);
      $selectedProduct = $item->productoSeleccionado ?: optional($selectedMatch)->product;

      $brand = data_get($item->meta, 'external_supplier')
          ?: data_get($item->meta, 'brand')
          ?: data_get($item->meta, 'marca')
          ?: data_get($selectedProduct, 'brand')
          ?: data_get($selectedProduct, 'marca')
          ?: '';

      $model = data_get($item->meta, 'modelo')
          ?: data_get($item->meta, 'model')
          ?: data_get($selectedProduct, 'model')
          ?: data_get($selectedProduct, 'modelo')
          ?: data_get($selectedProduct, 'model_name')
          ?: '';

      return [$item->id => ['brand' => $brand, 'model' => $model]];
  });
@endphp
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #f9fafb;          /* Fondo general de la página */
    --card: #ffffff;        /* Fondo de contenedores */
    --ink: #333333;         /* Texto principal */
    --muted: #888888;       /* Texto secundario / gris */
    --line: #ebebeb;        /* Bordes y separadores */
    --blue: #007aff;        /* Color primario / botones */
    --blue-soft: #e6f0ff;   /* Hover primario / badges */
    --success: #15803d;     /* Verde éxito */
    --success-soft: #e6ffe6;
    --danger: #ff4a4a;      /* Rojo error / ofertas */
    --danger-soft: #ffebeb;
    --heading: #111111;     /* Títulos oscuros */
  }

  /* ===== BASE & TYPOGRAPHY ===== */
  .adj-page {
    font-family: 'Quicksand', sans-serif;
    background: var(--bg);
    color: var(--ink);
    min-height: 100vh;
    padding: 56px 24px;
    font-weight: 500;
  }
  .adj-page * {
    box-sizing: border-box;
  }
  .adj-wrap {
    max-width: 1080px;
    margin: 0 auto;
  }
  .adj-page h1 {
    color: var(--heading);
    font-size: 28px;
    margin: 0 0 8px;
    font-weight: 700;
    letter-spacing: -0.5px;
  }
  .adj-sub {
    color: var(--muted);
    font-size: 15px;
    margin: 0 0 36px;
  }
  .back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--muted);
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 28px;
    text-decoration: none;
    transition: color 0.2s ease;
  }
  .back-link:hover {
    color: var(--heading);
  }

  /* ===== LAYOUT & STATS ===== */
  .adj-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 36px;
  }
  .adj-bar {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 36px;
  }
  .adj-stat {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 28px;
    min-width: 160px;
    flex: 1;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .adj-stat:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.04);
  }
  .adj-stat .v {
    font-size: 28px;
    font-weight: 700;
    color: var(--heading);
  }
  .adj-stat .l {
    font-size: 13px;
    color: var(--muted);
    font-weight: 600;
    margin-top: 6px;
  }

  /* ===== SEARCH (expandible) ===== */
  .search-wrap {
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }
  .search-pill {
    display: inline-flex;
    align-items: center;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 999px;
    height: 42px;
    padding: 0 4px 0 0;
    transition: border-color 0.25s ease, box-shadow 0.25s ease;
  }
  .search-pill.open {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }
  .search-toggle {
    border: 0;
    background: transparent;
    width: 42px;
    height: 40px;
    border-radius: 999px;
    cursor: pointer;
    color: var(--muted);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s ease;
    flex-shrink: 0;
  }
  .search-pill.open .search-toggle,
  .search-toggle:hover { color: var(--blue); }
  .search-input {
    font-family: 'Quicksand', sans-serif;
    font-weight: 600;
    font-size: 14px;
    border: 0;
    background: transparent;
    color: var(--ink);
    width: 0;
    padding: 0;
    opacity: 0;
    transition: width 0.35s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
  }
  .search-input::placeholder { color: var(--muted); }
  .search-input:focus { outline: none; }
  .search-pill.open .search-input {
    width: 220px;
    padding-right: 8px;
    opacity: 1;
  }
  .search-clear {
    border: 0;
    background: transparent;
    cursor: pointer;
    color: var(--muted);
    width: 0;
    height: 32px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    overflow: hidden;
    transition: all 0.2s ease;
    flex-shrink: 0;
  }
  .search-pill.open .search-clear.show {
    width: 32px;
    opacity: 1;
  }
  .search-clear:hover { color: var(--danger); background: var(--bg); }
  .search-count {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--blue);
    background: var(--blue-soft);
    padding: 4px 12px;
    border-radius: 999px;
    white-space: nowrap;
    animation: fadeIn 0.25s ease;
  }
  .adj-card.search-hide { display: none; }
  .search-empty {
    display: none;
    color: var(--muted);
    font-size: 15px;
    font-weight: 600;
    padding: 32px;
    text-align: center;
    border: 1px dashed var(--line);
    border-radius: 16px;
    background: var(--card);
    animation: fadeIn 0.25s ease;
  }

  /* ===== CARDS ===== */
  .adj-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 28px;
    margin-bottom: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  }
  .adj-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.04);
  }
  .adj-card.is-perdida {
    border-color: rgba(255, 74, 74, 0.3);
  }
  .adj-card.is-ganada {
    border-color: rgba(21, 128, 61, 0.3);
  }

  .adj-head {
    display: grid;
    grid-template-columns: 40px 1fr auto;
    gap: 16px;
    align-items: center;
  }
  .adj-num {
    font-weight: 700;
    color: var(--muted);
    text-align: center;
    font-size: 16px;
  }
  .adj-name {
    font-size: 16px;
    font-weight: 700;
    color: var(--heading);
    margin-bottom: 4px;
  }
  .adj-meta {
    font-size: 13.5px;
    color: var(--muted);
  }
  .adj-meta strong {
    color: var(--ink);
  }
  .adj-oferta {
    font-size: 13px;
    color: var(--muted);
    margin-top: 4px;
  }
  .adj-oferta strong { color: var(--ink); }
  .adj-oferta .tag {
    display: inline-block;
    background: var(--blue-soft);
    color: var(--blue);
    font-weight: 700;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 999px;
    margin-right: 6px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
  }

  /* ===== BADGES & STATUS ===== */
  .save-status {
    font-weight: 700;
    font-size: 12.5px;
    padding: 4px 10px;
    border-radius: 999px;
    margin-left: 8px;
    display: inline-block;
  }
  .save-status.saving { background: var(--blue-soft); color: var(--blue); }
  .save-status.saved { background: var(--success-soft); color: var(--success); }
  .save-status.error { background: var(--danger-soft); color: var(--danger); }

  .diff-pill {
    display: inline-block;
    font-size: 13px;
    font-weight: 700;
    padding: 6px 12px;
    border-radius: 999px;
    margin-top: 8px;
  }
  .diff-up { background: var(--danger-soft); color: var(--danger); }
  .diff-down { background: var(--success-soft); color: var(--success); }

  /* ===== SEGMENTED CONTROL ===== */
  .seg {
    display: inline-flex;
    background: var(--bg);
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 4px;
  }
  .seg button {
    border: 0;
    background: transparent;
    font-family: 'Quicksand', sans-serif;
    font-weight: 700;
    font-size: 13.5px;
    color: var(--muted);
    padding: 8px 18px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  .seg button:hover:not(.on-win):not(.on-lose) {
    background: var(--line);
    color: var(--heading);
  }
  .seg button.on-win { background: var(--success); color: var(--card); }
  .seg button.on-lose { background: var(--danger); color: var(--card); }

  /* ===== FORMS & INPUTS ===== */
  .lose-box {
    display: none;
    margin-top: 28px;
    padding-top: 28px;
    border-top: 1px solid var(--line);
  }
  .adj-card.is-perdida .lose-box {
    display: block;
    animation: fadeIn 0.3s ease;
  }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

  .grid2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }
  .field {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 16px;
  }
  .field label {
    font-size: 13px;
    font-weight: 700;
    color: var(--heading);
  }
  .input {
    font-family: 'Quicksand', sans-serif;
    font-weight: 500;
    font-size: 14.5px;
    height: 42px;
    padding: 0 14px;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 8px;
    color: var(--ink);
    width: 100%;
    transition: all 0.2s ease;
  }
  .input::placeholder {
    color: var(--muted);
  }
  textarea.input {
    height: auto;
    padding: 12px 14px;
    resize: vertical;
  }
  .input:focus {
    outline: none;
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  /* ===== BUTTONS ===== */
  .btn {
    font-family: 'Quicksand', sans-serif;
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
    font-size: 14.5px;
    text-decoration: none;
    transition: all 0.2s ease;
  }
  .btn:hover { transform: translateY(-1px); }
  .btn:active { transform: scale(0.98); }

  .btn-primary { background: var(--blue); color: #ffffff; }
  .btn-primary:hover { box-shadow: 0 4px 12px rgba(0, 122, 255, 0.2); }

  .btn-outline { background: var(--card); color: var(--blue); border-color: var(--blue); }
  .btn-outline:hover { background: var(--blue-soft); }

  .btn-dark { background: var(--heading); color: #ffffff; }
  .btn-dark:hover { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); }

  .btn-success { background: var(--success); color: #ffffff; }
  .btn-success:hover { box-shadow: 0 4px 12px rgba(21, 128, 61, 0.2); }

  .btn-ghost { background: transparent; color: #555555; }
  .btn-ghost:hover { background: var(--bg); color: var(--heading); }

  .btn-small { height: 36px; padding: 0 16px; font-size: 13.5px; }

  /* ===== FOOTER ===== */
  .adj-footer {
    position: sticky;
    bottom: 24px;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 20px 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
    margin-top: 48px;
    box-shadow: 0 12px 32px rgba(0,0,0,0.04);
    flex-wrap: wrap;
    z-index: 10;
  }

  .loader {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(0,122,255,0.2);
    border-radius: 50%;
    border-top-color: var(--blue);
    animation: spin 0.8s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* ===== PREMIUM MODALS ===== */
  .modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(17,17,17,0.35);
    backdrop-filter: blur(4px);
  }
  .modal-backdrop.show { display: flex; }

  .modal {
    width: min(800px, 100%);
    max-height: calc(100vh - 48px);
    background: var(--card);
    border-radius: 16px;
    border: 1px solid var(--line);
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.12);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: modalEnter 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    opacity: 0;
  }
  @keyframes modalEnter {
    0% { opacity: 0; transform: scale(0.95) translateY(15px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
  }

  .modal-head {
    padding: 32px 36px 24px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    border-bottom: 1px solid var(--line);
  }
  .modal-head h2 { margin: 0; font-size: 22px; color: var(--heading); font-weight: 700; letter-spacing: -0.3px; }
  .modal-head p { margin: 8px 0 0; font-size: 14.5px; color: var(--muted); }
  .modal-close {
    border: 0;
    background: transparent;
    cursor: pointer;
    color: var(--muted);
    font-size: 24px;
    line-height: 1;
    padding: 6px;
    border-radius: 8px;
    transition: all 0.2s ease;
  }
  .modal-close:hover { background: var(--bg); color: var(--heading); }

  .modal-body {
    padding: 28px 36px;
    overflow-y: auto !important;
  }

  /* Custom Scrollbar */
  .modal-body::-webkit-scrollbar { width: 6px; }
  .modal-body::-webkit-scrollbar-track { background: transparent; }
  .modal-body::-webkit-scrollbar-thumb { background: var(--line); border-radius: 10px; }
  .modal-body::-webkit-scrollbar-thumb:hover { background: var(--muted); }

  .modal-foot {
    padding: 24px 36px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    flex-wrap: wrap;
    background: var(--card);
    border-top: 1px solid var(--line);
  }

  /* Modal content specifics (from JS rendering) */
  .rep-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 16px;
    margin-bottom: 36px;
  }
  .rep-stat {
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    background: var(--card);
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
  }
  .rep-stat .v { font-size: 24px; font-weight: 700; color: var(--heading); }
  .rep-stat .l { font-size: 12px; font-weight: 700; color: var(--muted); margin-top: 6px; text-transform: uppercase; letter-spacing: 0.05em; }

  .rep-section { margin-bottom: 36px; }
  .rep-section h3 { font-size: 17px; color: var(--heading); margin: 0 0 16px; font-weight: 700; }
  .rep-text { font-size: 15px; line-height: 1.7; color: var(--ink); white-space: pre-wrap; }

  .rep-lose {
    border: 1px solid var(--line);
    background: var(--card);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
  }
  .rep-lose .t { font-size: 15px; font-weight: 700; color: var(--heading); }
  .rep-lose .m { font-size: 13.5px; color: var(--muted); margin-top: 6px; }
  .rep-lose .m strong { color: var(--ink); }
  .rep-lose .a { font-size: 14px; color: var(--ink); margin-top: 12px; line-height: 1.6; white-space: pre-wrap; padding-top: 12px; border-top: 1px solid var(--line); }

  .rep-recos { list-style: none; margin: 0; padding: 0; }
  .rep-recos li {
    position: relative;
    padding: 16px 16px 16px 44px;
    border: 1px solid var(--line);
    border-radius: 12px;
    margin-bottom: 12px;
    font-size: 14.5px;
    line-height: 1.6;
    color: var(--ink);
    background: var(--card);
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
  }
  .rep-recos li::before {
    content: "•";
    position: absolute;
    left: 20px;
    top: 16px;
    color: var(--success);
    font-weight: 700;
    font-size: 20px;
    line-height: 1.3;
  }
  .rep-empty { color: var(--muted); font-size: 15px; font-weight: 600; padding: 28px; text-align: center; border: 1px dashed var(--line); border-radius: 12px; }

  /* ===== RESPONSIVE ===== */
  @media (max-width: 768px) {
    .adj-page { padding: 28px 16px; }
    .grid2 { grid-template-columns: 1fr; }
    .adj-head { grid-template-columns: 1fr; gap: 8px; }
    .adj-num { text-align: left; }
    .adj-card, .adj-stat { padding: 20px; }
    .adj-footer { flex-direction: column; align-items: stretch; text-align: center; padding: 16px; }
    .adj-footer > div { justify-content: center; }
    .search-wrap { width: 100%; flex-wrap: wrap; }
    .search-pill.open .search-input { width: 150px; }
    .modal-head, .modal-body, .modal-foot { padding-left: 20px; padding-right: 20px; }
  }
</style>

<div class="adj-page">
  <div class="adj-wrap">
    <a href="{{ route('propuestas-comerciales.cliente.show', $propuestaComercial) }}" class="back-link">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
      Volver a la cotización
    </a>

    <div class="adj-top">
      <div>
        <h1>Generar adjudicación</h1>
        <p class="adj-sub">Folio {{ $folio }} · Cada partida se guarda sola al marcar Ganada/Perdida o al editar sus datos.</p>
      </div>
      <div class="search-wrap">
        <span class="search-count" id="searchCount" style="display:none;"></span>
        <div class="search-pill" id="searchPill">
          <button type="button" class="search-toggle" onclick="toggleSearch()" aria-label="Buscar partida">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          </button>
          <input type="text" id="searchInput" class="search-input" placeholder="Buscar partida..." oninput="filterCards()" onkeydown="if(event.key==='Escape'){clearSearch(true);}">
          <button type="button" class="search-clear" id="searchClear" onclick="clearSearch(false)" aria-label="Limpiar búsqueda">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
          </button>
        </div>
        <button type="button" class="btn btn-outline" onclick="openReportModal()">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
          Ver análisis completo
        </button>
      </div>
    </div>

    <div class="adj-bar">
      <div class="adj-stat"><div class="v" id="statGanadas">0</div><div class="l">Ganadas</div></div>
      <div class="adj-stat"><div class="v" id="statPerdidas">0</div><div class="l">Perdidas</div></div>
      <div class="adj-stat"><div class="v" id="statSubtotal">$0.00</div><div class="l">Subtotal ganadas</div></div>
    </div>

    <div id="adjList">
      @foreach($items as $i => $it)
        @php
          $sv = $it['saved'] ?? null;
          $res0 = $sv['resultado'] ?? 'ganada';
          $oi = $ofertaInfo[$it['id']] ?? ['brand' => '', 'model' => ''];
        @endphp
        <div class="adj-card {{ $res0 === 'perdida' ? 'is-perdida' : 'is-ganada' }}"
             data-row="{{ $i }}"
             data-qty="{{ $it['cantidad'] }}"
             data-offered="{{ $it['precio_unitario'] }}"
             data-num="{{ $it['numero'] }}"
             data-desc="{{ $it['descripcion'] }}"
             data-unit="{{ $it['unidad'] }}">
          <input type="hidden" name="partidas[{{ $i }}][item_id]" value="{{ $it['id'] }}">
          <input type="hidden" class="f-resultado" value="{{ $res0 }}">

          <div class="adj-head">
            <div class="adj-num">{{ $it['numero'] }}</div>
            <div>
              <div class="adj-name">{{ $it['descripcion'] ?: 'Producto sin descripción' }}</div>
              <div class="adj-meta">
                {{ rtrim(rtrim(number_format($it['cantidad'],2),'0'),'.') }} {{ $it['unidad'] }} ·
                Tu precio <strong>${{ number_format($it['precio_unitario'],2) }}</strong> ·
                Subtotal <strong>${{ number_format($it['subtotal'],2) }}</strong>
                <span class="save-status {{ $sv ? 'saved' : '' }}">{{ $sv ? 'Guardado' : '' }}</span>
              </div>
              @if($oi['brand'] || $oi['model'])
                <div class="adj-oferta">
                  <span class="tag">Ofertado</span>
                  Marca <strong>{{ $oi['brand'] ?: '—' }}</strong> ·
                  Modelo <strong>{{ $oi['model'] ?: '—' }}</strong>
                </div>
              @endif
            </div>
            <div class="seg">
              <button type="button" class="seg-win {{ $res0 === 'ganada' ? 'on-win' : '' }}" onclick="setRes({{ $i }},'ganada')">Ganada</button>
              <button type="button" class="seg-lose {{ $res0 === 'perdida' ? 'on-lose' : '' }}" onclick="setRes({{ $i }},'perdida')">Perdida</button>
            </div>
          </div>

          <div class="lose-box">
            <div class="grid2">
              <div class="field">
                <label>Licitante ganador</label>
                <input class="input f-proveedor" onchange="queueSave(this)" value="{{ $sv['proveedor_ganador'] ?? '' }}" placeholder="Nombre del Licitante ganador">
              </div>
              <div class="field">
                <label>Precio ganador (unit.)</label>
                <input class="input f-pganador" type="number" step="0.01" min="0" onchange="queueSave(this)" value="{{ $sv['precio_ganador'] ?? '' }}" placeholder="0.00">
              </div>
            </div>
            <div class="field">
              <label>Motivo de la pérdida</label>
              <textarea class="input f-motivo" rows="2" onchange="queueSave(this)" placeholder="Ej. Precio más alto, no cumplió ficha técnica, no surtió a tiempo...">{{ $sv['motivo_perdida'] ?? '' }}</textarea>
            </div>
            <div class="field">
              <label>Análisis (antecedente)</label>
              <textarea class="input f-analisis" rows="3" onchange="queueSave(this)" placeholder="Pulsa Analizar para generarlo automáticamente.">{{ $sv['analisis_ia'] ?? '' }}</textarea>
              <div><span class="diff-pill" style="display:none;"></span></div>
              <div style="margin-top:12px; display:flex; gap:12px; flex-wrap:wrap;">
                <button type="button" class="btn btn-outline btn-small" onclick="analizar({{ $i }}, this)">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                  Analizar diferencia
                </button>
                <button type="button" class="btn btn-success btn-small" onclick="savePartidaFromEl(this)">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                  Guardar partida
                </button>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <div class="search-empty" id="searchEmpty">No se encontraron partidas con ese término de búsqueda.</div>

    <div class="adj-footer">
      <div class="adj-meta" style="font-size: 14.5px; font-weight:600;">Todo se guarda automáticamente por partida. Las perdidas quedan como antecedente.</div>
      <div style="display:flex; gap:12px; flex-wrap:wrap;">
        <button type="button" class="btn btn-outline" onclick="openReportModal()">Análisis de resultados</button>
        <a id="btnVerResultado" class="btn btn-primary" href="#" onclick="return goResultado(event)">Ver resultado →</a>
      </div>
    </div>
  </div>

  {{-- ===== Modal: análisis completo ===== --}}
  <div class="modal-backdrop" id="reportModal">
    <div class="modal">
      <div class="modal-head">
        <div>
          <h2>Análisis de la licitación</h2>
          <p>Folio {{ $folio }} · Resumen, partidas no ganadas y plan de acción.</p>
        </div>
        <button type="button" class="modal-close" onclick="closeReportModal()">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
      </div>
      <div class="modal-body" id="reportBody"></div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="closeReportModal()" style="border-radius: 999px;">Cerrar</button>
        <button type="button" class="btn btn-primary" onclick="downloadReportPdf()" style="border-radius: 999px;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
          Descargar PDF
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  const csrfToken = @json(csrf_token());
  const analizarUrl = @json(route('propuestas-comerciales.adjudicacion.analizar-perdida', $propuestaComercial));
  const guardarPartidaUrl = @json(route('propuestas-comerciales.adjudicacion.guardar-partida', $propuestaComercial));
  const pdfUrl = @json(route('propuestas-comerciales.adjudicacion.analisis-pdf', $propuestaComercial));
  const showUrlBase = @json(url('/resultados-adjudicacion'));
  const reportFolio = @json($folio);
  const reportTitulo = @json($propuestaComercial->titulo ?? 'Adjudicación');
  let adjudicacionId = @json($adjudicacionId);

  const money = n => '$' + Number(n||0).toLocaleString('es-MX',{minimumFractionDigits:2, maximumFractionDigits:2});
  const pct = n => Number(n||0).toFixed(2) + '%';
  function escapeHtml(v){ return String(v ?? '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'","&#039;"); }

  function setRes(i, res) {
    const card = document.querySelector(`.adj-card[data-row="${i}"]`);
    card.querySelector('.f-resultado').value = res;
    card.classList.toggle('is-perdida', res === 'perdida');
    card.classList.toggle('is-ganada', res === 'ganada');
    card.querySelector('.seg-win').classList.toggle('on-win', res === 'ganada');
    card.querySelector('.seg-lose').classList.toggle('on-lose', res === 'perdida');
    recompute();
    savePartida(card);
  }

  function recompute() {
    let g = 0, p = 0, sub = 0;
    document.querySelectorAll('.adj-card').forEach(card => {
      const res = card.querySelector('.f-resultado').value;
      if (res === 'perdida') { p++; return; }
      g++;
      const qty = Number(card.dataset.qty || 0);
      const offered = Number(card.dataset.offered || 0);
      sub += qty * offered;
    });
    document.getElementById('statGanadas').textContent = g;
    document.getElementById('statPerdidas').textContent = p;
    document.getElementById('statSubtotal').textContent = money(sub);
  }

  /* ===== Autoguardado por partida ===== */
  const saveTimers = new WeakMap();

  function queueSave(el) {
    const card = el.closest('.adj-card');
    clearTimeout(saveTimers.get(card));
    saveTimers.set(card, setTimeout(() => savePartida(card), 600));
  }

  function savePartidaFromEl(el) {
    savePartida(el.closest('.adj-card'));
  }

  async function savePartida(card) {
    const res = card.querySelector('.f-resultado').value;
    const status = card.querySelector('.save-status');
    status.textContent = 'Guardando…';
    status.className = 'save-status saving';

    try {
      const resp = await fetch(guardarPartidaUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({
          item_id: card.querySelector('input[name$="[item_id]"]').value,
          resultado: res,
          precio_ofertado: card.dataset.offered,
          proveedor_ganador: card.querySelector('.f-proveedor')?.value || '',
          precio_ganador: card.querySelector('.f-pganador')?.value || '',
          motivo_perdida: card.querySelector('.f-motivo')?.value || '',
          analisis_ia: card.querySelector('.f-analisis')?.value || ''
        })
      });

      const data = await resp.json();
      if (!resp.ok || !data.ok) throw new Error(data.message || 'Error al guardar.');

      adjudicacionId = data.adjudicacion_id;
      document.getElementById('statGanadas').textContent = data.counters.ganadas;
      document.getElementById('statPerdidas').textContent = data.counters.perdidas;
      document.getElementById('statSubtotal').textContent = money(data.counters.subtotal_ganadas);

      status.textContent = 'Guardado';
      status.className = 'save-status saved';
      updateResultLink();
    } catch (e) {
      status.textContent = 'Error: ' + e.message;
      status.className = 'save-status error';
    }
  }

  function updateResultLink() {
    const btn = document.getElementById('btnVerResultado');
    if (btn) btn.style.opacity = adjudicacionId ? '1' : '.55';
  }

  function goResultado(e) {
    e.preventDefault();
    if (!adjudicacionId) {
      alert('Guarda al menos una partida primero.');
      return false;
    }
    window.location.href = showUrlBase + '/' + adjudicacionId;
    return false;
  }

  async function analizar(i, btn) {
    const card = document.querySelector(`.adj-card[data-row="${i}"]`);
    const itemId = card.querySelector('input[name$="[item_id]"]').value;
    const old = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<span class="loader"></span> Analizando...';

    try {
      const resp = await fetch(analizarUrl, {
        method:'POST',
        headers:{'X-CSRF-TOKEN':csrfToken,'Accept':'application/json','Content-Type':'application/json'},
        body: JSON.stringify({
          item_id: itemId,
          proveedor_ganador: card.querySelector('.f-proveedor').value,
          precio_ganador: card.querySelector('.f-pganador').value,
          precio_ofertado: card.dataset.offered,
          motivo_perdida: card.querySelector('.f-motivo').value
        })
      });
      const data = await resp.json();
      if (!resp.ok || !data.ok) throw new Error(data.message || 'Error al analizar.');

      card.querySelector('.f-analisis').value = data.analisis_ia || '';
      const pill = card.querySelector('.diff-pill');
      if (data.diferencia_monto !== null && data.diferencia_monto !== undefined) {
        const up = Number(data.diferencia_monto) > 0;
        pill.style.display = 'inline-block';
        pill.className = 'diff-pill ' + (up ? 'diff-up' : 'diff-down');
        pill.textContent = (up ? '(+) ' : '(-) ') + money(Math.abs(data.diferencia_monto)) + ' (' + Math.abs(data.diferencia_pct).toFixed(2) + '%)';
      } else { pill.style.display = 'none'; }

      savePartida(card);
    } catch (e) {
      alert(e.message);
    } finally {
      btn.disabled = false; btn.innerHTML = old;
    }
  }

  /* ===== Recolectar datos del formulario en vivo (para modal y PDF) ===== */
  function collectReport() {
    const ganadas = [], perdidas = [];
    let subtotalGanadas = 0, montoPerdidoPotencial = 0;

    document.querySelectorAll('.adj-card').forEach(card => {
      const res = card.querySelector('.f-resultado').value;
      const qty = Number(card.dataset.qty || 0);
      const offered = Number(card.dataset.offered || 0);
      const base = {
        num: card.dataset.num,
        desc: card.dataset.desc || 'Producto sin descripción',
        unit: card.dataset.unit || 'pz',
        qty,
        offered,
        subtotal: qty * offered
      };

      if (res === 'ganada') {
        subtotalGanadas += base.subtotal;
        ganadas.push(base);
        return;
      }

      const ganador = Number(card.querySelector('.f-pganador').value || 0);
      const proveedor = card.querySelector('.f-proveedor').value.trim();
      const motivo = card.querySelector('.f-motivo').value.trim();
      const analisis = card.querySelector('.f-analisis').value.trim();
      const dif = ganador > 0 ? offered - ganador : null;
      const difPct = ganador > 0 ? ((offered - ganador) / ganador) * 100 : null;

      montoPerdidoPotencial += base.subtotal;
      perdidas.push({ ...base, ganador, proveedor, motivo, analisis, dif, difPct });
    });

    const total = ganadas.length + perdidas.length;
    const tasaExito = total > 0 ? (ganadas.length / total) * 100 : 0;

    const porPrecio = perdidas.filter(p => p.ganador > 0 && p.dif > 0);
    const noPrecio = perdidas.filter(p => p.ganador > 0 && p.dif <= 0);
    const sinDato = perdidas.filter(p => !(p.ganador > 0));

    const promArriba = porPrecio.length
      ? porPrecio.reduce((a, p) => a + p.difPct, 0) / porPrecio.length
      : 0;
    const mayorBrecha = porPrecio.slice().sort((a, b) => b.difPct - a.difPct)[0] || null;

    return {
      ganadas, perdidas, total, tasaExito, subtotalGanadas, montoPerdidoPotencial,
      porPrecio, noPrecio, sinDato, promArriba, mayorBrecha
    };
  }

  function buildDiagnostico(r) {
    if (!r.perdidas.length) {
      return 'Se marcaron todas las partidas como ganadas. ¡Excelente resultado! No hay pérdidas que analizar en esta licitación.';
    }
    const partes = [];
    partes.push(`De ${r.total} partidas participadas se ganaron ${r.ganadas.length} y se perdieron ${r.perdidas.length} (tasa de éxito ${pct(r.tasaExito)}).`);

    if (r.porPrecio.length) {
      partes.push(`El principal factor de pérdida fue el PRECIO: ${r.porPrecio.length} partida(s) quedaron por arriba del licitante ganador, en promedio ${pct(r.promArriba)} más caras.`);
      if (r.mayorBrecha) {
        partes.push(`La mayor brecha fue en "${r.mayorBrecha.desc}", donde ofertamos ${money(r.mayorBrecha.offered)} contra ${money(r.mayorBrecha.ganador)} del ganador (${pct(r.mayorBrecha.difPct)} arriba).`);
      }
    }
    if (r.noPrecio.length) {
      partes.push(`En ${r.noPrecio.length} partida(s) igualamos o mejoramos el precio del ganador y aun así no se ganaron: la causa NO fue económica (revisar técnico, muestras, tiempos o documentación).`);
    }
    if (r.sinDato.length) {
      partes.push(`En ${r.sinDato.length} partida(s) no se capturó el precio del ganador, por lo que falta inteligencia de la competencia para esos casos.`);
    }
    return partes.join(' ');
  }

  function buildRecomendaciones(r) {
    const recos = [];

    if (r.porPrecio.length) {
      const objetivo = r.mayorBrecha
        ? ` Por ejemplo, para "${r.mayorBrecha.desc}" había que bajar a ~${money(r.mayorBrecha.ganador)} o menos.`
        : '';
      recos.push(`Ajustar precio en las ${r.porPrecio.length} partida(s) perdidas por costo: renegociar con el proveedor, buscar otra fuente de surtido o reducir el margen.${objetivo}`);
      recos.push('Pedir cotizaciones de varios proveedores antes de ofertar para tener el costo más bajo posible y poder competir en precio.');
    }
    if (r.noPrecio.length) {
      recos.push(`Revisar el cumplimiento técnico en las ${r.noPrecio.length} partida(s) donde el precio sí era competitivo: validar que la ficha técnica, marca, muestras y tiempos de entrega cumplan exactamente lo solicitado.`);
    }
    if (r.sinDato.length) {
      recos.push(`Conseguir el acta de fallo para registrar el precio y nombre del ganador en las ${r.sinDato.length} partida(s) sin dato, y así afinar próximas ofertas.`);
    }

    recos.push('Construir una base de datos de licitantes ganadores y sus precios por partida, para usarla como referencia en futuras participaciones.');
    recos.push('Guardar este análisis como antecedente: comparar contra próximas licitaciones del mismo cliente para detectar el rango de precios con el que se gana.');

    return recos;
  }

  function renderReportHtml(r) {
    const diag = buildDiagnostico(r);
    const recos = buildRecomendaciones(r);

    const statsHtml = `
      <div class="rep-grid">
        <div class="rep-stat"><div class="v" style="color:var(--success);">${r.ganadas.length}</div><div class="l">Ganadas</div></div>
        <div class="rep-stat"><div class="v" style="color:var(--danger);">${r.perdidas.length}</div><div class="l">Perdidas</div></div>
        <div class="rep-stat"><div class="v">${pct(r.tasaExito)}</div><div class="l">Tasa de éxito</div></div>
        <div class="rep-stat"><div class="v">${money(r.subtotalGanadas)}</div><div class="l">Subtotal ganado</div></div>
        <div class="rep-stat"><div class="v">${money(r.montoPerdidoPotencial)}</div><div class="l">No ganado</div></div>
      </div>`;

    const perdidasHtml = r.perdidas.length
      ? r.perdidas.map(p => `
          <div class="rep-lose">
            <div class="t">#${escapeHtml(p.num)} · ${escapeHtml(p.desc)}</div>
            <div class="m">
              ${escapeHtml(String(p.qty))} ${escapeHtml(p.unit)} ·
              Tu precio <strong>${money(p.offered)}</strong> ·
              Ganador <strong>${p.ganador > 0 ? money(p.ganador) : '—'}</strong>
              ${p.dif !== null ? ` · Diferencia <strong style="color: ${p.dif > 0 ? 'var(--danger)' : 'var(--success)'}">${p.dif > 0 ? '(+)' : '(-)'} ${money(Math.abs(p.dif))} (${pct(Math.abs(p.difPct))})</strong>` : ''}
              ${p.proveedor ? ` · Ganador: ${escapeHtml(p.proveedor)}` : ''}
            </div>
            ${p.motivo ? `<div class="a"><strong>Motivo:</strong> ${escapeHtml(p.motivo)}</div>` : ''}
            ${p.analisis ? `<div class="a">${escapeHtml(p.analisis)}</div>` : ''}
          </div>`).join('')
      : '<p class="rep-empty">No hay partidas marcadas como perdidas.</p>';

    return `
      ${statsHtml}
      <div class="rep-section">
        <h3>Diagnóstico general</h3>
        <div class="rep-text">${escapeHtml(diag)}</div>
      </div>
      <div class="rep-section">
        <h3>Partidas no ganadas (antecedente)</h3>
        ${perdidasHtml}
      </div>
      <div class="rep-section">
        <h3>Cómo solucionarlo y ganar la próxima vez</h3>
        <ul class="rep-recos">
          ${recos.map(t => `<li>${escapeHtml(t)}</li>`).join('')}
        </ul>
      </div>`;
  }

  function openReportModal() {
    const r = collectReport();
    document.getElementById('reportBody').innerHTML = renderReportHtml(r);
    document.getElementById('reportModal').classList.add('show');
    document.body.style.overflow = 'hidden';
  }

  function closeReportModal() {
    document.getElementById('reportModal').classList.remove('show');
    document.body.style.overflow = '';
  }

  document.getElementById('reportModal').addEventListener('click', e => {
    if (e.target.id === 'reportModal') closeReportModal();
  });

  /* ===== Descargar PDF formal (servidor / DomPDF) ===== */
  function downloadReportPdf() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = pdfUrl;
    form.target = '_blank';
    form.style.display = 'none';

    const add = (name, value) => {
      const inp = document.createElement('input');
      inp.type = 'hidden';
      inp.name = name;
      inp.value = value ?? '';
      form.appendChild(inp);
    };

    add('_token', csrfToken);

    let idx = 0;
    document.querySelectorAll('.adj-card').forEach(card => {
      const res = card.querySelector('.f-resultado').value;
      add(`partidas[${idx}][item_id]`, card.querySelector('input[name$="[item_id]"]').value);
      add(`partidas[${idx}][resultado]`, res);
      add(`partidas[${idx}][precio_ofertado]`, card.dataset.offered);

      if (res === 'perdida') {
        add(`partidas[${idx}][proveedor_ganador]`, card.querySelector('.f-proveedor').value);
        add(`partidas[${idx}][precio_ganador]`, card.querySelector('.f-pganador').value);
        add(`partidas[${idx}][motivo_perdida]`, card.querySelector('.f-motivo').value);
        add(`partidas[${idx}][analisis_ia]`, card.querySelector('.f-analisis').value);
      }
      idx++;
    });

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => form.remove(), 1500);
  }

  /* ===== Buscador expandible (filtrado dinámico con debounce) ===== */
  let searchTimer = null;

  function toggleSearch() {
    const pill = document.getElementById('searchPill');
    const input = document.getElementById('searchInput');
    if (pill.classList.contains('open')) {
      if (!input.value.trim()) {
        pill.classList.remove('open');
        input.blur();
      } else {
        input.focus();
      }
    } else {
      pill.classList.add('open');
      setTimeout(() => input.focus(), 200);
    }
  }

  function clearSearch(collapse) {
    const pill = document.getElementById('searchPill');
    const input = document.getElementById('searchInput');
    input.value = '';
    applyFilter('');
    document.getElementById('searchClear').classList.remove('show');
    if (collapse) {
      pill.classList.remove('open');
      input.blur();
    } else {
      input.focus();
    }
  }

  function filterCards() {
    clearTimeout(searchTimer);
    const q = document.getElementById('searchInput').value.trim().toLowerCase();
    document.getElementById('searchClear').classList.toggle('show', q.length > 0);
    searchTimer = setTimeout(() => applyFilter(q), 250);
  }

  function applyFilter(q) {
    let visibles = 0, total = 0;
    document.querySelectorAll('.adj-card').forEach(card => {
      total++;
      const num = (card.dataset.num || '').toLowerCase();
      const desc = (card.dataset.desc || '').toLowerCase();
      const match = !q || num.includes(q) || desc.includes(q);
      card.classList.toggle('search-hide', !match);
      if (match) visibles++;
    });

    document.getElementById('searchEmpty').style.display = (q && visibles === 0) ? 'block' : 'none';

    const count = document.getElementById('searchCount');
    if (q) {
      count.textContent = `${visibles} de ${total}`;
      count.style.display = 'inline-block';
    } else {
      count.style.display = 'none';
    }
  }

  // Colapsar al hacer clic fuera (si el campo está vacío)
  document.addEventListener('click', e => {
    const pill = document.getElementById('searchPill');
    const input = document.getElementById('searchInput');
    if (pill.classList.contains('open') && !pill.contains(e.target) && !input.value.trim()) {
      pill.classList.remove('open');
    }
  });

  recompute();
  updateResultLink();
</script>
@endsection