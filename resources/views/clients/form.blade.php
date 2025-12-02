@extends('layouts.app')

@section('title', $client->exists ? 'Editar cliente' : 'Nuevo cliente')
@section('header', $client->exists ? 'Editar cliente' : 'Nuevo cliente')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
:root{
  --mint:#48cfad; --mint-dark:#34c29e;
  --ink:#2a2e35; --muted:#7a7f87; --line:#e9ecef; --card:#ffffff;
}
*{ box-sizing:border-box }
.page{ max-width:1100px; margin:12px auto 24px; padding:0 14px }

/* ===== Panel ===== */
.panel{ background:var(--card); border-radius:16px; box-shadow:0 16px 40px rgba(18,38,63,.12); overflow:hidden }
.panel-head{
  padding:20px 22px; border-bottom:1px solid var(--line);
  display:flex; align-items:center; justify-content:space-between; gap:14px
}
.hgroup h2{ margin:0; font-weight:800; color:var(--ink); letter-spacing:-.02em }
.hgroup p{ margin:6px 0 0; color:var(--muted); font-size:14px }
@media (max-width:640px){ .hgroup p{ display:none } }

.back-link{
  display:inline-flex; align-items:center; gap:8px; color:var(--muted);
  text-decoration:none; padding:9px 12px; border-radius:10px; border:1px solid var(--line); background:#fff;
}
.back-link:hover{ color:var(--ink); border-color:#dfe3e8 }

/* ===== Campo con label flotante ===== */
.field{
  position:relative; background:#fff; border:1px solid var(--line); border-radius:12px;
  padding:16px 14px 10px; transition:box-shadow .2s, border-color .2s
}
.field:focus-within{ border-color:#d8dee6; box-shadow:0 8px 24px rgba(18,38,63,.08) }
.field input,
.field select{
  width:100%; border:0; outline:0; background:transparent; font-size:15px; color:var(--ink);
  padding-top:8px; appearance:none;
}
.field label{
  position:absolute; left:14px; top:12px; color:var(--muted); font-size:13px;
  transition:transform .15s, color .15s, font-size .15s, top .15s; pointer-events:none
}

/* inputs flotantes */
.field input::placeholder{ color:transparent }
.field input:focus + label,
.field input:not(:placeholder-shown) + label{
  top:6px; transform:translateY(-9px); font-size:11px; color:var(--mint-dark)
}

/* select flotante: mover label si tiene valor o foco */
.field select.filled + label,
.field select:focus + label{
  top:6px; transform:translateY(-9px); font-size:11px; color:var(--mint-dark)
}

/* Validación */
.is-invalid.field{ border-color:#f9c0c0 !important }
.error, .invalid-feedback{ color:#cc4b4b; font-size:12px; margin-top:6px }

/* ===== Switch (con leyendas) ===== */
.switch-wrap{
  display:flex; align-items:center; justify-content:space-between; gap:16px;
  background:#fff; border:1px solid var(--line); border-radius:12px; padding:14px
}
.switch-legend{ display:flex; align-items:center; gap:10px; color:var(--muted); font-size:14px }
.dot{ width:8px; height:8px; border-radius:999px; background:#e5e7eb }
.dot--on{ background:#10b981 }

.switch{ display:inline-flex; align-items:center; gap:10px; user-select:none }
.switch input{ display:none }
.switch .track{
  width:48px; height:26px; border-radius:999px; background:#e9edf2; position:relative; transition:background .2s
}
.switch .thumb{
  width:22px; height:22px; border-radius:50%; background:#fff; position:absolute; top:2px; left:2px;
  box-shadow:0 2px 8px rgba(0,0,0,.15); transition:left .18s ease
}
.switch input:checked + .track{ background:var(--mint) }
.switch input:checked + .track .thumb{ left:24px }

/* ===== Botones ===== */
.actions{ display:flex; gap:12px; justify-content:flex-end; margin-top:16px; padding:0 4px }
.btn{
  border:0; border-radius:12px; padding:11px 16px; font-weight:800; cursor:pointer;
  transition:transform .05s, box-shadow .2s, background .2s, color .2s
}
.btn:active{ transform:translateY(1px) }
.btn-primary{ background:var(--mint); color:#fff; box-shadow:0 12px 22px rgba(72,207,173,.26) }
.btn-primary:hover{ background:#fff; color:#111; box-shadow:0 16px 32px rgba(0,0,0,.18) }
.btn-ghost{ background:#fff; color:#111; border:1px solid var(--line) }
.btn-ghost:hover{ background:#fff; color:#111; box-shadow:0 12px 26px rgba(0,0,0,.14); border-color:#fff }

/* Nota pequeña bajo campos */
.field-note{
  font-size:11px;
  color:var(--muted);
  margin-top:4px;
}
</style>
@endpush

@section('content')
@php
  $isEdit = $client->exists;
  $v = function($key,$default=null) use ($client){ return old($key, $client->{$key} ?? $default); };
@endphp

<div class="page">
  <div class="panel">
    <div class="panel-head">
      <div class="hgroup">
        <h2>{{ $isEdit ? 'Editar cliente' : 'Agregar cliente' }}</h2>
        <p>{{ $isEdit ? 'Actualiza los datos del cliente.' : 'Crea un nuevo cliente (empresa, gobierno o particular).' }}</p>
      </div>
      <a href="{{ route('clients.index') }}" class="back-link" title="Volver">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Volver
      </a>
    </div>

    <div class="p-3 p-md-4">
      <form
        action="{{ $isEdit ? route('clients.update',$client) : route('clients.store') }}"
        method="POST">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Fila 1: Nombre comercial / Correo --}}
        <div class="row g-3 mb-2">
          <div class="col-12 col-md-6">
            <div class="field @error('nombre') is-invalid @enderror">
              <input type="text" name="nombre" id="f-nombre" value="{{ $v('nombre') }}" placeholder=" " required>
              <label for="f-nombre">Nombre comercial (requerido)</label>
            </div>
            @error('nombre')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-12 col-md-6">
            <div class="field @error('email') is-invalid @enderror">
              <input type="email" name="email" id="f-email" value="{{ $v('email') }}" placeholder=" " required>
              <label for="f-email">Correo (recomendado para Facturapi)</label>
            </div>
            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
        </div>

        {{-- Fila 1b: Razón social SAT --}}
        <div class="row g-3 mb-2">
          <div class="col-12">
            <div class="field @error('razon_social') is-invalid @enderror">
              <input type="text" name="razon_social" id="f-razon" value="{{ $v('razon_social') }}" placeholder=" ">
              <label for="f-razon">Nombre registrado en el SAT (Razón social)</label>
            </div>
            <div class="field-note">
              Si es persona física, escribe el nombre exactamente como aparece en el SAT.  
              Si lo dejas vacío, se usará el <strong>Nombre comercial</strong>.
            </div>
            @error('razon_social')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
        </div>

        {{-- Fila 2: Tipo cliente / Tipo persona / RFC / Régimen fiscal --}}
        <div class="row g-3 mb-2">
          <div class="col-12 col-md-3">
            <div class="field @error('tipo_cliente') is-invalid @enderror">
              <select name="tipo_cliente" id="f-tipo" class="{{ $v('tipo_cliente') ? 'filled' : '' }}">
                <option value="" {{ $v('tipo_cliente') ? '' : 'selected' }} disabled hidden></option>
                <option value="gobierno"   {{ $v('tipo_cliente')==='gobierno'?'selected':'' }}>Gobierno</option>
                <option value="empresa"    {{ $v('tipo_cliente')==='empresa'?'selected':'' }}>Empresa</option>
                <option value="particular" {{ $v('tipo_cliente')==='particular'?'selected':'' }}>Particular</option>
                <option value="otro"       {{ $v('tipo_cliente')==='otro'?'selected':'' }}>Otro</option>
              </select>
              <label for="f-tipo">Tipo de cliente</label>
            </div>
            @error('tipo_cliente')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-3">
            <div class="field @error('tipo_persona') is-invalid @enderror">
              <select name="tipo_persona" id="f-tipo-persona" class="{{ $v('tipo_persona') ? 'filled' : '' }}">
                <option value="" {{ $v('tipo_persona') ? '' : 'selected' }} disabled hidden></option>
                <option value="fisica" {{ $v('tipo_persona')==='fisica'?'selected':'' }}>Persona física</option>
                <option value="moral"  {{ $v('tipo_persona')==='moral'?'selected':'' }}>Persona moral</option>
              </select>
              <label for="f-tipo-persona">Tipo de persona (SAT)</label>
            </div>
            @error('tipo_persona')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-3">
            <div class="field @error('rfc') is-invalid @enderror">
              <input type="text" name="rfc" id="f-rfc" value="{{ $v('rfc') }}" placeholder=" ">
              <label for="f-rfc">RFC</label>
            </div>
            @error('rfc')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-3">
            <div class="field @error('regimen_fiscal') is-invalid @enderror">
              <select name="regimen_fiscal" id="f-regimen" class="{{ $v('regimen_fiscal') ? 'filled' : '' }}">
                <option value="" {{ $v('regimen_fiscal') ? '' : 'selected' }} disabled hidden></option>

                {{-- Persona física --}}
                <option value="605" data-persona="fisica" {{ $v('regimen_fiscal')==='605'?'selected':'' }}>605 - Sueldos y salarios</option>
                <option value="606" data-persona="fisica" {{ $v('regimen_fiscal')==='606'?'selected':'' }}>606 - Asimilados a salarios</option>
                <option value="608" data-persona="fisica" {{ $v('regimen_fiscal')==='608'?'selected':'' }}>608 - Sin obligaciones fiscales</option>
                <option value="612" data-persona="fisica" {{ $v('regimen_fiscal')==='612'?'selected':'' }}>612 - Personas físicas con actividad empresarial</option>
                <option value="621" data-persona="fisica" {{ $v('regimen_fiscal')==='621'?'selected':'' }}>621 - Incorporación fiscal</option>
                <option value="622" data-persona="fisica" {{ $v('regimen_fiscal')==='622'?'selected':'' }}>622 - Actividades agrícolas, ganaderas, silvícolas y pesqueras</option>
                <option value="623" data-persona="fisica" {{ $v('regimen_fiscal')==='623'?'selected':'' }}>623 - Opcional para grupos de sociedades</option>
                <option value="624" data-persona="fisica" {{ $v('regimen_fiscal')==='624'?'selected':'' }}>624 - Coordinados</option>
                <option value="626" data-persona="fisica" {{ $v('regimen_fiscal')==='626'?'selected':'' }}>626 - Régimen Simplificado de Confianza (Resico PF)</option>

                {{-- Persona moral --}}
                <option value="601" data-persona="moral" {{ $v('regimen_fiscal')==='601'?'selected':'' }}>601 - General de Ley Personas Morales</option>
                <option value="603" data-persona="moral" {{ $v('regimen_fiscal')==='603'?'selected':'' }}>603 - Personas Morales con Fines no Lucrativos</option>
                <option value="607" data-persona="moral" {{ $v('regimen_fiscal')==='607'?'selected':'' }}>607 - Régimen de Enajenación o Adquisición de Bienes</option>
                <option value="610" data-persona="moral" {{ $v('regimen_fiscal')==='610'?'selected':'' }}>610 - Residentes en el Extranjero sin Establecimiento Permanente</option>
                <option value="620" data-persona="moral" {{ $v('regimen_fiscal')==='620'?'selected':'' }}>620 - Sociedades Cooperativas de Producción</option>
              </select>
              <label for="f-regimen">Régimen fiscal (SAT)</label>
            </div>
            <div class="field-note">
              Debe coincidir con el tipo de persona (física/moral) para que Facturapi no marque error.
            </div>
            @error('regimen_fiscal')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
        </div>

        {{-- Fila 2b: Uso CFDI (filtrado por tipo de persona) --}}
        <div class="row g-3 mb-2">
          <div class="col-12 col-md-4">
            <div class="field @error('cfdi_uso') is-invalid @enderror">
              <select name="cfdi_uso" id="f-uso" class="{{ $v('cfdi_uso') ? 'filled' : '' }}">
                <option value="" {{ $v('cfdi_uso') ? '' : 'selected' }} disabled hidden></option>

                {{-- Gastos / general - suelen aplicar a ambos, pero revisa catálogo SAT según régimen --}}
                <option value="G01" data-persona="ambos"   {{ $v('cfdi_uso')==='G01'?'selected':'' }}>G01 - Adquisición de mercancías</option>
                <option value="G02" data-persona="ambos"   {{ $v('cfdi_uso')==='G02'?'selected':'' }}>G02 - Devoluciones, descuentos o bonificaciones</option>
                <option value="G03" data-persona="ambos"   {{ $v('cfdi_uso')==='G03'?'selected':'' }}>G03 - Gastos en general</option>

                {{-- Deducciones personas físicas (muy comunes en nómina/servicios) --}}
                <option value="D01" data-persona="fisica"  {{ $v('cfdi_uso')==='D01'?'selected':'' }}>D01 - Honorarios médicos, dentales y gastos hospitalarios</option>
                <option value="D02" data-persona="fisica"  {{ $v('cfdi_uso')==='D02'?'selected':'' }}>D02 - Gastos médicos por incapacidad o discapacidad</option>
                <option value="D03" data-persona="fisica"  {{ $v('cfdi_uso')==='D03'?'selected':'' }}>D03 - Gastos funerales</option>
                <option value="D04" data-persona="fisica"  {{ $v('cfdi_uso')==='D04'?'selected':'' }}>D04 - Donativos</option>
                <option value="D05" data-persona="fisica"  {{ $v('cfdi_uso')==='D05'?'selected':'' }}>D05 - Intereses reales por créditos hipotecarios</option>
                <option value="D07" data-persona="fisica"  {{ $v('cfdi_uso')==='D07'?'selected':'' }}>D07 - Primas por seguros de gastos médicos</option>
                <option value="D08" data-persona="fisica"  {{ $v('cfdi_uso')==='D08'?'selected':'' }}>D08 - Gastos de transportación escolar obligatoria</option

                >
                {{-- Sin efecto fiscal (suele ser física, pero revisa caso) --}}
                <option value="S01" data-persona="fisica"  {{ $v('cfdi_uso')==='S01'?'selected':'' }}>S01 - Sin efectos fiscales</option>
              </select>
              <label for="f-uso">Uso CFDI (SAT)</label>
            </div>
            <div class="field-note">
              Se filtra por persona física/moral. Además debe ser compatible con el régimen del receptor según c_UsoCFDI del SAT.
            </div>
            @error('cfdi_uso')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
        </div>

        {{-- Fila 3: País / Código postal / Teléfono / Contacto --}}
        <div class="row g-3 mb-2">
          <div class="col-12 col-md-3">
            <div class="field @error('pais') is-invalid @enderror">
              <select name="pais" id="f-pais" class="{{ $v('pais','MEX') ? 'filled' : '' }}">
                <option value="" {{ $v('pais','MEX') ? '' : 'selected' }} disabled hidden></option>
                <option value="MEX" {{ $v('pais','MEX')==='MEX'?'selected':'' }}>México (MEX)</option>
              </select>
              <label for="f-pais">País</label>
            </div>
            @error('pais')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-3">
            <div class="field @error('cp') is-invalid @enderror">
              <input type="text" name="cp" id="f-cp" value="{{ $v('cp') }}" placeholder=" ">
              <label for="f-cp">Código postal</label>
            </div>
            @error('cp')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-3">
            <div class="field @error('telefono') is-invalid @enderror">
              <input type="text" name="telefono" id="f-telefono" value="{{ $v('telefono') }}" placeholder=" ">
              <label for="f-telefono">Teléfono</label>
            </div>
            @error('telefono')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 col-md-3">
            <div class="field @error('contacto') is-invalid @enderror">
              <input type="text" name="contacto" id="f-contacto" value="{{ $v('contacto') }}" placeholder=" ">
              <label for="f-contacto">Persona de contacto</label>
            </div>
            @error('contacto')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
        </div>

        {{-- Fila 4: Calle / Núm ext / Núm int --}}
        <div class="row g-3 mb-2">
          <div class="col-12 col-md-6">
            <div class="field @error('calle') is-invalid @enderror">
              <input type="text" name="calle" id="f-calle" value="{{ $v('calle') }}" placeholder=" ">
              <label for="f-calle">Calle</label>
            </div>
            @error('calle')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-12 col-md-3">
            <div class="field @error('num_exterior') is-invalid @enderror">
              <input type="text" name="num_exterior" id="f-num-ext" value="{{ $v('num_exterior') }}" placeholder=" ">
              <label for="f-num-ext">Núm. exterior</label>
            </div>
            @error('num_exterior')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-12 col-md-3">
            <div class="field @error('num_interior') is-invalid @enderror">
              <input type="text" name="num_interior" id="f-num-int" value="{{ $v('num_interior') }}" placeholder=" ">
              <label for="f-num-int">Núm. interior</label>
            </div>
            @error('num_interior')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
        </div>

        {{-- Fila 5: Colonia / Ciudad / Municipio / Estado --}}
        <div class="row g-3 mb-3">
          <div class="col-12 col-md-3">
            <div class="field @error('colonia') is-invalid @enderror">
              <input type="text" name="colonia" id="f-colonia" value="{{ $v('colonia') }}" placeholder=" ">
              <label for="f-colonia">Colonia</label>
            </div>
            @error('colonia')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-12 col-md-3">
            <div class="field @error('ciudad') is-invalid @enderror">
              <input type="text" name="ciudad" id="f-ciudad" value="{{ $v('ciudad') }}" placeholder=" ">
              <label for="f-ciudad">Ciudad</label>
            </div>
            @error('ciudad')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-12 col-md-3">
            <div class="field @error('municipio') is-invalid @enderror">
              <input type="text" name="municipio" id="f-municipio" value="{{ $v('municipio') }}" placeholder=" ">
              <label for="f-municipio">Municipio</label>
            </div>
            @error('municipio')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-12 col-md-3">
            <div class="field @error('estado') is-invalid @enderror">
              <input type="text" name="estado" id="f-estado" value="{{ $v('estado') }}" placeholder=" ">
              <label for="f-estado">Estado</label>
            </div>
            @error('estado')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
        </div>

        {{-- Fila 6: Estatus (switch) --}}
        <div class="row g-3 mb-3">
          <div class="col-12 col-md-4">
            <div class="switch-wrap">
              <div class="switch-legend">
                <span class="dot {{ $v('estatus', $isEdit ? (int)$client->estatus : 1) ? 'dot--on' : '' }}"></span>
                <span>Estatus</span>
                <small style="color:var(--muted)">
                  {{ $v('estatus', $isEdit ? (int)$client->estatus : 1) ? 'Activo' : 'Inactivo' }}
                </small>
              </div>
              <label class="switch">
                <input type="checkbox" name="estatus" value="1"
                       {{ $v('estatus', $isEdit ? (int)$client->estatus : 1) ? 'checked' : '' }}>
                <span class="track"><span class="thumb"></span></span>
              </label>
            </div>
          </div>
        </div>

        {{-- Botones --}}
        <div class="actions">
          <a href="{{ route('clients.index') }}" class="btn btn-ghost">Cancelar</a>
          <button class="btn btn-primary" type="submit">{{ $isEdit ? 'Actualizar' : 'Guardar' }}</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  const selects = [
    document.getElementById('f-tipo'),
    document.getElementById('f-tipo-persona'),
    document.getElementById('f-regimen'),
    document.getElementById('f-pais'),
    document.getElementById('f-uso'),
  ];

  function updateFilled(sel){
    if(!sel) return;
    if(sel.value) sel.classList.add('filled'); else sel.classList.remove('filled');
  }
  selects.forEach(sel => {
    if(!sel) return;
    updateFilled(sel);
    sel.addEventListener('change', () => updateFilled(sel));
  });

  // Filtrar régimen y Uso CFDI según persona física / moral
  const personaSel = document.getElementById('f-tipo-persona');
  const regimenSel = document.getElementById('f-regimen');
  const usoSel     = document.getElementById('f-uso');

  const allRegimenOptions = regimenSel ? Array.from(regimenSel.options) : [];
  const allUsoOptions     = usoSel ? Array.from(usoSel.options) : [];

  function syncPersonaFilters(){
    if(!personaSel) return;
    const persona = personaSel.value; // 'fisica' | 'moral' | ''

    // ---- Régimen fiscal ----
    if(regimenSel){
      let hasVisibleSelected = false;

      allRegimenOptions.forEach(opt => {
        if(!opt.value){ opt.hidden=false; return; } // placeholder
        const p = opt.dataset.persona || 'ambos';
        const allowed = !persona || p === 'ambos' || p === persona;
        opt.hidden = !allowed;
        if(allowed && opt.selected) hasVisibleSelected = true;
      });

      if(!hasVisibleSelected && regimenSel.value){
        regimenSel.value = '';
        updateFilled(regimenSel);
      }
    }

    // ---- Uso CFDI ----
    if(usoSel){
      let hasVisibleSelectedUso = false;

      allUsoOptions.forEach(opt => {
        if(!opt.value){ opt.hidden=false; return; }
        const p = opt.dataset.persona || 'ambos';
        const allowed = !persona || p === 'ambos' || p === persona;
        opt.hidden = !allowed;
        if(allowed && opt.selected) hasVisibleSelectedUso = true;
      });

      if(!hasVisibleSelectedUso && usoSel.value){
        usoSel.value = '';
        updateFilled(usoSel);
      }
    }
  }

  personaSel?.addEventListener('change', syncPersonaFilters);
  // al cargar
  syncPersonaFilters();

  // Switch estatus: actualizar leyenda
  const wrap = document.querySelector('.switch-wrap');
  const chk  = wrap?.querySelector('input[type="checkbox"]');
  const dot  = wrap?.querySelector('.dot');
  const txt  = wrap?.querySelector('small');
  chk?.addEventListener('change', ()=>{
    if(chk.checked){ dot.classList.add('dot--on'); txt.textContent='Activo'; }
    else{ dot.classList.remove('dot--on'); txt.textContent='Inactivo'; }
  });
})();
</script>
@endpush
