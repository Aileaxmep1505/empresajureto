@extends('layouts.app')

@section('title', 'WMS · Bodega')

@section('content')
<div class="wms-shell">
  <div class="wms-header">
    <div>
      <h1 class="wms-title">WMS · Bodega</h1>
      <p class="wms-sub">Control de inventario, operaciones y análisis del almacén en un solo panel.</p>
    </div>

    <div class="wms-header-actions">
      <a href="{{ route('admin.wms.search.view') }}" class="wms-btn wms-btn-primary">
        <span class="wms-btn-ico" aria-hidden="true">
          {{-- Lupa --}}
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="7"/>
            <path d="M21 21l-4.3-4.3"/>
          </svg>
        </span>
        <span>Búsqueda rápida</span>
      </a>

      <a href="{{ route('admin.wms.pick.entry') }}" class="wms-btn wms-btn-ghost">
        <span class="wms-btn-ico" aria-hidden="true">
          {{-- Caja / picking --}}
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 7l9-4 9 4-9 4-9-4z"/>
            <path d="M3 7v10l9 4 9-4V7"/>
          </svg>
        </span>
        <span>Picking guiado</span>
      </a>
    </div>
  </div>

  <div class="wms-layout">
    {{-- Card grande: Inventario --}}
    <a href="{{ route('admin.wms.search.view') }}" class="wms-card wms-card-main">
      <div class="wms-card-badge wms-badge-blue" aria-hidden="true">
        {{-- Icono box --}}
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M3 7l9-4 9 4-9 4-9-4z"/>
          <path d="M3 7v10l9 4 9-4V7"/>
          <path d="M12 11v10"/>
        </svg>
      </div>

      <div class="wms-card-body">
        <h2 class="wms-card-title">Buscar producto</h2>
        <p class="wms-card-text">
          Control total de productos, ubicaciones y movimientos de stock en tiempo real.
        </p>
      </div>

      <div class="wms-card-link">
        <span>Explorar</span>
        <span class="wms-card-link-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 5l7 7-7 7"/>
            <path d="M5 12h10"/>
          </svg>
        </span>
      </div>
    </a>

    {{-- Card: Operaciones --}}
    <a href="{{ route('admin.wms.pick.entry') }}" class="wms-card wms-card-small">
      <div class="wms-card-badge wms-badge-green" aria-hidden="true">
        {{-- Camión / operaciones --}}
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="7" width="9" height="7" rx="1"/>
          <path d="M12 10h4l3 3v4"/>
          <circle cx="7.5" cy="18.5" r="1.5"/>
          <circle cx="17.5" cy="18.5" r="1.5"/>
        </svg>
      </div>

      <div class="wms-card-body">
        <h2 class="wms-card-title">Operaciones</h2>
        <p class="wms-card-text">
          Recepción, despacho y movimientos guiados para el equipo de almacén.
        </p>
      </div>

      <div class="wms-card-link">
        <span>Acceder</span>
        <span class="wms-card-link-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 5l7 7-7 7"/>
            <path d="M5 12h10"/>
          </svg>
        </span>
      </div>
    </a>

    {{-- Card: Reportes (puedes cambiar la ruta cuando tengas módulo) --}}
    <a href="#" class="wms-card wms-card-small">
      <div class="wms-card-badge wms-badge-purple" aria-hidden="true">
        {{-- Gráfica / reportes --}}
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 19V5"/>
          <path d="M8 19v-6"/>
          <path d="M12 19v-10"/>
          <path d="M16 19v-3"/>
          <path d="M20 19V8"/>
        </svg>
      </div>

      <div class="wms-card-body">
        <h2 class="wms-card-title">Reportes</h2>
        <p class="wms-card-text">
          Analíticas y métricas clave del almacén para decisiones rápidas.
        </p>
      </div>

      <div class="wms-card-link">
        <span>Ver datos</span>
        <span class="wms-card-link-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 5l7 7-7 7"/>
            <path d="M5 12h10"/>
          </svg>
        </span>
      </div>
    </a>
  </div>
</div>
@endsection

