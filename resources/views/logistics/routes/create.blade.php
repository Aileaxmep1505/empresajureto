@extends('layouts.app')
@section('title','Nueva ruta')

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
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

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

  /* --- BASE --- */
  #rp-create {
    font-family: 'Quicksand', sans-serif;
    color: var(--ink);
    background: var(--bg);
    min-height: calc(100vh - 56px);
    padding-bottom: 40px;
  }
  #rp-create * { box-sizing: border-box; }

  /* --- ANIMACIONES DE ENTRADA LATERAL --- */
  @keyframes slideInLeft {
    0% { opacity: 0; transform: translateX(-40px); }
    100% { opacity: 1; transform: translateX(0); }
  }
  @keyframes slideInRight {
    0% { opacity: 0; transform: translateX(40px); }
    100% { opacity: 1; transform: translateX(0); }
  }
  .slide-left { opacity: 0; animation: slideInLeft 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
  .slide-right { opacity: 0; animation: slideInRight 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; animation-delay: 0.1s; }

  /* --- LAYOUT --- */
  .wrap { max-width: 1320px; margin: 0 auto; padding: 32px 20px; }
  .pagehead {
    display: flex; justify-content: space-between; align-items: flex-end; 
    margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid var(--line);
  }
  .title { font-weight: 700; font-size: clamp(28px, 3vw, 36px); color: var(--ink-title); line-height: 1.2; margin: 0 0 8px 0; }
  .subtitle { color: var(--muted); font-size: 1.05rem; font-weight: 500; margin: 0; }
  
  .grid-layout { display: grid; gap: 32px; }
  @media (min-width: 1024px){ .grid-layout { grid-template-columns: 400px 1fr; } }

  /* --- CARDS --- */
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
    display: flex; justify-content: space-between; align-items: center;
  }
  .cardx .hd-title { font-weight: 700; font-size: 1.2rem; color: var(--ink-title); }
  .cardx .bd { padding: 0 24px 24px 24px; }

  /* --- CONTROLES Y FORMULARIOS --- */
  .field { margin-bottom: 24px; }
  .field label { font-weight: 600; font-size: 0.95rem; color: var(--ink-title); margin-bottom: 8px; display: block; }
  .control {
    width: 100%; padding: 14px 16px;
    border: 1px solid var(--line);
    border-radius: 8px; background: var(--card);
    color: var(--ink); font-size: 1rem; transition: all 0.2s ease;
    font-family: inherit; font-weight: 500;
  }
  .control:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
    outline: none;
  }
  .control::placeholder { color: var(--muted); opacity: 0.7; }

  /* --- BOTONES --- */
  .btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    border-radius: 8px; font-weight: 700; font-size: 1rem;
    padding: 12px 24px; cursor: pointer; transition: all 0.2s ease; border: none;
    font-family: inherit;
  }
  .btn:active { transform: translateY(1px) scale(0.98); }
  
  .btn-primary { background: var(--blue); color: #ffffff; }
  .btn-primary:hover { background: #0066d6; }
  
  .btn-ghost { background: transparent; color: var(--muted); }
  .btn-ghost:hover { background: var(--bg); color: var(--ink); }
  
  .btn-outline { background: var(--card); border: 1px solid var(--blue); color: var(--blue); }
  .btn-outline:hover { background: var(--blue-soft); }
  
  .back-btn {
    font-weight: 600; color: var(--muted); text-decoration: none;
    display: flex; align-items: center; gap: 8px; transition: color 0.2s;
  }
  .back-btn:hover { color: var(--blue); }

  /* --- ETIQUETAS (BADGES) --- */
  .badge { padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
  .badge-info { background: var(--blue-soft); color: var(--blue); }
  .badge-no { background: var(--bg); color: var(--muted); border-radius: 8px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-weight: 700; border: 1px solid var(--line); }

  /* --- LISTA DE PROVEEDORES --- */
  .prov-list {
    max-height: 320px; overflow-y: auto; border: 1px solid var(--line);
    border-radius: 8px; padding: 8px; background: var(--card);
  }
  .prov-list::-webkit-scrollbar { width: 6px; }
  .prov-list::-webkit-scrollbar-track { background: transparent; }
  .prov-list::-webkit-scrollbar-thumb { background: var(--line); border-radius: 10px; }
  .prov-list::-webkit-scrollbar-thumb:hover { background: var(--muted); }

  .prov-item {
    display: flex; gap: 12px; align-items: flex-start; padding: 12px;
    border-radius: 8px; cursor: pointer; transition: background 0.2s ease;
    margin-bottom: 4px;
  }
  .prov-item:hover { background: var(--bg); }
  
  .custom-check {
    width: 20px; height: 20px; border-radius: 6px; border: 1px solid var(--line);
    appearance: none; outline: none; cursor: pointer; transition: all 0.2s;
    position: relative; flex-shrink: 0; margin-top: 2px; background: var(--card);
  }
  .custom-check:checked { background: var(--blue); border-color: var(--blue); }
  .custom-check:checked::after {
    content: ''; position: absolute; left: 6px; top: 2px; width: 5px; height: 10px;
    border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg);
  }
  .custom-check:focus { box-shadow: 0 0 0 3px var(--blue-soft); }

  .prov-item-text { display: flex; flex-direction: column; gap: 4px; line-height: 1.4; }
  .prov-item-text .fw-bold { color: var(--ink-title); font-size: 0.95rem; font-weight: 700; }
  .prov-item-text small { color: var(--muted); font-size: 0.85rem; font-weight: 500; }

  /* --- MAPA Y BÚSQUEDA --- */
  .searchbar {
    display: flex; gap: 12px; align-items: center; flex-wrap: wrap;
    margin-bottom: 16px; position: relative; z-index: 1000;
  }
  .addr-wrap { position: relative; flex: 1 1 300px; }
  .addr-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--muted); }
  .addr { width: 100%; padding-left: 44px !important; }
  
  .suggest {
    position: absolute; left: 0; right: 0; top: calc(100% + 8px);
    border: 1px solid var(--line); border-radius: 12px;
    background: var(--card); box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    max-height: 300px; overflow: auto; display: none; z-index: 9999;
  }
  .s-item { padding: 14px 16px; cursor: pointer; transition: background 0.15s; border-bottom: 1px solid var(--line); font-weight: 500;}
  .s-item:last-child { border-bottom: none; }
  .s-item:hover { background: var(--bg); color: var(--blue); }
  .s-empty, .s-loading { padding: 14px 16px; color: var(--muted); font-style: italic; }
  .s-hint { padding: 12px 16px; color: var(--muted); font-size: 0.85rem; background: var(--bg); font-weight: 600; border-top: 1px solid var(--line); }

  #mapPick {
    height: 500px; border-radius: 12px; border: 1px solid var(--line);
    background: var(--bg); overflow: hidden; position: relative; z-index: 1;
  }
  .preview-tip {
    background: var(--card); color: var(--ink-title); border-radius: 8px; padding: 8px 12px;
    font-weight: 700; font-size: 0.85rem; border: 1px solid var(--line); box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    font-family: 'Quicksand', sans-serif;
  }
  .leaflet-tooltip.preview-tip { background: transparent; border: none; box-shadow: none; }
  .leaflet-tooltip-top.preview-tip:before { border-top-color: var(--card); }

  /* --- PUNTOS SELECCIONADOS --- */
  .list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 12px; }
  .rowx {
    display: flex; justify-content: space-between; align-items: center; gap: 16px;
    padding: 16px; border: 1px solid var(--line); border-radius: 12px;
    background: var(--card); transition: all 0.2s ease; cursor: grab;
  }
  .rowx:hover { border-color: var(--blue); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
  .rowx:active { cursor: grabbing; }
  .rowx.ghost { opacity: 0.5; background: var(--bg); border-style: dashed; }

  /* --- TOAST NOTIFICATION --- */
  .toastx {
    position: fixed; left: 50%; bottom: 40px; transform: translate(-50%, 20px);
    background: var(--ink-title); color: #fff;
    padding: 14px 24px; border-radius: 999px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    font-weight: 600; font-size: 0.95rem; opacity: 0; pointer-events: none; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    z-index: 100000; display: flex; align-items: center; gap: 12px;
  }
  .toastx.show { opacity: 1; transform: translate(-50%, 0); pointer-events: auto; }

  /* Alert */
  .alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; font-weight: 600; font-size: 0.95rem; }
  .alert-warning { background: var(--danger-soft); color: var(--danger); border: 1px solid rgba(255, 74, 74, 0.2); }
