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
      <div class="hm-sub">Visualiza zonas calientes por stock. Cambia métrica entre <b>primary_stock</b> y <b>inv_qty</b>.</div>
    </div>

    <div class="hm-actions">
      <a class="btn btn-ghost" href="{{ route('admin.wms.home') }}">← WMS</a>
      <a class="btn btn-ghost" href="{{ route('admin.wms.layout.editor', ['warehouse_id'=>$whId]) }}">🧩 Layout</a>

      <form method="GET" action="{{ route('admin.wms.heatmap.view') }}" class="wh-form">
        <select name="warehouse_id" class="inp" onchange="this.form.submit()">
          @foreach(($warehouses ?? []) as $w)
            <option value="{{ $w->id }}" @selected((int)$w->id === $whId)>{{ $w->name ?? ('Bodega #'.$w->id) }}</option>
          @endforeach
        </select>
      </form>

      <select id="metric" class="inp">
        <option value="primary_stock">primary_stock (CatalogItem.stock)</option>
        <option value="inv_qty">inv_qty (Inventory.qty)</option>
      </select>

      <button class="btn btn-primary" id="btnReload">↻ Actualizar</button>
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

    <div class="hm-canvas" id="hmCanvas"></div>
  </div>
</div>

@push('styles')
<style>
  :root{
    --ink:#0f172a; --muted:#64748b; --line:#e5e7eb; --bg:#f6f8fc;
    --card:#fff; --shadow:0 18px 55px rgba(2,6,23,.08);
    --brand:#2563eb; --r:18px;
  }
  .hm-wrap{max-width:1280px;margin:0 auto;padding:18px 14px 26px}
  .hm-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap}
  .hm-title{font-weight:950;color:var(--ink);font-size:1.15rem}
  .hm-sub{color:var(--muted);font-size:.88rem;margin-top:2px}
  .hm-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}

  .btn{border:0;border-radius:999px;padding:10px 14px;font-weight:800;font-size:.9rem;cursor:pointer;display:inline-flex;gap:8px;align-items:center;transition:transform .12s ease, box-shadow .12s ease, background .15s ease}
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

  .hm-canvas{position:relative;height:70vh;min-height:560px;background:linear-gradient(180deg,#fbfdff,#f6f8fc)}
  .hm-cell{
    position:absolute;border-radius:14px;border:1px solid rgba(148,163,184,.45);
    box-shadow:0 10px 22px rgba(2,6,23,.05);
    padding:8px 9px;overflow:hidden;cursor:pointer;
  }
  .hm-cell .t{font-weight:950;color:#0f172a;font-size:.85rem}
  .hm-cell .s{font-size:.75rem;color:#475569;margin-top:2px}
</style>
@endpush

@push('scripts')
<script>
(function(){
  const CSRF = @json(csrf_token());
  const whId = @json((int)$whId);
  const dataUrl = @json(route('admin.wms.heatmap.data'));

  const canvas = document.getElementById('hmCanvas');
  const metricSel = document.getElementById('metric');
  const metaLine = document.getElementById('metaLine');

  function colorFor(v, max){
    if (!max || max <= 0) return 'rgba(255,255,255,.85)';
    const r = v / max;
    if (r <= 0) return 'rgba(255,255,255,.85)';
    if (r < 0.25) return 'rgba(34,197,94,.18)';
    if (r < 0.55) return 'rgba(234,179,8,.22)';
    if (r < 0.80) return 'rgba(249,115,22,.25)';
    return 'rgba(239,68,68,.26)';
  }

  function rectToPx(c){
    const U = 22;
    return {
      left: (c.x * U) + 'px',
      top: (c.y * U) + 'px',
      width: (Math.max(1,c.w) * U) + 'px',
      height: (Math.max(1,c.h) * U) + 'px',
    };
  }

  async function load(){
    const metric = metricSel.value;
    metaLine.textContent = 'Cargando...';

    const res = await fetch(`${dataUrl}?warehouse_id=${encodeURIComponent(whId)}&metric=${encodeURIComponent(metric)}`, {
      headers:{'Accept':'application/json'}
    });
    const json = await res.json();
    if (!json.ok) {
      metaLine.textContent = 'Error cargando heatmap';
      return;
    }

    const cells = json.cells || [];
    const max = Number(json.max || 0);

    metaLine.textContent = `Métrica: ${json.metric} · Máximo: ${max} · Celdas: ${cells.length}`;

    canvas.innerHTML = '';
    cells.forEach(c=>{
      const d = document.createElement('div');
      d.className = 'hm-cell';
      Object.assign(d.style, rectToPx(c));
      d.style.background = colorFor(Number(c.value||0), max);

      d.innerHTML = `
        <div class="t">${escapeHtml(c.code || '')}</div>
        <div class="s">Valor: <b>${Number(c.value||0)}</b></div>
      `;

      d.addEventListener('click', ()=>{
        const url = `${@json(url('/admin/wms/locations'))}/${c.id}/page`;
        window.location.href = url;
      });

      canvas.appendChild(d);
    });
  }

  function escapeHtml(str){
    return String(str ?? '')
      .replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
      .replace(/"/g,"&quot;").replace(/'/g,"&#039;");
  }

  document.getElementById('btnReload').addEventListener('click', load);
  metricSel.addEventListener('change', load);

  load();
})();
</script>
@endpush
@endsection
