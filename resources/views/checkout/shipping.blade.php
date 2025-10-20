{{-- resources/views/checkout/shipping.blade.php --}}
@extends('layouts.web')
@section('title','Envío')

@section('content')
@php
  $cart      = is_array($cart ?? null) ? $cart : (array)session('cart', []);
  $subtotal  = (float) ($subtotal ?? array_reduce($cart, fn($c,$r)=> $c + (($r['price']??0)*($r['qty']??1)), 0));
  $selected  = $selected ?? ['code'=>null,'price'=>0,'name'=>null,'eta'=>null];
  $shipPrice = (float) ($selected['price'] ?? 0);
  $total     = $subtotal + $shipPrice;
@endphp

<style>
  .ck-wrap{display:grid;grid-template-columns:2fr 1fr;gap:18px}
  @media(max-width: 980px){ .ck-wrap{grid-template-columns:1fr} }
  .card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 8px 24px rgba(2,8,23,.04)}
  .card-h{padding:16px 18px;border-bottom:1px solid #eef2f7;display:flex;align-items:center;justify-content:space-between;gap:12px}
  .card-b{padding:16px 18px}
  .muted{color:#6b7280}
  .btn{display:inline-flex;align-items:center;justify-content:center;border-radius:12px;padding:10px 14px;font-weight:800;text-decoration:none;border:1px solid #dbe2ea;background:#fff;cursor:pointer}
  .btn-primary{background:#0f3bd6;border-color:#0f3bd6;color:#fff}
  .btn-primary:disabled{opacity:.5;cursor:not-allowed}
  .stepper{display:flex;gap:22px;align-items:center;margin:0 0 18px}
  .step{display:flex;align-items:center;gap:10px;font-weight:800;color:#334155}
  .dot{width:28px;height:28px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;border:2px solid #0f3bd6}
  .dot.active{background:#0f3bd6;color:#fff}
  .carrier{display:flex;gap:12px;align-items:flex-start;border:1px solid #e5e7eb;border-radius:12px;padding:12px;cursor:pointer}
  .carrier:hover{background:#f8fbff;border-color:#c4d1ff}
  .carrier input{margin-top:2px}
  .car-head{display:flex;justify-content:space-between;gap:12px;align-items:center}
  .sum-row{display:flex;justify-content:space-between;margin:8px 0;font-weight:800}
  .line{border:0;border-top:1px solid #eef2f7;margin:16px 0}
</style>

<div class="stepper" aria-label="Progreso de compra">
  <div class="step"><span class="dot">1</span> Entrega</div>
  <div class="step"><span class="dot">2</span> Factura</div>
  <div class="step"><span class="dot active">3</span> Envío</div>
  <div class="step"><span class="dot">4</span> Pago</div>
</div>

<div class="ck-wrap">
  {{-- Izquierda --}}
  <div class="card">
    <div class="card-h">
      <div>
        <h2 style="margin:0 0 2px;font-weight:900">Elige paquetería</h2>
        <div class="muted">Selecciona el servicio de envío para tu pedido.</div>
      </div>
      <a href="{{ route('checkout.start') }}" class="btn">Cambiar dirección</a>
    </div>

    <div class="card-b">
      {{-- Dirección seleccionada --}}
      @if($address)
        @php
          $addrLines = [
            trim(($address->street ?? '').' '.($address->ext_number ?? '').($address->int_number ? ' Int '.$address->int_number : '')),
            trim(($address->colony ?? '').', CP '.($address->postal_code ?? '')),
            trim(($address->municipality ?? '').', '.($address->state ?? '')),
            $address->between_street_1 ? 'Entre: '.$address->between_street_1.($address->between_street_2 ? ' y '.$address->between_street_2 : '') : null,
            $address->references ? 'Ref.: '.$address->references : null,
          ];
        @endphp
        <div class="card" style="border:1px dashed #c4d1ff;background:#f8fbff;margin-bottom:12px">
          <div class="card-b" style="display:flex;gap:12px;align-items:flex-start">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M3 7h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z" stroke="#0f3bd6" stroke-width="2"/><path d="M3 7l3-4h12l3 4" stroke="#0f3bd6" stroke-width="2"/></svg>
            <div>
              <strong>Entregar en:</strong>
              <div class="muted" style="margin-top:4px;white-space:pre-line">{{ implode("\n", array_filter($addrLines)) }}</div>
            </div>
          </div>
        </div>
      @else
        <div class="muted" style="margin-bottom:10px">
          No hay dirección seleccionada. <a href="{{ route('checkout.start') }}">Agrega una</a>.
        </div>
      @endif

      {{-- Lista de carriers --}}
      <form id="ship-form" method="post" action="{{ route('checkout.shipping.select') }}">
        @csrf
        <div style="display:grid;gap:10px" id="carriers">
          @foreach(($carriers ?? []) as $c)
            @php $checked = ($selected['code'] ?? null) === $c['code']; @endphp
            <label class="carrier">
              <input type="radio" name="code" value="{{ $c['code'] }}" {{ $checked ? 'checked' : '' }}>
              <div style="flex:1">
                <div class="car-head">
                  <div style="font-weight:900">{{ $c['name'] }}</div>
                  <div style="font-weight:900">${{ number_format($c['price'],2) }}</div>
                </div>
                <div class="muted">{{ $c['eta'] }}</div>
                {{-- Valores ocultos complementarios --}}
                <input type="hidden" name="price_{{ $c['code'] }}" value="{{ $c['price'] }}">
                <input type="hidden" name="name_{{ $c['code'] }}"  value="{{ $c['name']  }}">
                <input type="hidden" name="eta_{{ $c['code'] }}"   value="{{ $c['eta']   }}">
              </div>
            </label>
          @endforeach
        </div>

        <input type="hidden" name="price" id="ship-price" value="{{ $shipPrice }}">
        <input type="hidden" name="name"  id="ship-name"  value="{{ $selected['name'] ?? '' }}">
        <input type="hidden" name="eta"   id="ship-eta"   value="{{ $selected['eta']  ?? '' }}">

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:12px">
          <a class="btn" href="{{ route('web.cart.index') }}">Regresar al carrito</a>
          <button class="btn btn-primary" id="btn-continue" disabled>Continuar a pago</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Sidebar --}}
  <aside class="card" aria-label="Resumen">
    <div class="card-b">
      <h3 style="margin:0 0 8px;font-weight:900">Resumen</h3>
      <div class="sum-row"><span>Subtotal</span><span id="sum-subtotal">${{ number_format($subtotal,2) }}</span></div>
      <div class="sum-row"><span>Envío</span><span id="sum-envio">{{ $shipPrice>0 ? '$'.number_format($shipPrice,2) : '—' }}</span></div>
      <hr class="line">
      <div class="sum-row" style="font-size:1.12rem"><span>Total</span><span id="sum-total">${{ number_format($total,2) }}</span></div>
      <div class="muted" style="margin-top:6px;">Precios incluyen IVA</div>

      <hr class="line">
      <h4 style="margin:0 0 10px;font-weight:900">Tu pedido</h4>
      @forelse($cart as $row)
        <div style="display:grid;grid-template-columns:auto 1fr auto;gap:10px;align-items:center;padding:8px 0;border-bottom:1px solid #f1f5f9">
          <img src="{{ $row['image'] ?? asset('images/placeholder.png') }}" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb">
          <div>
            <div style="font-weight:800">{{ $row['name'] ?? 'Producto' }}</div>
            <div class="muted">x{{ $row['qty'] ?? 1 }}</div>
          </div>
          <div style="font-weight:800">${{ number_format(($row['price'] ?? 0) * ($row['qty'] ?? 1), 2) }}</div>
        </div>
      @empty
        <div class="muted">Tu carrito está vacío.</div>
      @endforelse
    </div>
  </aside>
</div>

@push('scripts')
<script>
(function(){
  const $ = s => document.querySelector(s);
  const $$ = s => Array.from(document.querySelectorAll(s));
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  const subtotalEl = $('#sum-subtotal');
  const envioEl    = $('#sum-envio');
  const totalEl    = $('#sum-total');
  const btn        = $('#btn-continue');

  // Habilita el botón si hay selección previa
  function initState(){
    const anyChecked = !!document.querySelector('input[name="code"]:checked');
    btn.disabled = !anyChecked;
  }

  function format(n){ try { return new Intl.NumberFormat('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2}).format(n); } catch(e){ return (Math.round(n*100)/100).toFixed(2); } }

  // Cuando cambias de carrier, actualiza hidden fields y totales
  $$('#carriers input[type="radio"][name="code"]').forEach(radio=>{
    radio.addEventListener('change', (e)=>{
      const code = e.target.value;
      const price = parseFloat(document.querySelector(`[name="price_${code}"]`)?.value || '0');
      const name  = document.querySelector(`[name="name_${code}"]`)?.value || '';
      const eta   = document.querySelector(`[name="eta_${code}"]`)?.value || '';

      // Set hidden
      $('#ship-price').value = price;
      $('#ship-name').value  = name;
      $('#ship-eta').value   = eta;

      // Totales
      const subtotal = parseFloat((subtotalEl.textContent||'').replace(/[^0-9.]/g,'')) || 0;
      envioEl.textContent = price>0 ? '$'+format(price) : '—';
      totalEl.textContent = '$' + format(subtotal + price);

      // Habilitar botón
      btn.disabled = false;
    });
  });

  // Submit normal (POST) ya redirige a payment en tu controller
  // Solo agrego un guardado visual durante envío:
  $('#ship-form')?.addEventListener('submit', ()=>{
    btn.disabled = true;
    btn.textContent = 'Guardando...';
  });

  initState();
})();
</script>
@endpush
@endsection
