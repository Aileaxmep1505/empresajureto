@extends('layouts.app')
{{-- Pantalla preparada para modo oscuro: el layout no la fuerza a claro --}}
@section('tema_oscuro', '1')
@section('title', 'WMS · Conteo ' . $conteo->folio)

@push('styles')
  @include('admin.wms.ops._estilos')
  <style>
    .cnt-scan{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .cnt-scan .ops-input{ flex:1; min-width:220px; }
    .cnt-grupo td{ background:var(--ui-surface-2) !important; font-weight:600; font-size:12.5px; color:var(--ui-ink-2); padding:8px 16px; }
    tr.is-ok td{ background:var(--ui-ok-soft); }
    tr.is-dif td{ background:var(--ui-warn-soft); }
    tr.is-oculto{ display:none; }
    .cnt-var{ font-weight:600; font-variant-numeric:tabular-nums; }
    .cnt-var.mas{ color:var(--ui-ok-ink); } .cnt-var.menos{ color:var(--ui-danger-ink); }
    .cnt-guardado{ font-size:12px; font-weight:500; color:var(--ui-ok-ink); opacity:0; transition:opacity .2s ease; }
    .cnt-guardado.is-on{ opacity:1; }
    .cnt-sugg{ position:relative; }
    .cnt-sugg-list{ position:absolute; left:0; right:0; top:calc(100% + 4px); z-index:20; background:var(--ui-surface); border:1px solid var(--ui-border); border-radius:var(--ui-r);
                    box-shadow:var(--ui-shadow-pop); overflow:hidden; display:none; }
    .cnt-sugg-list.is-open{ display:block; }
    .cnt-sugg-list button{ display:block; width:100%; text-align:left; padding:9px 12px; border:0; background:none; color:var(--ui-ink); font:inherit; font-size:13.5px; cursor:pointer; }
    .cnt-sugg-list button:hover{ background:var(--ui-surface-2); }
    .cnt-sugg-list small{ color:var(--ui-muted); }
  </style>
@endpush

@section('content')
@php
  $abierto = $conteo->isOpen();
  $verEsperado = ! $conteo->blind || ! $abierto;
@endphp

<div class="ops-wrap">
  @include('admin.wms.ops._nav', [
      'opsTitulo' => 'Conteo ' . $conteo->folio,
      'opsSub' => $conteo->scope_label . ' · ' . ($conteo->warehouse->name ?? 'Sin bodega') . ($conteo->blind ? ' · A ciegas' : '') . ($conteo->notes ? ' · ' . $conteo->notes : ''),
      'opsTour' => 'wms-conteo-captura',
      'opsPasos' => $abierto ? [
          ['Escanea la ubicación', 'Con el botón de cámara del celular o con la pistola. La lista se filtra sola.'],
          ['Cuenta y escribe', 'Anota las piezas que ves de verdad. Con Enter saltas al siguiente renglón.'],
          ['¿Apareció algo de más?', 'Agrégalo al final con su ubicación; entrará como sobrante.'],
          ['Cierra el conteo', 'El inventario se ajusta solo con las diferencias. Lo que no contaste no se toca.'],
      ] : [],
  ])

  <div class="ops-kpis">
    <div class="ops-kpi azul"><b><span data-r="contadas">{{ $resumen['contadas'] }}</span> / <span data-r="total">{{ $resumen['total'] }}</span></b><span>Renglones contados</span></div>
    <div class="ops-kpi"><b><span data-r="exactitud">{{ $resumen['exactitud'] === null ? '—' : $resumen['exactitud'] . '%' }}</span></b><span>Exactitud de lo contado</span></div>
    {{-- En un conteo a ciegas abierto las diferencias vienen nulas: se ven hasta cerrar. --}}
    <div class="ops-kpi rojo"><b data-r="faltantes">{!! $resumen['faltantes'] === null ? '<span class="pend">Se ve al cerrar</span>' : number_format($resumen['faltantes']) !!}</b><span>Unidades faltantes</span></div>
    <div class="ops-kpi verde"><b data-r="sobrantes">{!! $resumen['sobrantes'] === null ? '<span class="pend">Se ve al cerrar</span>' : number_format($resumen['sobrantes']) !!}</b><span>Unidades sobrantes</span></div>
  </div>

  <div class="ops-card">
    <div class="ops-card-head">
      <div style="flex:1; min-width:240px;">
        <h2>{{ $abierto ? 'Captura' : 'Resultado del conteo' }}</h2>
        <div class="ops-progress" style="margin-top:8px;"><i data-r="barra" style="width:{{ $resumen['avance'] }}%"></i></div>
      </div>

      @if($abierto)
        <div class="ops-actions" data-tour="cerrar">
          <form method="POST" action="{{ route('admin.wms.counts.cancel', $conteo) }}" onsubmit="return confirm('¿Cancelar el conteo? No se ajusta nada.');">
            @csrf @method('PATCH')
            <button type="submit" class="ops-btn is-danger">Cancelar conteo</button>
          </form>
          <form method="POST" action="{{ route('admin.wms.counts.close', $conteo) }}" onsubmit="return confirm('Se cerrará el conteo y se ajustará el inventario con las diferencias. Lo que quede sin contar no se toca. ¿Continuar?');">
            @csrf
            <button type="submit" class="ops-btn is-primary">Cerrar y ajustar</button>
          </form>
        </div>
      @else
        <span class="ops-pill {{ $conteo->status === 'cerrado' ? 'verde' : '' }}">{{ ucfirst($conteo->status) }}{{ $conteo->closed_at ? ' · ' . $conteo->closed_at->format('d/m/Y H:i') : '' }}</span>
      @endif
    </div>

    @if($abierto)
      <div class="ops-card-body" style="border-bottom:1px solid var(--ui-border);">
        <div class="cnt-scan" data-tour="filtro">
          <input type="search" id="cntFiltro" class="ops-input" placeholder="Escanea o escribe una ubicación, SKU o nombre…" autocomplete="off"
                 data-scan data-scan-enviar="no" data-scan-titulo="Escanea la ubicación o el producto"
                 data-scan-ayuda="Apunta al código QR del rack o al código de barras del producto. La lista se filtra sola.">
          <label class="ops-check"><input type="checkbox" id="cntSoloPend"> Solo pendientes</label>
        </div>
      </div>
    @endif

    @if($lineas->isEmpty())
      <div class="ops-empty"><h3>Sin renglones</h3><p>Las ubicaciones elegidas no tenían existencias registradas. Agrega lo que encuentres con el formulario de abajo.</p></div>
    @else
      <div class="ops-table-wrap">
        <table class="ops-table" id="cntTabla">
          <thead>
            <tr>
              <th>Producto</th>
              @if($verEsperado)<th class="num">Esperado</th>@endif
              <th class="num">Contado</th>
              @if($verEsperado)<th class="num">Diferencia</th>@endif
              <th>{{ $abierto ? '' : 'Contó' }}</th>
            </tr>
          </thead>
          <tbody>
            @php $ubicacionActual = null; @endphp
            @foreach($lineas as $l)
              @if($ubicacionActual !== $l->location_id)
                @php $ubicacionActual = $l->location_id; @endphp
                <tr class="cnt-grupo" data-grupo="{{ $l->location->code ?? '' }}"><td colspan="5">Ubicación {{ $l->location->code ?? '—' }}</td></tr>
              @endif
              <tr data-linea="{{ $l->id }}"
                  data-texto="{{ mb_strtolower(($l->location->code ?? '') . ' ' . ($l->item->sku ?? '') . ' ' . ($l->item->name ?? '')) }}"
                  class="{{ $l->isCounted() ? ($l->variance === 0 ? 'is-ok' : 'is-dif') : '' }}">
                <td><div class="ops-prod">{{ $l->item->name ?? 'Producto eliminado' }}<small>SKU {{ $l->item->sku ?? '—' }}{{ $l->note ? ' · ' . $l->note : '' }}</small></div></td>
                @if($verEsperado)<td class="num">{{ number_format($l->expected_qty) }}</td>@endif
                <td class="num">
                  @if($abierto)
                    <input type="number" min="0" class="ops-input ops-qty" value="{{ $l->counted_qty }}" placeholder="—"
                           data-contado data-url="{{ route('admin.wms.counts.lines.save', [$conteo, $l]) }}" aria-label="Cantidad contada">
                  @else
                    {{ $l->counted_qty === null ? 'Sin contar' : number_format($l->counted_qty) }}
                  @endif
                </td>
                @if($verEsperado)
                  <td class="num">
                    <span class="cnt-var {{ $l->variance > 0 ? 'mas' : ($l->variance < 0 ? 'menos' : '') }}" data-var>
                      {{ $l->variance === null ? '—' : ($l->variance > 0 ? '+' : '') . number_format($l->variance) }}
                    </span>
                  </td>
                @endif
                <td>
                  @if($abierto)
                    <span class="cnt-guardado" data-ok>Guardado</span>
                  @else
                    {{ $l->counter->name ?? '—' }} @if($l->adjusted)<span class="ops-pill ambar">Ajustado</span>@endif
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- ===================== Agregar encontrado ===================== --}}
  @if($abierto)
    <form method="POST" action="{{ route('admin.wms.counts.lines.add', $conteo) }}" class="ops-card" data-tour="agregar">
      @csrf
      <div class="ops-card-head">
        <div><h2>Encontré algo que no estaba en la lista</h2><p>Agrega el producto con la ubicación donde apareció; entrará como sobrante al cerrar.</p></div>
        <button type="submit" class="ops-btn">Agregar renglón</button>
      </div>
      <div class="ops-card-body">
        <div class="ops-grid">
          <div class="ops-field">
            <label for="aLoc">Ubicación</label>
            <select id="aLoc" name="location_id" required>
              @foreach($ubicaciones as $u)<option value="{{ $u->id }}">{{ $u->code }}</option>@endforeach
            </select>
          </div>
          <div class="ops-field span-2 cnt-sugg">
            <label for="aProd">Producto</label>
            <input id="aProd" type="text" placeholder="Busca por nombre o SKU…" autocomplete="off" required>
            <input type="hidden" name="catalog_item_id" id="aProdId" required>
            <div class="cnt-sugg-list" id="aProdList"></div>
          </div>
          <div class="ops-field">
            <label for="aQty">Cantidad contada</label>
            <input id="aQty" type="number" name="counted_qty" min="0" required>
          </div>
        </div>
      </div>
    </form>
  @endif