</style>

<div id="rp-create">
  <div class="toastx" id="toastx">
    <i class="bi bi-info-circle-fill" style="color: var(--blue-soft);"></i> <span id="toast-msg"></span>
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
                          <i class="bi bi-geo" style="color: var(--muted);"></i> {{ $addr ?: trim(implode(', ', array_filter([$calle,$colonia,$ciudad,$estado,$cp]))) ?: 'Sin datos' }}
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

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script type="module" src="https://unpkg.com/sortablejs@1.15.2/modular/sortable.esm.js"></script>

<script type="module">
import Sortable from 'https://unpkg.com/sortablejs@1.15.2/modular/sortable.esm.js';

const picked = [];
const pickedEl  = document.getElementById('picked');
const emptyState= document.getElementById('empty-state');
const stopsJson = document.getElementById('stopsJson');
const valAlert  = document.getElementById('valAlert');

const addrInput  = document.getElementById('addrInput');
const suggestBox = document.getElementById('suggestList');
const btnAddPrev = document.getElementById('btnAddPreview');
const btnMyLoc   = document.getElementById('btnUseMyLoc');

const toastEl = document.getElementById('toastx');
const toastMsg = document.getElementById('toast-msg');
let toastTimer = null;

let map, markersLayer, previewMarker=null, previewData=null;

