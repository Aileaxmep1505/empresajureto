@extends('layouts.app')

@section('title', 'WMS · Fast Flow · '.$batchCode)

@section('content')
@php
  $statusLabel = $status === 'active' ? 'Activo' : 'Completado';

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

      $statusValue = $bag['status'] ?? ($wave->status ?? 'pending');

      if (is_numeric($statusValue)) {
          return match ((int) $statusValue) {
              0 => 'pending',
              1 => 'in_progress',
              2 => 'completed',
              3, 9 => 'cancelled',
              default => 'pending',
          };
      }

      $statusValue = strtolower(trim((string) $statusValue));

      return match ($statusValue) {
          'pending', 'in_progress', 'completed', 'cancelled' => $statusValue,
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

  $reservedLabels = [];
  $stagedLabels = [];
  $currentBatchNormalized = $normalizeFast($batchCode);

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

              $itemBatch = $normalizeFast(data_get($item, 'batch_code'));
              if ($itemBatch !== $currentBatchNormalized) continue;

              foreach ((array) data_get($item, 'box_allocations', []) as $entry) {
                  if (is_array($entry)) {
                      $label = $normalizeFast(data_get($entry, 'label') ?: data_get($entry, 'box_label') ?: data_get($entry, 'code'));
                      if ($label !== '') $reservedLabels[$label] = true;
                  } else {
                      $label = $normalizeFast($entry);
                      if ($label !== '') $reservedLabels[$label] = true;
                  }
              }

              foreach ((array) data_get($item, 'scanned_boxes', []) as $label) {
                  $label = $normalizeFast($label);
                  if ($label !== '') $reservedLabels[$label] = true;
              }

              foreach ((array) data_get($item, 'stage_box_allocations', []) as $entry) {
                  if (is_array($entry)) {
                      $label = $normalizeFast(data_get($entry, 'label') ?: data_get($entry, 'box_label') ?: data_get($entry, 'code'));
                      if ($label !== '') $stagedLabels[$label] = true;
                  } else {
                      $label = $normalizeFast($entry);
                      if ($label !== '') $stagedLabels[$label] = true;
                  }
              }

              foreach ((array) data_get($item, 'staged_boxes', []) as $label) {
                  $label = $normalizeFast($label);
                  if ($label !== '') $stagedLabels[$label] = true;
              }
          }
      }
  } catch (\Throwable $e) {
      $reservedLabels = [];
      $stagedLabels = [];
  }

  $reservedLabels = array_keys($reservedLabels);
  $stagedLabels = array_keys($stagedLabels);

  $reservedBoxesCount = (int) collect($boxes)->filter(function ($box) use ($reservedLabels, $normalizeFast) {
      $status = (string) ($box->status ?? 'available');
      if ($status === 'shipped') return false;
      return in_array($normalizeFast($box->label_code ?? ''), $reservedLabels, true);
  })->count();

  $stagedBoxesCount = (int) collect($boxes)->filter(function ($box) use ($stagedLabels, $normalizeFast) {
      $status = (string) ($box->status ?? 'available');
      if ($status === 'shipped') return false;
      return in_array($normalizeFast($box->label_code ?? ''), $stagedLabels, true);
  })->count();

  $freeBoxesCount = max(0, $activeBoxes - $reservedBoxesCount);
@endphp

