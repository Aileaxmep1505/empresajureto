@extends('layouts.app')

@section('content')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  .jureto-dashboard-page {
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

    min-height: 100vh;
    background: var(--bg);
    color: var(--ink);
    font-family: 'Quicksand', sans-serif;
    padding: 34px 0 64px;
  }

  .jureto-dashboard-page,
  .jureto-dashboard-page * {
    box-sizing: border-box;
  }

  .jureto-dashboard-page .dash-wrap {
    width: 90vw;
    max-width: 1600px;
    margin: 0 auto;
  }

  .jureto-dashboard-page .dash-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
    margin-bottom: 48px;
  }

  .jureto-dashboard-page .eyebrow {
    margin: 0 0 12px;
    color: #777;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .16em;
    text-transform: uppercase;
  }

  .jureto-dashboard-page .dash-title {
    margin: 0;
    color: #111;
    font-size: 36px;
    line-height: 1.05;
    font-weight: 700;
    letter-spacing: -.04em;
  }

  .jureto-dashboard-page .dash-subtitle {
    margin: 10px 0 0;
    color: var(--muted);
    font-size: 15px;
    font-weight: 500;
  }

  .jureto-dashboard-page .btn {
    appearance: none;
    border: 1px solid transparent;
    min-height: 50px;
    padding: 0 22px;
    border-radius: 14px;
    background: transparent;
    color: var(--ink);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    font-family: 'Quicksand', sans-serif;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: .22s ease;
    white-space: nowrap;
  }

  .jureto-dashboard-page .btn:active {
    transform: scale(.98);
  }

  .jureto-dashboard-page .btn-primary {
    background: var(--blue);
    border-color: var(--blue);
    color: #fff;
    box-shadow: 0 10px 24px rgba(0,122,255,.14);
  }

  .jureto-dashboard-page .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 34px rgba(0,122,255,.20);
  }

  .jureto-dashboard-page .btn-ghost {
    background: transparent;
    color: #777;
    border-color: transparent;
  }

  .jureto-dashboard-page .btn-ghost:hover {
    background: #f3f4f6;
    color: #111;
  }

  .jureto-dashboard-page .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 52px;
  }

  .jureto-dashboard-page .stat-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 18px;
    padding: 28px 26px 26px;
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
    transition: .24s ease;
    min-height: 145px;
  }

  .jureto-dashboard-page .stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 18px 40px rgba(0,0,0,.05);
  }

  .jureto-dashboard-page .stat-card.is-blue {
    background: var(--blue);
    border-color: var(--blue);
    color: #fff;
    box-shadow: 0 16px 34px rgba(0,122,255,.16);
  }

  .jureto-dashboard-page .stat-label {
    margin: 0 0 16px;
    color: var(--muted);
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .16em;
    text-transform: uppercase;
  }

  .jureto-dashboard-page .stat-card.is-blue .stat-label {
    color: rgba(255,255,255,.76);
  }

  .jureto-dashboard-page .stat-value {
    margin: 0;
    color: #111;
    font-size: 30px;
    line-height: 1;
    font-weight: 700;
    letter-spacing: -.04em;
  }

  .jureto-dashboard-page .stat-card.is-blue .stat-value {
    color: #fff;
  }

  .jureto-dashboard-page .stat-caption {
    margin-top: 10px;
    color: var(--muted);
    font-size: 14px;
    font-weight: 500;
  }

  .jureto-dashboard-page .stat-card.is-blue .stat-caption {
    color: rgba(255,255,255,.72);
  }

  .jureto-dashboard-page .section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 20px;
  }

  .jureto-dashboard-page .section-title {
    margin: 0;
    color: #111;
    font-size: 20px;
    font-weight: 700;
    letter-spacing: -.02em;
  }

  .jureto-dashboard-page .view-all {
    color: #777;
    text-decoration: none;
    font-size: 15px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: .2s ease;
  }

  .jureto-dashboard-page .view-all:hover {
    color: var(--blue);
    transform: translateX(2px);
  }

  .jureto-dashboard-page .recent-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
    margin-bottom: 32px;
  }

  .jureto-dashboard-page .quote-row {
    display: grid;
    grid-template-columns: minmax(150px, 190px) minmax(0, 1fr) auto auto auto;
    align-items: center;
    gap: 18px;
    min-height: 88px;
    padding: 18px 30px;
    text-decoration: none;
    color: inherit;
    border-bottom: 1px solid var(--line);
    transition: .22s ease;
  }

  .jureto-dashboard-page .quote-row:last-child {
    border-bottom: 0;
  }

  .jureto-dashboard-page .quote-row:hover {
    background: #fcfcfd;
  }

  .jureto-dashboard-page .quote-folio {
    color: #111;
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 8px;
  }

  .jureto-dashboard-page .quote-date {
    color: var(--muted);
    font-size: 13px;
    font-weight: 500;
  }

  .jureto-dashboard-page .quote-code {
    color: #111;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
  }

  .jureto-dashboard-page .quote-note {
    color: var(--muted);
    font-size: 13px;
    font-weight: 500;
    max-width: 520px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .jureto-dashboard-page .badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 28px;
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
  }

  .jureto-dashboard-page .badge-draft,
  .jureto-dashboard-page .badge-pending {
    background: #f3f4f6;
    color: #555;
  }

  .jureto-dashboard-page .badge-approved,
  .jureto-dashboard-page .badge-aprobada,
  .jureto-dashboard-page .badge-priced,
  .jureto-dashboard-page .badge-accepted {
    background: var(--success-soft);
    color: var(--success);
  }

  .jureto-dashboard-page .badge-rejected,
  .jureto-dashboard-page .badge-error {
    background: var(--danger-soft);
    color: var(--danger);
  }

  .jureto-dashboard-page .badge-matched,
  .jureto-dashboard-page .badge-processing {
    background: var(--blue-soft);
    color: var(--blue);
  }

  .jureto-dashboard-page .quote-money {
    text-align: right;
    min-width: 96px;
  }

  .jureto-dashboard-page .quote-total {
    color: #111;
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 5px;
  }

  .jureto-dashboard-page .quote-margin {
    color: var(--muted);
    font-size: 13px;
    font-weight: 600;
  }

  .jureto-dashboard-page .quote-arrow {
    color: #888;
    font-size: 24px;
    line-height: 1;
    transition: .2s ease;
  }

  .jureto-dashboard-page .quote-row:hover .quote-arrow {
    color: var(--blue);
    transform: translateX(4px);
  }

  .jureto-dashboard-page .empty-state {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 20px;
    padding: 54px 24px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
  }

  .jureto-dashboard-page .empty-title {
    margin: 0;
    color: #111;
    font-size: 22px;
    font-weight: 700;
  }

  .jureto-dashboard-page .empty-text {
    margin: 10px auto 22px;
    color: var(--muted);
    font-size: 15px;
    line-height: 1.7;
    max-width: 520px;
  }

  .jureto-dashboard-page .foot-metrics {
    display: flex;
    align-items: center;
    gap: 28px;
    flex-wrap: wrap;
    color: var(--muted);
    font-size: 15px;
    font-weight: 500;
  }

  .jureto-dashboard-page .foot-metrics strong {
    color: #111;
    font-weight: 700;
  }

  .jureto-dashboard-page .pagination-wrap {
    margin-top: 28px;
  }

  @media (max-width: 1100px) {
    .jureto-dashboard-page .dash-wrap {
      width: 94vw;
    }

    .jureto-dashboard-page .stats-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .jureto-dashboard-page .quote-row {
      grid-template-columns: minmax(130px, 170px) minmax(0, 1fr) auto;
    }

    .jureto-dashboard-page .quote-money {
      grid-column: 2 / 3;
      text-align: left;
    }

    .jureto-dashboard-page .quote-arrow {
      grid-column: 3 / 4;
      grid-row: 1 / 3;
    }
  }

  @media (max-width: 720px) {
    .jureto-dashboard-page {
      padding: 24px 0 46px;
    }

    .jureto-dashboard-page .dash-wrap {
      width: calc(100vw - 24px);
    }

    .jureto-dashboard-page .dash-top {
      flex-direction: column;
      margin-bottom: 32px;
    }

    .jureto-dashboard-page .dash-title {
      font-size: 34px;
    }

    .jureto-dashboard-page .btn {
      width: 100%;
    }

    .jureto-dashboard-page .stats-grid {
      grid-template-columns: 1fr;
      gap: 14px;
      margin-bottom: 36px;
    }

    .jureto-dashboard-page .stat-card {
      min-height: auto;
      padding: 24px 22px;
    }

    .jureto-dashboard-page .section-head {
      align-items: flex-end;
    }

    .jureto-dashboard-page .quote-row {
      grid-template-columns: 1fr auto;
      gap: 12px;
      padding: 20px;
    }

    .jureto-dashboard-page .quote-main,
    .jureto-dashboard-page .quote-money {
      grid-column: 1 / 2;
    }

    .jureto-dashboard-page .quote-arrow {
      grid-column: 2 / 3;
      grid-row: 1 / 4;
      align-self: center;
    }

    .jureto-dashboard-page .badge {
      width: fit-content;
    }

    .jureto-dashboard-page .foot-metrics {
      gap: 14px;
      display: grid;
    }
  }
