@extends('layouts.app')

@section('title', 'WMS · Fast Flow')

@section('content')
@php
  $normalizeFast = function ($value): string {
      return strtoupper(trim((string) $value));
  };

  $decodeFast = function ($value) {
      if (is_array($value)) return $value;
      if (is_object($value)) return (array) $value;
      if (is_string($value) && trim($value) !== '') {
          $decoded = json_decode($value, true);
          if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
              return $decoded;
          }
      }
      return [];
  };

  $readPickWaveBag = function ($wave) use ($decodeFast) {
      foreach (['meta', 'data', 'payload', 'extra'] as $column) {
          if (isset($wave->{$column})) {
              $decoded = $decodeFast($wave->{$column});
              if (is_array($decoded) && !empty($decoded)) {
                  return $decoded;
              }
          }
      }
      return [];
  };

  $readPickWaveStatus = function ($wave) use ($readPickWaveBag) {
      $bag = $readPickWaveBag($wave);

      $status = $bag['status'] ?? ($wave->status ?? 'pending');

      if (is_numeric($status)) {
          return match ((int) $status) {
              0 => 'pending',
              1 => 'in_progress',
              2 => 'completed',
              3, 9 => 'cancelled',
              default => 'pending',
          };
      }

      $status = strtolower(trim((string) $status));

      return match ($status) {
          'pending', 'in_progress', 'completed', 'cancelled' => $status,
          default => 'pending',
      };
  };

  $readPickWaveItems = function ($wave) use ($decodeFast, $readPickWaveBag) {
      $bag = $readPickWaveBag($wave);

      if (!empty($bag['items']) && is_array($bag['items'])) {
          return $bag['items'];
      }

      foreach (['items', 'items_json'] as $column) {
          if (isset($wave->{$column})) {
              $decoded = $decodeFast($wave->{$column});
              if (is_array($decoded)) {
                  return $decoded;
              }
          }
      }

      return [];
  };

  $extractLabelsFromItem = function (array $item) use ($normalizeFast) {
      $labels = [];

      foreach ((array) data_get($item, 'box_allocations', []) as $entry) {
          if (is_array($entry)) {
              $label = $normalizeFast(data_get($entry, 'label') ?: data_get($entry, 'box_label') ?: data_get($entry, 'code'));
              if ($label !== '') $labels[$label] = true;
          } else {
              $label = $normalizeFast($entry);
              if ($label !== '') $labels[$label] = true;
          }
      }

      foreach ((array) data_get($item, 'scanned_boxes', []) as $label) {
          $label = $normalizeFast($label);
          if ($label !== '') $labels[$label] = true;
      }

      return array_keys($labels);
  };

  $extractStageLabelsFromItem = function (array $item) use ($normalizeFast) {
      $labels = [];

      foreach ((array) data_get($item, 'stage_box_allocations', []) as $entry) {
          if (is_array($entry)) {
              $label = $normalizeFast(data_get($entry, 'label') ?: data_get($entry, 'box_label') ?: data_get($entry, 'code'));
              if ($label !== '') $labels[$label] = true;
          } else {
              $label = $normalizeFast($entry);
              if ($label !== '') $labels[$label] = true;
          }
      }

      foreach ((array) data_get($item, 'staged_boxes', []) as $label) {
          $label = $normalizeFast($label);
          if ($label !== '') $labels[$label] = true;
      }

      return array_keys($labels);
  };

  $reservedByBatch = [];
  $stagedByBatch = [];

  try {
      $livePickWaves = \App\Models\PickWave::query()
          ->orderByDesc('id')
          ->limit(600)
          ->get();

      foreach ($livePickWaves as $wave) {
          $waveStatus = $readPickWaveStatus($wave);

          if (!in_array($waveStatus, ['pending', 'in_progress'], true)) {
              continue;
          }

          foreach ($readPickWaveItems($wave) as $item) {
              if (!is_array($item)) continue;

              $batchCode = $normalizeFast(data_get($item, 'batch_code'));
              if ($batchCode === '') continue;

              foreach ($extractLabelsFromItem($item) as $label) {
                  $reservedByBatch[$batchCode][$label] = true;
              }

              foreach ($extractStageLabelsFromItem($item) as $label) {
                  $stagedByBatch[$batchCode][$label] = true;
              }
          }
      }
  } catch (\Throwable $e) {
      $reservedByBatch = [];
      $stagedByBatch = [];
  }

  $batchCards = collect($recentBatches ?? [])->map(function($b) use ($reservedByBatch){
      $batchCode       = (string) data_get($b, 'batch_code', '');
      $productName     = (string) (data_get($b, 'product_name') ?? 'Producto');
      $sku             = (string) (data_get($b, 'sku') ?? '—');
      $warehouseName   = (string) (data_get($b, 'warehouse_name') ?? '—');
      $boxesCount      = (int) data_get($b, 'boxes_count', 0);
      $unitsPerBox     = (int) data_get($b, 'units_per_box', 0);
      $totalUnits      = (int) data_get($b, 'total_units', ($boxesCount * $unitsPerBox));
      $availableBoxesB = (int) data_get($b, 'available_boxes', 0);
      $availableUnitsB = (int) data_get($b, 'available_units', 0);
      $dispatchedBoxes = max(0, $boxesCount - $availableBoxesB);

      $reservedBoxesB  = isset($reservedByBatch[$batchCode]) ? count($reservedByBatch[$batchCode]) : 0;
      $reservedBoxesB  = min($reservedBoxesB, max(0, $availableBoxesB));
      $freeBoxesB      = max(0, $availableBoxesB - $reservedBoxesB);
      $reservedUnitsB  = max(0, $reservedBoxesB * max(0, $unitsPerBox));
      $freeUnitsB      = max(0, $availableUnitsB - $reservedUnitsB);

      $status          = ($freeBoxesB > 0 || $reservedBoxesB > 0) ? 'active' : 'completed';
      $progress        = $boxesCount > 0
          ? (int) round(((($reservedBoxesB + $dispatchedBoxes) / max(1, $boxesCount)) * 100))
          : 0;

      $dateSource = data_get($b, 'received_at') ?: data_get($b, 'created_at') ?: now();
      try {
          $dateObj       = \Illuminate\Support\Carbon::parse($dateSource);
          $dateLabel     = $dateObj->format('d M Y');
          $dateTimeLabel = $dateObj->format('d/m/Y H:i');
      } catch (\Throwable $e) {
          $dateLabel     = (string) $dateSource;
          $dateTimeLabel = (string) $dateSource;
      }

      $searchBlob = implode(' ', [
          $batchCode,
          $productName,
          $sku,
          $warehouseName,
      ]);

      return [
          'batch_code'       => $batchCode,
          'product_name'     => $productName,
          'sku'              => $sku,
          'warehouse_name'   => $warehouseName,
          'boxes_count'      => $boxesCount,
          'units_per_box'    => $unitsPerBox,
          'total_units'      => $totalUnits,
          'available_boxes'  => $freeBoxesB,
          'reserved_boxes'   => $reservedBoxesB,
          'available_units'  => $freeUnitsB,
          'reserved_units'   => $reservedUnitsB,
          'dispatched_boxes' => $dispatchedBoxes,
          'status'           => $status,
          'progress'         => $progress,
          'date_label'       => $dateLabel,
          'date_time_label'  => $dateTimeLabel,
          'show_url'         => $batchCode ? route('admin.wms.fastflow.show', $batchCode) : '#',
          'search_blob'      => $searchBlob,
      ];
  })->values();

  $activeShipments   = (int) $batchCards->where('status', 'active')->count();
  $boxesInStock      = (int) $batchCards->sum('available_boxes');
  $boxesReservedLive = (int) $batchCards->sum('reserved_boxes');
  $boxesDispatched   = (int) $batchCards->sum('dispatched_boxes');
  $piecesRemaining   = (int) $batchCards->sum('available_units');
