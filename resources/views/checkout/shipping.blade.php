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

  // Mapea nombre de paquetería -> logo (coloca tus SVG/PNG en /public/images/carriers)
  $logoMap = [
    'dhl'            => 'dhl.svg',
    'fedex'          => 'fedex.svg',
    'ups'            => 'ups.svg',
    'estafeta'       => 'estafeta.svg',
    'redpack'        => 'redpack.svg',
    '99minutos'      => '99minutos.svg',
    'paquetexpress'  => 'paquetexpress.svg',
    'sendex'         => 'sendex.svg',
    'mexpost'        => 'mexpost.svg',
    'carssa'         => 'carssa.svg',
    'ivoy'           => 'ivoy.svg',
    'dypaq'          => 'dypaq.svg',
  ];

  // Encuentra el más barato para la cinta “Mejor precio”
  $minPrice = null; $minIdx = -1;
  foreach(($carriers ?? []) as $i=>$c){
    $p = (float)($c['price'] ?? INF);
    if(!is_finite($p)) continue;
    if($minPrice === null || $p < $minPrice){ $minPrice = $p; $minIdx = $i; }
  }

  $threshold = (float) env('FREE_SHIPPING_THRESHOLD', 5000);
@endphp

<style>
  :root{
    --ink:#0e1726; --muted:#6b7280; --line:#e8eef6;
    --surface:#ffffff; --brand:#1f4cf0; --brand-ink:#0b1220;
    --ok:#16a34a; --chip:#f7fbff; --accent:#a3d5ff;
  }
  .ck-wrap{display:grid;grid-template-columns:2fr 1fr;gap:18px}
  @media(max-width:980px){ .ck-wrap{grid-template-columns:1fr} }

  .card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 8px 24px rgba(2,8,23,.04)}
  .card-h{padding:16px 18px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:12px}
  .card-b{padding:16px 18px}
  .muted{color:var(--muted)}
  .btn{display:inline-flex;align-items:center;justify-content:center;border-radius:12px;padding:10px 14px;font-weight:800;text-decoration:none;border:1px solid #dbe2ea;background:#fff;cursor:pointer}
  .btn-primary{background:var(--brand);border-color:var(--brand);color:#fff}
  .btn-primary:disabled{opacity:.5;cursor:not-allowed}
  .btn-ghost{background:#fff}

  .stepper{display:flex;gap:22px;align-items:center;margin:0 0 18px}
  .step{display:flex;align-items:center;gap:10px;font-weight:800;color:#334155}
  .dot{width:28px;height:28px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;border:2px solid var(--brand)}
  .dot.active{background:var(--brand);color:#fff}

  /* Filtros */
  .filters{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:12px}
  .seg{display:inline-flex;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden}
  .seg button{padding:8px 12px;border:0;background:#fff;cursor:pointer;font-weight:800;color:#0f172a}
  .seg button.active{background:#f4f7ff;color:#1b2a5a}
  .search{flex:1;min-width:220px;display:flex;align-items:center;gap:8px;border:1px solid #e5e7eb;border-radius:12px;padding:8px 12px}
  .search input{border:0;outline:none;width:100%}
  .chip{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;background:var(--chip);color:#22396b;border:1px solid #d9e3ff;font-weight:800}

  /* Lista de opciones */
  .carrier{display:grid;grid-template-columns:auto 1fr auto;gap:14px;align-items:center;border:1px solid #e5e7eb;border-radius:14px;padding:12px;cursor:pointer;transition:.2s ease;position:relative}
  .carrier:hover{background:#f8fbff;border-color:#c4d1ff}
  .carrier.active{border-color:#bfd2ff;box-shadow:0 12px 28px rgba(31,76,240,.07)}
  .carrier input{margin:0}
  .carrier-logo{width:68px;height:38px;display:flex;align-items:center;justify-content:center;border:1px solid #eef2f7;border-radius:10px;background:#fff}
  .carrier-logo img{max-width:90%;max-height:28px;object-fit:contain;image-rendering:auto}
  .car-info{display:grid;gap:4px}
  .car-title{font-weight:900;color:var(--ink)}
  .car-sub{font-size:.92rem;color:var(--muted)}
  .car-badges{display:flex;flex-wrap:wrap;gap:8px;margin-top:4px}
  .car-price{font-weight:900;font-size:1.02rem}
  .ribbon{position:absolute;left:10px;top:-8px;background:#10b981;color:#fff;padding:4px 8px;font-size:.74rem;border-radius:999px;box-shadow:0 6px 18px rgba(16,185,129,.25)}
  .ribbon.fast{background:#06b6d4}

  .sum-row{display:flex;justify-content:space-between;margin:8px 0;font-weight:800}
  .line{border:0;border-top:1px solid var(--line);margin:16px 0}

  /* “Ver más” */
  .more-wrap{position:relative}
  .more-fader{content:"";position:absolute;left:0;right:0;bottom:0;height:56px;background:linear-gradient(180deg, rgba(255,255,255,0), #fff)}
  .more-actions{display:flex;justify-content:center;margin-top:8px}
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
      <a href="{{ route('checkout.start') }}" class="btn btn-ghost">Cambiar dirección</a>
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
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M3 7h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z" stroke="#1f4cf0" stroke-width="2"/><path d="M3 7l3-4h12l3 4" stroke="#1f4cf0" stroke-width="2"/></svg>
            <div>
              <strong>Entregar en:</strong>
              <div class="muted" style="margin-top:4px;white-space:pre-line">{{ implode("\n", array_filter($addrLines)) }}</div>
            </div>
          </div>
        </div>
      @endif

      {{-- Barra superior de filtros --}}
      <div class="filters">
        <div class="seg" role="tablist" aria-label="Ordenar por">
          <button type="button" class="active" data-sort="recommended" aria-selected="true">Recomendado</button>
          <button type="button" data-sort="price">Más barato</button>
          <button type="button" data-sort="eta">Más rápido</button>
        </div>

        <div class="search" role="search">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
            <path d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 110-15 7.5 7.5 0 010 15z" stroke="#64748b" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <input id="q" type="search" placeholder="Buscar: DHL, Express, 24h...">
        </div>

        @if($threshold > 0)
          <span class="chip" title="Compras desde {{ number_format($threshold,0) }} MXN aplican envío cubierto por la tienda">
            Envío gratis desde ${{ number_format($threshold,0) }}
          </span>
        @endif
      </div>

      {{-- Lista de carriers --}}
      <form id="ship-form" method="post" action="{{ route('checkout.shipping.select') }}">
        @csrf

        @if(empty($carriers))
          <div class="card" style="border:1px dashed #e5e7eb;background:#f8fafc">
            <div class="card-b" style="display:flex;gap:12px;align-items:flex-start">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <path d="M3 12h13l4 4V8l-4 4H3z" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <div>
                <strong>No hay opciones disponibles en este momento.</strong>
                <div class="muted" style="margin-top:4px">Verifica tu dirección o vuelve a cotizar.</div>
                <div style="margin-top:10px">
                  <a href="{{ route('checkout.start') }}" class="btn">Cambiar dirección</a>
                </div>
              </div>
            </div>
          </div>
        @else
          <div id="carriers" class="more-wrap" data-collapsed="true" data-visible="6" style="display:grid;gap:10px">
            @foreach($carriers as $i => $c)
              @php
                $name    = trim($c['name'] ?? ($c['carrier'] ?? 'Paquetería'));
                $service = trim($c['service'] ?? '');
                $eta     = trim($c['eta'] ?? '');
                $price   = (float)($c['price'] ?? 0);
                $code    = (string)($c['code'] ?? Str::slug($name.'-'.$service));
                $slug    = Str::slug($c['carrier'] ?? $name); // para el logo
                $logo    = $logoMap[$slug] ?? 'generic-shipping.svg';
                $checked = (($selected['code'] ?? null) === $code);
                $isCheapest = ($i === $minIdx);
              @endphp
              <label class="carrier {{ $checked ? 'active' : '' }}"
                     data-price="{{ $price }}"
                     data-eta-text="{{ $eta }}"
                     data-name="{{ Str::upper($name) }}"
                     data-service="{{ $service }}"
                     data-carrier="{{ $slug }}"
                     @if($i >= 6) style="display:none" data-extra="true" @endif
              >
                @if($isCheapest)
                  <span class="ribbon">Mejor precio</span>
                @endif

                <div class="carrier-logo" aria-hidden="true">
                  <img src="{{ asset('images/carriers/'.$logo) }}" alt="{{ $name }} logo"
                       onerror="this.src='{{ asset('images/carriers/generic-shipping.svg') }}'">
                </div>

                <div class="car-info">
                  <div class="car-title">
                    {{ Str::upper($name) }}
                    {!! $service ? ' · <span class="car-sub" style="font-weight:700;color:#334155">'.$service.'</span>' : '' !!}
                  </div>
                  <div class="car-sub">{{ $eta ?: 'Tiempo estimado al pagar' }}</div>
                  <div class="car-badges">
                    @if(Str::contains(Str::lower($service.' '.$eta), ['express','día siguiente','24','next']))
                      <span class="chip">Rápido</span>
                    @endif
                    @if(Str::contains(Str::lower($service.' '.$name), ['económico','economy','ground']))
                      <span class="chip">Económico</span>
                    @endif
                  </div>

                  {{-- Campos ocultos por opción (para JS) --}}
                  <input type="hidden" name="price_{{ $code }}"   value="{{ $price }}">
                  <input type="hidden" name="name_{{ $code }}"    value="{{ $name }}">
                  <input type="hidden" name="service_{{ $code }}" value="{{ $service }}">
                  <input type="hidden" name="eta_{{ $code }}"     value="{{ $eta }}">
                </div>

                <div style="display:grid;gap:8px;justify-items:end">
                  <div class="car-price">{{ '$'.number_format($price,2) }}</div>
                  <input type="radio" name="code" value="{{ $code }}" {{ $checked ? 'checked' : '' }} aria-label="Elegir {{ $name }} {{ $service ? ' - '.$service : '' }}">
                </div>
              </label>
            @endforeach

            @if(count($carriers) > 6)
              <div class="more-fader" id="more-fader"></div>
              <div class="more-actions">
                <button type="button" class="btn" id="btn-more">Ver {{ count($carriers)-6 }} más</button>
              </div>
            @endif
          </div>
        @endif

        {{-- Hidden definitivos que se envían --}}
        <input type="hidden" name="price"   id="ship-price"   value="{{ $shipPrice }}">
        <input type="hidden" name="name"    id="ship-name"    value="{{ $selected['name'] ?? '' }}">
        <input type="hidden" name="service" id="ship-service" value="{{ $selected['service'] ?? '' }}">
        <input type="hidden" name="eta"     id="ship-eta"     value="{{ $selected['eta']  ?? '' }}">

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:12px">
          <a class="btn" href="{{ route('web.cart.index') }}">Regresar al carrito</a>
          <button class="btn btn-primary" id="btn-continue" {{ $selected['code'] ? '' : 'disabled' }}>Continuar a pago</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Sidebar --}}
  <aside class="card" aria-label="Resumen">
    <div class="card-b">
      <h3 style="margin:0 0 8px;font-weight:900">Resumen</h3>
      <div class="sum-row"><span>Subtotal</span><span id="sum-subtotal">${{ number_format($subtotal,2) }}</span></div>
      <div class="sum-row"><span>Envío</span><span id="sum-envio">{{ $shipPrice>0 ? '$'.number_format($shipPrice,2) : ($shipPrice===0.0 ? 'GRATIS' : '—') }}</span></div>
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
  const $  = s => document.querySelector(s);
  const $$ = s => Array.from(document.querySelectorAll(s));

  const subtotalEl = $('#sum-subtotal');
  const envioEl    = $('#sum-envio');
  const totalEl    = $('#sum-total');
  const btn        = $('#btn-continue');
  const listEl     = $('#carriers');
  const searchEl   = $('#q');

  // Utilidad moneda
  function fmt(n){ try { return new Intl.NumberFormat('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2}).format(n); } catch(e){ return (Math.round(n*100)/100).toFixed(2); } }
  function getSubtotal(){ return parseFloat((subtotalEl.textContent||'').replace(/[^\d.]/g,'')) || 0; }

  // Marcar visual activo
  function activateCard(card){
    $$('.carrier').forEach(c=>c.classList.remove('active'));
    if(card) card.classList.add('active');
  }

  // Parse ETA (busca el menor número en el texto)
  function etaDaysFromText(t){
    const m = String(t||'').match(/(\d+)(?=\s*(?:día|dias|días|day|days))/i);
    return m ? parseInt(m[1],10) : 999;
  }

  // Ordenar por
  const sortBtns = $$('.seg button');
  sortBtns.forEach(b=>{
    b.addEventListener('click', ()=>{
      sortBtns.forEach(x=>x.classList.remove('active'));
      b.classList.add('active');
      const mode = b.dataset.sort;
      const list = listEl;
      const items = $$('.carrier').filter(el=>el.style.display !== 'none' || !el.dataset.extra); // mantener visibles los colapsados en DOM

      const arr = items.map(el=>{
        const price = parseFloat(el.dataset.price || '0');
        const etaT  = el.dataset.etaText || '';
        const etaD  = etaDaysFromText(etaT);
        const free  = price <= 0 ? 0 : 1;
        return { el, price, etaD, free };
      });

      if(mode==='price') arr.sort((a,b)=> a.price - b.price);
      else if(mode==='eta') arr.sort((a,b)=> a.etaD - b.etaD);
      else {
        // recomendado: barato y rápido (ponderación simple)
        arr.sort((a,b)=> (a.free - b.free) || (a.price - b.price) || (a.etaD - b.etaD));
      }

      arr.forEach(x=>list.appendChild(x.el));
    });
  });

  // Búsqueda por nombre/servicio/eta
  if (searchEl){
    searchEl.addEventListener('input', ()=>{
      const q = searchEl.value.trim().toLowerCase();
      const items = $$('.carrier');
      const isCollapsed = (listEl?.dataset.collapsed === 'true');
      let shown = 0;
      items.forEach(el=>{
        const hay = (el.dataset.name+' '+el.dataset.service+' '+el.dataset.etaText+' '+el.dataset.carrier).toLowerCase();
        const match = q==='' ? true : hay.includes(q);
        if(match){
          // respeta colapso (solo 6 visibles si no hay query)
          if (q==='' && isCollapsed && el.dataset.extra === 'true'){
            el.style.display = 'none';
          } else {
            el.style.display = '';
            shown++;
          }
        }else{
          el.style.display = 'none';
        }
      });
      // actualizar botón "ver más"
      const moreBtn = $('#btn-more'), fader = $('#more-fader');
      if (moreBtn){
        if (q!=='' || shown <= 6){ moreBtn.style.display='none'; fader && (fader.style.display='none'); }
        else { moreBtn.style.display='inline-flex'; fader && (fader.style.display='block'); }
      }
    });
  }

  // Radio change
  $$('#carriers input[type="radio"][name="code"]').forEach(radio=>{
    radio.addEventListener('change', (e)=>{
      const code = e.target.value;
      const card = e.target.closest('.carrier');
      activateCard(card);

      const price = parseFloat(document.querySelector(`[name="price_${code}"]`)?.value || '0');
      const name  = document.querySelector(`[name="name_${code}"]`)?.value || '';
      const svc   = document.querySelector(`[name="service_${code}"]`)?.value || '';
      const eta   = document.querySelector(`[name="eta_${code}"]`)?.value || '';

      // Hidden definitivos
      $('#ship-price').value   = String(price);
      $('#ship-name').value    = name;
      $('#ship-service').value = svc;
      $('#ship-eta').value     = eta;

      // Totales
      const subtotal = getSubtotal();
      envioEl.textContent = (price>0) ? '$'+fmt(price) : 'GRATIS';
      totalEl.textContent = '$' + fmt(subtotal + price);

      // Habilitar continuar
      btn.disabled = false;
    });
  });

  // “Ver más”
  const moreBtn = $('#btn-more');
  if (moreBtn){
    moreBtn.addEventListener('click', ()=>{
      const extras = $$('#carriers [data-extra="true"]');
      extras.forEach(el=> el.style.display = '');
      const fader = $('#more-fader');
      if (fader) fader.style.display = 'none';
      listEl.dataset.collapsed = 'false';
      moreBtn.style.display = 'none';
    });
  }

  // Estado inicial (si viene preseleccionado desde sesión)
  (function init(){
    const checked = document.querySelector('input[name="code"]:checked');
    if(checked){
      checked.dispatchEvent(new Event('change', {bubbles:true}));
    }
  })();

  // UX al enviar
  $('#ship-form')?.addEventListener('submit', ()=>{
    btn.disabled = true;
    btn.textContent = 'Guardando...';
  });
})();
</script>
@endpush
@endsection
