@extends('layouts.web')
@section('title','Carrito')

@section('content')
@php
  use Illuminate\Support\Str;

  $FREE_SHIP = (float) env('FREE_SHIPPING_THRESHOLD', 5000);

  $subtotal  = (float) ($totals['subtotal'] ?? 0);
  $count     = (int)   ($totals['count'] ?? 0);

  // ✅ NO IVA extra: total = subtotal
  $total     = $subtotal;

  $faltan    = max(0, $FREE_SHIP - $subtotal);
  $pct       = $FREE_SHIP > 0 ? min(100, (int) round(($subtotal / $FREE_SHIP)*100)) : 100;

  /**
   * ✅ Resolver imagen guardada como:
   *   catalog/photos/xxxx.jpg
   * -> URL pública:
   *   asset('storage/catalog/photos/xxxx.jpg')
   *
   * Esto funciona aunque el proyecto esté en subcarpeta.
   * Requiere: php artisan storage:link
   */
  $resolveImg = function($raw){
    if(!$raw || !is_string($raw) || trim($raw)==='') return asset('images/placeholder.png');

    $raw = trim($raw);

    // URL absoluta
    if (Str::startsWith($raw, ['http://','https://'])) return $raw;

    // Si ya viene como /storage/... o storage/...
    if (Str::startsWith($raw, '/storage/')) return url($raw);
    if (Str::startsWith($raw, 'storage/'))  return asset($raw);

    // Tu caso: catalog/photos/...
    if (Str::startsWith($raw, 'catalog/'))  return asset('storage/' . ltrim($raw, '/'));

    // Fallback: intentar tratarlo como ruta relativa del storage
    return asset('storage/' . ltrim($raw, '/'));
  };
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>