@endphp

<div class="ff2-page" data-live-fastflow-dashboard="1">
  <div class="ff2-head">
    <div class="ff2-title-wrap">
      <div class="ff2-brand-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M13 2L4 14h6l-1 8 9-12h-6l1-8Z"/>
        </svg>
      </div>

      <div>
        <h1 class="ff2-title">Fast Flow</h1>
        <div class="ff2-sub">Cross Dock · Tránsito Rápido</div>
      </div>
    </div>

    <div class="ff2-top-actions">
      <span class="ff2-live-pill">
        <span class="ff2-live-dot"></span>
        Actualización automática
      </span>

      <a href="{{ route('admin.wms.fastflow.create') }}" class="ff2-top-btn ff2-top-btn-in">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M12 5v14"/>
          <path d="M7 10l5-5 5 5"/>
          <path d="M4 19h16"/>
        </svg>
        <span>Entrada</span>
      </a>

      <a href="{{ route('admin.wms.picking.v2') }}" class="ff2-top-btn ff2-top-btn-out">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M3 7h18"/>
          <path d="M6 12h12"/>
          <path d="M10 17h4"/>
        </svg>
        <span>Salida / Picking</span>
      </a>
    </div>
  </div>

  @if(session('ok'))
    <div class="ff2-alert ff2-alert-ok">{{ session('ok') }}</div>
  @endif

  @if($errors->any())
    <div class="ff2-alert ff2-alert-err">
      @foreach($errors->all() as $error)
        <div>{{ $error }}</div>
      @endforeach
    </div>
  @endif

  <div class="ff2-kpis" id="ff2Kpis">
    <div class="ff2-kpi ff2-kpi-green">
      <div class="ff2-kpi-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2l8 4.5v11L12 22 4 17.5v-11L12 2z"/>
          <path d="M12 22v-9.5"/>
          <path d="M20 6.5L12 11 4 6.5"/>
        </svg>
      </div>
      <div class="ff2-kpi-value">{{ number_format($activeShipments) }}</div>
      <div class="ff2-kpi-label">Envíos Activos</div>
    </div>

    <div class="ff2-kpi ff2-kpi-blue">
      <div class="ff2-kpi-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2l4 2.4v4.8L12 11.6 8 9.2V4.4L12 2z"/>
          <path d="M7 10.6l4 2.4v4.8L7 20.2 3 17.8V13l4-2.4z"/>
          <path d="M17 10.6l4 2.4v4.8L17 20.2 13 17.8V13l4-2.4z"/>
        </svg>
      </div>
      <div class="ff2-kpi-value">{{ number_format($boxesInStock) }}</div>
      <div class="ff2-kpi-label">Cajas Libres</div>
    </div>

    <div class="ff2-kpi ff2-kpi-picking">
      <div class="ff2-kpi-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 7h18"/>
          <path d="M6 12h12"/>
          <path d="M10 17h4"/>
        </svg>
      </div>
      <div class="ff2-kpi-value">{{ number_format($boxesReservedLive) }}</div>
      <div class="ff2-kpi-label">Cajas en Picking</div>
    </div>

    <div class="ff2-kpi ff2-kpi-orange">
      <div class="ff2-kpi-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2l8 4.5v11L12 22 4 17.5v-11L12 2z"/>
          <path d="M12 22v-9.5"/>
          <path d="M20 6.5L12 11 4 6.5"/>
        </svg>
      </div>
      <div class="ff2-kpi-value">{{ number_format($boxesDispatched) }}</div>
      <div class="ff2-kpi-label">Cajas Despachadas</div>
    </div>

    <div class="ff2-kpi ff2-kpi-slate">
      <div class="ff2-kpi-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2l8 4.5v11L12 22 4 17.5v-11L12 2z"/>
          <path d="M20 6.5L12 11 4 6.5"/>
          <path d="M16 15l4 4"/>
          <path d="M20 15l-4 4"/>
        </svg>
      </div>
      <div class="ff2-kpi-value">{{ number_format($piecesRemaining) }}</div>
      <div class="ff2-kpi-label">Piezas Libres</div>
    </div>
  </div>

  <div class="ff2-toolbar">
    <div class="ff2-tabs" role="tablist" aria-label="Filtro de lotes">
      <button type="button" class="ff2-tab is-active" data-tab="active">Activos</button>
      <button type="button" class="ff2-tab" data-tab="completed">Completados</button>
      <button type="button" class="ff2-tab" data-tab="all">Todos</button>
    </div>

    <div class="ff2-search">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="11" cy="11" r="7"></circle>
        <path d="M20 20l-3.5-3.5"></path>
      </svg>
      <input type="text" id="ff2DashboardSearch" placeholder="Buscar producto o código...">
    </div>
  </div>

  <div class="ff2-cards" id="ff2Cards">
    @forelse($batchCards as $card)
      <article
        class="ff2-flow-card"
        data-status="{{ $card['status'] }}"
        data-search="{{ \Illuminate\Support\Str::lower($card['search_blob']) }}"
      >
        <div class="ff2-flow-top">
          <div class="ff2-flow-ident">
            <div class="ff2-flow-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2l8 4.5v11L12 22 4 17.5v-11L12 2z"/>
                <path d="M12 22v-9.5"/>
                <path d="M20 6.5L12 11 4 6.5"/>
              </svg>
            </div>

            <div>
              <div class="ff2-flow-name">{{ $card['product_name'] }}</div>
              <div class="ff2-flow-code">{{ $card['batch_code'] }}</div>
              <div class="ff2-flow-sku">{{ $card['sku'] !== '—' ? $card['sku'] : $card['warehouse_name'] }}</div>
            </div>
          </div>

          @if($card['status'] === 'active')
            <span class="ff2-status ff2-status-green">Activo</span>
          @else
            <span class="ff2-status ff2-status-gray">Completado</span>
          @endif
        </div>

        <div class="ff2-metrics ff2-metrics-4">
          <div class="ff2-metric">
            <div class="ff2-metric-value">{{ number_format($card['boxes_count']) }}</div>
            <div class="ff2-metric-label">Cajas Total</div>
          </div>
          <div class="ff2-metric">
            <div class="ff2-metric-value is-green">{{ number_format($card['available_boxes']) }}</div>
            <div class="ff2-metric-label">Libres</div>
          </div>
          <div class="ff2-metric">
            <div class="ff2-metric-value is-picking">{{ number_format($card['reserved_boxes']) }}</div>
            <div class="ff2-metric-label">En Picking</div>
          </div>
          <div class="ff2-metric">
            <div class="ff2-metric-value is-orange">{{ number_format($card['dispatched_boxes']) }}</div>
            <div class="ff2-metric-label">Despachadas</div>
          </div>
        </div>

        <div class="ff2-progress">
          <span style="width: {{ max(0, min(100, $card['progress'])) }}%"></span>
        </div>

        <div class="ff2-flow-foot">
          <div class="ff2-flow-date">{{ $card['date_label'] }}</div>

          <div class="ff2-flow-actions">
            <a href="{{ $card['show_url'] }}" class="ff2-card-link">Ver detalle</a>
          </div>
        </div>
      </article>
    @empty
      <div class="ff2-empty ff2-empty-main" id="ff2EmptyMain">
        <div class="ff2-empty-title">Aún no hay lotes registrados</div>
        <div class="ff2-empty-sub">Aquí aparecerán tus lotes de Fast Flow.</div>
      </div>
    @endforelse
  </div>

  @if($batchCards->count())
    <div class="ff2-empty" id="ff2SearchEmpty" hidden>
      <div class="ff2-empty-title">Sin resultados</div>
      <div class="ff2-empty-sub">No encontramos lotes con ese filtro o búsqueda.</div>
    </div>
  @endif
