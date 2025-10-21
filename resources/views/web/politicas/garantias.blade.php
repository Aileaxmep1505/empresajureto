@extends('layouts.web')
@section('title','Garantías y Devoluciones')

@section('content')
<style>
/* ======= NAMESPACE AISLADO ======= */
#policy{
  --ink:#1b2550; --muted:#6b7280; --line:#e7ecf5; --bg1:#f7fbff; --bg2:#fff6ef;
  --chip:#f4f7ff; --chip-ink:#11316a; --brand:#6ea8fe; --ok:#16a34a; --warn:#eab308; --danger:#ef4444;
}
#policy{ position:relative; width:100%; padding: clamp(22px,3vw,34px) 16px 60px; color:var(--ink); }
#policy::before{
  content:""; position:fixed; inset:0; z-index:-1; pointer-events:none;
  background:
    radial-gradient(900px 520px at 100% -10%, var(--bg1) 0%, transparent 55%),
    radial-gradient(1200px 700px at -10% 0%, var(--bg2) 0%, transparent 55%),
    #ffffff;
}
#policy .wrap{ max-width:1100px; margin:0 auto; }

/* Header */
#policy .hero{ text-align:center; margin-bottom: clamp(18px,3vw,28px); }
#policy .title{ font-size: clamp(28px,5vw,50px); line-height:1.05; margin:0; letter-spacing:-.02em; }
#policy .sub{ color:var(--muted); max-width:860px; margin:8px auto 0; }

/* Pills nav */
#policy .pills{ display:flex; flex-wrap:wrap; gap:14px; justify-content:center; margin: clamp(18px,2.6vw,24px) 0 8px; }
#policy .pill{
  display:inline-flex; align-items:center; gap:10px; padding:12px 16px; border:1px solid var(--line);
  border-radius:14px; background:#fff; color:#1b2550; text-decoration:none; font-weight:700;
  box-shadow: 0 8px 22px rgba(2,8,23,.06); transition: transform .15s ease, box-shadow .15s ease;
}
#policy .pill:hover{ transform: translateY(-1px); box-shadow: 0 12px 26px rgba(2,8,23,.10); }
#policy .pill svg{ width:18px; height:18px }

/* Section */
#policy section{ padding-top: clamp(18px,2vw,24px) }
#policy h2{ font-size: clamp(20px,3.4vw,27px); margin: 10px 0 8px; letter-spacing:-.01em; }
#policy h3{ font-size: clamp(18px,2.6vw,22px); margin: 16px 0 6px; }
#policy p{ color:#2b3357; }
#policy .note{ background:#fff; border:1px dashed var(--line); border-radius:12px; padding:12px 14px; color:#374151; }
#policy ul{ padding-left: 22px; margin: 8px 0; }
#policy li{ margin:6px 0 }

/* Chips info */
#policy .chips{ display:flex; gap:10px; flex-wrap:wrap; margin-top:12px }
#policy .chip{ background:var(--chip); color:var(--chip-ink); border:1px solid var(--line); border-radius:999px; padding:8px 12px; font-weight:600; }

/* CTA */
#policy .cta{ margin-top: 26px; display:flex; gap:12px; flex-wrap:wrap; align-items:center }
#policy .btn{
  appearance:none; border:none; text-decoration:none !important;
  background:#9ec5fe; color:#fff; font-weight:800; letter-spacing:.01em;
  padding:12px 18px; border-radius:14px; display:inline-flex; align-items:center; gap:8px; cursor:pointer;
  box-shadow: 0 10px 24px rgba(110,168,254,.35); transition: transform .15s ease, box-shadow .15s ease, background .15s ease, color .15s ease;
}
#policy .btn:hover, #policy .btn:focus{
  background:#fff; color:#0b1530; box-shadow: 0 0 0 10px rgba(110,168,254,.22); transform: translateY(-1px);
}
#policy .muted{ color:var(--muted); font-size:14px }

/* Divider */
#policy .hr{ height:1px; background:linear-gradient(90deg, #fff, var(--line), #fff); margin: 18px 0; }

/* Anchor offset (sticky headers safe) */
#policy .anchor{ position:relative; scroll-margin-top:110px; }
</style>