const debounce = (fn,ms)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); } };
const fmt = (n,d=6)=>Number(n).toFixed(d);
const fmtLatLng = (lat,lng)=>`${fmt(lat,5)}, ${fmt(lng,5)}`;
const isNum = (n)=> typeof n === 'number' && !Number.isNaN(n) && Number.isFinite(n);

function norm(s){
  s = (s || '').toString().trim();
  if (!s) return '';
  s = s.normalize('NFD').replace(/[\u0300-\u036f]/g,'');
  s = s.replace(/[’'`"]/g,'');
  s = s.replace(/\s+/g,' ').trim();
  return s;
}

function joinParts(parts){
  const out = [];
  const seen = new Set();
  for (const p of parts){
    const t = norm(p);
    if (!t) continue;
    const k = t.toLowerCase();
    if (seen.has(k)) continue;
    seen.add(k);
    out.push(t);
  }
  return out.join(', ');
}

function ensureMx(q){
  q = norm(q);
  if (!q) return '';
  if (!/(^|,|\s)mex(ico)?(\s|,|$)/i.test(q)) q += ', México';
  return q;
}

function toast(msg, ms=3000){
  toastMsg.innerHTML = msg;
  toastEl.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(()=> toastEl.classList.remove('show'), ms);
}

function showSuggest(){ suggestBox.style.display='block'; }
function hideSuggest(){ suggestBox.style.display='none'; }
function setSuggestHTML(html){
  suggestBox.innerHTML = html;
  showSuggest();
}

// Leaflet Map Init (Estilo Minimalista)
map = L.map('mapPick', { zoomSnap:0.5 }).setView([23.6345,-102.5528], 5);
L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { 
    attribution: '&copy; OpenStreetMap &copy; CARTO' 
}).addTo(map);
markersLayer = L.layerGroup().addTo(map);

function setPreview(lat,lng,label){
  if (previewMarker){ markersLayer.removeLayer(previewMarker); previewMarker=null; }
  previewMarker = L.marker([lat,lng]).addTo(markersLayer);
  previewMarker.bindTooltip(`<div class="preview-tip">${label || 'Previsualización'}<br><span style="color:var(--muted); font-weight:500; font-size:0.8rem;">${fmtLatLng(lat,lng)}</span></div>`,{permanent:true,direction:'top',offset:[0,-10], className:'preview-tip'}).openTooltip();
  map.flyTo([lat,lng], 15, {duration:.6, easeLinearity: 0.25});
  btnAddPrev.disabled = false;
}

map.on('click', async (e)=>{
  const {lat,lng} = e.latlng;
  btnAddPrev.disabled = true;
  try{
    const r = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&accept-language=es&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,{headers:{'Accept':'application/json'}});
    const j = await r.json();
    const label = j?.display_name || 'Punto manual';
    addrInput.value = label;
    previewData = {lat,lng,address:label,name:''};
    setPreview(lat,lng,'Punto seleccionado');
  }catch{
    previewData = {lat,lng,address:'',name:''};
    setPreview(lat,lng,'Punto manual');
  }
});

