@extends('layouts.app')
@section('title','Fichas técnicas')

@push('styles')
<style>
  :root{
    --ink:#0f172a; --muted:#64748b; --bg:#f7fafc;
    --line:#e8eef6; --surface:#ffffff;
    --shadow:0 12px 30px rgba(13, 23, 38, .06);
    --r:16px;

    --acc:#34d399;
    --acc-ink:#065f46;
    --acc-soft:rgba(52,211,153,.14);
    --acc-ring:rgba(52,211,153,.28);

    --danger:#ef4444;
    --danger-soft:rgba(239,68,68,.12);
    --danger-ring:rgba(239,68,68,.26);

    --g1:rgba(52,211,153,.12);
    --g2:rgba(251,191,36,.10);
    --g3:rgba(148,163,184,.10);

    --tt-bg:#111827;
    --tt-fg:#ffffff;
  }

  html,body{background:var(--bg)}
  .ts-wrap{max-width:1200px; margin-inline:auto; padding:0 14px}
  .card{background:var(--surface); border:1px solid var(--line); border-radius:var(--r); box-shadow:var(--shadow);}

  /* ===== Header ===== */
  .head{display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; margin:14px 0 10px;}
  .title{font-weight:900; color:var(--ink); letter-spacing:-.02em; margin:0}
  .muted{color:var(--muted)}
  .subtxt{margin-top:6px;font-size:.92rem;max-width:70ch}

  /* ===== Botón pastel ===== */
  .btn{
    display:inline-flex; align-items:center; justify-content:center; gap:10px;
    border:1px solid transparent;
    cursor:pointer; text-decoration:none;
    font-weight:800;
    border-radius:14px;
    padding:10px 14px;
    transition:transform .12s ease, box-shadow .12s ease, background .12s ease, color .12s ease, border-color .12s ease, filter .12s ease;
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
  .btn-sm{ padding:8px 10px; border-radius:12px; font-size:.92rem; }
  .ico{ width:18px; height:18px; display:inline-block; }
  .ico svg{ width:18px; height:18px; display:block; }

  /* Tooltip */
  .tt{ position:relative; display:inline-flex; }
  .tt .tt-bubble{
    position:absolute; left:50%; bottom:calc(100% + 10px);
    transform:translateX(-50%);
    background:var(--tt-bg); color:var(--tt-fg);
    font-size:12px; font-weight:700;
    padding:8px 10px; border-radius:12px;
    white-space:nowrap; opacity:0; pointer-events:none;
    box-shadow:0 14px 30px rgba(0,0,0,.18);
    transition:opacity .14s ease, transform .14s ease;
    z-index:20;
  }
  .tt .tt-bubble:before{
    content:""; position:absolute; left:50%; bottom:-6px;
    width:12px; height:12px; background:var(--tt-bg);
    transform:translateX(-50%) rotate(45deg); border-radius:2px;
  }
  .tt:hover .tt-bubble{ opacity:1; transform:translateX(-50%) translateY(-2px); }
  @media (hover:none){ .tt .tt-bubble{ display:none !important; } }

  /* ===== Filtros ===== */
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
  .filters-row{display:flex; gap:12px; align-items:center; justify-content:space-between; flex-wrap:wrap;}

  .search{
    display:flex; align-items:center; gap:10px;
    flex:1; min-width:0; width:min(100%, 560px);
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
  .search input{border:0; outline:0; background:transparent; width:100%; color:var(--ink); font-weight:500;}

  .filter-tools{ display:inline-flex; gap:12px; align-items:center; flex-wrap:wrap; }

  .chip{
    display:inline-flex; align-items:center; gap:10px;
    padding:10px 14px; border-radius:999px;
    border:1px solid rgba(232,238,246,.95);
    background:rgba(255,255,255,.86);
    box-shadow:0 10px 18px rgba(15,23,42,.04);
    font-weight:700; color:#334155; cursor:pointer;
    user-select:none;
    transition: transform .12s ease, box-shadow .12s ease, background .12s ease, border-color .12s ease;
    white-space:nowrap;
  }
  .chip:hover{ transform: translateY(-1px); box-shadow:0 14px 22px rgba(15,23,42,.06); background:#fff; }
  .chip input{ width:16px; height:16px; accent-color: var(--acc); }

  .foot{display:flex; align-items:center; justify-content:space-between; gap:12px; margin:16px 4px; flex-wrap:wrap;}

  /* ===== Cards grid ===== */
  .cards-grid{
    margin-top:14px;
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(260px,1fr));
    gap:16px;
  }
  .ts-card{
    background:#fff;
    border-radius:18px;
    border:1px solid var(--line);
    box-shadow:0 18px 40px rgba(15,23,42,.08);
    overflow:hidden;
    display:flex;
    flex-direction:column;
    transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease;
    cursor:pointer;
  }
  .ts-card:hover{
    transform:translateY(-3px);
    border-color:rgba(148,163,184,.7);
    box-shadow:0 22px 60px rgba(15,23,42,.12);
  }
  .ts-hero{
    position:relative;
    height:170px;
    background:linear-gradient(135deg,#e0f2fe,#f5f3ff);
    display:flex;
    align-items:center;
    justify-content:center;
  }
  .ts-hero img{
    width:100%; height:100%; object-fit:cover; display:block;
  }
  .ts-hero .ph{
    width:66px; height:66px;
    border-radius:18px;
    border:2px dashed rgba(148,163,184,.8);
    display:grid; place-items:center;
    font-size:1.6rem; opacity:.7;
  }
  .ts-badge{
    position:absolute;
    top:10px; left:10px;
    padding:4px 10px;
    border-radius:999px;
    font-size:.75rem;
    font-weight:800;
    color:#065f46;
    background:rgba(134,239,172,.9);
  }
  .ts-badge.secondary{
    left:auto; right:10px;
    background:rgba(15,23,42,.9);
    color:#e5e7eb;
  }

  .ts-body{
    padding:12px 14px 10px;
    display:flex;
    flex-direction:column;
    gap:5px;
    flex:1;
  }
  .ts-ref{
    font-size:.75rem;
    letter-spacing:.12em;
    text-transform:uppercase;
    color:#9ca3af;
  }
  .ts-name{
    font-size:1rem;
    font-weight:900;
    color:var(--ink);
  }
  .ts-meta{
    font-size:.82rem;
    color:var(--muted);
  }
  .ts-desc{
    font-size:.86rem;
    color:#4b5563;
    margin-top:4px;
    min-height:40px;
  }
  .tag-row{
    margin-top:6px;
    display:flex;
    gap:6px;
    flex-wrap:wrap;
  }
  .tag-pill{
    font-size:.72rem;
    border-radius:999px;
    padding:4px 10px;
    background:#f1f5f9;
    color:#334155;
    border:1px solid rgba(226,232,240,.9);
    font-weight:700;
  }

  .ts-footer{
    padding:10px 14px 11px;
    border-top:1px solid var(--line);
    display:flex;
    align-items:center;
    justify-content:space-between;
    font-size:.78rem;
    color:var(--muted);
  }
  .ts-footer-left{
    display:flex;
    align-items:center;
    gap:6px;
  }
  .ts-dot{
    width:6px; height:6px;
    border-radius:999px;
    background:#22c55e;
  }

  .ts-actions{
    display:flex; gap:6px;
    align-items:center;
  }
  .ts-actions a,
  .ts-actions button{
    width:32px; height:32px;
    border-radius:8px; /* menos circulares */
    border:1px solid var(--line);
    background:#fff;
    display:grid; place-items:center;
    text-decoration:none;
    cursor:pointer;
    transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease, background .12s ease;
    box-shadow:0 4px 10px rgba(15,23,42,.06);
  }
  .ts-actions a:hover,
  .ts-actions button:hover{
    transform:translateY(-1px);
    border-color:var(--acc-ring);
    box-shadow:0 10px 20px rgba(15,23,42,.10);
  }
  .ts-actions svg{
    width:16px; height:16px;
    color:#0f172a;
  }
  .ts-actions .danger-btn{
    border-color: rgba(239,68,68,.35);
    background: var(--danger-soft);
  }
  .ts-actions .danger-btn svg{
    color:#7f1d1d;
  }
  .ts-actions .danger-btn:hover{
    border-color: var(--danger-ring);
  }

  /* ===== FAB + Sheet (móvil) ===== */
  .fab{
    position:fixed; right:16px; bottom:18px;
    width:58px; height:58px; border-radius:999px;
    border:1px solid rgba(232,238,246,.9);
    background:rgba(255,255,255,.92);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    box-shadow:0 18px 44px rgba(15,23,42,.18);
    display:none; place-items:center;
    z-index:1000; cursor:pointer;
    transition:transform .14s ease, box-shadow .14s ease;
  }
  .fab:hover{ transform: translateY(-2px); box-shadow:0 22px 54px rgba(15,23,42,.22); }
  .fab svg{ width:22px; height:22px; color: var(--acc-ink); }

  .sheet-overlay{
    position:fixed; inset:0;
    background:rgba(15,23,42,.42);
    opacity:0; pointer-events:none;
    transition:opacity .18s ease;
    z-index:1001;
  }
  .sheet{
    position:fixed; left:0; right:0;
    bottom:-85%;
    background:#fff;
    border-top-left-radius:20px;
    border-top-right-radius:20px;
    border:1px solid rgba(232,238,246,.9);
    box-shadow:0 -18px 50px rgba(15,23,42,.25);
    z-index:1002;
    transition: bottom .22s ease;
    padding:12px 14px 16px;
  }
  .sheet .grab{ width:44px; height:5px; border-radius:999px; background:#e5e7eb; margin:4px auto 10px; }
  .sheet .sheet-title{ display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px; }
  .sheet .sheet-title h3{ margin:0; font-size:15px; font-weight:900; color:var(--ink); }
  .sheet .sheet-close{
    width:36px; height:36px; border-radius:12px;
    border:1px solid var(--line);
    background:#fff;
    display:grid; place-items:center;
    cursor:pointer;
    box-shadow:0 10px 18px rgba(15,23,42,.06);
    transition:transform .12s ease, box-shadow .12s ease;
  }
  .sheet .sheet-close:hover{ transform:translateY(-1px); box-shadow:0 14px 24px rgba(15,23,42,.08); }
  .sheet .sheet-close svg{ width:18px; height:18px; }

  .sheet .sf{ display:grid; gap:12px; }
  .sheet .chip{ width:100%; justify-content:center; }
  .sheet .btn{ width:100%; justify-content:center; padding:12px 14px; border-radius:16px; }

  .sheet-open .sheet-overlay{ opacity:1; pointer-events:auto; }
  .sheet-open .sheet{ bottom:0; }

  @media (max-width: 760px){
    .ts-wrap{ padding:0 10px; }
    body{ padding-bottom: 86px; }

    .filter-tools{ display:none !important; }
    .head .tt-new{ display:none !important; }

    .cards-grid{ grid-template-columns:1fr; }
    .fab{ display:grid; }
  }

  /* ===== SweetAlert minimal ===== */
  .swal2-modern{
    border-radius:18px !important;
    padding:18px 20px 16px !important;
    box-shadow:0 18px 40px rgba(15,23,42,.12) !important;
    border:1px solid #e5e7eb !important;
  }
  .swal2-modern-title{
    font-size:1rem !important;
    font-weight:800 !important;
    color:#0f172a !important;
    margin-bottom:4px !important;
  }
  .swal2-modern-text{
    font-size:.86rem !important;
    color:#4b5563 !important;
  }
  .swal2-modern-btn{
    border-radius:8px !important;
    padding:7px 16px !important;
    font-size:.84rem !important;
    font-weight:600 !important;
    box-shadow:none !important;
    border-width:1px !important;
  }
  .swal2-modern-btn-danger{
    background:#0f172a !important;
    color:#f9fafb !important;
    border-color:#0f172a !important;
  }
  .swal2-modern-btn-secondary{
    background:#f9fafb !important;
    color:#4b5563 !important;
    border-color:#d1d5db !important;
    margin-right:8px !important;
  }
  .swal2-modern-toast{
    border-radius:12px !important;
    padding:8px 14px !important;
    box-shadow:0 10px 30px rgba(15,23,42,.18) !important;
    border:1px solid #e5e7eb !important;
  }
  .swal2-modern-toast-title{
    font-size:.82rem !important;
    font-weight:600 !important;
  }
</style>
@endpush

@section('content')
@php
  use Illuminate\Support\Str;
  $q    = (string) request('q','');
  $img  = request()->boolean('image_only');
@endphp

<div class="ts-wrap">

  <div class="head">
    <div>
      <h1 class="title">Fichas técnicas</h1>
      <p class="muted subtxt">Administra tus fichas técnicas.</p>
      <p class="muted" style="font-size:.84rem; margin-top:6px;">
        {{ $items->total() }} {{ Str::plural('ficha', $items->total()) }} encontradas
        @if($q) · filtro: “{{ $q }}” @endif
      </p>
    </div>

    <div class="tt tt-new">
      <span class="tt-bubble">Crear nueva ficha técnica</span>
      <a href="{{ route('tech-sheets.create') }}" class="btn">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"/>
          </svg>
        </span>
        Nueva ficha
      </a>
    </div>
  </div>

  <div class="filters">
    <form id="filtersForm" method="GET" action="{{ route('tech-sheets.index') }}" class="filters-row">
      <div class="tt" style="flex:1; min-width:0;">
        <span class="tt-bubble">Buscar por nombre, marca, modelo o referencia</span>
        <div class="search">
          <span class="sico">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
            </svg>
          </span>
          <input id="qInput" type="search" name="q" placeholder="Buscar ficha técnica…" value="{{ $q }}" autocomplete="off" />
        </div>
      </div>

      <div class="filter-tools">
        <div class="tt">
          <span class="tt-bubble">Mostrar solo fichas con imagen</span>
          <label class="chip">
            <input id="imgInput" type="checkbox" name="image_only" value="1" @checked($img)>
            Sólo con imagen
          </label>
        </div>

        @if(request()->hasAny(['q','image_only']))
          <div class="tt">
            <span class="tt-bubble">Quitar filtros</span>
            <a href="{{ route('tech-sheets.index') }}" class="btn btn-sm" style="padding:10px 12px;">
              <span class="ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M3 12a9 9 0 1 0 9-9"/><path d="M3 4v8h8"/>
                </svg>
              </span>
              Limpiar
            </a>
          </div>
        @endif
      </div>
    </form>
  </div>

  @if($items->count())
    <div class="cards-grid">
      @foreach($items as $sheet)
        @php
          $hasImg  = !empty($sheet->image_path);
          $ref     = $sheet->reference ?? null;
          $cat     = $sheet->identification ?? null;
          $desc    = $sheet->ai_description ?: $sheet->user_description;
        @endphp

        <article class="ts-card js-sheet-card"
                 data-url="{{ route('tech-sheets.show', $sheet) }}">
          <div class="ts-hero">
            @if($hasImg)
              <img src="{{ asset('storage/'.$sheet->image_path) }}" alt="{{ $sheet->product_name }}">
            @else
              <div class="ph">📦</div>
            @endif

            <span class="ts-badge">Ficha</span>

            @if($cat)
              <span class="ts-badge secondary">
                {{ \Illuminate\Support\Str::limit($cat, 18) }}
              </span>
            @endif
          </div>

          <div class="ts-body">
            @if($ref)
              <div class="ts-ref">{{ \Illuminate\Support\Str::upper($ref) }}</div>
            @endif

            <div class="ts-name">{{ $sheet->product_name }}</div>

            <div class="ts-meta">
              @if($sheet->brand) {{ $sheet->brand }} @endif
              @if($sheet->model) · Modelo {{ $sheet->model }} @endif
            </div>

            <div class="ts-desc">
              @if($desc)
                {{ \Illuminate\Support\Str::limit($desc, 110) }}
              @else
                Sin descripción.
              @endif
            </div>

            <div class="tag-row">
              @if($hasImg)
                <span class="tag-pill">Con imagen</span>
              @else
                <span class="tag-pill">Sin imagen</span>
              @endif

              @if(!empty($sheet->custom_pdf_path))
                <span class="tag-pill">PDF subido</span>
              @else
                <span class="tag-pill">PDF generado</span>
              @endif
            </div>
          </div>

          <div class="ts-footer">
            <div class="ts-footer-left">
              <div class="ts-dot"></div>
              <span>Actualizada {{ optional($sheet->updated_at)->diffForHumans() }}</span>
            </div>

            <div class="ts-actions">
              {{-- Editar --}}
              <a href="{{ route('tech-sheets.edit', $sheet) }}" title="Editar ficha" class="js-stop-card">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 20h9"/>
                  <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                </svg>
              </a>

              {{-- Borrar con SweetAlert --}}
              <form method="POST"
                    action="{{ route('tech-sheets.destroy', $sheet) }}"
                    class="js-delete-form js-stop-card"
                    data-name="{{ $sheet->product_name }}"
                    style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="danger-btn" title="Borrar ficha">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18"/>
                    <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                    <path d="M10 11v6"/><path d="M14 11v6"/>
                  </svg>
                </button>
              </form>
            </div>
          </div>
        </article>
      @endforeach
    </div>

    <div class="foot">
      <div class="muted" style="font-size:.84rem;">
        Mostrando {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} de {{ $items->total() }} registros
      </div>
      <div>
        {{ $items->onEachSide(1)->links() }}
      </div>
    </div>
  @else
    <div class="card" style="padding:24px 18px; text-align:center; margin-top:16px;">
      <div class="muted" style="font-size:.9rem;">
        No se encontraron fichas técnicas.
        <a href="{{ route('tech-sheets.create') }}">Crea la primera</a>.
      </div>
    </div>
  @endif
</div>

<button class="fab" id="fabOpen" type="button" aria-label="Abrir filtros">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M3 5h18M6 12h12M10 19h4"/>
  </svg>
</button>

<div class="sheet-overlay" id="sheetOverlay" aria-hidden="true"></div>

<div class="sheet" id="sheet" role="dialog" aria-modal="true" aria-label="Filtros de fichas" aria-hidden="true">
  <div class="grab"></div>
  <div class="sheet-title">
    <h3>Filtros</h3>
    <button class="sheet-close" type="button" id="sheetClose" aria-label="Cerrar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 6L6 18M6 6l12 12"/>
      </svg>
    </button>
  </div>

  <form id="sheetForm" method="GET" action="{{ route('tech-sheets.index') }}" class="sf">
    <input type="hidden" name="q" id="qMirror" value="{{ $q }}">

    <label class="chip">
      <input id="imgSheet" type="checkbox" name="image_only" value="1" @checked($img)>
      Sólo con imagen
    </label>

    <a href="{{ route('tech-sheets.create') }}" class="btn">
      <span class="ico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 5v14M5 12h14"/>
        </svg>
      </span>
      Nueva ficha
    </a>

    @if(request()->hasAny(['q','image_only']))
      <a href="{{ route('tech-sheets.index') }}" class="btn btn-sm" style="padding:12px 14px;">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 12a9 9 0 1 0 9-9"/><path d="M3 4v8h8"/>
          </svg>
        </span>
        Limpiar filtros
      </a>
    @endif
  </form>
</div>
@endsection

@push('scripts')
{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function(){
  function debounce(fn, wait){
    let t; return (...args)=>{ clearTimeout(t); t=setTimeout(()=>fn(...args), wait); };
  }
  function isMobile(){ return window.matchMedia('(max-width: 760px)').matches; }

  const form      = document.getElementById('filtersForm');
  const qInput    = document.getElementById('qInput');
  const imgInput  = document.getElementById('imgInput');

  const submitDebounced = debounce(()=> form?.submit(), 450);
  qInput?.addEventListener('input', submitDebounced);
  imgInput?.addEventListener('change', ()=> form?.submit());

  const root    = document.documentElement;
  const fab     = document.getElementById('fabOpen');
  const sheet   = document.getElementById('sheet');
  const overlay = document.getElementById('sheetOverlay');
  const closeBtn= document.getElementById('sheetClose');

  function openSheet(){
    if(!isMobile()) return;
    root.classList.add('sheet-open');
    sheet?.setAttribute('aria-hidden','false');
    overlay?.setAttribute('aria-hidden','false');
  }
  function closeSheet(){
    root.classList.remove('sheet-open');
    sheet?.setAttribute('aria-hidden','true');
    overlay?.setAttribute('aria-hidden','true');
  }

  fab?.addEventListener('click', openSheet);
  overlay?.addEventListener('click', closeSheet);
  closeBtn?.addEventListener('click', closeSheet);
  document.addEventListener('keydown', e => { if(e.key === 'Escape') closeSheet(); });
  window.addEventListener('resize', ()=>{ if(!isMobile()) closeSheet(); });

  const sheetForm  = document.getElementById('sheetForm');
  const qMirror    = document.getElementById('qMirror');
  const imgSheet   = document.getElementById('imgSheet');

  function syncSearch(){
    if(qMirror && qInput) qMirror.value = qInput.value || '';
  }
  qInput?.addEventListener('input', syncSearch);
  syncSearch();

  imgSheet?.addEventListener('change', ()=>{
    syncSearch();
    sheetForm?.submit();
  });

  // ==== Card clickeable (show) ====
  document.querySelectorAll('.js-sheet-card').forEach(function(card){
    card.addEventListener('click', function(e){
      const interactive = e.target.closest('a, button, .js-stop-card');
      if (interactive) return;
      const url = card.dataset.url;
      if (url) window.location.href = url;
    });
  });

  // ==== SweetAlert confirm delete minimal ====
  document.querySelectorAll('.js-delete-form').forEach(function(form){
    form.addEventListener('submit', function(e){
      e.preventDefault();
      const name = this.dataset.name || 'esta ficha';

      Swal.fire({
        title: 'Eliminar ficha',
        text: '¿Seguro que deseas eliminar "' + name + '"? Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        buttonsStyling: false,
        customClass: {
          popup: 'swal2-modern',
          title: 'swal2-modern-title',
          htmlContainer: 'swal2-modern-text',
          confirmButton: 'swal2-modern-btn swal2-modern-btn-danger',
          cancelButton: 'swal2-modern-btn swal2-modern-btn-secondary'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          this.submit();
        }
      });
    });
  });

})();
</script>

{{-- Toast de éxito --}}
@if(session('ok'))
<script>
  Swal.fire({
    icon: 'success',
    title: @json(session('ok')),
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2300,
    timerProgressBar: true,
    buttonsStyling:false,
    customClass:{
      popup:'swal2-modern-toast',
      title:'swal2-modern-toast-title'
    }
  });
</script>
@endif
@endpush
