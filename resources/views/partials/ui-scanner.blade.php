{{--
   Escáner con la cámara del celular (o de la laptop), reutilizable.

   Cómo se usa desde cualquier pantalla:

     1) Botón automático en un campo de texto:
        <input type="text" name="sku" data-scan>
        ...y aparece el botón de cámara a la derecha; al leer, escribe el
        código en ese campo y dispara los eventos input/change.

     2) A mano, cuando quieres controlar qué pasa al leer:
        UIScanner.abrir({
          titulo: 'Escanea la ubicación',
          ayuda: 'Apunta al código QR del rack.',
          alLeer: (codigo) => { ... }   // devuelve true para cerrar, false para seguir
        });

   Funciona con la pistola de escaneo igual que antes: la pistola escribe en el
   campo enfocado, no necesita este diálogo.

   IMPORTANTE: los navegadores solo dan acceso a la cámara en HTTPS o en
   localhost. Si la página se abre por IP con http:// el diálogo lo explica y
   ofrece la captura manual.
--}}
@include('partials.ui-tokens')

<dialog class="scan" id="uiScanner" aria-labelledby="uiScannerTitulo">
  <div class="scan-box">
    <header class="scan-head">
      <div class="scan-head-txt">
        <h2 class="scan-titulo" id="uiScannerTitulo">Escanear código</h2>
        <p class="scan-ayuda" data-scan-ayuda>Apunta la cámara al código de barras o QR.</p>
      </div>
      <button type="button" class="scan-x" data-scan-cerrar aria-label="Cerrar escáner">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </header>

    <div class="scan-visor" data-scan-visor>
      <video class="scan-video" data-scan-video playsinline muted autoplay></video>

      {{-- Marco de puntería: la lectura funciona en todo el cuadro, el marco solo guía --}}
      <div class="scan-marco" aria-hidden="true"><i></i><i></i><i></i><i></i></div>

      <div class="scan-aviso" data-scan-aviso hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
          <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>
        </svg>
        <p data-scan-aviso-txt></p>
      </div>

      <div class="scan-ok" data-scan-ok hidden aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
      </div>
    </div>

    <div class="scan-estado">
      <span class="scan-punto" data-scan-punto></span>
      <span data-scan-estado>Preparando la cámara…</span>
      <div class="scan-herramientas">
        <button type="button" class="scan-tool" data-scan-linterna hidden>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 2h6v4l-2 3v11a1 1 0 0 1-2 0V9L9 6z"/></svg>
          Luz
        </button>
        <button type="button" class="scan-tool" data-scan-cambiar hidden>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/><path d="M3 21v-5h5"/></svg>
          Voltear
        </button>
      </div>
    </div>

    <form class="scan-manual" data-scan-manual>
      <label for="uiScannerManual">…o escríbelo / dispara con la pistola</label>
      <div class="scan-manual-row">
        <input id="uiScannerManual" type="text" inputmode="search" autocomplete="off" placeholder="Código, SKU o ubicación" data-scan-input>
        <button type="submit" class="scan-btn">Usar</button>
      </div>
    </form>
  </div>
</dialog>