<div class="ffs-page" data-live-fastflow-show="1">
  <div class="ffs-head">
    <div class="ffs-head-left">
      <a href="{{ route('admin.wms.fastflow.index') }}" class="ffs-back" aria-label="Volver">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M15 18l-6-6 6-6"/>
        </svg>
      </a>

      <div>
        <div class="ffs-title-row">
          <h1 class="ffs-title">{{ $productName }}</h1>

          @if($status === 'active')
            <span class="ffs-status ffs-status-green">{{ $statusLabel }}</span>
          @else
            <span class="ffs-status ffs-status-gray">{{ $statusLabel }}</span>
          @endif

          <span class="ffs-live-pill">
            <span class="ffs-live-dot"></span>
            En tiempo real
          </span>
        </div>

        <div class="ffs-sub">{{ $batchCode }}</div>
      </div>
    </div>
  </div>

  <div class="ffs-summary-wrap" id="ffsSummaryWrap">
    <div class="ffs-summary">
      <div class="ffs-stat">
        <div class="ffs-stat-label">Total Cajas</div>
        <div class="ffs-stat-value">{{ number_format($totalBoxes) }}</div>
      </div>

      <div class="ffs-stat">
        <div class="ffs-stat-label">Pzas/Caja</div>
        <div class="ffs-stat-value">{{ number_format($unitsPerBox) }}</div>
      </div>

      <div class="ffs-stat">
        <div class="ffs-stat-label">Reservadas Picking</div>
        <div class="ffs-stat-value is-blue">{{ number_format($reservedBoxesCount) }}</div>
      </div>

      <div class="ffs-stat">
        <div class="ffs-stat-label">Despachadas</div>
        <div class="ffs-stat-value is-orange">{{ number_format($shippedBoxes) }}</div>
      </div>

      <div class="ffs-stat">
        <div class="ffs-stat-label">Piezas Libres</div>
        <div class="ffs-stat-value is-green">{{ number_format($remainingUnits) }}</div>
      </div>
    </div>
  </div>

  <div class="ffs-section-head" id="ffsSectionHead">
    <div>
      <h2 class="ffs-section-title">
        Cajas
        <span class="ffs-section-meta">
          ({{ $freeBoxesCount }} libres · {{ $reservedBoxesCount }} en picking · {{ $stagedBoxesCount }} área de picking · {{ $shippedBoxes }} despachadas)
        </span>
      </h2>
      <div class="ffs-section-sub">
        SKU: {{ $sku }} · Almacén: {{ $warehouseName }}{{ $warehouseCode ? ' · '.$warehouseCode : '' }}
      </div>
    </div>

    <div class="ffs-actions">
      <a href="{{ route('admin.wms.fastflow.labels', $batchCode) }}" class="ffs-btn ffs-btn-ghost">
        Descargar todas
      </a>

      <a href="{{ route('admin.wms.picking.v2', ['batch_code' => $batchCode]) }}" class="ffs-btn ffs-btn-warning">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2l8 4.5v11L12 22 4 17.5v-11L12 2z"/>
          <path d="M12 22v-9.5"/>
          <path d="M20 6.5L12 11 4 6.5"/>
        </svg>
        <span>Despachar</span>
      </a>
    </div>
  </div>

  <div class="ffs-grid" id="ffsGrid">
    @foreach($boxes as $box)
      @php
        $boxStatus = (string) ($box->status ?? 'available');
        $boxUnits = (int) ($box->current_units ?? 0);
        $boxOriginal = (int) ($box->units_per_box ?? 0);
        $normalizedLabel = strtoupper(trim((string) ($box->label_code ?? '')));
        $isReservedLive = in_array($normalizedLabel, $reservedLabels, true) && $boxStatus !== 'shipped';
        $isStagedLive = in_array($normalizedLabel, $stagedLabels, true) && $boxStatus !== 'shipped';

        $cardClass = 'is-available';
        $badgeText = 'En Stock';

        if ($boxStatus === 'shipped') {
            $cardClass = 'is-shipped';
            $badgeText = 'Despachada';
        } elseif ($isStagedLive) {
            $cardClass = 'is-staged';
            $badgeText = 'Área Picking';
        } elseif ($isReservedLive) {
            $cardClass = 'is-reserved';
            $badgeText = 'En Picking';
        } elseif ($boxStatus === 'partial') {
            $cardClass = 'is-partial';
            $badgeText = 'Parcial';
        }
      @endphp

      <article class="ffs-box-card {{ $cardClass }}">
        <div class="ffs-box-top">
          <div class="ffs-box-ident">
            <div class="ffs-box-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2l8 4.5v11L12 22 4 17.5v-11L12 2z"/>
                <path d="M12 22v-9.5"/>
                <path d="M20 6.5L12 11 4 6.5"/>
              </svg>
            </div>

            <div class="ffs-box-code">{{ $box->label_code }}</div>
          </div>

          <div class="ffs-box-badge">{{ $badgeText }}</div>
        </div>

        <div class="ffs-box-sub">Caja #{{ (int) ($box->box_number ?? 0) }}</div>

        <div class="ffs-box-bottom">
          @if($boxStatus === 'shipped')
            <div class="ffs-box-qty shipped">0 pzas / {{ number_format($boxOriginal) }}</div>
          @elseif($isStagedLive)
            <div class="ffs-box-qty staged">{{ number_format($boxUnits) }} pzas / {{ number_format($boxOriginal) }}</div>
          @elseif($isReservedLive)
            <div class="ffs-box-qty reserved">{{ number_format($boxUnits) }} pzas / {{ number_format($boxOriginal) }}</div>
          @elseif($boxStatus === 'partial')
            <div class="ffs-box-qty partial">{{ number_format($boxUnits) }} pzas / {{ number_format($boxOriginal) }}</div>
          @else
            <div class="ffs-box-qty">{{ number_format($boxUnits) }} pzas / {{ number_format($boxOriginal) }}</div>
          @endif
        </div>

        @if($isStagedLive)
          <div class="ffs-box-live-note">Reservada y movida a área de picking</div>
        @elseif($isReservedLive)
          <div class="ffs-box-live-note">Reservada en tiempo real por picking</div>
        @elseif($box->reference)
          <div class="ffs-box-ref">{{ $box->reference }}</div>
        @endif

        <div class="ffs-box-actions">
          <a href="{{ route('admin.wms.fastflow.label', $box->label_code) }}" class="ffs-mini-btn">
            Etiqueta
          </a>
        </div>
      </article>
    @endforeach
  </div>
