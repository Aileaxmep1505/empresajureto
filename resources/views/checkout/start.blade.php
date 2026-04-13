{{-- resources/views/checkout/start.blade.php --}}
@extends('layouts.web')
@section('title','Entrega')

@section('content')
@php
  $cart      = is_array($cart ?? null) ? $cart : (array)session('cart', []);
  $subtotal  = 0;
  foreach ($cart as $r) { $subtotal += (float)($r['price'] ?? 0) * (int)($r['qty'] ?? 1); }
  $total = $subtotal; // El envío se suma en checkout/shipping
  // Espera que el controlador pase $addresses (colección/array) y $address (última usada)
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
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .card.hoverable:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.04);
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
  .btn:active {
    transform: scale(0.98);
  }
  .btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
  }
  .btn-primary {
    background: var(--blue);
    color: #ffffff;
  }
  .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(0, 122, 255, 0.15);
  }
  .btn-ghost {
    background: transparent;
    color: #555555;
  }
  .btn-ghost:hover {
    background: var(--bg);
    color: var(--ink);
  }

  /* ====================== CHIPS / BADGES ====================== */
  .chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 999px;
    background: var(--blue-soft);
    color: var(--blue);
    font-weight: 700;
    font-size: 0.8rem;
  }
  .chip.success { background: var(--success-soft); color: var(--success); }
  .chip.danger { background: var(--danger-soft); color: var(--danger); }

  /* ====================== LISTS & ROWS ====================== */
  .addr-empty {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    background: var(--bg);
    border: 1px dashed var(--line);
    padding: 24px;
    border-radius: 12px;
  }

  .sum-row {
    display: flex;
    justify-content: space-between;
    margin: 14px 0;
    font-weight: 600;
    color: var(--text);
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

  .addr-list {
    display: grid;
    gap: 12px;
  }
  .addr-item {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 16px;
    align-items: center;
    padding: 18px;
    border: 1px solid var(--line);
    background: var(--card);
    border-radius: 12px;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  }
  .addr-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.03);
  }
  .addr-item.active {
    border-color: var(--blue);
    box-shadow: 0 0 0 1px var(--blue);
  }
  .addr-item input[type="radio"] {
    accent-color: var(--blue);
    width: 18px;
    height: 18px;
    cursor: pointer;
  }

  .order-row {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 16px;
    align-items: center;
    padding: 16px 0;
    border-bottom: 1px solid var(--line);
  }
  .order-row:last-child {
    border-bottom: none;
  }
  .thumb {
    width: 64px; height: 64px;
    object-fit: cover;
    border-radius: 12px;
    border: 1px solid var(--line);
    background: var(--bg);
  }

  /* ====================== MODALS ====================== */
  .modal-back {
    position: fixed;
    inset: 0;
    background: rgba(17, 17, 17, 0.45);
    display: none;
    z-index: 9998; /* Elevado para cubrir el header */
  }
  
  .modal {
    position: fixed;
    left: 50%; 
    top: 50%;
    transform: translate(-50%, -50%);
    width: min(520px, 92vw);
    max-height: calc(100vh - 64px); /* Margen para que no toque los bordes */
    overflow-y: auto; /* Scroll interno si es necesario */
    background: var(--card);
    border-radius: 16px;
    display: none;
    z-index: 9999; /* Por encima de todo */
    box-shadow: 0 24px 48px rgba(0,0,0,0.15);
  }

  .modal.open, .modal-back.open { display: block; }
  
  .modal .card-h {
    padding: 20px 24px;
    position: sticky;
    top: 0;
    z-index: 10;
    background: var(--card);
    border-bottom: 1px solid var(--line);
  }
  .modal .card-b { padding: 24px; }

  /* ====================== FORMS ====================== */
  .f { display: grid; gap: 16px; }
  .g2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  @media(max-width: 520px) { .g2 { grid-template-columns: 1fr; } }

  .fi { display: grid; gap: 8px; }
  .fi label {
    font-size: 0.85rem;
    color: var(--ink);
    font-weight: 600;
  }
  .fi input, .fi select, .fi textarea {
    width: 100%;
    box-sizing: border-box;
    border: 1px solid var(--line);
    border-radius: 8px;
    padding: 12px 14px;
    font-size: 0.95rem;
    font-family: inherit;
    font-weight: 500;
    color: var(--ink);
    background: var(--card);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
  }
  .fi input::placeholder, .fi textarea::placeholder {
    color: #a0a0a0;
    font-weight: 500;
  }
  .fi input:focus, .fi select:focus, .fi textarea:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }
  .fi input[readonly] {
    background: var(--bg);
    color: var(--muted);
    cursor: not-allowed;
  }
  .fi select {
    padding-right: 36px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23888888'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
    background-position: right 12px center;
    background-size: 16px;
    background-repeat: no-repeat;
  }
