<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Capturar documento</title>

  <style>
    :root{
      --ink:#0e1726; --muted:#6b7280; --bg:#f5f7fb; --line:#e8eef6;
      --brand:#6ea8fe; --ok:#16a34a; --warn:#f59e0b; --err:#ef4444;
    }
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;background:var(--bg);color:var(--ink);}
    .wrap{max-width:780px;margin:0 auto;padding:14px;}
    .card{background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:0 10px 24px rgba(13,23,38,.06);padding:14px;}
    h1{font-size:1.2rem;margin:0 0 6px;font-weight:800;}
    .muted{color:var(--muted);font-size:.9rem;}
    .btn{
      display:inline-flex;align-items:center;justify-content:center;border:0;cursor:pointer;
      border-radius:12px;padding:12px 14px;font-weight:800;font-size:1rem;
      background:#fff;border:1px solid var(--line);
    }
    .btn-primary{background:var(--brand);color:#0b1220;border-color:transparent;}
    .btn:disabled{opacity:.6;cursor:not-allowed;}
    .row{display:flex;gap:8px;flex-wrap:wrap;}
    .grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:10px;}
    .thumb{width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:10px;border:1px solid var(--line);background:#f8fafc;}
    .badge{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;font-weight:800;font-size:.8rem;}
    .b-pending{background:#eef2ff;color:#3730a3;}
    .b-upload{background:#fef9c3;color:#854d0e;}
    .b-proc{background:#e0f2fe;color:#075985;}
    .b-ready{background:#dcfce7;color:#166534;}
    .b-fail{background:#fee2e2;color:#991b1b;}
    .hr{border:none;border-top:1px dashed var(--line);margin:12px 0;}
    .small{font-size:.85rem;}

    /* ===== Overlay palomita tipo BBVA ===== */
    .success-overlay{
      position:fixed; inset:0; background:rgba(15,23,42,.55);
      display:flex; align-items:center; justify-content:center; z-index:9999;
      animation: fadeIn .2s ease;
    }
    @keyframes fadeIn{from{opacity:0}to{opacity:1}}
    .check-wrap{
      width:180px; height:180px; border-radius:999px; background:#fff;
      display:grid; place-items:center; position:relative;
      box-shadow:0 20px 50px rgba(0,0,0,.25);
      animation: pop .35s ease both;
    }
    @keyframes pop{from{transform:scale(.7);opacity:0}to{transform:scale(1);opacity:1}}

    .check-circle{
      position:absolute; inset:10px; border-radius:999px;
      border:10px solid #dcfce7; animation:ring .9s ease both;
    }
    @keyframes ring{from{transform:scale(.6);opacity:.2}to{transform:scale(1);opacity:1}}

    .check-icon{
      width:80px; height:80px; stroke:#16a34a; stroke-width:10; fill:none;
      stroke-linecap:round; stroke-linejoin:round;
      stroke-dasharray: 120; stroke-dashoffset:120;
      animation: draw .6s .15s ease forwards;
    }
    @keyframes draw{to{stroke-dashoffset:0}}

    .check-text{margin-top:6px;font-weight:900;color:#0f172a;}
  </style>
</head>
<body>

<div class="wrap">
  <div class="card">
    <h1>Captura de {{ $intake->source_type ?? 'documento' }}</h1>
    <div class="muted">
      Sesión: <b>#{{ $intake->id }}</b> ·
      Token: <span class="small">{{ $intake->token }}</span>
    </div>

    <hr class="hr">

    <div class="muted" style="margin-bottom:8px;">
      1) Toma fotos claras del documento.  
      2) Se subirán solas automáticamente.  
      3) Espera a que la IA termine y regresa a la compu.
    </div>

    <input id="imagesInput" type="file" accept="image/*" capture="environment" multiple style="display:none;">

    <div class="row">
      <button id="btnTake" class="btn btn-primary" type="button">📷 Tomar / elegir fotos</button>
      <button id="btnClear" class="btn" type="button" disabled>🧹 Limpiar</button>
    </div>

    <div id="info" class="muted" style="margin-top:8px;"></div>
    <div id="previewGrid" class="grid"></div>

    <hr class="hr">

    <div>
      <b>Estatus:</b>
      <span id="statusBadge" class="badge b-pending">Pendiente</span>
      <div id="statusMsg" class="muted" style="margin-top:4px;"></div>
    </div>
  </div>
</div>

{{-- Overlay de éxito --}}
<div id="successOverlay" class="success-overlay" style="display:none;">
  <div class="check-wrap">
    <div class="check-circle"></div>
    <svg viewBox="0 0 52 52" class="check-icon" aria-hidden="true">
      <path d="M14 27 L22 35 L38 18"></path>
    </svg>
    <div class="check-text">¡Listo!</div>
  </div>
</div>

<script>
  const intakeToken = @json($intake->token);
  const uploadUrl = `/i/${intakeToken}/upload`;
  const statusUrl = `/i/${intakeToken}/status`;

  const imagesInput  = document.getElementById('imagesInput');
  const btnTake      = document.getElementById('btnTake');
  const btnClear     = document.getElementById('btnClear');
  const previewGrid  = document.getElementById('previewGrid');
  const info         = document.getElementById('info');
  const statusBadge  = document.getElementById('statusBadge');
  const statusMsg    = document.getElementById('statusMsg');
  const successOverlay = document.getElementById('successOverlay');

  let files = [];

  const stMap = {
    0: {txt:'Pendiente', cls:'b-pending', msg:'Aún no se han subido fotos.'},
    1: {txt:'Fotos subidas', cls:'b-upload', msg:'Fotos recibidas. IA arrancando...'},
    2: {txt:'Procesando IA', cls:'b-proc', msg:'Analizando documento...'},
    3: {txt:'Listo', cls:'b-ready', msg:'IA lista. Regresa a la computadora.'},
    4: {txt:'Confirmado', cls:'b-ready', msg:'Esta captura ya fue aplicada.'},
    9: {txt:'Falló', cls:'b-fail', msg:'La IA falló. Intenta otra vez.'},
  };

  function renderPreviews(){
    previewGrid.innerHTML = '';
    files.forEach(f=>{
      const url = URL.createObjectURL(f);
      const img = document.createElement('img');
      img.src = url;
      img.className = 'thumb';
      previewGrid.appendChild(img);
    });

    info.textContent = files.length ? `${files.length} foto(s) seleccionada(s). Subiendo...` : '';
    btnClear.disabled = files.length === 0;
  }

  btnTake.onclick = ()=> imagesInput.click();

  imagesInput.onchange = async (e)=>{
    files = Array.from(e.target.files || []);
    if(!files.length) return;

    renderPreviews();
    await autoUpload(); // 👈 SUBE AUTOMÁTICO
  };

  btnClear.onclick = ()=>{
    files = [];
    imagesInput.value = '';
    renderPreviews();
    info.textContent = '';
  };

  async function autoUpload(){
    btnTake.disabled = true;

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

      const j = await res.json();
      if(!j.ok) throw new Error('No se pudieron subir las fotos.');

      // animación BBVA
      successOverlay.style.display = 'flex';
      setTimeout(()=> successOverlay.style.display = 'none', 1400);

      files = [];
      imagesInput.value = '';
      renderPreviews();
      info.textContent = 'Fotos subidas correctamente.';

      pollStatus(true);

    }catch(err){
      alert(err.message || 'Error al subir fotos.');
    }finally{
      btnTake.disabled = false;
    }
  }

  async function pollStatus(once=false){
    try{
      const res = await fetch(statusUrl, {headers:{'X-Requested-With':'XMLHttpRequest'}});
      const j = await res.json();

      const st = stMap[j.status] || stMap[0];
      statusBadge.className = `badge ${st.cls}`;
      statusBadge.textContent = st.txt;

      statusMsg.textContent = (j.meta && j.meta.error)
        ? `Error: ${j.meta.error}`
        : st.msg;

      if(!once && j.status !== 3 && j.status !== 9 && j.status !== 4){
        setTimeout(()=>pollStatus(false), 2500);
      }
    }catch(e){
      if(!once) setTimeout(()=>pollStatus(false), 4000);
    }
  }

  pollStatus(false);
</script>

</body>
</html>