const MX_VIEWBOX = '-118.5,32.9,-86.5,14.3';
let suggestAbort = null;
let suggestSeq = 0;

function buildSearchUrl(q, limit=6){
  const qs = new URLSearchParams();
  qs.set('format','jsonv2');
  qs.set('limit', String(limit));
  qs.set('accept-language','es');
  qs.set('addressdetails','1');
  qs.set('countrycodes','mx');
  qs.set('viewbox', MX_VIEWBOX);
  qs.set('bounded','1');
  qs.set('q', ensureMx(q));
  return `https://nominatim.openstreetmap.org/search?${qs.toString()}`;
}

async function fetchSuggestions(q, opts = { autoPickFirst:false }){
  q = norm(q);
  suggestBox.innerHTML = '';
  hideSuggest();

  if (!q || q.length < 3) return;

  if (suggestAbort) suggestAbort.abort();
  suggestAbort = new AbortController();
  const mySeq = ++suggestSeq;

  setSuggestHTML(`<div class="s-loading">Buscando en México...</div>`);

  try{
    const url = buildSearchUrl(q, 6);
    const res = await fetch(url, {
      headers:{'Accept':'application/json'},
      signal: suggestAbort.signal,
    });

    if (mySeq !== suggestSeq) return;
    const items = await res.json();
    if (suggestAbort.signal.aborted) return;

    if (!Array.isArray(items) || !items.length){
      setSuggestHTML(`<div class="s-empty">No se encontraron resultados para "${q}"</div>`);
      return;
    }

    suggestBox.innerHTML = '';
    items.forEach((it)=>{
      const div = document.createElement('div');
      div.className = 's-item';
      div.innerHTML = `<i class="bi bi-geo-alt me-2" style="color:var(--muted);"></i> ${it.display_name}`;
      div.addEventListener('click', ()=>{
        addrInput.value = it.display_name;
        previewData = { lat:Number(it.lat), lng:Number(it.lon), address: it.display_name, name:'' };
        setPreview(previewData.lat, previewData.lng, 'Ubicación encontrada');
        hideSuggest();
      });
      suggestBox.appendChild(div);
    });

    const hint = document.createElement('div');
    hint.className = 's-hint';
    hint.innerHTML = `<i class="bi bi-lightbulb" style="color:#f59e0b; margin-right:4px;"></i> Tip: Agrega calle + colonia + ciudad.`;
    suggestBox.appendChild(hint);

    showSuggest();

    if (opts.autoPickFirst){
      const first = suggestBox.querySelector('.s-item');
      if (first) first.click();
    }
  }catch(err){
    if (err?.name === 'AbortError') return;
    setSuggestHTML(`<div class="s-empty" style="color:var(--danger);">Error de conexión al buscar.</div>`);
  }
}

const debouncedSuggest = debounce((q)=> fetchSuggestions(q, {autoPickFirst:false}), 350);

addrInput.addEventListener('input', ()=> debouncedSuggest(addrInput.value));
addrInput.addEventListener('focus', ()=>{
  if (suggestBox.children.length) showSuggest();
});
document.addEventListener('click', (e)=>{
  if (!e.target.closest('.addr-wrap')) hideSuggest();
});

addrInput.addEventListener('keydown', async (e)=>{
  if (e.key === 'Enter'){
    e.preventDefault();
    await fetchSuggestions(addrInput.value, { autoPickFirst:true });
  }
});

btnMyLoc.addEventListener('click', ()=>{
  if (!navigator.geolocation){ toast('Tu navegador no soporta GPS'); return; }
  btnAddPrev.disabled = true;
  toast('Obteniendo tu ubicación...');
  navigator.geolocation.getCurrentPosition(async (p)=>{
    const lat=p.coords.latitude, lng=p.coords.longitude;
    try{
      const r=await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&accept-language=es&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,{headers:{'Accept':'application/json'}});
      const j=await r.json();
      const label=j?.display_name || 'Mi ubicación';
      addrInput.value = label;
      previewData = {lat,lng,address:label,name:''};
      setPreview(lat,lng,'Mi ubicación actual');
    }catch{
      previewData = {lat,lng,address:'',name:''};
      setPreview(lat,lng,'Mi ubicación actual');
    }
  }, ()=>toast('No se pudo acceder al GPS. Revisa los permisos.'), {enableHighAccuracy:true,timeout:12000,maximumAge:5000});
});

