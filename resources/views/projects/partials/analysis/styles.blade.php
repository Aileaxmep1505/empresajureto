@once
@push('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  :root {
    --pjd-blue: #007aff;
    --pjd-ink: #1c2024;
    --pjd-ink-soft: #5b6470;
    --pjd-border: #e6e9ee;
    --pjd-surface: #ffffff;
    --pjd-bg: #f6f8fb;
    --pjd-pending: #b8860b;
    --pjd-pending-bg: #fff8e8;
    --pjd-ok: #1a7f5a;
    --pjd-ok-bg: #e9f7f0;
    --pjd-danger: #c0392b;
    --pjd-danger-bg: #fdecea;
    --pjd-ease: cubic-bezier(0.23, 1, 0.32, 1);
    --pjd-dur-press: 120ms;
    --pjd-dur-ui: 200ms;

    --bg: var(--pjd-bg);
    --card: var(--pjd-surface);
    --ink: var(--pjd-ink);
    --ink2: var(--pjd-ink);
    --muted: var(--pjd-ink-soft);
    --line: var(--pjd-border);
    --blue: var(--pjd-blue);
    --blue-soft: #e6f0ff;
    --success: var(--pjd-ok);
    --success-soft: var(--pjd-ok-bg);
    --danger: var(--pjd-danger);
    --danger-soft: var(--pjd-danger-bg);
    --warning: var(--pjd-pending);
    --warning-soft: var(--pjd-pending-bg);
    --orange: #ea580c;
    --orange-soft: #ffedd5;
  }

  body { font-family: 'Quicksand', sans-serif; background: #ffffff; color: var(--ink2); }

  .pjd-wrap { width: 100%; max-width: 100%; margin: 0; padding: 0; min-height: calc(100vh - 60px); display: flex; flex-direction: column; }

  /* ── Topbar ── */
  .pjd-topbar { display: flex; align-items: center; gap: 18px; flex-wrap: wrap; padding: 12px 24px; background: #fff; border-bottom: 1px solid var(--line); position: sticky; top: 0; z-index: 10; }
  .pjd-back { color: var(--muted); text-decoration: none; font-size: 1.1rem; padding: 4px 8px; border-radius: 8px; transition-property: background, border-color, color, opacity, transform, box-shadow; transition-duration: .15s; transition-timing-function: var(--pjd-ease); }
  .pjd-back:hover { background: var(--bg); color: var(--blue); }
  .pjd-title { font-weight: 700; color: var(--ink); font-size: .98rem; display: flex; align-items: center; gap: 6px; }
  .pjd-status-pill { padding: 2px 8px; border-radius: 999px; font-size: .68rem; font-weight: 700; background: var(--blue-soft); color: var(--blue); margin-left: 6px; }
  .pjd-status-pill.is-ready { background: var(--success-soft); color: var(--success); }
  .pjd-status-pill.is-processing { background: var(--warning-soft); color: var(--warning); }
  .pjd-status-pill.is-error { background: var(--danger-soft); color: var(--danger); }

  .pjd-tabs { display: flex; gap: 4px; flex: 1; flex-wrap: wrap; }
  .pjd-tab { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 999px; border: none; background: transparent; font-family: inherit; font-size: .88rem; font-weight: 600; color: var(--ink2); cursor: pointer; transition-property: background, border-color, color, opacity, transform, box-shadow; transition-duration: .18s; transition-timing-function: var(--pjd-ease); }
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
  .pjd-chat-reset { background: var(--bg); border: 1px solid var(--line); padding: 5px 12px; border-radius: 999px; font-size: .8rem; font-weight: 600; color: var(--ink2); cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition-property: background, border-color, color, opacity, transform, box-shadow; transition-duration: .18s; transition-timing-function: var(--pjd-ease); }
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
  .pjd-chat-table-btn { background: var(--bg); border: 1px solid var(--line); padding: 6px 14px; border-radius: 999px; font-family: inherit; font-size: .8rem; font-weight: 700; color: var(--ink2); cursor: pointer; transition-property: background, border-color, color, opacity, transform, box-shadow; transition-duration: .15s; transition-timing-function: var(--pjd-ease); display: inline-flex; align-items: center; gap: 5px; }
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
    display: grid; place-items: center; transition-property: background, border-color, color, opacity, transform, box-shadow; transition-duration: .15s ease; transition-timing-function: var(--pjd-ease);
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
    transition-property: background, border-color, color, opacity, transform, box-shadow; transition-duration: .16s ease; transition-timing-function: var(--pjd-ease);
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
  .pjd-cita-quote { border: 1px solid var(--pjd-border); background: #f8faff; padding: 14px 16px; border-radius: 8px; font-size: .95rem; color: var(--ink); margin-bottom: 16px; white-space: pre-wrap; }
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
  .pjd-doc-drawer-quote-text { font-size: .92rem; line-height: 1.6; color: var(--ink); border: 1px solid var(--pjd-border); background: #f8fbff; padding: 12px 14px; border-radius: 10px; white-space: pre-wrap; }
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
    transition-property: background, border-color, color, opacity, transform, box-shadow; transition-duration: .2s ease; transition-timing-function: var(--pjd-ease);
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
    transition-property: background, border-color, color, opacity, transform, box-shadow; transition-duration: .18s ease; transition-timing-function: var(--pjd-ease);
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
    transition-property: background, border-color, color, opacity, transform, box-shadow; transition-duration: .18s ease; transition-timing-function: var(--pjd-ease);
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
    border: 1px solid var(--pjd-border);
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
    transition-property: background, border-color, color, opacity, transform, box-shadow; transition-duration: .15s; transition-timing-function: var(--pjd-ease);
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
    transition-property: background, border-color, color, opacity, transform, box-shadow; transition-duration: .15s; transition-timing-function: var(--pjd-ease);
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
  .pjd-editor-header { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin: 0 0 14px; }
  .pjd-editor-header-left { display:flex; align-items:center; gap:8px; flex-wrap:wrap; min-width:0; }
  .pjd-editor-header-right { display:flex; align-items:center; gap:6px; margin-left:auto; }
  .pjd-editor-project { font-size: 1.05rem; line-height:1; font-weight: 700; color: #111111; letter-spacing: -.01em; }
  .pjd-editor-mini { width: 28px; height: 28px; display:inline-flex; align-items:center; justify-content:center; border:none; background:transparent; color:#7c7c7c; border-radius:999px; cursor:pointer; transition: background .18s ease, color .18s ease, transform .18s ease; }
  .pjd-editor-mini:hover { background:#f5f7fa; color:#111111; }
  .pjd-editor-mini:active { transform:scale(.98); }
  .pjd-editor-mini svg { width: 17px; height: 17px; }
  .pjd-borrador-tabs { display:flex; align-items:center; gap:8px; margin-left:6px; }
  .pjd-borrador-tab { min-height: 36px; display:inline-flex; align-items:center; gap:8px; padding: 0 15px; border-radius:999px; border:1px solid #d9dde5; background:#ffffff; font-family:inherit; font-size: .82rem; font-weight: 700; color:#666666; cursor:pointer; box-shadow:0 1px 2px rgba(15,23,42,.03); transition-property: background, border-color, color, opacity, transform, box-shadow; transition-duration: .18s ease; transition-timing-function: var(--pjd-ease); }
  .pjd-borrador-tab:hover { border-color:#cfd5df; color:#111111; background:#fbfcfd; }
  .pjd-borrador-tab.is-active { background:#0f6fff; border-color:#0f6fff; color:#ffffff; box-shadow:0 6px 18px rgba(15,111,255,.18); }
  .pjd-borrador-tab svg { width: 15px; height: 15px; }
  .pjd-editor-icon-btn { width: 32px; height: 32px; display:inline-flex; align-items:center; justify-content:center; border:none; background:transparent; color:#767676; border-radius:10px; cursor:pointer; transition: background .18s ease, color .18s ease, transform .18s ease; }
  .pjd-editor-icon-btn:hover { background:#f5f7fa; color:#111111; }
  .pjd-editor-icon-btn:active { transform:scale(.98); }
  .pjd-editor-icon-btn svg { width: 18px; height: 18px; }
  .pjd-borrador-actions { display:flex; gap:10px; align-items:center; margin-bottom:14px; flex-wrap:wrap; }
  .pjd-borrador-actions .pjd-cl-btn { min-height:40px; padding:0 16px; border-radius:12px; }
  .pjd-borrador-section { display:none; }
  .pjd-borrador-section.is-active { display:block; }
  .pjd-wrap.is-conversation-collapsed .pjd-body { grid-template-columns: 0 0 minmax(360px, 1fr); }
  .pjd-wrap.is-conversation-collapsed .pjd-left,
  .pjd-wrap.is-conversation-collapsed .pjd-resizer { opacity: 0; pointer-events: none; overflow: hidden; border: 0; }
  .pjd-wrap.is-conversation-collapsed .pjd-right { border-left: 1px solid var(--line); }

  .pjd-draft-shell {
    border: 1px solid var(--line);
    border-radius: 16px;
    background: #fff;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
  }

  .pjd-draft-toolbar {
    background: #fbfcfe;
    border-bottom: 1px solid var(--line);
    padding: 10px 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .pjd-draft-group {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding-right: 8px;
    margin-right: 4px;
    border-right: 1px solid #e4e7ec;
  }

  .pjd-draft-group:last-child { border-right: 0; margin-right: 0; padding-right: 0; }

  .pjd-draft-btn,
  .pjd-draft-select,
  .pjd-draft-color {
    min-width: 34px;
    height: 34px;
    border: 1px solid transparent;
    background: transparent;
    border-radius: 9px;
    color: #111827;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    cursor: pointer;
    font-family: 'Quicksand', sans-serif;
    font-size: .86rem;
    font-weight: 700;
    transition: background .16s ease, border-color .16s ease, transform .16s ease;
  }

  .pjd-draft-btn svg {
    width: 18px;
    height: 18px;
    stroke-width: 2.1;
  }

  .pjd-draft-btn:hover,
  .pjd-draft-select:hover,
  .pjd-draft-color:hover {
    background: #f4f7fb;
    border-color: #edf0f5;
  }

  .pjd-draft-btn:active { transform: scale(.97); }
  .pjd-draft-btn.is-active { background: var(--blue-soft); color: var(--blue); border-color: #cfe0ff; }
  .pjd-draft-btn.is-muted { color: var(--muted); }

  .pjd-draft-select {
    justify-content: flex-start;
    min-width: 96px;
    padding: 0 10px;
    border-color: #edf0f5;
    background: #fff;
  }

  .pjd-draft-select.is-small { min-width: 70px; }

  .pjd-draft-color {
    width: 34px;
    padding: 0;
    overflow: hidden;
  }

  .pjd-draft-color input {
    width: 42px;
    height: 42px;
    border: 0;
    padding: 0;
    background: transparent;
    cursor: pointer;
  }

  .pjd-draft-editor {
    width: 100%;
    min-height: 640px;
    padding: 30px 38px;
    border: 0;
    background: #fff;
    font-family: 'Quicksand', sans-serif;
    font-size: 1rem;
    line-height: 1.72;
    color: var(--ink);
    outline: none;
    overflow: auto;
  }

  .pjd-draft-editor:empty:before {
    content: 'Empieza a escribir tu borrador...';
    color: #a0a7b2;
  }

  .pjd-draft-editor h1 { font-size: 2rem; line-height: 1.2; margin: 1rem 0 .6rem; color: #111; }
  .pjd-draft-editor h2 { font-size: 1.55rem; line-height: 1.25; margin: .9rem 0 .55rem; color: #111; }
  .pjd-draft-editor h3 { font-size: 1.25rem; line-height: 1.3; margin: .8rem 0 .45rem; color: #111; }
  .pjd-draft-editor p { margin: .55rem 0; }
  .pjd-draft-editor blockquote { margin: 14px 0; padding: 10px 14px; border: 1px solid var(--pjd-border); background: #f8fbff; color: #344054; border-radius: 0 10px 10px 0; }
  .pjd-draft-editor a { color: var(--blue); text-decoration: underline; }
  .pjd-draft-editor img { max-width: 100%; border-radius: 12px; border: 1px solid var(--line); }
  .pjd-draft-editor hr { border: 0; border-top: 1px solid var(--line); margin: 18px 0; }
  .pjd-draft-editor table { border-collapse: collapse; width: 100%; margin: 14px 0; }
  .pjd-draft-editor th,
  .pjd-draft-editor td { border: 1px solid #e5e7eb; padding: 10px 12px; text-align: left; vertical-align: top; }
  .pjd-draft-editor th { background: #f3f4f6; font-weight: 700; }

  .pjd-reporte-empty { background: #fff; border: 1.5px dashed var(--line); border-radius: 14px; padding: 60px 30px; text-align: center; min-height: 360px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 18px; }
  .pjd-reporte-empty p { margin: 0; font-size: 1rem; color: var(--muted); font-weight: 600; }
  .pjd-reporte-btn { background: var(--blue); color: #fff; padding: 12px 28px; border-radius: 999px; border: none; font-family: inherit; font-weight: 700; font-size: .92rem; cursor: pointer; box-shadow: 0 6px 14px rgba(0,122,255,.22); transition-property: background, border-color, color, opacity, transform, box-shadow; transition-duration: .15s; transition-timing-function: var(--pjd-ease); display: inline-flex; align-items: center; gap: 8px; }
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

  .pjd-report-doc { max-width: 980px; margin: 0 auto; color: #333333; }
  .pjd-report-title { margin: 0 0 22px !important; font-size: 1.38rem !important; letter-spacing: -.02em; color: #111111 !important; }
  .pjd-report-section { padding: 0 0 24px; margin: 0 0 24px; border-bottom: 1px solid #ebebeb; }
  .pjd-report-section:last-child { border-bottom: 0; margin-bottom: 0; padding-bottom: 0; }
  .pjd-report-section h2 { margin: 0 0 18px !important; font-size: 1.08rem !important; color: #111111 !important; }
  .pjd-report-grid { display: grid; gap: 16px; }
  .pjd-report-item { padding: 0 0 12px; }
  .pjd-report-label { display: block; margin: 0 0 7px; color: #111111; font-weight: 700; font-size: .94rem; }
  .pjd-report-value { margin: 0; color: #333333; font-weight: 500; line-height: 1.65; white-space: pre-line; }
  .pjd-report-empty { color: #888888; font-style: italic; }
  .pjd-report-question { margin: 0 0 7px; color:#111111; font-weight:700; }
  .pjd-report-answer { margin:0; color:#333333; line-height:1.65; }
  .pjd-report-points { margin: 0; padding: 0; list-style: none; display:grid; gap: 10px; }
  .pjd-report-points li { padding: 12px 14px; border: 1px solid #ebebeb; border-radius: 12px; background:#ffffff; }
  .pjd-report-point-title { display:block; color:#111111; font-weight:700; margin-bottom:4px; }
  .pjd-report-point-meta { color:#888888; font-size:.84rem; }

  .pjd-documents-card {
    background:#ffffff;
    border:1px solid var(--line);
    border-radius:16px;
    padding:22px;
    box-shadow:0 4px 12px rgba(0,0,0,.02);
  }
  .pjd-documents-title { margin:0 0 18px; color:#111111; font-size:1.08rem; font-weight:700; }
  .pjd-documents-search-row { display:flex; align-items:center; gap:14px; margin-bottom:18px; }
  .pjd-documents-search { flex:1; min-width:0; height:46px; display:flex; align-items:center; gap:10px; border:1px solid #e2e5ea; border-radius:12px; padding:0 14px; background:#ffffff; transition:border-color .18s ease, box-shadow .18s ease; }
  .pjd-documents-search:focus-within { border-color:var(--blue); box-shadow:0 0 0 3px var(--blue-soft); }
  .pjd-documents-search svg { width:19px; height:19px; color:#777777; flex-shrink:0; }
  .pjd-documents-search input { border:0; outline:0; width:100%; height:100%; font:inherit; font-size:.92rem; color:var(--ink); background:transparent; }
  .pjd-documents-search input::placeholder { color:#a3a9b5; }
  .pjd-documents-count { min-width:42px; height:42px; border-radius:999px; background:#f6f6f7; color:#555555; display:inline-flex; align-items:center; justify-content:center; font-weight:700; }
  .pjd-doc-list { display:flex; flex-direction:column; gap:10px; }
  .pjd-doc-empty { padding:34px; border:1px dashed #dfe3ea; border-radius:14px; color:var(--muted); text-align:center; font-weight:600; }
  .pjd-doc-card { position:relative; border:1px solid #e2e2e2; border-radius:14px; background:#ffffff; padding:16px; transition:box-shadow .18s ease, transform .18s ease, border-color .18s ease; }
  .pjd-doc-card:hover { transform:translateY(-1px); box-shadow:0 8px 18px rgba(15,23,42,.04); border-color:#dcdfe5; }
  .pjd-doc-main { display:flex; align-items:center; gap:14px; min-width:0; }
  .pjd-doc-file-icon { width:44px; height:44px; border-radius:10px; background:#e6f0ff; color:var(--blue); display:grid; place-items:center; flex-shrink:0; }
  .pjd-doc-file-icon svg { width:23px; height:23px; }
  .pjd-doc-content { flex:1; min-width:0; }
  .pjd-doc-title-line { display:flex; align-items:center; gap:8px; flex-wrap:wrap; min-width:0; }
  .pjd-doc-name { font-size:1rem; font-weight:700; color:#111111; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width: min(560px, 100%); }
  .pjd-doc-badge { display:inline-flex; align-items:center; height:30px; padding:0 13px; border-radius:999px; font-size:.78rem; font-weight:700; }
  .pjd-doc-badge.is-pdf { background:#ffebeb; color:#d91f1f; }
  .pjd-doc-badge.is-file { background:#f2f4f7; color:#555555; }
  .pjd-doc-status { background:#d6f9e7; color:#027a48; }
  .pjd-doc-status.is-processing { background:#fff7db; color:#b45309; }
  .pjd-doc-status.is-error { background:#ffebeb; color:#ff4a4a; }
  .pjd-doc-sub { margin-top:5px; color:#666f7d; font-size:.86rem; font-weight:500; }
  .pjd-doc-match { display:inline-flex; align-items:center; gap:6px; margin-top:8px; color:#6b7280; font-size:.78rem; font-weight:700; background:#f8fafc; border:1px solid #edf0f4; border-radius:999px; padding:5px 10px; }
  .pjd-doc-actions { display:flex; align-items:center; gap:8px; margin-left:auto; }
  .pjd-doc-icon-btn { width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; border:0; border-radius:999px; background:transparent; color:#111111; cursor:pointer; transition:background .18s ease, transform .18s ease; }
  .pjd-doc-icon-btn:hover { background:#f6f7f9; }
  .pjd-doc-icon-btn:active { transform:scale(.98); }
  .pjd-doc-icon-btn svg { width:20px; height:20px; }
  .pjd-doc-card.is-open .pjd-doc-toggle svg { transform:rotate(180deg); }
  .pjd-doc-details { display:none; margin:14px 0 0 58px; padding-top:14px; border-top:1px solid #eceff3; }
  .pjd-doc-card.is-open .pjd-doc-details { display:block; }
  .pjd-doc-details h4 { margin:0 0 8px; font-size:.93rem; color:#111111; font-weight:700; }
  .pjd-doc-details p { margin:0; color:#606a78; font-size:.9rem; line-height:1.45; max-width:900px; }
  .pjd-doc-detail-meta { margin-top:10px; color:#888888; font-size:.78rem; font-weight:600; }
  .pjd-doc-menu { position:absolute; right:22px; top:68px; min-width:230px; background:#ffffff; border:1px solid #e2e5ea; border-radius:12px; box-shadow:0 10px 28px rgba(15,23,42,.12); padding:8px; z-index:50; display:none; }
  .pjd-doc-menu.is-open { display:block; }
  .pjd-doc-menu a,
  .pjd-doc-menu button { width:100%; display:flex; align-items:center; gap:10px; padding:10px 12px; border:none; background:transparent; border-radius:9px; color:#111827; font:inherit; font-size:.92rem; font-weight:600; text-decoration:none; cursor:pointer; text-align:left; }
  .pjd-doc-menu a:hover,
  .pjd-doc-menu button:hover { background:#f3f6fb; }
  .pjd-doc-menu svg { width:18px; height:18px; flex-shrink:0; }
  .pjd-doc-menu .is-danger { color:#e11d1d; }
  .pjd-doc-menu .is-danger:hover { background:#fff1f1; }

  .pjd-inicio-card { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 18px; margin-bottom: 14px; }
  .pjd-inicio-card h4 { margin: 0 0 8px; font-size: .9rem; font-weight: 700; color: var(--ink); }
  .pjd-inicio-card p { margin: 0; font-size: .88rem; color: var(--muted); }

  .pjd-loading-dots { display: inline-flex; gap: 4px; }
  .pjd-loading-dots span { width: 6px; height: 6px; border-radius: 50%; background: var(--muted); animation: pjdBounce 1.2s infinite var(--pjd-ease); }
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
    transition-property: background, border-color, color, opacity, transform, box-shadow; transition-duration: .16s ease; transition-timing-function: var(--pjd-ease);
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


  .pjd-report-editor-shell { margin-top: 10px; }
  .pjd-report-editor { min-height: 760px; padding: 42px 48px; }
  .pjd-report-editor .pjd-reporte-content { max-width: 100%; margin: 0; }
  .pjd-report-editor .pjd-report-doc { max-width: 980px; margin: 0 auto; }
  .pjd-report-editor:focus { outline: none; }



  /* ════════════ FIX SCROLL CHAT / CONTENIDO ════════════ */
  .pjd-body {
    height: calc(100dvh - 57px) !important;
    max-height: calc(100dvh - 57px) !important;
    min-height: 0 !important;
    overflow: hidden !important;
    align-items: stretch !important;
  }

  .pjd-left,
  .pjd-resizer,
  .pjd-right {
    height: 100% !important;
    max-height: 100% !important;
    min-height: 0 !important;
  }

  .pjd-left {
    overflow: hidden !important;
  }

  .pjd-chat-head,
  .pjd-chat-input {
    flex: 0 0 auto !important;
  }

  .pjd-chat-list {
    flex: 1 1 auto !important;
    min-height: 0 !important;
    max-height: none !important;
    overflow-y: auto !important;
  }

  .pjd-right {
    overflow: auto !important;
  }

  @media (max-width: 1100px) {
    .pjd-body {
      height: auto !important;
      max-height: none !important;
      overflow: visible !important;
    }

    .pjd-left {
      height: min(72dvh, 680px) !important;
      max-height: min(72dvh, 680px) !important;
      min-height: 420px !important;
      border-bottom: 1px solid var(--line);
    }

    .pjd-chat-list {
      max-height: none !important;
    }
  }


  /* ════════════ AJUSTE FINAL: CHAT MAS COMPACTO ════════════ */
  .pjd-body {
    height: calc(100dvh - 128px) !important;
    max-height: calc(100dvh - 128px) !important;
  }

  .pjd-chat-head {
    padding: 7px 14px !important;
    min-height: 42px !important;
  }

  .pjd-chat-reset {
    padding: 4px 10px !important;
    font-size: .76rem !important;
  }

  .pjd-chat-list {
    padding: 14px !important;
    gap: 10px !important;
  }

  .pjd-msg-avatar {
    width: 25px !important;
    height: 25px !important;
    font-size: .74rem !important;
  }

  .pjd-msg-body {
    padding: 9px 12px !important;
    font-size: .88rem !important;
    line-height: 1.45 !important;
    border-radius: 13px !important;
  }

  .pjd-chat-input {
    padding: 10px 14px !important;
    gap: 8px !important;
  }

  .pjd-chat-input input {
    padding: 8px 14px !important;
    font-size: .88rem !important;
  }

  .pjd-chat-send {
    width: 34px !important;
    height: 34px !important;
  }

  @media (max-width: 1100px) {
    .pjd-left {
      height: min(58dvh, 560px) !important;
      max-height: min(58dvh, 560px) !important;
      min-height: 340px !important;
    }
  }


  /* ════════════ CHAT INPUT PRO: BARRA LUMINOSA TIPO SKIPER ════════════ */
  .pjd-chat-input {
    position: relative !important;
    margin: 10px 12px 12px !important;
    padding: 0 !important;
    gap: 0 !important;
    border: 1px solid rgba(0,122,255,.28) !important;
    border-radius: 28px !important;
    background: rgba(255,255,255,.92) !important;
    box-shadow:
      0 10px 28px rgba(15,23,42,.06),
      0 0 0 4px rgba(0,122,255,.08) !important;
    overflow: hidden !important;
    isolation: isolate;
    transition: border-color .22s ease, box-shadow .22s ease, transform .18s ease;
  }

  .pjd-chat-input::before {
    content: "";
    position: absolute;
    inset: -2px;
    border-radius: inherit;
    padding: 2px;
    background:
      conic-gradient(from var(--pjd-chat-angle, 180deg),
        transparent 0deg,
        transparent 92deg,
        rgba(0,122,255,.16) 118deg,
        rgba(0,122,255,.95) 150deg,
        rgba(123,180,255,.72) 176deg,
        transparent 218deg,
        transparent 360deg);
    -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
    -webkit-mask-composite: xor;
            mask-composite: exclude;
    opacity: 0;
    pointer-events: none;
    z-index: 0;
  }

  .pjd-chat-input::after {
    content: "";
    position: absolute;
    left: 18px;
    right: 68px;
    bottom: 7px;
    height: 1px;
    border-radius: 999px;
    background: linear-gradient(90deg, transparent, rgba(0,122,255,.62), transparent);
    opacity: 0;
    transform: translateX(-35%);
    pointer-events: none;
    z-index: 1;
  }

  .pjd-chat-input:focus-within {
    border-color: rgba(0,122,255,.52) !important;
    box-shadow:
      0 14px 32px rgba(15,23,42,.07),
      0 0 0 5px rgba(0,122,255,.13) !important;
  }

  .pjd-chat-input:focus-within::before {
    opacity: .55;
    animation: pjdChatHaloIdle 4.8s linear infinite;
  }

  .pjd-chat-input.is-sending {
    border-color: rgba(0,122,255,.72) !important;
    transform: translateY(-1px);
    box-shadow:
      0 18px 42px rgba(15,23,42,.08),
      0 0 0 6px rgba(0,122,255,.15),
      0 0 34px rgba(0,122,255,.16) !important;
  }

  .pjd-chat-input.is-sending::before {
    opacity: 1;
    animation: pjdChatHaloSend .78s cubic-bezier(.22,1,.36,1) both;
  }

  .pjd-chat-input.is-sending::after {
    animation: pjdChatLineSend .62s cubic-bezier(.22,1,.36,1) both;
  }

  .pjd-chat-input input {
    position: relative !important;
    z-index: 2;
    height: 62px !important;
    padding: 0 18px 0 34px !important;
    border: 0 !important;
    background: transparent !important;
    color: #111111 !important;
    font-size: 1.02rem !important;
    font-weight: 500 !important;
    letter-spacing: -.01em;
    box-shadow: none !important;
  }

  .pjd-chat-input input::placeholder {
    color: #7b7f87;
    font-weight: 500;
  }

  .pjd-chat-input input:focus {
    border: 0 !important;
    box-shadow: none !important;
  }

  .pjd-chat-send {
    position: relative !important;
    z-index: 3;
    width: 48px !important;
    height: 48px !important;
    margin: 7px 8px 7px 0 !important;
    border-radius: 999px !important;
    background: #0b0b0d !important;
    color: #ffffff !important;
    box-shadow: 0 10px 24px rgba(0,0,0,.16) !important;
    overflow: hidden;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease !important;
  }

  .pjd-chat-send::before {
    content: "";
    position: absolute;
    inset: -35%;
    background: radial-gradient(circle, rgba(255,255,255,.34), transparent 58%);
    opacity: 0;
    transform: scale(.45);
    pointer-events: none;
  }

  .pjd-chat-send:hover {
    transform: translateY(-1px) scale(1.02) !important;
    box-shadow: 0 14px 30px rgba(0,0,0,.20) !important;
  }

  .pjd-chat-send:active { transform: scale(.96) !important; }
  .pjd-chat-send svg { width: 22px; height: 22px; transform: translateX(1px); }
  .pjd-chat-input.is-sending .pjd-chat-send::before { animation: pjdSendRipple .55s ease-out both; }
  .pjd-chat-input.is-sending .pjd-chat-send svg { animation: pjdSendIconLift .55s cubic-bezier(.22,1,.36,1) both; }

  .pjd-chat-wave {
    position: absolute;
    left: 28px;
    right: 68px;
    top: 50%;
    height: 2px;
    border-radius: 999px;
    background: linear-gradient(90deg, transparent, rgba(0,122,255,.95), rgba(123,180,255,.8), transparent);
    box-shadow: 0 0 18px rgba(0,122,255,.42);
    opacity: 0;
    transform: translateY(-50%) scaleX(.2);
    transform-origin: left center;
    z-index: 1;
    pointer-events: none;
  }

  .pjd-chat-input.is-sending .pjd-chat-wave { animation: pjdChatWaveUp .68s cubic-bezier(.16,1,.3,1) both; }

  .pjd-msg {
    will-change: transform, opacity;
  }

  .pjd-msg.is-user .pjd-msg-body {
    background: #0ea5e9 !important;
    color: #ffffff !important;
    border-color: rgba(14,165,233,.22) !important;
    box-shadow: 0 12px 26px rgba(14,165,233,.18) !important;
  }

  .pjd-msg.is-user.pjd-msg-enter { animation: pjdUserMsgIn .34s cubic-bezier(.22,1,.36,1) both; }
  .pjd-msg.is-assistant.pjd-msg-enter { animation: pjdAssistantMsgIn .38s cubic-bezier(.22,1,.36,1) both; }
  .pjd-msg.is-assistant.pjd-loading-enter { animation: pjdAssistantMsgIn .28s cubic-bezier(.22,1,.36,1) both; }

  @keyframes pjdChatHaloIdle {
    to { --pjd-chat-angle: 540deg; }
  }

  @keyframes pjdChatHaloSend {
    0% { --pjd-chat-angle: 210deg; filter: brightness(1); }
    48% { --pjd-chat-angle: 330deg; filter: brightness(1.2); }
    100% { --pjd-chat-angle: 570deg; filter: brightness(1); }
  }

  @keyframes pjdChatLineSend {
    0% { opacity: 0; transform: translateX(-44%); }
    25% { opacity: .95; }
    100% { opacity: 0; transform: translateX(54%); }
  }

  @keyframes pjdChatWaveUp {
    0% { opacity: 0; transform: translateY(14px) scaleX(.08); }
    20% { opacity: 1; transform: translateY(4px) scaleX(.88); }
    68% { opacity: .92; transform: translateY(-32px) scaleX(.55); }
    100% { opacity: 0; transform: translateY(-78px) scaleX(.08); }
  }

  @keyframes pjdSendRipple {
    0% { opacity: 0; transform: scale(.42); }
    30% { opacity: 1; }
    100% { opacity: 0; transform: scale(1.25); }
  }

  @keyframes pjdSendIconLift {
    0% { transform: translateX(1px) translateY(0) rotate(0); opacity: 1; }
    45% { transform: translateX(6px) translateY(-5px) rotate(-8deg); opacity: .88; }
    100% { transform: translateX(1px) translateY(0) rotate(0); opacity: 1; }
  }

  @keyframes pjdUserMsgIn {
    0% { opacity: 0; transform: translateY(18px) scale(.96); filter: blur(4px); }
    100% { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
  }

  @keyframes pjdAssistantMsgIn {
    0% { opacity: 0; transform: translateY(12px) scale(.985); filter: blur(3px); }
    100% { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
  }

  @media (prefers-reduced-motion: reduce) {
    .pjd-chat-input::before,
    .pjd-chat-input::after,
    .pjd-chat-wave,
    .pjd-msg.is-user.pjd-msg-enter,
    .pjd-msg.is-assistant.pjd-msg-enter,
    .pjd-msg.is-assistant.pjd-loading-enter,
    .pjd-chat-input.is-sending .pjd-chat-send::before,
    .pjd-chat-input.is-sending .pjd-chat-send svg {
      animation: none !important;
    }
  }

  /* ════════════ UX MODALS / TOAST EXIT (ADITIVO, NO REEMPLAZA ESTILOS EXISTENTES) ════════════ */
  .pjd-ui-modal {
    position: fixed;
    inset: 0;
    z-index: 10000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .pjd-ui-modal.is-open { display: flex; }
  .pjd-ui-modal.is-closing { pointer-events: none; }
  .pjd-ui-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15,23,42,.42);
    backdrop-filter: blur(8px);
    animation: pjdModalFade .2s cubic-bezier(.23,1,.32,1) both;
  }
  .pjd-ui-modal.is-closing .pjd-ui-modal-backdrop { animation: pjdModalFadeOut .18s ease both; }
  .pjd-ui-modal-card {
    position: relative;
    z-index: 1;
    width: min(440px, 100%);
    border: 1px solid var(--line);
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 16px 42px rgba(15,23,42,.12);
    overflow: hidden;
    animation: pjdModalCardIn .22s cubic-bezier(.23,1,.32,1) both;
  }
  .pjd-ui-modal.is-closing .pjd-ui-modal-card { animation: pjdModalCardOut .18s ease both; }
  .pjd-ui-modal-head {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 18px 20px 12px;
    background: linear-gradient(180deg, #fafbff, #fff);
  }
  .pjd-ui-modal-icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    background: var(--blue-soft);
    color: var(--blue);
  }
  .pjd-ui-modal-icon.is-danger { background: var(--danger-soft); color: var(--danger); }
  .pjd-ui-modal-icon svg { width: 19px; height: 19px; }
  .pjd-ui-modal-title {
    margin: 0;
    color: var(--ink);
    font-size: 1.02rem;
    font-weight: 700;
    line-height: 1.25;
    letter-spacing: -.01em;
  }
  .pjd-ui-modal-text {
    margin: 5px 0 0;
    color: var(--muted);
    font-size: .88rem;
    font-weight: 600;
    line-height: 1.45;
  }
  .pjd-ui-modal-body { padding: 4px 20px 18px; }
  .pjd-ui-modal-input {
    width: 100%;
    height: 42px;
    border: 1px solid #dfe5ee;
    border-radius: 10px;
    background: #fff;
    color: var(--ink);
    font-family: inherit;
    font-size: .92rem;
    font-weight: 600;
    outline: none;
    padding: 0 12px;
    transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
  }
  .pjd-ui-modal-input:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }
  .pjd-ui-modal-input:active { transform: scale(.995); }
  .pjd-ui-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 20px;
    border-top: 1px solid var(--line);
    background: #fafbff;
  }
  .pjd-ui-modal-btn {
    height: 38px;
    padding: 0 16px;
    border-radius: 999px;
    border: 1px solid var(--line);
    background: #fff;
    color: var(--ink2);
    font-family: inherit;
    font-size: .86rem;
    font-weight: 700;
    cursor: pointer;
    transition: background .16s ease, border-color .16s ease, color .16s ease, transform .16s ease, box-shadow .16s ease;
  }
  .pjd-ui-modal-btn:hover { background: var(--bg); transform: translateY(-1px); }
  .pjd-ui-modal-btn:active { transform: scale(.98); }
  .pjd-ui-modal-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px var(--blue-soft);
  }
  .pjd-ui-modal-btn.is-primary {
    background: var(--blue);
    border-color: var(--blue);
    color: #fff;
    box-shadow: 0 4px 12px rgba(0,122,255,.14);
  }
  .pjd-ui-modal-btn.is-primary:hover { background: #0a84ff; }
  .pjd-ui-modal-btn.is-danger {
    background: var(--danger);
    border-color: var(--danger);
    color: #fff;
    box-shadow: 0 4px 12px rgba(239,68,68,.16);
  }
  .pjd-ui-modal-btn.is-danger:hover { background: #dc2626; }
  .pjd-toast.is-leaving { animation: pjdToastOut .22s ease both; }
  @keyframes pjdModalFade { from { opacity: 0; } to { opacity: 1; } }
  @keyframes pjdModalFadeOut { from { opacity: 1; } to { opacity: 0; } }
  @keyframes pjdModalCardIn { from { opacity: 0; transform: translateY(8px) scale(.96); } to { opacity: 1; transform: translateY(0) scale(1); } }
  @keyframes pjdModalCardOut { from { opacity: 1; transform: translateY(0) scale(1); } to { opacity: 0; transform: translateY(8px) scale(.97); } }
  @keyframes pjdToastOut { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(14px); } }
  @media (prefers-reduced-motion: reduce) {
    .pjd-ui-modal-backdrop,
    .pjd-ui-modal-card,
    .pjd-ui-modal.is-closing .pjd-ui-modal-backdrop,
    .pjd-ui-modal.is-closing .pjd-ui-modal-card,
    .pjd-toast.is-leaving {
      animation: none !important;
    }
  }



  /* ===== ENCLAII PATCH UI/UX ANALISIS ===== */
  .pjd-wrap {
    color: var(--pjd-ink) !important;
    background: var(--pjd-bg) !important;
  }

  .pjd-pane {
    transition: none !important;
  }

  .pjd-tab,
  .pjd-chat-send,
  .pjd-chat-reset,
  .pjd-chat-table-btn,
  .pjd-borrador-tab,
  .pjd-reporte-btn,
  .pjd-cl-add-btn,
  .pjd-cl-export-btn,
  .pjd-cl-action-btn,
  .pjd-cl-detail-link,
  .pjd-doc-action,
  .pjd-source-btn,
  .pjd-chat-send {
    transition-property: background, border-color, color, opacity, transform, box-shadow !important;
    transition-duration: var(--pjd-dur-ui) !important;
    transition-timing-function: var(--pjd-ease) !important;
  }

  .pjd-tab:active,
  .pjd-chat-send:active,
  .pjd-chat-reset:active,
  .pjd-chat-table-btn:active,
  .pjd-borrador-tab:active,
  .pjd-reporte-btn:active,
  .pjd-cl-add-btn:active,
  .pjd-cl-export-btn:active,
  .pjd-cl-action-btn:active,
  .pjd-cl-detail-link:active,
  .pjd-doc-action:active,
  .pjd-source-btn:active {
    transform: scale(.97) !important;
    transition-duration: var(--pjd-dur-press) !important;
  }

  .pjd-field-label,
  .pjd-card-sub,
  .pjd-md-p,
  .pjd-msg-body,
  .pjd-cl-muted,
  .pjd-cl-detail-empty,
  .pjd-date-sub,
  .pjd-doc-meta,
  .pjd-source-meta,
  .pjd-source-quote {
    color: var(--pjd-ink-soft) !important;
  }

  .pjd-field-value,
  .pjd-pane-title,
  .pjd-card-head h3,
  .pjd-title,
  .pjd-msg-body strong,
  .pjd-cl-title,
  .pjd-cl-name,
  .pjd-doc-title {
    color: var(--pjd-ink) !important;
  }

  .pjd-card,
  .pjd-msg-body,
  .pjd-chat-table-wrap,
  .pjd-source-card,
  .pjd-doc-drawer-quote-text,
  .pjd-cita-quote {
    border: 1px solid var(--pjd-border) !important;
    box-shadow: 0 4px 12px rgba(0,0,0,.02) !important;
  }

  .pjd-card-head {
    background: #fbfcfe !important;
    border-bottom: 1px solid var(--pjd-border) !important;
  }

  .pjd-field {
    border-bottom: 1px solid var(--pjd-border) !important;
    padding: 10px 0 !important;
  }

  .pjd-field-value:empty::after,
  .pjd-field-value.is-empty::after {
    content: "Sin dato";
    color: var(--pjd-ink-soft);
  }

  .pjd-ai-notice {
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid var(--pjd-border);
    border-radius: 12px;
    background: var(--pjd-bg);
    color: var(--pjd-ink-soft);
    padding: 12px 14px;
    margin: 10px 0 12px;
    font-size: .86rem;
    font-weight: 600;
  }

  .pjd-svg {
    width: 16px;
    height: 16px;
    display: block;
    flex-shrink: 0;
  }

  .pjd-cita-badge {
    color: var(--pjd-blue) !important;
    background: #e6f0ff !important;
    border: 1px solid #cfe1ff !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
  }

  .pjd-status-pill.is-processing,
  .pjd-doc-status.is-pending,
  .pjd-cl-badge.is-pending,
  .pjd-cl-status.is-pending,
  .pjd-badge.is-pending {
    color: var(--pjd-pending) !important;
    background: var(--pjd-pending-bg) !important;
    border-color: #f1dca5 !important;
  }

  .pjd-status-pill.is-ready,
  .pjd-doc-status.is-ready,
  .pjd-doc-status.is-completed,
  .pjd-cl-badge.is-ok,
  .pjd-cl-status.is-ok {
    color: var(--pjd-ok) !important;
    background: var(--pjd-ok-bg) !important;
    border-color: #bfe8d5 !important;
  }

  .pjd-status-pill.is-error,
  .pjd-doc-status.is-error,
  .pjd-cl-badge.is-danger,
  .pjd-cl-status.is-danger {
    color: var(--pjd-danger) !important;
    background: var(--pjd-danger-bg) !important;
    border-color: #f3c3bc !important;
  }

  .pjd-chat-input input,
  .pjd-cl-detail-date,
  .pjd-cl-detail-select,
  .pjd-draft-title-input,
  .pjd-doc-search-input,
  .pjd-cl-filter,
  .pjd-control {
    min-height: 42px !important;
    border: 1px solid var(--pjd-border) !important;
    border-radius: 10px !important;
    background: var(--pjd-surface) !important;
    color: var(--pjd-ink) !important;
    font-weight: 600 !important;
  }

  .pjd-chat-input input:focus,
  .pjd-cl-detail-date:focus,
  .pjd-cl-detail-select:focus,
  .pjd-draft-title-input:focus,
  .pjd-doc-search-input:focus,
  .pjd-cl-filter:focus,
  .pjd-control:focus {
    border-color: var(--pjd-blue) !important;
    box-shadow: 0 0 0 3px #e6f0ff !important;
    outline: none !important;
  }

  .pjd-cl-detail-controls {
    display: grid !important;
    gap: 10px !important;
  }

  .pjd-cl-detail-control-row {
    display: grid !important;
    grid-template-columns: 160px minmax(0, 1fr) !important;
    gap: 10px !important;
    align-items: center !important;
  }

  .pjd-step-check,
  .pjd-step-circle svg {
    transition-property: opacity !important;
    transition-duration: var(--pjd-dur-ui) !important;
    transition-timing-function: var(--pjd-ease) !important;
  }

  @media (hover:hover) and (pointer:fine) {
    .pjd-tab:hover,
    .pjd-chat-reset:hover,
    .pjd-chat-table-btn:hover,
    .pjd-borrador-tab:hover,
    .pjd-source-btn:hover,
    .pjd-doc-action:hover,
    .pjd-cl-action-btn:hover {
      background: var(--pjd-bg) !important;
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .pjd-tab,
    .pjd-chat-send,
    .pjd-chat-reset,
    .pjd-chat-table-btn,
    .pjd-borrador-tab,
    .pjd-reporte-btn,
    .pjd-cl-add-btn,
    .pjd-cl-export-btn,
    .pjd-cl-action-btn,
    .pjd-cl-detail-link,
    .pjd-doc-action,
    .pjd-source-btn,
    .pjd-step-check,
    .pjd-step-circle svg {
      transition-duration: 0ms !important;
      animation-duration: 0ms !important;
    }

    .pjd-tab:active,
    .pjd-chat-send:active,
    .pjd-chat-reset:active,
    .pjd-chat-table-btn:active,
    .pjd-borrador-tab:active,
    .pjd-reporte-btn:active,
    .pjd-cl-add-btn:active,
    .pjd-cl-export-btn:active,
    .pjd-cl-action-btn:active,
    .pjd-cl-detail-link:active,
    .pjd-doc-action:active,
    .pjd-source-btn:active {
      transform: none !important;
    }
  }



  /* FICHA: estilos movidos a resources/views/projects/partials/analysis/ficha.blade.php */


</style>
@endpush
@endonce