</div>
@endsection

@push('scripts')
@include('partials.ui-tour')
@include('partials.ui-scanner')
@if($abierto)
<script>
  UITour.registrar('wms-conteo-captura', {
    version: 1,
    auto: true,
    pasos: [
      { el: '[data-tour="filtro"]', titulo: 'Escanea, no busques',
        texto: 'Pulsa el icono de cámara para leer el código con el celular, o dispara con la pistola. La lista se filtra sola.' },
      { el: '[data-contado]', titulo: 'Escribe lo que ves',
        texto: 'Cuenta las piezas de verdad y anótalas. Se guarda solo, y con Enter brincas al siguiente renglón.' },
      { el: '[data-tour="agregar"]', titulo: 'Si aparece algo de más',
        texto: 'Agrégalo aquí con la ubicación donde lo encontraste. Entrará como sobrante al cerrar.' },
      { el: '[data-tour="cerrar"]', titulo: 'Cierra cuando termines',
        texto: 'El inventario se ajusta solo con las diferencias. Lo que dejaste sin contar no se toca.' },
    ],
  });
</script>
<script>
(function () {
  const token = document.querySelector('meta[name="csrf-token"]').content;
  const ciego = @json((bool) $conteo->blind);

  function pintarResumen(r) {
    const set = (k, v) => document.querySelectorAll('[data-r="' + k + '"]').forEach(el => el.textContent = v);
    set('contadas', r.contadas); set('total', r.total);
    set('exactitud', r.exactitud === null ? '—' : r.exactitud + '%');
    const num = v => v === null ? '<span class="pend">Se ve al cerrar</span>' : v.toLocaleString('es-MX');
    const setHtml = (k, v) => document.querySelectorAll('[data-r="' + k + '"]').forEach(el => el.innerHTML = v);
    setHtml('faltantes', num(r.faltantes)); setHtml('sobrantes', num(r.sobrantes));
    document.querySelectorAll('[data-r="barra"]').forEach(el => el.style.width = r.avance + '%');
  }

  // Guarda cada renglón al salir del campo o con Enter (y salta al siguiente).
  async function guardar(input) {
    const tr = input.closest('tr');
    const valor = input.value.trim();
    if (input.dataset.ultimo === valor) return;

    try {
      const r = await fetch(input.dataset.url, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ counted_qty: valor === '' ? null : parseInt(valor, 10) }),
      });
      const d = await r.json();
      if (!r.ok || !d.ok) throw new Error(d.error || d.message || 'No se pudo guardar');

      input.dataset.ultimo = valor;
      tr.classList.remove('is-ok', 'is-dif');
      if (valor !== '') tr.classList.add(ciego ? 'is-ok' : (d.variance === 0 ? 'is-ok' : 'is-dif'));

      const v = tr.querySelector('[data-var]');
      if (v) {
        v.textContent = d.variance === null ? '—' : (d.variance > 0 ? '+' : '') + d.variance.toLocaleString('es-MX');
        v.className = 'cnt-var ' + (d.variance > 0 ? 'mas' : (d.variance < 0 ? 'menos' : ''));
      }

      pintarResumen(d.resumen);
      const ok = tr.querySelector('[data-ok]');
      if (ok) { ok.classList.add('is-on'); setTimeout(() => ok.classList.remove('is-on'), 1400); }
      filtrar();
    } catch (e) {
      alert(e.message);
    }
  }

  document.querySelectorAll('[data-contado]').forEach(input => {
    input.dataset.ultimo = input.value.trim();
    input.addEventListener('change', () => guardar(input));
    input.addEventListener('keydown', e => {
      if (e.key !== 'Enter') return;
      e.preventDefault();
      guardar(input);
      const visibles = [...document.querySelectorAll('tr[data-linea]:not(.is-oculto) [data-contado]')];
      const sig = visibles[visibles.indexOf(input) + 1];
      if (sig) { sig.focus(); sig.select(); }
    });
  });

  // Filtro por texto (o escaneo) y "solo pendientes"
  const filtro = document.getElementById('cntFiltro');
  const soloPend = document.getElementById('cntSoloPend');

  function filtrar() {
    const q = (filtro?.value || '').trim().toLowerCase();
    document.querySelectorAll('tr[data-linea]').forEach(tr => {
      const contado = (tr.querySelector('[data-contado]')?.value || '').trim() !== '';
      const ok = (!q || tr.dataset.texto.includes(q)) && !(soloPend?.checked && contado);
      tr.classList.toggle('is-oculto', !ok);
    });
    // Un encabezado de ubicación se esconde si no le queda ningún renglón visible
    document.querySelectorAll('tr.cnt-grupo').forEach(g => {
      let n = g.nextElementSibling, hay = false;
      while (n && !n.classList.contains('cnt-grupo')) { if (!n.classList.contains('is-oculto')) hay = true; n = n.nextElementSibling; }
      g.classList.toggle('is-oculto', !hay);
    });
  }
  filtro?.addEventListener('input', filtrar);
  soloPend?.addEventListener('change', filtrar);
  filtro?.addEventListener('keydown', e => {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    const primero = document.querySelector('tr[data-linea]:not(.is-oculto) [data-contado]');
    if (primero) { primero.focus(); primero.select(); }
  });

  // Buscador de producto para "agregar encontrado"
  const prod = document.getElementById('aProd'), prodId = document.getElementById('aProdId'), lista = document.getElementById('aProdList');
  let t;
  prod?.addEventListener('input', () => {
    prodId.value = '';
    clearTimeout(t);
    t = setTimeout(async () => {
      const q = prod.value.trim();
      if (q.length < 2) { lista.classList.remove('is-open'); return; }
      const r = await fetch(@json(route('admin.wms.counts.items')) + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } });
      const d = await r.json();
      lista.innerHTML = '';
      (d.items || []).forEach(it => {
        const b = document.createElement('button');
        b.type = 'button';
        b.innerHTML = '';
        b.append(it.name + ' ');
        const s = document.createElement('small'); s.textContent = 'SKU ' + (it.sku || '—'); b.append(s);
        b.addEventListener('click', () => { prod.value = it.name; prodId.value = it.id; lista.classList.remove('is-open'); document.getElementById('aQty').focus(); });
        lista.append(b);
      });
      lista.classList.toggle('is-open', (d.items || []).length > 0);
    }, 220);
  });
  document.addEventListener('click', e => { if (!e.target.closest('.cnt-sugg')) lista?.classList.remove('is-open'); });
})();
</script>
@endif
@endpush
