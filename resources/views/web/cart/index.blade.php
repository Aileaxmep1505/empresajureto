@extends('layouts.web')
@section('title','Carrito')

@section('content')
@php
  $FREE_SHIP = (float) env('FREE_SHIPPING_THRESHOLD', 5000);
  $subtotal  = (float) $totals['subtotal'];
  $faltan    = max(0, $FREE_SHIP - $subtotal);
  $pct       = $FREE_SHIP > 0 ? min(100, round(($subtotal / $FREE_SHIP)*100)) : 100;
@endphp

<style>
  :root{
    --bg:#f6f8fc; --ink:#0e1726; --muted:#6b7280; --line:#e8eef6; --surface:#ffffff;
    --brand:#6ea8fe; --brand-ink:#0b1220; --success:#10b981; --warn:#f59e0b; --danger:#ef4444;
    --shadow:0 12px 30px rgba(13,23,38,.06); --radius:16px; --radius-sm:12px;
    --focus:0 0 0 3px rgba(110,168,254,.35);
  }
  html,body{background:var(--bg);color:var(--ink)}
  .wrap{width:min(1200px,95%);margin-inline:auto;padding:clamp(14px,2vw,24px)}
  .grid{display:grid;gap:18px;grid-template-columns:repeat(12,1fr)}
  .col-main{grid-column:span 8} .col-aside{grid-column:span 4}
  @media (max-width: 980px){.grid{grid-template-columns:1fr}.col-main,.col-aside{grid-column:1/-1}}

  .card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
  .card-body{padding:16px}
  .sticky{position:sticky;top:16px}

  .btn{border:0;border-radius:12px;padding:10px 14px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:10px;text-decoration:none;transition:transform .15s ease, box-shadow .2s ease, background .2s ease}
  .btn:focus-visible{outline:none;box-shadow:var(--focus)}
  .btn-primary{background:var(--brand);color:var(--brand-ink);box-shadow:0 8px 18px rgba(29,78,216,.12)}
  .btn-primary:hover{transform:translateY(-1px)}
  .btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink)}
  .btn-ghost:hover{background:#f9fafb}
  .btn-danger{background:#fff;border:1px solid #fee2e2;color:#b91c1c}
  .btn-danger:hover{background:#fff5f5}

  .input{border:1px solid var(--line);border-radius:12px;padding:10px 12px;outline:0;background:#fff;transition:box-shadow .15s ease}
  .input:focus{box-shadow:var(--focus)}

  .table{width:100%;border-collapse:collapse}
  .table th,.table td{padding:14px;border-bottom:1px solid var(--line);vertical-align:middle}
  .table th{font-size:.9rem;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.02em}
  .row{display:flex;align-items:center;gap:12px}
  .thumb{width:84px;height:84px;object-fit:cover;border-radius:14px;border:1px solid var(--line);background:#f1f5fb}
  .prod-name{font-weight:800;color:var(--ink);text-decoration:none}
  .prod-name:hover{opacity:.9}
  @media (max-width: 720px){
    .table thead{display:none}
    .table tr{display:grid;grid-template-columns:1fr auto;gap:10px;padding:10px 12px}
    .table td{border:0;padding:6px 0}
    .cell-actions{grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end}
    .row{align-items:flex-start}
    .thumb{width:68px;height:68px}
    .cell-price,.cell-importe{text-align:right}
    .cell-qty{justify-self:end}
  }

  .qty{display:inline-flex;align-items:center;border:1px solid var(--line);border-radius:12px;overflow:hidden;background:#fff}
  .qty button{border:0;background:#fff;padding:8px 12px;min-width:36px;font-size:18px;line-height:1}
  .qty input{width:56px;text-align:center;border:0;border-left:1px solid var(--line);border-right:1px solid var(--line);height:38px;outline:0;font-weight:700}

  .muted{color:var(--muted)}
  .hr{border:none;border-top:1px solid var(--line);margin:12px 0}
  .kv{display:grid;grid-template-columns:1fr auto;gap:8px;align-items:center}
  .total{font-weight:900;font-size:1.2rem}
  .badge-free{padding:4px 8px;border-radius:999px;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;font-size:.78rem;font-weight:700}
  .ship-list{display:grid;gap:8px}
  .ship-item{display:flex;align-items:center;gap:10px;padding:10px;border:1px solid var(--line);border-radius:12px;transition:background .2s ease}
  .ship-item:hover{background:#fafcff}

  /* Banner motivacional mejorado (dentro del resumen) */
  .motivation{
    border:1px solid #e9eefc;border-radius:14px;padding:12px;background:linear-gradient(135deg,#f4f8ff 0%,#f7fffb 100%);
    box-shadow: inset 0 0 0 1px #f2f6ff;
  }
  .motivation .headline{display:flex;align-items:center;gap:10px;font-weight:900}
  .motivation .headline .spark{width:24px;height:24px;border-radius:8px;background:#eef6ff;border:1px solid #dbeafe;display:inline-flex;align-items:center;justify-content:center;font-size:14px}
  .progress{height:10px;background:#eef2ff;border-radius:999px;overflow:hidden;border:1px solid #e5e7eb}
  .progress>div{height:100%;width:0%;background:linear-gradient(90deg,#93c5fd,#86efac);transition:width .35s ease}
  .mini-cta{display:flex;gap:8px;flex-wrap:wrap}
  .mini-link{color:#1d4ed8;text-decoration:none;font-weight:800}
  .mini-link:hover{text-decoration:underline}

  @keyframes pulseRow{0%{background:transparent}50%{background:#f7fbff}100%{background:transparent}}
  .pulse{animation:pulseRow .8s ease}

  .note{font-size:.85rem;color:var(--muted)}
</style>

<div class="wrap">
  <div style="display:flex;align-items:center;gap:12px;margin:6px 0 6px;">
    <h1 style="font-weight:800;margin:0;font-size:clamp(24px,3vw,32px)">Tu carrito</h1>
    <span class="muted" id="cartBadgePill">{{ $totals['count'] }} artículo(s)</span>
  </div>
  <div class="note" style="margin-bottom:16px;">Los precios ya incluyen IVA (16%).</div>

  <div class="grid">
    {{-- Lista de productos --}}
    <div class="card col-main">
      @if(count($cart) === 0)
        <div class="card-body" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center">
          <p class="muted" style="margin:0">Tu carrito está vacío.</p>
          <a class="btn btn-primary" href="{{ route('web.catalog.index') }}">Explorar catálogo</a>
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
              @foreach($cart as $row)
              <tr data-id="{{ $row['id'] }}">
                <td>
                  <div class="row">
                    <img class="thumb" src="{{ $row['image'] ?: asset('images/placeholder.png') }}"
                         alt="{{ $row['name'] }}" onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
                    <div>
                      <a class="prod-name" href="{{ route('web.catalog.show', $row['slug']) }}">{{ $row['name'] }}</a>
                      <div class="muted small">SKU: {{ $row['sku'] ?: '—' }}</div>
                    </div>
                  </div>
                </td>
                <td class="cell-price" style="text-align:right">${{ number_format($row['price'],2) }}</td>
                <td class="cell-qty" style="text-align:center">
                  <div class="qty">
                    <button type="button" aria-label="Disminuir" onclick="cartMinus({{ $row['id'] }})">−</button>
                    <input class="input" type="number" min="1" max="999" value="{{ $row['qty'] }}" onchange="cartSet({{ $row['id'] }}, this.value)">
                    <button type="button" aria-label="Aumentar" onclick="cartPlus({{ $row['id'] }})">+</button>
                  </div>
                </td>
                <td class="row-total cell-importe" style="text-align:right">
                  ${{ number_format($row['price'] * $row['qty'], 2) }}
                </td>
                <td class="cell-actions">
                  <form method="POST" action="{{ route('web.cart.remove') }}" onsubmit="return confirm('¿Quitar del carrito?')">
                    @csrf
                    <input type="hidden" name="catalog_item_id" value="{{ $row['id'] }}">
                    <button class="btn btn-danger" type="submit">Quitar</button>
                  </form>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>

          <div class="card-body" style="display:flex;gap:10px;justify-content:space-between;flex-wrap:wrap">
            <form method="POST" action="{{ route('web.cart.clear') }}" onsubmit="return confirm('¿Vaciar carrito por completo?')">
              @csrf
              <button class="btn btn-danger" type="submit">Vaciar carrito</button>
            </form>
            <a class="btn btn-ghost" href="{{ route('web.catalog.index') }}">← Seguir comprando</a>
          </div>
        </div>
      @endif
    </div>

    {{-- RESUMEN (con banner motivacional mejorado adentro) --}}
    <aside class="card col-aside sticky" aria-label="Resumen de compra">
      <div class="card-body">
        <h3 style="margin:0 0 10px;font-weight:900">Resumen</h3>

        {{-- Banner motivacional dentro del resumen (se oculta al alcanzar el umbral) --}}
        <div id="freeBanner" class="motivation" style="display: {{ $faltan>0 ? 'grid':'none' }}; gap:10px; margin-bottom:10px;">
          <div class="headline">
            <span class="spark">✨</span>
            <span>¡Casi lo logras! Estás a 
              <strong id="freeMissing">${{ number_format($faltan,2) }}</strong> 
              de obtener envío gratis.
            </span>
          </div>
          <div class="progress" aria-label="Progreso hacia envío gratis" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}">
            <div id="freeProgress" style="width: {{ $pct }}%"></div>
          </div>
          <div class="mini-cta">
            <a class="mini-link" href="{{ route('web.catalog.index') }}">Sigue explorando</a>
            <span class="muted">·</span>
            <span class="muted">Agrega artículos y desbloquea el beneficio</span>
          </div>
        </div>

        {{-- Key/values --}}
        <div class="kv" style="margin-bottom:6px">
          <div class="muted">Artículos</div><div id="sumCount">{{ $totals['count'] }}</div>
        </div>
        <div class="kv">
          <div class="muted">Subtotal</div>
          <div id="sumSubtotal">${{ number_format($totals['subtotal'],2) }}</div>
        </div>

        {{-- Bloque: Envío --}}
        <div class="kv" style="margin-top:6px"><div class="muted">Envío</div><div id="sumEnvio">$0.00</div></div>

        {{-- Si YA alcanzó el umbral: mensaje bonito y oculta cotizador --}}
        <div id="shipFreeBlock" style="display: {{ $faltan>0 ? 'none':'block' }}; margin:10px 0 0;">
          <div class="ship-item" style="border:1px dashed var(--line);background:#f8fffb">
            <div>
              <div><strong>Envío Gratis</strong> — <span class="badge-free">Aplicado</span></div>
              <div class="muted">Superaste ${{ number_format($FREE_SHIP,2) }}. Nosotros nos encargamos del envío 🎉</div>
            </div>
          </div>
        </div>

        {{-- Cotizador (solo si NO ha alcanzado el umbral) --}}
        <div id="shipCotizadorBlock" style="display: {{ $faltan>0 ? 'block':'none' }}; margin-top:10px">
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <label for="ship-cp" class="muted"><strong>CP destino:</strong></label>
            <input id="ship-cp" class="input" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="5"
                   placeholder="Ej. 54000" value="{{ $cliente_cp ?? '' }}" style="width:130px">
            <button class="btn btn-ghost" type="button" onclick="refreshShipping()">Cotizar envío</button>
          </div>
          <div id="shipOptions" class="ship-list" style="margin-top:10px"></div>
        </div>

        <div class="hr"></div>

        <div class="kv">
          <div style="font-weight:900">Total</div>
          <div id="sumTotal" class="total" aria-live="polite">${{ number_format($totals['total'],2) }}</div>
        </div>
        <div class="note" style="margin-top:6px;">Precios ya incluyen IVA (16%).</div>

        <div style="display:flex;flex-direction:column;gap:10px;margin-top:14px">
          <a class="btn btn-primary" href="{{ route('checkout.start') }}">Proceder al pago</a>
          <a class="btn btn-ghost" href="{{ route('web.contacto') }}">Cotizar por WhatsApp/Correo</a>
        </div>
      </div>
    </aside>
  </div>
</div>

@push('scripts')
<script>
  const FREE_SHIP = {{ json_encode($FREE_SHIP) }};

  async function postJson(url, data) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept':'application/json','Content-Type':'application/json'},
      body: JSON.stringify(data)
    });
    return res.json();
  }
  function pulse(el){ if(!el) return; el.classList.remove('pulse'); void el.offsetWidth; el.classList.add('pulse'); }

  function updateSummary(totals){
    document.getElementById('sumCount').textContent    = totals.count;
    document.getElementById('sumSubtotal').textContent = totals.subtotal.toLocaleString('es-MX',{style:'currency',currency:'MXN'});

    toggleFreeUI(totals.subtotal);

    const base = Number(totals.total); // total YA incluye IVA
    const elTotal = document.getElementById('sumTotal');
    elTotal.dataset.baseTotal = String(base.toFixed(2));
    const newTotal = base + (window.shippingPrice || 0);
    elTotal.textContent = newTotal.toLocaleString('es-MX',{style:'currency',currency:'MXN'});
    pulse(elTotal);

    const pill = document.getElementById('cartBadgePill');
    if (pill) pill.textContent = `${totals.count} artículo(s)`;

    const cp = document.getElementById('ship-cp')?.value || '';
    if (cp.length >= 5 && totals.subtotal < FREE_SHIP) refreshShipping();
  }

  async function cartSet(id, qty){
    qty = Math.max(1, parseInt(qty||1,10));
    const json = await postJson('{{ route('web.cart.update') }}', { catalog_item_id: id, qty });
    if (!json.ok) return alert(json.msg || 'Error al actualizar');

    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (row){
      row.querySelector('input[type="number"]').value = qty;
      const price = parseFloat(row.querySelector('.cell-price').textContent.replace(/[^0-9.]/g,'') || '0');
      row.querySelector('.row-total').textContent = (price * qty).toLocaleString('es-MX',{style:'currency',currency:'MXN'});
      row.classList.add('pulse'); setTimeout(()=>row.classList.remove('pulse'), 600);
    }
    updateSummary(json.totals);
  }
  function cartPlus(id){ const input = document.querySelector(`tr[data-id="${id}"] input[type="number"]`); if (!input) return; cartSet(id, (parseInt(input.value||'1',10)+1)); }
  function cartMinus(id){ const input = document.querySelector(`tr[data-id="${id}"] input[type="number"]`); if (!input) return; cartSet(id, Math.max(1,(parseInt(input.value||'1',10)-1))); }

  // ---- UI: banner + cotizador dentro del RESUMEN
  function toggleFreeUI(subtotal){
    const faltan = Math.max(0, FREE_SHIP - subtotal);
    const pct    = FREE_SHIP > 0 ? Math.min(100, Math.round((subtotal / FREE_SHIP)*100)) : 100;

    const banner = document.getElementById('freeBanner');
    const freeBlock = document.getElementById('shipFreeBlock');
    const cotBlock  = document.getElementById('shipCotizadorBlock');

    if (faltan > 0){
      if (banner){
        banner.style.display = 'grid';
        const missingEl = document.getElementById('freeMissing');
        if (missingEl) missingEl.textContent = faltan.toLocaleString('es-MX',{style:'currency',currency:'MXN'});
        const bar = document.getElementById('freeProgress'); if (bar) bar.style.width = pct + '%';
      }
      if (cotBlock) cotBlock.style.display = 'block';
      if (freeBlock) freeBlock.style.display = 'none';
    } else {
      if (banner) banner.style.display = 'none';
      if (cotBlock) cotBlock.style.display = 'none';
      if (freeBlock) freeBlock.style.display = 'block';
      window.shippingPrice = 0;
      window.shippingLabel = 'Envío Gratis — Aplicado';
      updateShippingSummary();
    }
  }

  // ---- Envío
  let shippingPrice = 0;
  let shippingLabel = 'Sin seleccionar';

  async function refreshShipping(){
    const subtotalTxt = document.getElementById('sumSubtotal').textContent.replace(/[^\d.]/g,'');
    const subtotalVal = Number(subtotalTxt||0);
    if (subtotalVal >= FREE_SHIP) return;

    const cp = (document.getElementById('ship-cp')?.value || '').trim();
    const box = document.getElementById('shipOptions');
    if (!cp || cp.length < 5) { box.innerHTML = '<div class="muted">Escribe un CP válido (5 dígitos).</div>'; return; }

    const packageData = { weight_kg: 1, length_cm: 20, width_cm: 20, height_cm: 10 };
    const subtotalStr = document.getElementById('sumSubtotal').textContent.replace(/[^\d.]/g,'');
    const subtotalNum = Number(subtotalStr || {{ json_encode($totals['subtotal']) }}) || 0;

    box.innerHTML = '<div class="muted">Cotizando envío…</div>';
    try{
      const res = await fetch('{{ route('cart.shipping.options') }}', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body: JSON.stringify({ subtotal: subtotalNum, to: { postal_code: cp, country_code: 'MX' }, package: packageData })
      });
      const data = await res.json();
      if (data.error) { box.innerHTML = '<div style="color:#b91c1c">Error al cotizar: '+data.error+'</div>'; return; }
      renderShipOptions(data);
    }catch(e){ box.innerHTML = '<div style="color:#b91c1c">No se pudo cotizar. Intenta de nuevo.</div>'; }
  }

  function renderShipOptions(data){
    const box = document.getElementById('shipOptions');
    box.innerHTML = '';
    if (data.free_shipping) {
      const o = data.options?.[0];
      if(!o){ box.innerHTML = '<div class="muted">No hay opciones disponibles.</div>'; return; }
      shippingPrice = 0; shippingLabel = `${o.carrier} — ${o.service}`;
      box.innerHTML = `
        <div class="ship-item" style="border:1px dashed var(--line);background:#f8fffb">
          <input type="radio" name="shipOpt" value="${o.id}" checked>
          <div>
            <div><strong>${o.carrier}</strong> — ${o.service} <span class="badge-free">Gratis</span></div>
            ${o.eta ? `<div class="muted">ETA: ${o.eta}</div>` : ''}
          </div>
        </div>`;
      updateShippingSummary();
      return selectShipping(o.id, shippingLabel, 0, o);
    }
    if (!data.options?.length){
      box.innerHTML = '<div class="muted">No hay opciones disponibles para el CP indicado.</div>';
      shippingPrice = 0; shippingLabel = 'Sin seleccionar';
      return updateShippingSummary();
    }
    const frag = document.createDocumentFragment();
    data.options.forEach(o=>{
      const div = document.createElement('label');
      div.className = 'ship-item';
      div.innerHTML = `
        <input type="radio" name="shipOpt" value="${o.id}">
        <div>
          <div><strong>${o.carrier}</strong> — ${o.service}</div>
          <div class="muted">${Number(o.price||0).toLocaleString('es-MX',{style:'currency',currency:'MXN'})}${o.eta ? ' · ETA: '+o.eta : ''}</div>
        </div>`;
      div.addEventListener('change',()=>{
        shippingPrice = Number(o.price||0);
        shippingLabel = `${o.carrier} — ${o.service}`;
        updateShippingSummary();
        selectShipping(o.id, shippingLabel, shippingPrice, o);
      });
      frag.appendChild(div);
    });
    box.appendChild(frag);
    const first = box.querySelector('input[name="shipOpt"]'); if(first){ first.checked = true; first.dispatchEvent(new Event('change')); }
  }

  async function selectShipping(optionId, label, price, raw){
    try {
      await fetch('{{ route('cart.shipping.select') }}', {
        method:'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body: JSON.stringify({ option_id: optionId, option_label: label, price: price, raw: raw || null })
      });
    } catch(e) { console.warn('No se pudo guardar la selección de envío', e); }
  }

  function updateShippingSummary(){
    const elEnvio = document.getElementById('sumEnvio');
    elEnvio.textContent = (shippingPrice||0).toLocaleString('es-MX',{style:'currency',currency:'MXN'});
    const baseStr = document.getElementById('sumTotal').dataset.baseTotal;
    const base = Number(baseStr || {{ json_encode($totals['total']) }}) || 0;
    const newTotal = base + (shippingPrice||0);
    const elTotal = document.getElementById('sumTotal');
    elTotal.textContent = newTotal.toLocaleString('es-MX',{style:'currency',currency:'MXN'});
    pulse(elEnvio); pulse(elTotal);
  }

  // Init
  (function initPage(){
    const elTot = document.getElementById('sumTotal');
    elTot.dataset.baseTotal = '{{ number_format($totals["total"],2,".","") }}'; // incluye IVA
    toggleFreeUI({{ json_encode($totals['subtotal']) }});
    const seed = document.getElementById('ship-cp')?.value || '';
    if (seed && seed.length >= 5 && {{ json_encode($totals['subtotal']) }} < FREE_SHIP) refreshShipping();
  })();
</script>
@endpush
@endsection
