@extends('layouts.app')
@section('title','Nueva licitación')

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
.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;}
@media(max-width:820px){ .grid{grid-template-columns:1fr} }

/* Field / floating label */
.field{position:relative;background:#fff;border:1px solid var(--line);border-radius:12px;padding:12px;transition:box-shadow .15s,border-color .15s;}
.field:focus-within{border-color:#d1e7de;box-shadow:0 8px 20px rgba(52,194,158,0.06);}
.field input,
.field select,
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
.field textarea{resize:vertical;min-height:72px;max-height:220px;line-height:1.4;}
.field label{
  position:absolute;
  left:14px;
  top:12px;
  color:var(--muted);
  font-size:12px;
  pointer-events:none;
  transition:all .14s;
}
.field input::placeholder,
.field textarea::placeholder{color:transparent;}
.field input:focus + label,
.field textarea:focus + label,
.field input:not(:placeholder-shown) + label,
.field textarea:not(:placeholder-shown) + label{
  top:6px;
  font-size:11px;
  color:var(--mint-dark);
  transform:translateY(-6px);
}

/* Textarea full width row */
.grid-full{grid-column:1 / -1;}

/* Actions */
.actions{display:flex;gap:12px;justify-content:flex-end;margin-top:18px;align-items:center;}
.actions a{text-decoration:none;}

.btn{border:0;border-radius:10px;padding:10px 16px;font-weight:700;cursor:pointer;font-size:13px;display:inline-flex;align-items:center;justify-content:center;white-space:nowrap;font-family:inherit;}
.btn-primary{background:var(--mint);color:#fff;box-shadow:0 8px 20px rgba(52,194,158,0.12);}
.btn-primary:hover{background:var(--mint-dark);}
.btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink);}
.btn-ghost:hover{border-color:#dbe7ef;}

/* Errors */
.alert-error{
  margin:16px 0;
  border-radius:12px;
  background:#fef2f2;
  border:1px solid #fecaca;
  padding:10px 12px;
  font-size:13px;
  color:#b91c1c;
}
.alert-error ul{margin:0;padding-left:18px;}
.alert-error li{margin:2px 0;}
</style>

<div class="wizard-wrap" style="margin-top:-5px;">
    <div class="panel">
        <div class="panel-head">
            <div class="hgroup">
                <div class="step-tag">Paso 1 de 9</div>
                <h2>Crear nueva licitación</h2>
                <p>Define la información básica de la licitación. Podrás completar el resto de los pasos después.</p>
            </div>

            <a href="{{ route('licitaciones.index') }}" class="back-link" title="Volver">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Volver
            </a>
        </div>

        @if($errors->any())
            <div class="form">
                <div class="alert-error">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form class="form" action="{{ route('licitaciones.store.step1') }}" method="POST" novalidate>
            @csrf

            <div class="grid">
                {{-- Título (full width) --}}
                <div class="grid-full">
                    <div class="field">
                        <input
                            type="text"
                            name="titulo"
                            id="titulo"
                            value="{{ old('titulo') }}"
                            placeholder=" "
                            autocomplete="off"
                        >
                        <label for="titulo">Título de la licitación</label>
                    </div>
                </div>

                {{-- Descripción (full width textarea) --}}
                <div class="grid-full">
                    <div class="field">
                        <textarea
                            name="descripcion"
                            id="descripcion"
                            rows="3"
                            placeholder=" "
                        >{{ old('descripcion') }}</textarea>
                        <label for="descripcion">Descripción (opcional)</label>
                    </div>
                </div>

                {{-- Fecha de convocatoria --}}
                <div>
                    <div class="field">
                        <input
                            type="date"
                            name="fecha_convocatoria"
                            id="fecha_convocatoria"
                            value="{{ old('fecha_convocatoria') }}"
                            placeholder=" "
                        >
                        <label for="fecha_convocatoria">Fecha de convocatoria</label>
                    </div>
                </div>

                {{-- Modalidad --}}
                <div>
                    <div class="field">
                        <select name="modalidad" id="modalidad">
                            <option value="">Selecciona una opción</option>
                            <option value="presencial" {{ old('modalidad') === 'presencial' ? 'selected' : '' }}>Presencial</option>
                            <option value="en_linea" {{ old('modalidad') === 'en_linea' ? 'selected' : '' }}>En línea</option>
                        </select>
                        <label for="modalidad">Modalidad</label>
                    </div>
                </div>
            </div>

            <div class="actions">
                <a href="{{ route('licitaciones.index') }}" class="btn btn-ghost">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    Guardar y continuar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
