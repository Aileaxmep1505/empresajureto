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
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700;800&display=swap" rel="stylesheet"/>

<style>
  /* ====================== PAGE BG (solo esquinas superiores) ====================== */
  body{
    font-family:"Quicksand", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    background:#f6f8fc;
    color:#0e1726;
  }
  body::before{
    content:"";
    position:fixed; inset:0;
    z-index:-1;
    pointer-events:none;
    background:
      radial-gradient(420px 240px at 0% 0%,
        rgba(147,197,253,.38) 0%,
        rgba(147,197,253,.18) 34%,
        rgba(147,197,253,0) 72%),
      radial-gradient(420px 240px at 100% 0%,
        rgba(134,239,172,.30) 0%,
        rgba(134,239,172,.14) 34%,
        rgba(134,239,172,0) 72%),
      linear-gradient(180deg, #f3f7ff 0%, #f8fff5 55%, #ffffff 100%);
    background-attachment:fixed;
  }

  :root{
    --brand:#111827;
    --brand-ink:#0b1220;
    --accent:#10b981;
    --muted:#6b7280;
    --line:#e8eef6;
    --surface:rgba(255,255,255,.86);
    --radius:18px;
    --shadow:0 14px 36px rgba(2,8,23,.08);
    --shadow-soft:0 10px 26px rgba(2,8,23,.06);
    --focus:0 0 0 3px rgba(17,24,39,.14);
  }

  /* ✅ 100% ancho desktop + responsivo */
  .ck-page{
    width:100%;
    padding: clamp(14px, 2vw, 22px);
  }

  .ck-wrap{
    display:grid;
    grid-template-columns: 2fr 1fr;
    gap:18px;
    width:100%;
  }
  @media(max-width: 980px){
    .ck-wrap{ grid-template-columns:1fr; }
  }

  /* Cards */
  .card{
    background: var(--surface);
    border:1px solid var(--line);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    backdrop-filter: blur(10px);
    overflow:hidden;
  }
  .card-h{
    padding:16px 18px;
    border-bottom:1px solid rgba(232,238,246,.95);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
    background: rgba(255,255,255,.72);
  }
  .card-b{ padding:16px 18px; }

  /* Stepper */
  .stepper{
    display:flex;
    gap:12px;
    align-items:center;
    flex-wrap:wrap;
    margin:0 0 18px;
  }
  .step{
    display:flex; align-items:center; gap:10px;
    font-weight:800;
    color:#334155;
    padding:10px 12px;
    border:1px solid var(--line);
    border-radius:999px;
    background: rgba(255,255,255,.78);
    box-shadow: var(--shadow-soft);
  }
  .dot{
    width:28px;height:28px;border-radius:999px;
    display:inline-flex;align-items:center;justify-content:center;
    border:2px solid var(--brand);
    font-weight:900;
  }
  .dot.active{ background:var(--brand); color:#fff; }

  /* Buttons */
  .btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:14px;
    padding:10px 14px;
    font-weight:900;
    text-decoration:none;
    border:1px solid var(--line);
    background: rgba(255,255,255,.92);
    cursor:pointer;
    gap:10px;
    transition: transform .15s ease, box-shadow .2s ease, background .2s ease, border-color .2s ease;
  }
  .btn:focus-visible{ outline:none; box-shadow: var(--focus); }
  .btn:hover{ transform: translateY(-1px); box-shadow: var(--shadow-soft); }
  .btn:disabled{ opacity:.5; cursor:not-allowed; transform:none; box-shadow:none; }

  .btn-primary{
    background: linear-gradient(180deg, #111827 0%, #0b1220 100%);
    border-color: rgba(255,255,255,.10);
    color:#fff;
    box-shadow: 0 14px 34px rgba(17,24,39,.22);
  }
  .btn-primary:hover{ box-shadow: 0 16px 38px rgba(17,24,39,.28); }

  .btn-ghost{ background: rgba(255,255,255,.92); }

  .muted{ color: var(--muted); font-weight:700; }

  /* Address empty */
  .addr-empty{
    display:flex;
    gap:12px;
    align-items:flex-start;
    background: rgba(248,251,255,.85);
    border:1px dashed #c4d1ff;
    padding:14px;
    border-radius:14px;
    color:#1f2a44;
    box-shadow: var(--shadow-soft);
  }

  /* Summary rows */
  .sum-row{
    display:flex;
    justify-content:space-between;
    margin:10px 0;
    font-weight:900;
  }
  .line{ border:0; border-top:1px solid rgba(232,238,246,.95); margin:16px 0; }

  /* Saved addresses */
  .addr-list{ display:grid; gap:10px; }
  .addr-item{
    display:grid;
    grid-template-columns:auto 1fr auto;
    gap:12px;
    align-items:center;
    padding:12px;
    border:1px solid var(--line);
    background: rgba(255,255,255,.92);
    border-radius:14px;
    transition: transform .15s ease, box-shadow .2s ease, border-color .2s ease;
  }
  .addr-item:hover{ transform: translateY(-1px); box-shadow: var(--shadow-soft); }
  .addr-item.active{ border-color:#bfd2ff; box-shadow:0 12px 28px rgba(31,76,240,.08); }

  .chip{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 12px;
    border-radius:999px;
    background: rgba(238,242,255,.9);
    color:#22396b;
    font-weight:900;
    border:1px solid #d9e3ff;
  }

  /* Resumen pedido list */
  .order-row{
    display:grid;
    grid-template-columns:auto 1fr auto;
    gap:12px;
    align-items:center;
    padding:10px 0;
    border-bottom:1px solid rgba(241,245,249,.95);
  }
  .thumb{
    width:64px;height:64px;
    object-fit:cover;
    border-radius:14px;
    border:1px solid var(--line);
    background:#f1f5fb;
  }

  /* ====================== MODALS (más chico + minimalista) ====================== */
  .modal-back{
    position:fixed;
    inset:0;
    background:rgba(15,23,42,.34);
    backdrop-filter:blur(3px);
    display:none;
    z-index:60;
  }

  .modal{
    position:fixed;
    left:50%;
    top:50%;
    transform:translate(-50%,-50%);
    width:min(560px, 92vw);          /* ✅ más angosto */
    max-height:min(78vh, 78dvh);     /* ✅ menos alto */
    overflow:auto;
    background: rgba(255,255,255,.96);
    border:1px solid rgba(232,238,246,.95);
    border-radius:16px;
    display:none;
    z-index:61;
    box-shadow:0 24px 70px rgba(2,8,23,.22);
    backdrop-filter: blur(10px);
  }
  .modal.open,.modal-back.open{ display:block; }

  .modal .card-h{
    padding:12px 14px;
    border-bottom:1px solid rgba(232,238,246,.95);
    background:rgba(255,255,255,.88);
  }
  .modal .card-b{ padding:14px; }

  .f{ display:grid; gap:10px; }
  .g2{ display:grid; grid-template-columns:1fr 1fr; gap:10px; }
  @media(max-width:720px){ .g2{ grid-template-columns:1fr; } }

  .fi{ display:grid; gap:6px; }
  .fi label{
    font-size:.86rem;
    color:#334155;
    font-weight:900;
  }

  /* ====================== INPUTS (MODERNOS) ====================== */
  .fi input,.fi select,.fi textarea{
    width:100%;
    border:1px solid rgba(226,232,240,.95);
    border-radius:14px;
    padding:11px 12px;              /* ✅ compacto + moderno */
    font-size:.96rem;
    font-weight:700;
    color:#0f172a;
    background: rgba(255,255,255,.98);
    box-shadow: 0 1px 0 rgba(2,8,23,.02);
    transition: border-color .18s ease, box-shadow .18s ease, transform .12s ease, background .18s ease;
    outline:none;
    -webkit-appearance:none;
    appearance:none;
  }
  .fi input::placeholder,
  .fi textarea::placeholder{
    color:#94a3b8;
    font-weight:700;
  }
  .fi input:hover,.fi select:hover,.fi textarea:hover{
    border-color: rgba(203,213,225,.95);
  }
  .fi input:focus,.fi select:focus,.fi textarea:focus{
    border-color: rgba(17,24,39,.55);
    box-shadow: 0 0 0 4px rgba(17,24,39,.12);
    background:#ffffff;
  }
  .fi input[readonly]{
    background: rgba(248,250,252,.95);
    border-color: rgba(226,232,240,.95);
    color:#334155;
  }

  /* Select flecha minimal */
  .fi select{
    padding-right:34px;
    background-image:
      linear-gradient(45deg, transparent 50%, #64748b 50%),
      linear-gradient(135deg, #64748b 50%, transparent 50%);
    background-position:
      calc(100% - 18px) 50%,
      calc(100% - 12px) 50%;
    background-size: 6px 6px, 6px 6px;
    background-repeat:no-repeat;
  }

  @media (max-width:520px){
    .modal{
      width:min(520px, 94vw);
      max-height:min(82vh, 82dvh);
    }
  }
</style>

<div class="ck-page">
  <div class="stepper" aria-label="Progreso de compra">
    <div class="step"><span class="dot active">1</span> Entrega</div>
    <div class="step"><span class="dot">2</span> Factura</div>
    <div class="step"><span class="dot">3</span> Envío</div>
    <div class="step"><span class="dot">4</span> Pago</div>
  </div>

  <div class="ck-wrap">
    {{-- Columna izquierda --}}
    <div class="card">
      <div class="card-h">
        <div>
          <h2 style="margin:0 0 2px;font-weight:1000;color:var(--brand-ink)">Dirección de entrega</h2>
          <div class="muted" id="addr-subtitle">Selecciona tu dirección o agrega una nueva.</div>
        </div>
        <button class="btn btn-ghost" id="btn-open-modal">Agregar / Cambiar</button>
      </div>

      <div class="card-b" id="address-box">
        {{-- Listado de direcciones guardadas --}}
        <section id="saved-addrs" style="display:none">
          <h3 style="margin:0 0 10px;font-weight:1000;color:var(--brand-ink)">Mis direcciones</h3>
          <div class="addr-list" id="saved-list"></div>
        </section>

        {{-- Estado inicial si no hay nada seleccionado --}}
        <div class="addr-empty" id="addr-empty" style="display:none">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
            <path d="M12 22s8-4 8-10a8 8 0 10-16 0c0 6 8 10 8 10z" stroke="var(--brand)" stroke-width="2"/>
            <circle cx="12" cy="12" r="3" stroke="var(--brand)" stroke-width="2"/>
          </svg>
          <div>
            <strong>Sin dirección seleccionada</strong>
            <div class="muted">Usa “Agregar / Cambiar” para capturarla.</div>
          </div>
        </div>

        {{-- Tarjeta de dirección seleccionada (se inyecta por JS) --}}
        <div id="current-addr-anchor"></div>

        {{-- Resumen del pedido --}}
        <h3 style="margin:18px 0 10px;font-weight:1000;color:var(--brand-ink)">Resumen de tu pedido</h3>
        @forelse($cart as $row)
          <div class="order-row">
            <img class="thumb" src="{{ $row['image'] ?? asset('images/placeholder.png') }}" alt="">
            <div>
              <div style="font-weight:900">{{ $row['name'] ?? 'Producto' }}</div>
              <div class="muted">Cantidad: {{ $row['qty'] ?? 1 }}</div>
            </div>
            <div style="font-weight:900">${{ number_format(($row['price'] ?? 0) * ($row['qty'] ?? 1), 2) }}</div>
          </div>
        @empty
          <div class="muted">Tu carrito está vacío.</div>
        @endforelse

        {{-- Aviso: la paquetería se elige en el siguiente paso --}}
        <div style="margin-top:14px">
          <span class="chip">La paquetería se elige en el siguiente paso</span>
        </div>
      </div>
    </div>

    {{-- Sidebar --}}
    <aside class="card" aria-label="Resumen">
      <div class="card-b">
        <div class="sum-row"><span>Subtotal</span><span id="sum-subtotal">${{ number_format($subtotal,2) }}</span></div>
        <div class="sum-row"><span>Envío</span><span id="sum-shipping" class="muted">A elegir en el siguiente paso</span></div>
        <hr class="line">
        <div class="sum-row" style="font-size:1.12rem"><span>Total</span><span id="sum-total">${{ number_format($total,2) }}</span></div>
        <div class="muted" style="margin-top:6px;">Precios incluyen IVA</div>
        <hr class="line">
        <button id="btn-continue" class="btn btn-primary" disabled>Continuar</button>
      </div>
    </aside>
  </div>
</div>

{{-- ===== Modal Dirección ===== --}}
<div class="modal-back" id="addr-backdrop" aria-hidden="true"></div>
<section class="modal" id="addr-modal" role="dialog" aria-modal="true" aria-labelledby="addr-title">
  <div class="card-h" style="position:sticky;top:0;z-index:1">
    <h3 id="addr-title" style="margin:0;font-weight:1000;color:var(--brand-ink)">Nueva dirección</h3>
    <button class="btn btn-ghost" id="addr-close">Cerrar</button>
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
          <label for="entre_calles">Entre qué calle y qué calle (opcional)</label>
          <input id="entre_calles" name="entre_calles" type="text" placeholder="Ej. Av. Juárez y Hidalgo">
        </div>
        <div class="fi">
          <label for="referencias">Referencias (opcional)</label>
          <input id="referencias" name="referencias" type="text" placeholder="Fachada, color, portón, etc.">
        </div>
      </div>

      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
        <button type="button" class="btn btn-ghost" id="addr-cancel">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar dirección</button>
      </div>
    </form>
    <div id="addr-msg" class="muted" style="margin-top:10px;display:none"></div>
  </div>
</section>

{{-- ===== Modal ¿Requieres factura? ===== --}}
<div class="modal-back" id="inv-backdrop" aria-hidden="true"></div>
<section class="modal" id="inv-modal" role="dialog" aria-modal="true" aria-labelledby="inv-title">
  <div class="card-h" style="position:sticky;top:0;z-index:1">
    <h3 id="inv-title" style="margin:0;font-weight:1000">¿Requieres factura?</h3>
    <button class="btn btn-ghost" id="inv-close">Cerrar</button>
  </div>
  <div class="card-b">
    <p class="muted" style="margin-top:0">
      Si necesitas CFDI, capturaremos tus datos en el siguiente paso.
    </p>
    <div style="display:flex;flex-wrap:wrap;gap:10px">
      <button type="button" class="btn btn-ghost" id="btn-no-invoice">No, continuar sin factura</button>
      <button type="button" class="btn btn-primary" id="btn-yes-invoice">Sí, necesito factura</button>
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
    el.style.cssText = 'position:fixed;left:50%;bottom:24px;transform:translateX(-50%);background:var(--accent);color:#fff;padding:10px 14px;border-radius:12px;font-weight:900;box-shadow:0 10px 30px rgba(2,8,23,.25);z-index:80';
    document.body.appendChild(el);
    setTimeout(()=>el.remove(), 2200);
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
      <div class="card" style="border:1px dashed #c4d1ff;background:rgba(248,251,255,.85)" data-addr-card>
        <div class="card-b" style="display:flex;gap:12px;align-items:flex-start">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
            <path d="M12 22s8-4 8-10a8 8 0 10-16 0c0 6 8 10 8 10z" stroke="var(--brand)" stroke-width="2"/>
            <circle cx="12" cy="12" r="3" stroke="var(--brand)" stroke-width="2"/>
          </svg>
          <div>
            <strong>Entregar en:</strong>
            <div class="muted" style="margin-top:4px;white-space:pre-line">${lines.join('\n')}</div>
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
        a.alias ? `<strong>${a.alias}</strong>` : '<strong>Dirección</strong>',
        [a.calle || a.street, a.num_ext || a.ext_number].filter(Boolean).join(' '),
        [a.colonia || a.colony, a.cp || a.postal_code].filter(Boolean).join(', '),
        [a.municipio || a.municipality, a.estado || a.state].filter(Boolean).join(', ')
      ].filter(Boolean).join(' · ');

      const isDefault = !!a.is_default;
      const html = `
        <label class="addr-item" for="${idAttr}" data-id="${a.id ?? i}">
          <input type="radio" name="addrOption" id="${idAttr}" style="margin:0 4px 0 0">
          <div>
            <div>${label} ${isDefault ? '<span class="chip" style="margin-left:6px">Predeterminada</span>' : ''}</div>
            ${a.nombre_recibe || a.contact_name ? `<div class="muted">Contacto: ${a.nombre_recibe || a.contact_name}</div>` : ''}
          </div>
          <div class="muted" style="font-weight:900">Elegir</div>
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
