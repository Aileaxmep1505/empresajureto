@extends('layouts.app')
{{-- Pantalla preparada para modo oscuro: el layout no la fuerza a claro --}}
@section('tema_oscuro', '1')
@section('title', 'WMS · Conteos de inventario')

@push('styles')
  @include('admin.wms.ops._estilos')
@endpush

@section('content')
@php $colorEstado = ['abierto' => 'azul', 'cerrado' => 'verde', 'cancelado' => '']; @endphp

<div class="ops-wrap">
  @include('admin.wms.ops._nav', [
      'opsTitulo' => 'Conteos de inventario',
      'opsSub' => 'Genera tareas de recuento por ubicación, por productos críticos, por muestra aleatoria o de todo el almacén. Al cerrar el conteo se ajustan las diferencias en la ubicación y en el stock global.',
      'opsTour' => 'wms-conteos',
      'opsPasos' => [
          ['Elige qué contar', 'Unas ubicaciones, los productos críticos, una muestra al azar o todo el almacén.'],
          ['Créalo y asígnalo', 'Dile a quién le toca. El sistema guarda cuánto cree que hay de cada cosa.'],
          ['Cuenta en el piso', 'Abre el conteo desde el celular, escanea la ubicación y captura lo que ves.'],
          ['Cierra y ajusta', 'Al cerrar, el inventario se corrige solo con las diferencias que encontraste.'],
      ],
  ])

  <div class="ops-kpis" data-tour="exactitud">
    <div class="ops-kpi {{ $exactitud === null ? '' : ($exactitud >= 95 ? 'verde' : ($exactitud >= 85 ? 'ambar' : 'rojo')) }}">
      <b>{{ $exactitud === null ? '—' : $exactitud . '%' }}</b><span>Exactitud de inventario (90 días)</span>
    </div>
    <div class="ops-kpi azul"><b>{{ $conteos->getCollection()->where('status', 'abierto')->count() }}</b><span>Conteos abiertos en esta página</span></div>
    <div class="ops-kpi"><b>{{ $conteos->total() }}</b><span>Conteos registrados</span></div>
    <div class="ops-kpi"><b>{{ $ubicaciones->count() }}</b><span>Ubicaciones disponibles</span></div>
  </div>

  {{-- ===================== Nuevo conteo ===================== --}}
  <form method="POST" action="{{ route('admin.wms.counts.store') }}" class="ops-card" data-tour="nuevo">
    @csrf
    <div class="ops-card-head">
      <div><h2>Nuevo conteo</h2><p>Se toma una foto de lo que el sistema cree que hay; después solo se captura lo encontrado.</p></div>
      <button type="submit" class="ops-btn is-primary">Crear conteo</button>
    </div>
    <div class="ops-card-body">
      <div class="ops-grid">
        <div class="ops-field">
          <label for="cWarehouse">Bodega</label>
          <select id="cWarehouse" name="warehouse_id" required>
            @foreach($bodegas as $b)<option value="{{ $b->id }}" @selected(old('warehouse_id') == $b->id)>{{ $b->name }}</option>@endforeach
          </select>
        </div>

        <div class="ops-field">
          <label for="cScope">Qué se cuenta</label>
          <select id="cScope" name="scope" required>
            @foreach(\App\Models\WmsCount::SCOPES as $k => $v)<option value="{{ $k }}" @selected(old('scope') === $k)>{{ $v }}</option>@endforeach
          </select>
        </div>

        <div class="ops-field" data-solo="aleatorio" hidden>
          <label for="cSample">Tamaño de la muestra</label>
          <input id="cSample" type="number" name="sample_size" min="1" max="500" value="{{ old('sample_size', 10) }}">
        </div>

        <div class="ops-field">
          <label for="cUser">Asignar a</label>
          <select id="cUser" name="assigned_user_id">
            <option value="">Sin asignar</option>
            @foreach($usuarios as $u)<option value="{{ $u->id }}" @selected(old('assigned_user_id') == $u->id)>{{ $u->name }}</option>@endforeach
          </select>
        </div>

        <div class="ops-field span-2" data-solo="ubicaciones">
          <label for="cLocs">Ubicaciones</label>
          <select id="cLocs" name="location_ids[]" multiple size="6">
            @foreach($ubicaciones as $l)
              <option value="{{ $l->id }}" data-wh="{{ $l->warehouse_id }}" @selected(in_array($l->id, old('location_ids', [])))>{{ $l->code }}</option>
            @endforeach
          </select>
          <small>Ctrl o Shift para elegir varias. Solo se muestran las de la bodega seleccionada.</small>
        </div>

        <div class="ops-field span-2">
          <label for="cNotes">Notas</label>
          <input id="cNotes" type="text" name="notes" maxlength="500" value="{{ old('notes') }}" placeholder="Ej. conteo cíclico semanal del pasillo A">
        </div>

        <div class="ops-field" style="justify-content:flex-end;">
          <label class="ops-check">
            <input type="hidden" name="blind" value="0">
            <input type="checkbox" name="blind" value="1" @checked(old('blind', '1') == '1')>
            Conteo a ciegas
          </label>
          <small>No muestra la cantidad esperada, para que se cuente de verdad.</small>
        </div>
      </div>
    </div>
  </form>

  {{-- ===================== Historial ===================== --}}
  <div class="ops-card" data-tour="lista">
    <div class="ops-card-head"><div><h2>Conteos</h2></div></div>

    @if($conteos->isEmpty())
      <div class="ops-empty"><h3>Aún no hay conteos</h3><p>Crea el primero con el formulario de arriba.</p></div>
    @else
      <div class="ops-table-wrap">
        <table class="ops-table">
          <thead><tr><th>Folio</th><th>Bodega</th><th>Alcance</th><th>Avance</th><th>Asignado a</th><th>Estado</th><th>Creado</th><th></th></tr></thead>
          <tbody>
            @foreach($conteos as $c)
              @php $avance = $c->lines_count ? (int) round($c->contadas_count / $c->lines_count * 100) : 0; @endphp
              <tr>
                <td><b>{{ $c->folio }}</b>@if($c->blind) <span class="ops-pill">A ciegas</span>@endif</td>
                <td>{{ $c->warehouse->name ?? '—' }}</td>
                <td>{{ $c->scope_label }}</td>
                <td style="min-width:150px;">
                  <div class="ops-progress"><i style="width:{{ $avance }}%"></i></div>
                  <small style="color:var(--ui-muted); font-weight:700;">{{ $c->contadas_count }} de {{ $c->lines_count }} · {{ $avance }}%</small>
                </td>
                <td>{{ $c->assignedUser->name ?? 'Sin asignar' }}</td>
                <td><span class="ops-pill {{ $colorEstado[$c->status] ?? '' }}">{{ ucfirst($c->status) }}</span></td>
                <td>{{ $c->created_at?->format('d/m/Y H:i') }}</td>
                <td style="text-align:right;"><a href="{{ route('admin.wms.counts.show', $c) }}" class="ops-btn is-sm">{{ $c->status === 'abierto' ? 'Contar' : 'Ver' }}</a></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @if($conteos->hasPages())
        <div class="ops-card-body">{{ $conteos->links() }}</div>
      @endif
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const scope = document.getElementById('cScope');
  const wh = document.getElementById('cWarehouse');
  const locs = document.getElementById('cLocs');

  function pintar() {
    // Campos que solo aplican a cierto alcance
    document.querySelectorAll('[data-solo]').forEach(el => { el.hidden = el.dataset.solo !== scope.value; });
    // Solo ubicaciones de la bodega elegida
    [...locs.options].forEach(o => { const ok = o.dataset.wh === wh.value; o.hidden = !ok; if (!ok) o.selected = false; });
  }

  scope.addEventListener('change', pintar);
  wh.addEventListener('change', pintar);
  pintar();
})();
</script>
@endpush

@push('scripts')
  @include('partials.ui-tour')
  <script>
    UITour.registrar('wms-conteos', {
      version: 1,
      auto: true,
      pasos: [
    {
        "el": "[data-tour=\"nuevo\"]",
        "titulo": "Empieza por aquí",
        "texto": "Elige qué se va a contar. Lo más común es \"Por ubicaciones\": marcas dos o tres racks y listo."
    },
    {
        "el": "#cScope",
        "titulo": "Conteo a ciegas",
        "texto": "Si lo dejas marcado, quien cuenta no ve la cantidad esperada. Así el conteo sirve de verdad."
    },
    {
        "el": "[data-tour=\"lista\"]",
        "titulo": "Tus conteos",
        "texto": "La barra muestra el avance. Pulsa Contar para capturar, desde la computadora o el celular."
    },
    {
        "el": "[data-tour=\"exactitud\"]",
        "titulo": "Qué tan confiable eres",
        "texto": "La exactitud sale de los conteos cerrados. Arriba de 95% es sano; abajo de 85% conviene contar más seguido."
    }
],
    });
  </script>
@endpush
