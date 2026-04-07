@extends('layouts.app')

@section('title', 'Subir publicación')

@section('content')
@php
  $v = fn($k,$d=null) => old($k,$d);
  $usersList = $users ?? \App\Models\User::select('id','name','email')->orderBy('name')->get();
@endphp
<link rel="stylesheet" href="{{ asset('css/publications.css') }}?v={{ time() }}">
<style>
  #pubCreateClean{
    --glass-bg: rgba(255,255,255,.78);
    --glass-brd: rgba(148,163,184,.22);
    --deep-shadow: 0 22px 60px rgba(15,23,42,.10);
  }

  #pubCreateClean .pageHead{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:16px;
    margin-bottom:20px;
  }

  #pubCreateClean .titleRow{
    letter-spacing:-.02em;
  }

  #pubCreateClean .subtitle{
    max-width:840px;
    line-height:1.6;
  }

  #pubCreateClean .card{
    background: linear-gradient(180deg, rgba(255,255,255,.94), rgba(248,250,252,.90));
    border: 1px solid var(--glass-brd);
    box-shadow: var(--deep-shadow);
    backdrop-filter: blur(14px);
  }

  #pubCreateClean .cardHead{
    border-bottom-color: rgba(148,163,184,.18);
  }

  #pubCreateClean .drop{
    position: relative;
    border: 1px dashed rgba(59,130,246,.28);
    background:
      radial-gradient(circle at top right, rgba(59,130,246,.08), transparent 35%),
      radial-gradient(circle at bottom left, rgba(16,185,129,.08), transparent 35%),
      linear-gradient(180deg, rgba(255,255,255,.88), rgba(248,250,252,.82));
    box-shadow: inset 0 1px 0 rgba(255,255,255,.75);
  }

  #pubCreateClean .drop::after{
    content:'Cualquier archivo';
    position:absolute;
    right:14px;
    bottom:12px;
    font-size:11px;
    font-weight:800;
    letter-spacing:.06em;
    text-transform:uppercase;
    color:rgba(71,85,105,.72);
  }

  #pubCreateClean .premiumHint{
    margin-top: 12px;
    padding: 12px 14px;
    border-radius: 14px;
    border: 1px solid rgba(148,163,184,.18);
    background: linear-gradient(180deg, rgba(248,250,252,.95), rgba(255,255,255,.92));
    color: #334155;
    font-size: 12px;
    line-height: 1.55;
  }

  #pubCreateClean .premiumHint strong{
    color:#0f172a;
  }

  #pubCreateClean .aiBanner{
    margin-top: 12px;
    padding: 11px 14px;
    border-radius: 12px;
    border: 1px solid rgba(59,130,246,.16);
    background: rgba(59,130,246,.06);
    color: #1e3a8a;
    font-size: 12px;
    display:none;
  }

  #pubCreateClean .aiBanner.show{
    display:block;
  }

  #pubCreateClean .multiItem{
    overflow: hidden;
    border: 1px solid rgba(148,163,184,.16);
    box-shadow: 0 10px 30px rgba(15,23,42,.06);
  }

  #pubCreateClean .miHead{
    background: linear-gradient(180deg, rgba(255,255,255,.92), rgba(248,250,252,.82));
  }

  #pubCreateClean .miniArea{
    min-height: 46px;
  }

  #pubCreateClean .accBox{
    margin-top: 16px;
    border: 1px solid rgba(37,99,235,.14);
    border-radius: 18px;
    background: linear-gradient(180deg, rgba(239,246,255,.86), rgba(255,255,255,.95));
    padding: 14px;
  }

  #pubCreateClean .accBoxHead{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
    margin-bottom: 12px;
  }

  #pubCreateClean .accBoxTitle{
    font-size: 13px;
    font-weight: 900;
    color:#0f172a;
    display:flex;
    align-items:center;
    gap:8px;
  }

  #pubCreateClean .accBoxSub{
    color:#475569;
    font-size: 12px;
    line-height:1.55;
  }

  #pubCreateClean .miniGrid2{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:14px;
  }

  #pubCreateClean .miniGrid3{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:14px;
  }

  #pubCreateClean .accInlineNote{
    margin-top: 12px;
    font-size: 12px;
    color:#475569;
    line-height:1.6;
    padding: 10px 12px;
    border-radius: 12px;
    background: rgba(255,255,255,.8);
    border: 1px solid rgba(148,163,184,.16);
  }

  #pubCreateClean .selectWrap{
    position: relative;
  }

  #pubCreateClean .selectWrap select,
  #pubCreateClean .selectWrap input,
  #pubCreateClean .selectWrap textarea{
    width:100%;
  }

  #pubCreateClean .sectionBadge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    border-radius:999px;
    padding:5px 10px;
    font-size:11px;
    font-weight:800;
    background: rgba(37,99,235,.08);
    color:#1d4ed8;
    border:1px solid rgba(37,99,235,.14);
  }

  /* Botón volver */
  #pubCreateClean .topActions{
    display:flex;
    align-items:center;
  }

  #pubCreateClean .backLinkClean{
    display:inline-flex;
    align-items:center;
    gap:10px;
    text-decoration:none;
    color:#64748b;
    font-weight:500;
    font-size:16px;
    line-height:1;
    padding:8px 2px;
    border-radius:12px;
    transition:
      color .18s ease,
      transform .18s ease,
      opacity .18s ease;
  }

  #pubCreateClean .backLinkClean svg{
    width:18px;
    height:18px;
    flex:0 0 18px;
    transition: transform .18s ease, color .18s ease;
  }

  #pubCreateClean .backLinkClean:hover{
    color:#334155;
    transform: translateX(-2px);
    text-decoration:none;
  }

  #pubCreateClean .backLinkClean:hover svg{
    transform: translateX(-3px);
  }

  /* Loader botones */
  #pubCreateClean .btnx.loading{
    pointer-events:none;
    opacity:1;
    position:relative;
    box-shadow: 0 12px 28px rgba(37,99,235,.18);
  }

  #pubCreateClean .btnx.loading .btnSpin{
    width:16px;
    height:16px;
    border:2px solid currentColor;
    border-right-color:transparent;
    border-radius:50%;
    display:inline-block;
    animation: btnSpin .75s linear infinite;
    vertical-align:-3px;
    margin-right:8px;
  }

  #pubCreateClean .btnx.btnPulse{
    animation: btnPulse 1.1s ease-in-out infinite;
  }

  #pubCreateClean .btnx[disabled]{
    opacity:.65;
    cursor:not-allowed;
    filter:saturate(.88);
  }

  @keyframes btnSpin{
    to{ transform:rotate(360deg); }
  }

  @keyframes btnPulse{
    0%,100%{ transform:translateY(0); }
    50%{ transform:translateY(-1px); }
  }

  /* Mejor estilo para selects */
  #pubCreateClean .field{
    position:relative;
  }

  #pubCreateClean .field select{
    -webkit-appearance:none;
    -moz-appearance:none;
    appearance:none;
    width:100%;
    min-height:56px;
    height:56px;
    border-radius:14px;
    border:1px solid rgba(148,163,184,.24);
    background-color:rgba(255,255,255,.98);
    color:#0f172a;
    font-size:14px;
    font-weight:600;
    line-height:1.15;
    padding:21px 42px 10px 14px;
    box-shadow:
      inset 0 1px 0 rgba(255,255,255,.94),
      0 4px 14px rgba(15,23,42,.04);
    transition:
      border-color .18s ease,
      box-shadow .18s ease,
      transform .18s ease,
      background-color .18s ease;
    cursor:pointer;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 20 20' fill='none'%3E%3Cpath d='M5 7.5L10 12.5L15 7.5' stroke='%2364758B' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat:no-repeat;
    background-position:right 13px center;
    background-size:16px 16px;
  }

  #pubCreateClean .field select:hover{
    border-color:rgba(59,130,246,.28);
    box-shadow:
      inset 0 1px 0 rgba(255,255,255,.96),
      0 8px 20px rgba(37,99,235,.07);
    transform:translateY(-1px);
  }

  #pubCreateClean .field select:focus{
    outline:none;
    border-color:#2563eb;
    box-shadow:
      0 0 0 4px rgba(37,99,235,.10),
      0 10px 22px rgba(37,99,235,.10);
    background-color:#fff;
  }

  #pubCreateClean .field select + label{
    position:absolute;
    top:9px;
    left:11px;
    z-index:2;
    margin:0;
    padding:0 6px;
    font-size:11px;
    font-weight:800;
    line-height:1;
    color:#2563eb;
    background: linear-gradient(180deg, rgba(248,250,252,.98), rgba(255,255,255,.94));
    border-radius:8px;
    pointer-events:none;
    letter-spacing:.01em;
  }

  #pubCreateClean .field.invalid select{
    border-color:rgba(244,63,94,.42);
    box-shadow:0 0 0 4px rgba(244,63,94,.07);
  }

  #pubCreateClean .field.invalid select + label{
    color:#e11d48;
  }

  /* Select custom compacto para Estado */
  #pubCreateClean .field--custom{
    position:relative;
  }

  #pubCreateClean .smartSelect{
    position:relative;
  }

  #pubCreateClean .smartSelect__trigger{
    width:100%;
    min-height:56px;
    height:56px;
    border:none;
    border-radius:14px;
    background:
      linear-gradient(180deg, rgba(255,255,255,.98), rgba(248,250,252,.98));
    box-shadow:
      inset 0 1px 0 rgba(255,255,255,.96),
      0 4px 14px rgba(15,23,42,.04);
    border:1px solid rgba(148,163,184,.24);
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    gap:12px;
    padding:22px 14px 10px;
    cursor:pointer;
    transition:
      border-color .18s ease,
      box-shadow .18s ease,
      transform .18s ease,
      background .18s ease;
  }

  #pubCreateClean .smartSelect__trigger:hover{
    transform:translateY(-1px);
    border-color:rgba(59,130,246,.28);
    box-shadow:
      inset 0 1px 0 rgba(255,255,255,.98),
      0 10px 24px rgba(37,99,235,.08);
  }

  #pubCreateClean .smartSelect.is-open .smartSelect__trigger{
    border-color:#2563eb;
    box-shadow:
      0 0 0 4px rgba(37,99,235,.10),
      0 12px 26px rgba(37,99,235,.12);
    background:#fff;
  }

  #pubCreateClean .smartSelect__label{
    position:absolute;
    top:9px;
    left:11px;
    z-index:2;
    padding:0 6px;
    font-size:11px;
    font-weight:800;
    line-height:1;
    color:#2563eb;
    background: linear-gradient(180deg, rgba(248,250,252,.98), rgba(255,255,255,.94));
    border-radius:8px;
    letter-spacing:.01em;
    pointer-events:none;
  }

  #pubCreateClean .smartSelect__current{
    display:flex;
    align-items:center;
    gap:9px;
    min-width:0;
    font-size:14px;
    font-weight:700;
    color:#0f172a;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }

  #pubCreateClean .smartSelect__dot{
    width:8px;
    height:8px;
    border-radius:999px;
    flex:0 0 8px;
    box-shadow:0 0 0 4px rgba(15,23,42,.04);
  }

  #pubCreateClean .smartSelect__dot.is-pendiente{ background:#f59e0b; }
  #pubCreateClean .smartSelect__dot.is-parcial{ background:#3b82f6; }
  #pubCreateClean .smartSelect__dot.is-cobrado{ background:#10b981; }
  #pubCreateClean .smartSelect__dot.is-vencido{ background:#ef4444; }
  #pubCreateClean .smartSelect__dot.is-cancelado{ background:#64748b; }

  #pubCreateClean .smartSelect__arrow{
    width:16px;
    height:16px;
    flex:0 0 16px;
    color:#64748b;
    transition:transform .22s ease, color .18s ease;
  }

  #pubCreateClean .smartSelect.is-open .smartSelect__arrow{
    transform:rotate(180deg);
    color:#2563eb;
  }

  #pubCreateClean .smartSelect__menu{
    position:absolute;
    top:calc(100% + 8px);
    left:0;
    right:0;
    z-index:40;
    padding:8px;
    border-radius:16px;
    border:1px solid rgba(226,232,240,.96);
    background:rgba(255,255,255,.98);
    backdrop-filter:blur(10px);
    box-shadow:0 18px 36px rgba(15,23,42,.14);
    opacity:0;
    visibility:hidden;
    transform:translateY(6px) scale(.98);
    transition:opacity .18s ease, transform .18s ease, visibility .18s ease;
  }

  #pubCreateClean .smartSelect.is-open .smartSelect__menu{
    opacity:1;
    visibility:visible;
    transform:translateY(0) scale(1);
  }

  #pubCreateClean .smartSelect__option{
    width:100%;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    border:none;
    background:transparent;
    border-radius:12px;
    padding:10px 11px;
    cursor:pointer;
    color:#1e293b;
    font-size:13px;
    font-weight:700;
    text-align:left;
    transition:
      background .16s ease,
      color .16s ease,
      transform .16s ease,
      box-shadow .16s ease;
  }

  #pubCreateClean .smartSelect__option + .smartSelect__option{
    margin-top:4px;
  }

  #pubCreateClean .smartSelect__option:hover{
    background:#f8fafc;
    transform:translateX(2px);
    box-shadow:inset 0 0 0 1px #e2e8f0;
  }

  #pubCreateClean .smartSelect__option.is-active{
    background:linear-gradient(135deg, rgba(37,99,235,.10), rgba(59,130,246,.05));
    color:#1d4ed8;
    box-shadow:inset 0 0 0 1px rgba(37,99,235,.15);
  }

  #pubCreateClean .smartSelect__optionMain{
    display:flex;
    align-items:center;
    gap:10px;
    min-width:0;
  }

  #pubCreateClean .smartSelect__optionText{
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }

  #pubCreateClean .smartSelect__check{
    width:16px;
    height:16px;
    opacity:0;
    transform:scale(.85);
    transition:opacity .16s ease, transform .16s ease;
    color:currentColor;
  }

  #pubCreateClean .smartSelect__option.is-active .smartSelect__check{
    opacity:1;
    transform:scale(1);
  }

  #pubCreateClean .field--custom.invalid .smartSelect__trigger{
    border-color:rgba(244,63,94,.42);
    box-shadow:0 0 0 4px rgba(244,63,94,.07);
  }

  #pubCreateClean .field--custom.invalid .smartSelect__label{
    color:#e11d48;
  }

  #pubCreateClean .accBox .field textarea{
    margin-top:2px;
  }

  @media (max-width: 900px){
    #pubCreateClean .miniGrid2,
    #pubCreateClean .miniGrid3{
      grid-template-columns:1fr;
    }

    #pubCreateClean .field select,
    #pubCreateClean .smartSelect__trigger{
      min-height:52px;
      height:52px;
      padding:20px 40px 9px 13px;
      font-size:13px;
    }

    #pubCreateClean .smartSelect__current{
      font-size:13px;
    }

    #pubCreateClean .pageHead{
      flex-direction:column;
      align-items:flex-start;
    }
  }

  
  /* =========================================================
     Cobranza · Estilo corporativo (encapsulado)
     - Minimalista, moderno, más aire
     - Evita conflictos con estilos globales
     ========================================================= */

  #pubCreateClean #salesAccountingBox{
    position:relative;
    padding: 16px;
    border-radius: 22px;
    border: 1px solid rgba(15,23,42,.08);
    background:
      radial-gradient(1200px 240px at 12% -10%, rgba(37,99,235,.08), transparent 55%),
      radial-gradient(900px 240px at 92% 0%, rgba(16,185,129,.07), transparent 55%),
      linear-gradient(180deg, rgba(255,255,255,.96), rgba(248,250,252,.92));
    box-shadow:
      0 18px 48px rgba(15,23,42,.10);
  }

  #pubCreateClean #salesAccountingBox::before{
    content:"";
    position:absolute;
    inset: 0 0 auto 0;
    height: 3px;
    border-radius: 22px 22px 0 0;
    background: linear-gradient(90deg, rgba(37,99,235,.85), rgba(59,130,246,.25), rgba(16,185,129,.75));
    opacity:.55;
    pointer-events:none;
  }

  #pubCreateClean #salesAccountingBox .accBoxHead{
    margin: 2px 2px 14px 2px;
    padding: 10px 10px 12px 10px;
    border-radius: 18px;
    border: 1px solid rgba(148,163,184,.14);
    background: rgba(255,255,255,.72);
    backdrop-filter: blur(10px);
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap: 14px;
  }

  #pubCreateClean #salesAccountingBox .accBoxTitle{
    display:flex;
    align-items:center;
    gap: 10px;
    font-size: 14px;
    font-weight: 900;
    color:#0f172a;
    letter-spacing: -.01em;
  }

  #pubCreateClean #salesAccountingBox .accBoxTitle svg{
    width:18px;
    height:18px;
    opacity:.9;
  }

  #pubCreateClean #salesAccountingBox .accBoxSub{
    margin-top: 6px;
    color:#475569;
    font-size: 12.5px;
    line-height: 1.55;
  }

  #pubCreateClean #salesAccountingBox .sectionBadge{
    background: rgba(37,99,235,.08);
    border: 1px solid rgba(37,99,235,.16);
    color: #1d4ed8;
    font-size: 11px;
    font-weight: 800;
    padding: 6px 10px;
  }

  /* Grids (tipo bootstrap gutters) */
  #pubCreateClean #salesAccountingBox .corpGrid{
    display:grid;
    gap: 14px;
    margin-top: 14px;
  }
  #pubCreateClean #salesAccountingBox .corpGrid--2{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
  #pubCreateClean #salesAccountingBox .corpGrid--3{ grid-template-columns: repeat(3, minmax(0, 1fr)); }

  /* Campo corporativo */
  #pubCreateClean #salesAccountingBox .corpField{
    min-width:0;
  }

  #pubCreateClean #salesAccountingBox .corpLabel{
    display:block;
    margin: 0 0 6px 2px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: rgba(30,41,59,.78);
  }

  /* Control base (select/input/textarea) */
  #pubCreateClean #salesAccountingBox .corpControl{
    width:100% !important;
    box-sizing:border-box !important;

    height: 48px !important;
    min-height: 48px !important;

    padding: 0 14px !important;
    border-radius: 14px !important;

    border: 1px solid rgba(148,163,184,.26) !important;
    background: rgba(255,255,255,.92) !important;
    color: #0f172a !important;

    font-size: 14px !important;
    font-weight: 100 !important;

    box-shadow:
      inset 0 1px 0 rgba(255,255,255,.92),
      0 6px 18px rgba(15,23,42,.05) !important;

    transition:
      border-color .18s ease,
      box-shadow .18s ease,
      transform .18s ease,
      background-color .18s ease !important;
  }

  #pubCreateClean #salesAccountingBox .corpControl:hover{
    transform: translateY(-1px) !important;
    border-color: rgba(59,130,246,.28) !important;
    box-shadow:
      inset 0 1px 0 rgba(255,255,255,.96),
      0 12px 28px rgba(15,23,42,.08) !important;
  }

  #pubCreateClean #salesAccountingBox .corpControl:focus{
    outline: none !important;
    background: #fff !important;
    border-color: #2563eb !important;
    box-shadow:
      0 0 0 4px rgba(37,99,235,.10),
      0 16px 34px rgba(37,99,235,.10) !important;
    transform: translateY(-1px) !important;
  }

  /* Select arrow corporativo */
  #pubCreateClean #salesAccountingBox select.corpControl{
    -webkit-appearance:none !important;
    -moz-appearance:none !important;
    appearance:none !important;
    padding-right: 42px !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 20 20' fill='none'%3E%3Cpath d='M5 7.5L10 12.5L15 7.5' stroke='%2364758B' stroke-width='1.9' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
    background-repeat:no-repeat !important;
    background-position:right 14px center !important;
    background-size:18px 18px !important;
  }

  /* Input date: alinea ícono */
  #pubCreateClean #salesAccountingBox input[type="date"].corpControl{
    padding-right: 42px !important;
  }

  /* Textarea */
  #pubCreateClean #salesAccountingBox textarea.corpControl{
    height: auto !important;
    min-height: 112px !important;
    padding: 12px 14px !important;
    line-height: 1.5 !important;
    font-weight: 600 !important;
    resize: vertical !important;
  }

  /* SmartSelect (Estado) versión corporativa */
  #pubCreateClean #salesAccountingBox .smartSelect--corp{
    position:relative;
  }

  #pubCreateClean #salesAccountingBox .smartSelect--corp .smartSelect__trigger{
    width:100%;
    height:48px;
    min-height:48px;
    padding: 0 14px;
    border-radius:14px;
    border: 1px solid rgba(148,163,184,.26);
    background: rgba(255,255,255,.92);
    box-shadow:
      inset 0 1px 0 rgba(255,255,255,.92),
      0 6px 18px rgba(15,23,42,.05);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    transition:
      border-color .18s ease,
      box-shadow .18s ease,
      transform .18s ease,
      background-color .18s ease;
  }

  #pubCreateClean #salesAccountingBox .smartSelect--corp .smartSelect__trigger:hover{
    transform: translateY(-1px);
    border-color: rgba(59,130,246,.28);
    box-shadow:
      inset 0 1px 0 rgba(255,255,255,.96),
      0 12px 28px rgba(15,23,42,.08);
  }

  #pubCreateClean #salesAccountingBox .smartSelect--corp.is-open .smartSelect__trigger{
    background:#fff;
    border-color:#2563eb;
    box-shadow:
      0 0 0 4px rgba(37,99,235,.10),
      0 16px 34px rgba(37,99,235,.10);
  }

  #pubCreateClean #salesAccountingBox .smartSelect--corp .smartSelect__current{
    font-size:14px;
    font-weight:800;
  }

  #pubCreateClean #salesAccountingBox .smartSelect--corp .smartSelect__menu{
    border-radius: 16px;
    border:1px solid rgba(226,232,240,.96);
    box-shadow: 0 22px 46px rgba(15,23,42,.16);
  }

  #pubCreateClean #salesAccountingBox .smartSelect--corp .smartSelect__option{
    padding: 10px 12px;
    border-radius: 12px;
  }

  /* Errores */
  #pubCreateClean #salesAccountingBox .corpField.invalid .corpControl,
  #pubCreateClean #salesAccountingBox .corpField.invalid .smartSelect__trigger{
    border-color: rgba(244,63,94,.42) !important;
    box-shadow: 0 0 0 4px rgba(244,63,94,.08) !important;
  }

  #pubCreateClean #salesAccountingBox .corpField.invalid .corpLabel{
    color: rgba(225,29,72,.90);
  }

  /* Nota final */
  #pubCreateClean #salesAccountingBox .accInlineNote{
    margin-top: 14px;
    border-radius: 16px;
    border: 1px solid rgba(148,163,184,.14);
    background: rgba(255,255,255,.70);
    box-shadow: 0 10px 24px rgba(15,23,42,.06);
  }

  @media (max-width: 900px){
    #pubCreateClean #salesAccountingBox{
      padding: 14px;
    }
    #pubCreateClean #salesAccountingBox .corpGrid--2,
    #pubCreateClean #salesAccountingBox .corpGrid--3{
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="container py-5" id="pubCreateClean">
  <div class="overlay" id="aiOverlay" aria-hidden="true">
    <div class="grain"></div>
    <div class="box">
      <div class="boxTop">
        <div class="t">Analizando documentos…</div>
        <div class="s" id="ovFile">—</div>
      </div>

      <div class="loader-wrapper" aria-label="Generando">
        <span class="loader-letter">G</span>
        <span class="loader-letter">e</span>
        <span class="loader-letter">n</span>
        <span class="loader-letter">e</span>
        <span class="loader-letter">r</span>
        <span class="loader-letter">a</span>
        <span class="loader-letter">n</span>
        <span class="loader-letter">d</span>
        <span class="loader-letter">o</span>
        <div class="loader"></div>
      </div>

      <div class="bar"><span id="ovBar"></span></div>
      <div style="margin-top:10px; color:rgba(255,255,255,.78); font-size:12px; font-weight:700;">
        <span id="ovTxt">Preparando…</span>
      </div>
    </div>
  </div>

  <div class="pageHead">
    <div>
      <h1 class="titleRow">
        @include('publications.partials.icons', ['name' => 'upload'])
        Subir publicación
      </h1>
      <div class="subtitle">
        Carga uno o varios archivos y revisa la extracción antes de guardar. El flujo acepta cualquier archivo. La IA extrae mejor en PDF, imágenes y archivos con texto legible.
      </div>
    </div>

    <div class="topActions">
      <a class="backLinkClean" href="{{ route('publications.index') }}">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M15 6L9 12L15 18" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Volver al listado</span>
      </a>
    </div>
  </div>

  @if($errors->any())
    <div class="card" style="margin-bottom:14px; border-color: var(--rose-brd);">
      <div class="cardBody" style="padding:14px 16px;">
        <ul style="margin:0; padding-left:18px; color: var(--rose-ink); font-size:13px;">
          @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
      </div>
    </div>
  @endif

  <form id="pubCreateForm" action="{{ route('publications.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <input type="hidden" name="ai_extract" id="ai_extract" value="1">
    <input type="hidden" name="ai_skip" id="ai_skip" value="0">

    <input type="hidden" name="ai_payload" id="ai_payload" value="">
    <input type="hidden" name="ai_payload_bulk" id="ai_payload_bulk" value="">
    <div id="fpInputs"></div>

    <input type="hidden" name="ai_tax_mode" id="ai_tax_mode" value="included">
    <input type="hidden" name="ai_tax_rate" id="ai_tax_rate" value="0.16">

    <div class="grid">
      <div class="stack">
        <div class="card">
          <div class="cardHead">
            <div class="cardTitle">@include('publications.partials.icons', ['name' => 'edit']) Detalles Generales</div>
          </div>
          <div class="cardBody stack">

            <div class="field @error('title') invalid @enderror">
              <input type="text" name="title" id="f-title" value="{{ $v('title') }}" placeholder=" " required>
              <label for="f-title">Título de la publicación</label>
            </div>

            <div class="switchWrap">
              <div class="switchText">
                <div>Tipo de operación</div>
                <small>¿Es una factura de compra o venta?</small>
              </div>
              <div class="catToggle">
                <input type="radio" name="category" value="compra" id="cat-compra" class="hidden" {{ $v('category', 'compra') == 'compra' ? 'checked' : '' }}>
                <label for="cat-compra" class="catOption opt-compra">Compra</label>

                <input type="radio" name="category" value="venta" id="cat-venta" class="hidden" {{ $v('category') == 'venta' ? 'checked' : '' }}>
                <label for="cat-venta" class="catOption opt-venta">Venta</label>
              </div>
            </div>

            <div class="field">
              <textarea name="description" id="f-desc" placeholder=" ">{{ $v('description') }}</textarea>
              <label for="f-desc">Descripción (Opcional)</label>
            </div>

            <div id="salesAccountingBox" class="accBox {{ $v('category') === 'venta' ? '' : 'hidden' }}">
              <div class="accBoxHead">
                <div>
                  <div class="accBoxTitle">
                    @include('publications.partials.icons', ['name' => 'file'])
                    Datos para Cuentas por Cobrar
                  </div>

                </div>
                <span class="sectionBadge">Venta → Cobranza</span>
              </div>

              <div class="corpGrid corpGrid--2">
                <div class="corpField @error('company_id') invalid @enderror">
                  <label class="corpLabel" for="f-company_id">Compañía</label>
                  <select class="corpControl" name="company_id" id="f-company_id">
                    <option value="">Selecciona compañía</option>
                    @foreach(($companies ?? []) as $c)
                      <option value="{{ $c->id }}" @selected((string)$v('company_id') === (string)$c->id)>{{ $c->name }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="corpField @error('due_date') invalid @enderror">
                  <label class="corpLabel" for="f-due_date">Fecha de vencimiento</label>
                  <input class="corpControl" type="date" name="due_date" id="f-due_date" value="{{ $v('due_date') }}">
                </div>
              </div>

              <div class="corpGrid corpGrid--3">
                <div class="corpField @error('amount_paid') invalid @enderror">
                  <label class="corpLabel" for="f-amount_paid">Monto pagado</label>
                  <input class="corpControl" type="number" min="0" step="0.01" name="amount_paid" id="f-amount_paid" value="{{ $v('amount_paid', 0) }}">
                </div>

                @php
                  $statusValue = $v('status', 'pendiente');
                  $statusLabelMap = [
                    'pendiente' => 'Pendiente',
                    'parcial' => 'Parcial',
                    'cobrado' => 'Cobrado',
                    'vencido' => 'Vencido',
                    'cancelado' => 'Cancelado',
                  ];
                @endphp
                <div class="corpField @error('status') invalid @enderror">
                  <label class="corpLabel">Estado</label>

                  <div class="smartSelect smartSelect--corp" data-smart-select>
                    <input type="hidden" name="status" id="f-status" value="{{ $statusValue }}">

                    <button type="button" class="smartSelect__trigger" data-smart-select-trigger aria-haspopup="listbox" aria-expanded="false">
                      <span class="smartSelect__current" data-smart-select-current>
                        <span class="smartSelect__dot is-{{ $statusValue }}"></span>
                        <span>{{ $statusLabelMap[$statusValue] ?? 'Pendiente' }}</span>
                      </span>
                      <svg class="smartSelect__arrow" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>

                    <div class="smartSelect__menu" data-smart-select-menu role="listbox">
                      <button type="button" class="smartSelect__option {{ $statusValue === 'pendiente' ? 'is-active' : '' }}" data-smart-select-option data-value="pendiente" data-label="Pendiente">
                        <span class="smartSelect__optionMain">
                          <span class="smartSelect__dot is-pendiente"></span>
                          <span class="smartSelect__optionText">Pendiente</span>
                        </span>
                        <svg class="smartSelect__check" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                          <path d="M5 10.5L8.2 13.5L15 6.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </button>

                      <button type="button" class="smartSelect__option {{ $statusValue === 'parcial' ? 'is-active' : '' }}" data-smart-select-option data-value="parcial" data-label="Parcial">
                        <span class="smartSelect__optionMain">
                          <span class="smartSelect__dot is-parcial"></span>
                          <span class="smartSelect__optionText">Parcial</span>
                        </span>
                        <svg class="smartSelect__check" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                          <path d="M5 10.5L8.2 13.5L15 6.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </button>

                      <button type="button" class="smartSelect__option {{ $statusValue === 'cobrado' ? 'is-active' : '' }}" data-smart-select-option data-value="cobrado" data-label="Cobrado">
                        <span class="smartSelect__optionMain">
                          <span class="smartSelect__dot is-cobrado"></span>
                          <span class="smartSelect__optionText">Cobrado</span>
                        </span>
                        <svg class="smartSelect__check" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                          <path d="M5 10.5L8.2 13.5L15 6.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </button>

                      <button type="button" class="smartSelect__option {{ $statusValue === 'vencido' ? 'is-active' : '' }}" data-smart-select-option data-value="vencido" data-label="Vencido">
                        <span class="smartSelect__optionMain">
                          <span class="smartSelect__dot is-vencido"></span>
                          <span class="smartSelect__optionText">Vencido</span>
                        </span>
                        <svg class="smartSelect__check" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                          <path d="M5 10.5L8.2 13.5L15 6.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </button>

                      <button type="button" class="smartSelect__option {{ $statusValue === 'cancelado' ? 'is-active' : '' }}" data-smart-select-option data-value="cancelado" data-label="Cancelado">
                        <span class="smartSelect__optionMain">
                          <span class="smartSelect__dot is-cancelado"></span>
                          <span class="smartSelect__optionText">Cancelado</span>
                        </span>
                        <svg class="smartSelect__check" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                          <path d="M5 10.5L8.2 13.5L15 6.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="corpField @error('priority') invalid @enderror">
                  <label class="corpLabel" for="f-priority">Prioridad</label>
                  <select class="corpControl" name="priority" id="f-priority">
                    <option value="alta" @selected($v('priority') === 'alta')>Alta</option>
                    <option value="media" @selected($v('priority','media') === 'media')>Media</option>
                    <option value="baja" @selected($v('priority') === 'baja')>Baja</option>
                  </select>
                </div>
              </div>

              <div class="corpGrid corpGrid--3">
                <div class="corpField @error('collection_status') invalid @enderror">
                  <label class="corpLabel" for="f-collection_status">Estado de cobranza</label>
                  <select class="corpControl" name="collection_status" id="f-collection_status">
                    <option value="sin_gestion" @selected($v('collection_status','sin_gestion') === 'sin_gestion')>Sin gestión</option>
                    <option value="en_gestion" @selected($v('collection_status') === 'en_gestion')>En gestión</option>
                    <option value="promesa_pago" @selected($v('collection_status') === 'promesa_pago')>Promesa de pago</option>
                    <option value="litigio" @selected($v('collection_status') === 'litigio')>Litigio</option>
                    <option value="incobrable" @selected($v('collection_status') === 'incobrable')>Incobrable</option>
                  </select>
                </div>

                <div class="corpField @error('reminder_days_before') invalid @enderror">
                  <label class="corpLabel" for="f-reminder_days_before">Días previos para recordar</label>
                  <input class="corpControl" type="number" min="0" max="365" name="reminder_days_before" id="f-reminder_days_before" value="{{ $v('reminder_days_before', 5) }}">
                </div>

                <div class="corpField @error('assigned_to') invalid @enderror">
                  <label class="corpLabel" for="f-assigned_to">Asignado a</label>
                  <select class="corpControl" name="assigned_to" id="f-assigned_to">
                    <option value="">Sin asignar</option>
                    @foreach($usersList as $user)
                      <option value="{{ $user->id }}" @selected((string)$v('assigned_to') === (string)$user->id)>
                        {{ $user->name }}{{ !empty($user->email) ? ' · '.$user->email : '' }}
                      </option>
                    @endforeach
                  </select>
                </div>
              </div>

              <div class="corpGrid" style="margin-top:14px;">
                <div class="corpField @error('notes') invalid @enderror">
                  <label class="corpLabel" for="f-notes">Notas de cobranza</label>
                  <textarea class="corpControl" name="notes" id="f-notes" placeholder="Notas internas, acuerdos, seguimiento...">{{ $v('notes') }}</textarea>
                </div>
              </div>

              <div class="accInlineNote">
                La publicación de venta quedará lista para ligarse a cobranza. Se recomienda capturar <strong>fecha de vencimiento</strong> y, si aplica, el <strong>usuario responsable</strong>.
              </div>
            </div>

            <div class="switchWrap">
              <div class="switchText">
                <div>Fijar publicación</div>
                <small>Mostrar al principio de la lista.</small>
              </div>
              <label class="switch">
                <input type="checkbox" name="pinned" value="1" {{ $v('pinned') ? 'checked' : '' }}>
                <span class="track"><span class="thumb"></span></span>
              </label>
            </div>

            <div style="margin-top:10px; display:flex; gap:10px; justify-content:flex-end;">
              <button class="btnx mint" type="submit" id="submitBtn" disabled>
                @include('publications.partials.icons', ['name' => 'check'])
                Guardar Publicación
              </button>
            </div>

          </div>
        </div>
      </div>

      <div class="stack">
        <div class="card">
          <div class="cardHead">
            <div class="cardTitle">@include('publications.partials.icons', ['name' => 'paperclip']) Documento</div>
            <div style="display:flex; gap:6px;">
              <span class="btnx blue tiny hidden" id="pillAiRun">Procesando...</span>
              <span class="btnx mint tiny hidden" id="pillAiOk">Extraído</span>
              <span class="btnx rose tiny hidden" id="pillAiFail">Error</span>
            </div>
          </div>

          <div class="cardBody">
            <input type="file" name="files[]" id="f-file" style="display:none;" multiple required accept="*/*">

            <div class="drop" id="dropZone" title="Click para seleccionar archivos">
              <div class="fileRow">
                <div style="display:flex; gap:12px; align-items:center; min-width:0;">
                  <div style="background:var(--blue-bg); color:var(--blue-ink); padding:8px; border-radius:8px;">
                    @include('publications.partials.icons', ['name' => 'file'])
                  </div>
                  <div style="min-width:0;">
                    <div class="fileName" id="fileName">Seleccionar archivo(s)...</div>
                    <div class="fileMini"><span id="fileType">Cualquier tipo de archivo</span> • <span id="fileSize">Máx. 50 MB por archivo</span></div>
                  </div>
                </div>
                <label class="btnx ghost tiny" for="f-file">Examinar</label>
              </div>
            </div>

            <div class="premiumHint">
              <strong>Modo inteligente:</strong> el guardado se habilita únicamente cuando la IA devuelve información útil o cuando capturas manualmente un documento válido.
            </div>

            <div class="aiBanner" id="aiBanner"></div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px;">
              <small id="aiStatus" style="color:var(--muted); font-size:12px;">Esperando archivo(s)...</small>
              <div style="display:flex; gap:6px;">
                <button type="button" class="btnx ghost tiny hidden" id="btnClearAi">Limpiar</button>
                <button type="button" class="btnx blue tiny" id="btnRetry" disabled>Analizar IA</button>
                <button type="button" class="btnx ghost tiny" id="btnSkipIA">Manual</button>
              </div>
            </div>

            <div class="hidden" id="multiBox" style="margin-top:14px;">
              <div class="multiList" id="multiList"></div>
            </div>

            <div class="hidden" id="aiResult">
              <div class="tableWrap">
                <div class="docMetaRow">
                  <input class="miniField" id="docSupplier" placeholder="Proveedor / Cliente">
                  <input class="miniField" id="docDatetime" type="datetime-local" placeholder="Fecha del documento">
                </div>

                <div class="tblHeader">
                  <div>Concepto</div>
                  <div style="text-align:right;">Cant.</div>
                  <div style="text-align:right;">Precio</div>
                  <div style="text-align:right;">Total</div>
                  <div>Unidad</div>
                  <div></div>
                </div>

                <div id="aiEditRows" class="aiRowsArea"></div>

                <div class="tblFooter">
                  <div style="display:flex; gap:10px; align-items:flex-end;">
                    <button type="button" class="btnx ghost tiny" id="btnAiAddRow">+ Fila</button>

                    <div class="ivaToggle" title="Si el total del documento YA incluye IVA, déjalo activado. Si no, desactívalo y lo sumamos.">
                      <input type="checkbox" id="taxIncluded" checked>
                      <label for="taxIncluded">Total ya incluye IVA</label>
                    </div>
                  </div>

                  <div class="totalsBox">
                    <div class="totRow"><span>Subtotal</span><strong id="aiSubtotal">$0.00</strong></div>
                    <div class="totRow"><span>IVA (16%)</span><strong id="aiTax">$0.00</strong></div>
                    <div class="totBig">
                      <div><small>Total documento</small></div>
                      <div><span id="aiTotal">$0.00</span></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="hidden" id="manualBox" style="margin-top:16px; border-top:1px solid rgba(15,23,42,.1); padding-top:16px;">
              <h4 style="font-size:13px; color:var(--ink); margin:0 0 10px 0;">Captura Manual</h4>

              <div class="docMetaRow" style="margin-bottom:10px; border:1px solid rgba(15,23,42,.08); border-radius:12px;">
                <input class="miniField" id="m_supplier" placeholder="Proveedor / Cliente (manual)">
                <input class="miniField" id="m_datetime" type="datetime-local" placeholder="Fecha (manual)">
              </div>

              <div class="manualGrid">
                <input class="miniField" id="m_name" placeholder="Descripción del ítem">
                <input class="miniField num" id="m_qty" placeholder="1">
                <input class="miniField num" id="m_price" placeholder="0.00">
              </div>
              <div class="manualGrid2">
                <input class="miniField" id="m_unit" placeholder="Unidad (pza)">
                <button type="button" class="btnx blue tiny" id="btnAddRow">Agregar</button>
              </div>

              <div class="tableWrap" style="min-height:auto;">
                <table>
                  <thead><tr><th>Ítem</th><th align="right">Cant.</th><th align="right">Total</th><th></th></tr></thead>
                  <tbody id="mTbody"></tbody>
                  <tfoot><tr><td colspan="2" align="right">Total</td><td align="right" id="mTotal">$0.00</td><td></td></tr></tfoot>
                </table>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </form>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const form = document.getElementById('pubCreateForm');

      const fileInput = document.getElementById('f-file');
      const dropZone = document.getElementById('dropZone');

      const aiEditRows = document.getElementById('aiEditRows');

      const aiPayloadHidden = document.getElementById('ai_payload');
      const aiPayloadBulkHidden = document.getElementById('ai_payload_bulk');
      const fpInputs = document.getElementById('fpInputs');

      const aiSubtotalEl = document.getElementById('aiSubtotal');
      const aiTaxEl = document.getElementById('aiTax');
      const aiTotalEl = document.getElementById('aiTotal');

      const taxIncluded = document.getElementById('taxIncluded');
      const taxModeHidden = document.getElementById('ai_tax_mode');
      const taxRateHidden = document.getElementById('ai_tax_rate');

      const docSupplier = document.getElementById('docSupplier');
      const docDatetime = document.getElementById('docDatetime');
      const mSupplier = document.getElementById('m_supplier');
      const mDatetime = document.getElementById('m_datetime');

      const aiExtractHidden = document.getElementById('ai_extract');
      const aiSkipHidden = document.getElementById('ai_skip');

      const multiBox = document.getElementById('multiBox');
      const multiList = document.getElementById('multiList');

      const aiResult = document.getElementById('aiResult');
      const manualBox = document.getElementById('manualBox');

      const fileNameEl = document.getElementById('fileName');
      const fileSizeEl = document.getElementById('fileSize');
      const aiStatus = document.getElementById('aiStatus');
      const aiBanner = document.getElementById('aiBanner');

      const btnRetry = document.getElementById('btnRetry');
      const btnSkipIA = document.getElementById('btnSkipIA');
      const btnClearAi = document.getElementById('btnClearAi');
      const btnAddRow = document.getElementById('btnAddRow');
      const btnAiAddRow = document.getElementById('btnAiAddRow');

      const overlay = document.getElementById('aiOverlay');
      const ovFile = document.getElementById('ovFile');
      const ovBar = document.getElementById('ovBar');
      const ovTxt = document.getElementById('ovTxt');

      const salesAccountingBox = document.getElementById('salesAccountingBox');
      const companyInput = document.getElementById('f-company_id');
      const submitBtn = document.getElementById('submitBtn');

      const iconFileHtml = @json(view('publications.partials.icons', ['name' => 'file'])->render());

      let aiRows = [];
      let manualRows = [];
      let aiDoc = { supplier_name:'', document_datetime:'' };
      const multi = new Map();

      let currentMode = '';
      let aiRunningCount = 0;
      let batchAiRunning = false;

      const money = n => '$' + Number(n||0).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
      const num = v => parseFloat(String(v ?? '').replace(/[^0-9.\-]/g,'')) || 0;
      const cleanTxt = s => String(s||'').trim();

      function escapeHtml(str){
        return String(str||'')
          .replaceAll('&','&amp;')
          .replaceAll('<','&lt;')
          .replaceAll('>','&gt;')
          .replaceAll('"','&quot;')
          .replaceAll("'","&#039;");
      }

      function round2(n){ return Math.round((Number(n||0) + Number.EPSILON) * 100) / 100; }

      function currentTaxRate(){
        const r = parseFloat(taxRateHidden?.value ?? '0.16');
        return isFinite(r) ? r : 0.16;
      }

      function toDatetimeLocal(val){
        if(!val) return '';
        const s = String(val).trim();
        if(s.includes('T')) return s.slice(0,16);
        const parts = s.split(' ');
        if(parts.length >= 2) return (parts[0] + 'T' + parts[1].slice(0,5));
        return s;
      }

      function fromDatetimeLocal(val){
        if(!val) return '';
        const s = String(val).trim();
        if(!s) return '';
        if(s.includes('T')){
          const [d,t] = s.split('T');
          return d + ' ' + (t.length === 5 ? (t + ':00') : t);
        }
        return s;
      }

      function autoGrowTextarea(el){
        if(!el) return;
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 220) + 'px';
      }

      function toggleState(s){
        document.getElementById('pillAiRun').classList.add('hidden');
        document.getElementById('pillAiOk').classList.add('hidden');
        document.getElementById('pillAiFail').classList.add('hidden');
        if(s === 'run') document.getElementById('pillAiRun').classList.remove('hidden');
        if(s === 'ok') document.getElementById('pillAiOk').classList.remove('hidden');
        if(s === 'fail') document.getElementById('pillAiFail').classList.remove('hidden');
      }

      function setBanner(msg='', kind='info'){
        if(!aiBanner) return;
        aiBanner.textContent = msg || '';
        aiBanner.classList.toggle('show', !!msg);
        aiBanner.style.borderColor = kind === 'warn'
          ? 'rgba(245,158,11,.24)'
          : kind === 'error'
            ? 'rgba(244,63,94,.24)'
            : 'rgba(59,130,246,.18)';
        aiBanner.style.background = kind === 'warn'
          ? 'rgba(245,158,11,.10)'
          : kind === 'error'
            ? 'rgba(244,63,94,.08)'
            : 'rgba(59,130,246,.06)';
        aiBanner.style.color = kind === 'warn'
          ? '#92400e'
          : kind === 'error'
            ? '#9f1239'
            : '#1e3a8a';
      }

      function ensureAtLeastOneRow(items){
        return Array.isArray(items) && items.length
          ? items
          : [{ item_name:'', qty:1, unit_price:0, line_total:0, unit:'pza' }];
      }

      function showOverlay(on, txt='', file='', pct=0){
        overlay.classList.toggle('show', !!on);
        if(on){
          ovTxt.textContent = txt || 'Analizando…';
          ovFile.textContent = file || '—';
          ovBar.style.width = Math.max(0, Math.min(100, pct)) + '%';
        }
      }

      function categoryVal(){
        return document.querySelector('input[name="category"]:checked')?.value || 'compra';
      }

      function isSale(){
        return categoryVal() === 'venta';
      }

      function partyLabel(){
        return isSale() ? 'Cliente / Receptor' : 'Proveedor';
      }

      function refreshPartyPlaceholders(){
        if (docSupplier) docSupplier.placeholder = partyLabel();
        if (mSupplier) mSupplier.placeholder = partyLabel() + ' (manual)';
      }

      function setBtnLoading(btn, on, text = 'Procesando...'){
        if(!btn) return;
        if(on){
          if(!btn.dataset.originalHtml){
            btn.dataset.originalHtml = btn.innerHTML;
          }
          btn.classList.add('loading','btnPulse');
          btn.disabled = true;
          btn.innerHTML = `<span class="btnSpin"></span>${escapeHtml(text)}`;
        }else{
          btn.classList.remove('loading','btnPulse');
          if(btn.dataset.originalHtml){
            btn.innerHTML = btn.dataset.originalHtml;
          }
        }
      }

      function initSmartSelects(){
        document.querySelectorAll('[data-smart-select]').forEach(select => {
          if(select.dataset.ready === '1') return;
          select.dataset.ready = '1';

          const trigger = select.querySelector('[data-smart-select-trigger]');
          const menu = select.querySelector('[data-smart-select-menu]');
          const input = select.querySelector('input[type="hidden"]');
          const current = select.querySelector('[data-smart-select-current]');
          const options = Array.from(select.querySelectorAll('[data-smart-select-option]'));

          if(!trigger || !menu || !input || !current || !options.length) return;

          const close = () => {
            select.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
          };

          const open = () => {
            document.querySelectorAll('[data-smart-select].is-open').forEach(other => {
              if(other !== select){
                other.classList.remove('is-open');
                other.querySelector('[data-smart-select-trigger]')?.setAttribute('aria-expanded', 'false');
              }
            });
            select.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
          };

          trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            select.classList.contains('is-open') ? close() : open();
          });

          options.forEach(option => {
            option.addEventListener('click', (e) => {
              e.preventDefault();

              const value = option.dataset.value || '';
              const label = option.dataset.label || option.textContent.trim();
              const dot = option.querySelector('.smartSelect__dot')?.outerHTML || '';

              input.value = value;
              current.innerHTML = `${dot}<span>${escapeHtml(label)}</span>`;

              options.forEach(opt => opt.classList.remove('is-active'));
              option.classList.add('is-active');

              close();
              input.dispatchEvent(new Event('change', { bubbles:true }));
            });
          });

          select.addEventListener('keydown', (e) => {
            if(e.key === 'Escape'){
              close();
              trigger.focus();
            }
          });
        });

        if(!document.body.dataset.smartSelectBound){
          document.body.dataset.smartSelectBound = '1';

          document.addEventListener('click', (e) => {
            document.querySelectorAll('[data-smart-select].is-open').forEach(select => {
              if(!select.contains(e.target)){
                select.classList.remove('is-open');
                select.querySelector('[data-smart-select-trigger]')?.setAttribute('aria-expanded', 'false');
              }
            });
          });
        }
      }

      function safeJsonParse(text){
        try{
          return text ? JSON.parse(text) : null;
        }catch(_e){
          return null;
        }
      }

      function hasMeaningfulItems(items){
        return Array.isArray(items) && items.some(r =>
          cleanTxt(r?.item_name) ||
          num(r?.qty) > 0 ||
          num(r?.unit_price) > 0 ||
          num(r?.line_total) > 0
        );
      }

      function hasMeaningfulDoc(doc){
        return !!(
          cleanTxt(doc?.supplier_name) ||
          cleanTxt(doc?.document_datetime) ||
          num(doc?.subtotal) > 0 ||
          num(doc?.tax) > 0 ||
          num(doc?.total) > 0
        );
      }

      function hasMeaningfulExtract(doc, items){
        return hasMeaningfulDoc(doc) || hasMeaningfulItems(items);
      }

      function isAiBusy(){
        return aiRunningCount > 0 || batchAiRunning;
      }

      function beginAiWork(){
        aiRunningCount++;
        updateSubmitAvailability();
      }

      function endAiWork(){
        aiRunningCount = Math.max(0, aiRunningCount - 1);
        updateSubmitAvailability();
      }

      function manualPayloadReady(){
        const payload = safeJsonParse(aiPayloadHidden.value);
        return hasMeaningfulExtract(payload?.document, payload?.items);
      }

      function singleAiPayloadReady(){
        const payload = safeJsonParse(aiPayloadHidden.value);
        return hasMeaningfulExtract(payload?.document, payload?.items);
      }

      function bulkPayloadReady(filesCount){
        const payloads = safeJsonParse(aiPayloadBulkHidden.value);
        if(!Array.isArray(payloads) || payloads.length !== filesCount) return false;
        return payloads.every(p => hasMeaningfulExtract(p?.document, p?.items || []));
      }

      function updateSubmitAvailability(){
        const files = Array.from(fileInput.files || []);
        let canSave = false;
        let reason = '';

        if(!files.length){
          reason = 'Selecciona al menos un archivo.';
        } else if (isAiBusy()){
          reason = 'Espera a que termine el análisis de IA.';
        } else if (isSale() && !companyInput?.value){
          reason = 'Selecciona una compañía para la venta.';
        } else if (files.length > 1){
          canSave = bulkPayloadReady(files.length);
          reason = canSave ? '' : 'Debes tener datos válidos en todos los documentos del lote.';
        } else if (aiSkipHidden.value === '1' || currentMode === 'manual'){
          canSave = manualPayloadReady();
          reason = canSave ? '' : 'En modo manual debes capturar al menos un concepto o datos válidos.';
        } else {
          canSave = singleAiPayloadReady();
          reason = canSave ? '' : 'No puedes guardar hasta que la IA arroje información válida.';
        }

        submitBtn.disabled = !canSave;
        submitBtn.title = reason;

        if(!canSave){
          submitBtn.classList.remove('loading','btnPulse');
          if(submitBtn.dataset.originalHtml){
            submitBtn.innerHTML = submitBtn.dataset.originalHtml;
          }
        }
      }

      function toggleSalesFields(){
        const sale = isSale();

        salesAccountingBox?.classList.toggle('hidden', !sale);

        if (companyInput) {
          companyInput.required = sale;
        }

        if (sale) {
          setBanner('En venta, el documento quedará ligado automáticamente a cuentas por cobrar.', 'info');
        } else {
          if (!Array.from(fileInput.files || []).length) {
            setBanner('', 'info');
          }
        }

        refreshPartyPlaceholders();
        updateSubmitAvailability();
      }

      function toggleView(mode){
        currentMode = mode || '';
        aiResult.classList.toggle('hidden', mode !== 'ai');
        manualBox.classList.toggle('hidden', mode !== 'manual');
        multiBox.classList.toggle('hidden', mode !== 'multi');
        btnClearAi.classList.toggle('hidden', mode !== 'ai');
        updateSubmitAvailability();
      }

      function syncAiDocFromInputs(){
        aiDoc.supplier_name = cleanTxt(docSupplier?.value || '');
        aiDoc.document_datetime = fromDatetimeLocal(docDatetime?.value || '');
      }

      docSupplier?.addEventListener('input', () => { syncAiDocFromInputs(); updateTotalsSingle(); });
      docDatetime?.addEventListener('change', () => { syncAiDocFromInputs(); updateTotalsSingle(); });
      companyInput?.addEventListener('change', updateSubmitAvailability);

      document.querySelectorAll('input[name="category"]').forEach(r => {
        r.addEventListener('change', () => {
          toggleSalesFields();
          handleSelection();
        });
      });

      function fingerprint(f){
        return `${f.name}|${f.size}|${f.type || ''}`;
      }

      function buildFpInputs(){
        fpInputs.innerHTML = '';
        Array.from(fileInput.files || []).forEach(f => {
          const inp = document.createElement('input');
          inp.type = 'hidden';
          inp.name = 'file_fps[]';
          inp.value = fingerprint(f);
          fpInputs.appendChild(inp);
        });
      }

      function updateMultiBulkHidden(){
        const payloads = [];
        for (const [fp, o] of multi.entries()){
          payloads.push({
            fp,
            document: o.doc || null,
            items: o.items || [],
            notes: o.notes || null
          });
        }
        aiPayloadBulkHidden.value = payloads.length ? JSON.stringify(payloads) : '';
        updateSubmitAvailability();
      }

      async function extractSingleFile(file){
        const fd = new FormData();
        fd.append('file', file);
        fd.append('category', categoryVal());

        const res = await fetch("{{ route('publications.ai.extract') }}", {
          method: 'POST',
          headers: {'X-CSRF-TOKEN': "{{ csrf_token() }}", 'Accept':'application/json'},
          body: fd
        });

        const data = await res.json();
        if(!res.ok) throw new Error(data.error || 'Error en extracción');
        return data;
      }

      function makeEmptyDoc(){
        return {
          document_type: 'otro',
          supplier_name: null,
          currency: 'MXN',
          document_datetime: null,
          subtotal: 0,
          tax: 0,
          total: 0,
          tax_mode: 'manual',
          tax_rate: currentTaxRate(),
          category: categoryVal()
        };
      }

      function recalcRowModel(r){
        const q = num(r.qty) || 0;
        const p = num(r.unit_price) || 0;
        const lt = num(r.line_total) || 0;
        if(lt <= 0 && q > 0 && p > 0) r.line_total = q * p;
        if(p <= 0 && q > 0 && lt > 0) r.unit_price = lt / q;
      }

      function computeTotalsFromItems(items, included){
        const base = (items || []).reduce((acc, r) => acc + (num(r.line_total) || 0), 0);
        const rate = currentTaxRate();

        let subtotal = 0, iva = 0, total = 0;

        if(included){
          total = base;
          subtotal = (rate > 0) ? (total / (1 + rate)) : total;
          iva = total - subtotal;
        } else {
          subtotal = base;
          iva = subtotal * rate;
          total = subtotal + iva;
        }

        return { subtotal: round2(subtotal), tax: round2(iva), total: round2(total), rate };
      }

      function renderMultiList(){
        multiList.innerHTML = '';

        const files = Array.from(fileInput.files || []);
        if(!files.length) return;

        files.forEach((f) => {
          const fp = fingerprint(f);
          if(!multi.has(fp)){
            multi.set(fp, {
              fp,
              file: f,
              status: 'queued',
              err: null,
              doc: null,
              items: [],
              notes: null,
              taxIncluded: true,
              expanded: false,
            });
          }

          const o = multi.get(fp);

          const pillClass = o.status === 'ok' ? 'ok' : (o.status === 'run' ? 'run' : (o.status === 'fail' ? 'fail' : ''));
          const pillText  = o.status === 'ok' ? 'Listo' : (o.status === 'run' ? 'Analizando' : (o.status === 'fail' ? 'Error' : 'En cola'));

          const supplier = o.doc?.supplier_name || '—';
          const dt = o.doc?.document_datetime ? toDatetimeLocal(o.doc.document_datetime).replace('T',' ') : '—';
          const total = (o.doc?.total != null) ? money(o.doc.total) : '—';
          const itemsCount = (o.items?.length ?? 0);

          const wrap = document.createElement('div');
          wrap.className = 'multiItem';
          wrap.innerHTML = `
            <div class="miHead">
              <div class="miLeft">
                <div class="miIcon">${iconFileHtml}</div>
                <div style="min-width:0">
                  <div class="miName" title="${escapeHtml(f.name)}">${escapeHtml(f.name)}</div>
                  <div class="miMeta">
                    <span>${escapeHtml(partyLabel())}: <b>${escapeHtml(supplier)}</b></span>
                    <span>Fecha: <b>${escapeHtml(dt)}</b></span>
                    <span>Total: <b>${escapeHtml(total)}</b></span>
                    <span>Items: <b>${itemsCount}</b></span>
                  </div>
                  ${o.err ? `<div class="miMeta" style="color:var(--rose-ink);">Error: <b>${escapeHtml(o.err)}</b></div>` : ``}
                </div>
              </div>

              <div class="miPills">
                <span class="pill ${pillClass}">${pillText}</span>
                ${o.notes?.warnings?.length ? `<span class="pill" title="Warnings">${o.notes.warnings.length} warning(s)</span>` : ``}
                ${o.notes?.confidence != null ? `<span class="pill" title="Confianza">${Number(o.notes.confidence).toFixed(2)}</span>` : ``}
              </div>
            </div>

            <div class="miBody ${o.expanded ? '' : 'hidden'}" data-body="1"></div>

            <div style="padding:10px 12px; border-top:1px solid rgba(15,23,42,.06); display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap;">
              <div class="miActions">
                <button type="button" class="btnx ghost tiny" data-act="toggle">${o.expanded ? 'Cerrar' : 'Editar'}</button>
                <button type="button" class="btnx blue tiny" data-act="analyze" ${o.status==='run'?'disabled':''}>Analizar</button>
                <button type="button" class="btnx amber tiny" data-act="manual">Manual</button>
              </div>
              <div class="miActions">
                <button type="button" class="btnx rose tiny" data-act="remove">Quitar</button>
              </div>
            </div>
          `;

          const body = wrap.querySelector('[data-body="1"]');

          function buildEditor(){
            if(!o.doc) o.doc = makeEmptyDoc();
            if(!Array.isArray(o.items)) o.items = [];

            o.doc.category = categoryVal();

            const included = !!o.taxIncluded;
            const totals = computeTotalsFromItems(o.items, included);

            o.doc.subtotal = totals.subtotal;
            o.doc.tax = totals.tax;
            o.doc.total = totals.total;
            o.doc.tax_mode = included ? 'included' : 'add';
            o.doc.tax_rate = totals.rate;

            const supplierVal = o.doc.supplier_name ?? '';
            const dtVal = toDatetimeLocal(o.doc.document_datetime ?? '');

            body.innerHTML = `
              <div class="tableWrap">
                <div class="docMetaRow">
                  <input class="miniField" data-k="supplier" placeholder="${escapeHtml(partyLabel())}" value="${escapeHtml(supplierVal)}">
                  <input class="miniField" data-k="datetime" type="datetime-local" placeholder="Fecha operación" value="${escapeHtml(dtVal)}">
                </div>

                <div class="tblHeader">
                  <div>Concepto</div>
                  <div style="text-align:right;">Cant.</div>
                  <div style="text-align:right;">Precio</div>
                  <div style="text-align:right;">Total</div>
                  <div>Unidad</div>
                  <div></div>
                </div>

                <div class="aiRowsArea" data-rows="1"></div>

                <div class="tblFooter">
                  <div style="display:flex; gap:10px; align-items:flex-end;">
                    <button type="button" class="btnx ghost tiny" data-add="1">+ Fila</button>

                    <div class="ivaToggle">
                      <input type="checkbox" data-tax="1" ${included?'checked':''}>
                      <label>Total ya incluye IVA</label>
                    </div>
                  </div>

                  <div class="totalsBox">
                    <div class="totRow"><span>Subtotal</span><strong data-sub="1">${money(totals.subtotal)}</strong></div>
                    <div class="totRow"><span>IVA (16%)</span><strong data-taxv="1">${money(totals.tax)}</strong></div>
                    <div class="totBig">
                      <div><small>Total documento</small></div>
                      <div><span data-tot="1">${money(totals.total)}</span></div>
                    </div>
                  </div>
                </div>
              </div>
            `;

            const rowsArea = body.querySelector('[data-rows="1"]');
            const subEl = body.querySelector('[data-sub="1"]');
            const taxEl = body.querySelector('[data-taxv="1"]');
            const totEl = body.querySelector('[data-tot="1"]');
            const taxChk = body.querySelector('[data-tax="1"]');

            function syncTotalsUI(){
              const t = computeTotalsFromItems(o.items, !!o.taxIncluded);
              o.doc.subtotal = t.subtotal;
              o.doc.tax = t.tax;
              o.doc.total = t.total;
              o.doc.tax_mode = o.taxIncluded ? 'included' : 'add';
              o.doc.tax_rate = t.rate;

              subEl.textContent = money(t.subtotal);
              taxEl.textContent = money(t.tax);
              totEl.textContent = money(t.total);

              updateMultiBulkHidden();
            }

            function renderRows(){
              rowsArea.innerHTML = '';
              o.items.forEach((r, ridx) => {
                const rr = document.createElement('div');
                rr.className = 'aiEditGrid';
                rr.innerHTML = `
                  <textarea class="miniArea" data-k="item_name" placeholder="Descripción">${escapeHtml(r.item_name || '')}</textarea>
                  <input class="miniField num" data-k="qty" value="${(num(r.qty)||1)}" placeholder="1">
                  <input class="miniField num" data-k="unit_price" value="${num(r.unit_price).toFixed(2)}" placeholder="0.00">
                  <input class="miniField num" data-k="line_total" value="${num(r.line_total).toFixed(2)}" placeholder="0.00">
                  <input class="miniField" data-k="unit" value="${escapeHtml(r.unit || '')}" placeholder="pza">
                  <button type="button" class="iconBtn" data-del="1" title="Eliminar">✕</button>
                `;

                rr.querySelectorAll('input, textarea').forEach(inp => {
                  inp.addEventListener('input', (e) => {
                    const k = e.target.dataset.k;
                    const v = e.target.value;

                    if(k === 'item_name' || k === 'unit') r[k] = v;
                    else r[k] = num(v);

                    if(k === 'qty' || k === 'unit_price'){
                      const q = num(r.qty) || 0;
                      const p = num(r.unit_price) || 0;
                      r.line_total = q * p;
                      const tInp = rr.querySelector('[data-k="line_total"]');
                      if(tInp) tInp.value = num(r.line_total).toFixed(2);
                    } else if(k === 'line_total'){
                      const q = num(r.qty) || 0;
                      if(q > 0){
                        r.unit_price = num(r.line_total) / q;
                        const pInp = rr.querySelector('[data-k="unit_price"]');
                        if(pInp) pInp.value = num(r.unit_price).toFixed(2);
                      }
                    }

                    if(e.target.tagName === 'TEXTAREA') autoGrowTextarea(e.target);
                    syncTotalsUI();
                  });

                  if(inp.classList.contains('num')){
                    inp.addEventListener('blur', (e) => {
                      if(e.target.dataset.k === 'qty') e.target.value = (num(e.target.value) || 1).toString();
                      else e.target.value = num(e.target.value).toFixed(2);
                    });
                  }
                });

                autoGrowTextarea(rr.querySelector('textarea'));

                rr.querySelector('[data-del="1"]').addEventListener('click', () => {
                  o.items.splice(ridx, 1);
                  renderRows();
                  syncTotalsUI();
                });

                rowsArea.appendChild(rr);
              });

              syncTotalsUI();
            }

            const inpSupplier = body.querySelector('[data-k="supplier"]');
            const inpDt = body.querySelector('[data-k="datetime"]');

            inpSupplier.addEventListener('input', () => {
              o.doc.supplier_name = cleanTxt(inpSupplier.value) || null;
              updateMultiBulkHidden();
              renderMultiList();
            });

            inpDt.addEventListener('change', () => {
              o.doc.document_datetime = fromDatetimeLocal(inpDt.value) || null;
              updateMultiBulkHidden();
              renderMultiList();
            });

            taxChk.addEventListener('change', () => {
              o.taxIncluded = !!taxChk.checked;
              syncTotalsUI();
              renderMultiList();
            });

            body.querySelector('[data-add="1"]').addEventListener('click', () => {
              o.items.push({ item_name:'', qty:1, unit_price:0, line_total:0, unit:'pza' });
              renderRows();
              rowsArea.scrollTop = rowsArea.scrollHeight;
            });

            if(o.items.length === 0){
              o.items.push({ item_name:'', qty:1, unit_price:0, line_total:0, unit:'pza' });
            }

            renderRows();
            updateMultiBulkHidden();
          }

          wrap.querySelector('[data-act="toggle"]').addEventListener('click', () => {
            o.expanded = !o.expanded;
            renderMultiList();
          });

          wrap.querySelector('[data-act="analyze"]').addEventListener('click', async (e) => {
            await analyzeOne(fp, e.currentTarget);
          });

          wrap.querySelector('[data-act="manual"]').addEventListener('click', () => {
            o.err = null;
            o.status = 'ok';
            o.notes = { warnings: ['Manual'], confidence: 0.0 };
            o.doc = makeEmptyDoc();
            o.items = [{ item_name:'', qty:1, unit_price:0, line_total:0, unit:'pza' }];
            o.taxIncluded = true;
            o.expanded = true;
            updateMultiBulkHidden();
            renderMultiList();
            setBanner('Documento en modo manual. Debes capturar datos válidos para habilitar Guardar.', 'warn');
          });

          wrap.querySelector('[data-act="remove"]').addEventListener('click', () => {
            const filesNow = Array.from(fileInput.files || []);
            const keep = filesNow.filter(x => fingerprint(x) !== fp);

            const dt = new DataTransfer();
            keep.forEach(x => dt.items.add(x));
            fileInput.files = dt.files;

            multi.delete(fp);
            buildFpInputs();
            updateMultiBulkHidden();
            handleSelection();
          });

          multiList.appendChild(wrap);

          if(o.expanded){
            buildEditor();
          }
        });

        updateMultiBulkHidden();
      }

      async function analyzeOne(fp, triggerBtn = null){
        const o = multi.get(fp);
        if(!o) return;
        if(o.status === 'run') return;

        o.status = 'run';
        o.err = null;
        renderMultiList();

        const files = Array.from(fileInput.files || []);
        const idx = files.findIndex(x => fingerprint(x) === fp);
        const totalN = Math.max(1, files.length);

        showOverlay(true, `Analizando ${idx+1}/${totalN}`, o.file?.name || '—', ((idx) / totalN) * 100);
        beginAiWork();
        setBtnLoading(triggerBtn, true, 'Analizando...');

        try{
          const data = await extractSingleFile(o.file);

          const doc = data.document || {};
          const items = Array.isArray(data.items) ? data.items : [];
          const notes = data.notes || {};

          const normItems = ensureAtLeastOneRow(items.map(it => ({
            item_name: cleanTxt(it.item_name || ''),
            qty: num(it.qty) || 1,
            unit_price: num(it.unit_price) || 0,
            line_total: num(it.line_total) || 0,
            unit: cleanTxt(it.unit || 'pza')
          })));
          normItems.forEach(recalcRowModel);

          if(!hasMeaningfulExtract(doc, normItems)){
            throw new Error('La IA no detectó información utilizable en este documento.');
          }

          const totals = computeTotalsFromItems(normItems, true);

          o.doc = {
            document_type: doc.document_type || 'otro',
            supplier_name: cleanTxt(doc.supplier_name || '') || null,
            currency: doc.currency || 'MXN',
            document_datetime: cleanTxt(doc.document_datetime || '') || null,
            subtotal: totals.subtotal,
            tax: totals.tax,
            total: totals.total,
            tax_mode: 'included',
            tax_rate: totals.rate,
            category: categoryVal()
          };

          o.items = normItems;
          o.notes = notes;
          o.taxIncluded = true;
          o.status = 'ok';
          o.expanded = true;

          updateMultiBulkHidden();
          renderMultiList();
          if(data.warning){
            setBanner(data.warning, 'warn');
          }else{
            setBanner('Extracción completada. Revisa y ajusta cada documento antes de guardar.', 'info');
          }
        }catch(e){
          o.status = 'fail';
          o.err = e.message || 'No se pudo extraer';
          o.notes = { warnings: ['AI failed'], confidence: 0.0 };
          setBanner(o.err || 'No se pudo extraer este documento.', 'error');
          renderMultiList();
        } finally {
          showOverlay(false);
          endAiWork();
          setBtnLoading(triggerBtn, false);
        }
      }

      async function analyzeAll(){
        const files = Array.from(fileInput.files || []);
        if(files.length <= 1) return;

        batchAiRunning = true;
        updateSubmitAvailability();
        toggleState('run');
        aiStatus.textContent = `Analizando ${files.length} documento(s) con IA...`;
        setBtnLoading(btnRetry, true, 'Analizando lote...');

        try{
          for(let i=0; i<files.length; i++){
            const fp = fingerprint(files[i]);
            await analyzeOne(fp);
          }

          showOverlay(false);
          const allReady = bulkPayloadReady(files.length);
          toggleState(allReady ? 'ok' : 'fail');
          aiStatus.textContent = allReady
            ? 'Listo. Revisa/edita cada documento antes de guardar.'
            : 'Faltan documentos con información válida. Completa o corrige antes de guardar.';
          setBanner(
            allReady
              ? 'Análisis terminado. Puedes editar cada documento antes de guardar.'
              : 'No se habilitará Guardar hasta que todos los documentos del lote tengan información válida.',
            allReady ? 'info' : 'warn'
          );
        } finally {
          batchAiRunning = false;
          updateSubmitAvailability();
          setBtnLoading(btnRetry, false);
        }
      }

      async function aiExtractAutoSingle(){
        const f = fileInput.files[0];
        if(!f) return;

        beginAiWork();
        toggleState('run');
        aiStatus.innerText = 'Analizando documento con IA...';
        setBtnLoading(btnRetry, true, 'Analizando IA...');

        const fd = new FormData();
        fd.append('file', f);
        fd.append('category', categoryVal());

        try{
          const res = await fetch("{{ route('publications.ai.extract') }}", {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': "{{ csrf_token() }}", 'Accept':'application/json'},
            body: fd
          });
          const data = await res.json();
          if(!res.ok) throw new Error(data.error || 'Error en extracción');

          const extractedDoc = {
            supplier_name: cleanTxt(data?.document?.supplier_name || ''),
            document_datetime: cleanTxt(data?.document?.document_datetime || ''),
            subtotal: num(data?.document?.subtotal || 0),
            tax: num(data?.document?.tax || 0),
            total: num(data?.document?.total || 0)
          };

          aiRows = ensureAtLeastOneRow((data.items || []).map(it => ({
            item_name: cleanTxt(it.item_name),
            qty: num(it.qty) || 1,
            unit_price: num(it.unit_price),
            line_total: num(it.line_total),
            unit: cleanTxt(it.unit) || 'pza'
          })));

          aiRows.forEach(recalcRowModel);

          if(!hasMeaningfulExtract(extractedDoc, aiRows)){
            aiRows = [];
            aiPayloadHidden.value = '';
            throw new Error('La IA no devolvió información utilizable. No se puede guardar hasta corregir o capturar manualmente.');
          }

          aiDoc = {
            supplier_name: extractedDoc.supplier_name,
            document_datetime: extractedDoc.document_datetime
          };
          docSupplier.value = aiDoc.supplier_name || '';
          docDatetime.value = toDatetimeLocal(aiDoc.document_datetime || '');

          renderAiEditorSingle();
          toggleView('ai');
          toggleState('ok');
          aiStatus.innerText = 'Revisa los datos extraídos. Puedes editar, agregar o borrar filas.';
          if(data.warning){
            setBanner(data.warning, 'warn');
          }else{
            setBanner('Extracción completada. Verifica el encabezado y los conceptos antes de guardar.', 'info');
          }
        }catch(e){
          console.error(e);
          toggleState('fail');
          aiStatus.innerText = e.message || 'No se pudo extraer información. Intenta manual.';
          setBanner(e.message || 'No se pudo analizar el archivo.', 'error');
          aiPayloadHidden.value = '';
          updateSubmitAvailability();
        } finally {
          endAiWork();
          setBtnLoading(btnRetry, false);
        }
      }

      function renderAiEditorSingle(){
        aiEditRows.innerHTML = '';
        aiRows.forEach((r, idx) => {
          const div = document.createElement('div');
          div.className = 'aiEditGrid';
          div.innerHTML = `
            <textarea class="miniArea" data-k="item_name" placeholder="Descripción">${escapeHtml(r.item_name)}</textarea>
            <input class="miniField num" data-k="qty" value="${(num(r.qty)||1)}" placeholder="1">
            <input class="miniField num" data-k="unit_price" value="${num(r.unit_price).toFixed(2)}" placeholder="0.00">
            <input class="miniField num" data-k="line_total" value="${num(r.line_total).toFixed(2)}" placeholder="0.00">
            <input class="miniField" data-k="unit" value="${escapeHtml(r.unit || '')}" placeholder="pza">
            <button type="button" class="iconBtn" data-del="${idx}" title="Eliminar">✕</button>
          `;

          div.querySelectorAll('input, textarea').forEach(inp => {
            inp.addEventListener('input', (e) => {
              updateAiModelSingle(idx, e.target.dataset.k, e.target.value);
              if(e.target.tagName === 'TEXTAREA') autoGrowTextarea(e.target);
            });

            if(inp.classList.contains('num')){
              inp.addEventListener('blur', (e) => {
                if(e.target.dataset.k === 'qty') e.target.value = (num(e.target.value) || 1).toString();
                else e.target.value = num(e.target.value).toFixed(2);
              });
            }
          });

          autoGrowTextarea(div.querySelector('textarea[data-k="item_name"]'));

          div.querySelector('[data-del]').addEventListener('click', () => {
            aiRows.splice(idx, 1);
            renderAiEditorSingle();
          });

          aiEditRows.appendChild(div);
        });

        updateTotalsSingle();
      }

      function updateAiModelSingle(idx, key, val){
        if(!aiRows[idx]) return;

        if(key === 'item_name' || key === 'unit') aiRows[idx][key] = val;
        else aiRows[idx][key] = num(val);

        if(key === 'qty' || key === 'unit_price'){
          const q = num(aiRows[idx].qty) || 0;
          const p = num(aiRows[idx].unit_price) || 0;
          aiRows[idx].line_total = q * p;
          const totalInp = aiEditRows.children[idx]?.querySelector('[data-k="line_total"]');
          if(totalInp) totalInp.value = num(aiRows[idx].line_total).toFixed(2);
        } else if(key === 'line_total'){
          const q = num(aiRows[idx].qty) || 0;
          if(q > 0){
            aiRows[idx].unit_price = num(aiRows[idx].line_total) / q;
            const priceInp = aiEditRows.children[idx]?.querySelector('[data-k="unit_price"]');
            if(priceInp) priceInp.value = num(aiRows[idx].unit_price).toFixed(2);
          }
        }

        updateTotalsSingle();
      }

      btnAiAddRow.addEventListener('click', () => {
        aiRows.push({item_name:'', qty:1, unit_price:0, line_total:0, unit:'pza'});
        renderAiEditorSingle();
        aiEditRows.scrollTop = aiEditRows.scrollHeight;
      });

      taxIncluded.addEventListener('change', () => updateTotalsSingle());

      function updateTotalsSingle(){
        syncAiDocFromInputs();

        const base = aiRows.reduce((acc, r) => acc + (num(r.line_total) || 0), 0);
        const rate = currentTaxRate();

        let subtotal = 0, iva = 0, total = 0;

        if(taxIncluded.checked){
          total = base;
          subtotal = (rate > 0) ? (total / (1 + rate)) : total;
          iva = total - subtotal;
          if(taxModeHidden) taxModeHidden.value = 'included';
        }else{
          subtotal = base;
          iva = subtotal * rate;
          total = subtotal + iva;
          if(taxModeHidden) taxModeHidden.value = 'add';
        }

        aiSubtotalEl.textContent = money(subtotal);
        aiTaxEl.textContent = money(iva);
        aiTotalEl.textContent = money(total);

        aiPayloadHidden.value = JSON.stringify({
          document: {
            category: categoryVal(),
            supplier_name: aiDoc.supplier_name || null,
            document_datetime: aiDoc.document_datetime || null,
            subtotal: round2(subtotal),
            tax: round2(iva),
            total: round2(total),
            tax_mode: taxIncluded.checked ? 'included' : 'add',
            tax_rate: rate
          },
          items: aiRows.map(r => ({
            item_name: cleanTxt(r.item_name),
            qty: num(r.qty) || 1,
            unit_price: num(r.unit_price),
            line_total: num(r.line_total),
            unit: cleanTxt(r.unit) || 'pza'
          }))
        });

        updateSubmitAvailability();
      }

      btnAddRow.onclick = () => {
        const item_name = cleanTxt(document.getElementById('m_name').value);
        const qty = num(document.getElementById('m_qty').value) || 1;
        const unit_price = num(document.getElementById('m_price').value) || 0;
        const unit = cleanTxt(document.getElementById('m_unit').value) || 'pza';
        if(!item_name) return;

        const row = {item_name, qty, unit_price, unit};
        manualRows.push(row);

        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${escapeHtml(item_name)}</td>
          <td align="right">${qty}</td>
          <td align="right">${money(qty*unit_price)}</td>
          <td><button type="button" class="iconBtn" title="Eliminar">✕</button></td>
        `;
        tr.querySelector('button').addEventListener('click', () => {
          const idx = Array.from(document.getElementById('mTbody').children).indexOf(tr);
          if(idx >= 0) manualRows.splice(idx, 1);
          tr.remove();
          syncManualPayload();
        });
        document.getElementById('mTbody').appendChild(tr);

        ['m_name','m_qty','m_price','m_unit'].forEach(id => document.getElementById(id).value = '');
        syncManualPayload();
      };

      mSupplier?.addEventListener('input', syncManualPayload);
      mDatetime?.addEventListener('change', syncManualPayload);

      function syncManualPayload(){
        const supplier_name = cleanTxt(mSupplier?.value || '');
        const document_datetime = fromDatetimeLocal(mDatetime?.value || '');

        const tot = manualRows.reduce((a,r) => a + (num(r.qty)*num(r.unit_price)), 0);
        document.getElementById('mTotal').textContent = money(tot);

        aiPayloadHidden.value = JSON.stringify({
          document: {
            category: categoryVal(),
            supplier_name: supplier_name || null,
            document_datetime: document_datetime || null,
            subtotal: round2(tot),
            tax: 0,
            total: round2(tot),
            tax_mode: 'manual'
          },
          items: manualRows.map(r => ({
            item_name: r.item_name,
            qty: r.qty,
            unit_price: r.unit_price,
            line_total: round2(num(r.qty)*num(r.unit_price)),
            unit: r.unit || 'pza'
          }))
        });

        updateSubmitAvailability();
      }

      function handleSelection(){
        const files = Array.from(fileInput.files || []);
        buildFpInputs();

        if(!files.length){
          fileNameEl.textContent = 'Seleccionar archivo(s)...';
          fileSizeEl.textContent = 'Max 10MB';
          btnRetry.disabled = true;
          aiStatus.textContent = 'Esperando archivo(s)...';
          toggleView('');
          toggleState('');
          aiPayloadHidden.value = '';
          aiPayloadBulkHidden.value = '';
          manualRows = [];
          aiRows = [];
          multi.clear();
          if (!isSale()) setBanner('');
          updateSubmitAvailability();
          return;
        }

        const totalBytes = files.reduce((a,f)=>a+(f.size||0),0);
        fileNameEl.textContent = (files.length === 1) ? files[0].name : `${files.length} archivos`;
        fileSizeEl.textContent = (files.length === 1) ? `${(files[0].size/1024/1024).toFixed(2)} MB` : `${(totalBytes/1024/1024).toFixed(2)} MB total`;

        aiSkipHidden.value = '0';
        aiExtractHidden.value = '1';

        if(files.length === 1){
          btnRetry.disabled = false;
          btnRetry.textContent = 'Reintentar IA';
          aiStatus.textContent = 'Archivo listo. Analizando con IA...';
          setBanner('Extrayendo encabezado y conceptos del documento...', 'info');
          toggleView('ai');
          aiPayloadBulkHidden.value = '';
          multi.clear();
          updateSubmitAvailability();
          aiExtractAutoSingle();
        } else {
          btnRetry.disabled = false;
          btnRetry.textContent = 'Analizar IA';
          aiStatus.textContent = `Listo. Se analizarán ${files.length} documentos aquí mismo (editable antes de guardar).`;
          setBanner('Lote listo. El guardado se habilitará cuando todos los documentos tengan información válida.', 'info');
          toggleView('multi');
          toggleState('');
          aiPayloadHidden.value = '';

          files.forEach(f => {
            const fp = fingerprint(f);
            if(!multi.has(fp)){
              multi.set(fp, { fp, file:f, status:'queued', err:null, doc:null, items:[], notes:null, taxIncluded:true, expanded:false });
            }
          });

          renderMultiList();
          updateSubmitAvailability();
          analyzeAll();
        }
      }

      fileInput.addEventListener('change', handleSelection);

      const browseLabel = document.querySelector('label[for="f-file"]');
      browseLabel?.addEventListener('click', (e) => {
        e.stopPropagation();
      });

      dropZone.addEventListener('click', (e) => {
        if (e.target.closest('label[for="f-file"]')) return;
        fileInput.click();
      });

      btnRetry.addEventListener('click', () => {
        const files = Array.from(fileInput.files || []);
        if(files.length === 1) aiExtractAutoSingle();
        else analyzeAll();
      });

      btnClearAi.addEventListener('click', () => {
        aiRows = [];
        aiEditRows.innerHTML = '';
        aiPayloadHidden.value = '';
        aiDoc = {supplier_name:'', document_datetime:''};
        docSupplier.value = '';
        docDatetime.value = '';
        toggleView('');
        toggleState('');
        aiStatus.innerText = 'Listo. Sube o reintenta con otro archivo.';
        if (!isSale()) setBanner('');
        else setBanner('Venta activa. Cuando guardes se ligará a cuentas por cobrar.', 'info');
        updateTotalsSingle();
        updateSubmitAvailability();
      });

      btnSkipIA.addEventListener('click', () => {
        const files = Array.from(fileInput.files || []);
        if(files.length > 1){
          aiSkipHidden.value = '1';
          aiExtractHidden.value = '0';
          aiPayloadBulkHidden.value = '';
          aiStatus.textContent = `Modo manual por documento. Debes completar cada archivo antes de que se habilite Guardar.`;
          setBanner('En lote, debes capturar o completar información válida en cada documento antes de guardar.', 'warn');
          toggleState('');
          renderMultiList();
          updateSubmitAvailability();
          return;
        }

        toggleView('manual');
        toggleState('');
        aiStatus.innerText = 'Captura manual habilitada.';
        setBanner('Captura manual activa. Debes agregar información válida para habilitar Guardar.', 'warn');
        aiSkipHidden.value = '1';
        aiExtractHidden.value = '0';
        syncManualPayload();
      });

      form.addEventListener('submit', (e) => {
        const files = Array.from(fileInput.files || []);
        buildFpInputs();

        if (isSale() && !companyInput?.value) {
          e.preventDefault();
          setBanner('Para guardar una venta debes seleccionar una compañía.', 'error');
          companyInput?.focus();
          updateSubmitAvailability();
          return;
        }

        if(!files.length){
          e.preventDefault();
          setBanner('Debes seleccionar al menos un archivo.', 'error');
          updateSubmitAvailability();
          return;
        }

        if(isAiBusy()){
          e.preventDefault();
          setBanner('Espera a que termine el análisis de IA antes de guardar.', 'warn');
          updateSubmitAvailability();
          return;
        }

        if(files.length > 1){
          if(!bulkPayloadReady(files.length)){
            e.preventDefault();
            setBanner('No puedes guardar todavía. Todos los documentos del lote deben tener información válida.', 'error');
            updateSubmitAvailability();
            return;
          }
          aiExtractHidden.value = '0';
          aiSkipHidden.value = '0';
        } else {
          if(aiSkipHidden.value === '1' || currentMode === 'manual'){
            if(!manualPayloadReady()){
              e.preventDefault();
              setBanner('En modo manual debes capturar información válida antes de guardar.', 'error');
              updateSubmitAvailability();
              return;
            }
          } else {
            if(!singleAiPayloadReady()){
              e.preventDefault();
              setBanner('No puedes guardar hasta que la IA arroje información válida del documento.', 'error');
              updateSubmitAvailability();
              return;
            }
          }
        }

        setBtnLoading(submitBtn, true, 'Guardando...');
        submitBtn.disabled = true;
      });

      initSmartSelects();
      refreshPartyPlaceholders();
      toggleSalesFields();
      toggleView('');
      toggleState('');
      updateSubmitAvailability();
    });
  </script>
</div>
@endsection