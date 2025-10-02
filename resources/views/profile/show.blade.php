@extends('layouts.app') 
@section('title','Mi perfil')
@section('titulo','Mi perfil')

@push('styles')
<link href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet">
<style>
:root{
  --bg:#f6f7fb; --surface:#fff; --ink:#0f172a; --muted:#667085; --line:#e6e8ef;
  --brand:#7ea2ff; --brand-ink:#14206a; --ok:#16a34a; --danger:#ef4444;
  --r:22px; --shadow-lg:0 28px 70px rgba(18,38,63,.12);
}
html,body{background:var(--bg)}
.idc-wrap{max-width:1100px;margin:84px auto 32px;padding:0 18px}
.idc-grid{display:grid;grid-template-columns:minmax(280px,520px);justify-content:center;gap:24px}

/* ====== FLIP 3D ====== */
.idc-flip{
  position:relative;perspective:1400px;height:600px; --rx:0; --ry:0
}
.idc-flip__inner{
  position:relative;width:100%;height:100%;
  transform-style:preserve-3d;transition:transform .9s cubic-bezier(.2,.7,.2,1);will-change:transform
}
@media (hover:hover){ .idc-flip:hover .idc-flip__inner{ transform:rotateX(var(--rx)) rotateY(var(--ry)) translateZ(0) scale(1.01) } }
.idc-flip[data-flipped="true"] .idc-flip__inner{ transform:rotateY(180deg) }

/* Caras */
.idc-card{
  position:absolute;inset:0;background:var(--surface);border:1px solid var(--line);
  border-radius:var(--r);box-shadow:var(--shadow-lg);overflow:hidden;
  backface-visibility:hidden;-webkit-backface-visibility:hidden;isolation:isolate;
}
.idc-card--front::before{
  content:"";position:absolute;inset:-40% -40% auto auto;height:130%;width:40%;
  background:linear-gradient(120deg, rgba(255,255,255,.38), rgba(255,255,255,0));
  transform:skewX(-18deg);pointer-events:none;filter:blur(18px);transition:transform .6s ease
}
.idc-card--front:hover::before{ transform:skewX(-18deg) translateX(12px) }
.idc-card--back{ transform:rotateY(180deg) }

