@extends('layouts.app')

@section('title', 'WMS · Historial (Kardex)')

@section('content')
@php
  $whId = (int)($warehouseId ?? 1);
@endphp

<div class="kx-wrap">
  <div class="kx-head">
    <div>
      <div class="kx-title">Historial (Kardex)</div>
      <div class="kx-sub">Entradas y salidas registradas. Filtra por bodega, tipo, producto o fecha.</div>
    </div>

    <div class="kx-actions">
      <a class="btn btn-ghost" href="{{ route('admin.wms.home') }}">← WMS</a>
      <a class="btn btn-ghost" href="{{ route('admin.wms.move.view', ['warehouse_id'=>$whId]) }}">↔ Entradas / Salidas</a>

      <form method="GET" action="{{ route('admin.wms.movements.view') }}" class="wh-form">
        <select name="warehouse_id" class="inp" onchange="this.form.submit()">
          @foreach(($warehouses ?? []) as $w)
            <option value="{{ $w->id }}" @selected((int)$w->id === $whId)>
              {{ $w->name ?? ('Bodega #'.$w->id) }}
            </option>
          @endforeach
        </select>
      </form>
    </div>
  </div>

  <div class="kx-card">
    <div class="kx-filters">
      <div class="f">
        <label class="lbl">Tipo</label>
        <select id="type" class="inp">
          <option value="">Todos</option>
          <option value="in">Entrada (sumar)</option>
          <option value="out">Salida (descontar)</option>
        </select>
      </div>

      <div class="f">
        <label class="lbl">Buscar</label>
        <input id="q" class="inp" placeholder="Nombre, SKU, GTIN o ID...">
      </div>

      <div class="f">
        <label class="lbl">Desde</label>
        <input id="from" type="date" class="inp">
      </div>

      <div class="f">
        <label class="lbl">Hasta</label>
        <input id="to" type="date" class="inp">
      </div>

      <div class="f fbtn">
        <button class="btn btn-primary" id="btnLoad" type="button">↻ Cargar</button>
      </div>
    </div>

    <div class="kx-meta">
      <div class="muted" id="metaLine">—</div>
      <div class="muted">Máx. 500 filas</div>
    </div>

    <div class="table-wrap">
      <table class="tbl" id="tbl">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Producto</th>
            <th>SKU</th>
            <th>GTIN</th>
            <th>Ubicación</th>
            <th class="t-right">Qty</th>
            <th class="t-right">Stock antes</th>
            <th class="t-right">Stock después</th>
            <th class="t-right">Inv antes</th>
            <th class="t-right">Inv después</th>
            <th>Nota</th>
          </tr>
        </thead>
        <tbody id="tbody">
          <tr><td colspan="12" class="empty">Cargando…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

