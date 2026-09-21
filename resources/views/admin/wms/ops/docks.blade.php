@extends('layouts.app')
{{-- Pantalla preparada para modo oscuro: el layout no la fuerza a claro --}}
@section('tema_oscuro', '1')
@section('title', 'WMS · Citas de andén')

@push('styles')
  @include('admin.wms.ops._estilos')
  <style>
    .dk-dia{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .dk-dia strong{ font-size:14px; font-weight:600; min-width:200px; text-align:center; text-transform:capitalize; }
    .dk-cols{ display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:1px; background:var(--ui-border); }
    .dk-col{ background:var(--ui-surface); min-height:120px; }
    .dk-col h3{ display:flex; justify-content:space-between; margin:0; padding:10px 14px; font-size:13px; font-weight:600;
                color:var(--ui-ink-2); background:var(--ui-surface-2); border-bottom:1px solid var(--ui-border); }
    .dk-col h3 span{ color:var(--ui-muted); font-weight:500; font-variant-numeric:tabular-nums; }
    .dk-vacio{ padding:26px 12px; text-align:center; color:var(--ui-muted); font-size:13px; }

    .dk-cita{ padding:12px 14px; border-bottom:1px solid var(--ui-border); }
    .dk-cita:last-child{ border-bottom:0; }
    .dk-cita.is-fin{ opacity:.6; }
    /* La operación se distingue por la etiqueta, no por un borde de color. */
    .dk-tipo{ display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:500; color:var(--ui-muted); }
    .dk-tipo i{ width:6px; height:6px; border-radius:999px; background:var(--ui-ok); }
    .dk-cita.salida .dk-tipo i{ background:var(--ui-accent); }
    .dk-hora{ font-size:14px; font-weight:600; font-variant-numeric:tabular-nums; color:var(--ui-ink); }
    .dk-quien{ margin-top:2px; font-size:13.5px; font-weight:600; color:var(--ui-ink); }
    .dk-meta{ margin-top:2px; font-size:12.5px; line-height:1.45; color:var(--ui-muted); }
    .dk-pie{ display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-top:9px; }
  </style>
@endpush

@section('content')
@php
  $colorEstado = ['programada' => 'azul', 'llego' => 'ambar', 'en_anden' => 'ambar', 'terminada' => 'verde', 'no_llego' => 'rojo', 'cancelada' => ''];
  // Botón principal según el estado: [estado siguiente, etiqueta]
  $paso = ['programada' => ['llego', 'Llegó'], 'llego' => ['en_anden', 'Entra al andén'], 'en_anden' => ['terminada', 'Terminó']];
@endphp

<div class="ops-wrap">
  @include('admin.wms.ops._nav', [
      'opsTitulo' => 'Citas de andén',
      'opsSub' => 'Agenda por andén de los vehículos que llegan a descargar o a cargar. Registra llegada, entrada y salida para medir puntualidad y tiempo en andén, y evitar que se junten.',
      'opsTour' => 'wms-andenes',
      'opsPasos' => [
          ['Programa la cita', 'Andén, hora y cuánto va a tardar. Si se empalma con otra, el sistema te avisa.'],
          ['Marca la llegada', 'Cuando el camión llega al patio, pulsa Llegó. De ahí sale la puntualidad.'],
          ['Entra al andén', 'Al empezar a cargar o descargar, pulsa Entra al andén.'],
          ['Ciérrala', 'Al terminar, pulsa Terminó y queda medido cuánto tiempo ocupó el andén.'],
      ],
  ])

  <div class="ops-kpis" data-tour="kpis">
    <div class="ops-kpi azul"><b>{{ $totalHoy }}</b><span>Citas este día</span></div>
    <div class="ops-kpi {{ $kpis['en_curso'] ? 'ambar' : '' }}"><b>{{ $kpis['en_curso'] }}</b><span>Vehículos en patio o andén ahora</span></div>
    <div class="ops-kpi {{ $kpis['puntualidad'] === null ? '' : ($kpis['puntualidad'] >= 85 ? 'verde' : 'rojo') }}"><b>{{ $kpis['puntualidad'] === null ? '—' : $kpis['puntualidad'] . '%' }}</b><span>Puntualidad (30 días, tolerancia {{ \App\Models\WmsDockAppointment::TOLERANCIA_MIN }} min)</span></div>
    <div class="ops-kpi"><b>{{ $kpis['min_anden'] === null ? '—' : $kpis['min_anden'] . ' min' }}</b><span>Tiempo promedio en andén · {{ $kpis['no_llegaron'] }} no llegaron</span></div>
  </div>

  {{-- ===================== Agenda del día ===================== --}}
  <div class="ops-card" data-tour="agenda">
    <div class="ops-card-head">
      <div class="dk-dia">
        <a class="ops-btn is-sm" href="{{ route('admin.wms.docks.index', ['dia' => $dia->copy()->subDay()->toDateString()]) }}" aria-label="Día anterior">←</a>
        <strong>{{ $dia->locale('es')->isoFormat('dddd D [de] MMMM') }}</strong>
        <a class="ops-btn is-sm" href="{{ route('admin.wms.docks.index', ['dia' => $dia->copy()->addDay()->toDateString()]) }}" aria-label="Día siguiente">→</a>
        @unless($dia->isToday())<a class="ops-btn is-sm" href="{{ route('admin.wms.docks.index') }}">Hoy</a>@endunless
      </div>
      <form method="GET" action="{{ route('admin.wms.docks.index') }}" class="ops-actions">
        <input type="date" name="dia" value="{{ $dia->toDateString() }}" class="ops-input" style="width:auto;" onchange="this.form.submit()" aria-label="Ir a una fecha">
      </form>
    </div>

    <div class="dk-cols">
      @foreach($andenes as $anden)
        @php $delAnden = $citas->get($anden, collect()); @endphp
        <div class="dk-col">
          <h3>{{ $anden }} <span>{{ $delAnden->count() }}</span></h3>

          @forelse($delAnden as $c)
            <div class="dk-cita {{ $c->type }} {{ in_array($c->status, ['terminada', 'cancelada', 'no_llego']) ? 'is-fin' : '' }}">
              <span class="dk-tipo"><i></i>{{ $c->type === 'entrada' ? 'Descarga' : 'Carga' }}</span>
              <div class="dk-hora">{{ $c->scheduled_at->format('H:i') }} – {{ $c->scheduled_at->copy()->addMinutes($c->duration_min)->format('H:i') }}</div>
              <div class="dk-quien">{{ $c->carrier ?: 'Sin transportista' }}</div>
              <div class="dk-meta">
                {{ collect([$c->vehicle_plate, $c->driver_name, $c->reference])->filter()->implode(' · ') ?: 'Sin datos del vehículo' }}
                @if($c->notes)<br>{{ $c->notes }}@endif
              </div>
              <div class="dk-pie">
                <span class="ops-pill {{ $colorEstado[$c->status] ?? '' }}">{{ $c->status_label }}</span>
                @if($c->puntual === true)<span class="ops-pill verde">Puntual</span>@elseif($c->puntual === false)<span class="ops-pill rojo">Tarde {{ (int) $c->scheduled_at->diffInMinutes($c->arrived_at) }} min</span>@endif
                @if($c->minutos_en_anden !== null)<span class="ops-pill">{{ $c->minutos_en_anden }} min en andén</span>@endif
              </div>

              <div class="dk-pie">
                @isset($paso[$c->status])
                  <form method="POST" action="{{ route('admin.wms.docks.status', $c) }}">@csrf @method('PATCH')
                    <input type="hidden" name="status" value="{{ $paso[$c->status][0] }}">
                    <button type="submit" class="ops-btn is-primary is-sm">{{ $paso[$c->status][1] }}</button>
                  </form>
                @endisset
                @if($c->status === 'programada')
                  <form method="POST" action="{{ route('admin.wms.docks.status', $c) }}">@csrf @method('PATCH')
                    <input type="hidden" name="status" value="no_llego"><button type="submit" class="ops-btn is-sm">No llegó</button>
                  </form>
                  <form method="POST" action="{{ route('admin.wms.docks.status', $c) }}">@csrf @method('PATCH')
                    <input type="hidden" name="status" value="cancelada"><button type="submit" class="ops-btn is-danger is-sm">Cancelar</button>
                  </form>
                @endif
                @if(in_array($c->status, ['cancelada', 'no_llego']))
                  <form method="POST" action="{{ route('admin.wms.docks.destroy', $c) }}" onsubmit="return confirm('¿Eliminar esta cita?');">@csrf @method('DELETE')
                    <button type="submit" class="ops-btn is-danger is-sm">Eliminar</button>
                  </form>
                @endif
              </div>
            </div>
          @empty
            <div class="dk-vacio">Libre todo el día</div>
          @endforelse
        </div>
      @endforeach
    </div>
  </div>

  {{-- ===================== Nueva cita ===================== --}}
  <form method="POST" action="{{ route('admin.wms.docks.store') }}" class="ops-card" data-tour="nueva">
    @csrf
    <div class="ops-card-head">
      <div><h2>Programar cita</h2><p>No se permiten dos citas activas que se traslapen en el mismo andén.</p></div>
      <button type="submit" class="ops-btn is-primary">Programar</button>
    </div>
    <div class="ops-card-body">
      <div class="ops-grid">
        <div class="ops-field">
          <label for="dDock">Andén</label>
          <select id="dDock" name="dock" required>@foreach($andenes as $a)<option value="{{ $a }}" @selected(old('dock') === $a)>{{ $a }}</option>@endforeach</select>
        </div>
        <div class="ops-field">
          <label for="dType">Operación</label>
          <select id="dType" name="type" required>
            <option value="entrada" @selected(old('type') === 'entrada')>Descarga (entrada)</option>
            <option value="salida" @selected(old('type') === 'salida')>Carga (salida)</option>
          </select>
        </div>
        <div class="ops-field">
          <label for="dWhen">Fecha y hora</label>
          <input id="dWhen" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', $dia->copy()->setTime(9, 0)->format('Y-m-d\TH:i')) }}" required>
        </div>
        <div class="ops-field">
          <label for="dDur">Duración (min)</label>
          <input id="dDur" type="number" name="duration_min" min="5" max="1440" step="5" value="{{ old('duration_min', 60) }}" required>
        </div>
        <div class="ops-field"><label for="dCar">Transportista / proveedor</label><input id="dCar" type="text" name="carrier" maxlength="120" value="{{ old('carrier') }}"></div>
        <div class="ops-field"><label for="dPlate">Placas</label><input id="dPlate" type="text" name="vehicle_plate" maxlength="30" value="{{ old('vehicle_plate') }}"></div>
        <div class="ops-field"><label for="dDrv">Chofer</label><input id="dDrv" type="text" name="driver_name" maxlength="120" value="{{ old('driver_name') }}"></div>
        <div class="ops-field"><label for="dRef">Referencia</label><input id="dRef" type="text" name="reference" maxlength="120" value="{{ old('reference') }}" placeholder="OC, embarque, pedido…"></div>
        @if($bodegas->count() > 1)
          <div class="ops-field">
            <label for="dWh">Bodega</label>
            <select id="dWh" name="warehouse_id"><option value="">—</option>@foreach($bodegas as $b)<option value="{{ $b->id }}" @selected(old('warehouse_id') == $b->id)>{{ $b->name }}</option>@endforeach</select>
          </div>
        @endif
        <div class="ops-field span-2"><label for="dNotes">Notas</label><input id="dNotes" type="text" name="notes" maxlength="500" value="{{ old('notes') }}"></div>
      </div>
    </div>
  </form>

  {{-- ===================== Andenes ===================== --}}
  <form method="POST" action="{{ route('admin.wms.docks.list') }}" class="ops-card" data-tour="andenes">
    @csrf
    <div class="ops-card-head">
      <div><h2>Andenes del almacén</h2><p>Uno por línea. Aquí defines las columnas de la agenda.</p></div>
      <button type="submit" class="ops-btn">Guardar andenes</button>
    </div>
    <div class="ops-card-body">
      <div class="ops-field"><textarea name="andenes" rows="4" required>{{ implode("\n", $andenes) }}</textarea></div>
    </div>
  </form>
</div>
@endsection

@push('scripts')
  @include('partials.ui-tour')
  <script>
    UITour.registrar('wms-andenes', {
      version: 1,
      auto: true,
      pasos: [
    {
        "el": "[data-tour=\"agenda\"]",
        "titulo": "El día, andén por andén",
        "texto": "Cada columna es un andén. Las flechas cambian de día."
    },
    {
        "el": "[data-tour=\"nueva\"]",
        "titulo": "Programar una cita",
        "texto": "Pon el andén, la hora y la duración. Si se empalma con otra cita, no te deja guardarla."
    },
    {
        "el": "[data-tour=\"kpis\"]",
        "titulo": "Cómo van tus andenes",
        "texto": "Puntualidad y tiempo promedio de los últimos 30 días. Sirve para reclamar o reacomodar horarios."
    },
    {
        "el": "[data-tour=\"andenes\"]",
        "titulo": "Tus andenes",
        "texto": "Escribe aquí los andenes reales de tu bodega, uno por línea. Son las columnas de la agenda."
    }
],
    });
  </script>
@endpush
