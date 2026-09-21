{{-- Toast de confirmación (estilo Obsidiana), usando los ui-tokens.
     Uso desde JS: window.showToast('Mensaje', 'ok'|'error').
     Muestra solo las notificaciones flash (session ok/error) al cargar. --}}
@push('styles')
<style>
  .jt-toast{ position:fixed; top:22px; right:22px; z-index:var(--ui-z-tip, 9999);
             display:flex; align-items:center; gap:12px; padding:13px 16px 13px 13px; border-radius:15px;
             background:var(--ui-surface); color:var(--ui-ink); border:1px solid var(--ui-border);
             box-shadow:var(--ui-shadow-pop); opacity:0; pointer-events:none;
             transform:translateX(120%); transition:opacity .3s ease, transform .34s cubic-bezier(.22,1,.36,1);
             font-size:14px; font-weight:600; max-width:min(360px, calc(100% - 44px)); }
  .jt-toast.show{ opacity:1; transform:translateX(0); pointer-events:auto; }
  .jt-toast .jt-toast-ico{ width:34px; height:34px; border-radius:10px; flex:0 0 auto;
             display:flex; align-items:center; justify-content:center;
             background:var(--ui-ok-soft); color:var(--ui-ok-ink); }
  .jt-toast .jt-toast-ico.err{ background:var(--ui-danger-soft); color:var(--ui-danger-ink); }
  .jt-toast .jt-toast-ico svg{ width:19px; height:19px; }
  .jt-toast .jt-toast-msg{ line-height:1.35; }
  .jt-toast .jt-toast-x{ margin-left:4px; display:grid; place-items:center; width:24px; height:24px; border:0;
             border-radius:8px; background:none; color:var(--ui-muted); cursor:pointer; flex:0 0 auto; }
  .jt-toast .jt-toast-x:hover{ background:var(--ui-surface-3); color:var(--ui-ink); }
  .jt-toast .jt-toast-x svg{ width:15px; height:15px; }
  @media (max-width:640px){ .jt-toast{ top:14px; right:14px; left:14px; max-width:none; } }
</style>
@endpush

<div class="jt-toast" id="jtToast" role="status" aria-live="polite">
  <span class="jt-toast-ico" id="jtToastIco"></span>
  <span class="jt-toast-msg" id="jtToastMsg"></span>
  <button type="button" class="jt-toast-x" id="jtToastX" aria-label="Cerrar">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
  </button>
</div>

@push('scripts')
<script>
(function(){
  var OK  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
  var ERR = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>';
  var t = document.getElementById('jtToast');
  var msgEl = document.getElementById('jtToastMsg');
  var icoEl = document.getElementById('jtToastIco');
  if(!t) return;

  window.showToast = function(msg, type){
    var esErr = type === 'error' || type === 'err' || type === 'warn';
    msgEl.textContent = msg;
    icoEl.classList.toggle('err', esErr);
    icoEl.innerHTML = esErr ? ERR : OK;
    t.classList.add('show');
    clearTimeout(window._jtToast);
    window._jtToast = setTimeout(function(){ t.classList.remove('show'); }, 3800);
  };
  document.getElementById('jtToastX').addEventListener('click', function(){ t.classList.remove('show'); });

  @if(session('ok'))
    window.showToast(@json(session('ok')), 'ok');
  @endif
  @if(session('error'))
    window.showToast(@json(session('error')), 'error');
  @endif
})();
</script>
@endpush
