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

  /* Pasteles + tinta intensa */
  --pastel-indigo:#eef2ff; --indigo-ink:#3730a3;
  --pastel-emerald:#ecfdf3; --emerald-ink:#047857;
  --pastel-amber:#fffbeb; --amber-ink:#92400e;
}
*{box-sizing:border-box}
body{font-family:"Open Sans",sans-serif;background:#f3f5f7;color:var(--ink);margin:0;padding:0}

/* Wrapper */
.wizard-wrap{max-width:760px;margin:56px auto;padding:18px;}
.panel{background:var(--card);border-radius:16px;box-shadow:var(--shadow);overflow:hidden;border:1px solid var(--line);}
.panel-head{
  padding:20px 22px;border-bottom:1px solid var(--line);
  display:flex;align-items:center;justify-content:space-between;gap:16px;
  background:
    radial-gradient(900px 160px at 0% 0%, rgba(72,207,173,.10), transparent 40%),
    radial-gradient(900px 160px at 100% 0%, rgba(79,70,229,.08), transparent 38%),
    #fff;
}
.hgroup h2{margin:0;font-weight:800;font-size:20px;letter-spacing:-.01em;}
.hgroup p{margin:4px 0 0;color:var(--muted);font-size:13px;max-width:480px;}
.step-tag{font-size:11px;text-transform:uppercase;letter-spacing:.14em;color:var(--mint-dark);font-weight:800;margin-bottom:4px;}

.back-link{
  display:inline-flex;align-items:center;gap:8px;color:var(--muted);
  text-decoration:none;padding:8px 12px;border-radius:12px;border:1px solid var(--line);
  background:#fff;font-size:13px;transition:.18s ease;
}
.back-link:hover{border-color:#dbe7ef;color:var(--ink);transform:translateY(-1px);box-shadow:0 6px 14px rgba(12,18,30,0.06);}

/* Form */
.form{padding:20px;animation:fadeIn .35s ease both;}
@keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
.grid{display:grid;grid-template-columns:1fr;gap:18px;}
.grid-2{grid-template-columns:repeat(2,minmax(0,1fr));}
@media(max-width:720px){ .grid-2{grid-template-columns:1fr;} }

/* Field */
.field{
  position:relative;background:#fff;border:1px solid var(--line);
  border-radius:12px;padding:12px;transition:box-shadow .15s,border-color .15s,transform .15s;
}
.field:focus-within{
  border-color:#d1e7de;box-shadow:0 8px 20px rgba(52,194,158,0.08);
  transform:translateY(-1px);
}
.field input,
.field textarea{
  width:100%;border:0;outline:0;background:transparent;
  font-size:14px;color:var(--ink);padding-top:8px;font-family:inherit;
}
.field textarea{resize:vertical;min-height:40px;max-height:220px;line-height:1.4;}
.field label{
  position:absolute;left:14px;top:12px;color:var(--muted);
  font-size:12px;pointer-events:none;transition:all .14s;
}
.field input::placeholder{color:transparent;}
.field input:focus + label,
.field input:not(:placeholder-shown) + label{
  top:6px;font-size:11px;color:var(--mint-dark);transform:translateY(-6px);
}
.field input[type="datetime-local"] + label{ top:6px;font-size:11px; }

/* Hint */
.hint{
  font-size:11px;color:var(--muted);margin-top:6px;line-height:1.4;
}

/* Email section */
.section{
  background:#fff;border:1px dashed var(--line);border-radius:14px;padding:14px;
}
.section-head{
  display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px;
}
.section-title{
  display:flex;align-items:center;gap:8px;font-size:13px;font-weight:800;color:var(--ink);
}
.section-title svg{width:16px;height:16px;color:var(--mint-dark);}

.email-group{display:flex;flex-direction:column;gap:10px;margin-top:8px;}
.email-row{
  display:grid;grid-template-columns:1fr auto;gap:8px;align-items:center;
}
@media(max-width:540px){ .email-row{grid-template-columns:1fr} }

.btn-mini{
  border:0;border-radius:999px;padding:8px 10px;font-weight:800;cursor:pointer;
  font-size:12px;display:inline-flex;align-items:center;justify-content:center;gap:6px;
  transition:.18s ease;white-space:nowrap;
}
.btn-add{
  background:var(--pastel-emerald);color:var(--emerald-ink);border:1px solid #bbf7d0;
}
.btn-add:hover{filter:brightness(.98);transform:translateY(-1px);box-shadow:0 6px 14px rgba(4,120,87,.10);}
.btn-del{
  background:#fff;color:#b91c1c;border:1px solid #fecaca;
}
.btn-del:hover{background:#fef2f2;transform:translateY(-1px);}

/* Error box */
.alert-error{
  margin:16px 20px 0 20px;border-radius:12px;background:#fef2f2;
  border:1px solid #fecaca;padding:10px 12px;font-size:13px;color:#b91c1c;
}
.alert-error ul{margin:0;padding-left:18px;}
.alert-error li{margin:2px 0;}

/* Actions */
.actions-line{
  margin-top:20px;display:flex;align-items:center;justify-content:space-between;gap:12px;
}
.actions-right{display:flex;gap:12px;align-items:center;}
.link-back{font-size:12px;color:var(--muted);text-decoration:none;}
.link-back:hover{color:var(--ink);text-decoration:underline;}

.btn{
  border:0;border-radius:12px;padding:10px 16px;font-weight:800;cursor:pointer;font-size:13px;
  display:inline-flex;align-items:center;justify-content:center;gap:8px;white-space:nowrap;
  font-family:inherit;transition:.18s ease;
}
.btn-primary{
  background:var(--mint);color:#fff;box-shadow:0 8px 20px rgba(52,194,158,0.14);
}
.btn-primary:hover{background:var(--mint-dark);transform:translateY(-1px);}
.btn-ghost{
  background:#fff;border:1px solid var(--line);color:var(--ink);
}
.btn-ghost:hover{border-color:#dbe7ef;transform:translateY(-1px);}

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
        <p>Configura la fecha de la junta y los correos que recibirán recordatorio para subir preguntas.</p>
      </div>

      <a href="{{ route('licitaciones.edit.step2', $licitacion) }}" class="back-link" title="Volver al paso anterior">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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

    @php
      // viene del controlador como array
      $savedEmails = $recordatorioEmails ?? [];
      // para mostrar mínimo 2 inputs
      $countEmails = max(2, count($savedEmails));
    @endphp

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
              required
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
      <div class="section" style="margin-top:14px;">
        <div class="section-head">
          <div class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M4 4h16v16H4z"></path>
              <path d="M22 6l-10 7L2 6"></path>
            </svg>
            Correos para recordatorio de preguntas
          </div>

          <button type="button" id="add-email" class="btn-mini btn-add">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M12 5v14M5 12h14"></path>
            </svg>
            Agregar correo
          </button>
        </div>

        <p class="hint">
          Se enviará un recordatorio 2 días antes de la junta para que agreguen sus preguntas.
        </p>

        <div id="email-group" class="email-group">
          @for($i = 0; $i < $countEmails; $i++)
            @php
              $oldVal = old("recordatorio_emails.$i");
              $val = $oldVal !== null ? $oldVal : ($savedEmails[$i] ?? '');
            @endphp
            <div class="email-row">
              <div class="field">
                <input
                  type="email"
                  name="recordatorio_emails[]"
                  value="{{ $val }}"
                  placeholder="correo@dominio.com"
                  autocomplete="off"
                >
                <label>Correo {{ $i+1 }}</label>
              </div>

              <button type="button" class="btn-mini btn-del del-email" title="Quitar correo" {{ $i < 2 ? 'style=display:none' : '' }}>
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M3 6h18"></path>
                  <path d="M8 6v14h8V6"></path>
                  <path d="M10 6V4h4v2"></path>
                </svg>
                Quitar
              </button>
            </div>
          @endfor
        </div>
      </div>

      {{-- Acciones --}}
      <div class="actions-line">
        <a href="{{ route('licitaciones.edit.step2', $licitacion) }}" class="link-back">
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
  const group = document.getElementById('email-group');
  const addBtn = document.getElementById('add-email');

  function refreshLabels(){
    const rows = group.querySelectorAll('.email-row');
    rows.forEach((row, idx) => {
      const label = row.querySelector('label');
      if(label) label.textContent = 'Correo ' + (idx+1);

      const delBtn = row.querySelector('.del-email');
      if(delBtn){
        // los primeros 2 no se pueden borrar
        delBtn.style.display = idx < 2 ? 'none' : '';
      }
    });
  }

  addBtn?.addEventListener('click', () => {
    const row = document.createElement('div');
    row.className = 'email-row';
    row.innerHTML = `
      <div class="field">
        <input type="email" name="recordatorio_emails[]" placeholder="correo@dominio.com" autocomplete="off">
        <label>Correo</label>
      </div>
      <button type="button" class="btn-mini btn-del del-email" title="Quitar correo">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M3 6h18"></path>
          <path d="M8 6v14h8V6"></path>
          <path d="M10 6V4h4v2"></path>
        </svg>
        Quitar
      </button>
    `;
    group.appendChild(row);
    refreshLabels();
    row.querySelector('input')?.focus();
  });

  group?.addEventListener('click', (e) => {
    const btn = e.target.closest('.del-email');
    if(!btn) return;
    const row = btn.closest('.email-row');
    if(row){
      row.remove();
      refreshLabels();
    }
  });

  refreshLabels();
})();
</script>
@endsection
