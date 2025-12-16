@extends('layouts.web')
@section('title','Mi Cuenta')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>

<style>
  /* ====================== SCOPE: #account ====================== */
  #account{
    --ink:#0e1726; --muted:#6b7280; --line:#e8eef6;
    --surface:#ffffff; --brand:#0f172a;
    --ok:#16a34a; --warn:#eab308; --bad:#ef4444;
    --radius:16px; --shadow:0 16px 40px rgba(2,8,23,.08);
    --container:1200px;
    font-family:'Plus Jakarta Sans',system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    color:var(--ink);
    position:relative;
    z-index:1;
  }
  /* Fondo encapsulado (pantalla completa) */
  #account .bg-grad{
    position:fixed; inset:0; z-index:0; pointer-events:none;
    background:
      radial-gradient(900px 500px at 50% -200px, #eaf3ff 0%, rgba(234,243,255,0) 60%),
      linear-gradient(180deg, #eef7ff 0%, #f1ffe0 28%, #f7fff1 100%);
    background-attachment:fixed;
  }

  /* Hero */
  #account .hero{
    position:relative; z-index:1;
    max-width:none; margin:0;
    padding: clamp(28px, 5vw, 48px) 20px 8px;
    background: linear-gradient(180deg, rgba(255,255,255,.55), rgba(255,255,255,.35));
    border-bottom:1px solid #edf2f7; backdrop-filter: blur(6px);
  }
  #account .hero__inner{ max-width:var(--container); margin:0 auto; }
  #account .hero h1{ font-weight:800; font-size:clamp(34px,4.2vw,56px); letter-spacing:-.02em; margin:0; }
  #account .hero p{ margin:8px 0 0; color:var(--muted); font-size:clamp(14px,1.7vw,18px); }

  /* Layout */
  #account .wrap{ max-width:var(--container); margin:0 auto; padding:12px 16px 36px; position:relative; z-index:1; }
  #account .layout{ display:grid; grid-template-columns:280px 1fr; gap:22px }
  @media (max-width:980px){ #account .layout{ grid-template-columns:1fr } }

  /* Cards */
  #account .card{ background:var(--surface); border:1px solid var(--line); border-radius:var(--radius); box-shadow:var(--shadow) }
  #account .card-head{ padding:18px; border-bottom:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap }
  #account .card-body{ padding:18px }

  /* Aside */
  #account .aside{ position:sticky; top:84px }
  #account .userbox{ display:flex; align-items:center; gap:12px; padding:16px }
  #account .avatar{
    width:44px; height:44px; border-radius:999px; display:grid; place-items:center;
    background:#eef2ff; border:2px solid #dfe6ee; color:#2e3a7a; font-weight:900;
  }
  #account .menu{ padding:10px }
  #account .menu a{
    display:flex; align-items:center; gap:10px; padding:12px; border-radius:12px;
    text-decoration:none; color:var(--ink); font-weight:700; border:1px solid transparent; transition:.15s ease;
  }
  #account .menu a:hover{ background:#f7fafb }
  #account .menu a.active{ background:#f2f7ff; border-color:#dfeaff; box-shadow:inset 0 0 0 1px #e7efff }

  /* Tabs */
  #account .tabs{ display:flex; gap:8px; flex-wrap:wrap }
  #account .tabbtn{
    border:1px solid #e4eaf6; background:#fff; color:#0e1726; border-radius:999px;
    padding:10px 16px; font-weight:800; cursor:pointer; box-shadow:0 6px 18px rgba(2,8,23,.04);
  }
  #account .tabbtn[aria-selected="true"]{ background:#0f172a; color:#fff; border-color:#0f172a }

  /* Controls */
  #account .searchRow{ display:flex; gap:12px; align-items:center; flex-wrap:wrap }
  #account .searchRow input[type="month"]{
    border:1px solid var(--line); border-radius:12px; padding:10px 12px; background:#fff; box-shadow:0 6px 18px rgba(2,8,23,.04);
  }
  #account .btn{
    appearance:none; border:1px solid var(--line); background:#fff; border-radius:12px;
    padding:10px 14px; cursor:pointer; font-weight:800; box-shadow:0 6px 18px rgba(2,8,23,.05);
    text-decoration:none; display:inline-flex; align-items:center; gap:8px;
  }
  #account .btn:hover{ transform:translateY(-1px) }
  #account .btn-brand{ background:#0f172a; color:#fff; border-color:#0f172a }
  #account .btn-ghost{ background:#fff }

  /* KPIs */
  #account .kpis{ display:grid; grid-template-columns:repeat(4,1fr); gap:12px }
  @media (max-width:980px){ #account .kpis{ grid-template-columns:1fr 1fr } }
  #account .kpi{ background:#fff; border:1px solid var(--line); border-radius:14px; padding:16px; box-shadow:0 6px 18px rgba(2,8,23,.04) }
  #account .kpi small{ color:var(--muted) }
  #account .kpi strong{ display:block; font-size:clamp(20px,2.5vw,24px); margin-top:6px }

  #account .hr{ border:0; border-top:1px solid var(--line); margin:16px 0 }

  /* ✅ Panels */
  #account .tpanel{ display:none; }
  #account .tpanel.active{ display:block; }

  /* Table */
  #account .table{ width:100%; border-collapse:separate; border-spacing:0 10px }
  #account .table thead th{ color:var(--muted); font-weight:800; text-align:left; font-size:.92rem; padding:0 10px }
  #account .tr{ background:#fff; border:1px solid var(--line); border-radius:14px; box-shadow:0 6px 18px rgba(2,8,23,.04) }
  #account .tr td{ padding:14px 10px; vertical-align:middle }
  #account .status{ display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:999px; border:1px solid var(--line); font-weight:800 }
  #account .status.cancel{ color:#b91c1c; background:#fff5f5 }
  #account .status.ok{ color:#065f46; background:#ecfdf5 }
  #account .status.proc{ color:#1e3a8a; background:#eff6ff }

  #account .pill{ display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; border:1px solid var(--line); font-weight:800; font-size:.85rem }
  #account .empty{ padding:24px; border:1px dashed var(--line); border-radius:14px; color:var(--muted); text-align:center; background:#fff }

  /* ===== Modal Seguimiento ===== */
  #account .modal{ position:fixed; inset:0; z-index:9999; display:none; }
  #account .modal[data-open="1"]{ display:block; }
  #account .modal__backdrop{
    position:absolute; inset:0; background:rgba(2,8,23,.45); backdrop-filter:blur(2px);
  }
  #account .modal__panel{
    position:absolute; left:50%; top:50%; transform:translate(-50%,-50%);
    width:min(760px, 92vw); background:#fff; border:1px solid var(--line); border-radius:18px; box-shadow:var(--shadow);
    overflow:hidden;
  }
  #account .modal__head{ padding:16px 18px; border-bottom:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:10px }
  #account .modal__body{ padding:16px 18px; max-height:min(70vh, 70dvh); overflow:auto }
  #account .close{ appearance:none; border:1px solid var(--line); background:#fff; border-radius:10px; padding:8px 10px; font-weight:800; cursor:pointer }

  /* Timeline */
  #account .trk-meta{ display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px }
  #account .trk-meta .pill{ background:#f8fafc }
  #account .progress{
    position:relative; height:10px; border-radius:999px; background:#f1f5f9; border:1px solid var(--line); overflow:hidden; margin:8px 0 16px;
  }
  #account .progress b{ position:absolute; height:100%; left:0; top:0; width:0%; background:#0f172a }
  #account .tl{ list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:10px }
  #account .tl__item{ border:1px solid var(--line); border-radius:14px; padding:12px; background:#fff }
  #account .tl__item .h{ font-weight:900 }
  #account .tl__item time{ color:var(--muted); font-size:.9rem }