@push('styles')
<style>
  :root{
    --ink:#0f172a; --muted:#64748b; --line:#e5e7eb; --bg:#f6f8fc;
    --card:#fff; --shadow:0 18px 55px rgba(2,6,23,.08);
    --brand:#2563eb; --r:18px;
  }

  .kx-wrap{max-width:1280px;margin:0 auto;padding:18px 14px 26px}
  .kx-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap}
  .kx-title{font-weight:950;color:var(--ink);font-size:1.15rem}
  .kx-sub{color:var(--muted);font-size:.88rem;margin-top:2px}
  .kx-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}

  .btn{
    border:0;border-radius:999px;padding:10px 14px;font-weight:900;font-size:.9rem;
    cursor:pointer;display:inline-flex;gap:8px;align-items:center;
    transition:transform .12s ease, box-shadow .12s ease, background .15s ease
  }
  .btn:hover{transform:translateY(-1px)}
  .btn-primary{background:var(--brand);color:#fff;box-shadow:0 14px 30px rgba(37,99,235,.25)}
  .btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink);box-shadow:0 10px 25px rgba(2,6,23,.04)}
  .inp{background:#f8fafc;border:1px solid var(--line);border-radius:12px;padding:10px 12px;min-height:42px;width:100%}

  .kx-card{margin-top:14px;background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow);overflow:hidden}
  .kx-filters{
    display:grid;grid-template-columns:180px 1fr 170px 170px 140px;
    gap:10px;padding:12px;border-bottom:1px solid #eef2f7;align-items:end
  }
  .f{min-width:0}
  .lbl{display:block;font-weight:950;color:var(--ink);font-size:.82rem;margin:0 0 6px}
  .fbtn{display:flex;justify-content:flex-end}

  .kx-meta{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;padding:10px 12px;border-bottom:1px solid #eef2f7}
  .muted{color:var(--muted);font-size:.85rem}

  .table-wrap{overflow:auto}
  .tbl{width:100%;border-collapse:collapse;font-size:.88rem}
  .tbl th,.tbl td{padding:10px 10px;border-bottom:1px solid #eef2f7;vertical-align:top;white-space:nowrap}
  .tbl th{color:#0f172a;font-weight:950;background:#f8fafc}
  .t-right{text-align:right}
  .empty{color:var(--muted);padding:16px 10px;text-align:center;white-space:normal}

  .pill{
    display:inline-flex;align-items:center;gap:6px;
    border-radius:999px;padding:4px 10px;font-weight:950;font-size:.78rem;border:1px solid;
  }
  .in{background:#dcfce7;color:#166534;border-color:#bbf7d0}
  .out{background:#fee2e2;color:#991b1b;border-color:#fecaca}

  @media (max-width: 1100px){
    .kx-filters{grid-template-columns:1fr; align-items:stretch}
    .fbtn{justify-content:stretch}
    .fbtn .btn{width:100%; justify-content:center}
  }
</style>
@endpush

@push('scripts')
<script>
(function(){
  const whId = @json((int)$whId);
  const dataUrl = @json(route('admin.wms.movements.data'));

  const typeEl = document.getElementById('type');
  const qEl = document.getElementById('q');
  const fromEl = document.getElementById('from');
  const toEl = document.getElementById('to');
  const btn = document.getElementById('btnLoad');
  const tbody = document.getElementById('tbody');
  const metaLine = document.getElementById('metaLine');

  function esc(s){
    return String(s ?? '')
      .replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
      .replace(/"/g,"&quot;").replace(/'/g,"&#039;");
  }

  function pill(type){
    if(type === 'in') return '<span class="pill in">Entrada</span>';
    if(type === 'out') return '<span class="pill out">Salida</span>';
    return esc(type);
  }

  async function load(){
    metaLine.textContent = 'Cargando...';
    tbody.innerHTML = `<tr><td colspan="12" class="empty">Cargando…</td></tr>`;

    const params = new URLSearchParams();
    params.set('warehouse_id', whId);
    if(typeEl.value) params.set('type', typeEl.value);
    if((qEl.value||'').trim()) params.set('q', (qEl.value||'').trim());
    if(fromEl.value) params.set('from', fromEl.value);
    if(toEl.value) params.set('to', toEl.value);

    let res, json;
    try{
      res = await fetch(`${dataUrl}?${params.toString()}`, { headers:{'Accept':'application/json'} });
      json = await res.json();
    }catch(e){
      metaLine.textContent = 'Error de red';
      tbody.innerHTML = `<tr><td colspan="12" class="empty">No se pudo cargar. Revisa consola / red.</td></tr>`;
      return;
    }

    if(!json || !json.ok){
      metaLine.textContent = 'Error cargando';
      tbody.innerHTML = `<tr><td colspan="12" class="empty">${esc(json?.error || 'No se pudo cargar')}</td></tr>`;
      return;
    }

    const rows = Array.isArray(json.rows) ? json.rows : [];
    metaLine.textContent = `Filas: ${rows.length}`;

    if(!rows.length){
      tbody.innerHTML = `<tr><td colspan="12" class="empty">Sin movimientos con esos filtros.</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(r => `
      <tr>
        <td>${esc(r.when)}</td>
        <td>${pill(r.type)}</td>
        <td style="white-space:normal;min-width:240px">
          <b>${esc(r.name)}</b>
          <div class="muted">ID ${esc(r.item_id)}</div>
        </td>
        <td>${esc(r.sku || '—')}</td>
        <td>${esc(r.gtin || '—')}</td>
        <td>${esc(r.location || '—')}</td>
        <td class="t-right"><b>${Number(r.qty||0)}</b></td>
        <td class="t-right">${Number(r.stock_before||0)}</td>
        <td class="t-right">${Number(r.stock_after||0)}</td>
        <td class="t-right">${Number(r.inv_before||0)}</td>
        <td class="t-right">${Number(r.inv_after||0)}</td>
        <td style="white-space:normal;min-width:220px">${esc(r.note || '—')}</td>
      </tr>
    `).join('');
  }

  btn?.addEventListener('click', load);
  [typeEl,qEl,fromEl,toEl].forEach(el=>{
    el?.addEventListener('keydown', (e)=>{ if(e.key === 'Enter'){ e.preventDefault(); load(); } });
  });

  load();
})();
</script>
@endpush
@endsection
