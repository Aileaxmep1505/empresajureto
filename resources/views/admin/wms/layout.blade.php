@extends('layouts.app')

@section('title', 'WMS · Layout Builder')

@section('content')
@php
  $whId = (int)($warehouseId ?? ($warehouse->id ?? 1));
@endphp

<div class="wms-wrap">
  <div class="wms-head">
    <div>
      <div class="wms-title">Layout Builder</div>
      <div class="wms-sub">Dibuja tu bodega, genera racks/bin automáticamente y asigna “códigos QR” (URL) por ubicación.</div>
    </div>

    <div class="wms-actions">
      <a class="btn btn-ghost" href="{{ route('admin.wms.home') }}">← WMS</a>
      <a class="btn btn-ghost" href="{{ route('admin.wms.heatmap.view', ['warehouse_id'=>$whId]) }}">🔥 Heatmap</a>

      <form method="GET" action="{{ route('admin.wms.layout.editor') }}" class="wh-form">
        <select name="warehouse_id" class="inp" onchange="this.form.submit()">
          @foreach(($warehouses ?? []) as $w)
            <option value="{{ $w->id }}" @selected((int)$w->id === $whId)>{{ $w->name ?? ('Bodega #'.$w->id) }}</option>
          @endforeach
        </select>
      </form>

      <button class="btn btn-primary" id="btnNewCell" type="button">＋ Nueva ubicación</button>
      <button class="btn btn-dark" id="btnGenRack" type="button">⚙️ Generar racks</button>
    </div>
  </div>

  <div class="wms-grid">
    <!-- Canvas -->
    <div class="canvas-card">
      <div class="canvas-top">
        <div class="canvas-badges">
          <span class="chip">Bodega: #{{ $whId }}</span>
          <span class="chip chip-soft" id="chipCount">0 ubicaciones</span>
        </div>

        <div class="canvas-tools">
          <button class="tool" id="btnZoomOut" title="Zoom -" type="button">－</button>
          <button class="tool" id="btnZoomIn" title="Zoom +" type="button">＋</button>
          <button class="tool" id="btnFit" title="Ajustar" type="button">⤢</button>
          <button class="tool" id="btnReload" title="Recargar" type="button">↻</button>
        </div>
      </div>

      <div class="canvas-wrap" id="canvasWrap">
        <div class="grid-bg" id="gridBg"></div>
        <div class="canvas" id="canvas"></div>
      </div>

      <div class="canvas-foot">
        <div class="muted">Tip: clic a una celda para editar. Usa “Generar racks” para crear stands/racks en lote.</div>
        <div class="muted">El QR apunta a: <code class="code">{{ url('/admin/wms/locations/{id}/page') }}</code></div>
      </div>
    </div>

    <!-- Inspector -->
    <div class="side-card">
      <div class="side-title">Inspector</div>
      <div class="side-sub">Edita la ubicación seleccionada. Guarda y listo.</div>

      <div class="empty" id="emptyState">
        <div class="empty-ic">🧭</div>
        <div class="empty-tt">Selecciona una ubicación</div>
        <div class="empty-tx">Haz clic en un bloque del plano para ver/editar sus datos.</div>
      </div>

      <form id="cellForm" class="form" style="display:none;">
        <input type="hidden" name="id" id="f_id">
        <input type="hidden" name="warehouse_id" value="{{ $whId }}">

        <div class="row">
          <div class="col">
            <label class="lbl">Tipo</label>
            <select class="inp" name="type" id="f_type">
              <option value="aisle">aisle</option>
              <option value="stand">stand</option>
              <option value="rack">rack</option>
              <option value="bin">bin</option>
              <option value="zone">zone</option>
            </select>
          </div>
          <div class="col">
            <label class="lbl">Código *</label>
            <input class="inp" name="code" id="f_code" placeholder="A-01-R01-L01-B01" required>
          </div>
        </div>

        <label class="lbl">Nombre (opcional)</label>
        <input class="inp" name="name" id="f_name" placeholder="Ej: Rack A01 (frente)">

        <div class="hr"></div>

        <div class="row">
          <div class="col">
            <label class="lbl">Pasillo</label>
            <input class="inp" name="aisle" id="f_aisle" placeholder="A">
          </div>
          <div class="col">
            <label class="lbl">Sección</label>
            <input class="inp" name="section" id="f_section" placeholder="01">
          </div>
        </div>

        <div class="row">
          <div class="col">
            <label class="lbl">Stand</label>
            <input class="inp" name="stand" id="f_stand" placeholder="01">
          </div>
          <div class="col">
            <label class="lbl">Rack</label>
            <input class="inp" name="rack" id="f_rack" placeholder="03">
          </div>
        </div>

        <div class="row">
          <div class="col">
            <label class="lbl">Nivel</label>
            <input class="inp" name="level" id="f_level" placeholder="02">
          </div>
          <div class="col">
            <label class="lbl">Bin</label>
            <input class="inp" name="bin" id="f_bin" placeholder="01">
          </div>
        </div>

        <div class="hr"></div>

        <div class="row">
          <div class="col">
            <label class="lbl">X</label>
            <input class="inp" type="number" name="meta[x]" id="f_x" min="0" step="1">
          </div>
          <div class="col">
            <label class="lbl">Y</label>
            <input class="inp" type="number" name="meta[y]" id="f_y" min="0" step="1">
          </div>
        </div>

        <div class="row">
          <div class="col">
            <label class="lbl">W</label>
            <input class="inp" type="number" name="meta[w]" id="f_w" min="1" step="1">
          </div>
          <div class="col">
            <label class="lbl">H</label>
            <input class="inp" type="number" name="meta[h]" id="f_h" min="1" step="1">
          </div>
        </div>

        <label class="lbl">Notas</label>
        <textarea class="inp" rows="3" name="meta[notes]" id="f_notes" placeholder="Ej: papelería pesada, alto flujo..."></textarea>

        <div class="btns">
          <button type="button" class="btn btn-ghost" id="btnCopyQr">📎 Copiar URL QR</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>

        <div class="hint" id="saveHint"></div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Generador -->