</style>

<div class="ck-page">
  <div class="stepper" aria-label="Progreso de compra">
    <div class="step active"><span class="dot">1</span> Entrega</div>
    <div class="step"><span class="dot">2</span> Factura</div>
    <div class="step"><span class="dot">3</span> Envío</div>
    <div class="step"><span class="dot">4</span> Pago</div>
  </div>

  <div class="ck-wrap">
    {{-- Columna izquierda --}}
    <div class="card">
      <div class="card-h">
        <div>
          <h2 style="font-weight:700; margin-bottom: 4px;">Dirección de entrega</h2>
          <div class="muted" id="addr-subtitle">Selecciona tu dirección o agrega una nueva.</div>
        </div>
        <button class="btn btn-ghost" id="btn-open-modal">Agregar / Cambiar</button>
      </div>

      <div class="card-b" id="address-box">
        {{-- Listado de direcciones guardadas --}}
        <section id="saved-addrs" style="display:none; margin-bottom: 32px;">
          <h3 style="font-weight:700; margin-bottom: 16px; font-size: 1.1rem;">Mis direcciones</h3>
          <div class="addr-list" id="saved-list"></div>
        </section>

        {{-- Estado inicial si no hay nada seleccionado --}}
        <div class="addr-empty" id="addr-empty" style="display:none; margin-bottom: 32px;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2">
            <path d="M12 22s8-4 8-10a8 8 0 10-16 0c0 6 8 10 8 10z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
          <div>
            <strong style="font-size: 1.05rem;">Sin dirección seleccionada</strong>
            <div class="muted" style="margin-top: 4px;">Usa “Agregar / Cambiar” para capturarla.</div>
          </div>
        </div>

        {{-- Tarjeta de dirección seleccionada (se inyecta por JS) --}}
        <div id="current-addr-anchor"></div>

        {{-- Resumen del pedido --}}
        <h3 style="font-weight:700; margin: 32px 0 16px; font-size: 1.1rem;">Resumen de tu pedido</h3>
        @forelse($cart as $row)
          <div class="order-row">
            <img class="thumb" src="{{ $row['image'] ?? asset('images/placeholder.png') }}" alt="">
            <div>
              <div style="font-weight:600; color: var(--ink);">{{ $row['name'] ?? 'Producto' }}</div>
              <div class="muted" style="font-size: 0.9rem; margin-top: 4px;">Cantidad: {{ $row['qty'] ?? 1 }}</div>
            </div>
            <div style="font-weight:700; color: var(--ink);">${{ number_format(($row['price'] ?? 0) * ($row['qty'] ?? 1), 2) }}</div>
          </div>
        @empty
          <div class="muted" style="padding: 20px 0;">Tu carrito está vacío.</div>
        @endforelse

        {{-- Aviso: la paquetería se elige en el siguiente paso --}}
        <div style="margin-top: 24px;">
          <span class="chip">La paquetería se elige en el siguiente paso</span>
        </div>
      </div>
    </div>

    {{-- Sidebar --}}
    <aside class="card" aria-label="Resumen">
      <div class="card-b">
        <div class="sum-row"><span>Subtotal</span><span id="sum-subtotal" style="color:var(--ink);">${{ number_format($subtotal,2) }}</span></div>
        <div class="sum-row"><span>Envío</span><span id="sum-shipping" class="muted" style="font-size: 0.9rem;">A elegir luego</span></div>
        <hr class="line">
        <div class="sum-row total"><span>Total</span><span id="sum-total">${{ number_format($total,2) }}</span></div>
        <div class="muted" style="font-size: 0.85rem; margin-top: 8px;">Los precios incluyen IVA</div>
        <hr class="line">
        <button id="btn-continue" class="btn btn-primary" style="width:100%; margin-top: 8px; padding: 14px;" disabled>Continuar</button>
      </div>
    </aside>
  </div>
