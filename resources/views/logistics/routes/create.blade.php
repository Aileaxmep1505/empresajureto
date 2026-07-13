@extends('layouts.app')
@section('title','Nueva ruta')
@section('content_class', 'content--flush')
@section('content')
@php
  $shipmentId      = (int) request('shipment_id', 0);
  $backUrl         = (string) request('back_url', '');
  $prefillDriverId = (int) request('driver_id', 0);
  $prefillName     = (string) request('name', '');
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>

<style>
  :root {
    --bg: #f9fafb;
    --card: #ffffff;
    --ink-title: #111111;
    --ink: #333333;
    --muted: #888888;
    --line: #ebebeb;
    --blue: #007aff;
    --blue-soft: #e6f0ff;
    --success: #15803d;
    --success-soft: #e6ffe6;
    --danger: #ff4a4a;
    --danger-soft: #ffebeb;
  }

  #rp-create {
    font-family: 'Quicksand', sans-serif;
    color: var(--ink);
    background: var(--bg);
    min-height: calc(100vh - 56px);
    padding-bottom: 40px;
  }

  #rp-create * {
    box-sizing: border-box;
  }

  @keyframes slideInLeft {
    0% { opacity: 0; transform: translateX(-40px); }
    100% { opacity: 1; transform: translateX(0); }
  }

  @keyframes slideInRight {
    0% { opacity: 0; transform: translateX(40px); }
    100% { opacity: 1; transform: translateX(0); }
  }

  .slide-left {
    opacity: 0;
    animation: slideInLeft 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  }

  .slide-right {
    opacity: 0;
    animation: slideInRight 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    animation-delay: 0.1s;
  }

  .wrap {
    max-width: 1320px;
    margin: 0 auto;
    padding: 32px 20px;
  }

  .pagehead {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--line);
    gap: 18px;
  }

  .title {
    font-weight: 700;
    font-size: clamp(28px, 3vw, 36px);
    color: var(--ink-title);
    line-height: 1.2;
    margin: 0 0 8px 0;
  }

  .subtitle {
    color: var(--muted);
    font-size: 1.05rem;
    font-weight: 500;
    margin: 0;
  }

  .grid-layout {
    display: grid;
    gap: 32px;
  }

  @media (min-width: 1024px) {
    .grid-layout {
      grid-template-columns: 400px 1fr;
    }
  }

  .cardx {
    background: var(--card);
    border-radius: 16px;
    border: 1px solid var(--line);
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    margin-bottom: 24px;
  }

  .cardx:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.04);
  }

  .cardx .hd {
    padding: 24px 24px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .cardx .hd-title {
    font-weight: 700;
    font-size: 1.2rem;
    color: var(--ink-title);
  }

  .cardx .bd {
    padding: 0 24px 24px 24px;
  }

  .field {
    margin-bottom: 24px;
  }

  .field label {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--ink-title);
    margin-bottom: 8px;
    display: block;
  }

  .control {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: var(--card);
    color: var(--ink);
    font-size: 1rem;
    transition: all 0.2s ease;
    font-family: inherit;
    font-weight: 500;
  }

  .control:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
    outline: none;
  }

  .control::placeholder {
    color: var(--muted);
    opacity: 0.7;
  }

  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 1rem;
    padding: 12px 24px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    font-family: inherit;
  }

  .btn:active {
    transform: translateY(1px) scale(0.98);
  }

  .btn-primary {
    background: var(--blue);
    color: #ffffff;
  }

  .btn-primary:hover {
    background: #0066d6;
  }

  .btn-ghost {
    background: transparent;
    color: var(--muted);
  }

  .btn-ghost:hover {
    background: var(--bg);
    color: var(--ink);
  }

  .btn-outline {
    background: var(--card);
    border: 1px solid var(--blue);
    color: var(--blue);
  }

  .btn-outline:hover {
    background: var(--blue-soft);
  }

  .btn:disabled {
    opacity: .55;
    cursor: not-allowed;
    transform: none;
  }

  .back-btn {
    font-weight: 600;
    color: var(--muted);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: color 0.2s;
    white-space: nowrap;
  }

  .back-btn:hover {
    color: var(--blue);
  }

  .badge {
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
  }

  .badge-info {
    background: var(--blue-soft);
    color: var(--blue);
  }

  .badge-no {
    background: var(--bg);
    color: var(--muted);
    border-radius: 8px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    border: 1px solid var(--line);
    flex: 0 0 auto;
  }

  .prov-list {
    max-height: 320px;
    overflow-y: auto;
    border: 1px solid var(--line);
    border-radius: 8px;
    padding: 8px;
    background: var(--card);
  }

  .prov-list::-webkit-scrollbar {
    width: 6px;
  }

  .prov-list::-webkit-scrollbar-track {
    background: transparent;
  }

  .prov-list::-webkit-scrollbar-thumb {
    background: var(--line);
    border-radius: 10px;
  }

  .prov-list::-webkit-scrollbar-thumb:hover {
    background: var(--muted);
  }

  .prov-item {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s ease;
    margin-bottom: 4px;
  }

  .prov-item:hover {
    background: var(--bg);
  }

  .custom-check {
    width: 20px;
    height: 20px;
    border-radius: 6px;
    border: 1px solid var(--line);
    appearance: none;
    outline: none;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    flex-shrink: 0;
    margin-top: 2px;
    background: var(--card);
  }

  .custom-check:checked {
    background: var(--blue);
    border-color: var(--blue);
  }

  .custom-check:checked::after {
    content: '';
    position: absolute;
    left: 6px;
    top: 2px;
    width: 5px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
  }

  .custom-check:focus {
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .prov-item-text {
    display: flex;
    flex-direction: column;
    gap: 4px;
    line-height: 1.4;
  }

  .prov-item-text .fw-bold {
    color: var(--ink-title);
    font-size: 0.95rem;
    font-weight: 700;
  }

  .prov-item-text small {
    color: var(--muted);
    font-size: 0.85rem;
    font-weight: 500;
  }

  .searchbar {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 16px;
    position: relative;
    z-index: 5;
  }

  .addr-wrap {
    position: relative;
    flex: 1 1 300px;
  }

  .addr-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
    z-index: 2;
  }

  .addr {
    width: 100%;
    padding-left: 44px !important;
  }

  .suggest {
    display: none;
  }

  #mapPick {
    height: 500px;
    border-radius: 12px;
    border: 1px solid var(--line);
    background: var(--bg);
    overflow: hidden;
    position: relative;
    z-index: 1;
  }

  .map-error {
    display: none;
    margin-bottom: 14px;
    padding: 14px 16px;
    border-radius: 12px;
    background: var(--danger-soft);
    color: var(--danger);
    font-weight: 700;
    font-size: .9rem;
  }

  .map-error.show {
    display: block;
  }

  .route-tools {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 14px;
    align-items: center;
  }

  .tool-group {
    display: inline-flex;
    gap: 6px;
    padding: 5px;
    border: 1px solid var(--line);
    border-radius: 999px;
    background: #fff;
  }

  .tool-btn {
    border: 0;
    background: transparent;
    color: var(--muted);
    border-radius: 999px;
    padding: 8px 12px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: background .18s ease, color .18s ease, transform .18s ease;
  }

  .tool-btn:hover {
    background: var(--bg);
    color: var(--ink);
  }

  .tool-btn.active {
    background: var(--blue-soft);
    color: var(--blue);
  }

  .tool-btn:active {
    transform: scale(.98);
  }

  .route-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 14px;
  }

  .route-summary > div {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 12px;
  }

  .route-summary span {
    display: block;
    color: var(--muted);
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 4px;
  }

  .route-summary strong {
    color: var(--ink-title);
    font-size: 15px;
    font-weight: 700;
  }

  .route-note {
    margin-bottom: 14px;
    color: var(--muted);
    font-size: 12px;
    font-weight: 600;
    line-height: 1.5;
  }

  @media (max-width: 900px) {
    .route-summary {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 520px) {
    .route-summary {
      grid-template-columns: 1fr;
    }

    .tool-group,
    .route-tools .btn {
      width: 100%;
      justify-content: center;
    }
  }


  .list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .rowx {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 16px;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: var(--card);
    transition: all 0.2s ease;
    cursor: grab;
  }

  .rowx:hover {
    border-color: var(--blue);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
  }

  .rowx:active {
    cursor: grabbing;
  }

  .rowx.ghost {
    opacity: 0.5;
    background: var(--bg);
    border-style: dashed;
  }

  .toastx {
    position: fixed;
    left: 50%;
    bottom: 40px;
    transform: translate(-50%, 20px);
    background: var(--ink-title);
    color: #fff;
    padding: 14px 24px;
    border-radius: 999px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    font-weight: 600;
    font-size: 0.95rem;
    opacity: 0;
    pointer-events: none;
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    z-index: 100000;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .toastx.show {
    opacity: 1;
    transform: translate(-50%, 0);
    pointer-events: auto;
  }

  .alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
    font-weight: 600;
    font-size: 0.95rem;
  }

  .alert-warning {
    background: var(--danger-soft);
    color: var(--danger);
    border: 1px solid rgba(255, 74, 74, 0.2);
  }

  .pac-container {
    border: 1px solid var(--line);
    border-radius: 12px;
    margin-top: 8px;
    box-shadow: 0 12px 28px rgba(0,0,0,.08);
    font-family: 'Quicksand', sans-serif;
    overflow: hidden;
    z-index: 99999 !important;
  }

  .pac-item {
    padding: 10px 12px;
    font-size: 13px;
    cursor: pointer;
  }

  .pac-item:hover {
    background: var(--bg);
  }

  @media (max-width: 768px) {
    .pagehead {
      align-items: flex-start;
      flex-direction: column;
    }

    .searchbar {
      flex-direction: column;
      align-items: stretch;
    }

    .addr-wrap,
    .searchbar .btn {
      width: 100%;
    }

    #mapPick {
      height: 420px;
    }
  }
