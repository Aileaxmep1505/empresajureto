@extends('layouts.app')
@section('title','Cotización COT-'.($cotizacion->folio ?: ($cotizacion->id ?: '—')))

@section('content')
<style>
:root{--bg:#f6f7fb;--card:#fff;--ink:#1f2937;--muted:#6b7280;--line:#e5e7eb;--ok:#16a34a;--warn:#d97706;--bad:#b91c1c}
.wrap{max-width:980px;margin:24px auto;padding:0 14px}
.card{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 16px 40px rgba(18,38,63,.08);overflow:hidden;margin-bottom:16px}
.head{padding:16px 18px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center}
.body{padding:18px}
.badge{padding:4px 10px;border-radius:999px;border:1px solid var(--line);font-size:12px}
.table{width:100%;border-collapse:collapse}
.table th,.table td{border-bottom:1px solid var(--line);padding:10px;text-align:left}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.actions{display:flex;gap:8px;flex-wrap:wrap}
.btn{display:inline-flex;gap:6px;align-items:center;padding:8px 12px;border-radius:10px;border:1px solid var(--line);background:#fff}
.btn.ok{background:#eafcef;border-color:#ccebd6}
.btn.warn{background:#fff7ed;border-color:#fde4cc}
.btn.bad{background:#fee2e2;border-color:#fecaca}
.small{font-size:12px;color:var(--muted)}
</style>

<div class="wrap">
  <div class="card">
    <div class="head">
      <h2 style="margin:0">COT-{{ $cotizacion->folio ?: ($cotizacion->id ?: '—') }}</h2>
      <span class="badge">{{ ucfirst($cotizacion->estado) }}</span>
    </div>
    <div class="body">
      <div class="grid">
        <div>
          @php $cli = $cotizacion->cliente; @endphp
          <div><strong>Cliente:</strong>
            {{ $cli->name ?? $cli->nombre ?? $cli->razon_social ?? '—' }}
          </div>
          <div class="small">Vence: {{ $cotizacion->vence_el? $cotizacion->vence_el->format('d/m/Y') : '—' }}</div>
        </div>
        <div class="actions" style="justify-content:flex-end">
          @if($cotizacion->getKey())
            <a class="btn" href="{{ route('cotizaciones.pdf', ['cotizacion' => $cotizacion->getKey()]) }}" target="_blank">Descargar PDF</a>
          @endif

          @if(in_array($cotizacion->estado,['borrador','enviada']) && $cotizacion->getKey())
            <form method="POST" action="{{ route('cotizaciones.aprobar', ['cotizacion' => $cotizacion->getKey()]) }}">@csrf
              <button class="btn ok" type="submit">Aprobar</button>
            </form>
            <form method="POST" action="{{ route('cotizaciones.rechazar', ['cotizacion' => $cotizacion->getKey()]) }}">@csrf
              <button class="btn bad" type="submit">Rechazar</button>
            </form>
          @endif

          @if($cotizacion->estado === 'aprobada' && $cotizacion->getKey())
            <form method="POST" action="{{ route('cotizaciones.convertir', ['cotizacion' => $cotizacion->getKey()]) }}">@csrf
              <button class="btn warn" type="submit">Convertir en venta</button>
            </form>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="head"><h3 style="margin:0">Detalle</h3></div>
    <div class="body">
      <table class="table">
        <thead>
          <tr>
            <th>Descripción</th><th>Cant.</th><th>P. Unit.</th><th>Desc.</th><th>IVA%</th><th>Importe</th>
          </tr>
        </thead>
        <tbody>
          @foreach($cotizacion->items as $it)
          @php $prod = $it->producto; @endphp
          <tr>
            <td>{{ $it->descripcion ?? ($prod->nombre ?? $prod->name ?? ('#'.$it->producto_id)) }}</td>
            <td>{{ number_format($it->cantidad,2) }}</td>
            <td>${{ number_format($it->precio_unitario,2) }}</td>
            <td>${{ number_format($it->descuento,2) }}</td>
            <td>{{ number_format($it->iva_porcentaje,2) }}%</td>
            <td>${{ number_format($it->importe,2) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>

      <div style="display:flex;justify-content:flex-end;margin-top:12px">
        <table class="table" style="width:auto">
          <tr><td>Subtotal</td><td>${{ number_format($cotizacion->subtotal,2) }}</td></tr>
          <tr><td>Descuento</td><td>-${{ number_format($cotizacion->descuento,2) }}</td></tr>
          <tr><td>Envío</td><td>${{ number_format($cotizacion->envio,2) }}</td></tr>
          <tr><td>IVA</td><td>${{ number_format($cotizacion->iva,2) }}</td></tr>
          <tr><td><strong>Total</strong></td><td><strong>${{ number_format($cotizacion->total,2) }} {{ $cotizacion->moneda }}</strong></td></tr>
        </table>
      </div>
    </div>
  </div>

  @if($cotizacion->plazos->count())
  <div class="card">
    <div class="head"><h3 style="margin:0">Plan de financiamiento</h3></div>
    <div class="body">
      <table class="table">
        <thead><tr><th>#</th><th>Vence</th><th>Monto</th><th>Estado</th></tr></thead>
        <tbody>
          @foreach($cotizacion->plazos as $pz)
          <tr>
            <td>{{ $pz->numero }}</td>
            <td>{{ $pz->vence_el->format('d/m/Y') }}</td>
            <td>${{ number_format($pz->monto,2) }}</td>
            <td>{{ $pz->pagado ? 'Pagado' : 'Pendiente' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
      @if($cotizacion->financiamiento_config)
        <div class="small" style="margin-top:8px">
          Tasa anual: {{ $cotizacion->financiamiento_config['tasa_anual'] ?? 0 }}% — Enganche: ${{ number_format($cotizacion->financiamiento_config['enganche'] ?? 0,2) }}
        </div>
      @endif
    </div>
  </div>
  @endif
</div>
@endsection
