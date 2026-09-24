@extends('layouts.app')

@section('title', ($isEdit ?? false) ? 'Editar producto web' : 'Nuevo producto web')
@section('titulo', ($isEdit ?? false) ? 'Editar producto' : 'Nuevo producto')
{{-- Habilita el modo oscuro (el toggle de tema aplica aquí, como en el catálogo) --}}
@section('tema_oscuro', '1')

@section('content')
@php
  $item = $item ?? null;
  $isEdit = isset($item) && $item;

  $has1 = !empty($item->photo_1 ?? null);
  $has2 = !empty($item->photo_2 ?? null);
  $has3 = !empty($item->photo_3 ?? null);
  $hasSku = !empty($item->sku ?? null);

  $categories = $categories ?? collect();
  $locations = $locations ?? collect();

  $currentCategoryId = old('category_product_id', $item->category_product_id ?? '');
  $currentLocationId = old('primary_location_id', $item->primary_location_id ?? '');
  $currentUnitMeasure = old('unit_measure', $item->unit_measure ?? 'pieza');
  $currentContentQuantity = old('content_quantity', $item->content_quantity ?? 1);
  $currentContentUnitMeasure = old('content_unit_measure', $item->content_unit_measure ?? 'pieza');

  // Muestras
  $isSample = (bool) old('is_sample', $item->is_sample ?? false);
  $currentSampleStatus = old('sample_status', $item->sample_status ?? 'guardada');
  $currentSampleHolder = old('sample_holder', $item->sample_holder ?? '');
  $currentSampleOutAt  = old('sample_out_at', (isset($item->sample_out_at) && $item->sample_out_at) ? $item->sample_out_at->format('Y-m-d') : '');

  $currentCategory = null;
  if ($currentCategoryId) {
      $currentCategory = $categories->firstWhere('id', (int) $currentCategoryId);
  }
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet"/>

