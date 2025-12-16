@extends('layouts.web')
@section('title','Carrito')

@section('content')
@php
  $FREE_SHIP = (float) env('FREE_SHIPPING_THRESHOLD', 5000);

  $subtotal  = (float) ($totals['subtotal'] ?? 0);
  $count     = (int)   ($totals['count'] ?? 0);
  $total     = (float) ($totals['total'] ?? 0);

  $faltan    = max(0, $FREE_SHIP - $subtotal);
  $pct       = $FREE_SHIP > 0 ? min(100, (int) round(($subtotal / $FREE_SHIP)*100)) : 100;
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700;800&display=swap" rel="stylesheet"/>

<style>
  /* ====================== SCOPE: #cart ====================== */
  #cart{
    --bg:#f6f8fc;
    --surface:rgba(255,255,255,.72);
    --card:#ffffff;
    --ink:#0e1726;
    --muted:#6b7280;
    --line:#e8eef6;

    --brand:#111827;
    --brand-2:#0f172a;

    --success:#10b981;
    --success-soft:#ecfdf5;

    --danger:#b91c1c;
    --danger-soft:#fff5f5;

    --radius:22px;
    --radius-sm:14px;

    --shadow-soft:0 16px 46px rgba(2,8,23,.08);
    --shadow:0 14px 36px rgba(2,8,23,.08);
    --focus:0 0 0 3px rgba(17,24,39,.14);

    font-family:"Quicksand", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    color:var(--ink);
  }

  #cart .bg{
    position:fixed; inset:0; z-index:0; pointer-events:none;
    background:
      radial-gradient(900px 520px at 50% -220px, #eaf3ff 0%, rgba(234,243,255,0) 60%),
      radial-gradient(900px 520px at 110% 10%, rgba(167,243,208,.35) 0%, rgba(167,243,208,0) 55%),
      linear-gradient(180deg, #f3f7ff 0%, #f7fff5 60%, #ffffff 100%);
    background-attachment:fixed;
  }

  #cart .wrap{ width:min(1200px,95%); margin:0 auto; padding: clamp(14px,2vw,24px); position:relative; z-index:1; }

  /* ===== Header ===== */
  #cart .top{
    position:relative;
    overflow:hidden;
    display:flex; align-items:flex-end; justify-content:space-between; gap:14px; flex-wrap:wrap;
    padding:18px 18px;
    border:1px solid var(--line);
    border-radius: var(--radius);
    background: var(--surface);
    backdrop-filter: blur(10px);
    box-shadow: var(--shadow-soft);
  }
  /* degradado SOLO en esquinas superiores */
  #cart .top::before{
    content:"";
    position:absolute; inset:0;
    pointer-events:none;
    border-radius: inherit;
    background:
      radial-gradient(320px 190px at 0% 0%,
        rgba(147,197,253,.35) 0%,
        rgba(147,197,253,.18) 32%,
        rgba(147,197,253,0) 72%),
      radial-gradient(320px 190px at 100% 0%,
        rgba(134,239,172,.28) 0%,
        rgba(134,239,172,.14) 32%,
        rgba(134,239,172,0) 72%);
    opacity:.9;
  }
  #cart .top > *{ position:relative; z-index:1; }

  #cart h1{ margin:0; font-weight:900; letter-spacing:-.02em; font-size: clamp(26px,3vw,40px); }
  #cart .sub{ margin:6px 0 0; color:var(--muted); font-weight:600; }
  #cart .note{ font-size:.92rem; color: var(--muted); font-weight:700; }

  #cart .pill{
    display:inline-flex; align-items:center; gap:10px;
    padding:10px 14px;
    border-radius:999px;
    border:1px solid var(--line);
    background:rgba(255,255,255,.7);
    box-shadow:0 10px 26px rgba(2,8,23,.06);
    color:var(--muted);
    font-weight:800;
    white-space:nowrap;
  }
  #cart .icon{ width:18px; height:18px; opacity:.9; display:inline-block; vertical-align:middle; }
  #cart .icon-lg{ width:20px; height:20px; opacity:.95; }

  /* Layout */
  #cart .grid{ display:grid; gap:18px; grid-template-columns: repeat(12,1fr); margin-top:16px; }
  #cart .col-main{ grid-column: span 8; }
  #cart .col-aside{ grid-column: span 4; }
  @media (max-width: 980px){
    #cart .grid{ grid-template-columns:1fr; }
    #cart .col-main, #cart .col-aside{ grid-column: 1/-1; }
  }

  /* Cards */
  #cart .card{
    background: rgba(255,255,255,.78);
    border:1px solid var(--line);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow:hidden;
    backdrop-filter: blur(10px);
  }
  #cart .card-body{ padding:18px; }
  #cart .sticky{ position:sticky; top:16px; }

  /* Buttons */
  #cart .btn{
    appearance:none;
    border:1px solid transparent;
    border-radius: 14px;
    padding: 11px 14px;
    font-weight:900;
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    text-decoration:none;
    transition: transform .15s ease, box-shadow .2s ease, background .2s ease, border-color .2s ease, opacity .2s ease;
    user-select:none;
  }
  #cart .btn:focus-visible{ outline:none; box-shadow: var(--focus); }
  #cart .btn:hover{ transform: translateY(-1px); }

  #cart .btn-primary{
    background: linear-gradient(180deg, #111827 0%, #0b1220 100%);
    border-color: rgba(255,255,255,.12);
    color:#fff;
    box-shadow: 0 14px 34px rgba(17,24,39,.22);
  }
  #cart .btn-primary:hover{ box-shadow: 0 16px 38px rgba(17,24,39,.28); }

  #cart .btn-ghost{
    background: rgba(255,255,255,.88);
    border-color: var(--line);
    color: var(--ink);
  }
  #cart .btn-ghost:hover{ background: rgba(255,255,255,.96); }

  #cart .btn-danger{
    background: var(--danger-soft);
    border-color: #fee2e2;
    color: var(--danger);
  }
  #cart .btn-danger:hover{ opacity:.95; }

  /* Text link help */
  #cart .help-link{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:6px 2px;
    font-weight:900;
    color: var(--muted);
    text-decoration:none;
    width:fit-content;
    transition: color .15s ease, transform .15s ease;
  }
  #cart .help-link:hover{ color: var(--ink); transform: translateY(-1px); }
  #cart .help-link .icon{ opacity:.85; }

  /* Table */
  #cart .table{ width:100%; border-collapse:collapse; }
  #cart .table th, #cart .table td{
    padding: 16px;
    border-bottom:1px solid var(--line);
    vertical-align:middle;
  }
  #cart .table thead th{
    font-size:.82rem;
    color: var(--muted);
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .05em;
    background: rgba(255,255,255,.55);
  }
  #cart .row{ display:flex; align-items:center; gap:14px; }
  #cart .thumb{
    width:86px; height:86px;
    border-radius: 18px;
    object-fit: cover;
    border:1px solid var(--line);
    background:#f1f5fb;
  }
  #cart .prod-name{
    display:inline-block;
    font-weight: 900;
    color: var(--ink);
    text-decoration:none;
    line-height:1.2;
  }
  #cart .prod-name:hover{ opacity:.9; }
  #cart .sku{ color: var(--muted); font-weight:700; margin-top:6px; font-size:.9rem; }

  /* Qty */
  #cart .qty{
    display:inline-flex; align-items:center;
    border:1px solid var(--line);
    border-radius: 14px;
    overflow:hidden;
    background: rgba(255,255,255,.92);
    box-shadow: 0 10px 26px rgba(2,8,23,.06);
  }
  #cart .qty button{
    border:0; background:transparent;
    padding: 10px 12px;
    min-width:40px;
    font-size:18px;
    font-weight:900;
    cursor:pointer;
    color: var(--ink);
  }
  #cart .qty button:hover{ background: rgba(2,8,23,.04); }
  #cart .qty input{
    width:60px;
    text-align:center;
    border:0;
    border-left:1px solid var(--line);
    border-right:1px solid var(--line);
    height:40px;
    outline:0;
    font-weight:900;
    background: transparent;
  }

  #cart .muted{ color: var(--muted); }
  #cart .hr{ border:0; border-top:1px solid var(--line); margin:14px 0; }

  #cart .kv{
    display:grid;
    grid-template-columns: 1fr auto;
    gap:10px;
    align-items:center;
    font-weight:800;
  }
  #cart .kv .k{ color: var(--muted); font-weight:900; }
  #cart .total{
    font-weight: 1000;
    font-size: 1.25rem;
    letter-spacing: -.01em;
  }

  /* Free shipping inside summary */
  #cart .freebox{
    border:1px solid #e9eefc;
    border-radius: 16px;
    background: rgba(255,255,255,.7);
    box-shadow: 0 12px 28px rgba(2,8,23,.06);
    overflow:hidden;
    margin: 12px 0;
  }
  #cart .freebox__inner{
    padding: 12px 12px 10px;
    display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap;
  }
  #cart .freebox__title{
    font-weight:1000;
    display:flex; align-items:center; gap:10px;
    letter-spacing:-.01em;
  }
  #cart .spark{
    width:26px; height:26px; border-radius:10px;
    display:grid; place-items:center;
    background:#eef6ff;
    border:1px solid #dbeafe;
    font-size:14px;
  }
  #cart .freebox__text{ color:var(--muted); font-weight:800; margin-top:2px; }
  #cart .progress{
    height:10px;
    background:#eef2ff;
    border-top:1px solid #edf2ff;
    overflow:hidden;
  }
  #cart .progress > div{
    height:100%;
    width:0%;
    background: linear-gradient(90deg, rgba(147,197,253,1), rgba(134,239,172,1));
    transition: width .35s ease;
  }
  #cart .badge-ok{
    display:inline-flex; align-items:center; gap:8px;
    padding:8px 12px;
    border-radius:999px;
    background: var(--success-soft);
    border:1px solid #bbf7d0;
    color:#065f46;
    font-weight:1000;
    white-space:nowrap;
  }

  /* Empty */
  #cart .empty{
    padding: 22px;
    border:1px dashed var(--line);
    border-radius: var(--radius);
    background: rgba(255,255,255,.65);
    color: var(--muted);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    flex-wrap:wrap;
  }

  /* Responsive table -> cards */
  @media (max-width: 760px){
    #cart .table thead{ display:none; }
    #cart .table tr{
      display:grid;
      grid-template-columns: 1fr auto;
      gap:10px;
      padding: 12px;
      border-bottom:1px solid var(--line);
    }
    #cart .table td{ border:0; padding: 6px 0; }
    #cart .cell-actions{ grid-column: 1/-1; display:flex; justify-content:flex-end; gap:10px; }
    #cart .thumb{ width:70px; height:70px; border-radius:16px; }
  }

  /* Anim pulse */
  @keyframes pulseRow{0%{background:transparent}50%{background:rgba(59,130,246,.06)}100%{background:transparent}}
  #cart .pulse{ animation:pulseRow .8s ease; }
