@extends('layouts.app')
{{-- Pantalla preparada para modo oscuro: el layout no la fuerza a claro --}}
@section('tema_oscuro', '1')
@section('title', 'WMS · Cross-docking')

@push('styles')
  @include('admin.wms.ops._estilos')
@endpush

@section('content')
@php
  $colorEstado = ['asignado' => 'azul', 'en_anden' => 'ambar', 'entregado' => 'verde', 'cancelado' => ''];
  $siguiente = ['asignado' => 'Ya está en andén', 'en_anden' => 'Entregado a la ola'];
  $enTransito = $asignaciones->whereIn('status', ['asignado', 'en_anden']);
@endphp

<div class="ops-wrap">
  @include('admin.wms.ops._nav', [
      'opsTitulo' => 'Cross-docking',
      'opsSub' => "Cruza lo recibido en los últimos {$dias} días contra lo que todavía piden las olas de picking abiertas. Lo que coincide se manda directo al andén del pedido, sin guardarlo en rack.",
      'opsTour' => 'wms-crossdock',
      'opsPasos' => [
          ['Mira las coincidencias', 'Llegó algo que un pedido abierto está esperando: no tiene caso guardarlo en rack.'],
          ['Asigna la cantidad', 'Elige cuántas piezas se van directo y a qué andén las vas a dejar.'],
          ['Llévalo al andén', 'Cuando la mercancía ya esté ahí, marca «Ya está en andén».'],
          ['Entrégalo', 'Al pasar al pedido, marca «Entregado» y se cierra la asignación.'],
      ],
  ])

  <div class="ops-kpis" data-tour="kpis">
    <div class="ops-kpi {{ count($oportunidades) ? 'ambar' : 'verde' }}"><b>{{ count($oportunidades) }}</b><span>Oportunidades detectadas</span></div>
    <div class="ops-kpi azul"><b>{{ number_format(collect($oportunidades)->sum('sugerido')) }}</b><span>Unidades que pueden irse directo</span></div>
    <div class="ops-kpi"><b>{{ $enTransito->count() }}</b><span>Asignaciones en tránsito</span></div>
    <div class="ops-kpi verde"><b>{{ $asignaciones->where('status', 'entregado')->count() }}</b><span>Entregadas (recientes)</span></div>
  </div>

  {{-- ===================== Oportunidades ===================== --}}
  <div class="ops-card">
    <div class="ops-card-head">
      <div><h2>Oportunidades</h2><p>Mercancía recién recibida que una ola abierta está esperando.</p></div>
    </div>
    <span data-tour="oportunidades" hidden></span>

    @if(!count($oportunidades))
      <div class="ops-empty">
        <h3>Sin oportunidades por ahora</h3>
        <p>Aparecen cuando se recibe un producto que alguna ola de picking pendiente o en proceso todavía necesita.</p>
      </div>
    @else
      <div class="ops-table-wrap">
        <table class="ops-table">
          <thead><tr><th>Recibido</th><th>Producto</th><th class="num">Disponible</th><th>Lo pide</th><th class="num">Le falta</th><th>Asignar</th></tr></thead>
          <tbody>
            @foreach($oportunidades as $o)
              <tr>
                <td>
                  <b>{{ $o['linea']->reception->folio ?? 'Recepción #' . $o['linea']->reception_id }}</b>
                  <small style="display:block; color:var(--ui-muted);">{{ $o['linea']->reception?->created_at?->format('d/m/Y H:i') }}</small>
                  @if($o['linea']->location)<span class="ops-loc">{{ $o['linea']->location->code }}</span>@endif
                </td>
                <td><div class="ops-prod">{{ $o['linea']->catalogItem->name ?? $o['linea']->name }}<small>SKU {{ $o['linea']->catalogItem->sku ?? $o['linea']->sku ?? '—' }}</small></div></td>
                <td class="num">{{ number_format($o['disponible']) }}</td>
                <td>
                  <b>{{ $o['ola']['code'] }}</b>
                  @if($o['ola']['order_number'])<small style="display:block; color:var(--ui-muted);">Pedido {{ $o['ola']['order_number'] }}</small>@endif
                </td>
                <td class="num">{{ number_format($o['faltante']) }} <small style="color:var(--ui-muted);">de {{ number_format($o['ola']['required']) }}</small></td>
                <td>
                  <form method="POST" action="{{ route('admin.wms.crossdock.assign') }}" class="ops-actions">
                    @csrf
                    <input type="hidden" name="reception_line_id" value="{{ $o['linea']->id }}">
                    <input type="hidden" name="pick_wave_id" value="{{ $o['ola']['wave_id'] }}">
                    <input type="hidden" name="wave_line_id" value="{{ $o['ola']['line_id'] }}">
                    <input type="number" name="qty" min="1" max="{{ $o['sugerido'] }}" value="{{ $o['sugerido'] }}" class="ops-input ops-qty" aria-label="Cantidad">
                    <select name="staging_location_id" class="ops-input" style="width:auto; max-width:190px;" aria-label="Andén / ubicación de salida">
                      <option value="">Se queda donde está</option>
                      @foreach($ubicaciones as $u)<option value="{{ $u->id }}">{{ $u->code }}</option>@endforeach
                    </select>
                    <button type="submit" class="ops-btn is-primary is-sm">Asignar</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- ===================== Asignaciones ===================== --}}
  <div class="ops-card">
    <div class="ops-card-head">
      <div data-tour="asignaciones"><h2>Asignaciones</h2><p>Sigue cada una hasta que se entrega a la ola. Si eliges una ubicación de andén, el stock se mueve ahí al asignar.</p></div>
    </div>

    @if($asignaciones->isEmpty())
      <div class="ops-empty"><h3>Aún no hay asignaciones</h3><p>Asigna una oportunidad para empezar.</p></div>
    @else
      <div class="ops-table-wrap">
        <table class="ops-table">
          <thead><tr><th>Producto</th><th class="num">Cantidad</th><th>De</th><th>Para</th><th>Andén</th><th>Estado</th><th></th></tr></thead>
          <tbody>
            @foreach($asignaciones as $a)
              <tr>
                <td><div class="ops-prod">{{ $a->item->name ?? '—' }}<small>SKU {{ $a->item->sku ?? '—' }} · {{ $a->creator->name ?? '—' }} · {{ $a->created_at?->format('d/m H:i') }}</small></div></td>
                <td class="num">{{ number_format($a->qty) }}</td>
                <td>{{ $a->reception->folio ?? 'Recepción #' . $a->reception_id }}</td>
                <td><b>{{ $a->wave->code ?? 'Ola #' . $a->pick_wave_id }}</b>@if($a->wave?->order_number)<small style="display:block; color:var(--ui-muted);">{{ $a->wave->order_number }}</small>@endif</td>
                <td>@if($a->stagingLocation)<span class="ops-loc">{{ $a->stagingLocation->code }}</span>@else — @endif</td>
                <td><span class="ops-pill {{ $colorEstado[$a->status] ?? '' }}">{{ $a->status_label }}</span></td>
                <td>
                  @if(isset($siguiente[$a->status]))
                    <div class="ops-actions" style="justify-content:flex-end;">
                      <form method="POST" action="{{ route('admin.wms.crossdock.advance', $a) }}">@csrf @method('PATCH')<button type="submit" class="ops-btn is-sm">{{ $siguiente[$a->status] }}</button></form>
                      <form method="POST" action="{{ route('admin.wms.crossdock.cancel', $a) }}">@csrf @method('PATCH')<button type="submit" class="ops-btn is-danger is-sm">Cancelar</button></form>
                    </div>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
@endsection

@push('scripts')
  @include('partials.ui-tour')
  <script>
    UITour.registrar('wms-crossdock', {
      version: 1,
      auto: true,
      pasos: [
    {
        "el": "[data-tour=\"kpis\"]",
        "titulo": "Qué puedes ahorrarte hoy",
        "texto": "Cada oportunidad es mercancía que no tienes que guardar y volver a bajar. Es tiempo y espacio."
    },
    {
        "el": "[data-tour=\"oportunidades\"]",
        "titulo": "Las coincidencias",
        "texto": "Aquí se cruza lo que acaba de llegar contra lo que un pedido abierto sigue esperando."
    },
    {
        "el": "[data-tour=\"asignaciones\"]",
        "titulo": "Sigue cada una",
        "texto": "Asignado, en andén, entregado. Así sabes qué va en camino y qué ya se surtió."
    }
],
    });
  </script>
@endpush