.card-head{
  position:relative;height:150px;border-bottom:1px solid var(--line);
  background:
    radial-gradient(120% 120% at 0% 0%, #e7efff 0%, transparent 55%),
    radial-gradient(120% 120% at 100% 0%, #f4e8ff 0%, transparent 60%),
    linear-gradient(180deg, #ffffff, #f7f8ff);
}
.card-brand{position:absolute;left:16px;top:16px;display:flex;align-items:center;gap:10px;z-index:2}
.card-brand img{height:28px}
.card-brand span{font-weight:800;color:#1e293b;letter-spacing:.2px}

/* Avatar (NAMESPACED para evitar conflicto con layout) */
.avatar-wrap{
  position:absolute;left:50%;bottom:-58px;transform:translateX(-50%);
  width:118px;height:118px;border-radius:50%;cursor:pointer;background:#fff;
  border:1px solid var(--line);box-shadow:0 8px 40px rgba(15,23,42,.18), inset 0 0 0 8px rgba(126,162,255,.10);
  display:grid;place-items:center;overflow:hidden;
}
.idc-avatar{width:100%;height:100%;border-radius:50%;object-fit:cover;display:block}

/* Body */
.card-body{padding:90px 18px 18px}
.row{display:flex;gap:12px;flex-wrap:wrap}
.col{flex:1;min-width:220px}
.label{font-size:12px;color:var(--muted);margin:0 0 6px 2px;display:block}
.val{background:#fff;border:1px solid var(--line);border-radius:14px;padding:12px 14px;color:var(--ink)}
.badges{margin-top:10px;display:flex;gap:8px;flex-wrap:wrap}
.badge{font-size:11px;border:1px solid #dbe3ff;background:#f3f6ff;color:#263a8b;padding:6px 10px;border-radius:999px}

/* Back list + form */
.back-body{padding:18px}
.item{display:flex;justify-content:space-between;gap:12px;border-bottom:1px dashed var(--line);padding:10px 0}
.item span:first-child{color:var(--muted);font-size:12px}
.item span:last-child{font-weight:600}
.input{width:100%;background:#fff;border:1px solid var(--line);border-radius:12px;padding:12px 14px;font:inherit}

/* Alerts */
.alert{border-radius:14px;padding:10px 12px;margin:12px 0;border:1px solid var(--line);background:#f9fafb}
.alert--ok{border-color:#c7f0d9;background:#ecfdf5;color:#065f46}
.alert--err{border-color:#fecaca;background:#fef2f2;color:#991b1b}

/* Hidden file */
#photo{position:absolute;left:-9999px;width:1px;height:1px;opacity:0}

/* ====== Modal Cropper (sólido, fullscreen móvil) ====== */
.cropper-backdrop{
  position:fixed;inset:0;
  background:rgba(8,12,22,.65);
  backdrop-filter:saturate(110%) blur(2px);
  display:none;align-items:center;justify-content:center;z-index:9999;
  overscroll-behavior:contain;
}
.cropper-modal{
  width:min(96vw, 980px);
  max-height:min(92vh,820px);
  display:flex;flex-direction:column;
  background:#fff;border-radius:20px;border:1px solid var(--line);
  box-shadow:0 28px 80px rgba(16,24,40,.35);
  overflow:hidden;transform:translateY(8px) scale(.98);opacity:0;
  animation:cm-in .18s ease-out forwards;
}
@keyframes cm-in{to{transform:translateY(0) scale(1);opacity:1}}
.cropper-head{
  padding:14px 18px;border-bottom:1px solid var(--line);font-weight:800;
  position:sticky;top:0;background:#fff;z-index:1;
  padding-top:calc(14px + env(safe-area-inset-top,0px));
}
.cropper-body{padding:14px;display:flex;flex-direction:column;gap:12px;min-height:0}
#cropper-stage{
  position:relative; height:min(68vh,600px);
  border-radius:14px;background:#f9fafb; overflow:hidden;touch-action:none;
  border:1px solid #eef1f6;
}
#cropper-stage .cropper-container{width:100%!important;height:100%!important}
#cropper-img{max-width:none!important;width:auto!important;user-select:none;-webkit-user-drag:none;-webkit-user-select:none}
.controls{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.zoom-range{appearance:none;width:180px;height:6px;border-radius:999px;background:#e5e7eb;outline:none}
.zoom-range::-webkit-slider-thumb{appearance:none;width:16px;height:16px;border-radius:50%;background:#7ea2ff;border:1px solid #c7d2fe}
.cropper-actions{
  position:sticky;bottom:0;background:#fff;display:flex;flex-wrap:wrap;gap:10px;
  justify-content:flex-end;border-top:1px solid var(--line);padding:12px 16px;z-index:1;
  padding-bottom:calc(12px + env(safe-area-inset-bottom,0px));
}
.btn{appearance:none;border:1px solid var(--line);background:#fff;color:#0b1220;border-radius:999px;padding:10px 16px;font-weight:700;cursor:pointer;box-shadow:0 10px 22px rgba(13,38,76,.06);transition:transform .06s}
.btn:hover{transform:translateY(-1px)}
.btn--brand{background:var(--brand);color:#fff;border-color:#6e93ff}

/* ====== Ajustes móviles ====== */
@media (max-width:640px){
  .idc-flip{ height:620px; perspective:1200px; }
  .card-head{ height:130px; }
  .avatar-wrap{ width:96px; height:96px; bottom:-46px;
    box-shadow:0 6px 28px rgba(15,23,42,.16), inset 0 0 0 6px rgba(126,162,255,.10);
  }
  .card-body{ padding:82px 14px 16px; }
  .col{ min-width:100%; }

  .cropper-modal{ width:100vw; height:100dvh; max-height:none; border-radius:0; border:none; }
  .cropper-body{ flex:1; display:flex; flex-direction:column; gap:10px; padding:12px }
  #cropper-stage{ flex:1; height:auto; min-height:60dvh; }
  .controls{ gap:6px }
  .zoom-range{ width:100% }
  .btn{ flex:1 }
  .cropper-actions{ gap:8px }
}
@media (max-height:520px){
  .idc-flip{ height:520px; }
  #cropper-stage{ height:55vh; }
}
</style>
@endpush

@section('content')
<div class="idc-wrap">
  @if (session('ok')) <div class="alert alert--ok">{{ session('ok') }}</div> @endif
  @if ($errors->any())
    <div class="alert alert--err"><strong>Revisa los campos:</strong><ul style="margin:6px 0 0 18px">
      @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul></div>
  @endif

  <div class="idc-grid">
    <div class="idc-flip" id="flip" data-flipped="false" aria-live="polite">
      <div class="idc-flip__inner" id="flipInner">

        {{-- ===== Frente (DETALLES) ===== --}}
        <article class="idc-card idc-card--front" id="cardFront" tabindex="0" aria-label="Credencial - Frente">
          <header class="card-head">
            <div class="card-brand">
              <img src="{{ asset('images/logo-credencial.svg') }}" alt="Logo" onerror="this.style.display='none'">
              <span>Identificación</span>
            </div>
            <button class="avatar-wrap" id="avatarTrigger" type="button" aria-label="Cambiar foto de perfil">
              <img id="avatarPreview" class="idc-avatar" alt="Avatar"
                   src="{{ $user->avatar_url }}"
                   onerror="this.onerror=null; this.src='https://www.gravatar.com/avatar/{{ md5(strtolower(trim($user->email ?? ''))) }}?s=300&d=mp';">
            </button>
          </header>
          <section class="card-body">
            <div class="row" style="margin-bottom:10px">
              <div class="col">
                <label class="label">Nombre</label>
                <div class="val">{{ $user->name }}</div>
              </div>
            </div>
            <div class="row" style="margin-bottom:10px">
              <div class="col">
                <label class="label">Email</label>
                <div class="val">{{ $user->email }}</div>
              </div>
            </div>
            <div class="badges">
              <span class="badge">Usuario #{{ $user->id }}</span>
              @if(method_exists($user,'getRoleNames'))
                @foreach($user->getRoleNames() as $r)
                  <span class="badge">{{ $r }}</span>
                @endforeach
              @endif
            </div>

            {{-- form foto (oculto, auto-submit) --}}
            <form id="photoForm" action="{{ route('profile.update.photo') }}" method="POST" enctype="multipart/form-data" style="margin-top:8px">
              @csrf @method('PUT')
              <input type="file" id="photo" name="photo" accept="image/*">
              <input type="hidden" name="avatar_cropped" id="avatar_cropped">
            </form>
            <p class="help">Tip: toca tu foto para cambiarla. El recorte es cuadrado (alta calidad).</p>
          </section>
        </article>

        {{-- ===== Reverso (SEGURIDAD) ===== --}}
        <article class="idc-card idc-card--back" id="cardBack" aria-label="Credencial - Reverso (Seguridad)">
          <header class="card-head">
            <div class="card-brand">
              <img src="{{ asset('images/logo-credencial.svg') }}" alt="Logo" onerror="this.style.display='none'">
              <span>Seguridad</span>
            </div>
          </header>
          <section class="back-body">
            <form action="{{ route('profile.update.password') }}" method="POST" autocomplete="off" id="pwdForm">
              @csrf @method('PUT')
              <div class="row" style="gap:16px;margin-bottom:10px">
                <div class="col">
                  <label class="label" for="current_password">Contraseña actual</label>
                  <input class="input" type="password" id="current_password" name="current_password" required>
                </div>
                <div class="col">
                  <label class="label" for="password">Nueva contraseña</label>
                  <input class="input" type="password" id="password" name="password" required>
                  <div class="help">Mínimo 8 caracteres, evita contraseñas comunes.</div>
                </div>
                <div class="col">
                  <label class="label" for="password_confirmation">Confirmar nueva</label>
                  <input class="input" type="password" id="password_confirmation" name="password_confirmation" required>
                </div>
              </div>
              <div style="display:flex;justify-content:flex-end;margin-top:12px">
                <button type="submit" class="btn btn--brand">Actualizar contraseña</button>
              </div>
            </form>

            <hr style="border:none;border-top:1px dashed var(--line);margin:16px 0">
            <div class="item"><span>Registrado</span><span>{{ $user->created_at?->format('d M Y') }}</span></div>
            <div class="item"><span>Último acceso</span><span>{{ $user->last_login_at?->format('d M Y H:i') ?? '—' }}</span></div>
            <div class="item"><span>Roles</span>
              <span>
                @if(method_exists($user,'getRoleNames'))
                  {{ $user->getRoleNames()->implode(', ') ?: '—' }}
                @else — @endif
              </span>
            </div>
            <div class="item"><span>Estatus</span><span class="badge">Activo</span></div>
          </section>
        </article>

      </div>
    </div>
  </div>
</div>

{{-- ===== Modal Cropper (cuadrado + pinch) ===== --}}
<div class="cropper-backdrop" id="cropperBackdrop" aria-hidden="true">
  <div class="cropper-modal" role="dialog" aria-modal="true" aria-labelledby="cropperTitle">
    <div class="cropper-head" id="cropperTitle">Recorta tu foto (cuadrado)</div>
    <div class="cropper-body">
      <div id="cropper-stage">
        <img id="cropper-img" alt="Recorte">
      </div>
      <div class="controls">
        <button type="button" class="btn" id="zoomIn">+ Zoom</button>
        <button type="button" class="btn" id="zoomOut">- Zoom</button>
        <button type="button" class="btn" id="rotate">Rotar 90°</button>
        <button type="button" class="btn" id="reset">Reiniciar</button>
        <input type="range" id="zoomRange" class="zoom-range" min="0.5" max="3" step="0.01" value="1" aria-label="Zoom">
        <span id="zoomLabel" class="help">Zoom 1.00x</span>
      </div>
    </div>
    <div class="cropper-actions">
      <button type="button" class="btn" id="closeCrop">Cancelar</button>
      <button type="button" class="btn btn--brand" id="applyCrop">Aplicar y guardar</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
(function(){
  /* ===== Flip 3D en toda la card (clic) ===== */
  const flip = document.getElementById('flip');
  const inner = document.getElementById('flipInner');
  function toggleFlip(e){
    if (e && e.target && e.target.closest('input,button,textarea,select,label')) return;
    flip.setAttribute('data-flipped', String(flip.getAttribute('data-flipped') !== 'true'));
  }
  document.getElementById('cardFront')?.addEventListener('click', toggleFlip);
  document.getElementById('cardBack') ?.addEventListener('click', toggleFlip);

  // Tilt en desktop
  if (window.matchMedia('(hover: hover)').matches) {
    const maxTilt = 6;
    inner.addEventListener('mousemove', (ev)=>{
      const r = inner.getBoundingClientRect();
      const dx = (ev.clientX - (r.left + r.width/2)) / (r.width/2);
      const dy = (ev.clientY - (r.top  + r.height/2)) / (r.height/2);
      flip.style.setProperty('--rx', (dy * -maxTilt).toFixed(2)+'deg');
      flip.style.setProperty('--ry', (dx *  maxTilt).toFixed(2)+'deg');
    });
    inner.addEventListener('mouseleave', ()=>{
      flip.style.setProperty('--rx','0deg');
      flip.style.setProperty('--ry','0deg');
    });
  }

  /* ===== Avatar + Cropper ===== */
  const trigger    = document.getElementById('avatarTrigger');
  const inputFile  = document.getElementById('photo');
  const preview    = document.getElementById('avatarPreview');
  const form       = document.getElementById('photoForm');
  const hiddenData = document.getElementById('avatar_cropped');

  const backdrop = document.getElementById('cropperBackdrop');
  const img     = document.getElementById('cropper-img');
  const stage   = document.getElementById('cropper-stage');
  const zoomIn  = document.getElementById('zoomIn');
  const zoomOut = document.getElementById('zoomOut');
  const rotate  = document.getElementById('rotate');
  const resetBt = document.getElementById('reset');
  const closeBt = document.getElementById('closeCrop');
  const applyBt = document.getElementById('applyCrop');
  const zoomRange = document.getElementById('zoomRange');
  const zoomLabel = document.getElementById('zoomLabel');

  let cropper = null, lastScrollY = 0;

  function lockBodyScroll(){
    lastScrollY = window.scrollY || document.documentElement.scrollTop;
    document.body.style.position = 'fixed';
    document.body.style.top = `-${lastScrollY}px`;
    document.body.style.left='0'; document.body.style.right='0';
    document.body.style.width='100%'; document.body.style.overflow='hidden';
  }
  function unlockBodyScroll(){
    document.body.style.position=''; document.body.style.top='';
    document.body.style.left=''; document.body.style.right='';
    document.body.style.width=''; document.body.style.overflow='';
    window.scrollTo(0,lastScrollY);
  }

  function openPicker(){ inputFile?.click(); }
  trigger?.addEventListener('click', openPicker);
  trigger?.addEventListener('keydown', e=>{ if(e.key==='Enter' || e.key===' '){ e.preventDefault(); openPicker(); }});

  function openModalWithFile(file){
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (ev) => {
      img.src = ev.target.result;
      backdrop.style.display = 'flex';
      lockBodyScroll();

      // asegurar altura util en móviles
      requestAnimationFrame(()=>{
        const isMobile = window.matchMedia('(max-width:640px)').matches;
        if(isMobile){ stage.style.minHeight = '60dvh'; }
      });

      setTimeout(()=>{
        if (cropper) { cropper.destroy(); cropper = null; }
        cropper = new Cropper(img, {
          aspectRatio: 1,
          viewMode: 2,
          dragMode: 'move',
          autoCropArea: 0.95,
          background: false,
          movable: true,
          zoomable: true,
          rotatable: true,
          responsive: true,
          center: true,
          restore: false,
          guides: true,
          highlight: true,
          zoomOnWheel: true,
          zoomOnTouch: true,
          ready(){
            const rect = stage.getBoundingClientRect();
            this.cropper.setCanvasData({ left: 0, top: 0, width: rect.width, height: rect.height });
            this.cropper.center();
            const ratio = this.cropper.getImageData().ratio || 1;
            const clamped = Math.min(3, Math.max(0.5, ratio));
            zoomRange.value = clamped;
            zoomLabel.textContent = `Zoom ${Number(clamped).toFixed(2)}x`;
          },
          zoom(e){
            const r = Math.min(3, Math.max(0.5, e.detail.ratio));
            zoomRange.value = r;
            zoomLabel.textContent = `Zoom ${r.toFixed(2)}x`;
          }
        });
      }, 0);
    };
    reader.readAsDataURL(file);
  }

  inputFile?.addEventListener('change', (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    if (!/^image\//.test(file.type)) { alert('Selecciona una imagen válida.'); return; }
    if (file.size > 3 * 1024 * 1024) { alert('Máximo 3MB.'); return; }
    openModalWithFile(file);
  });

  // Controles
  zoomIn ?.addEventListener('click', ()=> cropper?.zoom(0.1));
  zoomOut?.addEventListener('click', ()=> cropper?.zoom(-0.1));
  rotate ?.addEventListener('click', ()=> cropper?.rotate(90));
  resetBt?.addEventListener('click', ()=> {
    cropper?.reset();
    if(cropper){
      const r = cropper.getImageData().ratio || 1;
      const clamped = Math.min(3, Math.max(0.5, r));
      zoomRange.value = clamped;
      zoomLabel.textContent = `Zoom ${parseFloat(clamped).toFixed(2)}x`;
    }
  });

  // Slider -> zoom absoluto
  zoomRange?.addEventListener('input', (e)=>{
    const target = parseFloat(e.target.value || '1');
    if (!cropper) return;
    cropper.zoomTo(target);
    zoomLabel.textContent = `Zoom ${target.toFixed(2)}x`;
  });

  // Cerrar
  function closeModal(){
    backdrop.style.display = 'none';
    unlockBodyScroll();
    if (cropper) { cropper.destroy(); cropper = null; }
  }
  closeBt?.addEventListener('click', closeModal);
  backdrop?.addEventListener('click', (e)=>{ if(e.target === backdrop) closeModal(); });

  // Aplicar
  applyBt?.addEventListener('click', () => {
    if (!cropper) return;
    const canvas = cropper.getCroppedCanvas({
      width:1024, height:1024,
      imageSmoothingEnabled:true, imageSmoothingQuality:'high',
      fillColor:'transparent'
    });
    const dataURL = canvas.toDataURL('image/png');
    hiddenData.value = dataURL;
    preview.src  = dataURL;
    closeModal();
    form?.submit();
  });
})();
</script>
@endpush
