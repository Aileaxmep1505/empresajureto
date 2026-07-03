{{-- resources/views/ruta/show.blade.php --}}
@extends('layouts.app')
@section('title','Mi ruta')
@section('content_class', 'content--flush')
@section('content')
@php
  /**
   * ✅ Debug server-side (Laravel log)
   */
  try {
    \Illuminate\Support\Facades\Log::info('driver.routes.show view boot', [
      'plan_id' => $routePlan->id ?? null,
      'stops_count' => isset($stops) ? (is_countable($stops) ? count($stops) : null) : null,
      'has_driver' => isset($routePlan->driver),
      'sequence_locked' => (bool)($routePlan->sequence_locked ?? false),
      'start' => [
        'lat' => $routePlan->start_lat ?? null,
        'lng' => $routePlan->start_lng ?? null,
      ],
    ]);
  } catch (\Throwable $e) {}
@endphp

{{-- Tipografía Quicksand + iconos --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>

<div class="container-fluid p-0" id="rp-driver-pro">
  <style>
    /* ==========================================================
       NAMESPACE #rp-driver-pro
       Sistema de diseño limpio / minimalista (Quicksand, blanco)
       ========================================================== */
    #rp-driver-pro{
      --bg:#f9fafb;          /* Fondo general */
      --card:#ffffff;        /* Contenedores */
      --ink:#333333;         /* Texto principal */
      --ink-strong:#111111;  /* Títulos */
      --muted:#888888;       /* Texto secundario / iconos */
      --line:#ebebeb;        /* Bordes y separadores */
      --blue:#007aff;        /* Primario */
      --blue-soft:#e6f0ff;   /* Hover / badge info */
      --success:#15803d;
      --success-soft:#e6ffe6;
      --danger:#ff4a4a;
      --danger-soft:#ffebeb;
      --amber:#c2660c;
      --amber-soft:#fff4e0;

      color:var(--ink);
      background:var(--bg);
      min-height:calc(100vh - 56px);
      padding:0;
      font-family:'Quicksand', system-ui, -apple-system, 'Segoe UI', sans-serif;
      -webkit-font-smoothing:antialiased;
      text-rendering:optimizeLegibility;
    }
    #rp-driver-pro *{ box-sizing:border-box; }
    #rp-driver-pro,
    #rp-driver-pro .btn,
    #rp-driver-pro input,
    #rp-driver-pro select,
    #rp-driver-pro textarea{
      font-family:'Quicksand', system-ui, -apple-system, 'Segoe UI', sans-serif;
    }
    /* No forzar la tipografía dentro del mapa de Google */
    #rp-driver-pro #map,
    #rp-driver-pro #map *{ font-family:Roboto, Arial, sans-serif !important; }

    /* ===== Fallbacks de utilidades (seguros aunque Bootstrap esté presente) ===== */
    #rp-driver-pro .d-flex{ display:flex; }
    #rp-driver-pro .align-items-center{ align-items:center; }
    #rp-driver-pro .justify-content-between{ justify-content:space-between; }
    #rp-driver-pro .flex-wrap{ flex-wrap:wrap; }
    #rp-driver-pro .gap-2{ gap:.5rem; }
    #rp-driver-pro .mt-2{ margin-top:.5rem; }
    #rp-driver-pro .mb-1{ margin-bottom:.25rem; }
    #rp-driver-pro .mb-2{ margin-bottom:.5rem; }
    #rp-driver-pro .m-0{ margin:0; }
    #rp-driver-pro .small{ font-size:.85rem; }
    #rp-driver-pro .text-uppercase{ text-transform:uppercase; }
    #rp-driver-pro .text-muted{ color:var(--muted) !important; }
    #rp-driver-pro .fw-bold{ font-weight:700; color:var(--ink-strong); }
    #rp-driver-pro .h5{ font-size:1.1rem; font-weight:700; color:var(--ink-strong); }
    #rp-driver-pro .h6, #rp-driver-pro h6{ font-size:.95rem; font-weight:700; color:var(--ink-strong); }
    #rp-driver-pro .muted{ color:var(--muted); }

    /* ===== Layout: 2 columnas ===== */
    #rp-driver-pro .row.g-0{ display:flex; flex-direction:column; gap:14px; padding:16px; }
    #rp-driver-pro .col-lg-3, #rp-driver-pro .col-lg-9{ min-width:0; }
    @media (min-width: 992px){
      #rp-driver-pro .row.g-0{ flex-direction:row; gap:16px; }
      #rp-driver-pro .col-lg-3{ flex:0 0 26%; max-width:26%; }
      #rp-driver-pro .col-lg-9{ flex:1 1 74%; max-width:74%; }
    }

    /* ===== Panel izquierdo ===== */
    #rp-driver-pro .side{ background:transparent; border:none; min-height:auto; }

    #rp-driver-pro .toolbar{
      position:sticky; top:56px; z-index:6;
      background:var(--card); border:1px solid var(--line); border-radius:16px;
      box-shadow:0 4px 12px rgba(0,0,0,.02);
      padding:16px 18px; margin-bottom:14px; gap:12px;
    }
    #rp-driver-pro .side-title{ font-weight:700; font-size:1.02rem; color:var(--ink-strong); line-height:1.25; }
    #rp-driver-pro .side-sub{ font-size:.85rem; color:var(--muted); margin-top:2px; }
    #rp-driver-pro .side-hint{ font-size:.8rem; color:var(--muted); line-height:1.45; }
    #rp-driver-pro .side-hint strong{ color:var(--ink); font-weight:600; }

    #rp-driver-pro .kpi-pill{
      display:inline-flex; align-items:center; gap:.4rem;
      background:var(--blue-soft); color:var(--blue);
      border-radius:999px; padding:.42rem .8rem; font-weight:600; font-size:.85rem;
      white-space:nowrap;
    }

    #rp-driver-pro .gps-actions{ display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
    @media (max-width:575.98px){
      #rp-driver-pro .gps-actions .btn{ width:100%; }
    }

    /* Grid de tarjetas */
    #rp-driver-pro .grid{ display:grid; gap:12px; }
    #rp-driver-pro .side .grid{ padding:0; }
    #rp-driver-pro .g3{ grid-template-columns:repeat(3,minmax(0,1fr)); }
    @media (max-width: 991.98px){ #rp-driver-pro .g3{ grid-template-columns:1fr 1fr; } }
    @media (max-width: 575.98px){ #rp-driver-pro .g3{ grid-template-columns:1fr; } }

    /* ===== Tarjetas ===== */
    #rp-driver-pro .card{
      border:1px solid var(--line); border-radius:16px; background:var(--card);
      box-shadow:0 4px 12px rgba(0,0,0,.02);
      transition:transform .18s ease, box-shadow .18s ease;
    }
    #rp-driver-pro .card-body{ padding:16px 18px; }
    #rp-driver-pro .next:hover,
    #rp-driver-pro .metric:hover{ transform:translateY(-2px); box-shadow:0 10px 22px rgba(0,0,0,.05); }
    #rp-driver-pro .next{ border-left:3px solid var(--blue); }
    #rp-driver-pro .card h6{ color:var(--ink-strong); }

    #rp-driver-pro .metric .label{ font-size:.72rem; color:var(--muted); text-transform:uppercase; letter-spacing:.03em; font-weight:600; }
    #rp-driver-pro .metric .value{ font-weight:700; font-size:1.35rem; color:var(--ink-strong); margin-top:.15rem; }

    /* Skeleton */
    #rp-driver-pro .sk{
      background:linear-gradient(90deg,#f3f4f6 0%, #eef1f5 50%, #f3f4f6 100%);
      background-size:200% 100%;
      animation: rp-shimmer 1.2s infinite linear;
    }
    @keyframes rp-shimmer{ 0%{background-position:0 0} 100%{background-position:-200% 0} }

    /* ===== Mapa en tarjeta ===== */
    #rp-driver-pro .map-card{
      background:var(--card); border:1px solid var(--line); border-radius:18px;
      padding:12px; box-shadow:0 6px 18px rgba(0,0,0,.03);
      height:calc(100vh - 56px); min-height:560px; position:relative;
    }
    #rp-driver-pro .map-card .map{
      width:100%; height:100%; border-radius:14px; overflow:hidden; background:#eef1f5;
    }
    @media (max-width:991.98px){
      #rp-driver-pro .map-card{ height:70vh; min-height:440px; }
    }



    /* ===== Widget de tráfico estilo Google Maps (desktop) ===== */
    #rp-driver-pro .desktop-traffic-widget{
      position:absolute;
      left:50%;
      bottom:18px;
      z-index:700;
      transform:translateX(-50%);
      display:inline-flex;
      align-items:center;
      gap:16px;
      min-height:46px;
      padding:8px 14px;
      border:1px solid var(--line);
      border-radius:12px;
      background:rgba(255,255,255,.94);
      box-shadow:0 8px 24px rgba(0,0,0,.14);
      backdrop-filter:blur(16px);
      -webkit-backdrop-filter:blur(16px);
      font-family:'Quicksand', system-ui, sans-serif;
      pointer-events:auto;
    }
    #rp-driver-pro .traffic-selector{
      display:inline-flex;
      align-items:center;
      gap:8px;
      border:0;
      background:transparent;
      color:var(--ink);
      font-size:14px;
      font-weight:700;
      cursor:pointer;
      white-space:nowrap;
      padding:0;
    }
    #rp-driver-pro .traffic-selector i{
      font-size:11px;
      color:#555555;
    }
    #rp-driver-pro .traffic-scale{
      display:inline-flex;
      align-items:center;
      gap:7px;
      color:#555555;
      font-size:13px;
      font-style:italic;
      font-weight:600;
      white-space:nowrap;
    }
    #rp-driver-pro .traffic-bars{
      display:inline-flex;
      align-items:center;
      gap:3px;
    }
    #rp-driver-pro .traffic-bars .bar{
      display:block;
      width:19px;
      height:8px;
      border-radius:2px;
    }
    #rp-driver-pro .traffic-bars .green{ background:#00b050; }
    #rp-driver-pro .traffic-bars .yellow{ background:#f8d33a; }
    #rp-driver-pro .traffic-bars .orange{ background:#f59e0b; }
    #rp-driver-pro .traffic-bars .red{ background:#ef4444; }
    #rp-driver-pro .traffic-bars .darkred{ background:#991b1b; }
    #rp-driver-pro .traffic-switch{
      position:relative;
      width:45px;
      height:26px;
      margin:0;
      flex:0 0 auto;
    }
    #rp-driver-pro .traffic-switch input{ display:none; }
    #rp-driver-pro .traffic-switch span{
      position:absolute;
      inset:0;
      border-radius:999px;
      background:#d1d5db;
      cursor:pointer;
      transition:background .2s ease;
    }
    #rp-driver-pro .traffic-switch span::after{
      content:"";
      position:absolute;
      top:4px;
      left:4px;
      width:18px;
      height:18px;
      border-radius:999px;
      background:#ffffff;
      box-shadow:0 2px 6px rgba(0,0,0,.18);
      transition:transform .2s ease;
    }
    #rp-driver-pro .traffic-switch input:checked + span{ background:var(--blue); }
    #rp-driver-pro .traffic-switch input:checked + span::after{ transform:translateX(19px); }

    /* En celular no mostramos esta barra porque ahí manda el bottom sheet */
    @media (max-width:991.98px){
      #rp-driver-pro .desktop-traffic-widget{ display:none; }
    }

    /* Overlays dentro del mapa */
    #rp-driver-pro .map-legend{
      position:absolute; left:22px; top:22px; z-index:520;
      background:rgba(255,255,255,.94); backdrop-filter:blur(6px);
      border:1px solid var(--line); border-radius:14px; padding:8px 10px;
      display:flex; flex-wrap:wrap; gap:.4rem;
      box-shadow:0 4px 14px rgba(0,0,0,.04);
    }
    #rp-driver-pro .routes-panel{
      position:absolute; left:22px; bottom:22px; z-index:520;
      background:rgba(255,255,255,.96); backdrop-filter:blur(6px);
      border:1px solid var(--line); border-radius:16px; padding:12px; min-width:260px;
      max-width:min(90%,360px);
      box-shadow:0 8px 24px rgba(0,0,0,.06);
    }
    #rp-driver-pro .routes-list{ display:grid; gap:.5rem; }
    #rp-driver-pro .route-card{ border:1px solid var(--line); border-radius:12px; padding:.6rem .75rem; background:#fff; transition:.18s; }
    #rp-driver-pro .route-card.active{ border-color:var(--blue); box-shadow:0 6px 18px rgba(0,122,255,.12); }
    #rp-driver-pro .route-head{ display:flex; justify-content:space-between; align-items:center; }
    #rp-driver-pro .route-badge{ display:inline-flex; align-items:center; gap:.35rem; border-radius:999px; padding:.2rem .6rem; font-weight:700; font-size:.78rem; }
    #rp-driver-pro .rb-blue{ background:var(--blue-soft); color:var(--blue); }
    #rp-driver-pro .rb-amber{ background:var(--amber-soft); color:var(--amber); }
    #rp-driver-pro .rb-red{ background:var(--danger-soft); color:var(--danger); }
    #rp-driver-pro .small-muted{ font-size:.83rem; color:var(--muted); }

    /* Chips leyenda */
    #rp-driver-pro .chip{
      display:inline-flex; align-items:center; gap:.35rem;
      border:1px solid var(--line); background:#fff; border-radius:999px; padding:.25rem .6rem;
      font-weight:600; font-size:.78rem; color:var(--ink);
    }
    #rp-driver-pro .chip.alt{ background:var(--success-soft); border-color:#c9f2cf; }
    #rp-driver-pro .chip.warn{ background:var(--danger-soft); border-color:#ffd4d4; }

    /* ===== Timeline ===== */
    #rp-driver-pro .timeline{ list-style:none; margin:0; padding:0; position:relative; }
    #rp-driver-pro .timeline:before{ content:""; position:absolute; left:14px; top:4px; bottom:4px; width:2px; background:var(--line); }
    #rp-driver-pro .tl-item{ display:grid; grid-template-columns:28px 1fr; gap:12px; padding:10px 0; }
    #rp-driver-pro .dot{ width:12px; height:12px; border-radius:50%; margin-top:8px; border:2px solid var(--blue); background:#fff; }
    #rp-driver-pro .dot.done{ border-color:var(--success); background:var(--success); }
    #rp-driver-pro .tl-card{
      border:1px solid var(--line); border-radius:12px; background:#fff; padding:10px 12px;
      display:grid; gap:.4rem; box-shadow:0 4px 12px rgba(0,0,0,.02);
    }
    #rp-driver-pro .tl-top{ display:grid; grid-template-columns:1fr auto auto; align-items:center; gap:.5rem; }
    #rp-driver-pro .tl-title{
      font-weight:700; font-size:.95rem; line-height:1.25; color:var(--ink-strong);
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%;
    }
    #rp-driver-pro .tl-badges{ display:flex; gap:.35rem; align-items:center; }
    #rp-driver-pro .tl-btn{ justify-self:end; white-space:nowrap; }
    #rp-driver-pro .tl-meta{ display:grid; grid-template-columns:1fr 1fr; gap:.35rem .75rem; align-items:center; }
    #rp-driver-pro .tl-meta .muted{ color:var(--muted); font-size:.82rem; }
    #rp-driver-pro .tl-meta strong{ font-weight:700; color:var(--ink); }
    @media (max-width:575.98px){
      #rp-driver-pro .tl-top{ grid-template-columns:1fr auto; }
      #rp-driver-pro .tl-badges{ display:none; }
      #rp-driver-pro .tl-meta{ grid-template-columns:1fr; }
    }

    /* Pasos por calles */
    #rp-driver-pro .steps{ list-style:none; margin:.25rem 0 0; padding:0; display:grid; gap:.4rem; }
    #rp-driver-pro .steps li{
      font-size:.86rem; color:var(--ink); line-height:1.35;
      padding:.4rem .6rem; border:1px solid var(--line); border-radius:10px; background:#fff;
    }
    #rp-driver-pro .steps li .muted{ color:var(--muted); }

    /* Consejo IA */
    #rp-driver-pro #advice{ color:var(--ink); line-height:1.5; font-size:.9rem; }

    /* ===== Badges ===== */
    #rp-driver-pro .badge-ok{ background:var(--success-soft); color:var(--success); border-radius:999px; padding:.22rem .6rem; font-weight:700; font-size:.72rem; display:inline-flex; align-items:center; gap:.3rem; }
    #rp-driver-pro .badge-pending{ background:#f3f4f6; color:#555; border-radius:999px; padding:.22rem .6rem; font-weight:700; font-size:.72rem; display:inline-flex; align-items:center; gap:.3rem; }

    /* ===== Botones ===== */
    #rp-driver-pro .btn{
      display:inline-flex; align-items:center; justify-content:center; gap:.4rem;
      font-weight:600; font-size:.9rem; line-height:1;
      padding:.6rem 1rem; border-radius:999px; border:1px solid transparent;
      cursor:pointer; text-decoration:none; white-space:nowrap;
      transition: background .18s ease, color .18s ease, border-color .18s ease, box-shadow .18s ease, transform .12s ease;
    }
    #rp-driver-pro .btn-sm{ padding:.42rem .8rem; font-size:.82rem; }
    #rp-driver-pro .btn:active{ transform:scale(.97); }

    #rp-driver-pro .btn-primary{ background:var(--blue); color:#fff; }
    #rp-driver-pro .btn-primary:hover{ background:#0a6cf0; transform:translateY(-1px); box-shadow:0 6px 16px rgba(0,122,255,.26); }

    #rp-driver-pro .btn-outline-primary{ background:#fff; color:var(--blue); border-color:var(--blue); }
    #rp-driver-pro .btn-outline-primary:hover{ background:var(--blue-soft); transform:translateY(-1px); }

    /* Ghost / neutro (Waze) */
    #rp-driver-pro .btn-outline-dark{ background:#fff; color:#555; border-color:var(--line); }
    #rp-driver-pro .btn-outline-dark:hover{ background:#f9fafb; color:var(--ink-strong); transform:translateY(-1px); }

    #rp-driver-pro .btn-success{ background:var(--success-soft); color:var(--success); border-color:#c9f2cf; }
    #rp-driver-pro .btn-outline-success{ background:#fff; color:var(--success); border-color:#c9f2cf; }
    #rp-driver-pro .btn-outline-success:hover{ background:var(--success-soft); transform:translateY(-1px); }

    #rp-driver-pro .btn:disabled,
    #rp-driver-pro .btn.disabled{ opacity:.5; cursor:not-allowed; box-shadow:none; transform:none; pointer-events:none; }

    #rp-driver-pro .btn-fab{ position:fixed; right:20px; bottom:20px; z-index:10; padding:.7rem 1.15rem; box-shadow:0 10px 26px rgba(0,122,255,.32); }

    /* ===== Botón ocultar/mostrar panel de ruta ===== */
    #rp-driver-pro .panel-actions{
      display:flex; align-items:center; gap:8px; flex-shrink:0;
    }
    #rp-driver-pro .btn-panel-hide{
      width:38px; height:38px; padding:0; border-radius:999px;
      border:1px solid var(--line); background:#fff; color:var(--muted);
      display:inline-flex; align-items:center; justify-content:center;
      box-shadow:0 4px 12px rgba(0,0,0,.02);
    }
    #rp-driver-pro .btn-panel-hide:hover{
      color:var(--ink-strong); background:#f9fafb; transform:translateY(-1px);
    }
    #rp-driver-pro .driver-panel-show-btn{
      position:fixed; left:22px; bottom:22px; z-index:1200;
      min-height:52px; padding:0 20px; border:0; border-radius:999px;
      background:var(--blue); color:#fff; font-weight:700; font-size:.95rem;
      display:inline-flex; align-items:center; justify-content:center; gap:9px;
      box-shadow:0 14px 30px rgba(0,122,255,.28);
      opacity:0; visibility:hidden; pointer-events:none;
      transform:translateY(16px); transition:opacity .22s ease, transform .22s ease, visibility .22s ease;
    }
    #rp-driver-pro .driver-panel-show-btn.is-visible{
      opacity:1; visibility:visible; pointer-events:auto; transform:translateY(0);
    }
    #rp-driver-pro .driver-panel-show-btn:active{ transform:scale(.98); }
    #rp-driver-pro.is-panel-hidden .side{ display:none !important; }
    #rp-driver-pro.is-panel-hidden .col-lg-9{ flex:1 1 100%; max-width:100%; width:100%; }
    #rp-driver-pro.is-panel-hidden .row.g-0{ grid-template-columns:1fr; }

    @media (max-width: 991.98px){
      #rp-driver-pro .driver-panel-show-btn{
        left:50%; bottom:18px; transform:translateX(-50%) translateY(16px);
        min-height:54px; padding:0 22px;
      }
      #rp-driver-pro .driver-panel-show-btn.is-visible{ transform:translateX(-50%) translateY(0); }
      #rp-driver-pro .driver-panel-show-btn:active{ transform:translateX(-50%) scale(.98); }
      #rp-driver-pro.is-panel-hidden .row.g-0{ padding:0; gap:0; }
      #rp-driver-pro.is-panel-hidden .map-card{
        height:calc(100dvh - 56px); min-height:calc(100dvh - 56px);
        border-radius:0; border:0; padding:0;
      }
      #rp-driver-pro.is-panel-hidden .map-card .map{ border-radius:0; }
      #rp-driver-pro.is-panel-hidden .map-legend{ display:none; }
      #rp-driver-pro.is-panel-hidden .routes-panel{ display:none; }
    }

    /* ===== Toast + HUD ===== */
    #rp-driver-pro .toastx{
      position:fixed; left:50%; transform:translateX(-50%); bottom:24px;
      background:#111111; color:#fff; padding:.7rem 1.1rem; border-radius:12px; z-index:2000; display:none;
      box-shadow:0 12px 30px rgba(0,0,0,.2); font-weight:600; font-size:.9rem;
    }
    #rp-driver-pro .toastx.show{ display:block; }
    #rp-driver-pro .map-hud{ position:absolute; left:50%; top:16px; transform:translateX(-50%); z-index:500; display:flex; gap:8px; pointer-events:none; }
    #rp-driver-pro .nav-toast{ background:#111111; color:#fff; border-radius:12px; padding:.55rem .9rem; box-shadow:0 10px 26px rgba(0,0,0,.18); font-weight:600; font-size:.85rem; display:none; }
    #rp-driver-pro .nav-toast.show{ display:block; }

    /* ===== DEBUG chip ===== */
    #rp-driver-pro .dbg{
      position:absolute; right:22px; top:22px; z-index:650;
      background:#111111; color:#fff;
      border-radius:999px; padding:.35rem .7rem; font-weight:600; font-size:.78rem;
      box-shadow:0 8px 22px rgba(0,0,0,.18);
      display:none; max-width:min(92vw, 560px);
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }

    /* ===== Inputs / selects (para uso futuro) ===== */
    #rp-driver-pro input,
    #rp-driver-pro select,
    #rp-driver-pro textarea{
      font-size:.9rem; color:var(--ink);
      background:#fff; border:1px solid var(--line); border-radius:8px; padding:.55rem .7rem;
      transition:border-color .18s ease, box-shadow .18s ease; outline:none;
    }
    #rp-driver-pro input:focus,
    #rp-driver-pro select:focus,
    #rp-driver-pro textarea:focus{
      border-color:var(--blue); box-shadow:0 0 0 3px var(--blue-soft);
    }


    /* ===== Limpieza solicitada: quitar leyenda superior y tarjeta Google/Waze ===== */
    #rp-driver-pro .map-legend,
    #rp-driver-pro .routes-panel,
    #rp-driver-pro .route-legend,
    #rp-driver-pro .routes-legend,
    #rp-driver-pro .map-route-legend,
    #rp-driver-pro .route-status-legend,
    #rp-driver-pro .desktop-route-legend,
    #rp-driver-pro .external-route-card,
    #rp-driver-pro .route-provider-card,
    #rp-driver-pro .navigation-provider-card,
    #rp-driver-pro .google-waze-card,
    #rp-driver-pro .waze-google-card {
      display: none !important;
    }

    /* Botón para salir del modo Street View / vista de calle */
    #rp-driver-pro .exit-street-view-btn {
      position: fixed;
      top: 18px;
      left: 50%;
      z-index: 9999;
      transform: translateX(-50%) translateY(-16px);
      min-height: 46px;
      padding: 0 18px;
      border: 1px solid var(--line);
      border-radius: 999px;
      background: #ffffff;
      color: #111111;
      font-family: 'Quicksand', system-ui, sans-serif;
      font-size: 14px;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      box-shadow: 0 10px 28px rgba(0,0,0,.18);
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
      transition: opacity .2s ease, visibility .2s ease, transform .2s ease, background .18s ease;
    }
    #rp-driver-pro .exit-street-view-btn.is-visible {
      opacity: 1;
      visibility: visible;
      pointer-events: auto;
      transform: translateX(-50%) translateY(0);
    }
    #rp-driver-pro .exit-street-view-btn:hover { background: #f9fafb; }
    #rp-driver-pro .exit-street-view-btn:active { transform: translateX(-50%) scale(.98); }

    @media (max-width: 991.98px) {
      #rp-driver-pro .exit-street-view-btn {
        top: 14px;
        left: 14px;
        right: 14px;
        width: calc(100% - 28px);
        transform: translateY(-16px);
      }
      #rp-driver-pro .exit-street-view-btn.is-visible { transform: translateY(0); }
      #rp-driver-pro .exit-street-view-btn:active { transform: scale(.98); }
    }

  </style>

  <script>
    window.gm_authFailure = function () {
      const el = document.getElementById('dbgChip');
      if (el) {
        el.textContent = 'Error Google Maps API Key';
        el.style.display = 'block';
        el.style.background = '#991b1b';
      }
    };
  </script>

  <div class="row g-0">
    {{-- IZQUIERDA --}}
    <div class="col-lg-3 side">
      <div class="toolbar d-flex align-items-center justify-content-between">
        <div>
          <div class="side-title">{{ $routePlan->name ?? ('Ruta #'.$routePlan->id) }}</div>
          <div class="side-sub">Chofer: {{ $routePlan->driver->name ?? '—' }}</div>

          {{-- ✅ Controles GPS visibles para solicitar ubicación e iniciar cálculo --}}
          <div id="gpsControls" class="gps-actions mt-2">
            <button id="btnLocate" class="btn btn-sm btn-outline-primary" type="button">
              <i class="bi bi-geo-alt-fill"></i> Ver mi ubicación
            </button>

            <button id="btnStart" class="btn btn-sm btn-primary" type="button">
              <i class="bi bi-play-circle"></i> Iniciar y calcular
            </button>

            <button id="btnRecalc" class="btn btn-sm btn-outline-primary" type="button" disabled>
              <i class="bi bi-arrow-repeat"></i> Recalcular
            </button>
          </div>

          <div id="gpsHint" class="side-hint mt-2">
            Toca <strong>Ver mi ubicación</strong> o <strong>Iniciar y calcular</strong> para permitir el GPS.
          </div>

          {{-- ✅ Estado / lock --}}
          <div class="mt-2" id="lockBadgeWrap" style="display:none">
            <span class="badge-ok" id="lockBadge"><i class="bi bi-shield-check"></i> Orden bloqueado</span>
          </div>
        </div>

        <div class="panel-actions">
          <div class="kpi-pill">
            <i class="bi bi-stopwatch"></i> <span id="kpiTotal">—</span>
          </div>

          <button id="btnHideRoutePanel" class="btn-panel-hide" type="button" title="Ocultar panel de ruta" aria-label="Ocultar panel de ruta">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
      </div>

      <div class="grid g3">
        <div class="card next" style="grid-column:1/-1">
          <div class="card-body d-flex justify-content-between align-items-center">
            <div>
              <div class="small text-uppercase muted mb-1">Siguiente punto</div>
              <div class="h5 m-0" id="nextName">—</div>
              <div class="small"><span id="nextEta">—</span> • llegada <strong id="nextAt">—</strong></div>
            </div>
            <span class="badge-ok"><i class="bi bi-lightning-charge"></i> Prioridad</span>
          </div>
        </div>

        <div class="card metric">
          <div class="card-body">
            <div class="label">Fin estimado</div>
            <div class="value" id="etaFinish">—</div>
            <div class="muted small" id="etaFinishHint">Cuando completes todas</div>
          </div>
        </div>

        <div class="card metric">
          <div class="card-body">
            <div class="label">Pendientes</div>
            <div class="value"><span id="pendingCount">—</span>/<span id="totalCount">—</span></div>
            <div class="muted small">Paradas</div>
          </div>
        </div>

        <div class="card metric">
          <div class="card-body">
            <div class="label">Distancia</div>
            <div class="value"><span id="totalKm">—</span> km</div>
            <div class="muted small">Ruta activa</div>
          </div>
        </div>

        <div class="card" style="grid-column:1/-1">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="m-0">Paradas</h6>
              <div class="muted small">Marca “Hecho” al llegar.</div>
            </div>

            <ul id="timeline" class="timeline">
              @for($i=0;$i<3;$i++)
                <li class="tl-item">
                  <div class="dot sk"></div>
                  <div class="tl-card">
                    <div class="sk" style="height:16px;width:60%;border-radius:6px"></div>
                    <div class="sk" style="height:12px;width:40%;border-radius:6px"></div>
                  </div>
                </li>
              @endfor
            </ul>
          </div>
        </div>

        <div class="card" style="grid-column:1/-1">
          <div class="card-body">
            <h6 class="mb-2">Instrucciones por calles</h6>
            <ul id="steps" class="steps"></ul>
            <div class="muted small" id="stepsHint">Si no ves pasos, usa Google/Waze con los botones del mapa.</div>
          </div>
        </div>

        <div class="card" style="grid-column:1/-1">
          <div class="card-body">
            <h6 class="mb-2">Recomendación IA</h6>
            <div id="advice" class="small"></div>
          </div>
        </div>
      </div>
    </div>

    {{-- DERECHA --}}
    <div class="col-lg-9">
      <div class="map-card">
        <div id="map" class="map"></div>

        {{-- DEBUG chip --}}
        <div id="dbgChip" class="dbg">debug</div>

        <button type="button" class="exit-street-view-btn" id="btnExitStreetView">
          <i class="bi bi-x-lg"></i>
          Salir de vista de calle
        </button>

        <div class="map-legend">
          <span class="chip"><i class="bi bi-square-fill" style="color:#2563eb"></i> Principal</span>
          <span class="chip alt"><i class="bi bi-square-fill" style="color:#10b981"></i> Alternativa</span>
          <span class="chip warn"><i class="bi bi-square-fill" style="color:#ef4444"></i> Evitar</span>
          <span class="chip"><i class="bi bi-circle-fill" style="color:#10b981"></i> Fluido</span>
          <span class="chip"><i class="bi bi-circle-fill" style="color:#f59e0b"></i> Lento</span>
          <span class="chip"><i class="bi bi-circle-fill" style="color:#ef4444"></i> Congestión</span>
        </div>

        <div class="routes-panel">
          <div style="font-weight:700; color:var(--ink-strong); margin-bottom:.4rem">Rutas</div>
          <div id="routesCards" class="routes-list">
            <div class="sk" style="height:46px;border-radius:12px"></div>
          </div>
          <div id="routesEmpty" class="small-muted" style="display:none;margin-top:.45rem">
            Solo llegó una ruta. Recalcula o abre en Google/Waze.
          </div>

          <div class="d-flex gap-2 mt-2">
            <a id="linkGmaps" href="#" target="_blank" class="btn btn-sm btn-outline-primary disabled"><i class="bi bi-map"></i> Google Maps</a>
            <a id="linkWaze" href="#" target="_blank" class="btn btn-sm btn-outline-dark disabled"><i class="bi bi-sign-turn-right"></i> Waze</a>
          </div>

          {{-- ✅ info de roundtrip (siempre) --}}
          <div class="small-muted mt-2">
            <i class="bi bi-arrow-90deg-left"></i> Cierre: regresa al inicio (roundtrip)
          </div>
        </div>



        <div class="desktop-traffic-widget" id="desktopTrafficWidget">
          <button type="button" class="traffic-selector" id="btnDesktopTraffic" title="Activar o desactivar tráfico">
            <span>Tráfico en tiempo real</span>
            <i class="bi bi-caret-down-fill"></i>
          </button>

          <div class="traffic-scale" aria-hidden="true">
            <span>Rápido</span>
            <div class="traffic-bars">
              <i class="bar green"></i>
              <i class="bar yellow"></i>
              <i class="bar orange"></i>
              <i class="bar red"></i>
              <i class="bar darkred"></i>
            </div>
            <span>Lento</span>
          </div>

          <label class="traffic-switch" title="Mostrar tráfico en tiempo real">
            <input type="checkbox" id="trafficToggle" checked>
            <span></span>
          </label>
        </div>

        <div class="map-hud"><div id="navToast" class="nav-toast">Listo</div></div>
      </div>
    </div>
  </div>

  <button class="driver-panel-show-btn" id="btnShowRoutePanel" type="button" aria-label="Mostrar panel de ruta">
    <i class="bi bi-list-check"></i> Ver ruta
  </button>

  <button class="btn btn-primary btn-fab" id="fabDone" style="display:none" type="button">
    <i class="bi bi-check2-circle"></i> Marcar punto actual como hecho
  </button>
  <div id="toast" class="toastx">Listo</div>
