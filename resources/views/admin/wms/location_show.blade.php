@extends('layouts.app')

@section('title', 'WMS · Ubicación ' . $location->code)

@section('content')
<div class="wrap fade-in-up delay-1">
  <div class="top">
    <div class="top-left">
      <a href="{{ route('admin.wms.home') }}" class="btn btn-ghost btn-icon" title="Volver al WMS">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      </a>
      <div class="mid">
        <div class="tt">Ubicación <span class="badge-mono">{{ $location->code }}</span></div>
        <div class="sub">
          {{ $location->name ?? 'Sin nombre asignado' }} <span class="dot">•</span> Gestión de inventario
        </div>
      </div>
    </div>

    <div class="actions">
      <button class="btn btn-primary shadow-hover" id="btnHere" type="button">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        Fijar Ubicación
      </button>
      <a class="btn btn-ghost" href="{{ route('admin.wms.search.view', ['from' => $location->code]) }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        Buscar aquí
      </a>
      <a class="btn btn-ghost" href="{{ route('admin.wms.qr.print.one', ['location' => $location->id]) }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Imprimir QR
      </a>
    </div>
  </div>

  <div class="cards">
    {{-- Card: Resumen --}}
    <div class="card fade-in-up delay-2">
      <div class="card-h">
        <div>
          <div class="card-tt">Detalles de la Ubicación</div>
          <div class="card-sub">Información técnica y estructural</div>
        </div>
        <span class="chip chip-soft">{{ $rows->count() }} SKUs únicos</span>
      </div>

      <div class="kv">
        <div class="k">
          <div class="k-l">Código</div>
          <div class="k-v mono">{{ $location->code }}</div>
        </div>
        <div class="k">
          <div class="k-l">Tipo</div>
          <div class="k-v">{{ $location->type }}</div>
        </div>
        <div class="k">
          <div class="k-l">Pasillo / Sección</div>
          <div class="k-v">
            {{ $location->aisle ?? '—' }} / {{ $location->section ?? '—' }}
          </div>
        </div>
      </div>

      <div class="card-footer">
        <div class="mini-actions">
          <button class="btn btn-outline" id="btnAdjustOpen" type="button">Ajustar stock</button>
          <button class="btn btn-outline" id="btnTransferOpen" type="button">Transferir</button>
        </div>
        <div class="hint">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
          Imprime el QR y físcalo en el bin físico para escaneos rápidos.
        </div>
      </div>
    </div>

    {{-- Card: Inventario --}}
    <div class="card fade-in-up delay-3">
      <div class="card-h">
        <div>
          <div class="card-tt">Inventario Físico</div>
          <div class="card-sub">Gestión de existencias en esta ubicación</div>
        </div>
        <span class="chip chip-success" id="chipCount">{{ $rows->count() }} filas</span>
      </div>

      <div class="bar">
        <div class="input-icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <input class="inp" id="filter" placeholder="Filtrar por nombre, SKU o GTIN...">
        </div>
      </div>

      <div class="table-wrap">
        <table class="tbl" id="tbl">
          <thead>
            <tr>
              <th>Producto</th>
              <th>SKU</th>
              <th>GTIN</th>
              <th class="t-right">Unidades</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($rows as $r)
              @php $it = $r->item; @endphp
              <tr data-row="1" class="tbl-row-anim">
                <td>
                  <div class="pname">{{ $it->name }}</div>
                  <div class="pmeta">$ {{ number_format((float)$it->price, 2) }}</div>
                </td>
                <td><span class="mono-badge">{{ $it->sku ?? '—' }}</span></td>
                <td><span class="mono-badge">{{ $it->meli_gtin ?? '—' }}</span></td>
                <td class="t-right"><span class="qty-badge">{{ (int)$r->qty }}</span></td>
                <td class="t-right">
                  <button class="btn btn-ghost btn-xs" type="button" data-adjust-item="{{ $it->id }}" data-adjust-name="{{ e($it->name) }}">
                    Ajustar
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="empty">
                  <div class="empty-state">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    <span>Ubicación vacía. No hay inventario registrado aquí.</span>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- Modal: Ajuste stock --}}
