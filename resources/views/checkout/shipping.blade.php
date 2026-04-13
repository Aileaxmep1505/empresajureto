{{-- resources/views/checkout/shipping.blade.php --}}
@extends('layouts.web')
@section('title','Envío')

@section('content')
@php
  use Illuminate\Support\Str;

  $cart      = is_array($cart ?? null) ? $cart : (array)session('cart', []);
  $subtotal  = (float) ($subtotal ?? array_reduce($cart, fn($c,$r)=> $c + (($r['price']??0)*($r['qty']??1)), 0));
  $selected  = $selected ?? ['code'=>null,'price'=>0,'name'=>null,'eta'=>null,'service'=>null];
  $shipPrice = (float) ($selected['price'] ?? 0);
  $total     = $subtotal + $shipPrice;

  $logoMap = [
    'dhl' => 'dhl.svg', 'fedex' => 'fedex.svg', 'ups' => 'ups.svg',
    'estafeta' => 'estafeta.svg', 'redpack' => 'redpack.svg', '99minutos' => '99minutos.svg',
    'paquetexpress' => 'paquetexpress.svg', 'sendex' => 'sendex.svg', 'mexpost' => 'mexpost.svg',
    'carssa' => 'carssa.svg', 'ivoy' => 'ivoy.svg', 'dypaq' => 'dypaq.svg',
  ];

  $minPrice = null; $minIdx = -1;
  foreach(($carriers ?? []) as $i=>$c){
    $p = (float)($c['price'] ?? INF);
    if(!is_finite($p)) continue;
    if($minPrice === null || $p < $minPrice){ $minPrice = $p; $minIdx = $i; }
  }

  $threshold = (float) env('FREE_SHIPPING_THRESHOLD', 5000);
  
  // CONFIGURACIÓN DE VISIBILIDAD: Aquí controlas cuántos salen al inicio
  $limitInitial = 12; 
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet"/>

<style>
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
  }

  body {
    font-family: "Quicksand", system-ui, -apple-system, sans-serif;
    background: var(--bg);
    color: var(--text);
    margin: 0;
  }

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
  @media(max-width: 980px){ .ck-wrap { grid-template-columns: 1fr; gap: 24px; } }

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
    align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
  }
  .card-b { padding: 24px; }

  .stepper { display: flex; gap: 24px; align-items: center; flex-wrap: wrap; margin-bottom: 32px; }
  .step { display: flex; align-items: center; gap: 10px; font-weight: 600; color: var(--muted); font-size: 0.95rem; }
  .step.active { color: var(--ink); font-weight: 700; }
  .dot {
    width: 28px; height: 28px; border-radius: 999px;
    display: inline-flex; align-items: center; justify-content: center;
    border: 1px solid var(--line); background: var(--bg);
    font-size: 0.85rem; font-weight: 700; color: var(--muted);
  }
  .step.active .dot { background: var(--blue-soft); color: var(--blue); border-color: var(--blue-soft); }

  .btn {
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 8px; padding: 10px 18px;
    font-weight: 600; font-size: 0.95rem; font-family: inherit;
    text-decoration: none; cursor: pointer; gap: 8px; border: none;
    transition: all 0.2s ease;
  }
  .btn:active { transform: scale(0.98); }
  .btn:disabled { opacity: 0.5; cursor: not-allowed; }
  .btn-primary { background: var(--blue); color: #ffffff; }
  .btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0, 122, 255, 0.15); }
  .btn-ghost { background: transparent; color: #555555; border: 1px solid var(--line); }
  .btn-ghost:hover { background: var(--bg); color: var(--ink); }

  /* Filtros y Buscador */
  .filters { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; align-items: center; }
  .seg { display: inline-flex; background: var(--bg); border: 1px solid var(--line); border-radius: 8px; padding: 4px; gap: 4px; }
  .seg button {
    background: transparent; border: none; border-radius: 6px; padding: 8px 14px;
    font-weight: 600; font-size: 0.9rem; color: var(--muted); font-family: inherit;
    cursor: pointer; transition: all 0.2s ease;
  }
  .seg button.active { background: var(--card); color: var(--ink); font-weight: 700; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
  
  .search {
    flex: 1; min-width: 220px; display: flex; align-items: center; gap: 8px;
    background: var(--card); border: 1px solid var(--line); border-radius: 8px; padding: 10px 14px;
  }
  .search input { border: none; outline: none; width: 100%; font-family: inherit; font-size: 0.95rem; background: transparent; }

  /* Paqueterías */
  .carriers-grid { display: grid; gap: 12px; }
  .carrier {
    display: grid; grid-template-columns: auto 1fr auto; gap: 16px; align-items: center;
    padding: 20px; border: 1px solid var(--line); border-radius: 12px; background: var(--card);
    cursor: pointer; transition: all 0.2s ease; position: relative;
  }
  .carrier:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.03); }
  .carrier.active { border-color: var(--blue); box-shadow: 0 0 0 1px var(--blue); }
  .carrier input[type="radio"] { accent-color: var(--blue); width: 18px; height: 18px; }
  
  .carrier-logo {
    width: 68px; height: 48px; display: flex; align-items: center; justify-content: center;
    border: 1px solid var(--line); border-radius: 8px; background: var(--bg);
  }
  .carrier-logo img { max-width: 80%; max-height: 28px; object-fit: contain; }
  
  .car-title { font-weight: 700; color: var(--ink); font-size: 1.05rem; }
  .car-sub { font-size: 0.9rem; color: var(--muted); font-weight: 500; }
  .car-price { font-weight: 700; font-size: 1.1rem; color: var(--ink); }
  
  .chip { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-weight: 700; font-size: 0.8rem; }
  .chip.info { background: var(--blue-soft); color: var(--blue); }
  .chip.success { background: var(--success-soft); color: var(--success); }
  
  .ribbon {
    position: absolute; left: 16px; top: -10px;
    background: var(--success-soft); color: var(--success);
    padding: 2px 10px; font-size: 0.75rem; font-weight: 700; border-radius: 999px;
  }

  .sum-row { display: flex; justify-content: space-between; margin: 14px 0; font-weight: 600; }
  .sum-row.total { font-size: 1.15rem; color: var(--ink); font-weight: 700; }
  .line { border: 0; border-top: 1px solid var(--line); margin: 20px 0; }

  .more-wrap { position: relative; }
  .more-fader {
    position: absolute; left: 0; right: 0; bottom: 0; height: 80px;
    background: linear-gradient(180deg, rgba(255,255,255,0), var(--card));
    pointer-events: none;
  }
