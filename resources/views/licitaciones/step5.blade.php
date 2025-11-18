@extends('layouts.app')
@section('title','Apertura de propuesta y muestras')

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
.grid-2{grid-template-columns:repeat(2,minmax(0,1fr));}
@media(max-width:720px){ .grid-2{grid-template-columns:1fr;} }

/* Field */
.field{position:relative;background:#fff;border:1px solid var(--line);border-radius:12px;padding:12px;transition:box-shadow .15s,border-color .15s;}
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
/* datetime-local, como antes */
.field input[type="datetime-local"] + label{
  top:6px;
  font-size:11px;
}

/* Hint */
.hint{font-size:11px;color:var(--muted);margin-top:6px;}

/* Checkbox switch-ish */
.checkbox-row{
  display:flex;
  align-items:center;
  justify-content:space-between;
  margin-top:4px;
}
.checkbox-inline{
  display:inline-flex;
  align-items:center;
  gap:8px;
}
.checkbox-inline input[type="checkbox"]{
  width:16px;
  height:16px;
  border-radius:4px;
  border:1px solid #d1d5db;
  cursor:pointer;
}
.checkbox-inline label{
  font-size:13px;
  color:var(--ink);
}

/* Muestras group */
.muestras-group{
  margin-top:12px;
}
.hidden-block{
  display:none;
}

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

<div class="wizard-wrap">
    <div class="panel">
        <div class="panel-head">
            <div class="hgroup">
                <div class="step-tag">Paso 5 de 9</div>
                <h2>Apertura de propuesta y muestras</h2>
                <p>Define la fecha de apertura de la propuesta y, si aplica, los datos para la entrega de muestras.</p>
            </div>

            <a href="{{ route('licitaciones.edit.step3', $licitacion) }}" class="back-link" title="Volver al paso anterior">
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

        <form class="form" action="{{ route('licitaciones.update.step5', $licitacion) }}" method="POST" novalidate>
            @csrf

            {{-- Fecha apertura --}}
            <div class="field">
                <input
                    type="datetime-local"
                    name="fecha_apertura_propuesta"
                    id="fecha_apertura_propuesta"
                    value="{{ old('fecha_apertura_propuesta', optional($licitacion->fecha_apertura_propuesta)->format('Y-m-d\TH:i')) }}"
                    placeholder=" "
                >
                <label for="fecha_apertura_propuesta">Fecha y hora de apertura de propuesta</label>
            </div>

            {{-- Checkbox requiere muestras --}}
            <div class="checkbox-row">
                <div class="checkbox-inline">
                    <input
                        type="checkbox"
                        id="requiere_muestras"
                        name="requiere_muestras"
                        value="1"
                        {{ old('requiere_muestras', $licitacion->requiere_muestras) ? 'checked' : '' }}
                    >
                    <label for="requiere_muestras">¿Requiere entrega de muestras?</label>
                </div>
            </div>

            {{-- Campos de muestras --}}
            <div id="muestras_fields"
                 class="muestras-group {{ old('requiere_muestras', $licitacion->requiere_muestras) ? '' : 'hidden-block' }}">
                <div class="grid grid-2">
                    <div>
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
                    </div>
                    <div>
                        <div class="field">
                            <input
                                type="text"
                                name="lugar_entrega_muestras"
                                id="lugar_entrega_muestras"
                                value="{{ old('lugar_entrega_muestras', $licitacion->lugar_entrega_muestras) }}"
                                placeholder="Ej. Almacén central, puerta 2"
                                autocomplete="off"
                            >
                            <label for="lugar_entrega_muestras">Lugar de entrega de muestras</label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Acciones --}}
            <div class="actions-line">
                <a href="{{ route('licitaciones.edit.step3', $licitacion) }}" class="link-back">
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
    const checkbox = document.getElementById('requiere_muestras');
    const fields = document.getElementById('muestras_fields');

    if (!checkbox || !fields) return;

    function toggleMuestras(){
        if (checkbox.checked) {
            fields.classList.remove('hidden-block');
        } else {
            fields.classList.add('hidden-block');
        }
    }

    checkbox.addEventListener('change', toggleMuestras);
    // Asegurar estado inicial correcto
    toggleMuestras();
});
</script>
@endpush
@endsection
