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

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet"/>

<style>
  /* ====================== VARIABLES ====================== */
  :root {
    --bg: #f9fafb;
    --card: #ffffff;
    --ink: #111111;
    --text: #333333;
    --muted: #888888;
    --line: #ebebeb;
    --blue: #007aff;
    --blue-soft: #e6f0ff;
    --success: #15803d;
    --success-soft: #e6ffe6;
    --danger: #ff4a4a;
    --danger-soft: #ffebeb;
  }

  /* ====================== BASE ====================== */
  body {
    font-family: "Quicksand", system-ui, -apple-system, sans-serif;
    background: var(--bg);
    color: var(--text);
    margin: 0;
  }

  h1, h2, h3, h4, h5, strong {
    color: var(--ink);
    margin: 0;
  }

  .muted {
    color: var(--muted);
    font-weight: 500;
  }

  /* ====================== LAYOUT ====================== */
  .ck-page {
    width: 100%;
    max-width: 1100px;
    margin: 0 auto;
    padding: clamp(20px, 4vw, 40px) 20px;
    box-sizing: border-box;
  }

  .ck-wrap {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 32px;
    width: 100%;
    align-items: start;
  }
  @media(max-width: 980px){
    .ck-wrap { grid-template-columns: 1fr; gap: 24px; }
  }

  /* ====================== CARDS ====================== */
  .card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    overflow: hidden;
  }
  .card-h {
    padding: 24px;
    border-bottom: 1px solid var(--line);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
  }
  .card-b {
    padding: 24px;
  }

  /* ====================== STEPPER ====================== */
  .stepper {
    display: flex;
    gap: 24px;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 32px;
  }
  .step {
    display: flex; 
    align-items: center; 
    gap: 10px;
    font-weight: 600;
    color: var(--muted);
    font-size: 0.95rem;
  }
  .step.active {
    color: var(--ink);
    font-weight: 700;
  }
  .dot {
    width: 28px; height: 28px;
    border-radius: 999px;
    display: inline-flex; align-items: center; justify-content: center;
    border: 1px solid var(--line);
    background: var(--bg);
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--muted);
  }
  .step.active .dot {
    background: var(--blue-soft);
    color: var(--blue);
    border-color: var(--blue-soft);
  }

  /* ====================== BUTTONS ====================== */
  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    padding: 10px 18px;
    font-weight: 600;
    font-size: 0.95rem;
    font-family: inherit;
    text-decoration: none;
    cursor: pointer;
    gap: 8px;
    border: none;
    transition: transform 0.15s ease, background 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
  }
  .btn:active { transform: scale(0.98); }
  .btn:disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
  
  .btn-primary { background: var(--blue); color: #ffffff; }
  .btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0, 122, 255, 0.15); }
  
  .btn-ghost { background: transparent; color: #555555; border: 1px solid var(--line); }
  .btn-ghost:hover { background: var(--bg); color: var(--ink); }

  /* ====================== PAYMENT OPTIONS ====================== */
  .pay-opt {
    display: flex; gap: 16px; align-items: center;
    border: 1px solid var(--line); border-radius: 12px; padding: 20px;
    cursor: pointer; transition: all 0.2s ease; background: var(--card);
  }
  .pay-opt.selected {
    border-color: var(--blue); box-shadow: 0 0 0 1px var(--blue);
  }
  .pay-opt input[type="radio"] {
    accent-color: var(--blue); width: 18px; height: 18px; margin: 0; cursor: pointer;
  }

  /* ====================== SUMMARY ====================== */
  .sum-row {
    display: flex; justify-content: space-between; margin: 14px 0; font-weight: 600; color: var(--text);
  }
  .sum-row.total {
    font-size: 1.15rem; color: var(--ink); font-weight: 700;
  }
  .line { border: 0; border-top: 1px solid var(--line); margin: 20px 0; }
  
  .text-error { color: var(--danger); font-size: 0.9rem; font-weight: 600; margin-top: 8px; }
</style>

<div class="ck-page">
  <div class="stepper" aria-label="Progreso de compra">
    <div class="step"><span class="dot">1</span> Entrega</div>
    <div class="step"><span class="dot">2</span> Factura</div>
    <div class="step"><span class="dot">3</span> Envío</div>
    <div class="step active"><span class="dot">4</span> Pago</div>
  </div>

  <div class="ck-wrap">
    {{-- Izquierda: métodos de pago --}}
    <div class="card">
      <div class="card-h">
        <div>
          <h2 style="font-weight:700; margin-bottom: 4px;">Forma de pago</h2>
          <div class="muted">Procesa tu pago de forma rápida y segura.</div>
        </div>
        <a href="{{ route('checkout.shipping') }}" class="btn btn-ghost">Modificar envío</a>
      </div>

      <div class="card-b" style="display:grid; gap:24px;">
        
        {{-- Resumen de envío y dirección --}}
        <div class="card" style="border: 1px solid var(--blue); background: var(--blue-soft);">
          <div class="card-b" style="display:grid; gap:12px;">
            <div style="display:flex; justify-content:space-between; gap:16px; align-items:flex-start;">
              <div style="display:flex; gap:16px; align-items:flex-start;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2" style="margin-top: 2px;">
                  <path d="M5 12h14M12 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <div>
                  <strong style="color: var(--blue); font-size: 1.05rem;">Resumen de Entrega</strong>
                  <div class="muted" style="margin-top: 4px; color: var(--ink); font-weight: 600;">
                    {{ $ship['name'] ?? 'Envío estándar' }} {!! $ship['eta'] ? '· <span style="font-weight:500">'.$ship['eta'].'</span>' : '' !!}
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
                    <div class="muted" style="white-space:pre-line; margin-top: 8px; line-height: 1.5;">{{ implode("\n", array_filter($addrLines)) }}</div>
                  @endif
                </div>
              </div>
              
              <div style="font-weight:700; color: var(--blue); font-size: 1.05rem; white-space: nowrap;">
                {{ $shipPrice>0 ? '$'.number_format($shipPrice,2) : 'GRATIS' }}
              </div>
            </div>
          </div>
        </div>

        {{-- Opción: Tarjeta (Stripe Checkout) --}}
        <div>
          <h3 style="font-weight:700; font-size: 1.1rem; margin-bottom: 12px;">Selecciona tu método</h3>
          <label class="pay-opt selected" id="opt-card">
            <input type="radio" name="pay_method" value="card" checked>
            <div style="flex:1">
              <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap: wrap;">
                <div style="font-weight:700; font-size: 1.05rem; color: var(--ink);">Tarjeta de Crédito o Débito</div>
                <div class="muted" style="display:flex; gap:8px; align-items:center; font-size: 0.85rem; font-weight: 700;">
                  <span>VISA</span> · <span>MASTERCARD</span> · <span>AMEX</span>
                </div>
              </div>
              <div class="muted" style="margin-top: 6px; font-size: 0.95rem;">Serás redirigido a la pasarela segura de Stripe.</div>
            </div>
          </label>
        </div>

        <div id="pay-msg" class="text-error" style="display:none; text-align: right;"></div>

        {{-- Botón pagar --}}
        <div style="display:flex; gap:16px; justify-content:flex-end; border-top: 1px solid var(--line); padding-top: 24px; margin-top: 8px;">
          <a class="btn btn-ghost" href="{{ route('web.cart.index') }}" style="border: none;">Cancelar</a>
          <button class="btn btn-primary" id="btn-pay" style="padding: 12px 32px; font-size: 1.05rem;">
            Pagar ${{ number_format($total,2) }}
          </button>
        </div>

      </div>
    </div>

    {{-- Derecha: resumen --}}
    <aside class="card" aria-label="Resumen">
      <div class="card-b">
        <h3 style="font-weight:700; margin:0 0 16px; font-size: 1.1rem;">Resumen de Orden</h3>
        
        <div class="sum-row"><span>Subtotal</span><span id="sum-subtotal" style="color:var(--ink);">${{ number_format($subtotal,2) }}</span></div>
        <div class="sum-row"><span>Envío</span><span id="sum-envio" class="muted">{{ $shipPrice>0 ? '$'.number_format($shipPrice,2) : 'GRATIS' }}</span></div>
        <hr class="line">
        <div class="sum-row total"><span>Total</span><span id="sum-total">${{ number_format($total,2) }}</span></div>
        <div class="muted" style="font-size: 0.85rem; margin-top: 8px; text-align: right;">Precios incluyen IVA</div>

        <hr class="line">
        <h4 style="font-weight:700; margin:0 0 16px; font-size: 1rem;">Tu pedido</h4>
        
        <div style="max-height: 400px; overflow-y: auto; padding-right: 8px;">
          @forelse($cart as $row)
            <div style="display:grid; grid-template-columns:auto 1fr auto; gap:16px; align-items:center; padding:12px 0; border-bottom:1px solid var(--line);">
              <img src="{{ $row['image'] ?? asset('images/placeholder.png') }}" alt="" style="width:48px; height:48px; object-fit:cover; border-radius:8px; border:1px solid var(--line); background:var(--bg);">
              <div>
                <div style="font-weight:600; color: var(--ink);">{{ $row['name'] ?? 'Producto' }}</div>
                <div class="muted" style="font-size: 0.9rem; margin-top: 4px;">Cant: {{ $row['qty'] ?? 1 }}</div>
              </div>
              <div style="font-weight:700; color: var(--ink);">${{ number_format(($row['price'] ?? 0) * ($row['qty'] ?? 1), 2) }}</div>
            </div>
          @empty
            <div class="muted" style="padding: 20px 0; text-align: center;">Tu carrito está vacío.</div>
          @endforelse
        </div>
      </div>
    </aside>
  </div>
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
    btn.textContent = 'Procesando pago...';
    $('#pay-msg').style.display = 'none'; // Oculta mensajes de error previos

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
      showMsg('No se pudo iniciar el pago seguro. Intenta de nuevo.');
      btn.disabled = false;
      btn.textContent = original;
    }
  });
})();
</script>
@endpush
@endsection