</style>

<div id="cart">
  <div class="bg" aria-hidden="true"></div>

  <div class="wrap">
    {{-- Header --}}
    <div class="top">
      <div>
        <h1>Tu carrito</h1>
        <div class="sub">
          Revisa tu compra y continúa al pago.
          <div class="note">Los precios ya incluyen IVA (16%).</div>
        </div>
      </div>

      <div class="pill" id="cartBadgePill" title="Artículos en carrito">
        {{-- cart icon --}}
        <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M6.5 6h14l-1.5 8h-11L6.5 6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
          <path d="M6.5 6 5.8 3.8H3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          <path d="M9 21a1.2 1.2 0 1 0 0-2.4A1.2 1.2 0 0 0 9 21Zm9 0a1.2 1.2 0 1 0 0-2.4A1.2 1.2 0 0 0 18 21Z" fill="currentColor"/>
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
                <div style="font-weight:1000;color:var(--ink);font-size:1.05rem;">Tu carrito está vacío</div>
                <div class="note" style="margin-top:6px;">Explora el catálogo y agrega productos.</div>
              </div>
              <a class="btn btn-primary" href="{{ route('web.catalog.index') }}">
                <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                </svg>
                Explorar catálogo
              </a>
            </div>
          </div>
        @else
          <div class="card-body" style="padding:0">
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
                <tr data-id="{{ $row['id'] }}">
                  <td>
                    <div class="row">
                      <img class="thumb"
                           src="{{ $row['image'] ?: asset('images/placeholder.png') }}"
                           alt="{{ $row['name'] }}"
                           onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
                      <div>
                        <a class="prod-name" href="{{ route('web.catalog.show', $row['slug']) }}">{{ $row['name'] }}</a>
                        <div class="sku">SKU: {{ $row['sku'] ?: '—' }}</div>
                      </div>
                    </div>
                  </td>

                  <td class="cell-price" style="text-align:right; font-weight:900;">
                    ${{ number_format($row['price'],2) }}
                  </td>

                  <td class="cell-qty" style="text-align:center;">
                    <div class="qty">
                      <button type="button" aria-label="Disminuir" onclick="cartMinus({{ $row['id'] }})">−</button>
                      <input type="number" min="1" max="999" value="{{ $row['qty'] }}"
                             onchange="cartSet({{ $row['id'] }}, this.value)">
                      <button type="button" aria-label="Aumentar" onclick="cartPlus({{ $row['id'] }})">+</button>
                    </div>
                  </td>

                  <td class="row-total cell-importe" style="text-align:right; font-weight:1000;">
                    ${{ number_format($row['price'] * $row['qty'], 2) }}
                  </td>

                  <td class="cell-actions" style="text-align:right;">
                    <form method="POST" action="{{ route('web.cart.remove') }}" onsubmit="return confirm('¿Quitar del carrito?')">
                      @csrf
                      <input type="hidden" name="catalog_item_id" value="{{ $row['id'] }}">
                      <button class="btn btn-danger" type="submit">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                          <path d="M9 3h6m-8 4h10m-9 0 1 14h6l1-14" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                        </svg>
                        Quitar
                      </button>
                    </form>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>

            <div class="card-body" style="display:flex; gap:10px; justify-content:space-between; flex-wrap:wrap;">
              <form method="POST" action="{{ route('web.cart.clear') }}" onsubmit="return confirm('¿Vaciar carrito por completo?')">
                @csrf
                <button class="btn btn-danger" type="submit">
                  <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 7h16M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                    <path d="M7 7l1 14h8l1-14" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                  </svg>
                  Vaciar carrito
                </button>
              </form>
              <a class="btn btn-ghost" href="{{ route('web.catalog.index') }}">
                <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Seguir comprando
              </a>
            </div>
          </div>
        @endif
      </div>

      {{-- Aside --}}
      <aside class="card col-aside sticky" aria-label="Resumen de compra">
        <div class="card-body">
          <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
            <h3 style="margin:0; font-weight:1000; letter-spacing:-.01em;">Resumen</h3>
            <span class="pill" style="padding:8px 12px;">MXN</span>
          </div>

          {{-- ✅ Barra de envío gratis AHORA dentro del resumen --}}
          @if($FREE_SHIP > 0)
            <div class="freebox" id="freebox">
              <div class="freebox__inner">
                @if($faltan > 0)
                  <div>
                    <div class="freebox__title">
                      <span class="spark">✨</span>
                      <span>Envío gratis</span>
                    </div>
                    <div class="freebox__text">
                      Te faltan <b id="freeMissing">${{ number_format($faltan,2) }}</b> para llegar a
                      <b>${{ number_format($FREE_SHIP,2) }}</b>.
                    </div>
                  </div>
                  <div class="pill" style="padding:8px 12px; font-weight:1000;">
                    <span id="freePct">{{ $pct }}%</span>
                  </div>
                @else
                  <div class="badge-ok">✅ Envío gratis aplicado</div>
                @endif
              </div>
              <div class="progress" aria-label="Progreso hacia envío gratis" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}">
                <div id="freeProgress" style="width: {{ $pct }}%"></div>
              </div>
            </div>
          @endif

          <div class="kv">
            <div class="k">Artículos</div>
            <div id="sumCount">{{ $count }}</div>
          </div>

          <div class="kv" style="margin-top:10px;">
            <div class="k">Subtotal</div>
            <div id="sumSubtotal">${{ number_format($subtotal,2) }}</div>
          </div>

          <div class="kv" style="margin-top:10px;">
            <div class="k">Envío</div>
            <div class="muted" style="font-weight:900;">Se calcula en checkout</div>
          </div>

          <div class="hr"></div>

          <div class="kv">
            <div style="font-weight:1000;">Total</div>
            <div id="sumTotal" class="total">${{ number_format($total,2) }}</div>
          </div>

          <div class="note" style="margin-top:8px;">
            * El total final puede variar según el envío en el checkout.
          </div>

          <div style="display:flex; flex-direction:column; gap:10px; margin-top:14px;">
            <a class="btn btn-primary" href="{{ route('checkout.start') }}">
              <svg class="icon icon-lg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M8 12h8m-8 4h5M9 3h6l3 3v15a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              Proceder al pago
            </a>

            <a class="btn btn-ghost" href="{{ route('web.catalog.index') }}">
              <svg class="icon icon-lg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              Seguir comprando
            </a>

            {{-- ✅ Link sin fondo, debajo de seguir comprando --}}
            <a class="help-link" href="{{ route('web.contacto') }}">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 18h.01M9.5 9.5a2.5 2.5 0 1 1 4.1 2c-.8.6-1.6 1.1-1.6 2.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z" stroke="currentColor" stroke-width="1.8"/>
              </svg>
              ¿Necesitas ayuda? Contáctanos
            </a>
          </div>
        </div>
      </aside>
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

    const missingEl = document.getElementById('freeMissing');
    const pctEl = document.getElementById('freePct');
    const bar = document.getElementById('freeProgress');

    if (missingEl) missingEl.textContent = money(faltan);
    if (pctEl) pctEl.textContent = pct + '%';
    if (bar) bar.style.width = pct + '%';
  }

  function updateSummary(totals){
    const count = totals.count ?? 0;
    const subtotal = totals.subtotal ?? 0;
    const total = totals.total ?? 0;

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
      const price = parseFloat((row.querySelector('.cell-price')?.textContent || '').replace(/[^0-9.]/g,'') || '0');
      const totalCell = row.querySelector('.row-total');
      if (totalCell) totalCell.textContent = money(price * qty);

      row.classList.add('pulse');
      setTimeout(()=>row.classList.remove('pulse'), 650);
    }

    updateSummary(json.totals || {});
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

  // Init
  (function(){
    setFreeBar({{ json_encode($subtotal) }});
  })();
</script>
@endpush
@endsection
