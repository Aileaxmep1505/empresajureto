@extends('layouts.app')

@section('title', 'WMS · Bodega')

@section('content')
@php
  use Illuminate\Support\Facades\Route;

  $routeFirst = function (array $names, $fallback = '#') {
      foreach ($names as $name) {
          if (Route::has($name)) return route($name);
      }
      return $fallback;
  };

  $fmt = fn($v) => number_format((int)($v ?? 0));

  // SOLO DATOS REALES
  $warehouseName             = (string) ($warehouseName ?? 'Almacén principal');
  $productsCount             = (int) ($productsCount ?? 0);
  $totalStock                = (int) ($totalStock ?? 0);
  $lowStockCount             = (int) ($lowStockCount ?? 0);
  $totalLocations            = (int) ($totalLocations ?? 0);
  $usedLocations             = (int) ($usedLocations ?? 0);
  $availableLocations        = (int) ($availableLocations ?? 0);
  $occupancyRate             = (int) ($occupancyRate ?? 0);
  $pendingPickingCount       = (int) ($pendingPickingCount ?? 0);
  $inProgressPickingCount    = (int) ($inProgressPickingCount ?? 0);
  $completedPickingCount     = (int) ($completedPickingCount ?? 0);
  $fastFlowCount             = (int) ($fastFlowCount ?? 0);
  $fastFlowActiveBoxes       = (int) ($fastFlowActiveBoxes ?? 0);
  $todayMovementsCount       = (int) ($todayMovementsCount ?? 0);
  $entryCount                = (int) ($entryCount ?? 0);
  $exitCount                 = (int) ($exitCount ?? 0);
  $auditEventsCount          = (int) ($auditEventsCount ?? 0);
  $transferCount             = (int) ($transferCount ?? 0);
  $adjustCount               = (int) ($adjustCount ?? 0);
  $totalEntries              = (int) ($totalEntries ?? 0);
  $totalExits                = (int) ($totalExits ?? 0);
  $fastFlowAvailableUnits    = (int) ($fastFlowAvailableUnits ?? 0);

  $shipmentCount             = (int) ($shipmentCount ?? 0);
  $draftShipmentCount        = (int) ($draftShipmentCount ?? 0);
  $loadingShipmentCount      = (int) ($loadingShipmentCount ?? 0);
  $partialShipmentCount      = (int) ($partialShipmentCount ?? 0);
  $dispatchedShipmentCount   = (int) ($dispatchedShipmentCount ?? 0);

  $searchUrl      = $routeFirst(['admin.wms.search.view', 'admin.wms.products.finder', 'admin.wms.find-product']);
  $locationsUrl   = $routeFirst(['admin.wms.locations.view', 'admin.wms.locations.index']);
  $heatmapUrl     = $routeFirst(['admin.wms.heatmap.view']);
  $layoutUrl      = $routeFirst(['admin.wms.layout.editor']);
  $pickingUrl     = $routeFirst(['admin.wms.picking.v2', 'admin.wms.pick.entry']);
  $scannerUrl     = $routeFirst(['admin.wms.picking.scanner.v2', 'admin.wms.picking.scanner']);
  $shippingUrl    = $routeFirst(['admin.wms.shipping.index']);
  $receptionsUrl  = $routeFirst(['admin.wms.receptions.index']);
  $fastFlowUrl    = $routeFirst(['admin.wms.fastflow.index', 'admin.wms.fast-flow', 'admin.wms.fastflow']);
  $analyticsUrl   = $routeFirst(['admin.wms.analytics', 'admin.wms.analytics']);
  $auditUrl       = $routeFirst(['admin.wms.audit', 'admin.wms.audit.index']);
  $auditPdfUrl    = $routeFirst(['admin.wms.audit.pdf']);
@endphp

