@extends('layouts.app')
@section('title', 'Agenda')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">

<div id="agenda-clean">
  <style>
    #agenda-clean{
      --bg:#f4f5f7;
      --card:#ffffff;
      --ink:#1f2937;
      --muted:#6b7280;
      --line:#e5e7eb;
      --soft:#f1f5f9;
      --primary:#4f46e5;
      --primary-hover:#4338ca;
      --accent:#4f46e5;

      --indigo-bg:#eeecff; --indigo-text:#5b4cff;
      --rose-bg:#ffe6ea; --rose-text:#e83a54;
      --emerald-bg:#e2f7ed; --emerald-text:#1da370;
      --amber-bg:#fef3d9; --amber-text:#d98c00;
      --sky-bg:#e0f5fb; --sky-text:#0082aa;
      --violet-bg:#f3e8ff; --violet-text:#7e22ce;

      min-height:calc(100vh - 80px);
      background:transparent;
      padding:12px 18px 20px;
      font-family:'Inter',system-ui,-apple-system,sans-serif;
      color:var(--ink);
    }

    #agenda-clean *{ box-sizing:border-box; }

    #agenda-clean .agenda-layout{
      display:flex;
      gap:18px;
      align-items:flex-start;
    }

    #agenda-clean .agenda-main{
      min-width:0;
      flex:1;
    }

    #agenda-clean .wrap{
      max-width:1460px;
      margin:0 auto;
    }

    #agenda-clean .calendar-toolbar{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      flex-wrap:wrap;
      margin-bottom:12px;
    }

    #agenda-clean .toolbar-left,
    #agenda-clean .toolbar-right{
      display:flex;
      align-items:center;
      gap:10px;
      flex-wrap:wrap;
    }

    #agenda-clean .mini-title{
      margin:0;
      font-size:18px;
      line-height:1.1;
      letter-spacing:-0.02em;
      color:var(--ink);
      font-weight:700;
    }

    #agenda-clean .nav-btn,
    #agenda-clean .today-btn,
    #agenda-clean .view-btn,
    #agenda-clean .new-btn,
    #agenda-clean .modal-btn,
    #agenda-clean .close-btn{
      border:none;
      outline:none;
      cursor:pointer;
      transition:.2s ease;
      font-family:inherit;
    }

    #agenda-clean .nav-btn{
      width:30px;
      height:30px;
      border-radius:6px;
      background:#fff;
      border:1px solid var(--line);
      color:var(--muted);
      font-size:18px;
      display:flex;
      align-items:center;
      justify-content:center;
      line-height:1;
      box-shadow:0 1px 2px rgba(0,0,0,.02);
    }

    #agenda-clean .nav-btn:hover{
      background:#f9fafb;
      color:var(--ink);
    }

    #agenda-clean .today-btn{
      height:30px;
      padding:0 12px;
      border-radius:6px;
      background:#fff;
      border:1px solid var(--line);
      color:var(--ink);
      font-size:12px;
      font-weight:600;
      box-shadow:0 1px 2px rgba(0,0,0,.02);
    }

    #agenda-clean .today-btn:hover{
      background:#f9fafb;
    }

    #agenda-clean .view-switch{
      display:flex;
      align-items:center;
      gap:4px;
      background:#e5e7eb;
      padding:4px;
      border-radius:8px;
    }

    #agenda-clean .view-btn{
      min-width:64px;
      height:30px;
      border-radius:6px;
      background:transparent;
      color:#4b5563;
      font-size:12px;
      font-weight:500;
      padding:0 12px;
    }

    #agenda-clean .view-btn.active{
      background:#fff;
      color:var(--ink);
      font-weight:600;
      box-shadow:0 1px 2px rgba(0,0,0,.05);
    }

    #agenda-clean .new-btn{
      height:36px;
      padding:0 16px;
      border-radius:8px;
      background:var(--primary);
      color:#fff;
      display:inline-flex;
      align-items:center;
      gap:8px;
      font-size:13px;
      font-weight:600;
      box-shadow:0 4px 12px rgba(79,70,229,.18);
    }

    #agenda-clean .new-btn:hover{
      opacity:.92;
    }

    #agenda-clean .new-btn .plus{
      font-size:16px;
      line-height:1;
      font-weight:400;
    }

    #agenda-clean .calendar-shell{
      background:var(--card);
      border:1px solid var(--line);
      border-radius:12px;
      overflow:hidden;
      box-shadow:0 4px 6px -1px rgba(0,0,0,.02);
    }

    #agenda-clean #calendar{ padding:0; }
    #agenda-clean .fc .fc-toolbar{ display:none !important; }
    #agenda-clean .fc{ background:#fff; }

    #agenda-clean .fc-theme-standard td,
    #agenda-clean .fc-theme-standard th{
      border-color:var(--line) !important;
    }

    #agenda-clean .fc-scrollgrid{ border:none !important; }

    #agenda-clean .fc-col-header-cell{
      background:#fff;
      border-top:none !important;
      border-left:none !important;
      border-right:none !important;
      padding:8px 0;
    }

    #agenda-clean .fc-col-header-cell-cushion{
      padding:0 !important;
      text-decoration:none !important;
    }

    #agenda-clean .custom-header-month{
      color:#6b7280;
      font-size:11px;
      font-weight:600;
      text-transform:uppercase;
      letter-spacing:.05em;
    }

    #agenda-clean .custom-header-week{
      display:flex;
      flex-direction:column;
      align-items:center;
      gap:4px;
      padding:2px 0;
    }

    #agenda-clean .custom-header-week .day-name{
      font-size:10px;
      font-weight:600;
      color:#6b7280;
      text-transform:uppercase;
      letter-spacing:.05em;
    }

    #agenda-clean .custom-header-week .day-num{
      font-size:18px;
      font-weight:700;
      color:#1f2937;
      line-height:1;
      width:32px;
      height:32px;
      display:flex;
      align-items:center;
      justify-content:center;
      border-radius:50%;
      transition:background-color .18s ease,color .18s ease;
    }

    #agenda-clean .fc-day-today .custom-header-week .day-num{
      background:var(--accent);
      color:#fff;
    }

    #agenda-clean .fc-daygrid-day-frame{
      min-height:102px;
      padding:3px;
      transition:background .18s ease,box-shadow .18s ease;
      border-radius:8px;
    }

    #agenda-clean .fc-daygrid-day-top{
      display:flex;
      flex-direction:row !important;
      justify-content:flex-start;
      padding:4px 6px;
    }

    #agenda-clean .fc-daygrid-day-number{
      text-decoration:none !important;
      color:#1f2937;
      font-size:13px;
      font-weight:500;
      width:24px;
      height:24px;
      display:flex;
      align-items:center;
      justify-content:center;
      border-radius:50%;
      transition:background-color .18s ease,color .18s ease;
    }

    #agenda-clean .fc-day-other .fc-daygrid-day-number{ color:#9ca3af; }
    #agenda-clean .fc-day-today{ background:#fff !important; }

    #agenda-clean .fc-day-today .fc-daygrid-day-number{
      background:var(--accent);
      color:#fff !important;
      font-weight:600;
    }

    #agenda-clean .fc-daygrid-more-link{
      text-decoration:none !important;
      color:var(--muted);
      font-size:11px;
      font-weight:600;
      margin-left:4px;
    }

    #agenda-clean .fc-daygrid-day{
      transition:background-color .18s ease,box-shadow .18s ease,transform .18s ease;
    }

    #agenda-clean .fc-daygrid-day:hover{
      background:#fafbff !important;
    }

    #agenda-clean .fc-daygrid-day:hover .fc-daygrid-day-frame{
      background:linear-gradient(180deg,rgba(79,70,229,.035) 0%,rgba(79,70,229,.015) 100%);
      box-shadow:inset 0 0 0 1px rgba(79,70,229,.08);
    }

    #agenda-clean .fc-daygrid-day:hover .fc-daygrid-day-number{
      background:rgba(79,70,229,.08);
      color:#4338ca !important;
    }

    #agenda-clean .fc-daygrid-day.fc-day-today:hover .fc-daygrid-day-number{
      background:var(--accent) !important;
      color:#fff !important;
    }

    #agenda-clean .fc-timegrid-slot{ height:38px; }

    #agenda-clean .fc-timegrid-slot-label-cushion{
      color:#6b7280;
      font-size:10px;
      font-weight:500;
      padding-right:10px;
    }

    #agenda-clean .fc-timegrid-axis{
      border-right:none !important;
      width:56px !important;
    }

    #agenda-clean .fc-timegrid-col.fc-day-today{
      background:#fafbfc !important;
    }

    #agenda-clean .fc-timegrid-event-harness{
      margin-inline:2px;
    }

    #agenda-clean .fc-timegrid-col{
      transition:background-color .18s ease;
    }

    #agenda-clean .fc-timegrid-col:hover{
      background:rgba(79,70,229,.035) !important;
    }

    #agenda-clean .fc-timegrid-col.fc-day-today:hover{
      background:rgba(79,70,229,.06) !important;
    }

    #agenda-clean .fc .fc-timegrid-axis-frame,
    #agenda-clean .fc .fc-timegrid-slot-label-frame{
      justify-content:center;
    }

    #agenda-clean .fc .fc-timegrid-allday{
      background:#fbfcff;
    }

    #agenda-clean .fc .fc-timegrid-allday .fc-timegrid-axis-cushion{
      color:#6b7280;
      font-size:11px;
      font-weight:600;
    }

    #agenda-clean .fc .fc-timegrid-allday .fc-timegrid-slot-lane{
      background:#fcfcfe;
    }

    #agenda-clean .fc .fc-timegrid-allday .fc-timegrid-col-frame{
      min-height:38px;
      padding:4px 2px;
    }

    #agenda-clean .fc .fc-timegrid-divider{
      padding:0 !important;
      border:none !important;
      background:#f8fafc !important;
    }

    #agenda-clean .agenda-pill{
      border-radius:999px !important;
      padding:2px 8px !important;
      margin:2px 3px !important;
      border:none !important;
      min-height:20px;
      display:flex;
      align-items:center;
      position:relative;
    }

    #agenda-clean .agenda-pill .pill-title{
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
      font-size:11px;
      font-weight:500;
      width:100%;
      padding-right:18px;
    }

    #agenda-clean .agenda-pill .pill-title strong{
      font-weight:600;
      margin-right:4px;
      opacity:.85;
    }

    #agenda-clean .agenda-block{
      border-radius:8px !important;
      padding:4px 6px !important;
      border:1px solid rgba(0,0,0,.03) !important;
      box-shadow:none !important;
      display:flex;
      flex-direction:column;
      justify-content:center;
      text-align:left;
      position:relative;
    }

    #agenda-clean .agenda-block .block-title{
      font-size:11px;
      font-weight:600;
      line-height:1.15;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
      padding-right:18px;
    }

    #agenda-clean .agenda-block .block-time{
      font-size:10px;
      font-weight:500;
      opacity:.8;
      margin-top:2px;
    }

    #agenda-clean .evt-indigo{ background:var(--indigo-bg) !important; color:var(--indigo-text) !important; }
    #agenda-clean .evt-rose{ background:var(--rose-bg) !important; color:var(--rose-text) !important; }
    #agenda-clean .evt-emerald{ background:var(--emerald-bg) !important; color:var(--emerald-text) !important; }
    #agenda-clean .evt-amber{ background:var(--amber-bg) !important; color:var(--amber-text) !important; }
    #agenda-clean .evt-sky{ background:var(--sky-bg) !important; color:var(--sky-text) !important; }
    #agenda-clean .evt-violet{ background:var(--violet-bg) !important; color:var(--violet-text) !important; }

    #agenda-clean .agenda-pill.is-completed,
    #agenda-clean .agenda-block.is-completed{
      opacity:.78;
    }

    #agenda-clean .agenda-pill.is-completed::after,
    #agenda-clean .agenda-block.is-completed::after{
      content:"✓";
      display:inline-flex;
      align-items:center;
      justify-content:center;
      width:16px;
      height:16px;
      border-radius:999px;
      background:#10b981;
      color:#fff;
      font-size:10px;
      font-weight:800;
      position:absolute;
      right:6px;
      top:6px;
      box-shadow:0 4px 10px rgba(16,185,129,.22);
    }

    #agenda-clean .modal-backdrop,
    #agenda-clean .show-backdrop{
      position:fixed;
      inset:0;
      display:none;
      align-items:center;
      justify-content:center;
      background:rgba(15,23,42,.4);
      backdrop-filter:blur(4px);
      z-index:9999;
      padding:20px;
    }

    #agenda-clean .modal-box{
      width:min(640px,100%);
      max-height:min(90vh,850px);
      background:#fff;
      border-radius:12px;
      box-shadow:0 20px 25px -5px rgba(0,0,0,.1),0 10px 10px -5px rgba(0,0,0,.04);
      overflow:hidden;
      display:flex;
      flex-direction:column;
      animation:agendaFade .2s ease-out;
      border:1px solid var(--line);
    }

    @keyframes agendaFade{
      from{ opacity:0; transform:translateY(15px) scale(.98); }
      to{ opacity:1; transform:translateY(0) scale(1); }
    }

    #agenda-clean .modal-head{
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding:24px 32px 16px;
      border-bottom:1px solid var(--line);
    }

    #agenda-clean .modal-title{
      margin:0;
      font-size:20px;
      font-weight:600;
      color:var(--ink);
      letter-spacing:-.01em;
    }

    #agenda-clean .close-btn{
      width:32px;
      height:32px;
      border-radius:6px;
      background:transparent;
      color:var(--muted);
      font-size:24px;
      display:flex;
      align-items:center;
      justify-content:center;
    }

    #agenda-clean .close-btn:hover{
      background:var(--soft);
      color:var(--ink);
    }

    #agenda-clean .modal-body{
      padding:24px 32px;
      overflow-y:auto;
    }

    #agenda-clean .field{
      margin-bottom:20px;
    }

    #agenda-clean .field label{
      display:block;
      margin-bottom:6px;
      color:var(--ink);
      font-size:13px;
      font-weight:500;
    }

    #agenda-clean .title-input,
    #agenda-clean .input,
    #agenda-clean .select,
    #agenda-clean .textarea{
      width:100%;
      height:40px;
      border:1px solid var(--line);
      background:#fff;
      border-radius:6px;
      padding:0 12px;
      outline:none;
      font-size:14px;
      color:var(--ink);
      transition:all .2s ease;
      box-shadow:0 1px 2px rgba(0,0,0,.02);
    }

    #agenda-clean .title-input{
      font-size:16px;
      font-weight:500;
      height:44px;
    }

    #agenda-clean .title-input:focus,
    #agenda-clean .input:focus,
    #agenda-clean .select:focus,
    #agenda-clean .textarea:focus{
      border-color:var(--accent);
      box-shadow:0 0 0 3px rgba(37,99,235,.1);
    }

    #agenda-clean .textarea{
      height:auto;
      min-height:80px;
      resize:vertical;
      padding:10px 12px;
    }

    #agenda-clean .colors{
      display:flex;
      align-items:center;
      gap:12px;
      margin-top:8px;
    }

    #agenda-clean .color-btn{
      width:28px;
      height:28px;
      border:none;
      border-radius:50%;
      cursor:pointer;
      position:relative;
      transition:transform .1s;
    }

    #agenda-clean .color-btn:hover{ transform:scale(1.1); }

    #agenda-clean .color-btn.active::after{
      content:"";
      position:absolute;
      inset:-4px;
      border-radius:50%;
      border:2px solid currentColor;
      opacity:.4;
    }

    #agenda-clean .color-indigo{ background:#5b4cff; color:#5b4cff; }
    #agenda-clean .color-rose{ background:#e83a54; color:#e83a54; }
    #agenda-clean .color-emerald{ background:#1da370; color:#1da370; }
    #agenda-clean .color-amber{ background:#d98c00; color:#d98c00; }
    #agenda-clean .color-sky{ background:#0082aa; color:#0082aa; }
    #agenda-clean .color-violet{ background:#7e22ce; color:#7e22ce; }

    #agenda-clean .grid-2{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:16px;
    }

    #agenda-clean .switch-row{
      display:flex;
      align-items:center;
      gap:12px;
      min-height:40px;
    }

    #agenda-clean .switch{
      position:relative;
      width:44px;
      height:24px;
      border-radius:999px;
      background:#cbd5e1;
      cursor:pointer;
      transition:.2s ease;
      flex-shrink:0;
      border:none;
    }

    #agenda-clean .switch::after{
      content:"";
      position:absolute;
      top:2px;
      left:2px;
      width:20px;
      height:20px;
      border-radius:50%;
      background:#fff;
      box-shadow:0 1px 2px rgba(0,0,0,.1);
      transition:.2s ease;
    }

    #agenda-clean .switch.active{ background:var(--accent); }
    #agenda-clean .switch.active::after{ left:22px; }

    #agenda-clean .switch-text{
      font-size:14px;
      font-weight:500;
      color:var(--ink);
    }

    #agenda-clean .modal-foot{
      display:flex;
      align-items:center;
      justify-content:flex-end;
      gap:12px;
      padding:16px 32px;
      background:var(--card);
      border-top:1px solid var(--line);
    }

    #agenda-clean .modal-btn{
      height:38px;
      padding:0 16px;
      border-radius:6px;
      font-size:14px;
      font-weight:500;
    }

    #agenda-clean .modal-btn.cancel{
      background:#fff;
      border:1px solid var(--line);
      color:var(--ink);
    }

    #agenda-clean .modal-btn.cancel:hover{ background:var(--soft); }

    #agenda-clean .modal-btn.primary{
      background:var(--primary);
      color:#fff;
    }

    #agenda-clean .modal-btn.primary:hover{ opacity:.9; }

    #agenda-clean .modal-btn.danger{
      margin-right:auto;
      background:transparent;
      border:1px solid #fecdd3;
      color:#e11d48;
    }

    #agenda-clean .modal-btn.danger:hover{ background:#fff1f2; }

    #agenda-clean .helper-error{
      display:none;
      margin-top:6px;
      color:#e11d48;
      font-size:12px;
      font-weight:500;
    }

    #agenda-clean .chips{
      margin-top:8px;
      display:flex;
      flex-direction:column;
      gap:6px;
      max-height:140px;
      overflow:auto;
    }

    #agenda-clean .chip{
      display:flex;
      align-items:center;
      gap:10px;
      border:1px solid var(--line);
      background:#fff;
      border-radius:6px;
      padding:6px 10px;
    }

    #agenda-clean .chip-avatar{
      width:28px;
      height:28px;
      border-radius:50%;
      background:var(--muted);
      color:#fff;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:11px;
      font-weight:600;
    }

    #agenda-clean .chip-meta{ flex:1; min-width:0; }
    #agenda-clean .chip-name{ font-size:13px; font-weight:600; color:var(--ink); line-height:1.2; }
    #agenda-clean .chip-sub{ font-size:11px; color:var(--muted); margin-top:2px; }

    #agenda-clean .chip-remove{
      width:24px;
      height:24px;
      border:none;
      border-radius:4px;
      background:transparent;
      color:var(--muted);
      cursor:pointer;
      font-size:16px;
      display:flex;
      align-items:center;
      justify-content:center;
    }

    #agenda-clean .chip-remove:hover{
      background:#fee2e2;
      color:#e11d48;
    }

    #agenda-clean .show-box{
      width:min(580px,100%);
      background:#fff;
      border-radius:12px;
      overflow:hidden;
      box-shadow:0 25px 50px -12px rgba(0,0,0,.15);
      animation:agendaFade .2s ease-out;
      border:1px solid var(--line);
    }

    #agenda-clean .show-top-line{
      height:4px;
      background:var(--primary);
    }

    #agenda-clean .show-body{ padding:28px 32px; }

    #agenda-clean .show-head{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:16px;
      margin-bottom:24px;
    }

    #agenda-clean .show-head-left{
      display:flex;
      align-items:flex-start;
      gap:14px;
      flex:1;
    }

    #agenda-clean .show-check{
      width:28px;
      height:28px;
      border-radius:6px;
      border:2px solid #cbd5e1;
      background:#fff;
      flex-shrink:0;
      margin-top:2px;
      position:relative;
      cursor:pointer;
      transition:all .2s;
    }

    #agenda-clean .show-check.is-done{
      border-color:#10b981;
      background:#10b981;
    }

    #agenda-clean .show-check.is-done::after{
      content:"";
      position:absolute;
      left:8px;
      top:4px;
      width:6px;
      height:12px;
      border:solid #fff;
      border-width:0 2px 2px 0;
      transform:rotate(45deg);
    }

    #agenda-clean .show-title{
      margin:0;
      font-size:20px;
      line-height:1.2;
      font-weight:600;
      color:var(--ink);
      letter-spacing:-.01em;
    }

    #agenda-clean .show-desc{
      margin-top:8px;
      color:var(--muted);
      font-size:14px;
      line-height:1.5;
      white-space:pre-wrap;
    }

    #agenda-clean .show-details{
      display:grid;
      gap:16px;
      margin-top:16px;
      padding:16px;
      background:var(--bg);
      border-radius:8px;
      border:1px solid var(--line);
    }

    #agenda-clean .show-row{
      display:flex;
      align-items:center;
      gap:12px;
    }

    #agenda-clean .show-icon{
      color:var(--muted);
      display:flex;
    }

    #agenda-clean .show-icon svg{
      width:18px;
      height:18px;
      stroke-width:2;
    }

    #agenda-clean .show-text{
      color:var(--ink);
      font-size:14px;
      font-weight:500;
    }

    #agenda-clean .show-tags{
      display:flex;
      gap:8px;
      flex-wrap:wrap;
      margin-top:24px;
    }

    #agenda-clean .show-badge{
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding:4px 10px;
      border-radius:4px;
      font-size:12px;
      font-weight:600;
      border:1px solid transparent;
    }

    #agenda-clean .show-badge.cat-administracion{ background:#eef2ff; color:#4338ca; border-color:#c7d2fe; }
    #agenda-clean .show-badge.cat-sistemas{ background:#ecfeff; color:#0f766e; border-color:#a5f3fc; }
    #agenda-clean .show-badge.cat-almacen{ background:#fff7ed; color:#c2410c; border-color:#fed7aa; }
    #agenda-clean .show-badge.cat-contabilidad{ background:#f5f3ff; color:#7c3aed; border-color:#ddd6fe; }
    #agenda-clean .show-badge.cat-logistica{ background:#eff6ff; color:#2563eb; border-color:#bfdbfe; }
    #agenda-clean .show-badge.cat-ventas{ background:#fdf2f8; color:#db2777; border-color:#fbcfe8; }
    #agenda-clean .show-badge.cat-general{ background:#f8fafc; color:#475569; border-color:#e2e8f0; }

    #agenda-clean .show-badge.prio-baja{ background:#f8fafc; color:#64748b; }
    #agenda-clean .show-badge.prio-media{ background:#fffbeb; color:#d97706; }
    #agenda-clean .show-badge.prio-alta{ background:#fef2f2; color:#dc2626; }

    #agenda-clean .show-completed-badge{
      background:#ecfdf3;
      color:#047857;
      border-color:#a7f3d0;
    }

    #agenda-clean .show-footer{
      display:flex;
      align-items:center;
      justify-content:flex-end;
      gap:12px;
      margin-top:32px;
    }

    @media (max-width:900px){
      #agenda-clean .agenda-layout{
        flex-direction:column;
      }
    }

    @media (max-width:720px){
      #agenda-clean{
        padding:10px 10px 18px;
      }

      #agenda-clean .calendar-toolbar{
        flex-direction:column;
        align-items:stretch;
      }

      #agenda-clean .toolbar-left,
      #agenda-clean .toolbar-right{
        justify-content:space-between;
      }

      #agenda-clean .grid-2{ grid-template-columns:1fr; }
      #agenda-clean .modal-box,
      #agenda-clean .show-box{ width:100%; max-height:95vh; }
      #agenda-clean .modal-body,
      #agenda-clean .show-body{ padding:20px; }
      #agenda-clean .modal-head,
      #agenda-clean .modal-foot{ padding-left:20px; padding-right:20px; }

      #agenda-clean .view-switch{
        width:100%;
        justify-content:center;
      }
    }
  </style>

  <div class="agenda-layout">
    @include('agenda.partials.sidebar')

    <div class="agenda-main">
      <div class="wrap">
        <div class="calendar-toolbar">
          <div class="toolbar-left">
            <h2 id="calendar-title" class="mini-title">Agenda</h2>
            <button type="button" class="nav-btn" id="btn-prev">‹</button>
            <button type="button" class="today-btn" id="btn-today">Hoy</button>
            <button type="button" class="nav-btn" id="btn-next">›</button>
          </div>

          <div class="toolbar-right">
            <div class="view-switch">
              <button type="button" class="view-btn active" data-view="dayGridMonth">Mes</button>
              <button type="button" class="view-btn" data-view="timeGridWeek">Semana</button>
              <button type="button" class="view-btn" data-view="timeGridDay">Día</button>
            </div>

            <button type="button" id="btn-new" class="new-btn">
              <span class="plus">＋</span>
              <span>Nuevo evento</span>
            </button>
          </div>
        </div>

        <div class="calendar-shell">
          <div id="calendar"></div>
        </div>
      </div>
    </div>
  </div>

  {{-- MODAL EDITAR / CREAR --}}
  <div id="agenda-modal-backdrop" class="modal-backdrop">
    <div class="modal-box">
      <div class="modal-head">
        <h3 id="modal-title" class="modal-title">Nuevo evento</h3>
        <button type="button" id="btn-close" class="close-btn">×</button>
      </div>

      <div class="modal-body">
        <form id="agenda-form" onsubmit="return false;">
          @csrf

          <input type="hidden" id="ev-id">
          <input type="hidden" id="ev-color" value="indigo">
          <input type="hidden" id="ev-completed" value="0">
          <input type="hidden" id="ev-all-day" value="0">

          <div class="field">
            <input
              type="text"
              id="ev-title"
              class="title-input"
              placeholder="Título del evento"
              required
            >
          </div>

          <div class="field">
            <div class="colors" id="color-picker">
              <button type="button" class="color-btn color-indigo active" data-color="indigo"></button>
              <button type="button" class="color-btn color-rose" data-color="rose"></button>
              <button type="button" class="color-btn color-emerald" data-color="emerald"></button>
              <button type="button" class="color-btn color-amber" data-color="amber"></button>
              <button type="button" class="color-btn color-sky" data-color="sky"></button>
              <button type="button" class="color-btn color-violet" data-color="violet"></button>
            </div>
          </div>

          <div class="grid-2">
            <div class="field">
              <label>Fecha</label>
              <input type="date" id="ev-date" class="input" required>
            </div>

            <div class="field">
              <label>Categoría</label>
              <select id="ev-category" class="select">
                <option value="administracion">🗂 Administración</option>
                <option value="sistemas">💻 Sistemas</option>
                <option value="almacen">📦 Almacén</option>
                <option value="contabilidad">🧾 Contabilidad</option>
                <option value="logistica">🚚 Logística</option>
                <option value="ventas">💼 Ventas</option>
                <option value="general">📌 General</option>
              </select>
            </div>
          </div>

          <div class="field">
            <div class="switch-row">
              <button type="button" id="all-day-switch" class="switch"></button>
              <div class="switch-text">Todo el día</div>
            </div>
          </div>

          <div class="grid-2" id="time-grid">
            <div class="field">
              <label>Hora inicio</label>
              <input type="time" id="ev-start-time" class="input" value="09:00">
            </div>

            <div class="field">
              <label>Hora fin</label>
              <input type="time" id="ev-end-time" class="input" value="10:00">
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
            <input type="text" id="ev-location" class="input" placeholder="Agregar ubicación">
          </div>

          <div class="field">
            <label>Invitados</label>
            <select id="ev-users" class="select" size="4" style="height:auto;padding:8px 12px;"></select>
            <div id="users-error" class="helper-error">Selecciona al menos un usuario.</div>
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
        <button type="button" id="btn-delete" class="modal-btn danger" style="display:none;">Eliminar</button>
        <button type="button" id="btn-cancel" class="modal-btn cancel">Cancelar</button>
        <button type="button" id="btn-save" class="modal-btn primary">Crear evento</button>
      </div>
    </div>
  </div>

  {{-- MODAL SHOW --}}
  <div id="agenda-show-backdrop" class="show-backdrop">
    <div class="show-box">
      <div class="show-top-line" id="show-top-line"></div>

      <div class="show-body">
        <div class="show-head">
          <div class="show-head-left">
            <button type="button" id="show-check" class="show-check" aria-label="Completar evento"></button>

            <div class="show-title-wrap">
              <h3 id="show-title" class="show-title">Título del evento</h3>
              <div id="show-desc" class="show-desc" style="display:none;"></div>
            </div>
          </div>

          <button type="button" id="show-close" class="close-btn">×</button>
        </div>

        <div class="show-details">
          <div class="show-row">
            <div class="show-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <rect x="3" y="5" width="18" height="16" rx="2"></rect>
                <path d="M16 3v4M8 3v4M3 10h18"></path>
              </svg>
            </div>
            <div id="show-date" class="show-text"></div>
          </div>

          <div class="show-row">
            <div class="show-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <circle cx="12" cy="12" r="9"></circle>
                <path d="M12 7v5l3 2"></path>
              </svg>
            </div>
            <div id="show-time" class="show-text"></div>
          </div>

          <div class="show-row" id="show-location-row" style="display:none;">
            <div class="show-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M12 21s-6-5.33-6-11a6 6 0 1 1 12 0c0 5.67-6 11-6 11Z"></path>
                <circle cx="12" cy="10" r="2.5"></circle>
              </svg>
            </div>
            <div id="show-location" class="show-text"></div>
          </div>

          <div class="show-row">
            <div class="show-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M10 5a4 4 0 1 1 4 4H8a4 4 0 1 0 4 4v11"></path>
              </svg>
            </div>
            <div id="show-reminder" class="show-text"></div>
          </div>
        </div>

        <div class="show-tags">
          <div id="show-category-badge" class="show-badge"></div>
          <div id="show-priority-badge" class="show-badge"></div>
          <div id="show-completed-badge" class="show-badge show-completed-badge" style="display:none;">✓ Ya completado</div>
        </div>

        <div class="show-footer">
          <button type="button" id="show-btn-close" class="modal-btn cancel">Cerrar</button>
          <button type="button" id="show-btn-edit" class="modal-btn primary">Editar</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const titleEl = document.getElementById('calendar-title');
  const btnPrev = document.getElementById('btn-prev');
  const btnNext = document.getElementById('btn-next');
  const btnToday = document.getElementById('btn-today');
  const btnNew = document.getElementById('btn-new');
  const viewButtons = [...document.querySelectorAll('.view-btn')];

  const modalBackdrop = document.getElementById('agenda-modal-backdrop');
  const btnClose = document.getElementById('btn-close');
  const btnCancel = document.getElementById('btn-cancel');
  const btnDelete = document.getElementById('btn-delete');
  const btnSave = document.getElementById('btn-save');
  const modalTitle = document.getElementById('modal-title');

  const showBackdrop = document.getElementById('agenda-show-backdrop');
  const showClose = document.getElementById('show-close');
  const showBtnClose = document.getElementById('show-btn-close');
  const showBtnEdit = document.getElementById('show-btn-edit');
  const showCheck = document.getElementById('show-check');
  const showTopLine = document.getElementById('show-top-line');
  const showTitle = document.getElementById('show-title');
  const showDesc = document.getElementById('show-desc');
  const showDate = document.getElementById('show-date');
  const showTime = document.getElementById('show-time');
  const showLocationRow = document.getElementById('show-location-row');
  const showLocation = document.getElementById('show-location');
  const showReminder = document.getElementById('show-reminder');
  const showCategoryBadge = document.getElementById('show-category-badge');
  const showPriorityBadge = document.getElementById('show-priority-badge');
  const showCompletedBadge = document.getElementById('show-completed-badge');

  const colorButtons = [...document.querySelectorAll('.color-btn')];
  const allDaySwitch = document.getElementById('all-day-switch');
  const timeGrid = document.getElementById('time-grid');

  const usersSelect = document.getElementById('ev-users');
  const usersError = document.getElementById('users-error');
  const chipsWrap = document.getElementById('ev-chips');
  const userIdsHidden = document.getElementById('ev-user-ids');

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
    completed: document.getElementById('ev-completed')
  };

  let USERS_CACHE = [];
  let selectedIds = new Set();
  let currentShowEvent = null;

  const COLOR_CLASS = {
    indigo: 'evt-indigo',
    rose: 'evt-rose',
    emerald: 'evt-emerald',
    amber: 'evt-amber',
    sky: 'evt-sky',
    violet: 'evt-violet'
  };

  const COLOR_HEX = {
    indigo: '#5b4cff',
    rose: '#e83a54',
    emerald: '#1da370',
    amber: '#d98c00',
    sky: '#0082aa',
    violet: '#7e22ce'
  };

  function pad(n) {
    return String(n).padStart(2, '0');
  }

  function formatDateInput(date) {
    const d = new Date(date);
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  }

  function formatTimeInput(date) {
    const d = new Date(date);
    return `${pad(d.getHours())}:${pad(d.getMinutes())}`;
  }

  function formatLocalDateTime(date) {
    const d = new Date(date);
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
  }

  function getSafeEndDate(eventObj) {
    if (eventObj.end) return new Date(eventObj.end);

    const start = eventObj.start ? new Date(eventObj.start) : new Date();

    if (eventObj.allDay) {
      const end = new Date(start);
      end.setHours(23, 59, 0, 0);
      return end;
    }

    const end = new Date(start);
    end.setHours(end.getHours() + 1);
    return end;
  }

  function formatTitle(date, viewType) {
    const d = new Date(date);

    if (viewType === 'timeGridDay') {
      return new Intl.DateTimeFormat('es-MX', {
        weekday: 'long',
        day: 'numeric',
        month: 'long'
      }).format(d).replace(/^./, s => s.toUpperCase());
    }

    return new Intl.DateTimeFormat('es-MX', {
      month: 'long',
      year: 'numeric'
    }).format(d).replace(/^./, s => s.toUpperCase());
  }

  function formatLongDate(date) {
    return new Intl.DateTimeFormat('es-MX', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    }).format(new Date(date)).replace(/^./, s => s.toUpperCase());
  }

  function formatHour(date) {
    return new Intl.DateTimeFormat('es-MX', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false
    }).format(new Date(date));
  }

  function reminderLabel(value) {
    const v = Number(value || 0);
    if (v === 5) return '5 min antes';
    if (v === 15) return '15 min antes';
    if (v === 30) return '30 min antes';
    if (v === 60) return '1 hora antes';
    if (v === 1440) return '1 día antes';
    if (v > 60 && v < 1440) return `${v} minutos antes`;
    return 'Sin recordatorio';
  }

  function categoryLabel(value) {
    const map = {
      administracion: 'Administración',
      sistemas: 'Sistemas',
      almacen: 'Almacén',
      contabilidad: 'Contabilidad',
      logistica: 'Logística',
      ventas: 'Ventas',
      general: 'General'
    };
    return map[value] || 'General';
  }

  function priorityLabel(value) {
    const map = {
      baja: 'Prioridad Baja',
      media: 'Prioridad Media',
      alta: 'Prioridad Alta'
    };
    return map[value] || 'Prioridad Media';
  }

  function setHeaderTitle() {
    titleEl.textContent = formatTitle(calendar.getDate(), calendar.view.type);
  }

  function setActiveView(view) {
    viewButtons.forEach(btn => {
      btn.classList.toggle('active', btn.dataset.view === view);
    });
  }

  function setColor(color) {
    f.color.value = color;
    colorButtons.forEach(btn => {
      btn.classList.toggle('active', btn.dataset.color === color);
    });
  }

  function setAllDay(value) {
    const checked = !!value;
    f.allDay.value = checked ? '1' : '0';
    allDaySwitch.classList.toggle('active', checked);
    timeGrid.style.display = checked ? 'none' : 'grid';
  }

  function resetForm(date = null) {
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

  function openModal(mode = 'new', eventObj = null, pickedDate = null) {
    closeShowModal();

    modalBackdrop.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    if (mode === 'new') {
      modalTitle.textContent = 'Nuevo evento';
      btnSave.textContent = 'Crear evento';
      btnDelete.style.display = 'none';
      resetForm(pickedDate);
    } else {
      modalTitle.textContent = 'Editar evento';
      btnSave.textContent = 'Guardar';
      btnDelete.style.display = 'inline-flex';

      const ex = eventObj.extendedProps || {};
      const start = eventObj.start ? new Date(eventObj.start) : new Date();
      const end = getSafeEndDate(eventObj);

      f.id.value = eventObj.id || '';
      f.title.value = eventObj.title || '';
      f.date.value = formatDateInput(start);
      f.category.value = ex.category || 'general';
      f.startTime.value = formatTimeInput(start);
      f.endTime.value = formatTimeInput(end);
      f.offset.value = String(ex.remind_offset_minutes ?? 15);
      f.priority.value = ex.priority || 'media';
      f.location.value = ex.location || '';
      f.notes.value = ex.notes || ex.description || '';
      f.completed.value = String(Number(ex.completed ?? 0));

      setColor(ex.color || 'indigo');
      setAllDay(!!eventObj.allDay);
      setSelectedUserIds(ex.user_ids || []);
      usersError.style.display = 'none';
    }
  }

  function closeModal() {
    modalBackdrop.style.display = 'none';
    if (showBackdrop.style.display !== 'flex') {
      document.body.style.overflow = '';
    }
  }

  function openShowModal(eventObj) {
    closeModal();

    currentShowEvent = eventObj;
    const ex = eventObj.extendedProps || {};
    const color = ex.color || 'indigo';
    const start = eventObj.start ? new Date(eventObj.start) : new Date();
    const end = getSafeEndDate(eventObj);
    const isAllDay = !!eventObj.allDay;
    const isDone = !!Number(ex.completed ?? 0);

    showTopLine.style.background = COLOR_HEX[color] || COLOR_HEX.indigo;
    showTitle.textContent = eventObj.title || 'Sin título';

    const descriptionText = (ex.description || ex.notes || '').trim();
    if (descriptionText) {
      showDesc.style.display = '';
      showDesc.textContent = descriptionText;
    } else {
      showDesc.style.display = 'none';
      showDesc.textContent = '';
    }

    showDate.textContent = formatLongDate(start);

    if (isAllDay) {
      showTime.textContent = 'Todo el día';
    } else {
      showTime.textContent = `${formatHour(start)} — ${formatHour(end)}`;
    }

    if ((ex.location || '').trim()) {
      showLocationRow.style.display = '';
      showLocation.textContent = ex.location;
    } else {
      showLocationRow.style.display = 'none';
      showLocation.textContent = '';
    }

    showReminder.textContent = reminderLabel(ex.remind_offset_minutes);
    showCategoryBadge.className = `show-badge cat-${ex.category || 'general'}`;
    showCategoryBadge.textContent = categoryLabel(ex.category);

    showPriorityBadge.className = `show-badge prio-${ex.priority || 'media'}`;
    showPriorityBadge.textContent = priorityLabel(ex.priority);

    showCheck.classList.toggle('is-done', isDone);
    showCompletedBadge.style.display = isDone ? 'inline-flex' : 'none';

    showBackdrop.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }

  function closeShowModal() {
    showBackdrop.style.display = 'none';
    currentShowEvent = null;
    if (modalBackdrop.style.display !== 'flex') {
      document.body.style.overflow = '';
    }
  }

  async function toggleShowCompleted() {
    if (!currentShowEvent) return;

    const eventId = currentShowEvent.id;
    const event = calendar.getEventById(eventId);
    if (!event) return;

    const obj = event.toPlainObject();
    const ex = obj.extendedProps || {};
    const newCompleted = Number(ex.completed ?? 0) ? 0 : 1;

    let userIds = [];
    try {
      userIds = Array.isArray(ex.user_ids) ? ex.user_ids : [];
    } catch (e) {
      userIds = [];
    }

    const safeStart = obj.start ? new Date(obj.start) : new Date();
    const safeEnd = getSafeEndDate(obj);

    const payload = {
      title: obj.title || '',
      description: ex.description || ex.notes || '',
      start_at: formatLocalDateTime(safeStart),
      end_at: formatLocalDateTime(safeEnd),
      remind_offset_minutes: parseInt(ex.remind_offset_minutes ?? 15, 10),
      repeat_rule: ex.repeat_rule || 'none',
      user_ids: userIds,
      send_email: Number(ex.send_email ?? 1),
      send_whatsapp: Number(ex.send_whatsapp ?? 1),
      timezone: ex.timezone || 'America/Mexico_City',
      all_day: obj.allDay ? 1 : 0,
      completed: newCompleted,
      color: ex.color || 'indigo',
      category: ex.category || 'general',
      priority: ex.priority || 'media',
      location: ex.location || '',
      notes: ex.notes || ex.description || ''
    };

    try {
      showCheck.disabled = true;

      const res = await fetch(`{{ url('/agenda') }}/${eventId}`, {
        method: 'PUT',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      if (!res.ok) {
        throw new Error('No se pudo actualizar el evento');
      }

      event.setExtendedProp('completed', newCompleted);
      showCheck.classList.toggle('is-done', !!newCompleted);
      showCompletedBadge.style.display = newCompleted ? 'inline-flex' : 'none';

      calendar.refetchEvents();

      setTimeout(() => {
        const updated = calendar.getEventById(eventId);
        if (updated) {
          currentShowEvent = updated.toPlainObject();
          openShowModal(currentShowEvent);
        }
      }, 200);
    } catch (e) {
      console.error(e);
      alert('No se pudo actualizar el estado del evento.');
    } finally {
      showCheck.disabled = false;
    }
  }

  function closeAllModals() {
    closeModal();
    closeShowModal();
  }

  function initials(name = '') {
    const parts = String(name).trim().split(/\s+/).filter(Boolean);
    const a = parts[0]?.[0] || '';
    const b = parts[1]?.[0] || '';
    return (a + b).toUpperCase() || 'U';
  }

  function syncHiddenUsers() {
    userIdsHidden.value = JSON.stringify(Array.from(selectedIds));
  }

  function renderChips() {
    chipsWrap.innerHTML = '';

    Array.from(selectedIds).forEach(id => {
      const user = USERS_CACHE.find(u => Number(u.id) === Number(id));
      if (!user) return;

      const chip = document.createElement('div');
      chip.className = 'chip';

      chip.innerHTML = `
        <div class="chip-avatar">${initials(user.name)}</div>
        <div class="chip-meta">
          <div class="chip-name">${user.name || ''}</div>
          <div class="chip-sub">${[user.email, user.phone].filter(Boolean).join(' • ')}</div>
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

  function setSelectedUserIds(ids) {
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

  function addUserToSelection(id) {
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

  usersSelect.addEventListener('change', () => {
    addUserToSelection(usersSelect.value);
  });

  async function loadUsersOnce() {
    if (USERS_CACHE.length) return;

    usersSelect.innerHTML = '<option value="" disabled selected>Cargando usuarios...</option>';

    try {
      const res = await fetch("{{ route('agenda.users') }}", {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
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

  function buildPayload() {
    let userIds = [];
    try {
      userIds = JSON.parse(userIdsHidden.value || '[]');
    } catch (e) {
      userIds = [];
    }

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
      start_at: startAt,
      end_at: endAt,
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
      notes: f.notes.value || ''
    };
  }

  const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
    initialView: 'dayGridMonth',
    locale: 'es',
    firstDay: 1,
    height: 'auto',
    selectable: true,
    editable: false,
    eventStartEditable: false,
    eventDurationEditable: false,
    droppable: false,
    headerToolbar: false,
    dayMaxEvents: 2,
    expandRows: true,
    allDaySlot: true,
    slotMinTime: '07:00:00',
    slotMaxTime: '20:00:00',

    views: {
      dayGridMonth: {
        dayMaxEvents: 2
      },
      timeGridWeek: {
        dayHeaderFormat: { weekday: 'short', day: 'numeric' }
      },
      timeGridDay: {
        dayHeaderFormat: { weekday: 'long', day: 'numeric', month: 'long' }
      }
    },

    dayHeaderContent(arg) {
      const date = arg.date;
      const viewType = arg.view.type;

      const dayName = new Intl.DateTimeFormat('es-MX', {
        weekday: 'short',
        timeZone: 'UTC'
      })
        .format(date)
        .replace('.', '')
        .toUpperCase();

      if (viewType === 'timeGridWeek' || viewType === 'timeGridDay') {
        const dayNumber = new Intl.DateTimeFormat('es-MX', {
          day: 'numeric',
          timeZone: 'UTC'
        }).format(date);

        return {
          html: `
            <div class="custom-header-week">
              <div class="day-name">${dayName}</div>
              <div class="day-num">${dayNumber}</div>
            </div>
          `
        };
      }

      return {
        html: `<div class="custom-header-month">${dayName}</div>`
      };
    },

    events: {
      url: "{{ route('agenda.feed') }}",
      failure() {
        alert('No se pudo cargar la agenda.');
      }
    },

    datesSet() {
      setHeaderTitle();
      setActiveView(calendar.view.type);
    },

    dateClick(info) {
      openModal('new', null, info.date);
    },

    eventClick(info) {
      info.jsEvent.preventDefault();
      info.jsEvent.stopPropagation();
      openShowModal(info.event.toPlainObject());
    },

    eventDidMount(info) {
      const viewType = info.view.type;
      const ex = info.event.extendedProps || {};
      const color = ex.color || 'indigo';
      const colorClass = COLOR_CLASS[color] || COLOR_CLASS.indigo;
      const el = info.el;
      const isCompleted = !!Number(ex.completed ?? 0);

      el.classList.add(colorClass);

      if (viewType === 'dayGridMonth') {
        el.classList.add('agenda-pill');
        const timeText = info.timeText ? `<strong>${info.timeText}</strong>&nbsp;` : '';
        el.innerHTML = `<div class="pill-title">${timeText}${info.event.title || ''}</div>`;
      } else if (info.event.allDay) {
        el.classList.add('agenda-pill');
        el.innerHTML = `<div class="pill-title">${info.event.title || ''}</div>`;
      } else {
        el.classList.add('agenda-block');

        const start = info.event.start
          ? new Intl.DateTimeFormat('es-MX', { hour: '2-digit', minute: '2-digit', hour12: false }).format(info.event.start)
          : '';

        const end = info.event.end
          ? new Intl.DateTimeFormat('es-MX', { hour: '2-digit', minute: '2-digit', hour12: false }).format(info.event.end)
          : '';

        el.innerHTML = `
          <div class="block-title">${info.event.title || ''}</div>
          <div class="block-time">${start}${end ? ' - ' + end : ''}</div>
        `;
      }

      if (isCompleted) {
        el.classList.add('is-completed');
      }
    }
  });

  calendar.render();
  setHeaderTitle();
  loadUsersOnce();

  btnPrev.addEventListener('click', () => calendar.prev());
  btnNext.addEventListener('click', () => calendar.next());
  btnToday.addEventListener('click', () => calendar.today());

  viewButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      calendar.changeView(btn.dataset.view);
    });
  });

  btnNew.addEventListener('click', async () => {
    await loadUsersOnce();
    openModal('new');
  });

  btnClose.addEventListener('click', closeModal);
  btnCancel.addEventListener('click', closeModal);

  modalBackdrop.addEventListener('click', e => {
    if (e.target === modalBackdrop) closeModal();
  });

  showClose.addEventListener('click', closeShowModal);
  showBtnClose.addEventListener('click', closeShowModal);

  showBtnEdit.addEventListener('click', async () => {
    if (!currentShowEvent) return;
    await loadUsersOnce();
    openModal('edit', currentShowEvent);
  });

  showCheck.addEventListener('click', toggleShowCompleted);

  showBackdrop.addEventListener('click', e => {
    if (e.target === showBackdrop) closeShowModal();
  });

  colorButtons.forEach(btn => {
    btn.addEventListener('click', () => setColor(btn.dataset.color));
  });

  allDaySwitch.addEventListener('click', () => {
    setAllDay(f.allDay.value !== '1');
  });

  btnSave.addEventListener('click', async () => {
    const payload = buildPayload();
    if (!payload) return;

    try {
      btnSave.disabled = true;
      btnSave.textContent = f.id.value ? 'Guardando...' : 'Creando...';

      if (f.id.value) {
        const res = await fetch(`{{ url('/agenda') }}/${f.id.value}`, {
          method: 'PUT',
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(payload)
        });

        if (!res.ok) throw new Error('Error al actualizar');
      } else {
        const res = await fetch("{{ route('agenda.store') }}", {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(payload)
        });

        if (!res.ok) throw new Error('Error al guardar');
      }

      closeAllModals();
      calendar.refetchEvents();
    } catch (e) {
      console.error(e);
      alert('No se pudo guardar el evento.');
    } finally {
      btnSave.disabled = false;
      btnSave.textContent = f.id.value ? 'Guardar' : 'Crear evento';
    }
  });

  btnDelete.addEventListener('click', async () => {
    if (!f.id.value) return;
    if (!confirm('¿Eliminar el evento?')) return;

    try {
      btnDelete.disabled = true;
      btnDelete.textContent = 'Eliminando...';

      const res = await fetch(`{{ url('/agenda') }}/${f.id.value}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json'
        }
      });

      if (!res.ok) throw new Error('Error al eliminar');

      closeAllModals();
      calendar.refetchEvents();
    } catch (e) {
      console.error(e);
      alert('No se pudo eliminar el evento.');
    } finally {
      btnDelete.disabled = false;
      btnDelete.textContent = 'Eliminar';
    }
  });
});
</script>
@endsection