</style>

<div class="ck-page">
  <div class="stepper">
    <div class="step"><span class="dot">1</span> Entrega</div>
    <div class="step"><span class="dot">2</span> Factura</div>
    <div class="step active"><span class="dot">3</span> Envío</div>
    <div class="step"><span class="dot">4</span> Pago</div>
  </div>

  <div class="ck-wrap">
    <div class="card">
      <div class="card-h">
        <div>
          <h2 style="font-weight:700;">Método de Envío</h2>
          <div class="muted">Selecciona una de las {{ count($carriers ?? []) }} opciones disponibles.</div>
        </div>
        <a href="{{ route('checkout.start') }}" class="btn btn-ghost">Cambiar dirección</a>
      </div>

      <div class="card-b">
        {{-- Dirección --}}
        @if($address)
          <div class="card" style="border: 1px solid var(--blue); background: var(--blue-soft); margin-bottom: 32px;">
            <div class="card-b" style="display:flex; gap:16px; align-items:flex-start">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2">
                <path d="M12 22s8-4 8-10a8 8 0 10-16 0c0 6 8 10 8 10z"/><circle cx="12" cy="12" r="3"/>
              </svg>
              <div>
                <strong style="color: var(--blue);">Entregar en:</strong>
                <div style="color: var(--ink); margin-top: 4px; font-size: 0.95rem;">
                  {{ $address->street }} {{ $address->ext_number }}, {{ $address->colony }}, {{ $address->postal_code }}
                </div>
              </div>
            </div>
          </div>
        @endif

        {{-- Filtros --}}
        <div class="filters">
          <div class="seg">
            <button type="button" class="active" data-sort="recommended">Recomendado</button>
            <button type="button" data-sort="price">Más barato</button>
            <button type="button" data-sort="eta">Más rápido</button>
          </div>
          <div class="search">
            <input id="q" type="search" placeholder="Filtrar por nombre (ej. DHL, Fedex)...">
          </div>
        </div>

        <form id="ship-form" method="post" action="{{ route('checkout.shipping.select') }}">
          @csrf
          <div id="carriers" class="more-wrap" data-collapsed="true" data-visible="{{ $limitInitial }}">
            <div class="carriers-grid">
              @forelse($carriers ?? [] as $i => $c)
                @php
                  $name    = trim($c['name'] ?? ($c['carrier'] ?? 'Paquetería'));
                  $service = trim($c['service'] ?? '');
                  $price   = (float) ($c['price'] ?? 0);
                  $code    = (string) ($c['code'] ?? Str::slug($name.'-'.$service));
                  $slug    = Str::slug($c['carrier'] ?? $name);
                  $logo    = $logoMap[$slug] ?? 'generic-shipping.svg';
                  $checked = (($selected['code'] ?? null) === $code);
                @endphp
                <label class="carrier {{ $checked ? 'active' : '' }}" 
                       data-price="{{ $price }}" 
                       data-name="{{ Str::upper($name) }}"
                       data-service="{{ $service }}"
                       @if($i >= $limitInitial) style="display:none" data-extra="true" @endif>
                  
                  @if($i === $minIdx) <span class="ribbon">Mejor Precio</span> @endif

                  <div class="carrier-logo">
                    <img src="{{ asset('images/carriers/'.$logo) }}" onerror="this.src='{{ asset('images/carriers/generic-shipping.svg') }}'">
                  </div>

                  <div>
                    <div class="car-title">{{ Str::upper($name) }} <span class="car-sub">{{ $service }}</span></div>
                    <div class="car-sub">{{ $c['eta'] ?? 'Entrega estimada' }}</div>
                  </div>

                  <div style="text-align: right;">
                    <div class="car-price">${{ number_format($price, 2) }}</div>
                    <input type="radio" name="code" value="{{ $code }}" {{ $checked ? 'checked' : '' }}>
                  </div>

                  <input type="hidden" name="price_{{ $code }}" value="{{ $price }}">
                  <input type="hidden" name="name_{{ $code }}" value="{{ $name }}">
                  <input type="hidden" name="service_{{ $code }}" value="{{ $service }}">
                </label>
              @empty
                <div class="muted">No hay paqueterías disponibles para tu zona.</div>
              @endforelse
            </div>

            @if(count($carriers ?? []) > $limitInitial)
              <div class="more-fader" id="more-fader"></div>
              <div class="more-actions">
                <button type="button" class="btn btn-ghost" id="btn-more">Mostrar todas las opciones ({{ count($carriers) - $limitInitial }} más)</button>
              </div>
            @endif
          </div>

          <input type="hidden" name="price" id="ship-price" value="{{ $shipPrice }}">
          <input type="hidden" name="name" id="ship-name" value="{{ $selected['name'] ?? '' }}">

          <div style="display:flex; gap:12px; justify-content:flex-end; margin-top: 32px;">
            <a class="btn btn-ghost" href="{{ route('web.cart.index') }}">Regresar</a>
            <button class="btn btn-primary" id="btn-continue" {{ $selected['code'] ? '' : 'disabled' }}>Continuar al Pago</button>
          </div>
        </form>
      </div>
    </div>

    {{-- Sidebar --}}
    <aside class="card">
      <div class="card-b">
        <h3 style="font-weight:700; margin-bottom: 16px;">Resumen</h3>
        <div class="sum-row"><span>Subtotal</span><span>${{ number_format($subtotal, 2) }}</span></div>
        <div class="sum-row"><span>Envío</span><span id="sum-envio">{{ $shipPrice > 0 ? '$'.number_format($shipPrice, 2) : '—' }}</span></div>
        <hr class="line">
        <div class="sum-row total"><span>Total</span><span id="sum-total">${{ number_format($total, 2) }}</span></div>
      </div>
    </aside>
  </div>