</div>

{{-- ===== Modal Dirección ===== --}}
<div class="modal-back" id="addr-backdrop" aria-hidden="true"></div>
<section class="modal" id="addr-modal" role="dialog" aria-modal="true" aria-labelledby="addr-title">
  <div class="card-h">
    <h3 id="addr-title" style="font-weight:700; font-size: 1.25rem;">Nueva dirección</h3>
    <button class="btn btn-ghost" id="addr-close" style="padding: 6px 12px;">Cerrar</button>
  </div>
  <div class="card-b">
    <form id="addr-form" class="f" autocomplete="off">
      @csrf
      <div class="g2">
        <div class="fi">
          <label for="nombre_recibe">¿Quién recibe? (opcional)</label>
          <input id="nombre_recibe" name="nombre_recibe" type="text" placeholder="Nombre de contacto">
        </div>
        <div class="fi">
          <label for="telefono">Teléfono (opcional)</label>
          <input id="telefono" name="telefono" type="tel" placeholder="Celular o fijo">
        </div>
      </div>

      <div class="g2">
        <div class="fi">
          <label for="cp">C.P.</label>
          <input id="cp" name="cp" inputmode="numeric" placeholder="Ej. 52060" required>
        </div>
        <div class="fi">
          <label for="colonia">Colonia</label>
          <input list="colonias-list" id="colonia" name="colonia" placeholder="Colonia" required>
          <datalist id="colonias-list"></datalist>
        </div>
      </div>

      <div class="g2">
        <div class="fi">
          <label for="estado">Estado</label>
          <input id="estado" name="estado" type="text" readonly>
        </div>
        <div class="fi">
          <label for="municipio">Municipio / Alcaldía</label>
          <input id="municipio" name="municipio" type="text" readonly>
        </div>
      </div>

      <div class="fi">
        <label for="calle">Calle</label>
        <input id="calle" name="calle" type="text" required>
      </div>

      <div class="g2">
        <div class="fi">
          <label for="num_ext">Número exterior</label>
          <input id="num_ext" name="num_ext" type="text" required>
        </div>
        <div class="fi">
          <label for="num_int">Número interior (opcional)</label>
          <input id="num_int" name="num_int" type="text">
        </div>
      </div>

      <div class="g2">
        <div class="fi">
          <label for="entre_calles">Entre calles (opcional)</label>
          <input id="entre_calles" name="entre_calles" type="text" placeholder="Ej. Av. Juárez e Hidalgo">
        </div>
        <div class="fi">
          <label for="referencias">Referencias (opcional)</label>
          <input id="referencias" name="referencias" type="text" placeholder="Fachada, color, etc.">
        </div>
      </div>

      <div style="display:flex; gap:12px; justify-content:flex-end; margin-top: 16px;">
        <button type="button" class="btn btn-ghost" id="addr-cancel">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar dirección</button>
      </div>
    </form>
    <div id="addr-msg" class="muted" style="margin-top:16px; display:none; color: var(--danger); font-size: 0.9rem;"></div>
  </div>
</section>

{{-- ===== Modal ¿Requieres factura? ===== --}}
<div class="modal-back" id="inv-backdrop" aria-hidden="true"></div>
<section class="modal" id="inv-modal" role="dialog" aria-modal="true" aria-labelledby="inv-title">
  <div class="card-h">
    <h3 id="inv-title" style="font-weight:700; font-size: 1.25rem;">¿Requieres factura?</h3>
    <button class="btn btn-ghost" id="inv-close" style="padding: 6px 12px;">Cerrar</button>
  </div>
  <div class="card-b">
    <p class="muted" style="margin: 0 0 24px 0; line-height: 1.5;">
      Si necesitas CFDI, capturaremos tus datos en el siguiente paso.
    </p>
    <div style="display:flex; flex-wrap:wrap; gap:12px;">
      <button type="button" class="btn btn-ghost" id="btn-no-invoice" style="flex: 1; min-width: 200px;">No, continuar sin factura</button>
      <button type="button" class="btn btn-primary" id="btn-yes-invoice" style="flex: 1; min-width: 200px;">Sí, necesito factura</button>
    </div>
  </div>
