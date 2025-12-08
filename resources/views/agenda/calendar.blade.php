@extends('layouts.app')
@section('title','Agenda')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>

<div id="agenda-cal">
  <style>
    #agenda-cal{
      --ink:#0f172a;
      --muted:#6b7280;
      --line:#e2e8f0;
      --bg:#f3f4f6;
      --card:#ffffff;
      --brand:#2563eb;
      --brand-soft:#e0edff;
      --brand-ink:#0b1220;
      --ok:#16a34a;
      --danger:#ef4444;
      --radius-lg:18px;
      --radius-md:12px;
      font-family:'Outfit', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background:radial-gradient(circle at top left,#dbeafe 0,#f9fafb 40%,#f3f4f6 100%);
      min-height: calc(100vh - 80px);
      padding: clamp(16px, 3vw, 28px);
    }

    #agenda-cal .wrap{
      max-width:1200px;
      margin:0 auto;
    }

    /* ====== HEADER SUPERIOR ====== */
    #agenda-cal .top{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:16px;
      margin-bottom:18px;
      flex-wrap:wrap;
    }

    #agenda-cal .top-left h1{
      margin:0;
      font-size:clamp(22px,2.5vw,28px);
      font-weight:700;
      color:var(--ink);
    }

    #agenda-cal .top-left p{
      margin:4px 0 0;
      color:var(--muted);
      font-size:14px;
    }

    #agenda-cal .badge{
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding:4px 10px;
      border-radius:999px;
      background:rgba(37,99,235,0.06);
      color:#1d4ed8;
      font-size:12px;
      margin-top:8px;
    }

    #agenda-cal .badge-dot{
      width:8px;
      height:8px;
      border-radius:999px;
      background:#22c55e;
      box-shadow:0 0 0 4px rgba(34,197,94,0.25);
    }

    #agenda-cal .actions{
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      justify-content:flex-end;
    }

    #agenda-cal .btn{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:10px 14px;
      border-radius:var(--radius-md);
      border:1px solid var(--line);
      background:var(--card);
      color:var(--ink);
      text-decoration:none;
      font-size:14px;
      cursor:pointer;
      transition:all .15s ease-out;
    }

    #agenda-cal .btn i{
      font-size:14px;
    }

    #agenda-cal .btn:hover{
      background:#f9fafb;
      box-shadow:0 8px 22px rgba(15,23,42,0.08);
      transform:translateY(-1px);
    }

    #agenda-cal .btn.primary{
      background:linear-gradient(135deg,#2563eb,#60a5fa);
      border-color:transparent;
      color:white;
      font-weight:600;
      box-shadow:0 12px 35px rgba(37,99,235,0.45);
    }

    #agenda-cal .btn.primary:hover{
      filter:brightness(1.03);
    }

    /* ====== FULLCALENDAR THEME ====== */

    .fc{
      background:var(--card);
      border:1px solid var(--line);
      border-radius:var(--radius-lg);
      padding:8px;
      box-shadow:0 18px 50px rgba(15,23,42,0.08);
    }

    .fc .fc-toolbar{
      padding:4px 6px 10px;
    }

    .fc .fc-toolbar-title{
      font-weight:600;
      color:var(--ink);
      font-size:18px;
    }

    .fc .fc-button{
      border-radius:999px;
      border:1px solid var(--line);
      background:#ffffff;
      color:var(--ink);
      font-size:12px;
      padding:6px 10px;
    }

    .fc .fc-button-primary{
      background:var(--brand);
      border-color:var(--brand);
      color:var(--card);
    }

    .fc .fc-button-primary:not(:disabled):hover{
      filter:brightness(1.05);
    }

    .fc .fc-daygrid-day-number{
      color:#0f172a;
      font-size:12px;
    }

    .fc .fc-day-today{
      background:linear-gradient(180deg,#eff6ff,#ffffff);
    }

    .fc .fc-daygrid-event{
      border-radius:10px;
      padding:2px 6px;
      border:none;
      color:#0f172a;
      font-size:12px;
    }

    .fc .fc-event-title{
      font-weight:600;
    }

    /* Colores alternos para eventos (por clase) */
    .agenda-event-tag-1{
      background:linear-gradient(135deg,#e0edff,#dbeafe);
    }
    .agenda-event-tag-2{
      background:linear-gradient(135deg,#dcfce7,#bbf7d0);
    }
    .agenda-event-tag-3{
      background:linear-gradient(135deg,#fee2e2,#fecaca);
    }
    .agenda-event-tag-4{
      background:linear-gradient(135deg,#fef3c7,#fde68a);
    }
    .agenda-event-tag-5{
      background:linear-gradient(135deg,#f3e8ff,#e9d5ff);
    }

    /* ====== MODAL ====== */

    #agenda-modal-backdrop{
      position:fixed;
      inset:0;
      display:none;
      align-items:center;
      justify-content:center;
      background:rgba(15,23,42,0.45);
      z-index:50;
      backdrop-filter:blur(3px);
    }

    #agenda-modal{
      width:min(720px,92vw);
      background:#ffffff;
      border-radius:22px;
      border:1px solid var(--line);
      box-shadow:0 24px 60px rgba(15,23,42,0.45);
      overflow:hidden;
    }

    #agenda-modal .head{
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding:16px 20px;
      background:linear-gradient(180deg,#eff6ff,#ffffff);
    }

    #agenda-modal .head h3{
      margin:0;
      font-size:18px;
      font-weight:600;
      color:var(--ink);
    }

    #agenda-modal .head small{
      display:block;
      font-size:12px;
      color:var(--muted);
      margin-top:2px;
    }

    #agenda-modal .body{
      padding:18px 20px 12px;
    }

    #agenda-modal .grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:12px 16px;
    }

    @media (max-width: 768px){
      #agenda-modal .grid{
        grid-template-columns:1fr;
      }
      #agenda-cal .top{
        flex-direction:column;
        align-items:flex-start;
      }
      #agenda-cal .actions{
        width:100%;
        justify-content:flex-start;
      }
    }

    #agenda-modal label{
      display:flex;
      align-items:center;
      justify-content:space-between;
      font-weight:600;
      margin:10px 0 4px;
      color:var(--ink);
      font-size:13px;
    }

    #agenda-modal label span.hint{
      font-weight:400;
      color:var(--muted);
      font-size:11px;
      margin-left:8px;
    }

    #agenda-modal input,
    #agenda-modal select,
    #agenda-modal textarea{
      width:100%;
      padding:9px 11px;
      border:1px solid var(--line);
      border-radius:12px;
      background:#ffffff;
      color:var(--ink);
      font-size:14px;
    }

    #agenda-modal textarea{
      resize:vertical;
      min-height:70px;
    }

    #agenda-modal input:focus,
    #agenda-modal select:focus,
    #agenda-modal textarea:focus{
      outline:none;
      border-color:var(--brand);
      box-shadow:0 0 0 1px rgba(37,99,235,0.2);
    }

    #agenda-modal .channels{
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      margin-top:6px;
    }

    #agenda-modal .chip{
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding:6px 10px;
      border-radius:999px;
      border:1px solid var(--line);
      background:#f9fafb;
      font-size:13px;
      color:var(--ink);
      cursor:pointer;
    }

    #agenda-modal .chip input{
      width:auto;
    }

    #agenda-modal .foot{
      display:flex;
      gap:10px;
      justify-content:flex-end;
      padding:14px 20px;
      background:#f9fafb;
      border-top:1px solid var(--line);
      flex-wrap:wrap;
    }

    #agenda-modal .btn{
      border-radius:var(--radius-md);
      border:1px solid var(--line);
      background:#ffffff;
      color:var(--ink);
      padding:9px 14px;
      font-size:14px;
      cursor:pointer;
      display:inline-flex;
      align-items:center;
      gap:6px;
    }

    #agenda-modal .btn.danger{
      border-color:#fecaca;
      background:#fef2f2;
      color:#7f1d1d;
    }

    #agenda-modal .btn.primary{
      background:var(--brand);
      color:#ffffff;
      border-color:var(--brand);
      font-weight:600;
    }

    /* Mensajito pequeño encima del calendario */
    #agenda-cal .helper{
      margin-bottom:10px;
      font-size:13px;
      color:var(--muted);
      display:flex;
      align-items:center;
      gap:6px;
      flex-wrap:wrap;
    }
    #agenda-cal .helper-dot{
      width:8px;
      height:8px;
      border-radius:999px;
      background:#f97316;
    }
  </style>

  <div class="wrap">
    <div class="top">
      <div class="top-left">
        <h1>Agenda de recordatorios</h1>
        <p>Programa recordatorios automáticos por correo y WhatsApp para tus citas, seguimientos o tareas internas.</p>
        <div class="badge">
          <span class="badge-dot"></span> Recordatorios activos
        </div>
      </div>
      <div class="actions">
        <button id="btn-new" class="btn primary">
          <i class="fa-solid fa-plus"></i>
          <span>Nuevo evento</span>
        </button>
      </div>
    </div>

    <div class="helper">
      <span class="helper-dot"></span>
      <span>Haz clic en un día para crear un evento. Haz clic sobre un evento para editarlo o arrástralo para cambiar la fecha.</span>
    </div>

    <div id="calendar"></div>
  </div>

  {{-- ===== Modal Crear/Editar ===== --}}
  <div id="agenda-modal-backdrop">
    <div id="agenda-modal" role="dialog" aria-modal="true">
      <div class="head">
        <div>
          <h3 id="modal-title">Nuevo evento</h3>
          <small>Estos datos se usarán para los recordatorios automáticos.</small>
        </div>
        <button id="btn-close" class="btn" style="border:none;background:transparent;font-size:18px">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
      <div class="body">
        <form id="agenda-form">
          @csrf
          <input type="hidden" name="id" id="ev-id">

          <label>
            <span>Título *</span>
            <span class="hint">Ej. “Llamar al cliente”, “Consulta de seguimiento”</span>
          </label>
          <input name="title" id="ev-title" required>

          <label>
            <span>Descripción</span>
            <span class="hint">Notas internas o detalles adicionales</span>
          </label>
          <textarea name="description" id="ev-desc" rows="3"></textarea>

          <div class="grid">
            <div>
              <label>
                <span>Fecha y hora *</span>
                <span class="hint">Hora local según zona horaria</span>
              </label>
              <input type="datetime-local" name="start_at" id="ev-start" required>
            </div>
            <div>
              <label>
                <span>Recordar (minutos antes) *</span>
                <span class="hint">Ej. 60 = 1 hora antes</span>
              </label>
              <input type="number" name="remind_offset_minutes" id="ev-offset" value="60" min="1" max="10080" required>
            </div>
          </div>

          <div class="grid">
            <div>
              <label><span>Repetición *</span></label>
              <select name="repeat_rule" id="ev-repeat">
                <option value="none">Sin repetición</option>
                <option value="daily">Diaria</option>
                <option value="weekly">Semanal</option>
                <option value="monthly">Mensual</option>
              </select>
            </div>
            <div>
              <label>
                <span>Zona horaria *</span>
                <span class="hint">Ej. America/Mexico_City</span>
              </label>
              <input name="timezone" id="ev-tz" value="America/Mexico_City" required>
            </div>
          </div>

          <div class="grid">
            <div>
              <label><span>Nombre del destinatario</span></label>
              <input name="attendee_name" id="ev-name">
            </div>
            <div>
              <label>
                <span>Email del destinatario</span>
                <span class="hint">Para enviar recordatorio por correo</span>
              </label>
              <input type="email" name="attendee_email" id="ev-email">
            </div>
          </div>

          <div class="grid">
            <div>
              <label>
                <span>Teléfono WhatsApp</span>
                <span class="hint">Incluye código de país, ej. 521XXXXXXXXXX</span>
              </label>
              <input name="attendee_phone" id="ev-phone">
            </div>
            <div>
              <label><span>Canales de recordatorio</span></label>
              <div class="channels">
                <label class="chip">
                  <input type="checkbox" name="send_email" id="ev-email-on" checked> Email
                </label>
                <label class="chip">
                  <input type="checkbox" name="send_whatsapp" id="ev-wa-on"> WhatsApp
                </label>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="foot">
        <button id="btn-delete" class="btn danger" style="display:none">
          <i class="fa-solid fa-trash-can"></i>
          <span>Eliminar</span>
        </button>
        <button id="btn-save" class="btn primary">
          <i class="fa-solid fa-floppy-disk"></i>
          <span>Guardar evento</span>
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const modalBackdrop = document.getElementById('agenda-modal-backdrop');
  const btnNew   = document.getElementById('btn-new');
  const btnClose = document.getElementById('btn-close');
  const btnSave  = document.getElementById('btn-save');
  const btnDelete= document.getElementById('btn-delete');

  const f = {
    id:      document.getElementById('ev-id'),
    title:   document.getElementById('ev-title'),
    desc:    document.getElementById('ev-desc'),
    start:   document.getElementById('ev-start'),
    offset:  document.getElementById('ev-offset'),
    repeat:  document.getElementById('ev-repeat'),
    tz:      document.getElementById('ev-tz'),
    name:    document.getElementById('ev-name'),
    email:   document.getElementById('ev-email'),
    phone:   document.getElementById('ev-phone'),
    emailOn: document.getElementById('ev-email-on'),
    waOn:    document.getElementById('ev-wa-on'),
  };

  function toLocalInputValue(date){
    if(!date) return '';
    const d = new Date(date);
    const y = d.getFullYear();
    const m = String(d.getMonth()+1).padStart(2,'0');
    const day = String(d.getDate()).padStart(2,'0');
    const h = String(d.getHours()).padStart(2,'0');
    const min = String(d.getMinutes()).padStart(2,'0');
    return `${y}-${m}-${day}T${h}:${min}`;
  }

  function openModal(mode='new', data=null) {
    modalBackdrop.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    if (mode === 'new') {
      document.getElementById('modal-title').textContent = 'Nuevo evento';
      btnDelete.style.display = 'none';
      f.id.value = '';
      f.title.value = '';
      f.desc.value = '';
      f.start.value = '';
      f.offset.value = 60;
      f.repeat.value = 'none';
      f.tz.value = 'America/Mexico_City';
      f.name.value = '';
      f.email.value = '';
      f.phone.value = '';
      f.emailOn.checked = true;
      f.waOn.checked = false;
    } else if (data) {
      document.getElementById('modal-title').textContent = 'Editar evento';
      btnDelete.style.display = 'inline-flex';
      f.id.value    = data.id;
      f.title.value = data.title || '';
      f.desc.value  = data.extendedProps?.description || '';
      f.start.value = toLocalInputValue(data.start);
      f.offset.value= data.extendedProps?.remind_offset_minutes ?? 60;
      f.repeat.value= data.extendedProps?.repeat_rule ?? 'none';
      f.tz.value    = data.extendedProps?.timezone ?? 'America/Mexico_City';
      f.name.value  = data.extendedProps?.attendee_name ?? '';
      f.email.value = data.extendedProps?.attendee_email ?? '';
      f.phone.value = data.extendedProps?.attendee_phone ?? '';
      f.emailOn.checked = !!data.extendedProps?.send_email;
      f.waOn.checked    = !!data.extendedProps?.send_whatsapp;
    }
  }

  function closeModal(){
    modalBackdrop.style.display='none';
    document.body.style.overflow='auto';
  }

  btnNew.addEventListener('click', ()=>openModal('new'));
  btnClose.addEventListener('click', closeModal);
  modalBackdrop.addEventListener('click', (e)=>{ if(e.target===modalBackdrop) closeModal(); });

  // ====== CALENDARIO ======
  const calendarEl = document.getElementById('calendar');
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    height: 'auto',
    locale: 'es',
    firstDay: 1,
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
    },
    selectable: true,
    editable: true,
    eventStartEditable: true,
    eventDurationEditable: false,
    eventDisplay: 'block',
    events: {
      url: "{{ route('agenda.feed') }}",
      failure(){ alert('No se pudo cargar la agenda.'); }
    },
    eventClick(info){
      openModal('edit', info.event.toPlainObject());
    },
    dateClick(info){
      openModal('new');
      // Prefijar hora 09:00 local para ese día
      f.start.value = info.dateStr + 'T09:00';
    },
    eventDrop: async (info) => {
      try{
        const startLocal = toLocalInputValue(info.event.start);
        const tz = info.event.extendedProps?.timezone || 'America/Mexico_City';

        const res = await fetch("{{ url('/agenda') }}/"+info.event.id+"/move",{
          method:'PUT',
          headers:{
            'X-CSRF-TOKEN':csrf,
            'Content-Type':'application/json',
            'Accept':'application/json'
          },
          body: JSON.stringify({
            start_at: startLocal,
            timezone: tz
          })
        });

        if(!res.ok){ throw new Error('Move failed'); }
      }catch(e){
        alert('No se pudo mover el evento. Se deshará el cambio.');
        info.revert();
      }
    },
    eventDidMount: function(info){
      // Asignar color distinto según el id del evento
      let id = parseInt(info.event.id || 0, 10);
      if (isNaN(id)) id = 0;
      const idx = (id % 5) + 1; // 1..5
      info.el.classList.add('agenda-event-tag-' + idx);
    }
  });

  calendar.render();

  // ====== GUARDAR (CREAR / EDITAR) ======
  btnSave.addEventListener('click', async ()=>{
    if(!f.start.value){
      alert('Por favor selecciona una fecha y hora.');
      return;
    }

    const payload = {
      title: f.title.value,
      description: f.desc.value,
      start_at: f.start.value, // el backend interpreta según timezone
      remind_offset_minutes: parseInt(f.offset.value||'60',10),
      repeat_rule: f.repeat.value,
      timezone: f.tz.value,
      attendee_name: f.name.value || null,
      attendee_email: f.email.value || null,
      attendee_phone: f.phone.value || null,
      send_email: f.emailOn.checked ? 1 : 0,
      send_whatsapp: f.waOn.checked ? 1 : 0,
    };

    try{
      let res;
      if(f.id.value){
        res = await fetch("{{ url('/agenda') }}/"+f.id.value, {
          method:'PUT',
          headers:{
            'X-CSRF-TOKEN':csrf,
            'Content-Type':'application/json',
            'Accept':'application/json'
          },
          body: JSON.stringify(payload)
        });
      }else{
        res = await fetch("{{ route('agenda.store') }}",{
          method:'POST',
          headers:{
            'X-CSRF-TOKEN':csrf,
            'Content-Type':'application/json',
            'Accept':'application/json'
          },
          body: JSON.stringify(payload)
        });
      }
      if(!res.ok) throw new Error('Error al guardar');

      closeModal();
      calendar.refetchEvents();
    }catch(e){
      alert('Revisa los campos. No se pudo guardar el evento.');
    }
  });

  // ====== ELIMINAR ======
  btnDelete.addEventListener('click', async ()=>{
    if(!f.id.value) return;
    if(!confirm('¿Eliminar este evento de la agenda?')) return;

    try{
      const res = await fetch("{{ url('/agenda') }}/"+f.id.value, {
        method:'DELETE',
        headers:{
          'X-CSRF-TOKEN':csrf,
          'Accept':'application/json'
        }
      });
      if(!res.ok) throw new Error('Delete failed');
      closeModal();
      calendar.refetchEvents();
    }catch(e){
      alert('No se pudo eliminar el evento.');
    }
  });
});
</script>
@endsection