</div>
@endsection

@push('styles')
<style>
  :root{
    --ffs-card:#ffffff;
    --ffs-line:#dfe6ef;
    --ffs-line-soft:#ecf1f6;
    --ffs-ink:#0f2345;
    --ffs-muted:#6b7b93;
    --ffs-shadow:0 18px 42px rgba(15,35,69,.06);
  }

  .ffs-page{
    max-width:1280px;
    margin:0 auto;
    padding:18px 14px 30px;
    color:var(--ffs-ink);
  }

  .ffs-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:18px;
    flex-wrap:wrap;
    margin-bottom:18px;
  }

  .ffs-head-left{
    display:flex;
    align-items:flex-start;
    gap:14px;
  }

  .ffs-back{
    width:38px;
    height:38px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:var(--ffs-ink);
    text-decoration:none;
    transition:.18s ease;
  }

  .ffs-back:hover{
    background:#eef3f9;
  }

  .ffs-back svg{
    width:20px;
    height:20px;
  }

  .ffs-title-row{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
  }

  .ffs-title{
    margin:0;
    font-size:2rem;
    line-height:1.03;
    font-weight:950;
    letter-spacing:-.03em;
    color:var(--ffs-ink);
  }

  .ffs-sub{
    margin-top:6px;
    font-size:1.02rem;
    color:#58708e;
  }

  .ffs-status{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:30px;
    padding:6px 12px;
    border-radius:999px;
    font-size:.86rem;
    font-weight:900;
  }

  .ffs-status-green{
    background:#d9f8e5;
    color:#0c7a4f;
  }

  .ffs-status-gray{
    background:#eef2f7;
    color:#5d6b80;
  }

  .ffs-live-pill{
    display:inline-flex;
    align-items:center;
    gap:8px;
    min-height:30px;
    padding:6px 12px;
    border-radius:999px;
    background:#eef4ff;
    color:#3158d4;
    border:1px solid #b7cdfa;
    font-size:.8rem;
    font-weight:900;
  }

  .ffs-live-dot{
    width:8px;
    height:8px;
    border-radius:999px;
    background:#3158d4;
    box-shadow:0 0 0 0 rgba(49,88,212,.35);
    animation:ffsPulse 1.9s infinite;
  }

  @keyframes ffsPulse{
    0%{ box-shadow:0 0 0 0 rgba(49,88,212,.35); }
    70%{ box-shadow:0 0 0 9px rgba(49,88,212,0); }
    100%{ box-shadow:0 0 0 0 rgba(49,88,212,0); }
  }

  .ffs-summary-wrap{
    margin-bottom:22px;
  }

  .ffs-summary{
    display:grid;
    grid-template-columns:repeat(5,minmax(0,1fr));
    background:#fff;
    border:1px solid var(--ffs-line);
    border-radius:22px;
    box-shadow:var(--ffs-shadow);
    overflow:hidden;
  }

  .ffs-stat{
    padding:22px 24px;
    border-right:1px solid var(--ffs-line-soft);
  }

  .ffs-stat:last-child{
    border-right:0;
  }

  .ffs-stat-label{
    font-size:.95rem;
    color:var(--ffs-muted);
  }

  .ffs-stat-value{
    margin-top:8px;
    font-size:1.2rem;
    font-weight:950;
    color:var(--ffs-ink);
    line-height:1.1;
  }

  .ffs-stat-value.is-green{
    color:#079669;
  }

  .ffs-stat-value.is-blue{
    color:#3158d4;
  }

  .ffs-stat-value.is-orange{
    color:#d97706;
  }

  .ffs-stat-date{
    font-size:1.05rem;
  }

  .ffs-section-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:14px;
    margin-bottom:16px;
    flex-wrap:wrap;
  }

  .ffs-section-title{
    margin:0;
    font-size:2rem;
    font-weight:950;
    letter-spacing:-.03em;
    color:var(--ffs-ink);
  }

  .ffs-section-meta{
    font-size:1.4rem;
    font-weight:900;
    color:var(--ffs-ink);
  }

  .ffs-section-sub{
    margin-top:5px;
    color:var(--ffs-muted);
    font-size:.95rem;
  }

  .ffs-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
  }

  .ffs-btn{
    min-height:44px;
    padding:11px 16px;
    border-radius:16px;
    text-decoration:none;
    font-weight:900;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    transition:.18s ease;
    box-shadow:var(--ffs-shadow);
    border:1px solid transparent;
  }

  .ffs-btn:hover{
    transform:translateY(-1px);
  }

  .ffs-btn svg{
    width:18px;
    height:18px;
  }

  .ffs-btn-ghost{
    background:#fff;
    color:var(--ffs-ink);
    border-color:var(--ffs-line);
  }

  .ffs-btn-warning{
    background:#f59e0b;
    color:#fff;
  }

  .ffs-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:14px;
  }

  .ffs-box-card{
    background:#fff;
    border:2px dashed #90e1ba;
    border-radius:20px;
    padding:18px 18px 16px;
    box-shadow:var(--ffs-shadow);
  }

  .ffs-box-card.is-available{
    border-color:#73e0a8;
  }

  .ffs-box-card.is-partial{
    border-color:#f4b21e;
    background:#fffaf0;
  }

  .ffs-box-card.is-reserved{
    border-color:#7da7ff;
    background:linear-gradient(180deg,#f3f7ff 0%, #eaf1ff 100%);
  }

  .ffs-box-card.is-staged{
    border-color:#60d394;
    background:linear-gradient(180deg,#edfdf5 0%, #dcfce7 100%);
  }

  .ffs-box-card.is-shipped{
    border-color:#d6dce7;
    background:#f9fafb;
    opacity:.92;
  }

  .ffs-box-top{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
  }

  .ffs-box-ident{
    display:flex;
    align-items:flex-start;
    gap:12px;
    min-width:0;
  }

  .ffs-box-icon{
    width:40px;
    height:40px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    flex:0 0 40px;
    background:#dff7ea;
    color:#109f6f;
  }

  .ffs-box-card.is-partial .ffs-box-icon{
    background:#fce7b9;
    color:#d18615;
  }

  .ffs-box-card.is-reserved .ffs-box-icon{
    background:#dbeafe;
    color:#3158d4;
  }

  .ffs-box-card.is-staged .ffs-box-icon{
    background:#dcfce7;
    color:#15803d;
  }

  .ffs-box-card.is-shipped .ffs-box-icon{
    background:#eceff4;
    color:#64748b;
  }

  .ffs-box-icon svg{
    width:20px;
    height:20px;
  }

  .ffs-box-code{
    font-size:1.05rem;
    line-height:1.35;
    font-weight:950;
    color:var(--ffs-ink);
    word-break:break-word;
  }

  .ffs-box-badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:34px;
    padding:6px 12px;
    border-radius:12px;
    font-weight:900;
    font-size:.92rem;
    background:#d7f2e2;
    color:#0c7a4f;
  }

  .ffs-box-card.is-partial .ffs-box-badge{
    background:#fbe7bf;
    color:#b96d07;
  }

  .ffs-box-card.is-reserved .ffs-box-badge{
    background:#dbeafe;
    color:#1d4ed8;
  }

  .ffs-box-card.is-staged .ffs-box-badge{
    background:#dcfce7;
    color:#15803d;
  }

  .ffs-box-card.is-shipped .ffs-box-badge{
    background:#e8edf3;
    color:#64748b;
  }

  .ffs-box-sub{
    margin-top:10px;
    color:#61748a;
    font-size:.96rem;
  }

  .ffs-box-bottom{
    display:flex;
    justify-content:flex-end;
    margin-top:6px;
  }

  .ffs-box-qty{
    font-size:1.1rem;
    font-weight:900;
    color:#0f2345;
  }

  .ffs-box-qty.partial{
    color:#d18615;
  }

  .ffs-box-qty.reserved{
    color:#1d4ed8;
  }

  .ffs-box-qty.staged{
    color:#15803d;
  }

  .ffs-box-qty.shipped{
    color:#64748b;
  }

  .ffs-box-ref,
  .ffs-box-live-note{
    margin-top:10px;
    font-size:.83rem;
    color:var(--ffs-muted);
    border-top:1px solid var(--ffs-line-soft);
    padding-top:10px;
  }

  .ffs-box-live-note{
    color:#3158d4;
    font-weight:800;
  }

  .ffs-box-card.is-staged .ffs-box-live-note{
    color:#15803d;
  }

  .ffs-box-actions{
    margin-top:12px;
    display:flex;
    justify-content:flex-end;
  }

  .ffs-mini-btn{
    min-height:36px;
    padding:8px 12px;
    border-radius:12px;
    text-decoration:none;
    font-weight:900;
    font-size:.9rem;
    color:#0f8f63;
    background:#edf9f2;
    border:1px solid #bce8d2;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    transition:.18s ease;
  }

  .ffs-mini-btn:hover{
    transform:translateY(-1px);
  }

  @media (max-width:1100px){
    .ffs-summary{
      grid-template-columns:repeat(2,minmax(0,1fr));
    }

    .ffs-grid{
      grid-template-columns:repeat(2,minmax(0,1fr));
    }

    .ffs-stat:nth-child(2n){
      border-right:0;
    }
  }

  @media (max-width:760px){
    .ffs-title{
      font-size:1.55rem;
    }

    .ffs-section-title,
    .ffs-section-meta{
      font-size:1.15rem;
    }

    .ffs-summary{
      grid-template-columns:1fr;
    }

    .ffs-stat{
      border-right:0;
      border-bottom:1px solid var(--ffs-line-soft);
    }

    .ffs-stat:last-child{
      border-bottom:0;
    }

    .ffs-grid{
      grid-template-columns:1fr;
    }
  }
</style>
@endpush

@push('scripts')
<script>
(function(){
  let isRefreshing = false;

  async function refreshFastFlowShow(){
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

      const blocks = [
        ['#ffsSummaryWrap', '#ffsSummaryWrap'],
        ['#ffsSectionHead', '#ffsSectionHead'],
        ['#ffsGrid', '#ffsGrid']
      ];

      blocks.forEach(function(pair){
        const current = document.querySelector(pair[0]);
        const fresh = doc.querySelector(pair[1]);

        if(current && fresh){
          current.outerHTML = fresh.outerHTML;
        }
      });
    }catch(e){
    }finally{
      isRefreshing = false;
    }
  }

  setInterval(refreshFastFlowShow, 5000);

  document.addEventListener('visibilitychange', function(){
    if(!document.hidden){
      refreshFastFlowShow();
    }
  });
})();
</script>
@endpush