</section>

@push('scripts')
<script>
(function(){
  const $  = s => document.querySelector(s);
  const $$ = s => Array.from(document.querySelectorAll(s));
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const SUBTOTAL  = @json($subtotal);

  const ROUTE_CP             = @json(route('checkout.cp.lookup'));
  const ROUTE_ADDRESS_STORE  = @json(route('checkout.address.store'));
  const ROUTE_ADDRESS_SELECT = @json(route('checkout.address.select'));
  const ROUTE_INV            = @json(route('checkout.invoice'));
  const ROUTE_INV_SKIP       = @json(route('checkout.invoice.skip'));
  const ROUTE_NEXT_STEP      = @json(route('checkout.shipping'));

  function showToast(text){
    const el = document.createElement('div');
    el.textContent = text;
    el.style.cssText = 'position:fixed; left:50%; bottom:32px; transform:translateX(-50%); background:var(--ink); color:#fff; padding:12px 24px; border-radius:999px; font-weight:600; font-size:0.95rem; box-shadow:0 8px 24px rgba(0,0,0,0.15); z-index:9999;';
    document.body.appendChild(el);
    setTimeout(()=>el.remove(), 2500);
  }
  function showMsg(text){
    const m = $('#addr-msg'); m.textContent = text; m.style.display='block';
    setTimeout(()=>{ m.style.display='none'; }, 4500);
  }

  const addrModal = $('#addr-modal'), addrBack = $('#addr-backdrop');
  const invModal  = $('#inv-modal'),  invBack  = $('#inv-backdrop');

  function openAddr(){ addrModal.classList.add('open'); addrBack.classList.add('open'); }
  function closeAddr(){ addrModal.classList.remove('open'); addrBack.classList.remove('open'); }
  function openInv(){ invModal.classList.add('open'); invBack.classList.add('open'); }
  function closeInv(){ invModal.classList.remove('open'); invBack.classList.remove('open'); }

  $('#btn-open-modal')?.addEventListener('click', openAddr);
  $('#addr-close')?.addEventListener('click', closeAddr);
  $('#addr-cancel')?.addEventListener('click', closeAddr);
  addrBack?.addEventListener('click', closeAddr);

  $('#inv-close')?.addEventListener('click', closeInv);
  invBack?.addEventListener('click', closeInv);

  document.addEventListener('keydown', e=>{
    if(e.key==='Escape'){
      if(invModal.classList.contains('open')) closeInv();
      else if(addrModal.classList.contains('open')) closeAddr();
    }
  });

  const cp = $('#cp'), estado = $('#estado'), municipio = $('#municipio'), colonia = $('#colonia');
  const datalist = $('#colonias-list');

  function setEditable(ro){
    if(ro){ estado.setAttribute('readonly',''); municipio.setAttribute('readonly',''); }
    else { estado.removeAttribute('readonly'); municipio.removeAttribute('readonly'); }
  }

  async function lookupCP(code){
    estado.value=''; municipio.value=''; datalist.innerHTML=''; setEditable(true);
    if(!/^\d{5}$/.test(code||'')) return;
    try{
      const res = await fetch(`${ROUTE_CP}?cp=${encodeURIComponent(code)}`, { headers:{'Accept':'application/json'}});
      if(!res.ok) throw new Error('CP no encontrado');
      const data = await res.json();
      if(data.state) estado.value = data.state;
      if(data.municipality) municipio.value = data.municipality;
      const cols = Array.isArray(data.colonies) ? data.colonies : [];
      datalist.innerHTML = cols.map(c=>`<option value="${c}">`).join('');
      if(!colonia.value && cols[0]) colonia.value = cols[0];
      if(!data.state || !data.municipality) setEditable(false);
    }catch(err){
      console.warn(err); setEditable(false);
      showMsg('No pudimos autocompletar con el C.P. Escribe estado y municipio manualmente.');
    }
  }
  cp?.addEventListener('input', e=>{ if((e.target.value||'').length===5) lookupCP(e.target.value) });

  const savedAddrsSection = $('#saved-addrs');
  const savedList         = $('#saved-list');
  const addrEmpty         = $('#addr-empty');
  const currentAnchor     = $('#current-addr-anchor');
  const btnContinue       = $('#btn-continue');

  let ADDRS = @json($addresses ?? []);
  const LAST = @json($address ?? null);
  let selectedAddr = null;

  function fmtAddrLines(addr){
    const line1 = [addr.calle || addr.street, addr.num_ext || addr.ext_number, (addr.num_int || addr.int_number) ? `Int ${addr.num_int || addr.int_number}` : null].filter(Boolean).join(' ');
    const line2 = [addr.colonia || addr.colony, addr.cp || addr.postal_code].filter(Boolean).join(', ');
    const line3 = [addr.municipio || addr.municipality, addr.estado || addr.state].filter(Boolean).join(', ');
    const extra1 = addr.entre_calles ? `Entre: ${addr.entre_calles}` : null;
    const extra2 = addr.referencias || addr.references ? `Ref.: ${addr.referencias || addr.references}` : null;
    const contact = (addr.nombre_recibe || addr.contact_name) ? `Contacto: ${(addr.nombre_recibe || addr.contact_name)}${(addr.telefono || addr.phone) ? ' · '+(addr.telefono || addr.phone) : ''}` : null;
    return [line1,line2,line3,extra1,extra2,contact].filter(Boolean);
  }

  function renderCurrentAddrCard(addr){
    const lines = fmtAddrLines(addr);
    const html = `
      <div class="card" style="border: 1px solid var(--blue); background: var(--blue-soft); margin-bottom: 32px;" data-addr-card>
        <div class="card-b" style="display:flex; gap:16px; align-items:flex-start">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2" style="margin-top: 2px;">
            <path d="M12 22s8-4 8-10a8 8 0 10-16 0c0 6 8 10 8 10z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
          <div>
            <strong style="color: var(--blue); font-size: 1.05rem;">Entregar en:</strong>
            <div style="color: var(--ink); margin-top: 8px; white-space: pre-line; line-height: 1.6; font-weight: 500;">${lines.join('\n')}</div>
          </div>
        </div>
      </div>`;
    document.querySelector('[data-addr-card]')?.remove();
    currentAnchor.insertAdjacentHTML('afterend', html);
    $('#addr-subtitle').textContent = 'Dirección lista. Continúa para elegir paquetería.';
    btnContinue.disabled = false;
  }

  async function persistSelectedAddressById(id){
    try{
      await fetch(ROUTE_ADDRESS_SELECT, {
        method:'POST',
        headers:{'X-CSRF-TOKEN': csrf, 'Accept':'application/json','Content-Type':'application/json'},
        body: JSON.stringify({ id })
      });
    }catch(err){ console.warn('No se pudo persistir la dirección seleccionada:', err); }
  }

  function renderSavedList(){
    savedList.innerHTML = '';
    if(!Array.isArray(ADDRS)) ADDRS = [];

    if(ADDRS.length){
      savedAddrsSection.style.display = 'block';
      addrEmpty.style.display = 'none';
    }else{
      savedAddrsSection.style.display = 'none';
      addrEmpty.style.display = 'flex';
      return;
    }

    ADDRS.forEach((a, i)=>{
      const idAttr = `addr_${a.id ?? i}`;
      const label = [
        a.alias ? `<strong style="color:var(--ink)">${a.alias}</strong>` : '<strong style="color:var(--ink)">Dirección</strong>',
        [a.calle || a.street, a.num_ext || a.ext_number].filter(Boolean).join(' '),
        [a.colonia || a.colony, a.cp || a.postal_code].filter(Boolean).join(', '),
        [a.municipio || a.municipality, a.estado || a.state].filter(Boolean).join(', ')
      ].filter(Boolean).join(' · ');

      const isDefault = !!a.is_default;
      const html = `
        <label class="addr-item" for="${idAttr}" data-id="${a.id ?? i}">
          <input type="radio" name="addrOption" id="${idAttr}">
          <div style="font-size: 0.95rem; line-height: 1.5; color: var(--text);">
            <div style="margin-bottom: 4px;">${label} ${isDefault ? '<span class="chip" style="margin-left:8px">Predeterminada</span>' : ''}</div>
            ${a.nombre_recibe || a.contact_name ? `<div class="muted">Recibe: ${a.nombre_recibe || a.contact_name}</div>` : ''}
          </div>
        </label>
      `;
      savedList.insertAdjacentHTML('beforeend', html);
    });

    savedList.addEventListener('change', async (e)=>{
      const r = e.target.closest('input[type="radio"][name="addrOption"]');
      if(!r) return;

      $$('.addr-item').forEach(el=>el.classList.remove('active'));
      const item = r.closest('.addr-item'); item.classList.add('active');

      const radios = $$('#saved-list input[type="radio"]');
      const idx = radios.indexOf(r);
      const chosen = ADDRS[idx];

      selectedAddr = chosen;
      renderCurrentAddrCard(chosen);

      if(chosen?.id != null){ await persistSelectedAddressById(chosen.id); }
    }, { once:true });
  }

  function autoSelectInitial(){
    if(LAST){
      selectedAddr = LAST;
    }else if(Array.isArray(ADDRS) && ADDRS.length){
      selectedAddr = ADDRS.find(a=>a.is_default) || ADDRS[0];
    }else{
      selectedAddr = null;
    }

    renderSavedList();

    if(selectedAddr){
      const idx = Array.isArray(ADDRS) ? ADDRS.findIndex(a=>(a?.id ?? -1) === (selectedAddr?.id ?? -2)) : -1;
      const radio = idx >= 0 ? savedList.querySelectorAll('input[type="radio"]')[idx] : null;
      if(radio){ radio.checked = true; radio.dispatchEvent(new Event('change',{bubbles:true})); }
      else{ renderCurrentAddrCard(selectedAddr); btnContinue.disabled = false; }
    }else{
      addrEmpty.style.display = 'flex';
      btnContinue.disabled = true;
    }
  }

  $('#addr-form')?.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(e.target);
    const payload = Object.fromEntries(fd.entries());

    const required = ['calle','num_ext','colonia','cp'];
    for(const k of required){
      if(!String(payload[k]||'').trim()){
        showMsg('Por favor completa calle, número exterior, colonia y C.P.');
        return;
      }
    }
    if(!payload.estado || !payload.municipio){
      showMsg('Escribe estado y municipio (no se pudieron autocompletar).');
      return;
    }

    try{
      const res = await fetch(ROUTE_ADDRESS_STORE, {
        method:'POST',
        headers:{'X-CSRF-TOKEN': csrf, 'Accept':'application/json', 'Content-Type':'application/json'},
        body: JSON.stringify(payload)
      });
      if(!res.ok) throw new Error(await res.text() || 'Error al guardar');

      const data = await res.json();
      const addr = data?.addr || payload;
      if(!addr.id && data?.id) addr.id = data.id;

      if(!Array.isArray(ADDRS)) ADDRS = [];
      ADDRS.unshift(addr);
      selectedAddr = addr;

      closeAddr();
      renderSavedList();
      renderCurrentAddrCard(addr);

      const firstRadio = savedList.querySelector('input[type="radio"]');
      if(firstRadio){ firstRadio.checked = true; }

      if(addr?.id != null){ await persistSelectedAddressById(addr.id); }

      showToast('Dirección guardada');
    }catch(err){
      console.error(err);
      showMsg('No se pudo guardar la dirección. Revisa los campos e intenta de nuevo.');
    }
  });

  $('#btn-continue')?.addEventListener('click', async (e)=>{
    e.preventDefault();
    if(!selectedAddr){
      showToast('Selecciona o agrega una dirección');
      return;
    }
    if(selectedAddr?.id != null){
      await persistSelectedAddressById(selectedAddr.id);
    }
    openInv();
  });

  async function goNext(url){ window.location.href = url; }

  $('#btn-no-invoice')?.addEventListener('click', async ()=>{
    closeInv();
    try{ await fetch(ROUTE_INV_SKIP, { method:'POST', headers:{'X-CSRF-TOKEN': csrf, 'Accept':'application/json'} }); }catch(_){}
    await goNext(ROUTE_NEXT_STEP);
  });

  $('#btn-yes-invoice')?.addEventListener('click', async ()=>{
    closeInv();
    await goNext(ROUTE_INV);
  });

  autoSelectInitial();
})();
</script>
@endpush
@endsection