</div>

<script>
  /* =========================
   * CONFIG
   * ========================= */
  const DEBUG_ROUTE = true;

  function dlog(label, payload){
    if (!DEBUG_ROUTE) return;
    try { console.log('%c[ROUTE_DEBUG] ' + label, 'font-weight:800', payload ?? ''); } catch(e){}
  }
  function dwarn(label, payload){
    if (!DEBUG_ROUTE) return;
    try { console.warn('[ROUTE_DEBUG] ' + label, payload ?? ''); } catch(e){}
  }

  const DBG_URL = @json(url('/api/client-log'));

  async function sendClientLog(level, message, meta){
    if (!DEBUG_ROUTE) return;
    if (typeof window.csrfFetch !== 'function') return;
    try{
      await window.csrfFetch(DBG_URL, {
        method:'POST',
        headers:{ 'Content-Type':'application/json', 'Accept':'application/json' },
        body: JSON.stringify({
          scope: 'route_driver',
          level: level || 'info',
          message: String(message || ''),
          meta: meta || {}
        })
      });
    }catch(e){}
  }

  function dbgChip(text, isError=false){
    const el = document.getElementById('dbgChip');
    if (!el) return;
    el.style.display = 'block';
    el.textContent = text || 'debug';
    el.style.background = isError ? 'rgba(153,27,27,.92)' : 'rgba(17,24,39,.92)';
    clearTimeout(el._t);
    el._t = setTimeout(()=>{ el.style.display='none'; }, 5000);
  }

  const REQUEST_ALTS = { include_alternatives: false, max_alternatives: 0, steps: true };
  const USE_CREDENTIALS = true;

  /* ===== Datos servidor ===== */
  const planId        = {{ $routePlan->id }};
  const initialStops  = @json($stops);
  const csrf          = @json(csrf_token());

  // ✅ Nuevas rutas: start bloquea el orden 1 vez
  const URL_START     = @json(route('api.routes.start', $routePlan));
  const URL_COMPUTE   = @json(route('api.routes.compute', $routePlan));
  const URL_RECOMPUTE = @json(route('api.routes.recompute', $routePlan));
  const URL_DONE_BASE = @json(url('/api/routes/'.$routePlan->id.'/stops'));
  const URL_SAVE_LOC  = @json(route('api.driver.location.save'));
  const URL_LAST_LOC  = @json(route('api.driver.location.last'));
  const URL_LIVE      = @json(route('api.routes.live', $routePlan));

  dlog('boot', { planId, initialStopsCount: (initialStops||[]).length, URL_START, URL_COMPUTE, URL_RECOMPUTE });

  /* ===== Estado ===== */
  let map, meMarker, mainLine, alt1Line, alt2Line, segLines = [];
  let trafficLayer = null;
  let trafficEnabled = true;
  let stopMarkers = [];
  let currentPos = null, lastPayload = null, watcherId = null;
  let routeSteps = [], stepIdx = 0, didAutoZoom = false, followMode = true;

  // ✅ Estado de lock (server)
  let serverLocked = false;

  /* ===== Panel de ruta: ocultar / mostrar ===== */
  const rootEl = document.getElementById('rp-driver-pro');
  document.body.classList.add('driver-panel-open');

  function refreshGoogleMapLayout(){
    setTimeout(() => {
      try {
        if (window.google && map) {
          google.maps.event.trigger(map, 'resize');
          if (currentPos && isValidLatLng(currentPos.lat, currentPos.lng)) {
            map.panTo({ lat: currentPos.lat, lng: currentPos.lng });
          } else if (mainLine) {
            fitAll();
          }
        }
      } catch (e) {}
    }, 320);
  }

  function hideRoutePanel(){
    rootEl?.classList.add('is-panel-hidden');
    document.body.classList.remove('driver-panel-open');
    document.getElementById('btnShowRoutePanel')?.classList.add('is-visible');
    refreshGoogleMapLayout();
  }

  function showRoutePanel(){
    rootEl?.classList.remove('is-panel-hidden');
    document.body.classList.add('driver-panel-open');
    document.getElementById('btnShowRoutePanel')?.classList.remove('is-visible');
    refreshGoogleMapLayout();
  }

  /* ===== Utils ===== */
  const mm = (s)=> Math.round((s||0)/60);
  const km = (m)=> (m||0)/1000;
  const fmtClock = (d)=> `${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`;
  const mdToHtml = (md)=> (md? String(md)
    .replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
    .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>').replace(/\n/g,'<br>')
    : ''
  );

  const toNum = (v)=> {
    const n = Number(v);
    return Number.isFinite(n) ? n : null;
  };
  const isValidLatLng = (lat,lng)=> {
    if (lat === null || lng === null) return false;
    if (Math.abs(lat) < 0.000001 && Math.abs(lng) < 0.000001) return false;
    return Math.abs(lat) <= 90 && Math.abs(lng) <= 180;
  };

  function showToast(text, ok=true){
    const t=document.getElementById('toast');
    t.textContent=text|| (ok?'Listo':'Error');
    t.style.background= ok?'#111827':'#991b1b';
    t.classList.add('show');
    setTimeout(()=>t.classList.remove('show'),2400);
  }
  function mapToast(html){
    const el=document.getElementById('navToast');
    el.innerHTML=html||'Listo';
    el.classList.add('show');
    clearTimeout(el._t);
    el._t=setTimeout(()=>el.classList.remove('show'),4000);
  }

  function fopts(extra={}){
    const base = USE_CREDENTIALS ? { credentials:'include' } : {};
    return Object.assign(base, extra);
  }

  async function safeJsonFetch(url, options){
    let res;
    try{
      res = await fetch(url, options);
    }catch(e){
      dwarn('fetch network error', { url, err: String(e) });
      dbgChip('Network error en fetch()', true);
      await sendClientLog('error', 'fetch network error', { url, err: String(e) });
      return { ok:false, status:0, data:null };
    }

    const ct = (res.headers.get('content-type')||'').toLowerCase();

    if (!ct.includes('application/json')){
      const text = await res.text().catch(()=> '');
      const isLogin = text.includes('<html') || text.includes('<!doctype') || text.includes('login');
      const code = res.status;

      const hint =
        code === 401 ? '401 (sesión)' :
        code === 403 ? '403 (permiso)' :
        code === 419 ? '419 (CSRF)' :
        isLogin ? 'HTML (login?)' :
        'non-json';

      dwarn('non-json response', { url, status: code, ct, hint, sample: text.slice(0,220) });
      await sendClientLog('error', 'non-json response', { url, status: code, ct, hint, sample: text.slice(0,220) });

      if (code === 401) showToast('Sesión no válida (401).', false);
      else if (code === 419) showToast('CSRF expirado (419). Recarga.', false);
      else if (code === 403) showToast('No tienes permiso (403).', false);
      else showToast('Respuesta no-JSON del servidor ('+code+').', false);

      dbgChip('API non-json: ' + hint, true);
      return { ok:false, status:code, data:null };
    }

    const data = await res.json().catch(()=>null);

    if (!res.ok){
      const msg = data?.message || ('Error HTTP '+res.status);
      dwarn('api error', { url, status: res.status, data });
      await sendClientLog('error', 'api error', { url, status: res.status, data });
      dbgChip('API error ' + res.status + ': ' + (data?.message || 'sin mensaje'), true);
      showToast(msg, false);
      return { ok:false, status:res.status, data };
    }

    return { ok:true, status:res.status, data };
  }

  /* ===== Mostrar/ocultar controles GPS ===== */
  function setLockUI(locked){
    serverLocked = !!locked;

    const wrap = document.getElementById('lockBadgeWrap');
    if (wrap) wrap.style.display = locked ? 'block' : 'none';

    const controls = document.getElementById('gpsControls');
    if (controls) controls.style.display = 'flex';

    const btnStart = document.getElementById('btnStart');
    if (btnStart) btnStart.style.display = locked ? 'none' : 'inline-flex';

    const btnLocate = document.getElementById('btnLocate');
    if (btnLocate) btnLocate.style.display = 'inline-flex';

    const hint = document.getElementById('gpsHint');
    if (hint) {
      hint.innerHTML = locked
        ? 'GPS activo. Usa <strong>Recalcular</strong> para actualizar la ruta con tu ubicación actual.'
        : 'Toca <strong>Ver mi ubicación</strong> o <strong>Iniciar y calcular</strong> para permitir el GPS.';
    }
  }

  function showGpsControls(show){
    const el = document.getElementById('gpsControls');
    if (!el) return;

    // En móvil/navegador el permiso de GPS solo aparece por acción del usuario.
    // Por eso los controles deben mantenerse visibles.
    el.style.display = show ? 'flex' : 'flex';
  }

  /* ===== Mapa Google ===== */
  function makeStopIcon(number, done = false) {
    const color = done ? '#16a34a' : '#2563eb';

    return {
      url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
        <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 42 42">
          <filter id="s" x="-30%" y="-30%" width="160%" height="160%">
            <feDropShadow dx="0" dy="6" stdDeviation="4" flood-color="#000000" flood-opacity=".18"/>
          </filter>
          <path filter="url(#s)" d="M21 4c8.284 0 15 6.716 15 15 0 10.5-15 19-15 19S6 29.5 6 19C6 10.716 12.716 4 21 4Z" fill="${color}"/>
          <circle cx="21" cy="19" r="11" fill="#ffffff"/>
          <text x="21" y="23" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" font-weight="700" fill="${color}">${number}</text>
        </svg>
      `),
      scaledSize: new google.maps.Size(42, 42),
      anchor: new google.maps.Point(21, 38),
    };
  }

  function makeDriverIcon() {
    return {
      path: google.maps.SymbolPath.CIRCLE,
      scale: 9,
      fillColor: '#60a5fa',
      fillOpacity: .95,
      strokeColor: '#1d4ed8',
      strokeWeight: 3,
    };
  }

  function addStopFlags(stops){
    stopMarkers.forEach(m=>{ try{ m.setMap(null); }catch(e){} });
    stopMarkers = [];

    const ordered = (stops||[]).slice().sort((a,b)=>
      (a.sequence_index??999999)-(b.sequence_index??999999) || (a.id-b.id)
    );

    ordered.forEach((s, idx) => {
      const lat = toNum(s.lat), lng = toNum(s.lng);
      if (!isValidLatLng(lat,lng)) return;

      const n = (s.sequence_index != null && Number.isFinite(Number(s.sequence_index)))
        ? Number(s.sequence_index)
        : (idx + 1);

      const marker = new google.maps.Marker({
        map,
        position: { lat, lng },
        icon: makeStopIcon(n, s.status === 'done'),
        title: (s.name || 'Punto') + (s.status === 'done' ? ' • hecho' : ''),
      });

      const info = new google.maps.InfoWindow({
        content: `
          <div style="font-family:system-ui,-apple-system,Segoe UI,sans-serif;min-width:180px">
            <strong>${(s.name || 'Punto')}</strong>
            <div style="font-size:12px;color:#6b7280;margin-top:4px">${lat.toFixed(5)}, ${lng.toFixed(5)}</div>
            <div style="font-size:12px;margin-top:6px;color:${s.status === 'done' ? '#15803d' : '#2563eb'};font-weight:800">
              ${s.status === 'done' ? 'Hecho' : 'Pendiente'}
            </div>
          </div>
        `,
      });

      marker.addListener('click', () => info.open({ map, anchor: marker }));
      stopMarkers.push(marker);
    });
  }

  function fitPositions(positions, padding = 72) {
    if (!positions.length) return;

    const bounds = new google.maps.LatLngBounds();

    positions.forEach(p => {
      const lat = toNum(p.lat), lng = toNum(p.lng);
      if (isValidLatLng(lat, lng)) bounds.extend({ lat, lng });
    });

    if (!bounds.isEmpty()) map.fitBounds(bounds, padding);
  }



  function setupStreetViewExitButton(){
    const btnExitStreetView = document.getElementById('btnExitStreetView');
    if (!map || !btnExitStreetView || !window.google) return;

    const panorama = map.getStreetView();

    panorama.addListener('visible_changed', () => {
      const isVisible = panorama.getVisible();
      btnExitStreetView.classList.toggle('is-visible', isVisible);
    });

    btnExitStreetView.addEventListener('click', () => {
      panorama.setVisible(false);
      btnExitStreetView.classList.remove('is-visible');
      setTimeout(() => {
        try { google.maps.event.trigger(map, 'resize'); } catch(e) {}
      }, 120);
    });
  }

  function setupTrafficControls(){
    trafficLayer = new google.maps.TrafficLayer();
    trafficLayer.setMap(map);
    trafficEnabled = true;

    const trafficToggle = document.getElementById('trafficToggle');
    const btnDesktopTraffic = document.getElementById('btnDesktopTraffic');

    if (trafficToggle) {
      trafficToggle.checked = true;
      trafficToggle.addEventListener('change', () => {
        trafficEnabled = trafficToggle.checked;
        trafficLayer.setMap(trafficEnabled ? map : null);
        showNavToast(trafficEnabled ? 'Tráfico activado' : 'Tráfico oculto');
      });
    }

    if (btnDesktopTraffic) {
      btnDesktopTraffic.addEventListener('click', () => {
        trafficEnabled = !trafficEnabled;
        if (trafficToggle) trafficToggle.checked = trafficEnabled;
        trafficLayer.setMap(trafficEnabled ? map : null);
        showNavToast(trafficEnabled ? 'Tráfico activado' : 'Tráfico oculto');
      });
    }
  }

  function initMap(){
    map = new google.maps.Map(document.getElementById('map'), {
      center: { lat: 20.6736, lng: -103.344 },
      zoom: 12,
      mapTypeId: 'roadmap',
      mapTypeControl: true,
      mapTypeControlOptions: {
        style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
        position: google.maps.ControlPosition.TOP_LEFT,
        mapTypeIds: ['roadmap', 'satellite', 'terrain', 'hybrid'],
      },
      streetViewControl: true,
      fullscreenControl: true,
      zoomControl: true,
      clickableIcons: true,
      gestureHandling: 'greedy',
    });

    window.map = map;

    setupTrafficControls();
    setupStreetViewExitButton();

    const validStops = (initialStops||[])
      .map(s=>({ ...s, lat: toNum(s.lat), lng: toNum(s.lng) }))
      .filter(s=> isValidLatLng(s.lat, s.lng));

    if (validStops.length){
      addStopFlags(validStops);
      fitPositions(validStops, 72);
    }

    map.addListener('dragstart', ()=> followMode=false);
  }

  function clearRouteLayers(){
    if (mainLine) { mainLine.setMap(null); mainLine=null; }
    if (alt1Line){ alt1Line.setMap(null); alt1Line=null; }
    if (alt2Line){ alt2Line.setMap(null); alt2Line=null; }
    segLines.forEach(l=>{ try{ l.setMap(null); }catch(e){} });
    segLines=[];
  }

  function routeToPath(route){
    if (!route) return [];

    if (Array.isArray(route.coordinates) && route.coordinates.length) {
      return route.coordinates
        .map(p => Array.isArray(p)
          ? { lat: Number(p[1]), lng: Number(p[0]) }
          : { lat: Number(p.lat), lng: Number(p.lng) }
        )
        .filter(p => isValidLatLng(p.lat, p.lng));
    }

    if (route.polyline && window.google?.maps?.geometry?.encoding) {
      return google.maps.geometry.encoding.decodePath(route.polyline)
        .map(p => ({ lat: p.lat(), lng: p.lng() }));
    }

    const geo = route.geometry;
    if (!geo) return [];

    if (geo.type === 'LineString' && Array.isArray(geo.coordinates)) {
      return geo.coordinates
        .map(c => ({ lat: Number(c[1]), lng: Number(c[0]) }))
        .filter(p => isValidLatLng(p.lat, p.lng));
    }

    if (geo.type === 'MultiLineString' && Array.isArray(geo.coordinates)) {
      return geo.coordinates
        .flat()
        .map(c => ({ lat: Number(c[1]), lng: Number(c[0]) }))
        .filter(p => isValidLatLng(p.lat, p.lng));
    }

    return [];
  }

  function drawGeo(route, color, weight=6, dashed=false){
    const path = routeToPath(route);
    if (!path.length) return null;

    try{
      return new google.maps.Polyline({
        map,
        path,
        geodesic: true,
        strokeColor: color,
        strokeOpacity: .92,
        strokeWeight: weight,
        icons: dashed ? [{
          icon: { path: 'M 0,-1 0,1', strokeOpacity: 1, scale: 3 },
          offset: '0',
          repeat: '14px',
        }] : undefined,
      });
    }catch(e){
      dwarn('drawGeo failed', { err: String(e) });
      sendClientLog('error', 'drawGeo failed', { err: String(e), route });
      return null;
    }
  }

  function fitAll(){
    const positions=[];

    if (mainLine) {
      const path = mainLine.getPath();
      for (let i=0; i<path.getLength(); i++){
        const p = path.getAt(i);
        positions.push({ lat:p.lat(), lng:p.lng() });
      }
    }

    if (currentPos && isValidLatLng(currentPos.lat, currentPos.lng)) {
      positions.push(currentPos);
    }

    fitPositions(positions, 72);
  }

  /* ===== Render UI ===== */
  function renderRoutesCards(payload){
    const wrap=document.getElementById('routesCards');
    const empty=document.getElementById('routesEmpty');

    wrap.innerHTML=''; empty.style.display='none';
    const routes = payload.routes||[];
    if (!routes.length){ empty.style.display='block'; return; }

    routes.forEach((r,i)=>{
      const cls = 'route-card active';
      const badge = 'rb-blue';
      const mins = Math.round((r.total_sec||0)/60), h=Math.floor(mins/60), m=mins%60;
      const time = h? `${h} h ${m} min`:`${m} min`;
      const dist = `${km(r.total_m||0).toFixed(1)} km`;

      wrap.insertAdjacentHTML('beforeend', `
        <div class="${cls}">
          <div class="route-head">
            <span class="route-badge ${badge}"><i class="bi bi-route"></i> Principal</span>
            <span class="small-muted"><i class="bi bi-signpost-2"></i> ${dist}</span>
          </div>
          <div class="small-muted" style="margin-top:.25rem">
            <i class="bi bi-stopwatch"></i> <strong>${time}</strong> · Roundtrip
          </div>
        </div>
      `);
    });

    if (routes.length === 1) empty.style.display='block';
  }

  function renderStepsFromPayload(payload){
    const list=document.getElementById('steps'); list.innerHTML='';
    const stepsHint=document.getElementById('stepsHint');
    routeSteps = Array.isArray(payload?.routes?.[0]?.steps) ? payload.routes[0].steps : [];
    stepIdx=0;

    if (!routeSteps.length){ stepsHint.style.display='block'; return; }
    stepsHint.style.display='none';

    routeSteps.forEach((st, idx)=>{
      const name = st.name || '';
      const instr = st.instruction || '';
      const dist = st.distance ? (st.distance/1000).toFixed(1)+' km' : '';
      list.insertAdjacentHTML('beforeend', `<li>${idx+1}. ${instr} <span class="muted">${name ? ' • '+name : ''} ${dist ? ' • '+dist : ''}</span></li>`);
    });

    mapToast('Empezamos • ' + (routeSteps[0]?.instruction || 'Sigue la ruta'));
  }

  function renderTimeline(payload){
    lastPayload = payload;

    const ordered=(payload.ordered_stops||[]).slice()
      .sort((a,b)=> (a.sequence_index??999999)-(b.sequence_index??999999) || (a.id-b.id));

    const tl=document.getElementById('timeline'); tl.innerHTML='';

    // ✅ usar eta_seconds ya calculado por backend (parejo y consistente)
    const now=new Date();

    ordered.forEach((s)=>{
      const dotCls=s.status==='done'?'dot done':'dot';

      let etaMinTxt='—', arriveTxt='—';
      if (s.status!=='done'){
        const sec=Number(s.eta_seconds||0)||0;
        const at=new Date(now.getTime()+sec*1000);
        etaMinTxt=`${mm(sec)} min`;
        arriveTxt=fmtClock(at);
      }

      const seq = (s.sequence_index != null ? Number(s.sequence_index) : null);

      const statusChip = s.status==='done'
        ? '<span class="badge-ok">hecho</span>'
        : '<span class="badge-pending">pendiente</span>';

      const button = s.status==='done'
        ? '<button class="btn btn-sm btn-success" type="button" disabled><i class="bi bi-check2-circle"></i> Hecho</button>'
        : `<button class="btn btn-sm btn-outline-success tl-btn" type="button" data-done="${s.id}"><i class="bi bi-check2"></i> Hecho</button>`;

      const lat = toNum(s.lat), lng = toNum(s.lng);
      const coord = (isValidLatLng(lat,lng)) ? `(${lat.toFixed(5)}, ${lng.toFixed(5)})` : '(—)';

      tl.insertAdjacentHTML('beforeend', `
        <li class="tl-item">
          <div class="${dotCls}"></div>
          <div class="tl-card">
            <div class="tl-top">
              <div class="tl-title">${seq ? '#'+seq+'. ' : ''}${ (s.name||'Punto') }</div>
              <div class="tl-badges">${statusChip}</div>
              ${button}
            </div>
            <div class="tl-meta">
              <div class="muted">${coord}</div>
              <div class="muted"><strong>ETA</strong>: ${etaMinTxt} • <strong>llegada</strong>: ${arriveTxt}</div>
            </div>
          </div>
        </li>
      `);
    });

    const pending=ordered.filter(s=>s.status!=='done');
    document.getElementById('totalCount').textContent=ordered.length;
    document.getElementById('pendingCount').textContent=pending.length;

    if (pending.length){
      const first=pending[0];
      const sec=Number(first.eta_seconds||0)||0;
      const at=new Date(now.getTime()+sec*1000);

      document.getElementById('nextName').textContent=first.name||'Punto';
      document.getElementById('nextEta').textContent=`${mm(sec)} min`;
      document.getElementById('nextAt').textContent=fmtClock(at);

      const fab=document.getElementById('fabDone');
      fab.style.display='inline-block';
      fab.setAttribute('data-done', first.id);
    } else {
      document.getElementById('nextName').textContent='—';
      document.getElementById('nextEta').textContent='—';
      document.getElementById('nextAt').textContent='—';
      document.getElementById('fabDone').style.display='none';
    }

    renderStepsFromPayload(payload);
    updateNavLinks();
  }

  function renderAdvice(md){ document.getElementById('advice').innerHTML = mdToHtml(md||'Sin observaciones.'); }
  function renderKPIsDistance(payload){
    const m=Number(payload?.routes?.[0]?.total_m||0);
    document.getElementById('totalKm').textContent=m?(m/1000).toFixed(1):'—';
    const mins = Math.max(1, Math.round(Number(payload?.routes?.[0]?.total_sec||0)/60));
    document.getElementById('kpiTotal').textContent = `${mins} min`;
  }

  function drawAll(payload){
    clearRouteLayers();

    const R = payload.routes||[];

    if (R[0]) mainLine = drawGeo(R[0], '#2563eb', 6, false);

    if (currentPos && isValidLatLng(currentPos.lat, currentPos.lng)){
      if (meMarker) meMarker.setMap(null);

      meMarker = new google.maps.Marker({
        map,
        position: { lat: currentPos.lat, lng: currentPos.lng },
        icon: makeDriverIcon(),
        title: 'Mi ubicación',
        zIndex: 999,
      });
    }

    if (Array.isArray(payload.ordered_stops) && payload.ordered_stops.length){
      addStopFlags(payload.ordered_stops);
    }

    if (mainLine) fitAll();

    // ✅ lock UI
    setLockUI(!!payload.sequence_locked);

    renderRoutesCards(payload);
    renderTimeline(payload);
    renderAdvice(payload.advice_md);
    renderKPIsDistance(payload);

    setTimeout(()=>{ try{ google.maps.event.trigger(map, 'resize'); }catch(e){} }, 60);
  }

  /* ===== nav links ===== */
  function nextPendingStop(payload){
    const ordered=(payload?.ordered_stops||[]).slice()
      .sort((a,b)=> (a.sequence_index??999999)-(b.sequence_index??999999) || (a.id-b.id));
    return ordered.find(s=>s.status!=='done')||null;
  }

  function updateNavLinks(){
    const g=document.getElementById('linkGmaps');
    const w=document.getElementById('linkWaze');

    let dest=null;
    const stop = nextPendingStop(lastPayload);
    if (stop && stop.lat != null && stop.lng != null){
      dest=`${stop.lat},${stop.lng}`;
    }
    const origin = currentPos ? `${currentPos.lat},${currentPos.lng}` : null;

    if (!dest || !origin){
      g.classList.add('disabled'); w.classList.add('disabled');
      return;
    }

    g.href=`https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(dest)}&travelmode=driving&dir_action=navigate`;
    w.href=`https://waze.com/ul?ll=${encodeURIComponent(dest)}&from=${encodeURIComponent(origin)}&navigate=yes&zoom=17`;

    g.classList.remove('disabled');
    w.classList.remove('disabled');
  }

  /* ===== Persistencia ===== */
  async function saveDriverLocation(pos){
    const payload = { lat: pos.lat, lng: pos.lng, captured_at: new Date().toISOString() };

    const r = await safeJsonFetch(URL_SAVE_LOC, fopts({
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN': csrf
      },
      body: JSON.stringify(payload)
    }));

    if (!r.ok){
      await sendClientLog('error', 'saveDriverLocation failed', { r });
    }
  }

  function startWatching(){
    if (!navigator.geolocation){
      showToast('Tu dispositivo no soporta GPS', false);
      return;
    }
    if (!window.isSecureContext){
      showToast('GPS bloqueado: el sitio debe estar en HTTPS', false);
      return;
    }
    if (watcherId !== null) return;

    let lastSent=0, lastPos=currentPos;

    watcherId = navigator.geolocation.watchPosition(
      async (p)=>{
        currentPos={ lat:p.coords.latitude, lng:p.coords.longitude };

        if (!didAutoZoom && lastPos){
          const toRad=d=>d*Math.PI/180, R=6371000;
          const dLat=toRad(currentPos.lat-lastPos.lat), dLon=toRad(currentPos.lng-lastPos.lng);
          const lat1=toRad(lastPos.lat), lat2=toRad(currentPos.lat);
          const x=Math.sin(dLat/2)**2 + Math.cos(lat1)*Math.cos(lat2)*Math.sin(dLon/2)**2;
          const d=2*R*Math.asin(Math.sqrt(x));
          if (d >= 30){
            didAutoZoom=true;
            try{
              map.panTo({ lat: currentPos.lat, lng: currentPos.lng });
              map.setZoom(15);
            }catch{}
          }
        }
        lastPos=currentPos;

        if (followMode && didAutoZoom){
          map.panTo({ lat: currentPos.lat, lng: currentPos.lng });
        }

        if (meMarker) meMarker.setMap(null);

        meMarker = new google.maps.Marker({
          map,
          position: { lat: currentPos.lat, lng: currentPos.lng },
          icon: makeDriverIcon(),
          title: 'Mi ubicación',
          zIndex: 999,
        });

        const now=Date.now();
        if (now-lastSent>15000){
          lastSent=now;
          await saveDriverLocation(currentPos);
          try{ await recompute(); }catch(e){
            await sendClientLog('error', 'recompute failed in watcher', { err: String(e) });
          }
        }

        updateNavLinks();
      },
      async (err)=>{
        const msg =
          err.code===1 ? 'Permiso de ubicación denegado. Actívalo en tu navegador.' :
          err.code===2 ? 'No se pudo obtener señal GPS.' :
          err.code===3 ? 'El GPS tardó demasiado (timeout).' :
          (err.message || 'Error de GPS');

        showToast(msg, false);
        dbgChip('GPS: ' + msg, true);
        await sendClientLog('error', 'gps error', { code: err.code, message: err.message });
      },
      { enableHighAccuracy:true, maximumAge:5000, timeout:20000 }
    );
  }

  function stopWatching(){
    if (watcherId !== null){
      navigator.geolocation.clearWatch(watcherId);
      watcherId=null;
    }
  }

  async function requestGpsOnce(){
    if (!navigator.geolocation){
      showToast('Tu dispositivo no soporta GPS', false);
      return null;
    }
    if (!window.isSecureContext){
      showToast('El GPS requiere HTTPS', false);
      return null;
    }

    try{
      const pos = await new Promise((resolve, reject)=>{
        navigator.geolocation.getCurrentPosition(
          p=>resolve({lat:p.coords.latitude,lng:p.coords.longitude}),
          err=>reject(err),
          { enableHighAccuracy:true, timeout:12000, maximumAge:5000 }
        );
      });

      currentPos = pos;
      dbgChip('GPS listo: ' + pos.lat.toFixed(5) + ', ' + pos.lng.toFixed(5));
      await saveDriverLocation(pos);
      return pos;
    }catch(err){
      const msg =
        err?.code===1 ? 'Permiso denegado. Actívalo desde el candado.' :
        err?.code===2 ? 'No se pudo obtener señal GPS.' :
        err?.code===3 ? 'El GPS tardó demasiado.' :
        (err?.message || 'No se pudo obtener ubicación');

      showToast(msg, false);
      dbgChip('GPS error: ' + msg, true);
      await sendClientLog('error', 'requestGpsOnce failed', { err: String(err), msg });
      return null;
    }
  }

  function paintCurrentPosition(pos, zoom = 16){
    if (!pos || !map || !window.google) return;

    currentPos = {
      lat: Number(pos.lat),
      lng: Number(pos.lng)
    };

    if (!isValidLatLng(currentPos.lat, currentPos.lng)) return;

    if (meMarker) {
      meMarker.setMap(null);
    }

    meMarker = new google.maps.Marker({
      map,
      position: { lat: currentPos.lat, lng: currentPos.lng },
      icon: makeDriverIcon(),
      title: 'Mi ubicación',
      zIndex: 999,
    });

    map.panTo({ lat: currentPos.lat, lng: currentPos.lng });
    map.setZoom(zoom);
    didAutoZoom = true;
    updateNavLinks();
  }

  async function locateAndCalculate(){
    const pos = await requestGpsOnce();
    if (!pos) return null;

    paintCurrentPosition(pos, 16);
    await compute(pos);
    startWatching();

    document.getElementById('btnRecalc')?.removeAttribute('disabled');

    return pos;
  }

  /* ===== API ===== */
  async function startRoute(start){
    if (!start || !isValidLatLng(toNum(start.lat), toNum(start.lng))){
      showToast('Ubicación inválida para iniciar.', false);
      return false;
    }

    const payloadOut = { start_lat:start.lat, start_lng:start.lng };
    dlog('start request', payloadOut);

    const r = await safeJsonFetch(URL_START, fopts({
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN': csrf
      },
      body: JSON.stringify(payloadOut)
    }));

    if (!r.ok) return false;

    showToast('Ruta iniciada. Orden bloqueado.');
    setLockUI(true);
    return true;
  }

  async function compute(start){
    if (!start || !isValidLatLng(toNum(start.lat), toNum(start.lng))){
      showToast('Ubicación inválida para calcular.', false);
      return;
    }

    const payloadOut = { start_lat:start.lat, start_lng:start.lng, ...REQUEST_ALTS };

    const r = await safeJsonFetch(URL_COMPUTE, fopts({
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN': csrf
      },
      body: JSON.stringify(payloadOut)
    }));

    if (!r.ok || !r.data) return;

    drawAll(r.data);
    lastPayload=r.data;
    document.getElementById('btnRecalc')?.removeAttribute('disabled');
  }

  async function recompute(){
    if(!currentPos) return;

    const payloadOut = { start_lat: currentPos.lat, start_lng: currentPos.lng, ...REQUEST_ALTS };

    const r = await safeJsonFetch(URL_RECOMPUTE, fopts({
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN': csrf
      },
      body: JSON.stringify(payloadOut)
    }));

    if (!r.ok || !r.data) return;

    drawAll(r.data);
    lastPayload=r.data;
  }

  async function fetchLive(){
    const r = await safeJsonFetch(URL_LIVE, fopts({ headers:{ 'Accept':'application/json' } }));
    if (!r.ok || !r.data) return null;
    return r.data;
  }

  /* ===== Auto-boot ===== */
  async function autoBoot(){
    // 1) preguntar al server si ya está bloqueado y si hay start guardado
    try{
      const live = await fetchLive();
      if (live?.sequence_locked != null) setLockUI(!!live.sequence_locked);
    }catch(e){}

    // 2) última ubicación guardada
    try{
      const r = await safeJsonFetch(URL_LAST_LOC, fopts({ headers:{'Accept':'application/json'} }));
      if (r.ok && r.data?.lat && r.data?.lng){
        currentPos={ lat:Number(r.data.lat), lng:Number(r.data.lng) };
        await compute(currentPos);
      }
    }catch(e){}

    // 3) permisos
    try{
      if (navigator.permissions?.query){
        const p = await navigator.permissions.query({ name:'geolocation' });

        const applyState = async ()=>{
          if (p.state === 'prompt'){
            // si NO está bloqueado, mostramos iniciar
            showGpsControls(true);
          } else if (p.state === 'granted'){
            showGpsControls(true); // deja visible recalc; iniciar se oculta si locked
            startWatching();
            if (!currentPos){
              const pos = await requestGpsOnce();
              if (pos) await compute(pos);
            }
          } else {
            showGpsControls(true);
            showToast('Permiso de ubicación denegado. Puedes activarlo desde el candado del navegador.', false);
            dbgChip('GPS denied', true);
          }
        };

        await applyState();
        p.onchange = applyState;
        return;
      }
    }catch(e){}

    showGpsControls(true);
  }

  /* ===== Eventos ===== */
  document.addEventListener('click', async (e)=>{
    const btn=e.target.closest('[data-done]');
    if(!btn) return;

    const doneId=btn.getAttribute('data-done');
    const url=`${URL_DONE_BASE}/${doneId}/done`;

    const r = await safeJsonFetch(url, fopts({
      method:'POST',
      headers:{
        'Accept':'application/json',
        'X-CSRF-TOKEN': csrf
      }
    }));

    if (r.ok && r.data?.ok){
      await recompute();
      showToast('Punto marcado como hecho');
      dbgChip('Punto marcado ✓');
    }else{
      showToast(r.data?.message||'No se pudo marcar', false);
      dbgChip('No se pudo marcar', true);
    }
  });

  document.getElementById('btnLocate')?.addEventListener('click', async ()=>{
    const btn = document.getElementById('btnLocate');
    const old = btn?.innerHTML;

    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-crosshair"></i> Buscando...';
    }

    try {
      const pos = await locateAndCalculate();
      if (pos) {
        showToast('Ubicación detectada. Ruta calculada.');
        mapToast('Ubicación detectada');
      }
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = old;
      }
    }
  });

  // ✅ Iniciar ruta: pide GPS, bloquea orden 1 vez en backend y empieza seguimiento
  document.getElementById('btnStart')?.addEventListener('click', async ()=>{
    const btn = document.getElementById('btnStart');
    const old = btn?.innerHTML;

    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Iniciando...';
    }

    try {
      const pos = await requestGpsOnce();
      if (!pos) return;

      paintCurrentPosition(pos, 16);

      const ok = await startRoute(pos);
      if (!ok) return;

      await compute(pos);
      startWatching();

      document.getElementById('btnRecalc')?.removeAttribute('disabled');

      showToast('Ruta iniciada. Seguimiento activo.');
      mapToast('Ruta iniciada (GPS activo)');
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = old;
      }
    }
  });

  document.getElementById('btnRecalc')?.addEventListener('click', async ()=>{
    if (!currentPos){
      await locateAndCalculate();
      return;
    }
    await recompute();
    showToast('Ruta actualizada');
  });

  // FAB marca el "siguiente" directamente
  document.getElementById('fabDone')?.addEventListener('click', async (e)=>{
    const id = e.currentTarget.getAttribute('data-done');
    if (!id) return;
    const url=`${URL_DONE_BASE}/${id}/done`;

    const r = await safeJsonFetch(url, fopts({
      method:'POST',
      headers:{ 'Accept':'application/json', 'X-CSRF-TOKEN': csrf }
    }));

    if (r.ok && r.data?.ok){
      await recompute();
      showToast('Punto marcado como hecho');
      dbgChip('Punto marcado ✓');
    }else{
      showToast(r.data?.message||'No se pudo marcar', false);
      dbgChip('No se pudo marcar', true);
    }
  });

  document.getElementById('btnHideRoutePanel')?.addEventListener('click', hideRoutePanel);
  document.getElementById('btnShowRoutePanel')?.addEventListener('click', showRoutePanel);

  window.addEventListener('beforeunload', stopWatching);

  // ✅ refresco suave
  setInterval(async ()=>{ if(currentPos){ await recompute(); } }, 60000);

  // Init Google Maps. Se ejecuta cuando termina de cargar el SDK de Google.
  window.initGoogleDriverMap = async function () {
    try {
      initMap();
      await autoBoot();
    } catch (e) {
      dbgChip('No se pudo iniciar Google Maps: ' + String(e), true);
      showToast('No se pudo cargar Google Maps. Revisa API key y APIs habilitadas.', false);
      await sendClientLog('error', 'google maps init failed', { err: String(e) });
    }
  };
