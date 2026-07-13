{{-- resources/views/supervisor/routes/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Supervisor · Ruta')
@section('content_class', 'content--flush')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div id="rp-supervisor-pro" class="container-fluid p-0">
  <style>
    #rp-supervisor-pro {
      --bg: #f9fafb;
      --card: #ffffff;
      --ink: #333333;
      --ink-strong: #111111;
      --muted: #888888;
      --line: #ebebeb;
      --blue: #007aff;
      --blue-soft: #e6f0ff;
      --success: #15803d;
      --success-soft: #e6ffe6;
      --danger: #ff4a4a;
      --danger-soft: #ffebeb;
      --amber: #c2660c;
      --amber-soft: #fff4e0;

      min-height: calc(100vh - 56px);
      background: var(--bg);
      color: var(--ink);
      font-family: 'Quicksand', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      -webkit-font-smoothing: antialiased;
      text-rendering: optimizeLegibility;
    }

    #rp-supervisor-pro *,
    #rp-supervisor-pro *::before,
    #rp-supervisor-pro *::after { box-sizing: border-box; }

    #rp-supervisor-pro #mapSup,
    #rp-supervisor-pro #mapSup * { font-family: Roboto, Arial, sans-serif !important; }

    #rp-supervisor-pro .layout {
      display: flex;
      flex-direction: column;
      gap: 16px;
      padding: 16px;
      min-height: calc(100vh - 56px);
    }

    @media (min-width: 992px) {
      #rp-supervisor-pro .layout { flex-direction: row; }
      #rp-supervisor-pro .side { flex: 0 0 390px; max-width: 390px; }
      #rp-supervisor-pro .map-column { flex: 1 1 auto; min-width: 0; }
    }

    #rp-supervisor-pro .side { min-width: 0; transition: opacity .22s ease, transform .22s ease; }
    #rp-supervisor-pro.is-panel-hidden .side { display: none !important; }
    #rp-supervisor-pro.is-panel-hidden .map-column { flex: 1 1 100%; max-width: 100%; width: 100%; }

    #rp-supervisor-pro .toolbar,
    #rp-supervisor-pro .cardx,
    #rp-supervisor-pro .metric,
    #rp-supervisor-pro .stop-card,
    #rp-supervisor-pro .telemetry-card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,.02);
    }

    #rp-supervisor-pro .toolbar {
      position: sticky;
      top: 64px;
      z-index: 6;
      padding: 16px 18px;
      margin-bottom: 14px;
    }

    #rp-supervisor-pro .toolbar-head {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 14px;
    }

    #rp-supervisor-pro .side-title {
      color: var(--ink-strong);
      font-size: 1.08rem;
      font-weight: 700;
      line-height: 1.25;
      margin: 0;
    }

    #rp-supervisor-pro .side-sub {
      color: var(--muted);
      font-size: .88rem;
      font-weight: 600;
      margin-top: 4px;
    }

    #rp-supervisor-pro .top-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

    #rp-supervisor-pro .btn-icon {
      width: 38px;
      height: 38px;
      padding: 0;
      border-radius: 999px;
      border: 1px solid var(--line);
      background: #fff;
      color: var(--muted);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 12px rgba(0,0,0,.02);
      cursor: pointer;
      transition: transform .14s ease, background .18s ease, color .18s ease, box-shadow .18s ease;
    }

    #rp-supervisor-pro .btn-icon:hover { background: #f9fafb; color: var(--ink-strong); transform: translateY(-1px); }
    #rp-supervisor-pro .btn-icon:active { transform: scale(.96); }

    #rp-supervisor-pro .live-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      min-height: 38px;
      padding: 8px 12px;
      border-radius: 999px;
      background: #f3f4f6;
      color: var(--muted);
      font-size: .85rem;
      font-weight: 700;
      margin-top: 14px;
    }

    #rp-supervisor-pro .live-dot { width: 10px; height: 10px; border-radius: 999px; background: var(--muted); flex: 0 0 auto; }
    #rp-supervisor-pro .live-pill.online { background: var(--success-soft); color: var(--success); }
    #rp-supervisor-pro .live-pill.online .live-dot { background: var(--success); box-shadow: 0 0 0 4px rgba(21,128,61,.12); }
    #rp-supervisor-pro .live-pill.warn { background: var(--amber-soft); color: var(--amber); }
    #rp-supervisor-pro .live-pill.warn .live-dot { background: var(--amber); box-shadow: 0 0 0 4px rgba(194,102,12,.12); }
    #rp-supervisor-pro .live-pill.offline { background: var(--danger-soft); color: var(--danger); }
    #rp-supervisor-pro .live-pill.offline .live-dot { background: var(--danger); box-shadow: 0 0 0 4px rgba(255,74,74,.12); }

    #rp-supervisor-pro .grid { display: grid; gap: 12px; }
    #rp-supervisor-pro .g3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }

    #rp-supervisor-pro .metric { padding: 14px; transition: transform .18s ease, box-shadow .18s ease; }
    #rp-supervisor-pro .metric:hover { transform: translateY(-2px); box-shadow: 0 10px 22px rgba(0,0,0,.05); }

    #rp-supervisor-pro .metric-label {
      color: var(--muted);
      font-size: .72rem;
      text-transform: uppercase;
      letter-spacing: .04em;
      font-weight: 700;
      margin-bottom: 8px;
    }

    #rp-supervisor-pro .metric-value { color: var(--ink-strong); font-size: 1.45rem; line-height: 1; font-weight: 700; }
    #rp-supervisor-pro .metric-help { color: var(--muted); font-size: .78rem; font-weight: 600; margin-top: 8px; }

    #rp-supervisor-pro .cardx { margin-top: 14px; overflow: hidden; }
    #rp-supervisor-pro .cardx-hd {
      padding: 16px 18px;
      border-bottom: 1px solid var(--line);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
    }

    #rp-supervisor-pro .cardx-title { color: var(--ink-strong); font-size: 1rem; font-weight: 700; margin: 0; }
    #rp-supervisor-pro .cardx-muted { color: var(--muted); font-size: .83rem; font-weight: 600; }
    #rp-supervisor-pro .cardx-body { padding: 16px 18px; }
    #rp-supervisor-pro .next-card { border-left: 4px solid var(--blue); }

    #rp-supervisor-pro .next-main { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
    #rp-supervisor-pro .next-label {
      color: var(--muted);
      font-size: .75rem;
      text-transform: uppercase;
      letter-spacing: .05em;
      font-weight: 700;
      margin-bottom: 8px;
    }

    #rp-supervisor-pro .next-name {
      color: var(--ink-strong);
      font-size: 1.25rem;
      font-weight: 700;
      line-height: 1.2;
      margin: 0;
    }

    #rp-supervisor-pro .next-meta { color: var(--muted); font-size: .86rem; font-weight: 600; margin-top: 6px; }

    #rp-supervisor-pro .badge-soft {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      border-radius: 999px;
      padding: 7px 11px;
      background: var(--blue-soft);
      color: var(--blue);
      font-size: .82rem;
      font-weight: 700;
      white-space: nowrap;
    }

    #rp-supervisor-pro .badge-soft.success { background: var(--success-soft); color: var(--success); }
    #rp-supervisor-pro .badge-soft.danger { background: var(--danger-soft); color: var(--danger); }

    #rp-supervisor-pro .telemetry-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    #rp-supervisor-pro .telemetry-card { padding: 12px; }

    #rp-supervisor-pro .telemetry-card span {
      display: block;
      color: var(--muted);
      font-size: .72rem;
      text-transform: uppercase;
      letter-spacing: .04em;
      font-weight: 700;
      margin-bottom: 6px;
    }

    #rp-supervisor-pro .telemetry-card strong {
      color: var(--ink-strong);
      font-size: .98rem;
      font-weight: 700;
      word-break: break-word;
    }

    #rp-supervisor-pro .stops-list {
      list-style: none;
      margin: 0;
      padding: 0;
      display: grid;
      gap: 10px;
      max-height: calc(100vh - 565px);
      min-height: 220px;
      overflow: auto;
      padding-right: 4px;
    }

    #rp-supervisor-pro .stops-list::-webkit-scrollbar { width: 6px; }
    #rp-supervisor-pro .stops-list::-webkit-scrollbar-thumb { background: #d7dce3; border-radius: 999px; }

    #rp-supervisor-pro .stop-card {
      padding: 12px;
      display: grid;
      grid-template-columns: 36px 1fr auto;
      gap: 10px;
      align-items: flex-start;
      transition: transform .18s ease, box-shadow .18s ease;
    }

    #rp-supervisor-pro .stop-card:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(0,0,0,.04); }

    #rp-supervisor-pro .stop-num {
      width: 32px;
      height: 32px;
      border-radius: 999px;
      background: var(--blue-soft);
      color: var(--blue);
      display: grid;
      place-items: center;
      font-size: .86rem;
      font-weight: 700;
    }

    #rp-supervisor-pro .stop-num.done { background: var(--success-soft); color: var(--success); }
    #rp-supervisor-pro .stop-info { min-width: 0; }

    #rp-supervisor-pro .stop-info strong {
      display: block;
      color: var(--ink-strong);
      font-size: .93rem;
      font-weight: 700;
      line-height: 1.25;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    #rp-supervisor-pro .stop-info span { display: block; color: var(--muted); font-size: .78rem; font-weight: 600; margin-top: 4px; }

    #rp-supervisor-pro .status-pill {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 999px;
      padding: 6px 9px;
      font-size: .74rem;
      font-weight: 700;
      white-space: nowrap;
    }

    #rp-supervisor-pro .status-pill.done { background: var(--success-soft); color: var(--success); }
    #rp-supervisor-pro .status-pill.pending { background: var(--blue-soft); color: var(--blue); }

    #rp-supervisor-pro .map-shell {
      position: relative;
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 18px;
      padding: 12px;
      box-shadow: 0 6px 18px rgba(0,0,0,.03);
      height: calc(100vh - 88px);
      min-height: 620px;
    }

    #rp-supervisor-pro #mapSup {
      width: 100%;
      height: 100%;
      border-radius: 14px;
      background: #eef1f5;
      overflow: hidden;
    }

    #rp-supervisor-pro .map-error,
    #rp-supervisor-pro .map-status {
      position: absolute;
      top: 22px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 1000;
      display: none;
      width: min(540px, calc(100% - 44px));
      padding: 13px 16px;
      border-radius: 14px;
      font-weight: 700;
      font-size: .9rem;
      box-shadow: 0 10px 24px rgba(0,0,0,.12);
    }

    #rp-supervisor-pro .map-error { background: var(--danger-soft); color: var(--danger); }
    #rp-supervisor-pro .map-status { background: rgba(255,255,255,.96); color: var(--ink); border: 1px solid var(--line); }
    #rp-supervisor-pro .map-error.show,
    #rp-supervisor-pro .map-status.show { display: block; }

    #rp-supervisor-pro .map-controls {
      position: absolute;
      right: 22px;
      top: 22px;
      z-index: 710;
      display: grid;
      gap: 10px;
    }

    #rp-supervisor-pro .gm-btn {
      width: 48px;
      height: 48px;
      border: 1px solid var(--line);
      border-radius: 999px;
      background: rgba(255,255,255,.96);
      color: var(--ink-strong);
      display: grid;
      place-items: center;
      font-size: 20px;
      cursor: pointer;
      box-shadow: 0 8px 22px rgba(0,0,0,.14);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      transition: transform .12s ease, color .18s ease, box-shadow .18s ease, background .18s ease;
    }

    #rp-supervisor-pro .gm-btn:hover { background: #fff; color: var(--blue); transform: translateY(-1px); }
    #rp-supervisor-pro .gm-btn:active { transform: scale(.96); }
    #rp-supervisor-pro .gm-btn.active { color: var(--blue); box-shadow: 0 0 0 4px rgba(0,122,255,.12), 0 8px 22px rgba(0,0,0,.14); }

    #rp-supervisor-pro .desktop-traffic-widget {
      position: absolute;
      left: 50%;
      bottom: 18px;
      z-index: 700;
      transform: translateX(-50%);
      display: inline-flex;
      align-items: center;
      gap: 16px;
      min-height: 46px;
      padding: 8px 14px;
      border: 1px solid var(--line);
      border-radius: 12px;
      background: rgba(255,255,255,.94);
      box-shadow: 0 8px 24px rgba(0,0,0,.14);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      pointer-events: auto;
    }

    #rp-supervisor-pro .traffic-selector {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border: 0;
      background: transparent;
      color: var(--ink);
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      white-space: nowrap;
      padding: 0;
      font-family: inherit;
    }

    #rp-supervisor-pro .traffic-selector i { font-size: 11px; color: #555555; }
    #rp-supervisor-pro .traffic-scale { display: inline-flex; align-items: center; gap: 7px; color: #555555; font-size: 13px; font-style: italic; font-weight: 600; white-space: nowrap; }
    #rp-supervisor-pro .traffic-bars { display: inline-flex; align-items: center; gap: 3px; }
    #rp-supervisor-pro .traffic-bars .bar { display: block; width: 19px; height: 8px; border-radius: 2px; }
    #rp-supervisor-pro .traffic-bars .green { background: #00b050; }
    #rp-supervisor-pro .traffic-bars .yellow { background: #f8d33a; }
    #rp-supervisor-pro .traffic-bars .orange { background: #f59e0b; }
    #rp-supervisor-pro .traffic-bars .red { background: #ef4444; }
    #rp-supervisor-pro .traffic-bars .darkred { background: #991b1b; }

    #rp-supervisor-pro .traffic-switch { position: relative; width: 45px; height: 26px; margin: 0; flex: 0 0 auto; }
    #rp-supervisor-pro .traffic-switch input { display: none; }
    #rp-supervisor-pro .traffic-switch span { position: absolute; inset: 0; border-radius: 999px; background: #d1d5db; cursor: pointer; transition: background .2s ease; }
    #rp-supervisor-pro .traffic-switch span::after {
      content: "";
      position: absolute;
      top: 4px;
      left: 4px;
      width: 18px;
      height: 18px;
      border-radius: 999px;
      background: #ffffff;
      box-shadow: 0 2px 6px rgba(0,0,0,.18);
      transition: transform .2s ease;
    }
    #rp-supervisor-pro .traffic-switch input:checked + span { background: var(--blue); }
    #rp-supervisor-pro .traffic-switch input:checked + span::after { transform: translateX(19px); }

    #rp-supervisor-pro .show-panel-btn {
      position: fixed;
      left: 22px;
      bottom: 22px;
      z-index: 1200;
      min-height: 52px;
      padding: 0 20px;
      border: 0;
      border-radius: 999px;
      background: var(--blue);
      color: #fff;
      font-weight: 700;
      font-size: .95rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
      box-shadow: 0 14px 30px rgba(0,122,255,.28);
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
      transform: translateY(16px);
      transition: opacity .22s ease, transform .22s ease, visibility .22s ease;
      font-family: inherit;
      cursor: pointer;
    }

    #rp-supervisor-pro .show-panel-btn.is-visible { opacity: 1; visibility: visible; pointer-events: auto; transform: translateY(0); }

    #rp-supervisor-pro .exit-street-view-btn {
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
      cursor: pointer;
    }

    #rp-supervisor-pro .exit-street-view-btn.is-visible { opacity: 1; visibility: visible; pointer-events: auto; transform: translateX(-50%) translateY(0); }

    #rp-supervisor-pro .toastx {
      position: fixed;
      left: 50%;
      bottom: 24px;
      z-index: 2100;
      transform: translateX(-50%);
      display: none;
      background: #111111;
      color: #fff;
      padding: .78rem 1.1rem;
      border-radius: 14px;
      box-shadow: 0 12px 30px rgba(0,0,0,.2);
      font-size: .9rem;
      font-weight: 700;
    }

    #rp-supervisor-pro .toastx.show { display: block; }
    #rp-supervisor-pro .drawer-grip { display: none; }

    @media (max-width: 991.98px) {
      #rp-supervisor-pro { min-height: 100dvh; overflow: hidden; background: #000; }
      #rp-supervisor-pro .layout { padding: 0; gap: 0; min-height: 100dvh; display: block; }
      #rp-supervisor-pro .map-column { width: 100%; }
      #rp-supervisor-pro .map-shell { height: 100dvh; min-height: 100dvh; border: 0; border-radius: 0; padding: 0; box-shadow: none; }
      #rp-supervisor-pro #mapSup { border-radius: 0; }

      #rp-supervisor-pro .side {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 900;
        height: min(58svh, 560px);
        max-width: none;
        background: var(--card);
        border: 1px solid var(--line);
        border-bottom: 0;
        border-radius: 26px 26px 0 0;
        box-shadow: 0 -20px 48px rgba(0,0,0,.18);
        overflow: auto;
        -webkit-overflow-scrolling: touch;
        padding: 8px 14px calc(env(safe-area-inset-bottom) + 18px);
        transition: transform .28s cubic-bezier(.2,.8,.2,1);
        display: block !important;
        transform: translateY(0);
      }

      #rp-supervisor-pro .side.is-collapsed { transform: translateY(calc(100% - 104px)); }

      #rp-supervisor-pro .drawer-grip {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 6px 0 8px;
        position: sticky;
        top: 0;
        z-index: 5;
        background: var(--card);
        border-radius: 26px 26px 0 0;
      }

      #rp-supervisor-pro .drawer-grip button {
        border: 0;
        background: transparent;
        display: grid;
        gap: 5px;
        place-items: center;
        color: var(--muted);
        font-weight: 700;
        width: 100%;
        padding: 0;
        font-family: inherit;
      }

      #rp-supervisor-pro .drawer-grip .bar { width: 54px; height: 5px; border-radius: 999px; background: #d7dce3; }
      #rp-supervisor-pro .drawer-grip .txt { display: flex; align-items: center; justify-content: center; gap: 6px; font-size: .8rem; }

      #rp-supervisor-pro .toolbar { position: relative; top: auto; border: 0; border-radius: 18px; box-shadow: none; padding: 8px 4px 12px; margin-bottom: 8px; }
      #rp-supervisor-pro .g3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
      #rp-supervisor-pro .metric { padding: 13px; min-height: 112px; }
      #rp-supervisor-pro .metric-value { font-size: 1.25rem; }
      #rp-supervisor-pro .telemetry-grid { grid-template-columns: 1fr 1fr; }
      #rp-supervisor-pro .stops-list { max-height: none; min-height: 220px; }
      #rp-supervisor-pro .desktop-traffic-widget { display: none; }
      #rp-supervisor-pro .map-controls { right: 16px; top: calc(env(safe-area-inset-top) + 150px); }
      #rp-supervisor-pro .gm-btn { width: 54px; height: 54px; font-size: 23px; }
      #rp-supervisor-pro .show-panel-btn { display: none !important; }

      #rp-supervisor-pro .map-error,
      #rp-supervisor-pro .map-status {
        top: calc(env(safe-area-inset-top) + 18px);
        width: calc(100% - 32px);
        text-align: center;
      }

      #rp-supervisor-pro .exit-street-view-btn {
        top: 14px;
        left: 14px;
        right: 14px;
        width: calc(100% - 28px);
        transform: translateY(-16px);
      }

      #rp-supervisor-pro .exit-street-view-btn.is-visible { transform: translateY(0); }
      #rp-supervisor-pro .toastx { top: calc(env(safe-area-inset-top) + 18px); bottom: auto; width: calc(100% - 32px); text-align: center; border-radius: 18px; padding: 1rem; }
    }

    @media (max-width: 575.98px) {
      #rp-supervisor-pro .g3 { grid-template-columns: 1fr 1fr 1fr; gap: 9px; }
      #rp-supervisor-pro .metric-label { font-size: .68rem; }
      #rp-supervisor-pro .metric-value { font-size: 1.08rem; }
      #rp-supervisor-pro .metric-help { font-size: .72rem; }
      #rp-supervisor-pro .stop-card { grid-template-columns: 32px 1fr; }
      #rp-supervisor-pro .stop-card .status-pill { grid-column: 2; justify-self: start; }
    }
  </style>

  <div class="layout">
    <aside class="side is-collapsed" id="supervisorPanel">
      <div class="drawer-grip">
        <button type="button" id="btnToggleMobilePanel" aria-expanded="false">
          <span class="bar"></span>
          <span class="txt">
            <i class="bi bi-chevron-up" id="mobilePanelIcon"></i>
            Ver detalles de la ruta
          </span>
        </button>
      </div>

      <div class="toolbar">
        <div class="toolbar-head">
          <div>
            <h1 class="side-title">{{ $routePlan->name ?? ('Ruta #'.$routePlan->id) }}</h1>
            <div class="side-sub">Chofer: {{ $routePlan->driver?->name ?? '—' }}</div>
          </div>

          <div class="top-actions">
            <button type="button" class="btn-icon" id="btnHidePanel" title="Ocultar panel">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
        </div>

        <div class="live-pill offline" id="presencePill">
          <span class="live-dot"></span>
          <span id="presenceTxt">Conectando…</span>
        </div>
      </div>

      <div class="grid g3">
        <div class="metric">
          <div class="metric-label">Total</div>
          <div class="metric-value" id="kTotal">—</div>
          <div class="metric-help">Paradas</div>
        </div>

        <div class="metric">
          <div class="metric-label">Hechos</div>
          <div class="metric-value" id="kDone">—</div>
          <div class="metric-help">Completadas</div>
        </div>

        <div class="metric">
          <div class="metric-label">Pendientes</div>
          <div class="metric-value" id="kPending">—</div>
          <div class="metric-help">Por visitar</div>
        </div>
      </div>

      <div class="cardx next-card">
        <div class="cardx-body">
          <div class="next-main">
            <div>
              <div class="next-label">Siguiente punto</div>
              <h2 class="next-name" id="nextStopName">—</h2>
              <div class="next-meta" id="nextStopMeta">— • llegada —</div>
            </div>

            <span class="badge-soft success">
              <i class="bi bi-lightning-charge"></i>
              Prioridad
            </span>
          </div>
        </div>
      </div>

      <div class="cardx">
        <div class="cardx-hd">
          <h3 class="cardx-title">Telemetría</h3>
          <span class="cardx-muted" id="serverTime">—</span>
        </div>

        <div class="cardx-body">
          <div class="telemetry-grid">
            <div class="telemetry-card"><span>Velocidad</span><strong id="tSpeed">—</strong></div>
            <div class="telemetry-card"><span>Precisión</span><strong id="tAccuracy">—</strong></div>
            <div class="telemetry-card"><span>Batería</span><strong id="tBattery">—</strong></div>
            <div class="telemetry-card"><span>Red</span><strong id="tNetwork">—</strong></div>
            <div class="telemetry-card"><span>App</span><strong id="tAppState">—</strong></div>
            <div class="telemetry-card"><span>Mock GPS</span><strong id="tMocked">—</strong></div>
          </div>
        </div>
      </div>

      <div class="cardx">
        <div class="cardx-hd">
          <h3 class="cardx-title">Paradas</h3>
          <span class="cardx-muted">Estado en vivo</span>
        </div>

        <div class="cardx-body">
          <ul class="stops-list" id="stopsList"></ul>
        </div>
      </div>
    </aside>

    <main class="map-column">
      <div class="map-shell">
        <div class="map-error" id="mapError">
          No se pudo cargar Google Maps. Revisa tu API Key, facturación y APIs habilitadas.
        </div>

        <div class="map-status" id="mapStatus">
          Sin ubicación reciente del chofer. Cuando el chofer inicie ruta o comparta GPS, aparecerá aquí.
        </div>

        <div id="mapSup"></div>

        <div class="map-controls">
          <button type="button" class="gm-btn" id="btnCenterDriver" title="Centrar chofer">
            <i class="bi bi-crosshair"></i>
          </button>

          <button type="button" class="gm-btn active" id="btnFollowDriver" title="Seguir chofer">
            <i class="bi bi-navigation-fill"></i>
          </button>

          <button type="button" class="gm-btn active" id="btnTrafficMap" title="Tráfico">
            <i class="bi bi-signpost-split"></i>
          </button>

          <button type="button" class="gm-btn" id="btnToggleMapType" title="Mapa/Satélite">
            <i class="bi bi-layers-fill"></i>
          </button>

          <button type="button" class="gm-btn" id="btnRefreshNow" title="Actualizar">
            <i class="bi bi-arrow-clockwise"></i>
          </button>
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
      </div>
    </main>
  </div>

  <button type="button" class="show-panel-btn" id="btnShowPanel">
    <i class="bi bi-list-check"></i>
    Ver ruta
  </button>

  <button type="button" class="exit-street-view-btn" id="btnExitStreetView">
    <i class="bi bi-x-lg"></i>
    Salir de vista de calle
  </button>

  <div class="toastx" id="toastx">Listo</div>
