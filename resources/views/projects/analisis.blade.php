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

  /* ── Layout 2 columnas redimensionable ── */
  .pjd-wrap { --pjd-left-width: 44%; }

  .pjd-body {
    flex: 1;
    display: grid;
    grid-template-columns: minmax(300px, var(--pjd-left-width)) 10px minmax(360px, 1fr);
    gap: 0;
    min-height: 0;
    height: calc(100vh - 57px);
    overflow: hidden;
  }

  .pjd-left {
    display: flex;
    flex-direction: column;
    background: #fff;
    min-width: 0;
    min-height: 0;
    height: 100%;
    overflow: hidden;
  }

  .pjd-resizer {
    position: relative;
    width: 10px;
    height: 100%;
    background: #fff;
    border-left: 1px solid var(--line);
    border-right: 1px solid var(--line);
    cursor: col-resize;
    z-index: 30;
    transition: background .18s ease, border-color .18s ease;
    touch-action: none;
  }

  .pjd-resizer::before {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 18px;
    height: 42px;
    transform: translate(-50%, -50%);
    border: 1px solid var(--line);
    border-radius: 999px;
    background: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,.08);
  }

  .pjd-resizer::after {
    content: "⋮";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: var(--muted);
    font-size: 16px;
    line-height: 1;
    font-weight: 700;
  }

  .pjd-resizer:hover,
  .pjd-resizer:focus-visible,
  body.is-pjd-resizing .pjd-resizer {
    background: var(--blue-soft);
    border-color: #c7dcfd;
    outline: none;
  }

  .pjd-resizer:hover::before,
  .pjd-resizer:focus-visible::before,
  body.is-pjd-resizing .pjd-resizer::before {
    border-color: #c7dcfd;
    box-shadow: 0 8px 22px rgba(0,122,255,.16);
  }

  body.is-pjd-resizing {
    cursor: col-resize;
    user-select: none;
  }

  .pjd-right {
    display: flex;
    flex-direction: column;
    background: var(--bg);
    min-width: 0;
    min-height: 0;
    height: 100%;
    overflow: auto;
  }

  @media (max-width: 1100px) {
    .pjd-body {
      height: auto;
      overflow: visible;
      grid-template-columns: 1fr;
    }
    .pjd-left { height: auto; min-height: 520px; }
    .pjd-resizer { display: none; }
  }

  /* ── CHAT ── */
  .pjd-chat-head { padding: 10px 18px; border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: flex-end; }
  .pjd-chat-reset { background: var(--bg); border: 1px solid var(--line); padding: 5px 12px; border-radius: 999px; font-size: .8rem; font-weight: 600; color: var(--ink2); cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: all .18s; }
  .pjd-chat-reset:hover { background: var(--blue-soft); color: var(--blue); border-color: var(--blue); }
  .pjd-chat-list { flex: 1; min-height: 0; padding: 18px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; scroll-behavior: smooth; overscroll-behavior: contain; }
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
  .pjd-checklist-wrap {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 22px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
  }
  .pjd-checklist-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 14px;
    flex-wrap: wrap;
  }
  .pjd-checklist-title-block {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
  }
  .pjd-checklist-title {
    margin: 0;
    font-size: 1.95rem;
    line-height: 1;
    font-weight: 700;
    color: #111111;
    letter-spacing: -.02em;
  }
  .pjd-checklist-title-actions {
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .pjd-checklist-icon {
    width: 34px;
    height: 34px;
    border: 1px solid transparent;
    background: transparent;
    color: #6b7280;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all .2s ease;
    flex-shrink: 0;
  }
  .pjd-checklist-icon:hover {
    background: #f9fafb;
    border-color: var(--line);
    color: #111111;
    transform: translateY(-1px);
  }
  .pjd-checklist-icon svg { width: 18px; height: 18px; }
  .pjd-checklist-links {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    margin-left: auto;
  }
  .pjd-checklist-link {
    color: var(--blue);
    font-weight: 700;
    font-size: .95rem;
    text-decoration: none;
    padding: 0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    background: transparent;
    border: none;
    transition: opacity .2s ease, transform .2s ease;
  }
  .pjd-checklist-link:hover {
    opacity: .85;
    transform: translateY(-1px);
  }
  .pjd-checklist-link svg { width: 18px; height: 18px; }

  .pjd-cl-summary {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
    margin-bottom: 18px;
    flex-wrap: wrap;
  }
  .pjd-counters {
    flex: 1 1 760px;
    display: grid;
    grid-template-columns: repeat(4, minmax(120px, 1fr));
    gap: 18px 28px;
    margin-bottom: 0;
  }
  @media (max-width: 1200px) {
    .pjd-counters { grid-template-columns: repeat(2, minmax(140px, 1fr)); }
  }
  @media (max-width: 640px) {
    .pjd-counters { grid-template-columns: 1fr; }
  }
  .pjd-counter {
    padding: 0;
    border: none;
    background: transparent;
    border-radius: 0;
  }
  .pjd-counter-top {
    display: flex;
    align-items: baseline;
    gap: 8px;
    font-size: .92rem;
    font-weight: 600;
    color: #556070;
    line-height: 1.15;
  }
  .pjd-counter-num {
    font-size: 1.55rem;
    font-weight: 700;
    color: #111111;
  }
  .pjd-counter-label {
    min-width: 0;
    color: #556070;
  }
  .pjd-counter-pct {
    margin-left: auto;
    font-size: .85rem;
    font-weight: 700;
    color: var(--blue);
    white-space: nowrap;
  }
  .pjd-counter-bar {
    height: 8px;
    background: #e5e7eb;
    border-radius: 999px;
    overflow: hidden;
    margin-top: 8px;
  }
  .pjd-counter-bar-fill { height: 100%; background: #9ca3af; border-radius: 999px; transition: width .3s; }
  .pjd-counter.is-pending .pjd-counter-bar-fill,
  .pjd-counter.is-parcial .pjd-counter-bar-fill { background: #f4b321; }
  .pjd-counter.is-cumple .pjd-counter-bar-fill,
  .pjd-counter.is-approved .pjd-counter-bar-fill { background: #22c55e; }
  .pjd-counter.is-nocumple .pjd-counter-bar-fill { background: #ff4a4a; }
  .pjd-counter.is-review .pjd-counter-bar-fill { background: #2563eb; }
  .pjd-counter.is-pending .pjd-counter-top { color: #c28708; }
  .pjd-counter.is-cumple .pjd-counter-top,
  .pjd-counter.is-approved .pjd-counter-top { color: #15803d; }
  .pjd-counter.is-parcial .pjd-counter-top { color: #c28708; }
  .pjd-counter.is-nocumple .pjd-counter-top { color: #ff4a4a; }
  .pjd-counter.is-review .pjd-counter-top { color: #2563eb; }
  .pjd-counter.is-total {
    align-self: center;
    justify-self: start;
    min-width: 112px;
    padding: 10px 14px;
    border-radius: 10px;
    background: #f7f7f8;
    border: 1px solid #f0f0f0;
  }
  .pjd-counter.is-total .pjd-counter-top {
    justify-content: center;
    gap: 8px;
    color: #4b5563;
  }
  .pjd-counter.is-total .pjd-counter-num {
    font-size: 1.35rem;
    color: #111111;
  }

  .pjd-cl-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 16px;
    flex-wrap: wrap;
  }
  .pjd-cl-search {
    flex: 1 1 420px;
    min-width: 280px;
    position: relative;
  }
  .pjd-cl-search input {
    width: 100%;
    height: 46px;
    padding: 0 16px 0 46px;
    border: 1px solid var(--line);
    border-radius: 12px;
    font-family: inherit;
    font-size: .95rem;
    outline: none;
    background: #fff;
    color: var(--ink);
  }
  .pjd-cl-search input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft); }
  .pjd-cl-search svg {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    stroke: #9ca3af;
  }
  .pjd-cl-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-left: auto;
  }
  .pjd-cl-btn {
    height: 46px;
    padding: 0 18px;
    border: 1px solid var(--line);
    background: #fff;
    border-radius: 10px;
    font-family: inherit;
    font-size: .95rem;
    font-weight: 700;
    color: #111111;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all .18s ease;
  }
  .pjd-cl-btn:hover {
    background: #f9fafb;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
  }
  .pjd-cl-btn:active { transform: scale(0.98); }
  .pjd-cl-btn.is-primary {
    background: var(--blue);
    color: #fff;
    border-color: var(--blue);
    box-shadow: 0 4px 12px rgba(0,122,255,.14);
  }
  .pjd-cl-btn.is-primary:hover {
    background: #0a84ff;
    box-shadow: 0 10px 20px rgba(0,122,255,.18);
  }
  .pjd-cl-btn svg { width: 18px; height: 18px; }

  .pjd-cl-table-wrap {
    overflow-x: auto;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: #fff;
  }
  .pjd-cl-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    min-width: 1120px;
    background: #fff;
  }
  .pjd-cl-table thead th {
    background: #fff;
    padding: 16px 14px;
    font-weight: 700;
    color: #4b5563;
    text-align: left;
    font-size: .92rem;
    border-bottom: 1px solid var(--line);
    white-space: nowrap;
    position: relative;
  }
  .pjd-cl-table thead th + th::before {
    content: '';
    position: absolute;
    left: 0;
    top: 18px;
    bottom: 18px;
    width: 1px;
    background: var(--line);
  }
  .pjd-cl-table tbody td {
    padding: 12px 14px;
    border-bottom: 1px solid #f1f3f5;
    color: #374151;
    vertical-align: middle;
    font-size: .95rem;
    background: #fff;
  }
  .pjd-cl-table tbody tr:last-child td { border-bottom: none; }
  .pjd-cl-table tbody tr[data-row] { cursor: pointer; transition: background .18s ease; }
  .pjd-cl-table tbody tr[data-row]:hover td { background: #fafcff; }
  .pjd-cl-table tbody tr[data-row].is-expanded td { background: #f8fbff; }
  .pjd-cl-table tbody tr[data-row]:hover .pjd-cl-requisito-text { color: var(--blue); }

  .pjd-cl-th-main,
  .pjd-cl-th-compact {
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }
  .pjd-cl-th-handle {
    color: #c0c7d1;
    font-size: .85rem;
    letter-spacing: 1px;
    line-height: 1;
  }
  .pjd-cl-th-sort {
    color: #a3acb9;
    font-size: .88rem;
    line-height: 1;
  }
  .pjd-cl-check-cell,
  .pjd-cl-check-head { width: 44px; text-align: center; }
  .pjd-cl-check-head::before { display: none !important; }
  .pjd-cl-checkmark {
    width: 22px;
    height: 22px;
    border-radius: 7px;
    border: 1.5px solid #cfd6df;
    background: #fff;
    display: inline-block;
  }
  .pjd-cl-row-toggle {
    width: 20px;
    height: 20px;
    border: none;
    background: transparent;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #2563eb;
    cursor: pointer;
    transition: transform .18s ease;
    padding: 0;
  }
  .pjd-cl-row-toggle svg { width: 16px; height: 16px; }
  tr.is-expanded .pjd-cl-row-toggle { transform: rotate(90deg); }

  .pjd-cl-requisito {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
  }
  .pjd-cl-row-icon {
    width: 18px;
    height: 18px;
    color: #2563eb;
    flex-shrink: 0;
  }
  .pjd-cl-requisito-text {
    display: inline-block;
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 600;
    color: #2f3947;
  }
  .pjd-cl-cell-muted { color: #4b5563; }
  .pjd-cl-cell-strong { font-weight: 700; }
  .pjd-cl-cell-success { color: #15803d; font-weight: 700; }
  .pjd-cl-cell-center { text-align: center; }

  .pjd-cl-cumplimiento-btn {
    border: none;
    background: transparent;
    padding: 0;
    margin: 0;
    font: inherit;
    color: inherit;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
  }
  .pjd-cl-cumplimiento-btn:focus-visible,
  .pjd-cl-status:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px var(--blue-soft);
    border-radius: 8px;
  }
  .pjd-cl-cumple-dot {
    width: 22px;
    height: 22px;
    border-radius: 999px;
    border: 2px solid #a3acb9;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    padding: 0;
    flex-shrink: 0;
    transition: all .18s ease;
    position: relative;
  }
  .pjd-cl-cumple-dot::after {
    content: '';
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: transparent;
  }
  .pjd-cl-cumple-dot.is-cumple { border-color: #22c55e; }
  .pjd-cl-cumple-dot.is-cumple::after { background: #22c55e; }
  .pjd-cl-cumple-dot.is-parcial { border-color: #f4b321; }
  .pjd-cl-cumple-dot.is-parcial::after { background: #f4b321; }
  .pjd-cl-cumple-dot.is-nocumple { border-color: #ff4a4a; }
  .pjd-cl-cumple-dot.is-nocumple::after { background: #ff4a4a; }
  .pjd-cl-cumple-text {
    font-weight: 600;
    color: #4b5563;
    min-width: 64px;
    text-align: left;
  }
  .pjd-cl-cumple-text.is-cumple { color: #16a34a; }
  .pjd-cl-cumple-text.is-parcial { color: #c28708; }
  .pjd-cl-cumple-text.is-nocumple { color: #ef4444; }

  .pjd-cl-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: .95rem;
    cursor: pointer;
    padding: 0;
    border-radius: 999px;
    background: transparent;
    border: none;
    color: #4b5563;
  }
  .pjd-cl-status-icon {
    width: 22px;
    height: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .pjd-cl-status svg { width: 22px; height: 22px; }
  .pjd-cl-status.is-pendiente { color: #ea8a00; }
  .pjd-cl-status.is-revision { color: #2563eb; }
  .pjd-cl-status.is-aprobado { color: #16a34a; }

  .pjd-cl-options {
    background: transparent;
    border: none;
    color: #2563eb;
    width: 28px;
    height: 28px;
    border-radius: 8px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
  }
  .pjd-cl-options:hover { background: #f3f7ff; }

  .pjd-cl-detail {
    padding: 20px 24px;
    background: #fbfcfe;
    border-top: 1px solid #eef2f7;
  }
  .pjd-cl-detail-grid {
    display: grid;
    grid-template-columns: minmax(280px, 1.2fr) minmax(260px, 1fr);
    gap: 16px;
  }
  @media (max-width: 1024px) { .pjd-cl-detail-grid { grid-template-columns: 1fr; } }
  .pjd-cl-detail-card {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 16px 18px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.02);
  }
  .pjd-cl-detail-kicker {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    border-radius: 999px;
    background: var(--blue-soft);
    color: var(--blue);
    font-size: .74rem;
    font-weight: 700;
    margin-bottom: 10px;
  }
  .pjd-cl-detail-row { margin-bottom: 8px; line-height: 1.6; }
  .pjd-cl-detail-row:last-child { margin-bottom: 0; }
  .pjd-cl-source-quote {
    margin-top: 8px;
    padding: 12px 14px;
    border-left: 3px solid var(--blue);
    background: #f8fbff;
    border-radius: 10px;
    color: var(--ink);
    font-weight: 600;
    line-height: 1.55;
    white-space: pre-wrap;
  }
  .pjd-cl-source-meta { display: flex; flex-direction: column; gap: 8px; }
  .pjd-cl-source-pill {
    display: inline-flex;
    width: fit-content;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 999px;
    font-weight: 700;
    font-size: .78rem;
    background: var(--blue-soft);
    color: var(--blue);
    border: 1px solid #c7dcfd;
  }
  .pjd-cl-source-empty { color: var(--muted); font-weight: 600; }
  .pjd-cl-source-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
  .pjd-cl-source-btn {
    border: 1px solid var(--blue);
    background: #fff;
    color: var(--blue);
    border-radius: 10px;
    padding: 8px 12px;
    font-family: inherit;
    font-size: .82rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all .15s;
  }
  .pjd-cl-source-btn:hover { background: var(--blue-soft); transform: translateY(-1px); }
  .pjd-cl-source-btn:active { transform: scale(.98); }

  .pjd-cl-popover {
    position: fixed;
    z-index: 200;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 12px;
    box-shadow: 0 16px 40px rgba(0,0,0,.10);
    padding: 8px;
    display: none;
    min-width: 180px;
  }
  .pjd-cl-popover.is-open { display: block; }
  .pjd-cl-popover button {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 10px 12px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-family: inherit;
    font-size: .92rem;
    text-align: left;
    border-radius: 10px;
    color: var(--ink2);
  }
  .pjd-cl-popover button:hover { background: var(--bg); }
  .pjd-cl-popover button .dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }

  .pjd-cl-no-results { text-align: center; padding: 36px; color: var(--muted); font-size: .95rem; }

  .pjd-cl-add {
    margin-top: 14px;
    padding: 16px 14px;
    border: 1.5px dashed #d5ddec;
    border-radius: 12px;
    text-align: center;
    color: #41536a;
    font-weight: 700;
    cursor: pointer;
    background: #fff;
    width: 100%;
    font-family: inherit;
    font-size: 1rem;
    transition: all .15s;
  }
  .pjd-cl-add:hover { background: #f9fbff; border-color: #bfd3ff; }
  .pjd-cl-add span { color: var(--blue); margin-right: 8px; font-size: 1.1rem; }

  /* ════════════ CHECKLIST COMPACTO ════════════ */
  .pjd-checklist-wrap {
    padding: 14px;
    border-radius: 14px;
  }
  .pjd-checklist-head {
    gap: 10px;
    margin-bottom: 10px;
  }
  .pjd-checklist-title-block {
    gap: 8px;
  }
  .pjd-checklist-title {
    font-size: 1.25rem;
  }
  .pjd-checklist-icon {
    width: 28px;
    height: 28px;
  }
  .pjd-checklist-icon svg {
    width: 15px;
    height: 15px;
  }
  .pjd-checklist-links {
    gap: 12px;
  }
  .pjd-checklist-link {
    font-size: .82rem;
    gap: 6px;
  }
  .pjd-checklist-link svg {
    width: 15px;
    height: 15px;
  }

  .pjd-cl-summary {
    gap: 12px;
    margin-bottom: 12px;
  }
  .pjd-counters {
    grid-template-columns: repeat(4, minmax(105px, 1fr));
    gap: 10px 18px;
  }
  .pjd-counter-top {
    gap: 6px;
    font-size: .78rem;
    line-height: 1.1;
  }
  .pjd-counter-num {
    font-size: 1.05rem;
  }
  .pjd-counter-pct {
    font-size: .72rem;
  }
  .pjd-counter-bar {
    height: 6px;
    margin-top: 5px;
  }
  .pjd-counter.is-total {
    min-width: 82px;
    padding: 7px 10px;
    border-radius: 9px;
  }
  .pjd-counter.is-total .pjd-counter-num {
    font-size: 1rem;
  }

  .pjd-cl-toolbar {
    gap: 10px;
    margin-bottom: 10px;
  }
  .pjd-cl-search {
    min-width: 240px;
    flex-basis: 340px;
  }
  .pjd-cl-search input {
    height: 38px;
    padding-left: 38px;
    border-radius: 10px;
    font-size: .85rem;
  }
  .pjd-cl-search svg {
    left: 13px;
    width: 15px;
    height: 15px;
  }
  .pjd-cl-actions {
    gap: 8px;
  }
  .pjd-cl-btn {
    height: 38px;
    padding: 0 13px;
    border-radius: 9px;
    font-size: .84rem;
    gap: 6px;
  }
  .pjd-cl-btn svg {
    width: 15px;
    height: 15px;
  }

  .pjd-cl-table {
    min-width: 1040px;
  }
  .pjd-cl-table thead th {
    padding: 10px 10px;
    font-size: .78rem;
  }
  .pjd-cl-table thead th + th::before {
    top: 12px;
    bottom: 12px;
  }
  .pjd-cl-table tbody td {
    padding: 7px 10px;
    font-size: .82rem;
  }
  .pjd-cl-th-main,
  .pjd-cl-th-compact {
    gap: 5px;
  }
  .pjd-cl-th-handle,
  .pjd-cl-th-sort {
    font-size: .72rem;
  }
  .pjd-cl-check-cell,
  .pjd-cl-check-head {
    width: 34px;
  }
  .pjd-cl-checkmark {
    width: 18px;
    height: 18px;
    border-radius: 5px;
  }
  .pjd-cl-row-toggle {
    width: 18px;
    height: 18px;
  }
  .pjd-cl-row-toggle svg {
    width: 13px;
    height: 13px;
  }
  .pjd-cl-requisito {
    gap: 7px;
  }
  .pjd-cl-row-icon {
    width: 15px;
    height: 15px;
  }
  .pjd-cl-requisito-text {
    font-size: .84rem;
  }
  .pjd-cl-cumplimiento-btn {
    gap: 7px;
  }
  .pjd-cl-cumple-dot {
    width: 18px;
    height: 18px;
    border-width: 1.8px;
  }
  .pjd-cl-cumple-dot::after {
    width: 6px;
    height: 6px;
  }
  .pjd-cl-cumple-text {
    min-width: 48px;
    font-size: .82rem;
  }
  .pjd-cl-status {
    gap: 6px;
    font-size: .82rem;
  }
  .pjd-cl-status-icon {
    width: 18px;
    height: 18px;
  }
  .pjd-cl-status svg {
    width: 18px;
    height: 18px;
  }
  .pjd-cl-options {
    width: 24px;
    height: 24px;
  }
  .pjd-cl-options svg {
    width: 16px;
    height: 16px;
  }

  .pjd-cl-detail {
    padding: 12px 16px;
  }
  .pjd-cl-detail-grid {
    gap: 10px;
  }
  .pjd-cl-detail-card {
    padding: 12px 14px;
    border-radius: 12px;
  }
  .pjd-cl-detail-kicker {
    padding: 4px 8px;
    font-size: .68rem;
    margin-bottom: 7px;
  }
  .pjd-cl-detail-row,
  .pjd-cl-source-quote {
    font-size: .8rem;
    line-height: 1.45;
  }
  .pjd-cl-source-quote {
    padding: 9px 11px;
  }
  .pjd-cl-source-btn {
    padding: 6px 10px;
    font-size: .76rem;
    border-radius: 8px;
  }
  .pjd-cl-add {
    margin-top: 10px;
    padding: 10px 12px;
    border-radius: 10px;
    font-size: .88rem;
  }
  .pjd-cl-add-form {
    display: none;
    margin-top: 10px;
    padding: 14px;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
  }
  .pjd-cl-add-form.is-open { display: block; animation: pjdMenuIn .16s ease both; }
  .pjd-cl-add-title {
    margin: 0 0 12px;
    font-size: .95rem;
    font-weight: 700;
    color: #111111;
  }
  .pjd-cl-add-grid {
    display: grid;
    grid-template-columns: minmax(220px, 1fr) minmax(180px, .5fr);
    gap: 10px;
  }
  .pjd-cl-add-field { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
  .pjd-cl-add-field.is-full { grid-column: 1 / -1; }
  .pjd-cl-add-field label {
    font-size: .78rem;
    font-weight: 700;
    color: #374151;
  }
  .pjd-cl-add-field input,
  .pjd-cl-add-field textarea {
    width: 100%;
    border: 1px solid #dfe5ee;
    border-radius: 9px;
    background: #fff;
    color: #111827;
    font-family: inherit;
    font-size: .9rem;
    font-weight: 600;
    outline: none;
    transition: border-color .18s ease, box-shadow .18s ease;
  }
  .pjd-cl-add-field input { height: 40px; padding: 0 12px; }
  .pjd-cl-add-field textarea { min-height: 74px; padding: 10px 12px; resize: vertical; }
  .pjd-cl-add-field input:focus,
  .pjd-cl-add-field textarea:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }
  .pjd-cl-add-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 12px;
  }
  .pjd-cl-add-save,
  .pjd-cl-add-cancel {
    height: 38px;
    padding: 0 14px;
    border-radius: 9px;
    font-family: inherit;
    font-size: .86rem;
    font-weight: 700;
    cursor: pointer;
    transition: transform .15s ease, background .15s ease, border-color .15s ease;
  }
  .pjd-cl-add-save {
    border: none;
    background: var(--blue);
    color: #fff;
    box-shadow: 0 4px 12px rgba(0,122,255,.14);
  }
  .pjd-cl-add-save:hover { background: #0a84ff; transform: translateY(-1px); }
  .pjd-cl-add-save:active,
  .pjd-cl-add-cancel:active { transform: scale(.98); }
  .pjd-cl-add-cancel {
    border: 1px solid var(--line);
    background: #fff;
    color: #111827;
  }
  .pjd-cl-add-cancel:hover { background: #f9fafb; }
  @media (max-width: 720px) {
    .pjd-cl-add-grid { grid-template-columns: 1fr; }
  }

  @media (max-width: 1200px) {
    .pjd-counters { grid-template-columns: repeat(2, minmax(120px, 1fr)); }
  }

  /* ════════════ MENUS FUNCIONALES CHECKLIST ════════════ */
  .pjd-cl-menu-wrap { position: relative; display: inline-flex; }
  .pjd-cl-menu {
    position: fixed;
    top: 0;
    right: auto;
    z-index: 260;
    width: 260px;
    max-height: min(560px, 72vh);
    overflow: auto;
    display: none;
    padding: 10px;
    border: 1px solid #dfe5ee;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 14px 36px rgba(15,23,42,.12);
  }
  .pjd-cl-menu.is-open { display: block; animation: pjdMenuIn .16s ease both; }
  @keyframes pjdMenuIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
  .pjd-cl-menu-title {
    margin: 6px 8px 8px;
    font-size: .88rem;
    font-weight: 700;
    color: #111;
  }
  .pjd-cl-menu-sep { height: 1px; background: #e5e7eb; margin: 8px; }
  .pjd-cl-menu-option {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 8px 10px;
    border: 0;
    border-radius: 10px;
    background: transparent;
    font-family: inherit;
    font-size: .88rem;
    font-weight: 600;
    color: #1f2937;
    cursor: pointer;
    text-align: left;
  }
  .pjd-cl-menu-option:hover { background: #f9fafb; }
  .pjd-cl-menu-option.is-disabled { opacity: .45; cursor: not-allowed; }
  .pjd-cl-menu-left { display: inline-flex; align-items: center; gap: 10px; min-width: 0; }
  .pjd-cl-menu-check {
    width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #111827;
    flex-shrink: 0;
  }
  .pjd-cl-menu-square {
    width: 20px;
    height: 20px;
    border: 1.8px solid var(--blue);
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    background: #fff;
    flex-shrink: 0;
  }
  .pjd-cl-menu-option.is-active .pjd-cl-menu-square {
    background: var(--blue);
  }
  .pjd-cl-menu-dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    display: inline-block;
    flex-shrink: 0;
    background: #9ca3af;
  }
  .pjd-cl-menu-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 8px 8px 4px;
  }
  .pjd-cl-menu-mini {
    border: 1px solid var(--line);
    border-radius: 999px;
    background: #fff;
    color: #475569;
    padding: 6px 10px;
    font-family: inherit;
    font-size: .76rem;
    font-weight: 700;
    cursor: pointer;
  }
  .pjd-cl-menu-mini:hover { background: #f9fafb; color: var(--blue); border-color: #cfe0fb; }
  .pjd-cl-table [data-col].is-hidden-col { display: none !important; }
  .pjd-cl-hidden-count {
    display: none;
    margin-left: 6px;
    padding: 1px 6px;
    border-radius: 999px;
    background: var(--blue-soft);
    color: var(--blue);
    font-size: .68rem;
    font-weight: 700;
  }
  .pjd-cl-hidden-count.is-visible { display: inline-flex; }
  .pjd-cl-no-filter-results td {
    text-align: center;
    padding: 24px 12px !important;
    color: var(--muted) !important;
    font-weight: 600;
  }
  @media print {
    .pjd-topbar, .pjd-left, .pjd-resizer, .pjd-cl-toolbar, .pjd-checklist-links, .pjd-cl-add { display: none !important; }
    .pjd-body { display: block !important; }
    .pjd-right { overflow: visible !important; }
    .pjd-pane { display: none !important; }
    .pjd-pane[data-pane="checklist"] { display: block !important; }
    .pjd-checklist-wrap { box-shadow: none !important; border: 0 !important; }
  }

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


  /* ════════════ AJUSTES: POPOVER CUMPLIMIENTO / DESCARGAS / ACCIONES ════════════ */
  .pjd-cl-popover {
    min-width: 224px;
    padding: 10px;
    border-radius: 12px;
    box-shadow: 0 14px 34px rgba(15,23,42,.12);
  }
  .pjd-cl-popover button {
    gap: 12px;
    padding: 10px 12px;
    font-size: .95rem;
    font-weight: 600;
  }
  .pjd-cl-popover button:hover { background: #f8fafc; }
  .pjd-cl-choice-icon {
    width: 24px;
    height: 24px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .pjd-cl-choice-icon svg { width: 24px; height: 24px; }
  .pjd-cl-choice-muted { color: #98a2b3; }
  .pjd-cl-choice-success { color: #16a34a; }
  .pjd-cl-choice-warning { color: #d49100; }
  .pjd-cl-choice-danger { color: #ef4444; }
  .pjd-cl-popover button[data-set-cumplimiento="Cumple"] { color: #16a34a; }
  .pjd-cl-popover button[data-set-cumplimiento="Parcial"] { color: #d49100; }
  .pjd-cl-popover button[data-set-cumplimiento="No Cumple"] { color: #ef4444; }

  .pjd-cl-row-menu {
    position: fixed;
    z-index: 260;
    display: none;
    min-width: 218px;
    padding: 8px;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 12px;
    box-shadow: 0 16px 42px rgba(15,23,42,.12);
  }
  .pjd-cl-row-menu.is-open { display: block; animation: pjdMenuIn .16s ease both; }
  .pjd-cl-row-action {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border: none;
    background: transparent;
    border-radius: 10px;
    font-family: inherit;
    font-size: .95rem;
    font-weight: 600;
    color: #111827;
    cursor: pointer;
    text-align: left;
    transition: background .16s ease, color .16s ease;
  }
  .pjd-cl-row-action:hover { background: #f1f5fb; }
  .pjd-cl-row-action svg { width: 20px; height: 20px; color: var(--blue); flex-shrink: 0; }
  .pjd-cl-row-action.is-danger { color: #ef4444; }
  .pjd-cl-row-action.is-danger svg { color: #ef4444; }

  .pjd-cl-menu[data-variant="download"] { min-width: 210px; }
  .pjd-cl-menu[data-variant="download"] .pjd-cl-menu-option {
    font-size: .95rem;
    padding: 11px 14px;
  }

  .pjd-cl-add-form.is-editing .pjd-cl-add-title::after {
    content: 'Modo edición';
    display: inline-flex;
    margin-left: 10px;
    padding: 4px 8px;
    border-radius: 999px;
    background: var(--blue-soft);
    color: var(--blue);
    font-size: .72rem;
    font-weight: 700;
    vertical-align: middle;
  }



  /* Ajuste final: detalle tipo checklist workspace */
  .pjd-cl-detail {
    padding: 18px 26px 22px;
    background: #f8fbff;
    border-top: 1px solid #e8eef7;
  }
  .pjd-cl-detail-panel {
    max-width: 1060px;
    display: grid;
    gap: 14px;
    color: #344155;
  }
  .pjd-cl-detail-section {
    padding-bottom: 14px;
    border-bottom: 1px solid #dfe6ef;
  }
  .pjd-cl-detail-section:last-child { border-bottom: none; padding-bottom: 0; }
  .pjd-cl-detail-label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 8px;
    font-size: .88rem;
    font-weight: 700;
    color: #3d4858;
  }
  .pjd-cl-detail-text {
    margin: 0;
    font-size: .9rem;
    line-height: 1.45;
    color: #1f2937;
    white-space: pre-wrap;
  }
  .pjd-cl-detail-text.is-muted {
    color: #6b7280;
    font-style: italic;
  }
  .pjd-cl-detail-controls {
    display: grid;
    grid-template-columns: minmax(240px, 1fr) minmax(240px, .9fr);
    gap: 18px 28px;
    align-items: center;
  }
  .pjd-cl-detail-control-row {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
  }
  .pjd-cl-priority-group {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }
  .pjd-cl-priority-btn {
    height: 34px;
    min-width: 72px;
    padding: 0 14px;
    border: none;
    border-radius: 8px;
    background: #eef2f7;
    color: #4b5563;
    font-family: inherit;
    font-weight: 700;
    font-size: .88rem;
    cursor: pointer;
    transition: all .16s ease;
  }
  .pjd-cl-priority-btn:hover { background: #e7edf6; transform: translateY(-1px); }
  .pjd-cl-priority-btn.is-active { background: var(--blue-soft); color: var(--blue); }
  .pjd-cl-detail-date {
    height: 40px;
    min-width: 190px;
    border: 1px solid #dfe5ee;
    border-radius: 9px;
    background: #fff;
    color: #111827;
    font-family: inherit;
    font-size: .9rem;
    font-weight: 600;
    padding: 0 12px;
    outline: none;
  }
  .pjd-cl-detail-select {
    height: 40px;
    width: min(360px, 100%);
    border: 1px solid #dfe5ee;
    border-radius: 9px;
    background: #fff;
    color: #111827;
    font-family: inherit;
    font-size: .9rem;
    font-weight: 600;
    padding: 0 12px;
    outline: none;
  }
  .pjd-cl-detail-date:focus,
  .pjd-cl-detail-select:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }
  .pjd-cl-detail-link {
    margin-left: auto;
    border: none;
    background: transparent;
    color: var(--blue);
    font-family: inherit;
    font-weight: 700;
    font-size: .9rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 8px;
    border-radius: 8px;
  }
  .pjd-cl-detail-link:hover { background: var(--blue-soft); }
  .pjd-cl-detail-link svg { width: 18px; height: 18px; }
  .pjd-cl-detail-empty {
    margin: 18px 0 0;
    text-align: center;
    color: #6b7280;
    font-style: italic;
    font-size: .88rem;
  }
  @media (max-width: 900px) {
    .pjd-cl-detail-controls { grid-template-columns: 1fr; }
    .pjd-cl-detail-control-row { flex-wrap: wrap; }
    .pjd-cl-detail-link { margin-left: 0; }
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
  $checklistRaw = $project->relationLoaded('checklistItems') && $project->checklistItems->count()
      ? $project->checklistItems->map(fn ($it) => method_exists($it, 'toChecklistArray') ? $it->toChecklistArray() : [
          'id'                    => $it->id,
          'requisito'             => $it->requirement,
          'descripcion'           => $it->description,
          'criterio_cumplimiento' => $it->compliance_criteria,
          'formato'               => $it->format ?: 'No aplica',
          'categoria'             => $it->category ?: 'Legal-Administrativo',
          'aplicabilidad'         => $it->applicability ?: 'Único',
          'obligatorio'           => $it->mandatory ? 'Sí' : 'No',
          'cumplimiento'          => match($it->compliance_status) { 'cumple' => 'Cumple', 'parcial' => 'Parcial', 'no_cumple' => 'No Cumple', default => '-' },
          'status'                => match($it->review_status) { 'en_revision' => 'En revisión', 'aprobado' => 'Aprobado', default => 'Pendiente' },
          'prioridad'             => match($it->priority) { 'alta' => 'Alta', 'baja' => 'Baja', default => 'Media' },
          'fecha_limite'          => optional($it->due_date)->format('Y-m-d'),
          'responsable_id'        => $it->responsible_user_id,
          'responsable'           => $it->responsible?->name ?: data_get($it->metadata, 'responsable_text', ''),
          'revisor_id'            => $it->reviewer_user_id,
          'revisor'               => $it->reviewer?->name ?: data_get($it->metadata, 'revisor_text', ''),
          'fuente'                => $it->source_name,
          'pagina'                => $it->source_page,
          'cita'                  => $it->source_quote,
          'notas'                 => $it->notes->map(fn($n) => ['id'=>$n->id,'body'=>$n->body,'user_name'=>$n->user?->name,'created_at'=>optional($n->created_at)->format('Y-m-d H:i:s')])->values()->all(),
          'adjuntos'              => $it->attachments->map(fn($a) => ['id'=>$a->id,'name'=>$a->original_name,'url'=>$a->url,'mime'=>$a->mime_type,'size'=>$a->size,'uploaded_at'=>optional($a->created_at)->format('Y-m-d H:i:s')])->values()->all(),
      ])->values()->all()
      : ($project->checklist ?: ($sd['checklist_sugerido'] ?? []));

  $checklist = collect($checklistRaw)->map(function ($it, $i) {
      if (!is_array($it)) return null;
      return [
          'id'                    => $it['id'] ?? ('item-'.$i),
          'requisito'             => $it['requisito'] ?? $it['item'] ?? $it['text'] ?? 'Sin nombre',
          'descripcion'           => $it['descripcion'] ?? '',
          'criterio_cumplimiento' => $it['criterio_cumplimiento'] ?? '',
          'formato'               => $it['formato'] ?? 'No aplica',
          'categoria'             => $it['categoria'] ?? 'Legal-Administrativo',
          'aplicabilidad'         => $it['aplicabilidad'] ?? 'Único',
          'obligatorio'           => $it['obligatorio'] ?? 'Sí',
          'cumplimiento'          => $it['cumplimiento'] ?? '-',
          'status'                => $it['status'] ?? 'Pendiente',
          'prioridad'             => $it['prioridad'] ?? 'Media',
          'fecha_limite'          => $it['fecha_limite'] ?? null,
          'responsable'           => $it['responsable'] ?? '',
          'responsable_id'        => $it['responsable_id'] ?? null,
          'revisor'               => $it['revisor'] ?? '',
          'revisor_id'            => $it['revisor_id'] ?? null,
          'notas'                 => $it['notas'] ?? [],
          'adjuntos'              => $it['adjuntos'] ?? [],
          'fuente'                => $it['fuente'] ?? '',
          'pagina'                => $it['pagina'] ?? null,
          'cita'                  => $it['cita'] ?? $it['evidencia'] ?? $it['fragmento'] ?? '',
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

    <div class="pjd-resizer" id="pjdResizer" role="separator" aria-orientation="vertical" aria-label="Ajustar ancho del chat y del contenido" tabindex="0"></div>

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
            <div class="pjd-checklist-title-block">
              <h3 class="pjd-checklist-title">{{ $project->name }}</h3>
              <div class="pjd-checklist-title-actions">
                <button type="button" class="pjd-checklist-icon" aria-label="Editar nombre">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/></svg>
                </button>
                <button type="button" class="pjd-checklist-icon" aria-label="Favorito">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 17.27 6.18 3.73-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                </button>
              </div>
            </div>
            <div class="pjd-checklist-links">
              <button type="button" class="pjd-checklist-link" id="pjdClDownload" aria-haspopup="true" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/></svg>
                Descargar lista
              </button>
              <button type="button" class="pjd-checklist-link" id="pjdClExportBtn" aria-label="Exportar archivos">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 5 17 10"/><line x1="12" y1="5" x2="12" y2="17"/></svg>
                Exportar 0 archivos (0 B)
              </button>
            </div>
          </div>

          <div class="pjd-cl-summary">
            <div class="pjd-counters" id="pjdClCounters">
              <div class="pjd-counter"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="sin_revisar">0</span><span class="pjd-counter-label">Sin revisar</span><span class="pjd-counter-pct" data-pct="sin_revisar">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="sin_revisar" style="width:0%"></div></div></div>
              <div class="pjd-counter is-nocumple"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="no_cumple">0</span><span class="pjd-counter-label">No Cumple</span><span class="pjd-counter-pct" data-pct="no_cumple">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="no_cumple" style="width:0%"></div></div></div>
              <div class="pjd-counter is-parcial"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="parcial">0</span><span class="pjd-counter-label">Parcial</span><span class="pjd-counter-pct" data-pct="parcial">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="parcial" style="width:0%"></div></div></div>
              <div class="pjd-counter is-cumple"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="cumple">0</span><span class="pjd-counter-label">Cumple</span><span class="pjd-counter-pct" data-pct="cumple">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="cumple" style="width:0%"></div></div></div>
              <div class="pjd-counter is-pending"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="pendiente">0</span><span class="pjd-counter-label">Pendiente</span><span class="pjd-counter-pct" data-pct="pendiente">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="pendiente" style="width:0%"></div></div></div>
              <div class="pjd-counter is-review"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="revision">0</span><span class="pjd-counter-label">En revisión</span><span class="pjd-counter-pct" data-pct="revision">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="revision" style="width:0%"></div></div></div>
              <div class="pjd-counter is-approved"><div class="pjd-counter-top"><span class="pjd-counter-num" data-counter="aprobado">0</span><span class="pjd-counter-label">Aprobado</span><span class="pjd-counter-pct" data-pct="aprobado">0%</span></div><div class="pjd-counter-bar"><div class="pjd-counter-bar-fill" data-bar="aprobado" style="width:0%"></div></div></div>
              <div class="pjd-counter is-total"><div class="pjd-counter-top"><span class="pjd-counter-num" id="pjdClTotalNum">0</span><span class="pjd-counter-label">Total</span></div></div>
            </div>
          </div>

          <div class="pjd-cl-toolbar">
            <div class="pjd-cl-search">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
              <input type="text" id="pjdClSearch" placeholder="Buscar por requisito, formato o descripción...">
            </div>
            <div class="pjd-cl-actions">
              <button type="button" class="pjd-cl-btn is-primary" id="pjdClReanalisis">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v4"/><path d="M12 17v4"/><path d="M4.93 4.93l2.83 2.83"/><path d="M16.24 16.24l2.83 2.83"/><path d="M3 12h4"/><path d="M17 12h4"/><path d="M4.93 19.07l2.83-2.83"/><path d="M16.24 7.76l2.83-2.83"/></svg>
                Reanálisis
              </button>
              <div class="pjd-cl-menu-wrap"><button type="button" class="pjd-cl-btn" id="pjdClFiltersBtn" aria-label="Filtros" aria-haspopup="true" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Filtros
              </button></div>
              <div class="pjd-cl-menu-wrap"><button type="button" class="pjd-cl-btn" id="pjdClColumnsBtn" aria-label="Columnas" aria-haspopup="true" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                Columnas <span class="pjd-cl-hidden-count" id="pjdClHiddenCount">0</span>
              </button></div>
            </div>
          </div>

          <div class="pjd-cl-menu" id="pjdClExportMenu" aria-hidden="true">
            <button type="button" class="pjd-cl-menu-option" data-export="csv"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-check"></span>Exportar CSV</span></button>
            <button type="button" class="pjd-cl-menu-option" data-export="pdf"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-check"></span>Exportar PDF</span></button>
          </div>

          <div class="pjd-cl-menu" id="pjdClDownloadMenu" data-variant="download" aria-hidden="true">
            <button type="button" class="pjd-cl-menu-option" data-download-list="excel"><span class="pjd-cl-menu-left">Descargar Excel</span></button>
            <button type="button" class="pjd-cl-menu-option" data-download-list="pdf"><span class="pjd-cl-menu-left">Descargar PDF</span></button>
          </div>

          <div class="pjd-cl-menu" id="pjdClFiltersMenu" aria-hidden="true">
            <div class="pjd-cl-menu-title">Cumplimiento</div>
            <button type="button" class="pjd-cl-menu-option is-active" data-filter-group="cumplimiento" data-filter-value="__all"><span class="pjd-cl-menu-left">Todos</span><span class="pjd-cl-menu-square">✓</span></button>
            <button type="button" class="pjd-cl-menu-option" data-filter-group="cumplimiento" data-filter-value="-"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-dot" style="background:#9ca3af"></span>Sin revisar (-)</span><span class="pjd-cl-menu-square"></span></button>
            <button type="button" class="pjd-cl-menu-option" data-filter-group="cumplimiento" data-filter-value="Cumple"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-dot" style="background:#22c55e"></span>Cumple</span><span class="pjd-cl-menu-square"></span></button>
            <button type="button" class="pjd-cl-menu-option" data-filter-group="cumplimiento" data-filter-value="Parcial"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-dot" style="background:#eab308"></span>Parcial</span><span class="pjd-cl-menu-square"></span></button>
            <button type="button" class="pjd-cl-menu-option" data-filter-group="cumplimiento" data-filter-value="No Cumple"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-dot" style="background:#ef4444"></span>No Cumple</span><span class="pjd-cl-menu-square"></span></button>
            <div class="pjd-cl-menu-sep"></div>
            <div class="pjd-cl-menu-title">Status</div>
            <button type="button" class="pjd-cl-menu-option is-active" data-filter-group="status" data-filter-value="__all"><span class="pjd-cl-menu-left">Todos</span><span class="pjd-cl-menu-square">✓</span></button>
            <button type="button" class="pjd-cl-menu-option" data-filter-group="status" data-filter-value="Pendiente"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-dot" style="background:#f59e0b"></span>Pendiente</span><span class="pjd-cl-menu-square"></span></button>
            <button type="button" class="pjd-cl-menu-option" data-filter-group="status" data-filter-value="En revisión"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-dot" style="background:#3b82f6"></span>En revisión</span><span class="pjd-cl-menu-square"></span></button>
            <button type="button" class="pjd-cl-menu-option" data-filter-group="status" data-filter-value="Aprobado"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-dot" style="background:#22c55e"></span>Aprobado</span><span class="pjd-cl-menu-square"></span></button>
            <div class="pjd-cl-menu-sep"></div>
            <div class="pjd-cl-menu-title">Prioridad</div>
            <button type="button" class="pjd-cl-menu-option is-active" data-filter-group="prioridad" data-filter-value="__all"><span class="pjd-cl-menu-left">Todas</span><span class="pjd-cl-menu-square">✓</span></button>
            <button type="button" class="pjd-cl-menu-option" data-filter-group="prioridad" data-filter-value="Alta"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-dot" style="background:#ef4444"></span>Alta</span><span class="pjd-cl-menu-square"></span></button>
            <button type="button" class="pjd-cl-menu-option" data-filter-group="prioridad" data-filter-value="Media"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-dot" style="background:#eab308"></span>Media</span><span class="pjd-cl-menu-square"></span></button>
            <button type="button" class="pjd-cl-menu-option" data-filter-group="prioridad" data-filter-value="Baja"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-dot" style="background:#64748b"></span>Baja</span><span class="pjd-cl-menu-square"></span></button>
            <div class="pjd-cl-menu-actions"><button type="button" class="pjd-cl-menu-mini" id="pjdClClearFilters">Limpiar</button><button type="button" class="pjd-cl-menu-mini" id="pjdClCloseFilters">Cerrar</button></div>
          </div>

          <div class="pjd-cl-menu" id="pjdClColumnsMenu" aria-hidden="true">
            <button type="button" class="pjd-cl-menu-option is-disabled" disabled><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-check">✓</span>Requisito</span></button>
            <button type="button" class="pjd-cl-menu-option is-active" data-column-toggle="formato"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-check">✓</span>Formato</span></button>
            <button type="button" class="pjd-cl-menu-option is-active" data-column-toggle="categoria"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-check">✓</span>Categoría</span></button>
            <button type="button" class="pjd-cl-menu-option is-active" data-column-toggle="aplicabilidad"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-check">✓</span>Aplicación</span></button>
            <button type="button" class="pjd-cl-menu-option is-active" data-column-toggle="obligatorio"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-check">✓</span>Obligatorio</span></button>
            <button type="button" class="pjd-cl-menu-option is-active" data-column-toggle="cumplimiento"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-check">✓</span>Cumplimiento</span></button>
            <button type="button" class="pjd-cl-menu-option is-active" data-column-toggle="status"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-check">✓</span>Status</span></button>
            <button type="button" class="pjd-cl-menu-option is-active" data-column-toggle="opciones"><span class="pjd-cl-menu-left"><span class="pjd-cl-menu-check">✓</span>Opciones</span></button>
            <div class="pjd-cl-menu-actions"><button type="button" class="pjd-cl-menu-mini" id="pjdClShowAllColumns">Mostrar todo</button><button type="button" class="pjd-cl-menu-mini" id="pjdClCloseColumns">Cerrar</button></div>
          </div>

          <div class="pjd-cl-table-wrap">
            <table class="pjd-cl-table" id="pjdClTable">
              <thead>
                <tr>
                  <th class="pjd-cl-check-head"><span class="pjd-cl-checkmark" aria-hidden="true"></span></th>
                  <th data-col="requisito"><span class="pjd-cl-th-main"><span class="pjd-cl-th-handle">⋮⋮</span><span>Requisito</span><span class="pjd-cl-th-sort">↕</span></span></th>
                  <th data-col="formato"><span class="pjd-cl-th-compact"><span class="pjd-cl-th-handle">⋮⋮</span><span>Formato</span><span class="pjd-cl-th-sort">↕</span></span></th>
                  <th data-col="categoria"><span class="pjd-cl-th-compact"><span class="pjd-cl-th-handle">⋮⋮</span><span>Categoría</span><span class="pjd-cl-th-sort">↕</span></span></th>
                  <th data-col="aplicabilidad"><span class="pjd-cl-th-compact"><span class="pjd-cl-th-handle">⋮⋮</span><span>Aplic.</span><span class="pjd-cl-th-sort">↕</span></span></th>
                  <th data-col="obligatorio"><span class="pjd-cl-th-compact"><span class="pjd-cl-th-handle">⋮⋮</span><span>Oblig.</span><span class="pjd-cl-th-sort">↕</span></span></th>
                  <th data-col="cumplimiento"><span class="pjd-cl-th-compact"><span class="pjd-cl-th-handle">⋮⋮</span><span>Cumpl.</span><span class="pjd-cl-th-sort">↕</span></span></th>
                  <th data-col="status"><span class="pjd-cl-th-compact"><span class="pjd-cl-th-handle">⋮⋮</span><span>Status</span><span class="pjd-cl-th-sort">↕</span></span></th>
                  <th data-col="opciones"><span class="pjd-cl-th-compact"><span class="pjd-cl-th-handle">⋮⋮</span><span>Opc.</span></span></th>
                </tr>
              </thead>
              <tbody id="pjdClBody">
                @forelse($checklist as $idx => $it)
                  @php
                    $clPayload = $checklistCitaPayload($it);
                    $docMatch = !empty($it['fuente']) ? $project->documents->firstWhere('filename', $it['fuente']) : null;
                    $docUrl = $docMatch ? $docMatch->url : null;
                  @endphp
                  <tr data-row="{{ $it['id'] }}" data-legacy-index="{{ $idx }}" data-cumplimiento="{{ $it['cumplimiento'] }}" data-status="{{ $it['status'] }}" data-prioridad="{{ $it['prioridad'] }}" data-requisito="{{ e($it['requisito']) }}" data-formato="{{ e($it['formato']) }}" data-descripcion="{{ e($it['descripcion']) }}" data-fecha-limite="{{ $it['fecha_limite'] ?? '' }}" data-responsable="{{ e($it['responsable'] ?? '') }}" data-revisor="{{ e($it['revisor'] ?? '') }}" data-notas="{{ e(collect($it['notas'] ?? [])->map(fn($n) => is_array($n) ? ($n['body'] ?? '') : $n)->filter()->implode("\n")) }}" data-adjuntos='@json($it["adjuntos"] ?? [])' @if($clPayload) data-cita="{{ $clPayload }}" @endif>
                    <td class="pjd-cl-check-cell"><button type="button" class="pjd-cl-row-toggle" data-toggle="{{ $it['id'] }}" title="Ver fuente y detalle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></button></td>
                    <td>
                      <div class="pjd-cl-requisito">
                        <svg class="pjd-cl-row-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="7" x2="19" y2="7"/><line x1="5" y1="12" x2="19" y2="12"/><line x1="5" y1="17" x2="14" y2="17"/></svg>
                        <span class="pjd-cl-requisito-text" title="{{ $it['requisito'] }}">{{ $it['requisito'] }}</span>
                      </div>
                    </td>
                    <td class="pjd-cl-cell-muted" data-col="formato">{{ $it['formato'] }}</td>
                    <td class="pjd-cl-cell-muted" data-col="categoria">{{ Str::limit($it['categoria'], 22) }}</td>
                    <td class="pjd-cl-cell-muted" data-col="aplicabilidad">{{ $it['aplicabilidad'] }}</td>
                    <td class="pjd-cl-cell-center pjd-cl-cell-success" data-col="obligatorio">{{ $it['obligatorio'] }}</td>
                    <td data-col="cumplimiento">
                      @php
                        $cumpClass = match($it['cumplimiento']) { 'Cumple'=>'is-cumple','Parcial'=>'is-parcial','No Cumple'=>'is-nocumple', default=>'' };
                        $cumpLabel = $it['cumplimiento'] ?: '-';
                      @endphp
                      <button type="button" class="pjd-cl-cumplimiento-btn" data-cumplimiento-toggle="{{ $it['id'] }}" title="Cambiar cumplimiento">
                        <span class="pjd-cl-cumple-dot {{ $cumpClass }}"></span>
                        <span class="pjd-cl-cumple-text {{ $cumpClass }}">{{ $cumpLabel }}</span>
                      </button>
                    </td>
                    <td data-col="status">
                      @php
                        $statClass = match($it['status']) { 'En revisión'=>'is-revision','Aprobado'=>'is-aprobado', default=>'is-pendiente' };
                        $statusValue = $it['status'] ?: 'Pendiente';
                      @endphp
                      <button type="button" class="pjd-cl-status {{ $statClass }}" data-status-toggle="{{ $it['id'] }}">
                        <span class="pjd-cl-status-icon">
                          @if($statusValue === 'Aprobado')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 12l2.5 2.5L16 9"/></svg>
                          @elseif($statusValue === 'En revisión')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                          @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                          @endif
                        </span>
                        <span class="pjd-cl-status-text">{{ $statusValue }}</span>
                      </button>
                    </td>
                    <td class="pjd-cl-cell-center" data-col="opciones"><button type="button" class="pjd-cl-options" data-options="{{ $it['id'] }}" title="Opciones"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg></button></td>
                  </tr>
                  <tr class="pjd-cl-detail-row" data-detail="{{ $it['id'] }}" style="display:none;">
                    <td colspan="9" style="padding:0">
                      <div class="pjd-cl-detail">
                        <div class="pjd-cl-detail-panel">
                          <div class="pjd-cl-detail-section">
                            <div class="pjd-cl-detail-label">Descripción:</div>
                            @if($it['descripcion'])
                              <p class="pjd-cl-detail-text pjd-cl-detail-description">{{ $it['descripcion'] }}</p>
                            @else
                              <p class="pjd-cl-detail-text is-muted pjd-cl-detail-description">Sin descripción adicional.</p>
                            @endif
                          </div>

                          <div class="pjd-cl-detail-section">
                            <div class="pjd-cl-detail-controls">
                              <div class="pjd-cl-detail-control-row">
                                <span class="pjd-cl-detail-label" style="margin:0;">Prioridad:</span>
                                <div class="pjd-cl-priority-group" data-priority-group="{{ $it['id'] }}">
                                  <button type="button" class="pjd-cl-priority-btn {{ ($it['prioridad'] ?? 'Media') === 'Alta' ? 'is-active' : '' }}" data-priority-set="Alta">Alta</button>
                                  <button type="button" class="pjd-cl-priority-btn {{ ($it['prioridad'] ?? 'Media') === 'Media' ? 'is-active' : '' }}" data-priority-set="Media">Media</button>
                                  <button type="button" class="pjd-cl-priority-btn {{ ($it['prioridad'] ?? 'Media') === 'Baja' ? 'is-active' : '' }}" data-priority-set="Baja">Baja</button>
                                </div>
                              </div>
                              <div class="pjd-cl-detail-control-row">
                                <span class="pjd-cl-detail-label" style="margin:0;">
                                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                  Fecha límite:
                                </span>
                                <input type="date" class="pjd-cl-detail-date" data-detail-date="{{ $it['id'] }}" value="{{ $it['fecha_limite'] ?? '' }}">
                              </div>
                              <div class="pjd-cl-detail-control-row">
                                <span class="pjd-cl-detail-label" style="margin:0;">
                                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                                  Responsable:
                                </span>
                                <select class="pjd-cl-detail-select" data-detail-responsable="{{ $it['id'] }}">
                                  <option>Sin asignar</option>
                                </select>
                              </div>
                              <div class="pjd-cl-detail-control-row">
                                <span class="pjd-cl-detail-label" style="margin:0;">
                                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                                  Revisor:
                                </span>
                                <select class="pjd-cl-detail-select" data-detail-revisor="{{ $it['id'] }}">
                                  <option>Sin asignar</option>
                                </select>
                              </div>
                            </div>
                          </div>

                          <div class="pjd-cl-detail-section">
                            <div class="pjd-cl-detail-control-row">
                              <span class="pjd-cl-detail-label" style="margin:0;">Notas:</span>
                              <button type="button" class="pjd-cl-detail-link" data-detail-note="{{ $it['id'] }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                Agregar
                              </button>
                            </div>
                            <p class="pjd-cl-detail-empty">No hay notas agregadas.</p>
                          </div>

                          <div class="pjd-cl-detail-section">
                            <div class="pjd-cl-detail-control-row">
                              <span class="pjd-cl-detail-label" style="margin:0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                Documentos Adjuntos:
                              </span>
                              <button type="button" class="pjd-cl-detail-link" data-detail-attach="{{ $it['id'] }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05 12 20.49a6 6 0 0 1-8.49-8.49l9.44-9.44a4 4 0 0 1 5.66 5.66L9.17 17.66a2 2 0 0 1-2.83-2.83l8.49-8.49"/></svg>
                                Adjuntar
                              </button>
                            </div>
                            <p class="pjd-cl-detail-empty">No hay documentos adjuntos. Haz clic en "Adjuntar Documento" para agregar.</p>
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

          <button type="button" class="pjd-cl-add" id="pjdClAddBtn"><span>＋</span>Agregar nuevo requisito</button>

          <form class="pjd-cl-add-form" id="pjdClAddForm" autocomplete="off">
            <h4 class="pjd-cl-add-title" id="pjdClAddTitle">Agregar nuevo requisito</h4>
            <div class="pjd-cl-add-grid">
              <div class="pjd-cl-add-field">
                <label for="pjdClNewReq">Requisito *</label>
                <input type="text" id="pjdClNewReq" placeholder="Nombre del requisito" required>
              </div>
              <div class="pjd-cl-add-field">
                <label for="pjdClNewFormato">Formato</label>
                <input type="text" id="pjdClNewFormato" placeholder="Ej: Anexo 1, Documento 2, etc.">
              </div>
              <div class="pjd-cl-add-field is-full">
                <label for="pjdClNewDesc">Descripción</label>
                <textarea id="pjdClNewDesc" placeholder="Descripción del requisito"></textarea>
              </div>
            </div>
            <div class="pjd-cl-add-actions">
              <button type="submit" class="pjd-cl-add-save" id="pjdClAddSave">Guardar</button>
              <button type="button" class="pjd-cl-add-cancel" id="pjdClAddCancel">Cancelar</button>
            </div>
          </form>
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
  <button data-set-cumplimiento="-"><span class="pjd-cl-choice-icon pjd-cl-choice-muted"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg></span> Sin revisar</button>
  <button data-set-cumplimiento="Cumple"><span class="pjd-cl-choice-icon pjd-cl-choice-success"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 12l2.6 2.6L16.5 9"/></svg></span> Cumple</button>
  <button data-set-cumplimiento="Parcial"><span class="pjd-cl-choice-icon pjd-cl-choice-warning"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 7v6"/><path d="M12 17h.01"/></svg></span> Parcial</button>
  <button data-set-cumplimiento="No Cumple"><span class="pjd-cl-choice-icon pjd-cl-choice-danger"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6"/><path d="M9 9l6 6"/></svg></span> No Cumple</button>
</div>

<div class="pjd-cl-popover" id="pjdClStatusPop">
  <button data-set-status="Pendiente"><span class="dot" style="background:var(--warning)"></span> Pendiente</button>
  <button data-set-status="En revisión"><span class="dot" style="background:var(--blue)"></span> En revisión</button>
  <button data-set-status="Aprobado"><span class="dot" style="background:var(--success)"></span> Aprobado</button>
</div>

<div class="pjd-cl-row-menu" id="pjdClRowMenu" aria-hidden="true">
  <button type="button" class="pjd-cl-row-action" data-row-action="edit">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
    Editar
  </button>
  <button type="button" class="pjd-cl-row-action" data-row-action="duplicate">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
    Duplicar
  </button>
  <button type="button" class="pjd-cl-row-action is-danger" data-row-action="delete">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
    Eliminar
  </button>
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
  const CHECKLIST_ATTACH_URL = @json(route('projects.checklist.attach', $project));
  const CHECKLIST_EXPORT_BASE_URL = @json(url('/projects/' . $project->slug . '/checklist/export'));
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

  // ============ SPLIT VIEW REDIMENSIONABLE (aplica a todas las pestañas) ============
  const pjdWrap = document.querySelector('.pjd-wrap');
  const pjdBody = document.querySelector('.pjd-body');
  const pjdResizer = document.getElementById('pjdResizer');
  const PJD_SPLIT_KEY = 'pjd:project-detail:left-width';

  function clampSplit(px) {
    const bodyRect = pjdBody.getBoundingClientRect();
    const minLeft = 300;
    const minRight = 360;
    const maxLeft = Math.max(minLeft, bodyRect.width - minRight - 10);
    return Math.min(Math.max(px, minLeft), maxLeft);
  }

  function setSplitWidth(px, persist = true) {
    if (!pjdWrap || !pjdBody) return;
    const bodyRect = pjdBody.getBoundingClientRect();
    if (!bodyRect.width) return;
    const safePx = clampSplit(px);
    pjdWrap.style.setProperty('--pjd-left-width', safePx + 'px');
    pjdResizer?.setAttribute('aria-valuenow', String(Math.round(safePx)));
    if (persist) localStorage.setItem(PJD_SPLIT_KEY, String(Math.round(safePx)));
  }

  function restoreSplitWidth() {
    if (!pjdBody || window.matchMedia('(max-width: 1100px)').matches) return;
    const saved = parseInt(localStorage.getItem(PJD_SPLIT_KEY) || '', 10);
    const bodyRect = pjdBody.getBoundingClientRect();
    const fallback = bodyRect.width * 0.44;
    setSplitWidth(Number.isFinite(saved) && saved > 0 ? saved : fallback, false);
  }

  restoreSplitWidth();
  window.addEventListener('resize', () => {
    if (window.matchMedia('(max-width: 1100px)').matches) return;
    const current = parseInt(getComputedStyle(pjdWrap).getPropertyValue('--pjd-left-width'), 10);
    setSplitWidth(Number.isFinite(current) ? current : pjdBody.getBoundingClientRect().width * 0.44, false);
  });

  pjdResizer?.addEventListener('pointerdown', (e) => {
    if (window.matchMedia('(max-width: 1100px)').matches) return;
    e.preventDefault();
    pjdResizer.setPointerCapture(e.pointerId);
    document.body.classList.add('is-pjd-resizing');

    const onMove = (ev) => {
      const bodyRect = pjdBody.getBoundingClientRect();
      setSplitWidth(ev.clientX - bodyRect.left);
    };

    const onUp = () => {
      document.body.classList.remove('is-pjd-resizing');
      pjdResizer.removeEventListener('pointermove', onMove);
      pjdResizer.removeEventListener('pointerup', onUp);
      pjdResizer.removeEventListener('pointercancel', onUp);
    };

    pjdResizer.addEventListener('pointermove', onMove);
    pjdResizer.addEventListener('pointerup', onUp);
    pjdResizer.addEventListener('pointercancel', onUp);
  });

  pjdResizer?.addEventListener('dblclick', () => {
    localStorage.removeItem(PJD_SPLIT_KEY);
    setSplitWidth(pjdBody.getBoundingClientRect().width * 0.44);
  });

  pjdResizer?.addEventListener('keydown', (e) => {
    if (!['ArrowLeft','ArrowRight','Home','End'].includes(e.key)) return;
    e.preventDefault();
    const bodyRect = pjdBody.getBoundingClientRect();
    const current = parseInt(getComputedStyle(pjdWrap).getPropertyValue('--pjd-left-width'), 10) || bodyRect.width * 0.44;
    if (e.key === 'Home') return setSplitWidth(300);
    if (e.key === 'End') return setSplitWidth(bodyRect.width - 360 - 10);
    setSplitWidth(current + (e.key === 'ArrowRight' ? 32 : -32));
  });

  document.querySelectorAll('.js-card-toggle').forEach(head => {
    head.addEventListener('click', () => head.closest('.pjd-card').classList.toggle('is-open'));
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

  function renderChecklistStatusMarkup(val) {
    const status = val || 'Pendiente';
    const icon = status === 'Aprobado'
      ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M8 12l2.5 2.5L16 9"></path></svg>'
      : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>';
    return `<span class="pjd-cl-status-icon">${icon}</span><span class="pjd-cl-status-text">${status}</span>`;
  }

  function applyChecklistCumplimiento(row, val) {
    if (!row) return;
    const dot = row.querySelector('.pjd-cl-cumple-dot');
    const label = row.querySelector('.pjd-cl-cumple-text');
    row.dataset.cumplimiento = val;
    if (dot) {
      dot.className = 'pjd-cl-cumple-dot';
      if (val === 'Cumple') dot.classList.add('is-cumple');
      else if (val === 'Parcial') dot.classList.add('is-parcial');
      else if (val === 'No Cumple') dot.classList.add('is-nocumple');
    }
    if (label) {
      label.className = 'pjd-cl-cumple-text';
      label.textContent = val || '-';
      if (val === 'Cumple') label.classList.add('is-cumple');
      else if (val === 'Parcial') label.classList.add('is-parcial');
      else if (val === 'No Cumple') label.classList.add('is-nocumple');
    }
  }

  function applyChecklistStatus(row, val) {
    if (!row) return;
    const pill = row.querySelector('.pjd-cl-status');
    if (!pill) return;
    row.dataset.status = val;
    pill.className = 'pjd-cl-status';
    const cls = {'Pendiente':'is-pendiente','En revisión':'is-revision','Aprobado':'is-aprobado'};
    pill.classList.add(cls[val] || 'is-pendiente');
    pill.innerHTML = renderChecklistStatusMarkup(val);
  }

  updateCounters();

  async function postChecklistBackend(action, payload = {}) {
    const fd = new FormData();
    fd.append('_token', CSRF);
    fd.append('action', action);

    Object.entries(payload).forEach(([key, value]) => {
      if (value === undefined || value === null) return;
      if (key === 'item') fd.append('item', JSON.stringify(value));
      else fd.append(key, value);
    });

    const res = await fetch(CHECKLIST_URL, {
      method: 'POST',
      headers: { 'Accept': 'application/json' },
      body: fd,
      credentials: 'same-origin'
    });

    const json = await res.json().catch(() => ({}));
    if (!res.ok || json.ok === false) {
      throw new Error(json.message || json.error || 'No se pudo guardar el checklist.');
    }
    return json;
  }

  function updateRowDatasetFromItem(row, item) {
    if (!row || !item) return;
    row.dataset.itemId = item.id || row.dataset.itemId || '';
    row.dataset.requisito = item.requisito || '';
    row.dataset.formato = item.formato || 'No aplica';
    row.dataset.descripcion = item.descripcion || '';
    row.dataset.cumplimiento = item.cumplimiento || '-';
    row.dataset.status = item.status || 'Pendiente';
    row.dataset.prioridad = item.prioridad || 'Media';
    row.dataset.fechaLimite = item.fecha_limite || '';
    row.dataset.responsable = item.responsable || '';
    row.dataset.revisor = item.revisor || '';
    row.dataset.notas = Array.isArray(item.notas) ? item.notas.join('\n') : (item.notas || '');
    row.dataset.adjuntos = JSON.stringify(item.adjuntos || []);
  }

  const clFiltersBtn = document.getElementById('pjdClFiltersBtn');
  const clColumnsBtn = document.getElementById('pjdClColumnsBtn');
  const clExportBtn = document.getElementById('pjdClExportBtn');
  const clDownloadBtn = document.getElementById('pjdClDownload');
  const clFiltersMenu = document.getElementById('pjdClFiltersMenu');
  const clColumnsMenu = document.getElementById('pjdClColumnsMenu');
  const clExportMenu = document.getElementById('pjdClExportMenu');
  const clDownloadMenu = document.getElementById('pjdClDownloadMenu');
  const clRowMenu = document.getElementById('pjdClRowMenu');
  const clHiddenCount = document.getElementById('pjdClHiddenCount');
  const CL_COL_STORAGE = `pjd-checklist-columns-${PROJECT_SLUG}`;
  const clFilterState = { cumplimiento: new Set(['__all']), status: new Set(['__all']), prioridad: new Set(['__all']) };
  let activeOptionsRow = null;
  let editingChecklistRow = null;

  function positionChecklistMenu(btn, menu) {
    if (!btn || !menu) return;
    closeChecklistMenus(menu);
    menu.classList.add('is-open');
    menu.setAttribute('aria-hidden', 'false');
    btn.setAttribute('aria-expanded', 'true');
    const rect = btn.getBoundingClientRect();
    const menuWidth = menu.offsetWidth || 260;
    const left = Math.max(12, Math.min(rect.right - menuWidth, window.innerWidth - menuWidth - 12));
    menu.style.left = left + 'px';
    menu.style.top = Math.min(rect.bottom + 8, window.innerHeight - 80) + 'px';
  }
  function closeChecklistRowMenu() {
    if (!clRowMenu) return;
    clRowMenu.classList.remove('is-open');
    clRowMenu.setAttribute('aria-hidden', 'true');
    activeOptionsRow = null;
  }
  function positionChecklistRowMenu(btn, idx) {
    if (!btn || !clRowMenu) return;
    closeChecklistMenus();
    activeOptionsRow = idx;
    clRowMenu.classList.add('is-open');
    clRowMenu.setAttribute('aria-hidden', 'false');
    const rect = btn.getBoundingClientRect();
    const menuWidth = clRowMenu.offsetWidth || 218;
    const left = Math.max(12, Math.min(rect.right - menuWidth, window.innerWidth - menuWidth - 12));
    clRowMenu.style.left = left + 'px';
    clRowMenu.style.top = Math.min(rect.bottom + 6, window.innerHeight - 170) + 'px';
  }

  function closeChecklistMenus(except = null) {
    [clFiltersMenu, clColumnsMenu, clExportMenu, clDownloadMenu].forEach(menu => {
      if (!menu || menu === except) return;
      menu.classList.remove('is-open');
      menu.setAttribute('aria-hidden', 'true');
    });
    [clFiltersBtn, clColumnsBtn, clExportBtn, clDownloadBtn].forEach(btn => btn?.setAttribute('aria-expanded', 'false'));
    closeChecklistRowMenu();
  }

  clFiltersBtn?.addEventListener('click', (e) => { e.stopPropagation(); positionChecklistMenu(clFiltersBtn, clFiltersMenu); });
  clColumnsBtn?.addEventListener('click', (e) => { e.stopPropagation(); positionChecklistMenu(clColumnsBtn, clColumnsMenu); });
  clExportBtn?.addEventListener('click', (e) => { e.stopPropagation(); positionChecklistMenu(clExportBtn, clExportMenu); });
  clDownloadBtn?.addEventListener('click', (e) => { e.stopPropagation(); positionChecklistMenu(clDownloadBtn, clDownloadMenu); });
  [clFiltersMenu, clColumnsMenu, clExportMenu, clDownloadMenu, clRowMenu].forEach(menu => menu?.addEventListener('click', e => e.stopPropagation()));

  function getHiddenColumns() {
    try { return new Set(JSON.parse(localStorage.getItem(CL_COL_STORAGE) || '[]')); }
    catch (_) { return new Set(); }
  }
  function setHiddenColumns(cols) {
    localStorage.setItem(CL_COL_STORAGE, JSON.stringify([...cols]));
  }
  function applyChecklistColumns() {
    const hidden = getHiddenColumns();
    document.querySelectorAll('.pjd-cl-table [data-col]').forEach(el => {
      el.classList.toggle('is-hidden-col', hidden.has(el.dataset.col));
    });
    document.querySelectorAll('[data-column-toggle]').forEach(btn => {
      const col = btn.dataset.columnToggle;
      const active = !hidden.has(col);
      btn.classList.toggle('is-active', active);
      const check = btn.querySelector('.pjd-cl-menu-check');
      if (check) check.textContent = active ? '✓' : '';
    });
    if (clHiddenCount) {
      clHiddenCount.textContent = hidden.size;
      clHiddenCount.classList.toggle('is-visible', hidden.size > 0);
    }
  }

  clColumnsMenu?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-column-toggle]');
    if (!btn) return;
    const col = btn.dataset.columnToggle;
    const hidden = getHiddenColumns();
    if (hidden.has(col)) hidden.delete(col); else hidden.add(col);
    setHiddenColumns(hidden);
    applyChecklistColumns();
  });
  document.getElementById('pjdClShowAllColumns')?.addEventListener('click', () => { setHiddenColumns(new Set()); applyChecklistColumns(); });
  document.getElementById('pjdClCloseColumns')?.addEventListener('click', () => closeChecklistMenus());
  applyChecklistColumns();

  function updateFilterMenuChecks(group) {
    const selected = clFilterState[group];
    document.querySelectorAll(`[data-filter-group="${group}"]`).forEach(btn => {
      const active = selected.has(btn.dataset.filterValue);
      btn.classList.toggle('is-active', active);
      const sq = btn.querySelector('.pjd-cl-menu-square');
      if (sq) sq.textContent = active ? '✓' : '';
    });
  }
  function applyChecklistFilters() {
    const q = (clSearch?.value || '').trim().toLowerCase();
    let visibleCount = 0;
    clBody.querySelectorAll('tr[data-row]').forEach(r => {
      const textMatch = !q || r.textContent.toLowerCase().includes(q);
      const c = r.dataset.cumplimiento || '-';
      const s = r.dataset.status || 'Pendiente';
      const p = r.dataset.prioridad || 'Media';
      const cMatch = clFilterState.cumplimiento.has('__all') || clFilterState.cumplimiento.has(c);
      const sMatch = clFilterState.status.has('__all') || clFilterState.status.has(s);
      const pMatch = clFilterState.prioridad.has('__all') || clFilterState.prioridad.has(p);
      const match = textMatch && cMatch && sMatch && pMatch;
      r.style.display = match ? '' : 'none';
      if (match) visibleCount++;
      const detail = clBody.querySelector(`tr[data-detail="${r.dataset.row}"]`);
      if (detail && !match) { detail.style.display = 'none'; r.classList.remove('is-expanded'); }
    });
    let empty = document.getElementById('pjdClNoFilterResults');
    if (!empty) {
      empty = document.createElement('tr');
      empty.id = 'pjdClNoFilterResults';
      empty.className = 'pjd-cl-no-filter-results';
      empty.innerHTML = '<td colspan="9">No hay requisitos que coincidan con los filtros.</td>';
      clBody.appendChild(empty);
    }
    empty.style.display = visibleCount === 0 ? '' : 'none';
  }

  clFiltersMenu?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-filter-group]');
    if (!btn) return;
    const group = btn.dataset.filterGroup;
    const value = btn.dataset.filterValue;
    const selected = clFilterState[group];
    if (value === '__all') {
      selected.clear(); selected.add('__all');
    } else {
      selected.delete('__all');
      if (selected.has(value)) selected.delete(value); else selected.add(value);
      if (!selected.size) selected.add('__all');
    }
    updateFilterMenuChecks(group);
    applyChecklistFilters();
  });
  document.getElementById('pjdClClearFilters')?.addEventListener('click', () => {
    Object.keys(clFilterState).forEach(group => { clFilterState[group].clear(); clFilterState[group].add('__all'); updateFilterMenuChecks(group); });
    if (clSearch) clSearch.value = '';
    applyChecklistFilters();
  });
  document.getElementById('pjdClCloseFilters')?.addEventListener('click', () => closeChecklistMenus());

  function getChecklistExportRows(onlyVisible = true) {
    const rows = [];
    clBody.querySelectorAll('tr[data-row]').forEach(r => {
      if (onlyVisible && r.style.display === 'none') return;
      rows.push({
        requisito: r.querySelector('.pjd-cl-requisito-text')?.textContent.trim() || '',
        formato: r.querySelector('[data-col="formato"]')?.textContent.trim() || '',
        categoria: r.querySelector('[data-col="categoria"]')?.textContent.trim() || '',
        aplicabilidad: r.querySelector('[data-col="aplicabilidad"]')?.textContent.trim() || '',
        obligatorio: r.querySelector('[data-col="obligatorio"]')?.textContent.trim() || '',
        cumplimiento: r.dataset.cumplimiento || '-',
        status: r.dataset.status || 'Pendiente',
        prioridad: r.dataset.prioridad || 'Media'
      });
    });
    return rows;
  }
  function downloadChecklistCsv() {
    const headers = ['Requisito','Formato','Categoría','Aplicabilidad','Obligatorio','Cumplimiento','Status','Prioridad'];
    const rows = getChecklistExportRows(true).map(r => [r.requisito,r.formato,r.categoria,r.aplicabilidad,r.obligatorio,r.cumplimiento,r.status,r.prioridad]);
    const csv = [headers, ...rows].map(row => row.map(v => `"${String(v).replace(/"/g,'""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob); const a = document.createElement('a');
    a.href = url; a.download = `checklist-${PROJECT_SLUG}.csv`; a.click(); URL.revokeObjectURL(url);
    showToast('✓ CSV exportado', 'success');
  }
  function printChecklistPdf() {
    const rows = getChecklistExportRows(true);
    const htmlRows = rows.map(r => `<tr><td>${escapeHtml(r.requisito)}</td><td>${escapeHtml(r.formato)}</td><td>${escapeHtml(r.categoria)}</td><td>${escapeHtml(r.aplicabilidad)}</td><td>${escapeHtml(r.obligatorio)}</td><td>${escapeHtml(r.cumplimiento)}</td><td>${escapeHtml(r.status)}</td><td>${escapeHtml(r.prioridad)}</td></tr>`).join('');
    const w = window.open('', '_blank');
    if (!w) { window.print(); return; }
    w.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>Checklist - ${escapeHtml(PROJECT_NAME)}</title><style>body{font-family:Arial,sans-serif;padding:24px;color:#111}h1{font-size:20px;margin:0 0 18px}table{width:100%;border-collapse:collapse;font-size:12px}th,td{border:1px solid #e5e7eb;padding:8px;text-align:left;vertical-align:top}th{background:#f9fafb}</style></head><body><h1>Checklist - ${escapeHtml(PROJECT_NAME)}</h1><table><thead><tr><th>Requisito</th><th>Formato</th><th>Categoría</th><th>Aplicabilidad</th><th>Oblig.</th><th>Cumpl.</th><th>Status</th><th>Prioridad</th></tr></thead><tbody>${htmlRows}</tbody></table></body></html>`);
    w.document.close(); w.focus(); setTimeout(() => w.print(), 250);
  }
  clExportMenu?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-export]');
    if (!btn) return;
    closeChecklistMenus();
    if (btn.dataset.export === 'csv') downloadChecklistCsv();
    if (btn.dataset.export === 'pdf') printChecklistPdf();
  });

  function toggleChecklistDetail(idx, forceOpen = null) {
    const tr = clBody.querySelector(`tr[data-row="${idx}"]`);
    const detail = clBody.querySelector(`tr[data-detail="${idx}"]`);
    if (!tr || !detail) return;
    const shouldOpen = forceOpen === null ? !tr.classList.contains('is-expanded') : !!forceOpen;
    tr.classList.toggle('is-expanded', shouldOpen);
    detail.style.display = shouldOpen ? '' : 'none';
  }

  function checklistRowIconSvg() {
    return '<svg class="pjd-cl-row-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="7" x2="19" y2="7"></line><line x1="5" y1="12" x2="19" y2="12"></line><line x1="5" y1="17" x2="14" y2="17"></line></svg>';
  }
  function checklistChevronSvg() {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>';
  }
  function checklistOptionsSvg() {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="5" cy="12" r="1.5"></circle><circle cx="12" cy="12" r="1.5"></circle><circle cx="19" cy="12" r="1.5"></circle></svg>';
  }
  function nextChecklistRowId() {
    const nums = Array.from(clBody.querySelectorAll('tr[data-row]'))
      .map(r => parseInt(r.dataset.row, 10))
      .filter(n => Number.isFinite(n));
    return nums.length ? String(Math.max(...nums) + 1) : '0';
  }
  function clearChecklistAddForm() {
    document.getElementById('pjdClNewReq').value = '';
    document.getElementById('pjdClNewFormato').value = '';
    document.getElementById('pjdClNewDesc').value = '';
  }
  function closeChecklistAddForm() {
    document.getElementById('pjdClAddForm')?.classList.remove('is-open');
    clearChecklistAddForm();
    setChecklistAddMode('add');
  }
  function setChecklistAddMode(mode, row = null) {
    editingChecklistRow = mode === 'edit' && row ? row.dataset.row : null;
    const form = document.getElementById('pjdClAddForm');
    const title = document.getElementById('pjdClAddTitle');
    const save = document.getElementById('pjdClAddSave');
    form?.classList.toggle('is-editing', !!editingChecklistRow);
    if (title) title.textContent = editingChecklistRow ? 'Editar requisito' : 'Agregar nuevo requisito';
    if (save) save.textContent = editingChecklistRow ? 'Guardar cambios' : 'Guardar';
  }
  function getChecklistRowData(row) {
    if (!row) return null;
    const idx = row.dataset.row;
    const detail = clBody.querySelector(`tr[data-detail="${idx}"]`);
    let desc = row.dataset.descripcion || '';
    if (!desc && detail) {
      const descEl = detail.querySelector('.pjd-cl-detail-description');
      if (descEl) desc = descEl.textContent.trim();
    }
    return {
      requisito: row.dataset.requisito || row.querySelector('.pjd-cl-requisito-text')?.textContent.trim() || '',
      formato: row.dataset.formato || row.querySelector('[data-col="formato"]')?.textContent.trim() || 'No aplica',
      descripcion: desc && desc !== 'Sin descripción adicional.' ? desc : '',
      categoria: row.querySelector('[data-col="categoria"]')?.textContent.trim() || '-',
      aplicabilidad: row.querySelector('[data-col="aplicabilidad"]')?.textContent.trim() || '-',
      obligatorio: row.querySelector('[data-col="obligatorio"]')?.textContent.trim() || '-',
      cumplimiento: row.dataset.cumplimiento || '-',
      status: row.dataset.status || 'Pendiente',
      prioridad: row.dataset.prioridad || 'Media',
      fecha_limite: row.dataset.fechaLimite || '',
      responsable: row.dataset.responsable || '',
      revisor: row.dataset.revisor || '',
      notas: row.dataset.notas ? row.dataset.notas.split('\n').filter(Boolean) : [],
      adjuntos: (() => { try { return JSON.parse(row.dataset.adjuntos || '[]'); } catch (_) { return []; } })()
    };
  }
  function openChecklistAddForm(mode = 'add', row = null) {
    setChecklistAddMode(mode, row);
    if (mode === 'edit' && row) {
      const data = getChecklistRowData(row);
      document.getElementById('pjdClNewReq').value = data.requisito || '';
      document.getElementById('pjdClNewFormato').value = data.formato === '-' ? '' : (data.formato || '');
      document.getElementById('pjdClNewDesc').value = data.descripcion || '';
    } else {
      clearChecklistAddForm();
    }
    const form = document.getElementById('pjdClAddForm');
    form?.classList.add('is-open');
    setTimeout(() => document.getElementById('pjdClNewReq')?.focus(), 80);
  }
  function updateChecklistDomItem(idx, item) {
    const row = clBody.querySelector(`tr[data-row="${idx}"]`);
    const detail = clBody.querySelector(`tr[data-detail="${idx}"]`);
    if (!row) return;

    const requisito = item.requisito || '';
    const formato = item.formato || 'No aplica';
    const descripcion = item.descripcion || '';
    const categoria = item.categoria || row.querySelector('[data-col="categoria"]')?.textContent.trim() || '-';
    const aplicabilidad = item.aplicabilidad || row.querySelector('[data-col="aplicabilidad"]')?.textContent.trim() || '-';
    const obligatorio = item.obligatorio || row.querySelector('[data-col="obligatorio"]')?.textContent.trim() || '-';

    updateRowDatasetFromItem(row, { ...getChecklistRowData(row), ...item, requisito, formato, descripcion });

    const reqText = row.querySelector('.pjd-cl-requisito-text');
    if (reqText) { reqText.textContent = requisito; reqText.title = requisito; }
    const formatoCell = row.querySelector('[data-col="formato"]');
    if (formatoCell) formatoCell.textContent = formato;
    const categoriaCell = row.querySelector('[data-col="categoria"]');
    if (categoriaCell) categoriaCell.textContent = categoria;
    const aplicabilidadCell = row.querySelector('[data-col="aplicabilidad"]');
    if (aplicabilidadCell) aplicabilidadCell.textContent = aplicabilidad;
    const obligatorioCell = row.querySelector('[data-col="obligatorio"]');
    if (obligatorioCell) obligatorioCell.textContent = obligatorio;

    applyChecklistCumplimiento(row, item.cumplimiento || row.dataset.cumplimiento || '-');
    applyChecklistStatus(row, item.status || row.dataset.status || 'Pendiente');

    if (detail) {
      detail.innerHTML = checklistDetailHtml({
        idx,
        descripcion,
        prioridad: item.prioridad || row.dataset.prioridad || 'Media'
      });
    }
  }

  function createChecklistDomItem({ id = '', requisito, formato, descripcion, categoria = '-', aplicabilidad = '-', obligatorio = '-', cumplimiento = '-', status = 'Pendiente', prioridad = 'Media', fecha_limite = '', responsable = '', revisor = '', notas = [], adjuntos = [] }, skipSave = false) {
    const idx = id || nextChecklistRowId();
    const safeReq = escapeHtml(requisito);
    const safeFormato = escapeHtml(formato || 'No aplica');
    const safeDesc = escapeHtml(descripcion || 'Sin descripción adicional.');
    const tr = document.createElement('tr');
    tr.dataset.row = idx;
    tr.dataset.itemId = id || '';
    tr.dataset.cumplimiento = cumplimiento || '-';
    tr.dataset.status = status || 'Pendiente';
    tr.dataset.prioridad = prioridad || 'Media';
    tr.dataset.requisito = requisito;
    tr.dataset.formato = formato || 'No aplica';
    tr.dataset.descripcion = descripcion || '';
    tr.dataset.fechaLimite = fecha_limite || '';
    tr.dataset.responsable = responsable || '';
    tr.dataset.revisor = revisor || '';
    tr.dataset.notas = Array.isArray(notas) ? notas.join('\n') : (notas || '');
    tr.dataset.adjuntos = JSON.stringify(adjuntos || []);
    tr.dataset.added = '1';
    tr.innerHTML = `
      <td class="pjd-cl-check-cell"><button type="button" class="pjd-cl-row-toggle" data-toggle="${idx}" title="Ver fuente y detalle">${checklistChevronSvg()}</button></td>
      <td data-col="requisito"><div class="pjd-cl-requisito">${checklistRowIconSvg()}<span class="pjd-cl-requisito-text" title="${safeReq}">${safeReq}</span></div></td>
      <td class="pjd-cl-cell-muted" data-col="formato">${safeFormato}</td>
      <td class="pjd-cl-cell-muted" data-col="categoria">${escapeHtml(categoria || '-')}</td>
      <td class="pjd-cl-cell-muted" data-col="aplicabilidad">${escapeHtml(aplicabilidad || '-')}</td>
      <td class="pjd-cl-cell-center" data-col="obligatorio">${escapeHtml(obligatorio || '-')}</td>
      <td data-col="cumplimiento"><button type="button" class="pjd-cl-cumplimiento-btn" data-cumplimiento-toggle="${idx}" title="Cambiar cumplimiento"><span class="pjd-cl-cumple-dot"></span><span class="pjd-cl-cumple-text">-</span></button></td>
      <td data-col="status"><button type="button" class="pjd-cl-status is-pendiente" data-status-toggle="${idx}">${renderChecklistStatusMarkup('Pendiente')}</button></td>
      <td class="pjd-cl-cell-center" data-col="opciones"><button type="button" class="pjd-cl-options" data-options="${idx}" title="Opciones">${checklistOptionsSvg()}</button></td>`;

    const detail = document.createElement('tr');
    detail.className = 'pjd-cl-detail-row';
    detail.dataset.detail = idx;
    detail.style.display = 'none';
    detail.innerHTML = checklistDetailHtml({ idx, descripcion: descripcion || '', prioridad: prioridad || 'Media' });

    const empty = clBody.querySelector('.pjd-cl-no-results')?.closest('tr');
    if (empty) empty.remove();
    clBody.appendChild(tr);
    clBody.appendChild(detail);
    applyChecklistCumplimiento(tr, cumplimiento || '-');
    applyChecklistStatus(tr, status || 'Pendiente');
    applyChecklistColumns();
    updateCounters();
    applyChecklistFilters();
    toggleChecklistDetail(idx, true);
    tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
    if (!skipSave) saveChecklist();
  }

  clRowMenu?.addEventListener('click', async (e) => {
    const actionBtn = e.target.closest('[data-row-action]');
    if (!actionBtn || activeOptionsRow === null) return;
    const action = actionBtn.dataset.rowAction;
    const row = clBody.querySelector(`tr[data-row="${activeOptionsRow}"]`);
    const detail = clBody.querySelector(`tr[data-detail="${activeOptionsRow}"]`);
    if (!row) { closeChecklistRowMenu(); return; }

    if (action === 'edit') {
      openChecklistAddForm('edit', row);
      closeChecklistRowMenu();
      return;
    }

    if (action === 'duplicate') {
      try {
        const json = await postChecklistBackend('duplicate', { id: activeOptionsRow, idx: activeOptionsRow });
        const item = json.item || { ...getChecklistRowData(row), requisito: `${getChecklistRowData(row).requisito} copia` };
        createChecklistDomItem(item, true);
        closeChecklistRowMenu();
        updateCounters();
        applyChecklistFilters();
        showToast('✓ Requisito duplicado', 'success');
      } catch (err) {
        showToast(err.message || 'Error al duplicar', 'error');
      }
      return;
    }

    if (action === 'delete') {
      if (!confirm('¿Eliminar este requisito?')) return;
      try {
        await postChecklistBackend('delete', { id: activeOptionsRow, idx: activeOptionsRow });
        detail?.remove();
        row.remove();
        closeChecklistRowMenu();
        updateCounters();
        applyChecklistFilters();
        showToast('✓ Requisito eliminado', 'success');
      } catch (err) {
        showToast(err.message || 'Error al eliminar', 'error');
      }
    }
  });


  function positionSmallChecklistPopover(pop, rect) {
    if (!pop || !rect) return;
    pop.classList.add('is-open');
    const popWidth = pop.offsetWidth || 260;
    const popHeight = pop.offsetHeight || 260;
    const left = Math.max(12, Math.min(rect.left, window.innerWidth - popWidth - 12));
    const spaceBelow = window.innerHeight - rect.bottom;
    const top = spaceBelow >= Math.min(popHeight, 220)
      ? rect.bottom + 6
      : Math.max(12, rect.top - popHeight - 6);
    pop.style.left = left + 'px';
    pop.style.top = Math.max(12, Math.min(top, window.innerHeight - popHeight - 12)) + 'px';
  }

  function checklistDetailHtml({ idx, descripcion = '', prioridad = 'Media' }) {
    const safeDesc = escapeHtml(descripcion || 'Sin descripción adicional.');
    const descClass = descripcion ? 'pjd-cl-detail-text pjd-cl-detail-description' : 'pjd-cl-detail-text is-muted pjd-cl-detail-description';
    const p = prioridad || 'Media';
    return `<td colspan="9" style="padding:0"><div class="pjd-cl-detail"><div class="pjd-cl-detail-panel"><div class="pjd-cl-detail-section"><div class="pjd-cl-detail-label">Descripción:</div><p class="${descClass}">${safeDesc}</p></div><div class="pjd-cl-detail-section"><div class="pjd-cl-detail-controls"><div class="pjd-cl-detail-control-row"><span class="pjd-cl-detail-label" style="margin:0;">Prioridad:</span><div class="pjd-cl-priority-group" data-priority-group="${idx}"><button type="button" class="pjd-cl-priority-btn ${p === 'Alta' ? 'is-active' : ''}" data-priority-set="Alta">Alta</button><button type="button" class="pjd-cl-priority-btn ${p === 'Media' ? 'is-active' : ''}" data-priority-set="Media">Media</button><button type="button" class="pjd-cl-priority-btn ${p === 'Baja' ? 'is-active' : ''}" data-priority-set="Baja">Baja</button></div></div><div class="pjd-cl-detail-control-row"><span class="pjd-cl-detail-label" style="margin:0;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path></svg>Fecha límite:</span><input type="date" class="pjd-cl-detail-date" data-detail-date="${idx}"></div><div class="pjd-cl-detail-control-row"><span class="pjd-cl-detail-label" style="margin:0;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"></path><circle cx="12" cy="7" r="4"></circle></svg>Responsable:</span><select class="pjd-cl-detail-select" data-detail-responsable="${idx}"><option>Sin asignar</option></select></div><div class="pjd-cl-detail-control-row"><span class="pjd-cl-detail-label" style="margin:0;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"></path><circle cx="12" cy="7" r="4"></circle></svg>Revisor:</span><select class="pjd-cl-detail-select" data-detail-revisor="${idx}"><option>Sin asignar</option></select></div></div></div><div class="pjd-cl-detail-section"><div class="pjd-cl-detail-control-row"><span class="pjd-cl-detail-label" style="margin:0;">Notas:</span><button type="button" class="pjd-cl-detail-link" data-detail-note="${idx}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>Agregar</button></div><p class="pjd-cl-detail-empty">No hay notas agregadas.</p></div><div class="pjd-cl-detail-section"><div class="pjd-cl-detail-control-row"><span class="pjd-cl-detail-label" style="margin:0;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg>Documentos Adjuntos:</span><button type="button" class="pjd-cl-detail-link" data-detail-attach="${idx}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05 12 20.49a6 6 0 0 1-8.49-8.49l9.44-9.44a4 4 0 0 1 5.66 5.66L9.17 17.66a2 2 0 0 1-2.83-2.83l8.49-8.49"></path></svg>Adjuntar</button></div><p class="pjd-cl-detail-empty">No hay documentos adjuntos. Haz clic en "Adjuntar Documento" para agregar.</p></div></div></div></td>`;
  }

  clBody?.addEventListener('click', (e) => {
    const optBtn = e.target.closest('[data-options]');
    if (optBtn) { positionChecklistRowMenu(optBtn, optBtn.dataset.options); e.stopPropagation(); return; }

    const tBtn = e.target.closest('[data-toggle]');
    if (tBtn) { toggleChecklistDetail(tBtn.dataset.toggle); e.stopPropagation(); return; }

    const priorityBtn = e.target.closest('[data-priority-set]');
    if (priorityBtn) {
      const detailRow = priorityBtn.closest('tr[data-detail]');
      const idx = detailRow?.dataset.detail;
      const row = idx ? clBody.querySelector(`tr[data-row="${idx}"]`) : null;
      if (row) {
        row.dataset.prioridad = priorityBtn.dataset.prioritySet;
        detailRow.querySelectorAll('[data-priority-set]').forEach(btn => btn.classList.toggle('is-active', btn === priorityBtn));
        saveChecklist();
      }
      e.stopPropagation();
      return;
    }

    const sourceBtn = e.target.closest('.pjd-cl-source-btn[data-cita]');
    if (sourceBtn) { openCita(sourceBtn.getAttribute('data-cita')); e.stopPropagation(); return; }

    const sourceLink = e.target.closest('a.pjd-cl-source-btn');
    if (sourceLink) { e.stopPropagation(); return; }

    const row = e.target.closest('tr[data-row]');
    const clickedControl = e.target.closest('[data-cumplimiento-toggle], [data-status-toggle], [data-options]');
    if (row && !clickedControl) { toggleChecklistDetail(row.dataset.row); e.stopPropagation(); return; }

    const cumpBtn = e.target.closest('[data-cumplimiento-toggle]');
    if (cumpBtn) {
      activeCumpRow = cumpBtn.dataset.cumplimientoToggle;
      const rect = cumpBtn.getBoundingClientRect();
      positionSmallChecklistPopover(cumpPop, rect);
      cumpPop.classList.add('is-open'); statPop.classList.remove('is-open');
      e.stopPropagation(); return;
    }
    const statBtn = e.target.closest('[data-status-toggle]');
    if (statBtn) {
      activeStatusRow = statBtn.dataset.statusToggle;
      const rect = statBtn.getBoundingClientRect();
      positionSmallChecklistPopover(statPop, rect);
      statPop.classList.add('is-open'); cumpPop.classList.remove('is-open');
      e.stopPropagation();
    }
  });

  cumpPop?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-set-cumplimiento]');
    if (!btn || activeCumpRow === null) return;
    const val = btn.dataset.setCumplimiento;
    const row = clBody.querySelector(`tr[data-row="${activeCumpRow}"]`);
    if (!row) return;
    applyChecklistCumplimiento(row, val);
    cumpPop.classList.remove('is-open'); updateCounters(); applyChecklistFilters(); saveChecklist();
  });

  statPop?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-set-status]');
    if (!btn || activeStatusRow === null) return;
    const val = btn.dataset.setStatus;
    const row = clBody.querySelector(`tr[data-row="${activeStatusRow}"]`);
    if (!row) return;
    applyChecklistStatus(row, val);
    statPop.classList.remove('is-open'); updateCounters(); applyChecklistFilters(); saveChecklist();
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('[data-cumplimiento-toggle]') && !e.target.closest('#pjdClCumpPop')) cumpPop?.classList.remove('is-open');
    if (!e.target.closest('[data-status-toggle]') && !e.target.closest('#pjdClStatusPop')) statPop?.classList.remove('is-open');
    if (!e.target.closest('.pjd-cl-menu') && !e.target.closest('.pjd-cl-row-menu') && !e.target.closest('#pjdClFiltersBtn') && !e.target.closest('#pjdClColumnsBtn') && !e.target.closest('#pjdClExportBtn') && !e.target.closest('#pjdClDownload')) closeChecklistMenus();
  });

  clSearch?.addEventListener('input', applyChecklistFilters);

  async function saveChecklist() {
    const rows = Array.from(clBody.querySelectorAll('tr[data-row]')).map(r => {
      const data = getChecklistRowData(r);
      return {
        id: r.dataset.row,
        idx: r.dataset.row,
        requisito: data.requisito,
        descripcion: data.descripcion,
        formato: data.formato,
        categoria: data.categoria,
        aplicabilidad: data.aplicabilidad,
        obligatorio: data.obligatorio,
        cumplimiento: data.cumplimiento,
        status: data.status,
        prioridad: data.prioridad,
        fecha_limite: data.fecha_limite,
        responsable: data.responsable,
        revisor: data.revisor,
        notas: data.notas
      };
    });
    try { const fd = new FormData(); fd.append('_token', CSRF); fd.append('items', JSON.stringify(rows)); await fetch(CHECKLIST_URL, { method:'POST', headers:{'Accept':'application/json'}, body: fd, credentials:'same-origin' }); } catch (_) {}
  }

  document.getElementById('pjdClReanalisis')?.addEventListener('click', async () => {
    if (!confirm('Esto regenerará TODO el checklist con IA. ¿Continuar?')) return;
    const btn = document.getElementById('pjdClReanalisis');
    const original = btn.innerHTML; btn.disabled = true; btn.innerHTML = '⏳ Generando…';
    try { const fd = new FormData(); fd.append('_token', CSRF); fd.append('regenerate', '1'); const res = await fetch(CHECKLIST_URL, { method:'POST', headers:{'Accept':'application/json'}, body: fd, credentials:'same-origin' }); if (res.ok) location.reload(); else alert('Error al regenerar'); }
    catch (e) { alert('Error de red'); } finally { btn.disabled = false; btn.innerHTML = original; }
  });

  document.getElementById('pjdClAddBtn')?.addEventListener('click', () => openChecklistAddForm('add'));
  document.getElementById('pjdClAddCancel')?.addEventListener('click', closeChecklistAddForm);
  document.getElementById('pjdClAddForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const requisito = document.getElementById('pjdClNewReq')?.value.trim() || '';
    const formato = document.getElementById('pjdClNewFormato')?.value.trim() || 'No aplica';
    const descripcion = document.getElementById('pjdClNewDesc')?.value.trim() || '';
    if (!requisito) { document.getElementById('pjdClNewReq')?.focus(); return; }

    const item = {
      requisito,
      formato,
      descripcion,
      categoria: 'Legal-Administrativo',
      aplicabilidad: 'Único',
      obligatorio: 'Sí',
      cumplimiento: '-',
      status: 'Pendiente',
      prioridad: 'Media'
    };

    const saveBtn = document.getElementById('pjdClAddSave');
    const original = saveBtn?.textContent || 'Guardar';
    if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Guardando...'; }

    try {
      if (editingChecklistRow !== null) {
        const currentRow = clBody.querySelector(`tr[data-row="${editingChecklistRow}"]`);
        const currentData = getChecklistRowData(currentRow) || {};
        const payload = { ...currentData, ...item };
        const json = await postChecklistBackend('update', { id: editingChecklistRow, idx: editingChecklistRow, item: payload });
        updateChecklistDomItem(editingChecklistRow, json.item || payload);
        closeChecklistAddForm();
        updateCounters();
        applyChecklistFilters();
        showToast('✓ Requisito actualizado', 'success');
        return;
      }

      const json = await postChecklistBackend('create', { item });
      createChecklistDomItem(json.item || item, true);
      closeChecklistAddForm();
      updateCounters();
      applyChecklistFilters();
      showToast('✓ Requisito agregado', 'success');
    } catch (err) {
      showToast(err.message || 'Error al guardar requisito', 'error');
    } finally {
      if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = original; }
    }
  });


  function downloadChecklistExcel() {
    if (typeof XLSX === 'undefined') { showToast('Excel no disponible', 'error'); return; }
    const headers = ['Requisito','Formato','Categoría','Aplicabilidad','Obligatorio','Cumplimiento','Status','Prioridad'];
    const rows = getChecklistExportRows(true).map(r => [r.requisito,r.formato,r.categoria,r.aplicabilidad,r.obligatorio,r.cumplimiento,r.status,r.prioridad]);
    const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
    ws['!cols'] = headers.map((h, i) => { let max = h.length; rows.forEach(r => { const len = (r[i]||'').toString().length; if (len > max) max = len; }); return { wch: Math.min(Math.max(max + 2, 14), 70) }; });
    const wb = XLSX.utils.book_new(); XLSX.utils.book_append_sheet(wb, ws, 'Checklist');
    XLSX.writeFile(wb, `checklist-${PROJECT_SLUG}.xlsx`);
    showToast('✓ Excel descargado', 'success');
  }
  clDownloadMenu?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-download-list]');
    if (!btn) return;
    closeChecklistMenus();
    if (btn.dataset.downloadList === 'excel') window.location.href = `${CHECKLIST_EXPORT_BASE_URL}/excel`;
    if (btn.dataset.downloadList === 'pdf') window.location.href = `${CHECKLIST_EXPORT_BASE_URL}/pdf`;
  });

  clBody?.addEventListener('change', async (e) => {
    const input = e.target.closest('[data-detail-date], [data-detail-responsable], [data-detail-revisor]');
    if (!input) return;
    const detail = input.closest('tr[data-detail]');
    const idx = detail?.dataset.detail;
    const row = idx ? clBody.querySelector(`tr[data-row="${idx}"]`) : null;
    if (!row) return;

    if (input.matches('[data-detail-date]')) row.dataset.fechaLimite = input.value || '';
    if (input.matches('[data-detail-responsable]')) row.dataset.responsable = input.value || '';
    if (input.matches('[data-detail-revisor]')) row.dataset.revisor = input.value || '';

    try {
      await postChecklistBackend('update', { id: idx, idx, item: getChecklistRowData(row) });
      showToast('✓ Checklist guardado', 'success');
    } catch (err) {
      showToast(err.message || 'Error al guardar detalle', 'error');
    }
  });

  clBody?.addEventListener('click', async (e) => {
    const noteBtn = e.target.closest('[data-detail-note]');
    if (!noteBtn) return;
    e.preventDefault();
    const idx = noteBtn.dataset.detailNote;
    const row = clBody.querySelector(`tr[data-row="${idx}"]`);
    if (!row) return;

    const body = prompt('Agregar nota:');
    if (!body || !body.trim()) return;

    try {
      const json = await postChecklistBackend('note', { id: idx, idx, body: body.trim() });
      const notes = Array.isArray(json.item?.notas) ? json.item.notas : [];
      row.dataset.notas = notes.map(n => typeof n === 'object' ? (n.body || '') : n).filter(Boolean).join('
');
      showToast('✓ Nota agregada', 'success');
    } catch (err) {
      showToast(err.message || 'Error al agregar nota', 'error');
    }
  });

  clBody?.addEventListener('click', async (e) => {
    const attachBtn = e.target.closest('[data-detail-attach]');
    if (!attachBtn) return;
    e.preventDefault();
    const idx = attachBtn.dataset.detailAttach;
    const row = clBody.querySelector(`tr[data-row="${idx}"]`);
    if (!row) return;

    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.multiple = true;
    fileInput.onchange = async () => {
      if (!fileInput.files.length) return;
      const fd = new FormData();
      fd.append('_token', CSRF);
      fd.append('id', idx);
      fd.append('idx', idx);
      Array.from(fileInput.files).forEach(file => fd.append('files[]', file));

      try {
        const res = await fetch(CHECKLIST_ATTACH_URL, { method: 'POST', headers: { 'Accept': 'application/json' }, body: fd, credentials: 'same-origin' });
        const json = await res.json();
        if (!res.ok || json.ok === false) throw new Error(json.message || 'No se pudo adjuntar el documento.');
        row.dataset.adjuntos = JSON.stringify(json.item?.adjuntos || json.adjuntos || []);
        showToast('✓ Documento adjuntado', 'success');
      } catch (err) {
        showToast(err.message || 'Error al adjuntar', 'error');
      }
    };
    fileInput.click();
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