</style>

@php
  $proposalsSource = $propuestasComerciales ?? $propuestas ?? collect();

  $isPaginator = is_object($proposalsSource) && method_exists($proposalsSource, 'getCollection');
  $proposalsCollection = $isPaginator ? $proposalsSource->getCollection() : collect($proposalsSource);

  $allForStats = isset($allPropuestasComerciales)
      ? collect($allPropuestasComerciales)
      : $proposalsCollection;

  $fmtMoney = fn($n) => '$' . number_format((float) $n, 0);

  $proposalTotal = function ($proposal) {
      return (float) (
          $proposal->total
          ?? $proposal->subtotal
          ?? $proposal->subtotal_venta
          ?? 0
      );
  };

  $proposalCost = function ($proposal) {
      if (isset($proposal->subtotal_costo)) {
          return (float) $proposal->subtotal_costo;
      }

      if (isset($proposal->items)) {
          return (float) collect($proposal->items)->sum(function ($item) {
              $qty = (float) ($item->cantidad_cotizada ?? $item->quantity ?? 0);
              $cost = (float) ($item->costo_unitario ?? $item->cost ?? 0);
              return $qty * $cost;
          });
      }

      return 0;
  };

  $proposalProfit = function ($proposal) use ($proposalTotal, $proposalCost) {
      if (isset($proposal->utilidad_total)) {
          return (float) $proposal->utilidad_total;
      }

      if (isset($proposal->profit)) {
          return (float) $proposal->profit;
      }

      return $proposalTotal($proposal) - $proposalCost($proposal);
  };

  $totalQuotes = (int) $allForStats->count();
  $quotedAmount = (float) $allForStats->sum(fn($p) => $proposalTotal($p));
  $estimatedProfit = (float) $allForStats->sum(fn($p) => $proposalProfit($p));
  $estimatedCost = (float) $allForStats->sum(fn($p) => $proposalCost($p));
  $avgMargin = $estimatedCost > 0 ? round(($estimatedProfit / $estimatedCost) * 100) : 0;

  try {
      $productsCount = class_exists(\App\Models\Product::class) ? \App\Models\Product::count() : 0;
  } catch (\Throwable $e) {
      $productsCount = 0;
  }

  $draftsCount = $allForStats->filter(function ($p) {
      return in_array(strtolower((string) ($p->status ?? 'draft')), ['draft', 'borrador', 'pending', 'pendiente'], true);
  })->count();

  $approvedCount = $allForStats->filter(function ($p) {
      return in_array(strtolower((string) ($p->status ?? '')), ['approved', 'aprobada', 'accepted', 'aceptada'], true);
  })->count();

  $recentProposals = $proposalsCollection->sortByDesc(fn($p) => $p->created_at ?? now())->take(8);

  $statusLabel = function ($status) {
      $status = strtolower((string) ($status ?: 'draft'));

      return match ($status) {
          'approved', 'aprobada', 'accepted', 'aceptada' => 'Aprobada',
          'priced', 'cotizada' => 'Cotizada',
          'matched' => 'Analizada',
          'processing', 'procesando' => 'Procesando',
          'rejected', 'rechazada' => 'Rechazada',
          default => 'Borrador',
      };
  };

  $statusClass = function ($status) {
      $status = strtolower((string) ($status ?: 'draft'));

      return match ($status) {
          'approved', 'aprobada', 'accepted', 'aceptada' => 'badge-approved',
          'priced', 'cotizada' => 'badge-priced',
          'matched' => 'badge-matched',
          'processing', 'procesando' => 'badge-processing',
          'rejected', 'rechazada' => 'badge-rejected',
          default => 'badge-draft',
      };
  };

  $proposalFolio = function ($proposal) {
      return $proposal->titulo
          ?? $proposal->folio_cotizacion
          ?? $proposal->folio
          ?? ('COT-' . strtoupper(substr(md5(($proposal->id ?? '') . ($proposal->created_at ?? '')), 0, 8)));
  };

  $proposalCode = function ($proposal) {
      return $proposal->folio
          ?? $proposal->codigo
          ?? $proposal->licitacion_codigo
          ?? ('TEOA' . str_pad((string) ($proposal->id ?? 0), 8, '0', STR_PAD_LEFT));
  };

  $proposalNote = function ($proposal) {
      return $proposal->descripcion
          ?? $proposal->notas
          ?? $proposal->cliente
          ?? $proposal->filename
          ?? 'Sin descripción';
  };

  $showRoute = function ($proposal) {
      return route('propuestas-comerciales.show', $proposal);
  };

  $createRoute = route('propuestas-comerciales.create');
