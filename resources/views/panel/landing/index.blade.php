@extends('layouts.app') 
@section('title','Secciones de Inicio')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet"/>
<style>
  /* ========= Tokens ========= */
  :root{
    --bg:#f6f8fc;
    --card:#ffffff;
    --ink:#0f172a;
    --muted:#6b7280;
    --line:#e7edf7;
    --shadow:0 14px 30px rgba(2,6,23,.08);

    /* Pastel */
    --soft-blue-bg:#edf4ff;  --soft-blue-fg:#0b4db5;  --soft-blue-br:#cfe0ff;
    --soft-gray-bg:#f4f7fb;  --soft-gray-fg:#475569;  --soft-gray-br:#e5eaf5;
    --soft-green-bg:#eefbf5; --soft-green-fg:#0f766e; --soft-green-br:#c7f2e9;
    --soft-yellow-bg:#fff7e6; --soft-yellow-fg:#8a5a00; --soft-yellow-br:#ffe2b8;
    --soft-red-bg:#ffefef;   --soft-red-fg:#b42318;   --soft-red-br:#ffd3d3;
  }
  html,body{background:var(--bg); color:var(--ink)}

  /* ========= Layout centrado ========= */
  .wrap{
    width:min(1120px, calc(100% - 24px));
    margin:92px auto 26px;
  }

  /* ========= Hero ========= */
  .hero{
    background:
      radial-gradient(1200px 700px at 0% -10%, #eaf2ff 0%, transparent 60%),
      radial-gradient(1200px 700px at 100% -10%, #ffe9ef 0%, transparent 60%),
      var(--card);
    border:1px solid var(--line);
    border-radius:20px;
    box-shadow:var(--shadow);
    padding:16px 18px;
    display:flex; justify-content:space-between; align-items:center; gap:14px; flex-wrap:wrap;
  }
  .hero-title{
    display:flex; align-items:center; gap:.7rem;
    letter-spacing:-.01em; font-weight:800; margin:0;
  }
  .hero-icon{
    width:40px; height:40px; border-radius:50%;
    display:grid; place-items:center; background:#fff; border:1px solid var(--line);
    color:#0b4db5;
  }
  .hero-hint{ color:var(--muted); margin-top:2px; }

  /* CTA (+) pastel */
  .btnP{
    border:1px solid transparent; border-radius:12px; font-weight:800;
    padding:.55rem .85rem; line-height:1;
    display:inline-flex; align-items:center; gap:.5rem; text-decoration:none;
    box-shadow:0 10px 24px rgba(2,6,23,.08);
    transition:background .15s,color .15s,border-color .15s,box-shadow .15s,transform .1s;
  }
  .btnP:active{ transform:translateY(1px) }
  .btnP-blue{ background:var(--soft-blue-bg); color:var(--soft-blue-fg); border-color:var(--soft-blue-br); }
  .btnP:hover{ background:#fff; color:#0f172a; border-color:#e5e7eb; box-shadow:0 14px 28px rgba(2,6,23,.14); }

  /* ========= Grid ========= */
  .grid{
    display:grid; gap:16px; margin-top:14px;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  }

  /* ========= Card sección ========= */
  .section{
    background:linear-gradient(180deg,#fff 0%,#fff 60%,#fbfdff 100%);
    border:1px solid var(--line); border-radius:16px; box-shadow:var(--shadow);
    transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;
  }
  .section:hover{ transform:translateY(-3px); box-shadow:0 18px 36px rgba(2,6,23,.10); border-color:#e6edff; }
  .section-body{ padding:16px; }

  .row-top{ display:flex; justify-content:space-between; align-items:start; gap:10px; }
  .name{ font-weight:800; letter-spacing:-.01em; }
  .meta{ color:var(--muted); font-size:.9rem; display:flex; gap:.6rem; margin-top:2px; align-items:center; flex-wrap:wrap; }

  /* Estado pill */
  .pill{
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.22rem .48rem; border-radius:999px; font-size:.72rem; font-weight:800; border:1px solid;
  }
  .pill.on  { background:var(--soft-green-bg); color:var(--soft-green-fg); border-color:var(--soft-green-br); }
  .pill.off { background:var(--soft-gray-bg);  color:var(--soft-gray-fg);  border-color:var(--soft-gray-br); }

  /* ========= Botones icon-only pastel ========= */
  .ibtn{
    width:36px; height:36px; border-radius:12px; border:1px solid transparent;
    display:inline-flex; align-items:center; justify-content:center;
    font-size:1rem; cursor:pointer; text-decoration:none;
    box-shadow:0 8px 18px rgba(2,6,23,.06);
    transition:background .15s,color .15s,border-color .15s,box-shadow .15s,transform .1s;
  }
  .ibtn:active{ transform:translateY(1px) }
  .ibtn-blue   { background:var(--soft-blue-bg);   color:var(--soft-blue-fg);   border-color:var(--soft-blue-br); }
  .ibtn-yellow { background:var(--soft-yellow-bg); color:var(--soft-yellow-fg); border-color:var(--soft-yellow-br); }
  .ibtn-green  { background:var(--soft-green-bg);  color:var(--soft-green-fg);  border-color:var(--soft-green-br); }
  .ibtn-red    { background:var(--soft-red-bg);    color:var(--soft-red-fg);    border-color:var(--soft-red-br); }
  .ibtn:hover  { background:#fff; color:#0f172a; border-color:#e5e7eb; box-shadow:0 12px 26px rgba(2,6,23,.14); }

  .actions{ display:flex; gap:8px; align-items:center; }

  /* Alert bonita */
  .alert{ border-radius:14px; border:1px solid var(--line); box-shadow:0 8px 18px rgba(2,6,23,.06) }
</style>
@endpush

@section('content')
<div class="wrap">
  {{-- Alertas --}}
  @if(session('ok'))
    <div class="alert alert-success mb-3">{{ session('ok') }}</div>
  @endif

  {{-- HERO --}}
  <div class="hero">
    <div>
      <h1 class="hero-title">
        <span class="hero-icon"><i class="bi bi-layout-text-window-reverse"></i></span>
        Secciones
      </h1>
      <div class="hero-hint">Gestiona el contenido que aparece en tu landing.</div>
    </div>
    <a class="btnP btnP-blue" href="{{ route('panel.landing.create') }}">
      <i class="bi bi-plus-lg"></i> Nueva sección
    </a>
  </div>

  {{-- GRID --}}
  <div class="grid">
    @forelse($sections as $s)
      <div class="section">
        <div class="section-body">
          <div class="row-top">
            <div>
              <div class="name">{{ $s->name }}</div>
              <div class="meta">
                <span><i class="bi bi-grid-1x2"></i> {{ $s->layout }}</span>
                <span><i class="bi bi-list-ul"></i> {{ $s->items_count }} ítems</span>
                <span class="pill {{ $s->is_active ? 'on':'off' }}">
                  <i class="bi {{ $s->is_active ? 'bi-check2-circle':'bi-pause-circle' }}"></i>
                  {{ $s->is_active ? 'Activo' : 'Inactivo' }}
                </span>
              </div>
            </div>

            {{-- Botones icon-only --}}
            <div class="actions">
              {{-- Editar --}}
              <a href="{{ route('panel.landing.edit',$s) }}" class="ibtn ibtn-blue" title="Editar" aria-label="Editar">
                <i class="bi bi-pencil"></i>
              </a>

              {{-- Activar/Desactivar --}}
              <form action="{{ route('panel.landing.toggle',$s) }}" method="POST" class="d-inline" title="{{ $s->is_active ? 'Desactivar' : 'Activar' }}">
                @csrf
                <button type="submit" class="ibtn {{ $s->is_active ? 'ibtn-yellow':'ibtn-green' }}" aria-label="{{ $s->is_active ? 'Desactivar' : 'Activar' }}">
                  <i class="bi {{ $s->is_active ? 'bi-toggle2-off':'bi-toggle2-on' }}"></i>
                </button>
              </form>

              {{-- Eliminar --}}
              <form action="{{ route('panel.landing.destroy',$s) }}" method="POST" class="d-inline"
                    onsubmit="return confirm('¿Eliminar sección \"{{ $s->name }}\"?');" title="Eliminar">
                @csrf @method('DELETE')
                <button type="submit" class="ibtn ibtn-red" aria-label="Eliminar">
                  <i class="bi bi-trash3"></i>
                </button>
              </form>
            </div>
          </div>

          @if(!empty($s->summary))
            <div class="mt-2 small" style="color:var(--muted)">{{ $s->summary }}</div>
          @endif
        </div>
      </div>
    @empty
      <div class="section">
        <div class="section-body" style="text-align:center">
          <div class="mb-1" style="font-weight:800; letter-spacing:-.01em">Sin secciones</div>
          <div class="mb-3" style="color:var(--muted)">Crea tu primera sección para empezar a construir la landing.</div>
          <a class="btnP btnP-blue" href="{{ route('panel.landing.create') }}">
            <i class="bi bi-plus-lg"></i> Nueva sección
          </a>
        </div>
      </div>
    @endforelse
  </div>
</div>
@endsection
