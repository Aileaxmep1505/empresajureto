@extends('layouts.app')
@section('title','Cotización COT-'.($cotizacion->folio ?: ($cotizacion->id ?: '—')))

@section('content')
<style>
  :root{
    --bg:#f6f7fb; --card:#fff; --ink:#1f2937; --muted:#6b7280; --line:#e5e7eb;
    --ok:#16a34a; --warn:#d97706; --bad:#b91c1c; --brand:#2563eb; --brand-600:#1d4ed8;
    --radius:16px; --shadow:0 16px 40px rgba(18,38,63,.08);
  }
  body{background:var(--bg)}
  .wrap{max-width:1100px;margin:24px auto;padding:0 14px}
  .card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;margin-bottom:16px}
  .head{padding:16px 18px;border-bottom:1px solid var(--line);display:flex;gap:12px;justify-content:space-between;align-items:center;flex-wrap:wrap}
  .body{padding:18px}
  .badge{padding:4px 10px;border-radius:999px;border:1px solid var(--line);font-size:12px;display:inline-flex;gap:8px;align-items:center}
  .badge .dot{width:8px;height:8px;border-radius:999px;background:#9ca3af}
  .badge.ok .dot{background:var(--ok)} .badge.warn .dot{background:var(--warn)} .badge.bad .dot{background:var(--bad)}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .actions{display:flex;gap:8px;flex-wrap:wrap}
  .btn{display:inline-flex;gap:6px;align-items:center;padding:10px 14px;border-radius:12px;border:1px solid var(--line);background:#fff;font-weight:600;text-decoration:none;color:#111827;transition:.18s}
  .btn:hover{transform:translateY(-1px)}
  .btn.brand{background:var(--brand);border-color:var(--brand);color:#fff}
  .btn.brand:hover{background:var(--brand-600);border-color:var(--brand-600)}
  .btn.ok{background:#eafcef;border-color:#ccebd6}
  .btn.warn{background:#fff7ed;border-color:#fde4cc}
  .btn.bad{background:#fee2e2;border-color:#fecaca}
  .small{font-size:12px;color:var(--muted)}
  .muted{color:var(--muted)}
  .kv{display:flex;justify-content:space-between;gap:8px;margin:6px 0}
  .table{width:100%;border-collapse:collapse}
  .table th,.table td{border-bottom:1px solid var(--line);padding:10px;text-align:left;vertical-align:top}
  .table th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#334155}
  .right{text-align:right}

  /* Totales (alineado derecha, sin tabla) */
  .totals-wrap{display:flex;justify-content:flex-end}
  .totals-box{width:100%;max-width:420px;margin-top:12px;border:1px dashed var(--line);border-radius:14px;padding:14px;background:#fff}
  .trow{display:flex;justify-content:space-between;gap:12px;margin:6px 0}
  .trow .label{color:#334155}
  .trow .value{font-variant-numeric:tabular-nums;text-align:right}
  .trow.sum .label,.trow.sum .value{font-weight:700}
  .trow.grand{margin-top:10px;padding-top:10px;border-top:2px solid var(--line)}
  .trow.pill .value{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;background:#f8fafc;border:1px solid var(--line);font-size:12px}

  /* Tabla → cards en móvil */
  @media (max-width: 768px){
    .grid{grid-template-columns:1fr}
    .table thead{display:none}
    .table, .table tbody, .table tr, .table td{display:block;width:100%}
    .table tr{background:#fff;border:1px solid var(--line);border-radius:12px;margin-bottom:12px;padding:12px}
    .table td{border-bottom:none;padding:6px 0}
    .table td[data-label]::before{
      content:attr(data-label); display:block; font-size:12px; color:var(--muted);
      margin-bottom:2px; text-transform:uppercase; letter-spacing:.04em;
    }
    .btn{flex:1}
    .totals-box{max-width:none}
  }
  @media print{ .actions{display:none!important} .card{box-shadow:none;border-color:#cbd5e1} body{background:#fff} }
</style>

@php
  use Illuminate\Support\Str;

  // ===== Cálculos espejo de tu modelo (por si no están persistidos) =====
  $items = $cotizacion->relationLoaded('items') ? $cotizacion->items : $cotizacion->items()->get();

  $subtotalCalc = 0.0;   // Σ base (sin IVA, sin envío)
  $ivaCalc      = 0.0;   // Σ iva_monto
  $inversion    = 0.0;   // Σ (cost * cantidad)

  foreach ($items as $it) {
      $cost   = (float)($it->cost ?? 0);
      $qty    = (float)($it->cantidad ?? 0);
      $desc   = (float)($it->descuento ?? 0);            // descuento por fila ($)
      $ivaPct = (float)($it->iva_porcentaje ?? 0);       // %

      // Precio unitario resultante (ya con utilidad aplicada en la creación)
      $precioUnit = (float)($it->precio_unitario ?? $it->precio ?? 0);
      if (!$precioUnit && $cost) $precioUnit = $cost; // fallback defensivo

      $base = max(0, ($precioUnit * $qty) - $desc);
      $ivaFila = isset($it->iva_monto) ? (float)$it->iva_monto : round($base * ($ivaPct/100), 2);

      $subtotalCalc += $base;
      $ivaCalc      += $ivaFila;
      $inversion    += ($cost * $qty);
  }

  $subtotal = $cotizacion->subtotal !== null ? (float)$cotizacion->subtotal : round($subtotalCalc, 2);
  $iva      = $cotizacion->iva      !== null ? (float)$cotizacion->iva      : round($ivaCalc, 2);
  $descuentoGlobal = (float)($cotizacion->descuento ?? 0);
  $envio           = (float)($cotizacion->envio ?? 0);
  $total    = $cotizacion->total    !== null
                ? (float)$cotizacion->total
                : max(0, round($subtotal - $descuentoGlobal + $envio + $iva, 2));

  $inversion_total   = $cotizacion->inversion_total   ?? round($inversion, 2);
  $ganancia_estimada = $cotizacion->ganancia_estimada ?? round($subtotal - $inversion_total, 2);
  $utilidad_global   = (float)($cotizacion->utilidad_global ?? 0);
  $utilidad_pct      = $inversion_total > 0 ? ($ganancia_estimada / $inversion_total) * 100 : 0;

  $estado = strtolower($cotizacion->estado ?? 'abierta');
  $estadoClass = [
    'aprobada'  => 'ok',
    'convertida'=> 'ok',
    'borrador'  => 'warn',
    'enviada'   => 'warn',
    'rechazada' => 'bad',
    'cancelada' => 'bad',
  ][$estado] ?? '';
@endphp

<div class="wrap">

  {{-- HEADER --}}
  <div class="card">
    <div class="head">
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <h2 style="margin:0">COT-{{ $cotizacion->folio ?: ($cotizacion->id ?: '—') }}</h2>
        <span class="badge {{ $estadoClass }}"><span class="dot"></span>{{ $cotizacion->estado_label }}</span>
      </div>
      <div class="actions">
        @if($cotizacion->getKey())
          <a class="btn brand" href="{{ route('cotizaciones.pdf', ['cotizacion' => $cotizacion->getKey()]) }}" target="_blank" rel="noopener">
            Descargar PDF
          </a>
        @endif

        @if(in_array($estado, ['borrador','enviada']) && $cotizacion->getKey())
          <form method="POST" action="{{ route('cotizaciones.aprobar', ['cotizacion' => $cotizacion->getKey()]) }}">@csrf
            <button class="btn ok" type="submit">Aprobar</button>
          </form>
          <form method="POST" action="{{ route('cotizaciones.rechazar', ['cotizacion' => $cotizacion->getKey()]) }}">@csrf
            <button class="btn bad" type="submit">Rechazar</button>
          </form>
        @endif

        @if($estado === 'aprobada' && $cotizacion->getKey())
          <form method="POST" action="{{ route('cotizaciones.convertir', ['cotizacion' => $cotizacion->getKey()]) }}">@csrf
            <button class="btn warn" type="submit">Convertir en venta</button>
          </form>
        @endif
      </div>
    </div>

    <div class="body">
      <div class="grid">
        <div>
          @php $cli = $cotizacion->cliente; @endphp
          <div style="font-weight:700;margin-bottom:4px">Cliente</div>
          <div>{{ $cli->name ?? $cli->nombre ?? $cli->razon_social ?? '—' }}</div>
          <div class="small muted">
            @if(!empty($cli?->rfc)) RFC: {{ $cli->rfc }} @endif
            @if(!empty($cli?->email)) · {{ $cli->email }} @endif
            @if(!empty($cli?->telefono)) · {{ $cli->telefono }} @endif
          </div>
        </div>
        <div>
          <div class="kv"><div class="muted">Validez (días)</div><div>{{ $cotizacion->validez_dias ?? '—' }}</div></div>
          <div class="kv"><div class="muted">Vence</div><div>{{ $cotizacion->vence_el? $cotizacion->vence_el->format('d/m/Y') : '—' }}</div></div>
          <div class="kv"><div class="muted">Moneda</div><div>{{ $cotizacion->moneda ?? 'MXN' }}</div></div>
          @if($utilidad_global>0)
            <div class="kv"><div class="muted">Utilidad global (%)</div><div>{{ number_format($utilidad_global,2) }}%</div></div>
          @endif
        </div>
      </div>

      @if(!empty($cotizacion->notas))
        <div class="small" style="margin-top:8px"><strong>Notas:</strong> {{ $cotizacion->notas }}</div>
      @endif
    </div>
  </div>

  {{-- DETALLE (mismos campos que create) --}}
  <div class="card">
    <div class="head"><h3 style="margin:0">Productos</h3></div>
    <div class="body">
      <table class="table">
        <thead>
          <tr>
            <th>Producto / Descripción</th>
            <th class="right">Cant.</th>
            <th class="right">P. Unit. (desde costo)</th>
            <th class="right">Desc.</th>
            <th class="right">IVA%</th>
            <th class="right">Importe</th>
          </tr>
        </thead>
        <tbody>
          @forelse($items as $it)
            @php $prod = $it->producto; @endphp
            <tr>
              <td data-label="Producto / Descripción">
                <div style="font-weight:600">{{ $it->descripcion ?? ($prod->nombre ?? $prod->name ?? ('#'.$it->producto_id)) }}</div>
                @if(!empty($prod?->sku) || !empty($prod?->marca))
                  <div class="small muted">
                    @if(!empty($prod?->sku)) SKU: {{ $prod->sku }} @endif
                    @if(!empty($prod?->marca)) · {{ $prod->marca }} @endif
                  </div>
                @endif
              </td>
              <td class="right" data-label="Cant.">{{ number_format($it->cantidad,2) }}</td>
              <td class="right" data-label="P. Unit. (desde costo)">
                ${{ number_format($it->precio_unitario ?? $it->precio ?? 0,2) }}
                @php
                  $cost = (float)($it->cost ?? 0);
                  $pu   = (float)($it->precio_unitario ?? $it->precio ?? 0);
                @endphp
                @if($cost>0 && $pu>0)
                  <div class="small muted">Costo: ${{ number_format($cost,2) }}</div>
                @endif
              </td>
              <td class="right" data-label="Desc.">${{ number_format($it->descuento ?? 0,2) }}</td>
              <td class="right" data-label="IVA %">{{ number_format($it->iva_porcentaje ?? 0,2) }}%</td>
              <td class="right" data-label="Importe">${{ number_format($it->importe ?? ($it->importe_total ?? 0),2) }}</td>
            </tr>
          @empty
            <tr><td colspan="6" class="small muted">Sin productos.</td></tr>
          @endforelse
        </tbody>
      </table>

      {{-- Totales alineados a la derecha (y mismos campos que create) --}}
      <div class="totals-wrap">
        <div class="totals-box">
          <div class="trow">
            <div class="label">Inversión (costo)</div>
            <div class="value">${{ number_format($inversion_total,2) }}</div>
          </div>
          <div class="trow">
            <div class="label">Ganancia estimada</div>
            <div class="value">${{ number_format($ganancia_estimada,2) }}</div>
          </div>
          <div class="trow pill">
            <div class="label">Utilidad</div>
            <div class="value">{{ number_format($utilidad_pct,2) }}%</div>
          </div>

          <div class="trow sum">
            <div class="label">Subtotal</div>
            <div class="value">${{ number_format($subtotal,2) }}</div>
          </div>
          <div class="trow">
            <div class="label">IVA</div>
            <div class="value">${{ number_format($iva,2) }}</div>
          </div>
          <div class="trow">
            <div class="label">Descuento</div>
            <div class="value">- ${{ number_format($descuentoGlobal,2) }}</div>
          </div>
          <div class="trow">
            <div class="label">Envío</div>
            <div class="value">${{ number_format($envio,2) }}</div>
          </div>
          <div class="trow grand">
            <div class="label">TOTAL</div>
            <div class="value"><strong>${{ number_format($total,2) }} {{ $cotizacion->moneda ?? 'MXN' }}</strong></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- FINANCIAMIENTO (mismos campos que create) --}}
  @if($cotizacion->plazos && $cotizacion->plazos->count())
    <div class="card">
      <div class="head"><h3 style="margin:0">Plan de financiamiento</h3></div>
      <div class="body">
        <table class="table">
          <thead><tr><th>#</th><th>Vence</th><th class="right">Monto</th><th>Estado</th></tr></thead>
          <tbody>
            @foreach($cotizacion->plazos as $pz)
              <tr>
                <td data-label="#">{{ $pz->numero }}</td>
                <td data-label="Vence">{{ optional($pz->vence_el)->format('d/m/Y') }}</td>
                <td data-label="Monto" class="right">${{ number_format($pz->monto,2) }}</td>
                <td data-label="Estado">{{ $pz->pagado ? 'Pagado' : 'Pendiente' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>

        @if($cotizacion->financiamiento_config)
          <div class="small muted" style="margin-top:8px">
            Tasa anual: {{ $cotizacion->financiamiento_config['tasa_anual'] ?? 0 }}% —
            Enganche: ${{ number_format($cotizacion->financiamiento_config['enganche'] ?? 0,2) }}
            @if(isset($cotizacion->financiamiento_config['plazos'])) — Plazos: {{ $cotizacion->financiamiento_config['plazos'] }} @endif
            @if(isset($cotizacion->financiamiento_config['primer_vencimiento'])) — Primer vencimiento: {{ \Illuminate\Support\Arr::get($cotizacion->financiamiento_config,'primer_vencimiento') }} @endif
          </div>
        @endif
      </div>
    </div>
  @endif

</div>
@endsection
