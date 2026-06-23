<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar contraseña - Soluciones Jureto</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg: #f8fafc;
      --card: #ffffff;
      --ink: #2e3a59;
      --title: #1f2b49;
      --muted: #5c6885;
      --muted-2: #4c566f;
      --line: #dfe5ee;

      --blue: #1677f2;
      --blue-strong: #0f73f1;
      --blue-soft: #e7f0ff;
      --blue-soft-2: #edf3ff;
      --field: #e9eff8;
      --field-border: #d6deeb;

      --success: #15803d;
      --success-soft: #e6ffe6;
      --danger: #d92020;
      --danger-soft: #ffebeb;

      --ring-line: rgba(22, 119, 242, 0.18);
      --ring-line-soft: rgba(22, 119, 242, 0.08);

      --shadow-soft: 0 4px 12px rgba(0,0,0,0.02);
      --shadow-button: 0 10px 24px rgba(22, 119, 242, 0.18);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html,
    body {
      min-height: 100%;
    }

    body {
      font-family: 'Quicksand', sans-serif;
      background: var(--bg);
      color: var(--ink);
      -webkit-font-smoothing: antialiased;
      text-rendering: optimizeLegibility;
    }

    .page {
      min-height: 100vh;
      display: grid;
      grid-template-columns: minmax(420px, 560px) 1fr;
      background: linear-gradient(135deg, #ebf2fa 0%, #dbe7f6 100%);
    }

    /* =========================
       PANEL IZQUIERDO
    ========================== */
    .auth-panel {
      background: var(--card);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 56px clamp(28px, 6vw, 88px);
      position: relative;
      z-index: 10;
      border-radius: 0 48px 48px 0;
      box-shadow: 20px 0 50px rgba(27, 38, 66, 0.06);
    }

    .auth-wrap {
      width: 100%;
      max-width: 455px;
    }

    .brand {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
      margin-bottom: 20px;
      text-align: center;
    }

    .brand img {
      width: 76px;
      height: auto;
      object-fit: contain;
      margin-bottom: 16px;
    }

    .brand-name {
      color: var(--title);
      font-size: 24px;
      line-height: 1;
      font-weight: 700;
      letter-spacing: .26em;
    }

    .brand-name span {
      color: var(--blue);
    }

    .brand-tag {
      color: #5c6885;
      font-size: 10.5px;
      line-height: 1.4;
      font-weight: 700;
      letter-spacing: .24em;
      text-transform: uppercase;
    }

    .auth-title {
      color: var(--title);
      font-size: clamp(30px, 3.6vw, 42px);
      line-height: 1.1;
      letter-spacing: -0.03em;
      font-weight: 700;
      margin-bottom: 10px;
    }

    .auth-subtitle {
      color: #5c6885;
      font-size: 15px;
      line-height: 1.65;
      font-weight: 500;
      margin-bottom: 22px;
    }

    /* =========================
       ALERTAS Y FORM
    ========================== */
    .alert {
      border-radius: 14px;
      padding: 12px 14px;
      margin-bottom: 16px;
      font-size: 14px;
      font-weight: 700;
      line-height: 1.45;
    }

    .alert.success {
      background: var(--success-soft);
      color: var(--success);
      border: 1px solid #caefcf;
    }

    .alert.error {
      background: var(--danger-soft);
      color: var(--danger);
      border: 1px solid #ffd6d6;
    }

    .form {
      display: grid;
      gap: 16px;
    }

    .field {
      display: grid;
      gap: 8px;
    }

    .field-label {
      color: var(--title);
      font-size: 15px;
      line-height: 1.3;
      font-weight: 700;
    }

    .inputBx {
      position: relative;
      width: 100%;
    }

    .inputBx input {
      width: 100%;
      min-height: 56px;
      padding: 14px 20px;
      border-radius: 18px;
      border: 1px solid var(--field-border);
      background: var(--field);
      color: var(--title);
      font-family: 'Quicksand', sans-serif;
      font-size: 15px;
      font-weight: 600;
      outline: none;
      transition: border-color .18s ease, box-shadow .18s ease, background .18s ease, transform .18s ease;
    }

    .inputBx input::placeholder {
      color: #6b7794;
      font-weight: 500;
    }

    .inputBx input:focus {
      border-color: var(--blue);
      background: #e6eefc;
      box-shadow: 0 0 0 3px rgba(22, 119, 242, 0.14);
    }

    .inputBx.invalid input {
      border-color: var(--danger);
      background: #fff8f8;
    }

    .field-error {
      width: fit-content;
      max-width: 100%;
      display: inline-flex;
      align-items: center;
      padding: 6px 10px;
      border-radius: 999px;
      background: var(--danger-soft);
      color: var(--danger);
      font-size: 13px;
      line-height: 1.3;
      font-weight: 700;
    }

    .links-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
      margin-top: 2px;
    }

    .auth-link {
      color: var(--blue);
      font-size: 14px;
      font-weight: 700;
      text-decoration: none;
    }

    .auth-link:hover {
      text-decoration: underline;
    }

    .btn-submit {
      width: 100%;
      min-height: 56px;
      border: 0;
      border-radius: 999px;
      background: var(--blue-strong);
      color: #ffffff;
      font-family: 'Quicksand', sans-serif;
      font-size: 17px;
      line-height: 1;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      box-shadow: var(--shadow-button);
      transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
      position: relative;
      overflow: hidden;
      margin-top: 6px;
    }

    .btn-submit:hover {
      background: #0d6ce4;
      transform: translateY(-1px);
      box-shadow: 0 14px 28px rgba(22, 119, 242, 0.22);
    }

    .btn-submit:active {
      transform: scale(.98);
    }

    .btn-submit[data-loading="true"] {
      pointer-events: none;
      opacity: .92;
    }

    .btn-loader {
      width: 18px;
      height: 18px;
      border-radius: 50%;
      border: 2px solid rgba(255,255,255,.35);
      border-top-color: #ffffff;
      display: none;
      animation: spin .7s linear infinite;
    }

    .btn-submit[data-loading="true"] .btn-loader {
      display: inline-block;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* =========================
       PANEL DERECHO (GLASSMORPHISM, AZUL UNIFICADO)
    ========================== */
    .visual-panel {
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 44px 36px;
      background: transparent;
      z-index: 1;
    }

    .bg-glow-blob {
      position: absolute;
      width: 380px;
      height: 380px;
      border-radius: 50%;
      filter: blur(55px);
      opacity: 0.65;
      pointer-events: none;
      z-index: 0;
    }
    .blob-1 {
      background: linear-gradient(135deg, #4aa3ff, #6fb1fc);
      top: 6%;
      right: 10%;
      animation: floatBlobOne 11s ease-in-out infinite;
    }
    .blob-2 {
      background: linear-gradient(135deg, #0052d4, #1677f2);
      bottom: 8%;
      left: 5%;
      animation: floatBlobTwo 13s ease-in-out infinite;
    }

    @keyframes floatBlobOne {
      0%, 100% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(-40px, 50px) scale(1.18); }
    }
    @keyframes floatBlobTwo {
      0%, 100% { transform: translate(0, 0) scale(1.1); }
      50% { transform: translate(50px, -40px) scale(0.88); }
    }

    .visual-panel::before {
      content: "";
      position: absolute;
      width: 980px;
      height: 980px;
      border-radius: 50%;
      border: 1px dashed rgba(22, 119, 242, 0.12);
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      pointer-events: none;
      z-index: 1;
    }

    .orbital-ring {
      position: absolute;
      width: 620px;
      height: 620px;
      border-radius: 50%;
      border: 2px solid var(--ring-line);
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      box-shadow: 0 0 40px rgba(22,119,242,0.06);
      z-index: 1;
    }

    .visual-scene {
      position: relative;
      width: min(820px, 100%);
      height: 720px;
      z-index: 2;
    }

    .scene-center {
      position: absolute;
      inset: 0;
      display: grid;
      place-items: center;
      pointer-events: none;
    }

    .lottie-center {
      width: 330px;
      height: 330px;
      display: grid;
      place-items: center;
    }

    .lottie-center lottie-player {
      width: 320px;
      height: 320px;
      display: block;
    }

    .stat-box {
      position: absolute;
      width: 255px;
      min-height: 180px;
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.45) 0%, rgba(255, 255, 255, 0.12) 100%);
      border: 1px solid rgba(255, 255, 255, 0.45);
      border-top: 1px solid rgba(255, 255, 255, 0.85);
      border-left: 1px solid rgba(255, 255, 255, 0.85);
      border-radius: 30px;
      padding: 24px 22px;
      box-shadow:
        0 20px 45px rgba(27, 38, 66, 0.07),
        inset 0 1px 2px rgba(255, 255, 255, 0.4);
      backdrop-filter: blur(28px) saturate(180%);
      -webkit-backdrop-filter: blur(28px) saturate(180%);
      transition: transform .4s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow .4s ease;
    }

    .stat-box:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 30px 60px rgba(22, 119, 242, 0.16);
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.55) 0%, rgba(255, 255, 255, 0.18) 100%);
    }

    .stat-box h4 {
      color: #21314d;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: .08em;
      text-transform: uppercase;
      line-height: 1.3;
      margin-bottom: 12px;
    }

    .stat-box p {
      color: #2b3b58;
      font-size: 13px;
      line-height: 1.45;
      font-weight: 600;
      margin-top: 10px;
    }

    /* TARJETA: MARCAS QUE DISTRIBUIMOS (carrusel animado) */
    .brands-box {
      top: 32px;
      left: 70px;
    }
    .brand-marquee {
      position: relative;
      overflow: hidden;
      margin: 4px 0 2px;
      -webkit-mask-image: linear-gradient(90deg, transparent 0, #000 14%, #000 86%, transparent 100%);
      mask-image: linear-gradient(90deg, transparent 0, #000 14%, #000 86%, transparent 100%);
    }
    .brand-track {
      display: flex;
      gap: 7px;
      width: max-content;
      animation: marquee 28s linear infinite;
      will-change: transform;
    }
    .brand-track span {
      flex: none;
      display: inline-flex;
      align-items: center;
      padding: 6px 12px;
      border-radius: 999px;
      background: rgba(22, 119, 242, 0.10);
      border: 1px solid rgba(22, 119, 242, 0.16);
      color: #14528f;
      font-size: 12.5px;
      font-weight: 700;
      letter-spacing: .01em;
      white-space: nowrap;
    }
    .brands-box:hover .brand-track {
      animation-play-state: paused;
    }
    @keyframes marquee {
      to { transform: translateX(-50%); }
    }

    /* TARJETA: LOGÍSTICA (carrito viajero) */
    .shipment-box {
      top: 80px;
      right: 50px;
    }
    .road-track {
      position: relative;
      width: 100%;
      height: 38px;
      margin: 12px 0 6px;
      overflow: hidden;
      background: rgba(22, 119, 242, 0.06);
      border-radius: 12px;
      border: 1px solid rgba(255,255,255,0.4);
    }
    .road-line {
      position: absolute;
      bottom: 6px;
      left: 0;
      width: 200%;
      height: 2px;
      background: linear-gradient(90deg, transparent 0%, var(--blue) 50%, transparent 100%);
      opacity: 0.3;
    }
    .animated-car {
      position: absolute;
      bottom: 5px;
      left: -40px;
      animation: driveCar 6s cubic-bezier(0.4, 0, 0.2, 1) infinite;
    }
    @keyframes driveCar {
      0% { left: -40px; transform: scaleX(0.9); }
      50% { transform: scaleX(1); }
      100% { left: 100%; transform: scaleX(0.9); }
    }

    /* TARJETA: FACTURACIÓN AL INSTANTE (factura animada) */
    .facturacion-box {
      bottom: 84px;
      left: 70px;
    }
    .invoice-anim {
      display: flex;
      align-items: center;
      gap: 14px;
      margin: 8px 0 4px;
    }
    .invoice-doc {
      position: relative;
      flex: none;
      width: 86px;
      height: 78px;
      background: rgba(255, 255, 255, 0.75);
      border: 1px solid rgba(22, 119, 242, 0.18);
      border-radius: 10px;
      padding: 11px 10px;
      overflow: hidden;
      box-shadow: 0 6px 16px rgba(27, 38, 66, 0.08);
    }
    .invoice-doc .row {
      height: 6px;
      border-radius: 3px;
      background: rgba(22, 119, 242, 0.18);
      margin-bottom: 8px;
      transform-origin: left;
      animation: rowFill 2.8s ease-in-out infinite;
    }
    .invoice-doc .row.short { width: 50%; animation-delay: .1s; }
    .invoice-doc .row.mid   { width: 78%; animation-delay: .3s; }
    .invoice-doc .row.long  { width: 92%; animation-delay: .5s; }
    @keyframes rowFill {
      0%, 100% { transform: scaleX(.35); opacity: .55; }
      40%, 70% { transform: scaleX(1); opacity: 1; }
    }
    .invoice-scan {
      position: absolute;
      left: 0;
      right: 0;
      top: -16px;
      height: 16px;
      background: linear-gradient(180deg, rgba(22,119,242,0) 0%, rgba(22,119,242,0.32) 50%, rgba(22,119,242,0) 100%);
      animation: scanInvoice 2.8s ease-in-out infinite;
    }
    @keyframes scanInvoice {
      0%   { top: -16px; opacity: 0; }
      12%  { opacity: 1; }
      80%  { opacity: 1; }
      100% { top: 78px; opacity: 0; }
    }
    .invoice-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 6px 11px;
      border-radius: 999px;
      background: rgba(21, 128, 61, 0.12);
      border: 1px solid rgba(21, 128, 61, 0.24);
      color: var(--success);
      font-size: 12px;
      font-weight: 700;
      white-space: nowrap;
      animation: badgePop 2.8s ease-in-out infinite;
    }
    .invoice-badge svg {
      width: 14px;
      height: 14px;
    }
    @keyframes badgePop {
      0%, 55%  { transform: scale(.8); opacity: .4; }
      70%      { transform: scale(1.14); opacity: 1; }
      80%, 100%{ transform: scale(1); opacity: 1; }
    }

    /* TARJETA: AUTOMATIZACIÓN IA (red de nodos) */
    .ai-box {
      bottom: 96px;
      right: 40px;
      width: 290px;
      min-height: 200px;
    }
    .ai-graph {
      width: 100%;
      height: 76px;
      margin-top: 10px;
      margin-bottom: 6px;
    }
    .ai-graph svg {
      width: 100%;
      height: 100%;
      display: block;
    }
    .ai-graph .line {
      stroke: #1677f2;
      stroke-width: 2.5;
      fill: none;
      stroke-dasharray: 6, 6;
      animation: dataFlow 4s linear infinite;
    }
    @keyframes dataFlow {
      to { stroke-dashoffset: -40; }
    }
    .ai-graph .node {
      fill: #1677f2;
      stroke: #ffffff;
      stroke-width: 2;
      transform-origin: center;
      animation: pulseNode 2s ease-in-out infinite alternate;
    }
    .ai-graph .node:nth-child(odd) {
      animation-delay: 0.5s;
    }
    @keyframes pulseNode {
      0% { r: 5; opacity: 0.8; }
      100% { r: 7.5; opacity: 1; filter: drop-shadow(0 0 4px #1677f2); }
    }

    /* Adaptables */
    @media (max-width: 1280px) {
      .visual-scene { height: 660px; }
      .orbital-ring { width: 540px; height: 540px; }
      .lottie-center { width: 280px; height: 280px; }
      .lottie-center lottie-player { width: 270px; height: 270px; }
      .stat-box { width: 230px; }
      .ai-box { width: 265px; }
    }

    @media (max-width: 1100px) {
      .visual-panel { padding: 30px 20px; }
      .visual-scene { transform: scale(.9); }
    }

    @media (max-width: 1024px) {
      .page { grid-template-columns: 1fr; }
      .visual-panel { display: none; }
      .auth-panel {
        min-height: 100vh;
        border-radius: 0;
        box-shadow: none;
        padding: 34px 22px;
      }
      .auth-title { font-size: 28px; }
      .brand { margin-bottom: 24px; }
      .brand img { width: 64px; margin-bottom: 7px; }
      .brand-name { font-size: 21px; }
      .brand-tag { font-size: 10px; letter-spacing: .2em; }
      .inputBx input { min-height: 54px; padding: 13px 18px; border-radius: 16px; }
      .btn-submit { min-height: 54px; font-size: 16px; }
    }

    @media (max-width: 520px) {
      .auth-panel { align-items: flex-start; padding-top: 36px; }
      .links-row { justify-content: center; text-align: center; }
      .auth-title { font-size: 26px; }
    }

    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { animation: none !important; transition: none !important; }
      .lottie-center lottie-player { display: none; }
      .invoice-doc .row { transform: scaleX(1); opacity: 1; }
      .invoice-scan { display: none; }
      .invoice-badge { transform: scale(1); opacity: 1; }
    }
  </style>
</head>

<body>
  <div class="page">
    <main class="auth-panel">
      <div class="auth-wrap">
        <div class="brand">
          <img src="{{ asset('images/fffff.png') }}" alt="Logotipo Soluciones Jureto">
          <div class="brand-name">SOLUCIONES<span>JURETO</span></div>
          <div class="brand-tag">Comercializadora · Importadora · Exportadora</div>
        </div>

        <h1 class="auth-title">Recuperar contraseña</h1>
        <p class="auth-subtitle">Te enviaremos un enlace para restablecer tu acceso.</p>

        @if (session('status'))
          <div class="alert success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
          <div class="alert error">
            @foreach ($errors->all() as $error)
              <div>{{ $error }}</div>
            @endforeach
          </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="form" id="resetForm" novalidate>
          @csrf

          <div class="field">
            <label class="field-label" for="email">Correo electrónico</label>
            <div class="inputBx @error('email') invalid @enderror">
              <input
                id="email"
                type="email"
                name="email"
                placeholder="correo@empresa.com"
                value="{{ old('email') }}"
                required
                autocomplete="email"
                autofocus
              >
            </div>
            @error('email')
              <div class="field-error">{{ $message }}</div>
            @enderror
          </div>

          <div class="links-row">
            <a class="auth-link" href="{{ route('login') }}">Volver al login</a>
            <a class="auth-link" href="{{ route('register') }}">Crear cuenta</a>
          </div>

          <button type="submit" class="btn-submit" id="btnReset" data-loading="false">
            <span>Enviar enlace de reinicio</span>
            <span class="btn-loader" aria-hidden="true"></span>
          </button>
        </form>
      </div>
    </main>

    <aside class="visual-panel" aria-hidden="true">
      <div class="bg-glow-blob blob-1"></div>
      <div class="bg-glow-blob blob-2"></div>

      <div class="orbital-ring"></div>

      <div class="visual-scene">
        <div class="scene-center">
          <div class="lottie-center">
            <lottie-player
              src="{{ asset('animations/shopping5.json') }}"
              background="transparent"
              speed="1"
              loop
              autoplay>
            </lottie-player>
          </div>
        </div>

        {{-- MARCAS QUE DISTRIBUIMOS (carrusel animado, se pausa al pasar el mouse) --}}
        <div class="stat-box brands-box">
          <h4>Marcas que distribuimos</h4>
          <div class="brand-marquee">
            <div class="brand-track">
              <span>BIC</span>
              <span>Baco</span>
              <span>Barrilito</span>
              <span>Pelikan</span>
              <span>Maped</span>
              <span>Pilot</span>
              <span>Scribe</span>
              <span>Norma</span>
              {{-- Duplicado para loop continuo --}}
              <span>BIC</span>
              <span>Baco</span>
              <span>Barrilito</span>
              <span>Pelikan</span>
              <span>Maped</span>
              <span>Pilot</span>
              <span>Scribe</span>
              <span>Norma</span>
            </div>
          </div>
          <p>Distribuidor de papelería y artículos de oficina.</p>
        </div>

        {{-- LOGÍSTICA --}}
        <div class="stat-box shipment-box">
          <h4>Logística</h4>
          <div class="road-track">
            <div class="road-line"></div>
            <div class="animated-car">
              <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="#1677f2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="1" y="3" width="15" height="13" rx="2" ry="2" />
                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8" />
                <circle cx="5.5" cy="18.5" r="2.5" fill="#f8fafc" />
                <circle cx="18.5" cy="18.5" r="2.5" fill="#f8fafc" />
              </svg>
            </div>
          </div>
          <p>Envíos a todo el país, rápidos y seguros.</p>
        </div>

        {{-- FACTURACIÓN AL INSTANTE (factura animada + sello Timbrado) --}}
        <div class="stat-box facturacion-box">
          <h4>Facturación al instante</h4>
          <div class="invoice-anim">
            <div class="invoice-doc">
              <div class="row short"></div>
              <div class="row long"></div>
              <div class="row mid"></div>
              <div class="row long"></div>
              <div class="invoice-scan"></div>
            </div>
            <div class="invoice-badge">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              Timbrado
            </div>
          </div>
          <p>Genera tu factura CFDI en segundos.</p>
        </div>

        {{-- AUTOMATIZACIÓN IA --}}
        <div class="stat-box ai-box">
          <h4>Automatización IA</h4>
          <div class="ai-graph">
            <svg viewBox="0 0 240 76" xmlns="http://www.w3.org/2000/svg">
              <path class="line" d="M22 44 L78 60 L120 38 L176 22 L214 38" />
              <path class="line" d="M22 22 L120 38 L176 58 L214 38" />
              <circle class="node" cx="22" cy="44" r="6"/>
              <circle class="node" cx="22" cy="22" r="6"/>
              <circle class="node" cx="78" cy="60" r="6"/>
              <circle class="node" cx="120" cy="38" r="7"/>
              <circle class="node" cx="176" cy="22" r="6"/>
              <circle class="node" cx="176" cy="58" r="6"/>
              <circle class="node" cx="214" cy="38" r="7"/>
            </svg>
          </div>
          <p>Cotizaciones y cierres de venta más rápidos.</p>
        </div>
      </div>
    </aside>
  </div>

  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

  <script>
    (function(){
      // ===== Loading del botón submit =====
      const form = document.getElementById('resetForm');
      const btnReset = document.getElementById('btnReset');
      if (form && btnReset) {
        form.addEventListener('submit', function(){
          btnReset.dataset.loading = 'true';
        });
      }
    })();
  </script>
</body>
</html>