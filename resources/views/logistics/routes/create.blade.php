{{-- resources/views/routes/create.blade.php --}}
@extends('layouts.app')
@section('title','Nueva ruta')

@section('content')
<div id="rp-create">
  <style>
    /* =========================
       NAMESPACE #rp-create
       ========================= */
    #rp-create{
      --ink:#0e1726; --muted:#64748b; --line:#e7eef7; --bg:#f7f9fc; --card:#ffffff;
      --brand:#a6d3ff; --brand-ink:#0b1220;
      --accent:#b7f0e2; --accent-ink:#064e3b;
      --radius:16px; --shadow:0 14px 40px rgba(2,8,23,.08);
      color:var(--ink); background:var(--bg); min-height:calc(100vh - 56px);
    }
    #rp-create *{box-sizing:border-box}

    .wrap{max-width:1280px; margin:clamp(16px,2.5vw,28px) auto; padding:0 16px}
    .pagehead{display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px}
    .title{font-weight:900; letter-spacing:.2px; font-size:clamp(22px,2.4vw,32px)}
    .subtitle{color:var(--muted)}
    .grid{display:grid; gap:16px}
    @media (min-width: 992px){ .grid{grid-template-columns:340px 1fr} }

    .cardx{background:var(--card); border:1px solid var(--line); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden}
    .cardx .hd{padding:12px 14px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center}
    .cardx .bd{padding:14px}

    /* Botones */
    .btn{border-radius:12px; border:1px solid transparent; font-weight:700; transition:.18s; box-shadow:0 6px 18px rgba(2,8,23,.06)}
    .btn:hover{background:#fff !important; color:var(--ink) !important; transform:translateY(-1px); box-shadow:0 18px 42px rgba(2,8,23,.14)}
    .btn-brand{background:var(--brand); color:var(--brand-ink); border-color:#d6ecff}
    .btn-ghost{background:#f4f7fb; border-color:#eaf0f7; color:#0b1220}
    .btn-outline{background:#f5f9ff; border-color:#dbe6f4; color:#0b1220}

    /* Campos */
    .field{margin-bottom:12px}
    label{font-weight:700; font-size:.92rem}
    .control{width:100%; padding:.6rem .8rem; border:1px solid var(--line); border-radius:12px; background:#fff; outline:none; transition:.15s; font-size:.95rem}
    .control:focus{border-color:#cfe0ff; box-shadow:0 0 0 6px rgba(166,211,255,.25)}
    .control::placeholder{color:#9aa8b5}

    /* Providers */
    .prov-list{max-height:280px; overflow:auto; border:1px solid var(--line); border-radius:12px; padding:8px; background:#fff}
    .prov-item{display:flex; gap:8px; align-items:flex-start; padding:8px 6px}
    .prov-item small{display:block; line-height:1.25}

    /* Toast */
    .toastx{
      position:fixed; left:50%; top:18px; transform:translateX(-50%);
      background:#111827; color:#fff; padding:10px 12px; border-radius:12px;
      box-shadow:0 18px 50px rgba(2,8,23,.25);
      font-weight:800; font-size:.92rem;
      opacity:0; pointer-events:none; transition:.22s;
      z-index:100000;
      max-width:min(680px, calc(100vw - 24px));
    }
    .toastx.show{opacity:1; pointer-events:auto}
    .toastx .muted{opacity:.85; font-weight:700}

    /* Mapa */
    #mapPick{height:540px; border-radius:12px; border:1px solid var(--line); background:#e9eef8; overflow:hidden}
    .preview-tip{background:#111827; color:#fff; border-radius:10px; padding:.28rem .55rem; font-weight:800; font-size:.8rem; border:2px solid #fff; box-shadow:0 10px 24px rgba(2,8,23,.25)}

    /* Barra de búsqueda (no overlay) */
    .searchbar{display:flex; gap:8px; align-items:center; flex-wrap:wrap; border:1px solid var(--line); border-radius:14px; padding:10px; background:#fff; margin-bottom:12px}
    .addr-wrap{position:relative; flex:1 1 520px}
    .addr{width:100%}
    .suggest{position:absolute; left:0; right:0; top:calc(100% + 6px); border:1px solid var(--line); border-radius:12px; background:#fff; box-shadow:var(--shadow); max-height:280px; overflow:auto; display:none; z-index:9999}
    .s-item{padding:.55rem .7rem; cursor:pointer}
    .s-item:hover{background:#f6fafc}
    .s-empty{padding:.6rem .75rem; color:var(--muted)}

    /* Lista de puntos */
    .list{list-style:none; margin:0; padding:0}
    .rowx{display:flex; justify-content:space-between; align-items:center; gap:10px; padding:.6rem .75rem; border:1px solid var(--line); border-radius:12px; background:#fff}
    .rowx + .rowx{margin-top:8px}
    .badge-no{border:1px solid var(--line); border-radius:8px; padding:.1rem .4rem; background:#f7fbff}

    /* Link volver */
    .back{font-weight:800; color:#4338ca; text-decoration:none; background:#f5f7ff; border:1px solid #e5e7ff; padding:.4rem .7rem; border-radius:999px}
    .back:hover{background:#fff}
  </style>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>

  <div class="toastx" id="toastx"></div>

  <div class="wrap">
    <div class="pagehead">
      <div>
        <div class="title">Programar nueva ruta</div>
        <div class="subtitle">Selecciona chofer, agrega puntos (buscador, mapa o providers) y guarda.</div>
      </div>
      <a href="{{ route('routes.index') }}" class="back">← Volver</a>
    </div>

    <form id="routeForm" method="POST" action="{{ route('routes.store') }}" class="grid">
      @csrf

      {{-- Columna izquierda --}}
      <div class="cardx">
        <div class="hd"><div class="fw-bold">Detalles</div></div>
        <div class="bd">
          <div class="field">
            <label class="mb-1">Chofer asignado</label>
            <select name="driver_id" class="control" required>
              <option value="">Selecciona…</option>
              @foreach($drivers as $d)
                <option value="{{ $d->id }}">{{ $d->name ?? $d->email }} {{ $d->email ? "({$d->email})" : '' }}</option>
              @endforeach
            </select>
          </div>

          <div class="field">
            <label class="mb-1">Nombre de la ruta (opcional)</label>
            <input type="text" name="name" class="control" placeholder="Ruta Zona Norte – 1">
          </div>

          <div class="field">
            <div class="fw-bold mb-1">Providers (enviar provider_id + dirección por partes)</div>
            <div class="prov-list">
              @forelse($providers as $p)
                @php
                  $hasGeo = !is_null($p->lat) && !is_null($p->lng);
                  $addr   = trim((string)($p->address ?? ''));
                  $calle  = trim((string)($p->calle ?? ''));
                  $colonia= trim((string)($p->colonia ?? ''));
                  $ciudad = trim((string)($p->ciudad ?? ''));
                  $estado = trim((string)($p->estado ?? ''));
                  $cp     = trim((string)($p->cp ?? ''));
                @endphp

                <label class="prov-item">
                  <input class="form-check-input provChk" type="checkbox"
                         data-id="{{ $p->id }}"
                         data-name="{{ $p->name }}"
                         data-lat="{{ $hasGeo ? $p->lat : '' }}"
                         data-lng="{{ $hasGeo ? $p->lng : '' }}"
                         data-address="{{ e($addr) }}"
                         data-calle="{{ e($calle) }}"
                         data-colonia="{{ e($colonia) }}"
                         data-ciudad="{{ e($ciudad) }}"
                         data-estado="{{ e($estado) }}"
                         data-cp="{{ e($cp) }}">
                  <span>
                    <div class="fw-bold">{{ $p->name ?: 'Proveedor #'.$p->id }}</div>
                    <small class="text-muted">
                      @if($hasGeo)
                        ({{ number_format($p->lat,6) }}, {{ number_format($p->lng,6) }})
                      @else
                        {{ $addr ?: trim(implode(', ', array_filter([$calle,$colonia,$ciudad,$estado,$cp]))) ?: 'Sin datos' }}
                        <span class="muted"> · se geocodifica al seleccionar</span>
                      @endif
                    </small>
                  </span>
                </label>
              @empty
                <em class="text-muted">No hay providers disponibles.</em>
              @endforelse
            </div>
          </div>

          <div id="valAlert" class="alert alert-warning d-none"></div>

          <div class="d-grid mt-2">
            <button type="submit" class="btn btn-brand btn-lg">
              <i class="bi bi-floppy2-fill"></i> Guardar ruta
            </button>
          </div>
        </div>
      </div>

      {{-- Columna derecha --}}
      <div class="cardx">
        <div class="hd">
          <div class="fw-bold">Mapa y buscador</div>
        </div>
        <div class="bd">
          {{-- Barra de búsqueda (no overlay) --}}
          <div class="searchbar">
            <div class="addr-wrap">
              <input id="addrInput" class="control addr" type="text" placeholder="Escribe dirección en México (calle, colonia, ciudad)…">
              <div id="suggestList" class="suggest"></div>
            </div>
            <button id="btnUseMyLoc" type="button" class="btn btn-ghost">
              <i class="bi bi-geo-alt"></i> Mi ubicación
            </button>
            <button id="btnAddPreview" type="button" class="btn btn-outline" disabled>
              <i class="bi bi-plus-lg"></i> Agregar a la ruta
            </button>
          </div>

          <div id="mapPick"></div>
        </div>
      </div>

      {{-- Lista de puntos --}}
      <div class="cardx" style="grid-column:1/-1">
        <div class="hd">
          <div class="fw-bold">Puntos seleccionados</div>
          <small class="text-muted">Arrastra para reordenar (visual). La optimización final se hace al iniciar.</small>
        </div>
        <div class="bd">
          <ul id="picked" class="list"></ul>
        </div>
      </div>

      <input type="hidden" id="stopsJson" name="stops">
    </form>
  </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script type="module" src="https://unpkg.com/sortablejs@1.15.2/modular/sortable.esm.js"></script>

<script type="module">
import Sortable from 'https://unpkg.com/sortablejs@1.15.2/modular/sortable.esm.js';

const picked = []; // {name, lat, lng, address?, provider_id?, calle?, colonia?, ciudad?, estado?, cp?}
const pickedEl  = document.getElementById('picked');
const stopsJson = document.getElementById('stopsJson');
const valAlert  = document.getElementById('valAlert');

const addrInput  = document.getElementById('addrInput');
const suggestBox = document.getElementById('suggestList');
const btnAddPrev = document.getElementById('btnAddPreview');
const btnMyLoc   = document.getElementById('btnUseMyLoc');

const toastEl = document.getElementById('toastx');
let toastTimer = null;

let map, markersLayer, previewMarker=null, previewData=null;

/* ===== Utils ===== */
const debounce = (fn,ms)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); } };
const fmt = (n,d=6)=>Number(n).toFixed(d);
const fmtLatLng = (lat,lng)=>`(${fmt(lat,5)}, ${fmt(lng,5)})`;
const isNum = (n)=> typeof n === 'number' && !Number.isNaN(n) && Number.isFinite(n);

/** Normaliza string: sin acentos, sin apostrofes raros, espacios limpios */
function norm(s){
  s = (s || '').toString().trim();
  if (!s) return '';
  // quitar acentos (sin iconv raro)
  s = s.normalize('NFD').replace(/[\u0300-\u036f]/g,'');
  // quitar comillas raras/apostrofes sueltos
  s = s.replace(/[’'`"]/g,'');
  // espacios
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
  if (!/mex/i.test(q)) q += ', Mexico';
  return q;
}

function toast(msg, ms=2600){
  toastEl.innerHTML = msg;
  toastEl.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(()=> toastEl.classList.remove('show'), ms);
}

/* ===== Leaflet ===== */
map = L.map('mapPick', { zoomSnap:0.5 }).setView([23.6345,-102.5528], 5); // centro MX
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{ attribution:'© OpenStreetMap' }).addTo(map);
markersLayer = L.layerGroup().addTo(map);

function setPreview(lat,lng,label){
  if (previewMarker){ markersLayer.removeLayer(previewMarker); previewMarker=null; }
  previewMarker = L.marker([lat,lng]).addTo(markersLayer);
  previewMarker.bindTooltip(`<div class="preview-tip">${label || 'Previsualización'}<br>${fmtLatLng(lat,lng)}</div>`,{permanent:true,direction:'top',offset:[0,-8]}).openTooltip();
  map.flyTo([lat,lng], 15, {duration:.45});
  btnAddPrev.disabled = false;
}

/* Click en mapa -> reverse */
map.on('click', async (e)=>{
  const {lat,lng} = e.latlng;
  btnAddPrev.disabled = true;
  try{
    const r = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&accept-language=es&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,{headers:{'Accept':'application/json'}});
    const j = await r.json();
    const label = j?.display_name || 'Punto manual';
    addrInput.value = label;
    previewData = {lat,lng,address:label,name:''};
    setPreview(lat,lng,'Previsualización');
  }catch{
    previewData = {lat,lng,address:'',name:''};
    setPreview(lat,lng,'Previsualización');
  }
});

/* ===== Búsqueda (sólo México) ===== */
const queryNominatim = debounce(async (q)=>{
  q=q.trim();
  suggestBox.style.display='none';
  suggestBox.innerHTML='';
  if (!q || q.length<3) return;

  const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=6&accept-language=es&countrycodes=mx&q=${encodeURIComponent(q)}`;
  try{
    const res = await fetch(url, {headers:{'Accept':'application/json'}});
    const items = await res.json();
    if (!Array.isArray(items) || !items.length){
      suggestBox.innerHTML = `<div class="s-empty">Sin resultados en México</div>`;
      suggestBox.style.display = 'block';
      return;
    }
    items.forEach((it)=>{
      const div = document.createElement('div');
      div.className = 's-item';
      div.textContent = it.display_name;
      div.addEventListener('click', ()=>{
        addrInput.value = it.display_name;
        previewData = { lat:Number(it.lat), lng:Number(it.lon), address: it.display_name, name:'' };
        setPreview(previewData.lat, previewData.lng, 'Previsualización');
        suggestBox.style.display = 'none';
      });
      suggestBox.appendChild(div);
    });
    suggestBox.style.display = 'block';
  }catch{
    suggestBox.innerHTML = `<div class="s-empty">Error consultando geocodificador</div>`;
    suggestBox.style.display = 'block';
  }
}, 350);

addrInput.addEventListener('input', ()=> queryNominatim(addrInput.value));
addrInput.addEventListener('focus', ()=>{ if (suggestBox.children.length) suggestBox.style.display='block'; });
document.addEventListener('click', (e)=>{ if (!e.target.closest('.addr-wrap')) suggestBox.style.display='none'; });
addrInput.addEventListener('keydown', async (e)=>{
  if (e.key==='Enter'){
    e.preventDefault();
    await queryNominatim(addrInput.value);
    const first = suggestBox.querySelector('.s-item');
    if (first){ first.click(); }
  }
});

/* Mi ubicación */
btnMyLoc.addEventListener('click', ()=>{
  if (!navigator.geolocation){ alert('Tu dispositivo no soporta GPS'); return; }
  btnAddPrev.disabled = true;
  navigator.geolocation.getCurrentPosition(async (p)=>{
    const lat=p.coords.latitude, lng=p.coords.longitude;
    try{
      const r=await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&accept-language=es&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,{headers:{'Accept':'application/json'}});
      const j=await r.json();
      const label=j?.display_name || 'Mi ubicación';
      addrInput.value = label;
      previewData = {lat,lng,address:label,name:''};
      setPreview(lat,lng,'Mi ubicación');
    }catch{
      previewData = {lat,lng,address:'',name:''};
      setPreview(lat,lng,'Mi ubicación');
    }
  }, ()=>alert('No fue posible obtener tu ubicación'), {enableHighAccuracy:true,timeout:12000,maximumAge:5000});
});

/* Lista seleccionada */
function renderPicked(){
  pickedEl.innerHTML='';
  picked.forEach((p,i)=>{
    const row=document.createElement('li');
    row.className='rowx';
    row.dataset.index=i;
    row.innerHTML = `
      <div class="d-flex align-items-center gap-2">
        <span class="badge-no">#${i+1}</span>
        <div>
          <div class="fw-bold">${p.name || p.address || '(sin nombre)'}</div>
          <div class="text-muted small">
            Lat: ${fmt(p.lat)} · Lng: ${fmt(p.lng)}
            ${p.provider_id ? ' · Provider #' + p.provider_id : ''}
          </div>
          ${p.address ? `<div class="text-muted small">${p.address}</div>` : ''}
        </div>
      </div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline" data-edit="${i}" title="Editar"><i class="bi bi-pencil"></i></button>
        <button type="button" class="btn btn-sm btn-ghost" data-remove="${i}" title="Eliminar"><i class="bi bi-trash3"></i></button>
      </div>`;
    pickedEl.appendChild(row);
  });
  stopsJson.value = JSON.stringify(picked);
}

pickedEl.addEventListener('click',(e)=>{
  const rm = e.target.closest('[data-remove]'); const ed = e.target.closest('[data-edit]');
  if (rm){ picked.splice(+rm.dataset.remove,1); renderPicked(); }
  if (ed){
    const idx = +ed.dataset.edit;
    const name = prompt('Nombre del punto:', picked[idx].name || picked[idx].address || '');
    if (name !== null){ picked[idx].name = name.trim(); renderPicked(); }
  }
});

new Sortable(pickedEl,{animation:150,ghostClass:'ghost',
  onEnd:(evt)=>{ const [m]=picked.splice(evt.oldIndex,1); picked.splice(evt.newIndex,0,m); renderPicked(); }});

/* ===== Geocode helper (providers sin lat/lng) =====
   1) intento estructurado: street/city/state/postalcode/country
   2) fallback: q armado
*/
async function geocodeMxFromParts(parts){
  const calle   = norm(parts?.calle);
  const colonia = norm(parts?.colonia);
  const ciudad  = norm(parts?.ciudad);
  const estado  = norm(parts?.estado);
  const cp      = norm(parts?.cp);

  const street = joinParts([calle, colonia]); // street = calle + colonia (funciona mejor que meter colonia como "suburb")
  const city   = ciudad;
  const state  = estado;
  const zip    = cp;

  // 1) structured
  try{
    const qs = new URLSearchParams();
    qs.set('format','jsonv2');
    qs.set('limit','1');
    qs.set('accept-language','es');
    qs.set('countrycodes','mx');
    qs.set('addressdetails','1');
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

  // 2) fallback q (CP + ciudad + estado suele pegar)
  const q = ensureMx(joinParts([street, city, state, zip]));
  if (!q) return null;

  try{
    const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&accept-language=es&countrycodes=mx&q=${encodeURIComponent(q)}`;
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

/* Providers -> lista (con geocodificación si no hay lat/lng) */
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

    // helper: quitar por provider_id
    const removeByProviderId = () => {
      const i = picked.findIndex(p => p.provider_id === providerId);
      if (i >= 0) picked.splice(i, 1);
    };

    if (!chk.checked){
      removeByProviderId();
      renderPicked();
      return;
    }

    // si ya existe, no duplicar
    if (picked.some(p => p.provider_id === providerId)) {
      toast('Ese provider ya estaba agregado.');
      return;
    }

    let lat = Number(chk.dataset.lat);
    let lng = Number(chk.dataset.lng);

    // Si no hay coords, geocodificar por partes (NO por nombre)
    if (!isNum(lat) || !isNum(lng)) {
      chk.disabled = true;
      toast(`Geocodificando: <span class="muted">${name || ('Proveedor #' + providerId)}</span>…`, 1800);

      const geo = await geocodeMxFromParts(parts);

      chk.disabled = false;

      if (!geo) {
        chk.checked = false;
        toast(`No pude obtener coordenadas para: <span class="muted">${name || ('Proveedor #' + providerId)}</span><br><span class="muted">Revisa calle/colonia/ciudad/estado/cp o agrega desde el mapa/buscador.</span>`, 4400);
        return;
      }

      lat = geo.lat; lng = geo.lng;

      // guardar coords en el checkbox para futuras selecciones (en esta sesión)
      chk.dataset.lat = String(lat);
      chk.dataset.lng = String(lng);

      // si address venía vacío, usa display_name
      if (!address && geo.display_name) chk.dataset.address = geo.display_name;

      // acercar mapa
      try { map.flyTo([lat,lng], 13, {duration:.45}); } catch {}
    }

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

    // marker
    const m = L.marker([lat, lng]).addTo(markersLayer);
    m.bindTooltip(`${name || 'Proveedor'} ${fmtLatLng(lat,lng)}`);

    renderPicked();
  });
});

/* Confirmar preview -> lista */
btnAddPrev.addEventListener('click', ()=>{
  if (!previewData) return;
  const name = prompt('Nombre del punto (opcional):', previewData.address || 'Punto');
  picked.push({
    name: name ? name.trim() : (previewData.address || 'Punto'),
    address: previewData.address || '',
    lat: previewData.lat,
    lng: previewData.lng
  });
  const m = L.marker([previewData.lat, previewData.lng]).addTo(markersLayer);
  m.bindTooltip(`${name || 'Punto'} ${fmtLatLng(previewData.lat,previewData.lng)}`);
  if (previewMarker){ markersLayer.removeLayer(previewMarker); previewMarker=null; }
  previewData=null; btnAddPrev.disabled=true; renderPicked();
});

/* Envío */
document.getElementById('routeForm').addEventListener('submit',(e)=>{
  valAlert.classList.add('d-none');

  if (!picked.length){
    e.preventDefault();
    valAlert.classList.remove('d-none');
    valAlert.textContent='Agrega al menos un punto a la ruta.';
    return;
  }

  const bad = picked.find(p => !isNum(p.lat) || !isNum(p.lng));
  if (bad){
    e.preventDefault();
    valAlert.classList.remove('d-none');
    valAlert.textContent='Hay un punto sin coordenadas válidas. Agrega de nuevo el punto desde el mapa/buscador.';
    return;
  }

  stopsJson.value = JSON.stringify(picked);
});
</script>
@endsection