</style>

<div id="rp-create">
  <div class="toastx" id="toastx">
    <i class="bi bi-info-circle-fill" style="color: var(--blue-soft);"></i>
    <span id="toast-msg"></span>
  </div>

  <div class="wrap">
    <div class="pagehead slide-left">
      <div>
        <h1 class="title">Programar nueva ruta</h1>
        <p class="subtitle">Selecciona un chofer, añade puntos de interés y organiza el trayecto.</p>
      </div>

      <a href="{{ route('routes.index') }}" class="back-btn">
        <i class="bi bi-arrow-left"></i> Volver
      </a>
    </div>

    <form id="routeForm" method="POST" action="{{ route('routes.store') }}" class="grid-layout">
      @csrf

      <input type="hidden" name="shipment_id" value="{{ $shipmentId ?: '' }}">
      <input type="hidden" name="back_url" value="{{ $backUrl }}">

      <div class="slide-left">
        <div class="cardx">
          <div class="hd">
            <span class="hd-title">Detalles de Ruta</span>
          </div>

          <div class="bd">
            <div class="field">
              <label>Chofer asignado</label>
              <select name="driver_id" class="control" required>
                <option value="">Selecciona un chofer…</option>

                @foreach($drivers as $d)
                  <option value="{{ $d->id }}" @selected($prefillDriverId === (int) $d->id)>
                    {{ $d->name ?? $d->email }} {{ $d->email ? "({$d->email})" : '' }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="field">
              <label>Nombre de la ruta <span style="color:var(--muted); font-weight:500">(Opcional)</span></label>
              <input type="text" name="name" class="control" placeholder="Ej. Ruta Zona Norte – 1" value="{{ old('name', $prefillName) }}">
            </div>

            <div class="field">
              <label>Proveedores Disponibles</label>

              <div class="prov-list">
                @forelse($providers as $p)
                  @php
                    $hasGeo = !is_null($p->lat) && !is_null($p->lng);
                    $companyName = trim((string)($p->empresa ?? ''));
                    $contactName = trim((string)($p->nombre ?? ''));
                    $providerName = $companyName ?: ($contactName ?: 'Proveedor #'.$p->id);
                    $addr   = trim((string)($p->address ?? ''));
                    $calle  = trim((string)($p->calle ?? ''));
                    $colonia= trim((string)($p->colonia ?? ''));
                    $ciudad = trim((string)($p->ciudad ?? ''));
                    $estado = trim((string)($p->estado ?? ''));
                    $cp     = trim((string)($p->cp ?? ''));
                  @endphp

                  <label class="prov-item">
                    <input class="custom-check provChk" type="checkbox"
                           data-id="{{ $p->id }}"
                           data-name="{{ e($providerName) }}"
                           data-contact-name="{{ e($contactName) }}"
                           data-lat="{{ $hasGeo ? $p->lat : '' }}"
                           data-lng="{{ $hasGeo ? $p->lng : '' }}"
                           data-address="{{ e($addr) }}"
                           data-calle="{{ e($calle) }}"
                           data-colonia="{{ e($colonia) }}"
                           data-ciudad="{{ e($ciudad) }}"
                           data-estado="{{ e($estado) }}"
                           data-cp="{{ e($cp) }}">

                    <div class="prov-item-text">
                      <span class="fw-bold">{{ $providerName }}</span>

                      @if($companyName && $contactName && mb_strtolower($companyName) !== mb_strtolower($contactName))
                        <small>Contacto: {{ $contactName }}</small>
                      @endif

                      <small>
                        @if($hasGeo)
                          <i class="bi bi-geo-alt-fill" style="color: var(--success);"></i> Ubicado
                        @else
                          <i class="bi bi-geo" style="color: var(--muted);"></i>
                          {{ $addr ?: trim(implode(', ', array_filter([$calle,$colonia,$ciudad,$estado,$cp]))) ?: 'Sin datos' }}
                        @endif
                      </small>
                    </div>
                  </label>
                @empty
                  <div style="padding: 32px 20px; text-align: center; color: var(--muted);">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    <div style="font-weight: 600;">No hay proveedores</div>
                  </div>
                @endforelse
              </div>
            </div>

            <div id="valAlert" class="alert alert-warning" style="display:none;"></div>

            <button type="submit" class="btn btn-primary" style="width: 100%; border-radius: 999px;">
              Guardar Ruta
            </button>
          </div>
        </div>
      </div>

      <div class="slide-right">
        <div class="cardx">
          <div class="hd">
            <span class="hd-title">Mapa de Ruta</span>
          </div>

          <div class="bd">
            <div class="searchbar">
              <div class="addr-wrap">
                <i class="bi bi-search addr-icon"></i>
                <input id="addrInput" class="control addr" type="text" placeholder="Buscar dirección en México...">
                <div id="suggestList" class="suggest"></div>
              </div>

              <button id="btnUseMyLoc" type="button" class="btn btn-outline" style="padding: 14px;" title="Usar mi ubicación actual">
                <i class="bi bi-crosshair"></i>
              </button>

              <button id="btnAddPreview" type="button" class="btn btn-ghost" style="color: var(--blue);" disabled>
                <i class="bi bi-plus-lg"></i> Añadir
              </button>
            </div>

            <div id="mapError" class="map-error">
              No se pudo cargar Google Maps. Revisa tu API Key, facturación y APIs habilitadas.
            </div>

            <div class="route-tools">
              <div class="tool-group">
                <button type="button" class="tool-btn active" data-map-type="roadmap">Mapa</button>
                <button type="button" class="tool-btn" data-map-type="satellite">Satélite</button>
                <button type="button" class="tool-btn" data-map-type="terrain">Relieve</button>
                <button type="button" class="tool-btn" data-map-type="hybrid">Híbrido</button>
              </div>

              <div class="tool-group">
                <button type="button" class="tool-btn" id="btnTraffic">Tráfico</button>
                <button type="button" class="tool-btn" id="btnTransit">Transporte</button>
                <button type="button" class="tool-btn" id="btnBike">Bici</button>
              </div>

              <div class="tool-group">
                <button type="button" class="tool-btn active" id="btnWithTolls">Con casetas</button>
                <button type="button" class="tool-btn" id="btnAvoidTolls">Libre</button>
                <button type="button" class="tool-btn active" id="btnRoundTrip">Ida y regreso</button>
              </div>

              <button type="button" class="btn btn-outline" id="btnEstimateRoute">
                <i class="bi bi-calculator"></i> Calcular ruta
              </button>
            </div>

            <div class="route-summary" id="routeSummary" style="display:none;">
              <div>
                <span>Distancia</span>
                <strong id="sumDistance">—</strong>
              </div>
              <div>
                <span>Tiempo con tráfico</span>
                <strong id="sumDuration">—</strong>
              </div>
              <div>
                <span>Retraso por tráfico</span>
                <strong id="sumTraffic">—</strong>
              </div>
              <div>
                <span>Casetas</span>
                <strong id="sumTolls">—</strong>
              </div>
            </div>

            <div class="route-note">
              El costo de casetas es estimado por Google Routes API cuando Google tiene tarifa disponible. Para cobro exacto se recomienda validar contra CAPUFE o una tabla interna.
            </div>

            <div id="mapPick"></div>
          </div>
        </div>

        <div class="cardx">
          <div class="hd">
            <span class="hd-title">Itinerario</span>
            <small style="color: var(--muted); font-size: 0.85rem; font-weight: 600;">Arrastra para reordenar</small>
          </div>

          <div class="bd">
            <ul id="picked" class="list"></ul>

            <div id="empty-state" style="text-align: center; padding: 40px 10px; color: var(--muted);">
              <i class="bi bi-signpost-split fs-2 d-block mb-3" style="opacity: 0.3;"></i>
              <div style="font-weight: 600;">Aún no has agregado puntos</div>
            </div>
          </div>
        </div>
      </div>

      <input type="hidden" id="stopsJson" name="stops">
    </form>
  </div>
</div>

<script
  async
  defer
  src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.browser_key') }}&libraries=places&v=weekly">
</script>

<script type="module" src="https://unpkg.com/sortablejs@1.15.2/modular/sortable.esm.js"></script>

<script type="module">
import Sortable from 'https://unpkg.com/sortablejs@1.15.2/modular/sortable.esm.js';

const picked = [];
const pickedEl = document.getElementById('picked');
const emptyState = document.getElementById('empty-state');
const stopsJson = document.getElementById('stopsJson');
const valAlert = document.getElementById('valAlert');

const addrInput = document.getElementById('addrInput');
const btnAddPrev = document.getElementById('btnAddPreview');
const btnMyLoc = document.getElementById('btnUseMyLoc');
const mapError = document.getElementById('mapError');

const toastEl = document.getElementById('toastx');
const toastMsg = document.getElementById('toast-msg');

let toastTimer = null;

let map = null;
let geocoder = null;
let autocomplete = null;
let previewMarker = null;
let previewData = null;
let pickedMarkers = [];
let routeLine = null;
let trafficLayer = null;
let transitLayer = null;
let bikeLayer = null;
let estimatedRouteLine = null;

let routeOptions = {
  avoidTolls: false,
  roundTrip: true,
};

const fmt = (n, d = 6) => Number(n).toFixed(d);
const fmtLatLng = (lat, lng) => `${fmt(lat, 5)}, ${fmt(lng, 5)}`;
const isNum = (n) => typeof n === 'number' && !Number.isNaN(n) && Number.isFinite(n);

function norm(s) {
  s = (s || '').toString().trim();

  if (!s) return '';

  s = s.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  s = s.replace(/[’'`"]/g, '');
  s = s.replace(/\s+/g, ' ').trim();

  return s;
}

function joinParts(parts) {
  const out = [];
  const seen = new Set();

  for (const p of parts) {
    const t = norm(p);

    if (!t) continue;

    const k = t.toLowerCase();

    if (seen.has(k)) continue;

    seen.add(k);
    out.push(t);
  }

  return out.join(', ');
}

function ensureMx(q) {
  q = norm(q);

  if (!q) return '';

  if (!/(^|,|\s)mex(ico)?(\s|,|$)/i.test(q)) {
    q += ', México';
  }

  return q;
}

function safeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function toast(msg, ms = 3000) {
  toastMsg.innerHTML = msg;
  toastEl.classList.add('show');

  clearTimeout(toastTimer);

  toastTimer = setTimeout(() => {
    toastEl.classList.remove('show');
  }, ms);
}

function showMapError(show = true) {
  mapError.classList.toggle('show', !!show);
}

function waitGoogleMaps() {
  return new Promise((resolve, reject) => {
    let tries = 0;

    const timer = setInterval(() => {
      tries++;

      if (window.google && google.maps && google.maps.places) {
        clearInterval(timer);
        resolve();
      }

      if (tries > 120) {
        clearInterval(timer);
        reject(new Error('Google Maps no cargó'));
      }
    }, 100);
  });
}

function makeStopIcon(index, color = '#007aff') {
  return {
    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
      <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 42 42">
        <filter id="s" x="-30%" y="-30%" width="160%" height="160%">
          <feDropShadow dx="0" dy="6" stdDeviation="4" flood-color="#000000" flood-opacity=".18"/>
        </filter>
        <path filter="url(#s)" d="M21 4c8.284 0 15 6.716 15 15 0 10.5-15 19-15 19S6 29.5 6 19C6 10.716 12.716 4 21 4Z" fill="${color}"/>
        <circle cx="21" cy="19" r="11" fill="#ffffff"/>
        <text x="21" y="23" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" font-weight="700" fill="${color}">${index}</text>
      </svg>
    `),
    scaledSize: new google.maps.Size(42, 42),
    anchor: new google.maps.Point(21, 38),
  };
}

function makePreviewIcon() {
  return {
    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
      <svg xmlns="http://www.w3.org/2000/svg" width="46" height="46" viewBox="0 0 46 46">
        <filter id="s" x="-30%" y="-30%" width="160%" height="160%">
          <feDropShadow dx="0" dy="8" stdDeviation="5" flood-color="#000000" flood-opacity=".18"/>
        </filter>
        <path filter="url(#s)" d="M23 4c8.837 0 16 7.163 16 16 0 11.2-16 21-16 21S7 31.2 7 20C7 11.163 14.163 4 23 4Z" fill="#15803d"/>
        <circle cx="23" cy="20" r="10" fill="#ffffff"/>
        <path d="M18.5 20.5l3 3 6-7" fill="none" stroke="#15803d" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `),
    scaledSize: new google.maps.Size(46, 46),
    anchor: new google.maps.Point(23, 41),
  };
}

function clearPickedMarkers() {
  pickedMarkers.forEach(marker => {
    try {
      marker.setMap(null);
    } catch (e) {}
  });

  pickedMarkers = [];
}

function syncMapMarkers() {
  if (!map || !window.google) return;

  clearPickedMarkers();

  if (routeLine) {
    routeLine.setMap(null);
    routeLine = null;
  }

  const bounds = new google.maps.LatLngBounds();
  const path = [];

  picked.forEach((point, index) => {
    const lat = Number(point.lat);
    const lng = Number(point.lng);

    if (!isNum(lat) || !isNum(lng)) return;

    const position = { lat, lng };
    const color = point.provider_id ? '#007aff' : '#15803d';

    bounds.extend(position);
    path.push(position);

    const marker = new google.maps.Marker({
      map,
      position,
      icon: makeStopIcon(index + 1, color),
      title: point.name || point.address || `Punto ${index + 1}`,
      zIndex: 100 + index,
    });

    const info = new google.maps.InfoWindow({
      content: `
        <div style="font-family:Quicksand,Arial,sans-serif;min-width:210px">
          <div style="font-weight:700;color:#111;margin-bottom:4px">
            ${safeHtml(point.name || point.address || `Punto ${index + 1}`)}
          </div>
          ${point.address ? `
            <div style="font-size:12px;color:#888;margin-bottom:6px;line-height:1.4">
              ${safeHtml(point.address)}
            </div>
          ` : ''}
          <div style="font-size:12px;color:#888">
            ${fmtLatLng(lat, lng)}
          </div>
        </div>
      `,
    });

    marker.addListener('click', () => {
      info.open({
        map,
        anchor: marker,
      });
    });

    pickedMarkers.push(marker);
  });

  if (path.length >= 2) {
    routeLine = new google.maps.Polyline({
      path,
      map,
      geodesic: true,
      strokeColor: '#007aff',
      strokeOpacity: 0.75,
      strokeWeight: 4,
    });
  }

  if (picked.length > 0 && !bounds.isEmpty()) {
    map.fitBounds(bounds, 70);
  }
}

function setPreview(lat, lng, label) {
  if (!map || !window.google) return;

  if (previewMarker) {
    previewMarker.setMap(null);
    previewMarker = null;
  }

  const position = {
    lat: Number(lat),
    lng: Number(lng),
  };

  previewMarker = new google.maps.Marker({
    map,
    position,
    icon: makePreviewIcon(),
    title: label || 'Previsualización',
    animation: google.maps.Animation.DROP,
    zIndex: 999,
  });

  const info = new google.maps.InfoWindow({
    content: `
      <div style="font-family:Quicksand,Arial,sans-serif;min-width:190px">
        <div style="font-weight:700;color:#111;margin-bottom:4px">
          ${safeHtml(label || 'Previsualización')}
        </div>
        <div style="font-size:12px;color:#888">
          ${fmtLatLng(position.lat, position.lng)}
        </div>
      </div>
    `,
  });

  previewMarker.addListener('click', () => {
    info.open({
      map,
      anchor: previewMarker,
    });
  });

  map.panTo(position);
  map.setZoom(16);
  btnAddPrev.disabled = false;
}

function geocodeAddress(address) {
  return new Promise((resolve) => {
    const q = ensureMx(address);

    if (!q || !geocoder) {
      resolve(null);
      return;
    }

    geocoder.geocode({
      address: q,
      componentRestrictions: {
        country: 'MX',
      },
      region: 'mx',
    }, (results, status) => {
      if (status !== 'OK' || !results || !results.length) {
        resolve(null);
        return;
      }

      const item = results[0];
      const loc = item.geometry.location;

      resolve({
        lat: loc.lat(),
        lng: loc.lng(),
        display_name: item.formatted_address || q,
      });
    });
  });
}

function reverseGeocode(lat, lng) {
  return new Promise((resolve) => {
    if (!geocoder) {
      resolve(null);
      return;
    }

    geocoder.geocode({
      location: {
        lat: Number(lat),
        lng: Number(lng),
      },
      region: 'mx',
    }, (results, status) => {
      if (status !== 'OK' || !results || !results.length) {
        resolve(null);
        return;
      }

      resolve(results[0].formatted_address || null);
    });
  });
}

function renderPicked() {
  pickedEl.innerHTML = '';

  if (picked.length === 0) {
    emptyState.style.display = 'block';
  } else {
    emptyState.style.display = 'none';
  }

  picked.forEach((p, i) => {
    const row = document.createElement('li');
    row.className = 'rowx';
    row.dataset.index = i;

    row.innerHTML = `
      <div style="display:flex; align-items:flex-start; gap:16px; flex-grow:1;">
        <span class="badge-no">${i + 1}</span>

        <div>
          <div style="font-weight: 700; color: var(--ink-title); font-size: 0.95rem; margin-bottom:4px;">
            ${safeHtml(p.name || p.address || '(Punto sin nombre)')}
            ${p.provider_id ? `<span class="badge badge-info ms-2">Prov #${p.provider_id}</span>` : ''}
          </div>

          ${p.address ? `
            <div style="color: var(--muted); font-size: 0.85rem; margin-bottom:4px; line-height:1.4; font-weight:500;">
              ${safeHtml(p.address)}
            </div>
          ` : ''}

          <div style="color: var(--muted); font-size: 0.75rem; font-family: monospace; opacity:0.7;">
            ${fmt(p.lat)}, ${fmt(p.lng)}
          </div>
        </div>
      </div>

      <div style="display:flex; gap:8px; flex-shrink:0;">
        <button type="button" class="btn btn-ghost" style="padding: 8px 12px;" data-edit="${i}" title="Editar nombre">
          <i class="bi bi-pencil-fill" style="font-size:0.9rem;"></i>
        </button>

        <button type="button" class="btn btn-ghost" style="padding: 8px 12px; color:var(--danger);" data-remove="${i}" title="Eliminar punto">
          <i class="bi bi-trash3-fill" style="font-size:0.9rem;"></i>
        </button>
      </div>
    `;

    pickedEl.appendChild(row);
  });

  stopsJson.value = JSON.stringify(picked);
  syncMapMarkers();
}

pickedEl.addEventListener('click', (e) => {
  const rm = e.target.closest('[data-remove]');
  const ed = e.target.closest('[data-edit]');

  if (rm) {
    picked.splice(+rm.dataset.remove, 1);
    renderPicked();
    toast('Punto eliminado');
  }

  if (ed) {
    const idx = +ed.dataset.edit;
    const name = prompt('Asignar nombre al punto:', picked[idx].name || picked[idx].address || '');

    if (name !== null) {
      picked[idx].name = name.trim();
      renderPicked();
    }
  }
});

new Sortable(pickedEl, {
  animation: 250,
  ghostClass: 'ghost',
  handle: '.rowx',
  easing: 'cubic-bezier(0.16, 1, 0.3, 1)',
  onEnd: (evt) => {
    const [m] = picked.splice(evt.oldIndex, 1);
    picked.splice(evt.newIndex, 0, m);
    renderPicked();
  },
});

async function geocodeMxFromParts(parts) {
  const calle = norm(parts?.calle);
  const colonia = norm(parts?.colonia);
  const ciudad = norm(parts?.ciudad);
  const estado = norm(parts?.estado);
  const cp = norm(parts?.cp);

  const query = ensureMx(joinParts([
    calle,
    colonia,
    ciudad,
    estado,
    cp,
  ]));

  if (!query) return null;

  return await geocodeAddress(query);
}

document.querySelectorAll('.provChk').forEach(chk => {
  chk.addEventListener('change', async () => {
    const providerId = Number(chk.dataset.id);
    const name = norm(chk.dataset.name || '');
    const address = norm(chk.dataset.address || '');

    const parts = {
      calle: chk.dataset.calle || '',
      colonia: chk.dataset.colonia || '',
      ciudad: chk.dataset.ciudad || '',
      estado: chk.dataset.estado || '',
      cp: chk.dataset.cp || '',
    };

    const removeByProviderId = () => {
      const i = picked.findIndex(p => p.provider_id === providerId);

      if (i >= 0) {
        picked.splice(i, 1);
      }
    };

    if (!chk.checked) {
      removeByProviderId();
      renderPicked();
      return;
    }

    if (picked.some(p => p.provider_id === providerId)) {
      toast('Este proveedor ya está en la ruta.');
      return;
    }

    let lat = Number(chk.dataset.lat);
    let lng = Number(chk.dataset.lng);

    if (!isNum(lat) || !isNum(lng)) {
      chk.disabled = true;
      toast('Ubicando con Google Maps...', 2000);

      const geo = await geocodeMxFromParts(parts);

      chk.disabled = false;

      if (!geo) {
        chk.checked = false;
        toast(`No pude geolocalizar a: ${safeHtml(name)}. <br><small>Búscalo manualmente en el mapa.</small>`, 4500);
        return;
      }

      lat = geo.lat;
      lng = geo.lng;

      chk.dataset.lat = String(lat);
      chk.dataset.lng = String(lng);

      if (!address && geo.display_name) {
        chk.dataset.address = geo.display_name;
      }

      toast('Ubicación encontrada con Google Maps');
    }

    const finalAddress = norm(chk.dataset.address || '')
      || ensureMx(joinParts([parts.calle, parts.colonia, parts.ciudad, parts.estado, parts.cp]))
      || '';

    picked.push({
      provider_id: providerId,
      name: name || ('Proveedor #' + providerId),
      address: finalAddress,
      calle: norm(parts.calle),
      colonia: norm(parts.colonia),
      ciudad: norm(parts.ciudad),
      estado: norm(parts.estado),
      cp: norm(parts.cp),
      lat,
      lng,
    });

    if (map) {
      map.panTo({
        lat,
        lng,
      });
      map.setZoom(14);
    }

    renderPicked();
    toast('Proveedor agregado a la ruta');
  });
});

btnAddPrev.addEventListener('click', () => {
  if (!previewData) return;

  const name = prompt('Nombre para este punto (opcional):', previewData.address || 'Nuevo Punto');

  picked.push({
    name: name ? name.trim() : (previewData.address || 'Punto de entrega'),
    address: previewData.address || '',
    lat: previewData.lat,
    lng: previewData.lng,
  });

  if (previewMarker) {
    previewMarker.setMap(null);
    previewMarker = null;
  }

  previewData = null;
  btnAddPrev.disabled = true;
  addrInput.value = '';

  renderPicked();
  toast('Punto agregado');
});

document.getElementById('routeForm').addEventListener('submit', (e) => {
  valAlert.style.display = 'none';

  if (!picked.length) {
    e.preventDefault();
    valAlert.style.display = 'block';
    valAlert.innerHTML = '<i class="bi bi-exclamation-triangle-fill" style="margin-right:8px;"></i> Debes agregar al menos un punto.';
    window.scrollTo({
      top: valAlert.offsetTop - 100,
      behavior: 'smooth',
    });
    return;
  }

  const bad = picked.find(p => !isNum(p.lat) || !isNum(p.lng));

  if (bad) {
    e.preventDefault();
    valAlert.style.display = 'block';
    valAlert.innerHTML = '<i class="bi bi-exclamation-triangle-fill" style="margin-right:8px;"></i> Un punto no tiene coordenadas válidas.';
    window.scrollTo({
      top: valAlert.offsetTop - 100,
      behavior: 'smooth',
    });
    return;
  }

  stopsJson.value = JSON.stringify(picked);
});


function formatMoney(value, currency = 'MXN') {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return 'No disponible';
  }

  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency,
  }).format(Number(value));
}

function formatMinutes(minutes) {
  const min = Number(minutes || 0);

  if (min < 60) {
    return `${Math.round(min)} min`;
  }

  const h = Math.floor(min / 60);
  const m = Math.round(min % 60);

  return `${h} h ${m} min`;
}

function clearEstimatedRoute() {
  if (estimatedRouteLine) {
    estimatedRouteLine.setMap(null);
    estimatedRouteLine = null;
  }
}

function drawEstimatedRoute(route) {
  clearEstimatedRoute();

  const coords = route?.coordinates || [];

  if (!coords.length) return;

  const path = coords
    .map(p => ({
      lat: Number(p.lat),
      lng: Number(p.lng),
    }))
    .filter(p => isNum(p.lat) && isNum(p.lng));

  if (!path.length) return;

  estimatedRouteLine = new google.maps.Polyline({
    map,
    path,
    geodesic: true,
    strokeColor: routeOptions.avoidTolls ? '#15803d' : '#007aff',
    strokeOpacity: 0.92,
    strokeWeight: 6,
  });

  const bounds = new google.maps.LatLngBounds();
  path.forEach(p => bounds.extend(p));

  if (!bounds.isEmpty()) {
    map.fitBounds(bounds, 72);
  }
}

function updateRouteSummary(route) {
  const box = document.getElementById('routeSummary');
  const sumDistance = document.getElementById('sumDistance');
  const sumDuration = document.getElementById('sumDuration');
  const sumTraffic = document.getElementById('sumTraffic');
  const sumTolls = document.getElementById('sumTolls');

  if (!box || !route) return;

  box.style.display = 'grid';

  sumDistance.textContent = `${route.distance_km ?? 0} km`;
  sumDuration.textContent = formatMinutes(route.duration_minutes ?? 0);
  sumTraffic.textContent = formatMinutes(route.traffic_delay_minutes ?? 0);

  if (routeOptions.avoidTolls) {
    sumTolls.textContent = 'Ruta libre';
  } else if (route?.tolls?.unknown_price) {
    sumTolls.textContent = 'Precio no disponible';
  } else {
    sumTolls.textContent = formatMoney(route?.tolls?.estimated_price, route?.tolls?.currency || 'MXN');
  }
}

function getPointsForEstimate() {
  return picked
    .map(p => ({
      lat: Number(p.lat),
      lng: Number(p.lng),
    }))
    .filter(p => isNum(p.lat) && isNum(p.lng));
}

async function estimateRoute() {
  const points = getPointsForEstimate();

  if (points.length < 2) {
    toast('Agrega mínimo 2 puntos para calcular ruta.');
    return;
  }

  const btn = document.getElementById('btnEstimateRoute');
  const oldHtml = btn ? btn.innerHTML : '';

  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Calculando...';
  }

  try {
    const response = await fetch(@json(route('routes.google.estimate')), {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': @json(csrf_token()),
      },
      credentials: 'include',
      body: JSON.stringify({
        points,
        avoid_tolls: routeOptions.avoidTolls,
        round_trip: routeOptions.roundTrip,
      }),
    });

    const data = await response.json().catch(() => null);

    if (!response.ok || !data?.ok) {
      toast(data?.message || 'No se pudo calcular la ruta.');
      return;
    }

    drawEstimatedRoute(data.route);
    updateRouteSummary(data.route);

    toast(routeOptions.avoidTolls ? 'Ruta libre calculada.' : 'Ruta con casetas calculada.');
  } catch (e) {
    toast('Error calculando la ruta.');
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = oldHtml;
    }
  }
}

function bindGoogleMapTools() {
  trafficLayer = new google.maps.TrafficLayer();
  transitLayer = new google.maps.TransitLayer();
  bikeLayer = new google.maps.BicyclingLayer();

  document.querySelectorAll('[data-map-type]').forEach(btn => {
    btn.addEventListener('click', () => {
      const type = btn.dataset.mapType;
      map.setMapTypeId(type);
      document.querySelectorAll('[data-map-type]').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    });
  });

  document.getElementById('btnTraffic')?.addEventListener('click', function () {
    const active = this.classList.toggle('active');
    trafficLayer.setMap(active ? map : null);
  });

  document.getElementById('btnTransit')?.addEventListener('click', function () {
    const active = this.classList.toggle('active');
    transitLayer.setMap(active ? map : null);
  });

  document.getElementById('btnBike')?.addEventListener('click', function () {
    const active = this.classList.toggle('active');
    bikeLayer.setMap(active ? map : null);
  });

  document.getElementById('btnWithTolls')?.addEventListener('click', function () {
    routeOptions.avoidTolls = false;
    document.getElementById('btnWithTolls')?.classList.add('active');
    document.getElementById('btnAvoidTolls')?.classList.remove('active');
    estimateRoute();
  });

  document.getElementById('btnAvoidTolls')?.addEventListener('click', function () {
    routeOptions.avoidTolls = true;
    document.getElementById('btnAvoidTolls')?.classList.add('active');
    document.getElementById('btnWithTolls')?.classList.remove('active');
    estimateRoute();
  });

  document.getElementById('btnRoundTrip')?.addEventListener('click', function () {
    routeOptions.roundTrip = !routeOptions.roundTrip;
    this.classList.toggle('active', routeOptions.roundTrip);
    estimateRoute();
  });

  document.getElementById('btnEstimateRoute')?.addEventListener('click', estimateRoute);
}

async function initGoogleMap() {
  try {
    await waitGoogleMaps();

    showMapError(false);

    geocoder = new google.maps.Geocoder();

    map = new google.maps.Map(document.getElementById('mapPick'), {
      center: {
        lat: 23.6345,
        lng: -102.5528,
      },
      zoom: 5,
      mapTypeId: 'roadmap',
      mapTypeControl: true,
      mapTypeControlOptions: {
        style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
        position: google.maps.ControlPosition.TOP_LEFT,
        mapTypeIds: ['roadmap', 'satellite', 'terrain', 'hybrid'],
      },
      streetViewControl: true,
      fullscreenControl: true,
      zoomControl: true,
      clickableIcons: true,
      gestureHandling: 'greedy',
    });

    bindGoogleMapTools();

    autocomplete = new google.maps.places.Autocomplete(addrInput, {
      componentRestrictions: {
        country: 'mx',
      },
      fields: [
        'formatted_address',
        'geometry',
        'name',
      ],
    });

    autocomplete.addListener('place_changed', () => {
      const place = autocomplete.getPlace();

      if (!place.geometry || !place.geometry.location) {
        toast('No se pudo obtener la ubicación de esa dirección.');
        return;
      }

      const lat = place.geometry.location.lat();
      const lng = place.geometry.location.lng();
      const address = place.formatted_address || addrInput.value || 'Ubicación encontrada';

      addrInput.value = address;

      previewData = {
        lat,
        lng,
        address,
        name: place.name || '',
      };

      setPreview(lat, lng, 'Ubicación encontrada');
    });

    addrInput.addEventListener('keydown', async (e) => {
      if (e.key !== 'Enter') return;

      e.preventDefault();

      const geo = await geocodeAddress(addrInput.value);

      if (!geo) {
        toast('No se encontró esa dirección en Google Maps.');
        return;
      }

      addrInput.value = geo.display_name;

      previewData = {
        lat: geo.lat,
        lng: geo.lng,
        address: geo.display_name,
        name: '',
      };

      setPreview(geo.lat, geo.lng, 'Ubicación encontrada');
    });

    map.addListener('click', async (e) => {
      const lat = e.latLng.lat();
      const lng = e.latLng.lng();

      btnAddPrev.disabled = true;

      const label = await reverseGeocode(lat, lng) || 'Punto manual';

      addrInput.value = label;

      previewData = {
        lat,
        lng,
        address: label,
        name: '',
      };

      setPreview(lat, lng, 'Punto seleccionado');
    });

    btnMyLoc.addEventListener('click', () => {
      if (!navigator.geolocation) {
        toast('Tu navegador no soporta GPS');
        return;
      }

      btnAddPrev.disabled = true;
      toast('Obteniendo tu ubicación...');

      navigator.geolocation.getCurrentPosition(async (p) => {
        const lat = p.coords.latitude;
        const lng = p.coords.longitude;

        const label = await reverseGeocode(lat, lng) || 'Mi ubicación';

        addrInput.value = label;

        previewData = {
          lat,
          lng,
          address: label,
          name: '',
        };

        setPreview(lat, lng, 'Mi ubicación actual');
      }, () => {
        toast('No se pudo acceder al GPS. Revisa los permisos.');
      }, {
        enableHighAccuracy: true,
        timeout: 12000,
        maximumAge: 5000,
      });
    });

    renderPicked();
  } catch (e) {
    showMapError(true);
    toast('No se pudo cargar Google Maps. Revisa tu API key.', 4500);
  }
}

window.gm_authFailure = function () {
  showMapError(true);
  toast('Error con la API Key de Google Maps.', 4500);
};

initGoogleMap();
</script>
@endsection