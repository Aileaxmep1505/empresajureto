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
  $fastFlowBoxes             = (int) ($fastFlowBoxes ?? 0);
  $todayMovementsCount       = (int) ($todayMovementsCount ?? 0);
  $entryCount                = (int) ($entryCount ?? 0);
  $exitCount                 = (int) ($exitCount ?? 0);
  $auditEventsCount          = (int) ($auditEventsCount ?? 0);
  $transferCount             = (int) ($transferCount ?? 0);

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
  $fastFlowUrl    = $routeFirst(['admin.wms.fastflow.index', 'admin.wms.fast-flow', 'admin.wms.fastflow']);
  $fastFlowNewUrl = $routeFirst(['admin.wms.fastflow.create', 'admin.wms.fast-flow.create']);
  $analyticsUrl   = $routeFirst(['admin.wms.analytics.v2', 'admin.wms.analytics']);
  $auditUrl       = $routeFirst(['admin.wms.audit', 'admin.wms.audit.index']);
  $auditPdfUrl    = $routeFirst(['admin.wms.audit.pdf']);
  $homeUrl        = $routeFirst(['admin.wms.home']);
@endphp

<div class="wms-shell">
  <div class="wms-header">
    <div>
      <h1 class="wms-title">WMS · Bodega</h1>
      <p class="wms-sub">
        Control de inventario, operaciones, picking, embarque, Fast Flow, trazabilidad y análisis del almacén en un solo panel.
      </p>
      <div class="wms-header-meta">
        <span>{{ $warehouseName }}</span>
        <span>{{ $fmt($productsCount) }} productos</span>
        <span>{{ $fmt($totalLocations) }} ubicaciones</span>
        <span>{{ $fmt($shipmentCount) }} embarques</span>
        <span>{{ $occupancyRate }}% ocupación</span>
      </div>
    </div>

    <div class="wms-header-actions">
      <a href="{{ $homeUrl }}" class="wms-btn wms-btn-ghost">
        <span class="wms-btn-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 11l9-8 9 8"/>
            <path d="M5 10v10h14V10"/>
          </svg>
        </span>
        <span>Inicio</span>
      </a>

      <a href="{{ $shippingUrl }}" class="wms-btn wms-btn-ghost">
        <span class="wms-btn-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 7h13l3 4v6a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7z"/>
            <path d="M16 7V5a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v2"/>
            <circle cx="8.5" cy="18.5" r="1.5"/>
            <circle cx="15.5" cy="18.5" r="1.5"/>
          </svg>
        </span>
        <span>Embarques</span>
      </a>

      <a href="{{ $analyticsUrl }}" class="wms-btn wms-btn-primary">
        <span class="wms-btn-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 19V5"/>
            <path d="M8 19v-6"/>
            <path d="M12 19v-10"/>
            <path d="M16 19v-3"/>
            <path d="M20 19V8"/>
          </svg>
        </span>
        <span>Ver métricas</span>
      </a>
    </div>
  </div>

  <div class="wms-layout">
    {{-- Card principal --}}
    <a href="{{ $searchUrl }}" class="wms-card wms-card-main">
      <div class="wms-card-badge wms-badge-blue" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M3 7l9-4 9 4-9 4-9-4z"/>
          <path d="M3 7v10l9 4 9-4V7"/>
          <path d="M12 11v10"/>
        </svg>
      </div>

      <div class="wms-card-body">
        <div class="wms-card-kicker">Inventario y consulta</div>
        <h2 class="wms-card-title">Buscar producto</h2>
        <p class="wms-card-text">
          Consulta productos por nombre, SKU o escaneo, revisa ubicaciones, stock disponible, movimientos,
          cajas activas, estado operativo y trazabilidad del inventario en tiempo real.
        </p>

        <div class="wms-card-stats">
          <div class="wms-mini-stat">
            <small>Productos</small>
            <strong>{{ $fmt($productsCount) }}</strong>
          </div>
          <div class="wms-mini-stat">
            <small>Stock total</small>
            <strong>{{ $fmt($totalStock) }}</strong>
          </div>
          <div class="wms-mini-stat">
            <small>Stock bajo</small>
            <strong>{{ $fmt($lowStockCount) }}</strong>
          </div>
          <div class="wms-mini-stat">
            <small>Ubicaciones</small>
            <strong>{{ $fmt($totalLocations) }}</strong>
          </div>
        </div>
      </div>

      <div class="wms-card-link">
        <span>Explorar módulo</span>
        <span class="wms-card-link-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 5l7 7-7 7"/>
            <path d="M5 12h10"/>
          </svg>
        </span>
      </div>
    </a>

    {{-- Picking --}}
    <a href="{{ $pickingUrl }}" class="wms-card wms-card-medium">
      <div class="wms-card-badge wms-badge-green" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 7h16"/>
          <path d="M4 12h10"/>
          <path d="M4 17h7"/>
          <path d="M18 15l2 2 4-4"/>
        </svg>
      </div>

      <div class="wms-card-body">
        <div class="wms-card-kicker">Operación</div>
        <h2 class="wms-card-title">Picking</h2>
        <p class="wms-card-text">
          Controla tareas, fases, prioridades, avance, responsables y surtido operativo para dejar pedidos listos para embarque.
        </p>

        <div class="wms-card-inline-stats">
          <span>Pendientes: <strong>{{ $fmt($pendingPickingCount) }}</strong></span>
          <span>Proceso: <strong>{{ $fmt($inProgressPickingCount) }}</strong></span>
          <span>Completadas: <strong>{{ $fmt($completedPickingCount) }}</strong></span>
        </div>
      </div>

      <div class="wms-card-link">
        <span>Acceder</span>
        <span class="wms-card-link-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 5l7 7-7 7"/>
            <path d="M5 12h10"/>
          </svg>
        </span>
      </div>
    </a>

    {{-- Embarque --}}
    <a href="{{ $shippingUrl }}" class="wms-card wms-card-medium">
      <div class="wms-card-badge wms-badge-slate" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M3 7h13l3 4v6a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7z"/>
          <path d="M16 7V5a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v2"/>
          <circle cx="8.5" cy="18.5" r="1.5"/>
          <circle cx="15.5" cy="18.5" r="1.5"/>
        </svg>
      </div>

      <div class="wms-card-body">
        <div class="wms-card-kicker">Salida y despacho</div>
        <h2 class="wms-card-title">Embarque</h2>
        <p class="wms-card-text">
          Valida la carga contra picking, escanea cajas o productos, detecta faltantes, registra motivo, firma y salida de unidad.
        </p>

        <div class="wms-card-inline-stats">
          <span>Borrador: <strong>{{ $fmt($draftShipmentCount) }}</strong></span>
          <span>Cargando: <strong>{{ $fmt($loadingShipmentCount) }}</strong></span>
          <span>Despachados: <strong>{{ $fmt($dispatchedShipmentCount) }}</strong></span>
        </div>
      </div>

      <div class="wms-card-link">
        <span>Acceder</span>
        <span class="wms-card-link-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 5l7 7-7 7"/>
            <path d="M5 12h10"/>
          </svg>
        </span>
      </div>
    </a>

    {{-- Fast Flow --}}
    <a href="{{ $fastFlowUrl }}" class="wms-card wms-card-medium">
      <div class="wms-card-badge wms-badge-orange" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M3 12h11"/>
          <path d="M3 7h8"/>
          <path d="M3 17h13"/>
          <path d="M14 12l4-4"/>
          <path d="M14 12l4 4"/>
        </svg>
      </div>

      <div class="wms-card-body">
        <div class="wms-card-kicker">Alta rotación</div>
        <h2 class="wms-card-title">Fast Flow</h2>
        <p class="wms-card-text">
          Gestiona lotes rápidos, cajas activas, stock dinámico y flujo operativo de salida.
        </p>

        <div class="wms-card-inline-stats">
          <span>Lotes: <strong>{{ $fmt($fastFlowCount) }}</strong></span>
          <span>Cajas: <strong>{{ $fmt($fastFlowBoxes) }}</strong></span>
        </div>
      </div>

      <div class="wms-card-link">
        <span>Acceder</span>
        <span class="wms-card-link-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 5l7 7-7 7"/>
            <path d="M5 12h10"/>
          </svg>
        </span>
      </div>
    </a>

    {{-- Escáner --}}
    <a href="{{ $scannerUrl }}" class="wms-card wms-card-small">
      <div class="wms-card-badge wms-badge-cyan" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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

      <div class="wms-card-body">
        <div class="wms-card-kicker">Tiempo real</div>
        <h2 class="wms-card-title">Escáner</h2>
        <p class="wms-card-text">
          Escaneo rápido para picking, validación operativa y flujo de operación en piso.
        </p>
      </div>

      <div class="wms-card-link">
        <span>Entrar</span>
        <span class="wms-card-link-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 5l7 7-7 7"/>
            <path d="M5 12h10"/>
          </svg>
        </span>
      </div>
    </a>

    {{-- Ubicaciones --}}
    <a href="{{ $locationsUrl }}" class="wms-card wms-card-small">
      <div class="wms-card-badge wms-badge-indigo" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 21s6-5.2 6-11a6 6 0 1 0-12 0c0 5.8 6 11 6 11z"/>
          <circle cx="12" cy="10" r="2.5"/>
        </svg>
      </div>

      <div class="wms-card-body">
        <div class="wms-card-kicker">Mapa físico</div>
        <h2 class="wms-card-title">Ubicaciones</h2>
        <p class="wms-card-text">
          Revisa racks, niveles, espacios, capacidad y ocupación por ubicación.
        </p>

        <div class="wms-card-inline-stats">
          <span>Usadas: <strong>{{ $fmt($usedLocations) }}</strong></span>
          <span>Libres: <strong>{{ $fmt($availableLocations) }}</strong></span>
        </div>
      </div>

      <div class="wms-card-link">
        <span>Ver</span>
        <span class="wms-card-link-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 5l7 7-7 7"/>
            <path d="M5 12h10"/>
          </svg>
        </span>
      </div>
    </a>

    {{-- Heatmap --}}
    <a href="{{ $heatmapUrl }}" class="wms-card wms-card-small">
      <div class="wms-card-badge wms-badge-purple" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="4" y="4" width="6" height="6" rx="1"/>
          <rect x="14" y="4" width="6" height="6" rx="1"/>
          <rect x="4" y="14" width="6" height="6" rx="1"/>
          <rect x="14" y="14" width="6" height="6" rx="1"/>
        </svg>
      </div>

      <div class="wms-card-body">
        <div class="wms-card-kicker">Vista visual</div>
        <h2 class="wms-card-title">Heatmap 3D</h2>
        <p class="wms-card-text">
          Visualiza ocupación, racks, pasillos y zonas operativas del almacén.
        </p>
      </div>

      <div class="wms-card-link">
        <span>Abrir</span>
        <span class="wms-card-link-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 5l7 7-7 7"/>
            <path d="M5 12h10"/>
          </svg>
        </span>
      </div>
    </a>

    {{-- Analytics --}}
    <a href="{{ $analyticsUrl }}" class="wms-card wms-card-wide">
      <div class="wms-card-badge wms-badge-purple" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 19V5"/>
          <path d="M8 19v-6"/>
          <path d="M12 19v-10"/>
          <path d="M16 19v-3"/>
          <path d="M20 19V8"/>
        </svg>
      </div>

      <div class="wms-card-body">
        <div class="wms-card-kicker">Indicadores</div>
        <h2 class="wms-card-title">Analytics</h2>
        <p class="wms-card-text">
          KPIs, actividad, entradas, salidas, stock bajo, ocupación, embarques y visión general del almacén.
        </p>

        <div class="wms-card-stats wms-card-stats-3">
          <div class="wms-mini-stat">
            <small>Movimientos</small>
            <strong>{{ $fmt($todayMovementsCount) }}</strong>
          </div>
          <div class="wms-mini-stat">
            <small>Entradas</small>
            <strong>{{ $fmt($entryCount) }}</strong>
          </div>
          <div class="wms-mini-stat">
            <small>Salidas</small>
            <strong>{{ $fmt($exitCount) }}</strong>
          </div>
        </div>
      </div>

      <div class="wms-card-link">
        <span>Ver métricas</span>
        <span class="wms-card-link-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 5l7 7-7 7"/>
            <path d="M5 12h10"/>
          </svg>
        </span>
      </div>
    </a>

    {{-- Auditoría --}}
    <a href="{{ $auditUrl }}" class="wms-card wms-card-small">
      <div class="wms-card-badge wms-badge-rose" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M9 12l2 2 4-4"/>
          <path d="M12 3l7 3v6c0 5-3.5 7.5-7 9-3.5-1.5-7-4-7-9V6l7-3z"/>
        </svg>
      </div>

      <div class="wms-card-body">
        <div class="wms-card-kicker">Trazabilidad</div>
        <h2 class="wms-card-title">Auditoría</h2>
        <p class="wms-card-text">
          Revisión de movimientos, actividad y hallazgos operativos con análisis inteligente.
        </p>

        <div class="wms-card-inline-stats">
          <span>Eventos: <strong>{{ $fmt($auditEventsCount) }}</strong></span>
        </div>
      </div>

      <div class="wms-card-link">
        <span>Analizar</span>
        <span class="wms-card-link-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 5l7 7-7 7"/>
            <path d="M5 12h10"/>
          </svg>
        </span>
      </div>
    </a>

    {{-- PDF --}}
    <a href="{{ $auditPdfUrl }}" class="wms-card wms-card-small">
      <div class="wms-card-badge wms-badge-slate" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/>
          <path d="M14 3v5h5"/>
          <path d="M8 13h8"/>
          <path d="M8 17h5"/>
        </svg>
      </div>

      <div class="wms-card-body">
        <div class="wms-card-kicker">Documento</div>
        <h2 class="wms-card-title">Reporte PDF</h2>
        <p class="wms-card-text">
          Genera resumen exportable de auditoría y trazabilidad del almacén.
        </p>
      </div>

      <div class="wms-card-link">
        <span>Generar</span>
        <span class="wms-card-link-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 5l7 7-7 7"/>
            <path d="M5 12h10"/>
          </svg>
        </span>
      </div>
    </a>

    {{-- Layout --}}
    <a href="{{ $layoutUrl }}" class="wms-card wms-card-small">
      <div class="wms-card-badge wms-badge-teal" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="4" width="18" height="16" rx="2"/>
          <path d="M8 4v16"/>
          <path d="M16 4v16"/>
          <path d="M3 10h18"/>
        </svg>
      </div>

      <div class="wms-card-body">
        <div class="wms-card-kicker">Configuración</div>
        <h2 class="wms-card-title">Layout</h2>
        <p class="wms-card-text">
          Edita zonas, distribución visual, racks, pasillos y estructura operativa.
        </p>
      </div>

      <div class="wms-card-link">
        <span>Editar</span>
        <span class="wms-card-link-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 5l7 7-7 7"/>
            <path d="M5 12h10"/>
          </svg>
        </span>
      </div>
    </a>

    {{-- Nuevo Fast Flow --}}
    <a href="{{ $fastFlowNewUrl }}" class="wms-card wms-card-small">
      <div class="wms-card-badge wms-badge-amber" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 5v14"/>
          <path d="M5 12h14"/>
          <rect x="3" y="3" width="18" height="18" rx="3"/>
        </svg>
      </div>

      <div class="wms-card-body">
        <div class="wms-card-kicker">Registro</div>
        <h2 class="wms-card-title">Alta Fast Flow</h2>
        <p class="wms-card-text">
          Registra cajas, lotes rápidos y genera etiquetas con QR para operación.
        </p>
      </div>

      <div class="wms-card-link">
        <span>Crear</span>
        <span class="wms-card-link-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 5l7 7-7 7"/>
            <path d="M5 12h10"/>
          </svg>
        </span>
      </div>
    </a>
  </div>
