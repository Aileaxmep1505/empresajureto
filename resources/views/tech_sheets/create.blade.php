@extends('layouts.app')

@section('title','Nueva ficha técnica')

@section('content')
<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700" rel="stylesheet">

@php
  $v = function($key, $default = null) {
      return old($key, $default);
  };
@endphp

<style>
:root{
  --mint:#48cfad; --mint-dark:#34c29e;
  --ink:#2a2e35; --muted:#7a7f87; --line:#e9ecef; --card:#ffffff;
}
*{box-sizing:border-box}
body{font-family:"Open Sans",sans-serif;background:#eaebec}

/* Panel base */
.edit-wrap{ max-width:960px; margin:10px auto 40px; padding:0 16px; }
.panel{
  background:var(--card); border-radius:16px;
  box-shadow:0 16px 40px rgba(18,38,63,.12); overflow:hidden;
}
.panel-head{
  padding:18px 22px; border-bottom:1px solid var(--line);
  display:flex; align-items:center; gap:12px; justify-content:space-between;
}
.hgroup h2{ margin:0; font-weight:700; color:var(--ink); letter-spacing:-.02em }
.hgroup p{ margin:2px 0 0; color:var(--muted); font-size:14px }
.back-link{
  display:inline-flex; align-items:center; gap:8px;
  color:var(--muted); text-decoration:none;
  padding:8px 12px; border-radius:10px; border:1px solid var(--line); background:#fff;
}
.back-link:hover{ color:#111; border-color:#e3e6eb; box-shadow:0 8px 18px rgba(0,0,0,.08) }

/* Form + campos */
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
.field textarea{ min-height:90px; max-height:230px; }
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

/* Grid sin bootstrap */
.row{ display:flex; flex-wrap:wrap; margin-left:-10px; margin-right:-10px; }
.col{ padding:0 10px; }
.col-12{ width:100% }
@media (min-width: 768px){
  .col-md-3{ width:25% }
  .col-md-4{ width:33.3333% }
  .col-md-6{ width:50% }
}
.gy-3 > .col{ margin-top:12px }

/* Bloques de imágenes (dropzone) */
.block{
  border:1px dashed #dfe3e8; border-radius:14px;
  padding:14px; background:#fafbfc;
}
.block-title{
  font-size:13px; font-weight:600; color:var(--ink); margin-bottom:6px;
}
.block-sub{ font-size:12px; color:var(--muted); margin-bottom:10px; }

.dropzone{
  display:grid; grid-template-columns:150px 1fr;
  gap:14px; align-items:center;
}
@media (max-width: 620px){ .dropzone{ grid-template-columns:1fr } }

.preview{
  width:150px; height:150px; border-radius:12px; overflow:hidden;
  background:#f6f7f9; display:grid; place-items:center;
  border:1px solid #edf0f3;
}
.preview img{
  width:100%; height:100%; object-fit:cover; display:none;
}
.preview .placeholder{
  display:flex; flex-direction:column; align-items:center; justify-content:center;
  gap:6px; color:#6b7280; font-size:12px;
}
.preview .placeholder svg{ width:28px; height:28px; opacity:.8 }

.drop-actions{ display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.input-file{ display:none }
.btn-upload{
  background:var(--mint); color:#fff; border:none; border-radius:999px;
  padding:8px 14px; cursor:pointer; box-shadow:0 8px 18px rgba(0,0,0,.12);
  display:inline-flex; align-items:center; gap:6px; font-size:13px;
}
.btn-upload:hover{ background:var(--mint-dark) }
.drop-box{
  border:1px dashed #cfd6e0; border-radius:12px;
  padding:10px 12px; background:#fff; color:#60708a; font-size:12px;
}
.dropzone.dragover .drop-box{ border-color:#93a3c5; background:#f2f6ff }
.file-meta{ font-size:12px; color:#6b7280 }

.brand-block .dropzone{ grid-template-columns:120px 1fr; }
.brand-block .preview{ width:120px; height:120px; }

/* Acciones */
.actions{
  display:flex; gap:10px; justify-content:flex-end; margin-top:12px;
}
.btn{
  border:1px solid transparent; border-radius:12px;
  padding:10px 16px; font-weight:700; cursor:pointer;
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
        <h2>Nueva ficha técnica</h2>
        <p class="subtitle">Registra la información básica del equipo para generar la ficha.</p>
      </div>
      <a href="{{ route('tech-sheets.index') }}" class="back-link" title="Volver">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
        Volver
      </a>
    </div>

    <form class="form" action="{{ route('tech-sheets.store') }}" method="POST" enctype="multipart/form-data">
      @csrf

      {{-- Nombre --}}
      <div class="row gy-3 section-gap">
        <div class="col col-12">
          <div class="field @error('product_name') is-invalid @enderror">
            <input type="text" name="product_name" id="f-name" value="{{ $v('product_name') }}" placeholder=" " required>
            <label for="f-name">Nombre del producto *</label>
          </div>
          @error('product_name')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- Descripción rápida --}}
      <div class="row gy-3 section-gap">
        <div class="col col-12">
          <div class="field @error('user_description') is-invalid @enderror">
            <textarea name="user_description" id="f-desc" placeholder=" ">{{ $v('user_description') }}</textarea>
            <label for="f-desc">Descripción rápida (en tus palabras)</label>
          </div>
          @error('user_description')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- Marca / Modelo / Referencia / Nº Partida --}}
      <div class="row gy-3 section-gap">
        <div class="col col-12 col-md-3">
          <div class="field @error('brand') is-invalid @enderror">
            <input type="text" name="brand" id="f-brand" value="{{ $v('brand') }}" placeholder=" ">
            <label for="f-brand">Marca</label>
          </div>
          @error('brand')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="col col-12 col-md-3">
          <div class="field @error('model') is-invalid @enderror">
            <input type="text" name="model" id="f-model" value="{{ $v('model') }}" placeholder=" ">
            <label for="f-model">Modelo</label>
          </div>
          @error('model')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="col col-12 col-md-3">
          <div class="field @error('reference') is-invalid @enderror">
            <input type="text" name="reference" id="f-ref" value="{{ $v('reference') }}" placeholder=" ">
            <label for="f-ref">Referencia / código</label>
          </div>
          @error('reference')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="col col-12 col-md-3">
          <div class="field @error('partida_number') is-invalid @enderror">
            <input type="text" name="partida_number" id="f-partida" value="{{ $v('partida_number') }}" placeholder=" ">
            <label for="f-partida">N° de partida</label>
          </div>
          @error('partida_number')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- Identificación corta --}}
      <div class="row gy-3 section-gap">
        <div class="col col-12">
          <div class="field @error('identification') is-invalid @enderror">
            <input type="text" name="identification" id="f-ident" value="{{ $v('identification') }}" placeholder=" ">
            <label for="f-ident">Identificación corta del producto</label>
          </div>
          @error('identification')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- Imagen del producto --}}
      <div class="block section-gap">
        <div class="block-title">Imagen principal del producto</div>
        <div class="block-sub">Se mostrará en la cabecera de la ficha. Formato JPG o PNG.</div>

        <div class="dropzone" id="dropzone-main">
          <div class="preview" id="preview-main">
            <div class="placeholder" id="placeholder-main">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <rect x="3" y="4" width="18" height="14" rx="2"/>
                <path d="M3 14l4-4 4 4 3-3 5 5"/>
              </svg>
              <div>Sin imagen seleccionada</div>
            </div>
            <img id="img-main" alt="Vista previa del producto">
          </div>
          <div class="drop-actions">
            <label class="btn-upload" for="file-main">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12h14"/>
              </svg>
              Seleccionar imagen
            </label>
            <input id="file-main" class="input-file" type="file" name="image" accept="image/*">
            <div class="drop-box">o arrastra y suelta aquí</div>
            <div class="file-meta" id="meta-main"></div>
          </div>
        </div>
        @error('image')<div class="error" style="margin-top:6px;">{{ $message }}</div>@enderror
      </div>

      {{-- Logo / imagen de la marca --}}
      <div class="block section-gap brand-block">
        <div class="block-title">Logo / imagen de la marca</div>
        <div class="block-sub">Opcional, se usará como sello de marca en la ficha.</div>

        <div class="dropzone" id="dropzone-brand">
          <div class="preview" id="preview-brand">
            <div class="placeholder" id="placeholder-brand">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <circle cx="12" cy="12" r="7"/>
                <path d="M9 12l2 2 4-4"/>
              </svg>
              <div>Sin logo seleccionado</div>
            </div>
            <img id="img-brand" alt="Vista previa de la marca">
          </div>
          <div class="drop-actions">
            <label class="btn-upload" for="file-brand">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12h14"/>
              </svg>
              Seleccionar logo
            </label>
            <input id="file-brand" class="input-file" type="file" name="brand_image" accept="image/*">
            <div class="drop-box">o arrastra y suelta aquí</div>
            <div class="file-meta" id="meta-brand"></div>
          </div>
        </div>
        @error('brand_image')<div class="error" style="margin-top:6px;">{{ $message }}</div>@enderror
      </div>

      {{-- Acciones --}}
      <div class="actions">
        <a href="{{ route('tech-sheets.index') }}" class="btn btn-ghost">Cancelar</a>
        <button type="submit" class="btn btn-primary">Generar ficha con IA</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  function humanSize(bytes){
    if(!bytes) return '';
    const i = Math.floor(Math.log(bytes)/Math.log(1024));
    return (bytes/Math.pow(1024, i)).toFixed(1) + ' ' + ['B','KB','MB','GB','TB'][i];
  }

  function setupDropzone(cfg){
    const dz          = document.getElementById(cfg.zoneId);
    const input       = document.getElementById(cfg.inputId);
    const img         = document.getElementById(cfg.imgId);
    const placeholder = document.getElementById(cfg.placeholderId);
    const meta        = document.getElementById(cfg.metaId);
    if(!dz || !input || !img || !placeholder) return;

    function render(file){
      meta.textContent = file.name + ' • ' + humanSize(file.size);
      const rd = new FileReader();
      rd.onload = ev => {
        img.src = ev.target.result;
        img.style.display = 'block';
        placeholder.style.display = 'none';
      };
      rd.readAsDataURL(file);
    }

    input.addEventListener('change', e=>{
      const f = e.target.files?.[0]; if(!f) return;
      render(f);
    });

    ['dragenter','dragover'].forEach(evt=>{
      dz.addEventListener(evt, e=>{
        e.preventDefault(); e.stopPropagation();
        dz.classList.add('dragover');
      });
    });
    ['dragleave','drop'].forEach(evt=>{
      dz.addEventListener(evt, e=>{
        e.preventDefault(); e.stopPropagation();
        dz.classList.remove('dragover');
      });
    });
    dz.addEventListener('drop', e=>{
      const f = e.dataTransfer?.files?.[0]; if(!f) return;
      const dt = new DataTransfer(); dt.items.add(f); input.files = dt.files;
      render(f);
    });
  }

  setupDropzone({
    zoneId:'dropzone-main',
    inputId:'file-main',
    imgId:'img-main',
    placeholderId:'placeholder-main',
    metaId:'meta-main'
  });
  setupDropzone({
    zoneId:'dropzone-brand',
    inputId:'file-brand',
    imgId:'img-brand',
    placeholderId:'placeholder-brand',
    metaId:'meta-brand'
  });
})();
</script>
@endsection
