@extends('layouts.app')
@section('title','Acta de apertura')

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
.form{padding:20px;}
.grid{display:grid;grid-template-columns:1fr;gap:18px;}
@media(max-width:720px){ .grid{grid-template-columns:1fr;} }

/* Field base */
.field{
  position:relative;
  background:#fff;
  border:1px solid var(--line);
  border-radius:12px;
  padding:12px;
  transition:box-shadow .15s,border-color .15s;
}
.field:focus-within{border-color:#d1e7de;box-shadow:0 8px 20px rgba(52,194,158,0.06);}
.field input,
.field textarea{
  width:100%;
  border:0;
  outline:0;
  background:transparent;
  font-size:14px;
  color:var(--ink);
  padding-top:8px;
  font-family:inherit;
}
.field textarea{resize:vertical;min-height:40px;max-height:220px;line-height:1.4;}
.field label{
  position:absolute;
  left:14px;
  top:12px;
  color:var(--muted);
  font-size:12px;
  pointer-events:none;
  transition:all .14s;
}
.field input::placeholder{color:transparent;}
.field input:focus + label,
.field input:not(:placeholder-shown) + label{
  top:6px;
  font-size:11px;
  color:var(--mint-dark);
  transform:translateY(-6px);
}
/* para type="date": levantamos el label siempre */
.field input[type="date"] + label{
  top:6px;
  font-size:11px;
}

/* Campo de archivo: label estático y dropzone */
.field.file-field{
  padding-top:14px;
}
.field.file-field label{
  position:static;
  display:block;
  margin-bottom:6px;
  font-size:13px;
  color:var(--ink);
}
.field.file-field input[type="file"]{
  display:none;
}

/* Archivo actual */
.current-file{
  margin-bottom:10px;
  padding:10px 12px;
  border-radius:10px;
  border:1px dashed var(--line);
  background:#f9fafb;
  font-size:12px;
  display:flex;
  flex-direction:column;
  gap:4px;
}
.current-file strong{
  font-size:13px;
  color:var(--ink);
}
.current-file a{
  color:#2563eb;
  text-decoration:none;
  font-weight:600;
  font-size:12px;
}
.current-file a:hover{text-decoration:underline;}
.current-file small{color:var(--muted);font-size:11px;}

/* Dropzone diseño */
.file-drop{
  width:100%;
  border-radius:11px;
  border:1px dashed var(--line);
  background:#f9fafb;
  padding:12px 14px;
  display:flex;
  align-items:center;
  gap:10px;
  cursor:pointer;
  transition:border-color .16s ease, background-color .16s ease, box-shadow .16s ease, transform .12s ease;
}
.file-drop:hover{
  border-color:var(--mint);
  background:#f0fdf4;
  box-shadow:0 8px 20px rgba(72,207,173,0.12);
  transform:translateY(-1px);
}
.file-drop.dragover{
  border-color:var(--mint-dark);
  background:#ecfeff;
  box-shadow:0 10px 26px rgba(37,99,235,0.16);
}
.file-icon-bubble{
  flex:0 0 auto;
  width:38px;height:38px;border-radius:999px;
  display:grid;place-items:center;
  background:#eef2ff;
  border:1px solid #e0e7ff;
  color:#4f46e5;
}
.file-icon-bubble svg{
  width:18px;height:18px;
}
.file-drop-text{
  display:flex;
  flex-direction:column;
  gap:2px;
}
.file-drop-main{
  font-size:13px;
  color:var(--ink);
}
.file-drop-main span.action{
  font-weight:700;
  color:#2563eb;
}
.file-drop-sub{
  font-size:11px;
  color:var(--muted);
}
.file-selected{
  margin-top:6px;
  font-size:11px;
  color:var(--muted);
}
.file-selected strong{
  color:var(--ink);
}

/* Hint */
.hint{font-size:11px;color:var(--muted);margin-top:6px;}

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

/* Actions */
.actions-line{
  margin-top:18px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
}
.actions-right{
  display:flex;
  gap:12px;
  align-items:center;
}
.link-back{
  font-size:12px;
  color:var(--muted);
  text-decoration:none;
}
.link-back:hover{color:var(--ink);text-decoration:underline;}
.btn{
  border:0;
  border-radius:10px;
  padding:10px 16px;
  font-weight:700;
  cursor:pointer;
  font-size:13px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  white-space:nowrap;
  font-family:inherit;
}
.btn-primary{
  background:var(--mint);
  color:#fff;
  box-shadow:0 8px 20px rgba(52,194,158,0.12);
}
.btn-primary:hover{background:var(--mint-dark);}
.btn-ghost{
  background:#fff;
  border:1px solid var(--line);
  color:var(--ink);
}
.btn-ghost:hover{border-color:#dbe7ef;}

@media(max-width:540px){
  .actions-line{flex-direction:column;align-items:flex-start;}
  .actions-right{width:100%;justify-content:flex-end;}
}
</style>

<div class="wizard-wrap" style="margin-top:-5px;">
    <div class="panel">
        <div class="panel-head">
            <div class="hgroup">
                <div class="step-tag">Paso 6 de 12</div>
                <h2>Acta de apertura</h2>
                <p>
                    Sube el acta de apertura en PDF y registra la fecha en que se emitió.
                </p>
            </div>

            <a href="{{ route('licitaciones.edit.step5', $licitacion) }}" class="back-link" title="Volver al paso anterior">
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
              action="{{ route('licitaciones.update.step6', $licitacion) }}"
              method="POST"
              enctype="multipart/form-data"
              novalidate>
            @csrf

            <div class="grid">
                {{-- Fecha en que salió el acta de apertura --}}
                <div class="field">
                    <input
                        type="date"
                        name="fecha_acta_apertura"
                        id="fecha_acta_apertura"
                        value="{{ old('fecha_acta_apertura', optional($licitacion->fecha_acta_apertura)->format('Y-m-d')) }}"
                        placeholder=" "
                    >
                    <label for="fecha_acta_apertura">Fecha en que salió el acta de apertura</label>
                    <div class="hint">
                        Es la fecha oficial que aparece en el acta de apertura.
                    </div>
                </div>

                {{-- Acta de apertura (PDF) --}}
                <div class="field file-field">
                    <label for="acta_apertura">
                        Acta de apertura (PDF)
                    </label>

                    @php
                        // Si el controlador te pasa $actaApertura, lo usamos; si no, lo buscamos directo
                        $existingActa = isset($actaApertura)
                            ? $actaApertura
                            : $licitacion->archivos()->where('tipo','acta_apertura')->latest()->first();
                    @endphp

                    @if($existingActa)
                        <div class="current-file">
                            <strong>Acta de apertura actual guardada</strong>
                            <a href="{{ Storage::disk('public')->url($existingActa->path) }}" target="_blank">
                                {{ $existingActa->nombre_original }}
                            </a>
                            <small>Si no adjuntas otro archivo, se conservará este PDF.</small>
                        </div>
                    @endif

                    <div id="fileDropZone" class="file-drop">
                        <div class="file-icon-bubble" aria-hidden="true">
                            {{-- icono documento flecha arriba --}}
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/>
                                <path d="M14 2v6h6"/>
                                <path d="M12 17V9"/>
                                <path d="M9.5 11.5 12 9l2.5 2.5"/>
                            </svg>
                        </div>
                        <div class="file-drop-text">
                            <div class="file-drop-main">
                                <span class="action">Haz clic para seleccionar</span> o arrastra un PDF aquí
                            </div>
                            <div class="file-drop-sub">
                                Solo se acepta un archivo .pdf (tamaño razonable).
                            </div>
                        </div>
                    </div>

                    <input
                        id="acta_apertura"
                        type="file"
                        name="acta_apertura"
                        accept="application/pdf"
                    >

                    <div id="fileSelectedInfo" class="file-selected">
                        @if($existingActa)
                            Se conservará el archivo actual:
                            <strong>{{ $existingActa->nombre_original }}</strong>
                            si no adjuntas uno nuevo.
                        @else
                            Ningún archivo seleccionado.
                        @endif
                    </div>

                    <div class="hint">
                        Puedes subir una nueva versión cuando la tengas lista; siempre se guarda solo el último archivo.
                    </div>
                </div>
            </div>

            <div class="actions-line">
                <a href="{{ route('licitaciones.edit.step5', $licitacion) }}" class="link-back">
                    ← Volver al paso anterior
                </a>

                <div class="actions-right">
                    <a href="{{ route('licitaciones.index') }}" class="btn btn-ghost">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Guardar y continuar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const dropZone   = document.getElementById('fileDropZone');
    const fileInput  = document.getElementById('acta_apertura');
    const infoLabel  = document.getElementById('fileSelectedInfo');

    if (!dropZone || !fileInput || !infoLabel) return;

    const defaultText = infoLabel.innerHTML;

    function setFileNameLabel(file) {
        if (!file) {
            infoLabel.innerHTML = defaultText;
            return;
        }

        if (file.type !== 'application/pdf') {
            infoLabel.innerHTML = '<span style="color:#b91c1c;">El archivo debe ser un PDF.</span>';
            return;
        }

        infoLabel.innerHTML = 'Archivo seleccionado: <strong>' + file.name + '</strong>';
    }

    // Click en el dropzone abre el selector
    dropZone.addEventListener('click', () => fileInput.click());

    // Cuando se selecciona archivo por el input
    fileInput.addEventListener('change', (e) => {
        const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
        setFileNameLabel(file);
    });

    // Drag & drop
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('dragover');
        });
    });

    dropZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        if (!dt || !dt.files || !dt.files.length) return;

        const file = dt.files[0];

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;

        setFileNameLabel(file);
    });
});
</script>
@endpush
@endsection
