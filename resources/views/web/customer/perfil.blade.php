@extends('layouts.web')
@section('title','Mi Cuenta')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet"/>

<style>
  /* ====================== SCOPE: #account ====================== */
  :root {
    --bg: #f9fafb;
    --card: #ffffff;
    --ink: #333333;
    --muted: #888888;
    --line: #ebebeb;
    --blue: #007aff;
    --blue-soft: #e6f0ff;
    --success: #15803d;
    --success-soft: #e6ffe6;
    --danger: #ff4a4a;
    --danger-soft: #ffebeb;
  }

  #account {
    font-family: 'Quicksand', sans-serif;
    color: var(--ink);
    background-color: var(--bg);
    min-height: 100vh;
    padding-bottom: 60px;
  }

  /* Reset básico */
  #account * { box-sizing: border-box; }
  #account h1, #account h2, #account h3 { color: #111111; font-weight: 700; margin: 0; }
  #account a { text-decoration: none; }

  /* Hero */
  #account .hero {
    background-color: var(--card);
    border-bottom: 1px solid var(--line);
    padding: 48px 24px 32px;
    margin-bottom: 32px;
  }
  #account .hero__inner {
    max-width: 1200px;
    margin: 0 auto;
  }
  #account .hero h1 { font-size: clamp(28px, 4vw, 42px); margin-bottom: 8px; }
  #account .hero p { color: var(--muted); font-size: clamp(15px, 1.5vw, 18px); font-weight: 500; margin: 0; }

  /* Layout */
  #account .wrap { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
  #account .layout { display: grid; grid-template-columns: 280px 1fr; gap: 32px; }
  @media (max-width: 980px) { #account .layout { grid-template-columns: 1fr; } }

  /* Cards */
  #account .card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  #account .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
  }
  
  /* Cabecera de la tarjeta principal (Filtro rediseñado) */
  #account .card-head {
    padding: 20px 24px;
    border-bottom: 1px solid var(--line);
    background-color: #fdfdfd;
    border-radius: 16px 16px 0 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
  }
  
  /* Filtro Enterprise */
  #account .filter-group {
    display: flex;
    align-items: center;
    gap: 16px;
  }
  #account .filter-group label {
    font-size: 12px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  #account .filter-input-wrap {
    display: flex;
    align-items: center;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 8px;
    padding: 4px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
  }
  #account .filter-input-wrap:focus-within {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }
  #account .filter-input-wrap input[type="month"] {
    border: none;
    background: transparent;
    padding: 6px 12px;
    font-family: 'Quicksand', sans-serif;
    font-weight: 600;
    font-size: 14px;
    color: var(--ink);
    outline: none;
  }
  #account .filter-input-wrap .btn {
    padding: 8px 16px;
    font-size: 13px;
    border-radius: 6px;
    margin-left: 4px;
  }

  #account .card-body { padding: 24px; }

  /* Aside Menu */
  #account .aside { position: sticky; top: 24px; display: flex; flex-direction: column; gap: 24px; }
  #account .userbox { display: flex; align-items: center; gap: 16px; padding: 20px; }
  #account .avatar {
    width: 48px; height: 48px;
    border-radius: 999px;
    background: var(--blue-soft);
    color: var(--blue);
    font-weight: 700;
    font-size: 18px;
    display: grid;
    place-items: center;
  }
  #account .menu { padding: 12px; display: flex; flex-direction: column; gap: 4px; }
  #account .menu a {
    display: flex; align-items: center; gap: 12px; padding: 12px 16px;
    border-radius: 8px; color: var(--ink); font-weight: 600; font-size: 15px;
    transition: background-color 0.2s ease, color 0.2s ease;
  }
  #account .menu a:hover { background: var(--bg); }
  #account .menu a.active { background: var(--blue-soft); color: var(--blue); }

  /* Buttons */
  #account .btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    font-family: 'Quicksand', sans-serif; font-weight: 600; font-size: 14px;
    padding: 10px 18px; border-radius: 8px; cursor: pointer; text-decoration: none;
    transition: transform 0.1s ease, background-color 0.2s ease;
    border: 1px solid transparent; outline: none;
  }
  #account .btn:active { transform: scale(0.98) translateY(-1px); }
  #account .btn-primary { background: var(--blue); color: #ffffff; }
  #account .btn-primary:hover { background: #0062cc; }
  #account .btn-ghost { background: transparent; color: #555555; }
  #account .btn-ghost:hover { background: #f9fafb; }
  #account .btn-outline { background: var(--card); border-color: var(--blue); color: var(--blue); }
  #account .btn-outline:hover { background: var(--blue-soft); }

  /* Badges */
  #account .badge {
    display: inline-flex; padding: 4px 10px; border-radius: 999px;
    font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
  }
  #account .badge-success { background: var(--success-soft); color: var(--success); }
  #account .badge-danger { background: var(--danger-soft); color: var(--danger); }
  #account .badge-info { background: var(--blue-soft); color: var(--blue); }
  #account .badge-muted { background: var(--bg); color: var(--muted); border: 1px solid var(--line); }

  /* Datos / Profile info */
  #account .kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
  #account .kpi {
    background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 20px;
  }
  #account .kpi small { color: var(--muted); font-size: 13px; font-weight: 600; text-transform: uppercase; }
  #account .kpi strong { display: block; font-size: clamp(24px, 2vw, 28px); margin-top: 8px; color: #111111; }

  /* Panels */
  #account .tpanel { display: none; animation: fadeIn 0.3s ease; }
  #account .tpanel.active { display: block; }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }

  /* Table */
  #account .table-wrap { overflow-x: auto; }
  #account .table { width: 100%; border-collapse: separate; border-spacing: 0 12px; margin-top: -12px; }
  #account .table thead th { color: var(--muted); font-weight: 700; text-align: left; font-size: 13px; padding: 0 20px; text-transform: uppercase; }
  #account .tr { background: var(--card); border: 1px solid var(--line); border-radius: 12px; transition: transform 0.2s ease; }
  #account .tr:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
  #account .tr td { padding: 20px; vertical-align: middle; font-weight: 500; font-size: 15px; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }
  #account .tr td:first-child { border-left: 1px solid var(--line); border-radius: 12px 0 0 12px; }
  #account .tr td:last-child { border-right: 1px solid var(--line); border-radius: 0 12px 12px 0; text-align: right; }

  #account .empty { padding: 48px 24px; border: 1px dashed var(--line); border-radius: 12px; color: var(--muted); text-align: center; font-weight: 500; background: var(--bg); }

  /* ===== Modal Seguimiento ===== */
  #account .modal { position: fixed; inset: 0; z-index: 9999; display: none; align-items: center; justify-content: center; }
  #account .modal[data-open="1"] { display: flex; }
  
  #account .modal__backdrop { 
    position: absolute; inset: 0; 
    background: rgba(17, 17, 17, 0.4); backdrop-filter: blur(4px); 
    animation: fadeInModal 0.3s ease;
  }

  #account .modal__panel {
    position: relative; width: 100%; max-width: 560px; max-height: 85vh; 
    background: var(--card); border-radius: 20px; 
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    display: flex; flex-direction: column; 
    animation: slideUpModal 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  }

  @keyframes fadeInModal { from { opacity: 0; } to { opacity: 1; } }
  @keyframes slideUpModal { from { transform: translateY(30px) scale(0.98); opacity: 0; } to { transform: translateY(0) scale(1); opacity: 1; } }

  #account .modal__head { 
    padding: 24px; border-bottom: 1px solid var(--line); 
    display: flex; align-items: center; justify-content: space-between; 
  }
  #account .modal__head h3 { font-size: 20px; }
  #account .close-btn {
    background: var(--bg); color: var(--muted); border: none; width: 36px; height: 36px;
    border-radius: 50%; font-size: 20px; display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background 0.2s, color 0.2s;
  }
  #account .close-btn:hover { background: var(--line); color: var(--ink); }

  #account .modal__body { padding: 32px 24px; overflow-y: auto; }
  
  #account .trk-meta { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
  
  #account .progress {
    position: relative; height: 8px; border-radius: 999px; background: var(--bg); 
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 32px;
  }
  #account .progress b { 
    position: absolute; height: 100%; left: 0; top: 0; width: 0%; 
    background: var(--blue); border-radius: 999px; transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1); 
  }
  
  /* Timeline */
  #account .tl { list-style: none; padding: 0; margin: 0; position: relative; padding-left: 12px; }
  #account .tl::before {
    content: ''; position: absolute; left: 15px; top: 8px; bottom: 0; width: 2px; background: var(--line); z-index: 0;
  }
  #account .tl__item { position: relative; padding-left: 28px; margin-bottom: 24px; z-index: 1; }
  #account .tl__item:last-child { margin-bottom: 0; }
  #account .tl__item::before {
    content: ''; position: absolute; left: -1.5px; top: 4px; width: 12px; height: 12px;
    border-radius: 50%; background: var(--card); border: 2px solid var(--blue); z-index: 2;
    box-shadow: 0 0 0 4px var(--card); 
  }
  #account .tl__item .h { font-weight: 700; color: #111111; font-size: 16px; margin-bottom: 2px; }
  #account .tl__item time { color: var(--muted); font-size: 13px; font-weight: 500; display: block; }
