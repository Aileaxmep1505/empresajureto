@extends('layouts.app')
@section('content_class', 'content--flush')
@section('content')
<link rel="stylesheet" href="{{ asset('css/cotizacion.css') }}?v={{ time() }}">
<style>.fallo-table{width:100%;border-collapse:collapse;font-size:13px}.fallo-table th,.fallo-table td{border:1px solid #ececec;padding:8px;text-align:left}.fallo-table th{background:#f9fafb;font-weight:700}</style>

<div class="jureto-quote-page">
  <div class="quote-wrap">
    <a href="{{ route('adjudicaciones.show', $remision->adjudicacion_id) }}" class="back-link"><span>←</span><span>Volver a la adjudicación</span></a>

    @if(session('status'))
      <div class="notice show" style="border-color:#bbf7d0; background:#f0fdf4; color:#166534;"><span class="notice-dot" style="background:#16a34a;"></span><span>{{ session('status') }}</span></div>
    @endif

    <div class="topbar">
      <div class="topbar-main">
        <div class="quote-code">REMISIÓN · {{ $remision->folio ?: ('REM-'.$remision->id) }}</div>
        <h1 class="quote-title">{{ optional($remision->adjudicacion->client)->nombre ?: optional($remision->adjudicacion)->cliente_nombre ?: 'Cliente' }}</h1>
        <p class="quote-subtitle">Estatus: <span class="badge badge-info">{{ ucfirst($remision->status) }}</span></p>
      </div>
      <div class="actions">
        <a class="btn btn-outline" target="_blank" href="{{ route('remisiones.pdf', $remision) }}">↗ PDF</a>
      </div>
    </div>

    <form method="POST" action="{{ route('remisiones.update', $remision) }}">
      @csrf @method('PUT')

      <div class="item-card" style="padding:18px; margin-bottom:16px;">
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:12px;">
          <div class="field"><label>Folio</label><input class="input" name="folio" value="{{ $remision->folio }}"></div>
          <div class="field"><label>Fecha</label><input class="input" type="date" name="fecha" value="{{ optional($remision->fecha)->format('Y-m-d') }}"></div>
          <div class="field"><label>Recibe</label><input class="input" name="recibe_nombre" value="{{ $remision->recibe_nombre }}"></div>
          <div class="field"><label>Estatus</label>
            <select class="input" name="status">
              @foreach(['borrador','emitida','entregada','cancelada'] as $s)
                <option value="{{ $s }}" @selected($remision->status===$s)>{{ ucfirst($s) }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="field" style="margin-top:12px;"><label>Observaciones</label><input class="input" name="observaciones" value="{{ $remision->observaciones }}"></div>
      </div>

      <div class="item-card" style="padding:0; overflow:hidden; margin-bottom:16px;">
        <table class="fallo-table">
          <thead><tr><th>#</th><th>Descripción</th><th>Unidad</th><th>Cantidad</th><th>Precio</th></tr></thead>
          <tbody>
          @foreach($remision->items as $i => $it)
            <tr>
              <td>{{ $i + 1 }}<input type="hidden" name="items[{{ $i }}][id]" value="{{ $it->id }}"></td>
              <td><input class="input" name="items[{{ $i }}][descripcion]" value="{{ $it->descripcion }}" style="height:34px;"></td>
              <td><input class="input" name="items[{{ $i }}][unidad]" value="{{ $it->unidad }}" style="height:34px; width:90px;"></td>
              <td><input class="input" type="number" step="0.01" name="items[{{ $i }}][cantidad]" value="{{ $it->cantidad }}" style="height:34px; width:100px;"></td>
              <td><input class="input" type="number" step="0.01" name="items[{{ $i }}][precio_unitario]" value="{{ $it->precio_unitario }}" style="height:34px; width:110px;"></td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>

      <div class="action-row">
        <button class="btn btn-primary btn-small" type="submit">✓ Guardar remisión</button>
    </form>
        <form method="POST" action="{{ route('remisiones.destroy', $remision) }}" onsubmit="return confirm('¿Eliminar remisión?');">
          @csrf @method('DELETE')
          <button class="btn btn-danger btn-small" type="submit">Eliminar</button>
        </form>
      </div>

  </div>
</div>
@endsection