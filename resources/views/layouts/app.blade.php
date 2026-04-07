
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Panel')</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo-mail.png') }}">
  <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logo-mail.png') }}">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/logo-mail.png') }}">

  <link rel="stylesheet" href="{{ asset('css/app-layout.css') }}?v={{ time() }}">
  @stack('styles')

  <style>
    :root{
      --bg:#f4f7fc;
      --surface:#ffffff;
      --surface-soft:#f8faff;
      --panel:#ffffff;

      --primary:#2f6df6;
      --primary-2:#1f56cf;
      --primary-soft:#eaf1ff;

      --accent:#ff5ca8;
      --text:#111827;
      --text-2:#1f2937;
      --muted:#6b7280;
      --muted-2:#94a3b8;

      --border:#dbe4f2;
      --border-strong:#cad7eb;

      --success:#13b981;
      --success-soft:#dcfce7;

      --warning:#f59e0b;
      --warning-soft:#fff4d6;

      --danger:#ef4444;
      --danger-soft:#fee2e2;

      --shadow-sm:0 8px 20px rgba(15,23,42,.07);
      --shadow-md:0 14px 34px rgba(15,23,42,.10);
      --shadow-lg:0 22px 50px rgba(15,23,42,.14);

      --radius:16px;
      --radius-sm:12px;
      --radius-xs:10px;

      --topbar-h:58px;
      --sidebar-w:320px;
      --fade-h:16px;
    }

    *{ box-sizing:border-box; }
    html,body{ height:100%; }

    body.app{
      margin:0;
      font-family:"Quicksand", system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Arial, sans-serif;
      background:
        radial-gradient(circle at top left, rgba(47,109,246,.06), transparent 28%),
        radial-gradient(circle at top right, rgba(255,92,168,.05), transparent 24%),
        var(--bg);
      color:var(--text);
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
    }

    body.lock-scroll{
      overflow:hidden;
      touch-action:none;
    }

    *:focus-visible{
      outline:2px solid rgba(47,109,246,.24);
      outline-offset:2px;
    }

    .avatar,
    .avatar.avatar--sm{
      position:relative;
      overflow:hidden;
      border-radius:50%;
      flex-shrink:0;
    }

    .avatar img,
    .avatar.avatar--sm img{
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
    }

    .avatar img + span,
    .avatar.avatar--sm img + span{
      display:none !important;
    }

    .avatar-link{
      display:inline-flex;
      border-radius:50%;
      text-decoration:none;
      line-height:0;
      position:relative;
    }

    .shell{
      min-height:100vh;
      display:flex;
      flex-direction:column;
      transition:filter .28s ease;
      background:transparent;
    }

    .shell.dimmed{
      filter: blur(1.5px) saturate(.96);
    }

    .sidebar{
      position:fixed;
      left:0;
      top:0;
      bottom:0;
      width:var(--sidebar-w);
      max-width:calc(100vw - 14px);
      background:linear-gradient(180deg, rgba(255,255,255,.97), rgba(246,249,255,.98));
      backdrop-filter:blur(14px);
      -webkit-backdrop-filter:blur(14px);
      border-right:1px solid rgba(202,215,235,.72);
      transform:translateX(-105%);
      transition:transform .42s cubic-bezier(.16,1,.3,1);
      z-index:70;
      box-shadow:var(--shadow-lg);
      will-change:transform;
      display:flex;
      flex-direction:column;
      overflow:hidden;
    }

    .sidebar::before{
      content:"";
      position:absolute;
      top:-110px;
      right:-80px;
      width:180px;
      height:180px;
      background:radial-gradient(circle, rgba(47,109,246,.14), transparent 70%);
      pointer-events:none;
    }

    .sidebar::after{
      content:"";
      position:absolute;
      bottom:-100px;
      left:-80px;
      width:180px;
      height:180px;
      background:radial-gradient(circle, rgba(255,92,168,.08), transparent 72%);
      pointer-events:none;
    }

    .sidebar.is-open{
      transform:translateX(0);
    }

    .backdrop{
      position:fixed;
      inset:0;
      background:rgba(12,18,31,.40);
      backdrop-filter:blur(2px);
      -webkit-backdrop-filter:blur(2px);
      opacity:0;
      pointer-events:none;
      transition:opacity .28s ease;
      z-index:60;
    }

    .backdrop.is-show{
      opacity:1;
      pointer-events:auto;
    }

    .sidebar__head{
      position:relative;
      z-index:1;
      display:flex;
      align-items:flex-start;
      gap:12px;
      padding:14px 14px 12px;
      border-bottom:1px solid rgba(202,215,235,.65);
      background:linear-gradient(180deg, rgba(255,255,255,.88), rgba(247,250,255,.94));
    }

    .sidebar__close{
      margin-left:auto;
      background:rgba(15,23,42,.03);
      border:1px solid rgba(202,215,235,.78);
      cursor:pointer;
      color:#475569;
      width:36px;
      height:36px;
      border-radius:12px;
      display:grid;
      place-items:center;
      transition:background .2s ease, transform .12s ease, color .2s ease, box-shadow .2s ease;
      box-shadow:0 4px 10px rgba(15,23,42,.04);
      flex-shrink:0;
    }

    .sidebar__close:hover{
      background:rgba(47,109,246,.10);
      color:var(--primary);
      box-shadow:0 10px 20px rgba(47,109,246,.10);
    }

    .sidebar__close:active{
      transform:scale(.97);
    }

    .user{
      display:flex;
      gap:12px;
      align-items:center;
      min-width:0;
      flex:1;
    }

    .avatar{
      width:46px;
      height:46px;
      border-radius:15px;
      background:linear-gradient(135deg, #1e3a8a, #2f6df6 55%, #7aa6ff);
      color:#fff;
      display:grid;
      place-items:center;
      font-weight:700;
      box-shadow:0 10px 22px rgba(47,109,246,.18);
      border:2px solid rgba(255,255,255,.85);
    }

    .avatar--sm{
      width:36px;
      height:36px;
      border-radius:50%;
      background:linear-gradient(135deg, #1e3a8a, #2f6df6 55%, #7aa6ff);
      color:#fff;
      display:grid;
      place-items:center;
      font-weight:700;
      border:2px solid rgba(255,255,255,.92);
      box-shadow:0 8px 16px rgba(47,109,246,.13);
    }

    .user__meta{
      line-height:1.08;
      min-width:0;
    }

    .user__name{
      font-size:.95rem;
      font-weight:700;
      color:#1e293b;
      word-break:break-word;
    }

    .user__mail{
      color:#667085;
      font-size:.85rem;
      margin-top:4px;
      word-break:break-word;
    }

    .user__roles{
      margin-top:8px;
      display:flex;
      gap:6px;
      flex-wrap:wrap;
    }

    .chip{
      padding:4px 8px;
      border-radius:999px;
      background:rgba(47,109,246,.08);
      color:#1f4db8;
      font-size:.68rem;
      font-weight:700;
      border:1px solid rgba(47,109,246,.13);
      transition:transform .12s ease, box-shadow .18s ease;
    }

    .chip:hover{
      transform:translateY(-1px);
      box-shadow:0 8px 14px rgba(47,109,246,.08);
    }

    .side-nav{
      position:relative;
      z-index:1;
      display:flex;
      flex-direction:column;
      gap:4px;
      padding:10px 10px 12px;
      overflow:auto;
      overscroll-behavior:contain;
      scrollbar-width:none;
      -ms-overflow-style:none;
      flex:1;
      -webkit-mask-image: linear-gradient(to bottom, transparent 0, #000 var(--fade-h), #000 calc(100% - var(--fade-h)), transparent 100%);
              mask-image: linear-gradient(to bottom, transparent 0, #000 var(--fade-h), #000 calc(100% - var(--fade-h)), transparent 100%);
    }

    .side-nav::-webkit-scrollbar{
      display:none;
    }

    .nav__link,
    .nav__sublink,
    .side-nav .nav__group > summary{
      display:flex;
      align-items:center;
      gap:10px;
      width:100%;
      padding:10px 11px;
      border-radius:14px;
      color:#1f2937;
      text-decoration:none;
      transition:
        background .18s ease,
        color .18s ease,
        transform .12s ease,
        box-shadow .18s ease,
        border-color .18s ease;
      border:1px solid transparent;
      position:relative;
      min-height:44px;
    }

    .nav__link svg,
    .nav__sublink svg,
    .side-nav .nav__group > summary svg{
      flex-shrink:0;
    }

    .nav__link > span,
    .nav__sublink > span,
    .side-nav .nav__group > summary > span{
      font-size:.97rem;
      font-weight:600;
      letter-spacing:.01em;
    }

    .nav__link:hover,
    .nav__sublink:hover,
    .side-nav .nav__group > summary:hover{
      background:linear-gradient(180deg, #ffffff, #f3f7ff);
      color:#0f172a;
      transform:translateX(2px);
      border-color:rgba(47,109,246,.12);
      box-shadow:0 10px 20px rgba(15,23,42,.05);
    }

    .nav__link.is-active,
    .nav__sublink.is-active,
    .side-nav .nav__group > summary.is-active{
      background:linear-gradient(180deg, #f1f6ff, #e8f0ff);
      color:#1447b8;
      border-color:rgba(47,109,246,.18);
      box-shadow:0 10px 20px rgba(47,109,246,.08);
    }

    .nav__link.is-active::before,
    .nav__sublink.is-active::before,
    .side-nav .nav__group > summary.is-active::before{
      content:"";
      position:absolute;
      left:7px;
      top:8px;
      bottom:8px;
      width:3px;
      border-radius:999px;
      background:linear-gradient(180deg, var(--primary), #7ea6ff);
    }

    .side-nav .nav__group{
      margin:0;
    }

    .side-nav .nav__group > summary{
      list-style:none;
      cursor:pointer;
      user-select:none;
    }

    .side-nav .nav__group > summary::-webkit-details-marker{
      display:none;
    }

    .side-nav .nav__group[open] > summary{
      background:linear-gradient(180deg, #f7faff, #eef4ff);
      border-color:rgba(47,109,246,.12);
    }

    .side-nav .nav__group[open]{
      background:rgba(255,255,255,.45);
      border-radius:15px;
    }

    .nav__chev{
      margin-left:auto;
      transition:transform .22s ease, opacity .18s ease;
      opacity:.72;
    }

    .side-nav .nav__group[open] .nav__chev{
      transform:rotate(90deg);
      opacity:1;
    }

    .nav__submenu{
      display:flex;
      flex-direction:column;
      gap:4px;
      padding:4px 0 5px 12px;
      margin-left:14px;
      border-left:1px dashed rgba(47,109,246,.18);
    }

    .nav__submenu .nav__sublink{
      min-height:40px;
      border-radius:12px;
      padding:9px 10px;
    }

    .logout{
      position:relative;
      z-index:1;
      padding:12px 10px 12px;
      border-top:1px solid rgba(202,215,235,.65);
      background:linear-gradient(180deg, rgba(255,255,255,.84), rgba(251,252,255,.95));
    }

    .btn-logout{
      width:100%;
      display:flex;
      align-items:center;
      gap:10px;
      padding:12px 13px;
      border-radius:17px;
      background:linear-gradient(180deg, #fff1f3, #ffe6eb);
      color:#b42341;
      border:1px solid rgba(230,96,126,.20);
      cursor:pointer;
      font-weight:700;
      font-size:.96rem;
      box-shadow:0 8px 20px rgba(180,35,65,.08);
      transition:transform .10s ease, box-shadow .18s ease, filter .18s ease;
    }

    .btn-logout:hover{
      filter:brightness(1.01);
      box-shadow:0 12px 24px rgba(180,35,65,.10);
      transform:translateY(-1px);
    }

    .btn-logout:active{
      transform:scale(.99);
    }

    .topbar{
      position:sticky;
      top:0;
      z-index:40;
      display:flex;
      align-items:center;
      gap:10px;
      padding:8px 14px;
      background:rgba(244,247,255,.88);
      backdrop-filter:blur(12px);
      -webkit-backdrop-filter:blur(12px);
      border-bottom:1px solid rgba(202,215,235,.72);
      min-height:var(--topbar-h);
    }

    .topbar::after{
      content:"";
      position:absolute;
      left:0;
      right:0;
      bottom:0;
      height:1px;
      background:linear-gradient(90deg, transparent, rgba(47,109,246,.14), transparent);
      pointer-events:none;
    }

    .icon-btn{
      background:rgba(255,255,255,.74);
      border:1px solid rgba(202,215,235,.78);
      cursor:pointer;
      color:#243041;
      width:38px;
      height:38px;
      border-radius:13px;
      display:grid;
      place-items:center;
      transition:
        background .18s ease,
        transform .08s ease,
        color .18s ease,
        box-shadow .18s ease,
        border-color .18s ease;
      box-shadow:0 6px 16px rgba(15,23,42,.05);
      backdrop-filter:blur(8px);
      -webkit-backdrop-filter:blur(8px);
      flex-shrink:0;
    }

    .icon-btn:hover{
      background:#fff;
      color:var(--primary);
      border-color:rgba(47,109,246,.16);
      box-shadow:0 10px 20px rgba(47,109,246,.10);
      transform:translateY(-1px);
    }

    .icon-btn:active{
      transform:scale(.98);
    }

    .topbar__title{
      font-size:1.05rem;
      font-weight:700;
      color:#111827;
      letter-spacing:.01em;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }

    .topbar__right{
      margin-left:auto;
      display:flex;
      align-items:center;
      gap:10px;
      min-width:0;
    }

    .notif{
      position:relative;
    }

    .dot{
      position:absolute;
      top:-4px;
      right:-4px;
      min-width:24px;
      height:24px;
      padding:0 5px;
      background:linear-gradient(180deg, #101828, #020617);
      color:#fff;
      border-radius:999px;
      box-shadow:
        0 0 0 3px rgba(245,247,252,.95),
        0 8px 16px rgba(2,6,23,.16);
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:.68rem;
      font-weight:700;
      letter-spacing:.01em;
      animation: dotPulse 2.2s infinite cubic-bezier(.66,0,0,1);
    }

    @keyframes dotPulse{
      0%,100%{ transform:scale(1); opacity:1; }
      50%{ transform:scale(1.08); opacity:.88; }
    }

    .notif__panel{
      position:absolute;
      right:0;
      top:46px;
      width:360px;
      max-width:min(92vw, 360px);
      background:rgba(255,255,255,.98);
      border:1px solid rgba(202,215,235,.8);
      border-radius:20px;
      box-shadow:0 22px 46px rgba(15,23,42,.16);
      opacity:0;
      transform:translateY(-8px) scale(.985);
      pointer-events:none;
      transition:opacity .18s ease, transform .22s cubic-bezier(.22,1,.36,1);
      overflow:hidden;
      z-index:85;
      backdrop-filter:blur(14px);
      -webkit-backdrop-filter:blur(14px);
    }

    .notif__panel.is-open{
      opacity:1;
      transform:translateY(0) scale(1);
      pointer-events:auto;
    }

    .notif__head{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:10px;
      padding:14px 14px 12px;
      border-bottom:1px solid rgba(219,228,242,.92);
      background:linear-gradient(180deg, #ffffff, #f6f9ff);
    }

    .notif__head-main{
      min-width:0;
    }

    .notif__title{
      display:flex;
      align-items:center;
      gap:8px;
      font-size:1rem;
      font-weight:700;
      color:#111827;
      line-height:1.1;
    }

    .notif__subtitle{
      margin-top:4px;
      color:#667085;
      font-size:.84rem;
      font-weight:600;
    }

    .notif__markall{
      background:none;
      border:none;
      padding:0;
      margin:0;
      color:var(--primary);
      font-weight:700;
      font-size:.84rem;
      cursor:pointer;
      white-space:nowrap;
      transition:opacity .18s ease, transform .12s ease;
    }

    .notif__markall:hover{
      opacity:.86;
      transform:translateY(-1px);
    }

    .notif__list{
      max-height:380px;
      overflow:auto;
      overscroll-behavior:contain;
      padding:8px;
      background:linear-gradient(180deg, #fbfcff, #f7f9ff);
    }

    .notif__list::-webkit-scrollbar{
      width:7px;
    }

    .notif__list::-webkit-scrollbar-thumb{
      background:rgba(148,163,184,.38);
      border-radius:999px;
    }

    .notif__empty{
      padding:18px 14px;
      font-size:.9rem;
      color:var(--muted);
      text-align:center;
    }

    .notif__item{
      position:relative;
      display:grid;
      grid-template-columns:42px 1fr;
      gap:10px;
      padding:11px 11px;
      border:1px solid rgba(219,228,242,.96);
      border-radius:16px;
      background:linear-gradient(180deg, #ffffff, #fbfdff);
      margin-bottom:8px;
      transition:transform .14s ease, box-shadow .18s ease, border-color .18s ease;
      cursor:pointer;
      padding-right:38px;
    }

    .notif__item:last-child{
      margin-bottom:0;
    }

    .notif__item:hover{
      transform:translateY(-1px);
      border-color:rgba(47,109,246,.16);
      box-shadow:0 12px 22px rgba(15,23,42,.07);
    }

    .notif__item.is-unread{
      background:linear-gradient(180deg, #ffffff, #f4f8ff);
      border-color:rgba(47,109,246,.14);
    }

    .notif__item.is-read{
      opacity:.92;
    }

    .notif__icon{
      width:34px;
      height:34px;
      border-radius:11px;
      display:grid;
      place-items:center;
      background:linear-gradient(180deg, #eef2ff, #e8efff);
      color:#1e3a8a;
      border:1px solid rgba(47,109,246,.10);
      box-shadow:inset 0 1px 0 rgba(255,255,255,.65);
      margin-top:1px;
    }

    .notif__item.warn .notif__icon{
      background:linear-gradient(180deg, #fff8e8, #fff2cf);
      color:#9a6700;
      border-color:rgba(245,158,11,.14);
    }

    .notif__item.error .notif__icon{
      background:linear-gradient(180deg, #fff0f0, #ffe1e1);
      color:#b42318;
      border-color:rgba(239,68,68,.14);
    }

    .notif__content{
      min-width:0;
    }

    .notif__text{
      color:#111827;
      font-size:.92rem;
      font-weight:700;
      line-height:1.22;
      margin-bottom:3px;
      word-break:break-word;
      padding-right:4px;
    }

    .notif__msg{
      color:#6b7280;
      font-size:.84rem;
      line-height:1.28;
      word-break:break-word;
    }

    .notif__meta{
      display:flex;
      align-items:center;
      flex-wrap:wrap;
      gap:7px;
      margin-top:8px;
    }

    .notif__time{
      color:#7c8798;
      font-size:.8rem;
      font-weight:700;
    }

    .notif__sep{
      color:#b2bccb;
      font-size:.8rem;
    }

    .pill{
      padding:4px 8px;
      border-radius:999px;
      font-size:.66rem;
      font-weight:700;
      align-self:flex-start;
      border:1px solid transparent;
      line-height:1;
    }

    .pill--info{
      background:#eaf1ff;
      color:#2454c7;
      border-color:rgba(47,109,246,.12);
    }

    .pill--warn{
      background:#fff3d4;
      color:#956100;
      border-color:rgba(245,158,11,.16);
    }

    .pill--error{
      background:#fee5e5;
      color:#b42318;
      border-color:rgba(239,68,68,.14);
    }

    .notif__item-close{
      position:absolute;
      top:10px;
      right:10px;
      width:23px;
      height:23px;
      border-radius:999px;
      border:none;
      background:transparent;
      cursor:pointer;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:14px;
      color:#94a3b8;
      transition:background .16s ease,color .16s ease,transform .08s ease;
    }

    .notif__item-close:hover{
      background:rgba(47,109,246,.10);
      color:var(--primary);
    }

    .notif__item-close:active{
      transform:scale(.94);
    }

    .notif__footer{
      padding:8px 10px 10px;
      border-top:1px solid rgba(219,228,242,.92);
      background:linear-gradient(180deg, #ffffff, #f9fbff);
    }

    .notif__link{
      display:flex;
      align-items:center;
      justify-content:center;
      gap:8px;
      width:100%;
      padding:10px 12px;
      text-decoration:none;
      color:#183ea6;
      border-radius:14px;
      border:1px solid rgba(47,109,246,.12);
      font-weight:700;
      font-size:.88rem;
      text-align:center;
      background:linear-gradient(180deg, #f4f8ff, #edf3ff);
      cursor:pointer;
      transition:transform .12s ease, box-shadow .18s ease, filter .18s ease;
    }

    .notif__link:hover{
      transform:translateY(-1px);
      box-shadow:0 10px 18px rgba(47,109,246,.08);
      filter:brightness(1.01);
    }

    .content{
      padding:18px;
      min-height:calc(100vh - var(--topbar-h));
      background:transparent;
    }

    .no-anim *{
      transition:none !important;
      animation:none !important;
    }

    @media (max-width: 991.98px){
      :root{
        --topbar-h:56px;
        --sidebar-w:300px;
      }

      .topbar{
        padding:8px 12px;
      }

      .topbar__title{
        font-size:1rem;
      }

      .content{
        padding:15px;
      }

      .notif__panel{
        width:340px;
      }
    }

    @media (max-width: 767.98px){
      :root{
        --topbar-h:54px;
        --sidebar-w:88vw;
      }

      .sidebar{
        max-width:88vw;
      }

      .sidebar__head{
        padding:12px 12px 11px;
      }

      .avatar{
        width:42px;
        height:42px;
        border-radius:14px;
      }

      .user__name{
        font-size:.92rem;
      }

      .user__mail{
        font-size:.81rem;
      }

      .topbar{
        gap:8px;
        padding:8px 10px;
      }

      .icon-btn{
        width:36px;
        height:36px;
        border-radius:12px;
      }

      .topbar__title{
        font-size:.96rem;
      }

      .topbar__right{
        gap:8px;
      }

      .avatar--sm{
        width:34px;
        height:34px;
      }

      .dot{
        min-width:22px;
        height:22px;
        font-size:.64rem;
      }

      .notif__panel{
        position:fixed;
        left:8px;
        right:8px;
        top:62px;
        width:auto;
        max-width:none;
        border-radius:18px;
      }

      .notif__head{
        padding:12px 12px 10px;
      }

      .notif__title{
        font-size:.95rem;
      }

      .notif__subtitle{
        font-size:.8rem;
      }

      .notif__list{
        max-height:min(58vh, 450px);
        padding:8px;
      }

      .notif__item{
        grid-template-columns:38px 1fr;
        gap:9px;
        padding:10px;
        padding-right:34px;
        border-radius:15px;
      }

      .notif__icon{
        width:32px;
        height:32px;
        border-radius:10px;
      }

      .notif__text{
        font-size:.88rem;
      }

      .notif__msg{
        font-size:.8rem;
      }

      .notif__time{
        font-size:.77rem;
      }

      .nav__link > span,
      .nav__sublink > span,
      .side-nav .nav__group > summary > span{
        font-size:.93rem;
      }

      .btn-logout{
        border-radius:16px;
        padding:11px 12px;
      }

      .content{
        padding:12px;
      }
    }

    @media (max-width: 420px){
      .topbar__title{
        max-width:130px;
      }

      .notif__panel{
        left:6px;
        right:6px;
        top:60px;
      }

      .sidebar__close{
        width:34px;
        height:34px;
      }
    }
  </style>
</head>

<body class="app">
  <aside id="sidebar" class="sidebar" aria-hidden="true" aria-label="Menú lateral">
    <div class="sidebar__head">
      <div class="user">
        @php
          $u  = auth()->user();
          $nm = $u?->name ?? 'Usuario';
          $ini = mb_strtoupper(mb_substr($nm,0,1));

          $isAdmin   = $u && method_exists($u,'hasRole') ? $u->hasRole('admin') : false;
          $isManager = $u && method_exists($u,'hasRole') ? $u->hasRole('manager') : false;
          $restrictManager = $isManager && !$isAdmin;

          $baseAvatar = null;
          if ($u && !empty($u->avatar_url)) { $baseAvatar = $u->avatar_url; }
          if (!$baseAvatar && $u && !empty($u->email)) {
              $hash = md5(strtolower(trim($u->email)));
              $baseAvatar = "https://www.gravatar.com/avatar/{$hash}?s=300&d=mp";
          }
          $ver = null;
          if ($u && !empty($u->avatar_updated_at)) {
              $ver = $u->avatar_updated_at instanceof \Illuminate\Support\Carbon ? $u->avatar_updated_at->timestamp : strtotime($u->avatar_updated_at);
          } elseif ($u && !empty($u->updated_at)) {
              $ver = $u->updated_at instanceof \Illuminate\Support\Carbon ? $u->updated_at->timestamp : strtotime($u->updated_at);
          }
          $avatarSrc = null;
          if (!empty($baseAvatar)) {
              $sep = (strpos($baseAvatar, '?') !== false) ? '&' : '?';
              $avatarSrc = $baseAvatar . ($ver ? ($sep.'v='.$ver) : '');
          }
          $fallbackMp = ($u && !empty($u->email))
              ? "https://www.gravatar.com/avatar/".md5(strtolower(trim($u->email)))."?s=300&d=mp"
              : "https://www.gravatar.com/avatar/?s=300&d=mp";

          if (\Illuminate\Support\Facades\Route::has('profile.show')) {
              $profileHref = route('profile.show');
          } elseif (\Illuminate\Support\Facades\Route::has('profile')) {
              $profileHref = route('profile');
          } else {
              $profileHref = url('/panel/perfil');
          }

          if (\Illuminate\Support\Facades\Route::has('notifications.feed')) {
              $notifFeedUrl = route('notifications.feed');
          } else {
              $notifFeedUrl = url('/notifications/feed');
          }
          if (\Illuminate\Support\Facades\Route::has('notifications.read-all')) {
              $notifReadAllUrl = route('notifications.read-all');
          } else {
              $notifReadAllUrl = url('/notifications/read-all');
          }

          if (\Illuminate\Support\Facades\Route::has('notifications.read-one')) {
              $notifReadOneUrl = route('notifications.read-one', ['notification' => '__ID__']);
          } else {
              $notifReadOneUrl = url('/notifications/__ID__/read');
          }
        @endphp

        <a href="{{ $profileHref }}" class="avatar-link" title="Ver mi perfil">
          <div class="avatar" aria-hidden="true">
            @if($avatarSrc)
              <img src="{{ $avatarSrc }}" alt="Avatar de {{ $nm }}" onerror="this.onerror=null;this.src='{{ $fallbackMp }}';">
              <span>{{ $ini }}</span>
            @else
              <img src="{{ $fallbackMp }}" alt="Avatar de {{ $nm }}">
              <span>{{ $ini }}</span>
            @endif
          </div>
        </a>

        <div class="user__meta">
          <div class="user__name">{{ $nm }}</div>
          <div class="user__mail">{{ $u?->email ?? 'correo@dominio.com' }}</div>

          @if($u && method_exists($u,'getRoleNames'))
            <div class="user__roles">
              @foreach($u->getRoleNames() as $r)
                <span class="chip">{{ $r }}</span>
              @endforeach
            </div>
          @endif
        </div>
      </div>

      <button class="sidebar__close" id="btnCloseSidebar" aria-label="Cerrar menú">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6L6 18M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <nav class="side-nav" id="sidebarNav">

      @if($restrictManager)

        <details class="nav__group" {{ request()->routeIs('profile.*') ? 'open' : '' }}>
          <summary class="{{ request()->routeIs('profile.*') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
              <circle cx="12" cy="8" r="4"></circle>
              <path d="M5 20a7 7 0 0 1 14 0"></path>
            </svg>
            <span>Mi perfil</span>
            <svg class="nav__chev" viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="2">
              <path d="M9 6l6 6-6 6"/>
            </svg>
          </summary>
          <div class="nav__submenu">
            <a href="{{ route('profile.show') }}" class="nav__sublink {{ request()->routeIs('profile.show') ? 'is-active':'' }}">
              <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" fill="none" stroke-width="1.9">
                <path d="M4 7h16"></path>
                <path d="M4 12h11"></path>
                <path d="M4 17h8"></path>
              </svg>
              <span>Ver perfil</span>
            </a>
          </div>
        </details>

        <a href="{{ route('partcontable.index') }}" class="nav__link {{ request()->routeIs('partcontable.*') ? 'is-active':'' }}">
          <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
            <path d="M4 19h16"></path>
            <path d="M7 16V8"></path>
            <path d="M12 16V5"></path>
            <path d="M17 16v-4"></path>
          </svg>
          <span>Part. contable</span>
        </a>

        <a href="{{ route('alta.docs.index') }}" class="nav__link {{ request()->routeIs('alta.docs.*') ? 'is-active':'' }}">
          <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
            <path d="M8 3h6l5 5v11a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"></path>
            <path d="M14 3v5h5"></path>
            <path d="M9 13h6"></path>
            <path d="M9 17h4"></path>
          </svg>
          <span>Documentación de altas</span>
        </a>

      @else

      <a href="{{ route('dashboard') }}" class="nav__link {{ request()->routeIs('dashboard') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <path d="M4 13.5 12 5l8 8.5"></path>
          <path d="M7 11.5V20h10v-8.5"></path>
        </svg>
        <span>Dashboard</span>
      </a>

      <a href="{{ route('profile.show') }}" class="nav__link {{ request()->routeIs('profile.show') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <circle cx="12" cy="8" r="4"></circle>
          <path d="M5 20a7 7 0 0 1 14 0"></path>
        </svg>
        <span>Mi Perfil</span>
      </a>

      <a href="{{ route('products.index') }}" class="nav__link {{ request()->routeIs('products.index') || request()->routeIs('products.show') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <path d="M12 3 4 7l8 4 8-4-8-4Z"></path>
          <path d="M4 7v10l8 4 8-4V7"></path>
          <path d="M12 11v10"></path>
        </svg>
        <span>Catálogo</span>
      </a>

      <a href="{{ route('providers.index') }}" class="nav__link {{ request()->routeIs('providers.*') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <path d="M3 10h18"></path>
          <path d="M5 10V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v4"></path>
          <path d="M6 10v8a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-8"></path>
          <path d="M10 14h4"></path>
        </svg>
        <span>Proveedores</span>
      </a>

      <a href="{{ route('clients.index') }}" class="nav__link {{ request()->routeIs('clients.*') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <circle cx="9" cy="8" r="3.5"></circle>
          <path d="M3.5 19a5.5 5.5 0 0 1 11 0"></path>
          <path d="M16 8a3 3 0 0 1 0 6"></path>
          <path d="M18.5 19a5 5 0 0 0-2.5-4.33"></path>
        </svg>
        <span>Clientes</span>
      </a>

      <details class="nav__group" {{ request()->routeIs('cotizaciones.*') ? 'open' : '' }}>
        <summary class="{{ request()->routeIs('cotizaciones.*') ? 'is-active':'' }}">
          <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
            <path d="M7 3h10l4 4v14H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"></path>
            <path d="M17 3v4h4"></path>
            <path d="M9 11h8"></path>
            <path d="M9 15h8"></path>
          </svg>
          <span>Cotizaciones</span>
          <svg class="nav__chev" viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="2">
            <path d="M9 6l6 6-6 6"/>
          </svg>
        </summary>
        <div class="nav__submenu">
          <a href="{{ route('cotizaciones.index') }}" class="nav__sublink {{ request()->routeIs('cotizaciones.index') || request()->routeIs('cotizaciones.show') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" fill="none" stroke-width="1.9">
              <path d="M5 7h14"></path>
              <path d="M5 12h14"></path>
              <path d="M5 17h9"></path>
            </svg>
            <span>Listado</span>
          </a>
          <a href="{{ route('cotizaciones.create') }}" class="nav__sublink {{ request()->routeIs('cotizaciones.create') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" fill="none" stroke-width="1.9">
              <path d="M12 5v14"></path>
              <path d="M5 12h14"></path>
            </svg>
            <span>Nueva</span>
          </a>
          <a href="{{ route('cotizaciones.auto.form') }}" class="nav__sublink {{ request()->routeIs('cotizaciones.auto.*') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" fill="none" stroke-width="1.9">
              <path d="M7 7h8a4 4 0 0 1 0 8H9"></path>
              <path d="m7 11-3 3 3 3"></path>
            </svg>
            <span>Auto (asistida)</span>
          </a>
        </div>
      </details>

      <a href="{{ route('ventas.index') }}" class="nav__link {{ request()->routeIs('ventas.*') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <path d="M6 6h15l-2 8H8L6 4H3"></path>
          <circle cx="9" cy="19" r="1.6"></circle>
          <circle cx="18" cy="19" r="1.6"></circle>
        </svg>
        <span>Ventas</span>
      </a>

      <a href="{{ route('manual_invoices.index') }}" class="nav__link {{ request()->routeIs('manual_invoices.index') || request()->routeIs('manual_invoices.show') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <path d="M8 3h8l4 4v14H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"></path>
          <path d="M16 3v4h4"></path>
          <path d="M9 12h6"></path>
          <path d="M9 16h6"></path>
        </svg>
        <span>Facturas</span>
      </a>

      <a href="{{ route('tech-sheets.index') }}" class="nav__link {{ request()->routeIs('tech-sheets.index') || request()->routeIs('tech-sheets.show') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <rect x="5" y="3" width="14" height="18" rx="2"></rect>
          <path d="M9 8h6"></path>
          <path d="M9 12h6"></path>
          <path d="M9 16h4"></path>
        </svg>
        <span>Fichas técnicas</span>
      </a>

      <a href="{{ route('publications.index') }}" class="nav__link {{ request()->routeIs('publications.index') || request()->routeIs('publications.show') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <path d="M5 6.5A2.5 2.5 0 0 1 7.5 4H20v14H7.5A2.5 2.5 0 0 0 5 20.5z"></path>
          <path d="M5 6v14"></path>
          <path d="M9 8h7"></path>
          <path d="M9 12h7"></path>
        </svg>
        <span>Compras y Ventas</span>
      </a>

      <a href="{{ route('partcontable.index') }}" class="nav__link {{ request()->routeIs('partcontable.*') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <path d="M4 19h16"></path>
          <path d="M7 16V8"></path>
          <path d="M12 16V5"></path>
          <path d="M17 16v-4"></path>
        </svg>
        <span>Part. contable</span>
      </a>

      <a href="{{ url('/confidential/vault/6') }}" class="nav__link">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <path d="M4 7a2 2 0 0 1 2-2h3l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7z"></path>
          <path d="M8 13h8"></path>
        </svg>
        <span>Documentación</span>
      </a>

      <a href="{{ route('expenses.index') }}" class="nav__link {{ request()->routeIs('expenses.*') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <path d="M6 4h10"></path>
          <path d="M5 8h14"></path>
          <path d="M7 12h10"></path>
          <path d="M9 16h6"></path>
          <path d="M17 4v12"></path>
        </svg>
        <span>Gastos</span>
      </a>

      <a href="{{ route('vehicles.index') }}" class="nav__link {{ request()->routeIs('vehicles.*') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <path d="M5 15l2-5h10l2 5"></path>
          <path d="M4 15h16v3a2 2 0 0 1-2 2h-1"></path>
          <path d="M5 20H4a2 2 0 0 1-2-2v-3"></path>
          <circle cx="7" cy="18" r="2"></circle>
          <circle cx="17" cy="18" r="2"></circle>
        </svg>
        <span>Vehículos</span>
      </a>

      <a href="{{ route('alta.docs.index') }}" class="nav__link {{ request()->routeIs('alta.docs.*') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <path d="M8 3h6l5 5v11a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"></path>
          <path d="M14 3v5h5"></path>
          <path d="M9 13h6"></path>
          <path d="M9 17h4"></path>
        </svg>
        <span>Documentación de altas</span>
      </a>

      <details class="nav__group"
        {{ request()->routeIs('licitaciones.*')
            || request()->routeIs('licitaciones-ai.*')
            || request()->routeIs('admin.licitacion-pdfs.*')
            || request()->routeIs('admin.licitacion-propuestas.*')
            ? 'open' : '' }}>
        <summary class="{{ request()->routeIs('licitaciones.*')
                          || request()->routeIs('licitaciones-ai.*')
                          || request()->routeIs('admin.licitacion-pdfs.*')
                          || request()->routeIs('admin.licitacion-propuestas.*')
                          ? 'is-active':'' }}">
          <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
            <path d="M8 3h8l4 4v14H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"></path>
            <path d="M16 3v4h4"></path>
            <path d="M9 11h7"></path>
            <path d="M9 15h5"></path>
          </svg>
          <span>Licitaciones</span>
          <svg class="nav__chev" viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="2">
            <path d="M9 6l6 6-6 6"/>
          </svg>
        </summary>

        <div class="nav__submenu">
          <a href="{{ route('licitaciones.index') }}"
             class="nav__sublink {{ request()->routeIs('licitaciones.index') || request()->routeIs('licitaciones.show') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" fill="none" stroke-width="1.9">
              <path d="M5 7h14"></path>
              <path d="M5 12h14"></path>
              <path d="M5 17h9"></path>
            </svg>
            <span>Listado</span>
          </a>

          <a href="{{ route('licitaciones.create.step1') }}"
             class="nav__sublink {{ request()->routeIs('licitaciones.create.step1') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" fill="none" stroke-width="1.9">
              <path d="M12 5v14"></path>
              <path d="M5 12h14"></path>
            </svg>
            <span>Nueva licitación</span>
          </a>

          <a href="{{ route('licitaciones-ai.index') }}"
             class="nav__sublink {{ request()->routeIs('licitaciones-ai.index') || request()->routeIs('licitaciones-ai.show') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" fill="none" stroke-width="1.9">
              <rect x="4" y="4" width="16" height="10" rx="2"></rect>
              <path d="M9 18h6"></path>
              <path d="M12 14v4"></path>
            </svg>
            <span>Licitaciones IA</span>
          </a>

          <a href="{{ route('licitaciones-ai.tabla-global') }}"
             class="nav__sublink {{ request()->routeIs('licitaciones-ai.tabla-global') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" fill="none" stroke-width="1.9">
              <rect x="3" y="4" width="18" height="16" rx="2"></rect>
              <path d="M9 4v16"></path>
              <path d="M15 4v16"></path>
              <path d="M3 10h18"></path>
              <path d="M3 16h18"></path>
            </svg>
            <span>Tabla global IA</span>
          </a>

          <a href="{{ route('admin.licitacion-pdfs.index') }}"
             class="nav__sublink {{ request()->routeIs('admin.licitacion-pdfs.*') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" fill="none" stroke-width="1.9">
              <path d="M8 3h8l4 4v14H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"></path>
              <path d="M16 3v4h4"></path>
              <path d="M9 12h6"></path>
              <path d="M9 16h4"></path>
            </svg>
            <span>PDFs de requisiciones</span>
          </a>

          <a href="{{ route('admin.licitacion-propuestas.index') }}"
             class="nav__sublink {{ request()->routeIs('admin.licitacion-propuestas.*') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" fill="none" stroke-width="1.9">
              <path d="M4 19h16"></path>
              <path d="M7 16V10"></path>
              <path d="M12 16V6"></path>
              <path d="M17 16v-3"></path>
            </svg>
            <span>Propuestas / comparativas</span>
          </a>
        </div>
      </details>

      <a href="{{ route('agenda.calendar') }}" class="nav__link {{ request()->routeIs('agenda.*') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <rect x="3" y="5" width="18" height="16" rx="2"></rect>
          <path d="M8 3v4"></path>
          <path d="M16 3v4"></path>
          <path d="M3 10h18"></path>
        </svg>
        <span>Agenda</span>
      </a>

      <details class="nav__group" {{ request()->routeIs('tickets.*') ? 'open' : '' }}>
        <summary class="{{ request()->routeIs('tickets.*') ? 'is-active':'' }}">
          <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
            <path d="M8 4h8l3 3v10a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3V7l3-3z"></path>
            <path d="M9 11h6"></path>
            <path d="M9 15h4"></path>
          </svg>
          <span>Tickets</span>
          <svg class="nav__chev" viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="2">
            <path d="M9 6l6 6-6 6"/>
          </svg>
        </summary>

        <div class="nav__submenu">
          <a href="{{ route('tickets.index') }}"
             class="nav__sublink {{ request()->routeIs('tickets.index') || request()->routeIs('tickets.show') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" fill="none" stroke-width="1.9">
              <path d="M5 7h14"></path>
              <path d="M5 12h14"></path>
              <path d="M5 17h8"></path>
            </svg>
            <span>Lista de tickets</span>
          </a>
          @if(\Illuminate\Support\Facades\Route::has('tickets.my'))
            <a href="{{ route('tickets.my') }}"
               class="nav__sublink {{ request()->routeIs('tickets.my') ? 'is-active':'' }}">
              <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" fill="none" stroke-width="1.9">
                <circle cx="12" cy="8" r="3.5"></circle>
                <path d="M5 19a7 7 0 0 1 14 0"></path>
              </svg>
              <span>Mis tickets</span>
            </a>
          @endif
        </div>
      </details>

      <a href="{{ route('routes.index') }}" class="nav__link {{ request()->routeIs('routes.index') || request()->routeIs('routes.show') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <circle cx="6" cy="6" r="2"></circle>
          <circle cx="18" cy="8" r="2"></circle>
          <circle cx="9" cy="18" r="2"></circle>
          <path d="M8 7.2 16 8"></path>
          <path d="M17 9.8 10 16.2"></path>
        </svg>
        <span>Logística</span>
      </a>

      <a href="{{ route('admin.catalog.index') }}" class="nav__link {{ request()->routeIs('admin.catalog.*') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <path d="M12 3 4 7l8 4 8-4-8-4Z"></path>
          <path d="M4 7v10l8 4 8-4V7"></path>
          <path d="M12 11v10"></path>
        </svg>
        <span>Productos</span>
      </a>

      <a href="{{ route('admin.wms.home') }}" class="nav__link {{ request()->routeIs('admin.wms.home') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <path d="M4 10 12 4l8 6v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-9z"></path>
          <path d="M9 13h6"></path>
          <path d="M9 17h6"></path>
        </svg>
        <span>Almacén</span>
      </a>

      <a href="{{ route('accounting.dashboard') }}" class="nav__link {{ request()->routeIs('accounting.dashboard') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <path d="M4 19h16"></path>
          <path d="M7 16V10"></path>
          <path d="M12 16V5"></path>
          <path d="M17 16v-7"></path>
        </svg>
        <span>Contabilidad</span>
      </a>

      <a href="{{ route('admin.whatsapp.conversations') }}" class="nav__link {{ request()->routeIs('admin.whatsapp.conversations') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <path d="M12 4a8 8 0 0 0-6.9 12l-1.1 4 4.1-1A8 8 0 1 0 12 4z"></path>
          <path d="M9 10c.5 2 2.5 4 4.5 4.5"></path>
        </svg>
        <span>WhatsApp</span>
      </a>

      <details class="nav__group" {{ request()->routeIs('admin.help.*') ? 'open' : '' }}>
        <summary class="{{ request()->routeIs('admin.help.*') ? 'is-active':'' }}">
          <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M9.5 9a2.5 2.5 0 1 1 4.3 1.7C13 11.5 12 12 12 13"></path>
            <path d="M12 16h.01"></path>
          </svg>
          <span>Help Desk</span>
          <svg class="nav__chev" viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="2">
            <path d="M9 6l6 6-6 6"/>
          </svg>
        </summary>
        <div class="nav__submenu">
          <a href="{{ route('admin.help.index') }}" class="nav__sublink {{ request()->routeIs('admin.help.index') || request()->routeIs('admin.help.show') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" fill="none" stroke-width="1.9">
              <path d="M5 7h14"></path>
              <path d="M5 12h14"></path>
              <path d="M5 17h9"></path>
            </svg>
            <span>Tickets de usuarios</span>
          </a>
          <form action="{{ route('admin.help.sync') }}" method="POST" class="nav__sublink" style="padding:0; border:none; min-height:auto; background:transparent;">
            @csrf
            <button type="submit" class="nav__sublink" style="width:100%; text-align:left; background:transparent; border:none; cursor:pointer;">
              <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" fill="none" stroke-width="1.9">
                <path d="M20 11a8 8 0 1 1-2.34-5.66"></path>
                <path d="M20 4v6h-6"></path>
              </svg>
              <span>Reindexar conocimiento</span>
            </button>
          </form>
        </div>
      </details>

      <a href="{{ route('mail.index') }}" class="nav__link {{ request()->routeIs('mail.index') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <rect x="3" y="5" width="18" height="14" rx="2"></rect>
          <path d="m4 7 8 6 8-6"></path>
        </svg>
        <span>Correo</span>
      </a>

      <a href="{{ route('panel.landing.index') }}" class="nav__link {{ request()->routeIs('panel.landing.*') ? 'is-active':'' }}">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <path d="M4 12h16"></path>
          <path d="M12 4v16"></path>
          <path d="M5.5 5.5c4 3 9 3 13 0"></path>
          <path d="M5.5 18.5c4-3 9-3 13 0"></path>
        </svg>
        <span>Landing (Inicio web)</span>
      </a>

      <details class="nav__group" {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.catalog.*') || request()->routeIs('admin.orders.*') ? 'open' : '' }}>
        <summary class="{{ request()->routeIs('admin.users.*') || request()->routeIs('admin.catalog.*') || request()->routeIs('admin.orders.*') ? 'is-active':'' }}">
          <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
            <path d="M4 20v-1a5 5 0 0 1 5-5h2a5 5 0 0 1 5 5v1"></path>
            <circle cx="10" cy="8" r="3.5"></circle>
            <path d="M18 8v6"></path>
            <path d="M15 11h6"></path>
          </svg>
          <span>Administración</span>
          <svg class="nav__chev" viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="2">
            <path d="M9 6l6 6-6 6"/>
          </svg>
        </summary>
        <div class="nav__submenu">
          <a href="{{ route('admin.users.index') }}" class="nav__sublink {{ request()->routeIs('admin.users.*') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" fill="none" stroke-width="1.9">
              <circle cx="9" cy="8" r="3.5"></circle>
              <path d="M4 19a5 5 0 0 1 10 0"></path>
              <path d="M17 8v6"></path>
              <path d="M14 11h6"></path>
            </svg>
            <span>Usuarios</span>
          </a>

          <a href="{{ route('admin.orders.index') }}" class="nav__sublink {{ request()->routeIs('admin.orders.*') ? 'is-active':'' }}">
            <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" fill="none" stroke-width="1.9">
              <path d="M6 6h15l-2 8H8L6 4H3"></path>
              <circle cx="9" cy="19" r="1.6"></circle>
              <circle cx="18" cy="19" r="1.6"></circle>
            </svg>
            <span>Pedidos web</span>
          </a>
        </div>
      </details>

      @endif
    </nav>

    <form method="POST" action="{{ route('logout') }}" class="logout">
      @csrf
      <button type="submit" class="btn-logout">
        <svg viewBox="0 0 24 24" width="19" height="19" stroke="currentColor" fill="none" stroke-width="1.9">
          <path d="M10 21H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
          <path d="M15 16l5-4-5-4"></path>
          <path d="M20 12H9"></path>
        </svg>
        <span>Cerrar sesión</span>
      </button>
    </form>
  </aside>

  <div id="backdrop" class="backdrop" tabindex="-1" aria-hidden="true"></div>

  <div class="shell" id="shell">
    <header class="topbar">
      <button id="btnSidebar" class="icon-btn" aria-label="Abrir menú">
        <svg viewBox="0 0 24 24" width="21" height="21" stroke="currentColor" fill="none" stroke-width="2">
          <path d="M4 7h16"></path>
          <path d="M4 12h16"></path>
          <path d="M4 17h16"></path>
        </svg>
      </button>

      <div class="topbar__title">@yield('header','Panel')</div>

      <div class="topbar__right">
        <div class="notif">
          <button id="btnNotif" class="icon-btn" aria-haspopup="true" aria-expanded="false" aria-label="Notificaciones">
            <svg viewBox="0 0 24 24" width="21" height="21" stroke="currentColor" fill="none" stroke-width="2">
              <path d="M6 8a6 6 0 1 1 12 0v3.2c0 .53.21 1.04.59 1.41L20 14H4l1.41-1.39c.38-.37.59-.88.59-1.41V8"></path>
              <path d="M10 18a2 2 0 0 0 4 0"></path>
            </svg>
            <span id="notifBadge" class="dot" aria-hidden="true" style="display:none;"></span>
          </button>

          <div id="notifPanel" class="notif__panel" role="menu" aria-label="Panel de notificaciones" aria-hidden="true">
            <div class="notif__head">
              <div class="notif__head-main">
                <div class="notif__title">
                  <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2">
                    <path d="M6 8a6 6 0 1 1 12 0v3.2c0 .53.21 1.04.59 1.41L20 14H4l1.41-1.39c.38-.37.59-.88.59-1.41V8"></path>
                    <path d="M10 18a2 2 0 0 0 4 0"></path>
                  </svg>
                  <span>Notificaciones</span>
                </div>
                <div class="notif__subtitle"><span id="notifCountText">Cargando…</span></div>
              </div>

              <button type="button" id="btnMarkAllTop" class="notif__markall">Marcar leídas</button>
            </div>

            <div id="notifList" class="notif__list">
              <div class="notif__empty">Cargando…</div>
            </div>

            <div class="notif__footer">
              <button type="button" id="btnMarkAll" class="notif__link">Marcar todas como leídas</button>
            </div>
          </div>
        </div>

        <a href="{{ $profileHref }}" class="avatar-link" title="Ver mi perfil">
          <div class="avatar avatar--sm" aria-hidden="true">
            @if($avatarSrc)
              <img src="{{ $avatarSrc }}" alt="Avatar de {{ $nm }}" onerror="this.onerror=null;this.src='{{ $fallbackMp }}';">
              <span>{{ $ini }}</span>
            @else
              <img src="{{ $fallbackMp }}" alt="Avatar de {{ $nm }}">
              <span>{{ $ini }}</span>
            @endif
          </div>
        </a>
      </div>
    </header>

    <main id="content" class="content">
      @yield('content')
    </main>
  </div>

  @stack('scripts')

  <script>
    (function(){
      const shell      = document.getElementById('shell');
      const sidebar    = document.getElementById('sidebar');
      const sidebarNav = document.getElementById('sidebarNav');
      const backdrop   = document.getElementById('backdrop');
      const btnOpen    = document.getElementById('btnSidebar');
      const btnClose   = document.getElementById('btnCloseSidebar');

      const notifBtn        = document.getElementById('btnNotif');
      const notifPane       = document.getElementById('notifPanel');
      const notifList       = document.getElementById('notifList');
      const notifBadge      = document.getElementById('notifBadge');
      const notifMarkAll    = document.getElementById('btnMarkAll');
      const notifMarkAllTop = document.getElementById('btnMarkAllTop');
      const notifCountText  = document.getElementById('notifCountText');

      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

      const NOTIF_FEED_URL    = @json($notifFeedUrl);
      const NOTIF_READALL_URL = @json($notifReadAllUrl);
      const NOTIF_READONE_URL = @json($notifReadOneUrl);

      let sidebarOpen = false;
      let notifLoaded = false;
      let lastPayloadKey = null;

      const applyOverlay = () => {
        if (!backdrop || !shell || !sidebar) return;
        backdrop.classList.toggle('is-show', sidebarOpen);
        shell.classList.toggle('dimmed', sidebarOpen);
        document.body.classList.toggle('lock-scroll', sidebarOpen);
        sidebar.setAttribute('aria-hidden', sidebarOpen ? 'false' : 'true');
      };

      const openSidebar = () => {
        if (!sidebar || sidebarOpen) return;
        sidebarOpen = true;
        sidebar.classList.add('is-open');
        applyOverlay();
      };

      const closeSidebar = () => {
        if (!sidebar || !sidebarOpen) return;
        sidebarOpen = false;
        sidebar.classList.remove('is-open');
        applyOverlay();
      };

    
      if (btnOpen) btnOpen.addEventListener('click', openSidebar);
      if (btnClose) btnClose.addEventListener('click', closeSidebar);
      if (backdrop) backdrop.addEventListener('click', closeSidebar);

      if (sidebarNav){
        sidebarNav.addEventListener('click', (e) => {
          const summary = e.target.closest('summary');
          if (summary) return;

          const link = e.target.closest('a');
          if (!link) return;

          const href = link.getAttribute('href') || '';
          const keep = link.hasAttribute('data-keep-open');
          const isAnchorOnly = href.startsWith('#') || href === '' || href.startsWith('javascript');

          if (!keep && !isAnchorOnly) closeSidebar();
        });
      }

      function buildPayloadKey(payload){
        if (!payload || !Array.isArray(payload.items)) return '';
        return payload.items.map(n => n.id + (n.read_at ? '1' : '0')).join('|') + '|' + (payload.unread || 0);
      }

      function escapeHtml(value){
        return String(value ?? '')
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      }

      function getNotifIcon(level){
        if (level === 'warn'){
          return `
            <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" fill="none" stroke-width="2">
              <path d="M12 4 3 20h18L12 4z"></path>
              <path d="M12 10v4"></path>
              <path d="M12 17h.01"></path>
            </svg>
          `;
        }

        if (level === 'error'){
          return `
            <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" fill="none" stroke-width="2">
              <circle cx="12" cy="12" r="9"></circle>
              <path d="M15 9 9 15"></path>
              <path d="m9 9 6 6"></path>
            </svg>
          `;
        }

        return `
          <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" fill="none" stroke-width="2">
            <rect x="5" y="4" width="14" height="16" rx="2"></rect>
            <path d="M8 8h8"></path>
            <path d="M8 12h8"></path>
          </svg>
        `;
      }

      function updateNotifCounter(unread){
        if (notifBadge){
          if (unread > 0){
            notifBadge.style.display = '';
            notifBadge.textContent = unread > 99 ? '99+' : unread > 9 ? '9+' : unread;
          } else {
            notifBadge.style.display = 'none';
            notifBadge.textContent = '';
          }
        }

        if (notifCountText){
          if (unread > 0){
            notifCountText.textContent = `${unread} nueva${unread === 1 ? '' : 's'}`;
          } else {
            notifCountText.textContent = 'Sin notificaciones nuevas';
          }
        }
      }

      function renderNotifItems(payload){
        if (!notifList) return;

        const items = Array.isArray(payload?.items) ? payload.items : [];
        const unread = Number(payload?.unread || 0);

        updateNotifCounter(unread);

        notifList.innerHTML = '';

        if (!items.length){
          const empty = document.createElement('div');
          empty.className = 'notif__empty';
          empty.textContent = 'No tienes notificaciones.';
          notifList.appendChild(empty);
          return;
        }

        items.forEach(n => {
          const level = n.status || 'info';

          let pillClass = 'pill--info';
          let pillText  = 'Nueva';

          if (level === 'warn'){
            pillClass = 'pill--warn';
            pillText = 'Aviso';
          } else if (level === 'error'){
            pillClass = 'pill--error';
            pillText = 'Alerta';
          }

          const item = document.createElement('div');
          item.className = `notif__item ${n.read_at ? 'is-read' : 'is-unread'} ${level}`;
          item.dataset.id = n.id;
          if (n.url) item.dataset.url = n.url;

          item.innerHTML = `
            <div class="notif__icon">${getNotifIcon(level)}</div>
            <div class="notif__content">
              <div class="notif__text">${escapeHtml(n.title || 'Notificación')}</div>
              <div class="notif__msg">${escapeHtml(n.message || '')}</div>
              <div class="notif__meta">
                <span class="pill ${pillClass}">${pillText}</span>
                <span class="notif__sep">•</span>
                <span class="notif__time">${escapeHtml(n.time || '')}</span>
              </div>
            </div>
            <button type="button" class="notif__item-close" aria-label="Marcar como leída" data-id="${escapeHtml(n.id)}">&times;</button>
          `;

          notifList.appendChild(item);
        });
      }

      async function loadNotifications(){
        if (!NOTIF_FEED_URL || !notifList) return;

        if (!notifLoaded){
          notifList.innerHTML = '<div class="notif__empty">Cargando…</div>';
        }

        try{
          const res = await fetch(NOTIF_FEED_URL, {
            headers:{ 'Accept':'application/json' }
          });

          const json = await res.json();

          if (!res.ok){
            throw new Error(json.message || 'Error al cargar notificaciones');
          }

          const key = buildPayloadKey(json);
          if (key !== lastPayloadKey){
            lastPayloadKey = key;
            renderNotifItems(json);
          } else {
            updateNotifCounter(Number(json?.unread || 0));
          }

          notifLoaded = true;
        }catch(e){
          console.error(e);
          notifList.innerHTML = '<div class="notif__empty">No se pudieron cargar las notificaciones.</div>';
          if (notifCountText) notifCountText.textContent = 'No se pudieron cargar';
        }
      }

      async function markAllNotifications(){
        if (!NOTIF_READALL_URL || !csrf) return;

        try{
          await fetch(NOTIF_READALL_URL, {
            method:'POST',
            headers:{
              'X-CSRF-TOKEN': csrf,
              'Accept':'application/json'
            }
          });

          await loadNotifications();
        }catch(e){
          console.error(e);
        }
      }

      async function markOneNotification(id, itemEl){
        if (!NOTIF_READONE_URL || !csrf || !id) return;

        try{
          const url = NOTIF_READONE_URL.replace('__ID__', encodeURIComponent(id));
          await fetch(url, {
            method:'POST',
            headers:{
              'X-CSRF-TOKEN': csrf,
              'Accept':'application/json'
            }
          });

          if (itemEl){
            itemEl.classList.remove('is-unread');
            itemEl.classList.add('is-read');
          }

          await loadNotifications();
        }catch(e){
          console.error(e);
        }
      }

      function openNotifPanel(){
        if (!notifPane || !notifBtn) return;
        notifPane.classList.add('is-open');
        notifPane.setAttribute('aria-hidden','false');
        notifBtn.setAttribute('aria-expanded','true');
        loadNotifications();
      }

      function closeNotifPanel(){
        if (!notifPane || !notifBtn) return;
        notifPane.classList.remove('is-open');
        notifPane.setAttribute('aria-hidden','true');
        notifBtn.setAttribute('aria-expanded','false');
      }

      if (notifBtn){
        notifBtn.addEventListener('click', function(e){
          e.stopPropagation();
          if (!notifPane) return;
          const isOpen = notifPane.classList.contains('is-open');
          isOpen ? closeNotifPanel() : openNotifPanel();
        });
      }

      if (notifMarkAll){
        notifMarkAll.addEventListener('click', function(e){
          e.preventDefault();
          markAllNotifications();
        });
      }

      if (notifMarkAllTop){
        notifMarkAllTop.addEventListener('click', function(e){
          e.preventDefault();
          markAllNotifications();
        });
      }

      if (notifList){
        notifList.addEventListener('click', function(e){
          const closeBtn = e.target.closest('.notif__item-close');
          if (closeBtn){
            const id = closeBtn.getAttribute('data-id');
            const itemEl = closeBtn.closest('.notif__item');
            if (id) markOneNotification(id, itemEl);
            e.stopPropagation();
            return;
          }

          const row = e.target.closest('.notif__item');
          if (!row) return;

          const id = row.dataset.id;
          const url = row.dataset.url || '';
          if (!url) return;

          e.preventDefault();
          (async () => {
            if (id) await markOneNotification(id, row);
            window.location.href = url;
          })();
        });
      }

      document.addEventListener('click', function(e){
        if (!notifPane || !notifBtn) return;
        if (!notifPane.contains(e.target) && !notifBtn.contains(e.target)){
          closeNotifPanel();
        }
      });

      window.addEventListener('keydown', function(e){
        if (e.key === 'Escape'){
          closeNotifPanel();
          closeSidebar();
        }
      });

      if (NOTIF_FEED_URL){
        loadNotifications();
        setInterval(loadNotifications, 10000);
      }
    })();
  </script>
</body>
</html>