</div>

<script>
  const PLAN_ID = @json($routePlan->id);
  const POLL_URL = @json(route('api.supervisor.routes.poll', $routePlan));

  let map = null;
  let trafficLayer = null;
  let trafficEnabled = true;
  let followDriver = true;
  let currentMapType = 'roadmap';

  let driverMarker = null;
  let driverInfoWindow = null;
  let accuracyCircle = null;
  let routeLine = null;
  let trailLine = null;
  let stopMarkers = [];
  let driverTrail = [];
  let firstFitDone = false;
  let lastDriverPosition = null;
  let pollTimer = null;

  function toNum(value) {
    const number = Number(value);
    return Number.isFinite(number) ? number : null;
  }

  function isValid(lat, lng) {
    if (lat === null || lng === null) return false;
    if (Math.abs(lat) < 0.000001 && Math.abs(lng) < 0.000001) return false;
    return Math.abs(lat) <= 90 && Math.abs(lng) <= 180;
  }

  function safeText(value, fallback = '') {
    if (value === null || value === undefined) return fallback;
    return String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function showToast(message, ok = true) {
    const toast = document.getElementById('toastx');
    if (!toast) return;
    toast.textContent = message || 'Listo';
    toast.style.background = ok ? '#111111' : '#991b1b';
    toast.classList.add('show');
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => toast.classList.remove('show'), 2400);
  }

  function setMapError(show = true) {
    document.getElementById('mapError')?.classList.toggle('show', !!show);
  }

  function setMapStatus(show = true, text = null) {
    const box = document.getElementById('mapStatus');
    if (!box) return;
    if (text) box.textContent = text;
    box.classList.toggle('show', !!show);
  }

  function formatCoord(lat, lng) {
    if (!isValid(lat, lng)) return '—';
    return `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
  }

  function formatSpeed(value) {
    const n = Number(value);
    if (!Number.isFinite(n)) return '—';
    if (n <= 70) return `${Math.round(n * 3.6)} km/h`;
    return `${Math.round(n)} km/h`;
  }

  function formatAccuracy(value) {
    const n = Number(value);
    if (!Number.isFinite(n)) return '—';
    return `${Math.round(n)} m`;
  }

  function formatBattery(value) {
    const n = Number(value);
    if (!Number.isFinite(n)) return '—';
    return `${Math.round(n)}%`;
  }

  function formatLastSeen(value) {
    if (!value) return '—';
    try {
      const date = new Date(value);
      return date.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
    } catch (e) {
      return '—';
    }
  }

  function isMobileView() {
    return window.matchMedia('(max-width: 991.98px)').matches;
  }

  function refreshMapLayout() {
    setTimeout(() => {
      try {
        if (window.google && map) {
          google.maps.event.trigger(map, 'resize');
          if (followDriver && lastDriverPosition) map.panTo(lastDriverPosition);
        }
      } catch (e) {}
    }, 300);
  }

  function hidePanel() {
    if (isMobileView()) {
      setMobilePanel(false);
      return;
    }
    document.getElementById('rp-supervisor-pro')?.classList.add('is-panel-hidden');
    document.getElementById('btnShowPanel')?.classList.add('is-visible');
    refreshMapLayout();
  }

  function showPanel() {
    if (isMobileView()) {
      setMobilePanel(true);
      return;
    }
    document.getElementById('rp-supervisor-pro')?.classList.remove('is-panel-hidden');
    document.getElementById('btnShowPanel')?.classList.remove('is-visible');
    refreshMapLayout();
  }

  function setMobilePanel(open) {
    const panel = document.getElementById('supervisorPanel');
    const icon = document.getElementById('mobilePanelIcon');
    const btn = document.getElementById('btnToggleMobilePanel');
    if (!panel || !isMobileView()) return;
    panel.classList.toggle('is-collapsed', !open);
    btn?.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (icon) icon.className = open ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
    refreshMapLayout();
  }

  function makeStopIcon(number, done = false) {
    const color = done ? '#15803d' : '#007aff';
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

  function makeDriverIcon(heading = null, online = true) {
    const rotation = Number.isFinite(Number(heading)) ? Number(heading) : 0;
    const color = online ? '#15803d' : '#888888';
    return {
      path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
      scale: 7,
      fillColor: color,
      fillOpacity: 1,
      strokeColor: '#ffffff',
      strokeWeight: 2.5,
      rotation,
      anchor: new google.maps.Point(0, 2),
    };
  }

  function clearStopMarkers() {
    stopMarkers.forEach(marker => {
      try { marker.setMap(null); } catch (e) {}
    });
    stopMarkers = [];
  }

  function clearDriverVisuals() {
    if (driverMarker) {
      try { driverMarker.setMap(null); } catch (e) {}
      driverMarker = null;
    }

    if (accuracyCircle) {
      try { accuracyCircle.setMap(null); } catch (e) {}
      accuracyCircle = null;
    }

    if (trailLine) {
      try { trailLine.setMap(null); } catch (e) {}
      trailLine = null;
    }

    driverTrail = [];
    lastDriverPosition = null;
  }

  function resetTelemetry() {
    document.getElementById('tSpeed').textContent = '—';
    document.getElementById('tAccuracy').textContent = '—';
    document.getElementById('tBattery').textContent = '—';
    document.getElementById('tNetwork').textContent = '—';
    document.getElementById('tAppState').textContent = '—';
    document.getElementById('tMocked').textContent = '—';
  }

  function renderStopsList(stops = []) {
    const list = document.getElementById('stopsList');
    if (!list) return;
    list.innerHTML = '';

    if (!stops.length) {
      list.innerHTML = `
        <li class="stop-card">
          <div class="stop-num">—</div>
          <div class="stop-info">
            <strong>Sin paradas</strong>
            <span>No hay puntos registrados.</span>
          </div>
        </li>
      `;
      return;
    }

    stops.forEach((stop, index) => {
      const lat = toNum(stop.lat);
      const lng = toNum(stop.lng);
      const done = stop.status === 'done';
      const sequence = stop.sequence_index ?? (index + 1);

      list.insertAdjacentHTML('beforeend', `
        <li class="stop-card">
          <div class="stop-num ${done ? 'done' : ''}">${sequence}</div>
          <div class="stop-info">
            <strong>${safeText(stop.name || 'Punto')}</strong>
            <span>${formatCoord(lat, lng)}</span>
            ${stop.done_at ? `<span>Finalizado: ${safeText(stop.done_at)}</span>` : ''}
          </div>
          <span class="status-pill ${done ? 'done' : 'pending'}">${done ? 'Hecho' : 'Pendiente'}</span>
        </li>
      `);
    });
  }

  function renderStopMarkers(stops = []) {
    if (!map || !window.google) return;
    clearStopMarkers();

    stops.forEach((stop, index) => {
      const lat = toNum(stop.lat);
      const lng = toNum(stop.lng);
      if (!isValid(lat, lng)) return;

      const done = stop.status === 'done';
      const sequence = stop.sequence_index ?? (index + 1);
      const marker = new google.maps.Marker({
        position: { lat, lng },
        map,
        icon: makeStopIcon(sequence, done),
        title: stop.name || `Punto ${sequence}`,
      });

      const info = new google.maps.InfoWindow({
        content: `
          <div style="font-family:Quicksand,Arial,sans-serif;min-width:190px">
            <div style="font-weight:700;color:#111;margin-bottom:5px">#${sequence}. ${safeText(stop.name || 'Punto')}</div>
            <div style="font-size:12px;color:#888;margin-bottom:8px">${formatCoord(lat, lng)}</div>
            <div style="display:inline-flex;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:700;background:${done ? '#e6ffe6' : '#e6f0ff'};color:${done ? '#15803d' : '#007aff'}">${done ? 'Hecho' : 'Pendiente'}</div>
          </div>
        `,
      });

      marker.addListener('click', () => info.open({ map, anchor: marker }));
      stopMarkers.push(marker);
    });
  }

  function renderRouteLine(stops = []) {
    if (!map || !window.google) return;
    if (routeLine) {
      routeLine.setMap(null);
      routeLine = null;
    }

    const path = stops
      .map(stop => ({ lat: toNum(stop.lat), lng: toNum(stop.lng) }))
      .filter(point => isValid(point.lat, point.lng));

    if (path.length < 2) return;

    routeLine = new google.maps.Polyline({
      path,
      map,
      geodesic: true,
      strokeColor: '#007aff',
      strokeOpacity: .75,
      strokeWeight: 4,
    });
  }

  function updateDriverTrail(position) {
    if (!map || !window.google || !position) return;

    const last = driverTrail[driverTrail.length - 1];
    if (!last || Math.abs(last.lat - position.lat) > 0.00001 || Math.abs(last.lng - position.lng) > 0.00001) {
      driverTrail.push(position);
    }

    if (driverTrail.length > 180) driverTrail = driverTrail.slice(-180);

    if (trailLine) {
      trailLine.setMap(null);
      trailLine = null;
    }

    if (driverTrail.length < 2) return;

    trailLine = new google.maps.Polyline({
      path: driverTrail,
      map,
      geodesic: true,
      strokeColor: '#15803d',
      strokeOpacity: .9,
      strokeWeight: 4,
    });
  }

  function updateDriverMarker(driver = {}) {
    if (!map || !window.google) return null;

    const lastPosition = driver?.last_position || null;

    if (!lastPosition) {
      clearDriverVisuals();
      setMapStatus(true, 'Sin ubicación reciente del chofer. Cuando el chofer inicie ruta o comparta GPS, aparecerá aquí.');
      resetTelemetry();
      return null;
    }

    const rawLat = toNum(lastPosition.lat);
    const rawLng = toNum(lastPosition.lng);

    // Supervisor: usamos GPS real del celular. No usamos snap_lat/snap_lng para evitar posiciones falsas.
    const lat = rawLat;
    const lng = rawLng;

    if (!isValid(lat, lng)) {
      clearDriverVisuals();
      setMapStatus(true, 'La última ubicación recibida es inválida. Esperando nueva señal del chofer.');
      resetTelemetry();
      return null;
    }

    setMapStatus(false);

    const position = { lat, lng };
    const online = driver?.presence?.state === 'online';
    lastDriverPosition = position;
    updateDriverTrail(position);

    if (!driverMarker) {
      driverMarker = new google.maps.Marker({
        position,
        map,
        icon: makeDriverIcon(lastPosition.heading, online),
        title: driver?.name || 'Chofer',
        zIndex: 999,
      });
    } else {
      driverMarker.setPosition(position);
      driverMarker.setIcon(makeDriverIcon(lastPosition.heading, online));
    }

    const accuracy = toNum(lastPosition.accuracy);
    if (accuracyCircle) {
      accuracyCircle.setMap(null);
      accuracyCircle = null;
    }

    if (accuracy && accuracy > 0) {
      accuracyCircle = new google.maps.Circle({
        map,
        center: position,
        radius: accuracy,
        strokeColor: '#007aff',
        strokeOpacity: .35,
        strokeWeight: 1,
        fillColor: '#007aff',
        fillOpacity: .08,
      });
    }

    if (!driverInfoWindow) driverInfoWindow = new google.maps.InfoWindow();

    driverInfoWindow.setContent(`
      <div style="font-family:Quicksand,Arial,sans-serif;min-width:220px">
        <div style="font-weight:700;color:#111;margin-bottom:5px">${safeText(driver?.name || 'Chofer')}</div>
        <div style="font-size:12px;color:#888;margin-bottom:8px">${formatCoord(lat, lng)}</div>
        <div style="font-size:12px;color:#333;display:grid;gap:4px">
          <div><strong>Estado:</strong> ${online ? 'En línea' : 'Sin conexión'}</div>
          <div><strong>Velocidad:</strong> ${formatSpeed(lastPosition.speed)}</div>
          <div><strong>Precisión:</strong> ${formatAccuracy(lastPosition.accuracy)}</div>
          <div><strong>Batería:</strong> ${formatBattery(lastPosition.battery)}</div>
          <div><strong>Último visto:</strong> ${formatLastSeen(lastPosition.seen_at || lastPosition.received_at || driver?.presence?.last_seen_at)}</div>
        </div>
      </div>
    `);

    if (!driverMarker.__hasClick) {
      driverMarker.addListener('click', () => driverInfoWindow.open({ map, anchor: driverMarker }));
      driverMarker.__hasClick = true;
    }

    if (followDriver) map.panTo(position);
    return position;
  }

  function fitAll(stops = [], driverPosition = null) {
    if (!map || !window.google) return;
    const bounds = new google.maps.LatLngBounds();

    stops.forEach(stop => {
      const lat = toNum(stop.lat);
      const lng = toNum(stop.lng);
      if (isValid(lat, lng)) bounds.extend({ lat, lng });
    });

    if (driverPosition && isValid(driverPosition.lat, driverPosition.lng)) bounds.extend(driverPosition);
    if (!bounds.isEmpty()) map.fitBounds(bounds, 72);
  }

  function renderPresence(driver = {}) {
    const pill = document.getElementById('presencePill');
    const txt = document.getElementById('presenceTxt');
    if (!pill || !txt) return;

    const presence = driver?.presence || {};
    const state = presence.state || 'offline';

    pill.classList.remove('online', 'offline', 'warn');

    if (state === 'online') {
      pill.classList.add(presence.warn ? 'warn' : 'online');
      txt.textContent = presence.warn ? 'Señal tardía' : 'Chofer en vivo';
    } else {
      pill.classList.add('offline');
      txt.textContent = presence.message || 'Sin ubicación reciente';
    }
  }

  function renderTelemetry(driver = {}) {
    const pos = driver?.last_position || null;

    if (!pos) {
      resetTelemetry();
      return;
    }

    document.getElementById('tSpeed').textContent = formatSpeed(pos.speed);
    document.getElementById('tAccuracy').textContent = formatAccuracy(pos.accuracy);
    document.getElementById('tBattery').textContent = formatBattery(pos.battery);
    document.getElementById('tNetwork').textContent = pos.network ? safeText(pos.network) : '—';
    document.getElementById('tAppState').textContent = pos.app_state ? safeText(pos.app_state) : '—';
    document.getElementById('tMocked').textContent = pos.is_mocked === null || pos.is_mocked === undefined ? '—' : (pos.is_mocked ? 'Sí' : 'No');
  }

  function renderKpis(data = {}) {
    document.getElementById('kTotal').textContent = data.kpis?.total ?? '—';
    document.getElementById('kDone').textContent = data.kpis?.done ?? '—';
    document.getElementById('kPending').textContent = data.kpis?.pending ?? '—';
    document.getElementById('serverTime').textContent = data.server_time ? formatLastSeen(data.server_time) : '—';
  }

  function renderNextStop(stops = []) {
    const pending = stops.find(stop => stop.status !== 'done');
    document.getElementById('nextStopName').textContent = pending?.name || '—';
    document.getElementById('nextStopMeta').textContent = pending ? 'Pendiente • esperando llegada' : 'Ruta completada';
  }

  function renderAll(data = {}) {
    const stops = data.stops || [];
    const driver = data.driver || {};

    renderKpis(data);
    renderPresence(driver);
    renderTelemetry(driver);
    renderNextStop(stops);
    renderStopsList(stops);
    renderStopMarkers(stops);
    renderRouteLine(stops);

    const driverPosition = updateDriverMarker(driver);

    if (!firstFitDone) {
      fitAll(stops, driverPosition);
      firstFitDone = true;
    }
  }

  async function poll() {
    try {
      const response = await fetch(POLL_URL, {
        headers: { 'Accept': 'application/json' },
        credentials: 'include',
      });

      const data = await response.json().catch(() => null);

      if (!response.ok || !data) {
        showToast('Error API (' + response.status + ')', false);
        return;
      }

      console.log('[SUPERVISOR_POLL]', {
        ok: data.ok,
        driver_id: data.driver?.id,
        last_position: data.driver?.last_position,
        debug_last_saved: data.driver?.debug_last_saved,
        presence: data.driver?.presence,
      });

      renderAll(data);
    } catch (e) {
      showToast('Sin conexión con servidor', false);
    }
  }

  function setupTrafficControls() {
    trafficLayer = new google.maps.TrafficLayer();
    trafficLayer.setMap(map);

    const trafficToggle = document.getElementById('trafficToggle');
    const btnDesktopTraffic = document.getElementById('btnDesktopTraffic');
    const btnTrafficMap = document.getElementById('btnTrafficMap');

    function applyTrafficState() {
      trafficLayer.setMap(trafficEnabled ? map : null);
      if (trafficToggle) trafficToggle.checked = trafficEnabled;
      btnTrafficMap?.classList.toggle('active', trafficEnabled);
    }

    trafficToggle?.addEventListener('change', () => {
      trafficEnabled = trafficToggle.checked;
      applyTrafficState();
      showToast(trafficEnabled ? 'Tráfico activado' : 'Tráfico oculto');
    });

    btnDesktopTraffic?.addEventListener('click', () => {
      trafficEnabled = !trafficEnabled;
      applyTrafficState();
      showToast(trafficEnabled ? 'Tráfico activado' : 'Tráfico oculto');
    });

    btnTrafficMap?.addEventListener('click', () => {
      trafficEnabled = !trafficEnabled;
      applyTrafficState();
      showToast(trafficEnabled ? 'Tráfico activado' : 'Tráfico oculto');
    });

    applyTrafficState();
  }

  function setupStreetViewExitButton() {
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
        try { google.maps.event.trigger(map, 'resize'); } catch (e) {}
      }, 120);
    });
  }

  function bindUi() {
    document.getElementById('btnHidePanel')?.addEventListener('click', hidePanel);
    document.getElementById('btnShowPanel')?.addEventListener('click', showPanel);

    document.getElementById('btnCenterDriver')?.addEventListener('click', () => {
      if (!lastDriverPosition) {
        showToast('Aún no hay ubicación reciente del chofer', false);
        return;
      }
      followDriver = true;
      document.getElementById('btnFollowDriver')?.classList.add('active');
      map.panTo(lastDriverPosition);
      map.setZoom(16);
      showToast('Centrado en chofer');
    });

    document.getElementById('btnFollowDriver')?.addEventListener('click', () => {
      followDriver = !followDriver;
      document.getElementById('btnFollowDriver')?.classList.toggle('active', followDriver);
      showToast(followDriver ? 'Siguiendo chofer' : 'Seguimiento pausado');
    });

    document.getElementById('btnToggleMapType')?.addEventListener('click', () => {
      currentMapType = currentMapType === 'roadmap' ? 'hybrid' : 'roadmap';
      map.setMapTypeId(currentMapType);
      document.getElementById('btnToggleMapType')?.classList.toggle('active', currentMapType !== 'roadmap');
    });

    document.getElementById('btnRefreshNow')?.addEventListener('click', async () => {
      await poll();
      showToast('Actualizado');
    });

    const panel = document.getElementById('supervisorPanel');
    const btnToggleMobilePanel = document.getElementById('btnToggleMobilePanel');

    btnToggleMobilePanel?.addEventListener('click', () => {
      if (!isMobileView()) return;
      setMobilePanel(panel?.classList.contains('is-collapsed'));
    });

    let touchStartY = 0;
    let touchEndY = 0;

    panel?.addEventListener('touchstart', event => {
      if (!isMobileView()) return;
      touchStartY = event.changedTouches[0].clientY;
    }, { passive: true });

    panel?.addEventListener('touchend', event => {
      if (!isMobileView()) return;
      touchEndY = event.changedTouches[0].clientY;
      const diff = touchEndY - touchStartY;
      if (Math.abs(diff) < 42) return;
      setMobilePanel(diff < 0);
    }, { passive: true });

    window.addEventListener('resize', () => {
      if (isMobileView()) {
        document.getElementById('supervisorPanel')?.classList.add('is-collapsed');
      } else {
        document.getElementById('supervisorPanel')?.classList.remove('is-collapsed');
        document.getElementById('rp-supervisor-pro')?.classList.remove('is-panel-hidden');
        document.getElementById('btnShowPanel')?.classList.remove('is-visible');
      }
      refreshMapLayout();
    });
  }

  function initGoogleSupervisorMap() {
    try {
      setMapError(false);

      map = new google.maps.Map(document.getElementById('mapSup'), {
        center: { lat: 19.4326, lng: -99.1332 },
        zoom: 11,
        mapTypeId: isMobileView() ? 'hybrid' : 'roadmap',
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
      bindUi();

      poll();
      pollTimer = setInterval(poll, 5000);
    } catch (e) {
      setMapError(true);
      showToast('No se pudo cargar Google Maps', false);
    }
  }

  window.initGoogleSupervisorMap = initGoogleSupervisorMap;

  window.gm_authFailure = function () {
    setMapError(true);
    showToast('Error con la API Key de Google Maps', false);
  };
</script>

<script
  async
  defer
  src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.browser_key') }}&libraries=places,geometry&v=weekly&callback=initGoogleSupervisorMap">
</script>
@endsection
