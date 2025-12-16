@extends('layouts.web')
@section('title','Pedido #'.$order->id)

@section('content')
<style>
#od{
  --bg:#f6f7fb; --card:#fff; --line:#e8eef6; --ink:#0f172a; --muted:#64748b;
  --brand:#6d28d9; --soft:#f4f2ff;
  --ok:#16a34a; --okbg:#eafff1;
  --warn:#b45309; --warnbg:#fff3d6;
  --bad:#b91c1c; --badbg:#ffe4e6;
  --radius:18px; --shadow:0 20px 50px rgba(2,8,23,.08);
}
#od{background:var(--bg); padding:28px 0}
#od .wrap{max-width:1180px; margin:0 auto; padding:0 18px}
#od .top{display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:16px}
#od .h1{margin:0; font-size:28px; letter-spacing:-.02em; color:var(--ink)}
#od .sub{margin:6px 0 0; color:var(--muted); font-weight:700}
#od .actions{display:flex; gap:10px; flex-wrap:wrap}
#od .btn{
  display:inline-flex; align-items:center; justify-content:center; gap:10px;
  padding:11px 14px; border-radius:12px; border:1px solid var(--line);
  background:#fff; color:var(--ink); font-weight:900; text-decoration:none;
}
#od .btn:hover{box-shadow:0 10px 24px rgba(2,8,23,.08)}
#od .btn-primary{background:var(--brand); color:#fff; border-color:transparent}

#od .grid{display:grid; grid-template-columns: 1.55fr .9fr; gap:16px; align-items:start}
#od .card{background:var(--card); border:1px solid var(--line); border-radius:22px; box-shadow:var(--shadow); overflow:hidden}
#od .card .head{padding:16px 16px; border-bottom:1px solid var(--line); display:flex; align-items:center; justify-content:space-between}
#od .card .head h3{margin:0; font-size:16px; color:var(--ink); letter-spacing:-.01em}
#od .badge{display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:900}
#od .b-ok{background:var(--okbg); color:var(--ok)}
#od .b-warn{background:var(--warnbg); color:var(--warn)}
#od .b-bad{background:var(--badbg); color:var(--bad)}

#od .items{padding:0}
#od .row{display:flex; gap:14px; padding:14px 16px; border-top:1px solid #f1f5f9}
#od .row:first-child{border-top:0}
#od .thumb{
  width:54px; height:54px; border-radius:12px;
  border:1px solid var(--line); background:#fff; overflow:hidden;
  display:flex; align-items:center; justify-content:center;
}
#od .thumb img{width:100%; height:100%; object-fit:cover}
#od .name{font-weight:950; color:var(--ink)}
#od .meta{color:var(--muted); font-size:12px; margin-top:3px; font-weight:700}
#od .right{margin-left:auto; text-align:right; white-space:nowrap}
#od .right .price{font-weight:950}
#od .right .qty{color:var(--muted); font-weight:800; font-size:12px}

#od .summary{padding:14px 16px}
#od .sumrow{display:flex; justify-content:space-between; padding:8px 0; color:var(--muted); font-weight:800}
#od .sumrow strong{color:var(--ink)}
#od .total{font-size:20px; color:var(--ink)}

#od .client{padding:14px 16px}
#od .kv{display:flex; justify-content:space-between; gap:10px; padding:12px 0; border-top:1px solid #f1f5f9}
#od .kv:first-child{border-top:0}
#od .k{color:var(--muted); font-weight:800}
#od .v{color:var(--ink); font-weight:950; text-align:right}
#od .copy{
  border:1px solid var(--line); background:#fff; border-radius:10px;
  padding:8px 10px; font-weight:900; cursor:pointer;
}
#od .copy:hover{box-shadow:0 10px 24px rgba(2,8,23,.08)}
#od .note{color:var(--muted); font-weight:700; font-size:12px; margin-top:10px}

@media (max-width:980px){
  #od .grid{grid-template-columns:1fr}
}
</style>

@php
  $isPaid = $order->status === 'paid';
  $badge = $isPaid ? ['Pagado','b-ok'] : ['Esperando','b-warn'];
  if ($order->status === 'failed') $badge = ['Incumplido','b-bad'];

  $fmt = fn($n) => 'MX$'.number_format((float)$n, 2, '.', ',');
  $fullAddress = trim(
    ($addr['street'] ?? '').' '.($addr['ext_number'] ?? '').' '.
    ($addr['colony'] ?? '').', '.($addr['municipality'] ?? '').', '.
    ($addr['state'] ?? '').', CP '.($addr['postal_code'] ?? '')
  );
@endphp

