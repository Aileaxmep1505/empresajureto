<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $item->name }} | Catálogo Jureto</title>

  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --bg: #ffffff;
      --panel: #f5f5f7;          /* gris galería Apple */
      --card: #ffffff;

      --ink-dark: #1d1d1f;       /* texto principal Apple */
      --ink: #494949;
      --muted: #6e6e73;          /* gris secundario Apple */
      --muted-light: #86868b;

      --line: #d2d2d7;           /* bordes Apple */
      --line-soft: #e8e8ed;

      --blue: #007aff;           /* azul Jureto */
      --blue-hover: #0a84ff;
      --blue-soft: #eff6ff;      /* fondo badge azul */

      --success: #15803d;
      --success-soft: #f0fdf4;
      --warning: #c2410c;
      --warning-soft: #fff7ed;
      --danger: #ef4444;
      --danger-soft: #fef2f2;

      --radius-lg: 18px;
      --radius-md: 12px;
    }

    * { box-sizing: border-box; }

    html { scroll-behavior: smooth; }

    body {
      margin: 0;
      background: var(--bg);
      color: var(--ink-dark);
      font-family: 'Quicksand', -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
      font-weight: 500;
      -webkit-font-smoothing: antialiased;
      text-rendering: optimizeLegibility;
    }

    /* ============ LOCAL NAV (aparece al hacer scroll, estilo Apple) ============ */
    .localnav {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 100;
      background: rgba(255,255,255,0.85);
      -webkit-backdrop-filter: saturate(180%) blur(20px);
      backdrop-filter: saturate(180%) blur(20px);
      border-bottom: 1px solid var(--line-soft);
      transform: translateY(-100%);
      transition: transform 0.4s cubic-bezier(0.28, 0.11, 0.32, 1);
    }
    .localnav.visible { transform: translateY(0); }

    .localnav-inner {
      max-width: 1100px;
      margin: 0 auto;
      padding: 0 22px;
      height: 52px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
    }
    .localnav-title {
      font-size: 19px;
      font-weight: 600;
      letter-spacing: 0.011em;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .localnav-meta {
      font-size: 14px;
      color: var(--ink-dark);
      display: flex;
      align-items: center;
      gap: 14px;
      white-space: nowrap;
    }
    .localnav-meta .nav-status {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-weight: 500;
    }
    .nav-status .dot {
      width: 7px; height: 7px; border-radius: 50%;
      background: var(--success);
    }
    .nav-status.status-warning .dot { background: var(--warning); }
    .nav-status.status-danger .dot { background: var(--danger); }

    /* ============ PAGE ============ */
    .page {
      max-width: 1100px;
      margin: 0 auto;
      padding: 40px 22px 0;
    }

    /* ============ HEADER / HERO ============ */
    .hero {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 24px;
      padding: 16px 0 36px;
    }
    .hero-eyebrow {
      color: var(--success);
      font-size: 17px;
      font-weight: 700;
      margin-bottom: 4px;
    }
    .hero-title {
      font-size: clamp(32px, 5vw, 48px);
      font-weight: 700;
      letter-spacing: -0.015em;
      line-height: 1.07;
      margin: 0 0 10px;
    }
    .hero-sub {
      font-size: 17px;
      color: var(--ink-dark);
      font-weight: 400;
    }
    .hero-sub .sep { color: var(--line); margin: 0 8px; }

    .hero-chips {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 10px;
      padding-top: 8px;
      flex-shrink: 0;
    }
    .chip {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--blue-soft);
      color: var(--blue);
      border-radius: 999px;
      padding: 9px 16px;
      font-size: 13px;
      font-weight: 700;
      white-space: nowrap;
    }
    .chip.status-ok { background: var(--success-soft); color: var(--success); }
    .chip.status-ok .dot { background: var(--success); }
    .chip .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--success); }
    .chip.status-warning { background: var(--warning-soft); color: var(--warning); }
    .chip.status-warning .dot { background: var(--warning); }
    .chip.status-danger { background: var(--danger-soft); color: var(--danger); }
    .chip.status-danger .dot { background: var(--danger); }
    .chip i { font-size: 14px; }

    /* línea de "envío" estilo Apple */
    .meta-line {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 8px;
      color: var(--ink-dark);
      font-size: 14px;
      padding: 14px 0;
      border-top: 1px solid var(--line-soft);
    }
    .meta-line i { font-size: 17px; }

    /* ============ LAYOUT 2 COLUMNAS ============ */
    .buy-grid {
      display: grid;
      grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
      gap: 56px;
      align-items: start;
      padding-bottom: 40px;
    }

    /* ---- Galería sticky (panel gris Apple) ---- */
    .gallery-col {
      position: sticky;
      top: 72px;
    }
    .gallery-panel {
      background: var(--panel);
      border-radius: var(--radius-lg);
      min-height: 460px;
      height: clamp(420px, 58vh, 560px);
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    .gallery-caption {
      position: absolute;
      top: 26px;
      left: 28px;
      right: 28px;
      color: var(--muted);
      font-size: 14px;
      line-height: 1.45;
      font-weight: 400;
      max-width: 420px;
      pointer-events: none;
    }
    .gallery-panel img {
      max-width: 78%;
      max-height: 72%;
      object-fit: contain;
      transition: opacity 0.35s ease;
    }
    .gallery-panel img.fading { opacity: 0; }
    .gallery-placeholder {
      color: #c7c7cc;
      font-size: 72px;
    }

    /* controles del carrusel estilo Apple */
    .gallery-controls {
      position: absolute;
      bottom: 22px;
      left: 0;
      right: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 18px;
    }
    .g-arrow {
      width: 36px; height: 36px;
      border-radius: 50%;
      border: none;
      background: rgba(210,210,215,0.55);
      color: #4a4a4f;
      font-size: 15px;
      display: grid;
      place-items: center;
      cursor: pointer;
      transition: background 0.2s ease;
    }
    .g-arrow:hover { background: rgba(199,199,204,0.85); }
    .g-dots {
      display: flex;
      gap: 9px;
      background: rgba(210,210,215,0.55);
      border-radius: 999px;
      padding: 10px 14px;
    }
    .g-dot {
      width: 8px; height: 8px;
      border-radius: 50%;
      border: none;
      padding: 0;
      background: rgba(0,0,0,0.25);
      cursor: pointer;
      transition: background 0.2s ease, transform 0.2s ease;
    }
    .g-dot.active { background: var(--ink-dark); transform: scale(1.1); }

    /* ---- Columna derecha (selección estilo Apple) ---- */
    .select-col { min-width: 0; }

    .select-section { padding: 26px 0 34px; }
    .select-section + .select-section { border-top: 0; }

    .select-heading {
      font-size: 24px;
      font-weight: 700;
      letter-spacing: -0.01em;
      line-height: 1.25;
      margin: 0 0 18px;
    }
    .select-heading span { color: var(--muted-light); font-weight: 700; }

    /* cards estilo selector de Apple */
    .option-card {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: var(--radius-md);
      padding: 18px 20px;
      margin-bottom: 14px;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .option-card:hover {
      border-color: var(--blue);
      box-shadow: 0 0 0 1px var(--blue);
    }
    .option-card.option-assigned {
      border-color: var(--blue);
      box-shadow: 0 0 0 1px var(--blue);
    }
    .option-card.option-assigned .option-label { color: var(--blue); }

    /* ---- Card de datos de entrega (asignación) ---- */
    .entrega-card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: var(--radius-md);
      padding: 20px 22px;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .entrega-card:hover {
      border-color: var(--blue);
      box-shadow: 0 0 0 1px var(--blue);
    }
    .entrega-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 6px;
    }
    .entrega-title {
      display: flex;
      align-items: center;
      gap: 9px;
      font-size: 14px;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      color: var(--ink-dark);
    }
    .entrega-badge {
      background: var(--blue-soft);
      color: var(--blue);
      border-radius: 999px;
      padding: 5px 13px;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }
    .entrega-row {
      display: flex;
      align-items: baseline;
      justify-content: space-between;
      gap: 16px;
      padding: 13px 0;
      border-bottom: 1px solid var(--line-soft);
      font-size: 15px;
    }
    .entrega-row:last-of-type { border-bottom: none; }
    .entrega-key { color: var(--muted-light); font-weight: 600; flex-shrink: 0; }
    .entrega-val { color: var(--ink-dark); font-weight: 700; text-align: right; word-break: break-word; }

    .firma-label {
      color: var(--muted-light);
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      margin: 18px 0 10px;
    }
    .firma-box {
      background: #fff;
      border: 1px solid var(--line-soft);
      border-radius: var(--radius-md);
      padding: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 120px;
    }
    .firma-box img {
      max-width: 100%;
      max-height: 160px;
      object-fit: contain;
    }
    .option-main {
      display: flex;
      align-items: center;
      gap: 14px;
      min-width: 0;
    }
    .option-icon {
      width: 38px; height: 38px;
      border-radius: 10px;
      background: var(--blue-soft);
      color: var(--blue);
      display: grid;
      place-items: center;
      font-size: 17px;
      flex-shrink: 0;
    }
    .option-label {
      font-size: 12px;
      font-weight: 600;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 2px;
    }
    .option-value {
      font-size: 17px;
      font-weight: 600;
      color: var(--ink-dark);
      word-break: break-word;
    }
    .option-side {
      text-align: right;
      font-size: 12px;
      color: var(--muted);
      line-height: 1.45;
      max-width: 175px;
      flex-shrink: 0;
    }

    /* badges tipo Apple (texto pequeño naranja "Nuevo") */
    .tag-new {
      color: var(--success);
      font-size: 12px;
      font-weight: 700;
      margin-bottom: 2px;
    }

    /* ============ FICHA TÉCNICA (grid label/valor como la imagen) ============ */
    .ficha-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      column-gap: 32px;
      row-gap: 34px;
      padding: 6px 4px 0;
    }
    .ficha-item { min-width: 0; }
    .ficha-item.full { grid-column: 1 / -1; }
    .f-label {
      color: var(--muted-light);
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      margin-bottom: 8px;
    }
    .f-value {
      color: var(--ink-dark);
      font-size: 20px;
      font-weight: 600;
      line-height: 1.35;
      word-break: break-word;
    }

    /* ============ REVEAL AL HACER SCROLL ============ */
    @media (prefers-reduced-motion: no-preference) {
      .scroll-reveal {
        opacity: 0;
        transform: translateY(18px);
        transition: opacity 0.55s cubic-bezier(0.25, 0.1, 0.25, 1),
                    transform 0.55s cubic-bezier(0.25, 0.1, 0.25, 1);
      }
      .scroll-reveal.in-view {
        opacity: 1;
        transform: none;
      }
    }

    /* ============ SECCIÓN FINAL CENTRADA (estilo Apple) ============ */
    .closing {
      background: var(--panel);
      margin-top: 64px;
      padding: 72px 22px 64px;
      text-align: center;
    }
    .closing-inner { max-width: 640px; margin: 0 auto; }
    .closing h2 {
      font-size: clamp(26px, 4.5vw, 40px);
      font-weight: 700;
      letter-spacing: -0.015em;
      line-height: 1.15;
      margin: 0 0 14px;
    }
    .closing p {
      color: var(--ink);
      font-size: 17px;
      line-height: 1.5;
      margin: 0 0 26px;
    }
    .closing-links {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 12px 28px;
      font-size: 17px;
    }
    .closing-links a {
      color: var(--blue);
      text-decoration: none;
      font-weight: 400;
      display: inline-flex;
      align-items: center;
      gap: 7px;
    }
    .closing-links a:hover { text-decoration: underline; }

    .footer {
      text-align: center;
      color: var(--muted-light);
      font-size: 12px;
      padding: 26px 22px 40px;
      background: var(--panel);
      border-top: 1px solid var(--line-soft);
    }

    /* ============ REVEAL ON LOAD ============ */
    @media (prefers-reduced-motion: no-preference) {
      .reveal {
        opacity: 0;
        transform: translateY(14px);
        animation: rise 0.6s cubic-bezier(0.25, 0.1, 0.25, 1) forwards;
      }
      .reveal:nth-child(2) { animation-delay: 0.06s; }
      .reveal:nth-child(3) { animation-delay: 0.12s; }
      @keyframes rise { to { opacity: 1; transform: none; } }
    }

    /* ============ RESPONSIVE ============ */
    @media (max-width: 980px) {
      .buy-grid { grid-template-columns: 1fr; gap: 36px; }
      .gallery-col { position: static; }
      .hero { flex-direction: column; }
      .hero-chips { flex-direction: row; align-items: center; flex-wrap: wrap; }
      .localnav-meta .nav-extra { display: none; }
    }
    @media (max-width: 560px) {
      .page { padding: 28px 16px 0; }
      .gallery-panel { height: 340px; min-height: 340px; }
      .gallery-caption { font-size: 12.5px; top: 18px; left: 18px; }
      .option-card { flex-direction: column; align-items: flex-start; gap: 10px; }
      .option-side { text-align: left; max-width: none; }
      .ficha-grid { column-gap: 20px; row-gap: 26px; }
      .f-value { font-size: 18px; }
    }

    @media print {
      .localnav, .gallery-controls { display: none !important; }
      .gallery-col { position: static; }
    }
  </style>