<div class="modal" id="adjustModal" aria-hidden="true">
  <div class="back" data-close="1"></div>
  <div class="mcard">
    <div class="mh">
      <div>
        <div class="mtt">Ajustar inventario</div>
        <div class="msub">Ubicación actual: <span class="mono">{{ $location->code }}</span></div>
      </div>
      <button class="x" type="button" data-close="1"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="mb">
      <div class="grid">
        <div class="field">
          <label class="lbl">ID Producto</label>
          <input class="inp" id="adj_item_id" placeholder="Ej: 123">
          <div class="hint" id="adj_item_hint">—</div>
        </div>
        <div class="field">
          <label class="lbl">Tipo de ajuste</label>
          <select class="inp" id="adj_mode">
            <option value="delta">Sumar / Restar (Delta)</option>
            <option value="set">Reemplazar exacto (Set)</option>
          </select>
        </div>
        <div class="field">
          <label class="lbl">Cantidad</label>
          <input class="inp" id="adj_qty" type="number" value="1">
        </div>
        <div class="field" style="grid-column:1/-1">
          <label class="lbl">Motivo / Notas</label>
          <input class="inp" id="adj_notes" placeholder="Ej: Ajuste tras conteo físico semanal">
        </div>
      </div>
    </div>
    <div class="mf">
      <button class="btn btn-ghost" type="button" data-close="1">Cancelar</button>
      <button class="btn btn-primary" type="button" id="btnAdjustSave">Confirmar Ajuste</button>
    </div>
  </div>
</div>

