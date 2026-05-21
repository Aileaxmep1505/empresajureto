@extends('layouts.app')
@section('title', $project->name)

@push('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  :root {
    --bg:           #f9fafb;
    --card:         #ffffff;
    --ink:          #111;
    --ink2:         #333;
    --muted:        #888;
    --line:         #ebebeb;
    --blue:         #007aff;
    --blue-soft:    #e6f0ff;
    --success:      #15803d;
    --success-soft: #e6ffe6;
    --danger:       #ef4444;
    --danger-soft:  #ffebeb;
    --warning:      #b45309;
    --warning-soft: #fef9c3;
    --orange:       #ea580c;
    --orange-soft:  #ffedd5;
  }

  body { font-family: 'Quicksand', sans-serif; background: var(--bg); color: var(--ink2); }

  .pjd-wrap { width: 100%; max-width: 100%; margin: 0; padding: 0; min-height: calc(100vh - 60px); display: flex; flex-direction: column; }

  /* ── Topbar ── */
  .pjd-topbar { display: flex; align-items: center; gap: 18px; flex-wrap: wrap; padding: 12px 24px; background: #fff; border-bottom: 1px solid var(--line); position: sticky; top: 0; z-index: 10; }
  .pjd-back { color: var(--muted); text-decoration: none; font-size: 1.1rem; padding: 4px 8px; border-radius: 8px; transition: all .15s; }
  .pjd-back:hover { background: var(--bg); color: var(--blue); }
  .pjd-title { font-weight: 700; color: var(--ink); font-size: .98rem; display: flex; align-items: center; gap: 6px; }
  .pjd-status-pill { padding: 2px 8px; border-radius: 999px; font-size: .68rem; font-weight: 700; background: var(--blue-soft); color: var(--blue); margin-left: 6px; }
  .pjd-status-pill.is-ready { background: var(--success-soft); color: var(--success); }
  .pjd-status-pill.is-processing { background: var(--warning-soft); color: var(--warning); }
  .pjd-status-pill.is-error { background: var(--danger-soft); color: var(--danger); }

  .pjd-tabs { display: flex; gap: 4px; flex: 1; flex-wrap: wrap; }
  .pjd-tab { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 999px; border: none; background: transparent; font-family: inherit; font-size: .88rem; font-weight: 600; color: var(--ink2); cursor: pointer; transition: all .18s; }
  .pjd-tab:hover { background: var(--bg); color: var(--blue); }
  .pjd-tab.is-active { background: var(--blue); color: #fff; }
  .pjd-tab svg { width: 16px; height: 16px; }

  .pjd-view-doc { margin-left: auto; display: inline-flex; align-items: center; gap: 6px; color: var(--muted); font-size: .85rem; font-weight: 600; text-decoration: none; padding: 6px 10px; border-radius: 8px; }
  .pjd-view-doc:hover { background: var(--bg); color: var(--blue); }

  /* ── Layout 2 columnas ── */
  .pjd-body { flex: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 0; min-height: 0; }
  @media (max-width: 1100px) { .pjd-body { grid-template-columns: 1fr; } }
  .pjd-left { display: flex; flex-direction: column; border-right: 1px solid var(--line); background: #fff; min-height: 0; }
  .pjd-right { display: flex; flex-direction: column; background: var(--bg); min-height: 0; overflow: auto; }

  /* ── CHAT ── */
  .pjd-chat-head { padding: 10px 18px; border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: flex-end; }
  .pjd-chat-reset { background: var(--bg); border: 1px solid var(--line); padding: 5px 12px; border-radius: 999px; font-size: .8rem; font-weight: 600; color: var(--ink2); cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: all .18s; }
  .pjd-chat-reset:hover { background: var(--blue-soft); color: var(--blue); border-color: var(--blue); }
  .pjd-chat-list { flex: 1; padding: 18px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; }
  .pjd-msg { max-width: 90%; }
  .pjd-msg.is-user { align-self: flex-end; max-width: 80%; }
  .pjd-msg.is-assistant { align-self: flex-start; display: flex; gap: 10px; }
  .pjd-msg-avatar { width: 28px; height: 28px; border-radius: 50%; background: var(--ink); color: #fff; display: grid; place-items: center; font-weight: 700; font-size: .8rem; flex-shrink: 0; }
  .pjd-msg-body { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 10px 14px; font-size: .92rem; line-height: 1.5; }
  .pjd-msg.is-user .pjd-msg-body { background: var(--bg); border-color: var(--line); }
  .pjd-msg-meta { font-size: .72rem; color: var(--muted); margin-bottom: 3px; font-weight: 700; }
  .pjd-msg.is-assistant .pjd-msg-meta { display: flex; align-items: center; gap: 6px; }

  .pjd-chat-input { padding: 14px 18px; border-top: 1px solid var(--line); background: #fff; display: flex; align-items: center; gap: 10px; }
  .pjd-chat-input input { flex: 1; border: 1px solid var(--line); border-radius: 999px; padding: 10px 16px; font-family: inherit; font-size: .92rem; outline: none; transition: border-color .2s; }
  .pjd-chat-input input:focus { border-color: var(--blue); }
  .pjd-chat-send { width: 38px; height: 38px; border-radius: 50%; background: var(--ink); color: #fff; border: none; cursor: pointer; display: grid; place-items: center; transition: transform .12s; }
  .pjd-chat-send:hover { transform: scale(1.05); }
  .pjd-chat-send:disabled { opacity: .5; cursor: not-allowed; }

  /* ── Tablas en chat ── */
  .pjd-chat-table-wrap { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 14px; margin-top: 4px; max-width: 100%; overflow-x: auto; }
  .pjd-chat-table-actions { display: flex; gap: 8px; justify-content: flex-end; margin-bottom: 10px; flex-wrap: wrap; }
  .pjd-chat-table-btn { background: var(--bg); border: 1px solid var(--line); padding: 5px 12px; border-radius: 999px; font-family: inherit; font-size: .78rem; font-weight: 700; color: var(--ink2); cursor: pointer; transition: all .15s; display: inline-flex; align-items: center; gap: 4px; }
  .pjd-chat-table-btn:hover { border-color: var(--blue); color: var(--blue); background: var(--blue-soft); }
  .pjd-chat-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
  .pjd-chat-table th { background: #f5f7fb; color: var(--ink); font-weight: 700; text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--line); white-space: normal; }
  .pjd-chat-table td { padding: 10px 12px; border-bottom: 1px solid var(--line); color: var(--ink2); vertical-align: top; }
  .pjd-chat-table tr:last-child td { border-bottom: none; }
  .pjd-chat-table tr:nth-child(even) td { background: #fafbff; }

  /* ── Panel derecho ── */
  .pjd-pane { padding: 18px 22px; display: none; }
  .pjd-pane.is-active { display: block; }
  .pjd-pane-title { font-size: 1.05rem; font-weight: 700; color: var(--ink); margin: 0 0 4px; padding-right: 36px; }

  /* Cards de Ficha / Resumen */
  .pjd-card { background: var(--card); border: 1px solid var(--line); border-radius: 14px; margin-bottom: 14px; overflow: hidden; }
  .pjd-card-head { padding: 12px 16px; border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; gap: 10px; cursor: pointer; user-select: none; background: #fafbff; }
  .pjd-card-head h3 { margin: 0; font-size: .98rem; font-weight: 700; color: var(--ink); display: inline-flex; align-items: center; gap: 6px; }
  .pjd-card-head h3 .sparkle { color: var(--blue); }
  .pjd-card-chev { width: 22px; height: 22px; display: grid; place-items: center; color: var(--muted); transition: transform .2s; }
  .pjd-card.is-open .pjd-card-chev { transform: rotate(180deg); }
  .pjd-card-body { padding: 6px 16px 14px; display: none; }
  .pjd-card.is-open .pjd-card-body { display: block; }

  .pjd-field { padding: 10px 0; border-bottom: 1px solid var(--line); position: relative; }
  .pjd-field:last-child { border-bottom: none; }
  .pjd-field-label { font-size: .78rem; font-weight: 700; color: var(--muted); background: var(--bg); padding: 4px 10px; border-radius: 6px; display: inline-block; margin-bottom: 6px; }
  .pjd-field-value { font-size: .92rem; color: var(--ink); font-weight: 600; line-height: 1.5; padding: 2px 4px; }

  .pjd-qa { padding: 10px 0; border-bottom: 1px solid var(--line); position: relative; }
  .pjd-qa:last-child { border-bottom: none; }
  .pjd-qa-q { font-size: .85rem; font-weight: 700; color: var(--muted); background: var(--bg); padding: 6px 12px; border-radius: 8px; margin-bottom: 8px; display: inline-block; }
  .pjd-qa-a { font-size: .92rem; color: var(--ink); font-weight: 600; line-height: 1.5; padding: 0 6px; }

  /* Citas */
  .pjd-field.has-cita, .pjd-qa.has-cita { cursor: pointer; padding-right: 100px; transition: background .15s ease; border-radius: 8px; }
  .pjd-field.has-cita:hover, .pjd-qa.has-cita:hover { background: linear-gradient(to right, transparent, #f0f7ff 35%); }
  .pjd-cita-badge { position: absolute; top: 50%; right: 6px; transform: translateY(-50%); font-size: .68rem; font-weight: 700; color: var(--blue); background: var(--blue-soft); padding: 5px 12px; border-radius: 999px; border: 1px solid #c7dcfd; opacity: 0; transition: opacity .18s, transform .18s; pointer-events: none; white-space: nowrap; }
  .pjd-field.has-cita:hover .pjd-cita-badge, .pjd-qa.has-cita:hover .pjd-cita-badge { opacity: 1; transform: translateY(-50%) scale(1.02); }

  /* Modal cita */
  .pjd-cita-modal { display: none; position: fixed; inset: 0; z-index: 250; align-items: center; justify-content: center; padding: 20px; }
  .pjd-cita-modal.is-open { display: flex; }
  .pjd-cita-modal-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.5); backdrop-filter: blur(8px); }
  .pjd-cita-modal-card { position: relative; z-index: 1; background: #fff; border-radius: 16px; max-width: 600px; width: 100%; box-shadow: 0 24px 64px rgba(0,0,0,.22); overflow: hidden; animation: pjdCitaSlideUp .25s cubic-bezier(.22,1,.36,1) both; }
  @keyframes pjdCitaSlideUp { from { opacity:0; transform: translateY(20px) scale(.97); } to { opacity:1; transform: translateY(0) scale(1); } }
  .pjd-cita-modal-head { display: flex; align-items: center; justify-content: space-between; padding: 16px 22px; border-bottom: 1px solid var(--line); background: linear-gradient(180deg, #fafbff, #fff); }
  .pjd-cita-modal-head h4 { margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px; }
  .pjd-cita-modal-head h4::before { content: "📄"; }
  .pjd-cita-close { border: none; background: var(--bg); width: 30px; height: 30px; border-radius: 8px; cursor: pointer; color: var(--muted); font-size: 14px; line-height: 1; transition: all .15s; }
  .pjd-cita-close:hover { background: var(--blue-soft); color: var(--blue); }
  .pjd-cita-modal-body { padding: 22px; }
  .pjd-cita-quote { border-left: 4px solid var(--blue); background: #f8faff; padding: 14px 16px; border-radius: 8px; font-size: .95rem; line-height: 1.55; color: var(--ink); margin-bottom: 16px; white-space: pre-wrap; max-height: 320px; overflow-y: auto; }
  .pjd-cita-source { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding: 12px 14px; background: #f8fafc; border-radius: 10px; border: 1px solid var(--line); }
  .pjd-cita-source-label { font-size: .82rem; color: var(--muted); font-weight: 600; }
  .pjd-cita-source-file { font-size: .92rem; font-weight: 700; color: var(--ink); }
  .pjd-cita-source-page { font-size: .82rem; color: var(--muted); }
  .pjd-cita-modal-footer { display: flex; gap: 10px; justify-content: flex-end; padding: 14px 22px; border-top: 1px solid var(--line); background: #fafbff; }
  .pjd-cita-btn { padding: 8px 18px; border-radius: 999px; font-family: inherit; font-weight: 700; font-size: .85rem; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all .15s; }
  .pjd-cita-btn-primary { background: var(--blue); color: #fff; }
  .pjd-cita-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 14px rgba(0,122,255,.25); }
  .pjd-cita-btn-ghost { background: transparent; color: var(--ink2); border: 1px solid var(--line); }
  .pjd-cita-btn-ghost:hover { background: var(--bg); }

  /* ════════════ CHECKLIST AVANZADO ════════════ */
  .pjd-checklist-wrap { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 16px; }
  .pjd-checklist-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
  .pjd-checklist-title { font-size: 1.05rem; font-weight: 700; color: var(--ink); display: inline-flex; align-items: center; gap: 8px; }
  .pjd-checklist-title .star { color: var(--blue); }
  .pjd-checklist-head-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
  .pjd-checklist-link { color: var(--blue); font-weight: 700; font-size: .82rem; text-decoration: none; padding: 6px 10px; border-radius: 8px; display: inline-flex; align-items: center; gap: 5px; cursor: pointer; background: transparent; border: none; }
  .pjd-checklist-link:hover { background: var(--blue-soft); }

  /* Contadores */
  .pjd-counters { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 14px; }
  @media (max-width: 900px) { .pjd-counters { grid-template-columns: repeat(2, 1fr); } }
  .pjd-counter { padding: 10px 12px; border-radius: 10px; background: var(--bg); border: 1px solid var(--line); }
  .pjd-counter-top { display: flex; align-items: center; gap: 6px; font-size: .8rem; font-weight: 700; color: var(--ink2); }
  .pjd-counter-num { font-size: 1rem; font-weight: 700; color: var(--ink); }
  .pjd-counter-pct { margin-left: auto; font-size: .72rem; font-weight: 700; color: var(--muted); }
  .pjd-counter-bar { height: 4px; background: var(--line); border-radius: 999px; overflow: hidden; margin-top: 6px; }
  .pjd-counter-bar-fill { height: 100%; background: var(--muted); border-radius: 999px; transition: width .3s; }
  .pjd-counter.is-pending .pjd-counter-bar-fill { background: var(--warning); }
  .pjd-counter.is-cumple .pjd-counter-bar-fill { background: var(--success); }
  .pjd-counter.is-parcial .pjd-counter-bar-fill { background: var(--warning); }
  .pjd-counter.is-nocumple .pjd-counter-bar-fill { background: var(--danger); }
  .pjd-counter.is-review .pjd-counter-bar-fill { background: var(--blue); }
  .pjd-counter.is-approved .pjd-counter-bar-fill { background: var(--success); }
  .pjd-counter.is-pending .pjd-counter-top { color: var(--warning); }
  .pjd-counter.is-cumple .pjd-counter-top, .pjd-counter.is-approved .pjd-counter-top { color: var(--success); }
  .pjd-counter.is-parcial .pjd-counter-top { color: var(--orange); }
  .pjd-counter.is-nocumple .pjd-counter-top { color: var(--danger); }
  .pjd-counter.is-review .pjd-counter-top { color: var(--blue); }
  .pjd-counter.is-total { background: linear-gradient(180deg, var(--blue-soft), #f0f7ff); border-color: #c7dcfd; }

  /* Toolbar checklist */
  .pjd-cl-toolbar { display: flex; gap: 8px; align-items: center; margin-bottom: 12px; flex-wrap: wrap; }
  .pjd-cl-search { flex: 1; min-width: 220px; position: relative; }
  .pjd-cl-search input { width: 100%; padding: 8px 14px 8px 36px; border: 1px solid var(--line); border-radius: 999px; font-family: inherit; font-size: .88rem; outline: none; background: #fff; }
  .pjd-cl-search input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft); }
  .pjd-cl-search svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; stroke: var(--muted); }
  .pjd-cl-btn { padding: 7px 14px; border: 1px solid var(--line); background: #fff; border-radius: 999px; font-family: inherit; font-size: .85rem; font-weight: 700; color: var(--ink2); cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all .15s; }
  .pjd-cl-btn:hover { border-color: var(--blue); color: var(--blue); }
  .pjd-cl-btn.is-primary { background: var(--blue); color: #fff; border-color: var(--blue); }
  .pjd-cl-btn.is-primary:hover { filter: brightness(1.05); box-shadow: 0 6px 14px rgba(0,122,255,.22); }
  .pjd-cl-btn svg { width: 13px; height: 13px; }

  /* Tabla checklist */
  .pjd-cl-table-wrap { overflow-x: auto; }
  .pjd-cl-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .86rem; background: #fff; border-radius: 10px; overflow: hidden; border: 1px solid var(--line); min-width: 900px; }
  .pjd-cl-table thead th { background: #fafbff; padding: 10px 12px; font-weight: 700; color: var(--muted); text-align: left; font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; border-bottom: 1px solid var(--line); white-space: nowrap; }
  .pjd-cl-table tbody td { padding: 10px 12px; border-bottom: 1px solid var(--line); color: var(--ink2); vertical-align: middle; }
  .pjd-cl-table tbody tr:last-child td { border-bottom: none; }
  .pjd-cl-table tbody tr:hover { background: #fafbff; }
  .pjd-cl-table tbody tr.is-expanded { background: #f5f7fb; }

  .pjd-cl-row-toggle { background: transparent; border: none; cursor: pointer; padding: 2px; color: var(--muted); display: inline-flex; align-items: center; }
  .pjd-cl-row-toggle svg { width: 13px; height: 13px; transition: transform .2s; }
  tr.is-expanded .pjd-cl-row-toggle svg { transform: rotate(90deg); }

  .pjd-cl-requisito { display: flex; align-items: center; gap: 8px; max-width: 380px; }
  .pjd-cl-requisito-text { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 600; color: var(--ink); }

  .pjd-cl-detail { background: #f8faff; padding: 14px 18px; font-size: .85rem; color: var(--ink2); }
  .pjd-cl-detail strong { color: var(--ink); }
  .pjd-cl-detail-row { margin-bottom: 6px; }

  .pjd-cl-cumple-dot { width: 18px; height: 18px; border-radius: 50%; border: 2px solid var(--line); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; background: transparent; padding: 0; }
  .pjd-cl-cumple-dot.is-cumple { background: var(--success); border-color: var(--success); }
  .pjd-cl-cumple-dot.is-parcial { background: var(--warning); border-color: var(--warning); }
  .pjd-cl-cumple-dot.is-nocumple { background: var(--danger); border-color: var(--danger); }

  .pjd-cl-status { display: inline-flex; align-items: center; gap: 4px; font-weight: 700; font-size: .8rem; cursor: pointer; padding: 4px 8px; border-radius: 999px; }
  .pjd-cl-status.is-pendiente { color: var(--warning); background: var(--warning-soft); }
  .pjd-cl-status.is-revision { color: var(--blue); background: var(--blue-soft); }
  .pjd-cl-status.is-aprobado { color: var(--success); background: var(--success-soft); }

  .pjd-cl-options { background: var(--blue-soft); border: 1px solid #c7dcfd; color: var(--blue); width: 28px; height: 22px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 0; font-weight: 700; }
  .pjd-cl-options:hover { background: #d8e7ff; }

  .pjd-cl-popover { position: fixed; z-index: 200; background: #fff; border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 12px 32px rgba(0,0,0,.12); padding: 6px; display: none; min-width: 160px; }
  .pjd-cl-popover.is-open { display: block; }
  .pjd-cl-popover button { display: flex; align-items: center; gap: 8px; width: 100%; padding: 8px 10px; border: none; background: transparent; cursor: pointer; font-family: inherit; font-size: .85rem; text-align: left; border-radius: 6px; color: var(--ink2); }
  .pjd-cl-popover button:hover { background: var(--bg); }
  .pjd-cl-popover button .dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }

  .pjd-cl-no-results { text-align: center; padding: 30px; color: var(--muted); font-size: .9rem; }

  .pjd-cl-add { margin-top: 10px; padding: 14px; border: 1.5px dashed #c7dcfd; border-radius: 12px; text-align: center; color: var(--blue); font-weight: 700; cursor: pointer; background: transparent; width: 100%; font-family: inherit; font-size: .9rem; transition: all .15s; }
  .pjd-cl-add:hover { background: var(--blue-soft); }

  /* ════════════ BORRADOR / REPORTE ════════════ */
  .pjd-borrador-tabs { display: flex; gap: 6px; align-items: center; background: var(--bg); padding: 4px; border-radius: 999px; border: 1px solid var(--line); margin-bottom: 16px; width: fit-content; }
  .pjd-borrador-tab { display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; border-radius: 999px; border: none; background: transparent; font-family: inherit; font-size: .85rem; font-weight: 700; color: var(--ink2); cursor: pointer; transition: all .15s; }
  .pjd-borrador-tab:hover { color: var(--blue); }
  .pjd-borrador-tab.is-active { background: var(--blue); color: #fff; }
  .pjd-borrador-tab svg { width: 14px; height: 14px; }

  .pjd-borrador-actions { display: flex; gap: 8px; align-items: center; margin-bottom: 14px; flex-wrap: wrap; }

  .pjd-borrador-section { display: none; }
  .pjd-borrador-section.is-active { display: block; }

  .pjd-draft-toolbar { background: #f5f7fb; border: 1px solid var(--line); border-radius: 12px 12px 0 0; padding: 8px; display: flex; gap: 4px; flex-wrap: wrap; }
  .pjd-draft-btn { background: transparent; border: none; padding: 6px 8px; border-radius: 6px; cursor: pointer; font-size: .85rem; font-weight: 700; color: var(--ink2); }
  .pjd-draft-btn:hover { background: var(--card); }
  .pjd-draft-editor { width: 100%; min-height: 500px; padding: 16px; border: 1px solid var(--line); border-top: none; border-radius: 0 0 12px 12px; background: #fff; font-family: inherit; font-size: .95rem; outline: none; resize: vertical; }

  .pjd-reporte-empty { background: #fff; border: 1.5px dashed var(--line); border-radius: 14px; padding: 60px 30px; text-align: center; min-height: 360px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 18px; }
  .pjd-reporte-empty p { margin: 0; font-size: 1rem; color: var(--muted); font-weight: 600; }
  .pjd-reporte-btn { background: var(--blue); color: #fff; padding: 12px 28px; border-radius: 999px; border: none; font-family: inherit; font-weight: 700; font-size: .92rem; cursor: pointer; box-shadow: 0 6px 14px rgba(0,122,255,.22); transition: all .15s; display: inline-flex; align-items: center; gap: 8px; }
  .pjd-reporte-btn:hover { transform: translateY(-1px); box-shadow: 0 10px 22px rgba(0,122,255,.28); }
  .pjd-reporte-btn:disabled { opacity: .6; cursor: not-allowed; }
  .pjd-reporte-content { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 30px 36px; min-height: 480px; font-size: .95rem; line-height: 1.7; color: var(--ink2); }
  .pjd-reporte-content h1, .pjd-reporte-content h2, .pjd-reporte-content h3 { color: var(--ink); margin: 18px 0 10px; }
  .pjd-reporte-content h1 { font-size: 1.6rem; }
  .pjd-reporte-content h2 { font-size: 1.25rem; }
  .pjd-reporte-content h3 { font-size: 1.05rem; }
  .pjd-reporte-content table { width: 100%; border-collapse: collapse; margin: 14px 0; }
  .pjd-reporte-content th, .pjd-reporte-content td { padding: 8px 12px; border: 1px solid var(--line); text-align: left; }
  .pjd-reporte-content th { background: var(--bg); font-weight: 700; }

  /* Documentos */
  .pjd-doc { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border: 1px solid var(--line); border-radius: 12px; background: var(--card); margin-bottom: 8px; }
  .pjd-doc-icon { width: 34px; height: 40px; border-radius: 6px; background: var(--danger-soft); border: 1px solid #fecaca; display: grid; place-items: center; color: var(--danger); font-size: .65rem; font-weight: 700; flex-shrink: 0; }
  .pjd-doc-meta { flex: 1; min-width: 0; }
  .pjd-doc-name { font-size: .92rem; font-weight: 700; color: var(--ink); }
  .pjd-doc-sub { font-size: .78rem; color: var(--muted); margin-top: 2px; }
  .pjd-doc-actions { display: flex; gap: 6px; }
  .pjd-doc-link { padding: 6px 10px; border-radius: 8px; background: var(--bg); color: var(--ink2); font-weight: 600; font-size: .8rem; text-decoration: none; border: 1px solid var(--line); }
  .pjd-doc-link:hover { color: var(--blue); border-color: var(--blue); }

  .pjd-inicio-card { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 18px; margin-bottom: 14px; }
  .pjd-inicio-card h4 { margin: 0 0 8px; font-size: .9rem; font-weight: 700; color: var(--ink); }
  .pjd-inicio-card p { margin: 0; font-size: .88rem; color: var(--muted); }

  .pjd-loading-dots { display: inline-flex; gap: 4px; }
  .pjd-loading-dots span { width: 6px; height: 6px; border-radius: 50%; background: var(--muted); animation: pjdBounce 1.2s infinite ease-in-out; }
  .pjd-loading-dots span:nth-child(2) { animation-delay: .15s; }
  .pjd-loading-dots span:nth-child(3) { animation-delay: .3s; }
  @keyframes pjdBounce { 0%,80%,100% { transform: scale(.6); opacity: .4; } 40% { transform: scale(1); opacity: 1; } }
</style>
@endpush

@section('content')
@php
  use Illuminate\Support\Str;

  $sd = $project->structured_data ?? [];
  $ficha = $sd['ficha'] ?? [];
  $fechas = $sd['fechas_clave'] ?? [];
  $resumenEjec = $sd['resumen_ejecutivo'] ?? [];
  $partidas = $sd['partidas'] ?? [];
  $citas = $sd['citas'] ?? [];
  $checklistRaw = $project->checklist ?: ($sd['checklist_sugerido'] ?? []);

  $checklist = collect($checklistRaw)->map(function ($it, $i) {
      if (!is_array($it)) return null;
      return [
          'id'            => $it['id'] ?? ('item-'.$i),
          'requisito'     => $it['requisito'] ?? $it['item'] ?? $it['text'] ?? 'Sin nombre',
          'descripcion'   => $it['descripcion'] ?? '',
          'formato'       => $it['formato'] ?? 'No aplica',
          'categoria'     => $it['categoria'] ?? 'Legal-Administrativo',
          'aplicabilidad' => $it['aplicabilidad'] ?? 'Único',
          'obligatorio'   => $it['obligatorio'] ?? 'Sí',
          'cumplimiento'  => $it['cumplimiento'] ?? '-',
          'status'        => $it['status'] ?? 'Pendiente',
          'prioridad'     => $it['prioridad'] ?? 'Media',
          'fuente'        => $it['fuente'] ?? '',
          'pagina'        => $it['pagina'] ?? null,
      ];
  })->filter()->values()->all();

  $statusClass = match($project->status) {
      'ready' => 'is-ready',
      'processing' => 'is-processing',
      'error','partial' => 'is-error',
      default => '',
  };
  $statusLabel = match($project->status) {
      'ready' => 'Listo',
      'processing' => 'Procesando…',
      'error' => 'Error',
      'partial' => 'Parcial',
      default => $project->status,
  };

  $citaPayload = function ($citas, $key) {
      $c = $citas[$key] ?? null;
      if (!is_array($c) || empty($c['cita'])) return null;
      return htmlspecialchars(json_encode([
          'cita'   => $c['cita'] ?? '',
          'fuente' => $c['fuente'] ?? '',
          'pagina' => $c['pagina'] ?? null,
      ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
  };
@endphp

<div class="pjd-wrap">

  <div class="pjd-topbar">
    <a href="{{ route('projects.index') }}" class="pjd-back" title="Volver">←</a>
    <div class="pjd-title">
      {{ $project->name }}
      <span class="pjd-status-pill {{ $statusClass }}">{{ $statusLabel }}</span>
    </div>

    <div class="pjd-tabs" id="pjdTabs">
      <button class="pjd-tab" data-tab="analisis"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M9 9h6M9 13h6M9 17h3"/></svg> Análisis de Bases</button>
      <button class="pjd-tab" data-tab="inicio"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 11l9-8 9 8M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9"/></svg> Inicio</button>
      <button class="pjd-tab is-active" data-tab="ficha"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg> Ficha</button>
      <button class="pjd-tab" data-tab="resumen"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6"/></svg> Resumen Ejecutivo</button>
      <button class="pjd-tab" data-tab="checklist"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> Checklist</button>
      <button class="pjd-tab" data-tab="borrador"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg> Borrador</button>
      <button class="pjd-tab" data-tab="documentos"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg> Documentos ({{ $project->documents->count() }})</button>

      @if($project->documents->isNotEmpty())
        <a href="{{ Storage::disk('public')->url($project->documents->first()->file_path) }}" target="_blank" class="pjd-view-doc">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          Ver documento
        </a>
      @endif
    </div>
  </div>

  <div class="pjd-body">

    {{-- ============ COLUMNA IZQUIERDA: CHAT ============ --}}
    <div class="pjd-left">
      <div class="pjd-chat-head">
        <button type="button" class="pjd-chat-reset" id="pjdChatReset" title="Reiniciar chat">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v6h6"/></svg>
          Reiniciar
        </button>
      </div>

      <div class="pjd-chat-list" id="pjdChatList">
        @forelse($project->chatMessages as $m)
          <div class="pjd-msg {{ $m->role === 'user' ? 'is-user' : 'is-assistant' }}">
            @if($m->role === 'assistant')
              <div class="pjd-msg-avatar">j</div>
              <div style="flex:1;min-width:0;">
                <div class="pjd-msg-meta">jureto · {{ $m->created_at->format('H:i') }}</div>
                <div class="pjd-msg-body" data-raw="{{ $m->content }}">{!! nl2br(e($m->content)) !!}</div>
              </div>
            @else
              <div class="pjd-msg-body">{{ $m->content }}</div>
            @endif
          </div>
        @empty
          <div class="pjd-msg is-assistant">
            <div class="pjd-msg-avatar">j</div>
            <div>
              <div class="pjd-msg-meta">jureto</div>
              <div class="pjd-msg-body">Hola, soy tu asistente. Pregúntame cosas como "resúmeme los archivos" o "lista las fechas clave" y te las paso en tabla.</div>
            </div>
          </div>
        @endforelse
      </div>

      <form class="pjd-chat-input" id="pjdChatForm" autocomplete="off">
        @csrf
        <input type="text" name="message" id="pjdChatInput" placeholder="Pregunta a jureto (Shift+Enter para salto de línea)">
        <button type="submit" class="pjd-chat-send" id="pjdChatSend" aria-label="Enviar">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M2 21l21-9L2 3v7l15 2-15 2z"/></svg>
        </button>
      </form>
    </div>

    {{-- ============ COLUMNA DERECHA: PANEL DINÁMICO ============ --}}
    <div class="pjd-right">

      {{-- INICIO --}}
      <div class="pjd-pane" data-pane="inicio">
        <div class="pjd-inicio-card">
          <h4>Estado del proyecto</h4>
          <p>Status: <strong>{{ $statusLabel }}</strong></p>
          <p>Documentos: <strong>{{ $project->documents->count() }}</strong></p>
          <p>Creado: <strong>{{ $project->created_at->format('d M Y H:i') }}</strong></p>
        </div>
        @if($project->status === 'error' && $project->error_message)
          <div class="pjd-inicio-card" style="border-color:#fecaca;background:#fff5f5;">
            <h4 style="color:var(--danger)">Error de procesamiento</h4>
            <p>{{ $project->error_message }}</p>
          </div>
        @endif
      </div>

      {{-- FICHA --}}
      <div class="pjd-pane is-active" data-pane="ficha">
        <div class="pjd-card is-open">
          <div class="pjd-card-head js-card-toggle">
            <h3>Ficha de Resumen <span class="sparkle">✨</span></h3>
            <div class="pjd-card-chev">▾</div>
          </div>
          <div class="pjd-card-body">
            @php
              $fichaRows = [
                ['key'=>'ficha.numero_licitacion',     'label'=>'Número de licitación',                  'val'=>$ficha['numero_licitacion'] ?? null],
                ['key'=>'ficha.tipo_evento',           'label'=>'Tipo de evento',                         'val'=>$ficha['tipo_evento'] ?? null],
                ['key'=>'ficha.organismo',             'label'=>'Organismo',                              'val'=>$ficha['organismo'] ?? null],
                ['key'=>'ficha.objeto_licitacion',     'label'=>'¿Cuál es el objeto de la licitación?',   'val'=>$ficha['objeto_licitacion'] ?? null],
                ['key'=>'ficha.medio_participacion',   'label'=>'¿Cuál es el medio de participación?',    'val'=>$ficha['medio_participacion'] ?? null],
              ];
            @endphp
            @foreach($fichaRows as $row)
              @php $payload = $citaPayload($citas, $row['key']); @endphp
              <div class="pjd-field {{ $payload ? 'has-cita' : '' }}" @if($payload) data-cita="{{ $payload }}" @endif>
                <div class="pjd-field-label">{{ $row['label'] }}</div>
                <div class="pjd-field-value">{{ $row['val'] ?: 'No se encontró información' }}</div>
                @if($payload)<div class="pjd-cita-badge">📄 Ver cita</div>@endif
              </div>
            @endforeach
          </div>
        </div>

        <div class="pjd-card is-open">
          <div class="pjd-card-head js-card-toggle">
            <h3>Fechas Clave <span class="sparkle">✨</span></h3>
            <div class="pjd-card-chev">▾</div>
          </div>
          <div class="pjd-card-body">
            @php
              $fechasRows = [
                ['key'=>'fechas_clave.fecha_publicacion',     'label'=>'Fecha de publicación',                       'val'=>$fechas['fecha_publicacion'] ?? null],
                ['key'=>'fechas_clave.junta_aclaraciones',    'label'=>'Junta de aclaraciones',                      'val'=>$fechas['junta_aclaraciones'] ?? null],
                ['key'=>'fechas_clave.presentacion_apertura', 'label'=>'Presentación y apertura de proposiciones',   'val'=>$fechas['presentacion_apertura'] ?? null],
                ['key'=>'fechas_clave.fallo',                 'label'=>'Fallo',                                       'val'=>$fechas['fallo'] ?? null],
                ['key'=>'fechas_clave.vigencia_contrato',     'label'=>'Vigencia del contrato',                       'val'=>$fechas['vigencia_contrato'] ?? null],
              ];
            @endphp
            @foreach($fechasRows as $row)
              @php $payload = $citaPayload($citas, $row['key']); @endphp
              <div class="pjd-field {{ $payload ? 'has-cita' : '' }}" @if($payload) data-cita="{{ $payload }}" @endif>
                <div class="pjd-field-label">{{ $row['label'] }}</div>
                <div class="pjd-field-value">{{ $row['val'] ?: 'No se encontró información' }}</div>
                @if($payload)<div class="pjd-cita-badge">📄 Ver cita</div>@endif
              </div>
            @endforeach
          </div>
        </div>
      </div>

      {{-- RESUMEN EJECUTIVO --}}
      <div class="pjd-pane" data-pane="resumen">
        <div class="pjd-card is-open">
          <div class="pjd-card-head js-card-toggle">
            <h3>Resumen Ejecutivo <span class="sparkle">✨</span></h3>
            <div class="pjd-card-chev">▾</div>
          </div>
          <div class="pjd-card-body">
            @forelse($resumenEjec as $idx => $qa)
              @php $payload = $citaPayload($citas, "resumen_ejecutivo.{$idx}"); @endphp
              <div class="pjd-qa {{ $payload ? 'has-cita' : '' }}" @if($payload) data-cita="{{ $payload }}" @endif>
                <div class="pjd-qa-q">{{ $qa['pregunta'] ?? '' }}</div>
                <div class="pjd-qa-a">{{ $qa['respuesta'] ?? 'No se encontró información' }}</div>
                @if($payload)<div class="pjd-cita-badge">📄 Ver cita</div>@endif
              </div>
            @empty
              <p style="color:var(--muted);font-size:.9rem;padding:8px;">Sin información disponible.</p>
            @endforelse
          </div>
        </div>
      </div>

      {{-- ════════════ CHECKLIST ════════════ --}}
      <div class="pjd-pane" data-pane="checklist">
        <div class="pjd-checklist-wrap">

          <div class="pjd-checklist-head">
            <div class="pjd-checklist-title">{{ $project->name }} <span class="star">⭐</span></div>
            <div class="pjd-checklist-head-actions">
              <button type="button" class="pjd-checklist-link" id="pjdClDownload">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Descargar lista
              </button>
            </div>
          </div>

          <div class="pjd-counters" id="pjdClCounters">
            <div class="pjd-counter is-pending"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="sin_revisar">0</span> Sin revisar <span class="pjd-counter-pct" data-pct="sin_revisar">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="sin_revisar" style="width:0%"></div></div></div>
            <div class="pjd-counter is-nocumple"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="no_cumple">0</span> No Cumple <span class="pjd-counter-pct" data-pct="no_cumple">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="no_cumple" style="width:0%"></div></div></div>
            <div class="pjd-counter is-parcial"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="parcial">0</span> Parcial <span class="pjd-counter-pct" data-pct="parcial">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="parcial" style="width:0%"></div></div></div>
            <div class="pjd-counter is-cumple"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="cumple">0</span> Cumple <span class="pjd-counter-pct" data-pct="cumple">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="cumple" style="width:0%"></div></div></div>
            <div class="pjd-counter is-pending"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="pendiente">0</span> Pendiente <span class="pjd-counter-pct" data-pct="pendiente">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="pendiente" style="width:0%"></div></div></div>
            <div class="pjd-counter is-review"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="revision">0</span> En revisión <span class="pjd-counter-pct" data-pct="revision">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="revision" style="width:0%"></div></div></div>
            <div class="pjd-counter is-approved"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="aprobado">0</span> Aprobado <span class="pjd-counter-pct" data-pct="aprobado">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="aprobado" style="width:0%"></div></div></div>
            <div class="pjd-counter is-total"><div class="pjd-counter-top"><span class="pjd-counter-num" id="pjdClTotalNum">0</span> Total</div></div>
          </div>

          <div class="pjd-cl-toolbar">
            <div class="pjd-cl-search">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
              <input type="text" id="pjdClSearch" placeholder="Buscar por requisito, formato o descripción...">
            </div>
            <button type="button" class="pjd-cl-btn is-primary" id="pjdClReanalisis">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
              Reanálisis
            </button>
          </div>

          <div class="pjd-cl-table-wrap">
            <table class="pjd-cl-table" id="pjdClTable">
              <thead>
                <tr>
                  <th style="width:38px"></th>
                  <th>Requisito</th>
                  <th>Formato</th>
                  <th>Categoría</th>
                  <th>Aplicabilidad</th>
                  <th style="text-align:center">Oblig.</th>
                  <th style="text-align:center">Cumpl.</th>
                  <th>Status</th>
                  <th style="width:50px;text-align:center">Opc.</th>
                </tr>
              </thead>
              <tbody id="pjdClBody">
                @forelse($checklist as $idx => $it)
                  <tr data-row="{{ $idx }}" data-cumplimiento="{{ $it['cumplimiento'] }}" data-status="{{ $it['status'] }}" data-prioridad="{{ $it['prioridad'] }}">
                    <td><button type="button" class="pjd-cl-row-toggle" data-toggle="{{ $idx }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></button></td>
                    <td><div class="pjd-cl-requisito"><span class="pjd-cl-requisito-text" title="{{ $it['requisito'] }}">{{ $it['requisito'] }}</span></div></td>
                    <td>{{ $it['formato'] }}</td>
                    <td>{{ Str::limit($it['categoria'], 22) }}</td>
                    <td>{{ $it['aplicabilidad'] }}</td>
                    <td style="text-align:center;color:var(--success);font-weight:700">{{ $it['obligatorio'] }}</td>
                    <td style="text-align:center">
                      @php
                        $cumpClass = match($it['cumplimiento']) { 'Cumple'=>'is-cumple','Parcial'=>'is-parcial','No Cumple'=>'is-nocumple', default=>'' };
                      @endphp
                      <button type="button" class="pjd-cl-cumple-dot {{ $cumpClass }}" data-cumplimiento-toggle="{{ $idx }}" title="Cambiar cumplimiento"></button>
                    </td>
                    <td>
                      @php
                        $statClass = match($it['status']) { 'En revisión'=>'is-revision','Aprobado'=>'is-aprobado', default=>'is-pendiente' };
                        $statIcon = match($it['status']) { 'En revisión'=>'🔵','Aprobado'=>'🟢', default=>'🕐' };
                      @endphp
                      <span class="pjd-cl-status {{ $statClass }}" data-status-toggle="{{ $idx }}">{{ $statIcon }} {{ $it['status'] }}</span>
                    </td>
                    <td style="text-align:center"><button type="button" class="pjd-cl-options">···</button></td>
                  </tr>
                  <tr class="pjd-cl-detail-row" data-detail="{{ $idx }}" style="display:none;">
                    <td colspan="9" style="padding:0">
                      <div class="pjd-cl-detail">
                        @if($it['descripcion'])<div class="pjd-cl-detail-row"><strong>Descripción:</strong> {{ $it['descripcion'] }}</div>@endif
                        @if($it['fuente'])<div class="pjd-cl-detail-row"><strong>Fuente:</strong> {{ $it['fuente'] }}{{ $it['pagina'] ? ' · Página '.$it['pagina'] : '' }}</div>@endif
                        <div class="pjd-cl-detail-row"><strong>Prioridad:</strong> {{ $it['prioridad'] }}</div>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="9" class="pjd-cl-no-results">Sin items en el checklist. Da clic en <strong>Reanálisis</strong> para generar uno.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <button type="button" class="pjd-cl-add" id="pjdClAddBtn">+ Agregar nuevo requisito</button>
        </div>
      </div>

      {{-- ════════════ BORRADOR / REPORTE ════════════ --}}
      <div class="pjd-pane" data-pane="borrador">
        <h3 class="pjd-pane-title">{{ $project->name }}</h3>

        <div class="pjd-borrador-tabs">
          <button type="button" class="pjd-borrador-tab is-active" data-section="borrador">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
            Borrador
          </button>
          <button type="button" class="pjd-borrador-tab" data-section="reporte">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2zM22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            Reporte
          </button>
        </div>

        <div class="pjd-borrador-section is-active" data-section-pane="borrador">
          <div class="pjd-borrador-actions">
            <button type="button" class="pjd-cl-btn" id="pjdSaveDraft">💾 Guardar</button>
            <button type="button" class="pjd-cl-btn" id="pjdDownloadDraft">⬇ Descargar HTML</button>
            <span style="color:var(--muted);font-size:.78rem;" id="pjdDraftStatus"></span>
          </div>
          <div class="pjd-draft-toolbar">
            <button type="button" class="pjd-draft-btn" onclick="document.execCommand('bold')"><b>B</b></button>
            <button type="button" class="pjd-draft-btn" onclick="document.execCommand('italic')"><i>I</i></button>
            <button type="button" class="pjd-draft-btn" onclick="document.execCommand('underline')"><u>U</u></button>
            <button type="button" class="pjd-draft-btn" onclick="document.execCommand('formatBlock', false, 'H1')">H1</button>
            <button type="button" class="pjd-draft-btn" onclick="document.execCommand('formatBlock', false, 'H2')">H2</button>
            <button type="button" class="pjd-draft-btn" onclick="document.execCommand('formatBlock', false, 'H3')">H3</button>
            <button type="button" class="pjd-draft-btn" onclick="document.execCommand('insertUnorderedList')">• Lista</button>
            <button type="button" class="pjd-draft-btn" onclick="document.execCommand('insertOrderedList')">1. Lista</button>
          </div>
          <div id="pjdDraftEditor" class="pjd-draft-editor" contenteditable="true">{!! $project->draft_content ?? '' !!}</div>
        </div>

        <div class="pjd-borrador-section" data-section-pane="reporte">
          <div class="pjd-borrador-actions" id="pjdReporteActions" style="display:none;">
            <button type="button" class="pjd-cl-btn is-primary" id="pjdReporteRegen">✨ Regenerar</button>
            <button type="button" class="pjd-cl-btn" id="pjdReporteDownload">⬇ Descargar HTML</button>
          </div>

          @if(empty($project->report_content ?? null))
            <div class="pjd-reporte-empty" id="pjdReporteEmpty">
              <p>Deja que jureto haga el trabajo pesado y genera el reporte final.</p>
              <button type="button" class="pjd-reporte-btn" id="pjdReporteGen">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15 8.5 22 9.3 17 14 18.3 21 12 17.8 5.7 21 7 14 2 9.3 9 8.5 12 2"/></svg>
                Generar Reporte
              </button>
            </div>
          @endif

          <div class="pjd-reporte-content" id="pjdReporteContent" style="{{ empty($project->report_content) ? 'display:none;' : '' }}">
            {!! $project->report_content ?? '' !!}
          </div>
        </div>
      </div>

      {{-- DOCUMENTOS --}}
      <div class="pjd-pane" data-pane="documentos">
        <h3 class="pjd-pane-title">Documentos del proyecto</h3>
        @forelse($project->documents as $doc)
          <div class="pjd-doc">
            <div class="pjd-doc-icon">{{ strtoupper(pathinfo($doc->filename, PATHINFO_EXTENSION) ?: 'FILE') }}</div>
            <div class="pjd-doc-meta">
              <div class="pjd-doc-name">{{ $doc->filename }}</div>
              <div class="pjd-doc-sub">{{ number_format(($doc->file_size ?? 0) / 1024, 1) }} KB · Status: <strong>{{ $doc->status }}</strong></div>
            </div>
            <div class="pjd-doc-actions">
              <a href="{{ Storage::disk('public')->url($doc->file_path) }}" target="_blank" class="pjd-doc-link">Ver</a>
            </div>
          </div>
        @empty
          <p style="color:var(--muted);font-size:.9rem;">Este proyecto no tiene documentos.</p>
        @endforelse
      </div>

    </div>
  </div>
</div>

{{-- Popovers --}}
<div class="pjd-cl-popover" id="pjdClCumpPop">
  <button data-set-cumplimiento="-"><span class="dot" style="background:#ccc"></span> Sin revisar</button>
  <button data-set-cumplimiento="Cumple"><span class="dot" style="background:var(--success)"></span> Cumple</button>
  <button data-set-cumplimiento="Parcial"><span class="dot" style="background:var(--warning)"></span> Parcial</button>
  <button data-set-cumplimiento="No Cumple"><span class="dot" style="background:var(--danger)"></span> No Cumple</button>
</div>

<div class="pjd-cl-popover" id="pjdClStatusPop">
  <button data-set-status="Pendiente"><span class="dot" style="background:var(--warning)"></span> Pendiente</button>
  <button data-set-status="En revisión"><span class="dot" style="background:var(--blue)"></span> En revisión</button>
  <button data-set-status="Aprobado"><span class="dot" style="background:var(--success)"></span> Aprobado</button>
</div>

{{-- Modal cita --}}
<div class="pjd-cita-modal" id="pjdCitaModal" aria-hidden="true">
  <div class="pjd-cita-modal-backdrop" id="pjdCitaBackdrop"></div>
  <div class="pjd-cita-modal-card">
    <div class="pjd-cita-modal-head">
      <h4>Cita del documento</h4>
      <button type="button" class="pjd-cita-close" id="pjdCitaClose">✕</button>
    </div>
    <div class="pjd-cita-modal-body">
      <div class="pjd-cita-quote" id="pjdCitaQuote">—</div>
      <div class="pjd-cita-source">
        <span class="pjd-cita-source-label">Fuente:</span>
        <span id="pjdCitaSource" class="pjd-cita-source-file">—</span>
        <span id="pjdCitaPage" class="pjd-cita-source-page"></span>
      </div>
    </div>
    <div class="pjd-cita-modal-footer">
      <a href="#" id="pjdCitaOpenDoc" class="pjd-cita-btn pjd-cita-btn-primary" target="_blank" style="display:none;">Ver documento</a>
      <button type="button" class="pjd-cita-btn pjd-cita-btn-ghost" id="pjdCitaCloseBtn">Cerrar</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  'use strict';

  const PROJECT_SLUG    = @json($project->slug);
  const PROJECT_NAME    = @json($project->name);
  const CHAT_URL        = @json(route('projects.chat', $project));
  const CHAT_RESET_URL  = @json(route('projects.chat.reset', $project));
  const DRAFT_URL       = @json(route('projects.draft', $project));
  const CHECKLIST_URL   = @json(route('projects.checklist', $project));
  const REPORT_URL      = @json(route('projects.report', $project));
  const CSRF            = '{{ csrf_token() }}';
  const PROJECT_DOCS    = @json($project->documents->mapWithKeys(fn($d) => [$d->filename => \Illuminate\Support\Facades\Storage::disk('public')->url($d->file_path)]));

  function escapeHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  // ============ TABS ============
  const tabs = document.querySelectorAll('.pjd-tab');
  const panes = document.querySelectorAll('.pjd-pane');
  function activateTab(name) {
    tabs.forEach(t => t.classList.toggle('is-active', t.dataset.tab === name));
    panes.forEach(p => p.classList.toggle('is-active', p.dataset.pane === name));
  }
  tabs.forEach(t => t.addEventListener('click', () => activateTab(t.dataset.tab)));
  activateTab('ficha');

  // Cards
  document.querySelectorAll('.js-card-toggle').forEach(head => {
    head.addEventListener('click', () => head.closest('.pjd-card').classList.toggle('is-open'));
  });

  // ============ CHAT con detección de TABLAS ============
  const chatForm = document.getElementById('pjdChatForm');
  const chatInput = document.getElementById('pjdChatInput');
  const chatSend = document.getElementById('pjdChatSend');
  const chatList = document.getElementById('pjdChatList');
  const chatReset = document.getElementById('pjdChatReset');

  function scrollChatToBottom() { chatList.scrollTop = chatList.scrollHeight; }
  scrollChatToBottom();

  // Parser de markdown table
  function extractMarkdownTable(text) {
    const lines = text.split('\n');
    let start = -1, end = -1;
    for (let i = 0; i < lines.length; i++) {
      const l = lines[i].trim();
      if (l.startsWith('|') && l.endsWith('|') && lines[i+1] && /^\|[\s:|\-]+\|$/.test(lines[i+1].trim())) {
        start = i;
        for (let j = i + 2; j < lines.length; j++) {
          const lj = lines[j].trim();
          if (lj.startsWith('|') && lj.endsWith('|')) end = j;
          else break;
        }
        break;
      }
    }
    if (start === -1 || end === -1 || end <= start + 1) return null;
    const parseRow = (line) => line.trim().replace(/^\||\|$/g, '').split('|').map(c => c.trim());
    const headers = parseRow(lines[start]);
    const rows = [];
    for (let i = start + 2; i <= end; i++) {
      const r = parseRow(lines[i]);
      if (r.length) rows.push(r);
    }
    return {
      headers, rows,
      before: lines.slice(0, start).join('\n').trim(),
      after: lines.slice(end + 1).join('\n').trim(),
    };
  }

  function renderTableHtml(data) {
    const head = '<tr>' + data.headers.map(h => `<th>${escapeHtml(h)}</th>`).join('') + '</tr>';
    const body = data.rows.map(r => '<tr>' + r.map(c => `<td>${escapeHtml(c).replace(/\n/g,'<br>')}</td>`).join('') + '</tr>').join('');
    return `<table class="pjd-chat-table"><thead>${head}</thead><tbody>${body}</tbody></table>`;
  }

  async function copyTableToClipboard(data) {
    const tsv = [data.headers.join('\t'), ...data.rows.map(r => r.join('\t'))].join('\n');
    try { await navigator.clipboard.writeText(tsv); alert('Tabla copiada al portapapeles'); }
    catch (e) { const ta = document.createElement('textarea'); ta.value = tsv; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); alert('Tabla copiada'); }
  }

  function downloadTableAsExcel(data) {
    const csv = [data.headers, ...data.rows].map(row => row.map(c => `"${(c+'').replace(/"/g,'""')}"`).join(',')).join('\n');
    const blob = new Blob(["\ufeff" + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href = url; a.download = `tabla-${PROJECT_SLUG}-${Date.now()}.csv`;
    document.body.appendChild(a); a.click(); a.remove();
    URL.revokeObjectURL(url);
  }

  function appendMsg(role, content, time = '') {
    const wrap = document.createElement('div');
    wrap.className = `pjd-msg ${role === 'user' ? 'is-user' : 'is-assistant'}`;

    if (role === 'user') {
      wrap.innerHTML = `<div class="pjd-msg-body">${escapeHtml(content)}</div>`;
      chatList.appendChild(wrap);
      scrollChatToBottom();
      return wrap;
    }

    const tableData = extractMarkdownTable(content);
    let bodyHtml;

    if (tableData) {
      const tableHtml = renderTableHtml(tableData);
      const textBefore = tableData.before ? `<div class="pjd-msg-body" style="margin-bottom:8px">${escapeHtml(tableData.before).replace(/\n/g,'<br>')}</div>` : '';
      const textAfter  = tableData.after  ? `<div class="pjd-msg-body" style="margin-top:8px">${escapeHtml(tableData.after).replace(/\n/g,'<br>')}</div>` : '';
      bodyHtml = `
        ${textBefore}
        <div class="pjd-chat-table-wrap">
          <div class="pjd-chat-table-actions">
            <button type="button" class="pjd-chat-table-btn js-copy-table">📋 Copiar tabla</button>
            <button type="button" class="pjd-chat-table-btn js-download-excel">⬇ Descargar Excel</button>
          </div>
          ${tableHtml}
        </div>
        ${textAfter}
      `;
    } else {
      bodyHtml = `<div class="pjd-msg-body">${escapeHtml(content).replace(/\n/g, '<br>')}</div>`;
    }

    wrap.innerHTML = `<div class="pjd-msg-avatar">j</div><div style="flex:1;min-width:0;"><div class="pjd-msg-meta">jureto${time ? ' · ' + time : ''}</div>${bodyHtml}</div>`;
    chatList.appendChild(wrap);

    if (tableData) {
      wrap.querySelector('.js-copy-table')?.addEventListener('click', () => copyTableToClipboard(tableData));
      wrap.querySelector('.js-download-excel')?.addEventListener('click', () => downloadTableAsExcel(tableData));
    }

    scrollChatToBottom();
    return wrap;
  }

  // Re-renderizar mensajes assistant que vinieron del server (por si traían tabla markdown)
  document.querySelectorAll('.pjd-msg.is-assistant .pjd-msg-body[data-raw]').forEach(el => {
    const raw = el.getAttribute('data-raw') || '';
    const tableData = extractMarkdownTable(raw);
    if (!tableData) return;

    const container = el.parentElement;
    const time = container.querySelector('.pjd-msg-meta')?.textContent.split('·')[1]?.trim() || '';
    // construir nuevo body
    const tableHtml = renderTableHtml(tableData);
    const textBefore = tableData.before ? `<div class="pjd-msg-body" style="margin-bottom:8px">${escapeHtml(tableData.before).replace(/\n/g,'<br>')}</div>` : '';
    const textAfter  = tableData.after  ? `<div class="pjd-msg-body" style="margin-top:8px">${escapeHtml(tableData.after).replace(/\n/g,'<br>')}</div>` : '';
    el.outerHTML = `
      ${textBefore}
      <div class="pjd-chat-table-wrap">
        <div class="pjd-chat-table-actions">
          <button type="button" class="pjd-chat-table-btn js-copy-table">📋 Copiar tabla</button>
          <button type="button" class="pjd-chat-table-btn js-download-excel">⬇ Descargar Excel</button>
        </div>
        ${tableHtml}
      </div>
      ${textAfter}
    `;
    container.querySelector('.js-copy-table')?.addEventListener('click', () => copyTableToClipboard(tableData));
    container.querySelector('.js-download-excel')?.addEventListener('click', () => downloadTableAsExcel(tableData));
  });

  function appendLoading() {
    const wrap = document.createElement('div');
    wrap.className = 'pjd-msg is-assistant';
    wrap.id = 'pjdLoadingMsg';
    wrap.innerHTML = `<div class="pjd-msg-avatar">j</div><div><div class="pjd-msg-meta">jureto</div><div class="pjd-msg-body"><span class="pjd-loading-dots"><span></span><span></span><span></span></span></div></div>`;
    chatList.appendChild(wrap);
    scrollChatToBottom();
  }

  chatForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = chatInput.value.trim();
    if (!msg) return;
    chatInput.value = '';
    chatSend.disabled = true;
    appendMsg('user', msg);
    appendLoading();
    try {
      const fd = new FormData(); fd.append('_token', CSRF); fd.append('message', msg);
      const res = await fetch(CHAT_URL, { method:'POST', headers:{'Accept':'application/json'}, body: fd, credentials:'same-origin' });
      const json = await res.json();
      document.getElementById('pjdLoadingMsg')?.remove();
      if (json.ok && json.assistant_message) appendMsg('assistant', json.assistant_message.content, json.assistant_message.time);
      else appendMsg('assistant', json.message || 'Hubo un error.');
    } catch (err) {
      document.getElementById('pjdLoadingMsg')?.remove();
      appendMsg('assistant', 'Error de red.');
    } finally { chatSend.disabled = false; chatInput.focus(); }
  });

  chatReset?.addEventListener('click', async () => {
    if (!confirm('¿Borrar todo el historial?')) return;
    try {
      await fetch(CHAT_RESET_URL, { method:'DELETE', headers:{'X-CSRF-TOKEN': CSRF, 'Accept':'application/json'} });
      chatList.innerHTML = '';
      appendMsg('assistant', 'Hola, soy tu asistente. Pregúntame cosas como "resúmeme los archivos" y te las paso en tabla.');
    } catch (_) {}
  });

  // ============ CHECKLIST ============
  const clBody = document.getElementById('pjdClBody');
  const clSearch = document.getElementById('pjdClSearch');
  const cumpPop = document.getElementById('pjdClCumpPop');
  const statPop = document.getElementById('pjdClStatusPop');
  let activeCumpRow = null, activeStatusRow = null;

  function updateCounters() {
    const rows = clBody.querySelectorAll('tr[data-row]');
    const total = rows.length;
    const counts = { sin_revisar:0, no_cumple:0, parcial:0, cumple:0, pendiente:0, revision:0, aprobado:0 };
    rows.forEach(r => {
      const c = r.dataset.cumplimiento, s = r.dataset.status;
      if (c === 'Cumple') counts.cumple++;
      else if (c === 'Parcial') counts.parcial++;
      else if (c === 'No Cumple') counts.no_cumple++;
      else counts.sin_revisar++;
      if (s === 'En revisión') counts.revision++;
      else if (s === 'Aprobado') counts.aprobado++;
      else counts.pendiente++;
    });
    document.getElementById('pjdClTotalNum').textContent = total;
    Object.keys(counts).forEach(k => {
      const numEl = document.querySelector(`[data-counter="${k}"]`);
      const pctEl = document.querySelector(`[data-pct="${k}"]`);
      const barEl = document.querySelector(`[data-bar="${k}"]`);
      const pct = total > 0 ? Math.round((counts[k] / total) * 100) : 0;
      if (numEl) numEl.textContent = counts[k];
      if (pctEl) pctEl.textContent = pct + '%';
      if (barEl) barEl.style.width = pct + '%';
    });
  }
  updateCounters();

  clBody?.addEventListener('click', (e) => {
    const tBtn = e.target.closest('[data-toggle]');
    if (tBtn) {
      const idx = tBtn.dataset.toggle;
      const tr = clBody.querySelector(`tr[data-row="${idx}"]`);
      const detail = clBody.querySelector(`tr[data-detail="${idx}"]`);
      if (tr && detail) { const open = tr.classList.toggle('is-expanded'); detail.style.display = open ? '' : 'none'; }
      return;
    }
    const cumpBtn = e.target.closest('[data-cumplimiento-toggle]');
    if (cumpBtn) {
      activeCumpRow = cumpBtn.dataset.cumplimientoToggle;
      const rect = cumpBtn.getBoundingClientRect();
      cumpPop.style.top = (rect.bottom + window.scrollY + 6) + 'px';
      cumpPop.style.left = rect.left + 'px';
      cumpPop.classList.add('is-open');
      statPop.classList.remove('is-open');
      e.stopPropagation();
      return;
    }
    const statBtn = e.target.closest('[data-status-toggle]');
    if (statBtn) {
      activeStatusRow = statBtn.dataset.statusToggle;
      const rect = statBtn.getBoundingClientRect();
      statPop.style.top = (rect.bottom + window.scrollY + 6) + 'px';
      statPop.style.left = rect.left + 'px';
      statPop.classList.add('is-open');
      cumpPop.classList.remove('is-open');
      e.stopPropagation();
    }
  });

  cumpPop?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-set-cumplimiento]');
    if (!btn || activeCumpRow === null) return;
    const val = btn.dataset.setCumplimiento;
    const row = clBody.querySelector(`tr[data-row="${activeCumpRow}"]`);
    const dot = row?.querySelector('.pjd-cl-cumple-dot');
    if (!row || !dot) return;
    row.dataset.cumplimiento = val;
    dot.className = 'pjd-cl-cumple-dot';
    if (val === 'Cumple') dot.classList.add('is-cumple');
    else if (val === 'Parcial') dot.classList.add('is-parcial');
    else if (val === 'No Cumple') dot.classList.add('is-nocumple');
    cumpPop.classList.remove('is-open');
    updateCounters();
    saveChecklist();
  });

  statPop?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-set-status]');
    if (!btn || activeStatusRow === null) return;
    const val = btn.dataset.setStatus;
    const row = clBody.querySelector(`tr[data-row="${activeStatusRow}"]`);
    const pill = row?.querySelector('.pjd-cl-status');
    if (!row || !pill) return;
    row.dataset.status = val;
    pill.className = 'pjd-cl-status';
    const icons = {'Pendiente':'🕐','En revisión':'🔵','Aprobado':'🟢'};
    const cls = {'Pendiente':'is-pendiente','En revisión':'is-revision','Aprobado':'is-aprobado'};
    pill.classList.add(cls[val]);
    pill.textContent = (icons[val] || '🕐') + ' ' + val;
    statPop.classList.remove('is-open');
    updateCounters();
    saveChecklist();
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('[data-cumplimiento-toggle]') && !e.target.closest('#pjdClCumpPop')) cumpPop?.classList.remove('is-open');
    if (!e.target.closest('[data-status-toggle]') && !e.target.closest('#pjdClStatusPop')) statPop?.classList.remove('is-open');
  });

  clSearch?.addEventListener('input', () => {
    const q = clSearch.value.trim().toLowerCase();
    clBody.querySelectorAll('tr[data-row]').forEach(r => {
      const text = r.textContent.toLowerCase();
      const match = !q || text.includes(q);
      r.style.display = match ? '' : 'none';
      const detail = clBody.querySelector(`tr[data-detail="${r.dataset.row}"]`);
      if (detail && !match) detail.style.display = 'none';
    });
  });

  async function saveChecklist() {
    const rows = Array.from(clBody.querySelectorAll('tr[data-row]')).map(r => ({
      idx: r.dataset.row,
      cumplimiento: r.dataset.cumplimiento,
      status: r.dataset.status,
      prioridad: r.dataset.prioridad,
    }));
    try {
      const fd = new FormData(); fd.append('_token', CSRF); fd.append('items', JSON.stringify(rows));
      await fetch(CHECKLIST_URL, { method:'POST', headers:{'Accept':'application/json'}, body: fd, credentials:'same-origin' });
    } catch (_) {}
  }

  document.getElementById('pjdClReanalisis')?.addEventListener('click', async () => {
    if (!confirm('Esto regenerará TODO el checklist con IA. ¿Continuar?')) return;
    const btn = document.getElementById('pjdClReanalisis');
    const original = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '⏳ Generando…';
    try {
      const fd = new FormData(); fd.append('_token', CSRF); fd.append('regenerate', '1');
      const res = await fetch(CHECKLIST_URL, { method:'POST', headers:{'Accept':'application/json'}, body: fd, credentials:'same-origin' });
      if (res.ok) location.reload();
      else alert('Error al regenerar');
    } catch (e) { alert('Error de red'); }
    finally { btn.disabled = false; btn.innerHTML = original; }
  });

  document.getElementById('pjdClAddBtn')?.addEventListener('click', () => {
    const name = prompt('Nombre del requisito:');
    if (!name) return;
    location.reload();
  });

  document.getElementById('pjdClDownload')?.addEventListener('click', () => {
    const rows = [['Requisito','Formato','Categoría','Aplicabilidad','Obligatorio','Cumplimiento','Status']];
    clBody.querySelectorAll('tr[data-row]').forEach(r => {
      const cells = r.querySelectorAll('td');
      rows.push([
        cells[1]?.textContent.trim() || '',
        cells[2]?.textContent.trim() || '',
        cells[3]?.textContent.trim() || '',
        cells[4]?.textContent.trim() || '',
        cells[5]?.textContent.trim() || '',
        r.dataset.cumplimiento || '-',
        r.dataset.status || 'Pendiente',
      ]);
    });
    const csv = rows.map(r => r.map(c => `"${(c+'').replace(/"/g,'""')}"`).join(',')).join('\n');
    const blob = new Blob(["\ufeff" + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href = url; a.download = `checklist-${PROJECT_SLUG}.csv`;
    a.click(); URL.revokeObjectURL(url);
  });

  // ============ BORRADOR / REPORTE ============
  const borradorTabs = document.querySelectorAll('.pjd-borrador-tab');
  const borradorSections = document.querySelectorAll('[data-section-pane]');
  borradorTabs.forEach(t => t.addEventListener('click', () => {
    const sec = t.dataset.section;
    borradorTabs.forEach(x => x.classList.toggle('is-active', x.dataset.section === sec));
    borradorSections.forEach(s => s.classList.toggle('is-active', s.dataset.sectionPane === sec));
  }));

  const draftEditor = document.getElementById('pjdDraftEditor');
  const saveBtn = document.getElementById('pjdSaveDraft');
  const draftStatus = document.getElementById('pjdDraftStatus');

  saveBtn?.addEventListener('click', async () => {
    const fd = new FormData(); fd.append('_token', CSRF); fd.append('draft_content', draftEditor.innerHTML);
    saveBtn.disabled = true; saveBtn.textContent = 'Guardando…';
    try {
      const res = await fetch(DRAFT_URL, { method:'POST', headers:{'Accept':'application/json'}, body: fd, credentials:'same-origin' });
      if (res.ok) draftStatus.textContent = '✓ Guardado ' + new Date().toLocaleTimeString();
      else draftStatus.textContent = 'Error';
    } catch (e) { draftStatus.textContent = 'Error de red'; }
    finally { saveBtn.disabled = false; saveBtn.textContent = '💾 Guardar'; }
  });

  document.getElementById('pjdDownloadDraft')?.addEventListener('click', () => {
    const html = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>${PROJECT_NAME}</title><style>body{font-family:Quicksand,sans-serif;max-width:800px;margin:30px auto;padding:20px;line-height:1.6;}</style></head><body>${draftEditor.innerHTML}</body></html>`;
    const blob = new Blob([html], { type: 'text/html;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href = url; a.download = `borrador-${PROJECT_SLUG}.html`; a.click();
    URL.revokeObjectURL(url);
  });

  async function generateReport() {
    const btn = document.getElementById('pjdReporteGen') || document.getElementById('pjdReporteRegen');
    const empty = document.getElementById('pjdReporteEmpty');
    const content = document.getElementById('pjdReporteContent');
    const actions = document.getElementById('pjdReporteActions');
    if (btn) { btn.disabled = true; btn.innerHTML = '⏳ Generando reporte…'; }
    try {
      const fd = new FormData(); fd.append('_token', CSRF);
      const res = await fetch(REPORT_URL, { method:'POST', headers:{'Accept':'application/json'}, body: fd, credentials:'same-origin' });
      const json = await res.json();
      if (json.ok && json.html) {
        if (empty) empty.style.display = 'none';
        content.innerHTML = json.html;
        content.style.display = '';
        if (actions) actions.style.display = '';
      } else { alert(json.message || 'Error al generar reporte'); }
    } catch (e) { alert('Error de red'); }
    finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = btn.id === 'pjdReporteGen' ? '✨ Generar Reporte' : '✨ Regenerar';
      }
    }
  }
  document.getElementById('pjdReporteGen')?.addEventListener('click', generateReport);
  document.getElementById('pjdReporteRegen')?.addEventListener('click', () => { if (confirm('¿Regenerar el reporte?')) generateReport(); });

  document.getElementById('pjdReporteDownload')?.addEventListener('click', () => {
    const content = document.getElementById('pjdReporteContent').innerHTML;
    const html = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>Reporte - ${PROJECT_NAME}</title><style>body{font-family:Quicksand,sans-serif;max-width:850px;margin:30px auto;padding:30px;line-height:1.7;}table{border-collapse:collapse;width:100%;margin:14px 0}th,td{border:1px solid #ebebeb;padding:8px 12px;text-align:left}th{background:#fafbff}h1,h2,h3{color:#111}</style></head><body>${content}</body></html>`;
    const blob = new Blob([html], { type: 'text/html;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href = url; a.download = `reporte-${PROJECT_SLUG}.html`; a.click();
    URL.revokeObjectURL(url);
  });

  @if(!empty($project->report_content ?? null))
    document.getElementById('pjdReporteActions').style.display = '';
  @endif

  // ============ CITAS ============
  const citaModal = document.getElementById('pjdCitaModal');
  const citaBackdrop = document.getElementById('pjdCitaBackdrop');
  const citaQuote = document.getElementById('pjdCitaQuote');
  const citaSource = document.getElementById('pjdCitaSource');
  const citaPage = document.getElementById('pjdCitaPage');
  const citaOpenDoc = document.getElementById('pjdCitaOpenDoc');

  function openCita(payload) {
    if (!payload) return;
    let data;
    try { data = typeof payload === 'string' ? JSON.parse(payload) : payload; } catch (e) { return; }
    citaQuote.textContent = data.cita || '—';
    citaSource.textContent = data.fuente || '—';
    citaPage.textContent = data.pagina ? ` · Página ${data.pagina}` : '';
    const url = data.fuente ? PROJECT_DOCS[data.fuente] : null;
    if (url) { citaOpenDoc.href = url; citaOpenDoc.style.display = ''; }
    else citaOpenDoc.style.display = 'none';
    citaModal.classList.add('is-open');
  }
  function closeCita() { citaModal.classList.remove('is-open'); }
  document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-cita]');
    if (el) openCita(el.getAttribute('data-cita'));
  });
  document.getElementById('pjdCitaClose')?.addEventListener('click', closeCita);
  document.getElementById('pjdCitaCloseBtn')?.addEventListener('click', closeCita);
  citaBackdrop?.addEventListener('click', closeCita);
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeCita(); });

})();
</script>
@endpush