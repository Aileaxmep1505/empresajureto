@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700" rel="stylesheet">

@php
  $isEdit = isset($product) && $product instanceof \App\Models\Product && $product->exists;

  // Helper de valores antiguos/modelo
  $v = function($key, $default = null) use ($product) {
      return old($key, isset($product) ? ($product->{$key} ?? null) : null) ?? $default;
  };
@endphp

<style>
:root{ --mint:#48cfad; --mint-dark:#34c29e; --ink:#2a2e35; --muted:#7a7f87; --line:#e9ecef; --card:#ffffff; }
*{box-sizing:border-box}
body{font-family:"Open Sans",sans-serif;background:#eaebec}

/* Panel */
.edit-wrap{ max-width:1100px; margin:10px auto 40px; padding:0 16px; }
.panel{ background:var(--card); border-radius:16px; box-shadow:0 16px 40px rgba(18,38,63,.12); overflow:hidden; }
.panel-head{ padding:18px 22px; border-bottom:1px solid var(--line); display:flex; align-items:center; gap:12px; justify-content:space-between; }
.hgroup h2{ margin:0; font-weight:700; color:var(--ink); letter-spacing:-.02em }
.hgroup p{ margin:2px 0 0; color:var(--muted); font-size:14px }
.back-link{ display:inline-flex; align-items:center; gap:8px; color:var(--muted); text-decoration:none; padding:8px 12px; border-radius:10px; border:1px solid var(--line); background:#fff; }
.back-link:hover{ color:#111; border-color:#e3e6eb; box-shadow:0 8px 18px rgba(0,0,0,.08) }

/* Form + campos compactos */
.form{ padding:22px; }
.section-gap{ margin-top:8px; }
.field{
  position:relative; background:#fff; border:1px solid var(--line);
  border-radius:12px; padding:12px 12px 6px;
  transition:box-shadow .2s, border-color .2s;
}
.field:focus-within{ border-color:#d8dee6; box-shadow:0 6px 18px rgba(18,38,63,.08) }
.field input,.field textarea{
  width:100%; border:0; outline:0; background:transparent;
  font-size:14px; color:var(--ink); padding-top:8px; resize:vertical;
}
.field textarea{ min-height:90px; }
.field label{
  position:absolute; left:12px; top:10px; color:var(--muted); font-size:12px;
  transition:transform .15s ease, color .15s ease, font-size .15s ease, top .15s ease;
  pointer-events:none;
}
.field input::placeholder,.field textarea::placeholder{ color:transparent; }
.field input:focus + label,
.field input:not(:placeholder-shown) + label,
.field textarea:focus + label,
.field textarea:not(:placeholder-shown) + label{
  top:4px; transform:translateY(-8px); font-size:10.5px; color:var(--mint-dark);
}
.field .suffix,.field .prefix{ position:absolute; right:12px; top:50%; transform:translateY(-10%); color:#a2a7ae; font-size:12px; }
.field .prefix.left{ left:12px; right:auto }
.field.has-left input{ padding-left:26px }

/* Grid fluido sin bootstrap */
.row{ display:flex; flex-wrap:wrap; margin-left:-10px; margin-right:-10px; }
.col{ padding:0 10px; }
.col-12{ width:100% }
@media (min-width: 768px){
  .col-md-6{ width:50% } .col-md-4{ width:33.3333% } .col-md-8{ width:66.6666% } .col-md-3{ width:25% }
}
.gy-3 > .col{ margin-top:12px }

/* Toggle Activo */
.status-row{
  display:flex; align-items:center; justify-content:space-between;
  border:1px solid var(--line); background:#fff; border-radius:12px; padding:10px 12px;
}
.status-row .label{ font-size:13px; color:var(--ink); font-weight:600 }
.status-row .state{ font-size:12px; color:var(--muted); margin-right:10px }
.switch{ display:inline-flex; align-items:center; gap:10px; user-select:none; }
.switch input{ display:none }
.switch .track{ width:44px; height:24px; border-radius:999px; background:#e9edf2; position:relative; transition:background .2s; }
.switch .thumb{ width:20px; height:20px; border-radius:50%; background:#fff; position:absolute; top:2px; left:2px; box-shadow:0 2px 8px rgba(0,0,0,.15); transition:left .18s ease; }
.switch input:checked + .track{ background:var(--mint) }
.switch input:checked + .track .thumb{ left:22px }

/* Dropzone / archivos */
.block{ border:1px dashed #dfe3e8; border-radius:14px; padding:14px; background:#fafbfc; }
.dropzone{ display:grid; grid-template-columns:150px 1fr; gap:14px; align-items:center; }
@media (max-width: 620px){ .dropzone{ grid-template-columns:1fr } }
.preview{
  width:150px; height:150px; border-radius:12px; overflow:hidden; background:#f6f7f9;
  display:grid; place-items:center; border:1px solid #edf0f3;
}
.preview img{ width:100%; height:100%; object-fit:cover; display:none }
.preview .placeholder{
  display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; color:#6b7280; font-size:12px;
}
.placeholder svg{ width:28px; height:28px; opacity:.8 }
.drop-actions{ display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.input-file{ display:none }
.btn-upload{
  background:var(--mint); color:#fff; border:none; border-radius:999px; padding:8px 14px;
  cursor:pointer; box-shadow:0 8px 18px rgba(0,0,0,.12);
}
.btn-upload:hover{ background:var(--mint-dark) }
.drop-box{ border:1px dashed #cfd6e0; border-radius:12px; padding:10px 12px; background:#fff; color:#60708a; font-size:12px; }
.dropzone.dragover .drop-box{ border-color:#93a3c5; background:#f2f6ff }
.file-meta{ font-size:12px; color:#6b7280 }

/* Acciones */
.actions{ display:flex; gap:10px; justify-content:flex-end; margin-top:8px; }
.btn{
  border:1px solid transparent; border-radius:12px; padding:10px 16px; font-weight:700; cursor:pointer;
  transition:transform .05s ease, box-shadow .2s ease, background .2s ease, color .2s ease, border-color .2s ease;
  text-decoration:none; display:inline-flex; align-items:center; gap:8px;
}
.btn:active{ transform:translateY(1px) }
.btn-primary{ background:var(--mint); color:#fff; }
.btn-primary:hover{ background:#fff; color:#111; border-color:transparent; box-shadow:0 14px 34px rgba(0,0,0,.18); }
.btn-ghost{ background:#fff; color:#111; border:1px solid #e5e7eb; }
.btn-ghost:hover{ background:#fff; color:#111; border-color:transparent; box-shadow:0 12px 26px rgba(0,0,0,.12); }

.is-invalid{ border-color:#f9c0c0 !important }
.error{ color:#cc4b4b; font-size:12px; margin-top:6px }
@media (max-width: 768px){
  .hgroup .subtitle{ display:none; }
}

</style>

<div class="edit-wrap">
  <div class="panel">
    <div class="panel-head">
      <div class="hgroup">
        <h2>{{ $isEdit ? 'Editar producto' : 'Agregar producto' }}</h2>
       <p class="subtitle">{{ $isEdit ? 'Actualiza los datos del producto.' : 'Crea un nuevo producto y sube su archivo/imagen.' }}</p>
      </div>
      <a href="{{ route('products.index') }}" class="back-link" title="Volver">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Volver
      </a>
    </div>

    <form class="form"
          action="{{ $isEdit ? route('products.update', $product->getKey()) : route('products.store') }}"
          method="POST" enctype="multipart/form-data">
      @csrf @if($isEdit) @method('PUT') @endif

      {{-- ===== Fila: Nombre / SKU ===== --}}
      <div class="row gy-3 section-gap">
        <div class="col col-12 col-md-6">
          <div class="field @error('name') is-invalid @enderror">
            <input type="text" name="name" id="f-name" value="{{ $v('name') }}" placeholder=" " required>
            <label for="f-name">Nombre del producto *</label>
          </div>
          @error('name')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="col col-12 col-md-6">
          <div class="field @error('sku') is-invalid @enderror">
            <input type="text" name="sku" id="f-sku" value="{{ $v('sku') }}" placeholder=" ">
            <label for="f-sku">SKU</label>
          </div>
          @error('sku')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- ===== Fila: SKU proveedor / Marca ===== --}}
      <div class="row gy-3 section-gap">
        <div class="col col-12 col-md-6">
          <div class="field @error('supplier_sku') is-invalid @enderror">
            <input type="text" name="supplier_sku" id="f-ssku" value="{{ $v('supplier_sku') }}" placeholder=" ">
            <label for="f-ssku">SKU del proveedor</label>
          </div>
          @error('supplier_sku')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="col col-12 col-md-6">
          <div class="field @error('brand') is-invalid @enderror">
            <input type="text" name="brand" id="f-brand" value="{{ $v('brand') }}" placeholder=" ">
            <label for="f-brand">Marca</label>
          </div>
          @error('brand')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- ===== Fila: Unidad / Peso ===== --}}
      <div class="row gy-3 section-gap">
        <div class="col col-12 col-md-6">
          <div class="field @error('unit') is-invalid @enderror">
            <input type="text" name="unit" id="f-unit" value="{{ $v('unit') }}" placeholder=" ">
            <label for="f-unit">Unidad</label>
          </div>
          @error('unit')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="col col-12 col-md-6">
          <div class="field @error('weight') is-invalid @enderror">
            <input type="number" step="0.001" name="weight" id="f-weight" value="{{ $v('weight') }}" placeholder=" ">
            <label for="f-weight">Peso</label>
            <span class="suffix">kg</span>
          </div>
          @error('weight')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- ===== Fila: Costo / Precio ===== --}}
      <div class="row gy-3 section-gap">
        <div class="col col-12 col-md-6">
          <div class="field has-left @error('cost') is-invalid @enderror">
            <span class="prefix left">$</span>
            <input type="number" step="0.01" name="cost" id="f-cost" value="{{ $v('cost') }}" placeholder=" ">
            <label for="f-cost">Costo</label>
          </div>
          @error('cost')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="col col-12 col-md-6">
          <div class="field has-left @error('price') is-invalid @enderror">
            <span class="prefix left">$</span>
            <input type="number" step="0.01" name="price" id="f-price" value="{{ $v('price') }}" placeholder=" ">
            <label for="f-price">Precio</label>
          </div>
          @error('price')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- ===== Fila: Precio de mercado / Precio de licitación ===== --}}
      <div class="row gy-3 section-gap">
        <div class="col col-12 col-md-6">
          <div class="field has-left @error('market_price') is-invalid @enderror">
            <span class="prefix left">$</span>
            <input type="number" step="0.01" name="market_price" id="f-market" value="{{ $v('market_price') }}" placeholder=" ">
            <label for="f-market">Precio de mercado</label>
          </div>
          @error('market_price')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="col col-12 col-md-6">
          <div class="field has-left @error('bid_price') is-invalid @enderror">
            <span class="prefix left">$</span>
            <input type="number" step="0.01" name="bid_price" id="f-bid" value="{{ $v('bid_price') }}" placeholder=" ">
            <label for="f-bid">Precio de licitación</label>
          </div>
          @error('bid_price')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- ===== Fila: Dimensiones / Color ===== --}}
      <div class="row gy-3 section-gap">
        <div class="col col-12 col-md-6">
          <div class="field @error('dimensions') is-invalid @enderror">
            <input type="text" name="dimensions" id="f-dim" value="{{ $v('dimensions') }}" placeholder=" ">
            <label for="f-dim">Dimensiones</label>
          </div>
          @error('dimensions')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="col col-12 col-md-6">
          <div class="field @error('color') is-invalid @enderror">
            <input type="text" name="color" id="f-color" value="{{ $v('color') }}" placeholder=" ">
            <label for="f-color">Color</label>
          </div>
          @error('color')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- ===== Fila: Piezas por unidad / Categoría / Material ===== --}}
      <div class="row gy-3 section-gap">
        <div class="col col-12 col-md-4">
          <div class="field @error('pieces_per_unit') is-invalid @enderror">
            <input type="number" step="1" name="pieces_per_unit" id="f-ppu" value="{{ $v('pieces_per_unit') }}" placeholder=" ">
            <label for="f-ppu">Piezas por unidad</label>
          </div>
          @error('pieces_per_unit')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="col col-12 col-md-4">
          <div class="field @error('category') is-invalid @enderror">
            <input type="text" name="category" id="f-cat" value="{{ $v('category') }}" placeholder=" ">
            <label for="f-cat">Categoría</label>
          </div>
          @error('category')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="col col-12 col-md-4">
          <div class="field @error('material') is-invalid @enderror">
            <input type="text" name="material" id="f-mat" value="{{ $v('material') }}" placeholder=" ">
            <label for="f-mat">Material</label>
          </div>
          @error('material')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- ===== Fila: Etiquetas / Activo ===== --}}
      <div class="row gy-3 section-gap">
        <div class="col col-12 col-md-8">
          <div class="field @error('tags') is-invalid @enderror">
            <input type="text" name="tags" id="f-tags" value="{{ $v('tags') }}" placeholder=" ">
            <label for="f-tags">Etiquetas (separadas por coma)</label>
          </div>
          @error('tags')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="col col-12 col-md-4">
          <div class="status-row">
            <div class="label">Estado</div>
            <div class="d-flex align-items-center">
              <span class="state" id="stateText">
                {{ $v('active', ($isEdit? (int)$product->active : 1)) ? 'Activo' : 'Inactivo' }}
              </span>
              <label class="switch mb-0">
                <input type="checkbox" name="active" id="activeToggle" value="1"
                       {{ $v('active', ($isEdit? (int)$product->active : 1)) ? 'checked' : '' }}>
                <span class="track"><span class="thumb"></span></span>
              </label>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== Descripción ===== --}}
      <div class="row gy-3 section-gap">
        <div class="col col-12">
          <div class="field @error('description') is-invalid @enderror">
            <textarea name="description" id="f-desc" placeholder=" ">{{ $v('description') }}</textarea>
            <label for="f-desc">Descripción</label>
          </div>
          @error('description')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- ===== Notas ===== --}}
      <div class="row gy-3 section-gap">
        <div class="col col-12">
          <div class="field @error('notes') is-invalid @enderror">
            <textarea name="notes" id="f-notes" placeholder=" ">{{ $v('notes') }}</textarea>
            <label for="f-notes">Notas</label>
          </div>
          @error('notes')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- ===== Dropzone para imagen/archivo (cualquier formato) ===== --}}
      <div class="block section-gap">
        <div class="dropzone" id="dropzone">
          <div class="preview" id="filePreview">
            {{-- Placeholder profesional --}}
            <div class="placeholder" id="placeholder">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path d="M3 6h18v12H3z"/><path d="M3 14l4-4 4 4 4-4 4 4"/>
              </svg>
              <div>Sin archivo seleccionado</div>
            </div>
            <img id="imgPreview" alt="preview">
          </div>
          <div class="drop-actions">
            <label class="btn-upload" for="fileInput">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;">
                <path d="M12 5v14M5 12h14"/>
              </svg>
              Seleccionar archivo
            </label>
            {{-- IMPORTANTE: usamos name="image" para ser compatible con tu controlador; acepta cualquier tipo --}}
            <input id="fileInput" class="input-file" type="file" name="image" accept="*/*">
            <div class="drop-box">o arrastra y suelta aquí</div>
            <div class="file-meta" id="fileMeta"></div>
          </div>
        </div>
        @error('image')<div class="error" style="margin-top:8px;">{{ $message }}</div>@enderror
      </div>

      {{-- ===== Acciones ===== --}}
      <div class="actions">
        <a href="{{ route('products.index') }}" class="btn btn-ghost">Cancelar</a>
        <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Actualizar' : 'Guardar' }}</button>
      </div>
    </form>
  </div>