<div id="policy">
  <div class="wrap">

    <!-- HERO -->
    <header class="hero">
      <h1 class="title">Garantías y Devoluciones</h1>
      <p class="sub">
        Aquí encontrarás cómo solicitar una devolución, los requisitos para hacer válida una garantía
        y los tiempos estimados de respuesta. Hemos simplificado el lenguaje para que todo sea claro y directo.
      </p>

      <!-- NAV PILLS -->
      <nav class="pills">
        <a class="pill" href="#alcance">
          <!-- box icon -->
          <svg viewBox="0 0 24 24" fill="none"><path d="M4 7l8 4 8-4M4 7v10l8 4 8-4V7" stroke="#253B80" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Alcance
        </a>
        <a class="pill" href="#devoluciones">
          <svg viewBox="0 0 24 24" fill="none"><path d="M3 12h14m0 0l-3-3m3 3l-3 3M8 7V5a3 3 0 013-3h7" stroke="#253B80" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Devoluciones
        </a>
        <a class="pill" href="#pasos">
          <svg viewBox="0 0 24 24" fill="none"><path d="M7 8h10M7 12h10M7 16h6" stroke="#253B80" stroke-width="1.7" stroke-linecap="round"/><path d="M4 8h.01M4 12h.01M4 16h.01" stroke="#253B80" stroke-width="1.7" stroke-linecap="round"/></svg>
          Pasos a seguir
        </a>
        <a class="pill" href="#garantias">
          <svg viewBox="0 0 24 24" fill="none"><path d="M12 3l7 4v6c0 4-3 7-7 8-4-1-7-4-7-8V7l7-4z" stroke="#253B80" stroke-width="1.7" stroke-linejoin="round"/></svg>
          Garantías
        </a>
        <a class="pill" href="#excepciones">
          <svg viewBox="0 0 24 24" fill="none"><path d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="#253B80" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Exclusiones
        </a>
        <a class="pill" href="#tiempos">
          <svg viewBox="0 0 24 24" fill="none"><path d="M12 6v6l3 3M12 22a10 10 0 100-20 10 10 0 000 20z" stroke="#253B80" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Tiempos y reembolsos
        </a>
      </nav>

      <div class="chips">
        <span class="chip">⏱️ Devolución hasta 30 días hábiles</span>
        <span class="chip">📦 Empaque original y accesorios</span>
        <span class="chip">🛡️ Garantía sujeta a dictamen del fabricante</span>
      </div>
    </header>

    <div class="hr"></div>

    <!-- ALCANCE -->
    <section id="alcance" class="anchor">
      <h2>1) Alcance de la política</h2>
      <p>
        Esta guía aplica a compras realizadas en nuestro sitio para productos de papelería, consumibles, equipo de oficina y accesorios.
        Los procedimientos pueden variar según la marca o tipo de producto cuando el fabricante así lo establece.
      </p>
      <ul>
        <li>Las devoluciones se aceptan durante <strong>30 días hábiles</strong> desde la recepción del pedido.</li>
        <li>Las garantías se gestionan por <em>defecto de fabricación</em> dentro del plazo otorgado por cada marca.</li>
        <li>El trámite puede requerir número de serie, pruebas de falla y empaque original.</li>
      </ul>
    </section>

    <!-- DEVOLUCIONES -->
    <section id="devoluciones" class="anchor">
      <h2>2) Devoluciones</h2>
      <p>Puedes solicitar una devolución cuando se presente uno de estos escenarios:</p>
      <ul>
        <li>El artículo recibido <strong>no corresponde</strong> al solicitado.</li>
        <li>El producto <strong>no coincide</strong> con las especificaciones publicadas.</li>
        <li>Se envió <strong>unidades de más</strong> por error.</li>
        <li>El paquete llega íntegro y <strong>sin señales de apertura</strong> o daño externo.</li>
      </ul>
      <p class="note"><strong>Importante:</strong> utiliza el mismo embalaje o uno equivalente que proteja el contenido. No coloques cintas o etiquetas directamente sobre la caja del fabricante.</p>
    </section>

    <!-- PASOS -->
    <section id="pasos" class="anchor">
      <h2>3) ¿Cómo iniciar el trámite?</h2>
      <h3>Devolución</h3>
      <ol>
        <li>Contáctanos dentro de los <strong>15 días hábiles</strong> posteriores a la entrega para reportar el caso.</li>
        <li>Te enviaremos el <strong>folio y formato</strong> con instrucciones de envío.</li>
        <li>Empaca el producto <strong>con todos sus accesorios</strong>, manuales y obsequios incluidos.</li>
        <li>Remite el paquete con la guía indicada. Conserva tu comprobante.</li>
      </ol>

      <h3>Garantía</h3>
      <ol>
        <li>Ten a la mano la <strong>factura</strong>, número de serie y una descripción clara de la falla.</li>
        <li>Para consumibles (tinta/tóner) adjunta prueba de impresión o carta descriptiva de la anomalía.</li>
        <li>Algunas marcas requieren <strong>póliza original</strong> o diagnóstico en centro de servicio.</li>
      </ol>
    </section>

    <!-- GARANTÍAS -->
    <section id="garantias" class="anchor">
      <h2>4) Cobertura de Garantías</h2>
      <p>Tramitamos la garantía cuando exista un defecto de fabricación que afecte el funcionamiento normal.</p>
      <ul>
        <li><strong>Equipo y componentes</strong>: hasta 12 meses según marca (algunos gabinetes 3 meses, fuentes 6 meses).</li>
        <li><strong>Consumibles y papelería</strong>: 3 meses; algunas marcas como HP pueden otorgar hasta 12 meses.</li>
        <li><strong>Mobiliario de oficina</strong>: 12 meses (año comercial de 360 días).</li>
      </ul>
      <p>El fabricante determina el resultado del dictamen (reparación, sustitución o nota de crédito cuando aplique).</p>
    </section>

    <!-- EXCEPCIONES -->
    <section id="excepciones" class="anchor">
      <h2>5) Exclusiones y casos no cubiertos</h2>
      <ul>
        <li>Daño físico, golpes, humedad, quemaduras o intervención por terceros no autorizados.</li>
        <li>Etiquetas o sellos alterados/removidos; códigos de barras ilegibles.</li>
        <li>Consumibles <strong>caducados</strong> o con menos del 50% de contenido.</li>
        <li>Rendimiento de tinta/tóner (varía según uso). Para pantallas LCD, se aplican políticas de píxeles por marca.</li>
        <li>Software por licencia/código: <strong>no es retornable</strong> una vez entregado el código.</li>
      </ul>
      <p class="note">
        Algunas categorías (p.ej. ciertos accesorios, refacciones o productos de remate) pueden tener políticas especiales.
        Te confirmaremos el criterio antes de iniciar el trámite.
      </p>
    </section>

    <!-- TIEMPOS -->
    <section id="tiempos" class="anchor">
      <h2>6) Tiempos de respuesta y reembolsos</h2>
      <ul>
        <li><strong>Cancelación del pedido:</strong> puedes solicitarla en cualquier momento. Si ya está pagado, el reembolso se procesa en
          un máximo de <strong>10 días hábiles</strong>.</li>
        <li><strong>Garantía express:</strong> para ciertos productos se emite dictamen acelerado sujeto a revisión del proveedor.</li>
        <li><strong>Garantía estándar:</strong> dentro de los primeros 30 días, si procede, se resuelve en ~72h (excepto equipos que requieren centro de servicio).</li>
        <li><strong>Posterior a 30 días:</strong> la resolución puede tomar entre <strong>7 y 15 días hábiles</strong> según proveedor y categoría.</li>
      </ul>
      <div class="chips">
        <span class="chip">🧾 Reembolso vía nota de crédito o medio original</span>
        <span class="chip">📍 Tiempos sujetos al dictamen del fabricante</span>
      </div>

      <div class="cta">
        <a class="btn" href="{{ url('/contacto') }}">Abrir solicitud</a>
        <span class="muted">¿Dudas? Escríbenos y te ayudamos a elegir el proceso correcto.</span>
      </div>
    </section>

  </div>
</div>
@endsection