<style>
  .scan{ padding:0; border:0; background:transparent; max-width:none; max-height:none; color:var(--ui-ink); }
  .scan::backdrop{ background:oklch(0.18 0.02 262 / .72); }

  .scan-box{ display:flex; flex-direction:column; width:min(520px, calc(100vw - 24px)); max-height:min(92vh, 760px);
             background:var(--ui-surface); border:1px solid var(--ui-border); border-radius:var(--ui-r-lg);
             box-shadow:var(--ui-shadow-pop); overflow:hidden; font-family:inherit; }

  .scan-head{ display:flex; align-items:flex-start; gap:12px; padding:14px 16px; border-bottom:1px solid var(--ui-border); }
  .scan-head-txt{ min-width:0; flex:1; }
  .scan-titulo{ margin:0; font-size:15px; font-weight:600; }
  .scan-ayuda{ margin:3px 0 0; font-size:13px; color:var(--ui-muted); line-height:1.4; }
  .scan-x{ flex:0 0 auto; display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px;
           border:0; border-radius:var(--ui-r-sm); background:none; color:var(--ui-muted); cursor:pointer; }
  .scan-x svg{ width:17px; height:17px; }
  .scan-x:hover{ background:var(--ui-surface-3); color:var(--ui-ink); }

  /* Visor */
  .scan-visor{ position:relative; background:oklch(0.18 0.02 262); aspect-ratio:4 / 3; overflow:hidden; }
  .scan-video{ width:100%; height:100%; object-fit:cover; display:block; }

  .scan-marco{ position:absolute; inset:14% 10%; pointer-events:none; }
  .scan-marco i{ position:absolute; width:26px; height:26px; border:2.5px solid #fff; opacity:.9; }
  .scan-marco i:nth-child(1){ top:0; left:0; border-right:0; border-bottom:0; border-top-left-radius:6px; }
  .scan-marco i:nth-child(2){ top:0; right:0; border-left:0; border-bottom:0; border-top-right-radius:6px; }
  .scan-marco i:nth-child(3){ bottom:0; left:0; border-right:0; border-top:0; border-bottom-left-radius:6px; }
  .scan-marco i:nth-child(4){ bottom:0; right:0; border-left:0; border-top:0; border-bottom-right-radius:6px; }

  .scan-aviso{ position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px;
               padding:24px; text-align:center; background:oklch(0.18 0.02 262); color:oklch(0.93 0.01 262); }
  .scan-aviso svg{ width:30px; height:30px; color:var(--ui-warn); }
  .scan-aviso p{ margin:0; max-width:46ch; font-size:13.5px; line-height:1.55; }
  .scan-aviso[hidden]{ display:none; }

  .scan-ok{ position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
            background:oklch(0.62 0.15 155 / .9); color:#fff; animation:scan-ok-in 220ms var(--ui-ease); }
  .scan-ok[hidden]{ display:none; }
  .scan-ok svg{ width:56px; height:56px; }
  @keyframes scan-ok-in{ from{ opacity:0; } to{ opacity:1; } }

  /* Estado */
  .scan-estado{ display:flex; align-items:center; gap:8px; padding:10px 16px; border-bottom:1px solid var(--ui-border);
                font-size:13px; color:var(--ui-ink-2); }
  .scan-punto{ flex:0 0 auto; width:7px; height:7px; border-radius:999px; background:var(--ui-faint); }
  .scan-punto.is-on{ background:var(--ui-ok); }
  .scan-punto.is-err{ background:var(--ui-danger); }
  .scan-herramientas{ margin-left:auto; display:flex; gap:6px; }
  .scan-tool{ display:inline-flex; align-items:center; gap:5px; height:28px; padding:0 9px; border:1px solid var(--ui-border-strong);
              border-radius:var(--ui-r); background:var(--ui-surface); color:var(--ui-ink-2); font:inherit; font-size:12.5px; font-weight:500; cursor:pointer; }
  .scan-tool svg{ width:14px; height:14px; }
  .scan-tool:hover{ background:var(--ui-surface-3); }
  .scan-tool.is-on{ background:var(--ui-accent-soft); border-color:var(--ui-accent); color:var(--ui-accent-ink); }
  .scan-tool[hidden]{ display:none; }

  /* Captura manual */
  .scan-manual{ padding:12px 16px 14px; background:var(--ui-surface-2); }
  .scan-manual label{ display:block; margin-bottom:6px; font-size:12.5px; color:var(--ui-muted); }
  .scan-manual-row{ display:flex; gap:8px; }
  .scan-manual input{ flex:1; min-width:0; height:36px; padding:0 10px; border:1px solid var(--ui-border-strong);
                      border-radius:var(--ui-r); background:var(--ui-surface); font:inherit; font-size:14px; color:var(--ui-ink); outline:0; }
  .scan-manual input:focus{ border-color:var(--ui-accent); box-shadow:0 0 0 3px var(--ui-accent-ring); }
  .scan-btn{ flex:0 0 auto; height:36px; padding:0 14px; border:1px solid var(--ui-accent); border-radius:var(--ui-r);
             background:var(--ui-accent); color:#fff; font:inherit; font-size:13px; font-weight:600; cursor:pointer; }
  .scan-btn:hover{ background:var(--ui-accent-hover); border-color:var(--ui-accent-hover); }

  /* Botón de cámara que se inyecta junto a los campos con data-scan */
  .scan-campo{ position:relative; display:flex; align-items:center; }
  .scan-campo > input{ flex:1; min-width:0; padding-right:40px !important; }
  .scan-lanzar{ position:absolute; right:5px; display:inline-flex; align-items:center; justify-content:center;
                width:28px; height:28px; border:0; border-radius:var(--ui-r-sm); background:none; color:var(--ui-muted); cursor:pointer;
                transition:background var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease); }
  .scan-lanzar svg{ width:17px; height:17px; }
  .scan-lanzar:hover{ background:var(--ui-surface-3); color:var(--ui-accent-ink); }
  .scan-lanzar:focus-visible{ outline:2px solid var(--ui-accent); outline-offset:1px; }

  /* En el teléfono el diálogo ocupa toda la pantalla */
  @media (max-width: 560px){
    .scan-box{ width:100vw; max-width:100vw; height:100dvh; max-height:100dvh; border:0; border-radius:0; }
    .scan-visor{ flex:1; aspect-ratio:auto; min-height:0; }
    .scan-manual input, .scan-btn{ height:44px; }
    .scan-manual input{ font-size:16px; } /* 16px evita que iOS haga zoom al enfocar */
  }
  @media (prefers-reduced-motion: reduce){
    .scan-ok{ animation:none; }
  }
</style>

<script>
window.UIScanner = (function () {
  const dlg     = document.getElementById('uiScanner');
  const video   = dlg.querySelector('[data-scan-video]');
  const elTit   = dlg.querySelector('[data-scan-titulo], #uiScannerTitulo');
  const elAyuda = dlg.querySelector('[data-scan-ayuda]');
  const elEstado= dlg.querySelector('[data-scan-estado]');
  const elPunto = dlg.querySelector('[data-scan-punto]');
  const elAviso = dlg.querySelector('[data-scan-aviso]');
  const elAvisoT= dlg.querySelector('[data-scan-aviso-txt]');
  const elOk    = dlg.querySelector('[data-scan-ok]');
  const btnLuz  = dlg.querySelector('[data-scan-linterna]');
  const btnCam  = dlg.querySelector('[data-scan-cambiar]');
  const formMan = dlg.querySelector('[data-scan-manual]');
  const inputMan= dlg.querySelector('[data-scan-input]');

  const FORMATOS = ['qr_code','ean_13','ean_8','code_128','code_39','upc_a','upc_e','itf','codabar','data_matrix','pdf417'];
  const ZXING_CDN = 'https://cdn.jsdelivr.net/npm/@zxing/browser@0.1.5/umd/index.min.js';

  let stream = null, corriendo = false, alLeer = null, zxCtrl = null;
  let camaras = [], camIdx = 0, ultimo = '', ultimoT = 0;

  const esSeguro = () => window.isSecureContext || ['localhost','127.0.0.1'].includes(location.hostname);

  function estado(txt, tipo) {
    elEstado.textContent = txt;
    elPunto.className = 'scan-punto' + (tipo === 'ok' ? ' is-on' : tipo === 'err' ? ' is-err' : '');
  }

  function aviso(txt) {
    elAvisoT.textContent = txt;
    elAviso.hidden = false;
  }

  // ---------- Cámara ----------
  async function listarCamaras() {
    try {
      const d = await navigator.mediaDevices.enumerateDevices();
      camaras = d.filter(x => x.kind === 'videoinput');
      btnCam.hidden = camaras.length < 2;
    } catch { camaras = []; }
  }

  async function abrirCamara() {
    if (!esSeguro()) {
      aviso('Por seguridad, el navegador solo permite usar la cámara en páginas https:// o en localhost. Esta página se abrió por http://, así que escribe el código abajo o dispara con la pistola.');
      estado('Cámara no disponible aquí', 'err');
      inputMan.focus();
      return false;
    }
    if (!navigator.mediaDevices?.getUserMedia) {
      aviso('Este navegador no permite usar la cámara. Escribe el código abajo o usa la pistola de escaneo.');
      estado('Sin soporte de cámara', 'err');
      return false;
    }

    try {
      const pedir = camaras.length && camaras[camIdx]
        ? { deviceId: { exact: camaras[camIdx].deviceId } }
        : { facingMode: { ideal: 'environment' } };

      stream = await navigator.mediaDevices.getUserMedia({
        video: { ...pedir, width: { ideal: 1280 }, height: { ideal: 720 } },
        audio: false,
      });

      video.srcObject = stream;
      await Promise.race([
        new Promise(r => video.addEventListener('loadedmetadata', r, { once: true })),
        new Promise(r => setTimeout(r, 1200)),
      ]);
      try { await video.play(); } catch {}

      elAviso.hidden = true;
      if (!camaras.length) await listarCamaras();

      // La linterna solo existe en algunos teléfonos.
      const pista = stream.getVideoTracks()[0];
      btnLuz.hidden = !(pista?.getCapabilities?.().torch);
      btnLuz.classList.remove('is-on');

      estado('Buscando código…', 'ok');
      return true;
    } catch (e) {
      const negado = e?.name === 'NotAllowedError' || e?.name === 'SecurityError';
      aviso(negado
        ? 'No diste permiso para usar la cámara. Ábrelo desde el candado de la barra de direcciones, o escribe el código abajo.'
        : 'No se pudo encender la cámara (' + (e?.name || 'error') + '). Escribe el código abajo o usa la pistola.');
      estado(negado ? 'Permiso denegado' : 'Cámara no disponible', 'err');
      inputMan.focus();
      return false;
    }
  }

  function cerrarCamara() {
    corriendo = false;
    if (zxCtrl?.stop) { try { zxCtrl.stop(); } catch {} }
    zxCtrl = null;
    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
    video.srcObject = null;
  }

  // ---------- Lectura ----------
  // Primero el lector nativo del navegador (rápido y sin descargar nada);
  // si no existe, se baja ZXing.
  async function leerNativo() {
    if (!('BarcodeDetector' in window)) return false;

    let det;
    try { det = new window.BarcodeDetector({ formats: FORMATOS }); }
    catch { try { det = new window.BarcodeDetector(); } catch { return false; } }

    const paso = async () => {
      if (!corriendo) return;
      try {
        const hits = await det.detect(video);
        if (hits?.length && hits[0].rawValue) return acertar(hits[0].rawValue);
      } catch {}
      requestAnimationFrame(paso);
    };
    requestAnimationFrame(paso);
    return true;
  }

  async function leerZXing() {
    try {
      if (!window.ZXingBrowser && !window.ZXing) {
        await new Promise((res, rej) => {
          const s = document.createElement('script');
          s.src = ZXING_CDN; s.async = true; s.onload = res; s.onerror = rej;
          document.head.appendChild(s);
        });
      }
      const ns = window.ZXingBrowser || window.ZXing;
      const Reader = ns?.BrowserMultiFormatReader;
      if (!Reader) throw new Error('sin ZXing');

      const lector = new Reader();
      zxCtrl = await lector.decodeFromVideoElement(video, (res) => {
        if (res && corriendo) acertar(res.getText ? res.getText() : String(res));
      });
      return true;
    } catch {
      estado('Lectura automática no disponible; escribe el código', 'err');
      return false;
    }
  }

  function acertar(codigo) {
    const txt = String(codigo).trim();
    const ahora = Date.now();

    // El mismo código seguido se ignora 1.2 s para no leerlo dos veces.
    if (!txt || (txt === ultimo && ahora - ultimoT < 1200)) return;
    ultimo = txt; ultimoT = ahora;

    try { navigator.vibrate?.(60); } catch {}
    pitido();

    elOk.hidden = false;
    setTimeout(() => { elOk.hidden = true; }, 320);

    estado('Leído: ' + txt, 'ok');
    const seguir = alLeer ? alLeer(txt) : true;
    if (seguir !== false) cerrar();
  }

  function pitido() {
    try {
      const Ctx = window.AudioContext || window.webkitAudioContext;
      if (!Ctx) return;
      const ctx = new Ctx(), osc = ctx.createOscillator(), vol = ctx.createGain();
      osc.frequency.value = 880; vol.gain.value = 0.06;
      osc.connect(vol); vol.connect(ctx.destination);
      osc.start(); osc.stop(ctx.currentTime + 0.09);
      setTimeout(() => ctx.close(), 300);
    } catch {}
  }

  // ---------- Abrir / cerrar ----------
  async function abrir(opciones) {
    const o = opciones || {};
    alLeer = o.alLeer || null;
    ultimo = ''; ultimoT = 0;

    elTit.textContent = o.titulo || 'Escanear código';
    elAyuda.textContent = o.ayuda || 'Apunta la cámara al código de barras o QR.';
    inputMan.value = '';
    elAviso.hidden = true;
    elOk.hidden = true;
    estado('Preparando la cámara…');

    if (typeof dlg.showModal === 'function') { if (!dlg.open) dlg.showModal(); }
    else dlg.setAttribute('open', '');

    corriendo = true;
    if (await abrirCamara()) {
      if (!(await leerNativo())) await leerZXing();
    }
  }

  function cerrar() {
    cerrarCamara();
    alLeer = null;
    if (dlg.open) dlg.close(); else dlg.removeAttribute('open');
  }

  // ---------- Eventos del diálogo ----------
  dlg.querySelectorAll('[data-scan-cerrar]').forEach(b => b.addEventListener('click', cerrar));
  dlg.addEventListener('close', cerrarCamara);
  dlg.addEventListener('cancel', () => cerrarCamara());
  dlg.addEventListener('click', (e) => { if (e.target === dlg) cerrar(); });

  formMan.addEventListener('submit', (e) => {
    e.preventDefault();
    const v = inputMan.value.trim();
    if (v) { ultimo = ''; acertar(v); }
  });

  btnLuz.addEventListener('click', async () => {
    const pista = stream?.getVideoTracks?.()[0];
    if (!pista) return;
    const on = !btnLuz.classList.contains('is-on');
    try {
      await pista.applyConstraints({ advanced: [{ torch: on }] });
      btnLuz.classList.toggle('is-on', on);
    } catch {}
  });

  btnCam.addEventListener('click', async () => {
    if (camaras.length < 2) return;
    camIdx = (camIdx + 1) % camaras.length;
    cerrarCamara();
    corriendo = true;
    if (await abrirCamara()) { if (!(await leerNativo())) await leerZXing(); }
  });

  // ---------- Campos con data-scan ----------
  // Le pone un botón de cámara a cualquier <input data-scan> de la página.
  function equipar(raiz) {
    (raiz || document).querySelectorAll('input[data-scan]:not([data-scan-listo])').forEach(input => {
      input.setAttribute('data-scan-listo', '');

      const caja = document.createElement('div');
      caja.className = 'scan-campo';
      input.parentNode.insertBefore(caja, input);
      caja.appendChild(input);

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'scan-lanzar';
      btn.title = 'Escanear con la cámara';
      btn.setAttribute('aria-label', 'Escanear con la cámara');
      btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h10"/></svg>';
      caja.appendChild(btn);

      btn.addEventListener('click', () => abrir({
        titulo: input.dataset.scanTitulo || 'Escanear código',
        ayuda: input.dataset.scanAyuda || 'Apunta la cámara al código de barras o QR.',
        alLeer: (codigo) => {
          input.value = codigo;
          input.dispatchEvent(new Event('input', { bubbles: true }));
          input.dispatchEvent(new Event('change', { bubbles: true }));
          if (input.dataset.scanEnviar !== 'no') input.form?.requestSubmit?.();
          return true;
        },
      }));
    });
  }

  document.addEventListener('DOMContentLoaded', () => equipar());
  equipar();

  return { abrir, cerrar, equipar, disponible: esSeguro };
})();
</script>