</style>

<div id="account">
  {{-- Hero --}}
  <section class="hero">
    <div class="hero__inner">
      <h1>Mi cuenta</h1>
      <p>Consulta pedidos, gestiona tus datos y descargas de facturas desde un solo lugar.</p>
    </div>
  </section>

  <div class="wrap">
    <div class="layout">
      {{-- ===== ASIDE ===== --}}
      <aside class="aside">
        <div class="card userbox">
          @php $initial = strtoupper(mb_substr(($user->name ?? $user->email),0,1,'UTF-8')); @endphp
          <div class="avatar">{{ $initial }}</div>
          <div>
            <div style="font-weight:700; color: #111111;">{{ $user->name ?? 'Mi cuenta' }}</div>
            <small style="color:var(--muted); font-weight: 500;">{{ $user->email }}</small>
          </div>
        </div>

        <nav class="card menu">
          <a href="#t-resumen"      class="js-gotab {{ $activeTab==='resumen'?'active':'' }}"      data-tab="resumen">Resumen</a>
          <a href="#t-pedidos"      class="js-gotab {{ $activeTab==='pedidos'?'active':'' }}"      data-tab="pedidos">Mis pedidos</a>
          <a href="#t-datos"        class="js-gotab {{ $activeTab==='datos'?'active':'' }}"        data-tab="datos">Datos de cuenta</a>
          <a href="#t-facturacion"  class="js-gotab {{ $activeTab==='facturacion'?'active':'' }}"  data-tab="facturacion">Datos de facturación</a>
          <a href="#t-facturas"     class="js-gotab {{ $activeTab==='facturas'?'active':'' }}"     data-tab="facturas">Mis facturas</a>
          <a href="#t-direcciones"  class="js-gotab {{ $activeTab==='direcciones'?'active':'' }}"  data-tab="direcciones">Direcciones</a>
        </nav>
      </aside>

      {{-- ===== MAIN ===== --}}
      <section>
        <div class="card">
          <div class="card-head">
            <h2 style="font-size: 18px;">Gestión de Panel</h2>
            <form method="get" action="{{ route('customer.profile') }}">
              <div class="filter-group">
                <label for="ym-filter">Filtro por periodo</label>
                <div class="filter-input-wrap">
                  <input type="month" id="ym-filter" name="ym" value="{{ $ym }}">
                  @if($activeTab)<input type="hidden" name="tab" value="{{ $activeTab }}">@endif
                  <button type="submit" class="btn btn-primary">Aplicar</button>
                </div>
              </div>
            </form>
          </div>

          <div class="card-body">
            {{-- ===== RESUMEN ===== --}}
            <div id="t-resumen" class="tpanel {{ $activeTab==='resumen'?'active':'' }}">
              
              <h3 style="margin-bottom: 24px;">Últimos pedidos</h3>
              @if($orders->count())
                <div class="table-wrap">
                  <table class="table">
                    <thead><tr><th>ID</th><th>No. pedido</th><th>Fecha</th><th>Estatus</th><th>Total</th><th></th></tr></thead>
                    <tbody>
                    @foreach($orders->take(5) as $o)
                      <tr class="tr">
                        <td style="color:var(--muted);">{{ $o->id }}</td>
                        <td>
                          @if(\Illuminate\Support\Facades\Route::has('customer.orders.show'))
                            <a style="font-weight:700; color:var(--ink);" href="{{ route('customer.orders.show',$o) }}">
                              #{{ str_pad($o->id,6,'0',STR_PAD_LEFT) }}
                            </a>
                          @else
                            <span style="font-weight:700;">#{{ str_pad($o->id,6,'0',STR_PAD_LEFT) }}</span>
                          @endif
                        </td>
                        <td>{{ $o->created_at?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                          @php $st = strtolower((string)$o->status); @endphp
                          <span class="badge {{ $st==='cancelado'?'badge-danger':($st==='entregado'?'badge-success':'badge-info') }}">
                            {{ ucfirst($o->status ?? '—') }}
                          </span>

                          @if(!empty($o->shipping_code))
                            <div class="badge badge-muted" style="margin-top:8px; display:block; width:fit-content;">Guía: {{ $o->shipping_code }}</div>
                          @endif
                          @if(!empty($o->shipping_name) || !empty($o->shipping_service))
                            <div class="badge badge-muted" style="margin-top:4px; display:block; width:fit-content;">
                              {{ $o->shipping_name ?? '' }}{{ $o->shipping_service ? ' — '.$o->shipping_service : '' }}
                            </div>
                          @endif
                          @if(!empty($o->shipping_eta))
                            <div class="badge badge-muted" style="margin-top:4px; display:block; width:fit-content;">ETA: {{ $o->shipping_eta }}</div>
                          @endif
                        </td>
                        <td style="font-weight: 700;">${{ number_format($o->total,2) }}</td>
                        <td>
                          <div style="display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap">
                            @if(\Illuminate\Support\Facades\Route::has('customer.orders.show'))
                              <a class="btn btn-ghost" href="{{ route('customer.orders.show',$o) }}">Ver detalle</a>
                            @endif
                            @if(\Illuminate\Support\Facades\Route::has('customer.orders.tracking'))
                              <button class="btn btn-primary js-track"
                                      data-url="{{ route('customer.orders.tracking',$o) }}"
                                      data-label="{{ $o->shipping_label_url ?? '' }}">
                                Seguimiento
                              </button>
                            @endif
                            @if(\Illuminate\Support\Facades\Route::has('customer.orders.label') && !empty($o->shipping_label_url))
                              <a class="btn btn-ghost" href="{{ route('customer.orders.label',$o) }}" target="_blank" rel="noopener">PDF</a>
                            @endif
                          </div>
                        </td>
                      </tr>
                    @endforeach
                    </tbody>
                  </table>
                </div>
              @else
                <div class="empty">Aún no tienes pedidos.</div>
              @endif
            </div>

            {{-- ===== PEDIDOS ===== --}}
            <div id="t-pedidos" class="tpanel {{ $activeTab==='pedidos'?'active':'' }}">
              @if($orders->count())
                <div class="table-wrap">
                  <table class="table">
                    <thead><tr><th>ID</th><th>No. pedido</th><th>Fecha</th><th>Estatus</th><th>Factura</th><th>Total</th><th></th></tr></thead>
                    <tbody>
                    @foreach($orders as $o)
                      <tr class="tr">
                        <td style="color:var(--muted);">{{ $o->id }}</td>
                        <td>
                          @if(\Illuminate\Support\Facades\Route::has('customer.orders.show'))
                            <a style="font-weight:700; color:var(--ink);" href="{{ route('customer.orders.show',$o) }}">
                              #{{ str_pad($o->id,6,'0',STR_PAD_LEFT) }}
                            </a>
                          @else
                            <span style="font-weight:700;">#{{ str_pad($o->id,6,'0',STR_PAD_LEFT) }}</span>
                          @endif
                        </td>
                        <td>{{ $o->created_at?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                          @php $st = strtolower((string)$o->status); @endphp
                          <span class="badge {{ $st==='cancelado'?'badge-danger':($st==='entregado'?'badge-success':'badge-info') }}">
                            {{ ucfirst($o->status ?? '—') }}
                          </span>
                          @if(!empty($o->shipping_code))
                            <div class="badge badge-muted" style="margin-top:8px; display:block; width:fit-content;">Guía: {{ $o->shipping_code }}</div>
                          @endif
                          @if(!empty($o->shipping_name) || !empty($o->shipping_service))
                            <div class="badge badge-muted" style="margin-top:4px; display:block; width:fit-content;">
                              {{ $o->shipping_name ?? '' }}{{ $o->shipping_service ? ' — '.$o->shipping_service : '' }}
                            </div>
                          @endif
                          @if(!empty($o->shipping_eta))
                            <div class="badge badge-muted" style="margin-top:4px; display:block; width:fit-content;">ETA: {{ $o->shipping_eta }}</div>
                          @endif
                        </td>
                        <td><span class="badge badge-muted">—</span></td>
                        <td style="font-weight: 700;">${{ number_format($o->total,2) }}</td>
                        <td>
                          <div style="display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap">
                            @if(\Illuminate\Support\Facades\Route::has('customer.orders.show'))
                              <a class="btn btn-ghost" href="{{ route('customer.orders.show',$o) }}">Ver detalle</a>
                            @endif
                            @if(\Illuminate\Support\Facades\Route::has('customer.orders.tracking'))
                              <button class="btn btn-primary js-track"
                                      data-url="{{ route('customer.orders.tracking',$o) }}"
                                      data-label="{{ $o->shipping_label_url ?? '' }}">
                                Seguimiento
                              </button>
                            @endif
                            @if(\Illuminate\Support\Facades\Route::has('customer.orders.label') && !empty($o->shipping_label_url))
                              <a class="btn btn-ghost" href="{{ route('customer.orders.label',$o) }}" target="_blank" rel="noopener">PDF</a>
                            @endif
                          </div>
                        </td>
                      </tr>
                    @endforeach
                    </tbody>
                  </table>
                </div>
              @else
                <div class="empty">No hay pedidos para el filtro seleccionado.</div>
              @endif
            </div>

            {{-- ===== DATOS ===== --}}
            <div id="t-datos" class="tpanel {{ $activeTab==='datos'?'active':'' }}">
              <div class="kpis">
                <div class="kpi"><small>Nombre</small><strong>{{ $user->name ?? '—' }}</strong></div>
                <div class="kpi"><small>Correo</small><strong style="font-size: 18px;">{{ $user->email }}</strong></div>
                <div class="kpi"><small>Registrado</small><strong>{{ $user->created_at?->format('d/m/Y') ?? '—' }}</strong></div>
                <div class="kpi"><small>Último acceso</small><strong>{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</strong></div>
              </div>
              <div style="margin-top:24px; display:flex; gap:12px;">
                @if(\Illuminate\Support\Facades\Route::has('customer.welcome'))
                  <a href="{{ route('customer.welcome') }}" class="btn btn-outline">Ir al Inicio</a>
                @endif
              </div>
            </div>

            {{-- ===== FACTURACIÓN ===== --}}
            <div id="t-facturacion" class="tpanel {{ $activeTab==='facturacion'?'active':'' }}">
              @if($billingProfiles->count())
                <div style="display:flex; flex-direction:column; gap:16px;">
                  @foreach($billingProfiles as $bp)
                    <div class="card" style="padding: 24px;">
                      <div style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap">
                        <div>
                          <div style="font-weight:700; font-size:18px; margin-bottom:8px;">{{ $bp->razon_social }}</div>
                          <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px;">
                            <span class="badge badge-muted">RFC: {{ $bp->rfc }}</span>
                            <span class="badge badge-muted">Régimen: {{ $bp->regimen ?: '—' }}</span>
                            <span class="badge badge-muted">Uso CFDI: {{ $bp->uso_cfdi ?: '—' }}</span>
                          </div>
                          <div style="color:var(--muted); font-size:14px; font-weight:500;">
                            {{ $bp->direccion }} {{ $bp->colonia }}, {{ $bp->estado }} C.P. {{ $bp->zip }}
                          </div>
                        </div>
                        <div>
                          @if($bp->is_default) <span class="badge badge-success">Predeterminado</span> @endif
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>
              @else
                <div class="empty">Aún no tienes perfiles de facturación.</div>
              @endif
            </div>

            {{-- ===== FACTURAS ===== --}}
            <div id="t-facturas" class="tpanel {{ $activeTab==='facturas'?'active':'' }}">
              @if($invoices->count())
                <div class="table-wrap">
                  <table class="table">
                    <thead><tr><th>Serie/Folio</th><th>Fecha</th><th>Total</th><th>Descargas</th></tr></thead>
                    <tbody>
                    @foreach($invoices as $f)
                      <tr class="tr">
                        <td style="font-weight:700;">{{ ($f->serie ?? 'A').'-'.($f->folio ?? $f->id) }}</td>
                        <td>{{ $f->fecha?->format('d/m/Y') ?? $f->created_at?->format('d/m/Y') }}</td>
                        <td style="font-weight:700;">${{ number_format($f->total,2) }}</td>
                        <td>
                          <div style="display:flex; gap:8px; justify-content:flex-end;">
                            @if(\Illuminate\Support\Facades\Route::has('customer.invoices.pdf')) 
                              <a class="btn btn-outline" href="{{ route('customer.invoices.pdf',$f->id) }}">PDF</a> 
                            @endif
                            @if(\Illuminate\Support\Facades\Route::has('customer.invoices.xml')) 
                              <a class="btn btn-ghost" href="{{ route('customer.invoices.xml',$f->id) }}">XML</a> 
                            @endif
                          </div>
                        </td>
                      </tr>
                    @endforeach
                    </tbody>
                  </table>
                </div>
              @else
                <div class="empty">Aquí aparecerán tus facturas emitidas.</div>
              @endif
            </div>

            {{-- ===== DIRECCIONES ===== --}}
            <div id="t-direcciones" class="tpanel {{ $activeTab==='direcciones'?'active':'' }}">
              <div class="empty">Integra aquí tu listado de direcciones de envío si ya lo tienes en otra tabla.</div>
            </div>

          </div>
        </div>
      </section>
    </div>
  </div>

  {{-- ===== Modal Seguimiento ===== --}}
  <div class="modal" id="trkModal" data-open="0" aria-hidden="true">
    <div class="modal__backdrop"></div>
    <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="trkTitle">
      <div class="modal__head">
        <h3 id="trkTitle">Seguimiento de envío</h3>
        <button class="close-btn" id="trkClose" aria-label="Cerrar">&times;</button>
      </div>
      <div class="modal__body">
        <div class="trk-meta" id="trkMeta">
          <span class="badge badge-muted">Cargando…</span>
        </div>

        <div id="trkActions" style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom: 24px;"></div>

        <div class="progress"><b id="trkProgress" style="width:0%"></b></div>
        
        <ul class="tl" id="trkList">
          <li class="tl__item"><div class="h">Obteniendo eventos…</div><time>—</time></li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const root = document.getElementById('account');
  if(!root) return;

  const panels     = root.querySelectorAll('.tpanel');
  const asideLinks = root.querySelectorAll('.js-gotab');
  const form       = root.querySelector('.card-head form');

  function setActive(id){
    panels.forEach(p => p.classList.toggle('active', p.id === id));
    asideLinks.forEach(a => a.classList.toggle('active', a.getAttribute('href') === '#'+id));
    if(form){
      let h = form.querySelector('input[name="tab"]');
      if(!h){ h=document.createElement('input'); h.type='hidden'; h.name='tab'; form.appendChild(h); }
      h.value = id.replace('t-','');
    }
    history.replaceState(null, '', '#'+id);
  }

  asideLinks.forEach(a=> a.addEventListener('click', e=>{
    e.preventDefault();
    const id=(a.getAttribute('href')||'').slice(1);
    if(id) setActive(id);
    window.scrollTo({top:0, behavior:'smooth'});
  }));

  const fromHash = (location.hash||'').slice(1);
  if(fromHash && root.querySelector('#'+fromHash)) setActive(fromHash);

  // ===== Seguimiento (modal + fetch) =====
  const modal = document.getElementById('trkModal');
  const closeBtn = document.getElementById('trkClose');
  const meta = document.getElementById('trkMeta');
  const list = document.getElementById('trkList');
  const bar  = document.getElementById('trkProgress');
  const actions = document.getElementById('trkActions');

  function openModal(){ 
    modal.dataset.open = "1"; 
    modal.setAttribute('aria-hidden','false'); 
    document.body.style.overflow = 'hidden'; 
  }
  function closeModal(){ 
    modal.dataset.open = "0"; 
    modal.setAttribute('aria-hidden','true'); 
    document.body.style.overflow = '';
  }

  closeBtn.addEventListener('click', closeModal);
  modal.querySelector('.modal__backdrop').addEventListener('click', closeModal);
  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape' && modal.dataset.open==="1") closeModal(); });

  function escHtml(s){
    return (s ?? '').toString().replace(/[&<>"']/g, m => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
    }[m]));
  }

  function renderTracking(data, labelUrl){
    meta.innerHTML = '';
    actions.innerHTML = '';

    const badges = [];
    if(data.carrier) badges.push(`<span class="badge badge-muted">Carrier: ${escHtml(data.carrier)}</span>`);
    if(data.service) badges.push(`<span class="badge badge-muted">Servicio: ${escHtml(data.service)}</span>`);
    if(data.code)    badges.push(`<span class="badge badge-muted">Guía: ${escHtml(data.code)}</span>`);
    if(data.status)  badges.push(`<span class="badge badge-info">Estatus: ${escHtml((data.status||'').toString().toUpperCase())}</span>`);
    if(data.eta)     badges.push(`<span class="badge badge-muted">ETA: ${escHtml(data.eta)}</span>`);
    meta.innerHTML = badges.join('');

    if(labelUrl){
      const a = document.createElement('a');
      a.className = 'btn btn-outline';
      a.href = labelUrl;
      a.target = '_blank';
      a.rel = 'noopener';
      a.textContent = 'Descargar Guía PDF';
      actions.appendChild(a);
    }

    setTimeout(() => {
      bar.style.width = (Number(data.progress||0)) + '%';
    }, 100);

    list.innerHTML = '';
    const evs = Array.isArray(data.events) ? data.events : [];
    if(evs.length === 0){
      list.innerHTML = '<li class="tl__item"><div class="h">Sin eventos todavía</div><time>—</time></li>';
      return;
    }
    evs.forEach(ev=>{
      const t = ev.time ? new Date(ev.time) : null;
      const date = t ? t.toLocaleString() : '—';
      const where = ev.location ? ` — <i>${escHtml(ev.location)}</i>` : '';
      const details = ev.details ? `<div style="color:var(--muted);margin-top:6px;font-size:14px;line-height:1.4;">${escHtml(ev.details)}</div>` : '';
      const item = document.createElement('li');
      item.className = 'tl__item';
      item.innerHTML = `<div class="h">${escHtml(ev.status || 'Evento')}</div><time>${escHtml(date)}${where}</time>${details}`;
      list.appendChild(item);
    });
  }

  root.querySelectorAll('.js-track').forEach(btn=>{
    btn.addEventListener('click', async (e)=>{
      e.preventDefault();
      const url = btn.getAttribute('data-url');
      const labelUrl = (btn.getAttribute('data-label') || '').trim();
      if(!url) return;

      openModal();
      meta.innerHTML = '<span class="badge badge-muted">Cargando…</span>';
      actions.innerHTML = '';
      list.innerHTML = '<li class="tl__item"><div class="h">Obteniendo eventos…</div><time>—</time></li>';
      bar.style.width = '0%';

      try{
        const res = await fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}});
        const data = await res.json();
        if(data && data.ok) renderTracking(data, labelUrl);
        else meta.innerHTML = '<span class="badge badge-danger">No fue posible obtener el seguimiento</span>';
      }catch(err){
        meta.innerHTML = '<span class="badge badge-danger">Error al cargar seguimiento</span>';
      }
    });
  });
})();
</script>
@endsection