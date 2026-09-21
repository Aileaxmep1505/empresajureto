{{-- Listado (tabla o tarjetas) + pie (paginación).
     Es lo que se reemplaza al buscar o filtrar por AJAX. El modo lo decide $view. --}}
@php $view = $view ?? 'list'; @endphp

@if($items->isEmpty())
  {{-- ===================== Vacío (igual en ambos modos) ===================== --}}
  <div class="table-wrap card">
    <div class="empty">
      <span class="eico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/><path d="M8 11h6"/></svg></span>
      @if($samplesMode === 'only' && !$hasFilters)
        <h3>No hay muestras registradas</h3>
        <p>Marca un producto como muestra desde su edición para verlo aquí.</p>
      @elseif($filters['s'] !== '')
        <h3>Nada parecido a «{{ $filters['s'] }}»</h3>
        <p>Prueba con otra palabra, un SKU o parte del nombre. También revisa los filtros activos.</p>
        <a href="{{ $sinParams(['s']) }}" class="btn btn-sm btn-soft" data-ajax>Borrar búsqueda</a>
      @elseif($hasFilters)
        <h3>Sin resultados con estos filtros</h3>
        <p>Quita algún filtro para ver más productos.</p>
        <a href="{{ route('admin.catalog.index') }}" class="btn btn-sm btn-soft" data-ajax>Limpiar filtros</a>
      @else
        <h3>Todavía no hay productos</h3>
        <p>Crea el primero para empezar a llenar el catálogo.</p>
        <a href="{{ route('admin.catalog.create') }}" class="btn btn-sm">Nuevo producto</a>
      @endif
    </div>
  </div>

@elseif($view === 'cards')
  {{-- ===================== Tarjetas ===================== --}}
  <div class="pcards">
    @foreach($items as $it)
      @include('admin.catalog._card', ['it' => $it, 'i' => $loop->index])
    @endforeach
  </div>