<div class="wms-shell">
  <div class="wms-header">
    <div>
      <h1 class="wms-title">WMS · Bodega</h1>
      <p class="wms-sub">
        Control de inventario, operaciones, picking, embarque y análisis en un solo panel.
      </p>
    </div>
  </div>

  <div class="bento-grid">
    
    {{-- 1. Analytics (Intacto) --}}
    <a href="{{ $analyticsUrl }}" class="bento-card col-span-2 card-analytics-light">
      <div class="analytics-bg-grid-light"></div>

      <div class="chart-waves-container">
        <div class="wave-layer wave-cyan"></div>
        <div class="wave-layer wave-orange"></div>
      </div>

      <div class="analytics-layout">
        <div class="analytics-top-row">
          <div class="trend-info">
            <span class="trend-title-light">Daily Movement Trends</span>
            <div class="trend-legend">
              <span class="leg-item-light"><span class="c-cyan"></span> Entradas</span>
              <span class="leg-item-light"><span class="c-orange"></span> Salidas</span>
            </div>
          </div>
          <div class="live-badge-light">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <circle cx="12" cy="12" r="10"/>
              <polyline points="12 6 12 12 16 14"/>
            </svg>
            Real-Time Data
          </div>
        </div>

        <div class="analytics-bottom-row">
          <div class="analytics-kpi-main">
            <div class="bento-icon-wrapper light-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M4 19V5"/>
                <path d="M8 19v-6"/>
                <path d="M12 19v-10"/>
                <path d="M16 19v-3"/>
                <path d="M20 19V8"/>
              </svg>
            </div>
            <h3 class="bento-title">Analytics & KPIs</h3>
            <p class="bento-desc">
              Visión general en tiempo real. 
              <strong>{{ $fmt($todayMovementsCount) }}</strong> movimientos hoy, 
              ocupación del <strong>{{ $occupancyRate }}%</strong>.
            </p>
          </div>

          <div class="analytics-mini-bars">
            <span class="bars-title-light">Resumen</span>
            <div class="bars-group">
              <div class="bar-col">
                <div class="bar h-3"></div>
                <div class="bar h-4"></div>
                <div class="bar h-2"></div>
              </div>
              <div class="bar-col">
                <div class="bar h-2"></div>
                <div class="bar h-5"></div>
                <div class="bar h-3"></div>
              </div>
              <div class="bar-col">
                <div class="bar h-4"></div>
                <div class="bar h-2"></div>
                <div class="bar h-4"></div>
              </div>
            </div>
          </div>

          <div class="analytics-gauge">
            <svg viewBox="0 0 36 36" class="circular-chart cyan">
              <path class="circle-bg-light" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
              <path class="circle" stroke-dasharray="{{ $occupancyRate }}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
            </svg>
            <div class="gauge-value-light">{{ $occupancyRate }}%</div>
          </div>
        </div>
      </div>
    </a>

    {{-- 2. Buscar Producto (Intacto) --}}
    <a href="{{ $searchUrl }}" class="bento-card col-span-1 overflow-hidden">
      <div class="bento-bg bg-marquee">
        <div class="marquee-track">
          <div class="fake-item"><span>SKU</span><div class="fake-line"></div></div>
          <div class="fake-item"><span>MODELO</span><div class="fake-line"></div></div>
          <div class="fake-item"><span>LOTE</span><div class="fake-line"></div></div>
          <div class="fake-item"><span>UBICACIÓN</span><div class="fake-line"></div></div>
          <div class="fake-item"><span>SKU</span><div class="fake-line"></div></div>
          <div class="fake-item"><span>MODELO</span><div class="fake-line"></div></div>
        </div>
      </div>
      <div class="bento-inner">
        <div class="bento-content">
          <div class="bento-icon-wrapper">
            <div class="bento-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.3-4.3"/>
              </svg>
            </div>
          </div>
          <h3 class="bento-title">Buscar producto</h3>
          <p class="bento-desc">
            <strong>{{ $fmt($productsCount) }}</strong> productos registrados y 
            <strong>{{ $fmt($totalStock) }}</strong> unidades en stock.
          </p>
        </div>
        <div class="bento-cta">Explorar <svg class="bento-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></div>
      </div>
    </a>

    {{-- 3. Picking --}}
    <a href="{{ $pickingUrl }}" class="bento-card col-span-1">
      <div class="bento-bg bg-radar">
        <div class="radar-sweep"></div>
      </div>
      <div class="bento-inner">
        <div class="bento-content">
          <div class="bento-icon-wrapper">
            <div class="bento-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M4 7h16"/>
                <path d="M4 12h10"/>
                <path d="M4 17h7"/>
                <path d="M18 15l2 2 4-4"/>
              </svg>
            </div>
          </div>
          <h3 class="bento-title">Picking</h3>
          <p class="bento-desc">
            <strong>{{ $fmt($pendingPickingCount) }}</strong> pendientes, 
            <strong>{{ $fmt($inProgressPickingCount) }}</strong> en proceso y
            <strong>{{ $fmt($completedPickingCount) }}</strong> completadas.
          </p>
        </div>
        <div class="bento-cta">Acceder <svg class="bento-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></div>
      </div>
    </a>

    {{-- 4. Embarque --}}
    <a href="{{ $shippingUrl }}" class="bento-card col-span-2">
      <div class="bento-bg bg-beams">
        <div class="beam beam-1"></div>
        <div class="beam beam-2"></div>
      </div>
      <div class="bento-inner">
        <div class="bento-content">
          <div class="bento-icon-wrapper">
            <div class="bento-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M3 7h13l3 4v6a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7z"/>
                <path d="M16 7V5a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v2"/>
                <circle cx="8.5" cy="18.5" r="1.5"/>
                <circle cx="15.5" cy="18.5" r="1.5"/>
              </svg>
            </div>
          </div>
          <h3 class="bento-title">Embarque</h3>
          <p class="bento-desc">
            <strong>{{ $fmt($loadingShipmentCount) }}</strong> cargando,
            <strong>{{ $fmt($partialShipmentCount) }}</strong> parciales,
            <strong>{{ $fmt($dispatchedShipmentCount) }}</strong> despachados.
          </p>
        </div>
        <div class="bento-cta">Despachar <svg class="bento-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></div>
      </div>
    </a>

    {{-- 5. Fast Flow --}}
    <a href="{{ $fastFlowUrl }}" class="bento-card col-span-2">
      <div class="bento-bg bg-dots-move"></div>
      <div class="bento-inner">
        <div class="bento-content">
          <div class="bento-icon-wrapper">
            <div class="bento-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M3 12h11"/>
                <path d="M3 7h8"/>
                <path d="M3 17h13"/>
                <path d="M14 12l4-4"/>
                <path d="M14 12l4 4"/>
              </svg>
            </div>
          </div>
          <h3 class="bento-title">Fast Flow</h3>
          <p class="bento-desc">
            <strong>{{ $fmt($fastFlowCount) }}</strong> lotes activos,
            <strong>{{ $fmt($fastFlowActiveBoxes) }}</strong> cajas activas y
            <strong>{{ $fmt($fastFlowAvailableUnits) }}</strong> unidades disponibles.
          </p>
        </div>
        <div class="bento-cta">Ver flujo <svg class="bento-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></div>
      </div>
    </a>

    {{-- 6. Recepciones --}}
    <a href="{{ $receptionsUrl }}" class="bento-card col-span-1">
      <div class="bento-bg bg-waves"></div>
      <div class="bento-inner">
        <div class="bento-content">
          <div class="bento-icon-wrapper">
            <div class="bento-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
              </svg>
            </div>
          </div>
          <h3 class="bento-title">Recepciones</h3>
          <p class="bento-desc">
            <strong>{{ $fmt($totalEntries) }}</strong> unidades de entrada registradas.
          </p>
        </div>
        <div class="bento-cta">Recibir <svg class="bento-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></div>
      </div>
    </a>

    {{-- 7. Escáner --}}
    <a href="{{ $scannerUrl }}" class="bento-card col-span-1">
      <div class="bento-bg bg-scanline">
        <div class="scan-bar"></div>
      </div>
      <div class="bento-inner">
        <div class="bento-content">
          <div class="bento-icon-wrapper">
            <div class="bento-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M4 7V5a1 1 0 0 1 1-1h2"/>
                <path d="M20 7V5a1 1 0 0 0-1-1h-2"/>
                <path d="M4 17v2a1 1 0 0 0 1 1h2"/>
                <path d="M20 17v2a1 1 0 0 1-1 1h-2"/>
                <path d="M7 12h10"/>
                <path d="M9 9h1"/>
                <path d="M12 9h1"/>
                <path d="M15 9h1"/>
                <path d="M9 15h1"/>
                <path d="M12 15h1"/>
                <path d="M15 15h1"/>
              </svg>
            </div>
          </div>
          <h3 class="bento-title">Escáner</h3>
          <p class="bento-desc">
            Terminal operativa con trazabilidad de productos y movimientos.
          </p>
        </div>
        <div class="bento-cta">Escanear <svg class="bento-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></div>
      </div>
    </a>

    {{-- 8. Ubicaciones --}}
    <a href="{{ $locationsUrl }}" class="bento-card col-span-1">
      <div class="bento-bg bg-globe-wrapper">
        <canvas id="globe-canvas" class="globe-canvas"></canvas>
        <div class="globe-fade-overlay"></div>
      </div>
      <div class="bento-inner">
        <div class="bento-content">
          <div class="bento-icon-wrapper">
            <div class="bento-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M12 21s6-5.2 6-11a6 6 0 1 0-12 0c0 5.8 6 11 6 11z"/>
                <circle cx="12" cy="10" r="2.5"/>
              </svg>
            </div>
          </div>
          <h3 class="bento-title">Ubicaciones</h3>
          <p class="bento-desc">
            <strong>{{ $fmt($usedLocations) }}</strong> usadas,
            <strong>{{ $fmt($availableLocations) }}</strong> libres,
            <strong>{{ $fmt($totalLocations) }}</strong> totales.
          </p>
        </div>
        <div class="bento-cta">Ver mapa <svg class="bento-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></div>
      </div>
    </a>

    {{-- 9. Heatmap --}}
    <a href="{{ $heatmapUrl }}" class="bento-card col-span-1">
      <div class="bento-bg bg-heatmap-anim"></div>
      <div class="bento-inner">
        <div class="bento-content">
          <div class="bento-icon-wrapper">
            <div class="bento-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <rect x="4" y="4" width="6" height="6" rx="1"/>
                <rect x="14" y="4" width="6" height="6" rx="1"/>
                <rect x="4" y="14" width="6" height="6" rx="1"/>
                <rect x="14" y="14" width="6" height="6" rx="1"/>
              </svg>
            </div>
          </div>
          <h3 class="bento-title">Heatmap 3D</h3>
          <p class="bento-desc">
            Visualización operativa de zonas y comportamiento de inventario.
          </p>
        </div>
        <div class="bento-cta">Abrir <svg class="bento-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></div>
      </div>
    </a>

    {{-- 10. Auditoría --}}
    <a href="{{ $auditUrl }}" class="bento-card col-span-2">
      <div class="bento-bg bg-log-scroll">
        <div class="log-lines">
          <div>[INFO] Eventos reales</div>
          <div>[INFO] Entradas: {{ $fmt($entryCount) }}</div>
          <div>[INFO] Salidas: {{ $fmt($exitCount) }}</div>
          <div>[INFO] Transferencias: {{ $fmt($transferCount) }}</div>
        </div>
      </div>
      <div class="bento-inner">
        <div class="bento-content">
          <div class="bento-icon-wrapper">
            <div class="bento-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M9 12l2 2 4-4"/>
                <path d="M12 3l7 3v6c0 5-3.5 7.5-7 9-3.5-1.5-7-4-7-9V6l7-3z"/>
              </svg>
            </div>
          </div>
          <h3 class="bento-title">Auditoría y Trazabilidad</h3>
          <p class="bento-desc">
            <strong>{{ $fmt($auditEventsCount) }}</strong> eventos registrados,
            <strong>{{ $fmt($entryCount) }}</strong> entradas,
            <strong>{{ $fmt($exitCount) }}</strong> salidas.
          </p>
        </div>
        <div class="bento-cta">Analizar <svg class="bento-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></div>
      </div>
    </a>

    {{-- 11. PDF --}}
    <a href="{{ $auditPdfUrl }}" class="bento-card col-span-1">
      <div class="bento-bg bg-document"></div>
      <div class="bento-inner">
        <div class="bento-content">
          <div class="bento-icon-wrapper">
            <div class="bento-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/>
                <path d="M14 3v5h5"/>
                <path d="M8 13h8"/>
                <path d="M8 17h5"/>
              </svg>
            </div>
          </div>
          <h3 class="bento-title">Reporte PDF</h3>
          <p class="bento-desc">Generar exportable con datos reales del panel.</p>
        </div>
        <div class="bento-cta">Generar <svg class="bento-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></div>
      </div>
    </a>

    {{-- 12. Layout --}}
    <a href="{{ $layoutUrl }}" class="bento-card col-span-3 card-layout">
      <div class="bento-bg bg-blueprint"></div>
      <div class="bento-inner layout-inner">
        <div class="bento-content">
          <div class="bento-icon-wrapper">
            <div class="bento-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <rect x="3" y="4" width="18" height="16" rx="2"/>
                <path d="M8 4v16"/>
                <path d="M16 4v16"/>
                <path d="M3 10h18"/>
              </svg>
            </div>
          </div>
          <h3 class="bento-title">Configuración de Layout</h3>
          <p class="bento-desc">
            Edita el plano físico, pasillos, racks y la estructura de la bodega.
          </p>
        </div>
        <div class="bento-cta">Editar <svg class="bento-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></div>
      </div>
    </a>

  </div>
