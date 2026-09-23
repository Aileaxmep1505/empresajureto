<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Panel')</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="{{ asset('images/logo-icon.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('images/logo-icon.png') }}">

  {{-- Aplica el tema guardado antes de pintar, para que no parpadee --}}
  <script>
    (function () {
      try {
        var t = localStorage.getItem('theme');
        if (!t) { t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
        document.documentElement.setAttribute('data-theme', t);
      } catch (e) {}
    })();
  </script>

  <style>
    /* =====================================================================
       Variables de las vistas existentes. Se quedan con sus valores de
       siempre y NO cambian con el tema: hay casi 200 pantallas que las usan
       junto con colores fijos, y oscurecerlas dejaría texto invisible.
       ===================================================================== */
    :root{
      --bg:#f4f7fc; --surface:#ffffff; --surface-soft:#f8faff; --panel:#ffffff;
      --primary:#2f6df6; --primary-2:#1f56cf; --primary-soft:#eaf1ff;
      --accent:#ff5ca8; --text:#111827; --text-2:#1f2937; --muted:#6b7280; --muted-2:#94a3b8;
      --border:#dbe4f2; --border-strong:#cad7eb;
      --success:#13b981; --success-soft:#dcfce7; --warning:#f59e0b; --warning-soft:#fff4d6;
      --danger:#ef4444; --danger-soft:#fee2e2; --danger-2:#dc2626; --danger-3:#b91c1c; --danger-soft-2:#fff1f2;
      --shadow-sm:0 8px 20px rgba(15,23,42,.07); --shadow-md:0 14px 34px rgba(15,23,42,.10); --shadow-lg:0 22px 50px rgba(15,23,42,.14);
      --radius:16px; --radius-sm:12px; --radius-xs:10px;
      --topbar-h:74px; --sidebar-w:252px; --fade-h:16px;
    }

    /* =====================================================================
       Marco de la aplicación (menú lateral + barra superior).
       Mismo diseño que Obsidiana. Todo lleva prefijo sh- para no chocar con
       las clases que las pantallas ya definen (.card, .btn, .avatar, .dot…).
       ===================================================================== */
    :root{
      --sh-bg:#ffffff; --sh-surface:#ffffff; --sh-surface-2:#f7f8fa;
      --sh-text:#1f2633; --sh-muted:#6b7280; --sh-border:#e9ebef;
      --sh-primary:#2563eb; --sh-primary-strong:#1d4ed8; --sh-primary-soft:#edf2ff; --sh-primary-ink:#1d4ed8;
      --sh-hover:#f3f5f9;
      /* Elemento activo: azul profundo con un poco de índigo, con más presencia que un azul plano */
      --sh-activo:linear-gradient(135deg, #1e40af 0%, #2563eb 55%, #3b82f6 100%);
      --sh-activo-sombra:0 1px 0 rgba(255,255,255,.18) inset, 0 8px 18px -8px rgba(37,99,235,.75);
      --sh-danger:#ef4444; --sh-danger-soft:#fdecec;
      --sh-shadow:0 4px 20px rgba(17,24,39,.08);
      --sh-sidebar-bg:linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%); --sh-topbar-bg:#ffffff;
      --sh-ease:cubic-bezier(.22, 1, .36, 1);
      --sh-sidebar-w:252px; --sh-sidebar-w-collapsed:78px;
      --sh-page-bg:#f7f8fa;
      color-scheme:light;
    }
    :root[data-theme="dark"]{
      --sh-bg:#070c17; --sh-surface:#0f1a30; --sh-surface-2:#0c1526;
      --sh-text:#e8eef8; --sh-muted:#93a4bd; --sh-border:rgba(90,140,230,.16);
      --sh-primary:#3b82f6; --sh-primary-strong:#2563eb; --sh-primary-soft:rgba(59,130,246,.16); --sh-primary-ink:#93c5fd;
      --sh-hover:rgba(148,178,235,.07);
      --sh-activo:linear-gradient(135deg, #1d4ed8 0%, #2563eb 55%, #3b82f6 100%);
      --sh-activo-sombra:0 1px 0 rgba(255,255,255,.14) inset, 0 8px 22px -8px rgba(37,99,235,.8);
      --sh-danger:#f87171; --sh-danger-soft:rgba(220,38,38,.14);
      --sh-shadow:0 10px 30px rgba(0,0,0,.5);
      --sh-sidebar-bg:linear-gradient(180deg, #0b1427 0%, #091020 100%); --sh-topbar-bg:#0a1223;
      --sh-page-bg:#070c17;
      color-scheme:dark;
    }

    *{ box-sizing:border-box; }
    html, body{ margin:0; padding:0; }
    body{ font-family:'Quicksand', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
          background:var(--sh-page-bg); color:var(--sh-text); -webkit-font-smoothing:antialiased; }
    body.sh-bloqueado{ overflow:hidden; }

    /* ---------- Estructura ---------- */
    .sh-app{ display:grid; grid-template-columns:var(--sh-sidebar-w) minmax(0, 1fr); min-height:100vh;
             transition:grid-template-columns .34s cubic-bezier(.4,0,.2,1); }
    .sh-app.sh-no-anim, .sh-app.sh-no-anim *{ transition:none !important; }
    .sh-app.collapsed{ grid-template-columns:var(--sh-sidebar-w-collapsed) minmax(0, 1fr); }

    /* ---------- Menú lateral ---------- */
    .sh-sidebar{ position:sticky; top:0; z-index:40; height:100vh; display:flex; flex-direction:column;
                 padding:20px 16px 14px; background:var(--sh-sidebar-bg); color:var(--sh-text);
                 box-shadow:1px 0 3px rgba(17,24,39,.05), 1px 0 1px rgba(17,24,39,.03); }
    .sh-app.collapsed .sh-sidebar{ padding-left:12px; padding-right:12px; }

    .sh-brand{ display:flex; align-items:center; gap:11px; min-height:56px; padding:4px 6px 18px; text-decoration:none; color:inherit; }
    .sh-brand-logo{ flex:0 0 auto; display:flex; align-items:center; justify-content:center; width:44px; height:44px; color:var(--sh-primary); }
    .sh-brand-logo img{ width:100%; height:100%; object-fit:contain; display:block; }
    .sh-brand-text{ min-width:0; overflow:hidden; opacity:1; transform:translateX(0);
                    transition:opacity .22s ease .06s, transform .28s cubic-bezier(.4,0,.2,1); }
    .sh-brand-name{ font-size:19px; font-weight:700; letter-spacing:-.01em; line-height:1.1; white-space:nowrap; }
    .sh-brand-sub{ margin-top:3px; font-size:8.5px; letter-spacing:.06em; text-transform:uppercase; color:var(--sh-muted); white-space:nowrap; }
    .sh-app.collapsed .sh-brand{ justify-content:center; gap:0; padding-left:0; padding-right:0; }
    .sh-app.collapsed .sh-brand-logo{ width:50px; height:50px; margin:0 auto; }
    .sh-app.collapsed .sh-brand-text{ width:0; margin:0; padding:0; opacity:0; transform:translateX(-8px); pointer-events:none; }

    /* Botón contraer: flotante sobre el borde, gira según el estado */
    .sh-collapse{ position:absolute; top:30px; right:-13px; z-index:60; display:flex; align-items:center; justify-content:center;
                  width:26px; height:26px; border:1px solid var(--sh-border); border-radius:50%;
                  background:var(--sh-surface); color:var(--sh-muted); cursor:pointer; box-shadow:0 2px 8px rgba(17,24,39,.10);
                  transition:transform .2s ease, color .2s ease, background .2s ease; }
    .sh-collapse:hover{ color:var(--sh-text); background:var(--sh-surface-2); transform:scale(1.08); }
    .sh-collapse:active{ transform:scale(.94); }
    .sh-collapse svg{ width:15px; height:15px; transition:transform .34s cubic-bezier(.4,0,.2,1); }
    .sh-app.collapsed .sh-collapse svg{ transform:rotate(180deg); }

    /* La lista se desplaza pero sin barra visible. Un desvanecido arriba o
       abajo avisa que hay más opciones (lo prende el JS según el scroll). */
    .sh-nav{ --fade-arriba:0px; --fade-abajo:0px;
             flex:1; min-height:0; display:flex; flex-direction:column; gap:3px; margin:6px -6px 0; padding:2px 6px 12px;
             overflow-y:auto; overscroll-behavior:contain; scrollbar-width:none; -ms-overflow-style:none; }
    .sh-nav::-webkit-scrollbar{ display:none; }
    .sh-nav.puede-arriba{ --fade-arriba:26px; }
    .sh-nav.puede-abajo{ --fade-abajo:34px; }
    /* Solo expandido: contraído, la máscara recortaría el panel flotante de los grupos */
    .sh-app:not(.collapsed) .sh-nav, .sh-app.sidebar-open .sh-nav{
      -webkit-mask-image:linear-gradient(to bottom, transparent 0, #000 var(--fade-arriba), #000 calc(100% - var(--fade-abajo)), transparent 100%);
              mask-image:linear-gradient(to bottom, transparent 0, #000 var(--fade-arriba), #000 calc(100% - var(--fade-abajo)), transparent 100%); }

    .sh-nav-item{ position:relative; display:flex; align-items:center; gap:13px; width:100%; padding:10px 13px; border:0; border-radius:11px;
                  background:none; color:var(--sh-muted); font:inherit; font-size:14.5px; font-weight:600; text-align:left; text-decoration:none;
                  white-space:nowrap; cursor:pointer;
                  transition:background .18s ease, color .18s ease, box-shadow .25s ease, transform .12s ease; }
    .sh-nav-item > svg:first-child{ width:20px; height:20px; flex:0 0 auto; transition:color .18s ease, transform .25s var(--sh-ease); }
    .sh-nav-item:hover{ background:var(--sh-hover); color:var(--sh-text); }
    .sh-nav-item:hover > svg:first-child{ color:var(--sh-primary); transform:scale(1.08); }
    .sh-nav-item:active{ transform:scale(.985); }
    .sh-nav-item:focus-visible{ outline:2px solid var(--sh-primary); outline-offset:-2px; }

    .sh-nav-item.active{ background:var(--sh-activo); color:#fff; box-shadow:var(--sh-activo-sombra); }
    .sh-nav-item.active > svg:first-child, .sh-nav-item.active:hover > svg:first-child{ color:#fff; transform:none; }

    /* El apartado que contiene la pantalla actual se marca sin gritar */
    .sh-nav-toggle.has-active{ color:var(--sh-text); }
    .sh-nav-toggle.has-active > svg:first-child{ color:var(--sh-primary); }
    .sh-nav-group.open > .sh-nav-toggle{ color:var(--sh-text); }

    .sh-nav-label{ display:inline-block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; transition:opacity .2s ease .05s; }
    .sh-app.collapsed .sh-nav-item{ justify-content:center; gap:0; }
    .sh-app.collapsed .sh-nav-label{ width:0; opacity:0; }

    /* Globo con el nombre (menú contraído). Es un solo elemento fijo que
       mueve el JS: con un ::after dentro de la lista lo recortaba el scroll. */
    .sh-tip{ position:fixed; z-index:90; padding:7px 11px; border:1px solid var(--sh-border); border-radius:9px;
             background:var(--sh-surface); color:var(--sh-text); font-size:13px; font-weight:600; white-space:nowrap;
             box-shadow:0 10px 28px rgba(17,24,39,.16); pointer-events:none;
             opacity:0; transform:translate(-4px, -50%); transition:opacity .14s ease, transform .2s var(--sh-ease); }
    .sh-tip.is-on{ opacity:1; transform:translate(0, -50%); }

    /* Secciones: parten la lista para que no se lea como una sola columna larga */
    .sh-nav-section{ padding:15px 13px 5px; font-size:10.5px; font-weight:800; letter-spacing:.07em; text-transform:uppercase;
                     color:var(--sh-muted); white-space:nowrap; }
    .sh-nav-section:first-child{ padding-top:4px; }
    .sh-app.collapsed .sh-nav-section{ height:1px; margin:10px 12px; padding:0; overflow:hidden; color:transparent; background:var(--sh-border); }

    /* Grupos con submenú (acordeón). El alto se anima con la rejilla
       0fr → 1fr, que sí se puede transicionar sin medir con JS. */
    .sh-nav-group{ display:flex; flex-direction:column; }
    .sh-nav-chev{ width:16px !important; height:16px !important; margin-left:auto; flex:0 0 auto; pointer-events:none;
                  color:var(--sh-muted); transition:transform .28s var(--sh-ease); }
    .sh-nav-group.open .sh-nav-chev{ transform:rotate(180deg); }
    .sh-sub-wrap{ display:grid; grid-template-rows:0fr; opacity:0;
                  transition:grid-template-rows .3s var(--sh-ease), opacity .22s ease; }
    .sh-nav-group.open > .sh-sub-wrap{ grid-template-rows:1fr; opacity:1; }
    .sh-submenu{ display:flex; flex-direction:column; gap:2px; min-height:0; overflow:hidden;
                 margin-left:22px; padding-left:11px; border-left:1px solid var(--sh-border); }
    .sh-submenu > :first-child{ margin-top:4px; }
    .sh-submenu > :last-child{ margin-bottom:6px; }
    .sh-submenu form{ margin:0; }
    .sh-submenu .sh-nav-item{ gap:10px; padding:8px 11px; font-size:13.5px; border-radius:9px; }
    /* Las opciones entran con un leve deslizamiento al abrir el grupo */
    .sh-submenu .sh-nav-item{ opacity:.0001; transform:translateX(-4px); }
    .sh-nav-group.open .sh-submenu .sh-nav-item{ opacity:1; transform:none;
        transition:background .18s ease, color .18s ease, opacity .25s ease, transform .3s var(--sh-ease); }
    .sh-nav-bullet{ width:6px !important; height:6px !important; flex:0 0 auto; color:var(--sh-border); transition:color .18s ease, transform .2s var(--sh-ease); }
    .sh-submenu .sh-nav-item:hover .sh-nav-bullet{ color:var(--sh-primary); }

    /* Dentro de un submenú el activo va en tono suave: el degradado se reserva para el primer nivel */
    .sh-submenu .sh-nav-item.active{ background:var(--sh-primary-soft); color:var(--sh-primary-ink); box-shadow:none; }
    .sh-submenu .sh-nav-item.active .sh-nav-bullet{ color:var(--sh-primary); transform:scale(1.35); }

    /* Menú contraído: el grupo se abre en un panel flotante */
    .sh-app.collapsed .sh-nav-chev{ display:none; }
    .sh-app.collapsed .sh-sub-wrap{ display:contents; }
    .sh-app.collapsed .sh-nav-group .sh-submenu{ position:fixed; z-index:80; min-width:220px; margin:0; padding:6px; overflow:visible;
        border:1px solid var(--sh-border); border-radius:12px; background:var(--sh-surface); box-shadow:0 14px 40px rgba(17,24,39,.18);
        opacity:0; transform:translateX(-6px); pointer-events:none;
        transition:opacity .16s ease, transform .22s var(--sh-ease); }
    .sh-app.collapsed .sh-nav-group[data-flotante] .sh-submenu{ opacity:1; transform:translateX(0); pointer-events:auto; }
    .sh-app.collapsed .sh-submenu > :first-child, .sh-app.collapsed .sh-submenu > :last-child{ margin:0; }
    .sh-app.collapsed .sh-submenu .sh-nav-item{ justify-content:flex-start; gap:10px; padding:8px 11px; opacity:1; transform:none; }
    .sh-app.collapsed .sh-submenu::before{ content:attr(data-nombre); display:block; margin-bottom:5px; padding:7px 11px 8px;
        border-bottom:1px solid var(--sh-border); color:var(--sh-muted); font-size:10.5px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
    .sh-app.collapsed .sh-submenu .sh-nav-label{ width:auto; opacity:1; }

    /* ---------- Área principal ---------- */
    .sh-main{ display:flex; flex-direction:column; min-width:0; }
    .sh-topbar{ position:sticky; top:0; z-index:30; display:flex; align-items:center; gap:16px; padding:16px 26px;
                background:var(--sh-topbar-bg); color:var(--sh-text);
                box-shadow:0 1px 3px rgba(17,24,39,.05), 0 1px 1px rgba(17,24,39,.03); }
    .sh-topbar-titulo{ min-width:0; }
    .sh-page-title{ margin:0; font-size:25px; font-weight:700; line-height:1.15; letter-spacing:-.01em;
                    white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .sh-page-sub{ margin:2px 0 0; color:var(--sh-muted); font-size:14px; }
    .sh-spacer{ flex:1; }

    .sh-hamburger{ display:none; flex:0 0 auto; align-items:center; justify-content:center; width:42px; height:42px;
                   border:1px solid var(--sh-border); border-radius:11px; background:var(--sh-surface); color:var(--sh-text); cursor:pointer; }
    .sh-hamburger:hover{ background:var(--sh-surface-2); }

    .sh-icon-btn{ position:relative; display:flex; align-items:center; justify-content:center; width:42px; height:42px;
                  border:0; border-radius:50%; background:transparent; color:var(--sh-muted); cursor:pointer;
                  transition:background .18s ease, color .18s ease; }
    .sh-icon-btn:hover, .sh-dd.open > .sh-icon-btn{ background:var(--sh-surface-2); color:var(--sh-text); }
    .sh-icon-btn:focus-visible, .sh-user-btn:focus-visible, .sh-hamburger:focus-visible, .sh-collapse:focus-visible{ outline:2px solid var(--sh-primary); outline-offset:2px; }
    .sh-icon-btn svg{ width:19px; height:19px; }

    /* Tema: luna en claro, sol en oscuro */
    #shTema .sh-ico-sol{ display:none; }
    :root[data-theme="dark"] #shTema .sh-ico-sol{ display:block; }
    :root[data-theme="dark"] #shTema .sh-ico-luna{ display:none; }

    .sh-notif-num{ position:absolute; top:6px; right:6px; display:flex; align-items:center; justify-content:center; min-width:17px; height:17px;
                   padding:0 4px; border-radius:9px; background:var(--sh-danger); color:#fff; font-size:10.5px; font-weight:800; line-height:1; }
    .sh-notif-num[hidden]{ display:none; }

    .sh-user-btn{ display:flex; align-items:center; gap:10px; padding:6px 10px 6px 6px; border:1px solid var(--sh-border); border-radius:999px;
                  background:var(--sh-surface); color:var(--sh-text); font:inherit; cursor:pointer;
                  transition:background .18s ease, border-color .18s ease, box-shadow .18s ease; }
    .sh-user-btn:hover{ background:var(--sh-surface-2); box-shadow:0 2px 10px rgba(17,24,39,.06); }
    .sh-dd.open .sh-user-btn{ background:var(--sh-surface-2); border-color:var(--sh-primary); }
    .sh-user-btn .sh-chev{ width:16px; height:16px; color:var(--sh-muted); transition:transform .24s cubic-bezier(.4,0,.2,1); }
    .sh-dd.open .sh-user-btn .sh-chev{ transform:rotate(180deg); }
    .sh-avatar{ position:relative; flex:0 0 auto; display:flex; align-items:center; justify-content:center; width:34px; height:34px;
                border-radius:50%; overflow:hidden; background:linear-gradient(135deg, #4da3ff, var(--sh-primary)); color:#fff;
                font-size:13px; font-weight:700; }
    .sh-avatar img{ position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
    .sh-user-name{ max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:14px; font-weight:600; }

    /* ---------- Menús desplegables ---------- */
    .sh-dd{ position:relative; }
    .sh-dd-panel{ position:absolute; top:calc(100% + 10px); right:0; z-index:50; width:300px; padding:8px;
                  border:1px solid var(--sh-border); border-radius:18px; background:var(--sh-surface); color:var(--sh-text); box-shadow:var(--sh-shadow);
                  opacity:0; visibility:hidden; transform:translateY(-8px) scale(.97); transform-origin:top right; pointer-events:none;
                  transition:opacity .18s ease, transform .22s cubic-bezier(.4,0,.2,1), visibility .18s; }
    .sh-dd.open .sh-dd-panel{ opacity:1; visibility:visible; transform:translateY(0) scale(1); pointer-events:auto; }
    .sh-dd-panel--notif{ width:360px; }
    .sh-dd-head{ display:flex; align-items:center; gap:8px; padding:12px 12px 10px; }
    .sh-dd-head > div{ flex:1; min-width:0; }
    .sh-dd-head b{ display:block; font-size:15px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .sh-dd-head small{ display:block; margin-top:2px; color:var(--sh-muted); font-size:12.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .sh-dd-sep{ height:1px; margin:4px 6px 6px; background:var(--sh-border); }
    .sh-dd-item{ display:flex; align-items:center; gap:12px; width:100%; padding:10px 12px; border:0; border-radius:12px;
                 background:none; color:var(--sh-text); font:inherit; text-align:left; text-decoration:none; cursor:pointer;
                 transition:background .14s ease; }
    .sh-dd-item:hover{ background:var(--sh-surface-2); }
    .sh-dd-item:focus-visible{ outline:2px solid var(--sh-primary); outline-offset:-2px; }
    .sh-dd-item b{ display:block; font-size:14.5px; }
    .sh-dd-item small{ display:block; color:var(--sh-muted); font-size:12.5px; }
    .sh-di-ico{ flex:0 0 auto; display:flex; align-items:center; justify-content:center; width:36px; height:36px;
                border-radius:10px; background:var(--sh-primary-soft); color:var(--sh-primary); }
    .sh-di-ico svg{ width:18px; height:18px; }
    .sh-dd-item.danger .sh-di-ico{ background:var(--sh-danger-soft); color:var(--sh-danger); }
    .sh-dd-item.danger b{ color:var(--sh-danger); }
    .sh-dd-empty{ padding:26px 12px; text-align:center; color:var(--sh-muted); font-size:14px; }

    /* Notificaciones */
    .sh-notif-lista{ max-height:360px; overflow-y:auto; overscroll-behavior:contain; }
    .sh-notif-item{ position:relative; align-items:flex-start; padding-right:36px; }
    .sh-notif-item .sh-di-ico{ width:34px; height:34px; }
    .sh-notif-item.is-warn .sh-di-ico{ background:rgba(245,158,11,.14); color:#d97706; }
    .sh-notif-item.is-error .sh-di-ico{ background:var(--sh-danger-soft); color:var(--sh-danger); }
    .sh-notif-txt{ min-width:0; flex:1; }
    .sh-notif-txt b{ font-size:14px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .sh-notif-txt small{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .sh-notif-txt .sh-notif-hora{ font-size:11.5px; }
    .sh-notif-item.is-unread .sh-notif-txt b::after{ content:""; display:inline-block; width:6px; height:6px; margin-left:6px;
                                                     vertical-align:middle; border-radius:50%; background:var(--sh-primary); }
    .sh-notif-quitar{ position:absolute; top:10px; right:8px; display:flex; align-items:center; justify-content:center; width:24px; height:24px;
                      border:0; border-radius:7px; background:none; color:var(--sh-muted); font-size:17px; line-height:1; cursor:pointer; opacity:0;
                      transition:opacity .14s ease, background .14s ease; }
    .sh-notif-item:hover .sh-notif-quitar, .sh-notif-quitar:focus-visible{ opacity:1; }
    .sh-notif-quitar:hover{ background:var(--sh-border); color:var(--sh-text); }
    .sh-notif-todas{ padding:0; border:0; background:transparent; color:var(--sh-primary); font:inherit; font-size:12px; font-weight:600; cursor:pointer; white-space:nowrap; }
    .sh-notif-todas:hover{ text-decoration:underline; }
    .sh-notif-todas[hidden]{ display:none; }

    /* ---------- Contenido ---------- */
    .sh-content{ min-height:calc(100vh - var(--topbar-h)); padding:26px; background:var(--sh-page-bg); }
    .sh-content.content--flush{ padding:0; }

    /* Pantallas que todavía no están preparadas para el modo oscuro: se
       quedan en claro dentro del marco oscuro. Sus colores son fijos y al
       oscurecerlas el texto quedaría invisible. */
    .sh-content[data-tema-fijo="claro"]{ --sh-page-bg:#f4f7fc; color-scheme:light; color:#111827; }

    /* ===== Modo oscuro global para vistas con paleta propia =====
       Muchas vistas definen sus colores con variables neutras (--bg, --card,
       --ink, --line, --muted, etc.) en su :root. Aquí, en oscuro, las
       redefinimos sobre .sh-content (ancestro más cercano que gana en la
       cascada) para que TODAS esas vistas se oscurezcan sin tocarlas una por
       una. Solo fondos/superficies/texto/bordes; los acentos se dejan igual. */
    :root[data-theme="dark"] .sh-content:not([data-tema-fijo]){
      color-scheme: dark;
      --bg:#0b1220; --bg-base:#0b1220; --bg-top:#0e1a30; --bg-mid:#0b1426;
      --soft:#0f1a2e; --soft-2:#0f1a2e;
      --surface:#111d33; --surface-1:#111d33; --surface-2:#0f1a2e; --surface-3:#1a2740;
      --card:#111d33; --panel:#111d33; --panel-2:#0f1a2e;
      --ink:#eaf0fb; --ink-1:#eaf0fb; --ink-2:#c4cfe0; --heading:#eaf0fb;
      --text:#c4cfe0; --text-dark:#eaf0fb; --text-1:#eaf0fb; --text-2:#c4cfe0; --text-gray:#9aa6bd; --text-muted:#9aa6bd;
      --muted:#8a99b1; --muted-2:#7c8aa3;
      --ink-soft:#b3bfd2; --text-soft:#b3bfd2;
      --line:#243450; --line-2:#2b3c5a; --line-soft:#1e2c46; --border:#243450; --border-1:#243450; --border-2:#2b3c5a; --border-soft:#1e2c46;
    }

    /* En oscuro, el calendario y el reloj de los campos nativos se ven blancos */
    :root[data-theme="dark"] .sh-content:not([data-tema-fijo]) input[type=date]::-webkit-calendar-picker-indicator,
    :root[data-theme="dark"] .sh-content:not([data-tema-fijo]) input[type=time]::-webkit-calendar-picker-indicator,
    :root[data-theme="dark"] .sh-content:not([data-tema-fijo]) input[type=datetime-local]::-webkit-calendar-picker-indicator{ filter:invert(1) brightness(1.6); cursor:pointer; }

    /* En oscuro una sombra negra no se ve: se marca el borde con luz tenue */
    :root[data-theme="dark"] .sh-topbar{ box-shadow:0 1px 0 rgba(255,255,255,.06), 0 2px 8px rgba(0,0,0,.4); }
    :root[data-theme="dark"] .sh-sidebar{ box-shadow:1px 0 0 rgba(255,255,255,.06), 2px 0 8px rgba(0,0,0,.4); }
    :root[data-theme="dark"] .sh-nav-item.active{ box-shadow:0 8px 20px rgba(10,132,255,.25); }

    .sh-overlay{ display:none; position:fixed; inset:0; z-index:55; background:rgba(2,6,23,.5); }

    /* Compatibilidad: estilo base de .chip que algunas pantallas usan sin
       definirlo. Con :where() pesa cero y cualquier definición propia gana. */
    :where(.chip){ padding:4px 8px; border-radius:999px; background:rgba(47,109,246,.08); color:#1f4db8; font-size:.75rem; font-weight:600; }

    /* ---------- Responsive ---------- */
    @media (max-width:1024px){
      .sh-hamburger{ display:flex; }
      .sh-app, .sh-app.collapsed{ grid-template-columns:minmax(0, 1fr); }
      .sh-sidebar{ position:fixed; top:0; left:0; z-index:60; width:min(var(--sh-sidebar-w), 86vw);
                   transform:translateX(-100%); transition:transform .25s cubic-bezier(.4,0,.2,1); }
      .sh-app.sidebar-open .sh-sidebar{ transform:translateX(0); }
      .sh-app.sidebar-open .sh-overlay{ display:block; }
      .sh-collapse{ display:none; }

      /* El cajón del teléfono siempre se ve completo, aunque en escritorio
         se haya dejado contraído. */
      .sh-app.collapsed .sh-sidebar{ padding-left:16px; padding-right:16px; }
      .sh-app.collapsed .sh-brand{ justify-content:flex-start; gap:11px; padding:4px 6px 18px; }
      .sh-app.collapsed .sh-brand-logo{ width:44px; height:44px; margin:0; }
      .sh-app.collapsed .sh-brand-text, .sh-app.collapsed .sh-nav-label{ width:auto; opacity:1; transform:none; pointer-events:auto; }
      .sh-app.collapsed .sh-nav-item{ justify-content:flex-start; gap:13px; }
      .sh-app.collapsed .sh-nav-chev{ display:block; }
      .sh-app.collapsed .sh-nav-section{ height:auto; margin:0; padding:15px 13px 5px; color:var(--sh-muted); background:none; }
      .sh-app.collapsed .sh-sub-wrap{ display:grid; }
      .sh-app.collapsed .sh-nav-group .sh-submenu{ position:static; min-width:0; margin-left:22px; padding:0 0 0 11px; overflow:hidden;
          border:0; border-left:1px solid var(--sh-border); border-radius:0; background:none; box-shadow:none;
          opacity:1; transform:none; pointer-events:auto; }
      .sh-app.collapsed .sh-submenu > :first-child{ margin-top:4px; }
      .sh-app.collapsed .sh-submenu > :last-child{ margin-bottom:6px; }
      .sh-app.collapsed .sh-submenu .sh-nav-item{ opacity:.0001; transform:translateX(-4px); }
      .sh-app.collapsed .sh-nav-group.open .sh-submenu .sh-nav-item{ opacity:1; transform:none; }
      .sh-app.collapsed .sh-submenu::before{ display:none; }
      .sh-tip{ display:none; }
    }
    @media (max-width:640px){
      :root{ --topbar-h:58px; }
      .sh-content{ padding:16px; }
      .sh-topbar{ gap:8px; padding:10px 14px; }
      .sh-topbar-titulo{ flex:1; }
      .sh-page-title{ font-size:17px; }
      .sh-page-sub, .sh-spacer, .sh-user-name, .sh-user-btn .sh-chev{ display:none; }
      .sh-icon-btn{ width:36px; height:36px; }
      .sh-icon-btn svg{ width:18px; height:18px; }
      .sh-hamburger{ width:38px; height:38px; }
      .sh-user-btn{ padding:0; border:0; background:none; }
      .sh-dd-panel, .sh-dd-panel--notif{ position:fixed; top:64px; right:10px; left:10px; width:auto; }
    }
    /* ---------- Animaciones sutiles ---------- */
    /* Cambio de tema con View Transitions: el nuevo tema se revela con un círculo
       que se expande desde el botón. Es UNA sola animación compositada (fluida),
       en vez de transicionar el color de cada elemento a la vez (eso trababa). */
    ::view-transition-old(root),
    ::view-transition-new(root){ animation:none; mix-blend-mode:normal; }
    html[data-tema-vt="active"]::view-transition-group(root){ animation-duration:var(--tema-vt-dur, 450ms); }
    html[data-tema-vt="active"]::view-transition-new(root){ clip-path:var(--tema-vt-clip-from); }
    #shTema svg{ transition:transform .45s var(--sh-ease); }
    #shTema.gira svg{ transform:rotate(-90deg) scale(.85); }

    /* El número de notificaciones "salta" cuando llega una nueva */
    @keyframes sh-pop{ 0%{ transform:scale(.6); } 60%{ transform:scale(1.18); } 100%{ transform:scale(1); } }
    .sh-notif-num.pop{ animation:sh-pop .45s var(--sh-ease); }
    @keyframes sh-campana{ 0%,100%{ transform:rotate(0); } 20%{ transform:rotate(-12deg); } 45%{ transform:rotate(9deg); } 70%{ transform:rotate(-5deg); } }
    .sh-icon-btn.suena > svg{ animation:sh-campana .7s ease; transform-origin:50% 10%; }

    /* Los desplegables entran con un pequeño desliz de sus opciones */
    .sh-dd.open .sh-dd-item{ animation:sh-entra .26s var(--sh-ease) both; }
    .sh-dd.open .sh-dd-item:nth-child(3){ animation-delay:.03s; }
    .sh-dd.open .sh-dd-item:nth-child(4){ animation-delay:.06s; }
    @keyframes sh-entra{ from{ opacity:0; transform:translateY(-4px); } to{ opacity:1; transform:none; } }

    .sh-avatar{ transition:box-shadow .2s ease; }
    .sh-user-btn:hover .sh-avatar{ box-shadow:0 0 0 3px var(--sh-primary-soft); }

    @media (prefers-reduced-motion:reduce){
      .sh-app, .sh-sidebar, .sh-brand-text, .sh-nav-label, .sh-collapse, .sh-collapse svg, .sh-nav-chev, .sh-nav-item,
      .sh-nav-item > svg:first-child, .sh-sub-wrap, .sh-submenu .sh-nav-item, .sh-dd-panel, .sh-app .sh-submenu, .sh-tip,
      #shTema svg{ transition:none !important; }
      .sh-notif-num.pop, .sh-icon-btn.suena > svg, .sh-dd.open .sh-dd-item{ animation:none !important; }
      html.sh-tema-anim *{ transition:none !important; }
    }
  </style>

  {{-- Los estilos de cada pantalla van después para que ganen sobre los del marco --}}
  @stack('styles')
</head>

@php
  use Illuminate\Support\Facades\Route as R;

  $u  = auth()->user();
  $nm = $u?->name ?? 'Usuario';
  $iniciales = mb_strtoupper(collect(explode(' ', trim($nm)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('')) ?: 'U';

  $isAdmin   = $u && method_exists($u, 'hasRole') ? $u->hasRole('admin') : false;
  $isManager = $u && method_exists($u, 'hasRole') ? $u->hasRole('manager') : false;
  $restrictManager = $isManager && ! $isAdmin;

  // IDs con acceso a la Bitácora de Parte Contable
  $canSeeBitacora = $u && in_array($u->id, [2, 18]);

  // Foto solo si subió una; si no, se muestran sus iniciales.
  $avatarSrc = null;
  if ($u && ! empty($u->avatar_path)) {
      try {
          $avatarSrc = $u->avatar_url;
      } catch (\Throwable $e) {
          $avatarSrc = null;
      }
  }

  $ruta = fn (string $nombre, array $p = []) => R::has($nombre) ? route($nombre, $p) : null;

  $profileHref = $ruta('profile.show') ?? $ruta('profile') ?? url('/panel/perfil');
  $notifFeedUrl    = $ruta('notifications.feed') ?? url('/notifications/feed');
  $notifReadAllUrl = $ruta('notifications.read-all') ?? url('/notifications/read-all');
  $notifReadOneUrl = R::has('notifications.read-one')
      ? route('notifications.read-one', ['notification' => '__ID__'])
      : url('/notifications/__ID__/read');

  // ---------------- Menú ----------------
  // Un acceso: si su ruta no existe se descarta solo y no rompe la página.
  $item = fn (string $label, ?string $href, bool $activo = false) => $href ? ['label' => $label, 'href' => $href, 'activo' => $activo] : null;
  $en   = fn (...$patrones) => request()->routeIs(...$patrones);
  $grupo = function (string $label, string $icono, array $items) {
      $items = array_values(array_filter($items));
      if (! $items) return null;
      $abierto = collect($items)->contains(fn ($i) => ! empty($i['activo']));
      return ['tipo' => 'grupo', 'label' => $label, 'icon' => $icono, 'items' => $items, 'abierto' => $abierto];
  };
  $enlace = fn (string $label, string $icono, ?string $href, bool $activo = false) => $href
      ? ['tipo' => 'link', 'label' => $label, 'icon' => $icono, 'href' => $href, 'activo' => $activo]
      : null;

  $ico = [
      'inicio'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
      'ventas'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><path d="M17 5a3 3 0 0 1 0 6"/><path d="M20 20c0-2.5-1.3-4.2-3.5-5"/>',
      'licitacion' => '<path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7z"/><path d="M14 2v5h5"/><path d="M8 12h8"/><path d="M8 16h5"/>',
      'inventario' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
      'almacen'    => '<path d="M3 21V9l9-6 9 6v12"/><path d="M7 21v-8h10v8"/><path d="M7 17h10"/>',
      'logistica'  => '<rect x="1" y="7" width="13" height="10" rx="1.5"/><path d="M14 10h4l3 3v4h-7"/><circle cx="6" cy="18.5" r="1.8"/><circle cx="17.5" cy="18.5" r="1.8"/>',
      'finanzas'   => '<path d="M4 19h16"/><path d="M7 16V10"/><path d="M12 16V5"/><path d="M17 16v-7"/>',
      'whatsapp'   => '<path d="M12 4a8 8 0 0 0-6.9 12l-1.1 4 4.1-1A8 8 0 1 0 12 4z"/><path d="M9 10c.5 2 2.5 4 4.5 4.5"/>',
      'correo'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>',
      'tickets'    => '<path d="M8 4h8l3 3v10a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3V7l3-3z"/><path d="M9 11h6"/><path d="M9 15h4"/>',
      'ayuda'      => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 4.3 1.7C13 11.5 12 12 12 13"/><path d="M12 16h.01"/>',
      'documentos' => '<path d="M4 7a2 2 0 0 1 2-2h3l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7z"/>',
      'admin'      => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
      'perfil'     => '<circle cx="12" cy="8" r="4"/><path d="M5 20a7 7 0 0 1 14 0"/>',
      'contable'   => '<path d="M4 19h16"/><path d="M7 16V8"/><path d="M12 16V5"/><path d="M17 16v-4"/>',
      'altas'      => '<path d="M8 3h6l5 5v11a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M14 3v5h5"/><path d="M9 13h6"/><path d="M9 17h4"/>',
      'propuestas' => '<path d="M9 12h6"/><path d="M9 16h6"/><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
      'bitacora'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
  ];

  if ($restrictManager) {
      $menu = [
          ['titulo' => 'Accesos', 'entradas' => array_values(array_filter([
              $enlace('Mi perfil', $ico['perfil'], $ruta('profile.show'), $en('profile.*')),
              $enlace('Part. contable', $ico['contable'], $ruta('partcontable.index'), $en('partcontable.index', 'partcontable.company')),
              $enlace('Documentación de altas', $ico['altas'], $ruta('alta.docs.index'), $en('alta.docs.*')),
              $enlace('Cotizaciones', $ico['propuestas'], $ruta('propuestas-comerciales.index'), $en('propuestas-comerciales.*')),
              $canSeeBitacora ? $enlace('Bitácora', $ico['bitacora'], $ruta('partcontable.activity.all'), $en('partcontable.activity.all')) : null,
              $canSeeBitacora ? $enlace('Analíticas de actividad', '<path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16V9"/><path d="M13 16V6"/><path d="M18 16v-4"/>', $ruta('partcontable.activity.analytics'), $en('partcontable.activity.analytics')) : null,
          ]))],
      ];
  } else {
      $menu = [
          ['titulo' => null, 'entradas' => array_values(array_filter([
              $enlace('Inicio', $ico['inicio'], $ruta('dashboard'), $en('dashboard')),
          ]))],

          ['titulo' => 'Comercial', 'entradas' => array_values(array_filter([
              $grupo('Ventas y clientes', $ico['ventas'], [
                  $item('Clientes', $ruta('clients.index'), $en('clients.*')),
                  $item('Proveedores', $ruta('providers.index'), $en('providers.*')),
                  $item('Cotizaciones', $ruta('propuestas-comerciales.index'), $en('propuestas-comerciales.*')),
                  $item('Compras y ventas', $ruta('publications.index'), $en('publications.index', 'publications.show')),
                  $item('Pedidos web', $ruta('admin.orders.index'), $en('admin.orders.*')),
              ]),
              $grupo('Licitaciones', $ico['licitacion'], [
                  $item('Centro de control', $ruta('projects.control'), $en('projects.control')),
                  $item('Tablero de licitaciones', $ruta('projects.index'), $en('projects.index', 'projects.show')),
              ]),
          ]))],

          ['titulo' => 'Operación', 'entradas' => array_values(array_filter([
              $grupo('Inventario', $ico['inventario'], [
                  $item('Productos', $ruta('admin.catalog.index'), $en('admin.catalog.*')),
                  $item('Catálogo', $ruta('products.index'), $en('products.index', 'products.show')),
                  $item('Fichas técnicas', $ruta('tech-sheets.index'), $en('tech-sheets.*')),
                  $item('Activos e inventario', url('/internal-assets'), request()->is('internal-assets*') || $en('assets.board')),
              ]),
              $grupo('Almacén', $ico['almacen'], [
                  $item('Panel del almacén', $ruta('admin.wms.home'), $en('admin.wms.home')),
                  $item('Reabastecimiento', $ruta('admin.wms.replenishment.index'), $en('admin.wms.replenishment.*')),
                  $item('Conteos', $ruta('admin.wms.counts.index'), $en('admin.wms.counts.*')),
                  $item('Cross-docking', $ruta('admin.wms.crossdock.index'), $en('admin.wms.crossdock.*')),
                  $item('Productividad', $ruta('admin.wms.labor.index'), $en('admin.wms.labor.*')),
                  $item('Citas de andén', $ruta('admin.wms.docks.index'), $en('admin.wms.docks.*')),
              ]),
              $grupo('Logística', $ico['logistica'], [
                  $item('Rutas', $ruta('routes.index'), $en('routes.index', 'routes.show')),
                  $item('Vehículos', $ruta('vehicles.index'), $en('vehicles.*')),
                  $item('Agenda', $ruta('agenda.calendar'), $en('agenda.*')),
              ]),
          ]))],

          ['titulo' => 'Finanzas', 'entradas' => array_values(array_filter([
              $grupo('Finanzas', $ico['finanzas'], [
                  $item('Facturas', $ruta('manual_invoices.index'), $en('manual_invoices.index', 'manual_invoices.show')),
                  $item('Contabilidad', $ruta('accounting.dashboard'), $en('accounting.*')),
                  $item('Part. contable', $ruta('partcontable.index'), $en('partcontable.index', 'partcontable.company')),
                  $item('Gastos', $ruta('expenses.index'), $en('expenses.*')),
                  $canSeeBitacora ? $item('Bitácora', $ruta('partcontable.activity.all'), $en('partcontable.activity.all')) : null,
                  $canSeeBitacora ? $item('Analíticas de actividad', $ruta('partcontable.activity.analytics'), $en('partcontable.activity.analytics')) : null,
              ]),
          ]))],

          ['titulo' => 'Comunicación', 'entradas' => array_values(array_filter([
              $enlace('WhatsApp', $ico['whatsapp'], $ruta('admin.whatsapp.conversations'), $en('admin.whatsapp.*')),
              $enlace('Correo', $ico['correo'], $ruta('mail.index'), $en('mail.*')),
              $grupo('Tickets', $ico['tickets'], [
                  $item('Lista de tickets', $ruta('tickets.index'), $en('tickets.index', 'tickets.show')),
                  $item('Mis tickets', $ruta('tickets.my'), $en('tickets.my')),
              ]),
              ($g = $grupo('Help Desk', $ico['ayuda'], [
                  $item('Tickets de usuarios', $ruta('admin.help.index'), $en('admin.help.index', 'admin.help.show')),
              ])) && R::has('admin.help.sync')
                  ? array_merge($g, ['items' => array_merge($g['items'], [['tipo' => 'form', 'label' => 'Reindexar conocimiento', 'action' => route('admin.help.sync')]])])
                  : $g,
          ]))],

          ['titulo' => 'Administración', 'entradas' => array_values(array_filter([
              $grupo('Documentación', $ico['documentos'], [
                  $item('Documentación', url('/confidential/vault/6'), request()->is('confidential*')),
                  $item('Documentación de altas', $ruta('alta.docs.index'), $en('alta.docs.*')),
              ]),
              $grupo('Administración', $ico['admin'], [
                  $item('Usuarios', $ruta('admin.users.index'), $en('admin.users.*')),
                  $isAdmin ? $item('Roles y permisos', $ruta('admin.roles.index'), $en('admin.roles.*')) : null,
                  $item('Banners del inicio', $ruta('admin.home-banners.index'), $en('admin.home-banners.*')),
                  $item('Filas del inicio', $ruta('admin.home-product-sections.index'), $en('admin.home-product-sections.*')),
                  $item('Categorías web', $ruta('admin.category-products.index'), $en('admin.category-products.*')),
              ]),
          ]))],
      ];
  }

  $titulo = trim($__env->yieldContent('header')) ?: trim($__env->yieldContent('title')) ?: 'Panel';
  $temaOscuro = trim($__env->yieldContent('tema_oscuro')) !== '';
  // Por defecto todas las vistas siguen el tema (día/oscuro). Una vista puede
  // forzar el modo claro con @section('tema_claro', '1') si aún no está adaptada.
  $temaClaroForzado = trim($__env->yieldContent('tema_claro')) !== '';
@endphp

<body class="app">
<div class="sh-app" id="shApp">
  <div class="sh-overlay" id="shOverlay"></div>

  {{-- ===================== MENÚ LATERAL ===================== --}}
  <aside class="sh-sidebar" id="shSidebar" aria-label="Menú principal">
    <a class="sh-brand" href="{{ $ruta('dashboard') ?? url('/') }}">
      <span class="sh-brand-logo">
        <img src="{{ asset('images/logo-icon.png') }}" alt=""
             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
        <svg style="display:none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 7v10l9 4 9-4V7"/></svg>
      </span>
      <span class="sh-brand-text">
        <span class="sh-brand-name" style="display:block;">{{ config('app.name') }}</span>
        <span class="sh-brand-sub" style="display:block;">Plataforma empresarial</span>
      </span>
    </a>

    <button class="sh-collapse" id="shCollapse" type="button" aria-label="Contraer menú" title="Contraer menú">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </button>

    <nav class="sh-nav" id="shNav">
      @foreach($menu as $seccion)
        @continue(empty($seccion['entradas']))
        @if($seccion['titulo'])<div class="sh-nav-section">{{ $seccion['titulo'] }}</div>@endif

        @foreach($seccion['entradas'] as $e)
          @if($e['tipo'] === 'link')
            <a class="sh-nav-item {{ $e['activo'] ? 'active' : '' }}" href="{{ $e['href'] }}" data-tip="{{ $e['label'] }}" @if($e['activo']) aria-current="page" @endif>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $e['icon'] !!}</svg>
              <span class="sh-nav-label">{{ $e['label'] }}</span>
            </a>
          @else
            <div class="sh-nav-group {{ $e['abierto'] ? 'open' : '' }}">
              <a class="sh-nav-item sh-nav-toggle {{ $e['abierto'] ? 'has-active' : '' }}" href="#" role="button"
                 aria-expanded="{{ $e['abierto'] ? 'true' : 'false' }}" data-tip="{{ $e['label'] }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $e['icon'] !!}</svg>
                <span class="sh-nav-label">{{ $e['label'] }}</span>
                <svg class="sh-nav-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
              </a>
              <div class="sh-sub-wrap"><div class="sh-submenu">
                @foreach($e['items'] as $s)
                  @if(($s['tipo'] ?? 'link') === 'form')
                    <form method="POST" action="{{ $s['action'] }}">
                      @csrf
                      <button type="submit" class="sh-nav-item sh-nav-sub" data-tip="{{ $s['label'] }}">
                        <svg class="sh-nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                        <span class="sh-nav-label">{{ $s['label'] }}</span>
                      </button>
                    </form>
                  @else
                    <a class="sh-nav-item sh-nav-sub {{ $s['activo'] ? 'active' : '' }}" href="{{ $s['href'] }}" data-tip="{{ $s['label'] }}" @if($s['activo']) aria-current="page" @endif>
                      <svg class="sh-nav-bullet" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                      <span class="sh-nav-label">{{ $s['label'] }}</span>
                    </a>
                  @endif
                @endforeach
              </div></div>
            </div>
          @endif
        @endforeach
      @endforeach
    </nav>
    <div class="sh-tip" id="shTip" role="tooltip" aria-hidden="true"></div>
  </aside>

  {{-- ===================== ÁREA PRINCIPAL ===================== --}}
  <div class="sh-main">
    <header class="sh-topbar">
      <button class="sh-hamburger" id="shHamburger" type="button" aria-label="Abrir menú" aria-controls="shSidebar" aria-expanded="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>

      <div class="sh-topbar-titulo">
        <h1 class="sh-page-title">{{ $titulo }}</h1>
        @hasSection('page-sub')<p class="sh-page-sub">@yield('page-sub')</p>@endif
      </div>
      <div class="sh-spacer"></div>

      {{-- Tema claro / oscuro --}}
      <button class="sh-icon-btn" id="shTema" type="button" aria-label="Cambiar a modo oscuro" title="Cambiar tema">
        <svg class="sh-ico-luna" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
        <svg class="sh-ico-sol" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4.5"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5L19 19M19 5l-1.5 1.5M6.5 17.5L5 19"/></svg>
      </button>

      {{-- Notificaciones --}}
      <div class="sh-dd" id="shNotif">
        <button class="sh-icon-btn" type="button" data-dd="shNotif" aria-label="Notificaciones" aria-haspopup="true" aria-expanded="false">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
          <span class="sh-notif-num" id="shNotifNum" hidden></span>
        </button>
        <div class="sh-dd-panel sh-dd-panel--notif" role="menu" aria-label="Notificaciones">
          <div class="sh-dd-head">
            <div><b>Notificaciones</b><small id="shNotifSub">Cargando…</small></div>
            <button type="button" class="sh-notif-todas" id="shNotifTodas" hidden>Marcar todas leídas</button>
          </div>
          <div class="sh-notif-lista" id="shNotifLista"><div class="sh-dd-empty">Cargando…</div></div>
        </div>
      </div>

      {{-- Usuario --}}
      <div class="sh-dd" id="shUser">
        <button class="sh-user-btn" type="button" data-dd="shUser" aria-haspopup="true" aria-expanded="false" aria-label="Tu cuenta">
          <span class="sh-avatar">
            {{ $iniciales }}
            @if($avatarSrc)<img src="{{ $avatarSrc }}" alt="" onerror="this.remove()">@endif
          </span>
          <span class="sh-user-name">{{ $nm }}</span>
          <svg class="sh-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="sh-dd-panel" role="menu">
          <div class="sh-dd-head"><div><b>{{ $nm }}</b><small>{{ $u?->email }}</small></div></div>
          <a class="sh-dd-item" href="{{ $profileHref }}">
            <span class="sh-di-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4v16h16v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></span>
            <span><b>Mi perfil</b><small>Foto, contraseña y datos personales</small></span>
          </a>
          @if($isAdmin && R::has('admin.users.index'))
            <a class="sh-dd-item" href="{{ route('admin.users.index') }}">
              <span class="sh-di-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg></span>
              <span><b>Panel de usuarios</b><small>Aprobar cuentas y asignar roles</small></span>
            </a>
          @endif
          <div class="sh-dd-sep"></div>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="sh-dd-item danger" type="submit">
              <span class="sh-di-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
              <span><b>Cerrar sesión</b><small>Salir de tu cuenta en este equipo</small></span>
            </button>
          </form>
        </div>
      </div>
    </header>

    <main id="content" class="content sh-content @yield('content_class')" @if($temaClaroForzado) data-tema-fijo="claro" @endif>
      @yield('content')
    </main>
  </div>
</div>

<script>
(function () {
  var app       = document.getElementById('shApp');
  var sidebar   = document.getElementById('shSidebar');
  var hamburger = document.getElementById('shHamburger');
  var collapse  = document.getElementById('shCollapse');
  var overlay   = document.getElementById('shOverlay');
  var movil     = function () { return window.matchMedia('(max-width:1024px)').matches; };

  // ---------------- Menú lateral ----------------
  function etiquetaContraer() {
    var c = app.classList.contains('collapsed');
    collapse.setAttribute('aria-label', c ? 'Expandir menú' : 'Contraer menú');
    collapse.setAttribute('title', c ? 'Expandir menú' : 'Contraer menú');
  }
  function abrirCajon(abrir) {
    app.classList.toggle('sidebar-open', abrir);
    document.body.classList.toggle('sh-bloqueado', abrir);
    hamburger.setAttribute('aria-expanded', abrir ? 'true' : 'false');
  }

  // El estado contraído se aplica sin animación al cargar.
  if (!movil()) {
    app.classList.add('sh-no-anim');
    try { if (localStorage.getItem('sidebar-collapsed') === '1') app.classList.add('collapsed'); } catch (e) {}
    void app.offsetWidth;
    requestAnimationFrame(function () { app.classList.remove('sh-no-anim'); });
  }
  etiquetaContraer();

  collapse.addEventListener('click', function () {
    app.classList.toggle('collapsed');
    try { localStorage.setItem('sidebar-collapsed', app.classList.contains('collapsed') ? '1' : '0'); } catch (e) {}
    etiquetaContraer();
  });
  hamburger.addEventListener('click', function () { abrirCajon(!app.classList.contains('sidebar-open')); });
  overlay.addEventListener('click', function () { abrirCajon(false); });
  window.addEventListener('resize', function () { if (!movil()) abrirCajon(false); });

  // ---------------- Submenús ----------------
  var grupos = Array.prototype.slice.call(document.querySelectorAll('.sh-nav-group'));
  var flotante = null, espera = null;

  grupos.forEach(function (g) {
    var sub = g.querySelector('.sh-submenu'), t = g.querySelector('.sh-nav-toggle');
    if (sub && t) sub.dataset.nombre = t.dataset.tip || '';
  });

  // En el teléfono el menú se ve completo aunque en escritorio esté contraído.
  function contraido() { return app.classList.contains('collapsed') && !movil(); }

  function ocultarFlotante() {
    if (!flotante) return;
    flotante.removeAttribute('data-flotante');
    flotante = null;
  }
  function mostrarFlotante(g) {
    clearTimeout(espera);
    if (flotante === g) return;
    ocultarFlotante();
    var sub = g.querySelector('.sh-submenu');
    var r = g.getBoundingClientRect();
    // Se ancla al ancho final de la barra: durante la animación la medida en pantalla todavía cambia.
    var barra = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--sh-sidebar-w-collapsed'), 10) || 78;
    sub.style.left = (barra + 8) + 'px';
    sub.style.top = Math.max(10, Math.min(r.top, window.innerHeight - sub.offsetHeight - 10)) + 'px';
    g.setAttribute('data-flotante', '');
    flotante = g;
  }

  grupos.forEach(function (g) {
    var t = g.querySelector('.sh-nav-toggle');
    if (!t) return;

    t.addEventListener('click', function (e) {
      e.preventDefault();
      if (contraido()) { flotante === g ? ocultarFlotante() : mostrarFlotante(g); return; }

      // Acordeón: un solo apartado abierto a la vez.
      var abierto = g.classList.contains('open');
      grupos.forEach(function (o) {
        o.classList.remove('open');
        o.querySelector('.sh-nav-toggle')?.setAttribute('aria-expanded', 'false');
      });
      if (!abierto) { g.classList.add('open'); t.setAttribute('aria-expanded', 'true'); }
    });

    g.addEventListener('mouseenter', function () { if (contraido()) mostrarFlotante(g); });
    g.addEventListener('mouseleave', function () { if (contraido()) espera = setTimeout(ocultarFlotante, 200); });
  });

  document.addEventListener('click', function (e) { if (!e.target.closest('.sh-nav-group')) ocultarFlotante(); });
  window.addEventListener('resize', ocultarFlotante);

  // ---------------- Desvanecido del menú ----------------
  // En vez de barra de desplazamiento: se desvanece el borde por donde hay más opciones.
  var nav = document.getElementById('shNav');
  function bordesNav() {
    var arriba = nav.scrollTop > 4;
    var abajo = nav.scrollTop + nav.clientHeight < nav.scrollHeight - 4;
    nav.classList.toggle('puede-arriba', arriba);
    nav.classList.toggle('puede-abajo', abajo);
  }
  nav.addEventListener('scroll', bordesNav, { passive: true });
  window.addEventListener('resize', bordesNav);
  // Al abrir o cerrar un grupo el alto cambia durante la animación: se revisa al terminar.
  nav.addEventListener('transitionend', function (e) { if (e.target.classList.contains('sh-sub-wrap')) bordesNav(); });

  // La opción de la pantalla actual queda a la vista aunque esté al fondo del menú.
  var actual = nav.querySelector('.sh-nav-item.active');
  if (actual) {
    var r = actual.getBoundingClientRect(), n = nav.getBoundingClientRect();
    if (r.bottom > n.bottom - 30) nav.scrollTop += r.bottom - n.bottom + 60;
  }
  bordesNav();

  // ---------------- Globo con el nombre (menú contraído) ----------------
  var tip = document.getElementById('shTip');
  nav.addEventListener('mouseover', function (e) {
    var it = e.target.closest('.sh-nav-item');
    // Los grupos ya abren su panel con el nombre; dentro del panel no hace falta globo.
    if (!it || !contraido() || it.classList.contains('sh-nav-toggle') || it.closest('.sh-submenu')) return;
    var r = it.getBoundingClientRect();
    // Se ancla al ancho final de la barra: si el menú se acaba de contraer, la medida en pantalla todavía está cambiando.
    var barra = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--sh-sidebar-w-collapsed'), 10) || 78;
    tip.textContent = it.dataset.tip || '';
    tip.style.left = (barra + 10) + 'px';
    tip.style.top = (r.top + r.height / 2) + 'px';
    tip.classList.add('is-on');
  });
  nav.addEventListener('mouseout', function (e) {
    if (!e.relatedTarget || !e.relatedTarget.closest || e.relatedTarget.closest('.sh-nav-item') !== e.target.closest('.sh-nav-item')) {
      tip.classList.remove('is-on');
    }
  });
  function ocultarTip() { tip.classList.remove('is-on'); }
  nav.addEventListener('scroll', ocultarTip, { passive: true });
  nav.addEventListener('mouseleave', ocultarTip);
  collapse.addEventListener('click', ocultarTip);

  // ---------------- Tema ----------------
  var btnTema = document.getElementById('shTema');
  function etiquetaTema() {
    var oscuro = document.documentElement.getAttribute('data-theme') === 'dark';
    btnTema.setAttribute('aria-label', oscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
  }
  etiquetaTema();
  btnTema.addEventListener('click', function () {
    var html = document.documentElement;
    var t = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';

    // Aplica el tema (data-theme + guardado + evento). Es lo único que cambia el DOM.
    function aplicarTema() {
      html.setAttribute('data-theme', t);
      try { localStorage.setItem('theme', t); } catch (e) {}
      etiquetaTema();
      document.dispatchEvent(new CustomEvent('tema:cambio', { detail: { tema: t } }));
    }

    // Giro del icono siempre.
    btnTema.classList.add('gira');
    clearTimeout(window._shTema);
    window._shTema = setTimeout(function () { btnTema.classList.remove('gira'); }, 450);

    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Sin soporte de View Transitions (o reduced-motion): cambio instantáneo, sin
    // transicionar cada elemento (eso era lo que trababa).
    if (typeof document.startViewTransition !== 'function' || reduce) { aplicarTema(); return; }
    if (html.dataset.temaVt === 'active') return; // ya hay una animación en curso

    // Círculo que se expande desde el centro del botón.
    var r = btnTema.getBoundingClientRect();
    var x = r.left + r.width / 2, y = r.top + r.height / 2;
    var w = window.innerWidth, h = window.innerHeight;
    var maxR = Math.hypot(Math.max(x, w - x), Math.max(y, h - y));
    var dur = 450;
    var px = (x / w * 100) + '%', py = (y / h * 100) + '%';
    var radPct = (maxR / (Math.hypot(w, h) / Math.SQRT2) * 100) + '%';
    var clipFrom = 'circle(0% at ' + px + ' ' + py + ')';
    var clipTo = 'circle(' + radPct + ' at ' + px + ' ' + py + ')';

    html.dataset.temaVt = 'active';
    html.style.setProperty('--tema-vt-dur', dur + 'ms');
    html.style.setProperty('--tema-vt-clip-from', clipFrom);

    function limpiar() {
      delete html.dataset.temaVt;
      html.style.removeProperty('--tema-vt-dur');
      html.style.removeProperty('--tema-vt-clip-from');
    }

    var vt = document.startViewTransition(function () { aplicarTema(); });

    if (vt.finished && typeof vt.finished.finally === 'function') {
      vt.finished.finally(limpiar).catch(function () {});
    } else { limpiar(); }

    if (vt.ready && typeof vt.ready.then === 'function') {
      vt.ready.then(function () {
        document.documentElement.animate(
          { clipPath: [clipFrom, clipTo] },
          { duration: dur, easing: 'ease-in-out', fill: 'forwards', pseudoElement: '::view-transition-new(root)' }
        );
      }).catch(function () {});
    }
  });

  // ---------------- Desplegables ----------------
  function cerrarDesplegables() {
    document.querySelectorAll('.sh-dd.open').forEach(function (d) {
      d.classList.remove('open');
      d.querySelector('[data-dd]')?.setAttribute('aria-expanded', 'false');
    });
  }
  document.querySelectorAll('[data-dd]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var dd = document.getElementById(btn.getAttribute('data-dd'));
      var estaba = dd.classList.contains('open');
      cerrarDesplegables();
      if (!estaba) {
        dd.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
        if (dd.id === 'shNotif') cargarNotificaciones();
      }
    });
  });
  document.querySelectorAll('.sh-dd-panel').forEach(function (p) { p.addEventListener('click', function (e) { e.stopPropagation(); }); });
  document.addEventListener('click', cerrarDesplegables);
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    cerrarDesplegables(); ocultarFlotante(); abrirCajon(false);
  });

  // ---------------- Notificaciones ----------------
  var FEED = @json($notifFeedUrl), LEER_TODAS = @json($notifReadAllUrl), LEER_UNA = @json($notifReadOneUrl);
  var csrf  = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  var lista = document.getElementById('shNotifLista');
  var num   = document.getElementById('shNotifNum');
  var sub   = document.getElementById('shNotifSub');
  var todas = document.getElementById('shNotifTodas');
  var ultima = null;

  var ICONOS = {
    info:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    warn:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4 3 20h18L12 4z"/><path d="M12 10v4"/><path d="M12 17h.01"/></svg>',
    error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M15 9 9 15"/><path d="m9 9 6 6"/></svg>'
  };
  function esc(v) { return String(v ?? '').replace(/[&<>"']/g, function (c) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]; }); }

  var previo = null;
  function contador(n) {
    // Si llegó una nueva, el número salta y la campana se mueve una vez.
    if (previo !== null && n > previo) {
      num.classList.remove('pop'); void num.offsetWidth; num.classList.add('pop');
      var campana = num.closest('.sh-icon-btn');
      campana.classList.remove('suena'); void campana.offsetWidth; campana.classList.add('suena');
    }
    previo = n;
    num.hidden = !(n > 0);
    num.textContent = n > 9 ? '9+' : String(n || '');
    sub.textContent = n > 0 ? n + (n === 1 ? ' sin leer' : ' sin leer') : 'Estás al día';
    todas.hidden = !(n > 0);
  }
  function vacio(txt) { lista.innerHTML = '<div class="sh-dd-empty">' + esc(txt) + '</div>'; }

  function pintar(datos) {
    var items = Array.isArray(datos?.items) ? datos.items : [];
    contador(Number(datos?.unread || 0));
    if (!items.length) return vacio('No tienes notificaciones.');
    lista.innerHTML = items.map(function (n) {
      var nivel = ['warn', 'error'].indexOf(n.status) >= 0 ? n.status : 'info';
      // Un div con rol de enlace: dentro va el botón de quitar, y un botón no puede ir dentro de un <a>.
      return '<div class="sh-dd-item sh-notif-item is-' + nivel + ' ' + (n.read_at ? 'is-read' : 'is-unread') + '" role="link" tabindex="0"'
        + ' data-href="' + esc(n.url || '') + '" data-id="' + esc(n.id) + '">'
        + '<span class="sh-di-ico">' + ICONOS[nivel] + '</span>'
        + '<span class="sh-notif-txt"><b>' + esc(n.title || 'Notificación') + '</b>'
        + (n.message ? '<small>' + esc(n.message) + '</small>' : '')
        + '<small class="sh-notif-hora">' + esc(n.time || '') + '</small></span>'
        + '<button type="button" class="sh-notif-quitar" aria-label="Quitar notificación" data-quitar="' + esc(n.id) + '">&times;</button>'
        + '</div>';
    }).join('');
  }

  async function cargarNotificaciones() {
    if (!FEED) return;
    try {
      var r = await fetch(FEED, { headers: { 'Accept': 'application/json' } });
      var d = await r.json();
      if (!r.ok) throw new Error(d.message || 'Error');
      var llave = (d.items || []).map(function (n) { return n.id + (n.read_at ? '1' : '0'); }).join('|') + '|' + (d.unread || 0);
      if (llave !== ultima) { ultima = llave; pintar(d); } else { contador(Number(d.unread || 0)); }
    } catch (e) {
      if (ultima === null) { vacio('No se pudieron cargar las notificaciones.'); sub.textContent = 'Sin conexión'; }
    }
  }

  function post(url) { return fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } }); }

  todas.addEventListener('click', async function () {
    try { await post(LEER_TODAS); } catch (e) {}
    ultima = null; cargarNotificaciones();
  });

  async function abrirNotificacion(fila, quitar) {
    var id = fila.dataset.id;

    if (quitar) {
      fila.remove();
      try { await post(LEER_UNA.replace('__ID__', encodeURIComponent(id))); } catch (err) {}
      ultima = null; cargarNotificaciones();
      return;
    }

    var destino = fila.dataset.href;
    try { if (id) await post(LEER_UNA.replace('__ID__', encodeURIComponent(id))); } catch (err) {}
    if (destino) window.location.href = destino;
    else { ultima = null; cargarNotificaciones(); }
  }

  lista.addEventListener('click', function (e) {
    var fila = e.target.closest('.sh-notif-item');
    if (!fila) return;
    e.preventDefault();
    abrirNotificacion(fila, !!e.target.closest('[data-quitar]'));
  });
  lista.addEventListener('keydown', function (e) {
    var fila = e.target.closest('.sh-notif-item');
    if (!fila || e.target !== fila || (e.key !== 'Enter' && e.key !== ' ')) return;
    e.preventDefault();
    abrirNotificacion(fila, false);
  });

  if (FEED) { cargarNotificaciones(); setInterval(cargarNotificaciones, 15000); }
})();
</script>

@stack('scripts')
</body>
</html>
