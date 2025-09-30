@extends('layouts.app')
@section('title','Clientes')
@section('header','Clientes')

@push('styles')
<style>
:root{
  --surface:#ffffff; --border:#e7eaf0; --text:#0f172a; --muted:#667085;
  --primary:#2563eb; --primary-soft:#e7efff;
  --shadow:0 10px 30px rgba(2,6,23,.06);
}

.page{ max-width:1140px; margin:12px auto 22px; padding:0 14px }

/* ================= HERO (Encabezado azul) ================= */
.hero{
  position:relative; border-radius:22px; padding:16px 18px;
  background:
    radial-gradient(1200px 160px at 0% 0%, rgba(59,130,246,.16), transparent 40%),
    radial-gradient(1200px 160px at 100% 0%, rgba(29,78,216,.12), transparent 38%),
    var(--surface);
  border:1px solid var(--border);
  display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;
  animation: heroIn .45s ease both;
  box-shadow: var(--shadow);
}
@keyframes heroIn{ from{opacity:0; transform:translateY(8px)} to{opacity:1; transform:none} }

.hero__left{ display:flex; align-items:center; gap:12px; min-width:280px }
.hero__icon{ width:48px; height:48px; border-radius:50%; display:grid; place-items:center; background:#fff; border:1px solid #dce7ff }
.hero h1{ margin:0; font-weight:800; letter-spacing:-.02em }
.subtle{ color:var(--muted) }
.hero__right{ display:flex; align-items:center; gap:12px }

/* Ocultar icono en pantallas pequeñas */
@media (max-width: 576px){ .hero__icon{ display:none } }

/* ================= BUSCADOR ================= */
.searchbar{
  flex:1; display:flex; align-items:center; gap:8px;
  background:#fff; height:46px; border-radius:999px; padding:0 10px 0 12px;
  border:1px solid #cfe0ff; box-shadow: inset 0 1px 0 rgba(255,255,255,.9), 0 6px 14px rgba(29,78,216,.10);
  min-width:300px; max-width:min(82vw, 560px);
}
.sb-icon{ width:26px; display:grid; place-items:center; color:#94a3b8 }
.sb-input{
  flex:1; border:0; outline:none; height:100%; font-size:.98rem; color:var(--text); background:transparent;
}
.sb-clear{
  border:0; background:transparent; color:#94a3b8; width:28px; height:28px; border-radius:50%;
  display:grid; place-items:center; cursor:pointer; visibility:hidden;
}
.sb-clear:hover{ background:#f1f5f9; color:#64748b }

@media (max-width:768px){
  .hero{ padding:14px }
  .hero__right{ width:100%; justify-content:flex-end }
  .searchbar{ width:100%; max-width:100% }
}

/* Botón Nuevo (sin subrayado) */
.pbtn{
  display:inline-flex; align-items:center; gap:8px; height:46px; padding:0 14px;
  border-radius:14px; font-weight:800; color:#0f1f47; background:var(--primary-soft); border:1px solid #cfe0ff;
  text-decoration:none; transition:.15s ease;
}
.pbtn:hover{ background:#fff }

/* ====== Card/Table ====== */
.card{ background:var(--surface); border:1px solid var(--border); border-radius:16px; box-shadow:var(--shadow); overflow:hidden; margin-top:14px }
.table-wrap{ width:100%; overflow:auto }
table{ width:100%; border-collapse:collapse }
th, td{ padding:12px 14px; vertical-align:middle; border-bottom:1px solid var(--border) }
th{ text-align:left; font-size:.86rem; color:#6b7280; background:#fff; position:sticky; top:0; z-index:1 }
tr:hover td{ background:#fafcff }

/* Badges / tags */
.badge{
  display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px;
  font-size:.82rem; border:1px solid var(--border); white-space:nowrap;
}
.badge.activo{ background:#dcfce7; color:#14532d; border-color:#bbf7d0 }
.badge.inactivo{ background:#ffe4e6; color:#7f1d1d; border-color:#fecdd3 }
.tag{
  display:inline-flex; align-items:center; gap:6px; padding:2px 8px; border-radius:999px;
  font-size:.78rem; background:#eef3ff; color:#2b3756; border:1px solid #d8e4ff;
}

/* Acciones */
.actions{ display:flex; gap:8px }
.icon-btn{
  display:inline-grid; place-items:center; width:36px; height:36px; border-radius:10px; border:1px solid var(--border);
  background:#fff; cursor:pointer; transition:transform .06s, background .2s;
}
.icon-btn:hover{ background:#f7faff }
.icon-btn:active{ transform:translateY(1px) }

/* Responsive: tarjetas */
@media (max-width: 760px){
  table, thead, tbody, th, td, tr{ display:block }
  thead{ display:none }
  tbody tr{ border:1px solid var(--border); border-radius:14px; margin:10px 0; overflow:hidden; background:#fff }
  td{ border:none; padding:10px 14px }
  td::before{ content: attr(data-th); display:block; font-size:.78rem; color:var(--muted); margin-bottom:3px }
}

/* Toasts */
.toast-area{ position:fixed; right:14px; top:14px; display:flex; flex-direction:column; gap:10px; z-index:9999 }
.toast{
  min-width:240px; max-width:320px; background:linear-gradient(180deg,#ffffff,#f8fbff);
  border:1px solid var(--border); border-radius:14px; padding:10px 12px; display:grid; grid-template-columns:auto 1fr; column-gap:10px;
  box-shadow:0 10px 30px rgba(30,46,90,.12); transform:translateY(-10px) scale(.96); opacity:0; animation:tin .45s ease forwards
}
.toast--ok{ border-color:#a7f3d0 } .toast--err{ border-color:#fecdd3 }
@keyframes tin{ to{ transform:none; opacity:1 } }
@keyframes tout{ to{ transform:translateY(-10px) scale(.96); opacity:0 } }
</style>
@endpush

@section('content')
<div class="page">

  {{-- HERO --}}
  <div class="hero">
    <div class="hero__left">
      <div class="hero__icon" aria-hidden="true">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1e3a8a" stroke-width="2">
          <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
      </div>
      <div>
        <h1 class="h4">Clientes</h1>
        <div class="subtle">Gestiona clientes, contactos y estados.</div>
      </div>
    </div>

    <div class="hero__right">
      <form class="searchbar" onsubmit="return false;">
        <span class="sb-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2">
            <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
          </svg>
        </span>
        <input id="liveSearch" class="sb-input" type="search" placeholder="Buscar por nombre, correo, RFC, tipo (gobierno/empresa), ciudad, estado…">
        <button type="button" class="sb-clear" id="sbClear" aria-label="Limpiar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 6L6 18M6 6l12 12"/>
          </svg>
        </button>
      </form>

      <a class="pbtn" href="{{ route('clients.create') }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 5v14M5 12h14"/>
        </svg>
        Nuevo
      </a>
    </div>
  </div>

  {{-- CARD + TABLE --}}
  <div class="card">
    <div class="table-wrap">
      <table id="clientsTable">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Tipo</th>
            <th>RFC</th>
            <th>Contacto</th>
            <th>Teléfono</th>
            <th>Ciudad/Estado</th>
            <th>Estatus</th>
            <th style="width:84px">Acciones</th>
          </tr>
        </thead>
        <tbody id="clientsBody">
          @foreach($clients as $c)
            <tr data-id="{{ $c->id }}">
              <td data-th="Nombre">{{ $c->nombre }}</td>
              <td data-th="Correo">{{ $c->email }}</td>
              <td data-th="Tipo">
                @if($c->tipo_cliente)
                  <span class="tag">{{ $c->etiqueta_tipo }}</span>
                @else
                  — 
                @endif
              </td>
              <td data-th="RFC">{{ $c->rfc ?: '—' }}</td>
              <td data-th="Contacto">{{ $c->contacto ?: '—' }}</td>
              <td data-th="Teléfono">{{ $c->telefono ?: '—' }}</td>
              <td data-th="Ciudad/Estado">{{ trim(($c->ciudad ?: '').' / '.($c->estado ?: ''), ' /') ?: '—' }}</td>
              <td data-th="Estatus">
                <span class="badge {{ $c->estatus ? 'activo' : 'inactivo' }}">{{ $c->etiqueta_estatus }}</span>
              </td>
              <td data-th="Acciones">
                <div class="actions">
                  <a class="icon-btn" href="{{ route('clients.edit',$c) }}" title="Editar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                  </a>
                  <button class="icon-btn js-del" data-id="{{ $c->id }}" title="Eliminar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/></svg>
                  </button>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div style="padding:10px 12px">
      {{ $clients->links() }}
    </div>
  </div>

  <div class="toast-area" id="toastArea"></div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function(){
  const input = document.getElementById('liveSearch');
  const clearBtn = document.getElementById('sbClear');
  const body  = document.getElementById('clientsBody');
  const toastArea = document.getElementById('toastArea');
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  const norm = s => (s||'').toString().toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu,'').trim();

  // Filtro en vivo
  let t=null;
  function filter(){
    const q = norm(input?.value);
    clearBtn.style.visibility = q ? 'visible':'hidden';
    [...body.querySelectorAll('tr')].forEach(tr=>{
      const cells = [...tr.children].map(td => norm(td.textContent));
      tr.style.display = !q || cells.some(txt => txt.includes(q)) ? '' : 'none';
    });
  }
  input?.addEventListener('input', ()=>{ clearTimeout(t); t=setTimeout(filter,160); });
  clearBtn?.addEventListener('click', ()=>{ input.value=''; filter(); input.focus(); });
  filter();

  // Toast helper
  function showToast(type, text, sub=''){
    const el = document.createElement('div');
    el.className = 'toast ' + (type==='ok'?'toast--ok':'toast--err');
    el.innerHTML = `<div>${type==='ok'?'✅':'⚠️'}</div><div><strong>${text}</strong><div style="color:#667085;font-size:.9rem">${sub||''}</div></div>`;
    toastArea.appendChild(el);
    setTimeout(()=>{ el.style.animation='tout .35s ease forwards'; el.addEventListener('animationend',()=>el.remove(),{once:true}); }, 2600);
  }

  // Eliminar con confirmación
  document.addEventListener('click', async (e)=>{
    const btn = e.target.closest('.js-del'); if(!btn) return;
    const id = btn.dataset.id;
    const row = body.querySelector(`tr[data-id="${id}"]`);

    const swal = await Swal.fire({
      title: '¿Eliminar cliente?',
      text: 'Esta acción no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
      confirmButtonColor: '#ef476f',
      cancelButtonColor: '#9aa5b1',
      customClass: { popup: 'rounded-4' }
    });
    if(!swal.isConfirmed) return;

    const res = await fetch(`{{ url('clients') }}/${id}`,{
      method:'POST',
      headers:{ 'X-CSRF-TOKEN': csrf, 'Accept':'application/json' },
      body: new URLSearchParams({ _method:'DELETE' })
    });

    if(res.ok){ row?.remove(); showToast('ok','Cliente eliminado'); }
    else { showToast('err','No se pudo eliminar'); }
  });
})();
</script>
@endpush
