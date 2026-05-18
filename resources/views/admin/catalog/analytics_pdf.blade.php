<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Reporte de inventario Jureto</title>
  <style>
    *{
      box-sizing:border-box;
    }

    body{
      font-family:DejaVu Sans, sans-serif;
      color:#111111;
      margin:18px;
      font-size:10px;
    }

    .head{
      border-bottom:1px solid #ebebeb;
      padding-bottom:10px;
      margin-bottom:12px;
    }

    h1{
      margin:0;
      font-size:22px;
      letter-spacing:-.5px;
    }

    .subtitle{
      margin:5px 0 0;
      color:#666666;
      font-size:11px;
    }

    .kpis{
      width:100%;
      border-collapse:separate;
      border-spacing:8px;
      margin:0 -8px 10px;
    }

    .kpi{
      border:1px solid #ebebeb;
      border-radius:12px;
      padding:12px;
      background:#ffffff;
    }

    .kpi-label{
      color:#888888;
      font-size:9px;
      font-weight:bold;
      margin-bottom:5px;
    }

    .kpi-value{
      font-size:18px;
      font-weight:bold;
      color:#111111;
    }

    .grid{
      width:100%;
      border-collapse:separate;
      border-spacing:8px;
      margin:0 -8px 8px;
    }

    .panel{
      border:1px solid #ebebeb;
      border-radius:12px;
      padding:12px;
      vertical-align:top;
      background:#ffffff;
    }

    h2{
      margin:0 0 4px;
      font-size:13px;
    }

    .muted{
      color:#888888;
      font-size:9px;
      margin-bottom:10px;
    }

    .bar-row{
      margin-bottom:8px;
    }

    .bar-top{
      width:100%;
      font-size:9px;
      margin-bottom:3px;
    }

    .bar-name{
      display:inline-block;
      width:72%;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
      font-weight:bold;
    }

    .bar-value{
      display:inline-block;
      width:25%;
      text-align:right;
      font-weight:bold;
    }

    .track{
      height:8px;
      background:#f3f4f6;
      border-radius:20px;
      overflow:hidden;
    }

    .fill{
      height:8px;
      background:#007aff;
      border-radius:20px;
    }

    table.data{
      width:100%;
      border-collapse:collapse;
      margin-top:6px;
    }

    table.data th,
    table.data td{
      border-bottom:1px solid #ebebeb;
      padding:6px 4px;
      text-align:left;
      vertical-align:top;
    }

    table.data th{
      font-size:8px;
      color:#111111;
      background:#f9fafb;
    }

    table.data td{
      font-size:8.5px;
    }

    .danger{
      color:#ff4a4a;
      font-weight:bold;
    }

    .success{
      color:#15803d;
      font-weight:bold;
    }

    .pill{
      display:inline-block;
      padding:3px 7px;
      border-radius:20px;
      font-size:8px;
      font-weight:bold;
    }

    .pill-blue{
      background:#e6f0ff;
      color:#007aff;
    }

    .pill-red{
      background:#ffebeb;
      color:#ff4a4a;
    }

    .page-break{
      page-break-before:always;
    }
  </style>
</head>
<body>
@php
  $maxTopStock = max(1, (float) $topStock->max(fn($it) => (float)($it->stock ?? 0)));
  $maxCategoryStock = max(1, (float) $categoryStats->max('stock'));
@endphp

<div class="head">
  <h1>Reporte profesional de inventario</h1>
  <div class="subtitle">
    Generado el {{ now()->format('d/m/Y H:i') }} · Inventario Jureto
  </div>
</div>

<table class="kpis">
  <tr>
    <td class="kpi">
      <div class="kpi-label">VALOR TOTAL</div>
      <div class="kpi-value">${{ number_format($summary['total_money'], 2) }}</div>
    </td>
    <td class="kpi">
      <div class="kpi-label">PRODUCTOS</div>
      <div class="kpi-value">{{ number_format($summary['total_products']) }}</div>
    </td>
    <td class="kpi">
      <div class="kpi-label">PIEZAS TOTALES</div>
      <div class="kpi-value">{{ number_format($summary['total_stock']) }}</div>
    </td>
    <td class="kpi">
      <div class="kpi-label">STOCK CRÍTICO</div>
      <div class="kpi-value">{{ number_format($summary['critical']) }}</div>
    </td>
  </tr>
</table>

<table class="grid">
  <tr>
    <td class="panel" width="50%">
      <h2>Lo que tenemos más</h2>
      <div class="muted">Top de productos por stock físico.</div>

      @foreach($topStock->take(8) as $it)
        @php $w = min(100, ((float)($it->stock ?? 0) / $maxTopStock) * 100); @endphp
        <div class="bar-row">
          <div class="bar-top">
            <span class="bar-name">{{ $it->name }}</span>
            <span class="bar-value">{{ number_format((float)($it->stock ?? 0), 0) }}</span>
          </div>
          <div class="track"><div class="fill" style="width:{{ $w }}%;"></div></div>
        </div>
      @endforeach
    </td>

    <td class="panel" width="50%">
      <h2>Valor por categoría</h2>
      <div class="muted">Concentración de piezas y dinero.</div>

      @foreach($categoryStats->take(8) as $cat)
        @php $w = min(100, ((float)$cat['stock'] / $maxCategoryStock) * 100); @endphp
        <div class="bar-row">
          <div class="bar-top">
            <span class="bar-name">{{ $cat['category'] }}</span>
            <span class="bar-value">${{ number_format($cat['value'], 2) }}</span>
          </div>
          <div class="track"><div class="fill" style="width:{{ $w }}%;"></div></div>
        </div>
      @endforeach
    </td>
  </tr>
