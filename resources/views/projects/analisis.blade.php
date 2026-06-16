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
  .pjd-left { display: flex; flex-direction: column; border-right: 1px solid var(--line); background: #fff; min-height: 0; position: sticky; top: 57px; height: calc(100vh - 57px); align-self: start; }
  @media (max-width: 1100px) { .pjd-left { position: static; height: auto; } }
  .pjd-right { display: flex; flex-direction: column; background: var(--bg); min-height: 0; overflow: auto; }

  /* ── CHAT ── */
  .pjd-chat-head { padding: 10px 18px; border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: flex-end; }
  .pjd-chat-reset { background: var(--bg); border: 1px solid var(--line); padding: 5px 12px; border-radius: 999px; font-size: .8rem; font-weight: 600; color: var(--ink2); cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: all .18s; }
  .pjd-chat-reset:hover { background: var(--blue-soft); color: var(--blue); border-color: var(--blue); }
  .pjd-chat-list { flex: 1; min-height: 0; padding: 18px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; max-height: calc(100vh - 240px); scroll-behavior: smooth; overscroll-behavior: contain; }
  @media (max-width: 1100px) { .pjd-chat-list { max-height: 60vh; } }
  .pjd-msg { max-width: 90%; }
  .pjd-msg.is-user { align-self: flex-end; max-width: 80%; }
  .pjd-msg.is-assistant { align-self: flex-start; display: flex; gap: 10px; }
  .pjd-msg-avatar { width: 28px; height: 28px; border-radius: 50%; background: var(--ink); color: #fff; display: grid; place-items: center; font-weight: 700; font-size: .8rem; flex-shrink: 0; }
  .pjd-msg-body { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 10px 14px; font-size: .92rem; line-height: 1.5; }
  .pjd-msg.is-user .pjd-msg-body { background: var(--bg); border-color: var(--line); }
  .pjd-msg-meta { font-size: .72rem; color: var(--muted); margin-bottom: 3px; font-weight: 700; }
  .pjd-msg.is-assistant .pjd-msg-meta { display: flex; align-items: center; gap: 6px; }

  /* ── Markdown dentro del chat ── */
  .pjd-msg-body h3.pjd-md-h { margin: 12px 0 6px; font-size: 1rem; font-weight: 700; color: var(--ink); }
  .pjd-msg-body h4.pjd-md-h { margin: 10px 0 4px; font-size: .92rem; font-weight: 700; color: var(--ink); }
  .pjd-msg-body p.pjd-md-p { margin: 0 0 8px; }
  .pjd-msg-body p.pjd-md-p:last-child { margin-bottom: 0; }
  .pjd-msg-body ul.pjd-md-ul, .pjd-msg-body ol.pjd-md-ol { margin: 6px 0 8px; padding-left: 20px; }
  .pjd-msg-body ul.pjd-md-ul li, .pjd-msg-body ol.pjd-md-ol li { margin: 3px 0; line-height: 1.5; }
  .pjd-msg-body ul.pjd-md-ul { list-style: none; padding-left: 4px; }
  .pjd-msg-body ul.pjd-md-ul li { position: relative; padding-left: 16px; }
  .pjd-msg-body ul.pjd-md-ul li::before { content: ""; position: absolute; left: 2px; top: .55em; width: 5px; height: 5px; border-radius: 50%; background: var(--blue); }
  .pjd-msg-body strong { color: var(--ink); font-weight: 700; }
  .pjd-msg-body code.pjd-md-code { background: var(--bg); border: 1px solid var(--line); border-radius: 5px; padding: 1px 6px; font-size: .85em; font-family: ui-monospace, Menlo, Consolas, monospace; }

  .pjd-chat-input { padding: 14px 18px; border-top: 1px solid var(--line); background: #fff; display: flex; align-items: center; gap: 10px; }
  .pjd-chat-input input { flex: 1; border: 1px solid var(--line); border-radius: 999px; padding: 10px 16px; font-family: inherit; font-size: .92rem; outline: none; transition: border-color .2s; }
  .pjd-chat-input input:focus { border-color: var(--blue); }
  .pjd-chat-send { width: 38px; height: 38px; border-radius: 50%; background: var(--ink); color: #fff; border: none; cursor: pointer; display: grid; place-items: center; transition: transform .12s; }
  .pjd-chat-send:hover { transform: scale(1.05); }
  .pjd-chat-send:disabled { opacity: .5; cursor: not-allowed; }

  /* ── Tablas en chat ── */
  .pjd-chat-table-wrap { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 14px; margin-top: 4px; max-width: 100%; overflow-x: auto; }
  .pjd-chat-table-actions { display: flex; gap: 8px; justify-content: flex-end; margin-bottom: 12px; flex-wrap: wrap; }
  .pjd-chat-table-btn { background: var(--bg); border: 1px solid var(--line); padding: 6px 14px; border-radius: 999px; font-family: inherit; font-size: .8rem; font-weight: 700; color: var(--ink2); cursor: pointer; transition: all .15s; display: inline-flex; align-items: center; gap: 5px; }
  .pjd-chat-table-btn:hover { border-color: var(--blue); color: var(--blue); background: var(--blue-soft); }
  .pjd-chat-table-btn.is-primary { background: var(--blue); color: #fff; border-color: var(--blue); }
  .pjd-chat-table-btn.is-primary:hover { filter: brightness(1.05); color: #fff; background: var(--blue); }
  .pjd-chat-table { width: 100%; border-collapse: collapse; font-size: .92rem; border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
  .pjd-chat-table th { background: #f3f4f6; color: var(--ink); font-weight: 700; text-align: left; padding: 14px 16px; border-bottom: 1px solid var(--line); vertical-align: top; line-height: 1.4; }
  .pjd-chat-table td { padding: 14px 16px; border-bottom: 1px solid var(--line); color: var(--ink); vertical-align: top; line-height: 1.55; font-weight: 500; }
  .pjd-chat-table tr:last-child td { border-bottom: none; }
  .pjd-chat-table th + th, .pjd-chat-table td + td { border-left: 1px solid var(--line); }

  /* ── Panel derecho ── */
  .pjd-pane { padding: 18px 22px; display: none; }
  .pjd-pane.is-active { display: block; }
  .pjd-pane-title { font-size: 1.05rem; font-weight: 700; color: var(--ink); margin: 0 0 4px; padding-right: 36px; }

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

  .pjd-field, .pjd-qa { transition: background .18s ease, box-shadow .18s ease; border-radius: 12px; }
  .pjd-field.has-cita, .pjd-qa.has-cita, .pjd-field.has-no-cita, .pjd-qa.has-no-cita { cursor: pointer; padding-right: 112px; }
  .pjd-field.has-cita:hover, .pjd-qa.has-cita:hover { background: #f8fbff; box-shadow: inset 3px 0 0 var(--blue); }
  .pjd-field.has-no-cita:hover, .pjd-qa.has-no-cita:hover { background: #fbfbfb; }
  .pjd-cita-badge { position: absolute; top: 14px; right: 8px; font-size: .68rem; font-weight: 700; color: var(--blue); background: var(--blue-soft); padding: 5px 12px; border-radius: 999px; border: 1px solid #c7dcfd; opacity: 0; transform: translateY(-2px); transition: opacity .18s, transform .18s; pointer-events: none; white-space: nowrap; }
  .pjd-field.has-cita:hover .pjd-cita-badge, .pjd-qa.has-cita:hover .pjd-cita-badge { opacity: 1; transform: translateY(0); }
  .pjd-cita-badge.is-muted { color: var(--muted); background: #f4f5f7; border-color: var(--line); }

  /* ════════════ CITA DEL DOCUMENTO (panel inline) — PRO ════════════ */
  .pjd-source-panel { display: none; padding: 12px 0 4px; }
  .pjd-field.is-source-open .pjd-source-panel, .pjd-qa.is-source-open .pjd-source-panel { display: block; animation: pjdSourceIn .22s cubic-bezier(.22,1,.36,1) both; }
  @keyframes pjdSourceIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }

  .pjd-source-card {
    position: relative;
    border: 1px solid #e6eef9;
    border-radius: 16px;
    background: linear-gradient(180deg, #fbfdff 0%, #ffffff 55%);
    padding: 20px 22px 18px;
    box-shadow: 0 10px 28px rgba(15, 40, 90, .07);
    overflow: hidden;
  }
  .pjd-source-card::before {
    content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
    background: linear-gradient(180deg, var(--blue), #5aa6ff);
  }
  .pjd-source-card.is-empty { box-shadow: none; background: #fafafa; border-color: #eee; }
  .pjd-source-card.is-empty::before { background: #d6d6d6; }

  .pjd-source-close {
    position: absolute; top: 14px; right: 14px;
    width: 30px; height: 30px; border: none; border-radius: 9px;
    background: #f1f5fb; color: #94a0b2; cursor: pointer; font-size: .95rem;
    display: grid; place-items: center; transition: all .15s ease;
  }
  .pjd-source-close:hover { background: #e6eefb; color: var(--blue); }

  .pjd-source-title {
    display: inline-flex; align-items: center; gap: 10px;
    margin: 0 40px 16px 0; font-size: 1rem; font-weight: 700; color: var(--ink);
  }
  .pjd-source-title::before {
    content: ""; width: 30px; height: 30px; border-radius: 9px; flex-shrink: 0;
    background: var(--blue-soft) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23007aff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z'/%3E%3Cpath d='M14 2v6h6'/%3E%3Cpath d='M9 13h6M9 17h4'/%3E%3C/svg%3E") center / 16px no-repeat;
  }
  .pjd-source-card.is-empty .pjd-source-title::before { background-color: #eee; filter: grayscale(1) opacity(.6); }

  .pjd-source-quote {
    position: relative;
    margin: 0 0 16px; padding: 15px 18px 15px 46px;
    background: #f5f9ff; border: 1px solid #e8f0fc; border-radius: 13px;
    color: #3a4658; font-size: .96rem; line-height: 1.65; font-style: italic;
    white-space: pre-wrap;
  }
  .pjd-source-quote::before {
    content: "\201C"; position: absolute; left: 14px; top: 4px;
    font-size: 2.6rem; line-height: 1; color: #bcd6ff;
    font-family: Georgia, 'Times New Roman', serif; font-style: normal;
  }
  .pjd-source-card.is-empty .pjd-source-quote { background: #f7f7f7; border-color: #ececec; color: #9aa0a8; }
  .pjd-source-card.is-empty .pjd-source-quote::before { color: #d8d8d8; }

  .pjd-source-meta {
    display: flex; align-items: center; flex-wrap: wrap; gap: 8px;
    font-size: .82rem; color: #6b7280;
  }
  .pjd-source-meta strong {
    font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
    color: #9aa6b8;
  }
  .pjd-source-meta > span {
    display: inline-flex; align-items: center; gap: 6px;
    max-width: 100%; padding: 6px 11px; border-radius: 9px;
    background: #f2f6fc; border: 1px solid #e4ecf7;
    font-size: .8rem; font-weight: 600; color: #51607a;
    font-family: ui-monospace, Menlo, Consolas, monospace;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  }
  .pjd-source-meta > span::before {
    content: ""; width: 13px; height: 13px; flex-shrink: 0;
    background: center/contain no-repeat url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2351607a' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z'/%3E%3Cpath d='M14 2v6h6'/%3E%3C/svg%3E");
  }

  .pjd-source-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 18px; }
  .pjd-source-btn {
    display: inline-flex; align-items: center; gap: 7px;
    border: none; border-radius: 10px; padding: 10px 18px;
    font-family: inherit; font-size: .86rem; font-weight: 700; cursor: pointer; text-decoration: none;
    transition: all .16s ease;
  }
  .pjd-source-btn::before { content: ""; width: 15px; height: 15px; flex-shrink: 0; background: center/contain no-repeat; }
  .pjd-source-btn:not(.is-ghost) {
    background: linear-gradient(180deg, #1d8bff, var(--blue)); color: #fff;
    box-shadow: 0 6px 16px rgba(0,122,255,.28);
  }
  .pjd-source-btn:not(.is-ghost):hover { transform: translateY(-1px); box-shadow: 0 10px 22px rgba(0,122,255,.34); }
  .pjd-source-btn:not(.is-ghost):active { transform: translateY(0); }
  .pjd-source-btn:not(.is-ghost)::before {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z'/%3E%3Ccircle cx='12' cy='12' r='3'/%3E%3C/svg%3E");
  }
  .pjd-source-btn.is-ghost {
    background: #fff; color: var(--blue); border: 1px solid #cfe0fb;
  }
  .pjd-source-btn.is-ghost:hover { background: var(--blue-soft); border-color: var(--blue); }
  .pjd-source-btn.is-ghost::before {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23007aff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6'/%3E%3Cpolyline points='15 3 21 3 21 9'/%3E%3Cline x1='10' y1='14' x2='21' y2='3'/%3E%3C/svg%3E");
  }

  /* Modal cita (legacy, sin uso) */
  .pjd-cita-modal { display: none; position: fixed; inset: 0; z-index: 250; align-items: center; justify-content: center; padding: 20px; }
  .pjd-cita-modal.is-open { display: flex; }
  .pjd-cita-modal-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.5); backdrop-filter: blur(8px); }
  .pjd-cita-modal-card { position: relative; z-index: 1; background: #fff; border-radius: 16px; max-width: 600px; width: 100%; box-shadow: 0 24px 64px rgba(0,0,0,.22); overflow: hidden; }
  .pjd-cita-modal-head { display: flex; align-items: center; justify-content: space-between; padding: 16px 22px; border-bottom: 1px solid var(--line); background: linear-gradient(180deg, #fafbff, #fff); }
  .pjd-cita-modal-head h4 { margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--ink); }
  .pjd-cita-close { border: none; background: var(--bg); width: 30px; height: 30px; border-radius: 8px; cursor: pointer; color: var(--muted); font-size: 14px; }
  .pjd-cita-modal-body { padding: 22px; }
  .pjd-cita-quote { border-left: 4px solid var(--blue); background: #f8faff; padding: 14px 16px; border-radius: 8px; font-size: .95rem; color: var(--ink); margin-bottom: 16px; white-space: pre-wrap; }
  .pjd-cita-source { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding: 12px 14px; background: #f8fafc; border-radius: 10px; border: 1px solid var(--line); }
  .pjd-cita-source-label { font-size: .82rem; color: var(--muted); font-weight: 600; }
  .pjd-cita-source-file { font-size: .92rem; font-weight: 700; color: var(--ink); }
  .pjd-cita-source-page { font-size: .82rem; color: var(--muted); }
  .pjd-cita-modal-footer { display: flex; gap: 10px; justify-content: flex-end; padding: 14px 22px; border-top: 1px solid var(--line); background: #fafbff; }
  .pjd-cita-btn { padding: 8px 18px; border-radius: 999px; font-family: inherit; font-weight: 700; font-size: .85rem; border: none; cursor: pointer; text-decoration: none; }
  .pjd-cita-btn-primary { background: var(--blue); color: #fff; }
  .pjd-cita-btn-ghost { background: transparent; color: var(--ink2); border: 1px solid var(--line); }

  /* ════════════ DRAWER PDF (fuente con resaltado) ════════════ */
  .pjd-doc-drawer { position: fixed; inset: 0; z-index: 400; display: none; }
  .pjd-doc-drawer.is-open { display: block; }
  .pjd-doc-drawer-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.42); backdrop-filter: blur(2px); animation: pjdFade .2s ease both; }
  @keyframes pjdFade { from { opacity: 0; } to { opacity: 1; } }
  .pjd-doc-drawer-panel { position: absolute; top: 0; left: 0; height: 100%; width: min(840px, 96vw); background: #f1f3f5; box-shadow: 6px 0 30px rgba(0,0,0,.2); display: flex; flex-direction: column; transform: translateX(-100%); transition: transform .3s cubic-bezier(.22,1,.36,1); }
  .pjd-doc-drawer.is-open .pjd-doc-drawer-panel { transform: translateX(0); }
  .pjd-doc-drawer-head { display: flex; align-items: center; gap: 8px; padding: 10px 14px; border-bottom: 1px solid var(--line); background: #fff; }
  .pjd-doc-drawer-file { flex: 1; min-width: 0; display: inline-flex; align-items: center; gap: 8px; font-size: .86rem; font-weight: 700; color: var(--ink); padding: 7px 12px; background: #f8fafc; border: 1px solid var(--line); border-radius: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .pjd-doc-drawer-file::before { content: "📄"; }
  .pjd-doc-drawer-toolbtn { border: 1px solid var(--line); background: #fff; color: var(--ink2); border-radius: 8px; padding: 7px 11px; font-family: inherit; font-size: .82rem; font-weight: 700; cursor: pointer; text-decoration: none; white-space: nowrap; }
  .pjd-doc-drawer-toolbtn:hover { border-color: var(--blue); color: var(--blue); }
  .pjd-doc-drawer-toolbtn.is-active { background: var(--blue); color: #fff; border-color: var(--blue); }
  .pjd-doc-drawer-close { width: 34px; height: 34px; border: none; background: var(--bg); color: var(--muted); border-radius: 8px; cursor: pointer; font-size: 1rem; flex-shrink: 0; }
  .pjd-doc-drawer-close:hover { background: var(--danger-soft); color: var(--danger); }
  .pjd-doc-drawer-nav { display: flex; align-items: center; gap: 10px; padding: 8px 14px; background: #fff; border-bottom: 1px solid var(--line); }
  .pjd-doc-drawer-nav button { border: 1px solid var(--line); background: #fff; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; font-weight: 700; color: var(--ink2); font-size: 1.05rem; line-height: 1; }
  .pjd-doc-drawer-nav button:hover { border-color: var(--blue); color: var(--blue); }
  .pjd-doc-drawer-pageind { font-size: .82rem; font-weight: 700; color: var(--ink2); }
  .pjd-pdf-scroll { flex: 1; min-height: 0; overflow: auto; background: #525659; padding: 16px; display: flex; flex-direction: column; align-items: center; }
  .pjd-pdf-container { position: relative; }
  .pjd-pdf-container canvas { display: block; box-shadow: 0 6px 24px rgba(0,0,0,.3); border-radius: 2px; }
  .pjd-pdf-highlights { position: absolute; top: 0; left: 0; pointer-events: none; }
  .pjd-pdf-hl { position: absolute; background: rgba(0,122,255,.30); border-radius: 2px; mix-blend-mode: multiply; box-shadow: 0 0 0 1px rgba(0,122,255,.4); }
  .pjd-pdf-loading { color: #cfd2d6; font-weight: 600; padding: 50px 20px; text-align: center; }
  .pjd-doc-drawer-quote { background: #fff; border-top: 1px solid var(--line); padding: 14px 16px; max-height: 34%; overflow: auto; }
  .pjd-doc-drawer-quote[hidden] { display: none; }
  .pjd-doc-drawer-quote-kicker { display: inline-flex; align-items: center; gap: 6px; font-size: .72rem; font-weight: 700; color: var(--blue); background: var(--blue-soft); border: 1px solid #c7dcfd; padding: 4px 10px; border-radius: 999px; margin-bottom: 10px; }
  .pjd-doc-drawer-quote-text { font-size: .92rem; line-height: 1.6; color: var(--ink); border-left: 3px solid var(--blue); background: #f8fbff; padding: 12px 14px; border-radius: 10px; white-space: pre-wrap; }
  .pjd-doc-drawer-quote-meta { margin-top: 8px; font-size: .8rem; color: var(--muted); font-weight: 600; }

  /* ════════════ CHECKLIST AVANZADO ════════════ */
  .pjd-checklist-wrap { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 16px; }
  .pjd-checklist-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
  .pjd-checklist-title { font-size: 1.05rem; font-weight: 700; color: var(--ink); display: inline-flex; align-items: center; gap: 8px; }
  .pjd-checklist-title .star { color: var(--blue); }
  .pjd-checklist-head-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
  .pjd-checklist-link { color: var(--blue); font-weight: 700; font-size: .82rem; text-decoration: none; padding: 6px 10px; border-radius: 8px; display: inline-flex; align-items: center; gap: 5px; cursor: pointer; background: transparent; border: none; }
  .pjd-checklist-link:hover { background: var(--blue-soft); }

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

  .pjd-cl-table-wrap { overflow-x: auto; }
  .pjd-cl-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .86rem; background: #fff; border-radius: 10px; overflow: hidden; border: 1px solid var(--line); min-width: 900px; }
  .pjd-cl-table thead th { background: #fafbff; padding: 10px 12px; font-weight: 700; color: var(--muted); text-align: left; font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; border-bottom: 1px solid var(--line); white-space: nowrap; }
  .pjd-cl-table tbody td { padding: 10px 12px; border-bottom: 1px solid var(--line); color: var(--ink2); vertical-align: middle; }
  .pjd-cl-table tbody tr:last-child td { border-bottom: none; }
  .pjd-cl-table tbody tr:hover { background: #fafbff; }

  .pjd-cl-table tbody tr[data-row] { cursor: pointer; }
  .pjd-cl-table tbody tr[data-row]:hover .pjd-cl-requisito-text { color: var(--blue); }
  .pjd-cl-table tbody tr[data-row].is-expanded .pjd-cl-requisito-text { color: var(--blue); }
  .pjd-cl-table tbody tr.is-expanded { background: #f5f7fb; }

  .pjd-cl-row-toggle { background: transparent; border: none; cursor: pointer; padding: 2px; color: var(--muted); display: inline-flex; align-items: center; }
  .pjd-cl-row-toggle svg { width: 13px; height: 13px; transition: transform .2s; }
  tr.is-expanded .pjd-cl-row-toggle svg { transform: rotate(90deg); }

  .pjd-cl-requisito { display: flex; align-items: center; gap: 8px; max-width: 380px; }
  .pjd-cl-requisito-text { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 600; color: var(--ink); }

  .pjd-cl-table tbody tr[data-row] { cursor: pointer; transition: background .18s ease, box-shadow .18s ease; }
  .pjd-cl-table tbody tr[data-row]:hover { background: #f8faff; }
  .pjd-cl-table tbody tr[data-row].is-expanded { background: #f8faff; box-shadow: inset 3px 0 0 var(--blue); }

  .pjd-cl-detail { background: #fff; padding: 16px 18px 18px; font-size: .85rem; color: var(--ink2); border-top: 1px solid var(--line); animation: pjdSourceReveal .22s cubic-bezier(.22,1,.36,1) both; }
  @keyframes pjdSourceReveal { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
  .pjd-cl-detail strong { color: var(--ink); }
  .pjd-cl-detail-grid { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(240px, .8fr); gap: 12px; align-items: stretch; }
  @media (max-width: 900px) { .pjd-cl-detail-grid { grid-template-columns: 1fr; } }
  .pjd-cl-detail-card { background: var(--bg); border: 1px solid var(--line); border-radius: 12px; padding: 12px 14px; }
  .pjd-cl-detail-kicker { display: inline-flex; align-items: center; gap: 6px; padding: 4px 9px; border-radius: 999px; background: var(--blue-soft); color: var(--blue); font-size: .72rem; font-weight: 700; margin-bottom: 8px; }
  .pjd-cl-detail-row { margin-bottom: 8px; line-height: 1.55; }
  .pjd-cl-detail-row:last-child { margin-bottom: 0; }
  .pjd-cl-source-quote { margin-top: 8px; padding: 12px 14px; border-left: 3px solid var(--blue); background: #fff; border-radius: 10px; color: var(--ink); font-weight: 600; line-height: 1.55; white-space: pre-wrap; }
  .pjd-cl-source-meta { display: flex; flex-direction: column; gap: 8px; }
  .pjd-cl-source-pill { display: inline-flex; width: fit-content; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; font-weight: 700; font-size: .78rem; background: var(--blue-soft); color: var(--blue); border: 1px solid #c7dcfd; }
  .pjd-cl-source-empty { color: var(--muted); font-weight: 600; }
  .pjd-cl-source-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
  .pjd-cl-source-btn { border: 1px solid var(--blue); background: #fff; color: var(--blue); border-radius: 999px; padding: 7px 12px; font-family: inherit; font-size: .8rem; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all .15s; }
  .pjd-cl-source-btn:hover { background: var(--blue-soft); transform: translateY(-1px); }
  .pjd-cl-source-btn:active { transform: scale(.98); }

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
  .pjd-draft-editor table { border-collapse: collapse; width: 100%; margin: 14px 0; }
  .pjd-draft-editor th, .pjd-draft-editor td { border: 1px solid #e5e7eb; padding: 10px 12px; text-align: left; }
  .pjd-draft-editor th { background: #f3f4f6; font-weight: 700; }

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

  .pjd-toast { position: fixed; bottom: 24px; right: 24px; background: var(--ink); color: #fff; padding: 12px 18px; border-radius: 10px; font-size: .88rem; font-weight: 600; z-index: 9999; box-shadow: 0 10px 30px rgba(0,0,0,.2); animation: pjdToastIn .25s ease both; }
  .pjd-toast.is-success { background: var(--success); }
  .pjd-toast.is-error { background: var(--danger); }
  @keyframes pjdToastIn { from { opacity:0; transform: translateY(20px); } to { opacity:1; transform: translateY(0); } }


  /* ═══════════════════════════════════════════════════════════════
     AJUSTE PRO: PANEL EXPANDIBLE TIPO WORKSPACE / YOUTUBE
     Mantiene la lógica Blade/PHP/JS intacta y mejora la expansión visual.
  ═══════════════════════════════════════════════════════════════ */
  *, *::before, *::after { box-sizing: border-box; }

  html { scroll-behavior: smooth; }

  body {
    margin: 0;
    background: var(--bg);
    color: var(--ink2);
    -webkit-font-smoothing: antialiased;
    text-rendering: geometricPrecision;
  }

  button, input, textarea, select, a { -webkit-tap-highlight-color: transparent; }

  .pjd-wrap {
    background:
      radial-gradient(circle at top left, rgba(0,122,255,.035), transparent 28%),
      var(--bg);
  }

  .pjd-topbar {
    min-height: 64px;
    padding: 12px clamp(16px, 2.2vw, 32px);
    background: rgba(255,255,255,.92);
    backdrop-filter: saturate(180%) blur(16px);
    box-shadow: 0 1px 0 rgba(0,0,0,.03);
  }

  .pjd-back,
  .pjd-view-doc,
  .pjd-tab,
  .pjd-chat-reset,
  .pjd-chat-send,
  .pjd-chat-table-btn,
  .pjd-cl-btn,
  .pjd-checklist-link,
  .pjd-source-btn,
  .pjd-cl-source-btn,
  .pjd-reporte-btn,
  .pjd-doc-link,
  .pjd-borrador-tab,
  .pjd-draft-btn,
  .pjd-doc-drawer-toolbtn,
  .pjd-doc-drawer-nav button,
  .pjd-doc-drawer-close,
  .pjd-source-close,
  .pjd-cl-options,
  .pjd-cl-add {
    will-change: transform, box-shadow, background, color, border-color;
  }

  .pjd-back:active,
  .pjd-view-doc:active,
  .pjd-tab:active,
  .pjd-chat-reset:active,
  .pjd-chat-send:active,
  .pjd-chat-table-btn:active,
  .pjd-cl-btn:active,
  .pjd-checklist-link:active,
  .pjd-source-btn:active,
  .pjd-cl-source-btn:active,
  .pjd-reporte-btn:active,
  .pjd-doc-link:active,
  .pjd-borrador-tab:active,
  .pjd-draft-btn:active,
  .pjd-doc-drawer-toolbtn:active,
  .pjd-doc-drawer-nav button:active,
  .pjd-doc-drawer-close:active,
  .pjd-source-close:active,
  .pjd-cl-options:active,
  .pjd-cl-add:active {
    transform: scale(.98);
  }

  .pjd-body {
    grid-template-columns: minmax(420px, .92fr) minmax(620px, 1.08fr);
    background: var(--bg);
  }

  .pjd-left {
    top: 64px;
    height: calc(100vh - 64px);
    border-right: 1px solid var(--line);
    background: rgba(255,255,255,.92);
  }

  .pjd-right {
    padding: clamp(14px, 2vw, 28px);
    background: var(--bg);
  }

  .pjd-pane {
    padding: 0;
    max-width: 1160px;
    width: 100%;
    margin: 0 auto;
  }

  .pjd-card,
  .pjd-checklist-wrap,
  .pjd-inicio-card,
  .pjd-reporte-content,
  .pjd-reporte-empty,
  .pjd-doc,
  .pjd-chat-table-wrap {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
    transition: transform .22s cubic-bezier(.22,1,.36,1), box-shadow .22s ease, border-color .22s ease;
  }

  .pjd-card:hover,
  .pjd-checklist-wrap:hover,
  .pjd-inicio-card:hover,
  .pjd-doc:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(17,17,17,.045);
    border-color: #e3e7ee;
  }

  .pjd-card {
    overflow: hidden;
    margin-bottom: 16px;
  }

  .pjd-card-head {
    min-height: 58px;
    padding: 16px 18px;
    background: #fff;
    border-bottom: 1px solid transparent;
    position: relative;
  }

  .pjd-card.is-open .pjd-card-head { border-bottom-color: var(--line); }

  .pjd-card-head:hover { background: #fcfdff; }

  .pjd-card-head::after {
    content: "";
    position: absolute;
    left: 18px;
    right: 18px;
    bottom: 0;
    height: 1px;
    background: var(--line);
    opacity: 0;
    transition: opacity .18s ease;
  }

  .pjd-card.is-open .pjd-card-head::after { opacity: 1; }

  .pjd-card-head h3 {
    font-size: .98rem;
    letter-spacing: -.01em;
  }

  .pjd-card-chev {
    width: 30px;
    height: 30px;
    border-radius: 999px;
    background: var(--bg);
    border: 1px solid var(--line);
    color: var(--muted);
    font-size: .95rem;
  }

  .pjd-card-head:hover .pjd-card-chev {
    background: var(--blue-soft);
    border-color: #c7dcfd;
    color: var(--blue);
  }

  .pjd-card-body {
    display: block;
    padding: 0 18px;
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transform: translateY(-4px);
    transition:
      max-height .36s cubic-bezier(.22,1,.36,1),
      opacity .22s ease,
      transform .22s ease,
      padding .22s ease;
  }

  .pjd-card.is-open .pjd-card-body {
    max-height: 3200px;
    opacity: 1;
    transform: translateY(0);
    padding: 10px 18px 18px;
  }

  .pjd-field,
  .pjd-qa {
    padding: 14px 12px;
    margin: 2px 0;
    border-bottom: 1px solid var(--line);
    border-radius: 12px;
  }

  .pjd-field:last-child,
  .pjd-qa:last-child { border-bottom: 0; }

  .pjd-field-label,
  .pjd-qa-q {
    background: transparent;
    color: var(--muted);
    padding: 0;
    margin-bottom: 7px;
    letter-spacing: .01em;
  }

  .pjd-field-value,
  .pjd-qa-a {
    padding: 0;
    color: var(--ink2);
    font-weight: 600;
  }

  .pjd-field.has-cita,
  .pjd-qa.has-cita,
  .pjd-field.has-no-cita,
  .pjd-qa.has-no-cita { padding-right: 128px; }

  .pjd-field.has-cita:hover,
  .pjd-qa.has-cita:hover,
  .pjd-field.is-source-open,
  .pjd-qa.is-source-open {
    background: #f8fbff;
    box-shadow: inset 3px 0 0 var(--blue);
  }

  .pjd-cita-badge {
    top: 14px;
    right: 12px;
    opacity: 1;
    transform: none;
    background: var(--blue-soft);
    border-color: #c7dcfd;
  }

  .pjd-source-panel {
    padding: 0;
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    display: block;
    transition: max-height .36s cubic-bezier(.22,1,.36,1), opacity .22s ease, padding .22s ease;
  }

  .pjd-field.is-source-open .pjd-source-panel,
  .pjd-qa.is-source-open .pjd-source-panel {
    max-height: 1200px;
    opacity: 1;
    padding: 14px 0 2px;
  }

  .pjd-source-card {
    background: var(--card);
    border: 1px solid var(--line);
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
  }

  .pjd-source-card::before { background: var(--blue); }

  .pjd-source-quote,
  .pjd-doc-drawer-quote-text,
  .pjd-cl-source-quote {
    background: #f8fbff;
    border-color: #dceaff;
    color: var(--ink2);
  }

  .pjd-source-btn:not(.is-ghost),
  .pjd-chat-table-btn.is-primary,
  .pjd-cl-btn.is-primary,
  .pjd-reporte-btn,
  .pjd-borrador-tab.is-active,
  .pjd-tab.is-active {
    background: var(--blue);
    color: #fff;
    border-color: var(--blue);
    box-shadow: 0 8px 18px rgba(0,122,255,.16);
  }

  .pjd-source-btn:not(.is-ghost):hover,
  .pjd-chat-table-btn.is-primary:hover,
  .pjd-cl-btn.is-primary:hover,
  .pjd-reporte-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px rgba(0,122,255,.20);
    filter: none;
  }

  .pjd-source-btn.is-ghost,
  .pjd-cl-source-btn,
  .pjd-doc-link {
    background: #fff;
    color: var(--blue);
    border: 1px solid var(--blue);
  }

  .pjd-source-btn.is-ghost:hover,
  .pjd-cl-source-btn:hover,
  .pjd-doc-link:hover {
    background: var(--blue-soft);
    border-color: var(--blue);
  }

  input,
  textarea,
  select,
  .pjd-draft-editor {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 8px;
    color: var(--ink2);
  }

  input:focus,
  textarea:focus,
  select:focus,
  .pjd-draft-editor:focus,
  .pjd-chat-input input:focus {
    outline: none;
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .pjd-chat-head,
  .pjd-chat-input {
    background: rgba(255,255,255,.96);
  }

  .pjd-chat-list {
    padding: 22px;
    max-height: calc(100vh - 248px);
  }

  .pjd-msg-avatar {
    background: var(--blue);
    box-shadow: 0 6px 16px rgba(0,122,255,.18);
  }

  .pjd-msg-body {
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
  }

  .pjd-msg.is-user .pjd-msg-body {
    background: var(--blue);
    color: #fff;
    border-color: var(--blue);
    box-shadow: 0 8px 18px rgba(0,122,255,.16);
  }

  .pjd-chat-send {
    background: var(--blue);
    box-shadow: 0 8px 18px rgba(0,122,255,.16);
  }

  .pjd-chat-send:hover { transform: translateY(-1px); }

  .pjd-checklist-wrap { padding: 18px; }

  .pjd-checklist-head {
    padding: 2px 2px 16px;
    border-bottom: 1px solid var(--line);
  }

  .pjd-checklist-title {
    color: var(--ink);
    letter-spacing: -.015em;
  }

  .pjd-counters {
    margin: 18px 0;
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }

  .pjd-counter {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 13px 14px;
    box-shadow: 0 4px 12px rgba(0,0,0,.015);
  }

  .pjd-counter.is-total {
    background: #fff;
    border-color: #c7dcfd;
    box-shadow: inset 0 0 0 1px var(--blue-soft);
  }

  .pjd-cl-toolbar {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 10px;
    margin-bottom: 14px;
    box-shadow: 0 4px 12px rgba(0,0,0,.015);
  }

  .pjd-cl-search input {
    border-radius: 999px;
    min-height: 40px;
  }

  .pjd-cl-table-wrap {
    border: 1px solid var(--line);
    border-radius: 16px;
    background: #fff;
    overflow: auto;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.8);
  }

  .pjd-cl-table {
    border: 0;
    border-radius: 0;
    min-width: 980px;
  }

  .pjd-cl-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #fff;
    color: var(--muted);
    border-bottom: 1px solid var(--line);
    padding: 14px 14px;
  }

  .pjd-cl-table tbody td {
    padding: 14px;
    background: #fff;
  }

  .pjd-cl-table tbody tr[data-row] {
    transition: background .18s ease, box-shadow .18s ease, transform .18s ease;
  }

  .pjd-cl-table tbody tr[data-row]:hover td {
    background: #fbfdff;
  }

  .pjd-cl-table tbody tr[data-row].is-expanded td {
    background: #f8fbff;
  }

  .pjd-cl-detail {
    padding: 18px;
    background: #f8fbff;
    border-top: 1px solid #dceaff;
  }

  .pjd-cl-detail-card {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
  }

  .pjd-cl-status {
    border: 1px solid transparent;
  }

  .pjd-cl-status.is-pendiente { color: var(--warning); background: var(--warning-soft); border-color: #fde68a; }
  .pjd-cl-status.is-revision { color: var(--blue); background: var(--blue-soft); border-color: #c7dcfd; }
  .pjd-cl-status.is-aprobado { color: var(--success); background: var(--success-soft); border-color: #bbf7d0; }

  .pjd-cl-add {
    margin-top: 14px;
    min-height: 46px;
    border-radius: 14px;
    background: #fff;
  }

  .pjd-borrador-tabs {
    background: #fff;
    border-radius: 999px;
    padding: 5px;
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
  }

  .pjd-draft-toolbar {
    background: #fff;
    border: 1px solid var(--line);
    border-bottom: 0;
    border-radius: 16px 16px 0 0;
    padding: 10px;
  }

  .pjd-draft-editor {
    border-radius: 0 0 16px 16px;
    min-height: 540px;
    line-height: 1.65;
  }

  .pjd-doc-drawer-backdrop {
    background: rgba(17,24,39,.34);
    backdrop-filter: blur(8px);
  }

  .pjd-doc-drawer-panel {
    width: min(980px, 96vw);
    background: var(--bg);
    box-shadow: 18px 0 50px rgba(17,24,39,.18);
  }

  .pjd-doc-drawer-head,
  .pjd-doc-drawer-nav,
  .pjd-doc-drawer-quote { background: #fff; }

  .pjd-pdf-scroll { background: #f3f4f6; }

  .pjd-pdf-container canvas {
    border-radius: 12px;
    box-shadow: 0 14px 38px rgba(17,24,39,.16);
  }

  .pjd-pdf-hl {
    background: rgba(0,122,255,.24);
    box-shadow: 0 0 0 1px rgba(0,122,255,.45), 0 4px 12px rgba(0,122,255,.16);
  }

  @media (max-width: 1100px) {
    .pjd-body { grid-template-columns: 1fr; }
    .pjd-left { top: 0; height: auto; position: static; }
    .pjd-right { padding: 16px; }
    .pjd-counters { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  }

  @media (max-width: 720px) {
    .pjd-topbar { gap: 10px; }
    .pjd-tabs { overflow-x: auto; flex-wrap: nowrap; padding-bottom: 4px; }
    .pjd-tab { flex: 0 0 auto; }
    .pjd-field.has-cita,
    .pjd-qa.has-cita,
    .pjd-field.has-no-cita,
    .pjd-qa.has-no-cita { padding-right: 12px; }
    .pjd-cita-badge { position: static; margin-top: 10px; width: fit-content; display: flex; }
    .pjd-counters { grid-template-columns: 1fr; }
  }

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
          'criterio_cumplimiento' => $it['criterio_cumplimiento'] ?? '',
          'formato'       => $it['formato'] ?? 'No aplica',
          'categoria'     => $it['categoria'] ?? 'Legal-Administrativo',
          'aplicabilidad' => $it['aplicabilidad'] ?? 'Único',
          'obligatorio'   => $it['obligatorio'] ?? 'Sí',
          'cumplimiento'  => $it['cumplimiento'] ?? '-',
          'status'        => $it['status'] ?? 'Pendiente',
          'prioridad'     => $it['prioridad'] ?? 'Media',
          'fuente'        => $it['fuente'] ?? '',
          'pagina'        => $it['pagina'] ?? null,
          'cita'          => $it['cita'] ?? $it['evidencia'] ?? $it['fragmento'] ?? '',
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

  $normalizaFuente = function ($text) {
      $text = Str::ascii((string) $text);
      $text = mb_strtolower($text, 'UTF-8');
      $text = preg_replace('/[^a-z0-9]+/', ' ', $text);
      return trim(preg_replace('/\s+/', ' ', $text));
  };

  $resolverCita = function ($citas, $key, $value = null, $label = null) use ($normalizaFuente) {
      if (!is_array($citas) || empty($citas)) return null;

      $tieneEvidencia = function ($c) {
          return is_array($c) && (!empty($c['cita']) || !empty($c['fuente']) || !empty($c['pagina']));
      };

      if ($tieneEvidencia($citas[$key] ?? null)) {
          return $citas[$key];
      }

      $keyBase = preg_replace('/^ficha\.|^fechas_clave\.|^resumen_ejecutivo\./', '', (string) $key);
      $aliases = array_unique([
          $key,
          $keyBase,
          str_replace('_', ' ', $keyBase),
          str_replace('_', '.', $keyBase),
          Str::snake((string) $label),
          Str::slug((string) $label, '_'),
      ]);

      $aliasesNorm = array_filter(array_map($normalizaFuente, $aliases));

      foreach ($citas as $citaKey => $citaData) {
          if (!$tieneEvidencia($citaData)) continue;
          $citaKeyNorm = $normalizaFuente($citaKey);
          foreach ($aliasesNorm as $aliasNorm) {
              if ($citaKeyNorm === $aliasNorm || str_ends_with($citaKeyNorm, ' '.$aliasNorm) || str_contains($citaKeyNorm, $aliasNorm)) {
                  return $citaData;
              }
          }
      }

      $needle = $normalizaFuente(trim(($value ?? '').' '.($label ?? '')));
      $words = array_values(array_unique(array_filter(explode(' ', $needle), fn($w) => mb_strlen($w) >= 4)));
      if (count($words) < 2) return null;

      $best = null;
      $bestScore = 0;
      foreach ($citas as $citaData) {
          if (!$tieneEvidencia($citaData)) continue;
          $haystack = $normalizaFuente(($citaData['cita'] ?? '').' '.($citaData['fuente'] ?? ''));
          if (!$haystack) continue;

          $score = 0;
          foreach ($words as $w) {
              if (str_contains($haystack, $w)) $score++;
          }

          if ($score > $bestScore) {
              $bestScore = $score;
              $best = $citaData;
          }
      }

      $minScore = max(2, min(4, (int) ceil(count($words) * 0.30)));
      return $bestScore >= $minScore ? $best : null;
  };

  $citaPayload = function ($citas, $key, $value = null, $label = null) use ($resolverCita) {
      $c = $resolverCita($citas, $key, $value, $label);
      if (!is_array($c) || (empty($c['cita']) && empty($c['fuente']) && empty($c['pagina']))) return null;
      return json_encode([
          'cita'   => $c['cita'] ?? '',
          'fuente' => $c['fuente'] ?? '',
          'pagina' => $c['pagina'] ?? null,
      ], JSON_UNESCAPED_UNICODE);
  };

  $checklistCitaPayload = function ($it) {
      $hasSource = !empty($it['fuente']) || !empty($it['pagina']) || !empty($it['cita']) || !empty($it['descripcion']);
      if (!$hasSource) return null;

      return json_encode([
          'cita'        => $it['cita'] ?: ($it['descripcion'] ?? ''),
          'fuente'      => $it['fuente'] ?? '',
          'pagina'      => $it['pagina'] ?? null,
          'requisito'   => $it['requisito'] ?? '',
          'descripcion' => $it['descripcion'] ?? '',
      ], JSON_UNESCAPED_UNICODE);
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

    {{-- COLUMNA IZQUIERDA: CHAT --}}
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
              <div class="pjd-msg-body">Hola, soy tu asistente del proyecto. Puedes pedirme un resumen de las bases, los requisitos clave, las fechas importantes o cualquier duda sobre la licitación.</div>
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

    {{-- COLUMNA DERECHA: PANEL --}}
    <div class="pjd-right">

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
              @php
                $payload = $citaPayload($citas, $row['key'], $row['val'] ?? null, $row['label'] ?? null);
                $citaInfo = $resolverCita($citas, $row['key'], $row['val'] ?? null, $row['label'] ?? null);
                $fuente = is_array($citaInfo) ? ($citaInfo['fuente'] ?? null) : null;
                $pagina = is_array($citaInfo) ? ($citaInfo['pagina'] ?? null) : null;
                $citaTexto = is_array($citaInfo) ? ($citaInfo['cita'] ?? null) : null;
                $docUrl = $fuente ? optional($project->documents->firstWhere('filename', $fuente))->url : null;
              @endphp
              <div class="pjd-field {{ $payload ? 'has-cita' : 'has-no-cita' }}" @if($payload) data-cita="{{ $payload }}" @endif>
                <div class="pjd-field-label">{{ $row['label'] }}</div>
                <div class="pjd-field-value">{{ $row['val'] ?: 'No se encontró información' }}</div>
                @if($payload)<div class="pjd-cita-badge">📄 Ver fuente</div>@endif
                <div class="pjd-source-panel" hidden>
                  <div class="pjd-source-card {{ $payload ? '' : 'is-empty' }}">
                    <button type="button" class="pjd-source-close" aria-label="Cerrar fuente">✕</button>
                    <div class="pjd-source-title">{{ $payload ? 'Cita del documento' : 'Fuente no registrada' }}</div>
                    <div class="pjd-source-quote">{{ $citaTexto ?: 'No hay cita textual guardada para este dato. Ya se intentó buscar por clave y por coincidencia del texto en structured_data.citas. Si sigue apareciendo así, el backend/IA no guardó evidencia específica para este valor.' }}</div>
                    <div class="pjd-source-meta">
                      <strong>Fuente:</strong>
                      <span>{{ $fuente ?: 'Sin archivo fuente registrado' }}</span>
                      @if($pagina)<span> · Página {{ $pagina }}</span>@endif
                    </div>
                    <div class="pjd-source-actions">
                      @if($payload)<button type="button" class="pjd-source-btn js-open-cita" data-cita="{{ $payload }}">Ver cita</button>@endif
                      @if($docUrl)<a href="{{ $docUrl }}" target="_blank" class="pjd-source-btn is-ghost">Ver documento</a>@endif
                    </div>
                  </div>
                </div>
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
              @php
                $payload = $citaPayload($citas, $row['key'], $row['val'] ?? null, $row['label'] ?? null);
                $citaInfo = $resolverCita($citas, $row['key'], $row['val'] ?? null, $row['label'] ?? null);
                $fuente = is_array($citaInfo) ? ($citaInfo['fuente'] ?? null) : null;
                $pagina = is_array($citaInfo) ? ($citaInfo['pagina'] ?? null) : null;
                $citaTexto = is_array($citaInfo) ? ($citaInfo['cita'] ?? null) : null;
                $docUrl = $fuente ? optional($project->documents->firstWhere('filename', $fuente))->url : null;
              @endphp
              <div class="pjd-field {{ $payload ? 'has-cita' : 'has-no-cita' }}" @if($payload) data-cita="{{ $payload }}" @endif>
                <div class="pjd-field-label">{{ $row['label'] }}</div>
                <div class="pjd-field-value">{{ $row['val'] ?: 'No se encontró información' }}</div>
                @if($payload)<div class="pjd-cita-badge">📄 Ver fuente</div>@endif
                <div class="pjd-source-panel" hidden>
                  <div class="pjd-source-card {{ $payload ? '' : 'is-empty' }}">
                    <button type="button" class="pjd-source-close" aria-label="Cerrar fuente">✕</button>
                    <div class="pjd-source-title">{{ $payload ? 'Cita del documento' : 'Fuente no registrada' }}</div>
                    <div class="pjd-source-quote">{{ $citaTexto ?: 'No hay cita textual guardada para este dato. Ya se intentó buscar por clave y por coincidencia del texto en structured_data.citas. Si sigue apareciendo así, el backend/IA no guardó evidencia específica para este valor.' }}</div>
                    <div class="pjd-source-meta">
                      <strong>Fuente:</strong>
                      <span>{{ $fuente ?: 'Sin archivo fuente registrado' }}</span>
                      @if($pagina)<span> · Página {{ $pagina }}</span>@endif
                    </div>
                    <div class="pjd-source-actions">
                      @if($payload)<button type="button" class="pjd-source-btn js-open-cita" data-cita="{{ $payload }}">Ver cita</button>@endif
                      @if($docUrl)<a href="{{ $docUrl }}" target="_blank" class="pjd-source-btn is-ghost">Ver documento</a>@endif
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      <div class="pjd-pane" data-pane="resumen">
        <div class="pjd-card is-open">
          <div class="pjd-card-head js-card-toggle">
            <h3>Resumen Ejecutivo <span class="sparkle">✨</span></h3>
            <div class="pjd-card-chev">▾</div>
          </div>
          <div class="pjd-card-body">
            @forelse($resumenEjec as $idx => $qa)
              @php
                $resumenKey = "resumen_ejecutivo.{$idx}";
                $respuestaResumen = $qa['respuesta'] ?? null;
                $preguntaResumen = $qa['pregunta'] ?? null;
                $payload = $citaPayload($citas, $resumenKey, $respuestaResumen, $preguntaResumen);
                $citaInfo = $resolverCita($citas, $resumenKey, $respuestaResumen, $preguntaResumen);
                $fuente = is_array($citaInfo) ? ($citaInfo['fuente'] ?? null) : null;
                $pagina = is_array($citaInfo) ? ($citaInfo['pagina'] ?? null) : null;
                $citaTexto = is_array($citaInfo) ? ($citaInfo['cita'] ?? null) : null;
                $docUrl = $fuente ? optional($project->documents->firstWhere('filename', $fuente))->url : null;
              @endphp
              <div class="pjd-qa {{ $payload ? 'has-cita' : 'has-no-cita' }}" @if($payload) data-cita="{{ $payload }}" @endif>
                <div class="pjd-qa-q">{{ $qa['pregunta'] ?? '' }}</div>
                <div class="pjd-qa-a">{{ $qa['respuesta'] ?? 'No se encontró información' }}</div>
                @if($payload)<div class="pjd-cita-badge">📄 Ver fuente</div>@endif
                <div class="pjd-source-panel" hidden>
                  <div class="pjd-source-card {{ $payload ? '' : 'is-empty' }}">
                    <button type="button" class="pjd-source-close" aria-label="Cerrar fuente">✕</button>
                    <div class="pjd-source-title">{{ $payload ? 'Cita del documento' : 'Fuente no registrada' }}</div>
                    <div class="pjd-source-quote">{{ $citaTexto ?: 'No hay cita textual guardada para esta respuesta. Ya se intentó buscar por clave y por coincidencia del texto en structured_data.citas. Si sigue apareciendo así, el backend/IA no guardó evidencia específica para esta respuesta.' }}</div>
                    <div class="pjd-source-meta">
                      <strong>Fuente:</strong>
                      <span>{{ $fuente ?: 'Sin archivo fuente registrado' }}</span>
                      @if($pagina)<span> · Página {{ $pagina }}</span>@endif
                    </div>
                    <div class="pjd-source-actions">
                      @if($payload)<button type="button" class="pjd-source-btn js-open-cita" data-cita="{{ $payload }}">Ver cita</button>@endif
                      @if($docUrl)<a href="{{ $docUrl }}" target="_blank" class="pjd-source-btn is-ghost">Ver documento</a>@endif
                    </div>
                  </div>
                </div>
              </div>
            @empty
              <p style="color:var(--muted);font-size:.9rem;padding:8px;">Sin información disponible.</p>
            @endforelse
          </div>
        </div>
      </div>

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
                  @php
                    $clPayload = $checklistCitaPayload($it);
                    $docMatch = !empty($it['fuente']) ? $project->documents->firstWhere('filename', $it['fuente']) : null;
                    $docUrl = $docMatch ? $docMatch->url : null;
                  @endphp
                  <tr data-row="{{ $idx }}" data-cumplimiento="{{ $it['cumplimiento'] }}" data-status="{{ $it['status'] }}" data-prioridad="{{ $it['prioridad'] }}" @if($clPayload) data-cita="{{ $clPayload }}" @endif>
                    <td><button type="button" class="pjd-cl-row-toggle" data-toggle="{{ $idx }}" title="Ver fuente y detalle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></button></td>
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
                    <td style="text-align:center"><button type="button" class="pjd-cl-options" data-toggle="{{ $idx }}" title="Ver fuente">···</button></td>
                  </tr>
                  <tr class="pjd-cl-detail-row" data-detail="{{ $idx }}" style="display:none;">
                    <td colspan="9" style="padding:0">
                      <div class="pjd-cl-detail">
                        <div class="pjd-cl-detail-grid">
                          <div class="pjd-cl-detail-card">
                            <div class="pjd-cl-detail-kicker">Detalle del requisito</div>
                            <div class="pjd-cl-detail-row"><strong>Requisito:</strong> {{ $it['requisito'] }}</div>
                            @if($it['descripcion'])
                              <div class="pjd-cl-detail-row"><strong>Descripción:</strong> {{ $it['descripcion'] }}</div>
                            @else
                              <div class="pjd-cl-detail-row pjd-cl-source-empty">Sin descripción adicional.</div>
                            @endif
                            @if(!empty($it['criterio_cumplimiento']))
                              <div style="margin:10px 0;padding:12px 14px;border-left:3px solid var(--success);background:var(--success-soft);border-radius:10px;">
                                <div style="font-size:.72rem;font-weight:700;color:var(--success);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;display:flex;align-items:center;gap:5px;">✓ Criterio para cumplir</div>
                                <div style="font-size:.88rem;color:var(--ink);line-height:1.55;">{{ $it['criterio_cumplimiento'] }}</div>
                              </div>
                            @endif
                            <div class="pjd-cl-detail-row"><strong>Prioridad:</strong> {{ $it['prioridad'] }}</div>
                          </div>

                          <div class="pjd-cl-detail-card">
                            <div class="pjd-cl-detail-kicker">Fuente / evidencia</div>
                            <div class="pjd-cl-source-meta">
                              @if($it['fuente'])
                                <div class="pjd-cl-detail-row"><strong>Archivo:</strong> {{ $it['fuente'] }}</div>
                              @else
                                <div class="pjd-cl-detail-row pjd-cl-source-empty">Sin archivo fuente registrado.</div>
                              @endif

                              @if($it['pagina'])
                                <span class="pjd-cl-source-pill">Página {{ $it['pagina'] }}</span>
                              @endif

                              @if($it['cita'])
                                <div class="pjd-cl-source-quote">{{ $it['cita'] }}</div>
                              @elseif($it['descripcion'])
                                <div class="pjd-cl-source-quote">{{ $it['descripcion'] }}</div>
                              @endif

                              <div class="pjd-cl-source-actions">
                                @if($clPayload)
                                  <button type="button" class="pjd-cl-source-btn" data-cita="{{ $clPayload }}">📄 Abrir cita</button>
                                @endif
                                @if($docUrl)
                                  <a href="{{ $docUrl }}" target="_blank" class="pjd-cl-source-btn">Ver documento</a>
                                @endif
                              </div>
                            </div>
                          </div>
                        </div>
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

{{-- DRAWER LATERAL: VISTA PREVIA DEL PDF CON RESALTADO --}}
<div class="pjd-doc-drawer" id="pjdDocDrawer" aria-hidden="true">
  <div class="pjd-doc-drawer-backdrop" data-drawer-close></div>
  <div class="pjd-doc-drawer-panel">
    <div class="pjd-doc-drawer-head">
      <div class="pjd-doc-drawer-file" id="pjdDrawerFile">documento.pdf</div>
      <button type="button" class="pjd-doc-drawer-toolbtn is-active" id="pjdDrawerTranscript">Transcripción</button>
      <a href="#" target="_blank" class="pjd-doc-drawer-toolbtn" id="pjdDrawerOpen">Abrir</a>
      <button type="button" class="pjd-doc-drawer-close" data-drawer-close aria-label="Cerrar">✕</button>
    </div>
    <div class="pjd-doc-drawer-nav">
      <button type="button" id="pjdPdfPrev" title="Anterior">‹</button>
      <span class="pjd-doc-drawer-pageind" id="pjdPdfPageInd">1 / 1</span>
      <button type="button" id="pjdPdfNext" title="Siguiente">›</button>
    </div>
    <div class="pjd-pdf-scroll" id="pjdPdfScroll">
      <div class="pjd-pdf-container" id="pjdPdfContainer">
        <canvas id="pjdPdfCanvas"></canvas>
        <div class="pjd-pdf-highlights" id="pjdPdfHighlights"></div>
      </div>
      <div class="pjd-pdf-loading" id="pjdPdfLoading" style="display:none;">Cargando documento…</div>
    </div>
    <div class="pjd-doc-drawer-quote" id="pjdDrawerQuote">
      <div class="pjd-doc-drawer-quote-kicker">Transcripción de la cita</div>
      <div class="pjd-doc-drawer-quote-text" id="pjdDrawerQuoteText">—</div>
      <div class="pjd-doc-drawer-quote-meta" id="pjdDrawerQuoteMeta"></div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
{{-- SheetJS para Excel real (.xlsx) --}}
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
{{-- PDF.js para renderizar el PDF y resaltar la cita --}}
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
@php
  $pjdDocsList = $project->documents->map(fn($d) => [
    'filename' => $d->filename,
    'stored'   => basename($d->file_path),
    'url'      => \Illuminate\Support\Facades\Storage::disk('public')->url($d->file_path),
  ])->values();
@endphp
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
  const PROJECT_DOCS_LIST = @json($pjdDocsList);

  function escapeHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  // ============ MARKDOWN (chat) ============
  function renderMarkdown(text) {
    if (!text) return '';
    let s = escapeHtml(text.trim());
    s = s.replace(/`([^`]+)`/g, '<code class="pjd-md-code">$1</code>');
    s = s.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    s = s.replace(/__([^_]+)__/g, '<strong>$1</strong>');
    s = s.replace(/(^|[^*])\*([^*\n]+)\*(?!\*)/g, '$1<em>$2</em>');

    const lines = s.split('\n');
    let html = '';
    let listType = null;
    const closeList = () => { if (listType) { html += `</${listType}>`; listType = null; } };

    for (let raw of lines) {
      const t = raw.trim();
      if (t === '') { closeList(); continue; }
      let m;
      if ((m = t.match(/^###\s+(.*)$/))) { closeList(); html += `<h4 class="pjd-md-h">${m[1]}</h4>`; continue; }
      if ((m = t.match(/^##\s+(.*)$/)))  { closeList(); html += `<h3 class="pjd-md-h">${m[1]}</h3>`; continue; }
      if ((m = t.match(/^#\s+(.*)$/)))   { closeList(); html += `<h3 class="pjd-md-h">${m[1]}</h3>`; continue; }
      if ((m = t.match(/^[-•*]\s+(.*)$/))) {
        if (listType !== 'ul') { closeList(); html += '<ul class="pjd-md-ul">'; listType = 'ul'; }
        html += `<li>${m[1]}</li>`; continue;
      }
      if ((m = t.match(/^\d+[.)]\s+(.*)$/))) {
        if (listType !== 'ol') { closeList(); html += '<ol class="pjd-md-ol">'; listType = 'ol'; }
        html += `<li>${m[1]}</li>`; continue;
      }
      closeList();
      html += `<p class="pjd-md-p">${t}</p>`;
    }
    closeList();
    return html;
  }

  // ============ TOAST ============
  function showToast(msg, type) {
    const t = document.createElement('div');
    t.className = 'pjd-toast' + (type === 'success' ? ' is-success' : type === 'error' ? ' is-error' : '');
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; }, 2200);
    setTimeout(() => t.remove(), 2600);
  }

  // ============ TABS ============
  const tabs = document.querySelectorAll('.pjd-tab');
  const panes = document.querySelectorAll('.pjd-pane');
  function activateTab(name) {
    tabs.forEach(t => t.classList.toggle('is-active', t.dataset.tab === name));
    panes.forEach(p => p.classList.toggle('is-active', p.dataset.pane === name));
  }
  tabs.forEach(t => t.addEventListener('click', () => activateTab(t.dataset.tab)));
  activateTab('ficha');

  document.querySelectorAll('.js-card-toggle').forEach(head => {
    const card = head.closest('.pjd-card');
    head.setAttribute('role', 'button');
    head.setAttribute('tabindex', '0');
    head.setAttribute('aria-expanded', card?.classList.contains('is-open') ? 'true' : 'false');
    const toggle = () => {
      card?.classList.toggle('is-open');
      head.setAttribute('aria-expanded', card?.classList.contains('is-open') ? 'true' : 'false');
    };
    head.addEventListener('click', toggle);
    head.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
    });
  });

  // ============ CHAT ============
  const chatForm = document.getElementById('pjdChatForm');
  const chatInput = document.getElementById('pjdChatInput');
  const chatSend = document.getElementById('pjdChatSend');
  const chatList = document.getElementById('pjdChatList');
  const chatReset = document.getElementById('pjdChatReset');

  function scrollChatToBottom() { chatList.scrollTop = chatList.scrollHeight; }
  scrollChatToBottom();

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
    return { headers, rows, before: lines.slice(0, start).join('\n').trim(), after: lines.slice(end + 1).join('\n').trim() };
  }

  function renderTableHtml(data) {
    const head = '<tr>' + data.headers.map(h => `<th>${escapeHtml(h)}</th>`).join('') + '</tr>';
    const body = data.rows.map(r => '<tr>' + r.map(c => `<td>${escapeHtml(c).replace(/\n/g,'<br>')}</td>`).join('') + '</tr>').join('');
    return `<table class="pjd-chat-table"><thead>${head}</thead><tbody>${body}</tbody></table>`;
  }

  function buildTableHtmlInline(data) {
    let html = '<table style="width:100%;border-collapse:collapse;margin:14px 0;border:1px solid #e5e7eb;font-family:Quicksand,Arial,sans-serif">';
    html += '<thead><tr>';
    data.headers.forEach(h => { html += `<th style="background:#f3f4f6;color:#111;padding:14px 16px;border:1px solid #e5e7eb;text-align:left;font-weight:700;font-size:14px">${escapeHtml(h)}</th>`; });
    html += '</tr></thead><tbody>';
    data.rows.forEach(r => { html += '<tr>'; r.forEach(c => { html += `<td style="padding:14px 16px;border:1px solid #e5e7eb;vertical-align:top;color:#333;line-height:1.55;font-size:14px">${escapeHtml(c).replace(/\n/g, '<br>')}</td>`; }); html += '</tr>'; });
    html += '</tbody></table>';
    return html;
  }

  async function copyTableToClipboard(data) {
    const tsv = [data.headers.join('\t'), ...data.rows.map(r => r.join('\t'))].join('\n');
    const html = buildTableHtmlInline(data);
    try {
      if (typeof ClipboardItem !== 'undefined' && navigator.clipboard?.write) {
        await navigator.clipboard.write([ new ClipboardItem({ 'text/html': new Blob([html],{type:'text/html'}), 'text/plain': new Blob([tsv],{type:'text/plain'}) }) ]);
        showToast('✓ Tabla copiada (pégala donde quieras)', 'success'); return;
      }
    } catch (_) {}
    try { await navigator.clipboard.writeText(tsv); showToast('✓ Tabla copiada como texto', 'success'); }
    catch (e) { const ta = document.createElement('textarea'); ta.value = tsv; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); showToast('✓ Tabla copiada', 'success'); }
  }

  function copyTableToBorrador(data) {
    const editor = document.getElementById('pjdDraftEditor');
    if (!editor) { showToast('No se encontró el borrador', 'error'); return; }
    editor.innerHTML += buildTableHtmlInline(data) + '<p><br></p>';
    document.querySelector('.pjd-tab[data-tab="borrador"]')?.click();
    document.querySelector('.pjd-borrador-tab[data-section="borrador"]')?.click();
    setTimeout(() => { document.getElementById('pjdSaveDraft')?.click(); }, 200);
    showToast('✓ Tabla agregada al borrador', 'success');
  }

  function downloadTableAsExcel(data) {
    if (typeof XLSX === 'undefined') { showToast('La librería de Excel no se cargó. Recarga la página.', 'error'); return; }
    const ws = XLSX.utils.aoa_to_sheet([data.headers, ...data.rows]);
    ws['!cols'] = data.headers.map((h, i) => { let max = (h||'').toString().length; data.rows.forEach(r => { const len = (r[i]||'').toString().length; if (len > max) max = len; }); return { wch: Math.min(Math.max(max + 2, 14), 70) }; });
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Tabla');
    const ts = new Date().toISOString().slice(0,19).replace(/[:T]/g,'-');
    XLSX.writeFile(wb, `tabla-${PROJECT_SLUG}-${ts}.xlsx`);
    showToast('✓ Excel descargado', 'success');
  }

  function appendMsg(role, content, time = '') {
    const wrap = document.createElement('div');
    wrap.className = `pjd-msg ${role === 'user' ? 'is-user' : 'is-assistant'}`;
    if (role === 'user') { wrap.innerHTML = `<div class="pjd-msg-body">${escapeHtml(content)}</div>`; chatList.appendChild(wrap); scrollChatToBottom(); return wrap; }

    const tableData = extractMarkdownTable(content);
    let bodyHtml;
    if (tableData) {
      const tableHtml = renderTableHtml(tableData);
      const textBefore = tableData.before ? `<div class="pjd-msg-body" style="margin-bottom:8px">${renderMarkdown(tableData.before)}</div>` : '';
      const textAfter  = tableData.after  ? `<div class="pjd-msg-body" style="margin-top:8px">${renderMarkdown(tableData.after)}</div>` : '';
      bodyHtml = `${textBefore}<div class="pjd-chat-table-wrap"><div class="pjd-chat-table-actions"><button type="button" class="pjd-chat-table-btn js-copy-table">📋 Copiar tabla</button><button type="button" class="pjd-chat-table-btn js-copy-to-draft">📝 Pasar al borrador</button><button type="button" class="pjd-chat-table-btn is-primary js-download-excel">⬇ Descargar Excel</button></div>${tableHtml}</div>${textAfter}`;
    } else {
      bodyHtml = `<div class="pjd-msg-body">${renderMarkdown(content)}</div>`;
    }
    wrap.innerHTML = `<div class="pjd-msg-avatar">j</div><div style="flex:1;min-width:0;"><div class="pjd-msg-meta">jureto${time ? ' · ' + time : ''}</div>${bodyHtml}</div>`;
    chatList.appendChild(wrap);
    if (tableData) {
      wrap.querySelector('.js-copy-table')?.addEventListener('click', () => copyTableToClipboard(tableData));
      wrap.querySelector('.js-copy-to-draft')?.addEventListener('click', () => copyTableToBorrador(tableData));
      wrap.querySelector('.js-download-excel')?.addEventListener('click', () => downloadTableAsExcel(tableData));
    }
    scrollChatToBottom();
    return wrap;
  }

  document.querySelectorAll('.pjd-msg.is-assistant .pjd-msg-body[data-raw]').forEach(el => {
    const raw = el.getAttribute('data-raw') || '';
    const tableData = extractMarkdownTable(raw);
    if (!tableData) { el.innerHTML = renderMarkdown(raw); return; }
    const container = el.parentElement;
    const tableHtml = renderTableHtml(tableData);
    const textBefore = tableData.before ? `<div class="pjd-msg-body" style="margin-bottom:8px">${renderMarkdown(tableData.before)}</div>` : '';
    const textAfter  = tableData.after  ? `<div class="pjd-msg-body" style="margin-top:8px">${renderMarkdown(tableData.after)}</div>` : '';
    el.outerHTML = `${textBefore}<div class="pjd-chat-table-wrap"><div class="pjd-chat-table-actions"><button type="button" class="pjd-chat-table-btn js-copy-table">📋 Copiar tabla</button><button type="button" class="pjd-chat-table-btn js-copy-to-draft">📝 Pasar al borrador</button><button type="button" class="pjd-chat-table-btn is-primary js-download-excel">⬇ Descargar Excel</button></div>${tableHtml}</div>${textAfter}`;
    container.querySelector('.js-copy-table')?.addEventListener('click', () => copyTableToClipboard(tableData));
    container.querySelector('.js-copy-to-draft')?.addEventListener('click', () => copyTableToBorrador(tableData));
    container.querySelector('.js-download-excel')?.addEventListener('click', () => downloadTableAsExcel(tableData));
  });

  function appendLoading() {
    const wrap = document.createElement('div');
    wrap.className = 'pjd-msg is-assistant'; wrap.id = 'pjdLoadingMsg';
    wrap.innerHTML = `<div class="pjd-msg-avatar">j</div><div><div class="pjd-msg-meta">jureto</div><div class="pjd-msg-body"><span class="pjd-loading-dots"><span></span><span></span><span></span></span></div></div>`;
    chatList.appendChild(wrap); scrollChatToBottom();
  }

  chatForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = chatInput.value.trim(); if (!msg) return;
    chatInput.value = ''; chatSend.disabled = true;
    appendMsg('user', msg); appendLoading();
    try {
      const fd = new FormData(); fd.append('_token', CSRF); fd.append('message', msg);
      const res = await fetch(CHAT_URL, { method:'POST', headers:{'Accept':'application/json'}, body: fd, credentials:'same-origin' });
      const json = await res.json();
      document.getElementById('pjdLoadingMsg')?.remove();
      if (json.ok && json.assistant_message) appendMsg('assistant', json.assistant_message.content, json.assistant_message.time);
      else appendMsg('assistant', json.message || 'Hubo un error.');
    } catch (err) { document.getElementById('pjdLoadingMsg')?.remove(); appendMsg('assistant', 'Error de red.'); }
    finally { chatSend.disabled = false; chatInput.focus(); }
  });

  chatReset?.addEventListener('click', async () => {
    if (!confirm('¿Borrar todo el historial?')) return;
    try {
      await fetch(CHAT_RESET_URL, { method:'DELETE', headers:{'X-CSRF-TOKEN': CSRF, 'Accept':'application/json'} });
      chatList.innerHTML = '';
      appendMsg('assistant', 'Hola, soy tu asistente del proyecto. ¿En qué puedo ayudarte? Puedo resumir las bases, listar requisitos o aclararte cualquier punto de la licitación.');
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
      if (c === 'Cumple') counts.cumple++; else if (c === 'Parcial') counts.parcial++; else if (c === 'No Cumple') counts.no_cumple++; else counts.sin_revisar++;
      if (s === 'En revisión') counts.revision++; else if (s === 'Aprobado') counts.aprobado++; else counts.pendiente++;
    });
    document.getElementById('pjdClTotalNum').textContent = total;
    Object.keys(counts).forEach(k => {
      const numEl = document.querySelector(`[data-counter="${k}"]`);
      const pctEl = document.querySelector(`[data-pct="${k}"]`);
      const barEl = document.querySelector(`[data-bar="${k}"]`);
      const pct = total > 0 ? Math.round((counts[k]/total)*100) : 0;
      if (numEl) numEl.textContent = counts[k];
      if (pctEl) pctEl.textContent = pct + '%';
      if (barEl) barEl.style.width = pct + '%';
    });
  }
  updateCounters();

  function toggleChecklistDetail(idx, forceOpen = null) {
    const tr = clBody.querySelector(`tr[data-row="${idx}"]`);
    const detail = clBody.querySelector(`tr[data-detail="${idx}"]`);
    if (!tr || !detail) return;
    const shouldOpen = forceOpen === null ? !tr.classList.contains('is-expanded') : !!forceOpen;
    tr.classList.toggle('is-expanded', shouldOpen);
    detail.style.display = shouldOpen ? '' : 'none';
  }

  clBody?.addEventListener('click', (e) => {
    const tBtn = e.target.closest('[data-toggle]');
    if (tBtn) { toggleChecklistDetail(tBtn.dataset.toggle); e.stopPropagation(); return; }

    const sourceBtn = e.target.closest('.pjd-cl-source-btn[data-cita]');
    if (sourceBtn) { openCita(sourceBtn.getAttribute('data-cita')); e.stopPropagation(); return; }

    const sourceLink = e.target.closest('a.pjd-cl-source-btn');
    if (sourceLink) { e.stopPropagation(); return; }

    const row = e.target.closest('tr[data-row]');
    const clickedControl = e.target.closest('[data-cumplimiento-toggle], [data-status-toggle]');
    if (row && !clickedControl) { toggleChecklistDetail(row.dataset.row); e.stopPropagation(); return; }

    const cumpBtn = e.target.closest('[data-cumplimiento-toggle]');
    if (cumpBtn) {
      activeCumpRow = cumpBtn.dataset.cumplimientoToggle;
      const rect = cumpBtn.getBoundingClientRect();
      cumpPop.style.top = (rect.bottom + window.scrollY + 6) + 'px';
      cumpPop.style.left = rect.left + 'px';
      cumpPop.classList.add('is-open'); statPop.classList.remove('is-open');
      e.stopPropagation(); return;
    }
    const statBtn = e.target.closest('[data-status-toggle]');
    if (statBtn) {
      activeStatusRow = statBtn.dataset.statusToggle;
      const rect = statBtn.getBoundingClientRect();
      statPop.style.top = (rect.bottom + window.scrollY + 6) + 'px';
      statPop.style.left = rect.left + 'px';
      statPop.classList.add('is-open'); cumpPop.classList.remove('is-open');
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
    if (val === 'Cumple') dot.classList.add('is-cumple'); else if (val === 'Parcial') dot.classList.add('is-parcial'); else if (val === 'No Cumple') dot.classList.add('is-nocumple');
    cumpPop.classList.remove('is-open'); updateCounters(); saveChecklist();
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
    pill.classList.add(cls[val]); pill.textContent = (icons[val] || '🕐') + ' ' + val;
    statPop.classList.remove('is-open'); updateCounters(); saveChecklist();
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
      if (detail && !match) { detail.style.display = 'none'; r.classList.remove('is-expanded'); }
    });
  });

  async function saveChecklist() {
    const rows = Array.from(clBody.querySelectorAll('tr[data-row]')).map(r => ({ idx: r.dataset.row, cumplimiento: r.dataset.cumplimiento, status: r.dataset.status, prioridad: r.dataset.prioridad }));
    try { const fd = new FormData(); fd.append('_token', CSRF); fd.append('items', JSON.stringify(rows)); await fetch(CHECKLIST_URL, { method:'POST', headers:{'Accept':'application/json'}, body: fd, credentials:'same-origin' }); } catch (_) {}
  }

  document.getElementById('pjdClReanalisis')?.addEventListener('click', async () => {
    if (!confirm('Esto regenerará TODO el checklist con IA. ¿Continuar?')) return;
    const btn = document.getElementById('pjdClReanalisis');
    const original = btn.innerHTML; btn.disabled = true; btn.innerHTML = '⏳ Generando…';
    try { const fd = new FormData(); fd.append('_token', CSRF); fd.append('regenerate', '1'); const res = await fetch(CHECKLIST_URL, { method:'POST', headers:{'Accept':'application/json'}, body: fd, credentials:'same-origin' }); if (res.ok) location.reload(); else alert('Error al regenerar'); }
    catch (e) { alert('Error de red'); } finally { btn.disabled = false; btn.innerHTML = original; }
  });

  document.getElementById('pjdClAddBtn')?.addEventListener('click', () => { const name = prompt('Nombre del requisito:'); if (!name) return; location.reload(); });

  document.getElementById('pjdClDownload')?.addEventListener('click', () => {
    if (typeof XLSX === 'undefined') { showToast('Excel no disponible', 'error'); return; }
    const headers = ['Requisito','Formato','Categoría','Aplicabilidad','Obligatorio','Cumplimiento','Status','Prioridad'];
    const rows = [];
    clBody.querySelectorAll('tr[data-row]').forEach(r => {
      const cells = r.querySelectorAll('td');
      rows.push([ cells[1]?.textContent.trim() || '', cells[2]?.textContent.trim() || '', cells[3]?.textContent.trim() || '', cells[4]?.textContent.trim() || '', cells[5]?.textContent.trim() || '', r.dataset.cumplimiento || '-', r.dataset.status || 'Pendiente', r.dataset.prioridad || 'Media' ]);
    });
    const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
    ws['!cols'] = headers.map((h, i) => { let max = h.length; rows.forEach(r => { const len = (r[i]||'').toString().length; if (len > max) max = len; }); return { wch: Math.min(Math.max(max + 2, 14), 70) }; });
    const wb = XLSX.utils.book_new(); XLSX.utils.book_append_sheet(wb, ws, 'Checklist');
    XLSX.writeFile(wb, `checklist-${PROJECT_SLUG}.xlsx`); showToast('✓ Checklist descargado', 'success');
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
    try { const res = await fetch(DRAFT_URL, { method:'POST', headers:{'Accept':'application/json'}, body: fd, credentials:'same-origin' }); if (res.ok) draftStatus.textContent = '✓ Guardado ' + new Date().toLocaleTimeString(); else draftStatus.textContent = 'Error'; }
    catch (e) { draftStatus.textContent = 'Error de red'; } finally { saveBtn.disabled = false; saveBtn.textContent = '💾 Guardar'; }
  });

  document.getElementById('pjdDownloadDraft')?.addEventListener('click', () => {
    const html = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>${PROJECT_NAME}</title><style>body{font-family:Quicksand,Arial,sans-serif;max-width:850px;margin:30px auto;padding:20px;line-height:1.6;}table{border-collapse:collapse;width:100%;margin:14px 0}th,td{border:1px solid #e5e7eb;padding:10px 12px;text-align:left}th{background:#f3f4f6;font-weight:700}</style></head><body>${draftEditor.innerHTML}</body></html>`;
    const blob = new Blob([html], { type: 'text/html;charset=utf-8;' });
    const url = URL.createObjectURL(blob); const a = document.createElement('a'); a.href = url; a.download = `borrador-${PROJECT_SLUG}.html`; a.click(); URL.revokeObjectURL(url);
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
      if (json.ok && json.html) { if (empty) empty.style.display = 'none'; content.innerHTML = json.html; content.style.display = ''; if (actions) actions.style.display = ''; }
      else { alert(json.message || 'Error al generar reporte'); }
    } catch (e) { alert('Error de red'); }
    finally { if (btn) { btn.disabled = false; btn.innerHTML = btn.id === 'pjdReporteGen' ? '✨ Generar Reporte' : '✨ Regenerar'; } }
  }
  document.getElementById('pjdReporteGen')?.addEventListener('click', generateReport);
  document.getElementById('pjdReporteRegen')?.addEventListener('click', () => { if (confirm('¿Regenerar el reporte?')) generateReport(); });

  document.getElementById('pjdReporteDownload')?.addEventListener('click', () => {
    const content = document.getElementById('pjdReporteContent').innerHTML;
    const html = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>Reporte - ${PROJECT_NAME}</title><style>body{font-family:Quicksand,Arial,sans-serif;max-width:850px;margin:30px auto;padding:30px;line-height:1.7;}table{border-collapse:collapse;width:100%;margin:14px 0}th,td{border:1px solid #ebebeb;padding:8px 12px;text-align:left}th{background:#fafbff}h1,h2,h3{color:#111}</style></head><body>${content}</body></html>`;
    const blob = new Blob([html], { type: 'text/html;charset=utf-8;' });
    const url = URL.createObjectURL(blob); const a = document.createElement('a'); a.href = url; a.download = `reporte-${PROJECT_SLUG}.html`; a.click(); URL.revokeObjectURL(url);
  });

  @if(!empty($project->report_content ?? null))
    document.getElementById('pjdReporteActions').style.display = '';
  @endif

  // ============ CITAS (drawer lateral con PDF.js + resaltado exacto) ============
  const docDrawer    = document.getElementById('pjdDocDrawer');
  const drawerFile   = document.getElementById('pjdDrawerFile');
  const drawerOpen   = document.getElementById('pjdDrawerOpen');
  const drawerQuote  = document.getElementById('pjdDrawerQuote');
  const drawerQText  = document.getElementById('pjdDrawerQuoteText');
  const drawerQMeta  = document.getElementById('pjdDrawerQuoteMeta');
  const drawerTransBtn = document.getElementById('pjdDrawerTranscript');
  const pdfScroll    = document.getElementById('pjdPdfScroll');
  const pdfContainer = document.getElementById('pjdPdfContainer');
  const pdfCanvas    = document.getElementById('pjdPdfCanvas');
  const pdfHl        = document.getElementById('pjdPdfHighlights');
  const pdfLoading   = document.getElementById('pjdPdfLoading');
  const pdfPageInd   = document.getElementById('pjdPdfPageInd');

  if (window.pdfjsLib) {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';
  }

  const DOC_LOOKUP = {};
  (PROJECT_DOCS_LIST || []).forEach(d => {
    if (d.filename) DOC_LOOKUP[d.filename.toLowerCase()] = d;
    if (d.stored)   DOC_LOOKUP[d.stored.toLowerCase()]   = d;
  });
  function resolveDoc(name) {
    if (!name) return null;
    const n = String(name).toLowerCase();
    if (DOC_LOOKUP[n]) return DOC_LOOKUP[n];
    const base = n.split('/').pop().split('\\').pop();
    if (DOC_LOOKUP[base]) return DOC_LOOKUP[base];
    return (PROJECT_DOCS_LIST || []).find(d => (d.filename && d.filename.toLowerCase().includes(base)) || (d.stored && base.includes(d.stored.toLowerCase()))) || null;
  }

  function normPdf(s){
    return (s||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,' ').replace(/\s+/g,' ').trim();
  }

  // Busca el rango (inicio,fin) de la cita dentro del texto concatenado de la pagina.
  function findQuoteSpan(concat, quote) {
    if (!concat || !quote || quote.length < 4) return null;
    let s = concat.indexOf(quote);
    if (s !== -1) return [s, s + quote.length];

    const words = quote.split(' ').filter(Boolean);
    if (words.length < 2) return null;

    // recorta desde el final
    for (let n = words.length - 1; n >= Math.min(3, words.length); n--) {
      const sub = words.slice(0, n).join(' ');
      s = concat.indexOf(sub);
      if (s !== -1) return [s, s + sub.length];
    }
    // recorta desde el inicio
    for (let i = 1; i <= words.length - 3; i++) {
      const sub = words.slice(i).join(' ');
      s = concat.indexOf(sub);
      if (s !== -1) return [s, s + sub.length];
    }
    // bloque contiguo mas largo presente en la pagina
    let best = null;
    for (let i = 0; i < words.length - 1; i++) {
      for (let j = words.length; j > i + 1; j--) {
        const sub = words.slice(i, j).join(' ');
        const pos = concat.indexOf(sub);
        if (pos !== -1) {
          if (!best || sub.length > best.len) best = { start: pos, end: pos + sub.length, len: sub.length };
          break;
        }
      }
    }
    return best ? [best.start, best.end] : null;
  }

  // Concatena el texto de la pagina y mapea cada caracter al item que lo origina.
  function buildPageIndex(tc) {
    const items = [];
    let concat = '';
    const map = [];
    tc.items.forEach(it => {
      const norm = normPdf(it.str);
      if (!norm) return;
      const idx = items.length;
      items.push(it);
      if (concat.length) { concat += ' '; map.push(-1); }
      for (let k = 0; k < norm.length; k++) map.push(idx);
      concat += norm;
    });
    return { items, concat, map };
  }

  // Conjunto de palabras significativas de la cita (ignora conectores cortos).
  function quoteWordSet(quote) {
    const STOP = new Set(['para','como','esta','este','esto','estos','estas','sobre','entre','cuando','donde','todo','toda','todos','todas','cada','mas','segun','sera','seran','desde','hasta','pero','solo','tambien','dicha','dicho','dichos','dichas','sino','aquel','ello','ella','ellos','unos','unas','una','del','las','los','con','por','que']);
    return new Set(normPdf(quote).split(' ').filter(w => w.length >= 4 && !STOP.has(w)));
  }

  // Encuentra el grupo de items mas denso en palabras de la cita (para citas parafraseadas).
  function matchingCluster(items, quoteSet) {
    if (!quoteSet || !quoteSet.size) return { idxs: [], score: 0 };
    const matched = [];
    items.forEach((it, idx) => {
      const ws = normPdf(it.str).split(' ').filter(Boolean);
      let c = 0; ws.forEach(w => { if (quoteSet.has(w)) c++; });
      if (c > 0) matched.push({ idx, c });
    });
    if (!matched.length) return { idxs: [], score: 0 };

    const groupScore = g => g.reduce((s, m) => s + m.c, 0);
    let best = [], cur = [matched[0]];
    for (let k = 1; k < matched.length; k++) {
      if (matched[k].idx - cur[cur.length - 1].idx <= 6) cur.push(matched[k]);
      else { if (groupScore(cur) > groupScore(best)) best = cur; cur = [matched[k]]; }
    }
    if (groupScore(cur) > groupScore(best)) best = cur;
    return { idxs: best.map(m => m.idx), score: groupScore(best) };
  }

  // Pinta el resaltado de la cita sobre la pagina renderizada. Devuelve el primer rect (para scroll).
  async function paintHighlights(page, viewport, scale) {
    pdfHl.innerHTML = '';
    const quote = normPdf(PDF_STATE.quote);
    if (!quote || quote.length < 4) return null;

    const tc = await page.getTextContent();
    const { items, concat, map } = buildPageIndex(tc);

    // 1) match exacto / contiguo
    let idxs = [];
    const span = findQuoteSpan(concat, quote);
    if (span) {
      const set = new Set();
      for (let i = span[0]; i < span[1] && i < map.length; i++) if (map[i] >= 0) set.add(map[i]);
      idxs = [...set];
    }

    // 2) fallback por palabras clave si el match exacto fue pobre (cita parafraseada)
    if (idxs.length < 3) {
      const cluster = matchingCluster(items, quoteWordSet(PDF_STATE.quote));
      if (cluster.idxs.length > idxs.length) idxs = cluster.idxs;
    }
    if (!idxs.length) return null;

    let first = null, firstTop = Infinity;
    idxs.forEach(idx => {
      const it = items[idx];
      const t = pdfjsLib.Util.transform(viewport.transform, it.transform);
      const fh = Math.hypot(t[2], t[3]);
      const top = t[5] - fh;
      const div = document.createElement('div');
      div.className = 'pjd-pdf-hl';
      div.style.left   = t[4] + 'px';
      div.style.top    = top + 'px';
      div.style.width  = ((it.width || 0) * scale) + 'px';
      div.style.height = (fh * 1.2) + 'px';
      pdfHl.appendChild(div);
      if (top < firstTop) { firstTop = top; first = div; }
    });
    return first;
  }

  // Elige la pagina con la cita: prioriza match exacto, si no, la de mayor coincidencia de palabras.
  async function findQuotePage(quote, preferida) {
    const Q = normPdf(quote);
    if (!Q || Q.length < 4 || !PDF_STATE.doc) return preferida;
    const qset = quoteWordSet(quote);
    const minScore = Math.max(3, Math.ceil(qset.size * 0.4));

    const scorePage = async (p) => {
      try {
        const page = await PDF_STATE.doc.getPage(p);
        const tc = await page.getTextContent();
        const { items, concat } = buildPageIndex(tc);
        if (findQuoteSpan(concat, Q)) return 1e6; // el match exacto siempre gana
        return matchingCluster(items, qset).score;
      } catch (e) { return 0; }
    };

    const prefScore = await scorePage(preferida);
    if (prefScore >= minScore) return preferida;

    let bestP = preferida, bestS = prefScore;
    for (let p = 1; p <= PDF_STATE.total; p++) {
      if (p === preferida) continue;
      const sc = await scorePage(p);
      if (sc > bestS) { bestS = sc; bestP = p; }
    }
    return bestS >= minScore ? bestP : preferida;
  }

  const PDF_STATE = { doc:null, url:null, page:1, total:1, quote:'', citaPage:1 };

  async function renderPdfPage() {
    if (!PDF_STATE.doc || !window.pdfjsLib) return;
    const num = Math.min(Math.max(1, PDF_STATE.page), PDF_STATE.total);
    PDF_STATE.page = num;
    const page = await PDF_STATE.doc.getPage(num);
    const cw = (pdfScroll.clientWidth || 760) - 34;
    const base = page.getViewport({ scale: 1 });
    const scale = Math.max(0.6, Math.min(2.4, cw / base.width));
    const viewport = page.getViewport({ scale });

    const ctx = pdfCanvas.getContext('2d');
    pdfCanvas.width = viewport.width; pdfCanvas.height = viewport.height;
    pdfCanvas.style.width = viewport.width + 'px'; pdfCanvas.style.height = viewport.height + 'px';
    pdfHl.style.width = viewport.width + 'px'; pdfHl.style.height = viewport.height + 'px';
    pdfHl.innerHTML = '';

    await page.render({ canvasContext: ctx, viewport }).promise;

    if (num === PDF_STATE.citaPage && PDF_STATE.quote) {
      const first = await paintHighlights(page, viewport, scale);
      if (first) setTimeout(() => first.scrollIntoView({ block:'center', behavior:'smooth' }), 120);
    }

    pdfPageInd.textContent = num + ' / ' + PDF_STATE.total;
  }

  async function openCita(payload) {
    if (!payload) return;
    let data;
    try { data = typeof payload === 'string' ? JSON.parse(payload) : payload; } catch (e) { return; }

    const doc = resolveDoc(data.fuente);
    const pageNum = data.pagina ? parseInt(String(data.pagina).match(/\d+/)?.[0] || '1', 10) : 1;

    drawerFile.textContent = doc ? doc.filename : (data.fuente || 'Documento');
    drawerQText.textContent = data.cita || 'No hay transcripción textual guardada para esta cita.';
    drawerQMeta.innerHTML = (doc ? `<strong>Fuente:</strong> ${escapeHtml(doc.filename)}` : (data.fuente ? `<strong>Fuente:</strong> ${escapeHtml(data.fuente)}` : '')) + (data.pagina ? ` &middot; Página ${pageNum}` : '');

    docDrawer.classList.add('is-open');
    docDrawer.setAttribute('aria-hidden','false');
    document.body.style.overflow = 'hidden';

    if (doc) { drawerOpen.href = doc.url; drawerOpen.style.display = ''; } else { drawerOpen.style.display = 'none'; }

    if (!doc || !window.pdfjsLib) {
      pdfContainer.style.display = 'none';
      pdfLoading.style.display = '';
      pdfLoading.textContent = doc ? 'El visor PDF no se cargó. Usa el botón Abrir.' : 'No se encontró el archivo fuente.';
      return;
    }

    pdfContainer.style.display = '';
    pdfLoading.style.display = '';
    pdfLoading.textContent = 'Cargando documento…';

    try {
      if (PDF_STATE.url !== doc.url) {
        const task = pdfjsLib.getDocument(doc.url);
        PDF_STATE.doc = await task.promise;
        PDF_STATE.url = doc.url;
        PDF_STATE.total = PDF_STATE.doc.numPages;
      }
      PDF_STATE.quote = data.cita || '';
      const preferida = Math.min(Math.max(1, pageNum), PDF_STATE.total);
      const target = await findQuotePage(PDF_STATE.quote, preferida);
      PDF_STATE.citaPage = target;
      PDF_STATE.page = target;
      pdfLoading.style.display = 'none';
      await renderPdfPage();
    } catch (err) {
      pdfContainer.style.display = 'none';
      pdfLoading.style.display = '';
      pdfLoading.textContent = 'No se pudo abrir el documento. Usa el botón Abrir.';
    }
  }

  function closeCita() {
    docDrawer.classList.remove('is-open');
    docDrawer.setAttribute('aria-hidden','true');
    document.body.style.overflow = '';
  }

  document.getElementById('pjdPdfPrev')?.addEventListener('click', () => { if (PDF_STATE.page > 1) { PDF_STATE.page--; renderPdfPage(); } });
  document.getElementById('pjdPdfNext')?.addEventListener('click', () => { if (PDF_STATE.page < PDF_STATE.total) { PDF_STATE.page++; renderPdfPage(); } });
  drawerTransBtn?.addEventListener('click', () => {
    const hidden = drawerQuote.hasAttribute('hidden');
    if (hidden) { drawerQuote.removeAttribute('hidden'); drawerTransBtn.classList.add('is-active'); }
    else { drawerQuote.setAttribute('hidden',''); drawerTransBtn.classList.remove('is-active'); }
  });
  document.querySelectorAll('[data-drawer-close]').forEach(el => el.addEventListener('click', closeCita));

  document.addEventListener('click', (e) => {
    const openBtn = e.target.closest('[data-cita]');
    if (openBtn && e.target.closest('.js-open-cita, .pjd-source-btn, .pjd-cl-source-btn')) {
      e.preventDefault(); e.stopPropagation();
      openCita(openBtn.getAttribute('data-cita'));
      return;
    }

    const closeBtn = e.target.closest('.pjd-source-close');
    if (closeBtn) {
      e.preventDefault(); e.stopPropagation();
      const item = closeBtn.closest('.pjd-field, .pjd-qa');
      item?.classList.remove('is-source-open');
      const panel = item?.querySelector('.pjd-source-panel'); if (panel) panel.hidden = true;
      return;
    }

    if (e.target.closest('.pjd-source-btn, .pjd-cl-source-btn')) return;

    const sourceItem = e.target.closest('.pjd-field, .pjd-qa');
    if (sourceItem && !e.target.closest('.js-card-toggle')) {
      const panel = sourceItem.querySelector('.pjd-source-panel'); if (!panel) return;
      const willOpen = !sourceItem.classList.contains('is-source-open');
      sourceItem.closest('.pjd-card-body')?.querySelectorAll('.pjd-field.is-source-open, .pjd-qa.is-source-open').forEach(oi => {
        if (oi !== sourceItem) { oi.classList.remove('is-source-open'); const op = oi.querySelector('.pjd-source-panel'); if (op) op.hidden = true; }
      });
      sourceItem.classList.toggle('is-source-open', willOpen);
      panel.hidden = !willOpen;
      return;
    }
  });

  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeCita(); });

  let pdfResizeTimer = null;
  window.addEventListener('resize', () => {
    if (!docDrawer.classList.contains('is-open') || !PDF_STATE.doc) return;
    clearTimeout(pdfResizeTimer);
    pdfResizeTimer = setTimeout(() => renderPdfPage(), 200);
  });

})();
</script>
@endpush