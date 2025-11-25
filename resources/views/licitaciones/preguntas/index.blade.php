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

.wizard-wrap-wide{ max-width:1040px; margin:56px auto; padding:18px; }
.header-row{ display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:20px; }
.header-step{ font-size:11px; text-transform:uppercase; letter-spacing:.14em; color:var(--mint-dark); font-weight:700; margin:0; }
.header-title{ margin:4px 0 0; font-weight:700; font-size:22px; letter-spacing:-0.02em; }
.header-sub{ margin:4px 0 0; color:var(--muted); font-size:13px; max-width:520px; }
.header-link{
  font-size:12px;color:var(--muted);text-decoration:none;padding:6px 10px;border-radius:999px;
  border:1px solid var(--line);background:#fff;
}
.header-link:hover{ border-color:#dbe7ef;color:var(--ink); }

.alert-success{ border-radius:12px;background:#ecfdf3;border:1px solid #bbf7d0;padding:10px 12px;font-size:13px;color:#166534; }
.alert-error{ border-radius:12px;background:#fef2f2;border:1px solid #fecaca;padding:10px 12px;font-size:13px;color:#b91c1c; }
.alert-error ul{ margin:0;padding-left:18px;}
.alert-error li{ margin:2px 0;}

.info-card{
  background:var(--card);border-radius:16px;border:1px solid var(--line);box-shadow:var(--shadow);
  padding:16px 18px;margin-top:10px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;
}
.info-main{ font-size:13px; }
.info-title{ font-weight:600;color:var(--ink);margin-bottom:3px; }
.info-text{ color:var(--muted);margin:1px 0; }
.info-text strong{ color:var(--ink); }

/* acciones derecha */
.info-actions{ display:flex; align-items:center; gap:8px; font-size:12px; flex-wrap:wrap; justify-content:flex-end; }

.btn-small{
  display:inline-flex;align-items:center;justify-content:center;border-radius:999px;padding:7px 11px;font-size:12px;
  text-decoration:none;cursor:pointer;border:1px solid #e5e7eb;background:#fff;color:var(--ink);
}
.btn-small:hover{ background:#f9fafb; }

.two-col-grid{
  display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,1fr);gap:20px;margin-top:20px;
}
@media(max-width:860px){ .two-col-grid{ grid-template-columns:1fr; } }

.card{
  background:var(--card);border-radius:16px;border:1px solid var(--line);box-shadow:var(--shadow);
  padding:18px 18px 16px 18px;
}
.card-title{ font-size:14px;font-weight:600;color:var(--ink);margin:0 0 10px 0; }

.questions-list{ max-height:380px;overflow-y:auto;padding-right:4px;font-size:13px; }
.question-item{
  border-radius:12px;border:1px solid #edf1f7;padding:10px 12px;margin-bottom:8px;background:#fff;
  display:flex;flex-direction:column;gap:6px;
}
.question-ref{
  display:flex;align-items:center;gap:8px;font-size:11px;color:var(--muted);
}
.question-ref .ref-pill{
  display:inline-flex;align-items:center;gap:6px;padding:3px 8px;border-radius:999px;
  background:#f8fafc;border:1px dashed #e5e7eb;color:#334155;font-weight:600;
}
.question-ref .ref-dot{ width:6px;height:6px;border-radius:999px;background:var(--mint-dark); }
.opacity-60{opacity:.6;}

.question-text{ color:var(--ink);font-size:14px;line-height:1.45; }
.question-meta{
  display:flex;justify-content:space-between;font-size:11px;color:var(--muted);margin-top:2px;
}

.form-stack{ display:flex;flex-direction:column;gap:12px; }
.field-label{ display:block;font-size:13px;font-weight:500;color:var(--ink);margin-bottom:4px; }
.field-textarea{
  width:100%;border-radius:10px;border:1px solid #e5e7eb;padding:8px 10px;font-size:13px;outline:none;
  resize:vertical;min-height:80px;font-family:inherit;
}
.field-textarea:focus{ border-color:#c7d2fe;box-shadow:0 0 0 1px rgba(79,70,229,0.16); }

.btn-primary{
  width:100%;border-radius:999px;border:0;padding:10px 16px;font-size:13px;font-weight:700;
  background:var(--mint);color:#fff;cursor:pointer;box-shadow:0 10px 24px rgba(72,207,173,0.18);
}
.btn-primary:hover{ background:var(--mint-dark); }

.lock-message{
  font-size:13px;color:var(--danger);background:#fef2f2;border-radius:12px;border:1px solid #fecaca;padding:10px 12px;
}

/* ====== BOTÓN DESCARGA ANIMADO (PRO) - versión pequeña en fila ====== */
.dl-label{
  background-color: transparent;
  border: 2px solid var(--mint);
  display: inline-flex;
  align-items: center;
  border-radius: 50px;
  width: 110px;              /* más chico */
  cursor: pointer;
  transition: all 0.4s ease;
  padding: 4px 5px;          /* menos alto */
  position: relative;
  user-select:none;
}
.dl-label::before{
  content:"";
  position:absolute;
  inset:0;
  background:#fff;
  width:7px;height:7px;
  transition:all .4s ease;
  border-radius:100%;
  margin:auto;
  opacity:0;visibility:hidden;
}
.dl-input{ display:none; }

.dl-title{
  font-size:12px;
  color:var(--ink);
  transition:all .4s ease;
  position:absolute;
  right:14px;
  bottom:9px;
  text-align:center;
  font-weight:700;
  letter-spacing:.02em;
}
.dl-title:last-child{ opacity:0; visibility:hidden; }

.dl-circle{
  height:32px;width:32px;border-radius:50%;
  background:var(--mint);
  display:flex;justify-content:center;align-items:center;
  transition:all .4s ease;
  position:relative;
  box-shadow:0 0 0 0 rgb(255,255,255);
  overflow:hidden;
}
.dl-circle .dl-icon{
  color:#fff;width:20px;position:absolute;top:50%;left:50%;
  transform:translate(-50%,-50%);
  transition:all .4s ease;
}
.dl-circle .dl-square{
  aspect-ratio:1;width:12px;border-radius:2px;background:#fff;
  opacity:0;visibility:hidden;position:absolute;top:50%;left:50%;
  transform:translate(-50%,-50%);
  transition:all .4s ease;
}
.dl-circle::before{
  content:"";position:absolute;left:0;top:0;
  background:#1f8f77;
  width:100%;height:0;transition:all .4s ease;
}

.dl-label:has(.dl-input:checked){
  width:50px; /* colapsa más chico */
  animation: dl-installed 0.4s ease 3.5s forwards;
  border-color:#16a34a;
}
.dl-label:has(.dl-input:checked)::before{
  animation: dl-rotate 3s ease-in-out 0.4s forwards;
}
.dl-input:checked + .dl-circle{
  animation:
    dl-pulse 1s forwards,
    dl-circleDelete 0.2s ease 3.5s forwards;
  rotate:180deg;
}
.dl-input:checked + .dl-circle::before{
  animation: dl-installing 3s ease-in-out forwards;
}
.dl-input:checked + .dl-circle .dl-icon{ opacity:0; visibility:hidden; }
.dl-input:checked ~ .dl-circle .dl-square{ opacity:1; visibility:visible; }
.dl-input:checked ~ .dl-title{ opacity:0; visibility:hidden; }
.dl-input:checked ~ .dl-title:last-child{
  animation: dl-showInstalledMessage 0.4s ease 3.5s forwards;
}

@keyframes dl-pulse{
  0%{scale:.95;box-shadow:0 0 0 0 rgba(255,255,255,.7);}
  70%{scale:1;box-shadow:0 0 0 14px rgba(255,255,255,0);}
  100%{scale:.95;box-shadow:0 0 0 0 rgba(255,255,255,0);}
}
@keyframes dl-installing{ from{height:0;} to{height:100%;} }
@keyframes dl-rotate{
  0%{
    transform:rotate(-90deg) translate(22px) rotate(0);
    opacity:1;visibility:visible;
  }
  99%{
    transform:rotate(270deg) translate(22px) rotate(270deg);
    opacity:1;visibility:visible;
  }
  100%{opacity:0;visibility:hidden;}
}
@keyframes dl-installed{ 100%{width:110px;border-color:#16a34a;} }
@keyframes dl-circleDelete{ 100%{opacity:0;visibility:hidden;} }
@keyframes dl-showInstalledMessage{ 100%{opacity:1;visibility:visible;right:40px;} }
</style>

<div class="wizard-wrap-wide" style="margin-top:-5px;">
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

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert-error">
            <ul>
                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

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

        @php $convocatoria = $licitacion->archivos->firstWhere('tipo', 'convocatoria'); @endphp

        <div class="info-actions">
            @if($convocatoria)
                <a href="{{ Storage::disk('public')->url($convocatoria->path) }}" target="_blank" class="btn-small">
                    Ver documento de convocatoria
                </a>
            @endif

            {{-- PDF pequeño --}}
            <label class="dl-label" data-download-href="{{ route('licitaciones.preguntas.exportPdf', $licitacion) }}">
                <input type="checkbox" class="dl-input" />
                <span class="dl-circle">
                    <svg class="dl-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                              d="M12 19V5m0 14-4-4m4 4 4-4"></path>
                    </svg>
                    <div class="dl-square"></div>
                </span>
                <p class="dl-title">PDF</p>
                <p class="dl-title">Listo</p>
            </label>

            {{-- Word pequeño --}}
            <label class="dl-label" data-download-href="{{ route('licitaciones.preguntas.exportWord', $licitacion) }}">
                <input type="checkbox" class="dl-input" />
                <span class="dl-circle">
                    <svg class="dl-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                              d="M12 19V5m0 14-4-4m4 4 4-4"></path>
                    </svg>
                    <div class="dl-square"></div>
                </span>
                <p class="dl-title">WORD</p>
                <p class="dl-title">Listo</p>
            </label>
        </div>
    </div>

    <div class="two-col-grid">
        {{-- IZQUIERDA --}}
        <div class="card">
            <h2 class="card-title">Preguntas registradas</h2>

            <div class="questions-list">
                @forelse($preguntas as $pregunta)
                    <div class="question-item">
                        {{-- Referencia a bases ARRIBA --}}
                        <div class="question-ref">
                            <span class="ref-pill {{ $pregunta->notas_internas ? '' : 'opacity-60' }}">
                                <span class="ref-dot"></span>
                                Bases / Requisición / Partida:
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

        {{-- DERECHA --}}
        <div class="card">
            <h2 class="card-title">Agregar nueva pregunta</h2>

            @if(!$puedePreguntar)
                <div class="lock-message">
                    La fecha límite para enviar preguntas ya pasó. No es posible registrar nuevas preguntas.
                </div>
            @else
                <form action="{{ route('licitaciones.preguntas.store', $licitacion) }}" method="POST" class="form-stack">
                    @csrf

                    {{-- Referencia a bases ARRIBA --}}
                    <div>
                        <label class="field-label" for="notas_internas">
                            Referencia a bases (Requisición / No. de partida)
                        </label>
                        <textarea
                            id="notas_internas"
                            name="notas_internas"
                            rows="2"
                            class="field-textarea"
                            placeholder="Ej: Req. 12345 / Partida 07 / Anexo B / Página 12"
                        >{{ old('notas_internas') }}</textarea>
                    </div>

                    {{-- Pregunta ABAJO --}}
                    <div>
                        <label class="field-label" for="texto_pregunta">Pregunta</label>
                        <textarea
                            id="texto_pregunta"
                            name="texto_pregunta"
                            rows="4"
                            class="field-textarea"
                            placeholder="Escribe aquí la pregunta que quieras agregar..."
                        >{{ old('texto_pregunta') }}</textarea>
                    </div>

                    <button type="submit" class="btn-primary">Guardar pregunta</button>
                </form>
            @endif
        </div>
    </div>
</div>

<script>
(function(){
  document.querySelectorAll('.dl-label[data-download-href]').forEach(label => {
    label.addEventListener('click', (e) => {
      if(label.classList.contains('dl-running')) return;

      e.preventDefault();

      const input = label.querySelector('.dl-input');
      const href  = label.dataset.downloadHref;

      if(!input || !href) return;

      label.classList.add('dl-running');
      input.checked = true;

      setTimeout(() => {
        window.location.href = href;
        input.checked = false;
        label.classList.remove('dl-running');
      }, 3600);
    });
  });
})();
</script>
@endsection
