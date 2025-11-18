@extends('layouts.app')
@section('title','Junta de aclaraciones')

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

/* Field + label (floating-ish) */
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
/* datetime-local no soporta bien :placeholder-shown en todos los navegadores,
   pero mantenemos el mismo estilo general */
.field input[type="datetime-local"] + label{
  top:6px;
  font-size:11px;
}

/* Hint text */
.hint{font-size:11px;color:var(--muted);margin-top:6px;}

/* Email block */
.email-group{display:flex;flex-direction:column;gap:10px;margin-top:8px;}
.email-row .field{margin-bottom:0;}

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

<div class="wizard-wrap" style="margin-top:-5px;">
    <div class="panel">
        <div class="panel-head">
            <div class="hgroup">
                <div class="step-tag">Paso 3 de 9</div>
                <h2>Junta de aclaraciones</h2>
                <p>Configura la fecha de la junta de aclaraciones y los correos que recibirán el recordatorio para subir preguntas.</p>
            </div>

            <a href="{{ route('licitaciones.edit.step2', $licitacion) }}" class="back-link" title="Volver al paso anterior">
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

        <form class="form" action="{{ route('licitaciones.update.step3', $licitacion) }}" method="POST" novalidate>
            @csrf

            {{-- Fechas --}}
            <div class="grid grid-2">
                <div>
                    <div class="field">
                        <input
                            type="datetime-local"
                            name="fecha_junta_aclaraciones"
                            id="fecha_junta_aclaraciones"
                            value="{{ old('fecha_junta_aclaraciones', optional($licitacion->fecha_junta_aclaraciones)->format('Y-m-d\TH:i')) }}"
                            placeholder=" "
                        >
                        <label for="fecha_junta_aclaraciones">Fecha y hora de junta</label>
                    </div>
                </div>

                <div>
                    <div class="field">
                        <input
                            type="datetime-local"
                            name="fecha_limite_preguntas"
                            id="fecha_limite_preguntas"
                            value="{{ old('fecha_limite_preguntas', optional($licitacion->fecha_limite_preguntas)->format('Y-m-d\TH:i')) }}"
                            placeholder=" "
                        >
                        <label for="fecha_limite_preguntas">Fecha límite de preguntas</label>
                    </div>
                    <p class="hint">
                        Si lo dejas vacío, se tomará la misma fecha y hora de la junta.
                    </p>
                </div>
            </div>

            {{-- Lugar / Link --}}
            <div class="grid grid-2" style="margin-top:4px;">
                <div>
                    <div class="field">
                        <input
                            type="text"
                            name="lugar_junta"
                            id="lugar_junta"
                            value="{{ old('lugar_junta', $licitacion->lugar_junta) }}"
                            placeholder=" "
                            autocomplete="off"
                        >
                        <label for="lugar_junta">Lugar (si es presencial)</label>
                    </div>
                </div>

                <div>
                    <div class="field">
                        <input
                            type="text"
                            name="link_junta"
                            id="link_junta"
                            value="{{ old('link_junta', $licitacion->link_junta) }}"
                            placeholder=" "
                            autocomplete="off"
                        >
                        <label for="link_junta">Link de reunión (si es en línea)</label>
                    </div>
                </div>
            </div>

            {{-- Correos --}}
            <div style="margin-top:14px;">
                <label style="font-size:13px;font-weight:500;color:var(--ink);display:block;margin-bottom:4px;">
                    Correos para recordatorio de preguntas
                </label>
                <p class="hint">
                    Se enviará un recordatorio 2 días antes de la junta para que agreguen sus preguntas.
                </p>

                <div class="email-group">
                    <div class="email-row">
                        <div class="field">
                            <input
                                type="email"
                                name="recordatorio_emails[]"
                                id="recordatorio_email_1"
                                value="{{ old('recordatorio_emails.0') }}"
                                placeholder="correo1@dominio.com"
                                autocomplete="off"
                            >
                            <label for="recordatorio_email_1">Correo 1</label>
                        </div>
                    </div>
                    <div class="email-row">
                        <div class="field">
                            <input
                                type="email"
                                name="recordatorio_emails[]"
                                id="recordatorio_email_2"
                                value="{{ old('recordatorio_emails.1') }}"
                                placeholder="correo2@dominio.com"
                                autocomplete="off"
                            >
                            <label for="recordatorio_email_2">Correo 2 (opcional)</label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Acciones --}}
            <div class="actions-line">
                <a href="{{ route('licitaciones.edit.step2', $licitacion) }}" class="link-back">
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
@endsection
