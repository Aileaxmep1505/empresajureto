@extends('layouts.app')

@section('content')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  :root {
    --bg: #f9fafb;
    --card: #ffffff;
    --ink: #333333;
    --muted: #888888;
    --line: #ebebeb;
    --blue: #007aff;
    --blue-soft: #e6f0ff;
    --success: #15803d;
    --success-soft: #e6ffe6;
    --danger: #ff4a4a;
    --danger-soft: #ffebeb;
    --warning: #a16207;
    --warning-soft: #fff7d6;
    --shadow: 0 4px 12px rgba(0,0,0,0.02);
    --shadow-hover: 0 12px 28px rgba(0,0,0,0.05);
  }

  * {
    box-sizing: border-box;
  }

  .quote-page {
    min-height: 100vh;
    background: var(--bg);
    font-family: 'Quicksand', sans-serif;
    color: var(--ink);
    padding: 36px 20px 54px;
  }

  .quote-wrap {
    max-width: 1180px;
    margin: 0 auto;
  }

  .quote-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
    margin-bottom: 28px;
  }

  .back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #777;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 34px;
    transition: .2s ease;
  }

  .back-link:hover {
    color: var(--blue);
  }

  .quote-code {
    font-size: 12px;
    color: var(--muted);
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    margin-bottom: 8px;
  }

  .quote-title {
    margin: 0;
    color: #111;
    font-size: 30px;
    font-weight: 700;
    letter-spacing: -.03em;
    line-height: 1.1;
  }

  .quote-subtitle {
    margin: 10px 0 0;
    color: var(--muted);
    font-size: 15px;
    font-weight: 500;
  }

  .quote-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
    padding-top: 68px;
  }

  .btn {
    appearance: none;
    border: 1px solid transparent;
    min-height: 44px;
    padding: 0 16px;
    border-radius: 14px;
    background: transparent;
    font-family: 'Quicksand', sans-serif;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: .2s ease;
    white-space: nowrap;
  }

  .btn:active {
    transform: scale(.98);
  }

  .btn-primary {
    background: var(--blue);
    color: #fff;
    border-color: var(--blue);
    box-shadow: 0 8px 18px rgba(0,122,255,.12);
  }

  .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px rgba(0,122,255,.18);
  }

  .btn-outline {
    background: #fff;
    color: var(--blue);
    border-color: var(--blue);
  }

  .btn-outline:hover {
    background: var(--blue-soft);
    transform: translateY(-1px);
  }

  .btn-ghost {
    background: #fff;
    color: #666;
    border-color: var(--line);
  }

  .btn-ghost:hover {
    background: #f9fafb;
    transform: translateY(-1px);
  }

  .btn-success {
    background: #fff;
    color: var(--success);
    border-color: rgba(21,128,61,.35);
  }

  .btn-success:hover {
    background: var(--success-soft);
    transform: translateY(-1px);
  }

  .btn-danger {
    background: #fff;
    color: var(--danger);
    border-color: rgba(255,74,74,.35);
  }

  .btn-danger:hover {
    background: var(--danger-soft);
    transform: translateY(-1px);
  }

  .btn-warning {
    background: #fff;
    color: var(--warning);
    border-color: rgba(161,98,7,.35);
  }

  .btn-warning:hover {
    background: var(--warning-soft);
    transform: translateY(-1px);
  }

  .btn-soft {
    background: var(--blue-soft);
    color: var(--blue);
    border-color: transparent;
  }

  .btn-soft:hover {
    background: #dceaff;
    transform: translateY(-1px);
  }

  .icon {
    width: 16px;
    height: 16px;
    display: inline-block;
    flex: 0 0 auto;
  }

  .notice {
    display: flex;
    align-items: center;
    gap: 12px;
    border-radius: 14px;
    border: 1px solid #facc15;
    background: #fffbeb;
    color: #945d00;
    padding: 15px 18px;
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 28px;
  }

  .notice-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: #c69200;
    flex: 0 0 auto;
  }

  .alert {
    border-radius: 14px;
    padding: 15px 18px;
    margin-bottom: 18px;
    font-weight: 700;
    font-size: 14px;
    border: 1px solid var(--line);
  }

  .alert-success {
    color: var(--success);
    background: var(--success-soft);
  }

  .alert-danger {
    color: var(--danger);
    background: var(--danger-soft);
  }

  .summary-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 1px;
    background: var(--line);
    border: 1px solid var(--line);
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 38px;
    box-shadow: var(--shadow);
  }

  .summary-cell {
    background: var(--card);
    padding: 18px 14px 16px;
    text-align: center;
  }

  .summary-value {
    font-size: 19px;
    font-weight: 700;
    color: #111;
    line-height: 1.1;
  }

  .summary-label {
    color: var(--muted);
    font-size: 12px;
    font-weight: 600;
    margin-top: 9px;
  }

  .text-success {
    color: var(--success);
  }

  .text-danger {
    color: var(--danger);
  }

  .text-blue {
    color: var(--blue);
  }

  .tabs {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 24px;
  }

  .tab-btn {
    border: 1px solid transparent;
    background: #f3f4f6;
    color: #777;
    border-radius: 14px;
    padding: 12px 16px;
    font-size: 14px;
    font-weight: 700;
    font-family: 'Quicksand', sans-serif;
    cursor: pointer;
    transition: .2s ease;
  }

  .tab-btn:hover {
    background: #eef1f5;
  }

  .tab-btn.active {
    background: var(--blue);
    color: #fff;
  }

  .items-list {
    display: grid;
    gap: 12px;
  }

  .item-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-left: 3px solid var(--line);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: .2s ease;
  }

  .item-card:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-hover);
  }

  .item-card.status-exact {
    border-left-color: #22c55e;
  }

  .item-card.status-similar {
    border-left-color: var(--blue);
  }

  .item-card.status-not-found {
    border-left-color: var(--danger);
  }

  .item-card.status-review {
    border-left-color: #f59e0b;
  }

  .item-main {
    display: grid;
    grid-template-columns: 46px minmax(0, 1fr) auto auto;
    align-items: center;
    gap: 16px;
    padding: 18px 24px;
    cursor: pointer;
    transition: .2s ease;
  }

  .item-main:hover {
    background: #fcfcfd;
  }

  .item-index {
    color: #777;
    font-size: 12px;
    font-weight: 700;
    text-align: center;
  }

  .item-name {
    margin: 0;
    color: #111;
    font-size: 16px;
    font-weight: 700;
    line-height: 1.35;
  }

  .item-meta {
    color: var(--muted);
    font-size: 13px;
    margin-top: 6px;
    font-weight: 500;
  }

  .item-statuses {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-end;
  }

  .badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 28px;
    padding: 6px 13px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    line-height: 1;
    white-space: nowrap;
  }

  .badge-success {
    background: var(--success-soft);
    color: var(--success);
  }

  .badge-danger {
    background: var(--danger-soft);
    color: var(--danger);
  }

  .badge-info {
    background: var(--blue-soft);
    color: var(--blue);
  }

  .badge-warning {
    background: var(--warning-soft);
    color: var(--warning);
  }

  .badge-muted {
    background: #f3f4f6;
    color: #777;
  }

  .money-row {
    display: flex;
    align-items: center;
    gap: 24px;
    font-size: 13px;
    color: var(--muted);
    white-space: nowrap;
  }

  .money-row strong {
    color: #111;
    font-weight: 700;
  }

  .chevron {
    width: 18px;
    height: 18px;
    color: var(--muted);
    transition: .2s ease;
  }

  .item-card.is-open .chevron {
    transform: rotate(180deg);
  }

  .item-details {
    display: none;
    border-top: 1px solid var(--line);
    background: rgba(249,250,251,.6);
    padding: 22px 28px 24px;
  }

  .item-card.is-open .item-details {
    display: block;
  }

  .details-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 18px;
  }

  .detail-section {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,.015);
  }

  .detail-label {
    margin: 0 0 12px;
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
  }

  .catalog-card,
  .external-card {
    border: 1px solid var(--line);
    border-radius: 14px;
    background: #fff;
    padding: 15px;
    transition: .2s ease;
  }

  .catalog-card:hover,
  .external-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(0,0,0,.035);
  }

  .catalog-card.primary {
    border-color: rgba(0,122,255,.28);
    background: #fbfdff;
  }

  .catalog-top,
  .external-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
  }

  .catalog-title,
  .external-title {
    color: #111;
    font-size: 14px;
    font-weight: 700;
    line-height: 1.45;
    margin: 0;
  }

  .catalog-meta,
  .external-meta {
    color: var(--muted);
    font-size: 12px;
    line-height: 1.55;
    margin-top: 6px;
  }

  .catalog-price,
  .external-price {
    color: var(--blue);
    font-size: 13px;
    font-weight: 700;
    margin-top: 7px;
  }

  .card-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 14px;
  }

  .small-btn {
    min-height: 36px;
    padding: 0 13px;
    border-radius: 11px;
    font-size: 13px;
  }

  .item-actions {
    display: flex;
    align-items: center;
    gap: 9px;
    flex-wrap: wrap;
    margin-top: 4px;
  }

  .item-actions .delete-action {
    margin-left: auto;
  }

  .price-form {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin-top: 16px;
  }

  .field {
    display: grid;
    gap: 7px;
  }

  .field label {
    font-size: 11px;
    color: var(--muted);
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
  }

  .input {
    width: 100%;
    height: 40px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: #fff;
    color: var(--ink);
    padding: 0 12px;
    outline: none;
    font-family: 'Quicksand', sans-serif;
    font-size: 13px;
    font-weight: 600;
    transition: .2s ease;
  }

  .input:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .empty-note {
    color: var(--muted);
    font-size: 13px;
    line-height: 1.6;
  }

  .footer-actions {
    border-top: 1px solid var(--line);
    margin-top: 40px;
    padding-top: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
  }

  .internal-link {
    color: #777;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    font-size: 14px;
    font-weight: 700;
    transition: .2s ease;
  }

  .internal-link:hover {
    color: var(--blue);
  }

  .hidden-by-filter {
    display: none;
  }

  @media (max-width: 960px) {
    .quote-top {
      flex-direction: column;
    }

    .quote-actions {
      justify-content: flex-start;
      padding-top: 0;
    }

    .summary-grid {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .item-main {
      grid-template-columns: 36px minmax(0, 1fr) auto;
    }

    .money-row {
      grid-column: 2 / -1;
      justify-content: flex-start;
      gap: 14px;
      flex-wrap: wrap;
    }

    .price-form {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 640px) {
    .quote-page {
      padding: 24px 14px 42px;
    }

    .summary-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .item-main {
      padding: 16px;
      gap: 10px;
    }

    .item-statuses {
      grid-column: 2 / -1;
      justify-content: flex-start;
    }

    .money-row {
      font-size: 12px;
    }

    .item-details {
      padding: 16px;
    }

    .price-form {
      grid-template-columns: 1fr;
    }

    .btn {
      width: 100%;
    }

    .tabs .tab-btn {
      flex: 1;
    }

    .footer-actions {
      align-items: stretch;
    }
  }
</style>

@php
  $items = $propuestaComercial->items ?? collect();

  $exactCount = 0;
  $similarCount = 0;
  $notFoundCount = 0;

  foreach ($items as $rowItem) {
      $selectedMatch = $rowItem->matches->firstWhere('seleccionado', true) ?: $rowItem->matches->sortByDesc('score')->first();
      $score = (float) ($rowItem->match_score ?: optional($selectedMatch)->score);

      if ($rowItem->productoSeleccionado && $score >= 85) {
          $exactCount++;
      } elseif ($rowItem->productoSeleccionado || $rowItem->matches->count()) {
          $similarCount++;
      } else {
          $notFoundCount++;
      }
  }

  $subtotalSale = (float) $items->sum('subtotal');
  $subtotalCost = (float) $items->sum(fn($i) => ((float) $i->costo_unitario) * ((float) ($i->cantidad_cotizada ?: 0)));
  $profit = $subtotalSale - $subtotalCost;
  $margin = $subtotalCost > 0 ? round(($profit / $subtotalCost) * 100) : 0;

  $fmtMoney = fn($n) => '$' . number_format((float) $n, 0);
@endphp

<div class="quote-page">
  <div class="quote-wrap">
    <div class="quote-top">
      <div>
        <a href="{{ route('propuestas-comerciales.index') }}" class="back-link">
          <span>←</span>
          <span>Volver</span>
        </a>

        <div class="quote-code">
          {{ $propuestaComercial->folio ?: ('TEOA' . str_pad((string) $propuestaComercial->id, 8, '0', STR_PAD_LEFT)) }}
        </div>

        <h1 class="quote-title">
          {{ $propuestaComercial->titulo ?: ('COT-' . strtoupper(substr(md5($propuestaComercial->id . $propuestaComercial->created_at), 0, 8))) }}
        </h1>

        <p class="quote-subtitle">
          {{ $items->count() }} partidas analizadas por IA
        </p>
      </div>

      <div class="quote-actions">
        <a href="{{ route('propuestas-comerciales.index') }}" class="btn btn-ghost">
          <span>＋</span>
          Agregar
        </a>

        <form method="POST" action="{{ route('propuestas-comerciales.update-pricing', $propuestaComercial) }}">
          @csrf
          <input type="hidden" name="porcentaje_utilidad" value="{{ $propuestaComercial->porcentaje_utilidad }}">
          <input type="hidden" name="porcentaje_descuento" value="{{ $propuestaComercial->porcentaje_descuento }}">
          <input type="hidden" name="porcentaje_impuesto" value="{{ $propuestaComercial->porcentaje_impuesto }}">
          <button class="btn btn-ghost" type="submit">
            <span>▣</span>
            Guardar
          </button>
        </form>

        <a href="{{ route('propuestas-comerciales.export.word', $propuestaComercial) }}" class="btn btn-primary">
          <span>▣</span>
          Aprobar
        </a>
      </div>
    </div>

    @if(session('status'))
      <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($notFoundCount > 0)
      <div class="notice">
        <span class="notice-dot"></span>
        <span>
          <strong>{{ $notFoundCount }} partidas</strong> no encontradas en catálogo — usa “Buscar en internet” en cada una para encontrar alternativas.
        </span>
      </div>
    @endif

    <div class="summary-grid">
      <div class="summary-cell">
        <div class="summary-value text-success">{{ $exactCount }}</div>
        <div class="summary-label">Exactos</div>
      </div>

      <div class="summary-cell">
        <div class="summary-value text-blue">{{ $similarCount }}</div>
        <div class="summary-label">Similares</div>
      </div>

      <div class="summary-cell">
        <div class="summary-value text-danger">{{ $notFoundCount }}</div>
        <div class="summary-label">No encontrados</div>
      </div>

      <div class="summary-cell">
        <div class="summary-value">{{ $fmtMoney($subtotalSale) }}</div>
        <div class="summary-label">Subtotal venta</div>
      </div>

      <div class="summary-cell">
        <div class="summary-value text-success">{{ $fmtMoney($profit) }}</div>
        <div class="summary-label">Utilidad</div>
      </div>

      <div class="summary-cell">
        <div class="summary-value">{{ $margin }}%</div>
        <div class="summary-label">Margen</div>
      </div>
    </div>

    <div class="tabs">
      <button class="tab-btn active" type="button" data-filter="all">
        Todos {{ $items->count() }}
      </button>
      <button class="tab-btn" type="button" data-filter="exact">
        Exactos {{ $exactCount }}
      </button>
      <button class="tab-btn" type="button" data-filter="not-found">
        No encontrados {{ $notFoundCount }}
      </button>
    </div>

    <div class="items-list">
      @forelse($items as $item)
        @php
          $selectedMatch = $item->matches->firstWhere('seleccionado', true) ?: $item->matches->sortByDesc('score')->first();
          $score = (float) ($item->match_score ?: optional($selectedMatch)->score);
          $isExact = $item->productoSeleccionado && $score >= 85;
          $isSimilar = ($item->productoSeleccionado || $item->matches->count()) && !$isExact;
          $isNotFound = !$item->productoSeleccionado && !$item->matches->count();

          $statusKey = $isExact ? 'exact' : ($isSimilar ? 'similar' : 'not-found');
          $statusClass = $isExact ? 'status-exact' : ($isSimilar ? 'status-similar' : 'status-not-found');
          $statusLabel = $isExact ? 'Coincidencia exacta' : ($isSimilar ? 'Coincidencia similar' : 'No encontrado');
          $badgeClass = $isExact ? 'badge-success' : ($isSimilar ? 'badge-info' : 'badge-danger');

          $qty = (float) ($item->cantidad_cotizada ?: $item->cantidad_maxima ?: $item->cantidad_minima ?: 1);
          $cost = (float) ($item->costo_unitario ?: optional(optional($selectedMatch)->product)->cost ?: 0);
          $price = (float) ($item->precio_unitario ?: optional(optional($selectedMatch)->product)->price ?: 0);
          $subtotal = (float) ($item->subtotal ?: ($price * $qty));
        @endphp

        <div class="item-card {{ $statusClass }}" data-status="{{ $statusKey }}">
          <div class="item-main" role="button" tabindex="0" onclick="toggleQuoteItem(this)">
            <div class="item-index">{{ $loop->iteration }}</div>

            <div>
              <h3 class="item-name">{{ $item->descripcion_original ?: 'Producto sin descripción' }}</h3>
              <div class="item-meta">
                {{ number_format($qty, 0) }} {{ $item->unidad_solicitada ?: 'pz' }}
                @if($item->productoSeleccionado && $item->productoSeleccionado->brand)
                  · {{ $item->productoSeleccionado->brand }}
                @endif
              </div>
            </div>

            <div class="item-statuses">
              <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
            </div>

            <div class="money-row">
              <span>Costo <strong>{{ $fmtMoney($cost) }}</strong></span>
              <span>Precio <strong>{{ $fmtMoney($price) }}</strong></span>
              <span>Subtotal <strong>{{ $fmtMoney($subtotal) }}</strong></span>
            </div>

            <svg class="chevron" viewBox="0 0 24 24" fill="none">
              <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>

          <div class="item-details">
            <div class="details-grid">
              @if($item->matches && $item->matches->count())
                <div class="detail-section">
                  <p class="detail-label">Coincidencias en catálogo ({{ $item->matches->count() }})</p>

                  <div style="display:grid; gap:10px;">
                    @foreach($item->matches->sortBy('rank') as $match)
                      @php
                        $product = $match->product;
                        $matchCost = (float) ($product->cost ?? $product->costo ?? $cost);
                        $matchPrice = (float) ($product->price ?? $product->precio ?? $price);
                      @endphp

                      <div class="catalog-card {{ $loop->first ? 'primary' : '' }}">
                        <div class="catalog-top">
                          <div>
                            <p class="catalog-title">
                              {{ $product->name ?? ('Producto #' . $match->product_id) }}
                            </p>
                            <div class="catalog-meta">
                              SKU: {{ $product->sku ?? '—' }}
                              @if($product && !empty($product->brand))
                                · {{ $product->brand }}
                              @endif
                              @if($product && isset($product->stock))
                                · Stock: {{ $product->stock }}
                              @endif
                              · {{ number_format((float) $match->score, 0) }}%
                            </div>

                            <div class="catalog-price">
                              {{ $fmtMoney($matchPrice) }}/u
                              <span style="color:var(--muted); font-weight:600;">
                                (costo {{ $fmtMoney($matchCost) }})
                              </span>
                            </div>
                          </div>

                          @if($loop->first)
                            <span class="badge badge-info">Principal</span>
                          @else
                            <span class="badge badge-muted">Opción {{ $loop->iteration }}</span>
                          @endif
                        </div>

                        <div class="card-actions">
                          <form method="POST" action="{{ route('propuesta-comercial-items.matches.select', [$item, $match]) }}">
                            @csrf
                            <button class="btn btn-outline small-btn" type="submit">
                              ✓ Usar esta
                            </button>
                          </form>
                        </div>
                      </div>
                    @endforeach
                  </div>
                </div>
              @elseif($item->productoSeleccionado)
                <div class="detail-section">
                  <p class="detail-label">Coincidencia en catálogo</p>
                  <p class="catalog-title">{{ $item->productoSeleccionado->name }}</p>
                  <div class="catalog-meta">
                    SKU: {{ $item->productoSeleccionado->sku ?? '—' }}
                    @if($item->productoSeleccionado->brand)
                      · {{ $item->productoSeleccionado->brand }}
                    @endif
                    @if($score > 0)
                      · {{ number_format($score, 0) }}%
                    @endif
                  </div>
                </div>
              @endif

              @if($item->externalMatches && $item->externalMatches->count())
                <div class="detail-section">
                  <p class="detail-label">Opciones de internet</p>

                  <div style="display:grid; gap:10px;">
                    @foreach($item->externalMatches->sortBy('rank') as $external)
                      <div class="external-card">
                        <div class="external-top">
                          <div>
                            <p class="external-title">{{ $external->title }}</p>

                            <div class="external-meta">
                              {{ $external->source ?? 'Internet' }}
                              @if($external->seller)
                                · {{ $external->seller }}
                              @endif
                              · Score {{ number_format((float) $external->score, 0) }}%
                            </div>

                            <div class="external-price">
                              @if($external->price)
                                {{ $fmtMoney($external->price) }}/u
                              @else
                                Precio por validar
                              @endif
                            </div>
                          </div>

                          <span class="badge badge-info">{{ $external->source ?? 'Web' }}</span>
                        </div>

                        <div class="card-actions">
                          <a href="{{ $external->url }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline small-btn">
                            ↗ Ver referencia
                          </a>
                        </div>
                      </div>
                    @endforeach
                  </div>
                </div>
              @endif

              @if(!$item->matches->count() && !$item->externalMatches->count())
                <div class="detail-section">
                  <p class="detail-label">Sin resultados</p>
                  <p class="empty-note">
                    Esta partida todavía no tiene coincidencias ni referencias externas. Usa “Buscar en internet”.
                  </p>
                </div>
              @endif

              <div class="detail-section">
                <p class="detail-label">Acciones</p>

                <div class="item-actions">
                  <button type="button" class="btn btn-ghost small-btn" onclick="event.stopPropagation(); togglePriceForm({{ $item->id }})">
                    ✎ Editar
                  </button>

                  <form method="POST" action="{{ route('propuesta-comercial-items.suggest', $item) }}">
                    @csrf
                    <button class="btn btn-soft small-btn" type="submit">
                      ◎ Buscar en internet
                    </button>
                  </form>

                  <form method="POST" action="{{ route('propuesta-comercial-items.suggest', $item) }}">
                    @csrf
                    <button class="btn btn-warning small-btn" type="submit">
                      ◉ Revisión
                    </button>
                  </form>

                  @if($selectedMatch)
                    <form method="POST" action="{{ route('propuesta-comercial-items.matches.select', [$item, $selectedMatch]) }}">
                      @csrf
                      <button class="btn btn-success small-btn" type="submit">
                        ✓ Aceptar
                      </button>
                    </form>
                  @else
                    <button class="btn btn-success small-btn" type="button" disabled style="opacity:.45; cursor:not-allowed;">
                      ✓ Aceptar
                    </button>
                  @endif

                  <button class="btn btn-danger small-btn" type="button">
                    × Rechazar
                  </button>
                </div>

                <form id="price-form-{{ $item->id }}" method="POST" action="{{ route('propuesta-comercial-items.price', $item) }}" class="price-form" style="display:none;">
                  @csrf

                  <div class="field">
                    <label>Cantidad</label>
                    <input class="input" type="number" step="0.01" name="cantidad_cotizada" value="{{ $qty }}">
                  </div>

                  <div class="field">
                    <label>Costo unit.</label>
                    <input class="input" type="number" step="0.01" name="costo_unitario" value="{{ $cost }}">
                  </div>

                  <div class="field">
                    <label>Margen %</label>
                    <input class="input" type="number" step="0.01" name="porcentaje_utilidad" value="{{ $propuestaComercial->porcentaje_utilidad ?: 25 }}">
                  </div>

                  <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn btn-primary" type="submit">
                      Guardar precio
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      @empty
        <div class="item-card">
          <div class="item-main">
            <div></div>
            <div>
              <h3 class="item-name">Sin partidas</h3>
              <div class="item-meta">Esta cotización aún no tiene productos analizados.</div>
            </div>
          </div>
        </div>
      @endforelse
    </div>

    <div class="footer-actions">
      <a href="{{ route('propuestas-comerciales.export.word', $propuestaComercial) }}" class="internal-link">
        ▤ Ver cotización interna
      </a>

      <a href="{{ route('propuestas-comerciales.export.word', $propuestaComercial) }}" class="btn btn-primary">
        ▣ Aprobar y generar cotizaciones
      </a>
    </div>
  </div>
</div>

<script>
  function toggleQuoteItem(row) {
    const card = row.closest('.item-card');
    if (!card) return;
    card.classList.toggle('is-open');
  }

  function togglePriceForm(id) {
    const form = document.getElementById('price-form-' + id);
    if (!form) return;
    form.style.display = form.style.display === 'none' || form.style.display === '' ? 'grid' : 'none';
  }

  document.querySelectorAll('.tab-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach((b) => b.classList.remove('active'));
      btn.classList.add('active');

      const filter = btn.dataset.filter;

      document.querySelectorAll('.item-card[data-status]').forEach((card) => {
        const status = card.dataset.status;
        const show =
          filter === 'all' ||
          filter === status ||
          (filter === 'not-found' && status === 'not-found');

        card.classList.toggle('hidden-by-filter', !show);
      });
    });
  });

  document.querySelectorAll('.item-main').forEach((row) => {
    row.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        toggleQuoteItem(row);
      }
    });
  });
</script>
@endsection