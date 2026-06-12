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

  /* ─── Pipeline ─── */
  .pdb-pipeline { flex: 1; display: flex; align-items: center; justify-content: center; gap: 0; padding: 0 30px; min-width: 380px; }
  .pdb-step { display: flex; flex-direction: column; align-items: center; gap: 6px; min-width: 80px; }
  .pdb-step-circle { width: 44px; height: 44px; border-radius: 50%; background: #f3f4f6; color: var(--muted); display: grid; place-items: center; border: 2px solid var(--line); transition: all .2s; }
  .pdb-step-circle svg { width: 20px; height: 20px; }
  .pdb-step-label { font-size: .78rem; font-weight: 700; color: var(--muted); }

  .pdb-step.is-on.tone-blue   .pdb-step-circle { background: var(--blue);   color: #fff; border-color: var(--blue);   box-shadow: 0 6px 16px rgba(0,122,255,.25); }
  .pdb-step.is-on.tone-orange .pdb-step-circle { background: #f59e0b;       color: #fff; border-color: #f59e0b;       box-shadow: 0 6px 16px rgba(245,158,11,.25); }
  .pdb-step.is-on.tone-green  .pdb-step-circle { background: #10b981;       color: #fff; border-color: #10b981;       box-shadow: 0 6px 16px rgba(16,185,129,.25); }
  .pdb-step.is-on.tone-blue   .pdb-step-label  { color: var(--blue); }
  .pdb-step.is-on.tone-orange .pdb-step-label  { color: #d97706; }
  .pdb-step.is-on.tone-green  .pdb-step-label  { color: #059669; }
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
  .pdb-module.is-disabled { opacity: .45; cursor: not-allowed; pointer-events: none; }
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
  .pdb-tags { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
  .pdb-tag-add { padding: 4px 12px; border: 1.5px dashed var(--line); border-radius: 999px; font-size: .78rem; font-weight: 600; color: var(--muted); cursor: pointer; display: inline-flex; align-items: center; gap: 4px; font-family: inherit; background: transparent; }
  .pdb-tag-add:hover { color: var(--blue); border-color: var(--blue); }
  .pdb-tag-add.is-filled { color: var(--blue); border-style: solid; border-color: var(--blue); background: var(--blue-soft); }

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
  $chk    = $sd['checklist'] ?? null;   // ['total','sin_revisar','no_cumple','parcial','cumple','pendiente','en_revision','aprobado']
  $docs   = $project->documents;
  $notas  = $project->notes ?? collect();
  $tareas = $project->tasks ?? collect();
  $tareasPend = $tareas->where('completed', false)->count();
  $tareasDone = $tareas->where('completed', true)->count();

  /* ── Estado del proyecto → controla chip, pipeline y módulo sugerido ── */
  $statusRaw = mb_strtolower($project->status ?? 'analisis');
  if (str_contains($statusRaw, 'no particip')) {
      $estado = ['key' => 'no_participa', 'label' => 'No Participa', 'tone' => 'red', 'step' => 3];
  } elseif (str_contains($statusRaw, 'particip')) {
      $estado = ['key' => 'participa', 'label' => 'Participa', 'tone' => 'green', 'step' => 3];
  } elseif (str_contains($statusRaw, 'revis')) {
      $estado = ['key' => 'revision', 'label' => 'Revisión', 'tone' => 'orange', 'step' => 2];
  } else {
      $estado = ['key' => 'analisis', 'label' => 'Análisis de Bases', 'tone' => 'blue', 'step' => 1];
  }
  $cur = $estado['step'];

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

    <button type="button" class="pdb-nav-pill">{{ $estado['label'] }}</button>

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
      <button type="button" class="pdb-estado-chip tone-{{ $estado['tone'] }}">{{ $estado['label'] }}</button>

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
        <div class="pdb-step tone-green {{ $cur >= 3 ? 'is-on is-ring' : '' }}">
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
          $modulos = [
            ['key' => 'analisis',  'name' => 'Análisis de Bases',     'desc' => 'Revisa y analiza las bases del proyecto',   'tone' => 'tone-green',  'route' => route('projects.analisis', $project),
             'svg' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6"/>'],
            ['key' => 'juntas',    'name' => 'Junta de Aclaraciones', 'desc' => 'Gestiona preguntas y aclaraciones',          'tone' => 'tone-orange', 'route' => '#',
             'svg' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
            ['key' => 'propuesta', 'name' => 'Armado de Propuesta',   'desc' => 'Construye la propuesta técnica/económica',   'tone' => 'tone-blue',   'route' => '#',
             'svg' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>'],
            ['key' => 'reporte',   'name' => 'Reporte',               'desc' => 'Genera el reporte final del proyecto',       'tone' => 'tone-violet', 'route' => '#',
             'svg' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 17h6"/>'],
            ['key' => 'tecnico',   'name' => 'Análisis Técnico',      'desc' => 'Revisión técnica especializada',             'tone' => 'tone-warn',   'route' => '#',
             'svg' => '<path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/>'],
          ];
          // El módulo sugerido depende del estado: participa → juntas; revisión → análisis; default → análisis
          $sugerido = $estado['key'] === 'participa' ? 'juntas' : 'analisis';
          usort($modulos, fn($a, $b) => ($a['key'] === $sugerido ? -1 : 1) <=> ($b['key'] === $sugerido ? -1 : 1));
        @endphp

        @foreach($modulos as $m)
          @php $isCur = $m['key'] === $sugerido; @endphp
          <a href="{{ $isCur ? $m['route'] : '#' }}" class="pdb-module {{ $isCur ? 'is-current' : 'is-disabled' }}">
            <div class="pdb-module-icon {{ $isCur ? $m['tone'] : 'tone-gray' }}">
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
        @if($chk && ($chk['total'] ?? 0) > 0)
          @php
            $t = $chk['total'];
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
            <a href="{{ route('projects.analisis', $project) }}" class="pdb-chk-link" title="Ver checklist">↗</a>
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
        <div class="pdb-tags">
          @foreach(($project->labels ?? []) as $tag)
            <span class="pdb-tag-add is-filled">{{ $tag }}</span>
          @endforeach
          <button type="button" class="pdb-tag-add">+ Agregar</button>
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
// Acordeón Resumen de Documentos
document.querySelectorAll('.js-doc-toggle').forEach(head => {
  head.addEventListener('click', () => {
    head.closest('.pdb-doc-group').classList.toggle('is-open');
  });
});
</script>
@endsection