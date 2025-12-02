<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Capturar documento</title>

  <style>
    :root{
      --bg:#f5f5f7;
      --surface:#ffffff;
      --surface-soft:#f9fafb;
      --ink:#111827;
      --muted:#6b7280;
      --line:#e5e7eb;

      --accent:#38bdf8;
      --accent-pastel:#e0f2fe;
      --accent-border:#bae6fd;

      --neutral-pastel:#f3f4f6;
      --neutral-border:#e5e7eb;

      --ok:#22c55e;
      --ok-pastel:#dcfce7;
      --err:#ef4444;
      --err-pastel:#fee2e2;
    }

    *{
      box-sizing:border-box;
      margin:0;
      padding:0;
      font-family:system-ui,-apple-system,"SF Pro Text","Segoe UI",sans-serif;
    }

    body{
      min-height:100vh;
      background:radial-gradient(circle at top,#e5edff 0,#f5f5f7 40%,#eef2ff 100%);
      color:var(--ink);
      padding:16px;
      display:flex;
      align-items:center;
      justify-content:center;
    }

    .shell{
      width:100%;
      max-width:720px;
    }

    /* Top bar */
    .app-bar{
      display:flex;
      align-items:center;
      justify-content:space-between;
      margin-bottom:10px;
    }
    .app-left{
      display:flex;
      flex-direction:column;
      gap:2px;
    }
    .app-title{
      font-size:.9rem;
      font-weight:600;
      letter-spacing:.08em;
      text-transform:uppercase;
      color:var(--muted);
    }
    .app-session{
      font-size:.8rem;
      color:var(--muted);
    }
    .app-pill{
      padding:4px 10px;
      border-radius:999px;
      border:1px solid var(--line);
      background:rgba(255,255,255,.9);
      font-size:.78rem;
      color:var(--muted);
    }

    /* Card */
    .card{
      background:radial-gradient(circle at top left,#ffffff,#f9fafb 60%,#f3f4f6 100%);
      border-radius:18px;
      padding:16px 16px 14px;
      border:1px solid rgba(209,213,219,.9);
      box-shadow:0 18px 40px rgba(148,163,184,.45);
      position:relative;
      overflow:hidden;
    }
    .card-inner{position:relative;z-index:1;}

    h1{
      font-size:1.1rem;
      font-weight:700;
      letter-spacing:-.01em;
      margin-bottom:2px;
    }
    .sub{
      font-size:.85rem;
      color:var(--muted);
      margin-bottom:10px;
    }

    .hr{
      border:none;
      border-top:1px solid #e5e7eb;
      margin:10px 0 12px;
    }

    /* Steps compact */
    .steps{
      display:flex;
      gap:12px;
      flex-wrap:wrap;
      margin-bottom:12px;
    }
    .step-chip{
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding:5px 9px;
      border-radius:999px;
      background:var(--surface-soft);
      border:1px solid var(--line);
      font-size:.78rem;
      color:var(--muted);
      white-space:nowrap;
    }
    .step-dot{
      width:14px;height:14px;border-radius:999px;
      border:1px solid #cbd5f5;
      background:#e5edff;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:.72rem;
      font-weight:600;
      color:#4b5563;
    }

    /* Buttons */
    .row{
      display:flex;
      gap:8px;
      flex-wrap:wrap;
      margin-bottom:8px;
      margin-top:2px;
    }

    .btn-label{
      font-size:.76rem;
      text-transform:uppercase;
      letter-spacing:.18em;
      color:var(--muted);
      margin-bottom:6px;
    }

    .btn{
      position:relative;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:8px;
      border-radius:999px;
      border:1px solid transparent;
      padding:9px 14px;
      font-weight:600;
      font-size:.88rem;
      cursor:pointer;
      background:var(--surface);
      color:var(--ink);
      transition:
        transform .12s ease,
        box-shadow .12s ease,
        border-color .12s ease,
        background .12s ease,
        color .12s ease;
    }
    .btn:active{
      transform:scale(.97) translateY(1px);
      box-shadow:none;
    }
    .btn:disabled{
      opacity:.55;
      cursor:not-allowed;
      transform:none;
      box-shadow:none;
    }

    .btn-primary{
      background:var(--accent-pastel);
      border-color:var(--accent-border);
      color:#075985;
      box-shadow:0 8px 18px rgba(125,211,252,.7);
    }
    .btn-primary:hover:not(:disabled){
      background:#dbeafe;
      border-color:#bfdbfe;
      box-shadow:0 11px 24px rgba(96,165,250,.7);
      transform:translateY(-1px);
    }

    .btn-ghost{
      background:var(--neutral-pastel);
      border-color:var(--neutral-border);
      color:var(--muted);
      box-shadow:none;
    }
    .btn-ghost:hover:not(:disabled){
      background:#ffffff;
      border-color:#d1d5db;
    }

    .btn-main{
      font-size:.9rem;
    }
    .btn-sub{
      font-size:.78rem;
      color:var(--muted);
    }

    .btn-circle{
      width:18px;height:18px;border-radius:999px;
      border:1px solid rgba(148,163,184,.5);
      background:linear-gradient(135deg,#f9fafb,#e0f2fe);
      display:flex;align-items:center;justify-content:center;
    }
    .btn-circle-inner{
      width:12px;height:12px;border-radius:999px;
      background:linear-gradient(135deg,#bfdbfe,#7dd3fc);
    }

    /* Info + previews */
    .info{
      font-size:.8rem;
      color:var(--muted);
      min-height:18px;
      margin-bottom:6px;
    }

    .grid{
      display:grid;
      grid-template-columns:repeat(3,1fr);
      gap:6px;
      margin-top:4px;
    }
    @media (min-width:520px){
      .grid{grid-template-columns:repeat(4,1fr);}
    }

    .thumb-wrap{
      position:relative;
      border-radius:12px;
      overflow:hidden;
      border:1px solid #e5e7eb;
      background:#f3f4f6;
      box-shadow:0 8px 18px rgba(148,163,184,.55);
      animation:thumbIn .18s ease-out;
    }

    @keyframes thumbIn{
      from{opacity:0;transform:scale(.94) translateY(3px);}
      to{opacity:1;transform:scale(1) translateY(0);}
    }

    .thumb{
      width:100%;
      height:100%;
      aspect-ratio:3/4;
      object-fit:cover;
      display:block;
    }

    .thumb-tag{
      position:absolute;
      bottom:4px;left:4px;
      padding:2px 7px;
      border-radius:999px;
      background:rgba(17,24,39,.9);
      border:1px solid rgba(17,24,39,.85);
      font-size:.68rem;
      color:#f9fafb;
    }

    /* Status */
    .status-head{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      margin-bottom:4px;
    }
    .status-label{
      font-size:.78rem;
      text-transform:uppercase;
      letter-spacing:.18em;
      color:var(--muted);
      font-weight:600;
    }

    .badge{
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding:4px 10px;
      border-radius:999px;
      font-weight:600;
      font-size:.78rem;
      border:1px solid transparent;
      background:var(--surface-soft);
    }
    .badge-dot{
      width:7px;height:7px;border-radius:999px;
      background:#d1d5db;
    }

    .b-pending{
      border-color:#e5e7eb;
      background:#f9fafb;
      color:#4b5563;
    }
    .b-upload{
      border-color:#facc15;
      background:#fefce8;
      color:#854d0e;
    }
    .b-proc{
      border-color:#93c5fd;
      background:#eff6ff;
      color:#1d4ed8;
    }
    .b-ready{
      border-color:#4ade80;
      background:#dcfce7;
      color:#166534;
    }
    .b-fail{
      border-color:#fca5a5;
      background:#fee2e2;
      color:#b91c1c;
    }

    .status-msg{
      font-size:.8rem;
      color:var(--muted);
      min-height:18px;
    }

    /* Timeline */
    .timeline{
      display:flex;
      gap:6px;
      margin-top:8px;
      flex-wrap:wrap;
    }
    .timeline-step{
      flex:1 1 0;
      min-width:70px;
      padding:6px 8px;
      border-radius:999px;
      border:1px solid #e5e7eb;
      background:#f9fafb;
      font-size:.72rem;
      color:var(--muted);
      display:flex;
      align-items:center;
      gap:6px;
      justify-content:flex-start;
      transition:background .14s ease,border-color .14s ease,box-shadow .14s ease,color .14s ease;
    }
    .timeline-dot{
      width:7px;height:7px;border-radius:999px;
      background:#d1d5db;
      flex-shrink:0;
    }
    .timeline-step.active{
      border-color:#bfdbfe;
      background:#e0edff;
      color:#111827;
      box-shadow:0 8px 18px rgba(148,163,184,.55);
    }
    .timeline-step.active .timeline-dot{
      background:#2563eb;
      box-shadow:0 0 0 4px rgba(191,219,254,.9);
    }

    /* Overlay éxito */
    .success-overlay{
      position:fixed;
      inset:0;
      background:rgba(15,23,42,.12);
      display:flex;
      align-items:center;
      justify-content:center;
      z-index:50;
      animation:overlayIn .18s ease-out;
    }
    @keyframes overlayIn{
      from{opacity:0;}to{opacity:1;}
    }

    .check-wrap{
      width:180px;height:180px;
      border-radius:999px;
      background:radial-gradient(circle,#ffffff,#e5e7eb 70%);
      display:grid;
      place-items:center;
      position:relative;
      box-shadow:
        0 22px 50px rgba(148,163,184,.9),
        0 0 0 1px rgba(209,213,219,1) inset;
      animation:popIn .24s ease-out both;
    }
    @keyframes popIn{
      0%{transform:scale(.7);opacity:0;}
      60%{transform:scale(1.04);opacity:1;}
      100%{transform:scale(1);opacity:1;}
    }

    .check-circle{
      position:absolute;inset:16px;
      border-radius:999px;
      border:7px solid rgba(34,197,94,.35);
      box-shadow:0 0 0 1px rgba(255,255,255,1) inset;
      animation:ring .55s ease-out both;
    }
    @keyframes ring{
      from{transform:scale(.7);opacity:.4;}
      to{transform:scale(1);opacity:1;}
    }

    .check-icon{
      width:76px;height:76px;
      stroke:#16a34a;stroke-width:7;
      fill:none;
      stroke-linecap:round;stroke-linejoin:round;
      stroke-dasharray:120;
      stroke-dashoffset:120;
      animation:draw .5s .1s ease-out forwards;
    }
    @keyframes draw{
      to{stroke-dashoffset:0;}
    }

    .check-text{
      margin-top:4px;
      font-weight:600;
      font-size:.85rem;
      color:#166534;
    }

    .hidden{display:none;}
  </style>
</head>
<body>

<div class="shell">
  <div class="app-bar">
    <div class="app-left">
      <div class="app-title">Captura de documento</div>
      <div class="app-session">Sesión #{{ $intake->id }} · {{ $intake->source_type ?? 'documento' }}</div>
    </div>
    <div class="app-pill">
      Token: {{ \Illuminate\Support\Str::limit($intake->token, 8) }}
    </div>
  </div>

  <div class="card">
    <div class="card-inner">
      <h1>Sube las fotos</h1>
      <div class="sub">Captura el documento y se enviará automáticamente.</div>

      <div class="steps">
        <div class="step-chip">
          <span class="step-dot">1</span>
          <span>Tomar o elegir fotos</span>
        </div>
        <div class="step-chip">
          <span class="step-dot">2</span>
          <span>Envío automático</span>
        </div>
        <div class="step-chip">
          <span class="step-dot">3</span>
          <span>Revisar en la computadora</span>
        </div>
      </div>

      <div class="btn-label">Captura</div>

      <input id="imagesInput" type="file" accept="image/*" capture="environment" multiple style="display:none;">

      <div class="row">
        <button id="btnTake" class="btn btn-primary" type="button">
          <span class="btn-circle" aria-hidden="true">
            <span class="btn-circle-inner"></span>
          </span>
          <span>
            <div class="btn-main">Tomar o elegir fotos</div>
            <div class="btn-sub">Se enviarán en segundo plano</div>
          </span>
        </button>

        <button id="btnClear" class="btn btn-ghost" type="button" disabled>
          <span class="btn-main">Limpiar selección</span>
        </button>
      </div>

      <div id="info" class="info"></div>
      <div id="previewGrid" class="grid"></div>

      <hr class="hr">

      <div class="status-head">
        <div class="status-label">Estado</div>
        <span id="statusBadge" class="badge b-pending">
          <span class="badge-dot"></span>
          <span>Pendiente</span>
        </span>
      </div>

      <div id="statusMsg" class="status-msg"></div>

      <div class="timeline" aria-hidden="true">
        <div class="timeline-step" data-st="0">
          <span class="timeline-dot"></span>
          <span>Fotos listas</span>
        </div>
        <div class="timeline-step" data-st="1">
          <span class="timeline-dot"></span>
          <span>Subidas</span>
        </div>
        <div class="timeline-step" data-st="2">
          <span class="timeline-dot"></span>
          <span>Procesando</span>
        </div>
        <div class="timeline-step" data-st="3">
          <span class="timeline-dot"></span>
          <span>Disponible</span>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Overlay de éxito --}}
<div id="successOverlay" class="success-overlay hidden">
  <div class="check-wrap">
    <div class="check-circle"></div>
    <svg viewBox="0 0 52 52" class="check-icon" aria-hidden="true">
      <path d="M14 27 L22 35 L38 18"></path>
    </svg>
    <div class="check-text">Captura guardada</div>
  </div>
</div>

<script>
  const intakeToken    = @json($intake->token);
  const uploadUrl      = `/i/${intakeToken}/upload`;
  const statusUrl      = `/i/${intakeToken}/status`;

  const imagesInput    = document.getElementById('imagesInput');
  const btnTake        = document.getElementById('btnTake');
  const btnClear       = document.getElementById('btnClear');
  const previewGrid    = document.getElementById('previewGrid');
  const info           = document.getElementById('info');
  const statusBadge    = document.getElementById('statusBadge');
  const statusMsg      = document.getElementById('statusMsg');
  const successOverlay = document.getElementById('successOverlay');
  const timelineSteps  = document.querySelectorAll('.timeline-step');

  let files = [];

  const stMap = {
    0: {txt:'Pendiente', cls:'b-pending', msg:'Aún no se han subido fotos.'},
    1: {txt:'Fotos subidas', cls:'b-upload', msg:'Fotos recibidas. Iniciando análisis.'},
    2: {txt:'Procesando', cls:'b-proc', msg:'Analizando el documento…'},
    3: {txt:'Listo', cls:'b-ready', msg:'Análisis completo. Continúa en la computadora.'},
    4: {txt:'Confirmado', cls:'b-ready', msg:'Esta captura ya fue aplicada.'},
    9: {txt:'Error', cls:'b-fail', msg:'No se pudo procesar. Intenta de nuevo.'},
  };

  function setTimeline(status){
    timelineSteps.forEach(step => {
      const st = parseInt(step.getAttribute('data-st'), 10);
      step.classList.toggle('active', st <= status);
    });
  }

  function setStatusUI(status, meta){
    const st = stMap[status] || stMap[0];
    statusBadge.className = `badge ${st.cls}`;
    statusBadge.innerHTML = `
      <span class="badge-dot"></span>
      <span>${st.txt}</span>
    `;
    statusMsg.textContent = (meta && meta.error) ? `Error: ${meta.error}` : st.msg;
    setTimeline(status);
  }

  function renderPreviews(){
    previewGrid.innerHTML = '';
    files.forEach((f, idx) => {
      const url = URL.createObjectURL(f);
      const wrap = document.createElement('div');
      wrap.className = 'thumb-wrap';
      wrap.innerHTML = `
        <img src="${url}" class="thumb" alt="Imagen ${idx+1}">
        <div class="thumb-tag">Foto ${idx+1}</div>
      `;
      previewGrid.appendChild(wrap);
    });

    info.textContent = files.length
      ? `${files.length} foto(s) listas para enviar.`
      : '';

    btnClear.disabled = files.length === 0;
  }

  function setButtonLoading(isLoading){
    if(isLoading){
      btnTake.disabled = true;
      btnTake.dataset.originalText = btnTake.innerHTML;
      btnTake.innerHTML = `
        <span class="btn-main btn-loading-text">
          Enviando fotos
          <span class="dots">
            <span class="dot"></span>
            <span class="dot"></span>
            <span class="dot"></span>
          </span>
        </span>
      `;
    }else{
      btnTake.disabled = false;
      if(btnTake.dataset.originalText){
        btnTake.innerHTML = btnTake.dataset.originalText;
        delete btnTake.dataset.originalText;
      }
    }
  }

  btnTake.onclick = () => imagesInput.click();

  imagesInput.onchange = async (e) => {
    files = Array.from(e.target.files || []);
    if(!files.length) return;
    renderPreviews();
    await autoUpload();
  };

  btnClear.onclick = () => {
    files = [];
    imagesInput.value = '';
    renderPreviews();
    info.textContent = '';
  };

  async function autoUpload(){
    if (!files.length) return;
    setButtonLoading(true);
    info.textContent = 'Subiendo fotos…';

    try{
      const fd = new FormData();
      files.forEach(f => fd.append('images[]', f));
      const csrf = document.querySelector('meta[name="csrf-token"]').content;

      const res = await fetch(uploadUrl, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: fd
      });

      if(!res.ok) throw new Error('No se pudieron subir las fotos.');
      const j = await res.json();
      if(!j.ok) throw new Error(j.message || 'No se pudieron subir las fotos.');

      successOverlay.classList.remove('hidden');
      setTimeout(() => successOverlay.classList.add('hidden'), 1400);

      files = [];
      imagesInput.value = '';
      renderPreviews();
      info.textContent = 'Fotos subidas. Analizando…';

      pollStatus(true);
    }catch(err){
      alert(err.message || 'Error al subir fotos.');
      info.textContent = 'Hubo un problema al enviar las fotos.';
    }finally{
      setButtonLoading(false);
    }
  }

  async function pollStatus(once = false){
    try{
      const res = await fetch(statusUrl, { headers:{'X-Requested-With':'XMLHttpRequest'} });
      if(!res.ok) throw new Error('Error al consultar estado.');
      const j = await res.json();
      setStatusUI(j.status, j.meta || {});
      if(!once && j.status !== 3 && j.status !== 4 && j.status !== 9){
        setTimeout(() => pollStatus(false), 2500);
      }
    }catch(e){
      if(!once){
        setTimeout(() => pollStatus(false), 4000);
      }
    }
  }

  pollStatus(false);
</script>

</body>
</html>
