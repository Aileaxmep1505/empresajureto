@extends('layouts.app')
@section('title','Proveedores')
@section('header','Proveedores')

@push('styles')
<style>
:root{
  --surface:#ffffff; --border:#e7eaf0; --text:#0f172a; --muted:#667085;
  --primary:#2563eb; --primary-soft:#e7efff;
  --shadow:0 10px 30px rgba(2,6,23,.06);
}

/* ✅ MÁS ANCHO EN DESKTOP */
.page{ max-width:1400px; margin:12px auto 22px; padding:0 14px; }

/* ================= HERO ================= */
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
@media (max-width:576px){ .hero__icon{ display:none } }

/* ================= BUSCADOR ================= */
.searchbar{
  flex:1; display:flex; align-items:center; gap:8px;
  background:#fff; height:46px; border-radius:999px; padding:0 10px 0 12px;
  border:1px solid #cfe0ff; box-shadow: inset 0 1px 0 rgba(255,255,255,.9), 0 6px 14px rgba(29,78,216,.10);
  min-width:300px; max-width:min(82vw, 620px);
}
.sb-icon{ width:26px; display:grid; place-items:center; color:#94a3b8 }
.sb-input{ flex:1; border:0; outline:none; height:100%; font-size:.98rem; color:var(--text); background:transparent; }
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

/* Botón Nuevo */
.pbtn{
  display:inline-flex; align-items:center; gap:8px; height:46px; padding:0 14px;
  border-radius:14px; font-weight:800; color:#0f1f47; background:var(--primary-soft); border:1px solid #cfe0ff;
  text-decoration:none; transition:.15s ease;
}
.pbtn:hover{ background:#fff }

/* ====== Card/Table ====== */
.card{
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:16px;
  box-shadow:var(--shadow);
  overflow:hidden;
  margin-top:14px;
}
.table-wrap{ width:100%; overflow: visible; }
table{ width:100%; border-collapse:collapse }
th, td{ padding:12px 14px; vertical-align:middle; border-bottom:1px solid var(--border) }
th{
  text-align:left; font-size:.86rem; color:#6b7280;
  background:#fff; position:sticky; top:0; z-index:1
}
td{ font-size:.95rem; color:var(--text) }
tr:hover td{ background:#fafcff }

/* Celdas compactas */
#providersTable th:nth-child(4),
#providersTable td:nth-child(4),
#providersTable th:nth-child(5),
#providersTable td:nth-child(5){
  white-space: nowrap;
}
#providersTable th:nth-child(5),
#providersTable td:nth-child(5){
  width: 110px;
}

/* Badges */
.badge{
  display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px;
  font-size:.82rem; border:1px solid var(--border); white-space:nowrap;
}
.badge.activo{ background:#dcfce7; color:#14532d; border-color:#bbf7d0 }
.badge.inactivo{ background:#ffe4e6; color:#7f1d1d; border-color:#fecdd3 }

/* Acciones */
.actions{ display:flex; gap:8px; justify-content:flex-end }
.icon-btn{
  display:inline-grid; place-items:center; width:36px; height:36px; border-radius:10px;
  border:1px solid var(--border); background:#fff; cursor:pointer;
  transition:transform .06s, background .2s;
}
.icon-btn:hover{ background:#f7faff }
.icon-btn:active{ transform:translateY(1px) }

/* Avatar */
.p-avatar{
  width:38px; height:38px; border-radius:50%;
  background:#e8f0ff; border:1px solid #d7e6ff;
  display:grid; place-items:center;
  font-weight:800; color:#2563eb;
}

/* Layout inside cells */
.p-main{ display:flex; align-items:center; gap:12px; }
.p-title{ font-weight:800; line-height:1.1 }
.p-sub{ font-size:.85rem; color:var(--muted); margin-top:2px }

.p-contact-name{ font-weight:700; line-height:1.2 }
.p-contact-row{
  font-size:.9rem; color:var(--muted);
  display:flex; align-items:center; gap:8px; margin-top:3px;
}
.p-contact-row svg{ width:16px; height:16px; color:#94a3b8; flex:none }

/* Móvil: tarjetas */
@media (max-width: 760px){
  .table-wrap{ overflow:auto; -webkit-overflow-scrolling: touch; }
  table{ min-width: 860px; }

  table, thead, tbody, th, td, tr{ display:block }
  thead{ display:none }
  tbody tr{ border:1px solid var(--border); border-radius:14px; margin:10px 0; overflow:hidden; background:#fff }
  td{ border:none; padding:10px 14px }
  td::before{ content: attr(data-th); display:block; font-size:.78rem; color:var(--muted); margin-bottom:3px }
  .actions{ justify-content:flex-start }
}
</style>
@endpush

@section('content')
@php
  $q = $q ?? request('q','');
@endphp

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
        <h1 class="h4">Proveedores</h1>
        <div class="subtle">Gestión de proveedores y contactos.</div>
      </div>
    </div>

    <div class="hero__right">
      <form class="searchbar" onsubmit="return false;">
        <span class="sb-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2">
            <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
          </svg>
        </span>
        <input id="liveSearch" class="sb-input" type="search"
               value="{{ $q }}"
               placeholder="Buscar por empresa, folio, contacto, correo, teléfono, ciudad, estado…">
        <button type="button" class="sb-clear" id="sbClear" aria-label="Limpiar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 6L6 18M6 6l12 12"/>
          </svg>
        </button>
      </form>

      <a class="pbtn" href="{{ route('providers.create') }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 5v14M5 12h14"/>
        </svg>
        Nuevo
      </a>
    </div>
  </div>

  {{-- Tabla --}}
  <div class="card">
    <div class="table-wrap">
      <table id="providersTable">
        <thead>
          <tr>
            <th>Proveedor</th>
            <th>Contacto</th>
            <th>Ciudad/Estado</th>
            <th>Estado</th>
            <th style="width:110px;text-align:right">Acciones</th>
          </tr>
        </thead>

        <tbody id="providersBody">
          @forelse($providers as $p)
            @php
              $empresa = $p->empresa ?: 'Proveedor';
              $folio   = $p->code ?: '—';

              $contacto = $p->nombre ?: '—';
              $email    = $p->email ?: '—';
              $tel      = $p->telefono ?: '—';

              $ubic = trim(($p->ciudad ?: '').' / '.($p->estado ?: ''), ' /');
              $ubic = $ubic !== '' ? $ubic : '—';

              $initial = strtoupper(mb_substr($empresa, 0, 1, 'UTF-8'));
            @endphp

            <tr data-id="{{ $p->id }}">

              {{-- Proveedor (avatar + empresa + folio abajo) --}}
              <td data-th="Proveedor">
                <div class="p-main">
                  <div class="p-avatar">{{ $initial }}</div>
                  <div>
                    <div class="p-title">{{ $empresa }}</div>
                    <div class="p-sub">{{ $folio }}</div>
                  </div>
                </div>
              </td>

              {{-- Contacto (nombre + correo + tel) --}}
              <td data-th="Contacto">
                <div class="p-contact-name">{{ $contacto }}</div>

                <div class="p-contact-row" title="{{ $email }}">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16v16H4z"/><path d="m22 6-10 7L2 6"/>
                  </svg>
                  <span style="max-width:520px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block;">
                    {{ $email }}
                  </span>
                </div>

                <div class="p-contact-row">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 3.11 5.18 2 2 0 0 1 5.11 3h3a2 2 0 0 1 2 1.72c.12.9.32 1.77.59 2.6a2 2 0 0 1-.45 2.11L9.09 10.91a16 16 0 0 0 4 4l1.48-1.16a2 2 0 0 1 2.11-.45c.83.27 1.7.47 2.6.59A2 2 0 0 1 22 16.92z"/>
                  </svg>
                  <span>{{ $tel }}</span>
                </div>
              </td>

              {{-- Ciudad/Estado --}}
              <td data-th="Ciudad/Estado">{{ $ubic }}</td>

              {{-- Estado --}}
              <td data-th="Estado">
                <span class="badge {{ $p->estatus ? 'activo' : 'inactivo' }}">
                  {{ $p->estatus ? 'Activo' : 'Inactivo' }}
                </span>
              </td>

              {{-- Acciones --}}
              <td data-th="Acciones">
                <div class="actions">
                  <a class="icon-btn" href="{{ route('providers.edit',$p) }}" title="Editar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                    </svg>
                  </a>

                  <form method="POST" action="{{ route('providers.destroy',$p) }}" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="icon-btn" title="Eliminar" onclick="return confirm('¿Eliminar proveedor?');">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                        <path d="M10 11v6M14 11v6"/>
                        <path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/>
                      </svg>
                    </button>
                  </form>
                </div>
              </td>

            </tr>
          @empty
            <tr>
              <td colspan="5" style="padding:22px;color:#667085">
                No hay proveedores registrados.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
(function(){
  const input = document.getElementById('liveSearch');
  const clearBtn = document.getElementById('sbClear');
  const body  = document.getElementById('providersBody');
  const norm = s => (s||'').toString().toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu,'').trim();

  function filter(){
    const q = norm(input?.value);
    if(clearBtn) clearBtn.style.visibility = q ? 'visible':'hidden';
    if(!body) return;

    [...body.querySelectorAll('tr')].forEach(tr=>{
      // ignora la fila "no hay proveedores"
      if(tr.children.length < 2) return;

      const cells = [...tr.children].map(td => norm(td.textContent));
      tr.style.display = (!q || cells.some(txt => txt.includes(q))) ? '' : 'none';
    });
  }

  if(input){
    input.addEventListener('input', ()=>{
      window.clearTimeout(input._t);
      input._t = setTimeout(filter, 120);
    });
  }
  if(clearBtn){
    clearBtn.addEventListener('click', ()=>{
      input.value = '';
      filter();
      input.focus();
    });
  }

  filter();
})();
</script>
@endpush