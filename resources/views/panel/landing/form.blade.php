@extends('layouts.app')
@section('title', $mode==='create' ? 'Nueva Sección' : 'Editar Sección')

@section('content')
<div class="ls-wrap">
  {{-- Flash / errores --}}
  @if(session('ok')) <div class="ls-alert ok">{{ session('ok') }}</div> @endif
  @if($errors->any())
    <div class="ls-alert err">
      <strong>Hay errores en el formulario. Revisa los campos marcados.</strong>
      <ul class="mt-2 mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form class="ls-grid" method="POST" enctype="multipart/form-data"
        action="{{ $mode==='create' ? route('panel.landing.store') : route('panel.landing.update',$section) }}">
    @csrf
    @if($mode==='edit') @method('PUT') @endif

    {{-- =========== PANE IZQUIERDO: FORMULARIO ===========
         Top Card: metadatos de la sección   --}}
    <section class="ls-card ls-meta">
      <header class="ls-card-head">
        <div class="ls-breadcrumb">
          <a href="{{ route('panel.landing.index') }}" class="ls-link">Secciones</a>
          <span>›</span>
          <strong>{{ $mode==='create' ? 'Nueva sección' : 'Editar sección' }}</strong>
        </div>
        <div class="ls-actions">
          <a href="{{ route('panel.landing.index') }}" class="ls-btn ghost">Cancelar</a>
          <button class="ls-btn primary" id="submitBtn">{{ $mode==='create' ? 'Crear' : 'Guardar cambios' }}</button>
        </div>
      </header>

      <div class="ls-fields">
        <div class="ls-field">
          <label>Nombre de la sección</label>
          <input class="ls-input @error('name') is-invalid @enderror"
                 name="name"
                 value="{{ old('name',$section->name) }}"
                 required
                 placeholder="Ej. Héroe de inicio / Promociones / Marcas destacadas">
          @error('name') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="ls-field-row">
          <div class="ls-field">
            <label>Layout</label>
            <select class="ls-select @error('layout') is-invalid @enderror" name="layout" id="layoutSelect" required>
              @foreach($layouts as $val => $label)
                <option value="{{ $val }}" @selected(old('layout',$section->layout)===$val)>{{ $label }}</option>
              @endforeach
            </select>
            @error('layout') <small class="text-danger">{{ $message }}</small> @enderror
          </div>
          <div class="ls-field">
            <label>Estado</label>
            <label class="ls-switch">
              <input type="checkbox" name="is_active" @checked(old('is_active',$section->is_active))>
              <span class="track"></span><span class="txt">Activa</span>
            </label>
          </div>
        </div>
      </div>
    </section>

    {{-- =========== Repeater de items (drag & drop + dropzones) =========== --}}
    <section class="ls-card">
      <header class="ls-card-head">
        <div>
          <strong class="ls-card-title">Bloques (imagen • textos • CTA)</strong>
          <div class="ls-help">Consejo: “Banner ancho” usa 1 ítem · “Grid-3” usa 3 ítems.</div>
        </div>
        <div class="ls-actions">
          <button type="button" class="ls-btn outline" id="addItemBtn">
            <span class="mi">add</span> Agregar bloque
          </button>
        </div>
      </header>

      <div id="itemsContainer" class="ls-items" aria-live="polite">
        @php $items = old('items', $section->items?->toArray() ?? []); @endphp
        @foreach($items as $idx => $it)
          @include('panel.landing.partials.item', ['idx'=>$idx, 'it'=>$it])
        @endforeach
      </div>

      <footer class="ls-card-foot">
        <div class="ls-tip">
          Puedes <strong>arrastrar</strong> para reordenar. Se guarda el orden automáticamente al enviar.
        </div>
      </footer>
    </section>

    {{-- ===== Acciones móviles (duplicadas al fondo para UX) ===== --}}
    <div class="ls-actions mobile">
      <a href="{{ route('panel.landing.index') }}" class="ls-btn ghost">Cancelar</a>
      <button class="ls-btn primary">{{ $mode==='create' ? 'Crear' : 'Guardar cambios' }}</button>
    </div>

    {{-- =========== PANE DERECHO: PREVIEW EN VIVO =========== --}}
    <aside class="ls-preview">
      <div class="ls-preview-head">
        <div class="chip">Preview</div>
        <div class="ls-device">
          <button type="button" class="is-active" data-device="desktop" title="Desktop">🖥️</button>
          <button type="button" data-device="tablet" title="Tablet">📱</button>
          <button type="button" data-device="mobile" title="Mobile">📲</button>
        </div>
      </div>

      <div id="previewStage" class="ls-stage">
        <div id="previewGrid" class="pv-grid grid-3">
          {{-- tarjetas de preview se inyectan por JS --}}
        </div>
      </div>

      <div class="ls-preview-foot">
        <div class="ls-hint">
          Cambia textos/imagenes y mira la animación con <strong>hover</strong> y layout en tiempo real.
        </div>
      </div>
    </aside>
  </form>