</table>

<table class="grid">
  <tr>
    <td class="panel" width="50%">
      <h2>Productos con stock crítico</h2>
      <div class="muted">Stock actual menor o igual al mínimo configurado.</div>

      <table class="data">
        <thead>
          <tr>
            <th>Producto</th>
            <th>SKU</th>
            <th>Stock</th>
            <th>Mín.</th>
          </tr>
        </thead>
        <tbody>
          @forelse($criticalItems->take(10) as $it)
            <tr>
              <td>{{ $it->name }}</td>
              <td>{{ $it->sku ?: '—' }}</td>
              <td class="danger">{{ number_format((float)($it->stock ?? 0), 0) }}</td>
              <td>{{ $it->stock_min !== null ? number_format((float)$it->stock_min, 0) : '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="4">No hay productos críticos.</td></tr>
          @endforelse
        </tbody>
      </table>
    </td>

    <td class="panel" width="50%">
      <h2>Publicación y destacados</h2>
      <div class="muted">Estado comercial del catálogo.</div>

      <table class="data">
        <tbody>
          <tr>
            <td>Publicados en web</td>
            <td><span class="pill pill-blue">{{ $summary['published'] }}</span></td>
          </tr>
          <tr>
            <td>No publicados / ocultos</td>
            <td><span class="pill pill-red">{{ $summary['draft'] + $summary['hidden'] }}</span></td>
          </tr>
          <tr>
            <td>Destacados</td>
            <td><span class="pill pill-blue">{{ $summary['featured'] }}</span></td>
          </tr>
          <tr>
            <td>Con Mercado Libre ID</td>
            <td><span class="pill pill-blue">{{ $summary['meli_published'] }}</span></td>
          </tr>
          <tr>
            <td>Pendientes de Mercado Libre</td>
            <td><span class="pill pill-red">{{ $summary['meli_pending'] }}</span></td>
          </tr>
        </tbody>
      </table>
    </td>
  </tr>
</table>

<div class="page-break"></div>

<div class="head">
  <h1>Detalle comercial</h1>
  <div class="subtitle">Precios, rotación y oportunidades de inventario.</div>
</div>

<table class="grid">
  <tr>
    <td class="panel" width="50%">
      <h2>Productos más caros</h2>
      <table class="data">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Precio</th>
            <th>Stock</th>
            <th>Valor</th>
          </tr>
        </thead>
        <tbody>
          @foreach($expensiveItems->take(10) as $it)
            <tr>
              <td>{{ $it->name }}</td>
              <td>${{ number_format($effectivePrice($it), 2) }}</td>
              <td>{{ number_format((float)($it->stock ?? 0), 0) }}</td>
              <td>${{ number_format($stockValue($it), 2) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </td>

    <td class="panel" width="50%">
      <h2>Productos más baratos</h2>
      <table class="data">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Precio</th>
            <th>Stock</th>
            <th>Valor</th>
          </tr>
        </thead>
        <tbody>
          @foreach($cheapItems->take(10) as $it)
            <tr>
              <td>{{ $it->name }}</td>
              <td>${{ number_format($effectivePrice($it), 2) }}</td>
              <td>{{ number_format((float)($it->stock ?? 0), 0) }}</td>
              <td>${{ number_format($stockValue($it), 2) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </td>
  </tr>
</table>

<table class="grid">
  <tr>
    <td class="panel" width="50%">
      <h2>Más movimientos</h2>
      <div class="muted">
        @if($movementSource)
          Basado en {{ $movementSource }}.
        @else
          No se detectó tabla de movimientos conectada.
        @endif
      </div>

      <table class="data">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Movimientos</th>
            <th>Entradas</th>
            <th>Salidas</th>
          </tr>
        </thead>
        <tbody>
          @forelse($topMovements->take(10) as $row)
            <tr>
              <td>{{ $row['item']->name }}</td>
              <td>{{ number_format($row['total_movements'], 0) }}</td>
              <td class="success">{{ number_format($row['incoming'], 0) }}</td>
              <td class="danger">{{ number_format($row['outgoing'], 0) }}</td>
            </tr>
          @empty
            <tr><td colspan="4">Sin historial de movimientos detectado.</td></tr>
          @endforelse
        </tbody>
      </table>
    </td>

    <td class="panel" width="50%">
      <h2>Lo que se va más rápido</h2>
      <div class="muted">
        @if($movementSource)
          Ordenado por salidas.
        @else
          Aproximación por productos en stock crítico.
        @endif
      </div>

      <table class="data">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Salidas</th>
            <th>Stock</th>
            <th>Mín.</th>
          </tr>
        </thead>
        <tbody>
          @forelse($fastMoving->take(10) as $row)
            <tr>
              <td>{{ $row['item']->name }}</td>
              <td>{{ $row['outgoing'] !== null ? number_format($row['outgoing'], 0) : '—' }}</td>
              <td>{{ number_format((float)($row['item']->stock ?? 0), 0) }}</td>
              <td>{{ $row['item']->stock_min !== null ? number_format((float)$row['item']->stock_min, 0) : '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="4">Sin información suficiente.</td></tr>
          @endforelse
        </tbody>
      </table>
    </td>
  </tr>
</table>

</body>
</html>