@extends('layouts.app')

@section('title','WMS · Entradas/Salidas')

@section('content')
@php
  $whId = (int)($warehouseId ?? 1);
@endphp

<div class="mv-wrap">
  <div class="mv-head">
    <div>
      <div class="mv-tt">Entradas / Salidas</div>
      <div class="mv-sub">
        Selecciona varios productos, pon cantidades grandes y confirma. Se actualiza <b>catalog_items.stock</b> y <b>inventory.qty</b>.
      </div>
    </div>

    <div class="mv-actions">
      <a class="btn btn-ghost" href="{{ route('admin.wms.home') }}">← WMS</a>
      <a class="btn btn-ghost" href="{{ route('admin.wms.movements.view', ['warehouse_id'=>$whId]) }}">📜 Historial</a>

      <form method="GET" action="{{ route('admin.wms.move.view') }}">
        <select name="warehouse_id" class="inp" onchange="this.form.submit()">
          @foreach(($warehouses ?? []) as $w)
            <option value="{{ $w->id }}" @selected((int)$w->id === $whId)>{{ $w->name ?? ('Bodega #'.$w->id) }}</option>
          @endforeach
        </select>
      </form>

      <select id="mvType" class="inp">
        <option value="out">Salida (descontar)</option>
        <option value="in">Entrada (sumar)</option>
      </select>

      <input id="note" class="inp" placeholder="Nota (opcional) ej: Pedido #1234" style="min-width:260px">

      <button class="btn btn-primary" id="btnCommit" type="button">Confirmar</button>
    </div>
  </div>

  <div class="mv-grid">
    <div class="card">
      <div class="card-h">
        <div>
          <div class="card-tt">Buscar productos</div>
          <div class="card-sub">Por nombre, SKU, GTIN o ID</div>
        </div>
      </div>

      <div class="pad">
        <div class="row">
          <input id="q" class="inp" placeholder="Ej: lapicero / 123 / SKU-001 / 750...">
          <button class="btn btn-ghost" id="btnSearch" type="button">Buscar</button>
        </div>
        <div class="hint">Agrega productos al carrito.</div>
      </div>

      <div class="list" id="results"></div>
    </div>

    <div class="card">
      <div class="card-h">
        <div>
          <div class="card-tt">Carrito</div>
          <div class="card-sub">Cantidades grandes por producto</div>
        </div>
        <span class="chip" id="chipCount">0</span>
      </div>

      <div class="pad">
        <div class="row">
          <button class="btn btn-ghost" id="btnSet10" type="button">Poner 10 a todos</button>
          <button class="btn btn-ghost" id="btnSet50" type="button">Poner 50 a todos</button>
          <button class="btn btn-ghost" id="btnClear" type="button">Vaciar</button>
        </div>
        <div class="hint" id="msg"></div>
      </div>

      <div class="table-wrap">
        <table class="tbl" id="tbl">
          <thead>
            <tr>
              <th>Producto</th>
              <th class="t-right">Stock</th>
              <th class="t-right">Qty</th>
              <th>Ubicación sugerida</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="cartBody">
            <tr><td colspan="5" class="empty">Agrega productos desde la búsqueda.</td></tr>
          </tbody>
        </table>
      </div>

      <div class="route" id="routeBox" style="display:none;">
        <div class="route-h">📍 Ruta sugerida</div>
        <div class="route-sub">Ve por ubicaciones en este orden (click para abrir la ubicación).</div>
        <div id="routeList"></div>

        {{-- ✅ Botón para abrir el PDF (cuando el backend regrese pdf_url) --}}
        <div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap">
          <a class="btn btn-ghost" id="btnOpenPdf" href="#" target="_blank" style="display:none;">📄 Abrir PDF</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  :root{--ink:#0b1220;--muted:#64748b;--line:#e6eaf2;--line2:#eef2f7;--brand:#2563eb}
  .mv-wrap{max-width:1280px;margin:0 auto;padding:18px 14px 26px}
  .mv-head{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start}
  .mv-tt{font-weight:950;color:var(--ink);font-size:1.1rem}
  .mv-sub{color:var(--muted);font-size:.9rem;margin-top:2px}
  .mv-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}

  .btn{border:0;border-radius:999px;padding:10px 14px;font-weight:900;cursor:pointer;display:inline-flex;gap:8px;align-items:center;transition:transform .12s ease, box-shadow .12s ease}
  .btn:hover{transform:translateY(-1px)}
  .btn-primary{background:var(--brand);color:#fff;box-shadow:0 14px 30px rgba(37,99,235,.25)}
  .btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink);box-shadow:0 10px 25px rgba(2,6,23,.04)}
  .inp{background:#f8fafc;border:1px solid var(--line);border-radius:12px;padding:10px 12px;min-height:42px}

  .mv-grid{margin-top:14px;display:grid;grid-template-columns:1fr 1.2fr;gap:12px}
  .card{background:#fff;border:1px solid var(--line);border-radius:18px;box-shadow:0 10px 22px rgba(2,6,23,.05);overflow:hidden}
  .card-h{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;padding:12px 14px;border-bottom:1px solid var(--line)}
  .card-tt{font-weight:950;color:var(--ink)}
  .card-sub{color:var(--muted);font-size:.85rem;margin-top:2px}
  .pad{padding:12px 14px}
  .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .row .inp{flex:1 1 260px}
  .hint{color:var(--muted);font-size:.82rem;margin-top:8px}
  .chip{font-size:.78rem;font-weight:950;padding:6px 10px;border-radius:999px;background:#eff6ff;color:#1e40af;border:1px solid #dbeafe}

  .list{padding:8px 10px 12px;display:flex;flex-direction:column;gap:8px;max-height:58vh;overflow:auto}
  .it{border:1px solid var(--line);border-radius:14px;padding:10px 12px;display:flex;justify-content:space-between;gap:10px;align-items:flex-start}
  .it .n{font-weight:950;color:var(--ink)}
  .it .m{color:var(--muted);font-size:.82rem;margin-top:2px}
  .it .r{display:flex;flex-direction:column;gap:8px;align-items:flex-end}
  .mini{font-size:.78rem;color:#475569;background:#f1f5f9;border:1px solid #e2e8f0;padding:4px 8px;border-radius:999px;white-space:nowrap}
  .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}

  .table-wrap{padding:0 14px 12px;overflow:auto;max-height:45vh}
  .tbl{width:100%;border-collapse:collapse;font-size:.9rem}
  .tbl th,.tbl td{padding:10px 10px;border-bottom:1px solid var(--line2);vertical-align:top}
  .tbl th{font-weight:950;background:#f8fafc;white-space:nowrap}
  .t-right{text-align:right}
  .empty{color:var(--muted);padding:16px 10px;text-align:center}
  .qty{width:110px;text-align:right}

  .route{border-top:1px solid var(--line);padding:12px 14px}
  .route-h{font-weight:950;color:var(--ink)}
  .route-sub{color:var(--muted);font-size:.85rem;margin-top:2px}
  .route-card{margin-top:10px;border:1px solid var(--line);border-radius:14px;padding:10px 12px}
  .route-code{font-weight:950;color:var(--ink);display:flex;justify-content:space-between;gap:10px;align-items:center}
  .route-lines{margin-top:8px;color:#334155;font-size:.9rem}
  .route-lines div{display:flex;justify-content:space-between;gap:10px;padding:4px 0;border-bottom:1px dashed #eef2f7}
  .route-lines div:last-child{border-bottom:0}
  .link{color:#2563eb;text-decoration:underline;cursor:pointer}

  @media (max-width: 980px){ .mv-grid{grid-template-columns:1fr} }
</style>
@endpush

@push('scripts')
<script>
(function(){
  const API_PRODUCTS = @json(route('admin.wms.move.products'));
  const API_COMMIT   = @json(route('admin.wms.move.commit'));
  const CSRF = @json(csrf_token());
  const WAREHOUSE_ID = @json((int)$whId);

  const q = document.getElementById('q');
  const results = document.getElementById('results');
  const cartBody = document.getElementById('cartBody');
  const chipCount = document.getElementById('chipCount');
  const msg = document.getElementById('msg');
  const typeSel = document.getElementById('mvType');
  const noteInp = document.getElementById('note');

  const routeBox = document.getElementById('routeBox');
  const routeList = document.getElementById('routeList');
  const btnOpenPdf = document.getElementById('btnOpenPdf');

  const CART = {};

  function setMsg(t, ok=true){
    msg.textContent = t || '';
    msg.style.color = ok ? '#64748b' : '#b91c1c';
  }
  function escapeHtml(s){
    return String(s ?? '')
      .replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
      .replace(/"/g,"&quot;").replace(/'/g,"&#039;");
  }

  function renderCart(){
    const ids = Object.keys(CART);
    chipCount.textContent = ids.length;

    if(!ids.length){
      cartBody.innerHTML = `<tr><td colspan="5" class="empty">Agrega productos desde la búsqueda.</td></tr>`;
      return;
    }

    cartBody.innerHTML = ids.map(id=>{
      const it = CART[id];
      const rec = it.recommended?.code ? it.recommended.code : '—';
      const why = it.recommended?.why ? it.recommended.why : '';
      const badge = rec !== '—' ? `<span class="mini mono">${escapeHtml(rec)}</span>` : `<span class="mini">sin sugerencia</span>`;
      const small = (why === 'primary') ? 'primaria' : (why === 'most_qty' ? 'más qty' : '');

      return `
        <tr>
          <td>
            <div style="font-weight:950;color:#0b1220">${escapeHtml(it.name)}</div>
            <div style="color:#64748b;font-size:.82rem" class="mono">
              ID ${it.id} · SKU ${escapeHtml(it.sku || '—')} · GTIN ${escapeHtml(it.gtin || '—')}
            </div>
          </td>
          <td class="t-right mono"><b>${Number(it.stock||0)}</b></td>
          <td class="t-right">
            <input class="inp qty mono" type="number" min="1" value="${Number(it.qty||1)}" data-qty="${it.id}">
          </td>
          <td>
            ${badge}
            ${small ? `<div style="margin-top:6px;color:#64748b;font-size:.8rem">${small}</div>` : ''}
          </td>
          <td class="t-right">
            <button class="btn btn-ghost" style="padding:6px 10px;font-size:.78rem" data-del="${it.id}">Quitar</button>
          </td>
        </tr>
      `;
    }).join('');

    cartBody.querySelectorAll('[data-qty]').forEach(inp=>{
      inp.addEventListener('input', ()=>{
        const id = inp.getAttribute('data-qty');
        const v = parseInt(inp.value || '1', 10);
        CART[id].qty = isNaN(v) || v < 1 ? 1 : v;
      });
    });

    cartBody.querySelectorAll('[data-del]').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const id = btn.getAttribute('data-del');
        delete CART[id];
        renderCart();
      });
    });
  }

  async function search(){
    const term = (q.value || '').trim();
    setMsg('');
    routeBox.style.display = 'none';
    btnOpenPdf.style.display = 'none';
    results.innerHTML = '';

    if(!term){
      results.innerHTML = `<div class="empty">Escribe algo para buscar.</div>`;
      return;
    }

    results.innerHTML = `<div class="empty">Buscando…</div>`;
    const url = `${API_PRODUCTS}?warehouse_id=${encodeURIComponent(WAREHOUSE_ID)}&q=${encodeURIComponent(term)}`;
    const res = await fetch(url, {headers:{'Accept':'application/json'}});
    const data = await res.json().catch(()=> ({}));

    if(!data.ok){
      results.innerHTML = `<div class="empty">Error buscando.</div>`;
      return;
    }

    const items = Array.isArray(data.items) ? data.items : [];
    if(!items.length){
      results.innerHTML = `<div class="empty">Sin resultados.</div>`;
      return;
    }

    results.innerHTML = items.map(it=>{
      const rec = it.recommended?.code ? it.recommended.code : '—';
      const payload = JSON.stringify(it)
        .replace(/</g,'\\u003c').replace(/>/g,'\\u003e')
        .replace(/&/g,'\\u0026').replace(/'/g,"\\u0027");
      return `
        <div class="it">
          <div>
            <div class="n">${escapeHtml(it.name)}</div>
            <div class="m mono">ID ${it.id} · SKU ${escapeHtml(it.sku || '—')} · GTIN ${escapeHtml(it.gtin || '—')}</div>
            <div class="m">Stock global: <b class="mono">${Number(it.stock||0)}</b> · Ubicación sugerida: <span class="mono"><b>${escapeHtml(rec)}</b></span></div>
          </div>
          <div class="r">
            <button class="btn btn-primary" style="padding:8px 12px;font-size:.82rem" data-add='${payload}'>Agregar</button>
          </div>
        </div>
      `;
    }).join('');

    results.querySelectorAll('[data-add]').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const it = JSON.parse(btn.getAttribute('data-add'));
        if(!CART[it.id]) CART[it.id] = { ...it, qty: 1 };
        else CART[it.id].qty = Number(CART[it.id].qty||1) + 1;

        renderCart();
        setMsg(`Agregado: ${it.name}`);
      });
    });
  }

  async function postJson(url, body){
    const res = await fetch(url, {
      method:'POST',
      headers:{
        'Accept':'application/json',
        'Content-Type':'application/json',
        'X-CSRF-TOKEN': CSRF,
      },
      body: JSON.stringify(body || {})
    });
    const data = await res.json().catch(()=> ({}));
    if(!res.ok) data._http_error = true;
    return data;
  }

  function renderRoute(route){
    if(!Array.isArray(route) || !route.length){
      routeBox.style.display = 'none';
      return;
    }
    routeBox.style.display = 'block';
    routeList.innerHTML = route.map(step=>{
      const lines = (step.lines || []).map(l => `
        <div>
          <span>${escapeHtml(l.name)} <span class="mono" style="color:#64748b">(${escapeHtml(l.sku || '—')})</span></span>
          <b class="mono">${Number(l.qty||0)}</b>
        </div>
      `).join('');

      const openUrl = `${@json(url('/admin/wms/locations'))}/${encodeURIComponent(step.location_id)}/page`;

      return `
        <div class="route-card">
          <div class="route-code">
            <span>Ve a: <span class="mono"><b>${escapeHtml(step.code)}</b></span></span>
            <span class="link" data-open="${escapeHtml(openUrl)}">Abrir</span>
          </div>
          <div class="route-lines">${lines}</div>
        </div>
      `;
    }).join('');

    routeList.querySelectorAll('[data-open]').forEach(a=>{
      a.addEventListener('click', ()=> window.location.href = a.getAttribute('data-open'));
    });
  }

  async function commit(){
    const ids = Object.keys(CART);
    if(!ids.length){
      setMsg('Agrega productos primero.', false);
      return;
    }

    const type = typeSel.value || 'out';
    const note = (noteInp.value || '').trim();

    const lines = ids.map(id => ({
      catalog_item_id: Number(CART[id].id),
      qty: Number(CART[id].qty || 1),
      location_id: CART[id].recommended?.location_id || null,
    }));

    setMsg('Guardando movimiento…');
    routeBox.style.display = 'none';
    btnOpenPdf.style.display = 'none';

    const data = await postJson(API_COMMIT, {
      warehouse_id: WAREHOUSE_ID,
      type: type,
      note: note,
      lines: lines,
      // ✅ SIN datos de firmas: el PDF llevará espacios en blanco
    });

    if(!data.ok){
      setMsg(data.error || 'No se pudo guardar.', false);
      return;
    }

    setMsg('Listo ✅ Movimiento aplicado. Sigue la ruta sugerida.');
    renderRoute(data.route || []);

    // ✅ Abrir/mostrar PDF si backend lo regresa
    if (data.pdf_url) {
      btnOpenPdf.href = data.pdf_url;
      btnOpenPdf.style.display = 'inline-flex';
      window.open(data.pdf_url, '_blank');
    }
  }

  document.getElementById('btnSearch')?.addEventListener('click', search);
  q?.addEventListener('keydown', (e)=>{ if(e.key === 'Enter'){ e.preventDefault(); search(); } });

  document.getElementById('btnSet10')?.addEventListener('click', ()=>{
    Object.keys(CART).forEach(id => CART[id].qty = 10);
    renderCart();
  });
  document.getElementById('btnSet50')?.addEventListener('click', ()=>{
    Object.keys(CART).forEach(id => CART[id].qty = 50);
    renderCart();
  });
  document.getElementById('btnClear')?.addEventListener('click', ()=>{
    Object.keys(CART).forEach(id => delete CART[id]);
    renderCart();
    setMsg('');
    routeBox.style.display = 'none';
    btnOpenPdf.style.display = 'none';
  });

  document.getElementById('btnCommit')?.addEventListener('click', commit);

  renderCart();
})();
</script>
@endpush