</div>
@endsection

@push('styles')
<style>
  :root{
    --wms-page-bg:#f5f6fb;
    --wms-card-bg:#ffffff;
    --wms-card-bg-soft:#fafbff;
    --wms-ink:#111827;
    --wms-muted:#6b7280;
    --wms-line:#e5e7f2;
    --wms-shadow:0 22px 60px rgba(15,23,42,.10);
    --wms-radius:26px;

    --wms-blue:#2563eb;
    --wms-green:#16a34a;
    --wms-purple:#8b5cf6;
    --wms-orange:#ea580c;
    --wms-cyan:#0891b2;
    --wms-indigo:#4f46e5;
    --wms-rose:#e11d48;
    --wms-slate:#475569;
    --wms-teal:#0f766e;
    --wms-amber:#d97706;
  }

  .wms-shell{
    max-width:1400px;
    margin:0 auto;
    padding:22px 22px 30px;
    background:var(--wms-page-bg);
    border-radius:28px;
    box-shadow:0 18px 40px rgba(15,23,42,.06);
  }

  .wms-header{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:16px;
    flex-wrap:wrap;
    margin-bottom:18px;
  }

  .wms-title{
    margin:0;
    font-size:1.7rem;
    font-weight:800;
    letter-spacing:-.03em;
    color:var(--wms-ink);
  }

  .wms-sub{
    margin:6px 0 0;
    font-size:.96rem;
    color:var(--wms-muted);
    max-width:900px;
  }

  .wms-header-meta{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-top:14px;
  }

  .wms-header-meta span{
    display:inline-flex;
    align-items:center;
    min-height:34px;
    padding:0 12px;
    border-radius:999px;
    background:#fff;
    border:1px solid var(--wms-line);
    color:#374151;
    font-size:.83rem;
    font-weight:700;
  }

  .wms-header-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
  }

  .wms-btn{
    border-radius:999px;
    padding:10px 15px;
    font-size:.92rem;
    font-weight:700;
    display:inline-flex;
    align-items:center;
    gap:8px;
    text-decoration:none;
    cursor:pointer;
    border:1px solid transparent;
    transition:
      transform .16s ease,
      box-shadow .16s ease,
      background .18s ease,
      border-color .18s ease,
      filter .18s ease;
  }

  .wms-btn-ico{
    width:18px;
    height:18px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
  }

  .wms-btn-ico svg{
    width:18px;
    height:18px;
  }

  .wms-btn-primary{
    background:linear-gradient(135deg,var(--wms-blue),#1d4ed8);
    color:#eff6ff;
    box-shadow:0 14px 30px rgba(37,99,235,.30);
  }

  .wms-btn-primary:hover{
    transform:translateY(-1px);
    box-shadow:0 18px 40px rgba(37,99,235,.38);
    filter:brightness(1.05);
  }

  .wms-btn-ghost{
    background:#ffffff;
    border-color:var(--wms-line);
    color:var(--wms-ink);
  }

  .wms-btn-ghost:hover{
    transform:translateY(-1px);
    box-shadow:0 12px 26px rgba(15,23,42,.08);
  }

  .wms-top-stats{
    display:grid;
    grid-template-columns:repeat(6, minmax(0,1fr));
    gap:12px;
    margin-bottom:18px;
  }

  .wms-stat-pill{
    background:#fff;
    border:1px solid var(--wms-line);
    border-radius:18px;
    padding:14px 14px 13px;
    box-shadow:0 10px 24px rgba(15,23,42,.04);
  }

  .wms-stat-pill small{
    display:block;
    font-size:.78rem;
    color:var(--wms-muted);
    margin-bottom:6px;
    font-weight:700;
  }

  .wms-stat-pill strong{
    display:block;
    color:var(--wms-ink);
    font-size:1.15rem;
    line-height:1;
    font-weight:800;
  }

  .wms-layout{
    display:grid;
    grid-template-columns:repeat(4, minmax(0,1fr));
    gap:18px;
  }

  .wms-card{
    position:relative;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    min-height:220px;
    padding:22px 22px 20px;
    border-radius:var(--wms-radius);
    background:
      radial-gradient(circle at 0 0, rgba(255,255,255,.88) 0, transparent 55%),
      radial-gradient(circle at 100% 0, rgba(148,163,253,.10) 0, transparent 55%),
      var(--wms-card-bg-soft);
    border:1px solid var(--wms-line);
    box-shadow:0 14px 34px rgba(15,23,42,.04);
    text-decoration:none;
    overflow:hidden;
    transition:
      transform .2s ease,
      box-shadow .2s ease,
      border-color .18s ease,
      background .2s ease;
  }

  .wms-card::after{
    content:"";
    position:absolute;
    inset:-1px;
    background:radial-gradient(circle at 0 0, rgba(59,130,246,.16), transparent 55%);
    opacity:0;
    transition:opacity .25s ease;
    pointer-events:none;
  }

  .wms-card:hover{
    transform:translateY(-3px);
    box-shadow:0 22px 60px rgba(15,23,42,.14);
    border-color:#d4ddff;
    background:var(--wms-card-bg);
  }

  .wms-card:hover::after{
    opacity:1;
  }

  .wms-card-main{
    grid-column:span 2;
    grid-row:span 2;
    min-height:458px;
  }

  .wms-card-medium{
    min-height:220px;
  }

  .wms-card-wide{
    grid-column:span 2;
    min-height:220px;
  }

  .wms-card-small{
    min-height:220px;
  }

  .wms-card-badge{
    width:56px;
    height:56px;
    border-radius:20px;
    display:flex;
    align-items:center;
    justify-content:center;
    margin-bottom:18px;
    box-shadow:0 14px 30px rgba(15,23,42,.10);
  }

  .wms-card-badge svg{
    width:27px;
    height:27px;
  }

  .wms-badge-blue{
    background:linear-gradient(135deg,#e0ebff,#eff4ff);
    color:var(--wms-blue);
    border:1px solid #c7d2fe;
  }

  .wms-badge-green{
    background:linear-gradient(135deg,#dcfce7,#f0fdf4);
    color:var(--wms-green);
    border:1px solid #bbf7d0;
  }

  .wms-badge-purple{
    background:linear-gradient(135deg,#ede9fe,#faf5ff);
    color:var(--wms-purple);
    border:1px solid #e9d5ff;
  }

  .wms-badge-orange{
    background:linear-gradient(135deg,#ffedd5,#fff7ed);
    color:var(--wms-orange);
    border:1px solid #fdba74;
  }

  .wms-badge-cyan{
    background:linear-gradient(135deg,#cffafe,#ecfeff);
    color:var(--wms-cyan);
    border:1px solid #a5f3fc;
  }

  .wms-badge-indigo{
    background:linear-gradient(135deg,#e0e7ff,#eef2ff);
    color:var(--wms-indigo);
    border:1px solid #c7d2fe;
  }

  .wms-badge-rose{
    background:linear-gradient(135deg,#ffe4e6,#fff1f2);
    color:var(--wms-rose);
    border:1px solid #fecdd3;
  }

  .wms-badge-slate{
    background:linear-gradient(135deg,#e2e8f0,#f8fafc);
    color:var(--wms-slate);
    border:1px solid #cbd5e1;
  }

  .wms-badge-teal{
    background:linear-gradient(135deg,#ccfbf1,#f0fdfa);
    color:var(--wms-teal);
    border:1px solid #99f6e4;
  }

  .wms-badge-amber{
    background:linear-gradient(135deg,#fef3c7,#fffbeb);
    color:var(--wms-amber);
    border:1px solid #fde68a;
  }

  .wms-card-body{
    max-width:100%;
  }

  .wms-card-kicker{
    font-size:.75rem;
    font-weight:800;
    letter-spacing:.08em;
    text-transform:uppercase;
    color:#7c8aa5;
    margin-bottom:8px;
  }

  .wms-card-title{
    margin:0 0 8px;
    font-size:1.18rem;
    font-weight:800;
    color:var(--wms-ink);
    letter-spacing:-.02em;
  }

  .wms-card-main .wms-card-title{
    font-size:1.55rem;
  }

  .wms-card-text{
    margin:0;
    font-size:.95rem;
    color:var(--wms-muted);
    line-height:1.55;
  }

  .wms-card-stats{
    display:grid;
    grid-template-columns:repeat(2, minmax(0,1fr));
    gap:10px;
    margin-top:18px;
  }

  .wms-card-stats-3{
    grid-template-columns:repeat(3, minmax(0,1fr));
  }

  .wms-mini-stat{
    padding:12px 12px 11px;
    border-radius:16px;
    background:#fff;
    border:1px solid #e8ebf5;
  }

  .wms-mini-stat small{
    display:block;
    color:#7a8598;
    font-size:.76rem;
    margin-bottom:6px;
    font-weight:700;
  }

  .wms-mini-stat strong{
    display:block;
    color:#111827;
    font-size:1rem;
    font-weight:800;
    line-height:1;
  }

  .wms-card-inline-stats{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-top:16px;
  }

  .wms-card-inline-stats span{
    display:inline-flex;
    align-items:center;
    min-height:34px;
    padding:0 12px;
    border-radius:999px;
    background:#fff;
    border:1px solid #e7eaf4;
    color:#5f6b80;
    font-size:.82rem;
    font-weight:700;
  }

  .wms-card-inline-stats strong{
    margin-left:4px;
    color:#111827;
  }

  .wms-card-link{
    margin-top:22px;
    display:inline-flex;
    align-items:center;
    gap:6px;
    font-size:.92rem;
    font-weight:700;
    color:#6b7280;
  }

  .wms-card-link-ico{
    width:16px;
    height:16px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
  }

  .wms-card-link-ico svg{
    width:16px;
    height:16px;
    transition:transform .2s ease;
  }

  .wms-card:hover .wms-card-link-ico svg{
    transform:translateX(4px);
  }

  @media (max-width: 1200px){
    .wms-top-stats{
      grid-template-columns:repeat(3, minmax(0,1fr));
    }

    .wms-layout{
      grid-template-columns:repeat(2, minmax(0,1fr));
    }

    .wms-card-main,
    .wms-card-wide{
      grid-column:span 2;
    }

    .wms-card-stats-3{
      grid-template-columns:repeat(3, minmax(0,1fr));
    }
  }

  @media (max-width: 760px){
    .wms-shell{
      margin:0 8px;
      padding:16px 14px 22px;
      border-radius:20px;
    }

    .wms-top-stats{
      grid-template-columns:repeat(2, minmax(0,1fr));
    }

    .wms-layout{
      grid-template-columns:1fr;
    }

    .wms-card-main,
    .wms-card-wide{
      grid-column:auto;
      grid-row:auto;
      min-height:unset;
    }

    .wms-card{
      min-height:unset;
    }

    .wms-card-stats,
    .wms-card-stats-3{
      grid-template-columns:1fr;
    }
  }
</style>
@endpush