</div>

{{-- ================= Template oculto para nuevos items ================= --}}
<template id="tplItem">
  @include('panel.landing.partials.item', ['idx'=>'__IDX__','it'=>[]])
</template>

{{-- ===================== ESTILOS ===================== --}}
@push('styles')
<style>
/* ========= Tokens ========= */
:root{
  --bg:#f7f8fb; --surface:#ffffff; --ink:#0e1726; --muted:#6b7280;
  --line:#e8eef6; --brand:#6ea8fe; --brand-ink:#17427b; --ok:#10b981; --err:#ef4444;
  --radius:18px; --shadow:0 18px 50px rgba(2,8,23,.08);
  --glass: linear-gradient(180deg, rgba(255,255,255,.9), rgba(255,255,255,.6));
}
html,body{background:var(--bg)}
.mi{font-family: 'Material Symbols Outlined', sans-serif; vertical-align: -2px}

/* ========= Layout split ========= */
.ls-wrap{max-width:1280px;margin:clamp(48px,5vw,70px) auto;padding:0 16px}
.ls-grid{display:grid;grid-template-columns: minmax(0,1fr) 360px; gap:20px; align-items:start}
@media (max-width: 1100px){ .ls-grid{grid-template-columns:1fr} }

/* ========= Cards / alerts ========= */
.ls-card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
.ls-card-head, .ls-card-foot{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;border-bottom:1px solid var(--line)}
.ls-card-foot{border-top:1px solid var(--line);border-bottom:none}
.ls-card-title{font-size:18px}
.ls-meta{backdrop-filter:saturate(140%) blur(6px); background:var(--glass)}
.ls-help{color:var(--muted);font-size:13px;margin-top:2px}

.ls-alert{padding:12px 14px;border-radius:12px;margin-bottom:12px}
.ls-alert.ok{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25)}
.ls-alert.err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25)}

.ls-breadcrumb{display:flex;gap:8px;align-items:center}
.ls-link{color:var(--brand-ink);text-decoration:none}
.ls-actions{display:flex;gap:10px;align-items:center}
.ls-actions.mobile{display:none;margin-top:12px}
@media (max-width:1100px){ .ls-actions.mobile{display:flex; justify-content:flex-end} }

