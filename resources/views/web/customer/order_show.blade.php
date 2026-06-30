@extends('layouts.web')
@section('title','Detalle del pedido')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>

<style>
/* ====================== SCOPE: #orderShow ====================== */
#orderShow{
  --ink:#0e1726; --muted:#6b7280; --line:#e8eef6;
  --surface:#ffffff; --brand:#0f172a;
  --ok:#16a34a; --warn:#eab308; --bad:#ef4444;
  --radius:18px; --shadow:0 16px 40px rgba(2,8,23,.08);
  --container:1100px;
  font-family:'Plus Jakarta Sans',system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  color:var(--ink);
  position:relative;
}
#orderShow .bg{
  position:fixed; inset:0; z-index:0; pointer-events:none;
  background:
    radial-gradient(900px 500px at 50% -200px, #eaf3ff 0%, rgba(234,243,255,0) 60%),
    linear-gradient(180deg, #eef7ff 0%, #f1ffe0 28%, #f7fff1 100%);
  background-attachment:fixed;
}
#orderShow .wrap{ position:relative; z-index:1; max-width:var(--container); margin:0 auto; padding:26px 16px 44px; }
#orderShow .topbar{
  display:flex; align-items:flex-start; justify-content:space-between; gap:14px; flex-wrap:wrap;
  margin-bottom:14px;
}
#orderShow .title h1{ margin:0; font-size:clamp(22px,3vw,32px); letter-spacing:-.02em; font-weight:900; }
#orderShow .title p{ margin:6px 0 0; color:var(--muted); font-weight:700; }
#orderShow .actions{ display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:flex-end; }
#orderShow .btn{
  appearance:none; border:1px solid var(--line); background:#fff; border-radius:14px;
  padding:10px 14px; cursor:pointer; font-weight:900; text-decoration:none; color:var(--ink);
  box-shadow:0 8px 20px rgba(2,8,23,.05); transition:.15s ease;
  display:inline-flex; align-items:center; gap:8px;
}
#orderShow .btn:hover{ transform:translateY(-1px) }
#orderShow .btn-brand{ background:var(--brand); color:#fff; border-color:var(--brand) }

#orderShow .grid{
  display:grid; grid-template-columns: 1.25fr .75fr; gap:16px;
}
@media (max-width: 980px){
  #orderShow .grid{ grid-template-columns:1fr; }
}

#orderShow .card{
  background:var(--surface); border:1px solid var(--line); border-radius:var(--radius);
  box-shadow:var(--shadow); overflow:hidden;
}
#orderShow .head{
  padding:16px 18px; border-bottom:1px solid var(--line);
  display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;
}
#orderShow .head h2{ margin:0; font-size:16px; font-weight:900; }
#orderShow .body{ padding:16px 18px; }

#orderShow .pill{
  display:inline-flex; align-items:center; gap:8px;
  padding:8px 12px; border-radius:999px; border:1px solid var(--line);
  font-weight:900; font-size:.9rem; background:#fff;
}
#orderShow .pill.ok{ background:#ecfdf5; color:#065f46; border-color:#bbf7d0 }
#orderShow .pill.proc{ background:#eff6ff; color:#1e3a8a; border-color:#dbeafe }
#orderShow .pill.cancel{ background:#fff5f5; color:#b91c1c; border-color:#fecaca }

#orderShow .kvs{ display:grid; grid-template-columns:1fr 1fr; gap:12px; }
@media (max-width:560px){ #orderShow .kvs{ grid-template-columns:1fr; } }
#orderShow .kv{ border:1px solid var(--line); border-radius:14px; padding:12px; background:#fbfdff; }
#orderShow .kv small{ display:block; color:var(--muted); font-weight:800; }
#orderShow .kv b{ display:block; margin-top:6px; font-weight:900; overflow-wrap:anywhere; word-break:break-word; }
#orderShow .ship-kvs{ grid-template-columns:1fr; }
#orderShow .ship-pill{ max-width:100%; white-space:normal; overflow-wrap:anywhere; text-align:left; justify-content:flex-start; }
@media (min-width:1200px){
  #orderShow .ship-kvs{ grid-template-columns:1fr 1fr; }
}

#orderShow .progress{
  height:10px; border-radius:999px; background:#f1f5f9;
  border:1px solid var(--line); overflow:hidden;
}
#orderShow .progress b{ display:block; height:100%; width:0%; background:var(--brand); }

