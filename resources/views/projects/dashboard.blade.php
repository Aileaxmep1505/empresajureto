@extends('layouts.app')

@section('title', $project->name)

@push('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  :root {
    --bg: #f9fafb; --card: #ffffff; --ink: #111; --ink2: #333; --muted: #888;
    --line: #ebebeb; --blue: #007aff; --blue-soft: #e6f0ff;
    --success: #15803d; --success-soft: #e6ffe6;
    --danger: #ef4444; --danger-soft: #ffebeb;
    --warning: #b45309; --warning-soft: #fef9c3;
    --orange: #ea580c; --orange-soft: #ffedd5;
    --violet: #7c3aed; --violet-soft: #ede9fe;
  }

  body { font-family: 'Quicksand', sans-serif; background: var(--bg); color: var(--ink2); }

  .pdb-wrap { max-width: 1700px; margin: 0 auto; padding: 16px 24px 60px; }

  /* ═══════ NAVBAR SUPERIOR ═══════ */
  .pdb-navbar { display: flex; align-items: center; gap: 12px; padding: 12px 22px; background: #fff; border: 1px solid var(--line); border-radius: 14px; margin-bottom: 16px; flex-wrap: wrap; }
  .pdb-nav-back { color: var(--muted); text-decoration: none; font-size: 1.05rem; padding: 6px 8px; border-radius: 8px; }
  .pdb-nav-back:hover { background: var(--bg); color: var(--blue); }
  .pdb-nav-name { font-size: 1rem; font-weight: 700; color: var(--ink); margin: 0 6px 0 0; }
  .pdb-nav-name-dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: var(--blue); margin-left: 8px; vertical-align: middle; }

  .pdb-nav-pill { display: inline-flex; align-items: center; gap: 7px; padding: 7px 14px; border-radius: 999px; background: var(--blue-soft); color: var(--blue); font-weight: 700; font-size: .85rem; border: 1px solid #c7dcfd; cursor: pointer; font-family: inherit; }
  .pdb-nav-pill::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: var(--blue); display: inline-block; }

  .pdb-nav-tabs { display: inline-flex; gap: 4px; }
  .pdb-nav-tab { display: inline-flex; align-items: center; gap: 7px; padding: 8px 16px; border-radius: 999px; background: transparent; color: var(--ink2); font-weight: 700; font-size: .9rem; border: none; cursor: pointer; font-family: inherit; text-decoration: none; }
  .pdb-nav-tab:hover { background: var(--bg); color: var(--blue); }
  .pdb-nav-tab.is-active { background: var(--blue); color: #fff; box-shadow: 0 6px 14px rgba(0,122,255,.25); }
  .pdb-nav-tab svg { width: 16px; height: 16px; }

  /* ═══════ HERO UNIFICADO: TOPBAR + MÓDULO/CHECKLIST ═══════ */
  .pdb-hero { background: #fff; border: 1px solid var(--line); border-radius: 14px; margin-bottom: 16px; overflow: hidden; }
  .pdb-hero-top { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; padding: 18px 22px; border-bottom: 1px solid var(--line); }
  .pdb-hero-grid { display: grid; grid-template-columns: 1fr 1fr; }
  .pdb-hero-col { padding: 20px 22px; min-width: 0; }
  .pdb-hero-col + .pdb-hero-col { border-left: 1px solid var(--line); }
  @media (max-width: 1000px) {
    .pdb-hero-grid { grid-template-columns: 1fr; }
    .pdb-hero-col + .pdb-hero-col { border-left: none; border-top: 1px solid var(--line); }
  }

  .pdb-estado-label { font-size: .82rem; color: var(--muted); display: inline-flex; align-items: center; gap: 6px; font-weight: 600; }
  .pdb-estado-label::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: var(--success); display: inline-block; }

  .pdb-estado-chip { padding: 6px 14px; border-radius: 999px; font-weight: 700; font-size: .85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-family: inherit; }
  .pdb-estado-chip::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
  .pdb-estado-chip::after { content: "⌄"; font-size: .9rem; margin-left: 2px; }
  .pdb-estado-chip.tone-blue   { background: var(--blue-soft);    color: var(--blue);    border: 1px solid #c7dcfd; }
  .pdb-estado-chip.tone-orange { background: var(--orange-soft);  color: var(--orange);  border: 1px solid #fed7aa; }
  .pdb-estado-chip.tone-green  { background: var(--success-soft); color: var(--success); border: 1px solid #bbf7d0; }
  .pdb-estado-chip.tone-red    { background: var(--danger-soft);  color: var(--danger);  border: 1px solid #fecaca; }
  .pdb-estado-chip.tone-violet { background: var(--violet-soft);  color: var(--violet);  border: 1px solid #ddd6fe; }
  .pdb-estado-chip.tone-gray   { background: #f3f4f6;             color: #64748b;       border: 1px solid #e5e7eb; }

  .pdb-status-wrap { position: relative; display: inline-flex; align-items: center; }
  .pdb-status-menu { position: absolute; top: calc(100% + 8px); left: 0; z-index: 80; width: 286px; padding: 8px; border: 1px solid var(--line); border-radius: 12px; background: #fff; box-shadow: 0 18px 46px rgba(15,23,42,.12); display: none; animation: pdbMenuIn .16s ease both; }
  .pdb-status-wrap.is-open .pdb-status-menu { display: block; }
  @keyframes pdbMenuIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
  .pdb-status-option { position: relative; width: 100%; display: flex; align-items: center; gap: 10px; border: 0; background: transparent; padding: 10px 12px; border-radius: 9px; font-family: inherit; font-size: .86rem; font-weight: 700; color: #202735; cursor: pointer; text-align: left; transition: background .16s ease, color .16s ease, transform .16s ease; }
  .pdb-status-option:hover { background: #f7f9fc; transform: translateY(-1px); }
  .pdb-status-option.is-active { background: var(--blue-soft); color: var(--blue); }
  .pdb-status-option.is-child { padding-left: 34px; color: #6b7280; font-weight: 600; }
  .pdb-status-option.is-child::before { content: ""; position: absolute; left: 21px; top: -8px; width: 1px; height: 28px; background: #bdf6dc; }
  .pdb-status-dot { width: 8px; height: 8px; border-radius: 999px; flex-shrink: 0; background: var(--blue); }
  .pdb-status-check { margin-left: auto; color: var(--blue); font-size: .82rem; opacity: 0; }
  .pdb-status-option.is-active .pdb-status-check { opacity: 1; }
  .pdb-status-saving { pointer-events: none; opacity: .72; }
  .pdb-status-saving::after { content: ""; width: 11px; height: 11px; border: 2px solid currentColor; border-right-color: transparent; border-radius: 50%; animation: pdbSpin .65s linear infinite; }
  @keyframes pdbSpin { to { transform: rotate(360deg); } }

  /* ─── Pipeline ─── */
  .pdb-pipeline { flex: 1; display: flex; align-items: center; justify-content: center; gap: 0; padding: 0 30px; min-width: 380px; }
  .pdb-step { display: flex; flex-direction: column; align-items: center; gap: 6px; min-width: 80px; }
  .pdb-step-circle { width: 44px; height: 44px; border-radius: 50%; background: #f3f4f6; color: var(--muted); display: grid; place-items: center; border: 2px solid var(--line); transition: background var(--pjd-dur-ui) var(--pjd-ease), border-color var(--pjd-dur-ui) var(--pjd-ease), transform var(--pjd-dur-ui) var(--pjd-ease), color var(--pjd-dur-ui) var(--pjd-ease); }
  .pdb-step-circle svg { width: 20px; height: 20px; }
  .pdb-step-label { font-size: .78rem; font-weight: 700; color: var(--muted); }

  .pdb-step.is-on.tone-blue   .pdb-step-circle { background: var(--blue);   color: #fff; border-color: var(--blue);   box-shadow: 0 6px 16px rgba(0,122,255,.25); }
  .pdb-step.is-on.tone-orange .pdb-step-circle { background: #f59e0b;       color: #fff; border-color: #f59e0b;       box-shadow: 0 6px 16px rgba(245,158,11,.25); }
  .pdb-step.is-on.tone-green  .pdb-step-circle { background: #10b981;       color: #fff; border-color: #10b981;       box-shadow: 0 6px 16px rgba(16,185,129,.25); }
  .pdb-step.is-on.tone-red    .pdb-step-circle { background: var(--danger); color: #fff; border-color: var(--danger); box-shadow: 0 6px 16px rgba(239,68,68,.18); }
  .pdb-step.is-on.tone-violet .pdb-step-circle { background: var(--violet); color: #fff; border-color: var(--violet); box-shadow: 0 6px 16px rgba(124,58,237,.18); }
  .pdb-step.is-on.tone-gray   .pdb-step-circle { background: #64748b;       color: #fff; border-color: #64748b;       box-shadow: 0 6px 16px rgba(100,116,139,.18); }
  .pdb-step.is-on.tone-blue   .pdb-step-label  { color: var(--blue); }
  .pdb-step.is-on.tone-orange .pdb-step-label  { color: #d97706; }
  .pdb-step.is-on.tone-green  .pdb-step-label  { color: #059669; }
  .pdb-step.is-on.tone-red    .pdb-step-label  { color: var(--danger); }
  .pdb-step.is-on.tone-violet .pdb-step-label  { color: var(--violet); }
  .pdb-step.is-on.tone-gray   .pdb-step-label  { color: #64748b; }
  .pdb-step.is-ring .pdb-step-circle { box-shadow: 0 0 0 6px #d1fae5, 0 6px 16px rgba(16,185,129,.25); }

  .pdb-step-line { width: 80px; height: 2px; background: var(--line); margin-bottom: 22px; transition: background .2s; }
  .pdb-step-line.is-on.tone-blue  { background: var(--blue); }
  .pdb-step-line.is-on.tone-green { background: #10b981; }

  .pdb-ask-monico { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 999px; background: #fff; border: 1.5px solid #d8e7ff; color: var(--blue); font-weight: 700; font-size: .88rem; cursor: pointer; box-shadow: 0 4px 10px rgba(0,122,255,.08); font-family: inherit; }
  .pdb-ask-monico:hover { background: var(--blue-soft); border-color: var(--blue); }
  .pdb-collapse-btn { background: transparent; border: none; color: var(--muted); cursor: pointer; padding: 4px 8px; border-radius: 6px; font-size: 1rem; }
  .pdb-collapse-btn:hover { background: var(--bg); color: var(--blue); }

  /* ═══════ GRID ═══════ */
  .pdb-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
  @media (max-width: 1000px) { .pdb-grid { grid-template-columns: 1fr; } }

  .pdb-card { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 20px 22px; }
  .pdb-card-title { font-size: .98rem; font-weight: 700; color: var(--ink); margin: 0 0 16px; display: inline-flex; align-items: center; gap: 8px; }
  .pdb-card-title .ico { width: 26px; height: 26px; border-radius: 7px; background: var(--blue-soft); color: var(--blue); display: grid; place-items: center; font-size: .9rem; }
  .pdb-card-title .ico.is-orange  { background: var(--orange-soft);  color: var(--orange);  }
  .pdb-card-title .ico.is-success { background: var(--success-soft); color: var(--success); }
  .pdb-card-title .ico.is-violet  { background: var(--violet-soft);  color: var(--violet);  }
  .pdb-card-title .ico.is-warning { background: var(--warning-soft); color: var(--warning); }
  .pdb-card-title .sparkle { color: var(--violet); }
  .pdb-card-sub { font-size: .82rem; color: var(--muted); font-weight: 600; margin-top: 2px; }

  /* ═══════ MÓDULOS ═══════ */
  .pdb-module { display: flex; align-items: center; gap: 14px; padding: 14px 16px; border-radius: 12px; cursor: pointer; transition: background var(--pjd-dur-ui) var(--pjd-ease), border-color var(--pjd-dur-ui) var(--pjd-ease), transform var(--pjd-dur-ui) var(--pjd-ease), color var(--pjd-dur-ui) var(--pjd-ease); text-decoration: none; color: inherit; margin-bottom: 8px; border: 1.5px solid transparent; }
  .pdb-module:hover { background: var(--bg); }
  .pdb-module.is-current { background: linear-gradient(180deg, #f0f9ff, #fff); border-color: #bae6fd; }
  .pdb-module.is-disabled { opacity: .45; cursor: pointer; pointer-events: auto; }
  .pdb-module.is-disabled:hover { opacity: .78; background: #f9fafb; border-color: var(--line); }
  .pdb-module-icon { width: 42px; height: 42px; border-radius: 10px; display: grid; place-items: center; flex-shrink: 0; }
  .pdb-module-icon.tone-green { background: var(--success-soft); color: var(--success); }
  .pdb-module-icon.tone-blue  { background: var(--blue-soft);    color: var(--blue);    }
  .pdb-module-icon.tone-orange{ background: var(--orange-soft);  color: var(--orange);  }
  .pdb-module-icon.tone-violet{ background: var(--violet-soft);  color: var(--violet);  }
  .pdb-module-icon.tone-warn  { background: var(--warning-soft); color: var(--warning); }
  .pdb-module-icon.tone-gray  { background: #f3f4f6;             color: var(--muted);   }
  .pdb-module-text { flex: 1; }
  .pdb-module-name { font-size: .94rem; font-weight: 700; color: var(--ink); }
  .pdb-module.is-current .pdb-module-name { color: var(--blue); }
  .pdb-module-desc { font-size: .8rem; color: var(--muted); margin-top: 2px; }
  .pdb-module-chev { color: var(--muted); font-size: 1.2rem; }

  /* ═══════ CHECKLIST STATS ═══════ */
  .pdb-chk-link { margin-left: auto; color: var(--muted); text-decoration: none; padding: 4px 6px; border-radius: 6px; }
  .pdb-chk-link:hover { color: var(--blue); background: var(--bg); }
  .pdb-chk-head { display: flex; align-items: baseline; gap: 8px; margin-bottom: 14px; }
  .pdb-chk-head .badge-count { font-size: .82rem; color: var(--muted); font-weight: 600; }
  .pdb-chk-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; border: 1px solid var(--line); border-radius: 12px; overflow: hidden; }
  @media (max-width: 700px) { .pdb-chk-grid { grid-template-columns: 1fr; } }
  .pdb-chk-col { padding: 16px 18px; }
  .pdb-chk-col + .pdb-chk-col { border-left: 1px solid var(--line); }
  @media (max-width: 700px) { .pdb-chk-col + .pdb-chk-col { border-left: none; border-top: 1px solid var(--line); } }
  .pdb-chk-row { display: flex; align-items: center; gap: 8px; padding: 7px 0; font-size: .9rem; font-weight: 600; color: var(--ink2); }
  .pdb-chk-row .num { font-weight: 700; min-width: 26px; }
  .pdb-chk-row .lbl { flex: 1; }
  .pdb-chk-row .pct { font-weight: 700; color: var(--blue); }
  .pdb-chk-row.is-main { font-size: .98rem; color: var(--ink); }
  .pdb-chk-row.is-main .num { font-weight: 700; }
  .pdb-chk-row .num.c-gray   { color: var(--ink); }
  .pdb-chk-row .num.c-red    { color: var(--danger); }
  .pdb-chk-row .num.c-orange { color: var(--orange); }
  .pdb-chk-row .num.c-green  { color: var(--success); }
  .pdb-chk-bar { height: 5px; border-radius: 999px; background: #e5e7eb; margin: 4px 0 10px; overflow: hidden; }
  .pdb-chk-bar i { display: block; height: 100%; background: #9ca3af; border-radius: 999px; }
  .pdb-chk-total { display: flex; justify-content: space-between; align-items: center; padding: 12px 18px; border-top: 1px solid var(--line); font-size: .88rem; color: var(--muted); font-weight: 600; grid-column: 1 / -1; }
  .pdb-chk-total strong { color: var(--ink); font-size: 1rem; }

  /* ═══════ INSIGHTS VACÍO ═══════ */
  .pdb-insights-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 240px; gap: 14px; text-align: center; padding: 20px; }
  .pdb-insights-empty p { margin: 0; font-size: .92rem; color: var(--muted); font-weight: 600; }
  .pdb-insights-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 999px; background: var(--blue); color: #fff; border: none; font-weight: 700; font-size: .88rem; cursor: pointer; font-family: inherit; box-shadow: 0 6px 14px rgba(0,122,255,.22); }
  .pdb-insights-btn:hover { transform: translateY(-1px); }

  /* ═══════ NOTAS / TAREAS ═══════ */
  .pdb-list-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
  .pdb-list-title { font-size: .98rem; font-weight: 700; color: var(--ink); display: inline-flex; align-items: flex-start; gap: 8px; }
  .pdb-list-title .ico { width: 26px; height: 26px; border-radius: 7px; display: grid; place-items: center; flex-shrink: 0; }
  .pdb-list-title .ico.is-warning { background: var(--warning-soft); color: var(--warning); }
  .pdb-list-title .ico.is-success { background: var(--success-soft); color: var(--success); }
  .pdb-list-title-text { display: flex; flex-direction: column; }
  .pdb-list-empty { min-height: 200px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 8px; padding: 20px; color: var(--muted); }
  .pdb-list-empty .ico-empty { width: 48px; height: 48px; border-radius: 12px; background: var(--bg); display: grid; place-items: center; margin-bottom: 6px; color: #bbb; font-size: 1.2rem; }
  .pdb-list-empty .lbl { font-size: .92rem; font-weight: 700; color: var(--ink2); }
  .pdb-list-empty .sub { font-size: .8rem; }
  .pdb-list-input { display: flex; gap: 8px; margin-top: 12px; }
  .pdb-list-input input { flex: 1; border: 1px solid var(--line); border-radius: 999px; padding: 9px 16px; font-family: inherit; font-size: .88rem; outline: none; }
  .pdb-list-input input:focus { border-color: var(--blue); }
  .pdb-list-input .add-btn { width: 38px; height: 38px; background: var(--blue); color: #fff; border: none; border-radius: 999px; cursor: pointer; display: grid; place-items: center; font-size: 1.2rem; line-height: 1; }
  .pdb-list-input .add-btn:hover { filter: brightness(1.08); }
  .pdb-filter-btn { background: var(--bg); border: 1px solid var(--line); padding: 5px 12px; border-radius: 8px; font-size: .78rem; font-weight: 700; color: var(--ink2); cursor: pointer; display: inline-flex; align-items: center; gap: 4px; font-family: inherit; }
  .pdb-filter-btn:hover { background: var(--blue-soft); color: var(--blue); }
  .pdb-sort-btn { background: transparent; border: none; color: var(--muted); cursor: pointer; padding: 4px; border-radius: 6px; font-family: inherit; }
  .pdb-sort-btn:hover { background: var(--bg); color: var(--blue); }

  /* ─── Nota individual ─── */
  .pdb-note { border: 1px solid var(--line); border-radius: 12px; padding: 14px 16px; margin-bottom: 10px; }
  .pdb-note-head { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
  .pdb-note-avatar { width: 32px; height: 32px; border-radius: 50%; background: #f3f4f6; color: var(--ink2); display: grid; place-items: center; font-weight: 700; font-size: .82rem; flex-shrink: 0; }
  .pdb-note-author { font-weight: 700; color: var(--ink); font-size: .92rem; }
  .pdb-note-date { font-size: .8rem; color: var(--muted); display: inline-flex; align-items: center; gap: 4px; }
  .pdb-note-menu { margin-left: auto; background: transparent; border: none; color: var(--muted); cursor: pointer; padding: 4px 8px; border-radius: 6px; font-size: 1rem; }
  .pdb-note-menu:hover { background: var(--bg); }
  .pdb-note-body { font-size: .9rem; color: var(--ink2); font-weight: 600; }
  .pdb-notes-scroll { max-height: 280px; overflow-y: auto; }

  /* ═══════ RESUMEN DOCUMENTOS (acordeón) ═══════ */
  .pdb-doc-group { margin-bottom: 8px; border-radius: 12px; overflow: hidden; }
  .pdb-doc-group-head { display: flex; align-items: center; gap: 10px; padding: 12px 14px; cursor: pointer; transition: filter .15s; }
  .pdb-doc-group-head:hover { filter: brightness(.97); }
  .pdb-doc-group.tone-blue  .pdb-doc-group-head { background: var(--blue-soft);    color: var(--blue);    }
  .pdb-doc-group.tone-green .pdb-doc-group-head { background: var(--success-soft); color: var(--success); }
  .pdb-doc-group.tone-red   .pdb-doc-group-head { background: var(--danger-soft);  color: var(--danger);  }
  .pdb-doc-group-head .doc-ico { display: grid; place-items: center; }
  .pdb-doc-group-head .doc-name { flex: 1; font-weight: 700; font-size: .92rem; }
  .pdb-doc-group-head .doc-count { font-weight: 700; font-size: .92rem; }
  .pdb-doc-group-head .doc-chev { font-size: .85rem; opacity: .65; transition: transform .2s; }
  .pdb-doc-group.is-open .pdb-doc-group-head .doc-chev { transform: rotate(180deg); }

  .pdb-doc-group-body { display: none; padding: 8px 8px 4px; }
  .pdb-doc-group.is-open .pdb-doc-group-body { display: block; }

  .pdb-doc-file { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid var(--line); border-radius: 10px; background: #fff; margin-bottom: 6px; }
  .pdb-doc-file .file-ico { width: 26px; height: 26px; border-radius: 7px; background: var(--bg); display: grid; place-items: center; flex-shrink: 0; color: var(--muted); }
  .pdb-doc-file .file-info { flex: 1; min-width: 0; }
  .pdb-doc-file .file-name { font-size: .88rem; font-weight: 700; color: var(--ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .pdb-doc-file .file-status { font-size: .78rem; font-weight: 700; color: var(--success); margin-top: 1px; }
  .pdb-doc-file .file-status.is-pending { color: var(--warning); }
  .pdb-doc-file .file-status.is-error   { color: var(--danger);  }
  .pdb-doc-file .file-open { color: var(--muted); padding: 4px; border-radius: 6px; text-decoration: none; }
  .pdb-doc-file .file-open:hover { color: var(--blue); background: var(--bg); }

  .pdb-doc-total { display: flex; align-items: center; justify-content: space-between; padding: 14px 4px 0; border-top: 1px solid var(--line); margin-top: 12px; font-size: .88rem; color: var(--muted); font-weight: 600; }
  .pdb-doc-total strong { color: var(--ink); font-size: 1.1rem; }

  /* ═══════ INFO GENERAL ═══════ */
  .pdb-info-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 14px 0; border-bottom: 1px solid var(--line); }
  .pdb-info-row:last-child { border-bottom: none; }
  .pdb-info-lbl { font-size: .88rem; color: var(--ink2); font-weight: 600; }
  .pdb-info-val { font-size: .9rem; color: var(--ink); font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
  .pdb-info-val .ico-edit { color: var(--muted); cursor: pointer; font-size: .85rem; }
  .pdb-info-val .ico-edit:hover { color: var(--blue); }
  .pdb-avatar { width: 22px; height: 22px; border-radius: 50%; background: var(--blue); color: #fff; display: inline-grid; place-items: center; font-weight: 700; font-size: .72rem; }
  .pdb-tags-shell { position: relative; width: 100%; }
  .pdb-tags { display: flex; gap: 7px; flex-wrap: wrap; align-items: center; }
  .pdb-tag-pill,
  .pdb-tag-add {
    min-height: 28px;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: .78rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-family: inherit;
    transition: background var(--pjd-dur-ui) var(--pjd-ease), border-color var(--pjd-dur-ui) var(--pjd-ease), transform var(--pjd-dur-ui) var(--pjd-ease), color var(--pjd-dur-ui) var(--pjd-ease);
  }
  .pdb-tag-pill { color: var(--blue); border: 1px solid #bdd6ff; background: var(--blue-soft); }
  .pdb-tag-pill button { width: 17px; height: 17px; border: 0; border-radius: 999px; background: rgba(0,122,255,.10); color: var(--blue); cursor: pointer; display: grid; place-items: center; font-size: .8rem; line-height: 1; padding: 0; }
  .pdb-tag-pill button:hover { background: rgba(0,122,255,.18); }
  .pdb-tag-add { border: 1.5px dashed #b6b6b6; color: #777; cursor: pointer; background: transparent; }
  .pdb-tag-add:hover { color: var(--blue); border-color: #a8ccff; background: #f8fbff; transform: translateY(-1px); }
  .pdb-tags-popover {
    position: absolute;
    top: calc(100% + 10px);
    left: 0;
    z-index: 90;
    width: min(410px, calc(100vw - 56px));
    padding: 12px;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 18px 46px rgba(15,23,42,.13);
    display: none;
    animation: pdbMenuIn .16s ease both;
  }
  .pdb-tags-shell.is-open .pdb-tags-popover { display: block; }
  .pdb-tags-search { height: 48px; display: flex; align-items: center; gap: 10px; border: 1px solid #e2e5ea; border-radius: 12px; padding: 0 14px; background: #fff; transition: border-color .18s ease, box-shadow .18s ease; }
  .pdb-tags-search:focus-within { border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft); }
  .pdb-tags-search svg { width: 18px; height: 18px; color: #777; flex-shrink: 0; }
  .pdb-tags-search input { width: 100%; height: 100%; border: 0; outline: none; background: transparent; font: inherit; color: var(--ink); font-size: .92rem; font-weight: 600; }
  .pdb-tags-sep { height: 1px; background: var(--line); margin: 12px 4px; }
  .pdb-tags-title { margin: 0 2px 10px; font-size: .82rem; font-weight: 700; color: var(--muted); }
  .pdb-common-tags { display: flex; gap: 8px; flex-wrap: wrap; }
  .pdb-common-tag,
  .pdb-tag-create {
    border: 1px solid #d7e5ff;
    background: #fff;
    color: var(--blue);
    border-radius: 999px;
    min-height: 30px;
    padding: 5px 12px;
    font-family: inherit;
    font-size: .82rem;
    font-weight: 700;
    cursor: pointer;
    transition: background var(--pjd-dur-ui) var(--pjd-ease), border-color var(--pjd-dur-ui) var(--pjd-ease), transform var(--pjd-dur-ui) var(--pjd-ease), color var(--pjd-dur-ui) var(--pjd-ease);
  }
  .pdb-common-tag:hover,
  .pdb-tag-create:hover { background: var(--blue-soft); border-color: #a8ccff; transform: translateY(-1px); }
  .pdb-common-tag.is-selected { background: var(--blue); border-color: var(--blue); color: #fff; }
  .pdb-tag-create-row { display: none; align-items: center; gap: 10px; padding: 6px 4px 2px; color: var(--ink); font-size: .92rem; font-weight: 600; }
  .pdb-tag-create-row.is-visible { display: flex; }
  .pdb-tag-create-row .plus { color: var(--blue); font-size: 1.05rem; font-weight: 700; }
  .pdb-tag-preview { display: inline-flex; align-items: center; min-height: 27px; padding: 3px 12px; border-radius: 999px; background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; font-weight: 700; }
  .pdb-tags-saving { opacity: .6; pointer-events: none; }

  /* ═══════ FICHA TÉCNICA + FECHAS CLAVE estilo referencia ═══════ */
  .pdb-ref-card {
    padding: 0;
    overflow: hidden;
    border-radius: 16px;
    background: #fff;
  }

  .pdb-ref-head {
    min-height: 102px;
    padding: 24px 24px;
    border-bottom: 1px solid var(--line);
    display: flex;
    align-items: center;
    gap: 18px;
  }

  .pdb-ref-title {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 14px;
    color: #111;
    font-size: 1.18rem;
    font-weight: 700;
  }

  .pdb-ref-title .ico {
    width: 54px;
    height: 54px;
    border-radius: 12px;
    background: var(--blue-soft);
    color: var(--blue);
    display: grid;
    place-items: center;
    flex: 0 0 auto;
  }

  .pdb-ref-title .ico.is-success {
    background: var(--blue-soft);
    color: var(--blue);
  }

  .pdb-ref-sparkle {
    color: var(--violet);
    display: inline-grid;
    place-items: center;
  }

  .pdb-ref-open {
    margin-left: auto;
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 9px;
    display: grid;
    place-items: center;
    color: #777;
    text-decoration: none;
    transition: background .16s ease, color .16s ease, transform .16s ease;
  }

  .pdb-ref-open:hover {
    background: #f9fafb;
    color: var(--blue);
    transform: translateY(-1px);
  }

  .pdb-ref-body {
    padding: 16px 24px 24px;
  }

  .pdb-field {
    padding: 0;
    border-bottom: 0;
    margin-bottom: 24px;
  }

  .pdb-field:last-child {
    margin-bottom: 0;
  }

  .pdb-field-lbl {
    margin: 0 0 12px;
    color: #777;
    font-size: .92rem;
    font-weight: 600;
  }

  .pdb-field-val {
    min-height: 68px;
    border: 1px solid #dedede;
    border-radius: 9px;
    background: #fbfbfb;
    color: #222;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    font-size: 1.05rem;
    font-weight: 600;
  }

  .pdb-ext-link {
    color: var(--muted);
    cursor: pointer;
    padding: 4px 6px;
    border-radius: 6px;
    text-decoration: none;
    font-size: .85rem;
  }

  .pdb-ext-link:hover {
    color: var(--blue);
    background: var(--bg);
  }

  .pdb-date-list {
    padding: 0 24px;
  }

  .pdb-date-row {
    min-height: 102px;
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 22px 0;
    border-bottom: 1px solid var(--line);
  }

  .pdb-date-row:last-child {
    border-bottom: none;
  }

  .pdb-date-row.is-near {
    background: transparent;
    margin: 0;
    padding: 22px 0;
    border-radius: 0;
  }

  .pdb-date-ico {
    width: 54px;
    height: 54px;
    border-radius: 999px;
    display: grid;
    place-items: center;
    flex: 0 0 54px;
    background: #fff;
    color: #667085;
    border: 3px solid #e5e7eb;
    box-shadow: 0 1px 0 rgba(0,0,0,.02);
  }

  .pdb-date-ico.is-orange,
  .pdb-date-ico.is-green,
  .pdb-date-ico.is-warning {
    background: #fff;
    color: #667085;
  }

  .pdb-date-body {
    flex: 1;
    min-width: 0;
  }

  .pdb-date-title {
    margin: 0 0 5px;
    color: #222;
    font-size: 1rem;
    font-weight: 700;
  }

  .pdb-date-sub {
    color: #777;
    font-size: .92rem;
    font-weight: 600;
    line-height: 1.45;
    margin: 0;
  }

  .pdb-date-badge {
    margin-left: auto;
    min-width: 112px;
    justify-content: flex-end;
    padding: 0;
    border-radius: 0;
    background: transparent !important;
    color: #4b5563 !important;
    font-size: 1rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .pdb-date-empty {
    margin-left: auto;
    color: #777;
    font-size: 1.12rem;
    font-weight: 700;
  }

  .pdb-svg {
    width: 18px;
    height: 18px;
    display: block;
  }

  .pdb-svg.sm {
    width: 16px;
    height: 16px;
  }

  .pdb-ref-title .pdb-svg {
    width: 25px;
    height: 25px;
  }

  .pdb-date-ico .pdb-svg {
    width: 22px;
    height: 22px;
  }

  /* ════════════ DICTAMEN NO PARTICIPA ════════════ */
  .pdb-decline-panel {
    min-height: 100%;
  }
  .pdb-decline-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
  }
  .pdb-decline-title {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    color: #111111;
    font-size: .98rem;
    font-weight: 700;
  }
  .pdb-decline-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #777777;
    text-decoration: none;
    font-size: .78rem;
    font-weight: 700;
    border-radius: 999px;
    padding: 6px 9px;
    transition: background var(--pjd-dur-ui) var(--pjd-ease), border-color var(--pjd-dur-ui) var(--pjd-ease), transform var(--pjd-dur-ui) var(--pjd-ease), color var(--pjd-dur-ui) var(--pjd-ease);
  }
  .pdb-decline-link:hover { background: #f9fafb; color: var(--blue); }
  .pdb-decline-badge {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    padding: 7px 13px;
    border-radius: 999px;
    background: var(--danger-soft);
    color: var(--danger);
    font-size: .78rem;
    font-weight: 700;
    margin-bottom: 14px;
  }
  .pdb-decline-label {
    display: block;
    margin: 0 0 6px;
    font-size: .78rem;
    font-weight: 700;
    color: #333333;
  }
  .pdb-decline-field {
    position: relative;
  }
  .pdb-decline-display {
    width: 100%;
    min-height: 80px;
    border: 1px solid transparent;
    border-radius: 14px;
    background: #fbfbfc;
    color: #555555;
    font-family: inherit;
    font-size: .92rem;
    font-weight: 600;
    line-height: 1.45;
    padding: 16px 48px 16px 16px;
    cursor: text;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
    white-space: pre-wrap;
  }
  .pdb-decline-display:hover {
    background: #ffffff;
    border-color: var(--line);
  }
  .pdb-decline-display:focus {
    outline: none;
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
    background: #ffffff;
  }
  .pdb-decline-display.is-placeholder {
    color: #a3a3a3;
    font-style: italic;
  }
  .pdb-decline-edit-icon {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 2;
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 10px;
    background: transparent;
    color: #777777;
    cursor: pointer;
    display: grid;
    place-items: center;
    transition: background var(--pjd-dur-ui) var(--pjd-ease), border-color var(--pjd-dur-ui) var(--pjd-ease), transform var(--pjd-dur-ui) var(--pjd-ease), color var(--pjd-dur-ui) var(--pjd-ease);
  }
  .pdb-decline-edit-icon:hover {
    background: #f3f4f6;
    color: var(--blue);
    transform: translateY(-1px);
  }
  .pdb-decline-edit-icon svg {
    width: 18px;
    height: 18px;
  }
  .pdb-decline-textarea {
    width: 100%;
    min-height: 90px;
    resize: vertical;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: #ffffff;
    color: #111111;
    font-family: inherit;
    font-size: .88rem;
    font-weight: 600;
    line-height: 1.45;
    padding: 12px 14px;
    outline: none;
    transition: border-color .18s ease, box-shadow .18s ease;
  }
  .pdb-decline-textarea:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }
  .pdb-decline-panel.is-readonly .pdb-decline-textarea,
  .pdb-decline-panel.is-readonly .pdb-decline-actions {
    display: none !important;
  }
  .pdb-decline-panel.is-readonly .pdb-decline-display {
    display: block !important;
  }
  .pdb-decline-panel.is-readonly .pdb-decline-edit-icon {
    display: inline-flex !important;
  }
  .pdb-decline-panel.is-editing .pdb-decline-display,
  .pdb-decline-panel.is-editing .pdb-decline-edit-icon {
    display: none !important;
  }
  .pdb-decline-panel.is-editing .pdb-decline-textarea {
    display: block !important;
  }
  .pdb-decline-panel.is-editing .pdb-decline-actions {
    display: flex !important;
  }
  .pdb-decline-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
    flex-wrap: wrap;
  }
  .pdb-decline-btn {
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 13px;
    border-radius: 9px;
    border: 1px solid var(--line);
    background: #ffffff;
    color: #333333;
    font-family: inherit;
    font-size: .78rem;
    font-weight: 700;
    cursor: pointer;
    transition: background var(--pjd-dur-ui) var(--pjd-ease), border-color var(--pjd-dur-ui) var(--pjd-ease), transform var(--pjd-dur-ui) var(--pjd-ease), color var(--pjd-dur-ui) var(--pjd-ease);
  }
  .pdb-decline-btn:hover { background: #f9fafb; transform: translateY(-1px); }
  .pdb-decline-btn:active { transform: scale(.98); }
  .pdb-decline-btn.is-primary {
    border-color: var(--blue);
    background: var(--blue);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(0,122,255,.14);
  }
  .pdb-decline-tip {
    margin: 12px 0 0;
    padding: 10px 12px;
    border: 1px solid #cfe0fb;
    border-radius: 10px;
    background: var(--blue-soft);
    color: #64748b;
    font-size: .78rem;
    font-weight: 600;
  }
  .pdb-decline-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid var(--line);
    color: #888888;
    font-size: .74rem;
    font-weight: 600;
  }


  /* Fix UX: Cancelar no debe convertir el dictamen a modo lectura */
  .pdb-decline-panel .pdb-decline-read,
  .pdb-decline-panel .pdb-decline-display,
  .pdb-decline-panel .pdb-decline-edit-toggle {
    display: none !important;
  }
  .pdb-decline-panel .pdb-decline-textarea {
    display: block !important;
  }
  .pdb-decline-panel .pdb-decline-actions {
    display: flex !important;
  }


  .pdb-svg {
    width: 16px;
    height: 16px;
    display: block;
    flex-shrink: 0;
  }
  .pdb-svg.sm { width: 14px; height: 14px; }
  .pdb-card-title .ico .pdb-svg,
  .pdb-list-title .ico .pdb-svg,
  .pdb-date-ico .pdb-svg,
  .pdb-doc-group-head .doc-ico .pdb-svg,
  .pdb-doc-file .file-ico .pdb-svg,
  .pdb-list-empty .ico-empty .pdb-svg {
    width: 16px;
    height: 16px;
  }


  /* ═══════ NOTAS Y TAREAS FUNCIONALES ═══════ */
  .pdb-work-card {
    padding: 0;
    overflow: visible;
  }

  .pdb-work-head {
    min-height: 92px;
    padding: 22px 26px;
    border-bottom: 1px solid var(--line);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
  }

  .pdb-work-title-wrap {
    display: flex;
    align-items: center;
    gap: 16px;
    min-width: 0;
  }

  .pdb-work-ico {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    background: #fff7e6;
    color: #d99000;
  }

  .pdb-work-ico.is-violet {
    background: var(--violet-soft);
    color: var(--violet);
  }

  .pdb-work-title {
    margin: 0 0 4px;
    color: #111;
    font-size: 1.18rem;
    font-weight: 700;
    line-height: 1.1;
  }

  .pdb-work-sub {
    margin: 0;
    color: #666;
    font-size: .96rem;
    font-weight: 600;
  }

  .pdb-work-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
  }

  .pdb-icon-btn {
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 10px;
    background: transparent;
    color: #777;
    cursor: pointer;
    display: grid;
    place-items: center;
    transition: background .16s ease, color .16s ease, transform .16s ease;
  }

  .pdb-icon-btn:hover {
    background: #f9fafb;
    color: var(--blue);
    transform: translateY(-1px);
  }

  .pdb-filter-menu {
    height: 44px;
    padding: 0 14px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: #fff;
    color: #111;
    font-family: inherit;
    font-size: .92rem;
    font-weight: 700;
    outline: none;
  }

  .pdb-work-body {
    padding: 24px 26px;
  }

  .pdb-notes-list,
  .pdb-tasks-list {
    display: grid;
    gap: 16px;
    margin-bottom: 24px;
  }

  .pdb-note-card {
    border: 1px solid var(--line);
    border-radius: 14px;
    background: #fff;
    padding: 22px 24px;
    display: flex;
    align-items: flex-start;
    gap: 18px;
  }

  .pdb-note-card:hover,
  .pdb-task-row:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
  }

  .pdb-note-avatar {
    width: 54px;
    height: 54px;
    border-radius: 999px;
    background: #b6b6b6;
    color: #fff;
    display: grid;
    place-items: center;
    font-size: 1.05rem;
    font-weight: 700;
    flex: 0 0 auto;
  }

  .pdb-note-main {
    flex: 1;
    min-width: 0;
  }

  .pdb-note-top {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 10px;
  }

  .pdb-note-author {
    color: #111;
    font-size: 1rem;
    font-weight: 700;
  }

  .pdb-note-date {
    color: #666;
    font-size: .92rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .pdb-note-body {
    color: #666;
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.55;
  }

  .pdb-mention {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 7px;
    background: var(--blue-soft);
    color: var(--blue);
    font-weight: 700;
  }

  .pdb-note-menu,
  .pdb-task-menu {
    margin-left: auto;
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: #777;
    cursor: pointer;
    display: grid;
    place-items: center;
  }

  .pdb-note-menu:hover,
  .pdb-task-menu:hover {
    background: #f9fafb;
    color: var(--danger);
  }

  .pdb-note-input-wrap,
  .pdb-task-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
    gap: 14px;
  }

  .pdb-input {
    width: 100%;
    min-height: 56px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: #fff;
    color: #111;
    font-family: inherit;
    font-size: 1rem;
    font-weight: 600;
    padding: 0 18px;
    outline: none;
    transition: border-color .18s ease, box-shadow .18s ease;
  }

  .pdb-input:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .pdb-add-btn {
    width: 56px;
    height: 56px;
    border: 0;
    border-radius: 10px;
    background: #0b74ff;
    color: #fff;
    cursor: pointer;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    box-shadow: 0 5px 14px rgba(0,122,255,.22);
    transition: transform .16s ease, filter .16s ease;
  }

  .pdb-add-btn:hover {
    filter: brightness(1.04);
    transform: translateY(-1px);
  }

  .pdb-add-btn:active {
    transform: scale(.98);
  }

  .pdb-mention-pop {
    position: absolute;
    left: 0;
    right: 72px;
    bottom: calc(100% + 10px);
    z-index: 80;
    max-height: 230px;
    overflow: auto;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 18px 46px rgba(15,23,42,.13);
    display: none;
  }

  .pdb-mention-pop.is-open {
    display: block;
  }

  .pdb-mention-item {
    width: 100%;
    border: 0;
    background: #fff;
    padding: 12px 16px;
    text-align: left;
    cursor: pointer;
    font-family: inherit;
    display: grid;
    gap: 3px;
  }

  .pdb-mention-item:hover {
    background: #f9fafb;
  }

  .pdb-mention-name {
    color: #111;
    font-weight: 700;
  }

  .pdb-mention-email {
    color: #777;
    font-size: .84rem;
    font-weight: 600;
  }

  .pdb-task-row {
    min-height: 118px;
    border-bottom: 1px solid var(--line);
    display: grid;
    grid-template-columns: 44px minmax(0, 1fr) 34px;
    gap: 14px;
    align-items: flex-start;
    padding: 20px 0;
  }

  .pdb-task-check {
    width: 28px;
    height: 28px;
    margin-top: 2px;
    border-radius: 999px;
    border: 3px solid #e5e7eb;
    background: #fff;
    cursor: pointer;
    display: grid;
    place-items: center;
    color: #fff;
  }

  .pdb-task-check.is-completed {
    background: var(--blue);
    border-color: var(--blue);
  }

  .pdb-task-check svg {
    opacity: 0;
  }

  .pdb-task-check.is-completed svg {
    opacity: 1;
  }

  .pdb-task-title {
    color: #111;
    font-size: 1.05rem;
    font-weight: 600;
    margin: 0 0 18px;
  }

  .pdb-task-row.is-completed .pdb-task-title {
    color: #888;
    text-decoration: line-through;
  }

  .pdb-task-meta {
    display: flex;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
  }

  .pdb-task-chip {
    border: 0;
    background: transparent;
    color: #667085;
    font-family: inherit;
    font-size: .9rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    cursor: pointer;
    padding: 3px 0;
  }

  .pdb-task-chip:hover {
    color: var(--blue);
  }

  .pdb-task-chip.is-priority-alta { color: var(--danger); }
  .pdb-task-chip.is-priority-media { color: var(--orange); }
  .pdb-task-chip.is-priority-baja { color: var(--blue); }
  .pdb-task-chip.is-priority-normal { color: #667085; }

  .pdb-task-create-options {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
    margin-top: 16px;
  }

  .pdb-task-select,
  .pdb-task-date {
    height: 44px;
    border: 1px solid var(--line);
    border-radius: 9px;
    background: #fff;
    color: #111;
    font-family: inherit;
    font-size: .92rem;
    font-weight: 700;
    padding: 0 12px;
    outline: none;
  }

  .pdb-task-select:focus,
  .pdb-task-date:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .pdb-empty-state {
    min-height: 150px;
    border: 1px dashed var(--line);
    border-radius: 14px;
    display: grid;
    place-items: center;
    color: #888;
    font-weight: 700;
    text-align: center;
    padding: 22px;
  }


  /* Menú de acciones unificado para notas y tareas */
  .pdb-action-shell {
    position: relative;
    margin-left: auto;
  }

  .pdb-action-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    z-index: 120;
    width: 286px;
    padding: 8px;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 18px 46px rgba(15,23,42,.14);
    display: none;
    animation: pdbMenuIn .16s ease both;
  }

  .pdb-action-shell.is-open .pdb-action-menu {
    display: block;
  }

  .pdb-action-item {
    width: 100%;
    min-height: 46px;
    padding: 10px 12px;
    border: 0;
    border-radius: 9px;
    background: transparent;
    color: #222;
    font-family: inherit;
    font-size: .96rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 12px;
    text-align: left;
    transition: background .16s ease, color .16s ease, transform .16s ease;
  }

  .pdb-action-item:hover {
    background: #f7f9fc;
    transform: translateY(-1px);
  }

  .pdb-action-item.is-danger {
    color: #ef4444;
  }

  .pdb-action-item.is-danger:hover {
    background: #fff1f1;
  }

  .pdb-action-item svg {
    width: 20px;
    height: 20px;
    flex: 0 0 auto;
  }

  .pdb-note-card.is-pinned,
  .pdb-task-row.is-pinned {
    box-shadow: inset 3px 0 0 var(--blue);
  }

  .pdb-inline-edit {
    width: 100%;
    min-height: 44px;
    border: 1px solid var(--blue);
    border-radius: 9px;
    padding: 8px 12px;
    font: inherit;
    font-weight: 600;
    color: #111;
    outline: none;
    box-shadow: 0 0 0 3px var(--blue-soft);
  }


  /* ENCLAII compact dashboard patch */
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
    --pjd-font: .88rem;
    --pjd-small: .78rem;
    --pjd-title: 1.02rem;
  }

  .pdb-wrap.pjd-shell,
  .pdb-wrap.pjd-shell * {
    font-family: 'Quicksand', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;
  }

  .pdb-wrap.pjd-shell {
    max-width: 1680px !important;
    padding: 12px 18px 42px !important;
    background: var(--pjd-bg) !important;
    color: var(--pjd-ink) !important;
    font-size: var(--pjd-font) !important;
    line-height: 1.38 !important;
  }

  .pdb-wrap.pjd-shell .pdb-navbar,
  .pdb-wrap.pjd-shell .pdb-hero,
  .pdb-wrap.pjd-shell .pdb-card {
    border-color: var(--pjd-border) !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 12px rgba(0,0,0,.02) !important;
  }

  .pdb-wrap.pjd-shell .pdb-navbar {
    padding: 9px 14px !important;
    margin-bottom: 12px !important;
    gap: 8px !important;
  }

  .pdb-wrap.pjd-shell .pdb-hero {
    margin-bottom: 12px !important;
  }

  .pdb-wrap.pjd-shell .pdb-hero-top {
    padding: 12px 16px !important;
    gap: 12px !important;
  }

  .pdb-wrap.pjd-shell .pdb-hero-col {
    padding: 14px 16px !important;
  }

  .pdb-wrap.pjd-shell .pdb-grid {
    gap: 12px !important;
    margin-bottom: 12px !important;
  }

  .pdb-wrap.pjd-shell .pdb-card {
    padding: 14px 16px !important;
  }

  .pdb-wrap.pjd-shell .pdb-card-title,
  .pdb-wrap.pjd-shell .pdb-list-title,
  .pdb-wrap.pjd-shell .pdb-work-title,
  .pdb-wrap.pjd-shell .pdb-ref-title,
  .pdb-wrap.pjd-shell .pdb-decline-title {
    color: var(--pjd-ink) !important;
    font-size: var(--pjd-title) !important;
    line-height: 1.18 !important;
    font-weight: 700 !important;
    margin-bottom: 10px !important;
  }

  .pdb-wrap.pjd-shell .pdb-nav-name,
  .pdb-wrap.pjd-shell .pdb-module-name,
  .pdb-wrap.pjd-shell .pdb-doc-group-head .doc-name,
  .pdb-wrap.pjd-shell .pdb-info-val,
  .pdb-wrap.pjd-shell .pdb-field-val,
  .pdb-wrap.pjd-shell .pdb-date-title,
  .pdb-wrap.pjd-shell .pdb-note-author,
  .pdb-wrap.pjd-shell .pdb-task-title,
  .pdb-wrap.pjd-shell .pdb-action-item {
    color: var(--pjd-ink) !important;
    font-size: var(--pjd-font) !important;
    line-height: 1.32 !important;
  }

  .pdb-wrap.pjd-shell .pdb-card-sub,
  .pdb-wrap.pjd-shell .pdb-module-desc,
  .pdb-wrap.pjd-shell .pdb-step-label,
  .pdb-wrap.pjd-shell .pdb-estado-label,
  .pdb-wrap.pjd-shell .pdb-work-sub,
  .pdb-wrap.pjd-shell .pdb-note-date,
  .pdb-wrap.pjd-shell .pdb-note-body,
  .pdb-wrap.pjd-shell .pdb-task-chip,
  .pdb-wrap.pjd-shell .pdb-info-lbl,
  .pdb-wrap.pjd-shell .pdb-field-lbl,
  .pdb-wrap.pjd-shell .pdb-date-sub,
  .pdb-wrap.pjd-shell .pdb-doc-file .file-status,
  .pdb-wrap.pjd-shell .pdb-doc-total,
  .pdb-wrap.pjd-shell .pdb-chk-total,
  .pdb-wrap.pjd-shell .pdb-chk-row,
  .pdb-wrap.pjd-shell .pdb-list-empty {
    color: var(--pjd-ink-soft) !important;
    font-size: var(--pjd-small) !important;
    line-height: 1.34 !important;
  }

  .pdb-wrap.pjd-shell .pdb-nav-tab,
  .pdb-wrap.pjd-shell .pdb-nav-pill,
  .pdb-wrap.pjd-shell .pdb-estado-chip,
  .pdb-wrap.pjd-shell .pdb-ask-monico,
  .pdb-wrap.pjd-shell .pdb-filter-menu,
  .pdb-wrap.pjd-shell .pdb-task-select,
  .pdb-wrap.pjd-shell .pdb-task-date,
  .pdb-wrap.pjd-shell .pdb-input,
  .pdb-wrap.pjd-shell input,
  .pdb-wrap.pjd-shell select,
  .pdb-wrap.pjd-shell button {
    font-size: var(--pjd-font) !important;
  }

  .pdb-wrap.pjd-shell .pdb-nav-tab {
    padding: 7px 12px !important;
  }

  .pdb-wrap.pjd-shell .pdb-nav-pill,
  .pdb-wrap.pjd-shell .pdb-estado-chip {
    padding: 6px 11px !important;
  }

  .pdb-wrap.pjd-shell .pdb-pipeline {
    min-width: 300px !important;
    padding: 0 16px !important;
  }

  .pdb-wrap.pjd-shell .pdb-step {
    min-width: 66px !important;
    gap: 4px !important;
  }

  .pdb-wrap.pjd-shell .pdb-step-circle {
    width: 36px !important;
    height: 36px !important;
    transition-property: background, border-color, color, box-shadow, opacity !important;
    transition-duration: var(--pjd-dur-ui) !important;
    transition-timing-function: var(--pjd-ease) !important;
  }

  .pdb-wrap.pjd-shell .pdb-step-circle svg {
    width: 17px !important;
    height: 17px !important;
  }

  .pdb-wrap.pjd-shell .pdb-step-line {
    width: 62px !important;
    margin-bottom: 18px !important;
  }

  .pdb-wrap.pjd-shell .pdb-module {
    padding: 10px 12px !important;
    gap: 10px !important;
    margin-bottom: 6px !important;
    border-radius: 11px !important;
    transition-property: background, border-color, opacity, transform !important;
    transition-duration: var(--pjd-dur-ui) !important;
    transition-timing-function: var(--pjd-ease) !important;
  }

  .pdb-wrap.pjd-shell .pdb-module-icon {
    width: 34px !important;
    height: 34px !important;
    border-radius: 9px !important;
  }

  .pdb-wrap.pjd-shell .pdb-chk-col {
    padding: 12px 14px !important;
  }

  .pdb-wrap.pjd-shell .pdb-chk-row {
    padding: 5px 0 !important;
  }

  .pdb-wrap.pjd-shell .pdb-chk-bar {
    height: 4px !important;
    margin: 3px 0 7px !important;
  }

  .pdb-wrap.pjd-shell .pdb-work-head {
    min-height: 68px !important;
    padding: 14px 16px !important;
  }

  .pdb-wrap.pjd-shell .pdb-work-title-wrap {
    gap: 12px !important;
  }

  .pdb-wrap.pjd-shell .pdb-work-ico {
    width: 38px !important;
    height: 38px !important;
    border-radius: 11px !important;
  }

  .pdb-wrap.pjd-shell .pdb-work-body {
    padding: 14px 16px !important;
  }

  .pdb-wrap.pjd-shell .pdb-notes-list,
  .pdb-wrap.pjd-shell .pdb-tasks-list {
    gap: 10px !important;
    margin-bottom: 14px !important;
  }

  .pdb-wrap.pjd-shell .pdb-note-card {
    padding: 14px 16px !important;
    gap: 12px !important;
    border-radius: 12px !important;
  }

  .pdb-wrap.pjd-shell .pdb-note-avatar {
    width: 38px !important;
    height: 38px !important;
    font-size: .9rem !important;
  }

  .pdb-wrap.pjd-shell .pdb-note-top {
    gap: 8px !important;
    margin-bottom: 6px !important;
  }

  .pdb-wrap.pjd-shell .pdb-task-row {
    min-height: 82px !important;
    padding: 13px 0 !important;
    grid-template-columns: 36px minmax(0, 1fr) 32px !important;
    gap: 10px !important;
  }

  .pdb-wrap.pjd-shell .pdb-task-check {
    width: 24px !important;
    height: 24px !important;
    border-width: 2px !important;
  }

  .pdb-wrap.pjd-shell .pdb-task-title {
    margin: 0 0 10px !important;
  }

  .pdb-wrap.pjd-shell .pdb-task-meta {
    gap: 12px !important;
  }

  .pdb-wrap.pjd-shell .pdb-note-input-wrap,
  .pdb-wrap.pjd-shell .pdb-task-input-wrap {
    grid-template-columns: minmax(0, 1fr) 44px !important;
    gap: 10px !important;
  }

  .pdb-wrap.pjd-shell .pdb-input,
  .pdb-wrap.pjd-shell .pdb-task-select,
  .pdb-wrap.pjd-shell .pdb-task-date,
  .pdb-wrap.pjd-shell .pdb-filter-menu {
    min-height: 42px !important;
    height: 42px !important;
    border-radius: 10px !important;
    padding: 0 12px !important;
  }

  .pdb-wrap.pjd-shell .pdb-add-btn {
    width: 44px !important;
    height: 44px !important;
    border-radius: 10px !important;
  }

  .pdb-wrap.pjd-shell .pdb-task-create-options {
    margin-top: 10px !important;
    gap: 8px !important;
    grid-template-columns: repeat(3, minmax(120px, 1fr)) !important;
  }

  .pdb-wrap.pjd-shell .pdb-doc-group-head {
    padding: 10px 12px !important;
  }

  .pdb-wrap.pjd-shell .pdb-doc-file {
    padding: 8px 10px !important;
    border-radius: 9px !important;
    gap: 8px !important;
  }

  .pdb-wrap.pjd-shell .pdb-doc-file .file-ico,
  .pdb-wrap.pjd-shell .pdb-card-title .ico,
  .pdb-wrap.pjd-shell .pdb-list-title .ico {
    width: 24px !important;
    height: 24px !important;
    border-radius: 7px !important;
  }

  .pdb-wrap.pjd-shell .pdb-info-row {
    min-height: 38px !important;
    padding: 9px 0 !important;
  }

  .pdb-wrap.pjd-shell .pdb-ref-head {
    min-height: 70px !important;
    padding: 14px 16px !important;
    gap: 12px !important;
  }

  .pdb-wrap.pjd-shell .pdb-ref-title {
    gap: 10px !important;
    margin: 0 !important;
  }

  .pdb-wrap.pjd-shell .pdb-ref-title .ico {
    width: 38px !important;
    height: 38px !important;
    border-radius: 10px !important;
  }

  .pdb-wrap.pjd-shell .pdb-ref-body {
    padding: 12px 16px 16px !important;
  }

  .pdb-wrap.pjd-shell .pdb-field {
    grid-template-columns: 180px minmax(0,1fr) !important;
    gap: 12px !important;
    padding: 9px 0 !important;
    margin-bottom: 0 !important;
  }

  .pdb-wrap.pjd-shell .pdb-field-lbl {
    margin: 0 !important;
  }

  .pdb-wrap.pjd-shell .pdb-field-val {
    min-height: unset !important;
    padding: 0 !important;
    background: transparent !important;
    border: 0 !important;
  }

  .pdb-wrap.pjd-shell .pdb-date-list {
    padding: 0 16px !important;
  }

  .pdb-wrap.pjd-shell .pdb-date-row {
    min-height: 62px !important;
    padding: 12px 0 !important;
    gap: 12px !important;
  }

  .pdb-wrap.pjd-shell .pdb-date-ico {
    width: 36px !important;
    height: 36px !important;
    flex-basis: 36px !important;
    border-width: 2px !important;
  }

  .pdb-wrap.pjd-shell .pdb-action-menu {
    width: 250px !important;
    padding: 6px !important;
    border-radius: 12px !important;
  }

  .pdb-wrap.pjd-shell .pdb-action-item {
    min-height: 40px !important;
    padding: 8px 10px !important;
  }

  .pdb-wrap.pjd-shell .pdb-action-item svg {
    width: 18px !important;
    height: 18px !important;
  }

  .pdb-wrap.pjd-shell .pdb-ask-monico:active,
  .pdb-wrap.pjd-shell .pdb-add-btn:active,
  .pdb-wrap.pjd-shell button:active {
    transform: scale(.97) !important;
    transition-duration: var(--pjd-dur-press) !important;
  }

  @media (hover:hover) and (pointer:fine) {
    .pdb-wrap.pjd-shell .pdb-module:hover,
    .pdb-wrap.pjd-shell .pdb-note-card:hover,
    .pdb-wrap.pjd-shell .pdb-task-row:hover,
    .pdb-wrap.pjd-shell .pdb-doc-file:hover {
      background: #fbfcfe !important;
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .pdb-wrap.pjd-shell *,
    .pdb-wrap.pjd-shell *::before,
    .pdb-wrap.pjd-shell *::after {
      transition-duration: 0ms !important;
      animation-duration: 0ms !important;
      animation-iteration-count: 1 !important;
    }
  }

  @media (max-width: 760px) {
    .pdb-wrap.pjd-shell {
      padding: 10px 12px 36px !important;
    }

    .pdb-wrap.pjd-shell .pdb-field {
      grid-template-columns: 1fr !important;
    }

    .pdb-wrap.pjd-shell .pdb-task-create-options {
      grid-template-columns: 1fr !important;
    }
  }


  /* ENCLAII monico generated panel */
  .pdb-monico-panel {
    display: none;
    margin: 0 0 12px;
    border: 1px solid #b7d4ff;
    border-radius: 12px;
    background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
    box-shadow: 0 4px 12px rgba(0, 122, 255, .04);
    overflow: hidden;
  }

  .pdb-monico-panel.is-open {
    display: block;
  }

  .pdb-monico-head {
    min-height: 50px;
    padding: 12px 16px;
    border-bottom: 1px solid #d5e6ff;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .pdb-monico-head-title {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--pjd-ink, #1c2024);
    font-size: var(--pjd-title, 1.02rem);
    font-weight: 700;
  }

  .pdb-monico-by {
    color: var(--pjd-ink-soft, #5b6470);
    font-size: var(--pjd-small, .78rem);
    font-weight: 600;
  }

  .pdb-monico-head-actions {
    margin-left: auto;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .pdb-monico-icon-btn {
    width: 32px;
    height: 32px;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: var(--pjd-ink-soft, #5b6470);
    cursor: pointer;
    display: grid;
    place-items: center;
    transition-property: background, color, transform;
    transition-duration: var(--pjd-dur-ui, 200ms);
    transition-timing-function: var(--pjd-ease, cubic-bezier(0.23, 1, 0.32, 1));
  }

  .pdb-monico-icon-btn:active {
    transform: scale(.97);
    transition-duration: var(--pjd-dur-press, 120ms);
  }

  .pdb-monico-body {
    padding: 16px;
  }

  .pdb-monico-content {
    color: var(--pjd-ink-soft, #5b6470);
    font-size: var(--pjd-font, .88rem);
    font-weight: 600;
    line-height: 1.48;
  }

  .pdb-monico-content strong {
    color: var(--pjd-ink, #1c2024);
  }

  .pdb-monico-content ul {
    margin: 10px 0 0;
    padding-left: 18px;
  }

  .pdb-monico-content li {
    margin: 4px 0;
  }

  .pdb-monico-loading {
    display: grid;
    gap: 8px;
  }

  .pdb-monico-skeleton {
    height: 12px;
    border-radius: 999px;
    background: linear-gradient(90deg, #eef5ff, #dcecff, #eef5ff);
    background-size: 220% 100%;
    animation: pdbMonicoShimmer 1.1s var(--pjd-ease, cubic-bezier(0.23, 1, 0.32, 1)) infinite;
  }

  .pdb-monico-skeleton:nth-child(2) { width: 82%; }
  .pdb-monico-skeleton:nth-child(3) { width: 65%; }

  @keyframes pdbMonicoShimmer {
    from { background-position: 120% 0; }
    to { background-position: -120% 0; }
  }

  .pdb-monico-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid #d5e6ff;
  }

  .pdb-monico-action {
    min-height: 36px;
    border: 1px solid #b7d4ff;
    border-radius: 9px;
    background: #ffffff;
    color: var(--pjd-blue, #007aff);
    padding: 0 13px;
    font: inherit;
    font-size: var(--pjd-font, .88rem);
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition-property: background, border-color, transform;
    transition-duration: var(--pjd-dur-ui, 200ms);
    transition-timing-function: var(--pjd-ease, cubic-bezier(0.23, 1, 0.32, 1));
  }

  .pdb-monico-action:active {
    transform: scale(.97);
    transition-duration: var(--pjd-dur-press, 120ms);
  }

  .pdb-insight-actions {
    display: grid;
    gap: 8px;
    width: 100%;
    margin-top: 8px;
  }

  .pdb-insight-action {
    min-height: 38px;
    width: 100%;
    border: 1px solid #b7d4ff;
    border-radius: 10px;
    background: #f4f8ff;
    color: var(--pjd-ink, #1c2024);
    padding: 0 12px;
    font: inherit;
    font-size: var(--pjd-font, .88rem);
    font-weight: 700;
    cursor: pointer;
    display: grid;
    grid-template-columns: 18px minmax(0, 1fr) auto;
    align-items: center;
    gap: 8px;
    text-align: left;
    transition-property: background, border-color, transform;
    transition-duration: var(--pjd-dur-ui, 200ms);
    transition-timing-function: var(--pjd-ease, cubic-bezier(0.23, 1, 0.32, 1));
  }

  .pdb-insight-action small {
    color: var(--pjd-blue, #007aff);
    font-size: var(--pjd-small, .78rem);
    font-weight: 700;
  }

  .pdb-insight-action:active {
    transform: scale(.99);
    transition-duration: var(--pjd-dur-press, 120ms);
  }

  @media (hover:hover) and (pointer:fine) {
    .pdb-monico-icon-btn:hover,
    .pdb-monico-action:hover,
    .pdb-insight-action:hover {
      background: #eaf3ff;
      border-color: var(--pjd-blue, #007aff);
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .pdb-monico-skeleton {
      animation: none;
    }
  }


  /* ═══════ DICTAMEN PARTICIPA / GANADO ═══════ */
  .pdb-result-panel {
    padding: 18px;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
  }

  .pdb-result-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 14px;
  }

  .pdb-result-title {
    margin: 0;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: var(--ink);
    font-size: 1.02rem;
    font-weight: 700;
  }

  .pdb-result-title .ico {
    width: 28px;
    height: 28px;
    display: grid;
    place-items: center;
    color: var(--blue);
  }

  .pdb-result-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--muted);
    text-decoration: none;
    font-weight: 700;
    font-size: .88rem;
  }

  .pdb-result-topline {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
  }

  .pdb-result-badge {
    min-height: 34px;
    padding: 0 18px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #a855f7;
    color: #fff;
    font-weight: 700;
    font-size: .88rem;
    text-transform: uppercase;
  }

  .pdb-result-badge.is-win { background: #1a7f5a; }
  .pdb-result-badge.is-loss { background: #c0392b; }

  .pdb-result-stats {
    color: var(--muted);
    font-size: .88rem;
    font-weight: 700;
  }

  .pdb-result-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
  }

  .pdb-result-field {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--muted);
    font-size: .88rem;
    font-weight: 700;
  }

  .pdb-result-select,
  .pdb-result-input,
  .pdb-result-textarea {
    min-height: 40px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: #fff;
    color: var(--ink);
    padding: 0 12px;
    font: inherit;
    font-size: .88rem;
    font-weight: 600;
    outline: none;
    transition: border-color .2s var(--pjd-ease, ease), box-shadow .2s var(--pjd-ease, ease), background .2s var(--pjd-ease, ease);
  }

  .pdb-result-select:focus,
  .pdb-result-input:focus,
  .pdb-result-textarea:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px #e6f0ff;
  }

  .pdb-result-add {
    min-height: 40px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: #fff;
    color: var(--muted);
    padding: 0 12px;
    font: inherit;
    font-size: .88rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: background .2s var(--pjd-ease, ease), border-color .2s var(--pjd-ease, ease), transform .12s var(--pjd-ease, ease);
  }

  .pdb-result-add:active,
  .pdb-result-confirm:active { transform: scale(.97); }

  .pdb-result-list {
    max-height: 240px;
    overflow: auto;
    padding-right: 6px;
    display: grid;
    gap: 10px;
  }

  .pdb-result-item {
    border: 1px solid var(--line);
    border-radius: 14px;
    background: #fbfcfe;
    padding: 12px 14px;
  }

  .pdb-result-item.is-won {
    border-color: #86efac;
    background: #f0fdf4;
  }

  .pdb-result-item.is-lost {
    border-color: #fecaca;
    background: #fff7f7;
  }

  .pdb-result-item.is-pending {
    border-color: #e5e7eb;
    background: #fbfbfb;
  }

  .pdb-result-item-head {
    display: grid;
    grid-template-columns: minmax(0,1fr) 140px 30px;
    gap: 10px;
    align-items: center;
    margin-bottom: 10px;
  }

  .pdb-result-item-title {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--ink);
    font-weight: 700;
    font-size: .92rem;
  }

  .pdb-result-mark {
    width: 22px;
    height: 22px;
    border-radius: 6px;
    display: grid;
    place-items: center;
    background: #e5e7eb;
    color: #6b7280;
    font-size: .84rem;
    font-weight: 700;
  }

  .pdb-result-item.is-won .pdb-result-mark { background: #4ade80; color:#fff; }
  .pdb-result-item.is-lost .pdb-result-mark { background: #fb7185; color:#fff; }

  .pdb-result-remove {
    width: 30px;
    height: 30px;
    border: 0;
    background: transparent;
    color: var(--muted);
    cursor: pointer;
    border-radius: 8px;
    display: grid;
    place-items: center;
  }

  .pdb-result-input {
    width: 100%;
  }

  .pdb-result-summary {
    margin-top: 14px;
  }

  .pdb-result-summary-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
  }

  .pdb-result-summary-title {
    color: var(--ink);
    font-size: .9rem;
    font-weight: 700;
  }

  .pdb-result-textarea {
    width: 100%;
    min-height: 70px;
    padding: 12px;
    resize: vertical;
    background: #fbfbfb;
  }

  .pdb-result-tip {
    margin-top: 12px;
    border: 1px solid #cfe2ff;
    border-radius: 12px;
    background: #f4f8ff;
    color: var(--muted);
    padding: 10px 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .86rem;
    font-weight: 600;
  }

  .pdb-result-tip strong { color: var(--blue); }

  .pdb-result-footer {
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid var(--line);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    color: var(--muted);
    font-size: .84rem;
    font-weight: 600;
  }

  .pdb-result-confirm {
    min-height: 44px;
    border: 0;
    border-radius: 10px;
    background: var(--blue);
    color: #fff;
    padding: 0 18px;
    font: inherit;
    font-size: .9rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 6px 14px rgba(0,122,255,.18);
    transition: transform .12s var(--pjd-ease, ease), filter .2s var(--pjd-ease, ease);
  }

  @media (hover:hover) and (pointer:fine) {
    .pdb-result-add:hover,
    .pdb-result-remove:hover { background:#f9fafb; border-color: var(--blue); }
    .pdb-result-confirm:hover { filter: brightness(1.04); }
  }

  @media (max-width: 760px) {
    .pdb-result-item-head { grid-template-columns: 1fr; }
    .pdb-result-toolbar,
    .pdb-result-topline,
    .pdb-result-footer { align-items:flex-start; flex-direction:column; }
  }


  /* ═══════════════════════════════════════════════════════════════
     ENCLAII TYPOGRAPHY SYSTEM — UNIFICADO
     Todo el dashboard usa una sola escala de letra.
     Solo títulos/subtítulos/labels cambian por jerarquía.
  ═══════════════════════════════════════════════════════════════ */
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

    --pjd-text: 14px;
    --pjd-text-sm: 12.5px;
    --pjd-title: 17px;
    --pjd-title-lg: 19px;
    --pjd-line: 1.38;
    --pjd-radius: 12px;
  }

  .pdb-wrap.pjd-shell,
  .pdb-wrap.pjd-shell * {
    font-family: 'Quicksand', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;
    letter-spacing: 0 !important;
  }

  .pdb-wrap.pjd-shell {
    color: var(--pjd-ink) !important;
    background: var(--pjd-bg) !important;
    font-size: var(--pjd-text) !important;
    line-height: var(--pjd-line) !important;
  }

  /* Base: casi todo igual tamaño */
  .pdb-wrap.pjd-shell p,
  .pdb-wrap.pjd-shell span,
  .pdb-wrap.pjd-shell div,
  .pdb-wrap.pjd-shell a,
  .pdb-wrap.pjd-shell button,
  .pdb-wrap.pjd-shell input,
  .pdb-wrap.pjd-shell select,
  .pdb-wrap.pjd-shell textarea,
  .pdb-wrap.pjd-shell label,
  .pdb-wrap.pjd-shell li {
    font-size: var(--pjd-text) !important;
    line-height: var(--pjd-line) !important;
  }

  /* Títulos principales */
  .pdb-wrap.pjd-shell .pdb-card-title,
  .pdb-wrap.pjd-shell .pdb-list-title,
  .pdb-wrap.pjd-shell .pdb-work-title,
  .pdb-wrap.pjd-shell .pdb-ref-title,
  .pdb-wrap.pjd-shell .pdb-result-title,
  .pdb-wrap.pjd-shell .pdb-decline-title,
  .pdb-wrap.pjd-shell .pdb-monico-head-title,
  .pdb-wrap.pjd-shell .pdb-title-main,
  .pdb-wrap.pjd-shell h1,
  .pdb-wrap.pjd-shell h2,
  .pdb-wrap.pjd-shell h3 {
    color: var(--pjd-ink) !important;
    font-size: var(--pjd-title) !important;
    line-height: 1.22 !important;
    font-weight: 700 !important;
  }

  .pdb-wrap.pjd-shell .pdb-hero-title,
  .pdb-wrap.pjd-shell .pdb-project-title {
    color: var(--pjd-ink) !important;
    font-size: var(--pjd-title-lg) !important;
    line-height: 1.18 !important;
    font-weight: 700 !important;
  }

  /* Texto normal importante */
  .pdb-wrap.pjd-shell .pdb-nav-name,
  .pdb-wrap.pjd-shell .pdb-module-name,
  .pdb-wrap.pjd-shell .pdb-doc-group-head .doc-name,
  .pdb-wrap.pjd-shell .pdb-info-val,
  .pdb-wrap.pjd-shell .pdb-field-val,
  .pdb-wrap.pjd-shell .pdb-date-title,
  .pdb-wrap.pjd-shell .pdb-note-author,
  .pdb-wrap.pjd-shell .pdb-task-title,
  .pdb-wrap.pjd-shell .pdb-action-item,
  .pdb-wrap.pjd-shell .pdb-result-badge,
  .pdb-wrap.pjd-shell .pdb-result-summary-title,
  .pdb-wrap.pjd-shell .pdb-chk-row.is-main,
  .pdb-wrap.pjd-shell .pdb-chk-total strong,
  .pdb-wrap.pjd-shell .pdb-result-confirm,
  .pdb-wrap.pjd-shell .pdb-decline-badge {
    color: var(--pjd-ink) !important;
    font-size: var(--pjd-text) !important;
    font-weight: 700 !important;
    line-height: var(--pjd-line) !important;
  }

  /* Secundarios */
  .pdb-wrap.pjd-shell .pdb-card-sub,
  .pdb-wrap.pjd-shell .pdb-module-desc,
  .pdb-wrap.pjd-shell .pdb-step-label,
  .pdb-wrap.pjd-shell .pdb-estado-label,
  .pdb-wrap.pjd-shell .pdb-work-sub,
  .pdb-wrap.pjd-shell .pdb-note-date,
  .pdb-wrap.pjd-shell .pdb-note-body,
  .pdb-wrap.pjd-shell .pdb-task-chip,
  .pdb-wrap.pjd-shell .pdb-info-lbl,
  .pdb-wrap.pjd-shell .pdb-field-lbl,
  .pdb-wrap.pjd-shell .pdb-date-sub,
  .pdb-wrap.pjd-shell .pdb-doc-file .file-status,
  .pdb-wrap.pjd-shell .pdb-doc-total,
  .pdb-wrap.pjd-shell .pdb-chk-total,
  .pdb-wrap.pjd-shell .pdb-chk-row,
  .pdb-wrap.pjd-shell .pdb-list-empty,
  .pdb-wrap.pjd-shell .pdb-result-stats,
  .pdb-wrap.pjd-shell .pdb-result-tip,
  .pdb-wrap.pjd-shell .pdb-result-footer,
  .pdb-wrap.pjd-shell .pdb-decline-tip,
  .pdb-wrap.pjd-shell .pdb-decline-footer,
  .pdb-wrap.pjd-shell .pdb-monico-by,
  .pdb-wrap.pjd-shell .pdb-monico-content,
  .pdb-wrap.pjd-shell .pdb-insights-empty p,
  .pdb-wrap.pjd-shell small {
    color: var(--pjd-ink-soft) !important;
    font-size: var(--pjd-text-sm) !important;
    font-weight: 600 !important;
    line-height: 1.35 !important;
  }

  /* Controles uniformes */
  .pdb-wrap.pjd-shell input,
  .pdb-wrap.pjd-shell select,
  .pdb-wrap.pjd-shell textarea,
  .pdb-wrap.pjd-shell .pdb-input,
  .pdb-wrap.pjd-shell .pdb-task-select,
  .pdb-wrap.pjd-shell .pdb-task-date,
  .pdb-wrap.pjd-shell .pdb-filter-menu,
  .pdb-wrap.pjd-shell .pdb-result-select,
  .pdb-wrap.pjd-shell .pdb-result-textarea,
  .pdb-wrap.pjd-shell .pdb-decline-textarea {
    min-height: 42px !important;
    border: 1px solid var(--pjd-border) !important;
    border-radius: 10px !important;
    background: var(--pjd-surface) !important;
    color: var(--pjd-ink) !important;
    font-size: var(--pjd-text) !important;
    font-weight: 600 !important;
    outline: none !important;
    transition-property: border-color, box-shadow, background !important;
    transition-duration: var(--pjd-dur-ui) !important;
    transition-timing-function: var(--pjd-ease) !important;
  }

  .pdb-wrap.pjd-shell input:focus,
  .pdb-wrap.pjd-shell select:focus,
  .pdb-wrap.pjd-shell textarea:focus {
    border-color: var(--pjd-blue) !important;
    box-shadow: 0 0 0 3px #e6f0ff !important;
  }

  /* Botones uniformes */
  .pdb-wrap.pjd-shell button,
  .pdb-wrap.pjd-shell .pdb-btn,
  .pdb-wrap.pjd-shell .pdb-ask-monico,
  .pdb-wrap.pjd-shell .pdb-insights-btn,
  .pdb-wrap.pjd-shell .pdb-add-btn,
  .pdb-wrap.pjd-shell .pdb-result-add,
  .pdb-wrap.pjd-shell .pdb-result-confirm,
  .pdb-wrap.pjd-shell .pdb-decline-btn,
  .pdb-wrap.pjd-shell .pdb-monico-action {
    font-size: var(--pjd-text) !important;
    font-weight: 700 !important;
    transition-property: background, border-color, color, transform, opacity, box-shadow !important;
    transition-duration: var(--pjd-dur-ui) !important;
    transition-timing-function: var(--pjd-ease) !important;
  }

  .pdb-wrap.pjd-shell button:active,
  .pdb-wrap.pjd-shell .pdb-btn:active,
  .pdb-wrap.pjd-shell .pdb-ask-monico:active,
  .pdb-wrap.pjd-shell .pdb-insights-btn:active,
  .pdb-wrap.pjd-shell .pdb-add-btn:active,
  .pdb-wrap.pjd-shell .pdb-result-add:active,
  .pdb-wrap.pjd-shell .pdb-result-confirm:active,
  .pdb-wrap.pjd-shell .pdb-decline-btn:active,
  .pdb-wrap.pjd-shell .pdb-monico-action:active {
    transform: scale(.97) !important;
    transition-duration: var(--pjd-dur-press) !important;
  }

  /* Colores semánticos uniformes */
  .pdb-wrap.pjd-shell .c-green,
  .pdb-wrap.pjd-shell .is-green,
  .pdb-wrap.pjd-shell .pdb-step-label.is-green,
  .pdb-wrap.pjd-shell .pdb-result-badge.is-won {
    color: var(--pjd-ok) !important;
  }

  .pdb-wrap.pjd-shell .c-orange,
  .pdb-wrap.pjd-shell .is-orange,
  .pdb-wrap.pjd-shell .pdb-task-chip.is-priority-media {
    color: var(--pjd-pending) !important;
  }

  .pdb-wrap.pjd-shell .c-red,
  .pdb-wrap.pjd-shell .is-red,
  .pdb-wrap.pjd-shell .pdb-task-chip.is-priority-alta {
    color: var(--pjd-danger) !important;
  }

  .pdb-wrap.pjd-shell .c-gray,
  .pdb-wrap.pjd-shell .is-gray {
    color: var(--pjd-ink-soft) !important;
  }

  .pdb-wrap.pjd-shell .pdb-date-badge.is-near,
  .pdb-wrap.pjd-shell .pdb-date-badge.is-warning,
  .pdb-wrap.pjd-shell .pdb-date-badge.is-future,
  .pdb-wrap.pjd-shell .pdb-doc-file .file-status.is-pending {
    background: var(--pjd-pending-bg) !important;
    color: var(--pjd-pending) !important;
  }

  .pdb-wrap.pjd-shell .pdb-date-badge.is-past,
  .pdb-wrap.pjd-shell .pdb-doc-file .file-status.is-error {
    background: var(--pjd-danger-bg) !important;
    color: var(--pjd-danger) !important;
  }

  /* Tarjetas y espacios compactos */
  .pdb-wrap.pjd-shell .pdb-card,
  .pdb-wrap.pjd-shell .pdb-hero,
  .pdb-wrap.pjd-shell .pdb-navbar,
  .pdb-wrap.pjd-shell .pdb-result-panel,
  .pdb-wrap.pjd-shell .pdb-decline-panel,
  .pdb-wrap.pjd-shell .pdb-monico-panel {
    border-color: var(--pjd-border) !important;
    border-radius: var(--pjd-radius) !important;
    box-shadow: 0 4px 12px rgba(0,0,0,.02) !important;
  }

  .pdb-wrap.pjd-shell .pdb-card {
    padding: 14px 16px !important;
  }

  .pdb-wrap.pjd-shell .pdb-grid {
    gap: 12px !important;
    margin-bottom: 12px !important;
  }

  .pdb-wrap.pjd-shell .pdb-work-head,
  .pdb-wrap.pjd-shell .pdb-ref-head {
    min-height: 68px !important;
    padding: 14px 16px !important;
  }

  .pdb-wrap.pjd-shell .pdb-work-body,
  .pdb-wrap.pjd-shell .pdb-ref-body {
    padding: 14px 16px !important;
  }

  .pdb-wrap.pjd-shell .pdb-date-row,
  .pdb-wrap.pjd-shell .pdb-field,
  .pdb-wrap.pjd-shell .pdb-info-row,
  .pdb-wrap.pjd-shell .pdb-task-row {
    border-color: var(--pjd-border) !important;
  }

  .pdb-wrap.pjd-shell .pdb-field {
    display: grid !important;
    grid-template-columns: 180px minmax(0, 1fr) !important;
    gap: 12px !important;
    padding: 9px 0 !important;
  }

  .pdb-wrap.pjd-shell .pdb-field-val {
    min-height: unset !important;
    padding: 0 !important;
    background: transparent !important;
    border: 0 !important;
  }

  .pdb-wrap.pjd-shell .pdb-action-menu {
    border-color: var(--pjd-border) !important;
    border-radius: 12px !important;
  }

  @media (hover:hover) and (pointer:fine) {
    .pdb-wrap.pjd-shell button:hover,
    .pdb-wrap.pjd-shell .pdb-module:hover,
    .pdb-wrap.pjd-shell .pdb-note-card:hover,
    .pdb-wrap.pjd-shell .pdb-task-row:hover,
    .pdb-wrap.pjd-shell .pdb-doc-file:hover {
      background-color: #fbfcfe;
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .pdb-wrap.pjd-shell *,
    .pdb-wrap.pjd-shell *::before,
    .pdb-wrap.pjd-shell *::after {
      transition-duration: 0ms !important;
      animation-duration: 0ms !important;
      animation-iteration-count: 1 !important;
    }

    .pdb-wrap.pjd-shell button:active {
      transform: none !important;
    }
  }

  @media (max-width: 760px) {
    .pdb-wrap.pjd-shell .pdb-field {
      grid-template-columns: 1fr !important;
    }
  }


  /* ═══════════════════════════════════════════════════════════════
     DICTAMEN COMPACTO — optimiza espacio para ver más partidas
  ═══════════════════════════════════════════════════════════════ */
  .pdb-wrap.pjd-shell .pdb-result-panel {
    padding: 12px 14px !important;
    border-radius: 12px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-head {
    align-items: center !important;
    gap: 10px !important;
    margin-bottom: 8px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-title {
    font-size: 16px !important;
    gap: 8px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-title .ico {
    width: 24px !important;
    height: 24px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-link,
  .pdb-wrap.pjd-shell .pdb-result-stats {
    font-size: 12.5px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-topline {
    margin-bottom: 8px !important;
    gap: 10px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-badge {
    min-height: 28px !important;
    padding: 0 14px !important;
    font-size: 13px !important;
    border-radius: 999px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-toolbar {
    margin-bottom: 8px !important;
    gap: 10px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-field {
    gap: 8px !important;
    font-size: 13px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-select,
  .pdb-wrap.pjd-shell .pdb-result-input {
    min-height: 34px !important;
    height: 34px !important;
    border-radius: 9px !important;
    padding: 0 10px !important;
    font-size: 13px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-add {
    min-height: 34px !important;
    height: 34px !important;
    border-radius: 9px !important;
    padding: 0 11px !important;
    gap: 6px !important;
    font-size: 13px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-list {
    max-height: 360px !important;
    gap: 8px !important;
    padding-right: 4px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-item {
    padding: 8px 10px !important;
    border-radius: 11px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-item-head {
    grid-template-columns: minmax(0, 1fr) 124px 26px !important;
    gap: 8px !important;
    margin-bottom: 7px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-item-title {
    gap: 7px !important;
    font-size: 14px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-mark {
    width: 20px !important;
    height: 20px !important;
    border-radius: 6px !important;
    font-size: 12px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-remove {
    width: 26px !important;
    height: 26px !important;
    border-radius: 7px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-item .pdb-result-input {
    min-height: 34px !important;
    height: 34px !important;
    padding: 0 10px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-summary {
    margin-top: 9px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-summary-head {
    margin-bottom: 6px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-summary-title {
    font-size: 14px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-textarea {
    min-height: 48px !important;
    height: 48px !important;
    padding: 9px 10px !important;
    border-radius: 10px !important;
    font-size: 13px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-tip {
    margin-top: 8px !important;
    min-height: 36px !important;
    padding: 7px 10px !important;
    border-radius: 10px !important;
    gap: 7px !important;
    font-size: 12.5px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-footer {
    margin-top: 9px !important;
    padding-top: 9px !important;
    gap: 10px !important;
    font-size: 12.5px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-confirm {
    min-height: 36px !important;
    height: 36px !important;
    border-radius: 9px !important;
    padding: 0 14px !important;
    font-size: 13px !important;
    gap: 7px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-panel .pdb-svg {
    width: 16px !important;
    height: 16px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-panel .pdb-svg.sm {
    width: 14px !important;
    height: 14px !important;
  }

  @media (max-width: 760px) {
    .pdb-wrap.pjd-shell .pdb-result-item-head {
      grid-template-columns: 1fr !important;
    }

    .pdb-wrap.pjd-shell .pdb-result-toolbar,
    .pdb-wrap.pjd-shell .pdb-result-topline,
    .pdb-wrap.pjd-shell .pdb-result-footer {
      align-items: stretch !important;
      flex-direction: column !important;
    }
  }


  /* AJUSTE EXTRA: partidas del dictamen más compactas */
  .pdb-wrap.pjd-shell .pdb-result-panel {
    padding: 14px 16px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-topline {
    margin: 8px 0 10px !important;
    gap: 10px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-badge {
    min-height: 34px !important;
    padding: 6px 16px !important;
    border-radius: 999px !important;
    font-size: 13px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-toolbar {
    margin-bottom: 10px !important;
    gap: 10px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-field {
    gap: 8px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-select,
  .pdb-wrap.pjd-shell .pdb-result-add {
    min-height: 36px !important;
    height: 36px !important;
    border-radius: 9px !important;
    padding: 0 11px !important;
    font-size: 13px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-list {
    max-height: 260px !important;
    gap: 8px !important;
    padding-right: 8px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-item {
    padding: 10px 12px !important;
    border-radius: 12px !important;
    gap: 8px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-item-head {
    min-height: 30px !important;
    gap: 8px !important;
    margin-bottom: 7px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-item-icon {
    width: 24px !important;
    height: 24px !important;
    border-radius: 7px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-item-title {
    font-size: 14px !important;
    line-height: 1.2 !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-item-status {
    min-height: 32px !important;
    height: 32px !important;
    min-width: 118px !important;
    border-radius: 9px !important;
    padding: 0 10px !important;
    font-size: 13px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-item-remove {
    width: 28px !important;
    height: 28px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-item-note {
    min-height: 36px !important;
    height: 36px !important;
    border-radius: 9px !important;
    padding: 0 12px !important;
    font-size: 13px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-summary {
    margin-top: 10px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-summary-head {
    margin-bottom: 6px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-textarea {
    min-height: 54px !important;
    height: 54px !important;
    padding: 10px 12px !important;
    font-size: 13px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-tip {
    min-height: 38px !important;
    padding: 8px 11px !important;
    margin-top: 10px !important;
    border-radius: 10px !important;
    font-size: 12.5px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-footer {
    margin-top: 10px !important;
    padding-top: 10px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-confirm {
    min-height: 38px !important;
    height: 38px !important;
    padding: 0 16px !important;
    border-radius: 9px !important;
    font-size: 13px !important;
  }

  @media (max-width: 760px) {
    .pdb-wrap.pjd-shell .pdb-result-item-status {
      min-width: 105px !important;
    }

    .pdb-wrap.pjd-shell .pdb-result-list {
      max-height: 240px !important;
    }
  }


  /* AJUSTE FINAL: badge ganado blanco + partidas más bajas */
  .pdb-wrap.pjd-shell .pdb-result-badge,
  .pdb-wrap.pjd-shell .pdb-result-badge.is-won,
  .pdb-wrap.pjd-shell #pdbResultBadge {
    color: #ffffff !important;
    background: #15803d !important;
    border-color: #15803d !important;
    min-height: 30px !important;
    height: 30px !important;
    padding: 4px 14px !important;
    font-size: 12.5px !important;
    line-height: 1 !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-panel {
    padding: 12px 14px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-topline {
    margin: 6px 0 8px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-toolbar {
    margin-bottom: 8px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-list {
    max-height: 230px !important;
    gap: 6px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-item {
    padding: 8px 10px !important;
    border-radius: 11px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-item-head {
    min-height: 28px !important;
    margin-bottom: 5px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-item-icon {
    width: 22px !important;
    height: 22px !important;
    border-radius: 7px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-item-title {
    font-size: 13px !important;
    line-height: 1.15 !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-item-status {
    min-height: 30px !important;
    height: 30px !important;
    min-width: 104px !important;
    border-radius: 8px !important;
    padding: 0 9px !important;
    font-size: 12.5px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-item-remove {
    width: 26px !important;
    height: 26px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-item-note {
    min-height: 32px !important;
    height: 32px !important;
    border-radius: 8px !important;
    padding: 0 10px !important;
    font-size: 12.5px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-summary {
    margin-top: 8px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-textarea {
    min-height: 46px !important;
    height: 46px !important;
    padding: 8px 10px !important;
    font-size: 12.5px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-tip {
    min-height: 34px !important;
    padding: 7px 10px !important;
    margin-top: 8px !important;
    font-size: 12px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-footer {
    margin-top: 8px !important;
    padding-top: 8px !important;
  }

  .pdb-wrap.pjd-shell .pdb-result-confirm {
    min-height: 34px !important;
    height: 34px !important;
    padding: 0 14px !important;
    border-radius: 8px !important;
    font-size: 12.5px !important;
  }



  /* AJUSTE COMPACTO Y MINIMALISTA — ETIQUETAS */
  .pdb-wrap.pjd-shell .pdb-info-row:has(.pdb-tags-shell) {
    align-items: flex-start !important;
  }

  .pdb-wrap.pjd-shell .pdb-tags-shell {
    max-width: 100% !important;
  }

  .pdb-wrap.pjd-shell .pdb-tags {
    gap: 8px !important;
    align-items: center !important;
  }

  .pdb-wrap.pjd-shell .pdb-tag-pill,
  .pdb-wrap.pjd-shell .pdb-tag-add {
    min-height: 26px !important;
    height: 26px !important;
    padding: 0 10px !important;
    border-radius: 999px !important;
    font-size: 12px !important;
    line-height: 1 !important;
    font-weight: 700 !important;
    gap: 6px !important;
    box-shadow: none !important;
  }

  .pdb-wrap.pjd-shell .pdb-tag-pill {
    border: 1px solid #c7dafc !important;
    background: #eef5ff !important;
    color: var(--pjd-blue) !important;
    padding-right: 6px !important;
  }

  .pdb-wrap.pjd-shell .pdb-tag-pill button {
    width: 16px !important;
    height: 16px !important;
    min-height: 16px !important;
    border: 0 !important;
    border-radius: 999px !important;
    padding: 0 !important;
    font-size: 11px !important;
    line-height: 1 !important;
    background: rgba(0,122,255,.10) !important;
    color: var(--pjd-blue) !important;
    box-shadow: none !important;
  }

  .pdb-wrap.pjd-shell .pdb-tag-add {
    border: 1px dashed #b9c7da !important;
    background: #ffffff !important;
    color: #5b6470 !important;
    padding: 0 14px !important;
  }

  .pdb-wrap.pjd-shell .pdb-tag-add:hover {
    border-color: #9fc1fb !important;
    color: var(--pjd-blue) !important;
    background: #f7fbff !important;
    transform: translateY(-1px) !important;
  }

  .pdb-wrap.pjd-shell .pdb-tags-popover {
    top: calc(100% + 8px) !important;
    left: 0 !important;
    width: min(360px, calc(100vw - 52px)) !important;
    padding: 10px !important;
    border: 1px solid var(--pjd-border) !important;
    border-radius: 14px !important;
    background: #fff !important;
    box-shadow: 0 14px 34px rgba(15, 23, 42, .10) !important;
  }

  .pdb-wrap.pjd-shell .pdb-tags-search {
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    min-height: 42px !important;
    height: 42px !important;
    padding: 0 12px !important;
    border: 1px solid var(--pjd-border) !important;
    border-radius: 12px !important;
    background: #fff !important;
    box-shadow: none !important;
  }

  .pdb-wrap.pjd-shell .pdb-tags-search:focus-within {
    border-color: var(--pjd-blue) !important;
    box-shadow: 0 0 0 3px rgba(0,122,255,.10) !important;
  }

  .pdb-wrap.pjd-shell .pdb-tags-search svg {
    width: 18px !important;
    height: 18px !important;
    color: #7a8594 !important;
    flex: 0 0 auto !important;
  }

  .pdb-wrap.pjd-shell .pdb-tags-search input,
  .pdb-wrap.pjd-shell .pdb-tags-search input:focus {
    min-height: 0 !important;
    height: auto !important;
    border: 0 !important;
    outline: none !important;
    box-shadow: none !important;
    background: transparent !important;
    padding: 0 !important;
    margin: 0 !important;
    border-radius: 0 !important;
    color: var(--pjd-ink) !important;
    font-size: 12px !important;
    font-weight: 600 !important;
  }

  .pdb-wrap.pjd-shell .pdb-tags-search input::placeholder {
    color: #7f8793 !important;
    opacity: 1 !important;
  }

  .pdb-wrap.pjd-shell .pdb-tags-sep {
    margin: 10px 2px !important;
    background: var(--pjd-border) !important;
  }

  .pdb-wrap.pjd-shell .pdb-tags-title {
    margin: 0 2px 8px !important;
    font-size: 12px !important;
    font-weight: 700 !important;
    color: var(--pjd-ink-soft) !important;
  }

  .pdb-wrap.pjd-shell .pdb-common-tags {
    gap: 8px !important;
  }

  .pdb-wrap.pjd-shell .pdb-common-tag,
  .pdb-wrap.pjd-shell .pdb-tag-create {
    min-height: 30px !important;
    height: 30px !important;
    padding: 0 12px !important;
    border-radius: 999px !important;
    border: 1px solid #cfe0ff !important;
    background: #fff !important;
    color: var(--pjd-blue) !important;
    box-shadow: none !important;
    font-size: 12px !important;
    line-height: 1 !important;
    font-weight: 700 !important;
  }

  .pdb-wrap.pjd-shell .pdb-common-tag.is-selected {
    background: var(--pjd-blue) !important;
    border-color: var(--pjd-blue) !important;
    color: #fff !important;
  }

  .pdb-wrap.pjd-shell .pdb-tag-create-row {
    gap: 8px !important;
    padding: 2px 2px 0 !important;
    font-size: 12px !important;
  }

  .pdb-wrap.pjd-shell .pdb-tag-preview {
    min-height: 24px !important;
    padding: 0 10px !important;
    border-radius: 999px !important;
    font-size: 11.5px !important;
  }

  .pdb-wrap.pjd-shell .pdb-action-menu {
    min-width: 190px !important;
    padding: 8px !important;
    border-radius: 12px !important;
    box-shadow: 0 14px 30px rgba(15,23,42,.12) !important;
  }

  .pdb-wrap.pjd-shell .pdb-action-item {
    min-height: 36px !important;
    padding: 8px 10px !important;
    border-radius: 9px !important;
    gap: 10px !important;
    font-size: 12.5px !important;
  }

  .pdb-wrap.pjd-shell .pdb-action-item svg {
    width: 17px !important;
    height: 17px !important;
  }

</style>
@endpush

@section('content')
@php
  $sd = $project->structured_data ?? [];
  $ficha  = $sd['ficha'] ?? [];
  $fechas = $sd['fechas_clave'] ?? [];
  $docs   = $project->documents;
  $notas  = isset($notes) ? collect($notes) : collect($project->notes ?? []);
  $tareas = isset($tasks) ? collect($tasks) : collect($project->tasks ?? []);
  $dashboardUsers = isset($dashboardUsers) ? collect($dashboardUsers) : collect();
  $tareasPend = $tareas->where('completed', false)->count();
  $tareasDone = $tareas->where('completed', true)->count();

  /*
   |--------------------------------------------------------------------------
   | Checklist ligado al Análisis
   |--------------------------------------------------------------------------
   | El dashboard ya NO depende solo de structured_data.checklist.
   | Primero lee los requisitos reales guardados en project_checklist_items,
   | que son los mismos que editas desde la vista de Análisis.
   */
  $project->loadMissing(['checklistItems']);
  $checklistItemsDashboard = $project->checklistItems ?? collect();

  if ($checklistItemsDashboard->count() > 0) {
      $chk = [
          'total'        => $checklistItemsDashboard->count(),
          'sin_revisar'  => $checklistItemsDashboard->where('compliance_status', 'sin_revisar')->count(),
          'no_cumple'    => $checklistItemsDashboard->where('compliance_status', 'no_cumple')->count(),
          'parcial'      => $checklistItemsDashboard->where('compliance_status', 'parcial')->count(),
          'cumple'       => $checklistItemsDashboard->where('compliance_status', 'cumple')->count(),
          'pendiente'    => $checklistItemsDashboard->where('review_status', 'pendiente')->count(),
          'en_revision'  => $checklistItemsDashboard->where('review_status', 'en_revision')->count(),
          'aprobado'     => $checklistItemsDashboard->where('review_status', 'aprobado')->count(),
      ];
  } else {
      $legacyChecklist = $project->checklist ?: data_get($sd, 'checklist_sugerido', []);
      $legacyCollection = collect(is_array($legacyChecklist) ? $legacyChecklist : []);

      $chk = ($sd['checklist'] ?? null) ?: [
          'total'        => $legacyCollection->count(),
          'sin_revisar'  => $legacyCollection->where('cumplimiento', '-')->count(),
          'no_cumple'    => $legacyCollection->where('cumplimiento', 'No Cumple')->count(),
          'parcial'      => $legacyCollection->where('cumplimiento', 'Parcial')->count(),
          'cumple'       => $legacyCollection->where('cumplimiento', 'Cumple')->count(),
          'pendiente'    => $legacyCollection->where('status', 'Pendiente')->count(),
          'en_revision'  => $legacyCollection->where('status', 'En revisión')->count(),
          'aprobado'     => $legacyCollection->where('status', 'Aprobado')->count(),
      ];
  }

  $checklistTotalDashboard = (int) ($chk['total'] ?? 0);
  $hasChecklistDashboard = $checklistTotalDashboard > 0;

  /* ── Estado del proyecto → controla chip, pipeline y módulo sugerido ──
     IMPORTANTE: usamos workflow_status para NO mezclarlo con status
     de procesamiento (ready, processing, error). */
  $workflowOptions = [
      'analisis_bases' => ['key' => 'analisis_bases', 'label' => 'Análisis de Bases', 'tone' => 'blue', 'step' => 1, 'dot' => '#3b82f6'],
      'revision' => ['key' => 'revision', 'label' => 'Revisión', 'tone' => 'orange', 'step' => 2, 'dot' => '#f59e0b'],
      'participa' => ['key' => 'participa', 'label' => 'Participa', 'tone' => 'green', 'step' => 3, 'dot' => '#10b981'],
      'junta_aclaraciones' => ['key' => 'junta_aclaraciones', 'label' => 'Junta de Aclaraciones', 'tone' => 'green', 'step' => 3, 'dot' => '#10b981', 'child' => true],
      'armado_propuesta' => ['key' => 'armado_propuesta', 'label' => 'Armado de Propuesta', 'tone' => 'green', 'step' => 3, 'dot' => '#10b981', 'child' => true],
      'entrega' => ['key' => 'entrega', 'label' => 'Entrega', 'tone' => 'green', 'step' => 3, 'dot' => '#10b981', 'child' => true],
      'no_participa' => ['key' => 'no_participa', 'label' => 'No participa', 'tone' => 'red', 'step' => 3, 'dot' => '#ef4444'],
      'ganado' => ['key' => 'ganado', 'label' => 'Ganado', 'tone' => 'violet', 'step' => 3, 'dot' => '#8b5cf6'],
      'perdido' => ['key' => 'perdido', 'label' => 'Perdido', 'tone' => 'gray', 'step' => 3, 'dot' => '#6b7280'],
      'desierta' => ['key' => 'desierta', 'label' => 'Desierta', 'tone' => 'gray', 'step' => 3, 'dot' => '#64748b'],
  ];

  $workflowKey = $project->workflow_status ?: 'analisis_bases';
  if (!isset($workflowOptions[$workflowKey])) {
      $workflowKey = 'analisis_bases';
  }

  $estado = $workflowOptions[$workflowKey];
  $cur = $estado['step'];
  $workflowUrl = route('projects.workflow-status', $project);
  $labelsUrl = url('/projects/' . $project->slug . '/labels');
  $noParticipaUrl = url('/projects/' . $project->slug . '/no-participa-reason');
  $noParticipaReason = $project->no_participa_reason ?? '';
  $noParticipaConfirmedAt = $project->no_participa_confirmed_at
      ? $project->no_participa_confirmed_at->format('d/m/Y H:i')
      : null;
  $noParticipaConfirmedBy = method_exists($project, 'noParticipaConfirmer')
      ? optional($project->noParticipaConfirmer)->name
      : null;
  $showChecklistPanel = in_array($estado['key'], ['participa', 'junta_aclaraciones', 'armado_propuesta', 'entrega'], true);
  $showResultDictamen = in_array($estado['key'], ['ganado', 'perdido'], true);
  $resultInitialMode = match($estado['key']) {
      'ganado' => 'total',
      'perdido' => 'perdido',
      default => 'parcial',
  };
  $resultInitialBadge = match($estado['key']) {
      'ganado' => 'GANADO',
      'perdido' => 'PERDIDO',
      default => 'PARCIAL',
  };
  $projectLabels = collect($project->labels ?? [])->filter()->values()->all();
  $commonLabels = collect(['*PRUEBA*', 'papeleria', 'urgente', 'licitación', 'revisión', 'documentación', 'alta prioridad'])->merge($projectLabels)->unique()->values()->all();

  /* ── Clasificar documentos en grupos (heurística por nombre) ── */
  $bases    = $docs->filter(fn($d) => stripos($d->filename, 'bases') !== false || stripos($d->filename, 'convocatoria') !== false || stripos($d->filename, 'anexo') !== false || stripos($d->filename, 'licitacion') !== false)->values();
  $juntas   = $docs->filter(fn($d) => stripos($d->filename, 'junta') !== false || stripos($d->filename, 'aclaracion') !== false)->values();
  $regdocs  = $docs->diff($bases)->diff($juntas)->values();

  $today = \Carbon\Carbon::today();
  $badgeFor = function ($dateStr) use ($today) {
      if (!$dateStr) return ['', '—'];
      try {
          $d = strpos($dateStr, '/') !== false ? \Carbon\Carbon::createFromFormat('d/m/Y', $dateStr) : \Carbon\Carbon::parse($dateStr);
          $diff = $today->diffInDays($d, false);
          if ($diff < 0)  return ['is-past',   'Hace ' . abs($diff) . ' días'];
          if ($diff === 0) return ['is-near',  'Hoy'];
          if ($diff <= 3)  return ['is-warning','En ' . $diff . ' día' . ($diff === 1 ? '' : 's')];
          return ['is-future', 'En ' . $diff . ' días'];
      } catch (\Throwable $e) {
          return ['', '—'];
      }
  };

  $pct = fn($n, $t) => $t > 0 ? round($n / $t * 100) : 0;
@endphp

<div class="pdb-wrap pjd-shell">

  {{-- ════════ NAVBAR ════════ --}}
  <div class="pdb-navbar">
    <a href="{{ route('projects.index') }}" class="pdb-nav-back" title="Volver">←</a>
    <div class="pdb-nav-name">{{ $project->name }}<span class="pdb-nav-name-dot"></span></div>

    <button type="button" class="pdb-nav-pill" data-workflow-label>{{ $estado['label'] }}</button>

    <div class="pdb-nav-tabs">
      <a href="{{ route('projects.show', $project) }}" class="pdb-nav-tab is-active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l9-8 9 8M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9"/></svg>
        Inicio
      </a>
      <a href="{{ route('projects.reports', $project) }}" class="pdb-nav-tab">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6"/></svg>
        Reportes
      </a>
    </div>
  </div>

  {{-- ════════ HERO: ESTADO + PIPELINE + MÓDULO SUGERIDO + CHECKLIST/INSIGHTS ════════ --}}
  <div class="pdb-hero">

    <div class="pdb-hero-top">
      <span class="pdb-estado-label">Estado:</span>
      <div class="pdb-status-wrap" id="pdbStatusWrap" data-workflow-url="{{ $workflowUrl }}" data-current-workflow="{{ $estado['key'] }}">
        <button type="button" class="pdb-estado-chip tone-{{ $estado['tone'] }}" id="pdbStatusBtn" data-workflow-chip aria-haspopup="true" aria-expanded="false">
          <span data-workflow-label>{{ $estado['label'] }}</span>
        </button>

        <div class="pdb-status-menu" id="pdbStatusMenu" aria-hidden="true">
          @foreach($workflowOptions as $key => $option)
            <button type="button"
                    class="pdb-status-option {{ ($option['child'] ?? false) ? 'is-child' : '' }} {{ $estado['key'] === $key ? 'is-active' : '' }}"
                    data-workflow-option="{{ $key }}"
                    data-workflow-label-value="{{ $option['label'] }}"
                    data-workflow-tone="{{ $option['tone'] }}"
                    data-workflow-step="{{ $option['step'] }}">
              <span class="pdb-status-dot" style="background: {{ $option['dot'] }}"></span>
              <span>{{ $option['label'] }}</span>
              <span class="pdb-status-check">✓</span>
            </button>
          @endforeach
        </div>
      </div>

      <div class="pdb-pipeline">
        {{-- Paso 1: Análisis --}}
        <div class="pdb-step tone-blue {{ $cur >= 1 ? 'is-on' : '' }}">
          <div class="pdb-step-circle">
            @if($cur > 1)
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            @else
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6"/></svg>
            @endif
          </div>
          <div class="pdb-step-label">Análisis</div>
        </div>

        <div class="pdb-step-line tone-blue {{ $cur >= 2 ? 'is-on' : '' }}"></div>

        {{-- Paso 2: Revisión --}}
        <div class="pdb-step tone-orange {{ $cur >= 2 ? 'is-on' : '' }}">
          <div class="pdb-step-circle">
            @if($cur > 2)
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            @else
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg>
            @endif
          </div>
          <div class="pdb-step-label">Revisión</div>
        </div>

        <div class="pdb-step-line tone-green {{ $cur >= 3 ? 'is-on' : '' }}"></div>

        {{-- Paso 3: Resultado / Participa --}}
        <div class="pdb-step tone-{{ $estado['tone'] }} {{ $cur >= 3 ? 'is-on is-ring' : '' }}" data-workflow-result-step>
          <div class="pdb-step-circle">
            @if($cur >= 3)
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            @else
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            @endif
          </div>
          <div class="pdb-step-label">{{ $cur >= 3 ? $estado['label'] : 'Resultado' }}</div>
        </div>
      </div>

      <button type="button" class="pdb-ask-monico"><span class="sparkle"><svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/><path d="M19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15Z"/></svg></span> ask monico</button>
      <button type="button" class="pdb-collapse-btn" title="Colapsar">⌃</button>
    </div>

    <div class="pdb-hero-grid">

      {{-- ── Columna izquierda: Módulo sugerido ── --}}
      <div class="pdb-hero-col">
        <h3 class="pdb-card-title"><span class="ico is-violet"><svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg></span> Módulo sugerido</h3>

        @php
          $analisisUrl = route('projects.analisis', $project);
          $checklistUrl = route('projects.analisis', $project) . '#checklist';
          $borradorUrl = route('projects.analisis', $project) . '#borrador';
          $reportesUrl = route('projects.reports', $project);

          $modulos = [
            ['key' => 'analisis',  'name' => 'Análisis de Bases',     'desc' => 'Revisa y analiza las bases del proyecto',   'tone' => 'tone-green',  'route' => $analisisUrl, 'available' => true,
             'svg' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6"/>'],
            ['key' => 'revision_checklist', 'name' => 'Revisión de Checklist', 'desc' => $hasChecklistDashboard ? ($checklistTotalDashboard . ' requisitos detectados desde Análisis') : 'Primero genera el checklist desde Análisis', 'tone' => 'tone-blue', 'route' => $checklistUrl, 'available' => $hasChecklistDashboard,
             'svg' => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>'],
            ['key' => 'juntas',    'name' => 'Junta de Aclaraciones', 'desc' => 'Gestiona preguntas y aclaraciones',          'tone' => 'tone-orange', 'route' => $analisisUrl . '#resumen', 'available' => in_array($estado['key'], ['participa','junta_aclaraciones','armado_propuesta','entrega','ganado']),
             'svg' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
            ['key' => 'propuesta', 'name' => 'Armado de Propuesta',   'desc' => 'Abre el checklist ligado desde Análisis para preparar la propuesta',   'tone' => 'tone-blue',   'route' => $checklistUrl, 'available' => $hasChecklistDashboard && in_array($estado['key'], ['armado_propuesta','entrega','ganado']),
             'svg' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>'],
            ['key' => 'reporte',   'name' => 'Reporte',               'desc' => 'Abre la vista de reportes del proyecto',       'tone' => 'tone-violet', 'route' => $reportesUrl, 'available' => true,
             'svg' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 17h6"/>'],
            ['key' => 'tecnico',   'name' => 'Análisis Técnico',      'desc' => 'Revisión técnica especializada',             'tone' => 'tone-warn',   'route' => $analisisUrl, 'available' => $hasChecklistDashboard,
             'svg' => '<path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/>'],
          ];

          // El módulo sugerido depende del estado y de si ya existe checklist real en Análisis.
          $sugerido = match($estado['key']) {
            'revision' => $hasChecklistDashboard ? 'revision_checklist' : 'analisis',
            'participa', 'junta_aclaraciones' => 'juntas',
            'armado_propuesta', 'entrega', 'ganado' => 'propuesta',
            default => $hasChecklistDashboard ? 'revision_checklist' : 'analisis',
          };

          usort($modulos, fn($a, $b) => ($a['key'] === $sugerido ? -1 : 1) <=> ($b['key'] === $sugerido ? -1 : 1));
        @endphp

        @foreach($modulos as $m)
          @php
            $isCur = $m['key'] === $sugerido;
            $isAvailable = (bool) ($m['available'] ?? false);
          @endphp
          <a href="{{ $m['route'] }}" class="pdb-module {{ $isCur ? 'is-current' : '' }} {{ !$isAvailable ? 'is-disabled' : '' }}" title="Abrir {{ $m['name'] }}">
            <div class="pdb-module-icon {{ $isCur || $isAvailable ? $m['tone'] : 'tone-gray' }}">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $m['svg'] !!}</svg>
            </div>
            <div class="pdb-module-text">
              <div class="pdb-module-name">{{ $m['name'] }}</div>
              <div class="pdb-module-desc">{{ $m['desc'] }}</div>
            </div>
            <div class="pdb-module-chev">›</div>
          </a>
        @endforeach
      </div>

      {{-- ── Columna derecha: Dictamen / Checklist / monico insights ── --}}
      <div class="pdb-hero-col">
        @if($showChecklistPanel)

          @php
            $t = $checklistTotalDashboard;
            $sinRevisar = $chk['sin_revisar'] ?? 0;
            $noCumple   = $chk['no_cumple']   ?? 0;
            $parcial    = $chk['parcial']     ?? 0;
            $cumple     = $chk['cumple']      ?? 0;
            $pendiente  = $chk['pendiente']   ?? 0;
            $enRevision = $chk['en_revision'] ?? 0;
            $aprobado   = $chk['aprobado']    ?? 0;
          @endphp
          <div class="pdb-chk-head">
            <h3 class="pdb-card-title" style="margin:0;"><span class="ico is-success"><svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg></span> Checklist</h3>
            <span class="badge-count">{{ $sinRevisar }} sin revisar</span>
            <a href="{{ route('projects.analisis', $project) }}#checklist" class="pdb-chk-link" title="Ver checklist en Análisis">↗</a>
          </div>

          <div class="pdb-chk-grid">
            <div class="pdb-chk-col">
              <div class="pdb-chk-row is-main"><span class="num c-gray">{{ $t }}</span><span class="lbl">Revisión</span><span class="pct">100%</span></div>
              <div class="pdb-chk-bar"><i style="width:{{ $pct($sinRevisar, $t) }}%"></i></div>
              <div class="pdb-chk-row"><span class="num c-gray">{{ $sinRevisar }}</span><span class="lbl">Sin revisar</span><span class="pct">{{ $pct($sinRevisar, $t) }}%</span></div>
              <div class="pdb-chk-row"><span class="num c-red">{{ $noCumple }}</span><span class="lbl">No Cumple</span><span class="pct">{{ $pct($noCumple, $t) }}%</span></div>
              <div class="pdb-chk-row"><span class="num c-orange">{{ $parcial }}</span><span class="lbl">Parcial</span><span class="pct">{{ $pct($parcial, $t) }}%</span></div>
              <div class="pdb-chk-row"><span class="num c-green">{{ $cumple }}</span><span class="lbl">Cumple</span><span class="pct">{{ $pct($cumple, $t) }}%</span></div>
            </div>
            <div class="pdb-chk-col">
              <div class="pdb-chk-row is-main"><span class="num c-gray">{{ $t }}</span><span class="lbl">Aprobación</span><span class="pct">100%</span></div>
              <div class="pdb-chk-bar"><i style="width:{{ $pct($pendiente, $t) }}%"></i></div>
              <div class="pdb-chk-row"><span class="num c-gray">{{ $pendiente }}</span><span class="lbl">Pendiente</span><span class="pct">{{ $pct($pendiente, $t) }}%</span></div>
              <div class="pdb-chk-row"><span class="num c-gray">{{ $enRevision }}</span><span class="lbl">En revisión</span><span class="pct">{{ $pct($enRevision, $t) }}%</span></div>
              <div class="pdb-chk-row"><span class="num c-green">{{ $aprobado }}</span><span class="lbl">Aprobado</span><span class="pct">{{ $pct($aprobado, $t) }}%</span></div>
            </div>
            <div class="pdb-chk-total"><strong>{{ $t }}</strong><span>Total</span></div>
          </div>
        @elseif($showResultDictamen)
          <div class="pdb-result-panel" id="pdbResultPanel" data-project-key="{{ $project->slug }}" data-initial-mode="{{ $resultInitialMode }}">
            <div class="pdb-result-head">
              <h3 class="pdb-result-title">
                <span class="ico"><svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h4"/></svg></span>
                Dictamen del Resultado
              </h3>
              <a href="{{ route('projects.analisis', $project) }}#checklist" class="pdb-result-link">Ver checklist → <svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3h7v7"/><path d="M10 14 21 3"/><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/></svg></a>
            </div>

            <div class="pdb-result-topline">
              <span class="pdb-result-badge" id="pdbResultBadge">{{ $resultInitialBadge }}</span>
              <span class="pdb-result-stats" id="pdbResultStats">0 ganadas · 0 perdidas</span>
            </div>

            <div class="pdb-result-toolbar">
              <label class="pdb-result-field">
                Adjudicación:
                <select class="pdb-result-select" id="pdbResultMode">
                  <option value="total" {{ $resultInitialMode === 'total' ? 'selected' : '' }}>Total</option>
                  <option value="parcial" {{ $resultInitialMode === 'parcial' ? 'selected' : '' }}>Parcial</option>
                  <option value="perdido" {{ $resultInitialMode === 'perdido' ? 'selected' : '' }}>Perdido</option>
                </select>
              </label>

              <button type="button" class="pdb-result-add" id="pdbResultAdd">
                <svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Partida
              </button>
            </div>

            <div class="pdb-result-list" id="pdbResultList"></div>

            <div class="pdb-result-summary">
              <div class="pdb-result-summary-head">
                <div class="pdb-result-summary-title">Resumen</div>
                <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
              </div>
              <textarea class="pdb-result-textarea" id="pdbResultSummary" placeholder="Clic para documentar..."></textarea>
            </div>

            <div class="pdb-result-tip" id="pdbResultTip">
              <svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/></svg>
              <span><strong>tip:</strong> Documenta los factores de éxito.</span>
            </div>

            <div class="pdb-result-footer">
              <span>Siempre podrás actualizar después</span>
              <button type="button" class="pdb-result-confirm" id="pdbResultConfirm">
                <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                Confirmar
              </button>
            </div>
          </div>
        @elseif($estado['key'] === 'no_participa')
          <div class="pdb-decline-panel is-readonly" id="pdbDeclinePanel" data-decline-url="{{ $noParticipaUrl }}">
            <div class="pdb-decline-head">
              <h3 class="pdb-decline-title">
                <span class="ico is-violet"><svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h4"/></svg></span>
                Dictamen del Resultado
              </h3>
              <a href="{{ route('projects.analisis', $project) }}#checklist" class="pdb-decline-link">Ver checklist → ↗</a>
            </div>

            <div class="pdb-decline-badge">NO PARTICIPA</div>

            <label class="pdb-decline-label" for="pdbDeclineReason">Motivo</label>
            <div class="pdb-decline-field">
              <button type="button" class="pdb-decline-edit-icon" id="pdbDeclineEdit" title="Editar motivo" aria-label="Editar motivo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
              </button>
              <div class="pdb-decline-display {{ trim((string) $noParticipaReason) === '' ? 'is-placeholder' : '' }}" id="pdbDeclineDisplay" tabindex="0">{{ trim((string) $noParticipaReason) !== '' ? $noParticipaReason : 'Clic para documentar...' }}</div>
              <textarea class="pdb-decline-textarea" id="pdbDeclineReason" placeholder="¿Por qué no participamos?">{{ $noParticipaReason }}</textarea>
            </div>

            <div class="pdb-decline-actions" id="pdbDeclineActions">
              <button type="button" class="pdb-decline-btn is-primary" id="pdbDeclineSave"><svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg> Guardar</button>
              <button type="button" class="pdb-decline-btn" id="pdbDeclineCancel">× Cancelar</button>
            </div>

            <div class="pdb-decline-tip"><strong>tip:</strong> Documenta los motivos de la decisión.</div>

            <div class="pdb-decline-footer">
              <span>
                @if($noParticipaConfirmedAt)
                  Confirmado el {{ $noParticipaConfirmedAt }}@if($noParticipaConfirmedBy) por {{ $noParticipaConfirmedBy }}@endif
                @else
                  Siempre podrás actualizar después
                @endif
              </span>
              <button type="button" class="pdb-decline-btn is-primary" id="pdbDeclineConfirm"><svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg> Confirmar</button>
            </div>
          </div>
        @else
          <h3 class="pdb-card-title"><span class="ico is-violet"><svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/><path d="M19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15Z"/></svg></span> monico insights</h3>
          <div class="pdb-insights-empty">
            <p>Acciones sugeridas:</p>
            <div class="pdb-insight-actions">
              <button type="button" class="pdb-insight-action js-monico-generate" data-monico-type="resumen">
                <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3h7v7"/><path d="M10 14 21 3"/><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/></svg>
                <span>Revisar Resumen Ejecutivo</span>
                <small>Ir →</small>
              </button>
              <button type="button" class="pdb-insight-action js-monico-generate" data-monico-type="reporte">
                <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3h7v7"/><path d="M10 14 21 3"/><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/></svg>
                <span>Generar Reporte de Análisis</span>
                <small>Ir →</small>
              </button>
              <button type="button" class="pdb-insight-action js-monico-generate" data-monico-type="participacion">
                <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3h7v7"/><path d="M10 14 21 3"/><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/></svg>
                <span>Definir Participación</span>
                <small>Ir →</small>
              </button>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>

  {{-- ════════ FILA 2: NOTAS + TAREAS ════════ --}}
  <div class="pdb-grid">
    <div class="pdb-card pdb-work-card" id="pdbNotesCard"
         data-store-url="{{ route('projects.notes.store', $project) }}"
         data-users='@json($dashboardUsers->map(fn($u) => ["id" => $u->id, "name" => $u->name, "email" => $u->email])->values())'>
      <div class="pdb-work-head">
        <div class="pdb-work-title-wrap">
          <span class="pdb-work-ico">
            <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <path d="M14 2v6h6"/>
            </svg>
          </span>
          <div>
            <h3 class="pdb-work-title">Notas del Proyecto</h3>
            <p class="pdb-work-sub"><span id="pdbNotesCount">{{ $notas->count() }}</span> {{ $notas->count() === 1 ? 'nota' : 'notas' }}</p>
          </div>
        </div>
      </div>

      <div class="pdb-work-body">
        <div class="pdb-notes-list" id="pdbNotesList">
          @forelse($notas as $nota)
            @php
              $noteUserName = $nota->user_name ?? optional($nota->user)->name ?? 'Usuario';
              $noteUserInitial = mb_strtoupper(mb_substr($noteUserName, 0, 1, 'UTF-8'), 'UTF-8');
              $noteDate = $nota->created_at instanceof \Carbon\Carbon
                ? $nota->created_at->format('j M Y')
                : \Carbon\Carbon::parse($nota->created_at)->format('j M Y');
            @endphp
            <div class="pdb-note-card" data-note-id="{{ $nota->id }}" data-content="{{ e($nota->content) }}" data-update-url="{{ route('projects.notes.update', [$project, $nota->id]) }}" data-convert-url="{{ route('projects.notes.convert-task', [$project, $nota->id]) }}" data-pin-url="{{ route('projects.notes.pin', [$project, $nota->id]) }}" data-delete-url="{{ route('projects.notes.destroy', [$project, $nota->id]) }}">
              <span class="pdb-note-avatar">{{ $noteUserInitial }}</span>
              <div class="pdb-note-main">
                <div class="pdb-note-top">
                  <span class="pdb-note-author">{{ $noteUserName }}</span>
                  <span class="pdb-note-date">
                    <svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="9"/>
                      <path d="M12 7v5l3 2"/>
                    </svg>
                    {{ $noteDate }}
                  </span>
                </div>
                <div class="pdb-note-body">{!! preg_replace('/@([\pL\pN\s\.]+?)(?=\s|$)/u', '<span class="pdb-mention">@$1</span>', e($nota->content)) !!}</div>
              </div>
              <div class="pdb-action-shell">
                <button type="button" class="pdb-note-menu js-open-action-menu" title="Opciones de nota" aria-label="Opciones de nota">
                  <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                    <path d="M12 5h.01M12 12h.01M12 19h.01" stroke-linecap="round"/>
                  </svg>
                </button>
                <div class="pdb-action-menu">
                  <button type="button" class="pdb-action-item js-edit-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    Editar
                  </button>
                  <button type="button" class="pdb-action-item js-note-to-task">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l2 2 4-5"/><path d="M5 5h.01M5 12h.01M5 19h.01"/><path d="M9 5h10M9 12h10M9 19h10"/></svg>
                    Convertir en Tarea
                  </button>
                  <button type="button" class="pdb-action-item js-pin-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m14 4 6 6-4 1-5 5-1 4-6-6 4-1 5-5 1-4Z"/><path d="m9 15-5 5"/></svg>
                    Fijar Nota
                  </button>
                  <button type="button" class="pdb-action-item is-danger js-delete-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16"/><path d="M9 7V5h6v2"/><path d="m8 7 1 13h6l1-13"/></svg>
                    Eliminar
                  </button>
                </div>
              </div>
            </div>
          @empty
            <div class="pdb-empty-state" id="pdbNotesEmpty">No hay notas aún</div>
          @endforelse
        </div>

        <div class="pdb-note-input-wrap">
          <input type="text" class="pdb-input" id="pdbNoteInput" placeholder="Agrega una nota y menciona a alguien con @..." autocomplete="off">
          <div class="pdb-mention-pop" id="pdbMentionPop"></div>
          <button type="button" class="pdb-add-btn" id="pdbAddNoteBtn" title="Agregar nota">
            <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
              <path d="M12 5v14M5 12h14"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <div class="pdb-card pdb-work-card" id="pdbTasksCard"
         data-store-url="{{ route('projects.tasks.store', $project) }}"
         data-users='@json($dashboardUsers->map(fn($u) => ["id" => $u->id, "name" => $u->name, "email" => $u->email])->values())'>
      <div class="pdb-work-head">
        <div class="pdb-work-title-wrap">
          <span class="pdb-work-ico is-violet">
            <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M9 11l2 2 4-5"/>
              <path d="M5 5h.01M5 12h.01M5 19h.01"/>
              <path d="M9 5h10M9 12h10M9 19h10"/>
            </svg>
          </span>
          <div>
            <h3 class="pdb-work-title">Tareas del Proyecto</h3>
            <p class="pdb-work-sub"><span id="pdbTasksPending">{{ $tareasPend }}</span> pendientes · <span id="pdbTasksDone">{{ $tareasDone }}</span> completadas</p>
          </div>
        </div>

        <div class="pdb-work-actions">
          <button type="button" class="pdb-icon-btn js-sort-tasks" data-sort="az" title="Ordenar A-Z">
            <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 6h8M4 12h5M4 18h5"/>
              <path d="M16 5v14M16 19l4-4M16 19l-4-4"/>
            </svg>
          </button>
          <button type="button" class="pdb-icon-btn js-sort-tasks" data-sort="za" title="Ordenar Z-A">
            <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 6h8M4 12h5M4 18h5"/>
              <path d="M16 19V5M16 5l4 4M16 5l-4 4"/>
            </svg>
          </button>
          <select class="pdb-filter-menu" id="pdbTaskFilter">
            <option value="pending">Pendientes ({{ $tareasPend }})</option>
            <option value="all">Todas</option>
            <option value="completed">Completadas</option>
          </select>
        </div>
      </div>

      <div class="pdb-work-body">
        <div class="pdb-tasks-list" id="pdbTasksList">
          @forelse($tareas as $task)
            @php
              $taskCompleted = (bool) ($task->completed ?? false);
              $taskPriority = $task->priority ?? 'normal';
              $taskAssigned = $task->assigned_name ?? null;
              $taskDue = $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d M') : 'Sin fecha';
            @endphp
            <div class="pdb-task-row {{ $taskCompleted ? 'is-completed' : '' }}"
                 data-task-id="{{ $task->id }}"
                 data-title="{{ e($task->title) }}"
                 data-completed="{{ $taskCompleted ? '1' : '0' }}"
                 data-priority="{{ $taskPriority }}"
                 data-update-url="{{ route('projects.tasks.update', [$project, $task->id]) }}"
                 data-convert-url="{{ route('projects.tasks.convert-note', [$project, $task->id]) }}"
                 data-pin-url="{{ route('projects.tasks.pin', [$project, $task->id]) }}"
                 data-archive-url="{{ route('projects.tasks.archive', [$project, $task->id]) }}"
                 data-delete-url="{{ route('projects.tasks.destroy', [$project, $task->id]) }}">
              <button type="button" class="pdb-task-check js-toggle-task {{ $taskCompleted ? 'is-completed' : '' }}" title="Marcar tarea">
                <svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                  <path d="m5 12 4 4L19 6"/>
                </svg>
              </button>
              <div>
                <p class="pdb-task-title">{{ $task->title }}</p>
                <div class="pdb-task-meta">
                  <button type="button" class="pdb-task-chip is-priority-{{ $taskPriority }} js-cycle-priority">
                    <svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M5 21V4h10l1 3h4v9h-9l-1-3H5"/>
                    </svg>
                    <span>{{ ['alta'=>'Alta','media'=>'Media','baja'=>'Baja','normal'=>'Sin prioridad'][$taskPriority] ?? 'Sin prioridad' }}</span>
                  </button>
                  <button type="button" class="pdb-task-chip js-assign-task">
                    <svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/>
                      <circle cx="10" cy="7" r="4"/>
                    </svg>
                    <span>{{ $taskAssigned ? Str::limit($taskAssigned, 22) : 'Asignar' }}</span>
                  </button>
                  <button type="button" class="pdb-task-chip js-date-task">
                    <svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M7 3v4M17 3v4M4 9h16"/>
                      <rect x="4" y="5" width="16" height="16" rx="2"/>
                    </svg>
                    <span>{{ $taskDue }}</span>
                  </button>
                </div>
              </div>
              <div class="pdb-action-shell">
                <button type="button" class="pdb-task-menu js-open-action-menu" title="Opciones de tarea" aria-label="Opciones de tarea">
                  <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                    <path d="M12 5h.01M12 12h.01M12 19h.01" stroke-linecap="round"/>
                  </svg>
                </button>
                <div class="pdb-action-menu">
                  <button type="button" class="pdb-action-item js-edit-task">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    Editar
                  </button>
                  <button type="button" class="pdb-action-item js-task-to-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                    Convertir en Nota
                  </button>
                  <button type="button" class="pdb-action-item js-pin-task">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m14 4 6 6-4 1-5 5-1 4-6-6 4-1 5-5 1-4Z"/><path d="m9 15-5 5"/></svg>
                    Fijar Tarea
                  </button>
                  <button type="button" class="pdb-action-item js-archive-task">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h18"/><path d="M5 7h14v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7Z"/><path d="M9 11h6"/></svg>
                    Archivar
                  </button>
                  <button type="button" class="pdb-action-item is-danger js-delete-task">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16"/><path d="M9 7V5h6v2"/><path d="m8 7 1 13h6l1-13"/></svg>
                    Eliminar
                  </button>
                </div>
              </div>
            </div>
          @empty
            <div class="pdb-empty-state" id="pdbTasksEmpty">No hay tareas pendientes</div>
          @endforelse
        </div>

        <div class="pdb-task-input-wrap">
          <input type="text" class="pdb-input" id="pdbTaskInput" placeholder="Agregar nueva tarea..." autocomplete="off">
          <button type="button" class="pdb-add-btn" id="pdbAddTaskBtn" title="Agregar tarea">
            <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
              <path d="M12 5v14M5 12h14"/>
            </svg>
          </button>
        </div>

        <div class="pdb-task-create-options">
          <select class="pdb-task-select" id="pdbTaskPriority">
            <option value="normal">Sin prioridad</option>
            <option value="alta">Alta</option>
            <option value="media">Media</option>
            <option value="baja">Baja</option>
          </select>

          <select class="pdb-task-select" id="pdbTaskAssignee">
            <option value="">Asignar</option>
            @foreach($dashboardUsers as $user)
              <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
          </select>

          <input type="date" class="pdb-task-date" id="pdbTaskDueDate">
        </div>
      </div>
    </div>
  </div>

  {{-- ════════ FILA 3: RESUMEN DOCS + INFO GENERAL ════════ --}}
  <div class="pdb-grid">

    <div class="pdb-card">
      <h3 class="pdb-card-title"><span class="ico"><svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10.5" r="1.5"/><path d="M21 16l-5-5L5 19"/></svg></span> Resumen de Documentos</h3>

      {{-- Bases --}}
      <div class="pdb-doc-group tone-blue {{ $bases->count() ? 'is-open' : '' }}">
        <div class="pdb-doc-group-head js-doc-toggle">
          <div class="doc-ico"><svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h4"/></svg></div>
          <div class="doc-name">Bases</div>
          <div class="doc-count">{{ $bases->count() }}</div>
          <div class="doc-chev">⌄</div>
        </div>
        <div class="pdb-doc-group-body">
          @forelse($bases as $f)
            <div class="pdb-doc-file">
              <div class="file-ico"><svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h4"/></svg></div>
              <div class="file-info">
                <div class="file-name">{{ $f->filename }}</div>
                <div class="file-status {{ $f->status === 'procesado' ? '' : ($f->status === 'pendiente' ? 'is-pending' : 'is-error') }}">
                  {{ $f->status === 'procesado' ? 'Completado' : ucfirst($f->status) }}
                </div>
              </div>
              <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($f->file_path) }}" target="_blank" class="file-open" title="Abrir">↗</a>
            </div>
          @empty
            <div style="padding:10px 12px;color:var(--muted);font-size:.85rem;">Sin archivos</div>
          @endforelse
        </div>
      </div>

      {{-- Juntas de Aclaraciones --}}
      <div class="pdb-doc-group tone-green {{ $juntas->count() ? 'is-open' : '' }}">
        <div class="pdb-doc-group-head js-doc-toggle">
          <div class="doc-ico"><svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h4"/></svg></div>
          <div class="doc-name">Juntas de Aclaraciones</div>
          <div class="doc-count">{{ $juntas->count() }}</div>
          <div class="doc-chev">⌄</div>
        </div>
        <div class="pdb-doc-group-body">
          @forelse($juntas as $f)
            <div class="pdb-doc-file">
              <div class="file-ico"><svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h4"/></svg></div>
              <div class="file-info">
                <div class="file-name">{{ $f->filename }}</div>
                <div class="file-status {{ $f->status === 'procesado' ? '' : 'is-pending' }}">{{ $f->status === 'procesado' ? 'Completado' : ucfirst($f->status) }}</div>
              </div>
              <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($f->file_path) }}" target="_blank" class="file-open" title="Abrir">↗</a>
            </div>
          @empty
            <div style="padding:10px 12px;color:var(--muted);font-size:.85rem;">Sin archivos</div>
          @endforelse
        </div>
      </div>

      {{-- Documentación Regulatoria --}}
      <div class="pdb-doc-group tone-red {{ $regdocs->count() ? 'is-open' : '' }}">
        <div class="pdb-doc-group-head js-doc-toggle">
          <div class="doc-ico"><svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h4"/></svg></div>
          <div class="doc-name">Documentación Regulatoria</div>
          <div class="doc-count">{{ $regdocs->count() }}</div>
          <div class="doc-chev">⌄</div>
        </div>
        <div class="pdb-doc-group-body">
          @forelse($regdocs as $f)
            <div class="pdb-doc-file">
              <div class="file-ico"><svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h4"/></svg></div>
              <div class="file-info">
                <div class="file-name">{{ $f->filename }}</div>
                <div class="file-status {{ $f->status === 'procesado' ? '' : 'is-pending' }}">{{ $f->status === 'procesado' ? 'Completado' : ucfirst($f->status) }}</div>
              </div>
              <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($f->file_path) }}" target="_blank" class="file-open" title="Abrir">↗</a>
            </div>
          @empty
            <div style="padding:10px 12px;color:var(--muted);font-size:.85rem;">Sin archivos</div>
          @endforelse
        </div>
      </div>

      <div class="pdb-doc-total">
        <span>Total de Documentos</span>
        <strong>{{ $docs->count() }}</strong>
      </div>
    </div>

    <div class="pdb-card">
      <h3 class="pdb-card-title"><span class="ico is-warning">ⓘ</span> Información General</h3>

      <div class="pdb-info-row">
        <span class="pdb-info-lbl">Fecha de inicio</span>
        <span class="pdb-info-val"><span class="ico-edit"><svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/></svg></span> {{ optional($project->start_date)->format('d M Y') ?: $project->created_at->format('d M Y') }}</span>
      </div>
      <div class="pdb-info-row">
        <span class="pdb-info-lbl">Fecha de creación</span>
        <span class="pdb-info-val">{{ $project->created_at->format('d M Y') }}</span>
      </div>
      <div class="pdb-info-row">
        <span class="pdb-info-lbl">Usuario asignado</span>
        <span class="pdb-info-val">
          <span class="pdb-avatar">{{ strtoupper(substr(optional($project->user)->name ?? 'U', 0, 1)) }}</span>
          {{ optional($project->user)->name ?? 'Sin asignar' }}
        </span>
      </div>
      <div class="pdb-info-row">
        <span class="pdb-info-lbl">Última actividad</span>
        <span class="pdb-info-val"><svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg> Hace {{ (int) $project->updated_at->copy()->startOfDay()->diffInDays(now()->startOfDay()) }} días</span>
      </div>
      <div class="pdb-info-row" style="flex-direction:column;align-items:flex-start;gap:8px;">
        <span class="pdb-info-lbl">Etiquetas</span>
        <div class="pdb-tags-shell" id="pdbTagsShell" data-labels-url="{{ $labelsUrl }}" data-labels='@json($projectLabels)' data-common-labels='@json($commonLabels)'>
          <div class="pdb-tags" id="pdbTagsList">
            @forelse($projectLabels as $tag)
              <span class="pdb-tag-pill" data-tag-pill="{{ e($tag) }}">{{ $tag }} <button type="button" data-remove-tag="{{ e($tag) }}" aria-label="Eliminar etiqueta {{ $tag }}">×</button></span>
            @empty
              <span style="color:var(--muted);font-size:.82rem;font-weight:600;" data-tags-empty>Sin etiquetas</span>
            @endforelse
            <button type="button" class="pdb-tag-add" id="pdbTagsToggle">+ Agregar</button>
          </div>

          <div class="pdb-tags-popover" id="pdbTagsPopover" aria-hidden="true">
            <label class="pdb-tags-search" for="pdbTagsSearch">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
              <input type="text" id="pdbTagsSearch" placeholder="Buscar o crear etiqueta..." autocomplete="off">
            </label>

            <div class="pdb-tags-sep"></div>

            <div class="pdb-tag-create-row" id="pdbTagCreateRow">
              <span class="plus">+</span>
              <button type="button" class="pdb-tag-create" id="pdbTagCreateBtn">Crear</button>
              <span class="pdb-tag-preview" id="pdbTagPreview"></span>
            </div>

            <div id="pdbCommonTagsBlock">
              <div class="pdb-tags-title">Etiquetas comunes:</div>
              <div class="pdb-common-tags" id="pdbCommonTags">
                @foreach($commonLabels as $tag)
                  <button type="button" class="pdb-common-tag {{ in_array($tag, $projectLabels, true) ? 'is-selected' : '' }}" data-common-tag="{{ e($tag) }}">{{ $tag }}</button>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ════════ FILA 4: FICHA TÉCNICA + FECHAS CLAVE ════════ --}}
  <div class="pdb-grid">

    <div class="pdb-card pdb-ref-card">
      <div class="pdb-ref-head">
        <h3 class="pdb-ref-title">
          <span class="ico">
            <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <path d="M14 2v6h6"/>
              <path d="M9 13h6M9 17h4"/>
            </svg>
          </span>
          Ficha Técnica
          <span class="pdb-ref-sparkle">
            <svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/>
              <path d="M19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15Z"/>
            </svg>
          </span>
        </h3>
        <a href="{{ route('projects.analisis', $project) }}" class="pdb-ref-open" title="Ver completa">
          <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 3h7v7"/>
            <path d="M10 14 21 3"/>
            <path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/>
          </svg>
        </a>
      </div>

      <div class="pdb-ref-body">
        <div class="pdb-field">
          <div class="pdb-field-lbl">Tipo de evento</div>
          <div class="pdb-field-val">{{ $ficha['tipo_evento'] ?? '—' }}</div>
        </div>
        <div class="pdb-field">
          <div class="pdb-field-lbl">Organismo</div>
          <div class="pdb-field-val">{{ $ficha['organismo'] ?? '—' }}</div>
        </div>
        <div class="pdb-field">
          <div class="pdb-field-lbl">¿Cuál es el objeto de la licitación?</div>
          <div class="pdb-field-val">{{ $ficha['objeto'] ?? $ficha['objeto_licitacion'] ?? 'No se encontró información sobre objeto' }}</div>
        </div>
        <div class="pdb-field">
          <div class="pdb-field-lbl">¿Cuál es el medio de participación?</div>
          <div class="pdb-field-val">{{ $ficha['medio_participacion'] ?? $ficha['medio_de_participacion'] ?? $ficha['tipo_evento'] ?? '—' }}</div>
        </div>
      </div>
    </div>

    <div class="pdb-card pdb-ref-card">
      <div class="pdb-ref-head">
        <h3 class="pdb-ref-title">
          <span class="ico is-success">
            <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M7 3v4M17 3v4M4 9h16"/>
              <rect x="4" y="5" width="16" height="16" rx="2"/>
              <path d="M8 13h3M13 13h3M8 17h3"/>
            </svg>
          </span>
          Fechas Clave
          <span class="pdb-ref-sparkle">
            <svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/>
              <path d="M19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15Z"/>
            </svg>
          </span>
        </h3>
        <a href="{{ route('projects.analisis', $project) }}" class="pdb-ref-open" title="Ver completas">
          <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 3h7v7"/>
            <path d="M10 14 21 3"/>
            <path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/>
          </svg>
        </a>
      </div>

      @php
        $fechasRows = [
          ['k' => 'fecha_publicacion',     'label' => 'Fecha de publicación'],
          ['k' => 'junta_aclaraciones',    'label' => 'Junta de aclaraciones'],
          ['k' => 'presentacion_apertura', 'label' => 'Presentación y apertura de proposiciones'],
          ['k' => 'fallo',                 'label' => 'Fallo'],
          ['k' => 'vigencia_contrato',     'label' => 'Vigencia del contrato'],
        ];
      @endphp

      <div class="pdb-date-list">
        @foreach($fechasRows as $r)
          @php
            $val = $fechas[$r['k']] ?? null;
            [$badgeCls, $badgeTxt] = $badgeFor($val);
          @endphp
          <div class="pdb-date-row">
            <div class="pdb-date-ico">
              <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M7 3v4M17 3v4M4 9h16"/>
                <rect x="4" y="5" width="16" height="16" rx="2"/>
                <path d="M8 13h3M13 13h3M8 17h3"/>
              </svg>
            </div>
            <div class="pdb-date-body">
              <div class="pdb-date-title">{{ $r['label'] }}</div>
              <div class="pdb-date-sub">{{ $val ?: 'Sin dato' }}</div>
            </div>
            @if($val)
              <span class="pdb-date-badge {{ $badgeCls }}">{{ $badgeTxt }}</span>
            @else
              <span class="pdb-date-empty">—</span>
            @endif
          </div>
        @endforeach
      </div>
    </div>
  </div>

</div>

<div class="pdb-monico-panel" id="pdbMonicoPanel" aria-hidden="true">
  <div class="pdb-monico-head">
    <span class="pdb-icon-box">
      <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/>
        <path d="M19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15Z"/>
      </svg>
    </span>
    <div class="pdb-monico-head-title"><span id="pdbMonicoTitle">Resumen Ejecutivo</span></div>
    <span class="pdb-monico-by">por monico</span>
    <div class="pdb-monico-head-actions">
      <button type="button" class="pdb-monico-icon-btn" id="pdbMonicoCollapse" title="Colapsar">
        <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18 15-6-6-6 6"/></svg>
      </button>
      <button type="button" class="pdb-monico-icon-btn" id="pdbMonicoClose" title="Cerrar">
        <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"/></svg>
      </button>
    </div>
  </div>
  <div class="pdb-monico-body" id="pdbMonicoBody">
    <div class="pdb-monico-content" id="pdbMonicoContent"></div>
    <div class="pdb-monico-actions">
      <button type="button" class="pdb-monico-action" id="pdbMonicoSaveNote">
        <svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
        Guardar nota
      </button>
      <button type="button" class="pdb-monico-action" id="pdbMonicoCopy">
        <svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
        Copiar
      </button>
      <button type="button" class="pdb-monico-action" id="pdbMonicoCloseBottom">Cerrar</button>
    </div>
  </div>
</div>

<script>
(function () {
  'use strict';

  const CSRF_TOKEN = '{{ csrf_token() }}';

  // ============ MONICO PANEL GENERADO ============
  const monicoPanel = document.getElementById('pdbMonicoPanel');
  const monicoTitle = document.getElementById('pdbMonicoTitle');
  const monicoBody = document.getElementById('pdbMonicoBody');
  const monicoContent = document.getElementById('pdbMonicoContent');
  const monicoClose = document.getElementById('pdbMonicoClose');
  const monicoCloseBottom = document.getElementById('pdbMonicoCloseBottom');
  const monicoCollapse = document.getElementById('pdbMonicoCollapse');
  const monicoCopy = document.getElementById('pdbMonicoCopy');
  const monicoSaveNote = document.getElementById('pdbMonicoSaveNote');

  const monicoContext = {
    project: @json($project->name),
    status: @json($estado['label']),
    organismo: @json($ficha['organismo'] ?? null),
    tipo: @json($ficha['tipo_evento'] ?? null),
    objeto: @json($ficha['objeto'] ?? $ficha['objeto_licitacion'] ?? null),
    medio: @json($ficha['medio_participacion'] ?? $ficha['medio_de_participacion'] ?? null),
    documentos: {{ (int) $docs->count() }},
    requisitos: {{ (int) $checklistTotalDashboard }},
    cumple: {{ (int) ($chk['cumple'] ?? 0) }},
    parcial: {{ (int) ($chk['parcial'] ?? 0) }},
    noCumple: {{ (int) ($chk['no_cumple'] ?? 0) }},
    sinRevisar: {{ (int) ($chk['sin_revisar'] ?? 0) }},
  };

  let monicoPlainText = '';

  function monicoEscape(value) {
    return String(value || '').replace(/[&<>"']/g, char => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    }[char]));
  }

  function setMonicoOpen(open) {
    if (!monicoPanel) return;
    monicoPanel.classList.toggle('is-open', open);
    monicoPanel.setAttribute('aria-hidden', open ? 'false' : 'true');

    if (open) {
      monicoPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function setMonicoLoading(title) {
    if (!monicoTitle || !monicoContent) return;
    monicoTitle.textContent = title;
    monicoContent.innerHTML = `
      <div class="pdb-monico-loading">
        <div class="pdb-monico-skeleton"></div>
        <div class="pdb-monico-skeleton"></div>
        <div class="pdb-monico-skeleton"></div>
      </div>
    `;
    setMonicoOpen(true);
  }

  function buildMonicoContent(type = 'resumen') {
    const c = monicoContext;
    const organismo = c.organismo || 'organismo no identificado';
    const tipo = c.tipo || 'tipo de procedimiento no identificado';
    const objeto = c.objeto || 'objeto no identificado en los documentos';
    const medio = c.medio || 'medio de participación pendiente de confirmar';

    if (type === 'reporte') {
      return {
        title: 'Reporte de Análisis',
        plain: `Reporte de análisis - ${c.project}\n\nOrganismo: ${organismo}\nTipo de evento: ${tipo}\nObjeto: ${objeto}\nEstado actual: ${c.status}\nDocumentos cargados: ${c.documentos}\nRequisitos detectados: ${c.requisitos}\nCumple: ${c.cumple}\nParcial: ${c.parcial}\nNo cumple: ${c.noCumple}\nSin revisar: ${c.sinRevisar}\n\nAcción sugerida: revisar requisitos sin revisar y confirmar participación antes de avanzar de etapa.`,
        html: `
          <p><strong>Reporte de análisis generado para:</strong> ${monicoEscape(c.project)}</p>
          <ul>
            <li><strong>Organismo:</strong> ${monicoEscape(organismo)}</li>
            <li><strong>Tipo de evento:</strong> ${monicoEscape(tipo)}</li>
            <li><strong>Objeto:</strong> ${monicoEscape(objeto)}</li>
            <li><strong>Estado actual:</strong> ${monicoEscape(c.status)}</li>
            <li><strong>Documentos cargados:</strong> ${c.documentos}</li>
            <li><strong>Checklist:</strong> ${c.requisitos} requisitos · ${c.cumple} cumple · ${c.parcial} parcial · ${c.noCumple} no cumple · ${c.sinRevisar} sin revisar</li>
          </ul>
        `,
      };
    }

    if (type === 'participacion') {
      const risk = c.noCumple > 0 || c.sinRevisar > 0 ? 'requiere revisión antes de confirmar participación' : 'tiene condiciones favorables para continuar';
      return {
        title: 'Definir Participación',
        plain: `Definir participación - ${c.project}\n\nCon la información disponible, el proyecto ${risk}.\n\nPuntos a revisar:\n- Requisitos sin revisar: ${c.sinRevisar}\n- Requisitos no cumple: ${c.noCumple}\n- Requisitos parciales: ${c.parcial}\n\nRecomendación: documentar decisión y avanzar a Revisión si el análisis de requisitos está completo.`,
        html: `
          <p><strong>Lectura rápida:</strong> el proyecto ${monicoEscape(risk)}.</p>
          <ul>
            <li><strong>Sin revisar:</strong> ${c.sinRevisar}</li>
            <li><strong>No cumple:</strong> ${c.noCumple}</li>
            <li><strong>Parcial:</strong> ${c.parcial}</li>
            <li><strong>Medio:</strong> ${monicoEscape(medio)}</li>
          </ul>
          <p>Recomendación: documentar la decisión y avanzar a <strong>Revisión</strong> cuando el checklist esté validado.</p>
        `,
      };
    }

    return {
      title: 'Resumen Ejecutivo',
      plain: `Resumen ejecutivo - ${c.project}\n\n${c.project} corresponde a ${tipo} para ${organismo}. El objeto identificado es: ${objeto}.\n\nEstado actual: ${c.status}.\nDocumentos cargados: ${c.documentos}.\nChecklist: ${c.requisitos} requisitos detectados.\n\nSiguiente paso sugerido: revisar resumen ejecutivo, validar checklist y definir participación.`,
      html: `
        <p><strong>${monicoEscape(c.project)}</strong> corresponde a <strong>${monicoEscape(tipo)}</strong> para <strong>${monicoEscape(organismo)}</strong>.</p>
        <p><strong>Objeto:</strong> ${monicoEscape(objeto)}.</p>
        <ul>
          <li><strong>Estado actual:</strong> ${monicoEscape(c.status)}</li>
          <li><strong>Documentos cargados:</strong> ${c.documentos}</li>
          <li><strong>Requisitos detectados:</strong> ${c.requisitos}</li>
        </ul>
        <p>Siguiente paso sugerido: revisar el resumen, validar checklist y definir participación.</p>
      `,
    };
  }

  function generateMonico(type = 'resumen') {
    const result = buildMonicoContent(type);
    setMonicoLoading(result.title);

    window.setTimeout(() => {
      monicoPlainText = result.plain;
      if (monicoTitle) monicoTitle.textContent = result.title;
      if (monicoContent) monicoContent.innerHTML = result.html;
    }, 450);
  }

  document.querySelectorAll('.pdb-ask-monico, .pdb-insights-btn').forEach(button => {
    button.addEventListener('click', () => generateMonico('resumen'));
  });

  document.querySelectorAll('.js-monico-generate').forEach(button => {
    button.addEventListener('click', () => generateMonico(button.dataset.monicoType || 'resumen'));
  });

  monicoClose?.addEventListener('click', () => setMonicoOpen(false));
  monicoCloseBottom?.addEventListener('click', () => setMonicoOpen(false));

  monicoCollapse?.addEventListener('click', () => {
    if (!monicoBody) return;
    monicoBody.style.display = monicoBody.style.display === 'none' ? '' : 'none';
  });

  monicoCopy?.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(monicoPlainText || monicoContent?.innerText || '');
      notifyDashboard('Contenido copiado.', 'success');
    } catch (_) {
      notifyDashboard('No se pudo copiar el contenido.', 'error');
    }
  });

  monicoSaveNote?.addEventListener('click', async () => {
    if (!monicoPlainText || typeof createNote !== 'function') {
      notifyDashboard('Genera contenido primero.', 'error');
      return;
    }

    const previous = noteInput?.value || '';
    if (noteInput) noteInput.value = monicoPlainText;

    try {
      await createNote();
      notifyDashboard('Resumen guardado como nota.', 'success');
    } catch (_) {
      if (noteInput) noteInput.value = previous;
    }
  });


  document.querySelectorAll('.js-doc-toggle').forEach(head => {
    head.addEventListener('click', () => {
      head.closest('.pdb-doc-group')?.classList.toggle('is-open');
    });
  });

  const wrap = document.getElementById('pdbStatusWrap');
  const btn = document.getElementById('pdbStatusBtn');
  const menu = document.getElementById('pdbStatusMenu');
  const chip = document.querySelector('[data-workflow-chip]');
  const labels = document.querySelectorAll('[data-workflow-label]');
  const resultStep = document.querySelector('[data-workflow-result-step]');

  const workflowMeta = {
    analisis_bases: { label: 'Análisis de Bases', tone: 'blue', step: 1 },
    revision: { label: 'Revisión', tone: 'orange', step: 2 },
    participa: { label: 'Participa', tone: 'green', step: 3 },
    junta_aclaraciones: { label: 'Junta de Aclaraciones', tone: 'green', step: 3 },
    armado_propuesta: { label: 'Armado de Propuesta', tone: 'green', step: 3 },
    entrega: { label: 'Entrega', tone: 'green', step: 3 },
    no_participa: { label: 'No participa', tone: 'red', step: 3 },
    ganado: { label: 'Ganado', tone: 'violet', step: 3 },
    perdido: { label: 'Perdido', tone: 'gray', step: 3 },
    desierta: { label: 'Desierta', tone: 'gray', step: 3 },
  };

  function setMenuOpen(open) {
    if (!wrap || !btn || !menu) return;
    wrap.classList.toggle('is-open', open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    menu.setAttribute('aria-hidden', open ? 'false' : 'true');
  }

  function cleanToneClasses(el) {
    if (!el) return;
    ['tone-blue', 'tone-orange', 'tone-green', 'tone-red', 'tone-violet', 'tone-gray'].forEach(c => el.classList.remove(c));
  }

  function applyWorkflowVisual(key) {
    const meta = workflowMeta[key] || workflowMeta.analisis_bases;
    labels.forEach(el => { el.textContent = meta.label; });

    cleanToneClasses(chip);
    chip?.classList.add('tone-' + meta.tone);

    document.querySelectorAll('[data-workflow-option]').forEach(option => {
      option.classList.toggle('is-active', option.dataset.workflowOption === key);
    });

    document.querySelectorAll('.pdb-step').forEach((step, index) => {
      const stepNumber = index + 1;
      step.classList.toggle('is-on', meta.step >= stepNumber);
      step.classList.toggle('is-ring', meta.step >= 3 && stepNumber === 3);
    });

    document.querySelectorAll('.pdb-step-line').forEach((line, index) => {
      line.classList.toggle('is-on', meta.step >= index + 2);
    });

    if (resultStep) {
      cleanToneClasses(resultStep);
      resultStep.classList.add('tone-' + meta.tone);
      const label = resultStep.querySelector('.pdb-step-label');
      if (label) label.textContent = meta.step >= 3 ? meta.label : 'Resultado';
    }

    wrap?.setAttribute('data-current-workflow', key);
  }

  async function saveWorkflowStatus(key, optionBtn) {
    if (!wrap || !chip) return;

    const previous = wrap.getAttribute('data-current-workflow') || 'analisis_bases';
    applyWorkflowVisual(key);
    setMenuOpen(false);

    chip.classList.add('pdb-status-saving');
    optionBtn?.setAttribute('disabled', 'disabled');

    try {
      const response = await fetch(wrap.dataset.workflowUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': CSRF_TOKEN,
        },
        body: JSON.stringify({ workflow_status: key }),
      });

      const payload = await response.json().catch(() => ({}));

      if (!response.ok || payload.ok === false) {
        throw new Error(payload.message || 'No se pudo actualizar el estado.');
      }

      applyWorkflowVisual(payload.workflow_status || key);
      const reloadStatuses = ['no_participa', 'participa', 'junta_aclaraciones', 'armado_propuesta', 'entrega', 'ganado', 'perdido', 'desierta'];
      if (reloadStatuses.includes(key) || reloadStatuses.includes(previous)) {
        window.location.reload();
        return;
      }
    } catch (error) {
      applyWorkflowVisual(previous);
      notifyDashboard(error.message || 'No se pudo actualizar el estado del proyecto.', 'error');
    } finally {
      chip.classList.remove('pdb-status-saving');
      optionBtn?.removeAttribute('disabled');
    }
  }

  btn?.addEventListener('click', (event) => {
    event.stopPropagation();
    setMenuOpen(!wrap.classList.contains('is-open'));
  });

  document.querySelectorAll('[data-workflow-option]').forEach(option => {
    option.addEventListener('click', (event) => {
      event.stopPropagation();
      const key = option.dataset.workflowOption;
      if (!key || key === wrap?.getAttribute('data-current-workflow')) {
        setMenuOpen(false);
        return;
      }
      saveWorkflowStatus(key, option);
    });
  });

  document.addEventListener('click', (event) => {
    if (!wrap?.contains(event.target)) setMenuOpen(false);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setMenuOpen(false);
  });

  // ============ DICTAMEN NO PARTICIPA ============

  // ============ DICTAMEN PARTICIPA / GANADO PANEL ============
  const resultPanel = document.getElementById('pdbResultPanel');
  const resultMode = document.getElementById('pdbResultMode');
  const resultBadge = document.getElementById('pdbResultBadge');
  const resultStats = document.getElementById('pdbResultStats');
  const resultList = document.getElementById('pdbResultList');
  const resultAdd = document.getElementById('pdbResultAdd');
  const resultSummary = document.getElementById('pdbResultSummary');
  const resultTip = document.getElementById('pdbResultTip');
  const resultConfirm = document.getElementById('pdbResultConfirm');

  const resultStorageKey = resultPanel ? `pdb-result-${resultPanel.dataset.projectKey || 'project'}` : null;

  function defaultResultItems(mode) {
    if (mode === 'total') return [{ title: 'Partida 1', status: 'ganada', note: '' }];
    if (mode === 'perdido') return [{ title: 'Partida 1', status: 'perdida', note: '' }];
    return [
      { title: 'Partida 1', status: 'ganada', note: '' },
      { title: 'Partida 2', status: 'perdida', note: '' },
    ];
  }

  let resultState = resultPanel ? {
    mode: resultMode?.value || resultPanel.dataset.initialMode || 'parcial',
    summary: '',
    items: defaultResultItems(resultMode?.value || resultPanel.dataset.initialMode || 'parcial'),
  } : null;

  function resultClass(status) {
    if (status === 'ganada') return 'is-won';
    if (status === 'perdida') return 'is-lost';
    return 'is-pending';
  }

  function resultMark(status) {
    if (status === 'ganada') return '<svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6 9 17l-5-5"/></svg>';
    if (status === 'perdida') return '<svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 6l12 12M18 6 6 18"/></svg>';
    return '⌛';
  }

  function escapeResultAttr(value) {
    return String(value || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
  }

  function saveResultState() {
    if (!resultStorageKey || !resultState) return;
    try { localStorage.setItem(resultStorageKey, JSON.stringify(resultState)); } catch (_) {}
  }

  function loadResultState() {
    if (!resultStorageKey || !resultState) return;
    try {
      const saved = JSON.parse(localStorage.getItem(resultStorageKey) || 'null');
      if (saved && Array.isArray(saved.items)) {
        resultState = {
          mode: saved.mode || resultState.mode,
          summary: saved.summary || '',
          items: saved.items.length ? saved.items : resultState.items,
        };
      }
    } catch (_) {}
  }

  function syncResultModeDefaults(mode) {
    if (!resultState) return;
    resultState.mode = mode;
    resultState.items = defaultResultItems(mode);
    if (mode === 'total') resultState.summary = 'Resultado total ganado. Documenta los factores de éxito.';
    else if (mode === 'perdido') resultState.summary = 'Resultado perdido. Documenta los factores de pérdida.';
    else resultState.summary = 'Resultado parcial. Documenta partidas ganadas y perdidas.';
  }

  function renderResultPanel() {
    if (!resultPanel || !resultState || !resultList) return;

    const won = resultState.items.filter(item => item.status === 'ganada').length;
    const lost = resultState.items.filter(item => item.status === 'perdida').length;
    const pending = resultState.items.filter(item => item.status !== 'ganada' && item.status !== 'perdida').length;

    if (resultMode) resultMode.value = resultState.mode;
    if (resultSummary && resultSummary.value !== resultState.summary) resultSummary.value = resultState.summary || '';

    if (resultBadge) {
      resultBadge.classList.remove('is-win', 'is-loss');
      if (resultState.mode === 'total') {
        resultBadge.textContent = 'GANADO';
        resultBadge.classList.add('is-win');
      } else if (resultState.mode === 'perdido') {
        resultBadge.textContent = 'PERDIDO';
        resultBadge.classList.add('is-loss');
      } else {
        resultBadge.textContent = 'PARCIAL';
      }
    }

    if (resultStats) resultStats.textContent = `${won} ganadas · ${lost} perdida${lost === 1 ? '' : 's'}${pending ? ` · ${pending} sin definir` : ''}`;

    if (resultTip) {
      const tipText = resultState.mode === 'total'
        ? '¡Felicidades! Documenta los factores de éxito.'
        : resultState.mode === 'perdido'
          ? 'Documenta las causas para fortalecer la siguiente participación.'
          : 'Documenta los factores de éxito y las causas de pérdida.';
      const span = resultTip.querySelector('span');
      if (span) span.innerHTML = `<strong>tip:</strong> ${tipText}`;
    }

    resultList.innerHTML = resultState.items.map((item, index) => `
      <div class="pdb-result-item ${resultClass(item.status)}" data-index="${index}">
        <div class="pdb-result-item-head">
          <div class="pdb-result-item-title">
            <span class="pdb-result-mark">${resultMark(item.status)}</span>
            <span>${item.title || `Partida ${index + 1}`}</span>
          </div>
          <select class="pdb-result-select js-result-status">
            <option value="ganada" ${item.status === 'ganada' ? 'selected' : ''}>Ganada</option>
            <option value="perdida" ${item.status === 'perdida' ? 'selected' : ''}>Perdida</option>
            <option value="pendiente" ${item.status === 'pendiente' ? 'selected' : ''}>Sin definir</option>
          </select>
          <button type="button" class="pdb-result-remove js-result-remove" title="Quitar partida">
            <svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"/></svg>
          </button>
        </div>
        <input type="text" class="pdb-result-input js-result-note" value="${escapeResultAttr(item.note)}" placeholder="¿Por qué este resultado?">
      </div>
    `).join('');
  }

  if (resultPanel) {
    loadResultState();
    renderResultPanel();

    resultMode?.addEventListener('change', () => {
      syncResultModeDefaults(resultMode.value);
      saveResultState();
      renderResultPanel();
    });

    resultAdd?.addEventListener('click', () => {
      resultState.items.push({ title: `Partida ${resultState.items.length + 1}`, status: resultState.mode === 'perdido' ? 'perdida' : 'ganada', note: '' });
      saveResultState();
      renderResultPanel();
    });

    resultSummary?.addEventListener('input', () => {
      resultState.summary = resultSummary.value;
      saveResultState();
    });

    resultList?.addEventListener('change', event => {
      const row = event.target.closest('.pdb-result-item');
      if (!row || !resultState) return;
      const index = Number(row.dataset.index);
      if (event.target.classList.contains('js-result-status')) {
        resultState.items[index].status = event.target.value;
        saveResultState();
        renderResultPanel();
      }
    });

    resultList?.addEventListener('input', event => {
      const row = event.target.closest('.pdb-result-item');
      if (!row || !resultState) return;
      const index = Number(row.dataset.index);
      if (event.target.classList.contains('js-result-note')) {
        resultState.items[index].note = event.target.value;
        saveResultState();
      }
    });

    resultList?.addEventListener('click', event => {
      const btnRemove = event.target.closest('.js-result-remove');
      if (!btnRemove || !resultState) return;
      const row = btnRemove.closest('.pdb-result-item');
      const index = Number(row.dataset.index);
      resultState.items.splice(index, 1);
      if (!resultState.items.length) resultState.items = defaultResultItems(resultState.mode);
      saveResultState();
      renderResultPanel();
    });

    resultConfirm?.addEventListener('click', () => {
      saveResultState();
      notifyDashboard('Dictamen confirmado correctamente.', 'success');
    });
  }

  const declinePanel = document.getElementById('pdbDeclinePanel');
  const declineReason = document.getElementById('pdbDeclineReason');
  const declineDisplay = document.getElementById('pdbDeclineDisplay');
  const declineEdit = document.getElementById('pdbDeclineEdit');
  const declineSave = document.getElementById('pdbDeclineSave');
  const declineCancel = document.getElementById('pdbDeclineCancel');
  const declineConfirm = document.getElementById('pdbDeclineConfirm');
  const declineActions = document.getElementById('pdbDeclineActions') || declinePanel?.querySelector('.pdb-decline-actions');
  let savedDeclineReason = declineReason ? declineReason.value.trim() : '';

  function notifyDashboard(message, type = 'success') {
    if (typeof showToast === 'function') {
      showToast(message, type);
      return;
    }

    const toast = document.createElement('div');
    toast.className = 'pdb-local-toast';
    toast.textContent = message;
    toast.style.position = 'fixed';
    toast.style.right = '22px';
    toast.style.bottom = '22px';
    toast.style.zIndex = '9999';
    toast.style.padding = '13px 16px';
    toast.style.borderRadius = '12px';
    toast.style.background = '#ffffff';
    toast.style.border = '1px solid #ebebeb';
    toast.style.boxShadow = '0 18px 40px rgba(15,23,42,.12)';
    toast.style.color = type === 'error' ? '#ff4a4a' : '#333333';
    toast.style.fontWeight = '700';
    toast.style.fontFamily = 'Quicksand, sans-serif';
    document.body.appendChild(toast);
    window.setTimeout(() => toast.remove(), 2200);
  }

  function forceDeclineMode(mode) {
    if (!declinePanel) return;

    const isEditing = mode === 'editing';
    declinePanel.classList.toggle('is-editing', isEditing);
    declinePanel.classList.toggle('is-readonly', !isEditing);

    if (declineReason) {
      declineReason.style.display = isEditing ? 'block' : 'none';
    }

    if (declineActions) {
      declineActions.style.display = isEditing ? 'flex' : 'none';
    }

    if (declineDisplay) {
      declineDisplay.style.display = isEditing ? 'none' : 'block';
    }

    if (declineEdit) {
      declineEdit.style.display = isEditing ? 'none' : 'inline-flex';
    }
  }

  function renderDeclineReadonly() {
    if (!declinePanel || !declineDisplay) return;

    const value = savedDeclineReason.trim();
    declineDisplay.textContent = value || 'Clic para documentar...';
    declineDisplay.classList.toggle('is-placeholder', value === '');
    forceDeclineMode('readonly');
  }

  function enterDeclineEdit() {
    if (!declinePanel || !declineReason) return;

    declineReason.value = savedDeclineReason;
    forceDeclineMode('editing');

    window.setTimeout(() => {
      declineReason.focus();
      declineReason.setSelectionRange(declineReason.value.length, declineReason.value.length);
    }, 30);
  }

  function setDeclineButtonsLoading(loading) {
    [declineSave, declineConfirm, declineCancel].filter(Boolean).forEach(button => {
      if (loading) {
        button.disabled = true;
        button.dataset.originalText = button.textContent;
        if (button !== declineCancel) button.textContent = 'Guardando...';
      } else {
        button.disabled = false;
        button.textContent = button.dataset.originalText || button.textContent;
      }
    });
  }

  async function saveDeclineReason(confirmed = false) {
    if (!declinePanel || !declineReason) return;

    setDeclineButtonsLoading(true);

    try {
      const reason = declineReason.value.trim();
      const response = await fetch(declinePanel.dataset.declineUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': CSRF_TOKEN,
        },
        body: JSON.stringify({ reason, confirmed: Boolean(confirmed) }),
      });

      const payload = await response.json().catch(() => ({}));

      if (!response.ok || payload.ok === false) {
        throw new Error(payload.message || 'No se pudo guardar el motivo.');
      }

      savedDeclineReason = String(payload.reason ?? reason).trim();
      declineReason.value = savedDeclineReason;
      renderDeclineReadonly();

      notifyDashboard(confirmed ? 'Dictamen confirmado correctamente' : 'Motivo guardado correctamente', 'success');

      if (confirmed) {
        window.location.reload();
        return;
      }
    } catch (error) {
      notifyDashboard(error.message || 'No se pudo guardar el motivo.', 'error');
    } finally {
      setDeclineButtonsLoading(false);
    }
  }

  declineSave?.addEventListener('click', () => saveDeclineReason(false));
  declineConfirm?.addEventListener('click', () => saveDeclineReason(true));
  declineEdit?.addEventListener('click', enterDeclineEdit);
  declineDisplay?.addEventListener('click', enterDeclineEdit);
  declineDisplay?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      enterDeclineEdit();
    }
  });
  declineCancel?.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();

    if (!declineReason) return;

    const current = declineReason.value.trim();
    declineReason.value = savedDeclineReason;
    renderDeclineReadonly();

    if (current !== savedDeclineReason) {
      notifyDashboard(savedDeclineReason ? 'Cambios descartados. Se restauró el motivo guardado.' : 'Cambios descartados. El motivo quedó vacío.', 'success');
    } else {
      notifyDashboard('Edición cancelada.', 'success');
    }
  });

  renderDeclineReadonly();

  // ============ ETIQUETAS DEL PROYECTO ============
  const tagsShell = document.getElementById('pdbTagsShell');
  const tagsToggle = document.getElementById('pdbTagsToggle');
  const tagsList = document.getElementById('pdbTagsList');
  const tagsPopover = document.getElementById('pdbTagsPopover');
  const tagsSearch = document.getElementById('pdbTagsSearch');
  const tagCreateRow = document.getElementById('pdbTagCreateRow');
  const tagCreateBtn = document.getElementById('pdbTagCreateBtn');
  const tagPreview = document.getElementById('pdbTagPreview');
  const commonTags = document.getElementById('pdbCommonTags');

  let currentLabels = [];
  let commonLabels = [];

  try { currentLabels = JSON.parse(tagsShell?.dataset.labels || '[]'); } catch (_) { currentLabels = []; }
  try { commonLabels = JSON.parse(tagsShell?.dataset.commonLabels || '[]'); } catch (_) { commonLabels = []; }

  function normalizeTag(tag) {
    return String(tag || '').replace(/\s+/g, ' ').trim();
  }

  function tagExists(tag) {
    const needle = normalizeTag(tag).toLowerCase();
    return currentLabels.some(t => normalizeTag(t).toLowerCase() === needle);
  }

  function setTagsOpen(open) {
    if (!tagsShell || !tagsPopover) return;
    tagsShell.classList.toggle('is-open', open);
    tagsPopover.setAttribute('aria-hidden', open ? 'false' : 'true');
    if (open) setTimeout(() => tagsSearch?.focus(), 30);
  }

  function renderTags() {
    if (!tagsList || !tagsToggle) return;

    tagsList.querySelectorAll('[data-tag-pill], [data-tags-empty]').forEach(el => el.remove());

    if (!currentLabels.length) {
      const empty = document.createElement('span');
      empty.dataset.tagsEmpty = 'true';
      empty.style.color = 'var(--muted)';
      empty.style.fontSize = '.82rem';
      empty.style.fontWeight = '600';
      empty.textContent = 'Sin etiquetas';
      tagsList.insertBefore(empty, tagsToggle);
    } else {
      currentLabels.forEach(tag => {
        const pill = document.createElement('span');
        pill.className = 'pdb-tag-pill';
        pill.dataset.tagPill = tag;
        pill.textContent = tag + ' ';

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.dataset.removeTag = tag;
        remove.setAttribute('aria-label', 'Eliminar etiqueta ' + tag);
        remove.textContent = '×';
        pill.appendChild(remove);

        tagsList.insertBefore(pill, tagsToggle);
      });
    }

    commonTags?.querySelectorAll('[data-common-tag]').forEach(btn => {
      btn.classList.toggle('is-selected', tagExists(btn.dataset.commonTag));
    });
  }

  function updateCreateState() {
    const value = normalizeTag(tagsSearch?.value || '');
    if (!tagCreateRow || !tagPreview || !commonTags) return;

    commonTags.querySelectorAll('[data-common-tag]').forEach(btn => {
      const text = normalizeTag(btn.dataset.commonTag || btn.textContent || '');
      const visible = !value || text.toLowerCase().includes(value.toLowerCase());
      btn.style.display = visible ? '' : 'none';
    });

    const canCreate = value.length > 0 && !tagExists(value) && !commonLabels.some(t => normalizeTag(t).toLowerCase() === value.toLowerCase());
    tagCreateRow.classList.toggle('is-visible', canCreate);
    tagPreview.textContent = value;
  }

  async function saveLabels(nextLabels) {
    if (!tagsShell?.dataset.labelsUrl) return;
    tagsShell.classList.add('pdb-tags-saving');

    try {
      const response = await fetch(tagsShell.dataset.labelsUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': CSRF_TOKEN,
        },
        body: JSON.stringify({ labels: nextLabels }),
      });

      const payload = await response.json().catch(() => ({}));
      if (!response.ok || payload.ok === false) {
        throw new Error(payload.message || 'No se pudieron guardar las etiquetas.');
      }

      currentLabels = Array.isArray(payload.labels) ? payload.labels : nextLabels;
      commonLabels = Array.from(new Set([...commonLabels, ...currentLabels]));
      renderTags();
      updateCreateState();
    } catch (error) {
      notifyDashboard(error.message || 'No se pudieron guardar las etiquetas.', 'error');
    } finally {
      tagsShell.classList.remove('pdb-tags-saving');
    }
  }

  function addTag(tag) {
    const clean = normalizeTag(tag);
    if (!clean || tagExists(clean)) return;
    saveLabels([...currentLabels, clean]);
    if (tagsSearch) tagsSearch.value = '';
    setTagsOpen(false);
  }

  function removeTag(tag) {
    const needle = normalizeTag(tag).toLowerCase();
    saveLabels(currentLabels.filter(t => normalizeTag(t).toLowerCase() !== needle));
  }

  tagsToggle?.addEventListener('click', (event) => {
    event.stopPropagation();
    setTagsOpen(!tagsShell.classList.contains('is-open'));
    updateCreateState();
  });

  tagsSearch?.addEventListener('input', updateCreateState);
  tagsSearch?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      addTag(tagsSearch.value);
    }
  });

  tagCreateBtn?.addEventListener('click', () => addTag(tagsSearch?.value || ''));

  commonTags?.addEventListener('click', (event) => {
    const btnTag = event.target.closest('[data-common-tag]');
    if (!btnTag) return;
    const tag = normalizeTag(btnTag.dataset.commonTag);
    if (tagExists(tag)) removeTag(tag);
    else addTag(tag);
  });

  tagsList?.addEventListener('click', (event) => {
    const removeBtn = event.target.closest('[data-remove-tag]');
    if (!removeBtn) return;
    removeTag(removeBtn.dataset.removeTag);
  });

  document.addEventListener('click', (event) => {
    if (!tagsShell?.contains(event.target)) setTagsOpen(false);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setTagsOpen(false);
  });



  // ============ MENÚS UNIFICADOS DE NOTAS Y TAREAS ============
  function closeActionMenus() {
    document.querySelectorAll('.pdb-action-shell.is-open').forEach(shell => shell.classList.remove('is-open'));
  }

  document.addEventListener('click', event => {
    const trigger = event.target.closest('.js-open-action-menu');

    if (trigger) {
      event.preventDefault();
      event.stopPropagation();

      const shell = trigger.closest('.pdb-action-shell');
      const wasOpen = shell?.classList.contains('is-open');

      closeActionMenus();

      if (shell && !wasOpen) {
        shell.classList.add('is-open');
      }

      return;
    }

    if (!event.target.closest('.pdb-action-menu')) {
      closeActionMenus();
    }
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeActionMenus();
  });

  async function patchJson(url, data = {}) {
    const response = await fetch(url, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': CSRF_TOKEN,
      },
      body: JSON.stringify(data),
    });

    const payload = await response.json().catch(() => ({}));
    if (!response.ok || payload.ok === false) throw new Error(payload.message || 'No se pudo completar la acción.');

    return payload;
  }

  async function postJson(url, data = {}) {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': CSRF_TOKEN,
      },
      body: JSON.stringify(data),
    });

    const payload = await response.json().catch(() => ({}));
    if (!response.ok || payload.ok === false) throw new Error(payload.message || 'No se pudo completar la acción.');

    return payload;
  }

  function makeInlineEdit(element, initialValue, onSave) {
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'pdb-inline-edit';
    input.value = initialValue || '';

    element.replaceWith(input);
    input.focus();
    input.setSelectionRange(input.value.length, input.value.length);

    let saved = false;

    async function save() {
      if (saved) return;
      saved = true;

      const value = input.value.trim();

      if (!value) {
        input.replaceWith(element);
        return;
      }

      await onSave(value, input, element);
    }

    input.addEventListener('keydown', event => {
      if (event.key === 'Enter') save();
      if (event.key === 'Escape') input.replaceWith(element);
    });

    input.addEventListener('blur', save);
  }


  // ============ NOTAS FUNCIONALES ============
  const notesCard = document.getElementById('pdbNotesCard');
  const notesList = document.getElementById('pdbNotesList');
  const notesCount = document.getElementById('pdbNotesCount');
  const noteInput = document.getElementById('pdbNoteInput');
  const addNoteBtn = document.getElementById('pdbAddNoteBtn');
  const mentionPop = document.getElementById('pdbMentionPop');

  let dashboardUsers = [];
  try { dashboardUsers = JSON.parse(notesCard?.dataset.users || '[]'); } catch (_) { dashboardUsers = []; }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, char => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    }[char]));
  }

  function highlightMentions(value) {
    return escapeHtml(value).replace(/@([\wÁÉÍÓÚáéíóúÑñ.\s]+?)(?=\s|$)/g, '<span class="pdb-mention">@$1</span>');
  }

  function initialsFromName(name) {
    return String(name || 'U').trim().slice(0, 1).toUpperCase() || 'U';
  }

  function updateNotesCount() {
    if (!notesCount || !notesList) return;
    const total = notesList.querySelectorAll('.pdb-note-card').length;
    notesCount.textContent = String(total);
    const sub = notesCount.parentElement;
    if (sub) sub.innerHTML = `<span id="pdbNotesCount">${total}</span> ${total === 1 ? 'nota' : 'notas'}`;
  }

  function renderMentionSuggestions() {
    if (!noteInput || !mentionPop) return;

    const value = noteInput.value;
    const match = value.match(/@([^\s@]*)$/);

    if (!match) {
      mentionPop.classList.remove('is-open');
      mentionPop.innerHTML = '';
      return;
    }

    const q = match[1].toLowerCase();
    const results = dashboardUsers
      .filter(user => String(user.name || '').toLowerCase().includes(q) || String(user.email || '').toLowerCase().includes(q))
      .slice(0, 6);

    if (!results.length) {
      mentionPop.classList.remove('is-open');
      mentionPop.innerHTML = '';
      return;
    }

    mentionPop.innerHTML = results.map(user => `
      <button type="button" class="pdb-mention-item" data-name="${escapeHtml(user.name)}">
        <span class="pdb-mention-name">@${escapeHtml(user.name)}</span>
        <span class="pdb-mention-email">${escapeHtml(user.email || '')}</span>
      </button>
    `).join('');

    mentionPop.classList.add('is-open');
  }

  function insertMention(name) {
    if (!noteInput) return;
    noteInput.value = noteInput.value.replace(/@([^\s@]*)$/, '@' + name + ' ');
    noteInput.focus();
    mentionPop?.classList.remove('is-open');
  }

  function addNoteToDom(note) {
    if (!notesList) return;

    notesList.querySelector('#pdbNotesEmpty')?.remove();

    const card = document.createElement('div');
    card.className = 'pdb-note-card';
    card.dataset.noteId = note.id;
    card.dataset.content = note.content || '';
    card.dataset.updateUrl = note.update_url || '';
    card.dataset.convertUrl = note.convert_url || '';
    card.dataset.pinUrl = note.pin_url || '';
    card.dataset.deleteUrl = note.delete_url || '';

    card.innerHTML = `
      <span class="pdb-note-avatar">${escapeHtml(initialsFromName(note.user_name))}</span>
      <div class="pdb-note-main">
        <div class="pdb-note-top">
          <span class="pdb-note-author">${escapeHtml(note.user_name || 'Usuario')}</span>
          <span class="pdb-note-date">
            <svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="9"></circle>
              <path d="M12 7v5l3 2"></path>
            </svg>
            ${escapeHtml(note.date || '')}
          </span>
        </div>
        <div class="pdb-note-body">${highlightMentions(note.content || '')}</div>
      </div>
      <div class="pdb-action-shell">
        <button type="button" class="pdb-note-menu js-open-action-menu" title="Opciones de nota" aria-label="Opciones de nota">
          <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
            <path d="M12 5h.01M12 12h.01M12 19h.01" stroke-linecap="round"></path>
          </svg>
        </button>
        <div class="pdb-action-menu">
          <button type="button" class="pdb-action-item js-edit-note"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>Editar</button>
          <button type="button" class="pdb-action-item js-note-to-task"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l2 2 4-5"></path><path d="M5 5h.01M5 12h.01M5 19h.01"></path><path d="M9 5h10M9 12h10M9 19h10"></path></svg>Convertir en Tarea</button>
          <button type="button" class="pdb-action-item js-pin-note"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m14 4 6 6-4 1-5 5-1 4-6-6 4-1 5-5 1-4Z"></path><path d="m9 15-5 5"></path></svg>Fijar Nota</button>
          <button type="button" class="pdb-action-item is-danger js-delete-note"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16"></path><path d="M9 7V5h6v2"></path><path d="m8 7 1 13h6l1-13"></path></svg>Eliminar</button>
        </div>
      </div>
    `;

    notesList.prepend(card);
    updateNotesCount();
  }

  async function createNote() {
    if (!notesCard || !noteInput) return;

    const content = noteInput.value.trim();

    if (!content) {
      notifyDashboard('Escribe una nota primero.', 'error');
      return;
    }

    addNoteBtn?.setAttribute('disabled', 'disabled');

    try {
      const response = await fetch(notesCard.dataset.storeUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': CSRF_TOKEN,
        },
        body: JSON.stringify({ content }),
      });

      const payload = await response.json().catch(() => ({}));

      if (!response.ok || payload.ok === false) {
        throw new Error(payload.message || 'No se pudo guardar la nota.');
      }

      addNoteToDom(payload.note);
      noteInput.value = '';
      renderMentionSuggestions();
      notifyDashboard('Nota agregada correctamente.', 'success');
    } catch (error) {
      notifyDashboard(error.message || 'No se pudo guardar la nota.', 'error');
    } finally {
      addNoteBtn?.removeAttribute('disabled');
    }
  }

  noteInput?.addEventListener('input', renderMentionSuggestions);
  noteInput?.addEventListener('keydown', event => {
    if (event.key === 'Enter') {
      event.preventDefault();
      createNote();
    }
  });
  addNoteBtn?.addEventListener('click', createNote);

  mentionPop?.addEventListener('click', event => {
    const item = event.target.closest('.pdb-mention-item');
    if (!item) return;
    insertMention(item.dataset.name || '');
  });

  notesList?.addEventListener('click', async event => {
    const btn = event.target.closest('.js-delete-note');
    if (!btn) return;

    closeActionMenus();

    const card = btn.closest('.pdb-note-card');
    const url = card?.dataset.deleteUrl;

    if (!card || !url) return;

    try {
      const response = await fetch(url, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': CSRF_TOKEN,
        },
      });

      const payload = await response.json().catch(() => ({}));
      if (!response.ok || payload.ok === false) throw new Error(payload.message || 'No se pudo eliminar la nota.');

      card.remove();
      updateNotesCount();

      if (!notesList.querySelector('.pdb-note-card')) {
        notesList.innerHTML = '<div class="pdb-empty-state" id="pdbNotesEmpty">No hay notas aún</div>';
      }

      notifyDashboard('Nota eliminada.', 'success');
    } catch (error) {
      notifyDashboard(error.message || 'No se pudo eliminar la nota.', 'error');
    }
  });


  notesList?.addEventListener('click', async event => {
    const noteCard = event.target.closest('.pdb-note-card');
    if (!noteCard) return;

    try {
      if (event.target.closest('.js-edit-note')) {
        closeActionMenus();

        const body = noteCard.querySelector('.pdb-note-body');
        const current = noteCard.dataset.content || body?.textContent || '';

        makeInlineEdit(body, current, async (value, input) => {
          const payload = await patchJson(noteCard.dataset.updateUrl, { content: value });
          noteCard.dataset.content = payload.note.content;
          const newBody = document.createElement('div');
          newBody.className = 'pdb-note-body';
          newBody.innerHTML = highlightMentions(payload.note.content || '');
          input.replaceWith(newBody);
          notifyDashboard('Nota actualizada.', 'success');
        });

        return;
      }

      if (event.target.closest('.js-note-to-task')) {
        closeActionMenus();

        const payload = await postJson(noteCard.dataset.convertUrl);
        tasksList?.querySelector('#pdbTasksEmpty')?.remove();
        tasksList?.insertAdjacentHTML('afterbegin', taskRowTemplate(payload.task));
        taskCounters();
        applyTaskFilter();
        notifyDashboard('Nota convertida en tarea.', 'success');
        return;
      }

      if (event.target.closest('.js-pin-note')) {
        closeActionMenus();

        const payload = await patchJson(noteCard.dataset.pinUrl);
        noteCard.classList.toggle('is-pinned', !!payload.is_pinned);
        if (payload.is_pinned) notesList?.prepend(noteCard);
        notifyDashboard(payload.is_pinned ? 'Nota fijada.' : 'Nota desfijada.', 'success');
        return;
      }
    } catch (error) {
      notifyDashboard(error.message || 'No se pudo actualizar la nota.', 'error');
    }
  });


  // ============ TAREAS FUNCIONALES ============
  const tasksCard = document.getElementById('pdbTasksCard');
  const tasksList = document.getElementById('pdbTasksList');
  const tasksPending = document.getElementById('pdbTasksPending');
  const tasksDone = document.getElementById('pdbTasksDone');
  const taskInput = document.getElementById('pdbTaskInput');
  const addTaskBtn = document.getElementById('pdbAddTaskBtn');
  const taskPriority = document.getElementById('pdbTaskPriority');
  const taskAssignee = document.getElementById('pdbTaskAssignee');
  const taskDueDate = document.getElementById('pdbTaskDueDate');
  const taskFilter = document.getElementById('pdbTaskFilter');

  const priorityLabels = {
    alta: 'Alta',
    media: 'Media',
    baja: 'Baja',
    normal: 'Sin prioridad',
  };

  function userNameById(id) {
    const user = dashboardUsers.find(item => String(item.id) === String(id));
    return user ? user.name : '';
  }

  function taskCounters() {
    const rows = Array.from(tasksList?.querySelectorAll('.pdb-task-row') || []);
    const completed = rows.filter(row => row.dataset.completed === '1').length;
    const pending = rows.length - completed;

    if (tasksPending) tasksPending.textContent = String(pending);
    if (tasksDone) tasksDone.textContent = String(completed);

    if (taskFilter) {
      const selected = taskFilter.value;
      taskFilter.options[0].textContent = `Pendientes (${pending})`;
      taskFilter.value = selected;
    }
  }

  function applyTaskFilter() {
    const mode = taskFilter?.value || 'pending';

    Array.from(tasksList?.querySelectorAll('.pdb-task-row') || []).forEach(row => {
      const completed = row.dataset.completed === '1';
      const visible = mode === 'all' || (mode === 'pending' && !completed) || (mode === 'completed' && completed);
      row.style.display = visible ? '' : 'none';
    });
  }

  function taskRowTemplate(task) {
    const priority = task.priority || 'normal';
    const assigned = task.assigned_name || (task.assigned_to ? userNameById(task.assigned_to) : '');
    const due = task.due_date_label || 'Sin fecha';
    const completed = task.completed ? '1' : '0';

    return `
      <div class="pdb-task-row ${task.completed ? 'is-completed' : ''}"
           data-task-id="${escapeHtml(task.id)}"
           data-title="${escapeHtml(task.title)}"
           data-completed="${completed}"
           data-priority="${escapeHtml(priority)}"
           data-update-url="${escapeHtml(task.update_url || '')}"
           data-convert-url="${escapeHtml(task.convert_url || '')}"
           data-pin-url="${escapeHtml(task.pin_url || '')}"
           data-archive-url="${escapeHtml(task.archive_url || '')}"
           data-delete-url="${escapeHtml(task.delete_url || '')}">
        <button type="button" class="pdb-task-check js-toggle-task ${task.completed ? 'is-completed' : ''}" title="Marcar tarea">
          <svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
            <path d="m5 12 4 4L19 6"></path>
          </svg>
        </button>
        <div>
          <p class="pdb-task-title">${escapeHtml(task.title)}</p>
          <div class="pdb-task-meta">
            <button type="button" class="pdb-task-chip is-priority-${escapeHtml(priority)} js-cycle-priority">
              <svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M5 21V4h10l1 3h4v9h-9l-1-3H5"></path>
              </svg>
              <span>${escapeHtml(priorityLabels[priority] || 'Sin prioridad')}</span>
            </button>
            <button type="button" class="pdb-task-chip js-assign-task">
              <svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path>
                <circle cx="10" cy="7" r="4"></circle>
              </svg>
              <span>${escapeHtml(assigned ? assigned.slice(0, 22) : 'Asignar')}</span>
            </button>
            <button type="button" class="pdb-task-chip js-date-task">
              <svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M7 3v4M17 3v4M4 9h16"></path>
                <rect x="4" y="5" width="16" height="16" rx="2"></rect>
              </svg>
              <span>${escapeHtml(due)}</span>
            </button>
          </div>
        </div>
        <div class="pdb-action-shell">
          <button type="button" class="pdb-task-menu js-open-action-menu" title="Opciones de tarea" aria-label="Opciones de tarea">
            <svg class="pdb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
              <path d="M12 5h.01M12 12h.01M12 19h.01" stroke-linecap="round"></path>
            </svg>
          </button>
          <div class="pdb-action-menu">
            <button type="button" class="pdb-action-item js-edit-task"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>Editar</button>
            <button type="button" class="pdb-action-item js-task-to-note"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg>Convertir en Nota</button>
            <button type="button" class="pdb-action-item js-pin-task"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m14 4 6 6-4 1-5 5-1 4-6-6 4-1 5-5 1-4Z"></path><path d="m9 15-5 5"></path></svg>Fijar Tarea</button>
            <button type="button" class="pdb-action-item js-archive-task"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h18"></path><path d="M5 7h14v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7Z"></path><path d="M9 11h6"></path></svg>Archivar</button>
            <button type="button" class="pdb-action-item is-danger js-delete-task"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16"></path><path d="M9 7V5h6v2"></path><path d="m8 7 1 13h6l1-13"></path></svg>Eliminar</button>
          </div>
        </div>
      </div>
    `;
  }

  async function createTask() {
    if (!tasksCard || !taskInput) return;

    const title = taskInput.value.trim();
    if (!title) {
      notifyDashboard('Escribe una tarea primero.', 'error');
      return;
    }

    addTaskBtn?.setAttribute('disabled', 'disabled');

    try {
      const response = await fetch(tasksCard.dataset.storeUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': CSRF_TOKEN,
        },
        body: JSON.stringify({
          title,
          priority: taskPriority?.value || 'normal',
          assigned_to: taskAssignee?.value || null,
          due_date: taskDueDate?.value || null,
        }),
      });

      const payload = await response.json().catch(() => ({}));
      if (!response.ok || payload.ok === false) throw new Error(payload.message || 'No se pudo guardar la tarea.');

      tasksList?.querySelector('#pdbTasksEmpty')?.remove();
      tasksList?.insertAdjacentHTML('afterbegin', taskRowTemplate(payload.task));

      taskInput.value = '';
      if (taskPriority) taskPriority.value = 'normal';
      if (taskAssignee) taskAssignee.value = '';
      if (taskDueDate) taskDueDate.value = '';

      taskCounters();
      applyTaskFilter();
      notifyDashboard('Tarea agregada correctamente.', 'success');
    } catch (error) {
      notifyDashboard(error.message || 'No se pudo guardar la tarea.', 'error');
    } finally {
      addTaskBtn?.removeAttribute('disabled');
    }
  }

  async function updateTask(row, data) {
    const url = row?.dataset.updateUrl;
    if (!row || !url) return null;

    const response = await fetch(url, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': CSRF_TOKEN,
      },
      body: JSON.stringify(data),
    });

    const payload = await response.json().catch(() => ({}));
    if (!response.ok || payload.ok === false) throw new Error(payload.message || 'No se pudo actualizar la tarea.');

    return payload.task;
  }

  addTaskBtn?.addEventListener('click', createTask);
  taskInput?.addEventListener('keydown', event => {
    if (event.key === 'Enter') {
      event.preventDefault();
      createTask();
    }
  });

  taskFilter?.addEventListener('change', applyTaskFilter);

  document.querySelectorAll('.js-sort-tasks').forEach(button => {
    button.addEventListener('click', () => {
      const mode = button.dataset.sort || 'az';
      const rows = Array.from(tasksList?.querySelectorAll('.pdb-task-row') || []);

      rows.sort((a, b) => {
        const av = String(a.dataset.title || '').localeCompare(String(b.dataset.title || ''), 'es', { sensitivity: 'base' });
        return mode === 'za' ? av * -1 : av;
      });

      rows.forEach(row => tasksList.appendChild(row));
      applyTaskFilter();
    });
  });

  tasksList?.addEventListener('click', async event => {
    const row = event.target.closest('.pdb-task-row');
    if (!row) return;

    try {
      if (event.target.closest('.js-toggle-task')) {
        const next = row.dataset.completed !== '1';
        const task = await updateTask(row, { completed: next });

        row.dataset.completed = task.completed ? '1' : '0';
        row.classList.toggle('is-completed', !!task.completed);
        row.querySelector('.pdb-task-check')?.classList.toggle('is-completed', !!task.completed);

        taskCounters();
        applyTaskFilter();
        return;
      }

      if (event.target.closest('.js-cycle-priority')) {
        const order = ['normal', 'baja', 'media', 'alta'];
        const current = row.dataset.priority || 'normal';
        const next = order[(order.indexOf(current) + 1) % order.length];
        const task = await updateTask(row, { priority: next });

        row.dataset.priority = task.priority || next;
        const chip = row.querySelector('.js-cycle-priority');
        chip.className = 'pdb-task-chip is-priority-' + row.dataset.priority + ' js-cycle-priority';
        chip.querySelector('span').textContent = priorityLabels[row.dataset.priority] || 'Sin prioridad';
        return;
      }

      if (event.target.closest('.js-assign-task')) {
        const chip = event.target.closest('.js-assign-task');
        const picker = document.createElement('select');
        picker.className = 'pdb-task-select';
        picker.style.height = '34px';
        picker.innerHTML = '<option value="">Sin asignar</option>' + dashboardUsers.map(user => `<option value="${escapeHtml(user.id)}">${escapeHtml(user.name)}</option>`).join('');
        chip.replaceWith(picker);
        picker.focus();

        picker.addEventListener('change', async () => {
          const task = await updateTask(row, { assigned_to: picker.value || null });
          const holder = document.createElement('button');
          holder.type = 'button';
          holder.className = 'pdb-task-chip js-assign-task';
          holder.innerHTML = `
            <svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path>
              <circle cx="10" cy="7" r="4"></circle>
            </svg>
            <span>${escapeHtml(task.assigned_name ? task.assigned_name.slice(0, 22) : 'Asignar')}</span>
          `;
          picker.replaceWith(holder);
        }, { once: true });

        return;
      }

      if (event.target.closest('.js-date-task')) {
        const chip = event.target.closest('.js-date-task');
        const picker = document.createElement('input');
        picker.type = 'date';
        picker.className = 'pdb-task-date';
        picker.style.height = '34px';
        chip.replaceWith(picker);
        picker.focus();

        picker.addEventListener('change', async () => {
          const task = await updateTask(row, { due_date: picker.value || null });
          const holder = document.createElement('button');
          holder.type = 'button';
          holder.className = 'pdb-task-chip js-date-task';
          holder.innerHTML = `
            <svg class="pdb-svg sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M7 3v4M17 3v4M4 9h16"></path>
              <rect x="4" y="5" width="16" height="16" rx="2"></rect>
            </svg>
            <span>${escapeHtml(task.due_date_label || 'Sin fecha')}</span>
          `;
          picker.replaceWith(holder);
        }, { once: true });

        return;
      }

      if (event.target.closest('.js-edit-task')) {
        closeActionMenus();

        const title = row.querySelector('.pdb-task-title');
        const current = row.dataset.title || title?.textContent || '';

        makeInlineEdit(title, current, async (value, input) => {
          const task = await updateTask(row, { title: value });
          row.dataset.title = task.title;
          const newTitle = document.createElement('p');
          newTitle.className = 'pdb-task-title';
          newTitle.textContent = task.title;
          input.replaceWith(newTitle);
          notifyDashboard('Tarea actualizada.', 'success');
        });

        return;
      }

      if (event.target.closest('.js-task-to-note')) {
        closeActionMenus();

        const payload = await postJson(row.dataset.convertUrl);
        addNoteToDom(payload.note);
        notifyDashboard('Tarea convertida en nota.', 'success');
        return;
      }

      if (event.target.closest('.js-pin-task')) {
        closeActionMenus();

        const payload = await patchJson(row.dataset.pinUrl);
        row.classList.toggle('is-pinned', !!payload.is_pinned);
        if (payload.is_pinned) tasksList?.prepend(row);
        notifyDashboard(payload.is_pinned ? 'Tarea fijada.' : 'Tarea desfijada.', 'success');
        return;
      }

      if (event.target.closest('.js-archive-task')) {
        closeActionMenus();

        await patchJson(row.dataset.archiveUrl);
        row.remove();

        if (!tasksList.querySelector('.pdb-task-row')) {
          tasksList.innerHTML = '<div class="pdb-empty-state" id="pdbTasksEmpty">No hay tareas pendientes</div>';
        }

        taskCounters();
        applyTaskFilter();
        notifyDashboard('Tarea archivada.', 'success');
        return;
      }

      if (event.target.closest('.js-delete-task')) {
        closeActionMenus();

        const url = row.dataset.deleteUrl;
        if (!url) return;

        const response = await fetch(url, {
          method: 'DELETE',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
          },
        });

        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.ok === false) throw new Error(payload.message || 'No se pudo eliminar la tarea.');

        row.remove();

        if (!tasksList.querySelector('.pdb-task-row')) {
          tasksList.innerHTML = '<div class="pdb-empty-state" id="pdbTasksEmpty">No hay tareas pendientes</div>';
        }

        taskCounters();
        applyTaskFilter();
        notifyDashboard('Tarea eliminada.', 'success');
      }
    } catch (error) {
      notifyDashboard(error.message || 'No se pudo actualizar la tarea.', 'error');
    }
  });

  applyTaskFilter();


  renderTags();
  updateCreateState();

})();
</script>
@endsection