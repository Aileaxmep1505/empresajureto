@extends('layouts.app')
@section('title','Nueva licitación')

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
.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;}
@media(max-width:820px){ .grid{grid-template-columns:1fr} }

/* Field / floating label */
.field{position:relative;background:#fff;border:1px solid var(--line);border-radius:12px;padding:12px;transition:box-shadow .15s,border-color .15s;}
.field:focus-within{border-color:#d1e7de;box-shadow:0 8px 20px rgba(52,194,158,0.06);}
.field input,
.field select,
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
.grid-full{grid-column:1 / -1;}

/* Calendar */
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
.actions{display:flex;gap:12px;justify-content:flex-end;margin-top:18px;align-items:center;}
.actions a{text-decoration:none;}
.btn{border:0;border-radius:10px;padding:10px 16px;font-weight:700;cursor:pointer;font-size:13px;display:inline-flex;align-items:center;justify-content:center;white-space:nowrap;font-family:inherit;}
.btn-primary{background:var(--mint);color:#fff;box-shadow:0 8px 20px rgba(52,194,158,0.12);}
.btn-primary:hover{background:var(--mint-dark);}
.btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink);}
.btn-ghost:hover{border-color:#dbe7ef;}
.btn-xs{padding:7px 10px;font-size:12px;border-radius:999px;}

/* Errors */
.alert-error{
  margin:16px 0;border-radius:12px;background:#fef2f2;border:1px solid #fecaca;
  padding:10px 12px;font-size:13px;color:#b91c1c;
}
.alert-error ul{margin:0;padding-left:18px;}
.alert-error li{margin:2px 0;}
</style>

<div class="wizard-wrap" style="margin-top:-5px;">
  <div class="panel">

    <div class="panel-head">
      <div class="hgroup">
        <div class="step-tag">Paso 1 de 9</div>
        <h2>Crear nueva licitación</h2>
        <p>Define la información básica de la licitación. Podrás completar el resto de los pasos después.</p>
      </div>

      <a href="{{ route('licitaciones.index') }}" class="back-link" title="Volver">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
        Volver
      </a>
    </div>

    @if($errors->any())
      <div class="form">
        <div class="alert-error">
          <ul>
            @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      </div>
    @endif

    <form class="form" action="{{ route('licitaciones.store.step1') }}" method="POST" novalidate>
      @csrf

      <div class="grid">
        {{-- Título --}}
        <div class="grid-full">
          <div class="field">
            <input type="text" name="titulo" id="titulo" value="{{ old('titulo') }}" placeholder=" " autocomplete="off">
            <label for="titulo">Título de la licitación</label>
          </div>
        </div>

        {{-- Descripción --}}
        <div class="grid-full">
          <div class="field">
            <textarea name="descripcion" id="descripcion" rows="3" placeholder=" ">{{ old('descripcion') }}</textarea>
            <label for="descripcion">Descripción (opcional)</label>
          </div>
        </div>

        {{-- ✅ MULTI FECHA --}}
        <div class="grid-full">
          <div class="calendar-box">
            <div style="font-weight:700;font-size:12px;color:var(--muted);margin-bottom:6px;">
              Fechas de convocatoria (puedes elegir varias)
            </div>

            <div class="cal-head">
              <button type="button" class="cal-nav" id="calPrev" aria-label="Mes anterior">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
              </button>

              <div class="cal-title" id="calTitle">—</div>

              <button type="button" class="cal-nav" id="calNext" aria-label="Mes siguiente">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
              </button>
            </div>

            <div class="cal-week" id="calWeek"></div>
            <div class="cal-grid" id="calGrid"></div>

            <div class="cal-actions">
              <button type="button" id="calClear" class="btn btn-ghost btn-xs">Limpiar selección</button>
              <button type="button" id="calToday" class="btn btn-ghost btn-xs">Agregar hoy</button>
            </div>

            <div class="cal-note">
              Selecciona días sueltos o varios consecutivos. Click para activar/desactivar.
            </div>

            <div class="chips" id="calSelected"></div>

            {{-- inputs dinámicos para enviar array --}}
            <div id="fechasInputs"></div>

            {{-- compatibilidad: fecha principal --}}
            <input type="hidden" name="fecha_convocatoria" id="fecha_convocatoria_main" value="">
          </div>
        </div>

        {{-- Modalidad --}}
        <div>
          <div class="field">
            <select name="modalidad" id="modalidad">
              <option value="">Selecciona una opción</option>
              <option value="presencial" {{ old('modalidad') === 'presencial' ? 'selected' : '' }}>Presencial</option>
              <option value="en_linea" {{ old('modalidad') === 'en_linea' ? 'selected' : '' }}>En línea</option>
              <option value="mixta" {{ old('modalidad') === 'mixta' ? 'selected' : '' }}>Mixta</option>
            </select>
            <label for="modalidad">Modalidad</label>
          </div>
        </div>

      </div>

      <div class="actions">
        <a href="{{ route('licitaciones.index') }}" class="btn btn-ghost">Cancelar</a>
        <button type="submit" class="btn btn-primary">Guardar y continuar</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  'use strict';

  const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
  const weekNames = ['do','lu','ma','mi','ju','vi','sá'];

  const calTitle = document.getElementById('calTitle');
  const calWeek  = document.getElementById('calWeek');
  const calGrid  = document.getElementById('calGrid');
  const calPrev  = document.getElementById('calPrev');
  const calNext  = document.getElementById('calNext');
  const calClear = document.getElementById('calClear');
  const calToday = document.getElementById('calToday');

  const chipsWrap = document.getElementById('calSelected');
  const inputsWrap = document.getElementById('fechasInputs');
  const hiddenMain = document.getElementById('fecha_convocatoria_main');

  let viewDate = new Date(); viewDate.setDate(1);
  const selected = new Set(); // YYYY-MM-DD

  function pad(n){ return String(n).padStart(2,'0'); }
  function toISO(y,m,d){ return `${y}-${pad(m+1)}-${pad(d)}`; }
  function isSameDay(a,b){ return a.getFullYear()==b.getFullYear() && a.getMonth()==b.getMonth() && a.getDate()==b.getDate(); }

  function renderWeek(){
    calWeek.innerHTML = '';
    weekNames.forEach(w=>{
      const el=document.createElement('div');
      el.textContent=w;
      calWeek.appendChild(el);
    });
  }

  function renderCalendar(){
    const y=viewDate.getFullYear();
    const m=viewDate.getMonth();
    calTitle.textContent = `${monthNames[m]} ${y}`;

    calGrid.innerHTML='';

    const firstDayIdx = new Date(y,m,1).getDay();
    const daysInMonth = new Date(y,m+1,0).getDate();
    const today = new Date();

    // leading blanks (prev month)
    for(let i=0;i<firstDayIdx;i++){
      const ghost=document.createElement('div');
      ghost.className='cal-day is-out';
      ghost.textContent='';
      calGrid.appendChild(ghost);
    }

    for(let d=1; d<=daysInMonth; d++){
      const iso = toISO(y,m,d);
      const cell=document.createElement('button');
      cell.type='button';
      cell.className='cal-day';
      cell.textContent=d;
      cell.dataset.date = iso;

      if(selected.has(iso)) cell.classList.add('is-selected');
      if(isSameDay(new Date(y,m,d), today)) cell.classList.add('is-today');

      cell.addEventListener('click', ()=>{
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
    chipsWrap.innerHTML='';
    inputsWrap.innerHTML='';

    arr.forEach(date=>{
      // chips
      const chip=document.createElement('div');
      chip.className='chip-date';
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
      chip.querySelector('button').addEventListener('click', ()=>{
        selected.delete(date);
        renderCalendar();
        renderSelected();
      });
      chipsWrap.appendChild(chip);

      // hidden input array
      const inp=document.createElement('input');
      inp.type='hidden';
      inp.name='fechas_convocatoria[]';
      inp.value=date;
      inputsWrap.appendChild(inp);
    });

    // compatibilidad con fecha única
    hiddenMain.value = arr[0] || '';
  }

  calPrev.addEventListener('click', ()=>{
    viewDate.setMonth(viewDate.getMonth()-1);
    renderCalendar();
  });
  calNext.addEventListener('click', ()=>{
    viewDate.setMonth(viewDate.getMonth()+1);
    renderCalendar();
  });
  calClear.addEventListener('click', ()=>{
    selected.clear();
    renderCalendar();
    renderSelected();
  });
  calToday.addEventListener('click', ()=>{
    const t=new Date();
    const iso=toISO(t.getFullYear(), t.getMonth(), t.getDate());
    selected.add(iso);
    renderCalendar();
    renderSelected();
  });

  renderWeek();
  renderCalendar();
  renderSelected();
})();
</script>
@endsection
