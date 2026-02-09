<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Confirmar movimiento</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <meta name="csrf-token" content="{{ csrf_token() }}">

  <style>
    :root{
      --bg:#ffffff;               /* BODY BLANCO */
      --panel:#ffffff;
      --ink:#0f172a;
      --muted:#64748b;
      --line:#e6eef8;
      --blue:#1f7ae6;
      --blueSoft: rgba(31,122,230,.10);
      --shadow:0 16px 45px rgba(2,6,23,.10);
      --r:18px;
      --danger:#dc2626;
      --ok:#16a34a;
    }

    body{ background:var(--bg); color:var(--ink); }

    .wrap{ max-width:720px; margin:18px auto; padding:14px; }

    .hero{
      background: linear-gradient(135deg, rgba(31,122,230,.16), rgba(31,122,230,.06));
      border:1px solid rgba(31,122,230,.20);
      border-radius: var(--r);
      padding: 14px 16px;
      box-shadow: var(--shadow);
      display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
    }
    .hero .left{ display:flex; align-items:center; gap:12px; }
    .bubble{
      width:42px; height:42px; border-radius:999px;
      display:inline-flex; align-items:center; justify-content:center;
      background:#fff; border:1px solid rgba(31,122,230,.25);
    }
    .bubble i{ color:var(--blue); font-size:1.15rem; }

    .cardX{
      margin-top:14px;
      background:var(--panel);
      border:1px solid var(--line);
      border-radius: var(--r);
      box-shadow: var(--shadow);
      overflow:hidden;
    }
    .cardX .head{
      padding: 12px 16px;
      border-bottom:1px solid var(--line);
      display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
    }
    .pill{
      background: var(--blueSoft);
      color: var(--blue);
      border:1px solid rgba(31,122,230,.18);
      border-radius:999px;
      padding:6px 10px;
      font-size:12px;
      font-weight:700;
      display:inline-flex; align-items:center; gap:6px;
    }

    .body{ padding:16px; }

    .info{
      border:1px dashed rgba(31,122,230,.28);
      background:#fbfdff;
      border-radius:14px;
      padding:12px;
      margin-bottom:14px;
    }

    .label{ font-size:12px; color:var(--muted); font-weight:700; margin-bottom:6px; }
    .value{ font-weight:900; }

    .form-control{
      border:1px solid var(--line);
      border-radius:14px;
      padding:.9rem .95rem;
    }
    .form-control:focus{
      border-color: rgba(31,122,230,.35);
      box-shadow: 0 0 0 .25rem rgba(31,122,230,.12);
    }

    .sigWrap{
      border:1px dashed rgba(31,122,230,.28);
      border-radius:14px;
      padding:12px;
      background:#fff;
    }
    canvas{ width:100%; height:220px; border-radius:10px; touch-action:none; background:#fff; }

    .err{ color:var(--danger); font-size:12px; margin-top:8px; display:none; }
    .ok{ color:var(--ok); font-size:12px; margin-top:8px; display:none; }

    .btnX{
      border-radius:14px;
      padding:.78rem 1rem;
      font-weight:800;
      border:1px solid transparent;
      display:inline-flex; align-items:center; gap:.55rem;
      transition: transform .12s, box-shadow .2s, filter .2s;
    }
    .btnX:active{ transform: translateY(1px); }
    .btnPrimary{
      background: rgba(31,122,230,.14);
      border-color: rgba(31,122,230,.20);
      color:#0b2a4a;
      box-shadow: 0 14px 26px rgba(31,122,230,.12);
      width:100%;
      justify-content:center;
    }
    .btnPrimary:hover{
      filter:brightness(1.02);
      box-shadow: 0 18px 34px rgba(31,122,230,.16);
      transform: translateY(-1px);
    }
    .btnGhost{
      background:#fff;
      border-color: var(--line);
      color:#334155;
    }

    .spinner{ width:1.1rem; height:1.1rem; }
  </style>
</head>
<body>
@php
  $token = $expense->qr_token ?? '';
  $amount = number_format((float)($expense->amount ?? 0), 2);
  $currency = \Illuminate\Support\Facades\Schema::hasColumn('expenses','currency')
      ? ($expense->currency ?: 'MXN')
      : 'MXN';
@endphp

<div class="wrap">
  <div class="hero">
    <div class="left">
      <span class="bubble"><i class="bi bi-qr-code-scan"></i></span>
      <div>
        <div style="font-weight:900; font-size:18px; line-height:1.1;">Confirmar movimiento</div>
        <div style="color:var(--muted); font-size:12.5px;">Escribe el motivo y firma para confirmar.</div>
      </div>
    </div>
    <span class="pill"><i class="bi bi-shield-check"></i> Público (QR)</span>
  </div>

  <div class="cardX">
    <div class="head">
      <div style="font-weight:900;">Folio #{{ $expense->id }}</div>
      <div style="color:var(--muted); font-size:12.5px;">{{ $currency }} {{ $amount }}</div>
    </div>

    <div class="body">
      <div class="info">
        <div class="label">Concepto</div>
        <div class="value">{{ $expense->concept ?? 'Entrega' }}</div>
        <div style="color:var(--muted); font-size:12px; margin-top:4px;">
          Verifica que el motivo sea correcto antes de firmar.
        </div>
      </div>

      <div class="mb-3">
        <div class="label">Motivo *</div>
        <input type="text" id="purpose" class="form-control" maxlength="255"
               value="{{ $expense->description ?? '' }}"
               placeholder="Ej. Entrega para compras, viáticos, etc.">
      </div>

      <div class="mb-3">
        <div class="label">Firma *</div>
        <div class="sigWrap">
          <canvas id="sig"></canvas>
          <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
            <small class="text-muted"><i class="bi bi-pen"></i> Firma con el dedo o mouse</small>
            <button class="btnX btnGhost" id="clear" type="button">
              <i class="bi bi-eraser"></i> Limpiar
            </button>
          </div>
        </div>
      </div>

      <button class="btnX btnPrimary" id="send" type="button">
        <span id="sendText"><i class="bi bi-check2-circle"></i> Confirmar</span>
        <span class="d-none" id="sendSpin">
          <span class="spinner-border spinner-border-sm spinner" role="status"></span>
        </span>
      </button>

      <div class="err" id="err"></div>
      <div class="ok" id="ok"></div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.2.0/dist/signature_pad.umd.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
(function(){
  const canvas = document.getElementById('sig');
  const pad = new SignaturePad(canvas,{ backgroundColor:'#fff', penColor:'#0f172a' });

  const $err = $('#err');
  const $ok  = $('#ok');

  function fit(){
    const ratio = Math.max(window.devicePixelRatio||1,1);
    const rect = canvas.getBoundingClientRect();
    canvas.width  = rect.width * ratio;
    canvas.height = rect.height * ratio;
    canvas.getContext('2d').setTransform(ratio,0,0,ratio,0,0);
    pad.clear();
  }
  window.addEventListener('resize', fit);
  setTimeout(fit, 30);

  $('#clear').on('click', ()=> pad.clear());

  function setLoading(on){
    $('#send').prop('disabled', on);
    $('#sendText').toggleClass('d-none', on);
    $('#sendSpin').toggleClass('d-none', !on);
  }

  $('#send').on('click', ()=>{
    $err.hide().text('');
    $ok.hide().text('');

    const purpose = ($('#purpose').val()||'').trim();
    if(purpose.length < 3){
      $err.text('Motivo inválido.').show();
      return;
    }
    if(pad.isEmpty()){
      $err.text('Falta la firma.').show();
      return;
    }

    setLoading(true);

    $.ajax({
      url: "{{ route('expenses.movements.qr.ack', ['token'=>$expense->qr_token], false) }}",
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      },
      data: {
        purpose: purpose,
        signature: pad.toDataURL('image/png')
      }
    }).done(()=>{
      $ok.text('Listo. Puedes cerrar esta pantalla.').show();
      $('#clear').prop('disabled', true);
    }).fail((x)=>{
      setLoading(false);

      // 410 = expirado (tu controller abort_if(..., 410,...))
      if (x.status === 410) {
        $err.text('Este QR expiró. Pide que generen uno nuevo.').show();
        return;
      }

      $err.text(x.responseJSON?.message || 'No se pudo enviar.').show();
    });
  });

})();
</script>
</body>
</html>
