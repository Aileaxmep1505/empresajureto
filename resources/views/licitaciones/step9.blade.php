@extends('layouts.app') 
@section('title','Contrato y fianza')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">

<style>
:root{
  --mint:#48cfad;
  --mint-dark:#34c29e;
  --ink:#111827;
  --muted:#6b7280;
  --line:#e6eef6;
  --card:#ffffff;
  --danger:#ef4444;
  --shadow: 0 12px 34px rgba(12,18,30,0.06);
}
*{box-sizing:border-box}
body{font-family:"Open Sans",sans-serif;background:#f3f5f7;color:var(--ink);margin:0;padding:0}

/* Wrapper */
.wizard-wrap{max-width:720px;margin:56px auto;padding:18px;}
.panel{background:var(--card);border-radius:14px;box-shadow:var(--shadow);overflow:hidden;}
.panel-head{padding:20px 22px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:16px;}
.hgroup h2{margin:0;font-weight:700;font-size:20px;}
.hgroup p{margin:4px 0 0;color:var(--muted);font-size:13px;}
.step-tag{font-size:11px;text-transform:uppercase;letter-spacing:.14em;color:var(--mint-dark);font-weight:700;margin-bottom:4px;}
.back-link{display:inline-flex;align-items:center;gap:8px;color:var(--muted);text-decoration:none;padding:8px 12px;border-radius:10px;border:1px solid var(--line);background:#fff;font-size:13px;}
.back-link:hover{border-color:#dbe7ef;color:var(--ink);}

/* Form container */
.form{padding:20px;}
.grid{display:grid;grid-template-columns:1fr;gap:18px;}
.grid-2{grid-template-columns:repeat(2,minmax(0,1fr));}
@media(max-width:720px){ .grid-2{grid-template-columns:1fr;} }

/* Floating fields */
.field{position:relative;background:#fff;border:1px solid var(--line);border-radius:12px;padding:12px;transition:box-shadow .15s,border-color .15s;}
.field:focus-within{border-color:#d1e7de;box-shadow:0 8px 20px rgba(52,194,158,0.06);}
.field input,
.field textarea{
  width:100%;
  border:0;
  outline:0;
  background:transparent;
  font-size:14px;
  color:var(--ink);
  padding-top:8px;
  font-family:inherit;
}
.field textarea{resize:vertical;min-height:72px;max-height:220px;line-height:1.4;}
.field label{
  position:absolute;
  left:14px;
  top:12px;
  color:var(--muted);
  font-size:12px;
  pointer-events:none;
  transition:all .14s;
}
.field input::placeholder,
.field textarea::placeholder{color:transparent;}
.field input:focus + label,
.field textarea:focus + label,
.field input:not(:placeholder-shown) + label,
.field textarea:not(:placeholder-shown) + label{
  top:6px;
  font-size:11px;
  color:var(--mint-dark);
  transform:translateY(-6px);
}
.field input[type="date"] + label{
  top:6px;
  font-size:11px;
}

/* Uploader block */
.block{
  border-radius:12px;
  padding:16px;
  background:#fbfdff;
  border:1px dashed var(--line);
}
.uploader{display:flex;flex-direction:column;gap:10px;}
.uploader-top{display:flex;flex-wrap:wrap;gap:10px;align-items:center;}
.btn-file{
  background:var(--mint);
  color:#fff;
  padding:10px 14px;
  border-radius:999px;
  border:none;
  cursor:pointer;
  font-weight:700;
  font-size:13px;
  box-shadow:0 10px 22px rgba(72,207,173,0.16);
}
.btn-file:hover{background:var(--mint-dark);}
.file-chosen{
  font-size:13px;
  color:var(--muted);
}
.small{color:var(--muted);font-size:12px;}

/* Error box */
.alert-error{
  margin:16px 20px 0 20px;
  border-radius:12px;
  background:#fef2f2;
  border:1px solid #fecaca;
  padding:10px 12px;
  font-size:13px;
  color:#b91c1c;
}
.alert-error ul{margin:0;padding-left:18px;}
.alert-error li{margin:2px 0;}

/* Toggle tipo fianza */
.toggle-group{
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  margin-top:8px;
}
.toggle-pill{
  position:relative;
}
.toggle-pill input{
  display:none;
}
.toggle-pill label{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:8px 12px;
  border-radius:999px;
  border:1px solid var(--line);
  font-size:12px;
  cursor:pointer;
  background:#fff;
  color:var(--muted);
  transition:all .15s;
}
.toggle-pill input:checked + label{
  border-color:var(--mint-dark);
  background:rgba(72,207,173,0.1);
  color:var(--ink);
  box-shadow:0 6px 14px rgba(72,207,173,0.18);
}

/* Calendar for fechas de cobro */
.calendar-box{
  background:#fff;
  border:1px solid var(--line);
  border-radius:12px;
  padding:12px;
}
.cal-head{
  display:flex;align-items:center;justify-content:space-between;gap:10px;
}
.cal-title{
  font-weight:700;font-size:14px;
}
.cal-nav{
  border:1px solid var(--line);
  background:#fff;
  width:34px;height:34px;
  border-radius:10px;
  display:grid;place-items:center;
  cursor:pointer;transition:all .15s;
}
.cal-nav:hover{border-color:#dbe7ef;transform:translateY(-1px)}
.cal-week{
  margin-top:8px;
  display:grid;grid-template-columns:repeat(7,1fr);
  font-size:11px;color:var(--muted);
  text-align:center;
}
.cal-grid{
  margin-top:6px;
  display:grid;grid-template-columns:repeat(7,1fr);
  gap:6px;
}
.cal-day{
  height:38px;border-radius:10px;
  border:1px solid transparent;
  background:#f8fafc;
  display:grid;place-items:center;
  font-size:13px;cursor:pointer;
  transition:background .12s,border-color .12s,transform .12s,box-shadow .12s;
  user-select:none;
}
.cal-day:hover{
  background:#eefbf6;
  border-color:#cfeee5;
  transform:translateY(-1px);
}
.cal-day.is-out{opacity:.35;cursor:default;background:#f3f4f6;}
.cal-day.is-selected{
  background:rgba(72,207,173,.18);
  border-color:rgba(52,194,158,.6);
  color:var(--mint-dark);
  font-weight:700;
  box-shadow:0 6px 14px rgba(52,194,158,.18);
}
.cal-day.is-today{
  outline:2px solid #d1fae5;
}
.cal-actions{
  margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;
}
.cal-note{font-size:12px;color:var(--muted);margin-top:6px}

/* Chips selected dates */
.chips{
  display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;
}
.chip-date{
  background:#f3faf7;
  border:1px solid #d1fae5;
  color:#0f766e;
  padding:4px 8px;border-radius:999px;
  font-size:12px;font-weight:700;
  display:inline-flex;align-items:center;gap:6px;
}
.chip-date button{
  border:0;background:transparent;cursor:pointer;
  width:18px;height:18px;display:grid;place-items:center;
  border-radius:999px;color:#0f766e;
}
.chip-date button:hover{background:#e7f7f1}

/* Actions */
.actions-line{
  margin-top:20px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
}
.actions-right{
  display:flex;
  gap:12px;
  align-items:center;
}
.link-back{
  font-size:12px;
  color:var(--muted);
  text-decoration:none;
}
.link-back:hover{color:var(--ink);text-decoration:underline;}
.btn{
  border:0;
  border-radius:10px;
  padding:10px 16px;
  font-weight:700;
  cursor:pointer;
  font-size:13px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  white-space:nowrap;
  font-family:inherit;
}
.btn-primary{
  background:var(--mint);
  color:#fff;
  box-shadow:0 8px 20px rgba(52,194,158,0.12);
}
.btn-primary:hover{background:var(--mint-dark);}
.btn-ghost{
  background:#fff;
  border:1px solid var(--line);
  color:var(--ink);
}
.btn-ghost:hover{border-color:#dbe7ef;}

@media(max-width:540px){
  .actions-line{flex-direction:column;align-items:flex-start;}
  .actions-right{width:100%;justify-content:flex-end;}
}
</style>

@php
    $tipoFianza = old('tipo_fianza', $licitacion->tipo_fianza ?? null);
    $fechasCobro = old('fechas_cobro', $licitacion->fechas_cobro ?? []);
    if (!is_array($fechasCobro)) {
        $fechasCobro = (array) $fechasCobro;
    }
@endphp

<div class="wizard-wrap" style="margin-top:-5px;">
    <div class="panel">
        <div class="panel-head">
            <div class="hgroup">
                <div class="step-tag">Paso 9 de 9</div>
                <h2>Contrato y fianza</h2>
                <p>
                    Sube el contrato, define el tipo de fianza, registra las fechas clave y selecciona las
                    fechas de cobro esperadas. Se programarán recordatorios en la agenda.
                </p>
            </div>

            <a href="{{ route('licitaciones.edit.step8', $licitacion) }}" class="back-link" title="Volver al paso anterior">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Paso anterior
            </a>
        </div>

        @if($errors->any())
            <div class="alert-error">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="form" action="{{ route('licitaciones.update.step9', $licitacion) }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf

            {{-- Contrato --}}
            <div class="block">
                <div class="uploader">
                    <div class="uploader-top">
                        <label for="contrato" class="btn-file">
                            Seleccionar contrato (PDF)
                        </label>
                        <input
                            id="contrato"
                            type="file"
                            name="contrato"
                            accept=".pdf"
                            style="display:none;"
                        >
                        <span id="file-name-contrato" class="file-chosen">
                            Ningún archivo seleccionado
                        </span>
                    </div>
                    <p class="small">
                        Obligatorio, solo formato <strong>PDF</strong>. Este archivo quedará ligado como contrato principal de la licitación.
                    </p>
                </div>
            </div>

            {{-- Fechas principales --}}
            <div class="grid grid-2" style="margin-top:10px;">
                <div>
                    <div class="field">
                        <input
                            type="date"
                            name="fecha_emision_contrato"
                            id="fecha_emision_contrato"
                            value="{{ old('fecha_emision_contrato', optional($licitacion->fecha_emision_contrato)->format('Y-m-d')) }}"
                            placeholder=" "
                        >
                        <label for="fecha_emision_contrato">Fecha de emisión del contrato</label>
                    </div>
                </div>
                <div>
                    <div class="field">
                        <input
                            type="date"
                            name="fecha_fianza"
                            id="fecha_fianza"
                            value="{{ old('fecha_fianza', optional($licitacion->fecha_fianza)->format('Y-m-d')) }}"
                            placeholder=" "
                        >
                        <label for="fecha_fianza">Fecha de fianza</label>
                    </div>
                </div>
            </div>

            {{-- Tipo de fianza --}}
            <div style="margin-top:14px;">
                <div class="small" style="margin-bottom:4px;">Tipo de fianza</div>
                <div class="toggle-group">
                    <div class="toggle-pill">
                        <input
                            type="radio"
                            name="tipo_fianza"
                            id="tipo_fianza_cumplimiento"
                            value="cumplimiento"
                            {{ $tipoFianza === 'cumplimiento' ? 'checked' : '' }}
                        >
                        <label for="tipo_fianza_cumplimiento">
                            <span>✔</span>
                            <span>Fianza de cumplimiento</span>
                        </label>
                    </div>

                    <div class="toggle-pill">
                        <input
                            type="radio"
                            name="tipo_fianza"
                            id="tipo_fianza_vicios"
                            value="vicios_ocultos"
                            {{ $tipoFianza === 'vicios_ocultos' ? 'checked' : '' }}
                        >
                        <label for="tipo_fianza_vicios">
                            <span>✔</span>
                            <span>Fianza por vicios ocultos</span>
                        </label>
                    </div>
                </div>
                <p class="small" style="margin-top:4px;">
                    Selecciona el tipo de fianza aplicable a este contrato. Se usará en la descripción del recordatorio en la agenda.
                </p>
            </div>

            {{-- Observaciones --}}
            <div style="margin-top:14px;">
                <div class="field">
                    <textarea
                        name="observaciones_contrato"
                        id="observaciones_contrato"
                        placeholder=" "
                    >{{ old('observaciones_contrato', $licitacion->observaciones_contrato ?? '') }}</textarea>
                    <label for="observaciones_contrato">Observaciones / notas internas de contrato y fianza</label>
                </div>
            </div>

            {{-- Fechas de cobro con calendario (multi selección) --}}
            <div style="margin-top:18px;">
                <div class="calendar-box">
                    <div style="font-weight:700;font-size:12px;color:var(--muted);margin-bottom:6px;">
                        Fechas de cobro / pagos esperados (selecciona varias)
                    </div>

                    <div class="cal-head">
                        <button type="button" class="cal-nav" id="cobCalPrev" aria-label="Mes anterior">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>

                        <div class="cal-title" id="cobCalTitle">—</div>

                        <button type="button" class="cal-nav" id="cobCalNext" aria-label="Mes siguiente">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>

                    <div class="cal-week" id="cobCalWeek"></div>
                    <div class="cal-grid" id="cobCalGrid"></div>

                    <div class="cal-actions">
                        <button type="button" id="cobCalClear" class="btn btn-ghost" style="padding:7px 10px;font-size:12px;border-radius:999px;">
                            Limpiar selección
                        </button>
                        <button type="button" id="cobCalToday" class="btn btn-ghost" style="padding:7px 10px;font-size:12px;border-radius:999px;">
                            Agregar hoy
                        </button>
                    </div>

                    <div class="cal-note">
                        Ejemplo: del {{ now()->format('d/m/Y') }} al 12/12/2025 puedes marcar varios días.
                        Cada fecha se guardará también como evento de cobro en la agenda.
                    </div>

                    <div class="chips" id="cobCalSelected"></div>

                    {{-- inputs dinámicos para enviar array --}}
                    <div id="cobFechasInputs"></div>
                </div>
            </div>

            {{-- Acciones --}}
            <div class="actions-line">
                <a href="{{ route('licitaciones.edit.step8', $licitacion) }}" class="link-back">
                    ← Volver al paso anterior
                </a>

                <div class="actions-right">
                    <a href="{{ route('licitaciones.index') }}" class="btn btn-ghost">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Guardar y continuar al checklist
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
    // Nombre del archivo de contrato
    const inputContrato = document.getElementById('contrato');
    const labelContrato = document.getElementById('file-name-contrato');

    if(inputContrato && labelContrato){
        inputContrato.addEventListener('change', function(){
            if(this.files && this.files.length > 0){
                labelContrato.textContent = this.files[0].name;
            } else {
                labelContrato.textContent = 'Ningún archivo seleccionado';
            }
        });
    }

    // ----- Calendario de fechas de cobro (similar al paso 1) -----
    'use strict';

    const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    const weekNames  = ['do','lu','ma','mi','ju','vi','sá'];

    const calTitle   = document.getElementById('cobCalTitle');
    const calWeek    = document.getElementById('cobCalWeek');
    const calGrid    = document.getElementById('cobCalGrid');
    const calPrev    = document.getElementById('cobCalPrev');
    const calNext    = document.getElementById('cobCalNext');
    const calClear   = document.getElementById('cobCalClear');
    const calToday   = document.getElementById('cobCalToday');

    const chipsWrap  = document.getElementById('cobCalSelected');
    const inputsWrap = document.getElementById('cobFechasInputs');

    // Fechas que vienen de old() o de BD (Blade → JS)
    const initialSelected = @json($fechasCobro);

    let viewDate = new Date();
    viewDate.setDate(1);

    const selected = new Set(
        (initialSelected || []).filter(Boolean)
    ); // YYYY-MM-DD

    function pad(n){ return String(n).padStart(2,'0'); }
    function toISO(y,m,d){ return `${y}-${pad(m+1)}-${pad(d)}`; }
    function isSameDay(a,b){ return a.getFullYear()==b.getFullYear() && a.getMonth()==b.getMonth() && a.getDate()==b.getDate(); }

    function renderWeek(){
        calWeek.innerHTML = '';
        weekNames.forEach(w => {
            const el = document.createElement('div');
            el.textContent = w;
            calWeek.appendChild(el);
        });
    }

    function renderCalendar(){
        const y = viewDate.getFullYear();
        const m = viewDate.getMonth();
        calTitle.textContent = `${monthNames[m]} ${y}`;

        calGrid.innerHTML = '';

        const firstDayIdx  = new Date(y,m,1).getDay();
        const daysInMonth  = new Date(y,m+1,0).getDate();
        const today        = new Date();

        // blanks (no-días) al inicio
        for(let i=0; i<firstDayIdx; i++){
            const ghost = document.createElement('div');
            ghost.className = 'cal-day is-out';
            ghost.textContent = '';
            calGrid.appendChild(ghost);
        }

        for(let d=1; d<=daysInMonth; d++){
            const iso = toISO(y,m,d);
            const cell = document.createElement('button');
            cell.type = 'button';
            cell.className = 'cal-day';
            cell.textContent = d;
            cell.dataset.date = iso;

            if(selected.has(iso)) cell.classList.add('is-selected');
            if(isSameDay(new Date(y,m,d), today)) cell.classList.add('is-today');

            cell.addEventListener('click', () => {
                if(selected.has(iso)) selected.delete(iso);
                else selected.add(iso);
                renderCalendar();
                renderSelected();
            });

            calGrid.appendChild(cell);
        }
    }

    function renderSelected(){
        const arr = Array.from(selected).sort();
        chipsWrap.innerHTML = '';
        inputsWrap.innerHTML = '';

        arr.forEach(date => {
            // chip visible
            const chip = document.createElement('div');
            chip.className = 'chip-date';
            chip.innerHTML = `
                <span>${date.split('-').reverse().join('/')}</span>
                <button type="button" aria-label="Quitar fecha">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            `;
            chip.querySelector('button').addEventListener('click', () => {
                selected.delete(date);
                renderCalendar();
                renderSelected();
            });
            chipsWrap.appendChild(chip);

            // input hidden para enviar al servidor
            const inp = document.createElement('input');
            inp.type  = 'hidden';
            inp.name  = 'fechas_cobro[]';
            inp.value = date;
            inputsWrap.appendChild(inp);
        });
    }

    if(calPrev){
        calPrev.addEventListener('click', () => {
            viewDate.setMonth(viewDate.getMonth() - 1);
            renderCalendar();
        });
    }
    if(calNext){
        calNext.addEventListener('click', () => {
            viewDate.setMonth(viewDate.getMonth() + 1);
            renderCalendar();
        });
    }
    if(calClear){
        calClear.addEventListener('click', () => {
            selected.clear();
            renderCalendar();
            renderSelected();
        });
    }
    if(calToday){
        calToday.addEventListener('click', () => {
            const t   = new Date();
            const iso = toISO(t.getFullYear(), t.getMonth(), t.getDate());
            selected.add(iso);
            // mover la vista al mes actual para que se vea
            viewDate = new Date(t.getFullYear(), t.getMonth(), 1);
            renderCalendar();
            renderSelected();
        });
    }

    renderWeek();
    renderCalendar();
    renderSelected();
})();
</script>
@endsection