@else
  {{-- ===================== Tabla ===================== --}}
  <div class="table-wrap card">
    <table>
      <thead>
        <tr>
          <th class="img-cell">Img</th>
          <th>
            <a class="sort {{ in_array($sortKey, ['name_asc','name_desc']) ? 'is-active' : '' }}" href="{{ $urlOrden('name_asc','name_desc') }}" data-ajax title="Ordenar por nombre">
              Producto
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">@if($sortKey === 'name_desc')<path d="M6 9l6 6 6-6"/>@else<path d="M18 15l-6-6-6 6"/>@endif</svg>
            </a>
          </th>
          <th>Categoría</th>
          <th>
            <a class="sort {{ in_array($sortKey, ['price_asc','price_desc']) ? 'is-active' : '' }}" href="{{ $urlOrden('price_asc','price_desc') }}" data-ajax title="Ordenar por precio">
              Precio
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">@if($sortKey === 'price_desc')<path d="M6 9l6 6 6-6"/>@else<path d="M18 15l-6-6-6 6"/>@endif</svg>
            </a>
          </th>
          <th>
            <a class="sort {{ in_array($sortKey, ['stock_asc','stock_desc']) ? 'is-active' : '' }}" href="{{ $urlOrden('stock_asc','stock_desc') }}" data-ajax title="Ordenar por existencias">
              Stock
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">@if($sortKey === 'stock_desc')<path d="M6 9l6 6 6-6"/>@else<path d="M18 15l-6-6-6 6"/>@endif</svg>
            </a>
          </th>
          <th>
            <a class="sort {{ $sortKey === 'updated' ? 'is-active' : '' }}" href="{{ $urlOrden('updated','updated') }}" data-ajax title="Ordenar por última modificación">
              Modificado
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
            </a>
          </th>
          <th style="text-align:right;">Acciones</th>
        </tr>
      </thead>

      <tbody>
        @foreach($items as $it)
          @php
            $imgPath = $it->photo_1 ?: ($it->photo_2 ?: $it->photo_3);
            $imgUrl  = $imgPath ? \Illuminate\Support\Facades\Storage::url($imgPath) : asset('images/placeholder.png');

            $stockActual = (float)($it->stock ?? 0);
            $stockMinimo = $it->stock_min !== null ? (float)$it->stock_min : null;
            $stockMaximo = $it->stock_max !== null ? (float)$it->stock_max : null;
            $stockCritico = $stockMinimo !== null && $stockActual <= $stockMinimo;
            $sinStock = $stockActual <= 0;
            $stockUnit = $unitLabel($it);

            $esMuestra = (bool)($it->is_sample ?? false);
            $muestraLabel = ($esMuestra && method_exists($it, 'sampleStatusLabel')) ? $it->sampleStatusLabel() : null;
            $muestraHolder = $esMuestra ? trim((string)($it->sample_holder ?? '')) : '';

            $rowClass = trim(($stockCritico ? 'is-critical-row ' : '') . ($esMuestra ? 'is-sample-row' : ''));
            $tieneMl = !$esMuestra && $it->meli_item_id;
          @endphp

          <tr class="js-row reveal {{ $rowClass }}" style="--i:{{ $loop->index }}">
            <td class="img-cell">
              <div class="thumbbox">
                <img src="{{ $imgUrl }}" alt="Imagen de {{ $it->name }}" loading="lazy"
                     onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}';">
              </div>
            </td>

            <td>
              <div class="name">
                <strong>{!! $resaltar($it->name) !!}</strong>
                <div class="meta">
                  <span><span class="k">SKU:</span> <span class="v">{!! $it->sku ? $resaltar($it->sku) : '—' !!}</span></span>
                  @if($it->brand_name)
                    <span><span class="k">Marca:</span> <span class="v">{!! $resaltar($it->brand_name) !!}</span></span>
                  @endif
                  @if($it->model_name)
                    <span><span class="k">Modelo:</span> <span class="v">{!! $resaltar($it->model_name) !!}</span></span>
                  @endif
                  @if($it->meli_item_id)
                    <span><span class="k">ML:</span> <span class="v">{!! $resaltar($it->meli_item_id) !!}</span></span>
                  @endif
                </div>

                <div class="badges">
                  @if($esMuestra)
                    <span class="badge b-sample"><span class="dot"></span>Muestra{{ $muestraLabel ? ' · '.$muestraLabel : '' }}</span>
                    @if($muestraHolder !== '')
                      <span class="badge b-holder"><span class="dot"></span>Con: {{ $muestraHolder }}</span>
                    @endif
                  @elseif($it->status === 1)
                    <span class="badge b-live"><span class="dot"></span>Publicado</span>
                  @elseif($it->status === 2)
                    <span class="badge b-hidden"><span class="dot"></span>Oculto</span>
                  @else
                    <span class="badge b-draft"><span class="dot"></span>Borrador</span>
                  @endif

                  @if($it->is_featured)
                    <span class="badge b-star"><span class="dot"></span>Destacado</span>
                  @endif

                  @if($stockCritico)
                    <span class="badge b-crit"><span class="dot"></span>Stock crítico</span>
                  @endif

                  @if($tieneMl)
                    <span class="badge b-chan {{ !empty($it->meli_last_error) ? 'is-err' : '' }}" title="Mercado Libre{{ $it->meli_status ? ' · '.$it->meli_status : '' }}">ML</span>
                  @endif
                  @if($it->amazon_sku)
                    <span class="badge b-chan {{ !empty($it->amazon_last_error) ? 'is-err' : '' }}" title="Amazon{{ $it->amazon_status ? ' · '.$it->amazon_status : '' }}">Amazon</span>
                  @endif
                  @if($it->shopify_product_id)
                    <span class="badge b-chan {{ !empty($it->shopify_last_error) ? 'is-err' : '' }}" title="Shopify">Shopify</span>
                  @endif
                </div>

                @if(!empty($it->meli_last_error))
                  <details style="margin-top:6px;">
                    <summary class="muted-sm" style="cursor:pointer;color:var(--ui-danger-ink);font-weight:600;">Error Mercado Libre</summary>
                    <div class="muted-sm" style="margin-top:6px;color:var(--ui-danger-ink); white-space:normal; max-width:740px;">{{ $it->meli_last_error }}</div>
                  </details>
                @endif
              </div>
            </td>

            <td>
              <div class="cat">
                {{ $it->categoryProduct->name ?? ($it->category_label ?? '—') }}
                @if($it->primaryLocation)
                  <small title="Ubicación principal">{{ $it->primaryLocation->code ?? $it->primaryLocation->name }}</small>
                @endif
              </div>
            </td>

            <td>
              @if(!is_null($it->sale_price))
                <div class="sale">${{ number_format($it->sale_price,2) }}</div>
                <div class="muted-sm" style="text-decoration:line-through;">${{ number_format($it->price,2) }}</div>
              @else
                <div class="price">${{ number_format($it->price,2) }}</div>
              @endif
            </td>

            <td>
              <span class="stock-pill {{ $stockCritico ? 'is-critical' : '' }} {{ $sinStock ? 'is-empty' : '' }}">
                <span class="dot"></span>
                {{ number_format($stockActual, 0) }} {{ $stockUnit }}
              </span>
              <div class="stock-meta">
                Mín: {{ $stockMinimo !== null ? number_format($stockMinimo, 0) : '—' }}
                · Máx: {{ $stockMaximo !== null ? number_format($stockMaximo, 0) : '—' }}
              </div>
            </td>

            <td>
              <div class="rowdate" title="Creado {{ optional($it->created_at)->format('d/m/Y H:i') }}">
                <span class="v">{{ $it->updated_at ? $it->updated_at->format('d/m/Y') : '—' }}</span>
                <span>{{ $it->updated_at ? $it->updated_at->locale('es')->diffForHumans() : '' }}</span>
              </div>
            </td>

            <td class="actions-cell">
              @include('admin.catalog._acciones')
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif

<div class="foot">
  <div class="foot-left">
    <div class="muted">
      Mostrando {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} de {{ number_format($items->total()) }} registros
    </div>
    <div class="legend" aria-label="Leyenda de colores">
      <span><i class="sw sw-red"></i>Stock crítico</span>
      <span><i class="sw sw-amb"></i>Muestra</span>
    </div>
    <label class="perpage">
      Por página
      <select data-perpage>
        @foreach(\App\Http\Controllers\Admin\CatalogItemController::PER_PAGE as $n)
          <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
        @endforeach
      </select>
    </label>
  </div>
  <div>
    @php $links = $items->toArray()['links'] ?? []; @endphp

    @if($items->hasPages())
      <nav class="pagi" aria-label="Paginación">
        @foreach($links as $link)
          @php
            $label = strip_tags($link['label']);
            $isPrev = $loop->first;
            $isNext = $loop->last;
            $isDots = ($label === '...' || $label === '…');
            $url = $link['url'];
            $active = (bool)($link['active'] ?? false);
            $disabled = is_null($url) && !$active && !$isDots;
          @endphp

          @if($isPrev)
            <a class="page {{ $disabled ? 'is-disabled' : '' }}" href="{{ $url ?: 'javascript:void(0)' }}" data-ajax aria-label="Anterior">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            </a>
          @elseif($isNext)
            <a class="page {{ $disabled ? 'is-disabled' : '' }}" href="{{ $url ?: 'javascript:void(0)' }}" data-ajax aria-label="Siguiente">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
            </a>
          @elseif($isDots)
            <span class="page is-ellipsis" aria-hidden="true">…</span>
          @else
            <a class="page {{ $active ? 'is-active' : '' }} {{ $url ? '' : 'is-disabled' }}" href="{{ $url ?: 'javascript:void(0)' }}" data-ajax aria-label="Página {{ $label }}">{{ $label }}</a>
          @endif
        @endforeach
      </nav>
    @endif
  </div>
</div>
