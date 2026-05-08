@extends('layouts.app')

@section('title', 'WMS · Recolecciones virtuales')

@section('content_class', 'content--flush')

@section('content')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  :root {
    --bg: #f9fafb;
    --card: #ffffff;
    --ink: #333333;
    --muted: #888888;
    --line: #ebebeb;
    --blue: #007aff;
    --blue-soft: #e6f0ff;
    --success: #15803d;
    --success-soft: #e6ffe6;
    --danger: #ff4a4a;
    --danger-soft: #ffebeb;
  }

  .vp-page { min-height: 100vh; background: var(--bg); color: var(--ink); font-family: 'Quicksand', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; padding: 28px; }
  .vp-shell { max-width: 1480px; margin: 0 auto; }
  .vp-header { display: flex; justify-content: space-between; gap: 20px; align-items: flex-start; margin-bottom: 22px; }
  .vp-eyebrow { display: inline-flex; width: fit-content; padding: 7px 12px; border-radius: 999px; background: var(--blue-soft); color: var(--blue); font-size: 12px; font-weight: 700; margin-bottom: 12px; }
  .vp-title { margin: 0; color: #111111; font-size: 30px; line-height: 1.1; letter-spacing: -0.03em; font-weight: 700; }
  .vp-subtitle { margin: 10px 0 0; color: var(--muted); font-size: 15px; line-height: 1.55; font-weight: 600; max-width: 760px; }
  .vp-card { background: var(--card); border: 1px solid var(--line); border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); transition: transform .18s ease, box-shadow .18s ease; }
  .vp-card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,0.04); }
  .vp-btn { border: 0; outline: 0; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 40px; padding: 10px 16px; border-radius: 999px; font-size: 13px; font-weight: 700; text-decoration: none; transition: transform .18s ease, box-shadow .18s ease, background .18s ease; white-space: nowrap; }
  .vp-btn:active { transform: scale(.98); }
  .vp-btn-primary { background: var(--blue); color: #fff; box-shadow: 0 4px 12px rgba(0,122,255,.14); }
  .vp-btn-primary:hover { color: #fff; transform: translateY(-1px); box-shadow: 0 8px 20px rgba(0,122,255,.16); }
  .vp-btn-outline { background: #fff; border: 1px solid var(--blue); color: var(--blue); }
  .vp-btn-outline:hover { background: var(--blue-soft); color: var(--blue); transform: translateY(-1px); }
  .vp-btn-ghost { background: transparent; color: #555; }
  .vp-btn-ghost:hover { background: #f9fafb; color: #111; transform: translateY(-1px); }
  .vp-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; justify-content: flex-end; }
  .vp-summary { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }
  .vp-stat { padding: 18px; }
  .vp-stat-label { color: var(--muted); font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 8px; }
  .vp-stat-value { color: #111; font-size: 26px; font-weight: 700; letter-spacing: -0.03em; line-height: 1; }
  .vp-filters { padding: 18px; margin-bottom: 18px; }
  .vp-filter-grid { display: grid; grid-template-columns: 1.4fr .8fr .8fr auto; gap: 12px; align-items: end; }
  .vp-field label { display:block; margin-bottom:7px; font-size:12px; font-weight:700; color:#555; }
  .vp-input, .vp-select { width:100%; min-height:42px; background:#fff; border:1px solid var(--line); border-radius:8px; padding:10px 12px; font-size:14px; font-weight:600; color:var(--ink); outline:none; transition:border-color .18s ease, box-shadow .18s ease; }
  .vp-input:focus, .vp-select:focus { border-color:var(--blue); box-shadow:0 0 0 3px var(--blue-soft); }
  .vp-table-card { overflow: hidden; }
  .vp-table-head { display:flex; justify-content:space-between; align-items:center; gap:14px; padding:18px 20px; border-bottom:1px solid var(--line); }
  .vp-table-title { margin:0; color:#111; font-size:18px; font-weight:700; }
  .vp-live { display:inline-flex; align-items:center; gap:8px; color:var(--muted); font-size:12px; font-weight:700; }
  .vp-live-dot { width:8px; height:8px; border-radius:999px; background:var(--success); box-shadow:0 0 0 4px var(--success-soft); }
  .vp-table-wrap { overflow-x:auto; }
  .vp-table { width:100%; min-width:1180px; border-collapse:separate; border-spacing:0; }
  .vp-table th { text-align:left; padding:13px 16px; color:var(--muted); background:#fff; border-bottom:1px solid var(--line); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
  .vp-table td { padding:16px; border-bottom:1px solid var(--line); vertical-align:top; font-size:14px; color:var(--ink); font-weight:600; }
  .vp-table tr:last-child td { border-bottom:0; }
  .vp-product-name, .vp-strong { color:#111; font-weight:700; }
  .vp-muted { color:var(--muted); font-size:12px; font-weight:600; line-height:1.45; }
  .vp-badge { display:inline-flex; align-items:center; width:fit-content; gap:6px; padding:7px 11px; border-radius:999px; font-size:12px; font-weight:700; line-height:1; white-space:nowrap; }
  .vp-badge-info { color:var(--blue); background:var(--blue-soft); }
  .vp-badge-success { color:var(--success); background:var(--success-soft); }
  .vp-badge-danger { color:var(--danger); background:var(--danger-soft); }
  .vp-empty { padding:46px 20px; text-align:center; color:var(--muted); font-weight:700; }
  .vp-empty-title { color:#111; font-size:18px; font-weight:700; margin-bottom:8px; }
  .vp-row-actions { display:flex; gap:8px; flex-wrap:wrap; min-width:190px; }

  .vp-pick-summary { display:grid; gap:8px; }
  .vp-pick-title { color:#111; font-size:15px; font-weight:700; }
  .vp-pick-meta { color:var(--muted); font-size:12px; font-weight:600; line-height:1.45; }
  .vp-product-stack { display:grid; gap:10px; min-width:420px; }
  .vp-product-mini { padding:12px 14px; border:1px solid var(--line); border-radius:12px; background:#fff; }
  .vp-product-mini-head { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; }
  .vp-product-mini-title { color:#111; font-weight:700; line-height:1.3; }
  .vp-product-mini-qty { white-space:nowrap; color:#111; font-weight:700; }
  .vp-product-mini-sub { margin-top:5px; color:var(--muted); font-size:12px; font-weight:600; line-height:1.45; }
  @media (max-width: 1100px) { .vp-page{padding:18px;} .vp-header{flex-direction:column;} .vp-summary{grid-template-columns:repeat(2,minmax(0,1fr));} .vp-filter-grid{grid-template-columns:1fr;} }
  @media (max-width: 640px) { .vp-summary{grid-template-columns:1fr;} .vp-title{font-size:25px;} }
</style>

<div class="vp-page">
  <div class="vp-shell">
    <div class="vp-header">
      <div>
        <div class="vp-eyebrow">Recolección externa</div>
        <h1 class="vp-title">Recolecciones virtuales</h1>
        <p class="vp-subtitle">Aquí viven solo los productos que no pasan por el scanner de almacén. El recolector confirma qué sí recogió, qué quedó parcial y qué no se encontró.</p>
      </div>
      <div class="vp-actions">
        <a href="{{ route('admin.wms.picking.v2') }}" class="vp-btn vp-btn-ghost">Picking</a>
        <button type="button" class="vp-btn vp-btn-primary" onclick="refreshVirtualPickups()">Actualizar</button>
      </div>
    </div>

    <div class="vp-summary" id="vpSummary">
      <div class="vp-card vp-stat"><div class="vp-stat-label">Total</div><div class="vp-stat-value" data-summary="total">{{ number_format($summary['total'] ?? 0) }}</div></div>
      <div class="vp-card vp-stat"><div class="vp-stat-label">Pendientes</div><div class="vp-stat-value" data-summary="pending">{{ number_format($summary['pending'] ?? 0) }}</div></div>
      <div class="vp-card vp-stat"><div class="vp-stat-label">Parciales</div><div class="vp-stat-value" data-summary="partial">{{ number_format($summary['partial'] ?? 0) }}</div></div>
      <div class="vp-card vp-stat"><div class="vp-stat-label">Recolectados</div><div class="vp-stat-value" data-summary="collected">{{ number_format($summary['collected'] ?? 0) }}</div></div>
      <div class="vp-card vp-stat"><div class="vp-stat-label">En staging</div><div class="vp-stat-value" data-summary="staged">{{ number_format($summary['staged'] ?? 0) }}</div></div>
    </div>

    <form method="GET" action="{{ route('admin.wms.virtual-pickups.index') }}" class="vp-card vp-filters">
      <div class="vp-filter-grid">
        <div class="vp-field">
          <label>Buscar</label>
          <input class="vp-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Producto, SKU, tarea, pedido, match...">
        </div>
        <div class="vp-field">
          <label>Estado</label>
          <select class="vp-select" name="status">
            <option value="">Todos</option>
            <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pendiente</option>
            <option value="partial" @selected(($filters['status'] ?? '') === 'partial')>Parcial</option>
            <option value="collected" @selected(($filters['status'] ?? '') === 'collected')>Recolectado</option>
            <option value="staged" @selected(($filters['status'] ?? '') === 'staged')>En staging</option>
            <option value="not_collected" @selected(($filters['status'] ?? '') === 'not_collected')>No recolectado</option>
          </select>
        </div>
        <div class="vp-field">
          <label>Almacén</label>
          <select class="vp-select" name="warehouse_id">
            <option value="0">Todos</option>
            @foreach($warehouses as $warehouse)
              <option value="{{ $warehouse->id }}" @selected((int)($filters['warehouse_id'] ?? 0) === (int)$warehouse->id)>{{ $warehouse->name ?: $warehouse->code }}</option>
            @endforeach
          </select>
        </div>
        <div class="vp-actions">
          <button class="vp-btn vp-btn-primary" type="submit">Filtrar</button>
          <a class="vp-btn vp-btn-ghost" href="{{ route('admin.wms.virtual-pickups.index') }}">Limpiar</a>
        </div>
      </div>
    </form>

    <div class="vp-card vp-table-card">
      <div class="vp-table-head">
        <h2 class="vp-table-title">Checklist virtual pendiente</h2>
        <div class="vp-live"><span class="vp-live-dot"></span>Tiempo real cada 8s</div>
      </div>
      <div class="vp-table-wrap">
        <table class="vp-table">
          <thead>
            <tr>
              <th>Estado</th>
              <th>Pick / Pedido</th>
              <th>Productos virtuales</th>
              <th>Origen</th>
              <th>Recolectado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="vpRows">
            @forelse($rows as $row)
              <tr>
                <td>
                  <span class="vp-badge vp-badge-{{ $row['status_class'] ?? 'info' }}">{{ $row['status_label'] ?? 'Pendiente' }}</span>
                  <div class="vp-muted" style="margin-top:8px;">{{ $row['warehouse_name'] ?? 'Sin almacén' }}</div>
                </td>
                <td>
                  <div class="vp-pick-summary">
                    <div class="vp-pick-title">{{ $row['task_number'] ?? 'Tarea' }}</div>
                    <div class="vp-pick-meta">
                      Pedido: {{ ($row['order_number'] ?? '') ?: '—' }}<br>
                      Creado: {{ ($row['created_at'] ?? '') ?: '—' }}<br>
                      {{ number_format((int)($row['items_count'] ?? count($row['items'] ?? []))) }} producto(s) virtual(es)
                    </div>
                    <span class="vp-badge vp-badge-info" style="margin-top:4px;">Pick agrupado</span>
                  </div>
                </td>
                <td>
                  <div class="vp-product-stack">
                    @foreach(($row['items'] ?? []) as $item)
                      <div class="vp-product-mini">
                        <div class="vp-product-mini-head">
                          <div class="vp-product-mini-title">{{ $item['product_name'] ?? 'Producto virtual' }}</div>
                          <div class="vp-product-mini-qty">{{ number_format((int)($item['quantity_collected'] ?? 0)) }}/{{ number_format((int)($item['qty'] ?? 0)) }}</div>
                        </div>
                        <div class="vp-product-mini-sub">
                          SKU: {{ ($item['sku'] ?? '') ?: '—' }} · Cant: {{ number_format((int)($item['qty'] ?? 0)) }} · Estado: {{ $item['status_label'] ?? 'Pendiente' }}<br>
                          Match: {{ ($item['fulfillment_group_id'] ?? '') ?: '—' }}
                        </div>
                      </div>
                    @endforeach
                  </div>
                </td>
                <td>
                  <div class="vp-strong">{{ ($row['origin'] ?? '') ?: 'Origen externo' }}</div>
                  <div class="vp-muted">{{ $row['notes'] ?? '' }}</div>
                </td>
                <td>
                  <div class="vp-strong">{{ number_format((int)($row['quantity_collected'] ?? 0)) }} / {{ number_format((int)($row['qty'] ?? 0)) }}</div>
                  <div class="vp-muted">Total del pick</div>
                </td>
                <td>
                  <div class="vp-row-actions">
                    <a href="{{ $row['show_url'] }}" class="vp-btn vp-btn-primary">Abrir checklist</a>
                    <a href="{{ $row['pdf_url'] }}" class="vp-btn vp-btn-outline" target="_blank">PDF</a>
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="6"><div class="vp-empty"><div class="vp-empty-title">No hay recolecciones virtuales</div>Cuando una tarea tenga excedentes virtuales aparecerán aquí.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  const vpDataUrl = @json(route('admin.wms.virtual-pickups.data'));
  const esc = value => String(value ?? '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;');
  const badgeClass = cls => cls === 'success' ? 'vp-badge-success' : (cls === 'danger' ? 'vp-badge-danger' : 'vp-badge-info');

  function renderEmpty() {
    return `<tr><td colspan="6"><div class="vp-empty"><div class="vp-empty-title">No hay recolecciones virtuales</div>Cuando una tarea tenga excedentes virtuales aparecerán aquí.</div></td></tr>`;
  }

  function renderRow(row) {
    const items = Array.isArray(row.items) ? row.items : [];
    const itemsHtml = items.map(item => `
      <div class="vp-product-mini">
        <div class="vp-product-mini-head">
          <div class="vp-product-mini-title">${esc(item.product_name || 'Producto virtual')}</div>
          <div class="vp-product-mini-qty">${Number(item.quantity_collected || 0).toLocaleString('es-MX')}/${Number(item.qty || 0).toLocaleString('es-MX')}</div>
        </div>
        <div class="vp-product-mini-sub">
          SKU: ${esc(item.sku || '—')} · Cant: ${Number(item.qty || 0).toLocaleString('es-MX')} · Estado: ${esc(item.status_label || 'Pendiente')}<br>
          Match: ${esc(item.fulfillment_group_id || '—')}
        </div>
      </div>
    `).join('');

    return `
      <tr>
        <td><span class="vp-badge ${badgeClass(row.status_class)}">${esc(row.status_label)}</span><div class="vp-muted" style="margin-top:8px;">${esc(row.warehouse_name || 'Sin almacén')}</div></td>
        <td>
          <div class="vp-pick-summary">
            <div class="vp-pick-title">${esc(row.task_number || 'Tarea')}</div>
            <div class="vp-pick-meta">Pedido: ${esc(row.order_number || '—')}<br>Creado: ${esc(row.created_at || '—')}<br>${Number(row.items_count || items.length || 0).toLocaleString('es-MX')} producto(s) virtual(es)</div>
            <span class="vp-badge vp-badge-info" style="margin-top:4px;">Pick agrupado</span>
          </div>
        </td>
        <td><div class="vp-product-stack">${itemsHtml}</div></td>
        <td><div class="vp-strong">${esc(row.origin || 'Origen externo')}</div><div class="vp-muted">${esc(row.notes || '')}</div></td>
        <td><div class="vp-strong">${Number(row.quantity_collected || 0).toLocaleString('es-MX')} / ${Number(row.qty || 0).toLocaleString('es-MX')}</div><div class="vp-muted">Total del pick</div></td>
        <td><div class="vp-row-actions"><a href="${esc(row.show_url)}" class="vp-btn vp-btn-primary">Abrir checklist</a><a href="${esc(row.pdf_url)}" class="vp-btn vp-btn-outline" target="_blank">PDF</a></div></td>
      </tr>`;
  }

  function updateSummary(summary) {
    Object.entries(summary || {}).forEach(([key, value]) => {
      const el = document.querySelector(`[data-summary="${key}"]`);
      if (el) el.textContent = Number(value || 0).toLocaleString('es-MX');
    });
  }

  async function refreshVirtualPickups() {
    const url = new URL(vpDataUrl, window.location.origin);
    const current = new URL(window.location.href);
    ['q','status','warehouse_id'].forEach(key => {
      const value = current.searchParams.get(key);
      if (value !== null) url.searchParams.set(key, value);
    });
    const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
    const json = await res.json();
    if (!res.ok || !json.ok) return;
    updateSummary(json.summary);
    const rows = Array.isArray(json.rows) ? json.rows : [];
    document.getElementById('vpRows').innerHTML = rows.length ? rows.map(renderRow).join('') : renderEmpty();
  }

  setInterval(refreshVirtualPickups, 8000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshVirtualPickups(); });
</script>
@endsection