<div class="modal" id="rackModal" aria-hidden="true">
  <div class="modal__overlay" data-close></div>
  <div class="modal__panel">
    <div class="modal__head">
      <div>
        <div class="modal__title">Generar racks / bins</div>
        <div class="modal__sub">Crea ubicaciones en lote con posiciones (x,y) automáticas.</div>
      </div>
      <button class="modal__x" data-close type="button">✕</button>
    </div>

    <form id="rackForm" class="modal__body">
      <input type="hidden" name="warehouse_id" value="{{ $whId }}">

      <div class="row">
        <div class="col">
          <label class="lbl">Prefijo pasillo *</label>
          <input class="inp" name="prefix" value="A" required>
        </div>
        <div class="col">
          <label class="lbl">Stand (opcional)</label>
          <input class="inp" name="stand" placeholder="01">
        </div>
      </div>

      <div class="row">
        <div class="col">
          <label class="lbl"># Racks *</label>
          <input class="inp" type="number" name="rack_count" value="5" min="1" max="200" required>
        </div>
        <div class="col">
          <label class="lbl">Niveles *</label>
          <input class="inp" type="number" name="levels" value="3" min="1" max="10" required>
        </div>
        <div class="col">
          <label class="lbl">Bins por nivel *</label>
          <input class="inp" type="number" name="bins" value="4" min="1" max="10" required>
        </div>
      </div>

      <div class="hr"></div>

      <div class="row">
        <div class="col">
          <label class="lbl">Start X *</label>
          <input class="inp" type="number" name="start_x" value="10" min="0" required>
        </div>
        <div class="col">
          <label class="lbl">Start Y *</label>
          <input class="inp" type="number" name="start_y" value="10" min="0" required>
        </div>
      </div>

      <div class="row">
        <div class="col">
          <label class="lbl">Cell W *</label>
          <input class="inp" type="number" name="cell_w" value="8" min="1" required>
        </div>
        <div class="col">
          <label class="lbl">Cell H *</label>
          <input class="inp" type="number" name="cell_h" value="6" min="1" required>
        </div>
      </div>

      <div class="row">
        <div class="col">
          <label class="lbl">Gap X *</label>
          <input class="inp" type="number" name="gap_x" value="3" min="0" required>
        </div>
        <div class="col">
          <label class="lbl">Gap Y *</label>
          <input class="inp" type="number" name="gap_y" value="3" min="0" required>
        </div>
      </div>

      <label class="lbl">Dirección *</label>
      <select class="inp" name="direction" required>
        <option value="right">→ hacia la derecha</option>
        <option value="down">↓ hacia abajo</option>
      </select>

      <div class="modal__foot">
        <div class="hint" id="genHint"></div>
        <button type="button" class="btn btn-ghost" data-close>Cancelar</button>
        <button type="submit" class="btn btn-dark">Generar</button>
      </div>
    </form>
  </div>
