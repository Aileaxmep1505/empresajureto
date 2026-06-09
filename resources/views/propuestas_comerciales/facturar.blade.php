@extends('layouts.app')
@section('content_class', 'content--flush')
@section('content')
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
<style>
  .fac-page { font-family:'Quicksand',sans-serif; background:#f8fafc; color:#334155; min-height:100vh; padding:32px 24px; }
  .fac-page * { box-sizing:border-box; }
  .fac-wrap { max-width:1180px; margin:0 auto; }
  .fac-page h1 { color:#0f172a; font-size:26px; margin:0 0 6px; font-weight:700; }
  .fac-sub { color:#64748b; font-size:14px; margin:0 0 18px; }
  .back-link { display:inline-flex; gap:8px; color:#64748b; font-weight:600; font-size:14px; margin-bottom:18px; text-decoration:none; }
  .test-banner { background:#fff7ed; color:#9a3412; border:1px solid #fed7aa; border-radius:10px; padding:11px 16px; margin-bottom:14px; font-size:13px; font-weight:600; }
  .resolve-status { font-size:12.5px; font-weight:600; color:#1d4ed8; margin-bottom:14px; min-height:18px; }
  .fac-toolbar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px; align-items:center; }
  .btn { font-family:'Quicksand',sans-serif; font-weight:700; height:42px; padding:0 16px; border-radius:9px; border:1px solid transparent; cursor:pointer; display:inline-flex; align-items:center; gap:8px; font-size:14px; text-decoration:none; }
  .btn-green { background:#16a34a; color:#fff; } .btn-green:hover { background:#15803d; }
  .btn-blue { background:#007aff; color:#fff; } .btn-blue:hover { background:#005bb5; }
  .btn-outline { background:#fff; color:#0f172a; border-color:#e2e8f0; } .btn-outline:hover { border-color:#94a3b8; }
  .btn-dark { background:#0f172a; color:#fff; } .btn-dark:hover { background:#1e293b; }
  .btn-rebuscar { width:36px; height:34px; padding:0; border-radius:7px; background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; cursor:pointer; }
  .btn-rebuscar:hover { background:#dbeafe; }
  .btn-find { flex:0 0 auto; width:32px; height:34px; border:1px solid #e2e8f0; background:#f8fafc; border-radius:7px; cursor:pointer; font-size:13px; }
  .btn-find:hover { background:#eef2f7; }
  .clave-cell { display:flex; gap:4px; align-items:center; }
  .fac-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:18px 20px; margin-bottom:18px; overflow-x:auto; }
  table { width:100%; border-collapse:collapse; min-width:1000px; }
  th { text-align:left; font-size:11px; color:#64748b; font-weight:700; padding:9px 8px; border-bottom:1px solid #e2e8f0; text-transform:uppercase; }
  td { font-size:13px; color:#0f172a; padding:9px 8px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
  .tr { text-align:right; } .tc { text-align:center; }
  .inp { font-family:'Quicksand',sans-serif; font-weight:600; font-size:13px; height:36px; padding:0 8px; border:1px solid #e2e8f0; border-radius:7px; width:100%; }
  .inp:focus { outline:none; border-color:#007aff; box-shadow:0 0 0 3px #eff6ff; }
  .w-qty { width:80px; } .w-price { width:100px; } .w-clave { width:110px; }
  .fac-totals { display:flex; justify-content:flex-end; gap:30px; font-size:14px; margin-top:12px; }
  .fac-totals strong { color:#0f172a; font-size:17px; }

  .modal-backdrop { position:fixed; inset:0; z-index:9999; display:none; align-items:center; justify-content:center; padding:20px; background:rgba(15,23,42,.45); backdrop-filter:blur(4px); }
  .modal-backdrop.show { display:flex; }
  .modal { width:min(760px,100%); max-height:calc(100vh - 40px); background:#fff; border-radius:14px; box-shadow:0 24px 80px rgba(15,23,42,.22); display:flex; flex-direction:column; overflow:hidden; }
  .modal-head { padding:18px 22px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; }
  .modal-head h2 { margin:0; font-size:18px; color:#0f172a; font-weight:700; }
  .modal-close { border:0; background:transparent; cursor:pointer; color:#64748b; font-size:22px; }
  .modal-body { padding:20px 22px; overflow-y:auto; }
  .modal-foot { padding:14px 22px; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px; }
  .res-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:10px; margin-bottom:16px; }
  .res-box { border:1px solid #e2e8f0; border-radius:10px; padding:12px; text-align:center; }
  .res-box .v { font-size:18px; font-weight:700; color:#0f172a; }
  .res-box .l { font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase; margin-top:2px; }
  pre.json { background:#0f172a; color:#e2e8f0; border-radius:10px; padding:14px; font-size:11.5px; line-height:1.5; overflow:auto; max-height:340px; white-space:pre; }
  .loader { display:inline-block; width:14px; height:14px; border:2px solid rgba(0,0,0,.25); border-radius:50%; border-top-color:#1d4ed8; animation:spin 1s linear infinite; }
  @keyframes spin { to { transform:rotate(360deg); } }

  /* Resultados búsqueda de clave */
  .cl-row { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:11px 12px; border:1px solid #e2e8f0; border-radius:9px; margin-bottom:8px; }
  .cl-row strong { color:#0f172a; font-size:14px; }
  .cl-desc { font-size:12.5px; color:#64748b; margin-top:2px; }
  .cl-empty { color:#94a3b8; font-size:14px; }
</style>

<div class="fac-page">
  <div class="fac-wrap">
    <a href="{{ route('propuestas-comerciales.resultado.show', $resultado) }}" class="back-link">← Volver al resultado</a>

    <h1>Facturación · {{ $folio }}</h1>
    <p class="fac-sub">Cliente: <strong>{{ $cliente }}</strong> · IVA {{ number_format($ivaPct,0) }}% · Solo partidas ganadas</p>

    <div class="test-banner">⚙ MODO PRUEBA — todavía NO se envía a Facturapi. Al facturar verás el JSON que se enviaría.</div>
    <div class="resolve-status" id="resolveStatus"></div>

    <div class="fac-toolbar">
      <button type="button" class="btn btn-green" onclick="facturar('completo')">🧾 Facturar completo (todas)</button>
      <button type="button" class="btn btn-blue" onclick="facturar('partes')">✂ Facturar por partes (seleccionadas)</button>
    </div>

    <div class="fac-card">
      @if(count($ganadas))
        <table>
          <thead>
            <tr>
              <th class="tc"><input type="checkbox" id="chkAll" onchange="toggleAll(this)" checked></th>
              <th>#</th>
              <th>Descripción</th>
              <th class="tc">Unidad</th>
              <th class="tr">Cantidad</th>
              <th class="tr">P. Unit.</th>
              <th class="tr">Importe</th>
              <th>Clave ProdServ</th>
              <th>Clave Unidad</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @foreach($ganadas as $g)
              <tr class="fac-row" data-desc="{{ $g['desc'] }}" data-unidad="{{ $g['unidad'] }}">
                <td class="tc"><input type="checkbox" class="f-check" checked></td>
                <td>{{ $g['num'] }}</td>
                <td style="min-width:260px;">{{ $g['desc'] }}</td>
                <td class="tc">{{ $g['unidad'] }}</td>
                <td class="tr"><input type="number" step="0.01" min="0" class="inp w-qty f-qty tr" value="{{ $g['cantidad'] }}" oninput="recalc()"></td>
                <td class="tr"><input type="number" step="0.01" min="0" class="inp w-price f-price tr" value="{{ $g['precio'] }}" oninput="recalc()"></td>
                <td class="tr"><strong class="f-importe">${{ number_format($g['importe'],2) }}</strong></td>
                <td>
                  <div class="clave-cell">
                    <input type="text" class="inp w-clave f-prodserv" value="{{ $g['clave_prodserv'] }}" placeholder="buscando…">
                    <button type="button" class="btn-find" title="Buscar ProdServ" onclick="openClaveModal(this,'prodserv')">🔍</button>
                  </div>
                </td>
                <td>
                  <div class="clave-cell">
                    <input type="text" class="inp w-clave f-claveunidad" value="{{ $g['clave_unidad'] }}">
                    <button type="button" class="btn-find" title="Buscar Unidad" onclick="openClaveModal(this,'unidad')">🔍</button>
                  </div>
                </td>
                <td><button type="button" class="btn-rebuscar" title="Re-buscar con IA" onclick="rebuscar(this)">🔄</button></td>
              </tr>
            @endforeach
          </tbody>
        </table>

        <div class="fac-totals">
          <span>Subtotal <strong id="tSub">$0.00</strong></span>
          <span>IVA <strong id="tIva">$0.00</strong></span>
          <span>Total <strong id="tTot">$0.00</strong></span>
        </div>
      @else
        <p style="color:#94a3b8;">No hay partidas ganadas para facturar.</p>
      @endif
    </div>
  </div>

  {{-- Modal resultado prueba --}}
  <div class="modal-backdrop" id="facModal">
    <div class="modal">
      <div class="modal-head">
        <h2 id="facModalTitle">Resultado (prueba)</h2>
        <button type="button" class="modal-close" onclick="closeFacModal()">✕</button>
      </div>
      <div class="modal-body" id="facModalBody"></div>
      <div class="modal-foot">
        <button type="button" class="btn btn-outline" onclick="closeFacModal()">Cerrar</button>
        <button type="button" class="btn btn-dark" onclick="downloadJson()">↓ Descargar JSON</button>
      </div>
    </div>
  </div>

  {{-- Modal búsqueda manual de clave --}}
  <div class="modal-backdrop" id="claveModal">
    <div class="modal">
      <div class="modal-head">
        <h2 id="claveModalTitle">Buscar clave</h2>
        <button type="button" class="modal-close" onclick="closeClaveModal()">✕</button>
      </div>
      <div class="modal-body">
        <input type="text" class="inp" id="claveSearchInput" placeholder="Escribe para buscar..." style="height:44px; margin-bottom:14px;" oninput="scheduleClaveSearch()">
        <div class="resolve-status" id="claveSearchStatus"></div>
        <div id="claveResults"></div>
      </div>
    </div>
  </div>
</div>

<script>
  const csrfToken = @json(csrf_token());
  const pruebaUrl = @json(route('propuestas-comerciales.resultado.facturar.prueba', $resultado));
  const rebuscarUrl = @json(route('propuestas-comerciales.resultado.facturar.rebuscar', $resultado));
  const resolverUrl = @json(route('propuestas-comerciales.resultado.facturar.resolver', $resultado));
  const buscarUrl = @json(route('propuestas-comerciales.resultado.facturar.buscar', $resultado));
  const ivaPct = @json($ivaPct);
  const folio = @json($folio);
  let lastPayload = null;

  // Estado del modal de búsqueda manual
  let claveTargetInput = null;
  let claveTipo = 'prodserv';
  let claveSearchTimer = null;

  const money = n => '$' + Number(n||0).toLocaleString('es-MX',{minimumFractionDigits:2, maximumFractionDigits:2});
  function escapeHtml(v){ return String(v ?? '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'","&#039;"); }

  function toggleAll(el) {
    document.querySelectorAll('.f-check').forEach(c => c.checked = el.checked);
  }

  function recalc() {
    let sub = 0;
    document.querySelectorAll('.fac-row').forEach(row => {
      const qty = Number(row.querySelector('.f-qty').value || 0);
      const price = Number(row.querySelector('.f-price').value || 0);
      const imp = qty * price;
      row.querySelector('.f-importe').textContent = money(imp);
      sub += imp;
    });
    const iva = sub * (Number(ivaPct) / 100);
    document.getElementById('tSub').textContent = money(sub);
    document.getElementById('tIva').textContent = money(iva);
    document.getElementById('tTot').textContent = money(sub + iva);
  }

  function collectRows(tipo) {
    const rows = [...document.querySelectorAll('.fac-row')].filter(row => {
      return tipo === 'completo' ? true : row.querySelector('.f-check').checked;
    });
    return rows.map(row => ({
      descripcion: row.dataset.desc,
      unidad: row.dataset.unidad,
      cantidad: row.querySelector('.f-qty').value,
      precio: row.querySelector('.f-price').value,
      clave_prodserv: row.querySelector('.f-prodserv').value || '01010101',
      clave_unidad: row.querySelector('.f-claveunidad').value || 'H87',
    }));
  }

  async function facturar(tipo) {
    const items = collectRows(tipo);
    if (!items.length) { alert('Selecciona al menos una partida.'); return; }

    try {
      const resp = await fetch(pruebaUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ tipo, items })
      });
      const data = await resp.json();
      if (!resp.ok || !data.ok) throw new Error(data.message || 'Error en la prueba.');

      lastPayload = data.facturapi_payload;
      const r = data.resumen;

      document.getElementById('facModalTitle').textContent =
        tipo === 'completo' ? 'Factura completa (prueba)' : 'Factura por partes (prueba)';

      document.getElementById('facModalBody').innerHTML = `
        <div class="res-grid">
          <div class="res-box"><div class="v">${r.partidas}</div><div class="l">Partidas</div></div>
          <div class="res-box"><div class="v">${money(r.subtotal)}</div><div class="l">Subtotal</div></div>
          <div class="res-box"><div class="v">${money(r.iva)}</div><div class="l">IVA ${Number(r.iva_pct).toFixed(0)}%</div></div>
          <div class="res-box"><div class="v">${money(r.total)}</div><div class="l">Total</div></div>
        </div>
        <p style="font-size:12.5px; color:#64748b; margin:0 0 8px;">Esto se enviaría a Facturapi (modo prueba):</p>
        <pre class="json">${JSON.stringify(data.facturapi_payload, null, 2)}</pre>
      `;
      document.getElementById('facModal').classList.add('show');
    } catch (e) {
      alert(e.message);
    }
  }

  async function rebuscar(btn) {
    const row = btn.closest('.fac-row');
    const old = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<span class="loader"></span>';
    try {
      const resp = await fetch(rebuscarUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ descripcion: row.dataset.desc, unidad: row.dataset.unidad })
      });
      const data = await resp.json();
      if (!resp.ok || !data.ok) throw new Error(data.message || 'Error al re-buscar.');
      row.querySelector('.f-prodserv').value = data.clave_prodserv;
      row.querySelector('.f-claveunidad').value = data.clave_unidad;
    } catch (e) {
      alert(e.message);
    } finally {
      btn.disabled = false; btn.innerHTML = old;
    }
  }

  /* ===== Búsqueda manual de clave (modal) ===== */
  function openClaveModal(btn, tipo) {
    const row = btn.closest('.fac-row');
    claveTipo = tipo;
    claveTargetInput = tipo === 'prodserv' ? row.querySelector('.f-prodserv') : row.querySelector('.f-claveunidad');

    document.getElementById('claveModalTitle').textContent =
      tipo === 'prodserv' ? 'Buscar Clave ProdServ (SAT)' : 'Buscar Clave de Unidad (SAT)';

    const input = document.getElementById('claveSearchInput');
    // Prefill: prodserv con la descripción; unidad con la unidad de la fila.
    input.value = tipo === 'prodserv' ? (row.dataset.desc || '') : (row.dataset.unidad || '');

    document.getElementById('claveResults').innerHTML = '';
    document.getElementById('claveSearchStatus').textContent = '';
    document.getElementById('claveModal').classList.add('show');

    scheduleClaveSearch(50);
    setTimeout(() => input.focus(), 100);
  }

  function closeClaveModal() {
    document.getElementById('claveModal').classList.remove('show');
    claveTargetInput = null;
  }

  function scheduleClaveSearch(delay = 350) {
    clearTimeout(claveSearchTimer);
    claveSearchTimer = setTimeout(runClaveSearch, delay);
  }

  async function runClaveSearch() {
    const q = document.getElementById('claveSearchInput').value.trim();
    const status = document.getElementById('claveSearchStatus');
    const box = document.getElementById('claveResults');

    if (!q) { box.innerHTML = ''; status.textContent = 'Escribe para buscar.'; return; }

    status.innerHTML = '<span class="loader"></span> Buscando...';
    try {
      const resp = await fetch(buscarUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ tipo: claveTipo, q })
      });
      const data = await resp.json();
      if (!resp.ok || !data.ok) throw new Error(data.message || 'Error en la búsqueda.');

      const results = data.results || [];
      status.textContent = `${results.length} resultado(s)`;

      if (!results.length) {
        box.innerHTML = '<p class="cl-empty">Sin resultados. Prueba con otra palabra.</p>';
        return;
      }

      box.innerHTML = results.map(r => `
        <div class="cl-row">
          <div style="min-width:0;">
            <strong>${escapeHtml(r.clave)}</strong>
            <div class="cl-desc">${escapeHtml(r.texto)}</div>
          </div>
          <button type="button" class="btn btn-blue" onclick="usarClave('${escapeHtml(r.clave)}')">Usar</button>
        </div>
      `).join('');
    } catch (e) {
      status.textContent = e.message;
    }
  }

  function usarClave(clave) {
    if (claveTargetInput) {
      claveTargetInput.value = clave;
    }
    closeClaveModal();
  }

  // Cerrar modales al hacer clic fuera
  document.getElementById('facModal').addEventListener('click', e => { if (e.target.id === 'facModal') closeFacModal(); });
  document.getElementById('claveModal').addEventListener('click', e => { if (e.target.id === 'claveModal') closeClaveModal(); });

  /* ===== Auto-resolver ClaveProdServ por lotes ===== */
  async function autoResolverClaves() {
    const rows = [...document.querySelectorAll('.fac-row')]
      .filter(r => !r.querySelector('.f-prodserv').value.trim());

    const status = document.getElementById('resolveStatus');
    if (!rows.length) { if (status) status.textContent = ''; return; }

    const total = rows.length;
    let done = 0;
    const size = 8;

    for (let i = 0; i < rows.length; i += size) {
      const chunk = rows.slice(i, i + size);
      const descripciones = chunk.map(r => r.dataset.desc);
      if (status) status.textContent = `🔎 Buscando claves SAT… ${done}/${total}`;

      try {
        const resp = await fetch(resolverUrl, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
          body: JSON.stringify({ descripciones })
        });
        const data = await resp.json();
        if (data.ok && Array.isArray(data.claves)) {
          chunk.forEach((r, k) => { r.querySelector('.f-prodserv').value = data.claves[k] ?? ''; });
        }
      } catch (e) { /* siguiente lote */ }

      done += chunk.length;
    }

    if (status) status.textContent = `✅ Claves SAT listas (${total}). Revísalas; usa 🔍 o 🔄 si alguna no es correcta.`;
  }

  function closeFacModal() {
    document.getElementById('facModal').classList.remove('show');
  }

  function downloadJson() {
    if (!lastPayload) return;
    const blob = new Blob([JSON.stringify(lastPayload, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = 'factura_prueba_' + folio + '.json';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  }

  recalc();
  document.addEventListener('DOMContentLoaded', autoResolverClaves);
</script>
@endsection