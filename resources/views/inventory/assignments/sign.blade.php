@php
  $it       = $assignment->item;
  $isAsset  = $it && $it->type !== 'consumible';
  $isSigned = $assignment->signature_status === 'signed';
  $checklist = is_array($assignment->delivery_checklist) ? $assignment->delivery_checklist : [];
  $photoUrl  = ($it && $it->photo) ? asset('storage/'.$it->photo) : null;
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Firma de entrega • {{ $assignment->folio }}</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root{ --bg:#f4f5f7; --card:#fff; --ink:#0f172a; --muted:#64748b; --line:#e2e8f0; --blue:#007aff; --blue-soft:#eff6ff; --success:#15803d; --success-soft:#f0fdf4; --danger:#ef4444; }
    *{ box-sizing:border-box; }
    body{ margin:0; background:var(--bg); font-family:-apple-system,Segoe UI,Roboto,system-ui,sans-serif; color:var(--ink); }
    .wrap{ max-width:520px; margin:0 auto; padding:18px 16px 40px; }
    .card{ background:var(--card); border:1px solid var(--line); border-radius:18px; padding:18px; margin-bottom:14px; box-shadow:0 2px 10px rgba(0,0,0,.03); }
    .h{ font-size:20px; font-weight:800; margin:0 0 2px; }
    .muted{ color:var(--muted); font-size:13px; font-weight:600; }
    .label{ font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); margin:0 0 8px; }
    .asset{ display:flex; gap:14px; align-items:flex-start; }
    .asset img{ width:78px; height:78px; border-radius:12px; object-fit:cover; border:1px solid var(--line); background:#fff; }
    .asset .noimg{ width:78px; height:78px; border-radius:12px; border:1px solid var(--line); background:var(--bg); display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:26px; }
    .specs{ display:grid; grid-template-columns:auto 1fr; gap:4px 12px; font-size:12.5px; margin-top:6px; }
    .specs .k{ color:var(--muted); font-weight:600; }
    .specs .v{ color:var(--ink); font-weight:700; word-break:break-word; }
    .chk{ display:flex; align-items:center; gap:9px; padding:9px 0; border-bottom:1px solid var(--line); font-size:14px; font-weight:600; }
    .chk:last-child{ border-bottom:none; }
    .chk .ic{ width:22px; height:22px; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:13px; }
    .chk .yes{ background:var(--success-soft); color:var(--success); }
    .chk .no{ background:#f1f5f9; color:#94a3b8; }
    .sig-box{ border:1.5px dashed #cbd5e1; border-radius:14px; background:#fff; overflow:hidden; position:relative; }
    #pad{ width:100%; height:200px; display:block; touch-action:none; cursor:crosshair; }
    .sig-ph{ position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#94a3b8; pointer-events:none; gap:4px; }
    .sig-ph i{ font-size:24px; } .sig-ph span{ font-size:13px; font-weight:600; }
    .row{ display:flex; gap:10px; margin-top:12px; }
    .btn{ flex:1; height:50px; border:none; border-radius:12px; font-size:15px; font-weight:800; display:inline-flex; align-items:center; justify-content:center; gap:8px; }
    .btn-primary{ background:var(--blue); color:#fff; }
    .btn-primary:disabled{ opacity:.5; }
    .btn-ghost{ background:#f1f5f9; color:var(--muted); flex:0 0 auto; padding:0 18px; }
    .input{ width:100%; height:46px; border:1px solid var(--line); border-radius:12px; padding:0 14px; font-size:15px; font-weight:600; }
    .done{ text-align:center; padding:28px 18px; }
    .done .ring{ width:84px; height:84px; border-radius:50%; background:var(--success); color:#fff; font-size:46px; display:flex; align-items:center; justify-content:center; margin:0 auto 14px; box-shadow:0 10px 30px rgba(21,128,61,.3); animation:pop .5s cubic-bezier(.16,1,.3,1); }
    @keyframes pop{ 0%{transform:scale(.3);opacity:0} 100%{transform:scale(1);opacity:1} }
    .done h2{ margin:0 0 4px; color:var(--success); font-weight:800; }
    .done .sig-img{ max-width:100%; border:1px solid var(--line); border-radius:12px; margin-top:16px; background:#fff; }
  </style>
</head>
<body>
  <div class="wrap">

    @if($isSigned)
      {{-- ✅ PANTALLA DE FIRMA COMPLETADA --}}
      <div class="card done">
        <div class="ring"><i class="bi bi-check-lg"></i></div>
        <h2>Firma completada</h2>
        <div class="muted">Folio {{ $assignment->folio }} • {{ optional($assignment->signed_at)->format('d/m/Y H:i') }}</div>
        @if($assignment->signer_name)<div class="muted mt-1">Firmó: {{ $assignment->signer_name }}</div>@endif
        @if($assignment->signature_image)
          <img class="sig-img" src="{{ $assignment->signature_image }}" alt="Firma">
        @endif
        <div class="muted" style="margin-top:14px;">Ya puedes cerrar esta ventana.</div>
      </div>
    @else
      {{-- Encabezado --}}
      <div class="card">
        <p class="h">Recibo de entrega</p>
        <div class="muted">Folio {{ $assignment->folio }}</div>
        <div style="margin-top:10px; font-weight:700;">{{ $it->name ?? 'Activo' }} <span class="muted">× {{ $assignment->quantity }}</span></div>
        <div class="muted">Responsable: {{ $assignment->user->name ?? '—' }}</div>
      </div>

      {{-- Ficha del equipo (solo activos fijos) --}}
      @if($isAsset)
        <div class="card">
          <p class="label">Equipo asignado</p>
          <div class="asset">
            @if($photoUrl)
              <img src="{{ $photoUrl }}" alt="Equipo">
            @else
              <div class="noimg"><i class="bi bi-pc-display"></i></div>
            @endif
            <div style="flex:1; min-width:0;">
              <div style="font-weight:800;">{{ $it->name }}</div>
              <div class="specs">
                @if($it->brand)<div class="k">Marca</div><div class="v">{{ $it->brand }}</div>@endif
                @if($it->model)<div class="k">Modelo</div><div class="v">{{ $it->model }}</div>@endif
                @if($it->serial_number)<div class="k">No. Serie</div><div class="v">{{ $it->serial_number }}</div>@endif
                @if($it->internal_code)<div class="k">Código</div><div class="v">{{ $it->internal_code }}</div>@endif
                @if($it->processor)<div class="k">Procesador</div><div class="v">{{ $it->processor }}</div>@endif
                @if($it->ram)<div class="k">RAM</div><div class="v">{{ $it->ram }}</div>@endif
                @if($it->storage)<div class="k">Almacenam.</div><div class="v">{{ $it->storage }}</div>@endif
                @if($it->operating_system)<div class="k">S.O.</div><div class="v">{{ $it->operating_system }}</div>@endif
                @if($it->mac_address)<div class="k">MAC</div><div class="v">{{ $it->mac_address }}</div>@endif
              </div>
            </div>
          </div>
        </div>
      @endif

      {{-- Checklist de entrega --}}
      @if(count($checklist))
        <div class="card">
          <p class="label">Se entrega con</p>
          @foreach($checklist as $c)
            <div class="chk">
              <span class="ic {{ ($c['checked'] ?? false) ? 'yes' : 'no' }}">
                <i class="bi {{ ($c['checked'] ?? false) ? 'bi-check-lg' : 'bi-x-lg' }}"></i>
              </span>
              {{ $c['label'] ?? '' }}
            </div>
          @endforeach
        </div>
      @endif

      {{-- Firma --}}
      <form id="signForm" method="POST" action="{{ route('assignments.public.store', $assignment->sign_token) }}">
        @csrf
        <div class="card">
          <p class="label">Firma del responsable</p>
          <input type="text" class="input" name="signer_name" placeholder="Tu nombre" value="{{ $assignment->user->name ?? '' }}" style="margin-bottom:12px;">
          <div class="sig-box">
            <div class="sig-ph" id="sigPh"><i class="bi bi-pen"></i><span>Firma aquí con tu dedo</span></div>
            <canvas id="pad"></canvas>
          </div>
          <div class="row">
            <button type="button" class="btn btn-ghost" id="clearBtn"><i class="bi bi-eraser"></i></button>
            <button type="submit" class="btn btn-primary" id="sendBtn" disabled><i class="bi bi-check2-circle"></i> Confirmar firma</button>
          </div>
        </div>
        <input type="hidden" name="signature" id="signatureInput">
      </form>
    @endif

  </div>

  @unless($isSigned)
  <script>
    const canvas = document.getElementById('pad');
    const ctx = canvas.getContext('2d');
    const ph = document.getElementById('sigPh');
    const sendBtn = document.getElementById('sendBtn');
    let drawing = false, hasDrawn = false;

    function resize(){
      const ratio = Math.max(window.devicePixelRatio || 1, 1);
      const rect = canvas.getBoundingClientRect();
      const data = hasDrawn ? canvas.toDataURL() : null;
      canvas.width = rect.width * ratio;
      canvas.height = rect.height * ratio;
      ctx.setTransform(1,0,0,1,0,0);
      ctx.scale(ratio, ratio);
      ctx.lineWidth = 2.6; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#0f172a';
      if(data){ const img = new Image(); img.onload = ()=> ctx.drawImage(img,0,0,rect.width,rect.height); img.src = data; }
    }
    function pos(e){ const r = canvas.getBoundingClientRect(); const t = e.touches ? e.touches[0] : e; return { x:t.clientX-r.left, y:t.clientY-r.top }; }
    function start(e){ drawing = true; hasDrawn = true; ph.style.display='none'; sendBtn.disabled=false; const p=pos(e); ctx.beginPath(); ctx.moveTo(p.x,p.y); }
    function move(e){ if(!drawing) return; e.preventDefault(); const p=pos(e); ctx.lineTo(p.x,p.y); ctx.stroke(); }
    function end(){ drawing = false; }

    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    window.addEventListener('mouseup', end);
    canvas.addEventListener('touchstart', start, {passive:false});
    canvas.addEventListener('touchmove', move, {passive:false});
    window.addEventListener('touchend', end);

    document.getElementById('clearBtn').addEventListener('click', ()=>{
      ctx.clearRect(0,0,canvas.width,canvas.height);
      hasDrawn = false; sendBtn.disabled = true; ph.style.display='flex';
    });

    document.getElementById('signForm').addEventListener('submit', e=>{
      if(!hasDrawn){ e.preventDefault(); alert('Por favor captura tu firma.'); return; }
      document.getElementById('signatureInput').value = canvas.toDataURL('image/png');
      sendBtn.disabled = true; sendBtn.innerHTML = 'Enviando...';
    });

    window.addEventListener('load', resize);
    window.addEventListener('resize', resize);
  </script>
  @endunless
</body>
</html>