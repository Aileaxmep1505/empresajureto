@extends('layouts.app') 
@section('title','Subir documento de convocatoria')

@section('content')
<style>
:root{
  --mint:#48cfad;
  --mint-dark:#34c29e;
  --ink:#111827;
  --muted:#6b7280;
  --line:#e6eef6;
  --card:#ffffff;
  --danger:#ef4444;
  --shadow: 0 12px 34px rgba(12,18,30,0.06);
}
*{box-sizing:border-box}
body{font-family:"Open Sans",sans-serif;background:#f3f5f7;color:var(--ink);margin:0;padding:0}

/* Wrapper */
.wizard-wrap{max-width:720px;margin:56px auto;padding:18px;}
.panel{background:var(--card);border-radius:14px;box-shadow:var(--shadow);overflow:hidden;}
.panel-head{padding:20px 22px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:16px;}
.hgroup h2{margin:0;font-weight:700;font-size:20px;}
.hgroup p{margin:4px 0 0;color:var(--muted);font-size:13px;}
.step-tag{font-size:11px;text-transform:uppercase;letter-spacing:.14em;color:var(--mint-dark);font-weight:700;margin-bottom:4px;}
.back-link{display:inline-flex;align-items:center;gap:8px;color:var(--muted);text-decoration:none;padding:8px 12px;border-radius:10px;border:1px solid var(--line);background:#fff;font-size:13px;}
.back-link:hover{border-color:#dbe7ef;color:var(--ink);}

/* Form container */
.form{padding:20px;display:flex;flex-direction:column;gap:14px;}

/* Error box */
.alert-error{
  margin:16px 20px 0 20px;
  border-radius:12px;
  background:#fef2f2;
  border:1px solid #fecaca;
  padding:10px 12px;
  font-size:13px;
  color:#b91c1c;
}
.alert-error ul{margin:0;padding-left:18px;}
.alert-error li{margin:2px 0;}

/* Blocks */
.block{
  border-radius:12px;
  padding:16px;
  background:#fbfdff;
  border:1px dashed var(--line);
}
.block h3{
  margin:0 0 6px 0;
  font-size:14px;
  font-weight:700;
  color:var(--ink);
}
.small{color:var(--muted);font-size:12px;line-height:1.4;}
.current-file{
  margin-top:8px; font-size:12px; color:#374151;
  display:flex; flex-wrap:wrap; gap:8px; align-items:center;
}
.current-file a{
  color:#0f766e; font-weight:700; text-decoration:none;
}
.current-file a:hover{ text-decoration:underline; }

/* Uploader */
.uploader{display:flex;flex-direction:column;gap:10px;}
.uploader-top{display:flex;flex-wrap:wrap;gap:10px;align-items:center;}
.btn-file{
  background:var(--mint);
  color:#fff;
  padding:10px 14px;
  border-radius:999px;
  border:none;
  cursor:pointer;
  font-weight:700;
  font-size:13px;
  box-shadow:0 10px 22px rgba(72,207,173,0.16);
}
.btn-file:hover{background:var(--mint-dark);}
.file-chosen{
  font-size:13px;
  color:var(--muted);
}

/* Muestras */
.checkbox-row{
  display:flex;align-items:center;gap:10px;
  padding:10px 12px;border-radius:10px;background:#fff;border:1px solid var(--line);
}
.checkbox-row input{width:16px;height:16px;accent-color:var(--mint-dark);}
.checkbox-label{font-size:14px;color:var(--ink);font-weight:600;}
.muestras-fields{
  margin-top:10px;
  display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;
}
@media(max-width:820px){ .muestras-fields{grid-template-columns:1fr;} }

.field{
  position:relative;background:#fff;border:1px solid var(--line);
  border-radius:12px;padding:12px;transition:box-shadow .15s,border-color .15s;
}
.field:focus-within{border-color:#d1e7de;box-shadow:0 8px 20px rgba(52,194,158,0.06);}
.field input{
  width:100%;border:0;outline:0;background:transparent;
  font-size:14px;color:var(--ink);padding-top:8px;font-family:inherit;
}
.field label{
  position:absolute;left:14px;top:12px;color:var(--muted);font-size:12px;
  pointer-events:none;transition:all .14s;
}
.field input::placeholder{color:transparent;}
.field input:focus + label,
.field input:not(:placeholder-shown) + label{
  top:6px;font-size:11px;color:var(--mint-dark);transform:translateY(-6px);
}
.hidden{display:none !important;}

/* Actions */
.actions-line{
  margin-top:6px;
  display:flex;align-items:center;justify-content:space-between;gap:12px;
}
.actions-right{display:flex;gap:12px;align-items:center;}
.link-back{font-size:12px;color:var(--muted);text-decoration:none;}
.link-back:hover{color:var(--ink);text-decoration:underline;}
.btn{
  border:0;border-radius:10px;padding:10px 16px;font-weight:700;cursor:pointer;
  font-size:13px;display:inline-flex;align-items:center;justify-content:center;
  white-space:nowrap;font-family:inherit;
}
.btn-primary{
  background:var(--mint);color:#fff;box-shadow:0 8px 20px rgba(52,194,158,0.12);
}
.btn-primary:hover{background:var(--mint-dark);}
.btn-ghost{
  background:#fff;border:1px solid var(--line);color:var(--ink);
}
.btn-ghost:hover{border-color:#dbe7ef;}

@media(max-width:540px){
  .actions-line{flex-direction:column;align-items:flex-start;}
  .actions-right{width:100%;justify-content:flex-end;}
}
</style>

@php
  $convocatoria = $licitacion->archivos->where('tipo','convocatoria')->sortByDesc('id')->first();
  $antecedente  = $licitacion->archivos->where('tipo','acta_antecedente')->sortByDesc('id')->first();

  $checked = old('requiere_muestras', $licitacion->requiere_muestras) ? true : false;
@endphp

<div class="wizard-wrap" style="margin-top:-5px;">
  <div class="panel">

    <div class="panel-head">
      <div class="hgroup">
        <div class="step-tag">Paso 2 de 9</div>
        <h2>Documentos iniciales + muestras</h2>
        <p>Adjunta acta de antecedente, documento de convocatoria y define si hay entrega de muestras.</p>
      </div>

      <a href="{{ route('licitaciones.create.step1') }}" class="back-link" title="Volver al paso anterior">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
        Paso anterior
      </a>
    </div>

    @if($errors->any())
      <div class="alert-error">
        <ul>
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form class="form"
          action="{{ route('licitaciones.update.step2', $licitacion) }}"
          method="POST"
          enctype="multipart/form-data"
          novalidate>
      @csrf

      {{-- =======================
           BLOQUE ANTECEDENTE
      ======================== --}}
      <div class="block">
        <h3>Acta de antecedente (PDF)</h3>

        <div class="uploader">
          <div class="uploader-top">
            <label for="acta_antecedente" class="btn-file">
              {{ $antecedente ? 'Reemplazar PDF' : 'Seleccionar PDF' }}
            </label>
            <input
              id="acta_antecedente"
              type="file"
              name="acta_antecedente"
              accept=".pdf"
              style="display:none;"
            >
            <span id="file-name-ante" class="file-chosen">
              {{ $antecedente->nombre_original ?? 'Ningún archivo seleccionado' }}
            </span>
          </div>

          @if($antecedente)
            <div class="current-file">
              Archivo actual: 
              <strong>{{ $antecedente->nombre_original }}</strong>
              <a href="{{ Storage::disk('public')->url($antecedente->path) }}" target="_blank">Ver PDF</a>
            </div>
          @endif

          <p class="small">
            Sube el acta en formato <strong>PDF</strong>. Si ya existe, puedes dejarlo así o reemplazarlo.
          </p>
        </div>
      </div>

      {{-- =======================
           BLOQUE CONVOCATORIA
      ======================== --}}
      <div class="block">
        <h3>Documento de convocatoria</h3>

        <div class="uploader">
          <div class="uploader-top">
            <label for="archivo_convocatoria" class="btn-file">
              {{ $convocatoria ? 'Reemplazar archivo' : 'Seleccionar archivo' }}
            </label>
            <input
              id="archivo_convocatoria"
              type="file"
              name="archivo_convocatoria"
              accept=".pdf,.doc,.docx,.xls,.xlsx"
              style="display:none;"
            >
            <span id="file-name-conv" class="file-chosen">
              {{ $convocatoria->nombre_original ?? 'Ningún archivo seleccionado' }}
            </span>
          </div>

          @if($convocatoria)
            <div class="current-file">
              Archivo actual: 
              <strong>{{ $convocatoria->nombre_original }}</strong>
              <a href="{{ Storage::disk('public')->url($convocatoria->path) }}" target="_blank">Ver documento</a>
            </div>
          @endif

          <p class="small">
            Formatos permitidos: <strong>PDF, DOC, DOCX, XLS, XLSX</strong>.
          </p>
        </div>
      </div>

      {{-- =======================
           BLOQUE MUESTRAS
      ======================== --}}
      <div class="block">
        <h3>Entrega de muestras</h3>

        <label class="checkbox-row" for="requiere_muestras">
          <input type="checkbox"
                 id="requiere_muestras"
                 name="requiere_muestras"
                 value="1"
                 {{ $checked ? 'checked' : '' }}>
          <span class="checkbox-label">¿Requiere entrega de muestras?</span>
        </label>

        <p class="small" style="margin-top:8px;">
          Activa esta opción si la convocatoria solicita muestras físicas o técnicas.
        </p>

        <div id="muestras_fields" class="muestras-fields {{ $checked ? '' : 'hidden' }}">
          <div class="field">
            <input
              type="datetime-local"
              name="fecha_entrega_muestras"
              id="fecha_entrega_muestras"
              value="{{ old('fecha_entrega_muestras', optional($licitacion->fecha_entrega_muestras)->format('Y-m-d\TH:i')) }}"
              placeholder=" "
            >
            <label for="fecha_entrega_muestras">Fecha y hora límite de entrega</label>
          </div>

          <div class="field">
            <input
              type="text"
              name="lugar_entrega_muestras"
              id="lugar_entrega_muestras"
              value="{{ old('lugar_entrega_muestras', $licitacion->lugar_entrega_muestras) }}"
              placeholder=" "
              autocomplete="off"
            >
            <label for="lugar_entrega_muestras">Lugar de entrega de muestras</label>
          </div>
        </div>
      </div>

      {{-- =======================
           ACCIONES
      ======================== --}}
      <div class="actions-line">
        <a href="{{ route('licitaciones.create.step1') }}" class="link-back">
          ← Volver al paso anterior
        </a>

        <div class="actions-right">
          <a href="{{ route('licitaciones.index') }}" class="btn btn-ghost">Cancelar</a>
          <button type="submit" class="btn btn-primary">Guardar y continuar</button>
        </div>
      </div>

    </form>
  </div>
</div>

<script>
(function(){
  // nombres de archivo
  const inputConv = document.getElementById('archivo_convocatoria');
  const labelConv = document.getElementById('file-name-conv');

  const inputAnte = document.getElementById('acta_antecedente');
  const labelAnte = document.getElementById('file-name-ante');

  if(inputConv && labelConv){
    inputConv.addEventListener('change', function(){
      labelConv.textContent = (this.files && this.files.length)
        ? this.files[0].name
        : '{{ $convocatoria->nombre_original ?? "Ningún archivo seleccionado" }}';
    });
  }

  if(inputAnte && labelAnte){
    inputAnte.addEventListener('change', function(){
      labelAnte.textContent = (this.files && this.files.length)
        ? this.files[0].name
        : '{{ $antecedente->nombre_original ?? "Ningún archivo seleccionado" }}';
    });
  }

  // toggle muestras
  const checkbox = document.getElementById('requiere_muestras');
  const fields = document.getElementById('muestras_fields');

  if(checkbox && fields){
    const toggle = () => {
      if (checkbox.checked) fields.classList.remove('hidden');
      else fields.classList.add('hidden');
    };
    checkbox.addEventListener('change', toggle);
    toggle();
  }
})();
</script>
@endsection