@push('styles')
@include('partials.ui-tokens')
<style>
  /* ====================== VARIABLES GLOBALES PREMIUM ====================== */
  /* Rebase al sistema de diseño del index (ui-tokens): mismos colores, bordes,
     radios y tipografía, con modo oscuro automático (heredado del toggle). */
  :root {
    --bg: var(--ui-surface-2);
    --soft: var(--ui-surface-2);
    --card: var(--ui-surface);
    --ink: var(--ui-ink);
    --text: var(--ui-ink-2);
    --muted: var(--ui-muted);
    --line: var(--ui-border);
    --blue: var(--ui-accent);
    --blue-soft: var(--ui-accent-soft);
    --success: var(--ui-ok-ink);
    --success-soft: var(--ui-ok-soft);
    --danger: var(--ui-danger);
    --danger-soft: var(--ui-danger-soft);

    --shadow-soft: var(--ui-shadow-xs);
    --shadow-hover: var(--ui-shadow-pop);
    --shadow-modal: var(--ui-shadow-pop);

    --radius-card: var(--ui-r-lg);
    --radius-input: var(--ui-r);
    --radius-btn: var(--ui-r);
  }
  /* Tipografía del index: Inter en todo (sin Quicksand). */
  h1, h2, h3, h4, .head-ui__text h1, .section-heading, .modal-title {
    font-family: "Inter", system-ui, sans-serif; letter-spacing: -0.02em;
  }

  html, body { height: 100%; }
  body { background: var(--bg); font-family: "Inter", system-ui, sans-serif; }

  h1, h2, h3, h4, .head-ui__text h1, .section-heading, .modal-title {
    font-family: "Quicksand", system-ui, sans-serif;
    letter-spacing: -0.02em;
  }

  .wrap-ui {
    padding-top: 32px;
    color: var(--text);
    max-width: 1200px;
    margin: 0 auto 56px;
    padding-left: 24px;
    padding-right: 24px;
    box-sizing: border-box;
    min-height: calc(100vh - 32px);
  }
  .wrap-ui *, .wrap-ui *::before, .wrap-ui *::after { box-sizing: border-box; }

  .w-full{width:100%;}
  .flex{display:flex;}
  .items-center{align-items:center;}
  .items-start{align-items:flex-start;}
  .justify-between{justify-content:space-between;}
  .justify-center{justify-content:center;}
  .justify-end{justify-content:flex-end;}
  .flex-1{flex:1;}
  .flex-col{flex-direction:column;}
  .flex-wrap{flex-wrap:wrap;}

  .grid{display:grid; gap:32px;}
  .grid-2{grid-template-columns:repeat(2,1fr);}
  .grid-3{grid-template-columns:repeat(3,1fr);}
  .grid-main{grid-template-columns:1fr 380px;align-items:start;}
  .grid-2-ai{display:grid;grid-template-columns:420px 1fr;gap:32px;align-items:stretch;margin-bottom:28px;}

  .gap-2{gap:8px;} .gap-4{gap:16px;} .gap-6{gap:24px;}
  .m-0{margin:0!important;}
  .mb-2{margin-bottom:8px;} .mb-4{margin-bottom:16px;} .mb-5{margin-bottom:20px;} .mb-6{margin-bottom:24px;}
  .mt-2{margin-top:8px;} .mt-4{margin-top:16px;} .mt-6{margin-top:24px;} .ml-auto{margin-left:auto;}

  .text-xs{font-size:0.75rem;} .text-sm{font-size:0.875rem;} .text-base{font-size:1rem;} .text-lg{font-size:1.125rem;}
  .font-bold{font-weight:700;} .font-medium{font-weight:500;}
  .uppercase{text-transform:uppercase;} .text-right{text-align:right;}
  .text-muted{color:var(--muted);} .text-success{color:var(--success);} .text-ink{color:var(--ink);} .text-blue{color:var(--blue);}

  .head-ui {
    display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:16px; margin-bottom:32px;
  }
  .head-ui__text h1 {
    margin:0; font-size:2rem; font-weight:800; color:var(--ink); display:flex; align-items:center; gap:12px;
  }
  .head-ui__text h1 span { color:var(--muted); font-weight:600; font-size:1rem; }
  .head-ui__text p { margin:8px 0 0 0; color:var(--text); font-size:0.95rem; max-width:760px; line-height:1.6; font-weight:500; }

  .tabs-wrapper {
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:24px;
  }
  .tabs {
    display:inline-flex; background:var(--card); padding:6px; border-radius:999px; border:1px solid var(--line);
    box-shadow: var(--shadow-soft);
  }
  .tab {
    padding:10px 20px; border-radius:999px; border:none; background:transparent;
    font-weight:600; font-size:0.9rem; color:var(--muted);
    display:flex; align-items:center; gap:8px; cursor:pointer; transition:all 0.2s ease; font-family:inherit;
  }
  .tab:hover { color:var(--ink); }
  .tab.active { background:var(--blue); color:#fff; box-shadow: 0 4px 10px rgba(0, 122, 255, 0.2); }
  .tab svg { width:18px; height:18px; }

  .tabs-mode {
    display:inline-flex; align-items:center; gap:8px; font-weight:600; font-size:0.85rem; color:var(--muted);
    background:var(--card); border:1px solid var(--line); border-radius:999px; padding:10px 16px;
    box-shadow: var(--shadow-soft);
  }
  .tabs-mode .dot {
    width:8px; height:8px; border-radius:50%; background:var(--success); box-shadow:0 0 0 3px var(--success-soft);
  }

  .card {
    background:var(--card); border-radius:var(--radius-card); border:1px solid rgba(226, 232, 240, 0.8);
    padding:28px; box-shadow:var(--shadow-soft); display:flex; flex-direction:column;
    transition: box-shadow 0.3s ease;
  }
  .card:hover { box-shadow: var(--shadow-hover); }
  .h-full { height:100%; }

  .col-left, .col-right { display:flex; flex-direction:column; gap:32px; }
  .col-left .card, .col-right .card { margin:0 !important; }

  .section-heading {
    font-size:1.25rem; font-weight:800; color:var(--ink); margin:0 0 20px 0; padding:0; border:0;
  }
  .section-header-flex {
    display:flex; justify-content:space-between; align-items:baseline; margin-bottom:20px; gap:12px; flex-wrap:wrap;
  }
  .section-header-flex .section-heading { margin:0; }
  .hint { font-size:0.85rem; color:var(--muted); font-weight: 500;}

  .form-group { margin-bottom:20px; }
  .form-label { display:flex; justify-content:space-between; font-size:0.85rem; font-weight:700; color:var(--ink); margin-bottom:8px; text-transform:uppercase; letter-spacing:0.04em; }
  .req { color:var(--danger); }

  .form-input, .form-select {
    width:100%; border:1px solid var(--line); border-radius:var(--radius-input); padding:14px 16px;
    font-family:inherit; font-size:0.95rem; font-weight:500; color:var(--ink); background:var(--bg);
    transition:all 0.2s ease; outline:none; -webkit-appearance: none; appearance: none;
  }
  .form-input:focus, .form-select:focus { border-color:var(--blue); box-shadow:0 0 0 4px var(--blue-soft); background:var(--card); }
  .form-input::placeholder { color:#94a3b8; font-weight:400; }
  .form-input[readonly], .form-select:disabled { background:var(--soft); color:var(--muted); cursor:not-allowed; }

  .form-select {
    padding-right: 38px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
    background-position: right 16px center; background-size: 16px; background-repeat: no-repeat;
  }

  .resize-y { resize:vertical; }
  .input-icon-wrapper { position:relative; }
  .input-icon-wrapper .icon-left { position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--muted); font-weight:700; pointer-events:none; }
  .with-icon { padding-left:36px; }

  .btn-primary {
    background:var(--blue); color:#ffffff; border:none; border-radius:var(--radius-btn);
    padding:14px 24px; font-weight:600; font-size:0.95rem; font-family:inherit;
    cursor:pointer; transition:all 0.2s ease; display:inline-flex; align-items:center; justify-content:center; gap:8px;
    box-shadow: 0 4px 12px rgba(0, 122, 255, 0.2);
  }
  .btn-primary:hover:not(:disabled) { transform:translateY(-2px); box-shadow:0 8px 20px rgba(0, 122, 255, 0.3); }
  .btn-primary:disabled { opacity:0.6; cursor:not-allowed; transform:none; }
  .btn-primary:active { transform: scale(0.98); }

  .btn-ghost {
    background:transparent; color:var(--text); border:1px solid var(--line); border-radius:var(--radius-btn);
    padding:12px 20px; font-weight:600; font-size:0.95rem; font-family:inherit;
    cursor:pointer; transition:all 0.2s ease; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:8px;
    background: var(--card);
  }
  .btn-ghost:hover { background:var(--soft); color:var(--blue); border-color:#cbd5e1; }

  .btn-outline {
    background:transparent; color:var(--blue); border:1px solid var(--blue); border-radius:var(--radius-btn);
    padding:12px 20px; font-weight:600; font-size:0.95rem; font-family:inherit;
    cursor:pointer; transition:all 0.2s ease; display:inline-flex; align-items:center; justify-content:center; gap:8px; text-decoration:none;
  }
  .btn-outline:hover:not(:disabled) { background:var(--blue-soft); transform:translateY(-1px); }
  .btn-outline:disabled { opacity:0.55; cursor:not-allowed; border-color:var(--line); color:var(--muted); }

  .btn-text { background:none; border:none; color:var(--blue); font-weight:600; font-size:0.9rem; cursor:pointer; padding:0; text-decoration:none; font-family:inherit; }
  .btn-text:hover { text-decoration:underline; }

  .btn-text-danger { background:none; border:none; color:var(--danger); font-weight:600; font-size:0.9rem; cursor:pointer; padding:0; text-decoration:none; font-family:inherit; }
  .btn-text-danger:hover { text-decoration:underline; }

  .btn-sm { padding:10px 16px; font-size:0.85rem; }

  .btn-icon { color:var(--muted); transition:color 0.2s ease; display:inline-flex; align-items:center; justify-content:center; }
  .btn-icon:hover { color:var(--blue); }
  .btn-icon svg { width:20px; height:20px; }

  .btn-icon-square {
    width:42px; height:42px; display:flex; align-items:center; justify-content:center;
    border-radius:10px; background:var(--bg); border:1px solid var(--line);
    color:var(--text); cursor:pointer; transition:all 0.2s ease;
  }
  .btn-icon-square:hover:not(:disabled) { background:var(--soft); border-color:var(--blue); color:var(--blue); transform:translateY(-1px); }
  .btn-icon-square:disabled { opacity:0.55; cursor:not-allowed; }

  .table-wrap { border:1px solid var(--line); border-radius:16px; overflow-x:auto; background:var(--card); margin-bottom:16px; box-shadow:var(--shadow-soft); }
  .table { width:100%; border-collapse:collapse; font-size:0.9rem; }
  .table th, .table td { padding:16px; border-bottom:1px solid var(--line); white-space:nowrap; text-align:left; }
  .table th { background:var(--soft); font-weight:700; color:var(--muted); font-size:0.8rem; text-transform:uppercase; letter-spacing:0.06em; }
  .table td { color:var(--ink); font-weight:500; }
  .table tr:last-child td { border-bottom:none; }

  .category-display {
    width:100%; background:var(--bg); border:1px solid var(--line); border-radius:var(--radius-input);
    padding:16px; display:flex; align-items:center; justify-content:space-between;
    text-align:left; cursor:pointer; transition:all 0.2s ease; font-family:inherit;
  }
  .category-display:hover { border-color:var(--blue); box-shadow:0 4px 12px rgba(0, 122, 255, 0.08); background:var(--card); }
  .category-display__title { font-size:1rem; font-weight:700; color:var(--ink); }
  .category-display__path { font-size:0.85rem; color:var(--muted); margin-top:4px; font-weight:500; }
  .icon-right { width:20px; height:20px; color:var(--muted); }

  .media-box { position:relative; }
  .media-area { display:block; cursor:pointer; }
  .media-preview {
    width:100%; aspect-ratio:1; background:var(--bg); border:2px dashed #cbd5e1;
    border-radius:16px; display:flex; align-items:center; justify-content:center;
    overflow:hidden; margin-bottom:12px; transition:all 0.2s ease; color:#94a3b8;
  }
  .media-area:hover .media-preview { border-color:var(--blue); background:var(--blue-soft); color:var(--blue); }
  .media-preview svg { width:36px; height:36px; }
  .media-preview img { width:100%; height:100%; object-fit:contain; background:#fff; }

  .media-title { display:block; font-size:0.95rem; font-weight:700; color:var(--ink); margin-bottom:4px; }
  .media-subtitle { display:block; font-size:0.85rem; color:var(--muted); font-weight:500; }

  .media-clear {
    position:absolute; top:-12px; right:-12px; width:34px; height:34px; border-radius:999px;
    background:#ffffff; border:1px solid var(--line); color:var(--muted);
    display:none; align-items:center; justify-content:center; cursor:pointer;
    box-shadow:0 8px 16px rgba(0,0,0,0.1); z-index:10; transition:all 0.2s ease;
  }
  .media-clear:hover { background:var(--danger); color:#ffffff; border-color:var(--danger); transform:scale(1.1); }
  .media-clear svg { width:16px; height:16px; }

  .media-box.has-media .media-clear { display:flex; }
  .media-box.has-media .media-preview { border:1px solid var(--line); background:#ffffff; }

  .modal-backdrop {
    position:fixed; inset:0; background:rgba(15, 23, 42, 0.5); backdrop-filter: blur(6px);
    display:none; align-items:center; justify-content:center; z-index:9998; padding:24px;
    opacity: 0; transition: opacity 0.3s ease;
  }
  .modal-backdrop.show { display:flex; opacity: 1; }

  .modal {
    width:100%; max-width:800px;
    background:#ffffff;
    border-radius:24px;
    box-shadow:var(--shadow-modal);
    overflow:hidden;
    display:flex; flex-direction:column;
    max-height:calc(100vh - 48px);
    border:1px solid rgba(255,255,255,0.2);
    transform: translateY(20px) scale(0.98);
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .modal-backdrop.show .modal { transform: translateY(0) scale(1); }

  @media (min-width: 1024px) {
    .modal { height: 85vh; }
  }

  .modal-header {
    padding:24px 32px;
    border-bottom:1px solid var(--line);
    display:flex; justify-content:space-between; align-items:flex-start; gap:16px;
    background:#ffffff; z-index:10;
  }
  .modal-title { margin:0; font-size:1.35rem; font-weight:800; color:var(--ink); }
  .modal-subtitle { margin:6px 0 0 0; font-size:0.95rem; color:var(--muted); font-weight:500; }

  .modal-close {
    background:var(--bg); border:none; color:var(--muted);
    cursor:pointer; width:36px; height:36px; border-radius:50%; transition:all 0.2s ease;
    display:flex; align-items:center; justify-content:center;
  }
  .modal-close:hover { background:var(--danger-soft); color:var(--danger); }
  .modal-close svg { width:20px; height:20px; }

  .modal-breadcrumb {
    padding:16px 32px;
    background:var(--soft);
    border-bottom:1px solid var(--line);
    font-size:0.9rem; font-weight:600;
    display: flex; flex-wrap: wrap; align-items: center; gap: 6px;
  }
  .mlcat-crumb { color:var(--muted); transition: color 0.2s; }
  .mlcat-crumb.clickable-crumb { cursor:pointer; color:var(--blue); }
  .mlcat-crumb.clickable-crumb:hover { color:var(--blue); text-decoration:underline; }
  .mlcat-sep { color:#cbd5e1; font-weight:400; }

  .modal-body { padding:24px 32px; overflow-y:auto; flex:1; background:#ffffff; position:relative; }

  .modal-footer {
    padding:20px 32px;
    border-top:1px solid var(--line);
    display:flex; justify-content:space-between; align-items:center; gap:16px;
    background:var(--soft);
    position:sticky; bottom:0;
  }

  .search-wrapper { position: relative; margin-bottom: 20px; }
  .search-wrapper input {
    width: 100%; padding: 14px 16px 14px 44px;
    border-radius: 12px; border: 1px solid var(--line);
    background: var(--bg); font-size: 0.95rem; font-weight:500;
    transition: all 0.2s; outline: none;
  }
  .search-wrapper input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft); background:#fff; }
  .search-wrapper svg {
    position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
    color: var(--muted); width: 18px; height:18px;
  }

  .category-levels { display:flex; flex-direction:column; background:#fff; }
  .mlcat-level { width: 100%; animation: fadeInRight 0.3s ease; }
  @keyframes fadeInRight { from { opacity:0; transform:translateX(15px); } to { opacity:1; transform:translateX(0); } }

  .mlcat-options { display:grid; gap:8px; }
  .mlcat-option {
    display:flex; justify-content:space-between; align-items:center; padding:16px 20px;
    background:#ffffff; border:1px solid var(--line); border-radius:14px;
    cursor:pointer; text-align:left; transition:all 0.2s ease; font-family:inherit;
  }
  .mlcat-option:hover { background:var(--bg); border-color:#cbd5e1; transform:translateX(4px); }
  .mlcat-option.active { background:var(--blue-soft); border-color:var(--blue); box-shadow:0 4px 12px rgba(0, 122, 255, 0.15); }
  .mlcat-option__name { font-size:1rem; font-weight:600; color:var(--ink); }

  .mlcat-badge { font-size:0.75rem; font-weight:700; color:var(--muted); background:var(--soft); padding:4px 12px; border-radius:999px; border:1px solid var(--line); }
  .mlcat-empty { padding:32px; text-align:center; color:var(--muted); font-size:1rem; font-weight:500; background:var(--bg); border-radius:14px; border:2px dashed var(--line); }

  .alert-error {
    background:var(--danger-soft); color:var(--danger); padding:20px; border-radius:16px;
    font-size:0.95rem; font-weight:600; margin-bottom:24px; display:flex; gap:16px; align-items:flex-start;
    border:1px solid rgba(225,29,72,0.15);
  }
  .alert-error svg { width:24px; height:24px; flex-shrink:0; }
  .alert-error ul { margin:8px 0 0 20px; padding:0; color:var(--ink); font-weight:500; }

  .ai-badge { background:var(--blue-soft); color:var(--blue); padding:2px 10px; border-radius:999px; font-size:0.7rem; font-weight:800; margin-left:8px; display:inline-flex; align-items:center; letter-spacing:0.04em; }
  .hidden { display:none !important; }

  .ai-suggested-input { border-color:var(--blue) !important; box-shadow:0 0 0 4px var(--blue-soft) !important; background:#f8fbff !important; transition:all 0.5s ease; }

  .sticky-footer {
    position:sticky; bottom:0; background:rgba(244, 247, 249, 0.85); backdrop-filter:blur(12px);
    padding:24px 0; border-top:1px solid rgba(226, 232, 240, 0.6); z-index:40; margin-top:32px;
    display:flex; justify-content:flex-end;
  }
  .footer-actions { display:flex; gap:16px; }

  .step-title { display:flex; gap:16px; align-items:flex-start; margin-bottom:24px; }
  .step-num {
    width:36px; height:36px; border-radius:12px; background:var(--blue); color:#ffffff;
    display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1.1rem; flex-shrink:0;
  }
  .step-title h3 { margin:0 0 6px 0; font-size:1.2rem; color:var(--ink); font-weight:800; }
  .step-title p { margin:0; color:var(--text); font-size:0.95rem; line-height:1.6; font-weight:500; }

  .row-flex { display:flex; gap:16px; align-items:flex-end; margin-bottom:20px; }

  .qr-card { border:1px solid var(--line); border-radius:16px; background:#fff; overflow:hidden; box-shadow:var(--shadow-soft); }
  .qr-header { padding:16px 20px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center; background:#ffffff; }
  .qr-chip { background:var(--blue); color:#fff; font-size:0.75rem; font-weight:700; padding:6px 12px; border-radius:999px; }
  .qr-status { font-size:0.85rem; font-weight:600; color:var(--muted); display:flex; align-items:center; gap:8px; }
  .qr-box { padding:32px; display:flex; justify-content:center; background:#ffffff; }
  .qr-frame { padding:16px; border:2px dashed var(--line); border-radius:20px; background:#fff; position:relative; }
  .qr-footer { padding:16px 20px; background:var(--soft); border-top:1px solid var(--line); }
  .qr-url { display:flex; justify-content:space-between; font-size:0.85rem; font-weight:600; margin-bottom:16px; gap:10px; flex-wrap:wrap; }
  .qr-url span { color:var(--muted); }
  .qr-url a { color:var(--blue); text-decoration:none; font-weight:700; }
  .qr-url a:hover { text-decoration:underline; }

  .timeline-ai { display:flex; flex-direction:column; gap:12px; }
  .timeline-ai__item { display:flex; align-items:center; gap:12px; font-size:0.9rem; color:var(--muted); font-weight:600; }
  .timeline-ai__dot { width:14px; height:14px; border-radius:50%; border:2px solid var(--line); background:var(--soft); transition:all 0.3s; }
  .timeline-ai__item.active { color:var(--ink); font-weight:700; }
  .timeline-ai__item.active .timeline-ai__dot { border-color:var(--blue); background:var(--blue); box-shadow:0 0 0 4px var(--blue-soft); }

  .status-box { margin-top:20px; text-align:center; }
  .status-badge { display:inline-block; font-size:1.1rem; font-weight:800; color:var(--ink); margin-bottom:8px; }
  .status-hint { color:var(--muted); font-size:0.95rem; font-weight:500; }

  .tips-card { background:var(--blue-soft); border-color:#bfdbfe; }
  .tips-title { display:flex; align-items:center; gap:10px; font-weight:800; color:var(--blue); margin-bottom:16px; font-size:1.05rem; }
  .tips-card ul { margin:0; padding-left:24px; color:var(--ink); font-size:0.95rem; line-height:1.7; font-weight:500; }
  .tips-card li { margin-bottom:8px; }

  .waiting-box { text-align:center; padding:60px 20px; }
  .spinner-box { margin-bottom:20px; color:var(--blue); }
  .spinner { width:48px; height:48px; animation:spin 1.5s linear infinite; }
  @keyframes spin { 100% { transform:rotate(360deg); } }
  .waiting-msg { font-size:1.2rem; font-weight:800; color:var(--ink); margin-bottom:8px; }
  .waiting-box p { color:var(--muted); font-size:1rem; font-weight:500; }

  .skeleton-wrap { margin-top:28px; display:grid; gap:12px; }
  .skeleton { height:48px; background:var(--line); border-radius:12px; animation:pulse 1.5s ease-in-out infinite; }
  .skeleton.short { width:60%; margin:0 auto; }
  @keyframes pulse { 0%, 100% { opacity: 0.4; } 50% { opacity: 0.8; } }

  .summary-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:16px; background:var(--bg); border:1px solid var(--line); border-radius:16px; padding:20px; margin-bottom:24px; }
  .summary-grid label { font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:0.08em; display:block; margin-bottom:6px; }
  .summary-grid div > div { font-size:1.05rem; font-weight:800; color:var(--ink); }

  .actions-row { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-top:20px; }

  .dropzone-minimal {
    border:2px dashed var(--line); border-radius:16px; padding:36px; text-align:center;
    background:var(--bg); cursor:pointer; transition:all 0.2s ease; position:relative;
  }
  .dropzone-minimal:hover, .dropzone-minimal.is-dragover { border-color:var(--blue); background:var(--blue-soft); color:var(--blue); }
  .hidden-input { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
  .dropzone-content { display:flex; flex-direction:column; align-items:center; gap:16px; color:var(--muted); pointer-events:none; }
  .dropzone-content svg { width:40px; height:40px; }
  .dropzone-content span { font-weight:600; font-size:1rem; }
  .dropzone-minimal:hover .dropzone-content { color:var(--blue); }

  .ai-copilot-wrapper { background:var(--card); border:1px solid var(--line); border-radius:24px; padding:32px; margin-bottom:32px; box-shadow:var(--shadow-soft); }
  .ai-copilot-content { display:grid; grid-template-columns:1fr 1fr; gap:36px; align-items:center; }
  .ai-header { display:flex; align-items:center; gap:12px; margin-bottom:12px; }
  .ai-header h2 { margin:0; font-size:1.3rem; font-weight:800; color:var(--ink); }
  .ai-icon { color:var(--blue); width:26px; height:26px; }
  .badge-beta { background:var(--blue); color:#fff; font-size:0.7rem; font-weight:800; padding:4px 10px; border-radius:999px; letter-spacing:0.06em; }
  .ai-left p { color:var(--text); font-size:1rem; line-height:1.6; margin:0 0 20px 0; font-weight:500; }

  .ai-files-list { display:flex; flex-direction:column; gap:10px; }
  .ai-files-list span { display:inline-flex; align-items:center; gap:8px; font-size:0.9rem; font-weight:600; color:var(--ink); background:var(--bg); padding:8px 16px; border-radius:12px; border:1px solid var(--line); }
  .ai-right { display:flex; flex-direction:column; gap:16px; }

  .integration-panel { border:1px solid var(--line); border-radius:20px; padding:24px; background:var(--bg); position:relative; overflow:hidden; transition:border-color 0.3s; }
  .integration-panel:hover { border-color:#cbd5e1; }
  .channel-logo { width:56px; height:56px; border-radius:16px; display:flex; align-items:center; justify-content:center; background:#ffe600; flex-shrink:0; border:1px solid rgba(0,0,0,0.05); }
  .channel-logo img { width:36px; height:auto; }
  .channel-logo.bg-dark { background:#232f3e; }
  .overlay-lock { position:absolute; inset:0; background:rgba(255,255,255,0.85); backdrop-filter:blur(4px); z-index:10; display:flex; flex-direction:column; align-items:center; justify-content:center; color:var(--ink); font-weight:800; }
  .overlay-lock svg { width:36px; height:36px; margin-bottom:12px; color:var(--muted); }

  .sample-card { background:#fffbeb; border-color:#fde68a; }
  .sample-toggle { display:flex; align-items:center; gap:10px; cursor:pointer; text-transform:none; justify-content:flex-start; }
  .sample-toggle input { width:18px; height:18px; accent-color:var(--blue); }

  .animate-enter{opacity:0;animation:enterSlide .4s cubic-bezier(0.16,1,0.3,1) forwards;animation-delay:calc(var(--stagger) * .08s);}
  @keyframes enterSlide{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
  .fade-in{animation:fadeIn .3s ease forwards;}
  @keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
  .fade-in-up{animation:fadeInUp .4s ease forwards;}
  @keyframes fadeInUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}

  @media(max-width: 1024px) {
    .grid-main { grid-template-columns: 1fr; }
    .grid-2-ai { grid-template-columns: 1fr; }
  }
  @media(max-width: 768px) {
    .ai-copilot-content { grid-template-columns: 1fr; gap: 24px; }
    .summary-grid { grid-template-columns:repeat(2, 1fr); }
    .grid-3 { grid-template-columns: 1fr; }
    .grid-2 { grid-template-columns: 1fr; }
  }

  /* ====================== AJUSTES DE MODO OSCURO ======================
     El oscuro lo dan los ui-tokens (como el index). Aquí solo se corrigen los
     pocos colores fijos que no usaban variables. El markup y el JS no cambian. */
  :root[data-theme="dark"] .form-input::placeholder{ color:var(--muted); }
  :root[data-theme="dark"] .search-wrapper input:focus{ background:var(--card); }
  :root[data-theme="dark"] .category-levels,
  :root[data-theme="dark"] .mlcat-option{ background:var(--card); }
  :root[data-theme="dark"] .media-preview{ border-color:var(--line); }
  :root[data-theme="dark"] .media-preview img,
  :root[data-theme="dark"] .media-clear,
  :root[data-theme="dark"] .media-box.has-media .media-preview{ background:var(--card); }
  :root[data-theme="dark"] .modal,
  :root[data-theme="dark"] .modal-header,
  :root[data-theme="dark"] .modal-body{ background:var(--card); }
  :root[data-theme="dark"] .modal{ border-color:var(--line); }
  :root[data-theme="dark"] .qr-card,
  :root[data-theme="dark"] .qr-header,
  :root[data-theme="dark"] .qr-box,
  :root[data-theme="dark"] .qr-frame{ background:var(--card); }
  :root[data-theme="dark"] .ai-suggested-input{ background:var(--blue-soft) !important; }
  :root[data-theme="dark"] .tips-card{ border-color:var(--blue); }
  :root[data-theme="dark"] .sample-card{ background:var(--soft); border-color:var(--line); }
  :root[data-theme="dark"] .overlay-lock{ background:rgba(9,14,25,.82); color:var(--ink); }
  :root[data-theme="dark"] .sticky-footer{ background:rgba(11,18,32,.85); border-top-color:var(--line); }
  :root[data-theme="dark"] .btn-ghost:hover,
  :root[data-theme="dark"] .integration-panel:hover,
  :root[data-theme="dark"] .mlcat-option:hover{ border-color:var(--blue); }
  :root[data-theme="dark"] .form-select{
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%238a99b1'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
  }

  /* ====================== REFINAMIENTO MINIMALISTA ======================
     Capa de estilo: más limpio, botones planos, mejor distribución.
     No cambia el markup ni el JS; aplica en claro y oscuro. */
  .wrap-ui{ max-width:1120px; padding-top:28px; }
  .head-ui{ margin-bottom:22px; }
  .head-ui__text h1{ font-size:1.55rem; font-weight:700; letter-spacing:-.02em; }
  .head-ui__text h1 span{ font-size:.9rem; }
  .head-ui__text p{ font-size:.9rem; color:var(--muted); max-width:640px; }

  .grid{ gap:20px; }
  .col-left, .col-right{ gap:20px; }
  .grid-main{ gap:24px; }

  .card{ border-radius:16px; border:1px solid var(--line); box-shadow:none; padding:22px; }
  .card:hover{ box-shadow:none; }
  .ai-copilot-wrapper{ border-radius:16px; box-shadow:none; padding:22px; }

  .section-heading{ font-size:1.05rem; font-weight:700; letter-spacing:-.01em; margin-bottom:18px; }
  .form-group{ margin-bottom:16px; }
  .form-label{ text-transform:none; letter-spacing:0; font-weight:500; font-size:.82rem; color:var(--muted); margin-bottom:7px; }

  .form-input, .form-select{ border-radius:12px; padding:12px 14px; background:var(--card); border-color:var(--line); }
  .form-input:focus, .form-select:focus{ box-shadow:0 0 0 3px var(--blue-soft); }
  .form-input::placeholder{ font-weight:400; }
  /* Respetar el espacio del ícono $ y de la flecha del select (el padding de arriba los pisaba) */
  .form-select{ padding-right:38px; }
  .form-input.with-icon{ padding-left:32px; }
  .input-icon-wrapper .icon-left{ left:13px; font-weight:600; color:var(--muted); }
  .form-select option{ background:var(--card); color:var(--ink); }

  /* Botones idénticos al index: altura fija, fuente chica, limpios, sin glow */
  .btn-primary, .btn-ghost, .btn-outline{
    height:38px; padding:0 16px; font-size:.875rem; font-weight:600;
    border-radius:var(--radius-btn); box-shadow:none;
  }
  .btn-primary{ background:var(--blue); color:#fff; border:0; }
  .btn-primary:hover:not(:disabled){ transform:none; box-shadow:none; background:var(--ui-accent-hover); }
  .btn-primary:active{ transform:scale(.99); }
  .btn-ghost{ background:var(--card); color:var(--text); border:1px solid var(--line); }
  .btn-ghost:hover{ transform:none; background:var(--soft); color:var(--ink); border-color:var(--line); }
  .btn-outline{ background:transparent; color:var(--blue); border:1px solid var(--blue); }
  .btn-outline:hover:not(:disabled){ transform:none; background:var(--blue-soft); }
  .btn-sm{ height:32px; padding:0 12px; font-size:.8rem; }
  .btn-icon-square{ border-radius:var(--radius-btn); }
  .tabs, .tabs-mode, .tab.active, .table-wrap, .sticky-footer{ box-shadow:none; }

  /* Nada de mayúsculas forzadas (como el index) */
  .form-label, .table th, .summary-grid label, .mlcat-badge{ text-transform:none; letter-spacing:0; }

  /* ===== Compacto (menos scroll) ===== */
  .wrap-ui{ padding-top:18px; margin-bottom:32px; }
  .head-ui{ margin-bottom:16px; }
  .head-ui__text h1{ font-size:1.35rem; }
  .ai-copilot-wrapper{ padding:16px; margin-bottom:14px; }
  .card{ padding:16px 18px; }
  .grid{ gap:14px; }
  .col-left, .col-right{ gap:14px; }
  .grid-3{ gap:12px; }
  .section-heading{ margin-bottom:12px; font-size:1rem; }
  .section-header-flex{ margin-bottom:12px; }
  .form-group{ margin-bottom:10px; }
  .form-group.mb-6, .form-group.mb-5{ margin-bottom:12px; }
  .form-label{ margin-bottom:5px; }
  .form-input, .form-select{ padding:9px 12px; }
  textarea[name="description"]{ min-height:96px !important; }
  .pasos{ margin-bottom:12px; }
  .paso-nav{ margin:12px 0; }
  .media-preview{ aspect-ratio:4/3; margin-bottom:8px; }

  /* Micro-transiciones suaves */
  .btn-primary, .btn-ghost, .btn-outline, .btn-icon-square, .card, .form-input, .form-select, .media-preview{ transition:all .18s ease; }

  /* ====================== ASISTENTE POR PASOS (estilo Obsidiana) ======================
     El formulario largo se parte en pasos: una tarjeta a la vez, con barra de
     pasos que permite saltar y un pie Atrás/Continuar. Las tarjetas ocultas
     siguen enviando sus datos (el form usa novalidate y valida el servidor). */
  #catalogItemForm .grid-main{ display:block; }
  #catalogItemForm .col-left, #catalogItemForm .col-right{ display:contents; }

  .pasos{ display:flex; gap:6px; margin:0 0 18px; overflow-x:auto; scrollbar-width:none; -ms-overflow-style:none; padding-bottom:2px; }
  .pasos::-webkit-scrollbar{ height:0; }
  .paso-chip{ display:flex; align-items:center; gap:8px; padding:8px 13px; border:1px solid var(--line);
              border-radius:10px; background:var(--card); color:var(--muted); font-size:13px; font-weight:500;
              white-space:nowrap; cursor:pointer; flex:0 0 auto; transition:all .16s ease; font-family:inherit; }
  .paso-chip .n{ display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px;
                 border-radius:50%; background:var(--soft); color:var(--muted); font-size:11.5px; font-weight:700; flex:0 0 20px; }
  .paso-chip:hover{ border-color:var(--muted); color:var(--ink); }
  .paso-chip[data-estado="actual"]{ border-color:var(--blue); color:var(--ink); }
  .paso-chip[data-estado="actual"] .n{ background:var(--blue); color:#fff; }
  .paso-chip[data-estado="listo"]{ border-color:var(--blue); color:var(--ink); background:var(--blue-soft); }
  .paso-chip[data-estado="listo"] .n{ background:var(--blue); color:#fff; }
  .paso-chip[data-estado="falta"]{ border-color:var(--danger); color:var(--ink); }
  .paso-chip[data-estado="falta"] .n{ background:var(--danger); color:#fff; }
  @media (max-width:640px){ .paso-chip .txt{ display:none; } .paso-chip{ padding:8px 10px; } }

  /* Aviso de lo que falta, dentro del paso mismo */
  .paso-faltan{ margin:0 0 16px; padding:12px 14px; border:1px solid var(--danger);
                border-radius:10px; background:var(--danger-soft); color:var(--danger); font-size:13.5px; }
  .paso-faltan b{ display:block; margin-bottom:4px; }
  .paso-faltan ul{ margin:0; padding-left:18px; }
  .paso-faltan li{ margin-top:2px; }
  .campo-error{ border-color:var(--danger) !important; }

  .card.paso{ display:none; animation:none; opacity:1; }
  .card.paso[data-activo]{ display:flex; }

  /* ===== Sin scroll horizontal en la página ===== */
  .wrap-ui{ overflow-x:clip; }
  #catalogItemForm .card,
  #catalogItemForm .grid,
  #catalogItemForm .grid > *{ min-width:0; }
  /* Fila de presentaciones: los inputs deben poder encogerse */
  .pres-row{ min-width:0; }
  .pres-row > div{ min-width:0; }
  .pres-row input,
  .pres-row label{ min-width:0; }
  @media (max-width:860px){
    .pres-row > div{ grid-template-columns:1fr 1fr !important; }
    .pres-row .pres-remove{ grid-column:1 / -1; justify-self:end; }
  }

  .paso-nav{ display:flex; align-items:center; gap:10px; margin:18px 0; }
  .paso-nav .cuenta{ margin-right:auto; color:var(--muted); font-size:13px; }
  @media (max-width:640px){
    .paso-nav{ flex-wrap:wrap; }
    .paso-nav .cuenta{ width:100%; margin:0 0 4px; }
    .paso-nav .btn-ghost, .paso-nav .btn-outline{ flex:1; }
  }

  /* Botón "Volver" y toggle de IA: minimalistas (sin marco) */
  .btn-volver{ display:inline-flex; align-items:center; gap:7px; height:34px; padding:0 10px; border:0;
               border-radius:var(--radius-btn); background:none; color:var(--muted); font:inherit;
               font-size:.875rem; font-weight:600; text-decoration:none; cursor:pointer; transition:all .16s ease; }
  .btn-volver:hover{ background:var(--soft); color:var(--ink); }
  .btn-volver svg{ width:16px; height:16px; opacity:.85; }
  #toggleAiCopilot.is-on{ background:var(--blue-soft); color:var(--blue); }

  @media (prefers-reduced-motion: reduce){
    .animate-enter, .fade-in, .fade-in-up{ animation:none !important; opacity:1 !important; transform:none !important; }
  }
</style>
@endpush

<div class="wrap-ui fade-in-up">
  @include('admin.catalog._toast')
  <div class="head-ui">
    <div class="head-ui__text">
      <h1>{{ $isEdit ? 'Editar producto' : 'Nuevo producto' }} <span>Catálogo web</span></h1>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
      <button type="button" class="tour-abrir" data-tour-start="producto-form">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.6 9.4a2.5 2.5 0 1 1 3.3 2.9c-.6.2-.9.8-.9 1.4v.3"/><path d="M12 17h.01"/></svg>
        ¿Cómo funciona?
      </button>
      <a class="btn-volver" href="{{ route('admin.catalog.index') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
        Volver al catálogo
      </a>
    </div>
  </div>


  <section id="panelAi" class="panel-ai fade-in-up" style="display:none">
    <div class="grid-2-ai">
      <div class="col-ai">
        <div class="card h-full">
          <div class="step-title">
            <span class="step-num">1</span>
            <div>
              <h3>Sincronización Móvil</h3>
              <p>Genera un código QR, escanéalo con tu smartphone y sube las fotos de tu documento directamente.</p>
            </div>
          </div>

          <div class="row-flex">
            <div class="form-group m-0 flex-1">
              <label class="form-label">Tipo de comprobante</label>
              <select id="aiSourceType" class="form-select">
                <option value="factura">Factura</option>
                <option value="remision">Remisión</option>
                <option value="otro">Otro documento</option>
              </select>
            </div>
            <button type="button" id="btnAiStart" class="btn-primary">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3z"/><path d="M20 14h1v1h-1z"/><path d="M14 20h7"/></svg>
              Generar QR
            </button>
          </div>

          <div id="qrWrap" style="display:none; margin-top:20px;">
            <div class="qr-card">
              <div class="qr-header">
                <div class="qr-chip">Escanea para iniciar</div>
                <div class="qr-status" id="qrMiniStatus">
                  <span class="dot" style="background:var(--blue);"></span> Esperando conexión
                </div>
              </div>

              <div class="qr-box">
                <div class="qr-frame">
                  <div id="qrBox"></div>
                </div>
              </div>

              <div class="qr-footer">
                <div class="qr-url">
                  <span>Enlace de acceso manual:</span>
                  <a id="mobileUrl" href="#" target="_blank">Abrir link</a>
                </div>

                <div class="timeline-ai">
                  <div class="timeline-ai__item" data-st="0"><span class="timeline-ai__dot"></span> Conectando</div>
                  <div class="timeline-ai__item" data-st="1"><span class="timeline-ai__dot"></span> Subiendo</div>
                  <div class="timeline-ai__item" data-st="2"><span class="timeline-ai__dot"></span> Analizando</div>
                  <div class="timeline-ai__item" data-st="3"><span class="timeline-ai__dot"></span> Completado</div>
                </div>
              </div>
            </div>

            <div class="status-box">
              <div class="status-badge" id="aiStatusBadge">
                <span id="aiStatusText">Pendiente</span>
              </div>
              <div class="status-hint" id="aiStatusHint">
                El sistema está esperando las imágenes de tu dispositivo...
              </div>
            </div>
          </div>
        </div>

        <div class="card tips-card">
          <div class="tips-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            Mejores prácticas
          </div>
          <ul>
            <li>Asegúrate de tener buena iluminación, sin sombras pronunciadas.</li>
            <li>Encuadra correctamente el encabezado y la tabla de productos.</li>
            <li>Para documentos multipágina, toma una fotografía individual por hoja.</li>
          </ul>
        </div>
      </div>

      <div class="col-ai">
        <div class="card h-full">
          <div class="step-title">
            <span class="step-num">2</span>
            <div>
              <h3>Resultados de Extracción</h3>
              <p>La IA tabulará los datos encontrados. Selecciona el producto a registrar.</p>
            </div>
          </div>

          <div id="aiWaiting" class="waiting-box">
            <div class="spinner-box">
              <svg viewBox="0 0 24 24" class="spinner"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="30 30" stroke-linecap="round"/></svg>
            </div>
            <div class="waiting-msg">Esperando datos...</div>
            <p>Los resultados de la IA aparecerán aquí automáticamente.</p>
            <div class="skeleton-wrap">
              <div class="skeleton"></div>
              <div class="skeleton"></div>
              <div class="skeleton short"></div>
            </div>
          </div>

          <div id="aiResult" class="fade-in" style="display:none;">
            <div class="summary-grid">
              <div><label>PROVEEDOR</label><div id="exSupplier">—</div></div>
              <div><label>Nº FOLIO</label><div id="exFolio">—</div></div>
              <div><label>FECHA</label><div id="exDate">—</div></div>
              <div><label>TOTAL DOC.</label><div id="exTotal" class="text-blue">—</div></div>
            </div>

            <div class="table-wrap">
              <table class="table">
                <thead>
                  <tr>
                    <th>SKU</th>
                    <th>Descripción</th>
                    <th>Cant.</th>
                    <th>U.M.</th>
                    <th>P. Unitario</th>
                    <th>Total</th>
                    <th class="text-right">Acción</th>
                  </tr>
                </thead>
                <tbody id="aiItemsTbody"></tbody>
              </table>
            </div>

            <div class="actions-row">
              <button type="button" id="btnFillFirst" class="btn-primary btn-sm">Autocompletar primer ítem</button>
              <button type="button" id="btnBackManual" class="btn-outline btn-sm">Editar Manualmente</button>
              <div class="hint ml-auto">Podrás verificar todo antes de guardar.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div id="panelManual" class="fade-in-up">
    @if($errors->any())
      <div class="alert-error fade-in">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div>
          <strong style="color:var(--danger);">Revisa los siguientes detalles:</strong>
          <ul>
            @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
          </ul>
        </div>
      </div>
    @endif

    <form
      id="catalogItemForm"
      method="POST"
      action="{{ $isEdit ? route('admin.catalog.update', $item) : route('admin.catalog.store') }}"
      enctype="multipart/form-data"
      novalidate
    >
      @csrf
      @if($isEdit) @method('PUT') @endif

      <div style="display:flex; justify-content:flex-end; margin-bottom:16px;">
        <button type="button" id="toggleAiCopilot" class="btn-volver">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/></svg>
          Rellenar con IA
        </button>
      </div>

      <div class="ai-copilot-wrapper animate-enter" id="aiCopilot" style="--stagger: 1; display:none;">
        <div class="ai-copilot-content">
          <div class="ai-left">
            <div class="ai-header">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ai-icon"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
              <h2>Copiloto de IA</h2>
              <span class="badge-beta">BETA</span>
            </div>
            <p>Arrastra tu documento (PDF, JPG) y la inteligencia artificial extraerá y categorizará la información automáticamente para ahorrarte tiempo.</p>
            <div id="ai-files-list" class="ai-files-list empty:hidden"></div>
          </div>

          <div class="ai-right">
            <div id="ai-dropzone" class="dropzone-minimal group">
              <input id="ai_files" name="ai_files[]" type="file" multiple accept="image/*,.pdf" class="hidden-input">
              <div class="dropzone-content">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                <span id="ai-drop-text">Arrastra o clic para cargar</span>
              </div>
            </div>

            <button type="button" id="btn-ai-analyze" class="btn-outline w-full disabled:opacity-50">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="spinner hidden" id="ai-spinner" style="width:16px;height:16px;"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="4.93" x2="19.07" y2="7.76"></line></svg>
              <span id="ai-btn-text">Analizar Documento</span>
            </button>
          </div>
        </div>
      </div>

      <div id="ai-items-panel" class="hidden-collapse card mb-6" style="display:none;">
        <div class="section-header-flex">
          <h3 class="section-heading">Resultados de Extracción</h3>
          <button type="button" id="ai-clear-list" class="btn-text-danger">Descartar resultados</button>
        </div>
        <div class="table-wrap m-0">
          <table class="table">
            <thead>
              <tr>
                <th>#</th>
                <th>Producto</th>
                <th>Precio</th>
                <th>Marca / Modelo</th>
                <th>GTIN</th>
                <th class="text-right">Acción</th>
              </tr>
            </thead>
            <tbody id="ai-items-tbody"></tbody>
          </table>
        </div>
      </div>

      <nav class="pasos" id="pasos" aria-label="Pasos del registro"></nav>

      <div class="grid grid-main">
        <div class="col-left">
          <div class="card paso animate-enter" data-paso="info" data-titulo="Producto" data-activo style="--stagger: 2;">
            <h3 class="section-heading">Información Principal</h3>

            <div class="form-group">
              <label class="form-label">
                <span>Nombre del Producto <span class="req">*</span></span>
                <span class="ai-badge hidden">Sugerencia IA</span>
              </label>
              <input name="name" class="form-input text-lg font-bold" required placeholder="Ej. Bolígrafo Azul Bic Punta Fina 0.7mm" value="{{ old('name', $item->name ?? '') }}">
            </div>

            <div class="form-group">
              <label class="form-label">
                <span>Descripción Técnica</span>
                <span class="ai-badge hidden">Sugerencia IA</span>
              </label>
              <textarea name="description" class="form-input resize-y" style="min-height: 160px;" placeholder="Describe características técnicas, beneficios y contenido.">{{ old('description', $item->description ?? '') }}</textarea>
            </div>

            <div class="grid grid-2">
              <div class="form-group m-0">
                <label class="form-label">
                  <span>Slug (URL)</span>
                  <span class="ai-badge hidden">Sugerencia IA</span>
                </label>
                <input name="slug" class="form-input text-sm" placeholder="Se generará automáticamente" value="{{ old('slug', $item->slug ?? '') }}" readonly>
              </div>

              <div class="form-group m-0">
                <label class="form-label">
                  <span>Extracto Corto</span>
                  <span class="ai-badge hidden">Sugerencia IA</span>
                </label>
                <textarea name="excerpt" class="form-input resize-y" style="min-height: 50px;" rows="1" placeholder="Breve resumen de 1 línea">{{ old('excerpt', $item->excerpt ?? '') }}</textarea>
              </div>
            </div>
          </div>

          <div class="card paso animate-enter" data-paso="fotos" data-titulo="Fotos" style="--stagger: 3;">
            <div class="section-header-flex">
              <h3 class="section-heading">Multimedia <span class="req">*</span></h3>
              <span class="hint">JPG, PNG, WEBP (Máx. 5MB)</span>
            </div>

            <div class="grid grid-3">
              @foreach([1 => 'Frente (Fondo Blanco)', 2 => 'Ángulo / Empaque', 3 => 'Detalle / Etiqueta'] as $i => $label)
                @php
                  $hasPic = ${"has$i"};
                  $picField = "photo_{$i}_file";
                @endphp
                <div class="media-box {{ ($isEdit && $hasPic) ? 'has-media' : '' }}" data-photo-card="{{ $picField }}">
                  <label class="media-area" for="{{ $picField }}">
                    <div class="media-preview" id="photo_{{ $i }}_preview">
                      @if($isEdit && $hasPic)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($item->{"photo_$i"}) }}" alt="Foto {{ $i }}">
                      @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                      @endif
                    </div>
                    <div>
                      <span class="media-title" data-photo-strong="{{ $picField }}">Foto {{ $i }}</span>
                      <span class="media-subtitle" data-photo-sub="{{ $picField }}">{{ $label }}</span>
                    </div>
                  </label>
                  <input id="{{ $picField }}" name="{{ $picField }}" type="file" class="hidden-input" accept="image/*" @if(!$isEdit && $i==1) required @endif>
                  <button type="button" class="media-clear" data-photo-clear="{{ $picField }}" aria-label="Quitar foto">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                  </button>
                </div>
              @endforeach
            </div>
          </div>

          @if($isEdit)
            <div class="card paso animate-enter" data-paso="canales" data-titulo="Canales" style="--stagger: 7;">
              <h3 class="section-heading mb-6">Sincronización Multicanal</h3>
              <div class="grid grid-2 w-full">
                <div class="integration-panel">
                  <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-4">
                      <div class="channel-logo">
                        <img src="https://http2.mlstatic.com/frontend-assets/ml-web-navigation/ui-navigation/5.21.22/mercadolibre/logo__small.png" alt="ML">
                      </div>
                      <div>
                        <h4 class="m-0 font-bold text-base text-ink">Mercado Libre</h4>
                        <span class="text-xs font-bold text-success">Conectado</span>
                      </div>
                    </div>
                    <a href="{{ route('admin.catalog.meli.view', $item) }}" target="_blank" class="btn-icon" aria-label="Abrir en ML">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                    </a>
                  </div>
                  <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.catalog.meli.publish', $item) }}" class="flex-1 m-0">
                      @csrf
                      <button type="submit" class="btn-outline w-full justify-center">Sincronizar Listado</button>
                    </form>
                    <div class="flex gap-2">
                      <form method="POST" action="{{ route('admin.catalog.meli.pause', $item) }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn-icon-square" aria-label="Pausar">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>
                        </button>
                      </form>
                      <form method="POST" action="{{ route('admin.catalog.meli.activate', $item) }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn-icon-square" aria-label="Activar">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                        </button>
                      </form>
                    </div>
                  </div>
                </div>

                <div class="integration-panel relative">
                  @if(!$hasSku)
                    <div class="overlay-lock">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                      <p>SKU Requerido</p>
                    </div>
                  @endif
                  <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-4">
                      <div class="channel-logo bg-dark">
                        <svg viewBox="0 0 100 30" style="height:16px;">
                          <path d="M60.2 18.6c-2.4 1.8-5.8 2.8-9.4 2.8-6.6 0-11.9-3.4-15.6-8.9-.6-.9-1.8-1-2.6-.2l-2.7 2.7c-.8.8-.9 2-.2 2.9 4.8 6.7 11.9 10.9 20.3 10.9 4.9 0 9.7-1.4 13.9-4.2 1-.7 1.2-2 .5-2.9l-2-2.3c-.6-.7-1.5-.9-2.2-.4z" fill="#2563eb"/>
                          <path d="M84.2 22.4c-1.3-.7-2.9-1.2-4.6-1.5-2-.3-3.6-.9-4.7-1.6-1-.7-1.6-1.6-1.6-2.8 0-1.4.6-2.6 1.8-3.3 1.2-.7 2.8-1.1 4.7-1.1 1.9 0 3.6.4 4.8 1.1 1.1.7 1.8 1.8 1.9 3.2.1 1 .9 1.8 1.9 1.8h3.4c1.1 0 1.9-.9 1.8-2-.2-2.7-1.6-4.9-3.9-6.3-2.3-1.4-5.3-2.1-8.9-2.1-3.7 0-6.8.8-9.1 2.2-2.3 1.5-3.5 3.6-3.5 6.4 0 2.2.8 4 2.4 5.3 1.6 1.3 4 2.3 7 2.8 2.6.5 4.5 1.1 5.7 1.8 1.2.7 1.8 1.8 1.8 3 0 1.5-.7 2.8-2.1 3.6-1.4.8-3.2 1.2-5.4 1.2-2.2 0-4.2-.5-5.6-1.4-1.4-.9-2.3-2.3-2.5-4-.1-1-.9-1.8-1.9-1.8h-3.6c-1.1 0-1.9.9-1.8 2 .3 3.1 2 5.5 4.8 7 2.8 1.5 6.4 2.3 10.7 2.3 4.2 0 7.6-.8 10-2.3 2.5-1.5 3.8-3.7 3.8-6.5 0-2-.8-3.7-2.4-5z" fill="#111827"/>
                        </svg>
                      </div>
                      <div>
                        <h4 class="m-0 font-bold text-base text-ink">Amazon Seller</h4>
                        <span class="text-xs font-bold text-muted">SP-API V2</span>
                      </div>
                    </div>
                    <a href="{{ route('admin.catalog.amazon.view', $item) }}" target="_blank" class="btn-icon" @if(!$hasSku) style="pointer-events:none;" @endif>
                       <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                    </a>
                  </div>
                  <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.catalog.amazon.publish', $item) }}" class="flex-1 m-0">
                      @csrf
                      <button type="submit" class="btn-outline w-full justify-center" @disabled(!$hasSku)>Sincronizar Listado</button>
                    </form>
                    <div class="flex gap-2">
                      <form method="POST" action="{{ route('admin.catalog.amazon.pause', $item) }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn-icon-square" aria-label="Pausar" @disabled(!$hasSku)>
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>
                        </button>
                      </form>
                      <form method="POST" action="{{ route('admin.catalog.amazon.activate', $item) }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn-icon-square" aria-label="Activar" @disabled(!$hasSku)>
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                        </button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          @endif
        </div>

        <div class="col-right">
          <div class="card paso animate-enter" data-paso="precio" data-titulo="Precio y stock" style="--stagger: 4;">
            <h3 class="section-heading">Comercial</h3>

            <div class="grid grid-2 mb-4">
              <div class="form-group m-0">
                <label class="form-label">Precio Base <span class="req">*</span></label>
                <div class="input-icon-wrapper">
                  <span class="icon-left">$</span>
                  <input name="price" type="number" step="0.01" min="0" class="form-input with-icon text-lg font-bold" required value="{{ old('price', $item->price ?? 0) }}">
                </div>
              </div>

              <div class="form-group m-0">
                <label class="form-label"><span>Stock <span class="req">*</span></span> <span class="ai-badge hidden">IA</span></label>
                <input name="stock" type="number" step="1" min="0" class="form-input text-lg font-bold" required value="{{ old('stock', $item->stock ?? 0) }}">
              </div>
            </div>

            <div class="grid grid-2 mb-4">
              <div class="form-group m-0">
                <label class="form-label">Stock mínimo <span class="req">*</span></label>
                <input name="stock_min" type="number" step="1" min="0" class="form-input" required placeholder="Ej. 5" value="{{ old('stock_min', $item->stock_min ?? '') }}">
              </div>

              <div class="form-group m-0">
                <label class="form-label">Stock máximo <span class="req">*</span></label>
                <input name="stock_max" type="number" step="1" min="0" class="form-input" required placeholder="Ej. 100" value="{{ old('stock_max', $item->stock_max ?? '') }}">
              </div>
            </div>



            {{-- La "unidad de medida / contenido por unidad" se reemplazó por la sección
                 "Presentaciones de venta" (abajo). Se fija en pieza para el backend. --}}
            <input type="hidden" name="unit_measure" value="pieza">
            <input type="hidden" name="content_quantity" value="1">
            <input type="hidden" name="content_unit_measure" value="pieza">

            <div class="form-group mb-6">
              <label class="form-label">Precio Oferta (Opcional)</label>
              <div class="input-icon-wrapper">
                <span class="icon-left">$</span>
                <input name="sale_price" type="number" step="0.01" min="0" class="form-input with-icon" placeholder="0.00" value="{{ old('sale_price', $item->sale_price ?? '') }}">
              </div>
            </div>

            <div class="form-group mb-6">
              <div class="flex items-center justify-between mb-2">
                <label class="form-label m-0">Categoría <span class="req">*</span></label>
                <button type="button" class="btn-text" id="openCategoryPicker">Elegir categoría</button>
              </div>

              <input type="hidden" name="category_product_id" id="category_product_id" required value="{{ $currentCategoryId }}">

              <button type="button" class="category-display" id="selectedCategoryDisplay">
                <div class="category-display__main">
                  <div class="category-display__title" id="selectedCategoryTitle">
                    {{ $currentCategory?->name ?: 'Selecciona una categoría' }}
                  </div>
                  <div class="category-display__path" id="selectedCategoryPath">
                    {{ $currentCategory?->full_path ?: 'Haz clic para explorar el árbol de categorías.' }}
                  </div>
                </div>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-right"><polyline points="6 9 12 15 18 9"></polyline></svg>
              </button>

              <div class="flex justify-end mt-2">
                <button type="button" class="btn-text-danger text-xs" id="clearCategorySelection" @if(!$currentCategoryId) style="display:none;" @endif>
                  Quitar categoría
                </button>
              </div>
            </div>

            <div class="form-group mb-6">
              <label class="form-label">Ubicación principal</label>
              <select name="primary_location_id" class="form-select">
                <option value="">Sin ubicación asignada</option>
                @foreach($locations as $location)
                  <option value="{{ $location->id }}" @selected((string) $currentLocationId === (string) $location->id)>
                    {{ trim(($location->code ?? '') . ' · ' . ($location->name ?? '')) }}
                  </option>
                @endforeach
              </select>
              <span class="hint" style="display:block; margin-top:8px;">
                Aquí defines la ubicación principal del producto dentro del WMS.
              </span>
            </div>

            <div class="form-group m-0">
              <label class="form-label">Estado de Visibilidad <span class="req">*</span></label>
              <select name="status" class="form-select font-medium" required>
                @php $st = (string) old('status', isset($item) ? (string) $item->status : '1'); @endphp
                <option value="1" @selected($st === '1')>Publicado</option>
                <option value="0" @selected($st === '0')>Borrador</option>
                <option value="2" @selected($st === '2')>Privado (Solo Link)</option>
              </select>
            </div>
          </div>

          {{-- ====== MUESTRA ====== --}}
          <div class="card sample-card paso animate-enter" data-paso="muestra" data-titulo="Muestra" style="--stagger: 5;">
            <h3 class="section-heading">Muestra</h3>

            <div class="form-group {{ $isSample ? '' : 'm-0' }}">
              <label class="form-label sample-toggle">
                <input type="hidden" name="is_sample" value="0">
                <input type="checkbox" name="is_sample" id="isSampleCheck" value="1" @checked($isSample)>
                Este producto es una muestra (no se vende)
              </label>
              <span class="hint" style="display:block; margin-top:8px;">
                Las muestras no aparecen en el catálogo de venta ni se publican en Mercado Libre / Amazon.
              </span>
            </div>

            <div id="sampleFieldsWrap" class="@if(!$isSample) hidden @endif">
              <div class="form-group">
                <label class="form-label">Estado de la muestra</label>
                <select name="sample_status" id="sampleStatusField" class="form-select">
                  @foreach(\App\Models\CatalogItem::SAMPLE_STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected($currentSampleStatus === $value)>{{ $label }}</option>
                  @endforeach
                </select>
                <span class="hint" style="display:block; margin-top:8px;">
                  Al pasar a <strong>Prestada</strong> o <strong>Regalada</strong>, el stock baja 1 automáticamente. Si la regresas a <strong>Guardada</strong>, vuelve a subir.
                </span>
              </div>

              <div id="sampleHolderWrap" class="grid grid-2 m-0 @if(!in_array($currentSampleStatus, ['prestada','regalada'])) hidden @endif">
                <div class="form-group m-0">
                  <label class="form-label">Prestada / regalada a</label>
                  <input type="text" name="sample_holder" class="form-input" placeholder="Cliente o persona" value="{{ $currentSampleHolder }}">
                </div>
                <div class="form-group m-0">
                  <label class="form-label">Fecha de salida</label>
                  <input type="date" name="sample_out_at" class="form-input" value="{{ $currentSampleOutAt }}">
                </div>
              </div>
            </div>
          </div>

          <div class="card paso animate-enter" data-paso="ids" data-titulo="Identificadores" style="--stagger: 6;">
            <h3 class="section-heading">Identificadores</h3>

            <div class="form-group mb-5">
              <label class="form-label">SKU Interno / Código GTIN <span class="req">*</span></label>
              <input id="skuField" name="sku" class="form-input text-sm uppercase" required placeholder="Ej. 7501035910107" value="{{ old('sku', $item->sku ?? '') }}">
              <span class="hint" style="display:block; margin-top:8px;">Si capturas este campo, se copiará automáticamente al código GTIN/EAN.</span>
            </div>

            <div class="grid grid-2 gap-4 mb-5">
              <div class="form-group m-0">
                <label class="form-label"><span>Marca</span> <span class="ai-badge hidden">IA</span></label>
                <input name="brand_name" class="form-input" placeholder="Ej. Sony" value="{{ old('brand_name', $item->brand_name ?? '') }}">
              </div>
              <div class="form-group m-0">
                <label class="form-label"><span>Modelo</span> <span class="ai-badge hidden">IA</span></label>
                <input name="model_name" class="form-input" placeholder="Ej. PS5" value="{{ old('model_name', $item->model_name ?? '') }}">
              </div>
            </div>

            <div class="form-group m-0">
              <label class="form-label"><span>Código (GTIN/EAN) <span class="req">*</span></span> <span class="ai-badge hidden">IA</span></label>
              <input id="gtinField" name="meli_gtin" class="form-input" required style="letter-spacing: 1px;" placeholder="Ej. 7501035910107" value="{{ old('meli_gtin', $item->meli_gtin ?? '') }}">
              <span class="hint" style="display:block; margin-top:8px;">Este valor se sincroniza con SKU Interno para que ambos sean el mismo código.</span>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== Presentaciones de venta (cajas / paquetes) ===== --}}
      @php
        $existingPresentations = (isset($item) && $item)
            ? $item->barcodes()->where('units','>',1)->orderBy('units')->get()->map(fn($p)=>[
                'id'=>$p->id,'label'=>$p->label,'units'=>(int)$p->units,'barcode'=>$p->barcode,
                'price'=>$p->price!==null?(float)$p->price:null,
                'sale_price'=>$p->sale_price!==null?(float)$p->sale_price:null,
                'is_sellable_web'=>(bool)$p->is_sellable_web,
              ])->values()->all()
            : [];
      @endphp

      <div class="card paso animate-enter" data-paso="cajas" data-titulo="Presentaciones" style="--stagger: 6;">
        <div class="flex" style="justify-content:space-between;align-items:center;margin-bottom:6px;">
          <h3 class="section-heading" style="margin:0;">Presentaciones de venta (cajas / paquetes)</h3>
          <button type="button" id="btnAddPresentation" class="btn-ghost">+ Agregar caja</button>
        </div>
        <p class="hint" style="margin:0 0 14px;">
          La <b>pieza</b> se vende al Precio Base de arriba. Aquí agregas cajas o paquetes
          (ej. “Caja con 99”) con su propio precio y código de barras. El stock se cuenta
          siempre en piezas: vender 1 caja descuenta sus piezas.
        </p>

        <input type="hidden" name="presentations_json" id="presentations_json" value="">
        <div id="presentationsEmpty" class="hint" style="padding:4px 0;">Sin presentaciones extra. Este producto solo se vende por pieza.</div>
        <div id="presentationsList" style="display:flex;flex-direction:column;gap:12px;"></div>
      </div>

      <script>
      (function(){
        const LIST=document.getElementById('presentationsList');
        const EMPTY=document.getElementById('presentationsEmpty');
        const HIDDEN=document.getElementById('presentations_json');
        const BTN=document.getElementById('btnAddPresentation');
        const FORM=document.getElementById('catalogItemForm');
        if(!LIST||!FORM) return;
        const INITIAL=@json($existingPresentations);
        const esc=s=>String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
        function tpl(p){p=p||{};return `
          <div class="pres-row" style="border:1px solid #e5e7eb;border-radius:12px;padding:12px;background:#f8fafc;">
            <input type="hidden" data-f="id" value="${esc(p.id??'')}">
            <div style="display:grid;grid-template-columns:1.4fr .7fr 1.1fr .8fr .8fr auto;gap:10px;align-items:end;">
              <label style="display:block;"><span style="font-size:.72rem;color:#64748b;">Etiqueta</span><input data-f="label" class="form-input" placeholder="Caja con 99" value="${esc(p.label??'')}"></label>
              <label style="display:block;"><span style="font-size:.72rem;color:#64748b;">Piezas</span><input data-f="units" type="number" min="2" step="1" class="form-input" placeholder="99" value="${esc(p.units??'')}"></label>
              <label style="display:block;"><span style="font-size:.72rem;color:#64748b;">Código de barras</span><input data-f="barcode" class="form-input" placeholder="192838721" value="${esc(p.barcode??'')}"></label>
              <label style="display:block;"><span style="font-size:.72rem;color:#64748b;">Precio</span><input data-f="price" type="number" min="0" step="0.01" class="form-input" placeholder="0.00" value="${p.price??''}"></label>
              <label style="display:block;"><span style="font-size:.72rem;color:#64748b;">Oferta</span><input data-f="sale_price" type="number" min="0" step="0.01" class="form-input" placeholder="—" value="${p.sale_price??''}"></label>
              <button type="button" class="pres-remove btn-ghost" title="Quitar" style="height:38px;">✕</button>
            </div>
            <label style="display:inline-flex;align-items:center;gap:6px;margin-top:8px;font-size:.8rem;color:#475569;cursor:pointer;">
              <input data-f="is_sellable_web" type="checkbox" ${(p.is_sellable_web??true)?'checked':''}> Vender esta presentación en la tienda web
            </label>
          </div>`;}
        function refresh(){EMPTY.style.display=LIST.children.length?'none':'block';}
        function addRow(p){const w=document.createElement('div');w.innerHTML=tpl(p).trim();const n=w.firstElementChild;n.querySelector('.pres-remove').addEventListener('click',()=>{n.remove();refresh();});LIST.appendChild(n);refresh();}
        function serialize(){const rows=[];LIST.querySelectorAll('.pres-row').forEach(row=>{const g=f=>row.querySelector(`[data-f="${f}"]`);const units=parseInt(g('units').value||'0',10);const barcode=g('barcode').value.trim();if(units<2&&!barcode)return;rows.push({id:g('id').value||null,label:g('label').value.trim(),units:units,barcode:barcode,price:g('price').value!==''?parseFloat(g('price').value):null,sale_price:g('sale_price').value!==''?parseFloat(g('sale_price').value):null,is_sellable_web:g('is_sellable_web').checked});});HIDDEN.value=JSON.stringify(rows);}
        BTN&&BTN.addEventListener('click',()=>addRow({is_sellable_web:true}));
        FORM.addEventListener('submit',serialize);
        (INITIAL||[]).forEach(addRow);refresh();
        serialize(); // deja el campo oculto poblado desde el inicio (defensa extra contra borrado accidental)
      })();
      </script>

      <div class="paso-nav" id="pasoNav">
        <span class="cuenta" id="pasoCuenta"></span>
        <button type="button" class="btn-ghost" id="pasoAtras">← Atrás</button>
        <button type="button" class="btn-outline" id="pasoContinuar">Continuar →</button>
      </div>

    </form>
  </div>
</div>

<div class="modal-backdrop" id="mlCategoryModal">
  <div class="modal">
    <div class="modal-header">
      <div>
        <h3 class="modal-title">Seleccionar categoría</h3>
        <p class="modal-subtitle">Explora el árbol de categorías o usa el buscador.</p>
      </div>
      <button type="button" class="modal-close" id="closeCategoryPicker" aria-label="Cerrar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <div class="modal-breadcrumb" id="mlcatBreadcrumb">
      <span class="text-muted">Cargando navegación...</span>
    </div>

    <div class="modal-body">
      <div class="search-wrapper">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <input type="text" id="mlcatSearch" placeholder="Buscar categoría en este nivel...">
      </div>

      <div class="category-levels" id="mlcatLevels"></div>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn-text" id="mlcatAddBtn">+ Crear nueva categoría aquí</button>
      <div class="flex gap-4">
        <button type="button" class="btn-ghost" id="mlcatCancelBtn">Cancelar</button>
        <button type="button" class="btn-primary" id="mlcatUseBtn" disabled>Usar selección</button>
      </div>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="mlCategoryCreateModal">
  <div class="modal" style="max-width:520px;">
    <div class="modal-header">
      <div>
        <h3 class="modal-title">Nueva categoría</h3>
        <p class="modal-subtitle" id="mlcatCreateHint">Se creará dentro del nivel actual.</p>
      </div>
      <button type="button" class="modal-close" id="closeCreateCategoryModal" aria-label="Cerrar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Nombre</label>
        <input type="text" id="mlcatCreateName" class="form-input" placeholder="Ej. Portaminas">
      </div>

      <div class="form-group">
        <label class="form-label">Orden</label>
        <input type="number" id="mlcatCreateSort" class="form-input" min="0" value="0">
        <span class="hint" style="display:block; margin-top:6px;">Calculado automáticamente según los elementos actuales.</span>
      </div>

      <div class="form-group m-0">
        <label class="form-label">Referencias (Opcional)</label>
        <div id="mlcatRefContainer" class="flex flex-col gap-2">
        </div>
        <button type="button" class="btn-text btn-sm mt-3" id="mlcatAddRefBtn">
          + Agregar referencia
        </button>
      </div>

      <div class="alert-error mt-4" id="mlcatCreateError" style="display:none;"></div>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn-ghost" id="cancelCreateCategoryModal">Cancelar</button>
      <button type="button" class="btn-primary" id="saveCreateCategoryBtn">
        <span id="saveCreateCategoryText">Guardar categoría</span>
      </button>
    </div>
  </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>


  function syncContentFields() {
    const unitMeasure = document.querySelector('[name="unit_measure"]');
    const contentWrap = document.getElementById('contentFieldsWrap');
    const contentQuantity = document.getElementById('contentQuantityField');
    const contentUnit = document.getElementById('contentUnitMeasureField');

    if (!unitMeasure || !contentWrap || !contentQuantity || !contentUnit) return;

    const value = String(unitMeasure.value || 'pieza').toLowerCase();

    if (value === 'pieza') {
      contentQuantity.value = 1;
      contentUnit.value = 'pieza';
      contentQuantity.setAttribute('readonly', 'readonly');
      contentUnit.setAttribute('disabled', 'disabled');
      contentWrap.style.opacity = '0.55';
    } else {
      contentQuantity.removeAttribute('readonly');
      contentUnit.removeAttribute('disabled');
      contentWrap.style.opacity = '1';

      if (!contentQuantity.value || Number(contentQuantity.value) < 1) {
        contentQuantity.value = 1;
      }

      if (!contentUnit.value) {
        contentUnit.value = 'pieza';
      }
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    const unitMeasure = document.querySelector('[name="unit_measure"]');
    const form = document.getElementById('catalogItemForm');
    const contentUnit = document.getElementById('contentUnitMeasureField');

    unitMeasure?.addEventListener('change', syncContentFields);
    syncContentFields();

    form?.addEventListener('submit', () => {
      contentUnit?.removeAttribute('disabled');
    });
  });


  // ====== MUESTRA: mostrar/ocultar campos ======
  document.addEventListener('DOMContentLoaded', () => {
    const check = document.getElementById('isSampleCheck');
    const wrap = document.getElementById('sampleFieldsWrap');
    const statusSel = document.getElementById('sampleStatusField');
    const holderWrap = document.getElementById('sampleHolderWrap');

    const syncSample = () => {
      if (!check || !wrap) return;
      wrap.classList.toggle('hidden', !check.checked);
      if (statusSel && holderWrap) {
        const out = ['prestada', 'regalada'].includes(statusSel.value);
        holderWrap.classList.toggle('hidden', !(check.checked && out));
      }
    };

    check?.addEventListener('change', syncSample);
    statusSel?.addEventListener('change', syncSample);
    syncSample();
  });
  function syncSkuAndGtinFields() {
    const sku = document.getElementById('skuField') || document.querySelector('[name="sku"]');
    const gtin = document.getElementById('gtinField') || document.querySelector('[name="meli_gtin"]');
    if (!sku || !gtin) return;

    let syncing = false;
    const copyValue = (from, to) => {
      if (syncing) return;
      syncing = true;
      to.value = String(from.value || '').trim();
      syncing = false;
    };

    sku.addEventListener('input', () => copyValue(sku, gtin));
    gtin.addEventListener('input', () => copyValue(gtin, sku));

    const initial = String(sku.value || gtin.value || '').trim();
    if (initial) {
      sku.value = initial;
      gtin.value = initial;
    }
  }

  document.addEventListener('DOMContentLoaded', syncSkuAndGtinFields);

  const UI = {
    toast: { fire: (o) => window.showToast && window.showToast(o.title || o.text || '', (o.icon === 'error' || o.icon === 'warning') ? 'error' : 'ok') },
    success: (msg) => window.showToast && window.showToast(msg, 'ok'),
    error: (msg) => window.showToast && window.showToast(msg, 'error'),
    escape: (str) => String(str || '').replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m])),
    money: (v) => isNaN(Number(v)) ? '—' : `$${Number(v).toFixed(2)}`
  };

  const tabManual = document.getElementById('tabManual');
  const tabAi = document.getElementById('tabAi');
  const panelManual = document.getElementById('panelManual');
  const panelAi = document.getElementById('panelAi');
  const modeLabel = document.getElementById('modeLabel');

  function setMode(mode){
    const isAi = mode === 'ai';
    if(panelAi) panelAi.style.display = isAi ? 'block' : 'none';
    if(panelManual) panelManual.style.display = isAi ? 'none' : 'block';

    if(isAi && panelAi) { panelAi.classList.remove('fade-in-up'); void panelAi.offsetWidth; panelAi.classList.add('fade-in-up'); }
    else if(panelManual) { panelManual.classList.remove('fade-in-up'); void panelManual.offsetWidth; panelManual.classList.add('fade-in-up'); }

    if(tabAi) tabAi.classList.toggle('active', isAi);
    if(tabManual) tabManual.classList.toggle('active', !isAi);

    if(modeLabel) modeLabel.innerHTML = isAi
      ? `<span class="dot active"></span> Modo actual: Captura IA`
      : `<span class="dot active"></span> Modo actual: Manual`;
  }
  if(tabManual) tabManual.onclick = ()=>setMode('manual');
  if(tabAi) tabAi.onclick = ()=>setMode('ai');

  let intakeId = null;
  let pollTimer = null;
  let extractedCache = null;

  const btnAiStart   = document.getElementById('btnAiStart');
  const qrWrap       = document.getElementById('qrWrap');
  const qrBox        = document.getElementById('qrBox');
  const mobileUrlA   = document.getElementById('mobileUrl');

  const aiStatusText = document.getElementById('aiStatusText');
  const aiStatusHint = document.getElementById('aiStatusHint');
  const qrMiniStatus = document.getElementById('qrMiniStatus');

  const aiWaiting    = document.getElementById('aiWaiting');
  const aiResult     = document.getElementById('aiResult');

  const exSupplier   = document.getElementById('exSupplier');
  const exFolio      = document.getElementById('exFolio');
  const exDate       = document.getElementById('exDate');
  const exTotal      = document.getElementById('exTotal');
  const aiItemsTbody = document.getElementById('aiItemsTbody');

  const stMap = {
    0:{txt:'Conectado', hint:'Esperando a que subas fotos desde tu celular...'},
    1:{txt:'Fotos recibidas', hint:'Iniciando el motor de reconocimiento...'},
    2:{txt:'Procesando con IA', hint:'Extrayendo tabla de productos y montos...'},
    3:{txt:'Completado', hint:'Datos extraídos exitosamente. Selecciona un ítem.'},
    4:{txt:'Confirmado', hint:'Esta captura ya fue aplicada anteriormente.'},
    9:{txt:'Error', hint:'No se pudo analizar el documento. Intenta nuevamente.'},
  };

  function setTimelineActive(status){
    document.querySelectorAll('.timeline-ai__item').forEach(el=>{
      const st = parseInt(el.getAttribute('data-st'));
      el.classList.toggle('active', st <= status);
    });
  }

  function setStatusUI(status, meta){
    const st = stMap[status] || {txt:String(status), hint:''};
    aiStatusText.textContent = st.txt;
    aiStatusHint.textContent = (meta && meta.error) ? meta.error : st.hint;

    if(qrMiniStatus){
      const bg = status === 9 ? 'var(--danger)' : 'var(--blue)';
      qrMiniStatus.innerHTML = `<span class="dot" style="background:${bg}"></span> ${st.txt}`;
    }
    setTimelineActive(status);
  }

  btnAiStart?.addEventListener('click', async ()=>{
    btnAiStart.disabled = true;
    btnAiStart.innerHTML = `<svg class="spinner" style="width:16px;height:16px;" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="30 30" stroke-linecap="round"/></svg> Generando...`;

    try{
      const source_type = document.getElementById('aiSourceType').value;
      const res = await fetch(`{{ route('admin.catalog.ai.start') }}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' },
        body: JSON.stringify({ source_type })
      });

      const j = await res.json();
      if(!j.ok) throw new Error(j.error || 'No se pudo iniciar la IA');

      intakeId = j.intake_id;
      qrWrap.style.display = 'block';
      qrWrap.classList.add('fade-in-up');
      qrBox.innerHTML = '';

      new QRCode(qrBox, { text: j.mobile_url, width: 200, height: 200, colorDark: "#0f172a", colorLight: "#ffffff" });

      mobileUrlA.href = j.mobile_url;
      mobileUrlA.textContent = "Abrir enlace manual";

      setStatusUI(0);
      aiWaiting.style.display = 'block';
      aiResult.style.display = 'none';

      extractedCache = null;
      aiItemsTbody.innerHTML = '';
      exSupplier.textContent = '—'; exFolio.textContent = '—'; exDate.textContent = '—'; exTotal.textContent = '—';

      if(pollTimer) clearInterval(pollTimer);
      pollTimer = setInterval(pollStatus, 2200);

    }catch(e){
      alert(e.message || 'Error de conexión');
    }finally{
      btnAiStart.disabled = false;
      btnAiStart.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3z"/><path d="M20 14h1v1h-1z"/><path d="M14 20h7"/></svg> Generar QR`;
    }
  });

  async function pollStatus(){
    if(!intakeId) return;
    const res = await fetch(`/admin/catalog/ai/${intakeId}/status`, { headers:{'X-Requested-With':'XMLHttpRequest'} });
    const j = await res.json();

    setStatusUI(j.status, j.meta);

    if (j.status >= 1 && j.status < 3) {
      const qrCard = document.querySelector('.qr-card');
      if (qrCard && qrCard.style.display !== 'none') {
        qrCard.style.opacity = '0';
        setTimeout(() => qrCard.style.display = 'none', 250);
      }
      aiWaiting.style.display = 'block';
    }

    if(j.status === 3){
      clearInterval(pollTimer);
      extractedCache = j.extracted || {};
      renderExtracted(extractedCache);
    }

    if(j.status === 9){
      clearInterval(pollTimer);
      aiWaiting.innerHTML = `<div style="font-weight:900;color:var(--danger);text-align:center;">Fallo en la extracción de la IA. Intenta de nuevo.</div>`;
    }
  }

  function renderExtracted(ex){
    aiWaiting.style.display = 'none';
    aiResult.style.display = 'block';

    exSupplier.textContent = ex.supplier_name || '—';
    exFolio.textContent    = ex.folio || '—';
    exDate.textContent     = ex.invoice_date || '—';
    exTotal.textContent    = (ex.total ? `$${ex.total}` : '—');

    const items = Array.isArray(ex.items) ? ex.items : [];
    aiItemsTbody.innerHTML = '';

    items.forEach((it, idx)=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><span style="font-family:ui-monospace, monospace; color:var(--muted);">${escapeHtml(it.sku || '—')}</span></td>
        <td style="white-space:normal; min-width:200px;">${escapeHtml(it.description || '—')}</td>
        <td>${escapeHtml(it.quantity ?? '—')}</td>
        <td>${escapeHtml(it.unit || '—')}</td>
        <td>${escapeHtml(it.unit_price ? `$${it.unit_price}` : '—')}</td>
        <td style="font-weight:800; color:var(--blue);">${escapeHtml(it.line_total ? `$${it.line_total}` : '—')}</td>
        <td class="text-right">
          <button type="button" class="btn-text btn-sm" data-use="${idx}">Seleccionar</button>
        </td>
      `;
      aiItemsTbody.appendChild(tr);
    });

    aiItemsTbody.querySelectorAll('button[data-use]').forEach(btn=>{
      btn.onclick = ()=>{
        const i = parseInt(btn.getAttribute('data-use'));
        fillFormFromItem(items[i], ex);
        setMode('manual');
      };
    });

    const btnBackManual = document.getElementById('btnBackManual');
    if(btnBackManual) btnBackManual.onclick = ()=>setMode('manual');
  }

  const btnFillFirst = document.getElementById('btnFillFirst');
  if(btnFillFirst){
    btnFillFirst.onclick = ()=>{
      const items = (extractedCache && Array.isArray(extractedCache.items)) ? extractedCache.items : [];
      if(!items.length) return alert('No hay ítems detectados.');
      fillFormFromItem(items[0], extractedCache);
      setMode('manual');
    };
  }

  function fillFormFromItem(it, ex){
    if(!it) return;

    const setVal = (name, val, mark=true)=>{
      const el = document.querySelector(`[name="${name}"]`);
      if(!el) return;
      if(val === undefined || val === null || val === '') return;
      el.value = val;
      if(mark){
        el.classList.add('ai-suggested-input');
        setTimeout(()=> el.classList.remove('ai-suggested-input'), 3000);
        const badge = el.closest('.form-group')?.querySelector('.ai-badge');
        if (badge) { badge.classList.remove('hidden'); setTimeout(() => badge.classList.add('hidden'), 4500); }
      }
    };

    const desc  = (it.description || '').trim();
    const brand = (it.brand || it.brand_name || '').trim();
    const model = (it.model || it.model_name || '').trim();

    let finalName = desc || 'PRODUCTO SIN NOMBRE';
    if(brand && !finalName.toLowerCase().includes(brand.toLowerCase())) finalName += ' ' + brand;
    if(model && !finalName.toLowerCase().includes(model.toLowerCase())) finalName += ' ' + model;

    setVal('name', finalName);
    setVal('sku', it.sku || '');
    setVal('price', it.unit_price ?? it.price ?? 0);
    setVal('brand_name', brand);
    setVal('model_name', model);
    setVal('excerpt', desc ? desc.slice(0, 160) : '');

    const gtin = it.gtin || it.ean || it.upc || it.barcode || it.codigo_barras || it.sku || '';
    setVal('meli_gtin', gtin);
    if (gtin) setVal('sku', gtin);

    const qty = it.quantity ?? it.qty ?? it.cantidad ?? null;
    setVal('stock', qty);

    const unitRaw = String(it.unit || it.unit_measure || it.unidad || '').toLowerCase().trim();
    const unitMap = {
      'pz': 'pieza',
      'pza': 'pieza',
      'pieza': 'pieza',
      'piezas': 'pieza',
      'caja': 'caja',
      'cajas': 'caja',
      'paquete': 'paquete',
      'paquetes': 'paquete',
      'rollo': 'rollo',
      'rollos': 'rollo',
      'juego': 'juego',
      'kit': 'kit',
      'bolsa': 'bolsa',
      'par': 'par',
      'set': 'set',
      'display': 'display',
      'docena': 'docena',
      'metro': 'metro',
      'litro': 'litro'
    };
    setVal('unit_measure', unitMap[unitRaw] || 'pieza');

    const extra = ex || extractedCache || {};
    let longDesc = '';
    if(extra.supplier_name) longDesc += `Proveedor: ${extra.supplier_name}\n`;
    if(extra.folio)         longDesc += `Folio: ${extra.folio}\n`;
    if(extra.invoice_date)  longDesc += `Fecha Documento: ${extra.invoice_date}\n\n`;

    longDesc += `Descripción Original:\n${desc || '—'}\n\n`;
    longDesc += `Cantidad: ${qty ?? '—'} ${it.unit || ''}\n`;
    longDesc += `Precio unitario: ${it.unit_price ?? '—'}\n`;
    longDesc += `Total línea: ${it.line_total ?? '—'}`;

    const dEl = document.querySelector('[name="description"]');
    if(dEl){
      dEl.value = longDesc;
      dEl.classList.add('ai-suggested-input');
      setTimeout(()=> dEl.classList.remove('ai-suggested-input'), 3000);
      const badge = dEl.closest('.form-group')?.querySelector('.ai-badge');
      if (badge) { badge.classList.remove('hidden'); setTimeout(() => badge.classList.add('hidden'), 4500); }
    }

    window.scrollTo({top:0, behavior:'smooth'});
  }

  function escapeHtml(str){
    if(str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
  }

  document.addEventListener('DOMContentLoaded', () => {
    ['1','2','3'].forEach(i => {
      const inp = document.getElementById(`photo_${i}_file`);
      const box = document.querySelector(`[data-photo-card="photo_${i}_file"]`);
      const prev = document.getElementById(`photo_${i}_preview`);
      const text = document.querySelector(`[data-photo-strong="photo_${i}_file"]`);
      const clrBtn = document.querySelector(`[data-photo-clear="photo_${i}_file"]`);
      let objUrl = null;

      const updateUI = (hasFile, file = null) => {
        box?.classList.toggle('has-media', hasFile);
        if(text) text.textContent = file ? file.name : `Foto ${i}`;
      };

      inp?.addEventListener('change', e => {
        const file = e.target.files[0];
        updateUI(!!file, file);
        if(objUrl) URL.revokeObjectURL(objUrl);
        if(!file) {
          prev.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>';
          return;
        }
        objUrl = URL.createObjectURL(file);
        prev.innerHTML = `<img src="${objUrl}" class="fade-in">`;
      });

      clrBtn?.addEventListener('click', e => {
        e.preventDefault();
        if(inp) inp.value = '';
        updateUI(false);
        if(prev) prev.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>';
      });
    });
  });

  document.addEventListener('DOMContentLoaded', () => {
    const els = {
      dropzone: document.getElementById('ai-dropzone'),
      input: document.getElementById('ai_files'),
      list: document.getElementById('ai-files-list'),
      btnAnalyze: document.getElementById('btn-ai-analyze'),
      spinner: document.getElementById('ai-spinner'),
      btnText: document.getElementById('ai-btn-text'),
      dropText: document.getElementById('ai-drop-text'),
      panel: document.getElementById('ai-items-panel'),
      tbody: document.getElementById('ai-items-tbody'),
      btnClear: document.getElementById('ai-clear-list')
    };
    if(!els.dropzone) return;

    let aiState = JSON.parse(localStorage.getItem('cat_ai') || '{"items":[]}');
    const saveState = () => localStorage.setItem('cat_ai', JSON.stringify(aiState));

    const renderFiles = (files) => {
      els.list.innerHTML = '';
      if(!files.length) { els.dropText.textContent = 'Arrastra o clic para cargar'; return; }
      els.dropText.textContent = `${files.length} archivo(s)`;
      Array.from(files).forEach(f => els.list.insertAdjacentHTML('beforeend', `<span>${f.name}</span>`));
    };

    els.input.addEventListener('change', () => renderFiles(els.input.files));

    ['dragenter','dragover'].forEach(e => els.dropzone.addEventListener(e, ev => {
      ev.preventDefault(); els.dropzone.classList.add('is-dragover');
    }));
    ['dragleave','dragend','drop'].forEach(e => els.dropzone.addEventListener(e, ev => {
      ev.preventDefault(); els.dropzone.classList.remove('is-dragover');
    }));

    els.dropzone.addEventListener('drop', e => {
      const dt = new DataTransfer();
      Array.from(e.dataTransfer.files).filter(f => f.type.match(/image.*|pdf/)).forEach(f => dt.items.add(f));
      if(dt.files.length) { els.input.files = dt.files; renderFiles(dt.files); }
    });

    const renderTable = () => {
      els.tbody.innerHTML = '';
      if(!aiState.items.length) { els.panel.style.display = 'none'; return; }

      aiState.items.forEach((item, i) => {
        const brandModel = [item.brand_name, item.model_name].filter(Boolean).join(' / ') || '—';
        els.tbody.insertAdjacentHTML('beforeend', `
          <tr class="fade-in">
            <td>${i+1}</td>
            <td style="font-weight:800;">${UI.escape(item.name) || 'Sin título'}</td>
            <td>${UI.money(item.price)}</td>
            <td>${brandModel}</td>
            <td><span style="font-family:ui-monospace,monospace; background:var(--soft); padding:4px 10px; border-radius:999px; border:1px solid var(--line);">${UI.escape(item.meli_gtin) || '—'}</span></td>
            <td class="text-right"><button type="button" class="btn-text btn-use" data-idx="${i}">Usar datos</button></td>
          </tr>
        `);
      });

      els.panel.style.display = 'block';

      document.querySelectorAll('.btn-use').forEach(btn => {
        btn.addEventListener('click', e => {
          fillFormFromItem(aiState.items[e.target.dataset.idx], null);
          UI.success('Datos transferidos');
        });
      });
    };

    els.btnAnalyze.addEventListener('click', async () => {
      if(!els.input.files.length) return UI.toast.fire({ icon: 'info', title: 'Agrega un archivo' });

      const fd = new FormData();
      Array.from(els.input.files).forEach(f => fd.append('files[]', f));
      fd.append('_token', '{{ csrf_token() }}');

      els.btnAnalyze.disabled = true;
      els.spinner.classList.remove('hidden');
      els.btnText.textContent = 'Procesando...';
      els.panel.style.display = 'none';

      try {
        const res = await fetch("{{ route('admin.catalog.ai-from-upload') }}", { method: 'POST', body: fd });
        const data = await res.json();
        if(data.error) throw new Error(data.error);

        if(data.suggestions) fillFormFromItem(data.suggestions, null);
        aiState.items = Array.isArray(data.items) ? data.items : [];
        saveState(); renderTable();
        UI.success('Análisis exitoso');
      } catch (e) {
        UI.error(e.message);
      } finally {
        els.btnAnalyze.disabled = false;
        els.spinner.classList.add('hidden');
        els.btnText.textContent = 'Analizar Documento';
      }
    });

    els.btnClear.addEventListener('click', () => {
      aiState.items = []; saveState(); renderTable();
      els.input.value = ''; els.list.innerHTML = '';
      els.dropText.textContent = 'Arrastra o clic para cargar';
    });

    if(aiState.items.length) { renderTable(); }
  });

  document.addEventListener('DOMContentLoaded', () => {
    const api = {
      roots: @json(route('admin.category-products.roots')),
      children: @json(route('admin.category-products.children', ['category' => '__ID__'])),
      show: @json(route('admin.category-products.show-json', ['category' => '__ID__'])),
      store: @json(route('admin.category-products.store')),
    };

    const openBtn = document.getElementById('openCategoryPicker');
    const closeBtn = document.getElementById('closeCategoryPicker');
    const cancelBtn = document.getElementById('mlcatCancelBtn');
    const modal = document.getElementById('mlCategoryModal');

    const createModal = document.getElementById('mlCategoryCreateModal');
    const closeCreateBtn = document.getElementById('closeCreateCategoryModal');
    const cancelCreateBtn = document.getElementById('cancelCreateCategoryModal');
    const saveCreateBtn = document.getElementById('saveCreateCategoryBtn');
    const saveCreateText = document.getElementById('saveCreateCategoryText');

    const levelsBox = document.getElementById('mlcatLevels');
    const breadcrumbBox = document.getElementById('mlcatBreadcrumb');
    const useBtn = document.getElementById('mlcatUseBtn');
    const addBtn = document.getElementById('mlcatAddBtn');
    const searchInput = document.getElementById('mlcatSearch');

    const selectedIdInput = document.getElementById('category_product_id');
    const selectedTitle = document.getElementById('selectedCategoryTitle');
    const selectedPath = document.getElementById('selectedCategoryPath');
    const selectedDisplay = document.getElementById('selectedCategoryDisplay');
    const clearBtn = document.getElementById('clearCategorySelection');

    const createName = document.getElementById('mlcatCreateName');
    const createSort = document.getElementById('mlcatCreateSort');
    const createError = document.getElementById('mlcatCreateError');
    const createHint = document.getElementById('mlcatCreateHint');

    const refContainer = document.getElementById('mlcatRefContainer');
    const addRefBtn = document.getElementById('mlcatAddRefBtn');

    if (!openBtn || !modal) return;

    let path = [];
    let levels = [];
    let selectedFinal = null;

    function childrenUrl(id) { return api.children.replace('__ID__', id); }
    function showUrl(id) { return api.show.replace('__ID__', id); }

    function openModal(el) { el.classList.add('show'); document.body.style.overflow = 'hidden'; }
    function closeModal(el) { el.classList.remove('show'); if (!modal.classList.contains('show') && !createModal.classList.contains('show')) document.body.style.overflow = ''; }

    function createReferenceRow(value = '') {
      const row = document.createElement('div');
      row.className = 'flex gap-2 items-center animate-enter';
      row.style.setProperty('--stagger', '1');
      row.innerHTML = `
        <input type="text" class="form-input flex-1 ref-input" placeholder="Ej. Código, ID externo..." value="${value}">
        <button type="button" class="btn-icon-square remove-ref" aria-label="Quitar">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--danger); width: 18px; height: 18px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      `;
      row.querySelector('.remove-ref').addEventListener('click', () => row.remove());
      refContainer.appendChild(row);
    }

    addRefBtn?.addEventListener('click', () => createReferenceRow());

    if(searchInput) {
      searchInput.addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        document.querySelectorAll('.mlcat-option').forEach(el => {
            const text = el.querySelector('.mlcat-option__name').textContent.toLowerCase();
            el.style.display = text.includes(term) ? 'flex' : 'none';
        });
      });
    }

    function renderBreadcrumb() {
      let html = `<span class="mlcat-crumb clickable-crumb" data-jump="-1">Inicio</span>`;

      if (path.length > 0) {
        html += ` <span class="mlcat-sep">/</span> ` + path.map((item, index) => {
          const isLast = index === path.length - 1;
          const sep = !isLast ? ' <span class="mlcat-sep">/</span> ' : '';
          return isLast
              ? `<span class="text-ink font-bold">${UI.escape(item.name)}</span>`
              : `<span class="mlcat-crumb clickable-crumb" data-jump="${index}">${UI.escape(item.name)}</span>${sep}`;
        }).join('');
      }

      breadcrumbBox.innerHTML = html;

      document.querySelectorAll('.clickable-crumb').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            const jumpIdx = Number(e.currentTarget.dataset.jump);
            if (jumpIdx === -1) {
                await loadRoots();
            } else {
                const diff = (path.length - 1) - jumpIdx;
                for (let i = 0; i < diff; i++) {
                    path.pop();
                    levels.pop();
                }
                selectedFinal = null;
                renderBreadcrumb();
                renderLevels();
            }
        });
      });
    }

    function renderLevels() {
      if(searchInput) searchInput.value = '';

      if (!levels.length) { levelsBox.innerHTML = `<div class="mlcat-empty">No hay categorías.</div>`; return; }

      const currentLevelIndex = levels.length - 1;
      const level = levels[currentLevelIndex];

      const optionsHtml = level.items.length
        ? level.items.map(item => {
            const active = selectedFinal && Number(selectedFinal.id) === Number(item.id);
            return `
              <button type="button" class="mlcat-option ${active ? 'active' : ''}" data-level-index="${currentLevelIndex}" data-id="${item.id}">
                <div style="flex:1;">
                  <div class="mlcat-option__name">${UI.escape(item.name)}</div>
                </div>
                ${item.has_children
                    ? `<div style="display:flex; align-items:center; gap:8px;"><span class="mlcat-badge">${item.children_count || 0} sub</span> <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;color:var(--muted);"><polyline points="9 18 15 12 9 6"></polyline></svg></div>`
                    : `<span class="mlcat-badge" style="background:var(--success-soft);color:var(--success);border:1px solid rgba(5,150,105,0.2);">Seleccionable</span>`}
              </button>
            `;
          }).join('')
        : `<div class="mlcat-empty">No hay subcategorías aquí.</div>`;

      levelsBox.innerHTML = `
        <div class="mlcat-level animate-enter" style="border:none;">
          <div class="mlcat-options" style="padding:0;">${optionsHtml}</div>
        </div>
      `;

      document.querySelectorAll('.mlcat-option').forEach(btn => {
        btn.addEventListener('click', async () => { await handlePick(Number(btn.dataset.levelIndex), Number(btn.dataset.id)); });
      });

      useBtn.disabled = !selectedFinal;
    }

    async function fetchJson(url, options = {}) {
      const { headers: extraHeaders = {}, ...rest } = options;
      const response = await fetch(url, {
        ...rest,
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          ...extraHeaders
        }
      });

      const contentType = response.headers.get('content-type') || '';
      if (!contentType.includes('application/json')) {
        await response.text();
        if (response.status === 419 || response.status === 401) throw new Error('Tu sesión expiró. Recarga la página e inicia sesión de nuevo.');
        throw new Error(`El servidor devolvió HTML (no JSON), código ${response.status}.`);
      }

      const data = await response.json();
      if (!response.ok || data.ok === false) throw new Error(data.message || data.error || 'Error de conexión.');
      return data;
    }

    async function loadRoots() {
      try {
        const data = await fetchJson(api.roots);
        levels = [{ title: 'Categorías Principales', items: data.items || [] }];
        path = []; selectedFinal = null;
        renderBreadcrumb(); renderLevels();
      } catch (e) { UI.error(e.message || 'Error al cargar categorías'); }
    }

    async function loadCurrentSelection() {
      const currentId = selectedIdInput.value;
      if (!currentId) { await loadRoots(); return; }

      try {
        const data = await fetchJson(showUrl(currentId));
        const breadcrumb = data.breadcrumb || [];
        path = breadcrumb.map(item => ({ id: item.id, name: item.name, full_path: item.full_path || item.name }));
        selectedFinal = data.item || null;

        levels = [];
        const rootsData = await fetchJson(api.roots);
        levels.push({ title: 'Categorías Principales', items: rootsData.items || [] });

        for (let i = 0; i < path.length; i++) {
          const current = path[i];
          const childData = await fetchJson(childrenUrl(current.id));
          if ((childData.items || []).length) levels.push({ title: `Nivel ${i + 2}`, items: childData.items || [] });
        }
        renderBreadcrumb();
        renderLevels();
      } catch (e) { await loadRoots(); }
    }

    async function handlePick(levelIndex, id) {
      levels = levels.slice(0, levelIndex + 1);
      path = path.slice(0, levelIndex);

      const picked = levels[levelIndex].items.find(item => Number(item.id) === Number(id));
      if (!picked) return;

      path.push({ id: picked.id, name: picked.name, full_path: picked.full_path || picked.name });

      if (picked.has_children) {
        const data = await fetchJson(childrenUrl(id));
        levels.push({ title: `Nivel ${levelIndex + 2}`, items: data.items || [] });
        selectedFinal = null;
      } else {
        const data = await fetchJson(showUrl(id));
        selectedFinal = data.item || picked;
      }

      renderBreadcrumb();
      renderLevels();
    }

    function applySelectedCategory() {
      if (!selectedFinal) return;
      selectedIdInput.value = selectedFinal.id;
      selectedTitle.textContent = selectedFinal.name;
      selectedPath.textContent = selectedFinal.full_path || selectedFinal.name;
      clearBtn.style.display = 'inline-block';
      closeModal(modal);
    }

    function clearSelectedCategory() {
      selectedIdInput.value = '';
      selectedTitle.textContent = 'Selecciona una categoría';
      selectedPath.textContent = 'Haz clic para explorar el árbol de categorías.';
      clearBtn.style.display = 'none';
    }

    function openCreateCategory() {
      const parent = path.length ? path[path.length - 1] : null;
      createHint.textContent = parent ? `Se creará dentro de: ${parent.name}` : 'Se creará como categoría principal.';
      createName.value = '';
      createError.style.display = 'none';

      const currentLevelItems = levels.length ? levels[levels.length - 1].items : [];
      createSort.value = currentLevelItems.length;

      refContainer.innerHTML = '';
      createReferenceRow();

      openModal(createModal);
      setTimeout(() => createName.focus(), 100);
    }

    async function saveCategory() {
      const name = (createName.value || '').trim();
      const sort_order = Number(createSort.value || 0);
      const parent = path.length ? path[path.length - 1] : null;

      const references = Array.from(document.querySelectorAll('.ref-input'))
                              .map(inp => inp.value.trim())
                              .filter(val => val !== '');

      if (!name) { createError.textContent = 'El nombre es obligatorio.'; createError.style.display = 'block'; createName.focus(); return; }

      saveCreateBtn.disabled = true; saveCreateText.textContent = 'Guardando...';

      try {
        const data = await fetchJson(api.store, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
          body: JSON.stringify({ parent_id: parent ? parent.id : null, name, sort_order, references })
        });

        closeModal(createModal);
        UI.success('Categoría creada');

        if (parent) {
          const childrenData = await fetchJson(childrenUrl(parent.id));
          levels[levels.length - 1].items = childrenData.items || [];
          if (data.item?.id) await handlePick(levels.length - 1, data.item.id); else renderLevels();
        } else {
          await loadRoots();
          if (data.item?.id) await handlePick(0, data.item.id);
        }
      } catch (e) {
        createError.textContent = e.message || 'No se pudo guardar.'; createError.style.display = 'block';
      } finally {
        saveCreateBtn.disabled = false; saveCreateText.textContent = 'Guardar categoría';
      }
    }

    openBtn.addEventListener('click', async () => { openModal(modal); await loadCurrentSelection(); });
    selectedDisplay.addEventListener('click', async () => { openModal(modal); await loadCurrentSelection(); });
    closeBtn.addEventListener('click', () => closeModal(modal));
    cancelBtn.addEventListener('click', () => closeModal(modal));
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(modal); });
    useBtn.addEventListener('click', applySelectedCategory);
    clearBtn.addEventListener('click', clearSelectedCategory);

    addBtn.addEventListener('click', openCreateCategory);
    closeCreateBtn.addEventListener('click', () => closeModal(createModal));
    cancelCreateBtn.addEventListener('click', () => closeModal(createModal));
    createModal.addEventListener('click', (e) => { if (e.target === createModal) closeModal(createModal); });
    saveCreateBtn.addEventListener('click', saveCategory);

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        if (createModal.classList.contains('show')) closeModal(createModal);
        else if (modal.classList.contains('show')) closeModal(modal);
      }
    });
  });
</script>

@if(session('ok'))
<script>
  document.addEventListener('DOMContentLoaded', () => {
    localStorage.removeItem('cat_ai');
    // La confirmación la muestra el toast (_toast) automáticamente.
  });
</script>
@endif
@endpush

@push('scripts')
  @include('partials.ui-tour')
  <script>
    UITour.registrar('producto-form', {
      version: 2,
      auto: false,
      pasos: [
        { el: '#pasos', titulo: 'Se llena por pasos',
          texto: 'El registro está dividido en pasos para que sea rápido. Puedes hacer clic en cualquier paso para saltar; el actual se marca en azul y los que ya están listos quedan iluminados.' },
        { el: '#toggleAiCopilot', titulo: 'Rellenar con IA (opcional)',
          texto: 'Si tienes la factura o remisión, actívalo, arrastra el archivo y la IA llena los datos por ti. Si no, llena a mano.' },
        { el: '#pasoContinuar', titulo: 'Avanza seguro',
          texto: 'Con Continuar pasas al siguiente paso. Si falta un dato obligatorio, te lo avisa aquí mismo antes de seguir.' },
        { el: '.sticky-footer', titulo: 'Guardar',
          texto: 'Cuando todo esté completo, pulsa Registrar producto. Si algo quedó incompleto, te lleva directo al paso que falta.' },
      ],
    });
  </script>

  <script>
  (function(){
    const form = document.getElementById('catalogItemForm');
    const nav  = document.getElementById('pasos');
    if(!form || !nav) return;
    const pasos = Array.from(form.querySelectorAll('.card.paso'));
    if(!pasos.length) return;

    const btnAtras = document.getElementById('pasoAtras');
    const btnCont  = document.getElementById('pasoContinuar');
    const cuenta   = document.getElementById('pasoCuenta');

    let idx = pasos.findIndex(p => p.hasAttribute('data-activo'));
    if(idx < 0) idx = 0;

    // ---- Validación: qué campos obligatorios faltan en un paso ----
    function labelDe(el){
      if(el.name === 'category_product_id') return 'Categoría';
      if(el.closest('[data-photo-card]')) return 'Foto principal';
      const g = el.closest('.form-group');
      let t = g && g.querySelector('.form-label');
      t = t ? t.textContent : '';
      return (t || el.getAttribute('placeholder') || el.name || 'Campo').replace(/[*]/g, '').trim();
    }
    function faltantesDe(paso){
      const out = [];
      paso.querySelectorAll('[required]').forEach(el => {
        if(el.disabled) return;
        if(el.type === 'file'){
          const card = el.closest('[data-photo-card]');
          const tiene = (el.files && el.files.length) || (card && card.classList.contains('has-media'));
          if(!tiene) out.push({ el, label: labelDe(el) });
        } else if(!String(el.value || '').trim()){
          out.push({ el, label: labelDe(el) });
        }
      });
      return out;
    }
    function limpiarFaltan(paso){
      const b = paso.querySelector('.paso-faltan'); if(b) b.remove();
      paso.querySelectorAll('.campo-error').forEach(e => e.classList.remove('campo-error'));
    }
    function pintarFaltan(paso, lista){
      limpiarFaltan(paso);
      const box = document.createElement('div');
      box.className = 'paso-faltan';
      box.innerHTML = '<b>Falta completar en este paso:</b><ul>' + lista.map(f => '<li>' + f.label + '</li>').join('') + '</ul>';
      paso.insertBefore(box, paso.firstChild);
      lista.forEach(f => f.el && f.el.classList && f.el.classList.add('campo-error'));
    }

    // ---- Barra de pasos ----
    nav.innerHTML = '';
    pasos.forEach((p, i) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'paso-chip';
      b.innerHTML = '<span class="n">' + (i + 1) + '</span><span class="txt">' + (p.dataset.titulo || ('Paso ' + (i + 1))) + '</span>';
      b.addEventListener('click', () => ir(i));
      nav.appendChild(b);
    });
    const chips = Array.from(nav.children);

    const visitados = new Set();
    function estadoChips(){
      chips.forEach((c, i) => {
        if(i === idx){ c.dataset.estado = 'actual'; return; }
        if(!visitados.has(i)){ c.dataset.estado = ''; return; }   // aún no lo ves → neutro
        c.dataset.estado = faltantesDe(pasos[i]).length ? 'falta' : 'listo';
      });
    }
    function ir(n){
      idx = Math.max(0, Math.min(pasos.length - 1, n));
      visitados.add(idx);
      pasos.forEach((p, i) => i === idx ? p.setAttribute('data-activo','') : p.removeAttribute('data-activo'));
      estadoChips();
      if(btnAtras) btnAtras.style.visibility = idx === 0 ? 'hidden' : 'visible';
      if(btnCont)  btnCont.textContent = idx === pasos.length - 1 ? 'Terminar ✓' : 'Continuar →';
      if(cuenta)   cuenta.textContent = 'Paso ' + (idx + 1) + ' de ' + pasos.length;
      // No saltar a lo alto en cada paso: solo acomoda si la barra de pasos
      // quedó fuera de la vista (arriba o muy abajo).
      const ref = (nav || form);
      const r = ref.getBoundingClientRect();
      const margen = 90;
      if (r.top < margen || r.top > window.innerHeight - 120) {
        window.scrollTo({ top: r.top + window.scrollY - margen, behavior: 'smooth' });
      }
    }

    // Al pulsar un campo con error, se limpia su marca.
    form.addEventListener('input', e => { if(e.target.classList) e.target.classList.remove('campo-error'); });

    btnAtras && btnAtras.addEventListener('click', () => ir(idx - 1));
    btnCont  && btnCont.addEventListener('click', () => {
      const f = faltantesDe(pasos[idx]);
      if(f.length){ pintarFaltan(pasos[idx], f); chips[idx].dataset.estado = 'falta'; f[0].el.focus && f[0].el.focus(); return; }
      limpiarFaltan(pasos[idx]);
      if(idx < pasos.length - 1){
        ir(idx + 1);
      } else {
        // Último paso: "Terminar" guarda (dispara la validación de todos los pasos y envía).
        if(typeof form.requestSubmit === 'function') form.requestSubmit();
        else form.submit();
      }
    });

    // Al registrar: revisa TODOS los pasos; si falta algo, no envía y salta ahí.
    form.addEventListener('submit', function(e){
      for(let i = 0; i < pasos.length; i++){
        const f = faltantesDe(pasos[i]);
        if(f.length){
          e.preventDefault();
          ir(i);
          pintarFaltan(pasos[i], f);
          chips[i].dataset.estado = 'falta';
          f[0].el.focus && f[0].el.focus();
          return;
        }
      }
    });

    ir(idx);
  })();
  </script>

  <script>
  (function(){
    const tg = document.getElementById('toggleAiCopilot');
    const cop = document.getElementById('aiCopilot');
    if(!tg || !cop) return;
    tg.addEventListener('click', () => {
      const oculto = getComputedStyle(cop).display === 'none';
      cop.style.display = oculto ? 'block' : 'none';
      tg.classList.toggle('is-on', oculto);
      if(oculto) cop.scrollIntoView({ behavior:'smooth', block:'nearest' });
    });
  })();
  </script>
@endpush
@endsection