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
.pbtn-gray{ color:var(--btn-gray); background:var(--btn-gray-soft); border-color:#d0d7e2; }
.pbtn-gray:hover{ background:#e2e8f0; border-color:#cbd5e1; }
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

/* Tabla */
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

/* Imagen miniatura */
td.img-cell, th.img-cell{ width:110px; max-width:110px; }
.thumbbox{
  width:72px; height:56px;
  border-radius:12px;
  border:1px solid var(--border);
  background:#f1f5f9;
  overflow:hidden;
  display:grid;
  place-items:center;
}
.thumbbox img{ width:100%; height:100%; object-fit:cover; display:block; }

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

.ia-pill{
  display:inline-flex; align-items:center; gap:4px;
  font-size:11px; padding:3px 8px;
  border-radius:999px; border:1px solid #c7d2fe;
  background:#eef2ff; color:#3730a3; cursor:pointer; margin-left:6px;
}
.ia-pill svg{ width:12px; height:12px; }

/* Barra bulk */
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
.bulk-input{ display:flex; align-items:center; gap:6px; font-size:13px; }
.bulk-input input{
  border-radius:999px; border:1px solid var(--border);
  padding:7px 10px; font-size:13px; outline:none; width:160px;
}
.bulk-note{ font-size:11px; color:#6b7280; }
.bulk-btn{ padding:7px 12px; font-size:12px; }

.status-flash{
  margin-top:10px;
  font-size:12px;
  padding:8px 12px;
  border-radius:999px;
  background:#ecfdf3;
  color:#047857;
  border:1px solid #bbf7d0;
}

/* Highlight “tipo Google” */
mark.hl{
  background:#fff7ed;
  border:1px solid #fed7aa;
  padding:0 .16em;
  border-radius:6px;
  color:#7c2d12;
}

/* Row estados ajax */
.ajax-row{
  color:#64748b;
  font-size:13px;
  padding:18px 12px !important;
  text-align:center;
}
.ajax-topnote{
  margin-top:8px;
  font-size:12px;
  color:#64748b;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:8px;
  flex-wrap:wrap;
}
.ajax-pill{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:6px 10px;
  border-radius:999px;
  background:#f1f5f9;
  border:1px solid #e5e7eb;
  color:#334155;
  font-weight:700;
}
.ajax-pill small{ font-weight:600; color:#64748b; }
</style>
@endpush

@section('content')
@php $q = $q ?? request('q',''); @endphp

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

      <a href="{{ route('products.export.pdf', ['q' => $q]) }}" class="pbtn pbtn-gray" title="Descargar PDF" target="_blank">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/>
          <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
          <path d="M8 15h2.5a1.5 1.5 0 0 0 0-3H8v3z"/>
          <path d="M13 12v6"/>
          <path d="M13 15h1.5a1.5 1.5 0 0 0 0-3H13"/>
          <path d="M17 18v-6h2a1.5 1.5 0 0 1 0 3h-2"/>
        </svg>
        PDF
      </a>

      <a href="{{ route('products.export.excel', ['q' => $q]) }}" class="pbtn pbtn-gray" title="Descargar Excel">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
          <path d="M9 8l6 8M15 8l-6 8"/>
        </svg>
        Excel
      </a>

      {{-- Buscador (AJAX en la MISMA TABLA, sin recargar) --}}
      <form class="searchbar" id="searchForm" onsubmit="return false;">
        <span class="sb-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        </span>
        <input id="liveSearch" class="sb-input" type="text" value="{{ $q }}" placeholder="Buscar por cualquier campo…" autocomplete="off">
        <button type="button" class="sb-clear" id="sbClear" aria-label="Limpiar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
      </form>
    </div>
  </div>

  <div id="ajaxNote" class="ajax-topnote" style="display:none">
    <div class="ajax-pill">
      <span id="ajaxNoteText">Buscando…</span>
      <small id="ajaxNoteSub"></small>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap">
      <button type="button" class="pbtn pbtn-gray" id="btnRestore">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9"/><path d="M3 4v8h8"/></svg>
        Volver a la página
      </button>
      <button type="button" class="pbtn pbtn-gray" id="btnOpenFull">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 21l-4.3-4.3"/><circle cx="11" cy="11" r="7"/></svg>
        Buscar en vista completa (Enter)
      </button>
    </div>
  </div>

  @if(session('status'))
    <div class="status-flash">{{ session('status') }}</div>
  @endif

  {{-- Barra bulk Clave SAT --}}
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
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Aplicar a seleccionados
      </button>

      <div class="bulk-note">
        Consejo: filtra primero (por ejemplo solo lápiz), selecciona todos y aplica la misma clave SAT a ese grupo.
        Afecta solo los productos seleccionados en esta vista.
      </div>
    </div>
  </form>

  <div class="table-wrap">
    <table id="prodTable" class="products-table">
      <thead>
        <tr>
          <th style="width:36px">
            <input type="checkbox" id="selectAll">
          </th>
          <th class="img-cell">Imagen</th>
          <th>Información</th>
          <th class="th-actions" style="width:130px">Acciones</th>
        </tr>
      </thead>
      <tbody id="prodTbody">
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
            <td>
              <input type="checkbox" class="js-row-check" data-id="{{ $p->id }}">
            </td>

            <td class="img-cell" data-col="img" data-label="Imagen">
              @php $src = $p->image_src; @endphp
              <div class="thumbbox">
                @if($src)
                  <img
                    src="{{ $src }}"
                    alt="Imagen de {{ $p->name }}"
                    loading="lazy"
                    onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}';"
                  >
                @else
                  <img
                    src="{{ asset('images/placeholder.png') }}"
                    alt="Sin imagen"
                    loading="lazy"
                  >
                @endif
              </div>
            </td>

            <td>
              <div class="info-head">{{ $val($p->name) }}</div>

              <div class="info-sub">
                <span class="badge badge-strong">Unidad: {{ $val($p->unit) }}</span>
                <span class="badge badge-color">Color: {{ $val($p->color) }}</span>
                <span class="badge">SKU: {{ $val($p->sku) }}</span>
                <span class="badge">Marca: {{ $val($p->brand) }}</span>
                <span class="badge">Categoría: {{ $val($p->category) }}</span>
                <span class="badge">{{ $p->active ? 'Activo' : 'Inactivo' }}</span>
              </div>

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

  {{-- Paginación (se oculta cuando estás en búsqueda AJAX) --}}
  <div id="paginationWrap">
    @if($products instanceof \Illuminate\Pagination\LengthAwarePaginator && $products->hasPages())
      <div class="mt-3">
        {{ $products->appends(['q' => $q])->onEachSide(1)->links('vendor.pagination.pastel') }}
      </div>
    @endif
  </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function(){
  const input    = document.getElementById('liveSearch');
  const clearBtn = document.getElementById('sbClear');
  const tbody    = document.getElementById('prodTbody');
  const pagWrap  = document.getElementById('paginationWrap');

  const note     = document.getElementById('ajaxNote');
  const noteText = document.getElementById('ajaxNoteText');
  const noteSub  = document.getElementById('ajaxNoteSub');
  const btnRestore = document.getElementById('btnRestore');
  const btnOpenFull = document.getElementById('btnOpenFull');

  const ENDPOINT = "{{ route('products.ajax-table') }}";
  const PLACEHOLDER = "{{ asset('images/placeholder.png') }}";

  // Guardar HTML original (para volver SIN recargar)
  const originalHTML = tbody ? tbody.innerHTML : '';
  let isAjaxMode = false;

  // Debounce + Abort para que sea rápido y no “se trabe”
  let timer = null;
  let ctrl  = null;

  // Cache simple
  const cache = new Map(); // q -> items

  // Mantener seleccionados (bulk) aunque cambie la tabla
  const selectedIds = new Set();

  const norm = (s) => (s||'').toString().trim();
  const esc = (s) => (s||'').toString()
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');

  function setClearVisibility(){
    const q = norm(input?.value);
    if (clearBtn) clearBtn.style.visibility = q ? 'visible' : 'hidden';
  }

  function showNote(msg, sub=''){
    if(!note) return;
    note.style.display = '';
    noteText.textContent = msg || '';
    noteSub.textContent = sub || '';
  }
  function hideNote(){
    if(!note) return;
    note.style.display = 'none';
    noteText.textContent = '';
    noteSub.textContent = '';
  }

  function setAjaxMode(on){
    isAjaxMode = !!on;
    if (pagWrap) pagWrap.style.display = on ? 'none' : '';
    if (!on) hideNote();
  }

  function highlight(text, q){
    const t = esc(text || '');
    const query = norm(q);
    if(!query) return t;
    const re = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'ig');
    return t.replace(re, '<mark class="hl">$1</mark>');
  }

  function money(v){
    if (v === null || v === undefined || v === '') return '—';
    const n = Number(v);
    if (Number.isNaN(n)) return '—';
    return '$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function buildRow(it, q){
    const id = it.id;
    const checked = selectedIds.has(String(id)) ? 'checked' : '';
    const imgSrc = it.image_src ? esc(it.image_src) : PLACEHOLDER;

    const name = highlight(it.name || '—', q);

    const unit = highlight(it.unit || '—', q);
    const color = highlight(it.color || '—', q);
    const sku = highlight(it.sku || '—', q);
    const brand = highlight(it.brand || '—', q);
    const category = highlight(it.category || '—', q);

    const supplierSku = highlight(it.supplier_sku || '—', q);
    const weight = highlight(it.weight || '—', q);
    const dimensions = highlight(it.dimensions || '—', q);
    const ppu = highlight(it.pieces_per_unit || '—', q);
    const material = highlight(it.material || '—', q);
    const claveSat = highlight(it.clave_sat || '—', q);

    const desc = highlight((it.description || '').toString().slice(0,220) + ((it.description||'').length>220 ? '…' : ''), q);
    const notes = highlight((it.notes || '').toString().slice(0,220) + ((it.notes||'').length>220 ? '…' : ''), q);
    const tags = highlight(it.tags || '—', q);

    const statusTxt = it.active ? 'Activo' : 'Inactivo';

    return `
      <tr data-id="${id}">
        <td>
          <input type="checkbox" class="js-row-check" data-id="${id}" ${checked}>
        </td>

        <td class="img-cell" data-col="img" data-label="Imagen">
          <div class="thumbbox">
            <img src="${imgSrc}" alt="Imagen" loading="lazy"
                 onerror="this.onerror=null;this.src='${PLACEHOLDER}';">
          </div>
        </td>

        <td>
          <div class="info-head">${name}</div>

          <div class="info-sub">
            <span class="badge badge-strong">Unidad: ${unit}</span>
            <span class="badge badge-color">Color: ${color}</span>
            <span class="badge">SKU: ${sku}</span>
            <span class="badge">Marca: ${brand}</span>
            <span class="badge">Categoría: ${category}</span>
            <span class="badge">${statusTxt}</span>
          </div>

          <div class="kv">
            <div class="k">ID</div>               <div class="v">${id}</div>
            <div class="k">Supplier SKU</div>     <div class="v">${supplierSku}</div>
            <div class="k">Peso</div>             <div class="v">${weight}</div>
            <div class="k">Costo</div>            <div class="v">${money(it.cost)}</div>
            <div class="k">Precio</div>           <div class="v">${money(it.price)}</div>
            <div class="k">Precio Mercado</div>   <div class="v">${money(it.market_price)}</div>
            <div class="k">Precio Licitación</div><div class="v">${money(it.bid_price)}</div>
            <div class="k">Dimensiones</div>      <div class="v">${dimensions}</div>
            <div class="k">Piezas por unidad</div><div class="v">${ppu}</div>
            <div class="k">Material</div>         <div class="v">${material}</div>

            <div class="k">Clave SAT</div>
            <div class="v">
              <span class="js-clave-sat-text">${claveSat}</span>
              <button type="button"
                      class="ia-pill js-ia-suggest"
                      data-id="${id}"
                      data-name="${esc(it.name||'')}"
                      data-desc="${esc(it.description||'')}"
                      data-category="${esc(it.category||'')}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 3l1.9 3.9L18 9l-3.9 1.9L12 15l-1.9-4.1L6 9l4.1-.1L12 3z"/>
                  <path d="M4 19h16"/>
                </svg>
                IA sugerir
              </button>
            </div>

            <div class="k">Descripción</div>      <div class="v">${desc || '—'}</div>
            <div class="k">Notas</div>            <div class="v">${notes || '—'}</div>
            <div class="k">Tags</div>             <div class="v">${tags}</div>

            <div class="k">image_path</div>       <div class="v"><code>${esc(it.image_path||'—')}</code></div>
            <div class="k">image_url</div>        <div class="v">${it.image_url ? `<a href="${esc(it.image_url)}" target="_blank" rel="noopener">${esc((it.image_url||'').slice(0,70))}</a>` : '—'}</div>
            <div class="k">Creado</div>           <div class="v">${esc(it.created_at || '—')}</div>
          </div>
        </td>

        <td class="t-actions" data-label="Acciones">
          <div style="display:flex;gap:8px;align-items:center">
            <a class="btn-icon blue" title="Editar" href="${esc(it.edit_url)}">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
              </svg>
            </a>

            <form method="POST" action="${esc(it.delete_url)}" class="d-inline js-del">
              <input type="hidden" name="_token" value="{{ csrf_token() }}">
              <input type="hidden" name="_method" value="DELETE">
              <button type="submit" class="btn-icon red" title="Eliminar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"/>
                  <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                  <line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>
                </svg>
              </button>
            </form>
          </div>
        </td>
      </tr>
    `;
  }

  function renderLoading(){
    if(!tbody) return;
    tbody.innerHTML = `<tr><td colspan="4" class="ajax-row">Buscando…</td></tr>`;
  }

  function renderEmpty(q){
    if(!tbody) return;
    tbody.innerHTML = `<tr><td colspan="4" class="ajax-row">No hay ninguna coincidencia para “${esc(q)}”.</td></tr>`;
  }

  function attachDynamicHandlers(){
    // Re-enganchar eliminar (porque reemplazamos tbody)
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
      }, { once:true });
    });

    // Re-enganchar checks (bulk)
    document.querySelectorAll('.js-row-check').forEach(cb=>{
      cb.addEventListener('change', () => {
        const id = String(cb.dataset.id || '');
        if(!id) return;
        if (cb.checked) selectedIds.add(id);
        else selectedIds.delete(id);
        updateCountFromSet();
      });
    });

    // Re-enganchar IA (si la usas)
    attachIaButtons();
  }

  // ===== Bulk (mantener contador) =====
  const selectAll = document.getElementById('selectAll');
  const countEl   = document.getElementById('bulkSelectedCount');
  const bulkForm  = document.getElementById('bulkSatForm');
  const claveInput= document.getElementById('bulkClaveSat');

  function updateCountFromSet(){
    if (countEl) countEl.textContent = String(selectedIds.size);
  }

  selectAll?.addEventListener('change', () => {
    const checks = Array.from(document.querySelectorAll('.js-row-check'));
    checks.forEach(cb => {
      cb.checked = selectAll.checked;
      const id = String(cb.dataset.id || '');
      if(!id) return;
      if (cb.checked) selectedIds.add(id);
      else selectedIds.delete(id);
    });
    updateCountFromSet();
  });

  bulkForm?.addEventListener('submit', (e) => {
    e.preventDefault();

    if (selectedIds.size === 0) {
      Swal.fire({
        icon:'warning',
        title:'Selecciona productos',
        html:'Marca al menos un producto para aplicar la clave SAT.',
        buttonsStyling:false,
        customClass:{ popup:'swal-jrt-popup', title:'swal-jrt-title', htmlContainer:'swal-jrt-html', confirmButton:'swal-jrt-confirm', icon:'swal-jrt-icon' }
      });
      return;
    }

    if (!claveInput.value.trim()) {
      Swal.fire({
        icon:'info',
        title:'Falta la clave SAT',
        html:'Escribe la clave SAT que deseas aplicar.',
        buttonsStyling:false,
        customClass:{ popup:'swal-jrt-popup', title:'swal-jrt-title', htmlContainer:'swal-jrt-html', confirmButton:'swal-jrt-confirm', icon:'swal-jrt-icon' }
      }).then(()=> claveInput.focus());
      return;
    }

    Array.from(bulkForm.querySelectorAll('.js-hidden-id')).forEach(el => el.remove());

    Array.from(selectedIds).forEach(id => {
      const h = document.createElement('input');
      h.type = 'hidden';
      h.name = 'product_ids[]';
      h.value = id;
      h.classList.add('js-hidden-id');
      bulkForm.appendChild(h);
    });

    bulkForm.submit();
  });

  // ===== IA sugerir (igual que lo tenías, pero re-usable) =====
  function getCsrf() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function attachIaButtons(){
    document.querySelectorAll('.js-ia-suggest').forEach(btn => {
      if (btn.dataset._binded === '1') return;
      btn.dataset._binded = '1';

      btn.addEventListener('click', () => {
        const name = btn.dataset.name || '';
        const desc = btn.dataset.desc || '';
        const cat  = btn.dataset.category || '';

        Swal.fire({
          title: 'Consultando IA…',
          html: 'Generando sugerencia de clave SAT según nombre y descripción.',
          allowOutsideClick:false,
          didOpen: () => { Swal.showLoading(); },
          showConfirmButton:false,
          customClass:{ popup:'swal-jrt-popup', title:'swal-jrt-title', htmlContainer:'swal-jrt-html' }
        });

        fetch("{{ route('products.ai-suggest-clave-sat') }}", {
          method: "POST",
          headers: {
            "Content-Type":"application/json",
            "Accept":"application/json",
            "X-CSRF-TOKEN": getCsrf()
          },
          body: JSON.stringify({ name, description: desc, category: cat })
        })
        .then(r => r.json())
        .then(data => {
          if (!data || !data.suggestion) throw new Error(data.message || 'La IA no pudo sugerir una clave SAT.');
          const suggestion = data.suggestion;

          Swal.fire({
            icon:'info',
            title:'Sugerencia de clave SAT',
            html: `
              <p style="margin-bottom:4px;"><strong>Producto:</strong> ${esc(name || '(sin nombre)')}</p>
              <p style="margin-bottom:8px;"><strong>Descripción:</strong> ${esc(desc ? desc.substring(0,180) + (desc.length>180?'…':'') : '(sin descripción)')}</p>
              <p style="margin-top:10px">La IA sugiere usar la clave SAT:</p>
              <p style="font-size:22px;font-weight:700;letter-spacing:.10em;margin:4px 0 8px;">${esc(suggestion)}</p>
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
            customClass:{ popup:'swal-jrt-popup', title:'swal-jrt-title', htmlContainer:'swal-jrt-html', confirmButton:'swal-jrt-confirm', icon:'swal-jrt-icon' }
          });
        });
      });
    });
  }

  // Engancha handlers iniciales (HTML original)
  attachIaButtons();
  document.querySelectorAll('.js-row-check').forEach(cb=>{
    cb.addEventListener('change', () => {
      const id = String(cb.dataset.id || '');
      if(!id) return;
      if (cb.checked) selectedIds.add(id);
      else selectedIds.delete(id);
      updateCountFromSet();
    });
  });
  updateCountFromSet();

  async function ajaxSearch(q){
    const query = norm(q);
    if (query.length < 2) return null;

    if (cache.has(query)) return cache.get(query);

    if (ctrl) ctrl.abort();
    ctrl = new AbortController();

    const url = new URL(ENDPOINT, window.location.origin);
    url.searchParams.set('q', query);
    url.searchParams.set('limit', '60');

    const res = await fetch(url.toString(), { signal: ctrl.signal, headers: { 'Accept':'application/json' } });
    if (!res.ok) throw new Error('No se pudo buscar.');
    const data = await res.json();

    cache.set(query, data);
    return data;
  }

  function doSearch(){
    const q = norm(input?.value);
    setClearVisibility();

    clearTimeout(timer);

    // si borró: volver
    if (!q) {
      if (isAjaxMode) restoreOriginal();
      return;
    }

    // no buscar con 1 letra (UX + performance)
    if (q.length < 2) {
      setAjaxMode(true);
      showNote('Escribe al menos 2 letras', 'para buscar rápido sin recargar');
      if (tbody) tbody.innerHTML = `<tr><td colspan="4" class="ajax-row">Escribe al menos 2 letras para buscar.</td></tr>`;
      return;
    }

    timer = setTimeout(async () => {
      setAjaxMode(true);
      showNote('Buscando…', `“${q}”`);
      renderLoading();

      try {
        const data = await ajaxSearch(q);
        if (!data || !data.items) {
          renderEmpty(q);
          showNote('Sin coincidencias', `“${q}”`);
          return;
        }

        if (data.items.length === 0) {
          renderEmpty(q);
          showNote('Sin coincidencias', `“${q}”`);
          attachDynamicHandlers();
          return;
        }

        // Render tabla
        tbody.innerHTML = data.items.map(it => buildRow(it, q)).join('');
        showNote(`Mostrando ${data.items.length} resultado(s)`, `“${q}”`);

        // SelectAll se desactiva al cambiar results (evita confusión)
        if (selectAll) selectAll.checked = false;

        attachDynamicHandlers();
        updateCountFromSet();
      } catch (err) {
        if (err && err.name === 'AbortError') return;
        renderEmpty(q);
        showNote('Sin coincidencias', `“${q}”`);
      }
    }, 260); // debounce pro
  }

  function restoreOriginal(){
    if (!tbody) return;
    tbody.innerHTML = originalHTML;
    setAjaxMode(false);
    if (selectAll) selectAll.checked = false;

    // reenganchar checks/IA sobre HTML original
    document.querySelectorAll('.js-row-check').forEach(cb=>{
      cb.addEventListener('change', () => {
        const id = String(cb.dataset.id || '');
        if(!id) return;
        if (cb.checked) selectedIds.add(id);
        else selectedIds.delete(id);
        updateCountFromSet();
      });
    });
    attachIaButtons();
    updateCountFromSet();
  }

  input?.addEventListener('input', doSearch);
  input?.addEventListener('focus', doSearch);

  input?.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      e.preventDefault();
      if (input) input.value = '';
      setClearVisibility();
      restoreOriginal();
    }
    if (e.key === 'Enter') {
      e.preventDefault();
      const q = norm(input.value);
      if (!q) return;
      const url = new URL(window.location.href);
      url.searchParams.set('q', q);
      url.searchParams.delete('page');
      window.location.href = url.toString();
    }
  });

  clearBtn?.addEventListener('click', () => {
    if (!input) return;
    input.value = '';
    setClearVisibility();
    restoreOriginal();
    input.focus();
  });

  btnRestore?.addEventListener('click', () => {
    restoreOriginal();
  });

  btnOpenFull?.addEventListener('click', () => {
    const q = norm(input?.value);
    if(!q) return;
    const url = new URL(window.location.href);
    url.searchParams.set('q', q);
    url.searchParams.delete('page');
    window.location.href = url.toString();
  });

  setClearVisibility();

})();
</script>
@endpush
