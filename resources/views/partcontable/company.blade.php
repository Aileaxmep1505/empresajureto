{{-- resources/views/partcontable/company.blade.php --}}
@extends('layouts.app')
@section('title', $company->name.' - Parte contable')

@php
  use Illuminate\Support\Str;
  use Illuminate\Support\Facades\Storage;

  // ============================
  //   Configuración de tabs UI
  // ============================
  $pcTabs = [
    'declaracion_anual' => [
      'label'   => 'Declaración Anual',
      'subtabs' => [
        'acuse_anual'       => 'Acuse anual',
        'pago_anual'        => 'Pago anual',
        'declaracion_anual' => 'Declaración anual',
      ],
    ],
    'declaracion_mensual' => [
      'label'   => 'Declaración Mensual',
      'subtabs' => [
        'acuse_mensual'       => 'Acuse mensual',
        'pago_mensual'        => 'Pago mensual',
        'declaracion_mensual' => 'Declaración mensual',
      ],
    ],
    'constancias' => [
      'label'   => 'Constancias / Opiniones',
      'subtabs' => [
        'csf'            => 'Constancia de situación fiscal',
        'opinion_nl'     => 'Opinión estatal Nuevo León',
        'opinion_edomex' => 'Opinión estatal Estado de México',
        '32d_sat'        => '32-D SAT',
        'infonavit'      => 'INFONAVIT',
        'opinion_imss'   => 'Opinión IMSS',
      ],
    ],
    'estados_financieros' => [
      'label'   => 'Estados Financieros',
      'subtabs' => [
        'balance_general'   => 'Balance general',
        'estado_resultados' => 'Estado de resultados',
      ],
    ],
    'isn_3' => [
      'label'   => 'ISN-3%',
      'subtabs' => [
        'pago' => 'Pago',
      ],
    ],
  ];

  $year  = $year  ?? request('year');
  $month = $month ?? request('month');

  $currentSectionKey = $currentSectionKey ?? request('section', 'declaracion_anual');
  if (!isset($pcTabs[$currentSectionKey])) $currentSectionKey = 'declaracion_anual';
  $currentSubtabs = $pcTabs[$currentSectionKey]['subtabs'];

  $currentSubKey = $currentSubKey ?? request('subtipo', array_key_first($currentSubtabs));
  if (!isset($currentSubtabs[$currentSubKey])) $currentSubKey = array_key_first($currentSubtabs);

  $currentSubLabel = $currentSubLabel ?? $currentSubtabs[$currentSubKey];

  $welcomeSessionKey = "pc_welcome_{$company->id}";
  $welcomeData       = session($welcomeSessionKey);
  $userName          = auth()->user()->name ?? 'Usuario';
  $welcomeCloseKey   = "pc_welcome_closed_{$company->id}";

  // ===========================================
  //  Acceso restringido a Estados Financieros
  // ===========================================
  $financialUserIds = [2, 12, 18];
  $canSeeFinancial  = auth()->check() && in_array((int) auth()->id(), $financialUserIds, true);

  if (!$canSeeFinancial) {
    unset($pcTabs['estados_financieros']);

    if ($currentSectionKey === 'estados_financieros') {
      $currentSectionKey = 'declaracion_anual';
      $currentSubtabs    = $pcTabs[$currentSectionKey]['subtabs'];
      $currentSubKey     = array_key_first($currentSubtabs);
      $currentSubLabel   = $currentSubtabs[$currentSubKey];
    }
  }

  $ficticioAllowedSections = ['declaracion_anual', 'declaracion_mensual'];
  $ficticioAllowedSubtypes = [
    'acuse_anual','pago_anual','declaracion_anual',
    'acuse_mensual','pago_mensual','declaracion_mensual',
  ];

  $docsCount    = method_exists($documents, 'total') ? $documents->total() : $documents->count();
  $sevenDaysAgo = now()->subDays(7);
