@extends('layouts.app')

@section('title','Usuarios')
@section('header','Usuarios')

@push('styles')
<style>
/* ====== Contenedor de página ====== */
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
}
@keyframes heroIn{ from{opacity:0; transform:translateY(8px)} to{opacity:1; transform:none} }

.hero__left{ display:flex; align-items:center; gap:12px; min-width:280px }
.hero__icon{ width:48px; height:48px; border-radius:50%; display:grid; place-items:center; background:#fff; border:1px solid #dce7ff }
.hero h1{ margin:0; font-weight:800; letter-spacing:-.02em }
.subtle{ color:var(--muted) }
.hero__right{ display:flex; align-items:center; gap:12px }

/* Ocultar icono en pantallas pequeñas */
@media (max-width: 576px){ .hero__icon{ display:none } }

/* ================= BUSCADOR NORMAL ================= */
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

/* ====== Grid ====== */
.cards{ display:grid; gap:22px; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); margin-top:14px }

/* ====== Card ====== */
.card{
  background:var(--surface); border:1px solid var(--border); border-radius:18px; padding:16px;
  transition: transform .18s ease, border-color .25s ease, box-shadow .25s ease
}
.card:hover{
  transform:translateY(-3px);
  border-color:#c7d4f5;
  box-shadow: 0 10px 30px rgba(59,130,246,.12), 0 0 0 4px rgba(59,130,246,.08) inset
}

