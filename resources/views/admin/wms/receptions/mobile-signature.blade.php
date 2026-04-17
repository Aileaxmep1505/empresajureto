<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Firma Digital</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg: #ffffff; /* Fondo general 100% blanco */
      --card: #ffffff;
      --ink: #111111; /* Títulos oscuros */
      --text: #333333; /* Texto principal */
      --muted: #888888; /* Texto secundario */
      --line: #ebebeb; /* Bordes sutiles */
      --blue: #007aff; /* Azul corporativo primario */
      --blue-soft: #e6f0ff;
      --success: #15803d;
      --success-soft: #e6ffe6;
    }

    * {
      box-sizing: border-box;
      -webkit-tap-highlight-color: transparent;
    }

    html, body {
      margin: 0;
      padding: 0;
      background: var(--bg);
      font-family: 'Quicksand', system-ui, -apple-system, sans-serif;
      color: var(--text);
      -webkit-font-smoothing: antialiased;
    }

    body {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .page {
      width: 100%;
      max-width: 600px;
      margin: 0 auto;
      padding: 40px 24px;
    }

    /* Hero & Header */
    .hero {
      text-align: center;
      margin-bottom: 32px;
    }

    .logo-box {
      width: 72px;
      height: 72px;
      margin: 0 auto 20px;
      border-radius: 20px;
      background: var(--blue-soft); /* Reemplaza el gradiente pesado */
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .logo-box svg {
      color: var(--blue);
    }

    .hero h1 {
      margin: 0 0 8px;
      color: var(--ink);
      font-size: 28px;
      font-weight: 700;
      letter-spacing: -0.02em;
    }

    .folio {
      margin: 0;
      color: var(--muted);
      font-size: 16px;
      font-weight: 600;
    }

    .folio strong {
      color: var(--text);
      font-weight: 700;
    }

    /* Tarjetas Minimalistas */
    .card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.02);
      padding: 24px;
      margin-bottom: 24px;
      transition: all 0.3s ease;
    }

    .card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.04);
    }

    /* Grid de personas */
    .people-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 24px;
    }

    .label {
      display: block;
      color: var(--muted);
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 6px;
      font-weight: 700;
    }

    .person-name {
      color: var(--ink);
      font-size: 18px;
      font-weight: 700;
      line-height: 1.3;
      word-break: break-word;
    }

    /* Sección de Firma */
    .sign-card-title {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      color: var(--ink);
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 8px;
      text-align: center;
    }

    .sign-person {
      text-align: center;
      color: var(--muted);
      font-size: 15px;
      font-weight: 600;
      margin-bottom: 24px;
    }

    /* Canvas */
    .canvas-wrap {
      width: 100%;
      min-height: 240px;
      border: 2px dashed var(--line);
      border-radius: 12px;
      background: var(--bg);
      overflow: hidden;
      position: relative;
      margin-bottom: 24px;
    }

    canvas.signature-pad {
      width: 100%;
      height: 240px;
      display: block;
      background: transparent;
      touch-action: none;
      cursor: crosshair;
    }

    /* Botones corporativos */
    .buttons {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    .btn {
      min-height: 52px;
      border-radius: 999px;
      font-family: inherit;
      font-size: 15px;
      font-weight: 700;
      border: 0;
      cursor: pointer;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn:active {
      transform: scale(0.98);
    }

    .btn-outline {
      background: var(--card);
      color: var(--blue);
      border: 1.5px solid var(--blue);
    }
    .btn-outline:hover {
      background: var(--blue-soft);
    }

    .btn-primary {
      background: var(--blue);
      color: #ffffff;
    }
    .btn-primary:hover {
      background: #006ae6;
    }

    /* Estados de Éxito */
    .done-block {
      display: none;
      background: var(--success-soft);
      border-color: #cce8cc; /* Borde suave verde */
      text-align: center;
      padding: 32px 24px;
    }

    .done-block.is-open {
      display: block;
    }

    .done-icon {
      width: 48px;
      height: 48px;
      border-radius: 999px;
      background: #ffffff;
      color: var(--success);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      box-shadow: 0 4px 12px rgba(21, 128, 61, 0.1);
    }

    .done-title {
      color: var(--success);
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 4px;
    }

    .done-subtitle {
      color: var(--success);
      opacity: 0.8;
      font-size: 14px;
      font-weight: 600;
    }

    .sign-block.is-hidden {
      display: none;
    }

    /* All Done Final */
    .all-done {
      text-align: center;
      padding: 40px 20px;
    }

    .all-done-icon {
      width: 80px;
      height: 80px;
      border-radius: 999px;
      background: var(--success-soft);
      color: var(--success);
      margin: 0 auto 24px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .all-done h2 {
      margin: 0 0 12px;
      font-size: 24px;
      color: var(--ink);
      font-weight: 700;
    }

    .all-done p {
      margin: 0;
      color: var(--muted);
      font-size: 16px;
      line-height: 1.5;
      font-weight: 500;
    }

    @media (max-width: 480px) {
      .people-grid {
        grid-template-columns: 1fr;
        gap: 20px;
      }
      .buttons {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="page">
    
    <div class="hero">
      <div class="logo-box">
        <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
        </svg>
      </div>
      <h1>Firma Digital</h1>
      <p class="folio">Folio: <strong>{{ $reception->folio }}</strong></p>
    </div>

    <div class="card">
      <div class="people-grid">
        <div>
          <span class="label">Entrega</span>
          <div class="person-name">{{ $reception->deliverer_name }}</div>
        </div>
        <div>
          <span class="label">Recibe</span>
          <div class="person-name">{{ $reception->receiver_name }}</div>
        </div>
      </div>
    </div>

    <div class="card sign-block {{ !empty($reception->delivered_signature) ? 'is-hidden' : '' }}" id="delivererSignBlock">
      <div class="sign-card-title">Firma — Quien Entrega</div>
      <div class="sign-person">{{ $reception->deliverer_name }}</div>

      <div class="canvas-wrap">
        <canvas id="delivererCanvas" class="signature-pad"></canvas>
      </div>

      <div class="buttons">
        <button type="button" class="btn btn-outline" id="clearDelivererBtn">
          Limpiar
        </button>
        <button type="button" class="btn btn-primary" id="saveDelivererBtn">
          Confirmar Firma
        </button>
      </div>
    </div>

    <div class="card done-block {{ !empty($reception->delivered_signature) ? 'is-open' : '' }}" id="delivererDoneBlock">
      <div class="done-icon">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
      </div>
      <div class="done-title">Firma Registrada</div>
      <div class="done-subtitle">Entrega: {{ $reception->deliverer_name }}</div>
    </div>

    <div class="card sign-block {{ !empty($reception->received_signature) ? 'is-hidden' : '' }}" id="receiverSignBlock">
      <div class="sign-card-title">Firma — Quien Recibe</div>
      <div class="sign-person">{{ $reception->receiver_name }}</div>

      <div class="canvas-wrap">
        <canvas id="receiverCanvas" class="signature-pad"></canvas>
      </div>

      <div class="buttons">
        <button type="button" class="btn btn-outline" id="clearReceiverBtn">
          Limpiar
        </button>
        <button type="button" class="btn btn-primary" id="saveReceiverBtn">
          Confirmar Firma
        </button>
      </div>
    </div>

    <div class="card done-block {{ !empty($reception->received_signature) ? 'is-open' : '' }}" id="receiverDoneBlock">
      <div class="done-icon">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
      </div>
      <div class="done-title">Firma Registrada</div>
      <div class="done-subtitle">Recibe: {{ $reception->receiver_name }}</div>
    </div>

    <div class="card {{ (!empty($reception->delivered_signature) && !empty($reception->received_signature)) ? '' : 'is-hidden' }}" id="allDoneCard" style="{{ (!empty($reception->delivered_signature) && !empty($reception->received_signature)) ? '' : 'display:none;' }}">
      <div class="all-done">
        <div class="all-done-icon">
          <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <h2>¡Proceso Completado!</h2>
        <p>Ambas firmas han sido validadas y anexadas al documento de recepción exitosamente.</p>
      </div>
    </div>

  </div>

  <script>
    (() => {
      const csrfToken = @json(csrf_token());
      const token = @json($reception->signature_token);

      const delivererCanvas = document.getElementById('delivererCanvas');
      const receiverCanvas = document.getElementById('receiverCanvas');

      const delivererSignBlock = document.getElementById('delivererSignBlock');
      const receiverSignBlock = document.getElementById('receiverSignBlock');
      const delivererDoneBlock = document.getElementById('delivererDoneBlock');
      const receiverDoneBlock = document.getElementById('receiverDoneBlock');
      const allDoneCard = document.getElementById('allDoneCard');

      function initCanvas(canvas) {
        const ctx = canvas.getContext('2d');
        let drawing = false;
        let hasDrawn = false;

        function resize() {
          const rect = canvas.parentElement.getBoundingClientRect();
          const ratio = window.devicePixelRatio || 1;
          const width = rect.width;
          const height = rect.height;

          const oldData = hasDrawn ? canvas.toDataURL('image/png') : null;

          canvas.width = width * ratio;
          canvas.height = height * ratio;
          canvas.style.width = width + 'px';
          canvas.style.height = height + 'px';

          ctx.setTransform(1, 0, 0, 1, 0, 0);
          ctx.scale(ratio, ratio);
          ctx.lineWidth = 3.2;
          ctx.lineCap = 'round';
          ctx.lineJoin = 'round';
          ctx.strokeStyle = '#111111';

          if (oldData) {
            const img = new Image();
            img.onload = () => {
              ctx.drawImage(img, 0, 0, width, height);
            };
            img.src = oldData;
          }
        }

        function getPoint(e) {
          const rect = canvas.getBoundingClientRect();
          const source = e.touches ? e.touches[0] : e;
          return {
            x: source.clientX - rect.left,
            y: source.clientY - rect.top
          };
        }

        function start(e) {
          drawing = true;
          hasDrawn = true;
          const p = getPoint(e);
          ctx.beginPath();
          ctx.moveTo(p.x, p.y);
        }

        function move(e) {
          if (!drawing) return;
          e.preventDefault();
          const p = getPoint(e);
          ctx.lineTo(p.x, p.y);
          ctx.stroke();
        }

        function end() {
          drawing = false;
        }

        function clear() {
          ctx.clearRect(0, 0, canvas.width, canvas.height);
          hasDrawn = false;
        }

        function hasSignature() {
          return hasDrawn;
        }

        function toDataURL() {
          return canvas.toDataURL('image/png');
        }

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        window.addEventListener('mouseup', end);

        canvas.addEventListener('touchstart', start, { passive: false });
        canvas.addEventListener('touchmove', move, { passive: false });
        window.addEventListener('touchend', end);

        resize();
        window.addEventListener('resize', resize);

        return { clear, hasSignature, toDataURL, resize };
      }

      const delivererPad = delivererCanvas ? initCanvas(delivererCanvas) : null;
      const receiverPad = receiverCanvas ? initCanvas(receiverCanvas) : null;

      function showAllDoneIfReady() {
        const delivererDone = delivererDoneBlock.classList.contains('is-open');
        const receiverDone = receiverDoneBlock.classList.contains('is-open');

        if (delivererDone && receiverDone) {
          allDoneCard.style.display = 'block';
        }
      }

      async function saveSignature(role, pad, signBlock, doneBlock) {
        if (!pad || !pad.hasSignature()) {
          alert('Debes capturar una firma antes de confirmar.');
          return;
        }

        const res = await fetch(`{{ route('public.receptions.mobile.save', '__TOKEN__') }}`.replace('__TOKEN__', token), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            role,
            signature: pad.toDataURL()
          })
        });

        const json = await res.json();

        if (!json.ok) {
          alert('No se pudo guardar la firma.');
          return;
        }

        signBlock.classList.add('is-hidden');
        doneBlock.classList.add('is-open');
        showAllDoneIfReady();
      }

      document.getElementById('clearDelivererBtn')?.addEventListener('click', () => delivererPad?.clear());
      document.getElementById('clearReceiverBtn')?.addEventListener('click', () => receiverPad?.clear());

      document.getElementById('saveDelivererBtn')?.addEventListener('click', () => {
        saveSignature('deliverer', delivererPad, delivererSignBlock, delivererDoneBlock);
      });

      document.getElementById('saveReceiverBtn')?.addEventListener('click', () => {
        saveSignature('receiver', receiverPad, receiverSignBlock, receiverDoneBlock);
      });

      async function refreshPublicStatus() {
        try {
          const res = await fetch(`{{ route('public.receptions.mobile.status', '__TOKEN__') }}`.replace('__TOKEN__', token), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          });

          if (!res.ok) return;

          const json = await res.json();

          if (json.delivered_signature) {
            delivererSignBlock?.classList.add('is-hidden');
            delivererDoneBlock?.classList.add('is-open');
          }

          if (json.received_signature) {
            receiverSignBlock?.classList.add('is-hidden');
            receiverDoneBlock?.classList.add('is-open');
          }

          if (json.delivered_signature && json.received_signature) {
            allDoneCard.style.display = 'block';
          }
        } catch (e) {
          // silencioso
        }
      }

      setInterval(refreshPublicStatus, 3000);
      refreshPublicStatus();
    })();
  </script>
</body>
</html>