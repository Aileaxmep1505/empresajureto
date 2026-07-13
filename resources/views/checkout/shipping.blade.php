{{-- resources/views/checkout/shipping.blade.php --}}
@extends('layouts.web')

@section('title','Envío')

@section('content')
@php
  use Illuminate\Support\Str;

  $cart = is_array($cart ?? null) ? $cart : session('cart', []);
  $subtotal = (float) ($subtotal ?? collect($cart)->sum(fn($r) => ((float)($r['price'] ?? 0)) * ((int)($r['qty'] ?? 1))));
  $selected = session('checkout.shipping', session('shipping', []));
  $shipPrice = (float) ($selected['price'] ?? 0);
  $total = $subtotal + $shipPrice;

  $minIdx = null;
  if (!empty($carriers ?? [])) {
    $minPrice = collect($carriers)->pluck('price')->map(fn($p)=>(float)$p)->min();
    foreach(($carriers ?? []) as $i=>$c){
      if((float)($c['price'] ?? 0) === (float)$minPrice){ $minIdx = $i; break; }
    }
  }

  $logoFallback = asset('images/carriers/generic-shipping.svg');
@endphp

<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

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

  body {
    font-family: "Quicksand", system-ui, -apple-system, sans-serif;
    background: var(--bg);
    color: var(--text);
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

  @media(max-width: 980px) {
    .ck-wrap { grid-template-columns: 1fr; gap: 24px; }
  }

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

  .card-b { padding: 24px; }

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
    transition: all 0.2s ease;
  }

  .btn:active { transform: scale(0.98); }
  .btn:disabled { opacity: 0.5; cursor: not-allowed; }
  .btn-primary { background: var(--blue); color: #ffffff; }
  .btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0, 122, 255, 0.15); }
  .btn-ghost { background: transparent; color: #555555; border: 1px solid var(--line); }
  .btn-ghost:hover { background: var(--bg); color: var(--ink); }

  .address-box {
    border: 1px solid var(--blue);
    background: var(--blue-soft);
    border-radius: 16px;
    margin-bottom: 32px;
    padding: 22px;
    display: flex;
    gap: 16px;
    align-items: flex-start;
  }

  .filters {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
    align-items: center;
  }

  .seg {
    display: inline-flex;
    background: var(--bg);
    border: 1px solid var(--line);
    border-radius: 8px;
    padding: 4px;
    gap: 4px;
  }

  .seg button {
    background: transparent;
    border: none;
    border-radius: 6px;
    padding: 8px 14px;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--muted);
    font-family: inherit;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .seg button.active {
    background: var(--card);
    color: var(--ink);
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  }

  .search {
    flex: 1;
    min-width: 220px;
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 8px;
    padding: 10px 14px;
  }

  .search input {
    border: none;
    outline: none;
    width: 100%;
    font-family: inherit;
    font-size: 0.95rem;
    background: transparent;
  }

  .carriers-scroll {
    max-height: 520px;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 6px 10px 6px 0;
    margin-right: -10px;
    scroll-behavior: smooth;
  }

  .carriers-scroll::-webkit-scrollbar {
    width: 10px;
  }

  .carriers-scroll::-webkit-scrollbar-track {
    background: #f4f5f7;
    border-radius: 999px;
  }

  .carriers-scroll::-webkit-scrollbar-thumb {
    background: #d7dce3;
    border-radius: 999px;
    border: 2px solid #f4f5f7;
  }

  .carriers-scroll::-webkit-scrollbar-thumb:hover {
    background: #bfc7d2;
  }

  .carriers-grid { display: grid; gap: 12px; }

  .shipping-actions {
    position: sticky;
    bottom: 0;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 18px;
    padding-top: 18px;
    background: linear-gradient(to bottom, rgba(255,255,255,.88), #fff 40%);
    border-top: 1px solid var(--line);
    z-index: 5;
  }

  @media(max-width: 700px) {
    .carriers-scroll { max-height: 430px; }
    .shipping-actions { justify-content: stretch; }
    .shipping-actions .btn { flex: 1; }
  }

  .carrier {
    display: grid;
    grid-template-columns: 128px minmax(0,1fr) auto;
    gap: 18px;
    align-items: center;
    padding: 20px;
    border: 1px solid var(--line);
    border-radius: 16px;
    background: var(--card);
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
  }

  @media(max-width: 700px) {
    .carrier {
      grid-template-columns: 92px minmax(0,1fr);
    }

    .carrier-price-wrap {
      grid-column: 1 / -1;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
  }

  .carrier:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.03);
  }

  .carrier.active {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .carrier input[type="radio"] {
    accent-color: var(--blue);
    width: 20px;
    height: 20px;
  }

  .carrier-logo {
    width: 112px;
    height: 74px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--line);
    border-radius: 16px;
    background: #fff;
    overflow: hidden;
  }

  @media(max-width: 700px) {
    .carrier-logo {
      width: 84px;
      height: 62px;
    }
  }

  .carrier-logo img {
    max-width: 86%;
    max-height: 46px;
    object-fit: contain;
    display: block;
  }

  .car-title {
    font-weight: 700;
    color: var(--ink);
    font-size: 1.05rem;
    text-transform: uppercase;
  }

  .car-sub {
    font-size: 0.9rem;
    color: var(--muted);
    font-weight: 500;
    margin-top: 2px;
  }

  .car-price {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--ink);
    white-space: nowrap;
  }

  .ribbon {
    position: absolute;
    left: 16px;
    top: -10px;
    background: var(--success-soft);
    color: var(--success);
    padding: 3px 12px;
    font-size: 0.75rem;
    font-weight: 700;
    border-radius: 999px;
  }

  .sum-row {
    display: flex;
    justify-content: space-between;
    margin: 14px 0;
    font-weight: 600;
  }

  .sum-row.total {
    font-size: 1.15rem;
    color: var(--ink);
    font-weight: 700;
  }

  .line {
    border: 0;
    border-top: 1px solid var(--line);
    margin: 20px 0;
  }

  .empty-rates {
    padding: 24px;
    border: 1px dashed var(--line);
    border-radius: 16px;
    background: var(--bg);
    color: var(--muted);
    text-align: center;
    font-weight: 600;
  }

  .muted { color: var(--muted); font-weight: 500; }
