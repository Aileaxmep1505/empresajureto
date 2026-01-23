{{-- resources/views/tech_sheets/index.blade.php --}}
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

  /* icon buttons */
  .iconbtn-wrap{ display:inline-flex; position:relative; }
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

  /* ===== Filtros (search + tabs + chip) ===== */
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

  .tabs{
    display:inline-flex; align-items:center; gap:6px;
    padding:6px; border-radius:999px;
    border:1px solid rgba(232,238,246,.95);
    background:rgba(255,255,255,.86);
    box-shadow:0 10px 18px rgba(15,23,42,.04);
    user-select:none;
  }
  .tab{
    appearance:none; border:0; background:transparent;
    padding:9px 12px; border-radius:999px;
    cursor:pointer; font-weight:700; color:#334155;
    transition: background .12s ease, color .12s ease, transform .12s ease, box-shadow .12s ease;
    white-space:nowrap;
  }
  .tab:hover{ background:rgba(52,211,153,.10); transform: translateY(-1px); box-shadow:0 10px 18px rgba(15,23,42,.05); }
  .tab.is-active{ background:var(--acc-soft); color:var(--acc-ink); box-shadow:0 12px 22px rgba(52,211,153,.12); }

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
    transition:transform .12s ease, box-shadow .12s ease;
  }
  .ts-card:hover{
    transform:translateY(-3px);
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
  }
  .ts-actions a{
    width:30px; height:30px;
    border-radius:999px;
    border:1px solid var(--line);
    background:#fff;
    display:grid; place-items:center;
    text-decoration:none;
    font-size:.9rem;
  }
  .ts-actions a:hover{
    border-color:var(--acc-ring);
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
  .sheet .tabs{
    width:100%;
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
    justify-content:flex-start;
  }
  .sheet .tabs::-webkit-scrollbar{ height:0; }
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
</style>
@endpush

@section('content')
@php
  use Illuminate\Support\Str;
  $q    = (string) request('q','');
  $mode = (string) request('mode','');       // '' | ai | noai
  $img  = request()->boolean('image_only');  // true | false
@endphp

<div class="ts-wrap">

  <div class="head">
    <div>
      <h1 class="title">Fichas técnicas</h1>
      <p class="muted subtxt">
        Genera y organiza fichas técnicas con IA para tus productos. Descarga en PDF o Word con un clic.
      </p>
      <p class="muted" style="font-size:.84rem; margin-top:6px;">
        {{ $items->total() }} {{ Str::plural('ficha', $items->total()) }} encontradas
        @if($q) · filtro: “{{ $q }}” @endif
      </p>
    </div>

    {{-- Botón Nuevo (desktop) --}}
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

  {{-- Filtros --}}
  <div class="filters">
    <form id="filtersForm" method="GET" action="{{ route('tech-sheets.index') }}" class="filters-row">
      {{-- Buscador siempre visible --}}
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

      {{-- SOLO estos tools se van al bottom sheet en móvil --}}
      <div class="filter-tools">
        <div class="tt">
          <span class="tt-bubble">Filtrar por IA</span>
          <div class="tabs" role="tablist" aria-label="Generación IA">
            <button type="button" class="tab {{ $mode==='' ? 'is-active' : '' }}" data-mode="">Todas</button>
            <button type="button" class="tab {{ $mode==='ai' ? 'is-active' : '' }}" data-mode="ai">Con IA</button>
            <button type="button" class="tab {{ $mode==='noai' ? 'is-active' : '' }}" data-mode="noai">Pendiente IA</button>
          </div>
        </div>

        <div class="tt">
          <span class="tt-bubble">Mostrar solo fichas con imagen</span>
          <label class="chip">
            <input id="imgInput" type="checkbox" name="image_only" value="1" @checked($img)>
            Sólo con imagen
          </label>
        </div>

        @if(request()->hasAny(['q','mode','image_only']))
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

      <input type="hidden" name="mode" id="modeInput" value="{{ $mode }}">
    </form>
  </div>

  {{-- GRID DE CARDS --}}
  @if($items->count())
    <div class="cards-grid">
      @foreach($items as $sheet)
        @php
          $hasAi   = !empty($sheet->ai_description);
          $hasImg  = !empty($sheet->image_path);
          $ref     = $sheet->reference ?? null;
          $cat     = $sheet->identification ?? null;
          $desc    = $sheet->ai_description ?: $sheet->user_description;
        @endphp

        <article class="ts-card">
          {{-- Imagen + badges --}}
          <div class="ts-hero">
            @if($hasImg)
              <img src="{{ asset('storage/'.$sheet->image_path) }}" alt="{{ $sheet->product_name }}">
            @else
              <div class="ph">📦</div>
            @endif

            <span class="ts-badge">
              {{ $hasAi ? 'Con IA' : 'Borrador' }}
            </span>

            @if($cat)
              <span class="ts-badge secondary">
                {{ \Illuminate\Support\Str::limit($cat, 18) }}
              </span>
            @endif
          </div>

          {{-- Cuerpo --}}
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
                Sin descripción generada.
              @endif
            </div>

            <div class="tag-row">
              @if($hasAi)
                <span class="tag-pill">Texto IA</span>
              @else
                <span class="tag-pill">Pendiente IA</span>
              @endif

              @if($hasImg)
                <span class="tag-pill">Con imagen</span>
              @else
                <span class="tag-pill">Sin imagen</span>
              @endif
            </div>
          </div>

          {{-- Footer --}}
          <div class="ts-footer">
            <div class="ts-footer-left">
              <div class="ts-dot"></div>
              <span>Actualizada {{ optional($sheet->updated_at)->diffForHumans() }}</span>
            </div>
            <div class="ts-actions">
              <a href="{{ route('tech-sheets.show', $sheet) }}" title="Ver">
                👁
              </a>
              <a href="{{ route('tech-sheets.pdf', $sheet) }}" title="PDF">
                Ⓟ
              </a>
              <a href="{{ route('tech-sheets.word', $sheet) }}" title="Word">
                𝓦
              </a>
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

{{-- FAB (móvil) abre bottom sheet --}}
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

  {{-- En el sheet NO va el buscador, sólo filtros --}}
  <form id="sheetForm" method="GET" action="{{ route('tech-sheets.index') }}" class="sf">
    <input type="hidden" name="q" id="qMirror" value="{{ $q }}">

    <div class="tabs" role="tablist" aria-label="Generación IA (móvil)">
      <button type="button" class="tab {{ $mode==='' ? 'is-active' : '' }}" data-mode="">Todas</button>
      <button type="button" class="tab {{ $mode==='ai' ? 'is-active' : '' }}" data-mode="ai">Con IA</button>
      <button type="button" class="tab {{ $mode==='noai' ? 'is-active' : '' }}" data-mode="noai">Pendiente IA</button>
    </div>

    <input type="hidden" name="mode" id="modeSheet" value="{{ $mode }}">

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

    @if(request()->hasAny(['q','mode','image_only']))
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
<script>
(function(){
  function debounce(fn, wait){
    let t; return (...args)=>{ clearTimeout(t); t=setTimeout(()=>fn(...args), wait); };
  }
  function isMobile(){ return window.matchMedia('(max-width: 760px)').matches; }

  const form      = document.getElementById('filtersForm');
  const qInput    = document.getElementById('qInput');
  const modeInput = document.getElementById('modeInput');
  const tabs      = Array.from(document.querySelectorAll('#filtersForm .tab'));
  const imgInput  = document.getElementById('imgInput');

  const submitDebounced = debounce(()=> form?.submit(), 450);

  qInput?.addEventListener('input', submitDebounced);

  tabs.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      tabs.forEach(x=>x.classList.remove('is-active'));
      btn.classList.add('is-active');
      if(modeInput) modeInput.value = btn.dataset.mode ?? '';
      form?.submit();
    });
  });

  imgInput?.addEventListener('change', ()=> form?.submit());

  // ===== Bottom sheet =====
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

  // Sheet filters (modo + imagen)
  const sheetForm  = document.getElementById('sheetForm');
  const qMirror    = document.getElementById('qMirror');
  const modeSheet  = document.getElementById('modeSheet');
  const tabSheet   = Array.from(document.querySelectorAll('#sheetForm .tab'));
  const imgSheet   = document.getElementById('imgSheet');

  function syncSearch(){
    if(qMirror && qInput) qMirror.value = qInput.value || '';
  }
  qInput?.addEventListener('input', syncSearch);
  syncSearch();

  tabSheet.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      tabSheet.forEach(x=>x.classList.remove('is-active'));
      btn.classList.add('is-active');
      if(modeSheet) modeSheet.value = btn.dataset.mode ?? '';
      syncSearch();
      sheetForm?.submit();
    });
  });

  imgSheet?.addEventListener('change', ()=>{
    syncSearch();
    sheetForm?.submit();
  });

})();
</script>
@endpush
