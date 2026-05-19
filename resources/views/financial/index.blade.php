{{-- resources/views/financial/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Estados Financieros')

@push('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  :root {
    --bg:           #f9fafb;
    --card:         #ffffff;
    --ink:          #111111;
    --ink2:         #333333;
    --muted:        #888888;
    --line:         #ebebeb;
    --blue:         #007aff;
    --blue-soft:    #e6f0ff;
    --success:      #15803d;
    --success-soft: #e6ffe6;
    --danger:       #ff4a4a;
    --danger-soft:  #ffebeb;
    --warning:      #b45309;
    --warning-soft: #fef9c3;
    --r:            12px;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background: var(--bg);
    color: var(--ink2);
    font-family: 'Quicksand', system-ui, sans-serif;
    font-weight: 500;
    line-height: 1.6;
    min-height: 100vh;
  }

  /* ── Animaciones ── */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  @keyframes backdropIn {
    from { background: rgba(0,0,0,0); backdrop-filter: blur(0px); }
    to   { background: rgba(0,0,0,.6); backdrop-filter: blur(12px); }
  }
  @keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(28px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
  }
  @keyframes spin { to { transform: rotate(360deg); } }
  @keyframes toastIn {
    from { opacity: 0; transform: translateX(120%); }
    to   { opacity: 1; transform: translateX(0); }
  }
  @keyframes toastOut {
    from { opacity: 1; transform: translateX(0); }
    to   { opacity: 0; transform: translateX(120%); }
  }
  @keyframes badgePulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: .6; }
  }

  .au { animation: fadeUp .5s cubic-bezier(.22,1,.36,1) both; }
  .d1 { animation-delay: .06s; }
  .d2 { animation-delay: .13s; }

  /* ── Wrap ── */
  .fin-wrap { max-width: 1200px; margin: 0 auto; padding: 48px 24px 80px; }

  /* ── Header ── */
  .fin-header {
    display: flex; align-items: flex-end; justify-content: space-between;
    gap: 24px; margin-bottom: 32px; padding-bottom: 28px;
    border-bottom: 1px solid var(--line);
  }
  .fin-eyebrow { font-size: .72rem; font-weight: 700; letter-spacing: .18em; text-transform: uppercase; color: var(--blue); margin-bottom: 10px; }
  .fin-title   { font-size: clamp(1.9rem, 3.5vw, 2.8rem); font-weight: 700; letter-spacing: -0.03em; color: var(--ink); line-height: 1.1; }
  .fin-subtitle { margin-top: 8px; color: var(--muted); font-size: .92rem; max-width: 440px; line-height: 1.6; }
  .fin-counter  { margin-top: 10px; font-size: .82rem; color: var(--muted); font-weight: 600; }
  .fin-counter strong { color: var(--blue); }

  /* ── Botones ── */
  .btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 22px; border-radius: 999px;
    font-family: 'Quicksand', sans-serif; font-weight: 700; font-size: .88rem;
    border: none; cursor: pointer; text-decoration: none;
    transition: all .18s ease; white-space: nowrap;
  }
  .btn:active { transform: scale(0.98); }
  .btn svg    { width: 16px; height: 16px; flex-shrink: 0; }

  .btn-primary { background: var(--blue); color: #fff; box-shadow: 0 4px 14px rgba(0,122,255,.2); }
  .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(0,122,255,.28); }

  .btn-ghost  { background: transparent; color: #555; border: 1px solid var(--line); }
  .btn-ghost:hover { background: #f4f4f4; }

  .btn-outline { background: #fff; color: var(--blue); border: 1px solid var(--blue); }
  .btn-outline:hover { background: var(--blue-soft); transform: translateY(-1px); }

  .btn-danger-solid { background: var(--danger); color: #fff; box-shadow: 0 4px 14px rgba(255,74,74,.2); }
  .btn-danger-solid:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(255,74,74,.3); }

  /* ── Toolbar (búsqueda + sort + toggle) ── */
  .toolbar {
    display: flex; gap: 10px; flex-wrap: wrap; align-items: center;
    margin-bottom: 24px;
  }
  .toolbar-search {
    flex: 1 1 220px; position: relative; min-width: 180px;
  }
  .toolbar-search svg {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    width: 15px; height: 15px; stroke: var(--muted); fill: none;
    stroke-width: 2; stroke-linecap: round;
  }
  .search-input {
    width: 100%; height: 40px; background: var(--card);
    border: 1px solid var(--line); border-radius: 999px;
    padding: 0 14px 0 36px;
    font-family: 'Quicksand', sans-serif; font-size: .88rem; font-weight: 500;
    color: var(--ink2); outline: none;
    transition: border-color .2s, box-shadow .2s;
  }
  .search-input::placeholder { color: #bbb; }
  .search-input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft); }

  .sort-select {
    height: 40px; background: var(--card); border: 1px solid var(--line);
    border-radius: 999px; padding: 0 32px 0 14px;
    font-family: 'Quicksand', sans-serif; font-size: .88rem; font-weight: 600;
    color: var(--ink2); outline: none; appearance: none; cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23888' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 12px center;
    transition: border-color .2s;
  }
  .sort-select:focus { border-color: var(--blue); }

  /* Toggle grid/list */
  .view-toggle { display: flex; gap: 4px; background: var(--card); border: 1px solid var(--line); border-radius: 999px; padding: 4px; }
  .toggle-btn {
    width: 32px; height: 32px; border-radius: 999px; border: none;
    background: transparent; cursor: pointer; display: flex; align-items: center; justify-content: center;
    color: var(--muted); transition: all .18s;
  }
  .toggle-btn svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
  .toggle-btn.active { background: var(--blue); color: #fff; }
  .toggle-btn:hover:not(.active) { background: var(--blue-soft); color: var(--blue); }

  /* ── Flash ── */
  .flash {
    padding: 14px 18px; border-radius: var(--r); margin-bottom: 24px;
    font-size: .9rem; font-weight: 600; display: flex; align-items: center; gap: 10px;
    animation: fadeUp .4s ease both;
  }
  .flash svg { width: 16px; height: 16px; flex-shrink: 0; }
  .flash-success { background: var(--success-soft); border: 1px solid #bbf7d0; color: var(--success); }
  .flash-error   { background: var(--danger-soft);  border: 1px solid #fecaca; color: var(--danger); }

  /* ── Filter bar (server-side) ── */
  .filter-bar { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; align-items: center; }
  .filter-select, .filter-input {
    background: var(--card); border: 1px solid var(--line); color: var(--ink2);
    border-radius: 8px; font-family: 'Quicksand', sans-serif; font-size: .88rem;
    font-weight: 600; outline: none; height: 42px; padding: 0 14px;
    transition: border-color .2s, box-shadow .2s;
  }
  .filter-select {
    appearance: none; cursor: pointer; padding-right: 36px;
    background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23888' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 12px center;
  }
  .filter-input { min-width: 190px; }
  .filter-input::placeholder { color: #bbb; }
  .filter-select:focus, .filter-input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft); }

  /* ── Period groups ── */
  .period-group { margin-bottom: 44px; }
  .period-label {
    font-size: .72rem; font-weight: 700; letter-spacing: .15em; text-transform: uppercase;
    color: var(--muted); margin-bottom: 16px; display: flex; align-items: center; gap: 12px;
  }
  .period-label::after { content: ''; flex: 1; height: 1px; background: var(--line); }

  /* ── GRID VIEW ── */
  .doc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 16px;
  }

  .doc-card {
    background: var(--card); border: 1px solid var(--line); border-radius: 16px;
    padding: 0; box-shadow: 0 4px 12px rgba(0,0,0,.02);
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s;
    display: flex; flex-direction: column; cursor: pointer; position: relative; overflow: visible;
    text-decoration: none; color: inherit;
  }
  .doc-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,.08); border-color: #d0d0d0; }
  .doc-card:active { transform: scale(0.99); }

  /* Hover preview tooltip */
  .doc-card .pdf-hover-preview {
    position: absolute; bottom: calc(100% + 12px); left: 50%;
    transform: translateX(-50%) scale(0.92);
    width: 220px; height: 160px;
    background: #fff; border: 1px solid var(--line);
    border-radius: 12px; overflow: hidden;
    box-shadow: 0 16px 48px rgba(0,0,0,.18);
    opacity: 0; pointer-events: none;
    transition: opacity .2s ease, transform .2s ease;
    z-index: 50;
  }
  .doc-card:hover .pdf-hover-preview {
    opacity: 1; transform: translateX(-50%) scale(1);
  }
  .pdf-hover-preview iframe {
    width: 100%; height: 100%; border: none;
    pointer-events: none;
    transform: scale(1); transform-origin: top left;
  }
  .pdf-hover-preview::after {
    content: '';
    position: absolute; bottom: -7px; left: 50%; transform: translateX(-50%);
    width: 14px; height: 14px; background: #fff;
    border-right: 1px solid var(--line); border-bottom: 1px solid var(--line);
    transform: translateX(-50%) rotate(45deg);
  }

  .doc-card-body { padding: 22px 22px 0; flex: 1; display: flex; flex-direction: column; gap: 14px; }

  /* Badge nuevo */
  .badge-new {
    position: absolute; top: -8px; right: 14px;
    background: var(--blue); color: #fff;
    font-size: .62rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
    padding: 3px 9px; border-radius: 999px;
    animation: badgePulse 2s ease infinite;
    box-shadow: 0 2px 8px rgba(0,122,255,.35);
  }

  .doc-icon-wrap { display: flex; align-items: center; gap: 12px; }
  .doc-pdf-icon {
    width: 40px; height: 48px; border-radius: 6px;
    background: var(--danger-soft); border: 1px solid #fecaca;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    flex-shrink: 0; position: relative; gap: 3px;
  }
  .doc-pdf-icon::before {
    content: ''; position: absolute; top: 0; right: 0;
    width: 10px; height: 10px; background: #fff;
    clip-path: polygon(100% 0, 0 0, 100% 100%);
    border-left: 1px solid #fecaca; border-bottom: 1px solid #fecaca;
  }
  .doc-pdf-icon span { font-size: .55rem; font-weight: 700; letter-spacing: .04em; color: var(--danger); text-transform: uppercase; margin-top: 2px; }
  .doc-pdf-icon svg  { width: 16px; height: 16px; stroke: var(--danger); fill: none; stroke-width: 1.6; stroke-linecap: round; stroke-linejoin: round; }

  .doc-meta { flex: 1; min-width: 0; }
  .doc-type-pill {
    display: inline-block; font-size: .64rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase;
    padding: 3px 9px; border-radius: 999px; background: var(--blue-soft); color: var(--blue); margin-bottom: 4px;
  }
  .doc-title { font-size: .98rem; font-weight: 700; color: var(--ink); line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .doc-notes { font-size: .82rem; color: var(--muted); font-weight: 500; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5; }

  .doc-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 22px; margin-top: 14px; border-top: 1px solid var(--line);
  }
  .doc-info     { display: flex; flex-direction: column; gap: 2px; }
  .doc-uploader { font-size: .78rem; color: var(--ink2); font-weight: 600; }
  .doc-date     { font-size: .73rem; color: var(--muted); }

  .doc-actions  { display: flex; gap: 6px; position: relative; z-index: 2; }
  .btn-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    border: 1px solid var(--line); background: #fff;
    cursor: pointer; transition: all .18s; text-decoration: none; color: var(--muted); flex-shrink: 0;
  }
  .btn-icon svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 1.7; stroke-linecap: round; stroke-linejoin: round; }
  .btn-icon:hover         { border-color: var(--blue);   color: var(--blue);   background: var(--blue-soft); transform: translateY(-1px); }
  .btn-icon.danger:hover  { border-color: var(--danger); color: var(--danger); background: var(--danger-soft); transform: translateY(-1px); }
  .btn-icon:active { transform: scale(0.95); }

  /* ── LIST VIEW ── */
  .doc-list { display: none; flex-direction: column; gap: 8px; }
  .doc-list-item {
    background: var(--card); border: 1px solid var(--line); border-radius: 12px;
    padding: 14px 18px; display: flex; align-items: center; gap: 14px;
    cursor: pointer; transition: all .18s; position: relative;
  }
  .doc-list-item:hover { border-color: #d0d0d0; box-shadow: 0 4px 16px rgba(0,0,0,.06); transform: translateX(2px); }
  .doc-list-item:active { transform: scale(0.995); }

  .list-pdf-icon {
    width: 32px; height: 38px; border-radius: 5px; flex-shrink: 0;
    background: var(--danger-soft); border: 1px solid #fecaca;
    display: flex; align-items: center; justify-content: center; position: relative;
  }
  .list-pdf-icon::before {
    content: ''; position: absolute; top: 0; right: 0;
    width: 8px; height: 8px; background: #fff;
    clip-path: polygon(100% 0, 0 0, 100% 100%);
    border-left: 1px solid #fecaca; border-bottom: 1px solid #fecaca;
  }
  .list-pdf-icon svg { width: 13px; height: 13px; stroke: var(--danger); fill: none; stroke-width: 1.8; stroke-linecap: round; }

  .list-info    { flex: 1; min-width: 0; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
  .list-title   { font-size: .92rem; font-weight: 700; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 280px; }
  .list-pill    { font-size: .62rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; padding: 2px 8px; border-radius: 999px; background: var(--blue-soft); color: var(--blue); white-space: nowrap; }
  .list-period  { font-size: .78rem; color: var(--muted); font-weight: 600; white-space: nowrap; }
  .list-uploader{ font-size: .75rem; color: var(--muted); white-space: nowrap; }
  .list-size    { font-size: .75rem; color: var(--muted); white-space: nowrap; margin-left: auto; }
  .list-date    { font-size: .75rem; color: var(--muted); white-space: nowrap; }
  .list-actions { display: flex; gap: 6px; flex-shrink: 0; position: relative; z-index: 2; }

  /* ── Empty state ── */
  .empty-state { text-align: center; padding: 80px 24px; border: 1.5px dashed var(--line); border-radius: 16px; background: var(--card); }
  .empty-icon-wrap { width: 52px; height: 52px; border-radius: 14px; background: var(--blue-soft); margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; }
  .empty-icon-wrap svg { width: 24px; height: 24px; stroke: var(--blue); fill: none; stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; }
  .empty-state h3 { font-size: 1.2rem; font-weight: 700; color: var(--ink); margin-bottom: 6px; }
  .empty-state p  { color: var(--muted); font-size: .9rem; }

  /* No results local */
  .no-results-local { display: none; text-align: center; padding: 48px 24px; color: var(--muted); font-weight: 600; }

  /* ══════════════════════════════════════ MODALS ══════════════════════════════════════ */
  .modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 200;
    align-items: center; justify-content: center; padding: 20px;
  }
  .modal-overlay.open { display: flex; }

  .modal-backdrop {
    position: fixed; inset: 0; z-index: 0;
    background: rgba(0,0,0,.6); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
    animation: backdropIn .3s ease both;
  }
  .modal-content-wrap { position: relative; z-index: 1; width: 100%; display: flex; justify-content: center; }

  /* Upload modal */
  .modal-upload {
    background: var(--card); border: 1px solid var(--line); border-radius: 20px;
    width: 100%; max-width: 540px; padding: 40px; position: relative;
    box-shadow: 0 24px 64px rgba(0,0,0,.18); max-height: 92vh; overflow-y: auto;
    animation: modalSlideUp .3s cubic-bezier(.22,1,.36,1) both;
  }
  .modal-eyebrow { font-size: .7rem; font-weight: 700; letter-spacing: .15em; text-transform: uppercase; color: var(--blue); margin-bottom: 6px; }
  .modal-title   { font-size: 1.7rem; font-weight: 700; color: var(--ink); margin-bottom: 28px; line-height: 1.15; letter-spacing: -0.02em; }
  .modal-close {
    position: absolute; top: 18px; right: 18px; width: 30px; height: 30px; border-radius: 8px;
    border: 1px solid var(--line); background: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center; color: var(--muted); transition: all .18s;
  }
  .modal-close:hover { background: #f4f4f4; color: var(--ink); border-color: #ccc; }
  .modal-close svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2.2; stroke-linecap: round; }

  /* Form */
  .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  .form-group { display: flex; flex-direction: column; gap: 7px; }
  .form-group.full { grid-column: 1 / -1; }
  .form-label { font-size: .74rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #555; }
  .form-input, .form-select, .form-textarea {
    background: #fff; border: 1px solid var(--line); color: var(--ink2); border-radius: 8px;
    padding: 11px 14px; font-family: 'Quicksand', sans-serif; font-size: .9rem; font-weight: 500;
    outline: none; transition: border-color .2s, box-shadow .2s; width: 100%;
  }
  .form-select { appearance: none; cursor: pointer; }
  .form-input::placeholder, .form-textarea::placeholder { color: #bbb; }
  .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft); }
  .form-textarea { min-height: 76px; resize: vertical; }

  .drop-zone {
    border: 1.5px dashed var(--line); border-radius: 12px; padding: 28px 20px;
    text-align: center; cursor: pointer; display: block; transition: border-color .2s, background .2s;
  }
  .drop-zone:hover, .drop-zone.drag-over { border-color: var(--blue); background: var(--blue-soft); }
  .drop-zone input[type="file"] { display: none; }
  .drop-zone-icon { width: 40px; height: 40px; border-radius: 10px; background: var(--blue-soft); margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; transition: background .2s; }
  .drop-zone:hover .drop-zone-icon, .drop-zone.drag-over .drop-zone-icon { background: #cce0ff; }
  .drop-zone-icon svg { width: 20px; height: 20px; stroke: var(--blue); fill: none; stroke-width: 1.6; stroke-linecap: round; stroke-linejoin: round; }
  .drop-zone-text  { color: var(--muted); font-size: .88rem; font-weight: 500; }
  .drop-zone-text strong { color: var(--blue); font-weight: 700; }
  .drop-zone-hint  { font-size: .75rem; color: #bbb; margin-top: 5px; }
  #file-chosen     { font-size: .8rem; color: var(--success); margin-top: 8px; display: none; font-weight: 700; }

  .btn-submit {
    width: 100%; padding: 13px; border-radius: 999px; background: var(--blue); color: #fff;
    font-family: 'Quicksand', sans-serif; font-weight: 700; font-size: .95rem; border: none;
    cursor: pointer; margin-top: 6px; transition: all .2s; box-shadow: 0 4px 14px rgba(0,122,255,.22);
  }
  .btn-submit:hover  { transform: translateY(-1px); box-shadow: 0 8px 22px rgba(0,122,255,.3); }
  .btn-submit:active { transform: scale(.98); }

  .form-errors { background: var(--danger-soft); border: 1px solid #fecaca; border-radius: 8px; padding: 12px 16px; margin-bottom: 18px; }
  .form-errors li { font-size: .85rem; color: var(--danger); list-style: none; font-weight: 600; }

  /* Preview modal */
  .modal-preview {
    background: var(--card); border: 1px solid var(--line); border-radius: 20px;
    width: 100%; max-width: 1000px; height: 88vh;
    box-shadow: 0 32px 80px rgba(0,0,0,.25);
    display: flex; flex-direction: column; overflow: hidden;
    animation: modalSlideUp .3s cubic-bezier(.22,1,.36,1) both;
  }
  .preview-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 22px; border-bottom: 1px solid var(--line);
    flex-shrink: 0; gap: 16px; background: #fff;
  }
  .preview-header-info    { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
  .preview-header-title   { font-size: 1rem; font-weight: 700; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .preview-header-sub     { font-size: .75rem; color: var(--muted); }
  .preview-header-actions { display: flex; gap: 8px; flex-shrink: 0; align-items: center; }

  .preview-body    { flex: 1; position: relative; overflow: hidden; background: #e8e8e8; }
  .preview-loading {
    position: absolute; inset: 0; display: flex; flex-direction: column;
    align-items: center; justify-content: center; background: var(--card);
    gap: 14px; z-index: 1; transition: opacity .3s;
  }
  .preview-loading.hidden { opacity: 0; pointer-events: none; }
  .spinner { width: 32px; height: 32px; border-radius: 50%; border: 2.5px solid var(--line); border-top-color: var(--blue); animation: spin .65s linear infinite; }
  .preview-loading p { color: var(--muted); font-size: .85rem; font-weight: 600; }
  #preview-iframe  { width: 100%; height: 100%; border: none; }

  /* ── Confirm Delete Modal ── */
  .modal-confirm {
    background: var(--card); border: 1px solid var(--line); border-radius: 20px;
    width: 100%; max-width: 420px; padding: 36px 32px; position: relative;
    box-shadow: 0 24px 64px rgba(0,0,0,.18);
    animation: modalSlideUp .28s cubic-bezier(.22,1,.36,1) both;
    text-align: center;
  }
  .confirm-icon {
    width: 56px; height: 56px; border-radius: 16px; background: var(--danger-soft);
    border: 1px solid #fecaca; margin: 0 auto 20px;
    display: flex; align-items: center; justify-content: center;
  }
  .confirm-icon svg { width: 26px; height: 26px; stroke: var(--danger); fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
  .confirm-title   { font-size: 1.25rem; font-weight: 700; color: var(--ink); margin-bottom: 8px; }
  .confirm-desc    { font-size: .88rem; color: var(--muted); line-height: 1.6; margin-bottom: 6px; }
  .confirm-docname { font-size: .9rem; font-weight: 700; color: var(--danger); margin-bottom: 24px; background: var(--danger-soft); padding: 8px 14px; border-radius: 8px; display: inline-block; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .confirm-actions { display: flex; gap: 10px; justify-content: center; }

  /* ── Toast ── */
  .toast-container { position: fixed; bottom: 28px; right: 28px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; pointer-events: none; }
  .toast {
    display: flex; align-items: center; gap: 12px;
    background: var(--card); border: 1px solid var(--line);
    border-radius: 14px; padding: 14px 18px;
    box-shadow: 0 8px 32px rgba(0,0,0,.12);
    font-family: 'Quicksand', sans-serif; font-size: .88rem; font-weight: 600;
    min-width: 260px; max-width: 360px; pointer-events: all;
    animation: toastIn .35s cubic-bezier(.22,1,.36,1) both;
  }
  .toast.out { animation: toastOut .3s ease forwards; }
  .toast-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .toast-icon svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .toast-success .toast-icon { background: var(--success-soft); color: var(--success); }
  .toast-error   .toast-icon { background: var(--danger-soft);  color: var(--danger); }
  .toast-text    { flex: 1; color: var(--ink2); }
  .toast-close   { width: 20px; height: 20px; border: none; background: transparent; cursor: pointer; color: var(--muted); display: flex; align-items: center; justify-content: center; padding: 0; flex-shrink: 0; }
  .toast-close svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; }
  .toast-progress { position: absolute; bottom: 0; left: 0; height: 3px; background: var(--blue); border-radius: 0 0 14px 14px; animation: progressBar 4s linear forwards; }
  @keyframes progressBar { from { width: 100%; } to { width: 0%; } }
  .toast { position: relative; overflow: hidden; }

  /* ── Responsive ── */
  @media(max-width: 900px) { .doc-grid { grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); } .modal-preview { max-width: 100%; height: 92vh; border-radius: 14px; } }
  @media(max-width: 768px) {
    .fin-wrap { padding: 24px 14px 60px; }
    .fin-header { flex-direction: column; align-items: flex-start; gap: 16px; }
    .fin-title { font-size: 1.9rem; }
    .form-grid { grid-template-columns: 1fr; }
    .modal-upload { padding: 28px 18px; max-width: 100%; }
    .doc-grid { grid-template-columns: 1fr; }
    .toolbar { gap: 8px; }
    .toolbar-search { min-width: 100%; }
    .modal-overlay { padding: 12px; }
    .doc-card .pdf-hover-preview { display: none; }
    .toast-container { bottom: 16px; right: 16px; left: 16px; }
    .toast { min-width: unset; max-width: 100%; }
    .list-size, .list-date, .list-uploader { display: none; }
  }
  @media(max-width: 480px) {
    .fin-header .btn { font-size: .82rem; padding: 10px 16px; }
    .preview-header { padding: 12px 16px; }
    .confirm-actions { flex-direction: column; }
  }
</style>
@endpush

@section('content')
@php
  $totalDocs    = $statements->count();
  $totalPeriods = $grouped->count();
  $sevenDaysAgo = now()->subDays(7);
@endphp

<div class="fin-wrap">

  {{-- Header --}}
  <header class="fin-header au">
    <div>
      <p class="fin-eyebrow">Módulo Restringido</p>
      <h1 class="fin-title">Estados Financieros</h1>
      <p class="fin-subtitle">Repositorio privado de documentos financieros corporativos.</p>
      <p class="fin-counter">
        Mostrando <strong id="visibleCount">{{ $totalDocs }}</strong> documento{{ $totalDocs !== 1 ? 's' : '' }}
        · <strong>{{ $totalPeriods }}</strong> período{{ $totalPeriods !== 1 ? 's' : '' }}
      </p>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('upload-modal').classList.add('open')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      Subir documento
    </button>
  </header>

  {{-- Flash --}}
  @if(session('success'))
    <div class="flash flash-success au" id="serverFlash">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      {{ session('success') }}
    </div>
  @endif

  {{-- Server filters --}}
  <form method="GET" class="filter-bar au d1" id="serverFilters">
    <input type="text" name="period" class="filter-input" placeholder="Período (ej. Q1 2025)" value="{{ request('period') }}">
    <select name="type" class="filter-select">
      <option value="">Todos los tipos</option>
      <option value="balance_general"   @selected(request('type') === 'balance_general')>Balance General</option>
      <option value="estado_resultados" @selected(request('type') === 'estado_resultados')>Estado de Resultados</option>
      <option value="flujo_efectivo"    @selected(request('type') === 'flujo_efectivo')>Flujo de Efectivo</option>
      <option value="notas"             @selected(request('type') === 'notas')>Notas a los Estados</option>
      <option value="otro"              @selected(request('type') === 'otro')>Otro</option>
    </select>
    <button type="submit" class="btn btn-primary" style="border-radius:8px;padding:0 20px;height:42px;">Filtrar</button>
    @if(request()->hasAny(['period','type']))
      <a href="{{ route('financial.index') }}" class="btn btn-ghost" style="border-radius:8px;padding:0 18px;height:42px;">Limpiar</a>
    @endif
  </form>

  {{-- Toolbar: búsqueda local + sort + toggle vista --}}
  <div class="toolbar au d1">
    <div class="toolbar-search">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <input type="text" class="search-input" id="localSearch" placeholder="Buscar por título, tipo, período...">
    </div>

    <select class="sort-select" id="sortSelect">
      <option value="date-desc">Más reciente</option>
      <option value="date-asc">Más antiguo</option>
      <option value="name-asc">Nombre A–Z</option>
      <option value="name-desc">Nombre Z–A</option>
      <option value="size-desc">Mayor tamaño</option>
      <option value="size-asc">Menor tamaño</option>
      <option value="type-asc">Por tipo</option>
    </select>

    <div class="view-toggle">
      <button class="toggle-btn active" id="btnGrid" title="Vista cuadrícula" onclick="setView('grid')">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      </button>
      <button class="toggle-btn" id="btnList" title="Vista lista" onclick="setView('list')">
        <svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
      </button>
    </div>
  </div>

  {{-- Content --}}
  @if($grouped->isEmpty())
    <div class="empty-state au d2">
      <div class="empty-icon-wrap">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      </div>
      <h3>Sin documentos aún</h3>
      <p>Sube el primer estado financiero usando el botón superior.</p>
    </div>
  @else
    <div id="noResultsLocal" class="no-results-local">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#bbb" stroke-width="1.5" stroke-linecap="round" style="margin:0 auto 12px;display:block"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      No se encontraron documentos para "<span id="searchTerm"></span>"
    </div>

    {{-- Todos los documentos en un contenedor para búsqueda/sort --}}
    <div id="allDocsContainer">
      @foreach($grouped as $period => $docs)
        <div class="period-group au d2" data-period-group>
          <div class="period-label">{{ $period }}</div>

          {{-- Grid view --}}
          <div class="doc-grid" data-grid>
            @foreach($docs as $doc)
              @php $isNew = $doc->created_at->gt($sevenDaysAgo); @endphp
              <div
                class="doc-card"
                onclick="openPreview('{{ route('financial.preview', $doc) }}', '{{ addslashes($doc->title) }}', '{{ $doc->type_label }} · {{ $doc->period }}')"
                data-title="{{ strtolower($doc->title) }}"
                data-type="{{ strtolower($doc->type_label) }}"
                data-period="{{ strtolower($doc->period) }}"
                data-date="{{ $doc->created_at->timestamp }}"
                data-size="{{ $doc->file_size }}"
                data-name="{{ strtolower($doc->title) }}"
              >
                @if($isNew)<span class="badge-new">Nuevo</span>@endif

                {{-- Hover preview --}}
                <div class="pdf-hover-preview" data-preview-url="{{ route('financial.preview', $doc) }}">
                  <iframe data-src="{{ route('financial.preview', $doc) }}" loading="lazy" title="preview"></iframe>
                </div>

                <div class="doc-card-body">
                  <div class="doc-icon-wrap">
                    <div class="doc-pdf-icon">
                      <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                      <span>PDF</span>
                    </div>
                    <div class="doc-meta">
                      <div class="doc-type-pill">{{ $doc->type_label }}</div>
                      <div class="doc-title" title="{{ $doc->title }}">{{ $doc->title }}</div>
                    </div>
                  </div>
                  @if($doc->notes)<p class="doc-notes">{{ $doc->notes }}</p>@endif
                </div>

                <div class="doc-footer">
                  <div class="doc-info">
                    <span class="doc-uploader">{{ $doc->uploader->name ?? '—' }}</span>
                    <span class="doc-date">{{ $doc->created_at->format('d M Y') }} · {{ $doc->file_size_human }}</span>
                  </div>
                  <div class="doc-actions" onclick="event.stopPropagation()">
                    <a href="{{ route('financial.download', $doc) }}" class="btn-icon" title="Descargar">
                      <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </a>
                    <button type="button" class="btn-icon danger" title="Eliminar"
                      onclick="event.stopPropagation(); openConfirmDelete('{{ $doc->id }}', '{{ addslashes($doc->title) }}', '{{ route('financial.destroy', $doc) }}')">
                      <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    </button>
                  </div>
                </div>
              </div>
            @endforeach
          </div>

          {{-- List view --}}
          <div class="doc-list" data-list>
            @foreach($docs as $doc)
              @php $isNew = $doc->created_at->gt($sevenDaysAgo); @endphp
              <div
                class="doc-list-item"
                onclick="openPreview('{{ route('financial.preview', $doc) }}', '{{ addslashes($doc->title) }}', '{{ $doc->type_label }} · {{ $doc->period }}')"
                data-title="{{ strtolower($doc->title) }}"
                data-type="{{ strtolower($doc->type_label) }}"
                data-period="{{ strtolower($doc->period) }}"
                data-date="{{ $doc->created_at->timestamp }}"
                data-size="{{ $doc->file_size }}"
                data-name="{{ strtolower($doc->title) }}"
              >
                <div class="list-pdf-icon">
                  <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div class="list-info">
                  <span class="list-title" title="{{ $doc->title }}">{{ $doc->title }}</span>
                  @if($isNew)<span class="list-pill" style="background:#e6f0ff;color:#007aff;">Nuevo</span>@endif
                  <span class="list-pill">{{ $doc->type_label }}</span>
                  <span class="list-period">{{ $doc->period }}</span>
                  <span class="list-uploader">{{ $doc->uploader->name ?? '—' }}</span>
                  <span class="list-size">{{ $doc->file_size_human }}</span>
                  <span class="list-date">{{ $doc->created_at->format('d M Y') }}</span>
                </div>
                <div class="list-actions" onclick="event.stopPropagation()">
                  <a href="{{ route('financial.download', $doc) }}" class="btn-icon" title="Descargar">
                    <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                  </a>
                  <button type="button" class="btn-icon danger" title="Eliminar"
                    onclick="event.stopPropagation(); openConfirmDelete('{{ $doc->id }}', '{{ addslashes($doc->title) }}', '{{ route('financial.destroy', $doc) }}')">
                    <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                  </button>
                </div>
              </div>
            @endforeach
          </div>

        </div>
      @endforeach
    </div>
  @endif

</div>

{{-- Toast container --}}
<div class="toast-container" id="toastContainer"></div>

{{-- ══ MODAL: UPLOAD ══ --}}
<div class="modal-overlay" id="upload-modal">
  <div class="modal-backdrop" onclick="document.getElementById('upload-modal').classList.remove('open')"></div>
  <div class="modal-content-wrap">
    <div class="modal-upload">
      <button class="modal-close" onclick="document.getElementById('upload-modal').classList.remove('open')">
        <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
      <p class="modal-eyebrow">Nuevo documento</p>
      <h2 class="modal-title">Subir Estado Financiero</h2>
      @if($errors->any())
        <ul class="form-errors">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
      @endif
      <form method="POST" action="{{ route('financial.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="form-grid">
          <div class="form-group full">
            <label class="form-label">Título del documento</label>
            <input type="text" name="title" class="form-input" placeholder="Ej. Balance General Consolidado" value="{{ old('title') }}" required>
          </div>
          <div class="form-group">
            <label class="form-label">Período</label>
            <input type="text" name="period" class="form-input" placeholder="Ej. Q2 2025" value="{{ old('period') }}" required>
          </div>
          <div class="form-group">
            <label class="form-label">Tipo de documento</label>
            <select name="type" class="form-select" required>
              <option value="" disabled @selected(!old('type'))>Seleccionar...</option>
              <option value="balance_general"   @selected(old('type') === 'balance_general')>Balance General</option>
              <option value="estado_resultados" @selected(old('type') === 'estado_resultados')>Estado de Resultados</option>
              <option value="flujo_efectivo"    @selected(old('type') === 'flujo_efectivo')>Flujo de Efectivo</option>
              <option value="notas"             @selected(old('type') === 'notas')>Notas a los Estados</option>
              <option value="otro"              @selected(old('type') === 'otro')>Otro</option>
            </select>
          </div>
          <div class="form-group full">
            <label class="form-label">Notas (opcional)</label>
            <textarea name="notes" class="form-textarea" placeholder="Observaciones o contexto del documento...">{{ old('notes') }}</textarea>
          </div>
          <div class="form-group full">
            <label class="form-label">Archivo PDF</label>
            <label class="drop-zone" id="drop-zone">
              <input type="file" name="file" id="file-input" accept=".pdf" required>
              <div class="drop-zone-icon">
                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M12 12v6"/><path d="M9 15l3-3 3 3"/></svg>
              </div>
              <p class="drop-zone-text"><strong>Arrastra aquí</strong> o haz clic para seleccionar</p>
              <p class="drop-zone-hint">Solo PDF · Máximo 20 MB</p>
              <p id="file-chosen"></p>
            </label>
          </div>
        </div>
        <button type="submit" class="btn-submit" style="margin-top:20px;">Subir documento</button>
      </form>
    </div>
  </div>
</div>

{{-- ══ MODAL: PREVIEW PDF ══ --}}
<div class="modal-overlay" id="preview-modal">
  <div class="modal-backdrop" onclick="closePreview()"></div>
  <div class="modal-content-wrap">
    <div class="modal-preview">
      <div class="preview-header">
        <div class="preview-header-info">
          <div class="preview-header-title" id="preview-title">—</div>
          <div class="preview-header-sub"   id="preview-sub">—</div>
        </div>
        <div class="preview-header-actions">
          <a href="#" id="preview-download-btn" class="btn btn-outline" style="border-radius:8px;padding:0 16px;height:34px;font-size:.82rem;">
            <svg viewBox="0 0 24 24" style="width:14px;height:14px;" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Descargar
          </a>
          <button class="modal-close" style="position:static;" onclick="closePreview()">
            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
      </div>
      <div class="preview-body">
        <div class="preview-loading" id="preview-loading">
          <div class="spinner"></div>
          <p>Cargando documento...</p>
        </div>
        <iframe id="preview-iframe" title="Vista previa PDF"></iframe>
      </div>
    </div>
  </div>
</div>

{{-- ══ MODAL: CONFIRM DELETE ══ --}}
<div class="modal-overlay" id="confirm-modal">
  <div class="modal-backdrop" onclick="closeConfirmDelete()"></div>
  <div class="modal-content-wrap">
    <div class="modal-confirm">
      <div class="confirm-icon">
        <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
      </div>
      <h3 class="confirm-title">¿Eliminar documento?</h3>
      <p class="confirm-desc">Esta acción es permanente y no se puede deshacer. Se eliminará:</p>
      <div class="confirm-docname" id="confirm-doc-name">—</div>
      <div class="confirm-actions">
        <button class="btn btn-ghost" onclick="closeConfirmDelete()" style="flex:1;">Cancelar</button>
        <form id="confirm-delete-form" method="POST" style="flex:1;">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-danger-solid" style="width:100%;">Sí, eliminar</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
// ══════════════════════════════════════════
// TOAST
// ══════════════════════════════════════════
function showToast(message, type = 'success') {
  const container = document.getElementById('toastContainer');
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;

  const icons = {
    success: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
    error:   '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'
  };

  toast.innerHTML = `
    <div class="toast-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${icons[type] || icons.success}</svg>
    </div>
    <span class="toast-text">${message}</span>
    <button class="toast-close" onclick="dismissToast(this.closest('.toast'))">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <div class="toast-progress"></div>
  `;
  container.appendChild(toast);
  setTimeout(() => dismissToast(toast), 4200);
}

function dismissToast(toast) {
  if (!toast || toast.classList.contains('out')) return;
  toast.classList.add('out');
  setTimeout(() => toast.remove(), 320);
}

// Mostrar toast si hay flash de servidor
@if(session('success'))
  document.addEventListener('DOMContentLoaded', () => {
    showToast('{{ session('success') }}', 'success');
    const f = document.getElementById('serverFlash');
    if (f) f.style.display = 'none';
  });
@endif

// ══════════════════════════════════════════
// VISTA GRID / LIST
// ══════════════════════════════════════════
let currentView = localStorage.getItem('fin_view') || 'grid';

function setView(v) {
  currentView = v;
  localStorage.setItem('fin_view', v);
  document.querySelectorAll('[data-grid]').forEach(el => el.style.display = v === 'grid' ? 'grid' : 'none');
  document.querySelectorAll('[data-list]').forEach(el => el.style.display = v === 'list' ? 'flex' : 'none');
  document.getElementById('btnGrid').classList.toggle('active', v === 'grid');
  document.getElementById('btnList').classList.toggle('active', v === 'list');
}

// ══════════════════════════════════════════
// BÚSQUEDA LOCAL + SORT
// ══════════════════════════════════════════
function getAllItems() {
  return Array.from(document.querySelectorAll('.doc-card, .doc-list-item'));
}

function getSearchKey(el) {
  return (el.dataset.title || '') + ' ' + (el.dataset.type || '') + ' ' + (el.dataset.period || '');
}

function applySearchAndSort() {
  const q    = (document.getElementById('localSearch')?.value || '').trim().toLowerCase();
  const sort = document.getElementById('sortSelect')?.value || 'date-desc';

  // Recopilar todos los items con su grupo
  const groups = Array.from(document.querySelectorAll('[data-period-group]'));
  let totalVisible = 0;

  groups.forEach(group => {
    const gridItems = Array.from(group.querySelectorAll('[data-grid] .doc-card'));
    const listItems = Array.from(group.querySelectorAll('[data-list] .doc-list-item'));

    // Filtrar
    const matchGrid = gridItems.filter(el => !q || getSearchKey(el).includes(q));
    const matchList = listItems.filter(el => !q || getSearchKey(el).includes(q));

    // Ocultar todos primero
    gridItems.forEach(el => el.style.display = 'none');
    listItems.forEach(el => el.style.display = 'none');

    // Sort
    const sortFn = getSortFn(sort);
    matchGrid.sort(sortFn).forEach(el => { el.style.display = ''; });
    matchList.sort(sortFn).forEach(el => { el.style.display = ''; });

    // Reordenar en el DOM
    const grid = group.querySelector('[data-grid]');
    const list = group.querySelector('[data-list]');
    matchGrid.sort(sortFn).forEach(el => grid?.appendChild(el));
    matchList.sort(sortFn).forEach(el => list?.appendChild(el));

    // Ocultar grupo si no hay resultados
    const hasResults = matchGrid.length > 0;
    group.style.display = hasResults ? '' : 'none';
    totalVisible += matchGrid.length;
  });

  // Actualizar contador
  const vc = document.getElementById('visibleCount');
  if (vc) vc.textContent = totalVisible;

  // No results
  const noRes = document.getElementById('noResultsLocal');
  const term  = document.getElementById('searchTerm');
  if (noRes) {
    noRes.style.display = totalVisible === 0 && q ? 'block' : 'none';
    if (term) term.textContent = q;
  }
}

function getSortFn(sort) {
  return (a, b) => {
    switch(sort) {
      case 'date-asc':  return (a.dataset.date || 0) - (b.dataset.date || 0);
      case 'date-desc': return (b.dataset.date || 0) - (a.dataset.date || 0);
      case 'name-asc':  return (a.dataset.name || '').localeCompare(b.dataset.name || '');
      case 'name-desc': return (b.dataset.name || '').localeCompare(a.dataset.name || '');
      case 'size-asc':  return (a.dataset.size || 0) - (b.dataset.size || 0);
      case 'size-desc': return (b.dataset.size || 0) - (a.dataset.size || 0);
      case 'type-asc':  return (a.dataset.type || '').localeCompare(b.dataset.type || '');
      default: return 0;
    }
  };
}

// ══════════════════════════════════════════
// HOVER PREVIEW (lazy load iframe)
// ══════════════════════════════════════════
function initHoverPreviews() {
  document.querySelectorAll('.pdf-hover-preview').forEach(preview => {
    const iframe = preview.querySelector('iframe');
    const card   = preview.closest('.doc-card');
    if (!card || !iframe) return;

    let loaded = false;
    card.addEventListener('mouseenter', () => {
      if (!loaded) {
        iframe.src = iframe.dataset.src || '';
        loaded = true;
      }
    });
  });
}

// ══════════════════════════════════════════
// PREVIEW MODAL
// ══════════════════════════════════════════
const previewModal   = document.getElementById('preview-modal');
const previewIframe  = document.getElementById('preview-iframe');
const previewLoading = document.getElementById('preview-loading');
const previewTitle   = document.getElementById('preview-title');
const previewSub     = document.getElementById('preview-sub');
const previewDlBtn   = document.getElementById('preview-download-btn');

function openPreview(previewUrl, title, sub) {
  previewTitle.textContent = title;
  previewSub.textContent   = sub;
  previewDlBtn.href = previewUrl.replace('/preview', '/descargar');
  previewLoading.classList.remove('hidden');
  previewIframe.src = '';
  previewModal.classList.add('open');
  previewIframe.onload = () => { if (previewIframe.src) previewLoading.classList.add('hidden'); };
  previewIframe.src = previewUrl;
}

function closePreview() {
  previewModal.classList.remove('open');
  previewIframe.src = '';
  previewLoading.classList.remove('hidden');
}

// ══════════════════════════════════════════
// CONFIRM DELETE MODAL
// ══════════════════════════════════════════
function openConfirmDelete(id, title, actionUrl) {
  document.getElementById('confirm-doc-name').textContent = title;
  document.getElementById('confirm-delete-form').action   = actionUrl;
  document.getElementById('confirm-modal').classList.add('open');
}

function closeConfirmDelete() {
  document.getElementById('confirm-modal').classList.remove('open');
}

// ══════════════════════════════════════════
// INIT
// ══════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {

  // Vista guardada
  setView(currentView);

  // Búsqueda local
  const ls = document.getElementById('localSearch');
  const ss = document.getElementById('sortSelect');
  let debTimer;
  if (ls) ls.addEventListener('input', () => { clearTimeout(debTimer); debTimer = setTimeout(applySearchAndSort, 150); });
  if (ss) ss.addEventListener('change', applySearchAndSort);

  // Hover previews
  initHoverPreviews();

  // Upload modal si hay errores
  @if($errors->any())
    document.getElementById('upload-modal').classList.add('open');
  @endif

  // Drop zone
  const fileInput  = document.getElementById('file-input');
  const fileChosen = document.getElementById('file-chosen');
  const dropZone   = document.getElementById('drop-zone');

  if (fileInput) fileInput.addEventListener('change', function() {
    if (this.files.length) { fileChosen.textContent = '✓ ' + this.files[0].name; fileChosen.style.display = 'block'; }
  });
  if (dropZone) {
    dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', ()  => dropZone.classList.remove('drag-over'));
    dropZone.addEventListener('drop', e => {
      e.preventDefault(); dropZone.classList.remove('drag-over');
      if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        fileChosen.textContent = '✓ ' + e.dataTransfer.files[0].name;
        fileChosen.style.display = 'block';
      }
    });
  }
});

// ESC global
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.getElementById('upload-modal')?.classList.remove('open');
    document.getElementById('confirm-modal')?.classList.remove('open');
    closePreview();
  }
});
</script>
@endsection