</div>

@push('styles')
<style>
  :root{
    --bg:#f6f8fc;
    --card:#fff;
    --ink:#0f172a;
    --muted:#64748b;
    --line:#e5e7eb;
    --line2:#eef2f7;
    --brand:#2563eb;
    --brand2:#1d4ed8;
    --dark:#0b1220;
    --shadow:0 18px 55px rgba(2,6,23,.08);
    --r:18px;
    --r2:14px;
    --ease:cubic-bezier(.2,.8,.2,1);
  }

  .wms-wrap{max-width:1280px;margin:0 auto;padding:18px 14px 26px}
  .wms-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap}
  .wms-title{font-weight:900;font-size:1.15rem;color:var(--ink)}
  .wms-sub{color:var(--muted);font-size:.88rem;margin-top:2px}
  .wms-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .wh-form{margin:0}

  .btn{border:0;border-radius:999px;padding:10px 14px;font-weight:700;font-size:.9rem;cursor:pointer;display:inline-flex;gap:8px;align-items:center;transition:transform .12s ease, box-shadow .12s ease, background .15s ease}
  .btn:hover{transform:translateY(-1px)}
  .btn-primary{background:var(--brand);color:#fff;box-shadow:0 14px 30px rgba(37,99,235,.28)}
  .btn-primary:hover{background:var(--brand2)}
  .btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink);box-shadow:0 10px 25px rgba(2,6,23,.04)}
  .btn-dark{background:var(--dark);color:#fff;box-shadow:0 14px 30px rgba(2,6,23,.18)}
  .btn-dark:hover{background:#111827}

  .inp{width:100%;background:#f8fafc;border:1px solid var(--line);border-radius:12px;padding:10px 12px;min-height:42px;font-size:.92rem;color:var(--ink);transition:border-color .15s ease, box-shadow .15s ease, background .15s ease}
  .inp:focus{outline:none;border-color:#93c5fd;box-shadow:0 0 0 2px #bfdbfe;background:#fff}
  .lbl{display:block;font-weight:800;color:var(--ink);margin:10px 0 5px;font-size:.85rem}
  .hint{color:var(--muted);font-size:.8rem;margin-top:8px}
  .muted{color:var(--muted);font-size:.82rem}
  .code{background:#0b1220;color:#e2e8f0;padding:2px 8px;border-radius:999px}

  .wms-grid{display:grid;grid-template-columns:1.5fr .7fr;gap:14px;margin-top:14px}
  @media(max-width: 1020px){.wms-grid{grid-template-columns:1fr}}

  .canvas-card{background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow);overflow:hidden}
  .canvas-top{display:flex;justify-content:space-between;gap:10px;align-items:center;padding:12px 12px;border-bottom:1px solid var(--line2)}
  .canvas-badges{display:flex;gap:8px;flex-wrap:wrap}
  .chip{background:#f1f5f9;border:1px solid #e2e8f0;color:#0f172a;border-radius:999px;padding:4px 10px;font-weight:800;font-size:.75rem}
  .chip-soft{background:#eff6ff;border-color:#dbeafe;color:#1d4ed8}

  .canvas-tools{display:flex;gap:6px}
  .tool{width:40px;height:40px;border-radius:12px;border:1px solid var(--line);background:#fff;cursor:pointer;font-weight:900;transition:transform .12s ease, box-shadow .12s ease}
  .tool:hover{box-shadow:0 10px 25px rgba(2,6,23,.06);transform:translateY(-1px)}

  .canvas-wrap{position:relative;height:62vh;min-height:520px;background:linear-gradient(180deg,#fbfdff,#f6f8fc)}
  .grid-bg{
    position:absolute;inset:0;
    background-image:
      linear-gradient(to right, rgba(148,163,184,.22) 1px, transparent 1px),
      linear-gradient(to bottom, rgba(148,163,184,.22) 1px, transparent 1px);
    background-size: 22px 22px;
    opacity:.7;
    pointer-events:none;
  }
  .canvas{position:absolute;inset:0;transform-origin:0 0}

  .cell{
    position:absolute;
    border-radius:14px;
    border:1px solid rgba(148,163,184,.55);
    background:rgba(255,255,255,.85);
    box-shadow:0 10px 25px rgba(2,6,23,.06);
    padding:8px 9px;
    cursor:pointer;
    transition:transform .12s ease, box-shadow .12s ease, border-color .15s ease;
    overflow:hidden;
    user-select:none;
  }
  .cell:hover{transform:translateY(-1px);border-color:#60a5fa;box-shadow:0 14px 30px rgba(37,99,235,.12)}
  .cell.is-active{border-color:#2563eb;box-shadow:0 16px 40px rgba(37,99,235,.18);background:#eff6ff}
  .cell .c-code{font-weight:950;color:#0f172a;font-size:.85rem}
  .cell .c-type{font-size:.72rem;color:#64748b;margin-top:2px}
  .cell .c-mini{font-size:.72rem;color:#475569;margin-top:4px}

  .canvas-foot{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:10px 12px;border-top:1px solid var(--line2);background:#fff}

  .side-card{background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow);padding:12px}
  .side-title{font-weight:900;color:var(--ink);font-size:1rem}
  .side-sub{color:var(--muted);font-size:.86rem;margin-top:2px}
  .hr{border-top:1px dashed #e5e7eb;margin:12px 0}

  .row{display:flex;gap:10px;flex-wrap:wrap}
  .col{flex:1 1 120px}

  .btns{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}

  .empty{border:1px dashed #e5e7eb;border-radius:16px;padding:14px;margin-top:12px;background:#f9fafb}
  .empty-ic{font-size:1.6rem}
  .empty-tt{font-weight:900;color:var(--ink);margin-top:4px}
  .empty-tx{color:var(--muted);font-size:.86rem;margin-top:2px}

  /* Modal */
  .modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:9999}
  .modal.is-open{display:flex}
  .modal__overlay{position:absolute;inset:0;background:rgba(2,6,23,.55);backdrop-filter:blur(10px)}
  .modal__panel{position:relative;background:#fff;border-radius:18px;border:1px solid rgba(148,163,184,.3);width:min(720px,92vw);box-shadow:0 30px 80px rgba(2,6,23,.35);overflow:hidden}
  .modal__head{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;padding:14px 14px;border-bottom:1px solid var(--line2)}
  .modal__title{font-weight:950;color:var(--ink)}
  .modal__sub{color:var(--muted);font-size:.85rem;margin-top:2px}
  .modal__x{border:1px solid var(--line);background:#fff;border-radius:12px;width:40px;height:40px;cursor:pointer;font-weight:900}
  .modal__body{padding:12px 14px}
  .modal__foot{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-top:10px}
</style>
@endpush

@push('scripts')
<script>
(function(){
  const CSRF = @json(csrf_token());
  const whId = @json((int)$whId);

  const ROUTES = {
    data: @json(route('admin.wms.layout.data')),
    upsert: @json(route('admin.wms.layout.cell')),
    gen: @json(route('admin.wms.layout.generate-rack')),
    qrBase: @json(url('/admin/wms/locations')),
  };

  const canvas = document.getElementById('canvas');
  const chipCount = document.getElementById('chipCount');

  let zoom = 1;
  let selectedId = null;
  let locations = [];

  function setZoom(z){
    zoom = Math.max(0.4, Math.min(2.2, z));
    canvas.style.transform = `scale(${zoom})`;
  }

  function rectStyle(meta){
    const x = Number(meta?.x ?? 0);
    const y = Number(meta?.y ?? 0);
    const w = Number(meta?.w ?? 6);
    const h = Number(meta?.h ?? 5);

    const U = 22; // px por unidad
    return {
      left: (x * U) + 'px',
      top: (y * U) + 'px',
      width: (w * U) + 'px',
      height: (h * U) + 'px',
    };
  }

  function escapeHtml(str){
    return String(str ?? '')
      .replace(/&/g,"&amp;")
      .replace(/</g,"&lt;")
      .replace(/>/g,"&gt;")
      .replace(/"/g,"&quot;")
      .replace(/'/g,"&#039;");
  }

  function render(){
    canvas.innerHTML = '';
    chipCount.textContent = `${locations.length} ubicaciones`;

    locations.forEach(l => {
      const d = document.createElement('div');
      d.className = 'cell' + (l.id === selectedId ? ' is-active' : '');
      Object.assign(d.style, rectStyle(l.meta || {}));

      const mini = [
        l.aisle ? `Pasillo ${l.aisle}` : null,
        l.stand ? `Stand ${l.stand}` : null,
        l.rack ? `R${l.rack}` : null,
        l.level ? `L${l.level}` : null,
        l.bin ? `B${l.bin}` : null,
      ].filter(Boolean).join(' · ');

      d.innerHTML = `
        <div class="c-code">${escapeHtml(l.code || '')}</div>
        <div class="c-type">${escapeHtml(l.type || '')}</div>
        ${mini ? `<div class="c-mini">${escapeHtml(mini)}</div>` : ``}
      `;

      d.addEventListener('click', () => select(l.id));
      canvas.appendChild(d);
    });
  }

  async function load(){
    const res = await fetch(`${ROUTES.data}?warehouse_id=${encodeURIComponent(whId)}`, {
      headers:{'Accept':'application/json'}
    });
    const json = await res.json();

    locations = (json.locations || []).map(x => ({
      ...x,
      meta: x.meta || {x:0,y:0,w:6,h:5}
    }));

    render();
  }

  function fillForm(l){
    document.getElementById('emptyState').style.display = 'none';
    document.getElementById('cellForm').style.display = 'block';

    document.getElementById('f_id').value = l.id || '';
    document.getElementById('f_type').value = l.type || 'bin';
    document.getElementById('f_code').value = l.code || '';
    document.getElementById('f_name').value = l.name || '';

    document.getElementById('f_aisle').value = l.aisle || '';
    document.getElementById('f_section').value = l.section || '';
    document.getElementById('f_stand').value = l.stand || '';
    document.getElementById('f_rack').value = l.rack || '';
    document.getElementById('f_level').value = l.level || '';
    document.getElementById('f_bin').value = l.bin || '';

    document.getElementById('f_x').value = Number(l.meta?.x ?? 0);
    document.getElementById('f_y').value = Number(l.meta?.y ?? 0);
    document.getElementById('f_w').value = Number(l.meta?.w ?? 6);
    document.getElementById('f_h').value = Number(l.meta?.h ?? 5);
    document.getElementById('f_notes').value = (l.meta?.notes ?? '');
  }

  function select(id){
    selectedId = id;
    const l = locations.find(x => x.id === id);
    if (!l) return;
    fillForm(l);
    render();
  }

  // Tools
  document.getElementById('btnZoomIn').addEventListener('click', ()=>setZoom(zoom+0.1));
  document.getElementById('btnZoomOut').addEventListener('click', ()=>setZoom(zoom-0.1));
  document.getElementById('btnFit').addEventListener('click', ()=>setZoom(1));
  document.getElementById('btnReload').addEventListener('click', load);

  // New cell
  document.getElementById('btnNewCell').addEventListener('click', ()=>{
    selectedId = null;
    document.getElementById('emptyState').style.display = 'none';
    document.getElementById('cellForm').style.display = 'block';

    document.getElementById('f_id').value = '';
    document.getElementById('f_type').value = 'bin';
    document.getElementById('f_code').value = 'NEW-' + Math.random().toString(16).slice(2,6).toUpperCase();
    document.getElementById('f_name').value = '';

    document.getElementById('f_aisle').value = '';
    document.getElementById('f_section').value = '';
    document.getElementById('f_stand').value = '';
    document.getElementById('f_rack').value = '';
    document.getElementById('f_level').value = '';
    document.getElementById('f_bin').value = '';

    document.getElementById('f_x').value = 10;
    document.getElementById('f_y').value = 10;
    document.getElementById('f_w').value = 8;
    document.getElementById('f_h').value = 6;
    document.getElementById('f_notes').value = '';
  });

  // Save
  document.getElementById('cellForm').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(e.target);

    const payload = Object.fromEntries(fd.entries());
    payload.warehouse_id = whId;
    payload.meta = {
      x: Number(payload['meta[x]'] || 0),
      y: Number(payload['meta[y]'] || 0),
      w: Number(payload['meta[w]'] || 6),
      h: Number(payload['meta[h]'] || 5),
      notes: payload['meta[notes]'] || '',
    };
    delete payload['meta[x]']; delete payload['meta[y]']; delete payload['meta[w]']; delete payload['meta[h]']; delete payload['meta[notes]'];

    const hint = document.getElementById('saveHint');
    hint.textContent = 'Guardando...';

    const res = await fetch(ROUTES.upsert, {
      method:'POST',
      headers:{
        'X-CSRF-TOKEN':CSRF,
        'Accept':'application/json',
        'Content-Type':'application/json'
      },
      body: JSON.stringify(payload)
    });
    const json = await res.json();

    if (!json.ok){
      hint.textContent = json.error || 'No se pudo guardar.';
      return;
    }

    hint.textContent = 'Guardado ✅';
    await load();
    if (json.location?.id) select(json.location.id);
  });

  // Copy QR URL
  document.getElementById('btnCopyQr').addEventListener('click', async ()=>{
    const id = document.getElementById('f_id').value;
    if (!id) return alert('Guarda primero para obtener ID.');
    const url = `${ROUTES.qrBase}/${id}/page`;

    try {
      await navigator.clipboard.writeText(url);
      document.getElementById('saveHint').textContent = 'URL copiada ✅';
    } catch(e){
      prompt('Copia la URL:', url);
    }
  });

  // Modal
  const modal = document.getElementById('rackModal');
  function openModal(){ modal.classList.add('is-open'); modal.setAttribute('aria-hidden','false'); }
  function closeModal(){ modal.classList.remove('is-open'); modal.setAttribute('aria-hidden','true'); }

  document.getElementById('btnGenRack').addEventListener('click', openModal);
  modal.querySelectorAll('[data-close]').forEach(el => el.addEventListener('click', closeModal));

  document.getElementById('rackForm').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(e.target);
    const payload = Object.fromEntries(fd.entries());

    const hint = document.getElementById('genHint');
    hint.textContent = 'Generando...';

    const res = await fetch(ROUTES.gen, {
      method:'POST',
      headers:{
        'X-CSRF-TOKEN':CSRF,
        'Accept':'application/json',
        'Content-Type':'application/json'
      },
      body: JSON.stringify(payload)
    });
    const json = await res.json();

    if (!json.ok){
      hint.textContent = json.error || 'No se pudo generar.';
      return;
    }

    hint.textContent = `Listo ✅ creados: ${json.created}`;
    await load();
    setTimeout(()=>closeModal(), 450);
  });

  // init
  setZoom(1);
  load();
})();
</script>
@endpush
@endsection
