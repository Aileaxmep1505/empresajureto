@extends('layouts.app')

@section('title', 'WMS · Buscar producto')

@section('content')
<div class="wms-wrap">
  <div class="topbar">
    <a href="{{ route('admin.wms.home') }}" class="btn btn-ghost">← WMS</a>

    <div class="topbar-mid">
      <div class="title">Buscar producto</div>
      <div class="sub">Nombre / SKU / GTIN · Resultados con ubicación y ruta “Llévame”.</div>
    </div>

    <div class="topbar-actions">
      <button class="btn btn-ghost" type="button" id="btnOpenScanner">📷 Escanear</button>
    </div>
  </div>

  <div class="panel">
    <div class="panel-h">
      <div>
        <div class="panel-tt">Tu ubicación actual</div>
        <div class="panel-tx">Escanea un QR de stand/bin para fijar “dónde estás” y ordenar resultados por cercanía.</div>
      </div>
      <span class="chip" id="chipFrom">No definida</span>
    </div>

    <div class="row">
      <div class="field">
        <label class="lbl">Código ubicación (QR)</label>
        <div class="inprow">
          <input class="inp" id="from_code" placeholder="Ej: A-03-S2-R1-N4-B07">
          <button class="btn btn-ghost" type="button" id="btnSetFrom">Fijar</button>
          <button class="btn btn-ghost" type="button" id="btnClearFrom">Limpiar</button>
        </div>
        <div class="hint">Tip: puedes pegar el código si no tienes cámara.</div>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-h">
      <div>
        <div class="panel-tt">Buscador</div>
        <div class="panel-tx">Escribe o escanea un SKU/GTIN. Te sugerimos la mejor ubicación con stock.</div>
      </div>
      <span class="chip chip-soft" id="chipCount">0 resultados</span>
    </div>

    <div class="row">
      <div class="field" style="flex:1 1 420px">
        <label class="lbl">Buscar</label>
        <div class="inprow">
          <input class="inp" id="q" placeholder="Ej: Bic azul 0.7 / SKU / GTIN">
          <button class="btn btn-primary" type="button" id="btnSearch">
            <span class="spin" id="spinSearch"></span>
            <span>Buscar</span>
          </button>
        </div>
        <div class="hint">Enter también funciona. Resultado muestra stock total y ubicaciones.</div>
      </div>
    </div>

    <div class="results" id="results"></div>
  </div>
</div>

{{-- Modal: Ruta “Llévame” --}}
<div class="modal" id="navModal" aria-hidden="true">
  <div class="modal-backdrop" data-close="1"></div>
  <div class="modal-card">
    <div class="modal-h">
      <div>
        <div class="modal-tt">Ruta “Llévame”</div>
        <div class="modal-tx" id="navSubtitle">—</div>
      </div>
      <button class="x" type="button" data-close="1">✕</button>
    </div>

    <div class="steps" id="navSteps"></div>

    <div class="modal-ft">
      <button class="btn btn-ghost" type="button" data-close="1">Cerrar</button>
      <button class="btn btn-primary" type="button" id="btnMarkHere">✅ Ya estoy aquí</button>
    </div>
  </div>
</div>

