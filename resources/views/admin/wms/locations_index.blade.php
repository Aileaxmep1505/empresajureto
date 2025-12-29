@extends('layouts.app')

@section('title', 'WMS · Ubicaciones')

@section('content')
<div class="wrap">
  <div class="top">
    <a href="{{ route('admin.wms.home') }}" class="btn btn-ghost">← WMS</a>

    <div class="mid">
      <div class="tt">Ubicaciones</div>
      <div class="sub">Listado de ubicaciones del almacén</div>
    </div>

    <div class="actions">
      <a class="btn btn-primary" href="{{ route('admin.wms.layout.editor') }}">🧱 Layout</a>
      <a class="btn btn-ghost" href="{{ route('admin.wms.heatmap.view') }}">🔥 Heatmap</a>
    </div>
  </div>

  <div class="card">
    <div class="card-h">
      <div>
        <div class="card-tt">Listado</div>
        <div class="card-sub">Se carga desde la API (JSON) y se muestra aquí</div>
      </div>
      <span class="chip chip-soft" id="chipCount">—</span>
    </div>

    <div class="bar">
      <input class="inp" id="q" placeholder="Filtrar por código / nombre…">
      <button class="btn btn-ghost" id="btnReload" type="button">↻ Recargar</button>
    </div>

    <div class="table-wrap">
      <table class="tbl" id="tbl">
        <thead>
          <tr>
            <th>Código</th>
            <th>Tipo</th>
            <th>Nombre</th>
            <th class="t-right">Ver</th>
          </tr>
        </thead>
        <tbody id="tbody">
          <tr><td colspan="4" class="empty">Cargando…</td></tr>
        </tbody>
      </table>
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
  .actions{display:flex;gap:10px;flex-wrap:wrap}

  .btn{border:0;border-radius:999px;padding:10px 14px;font-weight:950;display:inline-flex;gap:8px;align-items:center;cursor:pointer;white-space:nowrap;transition:transform .12s ease, box-shadow .12s ease}
  .btn-primary{background:var(--brand);color:#eff6ff;box-shadow:0 14px 30px rgba(37,99,235,.30)}
  .btn-primary:hover{transform:translateY(-1px);box-shadow:0 18px 38px rgba(37,99,235,.34)}
  .btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink)}
  .btn-ghost:hover{transform:translateY(-1px);box-shadow:0 10px 22px rgba(2,6,23,.06)}

  .card{background:#fff;border:1px solid var(--line);border-radius:22px;box-shadow:0 10px 22px rgba(2,6,23,.05);overflow:hidden}
  .card-h{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;padding:12px 14px;border-bottom:1px solid var(--line)}
  .card-tt{font-weight:950;color:var(--ink)}
  .card-sub{color:var(--muted);font-size:.85rem;margin-top:2px}
  .chip{font-size:.78rem;font-weight:950;padding:6px 10px;border-radius:999px;background:#dcfce7;color:#166534;border:1px solid #bbf7d0;white-space:nowrap}
  .chip-soft{background:#eff6ff;color:#1e40af;border-color:#dbeafe}

  .bar{display:flex;gap:10px;align-items:center;padding:12px 14px;border-bottom:1px solid var(--line2);flex-wrap:wrap}
  .inp{flex:1 1 260px;min-height:44px;border:1px solid var(--line);border-radius:14px;padding:10px 12px;background:#f8fafc;color:#0f172a}
  .inp:focus{outline:none;border-color:#93c5fd;box-shadow:0 0 0 3px rgba(147,197,253,.35);background:#fff}

  .table-wrap{padding:0 14px 12px;overflow:auto}
  .tbl{width:100%;border-collapse:collapse;font-size:.9rem}
  .tbl th,.tbl td{padding:10px 10px;border-bottom:1px solid var(--line2);vertical-align:top}
  .tbl th{color:#0f172a;font-weight:950;background:#f8fafc;white-space:nowrap}
  .t-right{text-align:right}
  .empty{color:var(--muted);padding:16px 10px;text-align:center}
  .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}
</style>
@endpush

@push('scripts')
<script>
  const API_LOCATIONS_DATA = @json(route('admin.wms.locations.data'));

  const tbody = document.getElementById('tbody');
  const chip = document.getElementById('chipCount');
  const qInp = document.getElementById('q');

  let ALL = [];

  function render(list){
    const shown = list.length;
    chip.textContent = shown + ' ubicaciones';

    if(!shown){
      tbody.innerHTML = `<tr><td colspan="4" class="empty">Sin resultados.</td></tr>`;
      return;
    }

    tbody.innerHTML = list.map(l => {
      const code = (l.code ?? '—');
      const type = (l.type ?? '—');
      const name = (l.name ?? '—');

      // ✅ Tu ruta show actual es admin.wms.locations.show (controller)
      // y tu página QR es admin.wms.location.page
      const hrefShow = `{{ url('/admin/wms/locations') }}/${encodeURIComponent(l.id)}`;
      const hrefPage = `{{ url('/admin/wms/locations') }}/${encodeURIComponent(l.id)}/page`;

      return `
        <tr data-row="1">
          <td class="mono"><b>${escapeHtml(code)}</b></td>
          <td>${escapeHtml(type)}</td>
          <td>${escapeHtml(name)}</td>
          <td class="t-right">
            <a class="btn btn-ghost" style="padding:6px 10px;font-size:.78rem" href="${hrefPage}">Abrir (QR)</a>
          </td>
        </tr>
      `;
    }).join('');
  }

  function escapeHtml(s){
    return String(s ?? '').replace(/[&<>"']/g, m => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
    }[m]));
  }

  function applyFilter(){
    const q = (qInp.value || '').toLowerCase().trim();
    const list = !q ? ALL : ALL.filter(l => {
      const t = `${l.code ?? ''} ${l.name ?? ''} ${l.type ?? ''}`.toLowerCase();
      return t.includes(q);
    });
    render(list);
  }

  async function load(){
    tbody.innerHTML = `<tr><td colspan="4" class="empty">Cargando…</td></tr>`;
    chip.textContent = '—';

    const res = await fetch(API_LOCATIONS_DATA, { headers: { 'Accept':'application/json' } });
    const data = await res.json().catch(()=> ({}));

    // Tu locationsIndex devuelve { ok:true, locations:{current_page..., data:[...] } }
    const list = data?.locations?.data || data?.locations || [];
    ALL = Array.isArray(list) ? list : [];

    applyFilter();
  }

  document.getElementById('btnReload')?.addEventListener('click', load);
  qInp?.addEventListener('input', applyFilter);

  load();
</script>
@endpush
