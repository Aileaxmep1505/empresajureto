@extends('layouts.app')

@section('title', 'WMS · Heatmap')

@section('content')
@php
  $whId = (int)($warehouseId ?? 1);
@endphp

<div class="hm-wrap">
  <div class="hm-head">
    <div>
      <div class="hm-title">Heatmap</div>
      <div class="hm-sub">
        Visualiza zonas calientes por stock. Cambia métrica entre <b>primary_stock</b> y <b>inv_qty</b>.
      </div>
    </div>

    <div class="hm-actions">
      <a class="btn btn-ghost" href="{{ route('admin.wms.home') }}">← WMS</a>
      <a class="btn btn-ghost" href="{{ route('admin.wms.layout.editor', ['warehouse_id'=>$whId]) }}">🧩 Layout</a>

      <form method="GET" action="{{ route('admin.wms.heatmap.view') }}" class="wh-form">
        <select name="warehouse_id" class="inp" onchange="this.form.submit()">
          @foreach(($warehouses ?? []) as $w)
            <option value="{{ $w->id }}" @selected((int)$w->id === $whId)>
              {{ $w->name ?? ('Bodega #'.$w->id) }}
            </option>
          @endforeach
        </select>
      </form>

      <select id="metric" class="inp">
        <option value="primary_stock">primary_stock (Item.stock)</option>
        <option value="inv_qty">inv_qty (Inventory.qty)</option>
      </select>

      <button class="btn btn-primary" id="btnReload" type="button">↻ Actualizar</button>
    </div>
  </div>

  <div class="hm-card">
    <div class="hm-top">
      <div class="legend">
        <span class="dot d0"></span><span>0</span>
        <span class="dot d1"></span><span>bajo</span>
        <span class="dot d2"></span><span>medio</span>
        <span class="dot d3"></span><span>alto</span>
        <span class="dot d4"></span><span>máximo</span>
      </div>
      <div class="muted" id="metaLine">—</div>
    </div>

    <div class="hm-canvas-wrap">
      <div class="hm-empty" id="hmEmpty" style="display:none;">
        <div>
          <div class="hm-empty-tt">No hay celdas para mostrar</div>
          <div class="hm-empty-sub">
            Esto suele pasar si tus ubicaciones no tienen coordenadas en <code>locations.meta</code> (x, y, w, h),
            o si el warehouse seleccionado no tiene ubicaciones.
            <br>
            Ve a <a href="{{ route('admin.wms.layout.editor', ['warehouse_id'=>$whId]) }}">Layout</a> y asegúrate de guardar celdas.
          </div>
        </div>
      </div>

      {{-- “Escena” tipo bodega --}}
      <div class="hm-stage" id="hmStage">
        <div class="hm-stage-grid"></div>
        <div class="hm-canvas" id="hmCanvas"></div>
      </div>

      {{-- Tooltip --}}
      <div class="hm-tip" id="hmTip" style="display:none;">
        <div class="hm-tip-tt" id="hmTipTt">—</div>
        <div class="hm-tip-row">
          <span class="hm-pill" id="hmTipMetric">—</span>
          <span class="hm-pill hm-pill-strong" id="hmTipVal">—</span>
        </div>
        <div class="hm-tip-sub" id="hmTipSub">—</div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  :root{
    --ink:#0f172a; --muted:#64748b; --line:#e5e7eb; --bg:#f6f8fc;
    --card:#fff; --shadow:0 18px 55px rgba(2,6,23,.08);
    --brand:#2563eb; --r:18px;

    --stage:#f7fafc;
    --grid:rgba(148,163,184,.18);
    --rackEdge:rgba(15,23,42,.18);
  }

  .hm-wrap{max-width:1280px;margin:0 auto;padding:18px 14px 26px}
  .hm-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap}
  .hm-title{font-weight:950;color:var(--ink);font-size:1.15rem}
  .hm-sub{color:var(--muted);font-size:.88rem;margin-top:2px}
  .hm-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}

  .btn{
    border:0;border-radius:999px;padding:10px 14px;font-weight:900;font-size:.9rem;
    cursor:pointer;display:inline-flex;gap:8px;align-items:center;
    transition:transform .12s ease, box-shadow .12s ease, background .15s ease
  }
  .btn:hover{transform:translateY(-1px)}
  .btn-primary{background:var(--brand);color:#fff;box-shadow:0 14px 30px rgba(37,99,235,.25)}
  .btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink);box-shadow:0 10px 25px rgba(2,6,23,.04)}
  .inp{background:#f8fafc;border:1px solid var(--line);border-radius:12px;padding:10px 12px;min-height:42px}

  .hm-card{margin-top:14px;background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow);overflow:hidden}
  .hm-top{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;padding:12px 12px;border-bottom:1px solid #eef2f7}
  .muted{color:var(--muted);font-size:.85rem}

  .legend{display:flex;gap:10px;align-items:center;flex-wrap:wrap;font-size:.8rem;color:var(--muted)}
  .dot{width:14px;height:14px;border-radius:6px;display:inline-block;border:1px solid rgba(148,163,184,.55)}
  .d0{background:#fff}
  .d1{background:rgba(34,197,94,.20)}
  .d2{background:rgba(234,179,8,.22)}
  .d3{background:rgba(249,115,22,.25)}
  .d4{background:rgba(239,68,68,.26)}

  .hm-canvas-wrap{position:relative}

  /* EMPTY overlay */
  .hm-empty{
    position:absolute;inset:0;
    display:flex;align-items:center;justify-content:center;
    padding:24px;text-align:center;
    background:linear-gradient(180deg,#ffffffcc,#ffffffb8);
    backdrop-filter: blur(8px);
    z-index:30;
  }
  .hm-empty-tt{font-weight:950;color:#0f172a;font-size:1.05rem}
  .hm-empty-sub{margin-top:8px;color:#64748b;font-size:.9rem;line-height:1.4}
  .hm-empty-sub code{background:#f1f5f9;border:1px solid #e2e8f0;padding:2px 6px;border-radius:8px}

  /* ====== Stage “bodega 3D” ====== */
  .hm-stage{
    position:relative;
    height:70vh;min-height:560px;
    overflow:auto;
    background:linear-gradient(180deg,#fbfdff,#f6f8fc);
  }

  /* piso tipo “plano” con perspectiva */
  .hm-stage-grid{
    position:absolute;inset:0;
    background:
      radial-gradient(900px 420px at 50% 0%, rgba(37,99,235,.10), rgba(37,99,235,0) 65%),
      linear-gradient(180deg, #ffffff 0%, var(--stage) 100%);
    pointer-events:none;
  }

  /* Contenedor con perspectiva/isométrico suave */
  .hm-canvas{
    position:relative;
    min-height:560px;
    transform-origin: 0 0;
    transform: perspective(1200px) rotateX(12deg) rotateZ(-1deg);
    padding:22px;
  }

  /* ====== Rack cell “3D card” ====== */
  .hm-cell{
    position:absolute;
    border-radius:14px;
    cursor:pointer;
    user-select:none;
    padding:10px 10px;

    /* “cuerpo rack” */
    border:1px solid rgba(148,163,184,.40);
    box-shadow:
      0 18px 30px rgba(2,6,23,.08),
      0 2px 0 rgba(255,255,255,.55) inset;

    /* para pseudo-3d */
    overflow:visible;
    transition: transform .10s ease, box-shadow .10s ease, border-color .10s ease, filter .10s ease;
  }

  /* “techo” */
  .hm-cell::before{
    content:"";
    position:absolute;left:8px;right:8px;top:-8px;height:12px;
    border-radius:12px 12px 10px 10px;
    background:rgba(255,255,255,.55);
    border:1px solid rgba(148,163,184,.35);
    box-shadow:0 10px 18px rgba(2,6,23,.06);
  }

  /* “base/sombra” */
  .hm-cell::after{
    content:"";
    position:absolute;left:10px;right:10px;bottom:-10px;height:14px;
    border-radius:14px;
    background:rgba(2,6,23,.08);
    filter: blur(10px);
    z-index:-1;
  }

  .hm-cell:hover{
    transform: translateY(-2px) scale(1.01);
    box-shadow:
      0 22px 40px rgba(2,6,23,.12),
      0 2px 0 rgba(255,255,255,.65) inset;
    border-color:rgba(59,130,246,.55);
    filter:saturate(1.05);
  }

  .hm-cell .t{font-weight:950;color:#0f172a;font-size:.86rem;line-height:1.1}
  .hm-cell .s{font-size:.74rem;color:#475569;margin-top:4px}

  /* ====== Tooltip ====== */
  .hm-tip{
    position:fixed; /* flotante */
    z-index:9999;
    max-width:320px;
    background:rgba(255,255,255,.92);
    border:1px solid rgba(226,232,240,.9);
    border-radius:14px;
    box-shadow:0 22px 60px rgba(2,6,23,.18);
    backdrop-filter: blur(10px);
    padding:10px 10px;
    pointer-events:none;
  }
  .hm-tip-tt{font-weight:950;color:#0f172a}
  .hm-tip-row{display:flex;gap:8px;align-items:center;margin-top:8px;flex-wrap:wrap}
  .hm-pill{
    display:inline-flex;align-items:center;
    padding:6px 10px;border-radius:999px;
    background:#f1f5f9;border:1px solid #e2e8f0;
    color:#334155;font-weight:900;font-size:.78rem;
  }
  .hm-pill-strong{background:#eff6ff;border-color:#dbeafe;color:#1e40af}
  .hm-tip-sub{margin-top:8px;color:#64748b;font-size:.82rem;line-height:1.35}
  .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}

  @media (max-width: 900px){
    .hm-actions{gap:8px}
    .hm-stage{min-height:520px}
    .hm-canvas{transform: perspective(1200px) rotateX(8deg) rotateZ(0deg)}
  }
</style>
@endpush

@push('scripts')
<script>
(function(){
  const whId = @json((int)$whId);
  const dataUrl = @json(route('admin.wms.heatmap.data'));

  const canvas = document.getElementById('hmCanvas');
  const metricSel = document.getElementById('metric');
  const metaLine = document.getElementById('metaLine');
  const empty = document.getElementById('hmEmpty');
  const btn = document.getElementById('btnReload');

  // Tooltip
  const tip = document.getElementById('hmTip');
  const tipTt = document.getElementById('hmTipTt');
  const tipMetric = document.getElementById('hmTipMetric');
  const tipVal = document.getElementById('hmTipVal');
  const tipSub = document.getElementById('hmTipSub');

  function colorFor(v, max){
    if (!max || max <= 0) return 'rgba(255,255,255,.92)';
    const r = v / max;
    if (r <= 0) return 'rgba(255,255,255,.92)';
    if (r < 0.25) return 'rgba(34,197,94,.20)';
    if (r < 0.55) return 'rgba(234,179,8,.22)';
    if (r < 0.80) return 'rgba(249,115,22,.25)';
    return 'rgba(239,68,68,.26)';
  }

  function rectToPx(c){
    const U = 22; // unidad
    return {
      left: (Number(c.x||0) * U) + 'px',
      top: (Number(c.y||0) * U) + 'px',
      width: (Math.max(1, Number(c.w||1)) * U) + 'px',
      height: (Math.max(1, Number(c.h||1)) * U) + 'px',
    };
  }

  function escapeHtml(str){
    return String(str ?? '')
      .replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
      .replace(/"/g,"&quot;").replace(/'/g,"&#039;");
  }

  function showTipAt(x, y, payload){
    // offset
    const pad = 14;
    tip.style.display = 'block';
    tipTt.innerHTML = `<span class="mono"><b>${escapeHtml(payload.code || '')}</b></span>`;
    tipMetric.textContent = payload.metricLabel || '—';
    tipVal.textContent = 'Valor: ' + String(payload.value ?? 0);

    tipSub.innerHTML = `
      <div>Ubicación: <span class="mono"><b>${escapeHtml(payload.code || '—')}</b></span></div>
      <div>Click para abrir ubicación.</div>
    `;

    // posicionar evitando salirse de pantalla
    const w = 320;
    const h = 130;
    let left = x + pad;
    let top = y + pad;

    if (left + w > window.innerWidth - 8) left = x - w - pad;
    if (top + h > window.innerHeight - 8) top = y - h - pad;

    tip.style.left = left + 'px';
    tip.style.top = top + 'px';
  }

  function hideTip(){
    tip.style.display = 'none';
  }

  async function load(){
    const metric = metricSel.value || 'inv_qty';
    metaLine.textContent = 'Cargando...';
    empty.style.display = 'none';
    hideTip();
    canvas.innerHTML = '';

    let res, json;
    try{
      res = await fetch(`${dataUrl}?warehouse_id=${encodeURIComponent(whId)}&metric=${encodeURIComponent(metric)}`, {
        headers:{'Accept':'application/json'}
      });
      json = await res.json();
    }catch(e){
      metaLine.textContent = 'Error de red cargando heatmap';
      empty.style.display = 'flex';
      return;
    }

    if (!json || !json.ok) {
      metaLine.textContent = 'Error cargando heatmap';
      empty.style.display = 'flex';
      return;
    }

    const cells = Array.isArray(json.cells) ? json.cells : [];
    const max = Number(json.max || 0);
    const m = json.metric || metric;

    const metricLabel = (m === 'primary_stock') ? 'primary_stock' : 'inv_qty';
    metaLine.textContent = `Métrica: ${metricLabel} · Máximo: ${max} · Celdas: ${cells.length}`;

    if(!cells.length){
      empty.style.display = 'flex';
      return;
    }

    cells.forEach(c=>{
      const d = document.createElement('div');
      d.className = 'hm-cell';
      Object.assign(d.style, rectToPx(c));

      const v = Number(c.value||0);
      d.style.background = colorFor(v, max);

      d.innerHTML = `
        <div class="t">${escapeHtml(c.code || '')}</div>
        <div class="s">Valor: <b class="mono">${v}</b></div>
      `;

      // Tooltip hover + touch
      const payload = { code: c.code, value: v, metricLabel };

      d.addEventListener('mousemove', (e)=>{
        showTipAt(e.clientX, e.clientY, payload);
      });
      d.addEventListener('mouseenter', (e)=>{
        showTipAt(e.clientX, e.clientY, payload);
      });
      d.addEventListener('mouseleave', ()=>{
        hideTip();
      });

      // mobile: tap muestra tooltip y segundo tap abre
      let tapped = false;
      d.addEventListener('touchstart', (e)=>{
        const t = e.touches?.[0];
        if(!t) return;
        if(!tapped){
          e.preventDefault();
          tapped = true;
          showTipAt(t.clientX, t.clientY, payload);
          setTimeout(()=>{ tapped = false; }, 900);
        }
      }, {passive:false});

      d.addEventListener('click', ()=>{
        const url = `${@json(url('/admin/wms/locations'))}/${encodeURIComponent(c.id)}/page`;
        window.location.href = url;
      });

      canvas.appendChild(d);
    });
  }

  // esconder tooltip si scrollea
  document.getElementById('hmStage')?.addEventListener('scroll', hideTip, {passive:true});
  window.addEventListener('scroll', hideTip, {passive:true});
  window.addEventListener('resize', hideTip, {passive:true});

  btn?.addEventListener('click', load);
  metricSel?.addEventListener('change', load);

  load();
})();
</script>
@endpush
