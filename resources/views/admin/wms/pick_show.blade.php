@extends('layouts.app')

@section('title', 'WMS · Picking #' . $wave->code)

@section('content')
<div class="wms-wrap">
  <div class="topbar">
    <a href="{{ route('admin.wms.pick.entry') }}" class="btn btn-ghost">← Picking</a>

    <div class="topbar-mid">
      <div class="title">Picking · {{ $wave->code }}</div>
      <div class="sub">Guía: escanea ubicación → escanea producto → cantidad → siguiente.</div>
    </div>

    <div class="topbar-actions">
      <button class="btn btn-primary" type="button" id="btnStart">▶️ Iniciar</button>
      <button class="btn btn-ghost" type="button" id="btnFinish">✅ Finalizar</button>
    </div>
  </div>

  <div class="grid">
    <div class="card card-main">
      <div class="card-h">
        <div>
          <div class="card-tt">Paso actual</div>
          <div class="card-tx" id="stepHint">Cargando…</div>
        </div>
        <span class="chip" id="chipProgress">0%</span>
      </div>

      <div class="pick">
        <div class="pick-row">
          <div class="k">
            <div class="k-l">Ubicación esperada</div>
            <div class="k-v" id="expectedLoc">—</div>
          </div>

          <div class="k">
            <div class="k-l">Ubicación actual</div>
            <div class="k-v" id="currentLoc">—</div>
          </div>

          <div class="actions">
            <button class="btn btn-ghost" type="button" id="btnScanLoc">📍 Escanear ubicación</button>
          </div>
        </div>

        <div class="pick-row">
          <div class="prod">
            <div class="prod-tt" id="prodName">—</div>
            <div class="prod-meta" id="prodMeta">SKU/GTIN —</div>
          </div>

          <div class="qtybox">
            <div class="k-l">Cantidad</div>
            <div class="qtyrow">
              <button class="qtybtn" type="button" data-d="-1">−</button>
              <input class="qtyinp" id="qty" value="1">
              <button class="qtybtn" type="button" data-d="1">+</button>
            </div>
            <div class="hint" id="qtyHint">Pendiente: —</div>
          </div>

          <div class="actions">
            <button class="btn btn-primary" type="button" id="btnScanItem">🏷️ Escanear producto</button>
            <button class="btn btn-ghost" type="button" id="btnNext">⏭️ Siguiente</button>
          </div>
        </div>

        <div class="divider"></div>

        <div class="minirow">
          <div class="mini">
            <div class="mini-tt">Log rápido</div>
            <div class="log" id="log"></div>
          </div>

          <div class="mini">
            <div class="mini-tt">Atajos</div>
            <div class="hint">
              • Si escaneas un <b>stand</b> (padre), también se acepta.<br>
              • Si el scan falla, pega el valor manualmente en el modal.
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card card-side">
      <div class="card-h">
        <div>
          <div class="card-tt">Estado</div>
          <div class="card-tx">Optimizado para móvil, 1 mano.</div>
        </div>
      </div>

      <div class="status">
        <div class="stat">
          <div class="stat-l">Wave</div>
          <div class="stat-v">{{ $wave->code }}</div>
        </div>
        <div class="stat">
          <div class="stat-l">Status</div>
          <div class="stat-v" id="waveStatus">—</div>
        </div>
        <div class="stat">
          <div class="stat-l">Pick actual</div>
          <div class="stat-v" id="pickId">—</div>
        </div>

        <div class="bar">
          <div class="bar-in" id="barIn"></div>
        </div>
        <div class="hint" id="barHint">—</div>
      </div>
    </div>
  </div>
</div>