@endphp

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
    --purple:       #7c3aed;
    --purple-soft:  #ede9fe;
    --r:            12px;
  }

  *, *::before, *::after { box-sizing: border-box; }

  body {
    background: var(--bg);
    color: var(--ink2);
    font-family: 'Quicksand', system-ui, sans-serif;
    font-weight: 500;
    line-height: 1.6;
    min-height: 100vh;
  }

  /* ── Animaciones ── */
  @keyframes fadeUp { from { opacity:0; transform:translateY(20px);} to { opacity:1; transform:translateY(0);} }
  @keyframes backdropIn { from { background:rgba(0,0,0,0); backdrop-filter:blur(0);} to { background:rgba(0,0,0,.6); backdrop-filter:blur(12px);} }
  @keyframes modalSlideUp { from { opacity:0; transform:translateY(28px) scale(.97);} to { opacity:1; transform:translateY(0) scale(1);} }
  @keyframes toastIn  { from { opacity:0; transform:translateX(120%);} to { opacity:1; transform:translateX(0);} }
  @keyframes toastOut { from { opacity:1; transform:translateX(0);} to { opacity:0; transform:translateX(120%);} }
  @keyframes badgePulse { 0%,100% {opacity:1;} 50% {opacity:.6;} }
  @keyframes pcSpin { to { transform: rotate(360deg); } }
  @keyframes progressBar { from { width: 100%; } to { width: 0%; } }

  .au { animation: fadeUp .5s cubic-bezier(.22,1,.36,1) both; }
  .d1 { animation-delay: .06s; }
  .d2 { animation-delay: .13s; }
  .d3 { animation-delay: .20s; }

  /* ── Wrap ── */
  .pc-wrap {
    width: 100%;
    max-width: 100%;
    margin: 0;
    padding: 32px 40px 80px;
  }

  /* ── Header ── */
  .pc-header { display: flex; flex-direction: column; gap: 14px; margin-bottom: 28px; padding-bottom: 24px; border-bottom: 1px solid var(--line); }
  .pc-header-top { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
  .pc-header-top .pc-eyebrow { margin: 0; }
  .pc-back-link {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: .82rem; font-weight: 600; color: var(--muted);
    text-decoration: none; margin: 0;
    padding: 7px 14px; border-radius: 999px;
    background: var(--card); border: 1px solid var(--line);
    transition: all .18s; flex-shrink: 0;
  }
  .pc-back-link:hover { color: var(--blue); border-color: var(--blue); background: var(--blue-soft); transform: translateX(-2px); }

  .pc-header-main { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; flex-wrap: wrap; }
  .pc-header-main > div:first-child { min-width: 0; flex: 1 1 480px; }
  .pc-eyebrow { font-size: .72rem; font-weight: 700; letter-spacing: .18em; text-transform: uppercase; color: var(--blue); }
  .pc-title   { font-size: clamp(1.7rem, 3.2vw, 2.6rem); font-weight: 700; letter-spacing: -0.03em; color: var(--ink); line-height: 1.1; margin: 0; }
  .pc-subtitle{ margin-top: 8px; color: var(--muted); font-size: .92rem; max-width: 640px; line-height: 1.5; }
  .pc-counter { margin-top: 8px; font-size: .82rem; color: var(--muted); font-weight: 600; }
  .pc-counter strong { color: var(--blue); }
  .pc-header-actions { display: flex; gap: 10px; flex-shrink: 0; flex-wrap: wrap; }

  /* ── Botones ── */
  .btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 22px; border-radius: 999px;
    font-family: 'Quicksand', sans-serif; font-weight: 700; font-size: .88rem;
    border: none; cursor: pointer; text-decoration: none;
    transition: all .18s ease; white-space: nowrap;
  }
  .btn:active { transform: scale(.98); }
  .btn svg    { width: 16px; height: 16px; flex-shrink: 0; }
  .btn-primary { background: var(--blue); color: #fff; box-shadow: 0 4px 14px rgba(0,122,255,.2); }
  .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(0,122,255,.28); }
  .btn-ghost  { background: transparent; color: #555; border: 1px solid var(--line); }
  .btn-ghost:hover { background: #f4f4f4; }
  .btn-outline { background: #fff; color: var(--blue); border: 1px solid var(--blue); }
  .btn-outline:hover { background: var(--blue-soft); }
  .btn-danger-solid { background: var(--danger); color: #fff; box-shadow: 0 4px 14px rgba(255,74,74,.2); }
  .btn-danger-solid:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(255,74,74,.3); }

  /* ── Welcome ── */
  .pc-welcome {
    display: flex; align-items: center; justify-content: space-between;
    background: linear-gradient(135deg, var(--blue-soft) 0%, #f0f7ff 100%);
    border: 1px solid #c7dcfd; border-radius: 14px;
    padding: 14px 18px; margin-bottom: 18px; gap: 12px;
    animation: fadeUp .4s ease both;
  }
  .pc-welcome-title { font-size: .92rem; font-weight: 700; color: var(--ink); }
  .pc-welcome-user  { color: var(--blue); }
  .pc-welcome-sub   { font-size: .78rem; color: var(--muted); margin-top: 2px; }
  .pc-welcome-close {
    width: 28px; height: 28px; border-radius: 8px; border: none; background: rgba(255,255,255,.6);
    color: var(--muted); cursor: pointer; font-size: 16px; line-height: 1;
    transition: all .18s; flex-shrink: 0;
  }
  .pc-welcome-close:hover { background: #fff; color: var(--ink); }

  /* ── Flash ── */
  .pc-flash { padding: 12px 16px; border-radius: var(--r); margin-bottom: 14px; font-size: .9rem; font-weight: 600; display: none; }

  /* ── Tabs principales ── */
  .pc-tabs {
    display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 12px;
    padding: 6px; background: var(--card); border: 1px solid var(--line);
    border-radius: 16px; overflow-x: auto;
  }
  .pc-tab-item {
    padding: 9px 16px; border-radius: 10px; font-size: .85rem; font-weight: 700;
    color: var(--muted); text-decoration: none; transition: all .18s;
    white-space: nowrap; flex-shrink: 0;
  }
  .pc-tab-item:hover { color: var(--ink); background: var(--bg); }
  .pc-tab-item.active { background: var(--blue); color: #fff; box-shadow: 0 4px 12px rgba(0,122,255,.22); }

  /* ── Subtabs ── */
  .pc-subtabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; padding-bottom: 4px; overflow-x: auto; }
  .pc-subtab-item {
    padding: 7px 14px; border-radius: 999px; font-size: .8rem; font-weight: 600;
    color: var(--ink2); background: var(--card); border: 1px solid var(--line);
    text-decoration: none; transition: all .18s; white-space: nowrap;
  }
  .pc-subtab-item:hover { border-color: var(--blue); color: var(--blue); background: var(--blue-soft); }
  .pc-subtab-item.active { background: var(--blue-soft); color: var(--blue); border-color: #b6d6ff; box-shadow: 0 2px 8px rgba(0,122,255,.08); }

  /* ── Toolbar ── */
  .toolbar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 22px; }
  .toolbar-search { flex: 1 1 220px; position: relative; min-width: 180px; }
  .toolbar-search svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; stroke: var(--muted); fill: none; stroke-width: 2; stroke-linecap: round; }
  .search-input {
    width: 100%; height: 40px; background: var(--card);
    border: 1px solid var(--line); border-radius: 999px;
    padding: 0 14px 0 36px;
    font-family: 'Quicksand', sans-serif; font-size: .88rem; font-weight: 500;
    color: var(--ink2); outline: none; transition: border-color .2s, box-shadow .2s;
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
  }
  .sort-select:focus { border-color: var(--blue); }

  .view-toggle { display: flex; gap: 4px; background: var(--card); border: 1px solid var(--line); border-radius: 999px; padding: 4px; }
  .toggle-btn { width: 32px; height: 32px; border-radius: 999px; border: none; background: transparent; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--muted); transition: all .18s; }
  .toggle-btn svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
  .toggle-btn.active { background: var(--blue); color: #fff; }
  .toggle-btn:hover:not(.active) { background: var(--blue-soft); color: var(--blue); }

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
  }
  .doc-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,.08); border-color: #d0d0d0; }
  .doc-card:active { transform: scale(.99); }
  .doc-card .card__link { position: absolute; inset: 0; z-index: 1; border-radius: 16px; }

  /* ── Hover preview tooltip (estilo Estados Financieros) ── */
  .doc-card .pdf-hover-preview {
    position: absolute; bottom: calc(100% + 12px); left: 50%;
    transform: translateX(-50%) scale(0.92);
    width: 240px; height: 170px;
    background: #fff; border: 1px solid var(--line);
    border-radius: 12px; overflow: hidden;
    box-shadow: 0 16px 48px rgba(0,0,0,.18);
    opacity: 0; pointer-events: none;
    transition: opacity .2s ease, transform .2s ease;
    z-index: 50;
  }
  .doc-card:hover .pdf-hover-preview { opacity: 1; transform: translateX(-50%) scale(1); }
  .pdf-hover-preview iframe,
  .pdf-hover-preview img,
  .pdf-hover-preview video {
    width: 100%; height: 100%; border: none; object-fit: cover;
    pointer-events: none; display: block;
    background: #f3f4f6;
  }
  .pdf-hover-preview::after {
    content: '';
    position: absolute; bottom: -7px; left: 50%;
    width: 14px; height: 14px; background: #fff;
    border-right: 1px solid var(--line); border-bottom: 1px solid var(--line);
    transform: translateX(-50%) rotate(45deg);
  }

  /* ── Badge "Nuevo" ── */
  .badge-new {
    position: absolute; top: -8px; right: 14px;
    background: var(--blue); color: #fff;
    font-size: .62rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
    padding: 3px 9px; border-radius: 999px;
    animation: badgePulse 2s ease infinite;
    box-shadow: 0 2px 8px rgba(0,122,255,.35);
    z-index: 4;
  }

  .doc-card-body { padding: 22px 22px 0; flex: 1; display: flex; flex-direction: column; gap: 12px; position: relative; z-index: 2; pointer-events: none; }
  .doc-card-body * { pointer-events: auto; }

  .card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; position: relative; z-index: 3; }
  .card-badges { display: flex; gap: 6px; flex-wrap: wrap; }
  .doc-pill {
    display: inline-block; font-size: .62rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase;
    padding: 3px 8px; border-radius: 999px;
  }
  .doc-pill-pdf      { background: var(--danger-soft); color: var(--danger); }
  .doc-pill-img      { background: #f0f7ff; color: var(--blue); }
  .doc-pill-video    { background: var(--purple-soft); color: var(--purple); }
  .doc-pill-other    { background: #f3f4f6; color: #6b7280; }
  .doc-pill-ficticio { background: var(--warning-soft); color: var(--warning); }

  .card-top-actions { display: flex; gap: 6px; position: relative; z-index: 3; }

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
  .doc-pdf-icon span { font-size: .55rem; font-weight: 700; letter-spacing: .04em; color: var(--danger); text-transform: uppercase; }
  .doc-pdf-icon svg  { width: 16px; height: 16px; stroke: var(--danger); fill: none; stroke-width: 1.6; }
  .doc-pdf-icon.is-img      { background: var(--blue-soft); border-color: #b6d6ff; }
  .doc-pdf-icon.is-img::before { border-left-color: #b6d6ff; border-bottom-color: #b6d6ff; }
  .doc-pdf-icon.is-img span, .doc-pdf-icon.is-img svg { color: var(--blue); stroke: var(--blue); }
  .doc-pdf-icon.is-video    { background: var(--purple-soft); border-color: #ddd0fa; }
  .doc-pdf-icon.is-video::before { border-left-color: #ddd0fa; border-bottom-color: #ddd0fa; }
  .doc-pdf-icon.is-video span, .doc-pdf-icon.is-video svg { color: var(--purple); stroke: var(--purple); }
  .doc-pdf-icon.is-other    { background: #f3f4f6; border-color: #e5e7eb; }
  .doc-pdf-icon.is-other::before { border-left-color: #e5e7eb; border-bottom-color: #e5e7eb; }
  .doc-pdf-icon.is-other span, .doc-pdf-icon.is-other svg { color: #6b7280; stroke: #6b7280; }

  .doc-meta { flex: 1; min-width: 0; }
  .doc-title { font-size: .98rem; font-weight: 700; color: var(--ink); line-height: 1.3; word-break: break-word; }
  .doc-subtype { font-size: .75rem; color: var(--muted); margin-top: 2px; }

  .doc-thumb {
    width: 100%; aspect-ratio: 16/9; border-radius: 10px; overflow: hidden;
    background: #f3f4f6; border: 1px solid var(--line);
    display: flex; align-items: center; justify-content: center;
  }
  .doc-thumb img, .doc-thumb video { width: 100%; height: 100%; object-fit: cover; display: block; }

  .doc-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 22px; margin-top: 14px; border-top: 1px solid var(--line);
    gap: 8px; position: relative; z-index: 3;
  }
  .doc-info { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
  .doc-date     { font-size: .78rem; color: var(--ink2); font-weight: 600; }
  .doc-uploader { font-size: .73rem; color: var(--muted); }

  .doc-actions { display: flex; gap: 6px; flex-shrink: 0; }
  .btn-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: inline-flex; align-items: center; justify-content: center;
    border: 1px solid var(--line); background: #fff;
    cursor: pointer; transition: all .18s; text-decoration: none; color: var(--muted); flex-shrink: 0;
    position: relative;
  }
  .btn-icon svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 1.7; stroke-linecap: round; stroke-linejoin: round; }
  .btn-icon:hover         { border-color: var(--blue);   color: var(--blue);   background: var(--blue-soft); transform: translateY(-1px); }
  .btn-icon.danger:hover  { border-color: var(--danger); color: var(--danger); background: var(--danger-soft); transform: translateY(-1px); }
  .btn-icon.warning       { color: var(--warning); }
  .btn-icon.warning:hover { border-color: var(--warning); color: var(--warning); background: var(--warning-soft); transform: translateY(-1px); }
  .btn-icon:active { transform: scale(.95); }

  .btn-icon .pc-ic-default { display: inline-flex; }
  .btn-icon .pc-ic-spin    { display: none; width: 14px; height: 14px; align-items: center; justify-content: center; }
  .btn-icon.is-downloading { pointer-events: none; opacity: .78; }
  .btn-icon.is-downloading .pc-ic-default { display: none; }
  .btn-icon.is-downloading .pc-ic-spin    { display: inline-flex; animation: pcSpin .85s linear infinite; }

  /* ── LIST VIEW ── */
  .doc-list { display: none; flex-direction: column; gap: 8px; }
  .doc-list-item {
    background: var(--card); border: 1px solid var(--line); border-radius: 12px;
    padding: 14px 18px; display: flex; align-items: center; gap: 14px;
    cursor: pointer; transition: all .18s; position: relative;
  }
  .doc-list-item:hover { border-color: #d0d0d0; box-shadow: 0 4px 16px rgba(0,0,0,.06); transform: translateX(2px); }
  .doc-list-item:active { transform: scale(.995); }
  .doc-list-item .card__link { position: absolute; inset: 0; border-radius: 12px; z-index: 1; }
  .list-mini-icon {
    width: 32px; height: 38px; border-radius: 5px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; position: relative;
    background: var(--danger-soft); border: 1px solid #fecaca;
    z-index: 2;
  }
  .list-mini-icon.is-img      { background: var(--blue-soft); border-color: #b6d6ff; }
  .list-mini-icon.is-video    { background: var(--purple-soft); border-color: #ddd0fa; }
  .list-mini-icon.is-other    { background: #f3f4f6; border-color: #e5e7eb; }
  .list-mini-icon svg { width: 13px; height: 13px; stroke: var(--danger); fill: none; stroke-width: 1.8; }
  .list-mini-icon.is-img svg   { stroke: var(--blue); }
  .list-mini-icon.is-video svg { stroke: var(--purple); }
  .list-mini-icon.is-other svg { stroke: #6b7280; }

  .list-info { flex: 1; min-width: 0; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; z-index: 2; position: relative; }
  .list-title { font-size: .92rem; font-weight: 700; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 280px; }
  .list-pill { font-size: .62rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; padding: 2px 8px; border-radius: 999px; background: var(--blue-soft); color: var(--blue); white-space: nowrap; }
  .list-date    { font-size: .75rem; color: var(--muted); white-space: nowrap; }
  .list-actions { display: flex; gap: 6px; flex-shrink: 0; z-index: 3; position: relative; }

  /* ── Empty / no results ── */
  .empty-state { text-align: center; padding: 80px 24px; border: 1.5px dashed var(--line); border-radius: 16px; background: var(--card); }
  .empty-icon-wrap { width: 52px; height: 52px; border-radius: 14px; background: var(--blue-soft); margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; }
  .empty-icon-wrap svg { width: 24px; height: 24px; stroke: var(--blue); fill: none; stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; }
  .empty-state h3 { font-size: 1.2rem; font-weight: 700; color: var(--ink); margin-bottom: 6px; }
  .empty-state p  { color: var(--muted); font-size: .9rem; margin-bottom: 16px; }

  .no-results-local { display: none; text-align: center; padding: 48px 24px; color: var(--muted); font-weight: 600; }

  /* ── Modals ── */
  .modal-overlay { display: none; position: fixed; inset: 0; z-index: 200; align-items: center; justify-content: center; padding: 20px; }
  .modal-overlay.open { display: flex; }
  .modal-backdrop { position: fixed; inset: 0; z-index: 0; background: rgba(0,0,0,.6); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); animation: backdropIn .3s ease both; }
  .modal-content-wrap { position: relative; z-index: 1; width: 100%; display: flex; justify-content: center; }

  .modal-upload {
    background: var(--card); border: 1px solid var(--line); border-radius: 20px;
    width: 100%; max-width: 540px; padding: 40px; position: relative;
    box-shadow: 0 24px 64px rgba(0,0,0,.18); max-height: 92vh; overflow-y: auto;
    animation: modalSlideUp .3s cubic-bezier(.22,1,.36,1) both;
  }
  .modal-eyebrow { font-size: .7rem; font-weight: 700; letter-spacing: .15em; text-transform: uppercase; color: var(--blue); margin-bottom: 6px; }
  .modal-title   { font-size: 1.5rem; font-weight: 700; color: var(--ink); margin-bottom: 24px; line-height: 1.15; letter-spacing: -0.02em; }
  .modal-close {
    position: absolute; top: 18px; right: 18px; width: 30px; height: 30px; border-radius: 8px;
    border: 1px solid var(--line); background: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center; color: var(--muted); transition: all .18s;
  }
  .modal-close:hover { background: #f4f4f4; color: var(--ink); border-color: #ccc; }
  .modal-close svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2.2; stroke-linecap: round; }

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

  .btn-submit {
    width: 100%; padding: 13px; border-radius: 999px; background: var(--blue); color: #fff;
    font-family: 'Quicksand', sans-serif; font-weight: 700; font-size: .95rem; border: none;
    cursor: pointer; margin-top: 16px; transition: all .2s; box-shadow: 0 4px 14px rgba(0,122,255,.22);
  }
  .btn-submit:hover  { transform: translateY(-1px); box-shadow: 0 8px 22px rgba(0,122,255,.3); }
  .btn-submit:active { transform: scale(.98); }

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
  .confirm-title { font-size: 1.25rem; font-weight: 700; color: var(--ink); margin-bottom: 8px; }
  .confirm-desc  { font-size: .88rem; color: var(--muted); line-height: 1.6; margin-bottom: 6px; }
  .confirm-docname { font-size: .9rem; font-weight: 700; color: var(--danger); margin-bottom: 22px; background: var(--danger-soft); padding: 8px 14px; border-radius: 8px; display: inline-block; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
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
    position: relative; overflow: hidden;
  }
  .toast.out { animation: toastOut .3s ease forwards; }
  .toast-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: 700; }
  .toast-icon svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .toast-success .toast-icon { background: var(--success-soft); color: var(--success); }
  .toast-error   .toast-icon { background: var(--danger-soft);  color: var(--danger); }
  .toast-info    .toast-icon { background: var(--blue-soft);    color: var(--blue); }
  .toast-text { flex: 1; color: var(--ink2); }
  .toast-close { width: 20px; height: 20px; border: none; background: transparent; cursor: pointer; color: var(--muted); display: flex; align-items: center; justify-content: center; padding: 0; flex-shrink: 0; }
  .toast-close svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; }
  .toast-progress { position: absolute; bottom: 0; left: 0; height: 3px; background: var(--blue); border-radius: 0 0 14px 14px; animation: progressBar 4s linear forwards; }

  /* ── Responsive ── */
  @media (max-width: 1100px) {
    .pc-wrap { padding: 28px 24px 70px; }
  }

  @media (max-width: 900px) {
    .doc-grid { grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); }
  }

  @media (max-width: 768px) {
    .pc-wrap { padding: 18px 14px 60px; }

    .pc-header-main { flex-direction: column; align-items: stretch; gap: 14px; }
    .pc-header-actions { width: 100%; }
    .pc-header-actions .btn { flex: 1; justify-content: center; }

    .pc-back-link { padding: 6px 12px; font-size: .78rem; }

    .pc-title { font-size: 1.7rem; }

    .form-grid { grid-template-columns: 1fr; }
    .modal-upload { padding: 28px 18px; max-width: 100%; }
    .doc-grid { grid-template-columns: 1fr; }
    .toolbar { gap: 8px; }
    .toolbar-search { min-width: 100%; }
    .modal-overlay { padding: 12px; }
    .toast-container { bottom: 16px; right: 16px; left: 16px; }
    .toast { min-width: unset; max-width: 100%; }
    .list-date { display: none; }
    .list-title { max-width: 180px; }

    /* En móvil ocultamos hover preview porque no hay hover real */
    .doc-card .pdf-hover-preview { display: none; }
  }

  @media (max-width: 480px) {
    .pc-wrap { padding: 16px 12px 60px; }
    .pc-header-actions .btn { font-size: .82rem; padding: 10px 14px; }
    .pc-back-link { width: auto; }
    .confirm-actions { flex-direction: column; }
  }
</style>
@endpush

@section('content')
<div class="pc-wrap">

  {{-- Header --}}
  <header class="pc-header au">
    <div class="pc-header-top">
      <p class="pc-eyebrow">Parte Contable · Empresa</p>
      <a href="{{ route('partcontable.index') }}" class="pc-back-link">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        Volver a empresas
      </a>
    </div>

    <div class="pc-header-main">
      <div>
        <h1 class="pc-title">{{ $company->name }}</h1>
        <p class="pc-subtitle">Declaraciones, acuses, pagos, constancias y estados financieros.</p>
        <p class="pc-counter">
          <strong id="visibleCount">{{ $docsCount }}</strong>
          documento{{ $docsCount !== 1 ? 's' : '' }} en
          <strong>{{ $currentSubLabel }}</strong>
        </p>
      </div>
      <div class="pc-header-actions">
        <a href="{{ route('partcontable.documents.create', $company->slug) }}?section={{ $currentSectionKey }}&subtipo={{ $currentSubKey }}"
           class="btn btn-primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          Subir {{ $currentSubLabel }}
        </a>
      </div>
    </div>
  </header>

  {{-- Welcome --}}
  @if(!empty($welcomeData))
    <div class="pc-welcome" id="pcWelcome" data-close-key="{{ $welcomeCloseKey }}">
      <div>
        <div class="pc-welcome-title">
          Bienvenido, accediste como <span class="pc-welcome-user">{{ $userName }}</span>
        </div>
        <div class="pc-welcome-sub">Acceso protegido por NIP · Tus acciones quedan registradas.</div>
      </div>
      <button type="button" class="pc-welcome-close" id="pcWelcomeClose" aria-label="Cerrar bienvenida">✕</button>
    </div>
  @endif

  {{-- Flashes ocultos --}}
  @if(session('success'))<div class="pc-flash" id="pcFlashSuccess">{{ session('success') }}</div>@endif
  @if(session('warning'))<div class="pc-flash" id="pcFlashWarning">{{ session('warning') }}</div>@endif

  {{-- Tabs principales --}}
  <nav class="pc-tabs au d1" aria-label="Secciones principales">
    @foreach($pcTabs as $key => $conf)
      @php
        $url = route('partcontable.company', $company->slug)
              . '?section='.$key
              . '&subtipo='.array_key_first($conf['subtabs']);
      @endphp
      <a href="{{ $url }}" class="pc-tab-item {{ $currentSectionKey === $key ? 'active' : '' }}">
        {{ $conf['label'] }}
      </a>
    @endforeach
  </nav>

  {{-- Subtabs --}}
  <nav class="pc-subtabs au d1" aria-label="Subtipo de documentos">
    @foreach($currentSubtabs as $subKey => $label)
      @php
        $url = route('partcontable.company', $company->slug)
              . '?section='.$currentSectionKey
              . '&subtipo='.$subKey;
      @endphp
      <a href="{{ $url }}" class="pc-subtab-item {{ $currentSubKey === $subKey ? 'active' : '' }}">
        {{ $label }}
      </a>
    @endforeach
  </nav>

  {{-- Toolbar local --}}
  <div class="toolbar au d2">
    <div class="toolbar-search">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <input type="text" class="search-input" id="localSearch" placeholder="Buscar en esta página...">
    </div>
    <select class="sort-select" id="sortSelect">
      <option value="date-desc">Más reciente</option>
      <option value="date-asc">Más antiguo</option>
      <option value="name-asc">Nombre A–Z</option>
      <option value="name-desc">Nombre Z–A</option>
      <option value="type-asc">Por tipo</option>
    </select>
    <div class="view-toggle">
      <button class="toggle-btn active" id="btnGrid" type="button" title="Vista cuadrícula" onclick="setView('grid')">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      </button>
      <button class="toggle-btn" id="btnList" type="button" title="Vista lista" onclick="setView('list')">
        <svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
      </button>
    </div>
  </div>

  {{-- Contenido --}}
  @if($documents->isEmpty())
    <div class="empty-state au d3">
      <div class="empty-icon-wrap">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      </div>
      <h3>Sin documentos aún</h3>
      <p>Sube el primer {{ strtolower($currentSubLabel) }} para esta empresa.</p>
      <a href="{{ route('partcontable.documents.create', $company->slug) }}?section={{ $currentSectionKey }}&subtipo={{ $currentSubKey }}"
         class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Subir documento
      </a>
    </div>
  @else
    <div id="noResultsLocal" class="no-results-local">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#bbb" stroke-width="1.5" stroke-linecap="round" style="margin:0 auto 12px;display:block">
        <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
      </svg>
      No se encontraron documentos para "<span id="searchTerm"></span>"
    </div>

    {{-- Grid view --}}
    <div class="doc-grid au d3" id="docGrid" data-grid>
      @foreach($documents as $doc)
        @php
          $filename = $doc->filename ?? basename($doc->file_path ?? '');
          if (!Str::contains($filename, '.')) {
            $extFromPath = pathinfo($doc->file_path ?? '', PATHINFO_EXTENSION);
            if ($extFromPath) $filename .= '.' . $extFromPath;
          }
          $displayUrl = null;
          $mime = $doc->mime_type ?? null;

          $hasMainStoredFile = (!empty($doc->file_path) && Storage::disk('public')->exists($doc->file_path));
          if ($hasMainStoredFile) {
            $displayUrl = Storage::disk('public')->url($doc->file_path);
            if (!$mime) {
              try { $mime = Storage::disk('public')->mimeType($doc->file_path); } catch (\Throwable $_) {}
            }
          } else {
            $displayUrl = (!empty($doc->url) && Str::startsWith($doc->url, ['http://','https://']))
              ? $doc->url : ($doc->url ?? '');
          }

          $createdAt = $doc->created_at;
          $dateLabel = $doc->date
            ? \Carbon\Carbon::parse($doc->date)->format('d M Y')
            : ($createdAt ? \Carbon\Carbon::parse($createdAt)->format('d M Y') : '—');
          $dateTs = $doc->date
            ? \Carbon\Carbon::parse($doc->date)->timestamp
            : ($createdAt ? \Carbon\Carbon::parse($createdAt)->timestamp : 0);

          $isNew = $createdAt && \Carbon\Carbon::parse($createdAt)->gt($sevenDaysAgo);

          $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
          $isImage = $mime ? Str::startsWith($mime, 'image/') : in_array($ext, ['jpg','jpeg','png','gif','webp','svg'], true);
          $isVideo = $mime ? Str::startsWith($mime, 'video/') : in_array($ext, ['mp4','mov','webm','mkv'], true);
          $isPdf   = $ext === 'pdf';

          $kind = $isPdf ? 'pdf' : ($isImage ? 'img' : ($isVideo ? 'video' : 'other'));
          $pillClass = 'doc-pill-'.$kind;
          $iconClass = $isPdf ? '' : 'is-'.$kind;

          $allowFicticioHere = in_array($currentSectionKey, $ficticioAllowedSections, true)
            && in_array($currentSubKey, $ficticioAllowedSubtypes, true);
          $hasFicticio = !empty($doc->ficticio_file_path);

          $returnUrl  = request()->fullUrl();
          $previewUrl = route('partcontable.documents.preview', $doc) . '?open_ficticio=1&return=' . urlencode($returnUrl);
          $previewUrlRaw = route('partcontable.documents.preview', $doc);

          $fExt = strtolower(pathinfo($doc->ficticio_file_path ?? '', PATHINFO_EXTENSION));
          if (!$fExt) $fExt = ($ext ?: 'pdf');
          $baseName = pathinfo($filename, PATHINFO_FILENAME) ?: ('documento-'.$doc->id);
          $ficticioSuggestedName = $baseName . '-ficticio.' . $fExt;
        @endphp

        <article class="doc-card pc-doc-card"
                 data-id="{{ $doc->id }}"
                 data-title="{{ strtolower($doc->title) }}"
                 data-name="{{ strtolower($doc->title) }}"
                 data-type="{{ $kind }}"
                 data-date="{{ $dateTs }}"
                 data-has-ficticio="{{ $hasFicticio ? '1' : '0' }}">

          {{-- Click overlay --}}
          <a class="card__link"
             href="{{ $previewUrlRaw }}"
             target="_blank" rel="noopener"
             aria-label="Vista previa de {{ $doc->title }}"></a>

          {{-- Badge nuevo --}}
          @if($isNew)<span class="badge-new">Nuevo</span>@endif

          {{-- Hover preview tooltip (lazy load) --}}
          <div class="pdf-hover-preview" data-kind="{{ $kind }}" data-preview-url="{{ $previewUrlRaw }}" data-display-url="{{ $displayUrl }}">
            @if($isImage && $displayUrl)
              <img data-src="{{ $displayUrl }}" alt="preview" loading="lazy">
            @elseif($isVideo && $displayUrl)
              <video data-src="{{ $displayUrl }}" muted preload="none"></video>
            @else
              <iframe data-src="{{ $previewUrlRaw }}" loading="lazy" title="preview"></iframe>
            @endif
          </div>

          <div class="doc-card-body">
            <div class="card-top">
              <div class="card-badges">
                <span class="doc-pill {{ $pillClass }}">{{ strtoupper($ext ?: ($doc->file_type ?? 'FILE')) }}</span>
                @if($hasFicticio)<span class="doc-pill doc-pill-ficticio">Ficticio</span>@endif
              </div>
              <div class="card-top-actions">
                @if($allowFicticioHere && !$hasFicticio)
                  <a href="{{ $previewUrl }}" class="btn-icon warning"
                     title="Subir ficticio (en previsualización)"
                     aria-label="Subir ficticio para {{ $doc->title }}"
                     onclick="event.stopPropagation();">
                    <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="M9 15h6"/></svg>
                  </a>
                @endif
                <form method="POST" action="{{ route('partcontable.documents.destroy', $doc) }}" class="pc-delete-form-inline" style="display:inline;">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn-icon danger"
                          title="Eliminar"
                          data-doc-title="{{ $doc->title }}"
                          aria-label="Eliminar {{ $doc->title }}"
                          onclick="event.stopPropagation();">
                    <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                  </button>
                </form>
              </div>
            </div>

            <div class="doc-icon-wrap">
              <div class="doc-pdf-icon {{ $iconClass }}">
                @if($isImage)
                  <svg viewBox="0 0 24 24"><path d="M3 7h3l2-3h6l2 3h3v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"/><circle cx="12" cy="13" r="3"/></svg>
                  <span>IMG</span>
                @elseif($isVideo)
                  <svg viewBox="0 0 24 24"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                  <span>VID</span>
                @elseif($isPdf)
                  <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                  <span>PDF</span>
                @else
                  <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                  <span>FILE</span>
                @endif
              </div>
              <div class="doc-meta">
                <div class="doc-title" title="{{ $doc->title }}">{{ Str::limit($doc->title, 60) }}</div>
                <div class="doc-subtype">{{ $doc->subtype?->name ?? $currentSubLabel }}</div>
              </div>
            </div>

            @if($isImage && $displayUrl)
              <div class="doc-thumb">
                <img src="{{ $displayUrl }}" alt="{{ $doc->title }}" loading="lazy">
              </div>
            @elseif($isVideo && $displayUrl)
              <div class="doc-thumb">
                <video muted preload="metadata">
                  <source src="{{ $displayUrl }}" type="{{ $mime ?: 'video/mp4' }}">
                </video>
              </div>
            @endif
          </div>

          <div class="doc-footer">
            <div class="doc-info">
              <span class="doc-date">{{ $dateLabel }}</span>
              <span class="doc-uploader">{{ $doc->uploader->name ?? $doc->user->name ?? '—' }}</span>
            </div>
            <div class="doc-actions">
              <a class="btn-icon pc-btn-download"
                 href="{{ route('partcontable.documents.download', $doc) }}"
                 data-filename="{{ $filename }}"
                 title="Descargar"
                 aria-label="Descargar {{ $doc->title }}"
                 onclick="event.stopPropagation();">
                <span class="pc-ic-default">
                  <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                </span>
                <span class="pc-ic-spin">
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="none">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.2" opacity=".25"/>
                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                  </svg>
                </span>
              </a>

              @if($hasFicticio)
                <a class="btn-icon warning pc-btn-download"
                   href="{{ route('partcontable.documents.ficticio.download', $doc) }}"
                   data-filename="{{ $ficticioSuggestedName }}"
                   title="Descargar ficticio"
                   aria-label="Descargar ficticio de {{ $doc->title }}"
                   onclick="event.stopPropagation();">
                  <span class="pc-ic-default">
                    <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="M9 15h6"/></svg>
                  </span>
                  <span class="pc-ic-spin">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none">
                      <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.2" opacity=".25"/>
                      <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                    </svg>
                  </span>
                </a>
              @endif
            </div>
          </div>
        </article>
      @endforeach
    </div>

    {{-- List view --}}
    <div class="doc-list au d3" data-list>
      @foreach($documents as $doc)
        @php
          $filename = $doc->filename ?? basename($doc->file_path ?? '');
          if (!Str::contains($filename, '.')) {
            $extFromPath = pathinfo($doc->file_path ?? '', PATHINFO_EXTENSION);
            if ($extFromPath) $filename .= '.' . $extFromPath;
          }
          $mime = $doc->mime_type ?? null;
          $hasMainStoredFile = (!empty($doc->file_path) && Storage::disk('public')->exists($doc->file_path));
          if (!$mime && $hasMainStoredFile) {
            try { $mime = Storage::disk('public')->mimeType($doc->file_path); } catch (\Throwable $_) {}
          }
          $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
          $isImage = $mime ? Str::startsWith($mime, 'image/') : in_array($ext, ['jpg','jpeg','png','gif','webp','svg'], true);
          $isVideo = $mime ? Str::startsWith($mime, 'video/') : in_array($ext, ['mp4','mov','webm','mkv'], true);
          $isPdf   = $ext === 'pdf';
          $kind = $isPdf ? 'pdf' : ($isImage ? 'img' : ($isVideo ? 'video' : 'other'));
          $iconClass = $isPdf ? '' : 'is-'.$kind;

          $hasFicticio = !empty($doc->ficticio_file_path);
          $createdAt = $doc->created_at;
          $dateLabel = $doc->date
            ? \Carbon\Carbon::parse($doc->date)->format('d M Y')
            : ($createdAt ? \Carbon\Carbon::parse($createdAt)->format('d M Y') : '—');
          $dateTs = $doc->date
            ? \Carbon\Carbon::parse($doc->date)->timestamp
            : ($createdAt ? \Carbon\Carbon::parse($createdAt)->timestamp : 0);

          $isNew = $createdAt && \Carbon\Carbon::parse($createdAt)->gt($sevenDaysAgo);

          $fExt = strtolower(pathinfo($doc->ficticio_file_path ?? '', PATHINFO_EXTENSION));
          if (!$fExt) $fExt = ($ext ?: 'pdf');
          $baseName = pathinfo($filename, PATHINFO_FILENAME) ?: ('documento-'.$doc->id);
          $ficticioSuggestedName = $baseName . '-ficticio.' . $fExt;
        @endphp

        <div class="doc-list-item pc-doc-card"
             data-id="{{ $doc->id }}"
             data-title="{{ strtolower($doc->title) }}"
             data-name="{{ strtolower($doc->title) }}"
             data-type="{{ $kind }}"
             data-date="{{ $dateTs }}"
             data-has-ficticio="{{ $hasFicticio ? '1' : '0' }}">
          <a class="card__link" href="{{ route('partcontable.documents.preview', $doc) }}" target="_blank" rel="noopener"></a>

          <div class="list-mini-icon {{ $iconClass }}">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          </div>
          <div class="list-info">
            <span class="list-title" title="{{ $doc->title }}">{{ $doc->title }}</span>
            @if($isNew)<span class="list-pill" style="background:#e6f0ff;color:#007aff;">Nuevo</span>@endif
            <span class="list-pill">{{ strtoupper($ext ?: 'FILE') }}</span>
            @if($hasFicticio)
              <span class="list-pill" style="background:var(--warning-soft);color:var(--warning);">Ficticio</span>
            @endif
            <span class="list-date">{{ $dateLabel }}</span>
          </div>
          <div class="list-actions">
            <a class="btn-icon pc-btn-download"
               href="{{ route('partcontable.documents.download', $doc) }}"
               data-filename="{{ $filename }}"
               title="Descargar"
               onclick="event.stopPropagation();">
              <span class="pc-ic-default">
                <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              </span>
              <span class="pc-ic-spin">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none">
                  <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.2" opacity=".25"/>
                  <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
              </span>
            </a>
            @if($hasFicticio)
              <a class="btn-icon warning pc-btn-download"
                 href="{{ route('partcontable.documents.ficticio.download', $doc) }}"
                 data-filename="{{ $ficticioSuggestedName }}"
                 title="Descargar ficticio"
                 onclick="event.stopPropagation();">
                <span class="pc-ic-default">
                  <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="M9 15h6"/></svg>
                </span>
                <span class="pc-ic-spin">
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="none">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.2" opacity=".25"/>
                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                  </svg>
                </span>
              </a>
            @endif
            <form method="POST" action="{{ route('partcontable.documents.destroy', $doc) }}" class="pc-delete-form-inline" style="display:inline;">
              @csrf @method('DELETE')
              <button type="submit" class="btn-icon danger"
                      title="Eliminar"
                      data-doc-title="{{ $doc->title }}"
                      onclick="event.stopPropagation();">
                <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
              </button>
            </form>
          </div>
        </div>
      @endforeach
    </div>
  @endif

  {{-- Sin paginación --}}
</div>

{{-- ══ MODAL: UPLOAD ══ --}}
<div class="modal-overlay" id="pcUploadModal" aria-hidden="true" role="dialog">
  <div class="modal-backdrop" data-action="close"></div>
  <div class="modal-content-wrap">
    <div class="modal-upload">
      <button class="modal-close" id="pcModalCancel" type="button">
        <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
      <p class="modal-eyebrow">Nuevo documento</p>
      <h2 class="modal-title">Subir <span id="pcModalSectionName">{{ $currentSubLabel }}</span></h2>

      <form id="pcUploadForm" method="POST" enctype="multipart/form-data" action="{{ route('partcontable.documents.store', $company->slug) }}">
        @csrf
        <input type="hidden" name="section_id" value="{{ $section->id ?? '' }}">

        <div class="form-grid">
          <div class="form-group full">
            <label class="form-label">Título</label>
            <input type="text" name="title" placeholder="Nombre del documento" class="form-input">
          </div>
          <div class="form-group full">
            <label class="form-label">Subcategoría (opcional)</label>
            <select name="subtype_id" id="pcSubtypeSelect" class="form-select">
              <option value="">-- Seleccionar --</option>
              @isset($subtypes)
                @foreach($subtypes as $st)
                  <option value="{{ $st->id }}">{{ $st->name }}</option>
                @endforeach
              @endisset
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Fecha</label>
            <input type="date" name="date" class="form-input">
          </div>
          <div class="form-group">
            <label class="form-label">Archivo</label>
            <input type="file" name="file" accept=".jpg,.jpeg,.png,.gif,.webp,.svg,.mp4,.mov,.pdf,.doc,.docx,.xls,.xlsx" required class="form-input">
          </div>
          <div class="form-group full">
            <label class="form-label">Descripción</label>
            <textarea name="description" rows="3" placeholder="Descripción (opcional)" class="form-textarea"></textarea>
          </div>
        </div>
        <button type="submit" class="btn-submit">Subir documento</button>
      </form>
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
        <button type="button" class="btn btn-danger-solid" id="confirm-delete-btn" style="flex:1;">Sí, eliminar</button>
      </div>
    </div>
  </div>
</div>

{{-- Toast host --}}
<div class="toast-container" id="toastContainer"></div>
@endsection

@push('scripts')
<script>
(function(){
  'use strict';
  if (window.__pcCompanyBound) return;
  window.__pcCompanyBound = true;

  // ════════════ TOAST ════════════
  function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    const icons = {
      success: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
      error:   '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
      info:    '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>'
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
  window.dismissToast = dismissToast;

  // ════════════ VIEW GRID/LIST ════════════
  let currentView = localStorage.getItem('pc_company_view') || 'grid';
  window.setView = function(v) {
    currentView = v;
    localStorage.setItem('pc_company_view', v);
    document.querySelectorAll('[data-grid]').forEach(el => el.style.display = v === 'grid' ? 'grid' : 'none');
    document.querySelectorAll('[data-list]').forEach(el => el.style.display = v === 'list' ? 'flex' : 'none');
    document.getElementById('btnGrid')?.classList.toggle('active', v === 'grid');
    document.getElementById('btnList')?.classList.toggle('active', v === 'list');
  };

  // ════════════ SEARCH + SORT ════════════
  function getKey(el){ return (el.dataset.title || '') + ' ' + (el.dataset.type || ''); }

  function applySearchAndSort() {
    const q    = (document.getElementById('localSearch')?.value || '').trim().toLowerCase();
    const sort = document.getElementById('sortSelect')?.value || 'date-desc';

    const grid = document.querySelector('[data-grid]');
    const list = document.querySelector('[data-list]');
    if (!grid && !list) return;

    const gridItems = Array.from(grid?.querySelectorAll('.doc-card') || []);
    const listItems = Array.from(list?.querySelectorAll('.doc-list-item') || []);

    const matches = el => !q || getKey(el).includes(q);
    const sortFn = (a, b) => {
      switch(sort) {
        case 'date-asc':  return (a.dataset.date || 0) - (b.dataset.date || 0);
        case 'date-desc': return (b.dataset.date || 0) - (a.dataset.date || 0);
        case 'name-asc':  return (a.dataset.name || '').localeCompare(b.dataset.name || '');
        case 'name-desc': return (b.dataset.name || '').localeCompare(a.dataset.name || '');
        case 'type-asc':  return (a.dataset.type || '').localeCompare(b.dataset.type || '');
        default: return 0;
      }
    };

    const visibleGrid = gridItems.filter(matches).sort(sortFn);
    const visibleList = listItems.filter(matches).sort(sortFn);

    gridItems.forEach(el => el.style.display = 'none');
    listItems.forEach(el => el.style.display = 'none');
    visibleGrid.forEach(el => { el.style.display = ''; grid?.appendChild(el); });
    visibleList.forEach(el => { el.style.display = ''; list?.appendChild(el); });

    const total = visibleGrid.length || visibleList.length;
    const vc = document.getElementById('visibleCount');
    if (vc) vc.textContent = total;

    const noRes = document.getElementById('noResultsLocal');
    const term  = document.getElementById('searchTerm');
    if (noRes) {
      noRes.style.display = total === 0 && q ? 'block' : 'none';
      if (term) term.textContent = q;
    }
  }

  // ════════════ HOVER PREVIEW (lazy load) ════════════
  function initHoverPreviews() {
    document.querySelectorAll('.pdf-hover-preview').forEach(preview => {
      const card = preview.closest('.doc-card');
      if (!card) return;

      const media = preview.querySelector('iframe, img, video');
      if (!media) return;

      let loaded = false;
      card.addEventListener('mouseenter', () => {
        if (loaded) return;
        const src = media.dataset.src;
        if (!src) { loaded = true; return; }

        if (media.tagName === 'VIDEO') {
          const source = document.createElement('source');
          source.src = src;
          media.appendChild(source);
          media.load();
        } else {
          media.src = src;
        }
        loaded = true;
      });
    });
  }

  // ════════════ DOWNLOAD ANIMATED ════════════
  function parseFilenameFromContentDisposition(cd){
    if(!cd) return null;
    try{
      const mStar = cd.match(/filename\*\s*=\s*UTF-8''([^;]+)/i);
      if(mStar && mStar[1]) return decodeURIComponent(mStar[1].replace(/["']/g,'').trim());
      const m = cd.match(/filename\s*=\s*("?)([^";]+)\1/i);
      if(m && m[2]) return m[2].trim();
    }catch(_){}
    return null;
  }

  async function handleDownloadClick(e){
    e.preventDefault();
    e.stopPropagation();
    const btn = e.currentTarget;
    if(!btn || btn.classList.contains('is-downloading')) return;
    const url = btn.getAttribute('href');
    if(!url) return;
    const suggested = (btn.getAttribute('data-filename') || '').trim();
    btn.classList.add('is-downloading');
    showToast('Preparando descarga…', 'info');
    try{
      const res = await fetch(url, { credentials:'same-origin' });
      if(!res.ok) throw new Error('HTTP ' + res.status);
      const cd = res.headers.get('Content-Disposition') || res.headers.get('content-disposition');
      const fromHeader = parseFilenameFromContentDisposition(cd);
      const filename = fromHeader || suggested || 'archivo';
      const blob = await res.blob();
      const blobUrl = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = blobUrl; a.download = filename;
      document.body.appendChild(a); a.click(); a.remove();
      setTimeout(() => URL.revokeObjectURL(blobUrl), 30000);
      showToast('Descarga iniciada', 'success');
    }catch(err){
      showToast('No se pudo descargar el archivo', 'error');
    }finally{
      btn.classList.remove('is-downloading');
    }
  }

  // ════════════ DELETE CONFIRM ════════════
  let pendingDeleteForm = null;
  function openConfirmDelete(form, title){
    pendingDeleteForm = form;
    document.getElementById('confirm-doc-name').textContent = title || 'documento';
    document.getElementById('confirm-modal').classList.add('open');
  }
  window.closeConfirmDelete = function(){
    document.getElementById('confirm-modal').classList.remove('open');
    pendingDeleteForm = null;
  };

  async function performDelete(form){
    const url = form.action;
    const token = form.querySelector('input[name="_token"]').value;
    const methodInput = form.querySelector('input[name="_method"]');
    const method = methodInput ? methodInput.value : 'DELETE';
    try{
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': token,
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'Accept': 'application/json'
        },
        credentials: 'same-origin',
        body: new URLSearchParams({'_method': method})
      });
      if(res.ok){
        const card = form.closest('.pc-doc-card');
        const docId = card?.dataset.id;
        if (docId) {
          document.querySelectorAll(`.pc-doc-card[data-id="${docId}"]`).forEach(el => el.remove());
        } else if (card) {
          card.remove();
        }
        showToast('Documento eliminado correctamente', 'success');
        applySearchAndSort();
      } else {
        let msg = 'No se pudo eliminar el documento.';
        try {
          const json = await res.json();
          if (json && json.message) msg = json.message;
        } catch(_) {}
        showToast(msg, 'error');
      }
    }catch(e){
      showToast('Error de red al eliminar', 'error');
    }
  }

  // ════════════ INIT ════════════
  document.addEventListener('DOMContentLoaded', function(){
    setView(currentView);

    const ls = document.getElementById('localSearch');
    const ss = document.getElementById('sortSelect');
    let debTimer;
    if (ls) ls.addEventListener('input', () => { clearTimeout(debTimer); debTimer = setTimeout(applySearchAndSort, 150); });
    if (ss) ss.addEventListener('change', applySearchAndSort);

    const fs = document.getElementById('pcFlashSuccess');
    const fw = document.getElementById('pcFlashWarning');
    if (fs && fs.textContent.trim()) showToast(fs.textContent.trim(), 'success');
    if (fw && fw.textContent.trim()) showToast(fw.textContent.trim(), 'error');

    // ════ WELCOME BANNER (auto-hide 20s + close persistente) ════
    const welcome  = document.getElementById('pcWelcome');
    const closeBtn = document.getElementById('pcWelcomeClose');
    if (welcome) {
      const key = welcome.getAttribute('data-close-key') || 'pc_welcome_closed_global';
      if (localStorage.getItem(key) === '1') welcome.style.display = 'none';

      const dismissWelcome = (persist = false) => {
        if (welcome.style.display === 'none') return;
        if (persist) localStorage.setItem(key, '1');
        welcome.style.transition = 'opacity .45s ease, transform .45s ease';
        welcome.style.opacity   = '0';
        welcome.style.transform = 'translateY(-8px)';
        setTimeout(() => { welcome.style.display = 'none'; }, 450);
      };

      if (closeBtn) closeBtn.addEventListener('click', () => dismissWelcome(true));
      setTimeout(() => dismissWelcome(false), 20000);
    }

    // Hover previews (lazy load)
    initHoverPreviews();

    document.querySelectorAll('.pc-delete-form-inline').forEach(function(form){
      form.addEventListener('submit', function(ev){
        ev.preventDefault();
        const btn = form.querySelector('[data-doc-title]');
        const title = btn?.getAttribute('data-doc-title') || 'documento';
        openConfirmDelete(form, title);
      });
    });

    document.getElementById('confirm-delete-btn')?.addEventListener('click', function(){
      if (pendingDeleteForm) {
        const formRef = pendingDeleteForm;
        closeConfirmDelete();
        performDelete(formRef);
      }
    });

    document.querySelectorAll('a.pc-btn-download').forEach(btn => {
      btn.addEventListener('click', handleDownloadClick, { passive:false });
    });

    document.addEventListener('click', (e) => {
      if (e.target.closest('.doc-actions, .list-actions, .card-top-actions')) {
        e.stopPropagation();
      }
    }, true);

    const uploadModal = document.getElementById('pcUploadModal');
    document.getElementById('pcModalCancel')?.addEventListener('click', () => uploadModal?.classList.remove('open'));
    uploadModal?.querySelector('[data-action="close"]')?.addEventListener('click', () => uploadModal.classList.remove('open'));

    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
      }
    });
  });
})();
</script>
@endpush