</div>
@endsection

@push('scripts')
<script type="module">
  import createGlobe from 'https://cdn.skypack.dev/cobe';

  document.addEventListener('DOMContentLoaded', () => {
    let phi = 0;
    const canvas = document.getElementById("globe-canvas");

    if (canvas) {
      createGlobe(canvas, {
        devicePixelRatio: 2,
        width: 800,
        height: 800,
        phi: 0,
        theta: 0.3,
        dark: 0,
        diffuse: 0.6,
        mapSamples: 16000,
        mapBrightness: 0.8,
        baseColor: [1, 1, 1],
        markerColor: [251 / 255, 100 / 255, 21 / 255],
        glowColor: [1, 0.8, 0.6],
        markers: [
          { location: [19.6450, -99.2158], size: 0.12 },
          { location: [40.7128, -74.006], size: 0.08 },
          { location: [14.5995, 120.9842], size: 0.05 }
        ],
        onRender: (state) => {
          state.phi = phi;
          phi += 0.005;
          state.width = 800;
          state.height = 800;
        }
      });
      setTimeout(() => canvas.style.opacity = '1', 100);
    }
  });
</script>
@endpush

@push('styles')
<style>
  html, body {
    background: #f4f4f5 !important;
    font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  }

  .wms-shell { max-width: 1200px; margin: 0 auto; padding: 40px 24px 80px; }
  .wms-header { margin-bottom: 32px; }
  .wms-title { margin: 0; font-size: 2.25rem; font-weight: 800; letter-spacing: -0.04em; color: #09090b; }
  .wms-sub { margin: 8px 0 0; font-size: 1.1rem; color: #52525b; max-width: 600px; }

  .bento-grid {
    display: grid; width: 100%; grid-auto-rows: 20rem; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.25rem;
  }

  .col-span-1 { grid-column: span 1; }
  .col-span-2 { grid-column: span 2; }
  .col-span-3 { grid-column: span 3; }
  .card-layout { min-height: 16rem; grid-row: auto; }

  /* BENTO CARD BASE (Estructura central para el Hover Style) */
  .bento-card {
    position: relative; display: flex; flex-direction: column; justify-content: flex-end;
    overflow: hidden; border-radius: 28px; background: #ffffff;
    border: 1px solid rgba(0,0,0,0.04); box-shadow: 0 4px 20px rgba(0,0,0,0.03), 0 1px 3px rgba(0,0,0,0.02);
    text-decoration: none; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  }

  /* Animación general de elevación en hover */
  .bento-card:not(.card-analytics-light):hover {
    box-shadow: 0 10px 40px rgba(0,0,0,0.08), 0 4px 10px rgba(0,0,0,0.04);
    transform: translateY(-4px); border-color: rgba(0,0,0,0.08);
  }

  /* Estructura para separar el fondo de los textos */
  .bento-inner {
    padding: 1.75rem; position: relative; z-index: 10;
    background: linear-gradient(to top, #ffffff 40%, rgba(255,255,255,0.85) 75%, transparent 100%);
    height: 100%; display: flex; flex-direction: column; justify-content: flex-end;
  }

  .layout-inner { background: linear-gradient(to right, #ffffff 30%, rgba(255,255,255,0.9) 50%, transparent 100%); justify-content: center; }
  
  /* El contenido se desliza hacia arriba */
  .bento-content { display: flex; flex-direction: column; gap: 0.5rem; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); transform: translateY(0); transform-origin: left; pointer-events: none; }
  .bento-card:not(.card-analytics-light):hover .bento-content { transform: translateY(-2.2rem); }

  /* El icono se oscurece y se hace pequeño */
  .bento-icon-wrapper { width: 3.5rem; height: 3.5rem; background: #f4f4f5; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem; transition: all 0.4s ease; }
  .bento-card:not(.card-analytics-light):hover .bento-icon-wrapper { background: #18181b; }

  .bento-icon { width: 1.8rem; height: 1.8rem; color: #3f3f46; transition: all 0.4s ease; }
  .bento-card:not(.card-analytics-light):hover .bento-icon { color: #ffffff; transform: scale(0.9); }

  .bento-title { margin: 0; font-size: 1.3rem; font-weight: 700; color: #18181b; }
  .bento-desc { margin: 0; font-size: 0.95rem; color: #71717a; max-width: 90%; line-height: 1.5; }
  .bento-desc strong { color: #18181b; }

  /* Botón interactivo aparece */
  .bento-cta {
    position: absolute; bottom: 1.5rem; left: 1.75rem; display: flex; align-items: center; font-size: 0.9rem; font-weight: 600; color: #09090b; opacity: 0; transform: translateY(15px); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .bento-card:not(.card-analytics-light):hover .bento-cta { opacity: 1; transform: translateY(0); }
  .bento-arrow { width: 1.1rem; height: 1.1rem; margin-left: 0.4rem; transition: transform 0.2s ease; }
  .bento-card:not(.card-analytics-light):hover .bento-cta:hover .bento-arrow { transform: translateX(5px); }

  /* --- 1. ESTILOS INTÁCTOS DE TU TARJETA ANALYTICS --- */
  .card-analytics-light {
    background: #ffffff;
    padding: 0 !important;
  }
  .card-analytics-light:hover {
    transform: translateY(-4px); box-shadow: 0 15px 50px rgba(0,0,0,0.08); border-color: rgba(0,0,0,0.08);
  }

  .analytics-bg-grid-light {
    position: absolute; inset: 0; z-index: 0;
    background-image:
      linear-gradient(rgba(0,0,0,0.03) 1px, transparent 1px),
      linear-gradient(90deg, rgba(0,0,0,0.03) 1px, transparent 1px);
    background-size: 40px 40px;
    background-position: center top;
  }

  .analytics-layout {
    position: relative; z-index: 10; height: 100%; display: flex; flex-direction: column; justify-content: space-between; padding: 1.75rem;
  }

  .analytics-top-row { display: flex; justify-content: space-between; align-items: flex-start; }
  .trend-title-light { color: #18181b; font-size: 0.95rem; font-weight: 700; display: block; margin-bottom: 8px; }
  .trend-legend { display: flex; gap: 12px; }
  .leg-item-light { font-size: 0.8rem; color: #52525b; display: flex; align-items: center; gap: 6px; font-weight: 500; }
  .leg-item-light span { width: 12px; height: 3px; border-radius: 2px; }
  .c-cyan { background: #06b6d4; box-shadow: 0 0 8px rgba(6,182,212,0.5); }
  .c-orange { background: #f97316; box-shadow: 0 0 8px rgba(249,115,22,0.5); }

  .live-badge-light {
    background: rgba(34,197,94,0.1); color: #16a34a; border: 1px solid rgba(34,197,94,0.2);
    padding: 4px 10px; border-radius: 99px; font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; gap: 6px;
  }
  .live-badge-light svg { width: 14px; height: 14px; animation: pulse-opacity 2s infinite; }

  .chart-waves-container {
    position: absolute; top: 20%; left: 0; width: 100%; height: 45%; overflow: hidden; z-index: 1;
    mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
    -webkit-mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
  }
  .wave-layer {
    position: absolute; top: 0; left: 0; width: 200%; height: 100%;
    background-repeat: repeat-x; background-size: 50% 100%;
    animation: wave-slide 8s linear infinite;
  }
  .wave-cyan {
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 500 150' preserveAspectRatio='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0,75 C100,20 150,130 250,75 C350,20 400,130 500,75' fill='none' stroke='%2306b6d4' stroke-width='4'/%3E%3C/svg%3E");
    opacity: 0.9;
  }
  .wave-orange {
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 500 150' preserveAspectRatio='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0,90 C120,140 180,10 250,60 C320,110 380,10 500,60' fill='none' stroke='%23f97316' stroke-width='3'/%3E%3C/svg%3E");
    opacity: 0.9; animation-duration: 12s; animation-direction: reverse;
  }
  @keyframes wave-slide { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }

  .analytics-bottom-row { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; z-index: 10; }
  .analytics-kpi-main { flex: 1; }
  .light-icon { background: #f4f4f5; border: 1px solid rgba(0,0,0,0.05); color: #3f3f46; }

  .analytics-mini-bars { display: flex; flex-direction: column; gap: 8px; }
  .bars-title-light { font-size: 0.75rem; color: #71717a; font-weight: 600; text-align: center; }
  .bars-group { display: flex; gap: 16px; align-items: flex-end; height: 50px; }
  .bar-col { display: flex; gap: 3px; align-items: flex-end; height: 100%; position: relative; }
  .bar { width: 6px; background: #06b6d4; border-radius: 4px 4px 0 0; box-shadow: 0 0 8px rgba(6,182,212,0.3); animation: bar-bounce 2s ease-in-out infinite alternate; transform-origin: bottom; }
  .bar:nth-child(2) { background: #38bdf8; animation-delay: 0.3s; }
  .bar:nth-child(3) { background: #f97316; box-shadow: 0 0 8px rgba(249,115,22,0.3); animation-delay: 0.6s; }
  .h-2 { height: 40%; } .h-3 { height: 60%; } .h-4 { height: 80%; } .h-5 { height: 100%; }
  @keyframes bar-bounce { 0% { transform: scaleY(0.7); } 100% { transform: scaleY(1); } }

  .analytics-gauge { position: relative; width: 80px; height: 80px; }
  .circular-chart { display: block; margin: 0 auto; max-width: 100%; max-height: 250px; }
  .circle-bg-light { fill: none; stroke: rgba(0,0,0,0.06); stroke-width: 2.5; }
  .circle { fill: none; stroke-width: 2.5; stroke-linecap: round; animation: progress 1s ease-out forwards; }
  .cyan .circle { stroke: #06b6d4; filter: drop-shadow(0 0 4px rgba(6,182,212,0.4)); }
  .gauge-value-light { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 1.1rem; font-weight: 800; color: #18181b; }
  @keyframes progress { 0% { stroke-dasharray: 0 100; } }

  /* --- FONDOS ANIMADOS RESTANTES (MÁS VISIBLES Y EN EL FONDO) --- */
  .bg-globe-wrapper {
    position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 1; pointer-events: none;
    background: radial-gradient(circle at 50% 30%, transparent 20%, rgba(0,0,0,0.08) 80%);
    mask-image: none !important; -webkit-mask-image: none !important;
  }
  .globe-canvas {
    position: absolute; top: 15px; left: 50%; transform: translateX(-50%);
    width: 420px !important; height: 420px !important; opacity: 0; transition: opacity 1s ease; pointer-events: none;
  }
  .globe-fade-overlay {
    position: absolute; inset: 0;
    background: radial-gradient(circle at 50% 120%, rgba(0,0,0,0.25), rgba(255,255,255,0)); pointer-events: none;
  }

  .bento-bg {
    position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 1; pointer-events: none; overflow: hidden;
    /* Difuminado mucho más abajo (50%) para que los fondos se noten en la mitad superior de la tarjeta */
    mask-image: linear-gradient(to bottom, black 50%, transparent 100%);
    -webkit-mask-image: linear-gradient(to bottom, black 50%, transparent 100%);
  }

  .bg-marquee { display: flex; justify-content: flex-end; padding-top: 1rem; padding-right: 1.5rem; opacity: 0.8; }
  .marquee-track { display: flex; flex-direction: column; gap: 0.75rem; width: 120px; animation: scroll-up 8s linear infinite; }
  .fake-item { background: #fafafa; border: 1px solid #f4f4f5; padding: 0.75rem; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
  .fake-item span { display: block; font-size: 0.65rem; font-weight: 700; color: #71717a; margin-bottom: 4px; }
  .fake-line { height: 4px; width: 100%; background: #e4e4e7; border-radius: 4px; }
  @keyframes scroll-up { 0% { transform: translateY(0); } 100% { transform: translateY(-50%); } }

  /* --- RADAR: Líneas más gruesas, color más sólido --- */
  .bg-radar { display: flex; align-items: flex-start; justify-content: flex-end; padding: 2rem; }
  .radar-sweep { width: 100px; height: 100px; border-radius: 50%; border: 2px solid rgba(0,0,0,0.1); position: relative; }
  .radar-sweep::before, .radar-sweep::after { 
    content: ''; position: absolute; inset: 0; border-radius: 50%; 
    border: 3px solid rgba(34,197,94,0.8); 
    animation: pulse-ring 3s cubic-bezier(0.215, 0.61, 0.355, 1) infinite; 
  }
  .radar-sweep::after { animation-delay: 1.5s; }
  @keyframes pulse-ring {
    0% { transform: scale(0.6); opacity: 0.9; }
    100% { transform: scale(1.8); opacity: 0; }
  }

  /* --- BEAMS: Menos blur, más opacidad --- */
  .bg-beams .beam {
    position: absolute; width: 180px; height: 180px; border-radius: 999px; filter: blur(14px); opacity: 0.8;
  }
  .beam-1 { top: 30px; right: 60px; background: #3b82f6; }
  .beam-2 { top: 90px; right: 10px; background: #60a5fa; }

  /* --- DOTS: Puntos más grandes y opacos --- */
  .bg-dots-move {
    background-image: radial-gradient(circle, rgba(59,130,246,0.5) 2px, transparent 2px);
    background-size: 22px 22px;
    animation: dotsMove 8s linear infinite;
  }
  @keyframes dotsMove {
    from { transform: translateY(0); }
    to { transform: translateY(22px); }
  }

  /* --- WAVES: Gradientes más duros --- */
  .bg-waves {
    background:
      radial-gradient(circle at 20% 20%, rgba(59,130,246,0.4), transparent 40%),
      radial-gradient(circle at 80% 30%, rgba(14,165,233,0.4), transparent 40%),
      radial-gradient(circle at 50% 80%, rgba(99,102,241,0.3), transparent 40%);
  }

  /* --- SCANLINE: Barra sólida con sombra brillante --- */
  .bg-scanline .scan-bar {
    position: absolute; top: 0; left: 10%; width: 80%; height: 4px; background: rgba(34,197,94,1);
    box-shadow: 0 0 20px rgba(34,197,94,0.8);
    animation: scanMove 2.2s linear infinite;
  }
  @keyframes scanMove {
    0% { top: 8%; opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { top: 82%; opacity: 0; }
  }

  /* --- HEATMAP: Colores más densos --- */
  .bg-heatmap-anim {
    background:
      radial-gradient(circle at 25% 30%, rgba(239,68,68,0.5), transparent 30%),
      radial-gradient(circle at 70% 40%, rgba(249,115,22,0.5), transparent 35%),
      radial-gradient(circle at 50% 70%, rgba(234,179,8,0.4), transparent 35%);
    animation: heatPulse 4s ease-in-out infinite alternate;
  }
  @keyframes heatPulse {
    from { transform: scale(1); opacity: 0.85; }
    to { transform: scale(1.04); opacity: 1; }
  }

  /* --- LOG SCROLL: Texto más oscuro --- */
  .bg-log-scroll { display: flex; align-items: flex-start; justify-content: flex-end; padding: 1.5rem; opacity: 1; }
  .log-lines {
    font-size: 0.8rem; color: #475569; font-weight: 600; font-family: monospace; display: flex; flex-direction: column; gap: 0.5rem;
    animation: logFloat 8s linear infinite;
  }
  @keyframes logFloat {
    from { transform: translateY(0); }
    to { transform: translateY(-20px); }
  }

  /* --- DOCUMENT: Líneas más definidas --- */
  .bg-document {
    background:
      linear-gradient(180deg, rgba(0,0,0,0.04), rgba(0,0,0,0.02)),
      repeating-linear-gradient(180deg, rgba(0,0,0,0.08) 0 1px, transparent 1px 16px);
    opacity: 1;
  }

  /* --- BLUEPRINT: Cuadrícula azul notoria --- */
  .bg-blueprint {
    background-image:
      linear-gradient(rgba(59,130,246,0.25) 1px, transparent 1px),
      linear-gradient(90deg, rgba(59,130,246,0.25) 1px, transparent 1px);
    background-size: 24px 24px;
  }

  @media (max-width: 1024px) {
    .bento-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .col-span-3 { grid-column: span 2; }
  }

  @media (max-width: 720px) {
    .wms-shell { padding: 24px 16px 56px; }
    .bento-grid { grid-template-columns: 1fr; grid-auto-rows: 18rem; }
    .col-span-1, .col-span-2, .col-span-3 { grid-column: span 1; }
    .analytics-bottom-row { gap: 14px; }
    .wms-title { font-size: 1.8rem; }
    .wms-sub { font-size: 0.98rem; }
    .globe-canvas { width: 300px !important; height: 300px !important; }
  }
</style>
@endpush