<div id="od">
  <div class="wrap">
    <div class="top">
      <div>
        <div class="h1">#{{ $order->id }}</div>
        <div class="sub">{{ optional($order->created_at)->format('d \\d\\e F \\d\\e Y, H:i') }}</div>
      </div>
      <div class="actions">
        <a class="btn" href="{{ route('admin.orders.index') }}">← Volver</a>
        @if(!empty($order->invoice_id))
          <a class="btn btn-primary" href="{{ route('checkout.invoice.pdf', $order->invoice_id) }}">Ver factura</a>
        @endif
      </div>
    </div>

    <div class="grid">
      {{-- LEFT --}}
      <div>
        <div class="card">
          <div class="head">
            <h3>Completado ({{ $order->items->count() }})</h3>
            <span class="badge {{ $badge[1] }}">{{ $badge[0] }}</span>
          </div>

          <div class="items">
            @foreach($order->items as $it)
              @php
                $img = $it->image_url ?? ($it->meta['image'] ?? null);
              @endphp
              <div class="row">
                <div class="thumb">
                  @if($img)
                    <img src="{{ $img }}" alt="">
                  @else
                    <span style="color:#94a3b8;font-weight:900;">—</span>
                  @endif
                </div>
                <div>
                  <div class="name">{{ $it->name }}</div>
                  <div class="meta">
                    {{ $it->sku ? 'SKU: '.$it->sku : 'SKU: —' }}
                  </div>
                </div>
                <div class="right">
                  <div class="qty">{{ (int)$it->qty }} x {{ $fmt($it->price) }}</div>
                  <div class="price">{{ $fmt($it->amount) }}</div>
                </div>
              </div>
            @endforeach
          </div>
        </div>

        <div class="card" style="margin-top:16px;">
          <div class="head">
            <h3>Resumen</h3>
            <span class="badge {{ $badge[1] }}">{{ $badge[0] }}</span>
          </div>
          <div class="summary">
            <div class="sumrow"><span>Subtotal</span><strong>{{ $fmt($order->subtotal) }}</strong></div>
            <div class="sumrow"><span>Envío</span><strong>{{ $fmt($order->shipping_amount) }}</strong></div>
            <div class="sumrow" style="border-top:1px solid #f1f5f9; margin-top:8px; padding-top:12px;">
              <span class="total">Total</span>
              <strong class="total">{{ $fmt($order->total) }}</strong>
            </div>

            <div class="note">
              Método de pago: <strong>{{ $isPaid ? 'Tarjeta (Stripe)' : 'Pendiente' }}</strong>
            </div>
          </div>
        </div>
      </div>

      {{-- RIGHT --}}
      <div class="card">
        <div class="head">
          <h3>Cliente</h3>
          <a class="btn" style="padding:9px 12px" href="{{ route('admin.orders.index', ['q' => $order->customer_email]) }}">Ver pedidos</a>
        </div>

        <div class="client">
          <div class="kv">
            <div class="k">Nombre:</div>
            <div class="v">
              {{ $order->customer_name ?? '—' }}
              <button class="copy" type="button" onclick="copyText('{{ addslashes($order->customer_name ?? '') }}')">⧉</button>
            </div>
          </div>

          <div class="kv">
            <div class="k">Correo electrónico:</div>
            <div class="v">
              {{ $order->customer_email ?? '—' }}
              <button class="copy" type="button" onclick="copyText('{{ addslashes($order->customer_email ?? '') }}')">⧉</button>
            </div>
          </div>

          <div class="kv">
            <div class="k">Número de teléfono:</div>
            <div class="v">
              {{ $phone ?? '—' }}
              <button class="copy" type="button" onclick="copyText('{{ addslashes($phone ?? '') }}')">⧉</button>
            </div>
          </div>

          <div class="kv">
            <div class="k">Dirección:</div>
            <div class="v" style="max-width:320px">
              {{ $fullAddress ?: '—' }}
              <button class="copy" type="button" onclick="copyText('{{ addslashes($fullAddress) }}')">⧉</button>
            </div>
          </div>

          @if(!empty($contact))
          <div class="kv">
            <div class="k">Recibe:</div>
            <div class="v">
              {{ $contact }}
              <button class="copy" type="button" onclick="copyText('{{ addslashes($contact) }}')">⧉</button>
            </div>
          </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function copyText(text){
  if(!text) return;
  navigator.clipboard.writeText(text).then(()=> {
    // mini feedback
    const t = document.createElement('div');
    t.textContent = 'Copiado ✅';
    t.style.position='fixed';
    t.style.bottom='18px';
    t.style.right='18px';
    t.style.padding='10px 12px';
    t.style.border='1px solid #e8eef6';
    t.style.borderRadius='12px';
    t.style.background='#fff';
    t.style.boxShadow='0 16px 40px rgba(2,8,23,.10)';
    t.style.fontWeight='900';
    t.style.zIndex='9999';
    document.body.appendChild(t);
    setTimeout(()=>t.remove(), 1200);
  });
}
</script>
@endsection
