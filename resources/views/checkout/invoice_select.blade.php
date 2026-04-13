{{-- resources/views/checkout/invoice_select.blade.php --}}
@extends('layouts.web')
@section('title','Datos de facturación')

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
    overflow-x: hidden;
  }

  .wrap {
    max-width: 980px;
    margin: 32px auto;
    padding: 0 20px;
    box-sizing: border-box;
  }

  /* ====================== CARDS ====================== */
  .card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    margin-bottom: 24px;
    overflow: hidden;
  }
  .card-head {
    padding: 24px;
    border-bottom: 1px solid var(--line);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
  }
  .card-body {
    padding: 24px;
  }

  /* ====================== TYPOGRAPHY ====================== */
  .page-title { margin: 0; font-weight: 700; color: var(--ink); font-size: 1.5rem; }
  .muted { color: var(--muted); font-weight: 500; }
  .text-error { color: var(--danger); font-size: 0.9rem; font-weight: 600; }

  /* ====================== BADGES ====================== */
  .badges { display: flex; flex-wrap: wrap; gap: 8px; }
  .badge {
    background: var(--blue-soft);
    color: var(--blue);
    padding: 6px 12px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 0.85rem;
  }

  /* ====================== BUTTONS ====================== */
  .btn {
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 8px; padding: 10px 18px;
    font-weight: 600; font-size: 0.95rem; font-family: inherit;
    text-decoration: none; cursor: pointer; gap: 8px; border: none;
    transition: transform 0.15s ease, background 0.2s ease, box-shadow 0.2s ease;
  }
  .btn:active { transform: scale(0.98); }
  .btn:disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
  
  .btn-primary { background: var(--blue); color: #ffffff; }
  .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0, 122, 255, 0.15); }
  
  .btn-outline { background: var(--card); border: 1px solid var(--blue); color: var(--blue); }
  .btn-outline:hover { background: var(--blue-soft); transform: translateY(-1px); }

  .btn-danger { background: var(--danger-soft); color: var(--danger); }
  .btn-danger:hover { background: var(--danger); color: #ffffff; transform: translateY(-1px); }

  .btn-ghost { background: transparent; color: #555555; }
  .btn-ghost:hover { background: var(--bg); color: var(--ink); }

  /* ====================== FORMS ====================== */
  .fi { display: grid; gap: 8px; }
  .fi label { font-size: 0.85rem; color: var(--ink); font-weight: 600; }
  .input, .select {
    width: 100%; box-sizing: border-box;
    border: 1px solid var(--line); border-radius: 8px;
    padding: 12px 14px; font-size: 0.95rem; font-family: inherit;
    font-weight: 500; color: var(--ink); background: var(--card);
    transition: border-color 0.2s ease, box-shadow 0.2s ease; outline: none;
  }
  .input::placeholder { color: #a0a0a0; }
  .input:focus, .select:focus { border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft); }
  .input[readonly], .select:disabled { background: var(--bg); color: var(--muted); cursor: not-allowed; }
  
  .select {
    appearance: none; -webkit-appearance: none;
    padding-right: 36px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23888888'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
    background-position: right 12px center; background-size: 16px; background-repeat: no-repeat;
  }

  .grid { display: grid; gap: 16px; }
  .g2 { grid-template-columns: 1fr 1fr; }
  @media (max-width: 860px) { .g2 { grid-template-columns: 1fr; } }
  .row { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
  .footer-actions { display: flex; align-items: center; justify-content: flex-end; gap: 12px; margin-top: 16px; flex-wrap: wrap; }

  /* ====================== MODAL ====================== */
  .modal-backdrop {
    position: fixed; inset: 0;
    background: rgba(17, 17, 17, 0.45);
    z-index: 9998; /* Cubre el header global */
    opacity: 0; pointer-events: none; transition: opacity 0.2s ease;
  }
  .modal-backdrop.open { opacity: 1; pointer-events: auto; }
  
  .modal {
    position: fixed; left: 50%; top: 50%;
    transform: translate(-50%, -45%) scale(0.98);
    width: min(720px, 92vw);
    max-height: calc(100vh - 64px); /* Margen superior e inferior */
    overflow-y: auto;
    background: var(--card); border-radius: 16px;
    box-shadow: 0 24px 48px rgba(0,0,0,0.15);
    z-index: 9999;
    opacity: 0; pointer-events: none; transition: all 0.2s ease;
  }
  .modal.open { opacity: 1; pointer-events: auto; transform: translate(-50%, -50%) scale(1); }
  
  .modal-head {
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
    padding: 20px 24px; border-bottom: 1px solid var(--line);
    position: sticky; top: 0; background: var(--card); z-index: 10;
  }
  .modal-body { padding: 24px; }
  
  .close-x {
    appearance: none; border: 0; background: transparent; cursor: pointer;
    font-size: 24px; line-height: 1; color: var(--muted); transition: color 0.2s;
  }
  .close-x:hover { color: var(--ink); }

  /* ====================== STEPS ====================== */
  .steps { display: flex; align-items: center; gap: 12px; }
  .dot {
    width: 28px; height: 28px; border-radius: 999px;
    display: inline-flex; align-items: center; justify-content: center;
    border: 1px solid var(--line); background: var(--bg);
    font-weight: 700; color: var(--muted); font-size: 0.85rem;
  }
  .dot.active { background: var(--blue-soft); color: var(--blue); border-color: var(--blue-soft); }
  .steps strong { color: var(--muted); font-weight: 600; font-size: 0.95rem; }
  .dot.active + strong { color: var(--ink); font-weight: 700; }
  .sep { color: var(--line); font-weight: 700; }

  /* ====================== PROFILES LIST ====================== */
  .profiles { display: grid; gap: 12px; }
  .profile {
    display: flex; gap: 16px; align-items: flex-start; justify-content: space-between;
    padding: 20px; border: 1px solid var(--line); border-radius: 12px; background: var(--card);
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease; position: relative; cursor: pointer;
  }
  .profile:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.03); }
  .profile.selected { border-color: var(--blue); box-shadow: 0 0 0 1px var(--blue); }
  
  .profile .left { display: flex; gap: 16px; align-items: flex-start; width: 100%; }
  .radio { accent-color: var(--blue); width: 18px; height: 18px; margin-top: 4px; cursor: pointer; }
  
  .meta { line-height: 1.5; width: 100%; cursor: pointer; }
  .meta .razon { font-weight: 700; color: var(--ink); font-size: 1.05rem; }
  .meta .small { font-size: 0.9rem; color: var(--muted); font-weight: 500; }
  
  .chips { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
  .chip {
    background: var(--bg); border: 1px solid var(--line);
    padding: 4px 10px; border-radius: 999px; font-size: 0.8rem; color: var(--text); font-weight: 600;
  }
  .actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

  /* Animations */
  .fade-in { animation: fade 0.3s ease-out; }
  @keyframes fade { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="wrap">
  {{-- ================== HEADER ================== --}}
  <div class="card fade-in">
    <div class="card-head">
      <div>
        <h1 class="page-title">Datos de facturación</h1>
        <div class="muted" style="margin-top:6px">Usa un perfil guardado o agrega uno nuevo. Podrás cambiarlo después.</div>
      </div>
      <a href="{{ route('checkout.shipping') }}" class="btn btn-ghost">Omitir ahora</a>
    </div>

    <div class="card-body">
      @if ($errors->any())
        <div class="text-error" style="margin-bottom:16px; background: var(--danger-soft); padding: 16px; border-radius: 8px;">
          <strong style="color: var(--danger)">Corrige estos campos:</strong>
          <ul style="margin: 8px 0 0 18px; color: var(--danger)">
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
          <span class="badge" style="background: var(--bg); color: var(--text); border: 1px solid var(--line);">Pago: Tarjeta</span>
        </div>
        <button id="open-modal" class="btn btn-primary">Agregar nuevo</button>
      </div>
    </div>
  </div>

  {{-- ================== LISTA DE PERFILES GUARDADOS ================== --}}
  @php
    $hasProfiles = isset($profiles) && $profiles->count() > 0;
    $regimenLabels = $regimenOptions ?? [];
    $usoLabels     = $usoCfdiOptions ?? [];
    $defaultId = $hasProfiles ? ($profiles->firstWhere('is_default', true)->id ?? $profiles->first()->id) : null;
  @endphp

  @if($hasProfiles)
    <div class="card fade-in">
      <div class="card-head">
        <div>
          <h2 class="page-title" style="font-size:1.2rem">Perfiles guardados</h2>
          <div class="muted" style="margin-top:4px">{{ $profiles->count() }} perfil{{ $profiles->count()===1?'':'es' }} disponibles</div>
        </div>
        <form id="form-select" method="POST" action="{{ route('checkout.invoice.select') }}">
          @csrf
          <input type="hidden" name="id" id="selected_id" value="{{ $defaultId }}">
          <button type="submit" class="btn btn-primary">Usar seleccionado</button>
        </form>
      </div>

      <div class="card-body">
        <div class="profiles" id="profiles">
          @foreach($profiles as $p)
            @php
              $isDefault = (bool)$p->is_default;
              $rid = 'profile_'.$p->id;
              $reg = $p->regimen ? ($regimenLabels[$p->regimen] ?? $p->regimen) : '—';
              $uso = $p->uso_cfdi ? ($usoLabels[$p->uso_cfdi] ?? $p->uso_cfdi) : '—';
              $addr = trim(($p->direccion ?? '').' C.P. '.($p->zip ?? ''));
            @endphp
            <div class="profile {{ $defaultId===$p->id ? 'selected':'' }}" data-id="{{ $p->id }}">
              <div class="left">
                <input class="radio" type="radio" name="profile_radio" id="{{ $rid }}" {{ $defaultId===$p->id ? 'checked':'' }}>
                <label for="{{ $rid }}" class="meta">
                  <div class="razon">{{ $p->razon_social ?? '—' }}</div>
                  <div class="small" style="margin-top: 4px;">RFC: <strong style="color:var(--ink)">{{ $p->rfc }}</strong> • Régimen: {{ $reg }} • Uso: {{ $uso }}</div>
                  <div class="small">Dirección fiscal: {{ $addr }}</div>
                  @if($p->email || $p->telefono || $p->contacto || $isDefault)
                    <div class="chips">
                      @if($p->email)<span class="chip">Email: {{ $p->email }}</span>@endif
                      @if($p->telefono)<span class="chip">Tel: {{ $p->telefono }}</span>@endif
                      @if($p->contacto)<span class="chip">Contacto: {{ $p->contacto }}</span>@endif
                      @if($isDefault)<span class="chip" style="background:var(--blue-soft); color:var(--blue); border-color:var(--blue-soft);">Predeterminado</span>@endif
                    </div>
                  @endif
                </label>
              </div>
              <div class="actions">
                <form method="POST" action="{{ route('checkout.invoice.select') }}" onsubmit="return confirm('¿Usar este perfil?')">
                  @csrf
                  <input type="hidden" name="id" value="{{ $p->id }}">
                  <button type="submit" class="btn btn-outline" style="padding: 8px 12px; font-size: 0.85rem;">Usar este</button>
                </form>
                <form method="POST" action="{{ route('checkout.invoice.delete') }}" onsubmit="return confirm('¿Eliminar este perfil de facturación?')">
                  @csrf
                  @method('DELETE')
                  <input type="hidden" name="id" value="{{ $p->id }}">
                  <button type="submit" class="btn btn-danger" style="padding: 8px 12px; font-size: 0.85rem;">Eliminar</button>
                </form>
              </div>
            </div>
          @endforeach
        </div>

        <div class="muted" style="margin-top:24px; display: flex; align-items: center; gap: 12px;">
          ¿Otro RFC? <button id="open-modal-2" class="btn btn-ghost" style="padding: 6px 12px;">Agregar otro</button>
        </div>
      </div>
    </div>
  @endif

</div>

{{-- ================== MODAL: ALTA NUEVO PERFIL (2 pasos) ================== --}}
<div id="backdrop" class="modal-backdrop"></div>
<section id="invoice-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="inv-title">
  <header class="modal-head">
    <div class="steps">
      <span id="dot1" class="dot active">1</span><strong>RFC</strong>
      <span class="sep">—</span>
      <span id="dot2" class="dot">2</span><strong>Datos</strong>
    </div>
    <button id="close-modal" class="close-x" aria-label="Cerrar">✕</button>
  </header>

  <div class="modal-body">
    {{-- Paso 1: RFC --}}
    <div id="step1" class="grid">
      <div class="fi">
        <label for="rfc">RFC *</label>
        <input id="rfc" class="input" maxlength="13" placeholder="Ej. ABCD800101XXX" value="{{ old('rfc') }}">
        <small class="muted" style="margin-top: 4px;">PM: 12 · PF: 13 · Se permite XAXX/XEXX (genérico).</small>
        <div id="rfc-error" class="text-error" style="display:none; margin-top: 8px;"></div>
      </div>
      <div class="footer-actions">
        <button class="btn btn-ghost" id="cancel-1" type="button">Cancelar</button>
        <button class="btn btn-primary" id="btn-next" type="button">Validar y continuar</button>
      </div>
    </div>

    {{-- Paso 2: Datos completos --}}
    <div id="step2" class="grid" style="display:none">
      <form id="invoice-form" method="POST" action="{{ route('checkout.invoice.store') }}" autocomplete="off" novalidate>
        @csrf
        <input type="hidden" name="rfc" id="rfc_final" value="{{ old('rfc') }}">
        <input type="hidden" name="direccion" id="direccion">

        <div class="grid" style="margin-bottom: 16px;">
          <div class="fi">
            <label>Razón social *</label>
            <input name="razon" class="input" value="{{ old('razon') }}" required>
          </div>
        </div>

        <div class="grid g2" style="margin-bottom: 16px;">
          <div class="fi">
            <label>C.P. fiscal *</label>
            <input id="zip" name="zip" class="input" inputmode="numeric" maxlength="10" value="{{ old('zip') }}" required>
            <small class="muted" style="margin-top: 4px;">Autocompleta Estado / Municipio / Colonia</small>
          </div>
          <div class="fi">
            <label>Email para factura (opcional)</label>
            <input type="email" name="email" class="input" placeholder="ejemplo@correo.com" value="{{ old('email') }}">
          </div>
        </div>

        <div class="grid g2" style="margin-bottom: 16px;">
          <div class="fi">
            <label>Régimen fiscal (SAT) *</label>
            <select name="regimen" id="regimen" class="select" required></select>
            <small class="muted" id="hint-persona" style="display:block;margin-top:6px; color: var(--blue); font-weight: 600;"></small>
          </div>

          <div class="fi">
            <label>Uso CFDI *</label>
            <select name="uso_cfdi" id="uso_cfdi" class="select" required></select>
            <small class="muted" style="margin-top: 4px;">Opciones mostradas según persona física/moral.</small>
          </div>
        </div>

        <div class="grid g2" style="margin-bottom: 16px;">
          <div class="fi">
            <label>Contacto (quien recibe)</label>
            <input name="contacto" id="contact_name" class="input" placeholder="Nombre de contacto" value="{{ old('contacto') }}">
          </div>
          <div class="fi">
            <label>Teléfono</label>
            <input name="telefono" id="phone" type="tel" class="input" placeholder="Celular o fijo" value="{{ old('telefono') }}">
          </div>
        </div>

        <div class="grid" style="margin-bottom: 16px;">
          <div class="fi">
            <label>Calle *</label>
            <input id="street" class="input" placeholder="Nombre de la calle" value="">
          </div>
        </div>

        <div class="grid g2" style="margin-bottom: 16px;">
          <div class="fi">
            <label>No. exterior *</label>
            <input id="ext_number" class="input" placeholder="Ej. 123" value="">
          </div>
          <div class="fi">
            <label>No. interior (opcional)</label>
            <input id="int_number" class="input" placeholder="Ej. B" value="">
          </div>
        </div>

        <div class="grid g2" style="margin-bottom: 16px;">
          <div class="fi">
            <label>Colonia *</label>
            <input list="colonies-list" id="colony" name="colonia" class="input" placeholder="Selecciona o escribe" value="{{ old('colonia') }}">
            <datalist id="colonies-list"></datalist>
          </div>
          <div class="fi">
            <label>Municipio / Alcaldía *</label>
            <input id="municipality" class="input" placeholder="Municipio" value="">
          </div>
        </div>

        <div class="grid g2" style="margin-bottom: 24px;">
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

        <div class="footer-actions" style="border-top: 1px solid var(--line); padding-top: 20px;">
          <button type="button" class="btn btn-ghost" id="btn-back">Atrás</button>
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
  const $$ = s => Array.from(document.querySelectorAll(s));
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  // ===== Rutas backend
  const ROUTE_CP   = @json(route('checkout.cp'));            // GET ?cp=XXXXX
  const ROUTE_ADDR = @json(route('checkout.address.store')); // POST JSON

  // ===== Modal
  const backdrop = $('#backdrop');
  const modal = $('#invoice-modal');
  const openBtn = $('#open-modal');
  const openBtn2 = $('#open-modal-2');
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
  openBtn2?.addEventListener('click', openModal);
  closeBtn?.addEventListener('click', closeModal);
  cancel1?.addEventListener('click', closeModal);
  backdrop?.addEventListener('click', e => { if(e.target === backdrop) closeModal(); });
  document.addEventListener('keydown', e => { if(e.key === 'Escape') closeModal(); });

  // ===== Steps
  const step1 = $('#step1'), step2 = $('#step2');
  const dot1 = $('#dot1'), dot2 = $('#dot2');
  function go1(){ step1.style.display='grid'; step2.style.display='none'; dot1.classList.add('active'); dot2.classList.remove('active'); }
  function go2(){ step1.style.display='none'; step2.style.display='grid'; dot1.classList.remove('active'); dot2.classList.add('active'); }

  // ===== Catálogos (filtrado por persona)
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
  const USO_PM = USO_BASE;
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

  // ===== Validación RFC
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

  // ===== CP Lookup
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

  // ===== Guardar dirección auxiliar y componer "direccion" ANTES del submit
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
      fetch(ROUTE_ADDR, {
        method:'POST',
        headers:{'X-CSRF-TOKEN': csrf, 'Accept':'application/json', 'Content-Type':'application/json'},
        body: JSON.stringify(payload)
      }).catch(()=>{});
    }catch(err){}
  });

  // ===== Radios y selección rápida en la lista
  const rows = $$('#profiles .profile');
  const hiddenId = $('#selected_id');
  rows.forEach(row=>{
    const radio = row.querySelector('.radio');
    const id = row.getAttribute('data-id');
    function selectRow(){
      rows.forEach(r=>r.classList.remove('selected'));
      row.classList.add('selected');
      hiddenId.value = id;
      radio.checked = true;
    }
    row.addEventListener('click', (e)=>{
      // Evitar conflicto con botones de acción
      const target = e.target;
      if (target.closest('form')) return;
      selectRow();
    });
    radio?.addEventListener('change', selectRow);
  });

})();
</script>
@endpush
@endsection