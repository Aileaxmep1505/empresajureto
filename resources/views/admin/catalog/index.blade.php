@extends('layouts.app')
@section('title','Productos Web')

@push('styles')
<style>
  :root{
    --ink:#0f172a; --muted:#64748b; --bg:#f7fafc;
    --line:#e8eef6; --surface:#ffffff;
    --shadow:0 12px 30px rgba(13, 23, 38, .06);
    --r:16px;

    /* ✅ nuevo acento (verde/menta) */
    --acc:#34d399;          /* emerald */
    --acc-ink:#065f46;
    --acc-soft:rgba(52,211,153,.16);
    --acc-ring:rgba(52,211,153,.28);

    /* degradado suave */
    --g1:rgba(52,211,153,.14);
    --g2:rgba(251,191,36,.10);   /* ámbar muy suave */
    --g3:rgba(148,163,184,.10);  /* gris */
  }

  html,body{background:var(--bg)}
  .wrap{max-width:1200px; margin-inline:auto; padding:0 14px}

  .card{
    background:var(--surface);
    border:1px solid var(--line);
    border-radius:var(--r);
    box-shadow:var(--shadow);
  }

  /* ===== Header ===== */
  .head{
    display:flex; gap:14px; align-items:flex-start; justify-content:space-between;
    flex-wrap:wrap; margin:14px 0 10px;
  }
  .title{font-weight:900; color:var(--ink); letter-spacing:-.02em; margin:0}
  .muted{color:var(--muted)}
  .subtxt{margin-top:6px;font-size:.92rem;max-width:70ch}

  /* ===== Botón pastel (como pediste) ===== */
  .btn{
    display:inline-flex; align-items:center; justify-content:center; gap:10px;
    border:1px solid transparent;
    cursor:pointer; text-decoration:none;
    font-weight:800;
    border-radius:14px;
    padding:10px 14px;
    transition:transform .12s ease, box-shadow .12s ease, background .12s ease, color .12s ease, border-color .12s ease;
    box-shadow:0 10px 22px rgba(15,23,42,.06);
    user-select:none;
    background:var(--acc-soft);
    color:var(--acc-ink);
    border-color:var(--acc-ring);
  }
  .btn:hover{
    transform:translateY(-1px);
    background:#fff;
    color:#111827;
    border-color:var(--line);
    box-shadow:0 14px 28px rgba(15,23,42,.08);
  }
  .btn:active{ transform:translateY(0); box-shadow:0 10px 22px rgba(15,23,42,.06); }
  .ico{ width:18px; height:18px; display:inline-block; }
  .ico svg{ width:18px; height:18px; display:block; }

  /* ===== Filtros minimalistas (sin botón aplicar) ===== */
  .filters{
    margin-top:12px;
    padding:12px;
    border-radius:18px;
    border:1px solid rgba(232,238,246,.9);
    background:
      radial-gradient(900px 140px at 12% 0%, var(--g1), transparent 62%),
      radial-gradient(860px 160px at 88% 0%, var(--g2), transparent 60%),
      radial-gradient(520px 120px at 55% 0%, var(--g3), transparent 60%),
      #ffffff;
    box-shadow:0 18px 44px rgba(15,23,42,.08);
  }

  .filters-row{
    display:flex;
    gap:12px;
    align-items:center;
    justify-content:space-between;
    flex-wrap:wrap;
  }

  /* Search (✅ letra normal, no bold) */
  .search{
    display:flex; align-items:center; gap:10px;
    flex:1;
    min-width:min(92vw, 560px);
    background:#fff;
    border:1px solid rgba(232,238,246,.95);
    border-radius:999px;
    padding:10px 12px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.9), 0 10px 18px rgba(15,23,42,.05);
    transition:border-color .14s ease, box-shadow .14s ease, transform .14s ease;
  }
  .search:focus-within{
    border-color:var(--acc-ring);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.95), 0 14px 26px rgba(52,211,153,.14);
    transform: translateY(-1px);
  }
  .search .sico{ color:#94a3b8; width:22px; display:grid; place-items:center; }
  .search input{
    border:0; outline:0; background:transparent;
    width:100%;
    color:var(--ink);
    font-weight:500; /* ✅ normal */
  }

  /* Tabs (Todos / Publicado / Borrador / Oculto) */
  .tabs{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:6px;
    border-radius:999px;
    border:1px solid rgba(232,238,246,.95);
    background:rgba(255,255,255,.86);
    box-shadow:0 10px 18px rgba(15,23,42,.04);
    user-select:none;
  }
  .tab{
    appearance:none;
    border:0;
    background:transparent;
    padding:9px 12px;
    border-radius:999px;
    cursor:pointer;
    font-weight:700;
    color:#334155;
    transition: background .12s ease, color .12s ease, transform .12s ease, box-shadow .12s ease;
    white-space:nowrap;
  }
  .tab:hover{
    background:rgba(52,211,153,.10);
    transform: translateY(-1px);
    box-shadow:0 10px 18px rgba(15,23,42,.05);
  }
  .tab.is-active{
    background:var(--acc-soft);
    color:var(--acc-ink);
    box-shadow:0 12px 22px rgba(52,211,153,.12);
  }

  /* Destacados (chip) */
  .chip{
    display:inline-flex; align-items:center; gap:10px;
    padding:10px 14px;
    border-radius:999px;
    border:1px solid rgba(232,238,246,.95);
    background:rgba(255,255,255,.86);
    box-shadow:0 10px 18px rgba(15,23,42,.04);
    font-weight:700;
    color:#334155;
    cursor:pointer;
    user-select:none;
    transition: transform .12s ease, box-shadow .12s ease, background .12s ease, border-color .12s ease;
    white-space:nowrap;
  }
  .chip:hover{
    transform: translateY(-1px);
    box-shadow:0 14px 22px rgba(15,23,42,.06);
    background:#fff;
  }
  .chip input{ width:16px; height:16px; accent-color: var(--acc); }

  /* ===== Table ===== */
  .table-wrap{ margin-top:12px; overflow:auto; border-radius:14px; border:1px solid var(--line); }
  table{ width:100%; border-collapse:collapse; font-size:.95rem; background:#fff }
  th, td{ padding:12px 12px; border-bottom:1px solid var(--line); vertical-align:middle; }
  th{
    font-weight:900; text-align:left; color:var(--ink);
    background:linear-gradient(#fbfdff,#fbfdff);
    white-space:nowrap;
  }
  tr:hover td{ background:#fcfdfd }
  .thumb{ width:56px; height:56px; border-radius:12px; object-fit:cover; border:1px solid var(--line); background:#f6f8fc }

  .name{ display:flex; flex-direction:column; gap:4px; min-width:260px; }
  .name strong{ color:var(--ink); font-weight:900; line-height:1.2 }
  .meta{ display:flex; gap:10px; flex-wrap:wrap; align-items:center; font-size:.84rem; color:var(--muted); }
  .meta .k{ color:#64748b; font-weight:800; }
  .meta .v{ color:#334155; font-weight:800; }

  .badges{ display:flex; gap:8px; flex-wrap:wrap; margin-top:6px; }
  .badge{
    display:inline-flex; align-items:center; gap:8px;
    padding:6px 10px;
    border-radius:999px;
    font-weight:900;
    font-size:.78rem;
    border:1px solid var(--line);
    background:#f1f5f9;
    color:#334155;
  }
  .badge .dot{ width:8px; height:8px; border-radius:999px; background:#cbd5e1; }
  .b-live{ background:rgba(134,239,172,.22); border-color:rgba(134,239,172,.40); color:#065f46; }
  .b-live .dot{ background:#22c55e; }
  .b-draft{ background:#f1f5f9; }
  .b-draft .dot{ background:#94a3b8; }
  .b-hidden{ background:rgba(254,202,202,.26); border-color:rgba(254,202,202,.55); color:#991b1b; }
  .b-hidden .dot{ background:#ef4444; }

  .b-ml{ background:rgba(196,181,253,.18); border-color:rgba(196,181,253,.45); color:#5b21b6; }
  .b-ml .dot{ background:#8b5cf6; }
  .b-ml-warn{ background:rgba(253,230,138,.26); border-color:rgba(253,230,138,.55); color:#92400e; }
  .b-ml-warn .dot{ background:#f59e0b; }
  .b-ml-bad{ background:rgba(254,202,202,.30); border-color:rgba(254,202,202,.62); color:#b91c1c; }
  .b-ml-bad .dot{ background:#ef4444; }

  .price{ font-weight:900; color:var(--ink); }
  .sale{ color:#16a34a; font-weight:900; }
  .muted-sm{ color:var(--muted); font-size:.85rem; }

  .actions{ display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
  .iconbtn{
    width:38px; height:38px;
    border-radius:12px;
    border:1px solid var(--line);
    background:#fff;
    display:inline-grid;
    place-items:center;
    cursor:pointer;
    transition:transform .12s ease, box-shadow .12s ease, background .12s ease;
    box-shadow:0 8px 18px rgba(15,23,42,.05);
  }
  .iconbtn:hover{ transform:translateY(-1px); background:#fff; box-shadow:0 12px 24px rgba(15,23,42,.08); }
  .iconbtn svg{ width:18px; height:18px; }

  @media (max-width: 860px){
    th:nth-child(6), td:nth-child(6){ display:none; }
  }
  @media (max-width: 640px){
    .filters-row{ flex-direction:column; align-items:stretch; }
    .search{ min-width:unset; width:100%; }
    .tabs{ width:100%; justify-content:space-between; }
    .chip{ width:100%; justify-content:center; }
    .actions{ justify-content:flex-start; }
    .name{ min-width:unset; }
  }

  .foot{
    display:flex; align-items:center; justify-content:space-between;
    gap:12px; margin:16px 4px; flex-wrap:wrap;
  }
</style>
@endpush

@section('content')
@php
  use Illuminate\Support\Str;

  $st = (string)request('status','');
@endphp

<div class="wrap">

  {{-- ===== Header ===== --}}
  <div class="head">
    <div>
      <h1 class="title">Productos Web <span class="muted" style="font-weight:700;">(Catálogo público)</span></h1>
      <p class="muted subtxt">Gestiona el catálogo público y sincroniza con Mercado Libre con acciones rápidas.</p>
    </div>

    <div>
      <a href="{{ route('admin.catalog.create') }}" class="btn">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"/>
          </svg>
        </span>
        Nuevo
      </a>
    </div>
  </div>

  @if(session('ok'))
    <div class="card" style="padding:10px 12px; border-radius:12px; border:1px solid var(--line); background:#f8fffb; color:#0b6b3a; margin:10px 0 12px;">
      {{ session('ok') }}
    </div>
  @endif

  {{-- ===== Filtros (AUTO submit + minimal) ===== --}}
  <div class="filters">
    <form id="filtersForm" method="GET" action="{{ route('admin.catalog.index') }}" class="filters-row">
      {{-- Buscar --}}
      <div class="search">
        <span class="sico">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
          </svg>
        </span>
        <input id="sInput" type="search" name="s" placeholder="Buscar por nombre, SKU o slug…" value="{{ request('s') }}" autocomplete="off" />
      </div>

      {{-- Estado (tabs) --}}
      <div class="tabs" role="tablist" aria-label="Estado">
        <button type="button" class="tab {{ $st==='' ? 'is-active' : '' }}" data-status="">Todos</button>
        <button type="button" class="tab {{ $st==='1' ? 'is-active' : '' }}" data-status="1">Publicado</button>
        <button type="button" class="tab {{ $st==='0' ? 'is-active' : '' }}" data-status="0">Borrador</button>
        <button type="button" class="tab {{ $st==='2' ? 'is-active' : '' }}" data-status="2">Oculto</button>
      </div>

      <input type="hidden" name="status" id="statusInput" value="{{ $st }}">

      {{-- Destacados --}}
      <label class="chip" title="Solo destacados">
        <input id="featuredInput" type="checkbox" name="featured_only" value="1" @checked(request()->boolean('featured_only'))>
        Destacados
      </label>

      {{-- Limpiar (solo si hay filtros) --}}
      @if(request()->hasAny(['s','status','featured_only']))
        <a href="{{ route('admin.catalog.index') }}" class="btn" style="padding:10px 12px;">
          <span class="ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 12a9 9 0 1 0 9-9"/><path d="M3 4v8h8"/>
            </svg>
          </span>
          Limpiar
        </a>
      @endif
    </form>
  </div>

  {{-- ======= Tabla ======= --}}
  <div class="table-wrap card">
    <table>
      <thead>
        <tr>
          <th style="width:72px;">Img</th>
          <th>Producto</th>
          <th>Precio</th>
          <th>Estado</th>
          <th>Destacado</th>
          <th>Publicado</th>
          <th style="text-align:right;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $it)
          @php
            $hasMl = ($it->meli_item_id || $it->meli_status);
            $mlErr = !empty($it->meli_last_error);
          @endphp
          <tr>
            <td>
              @php $src = $it->image_url; @endphp
              <img
                class="thumb"
                src="{{ $src ?: asset('images/placeholder.png') }}"
                alt="Imagen de {{ $it->name }}"
                onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}';"
              >
            </td>

            <td>
              <div class="name">
                <strong>{{ $it->name }}</strong>
                <div class="meta">
                  <span><span class="k">SKU:</span> <span class="v">{{ $it->sku ?: '—' }}</span></span>
                  <span><span class="k">Slug:</span> <span class="v">{{ $it->slug }}</span></span>
                  @if($hasMl)
                    <span><span class="k">ML ID:</span> <span class="v">{{ $it->meli_item_id ?: '—' }}</span></span>
                  @endif
                </div>

                <div class="badges">
                  @if($it->status === 1)
                    <span class="badge b-live"><span class="dot"></span>Publicado</span>
                  @elseif($it->status === 2)
                    <span class="badge b-hidden"><span class="dot"></span>Oculto</span>
                  @else
                    <span class="badge b-draft"><span class="dot"></span>Borrador</span>
                  @endif

                  @if($hasMl)
                    @if($it->meli_status === 'active')
                      <span class="badge b-ml"><span class="dot"></span>ML Activo</span>
                    @elseif($it->meli_status === 'paused')
                      <span class="badge b-ml-warn"><span class="dot"></span>ML Pausado</span>
                    @elseif($it->meli_status === 'error' || $mlErr)
                      <span class="badge b-ml-bad"><span class="dot"></span>ML Error</span>
                    @elseif($it->meli_status)
                      <span class="badge b-ml"><span class="dot"></span>ML {{ ucfirst($it->meli_status) }}</span>
                    @endif
                  @endif

                  @if($it->is_featured)
                    <span class="badge" style="background:rgba(52,211,153,.16);border-color:rgba(52,211,153,.28);color:#065f46;">
                      <span class="dot" style="background:#22c55e"></span>Destacado
                    </span>
                  @endif
                </div>

                @if($mlErr)
                  <details style="margin-top:8px;">
                    <summary style="cursor:pointer; font-weight:800; color:#b91c1c;">
                      Ver error de Mercado Libre
                    </summary>
                    <div style="margin-top:8px; font-size:.9rem; color:#7f1d1d; white-space:normal; max-width:740px;">
                      {{ $it->meli_last_error }}
                    </div>
                  </details>
                @endif
              </div>
            </td>

            <td>
              @if(!is_null($it->sale_price))
                <div class="sale">${{ number_format($it->sale_price,2) }}</div>
                <div class="muted-sm" style="text-decoration:line-through;">${{ number_format($it->price,2) }}</div>
              @else
                <div class="price">${{ number_format($it->price,2) }}</div>
              @endif
            </td>

            <td>
              @if($it->status === 1)
                <span class="badge b-live"><span class="dot"></span>Publicado</span>
              @elseif($it->status === 2)
                <span class="badge b-hidden"><span class="dot"></span>Oculto</span>
              @else
                <span class="badge b-draft"><span class="dot"></span>Borrador</span>
              @endif
            </td>

            <td>
              @if($it->is_featured)
                <span class="badge" style="background:rgba(52,211,153,.16);border-color:rgba(52,211,153,.28);color:#065f46;">
                  <span class="dot" style="background:#22c55e"></span>Sí
                </span>
              @else
                <span class="muted">—</span>
              @endif
            </td>

            <td>
              <span class="muted">{{ $it->published_at ? $it->published_at->format('Y-m-d H:i') : '—' }}</span>
            </td>

            <td style="text-align:right;">
              <div class="actions">
                <a class="iconbtn" title="Editar" href="{{ route('admin.catalog.edit', $it) }}">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                  </svg>
                </a>

                <form method="POST" action="{{ route('admin.catalog.toggle', $it) }}"
                      onsubmit="return confirm('¿Cambiar estado de publicación en el sitio web?')">
                  @csrf
                  @method('PATCH')
                  <button class="iconbtn" title="{{ $it->status == 1 ? 'Ocultar' : 'Publicar' }}" type="submit">
                    @if($it->status == 1)
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12z"/>
                        <path d="M3 3l18 18"/>
                      </svg>
                    @else
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12z"/>
                        <circle cx="12" cy="12" r="3"/>
                      </svg>
                    @endif
                  </button>
                </form>

                <form method="POST" action="{{ route('admin.catalog.meli.publish', $it) }}">
                  @csrf
                  <button class="iconbtn" title="Mercado Libre: Publicar/Actualizar" type="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M12 3v12"/><path d="M7 10l5 5 5-5"/>
                      <path d="M20 21H4a2 2 0 0 1-2-2v-3"/>
                    </svg>
                  </button>
                </form>

                @if($it->meli_item_id)
                  <a class="iconbtn" title="Mercado Libre: Ver" href="{{ route('admin.catalog.meli.view', $it) }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12z"/>
                      <circle cx="12" cy="12" r="3"/>
                    </svg>
                  </a>

                  <form method="POST" action="{{ route('admin.catalog.meli.pause', $it) }}">
                    @csrf
                    <button class="iconbtn" title="Mercado Libre: Pausar" type="submit">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/>
                      </svg>
                    </button>
                  </form>

                  <form method="POST" action="{{ route('admin.catalog.meli.activate', $it) }}">
                    @csrf
                    <button class="iconbtn" title="Mercado Libre: Activar" type="submit">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="8 5 19 12 8 19 8 5"/>
                      </svg>
                    </button>
                  </form>
                @endif

                <form method="POST" action="{{ route('admin.catalog.destroy', $it) }}"
                      onsubmit="return confirm('¿Eliminar este producto del catálogo web? Esta acción no se puede deshacer.')">
                  @csrf
                  @method('DELETE')
                  <button class="iconbtn" title="Eliminar" type="submit" style="border-color:rgba(254,202,202,.75);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#b91c1c" stroke-width="2">
                      <polyline points="3 6 5 6 21 6"/>
                      <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                      <line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>
                    </svg>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="muted" style="text-align:center; padding:28px;">
              No hay productos que coincidan con el filtro.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="foot">
    <div class="muted">
      Mostrando {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} de {{ $items->total() }} registros
    </div>
    <div>
      {{ $items->onEachSide(1)->links() }}
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
(function(){
  const form = document.getElementById('filtersForm');
  const sInput = document.getElementById('sInput');
  const statusInput = document.getElementById('statusInput');
  const tabs = Array.from(document.querySelectorAll('.tab'));
  const featured = document.getElementById('featuredInput');

  // ✅ auto submit con debounce (evita recargar por cada letra)
  function debounce(fn, wait){
    let t;
    return (...args)=>{ clearTimeout(t); t=setTimeout(()=>fn(...args), wait); };
  }

  const submitDebounced = debounce(()=> form?.submit(), 450);

  sInput?.addEventListener('input', submitDebounced);

  // Tabs: set hidden input + submit inmediato (sin botón aplicar)
  tabs.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      tabs.forEach(x=>x.classList.remove('is-active'));
      btn.classList.add('is-active');
      if(statusInput) statusInput.value = btn.dataset.status ?? '';
      form?.submit();
    });
  });

  // Destacados: submit inmediato
  featured?.addEventListener('change', ()=> form?.submit());
})();
</script>
@endpush
