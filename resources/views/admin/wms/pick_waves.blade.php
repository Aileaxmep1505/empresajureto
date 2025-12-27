@extends('layouts.app')

@section('title', 'WMS · Picking Waves')

@section('content')
<div class="wrap">
  <div class="top">
    <a href="{{ route('admin.wms.home') }}" class="btn btn-ghost">← WMS</a>

    <div class="mid">
      <div class="tt">Picking Waves</div>
      <div class="sub">Crea un wave y surte con escaneo guiado (ubicación → producto → qty).</div>
    </div>

    <div class="actions">
      <button class="btn btn-primary" id="btnCreateOpen" type="button">➕ Crear wave</button>
    </div>
  </div>

  <div class="card">
    <div class="card-h">
      <div>
        <div class="card-tt">Waves recientes</div>
        <div class="card-sub">Abre para surtir.</div>
      </div>
      <span class="chip chip-soft">{{ $waves->count() }} waves</span>
    </div>

    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th>Código</th>
            <th>Status</th>
            <th>Creado</th>
            <th class="t-right">Acción</th>
          </tr>
        </thead>
        <tbody>
          @forelse($waves as $w)
            <tr>
              <td class="mono"><b>{{ $w->code }}</b> <span class="muted">(#{{ $w->id }})</span></td>
              <td>
                @php
                  $st = (int)$w->status;
                  $label = match($st){ 0=>'Nuevo',1=>'En progreso',2=>'Terminado',3=>'Cancelado',default=>'—' };
                @endphp
                <span class="tag tag-{{ $st }}">{{ $label }}</span>
              </td>
              <td class="muted">{{ $w->created_at?->format('Y-m-d H:i') }}</td>
              <td class="t-right">
                <a class="btn btn-ghost btn-xs" href="{{ route('admin.wms.pick.show', ['wave'=>$w->id]) }}">Abrir</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="empty">No hay waves aún.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="hint">Tip: si quieres “flujo Amazon”, crea waves por lote (lista) y un operador solo se dedica a surtir.</div>
  </div>
</div>

{{-- Modal crear wave --}}
<div class="modal" id="createModal" aria-hidden="true">
  <div class="back" data-close="1"></div>
  <div class="mcard">
    <div class="mh">
      <div>
        <div class="mtt">Crear wave</div>
        <div class="msub">Pega líneas: <span class="mono">catalog_item_id,qty</span></div>
      </div>
      <button class="x" type="button" data-close="1">✕</button>
    </div>

    <div class="mb">
      <label class="lbl">Items</label>
      <textarea class="inp area" id="itemsText" placeholder="Ej:
123,2
456,1
789,6"></textarea>
      <div class="hint">Se sugiere ubicación por stock. (Con 1 bodega ya no eliges warehouse).</div>

      <label class="lbl" style="margin-top:10px">Asignarme a mí</label>
      <label class="toggle">
        <input type="checkbox" id="assignMe" checked>
        <span>Auto-asignar</span>
      </label>
    </div>

    <div class="mf">
      <button class="btn btn-ghost" type="button" data-close="1">Cancelar</button>
      <button class="btn btn-primary" type="button" id="btnCreate">Crear</button>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  :root{--ink:#0b1220;--muted:#64748b;--line:#e6eaf2;--line2:#eef2f7;--brand:#2563eb;--radius:18px}
  .wrap{max-width:1100px;margin:0 auto;padding:18px 14px 28px}
  .top{display:flex;gap:12px;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;margin-bottom:12px}
  .mid{flex:1 1 420px}
  .tt{font-weight:950;color:var(--ink);font-size:1.05rem}
  .sub{color:var(--muted);font-size:.9rem;margin-top:2px}
  .actions{display:flex;gap:10px;flex-wrap:wrap}

  .btn{border:0;border-radius:999px;padding:10px 14px;font-weight:950;display:inline-flex;gap:8px;align-items:center;cursor:pointer;white-space:nowrap;transition:transform .12s ease, box-shadow .12s ease}
  .btn-primary{background:var(--brand);color:#eff6ff;box-shadow:0 14px 30px rgba(37,99,235,.30)}
  .btn-primary:hover{transform:translateY(-1px);box-shadow:0 18px 38px rgba(37,99,235,.34)}
  .btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink)}
  .btn-ghost:hover{transform:translateY(-1px);box-shadow:0 10px 22px rgba(2,6,23,.06)}
  .btn-xs{padding:6px 10px;font-size:.78rem}

  .card{background:#fff;border:1px solid var(--line);border-radius:22px;box-shadow:0 10px 22px rgba(2,6,23,.05);overflow:hidden}
  .card-h{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;padding:12px 14px;border-bottom:1px solid var(--line)}
  .card-tt{font-weight:950;color:var(--ink)}
  .card-sub{color:var(--muted);font-size:.85rem;margin-top:2px}
  .chip{font-size:.78rem;font-weight:950;padding:6px 10px;border-radius:999px;background:#eff6ff;color:#1e40af;border:1px solid #dbeafe;white-space:nowrap}
  .chip-soft{background:#f8fafc;color:#0f172a;border-color:var(--line2)}
  .hint{color:var(--muted);font-size:.78rem;padding:12px 14px}

  .table-wrap{padding:0 14px 12px;overflow:auto}
  .tbl{width:100%;border-collapse:collapse;font-size:.88rem}
  .tbl th,.tbl td{padding:10px 10px;border-bottom:1px solid var(--line2);vertical-align:top}
  .tbl th{color:#0f172a;font-weight:950;background:#f8fafc;white-space:nowrap}
  .t-right{text-align:right}
  .empty{color:var(--muted);padding:16px 10px;text-align:center}
  .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}
  .muted{color:var(--muted)}

  .tag{display:inline-flex;padding:6px 10px;border-radius:999px;font-weight:950;font-size:.78rem;border:1px solid var(--line2);background:#fff}
  .tag-0{background:#eff6ff;color:#1e40af;border-color:#dbeafe}
  .tag-1{background:#dcfce7;color:#166534;border-color:#bbf7d0}
  .tag-2{background:#f1f5f9;color:#0f172a;border-color:#e2e8f0}
  .tag-3{background:#fee2e2;color:#991b1b;border-color:#fecaca}

  /* Modal */
  .modal{position:fixed;inset:0;display:none;z-index:9999}
  .modal[aria-hidden="false"]{display:block}
  .back{position:absolute;inset:0;background:rgba(2,6,23,.55);backdrop-filter:blur(10px)}
  .mcard{position:relative;max-width:740px;margin:18px auto;background:#fff;border:1px solid rgba(226,232,240,.8);border-radius:22px;box-shadow:0 30px 80px rgba(2,6,23,.35);overflow:hidden}
  .mh{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;padding:12px 14px;border-bottom:1px solid var(--line)}
  .mtt{font-weight:950;color:var(--ink)}
  .msub{color:var(--muted);font-size:.85rem;margin-top:2px}
  .x{border:0;background:transparent;font-size:1.2rem;cursor:pointer;padding:6px 10px;border-radius:12px}
  .x:hover{background:#f1f5f9}
  .mb{padding:12px 14px}
  .mf{display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;padding:12px 14px;border-top:1px solid var(--line)}
  .lbl{display:block;font-weight:950;color:var(--ink);font-size:.85rem;margin-bottom:6px}
  .inp{width:100%;min-height:44px;border:1px solid var(--line);border-radius:14px;padding:10px 12px;background:#f8fafc;color:#0f172a}
  .inp:focus{outline:none;border-color:#93c5fd;box-shadow:0 0 0 3px rgba(147,197,253,.35);background:#fff}
  .area{min-height:140px;resize:vertical;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}
  .toggle{display:flex;gap:10px;align-items:center;color:#334155;font-weight:900}
  @media (max-width: 900px){ .mcard{margin:18px 10px} }
</style>
@endpush

@push('scripts')
<script>
  const CSRF = @json(csrf_token());
  const API_CREATE = @json(route('admin.wms.pick.waves.create'));
  const WAREHOUSE_ID = @json($warehouseId);

  function setModal(open){
    document.getElementById('createModal').setAttribute('aria-hidden', open ? 'false' : 'true');
  }
  document.addEventListener('click', (e)=>{
    if(e.target?.getAttribute?.('data-close')) setModal(false);
  });

  document.getElementById('btnCreateOpen')?.addEventListener('click', ()=> setModal(true));

  async function postJson(url, body){
    const res = await fetch(url, {
      method:'POST',
      headers:{
        'Accept':'application/json',
        'Content-Type':'application/json',
        'X-CSRF-TOKEN': CSRF
      },
      body: JSON.stringify(body||{})
    });
    const data = await res.json().catch(()=> ({}));
    if(!res.ok) data._http_error = true;
    return data;
  }

  function parseLines(txt){
    const lines = (txt||'').split('\n').map(s=>s.trim()).filter(Boolean);
    const items = [];
    for(const ln of lines){
      const parts = ln.split(',').map(s=>s.trim());
      const id = parseInt(parts[0]||'',10);
      const qty = parseInt(parts[1]||'1',10);
      if(!id || isNaN(id)) continue;
      items.push({catalog_item_id:id, qty: (isNaN(qty)||qty<1)?1:qty});
    }
    return items;
  }

  document.getElementById('btnCreate')?.addEventListener('click', async ()=>{
    const txt = document.getElementById('itemsText').value || '';
    const items = parseLines(txt);
    const assign = !!document.getElementById('assignMe').checked;

    if(!items.length){
      alert('Pega al menos una línea válida: id,qty');
      return;
    }

    const data = await postJson(API_CREATE, {
      warehouse_id: WAREHOUSE_ID,
      items: items,
      assign_to_me: assign
    });

    if(!data.ok){
      alert(data.error || 'No se pudo crear el wave.');
      return;
    }

    // Redirigir al wave
    const waveId = data.wave?.id;
    if(waveId){
      window.location.href = @json(route('admin.wms.pick.show', ['wave' => '__ID__'])).replace('__ID__', waveId);
      return;
    }

    alert('Creado, pero no pude obtener ID.');
    location.reload();
  });
</script>
@endpush