{{-- Modal: Transferir --}}
<div class="modal" id="transferModal" aria-hidden="true">
  <div class="back" data-close="1"></div>
  <div class="mcard">
    <div class="mh">
      <div>
        <div class="mtt">Transferir a otra ubicación</div>
        <div class="msub">Origen: <span class="mono">{{ $location->code }}</span></div>
      </div>
      <button class="x" type="button" data-close="1"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="mb">
      <div class="grid">
        <div class="field">
          <label class="lbl">ID Producto</label>
          <input class="inp" id="tr_item_id" placeholder="Ej: 123">
        </div>
        <div class="field">
          <label class="lbl">Destino (Código)</label>
          <input class="inp" id="tr_to_code" placeholder="Ej: A-02-S1">
          <div class="hint">Escanea el QR del destino.</div>
        </div>
        <div class="field">
          <label class="lbl">Cantidad</label>
          <input class="inp" type="number" id="tr_qty" value="1">
        </div>
        <div class="field" style="grid-column:1/-1">
          <label class="lbl">Motivo / Notas</label>
          <input class="inp" id="tr_notes" placeholder="Ej: Reubicación por consolidación de pasillo">
        </div>
      </div>
    </div>
    <div class="mf">
      <button class="btn btn-ghost" type="button" data-close="1">Cancelar</button>
      <button class="btn btn-primary" type="button" id="btnTransferSave">Ejecutar Transferencia</button>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');

  :root {
    --bg-page: #f8fafc;
    --surface: #ffffff;
    --ink: #0f172a;
    --muted: #64748b;
    --line: #e2e8f0;
    --line-soft: #f1f5f9;
    --brand: #0f172a; /* Negro empresarial moderno en lugar de azul brillante */
    --brand-hover: #1e293b;
    --accent: #2563eb;
    --radius-lg: 16px;
    --radius-md: 10px;
    --shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.06);
    --shadow-md: 0 4px 12px -2px rgba(15, 23, 42, 0.08);
    --shadow-modal: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
    --bezier: cubic-bezier(0.16, 1, 0.3, 1);
  }

  body { font-family: 'Inter', system-ui, sans-serif; background-color: var(--bg-page); color: var(--ink); }
  
  /* Layout y Animaciones base */
  .wrap { max-width: 1200px; margin: 0 auto; padding: 32px 20px; }
  
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .fade-in-up { opacity: 0; animation: fadeUp 0.6s var(--bezier) forwards; }
  .delay-1 { animation-delay: 0.1s; }
  .delay-2 { animation-delay: 0.2s; }
  .delay-3 { animation-delay: 0.3s; }

  /* Header Superior */
  .top { display: flex; gap: 16px; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; margin-bottom: 24px; }
  .top-left { display: flex; gap: 16px; align-items: center; }
  .tt { font-weight: 600; color: var(--ink); font-size: 1.25rem; display: flex; align-items: center; gap: 10px; }
  .sub { color: var(--muted); font-size: 0.875rem; margin-top: 4px; }
  .dot { margin: 0 6px; color: var(--line); }
  
  .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
  .badge-mono { background: var(--line-soft); padding: 2px 8px; border-radius: 6px; font-size: 0.9em; border: 1px solid var(--line); color: var(--muted); }
  
  .actions { display: flex; gap: 12px; flex-wrap: wrap; }

  /* Botones - Estilo Premium */
  .btn { 
    border: 0; border-radius: 8px; padding: 10px 16px; font-weight: 500; font-size: 0.875rem;
    display: inline-flex; gap: 8px; align-items: center; cursor: pointer; white-space: nowrap;
    transition: all 0.2s var(--bezier); font-family: inherit;
  }
  .btn-primary { background: var(--brand); color: #fff; }
  .btn-primary:hover { background: var(--brand-hover); transform: translateY(-1px); box-shadow: var(--shadow-md); }
  .btn-ghost { background: var(--surface); border: 1px solid var(--line); color: var(--ink); box-shadow: var(--shadow-sm); }
  .btn-ghost:hover { background: var(--line-soft); border-color: #cbd5e1; }
  .btn-outline { background: transparent; border: 1px solid var(--line); color: var(--ink); }
  .btn-outline:hover { background: var(--line-soft); }
  .btn-icon { padding: 10px; border-radius: 10px; }
  .btn-xs { padding: 6px 12px; font-size: 0.75rem; }

  /* Tarjetas */
  .cards { display: grid; grid-template-columns: repeat(12, 1fr); gap: 20px; }
  .card { grid-column: span 6; background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden; display: flex; flex-direction: column; }
  
  .card-h { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; padding: 20px; border-bottom: 1px solid var(--line-soft); }
  .card-tt { font-weight: 600; color: var(--ink); font-size: 1.05rem; }
  .card-sub { color: var(--muted); font-size: 0.85rem; margin-top: 4px; }
  
  .chip { font-size: 0.75rem; font-weight: 600; padding: 6px 12px; border-radius: 999px; white-space: nowrap; }
  .chip-soft { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
  .chip-success { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }

  .kv { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; padding: 20px; flex: 1; }
  .k-l { color: var(--muted); font-weight: 500; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; }
  .k-v { color: var(--ink); font-weight: 600; margin-top: 6px; font-size: 0.95rem; }
  
  .card-footer { background: #fafafa; border-top: 1px solid var(--line-soft); padding: 16px 20px; margin-top: auto; }
  .mini-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
  .hint { display: flex; gap: 8px; align-items: flex-start; color: var(--muted); font-size: 0.8rem; line-height: 1.4; }
  .hint svg { flex-shrink: 0; margin-top: 2px; color: #94a3b8; }

  /* Inputs estilo minimalista */
  .bar { padding: 20px 20px 0; }
  .input-icon { position: relative; }
  .input-icon svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
  .inp { width: 100%; min-height: 42px; border: 1px solid var(--line); border-radius: var(--radius-md); padding: 10px 14px 10px 40px; background: var(--surface); color: var(--ink); font-family: inherit; font-size: 0.9rem; transition: all 0.2s ease; box-shadow: var(--shadow-sm); }
  .inp:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
  
  /* Inputs sin ícono en el modal */
  .modal .inp { padding: 10px 14px; }

  /* Tabla moderna */
  .table-wrap { padding: 16px 20px 20px; overflow-x: auto; }
  .tbl { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.875rem; }
  .tbl th, .tbl td { padding: 14px 12px; border-bottom: 1px solid var(--line-soft); vertical-align: middle; }
  .tbl th { color: var(--muted); font-weight: 500; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; text-align: left; }
  .tbl tr { transition: background 0.2s ease; }
  .tbl tbody tr:hover { background: #f8fafc; }
  .t-right { text-align: right; }
  
  .pname { font-weight: 500; color: var(--ink); }
  .pmeta { color: var(--muted); font-size: 0.8rem; margin-top: 4px; }
  .mono-badge { background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-family: var(--mono); font-size: 0.85em; color: #475569; }
  .qty-badge { background: var(--ink); color: #fff; padding: 4px 10px; border-radius: 999px; font-weight: 600; font-size: 0.85em; }
  
  .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; color: #94a3b8; padding: 40px 20px; text-align: center; }

  /* Modal React-like (Framer Motion feel) */
  .modal { position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px; opacity: 0; visibility: hidden; transition: opacity 0.3s var(--bezier), visibility 0.3s; }
  .modal[aria-hidden="false"] { opacity: 1; visibility: visible; }
  .back { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); }
  
  .mcard { position: relative; width: 100%; max-width: 600px; background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-lg); box-shadow: var(--shadow-modal); overflow: hidden; transform: scale(0.95) translateY(10px); transition: transform 0.4s var(--bezier); }
  .modal[aria-hidden="false"] .mcard { transform: scale(1) translateY(0); }
  
  .mh { display: flex; justify-content: space-between; align-items: flex-start; padding: 20px 24px; border-bottom: 1px solid var(--line-soft); }
  .mtt { font-weight: 600; color: var(--ink); font-size: 1.1rem; }
  .msub { color: var(--muted); font-size: 0.85rem; margin-top: 4px; }
  .x { border: 0; background: transparent; color: var(--muted); cursor: pointer; padding: 6px; border-radius: 8px; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
  .x:hover { background: var(--line-soft); color: var(--ink); }
  
  .mb { padding: 24px; }
  .mf { display: flex; justify-content: flex-end; gap: 12px; padding: 16px 24px; background: #fafafa; border-top: 1px solid var(--line-soft); }
  
  .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
  .field { display: flex; flex-direction: column; gap: 6px; }
  .lbl { font-weight: 500; color: var(--ink); font-size: 0.85rem; }

  @media (max-width: 1050px) {
    .card { grid-column: span 12; }
    .kv { grid-template-columns: 1fr; }
    .grid { grid-template-columns: 1fr; }
  }
</style>
@endpush

@push('scripts')
<script>
  // La lógica base de JavaScript se mantiene intacta,
  // sólo hemos refinado cómo el DOM reacciona visualmente.

  const LS_FROM = 'wms_from_code';
  const API_ADJUST  = @json(route('admin.wms.inventory.adjust'));
  const API_TRANSFER= @json(route('admin.wms.inventory.transfer'));
  const API_SCAN_LOC = @json(route('admin.wms.locations.scan'));
  const CSRF = @json(csrf_token());

  const LOC_CODE = @json($location->code);
  const LOC_ID   = @json($location->id);

  function setModal(id, open){
    const m = document.getElementById(id);
    if(!m) return;
    m.setAttribute('aria-hidden', open ? 'false' : 'true');
  }

  document.addEventListener('click', (e) => {
    if(e.target?.closest('[data-close]')){
      setModal('adjustModal', false);
      setModal('transferModal', false);
    }
  });

  // Efectos de Sonido y Vibración (Feedback de UX)
  function beep(ok=true){
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const o = ctx.createOscillator();
      const g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.frequency.value = ok ? 880 : 220;
      g.gain.value = 0.04; // Volumen un poco más bajo, menos invasivo
      o.start();
      setTimeout(()=>{o.stop();ctx.close();}, ok ? 70 : 120);
    } catch(e){}
  }
  function vibrate(ms=40){ try{ if(navigator.vibrate) navigator.vibrate(ms);}catch(e){} }

  // Botón "Fijar Ubicación"
  document.getElementById('btnHere')?.addEventListener('click', async (e) => {
    const btn = e.currentTarget;
    const originalText = btn.innerHTML;
    btn.innerHTML = 'Fijando...';
    btn.disabled = true;

    const url = API_SCAN_LOC + '?code=' + encodeURIComponent(LOC_CODE);
    const res = await fetch(url, {headers:{'Accept':'application/json'}});
    
    if(!res.ok){
      alert('Error de validación de ubicación.');
      beep(false); vibrate(90);
      btn.innerHTML = originalText;
      btn.disabled = false;
      return;
    }
    
    localStorage.setItem(LS_FROM, LOC_CODE);
    beep(true); vibrate(25);
    
    // Feedback visual en el botón
    btn.innerHTML = '¡Ubicación Fijada! ✅';
    btn.classList.add('btn-success');
    setTimeout(() => {
      btn.innerHTML = originalText;
      btn.disabled = false;
      btn.classList.remove('btn-success');
    }, 2000);
  });

  // Filtro en tiempo real
  const filter = document.getElementById('filter');
  const tbl = document.getElementById('tbl');
  const chipCount = document.getElementById('chipCount');

  filter?.addEventListener('input', () => {
    const q = (filter.value||'').toLowerCase().trim();
    const rows = tbl.querySelectorAll('tbody tr[data-row="1"]');
    let shown = 0;
    
    rows.forEach(tr => {
      const t = tr.textContent.toLowerCase();
      const ok = !q || t.includes(q);
      tr.style.display = ok ? '' : 'none';
      if(ok) shown++;
    });
    
    chipCount.textContent = shown + ' filas';
  });

  // Abrir modales
  document.getElementById('btnAdjustOpen')?.addEventListener('click', () => {
    document.getElementById('adj_item_id').value = '';
    document.getElementById('adj_item_hint').textContent = '—';
    setModal('adjustModal', true);
  });
  
  document.getElementById('btnTransferOpen')?.addEventListener('click', () => {
    document.getElementById('tr_item_id').value = '';
    setModal('transferModal', true);
  });

  // Ajustar desde botón por fila
  document.querySelectorAll('[data-adjust-item]')?.forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('adj_item_id').value = btn.getAttribute('data-adjust-item') || '';
      document.getElementById('adj_item_hint').textContent = btn.getAttribute('data-adjust-name') || '—';
      setModal('adjustModal', true);
    });
  });

  // Función Helper Fetch
  async function postJson(url, body){
    const res = await fetch(url, {
      method:'POST',
      headers:{
        'Accept':'application/json',
        'Content-Type':'application/json',
        'X-CSRF-TOKEN': CSRF
      },
      body: JSON.stringify(body || {})
    });
    const data = await res.json().catch(()=> ({}));
    if(!res.ok) data._http_error = true;
    return data;
  }

  // Guardar ajuste
  document.getElementById('btnAdjustSave')?.addEventListener('click', async (e) => {
    const btn = e.currentTarget;
    const itemId = (document.getElementById('adj_item_id').value||'').trim();
    const mode   = (document.getElementById('adj_mode').value||'delta').trim();
    const qty    = parseInt((document.getElementById('adj_qty').value||'0').trim(), 10);
    const notes  = (document.getElementById('adj_notes').value||'').trim();

    if(!itemId || isNaN(qty)){
      alert('Completa producto y cantidad.');
      beep(false); vibrate(90);
      return;
    }

    btn.disabled = true;
    btn.textContent = 'Guardando...';

    const data = await postJson(API_ADJUST, {
      location_id: LOC_ID,
      catalog_item_id: parseInt(itemId,10),
      mode: mode,
      qty: qty,
      notes: notes,
      movement_type: 'adjust'
    });

    if(!data.ok){
      alert(data.error || 'Error en el ajuste.');
      beep(false); vibrate(90);
      btn.disabled = false;
      btn.textContent = 'Confirmar Ajuste';
      return;
    }

    beep(true); vibrate(30);
    location.reload();
  });

  // Transferir
  document.getElementById('btnTransferSave')?.addEventListener('click', async (e) => {
    const btn = e.currentTarget;
    const itemId = (document.getElementById('tr_item_id').value||'').trim();
    const toCode = (document.getElementById('tr_to_code').value||'').trim();
    const qty    = parseInt((document.getElementById('tr_qty').value||'0').trim(), 10);
    const notes  = (document.getElementById('tr_notes').value||'').trim();

    if(!itemId || !toCode || isNaN(qty) || qty < 1){
      alert('Revisa los datos. Producto, destino y cantidad son obligatorios.');
      beep(false); vibrate(90);
      return;
    }

    btn.disabled = true;
    btn.textContent = 'Procesando...';

    const scanUrl = API_SCAN_LOC + '?code=' + encodeURIComponent(toCode);
    const scanRes = await fetch(scanUrl, {headers:{'Accept':'application/json'}});
    const scan = await scanRes.json().catch(()=> ({}));
    
    if(!scanRes.ok || !scan.ok || !scan.location?.id){
      alert(scan.error || 'Ubicación destino inválida.');
      beep(false); vibrate(90);
      btn.disabled = false;
      btn.textContent = 'Ejecutar Transferencia';
      return;
    }

    const data = await postJson(API_TRANSFER, {
      catalog_item_id: parseInt(itemId,10),
      from_location_id: LOC_ID,
      to_location_id: parseInt(scan.location.id, 10),
      qty: qty,
      notes: notes
    });

    if(!data.ok){
      alert(data.error || 'Error al transferir.');
      beep(false); vibrate(90);
      btn.disabled = false;
      btn.textContent = 'Ejecutar Transferencia';
      return;
    }

    beep(true); vibrate(30);
    location.reload();
  });
</script>
@endpush