@push('styles')
<style>
  :root{
    --wms-page-bg:#f5f6fb;
    --wms-card-bg:#ffffff;
    --wms-card-bg-soft:#fafbff;
    --wms-ink:#111827;
    --wms-muted:#6b7280;
    --wms-line:#e5e7f2;
    --wms-shadow:0 22px 60px rgba(15,23,42,.10);
    --wms-radius:26px;

    --wms-blue:#2563eb;
    --wms-green:#16a34a;
    --wms-purple:#8b5cf6;
  }

  .wms-shell{
    max-width:1100px;
    margin:0 auto;
    padding:18px 18px 28px;
    background:var(--wms-page-bg);
    border-radius:24px;
    box-shadow:0 18px 40px rgba(15,23,42,.06);
  }

  .wms-header{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:14px;
    flex-wrap:wrap;
    margin-bottom:18px;
  }
  .wms-title{
    margin:0;
    font-size:1.3rem;
    font-weight:800;
    letter-spacing:-.03em;
    color:var(--wms-ink);
  }
  .wms-sub{
    margin:4px 0 0;
    font-size:.9rem;
    color:var(--wms-muted);
  }

  .wms-header-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
  }

  /* Botones */
  .wms-btn{
    border-radius:999px;
    padding:9px 14px;
    font-size:.9rem;
    font-weight:700;
    display:inline-flex;
    align-items:center;
    gap:8px;
    text-decoration:none;
    cursor:pointer;
    border:1px solid transparent;
    transition:
      transform .16s ease,
      box-shadow .16s ease,
      background .18s ease,
      border-color .18s ease,
      filter .18s ease;
  }
  .wms-btn-ico{
    width:18px;
    height:18px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
  }
  .wms-btn-ico svg{
    width:18px;
    height:18px;
  }
  .wms-btn-primary{
    background:linear-gradient(135deg,var(--wms-blue),#1d4ed8);
    color:#eff6ff;
    box-shadow:0 14px 30px rgba(37,99,235,.30);
  }
  .wms-btn-primary:hover{
    transform:translateY(-1px);
    box-shadow:0 18px 40px rgba(37,99,235,.38);
    filter:brightness(1.05);
  }
  .wms-btn-ghost{
    background:#ffffff;
    border-color:var(--wms-line);
    color:var(--wms-ink);
  }
  .wms-btn-ghost:hover{
    transform:translateY(-1px);
    box-shadow:0 12px 26px rgba(15,23,42,.08);
  }

  /* Layout principal: tipo 2x1 */
  .wms-layout{
    display:grid;
    grid-template-columns:minmax(0,2fr) minmax(0,1.2fr);
    grid-template-rows:repeat(2,minmax(0,1fr));
    gap:18px;
  }

  /* Cards base */
  .wms-card{
    position:relative;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    padding:22px 22px 20px;
    border-radius:var(--wms-radius);
    background:
      radial-gradient(circle at 0 0, rgba(255,255,255,.85) 0, transparent 55%),
      radial-gradient(circle at 100% 0, rgba(148,163,253,.10) 0, transparent 55%),
      var(--wms-card-bg-soft);
    border:1px solid var(--wms-line);
    box-shadow:0 14px 34px rgba(15,23,42,.04);
    text-decoration:none;
    overflow:hidden;
    transition:
      transform .2s ease,
      box-shadow .2s ease,
      border-color .18s ease,
      background .2s ease;
  }

  /* Glow animado muy sutil */
  .wms-card::after{
    content:"";
    position:absolute;
    inset:-1px;
    background:radial-gradient(circle at 0 0, rgba(59,130,246,.18), transparent 55%);
    opacity:0;
    transition:opacity .25s ease;
    pointer-events:none;
  }

  .wms-card-main{
    grid-row:1 / span 2;
    grid-column:1 / 2;
  }
  .wms-card-small{
    grid-column:2 / 3;
  }

  .wms-card:hover{
    transform:translateY(-3px);
    box-shadow:0 22px 60px rgba(15,23,42,.14);
    border-color:#d4ddff;
    background:var(--wms-card-bg);
  }
  .wms-card:hover::after{
    opacity:1;
  }

  .wms-card-badge{
    width:54px;
    height:54px;
    border-radius:20px;
    display:flex;
    align-items:center;
    justify-content:center;
    margin-bottom:18px;
    box-shadow:0 14px 30px rgba(15,23,42,.10);
  }
  .wms-card-badge svg{
    width:26px;
    height:26px;
  }

  .wms-badge-blue{
    background:linear-gradient(135deg,#e0ebff,#eff4ff);
    color:var(--wms-blue);
    border:1px solid #c7d2fe;
  }
  .wms-badge-green{
    background:linear-gradient(135deg,#dcfce7,#f0fdf4);
    color:var(--wms-green);
    border:1px solid #bbf7d0;
  }
  .wms-badge-purple{
    background:linear-gradient(135deg,#ede9fe,#faf5ff);
    color:var(--wms-purple);
    border:1px solid #e9d5ff;
  }

  .wms-card-body{
    max-width:420px;
  }
  .wms-card-title{
    margin:0 0 6px;
    font-size:1.1rem;
    font-weight:800;
    color:var(--wms-ink);
    letter-spacing:-.02em;
  }
  .wms-card-text{
    margin:0;
    font-size:.94rem;
    color:var(--wms-muted);
    line-height:1.4;
  }

  .wms-card-link{
    margin-top:22px;
    display:inline-flex;
    align-items:center;
    gap:6px;
    font-size:.9rem;
    font-weight:600;
    color:#6b7280;
  }
  .wms-card-link-ico{
    width:16px;
    height:16px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
  }
  .wms-card-link-ico svg{
    width:16px;
    height:16px;
    transition:transform .2s ease;
  }

  /* 👉 Flecha se mueve a la derecha en hover */
  .wms-card:hover .wms-card-link-ico svg{
    transform:translateX(4px);
  }

  @media (max-width: 960px){
    .wms-shell{
      margin:0 8px;
      padding:16px 14px 22px;
      border-radius:18px;
    }
    .wms-layout{
      grid-template-columns:minmax(0,1fr);
      grid-template-rows:none;
    }
    .wms-card-main{
      grid-column:auto;
      grid-row:auto;
    }
    .wms-card-small{
      grid-column:auto;
    }
  }
</style>
@endpush
