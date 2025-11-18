@extends('layouts.app')
@section('title','Checklist de facturación')

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
  --success:#16a34a;
  --shadow:0 12px 34px rgba(12,18,30,0.06);
}
*{box-sizing:border-box}
body{font-family:"Open Sans",sans-serif;background:#f3f5f7;color:var(--ink);margin:0;padding:0}

/* Wrapper */
.wizard-wrap{max-width:720px;margin:56px auto;padding:18px;}
.panel{background:var(--card);border-radius:16px;box-shadow:var(--shadow);overflow:hidden;}
.panel-head{padding:20px 22px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:16px;}
.hgroup h2{margin:0;font-weight:700;font-size:20px;}
.hgroup p{margin:4px 0 0;color:var(--muted);font-size:13px;max-width:520px;}
.step-tag{font-size:11px;text-transform:uppercase;letter-spacing:.14em;color:var(--mint-dark);font-weight:700;margin-bottom:4px;}
.back-link{display:inline-flex;align-items:center;gap:8px;color:var(--muted);text-decoration:none;padding:8px 12px;border-radius:999px;border:1px solid var(--line);background:#fff;font-size:12px;}
.back-link:hover{border-color:#dbe7ef;color:var(--ink);}

/* Form container */
.form{padding:20px;}
.grid{display:grid;grid-template-columns:1fr;gap:18px;}
.grid-2{grid-template-columns:repeat(2,minmax(0,1fr));}
@media(max-width:720px){ .grid-2{grid-template-columns:1fr;} }

/* Alerts */
.alert-success{
  border-radius:12px;
  background:#ecfdf3;
  border:1px solid #bbf7d0;
  padding:10px 12px;
  font-size:13px;
  color:#166534;
  margin-bottom:12px;
}
.alert-error{
  border-radius:12px;
  background:#fef2f2;
  border:1px solid #fecaca;
  padding:10px 12px;
  font-size:13px;
  color:#b91c1c;
  margin-bottom:12px;
}
.alert-error ul{margin:0;padding-left:18px;}
.alert-error li{margin:2px 0;}

/* Checkbox row */
.checkbox-row{
  display:flex;
  align-items:center;
  gap:8px;
  margin-bottom:8px;
}
.checkbox-row input[type="checkbox"]{
  width:16px;
  height:16px;
  border-radius:4px;
  border:1px solid #d1d5db;
  cursor:pointer;
}
.checkbox-row label{
  font-size:13px;
  color:var(--ink);
}

/* Floating fields */
.field{position:relative;background:#fff;border:1px solid var(--line);border-radius:12px;padding:12px;transition:box-shadow .15s,border-color .15s;}
.field:focus-within{border-color:#d1e7de;box-shadow:0 8px 20px rgba(52,194,158,0.06);}
.field input{
  width:100%;
  border:0;
  outline:0;
  background:transparent;
  font-size:14px;
  color:var(--ink);
  padding-top:8px;
  font-family:inherit;
}
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
/* date/number variants */
.field input[type="date"] + label,
.field input[type="number"] + label{
  top:6px;
  font-size:11px;
}

/* Uploader block */
.block{
  border-radius:12px;
  padding:16px;
  background:#fbfdff;
  border:1px dashed var(--line);
  margin-top:4px;
}
.uploader{display:flex;flex-direction:column;gap:10px;}
.uploader-top{display:flex;flex-wrap:wrap;gap:10px;align-items:center;}
.btn-file{
  background:var(--mint);
  color:#fff;
  padding:9px 14px;
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
.small{color:var(--muted);font-size:12px;}
.small a{color:#4f46e5;text-decoration:none;}
.small a:hover{color:#3730a3;text-decoration:underline;}

/* Actions */
.actions-line{
  margin-top:20px;
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
  padding:9px 15px;
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

<div class="wizard-wrap">
    <div class="panel">
        <div class="panel-head">
            <div class="hgroup">
                <div class="step-tag">Paso 11 de 12</div>
                <h2>Checklist de facturación</h2>
                <p>Registra la información de la factura y adjunta evidencia en caso de existir.</p>
            </div>

            <a href="{{ route('licitaciones.checklist.compras.edit', $licitacion) }}" class="back-link" title="Volver al paso anterior">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Paso anterior
            </a>
        </div>

        @if(session('success'))
            <div class="alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert-error">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $facturacion = $facturacion ?? null;
        @endphp

        <form
            class="form"
            action="{{ route('licitaciones.checklist.facturacion.store', $licitacion) }}"
            method="POST"
            enctype="multipart/form-data"
            novalidate
        >
            @csrf

            {{-- Checkbox tiene factura --}}
            <div class="checkbox-row">
                <input
                    type="checkbox"
                    id="tiene_factura"
                    name="tiene_factura"
                    value="1"
                    {{ old('tiene_factura', $facturacion->tiene_factura ?? false) ? 'checked' : '' }}
                >
                <label for="tiene_factura">¿Ya se emitió la factura?</label>
            </div>

            {{-- Fecha + Monto --}}
            <div class="grid grid-2">
                <div>
                    <div class="field">
                        <input
                            type="date"
                            name="fecha_factura"
                            id="fecha_factura"
                            value="{{ old('fecha_factura', optional($facturacion->fecha_factura ?? null)->format('Y-m-d')) }}"
                            placeholder=" "
                        >
                        <label for="fecha_factura">Fecha de factura</label>
                    </div>
                </div>
                <div>
                    <div class="field">
                        <input
                            type="number"
                            step="0.01"
                            name="monto_factura"
                            id="monto_factura"
                            value="{{ old('monto_factura', $facturacion->monto_factura ?? null) }}"
                            placeholder=" "
                        >
                        <label for="monto_factura">Monto de factura</label>
                    </div>
                </div>
            </div>

            {{-- Evidencia --}}
            <div class="block">
                <div class="uploader">
                    <div class="uploader-top">
                        <label for="evidencia" class="btn-file">
                            Seleccionar evidencia (PDF, JPG, PNG)
                        </label>
                        <input
                            id="evidencia"
                            type="file"
                            name="evidencia"
                            accept=".pdf,.xml,image/jpeg,image/png,image/jpg"
                            style="display:none;"
                        >
                        <span id="file-name-evidencia" class="file-chosen">
                            Ningún archivo seleccionado
                        </span>
                    </div>

                    @if($facturacion && $facturacion->evidencia_path)
                        <p class="small">
                            Actualmente:
                            <a href="{{ Storage::disk('public')->url($facturacion->evidencia_path) }}" target="_blank">
                                Ver evidencia
                            </a>
                        </p>
                    @else
                        <p class="small">
                            Opcional. Puedes adjuntar XML, PDF o captura de pantalla del CFDI.
                        </p>
                    @endif
                </div>
            </div>

            {{-- Acciones --}}
            <div class="actions-line">
                <a href="{{ route('licitaciones.checklist.compras.edit', $licitacion) }}" class="link-back">
                    ← Volver al paso anterior
                </a>

                <div class="actions-right">
                    <a href="{{ route('licitaciones.index') }}" class="btn btn-ghost">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Guardar y continuar a contabilidad
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
    const input = document.getElementById('evidencia');
    const label = document.getElementById('file-name-evidencia');

    if(input && label){
        input.addEventListener('change', function(){
            if(this.files && this.files.length > 0){
                label.textContent = this.files[0].name;
            } else {
                label.textContent = 'Ningún archivo seleccionado';
            }
        });
    }
})();
</script>
@endsection