function renderPicked(){
  pickedEl.innerHTML='';
  
  if(picked.length === 0) {
      emptyState.style.display = 'block';
  } else {
      emptyState.style.display = 'none';
  }

  picked.forEach((p,i)=>{
    const row=document.createElement('li');
    row.className='rowx';
    row.dataset.index=i;
    row.innerHTML = `
      <div style="display:flex; align-items:flex-start; gap:16px; flex-grow:1;">
        <span class="badge-no">${i+1}</span>
        <div>
          <div style="font-weight: 700; color: var(--ink-title); font-size: 0.95rem; margin-bottom:4px;">
            ${p.name || p.address || '(Punto sin nombre)'}
            ${p.provider_id ? `<span class="badge badge-info ms-2">Prov #${p.provider_id}</span>` : ''}
          </div>
          ${p.address ? `<div style="color: var(--muted); font-size: 0.85rem; margin-bottom:4px; line-height:1.4; font-weight:500;">${p.address}</div>` : ''}
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
      </div>`;
    pickedEl.appendChild(row);
  });
  stopsJson.value = JSON.stringify(picked);
}

pickedEl.addEventListener('click',(e)=>{
  const rm = e.target.closest('[data-remove]'); const ed = e.target.closest('[data-edit]');
  if (rm){ 
      picked.splice(+rm.dataset.remove,1); 
      renderPicked(); 
      toast('Punto eliminado');
  }
  if (ed){
    const idx = +ed.dataset.edit;
    const name = prompt('Asignar nombre al punto:', picked[idx].name || picked[idx].address || '');
    if (name !== null){ picked[idx].name = name.trim(); renderPicked(); }
  }
});

new Sortable(pickedEl,{
    animation: 250, 
    ghostClass: 'ghost',
    handle: '.rowx',
    easing: "cubic-bezier(0.16, 1, 0.3, 1)",
    onEnd:(evt)=>{ 
        const [m]=picked.splice(evt.oldIndex,1); 
        picked.splice(evt.newIndex,0,m); 
        renderPicked(); 
    }
});

async function geocodeMxFromParts(parts){
  const calle   = norm(parts?.calle);
  const colonia = norm(parts?.colonia);
  const ciudad  = norm(parts?.ciudad);
  const estado  = norm(parts?.estado);
  const cp      = norm(parts?.cp);

  const street = joinParts([calle, colonia]);
  const city   = ciudad;
  const state  = estado;
  const zip    = cp;

  try{
    const qs = new URLSearchParams();
    qs.set('format','jsonv2');
    qs.set('limit','1');
    qs.set('accept-language','es');
    qs.set('countrycodes','mx');
    qs.set('addressdetails','1');
    qs.set('viewbox', MX_VIEWBOX);
    qs.set('bounded','1');
    if (street) qs.set('street', street);
    if (city)   qs.set('city', city);
    if (state)  qs.set('state', state);
    if (zip)    qs.set('postalcode', zip);
    qs.set('country','Mexico');

    const url = `https://nominatim.openstreetmap.org/search?${qs.toString()}`;
    const res = await fetch(url, { headers: { 'Accept':'application/json' }});
    const items = await res.json();
    if (Array.isArray(items) && items.length){
      const it = items[0];
      const lat = Number(it.lat), lng = Number(it.lon);
      if (isNum(lat) && isNum(lng)) return { lat, lng, display_name: it.display_name || '' };
    }
  }catch{}

  const q = ensureMx(joinParts([street, city, state, zip]));
  if (!q) return null;

  try{
    const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&accept-language=es&countrycodes=mx&viewbox=${encodeURIComponent(MX_VIEWBOX)}&bounded=1&q=${encodeURIComponent(q)}`;
    const res = await fetch(url, { headers: { 'Accept':'application/json' }});
    const items = await res.json();
    if (!Array.isArray(items) || !items.length) return null;
    const it = items[0];
    const lat = Number(it.lat), lng = Number(it.lon);
    if (!isNum(lat) || !isNum(lng)) return null;
    return { lat, lng, display_name: it.display_name || q };
  }catch{
    return null;
  }
}

document.querySelectorAll('.provChk').forEach(chk=>{
  chk.addEventListener('change', async ()=>{
    const providerId = Number(chk.dataset.id);
    const name = norm(chk.dataset.name || '');
    const address = norm(chk.dataset.address || '');

    const parts = {
      calle:   chk.dataset.calle || '',
      colonia: chk.dataset.colonia || '',
      ciudad:  chk.dataset.ciudad || '',
      estado:  chk.dataset.estado || '',
      cp:      chk.dataset.cp || '',
    };

    const removeByProviderId = () => {
      const i = picked.findIndex(p => p.provider_id === providerId);
      if (i >= 0) picked.splice(i, 1);
    };

    if (!chk.checked){
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
      toast(`Ubicando...`, 2000);

      const geo = await geocodeMxFromParts(parts);

      chk.disabled = false;

      if (!geo) {
        chk.checked = false;
        toast(`No pude geolocalizar a: ${name}. <br><small>Búscalo manualmente en el mapa.</small>`, 4000);
        return;
      }

      lat = geo.lat; lng = geo.lng;
      chk.dataset.lat = String(lat);
      chk.dataset.lng = String(lng);
      if (!address && geo.display_name) chk.dataset.address = geo.display_name;
      
      toast(`¡Ubicación encontrada!`);
    }

    try { map.flyTo([lat,lng], 14, {duration:.6, easeLinearity: 0.25}); } catch {}

    const finalAddress = norm(chk.dataset.address || '') || ensureMx(joinParts([parts.calle, parts.colonia, parts.ciudad, parts.estado, parts.cp])) || '';

    picked.push({
      provider_id: providerId,
      name: name || ('Proveedor #' + providerId),
      address: finalAddress,
      calle: norm(parts.calle),
      colonia: norm(parts.colonia),
      ciudad: norm(parts.ciudad),
      estado: norm(parts.estado),
      cp: norm(parts.cp),
      lat, lng
    });

    const m = L.circleMarker([lat, lng], {
        radius: 8, fillColor: '#007aff', color: '#fff', weight: 2, fillOpacity: 1
    }).addTo(markersLayer);
    m.bindTooltip(`<div style="font-family:'Quicksand'; font-weight:700;">${name || 'Proveedor'}</div>`, {direction: 'top', offset: [0, -10]});

    renderPicked();
  });
});

btnAddPrev.addEventListener('click', ()=>{
  if (!previewData) return;
  const name = prompt('Nombre para este punto (opcional):', previewData.address || 'Nuevo Punto');
  picked.push({
    name: name ? name.trim() : (previewData.address || 'Punto de entrega'),
    address: previewData.address || '',
    lat: previewData.lat,
    lng: previewData.lng
  });
  
  const m = L.circleMarker([previewData.lat, previewData.lng], {
        radius: 8, fillColor: '#15803d', color: '#fff', weight: 2, fillOpacity: 1
  }).addTo(markersLayer);
  m.bindTooltip(`<div style="font-family:'Quicksand'; font-weight:700;">${name || 'Punto Agregado'}</div>`, {direction: 'top', offset: [0, -10]});
  
  if (previewMarker){ markersLayer.removeLayer(previewMarker); previewMarker=null; }
  previewData=null; btnAddPrev.disabled=true; 
  addrInput.value = '';
  renderPicked();
  toast('Punto agregado');
});

document.getElementById('routeForm').addEventListener('submit',(e)=>{
  valAlert.style.display = 'none';

  if (!picked.length){
    e.preventDefault();
    valAlert.style.display = 'block';
    valAlert.innerHTML = '<i class="bi bi-exclamation-triangle-fill" style="margin-right:8px;"></i> Debes agregar al menos un punto.';
    window.scrollTo({ top: valAlert.offsetTop - 100, behavior: 'smooth' });
    return;
  }

  const bad = picked.find(p => !isNum(p.lat) || !isNum(p.lng));
  if (bad){
    e.preventDefault();
    valAlert.style.display = 'block';
    valAlert.innerHTML = '<i class="bi bi-exclamation-triangle-fill" style="margin-right:8px;"></i> Un punto no tiene coordenadas válidas.';
    window.scrollTo({ top: valAlert.offsetTop - 100, behavior: 'smooth' });
    return;
  }

  stopsJson.value = JSON.stringify(picked);
});

renderPicked();
</script>
@endsection