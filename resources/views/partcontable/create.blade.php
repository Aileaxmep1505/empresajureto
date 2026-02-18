@extends('layouts.app')
@section('title','Subir documentos - '.$company->name)

@section('content')
@php
    // Preparamos las secciones + subtipos para JS (evita el error de corchetes)
    $sectionsForJs = $sections->map(function($s){
        return [
            'id'   => $s->id,
            'name' => $s->name,
            'subtypes' => $s->subtypes->map(function($st){
                return [
                    'id'   => $st->id,
                    'name' => $st->name,
                ];
            })->values()->all(),
        ];
    })->values()->all();
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">

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

/* Page wrapper */
.upload-wrap{max-width:980px;margin:56px auto;padding:18px;}
.panel{background:var(--card);border-radius:14px;box-shadow:var(--shadow);overflow:hidden;}
.panel-head{padding:20px 22px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:16px;}
.hgroup h2{margin:0;font-weight:700;font-size:20px;}
.hgroup p{margin:4px 0 0;color:var(--muted);font-size:13px;}
.back-link{display:inline-flex;align-items:center;gap:8px;color:var(--muted);text-decoration:none;padding:8px 12px;border-radius:10px;border:1px solid var(--line);background:#fff;}
.back-link:hover{border-color:#dbe7ef;color:var(--ink);}

/* Form */
.form{padding:20px;}
.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;}
@media(max-width:820px){ .grid{grid-template-columns:1fr} }

.field{position:relative;background:#fff;border:1px solid var(--line);border-radius:12px;padding:12px;transition:box-shadow .15s,border-color .15s;}
.field:focus-within{border-color:#d1e7de;box-shadow:0 8px 20px rgba(52,194,158,0.06);}
.field input, .field select, .field textarea{width:100%;border:0;outline:0;background:transparent;font-size:14px;color:var(--ink);padding-top:8px;}
.field label{position:absolute;left:14px;top:12px;color:var(--muted);font-size:12px;pointer-events:none;transition:all .14s;}
.field input::placeholder{color:transparent;}
.field input:focus + label,
.field textarea:focus + label,
.field input:not(:placeholder-shown) + label,
.field textarea:not(:placeholder-shown) + label{
  top:6px;font-size:11px;color:var(--mint-dark);transform:translateY(-6px)
}

/* dropzone / preview */
.block{border-radius:12px;padding:14px;background:#fbfdff;border:1px dashed var(--line);}
.uploader{display:flex;gap:14px;align-items:flex-start;flex-direction:column;}
.drop{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
.btn-file{background:var(--mint);color:#fff;padding:10px 14px;border-radius:999px;border:none;cursor:pointer;font-weight:700;box-shadow:0 10px 22px rgba(72,207,173,0.16);}
.btn-file:hover{background:var(--mint-dark)}
.small{color:var(--muted);font-size:12px}

/* preview tiles */
.preview{display:flex;flex-wrap:wrap;gap:12px;margin-top:12px}
.tile{width:180px;background:#fff;border-radius:12px;border:1px solid #f0f3f6;box-shadow:0 10px 20px rgba(8,12,20,0.03);overflow:hidden;display:flex;flex-direction:column;position:relative}
.tile-media{height:110px;background:#f6f8fa;display:grid;place-items:center;overflow:hidden}
.tile-media img, .tile-media video{width:100%;height:100%;object-fit:cover;display:block}
.tile-body{padding:10px;display:flex;flex-direction:column;gap:6px}
.tile .name{font-weight:700;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.tile .meta{font-size:12px;color:var(--muted)}
.remove{position:absolute;top:8px;right:8px;background:rgba(0,0,0,0.5);color:#fff;width:30px;height:30px;border-radius:8px;display:grid;place-items:center;cursor:pointer;font-weight:700}

/* progress */
.progress{height:8px;background:#f1f5f7;border-radius:999px;overflow:hidden;margin-top:8px}
.progress > span{display:block;height:100%;width:0;background:linear-gradient(90deg,var(--mint),var(--mint-dark));transition:width .18s linear}

/* actions */
.actions{display:flex;gap:12px;justify-content:flex-end;margin-top:18px}
.btn{border:0;border-radius:10px;padding:10px 14px;font-weight:700;cursor:pointer}
.btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink)}
.btn-primary{background:var(--mint);color:#fff;box-shadow:0 8px 20px rgba(52,194,158,0.12)}
.btn-danger{background:var(--danger);color:#fff}

/* status area */
.status{margin-top:12px;font-size:13px;color:var(--muted)}

/* errors */
.error{color:var(--danger);font-size:13px;margin-top:6px}

/* responsive */
@media(max-width:640px){ .tile{width:48%} .actions{flex-direction:column;gap:8px} }
@media(max-width:420px){ .tile{width:100%} }
</style>

<div class="upload-wrap">
  <div class="panel">
    <div class="panel-head">
      <div class="hgroup">
        <h2>Subir documentos — {{ $company->name }}</h2>
        <p>Selecciona varios archivos (imágenes, videos, PDF, Word, Excel). Cada archivo se sube individualmente y verás el progreso.</p>
      </div>

      <a href="{{ route('partcontable.company', $company->slug) }}" class="back-link" title="Volver">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg> Volver
      </a>
    </div>

    <form id="upload-form" class="form" method="POST" enctype="multipart/form-data" novalidate>
      @csrf

      <div class="grid">
        {{-- SECCIÓN (Declaración Anual, Declaración Mensual, etc.) --}}
        <div>
          <div class="field">
            <select name="section_id" id="section_id" required>
              @foreach($sections as $s)
                <option value="{{ $s->id }}"
                  {{ isset($defaultSection) && $defaultSection->id === $s->id ? 'selected' : '' }}>
                  {{ $s->name }}
                </option>
              @endforeach
            </select>
            <label for="section_id">Sección</label>
          </div>
        </div>

        {{-- SUBCATEGORÍA (depende de la sección) --}}
        <div>
          <div class="field">
            <select name="subtype_id" id="subtype_id">
              <option value="">— Ninguno —</option>
              {{-- El JS llenará el resto según la sección --}}
            </select>
            <label for="subtype_id">Subcategoría (opcional)</label>
          </div>
        </div>

        <div>
          <div class="field">
            <input type="date" name="date" id="date" value="{{ now()->toDateString() }}" placeholder=" " />
            <label for="date">Fecha del documento</label>
          </div>
        </div>

        <div>
          <div class="field">
            <input type="text" name="title_global" id="title_global" placeholder=" " />
            <label for="title_global">Título (opcional) — aplicado a todos si se llena</label>
          </div>
        </div>

        <div style="grid-column: 1 / -1;">
          <div class="field">
            <textarea name="description_global" id="description_global" rows="2" placeholder=" "></textarea>
            <label for="description_global">Descripción (opcional) — aplicada a todos</label>
          </div>
        </div>
      </div>

      <div style="margin-top:16px;">
        <div class="block">
          <div class="uploader">
            <div class="drop">
              <label class="btn-file" for="files">Seleccionar archivos</label>
              <input id="files" type="file"
                accept=".jpg,.jpeg,.png,.gif,.webp,.svg,.mp4,.mov,.pdf,.doc,.docx,.xls,.xlsx"
                multiple style="display:none;">
              <div class="small">Formatos permitidos: imágenes, video, pdf, doc, xls. Tamaño máximo por archivo: 30MB.</div>
            </div>

            <div class="preview" id="preview" aria-live="polite" aria-atomic="true"></div>

            <div class="status" id="globalStatus">No hay archivos en cola.</div>
          </div>
        </div>
      </div>

      <div class="actions">
        <button type="button" id="startUpload" class="btn btn-primary">Iniciar subida</button>
        <button type="button" id="cancelUpload" class="btn btn-ghost" disabled>Cancelar</button>
      </div>

      <div id="uploadSummary" style="margin-top:10px;"></div>
    </form>
  </div>
</div>

<script>
(function(){
  'use strict';

  /* ============================================
   *  MAPA SECCIONES → SUBTIPOS (desde backend)
   * ============================================ */
  const pcSections = @json($sectionsForJs);

  // ✅ Mapeos por NOMBRE → KEY (porque en sectionsForJs no vienen keys)
  // Si cambias nombres en BD, ajusta aquí.
  const sectionNameToKey = {
    'Declaración Anual': 'declaracion_anual',
    'Declaración Mensual': 'declaracion_mensual',
    'Constancias / Opiniones': 'constancias',
    'Estados Financieros': 'estados_financieros',
  };

  const subtypeNameToKey = {
    // Declaración Anual
    'Acuse anual': 'acuse_anual',
    'Pago anual': 'pago_anual',
    'Declaración anual': 'declaracion_anual',

    // Declaración Mensual
    'Acuse mensual': 'acuse_mensual',
    'Pago mensual': 'pago_mensual',
    'Declaración mensual': 'declaracion_mensual',

    // Constancias / Opiniones
    'Constancia de situación fiscal': 'csf',
    'Opinión estatal Nuevo León': 'opinion_nl',
    'Opinión estatal Estado de México': 'opinion_edomex',
    '32-D SAT': '32d_sat',
    'INFONAVIT': 'infonavit',
    'Opinión IMSS': 'opinion_imss',

    // Estados Financieros
    'Balance general': 'balance_general',
    'Estado de resultados': 'estado_resultados',
  };

  const defaultSubBySection = {
    'declaracion_anual': 'acuse_anual',
    'declaracion_mensual': 'acuse_mensual',
    'constancias': 'csf',
    'estados_financieros': 'balance_general',
  };

  function renderSubtypes(sectionId){
    const select = document.getElementById('subtype_id');
    if(!select) return;

    // Limpiar y poner "Ninguno"
    select.innerHTML = '';
    const optNone = document.createElement('option');
    optNone.value = '';
    optNone.textContent = '— Ninguno —';
    select.appendChild(optNone);

    const sec = pcSections.find(s => String(s.id) === String(sectionId));
    if(!sec) return;

    (sec.subtypes || []).forEach(st => {
      const opt = document.createElement('option');
      opt.value = st.id;
      opt.textContent = st.name;
      select.appendChild(opt);
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    /* ============================
     *   INICIALIZAR SUBCATEGORÍAS
     * ============================ */
    const sectionSelect = document.getElementById('section_id');
    if(sectionSelect){
      renderSubtypes(sectionSelect.value);
      sectionSelect.addEventListener('change', function(e){
        renderSubtypes(e.target.value);
      });
    }

    /* ============================
     *   LÓGICA DE SUBIDA MULTIPLE
     * ============================ */
    const input   = document.getElementById('files');
    const preview = document.getElementById('preview');
    const startBtn = document.getElementById('startUpload');
    const cancelBtn = document.getElementById('cancelUpload');
    const status = document.getElementById('globalStatus');
    const summary = document.getElementById('uploadSummary');

    const maxSizeBytes = 30 * 1024 * 1024; // 30MB
    const allowedTypes = [
      'image/jpeg','image/png','image/gif','image/webp','image/svg+xml',
      'video/mp4','video/quicktime',
      'application/pdf',
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];

    let queue = []; // { id, file, url, tile, progressBar, statusNode }
    let uploading = false;
    let currentXhr = null;
    let aborted = false;

    function fmtSize(b){
      if(b < 1024) return b + ' B';
      if(b < 1024*1024) return Math.round(b/1024) + ' KB';
      return (b/(1024*1024)).toFixed(2) + ' MB';
    }

    function createTile(file, idx){
      const id = 'f' + Date.now() + '_' + idx;
      const tile = document.createElement('div'); tile.className = 'tile'; tile.dataset.id = id;

      const remove = document.createElement('div'); remove.className = 'remove'; remove.title = 'Eliminar';
      remove.innerHTML = '×';
      tile.appendChild(remove);

      const media = document.createElement('div'); media.className = 'tile-media';
      let url = null;
      const type = file.type || '';
      if(type.startsWith('image/')){
        url = URL.createObjectURL(file);
        const img = document.createElement('img'); img.src = url; media.appendChild(img);
      } else if(type.startsWith('video/')){
        url = URL.createObjectURL(file);
        const vid = document.createElement('video'); vid.src = url; vid.muted = true; vid.loop = true; vid.autoplay = true; media.appendChild(vid);
      } else {
        const icon = document.createElement('div'); icon.style.fontSize = '28px'; icon.textContent = '📄'; media.appendChild(icon);
      }
      tile.appendChild(media);

      const body = document.createElement('div'); body.className = 'tile-body';
      const name = document.createElement('div'); name.className = 'name'; name.textContent = file.name;
      const meta = document.createElement('div'); meta.className = 'meta'; meta.textContent = fmtSize(file.size) + ' • ' + (file.type || 'document');
      const prog = document.createElement('div'); prog.className = 'progress'; const bar = document.createElement('span'); prog.appendChild(bar);
      const st = document.createElement('div'); st.className = 'meta'; st.style.marginTop='6px'; st.textContent = 'En cola';

      body.appendChild(name); body.appendChild(meta); body.appendChild(prog); body.appendChild(st);
      tile.appendChild(body);

      // remove handler
      remove.addEventListener('click', () => {
        if(uploading){ alert('No puedes eliminar durante la subida. Cancela primero.'); return; }
        if(url) URL.revokeObjectURL(url);
        queue = queue.filter(q => q.id !== id);
        renderPreview();
      });

      return { id, file, url, tile, progressBar: bar, statusNode: st };
    }

    function renderPreview(){
      preview.innerHTML = '';
      queue.forEach(q => preview.appendChild(q.tile));
      status.textContent = queue.length ? (queue.length + ' archivo(s) en cola') : 'No hay archivos en cola.';
      startBtn.disabled = !queue.length || uploading;
    }

    input.addEventListener('change', (e) => {
      const chosen = Array.from(e.target.files || []);
      if(!chosen.length) return;
      const initial = queue.length;
      chosen.forEach((f,i) => {
        if(!allowedTypes.includes(f.type)){
          alert('Formato no permitido: ' + f.name);
          return;
        }
        if(f.size > maxSizeBytes){
          alert('El archivo ' + f.name + ' supera el límite de ' + (maxSizeBytes/1024/1024) + 'MB');
          return;
        }
        const tileObj = createTile(f, initial + i);
        queue.push(tileObj);
      });
      input.value = '';
      renderPreview();
    });

    // Metadatos globales
    function gatherMeta(){
      return {
        section_id: document.getElementById('section_id').value,
        subtype_id: document.getElementById('subtype_id').value,
        title: document.getElementById('title_global').value,
        description: document.getElementById('description_global').value,
        date: document.getElementById('date').value,
        _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      };
    }

    function parseResponse(xhr){
      try {
        if(xhr.responseType === 'json') return xhr.response;
        return JSON.parse(xhr.responseText || '{}');
      } catch (e){
        return { message: xhr.responseText || 'Respuesta inválida' };
      }
    }

    function uploadOne(item, meta){
      return new Promise((resolve) => {
        const formData = new FormData();
        formData.append('file', item.file);
        formData.append('section_id', meta.section_id);
        if(meta.subtype_id)    formData.append('subtype_id', meta.subtype_id);
        if(meta.title)        formData.append('title_global', meta.title);
        if(meta.description)  formData.append('description_global', meta.description);
        if(meta.date)         formData.append('date', meta.date);
        formData.append('_token', meta._token);

        const xhr = new XMLHttpRequest();
        currentXhr = xhr;
        xhr.open('POST', '{{ route("partcontable.documents.store", $company->slug) }}', true);

        try { xhr.responseType = 'json'; } catch(e){}

        xhr.upload.onprogress = function(e){
          if(e.lengthComputable && item.progressBar){
            const pct = Math.round((e.loaded / e.total) * 100);
            item.progressBar.style.width = pct + '%';
          }
        };

        xhr.onload = function(){
          currentXhr = null;
          const ok = xhr.status >= 200 && xhr.status < 300;
          const res = parseResponse(xhr);
          if(ok){
            item.statusNode.textContent = 'Subido';
            item.progressBar.style.width = '100%';
            if(item.url) { URL.revokeObjectURL(item.url); item.url = null; }
            resolve({ ok:true, status: xhr.status, body: res });
          } else {
            let message = res && (res.message || (res.errors && Object.values(res.errors).flat().join('; '))) || ('Error al subir (status ' + xhr.status + ')');
            item.statusNode.textContent = message;
            item.progressBar.style.width = '0%';
            resolve({ ok:false, status:xhr.status, body: res });
          }
        };

        xhr.onerror = function(){
          currentXhr = null;
          item.statusNode.textContent = 'Error de red';
          resolve({ ok:false, error:'network' });
        };

        xhr.onabort = function(){
          currentXhr = null;
          item.statusNode.textContent = 'Cancelado';
          resolve({ ok:false, error:'aborted' });
        };

        xhr.send(formData);
      });
    }

    // ✅ Construye la URL de regreso EXACTA a donde se subió (sección/subtipo/año/mes)
    function buildReturnUrl(meta){
      const secId = String(meta.section_id || '');
      const subId = String(meta.subtype_id || '');
      const date  = String(meta.date || '');

      const secObj = pcSections.find(s => String(s.id) === secId) || null;

      const sectionKey = secObj ? (sectionNameToKey[secObj.name] || 'declaracion_anual') : 'declaracion_anual';

      let subtypeKey = '';
      if(secObj && subId){
        const st = (secObj.subtypes || []).find(x => String(x.id) === subId);
        if(st && st.name){
          subtypeKey = subtypeNameToKey[st.name] || '';
        }
      }
      if(!subtypeKey){
        subtypeKey = defaultSubBySection[sectionKey] || 'acuse_anual';
      }

      // year/month desde date (YYYY-MM-DD)
      let year = '';
      let month = '';
      if(date && /^\d{4}-\d{2}-\d{2}$/.test(date)){
        year = date.slice(0,4);
        month = String(parseInt(date.slice(5,7),10));
      }

      const base = '{{ route("partcontable.company", $company->slug) }}';
      const params = new URLSearchParams();
      params.set('section', sectionKey);
      params.set('subtipo', subtypeKey);
      if(year) params.set('year', year);
      if(month) params.set('month', month);

      return base + '?' + params.toString();
    }

    async function startUpload(){
      if(!queue.length) { alert('Selecciona primero archivos.'); return; }
      uploading = true;
      aborted = false;
      startBtn.disabled = true;
      cancelBtn.disabled = false;
      status.textContent = 'Iniciando subida...';
      summary.innerHTML = '';

      const meta = gatherMeta();
      const results = [];

      for(let i=0;i<queue.length;i++){
        if(aborted) break;
        const item = queue[i];
        status.textContent = `Subiendo ${i+1} de ${queue.length}: ${item.file.name}`;
        item.statusNode.textContent = 'Subiendo...';
        const res = await uploadOne(item, meta);
        results.push(res);
        await new Promise(r => setTimeout(r, 180));
      }

      uploading = false;
      cancelBtn.disabled = true;
      startBtn.disabled = false;
      currentXhr = null;

      const ok = results.filter(r => r.ok).length;
      const failed = results.length - ok;
      status.textContent = `Finalizado — ${ok} subidos, ${failed} con error.`;
      summary.innerHTML = `<div style="font-size:13px;margin-top:8px;color:${failed? 'var(--danger)': 'var(--mint-dark)'}">${ok} ok — ${failed} fallidos</div>`;

      if(ok > 0){
        const returnUrl = buildReturnUrl(meta);
        setTimeout(()=> {
          window.location.href = returnUrl;
        }, 650);
      }
    }

    startBtn.addEventListener('click', startUpload);

    cancelBtn.addEventListener('click', function(){
      if(!uploading) return;
      aborted = true;
      if(currentXhr) currentXhr.abort();
      cancelBtn.disabled = true;
      startBtn.disabled = false;
      status.textContent = 'Cancelando subida...';
      queue.forEach(q => {
        if(q.statusNode && q.statusNode.textContent === 'En cola')
          q.statusNode.textContent = 'Cancelado';
      });
    });

    document.getElementById('upload-form').addEventListener('submit', function(e){
      e.preventDefault();
      startUpload();
    });
  });
})();
</script>

@endsection
