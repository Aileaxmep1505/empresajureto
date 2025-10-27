<style>
  #toast-root{
    position: fixed; inset: 12px 12px auto auto; z-index: 9999;
    display: flex; flex-direction: column; gap: 10px;
    pointer-events: none;
  }
  .toast{
    --bg: rgba(15,23,42,.86);
    --ink: #e5e7eb;
    --accent: #a3d5ff;
    --ring: rgba(163,213,255,.25);
    min-width: 280px; max-width: 380px;
    color: var(--ink);
    background: var(--bg);
    backdrop-filter: blur(10px);
    border: 1px solid var(--ring);
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(2,8,23,.25);
    padding: 12px 14px 10px 12px;
    display: grid; grid-template-columns: 28px 1fr auto; gap: 10px;
    pointer-events: auto;
    transform: translateY(-8px); opacity: 0;
    animation: toast-in .35s ease forwards;
  }
  .toast--success { --bg: rgba(15,23,42,.86); --accent:#86efac; --ring: rgba(134,239,172,.25); }
  .toast--info    { --bg: rgba(15,23,42,.86); --accent:#93c5fd; --ring: rgba(147,197,253,.25); }
  .toast--warning { --bg: rgba(15,23,42,.86); --accent:#fcd34d; --ring: rgba(252,211,77,.25); }
  .toast__icon{
    width:28px;height:28px;border-radius:999px;
    display:grid;place-items:center;
    background: radial-gradient(120% 120% at 30% 30%, var(--accent), transparent 70%);
    border:1px solid var(--ring);
  }
  .toast__title{ font-weight:800; line-height:1.15; margin-top:1px; }
  .toast__msg{ font-size:.92rem; opacity:.9 }
  .toast__close{
    width:30px;height:30px;border-radius:10px;border:0;background:transparent;color:#cbd5e1;
    display:grid;place-items:center; cursor:pointer;
  }
  .toast__close:hover{ background: rgba(255,255,255,.06); color:#fff; }
  .toast__bar{
    grid-column: 1 / -1; height:3px; border-radius:999px;
    background: linear-gradient(90deg, var(--accent), transparent);
    transform-origin:left center;
    animation: toast-bar linear forwards;
  }
  @keyframes toast-in{
    to { transform: translateY(0); opacity: 1; }
  }
  @keyframes toast-out{
    to { transform: translateY(-8px); opacity: 0; }
  }
</style>

<div id="toast-root" aria-live="polite" aria-atomic="true"></div>

<script>
  (function(){
    const root = document.getElementById('toast-root');

    function svgFor(type){
      if(type==='success') return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M20 7L10 17L4 11" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
      if(type==='warning') return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
      return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M13 16h-1v-4h-1m1-4h.01" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    window.showToast = function({type='info', title='', message='', duration=4000}={}){
      const el = document.createElement('div');
      el.className = `toast toast--${type}`;
      el.innerHTML = `
        <div class="toast__icon">${svgFor(type)}</div>
        <div>
          <div class="toast__title">${title ? title : ''}</div>
          ${message ? `<div class="toast__msg">${message}</div>` : ''}
        </div>
        <button class="toast__close" aria-label="Cerrar" type="button">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
        <div class="toast__bar" style="animation-duration:${Math.max(800, duration)}ms"></div>
      `;
      root.appendChild(el);

      let closing = false;
      const close = () => {
        if (closing) return;
        closing = true;
        el.style.animation = 'toast-out .25s ease forwards';
        setTimeout(()=> el.remove(), 220);
      };

      const timer = setTimeout(close, duration);
      el.querySelector('.toast__close').addEventListener('click', () => { clearTimeout(timer); close(); });
    };

    // Si viene un toast de sesión, muéstralo
    @if(session('toast'))
      try {
        const t = @json(session('toast'));
        window.showToast(t || {});
      } catch(e) {}
    @endif
  })();
</script>
