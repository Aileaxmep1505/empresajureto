<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Confirmar movimiento</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{ background:#f5f7fb; }
    .wrap{ max-width:640px; margin:24px auto; padding:14px; }
    .card{ border:1px solid #e8eef6; border-radius:16px; box-shadow:0 10px 40px rgba(2,6,23,.08) }
    .sig{ border:1px dashed #cfe6ff; border-radius:14px; padding:12px; background:#fff }
    canvas{ width:100%; height:220px; border-radius:10px; touch-action:none; }
  </style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <div class="card-body">
      <h5 class="mb-1">Confirmar movimiento</h5>
      <div class="text-muted mb-3">Escribe el motivo y firma para confirmar.</div>

      <div class="mb-3">
        <label class="form-label">Motivo *</label>
        <input type="text" id="purpose" class="form-control" maxlength="255" value="{{ $expense->description ?? '' }}">
      </div>

      <div class="mb-3">
        <label class="form-label">Firma *</label>
        <div class="sig">
          <canvas id="sig"></canvas>
          <div class="d-flex justify-content-between mt-2">
            <small class="text-muted">Firma con el dedo</small>
            <button class="btn btn-sm btn-outline-secondary" id="clear" type="button">Limpiar</button>
          </div>
        </div>
      </div>

      <button class="btn btn-success w-100" id="send" type="button">Confirmar</button>
      <div class="text-muted small mt-2" id="msg"></div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.2.0/dist/signature_pad.umd.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
  const canvas = document.getElementById('sig');
  const pad = new SignaturePad(canvas,{backgroundColor:'#fff', penColor:'#0f172a'});

  function fit(){
    const ratio = Math.max(window.devicePixelRatio||1,1);
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width*ratio;
    canvas.height = rect.height*ratio;
    canvas.getContext('2d').setTransform(ratio,0,0,ratio,0,0);
    pad.clear();
  }
  window.addEventListener('resize', fit);
  fit();

  $('#clear').on('click', ()=> pad.clear());

  $('#send').on('click', ()=>{
    const purpose = ($('#purpose').val()||'').trim();
    if(purpose.length < 3){ $('#msg').text('Motivo inválido.'); return; }
    if(pad.isEmpty()){ $('#msg').text('Falta la firma.'); return; }

    $('#send').prop('disabled', true);
    $('#msg').text('Enviando…');

    $.ajax({
      url: "{{ route('expenses.movements.qr.ack', ['token'=>$expense->qr_token], false) }}",
      method:'POST',
      data: {
        purpose,
        signature: pad.toDataURL('image/png'),
        _token: "{{ csrf_token() }}"
      }
    }).done(()=>{
      $('#msg').text('Listo. Puedes cerrar esta pantalla.');
    }).fail((x)=>{
      $('#send').prop('disabled', false);
      $('#msg').text(x.responseJSON?.message || 'No se pudo enviar.');
    });
  });
</script>
</body>
</html>