/* ====== Contenido card ====== */
.user{ display:grid; grid-template-rows:auto auto auto auto; row-gap:10px }
.u-head{ display:flex; align-items:flex-start; justify-content:space-between; gap:12px }
.user h3{ margin:0; line-height:1.2; word-break:break-word }
.u-mail{ color:var(--muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap }
.u-roles strong{ font-weight:800 }
.actions{ display:flex; gap:10px; flex-wrap:wrap }
.role-form{ display:flex; gap:10px; flex-wrap:wrap }

/* ====== Botones ====== */
.btn{
  position:relative; overflow:hidden; white-space:nowrap;
  padding:12px 14px; border:1px solid var(--border); border-radius:12px; cursor:pointer;
  background:#eef3ff; color:#2b3756; font-weight:700;
  transition: transform .08s ease, filter .18s ease, background .2s ease, border-color .2s ease
}
.btn:hover{ filter:brightness(1.02) }
.btn:active{ transform:translateY(1px) }
.btn.primary{ background:linear-gradient(135deg,#7EA8FF,#A8C9FF); color:#0f1f47; border-color:#9bbcfb }
.btn.danger{  background:linear-gradient(135deg,#ff9bbd,#ff6a9a); color:#4a0f23; border-color:#ff92b9 }
.btn[disabled], .btn:disabled{ opacity:.55; cursor:not-allowed }

/* ====== Select estilizado ====== */
select.input, .input-select{
  appearance:none; -webkit-appearance:none; -moz-appearance:none;
  min-width:180px; padding:10px 38px 10px 12px;
  border:1px solid var(--border); border-radius:12px; background:#fff; color:var(--text);
  background-image:url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="%23667885" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>');
  background-repeat:no-repeat; background-position:right 10px center;
  transition:border-color .2s ease, box-shadow .2s ease
}
select.input:focus, .input-select:focus{ outline:none; border-color:#9bbcfb; box-shadow:0 0 0 6px rgba(91,141,239,.12) }

/* ====== Badges (texto visible en ES) ====== */
.badge{
  display:inline-flex; align-items:center; gap:6px;
  padding:4px 10px; border-radius:999px; font-size:.82rem; border:1px solid var(--border);
  white-space:nowrap; transition:background .2s ease, color .2s ease, border-color .2s ease
}
.badge.pending  { background:#fff5d9; color:#8a6d1a; border-color:#f5e6b5; position:relative }
.badge.approved { background:#dcfce7; color:#14532d; border-color:#bbf7d0 }
.badge.revoked  { background:#ffe4e6; color:#7f1d1d; border-color:#fecdd3 }
.badge.pending::after{ content:""; position:absolute; inset:-2px; border-radius:999px; border:2px solid rgba(245,158,11,.35); animation:pulse 1.8s infinite ease-out }
@keyframes pulse{ 0%{transform:scale(1);opacity:.8} 100%{transform:scale(1.15);opacity:0} }

/* ====== Paginación ====== */
.pagination{ margin-top:22px; display:flex; justify-content:center }
.pagination .hidden{ display:none }
.pagination nav{ display:flex; gap:6px; flex-wrap:wrap }
.pagination a, .pagination span{
  padding:10px 12px; border-radius:10px; border:1px solid var(--border);
  background:var(--surface); color:var(--text); text-decoration:none; font-weight:700;
  transition:background .2s ease, transform .06s ease
}
.pagination a:hover{ background:#eef3ff }
.pagination .active span{ background:#e3ebff; color:var(--primary-600) }

/* ====== Toast ====== */
.toast-area{ position:fixed; right:14px; top:14px; display:flex; flex-direction:column; gap:10px; z-index:9999 }
.toast{
  min-width:240px; max-width:320px; background:linear-gradient(180deg,#ffffff,#f8fbff);
  border:1px solid var(--border); color:#1f2a44; border-radius:14px; padding:10px 12px;
  display:grid; grid-template-columns:auto 1fr; column-gap:10px; align-items:center;
  transform:translateY(-10px) scale(.96); opacity:0; animation:toastIn .45s cubic-bezier(.2,.9,.2,1) forwards;
  box-shadow:0 10px 30px rgba(30,46,90,.12)
}
.toast--ok{ border-color:#a7f3d0 } .toast--err{ border-color:#fecdd3 }
.toast__icon{ width:22px; height:22px; display:grid; place-items:center }
.toast__text{ font-weight:700 } .toast__sub{ color:var(--muted); font-weight:600; font-size:.88rem }
@keyframes toastIn{ to{ transform:translateY(0) scale(1); opacity:1 } }
@keyframes toastOut{ to{ transform:translateY(-10px) scale(.96); opacity:0 } }

/* SweetAlert2: bordes redondeados + botones */
.swal-rounded.swal2-popup{ border-radius:18px !important; padding:18px !important }
.swal-confirm{ border-radius:12px; padding:10px 14px; font-weight:800; background:linear-gradient(135deg,#ff9bbd,#ff6a9a); color:#4a0f23; border:1px solid #ff92b9; margin-left:8px }
.swal-confirm:hover{ filter:brightness(1.04) }
.swal-cancel{ border-radius:12px; padding:10px 14px; font-weight:800; background:#fff; color:#334155; border:1px solid var(--border) }
.swal-cancel:hover{ background:#f8fafc }
</style>
@endpush

@section('content')
<div class="page">

  {{-- ENCABEZADO + BUSCADOR --}}
  <div class="hero">
    <div class="hero__left">
      <div class="hero__icon" aria-hidden="true">
        {{-- icono usuarios (2 siluetas) --}}
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
      </div>
      <div>
        <h1 class="h4" style="margin-bottom:2px">Usuarios</h1>
        <div class="subtle">Gestiona aprobaciones, roles y accesos.</div>
      </div>
    </div>

    <div class="hero__right">
      {{-- Buscador normal (input text para evitar el tache nativo) --}}
      <form class="searchbar" method="GET" action="{{ url()->current() }}" role="search" aria-label="Buscar" onsubmit="return false;">
        <span class="sb-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>
        </span>
        <input id="liveSearch" class="sb-input" type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre, correo, rol o estado…" autocomplete="off">
        <button type="button" class="sb-clear" id="sbClear" aria-label="Limpiar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
      </form>
    </div>
  </div>
  {{-- /HERO --}}

  @if (session('status'))
    <div class="toast-area" id="toastArea">
      <div class="toast toast--ok">
        <div class="toast__icon">✅</div>
        <div><div class="toast__text">{{ session('status') }}</div><div class="toast__sub">Listo</div></div>
      </div>
    </div>
  @endif

  <div class="cards" id="userGrid">
    @foreach($users as $u)
      @php
        $statusMap = ['approved'=>'aprobado','pending'=>'pendiente','revoked'=>'rechazado'];
        $statusText = $statusMap[$u->status] ?? $u->status;
        $rolesText = $u->getRoleNames()->implode(', ') ?: '—';
      @endphp
      <div class="card user"
           id="user-card-{{ $u->id }}"
           data-user-id="{{ $u->id }}"
           data-status="{{ $u->status }}"
           data-name="{{ Str::of($u->name)->lower() }}"
           data-email="{{ Str::of($u->email)->lower() }}"
           data-roles="{{ Str::of($rolesText)->lower() }}"
           data-status-es="{{ $statusText }}">
        <div class="u-head">
          <h3 class="u-name">{{ $u->name }}</h3>
          <span class="badge {{ $u->status }} u-badge">{{ $statusText }}</span>
        </div>

        <div class="u-mail" title="{{ $u->email }}">{{ $u->email }}</div>

        <div class="u-roles">
          Roles: <strong class="u-roles-text">{{ $rolesText }}</strong>
        </div>

        <div class="actions">
          @if(!$u->isApproved())
            <button class="btn primary js-approve" data-id="{{ $u->id }}">Aprobar</button>
          @else
            <button class="btn danger js-revoke" data-id="{{ $u->id }}">Revocar</button>
          @endif
        </div>

        <form method="POST" action="{{ route('admin.users.role.assign',$u) }}" class="role-form js-role-form" data-id="{{ $u->id }}">
          @csrf
          <select name="role" class="input input-select js-role-select">
            @foreach($roles as $r)
              <option value="{{ $r->name }}" {{ $u->hasRole($r->name) ? 'selected' : '' }}>{{ $r->name }}</option>
            @endforeach
          </select>
          <button class="btn">Guardar rol</button>
        </form>
      </div>
    @endforeach
  </div>

  <div class="pagination">
    {{ $users->links() }}
  </div>

  <div class="toast-area" id="toastArea"></div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function(){
  const csrftoken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const toastArea = document.getElementById('toastArea');

  /* ====== Buscador en vivo ====== */
  const input   = document.getElementById('liveSearch');
  const clearBtn= document.getElementById('sbClear');
  const cards   = Array.from(document.querySelectorAll('.card.user'));

  function normalize(str){
    return (str||'')
      .toString()
      .normalize('NFD').replace(/[\u0300-\u036f]/g,'') // quita acentos
      .toLowerCase().trim();
  }
  // Mapeo de palabras a estados internos
  const statusAliases = {
    'aprobado':'approved','aprobada':'approved','aprovado':'approved','aprovada':'approved',
    'pendiente':'pending','pend':'pending',
    'rechazado':'revoked','revocado':'revoked','rechazada':'revoked','revocada':'revoked'
  };

  function matchCard(card, query){
    if(!query) return true;
    const tokens = normalize(query).split(/\s+/).filter(Boolean);
    if(!tokens.length) return true;

    // Campos a buscar
    const name  = normalize(card.dataset.name);
    const email = normalize(card.dataset.email);
    const roles = normalize(card.dataset.roles);
    const status = normalize(card.dataset.status);     // approved|pending|revoked
    const statusEs = normalize(card.dataset.statusEs); // aprobado|pendiente|rechazado

    // Composición para búsqueda libre
    const bag = [name,email,roles,status,statusEs].join(' ');
    // Además, si token es alias de estado, lo sustituimos
    return tokens.every(t=>{
      const st = statusAliases[t];
      if(st && (status.includes(st))) return true;
      return bag.includes(t);
    });
  }

  function applyFilter(){
    const q = input.value;
    clearBtn.style.visibility = q ? 'visible' : 'hidden';
    let any = false;
    cards.forEach(card=>{
      const ok = matchCard(card, q);
      card.style.display = ok ? '' : 'none';
      if(ok) any = true;
    });
    // (opcional) podrías mostrar “sin resultados” aquí
  }
  input.addEventListener('input', applyFilter);
  clearBtn.addEventListener('click', ()=>{ input.value=''; applyFilter(); input.focus(); });
  // Ejecuta una vez por si venía ?q= en la URL
  applyFilter();

  /* ====== Toast ====== */
  function showToast(type, text, sub=''){
    const el = document.createElement('div');
    el.className = 'toast ' + (type === 'ok' ? 'toast--ok' : 'toast--err');
    el.innerHTML = `<div class="toast__icon">${type==='ok'?'✅':'⚠️'}</div><div><div class="toast__text">${text}</div><div class="toast__sub">${sub|| (type==='ok'?'Éxito':'Atención')}</div></div>`;
    toastArea.appendChild(el);
    setTimeout(()=>{ el.style.animation='toastOut .35s ease forwards'; el.addEventListener('animationend',()=> el.remove(),{once:true}); },2700);
  }

  const statusToEs = (s)=> ({approved:'aprobado', pending:'pendiente', revoked:'rechazado'})[s] || s;

  function updateCardState(card, nextStatus){
    card.dataset.status = nextStatus;
    card.dataset.statusEs = statusToEs(nextStatus);
    const badge = card.querySelector('.u-badge');
    badge.textContent = statusToEs(nextStatus);
    badge.classList.remove('pending','approved','revoked');
    badge.classList.add(nextStatus);
    const actions = card.querySelector('.actions');
    actions.innerHTML = nextStatus === 'approved'
      ? `<button class="btn danger js-revoke" data-id="${card.dataset.userId || card.getAttribute('data-user-id')}">Revocar</button>`
      : `<button class="btn primary js-approve" data-id="${card.dataset.userId || card.getAttribute('data-user-id')}">Aprobar</button>`;
  }

  async function postJSON(url, payload = {}){
    const res = await fetch(url, {
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN': csrftoken,
        'X-Requested-With':'XMLHttpRequest'
      },
      body: JSON.stringify(payload)
    });
    let data = null; try{ data = await res.clone().json(); }catch(_){}
    return { ok: res.ok, status: res.status, data };
  }

  // Aprobar
  document.addEventListener('click', async (e)=>{
    const btn = e.target.closest('.js-approve'); if(!btn) return;
    e.preventDefault();
    const id = btn.getAttribute('data-id');
    const card = document.getElementById('user-card-'+id);
    const { data } = await postJSON(`{{ url('/admin/users') }}/${id}/approve`, { ajax:true });
    updateCardState(card, (data && data.status) ? data.status : 'approved');
    showToast('ok','Usuario aprobado','Se actualizó el estado');
    applyFilter(); // re-evaluar filtro por si estaba filtrando por estado
  });

  // Revocar
  document.addEventListener('click', async (e)=>{
    const btn = e.target.closest('.js-revoke'); if(!btn) return;
    e.preventDefault();
    const id = btn.getAttribute('data-id');
    const card = document.getElementById('user-card-'+id);

    const result = await Swal.fire({
      title:'¿Revocar acceso?',
      text:'El usuario perderá el acceso al sistema.',
      icon:'warning',
      showCancelButton:true,
      confirmButtonText:'Sí, revocar',
      cancelButtonText:'Cancelar',
      reverseButtons:true,
      customClass:{ popup:'swal-rounded', confirmButton:'swal-confirm', cancelButton:'swal-cancel' },
      buttonsStyling:false,
      backdrop:true
    });
    if(result.isConfirmed){
      const { data } = await postJSON(`{{ url('/admin/users') }}/${id}/revoke`, { ajax:true });
      updateCardState(card, (data && data.status) ? data.status : 'pending');
      showToast('ok','Acceso revocado','El usuario ya no puede ingresar');
      applyFilter();
    }
  });

  // Aparición escalonada
  document.querySelectorAll('.card.user').forEach((el,i)=>{ el.style.animation=`heroIn .45s ease ${i*70}ms both`; });
})();
</script>
@endpush
