@extends('layouts.app')
@section('content_class', 'content--flush')
@section('content')

<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #f8fafc;
    --card: #ffffff;
    --input-bg: #ffffff;
    --ink-dark: #0f172a;
    --ink: #334155;
    --muted: #64748b;
    --muted-light: #94a3b8;
    --line: #e2e8f0;
    --blue: #007aff;
    --blue-hover: #005bb5;
    --blue-soft: #eff6ff;
    --success: #4d823b; 
    --success-soft: #f0fdf4;
    --danger: #ef4444;
    --danger-soft: #fef2f2;
    --warning: #c74a14;
    --warning-hover: #a3390c;
    --warning-soft: #fff7ed;
    
    --font-family: 'Quicksand', sans-serif;
    --radius-card: 12px;
    --radius-modal: 12px; 
    --radius-input: 8px;
    --radius-btn: 8px;
  }

  /* Base & Typography */
  .jureto-quote-page { font-family: var(--font-family); background-color: var(--bg); color: var(--ink); min-height: 100vh; padding: 32px 24px; }
  .jureto-quote-page * { box-sizing: border-box; }
  .quote-wrap { max-width: 1100px; margin: 0 auto; }
  .jureto-quote-page h1, .jureto-quote-page h2, .jureto-quote-page h3 { color: var(--ink-dark); font-weight: 700; margin: 0; }
  .jureto-quote-page a { text-decoration: none; }

  /* Topbar Moderno */
  .back-link { display: inline-flex; align-items: center; gap: 8px; color: var(--muted); font-weight: 600; font-size: 14px; margin-bottom: 24px; transition: color 0.2s; }
  .back-link:hover { color: var(--ink-dark); }
  .topbar-modern { display: flex; flex-direction: column; margin-bottom: 32px; }
  .quote-code { font-size: 13px; font-weight: 700; color: var(--muted); letter-spacing: 0.5px; margin-bottom: 8px; text-transform: uppercase; }
    /* Back link + folio en la misma línea */
  .quote-topline { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
  .quote-topline .back-link { margin-bottom: 0; }
  .quote-topline .quote-code { margin-bottom: 0; }
  .topline-divider { width: 1px; height: 16px; background: var(--line); flex-shrink: 0; }
  .quote-title { font-size: 28px; margin-bottom: 8px; color: var(--ink-dark); }
  .quote-subtitle { font-size: 14px; color: var(--muted); margin: 0 0 24px 0; font-weight: 500; }
   .actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
  .actions-group { display: inline-flex; gap: 8px; align-items: center; }
  .actions-divider { width: 1px; height: 22px; background: var(--line); margin: 0 4px; flex-shrink: 0; }
  .actions-spacer { flex: 1 1 auto; }
  @media (max-width: 720px) {
    .actions-spacer { display: none; }
    .actions-divider { display: none; }
  }

  /* Buttons */
  .btn {
    font-family: var(--font-family); font-weight: 600; height: 40px; padding: 0 16px;
    border-radius: var(--radius-btn); border: 1px solid transparent; cursor: pointer; 
    display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px; 
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
  }
  .btn:hover { transform: translateY(-2px); }
  .btn:active { transform: scale(0.96) translateY(0); transition: transform 0.1s ease; }
  .btn-icon svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

  .btn-primary { background: var(--blue); color: #fff; box-shadow: 0 4px 12px rgba(0, 122, 255, 0.15); }
  .btn-primary:hover { background: var(--blue-hover); box-shadow: 0 6px 16px rgba(0, 122, 255, 0.25); }
  .btn-ghost { background: transparent; color: var(--muted); }
  .btn-ghost:hover { background: #f1f5f9; color: var(--ink-dark); }
  .btn-outline { background: var(--card); color: var(--ink-dark); border-color: var(--line); box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
  .btn-outline:hover { border-color: var(--muted-light); box-shadow: 0 4px 8px rgba(0,0,0,0.04); }
  .btn-rust { background: var(--warning); color: #fff; box-shadow: 0 4px 12px rgba(199, 74, 20, 0.15); }
  .btn-rust:hover { background: var(--warning-hover); box-shadow: 0 6px 16px rgba(199, 74, 20, 0.25); }
  .btn-success { background: var(--success); color: #fff; }
  .btn-danger { background: transparent; color: var(--danger); }
  .btn-danger:hover { background: var(--danger-soft); }
  .btn-small { height: 32px; padding: 0 12px; font-size: 13px; }

  /* Dropdown & Kebab Menu */
  .action-cell { position: relative; display: flex; align-items: center; gap: 8px; }
  .btn-kebab { width: 36px; height: 36px; border: 1px solid var(--line); border-radius: 10px; background: var(--card); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; color: var(--ink-dark); }
  .btn-kebab:hover { border-color: var(--muted-light); background: #f8fafc; transform: translateY(-1px); }
  .dropdown-menu { position: absolute; right: 0; bottom: calc(100% + 8px); top: auto; background: var(--card); border: 1px solid var(--line); border-radius: var(--radius-card); box-shadow: 0 18px 45px -12px rgba(15,23,42,0.22); min-width: 220px; z-index: 5000; display: none; flex-direction: column; padding: 8px 0; }
  .dropdown-menu.show { display: flex; animation: fadeIn 0.2s ease; }
  .dropdown-item { padding: 10px 16px; font-size: 14px; font-family: var(--font-family); color: var(--ink); font-weight: 500; display: flex; align-items: center; gap: 10px; cursor: pointer; background: transparent; border: none; width: 100%; text-align: left; transition: background 0.15s; }
  .dropdown-item:hover { background: #f8fafc; color: var(--blue); }
  .dropdown-item.text-danger:hover { color: var(--danger); background: var(--danger-soft); }
   .dropdown-divider { height: 1px; background: var(--line); margin: 6px 0; }
     /* Tamaño de iconos (los SVG centralizados no traen width/height) */
  .dropdown-item svg { width: 18px; height: 18px; flex-shrink: 0; }
  .btn svg { width: 16px; height: 16px; flex-shrink: 0; }
  .result-title svg { width: 18px; height: 18px; flex-shrink: 0; }

  /* Inputs & Forms */
  .input { font-family: var(--font-family); font-weight: 500; font-size: 14px; height: 40px; padding: 0 14px; background: var(--input-bg); border: 1px solid var(--line); border-radius: var(--radius-input); color: var(--ink); width: 100%; transition: all 0.2s ease; }
  textarea.input { height: auto; padding-top: 12px; padding-bottom: 12px; resize: vertical; }
  .input::placeholder { color: var(--muted-light); }
  .input:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft); }
  .field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; width: 100%; }
  .field label { font-size: 13px; font-weight: 700; color: var(--ink-dark); }

  /* Item Cards */
  .item-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius-card); box-shadow: 0 2px 8px rgba(0,0,0,0.02); transition: transform 0.3s ease, box-shadow 0.3s ease; margin-bottom: 16px; overflow: visible; }
  .item-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
  .item-main { padding: 20px 24px; display: grid; grid-template-columns: 16px 32px 1fr auto auto auto 24px; gap: 16px; align-items: center; cursor: pointer; }
  .drag-handle { background: transparent; border: none; color: var(--muted-light); cursor: grab; padding: 6px; display: flex; align-items: center; justify-content: center; touch-action: none; }
  .drag-handle:active { cursor: grabbing; }
  .item-card.dragging { opacity: .72; transform: scale(.995); box-shadow: 0 14px 34px rgba(15, 23, 42, .12); }
  .item-index { font-weight: 700; color: var(--muted-light); font-size: 14px; text-align: center; }
  .item-name { font-size: 16px; margin-bottom: 4px; }
  .item-meta { font-size: 13px; color: var(--muted); font-weight: 500; display: flex; gap: 6px; align-items: center;}
  .money-row { display: flex; gap: 24px; font-size: 13px; color: var(--muted); }
  .money-row strong { color: var(--ink-dark); display: block; font-size: 14px; margin-top: 2px; }
  .chevron { color: var(--muted-light); display: flex; align-items: center; justify-content: center; transition: transform 0.3s ease; }
  .item-card.open .chevron { transform: rotate(180deg); }
  .item-details { display: none; padding: 24px; border-top: 1px solid var(--line); background: var(--card); animation: fadeIn 0.3s ease; }
  .item-card.open .item-details { display: block; }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

  /* Inner Tabs (Fiel a la captura Nelo) */
  .item-tabs-container { display: flex; flex-wrap: wrap; background: #f1f5f9; border-radius: 10px; padding: 4px; margin-bottom: 24px; width: max-content; max-width: 100%; }
  .item-tab-btn { padding: 8px 16px; font-size: 14px; font-weight: 600; color: #64748b; border-radius: 6px; cursor: pointer; border: none; background: transparent; font-family: var(--font-family); transition: all 0.2s; }
  .item-tab-btn.active { background: #ffffff; color: #0f172a; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
  .item-tab-pane { display: none; }
  .item-tab-pane.active { display: block; animation: fadeIn 0.3s ease; }

  /* Result Cards */
  .result-card, .external-box { background: var(--card); padding: 16px 20px; margin-bottom: 16px; border: 1px solid var(--line); border-radius: var(--radius-input); }
  .result-card { position: relative; max-width: 100%; overflow: hidden; }
  .result-card.is-selected { padding-right: 64px; }
  .result-title { font-weight: 700; color: var(--ink-dark); font-size: 16px; display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
  .result-meta { font-size: 13px; color: var(--muted); font-weight: 500; }
  .deselect-clean-btn {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 34px;
    height: 34px;
    border-radius: 999px;
    border: 1px solid var(--line);
    background: #fff;
    color: var(--muted);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 6px 18px rgba(15, 23, 42, .06);
    transition: transform .18s ease, background .18s ease, color .18s ease, border-color .18s ease, box-shadow .18s ease;
    z-index: 2;
  }
  .deselect-clean-btn:hover {
    background: var(--danger-soft);
    color: var(--danger);
    border-color: rgba(239, 68, 68, .2);
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(239, 68, 68, .10);
  }
  .deselect-clean-btn svg { width: 16px; height: 16px; }

  /* Badges */
  .badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
  .badge-success { background: var(--success-soft); color: var(--success); }
  .badge-danger { background: var(--danger-soft); color: var(--danger); }
  .badge-info { background: #e2e8f0; color: var(--ink-dark); } 
  .badge-blue { background: var(--blue-soft); color: var(--blue); }
  .badge-warning { background: var(--warning-soft); color: var(--warning); }

  /* Summaries */
  .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; margin-bottom: 32px; }
  .summary-cell { padding: 24px 16px; text-align: center; cursor: pointer; border: 1px solid var(--line); background: var(--card); border-radius: var(--radius-card); transition: all 0.2s; }
  .summary-cell:hover, .summary-cell.active { border-color: var(--blue); box-shadow: 0 4px 12px rgba(0, 122, 255, 0.08); }
  .summary-value { font-size: 24px; font-weight: 700; color: var(--ink-dark); }
  .summary-label { font-size: 13px; color: var(--muted); margin-top: 6px; font-weight: 600; }

  /* ===== Margen Global (Colapsable / Botón desplegable) ===== */
  .global-margin-wrap { margin-bottom: 32px; }
  .global-margin-toggle {
    font-family: var(--font-family);
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
    width: 100%; max-width: 480px; padding: 16px 20px; text-align: left;
    background: var(--card); border: 1px solid var(--line); border-radius: var(--radius-card);
    cursor: pointer; transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
  }
  .global-margin-toggle:hover { border-color: var(--muted-light); box-shadow: 0 4px 12px rgba(0,0,0,0.05); transform: translateY(-1px); }
  .global-margin-toggle:active { transform: translateY(0); }
  .gm-toggle-left { display: flex; align-items: center; gap: 14px; }
  .gm-toggle-icon { width: 38px; height: 38px; border-radius: 10px; background: var(--blue-soft); color: var(--blue); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .gm-toggle-icon svg { width: 18px; height: 18px; }
  .gm-toggle-text { display: flex; flex-direction: column; gap: 2px; }
  .gm-toggle-title { font-weight: 700; color: var(--ink-dark); font-size: 15px; }
  .gm-toggle-sub { font-size: 12px; color: var(--muted); font-weight: 500; }
  .gm-toggle-chevron { color: var(--muted-light); display: flex; align-items: center; transition: transform .3s ease; }
  .global-margin-wrap.open .gm-toggle-chevron { transform: rotate(180deg); }
  .global-margin-wrap.open .global-margin-toggle {
    border-color: var(--blue);
    border-bottom-left-radius: 0; border-bottom-right-radius: 0;
  }

  .global-margin-panel { max-width: 480px; overflow: hidden; max-height: 0; opacity: 0; transition: max-height .35s ease, opacity .3s ease; }
  .global-margin-wrap.open .global-margin-panel { max-height: 400px; opacity: 1; }

  .global-margin { display: flex; align-items: flex-end; flex-wrap: wrap; gap: 16px; padding: 20px 24px; background: var(--card); border: 1px solid var(--blue); border-top: none; border-radius: 0 0 var(--radius-card) var(--radius-card); }
  .global-margin .field { margin-bottom: 0; width: auto; }
  .global-margin .field label { font-size: 14px; margin-bottom: 4px; }
  .global-margin .input { width: 120px; }

  /* Modal Base */
  .modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(4px); z-index: 1050; display: none; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; padding: 20px; }
  .modal-backdrop.show { display: flex; opacity: 1; }
  .modal { background: var(--card); border-radius: 12px; width: 100%; max-width: 560px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); transform: scale(0.95) translateY(15px); transition: transform 0.3s ease; display: flex; flex-direction: column; max-height: calc(100vh - 40px); }
  .modal-backdrop.show .modal { transform: scale(1) translateY(0); }
  .delete-confirm-backdrop {
    background: rgba(15, 23, 42, 0.32);
    backdrop-filter: blur(4px);
    z-index: 1200;
  }
  .delete-confirm-modal {
    width: min(520px, 100%);
    max-width: 520px;
    border-radius: 12px;
    background: #ffffff;
    color: var(--ink-dark);
    box-shadow: 0 24px 80px rgba(15, 23, 42, 0.22);
    padding: 28px;
    transform: scale(0.96) translateY(12px);
    transition: transform 0.24s ease;
  }
  .modal-backdrop.show .delete-confirm-modal { transform: scale(1) translateY(0); }
  .delete-confirm-title {
    font-size: 24px;
    line-height: 1.25;
    font-weight: 700;
    margin: 0 0 16px;
    color: #111827;
  }
  .delete-confirm-text {
    font-size: 15px;
    line-height: 1.55;
    color: #475569;
    margin: 0;
  }
  .delete-confirm-text strong {
    color: #111827;
    font-weight: 700;
  }
  .delete-confirm-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 28px;
  }
  .btn-modal-cancel {
    min-width: 112px;
    height: 44px;
    border-radius: 999px;
    border: 1px solid var(--line);
    background: #ffffff;
    color: #111827;
    font-weight: 700;
  }
  .btn-modal-cancel:hover { background: #f8fafc; transform: translateY(-1px); }
  .btn-modal-delete {
    min-width: 112px;
    height: 44px;
    border-radius: 999px;
    border: 1px solid #ef4444;
    background: #ef4444;
    color: #ffffff;
    font-weight: 700;
    box-shadow: 0 8px 18px rgba(239, 68, 68, 0.18);
  }
  .btn-modal-delete:hover { background: #dc2626; transform: translateY(-1px); }
  .modal-head { padding: 20px 24px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: flex-start; flex-shrink:0; }
  .modal-title { font-size: 18px; margin-bottom: 4px; }
  .modal-subtitle { font-size: 14px; color: var(--muted); margin: 0; font-weight: 500; }
  .modal-body { padding: 24px; overflow-y: auto; }
  .modal-tabs { display: flex; gap: 24px; border-bottom: 1px solid var(--line); margin-bottom: 24px; }
  .tab-btn { padding: 12px 0; font-weight: 700; color: var(--muted); border: none; background: transparent; border-bottom: 2px solid transparent; cursor: pointer; font-family: var(--font-family); font-size: 14px; transition: color 0.2s; }
  .tab-btn.active { color: var(--blue); border-bottom-color: var(--blue); }
  .modal-result { display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid var(--line); }
  .modal-result:last-child { border-bottom: none; }

  /* Nuevo Toast Progress (Flotante) */
  .toast-process {
    position: fixed; bottom: 24px; right: 24px; width: 360px; background: var(--card);
    border: 1px solid var(--line); border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    z-index: 9999; transform: translateY(150%); transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex; flex-direction: column; overflow: hidden;
  }
  .toast-process.show { transform: translateY(0); }
  .toast-bar-container { height: 4px; background: var(--line); width: 100%; }
  .toast-bar-fill { height: 100%; background: var(--blue); width: 0%; transition: width 0.3s ease; }
  .toast-header { padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; background: var(--card); cursor: pointer; user-select: none; }
  .toast-header:hover { background: #f8fafc; }
  .toast-header-right { display: flex; align-items: center; gap: 8px; }
  .toast-title { font-weight: 700; font-size: 14px; color: var(--ink-dark); }
  .toast-subtitle { font-size: 12px; color: var(--muted); margin-top: 4px; }
  .toast-error-badge { background: var(--danger-soft); color: var(--danger); font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 999px; display: none; align-items: center; white-space: nowrap; }
  .toast-process.has-errors .toast-error-badge { display: inline-flex; }
  .toast-chevron { color: var(--muted); display: flex; align-items: center; transition: transform 0.3s ease; }
  .toast-process.expanded .toast-chevron { transform: rotate(180deg); }
  .toast-body { padding: 0 20px 16px; display: none; max-height: 250px; overflow-y: auto; border-top: 1px solid var(--line); }
  .toast-process.expanded .toast-body { display: block; padding-top: 16px; }
  .toast-error-list { font-size: 12px; color: var(--danger); display: flex; flex-direction: column; gap: 8px; }
  .toast-error-item { background: var(--danger-soft); padding: 8px 12px; border-radius: 6px; line-height: 1.4; border-left: 3px solid var(--danger); }
  
  /* Utilities */
  .action-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
  .row { display: flex; flex-wrap: wrap; margin-left: -8px; margin-right: -8px; }
  .col, .col-12, .col-6, .col-4, .col-3 { padding-left: 8px; padding-right: 8px; }
  .col-12 { width: 100%; } .col-6 { width: 50%; } .col-4 { width: 33.333333%; } .col-3 { width: 25%; }
  .d-flex { display: flex; } .align-items-center { align-items: center; } .mb-0 { margin-bottom: 0 !important; } .mb-2 { margin-bottom: 8px !important; } .mt-2 { margin-top: 8px !important; } .mt-3 { margin-top: 16px !important; }

  /* Notice */
    /* Notice (pill minimalista) */
  .notice { background: var(--warning-soft); color: var(--warning); padding: 8px 14px; border-radius: 999px; display: none; align-items: center; gap: 8px; margin-bottom: 20px; font-weight: 600; font-size: 12.5px; border: 1px solid rgba(199, 74, 20, 0.12); width: max-content; max-width: 100%; }
  .notice.show { display: inline-flex; }
  .notice-dot { width: 6px; height: 6px; background: var(--warning); border-radius: 50%; flex-shrink: 0; }
  .text-blue { color: var(--blue); } .text-success { color: var(--success); } .text-danger { color: var(--danger); }
  
  .loader { display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: #fff; animation: spin 1s linear infinite; }
  @keyframes spin { to { transform: rotate(360deg); } }
  
  ::-webkit-scrollbar { width: 6px; height: 6px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: var(--line); border-radius: 10px; }
  ::-webkit-scrollbar-thumb:hover { background: var(--muted-light); }
    /* Botones solo icono (Excel / Word) */
  .btn-square { width: 40px; height: 40px; padding: 0; }
  .actions .btn-square svg { width: 22px; height: 22px; }
    /* ===== Tooltip pro (reutilizable con class="has-tip" data-tip="...") ===== */
  .has-tip { position: relative; }
  .has-tip::after {
    content: attr(data-tip);
    position: absolute;
    bottom: calc(100% + 10px);
    left: 50%;
    transform: translateX(-50%) translateY(4px);
    background: var(--ink-dark);
    color: #fff;
    font-family: var(--font-family);
    font-size: 12px;
    font-weight: 600;
    letter-spacing: .2px;
    padding: 7px 11px;
    border-radius: 8px;
    white-space: nowrap;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.22);
    opacity: 0;
    pointer-events: none;
    transition: opacity .18s ease, transform .18s cubic-bezier(0.16, 1, 0.3, 1);
    z-index: 200;
  }
  .has-tip::before {
    content: "";
    position: absolute;
    bottom: calc(100% + 4px);
    left: 50%;
    transform: translateX(-50%) translateY(4px);
    border: 6px solid transparent;
    border-top-color: var(--ink-dark);
    opacity: 0;
    pointer-events: none;
    transition: opacity .18s ease, transform .18s cubic-bezier(0.16, 1, 0.3, 1);
    z-index: 200;
  }
  .has-tip:hover::after,
  .has-tip:hover::before { opacity: 1; transform: translateX(-50%) translateY(0); }


  /* ===== Muestras / stock estilo busqueda manual ===== */
  .samples-modal {
    max-width: 920px;
  }

  .samples-toolbar {
    position: relative;
    margin-bottom: 18px;
  }

  .samples-results {
    display: flex;
    flex-direction: column;
    gap: 14px;
  }

  .sample-card {
    display: grid;
    grid-template-columns: 96px 1fr auto;
    gap: 16px;
    align-items: flex-start;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 16px;
  }

  .sample-card:hover {
    border-color: var(--muted-light);
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
  }

  .sample-image {
    width: 96px;
    height: 96px;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: #f8fafc;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--muted-light);
    font-size: 12px;
    font-weight: 700;
    text-align: center;
  }

  .sample-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
  }

  .sample-title-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 6px;
  }

  .sample-title {
    color: var(--ink-dark);
    font-size: 16px;
    font-weight: 700;
  }

  .sample-meta-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 8px 12px;
    margin-top: 12px;
  }

  .sample-mini {
    background: #f8fafc;
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: 8px 10px;
  }

  .sample-mini span {
    display: block;
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 3px;
  }

  .sample-mini strong {
    color: var(--ink-dark);
    font-size: 13px;
  }

  .sample-description {
    color: var(--muted);
    font-size: 13px;
    line-height: 1.45;
    margin-top: 10px;
  }

  .sample-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 10px;
  }

  .sample-tag {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    background: #f1f5f9;
    color: var(--muted);
    font-size: 12px;
    font-weight: 700;
    padding: 4px 9px;
  }

  .sample-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    align-items: flex-end;
    min-width: 124px;
  }

  .sample-stock-box {
    text-align: right;
    color: var(--muted);
    font-size: 12px;
    line-height: 1.5;
  }

  .sample-stock-box strong {
    color: var(--ink-dark);
  }

  @media (max-width: 760px) {
    .sample-card {
      grid-template-columns: 72px 1fr;
    }

    .sample-image {
      width: 72px;
      height: 72px;
    }

    .sample-actions {
      grid-column: 1 / -1;
      flex-direction: row;
      justify-content: flex-start;
      align-items: center;
    }

    .sample-stock-box {
      text-align: left;
    }
  }

</style>

@php
  $propuestaComercial->loadMissing([
      'items.matches.product',
      'items.externalMatches',
      'items.productoSeleccionado',
      'items.aclaracionPreguntas',
  ]);

  $itemsPayload = $propuestaComercial->items
      ->sortBy('sort')
      ->values()
      ->map(function ($item) use ($propuestaComercial) {
          // El match automatico solo debe sugerir candidatos.
          // Una partida solo se considera aceptada/exacta cuando el usuario la acepta manualmente.
          $uiStatus = data_get($item->meta, 'ui_status', 'pending');
          $humanAccepted = $uiStatus === 'accepted_item';
          $selectedMatch = $humanAccepted ? $item->matches->firstWhere('seleccionado', true) : null;
          $bestMatch = $item->matches->sortByDesc('score')->first();
          $score = (float) ($item->match_score ?: optional($selectedMatch ?: $bestMatch)->score);

          if ($humanAccepted && ($item->productoSeleccionado || $selectedMatch)) {
              $statusKey = 'exact';
          } elseif ($item->productoSeleccionado || $item->matches->count()) {
              $statusKey = 'similar';
          } else {
              $statusKey = 'not_found';
          }

          return [
              'id' => $item->id,
              'sort' => (int) $item->sort,
              'descripcion_original' => $item->descripcion_original,
              'unidad_solicitada' => $item->unidad_solicitada,
              'cantidad_minima' => (float) $item->cantidad_minima,
              'cantidad_maxima' => (float) $item->cantidad_maxima,
              'cantidad_cotizada' => (float) ($item->cantidad_cotizada ?: 1),
              'costo_unitario' => (float) $item->costo_unitario,
              'precio_unitario' => (float) $item->precio_unitario,
              'subtotal' => (float) $item->subtotal,
              'match_score' => $score,
              'status_key' => $statusKey,
              'ui_status' => $uiStatus,
              'item_margin_pct' => (float) data_get($item->meta, 'item_margin_pct', $propuestaComercial->porcentaje_utilidad ?: 25),
              'manual_external_supplier' => data_get($item->meta, 'external_supplier'),
              'manual_external_link' => data_get($item->meta, 'external_link'),
              'modelo' => data_get($item->meta, 'modelo'), // Nuevo campo de modelo
              'catalog_product_name_manual' => data_get($item->meta, 'catalog_product_name_manual'),
              'tech_sheet_id' => data_get($item->meta, 'tech_sheet_id'),
              'tech_sheet_name' => data_get($item->meta, 'tech_sheet_name'),
              'clarification_questions' => $item->relationLoaded('aclaracionPreguntas')
                  ? $item->aclaracionPreguntas->sortBy('sort')->values()->map(function ($pregunta) {
                      return [
                          'id' => $pregunta->id,
                          'texto_usuario' => $pregunta->texto_usuario,
                          'pregunta_generada' => $pregunta->pregunta_generada,
                          'question' => $pregunta->pregunta_generada,
                          'producto_solicitado' => $pregunta->producto_solicitado,
                          'producto_sugerido' => $pregunta->producto_sugerido,
                          'sku_sugerido' => $pregunta->sku_sugerido,
                          'marca_sugerida' => $pregunta->marca_sugerida,
                          'precio_sugerido' => (float) $pregunta->precio_sugerido,
                          'justificacion' => $pregunta->justificacion,
                          'catalog_candidate' => [
                              'name' => $pregunta->producto_sugerido,
                              'sku' => $pregunta->sku_sugerido,
                              'brand' => $pregunta->marca_sugerida,
                              'price' => (float) $pregunta->precio_sugerido,
                          ],
                      ];
                  })->all()
                  : data_get($item->meta, 'clarification_questions', []),
              'producto_seleccionado' => ($humanAccepted && $item->productoSeleccionado) ? [
                  'id' => $item->productoSeleccionado->id,
                  'name' => $item->productoSeleccionado->name,
                  'sku' => $item->productoSeleccionado->sku,
                  'brand' => data_get($item->meta, 'external_supplier') ?: $item->productoSeleccionado->brand,
                  'model' => data_get($item->meta, 'modelo') ?: ($item->productoSeleccionado->model ?? $item->productoSeleccionado->modelo ?? $item->productoSeleccionado->model_name ?? null),
                  'stock' => $item->productoSeleccionado->stock ?? 0,
                  'cost' => (float) ($item->productoSeleccionado->cost ?? $item->productoSeleccionado->costo ?? 0),
                  'price' => (float) ($item->productoSeleccionado->price ?? $item->productoSeleccionado->precio ?? 0),
              ] : null,
              'matches' => $item->matches->sortBy('rank')->values()->map(function ($match) use ($humanAccepted) {
                  $p = $match->product;
                  return [
                      'id' => $match->id,
                      'rank' => $match->rank,
                      'score' => (float) $match->score,
                      'seleccionado' => $humanAccepted && (bool) $match->seleccionado,
                      'product' => $p ? [
                          'id' => $p->id,
                          'name' => $p->name,
                          'sku' => $p->sku,
                          'brand' => $p->brand,
                          'model' => $p->model ?? $p->modelo ?? $p->model_name ?? null,
                          'stock' => $p->stock ?? 0,
                          'cost' => (float) ($p->cost ?? $p->costo ?? $p->purchase_price ?? 0),
                          'price' => (float) ($p->price ?? $p->precio ?? $p->sale_price ?? 0),
                      ] : null,
                  ];
              })->all(),
              'external_matches' => $item->externalMatches->sortBy('rank')->values()->map(function ($external) {
                  return [
                      'id' => $external->id,
                      'source' => $external->source,
                      'title' => $external->title,
                      'seller' => $external->seller,
                      'price' => (float) $external->price,
                      'url' => $external->url,
                      'score' => (float) $external->score,
                  ];
              })->all(),
          ];
      });

  $subtotalSale = (float) $propuestaComercial->items->sum('subtotal');
  $subtotalCost = (float) $propuestaComercial->items->sum(fn($i) => ((float) $i->costo_unitario) * ((float) ($i->cantidad_cotizada ?: 0)));
  $profit = $subtotalSale - $subtotalCost;
  $margin = $subtotalCost > 0 ? round(($profit / $subtotalCost) * 100) : 0;

  $summaryPayload = [
      'exact' => $itemsPayload->where('status_key', 'exact')->count(),
      'similar' => $itemsPayload->where('status_key', 'similar')->count(),
      'not_found' => $itemsPayload->where('status_key', 'not_found')->count(),
      'subtotal_sale' => $subtotalSale,
      'subtotal_cost' => $subtotalCost,
      'profit' => $profit,
      'margin' => $margin,
      'total_items' => $itemsPayload->count(),
  ];

  $exportFolio = $propuestaComercial->folio ?: ('TEOA' . str_pad((string) $propuestaComercial->id, 8, '0', STR_PAD_LEFT));
  $exportTitle = $propuestaComercial->titulo ?: ('COT-' . strtoupper(substr(md5($propuestaComercial->id . $propuestaComercial->created_at), 0, 8)));

  // Payloads reales para exportar tablas extraidas del PDF. Si no hay payloads, JS usa fallback con las partidas normalizadas.
  $decodeExportValue = function ($value) {
      if ($value instanceof \Illuminate\Support\Collection) {
          return $value->toArray();
      }

      if (is_array($value)) {
          return $value;
      }

      if (is_object($value)) {
          return json_decode(json_encode($value), true) ?: [];
      }

      if (is_string($value) && trim($value) !== '') {
          $decoded = json_decode($value, true);
          return json_last_error() === JSON_ERROR_NONE ? $decoded : [];
      }

      return [];
  };

  $rawExportPayloads = [];
  $fieldsForExport = ['structured_json', 'items_json', 'result_json', 'raw_json', 'extracted_json', 'document_json', 'table_json', 'meta'];

  foreach ($fieldsForExport as $field) {
      $decoded = $decodeExportValue(data_get($propuestaComercial, $field));
      if (!empty($decoded)) {
          $rawExportPayloads['propuesta_' . $field] = $decoded;
      }
  }

  foreach ($propuestaComercial->getRelations() as $relationName => $relationValue) {
      if (!$relationValue) {
          continue;
      }

      if ($relationValue instanceof \Illuminate\Support\Collection) {
          foreach ($relationValue as $index => $relatedModel) {
              foreach ($fieldsForExport as $field) {
                  $decoded = $decodeExportValue(data_get($relatedModel, $field));
                  if (!empty($decoded)) {
                      $rawExportPayloads[$relationName . '_' . $index . '_' . $field] = $decoded;
                  }
              }
          }
      } else {
          foreach ($fieldsForExport as $field) {
              $decoded = $decodeExportValue(data_get($relationValue, $field));
              if (!empty($decoded)) {
                  $rawExportPayloads[$relationName . '_' . $field] = $decoded;
              }
          }
      }
  }
@endphp

<div class="jureto-quote-page">
  <div class="quote-wrap">
        <div class="topbar-modern">
      <div class="quote-topline">
        <a href="{{ route('propuestas-comerciales.index') }}" class="back-link">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
          <span>Volver a propuestas</span>
        </a>
        <span class="topline-divider"></span>
        <span class="quote-code">{{ $exportFolio }}</span>
      </div>
      <h1 class="quote-title">{{ $exportTitle }}</h1>
      <p class="quote-subtitle"><span id="itemsCountText">{{ $summaryPayload['total_items'] }}</span> partidas analizadas por IA · Exportación estructurada</p>

         <div class="actions">
        <button class="btn btn-ghost" type="button" id="btnOpenAddItem">
          <span class="btn-icon"><svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></span>
          <span>Agregar</span>
        </button>
        <button class="btn btn-outline" type="button" id="btnSuggestAll">
          <span class="btn-icon"><svg viewBox="0 0 24 24"><circle cx="6" cy="6" r="2.5"></circle><circle cx="18" cy="18" r="2.5"></circle><path d="M8 8l8 8"></path></svg><circle cx="9" cy="12" r="6"></circle><circle cx="15" cy="12" r="6"></circle></svg></span>
          <span>Hacer Match</span>
        </button>
        <span class="actions-divider"></span>

              <div class="actions-group">
          <button class="btn btn-outline btn-square has-tip" type="button" id="btnExportExcel" data-tip="Exportar a Excel" aria-label="Exportar a Excel">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" fill="#fff" stroke="#1D6F42" stroke-width="1.5" stroke-linejoin="round"/>
              <path d="M14 2v6h6" stroke="#1D6F42" stroke-width="1.5" stroke-linejoin="round"/>
              <path d="M9 12.5l4.5 6M13.5 12.5l-4.5 6" stroke="#1D6F42" stroke-width="1.7" stroke-linecap="round"/>
            </svg>
          </button>
          <button class="btn btn-outline btn-square has-tip" type="button" id="btnExportWord" data-tip="Exportar a Word" aria-label="Exportar a Word">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" fill="#fff" stroke="#2B579A" stroke-width="1.5" stroke-linejoin="round"/>
              <path d="M14 2v6h6" stroke="#2B579A" stroke-width="1.5" stroke-linejoin="round"/>
              <path d="M8.3 12.5l1.3 6 1.4-4.2 1.4 4.2 1.3-6" stroke="#2B579A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
          <button class="btn btn-outline btn-square has-tip" type="button" id="btnExportClarificationsPdf" data-tip="PDF junta de aclaraciones" aria-label="PDF junta de aclaraciones">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" fill="#fff" stroke="#007aff" stroke-width="1.5" stroke-linejoin="round"/>
              <path d="M14 2v6h6" stroke="#007aff" stroke-width="1.5" stroke-linejoin="round"/>
              <path d="M9 12h6M9 16h4" stroke="#007aff" stroke-width="1.7" stroke-linecap="round"/>
              <circle cx="17" cy="17" r="3" fill="#e6f0ff" stroke="#007aff" stroke-width="1.4"/>
              <path d="M17 15.8v1.8" stroke="#007aff" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </button>
          <button class="btn btn-outline btn-square has-tip" type="button" id="btnExportBrandsPdf" data-tip="PDF por marca" aria-label="PDF por marca">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" fill="#fff" stroke="#c74a14" stroke-width="1.5" stroke-linejoin="round"/>
              <path d="M14 2v6h6" stroke="#c74a14" stroke-width="1.5" stroke-linejoin="round"/>
              <path d="M8 13h8M8 17h8" stroke="#c74a14" stroke-width="1.6" stroke-linecap="round"/>
              <path d="M8 9h3" stroke="#c74a14" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
          </button>
        </div>

        <span class="actions-spacer"></span>

        <a href="{{ route('propuestas-comerciales.fallo.show', $propuestaComercial) }}" class="btn btn-rust">Acta de fallo</a>
        <a href="{{ route('propuestas-comerciales.cliente.show', $propuestaComercial) }}" class="btn btn-primary">Aprobar</a>
      </div>
    </div>



    <div class="summary-grid" id="summaryFilters">
      <button class="summary-cell filter-summary active" type="button" data-filter="all"><div class="summary-value text-blue" id="sumAll">0</div><div class="summary-label">Todos</div></button>
      <button class="summary-cell filter-summary" type="button" data-filter="exact"><div class="summary-value text-success" id="sumExact">0</div><div class="summary-label">Exactos</div></button>
      <button class="summary-cell filter-summary" type="button" data-filter="similar"><div class="summary-value text-blue" id="sumSimilar">0</div><div class="summary-label">Similares</div></button>
      <button class="summary-cell filter-summary" type="button" data-filter="not_found"><div class="summary-value text-danger" id="sumNotFound">0</div><div class="summary-label">No encontrados</div></button>
      <button class="summary-cell filter-summary" type="button" data-filter="priced"><div class="summary-value" id="sumSale">$0</div><div class="summary-label">Subtotal venta</div></button>
      <button class="summary-cell filter-summary" type="button" data-filter="profit"><div class="summary-value text-success" id="sumProfit">$0</div><div class="summary-label">Utilidad</div></button>
      <button class="summary-cell filter-summary" type="button" data-filter="margin"><div class="summary-value" id="sumMargin">0%</div><div class="summary-label">Margen</div></button>
    </div>

    {{-- ===== Margen global como botón desplegable (colapsable) ===== --}}
    <div class="global-margin-wrap" id="globalMarginWrap">
      <button class="global-margin-toggle" type="button" id="btnToggleGlobalMargin" aria-expanded="false" aria-controls="globalMarginPanel">
        <span class="gm-toggle-left">
          <span class="gm-toggle-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="5" x2="5" y2="19"></line><circle cx="6.5" cy="6.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle></svg>
          </span>
          <span class="gm-toggle-text">
            <span class="gm-toggle-title">Margen global</span>
            <span class="gm-toggle-sub">Define el porcentaje de utilidad de la cotización</span>
          </span>
        </span>
        <span class="gm-toggle-chevron">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </span>
      </button>

      <div class="global-margin-panel" id="globalMarginPanel">
        <div class="global-margin">
          <div class="field">
            <label>Margen global %</label>
            <input class="input" id="globalMarginInput" type="number" step="0.01" value="{{ $propuestaComercial->porcentaje_utilidad ?: 25 }}">
          </div>
          <button class="btn btn-ghost" type="button" id="btnSaveGlobalMargin">Guardar margen</button>
          <button class="btn btn-outline" type="button" id="btnApplyGlobalMargin">Aplicar a todas</button>
        </div>
      </div>
    </div>
    {{-- Aviso compacto, pegado a la lista de partidas --}}
    <div id="noticeBox" class="notice">
      <span class="notice-dot"></span>
      <span><strong id="noticeCount">0 partidas</strong> no encontradas en catálogo — usa "Buscar manualmente" para encontrar alternativas.</span>
    </div>

    <div class="items-list" id="itemsList"></div>
  </div>

  <div id="toastProcess" class="toast-process">
    <div class="toast-bar-container"><div id="toastFill" class="toast-bar-fill"></div></div>
    <div class="toast-header" onclick="document.getElementById('toastProcess').classList.toggle('expanded')">
      <div>
        <div id="toastTitle" class="toast-title">Procesando...</div>
        <div id="toastText" class="toast-subtitle">0/0 completados</div>
      </div>
      <div class="toast-header-right">
        <span class="toast-error-badge" id="toastErrorBadge"></span>
        <span class="toast-chevron"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg></span>
      </div>
    </div>
    <div class="toast-body">
      <div id="toastErrors" class="toast-error-list"></div>
    </div>
  </div>

  <div class="modal-backdrop" id="manualModal">
    <div class="modal">
      <div class="modal-head">
        <div>
          <h2 class="modal-title">Búsqueda manual</h2>
          <p class="modal-subtitle" id="manualSubtitle">Busca por nombre, SKU, marca, color o descripción.</p>
        </div>
        <button class="btn btn-ghost btn-small" type="button" onclick="closeManualModal()"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
      </div>
      <div class="modal-body">
        <div style="position:relative; margin-bottom: 24px;">
          <input class="input" id="manualQueryInput" placeholder="Buscar producto..." autocomplete="off">
          <button type="button" onclick="clearManualSearch()" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); border:0; background:transparent; color:var(--muted); cursor:pointer; display:flex; align-items:center;"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
        </div>
        <div class="modal-tabs">
          <button class="tab-btn active" type="button" id="manualTabCatalog">Catálogo interno</button>
          <button class="tab-btn" type="button" id="manualTabInternet">Internet</button>
        </div>
        <div id="manualSearchStatus" class="result-meta" style="margin-bottom:16px;">Escribe para buscar automáticamente.</div>
        <div id="manualResults"></div>
      </div>
    </div>
  </div>

  <div class="modal-backdrop" id="addItemModal">
    <div class="modal">
      <div class="modal-head">
        <div>
          <h2 class="modal-title">Agregar nueva partida</h2>
          <p class="modal-subtitle">Crea un nuevo producto solicitado manualmente.</p>
        </div>
        <button class="btn btn-ghost btn-small" type="button" onclick="closeAddItemModal()"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
      </div>
      <div class="modal-body">
        <form id="addItemForm" onsubmit="storeNewItem(event)">
          <div class="row">
            <div class="col-12"><div class="field"><label>Producto solicitado</label><input class="input" name="descripcion_original" placeholder="Ej. 100 paquetes de gasas" required></div></div>
            <div class="col-6"><div class="field"><label>Cantidad</label><input class="input" type="number" step="0.01" name="cantidad_cotizada" value="1" required></div></div>
            <div class="col-6"><div class="field"><label>Unidad</label><input class="input" name="unidad_solicitada" value="pz"></div></div>
            <div class="col-6"><div class="field"><label>Costo unit.</label><input class="input" type="number" step="0.01" name="costo_unitario" value="0"></div></div>
            <div class="col-6"><div class="field"><label>Margen %</label><input class="input" type="number" step="0.01" name="porcentaje_utilidad" value="{{ $propuestaComercial->porcentaje_utilidad ?: 25 }}"></div></div>
          </div>
          <div class="action-row mt-3">
            <button class="btn btn-primary" type="submit">Agregar partida</button>
            <button class="btn btn-ghost" type="button" onclick="closeAddItemModal()">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal-backdrop" id="samplesModal">
    <div class="modal samples-modal">
      <div class="modal-head">
        <div>
          <h2 class="modal-title">Análisis de almacén</h2>
          <p class="modal-subtitle" id="samplesSubtitle">Producto</p>
        </div>
        <button class="btn btn-ghost btn-small" type="button" onclick="closeSamplesModal()"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
      </div>
      <div class="modal-body">
        <div class="samples-toolbar">
          <input class="input" id="samplesFilterInput" placeholder="Filtrar por nombre, SKU, marca, categoría o ubicación..." autocomplete="off" oninput="filterSamplesResults()">
          <button type="button" onclick="clearSamplesFilter()" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); border:0; background:transparent; color:var(--muted); cursor:pointer; display:flex; align-items:center;"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
        </div>
        <div id="samplesStatus" class="result-meta" style="margin-bottom:16px;">Buscando en catálogo...</div>
        <div id="samplesResults" class="samples-results"></div>
      </div>
    </div>
  </div>

  <div class="modal-backdrop" id="techSheetsModal">
    <div class="modal">
      <div class="modal-head">
        <div>
          <h2 class="modal-title">Fichas técnicas</h2>
          <p class="modal-subtitle" id="techSubtitle">Producto</p>
        </div>
        <button class="btn btn-ghost btn-small" type="button" onclick="closeTechSheetsModal()"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
      </div>
      <div class="modal-body" style="padding-top:0;">
        <div class="modal-tabs" style="margin-top:20px;">
          <button class="tab-btn active" type="button" id="techTabList" onclick="techShowList()">Vincular existente</button>
          <button class="tab-btn" type="button" id="techTabForm" onclick="techShowCreate()">Crear nueva</button>
        </div>
        
        <div id="techListPane">
          <input class="input" id="techQueryInput" placeholder="Buscar ficha por nombre, marca..." style="margin-bottom:16px;">
          <div id="techStatus" class="result-meta" style="margin-bottom:16px;"></div>
          <div id="techResults"></div>
        </div>

        <div id="techFormPane" style="display:none;">
          <form id="techForm" onsubmit="submitTechSheet(event)">
            <input type="hidden" name="tech_sheet_id" id="techFormId" value="">
            <div class="row">
              <div class="col-12"><div class="field"><label>Nombre del producto *</label><input class="input" name="product_name" required></div></div>
              <div class="col-6"><div class="field"><label>Marca</label><input class="input" name="brand"></div></div>
              <div class="col-6"><div class="field"><label>Modelo</label><input class="input" name="model"></div></div>
              <div class="col-6"><div class="field"><label>Referencia</label><input class="input" name="reference"></div></div>
              <div class="col-6"><div class="field"><label>Partida</label><input class="input" name="partida_number"></div></div>
              <div class="col-12"><div class="field"><label>Descripción</label><textarea class="input" name="user_description" rows="3"></textarea></div></div>
              <div class="col-12"><div class="field"><label>Imagen (opcional)</label><input class="input" type="file" name="image" accept="image/*" style="padding:8px; border:1px solid var(--line); background:transparent;"></div></div>
            </div>
            <div class="action-row mt-3">
              <button class="btn btn-primary" type="submit">Guardar ficha</button>
              <button class="btn btn-ghost" type="button" onclick="techShowList()">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>


  <div class="modal-backdrop" id="clarificationModal">
    <div class="modal">
      <div class="modal-head">
        <div>
          <h2 class="modal-title">Pregunta para junta de aclaraciones</h2>
          <p class="modal-subtitle" id="clarificationSubtitle">Redacta una duda técnica o solicita autorización para ofertar alternativa.</p>
        </div>
        <button class="btn btn-ghost btn-small" type="button" onclick="closeClarificationModal()"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
      </div>
      <div class="modal-body">
        <div class="field">
          <label>Idea de la pregunta</label>
          <textarea class="input" id="clarificationIdeaInput" rows="4" placeholder="Ej. No existe lapicero de aceite color negro permanente con las características exactas. ¿Podemos ofertar equivalente de nuestro catálogo?"></textarea>
        </div>

        <div class="field">
          <label>Pregunta estructurada</label>
          <textarea class="input" id="clarificationQuestionInput" rows="7" placeholder="Aquí aparecerá la pregunta formal para gobierno."></textarea>
        </div>

        <div id="clarificationCandidateBox" class="result-card" style="display:none;"></div>
        <div id="clarificationStatus" class="result-meta" style="margin-bottom:16px;">Escribe una idea y genera la redacción profesional.</div>

        <div class="action-row">
          <button class="btn btn-outline" type="button" id="btnGenerateClarification">Generar con IA</button>
          <button class="btn btn-primary" type="button" id="btnSaveClarification">Guardar pregunta</button>
          <button class="btn btn-ghost" type="button" onclick="closeClarificationModal()">Cancelar</button>
        </div>
      </div>
    </div>
  </div>


  <div class="modal-backdrop delete-confirm-backdrop" id="deleteItemModal" onclick="handleDeleteModalBackdrop(event)">
    <div class="delete-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="deleteItemModalTitle">
      <h2 class="delete-confirm-title" id="deleteItemModalTitle">¿Eliminar partida?</h2>
      <p class="delete-confirm-text">
        Esto eliminará <strong id="deleteItemName">esta partida</strong> de la propuesta comercial.
        Esta acción no se puede deshacer.
      </p>
      <div class="delete-confirm-actions">
        <button class="btn btn-modal-cancel" type="button" onclick="closeDeleteItemModal()">Cancelar</button>
        <button class="btn btn-modal-delete" type="button" id="btnConfirmDeleteItem" onclick="confirmDeleteItem()">Eliminar</button>
      </div>
    </div>
  </div>

</div>

<script>
  const csrfToken = @json(csrf_token());

  // SVGs Centralizados
  const svgs = {
    edit: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
    search: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>',
    box: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>',
    file: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
    question: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 2.2-3 4"></path><path d="M12 17h.01"></path></svg>',
    target: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="6"></circle><circle cx="12" cy="12" r="2"></circle></svg>',
    x: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
    trash: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>',
    dots: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="6" cy="12" r="1.5" fill="currentColor"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/><circle cx="18" cy="12" r="1.5" fill="currentColor"/></svg>',
    drag: '<svg width="12" height="20" viewBox="0 0 12 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="4" cy="6" r="1.5" fill="currentColor"/><circle cx="8" cy="6" r="1.5" fill="currentColor"/><circle cx="4" cy="10" r="1.5" fill="currentColor"/><circle cx="8" cy="10" r="1.5" fill="currentColor"/><circle cx="4" cy="14" r="1.5" fill="currentColor"/><circle cx="8" cy="14" r="1.5" fill="currentColor"/></svg>',
    chevron: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>',
    external: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>',
    doc_linked: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--success)"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>'
  };
  const routes = {
    suggestAll: @json(route('propuestas-comerciales.ajax.suggest-all', $propuestaComercial)),
    suggestItem: @json(url('/propuesta-comercial-items/__ID__/ajax/suggest')),
    updateItem: @json(url('/propuesta-comercial-items/__ID__/ajax/update')),
    updateStatus: @json(url('/propuesta-comercial-items/__ID__/ajax/status')),
    deleteItem: @json(url('/propuesta-comercial-items/__ID__/ajax/delete')),
    deselectItem: @json(url('/propuesta-comercial-items/__ID__/ajax/deselect')),
    manualSearch: @json(route('propuestas-comerciales.ajax.manual-search', $propuestaComercial)),
    reorder: @json(route('propuestas-comerciales.ajax.reorder-items', $propuestaComercial)),
    globalMargin: @json(route('propuestas-comerciales.ajax.global-margin', $propuestaComercial)),
    storeItem: @json(route('propuestas-comerciales.ajax.items.store', $propuestaComercial)),
    selectMatch: @json(url('/propuesta-comercial-items/__ID__/ajax/select-match/__MATCH__')),
    itemSamples: @json(url('/propuesta-comercial-items/__ID__/ajax/samples')),
    techSheetsList: @json(url('/propuesta-comercial-items/__ID__/ajax/tech-sheets')),
    linkTechSheet: @json(url('/propuesta-comercial-items/__ID__/ajax/tech-sheets/link')),
    createTechSheet: @json(url('/propuesta-comercial-items/__ID__/ajax/tech-sheets/create')),
    updateTechSheet: @json(url('/propuesta-comercial-fichas/__ID__/update')),
    techSheetPdf: @json(url('/tech-sheets/__ID__/pdf')),
    clarificationSuggest: @json(url('/propuesta-comercial-items/__ID__/ajax/clarification/suggest')),
    clarificationSave: @json(url('/propuesta-comercial-items/__ID__/ajax/clarification/save')),
    clarificationDelete: @json(url('/propuesta-comercial-items/__ID__/ajax/clarification/__QUESTION__/delete')),
    clarificationsPdf: @json(route('propuestas-comerciales.clarifications.pdf', $propuestaComercial)),
  };

  let items = @json($itemsPayload);
  let summary = @json($summaryPayload);
  let rawExportPayloads = @json($rawExportPayloads);
  const exportFolio = @json($exportFolio);
  const exportTitle = @json($exportTitle);
  let currentFilter = 'all';
  let manualItemId = null;
  let manualTab = 'catalog';
  let manualSearchTimer = null;
  let manualLastQuery = '';
  let manualCatalogResults = [];
  let manualInternetResults = [];
  let isSuggestingAll = false;
  let samplesItemId = null;
  let techItemId = null;
  let techSheetsCache = [];
  let currentLinkedSheetId = null;
  let clarificationItemId = null;
  let clarificationLastCandidate = null;
  let pendingDeleteItemId = null;

  function money(n) { return Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 2 }); }
  function moneyOrBlank(n) { return Number(n || 0) > 0 ? money(n) : ''; }
  function escapeHtml(value) { return String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;'); }

  function normalizeMatchSelection(item) {
    if (!item || typeof item !== 'object') return item;

    const humanAccepted = item.ui_status === 'accepted_item';

    if (!humanAccepted) {
      if (Array.isArray(item.matches)) {
        item.matches = item.matches.map(match => ({ ...match, seleccionado: false }));
      }

      item.producto_seleccionado = null;
      item.status_key = (Array.isArray(item.matches) && item.matches.length) || item.manual_external_link || item.catalog_product_name_manual
        ? 'similar'
        : 'not_found';
    }

    return item;
  }

  function normalizeItemsPayload(payload) {
    return Array.isArray(payload) ? payload.map(item => normalizeMatchSelection(item)) : payload;
  }

  items = normalizeItemsPayload(items) || [];

  async function ajax(url, options = {}) {
    const response = await fetch(url, {
      ...options,
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json', ...(options.headers || {}) }
    });
    const rawText = await response.text();
    let data = null;
    try { data = rawText ? JSON.parse(rawText) : null; } catch (error) {}
    if (!response.ok || !data || data.ok === false) throw new Error(data?.message || 'Error procesando la solicitud.');
    return data;
  }

  function urlFor(template, id) { return template.replace('__ID__', id); }

  function mergeTechSheetMeta(newItems) {
    if (!Array.isArray(newItems)) return newItems;
    const map = {}; items.forEach(i => { map[i.id] = i; });
    newItems.forEach(ni => {
      const prev = map[ni.id];
      if (prev) {
        if (ni.tech_sheet_id === undefined) ni.tech_sheet_id = prev.tech_sheet_id ?? null;
        if (ni.tech_sheet_name === undefined) ni.tech_sheet_name = prev.tech_sheet_name ?? null;
        if (ni.clarification_questions === undefined) ni.clarification_questions = prev.clarification_questions ?? [];
      }
    });
    return normalizeItemsPayload(newItems);
  }

  function isManualExternalChosen(item) { return !!item.manual_external_link; }
  function isCatalogAccepted(item) { return item.ui_status === 'accepted_item'; }

  function getSelectedCatalogProduct(item) {
    const selMatch = (item.matches || []).find(m => m.seleccionado);
    if (selMatch && selMatch.product) return { product: selMatch.product, score: Number(selMatch.score || 0) };
    if (item.producto_seleccionado) return { product: item.producto_seleccionado, score: Number(item.match_score || 0) };
    return null;
  }

  // --- Funciones para el Nuevo Toast de Progreso ---
  function showToast(title, text, done, total, errors = []) {
    const toast = document.getElementById('toastProcess');
    toast.classList.add('show');
    document.getElementById('toastTitle').textContent = title;
    document.getElementById('toastText').textContent = `${text} (${done}/${total})`;
    const pct = total > 0 ? Math.round((done / total) * 100) : 0;
    document.getElementById('toastFill').style.width = pct + '%';
    
    const errorsEl = document.getElementById('toastErrors');
    const badge = document.getElementById('toastErrorBadge');
    if (errors.length) {
      errorsEl.innerHTML = errors.map(e => `<div class="toast-error-item">${escapeHtml(e)}</div>`).join('');
      // NO se auto-expande: el panel de errores SOLO se abre cuando el usuario hace clic en el encabezado.
      // Solo mostramos una etiqueta indicando que hay errores disponibles.
      toast.classList.add('has-errors');
      if (badge) badge.textContent = errors.length + (errors.length === 1 ? ' error' : ' errores');
    } else {
      errorsEl.innerHTML = '';
      toast.classList.remove('has-errors');
      if (badge) badge.textContent = '';
    }
  }

  function hideToast() {
    const toast = document.getElementById('toastProcess');
    toast.classList.remove('show', 'expanded', 'has-errors');
    const badge = document.getElementById('toastErrorBadge');
    if (badge) badge.textContent = '';
    setTimeout(() => { document.getElementById('toastErrors').innerHTML = ''; }, 400);
  }

  function showInlineError(message) {
    showToast('Error', 'No se pudo completar la acción.', 1, 1, [message || 'Ocurrió un error.']);
    setTimeout(hideToast, 6000);
  }

  function statusLabel(item) {
    if (item.ui_status === 'accepted_item') return { text: 'Aceptado', cls: 'badge-success' };
    if (item.ui_status === 'manual_review') return { text: 'Revisión', cls: 'badge-warning' };
    if (item.ui_status === 'rejected_item') return { text: 'Rechazado', cls: 'badge-danger' };
    if (item.status_key === 'exact') return { text: 'Exacto', cls: 'badge-success' };
    if (item.status_key === 'similar') return { text: 'Similar', cls: 'badge-info' };
    return { text: 'No encontrado', cls: 'badge-danger' };
  }

  function recomputeSummaryFromItems() {
    const subtotalSale = items.reduce((acc, item) => acc + Number(item.subtotal || 0), 0);
    const subtotalCost = items.reduce((acc, item) => acc + (Number(item.costo_unitario || 0) * Number(item.cantidad_cotizada || 0)), 0);
    const profit = subtotalSale - subtotalCost;
    const margin = subtotalCost > 0 ? Math.round((profit / subtotalCost) * 100) : 0;

    summary = {
      ...summary,
      exact: items.filter(item => item.status_key === 'exact').length,
      similar: items.filter(item => item.status_key === 'similar').length,
      not_found: items.filter(item => item.status_key === 'not_found').length,
      subtotal_sale: subtotalSale,
      subtotal_cost: subtotalCost,
      profit,
      margin,
      total_items: items.length,
    };
  }

  function renderSummary() {
    recomputeSummaryFromItems();
    const total = summary.total_items || items.length;
    document.getElementById('sumAll').textContent = total;
    document.getElementById('sumExact').textContent = summary.exact || 0;
    document.getElementById('sumSimilar').textContent = summary.similar || 0;
    document.getElementById('sumNotFound').textContent = summary.not_found || 0;
    document.getElementById('sumSale').textContent = money(summary.subtotal_sale);
    document.getElementById('sumProfit').textContent = money(summary.profit);
    document.getElementById('sumMargin').textContent = `${summary.margin || 0}%`;
    document.getElementById('itemsCountText').textContent = total;
    document.querySelectorAll('.filter-summary').forEach(btn => btn.classList.toggle('active', btn.dataset.filter === currentFilter));
    const notice = document.getElementById('noticeBox');
    if ((summary.not_found || 0) > 0) { document.getElementById('noticeCount').textContent = `${summary.not_found} partidas`; notice.classList.add('show'); } 
    else { notice.classList.remove('show'); }
  }

  function renderItems() {
    renderSummary();
    const list = document.getElementById('itemsList');
    const filtered = items.filter(item => {
      if (currentFilter === 'all') return true;
      if (currentFilter === 'exact') return item.status_key === 'exact';
      if (currentFilter === 'similar') return item.status_key === 'similar';
      if (currentFilter === 'not_found') return item.status_key === 'not_found';
      if (currentFilter === 'priced') return Number(item.subtotal || 0) > 0;
      if (currentFilter === 'profit') return Number(item.precio_unitario || 0) > Number(item.costo_unitario || 0);
      if (currentFilter === 'margin') return Number(item.item_margin_pct || 0) > 0;
      return true;
    });
    list.innerHTML = filtered.map((item, idx) => renderItemCard(item, idx)).join('');
    bindDragEvents();
  }

  function renderItemCard(item, idx) {
    const badge = statusLabel(item);
    const qty = Number(item.cantidad_cotizada || item.cantidad_maxima || item.cantidad_minima || 1);
    const cost = Number(item.costo_unitario || 0);
    const price = Number(item.precio_unitario || 0);
    const subtotal = Number(item.subtotal || price * qty);
    const hasPrices = cost > 0 || price > 0 || subtotal > 0;

    const moneyRowHtml = hasPrices ? `
      <div class="money-row">
        ${cost > 0 ? `<span>Costo <strong>${money(cost)}</strong></span>` : ''}
        ${price > 0 ? `<span>Precio <strong>${money(price)}</strong></span>` : ''}
        ${subtotal > 0 ? `<span>Subtotal <strong>${money(subtotal)}</strong></span>` : ''}
      </div>
    ` : '';

    return `
      <div class="item-card" data-id="${item.id}" draggable="false">
        <div class="item-main" onclick="toggleItem(${item.id})">
          <button class="drag-handle" type="button" draggable="${currentFilter === 'all' ? 'true' : 'false'}" title="Mover posición" aria-label="Mover posición" onclick="event.stopPropagation()">
            ${svgs.drag}
          </button>
          <div class="item-index">${idx + 1}</div>
          <div>
            <h3 class="item-name">${escapeHtml(item.descripcion_original || 'Producto sin descripción')}</h3>
            <div class="item-meta">
              ${qty} ${escapeHtml(item.unidad_solicitada || 'pz')}
              ${(item.manual_external_supplier || item.producto_seleccionado?.brand) ? ' · ' + escapeHtml(item.manual_external_supplier || item.producto_seleccionado.brand) : ''}
              ${item.tech_sheet_id ? ` · ${svgs.doc_linked} <span style="color:var(--success); font-weight:700;">Ficha</span>` : ''}
            </div>
          </div>
          <span class="badge ${badge.cls}">${badge.text}</span>

          ${moneyRowHtml}
          
          <div class="action-cell" onclick="event.stopPropagation()">
            <div class="dropdown-menu" id="dropdown-${item.id}">
              <button class="dropdown-item" onclick="suggestItem(${item.id}, this); toggleDropdown(event, ${item.id});">${svgs.target} Hacer match</button>
              <button class="dropdown-item" onclick="openManualModal(${item.id}); toggleDropdown(event, ${item.id});">${svgs.search} Buscar manualmente</button>
              <button class="dropdown-item" onclick="openManualModal(${item.id}, 'internet'); toggleDropdown(event, ${item.id});">${svgs.external} Buscar en internet</button>
              <button class="dropdown-item" onclick="openSamplesModal(${item.id}); toggleDropdown(event, ${item.id});">${svgs.box} Muestras / stock</button>
              <div class="dropdown-divider"></div>
              <button class="dropdown-item" onclick="setItemStatus(${item.id}, 'accepted_item'); toggleDropdown(event, ${item.id});">${svgs.check} Aceptar partida</button>
              <button class="dropdown-item" onclick="setItemStatus(${item.id}, 'manual_review'); toggleDropdown(event, ${item.id});">${svgs.target} Marcar en revisión</button>
              <button class="dropdown-item text-danger" onclick="setItemStatus(${item.id}, 'rejected_item'); toggleDropdown(event, ${item.id});">${svgs.x} Rechazar partida</button>
              <div class="dropdown-divider"></div>
              <button class="dropdown-item text-danger" onclick="deleteItem(${item.id}); toggleDropdown(event, ${item.id});">${svgs.trash} Eliminar partida</button>
            </div>
            <button class="btn-kebab" onclick="toggleDropdown(event, ${item.id})">${svgs.dots}</button>
          </div>

          <div class="chevron">
            ${svgs.chevron}
          </div>
        </div>

        <div class="item-details" id="item-details-${item.id}" onclick="event.stopPropagation()">
          <div class="item-tabs-container">
            <button class="item-tab-btn active" id="tab-${item.id}-catalog" onclick="switchTab(${item.id}, 'catalog')">Catálogo</button>
            <button class="item-tab-btn" id="tab-${item.id}-internet" onclick="switchTab(${item.id}, 'internet')">Internet</button>
            <button class="item-tab-btn" id="tab-${item.id}-tech" onclick="switchTab(${item.id}, 'tech')">Ficha técnica</button>
            <button class="item-tab-btn" id="tab-${item.id}-questions" onclick="switchTab(${item.id}, 'questions')">Preguntas</button>
            <button class="item-tab-btn" id="tab-${item.id}-edit" onclick="switchTab(${item.id}, 'edit')">Editar</button>
          </div>

          <div class="item-tab-pane active" id="pane-${item.id}-catalog">${renderCatalogSection(item)}</div>
          <div class="item-tab-pane" id="pane-${item.id}-internet">${renderExternalSection(item)}</div>
          <div class="item-tab-pane" id="pane-${item.id}-tech">${renderTechSheetLinked(item)}</div>
          <div class="item-tab-pane" id="pane-${item.id}-questions">${renderClarificationSection(item)}</div>
          <div class="item-tab-pane" id="pane-${item.id}-edit">${renderEditForm(item)}</div>
        </div>
      </div>
    `;
  }

  function renderCatalogSection(item) {
    const qty = Number(item.cantidad_cotizada || item.cantidad_maxima || item.cantidad_minima || 1);

    function productCost(product) {
      const itemCost = Number(item.costo_unitario || 0);
      const productCost = Number(product?.cost || 0);
      return itemCost > 0 ? itemCost : productCost;
    }

    function productPrice(product) {
      const itemPrice = Number(item.precio_unitario || 0);
      const productPrice = Number(product?.price || 0);
      return itemPrice > 0 ? itemPrice : productPrice;
    }

    function productSubtotal(product) {
      const itemSubtotal = Number(item.subtotal || 0);
      if (itemSubtotal > 0) return itemSubtotal;

      const price = productPrice(product);
      return price * qty;
    }

    if (item.matches?.length) {
      return `
        <div>
          ${item.matches.map((m, i) => {
            const isSelected = !!m.seleccionado;
            const p = m.product || {};
            const cost = isSelected ? productCost(p) : Number(p.cost || 0);
            const price = isSelected ? productPrice(p) : Number(p.price || 0);
            const subtotal = isSelected ? productSubtotal(p) : price * qty;

            return `
              <div class="result-card ${isSelected ? 'is-selected' : ''}" ${isSelected ? 'style="border-color:var(--success);"' : ''}>
                ${isSelected ? `<button class="deselect-clean-btn" type="button" title="Deseleccionar producto" aria-label="Deseleccionar producto" onclick="deselectItem(${item.id})">${svgs.x}</button>` : ''}
                <div class="d-flex align-items-center mb-2">
                  <div class="result-title mb-0" style="margin-right:12px;">
                    ${escapeHtml(p.name || 'Producto sin nombre')}
                  </div>
                  ${isSelected ? '<span class="badge badge-success">Aceptado</span>' : `<span class="badge badge-info">${Number(m.score || 0).toFixed(0)}%</span>`}
                </div>

                <div class="result-meta mb-2">
                  SKU: ${escapeHtml(p.sku || '—')} · ${escapeHtml(item.manual_external_supplier || p.brand || '—')} · ${item.modelo ? 'Modelo: ' + escapeHtml(item.modelo) + ' · ' : ''}Stock: ${p.stock ?? '—'} · ${Number(m.score || 0).toFixed(0)}%
                </div>

                <div class="result-meta mt-2">
                  Costo <strong>${money(cost)}</strong> ·
                  Precio <strong>${money(price)}</strong> ·
                  Subtotal <strong>${money(subtotal)}</strong>
                </div>

                <div class="action-row mt-3">
                  ${isSelected
                    ? '<span class="badge badge-success">Producto seleccionado</span>'
                    : `<button class="btn btn-success btn-small" type="button" onclick="selectMatch(${item.id}, ${m.id})">${svgs.check} Usar esta</button>`
                  }
                  ${i === 0 ? '<span class="badge badge-blue">Principal</span>' : ''}
                </div>
              </div>
            `;
          }).join('')}
        </div>
      `;
    }

    if (item.producto_seleccionado) {
      const p = item.producto_seleccionado;
      const cost = productCost(p);
      const price = productPrice(p);
      const subtotal = productSubtotal(p);

      return `
        <div class="result-card is-selected" style="border-color:var(--success);">
          <button class="deselect-clean-btn" type="button" title="Deseleccionar producto" aria-label="Deseleccionar producto" onclick="deselectItem(${item.id})">${svgs.x}</button>
          <div class="d-flex align-items-center mb-2">
            <div class="result-title mb-0" style="margin-right:12px;">
              ${escapeHtml(p.name || 'Producto seleccionado')}
            </div>
            <span class="badge badge-success">Aceptado</span>
          </div>

          <div class="result-meta">
            SKU: ${escapeHtml(p.sku || '—')} · ${escapeHtml(item.manual_external_supplier || p.brand || '—')} · ${item.modelo ? 'Modelo: ' + escapeHtml(item.modelo) + ' · ' : ''}Stock: ${p.stock ?? '—'}${item.match_score ? ' · ' + Number(item.match_score || 0).toFixed(0) + '%' : ''}
          </div>

          <div class="result-meta mt-2">
            Costo <strong>${money(cost)}</strong> ·
            Precio <strong>${money(price)}</strong> ·
            Subtotal <strong>${money(subtotal)}</strong>
          </div>
        </div>
      `;
    }

    return `
      <div class="result-card">
        <div class="result-title">Sin matches en catálogo</div>
        <div class="result-meta">No hay coincidencias guardadas todavía. Puedes ejecutar “Hacer match” o buscar manualmente.</div>
        <div class="action-row mt-3">
          <button class="btn btn-outline btn-small" type="button" onclick="suggestItem(${item.id}, this)">${svgs.target} Hacer match</button>
          <button class="btn btn-ghost btn-small" type="button" onclick="openManualModal(${item.id})">${svgs.search} Buscar manualmente</button>
        </div>
      </div>
    `;
  }

  function renderExternalSection(item) {
    let html = '';

    if (item.manual_external_supplier || item.manual_external_link || item.manual_catalog_product_name || item.catalog_product_name_manual) {
      html += `
        <div class="external-box">
          <div class="result-title">
            ${escapeHtml(item.manual_external_supplier || item.manual_catalog_product_name || item.catalog_product_name_manual || 'Referencia manual')}
            <span class="badge badge-warning">Manual</span>
          </div>
          <div class="result-meta mt-2">Costo estimado: <strong>${money(item.costo_unitario)}</strong></div>
          ${item.manual_external_link ? `
            <div class="action-row mt-3">
              <a href="${escapeHtml(item.manual_external_link)}" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-small">${svgs.external} Ver enlace</a>
            </div>
          ` : ''}
        </div>
      `;
    }

    if (item.external_matches?.length) {
      html += `
        <div>
          ${item.external_matches.map(e => `
            <div class="external-box">
              <div class="result-title">${escapeHtml(e.title || 'Referencia externa')}</div>
              <div class="result-meta mb-2">
                ${escapeHtml(e.source || 'Internet')}
                ${e.seller ? ' · ' + escapeHtml(e.seller) : ''}
                · Score ${Number(e.score || 0).toFixed(0)}%
              </div>
              <div class="result-meta mt-2">${e.price ? `<strong>${money(e.price)}</strong>` : 'Precio por validar'}</div>
              <div class="action-row mt-3">
                ${e.url ? `<a class="btn btn-outline btn-small" href="${escapeHtml(e.url)}" target="_blank" rel="noopener noreferrer">${svgs.external} Ver enlace</a>` : ''}
              </div>
            </div>
          `).join('')}
        </div>
      `;
    }

    if (!html) {
      html = `
        <div class="result-card">
          <div class="result-title">Sin opciones de internet</div>
          <div class="result-meta">No hay referencias externas guardadas todavía. Puedes buscarlas desde la búsqueda manual en la pestaña Internet.</div>
          <div class="action-row mt-3">
            <button class="btn btn-outline btn-small" type="button" onclick="openManualModal(${item.id}, 'internet')">${svgs.search} Buscar en internet</button>
          </div>
        </div>
      `;
    }

    return html;
  }

  function renderTechSheetLinked(item) {
    if (!item.tech_sheet_id) {
      return `
        <div class="result-card">
          <div class="result-title">${svgs.file} Sin ficha técnica vinculada</div>
          <div class="result-meta">Vincula una ficha existente o crea una nueva para esta partida.</div>
          <div class="action-row mt-3">
            <button class="btn btn-outline btn-small" type="button" onclick="openTechSheetsModal(${item.id})">${svgs.file} Vincular ficha</button>
          </div>
        </div>
      `;
    }

    const pdfUrl = urlFor(routes.techSheetPdf, item.tech_sheet_id);
    return `
      <div class="result-card">
        <div class="result-title">${svgs.file} ${escapeHtml(item.tech_sheet_name || 'Ficha técnica')}</div>
        <div class="action-row mt-3">
          <button class="btn btn-outline btn-small" type="button" onclick="toggleTechPreview(${item.id}, ${JSON.stringify(pdfUrl).replaceAll('"', '&quot;')})">Ver ficha</button>
          <a class="btn btn-outline btn-small" target="_blank" rel="noopener noreferrer" href="${escapeHtml(pdfUrl)}">${svgs.external} Abrir PDF</a>
          <button class="btn btn-ghost btn-small" type="button" onclick="openTechSheetsModal(${item.id})">Cambiar</button>
        </div>
        <div id="tech-preview-${item.id}" class="tech-preview" style="display:none; margin-top:12px;"></div>
      </div>
    `;
  }

  function toggleTechPreview(itemId, pdfUrl) {
    const box = document.getElementById(`tech-preview-${itemId}`);
    if (!box) return;

    const isHidden = box.style.display === 'none' || box.style.display === '';

    if (isHidden) {
      box.innerHTML = `
        <iframe
          src="${escapeHtml(pdfUrl)}"
          title="Ficha técnica"
          style="width:100%; height:560px; border:1px solid var(--line); border-radius:var(--radius-card); background:#fff;"></iframe>
      `;
      box.style.display = 'block';
      box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else {
      box.style.display = 'none';
      box.innerHTML = '';
    }
  }


  function getClarificationText(q) {
    if (!q) return '';

    if (typeof q === 'string') {
      return q;
    }

    return q.pregunta_generada ||
      q.question ||
      q.generated_question ||
      q.text ||
      q.pregunta ||
      '';
  }

  function getClarificationCandidate(q) {
    if (!q || typeof q !== 'object') return null;

    if (q.catalog_candidate && typeof q.catalog_candidate === 'object') {
      return q.catalog_candidate;
    }

    if (q.producto_sugerido || q.sku_sugerido || q.marca_sugerida) {
      return {
        name: q.producto_sugerido || '',
        sku: q.sku_sugerido || '',
        brand: q.marca_sugerida || '',
        price: q.precio_sugerido || null
      };
    }

    return null;
  }

  function renderClarificationSection(item) {
    const questions = Array.isArray(item.clarification_questions) ? item.clarification_questions : [];

    const questionsHtml = questions.length ? questions.map((q, index) => {
      const questionText = getClarificationText(q);
      const candidate = getClarificationCandidate(q);

      return `
        <div class="result-card">
          <div class="result-title">
            ${svgs.question} Pregunta ${index + 1}
            <span class="badge badge-blue">Junta de aclaraciones</span>
          </div>

          <div class="result-meta" style="white-space:pre-wrap; line-height:1.55; color:var(--ink);">
            ${escapeHtml(questionText)}
          </div>

          ${candidate?.name ? `
            <div class="result-meta mt-2">
              Alternativa sugerida:
              <strong>${escapeHtml(candidate.name)}</strong>
              ${candidate.sku ? ' · SKU ' + escapeHtml(candidate.sku) : ''}
              ${candidate.brand ? ' · ' + escapeHtml(candidate.brand) : ''}
              ${candidate.price ? ' · Precio ' + money(candidate.price) : ''}
            </div>
          ` : ''}

          <div class="action-row mt-3">
            <button class="btn btn-ghost btn-small" type="button" onclick="deleteClarificationQuestion(${item.id}, '${escapeHtml(q.id || '')}')">
              Eliminar
            </button>
          </div>
        </div>
      `;
    }).join('') : `
      <div class="result-card">
        <div class="result-title">Sin preguntas guardadas</div>
        <div class="result-meta">
          Agrega una pregunta individual para esta partida. Al final podrás descargar el PDF de junta de aclaraciones con todas las preguntas.
        </div>
      </div>
    `;

    return `
      <div>
        ${questionsHtml}

        <div class="action-row mt-3">
          <button class="btn btn-outline btn-small" type="button" onclick="openClarificationModal(${item.id})">
            ${svgs.question} Nueva pregunta
          </button>

          <button class="btn btn-primary btn-small" type="button" onclick="openClarificationsPdf()">
            ${svgs.file} PDF junta de aclaraciones
          </button>
        </div>
      </div>
    `;
  }

  function renderEditForm(item) {
    return `
      <form class="edit-form show" id="edit-form-${item.id}" onsubmit="saveItem(event, ${item.id})" style="border:none; padding:0; background:transparent; margin:0;">
        <div class="row">
          <div class="col-12 col-md-6 field"><label>Producto</label><input class="input" name="descripcion_original" value="${escapeHtml(item.descripcion_original || '')}"></div>
          <div class="col-6 col-md-2 field"><label>Cant.</label><input class="input" type="number" step="0.01" name="cantidad_cotizada" value="${Number(item.cantidad_cotizada || 1)}"></div>
          <div class="col-6 col-md-2 field"><label>Unidad</label><input class="input" name="unidad_solicitada" value="${escapeHtml(item.unidad_solicitada || '')}"></div>
          <div class="col-6 col-md-2 field"><label>Costo unit.</label><input class="input" type="number" step="0.01" name="costo_unitario" value="${Number(item.costo_unitario || 0)}"></div>
          <div class="col-6 col-md-3 field"><label>Margen %</label><input class="input" type="number" step="0.01" name="porcentaje_utilidad" value="${Number(item.item_margin_pct || 25)}"></div>
          <div class="col-6 col-md-4 field"><label>Marca</label><input class="input" name="brand" value="${escapeHtml(item.manual_external_supplier || item.producto_seleccionado?.brand || '')}"></div>
          <div class="col-6 col-md-5 field"><label>Modelo</label><input class="input" name="model" value="${escapeHtml(item.modelo || item.producto_seleccionado?.model || '')}"></div>
          <div class="col-12 col-md-12 field"><label>Enlace externo</label><input class="input" name="external_link" value="${escapeHtml(item.manual_external_link || '')}"></div>
        </div>
        <div class="action-row mt-3">
          <button class="btn btn-primary btn-small" type="submit">Guardar cambios</button>
        </div>
      </form>`;
  }

  // --- Interaction Logic ---
  function toggleItem(id, forceOpen = false) {
    const card = document.querySelector(`.item-card[data-id="${id}"]`);
    if(card) {
      if(forceOpen) card.classList.add('open');
      else card.classList.toggle('open');
    }
  }

  function switchTab(itemId, tabName) {
    document.querySelectorAll(`#item-details-${itemId} .item-tab-pane`).forEach(el => el.classList.remove('active'));
    document.querySelectorAll(`#item-details-${itemId} .item-tab-btn`).forEach(el => el.classList.remove('active'));
    document.getElementById(`pane-${itemId}-${tabName}`)?.classList.add('active');
    document.getElementById(`tab-${itemId}-${tabName}`)?.classList.add('active');
  }

  function toggleDropdown(event, itemId) {
    event.stopPropagation();
    const target = document.getElementById(`dropdown-${itemId}`);
    const isShowing = target.classList.contains('show');
    document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove('show'));
    if(!isShowing) target.classList.add('show');
  }

  document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove('show'));
  });

  // --- API / State Management ---
  function updateItemInState(item) {
    item = normalizeMatchSelection(item);
    const idx = items.findIndex(i => i.id === item.id);
    if (idx >= 0) {
      const prev = items[idx] || {};
      if (item.tech_sheet_id === undefined) item.tech_sheet_id = prev.tech_sheet_id ?? null;
      if (item.tech_sheet_name === undefined) item.tech_sheet_name = prev.tech_sheet_name ?? null;
      if (item.clarification_questions === undefined) item.clarification_questions = prev.clarification_questions ?? [];
      items[idx] = item;
    }
  }

  function keepScrollAfterRender(anchorId, renderCallback) {
    const anchor = anchorId ? document.querySelector(`.item-card[data-id="${anchorId}"]`) : null;
    const anchorTop = anchor ? anchor.getBoundingClientRect().top : null;
    const previousScroll = window.scrollY;

    renderCallback();

    if (anchor && anchorTop !== null) {
      const nextAnchor = document.querySelector(`.item-card[data-id="${anchorId}"]`);

      if (nextAnchor) {
        const nextTop = nextAnchor.getBoundingClientRect().top;
        window.scrollTo({
          top: window.scrollY + nextTop - anchorTop,
          left: 0,
          behavior: 'auto'
        });
        return;
      }
    }

    window.scrollTo({ top: previousScroll, left: 0, behavior: 'auto' });
  }

  async function suggestItem(id, button = null) {
    const old = button?.innerHTML;

    if (button) {
      button.disabled = true;
      button.innerHTML = '<span class="loader"></span> Buscando...';
    }

    try {
      const data = await ajax(urlFor(routes.suggestItem, id), { method: 'POST', body: '{}' });
      updateItemInState(data.item);
      summary = data.summary || summary;
      keepScrollAfterRender(id, () => {
        renderItems();
        toggleItem(id, true);
        switchTab(id, 'catalog');
      });
    } catch (e) {
      showInlineError(e.message);
    } finally {
      if (button) {
        button.disabled = false;
        button.innerHTML = old;
      }
    }
  }

  async function selectMatch(itemId, matchId) {
    try {
      const data = await ajax(routes.selectMatch.replace('__ID__', itemId).replace('__MATCH__', matchId), {
        method: 'POST',
        body: '{}'
      });

      const statusData = await ajax(urlFor(routes.updateStatus, itemId), {
        method: 'POST',
        body: JSON.stringify({ ui_status: 'accepted_item' })
      });

      updateItemInState(statusData.item || data.item);
      summary = statusData.summary || data.summary || summary;

      keepScrollAfterRender(itemId, () => {
        renderItems();
        toggleItem(itemId, true);
        switchTab(itemId, 'catalog');
      });
    } catch (e) {
      showInlineError(e.message);
    }
  }

  async function deselectItem(itemId) {
    try {
      const data = await ajax(urlFor(routes.deselectItem, itemId), {
        method: 'POST',
        body: '{}'
      });

      updateItemInState(data.item);
      summary = data.summary || summary;

      keepScrollAfterRender(itemId, () => {
        renderItems();
        toggleItem(itemId, true);
        switchTab(itemId, 'catalog');
      });

      showToast('Producto deseleccionado', 'La partida quedó lista para seleccionar otra referencia.', 1, 1);
      setTimeout(hideToast, 3000);
    } catch (e) {
      showInlineError(e.message || 'No se pudo deseleccionar el producto.');
    }
  }

  async function setItemStatus(id, status) {
    try {
      const data = await ajax(urlFor(routes.updateStatus, id), { method: 'POST', body: JSON.stringify({ ui_status: status }) });
      updateItemInState(data.item); summary = data.summary || summary;
      keepScrollAfterRender(id, () => {
        renderItems();
        toggleItem(id, true);
      });
    } catch (e) { showInlineError(e.message); }
  }



  function recalculateSummaryFromItems() {
    const subtotalSale = items.reduce((acc, item) => acc + Number(item.subtotal || 0), 0);

    const subtotalCost = items.reduce((acc, item) => {
      const qty = Number(item.cantidad_cotizada || item.cantidad_maxima || item.cantidad_minima || 0);
      const cost = Number(item.costo_unitario || 0);
      return acc + (qty * cost);
    }, 0);

    const profit = subtotalSale - subtotalCost;
    const margin = subtotalCost > 0 ? Math.round((profit / subtotalCost) * 100) : 0;

    summary = {
      exact: items.filter(item => item.status_key === 'exact').length,
      similar: items.filter(item => item.status_key === 'similar').length,
      not_found: items.filter(item => item.status_key === 'not_found').length,
      subtotal_sale: subtotalSale,
      subtotal_cost: subtotalCost,
      profit,
      margin,
      total_items: items.length
    };

    return summary;
  }

  function openDeleteItemModal(id) {
    pendingDeleteItemId = id;
    const item = items.find(i => Number(i.id) === Number(id));
    const name = item?.descripcion_original || 'esta partida';
    const nameEl = document.getElementById('deleteItemName');
    const modal = document.getElementById('deleteItemModal');

    if (nameEl) nameEl.textContent = name;
    if (modal) modal.classList.add('show');
  }

  function closeDeleteItemModal() {
    const modal = document.getElementById('deleteItemModal');
    if (modal) modal.classList.remove('show');
    pendingDeleteItemId = null;
  }

  function handleDeleteModalBackdrop(event) {
    if (event.target && event.target.id === 'deleteItemModal') {
      closeDeleteItemModal();
    }
  }

  function deleteItem(id) {
    openDeleteItemModal(id);
  }

  async function confirmDeleteItem() {
    const id = pendingDeleteItemId;
    if (!id) return;

    const button = document.getElementById('btnConfirmDeleteItem');
    const old = button ? button.innerHTML : '';

    if (button) {
      button.disabled = true;
      button.innerHTML = '<span class="loader"></span> Eliminando...';
    }

    try {
      await ajax(urlFor(routes.deleteItem, id), {
        method: 'DELETE',
        body: '{}'
      });

      items = items.filter(i => Number(i.id) !== Number(id));

      items = items.map((item, index) => ({
        ...item,
        sort: index + 1
      }));

      recalculateSummaryFromItems();
      renderItems();
      closeDeleteItemModal();

      showToast('Partida eliminada', 'La partida se eliminó correctamente.', 1, 1);
      setTimeout(hideToast, 3000);
    } catch (e) {
      showInlineError(e.message || 'No se pudo eliminar la partida.');
    } finally {
      if (button) {
        button.disabled = false;
        button.innerHTML = old;
      }
    }
  }

  async function saveItem(event, id) {
    event.preventDefault();
    const payload = Object.fromEntries(new FormData(event.target).entries());
    if (payload.brand !== undefined) payload.external_supplier = payload.brand;
    if (payload.model !== undefined) payload.modelo = payload.model;
    try {
      const data = await ajax(urlFor(routes.updateItem, id), { method: 'POST', body: JSON.stringify(payload) });
      updateItemInState(data.item); summary = data.summary || summary;
      keepScrollAfterRender(id, () => {
        renderItems();
        toggleItem(id, true);
        switchTab(id, 'edit');
      });
      showToast('Guardado', 'Los cambios se han guardado exitosamente.', 1, 1);
      setTimeout(hideToast, 3000);
    } catch (e) { showInlineError(e.message); }
  }

  async function suggestAll() {
    if (isSuggestingAll) return;
    const pendingItems = items.filter(item => !Array.isArray(item.matches) || item.matches.length === 0);
    if (!pendingItems.length) {
      showToast('Sugerencias listas', 'Todas las partidas ya tienen candidatos para revisión humana.', 1, 1);
      setTimeout(hideToast, 3500); return;
    }
    isSuggestingAll = true;
    showToast('Buscando coincidencias', `Procesando ${pendingItems.length} partidas...`, 0, pendingItems.length, []);
    
    let done = 0, success = 0, errors = [], cursor = 0;
    async function worker() {
      while (cursor < pendingItems.length) {
        const item = pendingItems[cursor++];
        try {
          const data = await ajax(urlFor(routes.suggestItem, item.id), { method: 'POST', body: '{}' });
          if (data.item) updateItemInState(data.item); if (data.summary) summary = data.summary; success++;
        } catch (error) { 
          // SOLUCIÓN AL ERROR GIGANTE DE SQL
          let cleanMsg = error.message || 'Error desconocido';
          if (cleanMsg.includes('SQLSTATE')) {
             if (cleanMsg.includes('Data too long')) cleanMsg = 'Nombre o descripción de referencia demasiado largo (truncado).';
             else cleanMsg = 'Error en base de datos. Partida omitida.';
          }
          if (cleanMsg.length > 100) cleanMsg = cleanMsg.substring(0, 100) + '...';
          errors.push(`Partida #${item.sort || cursor}: ${cleanMsg}`); 
        }
        finally {
          done++;
          showToast(errors.length ? 'Proceso con errores' : 'Buscando coincidencias', `Listas ${done} de ${pendingItems.length}.`, done, pendingItems.length, errors);
          if (done % 5 === 0 || done === pendingItems.length) renderItems();
        }
      }
    }
    try { await Promise.all(Array.from({ length: 4 }, worker)); } finally { isSuggestingAll = false; setTimeout(() => { if(!errors.length) hideToast(); }, 4000); }
  }

  async function saveGlobalMargin(applyToItems) {
    const margin = document.getElementById('globalMarginInput').value;
    try {
      const data = await ajax(routes.globalMargin, { method: 'POST', body: JSON.stringify({ porcentaje_utilidad: margin, apply_to_items: applyToItems }) });
      items = mergeTechSheetMeta(data.items) || items; summary = data.summary || summary; renderItems();
      showToast('Margen Guardado', 'El margen global ha sido actualizado.', 1, 1);
      setTimeout(hideToast, 3000);
    } catch (e) { showInlineError(e.message); }
  }

  // --- Margen global colapsable (botón desplegable) ---
  function toggleGlobalMargin() {
    const wrap = document.getElementById('globalMarginWrap');
    const btn = document.getElementById('btnToggleGlobalMargin');
    const isOpen = wrap.classList.toggle('open');
    if (btn) btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  }

  // --- Modals Búsqueda Manual ---
  function openManualModal(id, preferredTab = 'catalog') {
    manualItemId = id;
    const item = items.find(i => i.id === id);

    document.getElementById('manualSubtitle').textContent = item?.descripcion_original || 'Producto';
    document.getElementById('manualQueryInput').value = item?.descripcion_original || '';
    document.getElementById('manualResults').innerHTML = '';
    document.getElementById('manualSearchStatus').textContent = 'Buscando coincidencias...';
    document.getElementById('manualModal').classList.add('show');

    manualTab = preferredTab === 'internet' ? 'internet' : 'catalog';
    document.getElementById('manualTabCatalog').classList.toggle('active', manualTab === 'catalog');
    document.getElementById('manualTabInternet').classList.toggle('active', manualTab === 'internet');

    manualLastQuery = '';
    scheduleManualSearch(250);
  }

  function closeManualModal() {
    document.getElementById('manualModal').classList.remove('show');
  }

  function clearManualSearch() {
    manualLastQuery = '';
    document.getElementById('manualQueryInput').value = '';
    document.getElementById('manualResults').innerHTML = '';
    document.getElementById('manualSearchStatus').textContent = 'Escribe para buscar automáticamente.';
  }

  function scheduleManualSearch(delay = 420) {
    clearTimeout(manualSearchTimer);
    manualSearchTimer = setTimeout(runManualSearchLive, delay);
  }

  async function runManualSearchLive() {
    const q = document.getElementById('manualQueryInput').value.trim();
    const resultsBox = document.getElementById('manualResults');
    const statusBox = document.getElementById('manualSearchStatus');

    if (!q) {
      resultsBox.innerHTML = '';
      statusBox.textContent = 'Escribe para buscar automáticamente.';
      return;
    }

    const cacheKey = manualTab + '::' + q;
    if (cacheKey === manualLastQuery) return;

    manualLastQuery = cacheKey;
    statusBox.innerHTML = '<span class="loader"></span> Buscando similitudes...';

    try {
      const params = new URLSearchParams({
        q,
        item_id: manualItemId,
        internet: manualTab === 'internet' ? '1' : '0'
      });

      const data = await ajax(routes.manualSearch + '?' + params.toString(), {
        method: 'GET',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        }
      });

      if (manualTab === 'internet') {
        manualInternetResults = data.internet || [];
        statusBox.textContent = `${manualInternetResults.length} referencias externas encontradas`;
        renderManualInternet(manualInternetResults);
      } else {
        manualCatalogResults = data.products || [];
        statusBox.textContent = `${manualCatalogResults.length} productos encontrados en products`;
        renderManualCatalog(manualCatalogResults);
      }
    } catch (e) {
      resultsBox.innerHTML = `<p class="result-meta">${escapeHtml(e.message)}</p>`;
      statusBox.textContent = 'No se pudo completar la búsqueda.';
    }
  }

  function renderManualCatalog(products) {
    const box = document.getElementById('manualResults');

    if (!products.length) {
      box.innerHTML = '<p class="result-meta">Sin resultados similares en products.</p>';
      return;
    }

    box.innerHTML = products.map((p, index) => `
      <div class="modal-result">
        <div style="min-width:0;">
          <div class="result-title mb-2">${escapeHtml(p.name)}</div>
          <div class="result-meta">
            SKU: ${escapeHtml(p.sku || '—')}
            ${p.brand ? ' · ' + escapeHtml(p.brand) : ''}
            ${p.model ? ' · Modelo: ' + escapeHtml(p.model) : ''}
            · Stock: ${Number(p.stock || 0).toLocaleString('es-MX')}
            · ${Number(p.similarity_pct || 0).toFixed(0)}%
          </div>
          <div class="result-meta mt-2">
            ${p.unit ? `<strong>Unidad:</strong> ${escapeHtml(p.unit)} · ` : ''}
            ${p.color ? `<strong>Color:</strong> ${escapeHtml(p.color)} · ` : ''}
            ${p.category ? `<strong>Categoría:</strong> ${escapeHtml(p.category)} · ` : ''}
            Costo <strong>${formatPlainMoney(p.cost)}</strong> · Precio <strong>${formatPlainMoney(p.price)}</strong>
          </div>
          ${p.description ? `<div class="result-meta mt-2">${escapeHtml(p.description)}</div>` : ''}
        </div>

        <button class="btn btn-outline btn-small" type="button" onclick="useManualCatalog(${index})">Usar</button>
      </div>
    `).join('');
  }

  function renderManualInternet(results) {
    const box = document.getElementById('manualResults');

    if (!results.length) {
      box.innerHTML = '<p class="result-meta">Sin resultados de internet.</p>';
      return;
    }

    box.innerHTML = results.map((r, index) => `
      <div class="modal-result">
        <div style="min-width:0;">
          <div class="result-title mb-2">${escapeHtml(r.title)}</div>
          <div class="result-meta">
            ${escapeHtml(r.source || 'Internet')}
            ${r.seller ? ' · ' + escapeHtml(r.seller) : ''}
            · Score ${Number(r.score || 0).toFixed(0)}%
          </div>
          <div class="result-meta mt-2">${r.price ? `<strong>${money(r.price)}</strong>` : 'Precio por validar'}</div>
          ${r.url ? `<div class="mt-2"><a class="btn btn-outline btn-small" target="_blank" rel="noopener noreferrer" href="${escapeHtml(r.url)}">${svgs.external} Ver referencia</a></div>` : ''}
        </div>

        <button class="btn btn-outline btn-small" type="button" onclick="useManualInternet(${index})">Usar</button>
      </div>
    `).join('');
  }

  async function useManualCatalog(index) {
    const product = manualCatalogResults[index];
    if (!product) return;

    const item = items.find(i => i.id === manualItemId);
    const margin = Number(item?.item_margin_pct || 25);
    const cost = Number(product.cost || 0);

    try {
      const data = await ajax(urlFor(routes.updateItem, manualItemId), {
        method: 'POST',
        body: JSON.stringify({
          catalog_product_name: product.name,
          costo_unitario: cost,
          porcentaje_utilidad: margin,
          brand: product.brand || '',
          model: product.model || '',
          external_link: ''
        })
      });

      updateItemInState(data.item);
      summary = data.summary || summary;
      closeManualModal();
      keepScrollAfterRender(manualItemId, () => {
        renderItems();
        toggleItem(manualItemId, true);
        switchTab(manualItemId, 'catalog');
      });
    } catch (e) {
      showInlineError(e.message);
    }
  }

  async function useManualInternet(index) {
    const result = manualInternetResults[index];
    if (!result) return;

    const item = items.find(i => i.id === manualItemId);
    const margin = Number(item?.item_margin_pct || 25);
    const cost = Number(result.price || 0);

    try {
      const data = await ajax(urlFor(routes.updateItem, manualItemId), {
        method: 'POST',
        body: JSON.stringify({
          catalog_product_name: result.title,
          costo_unitario: cost,
          porcentaje_utilidad: margin,
          external_supplier: result.source || result.seller || 'Proveedor externo',
          external_link: result.url || ''
        })
      });

      updateItemInState(data.item);
      summary = data.summary || summary;
      closeManualModal();
      keepScrollAfterRender(manualItemId, () => {
        renderItems();
        toggleItem(manualItemId, true);
        switchTab(manualItemId, 'internet');
      });
    } catch (e) {
      showInlineError(e.message);
    }
  }

  // --- Modal Agregar Partida ---
  function openAddItemModal() { document.getElementById('addItemModal').classList.add('show'); }
  function closeAddItemModal() { document.getElementById('addItemModal').classList.remove('show'); }

  async function storeNewItem(event) {
    event.preventDefault();
    try {
      const data = await ajax(routes.storeItem, { method: 'POST', body: JSON.stringify(Object.fromEntries(new FormData(event.target).entries())) });
      items = mergeTechSheetMeta(data.items) || items; summary = data.summary || summary; closeAddItemModal(); event.target.reset(); renderItems();
    } catch (e) { showInlineError(e.message); }
  }

  // --- Modal Muestras ---
  let samplesCache = [];
  let samplesNeededQty = 0;

  function closeSamplesModal() {
    document.getElementById('samplesModal').classList.remove('show');
  }

  async function openSamplesModal(id) {
    samplesItemId = id;
    const item = items.find(i => i.id === id);

    document.getElementById('samplesSubtitle').textContent = item?.descripcion_original || 'Producto';
    document.getElementById('samplesResults').innerHTML = '';
    document.getElementById('samplesStatus').innerHTML = '<span class="loader"></span> Buscando en catálogo y almacén...';
    const filter = document.getElementById('samplesFilterInput');
    if (filter) filter.value = '';
    document.getElementById('samplesModal').classList.add('show');

    try {
      const data = await ajax(urlFor(routes.itemSamples, id), { method: 'GET' });
      renderSamples(data);
    } catch (e) {
      document.getElementById('samplesStatus').textContent = e.message;
    }
  }

  function clearSamplesFilter() {
    const input = document.getElementById('samplesFilterInput');
    if (input) input.value = '';
    renderSamplesList(samplesCache);
  }

  function filterSamplesResults() {
    const q = (document.getElementById('samplesFilterInput')?.value || '').trim().toLowerCase();
    if (!q) {
      renderSamplesList(samplesCache);
      return;
    }

    const filtered = samplesCache.filter(c => {
      const haystack = [
        c.name,
        c.sku,
        c.brand,
        c.model,
        c.category,
        c.unit,
        c.description,
        c.location_summary,
        ...(c.locations || []).map(l => l.location),
        ...(c.details || []).map(d => `${d.label} ${d.value}`)
      ].join(' ').toLowerCase();

      return haystack.includes(q);
    });

    renderSamplesList(filtered);
  }

  function sampleImageHtml(c) {
    const src = c.image_url || (Array.isArray(c.photo_urls) && c.photo_urls.length ? c.photo_urls[0] : '') || c.photo_1 || c.photo_2 || c.photo_3 || c.image || c.thumbnail_url || c.photo_url || c.picture_url || '';

    if (!src) {
      return '<div class="sample-image">Sin imagen</div>';
    }

    return `
      <div class="sample-image">
        <img src="${escapeHtml(src)}" alt="${escapeHtml(c.name || 'Producto')}" loading="lazy" onerror="this.closest('.sample-image').innerHTML='Sin imagen';">
      </div>
    `;
  }

  function formatPlainMoney(n) {
    return Number(n || 0) > 0 ? money(n) : '—';
  }

  function renderSamples(data) {
    samplesNeededQty = Number(data.needed_qty || 0);
    samplesCache = Array.isArray(data.candidates) ? data.candidates : [];

    document.getElementById('samplesStatus').textContent =
      `Cantidad solicitada: ${samplesNeededQty.toLocaleString('es-MX')} · ${samplesCache.length} coincidencias en catálogo`;

    renderSamplesList(samplesCache);
  }

  function renderSamplesList(cands) {
    const box = document.getElementById('samplesResults');

    if (!cands.length) {
      box.innerHTML = '<p class="result-meta">No se encontraron productos similares en el catálogo interno.</p>';
      return;
    }

    box.innerHTML = cands.map((c, index) => {
      const locs = (c.locations || [])
        .map(l => `${escapeHtml(l.location || 'Ubicación')}: ${Number(l.qty || 0).toLocaleString('es-MX')}${Number(l.reserved || 0) > 0 ? ' (apartado ' + Number(l.reserved || 0).toLocaleString('es-MX') + ')' : ''}`)
        .join(' · ');

      const buyBadge = Number(c.to_buy || 0) > 0
        ? `<span class="badge badge-danger">Comprar ${Number(c.to_buy || 0).toLocaleString('es-MX')}</span>`
        : `<span class="badge badge-success">Stock suficiente</span>`;

      const details = Array.isArray(c.details) ? c.details.filter(d => d && d.value !== null && d.value !== undefined && String(d.value).trim() !== '') : [];
      const detailTags = details.slice(0, 10).map(d => `<span class="sample-tag"><strong>${escapeHtml(d.label)}:</strong>&nbsp;${escapeHtml(d.value)}</span>`).join('');

      return `
        <div class="sample-card">
          ${sampleImageHtml(c)}

          <div>
            <div class="sample-title-row">
              <div class="sample-title">${escapeHtml(c.name || 'Producto sin nombre')}</div>
              ${buyBadge}
              ${Number(c.similarity_pct || 0) > 0 ? `<span class="badge badge-info">Similitud ${Number(c.similarity_pct || 0).toFixed(0)}%</span>` : ''}
            </div>

            <div class="result-meta">
              SKU: ${escapeHtml(c.sku || '—')}
              ${c.brand ? ' · Marca: ' + escapeHtml(c.brand) : ''}
              ${c.model ? ' · Modelo: ' + escapeHtml(c.model) : ''}
              ${c.category ? ' · Categoría: ' + escapeHtml(c.category) : ''}
              ${c.unit ? ' · Unidad: ' + escapeHtml(c.unit) : ''}
            </div>

            ${c.description ? `<div class="sample-description">${escapeHtml(c.description)}</div>` : ''}

            <div class="sample-meta-grid">
              <div class="sample-mini"><span>En almacén</span><strong>${Number(c.net_available || 0).toLocaleString('es-MX')}</strong></div>
              <div class="sample-mini"><span>Apartado</span><strong>${Number(c.reserved || 0).toLocaleString('es-MX')}</strong></div>
              <div class="sample-mini"><span>Necesario</span><strong>${samplesNeededQty.toLocaleString('es-MX')}</strong></div>
              <div class="sample-mini"><span>Faltan</span><strong>${Number(c.to_buy || 0).toLocaleString('es-MX')}</strong></div>
              <div class="sample-mini"><span>Costo</span><strong>${formatPlainMoney(c.cost)}</strong></div>
              <div class="sample-mini"><span>Precio</span><strong>${formatPlainMoney(c.price)}</strong></div>
            </div>

            ${locs
              ? `<div class="sample-description"><strong>Ubicaciones:</strong> ${locs}</div>`
              : `<div class="sample-description">Sin inventario por ubicación${c.stock_field !== undefined ? ' (stock general: ' + Number(c.stock_field || 0).toLocaleString('es-MX') + ')' : ''}.</div>`}

            ${detailTags ? `<div class="sample-tags">${detailTags}</div>` : ''}
          </div>

          <div class="sample-actions">
            <div class="sample-stock-box">
              <div>Disponible: <strong>${Number(c.net_available || 0).toLocaleString('es-MX')}</strong></div>
              <div>Faltante: <strong>${Number(c.to_buy || 0).toLocaleString('es-MX')}</strong></div>
            </div>
            <button class="btn btn-primary btn-small" type="button" onclick="useSampleCatalog(${index})">Usar</button>
            ${c.public_url ? `<a class="btn btn-outline btn-small" target="_blank" rel="noopener noreferrer" href="${escapeHtml(c.public_url)}">Ver producto</a>` : ''}
          </div>
        </div>
      `;
    }).join('');
  }


  async function useSampleCatalog(index) {
    const product = samplesCache[index];
    if (!product || !samplesItemId) return;

    const item = items.find(i => Number(i.id) === Number(samplesItemId));
    const margin = Number(item?.item_margin_pct || 25);
    const cost = Number(product.cost || 0);

    try {
      const data = await ajax(urlFor(routes.updateItem, samplesItemId), {
        method: 'POST',
        body: JSON.stringify({
          catalog_product_name: product.name,
          costo_unitario: cost,
          porcentaje_utilidad: margin,
          brand: product.brand || '',
          model: product.model || '',
          external_link: product.public_url || ''
        })
      });

      updateItemInState(data.item);
      summary = data.summary || summary;
      closeSamplesModal();
      keepScrollAfterRender(samplesItemId, () => {
        renderItems();
        toggleItem(samplesItemId, true);
        switchTab(samplesItemId, 'edit');
      });

      showToast('Producto aplicado', 'La referencia se guardó en la partida. Revísala y acéptala manualmente cuando corresponda.', 1, 1);
      setTimeout(hideToast, 3000);
    } catch (e) {
      showInlineError(e.message || 'No se pudo usar este producto.');
    }
  }

  // --- Modal Fichas Técnicas ---
  function closeTechSheetsModal() {
    document.getElementById('techSheetsModal').classList.remove('show');
  }

  function techShowList() {
    document.getElementById('techListPane').style.display = '';
    document.getElementById('techFormPane').style.display = 'none';
    document.getElementById('techTabList').classList.add('active');
    document.getElementById('techTabForm').classList.remove('active');
  }

  function techShowCreate(sheet = null) {
    document.getElementById('techListPane').style.display = 'none';
    document.getElementById('techFormPane').style.display = '';
    document.getElementById('techTabList').classList.remove('active');
    document.getElementById('techTabForm').classList.add('active');

    const form = document.getElementById('techForm');
    form.reset();
    document.getElementById('techFormId').value = sheet?.id || '';

    if (sheet) {
      form.product_name.value = sheet.product_name || '';
      form.brand.value = sheet.brand || '';
      form.model.value = sheet.model || '';
      form.reference.value = sheet.reference || '';
      form.partida_number.value = sheet.partida_number || '';
    } else {
      const item = items.find(i => i.id === techItemId);
      form.product_name.value = item?.descripcion_original || '';
    }
  }

  function openTechSheetsModal(id) {
    techItemId = id;
    const item = items.find(i => i.id === id);

    document.getElementById('techSubtitle').textContent = item?.descripcion_original || 'Producto';
    document.getElementById('techQueryInput').value = item?.descripcion_original || '';
    document.getElementById('techSheetsModal').classList.add('show');

    techShowList();
    loadTechSheets();
  }

  async function loadTechSheets() {
    const q = document.getElementById('techQueryInput').value.trim();
    document.getElementById('techStatus').innerHTML = '<span class="loader"></span> Buscando fichas...';

    try {
      const params = new URLSearchParams({ q });
      const data = await ajax(urlFor(routes.techSheetsList, techItemId) + '?' + params.toString(), { method: 'GET' });
      renderTechSheets(data);
    } catch (e) {
      document.getElementById('techStatus').textContent = e.message;
    }
  }

  function renderTechSheets(data) {
    techSheetsCache = data.sheets || [];
    currentLinkedSheetId = data.linked_id || null;

    document.getElementById('techStatus').textContent = `${techSheetsCache.length} fichas encontradas`;
    const box = document.getElementById('techResults');

    if (!techSheetsCache.length) {
      box.innerHTML = '<p class="result-meta">No hay fichas. Crea una nueva en la pestaña de arriba.</p>';
      return;
    }

    box.innerHTML = techSheetsCache.map((s, i) => `
      <div class="modal-result">
        <div style="min-width:0;">
          <div class="result-title mb-2">
            ${escapeHtml(s.product_name)}
            ${s.id === currentLinkedSheetId ? '<span class="badge badge-success">Vinculada</span>' : ''}
          </div>
          <div class="result-meta">
            ${escapeHtml(s.brand || '—')}
            ${s.model ? ' · ' + escapeHtml(s.model) : ''}
            ${s.reference ? ' · Ref ' + escapeHtml(s.reference) : ''}
          </div>
          <div class="action-row mt-2">
            ${s.urls?.pdf ? `<a class="btn btn-outline btn-small" target="_blank" rel="noopener noreferrer" href="${escapeHtml(s.urls.pdf)}">${svgs.external} PDF</a>` : ''}
            ${s.urls?.public ? `<a class="btn btn-ghost btn-small" target="_blank" rel="noopener noreferrer" href="${escapeHtml(s.urls.public)}">Ficha pública</a>` : ''}
            <button class="btn btn-ghost btn-small" type="button" onclick="techEditInline(${i})">${svgs.edit} Editar</button>
          </div>
        </div>
        <button class="btn btn-outline btn-small" type="button" onclick="linkTechSheet(${s.id})">${s.id === currentLinkedSheetId ? 'Quitar' : 'Vincular'}</button>
      </div>
    `).join('');
  }

  function techEditInline(index) {
    techShowCreate(techSheetsCache[index]);
  }

  async function linkTechSheet(sheetId) {
    const unlink = sheetId === currentLinkedSheetId;

    try {
      await ajax(urlFor(routes.linkTechSheet, techItemId), {
        method: 'POST',
        body: JSON.stringify({ tech_sheet_id: unlink ? null : sheetId })
      });

      const idx = items.findIndex(i => i.id === techItemId);
      if (idx >= 0) {
        if (unlink) {
          items[idx].tech_sheet_id = null;
          items[idx].tech_sheet_name = null;
        } else {
          const s = techSheetsCache.find(x => Number(x.id) === Number(sheetId));
          items[idx].tech_sheet_id = sheetId;
          items[idx].tech_sheet_name = s ? s.product_name : items[idx].tech_sheet_name;
        }
      }

      keepScrollAfterRender(techItemId, () => {
        renderItems();
        toggleItem(techItemId, true);
        switchTab(techItemId, 'tech');
      });
      loadTechSheets();
    } catch (e) {
      showInlineError(e.message);
    }
  }

  async function submitTechSheet(event) {
    event.preventDefault();

    const form = event.target;
    const fd = new FormData(form);
    const id = document.getElementById('techFormId').value;
    const url = id ? routes.updateTechSheet.replace('__ID__', id) : urlFor(routes.createTechSheet, techItemId);
    const submit = form.querySelector('button[type="submit"]');
    const old = submit.innerHTML;

    submit.disabled = true;
    submit.innerHTML = '<span class="loader"></span> Guardando...';

    try {
      const resp = await fetch(url, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        },
        body: fd
      });

      const text = await resp.text();
      let data = null;
      try { data = JSON.parse(text); } catch (_) {}

      if (!resp.ok || !data || data.ok === false) {
        throw new Error((data && data.message) || ('Error al guardar la ficha. ' + text.slice(0, 200)));
      }

      if (!id) {
        const idx = items.findIndex(i => i.id === techItemId);
        if (idx >= 0 && data.sheet) {
          items[idx].tech_sheet_id = data.sheet.id;
          items[idx].tech_sheet_name = data.sheet.product_name;
        }
      }

      if (id && data.sheet) {
        const idx = items.findIndex(i => Number(i.tech_sheet_id) === Number(data.sheet.id));
        if (idx >= 0) items[idx].tech_sheet_name = data.sheet.product_name;
      }

      techShowList();
      document.getElementById('techQueryInput').value = (data.sheet && data.sheet.product_name) || '';
      keepScrollAfterRender(techItemId, () => {
        renderItems();
        toggleItem(techItemId, true);
        switchTab(techItemId, 'tech');
      });
      loadTechSheets();
    } catch (e) {
      showInlineError(e.message);
    } finally {
      submit.disabled = false;
      submit.innerHTML = old;
    }
  }


  // --- Preguntas para junta de aclaraciones ---
  function openClarificationModal(id) {
    clarificationItemId = id;
    clarificationLastCandidate = null;
    window.currentClarificationQuestion = null;

    const item = items.find(i => Number(i.id) === Number(id));

    document.getElementById('clarificationSubtitle').textContent = item?.descripcion_original || 'Producto';
    document.getElementById('clarificationIdeaInput').value = '';
    document.getElementById('clarificationQuestionInput').value = '';
    document.getElementById('clarificationCandidateBox').style.display = 'none';
    document.getElementById('clarificationCandidateBox').innerHTML = '';
    document.getElementById('clarificationStatus').textContent = 'Escribe una idea y genera la redacción profesional.';
    document.getElementById('clarificationModal').classList.add('show');
  }

  function closeClarificationModal() {
    document.getElementById('clarificationModal').classList.remove('show');
  }

  function normalizeClarificationResponse(data) {
    const questionPayload = data?.question || data?.pregunta || data || {};

    const generatedText =
      (typeof questionPayload === 'string' ? questionPayload : '') ||
      questionPayload.pregunta_generada ||
      questionPayload.generated_question ||
      questionPayload.question ||
      questionPayload.text ||
      questionPayload.pregunta ||
      data?.pregunta_generada ||
      data?.generated_question ||
      '';

    const candidate =
      questionPayload.catalog_candidate ||
      data?.catalog_candidate ||
      (
        questionPayload.producto_sugerido ||
        questionPayload.sku_sugerido ||
        questionPayload.marca_sugerida
          ? {
              name: questionPayload.producto_sugerido || '',
              sku: questionPayload.sku_sugerido || '',
              brand: questionPayload.marca_sugerida || '',
              price: questionPayload.precio_sugerido || null
            }
          : null
      );

    return {
      raw: questionPayload,
      generatedText,
      candidate
    };
  }

  async function generateClarificationQuestion() {
    if (!clarificationItemId) return;

    const ideaInput = document.getElementById('clarificationIdeaInput');
    const questionInput = document.getElementById('clarificationQuestionInput');
    const status = document.getElementById('clarificationStatus');
    const btn = document.getElementById('btnGenerateClarification');

    const idea = ideaInput.value.trim();

    if (!idea) {
      status.textContent = 'Escribe primero la idea de la pregunta.';
      return;
    }

    const old = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span class="loader"></span> Generando...';
    status.textContent = 'La IA está estructurando la pregunta y revisando posibles alternativas.';

    try {
      const data = await ajax(urlFor(routes.clarificationSuggest, clarificationItemId), {
        method: 'POST',
        body: JSON.stringify({
          texto_usuario: idea,
          idea: idea,
          buscar_catalogo: true,
          buscar_internet: true,
          internet: true
        })
      });

      const normalized = normalizeClarificationResponse(data);

      questionInput.value = normalized.generatedText || 'No se pudo generar la pregunta.';
      clarificationLastCandidate = normalized.candidate || null;
      window.currentClarificationQuestion = normalized.raw || {};

      const box = document.getElementById('clarificationCandidateBox');

      if (clarificationLastCandidate?.name) {
        box.style.display = '';
        box.innerHTML = `
          <div class="result-title">Alternativa sugerida de catálogo</div>

          <div class="result-meta">
            <strong>${escapeHtml(clarificationLastCandidate.name)}</strong>
            ${clarificationLastCandidate.sku ? ' · SKU ' + escapeHtml(clarificationLastCandidate.sku) : ''}
            ${clarificationLastCandidate.brand ? ' · ' + escapeHtml(clarificationLastCandidate.brand) : ''}
            ${clarificationLastCandidate.stock !== undefined ? ' · Stock ' + escapeHtml(clarificationLastCandidate.stock) : ''}
            ${clarificationLastCandidate.price ? ' · Precio ' + money(clarificationLastCandidate.price) : ''}
          </div>
        `;
      } else {
        box.style.display = 'none';
        box.innerHTML = '';
      }

      status.textContent = 'Pregunta generada. Revísala y guárdala para incluirla en el PDF.';
    } catch (e) {
      status.textContent = e.message || 'No se pudo generar la pregunta.';
      showInlineError(e.message);
    } finally {
      btn.disabled = false;
      btn.innerHTML = old;
    }
  }

  async function saveClarificationQuestion() {
    if (!clarificationItemId) return;

    const questionInput = document.getElementById('clarificationQuestionInput');
    const ideaInput = document.getElementById('clarificationIdeaInput');
    const status = document.getElementById('clarificationStatus');
    const btn = document.getElementById('btnSaveClarification');

    const preguntaGenerada = questionInput.value.trim();
    const idea = ideaInput.value.trim();

    if (!preguntaGenerada) {
      status.textContent = 'Primero genera o escribe una pregunta.';
      return;
    }

    const current = window.currentClarificationQuestion || {};
    const candidate = clarificationLastCandidate || current.catalog_candidate || {};

    const old = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span class="loader"></span> Guardando...';

    try {
      const data = await ajax(urlFor(routes.clarificationSave, clarificationItemId), {
        method: 'POST',
        body: JSON.stringify({
          texto_usuario: idea,
          original_idea: idea,

          pregunta_generada: preguntaGenerada,
          question: preguntaGenerada,

          producto_sugerido: current.producto_sugerido || candidate.name || null,
          sku_sugerido: current.sku_sugerido || candidate.sku || null,
          marca_sugerida: current.marca_sugerida || candidate.brand || null,
          precio_sugerido: current.precio_sugerido || candidate.price || null,
          justificacion: current.justificacion || null,

          catalog_candidate: candidate || null
        })
      });

      if (data.item) {
        updateItemInState(data.item);
      } else if (data.question) {
        const idx = items.findIndex(i => Number(i.id) === Number(clarificationItemId));

        if (idx >= 0) {
          if (!Array.isArray(items[idx].clarification_questions)) {
            items[idx].clarification_questions = [];
          }

          items[idx].clarification_questions.push(data.question);
        }
      }

      renderItems();
      closeClarificationModal();
      toggleItem(clarificationItemId, true);
      switchTab(clarificationItemId, 'questions');

      showToast('Pregunta guardada', 'Se agregó a la junta de aclaraciones.', 1, 1);
      setTimeout(hideToast, 3000);
    } catch (e) {
      status.textContent = e.message || 'No se pudo guardar la pregunta.';
      showInlineError(e.message);
    } finally {
      btn.disabled = false;
      btn.innerHTML = old;
    }
  }

  async function deleteClarificationQuestion(itemId, questionId) {
    if (!questionId) return;
    if (!confirm('¿Eliminar esta pregunta?')) return;

    try {
      const url = routes.clarificationDelete
        .replace('__ID__', itemId)
        .replace('__QUESTION__', encodeURIComponent(questionId));

      const data = await ajax(url, {
        method: 'DELETE',
        body: '{}'
      });

      if (data.item) {
        updateItemInState(data.item);
      } else {
        const idx = items.findIndex(i => Number(i.id) === Number(itemId));

        if (idx >= 0 && Array.isArray(items[idx].clarification_questions)) {
          items[idx].clarification_questions = items[idx].clarification_questions.filter(q => String(q.id) !== String(questionId));
        }
      }

      keepScrollAfterRender(itemId, () => {
        renderItems();
        toggleItem(itemId, true);
        switchTab(itemId, 'questions');
      });
    } catch (e) {
      showInlineError(e.message);
    }
  }

  function openClarificationsPdf() {
    window.open(routes.clarificationsPdf, '_blank');
  }

  // --- Exportación a PDF/Excel/Word ---
  function getQuoteFileName(extension) {
    const safeFolio = String(exportFolio || 'cotizacion').replace(/[^\w\-]+/g, '_').replace(/_+/g, '_');
    return `${safeFolio}_tabla_extraida_pdf.${extension}`;
  }

  function isPlainObject(value) { return value && typeof value === 'object' && !Array.isArray(value); }

  function normalizeCell(value) {
    if (value === null || value === undefined) return '';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
  }

  function normalizeRows(rows) {
    if (!Array.isArray(rows) || rows.length === 0) return null;
    if (isPlainObject(rows[0])) {
      const columns = [];
      rows.forEach(row => {
        if (!isPlainObject(row)) return;
        Object.keys(row).forEach(key => { if (!columns.includes(key)) columns.push(key); });
      });
      if (!columns.length) return null;
      return { columns, rows: rows.filter(isPlainObject).map(row => {
          const out = {}; columns.forEach(column => out[column] = normalizeCell(row[column])); return out;
      })};
    }
    if (Array.isArray(rows[0])) {
      const max = rows.reduce((acc, row) => Array.isArray(row) ? Math.max(acc, row.length) : acc, 0);
      if (!max) return null;
      const columns = Array.from({ length: max }, (_, index) => `Columna ${index + 1}`);
      return { columns, rows: rows.filter(Array.isArray).map(row => {
          const out = {}; columns.forEach((column, index) => out[column] = normalizeCell(row[index])); return out;
      })};
    }
    return null;
  }

  function collectExtractedTables(payload, source = 'PDF') {
    const tables = [];
    const tableKeys = ['tables', 'tablas', 'table', 'tabla', 'rows', 'filas', 'items', 'partidas', 'line_items', 'extracted_items', 'raw_items', 'original_items', 'data'];

    function walk(value, path = '') {
      if (!value || typeof value !== 'object') return;
      if (Array.isArray(value)) {
        const normalized = normalizeRows(value);
        if (normalized && normalized.rows.length) { tables.push({ title: path || 'Tabla extraída', source, columns: normalized.columns, rows: normalized.rows }); }
        value.forEach((child, index) => walk(child, `${path} ${index + 1}`.trim())); return;
      }
      tableKeys.forEach(key => {
        if (!Object.prototype.hasOwnProperty.call(value, key)) return;
        const candidate = value[key];
        if (candidate && typeof candidate === 'object') {
          if (isPlainObject(candidate) && Array.isArray(candidate.columns) && Array.isArray(candidate.rows)) {
            const rows = candidate.rows.map(row => {
              if (Array.isArray(row)) { const out = {}; candidate.columns.forEach((column, index) => out[column] = normalizeCell(row[index])); return out; }
              if (isPlainObject(row)) return row; return null;
            }).filter(Boolean);
            const normalized = normalizeRows(rows);
            if (normalized) tables.push({ title: key, source, columns: normalized.columns, rows: normalized.rows });
          } else {
            const normalized = normalizeRows(candidate);
            if (normalized) tables.push({ title: key, source, columns: normalized.columns, rows: normalized.rows });
          }
        }
      });
      Object.entries(value).forEach(([key, child]) => walk(child, key));
    }
    walk(payload, source);

    const seen = new Set();
    return tables.filter(table => {
      const signature = JSON.stringify(table.columns) + JSON.stringify(table.rows.slice(0, 5));
      if (seen.has(signature)) return false; seen.add(signature); return true;
    });
  }

  function getExportTables() {
    const tables = [];
    Object.entries(rawExportPayloads || {}).forEach(([source, payload]) => { collectExtractedTables(payload, source).forEach(table => tables.push(table)); });
    if (tables.length) return tables;
    return [{
      title: 'Partidas normalizadas', source: 'fallback_items',
      columns: ['descripcion_original', 'unidad_solicitada', 'cantidad_minima', 'cantidad_maxima', 'cantidad_cotizada', 'costo_unitario', 'precio_unitario', 'subtotal'],
      rows: items.map(item => ({
        descripcion_original: item.descripcion_original || '', unidad_solicitada: item.unidad_solicitada || '',
        cantidad_minima: item.cantidad_minima || '', cantidad_maxima: item.cantidad_maxima || '',
        cantidad_cotizada: item.cantidad_cotizada || '', costo_unitario: item.costo_unitario || '',
        precio_unitario: item.precio_unitario || '', subtotal: item.subtotal || ''
      }))
    }];
  }

  function buildExtractedTablesHtml() {
    const generatedAt = new Date().toLocaleString('es-MX');
    const tables = getExportTables();
    const tablesHtml = tables.map((table, tableIndex) => {
      const columns = Array.isArray(table.columns) ? table.columns : [];
      const rows = Array.isArray(table.rows) ? table.rows : [];
      const thead = columns.map(column => `<th>${escapeHtml(column)}</th>`).join('');
      const tbody = rows.map(row => `<tr>${columns.map(column => `<td>${escapeHtml(row?.[column] ?? '')}</td>`).join('')}</tr>`).join('');
      return `
        <div class="table-block">
          <h2>${escapeHtml(table.title || ('Tabla extraída ' + (tableIndex + 1)))}</h2>
          <div class="table-meta">Fuente: ${escapeHtml(table.source || 'PDF')} · Filas: ${rows.length} · Columnas: ${columns.length}</div>
          <table><thead><tr>${thead}</tr></thead><tbody>${tbody || `<tr><td colspan="${Math.max(columns.length, 1)}">Sin filas extraídas.</td></tr>`}</tbody></table>
        </div>`;
    }).join('');

    return `
      <!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>${escapeHtml(exportTitle)}</title>
        <style>
          body { font-family: Arial, sans-serif; color: #333333; background: #ffffff; margin: 24px; }
          h1 { color: #111111; font-size: 22px; margin: 0 0 6px; } h2 { color: #111111; font-size: 16px; margin: 22px 0 6px; }
          .meta, .table-meta { color: #666666; font-size: 12px; margin-bottom: 12px; }
          .table-block { margin-top: 18px; page-break-inside: avoid; }
          table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 18px; }
          th { background: #f9fafb; color: #111111; font-weight: 700; border: 1px solid #ebebeb; padding: 8px; text-align: left; vertical-align: top; }
          td { border: 1px solid #ebebeb; padding: 7px; vertical-align: top; } tr:nth-child(even) td { background: #fcfcfc; }
        </style>
      </head><body>
        <h1>${escapeHtml(exportTitle)}</h1>
        <div class="meta">Folio: ${escapeHtml(exportFolio)} · Generado: ${escapeHtml(generatedAt)} · Exportación basada en tabla extraída del PDF</div>
        ${tablesHtml || '<p>No se encontraron tablas para exportar.</p>'}
      </body></html>`;
  }

  function downloadBlob(content, fileName, mimeType) {
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');

    a.href = url;
    a.download = fileName;
    a.style.display = 'none';

    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    setTimeout(() => URL.revokeObjectURL(url), 1000);
  }

  function exportExtractedTablesToExcel() {
    const html = buildExtractedTablesHtml();
    downloadBlob(html, getQuoteFileName('xls'), 'application/vnd.ms-excel;charset=utf-8');
  }

  function exportExtractedTablesToWord() {
    const title = exportTitle; const folio = exportFolio; const generatedAt = new Date().toLocaleString('es-MX');
    const tables = getExportTables();
    const tablesHtml = tables.map((table, tableIndex) => {
      const columns = Array.isArray(table.columns) ? table.columns : [];
      const rows = Array.isArray(table.rows) ? table.rows : [];
      const thead = columns.map(column => `<th>${escapeHtml(column)}</th>`).join('');
      const tbody = rows.map(row => `<tr>${columns.map(column => `<td>${escapeHtml(row?.[column] ?? '')}</td>`).join('')}</tr>`).join('');
      return `
        <div class="table-block">
          <h2>${escapeHtml(table.title || ('Tabla extraída ' + (tableIndex + 1)))}</h2>
          <div class="table-meta">Fuente: ${escapeHtml(table.source || 'PDF')} · Filas: ${rows.length} · Columnas: ${columns.length}</div>
          <table><thead><tr>${thead}</tr></thead><tbody>${tbody || `<tr><td colspan="${Math.max(columns.length, 1)}">Sin filas extraídas.</td></tr>`}</tbody></table>
        </div>`;
    }).join('');

    const wordContent = `
      <!DOCTYPE html><html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
      <head><meta charset="UTF-8"><title>${escapeHtml(title)}</title>
        <style>
          @page WordSection1 { size: 11in 8.5in; mso-page-orientation: landscape; margin: 0.35in; }
          div.WordSection1 { page: WordSection1; }
          body { font-family: Arial, sans-serif; color: #333; background: #fff; margin: 0; }
          h1 { color: #111; font-size: 18pt; margin: 0 0 4pt; font-weight: 700; } h2 { color: #111; font-size: 11pt; margin: 14pt 0 4pt; font-weight: 700; }
          .meta, .table-meta { color: #666; font-size: 8pt; margin-bottom: 8pt; }
          .table-block { margin-top: 12pt; page-break-inside: avoid; }
          table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 7pt; margin-bottom: 12pt; }
          th { background: #f3f4f6; color: #111; font-weight: 700; border: 1px solid #d9d9d9; padding: 4pt; text-align: left; vertical-align: top; word-wrap: break-word; }
          td { border: 1px solid #e5e5e5; padding: 3pt 4pt; vertical-align: top; word-wrap: break-word; } tr:nth-child(even) td { background: #fafafa; }
        </style>
      </head><body>
        <div class="WordSection1">
          <h1>${escapeHtml(title)}</h1>
          <div class="meta">Folio: ${escapeHtml(folio)} · Generado: ${escapeHtml(generatedAt)} · Exportación basada en tabla extraída del PDF</div>
          ${tablesHtml || '<p>No se encontraron tablas para exportar.</p>'}
        </div>
      </body></html>`;
    downloadBlob(wordContent, getQuoteFileName('doc'), 'application/msword;charset=utf-8');
  }


  function getItemBrand(item) {
    const humanAccepted = item?.ui_status === 'accepted_item';
    if (!humanAccepted) return 'SIN MARCA';

    const selectedCatalog = getSelectedCatalogProduct(item);
    const brand =
      item.manual_external_supplier ||
      selectedCatalog?.product?.brand ||
      item.producto_seleccionado?.brand ||
      'SIN MARCA';

    return String(brand || 'SIN MARCA').trim().toUpperCase() || 'SIN MARCA';
  }

  function getItemDisplayNumber(item, fallbackIndex) {
    return item.sort || item.number || item.numero || fallbackIndex + 1;
  }

  function getBrandGroupedItems() {
    const sourceItems = [...items].sort((a, b) => Number(a.sort || 0) - Number(b.sort || 0));
    const groups = new Map();

    sourceItems.forEach((item, index) => {
      const brand = getItemBrand(item);
      if (!groups.has(brand)) {
        groups.set(brand, {
          brand,
          items: [],
          totalQuantity: 0,
          totalCost: 0,
          totalSale: 0,
          totalSubtotal: 0
        });
      }

      const qty = Number(item.cantidad_cotizada || item.cantidad_maxima || item.cantidad_minima || 1);
      const cost = Number(item.costo_unitario || 0);
      const price = Number(item.precio_unitario || 0);
      const subtotal = Number(item.subtotal || (price * qty) || 0);
      const selectedCatalog = getSelectedCatalogProduct(item);
      const productName = selectedCatalog?.product?.name || item.catalog_product_name_manual || item.manual_catalog_product_name || '';

      const group = groups.get(brand);
      group.items.push({
        number: getItemDisplayNumber(item, index),
        requested: item.descripcion_original || '',
        productName,
        unit: item.unidad_solicitada || 'pz',
        qty,
        cost,
        price,
        subtotal,
        status: statusLabel(item).text
      });
      group.totalQuantity += qty;
      group.totalCost += cost * qty;
      group.totalSale += price * qty;
      group.totalSubtotal += subtotal;
    });

    return [...groups.values()].sort((a, b) => a.brand.localeCompare(b.brand, 'es'));
  }

  function buildBrandsPdfHtml() {
    const generatedAt = new Date().toLocaleString('es-MX');
    const groups = getBrandGroupedItems();
    const grandTotal = groups.reduce((acc, group) => acc + Number(group.totalSubtotal || 0), 0);
    const totalItems = groups.reduce((acc, group) => acc + group.items.length, 0);

    const groupsHtml = groups.map(group => {
      const rows = group.items.map(row => `
        <tr>
          <td class="center">${escapeHtml(row.number)}</td>
          <td>${escapeHtml(row.requested)}</td>
          <td>${escapeHtml(row.productName || '—')}</td>
          <td class="center">${escapeHtml(row.unit)}</td>
          <td class="right">${Number(row.qty || 0).toLocaleString('es-MX')}</td>
          <td class="right">${moneyOrBlank(row.cost)}</td>
          <td class="right">${moneyOrBlank(row.price)}</td>
          <td class="right"><strong>${moneyOrBlank(row.subtotal)}</strong></td>
          <td class="center">${escapeHtml(row.status)}</td>
        </tr>
      `).join('');

      return `
        <section class="brand-section">
          <div class="brand-head">
            <div>
              <h2>${escapeHtml(group.brand)}</h2>
              <div class="brand-meta">${group.items.length} partida${group.items.length === 1 ? '' : 's'} solicitada${group.items.length === 1 ? '' : 's'}</div>
            </div>
            <div class="brand-total">Total marca: <strong>${money(group.totalSubtotal)}</strong></div>
          </div>

          <table>
            <thead>
              <tr>
                <th class="center">#</th>
                <th>Producto solicitado</th>
                <th>Producto / referencia</th>
                <th class="center">Unidad</th>
                <th class="right">Cantidad</th>
                <th class="right">Costo</th>
                <th class="right">Precio</th>
                <th class="right">Subtotal</th>
                <th class="center">Estado</th>
              </tr>
            </thead>
            <tbody>${rows}</tbody>
          </table>
        </section>
      `;
    }).join('');

    return `
      <!doctype html>
      <html lang="es">
      <head>
        <meta charset="UTF-8">
        <title>${escapeHtml(exportFolio)} - partidas por marca</title>
        <style>
          @page { size: letter landscape; margin: 10mm; }
          * { box-sizing: border-box; }
          body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 0; font-size: 10px; }
          .header { display: flex; justify-content: space-between; gap: 18px; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 14px; }
          h1 { font-size: 18px; margin: 0 0 5px; }
          .meta { color: #555; line-height: 1.45; }
          .summary { text-align: right; line-height: 1.55; white-space: nowrap; }
          .summary strong { font-size: 13px; }
          .brand-section { page-break-inside: avoid; margin-bottom: 18px; }
          .brand-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; background: #f3f4f6; border: 1px solid #d9d9d9; padding: 8px 10px; margin-bottom: 0; }
          h2 { font-size: 14px; margin: 0 0 3px; text-transform: uppercase; }
          .brand-meta { color: #666; font-size: 9px; }
          .brand-total { font-size: 11px; white-space: nowrap; }
          table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 8px; }
          th, td { border: 1px solid #d9d9d9; padding: 5px 6px; vertical-align: top; word-wrap: break-word; }
          th { background: #fafafa; color: #111; font-weight: 700; font-size: 9px; }
          td { font-size: 9px; }
          .center { text-align: center; }
          .right { text-align: right; }
          tr:nth-child(even) td { background: #fcfcfc; }
          .footer { margin-top: 14px; border-top: 1px solid #d9d9d9; padding-top: 8px; color: #555; font-size: 9px; }
          @media print { .no-print { display: none !important; } body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
        </style>
      </head>
      <body>
        <div class="header">
          <div>
            <h1>Partidas agrupadas por marca</h1>
            <div class="meta">
              <strong>${escapeHtml(exportTitle)}</strong><br>
              Folio: ${escapeHtml(exportFolio)}<br>
              Generado: ${escapeHtml(generatedAt)}
            </div>
          </div>
          <div class="summary">
            Marcas: <strong>${groups.length}</strong><br>
            Partidas: <strong>${totalItems}</strong><br>
            Total general: <strong>${money(grandTotal)}</strong>
          </div>
        </div>

        ${groupsHtml || '<p>No hay partidas para agrupar.</p>'}

        <div class="footer">
          Este reporte agrupa las partidas actuales de la propuesta por marca detectada: producto seleccionado, marca manual/proveedor externo o coincidencia de catálogo. Las partidas sin marca quedan en SIN MARCA.
        </div>
      </body>
      </html>
    `;
  }

  function exportBrandsGroupedPdf() {
    const html = buildBrandsPdfHtml();
    const printWindow = window.open('', '_blank');

    if (!printWindow) {
      downloadBlob(html, getQuoteFileName('partidas_por_marca.html'), 'text/html;charset=utf-8');
      showToast('Archivo generado', 'Se descargó el reporte por marca en HTML. Ábrelo e imprime como PDF.', 1, 1);
      setTimeout(hideToast, 4500);
      return;
    }

    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
    printWindow.focus();

    setTimeout(() => {
      printWindow.print();
    }, 450);
  }

  // --- Drag and Drop Logic ---
  function bindDragEvents() {
    const list = document.getElementById('itemsList');

    document.querySelectorAll('.jureto-quote-page .item-card').forEach(card => {
      card.setAttribute('draggable', 'false');

      card.addEventListener('dragover', (e) => {
        if (currentFilter !== 'all') return;

        const dragging = document.querySelector('.jureto-quote-page .item-card.dragging');
        if (!dragging) return;

        e.preventDefault();

        const after = getDragAfterElement(list, e.clientY);
        if (after == null) list.appendChild(dragging);
        else list.insertBefore(dragging, after);
      });
    });

    document.querySelectorAll('.jureto-quote-page .drag-handle').forEach(handle => {
      handle.addEventListener('mousedown', (event) => event.stopPropagation());
      handle.addEventListener('click', (event) => event.stopPropagation());

      handle.addEventListener('dragstart', (event) => {
        if (currentFilter !== 'all') {
          event.preventDefault();
          return;
        }

        const card = handle.closest('.item-card');
        if (!card) {
          event.preventDefault();
          return;
        }

        card.classList.add('dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', card.dataset.id || '');
      });

      handle.addEventListener('dragend', () => {
        const card = handle.closest('.item-card');
        const anchorId = card ? Number(card.dataset.id) : null;
        if (card) card.classList.remove('dragging');
        saveOrder(anchorId);
      });
    });
  }

  function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.item-card:not(.dragging)')];
    return draggableElements.reduce((closest, child) => {
      const box = child.getBoundingClientRect(); const offset = y - box.top - box.height / 2;
      if (offset < 0 && offset > closest.offset) return { offset, element: child };
      return closest;
    }, { offset: Number.NEGATIVE_INFINITY }).element;
  }

  async function saveOrder(anchorId = null) {
    if (currentFilter !== 'all') return;
    const ids = [...document.querySelectorAll('#itemsList .item-card')].map(card => Number(card.dataset.id));
    if (!ids.length) return;
    try {
      const data = await ajax(routes.reorder, { method: 'POST', body: JSON.stringify({ items: ids }) });
      items = mergeTechSheetMeta(data.items) || items; summary = data.summary || summary;
      keepScrollAfterRender(anchorId || ids[0], () => renderItems());
    } catch (e) { showInlineError(e.message); }
  }

  // --- Bindings & Listeners ---
  document.querySelectorAll('.filter-summary').forEach(btn => btn.addEventListener('click', () => { currentFilter = btn.dataset.filter || 'all'; renderItems(); }));
  document.getElementById('manualTabCatalog').addEventListener('click', () => { manualTab = 'catalog'; manualLastQuery = ''; document.getElementById('manualTabCatalog').classList.add('active'); document.getElementById('manualTabInternet').classList.remove('active'); scheduleManualSearch(10); });
  document.getElementById('manualTabInternet').addEventListener('click', () => { manualTab = 'internet'; manualLastQuery = ''; document.getElementById('manualTabInternet').classList.add('active'); document.getElementById('manualTabCatalog').classList.remove('active'); scheduleManualSearch(10); });
  
  document.getElementById('manualQueryInput').addEventListener('input', () => { manualLastQuery = ''; scheduleManualSearch(420); });
  document.getElementById('manualQueryInput').addEventListener('keydown', (event) => { if (event.key === 'Enter') { event.preventDefault(); manualLastQuery = ''; scheduleManualSearch(10); } });
  document.getElementById('techQueryInput').addEventListener('input', () => { clearTimeout(window.__techTimer); window.__techTimer = setTimeout(loadTechSheets, 350); });

  document.getElementById('btnSuggestAll').addEventListener('click', suggestAll);
  document.getElementById('btnOpenAddItem').addEventListener('click', openAddItemModal);
  document.getElementById('btnToggleGlobalMargin').addEventListener('click', toggleGlobalMargin);
  document.getElementById('btnSaveGlobalMargin').addEventListener('click', () => saveGlobalMargin(false));
  document.getElementById('btnApplyGlobalMargin').addEventListener('click', () => saveGlobalMargin(true));
  document.getElementById('btnExportExcel')?.addEventListener('click', exportExtractedTablesToExcel);
  document.getElementById('btnExportWord')?.addEventListener('click', exportExtractedTablesToWord);
  document.getElementById('btnExportClarificationsPdf')?.addEventListener('click', openClarificationsPdf);
  document.getElementById('btnExportBrandsPdf')?.addEventListener('click', exportBrandsGroupedPdf);
  document.getElementById('btnGenerateClarification')?.addEventListener('click', generateClarificationQuestion);
  document.getElementById('btnSaveClarification')?.addEventListener('click', saveClarificationQuestion);

  document.addEventListener('DOMContentLoaded', () => { renderItems(); });
</script>
@endsection