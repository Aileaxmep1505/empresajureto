{{--
   Guía paso a paso (tour) reutilizable.

   Se declara una vez por pantalla, normalmente al final de la vista:

     @push('scripts')
     <script>
       UITour.registrar('wms-conteos', {
         version: 1,          // súbele cuando cambies los pasos y se vuelve a mostrar
         auto: true,          // se muestra solo la primera vez que entran
         pasos: [
           { el: '[data-tour="crear"]', titulo: 'Empieza aquí',
             texto: 'Elige qué se va a contar y para quién.' },
           { titulo: 'Listo', texto: 'Paso sin elemento: sale centrado.' },
         ],
       });
     </script>
     @endpush

   Para el botón de "¿Cómo funciona?" basta con:
     <button type="button" data-tour-start="wms-conteos">¿Cómo funciona?</button>

   Un paso cuyo elemento no exista en la página se salta solo, así que la misma
   guía sirve aunque la pantalla esté vacía o el usuario no tenga permisos.
--}}
@include('partials.ui-tokens')

<div class="tour" id="uiTour" hidden>
  <div class="tour-velo" data-tour-velo></div>
  <div class="tour-foco" data-tour-foco aria-hidden="true"></div>

  <div class="tour-globo" data-tour-globo role="dialog" aria-modal="true" aria-labelledby="uiTourTitulo">
    <div class="tour-globo-head">
      <span class="tour-paso" data-tour-contador>Paso 1 de 3</span>
      <button type="button" class="tour-x" data-tour-cerrar aria-label="Cerrar la guía">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <h2 class="tour-titulo" id="uiTourTitulo" data-tour-titulo></h2>
    <p class="tour-texto" data-tour-texto></p>

    <div class="tour-pie">
      <div class="tour-puntos" data-tour-puntos aria-hidden="true"></div>
      <div class="tour-botones">
        <button type="button" class="tour-btn" data-tour-saltar>Saltar</button>
        <button type="button" class="tour-btn" data-tour-atras>Atrás</button>
        <button type="button" class="tour-btn is-primary" data-tour-siguiente>Siguiente</button>
      </div>
    </div>
  </div>
</div>

<style>
  .tour[hidden]{ display:none; }
  .tour{ position:fixed; inset:0; z-index:var(--ui-z-tip); }

  /* El velo tapa los clics; el hueco lo abre el recuadro del foco. */
  .tour-velo{ position:absolute; inset:0; }

  .tour-foco{ position:fixed; border-radius:var(--ui-r); pointer-events:none;
              box-shadow:0 0 0 9999px oklch(0.18 0.02 262 / .6), 0 0 0 2px var(--ui-accent);
              transition:top 260ms var(--ui-ease), left 260ms var(--ui-ease),
                         width 260ms var(--ui-ease), height 260ms var(--ui-ease); }
  .tour-foco.is-centro{ box-shadow:0 0 0 9999px oklch(0.18 0.02 262 / .6); }

  .tour-globo{ position:fixed; width:min(340px, calc(100vw - 24px)); padding:16px;
               background:var(--ui-surface); border:1px solid var(--ui-border); border-radius:var(--ui-r-lg);
               box-shadow:var(--ui-shadow-pop); color:var(--ui-ink);
               transition:top 260ms var(--ui-ease), left 260ms var(--ui-ease); }

  .tour-globo-head{ display:flex; align-items:center; gap:10px; margin-bottom:8px; }
  .tour-paso{ font-size:12px; font-weight:600; color:var(--ui-accent-ink); }
  .tour-x{ margin-left:auto; display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px;
           border:0; border-radius:var(--ui-r-sm); background:none; color:var(--ui-muted); cursor:pointer; }
  .tour-x svg{ width:15px; height:15px; }
  .tour-x:hover{ background:var(--ui-surface-3); color:var(--ui-ink); }

  .tour-titulo{ margin:0 0 5px; font-size:15px; font-weight:600; letter-spacing:-.01em; text-wrap:balance; }
  .tour-texto{ margin:0; font-size:13.5px; line-height:1.55; color:var(--ui-ink-2); text-wrap:pretty; }

  .tour-pie{ display:flex; align-items:center; gap:12px; margin-top:16px; }
  .tour-puntos{ display:flex; gap:5px; }
  .tour-puntos i{ width:6px; height:6px; border-radius:999px; background:var(--ui-border-strong); transition:background var(--ui-fast) var(--ui-ease); }
  .tour-puntos i.is-on{ background:var(--ui-accent); }
  .tour-botones{ margin-left:auto; display:flex; gap:6px; }

  .tour-btn{ height:32px; padding:0 12px; border:1px solid var(--ui-border-strong); border-radius:var(--ui-r);
             background:var(--ui-surface); color:var(--ui-ink-2); font:inherit; font-size:13px; font-weight:600; cursor:pointer;
             transition:background var(--ui-fast) var(--ui-ease), border-color var(--ui-fast) var(--ui-ease); }
  .tour-btn:hover{ background:var(--ui-surface-3); color:var(--ui-ink); }
  .tour-btn:focus-visible{ outline:2px solid var(--ui-accent); outline-offset:2px; }
  .tour-btn.is-primary{ background:var(--ui-accent); border-color:var(--ui-accent); color:#fff; }
  .tour-btn.is-primary:hover{ background:var(--ui-accent-hover); border-color:var(--ui-accent-hover); }
  .tour-btn[hidden]{ display:none; }

  /* Botón "¿Cómo funciona?" que cada pantalla coloca en su encabezado */
  .tour-abrir{ display:inline-flex; align-items:center; gap:6px; height:34px; padding:0 12px;
               border:1px solid var(--ui-border-strong); border-radius:var(--ui-r); background:var(--ui-surface);
               color:var(--ui-ink-2); font:inherit; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; white-space:nowrap;
               transition:background var(--ui-fast) var(--ui-ease), border-color var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease); }
  .tour-abrir:hover{ background:var(--ui-surface-3); color:var(--ui-ink); border-color:var(--ui-border-strong); }
  .tour-abrir svg{ width:15px; height:15px; }

  /* En el teléfono el globo es una hoja fija abajo */
  @media (max-width: 640px){
    .tour-globo{ left:8px !important; right:8px; top:auto !important; bottom:8px;
                 width:auto; max-width:none; padding-bottom:14px; }
    .tour-pie{ flex-wrap:wrap; }
    .tour-botones{ width:100%; margin-left:0; }
    .tour-botones .tour-btn{ flex:1; height:40px; }
    .tour-puntos{ width:100%; justify-content:center; order:-1; margin-bottom:4px; }
  }
  @media (prefers-reduced-motion: reduce){
    .tour-foco, .tour-globo, .tour-puntos i{ transition:none; }
  }