<style>
  /* ====================== SCOPE: #cart ====================== */
  #cart{
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
    --radius: 16px;
    --radius-sm: 8px;
    --shadow: 0 4px 12px rgba(0,0,0,0.03);
    --shadow-hover: 0 10px 25px rgba(0,0,0,0.06);

    font-family: "Quicksand", system-ui, -apple-system, sans-serif;
    color: var(--ink);
    background: var(--bg);
    min-height: calc(100vh - 80px);
    padding: 40px 0 60px 0;
  }

  #cart .wrap{ width:min(1200px,95%); margin:0 auto; position:relative; z-index:1; }

  /* ===== Header ===== */
  #cart .top{
    display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap;
    margin-bottom: 24px;
  }

  #cart h1{ margin:0; font-weight:700; font-size: 28px; color: #111; }
  #cart .sub{ margin-top:6px; color:var(--muted); font-size: 14px; font-weight:500; }

  #cart .pill{
    display:inline-flex; align-items:center; gap:8px;
    padding:8px 16px;
    border-radius:999px;
    background: var(--blue-soft);
    color: var(--blue);
    font-weight:700;
    font-size: 14px;
    white-space:nowrap;
  }
  #cart .icon{ width:18px; height:18px; display:inline-block; vertical-align:middle; }
  #cart .icon-lg{ width:20px; height:20px; }

  /* Layout */
  #cart .grid{ display:grid; gap:24px; grid-template-columns: 1fr 380px; }
  @media (max-width: 980px){
    #cart .grid{ grid-template-columns:1fr; }
  }

  /* Cards */
  #cart .card{
    background: var(--card);
    border:1px solid var(--line);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow:hidden;
  }
  #cart .card-body{ padding:24px; }
  #cart .sticky{ position:sticky; top:24px; }

  /* Buttons */
  #cart .btn{
    appearance:none;
    border:1px solid transparent;
    border-radius: 999px;
    padding: 12px 20px;
    font-weight:700;
    font-size: 14px;
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    text-decoration:none;
    transition: all .2s ease;
    user-select:none;
    width: 100%;
  }
  #cart .btn:focus-visible{ outline:none; box-shadow: 0 0 0 3px var(--blue-soft); }
  #cart .btn:hover{ transform: translateY(-1px); }

  #cart .btn-primary{
    background: var(--blue);
    color:#fff;
    box-shadow: 0 4px 12px rgba(0, 122, 255, 0.2);
  }
  #cart .btn-primary:hover{ background: #0062cc; box-shadow: 0 6px 16px rgba(0, 122, 255, 0.3); }

  #cart .btn-ghost{
    background: #fff;
    border-color: var(--blue);
    color: var(--blue);
  }
  #cart .btn-ghost:hover{ background: var(--blue-soft); }

  #cart .btn-danger{
    background: var(--danger-soft);
    color: var(--danger);
    width: auto;
    padding: 8px 16px;
    font-size: 13px;
  }
  #cart .btn-danger:hover{ background: #ffd6d6; }

  /* Text link help */
  #cart .help-link{
    display:inline-flex;
    align-items:center;
    justify-content: center;
    gap:8px;
    padding:8px;
    font-weight:600;
    font-size: 13px;
    color: var(--muted);
    text-decoration:none;
    width:100%;
    transition: color .2s ease;
    margin-top: 8px;
  }
  #cart .help-link:hover{ color: var(--blue); }

  /* Table */
  #cart .table{ width:100%; border-collapse:collapse; }
  #cart .table th, #cart .table td{
    padding: 16px 10px;
    border-bottom:1px solid var(--line);
    vertical-align:middle;
  }
  #cart .table thead th{
    font-size: 11px;
    color: var(--muted);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    background: #fff;
    border-bottom: 2px solid var(--line);
  }
  #cart .table tbody tr:last-child td { border-bottom: 0; }

  #cart .row{ display:flex; align-items:center; gap:16px; }
  #cart .thumb{
    width:70px; height:70px;
    border-radius: 12px;
    object-fit: contain;
    border:1px solid var(--line);
    background:#fff;
    padding: 4px;
  }
  #cart .prod-name{
    display:inline-block;
    font-weight: 600;
    font-size: 15px;
    color: #111;
    text-decoration:none;
    line-height:1.3;
    transition: color .2s;
  }
  #cart .prod-name:hover{ color: var(--blue); }
  #cart .sku{ color: var(--muted); font-weight:500; margin-top:4px; font-size:12px; }

  /* Qty */
  #cart .qty{
    display:inline-flex; align-items:center;
    border:1px solid var(--line);
    border-radius: var(--radius-sm);
    overflow:hidden;
    background: #fff;
  }
  #cart .qty button{
    border:0; background:transparent;
    padding: 8px 12px;
    min-width:36px;
    font-size:16px;
    font-weight:600;
    cursor:pointer;
    color: var(--muted);
    transition: background .2s, color .2s;
  }
  #cart .qty button:hover{ background: #f1f5f9; color: var(--ink); }
  #cart .qty input{
    width:40px;
    text-align:center;
    border:0;
    border-left:1px solid var(--line);
    border-right:1px solid var(--line);
    height:36px;
    outline:0;
    font-family: inherit;
    font-weight:600;
    color: var(--ink);
    background: transparent;
  }

  #cart .muted{ color: var(--muted); }
  #cart .hr{ border:0; border-top:1px solid var(--line); margin:20px 0; }

  #cart .kv{
    display:flex;
    justify-content: space-between;
    align-items:center;
    font-weight:600;
    font-size: 15px;
    color: #444;
  }
  #cart .kv .k{ color: var(--muted); font-weight:500; }
  #cart .total{
    font-weight: 800;
    font-size: 22px;
    color: #111;
  }

  /* Free shipping inside summary */
  #cart .freebox{
    border:1px solid #cce0ff;
    border-radius: 12px;
    background: var(--blue-soft);
    padding: 16px;
    margin-bottom: 24px;
  }
  #cart .freebox__title{
    font-weight:700;
    color: var(--blue);
    display:flex; align-items:center; gap:8px;
    font-size: 15px;
    margin-bottom: 6px;
  }
  #cart .freebox__text{ color:#555; font-size: 13px; font-weight:500; line-height: 1.4; }
  #cart .freebox__text b { color: #111; font-weight: 700; }
  #cart .progress{
    height:6px;
    background:#fff;
    border-radius: 999px;
    overflow:hidden;
    margin-top: 12px;
  }
  #cart .progress > div{
    height:100%;
    width:0%;
    background: var(--blue);
    border-radius: 999px;
    transition: width .4s ease;
  }
  #cart .badge-ok{
    display:flex; align-items:center; justify-content: center; gap:8px;
    padding:12px;
    border-radius:12px;
    background: var(--success-soft);
    border:1px solid #bbf7d0;
    color:var(--success);
    font-weight:700;
    font-size: 14px;
    margin-bottom: 24px;
  }

  /* Empty */
  #cart .empty{
    padding: 60px 20px;
    text-align: center;
    color: var(--muted);
    display:flex;
    flex-direction: column;
    align-items:center;
    justify-content:center;
    gap:20px;
  }
  #cart .empty h2 { color: #111; font-size: 24px; margin: 0; font-weight: 700; }
  #cart .empty p { margin: 0; font-size: 15px; }

  /* Responsive table -> cards */
  @media (max-width: 760px){
    #cart .table thead{ display:none; }
    #cart .table tr{
      display:flex;
      flex-direction: column;
      gap:12px;
      padding: 16px 0;
      border-bottom:1px solid var(--line);
    }
    #cart .table td{ border:0; padding: 0; text-align: left !important; display: flex; justify-content: space-between; align-items: center; }
    #cart .table td::before { content: attr(data-label); font-size: 12px; color: var(--muted); font-weight: 600; text-transform: uppercase; }
    #cart .table td:first-child::before { display: none; }
    #cart .cell-actions{ justify-content:flex-end !important; margin-top: 8px; }
    #cart .row { width: 100%; }
  }

  /* Anim pulse */
  @keyframes pulseRow{0%{background:transparent}50%{background:var(--blue-soft)}100%{background:transparent}}
  #cart .pulse{ animation:pulseRow .8s ease; }