.ls-btn{border-radius:12px;padding:10px 14px;border:1px solid var(--line);background:#fff;cursor:pointer;transition:transform .12s ease, box-shadow .2s;}
.ls-btn:hover{transform:translateY(-1px);box-shadow:0 10px 28px rgba(15,23,42,.08)}
.ls-btn.ghost{background:transparent}
.ls-btn.outline{background:transparent;border-color:#cbd5e1}
.ls-btn.primary{background:var(--brand);border-color:var(--brand);color:#fff}

/* ========= Fields ========= */
.ls-fields{padding:18px}
.ls-field{display:flex;flex-direction:column;gap:8px;margin-bottom:12px}
.ls-field-row{display:grid;grid-template-columns:1fr 1fr; gap:12px}
@media (max-width:640px){ .ls-field-row{grid-template-columns:1fr} }

.ls-input,.ls-select,.ls-textarea{
  border:1px solid var(--line); border-radius:12px; padding:12px 14px; outline:none;
  transition: box-shadow .2s, border-color .2s; font-size:15px; background:#fff;
}
.ls-input:focus,.ls-select:focus,.ls-textarea:focus{box-shadow:0 0 0 4px rgba(110,168,254,.18); border-color:#b9d2ff}

.ls-switch{display:inline-flex;align-items:center;gap:10px;cursor:pointer}
.ls-switch input{display:none}
.ls-switch .track{width:48px;height:28px;border-radius:999px;background:#e2e8f0;position:relative;transition:all .2s}
.ls-switch .track::after{content:"";position:absolute;left:4px;top:4px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.15);transition:left .2s}
.ls-switch input:checked + .track{background:linear-gradient(90deg,#93c5fd,#60a5fa)}
.ls-switch input:checked + .track::after{left:24px}
.ls-switch .txt{color:var(--muted)}

/* ========= Items (repeater) ========= */
.ls-items{display:grid;gap:14px;padding:14px}
.item{border:1px dashed #d8e1ee;border-radius:14px;overflow:hidden;background:#fff;transition:box-shadow .2s, transform .12s}
.item:hover{box-shadow:0 10px 30px rgba(2,8,23,.06); transform:translateY(-1px)}
.item-head{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:linear-gradient(180deg,#f9fbff,#f7fafc);border-bottom:1px solid #eef2f8}
.item-handle{cursor:grab;display:flex;gap:8px;align-items:center;color:#64748b}
.item-handle .dots{letter-spacing:2px}
.item-del{border:none;background:transparent;color:#ef4444;cursor:pointer}
.item-body{display:grid;grid-template-columns:220px minmax(0,1fr);gap:14px;padding:12px}
@media (max-width:860px){ .item-body{grid-template-columns:1fr} }

/* Dropzone */
.drop{border:2px dashed #cbd5e1;border-radius:12px;display:grid;place-items:center;overflow:hidden;min-height:160px;position:relative;background:#f8fafc}
.drop input[type=file]{position:absolute; inset:0; opacity:0; cursor:pointer}
.drop .ph{display:grid;place-items:center;text-align:center;color:#64748b;font-size:13px;padding:14px}
.drop img{width:100%;height:100%;object-fit:cover;display:none}
.drop.has-img img{display:block}
.drop.has-img .ph{display:none}

/* Fields inside item */
.subgrid{display:grid;grid-template-columns:1fr 1fr; gap:10px}
@media (max-width:640px){ .subgrid{grid-template-columns:1fr} }
.item .ls-input, .item .ls-textarea, .item .ls-select{background:#fff}

/* Soft delete visual */
.item.is-deleted{opacity:.45; filter:grayscale(30%)}

/* ========= Preview (right pane) ========= */
.ls-preview{position:sticky;top:18px;height:calc(100dvh - 100px);display:flex;flex-direction:column;gap:12px}
.ls-preview-head{display:flex;align-items:center;justify-content:space-between}
.chip{background:#eef2ff;color:#3730a3;border-radius:999px;padding:6px 10px;font-size:12px;border:1px solid #dbe2ff}
.ls-device{display:flex;gap:6px}
.ls-device button{border:1px solid #d9e2f0;border-radius:10px;background:#fff;padding:6px 10px;cursor:pointer}
.ls-device button.is-active{background:#edf5ff;border-color:#b9d2ff}

.ls-stage{flex:1;border:1px solid var(--line);border-radius:16px;padding:10px;background:
  radial-gradient(800px 400px at 0% 0%, #f1f6ff 0%, transparent 40%),
  radial-gradient(800px 400px at 120% -20%, #fff0f5 0%, transparent 40%),
  #fff; overflow:auto}
.pv-grid{display:grid;gap:12px}
.pv-card{position:relative;border-radius:16px;overflow:hidden;background:#fff;border:1px solid #e9eef7}
.pv-card .img{aspect-ratio: 16/9; width:100%; object-fit:cover; display:block; background:#eef2f7}
.pv-card .txt{position:absolute;left:0;right:0;bottom:0;padding:16px;background:linear-gradient(180deg, transparent, rgba(0,0,0,.45));color:#fff}
.pv-card .t1{font-weight:700}
.pv-card .t2{opacity:.9;font-size:13px;margin-top:2px}
.pv-card .cta{display:inline-flex;gap:6px;align-items:center;background:rgba(255,255,255,.95);color:#0b1220;border-radius:999px;padding:6px 10px;margin-top:10px;font-size:13px}

/* grid templates */
.pv-grid.grid-banner{grid-template-columns:1fr}
.pv-grid.grid-1{grid-template-columns:1fr}
.pv-grid.grid-2{grid-template-columns:repeat(2,1fr)}
.pv-grid.grid-3{grid-template-columns:repeat(3,1fr)}
@media (max-width:980px){
  .pv-grid.grid-2{grid-template-columns:1fr}
  .pv-grid.grid-3{grid-template-columns:repeat(2,1fr)}
}
@media (max-width:560px){
  .pv-grid.grid-3{grid-template-columns:1fr}
}

/* Hover micro-interaction */
.pv-card{transform:translateZ(0); transition: transform .18s ease, box-shadow .22s ease}
.pv-card:hover{transform: translateY(-3px) scale(1.01); box-shadow: 0 18px 50px rgba(2,8,23,.18)}

/* foot / tips */
.ls-preview-foot{margin-top:6px;color:#6b7280;font-size:13px}
.ls-tip{color:#6b7280;font-size:13px}

/* Keyframes (sutiles) */
@keyframes pop {from{transform:scale(.96); opacity:.0} to{transform:scale(1); opacity:1}}
.item{animation: pop .18s ease both}
</style>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300..700" />
@endpush

{{-- ===================== SCRIPTS ===================== --}}
@push('scripts')
<script>
(() => {
  const itemsContainer = document.getElementById('itemsContainer');
  const addItemBtn = document.getElementById('addItemBtn');
  const tpl = document.getElementById('tplItem').innerHTML;
  const layoutSelect = document.getElementById('layoutSelect');
  const previewGrid = document.getElementById('previewGrid');
  const deviceBtns = document.querySelectorAll('.ls-device button');
  const form = document.querySelector('form');

  /* ===== Add item ===== */
  addItemBtn?.addEventListener('click', () => {
    const nextIdx = itemsContainer.querySelectorAll('.item[data-item]').length;
    const html = tpl.replaceAll('__IDX__', nextIdx);
    itemsContainer.insertAdjacentHTML('beforeend', html);
    enhanceItem(itemsContainer.lastElementChild);
    syncPreview();
    smoothScroll(itemsContainer.lastElementChild);
  });

  /* ===== Delete / restore & file preview ===== */
  document.addEventListener('click', (e) => {
    const delBtn = e.target.closest('[data-delete-item]');
    if(delBtn){
      const wrap = delBtn.closest('.item[data-item]');
      const idInput = wrap.querySelector('input[name$="[id]"]');
      const delInput = wrap.querySelector('input[name$="[_delete]"]');
      if(idInput && idInput.value){
        // soft delete
        delInput.value = delInput.value === '1' ? '' : '1';
        wrap.classList.toggle('is-deleted');
        delBtn.innerHTML = wrap.classList.contains('is-deleted') ? '<span class="mi">undo</span> Restaurar' : '<span class="mi">delete</span> Eliminar';
      } else {
        wrap.remove();
      }
      syncPreview();
    }
  });

  document.addEventListener('change', (e) => {
    // Imagen -> preview local + UI dropzone
    if(e.target.matches('input[type=file][data-preview]')){
      const input = e.target;
      const file = input.files?.[0];
      const drop = input.closest('.drop');
      const img = drop.querySelector('img');
      if(file){
        const url = URL.createObjectURL(file);
        img.src = url;
        drop.classList.add('has-img');
      }
      syncPreview();
    }
    // Cualquier input que afecte preview
    if(e.target.matches('.item input, .item textarea, #layoutSelect')){
      syncPreview();
    }
  });

  /* ===== HTML5 drag & drop ===== */
  let dragSrc = null;
  itemsContainer.addEventListener('dragstart', (e) => {
    const row = e.target.closest('.item[data-item]');
    if(!row) return;
    dragSrc = row;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', row.dataset.item);
    row.style.opacity = .6;
  });
  itemsContainer.addEventListener('dragover', (e) => { e.preventDefault(); e.dataTransfer.dropEffect='move'; });
  itemsContainer.addEventListener('drop', (e) => {
    const row = e.target.closest('.item[data-item]');
    if(!row || !dragSrc || row === dragSrc) return;
    const rect = row.getBoundingClientRect();
    const before = (e.clientY - rect.top) < rect.height/2;
    row.insertAdjacentElement(before ? 'beforebegin':'afterend', dragSrc);
    syncPreview();
  });
  itemsContainer.addEventListener('dragend', (e) => {
    const row = e.target.closest('.item[data-item]');
    if(row) row.style.opacity = '';
    dragSrc = null;
  });

  /* ===== Preview: layout & device ===== */
  function layoutToGrid(value){
    if(value === 'banner-wide') return 'grid-banner';
    if(value === 'grid-1') return 'grid-1';
    if(value === 'grid-2') return 'grid-2';
    return 'grid-3';
  }

  deviceBtns.forEach(b=>{
    b.addEventListener('click', ()=>{
      deviceBtns.forEach(x=>x.classList.remove('is-active'));
      b.classList.add('is-active');
      const stage = document.getElementById('previewStage');
      stage.style.width = b.dataset.device==='desktop' ? '100%' : b.dataset.device==='tablet' ? '780px' : '420px';
    });
  });

  /* ===== Enhancers per item (drop highlight) ===== */
  function enhanceItem(el){
    const drop = el.querySelector('.drop');
    const file = drop?.querySelector('input[type=file]');
    if(!drop || !file) return;
    ['dragenter','dragover'].forEach(ev=>drop.addEventListener(ev, (e)=>{e.preventDefault(); drop.style.borderColor = '#93c5fd';}));
    ;['dragleave','drop'].forEach(ev=>drop.addEventListener(ev, (e)=>{e.preventDefault(); drop.style.borderColor = '#cbd5e1';}));
  }
  // enhance existing
  itemsContainer.querySelectorAll('.item[data-item]').forEach(enhanceItem);

  /* ===== Smooth scroll helper ===== */
  function smoothScroll(el){ el?.scrollIntoView({behavior:'smooth', block:'center'}); }

  /* ===== Sync preview ===== */
  function collectItems(){
    const rows = [...itemsContainer.querySelectorAll('.item[data-item]')].filter(r => !r.classList.contains('is-deleted'));
    return rows.map(r=>{
      const t1 = r.querySelector('[name$="[title]"]')?.value?.trim() || 'Título del bloque';
      const t2 = r.querySelector('[name$="[subtitle]"]')?.value?.trim() || 'Subtítulo / descripción breve';
      const cta = r.querySelector('[name$="[cta_text]"]')?.value?.trim() || '';
      const imgEl = r.querySelector('.drop img');
      const src = (imgEl && imgEl.src && !imgEl.src.startsWith('data:')) ? imgEl.src : r.dataset.existing || '';
      return {t1,t2,cta,src};
    });
  }

  function cardHTML({t1,t2,cta,src}){
    const img = src || 'data:image/svg+xml;utf8,' + encodeURIComponent(`<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="675"><rect width="100%" height="100%" fill="#e9eef7"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="26" fill="#6b7280">Vista previa</text></svg>`);
    return `
      <article class="pv-card">
        <img class="img" src="${img}">
        <div class="txt">
          <div class="t1">${escapeHTML(t1)}</div>
          <div class="t2">${escapeHTML(t2)}</div>
          ${cta ? `<div class="cta"><span class="mi">bolt</span>${escapeHTML(cta)}</div>` : ``}
        </div>
      </article>
    `;
  }

  function syncPreview(){
    const layout = layoutSelect.value;
    previewGrid.className = 'pv-grid ' + layoutToGrid(layout);
    const data = collectItems();
    previewGrid.innerHTML = data.length ? data.map(cardHTML).join('') : cardHTML({t1:'Aún no hay bloques',t2:'Agrega al menos uno para ver el diseño',cta:'',src:''});
  }

  function escapeHTML(s){ return s?.replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c])) ?? ''; }

  /* ===== Atajos ===== */
  document.addEventListener('keydown', (e)=>{
    if((e.ctrlKey || e.metaKey) && e.key.toLowerCase()==='enter'){
      form?.submit();
    }
  });

  /* ===== Primera sync ===== */
  syncPreview();
})();
</script>
@endpush
@endsection