</style>

<div id="account">
  <div class="bg-grad" aria-hidden="true"></div>

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
            <div style="font-weight:900">{{ $user->name ?? 'Mi cuenta' }}</div>
            <small style="color:var(--muted)">{{ $user->email }}</small>
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
            <div class="tabs" id="tabs">
              @php $tab = $activeTab; @endphp
              <button class="tabbtn" data-target="t-resumen"      aria-selected="{{ $tab==='resumen'?'true':'false' }}">Resumen</button>
              <button class="tabbtn" data-target="t-pedidos"      aria-selected="{{ $tab==='pedidos'?'true':'false' }}">Pedidos</button>
              <button class="tabbtn" data-target="t-datos"        aria-selected="{{ $tab==='datos'?'true':'false' }}">Datos</button>
              <button class="tabbtn" data-target="t-facturacion"  aria-selected="{{ $tab==='facturacion'?'true':'false' }}">Facturación</button>
              <button class="tabbtn" data-target="t-facturas"     aria-selected="{{ $tab==='facturas'?'true':'false' }}">Facturas</button>
              <button class="tabbtn" data-target="t-direcciones"  aria-selected="{{ $tab==='direcciones'?'true':'false' }}">Direcciones</button>
            </div>

            <form class="searchRow" method="get" action="{{ route('customer.profile') }}">
              <span style="color:var(--muted);font-weight:700;">Año / Mes</span>
              <input type="month" name="ym" value="{{ $ym }}">
              @if($activeTab)<input type="hidden" name="tab" value="{{ $activeTab }}">@endif
              <button class="btn">Filtrar</button>
            </form>
          </div>

          <div class="card-body">
            {{-- ===== RESUMEN ===== --}}
            <div id="t-resumen" class="tpanel {{ $tab==='resumen'?'active':'' }}">
              <div class="kpis">
                <div class="kpi"><small>Pedidos totales</small><strong>{{ number_format($stats['orders_total']) }}</strong></div>
                <div class="kpi"><small>Pedidos activos</small><strong>{{ number_format($stats['orders_open']) }}</strong></div>
                <div class="kpi"><small>Pedidos cancelados</small><strong>{{ number_format($stats['orders_cancel']) }}</strong></div>
                <div class="kpi"><small>Gasto acumulado</small><strong>${{ number_format($stats['spent_total'],2) }}</strong></div>
              </div>

              <div class="hr"></div>

              <h3 style="margin:8px 0 10px">Últimos pedidos</h3>
              @if($orders->count())
                <table class="table">
                  <thead><tr><th>ID</th><th>No. pedido</th><th>Fecha</th><th>Estatus</th><th>Total</th><th></th></tr></thead>
                  <tbody>
                  @foreach($orders->take(5) as $o)
                    <tr class="tr">
                      <td>{{ $o->id }}</td>
                      <td>
                        @if(route_has('customer.orders.show'))
                          <a class="btn btn-ghost" href="{{ route('customer.orders.show',$o) }}">
                            #{{ str_pad($o->id,6,'0',STR_PAD_LEFT) }}
                          </a>
                        @else
                          #{{ str_pad($o->id,6,'0',STR_PAD_LEFT) }}
                        @endif
                      </td>
                      <td>{{ $o->created_at?->format('d/m/Y') ?? '—' }}</td>
                      <td>
                        @php $st = strtolower((string)$o->status); @endphp
                        <span class="status {{ $st==='cancelado'?'cancel':($st==='entregado'?'ok':'proc') }}">{{ ucfirst($o->status ?? '—') }}</span>

                        @if(!empty($o->shipping_code))
                          <div class="pill" style="margin-top:6px">Guía: {{ $o->shipping_code }}</div>
                        @endif

                        @if(!empty($o->shipping_name) || !empty($o->shipping_service))
                          <div class="pill" style="margin-top:6px">
                            {{ $o->shipping_name ?? '' }}{{ $o->shipping_service ? ' — '.$o->shipping_service : '' }}
                          </div>
                        @endif

                        @if(!empty($o->shipping_eta))
                          <div class="pill" style="margin-top:6px">ETA: {{ $o->shipping_eta }}</div>
                        @endif
                      </td>
                      <td>${{ number_format($o->total,2) }}</td>
                      <td style="text-align:right; display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap">
                        @if(route_has('customer.orders.show'))
                          <a class="btn btn-ghost" href="{{ route('customer.orders.show',$o) }}">Ver detalle</a>
                        @endif

                        @if(route_has('customer.orders.reorder'))
                          <form action="{{ route('customer.orders.reorder',$o) }}" method="post" style="display:inline">@csrf
                            <button class="btn btn-brand">Agregar a carrito</button>
                          </form>
                        @endif

                        @if(route_has('customer.orders.tracking'))
                          <button class="btn js-track"
                                  data-url="{{ route('customer.orders.tracking',$o) }}"
                                  data-label="{{ $o->shipping_label_url ?? '' }}">
                            Seguimiento
                          </button>
                        @endif

                        @if(route_has('customer.orders.label') && !empty($o->shipping_label_url))
                          <a class="btn btn-ghost" href="{{ route('customer.orders.label',$o) }}" target="_blank" rel="noopener">Guía PDF</a>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                  </tbody>
                </table>
              @else
                <div class="empty">Aún no tienes pedidos.</div>
              @endif
            </div>

            {{-- ===== PEDIDOS ===== --}}
            <div id="t-pedidos" class="tpanel {{ $tab==='pedidos'?'active':'' }}">
              @if($orders->count())
                <table class="table">
                  <thead><tr><th>ID</th><th>No. pedido</th><th>Fecha</th><th>Estatus</th><th>Factura</th><th>Total</th><th></th></tr></thead>
                  <tbody>
                  @foreach($orders as $o)
                    <tr class="tr">
                      <td>{{ $o->id }}</td>
                      <td>
                        @if(route_has('customer.orders.show'))
                          <a class="btn btn-ghost" href="{{ route('customer.orders.show',$o) }}">
                            #{{ str_pad($o->id,6,'0',STR_PAD_LEFT) }}
                          </a>
                        @else
                          #{{ str_pad($o->id,6,'0',STR_PAD_LEFT) }}
                        @endif
                      </td>
                      <td>{{ $o->created_at?->format('d/m/Y') ?? '—' }}</td>
                      <td>
                        @php $st = strtolower((string)$o->status); @endphp
                        <span class="status {{ $st==='cancelado'?'cancel':($st==='entregado'?'ok':'proc') }}">{{ ucfirst($o->status ?? '—') }}</span>

                        @if(!empty($o->shipping_code))
                          <div class="pill" style="margin-top:6px">Guía: {{ $o->shipping_code }}</div>
                        @endif

                        @if(!empty($o->shipping_name) || !empty($o->shipping_service))
                          <div class="pill" style="margin-top:6px">
                            {{ $o->shipping_name ?? '' }}{{ $o->shipping_service ? ' — '.$o->shipping_service : '' }}
                          </div>
                        @endif

                        @if(!empty($o->shipping_eta))
                          <div class="pill" style="margin-top:6px">ETA: {{ $o->shipping_eta }}</div>
                        @endif
                      </td>

                      <td><span class="pill">—</span></td>

                      <td>${{ number_format($o->total,2) }}</td>

                      <td style="text-align:right; display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap">
                        @if(route_has('customer.orders.show'))
                          <a class="btn btn-ghost" href="{{ route('customer.orders.show',$o) }}">Ver detalle</a>
                        @endif

                        @if(route_has('customer.orders.reorder'))
                          <form action="{{ route('customer.orders.reorder',$o) }}" method="post" style="display:inline">@csrf
                            <button class="btn btn-brand">Agregar a carrito</button>
                          </form>
                        @endif

                        @if(route_has('customer.orders.tracking'))
                          <button class="btn js-track"
                                  data-url="{{ route('customer.orders.tracking',$o) }}"
                                  data-label="{{ $o->shipping_label_url ?? '' }}">
                            Seguimiento
                          </button>
                        @endif

                        @if(route_has('customer.orders.label') && !empty($o->shipping_label_url))
                          <a class="btn btn-ghost" href="{{ route('customer.orders.label',$o) }}" target="_blank" rel="noopener">Guía PDF</a>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                  </tbody>
                </table>
              @else
                <div class="empty">No hay pedidos para el filtro seleccionado.</div>
              @endif
            </div>

            {{-- ===== DATOS ===== --}}
            <div id="t-datos" class="tpanel {{ $tab==='datos'?'active':'' }}">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="kpi"><small>Nombre</small><strong>{{ $user->name ?? '—' }}</strong></div>
                <div class="kpi"><small>Correo</small><strong>{{ $user->email }}</strong></div>
                <div class="kpi"><small>Registrado</small><strong>{{ $user->created_at?->format('d/m/Y') ?? '—' }}</strong></div>
                <div class="kpi"><small>Último acceso</small><strong>{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</strong></div>
              </div>
              <div style="margin-top:14px;display:flex;gap:8px">
               @if(\Illuminate\Support\Facades\Route::has('customer.welcome'))
  <a href="{{ route('customer.welcome') }}" class="btn btn-ghost">Inicio cliente</a>
@endif

              </div>
            </div>

            {{-- ===== FACTURACIÓN ===== --}}
            <div id="t-facturacion" class="tpanel {{ $tab==='facturacion'?'active':'' }}">
              @if($billingProfiles->count())
                @foreach($billingProfiles as $bp)
                  <div class="tr" style="padding:14px">
                    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
                      <div>
                        <div style="font-weight:900">{{ $bp->razon_social }}</div>
                        <div class="pill">RFC: {{ $bp->rfc }}</div>
                        <div class="pill">Régimen: {{ $bp->regimen ?: '—' }}</div>
                        <div class="pill">Uso CFDI: {{ $bp->uso_cfdi ?: '—' }}</div>
                        <div style="color:var(--muted);margin-top:6px">{{ $bp->direccion }} {{ $bp->colonia }} {{ $bp->estado }} C.P. {{ $bp->zip }}</div>
                      </div>
                      <div style="display:flex;align-items:center;gap:8px">
                        @if($bp->is_default) <span class="pill" style="background:#ecfdf5;color:#065f46;border-color:#bbf7d0">Predeterminado</span> @endif
                      </div>
                    </div>
                  </div>
                @endforeach
              @else
                <div class="empty">Aún no tienes perfiles de facturación.</div>
              @endif
            </div>

            {{-- ===== FACTURAS ===== --}}
            <div id="t-facturas" class="tpanel {{ $tab==='facturas'?'active':'' }}">
              @if($invoices->count())
                <table class="table">
                  <thead><tr><th>Serie/Folio</th><th>Fecha</th><th>Total</th><th>Descargas</th></tr></thead>
                  <tbody>
                  @foreach($invoices as $f)
                    <tr class="tr">
                      <td>{{ ($f->serie ?? 'A').'-'.($f->folio ?? $f->id) }}</td>
                      <td>{{ $f->fecha?->format('d/m/Y') ?? $f->created_at?->format('d/m/Y') }}</td>
                      <td>${{ number_format($f->total,2) }}</td>
                      <td style="display:flex;gap:8px">
                        @if(route_has('customer.invoices.pdf')) <a class="btn btn-ghost" href="{{ route('customer.invoices.pdf',$f->id) }}">PDF</a> @endif
                        @if(route_has('customer.invoices.xml')) <a class="btn btn-ghost" href="{{ route('customer.invoices.xml',$f->id) }}">XML</a> @endif
                      </td>
                    </tr>
                  @endforeach
                  </tbody>
                </table>
              @else
                <div class="empty">Aquí aparecerán tus facturas emitidas.</div>
              @endif
            </div>

            {{-- ===== DIRECCIONES ===== --}}
            <div id="t-direcciones" class="tpanel {{ $tab==='direcciones'?'active':'' }}">
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
        <div style="font-weight:900" id="trkTitle">Seguimiento del envío</div>
        <button class="close" id="trkClose">Cerrar</button>
      </div>
      <div class="modal__body">
        <div class="trk-meta" id="trkMeta">
          <span class="pill">Cargando…</span>
        </div>

        {{-- ✅ Acciones dentro del modal --}}
        <div id="trkActions" style="display:flex; gap:8px; flex-wrap:wrap; margin:10px 0 12px;"></div>

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

  const tabBtns    = root.querySelectorAll('.tabbtn');
  const panels     = root.querySelectorAll('.tpanel');
  const asideLinks = root.querySelectorAll('.js-gotab');
  const form       = root.querySelector('.card-head form.searchRow');

  function setActive(id){
    panels.forEach(p => p.classList.toggle('active', p.id === id));
    tabBtns.forEach(b => b.setAttribute('aria-selected', b.dataset.target === id ? 'true' : 'false'));
    asideLinks.forEach(a => a.classList.toggle('active', a.getAttribute('href') === '#'+id));
    if(form){
      let h = form.querySelector('input[name="tab"]');
      if(!h){ h=document.createElement('input'); h.type='hidden'; h.name='tab'; form.appendChild(h); }
      h.value = id.replace('t-','');
    }
    history.replaceState(null, '', '#'+id);
  }

  tabBtns.forEach(b=> b.addEventListener('click', e=>{ e.preventDefault(); setActive(b.dataset.target); }));
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

  function openModal(){ modal.dataset.open = "1"; modal.setAttribute('aria-hidden','false'); }
  function closeModal(){ modal.dataset.open = "0"; modal.setAttribute('aria-hidden','true'); }

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

    const pills = [];
    if(data.carrier) pills.push(`<span class="pill">Carrier: <b>${escHtml(data.carrier)}</b></span>`);
    if(data.service) pills.push(`<span class="pill">Servicio: <b>${escHtml(data.service)}</b></span>`);
    if(data.code)    pills.push(`<span class="pill">Guía: <b>${escHtml(data.code)}</b></span>`);
    if(data.status)  pills.push(`<span class="pill">Estatus: <b>${escHtml((data.status||'').toString().toUpperCase())}</b></span>`);
    if(data.eta)     pills.push(`<span class="pill">ETA: <b>${escHtml(data.eta)}</b></span>`);
    meta.innerHTML = pills.join('');

    if(labelUrl){
      const a = document.createElement('a');
      a.className = 'btn btn-ghost';
      a.href = labelUrl;
      a.target = '_blank';
      a.rel = 'noopener';
      a.textContent = 'Guía PDF';
      actions.appendChild(a);
    }

    bar.style.width = (Number(data.progress||0)) + '%';

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
      const details = ev.details ? `<div style="color:var(--muted);margin-top:4px">${escHtml(ev.details)}</div>` : '';
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
      meta.innerHTML = '<span class="pill">Cargando…</span>';
      actions.innerHTML = '';
      list.innerHTML = '<li class="tl__item"><div class="h">Obteniendo eventos…</div><time>—</time></li>';
      bar.style.width = '0%';

      try{
        const res = await fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}});
        const data = await res.json();
        if(data && data.ok) renderTracking(data, labelUrl);
        else meta.innerHTML = '<span class="pill">No fue posible obtener el seguimiento</span>';
      }catch(err){
        meta.innerHTML = '<span class="pill">Error al cargar seguimiento</span>';
      }
    });
  });
})();
</script>
@endsection

@php
if (!function_exists('route_has')) {
  function route_has($name) { try { return app('router')->has($name); } catch (\Throwable $e) { return false; } }
}
@endphp
