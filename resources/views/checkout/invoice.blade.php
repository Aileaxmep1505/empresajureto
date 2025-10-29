{{-- resources/views/checkout/invoice_select.blade.php --}}
@extends('layouts.web')
@section('title','Facturación')

@section('content')
<style>
  :root{
    --ink:#0b1220; --muted:#667085; --line:#eceff4; --brand:#1f4cf0; --accent:#10b981; --bg:#ffffff;
  }
  html,body{background:var(--bg)}
  .wrap{max-width:980px;margin:24px auto;padding:0 16px}
  .hero{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:14px}
  .h-title{font-weight:900;color:var(--ink);letter-spacing:-.01em;margin:0}
  .card{background:#fff;border:1px solid var(--line);border-radius:18px;box-shadow:0 10px 30px rgba(9,16,29,.06);overflow:hidden}
  .card-b{padding:18px 20px}
  .muted{color:var(--muted)}
  .btn{display:inline-flex;align-items:center;gap:8px;border-radius:12px;padding:10px 16px;
       border:1px solid #dfe6ee;background:#fff;font-weight:800;cursor:pointer;transition:transform .06s ease, box-shadow .2s ease}
  .btn:hover{box-shadow:0 6px 18px rgba(15,23,42,.08)}
  .btn:active{transform:translateY(1px)}
  .btn-primary{background:var(--brand);border-color:var(--brand);color:#fff}
  .btn-ghost{background:#fff}
  .cta{display:flex;gap:10px;flex-wrap:wrap}
  .info{display:flex;align-items:center;gap:10px;background:#f6f8ff;border:1px dashed #cdd7ff;padding:12px;border-radius:12px}

  /* Modal */
  .modal-back{position:fixed;inset:0;background:rgba(15,23,42,.42);backdrop-filter:blur(2px);display:none;z-index:60}
  .modal{position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);width:min(720px,94vw);max-height:90vh;overflow:auto;background:#fff;border:1px solid var(--line);border-radius:18px;display:none;z-index:61;box-shadow:0 30px 80px rgba(2,8,23,.28)}
  .modal.open,.modal-back.open{display:block}
  .m-h{padding:16px 18px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between}
  .m-b{padding:16px 18px}
  .grid{display:grid;gap:12px;grid-template-columns:1fr 1fr}
  @media(max-width:880px){ .grid{grid-template-columns:1fr} }
  .fi{display:grid;gap:6px}
  .fi label{font-size:.92rem;color:#22304a;font-weight:800}
  .fi input,.fi select{
    border:1px solid #dfe6ee;background:#fff;border-radius:12px;padding:12px 12px;font-size:1rem;
    transition:border-color .18s ease, box-shadow .18s ease;
  }
  .fi input:focus,.fi select:focus{outline:0;border-color:var(--brand);box-shadow:0 0 0 4px rgba(31,76,240,.08)}
  .foot{display:flex;gap:10px;justify-content:flex-end;margin-top:12px}
  .fade-in{animation:fade .25s ease-out}
  @keyframes fade{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
  small.text-danger{color:#b42318}
</style>

<div class="wrap">
  <div class="hero">
    <div>
      <h1 class="h-title">¿Requieres factura?</h1>
      <div class="muted">Validaremos tu RFC y después capturaremos tus datos fiscales (CFDI 4.0).</div>
    </div>
    <div class="cta">
      <a href="{{ route('checkout.shipping') }}" class="btn">Continuar sin factura</a>
      <button class="btn btn-primary" id="open-rfc">Sí, facturar</button>
    </div>
  </div>

  <div class="card fade-in">
    <div class="card-b">
      <div class="info">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="#1f4cf0" stroke-width="2"/><path d="M12 8v6" stroke="#1f4cf0" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="16.5" r="1" fill="#1f4cf0"/></svg>
        <div class="muted">Usaremos tus datos para timbrar una factura CFDI 4.0 y enviarla al correo que indiques.</div>
      </div>
    </div>
  </div>
</div>

{{-- ===== Modal 1: Validar RFC ===== --}}
<div class="modal-back" id="rfc-back"></div>
<section class="modal" id="rfc-modal" role="dialog" aria-modal="true" aria-labelledby="rfc-title">
  <div class="m-h">
    <h3 id="rfc-title" style="margin:0;font-weight:900">Valida tu RFC</h3>
    <button class="btn" id="rfc-close" type="button">Cerrar</button>
  </div>
  <div class="m-b">
    <form id="rfc-form" class="fi" autocomplete="off" novalidate>
      @csrf
      <label for="rfc">RFC (personas físicas 13, morales 12)</label>
      <input id="rfc" name="rfc" maxlength="13" placeholder="Ej. ABCD001122XXX" value="{{ old('rfc') }}" required>
      <small id="rfc-help" class="muted">Se permite RFC genérico XAXX010101000 / XEXX010101000.</small>
      <div id="rfc-error" class="text-danger" style="display:none"></div>
      <div class="foot">
        <button type="button" class="btn" id="rfc-cancel">Cancelar</button>
        <button type="submit" class="btn btn-primary">Validar y continuar</button>
      </div>
    </form>
  </div>
</section>

{{-- ===== Modal 2: Captura de datos fiscales ===== --}}
<div class="modal-back" id="inv-back"></div>
<section class="modal" id="inv-modal" role="dialog" aria-modal="true" aria-labelledby="inv-title">
  <div class="m-h">
    <h3 id="inv-title" style="margin:0;font-weight:900">Datos de facturación</h3>
    <button class="btn" id="inv-close" type="button">Cerrar</button>
  </div>
  <div class="m-b">
    <form method="POST" action="{{ route('checkout.invoice.store') }}" id="invoice-form" autocomplete="off" novalidate>
      @csrf
      <input type="hidden" name="rfc" id="final-rfc" value="{{ old('rfc') }}">

      <div class="grid">
        <div class="fi" style="grid-column:span 2">
          <label>Razón social *</label>
          {{-- IMPORTANTE: name="razon" para coincidir con el controlador --}}
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
            <option value="" hidden>Selecciona el uso del CFDI…</option>
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
          {{-- coincide con validador: "contacto" --}}
          <input name="contacto" value="{{ old('contacto') }}">
        </div>

        <div class="fi">
          <label>Teléfono (opcional)</label>
          {{-- coincide con validador: "telefono" --}}
          <input name="telefono" value="{{ old('telefono') }}">
        </div>

        <div class="fi">
          <label>Código postal (fiscal) *</label>
          {{-- puedes enviar "zip"; el backend lo normaliza a "cp" --}}
          <input id="zip" name="zip" inputmode="numeric" maxlength="10" value="{{ old('zip') }}" required>
          @error('cp')<small class="text-danger">{{ $message }}</small>@enderror
        </div>

        <div class="fi">
          <label>Colonia (fiscal) *</label>
          {{-- coincide con validador: "colonia" --}}
          <input list="colonies-list" id="colony" name="colonia" value="{{ old('colonia') }}" required>
          <datalist id="colonies-list"></datalist>
          @error('colonia')<small class="text-danger">{{ $message }}</small>@enderror
        </div>

        <div class="fi" style="grid-column:span 2">
          <label>Calle y número (fiscal) (opcional)</label>
          {{-- coincide con validador: "direccion" (una sola línea) --}}
          <input name="direccion" value="{{ old('direccion') }}" placeholder="Calle, No. Ext, No. Int">
          @error('direccion')<small class="text-danger">{{ $message }}</small>@enderror
        </div>

        <div class="fi">
          <label>Estado (fiscal) *</label>
          {{-- coincide con validador: "estado" --}}
          <input id="state" name="estado" value="{{ old('estado') }}" required>
          @error('estado')<small class="text-danger">{{ $message }}</small>@enderror
        </div>

        <div class="fi">
          <label>Municipio / Alcaldía (fiscal) (opcional)</label>
          {{-- no es obligatorio en el validador del perfil --}}
          <input id="municipality" name="municipality" value="{{ old('municipality') }}">
        </div>

        <div class="fi" style="grid-column:span 2">
          <label>Email para enviar la factura (opcional)</label>
          <input type="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}">
          @error('email')<small class="text-danger">{{ $message }}</small>@enderror
        </div>

        <div class="fi">
          <label>Método de pago</label>
          <select name="payment_method" disabled>
            <option selected>Tarjeta</option>
          </select>
        </div>
      </div>

      {{-- (Opcional) Guardar/actualizar una dirección de envío por AJAX antes de enviar el perfil fiscal --}}
      <details style="margin-top:14px">
        <summary class="muted" style="cursor:pointer;font-weight:700">¿Guardar también una dirección de entrega?</summary>
        <div class="grid" style="margin-top:12px">
          <div class="fi">
            <label>Contacto (quien recibe)</label>
            <input id="ship_contact_name" placeholder="Nombre de contacto" value="">
          </div>
          <div class="fi">
            <label>Teléfono</label>
            <input id="ship_phone" type="tel" placeholder="Celular o fijo" value="">
          </div>

          <div class="fi" style="grid-column:span 2">
            <label>Calle</label>
            <input id="ship_street" placeholder="Calle" value="">
          </div>

          <div class="fi">
            <label>No. exterior</label>
            <input id="ship_ext" placeholder="No. exterior" value="">
          </div>
          <div class="fi">
            <label>No. interior (opcional)</label>
            <input id="ship_int" placeholder="No. interior" value="">
          </div>

          <div class="fi">
            <label>Colonia</label>
            <input id="ship_colony" placeholder="Colonia" value="">
          </div>
          <div class="fi">
            <label>Municipio / Alcaldía</label>
            <input id="ship_mun" placeholder="Municipio o alcaldía" value="">
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
        <a class="btn" href="{{ route('checkout.shipping') }}">Omitir ahora</a>
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
  const ROUTE_CP   = @json(route('checkout.cp'));            // GET ?cp=XXXXX (autocompleta colonia/estado/municipio)
  const ROUTE_ADDR = @json(route('checkout.address.store')); // POST JSON (opcional) para dirección de envío

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
    if (v === 'XAXX010101000' || v === 'XEXX010101000') return true; // genérico
    // 12 (moral) o 13 (física) alfanumérico
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
    openINV(); // abre el segundo modal
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

  // ===== CP lookup (colonias/estado/municipio) para datos fiscales
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

  // ===== Guardar dirección de envío por AJAX (opcional, no bloqueante)
  const form = document.getElementById('invoice-form');
  const btn  = document.getElementById('btn-save');
  const t    = document.getElementById('btn-text');
  const s    = document.getElementById('btn-spin');

  form?.addEventListener('submit', ()=>{
    btn.disabled = true; t.style.display='none'; s.style.display='inline';

    // Si el usuario llenó algo en el bloque de envío, intentamos guardarlo con keepalive
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
    if(!hasAny) return; // nada que guardar

    try{
      fetch(ROUTE_ADDR, {
        method:'POST',
        headers:{'X-CSRF-TOKEN': csrf, 'Accept':'application/json', 'Content-Type':'application/json'},
        body: JSON.stringify(payload),
        keepalive: true // permite que se envíe incluso si navegamos al siguiente paso
      }).catch(()=>{});
    }catch(e){/* noop */}
  });

  // Si ya venía RFC precargado (old), abre directo captura
  if(finalRFC.value){ openINV(); }
})();
</script>
@endpush
@endsection
