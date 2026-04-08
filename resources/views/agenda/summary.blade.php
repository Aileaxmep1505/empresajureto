@extends('layouts.app')
@section('title', 'Resumen de Agenda')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<div id="agenda-summary">
  <style>
    #agenda-summary .agenda-layout{
      display:flex;
      gap:18px;
      align-items:flex-start;
    }

    #agenda-summary .agenda-main{
      min-width:0;
      flex:1;
    }

    @media (max-width: 900px){
      #agenda-summary .agenda-layout{
        flex-direction:column;
      }
    }
  </style>

  <div class="agenda-layout">
    @include('agenda.partials.sidebar')

    <div class="agenda-main">
  <style>
    #agenda-summary{
      --bg:#f4f5f7;
      --card:#ffffff;
      --ink:#111827;
      --muted:#667085;
      --line:#e5e7eb;
      --soft:#f8fafc;
      --primary:#4f46e5;
      --primary-dark:#4338ca;

      --indigo:#6366f1;
      --emerald:#10b981;
      --violet:#8b5cf6;
      --rose:#f43f5e;
      --sky:#0ea5e9;
      --amber:#f59e0b;

      min-height:calc(100vh - 80px);
      padding:18px;
      font-family:'Inter',system-ui,-apple-system,sans-serif;
      color:var(--ink);
    }

    #agenda-summary *{ box-sizing:border-box; }

    #agenda-summary .wrap{
      max-width:1450px;
      margin:0 auto;
    }

    #agenda-summary .topbar{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:14px;
      flex-wrap:wrap;
      margin-bottom:20px;
    }

    #agenda-summary .left{
      display:flex;
      align-items:center;
      gap:10px;
      flex-wrap:wrap;
    }

    #agenda-summary .title{
      margin:0;
      font-size:24px;
      font-weight:700;
      letter-spacing:-.02em;
      color:#111827;
    }

    #agenda-summary .sub{
      font-size:14px;
      color:var(--muted);
      margin-top:4px;
    }

    #agenda-summary .actions{
      display:flex;
      align-items:center;
      gap:10px;
      flex-wrap:wrap;
    }

    #agenda-summary .btn,
    #agenda-summary .icon-btn{
      border:none;
      outline:none;
      cursor:pointer;
      transition:.18s ease;
      font-family:inherit;
    }

    #agenda-summary .btn{
      height:38px;
      padding:0 16px;
      border-radius:8px;
      font-size:13px;
      font-weight:600;
      display:inline-flex;
      align-items:center;
      gap:8px;
      text-decoration:none;
    }

    #agenda-summary .btn.secondary{
      background:#fff;
      color:#334155;
      border:1px solid var(--line);
      box-shadow:0 1px 2px rgba(0,0,0,0.05);
    }

    #agenda-summary .btn.secondary:hover{
      background:#f8fafc;
    }

    #agenda-summary .btn.primary{
      background:var(--primary);
      color:#fff;
      box-shadow:0 4px 12px rgba(79,70,229,.15);
    }

    #agenda-summary .btn.primary:hover{
      background:var(--primary-dark);
    }

    #agenda-summary .grid{
      display:grid;
      grid-template-columns:minmax(0,1.6fr) minmax(320px,1fr);
      gap:24px;
      align-items:start;
    }

    #agenda-summary .card{
      background:var(--card);
      border:1px solid var(--line);
      border-radius:16px;
      overflow:hidden;
      box-shadow:0 1px 3px rgba(0,0,0,.04);
    }

    #agenda-summary .card-head{
      padding:20px 24px;
      border-bottom:1px solid var(--line);
      font-size:16px;
      font-weight:600;
      color:#111827;
    }

    #agenda-summary .list{
      display:flex;
      flex-direction:column;
    }

    #agenda-summary .event-row{
      display:grid;
      grid-template-columns:24px 4px minmax(0,1fr) auto;
      align-items:center;
      gap:16px;
      padding:16px 24px;
      border-bottom:1px solid var(--line);
      cursor:pointer;
      transition:.15s ease;
    }

    #agenda-summary .event-row:last-child{
      border-bottom:none;
    }

    #agenda-summary .event-row:hover{
      background:#f8fafc;
    }

    #agenda-summary .check{
      width:22px;
      height:22px;
      border-radius:999px;
      border:1.5px solid #d1d5db;
      background:#fff;
      cursor:pointer;
      transition:.18s ease;
      position:relative;
      flex-shrink:0;
    }

    #agenda-summary .check.done{
      border-color:#10b981;
      background:#10b981;
    }

    #agenda-summary .check.done::after{
      content:"";
      position:absolute;
      left:6px;
      top:3px;
      width:5px;
      height:10px;
      border:solid #fff;
      border-width:0 2px 2px 0;
      transform:rotate(45deg);
    }

    #agenda-summary .bar{
      width:4px;
      height:36px;
      border-radius:4px;
    }

    #agenda-summary .meta{
      min-width:0;
    }

    #agenda-summary .event-title{
      font-size:15px;
      font-weight:500;
      color:#111827;
      line-height:1.2;
      margin-bottom:6px;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }

    #agenda-summary .event-sub{
      display:flex;
      flex-wrap:wrap;
      gap:14px;
      color:#667085;
      font-size:13px;
      font-weight:400;
    }

    #agenda-summary .event-sub span{
      display:inline-flex;
      align-items:center;
      gap:4px;
    }
    
    #agenda-summary .event-sub svg {
      width: 14px;
      height: 14px;
      color: #94a3b8;
    }

    #agenda-summary .badge{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:4px 10px;
      border-radius:6px;
      font-size:12px;
      font-weight:600;
      white-space:nowrap;
    }

    #agenda-summary .badge.urgent{
      background:#fee2e2;
      color:#ef4444;
    }

    #agenda-summary .today-body{
      padding:24px;
    }

    #agenda-summary .today-empty,
    #agenda-summary .empty{
      color:#94a3b8;
      font-size:14px;
      font-weight:500;
      text-align:center;
      padding:30px 16px;
    }

    #agenda-summary .timeline{
      position:relative;
      padding-left:24px;
    }

    #agenda-summary .timeline::before{
      content:"";
      position:absolute;
      left:5px;
      top:12px;
      bottom:0;
      width:2px;
      background:#e2e8f0;
    }

    #agenda-summary .timeline-item{
      position:relative;
      padding-bottom:20px;
      cursor:pointer;
    }

    #agenda-summary .timeline-item:last-child{
      padding-bottom:0;
    }
    
    #agenda-summary .timeline-item:last-child::before {
      display: none;
    }

    #agenda-summary .timeline-dot{
      position:absolute;
      left:-24px;
      top:4px;
      width:12px;
      height:12px;
      border-radius:999px;
      border:2px solid #fff;
      box-shadow:0 0 0 1px #e2e8f0;
    }

    #agenda-summary .timeline-time{
      color:#64748b;
      font-size:13px;
      margin-bottom:6px;
    }

    #agenda-summary .timeline-title{
      color:#111827;
      font-size:15px;
      font-weight:600;
      line-height:1.2;
      margin-bottom:4px;
    }

    #agenda-summary .timeline-loc{
      color:#64748b;
      font-size:13px;
    }

    #agenda-summary .c-indigo{ background:var(--indigo); }
    #agenda-summary .c-emerald{ background:var(--emerald); }
    #agenda-summary .c-violet{ background:var(--violet); }
    #agenda-summary .c-rose{ background:var(--rose); }
    #agenda-summary .c-sky{ background:var(--sky); }
    #agenda-summary .c-amber{ background:var(--amber); }

    /* modals */
    #agenda-summary .overlay{
      position:fixed;
      inset:0;
      display:none;
      align-items:center;
      justify-content:center;
      background:rgba(15,23,42,.42);
      backdrop-filter:blur(4px);
      z-index:9999;
      padding:18px;
    }

    #agenda-summary .modal{
      width:min(620px,100%);
      max-height:min(90vh,900px);
      background:#fff;
      border-radius:18px;
      overflow:hidden;
      border:1px solid var(--line);
      box-shadow:0 30px 70px rgba(15,23,42,.24);
      display:flex;
      flex-direction:column;
      position:relative;
    }

    /* ESTILOS NUEVOS PARA EL MODAL DE SHOW (VISTA) */
    #agenda-summary .modal-top-bar {
      position:absolute;
      top:0; left:0; right:0;
      height:6px;
      background:var(--indigo);
    }

    #agenda-summary .show-content {
      padding:32px 28px 24px;
      overflow:auto;
    }

    #agenda-summary .show-header {
      display:flex;
      align-items:flex-start;
      gap:16px;
    }

    #agenda-summary .show-icon-box {
      width:42px;
      height:42px;
      border-radius:10px;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      flex-shrink:0;
    }

    #agenda-summary .show-icon-box svg {
      width:24px;
      height:24px;
    }

    #agenda-summary .show-title-wrapper {
      flex:1;
      min-width:0;
    }

    #agenda-summary .show-title-wrapper h3 {
      margin:0;
      font-size:22px;
      font-weight:700;
      color:#111827;
      line-height:1.3;
    }

    #agenda-summary .show-desc {
      font-size:15px;
      color:#64748b;
      margin-top:10px;
      line-height:1.5;
    }

    #agenda-summary .show-details-box {
      background:#f8fafc;
      border:1px solid #f1f5f9;
      border-radius:12px;
      padding:18px 20px;
      margin-top:24px;
      display:flex;
      flex-direction:column;
      gap:14px;
    }

    #agenda-summary .detail-row {
      display:flex;
      align-items:center;
      gap:12px;
      font-size:15px;
      font-weight:500;
      color:#1e293b;
    }

    #agenda-summary .detail-row svg {
      width:18px;
      height:18px;
      color:#64748b;
    }

    #agenda-summary .show-badges {
      display:flex;
      align-items:center;
      gap:12px;
      margin-top:20px;
      flex-wrap:wrap;
    }

    #agenda-summary .show-badge {
      padding:6px 14px;
      border-radius:6px;
      font-size:13px;
      font-weight:600;
    }

    #agenda-summary .show-badge.outline {
      border:1px solid #e2e8f0;
      color:#475569;
    }

    #agenda-summary .show-badge.warning {
      background:#fffbeb;
      color:#d97706;
    }
    
    #agenda-summary .show-badge.danger {
      background:#fef2f2;
      color:#ef4444;
    }

    #agenda-summary .show-badge.info {
      background:#f0f9ff;
      color:#0284c7;
    }

    /* ESTILOS ORIGINALES DEL MODAL FORM */
    #agenda-summary .modal-head{
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding:18px 22px;
      border-bottom:1px solid var(--line);
    }

    #agenda-summary .modal-head h3{
      margin:0;
      font-size:20px;
      font-weight:800;
    }

    #agenda-summary .x{
      width:34px;
      height:34px;
      border:none;
      background:transparent;
      border-radius:10px;
      font-size:24px;
      cursor:pointer;
      color:#667085;
      display:flex;
      align-items:center;
      justify-content:center;
      flex-shrink:0;
    }

    #agenda-summary .x:hover{
      background:#f8fafc;
    }

    #agenda-summary .modal-body{
      padding:20px 22px;
      overflow:auto;
    }

    #agenda-summary .field{
      margin-bottom:16px;
    }

    #agenda-summary .field label{
      display:block;
      margin-bottom:6px;
      font-size:13px;
      font-weight:700;
      color:#475569;
    }

    #agenda-summary .input,
    #agenda-summary .select,
    #agenda-summary .textarea{
      width:100%;
      border:1px solid var(--line);
      background:#fff;
      border-radius:10px;
      padding:10px 12px;
      font-size:14px;
      font-family:inherit;
      outline:none;
    }

    #agenda-summary .textarea{
      min-height:84px;
      resize:vertical;
    }

    #agenda-summary .grid-2{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:14px;
    }

    #agenda-summary .colors{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
    }

    #agenda-summary .color{
      width:28px;
      height:28px;
      border-radius:999px;
      border:none;
      cursor:pointer;
      position:relative;
    }

    #agenda-summary .color.active::after{
      content:"";
      position:absolute;
      inset:-4px;
      border-radius:999px;
      border:2px solid currentColor;
      opacity:.35;
    }

    #agenda-summary .switch{
      width:46px;
      height:26px;
      border-radius:999px;
      background:#dbe3ef;
      position:relative;
      border:none;
      cursor:pointer;
    }

    #agenda-summary .switch::after{
      content:"";
      position:absolute;
      top:3px;
      left:3px;
      width:20px;
      height:20px;
      border-radius:999px;
      background:#fff;
      box-shadow:0 2px 6px rgba(0,0,0,.14);
      transition:.18s ease;
    }

    #agenda-summary .switch.active{
      background:#4f46e5;
    }

    #agenda-summary .switch.active::after{
      left:23px;
    }

    #agenda-summary .switch-row{
      display:flex;
      align-items:center;
      gap:10px;
      min-height:40px;
    }

    #agenda-summary .chips{
      display:flex;
      flex-direction:column;
      gap:8px;
      margin-top:10px;
      max-height:160px;
      overflow:auto;
    }

    #agenda-summary .chip{
      display:flex;
      align-items:center;
      gap:10px;
      border:1px solid var(--line);
      padding:8px 10px;
      border-radius:10px;
    }

    #agenda-summary .chip-avatar{
      width:28px;
      height:28px;
      border-radius:999px;
      background:#4f46e5;
      color:#fff;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:11px;
      font-weight:800;
      flex-shrink:0;
    }

    #agenda-summary .chip-meta{
      flex:1;
      min-width:0;
    }

    #agenda-summary .chip-name{
      font-size:13px;
      font-weight:700;
      color:#111827;
    }

    #agenda-summary .chip-sub{
      font-size:11px;
      color:#667085;
      margin-top:2px;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }

    #agenda-summary .chip-remove{
      width:24px;
      height:24px;
      border:none;
      background:#f1f5f9;
      border-radius:999px;
      cursor:pointer;
      color:#475569;
      font-size:14px;
    }

    #agenda-summary .chip-remove:hover{
      background:#ffe4e6;
      color:#e11d48;
    }

    #agenda-summary .modal-foot{
      display:flex;
      align-items:center;
      justify-content:flex-end;
      gap:10px;
      padding:16px 22px;
      border-top:1px solid var(--line);
      background:#fff;
    }

    #agenda-summary .danger{
      margin-right:auto;
      background:#fff1f2;
      color:#e11d48;
      border:1px solid #fecdd3;
    }

    @media (max-width: 1080px){
      #agenda-summary .grid{
        grid-template-columns:1fr;
      }
    }

    @media (max-width: 720px){
      #agenda-summary{
        padding:12px;
      }

      #agenda-summary .grid-2{
        grid-template-columns:1fr;
      }

      #agenda-summary .event-row{
        grid-template-columns:22px 4px minmax(0,1fr);
      }

      #agenda-summary .badge{
        grid-column:3;
        justify-self:start;
        margin-top:8px;
      }

      #agenda-summary .topbar{
        align-items:stretch;
      }

      #agenda-summary .actions{
        width:100%;
      }

      #agenda-summary .btn{
        justify-content:center;
      }
    }
  </style>

  <div class="wrap">
    <div class="topbar">
      <div>
        <h1 class="title">Resumen de agenda</h1>
        <div class="sub">Consulta próximos eventos y la agenda programada para hoy.</div>
      </div>

      <div class="actions">
        <button type="button" id="btn-new-event" class="btn primary">＋ Nuevo evento</button>
      </div>
    </div>

    <div class="grid">
      <div class="card">
        <div class="card-head">Próximos eventos</div>
        <div id="upcoming-list" class="list">
          <div class="empty">Cargando eventos...</div>
        </div>
      </div>

      <div class="card">
        <div class="card-head">Agenda de hoy</div>
        <div id="today-list" class="today-body">
          <div class="today-empty">Cargando agenda de hoy...</div>
        </div>
      </div>
    </div>
  </div>

  {{-- MODAL VISTA (SHOW) --}}
  <div id="agenda-show-overlay" class="overlay">
    <div class="modal">
      <div class="modal-top-bar" id="show-top-bar"></div>
      
      <div class="show-content">
        <div class="show-header">
          <div class="show-icon-box" id="show-icon-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
          </div>
          
          <div class="show-title-wrapper">
            <h3 id="show-title">Título del evento</h3>
            <div class="show-desc" id="show-desc">Descripción del evento</div>
          </div>
          
          <button type="button" id="show-close-x" class="x">×</button>
        </div>

        <div class="show-details-box">
          <div class="detail-row">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <span id="show-date">Fecha</span>
          </div>
          <div class="detail-row">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            <span id="show-time">Hora</span>
          </div>
          <div class="detail-row">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
            <span id="show-reminder">Recordatorio</span>
          </div>
        </div>

        <div class="show-badges">
          <span class="show-badge outline" id="show-category">Categoría</span>
          <span class="show-badge" id="show-priority">Prioridad</span>
        </div>
      </div>

      <div class="modal-foot">
        <button type="button" id="btn-show-close" class="btn secondary">Cerrar</button>
        <button type="button" id="btn-show-edit" class="btn primary">Editar</button>
      </div>
    </div>
  </div>

  {{-- MODAL CREAR / EDITAR --}}
  <div id="agenda-summary-overlay" class="overlay">
    <div class="modal">
      <div class="modal-head">
        <h3 id="summary-modal-title">Nuevo evento</h3>
        <button type="button" id="summary-close" class="x">×</button>
      </div>

      <div class="modal-body">
        <form id="summary-form">
          @csrf

          <input type="hidden" id="ev-id">
          <input type="hidden" id="ev-color" value="indigo">
          <input type="hidden" id="ev-completed" value="0">
          <input type="hidden" id="ev-all-day" value="0">

          <div class="field">
            <label>Título</label>
            <input id="ev-title" class="input" type="text" required>
          </div>

          <div class="field">
            <label>Color</label>
            <div class="colors" id="color-picker">
              <button type="button" class="color c-indigo active" data-color="indigo"></button>
              <button type="button" class="color c-emerald" data-color="emerald"></button>
              <button type="button" class="color c-violet" data-color="violet"></button>
              <button type="button" class="color c-rose" data-color="rose"></button>
              <button type="button" class="color c-sky" data-color="sky"></button>
              <button type="button" class="color c-amber" data-color="amber"></button>
            </div>
          </div>

          <div class="grid-2">
            <div class="field">
              <label>Fecha</label>
              <input id="ev-date" class="input" type="date" required>
            </div>
            <div class="field">
              <label>Categoría</label>
              <select id="ev-category" class="select">
                <option value="administracion">Administración</option>
                <option value="sistemas">Sistemas</option>
                <option value="almacen">Almacén</option>
                <option value="contabilidad">Contabilidad</option>
                <option value="logistica">Logística</option>
                <option value="ventas">Ventas</option>
                <option value="general">General</option>
              </select>
            </div>
          </div>

          <div class="field">
            <div class="switch-row">
              <button type="button" id="all-day-switch" class="switch"></button>
              <span>Todo el día</span>
            </div>
          </div>

          <div class="grid-2" id="time-grid">
            <div class="field">
              <label>Hora inicio</label>
              <input id="ev-start-time" class="input" type="time" value="09:00">
            </div>
            <div class="field">
              <label>Hora fin</label>
              <input id="ev-end-time" class="input" type="time" value="10:00">
            </div>
          </div>

          <div class="grid-2">
            <div class="field">
              <label>Recordatorio</label>
              <select id="ev-offset" class="select">
                <option value="5">5 minutos antes</option>
                <option value="15" selected>15 minutos antes</option>
                <option value="30">30 minutos antes</option>
                <option value="60">1 hora antes</option>
                <option value="1440">1 día antes</option>
              </select>
            </div>
            <div class="field">
              <label>Prioridad</label>
              <select id="ev-priority" class="select">
                <option value="baja">Baja</option>
                <option value="media" selected>Media</option>
                <option value="alta">Alta</option>
              </select>
            </div>
          </div>

          <div class="field">
            <label>Ubicación</label>
            <input id="ev-location" class="input" type="text" placeholder="Agregar ubicación">
          </div>

          <div class="field">
            <label>Invitados</label>
            <select id="ev-users" class="select" size="5" style="height:auto;padding:10px 12px;"></select>
            <div id="users-error" style="display:none;margin-top:6px;color:#e11d48;font-size:12px;font-weight:700;">Selecciona al menos un usuario.</div>
            <div id="ev-chips" class="chips"></div>
            <input type="hidden" id="ev-user-ids" value="[]">
          </div>

          <div class="field" style="margin-bottom:0;">
            <label>Notas</label>
            <textarea id="ev-notes" class="textarea" placeholder="Notas adicionales..."></textarea>
          </div>
        </form>
      </div>

      <div class="modal-foot">
        <button type="button" id="btn-delete" class="btn danger" style="display:none;">Eliminar</button>
        <button type="button" id="btn-cancel" class="btn secondary">Cancelar</button>
        <button type="button" id="btn-save" class="btn primary">Guardar</button>
      </div>
    </div>
  </div>

  <script>
    window.agendaSummaryRoutes = {
      feed: @json(route('agenda.feed')),
      users: @json(route('agenda.users')),
      store: @json(route('agenda.store')),
      calendar: @json(route('agenda.calendar')),
      base: @json(url('/agenda'))
    };
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const routes = window.agendaSummaryRoutes;
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

      const upcomingList = document.getElementById('upcoming-list');
      const todayList = document.getElementById('today-list');

      // Modal CREAR/EDITAR
      const overlay = document.getElementById('agenda-summary-overlay');
      const btnNew = document.getElementById('btn-new-event');
      const btnClose = document.getElementById('summary-close');
      const btnCancel = document.getElementById('btn-cancel');
      const btnSave = document.getElementById('btn-save');
      const btnDelete = document.getElementById('btn-delete');
      const modalTitle = document.getElementById('summary-modal-title');

      // Modal SHOW (VISTA)
      const showOverlay = document.getElementById('agenda-show-overlay');
      const btnShowCloseX = document.getElementById('show-close-x');
      const btnShowClose = document.getElementById('btn-show-close');
      const btnShowEdit = document.getElementById('btn-show-edit');

      const showTopBar = document.getElementById('show-top-bar');
      const showIconBox = document.getElementById('show-icon-box');
      const showTitle = document.getElementById('show-title');
      const showDesc = document.getElementById('show-desc');
      const showDate = document.getElementById('show-date');
      const showTime = document.getElementById('show-time');
      const showReminder = document.getElementById('show-reminder');
      const showCategory = document.getElementById('show-category');
      const showPriority = document.getElementById('show-priority');

      const usersSelect = document.getElementById('ev-users');
      const usersError = document.getElementById('users-error');
      const chipsWrap = document.getElementById('ev-chips');
      const userIdsHidden = document.getElementById('ev-user-ids');
      const allDaySwitch = document.getElementById('all-day-switch');
      const timeGrid = document.getElementById('time-grid');
      const colorButtons = [...document.querySelectorAll('#color-picker .color')];

      const f = {
        id: document.getElementById('ev-id'),
        title: document.getElementById('ev-title'),
        color: document.getElementById('ev-color'),
        date: document.getElementById('ev-date'),
        category: document.getElementById('ev-category'),
        allDay: document.getElementById('ev-all-day'),
        startTime: document.getElementById('ev-start-time'),
        endTime: document.getElementById('ev-end-time'),
        offset: document.getElementById('ev-offset'),
        priority: document.getElementById('ev-priority'),
        location: document.getElementById('ev-location'),
        notes: document.getElementById('ev-notes'),
        completed: document.getElementById('ev-completed'),
      };

      let USERS_CACHE = [];
      let selectedIds = new Set();
      let rawEvents = [];
      let currentEditingEvent = null; // Guarda el evento actual visualizado

      const COLOR_CLASS = {
        indigo: 'c-indigo', emerald: 'c-emerald', violet: 'c-violet',
        rose: 'c-rose', sky: 'c-sky', amber: 'c-amber'
      };

      const HEX_COLORS = {
        indigo: '#6366f1', emerald: '#10b981', violet: '#8b5cf6',
        rose: '#f43f5e', sky: '#0ea5e9', amber: '#f59e0b'
      };
      
      const ICON_TIME = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>`;
      const ICON_PIN = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>`;

      function pad(n){ return String(n).padStart(2,'0'); }
      function formatDateInput(date){
        const d = new Date(date);
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
      }
      function formatTimeInput(date){
        const d = new Date(date);
        return `${pad(d.getHours())}:${pad(d.getMinutes())}`;
      }
      function initials(name=''){
        const parts = String(name).trim().split(/\s+/).filter(Boolean);
        return ((parts[0]?.[0] || '') + (parts[1]?.[0] || '')).toUpperCase() || 'U';
      }
      function escapeHtml(str=''){
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
      }
      function isTodayDate(d){
        const n = new Date();
        return d.getFullYear() === n.getFullYear() && d.getMonth() === n.getMonth() && d.getDate() === n.getDate();
      }
      function isTomorrowDate(d){
        const n = new Date();
        n.setHours(0,0,0,0);
        n.setDate(n.getDate()+1);
        return d.getFullYear() === n.getFullYear() && d.getMonth() === n.getMonth() && d.getDate() === n.getDate();
      }
      function formatDateLabel(d){
        if (isTodayDate(d)) return 'Hoy';
        if (isTomorrowDate(d)) return 'Mañana';
        const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        return `${days[d.getDay()]}, ${d.getDate()} ${months[d.getMonth()]}`;
      }
      function formatFullDateES(d) {
        return new Intl.DateTimeFormat('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(d)
          .replace(/^./, s => s.toUpperCase()); // Primera letra mayúscula
      }
      function formatHour(d){
        return new Intl.DateTimeFormat('es-MX', { hour:'2-digit', minute:'2-digit', hour12:false }).format(d);
      }
      function formatReminderText(minutes) {
        if (!minutes || minutes == 0) return 'Sin recordatorio';
        if (minutes == 5) return '5 minutos antes';
        if (minutes == 15) return '15 minutos antes';
        if (minutes == 30) return '30 minutos antes';
        if (minutes == 60) return '1 hora antes';
        if (minutes == 1440) return '1 día antes';
        return `${minutes} minutos antes`;
      }

      function setColor(color){
        f.color.value = color;
        colorButtons.forEach(btn => {
          btn.classList.toggle('active', btn.dataset.color === color);
        });
      }

      function setAllDay(value){
        const checked = !!value;
        f.allDay.value = checked ? '1' : '0';
        allDaySwitch.classList.toggle('active', checked);
        timeGrid.style.display = checked ? 'none' : 'grid';
      }

      function syncHiddenUsers(){
        userIdsHidden.value = JSON.stringify(Array.from(selectedIds));
      }

      function renderChips(){
        chipsWrap.innerHTML = '';

        Array.from(selectedIds).forEach(id => {
          const user = USERS_CACHE.find(u => Number(u.id) === Number(id));
          if (!user) return;

          const chip = document.createElement('div');
          chip.className = 'chip';
          chip.innerHTML = `
            <div class="chip-avatar">${initials(user.name)}</div>
            <div class="chip-meta">
              <div class="chip-name">${escapeHtml(user.name || '')}</div>
              <div class="chip-sub">${escapeHtml([user.email, user.phone].filter(Boolean).join(' • '))}</div>
            </div>
            <button type="button" class="chip-remove">×</button>
          `;

          chip.querySelector('.chip-remove').addEventListener('click', () => {
            selectedIds.delete(Number(id));
            const opt = usersSelect.querySelector(`option[value="${id}"]`);
            if (opt) opt.disabled = false;
            renderChips();
            syncHiddenUsers();
          });

          chipsWrap.appendChild(chip);
        });
      }

      function setSelectedUserIds(ids){
        selectedIds = new Set((ids || []).map(v => Number(v)).filter(Boolean));

        [...usersSelect.options].forEach(opt => {
          if (opt.value) opt.disabled = false;
        });

        selectedIds.forEach(id => {
          const opt = usersSelect.querySelector(`option[value="${id}"]`);
          if (opt) opt.disabled = true;
        });

        renderChips();
        syncHiddenUsers();
      }

      function addUserToSelection(id){
        id = Number(id);
        if (!id || selectedIds.has(id)) return;

        selectedIds.add(id);
        const opt = usersSelect.querySelector(`option[value="${id}"]`);
        if (opt) {
          opt.disabled = true;
          usersSelect.value = '';
        }

        renderChips();
        syncHiddenUsers();
      }

      usersSelect.addEventListener('change', () => addUserToSelection(usersSelect.value));

      async function loadUsersOnce(){
        if (USERS_CACHE.length) return;

        usersSelect.innerHTML = '<option value="" disabled selected>Cargando usuarios...</option>';

        try{
          const res = await fetch(routes.users, {
            method:'GET',
            credentials:'same-origin',
            headers:{ 'Accept':'application/json' }
          });

          if (!res.ok) throw new Error('No se pudieron cargar usuarios');

          USERS_CACHE = await res.json();
          usersSelect.innerHTML = '<option value="" disabled selected>Selecciona un usuario...</option>';

          USERS_CACHE.forEach(user => {
            const opt = document.createElement('option');
            opt.value = user.id;
            opt.textContent = user.name || 'Sin nombre';
            usersSelect.appendChild(opt);
          });
        } catch (e) {
          console.error(e);
          usersSelect.innerHTML = '<option value="" disabled>Error cargando usuarios</option>';
        }
      }

      function resetForm(date=null){
        f.id.value = '';
        f.title.value = '';
        f.color.value = 'indigo';
        f.date.value = date ? formatDateInput(date) : formatDateInput(new Date());
        f.category.value = 'general';
        f.startTime.value = '09:00';
        f.endTime.value = '10:00';
        f.offset.value = '15';
        f.priority.value = 'media';
        f.location.value = '';
        f.notes.value = '';
        f.completed.value = '0';
        setColor('indigo');
        setAllDay(false);
        setSelectedUserIds([]);
        usersError.style.display = 'none';
      }

      // MODAL EDITAR / NUEVO
      function openModal(mode='new', ev=null){
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        if (mode === 'new') {
          modalTitle.textContent = 'Nuevo evento';
          btnSave.textContent = 'Guardar';
          btnDelete.style.display = 'none';
          resetForm();
          return;
        }

        const ex = ev.extendedProps || {};
        const start = ev.start ? new Date(ev.start) : new Date();
        const end = ev.end ? new Date(ev.end) : new Date(start.getTime() + 60*60*1000);

        modalTitle.textContent = 'Editar evento';
        btnSave.textContent = 'Guardar';
        btnDelete.style.display = 'inline-flex';

        f.id.value = ev.id || '';
        f.title.value = ev.title || '';
        f.date.value = formatDateInput(start);
        f.category.value = ex.category || 'general';
        f.startTime.value = formatTimeInput(start);
        f.endTime.value = formatTimeInput(end);
        f.offset.value = String(ex.remind_offset_minutes ?? 15);
        f.priority.value = ex.priority || 'media';
        f.location.value = ex.location || '';
        f.notes.value = ex.notes || ex.description || '';
        f.completed.value = ex.completed ? '1' : '0';

        setColor(ex.color || 'indigo');
        setAllDay(!!ev.allDay);
        setSelectedUserIds(ex.user_ids || []);
        usersError.style.display = 'none';
      }

      function closeModal(){
        overlay.style.display = 'none';
        document.body.style.overflow = '';
      }

      // MODAL VISTA (SHOW)
      function openShowModal(ev) {
        currentEditingEvent = ev;
        const ex = ev.extendedProps || {};
        const start = ev.start ? new Date(ev.start) : new Date();
        const end = ev.end ? new Date(ev.end) : null;
        
        const baseColor = HEX_COLORS[ex.color || 'indigo'] || HEX_COLORS.indigo;
        
        // Colores y encabezados
        showTopBar.style.background = baseColor;
        showIconBox.style.background = baseColor;
        
        // Icono de completado vs general
        if(ex.completed) {
            showIconBox.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
        } else {
            showIconBox.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>`;
        }

        showTitle.textContent = ev.title || 'Sin Título';
        
        if (ex.description || ex.notes) {
          showDesc.textContent = ex.description || ex.notes;
          showDesc.style.display = 'block';
        } else {
          showDesc.style.display = 'none';
        }

        showDate.textContent = formatFullDateES(start);

        if (ev.allDay) {
          showTime.textContent = 'Todo el día';
        } else {
          showTime.textContent = end ? `${formatHour(start)} — ${formatHour(end)}` : formatHour(start);
        }

        showReminder.textContent = formatReminderText(ex.remind_offset_minutes);

        // Categoría (badge)
        const catMap = {
          administracion: 'Administración', sistemas: 'Sistemas', almacen: 'Almacén',
          contabilidad: 'Contabilidad', logistica: 'Logística', ventas: 'Ventas', general: 'General'
        };
        showCategory.textContent = catMap[ex.category || 'general'] || 'General';

        // Prioridad (badge)
        const prio = ex.priority || 'media';
        showPriority.className = 'show-badge';
        if (prio === 'baja') {
          showPriority.textContent = 'Prioridad Baja';
          showPriority.classList.add('info');
        } else if (prio === 'media') {
          showPriority.textContent = 'Prioridad Media';
          showPriority.classList.add('warning');
        } else {
          showPriority.textContent = 'Prioridad Alta';
          showPriority.classList.add('danger');
        }

        showOverlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
      }

      function closeShowModal() {
        showOverlay.style.display = 'none';
        document.body.style.overflow = '';
      }

      // Eventos del Show Modal
      btnShowCloseX.addEventListener('click', closeShowModal);
      btnShowClose.addEventListener('click', closeShowModal);
      showOverlay.addEventListener('click', (e) => {
        if (e.target === showOverlay) closeShowModal();
      });

      btnShowEdit.addEventListener('click', () => {
        closeShowModal();
        loadUsersOnce().then(() => {
          if(currentEditingEvent) openModal('edit', currentEditingEvent);
        });
      });

      // Eventos del Edit Form Modal
      btnNew.addEventListener('click', async () => {
        await loadUsersOnce();
        openModal('new');
      });
      btnClose.addEventListener('click', closeModal);
      btnCancel.addEventListener('click', closeModal);
      overlay.addEventListener('click', (e) => {
        if (e.target === overlay) closeModal();
      });

      colorButtons.forEach(btn => {
        btn.addEventListener('click', () => setColor(btn.dataset.color));
      });

      allDaySwitch.addEventListener('click', () => {
        setAllDay(f.allDay.value !== '1');
      });

      function buildPayload(){
        let userIds = [];
        try { userIds = JSON.parse(userIdsHidden.value || '[]'); } catch(e){ userIds = []; }

        if (!userIds.length) {
          usersError.style.display = 'block';
          usersSelect.focus();
          return null;
        }

        const isAllDay = f.allDay.value === '1';

        const startAt = isAllDay
          ? `${f.date.value}T00:00:00`
          : `${f.date.value}T${f.startTime.value}:00`;

        const endAt = isAllDay
          ? `${f.date.value}T23:59:00`
          : `${f.date.value}T${f.endTime.value}:00`;

        return {
          title: f.title.value,
          description: f.notes.value || '',
          start_at: new Date(startAt).toISOString(),
          end_at: new Date(endAt).toISOString(),
          remind_offset_minutes: parseInt(f.offset.value || '15', 10),
          repeat_rule: 'none',
          user_ids: userIds,
          send_email: 1,
          send_whatsapp: 1,
          timezone: 'America/Mexico_City',
          all_day: isAllDay ? 1 : 0,
          completed: parseInt(f.completed.value || '0', 10),
          color: f.color.value || 'indigo',
          category: f.category.value || 'general',
          priority: f.priority.value || 'media',
          location: f.location.value || '',
          notes: f.notes.value || '',
        };
      }

      btnSave.addEventListener('click', async () => {
        const payload = buildPayload();
        if (!payload) return;

        try {
          btnSave.disabled = true;
          btnSave.textContent = 'Guardando...';

          if (f.id.value) {
            const res = await fetch(`${routes.base}/${f.id.value}`, {
              method:'PUT',
              headers:{
                'X-CSRF-TOKEN': csrf,
                'Content-Type':'application/json',
                'Accept':'application/json'
              },
              body: JSON.stringify(payload)
            });
            if (!res.ok) throw new Error('No se pudo actualizar');
          } else {
            const res = await fetch(routes.store, {
              method:'POST',
              headers:{
                'X-CSRF-TOKEN': csrf,
                'Content-Type':'application/json',
                'Accept':'application/json'
              },
              body: JSON.stringify(payload)
            });
            if (!res.ok) throw new Error('No se pudo guardar');
          }

          closeModal();
          await loadSummary();
        } catch (e) {
          console.error(e);
          alert('No se pudo guardar el evento.');
        } finally {
          btnSave.disabled = false;
          btnSave.textContent = 'Guardar';
        }
      });

      btnDelete.addEventListener('click', async () => {
        if (!f.id.value) return;
        if (!confirm('¿Eliminar el evento?')) return;

        try {
          btnDelete.disabled = true;
          btnDelete.textContent = 'Eliminando...';

          const res = await fetch(`${routes.base}/${f.id.value}`, {
            method:'DELETE',
            headers:{
              'X-CSRF-TOKEN': csrf,
              'Accept':'application/json'
            }
          });

          if (!res.ok) throw new Error('No se pudo eliminar');

          closeModal();
          await loadSummary();
        } catch (e) {
          console.error(e);
          alert('No se pudo eliminar el evento.');
        } finally {
          btnDelete.disabled = false;
          btnDelete.textContent = 'Eliminar';
        }
      });

      async function toggleComplete(ev){
        const ex = ev.extendedProps || {};
        const payload = {
          title: ev.title || '',
          description: ex.description || ex.notes || '',
          start_at: ev.start,
          end_at: ev.end || ev.start,
          remind_offset_minutes: parseInt(ex.remind_offset_minutes ?? 15, 10),
          repeat_rule: ex.repeat_rule || 'none',
          user_ids: ex.user_ids || [],
          send_email: 1,
          send_whatsapp: 1,
          timezone: 'America/Mexico_City',
          all_day: ev.allDay ? 1 : 0,
          completed: ex.completed ? 0 : 1,
          color: ex.color || 'indigo',
          category: ex.category || 'general',
          priority: ex.priority || 'media',
          location: ex.location || '',
          notes: ex.notes || ex.description || '',
        };

        const res = await fetch(`${routes.base}/${ev.id}`, {
          method:'PUT',
          headers:{
            'X-CSRF-TOKEN': csrf,
            'Content-Type':'application/json',
            'Accept':'application/json'
          },
          body: JSON.stringify(payload)
        });

        if (!res.ok) throw new Error('No se pudo actualizar');
      }

      function normalizeEvents(feed){
        return (feed || []).map(ev => ({
          ...ev,
          start: ev.start ? new Date(ev.start) : null,
          end: ev.end ? new Date(ev.end) : null,
          allDay: !!ev.allDay,
          extendedProps: ev.extendedProps || {},
        }));
      }

      function renderUpcoming(events){
        const now = new Date();
        now.setHours(0,0,0,0);

        const upcoming = events
          .filter(ev => ev.start && ev.start >= now && !ev.extendedProps.completed)
          .sort((a,b) => a.start - b.start);

        if (!upcoming.length) {
          upcomingList.innerHTML = `<div class="empty">No hay eventos próximos.</div>`;
          return;
        }

        upcomingList.innerHTML = upcoming.map(ev => {
          const ex = ev.extendedProps || {};
          const barClass = COLOR_CLASS[ex.color || 'indigo'] || 'c-indigo';
          const urgent = ex.priority === 'alta';

          return `
            <div class="event-row" data-id="${ev.id}">
              <button type="button" class="check ${ex.completed ? 'done' : ''}" data-check-id="${ev.id}"></button>
              <div class="bar ${barClass}"></div>
              <div class="meta">
                <div class="event-title">${escapeHtml(ev.title || '')}</div>
                <div class="event-sub">
                  <span>${escapeHtml(formatDateLabel(ev.start))}</span>
                  ${ev.allDay ? '' : `<span>${ICON_TIME} ${escapeHtml(formatHour(ev.start))}</span>`}
                  ${ex.location ? `<span>${ICON_PIN} ${escapeHtml(ex.location)}</span>` : ''}
                </div>
              </div>
              ${urgent ? `<div class="badge urgent">Urgente</div>` : `<div></div>`}
            </div>
          `;
        }).join('');

        upcomingList.querySelectorAll('.event-row').forEach(row => {
          row.addEventListener('click', (e) => {
            if (e.target.closest('.check')) return;
            const ev = rawEvents.find(x => String(x.id) === String(row.dataset.id));
            if (ev) openShowModal(ev);
          });
        });

        upcomingList.querySelectorAll('.check').forEach(chk => {
          chk.addEventListener('click', async (e) => {
            e.stopPropagation();
            const ev = rawEvents.find(x => String(x.id) === String(chk.dataset.checkId));
            if (!ev) return;
            try {
              await toggleComplete(ev);
              await loadSummary();
            } catch (err) {
              console.error(err);
              alert('No se pudo actualizar el evento.');
            }
          });
        });
      }

      function renderToday(events){
        const today = events
          .filter(ev => ev.start && isTodayDate(ev.start))
          .sort((a,b) => a.start - b.start);

        if (!today.length) {
          todayList.innerHTML = `<div class="today-empty">Sin eventos para hoy.</div>`;
          return;
        }

        todayList.innerHTML = `
          <div class="timeline">
            ${today.map(ev => {
              const ex = ev.extendedProps || {};
              const dotClass = COLOR_CLASS[ex.color || 'indigo'] || 'c-indigo';

              return `
                <div class="timeline-item" data-id="${ev.id}">
                  <div class="timeline-dot ${dotClass}"></div>
                  <div class="timeline-time">
                    ${ev.allDay ? 'Todo el día' : `${escapeHtml(formatHour(ev.start))}${ev.end ? ` — ${escapeHtml(formatHour(ev.end))}` : ''}`}
                  </div>
                  <div class="timeline-title">${escapeHtml(ev.title || '')}</div>
                  ${ex.location ? `<div class="timeline-loc">${escapeHtml(ex.location)}</div>` : ''}
                </div>
              `;
            }).join('')}
          </div>
        `;

        todayList.querySelectorAll('.timeline-item').forEach(item => {
          item.addEventListener('click', () => {
            const ev = rawEvents.find(x => String(x.id) === String(item.dataset.id));
            if (ev) openShowModal(ev);
          });
        });
      }

      async function loadSummary(){
        try {
          const res = await fetch(routes.feed, {
            method:'GET',
            headers:{ 'Accept':'application/json' },
            credentials:'same-origin'
          });

          if (!res.ok) throw new Error('No se pudo cargar agenda');

          const json = await res.json();
          rawEvents = normalizeEvents(json);

          renderUpcoming(rawEvents);
          renderToday(rawEvents);
        } catch (e) {
          console.error(e);
          upcomingList.innerHTML = `<div class="empty">No se pudo cargar los eventos.</div>`;
          todayList.innerHTML = `<div class="today-empty">No se pudo cargar la agenda de hoy.</div>`;
        }
      }

      loadSummary();
      loadUsersOnce();
    });
  </script>
</div>
@endsection