{{-- Modal: Scanner cámara (BarcodeDetector si existe) --}}
<div class="modal" id="scanModal" aria-hidden="true">
  <div class="modal-backdrop" data-close="1"></div>
  <div class="modal-card modal-wide">
    <div class="modal-h">
      <div>
        <div class="modal-tt">Escanear</div>
        <div class="modal-tx">Apunta al QR de ubicación o al código del producto.</div>
      </div>
      <button class="x" type="button" data-close="1">✕</button>
    </div>

    <div class="scan-grid">
      <div class="scan-box">
        <video id="video" playsinline muted></video>
        <div class="scan-overlay">
          <div class="scan-frame"></div>
          <div class="scan-hint">Centra el QR/código dentro del cuadro</div>
        </div>
      </div>

      <div class="scan-side">
        <div class="mini">
          <div class="mini-tt">Modo</div>
          <div class="mini-row">
            <button class="btn btn-ghost btn-sm" type="button" id="scanModeLoc">📍 Ubicación</button>
            <button class="btn btn-ghost btn-sm" type="button" id="scanModeItem">🏷️ Producto</button>
          </div>
          <div class="hint">Ubicación llena “Tu ubicación actual”. Producto llena el buscador.</div>
        </div>

        <div class="mini">
          <div class="mini-tt">Última lectura</div>
          <div class="pill" id="lastScan">—</div>
          <div class="mini-row">
            <button class="btn btn-primary" type="button" id="btnUseScan">Usar</button>
            <button class="btn btn-ghost" type="button" id="btnStopScan">Detener</button>
          </div>
        </div>

        <div class="mini">
          <div class="mini-tt">Fallback</div>
          <div class="hint">
            Si tu dispositivo no soporta detección automática, pega el valor manualmente.
          </div>
        </div>
      </div>
    </div>

    <div class="modal-ft">
      <button class="btn btn-ghost" type="button" data-close="1">Cerrar</button>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  :root{
    --bg:#f6f8fc;
    --card:#fff;
    --ink:#0b1220;
    --muted:#64748b;
    --line:#e6eaf2;
    --line2:#eef2f7;
    --brand:#2563eb;
    --brand2:#1d4ed8;
    --ok:#16a34a;
    --warn:#f59e0b;
    --bad:#ef4444;
    --shadow:0 18px 55px rgba(2,6,23,.08);
    --radius:18px;
  }

  .wms-wrap{max-width:1100px;margin:0 auto;padding:18px 14px 28px}
  .topbar{display:flex;gap:12px;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;margin-bottom:12px}
  .topbar-mid{flex:1 1 360px}
  .title{font-weight:900;color:var(--ink);font-size:1.05rem}
  .sub{color:var(--muted);font-size:.9rem;margin-top:2px}
  .topbar-actions{display:flex;gap:10px;flex-wrap:wrap}

  .panel{
    background:var(--card);
    border:1px solid var(--line);
    border-radius:var(--radius);
    padding:12px 12px;
    box-shadow:0 10px 22px rgba(2,6,23,.05);
    margin-bottom:12px;
  }
  .panel-h{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;margin-bottom:10px}
  .panel-tt{font-weight:900;color:var(--ink)}
  .panel-tx{color:var(--muted);font-size:.88rem;margin-top:2px}
  .chip{
    font-size:.78rem;font-weight:800;
    padding:6px 10px;border-radius:999px;
    background:#dcfce7;color:#166534;border:1px solid #bbf7d0;
    white-space:nowrap;
  }
  .chip-soft{
    background:#eff6ff;color:#1e40af;border:1px solid #dbeafe;
  }

  .row{display:flex;gap:10px;flex-wrap:wrap}
  .field{flex:1 1 320px}
  .lbl{display:block;font-weight:900;color:var(--ink);font-size:.86rem;margin-bottom:6px}
  .inp{
    width:100%;min-height:44px;
    border:1px solid var(--line);border-radius:14px;
    padding:10px 12px;background:#f8fafc;color:#0f172a;
    transition:border-color .14s ease, box-shadow .14s ease, transform .12s ease, background .14s ease;
  }
  .inp:focus{outline:none;border-color:#93c5fd;box-shadow:0 0 0 3px rgba(147,197,253,.35);background:#fff}
  .inprow{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
  .hint{color:var(--muted);font-size:.78rem;margin-top:6px}

  .btn{
    border:0;border-radius:999px;padding:10px 14px;font-weight:900;
    display:inline-flex;gap:8px;align-items:center;cursor:pointer;
    transition:transform .12s ease, box-shadow .12s ease, background .15s ease, border-color .15s ease;
    white-space:nowrap;
  }
  .btn-primary{background:var(--brand);color:#eff6ff;box-shadow:0 14px 30px rgba(37,99,235,.30)}
  .btn-primary:hover{transform:translateY(-1px);box-shadow:0 18px 38px rgba(37,99,235,.34)}
  .btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink)}
  .btn-ghost:hover{transform:translateY(-1px);box-shadow:0 10px 22px rgba(2,6,23,.06)}
  .btn-sm{padding:8px 10px;font-size:.82rem}

  .spin{
    width:16px;height:16px;border-radius:999px;
    border:2px solid rgba(255,255,255,.4);
    border-top-color:#fff;display:none;
    animation:sp .8s linear infinite;
  }
  @keyframes sp{to{transform:rotate(360deg)}}

  .results{display:flex;flex-direction:column;gap:10px;margin-top:10px}
  .r{
    border:1px solid var(--line);border-radius:18px;
    padding:12px 12px;background:#fff;
    transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease;
  }
  .r:hover{transform:translateY(-1px);box-shadow:0 16px 40px rgba(2,6,23,.08);border-color:#dbeafe}
  .r-top{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;flex-wrap:wrap}
  .r-name{font-weight:950;color:var(--ink);line-height:1.2}
  .r-meta{color:var(--muted);font-size:.82rem;margin-top:4px}
  .r-actions{display:flex;gap:8px;flex-wrap:wrap}
  .badge{
    font-size:.75rem;font-weight:900;padding:5px 9px;border-radius:999px;white-space:nowrap;
    border:1px solid var(--line2);background:#f8fafc;color:#0f172a;
  }
  .badge-ok{background:#dcfce7;border-color:#bbf7d0;color:#166534}
  .badge-warn{background:#fef3c7;border-color:#fde68a;color:#92400e}
  .badge-bad{background:#fee2e2;border-color:#fecaca;color:#991b1b}

  .locs{margin-top:10px;display:flex;gap:8px;flex-wrap:wrap}
  .locchip{
    display:inline-flex;gap:6px;align-items:center;
    padding:6px 10px;border-radius:999px;border:1px solid var(--line2);
    background:#fff;font-size:.78rem;font-weight:900;color:#0f172a;
  }
  .locchip small{font-weight:900;color:var(--muted)}
  .locchip strong{color:var(--brand2)}

  /* Modales */
  .modal{position:fixed;inset:0;display:none;z-index:9999}
  .modal[aria-hidden="false"]{display:block}
  .modal-backdrop{position:absolute;inset:0;background:rgba(2,6,23,.55);backdrop-filter:blur(10px)}
  .modal-card{
    position:relative;
    max-width:760px;margin:40px auto;
    background:#fff;border:1px solid rgba(226,232,240,.8);
    border-radius:22px;box-shadow:0 30px 80px rgba(2,6,23,.35);
    overflow:hidden;
  }
  .modal-wide{max-width:980px}
  .modal-h{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;padding:12px 14px;border-bottom:1px solid var(--line)}
  .modal-tt{font-weight:950;color:var(--ink)}
  .modal-tx{color:var(--muted);font-size:.85rem;margin-top:2px}
  .x{border:0;background:transparent;font-size:1.2rem;cursor:pointer;padding:6px 10px;border-radius:12px}
  .x:hover{background:#f1f5f9}
  .modal-ft{display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;padding:12px 14px;border-top:1px solid var(--line)}
  .steps{padding:12px 14px;display:flex;flex-direction:column;gap:8px}
  .step{
    display:flex;gap:10px;align-items:flex-start;
    padding:10px 12px;border:1px solid var(--line2);border-radius:16px;background:#f8fafc;
  }
  .dot{
    width:26px;height:26px;border-radius:999px;display:flex;align-items:center;justify-content:center;
    background:#dbeafe;color:#1e40af;font-weight:950;flex:0 0 auto;
  }
  .step .tx{font-weight:900;color:#0f172a}
  .step .sm{color:var(--muted);font-size:.82rem;margin-top:2px}

  /* Scanner */
  .scan-grid{display:grid;grid-template-columns:1.3fr .7fr;gap:12px;padding:12px 14px}
  .scan-box{
    position:relative;border-radius:18px;overflow:hidden;border:1px solid var(--line);
    background:#0b1220;
    min-height:360px;
  }
  #video{width:100%;height:100%;object-fit:cover;display:block}
  .scan-overlay{position:absolute;inset:0;display:flex;flex-direction:column;justify-content:center;align-items:center;pointer-events:none}
  .scan-frame{
    width:min(360px, 86%);height:min(240px, 56%);
    border-radius:18px;border:2px solid rgba(255,255,255,.78);
    box-shadow:0 0 0 999px rgba(2,6,23,.25) inset;
  }
  .scan-hint{margin-top:10px;color:#e2e8f0;font-weight:800;font-size:.85rem}
  .scan-side{display:flex;flex-direction:column;gap:10px}
  .mini{
    border:1px solid var(--line);border-radius:18px;padding:10px 10px;background:#fff;
  }
  .mini-tt{font-weight:950;color:var(--ink);margin-bottom:6px}
  .mini-row{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
  .pill{
    padding:10px 12px;border-radius:14px;border:1px solid var(--line2);
    background:#f8fafc;font-weight:950;color:#0f172a;word-break:break-all;
  }

  @media (max-width: 980px){
    .scan-grid{grid-template-columns:1fr}
    .modal-card{margin:18px 10px}
  }
</style>
@endpush

@push('scripts')
<script>
  // ----------------------------
  // Config
  // ----------------------------
  const API_SEARCH = @json(route('admin.wms.search.products'));
  const API_NAV    = @json(route('admin.wms.nav'));
  const API_LOC_SCAN = @json(route('admin.wms.locations.scan'));

  const LS_FROM = 'wms_from_code';

  // ----------------------------
  // Helpers UX
  // ----------------------------
  function beep(ok=true){
    try{
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const o = ctx.createOscillator();
      const g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.value = ok ? 880 : 220;
      g.gain.value = 0.06;
      o.start();
      setTimeout(()=>{o.stop();ctx.close();}, ok ? 80 : 140);
    }catch(e){}
  }
  function vibrate(ms=40){ try{ if(navigator.vibrate) navigator.vibrate(ms);}catch(e){} }

  function setModal(id, open){
    const m = document.getElementById(id);
    if(!m) return;
    m.setAttribute('aria-hidden', open ? 'false' : 'true');
  }
  document.addEventListener('click', (e)=>{
    const close = e.target?.getAttribute?.('data-close');
    if(close){
      setModal('navModal', false);
      setModal('scanModal', false);
      stopCamera();
    }
  });

  function escapeHtml(str){
    if(str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
      .replace(/"/g,"&quot;").replace(/'/g,"&#039;");
  }

  function qtyBadge(total){
    total = Number(total||0);
    if(total <= 0) return `<span class="badge badge-bad">Sin stock</span>`;
    if(total <= 3) return `<span class="badge badge-warn">Bajo (${total})</span>`;
    return `<span class="badge badge-ok">Stock (${total})</span>`;
  }

  // ----------------------------
  // State: from_code
  // ----------------------------
  const chipFrom = document.getElementById('chipFrom');
  const fromInp = document.getElementById('from_code');

  function loadFrom(){
    const v = localStorage.getItem(LS_FROM) || '';
    if(fromInp) fromInp.value = v;
    chipFrom.textContent = v ? v : 'No definida';
    chipFrom.classList.toggle('chip-soft', !v);
  }
  function saveFrom(v){
    v = (v||'').trim();
    if(v) localStorage.setItem(LS_FROM, v);
    else localStorage.removeItem(LS_FROM);
    loadFrom();
  }

  async function validateLocationCode(code){
    code = (code||'').trim();
    if(!code) return {ok:true, code:''};

    // valida contra API (por si teclearon mal)
    const url = API_LOC_SCAN + '?code=' + encodeURIComponent(code);
    const res = await fetch(url, {headers:{'Accept':'application/json'}});
    if(!res.ok){
      return {ok:false, error:'Ubicación no encontrada.'};
    }
    const data = await res.json();
    if(!data.ok) return {ok:false, error:data.error||'Ubicación inválida'};
    return {ok:true, code:data.location?.code || code};
  }

  // ----------------------------
  // Search
  // ----------------------------
  const qInp = document.getElementById('q');
  const btnSearch = document.getElementById('btnSearch');
  const spinSearch = document.getElementById('spinSearch');
  const resultsEl = document.getElementById('results');
  const chipCount = document.getElementById('chipCount');

  function setLoading(on){
    if(spinSearch) spinSearch.style.display = on ? 'inline-block' : 'none';
    if(btnSearch) btnSearch.disabled = !!on;
  }

  async function runSearch(){
    const q = (qInp.value||'').trim();
    if(!q){
      resultsEl.innerHTML = `<div class="hint">Escribe algo para buscar.</div>`;
      chipCount.textContent = '0 resultados';
      return;
    }

    const from = (localStorage.getItem(LS_FROM) || '').trim();

    setLoading(true);
    resultsEl.innerHTML = `<div class="hint">Buscando…</div>`;

    const url = new URL(API_SEARCH, window.location.origin);
    url.searchParams.set('q', q);
    if(from) url.searchParams.set('from_code', from);
    url.searchParams.set('limit', '20');

    try{
      const res = await fetch(url.toString(), {headers:{'Accept':'application/json'}});
      const data = await res.json();

      if(!data.ok){
        resultsEl.innerHTML = `<div class="hint">Error: ${escapeHtml(data.error||'No se pudo buscar')}</div>`;
        chipCount.textContent = '0 resultados';
        beep(false); vibrate(80);
        return;
      }

      const list = Array.isArray(data.results) ? data.results : [];
      chipCount.textContent = list.length + ' resultados';

      if(!list.length){
        resultsEl.innerHTML = `<div class="hint">Sin resultados.</div>`;
        beep(false);
        return;
      }

      resultsEl.innerHTML = list.map(r => {
        const rec = r.recommended_location;
        const nav = r.nav;
        const recCode = rec?.code ? escapeHtml(rec.code) : '—';

        const locs = (r.locations||[]).slice(0, 6).map(l =>
          `<span class="locchip"><strong>${escapeHtml(l.code||'—')}</strong> <small>x${Number(l.qty||0)}</small></span>`
        ).join('');

        const metaBits = [];
        if(r.sku) metaBits.push('SKU: ' + escapeHtml(r.sku));
        if(r.meli_gtin) metaBits.push('GTIN: ' + escapeHtml(r.meli_gtin));
        if(r.primary_location?.code) metaBits.push('Principal: ' + escapeHtml(r.primary_location.code));

        return `
          <div class="r">
            <div class="r-top">
              <div>
                <div class="r-name">${escapeHtml(r.name||'—')}</div>
                <div class="r-meta">${metaBits.join(' · ') || '—'}</div>
              </div>
              <div class="r-actions">
                ${qtyBadge(r.total_qty)}
                ${rec?.code ? `<span class="badge">Sugerida: <b>${recCode}</b></span>` : `<span class="badge">Sin sugerencia</span>`}
                ${nav?.steps?.length ? `<button class="btn btn-primary" type="button" data-nav='${escapeHtml(JSON.stringify(nav))}'>🧭 Llévame</button>` : ``}
              </div>
            </div>

            <div class="locs">${locs || `<span class="hint">Sin ubicaciones con stock.</span>`}</div>
          </div>
        `;
      }).join('');

      // bind nav buttons
      resultsEl.querySelectorAll('button[data-nav]').forEach(btn=>{
        btn.addEventListener('click', ()=>{
          try{
            const nav = JSON.parse(btn.getAttribute('data-nav'));
            openNav(nav);
          }catch(e){}
        });
      });

      beep(true); vibrate(30);

    }catch(e){
      resultsEl.innerHTML = `<div class="hint">Error de conexión.</div>`;
      beep(false); vibrate(80);
    }finally{
      setLoading(false);
    }
  }

  // Nav modal
  let currentNav = null;
  function openNav(nav){
    currentNav = nav;
    const sub = document.getElementById('navSubtitle');
    const steps = document.getElementById('navSteps');

    sub.textContent = `${nav.from?.code || '—'} → ${nav.to?.code || '—'}`;
    steps.innerHTML = (nav.steps||[]).map((s,i)=>`
      <div class="step">
        <div class="dot">${i+1}</div>
        <div>
          <div class="tx">${escapeHtml(s)}</div>
          <div class="sm">Paso ${i+1} de ${(nav.steps||[]).length}</div>
        </div>
      </div>
    `).join('');

    setModal('navModal', true);
  }

  document.getElementById('btnMarkHere')?.addEventListener('click', ()=>{
    if(currentNav?.to?.code){
      saveFrom(currentNav.to.code);
      setModal('navModal', false);
      beep(true); vibrate(35);
    }
  });

  // set/clear from
  document.getElementById('btnSetFrom')?.addEventListener('click', async ()=>{
    const val = (fromInp.value||'').trim();
    if(!val){ saveFrom(''); return; }

    const v = await validateLocationCode(val);
    if(!v.ok){
      chipFrom.textContent = 'Inválida';
      chipFrom.classList.add('chip-soft');
      beep(false); vibrate(90);
      alert(v.error || 'Ubicación inválida');
      return;
    }
    saveFrom(v.code);
    beep(true); vibrate(30);
  });

  document.getElementById('btnClearFrom')?.addEventListener('click', ()=>{
    saveFrom('');
    beep(true); vibrate(20);
  });

  // search events
  btnSearch?.addEventListener('click', runSearch);
  qInp?.addEventListener('keydown', (e)=>{ if(e.key === 'Enter') runSearch(); });

  // ----------------------------
  // Scanner (BarcodeDetector)
  // ----------------------------
  const btnOpenScanner = document.getElementById('btnOpenScanner');
  const scanModal = document.getElementById('scanModal');
  const video = document.getElementById('video');
  const lastScan = document.getElementById('lastScan');
  const btnUseScan = document.getElementById('btnUseScan');
  const btnStopScan = document.getElementById('btnStopScan');

  let scanMode = 'loc'; // loc|item
  let stream = null;
  let scanning = false;
  let lastValue = '';

  function setScanMode(m){
    scanMode = m;
    document.getElementById('scanModeLoc')?.classList.toggle('btn-primary', m==='loc');
    document.getElementById('scanModeLoc')?.classList.toggle('btn-ghost', m!=='loc');
    document.getElementById('scanModeItem')?.classList.toggle('btn-primary', m==='item');
    document.getElementById('scanModeItem')?.classList.toggle('btn-ghost', m!=='item');
  }
  document.getElementById('scanModeLoc')?.addEventListener('click', ()=>setScanMode('loc'));
  document.getElementById('scanModeItem')?.addEventListener('click', ()=>setScanMode('item'));

  async function startCamera(){
    if(!navigator.mediaDevices?.getUserMedia){
      alert('Tu navegador no soporta cámara.');
      return;
    }
    stream = await navigator.mediaDevices.getUserMedia({video:{facingMode:'environment'}});
    video.srcObject = stream;
    await video.play();
  }
  function stopCamera(){
    scanning = false;
    if(stream){
      stream.getTracks().forEach(t=>t.stop());
      stream = null;
    }
    if(video) video.srcObject = null;
  }

  async function loopScan(){
    if(!('BarcodeDetector' in window)){
      // sin detector nativo; deja el modal como visor de cámara
      return;
    }
    const detector = new BarcodeDetector({formats:['qr_code','ean_13','ean_8','code_128','upc_a','upc_e']});
    scanning = true;

    while(scanning){
      try{
        const codes = await detector.detect(video);
        if(codes && codes.length){
          const val = codes[0].rawValue || '';
          if(val && val !== lastValue){
            lastValue = val;
            lastScan.textContent = val;
            beep(true); vibrate(25);
          }
        }
      }catch(e){}
      await new Promise(r=>setTimeout(r, 120));
    }
  }

  btnOpenScanner?.addEventListener('click', async ()=>{
    setModal('scanModal', true);
    setScanMode('loc');
    lastValue = '';
    lastScan.textContent = '—';
    try{
      await startCamera();
      loopScan();
    }catch(e){
      alert('No se pudo abrir la cámara.');
      stopCamera();
    }
  });

  btnStopScan?.addEventListener('click', ()=>{
    stopCamera();
    beep(true);
  });

  btnUseScan?.addEventListener('click', async ()=>{
    const val = (lastScan.textContent || '').trim();
    if(!val || val === '—'){
      beep(false); vibrate(80);
      return;
    }

    if(scanMode === 'loc'){
      const v = await validateLocationCode(val);
      if(!v.ok){
        beep(false); vibrate(90);
        alert(v.error || 'Ubicación inválida');
        return;
      }
      saveFrom(v.code);
      setModal('scanModal', false);
      stopCamera();
      beep(true); vibrate(30);
      return;
    }

    // item mode: llena buscador
    qInp.value = val;
    setModal('scanModal', false);
    stopCamera();
    beep(true); vibrate(30);
    runSearch();
  });

  // init
  loadFrom();
</script>
@endpush