</div>

@push('scripts')
<script>
(function(){
  const $ = s => document.querySelector(s);
  const $$ = s => Array.from(document.querySelectorAll(s));

  const subtotal = {{ $subtotal }};
  const envioEl = $('#sum-envio');
  const totalEl = $('#sum-total');
  const btnCont = $('#btn-continue');

  // Radio Change
  $$('input[name="code"]').forEach(r => {
    r.addEventListener('change', e => {
      const code = e.target.value;
      const price = parseFloat($(`[name="price_${code}"]`).value);
      const name = $(`[name="name_${code}"]`).value;

      $$('.carrier').forEach(c => c.classList.remove('active'));
      e.target.closest('.carrier').classList.add('active');

      $('#ship-price').value = price;
      $('#ship-name').value = name;

      envioEl.textContent = price > 0 ? '$' + price.toFixed(2) : 'GRATIS';
      totalEl.textContent = '$' + (subtotal + price).toFixed(2);
      btnCont.disabled = false;
    });
  });

  // Ver más
  $('#btn-more')?.addEventListener('click', e => {
    $$('[data-extra="true"]').forEach(el => el.style.display = 'grid');
    $('#more-fader').style.display = 'none';
    e.target.style.display = 'none';
  });

  // Buscador simple
  $('#q').addEventListener('input', e => {
    const q = e.target.value.toLowerCase();
    $$('.carrier').forEach(el => {
      const text = el.innerText.toLowerCase();
      el.style.display = text.includes(q) ? 'grid' : 'none';
    });
  });
})();
</script>
@endpush
@endsection