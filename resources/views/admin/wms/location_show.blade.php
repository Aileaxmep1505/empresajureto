@extends('layouts.app')

@section('title', 'WMS · Ubicación ' . $location->code)

@section('content')
<div class="wrap">
  <div class="top">
    <a href="{{ route('admin.wms.home') }}" class="btn btn-ghost">← WMS</a>

    <div class="mid">
      <div class="tt">Ubicación · <span class="mono">{{ $location->code }}</span></div>
      <div class="sub">
        {{ $location->name ?? '—' }}
        <span class="dot">•</span>
        Inventario en esta ubicación.
      </div>
    </div>

    <div class="actions">
      <button class="btn btn-primary" id="btnHere" type="button">📍 Estoy aquí</button>
      <a class="btn btn-ghost" href="{{ route('admin.wms.search.view', ['from' => $location->code]) }}">🔎 Buscar desde aquí</a>
      <a class="btn btn-ghost" href="{{ route('admin.wms.qr.print.one', ['location' => $location->id]) }}">🖨️ Imprimir QR</a>
    </div>
  </div>

  <div class="cards">
    <div class="card">
      <div class="card-h">
        <div>
          <div class="card-tt">Resumen</div>
          <div class="card-sub">Datos rápidos de la ubicación</div>
        </div>
        <span class="chip">{{ $rows->count() }} productos</span>
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

      <div class="hr"></div>

      <div class="mini-actions">
        <button class="btn btn-ghost" id="btnAdjustOpen" type="button">➕ Ajustar stock</button>
        <button class="btn btn-ghost" id="btnTransferOpen" type="button">🔁 Transferir</button>
      </div>

      <div class="hint">
        Tip: imprime este QR en el stand/bin. Al escanear, esta pantalla se abre y fijas “dónde estás”.
      </div>
    </div>

    <div class="card">
      <div class="card-h">
        <div>
          <div class="card-tt">Inventario</div>
          <div class="card-sub">Busca rápido dentro de esta ubicación</div>
        </div>
        <span class="chip chip-soft" id="chipCount">{{ $rows->count() }} filas</span>
      </div>

      <div class="bar">
        <input class="inp" id="filter" placeholder="Filtrar por nombre / SKU / GTIN…">
      </div>

      <div class="table-wrap">
        <table class="tbl" id="tbl">
          <thead>
            <tr>
              <th>Producto</th>
              <th>SKU</th>
              <th>GTIN</th>
              <th class="t-right">Qty</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($rows as $r)
              @php
                $it = $r->item;
              @endphp
              <tr data-row="1">
                <td>
                  <div class="pname">{{ $it->name }}</div>
                  <div class="pmeta">$ {{ number_format((float)$it->price, 2) }}</div>
                </td>
                <td class="mono">{{ $it->sku ?? '—' }}</td>
                <td class="mono">{{ $it->meli_gtin ?? '—' }}</td>
                <td class="t-right mono"><b>{{ (int)$r->qty }}</b></td>
                <td class="t-right">
                  <button class="btn btn-ghost btn-xs"
                          type="button"
                          data-adjust-item="{{ $it->id }}"
                          data-adjust-name="{{ e($it->name) }}">
                    Ajustar
                  </button>
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="empty">Sin inventario en esta ubicación.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="hint">
        Nota: este inventario es por ubicación. Tu “stock total” por producto puede estar repartido en varios stands/bins.
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
        <div class="mtt">Ajustar stock</div>
        <div class="msub">Ubicación: <span class="mono">{{ $location->code }}</span></div>
      </div>
      <button class="x" type="button" data-close="1">✕</button>
    </div>

    <div class="mb">
      <div class="grid">
        <div class="field">
          <label class="lbl">Producto (ID)</label>
          <input class="inp" id="adj_item_id" placeholder="Ej: 123">
          <div class="hint" id="adj_item_hint">—</div>
        </div>

        <div class="field">
          <label class="lbl">Modo</label>
          <select class="inp" id="adj_mode">
            <option value="delta">Sumar / Restar (delta)</option>
            <option value="set">Fijar exacto (set)</option>
          </select>
          <div class="hint">Delta: +5 o -2 · Set: cantidad exacta</div>
        </div>

        <div class="field">
          <label class="lbl">Cantidad</label>
          <input class="inp" id="adj_qty" value="1">
          <div class="hint">Enteros. No uses decimales.</div>
        </div>

        <div class="field" style="grid-column:1/-1">
          <label class="lbl">Notas (opcional)</label>
          <input class="inp" id="adj_notes" placeholder="Ej: Ajuste por conteo físico">
        </div>
      </div>
    </div>

    <div class="mf">
      <button class="btn btn-ghost" type="button" data-close="1">Cancelar</button>
      <button class="btn btn-primary" type="button" id="btnAdjustSave">Guardar</button>
    </div>
  </div>
