@extends('layouts.app')
@section('title','Venta VTA-'.($venta->folio ?: ($venta->id ?: '—')))

@section('content')
<style>
.wrap{max-width:980px;margin:24px auto;padding:0 14px}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 16px 40px rgba(18,38,63,.08);overflow:hidden;margin-bottom:16px}
.head{padding:16px 18px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center}
.body{padding:18px}
.badge{padding:4px 10px;border-radius:999px;border:1px solid #e5e7eb;font-size:12px}
.table{width:100%;border-collapse:collapse}
.table th,.table td{border-bottom:1px solid #e5e7eb;padding:10px;text-align:left}
.small{font-size:12px;color:#6b7280}
</style>

<div class="wrap">
  <div class="card">
    <div class="head">
      <h2 style="margin:0">VTA-{{ $venta->folio ?: ($venta->id ?: '—') }}</h2>
      <span class="badge">{{ ucfirst($venta->estado) }}</span>
    </div>
    <div class="body">
      @php $cli = $venta->cliente; @endphp
      <div><strong>Cliente:</strong> {{ $cli->name ?? $cli->nombre ?? $cli->razon_social ?? '—' }}</div>
      @if($venta->cotizacion)
        <div class="small">Origen: COT-{{ $venta->cotizacion->id }}</div>
      @endif
    </div>
  </div>

  <div class="card">
    <div class="head"><h3 style="margin:0">Productos</h3></div>
    <div class="body">
      <table class="table">
        <thead>
          <tr><th>Descripción</th><th>Cant.</th><th>P. Unit.</th><th>Desc.</th><th>IVA%</th><th>Importe</th></tr>
        </thead>
        <tbody>
          @foreach($venta->items as $it)
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
          <tr><td>Subtotal</td><td>${{ number_format($venta->subtotal,2) }}</td></tr>
          <tr><td>Descuento</td><td>-${{ number_format($venta->descuento,2) }}</td></tr>
          <tr><td>Envío</td><td>${{ number_format($venta->envio,2) }}</td></tr>
          <tr><td>IVA</td><td>${{ number_format($venta->iva,2) }}</td></tr>
          <tr><td><strong>Total</strong></td><td><strong>${{ number_format($venta->total,2) }} {{ $venta->moneda }}</strong></td></tr>
        </table>
      </div>
    </div>
  </div>

  @if($venta->plazos->count())
  <div class="card">
    <div class="head"><h3 style="margin:0)">Plan de financiamiento</h3></div>
    <div class="body">
      <table class="table">
        <thead><tr><th>#</th><th>Vence</th><th>Monto</th><th>Estado</th></tr></thead>
        <tbody>
          @foreach($venta->plazos as $pz)
          <tr>
            <td>{{ $pz->numero }}</td>
            <td>{{ $pz->vence_el->format('d/m/Y') }}</td>
            <td>${{ number_format($pz->monto,2) }}</td>
            <td>{{ $pz->pagado ? 'Pagado' : 'Pendiente' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
      @if($venta->financiamiento_config)
        <div class="small" style="margin-top:8px">
          Tasa anual: {{ $venta->financiamiento_config['tasa_anual'] ?? 0 }}% — Enganche: ${{ number_format($venta->financiamiento_config['enganche'] ?? 0,2) }}
        </div>
      @endif
    </div>
  </div>
  @endif
</div>
@endsection
