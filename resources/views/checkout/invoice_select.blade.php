{{-- resources/views/checkout/invoice_select.blade.php --}}
@extends('layouts.web')
@section('title','Datos de facturación')

@section('content')
<style>
  :root{
    --bg:#ffffff; --card:#ffffff; --ink:#0b1220; --ink-soft:#22304a;
    --muted:#667085; --line:#eceff4; --brand:#1f4cf0; --ring: rgba(31,76,240,.10);
    --chip:#f3f6ff;
  }
  html,body{background:var(--bg); overflow-x:hidden;}
  .wrap{max-width:980px;margin:24px auto;padding:0 16px}
  .card{background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:0 12px 32px rgba(2,8,23,.06)}
  .card-head{padding:18px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:12px}
  .card-body{padding:18px 20px}
  .page-title{margin:0;font-weight:900;color:var(--ink);letter-spacing:-.01em}
  .muted{color:var(--muted)}
  .badges{display:flex;flex-wrap:wrap;gap:8px}
  .badge{background:var(--chip);color:#274194;border:1px solid #dbe3ff;padding:6px 10px;border-radius:999px;font-weight:700;font-size:.85rem}
  .btn{display:inline-flex;align-items:center;gap:8px;border-radius:12px;padding:10px 16px;border:1px solid #dfe6ee;background:#fff;font-weight:800;color:#0b1220;cursor:pointer;transition:transform .08s ease, box-shadow .2s ease}
  .btn:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(15,23,42,.08)}
  .btn-primary{background:var(--brand);border-color:var(--brand);color:#fff}
  .btn-outline{background:#fff;border-color:#dfe6ee;color:#0b1220}
  .fi{display:grid;gap:6px}
  .fi label{font-size:.95rem;color:var(--ink-soft);font-weight:800}
  .input,.select{border:1px solid #dfe6ee;border-radius:12px;padding:.7rem .9rem;font-size:1rem;background:#fff;transition:border-color .18s ease, box-shadow .18s ease; width:100%}
  .input:focus,.select:focus{border-color:var(--brand);box-shadow:0 0 0 6px var(--ring);outline:0}
  .text-error{color:#b42318;font-size:.9rem}
  .row{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
  .modal-backdrop{position:fixed;inset:0;background:rgba(2,8,23,.45);backdrop-filter:blur(2px);opacity:0;pointer-events:none;transition:opacity .2s ease;z-index:70}
  .modal-backdrop.open{opacity:1;pointer-events:auto}
  .modal{position:fixed;left:50%;top:50%;transform:translate(-50%,-42%) scale(.98);width:min(720px,94vw);max-height:90vh;overflow:auto;background:#fff;border-radius:16px;border:1px solid var(--line);box-shadow:0 30px 80px rgba(2,8,23,.25);opacity:0;pointer-events:none;transition:opacity .2s ease, transform .2s ease;z-index:71}
  .modal.open{opacity:1;pointer-events:auto;transform:translate(-50%,-50%) scale(1)}
  .modal-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 16px;border-bottom:1px solid var(--line)}
  .modal-body{padding:16px}
  .close-x{appearance:none;border:0;background:transparent;cursor:pointer;font-size:20px;line-height:1}
  .steps{display:flex;align-items:center;gap:10px}
  .dot{width:26px;height:26px;border-radius:50%;border:2px solid var(--brand);display:inline-flex;align-items:center;justify-content:center;font-weight:800;color:#1f4cf0;font-size:.86rem}
  .dot.active{background:var(--brand);color:#fff}
  .sep{color:#9aa4b2}
  .grid{display:grid;gap:14px}
  .g2{grid-template-columns:1fr 1fr}
  @media (max-width: 860px){ .g2{grid-template-columns:1fr} }
  .footer-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:12px;flex-wrap:wrap}
  .fade-in{animation:fade .25s ease-out}
  @keyframes fade{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
  .pill{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;font-weight:800;border:1px solid #e5e7eb;background:#f9fafb}
</style>

<div class="wrap">
  <div class="card fade-in">
    <div class="card-head">
      <div>
        <h1 class="page-title">Datos de facturación</h1>
        <div class="muted" style="margin-top:4px">Se guardarán como <strong>perfil predeterminado</strong> para futuras compras.</div>
      </div>
      <a href="{{ route('checkout.shipping') }}" class="btn btn-outline">Omitir ahora</a>
    </div>

    <div class="card-body">
      @if ($errors->any())
        <div class="text-error" style="margin-bottom:12px">
          <strong>Corrige estos campos:</strong>
          <ul style="margin:6px 0 0 18px">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="row">
        <div class="badges">
          <span class="badge">Validación de RFC</span>
          <span class="badge">CFDI 4.0</span>
          <span class="badge">Autocompleta C.P.</span>
          <span class="badge">Pago: Tarjeta</span>
        </div>
        <button id="open-modal" class="btn btn-primary">Capturar datos</button>
      </div>
    </div>
  </div>
</div>

{{-- Modal 2 pasos --}}
<div id="backdrop" class="modal-backdrop"></div>
<section id="invoice-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="inv-title" aria-describedby="inv-desc">
  <header class="modal-head">
    <div class="steps">
      <span id="dot1" class="dot active">1</span><strong>RFC</strong>
      <span class="sep">—</span>
      <span id="dot2" class="dot">2</span><strong>Datos</strong>
    </div>
    <button id="close-modal" class="close-x" aria-label="Cerrar">×</button>
  </header>

  <div class="modal-body">
    {{-- Paso 1: RFC --}}
    <div id="step1" class="grid">
      <div class="fi">
        <label for="rfc">RFC *</label>
        <input id="rfc" class="input" maxlength="13" placeholder="Ej. ABCD800101XXX" value="{{ old('rfc') }}">
        <small class="muted">PM: 12 · PF: 13 · Se permite XAXX/XEXX (genérico).</small>
        <div id="rfc-error" class="text-error" style="display:none"></div>
      </div>
      <div class="footer-actions">
        <button class="btn btn-outline" id="cancel-1" type="button">Cancelar</button>
        <button class="btn btn-primary" id="btn-next" type="button">Validar y continuar</button>
      </div>
    </div>

    {{-- Paso 2: Datos completos --}}
    <div id="step2" class="grid" style="display:none">
      <form id="invoice-form" method="POST" action="{{ route('checkout.invoice.store') }}" autocomplete="off" novalidate>
        @csrf
        <input type="hidden" name="rfc" id="rfc_final" value="{{ old('rfc') }}">
        {{-- NUEVO: dirección consolidada que espera el controlador --}}
        <input type="hidden" name="direccion" id="direccion">

        <div class="grid">
          <div class="fi">
            <label>Razón social *</label>
            <input name="razon" class="input" value="{{ old('razon') }}" required>
          </div>
        </div>

        <div class="grid g2">
          <div class="fi">
            <label>C.P. fiscal *</label>
            <input id="zip" name="zip" class="input" inputmode="numeric" maxlength="10" value="{{ old('zip') }}" required>
            <small class="muted">Autocompleta Estado / Municipio / Colonia</small>
          </div>
          <div class="fi">
            <label>Email para factura (opcional)</label>
            <input type="email" name="email" class="input" value="{{ old('email') }}">
          </div>
        </div>

        {{-- ===== Régimen y Uso (filtrados por tipo de persona) ===== --}}
        <div class="grid g2">
          <div class="fi">
            <label>Régimen fiscal (SAT) *</label>
            <select name="regimen" id="regimen" class="select" required></select>
            <small class="muted" id="hint-persona" style="display:block;margin-top:4px"></small>
          </div>

          <div class="fi">
            <label>Uso CFDI *</label>
            <select name="uso_cfdi" id="uso_cfdi" class="select" required></select>
            <small class="muted">Opciones mostradas según persona física/moral.</small>
          </div>
        </div>

        {{-- CONTACTO / ENTREGA (opcional para el perfil) --}}
        <div class="grid g2">
          <div class="fi">
            <label>Contacto (quien recibe)</label>
            <input name="contacto" id="contact_name" class="input" placeholder="Nombre de contacto" value="{{ old('contacto') }}">
          </div>
          <div class="fi">
            <label>Teléfono</label>
            <input name="telefono" id="phone" type="tel" class="input" placeholder="Celular o fijo" value="{{ old('telefono') }}">
          </div>
        </div>

        <div class="grid">
          <div class="fi">
            <label>Calle *</label>
            <input id="street" class="input" placeholder="Calle" value="">
          </div>
        </div>

        <div class="grid g2">
          <div class="fi">
            <label>No. exterior *</label>
            <input id="ext_number" class="input" placeholder="No. exterior" value="">
          </div>
          <div class="fi">
            <label>No. interior (opcional)</label>
            <input id="int_number" class="input" placeholder="No. interior" value="">
          </div>
        </div>

        <div class="grid g2">
          <div class="fi">
            <label>Colonia *</label>
            <input list="colonies-list" id="colony" name="colonia" class="input" placeholder="Colonia" value="{{ old('colonia') }}">
            <datalist id="colonies-list"></datalist>
          </div>
          <div class="fi">
            <label>Municipio / Alcaldía *</label>
            <input id="municipality" class="input" placeholder="Municipio o alcaldía" value="">
          </div>
        </div>

        <div class="grid g2">
          <div class="fi">
            <label>Estado *</label>
            <input id="state" name="estado" class="input" placeholder="Estado" value="{{ old('estado') }}">
          </div>
          <div class="fi">
            <label>Método de pago</label>
            <select class="select" disabled>
              <option>Tarjeta</option>
            </select>
          </div>
        </div>

        <div class="footer-actions">
          <button type="button" class="btn btn-outline" id="btn-back">Atrás</button>
          <button class="btn btn-primary" type="submit" id="btn-save">
            <span id="btn-text">Guardar y continuar</span>
            <span id="btn-spin" style="display:none">Procesando…</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</section>

@push('scripts')
<script>
(function(){
  const $ = s => document.querySelector(s);
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  // Rutas backend
  const ROUTE_CP   = @json(route('checkout.cp'));            // GET ?cp=XXXXX
  const ROUTE_ADDR = @json(route('checkout.address.store')); // POST JSON

  // Modal
  const backdrop = $('#backdrop');
  const modal = $('#invoice-modal');
  const openBtn = $('#open-modal');
  const closeBtn = $('#close-modal');
  const cancel1 = $('#cancel-1');

  function openModal(){
    backdrop.classList.add('open');
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
    $('#rfc')?.focus();
  }
  function closeModal(){
    backdrop.classList.remove('open');
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }
  openBtn?.addEventListener('click', openModal);
  closeBtn?.addEventListener('click', closeModal);
  cancel1?.addEventListener('click', closeModal);
  backdrop?.addEventListener('click', e => { if(e.target === backdrop) closeModal(); });
  document.addEventListener('keydown', e => { if(e.key === 'Escape') closeModal(); });

  // Steps
  const step1 = $('#step1'), step2 = $('#step2');
  const dot1 = $('#dot1'), dot2 = $('#dot2');
  function go1(){ step1.style.display='grid'; step2.style.display='none'; dot1.classList.add('active'); dot2.classList.remove('active'); }
  function go2(){ step1.style.display='none'; step2.style.display='grid'; dot1.classList.remove('active'); dot2.classList.add('active'); }

  // ===== Catálogos (filtrado por persona) =====
  const REGIMEN_PM = {
    '601':'General de Ley Personas Morales',
    '603':'Personas Morales con Fines no Lucrativos',
    '620':'Sociedades Cooperativas de Producción que optan por diferir sus ingresos',
    '623':'Opcional para Grupos de Sociedades',
    '624':'Coordinados',
  };
  const REGIMEN_PF = {
    '606':'Arrendamiento',
    '607':'Régimen de Enajenación o Adquisición de Bienes',
    '608':'Demás ingresos',
    '611':'Ingresos por Dividendos (socios y accionistas)',
    '612':'PF con Actividades Empresariales y Profesionales',
    '614':'Ingresos por intereses',
    '615':'Ingresos por Obtención de Premios',
    '621':'Incorporación Fiscal',
    '625':'AE con ingresos por Plataformas Tecnológicas',
    '626':'Régimen Simplificado de Confianza',
  };
  const REGIMEN_GEN = {'616':'Sin obligaciones fiscales'};

  const USO_BASE = {
    'G01':'Adquisición de mercancías',
    'G02':'Devoluciones, descuentos o bonificaciones',
    'G03':'Gastos en general',
    'I01':'Construcciones',
    'I02':'Mobiliario y equipo de oficina por inversiones',
    'I03':'Equipo de transporte',
    'I04':'Equipo de computo y accesorios',
    'I05':'Dados, troqueles, moldes, matrices y herramental',
    'I06':'Comunicaciones telefónicas',
    'I07':'Comunicaciones satelitales',
    'I08':'Otra maquinaria y equipo',
    'CP01':'Pagos',
    'S01':'Sin efectos fiscales',
  };
  const USO_D_PF = {
    'D01':'Honorarios médicos, dentales y gastos hospitalarios',
    'D02':'Gastos médicos por incapacidad o discapacidad',
    'D03':'Gastos funerales',
    'D04':'Donativos',
    'D05':'Intereses reales por créditos hipotecarios (casa habitación)',
    'D06':'Aportaciones voluntarias al SAR',
    'D07':'Primas por seguros de gastos médicos',
    'D08':'Gastos de transportación escolar obligatoria',
    'D09':'Depósitos para el ahorro, planes de pensiones',
    'D10':'Pagos por servicios educativos (colegiaturas)',
  };
  const USO_PM = USO_BASE; // sin Dxx
  const USO_PF = Object.assign({}, USO_BASE, USO_D_PF);
  const USO_GEN = {'S01':'Sin efectos fiscales'};

  const elRegimen = $('#regimen');
  const elUso    = $('#uso_cfdi');
  const elHint   = $('#hint-persona');

  function clearOptions(sel){
    sel.innerHTML = '<option value="" hidden>Selecciona…</option>';
    sel.disabled = false;
  }
  function fillOptions(sel, map, selected){
    clearOptions(sel);
    Object.entries(map).forEach(([k,v])=>{
      const opt = document.createElement('option');
      opt.value = k; opt.textContent = `${k} — ${v}`;
      if (selected && String(selected) === String(k)) opt.selected = true;
      sel.appendChild(opt);
    });
  }
  function detectPersona(rfc){
    const R = String(rfc||'').toUpperCase().trim();
    if (/^XAXX010101000$/.test(R) || /^XEXX010101000$/.test(R)) return 'GEN';
    if (/^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$/.test(R)) return 'PM';  // 12
    if (/^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/.test(R)) return 'PF';  // 13
    return null;
  }
  function renderRegimenUsoByRFC(rfc){
    const tipo = detectPersona(rfc);
    const oldReg = @json(old('regimen',''));
    const oldUso = @json(old('uso_cfdi',''));

    if (tipo === 'PM') {
      fillOptions(elRegimen, REGIMEN_PM, oldReg || '601');
      fillOptions(elUso, USO_PM, oldUso || 'G03');
      elRegimen.disabled = false; elUso.disabled = false;
      elHint.textContent = 'Detectado: Persona Moral (12 caracteres).';
    } else if (tipo === 'PF') {
      fillOptions(elRegimen, REGIMEN_PF, oldReg || '612');
      fillOptions(elUso, USO_PF, oldUso || 'G03');
      elRegimen.disabled = false; elUso.disabled = false;
      elHint.textContent = 'Detectado: Persona Física (13 caracteres).';
    } else if (tipo === 'GEN') {
      fillOptions(elRegimen, REGIMEN_GEN, '616');
      fillOptions(elUso, USO_GEN, 'S01');
      elRegimen.disabled = true; elUso.disabled = true;
      elHint.textContent = 'Detectado: Público en general / Extranjero genérico.';
    } else {
      clearOptions(elRegimen); clearOptions(elUso);
      elHint.textContent = '';
    }
  }

  // Validación RFC
  const rfcInput = $('#rfc');
  const rfcErr   = $('#rfc-error');
  const rfcFinal = $('#rfc_final');

  function isValidRFC(rfc){
    rfc = String(rfc||'').trim().toUpperCase();
    const rePM = /^[A-Z&Ñ]{3}\d{6}[A-Z0-9]{3}$/; // 12
    const rePF = /^[A-Z&Ñ]{4}\d{6}[A-Z0-9]{3}$/; // 13
    if(/^XAXX010101000$/.test(rfc) || /^XEXX010101000$/.test(rfc)) return true;
    return rePM.test(rfc) || rePF.test(rfc);
  }

  $('#btn-next')?.addEventListener('click', ()=>{
    const rfc = rfcInput.value;
    if(!isValidRFC(rfc)){
      rfcErr.textContent = 'RFC inválido. Verifica longitud y formato.';
      rfcErr.style.display = 'block';
      return;
    }
    rfcErr.style.display = 'none';
    rfcFinal.value = rfc.toUpperCase();
    renderRegimenUsoByRFC(rfc);
    go2();
  });

  $('#btn-back')?.addEventListener('click', go1);

  // CP Lookup
  const zip = $('#zip'), state = $('#state'), municipality = $('#municipality'), colony = $('#colony');
  const datalist = $('#colonies-list');

  async function lookupCP(code){
    state.value=''; municipality.value=''; datalist.innerHTML='';
    if(!/^\d{5}$/.test(code||'')) return;
    try{
      const res = await fetch(`${ROUTE_CP}?cp=${encodeURIComponent(code)}`, { headers:{'Accept':'application/json'} });
      if(!res.ok) throw new Error();
      const data = await res.json();
      if(data.state) state.value = data.state;
      if(data.municipality) municipality.value = data.municipality;
      const cols = Array.isArray(data.colonies)?data.colonies:[];
      datalist.innerHTML = cols.map(c=>`<option value="${c}">`).join('');
      if(!colony.value && cols[0]) colony.value = cols[0];
    }catch(e){ console.warn('CP lookup error', e); }
  }
  zip?.addEventListener('input', e => { if((e.target.value||'').length===5) lookupCP(e.target.value); });

  // Guardar dirección y componer "direccion" ANTES del submit
  const formInvoice = document.getElementById('invoice-form');
  const btnSave = document.getElementById('btn-save');
  const btnText = document.getElementById('btn-text');
  const btnSpin = document.getElementById('btn-spin');

  formInvoice?.addEventListener('submit', async (e)=>{
    const must = {
      street: $('#street').value.trim(),
      ext: $('#ext_number').value.trim(),
      col: $('#colony').value.trim(),
      z: $('#zip').value.trim(),
      st: $('#state').value.trim(),
      mun: $('#municipality').value.trim()
    };
    if(!must.street || !must.ext || !must.col || !must.z || !must.st || !must.mun){
      e.preventDefault();
      alert('Completa calle, número exterior, colonia, C.P., estado y municipio.');
      return;
    }

    // Componer el campo que valida el backend: "direccion"
    const dir = `${must.street} #${must.ext}${($('#int_number').value||'') ? ' Int. '+$('#int_number').value : ''}, ${must.col}, ${must.mun}, ${must.st}, C.P. ${must.z}`;
    document.getElementById('direccion').value = dir;

    btnSave.disabled = true; btnText.style.display='none'; btnSpin.style.display='inline';

    // Guardar dirección en libreta (no bloqueante)
    try{
      const payload = {
        nombre_recibe: $('#contact_name').value || '',
        telefono: $('#phone').value || '',
        calle: must.street,
        num_ext: must.ext,
        num_int: $('#int_number').value || '',
        colonia: must.col,
        cp: must.z,
        estado: must.st,
        municipio: must.mun,
      };
      console.log('POST address payload', payload);
      fetch(ROUTE_ADDR, {
        method:'POST',
        headers:{'X-CSRF-TOKEN': csrf, 'Accept':'application/json', 'Content-Type':'application/json'},
        body: JSON.stringify(payload)
      }).catch(err => console.warn('address save failed (non-blocking)', err));
    }catch(err){ console.warn('address save error', err); }
  });

  // Si tu layout no imprime @stack('scripts'), este JS no corre
  console.log('invoice_select blade JS loaded');
})();
</script>
@endpush
@endsection