{{-- Modal: Scanner (reutilizable) --}}
<div class="modal" id="scanModal" aria-hidden="true">
  <div class="modal-backdrop" data-close="1"></div>
  <div class="modal-card modal-wide">
    <div class="modal-h">
      <div>
        <div class="modal-tt" id="scanTitle">Escanear</div>
        <div class="modal-tx" id="scanSub">—</div>
      </div>
      <button class="x" type="button" data-close="1">✕</button>
    </div>

    <div class="scan-grid">
      <div class="scan-box">
        <video id="video" playsinline muted></video>
        <div class="scan-overlay">
          <div class="scan-frame"></div>
          <div class="scan-hint">Centra el código dentro del cuadro</div>
        </div>
      </div>

      <div class="scan-side">
        <div class="mini">
          <div class="mini-tt">Lectura</div>
          <div class="pill" id="lastScan">—</div>

          <div class="mini-row">
            <button class="btn btn-primary" type="button" id="btnUseScan">Usar</button>
            <button class="btn btn-ghost" type="button" id="btnStopScan">Detener</button>
          </div>

          <div class="hint">Si tu equipo no detecta automático, pega el valor abajo:</div>
          <input class="inp" id="manualScan" placeholder="Pegar aquí…">
          <div class="mini-row">
            <button class="btn btn-ghost" type="button" id="btnUseManual">Usar pegado</button>
          </div>
        </div>

        <div class="mini">
          <div class="mini-tt">Tips</div>
          <div class="hint">
            • Para ubicación: QR del stand/bin.<br>
            • Para producto: GTIN o SKU del artículo.
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
    --ink:#0b1220;--muted:#64748b;--line:#e6eaf2;--line2:#eef2f7;
    --brand:#2563eb;--brand2:#1d4ed8;
    --shadow:0 18px 55px rgba(2,6,23,.08);
    --radius:18px;
  }
  .wms-wrap{max-width:1200px;margin:0 auto;padding:18px 14px 28px}
  .topbar{display:flex;gap:12px;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;margin-bottom:12px}
  .topbar-mid{flex:1 1 420px}
  .title{font-weight:950;color:var(--ink);font-size:1.05rem}
  .sub{color:var(--muted);font-size:.9rem;margin-top:2px}
  .topbar-actions{display:flex;gap:10px;flex-wrap:wrap}

  .btn{border:0;border-radius:999px;padding:10px 14px;font-weight:950;display:inline-flex;gap:8px;align-items:center;cursor:pointer;white-space:nowrap;transition:transform .12s ease, box-shadow .12s ease}
  .btn-primary{background:var(--brand);color:#eff6ff;box-shadow:0 14px 30px rgba(37,99,235,.30)}
  .btn-primary:hover{transform:translateY(-1px);box-shadow:0 18px 38px rgba(37,99,235,.34)}
  .btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink)}
  .btn-ghost:hover{transform:translateY(-1px);box-shadow:0 10px 22px rgba(2,6,23,.06)}

  .grid{display:grid;grid-template-columns:repeat(12,1fr);gap:12px}
  .card{background:#fff;border:1px solid var(--line);border-radius:22px;box-shadow:0 10px 22px rgba(2,6,23,.05);overflow:hidden}
  .card-main{grid-column:span 8}
  .card-side{grid-column:span 4}
  .card-h{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;padding:12px 14px;border-bottom:1px solid var(--line)}
  .card-tt{font-weight:950;color:var(--ink)}
  .card-tx{color:var(--muted);font-size:.85rem;margin-top:2px}
  .chip{font-size:.78rem;font-weight:950;padding:6px 10px;border-radius:999px;background:#eff6ff;color:#1e40af;border:1px solid #dbeafe;white-space:nowrap}

  .pick{padding:12px 14px}
  .pick-row{display:flex;gap:12px;align-items:flex-start;justify-content:space-between;flex-wrap:wrap}
  .k{flex:1 1 220px}
  .k-l{color:var(--muted);font-size:.8rem;font-weight:900}
  .k-v{font-weight:950;color:var(--ink);font-size:1rem;margin-top:2px}
  .actions{display:flex;gap:8px;flex-wrap:wrap}

  .prod{flex:1 1 360px}
  .prod-tt{font-weight:950;color:var(--ink);font-size:1.02rem;line-height:1.2}
  .prod-meta{color:var(--muted);font-size:.85rem;margin-top:4px}

  .qtybox{flex:0 0 220px}
  .qtyrow{display:flex;gap:8px;align-items:center;margin-top:6px}
  .qtybtn{
    width:44px;height:44px;border-radius:14px;border:1px solid var(--line);
    background:#fff;font-weight:950;font-size:1.2rem;cursor:pointer;
  }
  .qtybtn:hover{box-shadow:0 10px 22px rgba(2,6,23,.06)}
  .qtyinp{
    width:90px;height:44px;border-radius:14px;border:1px solid var(--line);
    background:#f8fafc;text-align:center;font-weight:950;font-size:1rem;
  }

  .divider{border-top:1px dashed #e5e7eb;margin:14px 0}
  .minirow{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .mini{border:1px solid var(--line2);border-radius:18px;padding:10px 10px;background:#f8fafc}
  .mini-tt{font-weight:950;color:var(--ink);margin-bottom:6px}
  .hint{color:var(--muted);font-size:.78rem}
  .log{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:.78rem;color:#0f172a;white-space:pre-wrap}

  .status{padding:12px 14px}
  .stat{display:flex;justify-content:space-between;gap:10px;padding:8px 10px;border:1px solid var(--line2);border-radius:16px;background:#f8fafc;margin-bottom:8px}
  .stat-l{color:var(--muted);font-weight:900;font-size:.8rem}
  .stat-v{color:var(--ink);font-weight:950}
  .bar{height:10px;border-radius:999px;background:#eef2ff;border:1px solid #e0e7ff;overflow:hidden;margin-top:10px}
  .bar-in{height:100%;width:0%;background:linear-gradient(90deg,#2563eb,#60a5fa)}
  .barHint{margin-top:6px}

  /* Modal scanner (igual estilo que search) */
  .modal{position:fixed;inset:0;display:none;z-index:9999}
  .modal[aria-hidden="false"]{display:block}
  .modal-backdrop{position:absolute;inset:0;background:rgba(2,6,23,.55);backdrop-filter:blur(10px)}
  .modal-card{position:relative;max-width:980px;margin:18px auto;background:#fff;border:1px solid rgba(226,232,240,.8);border-radius:22px;box-shadow:0 30px 80px rgba(2,6,23,.35);overflow:hidden}
  .modal-wide{max-width:980px}
  .modal-h{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;padding:12px 14px;border-bottom:1px solid var(--line)}
  .modal-tt{font-weight:950;color:var(--ink)}
  .modal-tx{color:var(--muted);font-size:.85rem;margin-top:2px}
  .x{border:0;background:transparent;font-size:1.2rem;cursor:pointer;padding:6px 10px;border-radius:12px}
  .x:hover{background:#f1f5f9}
  .modal-ft{display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;padding:12px 14px;border-top:1px solid var(--line)}

  .scan-grid{display:grid;grid-template-columns:1.3fr .7fr;gap:12px;padding:12px 14px}
  .scan-box{position:relative;border-radius:18px;overflow:hidden;border:1px solid var(--line);background:#0b1220;min-height:360px}
  #video{width:100%;height:100%;object-fit:cover;display:block}
  .scan-overlay{position:absolute;inset:0;display:flex;flex-direction:column;justify-content:center;align-items:center;pointer-events:none}
  .scan-frame{width:min(360px, 86%);height:min(240px, 56%);border-radius:18px;border:2px solid rgba(255,255,255,.78);box-shadow:0 0 0 999px rgba(2,6,23,.25) inset}
  .scan-hint{margin-top:10px;color:#e2e8f0;font-weight:900;font-size:.85rem}
  .scan-side{display:flex;flex-direction:column;gap:10px}
  .pill{padding:10px 12px;border-radius:14px;border:1px solid var(--line2);background:#f8fafc;font-weight:950;color:#0f172a;word-break:break-all}
  .mini-row{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
  .inp{width:100%;min-height:44px;border:1px solid var(--line);border-radius:14px;padding:10px 12px;background:#fff;color:#0f172a}

  @media (max-width: 1050px){
    .card-main{grid-column:span 12}
    .card-side{grid-column:span 12}
    .minirow{grid-template-columns:1fr}
    .scan-grid{grid-template-columns:1fr}
    .modal-card{margin:18px 10px}
  }
</style>
@endpush

@push('scripts')
<script>
  const WAVE_ID = @json($wave->id);

  const API_START = @json(route('admin.wms.pick.waves.start', ['wave' => $wave->id]));
  const API_NEXT  = @json(route('admin.wms.pick.waves.next',  ['wave' => $wave->id]));
  const API_SCAN_LOC  = @json(route('admin.wms.pick.waves.scan-location', ['wave' => $wave->id]));
  const API_SCAN_ITEM = @json(route('admin.wms.pick.waves.scan-item',     ['wave' => $wave->id]));
  const API_FINISH    = @json(route('admin.wms.pick.waves.finish',        ['wave' => $wave->id]));

  const token = @json(csrf_token());

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

  function logLine(t){
    const el = document.getElementById('log');
    if(!el) return;
    const now = new Date();
    const hh = String(now.getHours()).padStart(2,'0');
    const mm = String(now.getMinutes()).padStart(2,'0');
    el.textContent = `[${hh}:${mm}] ${t}\n` + el.textContent;
  }

  function setModal(open){
    document.getElementById('scanModal').setAttribute('aria-hidden', open ? 'false' : 'true');
  }
  document.addEventListener('click',(e)=>{
    if(e.target?.getAttribute?.('data-close')){
      setModal(false);
      stopCamera();
    }
  });

  // UI elements
  const expectedLocEl = document.getElementById('expectedLoc');
  const currentLocEl  = document.getElementById('currentLoc');
  const prodNameEl    = document.getElementById('prodName');
  const prodMetaEl    = document.getElementById('prodMeta');
  const qtyEl         = document.getElementById('qty');
  const qtyHintEl     = document.getElementById('qtyHint');
  const stepHintEl    = document.getElementById('stepHint');
  const waveStatusEl  = document.getElementById('waveStatus');
  const pickIdEl      = document.getElementById('pickId');

  const barIn = document.getElementById('barIn');
  const barHint = document.getElementById('barHint');
  const chipProgress = document.getElementById('chipProgress');

  let currentPick = null;
  let lastExpectedLocCode = null;

  async function apiGet(url){
    const res = await fetch(url, {headers:{'Accept':'application/json'}});
    return res.json();
  }
  async function apiPost(url, body){
    const res = await fetch(url, {
      method:'POST',
      headers:{
        'Accept':'application/json',
        'Content-Type':'application/json',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify(body || {})
    });
    const data = await res.json().catch(()=> ({}));
    if(!res.ok) data._http_error = true;
    return data;
  }

  function renderPick(pick, wave){
    currentPick = pick;

    if(wave?.status !== undefined){
      waveStatusEl.textContent = String(wave.status);
    }

    pickIdEl.textContent = pick?.pick_item_id ? ('#' + pick.pick_item_id) : '—';

    const exp = pick?.expected_location?.code || '—';
    expectedLocEl.textContent = exp;
    lastExpectedLocCode = (pick?.expected_location?.code || null);

    if(wave?.current_location?.code){
      currentLocEl.textContent = wave.current_location.code;
    }

    const item = pick?.item || {};
    prodNameEl.textContent = item.name || '—';

    const bits = [];
    if(item.sku) bits.push('SKU: ' + item.sku);
    if(item.meli_gtin) bits.push('GTIN: ' + item.meli_gtin);
    prodMetaEl.textContent = bits.join(' · ') || '—';

    const remaining = Number(pick.remaining_qty || 0);
    qtyHintEl.textContent = 'Pendiente: ' + remaining;

    // qty default = min(1, remaining)
    const q = Math.max(1, Math.min(1, remaining || 1));
    qtyEl.value = String(q);

    stepHintEl.textContent = `Ve a ${exp} y escanea ubicación. Luego escanea el producto y confirma la cantidad.`;
  }

  // Progress (simple: usamos remaining y requested del pick actual + asumimos % por contadores)
  // Para % real necesitarías un endpoint de progreso. Aquí hacemos aproximación con picks completados via API_NEXT info.
  function setProgress(done, total){
    if(!total || total <= 0){
      chipProgress.textContent = '—';
      barIn.style.width = '0%';
      barHint.textContent = '—';
      return;
    }
    const pct = Math.round((done/total)*100);
    chipProgress.textContent = pct + '%';
    barIn.style.width = pct + '%';
    barHint.textContent = `${done} / ${total} completos`;
  }

  async function loadNext(){
    const data = await apiGet(API_NEXT);
    if(!data.ok){
      logLine('Error cargando next: ' + (data.error || '—'));
      beep(false); vibrate(90);
      return;
    }

    if(data.done){
      stepHintEl.textContent = data.message || 'Listo. No hay más por surtir.';
      prodNameEl.textContent = '—';
      prodMetaEl.textContent = '—';
      expectedLocEl.textContent = '—';
      pickIdEl.textContent = '—';
      beep(true); vibrate(25);
      return;
    }

    // wave partial comes in data.wave and pick in data.pick
    if(data.wave?.current_location?.code){
      currentLocEl.textContent = data.wave.current_location.code;
    }
    waveStatusEl.textContent = String(data.wave?.status ?? '—');

    renderPick(data.pick, data.wave);

    // progreso “dummy”: si quieres real luego lo hacemos con endpoint
    // aquí solo mostramos 0% si no tenemos data; mantenemos visual.
    setProgress(0, 1);

    logLine('Siguiente pick cargado.');
  }

  // Buttons
  document.getElementById('btnStart')?.addEventListener('click', async ()=>{
    const data = await apiPost(API_START, {});
    if(!data.ok){
      alert(data.error || 'No se pudo iniciar.');
      beep(false); vibrate(90);
      return;
    }
    logLine('Wave iniciada.');
    beep(true); vibrate(30);
    await loadNext();
  });

  document.getElementById('btnNext')?.addEventListener('click', async ()=>{
    await loadNext();
  });

  document.getElementById('btnFinish')?.addEventListener('click', async ()=>{
    const data = await apiPost(API_FINISH, {});
    if(!data.ok){
      alert(data.error || 'No se pudo finalizar.');
      beep(false); vibrate(90);
      return;
    }
    logLine('Wave finalizada.');
    beep(true); vibrate(30);
    alert('Listo: wave finalizada ✅');
  });

  // Qty controls
  document.querySelectorAll('[data-d]')?.forEach(b=>{
    b.addEventListener('click', ()=>{
      const d = parseInt(b.getAttribute('data-d'),10) || 0;
      let v = parseInt(qtyEl.value||'1',10);
      if(isNaN(v)) v = 1;
      v = Math.max(1, v + d);
      qtyEl.value = String(v);
    });
  });
  qtyEl?.addEventListener('input', ()=>{
    let v = parseInt(qtyEl.value||'1',10);
    if(isNaN(v) || v < 1) v = 1;
    qtyEl.value = String(v);
  });

  // -------------------------
  // Scanner modal logic
  // -------------------------
  const scanTitle = document.getElementById('scanTitle');
  const scanSub   = document.getElementById('scanSub');
  const lastScan  = document.getElementById('lastScan');
  const manualScan= document.getElementById('manualScan');

  const video = document.getElementById('video');
  let stream = null;
  let scanning = false;
  let lastValue = '';
  let scanContext = 'loc'; // loc|item

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
    if(!('BarcodeDetector' in window)) return;
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

  function openScan(ctx){
    scanContext = ctx;
    scanTitle.textContent = ctx === 'loc' ? 'Escanear ubicación' : 'Escanear producto';
    scanSub.textContent = ctx === 'loc'
      ? 'Escanea el QR/código del stand/bin.'
      : 'Escanea el GTIN o SKU del producto.';
    lastScan.textContent = '—';
    manualScan.value = '';
    lastValue = '';
    setModal(true);
    startCamera().then(loopScan).catch(()=>{ alert('No se pudo abrir la cámara.'); stopCamera(); setModal(false); });
  }

  document.getElementById('btnScanLoc')?.addEventListener('click', ()=> openScan('loc'));
  document.getElementById('btnScanItem')?.addEventListener('click', ()=> openScan('item'));

  document.getElementById('btnStopScan')?.addEventListener('click', ()=>{ stopCamera(); beep(true); });

  async function useValue(val){
    val = (val||'').trim();
    if(!val || val==='—'){ beep(false); vibrate(80); return; }

    if(scanContext === 'loc'){
      const data = await apiPost(API_SCAN_LOC, {code: val});
      if(!data.ok){
        alert(data.error || 'Ubicación incorrecta.');
        logLine('Ubicación incorrecta: ' + val);
        beep(false); vibrate(90);
        return;
      }
      // Esperamos que API regrese current_location y pick
      if(data.current_location?.code){
        currentLocEl.textContent = data.current_location.code;
      }
      if(data.pick){
        renderPick(data.pick, {current_location: data.current_location});
      }
      logLine('Ubicación OK: ' + val);
      beep(true); vibrate(30);
      setModal(false);
      stopCamera();
      return;
    }

    // item
    const qty = parseInt(qtyEl.value||'1',10) || 1;
    const data = await apiPost(API_SCAN_ITEM, {barcode_or_sku: val, qty: qty});
    if(!data.ok){
      alert(data.error || 'Producto incorrecto / stock insuficiente.');
      logLine('Error producto: ' + val + ' · ' + (data.error || '—'));
      beep(false); vibrate(90);
      return;
    }
    if(data.pick){
      renderPick(data.pick, {current_location: {code: currentLocEl.textContent}});
      logLine(`Pick aplicado: ${qty} u.`);
      beep(true); vibrate(30);
      setModal(false);
      stopCamera();

      // auto-next si el pick quedó completo
      if(Number(data.pick.remaining_qty||0) <= 0){
        await loadNext();
      }
    }
  }

  document.getElementById('btnUseScan')?.addEventListener('click', ()=> useValue(lastScan.textContent));
  document.getElementById('btnUseManual')?.addEventListener('click', ()=> useValue(manualScan.value));

  // init load
  (async function init(){
    logLine('Cargando pick…');
    await loadNext();
  })();
</script>
@endpush
