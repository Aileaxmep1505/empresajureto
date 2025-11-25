@extends('layouts.app')
@section('title','Preguntas de la licitación')

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

/* Layout principal */
.wizard-wrap-wide{
  max-width:1040px;
  margin:56px auto;
  padding:18px;
}
.header-row{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:16px;
  margin-bottom:20px;
}
.header-step{
  font-size:11px;
  text-transform:uppercase;
  letter-spacing:.14em;
  color:var(--mint-dark);
  font-weight:700;
  margin:0;
}
.header-title{
  margin:4px 0 0;
  font-weight:700;
  font-size:22px;
  letter-spacing:-0.02em;
}
.header-sub{
  margin:4px 0 0;
  color:var(--muted);
  font-size:13px;
  max-width:520px;
}
.header-link{
  font-size:12px;
  color:var(--muted);
  text-decoration:none;
  padding:6px 10px;
  border-radius:999px;
  border:1px solid var(--line);
  background:#fff;
}
.header-link:hover{
  border-color:#dbe7ef;
  color:var(--ink);
}

/* Alertas */
.alert-success{
  border-radius:12px;
  background:#ecfdf3;
  border:1px solid #bbf7d0;
  padding:10px 12px;
  font-size:13px;
  color:#166534;
}
.alert-error{
  border-radius:12px;
  background:#fef2f2;
  border:1px solid #fecaca;
  padding:10px 12px;
  font-size:13px;
  color:#b91c1c;
}
.alert-error ul{ margin:0; padding-left:18px; }
.alert-error li{ margin:2px 0; }