</div>
@endsection

@push('styles')
<style>
  :root{
    --ff2-card:#ffffff;
    --ff2-line:#dfe6ef;
    --ff2-line-soft:#ecf1f6;
    --ff2-ink:#0f2345;
    --ff2-muted:#6b7b93;
    --ff2-shadow:0 18px 42px rgba(15,35,69,.06);
    --ff2-radius:20px;
  }

  .ff2-page{
    max-width:1280px;
    margin:0 auto;
    padding:18px 14px 30px;
    color:var(--ff2-ink);
  }

  .ff2-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:18px;
    flex-wrap:wrap;
    margin-bottom:18px;
  }

  .ff2-title-wrap{
    display:flex;
    align-items:flex-start;
    gap:14px;
  }

  .ff2-brand-icon{
    width:54px;
    height:54px;
    border-radius:16px;
    background:#0a9f69;
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 14px 30px rgba(5,150,105,.18);
    flex:0 0 54px;
  }

  .ff2-brand-icon svg{width:26px;height:26px}

  .ff2-title{
    margin:0;
    font-size:2.05rem;
    line-height:1.05;
    font-weight:900;
    letter-spacing:-.03em;
    color:var(--ff2-ink);
  }

  .ff2-sub{
    margin-top:4px;
    color:var(--ff2-muted);
    font-size:1rem;
  }

  .ff2-top-actions{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    align-items:center;
  }

  .ff2-live-pill{
    display:inline-flex;
    align-items:center;
    gap:8px;
    min-height:44px;
    padding:0 14px;
    border-radius:16px;
    border:1px solid #dbe6f3;
    background:#fff;
    color:#58708e;
    font-weight:800;
    box-shadow:var(--ff2-shadow);
  }

  .ff2-live-dot{
    width:8px;
    height:8px;
    border-radius:999px;
    background:#10b981;
    box-shadow:0 0 0 0 rgba(16,185,129,.35);
    animation:ff2Pulse 1.9s infinite;
  }

  @keyframes ff2Pulse{
    0%{ box-shadow:0 0 0 0 rgba(16,185,129,.35); }
    70%{ box-shadow:0 0 0 9px rgba(16,185,129,0); }
    100%{ box-shadow:0 0 0 0 rgba(16,185,129,0); }
  }

  .ff2-top-btn{
    border-radius:16px;
    padding:13px 18px;
    border:1px solid transparent;
    font-weight:900;
    display:inline-flex;
    align-items:center;
    gap:10px;
    cursor:pointer;
    transition:.18s ease;
    text-decoration:none;
    box-shadow:var(--ff2-shadow);
  }

  .ff2-top-btn svg{width:18px;height:18px}
  .ff2-top-btn:hover{transform:translateY(-1px)}

  .ff2-top-btn-in{
    background:#079669;
    color:#fff;
    border-color:#079669;
  }

  .ff2-top-btn-out{
    color:#c56b11;
    border-color:#e8bf75;
    background:#fffaf1;
  }

  .ff2-alert{
    border-radius:16px;
    padding:13px 16px;
    font-weight:800;
    margin-bottom:14px;
    box-shadow:var(--ff2-shadow);
  }

  .ff2-alert-ok{
    background:#edfdf5;
    color:#166534;
    border:1px solid #b7efcf;
  }

  .ff2-alert-err{
    background:#fff1f2;
    color:#be123c;
    border:1px solid #fecdd3;
  }

  .ff2-kpis{
    display:grid;
    grid-template-columns:repeat(5,minmax(0,1fr));
    gap:18px;
    margin-bottom:18px;
  }

  .ff2-kpi{
    border-radius:22px;
    border:1px solid var(--ff2-line);
    padding:18px 22px 20px;
    min-height:132px;
    position:relative;
    overflow:hidden;
  }

  .ff2-kpi-green{background:#eef8f2;border-color:#b7e3c8}
  .ff2-kpi-blue{background:#eef2fb;border-color:#bfd0fb}
  .ff2-kpi-picking{background:#eef4ff;border-color:#b7cdfa}
  .ff2-kpi-orange{background:#f8f2e5;border-color:#efd173}
  .ff2-kpi-slate{background:#f3f5f8;border-color:#d7dde8}

  .ff2-kpi-icon{
    width:28px;
    height:28px;
    color:inherit;
    opacity:.75;
  }

  .ff2-kpi-icon svg{width:100%;height:100%}
  .ff2-kpi-green .ff2-kpi-icon,
  .ff2-kpi-green .ff2-kpi-label{color:#3f9f81}
  .ff2-kpi-blue .ff2-kpi-icon,
  .ff2-kpi-blue .ff2-kpi-label{color:#5878eb}
  .ff2-kpi-picking .ff2-kpi-icon,
  .ff2-kpi-picking .ff2-kpi-label{color:#3158d4}
  .ff2-kpi-orange .ff2-kpi-icon,
  .ff2-kpi-orange .ff2-kpi-label{color:#d18341}
  .ff2-kpi-slate .ff2-kpi-icon,
  .ff2-kpi-slate .ff2-kpi-label{color:#7a8698}

  .ff2-kpi-value{
    margin-top:18px;
    font-size:2.1rem;
    line-height:1;
    font-weight:950;
    letter-spacing:-.03em;
    color:var(--ff2-ink);
  }

  .ff2-kpi-label{
    margin-top:10px;
    font-size:.98rem;
    font-weight:500;
  }

  .ff2-toolbar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    flex-wrap:wrap;
    margin-bottom:18px;
  }

  .ff2-tabs{
    display:inline-flex;
    align-items:center;
    background:#fff;
    border:1px solid var(--ff2-line);
    border-radius:14px;
    padding:3px;
    box-shadow:var(--ff2-shadow);
  }

  .ff2-tab{
    border:0;
    background:transparent;
    color:#69788f;
    font-weight:900;
    padding:10px 14px;
    border-radius:11px;
    cursor:pointer;
    transition:background .18s ease,color .18s ease,box-shadow .18s ease,transform .18s ease;
  }

  .ff2-tab:hover{
    background:#f4f8f6;
    color:#0a9467;
    box-shadow:0 3px 10px rgba(15,35,69,.06);
  }

  .ff2-tab.is-active{
    background:#fff;
    color:var(--ff2-ink);
    box-shadow:0 3px 10px rgba(15,35,69,.08);
  }

  .ff2-search{
    position:relative;
    width:min(100%, 435px);
  }

  .ff2-search svg{
    position:absolute;
    left:15px;
    top:50%;
    transform:translateY(-50%);
    width:18px;
    height:18px;
    color:#94a3b8;
  }

  .ff2-search input{
    width:100%;
    height:46px;
    border:1px solid var(--ff2-line);
    border-radius:14px;
    background:#fff;
    padding:0 14px 0 44px;
    color:var(--ff2-ink);
    outline:none;
    box-shadow:var(--ff2-shadow);
  }

  .ff2-cards{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:16px;
    margin-bottom:18px;
  }

  .ff2-flow-card{
    background:#fff;
    border:1px solid var(--ff2-line);
    border-radius:22px;
    box-shadow:var(--ff2-shadow);
    padding:22px 22px 18px;
    transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
  }

  .ff2-flow-card:hover{
    transform:translateY(-2px);
    box-shadow:0 22px 42px rgba(15,35,69,.09);
    border-color:#79d0a3;
  }

  .ff2-flow-top{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
  }

  .ff2-flow-ident{
    display:flex;
    align-items:flex-start;
    gap:14px;
    min-width:0;
  }

  .ff2-flow-icon{
    width:46px;
    height:46px;
    border-radius:14px;
    background:#e9f7ee;
    color:#12a06e;
    display:flex;
    align-items:center;
    justify-content:center;
    flex:0 0 46px;
  }

  .ff2-flow-icon svg{width:22px;height:22px}

  .ff2-flow-name{
    font-size:1.02rem;
    font-weight:900;
    color:var(--ff2-ink);
    line-height:1.25;
  }

  .ff2-flow-code{
    margin-top:4px;
    color:#4d7aa4;
    font-size:.94rem;
  }

  .ff2-flow-sku{
    margin-top:2px;
    color:var(--ff2-muted);
    font-size:.83rem;
  }

  .ff2-status{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:64px;
    padding:6px 12px;
    border-radius:999px;
    font-size:.8rem;
    font-weight:900;
    white-space:nowrap;
  }

  .ff2-status-green{
    background:#d9f8e5;
    color:#0c7a4f;
    border:1px solid #b5eccd;
  }

  .ff2-status-gray{
    background:#eef2f7;
    color:#5d6b80;
    border:1px solid #d9e2ec;
  }

  .ff2-metrics{
    display:grid;
    gap:12px;
    margin-top:18px;
  }

  .ff2-metrics-4{
    grid-template-columns:repeat(4,minmax(0,1fr));
  }

  .ff2-metric{
    background:#f7f9fc;
    border-radius:16px;
    padding:14px 12px 12px;
    text-align:center;
  }

  .ff2-metric-value{
    font-size:1.2rem;
    font-weight:950;
    color:var(--ff2-ink);
    line-height:1;
  }

  .ff2-metric-value.is-green{color:#079669}
  .ff2-metric-value.is-orange{color:#d97706}
  .ff2-metric-value.is-picking{color:#3158d4}

  .ff2-metric-label{
    margin-top:8px;
    font-size:.83rem;
    color:#61748a;
  }

  .ff2-progress{
    margin-top:20px;
    height:8px;
    background:#e8edf4;
    border-radius:999px;
    overflow:hidden;
  }

  .ff2-progress span{
    display:block;
    height:100%;
    background:linear-gradient(90deg,#3158d4,#12a06e,#d97706);
    border-radius:999px;
  }

  .ff2-flow-foot{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    margin-top:14px;
    flex-wrap:wrap;
    min-height:28px;
  }

  .ff2-flow-date{
    color:#8492a6;
    font-size:.92rem;
  }

  .ff2-flow-actions{
    display:flex;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    opacity:0;
    visibility:hidden;
    transform:translateY(6px);
    pointer-events:none;
    transition:opacity .18s ease, transform .18s ease, visibility .18s ease;
  }

  .ff2-flow-card:hover .ff2-flow-actions{
    opacity:1;
    visibility:visible;
    transform:translateY(0);
    pointer-events:auto;
  }

  .ff2-card-link{
    border:0;
    background:transparent;
    cursor:pointer;
    text-decoration:none;
    color:#0a9467;
    font-weight:900;
    padding:0;
    transition:color .18s ease;
  }

  .ff2-card-link::after{
    content:" →";
    display:inline-block;
    transition:transform .18s ease;
  }

  .ff2-card-link:hover{
    color:#087f58;
  }

  .ff2-card-link:hover::after{
    transform:translateX(3px);
  }

  .ff2-empty{
    background:#fff;
    border:1px dashed #cfd8e3;
    border-radius:20px;
    padding:26px 18px;
    text-align:center;
    box-shadow:var(--ff2-shadow);
  }

  .ff2-empty-main{
    grid-column:1 / -1;
  }

  .ff2-empty-title{
    font-size:1rem;
    font-weight:900;
    color:var(--ff2-ink);
  }

  .ff2-empty-sub{
    margin-top:6px;
    color:var(--ff2-muted);
  }

  @media (max-width:1240px){
    .ff2-kpis{grid-template-columns:repeat(3,minmax(0,1fr))}
  }

  @media (max-width:1100px){
    .ff2-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}
    .ff2-cards{grid-template-columns:repeat(2,minmax(0,1fr))}
  }

  @media (max-width:760px){
    .ff2-title{font-size:1.7rem}
    .ff2-kpis{grid-template-columns:1fr}
    .ff2-cards{grid-template-columns:1fr}
    .ff2-metrics-4{grid-template-columns:repeat(2,minmax(0,1fr))}
  }
</style>
@endpush

@push('scripts')
<script>
(function(){
  const tabs = document.querySelectorAll('.ff2-tab');
  const dashboardSearch = document.getElementById('ff2DashboardSearch');
  const searchEmpty = document.getElementById('ff2SearchEmpty');
  let cards = Array.from(document.querySelectorAll('.ff2-flow-card'));
  let activeTab = 'active';
  let isRefreshing = false;

  function recaptureCards(){
    cards = Array.from(document.querySelectorAll('.ff2-flow-card'));
  }

  function applyDashboardFilters(){
    const query = String((dashboardSearch && dashboardSearch.value) || '').trim().toLowerCase();
    let visible = 0;

    cards.forEach(function(card){
      const cardStatus = card.dataset.status || '';
      const haystack = (card.dataset.search || '').toLowerCase();

      const okTab = activeTab === 'all' ? true : cardStatus === activeTab;
      const okQuery = !query || haystack.includes(query);

      const show = okTab && okQuery;
      card.hidden = !show;

      if(show) visible++;
    });

    if(searchEmpty){
      searchEmpty.hidden = visible > 0;
    }
  }

  tabs.forEach(function(tab){
    tab.addEventListener('click', function(){
      tabs.forEach(function(t){ t.classList.remove('is-active'); });
      tab.classList.add('is-active');
      activeTab = tab.dataset.tab || 'all';
      applyDashboardFilters();
    });
  });

  if(dashboardSearch){
    dashboardSearch.addEventListener('input', applyDashboardFilters);
  }

  async function refreshDashboardLive(){
    if(document.hidden || isRefreshing) return;

    isRefreshing = true;

    try{
      const response = await fetch(window.location.href, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'text/html'
        },
        cache: 'no-store'
      });

      const html = await response.text();
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');

      const nextKpis = doc.querySelector('#ff2Kpis');
      const nextCards = doc.querySelector('#ff2Cards');
      const currentKpis = document.querySelector('#ff2Kpis');
      const currentCards = document.querySelector('#ff2Cards');

      if(nextKpis && currentKpis){
        currentKpis.innerHTML = nextKpis.innerHTML;
      }

      if(nextCards && currentCards){
        currentCards.innerHTML = nextCards.innerHTML;
        recaptureCards();
        applyDashboardFilters();
      }
    }catch(e){
    }finally{
      isRefreshing = false;
    }
  }

  applyDashboardFilters();

  setInterval(refreshDashboardLive, 5000);

  document.addEventListener('visibilitychange', function(){
    if(!document.hidden){
      refreshDashboardLive();
    }
  });
})();
</script>
@endpush