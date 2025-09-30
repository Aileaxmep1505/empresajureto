@extends('layouts.app')
@section('title','Ventas')
@section('header','Ventas')

@push('styles')
<style>
:root{
  --bg:#f6f8fb; --panel:#ffffff; --text:#0f172a; --muted:#667085; --border:#e7eaf0;
  --primary-600:#5b8def; --ok:#10b981; --warn:#f59e0b; --danger:#ef4444;
  --shadow:0 10px 30px rgba(2,6,23,.06);
}

/* Page */
.page{ max-width:1200px; margin:10px auto 20px; padding:0 12px; }

/* Toolbar (igual al diseño anterior) */
.toolbar{ display:flex; gap:10px; align-items:center; margin:10px 0 14px; flex-wrap:wrap; }
.search{ position:relative; flex:1 1 300px; }
.search input{
  width:100%; padding:12px 42px 12px 14px; background:#fff; border:1px solid var(--border);
  border-radius:12px; outline:0; transition:border-color .2s, box-shadow .2s;
}
.search input:focus{ border-color:var(--primary-600); box-shadow:0 0 0 6px rgba(91,141,239,.12); }
.search .icon{ position:absolute; right:10px; top:50%; transform:translateY(-50%); opacity:.7 }

.btn{
  border:1px solid var(--border); background:#eef3ff; color:#2b3756; border-radius:12px;
  padding:12px 14px; font-weight:700; cursor:pointer; transition:transform .06s, filter .18s;
  text-decoration:none; display:inline-flex; align-items:center; gap:6px;
}
.btn-primary{ background:#e7efff; color:#0f1f47; border-color:#cfe0ff; }
.btn:hover{ filter:brightness(1.02) }
.btn:active{ transform:translateY(1px) }

/* Card & Table */
.card{ background:var(--panel); border:1px solid var(--border); border-radius:16px; box-shadow:var(--shadow); overflow:hidden; }
.table-wrap{ width:100%; overflow:auto; }
table{ width:100%; border-collapse:collapse; }
th, td{ padding:12px 14px; vertical-align:middle; border-bottom:1px solid var(--border); }
th{ text-align:left; font-size:.86rem; color:#6b7280; background:#fff; position:sticky; top:0; z-index:1; }
tr:hover td{ background:#fafcff; }

/* Badges de estado (ejemplos comunes) */
.badge{
  display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px;
  font-size:.82rem; border:1px solid var(--border); white-space:nowrap;
}
.badge.pendiente{ background:#fff7ed; color:#7c2d12; border-color:#fed7aa }
.badge.pagada, .badge.pagado{ background:#dcfce7; color:#14532d; border-color:#bbf7d0 }
.badge.cancelada, .badge.cancelado{ background:#ffe4e6; color:#7f1d1d; border-color:#fecdd3 }

/* Acciones */
.actions{ display:flex; gap:8px; }
.icon-btn{
  display:inline-grid; place-items:center; width:36px; height:36px; border-radius:10px; border:1px solid var(--border);
  background:#fff; cursor:pointer; transition:transform .06s, background .2s;
}
.icon-btn:hover{ background:#f7faff }
.icon-btn:active{ transform:translateY(1px) }

/* Responsive -> tarjetas en móvil */
@media (max-width: 760px){
  .table-wrap{ overflow:visible; }
  table, thead, tbody, th, td, tr{ display:block; }
  thead{ display:none; }
  tbody tr{ border:1px solid var(--border); border-radius:14px; margin:10px 0; overflow:hidden; background:#fff; }
  td{ border:none; padding:10px 14px; }
  td::before{
    content: attr(data-th);
    display:block; font-size:.78rem; color:var(--muted); margin-bottom:3px;
  }
}

/* Toasts (por si usas mensajes flash) */
.toast-area{ position:fixed; right:14px; top:14px; display:flex; flex-direction:column; gap:10px; z-index:9999; }
.toast{
  min-width:240px; max-width:320px; background:linear-gradient(180deg,#ffffff,#f8fbff);
  border:1px solid var(--border); border-radius:14px; padding:10px 12px; display:grid; grid-template-columns:auto 1fr; column-gap:10px;
  box-shadow:0 10px 30px rgba(30,46,90,.12); transform:translateY(-10px) scale(.96); opacity:0; animation:tin .45s ease forwards;
}
.toast--ok{ border-color:#a7f3d0 } .toast--err{ border-color:#fecdd3 }
@keyframes tin{ to{ transform:none; opacity:1 } }
@keyframes tout{ to{ transform:translateY(-10px) scale(.96); opacity:0 } }
</style>
@endpush

@section('content')
<div class="page">
  {{-- Toolbar --}}
  <div class="toolbar">
    <div class="search">
      <input type="search" id="search" placeholder="Buscar por folio, cliente, estado, moneda…" value="{{ request('q') }}">
      <span class="icon" aria-hidden="true">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      </span>
    </div>
    {{-- Si tienes ruta para crear ventas, descomenta: --}}
    {{-- <a class="btn btn-primary" href="{{ route('ventas.create') }}">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
      Nueva
    </a> --}}
  </div>

  {{-- Toast de estado (opcional) --}}
  @if (session('status'))
    <div class="toast-area" id="toastArea">
      <div class="toast toast--ok">
        <div>✅</div>
        <div><strong>{{ session('status') }}</strong><div style="color:#667085;font-size:.9rem">Listo</div></div>
      </div>
    </div>
  @endif

  <div class="card">
    <div class="table-wrap" id="tableWrap">
      <table id="salesTable">
        <thead>
          <tr>
            <th>Folio</th>
            <th>Cliente</th>
            <th>Estado</th>
            <th>Total</th>
            <th style="width:90px">Acciones</th>
          </tr>
        </thead>
        <tbody id="salesBody">
          @forelse($ventas as $v)
            @php
              $estado = strtolower($v->estado ?? '');
              $badgeClass = in_array($estado, ['pendiente','pagada','pagado','cancelada','cancelado']) ? $estado : 'pendiente';
            @endphp
            <tr data-id="{{ $v->id }}">
              <td data-th="Folio">VTA-{{ $v->folio }}</td>
              <td data-th="Cliente">{{ $v->cliente->name ?? '—' }}</td>
              <td data-th="Estado"><span class="badge {{ $badgeClass }}">{{ ucfirst($v->estado) }}</span></td>
              <td data-th="Total">${{ number_format($v->total,2) }} {{ $v->moneda }}</td>
              <td data-th="Acciones">
                <div class="actions">
                  <a class="icon-btn" href="{{ route('ventas.show',$v) }}" title="Ver">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" style="text-align:center;color:var(--muted)">Sin registros</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Paginación --}}
    <div style="padding:10px 12px">
      {{ $ventas->links() }}
    </div>
  </div>

  <div class="toast-area" id="toastArea"></div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const input = document.getElementById('search');
  const body  = document.getElementById('salesBody');
  const toastArea = document.getElementById('toastArea');

  const norm = s => (s||'').toString().toLowerCase()
    .normalize('NFD').replace(/\p{Diacritic}/gu,'').trim();

  // Filtro en vivo
  let t=null;
  input?.addEventListener('input', ()=>{
    clearTimeout(t);
    t = setTimeout(()=>{
      const q = norm(input.value);
      [...body.querySelectorAll('tr')].forEach(tr=>{
        const cells = [...tr.children].map(td => norm(td.textContent));
        const hit = cells.some(txt => txt.includes(q));
        tr.style.display = hit ? '' : 'none';
      });
    }, 160);
  });

  // Auto-ocultar toast si existe
  if (toastArea && toastArea.children.length){
    setTimeout(()=>{
      const el = toastArea.firstElementChild;
      if(el){ el.style.animation='tout .35s ease forwards'; el.addEventListener('animationend',()=>el.remove(),{once:true}); }
    }, 2600);
  }
})();
</script>
@endpush