#orderShow .items{
  width:100%; border-collapse:separate; border-spacing:0 10px;
}
#orderShow .items thead th{
  text-align:left; color:var(--muted); font-weight:900; font-size:.92rem; padding:0 10px;
}
#orderShow .row{
  background:#fff; border:1px solid var(--line); border-radius:14px;
  box-shadow:0 8px 20px rgba(2,8,23,.04);
}
#orderShow .row td{ padding:12px 10px; vertical-align:middle; }
#orderShow .prod{ display:flex; gap:12px; align-items:center; }
#orderShow .img{
  width:46px; height:46px; border-radius:12px; border:1px solid var(--line);
  background:#f8fafc; display:grid; place-items:center; overflow:hidden;
}
#orderShow .img img{ width:100%; height:100%; object-fit:cover; display:block; }
#orderShow .name{ font-weight:900; line-height:1.15; }
#orderShow .sku{ color:var(--muted); font-weight:800; font-size:.85rem; margin-top:3px; }

#orderShow .totals{
  display:grid; gap:10px;
}
#orderShow .totals .line{
  display:flex; align-items:center; justify-content:space-between; gap:10px;
  padding:10px 12px; border:1px solid var(--line); border-radius:14px; background:#fff;
}
#orderShow .totals .line span{ color:var(--muted); font-weight:900; }
#orderShow .totals .line b{ font-weight:900; }
#orderShow .totals .grand{
  border-color:#cbd5e1; background:linear-gradient(180deg,#fff, #fbfdff);
}
#orderShow .totals .grand b{ font-size:18px; color:var(--brand); }

#orderShow .timeline{ list-style:none; padding:0; margin:0; display:grid; gap:10px; }
#orderShow .tl{
  border:1px solid var(--line); border-radius:14px; padding:12px; background:#fff;
}
#orderShow .tl .h{ font-weight:900; }
#orderShow .tl time{ display:block; margin-top:4px; color:var(--muted); font-weight:800; font-size:.9rem; }

/* Modal flotante de seguimiento */
#orderShow .trk-float{
  position:fixed;
  inset:0;
  z-index:9999;
  display:none;
  align-items:center;
  justify-content:center;
  padding:18px;
  background:rgba(15,23,42,.52);
  backdrop-filter:blur(8px);
}
#orderShow .trk-float.is-open{ display:flex; }
#orderShow .trk-box{
  width:min(760px, 96vw);
  max-height:88vh;
  background:#fff;
  border:1px solid var(--line);
  border-radius:22px;
  box-shadow:0 28px 80px rgba(2,8,23,.28);
  overflow:hidden;
}
#orderShow .trk-head{
  padding:16px 18px;
  border-bottom:1px solid var(--line);
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:12px;
  background:linear-gradient(180deg,#ffffff,#f8fbff);
}
#orderShow .trk-head h3{
  margin:0;
  font-size:18px;
  font-weight:900;
}
#orderShow .trk-head p{
  margin:4px 0 0;
  color:var(--muted);
  font-weight:800;
}
#orderShow .trk-close{
  border:1px solid var(--line);
  background:#fff;
  border-radius:12px;
  width:38px;
  height:38px;
  cursor:pointer;
  font-weight:900;
  box-shadow:0 8px 20px rgba(2,8,23,.06);
}
#orderShow .trk-body{
  padding:16px 18px;
  max-height:calc(88vh - 86px);
  overflow-y:auto;
}
#orderShow .trk-meta{
  display:flex;
  gap:8px;
  flex-wrap:wrap;
  margin-bottom:14px;
}
#orderShow .trk-list{
  list-style:none;
  margin:0;
  padding:0;
  display:grid;
  gap:12px;
  position:relative;
}
#orderShow .trk-item{
  border:1px solid var(--line);
  border-radius:16px;
  padding:13px 14px;
  background:#fff;
  box-shadow:0 8px 20px rgba(2,8,23,.04);
}
#orderShow .trk-item .h{
  font-weight:900;
}
#orderShow .trk-item .d{
  margin-top:4px;
  color:var(--muted);
  font-weight:750;
  line-height:1.35;
}
#orderShow .trk-item time{
  display:block;
  margin-top:7px;
  color:var(--muted);
  font-weight:850;
  font-size:.88rem;
}
#orderShow .trk-item:first-child{
  border-color:#cbd5e1;
  background:linear-gradient(180deg,#fff,#f8fbff);
}
#orderShow .tracking-live{ margin-top:14px; display:grid; gap:10px; }
#orderShow .tracking-meta{ display:flex; gap:8px; flex-wrap:wrap; margin-bottom:10px; }
#orderShow .tracking-list{ list-style:none; padding:0; margin:0; display:grid; gap:10px; }
#orderShow .tracking-item{ border:1px solid var(--line); border-radius:14px; padding:12px; background:#fff; }
#orderShow .tracking-item .h{ font-weight:900; }
#orderShow .tracking-item .d{ margin-top:4px; color:var(--muted); font-weight:700; }
#orderShow .tracking-item time{ display:block; margin-top:6px; color:var(--muted); font-weight:800; font-size:.88rem; }
#orderShow .tl .d{ margin-top:6px; color:var(--muted); font-weight:700; }

