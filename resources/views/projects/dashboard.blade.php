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
  .pdb-step-circle { width: 44px; height: 44px; border-radius: 50%; background: #f3f4f6; color: var(--muted); display: grid; place-items: center; border: 2px solid var(--line); transition: all .2s; }
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
  .pdb-module { display: flex; align-items: center; gap: 14px; padding: 14px 16px; border-radius: 12px; cursor: pointer; transition: all .15s; text-decoration: none; color: inherit; margin-bottom: 8px; border: 1.5px solid transparent; }
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
    transition: all .16s ease;
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
    transition: all .16s ease;
  }
  .pdb-common-tag:hover,
  .pdb-tag-create:hover { background: var(--blue-soft); border-color: #a8ccff; transform: translateY(-1px); }
  .pdb-common-tag.is-selected { background: var(--blue); border-color: var(--blue); color: #fff; }
  .pdb-tag-create-row { display: none; align-items: center; gap: 10px; padding: 6px 4px 2px; color: var(--ink); font-size: .92rem; font-weight: 600; }
  .pdb-tag-create-row.is-visible { display: flex; }
  .pdb-tag-create-row .plus { color: var(--blue); font-size: 1.05rem; font-weight: 700; }
  .pdb-tag-preview { display: inline-flex; align-items: center; min-height: 27px; padding: 3px 12px; border-radius: 999px; background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; font-weight: 700; }
  .pdb-tags-saving { opacity: .6; pointer-events: none; }

  /* ═══════ FICHA TÉCNICA ═══════ */
  .pdb-field { padding: 12px 0; border-bottom: 1px solid var(--line); }
  .pdb-field:last-child { border-bottom: none; }
  .pdb-field-lbl { font-size: .8rem; color: var(--muted); margin-bottom: 6px; font-weight: 600; }
  .pdb-field-val { border: 1px solid var(--line); border-radius: 8px; padding: 9px 12px; font-size: .9rem; color: var(--ink); background: #fff; min-height: 38px; display: flex; align-items: center; font-weight: 600; }
  .pdb-ext-link { color: var(--muted); cursor: pointer; padding: 4px 6px; border-radius: 6px; text-decoration: none; font-size: .85rem; }
  .pdb-ext-link:hover { color: var(--blue); background: var(--bg); }

  /* ═══════ FECHAS CLAVE ═══════ */
  .pdb-date-row { display: flex; align-items: center; gap: 12px; padding: 14px 0; border-bottom: 1px solid var(--line); }
  .pdb-date-row:last-child { border-bottom: none; }
  .pdb-date-row.is-near { background: linear-gradient(90deg, #fffbeb, transparent 60%); margin: 0 -12px; padding: 14px 12px; border-radius: 8px; }
  .pdb-date-ico { width: 34px; height: 34px; border-radius: 9px; display: grid; place-items: center; flex-shrink: 0; background: var(--bg); color: var(--muted); font-size: .9rem; }
  .pdb-date-ico.is-orange  { background: var(--orange-soft);  color: var(--orange);  }
  .pdb-date-ico.is-green   { background: var(--success-soft); color: var(--success); }
  .pdb-date-ico.is-warning { background: var(--warning-soft); color: var(--warning); }
  .pdb-date-body { flex: 1; }
  .pdb-date-title { font-size: .92rem; font-weight: 700; color: var(--ink); }
  .pdb-date-sub   { font-size: .8rem; color: var(--muted); margin-top: 2px; }
  .pdb-date-badge { font-size: .82rem; font-weight: 700; padding: 4px 12px; border-radius: 999px; display: inline-flex; align-items: center; gap: 4px; }
  .pdb-date-badge.is-past    { color: var(--muted);   background: var(--bg); }
  .pdb-date-badge.is-near    { color: var(--warning); background: var(--warning-soft); }
  .pdb-date-badge.is-warning { color: var(--warning); background: var(--warning-soft); }
  .pdb-date-badge.is-future  { color: var(--success); background: var(--success-soft); }
</style>
@endpush

@section('content')
@php
  $sd = $project->structured_data ?? [];
  $ficha  = $sd['ficha'] ?? [];
  $fechas = $sd['fechas_clave'] ?? [];
  $docs   = $project->documents;
  $notas  = $project->notes ?? collect();
  $tareas = $project->tasks ?? collect();
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

<div class="pdb-wrap">

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
      <a href="#" class="pdb-nav-tab">
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

      <button type="button" class="pdb-ask-monico"><span class="sparkle">✨</span> ask monico</button>
      <button type="button" class="pdb-collapse-btn" title="Colapsar">⌃</button>
    </div>

    <div class="pdb-hero-grid">

      {{-- ── Columna izquierda: Módulo sugerido ── --}}
      <div class="pdb-hero-col">
        <h3 class="pdb-card-title"><span class="ico is-violet">📚</span> Módulo sugerido</h3>

        @php
          $analisisUrl = route('projects.analisis', $project);
          $checklistUrl = route('projects.analisis', $project) . '#checklist';
          $borradorUrl = route('projects.analisis', $project) . '#borrador';

          $modulos = [
            ['key' => 'analisis',  'name' => 'Análisis de Bases',     'desc' => 'Revisa y analiza las bases del proyecto',   'tone' => 'tone-green',  'route' => $analisisUrl, 'available' => true,
             'svg' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6"/>'],
            ['key' => 'revision_checklist', 'name' => 'Revisión de Checklist', 'desc' => $hasChecklistDashboard ? ($checklistTotalDashboard . ' requisitos detectados desde Análisis') : 'Primero genera el checklist desde Análisis', 'tone' => 'tone-blue', 'route' => $checklistUrl, 'available' => $hasChecklistDashboard,
             'svg' => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>'],
            ['key' => 'juntas',    'name' => 'Junta de Aclaraciones', 'desc' => 'Gestiona preguntas y aclaraciones',          'tone' => 'tone-orange', 'route' => $analisisUrl . '#resumen', 'available' => in_array($estado['key'], ['participa','junta_aclaraciones','armado_propuesta','entrega','ganado']),
             'svg' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
            ['key' => 'propuesta', 'name' => 'Armado de Propuesta',   'desc' => 'Abre el checklist ligado desde Análisis para preparar la propuesta',   'tone' => 'tone-blue',   'route' => $checklistUrl, 'available' => $hasChecklistDashboard && in_array($estado['key'], ['armado_propuesta','entrega','ganado']),
             'svg' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>'],
            ['key' => 'reporte',   'name' => 'Reporte',               'desc' => 'Genera el reporte final del proyecto',       'tone' => 'tone-violet', 'route' => $borradorUrl, 'available' => $hasChecklistDashboard,
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

      {{-- ── Columna derecha: Checklist (si hay datos) o monico insights ── --}}
      <div class="pdb-hero-col">
        @if($hasChecklistDashboard)
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
            <h3 class="pdb-card-title" style="margin:0;"><span class="ico is-success">✓</span> Checklist</h3>
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
        @else
          <h3 class="pdb-card-title"><span class="ico is-violet">✨</span> monico insights</h3>
          <div class="pdb-insights-empty">
            <p>¿Quieres sugerencias para esta etapa?</p>
            <button type="button" class="pdb-insights-btn"><span>✨</span> ask monico</button>
          </div>
        @endif
      </div>
    </div>
  </div>

  {{-- ════════ FILA 2: NOTAS + TAREAS ════════ --}}
  <div class="pdb-grid">
    <div class="pdb-card">
      <div class="pdb-list-head">
        <div class="pdb-list-title">
          <span class="ico is-warning">📝</span>
          <div class="pdb-list-title-text">
            <span>Notas del Proyecto</span>
            <span class="pdb-card-sub">{{ $notas->count() }} {{ $notas->count() === 1 ? 'nota' : 'notas' }}</span>
          </div>
        </div>
      </div>

      @if($notas->count())
        <div class="pdb-notes-scroll">
          @foreach($notas as $nota)
            <div class="pdb-note">
              <div class="pdb-note-head">
                <span class="pdb-note-avatar">{{ strtoupper(substr(optional($nota->user)->name ?? 'U', 0, 1)) }}</span>
                <span class="pdb-note-author">{{ optional($nota->user)->name ?? 'Usuario' }}</span>
                <span class="pdb-note-date">🕐 {{ $nota->created_at->format('j M Y') }}</span>
                <button type="button" class="pdb-note-menu">⋮</button>
              </div>
              <div class="pdb-note-body">{{ $nota->content }}</div>
            </div>
          @endforeach
        </div>
      @else
        <div class="pdb-list-empty">
          <div class="ico-empty">📄</div>
          <div class="lbl">No hay notas aún</div>
          <div class="sub">Agrega notas para registrar información importante</div>
        </div>
      @endif

      <div class="pdb-list-input">
        <input type="text" placeholder="Agrega una nota y menciona a alguien con @…">
        <button class="add-btn">+</button>
      </div>
    </div>

    <div class="pdb-card">
      <div class="pdb-list-head">
        <div class="pdb-list-title">
          <span class="ico is-success">✓</span>
          <div class="pdb-list-title-text">
            <span>Tareas del Proyecto</span>
            <span class="pdb-card-sub">{{ $tareasPend }} pendientes · {{ $tareasDone }} completadas</span>
          </div>
        </div>
        <div style="display:flex;gap:6px;align-items:center;">
          <button type="button" class="pdb-sort-btn" title="Ordenar A-Z">A↑</button>
          <button type="button" class="pdb-sort-btn" title="Ordenar Z-A">Z↓</button>
          <button type="button" class="pdb-filter-btn">▽ Pendientes ({{ $tareasPend }}) ⌄</button>
        </div>
      </div>
      <div class="pdb-list-empty">
        <div class="ico-empty">✨</div>
        <div class="lbl">No hay tareas pendientes</div>
        <div class="sub">Usa las sugerencias de monico o agrega tareas manualmente</div>
      </div>
      <div class="pdb-list-input">
        <input type="text" placeholder="Agregar nueva tarea…">
        <button class="add-btn">+</button>
      </div>
    </div>
  </div>

  {{-- ════════ FILA 3: RESUMEN DOCS + INFO GENERAL ════════ --}}
  <div class="pdb-grid">

    <div class="pdb-card">
      <h3 class="pdb-card-title"><span class="ico">🖼</span> Resumen de Documentos</h3>

      {{-- Bases --}}
      <div class="pdb-doc-group tone-blue {{ $bases->count() ? 'is-open' : '' }}">
        <div class="pdb-doc-group-head js-doc-toggle">
          <div class="doc-ico">📄</div>
          <div class="doc-name">Bases</div>
          <div class="doc-count">{{ $bases->count() }}</div>
          <div class="doc-chev">⌄</div>
        </div>
        <div class="pdb-doc-group-body">
          @forelse($bases as $f)
            <div class="pdb-doc-file">
              <div class="file-ico">📄</div>
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
          <div class="doc-ico">📄</div>
          <div class="doc-name">Juntas de Aclaraciones</div>
          <div class="doc-count">{{ $juntas->count() }}</div>
          <div class="doc-chev">⌄</div>
        </div>
        <div class="pdb-doc-group-body">
          @forelse($juntas as $f)
            <div class="pdb-doc-file">
              <div class="file-ico">📄</div>
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
          <div class="doc-ico">📄</div>
          <div class="doc-name">Documentación Regulatoria</div>
          <div class="doc-count">{{ $regdocs->count() }}</div>
          <div class="doc-chev">⌄</div>
        </div>
        <div class="pdb-doc-group-body">
          @forelse($regdocs as $f)
            <div class="pdb-doc-file">
              <div class="file-ico">📄</div>
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
        <span class="pdb-info-val"><span class="ico-edit">✏️</span> {{ optional($project->start_date)->format('d M Y') ?: $project->created_at->format('d M Y') }}</span>
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
        <span class="pdb-info-val">🕐 Hace {{ $project->updated_at->diffInDays(now()) }} días</span>
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

    <div class="pdb-card">
      <h3 class="pdb-card-title">
        <span class="ico">📄</span> Ficha Técnica
        <span class="sparkle">✨</span>
        <a href="{{ route('projects.analisis', $project) }}" class="pdb-ext-link" style="margin-left:auto;" title="Ver completa">↗</a>
      </h3>

      <div class="pdb-field">
        <div class="pdb-field-lbl">Número de licitación</div>
        <div class="pdb-field-val">{{ $ficha['numero_licitacion'] ?? '—' }}</div>
      </div>
      <div class="pdb-field">
        <div class="pdb-field-lbl">Tipo de evento</div>
        <div class="pdb-field-val">{{ $ficha['tipo_evento'] ?? '—' }}</div>
      </div>
      <div class="pdb-field">
        <div class="pdb-field-lbl">Organismo</div>
        <div class="pdb-field-val">{{ $ficha['organismo'] ?? '—' }}</div>
      </div>
    </div>

    <div class="pdb-card">
      <h3 class="pdb-card-title">
        <span class="ico is-success">📅</span> Fechas Clave
        <span class="sparkle">✨</span>
        <a href="{{ route('projects.analisis', $project) }}" class="pdb-ext-link" style="margin-left:auto;" title="Ver completas">↗</a>
      </h3>

      @php
        $fechasRows = [
          ['k' => 'fecha_publicacion',     'label' => 'Fecha de publicación',                     'tone' => 'is-green'],
          ['k' => 'junta_aclaraciones',    'label' => 'Junta de aclaraciones',                    'tone' => 'is-warning'],
          ['k' => 'presentacion_apertura', 'label' => 'Presentación y apertura de proposiciones', 'tone' => 'is-green'],
          ['k' => 'fallo',                 'label' => 'Fallo',                                    'tone' => 'is-green'],
          ['k' => 'vigencia_contrato',     'label' => 'Vigencia del contrato',                    'tone' => 'is-green'],
        ];
      @endphp

      @foreach($fechasRows as $r)
        @php
          $val = $fechas[$r['k']] ?? null;
          [$badgeCls, $badgeTxt] = $badgeFor($val);
          $isNear = $badgeCls === 'is-near' || $badgeCls === 'is-warning';
        @endphp
        <div class="pdb-date-row {{ $isNear ? 'is-near' : '' }}">
          <div class="pdb-date-ico {{ $r['tone'] }}">
            @if($isNear)🕐@else📅@endif
          </div>
          <div class="pdb-date-body">
            <div class="pdb-date-title">{{ $r['label'] }}</div>
            <div class="pdb-date-sub">{{ $val ?: '—' }}</div>
          </div>
          @if($val)
            <span class="pdb-date-badge {{ $badgeCls }}">
              @if($isNear) ⚠ @endif {{ $badgeTxt }}
            </span>
          @endif
        </div>
      @endforeach
    </div>
  </div>

</div>

<script>
(function () {
  'use strict';

  const CSRF_TOKEN = '{{ csrf_token() }}';

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
    } catch (error) {
      applyWorkflowVisual(previous);
      alert(error.message || 'No se pudo actualizar el estado del proyecto.');
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
      alert(error.message || 'No se pudieron guardar las etiquetas.');
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

  renderTags();
  updateCreateState();

})();
</script>
@endsection