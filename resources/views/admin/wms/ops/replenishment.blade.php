@extends('layouts.app')
@section('title', 'WMS · Reabastecimiento')

@push('styles')
  @include('admin.wms.ops._estilos')
@endpush

@section('content')
@php
  $colorPrioridad = ['alta' => 'rojo', 'media' => 'ambar', 'normal' => 'azul'];
  $totalSugerido = collect($sugerencias)->sum('cubierto');
@endphp

<div class="ops-wrap">
  @include('admin.wms.ops._nav', [
      'opsTitulo' => 'Reabastecimiento',
      'opsSub' => 'Mantiene surtidas las ubicaciones de picking. Cuando una cae a su mínimo, o las olas abiertas piden más de lo que tiene, se propone bajar mercancía de las ubicaciones de reserva.',
      'opsTour' => 'wms-reabasto',
      'opsPasos' => [
          ['Revisa lo sugerido', 'El sistema ya detectó qué ubicaciones de picking se están quedando vacías.'],
          ['Genera las tareas', 'Marca las que sí quieres surtir y pulsa Generar tareas. Puedes asignarlas a alguien.'],
          ['Baja la mercancía', 'El almacenista va a la ubicación de reserva y lleva las piezas a la de picking.'],
          ['Confirma el movimiento', 'Escribe cuántas piezas movió y pulsa Mover. El stock se actualiza solo.'],
      ],
  ])

  <div class="ops-kpis" data-tour="kpis">
    <div class="ops-kpi {{ count($sugerencias) ? 'ambar' : 'verde' }}"><b>{{ count($sugerencias) }}</b><span>Ubicaciones de picking por reponer</span></div>
    <div class="ops-kpi azul"><b>{{ number_format($totalSugerido) }}</b><span>Unidades sugeridas a mover</span></div>
    <div class="ops-kpi"><b>{{ $tareas->count() }}</b><span>Tareas pendientes</span></div>
    <div class="ops-kpi {{ count($compras) ? 'rojo' : 'verde' }}"><b>{{ count($compras) }}</b><span>Productos por comprar</span></div>
  </div>

  {{-- ===================== Sugerencias ===================== --}}
  <form method="POST" action="{{ route('admin.wms.replenishment.generate') }}" class="ops-card" data-tour="sugerencias">
    @csrf
    <div class="ops-card-head">
      <div>
        <h2>Sugerencias de reposición</h2>
        <p>Se calcula con el mínimo y máximo del producto y su ubicación principal. Marca las que quieras convertir en tarea.</p>
      </div>
      @if(count($sugerencias))
        <div class="ops-actions">
          <select name="assigned_user_id" class="ops-input" style="width:auto;" aria-label="Asignar a">
            <option value="">Sin asignar</option>
            @foreach($usuarios as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
          </select>
          <button type="submit" class="ops-btn is-primary">Generar tareas</button>
        </div>
      @endif
    </div>

    @if(!count($sugerencias))
      <div class="ops-empty">
        <h3>Todo surtido</h3>
        <p>Ninguna ubicación de picking está en su mínimo. Para que un producto entre aquí necesita ubicación principal y stock mínimo definidos.</p>
      </div>
    @else
      <div class="ops-table-wrap">
        <table class="ops-table">
          <thead>
            <tr>
              <th style="width:36px;"><input type="checkbox" checked data-todos aria-label="Marcar todas"></th>
              <th>Producto</th>
              <th>Picking</th>
              <th class="num">Hay / Mín / Máx</th>
              <th class="num">Pedido en olas</th>
              <th>Mover desde reserva</th>
              <th>Prioridad</th>
            </tr>
          </thead>
          <tbody>
            @foreach($sugerencias as $s)
              <tr>
                <td>
                  @foreach($s['fuentes'] as $f)
                    <input type="checkbox" name="sel[]" value="{{ $s['item']->id }}-{{ $f['location']->id }}-{{ $s['to']->id }}" checked data-sel style="display:block; margin:3px 0;">
                  @endforeach
                </td>
                <td><div class="ops-prod">{{ $s['item']->name }}<small>SKU {{ $s['item']->sku ?: '—' }}</small></div></td>
                <td><span class="ops-loc">{{ $s['to']->code }}</span></td>
                <td class="num"><b style="color:{{ $s['pickQty'] <= 0 ? 'var(--ui-danger-ink)' : 'inherit' }}">{{ number_format($s['pickQty']) }}</b> / {{ number_format($s['min']) }} / {{ number_format($s['max']) }}</td>
                <td class="num">{{ $s['demanda'] ? number_format($s['demanda']) : '—' }}</td>
                <td>
                  @foreach($s['fuentes'] as $f)
                    <div style="margin:2px 0;">
                      <b>{{ number_format($f['mover']) }}</b> de <span class="ops-loc">{{ $f['location']->code }}</span>
                      <small style="color:var(--ui-muted);">(tiene {{ number_format($f['qty']) }})</small>
                    </div>
                  @endforeach
                  @if($s['cubierto'] < $s['need'])
                    <small style="color:var(--ui-warn-ink); font-weight:600;">La reserva solo cubre {{ number_format($s['cubierto']) }} de {{ number_format($s['need']) }}.</small>
                  @endif
                </td>
                <td><span class="ops-pill {{ $colorPrioridad[$s['priority']] }}">{{ ucfirst($s['priority']) }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </form>

  {{-- ===================== Tareas pendientes ===================== --}}
  <div class="ops-card" data-tour="tareas">
    <div class="ops-card-head">
      <div>
        <h2>Tareas pendientes</h2>
        <p>Al completar una tarea el stock pasa de la reserva a la ubicación de picking y queda en la bitácora de movimientos.</p>
      </div>
    </div>

    @if($tareas->isEmpty())
      <div class="ops-empty"><h3>Sin tareas pendientes</h3><p>Genera tareas desde las sugerencias de arriba.</p></div>
    @else
      <div class="ops-table-wrap">
        <table class="ops-table">
          <thead><tr><th>#</th><th>Producto</th><th>Ruta</th><th>Prioridad</th><th>Asignada a</th><th style="text-align:right;">Completar</th></tr></thead>
          <tbody>
            @foreach($tareas as $t)
              <tr>
                <td>{{ $t->id }}</td>
                <td><div class="ops-prod">{{ $t->item->name ?? 'Producto eliminado' }}<small>SKU {{ $t->item->sku ?? '—' }}</small></div></td>
                <td><span class="ops-loc">{{ $t->fromLocation->code ?? '—' }}</span><span class="ops-flecha">→</span><span class="ops-loc">{{ $t->toLocation->code ?? '—' }}</span></td>
                <td><span class="ops-pill {{ $colorPrioridad[$t->priority] ?? '' }}">{{ ucfirst($t->priority) }}</span></td>
                <td>{{ $t->assignedUser->name ?? 'Sin asignar' }}</td>
                <td>
                  <div class="ops-actions" style="justify-content:flex-end;">
                    <form method="POST" action="{{ route('admin.wms.replenishment.complete', $t) }}" class="ops-actions">
                      @csrf
                      <input type="number" name="qty" value="{{ $t->qty_suggested }}" min="1" class="ops-input ops-qty" aria-label="Cantidad movida">
                      <button type="submit" class="ops-btn is-primary is-sm">Mover</button>
                    </form>
                    <form method="POST" action="{{ route('admin.wms.replenishment.cancel', $t) }}">
                      @csrf @method('PATCH')
                      <button type="submit" class="ops-btn is-danger is-sm">Cancelar</button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- ===================== Comprar ===================== --}}
  <div class="ops-card" data-tour="compras">
    <div class="ops-card-head">
      <div>
        <h2>Por comprar</h2>
        <p>Productos cuyo stock total ya está en o bajo su mínimo. Aquí ya no alcanza con mover: hay que reponer con el proveedor.</p>
      </div>
    </div>

    @if(!count($compras))
      <div class="ops-empty"><h3>Nada por comprar</h3><p>Ningún producto está en su mínimo global.</p></div>
    @else
      <div class="ops-table-wrap">
        <table class="ops-table">
          <thead><tr><th>Producto</th><th class="num">Stock</th><th class="num">Mínimo</th><th class="num">Objetivo</th><th class="num">Pedido en olas</th><th class="num">Comprar</th></tr></thead>
          <tbody>
            @foreach($compras as $c)
              <tr>
                <td><div class="ops-prod">{{ $c['item']->name }}<small>SKU {{ $c['item']->sku ?: '—' }}{{ $c['item']->brand_name ? ' · '.$c['item']->brand_name : '' }}</small></div></td>
                <td class="num"><b style="color:{{ $c['stock'] <= 0 ? 'var(--ui-danger-ink)' : 'var(--ui-warn-ink)' }}">{{ number_format($c['stock']) }}</b></td>
                <td class="num">{{ number_format($c['min']) }}</td>
                <td class="num">{{ number_format($c['objetivo']) }}</td>
                <td class="num">{{ $c['demanda'] ? number_format($c['demanda']) : '—' }}</td>
                <td class="num"><span class="ops-pill rojo">{{ number_format($c['comprar']) }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- ===================== Historial ===================== --}}
  @if($hechas->isNotEmpty())
    <div class="ops-card">
      <div class="ops-card-head"><div><h2>Últimos reabastecimientos</h2></div></div>
      <div class="ops-table-wrap">
        <table class="ops-table">
          <thead><tr><th>Producto</th><th>Ruta</th><th class="num">Movido</th><th>Quién</th><th>Cuándo</th></tr></thead>
          <tbody>
            @foreach($hechas as $t)
              <tr>
                <td><div class="ops-prod">{{ $t->item->name ?? '—' }}</div></td>
                <td><span class="ops-loc">{{ $t->fromLocation->code ?? '—' }}</span><span class="ops-flecha">→</span><span class="ops-loc">{{ $t->toLocation->code ?? '—' }}</span></td>
                <td class="num">{{ number_format($t->qty_moved) }}</td>
                <td>{{ $t->completedBy->name ?? '—' }}</td>
                <td>{{ $t->completed_at?->format('d/m/Y H:i') }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif
</div>
@endsection

@push('scripts')
<script>
  // "Marcar todas" de las sugerencias
  document.querySelector('[data-todos]')?.addEventListener('change', function () {
    document.querySelectorAll('[data-sel]').forEach(c => c.checked = this.checked);
  });
</script>
@endpush

@push('scripts')
  @include('partials.ui-tour')
  <script>
    UITour.registrar('wms-reabasto', {
      version: 1,
      auto: true,
      pasos: [
    {
        "el": "[data-tour=\"kpis\"]",
        "titulo": "Cómo va el almacén",
        "texto": "De un vistazo: cuántas ubicaciones se están quedando vacías y cuánto hay que mover hoy."
    },
    {
        "el": "[data-tour=\"sugerencias\"]",
        "titulo": "Lo que el sistema propone",
        "texto": "Aquí salen los productos que ya llegaron a su mínimo. Desmarca lo que no quieras y pulsa Generar tareas."
    },
    {
        "el": "[data-tour=\"tareas\"]",
        "titulo": "El trabajo del almacenista",
        "texto": "Cada tarea dice de dónde a dónde mover. Cuando lo haga, escribe cuántas piezas movió y pulsa Mover."
    },
    {
        "el": "[data-tour=\"compras\"]",
        "titulo": "Cuando ya no hay de dónde",
        "texto": "Si no queda reserva, el producto aparece aquí: ya no alcanza con mover, hay que comprarle al proveedor."
    }
],
    });
  </script>
@endpush