/* Tarjeta info fechas */
.info-card{
  background:var(--card);
  border-radius:16px;
  border:1px solid var(--line);
  box-shadow:var(--shadow);
  padding:16px 18px;
  margin-top:10px;
  display:flex;
  flex-wrap:wrap;
  align-items:center;
  justify-content:space-between;
  gap:16px;
}
.info-main{ font-size:13px; }
.info-title{
  font-weight:600;
  color:var(--ink);
  margin-bottom:3px;
}
.info-text{ color:var(--muted); margin:1px 0; }
.info-text strong{ color:var(--ink); }
.info-actions{
  display:flex;
  flex-direction:column;
  align-items:flex-end;
  gap:6px;
  font-size:12px;
}
.btn-small{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  border-radius:999px;
  padding:7px 11px;
  font-size:12px;
  text-decoration:none;
  cursor:pointer;
  border:1px solid #e5e7eb;
  background:#fff;
  color:var(--ink);
}
.btn-small:hover{ background:#f9fafb; }
.btn-small-outline{
  border-color:#c7d2fe;
  color:#4338ca;
  background:#f5f3ff;
}
.btn-small-outline:hover{ background:#e0e7ff; }

/* Grid columnas */
.two-col-grid{
  display:grid;
  grid-template-columns:minmax(0,1.1fr) minmax(0,1fr);
  gap:20px;
  margin-top:20px;
}
@media(max-width:860px){
  .two-col-grid{ grid-template-columns:1fr; }
}

/* Tarjetas generales */
.card{
  background:var(--card);
  border-radius:16px;
  border:1px solid var(--line);
  box-shadow:var(--shadow);
  padding:18px 18px 16px 18px;
}
.card-title{
  font-size:14px;
  font-weight:600;
  color:var(--ink);
  margin:0 0 10px 0;
}

/* Listado de preguntas */
.questions-list{
  max-height:380px;
  overflow-y:auto;
  padding-right:4px;
  font-size:13px;
}
.question-item{
  border-radius:12px;
  border:1px solid #edf1f7;
  padding:10px 12px;
  margin-bottom:8px;
  background:#fff;
  display:flex;
  flex-direction:column;
  gap:6px;
}
.question-ref{
  display:flex;
  align-items:center;
  gap:8px;
  font-size:11px;
  color:var(--muted);
}
.question-ref .ref-pill{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:3px 8px;
  border-radius:999px;
  background:#f8fafc;
  border:1px dashed #e5e7eb;
  color:#334155;
  font-weight:600;
}
.question-ref .ref-dot{
  width:6px;height:6px;border-radius:999px;background:var(--mint-dark);
}
.question-text{
  color:var(--ink);
  font-size:14px;
  line-height:1.45;
}
.question-meta{
  display:flex;
  justify-content:space-between;
  font-size:11px;
  color:var(--muted);
  margin-top:2px;
}

/* Formulario derecha */
.form-stack{
  display:flex;
  flex-direction:column;
  gap:12px;
}
.field-label{
  display:block;
  font-size:13px;
  font-weight:500;
  color:var(--ink);
  margin-bottom:4px;
}
.field-textarea{
  width:100%;
  border-radius:10px;
  border:1px solid #e5e7eb;
  padding:8px 10px;
  font-size:13px;
  outline:none;
  resize:vertical;
  min-height:80px;
  font-family:inherit;
}
.field-textarea:focus{
  border-color:#c7d2fe;
  box-shadow:0 0 0 1px rgba(79,70,229,0.16);
}
.btn-primary{
  width:100%;
  border-radius:999px;
  border:0;
  padding:10px 16px;
  font-size:13px;
  font-weight:700;
  background:var(--mint);
  color:#fff;
  cursor:pointer;
  box-shadow:0 10px 24px rgba(72,207,173,0.18);
}
.btn-primary:hover{ background:var(--mint-dark); }

/* Mensaje bloqueo */
.lock-message{
  font-size:13px;
  color:var(--danger);
  background:#fef2f2;
  border-radius:12px;
  border:1px solid #fecaca;
  padding:10px 12px;
}
</style>

<div class="wizard-wrap-wide" style="margin-top:-5px;">
    {{-- Header --}}
    <div class="header-row">
        <div>
            <p class="header-step">Paso 4 de 12</p>
            <h1 class="header-title">Preguntas de la licitación</h1>
            <p class="header-sub">
                Aquí puedes revisar y registrar preguntas relacionadas con la licitación antes de la junta de aclaraciones.
            </p>
        </div>
        <a href="{{ route('licitaciones.show', $licitacion) }}" class="header-link">
            Volver al resumen
        </a>
    </div>

    {{-- Alertas --}}
    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
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

    {{-- Info fechas --}}
    <div class="info-card">
        <div class="info-main">
            <div class="info-title">{{ $licitacion->titulo }}</div>
            <div class="info-text">
                Junta de aclaraciones:
                <strong>{{ optional($licitacion->fecha_junta_aclaraciones)->format('d/m/Y H:i') ?? '—' }}</strong>
            </div>
            <div class="info-text">
                Fecha límite para preguntas:
                <strong>{{ $limite ? $limite->format('d/m/Y H:i') : 'No definida' }}</strong>
            </div>
        </div>

        <div class="info-actions">
            @php $convocatoria = $licitacion->archivos->firstWhere('tipo', 'convocatoria'); @endphp
            @if($convocatoria)
                <a href="{{ Storage::disk('public')->url($convocatoria->path) }}"
                   target="_blank"
                   class="btn-small">
                    Ver documento de convocatoria
                </a>
            @endif

            <a href="{{ route('licitaciones.preguntas.exportPdf', $licitacion) }}"
               class="btn-small btn-small-outline">
                Descargar preguntas en PDF
            </a>
        </div>
    </div>

    {{-- Grid principal --}}
    <div class="two-col-grid">
        {{-- Izquierda: preguntas --}}
        <div class="card">
            <h2 class="card-title">Preguntas registradas</h2>

            <div class="questions-list">
                @forelse($preguntas as $pregunta)
                    <div class="question-item">

                        {{-- Referencia ARRIBA --}}
                        <div class="question-ref">
                            <span class="ref-pill {{ $pregunta->notas_internas ? '' : 'opacity:.6;' }}">
                                <span class="ref-dot"></span>
                                Referencia a bases (Requisición / Partida):
                                <span style="font-weight:700;color:#0f172a;">
                                    {{ $pregunta->notas_internas ?: '—' }}
                                </span>
                            </span>
                        </div>

                        {{-- Pregunta ABAJO --}}
                        <div class="question-text">
                            {{ $pregunta->texto_pregunta }}
                        </div>

                        <div class="question-meta">
                            <span>{{ optional($pregunta->fecha_pregunta)->format('d/m/Y H:i') }}</span>
                            <span>Por: {{ $pregunta->usuario->name ?? 'Usuario' }}</span>
                        </div>
                    </div>
                @empty
                    <p style="font-size:13px;color:var(--muted);margin-top:4px;">
                        Aún no hay preguntas registradas para esta licitación.
                    </p>
                @endforelse
            </div>
        </div>

        {{-- Derecha: formulario --}}
        <div class="card">
            <h2 class="card-title">Agregar nuev</h2>

            @if(!$puedePreguntar)
                <div class="lock-message">
                    La fecha límite para enviar preguntas ya pasó. No es posible registrar nuevas preguntas.
                </div>
            @else
                <form action="{{ route('licitaciones.preguntas.store', $licitacion) }}"
                      method="POST"
                      class="form-stack">
                    @csrf

                    {{-- Referencia ARRIBA --}}
                    <div>
                        <label class="field-label" for="notas_internas">
                            Referencia a bases (Requisición / No. de partida)
                        </label>
                        <textarea id="notas_internas"
                                  name="notas_internas"
                                  rows="2"
                                  class="field-textarea"
                                  placeholder="Ej: Req. 12345 / Partida 07 / Anexo B / Página 12">{{ old('notas_internas') }}</textarea>
                    </div>

                    {{-- Pregunta ABAJO --}}
                    <div>
                        <label class="field-label" for="texto_pregunta">Pregunta</label>
                        <textarea id="texto_pregunta"
                                  name="texto_pregunta"
                                  rows="4"
                                  class="field-textarea"
                                  placeholder="Escribe aquí la pregunta que quieras agregar...">{{ old('texto_pregunta') }}</textarea>
                    </div>

                    <button type="submit" class="btn-primary">
                        Guardar pregunta
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
