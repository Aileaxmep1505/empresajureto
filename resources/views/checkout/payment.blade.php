{{-- resources/views/checkout/payment.blade.php --}}
@extends('layouts.web')
@section('title','Pago')

@section('content')
@php
  $cart      = is_array($cart ?? null) ? $cart : (array)session('cart', []);
  $subtotal  = (float) ($subtotal ?? array_reduce($cart, fn($c,$r)=> $c + (($r['price']??0)*($r['qty']??1)), 0));
  $ship      = $shipping ?? (array) session('checkout.shipping', ['price'=>0,'name'=>null,'eta'=>null,'code'=>null]);
  $shipPrice = (float) ($ship['price'] ?? 0);
  $total     = (float) ($total ?? ($subtotal + $shipPrice));
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
  .pay-opt{display:flex;gap:12px;align-items:center;border:1px solid #e5e7eb;border-radius:12px;padding:12px}
  .pay-opt.selected{background:#f8fbff;border-color:#c4d1ff}
  .sum-row{display:flex;justify-content:space-between;margin:8px 0;font-weight:800}
  .line{border:0;border-top:1px solid #eef2f7;margin:16px 0}
</style>

<div class="stepper" aria-label="Progreso de compra">
  <div class="step"><span class="dot">1</span> Entrega</div>
  <div class="step"><span class="dot">2</span> Factura</div>
  <div class="step"><span class="dot">3</span> Envío</div>
  <div class="step"><span class="dot active">4</span> Pago</div>
</div>

<div class="ck-wrap">
  {{-- Izquierda: métodos de pago --}}
  <div class="card">
    <div class="card-h">
      <div>
        <h2 style="margin:0 0 2px;font-weight:900">Forma de pago</h2>
        <div class="muted">Paga de forma segura con Stripe.</div>
      </div>
      <a href="{{ route('checkout.shipping') }}" class="btn">Cambiar envío</a>
    </div>

    <div class="card-b" style="display:grid;gap:12px">
      {{-- Resumen de envío y dirección --}}
      <div class="card" style="border:1px dashed #c4d1ff;background:#f8fbff">
        <div class="card-b" style="display:grid;gap:8px">
          <div style="display:flex;justify-content:space-between;gap:12px;align-items:center">
            <div>
              <strong>Paquetería:</strong>
              <div class="muted">
                {{ $ship['name'] ?? '—' }} {!! $ship['eta'] ? '· '.$ship['eta'] : '' !!}
              </div>
            </div>
            <div style="font-weight:900">{{ $shipPrice>0 ? '$'.number_format($shipPrice,2) : '—' }}</div>
          </div>

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
            <div class="muted" style="white-space:pre-line">{{ implode("\n", array_filter($addrLines)) }}</div>
          @endif
        </div>
      </div>

      {{-- Opción: Tarjeta (Stripe Checkout) --}}
      <label class="pay-opt selected" id="opt-card">
        <input type="radio" name="pay_method" value="card" checked style="margin:0">
        <div style="flex:1">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:12px">
            <div style="font-weight:900">Tarjeta de crédito o débito</div>
            {{-- Logos simples --}}
            <div class="muted" style="display:flex;gap:8px;align-items:center">
              <span>Visa</span><span>Mastercard</span><span>AmEx</span>
            </div>
          </div>
          <div class="muted">Serás redirigido a Stripe Checkout.</div>
        </div>
      </label>

      {{-- Botón pagar --}}
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <a class="btn" href="{{ route('web.cart.index') }}">Regresar al carrito</a>
        <button class="btn btn-primary" id="btn-pay">Pagar ${{ number_format($total,2) }}</button>
      </div>

      <div id="pay-msg" class="muted" style="display:none"></div>
    </div>
  </div>

  {{-- Derecha: resumen --}}
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
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  function showMsg(t){
    const el = $('#pay-msg');
    el.textContent = t;
    el.style.display = 'block';
  }

  // Pagar con Stripe Checkout (crea sesión del carrito en tu backend)
  $('#btn-pay')?.addEventListener('click', async ()=>{
    const btn = $('#btn-pay');
    btn.disabled = true;
    const original = btn.textContent;
    btn.textContent = 'Creando pago...';

    try{
      const res = await fetch('{{ route('checkout.cart') }}', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        // No hace falta enviar nada: el backend usa el carrito en sesión
        body: JSON.stringify({ _source: 'payment.blade' })
      });

      if(!res.ok){
        const txt = await res.text();
        throw new Error(txt || 'Error al crear la sesión de pago.');
      }

      const data = await res.json().catch(()=>({}));
      if(!data?.url) throw new Error('No se recibió URL de Stripe.');

      window.location.href = data.url; // redirige a Stripe Checkout
    }catch(err){
      console.error(err);
      showMsg('No se pudo iniciar el pago. Intenta de nuevo.');
      btn.disabled = false;
      btn.textContent = original;
    }
  });
})();
</script>
@endpush
@endsection
