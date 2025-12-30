@extends('layouts.app')
@section('title','Agenda')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">

<div id="agenda-cal">
  <style>
    #agenda-cal{
      --ink:#0f172a;
      --muted:#6b7280;
      --line:#e5e7eb;
      --bg:#f4f5fb;
      --card:#ffffff;

      font-family:'Outfit',system-ui,-apple-system,blinkmacsystemfont,"Segoe UI",sans-serif;
      background:radial-gradient(circle at top,#eef2ff,#f9fafb);
      min-height:calc(100vh - 80px);
      padding:clamp(16px,3vw,28px);
    }
    #agenda-cal .wrap{max-width:1200px;margin:0 auto}

    /* ---------- HEADER ---------- */
    #agenda-cal .top{
      display:flex;
      flex-wrap:wrap;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      margin-bottom:14px;
    }
    #agenda-cal h1{
      margin:0;
      font-size:clamp(20px,2.4vw,28px);
      color:var(--ink);
      letter-spacing:.02em;
    }
    #agenda-cal .top-sub{
      font-size:13px;
      color:var(--muted);
    }
    #agenda-cal .actions{display:flex;gap:10px;flex-wrap:wrap}
    #agenda-cal .btn{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:9px 14px;
      border-radius:999px;
      border:1px solid #d1d5db;
      background:#fff;
      color:var(--ink);
      font-size:13px;
      text-decoration:none;
      cursor:pointer;
      transition:.18s ease all;
    }
    #agenda-cal .btn span.icon{
      display:inline-flex;
      width:18px;
      height:18px;
      border-radius:999px;
      align-items:center;
      justify-content:center;
      border:1px solid #dbeafe;
      background:#eff6ff;
      font-size:11px;
    }
    #agenda-cal .btn.primary{
      background:linear-gradient(120deg,#2563eb,#4f46e5);
      color:#f9fafb;
      border-color:transparent;
      box-shadow:0 16px 35px rgba(37,99,235,.35);
      font-weight:600;
    }
    #agenda-cal .btn:hover{
      transform:translateY(-1px);
      box-shadow:0 12px 28px rgba(15,23,42,.12);
    }
    #agenda-cal .btn.primary:hover{
      box-shadow:0 18px 40px rgba(37,99,235,.4);
    }

    /* ---------- FULLCALENDAR --------- */
    .fc{
      background:var(--card);
      border-radius:18px;
      border:1px solid var(--line);
      padding:6px;
      box-shadow:0 20px 55px rgba(15,23,42,.07);
    }
    .fc .fc-toolbar.fc-header-toolbar{
      padding:8px 10px 6px;
      margin-bottom:4px;
    }
    .fc .fc-toolbar-title{
      font-weight:700;
      color:var(--ink);
      font-size:16px;
    }
    .fc .fc-button{
      border-radius:999px;
      padding:4px 10px;
      border:1px solid #e5e7eb;
      background:#fff;
      color:#111827;
      font-size:12px;
      box-shadow:none;
    }
    .fc .fc-button-primary{
      background:#eff6ff;
      border-color:#dbeafe;
      color:#1d4ed8;
    }
    .fc .fc-button-primary:not(:disabled).fc-button-active{
      background:#2563eb;
      border-color:#2563eb;
      color:#f9fafb;
    }
    .fc .fc-daygrid-day-number{
      color:#4b5563;
      font-size:11px;
      padding:4px 6px;
    }
    .fc .fc-col-header-cell-cushion{
      padding:6px 4px;
      font-size:11px;
      font-weight:600;
      color:#6b7280;
      text-transform:uppercase;
      letter-spacing:.08em;
    }
    .fc .fc-day-today{
      background:rgba(219,234,254,.6);
    }

    /* ---------- EVENTOS (PILLS) ---------- */
    .agenda-event-pill{
      border-radius:999px !important;
      border-width:1px !important;
      padding:2px 6px !important;
      font-size:11px !important;
      line-height:1.25 !important;
      display:flex;
      align-items:center;
      gap:4px;
      overflow:hidden;
      white-space:nowrap;
    }
    .agenda-event-pill .time-dot{
      display:inline-block;
      width:6px;
      height:6px;
      border-radius:999px;
      margin-right:4px;
      flex-shrink:0;
    }
    .agenda-event-pill .title{
      font-weight:600;
      flex:1;
      min-width:0;
      text-overflow:ellipsis;
      overflow:hidden;
    }

    /* ---------- MODAL ---------- */
    #agenda-modal-backdrop{
      position:fixed;
      inset:0;
      display:none;
      align-items:center;
      justify-content:center;
      background:rgba(15,23,42,.32);
      z-index:50;
      backdrop-filter:blur(2px);
    }

    /* ✅ Modal con scroll interno + footer fijo */
    #agenda-modal{
      width:min(680px,92vw);
      background:#ffffff;
      border-radius:20px;
      border:1px solid #e5e7eb;
      box-shadow:0 26px 70px rgba(15,23,42,.35);
      overflow:hidden;

      max-height:min(84vh,760px);
      display:flex;
      flex-direction:column;
    }
    #agenda-modal .head{
      flex:0 0 auto;
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding:14px 18px;
      background:linear-gradient(120deg,#eff6ff,#ffffff);
      border-bottom:1px solid #e5e7eb;
    }
    #agenda-modal .head h3{
      margin:0;
      font-size:17px;
      color:var(--ink);
    }
    #agenda-modal .body{
      flex:1 1 auto;
      overflow:auto;
      -webkit-overflow-scrolling:touch;
      padding:18px;
      background:#ffffff;
    }

    #agenda-modal .grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:12px;
    }
    #agenda-modal label{
      display:block;
      font-weight:600;
      font-size:13px;
      margin:10px 0 5px;
      color:var(--ink);
    }
    #agenda-modal input,
    #agenda-modal select,
    #agenda-modal textarea{
      width:100%;
      padding:9px 11px;
      border-radius:12px;
      border:1px solid #e5e7eb;
      background:#f9fafb;
      font-size:13px;
    }
    #agenda-modal textarea{resize:vertical;min-height:70px}

    #agenda-modal .foot{
      flex:0 0 auto;
      position:sticky;
      bottom:0;
      z-index:2;

      display:flex;
      gap:10px;
      justify-content:flex-end;
      padding:14px 18px;
      background:#f9fafb;
      border-top:1px solid #e5e7eb;
      box-shadow:0 -10px 25px rgba(15,23,42,.08);
    }
    #agenda-modal .btn{
      border-radius:999px;
      padding:8px 14px;
      border:1px solid #d1d5db;
      background:#fff;
      font-size:13px;
      cursor:pointer;
    }
    #agenda-modal .btn.danger{
      border-color:#fecaca;
      background:#fef2f2;
      color:#b91c1c;
    }
    #agenda-modal .btn.primary{
      background:linear-gradient(120deg,#2563eb,#4f46e5);
      border-color:transparent;
      color:#f9fafb;
      font-weight:600;
    }

    /* ---------- Selector usuarios pro ---------- */
    .users-hint{
      font-size:12px;
      color:#6b7280;
      margin-top:6px;
      display:flex;
      gap:8px;
      flex-wrap:wrap;
      align-items:center;
    }
    .users-hint .badge{
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding:6px 10px;
      border-radius:999px;
      border:1px solid #e5e7eb;
      background:#fff;
      font-size:12px;
      color:#0f172a;
    }
    .users-hint .dot{
      width:8px;height:8px;border-radius:999px;background:#22c55e;display:inline-block;
    }
    .users-error{
      display:none;
      margin-top:8px;
      padding:10px 12px;
      border-radius:12px;
      border:1px solid #fecaca;
      background:#fef2f2;
      color:#991b1b;
      font-size:12px;
    }

    /* ---------- CHIPS (invitados) ---------- */
    .chips{
      margin-top:10px;
      display:flex;
      flex-direction:column;
      gap:10px;
      max-height:240px; /* ✅ evita que crezca demasiado */
      overflow:auto;    /* ✅ scroll propio */
      padding-right:4px;
    }
    .chip-row{
      display:flex;
      align-items:center;
      gap:10px;
      padding:10px 12px;
      border:1px solid #e5e7eb;
      background:#fff;
      border-radius:14px;
      box-shadow:0 10px 20px rgba(15,23,42,.06);
    }
    .chip-avatar{
      width:34px;height:34px;border-radius:999px;
      display:flex;align-items:center;justify-content:center;
      font-weight:800;font-size:12px;
      background:#2563eb;color:#fff;
      flex-shrink:0;
    }
    .chip-meta{flex:1;min-width:0}
    .chip-name{
      font-weight:700;
      color:#0f172a;
      font-size:13px;
      line-height:1.1;
    }
    .chip-sub{
      color:#6b7280;
      font-size:12px;
      margin-top:2px;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }
    .chip-x{
      border:none;
      background:#f3f4f6;
      color:#111827;
      width:30px;height:30px;
      border-radius:999px;
      cursor:pointer;
      font-size:16px;
      line-height:1;
      display:flex;
      align-items:center;
      justify-content:center;
    }
    .chip-x:hover{background:#fee2e2;color:#b91c1c}

    @media (max-width:768px){
      #agenda-cal{padding:14px}
      #agenda-modal .grid{grid-template-columns:1fr}
      .fc{padding:4px}
      #agenda-modal{max-height:88vh}
      .chips{max-height:220px}
    }
  </style>

  <div class="wrap">
    <div class="top">
      <div>
        <h1>Agenda de recordatorios</h1>
        <div class="top-sub">
          Programa llamadas, seguimientos y tareas. Los recordatorios se envían por <b>Email + WhatsApp</b> a los usuarios seleccionados.
        </div>
      </div>
      <div class="actions">
        <button id="btn-new" class="btn primary">
          <span class="icon">+</span>
          <span>Nuevo evento</span>
        </button>
      </div>
    </div>

    <div id="calendar"></div>
  </div>

  {{-- ===== Modal Crear/Editar ===== --}}
  <div id="agenda-modal-backdrop">
    <div id="agenda-modal" role="dialog" aria-modal="true">
      <div class="head">
        <h3 id="modal-title">Nuevo evento</h3>
        <button id="btn-close" class="btn" style="border:none;background:transparent;font-size:18px;">✕</button>
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
              <label>Recordar (minutos antes) *</label>
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
              <label>Invitados *</label>

              <!-- SOLO NOMBRES en el modal -->
              <select id="ev-users" size="7" style="height:auto"></select>

              <div class="users-hint">
                <span class="badge"><span class="dot"></span> Se enviará por Email + WhatsApp</span>
                <span class="badge" id="users-count">0 usuario(s)</span>
              </div>

              <div class="users-error" id="users-error">
                Selecciona al menos 1 usuario para enviar la notificación.
              </div>

              <!-- chips: nombre + email + teléfono -->
              <div id="ev-chips" class="chips"></div>

              <input type="hidden" id="ev-user-ids" name="user_ids" value="[]">
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

  const usersSelect   = document.getElementById('ev-users');
  const usersCount    = document.getElementById('users-count');
  const usersError    = document.getElementById('users-error');
  const chipsWrap     = document.getElementById('ev-chips');
  const userIdsHidden = document.getElementById('ev-user-ids');

  const f = {
    id:      document.getElementById('ev-id'),
    title:   document.getElementById('ev-title'),
    desc:    document.getElementById('ev-desc'),
    start:   document.getElementById('ev-start'),
    offset:  document.getElementById('ev-offset'),
    repeat:  document.getElementById('ev-repeat'),
  };

  let USERS_CACHE = [];        // [{id,name,email,phone}]
  let selectedIds = new Set(); // ids seleccionados

  function initials(name=''){
    const parts = String(name).trim().split(/\s+/).filter(Boolean);
    const a = (parts[0]?.[0] || '').toUpperCase();
    const b = (parts[1]?.[0] || '').toUpperCase();
    return (a + b) || 'U';
  }

  function syncHidden(){
    const arr = Array.from(selectedIds);
    userIdsHidden.value = JSON.stringify(arr);
    usersCount.textContent = `${arr.length} usuario(s)`;
    usersError.style.display = arr.length ? 'none' : 'none';
  }

  function renderChips(){
    chipsWrap.innerHTML = '';
    const ids = Array.from(selectedIds);

    ids.forEach(id => {
      const u = USERS_CACHE.find(x => Number(x.id) === Number(id));
      if (!u) return;

      const chip = document.createElement('div');
      chip.className = 'chip-row';
      chip.dataset.id = id;

      const sub = [u.email, u.phone].filter(Boolean).join(' • ');

      chip.innerHTML = `
        <div class="chip-avatar">${initials(u.name)}</div>
        <div class="chip-meta">
          <div class="chip-name">${u.name || ''}</div>
          <div class="chip-sub">${sub}</div>
        </div>
        <button type="button" class="chip-x" aria-label="Quitar">×</button>
      `;

      chip.querySelector('.chip-x').addEventListener('click', () => {
        selectedIds.delete(Number(id));
        const opt = usersSelect.querySelector(`option[value="${id}"]`);
        if (opt) opt.disabled = false;
        renderChips();
        syncHidden();
      });

      chipsWrap.appendChild(chip);
    });
  }

  function addUserToSelection(id){
    id = Number(id);
    if (!id) return;
    if (selectedIds.has(id)) return;

    selectedIds.add(id);

    const opt = usersSelect.querySelector(`option[value="${id}"]`);
    if (opt) {
      opt.disabled = true;
      usersSelect.value = ''; // reset
    }

    renderChips();
    syncHidden();
  }

  function setSelectedUserIds(ids){
    selectedIds = new Set((ids || []).map(n => Number(n)).filter(Boolean));

    Array.from(usersSelect.options).forEach(opt => {
      if (!opt.value) return;
      opt.disabled = false;
    });

    selectedIds.forEach(id => {
      const opt = usersSelect.querySelector(`option[value="${id}"]`);
      if (opt) opt.disabled = true;
    });

    renderChips();
    syncHidden();
  }

  usersSelect.addEventListener('change', () => {
    addUserToSelection(usersSelect.value);
  });

  async function loadUsersOnce(){
    if (USERS_CACHE.length) return;

    usersSelect.innerHTML = `<option value="" disabled selected>Cargando usuarios...</option>`;

    try{
      const res = await fetch("{{ route('agenda.users') }}", {
        method:'GET',
        credentials:'same-origin',
        headers:{'Accept':'application/json'}
      });

      const ct = res.headers.get('content-type') || '';
      if(!res.ok) throw new Error('HTTP ' + res.status);
      if(!ct.includes('application/json')) throw new Error('Respuesta no JSON');

      USERS_CACHE = await res.json();

      usersSelect.innerHTML = `<option value="" selected disabled>Selecciona un usuario...</option>`;
      USERS_CACHE.forEach(u => {
        const opt = document.createElement('option');
        opt.value = u.id;
        opt.textContent = (u.name || 'Sin nombre'); // ✅ solo nombres
        usersSelect.appendChild(opt);
      });

      syncHidden();
    }catch(e){
      console.error(e);
      usersSelect.innerHTML = `<option value="" disabled>Error cargando usuarios</option>`;
    }
  }

  function openModal(mode='new', data=null) {
    modalBackdrop.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    loadUsersOnce();

    if (mode === 'new') {
      document.getElementById('modal-title').textContent = 'Nuevo evento';
      btnDelete.style.display = 'none';
      f.id.value = '';
      f.title.value = '';
      f.desc.value = '';
      f.start.value = '';
      f.offset.value = 60;
      f.repeat.value = 'none';
      setSelectedUserIds([]);
    } else if (data) {
      document.getElementById('modal-title').textContent = 'Editar evento';
      btnDelete.style.display = 'inline-flex';

      f.id.value = data.id;
      f.title.value = data.title || '';
      f.desc.value  = data.extendedProps?.description || '';

      const dt = new Date(data.start);
      dt.setMinutes(dt.getMinutes() - dt.getTimezoneOffset());
      f.start.value = dt.toISOString().slice(0,16);

      f.offset.value = data.extendedProps?.remind_offset_minutes ?? 60;
      f.repeat.value = data.extendedProps?.repeat_rule ?? 'none';

      const ids = data.extendedProps?.user_ids || [];
      setTimeout(() => setSelectedUserIds(ids), 0);
    }
  }

  function closeModal() {
    modalBackdrop.style.display = 'none';
    document.body.style.overflow = 'auto';
    usersError.style.display = 'none';
  }

  btnNew.addEventListener('click', () => openModal('new'));
  btnClose.addEventListener('click', closeModal);
  modalBackdrop.addEventListener('click', (e) => {
    if (e.target === modalBackdrop) closeModal();
  });

  const calendarEl = document.getElementById('calendar');

  const palette = [
    { bg: '#fee2e2', border: '#fecaca' },
    { bg: '#dbeafe', border: '#bfdbfe' },
    { bg: '#dcfce7', border: '#bbf7d0' },
    { bg: '#fef3c7', border: '#fde68a' },
    { bg: '#ede9fe', border: '#ddd6fe' },
    { bg: '#cffafe', border: '#a5f3fc' },
  ];

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
    editable: false,
    eventStartEditable: false,
    eventDurationEditable: false,

    events: {
      url: "{{ route('agenda.feed') }}",
      failure() { alert('No se pudo cargar la agenda.'); }
    },

    dateClick(info) {
      openModal('new');
      const dt = new Date(info.dateStr + 'T09:00');
      dt.setMinutes(dt.getMinutes() - dt.getTimezoneOffset());
      f.start.value = dt.toISOString().slice(0,16);
    },

    eventClick(info) {
      openModal('edit', info.event.toPlainObject());
    },

    eventDidMount(info) {
      const idNum = parseInt(info.event.id || '0', 10);
      const color = palette[idNum % palette.length];
      const el = info.el;

      el.classList.add('agenda-event-pill');
      el.style.backgroundColor = color.bg;
      el.style.borderColor = color.border;
      el.style.color = '#0f172a';

      const title = info.event.title || '';
      const timeText = info.timeText || '';
      el.innerHTML = `
        <span class="time-dot" style="background:${color.border};"></span>
        <span class="title">${timeText ? timeText + ' ' : ''}${title}</span>
      `;
    },
  });

  calendar.render();

  // Guardar (crear/editar)
  btnSave.addEventListener('click', async () => {
    let userIds = [];
    try { userIds = JSON.parse(userIdsHidden.value || '[]'); } catch(e) { userIds = []; }

    if (!userIds.length) {
      usersError.style.display = 'block';
      usersSelect.focus();
      return;
    }

    const payload = {
      title: f.title.value,
      description: f.desc.value,
      start_at: new Date(f.start.value).toISOString(),
      remind_offset_minutes: parseInt(f.offset.value || '60', 10),
      repeat_rule: f.repeat.value,

      user_ids: userIds,

      send_email: 1,
      send_whatsapp: 1,
      timezone: 'America/Mexico_City',
    };

    try {
      if (f.id.value) {
        const res = await fetch("{{ url('/agenda') }}/" + f.id.value, {
          method: 'PUT',
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify(payload),
        });
        if (!res.ok) throw new Error('Error al actualizar');
      } else {
        const res = await fetch("{{ route('agenda.store') }}", {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify(payload),
        });
        if (!res.ok) throw new Error('Error al guardar');
      }

      closeModal();
      calendar.refetchEvents();
    } catch (e) {
      console.error(e);
      alert('No se pudo guardar el evento. Revisa los campos.');
    }
  });

  // Eliminar
  btnDelete.addEventListener('click', async () => {
    if (!f.id.value) return;
    if (!confirm('¿Eliminar el evento de forma permanente?')) return;

    try {
      const res = await fetch("{{ url('/agenda') }}/" + f.id.value, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
        },
      });
      if (!res.ok) throw new Error('Error al eliminar');
      closeModal();
      calendar.refetchEvents();
    } catch (e) {
      console.error(e);
      alert('No se pudo eliminar el evento.');
    }
  });
});
</script>
@endsection