</head>
<body>
  @php
    $photos = collect([$item->photo, $item->photo_2 ?? null, $item->photo_3 ?? null])
      ->filter()
      ->map(fn($photo) => \Illuminate\Support\Str::startsWith($photo, ['http', '/storage']) ? $photo : asset('storage/'.$photo))
      ->values();

    $brand = $item->brand ?: ($item->category->name ?? 'JURETO');
    $model = $item->model ?: 'Sin modelo';
    $sku = $item->internal_code ?: $item->serial_number ?: $item->id;
    $gtin = $item->serial_number ?: $item->internal_code ?: $item->id;
    $stock = (int) $item->stock;
    $unit = $item->unit ?: 'pzas';
    $isConsumible = $item->type === 'consumible';

    $statusLabel = 'Disponible';
    $statusClass = 'status-ok';

    if ($item->type === 'activo_fijo') {
      $statusLabel = match($item->asset_status) {
        'asignado' => 'Asignado',
        'en_reparacion' => 'En reparación',
        'dado_de_baja' => 'Dado de baja',
        default => 'Disponible',
      };
      $statusClass = match($item->asset_status) {
        'asignado' => 'status-warning',
        'en_reparacion' => 'status-warning',
        'dado_de_baja' => 'status-danger',
        default => 'status-ok',
      };
    }

    if ($isConsumible && $stock <= (int) $item->stock_min) {
      $statusLabel = 'Bajo stock';
      $statusClass = 'status-warning';
    }

    $conditionLabel = $item->condition ? ucfirst(str_replace('_', ' ', $item->condition)) : null;

    // ===== Datos de asignación =====
    // Busca primero la asignación activa en la relación, luego en columnas del propio item.
    $isAssigned = $item->type === 'activo_fijo' && $item->asset_status === 'asignado';

    $assignment = null;
    foreach (['activeAssignment', 'currentAssignment', 'latestAssignment'] as $rel) {
      if (isset($item->{$rel})) { $assignment = $item->{$rel}; break; }
    }
    if (!$assignment && method_exists($item, 'assignments')) {
      $assignment = $item->assignments()->latest()->first();
    }

    $pick = function ($source, array $keys) {
      if (!$source) return null;
      foreach ($keys as $key) {
        $value = data_get($source, $key);
        if (!empty($value)) return $value;
      }
      return null;
    };

    // Igual que $pick pero descarta valores puramente numéricos (IDs no son nombres)
    $pickName = function ($source, array $keys) {
      if (!$source) return null;
      foreach ($keys as $key) {
        $value = data_get($source, $key);
        if (!empty($value) && !is_numeric($value)) return $value;
      }
      return null;
    };

    $assignedTo = $pickName($assignment, ['user.name', 'assignee.name', 'employee.name', 'assignedUser.name', 'assignee_name', 'recipient_name', 'employee_name', 'received_by', 'signed_by', 'assigned_to'])
      ?? $pickName($item, ['assigned_to', 'assigned_user_name', 'assignedUser.name', 'responsible.name']);

    // Si solo tenemos un ID numérico, lo resolvemos contra el modelo User
    if (!$assignedTo) {
      $assignedId = $pick($assignment, ['assigned_to', 'user_id', 'assignee_id', 'employee_id'])
        ?? $pick($item, ['assigned_to', 'assigned_user_id']);
      if ($assignedId && is_numeric($assignedId) && class_exists(\App\Models\User::class)) {
        $assignedTo = optional(\App\Models\User::find($assignedId))->name;
      }
    }

    $assignedAtRaw = $pick($assignment, ['assigned_at', 'assignment_date', 'created_at'])
      ?? $pick($item, ['assigned_at', 'assignment_date']);
    $assignedAt = $assignedAtRaw ? \Carbon\Carbon::parse($assignedAtRaw)->format('d/m/Y H:i') : null;

    // Detalles de entrega (para la sección Asignación)
    $aFolio     = $pick($assignment, ['folio', 'code', 'reference']);
    $aEmail     = $pick($assignment, ['email', 'assignee_email', 'recipient_email', 'user.email']);
    $aQty       = $pick($assignment, ['quantity', 'qty']);
    $aDelivers  = $pickName($assignment, ['delivered_by', 'who_delivers', 'deliverer_name', 'deliverer.name']);
    $aReceives  = $pickName($assignment, ['received_by', 'who_receives', 'recipient_name', 'receiver.name']);
    $aSignedBy  = $pickName($assignment, ['signed_by', 'signer_name', 'signer.name']);
    $aSignedAtRaw = $pick($assignment, ['signed_at', 'signature_date']);
    $aSignedAt  = $aSignedAtRaw ? \Carbon\Carbon::parse($aSignedAtRaw)->format('d/m/Y H:i') : null;

    $aSignature = $pick($assignment, ['signature', 'signature_path', 'signature_image']);
    if ($aSignature && !\Illuminate\Support\Str::startsWith($aSignature, ['data:', 'http', '/storage'])) {
      $aSignature = asset('storage/'.$aSignature);
    }
  @endphp

  {{-- ===== Local nav estilo Apple (aparece al hacer scroll) ===== --}}
  <nav class="localnav" id="localnav" aria-hidden="true">
    <div class="localnav-inner">
      <div class="localnav-title">{{ $item->name }}</div>
      <div class="localnav-meta">
        @if($isConsumible)
          <span class="nav-extra">{{ $stock }} {{ $unit }} en inventario</span>
        @elseif($isAssigned && $assignedTo)
          <span class="nav-extra">{{ $assignedTo }}</span>
        @elseif($item->location)
          <span class="nav-extra">{{ $item->location }}</span>
        @endif
        <span class="nav-status {{ $statusClass }}"><span class="dot"></span>{{ $statusLabel }}</span>
      </div>
    </div>
  </nav>

  <main class="page">

    {{-- ===== Hero ===== --}}
    <header class="hero">
      <div class="reveal">
        @if($conditionLabel === 'Nuevo')
          <div class="hero-eyebrow">Nuevo</div>
        @else
          <div class="hero-eyebrow" style="color: var(--muted);">{{ $brand }}</div>
        @endif
        <h1 class="hero-title">{{ $item->name }}</h1>
        <div class="hero-sub">
          Modelo {{ $model }}
          <span class="sep">|</span>
          {{ $item->category->name ?? ($isConsumible ? 'Consumible' : 'Activo fijo') }}
          <span class="sep">|</span>
          ID #{{ $item->id }}
        </div>
      </div>
      <div class="hero-chips reveal">
        <span class="chip {{ $statusClass }}"><span class="dot"></span>{{ $statusLabel }}</span>
        @if($item->type === 'activo_fijo' && $item->internal_code)
          <span class="chip"><i class="bi bi-tag"></i> Código {{ $item->internal_code }}</span>
        @elseif($isConsumible)
          <span class="chip"><i class="bi bi-upc-scan"></i> SKU {{ $sku }}</span>
        @endif
      </div>
    </header>

    <div class="meta-line">
      <i class="bi bi-buildings"></i>
      Catálogo interno · JURETO S.A. DE C.V.
    </div>

    {{-- ===== Grid de compra estilo Apple ===== --}}
    <section class="buy-grid">

      {{-- Galería sticky --}}
      <div class="gallery-col reveal">
        <div class="gallery-panel" id="galleryPanel">
          <div class="gallery-caption">
            {{ $item->notes ?: ($isConsumible ? 'Insumo registrado en el inventario de Jureto.' : 'Activo registrado en el inventario de Jureto.') }}
          </div>

          @if($photos->isNotEmpty())
            <img src="{{ $photos[0] }}" alt="{{ $item->name }}" id="mainPhoto">
          @else
            <div class="gallery-placeholder"><i class="bi bi-image"></i></div>
          @endif

          @if($photos->count() > 1)
            <div class="gallery-controls">
              <button type="button" class="g-arrow" id="prevBtn" aria-label="Imagen anterior"><i class="bi bi-chevron-left"></i></button>
              <div class="g-dots" id="gDots">
                @foreach($photos as $index => $photo)
                  <button type="button" class="g-dot {{ $index === 0 ? 'active' : '' }}" data-index="{{ $index }}" aria-label="Imagen {{ $index + 1 }}"></button>
                @endforeach
              </div>
              <button type="button" class="g-arrow" id="nextBtn" aria-label="Imagen siguiente"><i class="bi bi-chevron-right"></i></button>
            </div>
          @endif
        </div>
      </div>

      {{-- Columna de información estilo selector Apple --}}
      <div class="select-col">

        {{-- Estado --}}
        <div class="select-section">
          <h2 class="select-heading">Estado. <span>{{ $isConsumible ? 'Inventario en tiempo real.' : 'Situación actual del activo.' }}</span></h2>

          <div class="option-card">
            <div class="option-main">
              <span class="option-icon"><i class="bi bi-check-circle"></i></span>
              <div>
                <div class="option-label">Estado</div>
                <div class="option-value">{{ $statusLabel }}</div>
              </div>
            </div>
            <div class="option-side">{{ $isConsumible ? 'Nivel de existencias del insumo.' : 'Estado operativo registrado.' }}</div>
          </div>

          @if($isAssigned)
            <div class="option-card option-assigned">
              <div class="option-main">
                <span class="option-icon"><i class="bi bi-person"></i></span>
                <div>
                  <div class="option-label">Asignado a</div>
                  <div class="option-value">{{ $assignedTo ?: 'Sin registro de responsable' }}</div>
                </div>
              </div>
              <div class="option-side">
                @if($assignedAt)
                  Desde el {{ $assignedAt }}.
                @else
                  Resguardo activo.
                @endif
              </div>
            </div>
          @endif

          @if($isConsumible)
            <div class="option-card">
              <div class="option-main">
                <span class="option-icon"><i class="bi bi-bar-chart"></i></span>
                <div>
                  <div class="option-label">Stock actual</div>
                  <div class="option-value">{{ $stock }} {{ $unit }}</div>
                </div>
              </div>
              <div class="option-side">Mín {{ (int) $item->stock_min }} {{ $unit }} · Máx {{ (int) $item->stock_max }} {{ $unit }}</div>
            </div>
          @elseif($conditionLabel)
            <div class="option-card">
              <div class="option-main">
                <span class="option-icon"><i class="bi bi-clipboard-check"></i></span>
                <div>
                  @if($conditionLabel === 'Nuevo')<div class="tag-new">Nuevo</div>@endif
                  <div class="option-label">Condición</div>
                  <div class="option-value">{{ $conditionLabel }}</div>
                </div>
              </div>
              <div class="option-side">Condición física registrada en la última revisión.</div>
            </div>
          @endif
        </div>

        {{-- Asignación: datos de entrega --}}
        @if($isAssigned && $assignment)
          <div class="select-section">
            <h2 class="select-heading">Asignación. <span>Datos de entrega.</span></h2>

            <div class="entrega-card">
              <div class="entrega-head">
                <div class="entrega-title"><i class="bi bi-person-vcard"></i> Datos de entrega</div>
                <span class="entrega-badge">Activa</span>
              </div>

              @if($aFolio)<div class="entrega-row"><span class="entrega-key">Folio</span><span class="entrega-val">{{ $aFolio }}</span></div>@endif
              @if($assignedTo)<div class="entrega-row"><span class="entrega-key">Asignado a</span><span class="entrega-val">{{ $assignedTo }}</span></div>@endif
              @if($aEmail)<div class="entrega-row"><span class="entrega-key">Correo</span><span class="entrega-val">{{ $aEmail }}</span></div>@endif
              @if($assignedAt)<div class="entrega-row"><span class="entrega-key">Fecha de asignación</span><span class="entrega-val">{{ $assignedAt }}</span></div>@endif
              @if($aQty)<div class="entrega-row"><span class="entrega-key">Cantidad</span><span class="entrega-val">{{ $aQty }}</span></div>@endif
              @if($aDelivers)<div class="entrega-row"><span class="entrega-key">Quién entrega</span><span class="entrega-val">{{ $aDelivers }}</span></div>@endif
              @if($aReceives)<div class="entrega-row"><span class="entrega-key">Quién recibe</span><span class="entrega-val">{{ $aReceives }}</span></div>@endif
              @if($aSignedBy)<div class="entrega-row"><span class="entrega-key">Firmado por</span><span class="entrega-val">{{ $aSignedBy }}</span></div>@endif
              @if($aSignedAt)<div class="entrega-row"><span class="entrega-key">Fecha de firma</span><span class="entrega-val">{{ $aSignedAt }}</span></div>@endif

              @if($aSignature)
                <div class="firma-label">Firma</div>
                <div class="firma-box">
                  <img src="{{ $aSignature }}" alt="Firma de recepción">
                </div>
              @endif
            </div>
          </div>
        @endif

        {{-- Ubicación --}}
        @if($item->location || $item->department)
          <div class="select-section">
            <h2 class="select-heading">Ubicación. <span>Dónde encontrarlo.</span></h2>

            @if($item->location)
              <div class="option-card">
                <div class="option-main">
                  <span class="option-icon"><i class="bi bi-geo-alt"></i></span>
                  <div>
                    <div class="option-label">Ubicación física</div>
                    <div class="option-value">{{ $item->location }}</div>
                  </div>
                </div>
                <div class="option-side">Ubicación asignada del {{ $isConsumible ? 'insumo' : 'activo' }}.</div>
              </div>
            @endif

            @if($item->department)
              <div class="option-card">
                <div class="option-main">
                  <span class="option-icon"><i class="bi bi-building"></i></span>
                  <div>
                    <div class="option-label">Departamento</div>
                    <div class="option-value">{{ $item->department }}</div>
                  </div>
                </div>
                <div class="option-side">Área responsable del resguardo.</div>
              </div>
            @endif
          </div>
        @endif

        {{-- Identificación --}}
        <div class="select-section">
          <h2 class="select-heading">Identificación. <span>Códigos y registro.</span></h2>

          @if($item->type === 'activo_fijo')
            @if($item->internal_code)
              <div class="option-card">
                <div class="option-main">
                  <span class="option-icon"><i class="bi bi-tag"></i></span>
                  <div>
                    <div class="option-label">Código interno</div>
                    <div class="option-value">{{ $item->internal_code }}</div>
                  </div>
                </div>
                <div class="option-side">Identificador interno Jureto.</div>
              </div>
            @endif
            @if($item->serial_number)
              <div class="option-card">
                <div class="option-main">
                  <span class="option-icon"><i class="bi bi-hash"></i></span>
                  <div>
                    <div class="option-label">No. de serie</div>
                    <div class="option-value">{{ $item->serial_number }}</div>
                  </div>
                </div>
                <div class="option-side">Serie del fabricante.</div>
              </div>
            @endif
          @else
            <div class="option-card">
              <div class="option-main">
                <span class="option-icon"><i class="bi bi-upc-scan"></i></span>
                <div>
                  <div class="option-label">Código de barras</div>
                  <div class="option-value">{{ $gtin }}</div>
                </div>
              </div>
              <div class="option-side">Para escáner de recepción y picking.</div>
            </div>
          @endif
        </div>

        {{-- Ficha técnica (grid label/valor) --}}
        <div class="select-section">
          <h2 class="select-heading">Ficha técnica. <span>Especificaciones completas.</span></h2>

          <div class="ficha-grid">
            @if($brand)
              <div class="ficha-item"><div class="f-label">Marca</div><div class="f-value">{{ $brand }}</div></div>
            @endif
            @if($model && $model !== 'Sin modelo')
              <div class="ficha-item"><div class="f-label">Modelo</div><div class="f-value">{{ $model }}</div></div>
            @endif
            <div class="ficha-item"><div class="f-label">ID</div><div class="f-value">#{{ $item->id }}</div></div>
            @if($item->type)
              <div class="ficha-item"><div class="f-label">Tipo</div><div class="f-value">{{ $isConsumible ? 'Consumible / Insumo' : 'Activo fijo' }}</div></div>
            @endif
            @if($item->category)
              <div class="ficha-item"><div class="f-label">Categoría</div><div class="f-value">{{ $item->category->name }}</div></div>
            @endif

            @if($item->type === 'activo_fijo')
              @if($item->internal_code)
                <div class="ficha-item"><div class="f-label">Código interno</div><div class="f-value">{{ $item->internal_code }}</div></div>
              @endif
              @if($item->serial_number)
                <div class="ficha-item"><div class="f-label">No. serie</div><div class="f-value">{{ $item->serial_number }}</div></div>
              @endif
              @if($item->asset_status)
                <div class="ficha-item"><div class="f-label">Estado</div><div class="f-value">{{ $statusLabel }}</div></div>
              @endif
              @if($isAssigned && $assignedTo)
                <div class="ficha-item"><div class="f-label">Asignado a</div><div class="f-value">{{ $assignedTo }}</div></div>
              @endif
              @if($isAssigned && $assignedAt)
                <div class="ficha-item"><div class="f-label">Fecha de asignación</div><div class="f-value">{{ $assignedAt }}</div></div>
              @endif
              @if($conditionLabel)
                <div class="ficha-item"><div class="f-label">Condición</div><div class="f-value">{{ $conditionLabel }}</div></div>
              @endif
              @if($item->location)
                <div class="ficha-item"><div class="f-label">Ubicación</div><div class="f-value">{{ $item->location }}</div></div>
              @endif
              @if($item->department)
                <div class="ficha-item"><div class="f-label">Departamento</div><div class="f-value">{{ $item->department }}</div></div>
              @endif
              @if($item->supplier)
                <div class="ficha-item"><div class="f-label">Proveedor</div><div class="f-value">{{ $item->supplier }}</div></div>
              @endif
              @if($item->warranty_until)
                <div class="ficha-item"><div class="f-label">Garantía hasta</div><div class="f-value">{{ \Carbon\Carbon::parse($item->warranty_until)->format('d/m/Y') }}</div></div>
              @endif
              @if($item->processor)
                <div class="ficha-item"><div class="f-label">Procesador</div><div class="f-value">{{ $item->processor }}</div></div>
              @endif
              @if($item->ram)
                <div class="ficha-item"><div class="f-label">RAM</div><div class="f-value">{{ $item->ram }}</div></div>
              @endif
              @if($item->storage)
                <div class="ficha-item"><div class="f-label">Almacenamiento</div><div class="f-value">{{ $item->storage }}</div></div>
              @endif
              @if($item->operating_system)
                <div class="ficha-item"><div class="f-label">Sistema operativo</div><div class="f-value">{{ $item->operating_system }}</div></div>
              @endif
              @if($item->mac_address)
                <div class="ficha-item"><div class="f-label">MAC Address</div><div class="f-value">{{ $item->mac_address }}</div></div>
              @endif
            @endif

            @if($isConsumible)
              @if($sku)
                <div class="ficha-item"><div class="f-label">SKU</div><div class="f-value">{{ $sku }}</div></div>
              @endif
              @if($gtin)
                <div class="ficha-item"><div class="f-label">Código de barras</div><div class="f-value">{{ $gtin }}</div></div>
              @endif
              @if($unit)
                <div class="ficha-item"><div class="f-label">Unidad</div><div class="f-value">{{ $unit }}</div></div>
              @endif
              <div class="ficha-item"><div class="f-label">Stock actual</div><div class="f-value">{{ $stock }} {{ $unit }}</div></div>
              <div class="ficha-item"><div class="f-label">Stock mínimo</div><div class="f-value">{{ (int) $item->stock_min }} {{ $unit }}</div></div>
              <div class="ficha-item"><div class="f-label">Stock máximo</div><div class="f-value">{{ (int) $item->stock_max }} {{ $unit }}</div></div>
              @if($item->department)
                <div class="ficha-item"><div class="f-label">Departamento</div><div class="f-value">{{ $item->department }}</div></div>
              @endif
              @if($item->supplier)
                <div class="ficha-item"><div class="f-label">Proveedor</div><div class="f-value">{{ $item->supplier }}</div></div>
              @endif
              @if($item->warranty_until)
                <div class="ficha-item"><div class="f-label">Garantía hasta</div><div class="f-value">{{ \Carbon\Carbon::parse($item->warranty_until)->format('d/m/Y') }}</div></div>
              @endif
            @endif

            @if($item->notes)
              <div class="ficha-item full"><div class="f-label">Descripción</div><div class="f-value">{{ $item->notes }}</div></div>
            @endif
          </div>
        </div>

      </div>
    </section>
  </main>

  {{-- ===== Cierre centrado estilo Apple ===== --}}
  <section class="closing">
    <div class="closing-inner">
      <h2>¿Tienes dudas sobre este {{ $isConsumible ? 'insumo' : 'activo' }}?</h2>
      <p>El equipo de JURETO S.A. DE C.V. puede ayudarte con la asignación, el resguardo o cualquier aclaración sobre el inventario.</p>
      <div class="closing-links">
        <a href="mailto:contacto@jureto.com.mx"><i class="bi bi-envelope"></i> contacto@jureto.com.mx</a>
        <a href="tel:7224485191"><i class="bi bi-telephone"></i> 722 448 5191</a>
        <a href="https://www.jureto.com.mx" target="_blank" rel="noopener"><i class="bi bi-globe"></i> jureto.com.mx</a>
      </div>
    </div>
  </section>

  <footer class="footer">
    JURETO S.A. DE C.V. · Catálogo interno de activos e inventario
  </footer>

  <script>
    // ===== Local nav al hacer scroll (estilo Apple) =====
    const localnav = document.getElementById('localnav');
    const hero = document.querySelector('.hero');

    if ('IntersectionObserver' in window && hero) {
      const navObserver = new IntersectionObserver(([entry]) => {
        localnav.classList.toggle('visible', !entry.isIntersecting);
        localnav.setAttribute('aria-hidden', entry.isIntersecting ? 'true' : 'false');
      }, { rootMargin: '-60px 0px 0px 0px' });
      navObserver.observe(hero);
    }

    // ===== Carrusel de galería =====
    const photos = @json($photos);
    const mainPhoto = document.getElementById('mainPhoto');
    const dots = document.querySelectorAll('.g-dot');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    let current = 0;

    function goTo(index) {
      if (!mainPhoto || !photos.length) return;
      current = (index + photos.length) % photos.length;
      mainPhoto.classList.add('fading');
      setTimeout(() => {
        mainPhoto.src = photos[current];
        mainPhoto.classList.remove('fading');
      }, 180);
      dots.forEach((d, i) => d.classList.toggle('active', i === current));
    }

    dots.forEach((dot) => dot.addEventListener('click', () => goTo(parseInt(dot.dataset.index, 10))));
    if (prevBtn) prevBtn.addEventListener('click', () => goTo(current - 1));
    if (nextBtn) nextBtn.addEventListener('click', () => goTo(current + 1));

    // Navegación con teclado
    document.addEventListener('keydown', (e) => {
      if (photos.length < 2) return;
      if (e.key === 'ArrowLeft') goTo(current - 1);
      if (e.key === 'ArrowRight') goTo(current + 1);
    });

    // ===== Reveal de secciones al hacer scroll =====
    if ('IntersectionObserver' in window) {
      const sections = document.querySelectorAll('.select-section');
      sections.forEach((s) => s.classList.add('scroll-reveal'));

      const sectionObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('in-view');
            sectionObserver.unobserve(entry.target);
          }
        });
      }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

      sections.forEach((s) => sectionObserver.observe(s));
    }
  </script>
</body>
</html>