</style>

<div class="ck-page">
  <div class="ck-wrap">
    <div class="card">
      <div class="card-h">
        <div>
          <h2 style="font-weight:700; margin:0;">Método de Envío</h2>
          <div class="muted">Selecciona una opción disponible para tu dirección.</div>
        </div>
        <a href="{{ route('checkout.start') }}" class="btn btn-ghost">Cambiar dirección</a>
      </div>

      <div class="card-b">
        @if($address)
          <div class="address-box">
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
        @endif

        <div class="filters">
          <div class="seg">
            <button type="button" class="active" data-sort="recommended">Recomendado</button>
            <button type="button" data-sort="price">Más barato</button>
            <button type="button" data-sort="eta">Más rápido</button>
          </div>
          <div class="search">
            <input id="q" type="search" placeholder="Filtrar por paquetería o servicio">
          </div>
        </div>

        <form id="ship-form" method="post" action="{{ route('checkout.shipping.select') }}">
          @csrf
          <input type="hidden" name="force_redirect" value="1">

          <div class="carriers-scroll">
            <div id="carriers" class="carriers-grid">
            @forelse($carriers ?? [] as $i => $c)
              @php
                $name = trim($c['name'] ?? ($c['carrier'] ?? 'Paquetería'));
                $service = trim($c['service'] ?? '');
                $price = (float) ($c['price'] ?? 0);
                $currency = (string) ($c['currency'] ?? 'MXN');
                $code = (string) ($c['id'] ?? $c['code'] ?? md5(json_encode($c)));
                $carrierKey = (string) ($c['carrier_key'] ?? Str::slug(Str::ascii($c['carrier'] ?? $name)));
                $logoUrl = $c['logo_url'] ?? $logoFallback;
                $checked = (($selected['selected_id'] ?? $selected['code'] ?? null) === $code);
                $rawJson = json_encode($c['raw'] ?? $c, JSON_UNESCAPED_UNICODE);
              @endphp

              <label class="carrier {{ $checked ? 'active' : '' }}"
                     data-price="{{ $price }}"
                     data-name="{{ Str::upper($name) }}"
                     data-service="{{ $service }}"
                     data-eta="{{ $c['eta'] ?? '' }}">
                @if($i === $minIdx)
                  <span class="ribbon">Mejor precio</span>
                @endif

                <div class="carrier-logo">
                  <img src="{{ $logoUrl }}" alt="{{ $name }}" loading="lazy" onerror="this.onerror=null; this.src='{{ $logoFallback }}'">
                </div>

                <div>
                  <div class="car-title">{{ $name }}</div>
                  <div class="car-sub">{{ $service ?: 'Servicio disponible' }}</div>
                  <div class="car-sub">{{ $c['eta'] ?? 'Entrega estimada' }}</div>
                </div>

                <div class="carrier-price-wrap" style="text-align:right;">
                  <div class="car-price">{{ $price > 0 ? '$'.number_format($price, 2) : 'Gratis' }}</div>
                  <input type="radio" name="code" value="{{ $code }}" {{ $checked ? 'checked' : '' }}>
                </div>

                <input type="hidden" name="price_{{ $code }}" value="{{ $price }}">
                <input type="hidden" name="currency_{{ $code }}" value="{{ $currency }}">
                <input type="hidden" name="name_{{ $code }}" value="{{ $name }}">
                <input type="hidden" name="service_{{ $code }}" value="{{ $service }}">
                <input type="hidden" name="carrier_key_{{ $code }}" value="{{ $carrierKey }}">
                <input type="hidden" name="logo_url_{{ $code }}" value="{{ $logoUrl }}">
                <input type="hidden" name="raw_{{ $code }}" value="{{ e($rawJson) }}">
              </label>
            @empty
              <div class="empty-rates">
                No hay paqueterías disponibles para esta dirección. Verifica el código postal, el paquete o que tengas paqueterías activadas en Envia.com.
              </div>
            @endforelse
            </div>
          </div>

          <input type="hidden" name="option_id" id="ship-id" value="{{ $selected['selected_id'] ?? $selected['code'] ?? '' }}">
          <input type="hidden" name="price" id="ship-price" value="{{ $shipPrice }}">
          <input type="hidden" name="currency" id="ship-currency" value="{{ $selected['currency'] ?? 'MXN' }}">
          <input type="hidden" name="carrier" id="ship-carrier" value="{{ $selected['carrier'] ?? '' }}">
          <input type="hidden" name="carrier_key" id="ship-carrier-key" value="{{ $selected['carrier_key'] ?? '' }}">
          <input type="hidden" name="service" id="ship-service" value="{{ $selected['service'] ?? '' }}">
          <input type="hidden" name="logo_url" id="ship-logo-url" value="{{ $selected['logo_url'] ?? '' }}">
          <input type="hidden" name="raw" id="ship-raw" value="">

          <div class="shipping-actions">
            <a class="btn btn-ghost" href="{{ route('web.cart.index') }}">Regresar</a>
            <button type="submit" class="btn btn-primary" id="btn-continue" {{ (($selected['selected_id'] ?? $selected['code'] ?? null)) ? '' : 'disabled' }}>Continuar al Pago</button>
          </div>
        </form>
      </div>
    </div>

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
  const form = $('#ship-form');
  const paymentUrl = @json(route('checkout.payment'));

  function byName(name, code){
    return document.querySelector(`[name="${name}_${CSS.escape(code)}"]`);
  }

  function selectRate(radio){
    const code = radio.value;
    const price = parseFloat(byName('price', code)?.value || '0');
    const currency = byName('currency', code)?.value || 'MXN';
    const carrier = byName('name', code)?.value || '';
    const service = byName('service', code)?.value || '';
    const carrierKey = byName('carrier_key', code)?.value || '';
    const logoUrl = byName('logo_url', code)?.value || '';
    const raw = byName('raw', code)?.value || '';

    $$('.carrier').forEach(c => c.classList.remove('active'));
    radio.closest('.carrier').classList.add('active');

    $('#ship-id').value = code;
    $('#ship-price').value = price;
    $('#ship-currency').value = currency;
    $('#ship-carrier').value = carrier;
    $('#ship-carrier-key').value = carrierKey;
    $('#ship-service').value = service;
    $('#ship-logo-url').value = logoUrl;
    $('#ship-raw').value = raw;

    envioEl.textContent = price > 0 ? '$' + price.toFixed(2) : 'GRATIS';
    totalEl.textContent = '$' + (subtotal + price).toFixed(2);
    btnCont.disabled = false;
  }

  $$('input[name="code"]').forEach(r => {
    r.addEventListener('change', e => selectRate(e.target));
  });

  $('#q')?.addEventListener('input', e => {
    const q = e.target.value.toLowerCase();
    $$('.carrier').forEach(el => {
      const text = el.innerText.toLowerCase();
      el.style.display = text.includes(q) ? 'grid' : 'none';
    });
  });

  $$('.seg button').forEach(btn => {
    btn.addEventListener('click', () => {
      $$('.seg button').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const sort = btn.dataset.sort;
      const wrap = $('#carriers');
      const cards = $$('.carrier');

      cards.sort((a,b) => {
        if(sort === 'price') return parseFloat(a.dataset.price || 0) - parseFloat(b.dataset.price || 0);
        if(sort === 'eta') return String(a.dataset.eta || '').localeCompare(String(b.dataset.eta || ''));
        return 0;
      }).forEach(card => wrap.appendChild(card));
    });
  });

  form?.addEventListener('submit', async function(event){
    event.preventDefault();

    const selected = document.querySelector('input[name="code"]:checked');
    if(!selected){
      alert('Selecciona una opción de envío para continuar.');
      return;
    }

    selectRate(selected);

    btnCont.disabled = true;
    const originalText = btnCont.textContent;
    btnCont.textContent = 'Guardando envío...';

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: new FormData(form)
      });

      const data = await response.json().catch(() => ({}));

      if(!response.ok || data.ok === false){
        alert(data.error || data.message || 'No se pudo guardar la opción de envío.');
        btnCont.disabled = false;
        btnCont.textContent = originalText;
        return;
      }

      window.location.href = paymentUrl;
    } catch (error) {
      /*
       * Si por alguna razón falla el fetch, dejamos que el navegador haga
       * el POST normal. El controlador debe redirigir a checkout.payment.
       */
      form.submit();
    }
  });

  const checked = document.querySelector('input[name="code"]:checked');
  if(checked) selectRate(checked);
})();
</script>
@endpush
@endsection