</style>

<div id="cart">
  <div class="wrap">
    {{-- Header --}}
    <div class="top">
      <div>
        <h1>Tu carrito</h1>
        <div class="sub">Revisa tu compra y continúa al pago.</div>
      </div>

      <div class="pill" id="cartBadgePill" title="Artículos en carrito">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <span>{{ $count }} artículo(s)</span>
      </div>
    </div>

    <div class="grid">
      {{-- Main --}}
      <div class="card col-main">
        @if(count($cart ?? []) === 0)
          <div class="card-body">
            <div class="empty">
              <div>
                <h2>Tu carrito está vacío</h2>
                <p>Explora nuestro catálogo y descubre productos increíbles.</p>
              </div>
              <a class="btn btn-primary" style="width: auto; padding: 14px 32px;" href="{{ route('web.catalog.index') }}">
                Explorar catálogo
              </a>
            </div>
          </div>
        @else
          <div class="card-body" style="padding: 0 24px;">
            <table class="table" id="cartTable">
              <thead>
                <tr>
                  <th>Producto</th>
                  <th style="text-align:right">Precio</th>
                  <th style="text-align:center">Cantidad</th>
                  <th style="text-align:right">Importe</th>
                  <th style="width:1%"></th>
                </tr>
              </thead>
              <tbody id="cartRows">
                @foreach(($cart ?? []) as $row)
                @php $img = $resolveImg($row['image'] ?? null); @endphp
                <tr data-id="{{ $row['id'] }}">
                  <td data-label="Producto">
                    <div class="row">
                      <img class="thumb"
                           src="{{ $img }}"
                           alt="{{ $row['name'] }}"
                           onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
                      <div>
                        <a class="prod-name" href="{{ route('web.catalog.show', $row['slug'] ?? '') }}">{{ $row['name'] }}</a>
                        <div class="sku">SKU: {{ !empty($row['sku']) ? $row['sku'] : '—' }}</div>
                      </div>
                    </div>
                  </td>

                  <td class="cell-price" data-label="Precio" style="text-align:right; font-weight:700; color: #111;">
                    ${{ number_format($row['price'], 2) }}
                  </td>

                  <td class="cell-qty" data-label="Cantidad" style="text-align:center;">
                    <div class="qty">
                      <button type="button" aria-label="Disminuir" onclick="cartMinus({{ $row['id'] }})">−</button>
                      <input type="number" min="1" max="999" value="{{ $row['qty'] }}"
                             onchange="cartSet({{ $row['id'] }}, this.value)">
                      <button type="button" aria-label="Aumentar" onclick="cartPlus({{ $row['id'] }})">+</button>
                    </div>
                  </td>

                  <td class="row-total cell-importe" data-label="Importe" style="text-align:right; font-weight:700; color: var(--blue);">
                    ${{ number_format($row['price'] * $row['qty'], 2) }}
                  </td>

                  <td class="cell-actions" style="text-align:right;">
                    <form method="POST" action="{{ route('web.cart.remove') }}" onsubmit="return confirm('¿Quitar del carrito?')">
                      @csrf
                      <input type="hidden" name="catalog_item_id" value="{{ $row['id'] }}">
                      <button class="btn btn-danger" type="submit" aria-label="Quitar">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                      </button>
                    </form>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="card-body" style="border-top: 1px solid var(--line); display:flex; gap:16px; justify-content:space-between; flex-wrap:wrap; align-items: center; background: #fafafa;">
            <a class="btn btn-ghost" style="width: auto;" href="{{ route('web.catalog.index') }}">
              Seguir comprando
            </a>
            <form method="POST" action="{{ route('web.cart.clear') }}" onsubmit="return confirm('¿Vaciar carrito por completo?')">
              @csrf
              <button class="btn btn-ghost" style="width: auto; border-color: transparent; color: var(--muted);" type="submit">
                Vaciar carrito
              </button>
            </form>
          </div>
        @endif
      </div>

      {{-- Aside --}}
      @if(count($cart ?? []) > 0)
      <aside class="col-aside sticky" aria-label="Resumen de compra">
        <div class="card">
          <div class="card-body">
            <h3 style="margin:0 0 20px 0; font-weight:700; font-size: 18px; color: #111;">Resumen de compra</h3>

            {{-- Barra de envío gratis --}}
            @if($FREE_SHIP > 0)
              <div id="freeboxWrapper">
                @if($faltan > 0)
                  <div class="freebox" id="freebox">
                    <div class="freebox__title">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                      Envío gratis
                    </div>
                    <div class="freebox__text">
                      Te faltan <b id="freeMissing">${{ number_format($faltan,2) }}</b> para aplicar a envío gratis.
                    </div>
                    <div class="progress" aria-label="Progreso hacia envío gratis">
                      <div id="freeProgress" style="width: {{ $pct }}%"></div>
                    </div>
                  </div>
                @else
                  <div class="badge-ok" id="freeboxOk">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    Envío gratis aplicado
                  </div>
                @endif
              </div>
            @endif

            <div class="kv" style="margin-bottom: 12px;">
              <div class="k">Artículos (<span id="sumCount">{{ $count }}</span>)</div>
              <div id="sumSubtotal">${{ number_format($subtotal,2) }}</div>
            </div>

            <div class="kv" style="margin-bottom: 12px;">
              <div class="k">Envío</div>
              <div class="muted" style="font-size: 13px;">Calculado en checkout</div>
            </div>

            <div class="hr"></div>

            <div class="kv">
              <div style="font-weight:700; color: #111;">Total</div>
              <div id="sumTotal" class="total">${{ number_format($total,2) }}</div>
            </div>

            <div style="font-size: 12px; color: var(--muted); text-align: right; margin-top: 4px;">
              IVA incluido
            </div>

            <div style="margin-top:24px;">
              <a class="btn btn-primary" href="{{ route('checkout.start') }}">
                Proceder al pago
              </a>

              <a class="help-link" href="{{ route('web.contacto') }}">
                ¿Necesitas ayuda? Contáctanos
              </a>
            </div>
          </div>
        </div>
      </aside>
      @endif
    </div>
  </div>
