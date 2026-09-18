{{--
   Encabezado, pestañas, guía y avisos de las operaciones del WMS.

   Espera:
     $opsTitulo    título de la pantalla
     $opsSub       una línea explicando para qué sirve
     $opsTour      (opcional) clave del tour registrado en la vista
     $opsPasos     (opcional) [['Título corto', 'Qué hace la persona aquí'], ...]
     $opsAcciones  (opcional) HTML de botones a la derecha
--}}
@php
  $opsTabs = [
      ['admin.wms.replenishment.index', 'Reabastecimiento', '<path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/><path d="M3 21v-5h5"/>'],
      ['admin.wms.counts.index', 'Conteos', '<path d="M9 11l3 3 8-8"/><path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9"/>'],
      ['admin.wms.crossdock.index', 'Cross-docking', '<path d="M4 7h11l-3-3"/><path d="M20 17H9l3 3"/><path d="M4 7v4"/><path d="M20 17v-4"/>'],
      ['admin.wms.labor.index', 'Productividad', '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M17 11l2 2 3.5-4"/>'],
      ['admin.wms.docks.index', 'Citas de andén', '<rect x="1" y="7" width="13" height="10" rx="1.5"/><path d="M14 10h4l3 3v4h-7"/><circle cx="6" cy="18.5" r="1.8"/><circle cx="17.5" cy="18.5" r="1.8"/>'],
  ];
  // admin.wms.<modulo>.<accion> -> el tercer segmento dice en qué módulo estamos
  $opsActual = explode('.', (string) request()->route()?->getName())[2] ?? '';
  $opsPasos = $opsPasos ?? [];
@endphp

<div class="ops-head">
  <div>
    <a href="{{ route('admin.wms.home') }}" class="ops-back">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M15 18l-6-6 6-6"/></svg>
      WMS · Bodega
    </a>
    <h1 class="ops-title">{{ $opsTitulo }}</h1>
    <p class="ops-sub">{{ $opsSub }}</p>
  </div>

  <div class="ops-actions">
    @isset($opsAcciones){!! $opsAcciones !!}@endisset
    @isset($opsTour)
      <button type="button" class="tour-abrir" data-tour-start="{{ $opsTour }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="9"/><path d="M9.6 9.4a2.5 2.5 0 1 1 3.3 2.9c-.6.2-.9.8-.9 1.4v.3"/><path d="M12 17h.01"/>
        </svg>
        ¿Cómo funciona?
      </button>
    @endisset
  </div>
</div>

<nav class="ops-tabs" aria-label="Operaciones del almacén">
  @foreach($opsTabs as [$ruta, $etiqueta, $icono])
    @continue(! \Illuminate\Support\Facades\Route::has($ruta))
    @php $esActual = explode('.', $ruta)[2] === $opsActual; @endphp
    <a href="{{ route($ruta) }}" class="ops-tab {{ $esActual ? 'is-active' : '' }}" @if($esActual) aria-current="page" @endif>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">{!! $icono !!}</svg>
      {{ $etiqueta }}
    </a>
  @endforeach
</nav>

@if(session('ok'))
  <div class="ops-flash ok" role="status">{{ session('ok') }}</div>
@endif
@if(session('error'))
  <div class="ops-flash err" role="alert">{{ session('error') }}</div>
@endif
@if($errors->any())
  <div class="ops-flash err" role="alert">{{ $errors->first() }}</div>
@endif

{{-- Los pasos del proceso, en orden. Se pueden cerrar y quedan guardados así. --}}
@if($opsPasos)
  <section class="ops-pasos" data-pasos="{{ $opsActual }}">
    <button type="button" class="ops-pasos-toggle" data-pasos-toggle aria-expanded="true" aria-controls="opsPasosLista">
      <svg class="ops-pasos-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
      Cómo se hace, paso a paso
    </button>

    <ol class="ops-pasos-lista" id="opsPasosLista">
      @foreach($opsPasos as $n => [$pTitulo, $pTexto])
        <li>
          <span class="ops-pasos-n">{{ $n + 1 }}</span>
          <span class="ops-pasos-txt"><b>{{ $pTitulo }}</b>{{ $pTexto }}</span>
        </li>
      @endforeach
    </ol>
  </section>

  <script>
  (function () {
    // Abrir o cerrar los pasos; la elección se recuerda por pantalla.
    const caja = document.querySelector('[data-pasos]');
    if (!caja) return;
    const btn = caja.querySelector('[data-pasos-toggle]');
    const llave = 'ops-pasos:' + caja.dataset.pasos;

    try { if (localStorage.getItem(llave) === 'cerrado') { caja.classList.add('is-cerrado'); btn.setAttribute('aria-expanded', 'false'); } } catch {}

    btn.addEventListener('click', () => {
      const cerrado = caja.classList.toggle('is-cerrado');
      btn.setAttribute('aria-expanded', cerrado ? 'false' : 'true');
      try { localStorage.setItem(llave, cerrado ? 'cerrado' : 'abierto'); } catch {}
    });
  })();
  </script>
@endif
