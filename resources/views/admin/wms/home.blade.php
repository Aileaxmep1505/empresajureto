@extends('layouts.app')

@section('title', 'WMS · Bodega')

@section('content')
<div class="wms-wrap">
  <div class="wms-top">
    <div>
      <div class="wms-title">WMS · Bodega</div>
      <div class="wms-sub">Búsqueda por ubicación (QR) y picking guiado tipo Amazon/ML.</div>
    </div>

    <div class="wms-top-actions">
      <a href="{{ route('admin.wms.search.view') }}" class="btn btn-primary">
        🔎 Buscar producto
      </a>
      <a href="{{ route('admin.wms.pick.entry') }}" class="btn btn-ghost">
        📦 Picking
      </a>
    </div>
  </div>

  <div class="grid">
    <a class="card" href="{{ route('admin.wms.search.view') }}">
      <div class="card-ic">🔎</div>
      <div class="card-tt">Buscador con ubicación</div>
      <div class="card-tx">Busca por nombre / SKU / GTIN y obtén su ubicación + “Llévame”.</div>
      <div class="card-ft">Abrir →</div>
    </a>

    <a class="card" href="{{ route('admin.wms.pick.entry') }}">
      <div class="card-ic">📦</div>
      <div class="card-tt">Picking guiado</div>
      <div class="card-tx">Escanea ubicación → escanea producto → confirma cantidad → siguiente.</div>
      <div class="card-ft">Abrir →</div>
    </a>

    <div class="card card-soft">
      <div class="card-ic">🏷️</div>
      <div class="card-tt">Tip</div>
      <div class="card-tx">
        Imprime el <strong>código grande</strong> (A-03-S2-...) y un QR por stand/bin.
        El QR debe abrir la ubicación para fijar “dónde estás”.
      </div>
      <div class="card-ft">UX rápida</div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  :root{
    --bg:#f6f8fc;
    --card:#fff;
    --ink:#0b1220;
    --muted:#64748b;
    --line:#e6eaf2;
    --brand:#2563eb;
    --brand2:#1d4ed8;
    --shadow:0 18px 55px rgba(2,6,23,.08);
    --radius:18px;
  }

  .wms-wrap{max-width:1100px;margin:0 auto;padding:18px 14px 30px}
  .wms-top{
    display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;
    margin-bottom:14px;
  }
  .wms-title{font-weight:800;font-size:1.1rem;color:var(--ink)}
  .wms-sub{color:var(--muted);font-size:.9rem;margin-top:2px}
  .wms-top-actions{display:flex;gap:10px;flex-wrap:wrap}

  .btn{
    border:0;border-radius:999px;padding:10px 14px;font-weight:700;
    display:inline-flex;gap:8px;align-items:center;cursor:pointer;
    transition:transform .12s ease, box-shadow .12s ease, background .15s ease, border-color .15s ease;
    white-space:nowrap;text-decoration:none;
  }
  .btn-primary{background:var(--brand);color:#eff6ff;box-shadow:0 14px 30px rgba(37,99,235,.30)}
  .btn-primary:hover{transform:translateY(-1px);box-shadow:0 18px 38px rgba(37,99,235,.34)}
  .btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink)}
  .btn-ghost:hover{transform:translateY(-1px);box-shadow:0 10px 22px rgba(2,6,23,.06)}

  .grid{
    display:grid;grid-template-columns:repeat(12,1fr);gap:12px;
  }
  .card{
    grid-column:span 6;
    background:var(--card);
    border:1px solid var(--line);
    border-radius:var(--radius);
    padding:14px 14px;
    box-shadow:0 10px 24px rgba(2,6,23,.05);
    text-decoration:none;
    transition:transform .14s ease, box-shadow .14s ease, border-color .14s ease;
    position:relative;overflow:hidden;
  }
  .card:hover{transform:translateY(-2px);box-shadow:var(--shadow);border-color:#dbeafe}
  .card-ic{
    width:46px;height:46px;border-radius:14px;
    display:flex;align-items:center;justify-content:center;
    background:linear-gradient(135deg,#dbeafe,#fff);
    border:1px solid #dbeafe;
    font-size:1.5rem;
    margin-bottom:8px;
  }
  .card-tt{font-weight:800;color:var(--ink);margin-bottom:2px}
  .card-tx{color:var(--muted);font-size:.9rem;line-height:1.35}
  .card-ft{margin-top:10px;color:var(--brand2);font-weight:800;font-size:.9rem}

  .card-soft{
    grid-column:span 12;
    background:radial-gradient(circle at top left,#dbeafe 0,#eff6ff 35%,#fff 85%);
  }

  @media (max-width: 900px){
    .card{grid-column:span 12}
  }
</style>
@endpush
