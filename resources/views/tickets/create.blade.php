@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700" rel="stylesheet">

@php
  $priorities = $priorities ?? \App\Http\Controllers\Tickets\TicketController::PRIORITIES;
  $areas      = $areas      ?? \App\Http\Controllers\Tickets\TicketController::AREAS;

  // Helper old()
  $v = fn($key, $default=null) => old($key, $default);
@endphp

<style>
:root{ --mint:#48cfad; --mint-dark:#34c29e; --ink:#2a2e35; --muted:#7a7f87; --line:#e9ecef; --card:#ffffff; }
*{box-sizing:border-box}
body{font-family:"Open Sans",sans-serif;background:#eaebec}

/* Panel */
.edit-wrap{ max-width:1100px; margin:10px auto 40px; padding:0 16px; }
.panel{ background:var(--card); border-radius:16px; box-shadow:0 16px 40px rgba(18,38,63,.12); overflow:hidden; }
.panel-head{ padding:18px 22px; border-bottom:1px solid var(--line); display:flex; align-items:center; gap:12px; justify-content:space-between; }
.hgroup h2{ margin:0; font-weight:700; color:var(--ink); letter-spacing:-.02em }
.hgroup p{ margin:2px 0 0; color:var(--muted); font-size:14px }
.back-link{ display:inline-flex; align-items:center; gap:8px; color:var(--muted); text-decoration:none; padding:8px 12px; border-radius:10px; border:1px solid var(--line); background:#fff; }
.back-link:hover{ color:#111; border-color:#e3e6eb; box-shadow:0 8px 18px rgba(0,0,0,.08) }

/* Form + campos compactos */
.form{ padding:22px; }
.section-gap{ margin-top:8px; }
.field{
  position:relative; background:#fff; border:1px solid var(--line);
  border-radius:12px; padding:12px 12px 6px;
  transition:box-shadow .2s, border-color .2s;
}
.field:focus-within{ border-color:#d8dee6; box-shadow:0 6px 18px rgba(18,38,63,.08) }
.field input,.field textarea,.field select{
  width:100%; border:0; outline:0; background:transparent;
  font-size:14px; color:var(--ink); padding-top:8px; resize:vertical;
  font-family:"Open Sans",sans-serif;
}
.field textarea{ min-height:110px; }
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
.field select{
  appearance:none;
  padding-top:18px;
  padding-bottom:10px;
}
.field select + label{
  top:4px; transform:translateY(-8px); font-size:10.5px; color:var(--mint-dark);
}
.field .suffix,.field .prefix{ position:absolute; right:12px; top:50%; transform:translateY(-10%); color:#a2a7ae; font-size:12px; }
.field .prefix.left{ left:12px; right:auto }
.field.has-left input{ padding-left:26px }

/* Grid fluido sin bootstrap */
.row{ display:flex; flex-wrap:wrap; margin-left:-10px; margin-right:-10px; }
.col{ padding:0 10px; }
.col-12{ width:100% }
@media (min-width: 768px){
  .col-md-6{ width:50% } .col-md-4{ width:33.3333% } .col-md-8{ width:66.6666% } .col-md-3{ width:25% }
}
.gy-3 > .col{ margin-top:12px }

/* Chips info */
.chips{ display:flex; flex-wrap:wrap; gap:8px; margin-top:8px; }
.chip{
  display:inline-flex; align-items:center; gap:8px;
  border:1px solid var(--line); background:#fff;
  border-radius:999px; padding:7px 10px;
  font-weight:700; font-size:12px; color:var(--muted);
}

/* Dropzone / archivos (OPCIONAL) */
.block{ border:1px dashed #dfe3e8; border-radius:14px; padding:14px; background:#fafbfc; }
.dropzone{ display:grid; grid-template-columns:150px 1fr; gap:14px; align-items:center; }
@media (max-width: 620px){ .dropzone{ grid-template-columns:1fr } }
.preview{
  width:150px; height:150px; border-radius:12px; overflow:hidden; background:#f6f7f9;
  display:grid; place-items:center; border:1px solid #edf0f3;
}
.preview img{ width:100%; height:100%; object-fit:cover; display:none }
.preview .placeholder{
  display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; color:#6b7280; font-size:12px;
  text-align:center; padding:10px;
}
.placeholder svg{ width:28px; height:28px; opacity:.8 }

.drop-actions{ display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.input-file{ display:none }
.btn-upload{
  background:var(--mint); color:#fff; border:none; border-radius:999px; padding:8px 14px;
  cursor:pointer; box-shadow:0 8px 18px rgba(0,0,0,.12);
  display:inline-flex; align-items:center; gap:8px;
}
.btn-upload:hover{ background:var(--mint-dark) }
.drop-box{ border:1px dashed #cfd6e0; border-radius:12px; padding:10px 12px; background:#fff; color:#60708a; font-size:12px; }
.dropzone.dragover .drop-box{ border-color:#93a3c5; background:#f2f6ff }
.file-meta{ font-size:12px; color:#6b7280 }

/* Lista de archivos */
.file-list{ margin-top:10px; display:flex; flex-direction:column; gap:8px; }
.file-item{
  display:flex; align-items:center; justify-content:space-between; gap:10px;
  background:#fff; border:1px solid #edf0f3; border-radius:12px; padding:10px 12px;
}
.file-left{ min-width:0; }
.file-name{
  font-weight:700; color:var(--ink);
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
  max-width:520px;
}
.file-sub{ font-size:12px; color:#6b7280; margin-top:2px; }
.file-remove{
  border:1px solid rgba(239,68,68,.25);
  background:rgba(239,68,68,.12);
  color:#7f1d1d;
  border-radius:12px;
  padding:8px 10px;
  font-weight:700;
  cursor:pointer;
  white-space:nowrap;
}
.file-remove:hover{ box-shadow:0 10px 18px rgba(0,0,0,.10) }

/* Acciones */
.actions{ display:flex; gap:10px; justify-content:flex-end; margin-top:8px; }
.btn{
  border:1px solid transparent; border-radius:12px; padding:10px 16px; font-weight:700; cursor:pointer;
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
  .file-name{ max-width:260px; }
}
</style>

<div class="edit-wrap">
  <div class="panel">
    <div class="panel-head">
      <div class="hgroup">
        <h2>Nuevo ticket</h2>
        <p class="subtitle">Crea una tarea ordenada, asígnala, y adjunta evidencias si lo necesitas.</p>
      </div>
      <a href="{{ route('tickets.index') }}" class="back-link" title="Volver">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Volver
      </a>
    </div>

    <form class="form" action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data" id="tkForm">
      @csrf

      {{-- ===== Fila: Título / Asignado ===== --}}
      <div class="row gy-3 section-gap">
        <div class="col col-12 col-md-6">
          <div class="field @error('title') is-invalid @enderror">
            <input type="text" name="title" id="f-title" value="{{ $v('title') }}" placeholder=" " required>
            <label for="f-title">Título *</label>
          </div>
          @error('title')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="col col-12 col-md-6">
          <div class="field @error('assignee_id') is-invalid @enderror">
            <select name="assignee_id" id="f-assignee">
              <option value="">— Sin asignar —</option>
              @foreach(($users ?? []) as $u)
                <option value="{{ $u->id }}" @selected((string)$v('assignee_id')===(string)$u->id)>{{ $u->name }}</option>
              @endforeach
            </select>
            <label for="f-assignee">Asignado a</label>
          </div>
          @error('assignee_id')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- ===== Descripción ===== --}}
      <div class="row gy-3 section-gap">
        <div class="col col-12">
          <div class="field @error('description') is-invalid @enderror">
            <textarea name="description" id="f-desc" placeholder=" ">{{ $v('description') }}</textarea>
            <label for="f-desc">Descripción</label>
          </div>
          @error('description')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- ===== Fila: Área / Prioridad / Vencimiento ===== --}}
      <div class="row gy-3 section-gap">
        <div class="col col-12 col-md-4">
          <div class="field @error('area') is-invalid @enderror">
            <select name="area" id="f-area" required>
              <option value="">Selecciona…</option>
              @foreach($areas as $k => $label)
                <option value="{{ $k }}" @selected($v('area')===$k)>{{ $label }}</option>
              @endforeach
            </select>
            <label for="f-area">Área *</label>
          </div>
          @error('area')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="col col-12 col-md-4">
          <div class="field @error('priority') is-invalid @enderror">
            <select name="priority" id="f-priority" required>
              @foreach($priorities as $k => $label)
                <option value="{{ $k }}" @selected($v('priority','media')===$k)>{{ $label }}</option>
              @endforeach
            </select>
            <label for="f-priority">Prioridad *</label>
          </div>
          @error('priority')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="col col-12 col-md-4">
          <div class="field @error('due_at') is-invalid @enderror">
            <input type="datetime-local" name="due_at" id="f-due" value="{{ $v('due_at') }}" placeholder=" ">
            <label for="f-due">Vence (opcional)</label>
          </div>
          @error('due_at')<div class="error">{{ $message }}</div>@enderror

          <div class="chips">
            <span class="chip">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 6 9 17l-5-5"/>
              </svg>
              Estatus inicial: <span style="color:#111">Pendiente</span>
            </span>
          </div>
        </div>
      </div>

      {{-- ===== Fila: Impacto / Urgencia / Esfuerzo / Score ===== --}}
      <div class="row gy-3 section-gap">
        <div class="col col-12 col-md-3">
          <div class="field @error('impact') is-invalid @enderror">
            <select name="impact" id="f-impact">
              <option value="">—</option>
              @for($i=1;$i<=5;$i++)
                <option value="{{ $i }}" @selected((string)$v('impact')===(string)$i)>{{ $i }}</option>
              @endfor
            </select>
            <label for="f-impact">Impacto (1–5)</label>
          </div>
          @error('impact')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="col col-12 col-md-3">
          <div class="field @error('urgency') is-invalid @enderror">
            <select name="urgency" id="f-urgency">
              <option value="">—</option>
              @for($i=1;$i<=5;$i++)
                <option value="{{ $i }}" @selected((string)$v('urgency')===(string)$i)>{{ $i }}</option>
              @endfor
            </select>
            <label for="f-urgency">Urgencia (1–5)</label>
          </div>
          @error('urgency')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="col col-12 col-md-3">
          <div class="field @error('effort') is-invalid @enderror">
            <select name="effort" id="f-effort">
              <option value="">—</option>
              @for($i=1;$i<=5;$i++)
                <option value="{{ $i }}" @selected((string)$v('effort')===(string)$i)>{{ $i }}</option>
              @endfor
            </select>
            <label for="f-effort">Esfuerzo (1–5)</label>
          </div>
          @error('effort')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="col col-12 col-md-3">
          <div class="field">
            <input type="text" id="f-score" value="(Impacto + Urgencia) - Esfuerzo" placeholder=" " disabled>
            <label for="f-score">Score (auto)</label>
          </div>
          <div class="chips">
            <span class="chip" id="scoreChip">Score: —</span>
          </div>
        </div>
      </div>

      {{-- ===== Evidencias (OPCIONAL): agregar 1 por 1, enviar multiple ===== --}}
      <div class="block section-gap">
        <div class="dropzone" id="dropzone">
          <div class="preview" id="filePreview">
            <div class="placeholder" id="placeholder">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <path d="M14 2v6h6"/>
              </svg>
              <div>Archivos opcionales</div>
              <div style="opacity:.85">Agrega evidencias si aplica</div>
            </div>
            <img id="imgPreview" alt="preview">
          </div>

          <div class="drop-actions">
            <label class="btn-upload" for="filePicker">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12h14"/>
              </svg>
              Agregar archivo
            </label>

            {{-- Picker de 1 archivo (UX), NO se envía --}}
            <input id="filePicker" class="input-file" type="file" accept="*/*">

            <div class="drop-box">o arrastra y suelta aquí (uno a la vez)</div>
            <div class="file-meta" id="fileMeta">0 archivos</div>

            {{-- Input real que se envía (multiple) --}}
            <input id="filesReal" type="file" name="files[]" multiple class="input-file" accept="*/*">
          </div>
        </div>

        <div class="file-list" id="fileList"></div>
        @error('files')<div class="error" style="margin-top:8px;">{{ $message }}</div>@enderror
        @error('files.*')<div class="error" style="margin-top:8px;">{{ $message }}</div>@enderror
      </div>

      {{-- ===== Acciones ===== --}}
      <div class="actions">
        <a href="{{ route('tickets.index') }}" class="btn btn-ghost">Cancelar</a>
        <button type="submit" class="btn btn-primary" id="submitBtn">Crear ticket</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  // ===== Helpers
  function humanSize(bytes){
    if(!bytes) return '';
    const i = Math.floor(Math.log(bytes)/Math.log(1024));
    return (bytes/Math.pow(1024, i)).toFixed(1) + ' ' + ['B','KB','MB','GB','TB'][i];
  }

  // ===== Score chip (solo UI)
  const impact  = document.getElementById('f-impact');
  const urgency = document.getElementById('f-urgency');
  const effort  = document.getElementById('f-effort');
  const scoreChip = document.getElementById('scoreChip');

  function computeScore(){
    const I = parseInt(impact?.value || '', 10);
    const U = parseInt(urgency?.value || '', 10);
    const E = parseInt(effort?.value || '', 10);
    if (Number.isFinite(I) && Number.isFinite(U) && Number.isFinite(E)){
      const s = (I + U) - E;
      scoreChip.textContent = 'Score: ' + s;
    } else {
      scoreChip.textContent = 'Score: —';
    }
  }
  impact?.addEventListener('change', computeScore);
  urgency?.addEventListener('change', computeScore);
  effort?.addEventListener('change', computeScore);
  computeScore();

  // ===== Evidencias (OPCIONAL)
  const dz          = document.getElementById('dropzone');
  const picker      = document.getElementById('filePicker'); // UX: 1 archivo
  const filesReal   = document.getElementById('filesReal');  // REAL: multiple
  const list        = document.getElementById('fileList');
  const meta        = document.getElementById('fileMeta');
  const imgPrev     = document.getElementById('imgPreview');
  const placeholder = document.getElementById('placeholder');

  const selected = [];

  function rebuildRealInput(){
    const dt = new DataTransfer();
    selected.forEach(f => dt.items.add(f));
    filesReal.files = dt.files;
  }

  function renderPreview(file){
    meta.textContent = `${selected.length} archivo${selected.length===1?'':'s'}`;

    if (/^image\//.test(file.type)){
      const rd = new FileReader();
      rd.onload = ev => {
        imgPrev.src = ev.target.result;
        imgPrev.style.display = 'block';
        placeholder.style.display = 'none';
      };
      rd.readAsDataURL(file);
    } else {
      imgPrev.style.display = 'none';
      placeholder.innerHTML = `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <path d="M14 2v6h6"/>
        </svg>
        <div>${file.type || 'Archivo'}</div>
        <div style="opacity:.85">${file.name}</div>
      `;
      placeholder.style.display = 'flex';
    }
  }

  function renderList(){
    list.innerHTML = '';
    if (selected.length === 0){
      const empty = document.createElement('div');
      empty.className = 'file-item';
      empty.innerHTML = `<div class="file-left"><div class="file-name">Sin archivos</div><div class="file-sub">Puedes crear el ticket sin adjuntar evidencias.</div></div>`;
      list.appendChild(empty);
      meta.textContent = '0 archivos';
      imgPrev.style.display = 'none';
      placeholder.style.display = 'flex';
      return;
    }

    selected.forEach((f, idx) => {
      const row = document.createElement('div');
      row.className = 'file-item';

      const left = document.createElement('div');
      left.className = 'file-left';

      const name = document.createElement('div');
      name.className = 'file-name';
      name.title = f.name;
      name.textContent = f.name;

      const sub = document.createElement('div');
      sub.className = 'file-sub';
      sub.textContent = `${f.type || 'archivo'} • ${humanSize(f.size)}`;

      left.appendChild(name);
      left.appendChild(sub);

      const rm = document.createElement('button');
      rm.type = 'button';
      rm.className = 'file-remove';
      rm.textContent = 'Quitar';
      rm.addEventListener('click', () => {
        selected.splice(idx, 1);
        rebuildRealInput();
        renderList();
        meta.textContent = `${selected.length} archivo${selected.length===1?'':'s'}`;
        if (selected.length === 0){
          imgPrev.style.display = 'none';
          placeholder.style.display = 'flex';
        }
      });

      row.appendChild(left);
      row.appendChild(rm);
      list.appendChild(row);
    });

    meta.textContent = `${selected.length} archivo${selected.length===1?'':'s'}`;
  }

  function addFile(file){
    if (!file) return;

    // Evitar duplicados exactos
    const dup = selected.some(x => x.name === file.name && x.size === file.size);
    if (dup){
      alert('Ese archivo ya fue agregado.');
      return;
    }

    selected.push(file);
    rebuildRealInput();
    renderList();
    renderPreview(file);
  }

  picker?.addEventListener('change', (e) => {
    const f = e.target.files?.[0];
    picker.value = '';
    addFile(f);
  });

  // Drag & drop: 1 archivo por vez
  ['dragenter','dragover'].forEach(evt=>{
    dz.addEventListener(evt, e=>{ e.preventDefault(); e.stopPropagation(); dz.classList.add('dragover'); });
  });
  ['dragleave','drop'].forEach(evt=>{
    dz.addEventListener(evt, e=>{ e.preventDefault(); e.stopPropagation(); dz.classList.remove('dragover'); });
  });
  dz.addEventListener('drop', e=>{
    const f = e.dataTransfer?.files?.[0];
    addFile(f);
  });

  renderList();

  // ===== Ya NO se valida mínimo de archivos al enviar (OPCIONAL)
  const form = document.getElementById('tkForm');
  const btn  = document.getElementById('submitBtn');

  form?.addEventListener('submit', ()=> {
    btn.disabled = true;
    btn.textContent = 'Creando...';
  });
})();
</script>
@endsection