#orderShow .addr{
  border:1px solid var(--line); border-radius:14px; padding:12px; background:#fbfdff;
  font-weight:800; color:#0f172a;
}
#orderShow .addr small{ display:block; color:var(--muted); font-weight:800; margin-bottom:6px; }

#orderShow .muted{ color:var(--muted); font-weight:800; }
</style>
<style>
/* Fallback global modal seguimiento */
.trk-float{
  position:fixed;
  inset:0;
  z-index:99999;
  display:none;
  align-items:center;
  justify-content:center;
  padding:18px;
  background:rgba(15,23,42,.52);
  backdrop-filter:blur(8px);
}
.trk-float.is-open{ display:flex; }
.trk-box{
  width:min(760px, 96vw);
  max-height:88vh;
  background:#fff;
  border:1px solid #e8eef6;
  border-radius:22px;
  box-shadow:0 28px 80px rgba(2,8,23,.28);
  overflow:hidden;
}
.trk-head{
  padding:16px 18px;
  border-bottom:1px solid #e8eef6;
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:12px;
  background:linear-gradient(180deg,#ffffff,#f8fbff);
}
.trk-head h3{ margin:0; font-size:18px; font-weight:900; }
.trk-head p{ margin:4px 0 0; color:#6b7280; font-weight:800; }
.trk-close{
  border:1px solid #e8eef6;
  background:#fff;
  border-radius:12px;
  width:38px;
  height:38px;
  cursor:pointer;
  font-weight:900;
}
.trk-body{
  padding:16px 18px;
  max-height:calc(88vh - 86px);
  overflow-y:auto;
}
.trk-meta{ display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
.trk-meta .pill{
  display:inline-flex;
  align-items:center;
  padding:8px 12px;
  border-radius:999px;
  border:1px solid #e8eef6;
  background:#fff;
  font-weight:900;
}
.trk-list{
  list-style:none;
  margin:0;
  padding:0;
  display:grid;
  gap:12px;
}
.trk-item{
  border:1px solid #e8eef6;
  border-radius:16px;
  padding:13px 14px;
  background:#fff;
  box-shadow:0 8px 20px rgba(2,8,23,.04);
}
.trk-item .h{ font-weight:900; }
.trk-item .d{
  margin-top:4px;
  color:#6b7280;
  font-weight:750;
  line-height:1.35;
}
.trk-item time{
  display:block;
  margin-top:7px;
  color:#6b7280;
  font-weight:850;
  font-size:.88rem;
}
.trk-item:first-child{
  border-color:#cbd5e1;
  background:linear-gradient(180deg,#fff,#f8fbff);
}
</style>

<div id="orderShow">
  <div class="bg" aria-hidden="true"></div>

  @php
    $fmt = fn($n)=> '$'.number_format((float)$n, 2, '.', ',').' MXN';
    $st  = strtolower((string)($order->status ?? ''));
    $pill = $st==='cancelado' || $st==='canceled' || $st==='cancelled'
      ? 'cancel'
      : (in_array($st,['delivered','entregado']) ? 'ok' : 'proc');

    $num = '#'.str_pad($order->id, 6, '0', STR_PAD_LEFT);
    $addr = (array)($order->address_json ?? []);
    $addrLine = trim(
      ($addr['street'] ?? '').' '.($addr['ext_number'] ?? '').' '.
      ($addr['colony'] ?? '').', '.($addr['municipality'] ?? '').', '.($addr['state'] ?? '').' CP '.($addr['postal_code'] ?? '')
    );
  @endphp

  <div class="wrap">
    <div class="topbar">
      <div class="title">
        <h1>Pedido {{ $num }}</h1>
        <p>
          Fecha: <b>{{ $order->created_at?->format('d/m/Y H:i') ?? '-' }}</b>
          <span class="muted">-</span>
          <span class="pill {{ $pill }}">{{ strtoupper($order->status ?? '-') }}</span>
        </p>
      </div>
      <div class="actions">
        <a class="btn" href="{{ route('customer.profile', ['tab'=>'pedidos']) }}">&larr; Volver</a>

        @if(\Illuminate\Support\Facades\Route::has('customer.orders.tracking'))
          <a class="btn" href="{{ route('customer.profile', ['tab'=>'pedidos']) }}#t-pedidos">Ver en pedidos</a>
        @endif

        @if(!empty($order->shipping_label_url) && \Illuminate\Support\Facades\Route::has('customer.orders.label'))
          <a class="btn btn-brand" href="{{ route('customer.orders.label',$order) }}" target="_blank" rel="noopener">Gu&iacute;a PDF</a>
        @endif
      </div>
    </div>

    <div class="grid">
      {{-- ===== IZQ: Productos + info ===== --}}
      <div class="card">
        <div class="head">
          <h2>Productos</h2>
          <span class="pill">
            {{ $order->items->sum('qty') }} articulos
          </span>
        </div>

        <div class="body">
          <table class="items">
            <thead>
              <tr>
                <th style="padding-left:10px">Articulo</th>
                <th style="text-align:center">Cant</th>
                <th style="text-align:right">Precio</th>
                <th style="text-align:right;padding-right:10px">Importe</th>
              </tr>
            </thead>
            <tbody>
              @forelse($order->items as $it)
                @php
                  $img = data_get($it->meta,'image') ?: ($it->image_url ?? null);
                @endphp
                <tr class="row">
                  <td>
                    <div class="prod">
                      <div class="img">
                        @if($img)
                          <img src="{{ $img }}" alt="img">
                        @else
          <span class="muted">-</span>
                        @endif
                      </div>
                      <div>
                        <div class="name">{{ $it->name }}</div>
                        <div class="sku">SKU: {{ $it->sku ?: '-' }}</div>
                      </div>
                    </div>
                  </td>
                  <td style="text-align:center; font-weight:900">{{ (int)($it->qty ?? 1) }}</td>
                  <td style="text-align:right; font-weight:900">{{ $fmt($it->price ?? $it->unit_price ?? 0) }}</td>
                  <td style="text-align:right; font-weight:900; padding-right:10px">{{ $fmt($it->amount ?? $it->total ?? 0) }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="muted" style="padding:12px">Sin partidas.</td></tr>
              @endforelse
            </tbody>
          </table>

          <div style="margin-top:14px" class="kvs">
            <div class="kv">
              <small>Metodo de pago</small>
              <b>Stripe (tarjeta)</b>
            </div>
            <div class="kv">
              <small>Stripe Session</small>
              <b style="word-break:break-all">{{ $order->stripe_session_id ?: '-' }}</b>
            </div>
          </div>

          @if(!empty($addrLine))
            <div style="margin-top:14px" class="addr">
              <small>Direccion de entrega</small>
              {{ $addrLine }}
              @if(!empty($addr['contact_name']) || !empty($addr['phone']))
                <div class="muted" style="margin-top:8px">
                  Recibe: {{ $addr['contact_name'] ?? '-' }} - Tel: {{ $addr['phone'] ?? '-' }}
                </div>
              @endif
            </div>
          @endif
        </div>
      </div>

      {{-- ===== DER: Resumen + Envio + Timeline ===== --}}
      <aside style="display:grid; gap:16px;">
        <div class="card">
          <div class="head">
            <h2>Resumen</h2>
            <span class="pill">Progreso {{ (int)$progress }}%</span>
          </div>
          <div class="body">
            <div class="progress" aria-hidden="true">
              <b style="width:{{ (int)$progress }}%"></b>
            </div>

            <div style="margin-top:12px" class="totals">
              <div class="line"><span>Subtotal</span><b>{{ $fmt($order->subtotal) }}</b></div>
              <div class="line"><span>Envio</span><b>{{ $fmt($order->shipping_amount) }}</b></div>
              <div class="line grand"><span>Total</span><b>{{ $fmt($order->total) }}</b></div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="head">
            <h2>Envio (Envia.com)</h2>
            <span class="pill ship-pill">
              {{ $shipping['name'] ?: 'Paqueteria' }}
              {{ $shipping['service'] ? ' - '.$shipping['service'] : '' }}
            </span>
            @if(!empty($shipping['eta']))
              <span class="pill ship-pill">Entrega estimada: {{ $shipping['eta'] }}</span>
            @endif
          </div>
          <div class="body">
            <div class="kvs ship-kvs">
              <div class="kv">
                <small>Guia</small>
                <b>{{ $shipping['code'] ?: '-' }}</b>
              </div>
              <div class="kv">
                <small>Fecha estimada</small>
                <b>{{ $shipping['eta'] ?: '-' }}</b>
              </div>
            </div>

            <div style="margin-top:12px" class="muted">
              @if($shipping['store_pays'])
                Envio gratis aplicado (cubierto por la tienda).
              @else
                Envio pagado por el cliente.
              @endif
            </div>

            @if(\Illuminate\Support\Facades\Route::has('customer.orders.tracking'))
              <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap">
                <a class="btn js-load-tracking" href="#" data-url="{{ route('customer.orders.tracking', $order) }}">Ver seguimiento</a>
              </div>
            @endif
          </div>
        </div>

<div class="trk-float" id="trackingFloatModal" aria-hidden="true">
  <div class="trk-box" role="dialog" aria-modal="true" aria-labelledby="trkFloatTitle">
    <div class="trk-head">
      <div>
        <h3 id="trkFloatTitle">Seguimiento del pedido</h3>
        <p>Movimientos y estado del envio</p>
      </div>
      <button type="button" class="trk-close" id="trackingFloatClose" aria-label="Cerrar">X</button>
    </div>

    <div class="trk-body">
      <div class="trk-meta" id="trackingFloatMeta">
        <span class="pill">Cargando...</span>
      </div>

      <ul class="trk-list" id="trackingFloatList">
        <li class="trk-item">
          <div class="h">Esperando informacion...</div>
          <time>-</time>
        </li>
      </ul>
    </div>
  </div>
</div>

<script>
(function(){
  const modal = document.getElementById('trackingFloatModal');
  const closeBtn = document.getElementById('trackingFloatClose');
  const list = document.getElementById('trackingFloatList');
  const meta = document.getElementById('trackingFloatMeta');
  const btns = document.querySelectorAll('#orderShow .js-load-tracking');

  function esc(v){
    return String(v ?? '').replace(/[&<>"']/g, function(m){
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m];
    });
  }

  function fmtDate(v){
    if(!v) return '-';
    const d = new Date(v);
    if(isNaN(d.getTime())) return esc(v);
    return d.toLocaleString('es-MX', { dateStyle:'medium', timeStyle:'short' });
  }

  function openModal(){
    if(!modal) return;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden','false');
    document.body.style.overflow = 'hidden';
  }

  function closeModal(){
    if(!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden','true');
    document.body.style.overflow = '';
  }

  function render(data){
    const events = Array.isArray(data.events) ? data.events : [];

    const pills = [];
    pills.push('<span class="pill">Paqueteria: ' + esc(data.carrier || 'Envia.com') + '</span>');
    if(data.service) pills.push('<span class="pill">Servicio: ' + esc(data.service) + '</span>');
    if(data.code) pills.push('<span class="pill">Guia: ' + esc(data.code) + '</span>');
    if(data.eta) pills.push('<span class="pill">Entrega estimada: ' + esc(data.eta) + '</span>');
    if(data.mode === 'test') pills.push('<span class="pill">Modo prueba</span>');

    meta.innerHTML = pills.join('');

    if(!events.length){
      list.innerHTML = '<li class="trk-item"><div class="h">Sin movimientos todavia</div><div class="d">La paqueteria aun no reporta eventos.</div><time>-</time></li>';
      return;
    }

    list.innerHTML = events.map(function(ev){
      const loc = ev.location ? '<div class="d">Ubicacion: ' + esc(ev.location) + '</div>' : '';
      const details = ev.details ? '<div class="d">' + esc(ev.details) + '</div>' : '';

      return '<li class="trk-item">' +
        '<div class="h">' + esc(ev.status || 'Movimiento') + '</div>' +
        loc +
        details +
        '<time>' + fmtDate(ev.time) + '</time>' +
      '</li>';
    }).join('');
  }

  async function load(url){
    meta.innerHTML = '<span class="pill">Cargando seguimiento...</span>';
    list.innerHTML = '<li class="trk-item"><div class="h">Cargando linea del tiempo...</div><time>-</time></li>';

    try{
      const res = await fetch(url, { headers:{ 'Accept':'application/json' } });
      const data = await res.json();
      render(data);
    }catch(e){
      meta.innerHTML = '<span class="pill">Error</span>';
      list.innerHTML = '<li class="trk-item"><div class="h">No se pudo cargar el seguimiento</div><div class="d">Intenta nuevamente.</div><time>-</time></li>';
    }
  }

  btns.forEach(function(btn){
    btn.addEventListener('click', function(e){
      e.preventDefault();
      const url = btn.getAttribute('data-url');
      openModal();
      if(url) load(url);
    });
  });

  if(closeBtn){
    closeBtn.addEventListener('click', closeModal);
  }

  if(modal){
    modal.addEventListener('click', function(e){
      if(e.target === modal) closeModal();
    });
  }

  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeModal();
  });
})();
</script>
@endsection

