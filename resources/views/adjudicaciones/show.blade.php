@extends('layouts.app')
@section('content_class', 'content--flush')
@section('content')
<link rel="stylesheet" href="{{ asset('css/cotizacion.css') }}?v={{ time() }}">
<style>.fallo-table{width:100%;border-collapse:collapse;font-size:13px}.fallo-table th,.fallo-table td{border:1px solid #ececec;padding:9px;text-align:left}.fallo-table th{background:#f9fafb;font-weight:700}</style>

<div class="jureto-quote-page">
  <div class="quote-wrap">
    <a href="{{ route('propuestas-comerciales.fallo.show', $adjudicacion->propuesta_comercial_id) }}" class="back-link"><span>←</span><span>Volver al acta de fallo</span></a>

    @if(session('status'))
      <div class="notice show" style="border-color:#bbf7d0; background:#f0fdf4; color:#166534;"><span class="notice-dot" style="background:#16a34a;"></span><span>{{ session('status') }}</span></div>
    @endif

    <div class="topbar">
      <div class="topbar-main">
        <div class="quote-code">ADJUDICACIÓN · {{ $adjudicacion->folio ?: ('ADJ-'.$adjudicacion->id) }}</div>
        <h1 class="quote-title">{{ optional($adjudicacion->client)->nombre ?: $adjudicacion->cliente_nombre ?: 'Cliente sin asignar' }}</h1>
        <p class="quote-subtitle">Estatus: <span class="badge badge-info">{{ ucfirst($adjudicacion->status) }}</span> · Total ${{ number_format($adjudicacion->total,2) }}</p>
      </div>
    </div>

    {{-- Renglones --}}
    <div class="item-card" style="padding:0; overflow:hidden; margin-bottom:16px;">
      <table class="fallo-table">
        <thead><tr><th>#</th><th>Descripción</th><th>Unidad</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr></thead>
        <tbody>
        @foreach($adjudicacion->items as $it)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $it->descripcion }}</td>
            <td>{{ $it->unidad }}</td>
            <td>{{ rtrim(rtrim(number_format($it->cantidad,2),'0'),'.') }}</td>
            <td>${{ number_format($it->precio_unitario,2) }}</td>
            <td>${{ number_format($it->subtotal,2) }}</td>
          </tr>
        @endforeach
        </tbody>
        <tfoot>
          <tr><td colspan="5" style="text-align:right;"><strong>Subtotal</strong></td><td>${{ number_format($adjudicacion->subtotal,2) }}</td></tr>
          <tr><td colspan="5" style="text-align:right;"><strong>IVA ({{ rtrim(rtrim(number_format($adjudicacion->porcentaje_impuesto,2),'0'),'.') }}%)</strong></td><td>${{ number_format($adjudicacion->impuesto_total,2) }}</td></tr>
          <tr><td colspan="5" style="text-align:right;"><strong>Total</strong></td><td><strong>${{ number_format($adjudicacion->total,2) }}</strong></td></tr>
        </tfoot>
      </table>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
      {{-- Editar adjudicación --}}
      <div class="item-card" style="padding:18px;">
        <div class="section-title">Editar adjudicación</div>
        <form method="POST" action="{{ route('adjudicaciones.update', $adjudicacion) }}" style="display:grid; gap:12px;">
          @csrf @method('PUT')
          <div class="field"><label>Folio</label><input class="input" name="folio" value="{{ $adjudicacion->folio }}"></div>
          <div class="field"><label>Fecha</label><input class="input" type="date" name="fecha" value="{{ optional($adjudicacion->fecha)->format('Y-m-d') }}"></div>
          <div class="field"><label>% Impuesto</label><input class="input" type="number" step="0.01" name="porcentaje_impuesto" value="{{ $adjudicacion->porcentaje_impuesto }}"></div>
          <div class="field"><label>Estatus</label>
            <select class="input" name="status">
              @foreach(['borrador','confirmada','remisionada','cerrada'] as $s)
                <option value="{{ $s }}" @selected($adjudicacion->status===$s)>{{ ucfirst($s) }}</option>
              @endforeach
            </select>
          </div>
          <div class="field"><label>Observaciones</label><textarea class="input" name="observaciones" rows="2" style="height:auto;padding:10px;">{{ $adjudicacion->observaciones }}</textarea></div>
          <div class="action-row">
            <button class="btn btn-primary btn-small" type="submit">Guardar</button>
        </form>
            <form method="POST" action="{{ route('adjudicaciones.destroy', $adjudicacion) }}" onsubmit="return confirm('¿Eliminar la adjudicación?');">
              @csrf @method('DELETE')
              <button class="btn btn-danger btn-small" type="submit">Eliminar</button>
            </form>
          </div>
      </div>

      {{-- Remisiones --}}
      <div class="item-card" style="padding:18px;">
        <div class="section-title">Remisiones</div>

        <table class="fallo-table" style="margin-bottom:12px;">
          <thead><tr><th>Folio</th><th>Fecha</th><th>Estatus</th><th></th></tr></thead>
          <tbody>
          @forelse($adjudicacion->remisiones as $rem)
            <tr>
              <td>{{ $rem->folio ?: ('REM-'.$rem->id) }}</td>
              <td>{{ optional($rem->fecha)->format('d/m/Y') }}</td>
              <td><span class="badge badge-info">{{ ucfirst($rem->status) }}</span></td>
              <td><a class="btn btn-outline btn-small" href="{{ route('remisiones.show', $rem) }}">Abrir</a></td>
            </tr>
          @empty
            <tr><td colspan="4" style="text-align:center;color:#888;">Sin remisiones.</td></tr>
          @endforelse
          </tbody>
        </table>

        <form method="POST" action="{{ route('remisiones.store', $adjudicacion) }}" style="display:grid; gap:10px;">
          @csrf
          <div class="field"><label>Recibe (nombre)</label><input class="input" name="recibe_nombre"></div>
          <div class="field"><label>Fecha</label><input class="input" type="date" name="fecha" value="{{ now()->format('Y-m-d') }}"></div>
          <div class="field"><label>Observaciones</label><input class="input" name="observaciones"></div>
          <div class="action-row"><button class="btn btn-soft btn-small" type="submit">＋ Crear remisión (copia renglones)</button></div>
        </form>
      </div>
    </div>

  </div>
</div>
@endsection