</div>

@push('scripts')
<script>
  const FREE_SHIP = {{ json_encode($FREE_SHIP) }};

  async function postJson(url, data) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept':'application/json',
        'Content-Type':'application/json'
      },
      body: JSON.stringify(data)
    });
    return res.json();
  }

  function pulse(el){
    if(!el) return;
    el.classList.remove('pulse');
    void el.offsetWidth;
    el.classList.add('pulse');
  }

  function money(n){
    return Number(n||0).toLocaleString('es-MX',{style:'currency',currency:'MXN'});
  }

  function setFreeBar(subtotal){
    if (!FREE_SHIP || FREE_SHIP <= 0) return;

    const faltan = Math.max(0, FREE_SHIP - subtotal);
    const pct = FREE_SHIP > 0 ? Math.min(100, Math.round((subtotal / FREE_SHIP)*100)) : 100;

    const wrapper = document.getElementById('freeboxWrapper');
    if (!wrapper) return;

    if (faltan > 0) {
      wrapper.innerHTML = `
        <div class="freebox" id="freebox">
          <div class="freebox__title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            Envío gratis
          </div>
          <div class="freebox__text">
            Te faltan <b id="freeMissing">${money(faltan)}</b> para aplicar a envío gratis.
          </div>
          <div class="progress" aria-label="Progreso hacia envío gratis">
            <div id="freeProgress" style="width: ${pct}%"></div>
          </div>
        </div>
      `;
    } else {
      wrapper.innerHTML = `
        <div class="badge-ok" id="freeboxOk">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
          Envío gratis aplicado
        </div>
      `;
    }
  }

  // ✅ total = subtotal (sin IVA extra)
  function updateSummary(totals){
    const count = totals.count ?? 0;
    const subtotal = Number(totals.subtotal ?? 0);
    const total = subtotal;

    document.getElementById('sumCount').textContent = count;
    document.getElementById('sumSubtotal').textContent = money(subtotal);
    document.getElementById('sumTotal').textContent = money(total);

    const pill = document.getElementById('cartBadgePill');
    if (pill) pill.querySelector('span').textContent = `${count} artículo(s)`;

    setFreeBar(subtotal);

    pulse(document.getElementById('sumSubtotal'));
    pulse(document.getElementById('sumTotal'));
  }

  async function cartSet(id, qty){
    qty = Math.max(1, parseInt(qty||1,10));
    const json = await postJson('{{ route('web.cart.update') }}', { catalog_item_id: id, qty });
    if (!json.ok) return alert(json.msg || 'Error al actualizar');

    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (row){
      row.querySelector('input[type="number"]').value = qty;

      const priceText = row.querySelector('.cell-price')?.textContent || '';
      const price = parseFloat(priceText.replace(/[^0-9.]/g, '') || '0');

      const totalCell = row.querySelector('.row-total');
      if (totalCell) totalCell.textContent = money(price * qty);

      row.classList.add('pulse');
      setTimeout(()=>row.classList.remove('pulse'), 650);
    }

    updateSummary(json.totals || {});

    window.dispatchEvent(new CustomEvent('cartUpdated', { detail: { count: json.totals?.count ?? 0 } }));
  }

  function cartPlus(id){
    const input = document.querySelector(`tr[data-id="${id}"] input[type="number"]`);
    if (!input) return;
    cartSet(id, (parseInt(input.value||'1',10)+1));
  }

  function cartMinus(id){
    const input = document.querySelector(`tr[data-id="${id}"] input[type="number"]`);
    if (!input) return;
    cartSet(id, Math.max(1,(parseInt(input.value||'1',10)-1)));
  }

  (function(){
    setFreeBar({{ json_encode($subtotal) }});
  })();
</script>
@endpush
@endsection