</script>

<script
  async
  defer
  src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.browser_key') }}&libraries=geometry&v=weekly&callback=initGoogleDriverMap">
</script>

{{-- ============================
   ✅ SNIPPET BACKEND (OPCIONAL PARA LOGS DEL CLIENTE)
   Pégalo en routes/api.php
============================ --}}
{{--
Route::middleware(['auth'])->post('/client-log', function (\Illuminate\Http\Request $r) {
    $data = $r->validate([
        'scope' => ['nullable','string','max:80'],
        'level' => ['nullable','string','max:20'],
        'message' => ['required','string','max:1000'],
        'meta' => ['nullable','array'],
    ]);

    $scope = $data['scope'] ?? 'client';
    $level = strtolower($data['level'] ?? 'info');
    $msg   = "[CLIENT_LOG][$scope] ".$data['message'];

    $ctx = [
        'user_id' => auth()->id(),
        'meta' => $data['meta'] ?? [],
        'ip' => $r->ip(),
        'ua' => substr((string)$r->userAgent(), 0, 240),
    ];

    if ($level === 'error') \Log::error($msg, $ctx);
    elseif ($level === 'warning' || $level === 'warn') \Log::warning($msg, $ctx);
    else \Log::info($msg, $ctx);

    return response()->json(['ok'=>true]);
});
--}}
@endsection