</style>

<script>
window.UITour = (function () {
  const caja     = document.getElementById('uiTour');
  const foco     = caja.querySelector('[data-tour-foco]');
  const globo    = caja.querySelector('[data-tour-globo]');
  const elTit    = caja.querySelector('[data-tour-titulo]');
  const elTxt    = caja.querySelector('[data-tour-texto]');
  const elCont   = caja.querySelector('[data-tour-contador]');
  const elPuntos = caja.querySelector('[data-tour-puntos]');
  const btnAtras = caja.querySelector('[data-tour-atras]');
  const btnSig   = caja.querySelector('[data-tour-siguiente]');
  const btnSaltar= caja.querySelector('[data-tour-saltar]');

  const guias = {};
  let activa = null, pasos = [], i = 0, antesDeAbrir = null;

  const movil = () => window.matchMedia('(max-width: 640px)').matches;
  const llave = (clave, v) => 'tour:' + clave + ':v' + (v || 1);

  function visto(clave) {
    const g = guias[clave];
    try { return localStorage.getItem(llave(clave, g?.version)) === '1'; } catch { return false; }
  }
  function marcarVisto(clave) {
    const g = guias[clave];
    try { localStorage.setItem(llave(clave, g?.version), '1'); } catch {}
  }

  function registrar(clave, config) {
    guias[clave] = Object.assign({ version: 1, auto: false, pasos: [] }, config);

    // Los botones de "¿Cómo funciona?" siempre funcionan, aunque ya se haya visto.
    document.querySelectorAll('[data-tour-start="' + clave + '"]').forEach(b => {
      if (b.dataset.tourListo) return;
      b.dataset.tourListo = '1';
      b.addEventListener('click', () => iniciar(clave, { forzar: true }));
    });

    if (guias[clave].auto && !visto(clave)) {
      // Se espera a que la página asiente antes de apuntar a un elemento.
      setTimeout(() => { if (!activa) iniciar(clave); }, 700);
    }
  }

  function iniciar(clave, opciones) {
    const g = guias[clave];
    if (!g) return;
    if (!opciones?.forzar && visto(clave)) return;

    // Se descartan los pasos cuyo elemento no está en esta pantalla.
    pasos = g.pasos.filter(p => !p.el || document.querySelector(p.el));
    if (!pasos.length) return;

    activa = clave;
    i = 0;
    antesDeAbrir = document.activeElement;
    caja.hidden = false;
    document.addEventListener('keydown', teclas, true);
    window.addEventListener('resize', recolocar);
    window.addEventListener('scroll', recolocar, true);
    pintar();
  }

  function cerrar(completado) {
    if (!activa) return;
    if (completado) marcarVisto(activa);
    activa = null;
    caja.hidden = true;
    document.removeEventListener('keydown', teclas, true);
    window.removeEventListener('resize', recolocar);
    window.removeEventListener('scroll', recolocar, true);
    try { antesDeAbrir?.focus?.(); } catch {}
  }

  function teclas(e) {
    if (e.key === 'Escape')      { e.preventDefault(); cerrar(true); }
    else if (e.key === 'ArrowRight' || e.key === 'Enter') { e.preventDefault(); avanzar(1); }
    else if (e.key === 'ArrowLeft')  { e.preventDefault(); avanzar(-1); }
  }

  function avanzar(d) {
    const n = i + d;
    if (n < 0) return;
    if (n >= pasos.length) return cerrar(true);
    i = n;
    pintar();
  }

  function pintar() {
    const p = pasos[i];

    elTit.textContent = p.titulo || '';
    elTxt.textContent = p.texto || '';
    elCont.textContent = 'Paso ' + (i + 1) + ' de ' + pasos.length;

    elPuntos.innerHTML = '';
    pasos.forEach((_, n) => {
      const d = document.createElement('i');
      if (n === i) d.className = 'is-on';
      elPuntos.appendChild(d);
    });

    btnAtras.hidden = i === 0;
    btnSaltar.hidden = i === pasos.length - 1;
    btnSig.textContent = i === pasos.length - 1 ? 'Entendido' : 'Siguiente';

    const el = p.el ? document.querySelector(p.el) : null;
    if (el) {
      const r = el.getBoundingClientRect();
      const fuera = r.top < 60 || r.bottom > innerHeight - 60;
      if (fuera) {
        el.scrollIntoView({ behavior: movil() ? 'auto' : 'smooth', block: 'center' });
        setTimeout(recolocar, 320);
      }
    }
    recolocar();
    btnSig.focus({ preventScroll: true });
  }

  function recolocar() {
    if (!activa) return;
    const p = pasos[i];
    const el = p?.el ? document.querySelector(p.el) : null;

    // Paso sin elemento: solo se oscurece la pantalla y el globo va al centro.
    if (!el) {
      foco.classList.add('is-centro');
      foco.style.cssText = 'top:50%;left:50%;width:0;height:0;border-radius:0;';
      if (!movil()) {
        globo.style.top = Math.round(innerHeight / 2 - globo.offsetHeight / 2) + 'px';
        globo.style.left = Math.round(innerWidth / 2 - globo.offsetWidth / 2) + 'px';
      }
      return;
    }

    foco.classList.remove('is-centro');
    const r = el.getBoundingClientRect();
    const pad = 6;
    const top = Math.max(4, r.top - pad);
    const left = Math.max(4, r.left - pad);
    const ancho = Math.min(innerWidth - left - 4, r.width + pad * 2);
    const alto = Math.min(innerHeight - top - 4, r.height + pad * 2);

    foco.style.top = top + 'px';
    foco.style.left = left + 'px';
    foco.style.width = ancho + 'px';
    foco.style.height = alto + 'px';

    if (movil()) return; // en el teléfono el globo vive abajo, fijo por CSS

    const gw = globo.offsetWidth, gh = globo.offsetHeight, hueco = 12;
    let gTop = top + alto + hueco;
    if (gTop + gh > innerHeight - 8) gTop = Math.max(8, top - gh - hueco);   // no cabe abajo: va arriba
    if (gTop < 8) gTop = Math.min(innerHeight - gh - 8, top);                // tampoco arriba: al costado

    let gLeft = left + ancho / 2 - gw / 2;
    gLeft = Math.max(8, Math.min(innerWidth - gw - 8, gLeft));

    globo.style.top = Math.round(gTop) + 'px';
    globo.style.left = Math.round(gLeft) + 'px';
  }

  btnSig.addEventListener('click', () => avanzar(1));
  btnAtras.addEventListener('click', () => avanzar(-1));
  btnSaltar.addEventListener('click', () => cerrar(true));
  caja.querySelector('[data-tour-cerrar]').addEventListener('click', () => cerrar(true));
  caja.querySelector('[data-tour-velo]').addEventListener('click', () => cerrar(true));

  return { registrar, iniciar, cerrar, visto };
})();
</script>
