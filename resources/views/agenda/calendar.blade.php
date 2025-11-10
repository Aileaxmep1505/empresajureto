@extends('layouts.app')
@section('title','Agenda')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">

<div id="agenda-cal">
  <style>
    #agenda-cal{
      --ink:#0e1726; --muted:#6b7280; --line:#e8eef6; --bg:#f6f8fb; --card:#fff;
      --brand:#6ea8fe; --brand-ink:#0b1220; --ok:#16a34a; --danger:#ef4444;
      font-family:'Outfit', system-ui; background:linear-gradient(180deg,#fbfdff,#f6f8fb);
      min-height: calc(100vh - 80px);
      padding: clamp(16px, 3vw, 28px);
    }
    #agenda-cal .wrap{max-width:1200px;margin:0 auto}
    #agenda-cal .top{
      display:flex;align-items:center;justify-content:space-between;margin-bottom:14px
    }
    #agenda-cal h1{margin:0; font-size:clamp(20px,2.4vw,28px); color:var(--ink)}
    #agenda-cal .actions{display:flex; gap:10px}
    #agenda-cal .btn{
      display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:12px;
      background:var(--card); border:1px solid var(--line); color:var(--ink); text-decoration:none
    }
    #agenda-cal .btn.primary{
      background:var(--brand); color:var(--brand-ink); font-weight:800;
      box-shadow:0 14px 32px rgba(110,168,254,.35)
    }

    /* FullCalendar theming ligero */
    .fc{background:var(--card); border:1px solid var(--line); border-radius:16px; padding:6px;
        box-shadow:0 18px 52px rgba(15,23,42,.06)}
    .fc .fc-toolbar-title{font-weight:800; color:var(--ink)}
    .fc .fc-button{
      border-radius:10px; border:1px solid var(--line); background:#fff; color:var(--ink)
    }
    .fc .fc-button-primary{background:var(--brand); border-color:transparent; color:var(--brand-ink)}
    .fc .fc-daygrid-day-number{color:#0f172a}
    .fc .fc-daygrid-event{
      border-radius:10px; padding:2px 6px; border:none;
      background:linear-gradient(180deg,#eaf3ff,#e2ecff)
    }
    .fc .fc-event-title{font-weight:600}

    /* Modal */
    #agenda-modal-backdrop{position:fixed; inset:0; display:none; align-items:center; justify-content:center;
      background:rgba(2,8,23,.35); z-index:50}
    #agenda-modal{width:min(680px,92vw); background:#fff; border-radius:20px; border:1px solid var(--line);
      box-shadow:0 24px 60px rgba(2,8,23,.25); overflow:hidden}
    #agenda-modal .head{display:flex; align-items:center; justify-content:space-between; padding:16px 18px; background:linear-gradient(180deg,#f7fbff,#fff)}
    #agenda-modal .head h3{margin:0; font-size:18px}
    #agenda-modal .body{padding:18px}
    #agenda-modal .grid{display:grid; grid-template-columns:1fr 1fr; gap:12px}
    #agenda-modal label{display:block; font-weight:700; margin:10px 0 6px; color:var(--ink)}
    #agenda-modal input,#agenda-modal select,#agenda-modal textarea{
      width:100%; padding:10px 12px; border:1px solid var(--line); border-radius:12px; background:#fff; color:var(--ink)
    }
    #agenda-modal .foot{display:flex; gap:10px; justify-content:flex-end; padding:16px 18px; background:#fafcff; border-top:1px solid var(--line)}
    #agenda-modal .chip{display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:999px; border:1px solid var(--line); background:#fff}
    #agenda-modal .btn{padding:10px 14px; border-radius:12px; border:1px solid var(--line); background:#fff; color:var(--ink); text-decoration:none}
    #agenda-modal .btn.danger{border-color:#fecaca; background:#fff5f5; color:#7f1d1d}
    #agenda-modal .btn.primary{background:var(--brand); color:var(--brand-ink); font-weight:800}
  </style>

  <div class="wrap">
    <div class="top">
      <h1>Agenda (Calendario)</h1>
      <div class="actions">
        <button id="btn-new" class="btn primary">➕ Nuevo evento</button>
      </div>
    </div>

    <div id="calendar"></div>
  </div>

  {{-- ===== Modal Crear/Editar ===== --}}
  <div id="agenda-modal-backdrop">
    <div id="agenda-modal" role="dialog" aria-modal="true">
      <div class="head">
        <h3 id="modal-title">Nuevo evento</h3>
        <button id="btn-close" class="btn" style="border:none;background:transparent;font-size:22px">✕</button>
      </div>
      <div class="body">
        <form id="agenda-form">
          @csrf
          <input type="hidden" name="id" id="ev-id">

          <label>Título *</label>
          <input name="title" id="ev-title" required>

          <label>Descripción</label>
          <textarea name="description" id="ev-desc" rows="3"></textarea>

          <div class="grid">
            <div>
              <label>Fecha y hora *</label>
              <input type="datetime-local" name="start_at" id="ev-start" required>
            </div>
            <div>
              <label>Recordar (min antes) *</label>
              <input type="number" name="remind_offset_minutes" id="ev-offset" value="60" min="1" max="10080" required>
            </div>
          </div>

          <div class="grid">
            <div>
              <label>Repetición *</label>
              <select name="repeat_rule" id="ev-repeat">
                <option value="none">Sin repetición</option>
                <option value="daily">Diaria</option>
                <option value="weekly">Semanal</option>
                <option value="monthly">Mensual</option>
              </select>
            </div>
            <div>
              <label>Zona horaria *</label>
              <input name="timezone" id="ev-tz" value="America/Mexico_City" required>
            </div>
          </div>

          <div class="grid">
            <div>
              <label>Nombre destinatario</label>
              <input name="attendee_name" id="ev-name">
            </div>
            <div>
              <label>Email destinatario</label>
              <input type="email" name="attendee_email" id="ev-email">
            </div>
          </div>

          <div class="grid">
            <div>
              <label>Teléfono WhatsApp (con código país)</label>
              <input name="attendee_phone" id="ev-phone">
            </div>
            <div>
              <label>Canales</label>
              <div style="display:flex; gap:16px; align-items:center; margin-top:8px;">
                <label class="chip"><input type="checkbox" name="send_email" id="ev-email-on" checked> Email</label>
                <label class="chip"><input type="checkbox" name="send_whatsapp" id="ev-wa-on"> WhatsApp</label>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="foot">
        <button id="btn-delete" class="btn danger" style="display:none">Eliminar</button>
        <button id="btn-save" class="btn primary">Guardar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const modalBackdrop = document.getElementById('agenda-modal-backdrop');
  const btnNew = document.getElementById('btn-new');
  const btnClose = document.getElementById('btn-close');
  const btnSave = document.getElementById('btn-save');
  const btnDelete = document.getElementById('btn-delete');

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
    } else {
      document.getElementById('modal-title').textContent = 'Editar evento';
      btnDelete.style.display = 'inline-flex';
      // Cargar datos
      f.id.value = data.id;
      f.title.value = data.title || '';
      f.desc.value = data.extendedProps?.description || '';
      // convertir ISO a datetime-local
      const dt = new Date(data.start);
      dt.setMinutes(dt.getMinutes() - dt.getTimezoneOffset());
      f.start.value = dt.toISOString().slice(0,16);
      f.offset.value = data.extendedProps?.remind_offset_minutes ?? 60;
      f.repeat.value = data.extendedProps?.repeat_rule ?? 'none';
      f.tz.value = data.extendedProps?.timezone ?? 'America/Mexico_City';
      f.name.value = data.extendedProps?.attendee_name ?? '';
      f.email.value = data.extendedProps?.attendee_email ?? '';
      f.phone.value = data.extendedProps?.attendee_phone ?? '';
      f.emailOn.checked = !!data.extendedProps?.send_email;
      f.waOn.checked = !!data.extendedProps?.send_whatsapp;
    }
  }
  function closeModal(){ modalBackdrop.style.display='none'; document.body.style.overflow='auto'; }

  btnNew.addEventListener('click', ()=>openModal('new'));
  btnClose.addEventListener('click', closeModal);
  modalBackdrop.addEventListener('click', (e)=>{ if(e.target===modalBackdrop) closeModal(); });

  // Calendar
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
    eventDurationEditable: false, // si quieres redimensionar por duración, cámbialo a true y agrega campo "end_at"
    eventClick(info){
      openModal('edit', info.event.toPlainObject());
    },
    dateClick(info){
      openModal('new');
      // prefijar fecha seleccionada
      const dt = new Date(info.dateStr + 'T09:00');
      dt.setMinutes(dt.getMinutes() - dt.getTimezoneOffset());
      f.start.value = dt.toISOString().slice(0,16);
    },
    events: {
      url: "{{ route('agenda.feed') }}",
      failure(){ alert('No se pudo cargar la agenda.'); }
    },
    eventDrop: async (info) => {
      // Drag & drop → actualizar start_at
      try{
        const startISO = info.event.start.toISOString();
        const res = await fetch("{{ url('/agenda') }}/"+info.event.id+"/move",{
          method:'PUT',
          headers:{'X-CSRF-TOKEN':csrf,'Content-Type':'application/json','Accept':'application/json'},
          body: JSON.stringify({ start_at: startISO })
        });
        if(!res.ok){ throw new Error('Move failed'); }
      }catch(e){
        alert('No se pudo mover el evento.');
        info.revert();
      }
    },
  });
  calendar.render();

  // Guardar (crear/editar)
  btnSave.addEventListener('click', async ()=>{
    const payload = {
      title: f.title.value,
      description: f.desc.value,
      start_at: new Date(f.start.value).toISOString(),
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
      if(f.id.value){
        const res = await fetch("{{ url('/agenda') }}/"+f.id.value, {
          method:'PUT',
          headers:{'X-CSRF-TOKEN':csrf,'Content-Type':'application/json','Accept':'application/json'},
          body: JSON.stringify(payload)
        });
        if(!res.ok) throw new Error('Update failed');
      }else{
        const res = await fetch("{{ route('agenda.store') }}",{
          method:'POST',
          headers:{'X-CSRF-TOKEN':csrf,'Content-Type':'application/json','Accept':'application/json'},
          body: JSON.stringify(payload)
        });
        if(!res.ok) throw new Error('Store failed');
      }
      closeModal();
      calendar.refetchEvents();
    }catch(e){
      alert('Revisa los campos. No se pudo guardar.');
    }
  });

  // Eliminar
  btnDelete.addEventListener('click', async ()=>{
    if(!f.id.value) return;
    if(!confirm('¿Eliminar evento permanentemente?')) return;
    try{
      const res = await fetch("{{ url('/agenda') }}/"+f.id.value, {
        method:'DELETE',
        headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'}
      });
      if(!res.ok) throw new Error('Delete failed');
      closeModal();
      calendar.refetchEvents();
    }catch(e){
      alert('No se pudo eliminar.');
    }
  });
});
</script>
@endsection
