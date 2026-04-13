{{-- resources/views/checkout/invoice_select.blade.php --}}
@extends('layouts.web')
@section('title','Facturación')

@section('content')
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

  .wrap {
    max-width: 860px; /* Un poco más angosto para lectura cómoda */
    margin: clamp(32px, 5vw, 48px) auto;
    padding: 0 20px;
    box-sizing: border-box;
  }

  /* ====================== HERO & CARDS ====================== */
  .hero {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
    margin-bottom: 32px;
    flex-wrap: wrap;
  }
  .h-title {
    font-weight: 700;
    color: var(--ink);
    font-size: 1.8rem;
    margin: 0 0 8px 0;
  }
  .muted {
    color: var(--muted);
    font-weight: 500;
    line-height: 1.5;
  }
  .text-danger { color: var(--danger); font-size: 0.85rem; font-weight: 600; margin-top: 4px; display: block; }

  .card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    overflow: hidden;
  }
  .card-b {
    padding: 24px;
  }

  .info {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    background: var(--blue-soft);
    border: 1px solid var(--blue);
    padding: 20px;
    border-radius: 12px;
  }

  /* ====================== BUTTONS ====================== */
  .cta { display: flex; gap: 12px; flex-wrap: wrap; }
  .btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    border-radius: 8px; padding: 12px 20px;
    font-weight: 600; font-size: 0.95rem; font-family: inherit;
    text-decoration: none; cursor: pointer; border: none;
    transition: transform 0.15s ease, background 0.2s ease, box-shadow 0.2s ease;
  }
  .btn:active { transform: scale(0.98); }
  .btn-primary { background: var(--blue); color: #ffffff; }
  .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0, 122, 255, 0.15); }
  .btn-ghost { background: transparent; color: #555555; border: 1px solid var(--line); }
  .btn-ghost:hover { background: var(--bg); color: var(--ink); }

  /* ====================== MODALS ====================== */
  .modal-back {
    position: fixed; inset: 0; background: rgba(17, 17, 17, 0.45);
    z-index: 9998; display: none;
  }
  .modal {
    position: fixed; left: 50%; top: 50%; transform: translate(-50%, -50%);
    width: min(720px, 92vw); max-height: calc(100vh - 64px); overflow-y: auto;
    background: var(--card); border-radius: 16px; box-shadow: 0 24px 48px rgba(0,0,0,0.15);
    z-index: 9999; display: none;
  }
  .modal.open, .modal-back.open { display: block; }
  
  .m-h {
    padding: 20px 24px; border-bottom: 1px solid var(--line);
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; background: var(--card); z-index: 10;
  }
  .m-b { padding: 24px; }

  /* ====================== FORMS ====================== */
  .grid { display: grid; gap: 16px; grid-template-columns: 1fr 1fr; }
  @media(max-width: 640px){ .grid { grid-template-columns: 1fr; } }
  
  .fi { display: grid; gap: 8px; }
  .fi label { font-size: 0.85rem; color: var(--ink); font-weight: 600; }
  .fi input, .fi select {
    width: 100%; box-sizing: border-box;
    border: 1px solid var(--line); border-radius: 8px;
    padding: 12px 14px; font-size: 0.95rem; font-family: inherit; font-weight: 500;
    color: var(--ink); background: var(--card);
    transition: border-color 0.2s ease, box-shadow 0.2s ease; outline: none;
  }
  .fi input::placeholder { color: #a0a0a0; }
  .fi input:focus, .fi select:focus { border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft); }
  .fi select:disabled { background: var(--bg); color: var(--muted); cursor: not-allowed; }
  
  .fi select {
    appearance: none; -webkit-appearance: none; padding-right: 36px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23888888'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
    background-position: right 12px center; background-size: 16px; background-repeat: no-repeat;
  }

  .foot { display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; border-top: 1px solid var(--line); padding-top: 20px; }
  
  /* Details / Accordion */
  details summary { outline: none; color: var(--blue); font-weight: 700; cursor: pointer; margin-bottom: 8px; transition: color 0.2s; }
  details summary:hover { color: var(--ink); }
  details summary::marker { color: var(--muted); }

  .fade-in { animation: fade 0.3s ease-out; }
  @keyframes fade { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="wrap">
  <div class="hero fade-in">
    <div>
      <h1 class="h-title">¿Requieres factura?</h1>
      <div class="muted">Validaremos tu RFC y capturaremos tus datos fiscales (CFDI 4.0).</div>
    </div>
    <div class="cta">
      <a href="{{ route('checkout.shipping') }}" class="btn btn-ghost">Continuar sin factura</a>
      <button class="btn btn-primary" id="open-rfc">Sí, facturar</button>
    </div>
  </div>

  <div class="card fade-in">
    <div class="card-b">
      <div class="info">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2" style="margin-top: 2px;">
          <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
          <polyline points="13 2 13 9 20 9"></polyline>
        </svg>
        <div class="muted" style="color: var(--ink);">Usaremos tus datos para timbrar una factura CFDI 4.0 y enviarla al correo electrónico que nos indiques.</div>
      </div>
    </div>
  </div>
</div>

{{-- ===== Modal 1: Validar RFC ===== --}}
<div class="modal-back" id="rfc-back"></div>
<section class="modal" id="rfc-modal" role="dialog" aria-modal="true" aria-labelledby="rfc-title">
  <div class="m-h">
    <h3 id="rfc-title" style="margin:0; font-weight:700; font-size: 1.2rem; color: var(--ink);">Valida tu RFC</h3>
    <button class="btn btn-ghost" id="rfc-close" type="button" style="padding: 6px 12px; border:none;">Cerrar</button>
  </div>
  <div class="m-b">
    <form id="rfc-form" class="fi" autocomplete="off" novalidate>
      @csrf
      <label for="rfc">RFC (Personas físicas 13, morales 12)</label>
      <input id="rfc" name="rfc" maxlength="13" placeholder="Ej. ABCD001122XXX" value="{{ old('rfc') }}" required>
      <small id="rfc-help" class="muted" style="margin-top: 4px;">Se permite RFC genérico XAXX010101000 / XEXX010101000.</small>
      <div id="rfc-error" class="text-danger" style="display:none"></div>
      
      <div class="foot">
        <button type="button" class="btn btn-ghost" id="rfc-cancel" style="border:none;">Cancelar</button>
        <button type="submit" class="btn btn-primary">Validar y continuar</button>
      </div>
    </form>
  </div>
</section>

{{-- ===== Modal 2: Captura de datos fiscales ===== --}}
<div class="modal-back" id="inv-back"></div>
<section class="modal" id="inv-modal" role="dialog" aria-modal="true" aria-labelledby="inv-title">
  <div class="m-h">
    <h3 id="inv-title" style="margin:0; font-weight:700; font-size: 1.2rem; color: var(--ink);">Datos de facturación</h3>
    <button class="btn btn-ghost" id="inv-close" type="button" style="padding: 6px 12px; border:none;">Cerrar</button>
  </div>
  <div class="m-b">
    <form method="POST" action="{{ route('checkout.invoice.store') }}" id="invoice-form" autocomplete="off" novalidate>
      @csrf
      <input type="hidden" name="rfc" id="final-rfc" value="{{ old('rfc') }}">

      <div class="grid">
        <div class="fi" style="grid-column: 1 / -1;">
          <label>Razón social *</label>
          <input name="razon" value="{{ old('razon') }}" required>
          @error('razon')<small class="text-danger">{{ $message }}</small>@enderror
        </div>

        <div class="fi">
          <label>Régimen fiscal (SAT) *</label>
          <select name="regimen" required>
            <option value="" hidden>Selecciona tu régimen…</option>
            @foreach(($regimenOptions ?? []) as $code => $label)
              <option value="{{ $code }}" {{ old('regimen')===$code ? 'selected' : '' }}>
                {{ $code }} — {{ $label }}
              </option>
            @endforeach
          </select>
          @error('regimen')<small class="text-danger">{{ $message }}</small>@enderror
        </div>

        <div class="fi">
          <label>Uso CFDI *</label>
          <select name="uso_cfdi" required>
            <option value="" hidden>Selecciona el uso…</option>
            @foreach(($usoCfdiOptions ?? []) as $code => $label)
              <option value="{{ $code }}" {{ old('uso_cfdi')===$code ? 'selected' : '' }}>
                {{ $code }} — {{ $label }}
              </option>
            @endforeach
          </select>
          @error('uso_cfdi')<small class="text-danger">{{ $message }}</small>@enderror
        </div>

        <div class="fi">
          <label>Contacto (opcional)</label>
          <input name="contacto" value="{{ old('contacto') }}" placeholder="Nombre de quien tramita">
        </div>

        <div class="fi">
          <label>Teléfono (opcional)</label>
          <input name="telefono" value="{{ old('telefono') }}" placeholder="10 dígitos">
        </div>

        <div class="fi">
          <label>Código postal (fiscal) *</label>
          <input id="zip" name="zip" inputmode="numeric" maxlength="10" value="{{ old('zip') }}" required placeholder="Ej. 01000">
          @error('cp')<small class="text-danger">{{ $message }}</small>@enderror
        </div>

        <div class="fi">
          <label>Colonia (fiscal) *</label>
          <input list="colonies-list" id="colony" name="colonia" value="{{ old('colonia') }}" required>
          <datalist id="colonies-list"></datalist>
          @error('colonia')<small class="text-danger">{{ $message }}</small>@enderror
        </div>

        <div class="fi" style="grid-column: 1 / -1;">
          <label>Calle y número (fiscal) (opcional)</label>
          <input name="direccion" value="{{ old('direccion') }}" placeholder="Calle, No. Ext, No. Int">
          @error('direccion')<small class="text-danger">{{ $message }}</small>@enderror
        </div>

        <div class="fi">
          <label>Estado (fiscal) *</label>
          <input id="state" name="estado" value="{{ old('estado') }}" required>
          @error('estado')<small class="text-danger">{{ $message }}</small>@enderror
        </div>

        <div class="fi">
          <label>Municipio / Alcaldía (fiscal) (opcional)</label>
          <input id="municipality" name="municipality" value="{{ old('municipality') }}">
        </div>

        <div class="fi" style="grid-column: 1 / -1;">
          <label>Email para enviar la factura (opcional)</label>
          <input type="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" placeholder="ejemplo@empresa.com">
          @error('email')<small class="text-danger">{{ $message }}</small>@enderror
        </div>

        <div class="fi" style="grid-column: 1 / -1;">
          <label>Método de pago</label>
          <select name="payment_method" disabled>
            <option selected>Tarjeta de Crédito / Débito</option>
          </select>
        </div>
      </div>

      {{-- (Opcional) Guardar/actualizar dirección de envío --}}
      <details style="margin-top: 24px; padding: 16px; background: var(--bg); border: 1px solid var(--line); border-radius: 12px;">
        <summary>¿Guardar también una dirección de entrega?</summary>
        <div class="grid" style="margin-top: 16px;">
          <div class="fi">
            <label>Contacto (quien recibe)</label>
            <input id="ship_contact_name" placeholder="Nombre de contacto" value="">
          </div>
          <div class="fi">
            <label>Teléfono</label>
            <input id="ship_phone" type="tel" placeholder="Celular o fijo" value="">
          </div>

          <div class="fi" style="grid-column: 1 / -1;">
            <label>Calle</label>
            <input id="ship_street" placeholder="Nombre de la calle" value="">
          </div>

          <div class="fi">
            <label>No. exterior</label>
            <input id="ship_ext" placeholder="Ej. 123" value="">
          </div>
          <div class="fi">
            <label>No. interior (opcional)</label>
            <input id="ship_int" placeholder="Ej. B" value="">
          </div>

          <div class="fi">
            <label>Colonia</label>
            <input id="ship_colony" placeholder="Colonia" value="">
          </div>
          <div class="fi">
            <label>Municipio / Alcaldía</label>
            <input id="ship_mun" placeholder="Municipio" value="">
          </div>

          <div class="fi">
            <label>Estado</label>
            <input id="ship_state" placeholder="Estado" value="">
          </div>
          <div class="fi">
            <label>C.P.</label>
            <input id="ship_cp" inputmode="numeric" maxlength="10" placeholder="Código postal" value="">
          </div>
        </div>
      </details>

      <div class="foot">
        <a class="btn btn-ghost" href="{{ route('checkout.shipping') }}" style="border:none;">Omitir ahora</a>
        <button class="btn btn-primary" type="submit" id="btn-save">
          <span id="btn-text">Guardar y continuar</span>
          <span id="btn-spin" style="display:none">Procesando…</span>
        </button>
      </div>
    </form>
  </div>
</section>

@push('scripts')
<script>
(function(){
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  // Rutas backend
  const ROUTE_CP   = @json(route('checkout.cp'));            // GET ?cp=XXXXX 
  const ROUTE_ADDR = @json(route('checkout.address.store')); // POST JSON

  // ===== RFC Modal logic
  const rfcBtn   = document.getElementById('open-rfc');
  const rfcModal = document.getElementById('rfc-modal');
  const rfcBack  = document.getElementById('rfc-back');
  const rfcClose = document.getElementById('rfc-close');
  const rfcCancel= document.getElementById('rfc-cancel');
  const rfcForm  = document.getElementById('rfc-form');
  const rfcInput = document.getElementById('rfc');
  const finalRFC = document.getElementById('final-rfc');
  const rfcHelp  = document.getElementById('rfc-help');
  const rfcErr   = document.getElementById('rfc-error');

  function openRFC(){ rfcModal.classList.add('open'); rfcBack.classList.add('open'); setTimeout(()=>rfcInput?.focus(),50); }
  function closeRFC(){ rfcModal.classList.remove('open'); rfcBack.classList.remove('open'); }

  rfcBtn?.addEventListener('click', openRFC);
  rfcClose?.addEventListener('click', closeRFC);
  rfcCancel?.addEventListener('click', closeRFC);
  rfcBack?.addEventListener('click', closeRFC);

  function validaRFC(value){
    const v = (value||'').toUpperCase().trim();
    if (v === 'XAXX010101000' || v === 'XEXX010101000') return true;
    return /^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/.test(v);
  }

  rfcForm?.addEventListener('submit', (e)=>{
    e.preventDefault();
    const v = (rfcInput.value||'').toUpperCase().trim();
    if(!validaRFC(v)){
      rfcErr.textContent = 'RFC inválido. Revisa el formato (12/13 caracteres).';
      rfcErr.style.display = 'block';
      rfcHelp.classList.add('text-danger');
      return;
    }
    rfcErr.style.display = 'none';
    rfcHelp.classList.remove('text-danger');
    finalRFC.value = v;
    closeRFC();
    openINV();
  });

  // ===== Invoice Modal logic
  const invModal = document.getElementById('inv-modal');
  const invBack  = document.getElementById('inv-back');
  const invClose = document.getElementById('inv-close');

  function openINV(){ invModal.classList.add('open'); invBack.classList.add('open'); }
  function closeINV(){ invModal.classList.remove('open'); invBack.classList.remove('open'); }

  invClose?.addEventListener('click', closeINV);
  invBack?.addEventListener('click', closeINV);
  document.addEventListener('keydown', e => { if(e.key === 'Escape'){ closeRFC(); closeINV(); } });

  // ===== CP lookup para datos fiscales
  const zip = document.getElementById('zip');
  const colony = document.getElementById('colony');
  const datalist = document.getElementById('colonies-list');
  const state = document.getElementById('state');
  const municipality = document.getElementById('municipality');

  async function lookupCP(cp){
    state.value=''; municipality.value=''; datalist.innerHTML='';
    if(!/^\d{5}$/.test(cp||'')) return;
    try{
      const res = await fetch(`${ROUTE_CP}?cp=${encodeURIComponent(cp)}`, { headers:{'Accept':'application/json'} });
      if(!res.ok) throw new Error('CP lookup failed');
      const data = await res.json();
      if(data.state){ state.value = data.state; }
      if(data.municipality){ municipality.value = data.municipality; }
      const cols = Array.isArray(data.colonies)? data.colonies : [];
      datalist.innerHTML = cols.map(c=>`<option value="${c}">`).join('');
      if(!colony.value && cols[0]) colony.value = cols[0];
    }catch(e){
      console.warn('CP lookup error', e);
    }
  }
  zip?.addEventListener('input', e=>{ if((e.target.value||'').length===5) lookupCP(e.target.value); });

  // ===== Guardar dirección de envío por AJAX
  const form = document.getElementById('invoice-form');
  const btn  = document.getElementById('btn-save');
  const t    = document.getElementById('btn-text');
  const s    = document.getElementById('btn-spin');

  form?.addEventListener('submit', ()=>{
    btn.disabled = true; t.style.display='none'; s.style.display='inline';

    const payload = {
      contact_name: (document.getElementById('ship_contact_name')?.value || '').trim(),
      phone:        (document.getElementById('ship_phone')?.value || '').trim(),
      street:       (document.getElementById('ship_street')?.value || '').trim(),
      ext_number:   (document.getElementById('ship_ext')?.value || '').trim(),
      int_number:   (document.getElementById('ship_int')?.value || '').trim(),
      colony:       (document.getElementById('ship_colony')?.value || '').trim(),
      municipality: (document.getElementById('ship_mun')?.value || '').trim(),
      state:        (document.getElementById('ship_state')?.value || '').trim(),
      postal_code:  (document.getElementById('ship_cp')?.value || '').trim(),
    };
    
    const hasAny = Object.values(payload).some(v => (v||'').length>0);
    if(!hasAny) return;

    try{
      fetch(ROUTE_ADDR, {
        method:'POST',
        headers:{'X-CSRF-TOKEN': csrf, 'Accept':'application/json', 'Content-Type':'application/json'},
        body: JSON.stringify(payload),
        keepalive: true
      }).catch(()=>{});
    }catch(e){}
  });

  // Si ya venía RFC precargado, abre directo la captura
  if(finalRFC.value){ openINV(); }
})();
</script>
@endpush
@endsection