@endphp

<div class="jureto-dashboard-page">
  <div class="dash-wrap">
    <div class="dash-top">
      <div>
        <p class="eyebrow">Panel general</p>
        <h1 class="dash-title">Dashboard</h1>
        <p class="dash-subtitle">
          Resumen ejecutivo de tus cotizaciones comerciales, utilidad estimada y estado de aprobación.
        </p>
      </div>

      <a href="{{ $createRoute }}" class="btn btn-primary">
        <span>▣</span>
        Nueva cotización
      </a>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <p class="stat-label">Cotizaciones</p>
        <p class="stat-value">{{ number_format($totalQuotes) }}</p>
        <div class="stat-caption">total</div>
      </div>

      <div class="stat-card">
        <p class="stat-label">Monto cotizado</p>
        <p class="stat-value">{{ $fmtMoney($quotedAmount) }}</p>
        <div class="stat-caption">acumulado</div>
      </div>

      <div class="stat-card is-blue">
        <p class="stat-label">Utilidad estimada</p>
        <p class="stat-value">{{ $fmtMoney($estimatedProfit) }}</p>
        <div class="stat-caption">neta</div>
      </div>

      <div class="stat-card">
        <p class="stat-label">Margen promedio</p>
        <p class="stat-value">{{ $avgMargin }}%</p>
        <div class="stat-caption">sobre costo</div>
      </div>
    </div>

    <div class="section-head">
      <h2 class="section-title">Cotizaciones recientes</h2>

      @if($isPaginator && method_exists($proposalsSource, 'url'))
        <a href="{{ $proposalsSource->url(1) }}" class="view-all">
          Ver todas <span>→</span>
        </a>
      @else
        <a href="{{ route('propuestas-comerciales.index') }}" class="view-all">
          Ver todas <span>→</span>
        </a>
      @endif
    </div>

    @if($recentProposals->count())
      <div class="recent-card">
        @foreach($recentProposals as $proposal)
          @php
            $cost = $proposalCost($proposal);
            $profit = $proposalProfit($proposal);
            $margin = $cost > 0 ? round(($profit / $cost) * 100) : 0;
            $status = $proposal->status ?? 'draft';
          @endphp

          <a href="{{ $showRoute($proposal) }}" class="quote-row">
            <div>
              <div class="quote-folio">{{ $proposalFolio($proposal) }}</div>
              <div class="quote-date">
                {{ optional($proposal->created_at)->format('d M Y') ?? 'Sin fecha' }}
              </div>
            </div>

            <div class="quote-main">
              <div class="quote-code">{{ $proposalCode($proposal) }}</div>
              <div class="quote-note">{{ $proposalNote($proposal) }}</div>
            </div>

            <div>
              <span class="badge {{ $statusClass($status) }}">
                {{ $statusLabel($status) }}
              </span>
            </div>

            <div class="quote-money">
              <div class="quote-total">{{ $fmtMoney($proposalTotal($proposal)) }}</div>
              <div class="quote-margin">↗ {{ $margin }}%</div>
            </div>

            <div class="quote-arrow">→</div>
          </a>
        @endforeach
      </div>
    @else
      <div class="empty-state">
        <h2 class="empty-title">Aún no tienes cotizaciones</h2>
        <p class="empty-text">
          Crea tu primera cotización comercial, analiza partidas con IA y calcula márgenes automáticamente.
        </p>
        <a href="{{ $createRoute }}" class="btn btn-primary">
          ▣ Nueva cotización
        </a>
      </div>
    @endif

    <div class="foot-metrics">
      <div><strong>{{ number_format($productsCount) }}</strong> productos en catálogo</div>
      <div><strong>{{ number_format($draftsCount) }}</strong> borradores pendientes</div>
      <div><strong>{{ number_format($approvedCount) }}</strong> aceptadas</div>
    </div>

    @if($isPaginator)
      <div class="pagination-wrap">
        {{ $proposalsSource->links() }}
      </div>
    @endif
  </div>
</div>
@endsection