</div>

<script>
// ===== Toggle Activo – actualiza texto y envía 0 si está apagado
const activeToggle = document.getElementById('activeToggle');
const stateText    = document.getElementById('stateText');
const formEl       = document.querySelector('form');

activeToggle?.addEventListener('change', ()=>{
  stateText.textContent = activeToggle.checked ? 'Activo' : 'Inactivo';
});
formEl?.addEventListener('submit', ()=>{
  if (activeToggle && !activeToggle.checked) {
    const h = document.createElement('input');
    h.type='hidden'; h.name='active'; h.value='0';
    formEl.appendChild(h);
  }
});

// ===== Drag & Drop – cualquier archivo; preview si es imagen
const dz        = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const imgPrev   = document.getElementById('imgPreview');
const placeholder = document.getElementById('placeholder');
const meta      = document.getElementById('fileMeta');

function humanSize(bytes){
  if(!bytes) return '';
  const i = Math.floor(Math.log(bytes)/Math.log(1024));
  return (bytes/Math.pow(1024, i)).toFixed(1) + ' ' + ['B','KB','MB','GB','TB'][i];
}
function renderFile(file){
  meta.textContent = `${file.name} • ${humanSize(file.size)}`;
  if (/^image\//.test(file.type)) {
    const rd = new FileReader();
    rd.onload = ev => {
      imgPrev.src = ev.target.result;
      imgPrev.style.display = 'block';
      placeholder.style.display = 'none';
    };
    rd.readAsDataURL(file);
  } else {
    imgPrev.style.display = 'none';
    placeholder.innerHTML = `
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <path d="M14 2v6h6"/>
      </svg>
      <div>${file.type || 'Archivo'}</div>
    `;
    placeholder.style.display = 'flex';
  }
}
fileInput?.addEventListener('change', e=>{
  const f = e.target.files?.[0]; if(!f) return; renderFile(f);
});
['dragenter','dragover'].forEach(evt=>{
  dz.addEventListener(evt, e=>{ e.preventDefault(); e.stopPropagation(); dz.classList.add('dragover'); });
});
['dragleave','drop'].forEach(evt=>{
  dz.addEventListener(evt, e=>{ e.preventDefault(); e.stopPropagation(); dz.classList.remove('dragover'); });
});
dz.addEventListener('drop', e=>{
  const f = e.dataTransfer?.files?.[0]; if(!f) return;
  const dt = new DataTransfer(); dt.items.add(f); fileInput.files = dt.files;
  renderFile(f);
});

// ===== Redondeo de montos al salir
['f-cost','f-price','f-market','f-bid'].forEach(id=>{
  const el = document.getElementById(id);
  if(!el) return;
  el.addEventListener('blur', ()=> {
    if(el.value === '') return;
    const n = Number(el.value);
    if(!isNaN(n)) el.value = n.toFixed(2);
  });
});
</script>
@endsection