</div>

{{-- Modal: Transferir --}}
<div class="modal" id="transferModal" aria-hidden="true">
  <div class="back" data-close="1"></div>
  <div class="mcard">
    <div class="mh">
      <div>
        <div class="mtt">Transferir stock</div>
        <div class="msub">Desde: <span class="mono">{{ $location->code }}</span></div>
      </div>
      <button class="x" type="button" data-close="1">✕</button>
    </div>

    <div class="mb">
      <div class="grid">
        <div class="field">
          <label class="lbl">Producto (ID)</label>
          <input class="inp" id="tr_item_id" placeholder="Ej: 123">
        </div>

        <div class="field">
          <label class="lbl">Hacia (código ubicación)</label>
          <input class="inp" id="tr_to_code" placeholder="Ej: A-02-S1-R1-N1-B03">
          <div class="hint">Debe existir. Puedes escanear el QR y copiar el code.</div>
        </div>

        <div class="field">
          <label class="lbl">Cantidad</label>
          <input class="inp" id="tr_qty" value="1">
        </div>

        <div class="field" style="grid-column:1/-1">
          <label class="lbl">Notas (opcional)</label>
          <input class="inp" id="tr_notes" placeholder="Ej: reubicación por acomodo">
        </div>
      </div>
    </div>

    <div class="mf">
      <button class="btn btn-ghost" type="button" data-close="1">Cancelar</button>
      <button class="btn btn-primary" type="button" id="btnTransferSave">Transferir</button>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  :root{
    --ink:#0b1220;--muted:#64748b;--line:#e6eaf2;--line2:#eef2f7;
    --brand:#2563eb;--shadow:0 18px 55px rgba(2,6,23,.08);--radius:18px;
  }
  .wrap{max-width:1200px;margin:0 auto;padding:18px 14px 28px}
  .top{display:flex;gap:12px;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;margin-bottom:12px}
  .mid{flex:1 1 420px}
  .tt{font-weight:950;color:var(--ink);font-size:1.05rem}
  .sub{color:var(--muted);font-size:.9rem;margin-top:2px}
  .dot{margin:0 6px;color:#cbd5e1}
  .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}
  .actions{display:flex;gap:10px;flex-wrap:wrap}

  .btn{border:0;border-radius:999px;padding:10px 14px;font-weight:950;display:inline-flex;gap:8px;align-items:center;cursor:pointer;white-space:nowrap;transition:transform .12s ease, box-shadow .12s ease}
  .btn-primary{background:var(--brand);color:#eff6ff;box-shadow:0 14px 30px rgba(37,99,235,.30)}
  .btn-primary:hover{transform:translateY(-1px);box-shadow:0 18px 38px rgba(37,99,235,.34)}
  .btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink)}
  .btn-ghost:hover{transform:translateY(-1px);box-shadow:0 10px 22px rgba(2,6,23,.06)}
  .btn-xs{padding:6px 10px;font-size:.78rem}

  .cards{display:grid;grid-template-columns:repeat(12,1fr);gap:12px}
  .card{grid-column:span 6;background:#fff;border:1px solid var(--line);border-radius:22px;box-shadow:0 10px 22px rgba(2,6,23,.05);overflow:hidden}
  .card-h{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;padding:12px 14px;border-bottom:1px solid var(--line)}
  .card-tt{font-weight:950;color:var(--ink)}
  .card-sub{color:var(--muted);font-size:.85rem;margin-top:2px}
  .chip{font-size:.78rem;font-weight:950;padding:6px 10px;border-radius:999px;background:#dcfce7;color:#166534;border:1px solid #bbf7d0;white-space:nowrap}
  .chip-soft{background:#eff6ff;color:#1e40af;border-color:#dbeafe}

  .kv{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;padding:12px 14px}
  .k-l{color:var(--muted);font-weight:900;font-size:.78rem}
  .k-v{color:var(--ink);font-weight:950;margin-top:2px}
  .hr{border-top:1px dashed #e5e7eb;margin:0 14px}
  .mini-actions{display:flex;gap:10px;flex-wrap:wrap;padding:12px 14px}
  .hint{color:var(--muted);font-size:.78rem;padding:0 14px 12px}

  .bar{padding:12px 14px}
  .inp{width:100%;min-height:44px;border:1px solid var(--line);border-radius:14px;padding:10px 12px;background:#f8fafc;color:#0f172a}
  .inp:focus{outline:none;border-color:#93c5fd;box-shadow:0 0 0 3px rgba(147,197,253,.35);background:#fff}

  .table-wrap{padding:0 14px 12px;overflow:auto}
  .tbl{width:100%;border-collapse:collapse;font-size:.88rem}
  .tbl th,.tbl td{padding:10px 10px;border-bottom:1px solid var(--line2);vertical-align:top}
  .tbl th{color:#0f172a;font-weight:950;background:#f8fafc;white-space:nowrap}
  .t-right{text-align:right}
  .pname{font-weight:950;color:var(--ink)}
  .pmeta{color:var(--muted);font-size:.8rem;margin-top:2px}
  .empty{color:var(--muted);padding:16px 10px;text-align:center}

  /* Modal */
  .modal{position:fixed;inset:0;display:none;z-index:9999}
  .modal[aria-hidden="false"]{display:block}
  .back{position:absolute;inset:0;background:rgba(2,6,23,.55);backdrop-filter:blur(10px)}
  .mcard{position:relative;max-width:720px;margin:18px auto;background:#fff;border:1px solid rgba(226,232,240,.8);border-radius:22px;box-shadow:0 30px 80px rgba(2,6,23,.35);overflow:hidden}
  .mh{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;padding:12px 14px;border-bottom:1px solid var(--line)}
  .mtt{font-weight:950;color:var(--ink)}
  .msub{color:var(--muted);font-size:.85rem;margin-top:2px}
  .x{border:0;background:transparent;font-size:1.2rem;cursor:pointer;padding:6px 10px;border-radius:12px}
  .x:hover{background:#f1f5f9}
  .mb{padding:12px 14px}
  .mf{display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;padding:12px 14px;border-top:1px solid var(--line)}
  .grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
  .field{min-width:0}
  .lbl{display:block;font-weight:950;color:var(--ink);font-size:.85rem;margin-bottom:6px}

  @media (max-width: 1050px){
    .card{grid-column:span 12}
    .kv{grid-template-columns:1fr}
    .grid{grid-template-columns:1fr}
    .mcard{margin:18px 10px}
  }
</style>
@endpush

@push('scripts')
<script>
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

  document.addEventListener('click', (e)=>{
    if(e.target?.getAttribute?.('data-close')){
      setModal('adjustModal', false);
      setModal('transferModal', false);
    }
  });

  function beep(ok=true){
    try{
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const o = ctx.createOscillator();
      const g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.frequency.value = ok ? 880 : 220;
      g.gain.value = 0.06;
      o.start();
      setTimeout(()=>{o.stop();ctx.close();}, ok ? 70 : 120);
    }catch(e){}
  }
  function vibrate(ms=40){ try{ if(navigator.vibrate) navigator.vibrate(ms);}catch(e){} }

  // “Estoy aquí”
  document.getElementById('btnHere')?.addEventListener('click', async ()=>{
    // valida que exista por API (por si cambió)
    const url = API_SCAN_LOC + '?code=' + encodeURIComponent(LOC_CODE);
    const res = await fetch(url, {headers:{'Accept':'application/json'}});
    if(!res.ok){
      alert('No se pudo validar la ubicación.');
      beep(false); vibrate(90);
      return;
    }
    localStorage.setItem(LS_FROM, LOC_CODE);
    beep(true); vibrate(25);
    alert('Ubicación fijada ✅\nAhora “Buscar” y “Llévame” usarán: ' + LOC_CODE);
  });

  // filtro
  const filter = document.getElementById('filter');
  const tbl = document.getElementById('tbl');
  const chipCount = document.getElementById('chipCount');

  filter?.addEventListener('input', ()=>{
    const q = (filter.value||'').toLowerCase().trim();
    const rows = tbl.querySelectorAll('tbody tr[data-row="1"]');
    let shown = 0;
    rows.forEach(tr=>{
      const t = tr.textContent.toLowerCase();
      const ok = !q || t.includes(q);
      tr.style.display = ok ? '' : 'none';
      if(ok) shown++;
    });
    chipCount.textContent = shown + ' filas';
  });

  // abrir modales
  document.getElementById('btnAdjustOpen')?.addEventListener('click', ()=> setModal('adjustModal', true));
  document.getElementById('btnTransferOpen')?.addEventListener('click', ()=> setModal('transferModal', true));

  // Ajustar desde botón por fila
  document.querySelectorAll('[data-adjust-item]')?.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      document.getElementById('adj_item_id').value = btn.getAttribute('data-adjust-item') || '';
      document.getElementById('adj_item_hint').textContent = btn.getAttribute('data-adjust-name') || '—';
      setModal('adjustModal', true);
    });
  });

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
  document.getElementById('btnAdjustSave')?.addEventListener('click', async ()=>{
    const itemId = (document.getElementById('adj_item_id').value||'').trim();
    const mode   = (document.getElementById('adj_mode').value||'delta').trim();
    const qty    = parseInt((document.getElementById('adj_qty').value||'0').trim(), 10);
    const notes  = (document.getElementById('adj_notes').value||'').trim();

    if(!itemId || isNaN(qty)){
      alert('Completa producto y cantidad.');
      beep(false); vibrate(90);
      return;
    }

    const data = await postJson(API_ADJUST, {
      location_id: LOC_ID,
      catalog_item_id: parseInt(itemId,10),
      mode: mode,
      qty: qty,
      notes: notes,
      movement_type: 'adjust'
    });

    if(!data.ok){
      alert(data.error || 'No se pudo ajustar.');
      beep(false); vibrate(90);
      return;
    }

    beep(true); vibrate(30);
    alert('Guardado ✅\nRecarga para ver cambios.');
    location.reload();
  });

  // Transferir
  document.getElementById('btnTransferSave')?.addEventListener('click', async ()=>{
    const itemId = (document.getElementById('tr_item_id').value||'').trim();
    const toCode = (document.getElementById('tr_to_code').value||'').trim();
    const qty    = parseInt((document.getElementById('tr_qty').value||'0').trim(), 10);
    const notes  = (document.getElementById('tr_notes').value||'').trim();

    if(!itemId || !toCode || isNaN(qty) || qty < 1){
      alert('Completa producto, destino y cantidad.');
      beep(false); vibrate(90);
      return;
    }

    // resolver to_location_id por scan
    const scanUrl = API_SCAN_LOC + '?code=' + encodeURIComponent(toCode);
    const scanRes = await fetch(scanUrl, {headers:{'Accept':'application/json'}});
    const scan = await scanRes.json().catch(()=> ({}));
    if(!scanRes.ok || !scan.ok){
      alert(scan.error || 'Ubicación destino inválida.');
      beep(false); vibrate(90);
      return;
    }

    const toLocationId = scan.location?.id;
    if(!toLocationId){
      alert('No se pudo obtener ID del destino.');
      return;
    }

    const data = await postJson(API_TRANSFER, {
      catalog_item_id: parseInt(itemId,10),
      from_location_id: LOC_ID,
      to_location_id: parseInt(toLocationId,10),
      qty: qty,
      notes: notes
    });

    if(!data.ok){
      alert(data.error || 'No se pudo transferir.');
      beep(false); vibrate(90);
      return;
    }

    beep(true); vibrate(30);
    alert('Transferido ✅\nRecarga para ver cambios.');
    location.reload();
  });
</script>
@endpush
