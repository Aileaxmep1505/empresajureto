@extends('layouts.app')
@section('title','Productos')
@section('header','Productos')

@push('styles')
<style>
:root{
  --btn-blue:#2563eb; --btn-blue-h:#1d4ed8; --btn-blue-soft:#e6efff;
  --btn-green:#059669; --btn-green-h:#047857; --btn-green-soft:#e6fff4;
  --btn-gray:#64748b; --btn-gray-h:#475569; --btn-gray-soft:#eef2f7;
  --btn-red:#ef4444; --btn-red-h:#dc2626; --btn-red-soft:#ffe9eb;
  --surface:#ffffff; --border:#e5e7eb; --muted:#6b7280;
}
.page{ max-width:1200px; margin:12px auto 24px; padding:0 14px }

.hero{
  display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
  background: radial-gradient(800px 120px at 10% 0%, rgba(59,130,246,.10), transparent 60%),
              radial-gradient(800px 120px at 100% 0%, rgba(14,165,233,.09), transparent 60%),
              var(--surface);
  border:1px solid var(--border); border-radius:18px; padding:12px 14px;
}
.hero h1{ margin:0; font-weight:800; letter-spacing:-.02em }
.subtle{ color:var(--muted) }

.pbtn{ font-weight:800; border-radius:14px; padding:10px 14px; display:inline-flex; align-items:center; gap:8px; text-decoration:none; border:2px solid transparent }
.pbtn-blue{ color:var(--btn-blue); background:var(--btn-blue-soft); border-color:#cfe0ff }
.pbtn-green{ color:var(--btn-green); background:var(--btn-green-soft); border-color:#cfeedd }
.btn-icon{ width:36px; height:36px; display:inline-grid; place-items:center; padding:0; border-radius:12px; border:0; cursor:pointer }
.btn-icon.blue{ background:var(--btn-blue); color:#fff } .btn-icon.red{ background:var(--btn-red); color:#fff }

.searchbar{
  display:flex; align-items:center; gap:8px; background:#fff; height:42px; border-radius:999px; padding:0 10px 0 12px;
  border:1px solid #cfe0ff; box-shadow: inset 0 1px 0 rgba(255,255,255,.9), 0 6px 14px rgba(29,78,216,.10);
  min-width:260px; max-width:min(70vw, 520px)
}
.sb-icon{ width:24px; display:grid; place-items:center; color:#94a3b8 }
.sb-input{ flex:1; border:0; outline:none; background:transparent }
.sb-clear{ border:0; background:transparent; color:#94a3b8; width:28px; height:28px; border-radius:50%; display:grid; place-items:center; cursor:pointer; visibility:hidden }
.sb-clear:hover{ background:#f1f5f9; color:#64748b }

/* Tabla estable */
.table-wrap{
  margin-top:14px; background:var(--surface); border:1px solid var(--border); border-radius:16px;
  overflow:auto; contain: paint; -webkit-overflow-scrolling:touch;
}
table{
  width:100%; min-width: 980px;
  border-collapse:separate; border-spacing:0; table-layout: fixed;
}
thead th{
  background:#f7faff; color:#334155; text-align:left; font-weight:800;
  border-bottom:1px solid var(--border); padding:12px 12px; white-space:nowrap;
}
tbody td{ padding:12px; border-bottom:1px solid var(--border); vertical-align:top }
tbody tr:hover{ background:#f8fbff }
tbody tr{ will-change: transform; transform: translateZ(0); -webkit-transform: translateZ(0); backface-visibility:hidden; }
th.th-actions, td.t-actions{ position:sticky; right:0; background:var(--surface); z-index:2; border-left:1px solid var(--border) }

/* Miniatura */
.thumb{ width:88px; height:66px; object-fit:cover; border-radius:10px; background:#f1f5f9; border:1px solid var(--border) }

/* Bloque Info */
.info-head{ font-weight:800; line-height:1.25; margin-bottom:6px }
.info-sub{ display:flex; flex-wrap:wrap; gap:10px; margin-bottom:8px }
.badge{ padding:.22rem .5rem; border-radius:999px; font-weight:800; font-size:.75rem; background:#eef2f7; color:#334155; border:1px solid #e5e7eb }
.badge-strong{ background:#e6efff; color:#1d4ed8; border-color:#cfe0ff }
.badge-color{ background:#e6fff4; color:#047857; border-color:#cfeedd }

.kv{
  display:grid; grid-template-columns: max(140px) 1fr; gap:4px 12px;
  font-size:.92rem; color:#334155;
}
.kv .k{ color:#64748b }
.kv .v{ color:#111827; word-break:break-word }
.kv code{ font-size:.82rem; background:#f8fafc; padding:.08rem .3rem; border-radius:6px; border:1px solid #e5e7eb }

/* Botón mini IA */
.ia-pill{
  display:inline-flex;
  align-items:center;
  gap:4px;
  font-size:11px;
  padding:3px 8px;
  border-radius:999px;
  border:1px solid #c7d2fe;
  background:#eef2ff;
  color:#3730a3;
  cursor:pointer;
  margin-left:6px;
}
.ia-pill svg{
  width:12px;
  height:12px;
}

/* bulk bar */
@media (max-width: 960px){
  .page{ padding:0 8px }
  .table-wrap{ border:0; background:transparent; overflow:visible }
  table, thead, tbody, th, td, tr { display:block }
  table{ min-width: 0 }
  thead{ display:none }
  tbody tr{ background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:12px; margin-bottom:12px; }
  tbody td{ border:0; padding:0; }
  td[data-col="img"]{ margin-bottom:10px }
  .kv{ grid-template-columns: max(42%) 1fr }
}

/* Bulk Clave SAT bar */
.bulk-bar{
  margin-top:12px;
  background:linear-gradient(135deg,#f9fafb,#e5f9f4);
  border-radius:14px;
  padding:8px 12px;
  display:flex;
  flex-wrap:wrap;
  align-items:center;
  gap:10px;
  border:1px solid #d1fae5;
}
.bulk-badge{
  font-size:12px;
  font-weight:600;
  color:#047857;
  background:#ecfdf3;
  border-radius:999px;
  padding:4px 10px;
}
.bulk-input{
  display:flex;
  align-items:center;
  gap:6px;
  font-size:13px;
}
.bulk-input input{
  border-radius:999px;
  border:1px solid var(--border);
  padding:7px 10px;
  font-size:13px;
  outline:none;
  width:160px;
}
.bulk-note{
  font-size:11px;
  color:#6b7280;
}
.bulk-btn{
  padding:7px 12px;
  font-size:12px;
}
.status-flash{
  margin-top:10px;
  font-size:12px;
  padding:8px 12px;
  border-radius:999px;
  background:#ecfdf3;
  color:#047857;
  border:1px solid #bbf7d0;
}

/* SweetAlert2 minimal / pro */
.swal-jrt-popup{
  border-radius:18px !important;
  padding:22px 24px !important;
  box-shadow:0 20px 50px rgba(15,23,42,0.25) !important;
}
.swal-jrt-title{
  font-size:20px !important;
  font-weight:700 !important;
  color:#0f172a !important;
}
.swal-jrt-html{
  font-size:14px !important;
  color:#4b5563 !important;
}
.swal-jrt-confirm{
  border-radius:999px !important;
  padding:8px 18px !important;
  font-weight:600 !important;
  background:#2563eb !important;
  color:#fff !important;
  border:0 !important;
}
.swal-jrt-cancel{
  border-radius:999px !important;
  padding:8px 18px !important;
  font-weight:500 !important;
  background:#e5e7eb !important;
  color:#111827 !important;
  border:0 !important;
}
.swal-jrt-icon{
  border-radius:999px !important;
  border-width:2px !important;
}
.swal-jrt-icon.swal2-info{
  border-color:#bfdbfe !important;
  color:#2563eb !important;
}
.swal-jrt-icon.swal2-warning{
  border-color:#fed7aa !important;
  color:#f97316 !important;
}
.swal-jrt-icon.swal2-error{
  border-color:#fecaca !important;
  color:#dc2626 !important;
}
</style>
@endpush

@section('content')
<div class="page">

  <div class="hero">
    <div>
      <h1 class="h4">Productos</h1>
      <div class="subtle">Todos los campos visibles. Nombre, Color y Unidad en negritas.</div>
    </div>

    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center">
      <a href="{{ route('products.create') }}" class="pbtn pbtn-blue">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Nuevo
      </a>

      <a href="{{ route('products.import.form') }}" class="pbtn pbtn-green">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0l-4-4m4 4l4-4"/><path d="M20 21H4a2 2 0 0 1-2-2v-3"/></svg>
        Importar
      </a>

      <form id="searchForm" class="searchbar" onsubmit="return false;">
        <span class="sb-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        </span>
        <input id="liveSearch" class="sb-input" type="text" value="{{ $q ?? '' }}" placeholder="Buscar por cualquier campo…" autocomplete="off">
        <button type="button" class="sb-clear" id="sbClear" aria-label="Limpiar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
      </form>
    </div>
  </div>

  @if(session('status'))
    <div class="status-flash">
      {{ session('status') }}
    </div>
  @endif

  {{-- Barra bulk Clave SAT (form independiente) --}}
  <form id="bulkSatForm" method="POST" action="{{ route('products.bulk-clave-sat') }}">
    @csrf
    <div class="bulk-bar">
      <div class="bulk-badge">
        <span id="bulkSelectedCount">0</span> seleccionados
      </div>

      <div class="bulk-input">
        <span>Clave SAT para aplicar:</span>
        <input type="text" name="clave_sat" id="bulkClaveSat" placeholder="Ej. 01010101">
      </div>

      <button type="submit" class="pbtn pbtn-green bulk-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 5v14M5 12h14"/>
        </svg>
        Aplicar a seleccionados
      </button>

      <div class="bulk-note">
        Consejo: filtra primero (por ejemplo solo lápiz), selecciona todos y aplica la misma clave SAT a ese grupo.
        Afecta solo los productos seleccionados en esta vista.
      </div>
    </div>
  </form>

  <div class="table-wrap">
    <table id="prodTable">
      <thead>
        <tr>
          <th style="width:36px">
            <input type="checkbox" id="selectAll">
          </th>
          <th style="width:110px">Imagen</th>
          <th>Información</th>
          <th class="th-actions" style="width:130px">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach($products as $p)
          @php
            $state = $p->active ? 'activo' : 'inactivo';
            $bag = \Illuminate\Support\Str::of(trim("
              {$p->id} {$p->name} {$p->sku} {$p->supplier_sku} {$p->unit} {$p->weight}
              {$p->cost} {$p->price} {$p->market_price} {$p->bid_price} {$p->dimensions}
              {$p->color} {$p->pieces_per_unit} {$state} {$p->brand} {$p->category}
              {$p->material} {$p->description} {$p->notes} {$p->tags} {$p->image_path} {$p->image_url}
              {$p->clave_sat}
            "))->lower();
            $fmtDate = optional($p->created_at)->format('Y-m-d');
            $val = fn($v,$fallback='—') => (isset($v) && $v!=='' ? $v : $fallback);
          @endphp
          @continue(empty($p->name) && empty($p->sku))

          <tr
            data-id="{{ $p->id }}"
            data-bag="{{ $bag }}"
            data-name="{{ $p->name }}"
            data-sku="{{ $p->sku }}"
            data-brand="{{ $p->brand }}"
            data-category="{{ $p->category }}"
            data-tags="{{ $p->tags }}"
            data-desc="{{ $p->description }}"
          >
            {{-- Checkbox selección múltiple --}}
            <td>
              <input type="checkbox" class="js-row-check" data-id="{{ $p->id }}">
            </td>

            <td data-col="img" data-label="Imagen">
              @php $src = $p->image_src; @endphp
              @if($src)
                <img class="thumb" src="{{ $src }}" alt="Imagen de {{ $p->name }}"
                     onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}';">
              @else
                <img class="thumb" src="{{ asset('images/placeholder.png') }}" alt="Sin imagen">
              @endif
            </td>

            <td>
              <!-- Encabezado -->
              <div class="info-head">
                {{ $val($p->name) }}
              </div>

              <div class="info-sub">
                <span class="badge badge-strong">Unidad: {{ $val($p->unit) }}</span>
                <span class="badge badge-color">Color: {{ $val($p->color) }}</span>
                <span class="badge">SKU: {{ $val($p->sku) }}</span>
                <span class="badge">Marca: {{ $val($p->brand) }}</span>
                <span class="badge">Categoría: {{ $val($p->category) }}</span>
                <span class="badge">{{ $p->active ? 'Activo' : 'Inactivo' }}</span>
              </div>

              <!-- Lista clave:valor -->
              <div class="kv">
                <div class="k">ID</div>               <div class="v">{{ $p->id }}</div>
                <div class="k">Supplier SKU</div>      <div class="v">{{ $val($p->supplier_sku) }}</div>
                <div class="k">Peso</div>              <div class="v">{{ $val($p->weight) }}</div>
                <div class="k">Costo</div>             <div class="v">{{ $p->cost !== null ? '$'.number_format((float)$p->cost,2) : '—' }}</div>
                <div class="k">Precio</div>            <div class="v">{{ $p->price !== null ? '$'.number_format((float)$p->price,2) : '—' }}</div>
                <div class="k">Precio Mercado</div>    <div class="v">{{ $p->market_price !== null ? '$'.number_format((float)$p->market_price,2) : '—' }}</div>
                <div class="k">Precio Licitación</div> <div class="v">{{ $p->bid_price !== null ? '$'.number_format((float)$p->bid_price,2) : '—' }}</div>
                <div class="k">Dimensiones</div>       <div class="v">{{ $val($p->dimensions) }}</div>
                <div class="k">Piezas por unidad</div> <div class="v">{{ $val($p->pieces_per_unit) }}</div>
                <div class="k">Material</div>          <div class="v">{{ $val($p->material) }}</div>
                <div class="k">Clave SAT</div>
                <div class="v">
                  <span class="js-clave-sat-text">{{ $p->clave_sat ?? '—' }}</span>
                  {{-- Botón IA sugerir --}}
                  <button type="button"
                          class="ia-pill js-ia-suggest"
                          data-id="{{ $p->id }}"
                          data-name="{{ $p->name }}"
                          data-desc="{{ $p->description }}"
                          data-category="{{ $p->category }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M12 3l1.9 3.9L18 9l-3.9 1.9L12 15l-1.9-4.1L6 9l4.1-.1L12 3z"/>
                      <path d="M4 19h16"/>
                    </svg>
                    IA sugerir
                  </button>
                </div>
                <div class="k">Descripción</div>       <div class="v">{{ \Illuminate\Support\Str::limit($val($p->description), 220) }}</div>
                <div class="k">Notas</div>             <div class="v">{{ \Illuminate\Support\Str::limit($val($p->notes), 220) }}</div>
                <div class="k">Tags</div>              <div class="v">{{ $val($p->tags) }}</div>
                <div class="k">image_path</div>        <div class="v"><code>{{ $val($p->image_path) }}</code></div>
                <div class="k">image_url</div>         <div class="v">
                  @if($p->image_url)
                    <a href="{{ $p->image_url }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($p->image_url, 70) }}</a>
                  @else
                    —
                  @endif
                </div>
                <div class="k">Creado</div>            <div class="v">{{ $val($fmtDate) }}</div>
              </div>
            </td>

            <td class="t-actions" data-label="Acciones">
              <div style="display:flex;gap:8px;align-items:center">
                <a class="btn-icon blue" title="Editar" href="{{ route('products.edit',$p) }}">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                </a>
                <form method="POST" action="{{ route('products.destroy',$p) }}" class="d-inline js-del">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn-icon red" title="Eliminar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function(){
  // ===== Buscador en vivo con ranking =====
  const input    = document.getElementById('liveSearch');
  const clearBtn = document.getElementById('sbClear');

  if(!input){
    const sb = document.createElement('input');
    sb.id='liveSearch'; sb.placeholder='Buscar...'; sb.style.cssText='display:none';
    document.body.appendChild(sb);
  }

  const tbody    = document.querySelector('#prodTable tbody');
  const rows     = Array.from(tbody.querySelectorAll('tr'));
  rows.forEach((r,i)=> r.dataset._idx = String(i));

  const norm = s => (s||'').toString()
    .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
    .toLowerCase().trim();

  function scoreField(field, q){
    if(!field || !q) return 0;
    if(field === q)         return 1000;
    if(field.startsWith(q)) return 600;
    const pos = field.indexOf(q);
    if(pos >= 0)            return 350 + Math.max(0, 100 - pos);
    return 0;
  }

  function rowScore(r, q){
    const name = norm(r.dataset.name);
    const sku  = norm(r.dataset.sku);
    const brand= norm(r.dataset.brand);
    const cat  = norm(r.dataset.category);
    const tags = norm(r.dataset.tags);
    const desc = norm(r.dataset.desc);
    let s = 0;
    s += scoreField(name, q) * 5;
    s += scoreField(sku,  q) * 4;
    s += scoreField(brand,q) * 2;
    s += scoreField(cat,  q) * 2;
    s += scoreField(tags, q) * 1;
    s += scoreField(desc, q) * 1;
    return s;
  }

  function applyFilter(){
    const inputEl = document.getElementById('liveSearch');
    const q = norm(inputEl?.value);
    if(clearBtn) clearBtn.style.visibility = q ? 'visible' : 'hidden';

    if(!q){
      const orig = [...rows].sort((a,b)=> (+a.dataset._idx) - (+b.dataset._idx));
      orig.forEach(r => { r.style.display=''; tbody.appendChild(r); });
      return;
    }

    const ranked = rows.map(r => {
      const bag = norm(r.dataset.bag || '');
      const match = bag.includes(q);
      const s = rowScore(r, q);
      if(!match && s <= 0){
        r.style.display = 'none';
      } else {
        r.style.display = '';
      }
      return { r, s };
    }).filter(x => x.r.style.display !== 'none');

    ranked.sort((a,b) => {
      const diff = b.s - a.s;
      if(diff) return diff;
      const an = norm(a.r.dataset.name), bn = norm(b.r.dataset.name);
      return an.localeCompare(bn);
    });

    ranked.forEach(x => tbody.appendChild(x.r));
  }

  document.getElementById('liveSearch')?.addEventListener('input', applyFilter);
  document.getElementById('sbClear')?.addEventListener('click', ()=>{
    const i=document.getElementById('liveSearch');
    if(i){ i.value=''; applyFilter(); i.focus(); }
  });
  applyFilter();

  // ===== Confirmación eliminar (SweetAlert pro) =====
  document.querySelectorAll('form.js-del').forEach(f=>{
    f.addEventListener('submit', function(e){
      e.preventDefault();
      Swal.fire({
        icon:'warning',
        title:'¿Eliminar producto?',
        html:'Esta acción no se puede deshacer.',
        showCancelButton:true,
        confirmButtonText:'Sí, eliminar',
        cancelButtonText:'Cancelar',
        buttonsStyling:false,
        customClass:{
          popup:'swal-jrt-popup',
          title:'swal-jrt-title',
          htmlContainer:'swal-jrt-html',
          confirmButton:'swal-jrt-confirm',
          cancelButton:'swal-jrt-cancel',
          icon:'swal-jrt-icon'
        }
      }).then(res=>{ if(res.isConfirmed) this.submit(); });
    });
  });

  // ===== Bulk Clave SAT: selección múltiple + validaciones =====
  const selectAll   = document.getElementById('selectAll');
  const rowChecks   = Array.from(document.querySelectorAll('.js-row-check'));
  const countEl     = document.getElementById('bulkSelectedCount');
  const bulkForm    = document.getElementById('bulkSatForm');
  const claveInput  = document.getElementById('bulkClaveSat');

  function updateCount(){
    const selected = rowChecks.filter(cb => cb.checked).length;
    if (countEl) countEl.textContent = selected;
  }

  if (selectAll) {
    selectAll.addEventListener('change', () => {
      rowChecks.forEach(cb => cb.checked = selectAll.checked);
      updateCount();
    });
  }

  rowChecks.forEach(cb => {
    cb.addEventListener('change', () => {
      if (!cb.checked && selectAll && selectAll.checked) {
        selectAll.checked = false;
      }
      updateCount();
    });
  });

  if (bulkForm) {
    bulkForm.addEventListener('submit', (e) => {
      e.preventDefault();

      const selected = rowChecks.filter(cb => cb.checked).map(cb => cb.dataset.id);
      if (!selected.length) {
        Swal.fire({
          icon:'warning',
          title:'Selecciona productos',
          html:'Marca al menos un producto para aplicar la clave SAT.',
          buttonsStyling:false,
          customClass:{
            popup:'swal-jrt-popup',
            title:'swal-jrt-title',
            htmlContainer:'swal-jrt-html',
            confirmButton:'swal-jrt-confirm',
            icon:'swal-jrt-icon'
          }
        });
        return;
      }

      if (!claveInput.value.trim()) {
        Swal.fire({
          icon:'info',
          title:'Falta la clave SAT',
          html:'Escribe la clave SAT que deseas aplicar.',
          buttonsStyling:false,
          customClass:{
            popup:'swal-jrt-popup',
            title:'swal-jrt-title',
            htmlContainer:'swal-jrt-html',
            confirmButton:'swal-jrt-confirm',
            icon:'swal-jrt-icon'
          }
        }).then(()=> claveInput.focus());
        return;
      }

      // Limpia ids ocultos anteriores
      Array.from(bulkForm.querySelectorAll('.js-hidden-id')).forEach(el => el.remove());

      // Crea inputs hidden con los IDs seleccionados
      selected.forEach(id => {
        const h = document.createElement('input');
        h.type = 'hidden';
        h.name = 'product_ids[]';
        h.value = id;
        h.classList.add('js-hidden-id');
        bulkForm.appendChild(h);
      });

      bulkForm.submit();
    });
  }

  updateCount();

  // ===== IA sugerir clave SAT (por producto, igual que el que te funciona) =====
  const iaButtons = document.querySelectorAll('.js-ia-suggest');

  function getCsrf() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  iaButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const name = btn.dataset.name || '';
      const desc = btn.dataset.desc || '';
      const cat  = btn.dataset.category || '';

      if (!name && !desc) {
        Swal.fire({
          icon:'info',
          title:'Sin datos',
          html:'Este producto no tiene nombre ni descripción para sugerir clave SAT.',
          buttonsStyling:false,
          customClass:{
            popup:'swal-jrt-popup',
            title:'swal-jrt-title',
            htmlContainer:'swal-jrt-html',
            confirmButton:'swal-jrt-confirm',
            icon:'swal-jrt-icon'
          }
        });
        return;
      }

      Swal.fire({
        title: 'Consultando IA…',
        html: 'Generando sugerencia de clave SAT según nombre y descripción.',
        allowOutsideClick:false,
        didOpen: () => { Swal.showLoading(); },
        showConfirmButton:false,
        customClass:{
          popup:'swal-jrt-popup',
          title:'swal-jrt-title',
          htmlContainer:'swal-jrt-html'
        }
      });

      // ⚠️ IMPORTANTE: payload PLANO, como en tu versión que sí funciona
      fetch("{{ route('products.ai-suggest-clave-sat') }}", {
        method: "POST",
        headers: {
          "Content-Type":"application/json",
          "Accept":"application/json",
          "X-CSRF-TOKEN": getCsrf()
        },
        body: JSON.stringify({
          name: name,
          description: desc,
          category: cat
        })
      })
      .then(r => r.json())
      .then(data => {
        if (!data || !data.suggestion) {
          throw new Error(data.message || 'La IA no pudo sugerir una clave SAT.');
        }

        const suggestion = data.suggestion;

        Swal.fire({
          icon:'info',
          title:'Sugerencia de clave SAT',
          html: `
            <p style="margin-bottom:4px;"><strong>Producto:</strong> ${name || '(sin nombre)'}</p>
            <p style="margin-bottom:8px;"><strong>Descripción:</strong> ${desc ? desc.substring(0,180) + (desc.length>180?'…':'') : '(sin descripción)'}</p>
            <p style="margin-top:10px">La IA sugiere usar la clave SAT:</p>
            <p style="font-size:22px;font-weight:700;letter-spacing:.10em;margin:4px 0 8px;">${suggestion}</p>
            <p style="font-size:12px;margin-top:0;color:#6b7280;">
              Esta clave <strong>no se guarda aún</strong>.<br>
              Puedes usarla en la barra bulk para aplicarla a uno o varios productos seleccionados.
            </p>
          `,
          showCancelButton:true,
          confirmButtonText:'Usar en barra bulk',
          cancelButtonText:'Cerrar',
          buttonsStyling:false,
          customClass:{
            popup:'swal-jrt-popup',
            title:'swal-jrt-title',
            htmlContainer:'swal-jrt-html',
            confirmButton:'swal-jrt-confirm',
            cancelButton:'swal-jrt-cancel',
            icon:'swal-jrt-icon'
          }
        }).then(res => {
          if(res.isConfirmed && claveInput){
            claveInput.value = suggestion;
            claveInput.focus();
          }
        });
      })
      .catch(err => {
        Swal.fire({
          icon:'error',
          title:'Error con la IA',
          html: err.message || 'No se pudo obtener una sugerencia de la IA.',
          buttonsStyling:false,
          customClass:{
            popup:'swal-jrt-popup',
            title:'swal-jrt-title',
            htmlContainer:'swal-jrt-html',
            confirmButton:'swal-jrt-confirm',
            icon:'swal-jrt-icon'
          }
        });
      });
    });
  });

})();
</script>
@endpush
