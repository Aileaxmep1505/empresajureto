{{-- Tabla + pie (paginación). Es lo que se reemplaza al buscar o filtrar por AJAX. --}}
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
        <th>Publicado</th>
        <th style="text-align:right;">Acciones</th>
      </tr>
    </thead>

    <tbody>
      @forelse($items as $it)
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

          // Una muestra con stock crítico lleva las dos clases: fila ámbar con barra roja.
          $rowClass = trim(($stockCritico ? 'is-critical-row ' : '') . ($esMuestra ? 'is-sample-row' : ''));
          $tieneMl = !$esMuestra && $it->meli_item_id;
        @endphp

        <tr class="{{ $rowClass }}">
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

                {{-- Canales de venta donde ya está sincronizado --}}
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
                  <summary class="muted-sm" style="cursor:pointer;color:#b91c1c;font-weight:800;">Error Mercado Libre</summary>
                  <div class="muted-sm" style="margin-top:6px;color:#7f1d1d; white-space:normal; max-width:740px;">{{ $it->meli_last_error }}</div>
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
            <span class="muted" style="white-space:nowrap;">{{ $it->published_at ? $it->published_at->format('d/m/Y H:i') : '—' }}</span>
          </td>

          <td style="text-align:right;">
            <div class="actions {{ $tieneMl ? 'is-wide' : '' }}">
              <span class="tt iconbtn-wrap">
                <span class="tt-bubble">Vista previa</span>
                <a class="iconbtn" href="{{ route('catalog.preview', $it) }}" target="_blank">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                </a>
              </span>

              <span class="tt iconbtn-wrap">
                <span class="tt-bubble">Actualizar stock</span>
                <button type="button" class="iconbtn js-open-stock"
                        data-name="{{ $it->name }}" data-stock="{{ (float)($it->stock ?? 0) }}"
                        data-action="{{ route('admin.catalog.stock.update', $it) }}">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M8 9h8M8 13h4"/></svg>
                </button>
              </span>

              <span class="tt iconbtn-wrap">
                <span class="tt-bubble">Editar</span>
                <a class="iconbtn" href="{{ route('admin.catalog.edit', $it) }}">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                </a>
              </span>

              <span class="tt iconbtn-wrap">
                <span class="tt-bubble">{{ $it->status == 1 ? 'Ocultar' : 'Publicar' }}</span>
                <form method="POST" action="{{ route('admin.catalog.toggle', $it) }}" class="js-sa-confirm"
                      data-sa-title="¿Cambiar estado de publicación?"
                      data-sa-text="Se actualizará el estado de este producto en el sitio web." data-sa-icon="question">
                  @csrf @method('PATCH')
                  <button class="iconbtn" type="submit">
                    @if($it->status == 1)
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12z"/><path d="M3 3l18 18"/></svg>
                    @else
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11v2"/><path d="M5 10v4"/><path d="M7 9v6"/><path d="M9 8l10-3v14l-10-3V8z"/><path d="M11 16l1 4"/></svg>
                    @endif
                  </button>
                </form>
              </span>

              @unless($esMuestra)
                <span class="tt iconbtn-wrap">
                  <span class="tt-bubble">ML: Publicar/Actualizar</span>
                  <form method="POST" action="{{ route('admin.catalog.meli.publish', $it) }}" class="js-sa-confirm"
                        data-sa-title="¿Enviar a Mercado Libre?"
                        data-sa-text="Se publicará o actualizará el anuncio en Mercado Libre." data-sa-icon="info">
                    @csrf
                    <button class="iconbtn" type="submit">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21V8"/><path d="M7 12l5-5 5 5"/><path d="M20 21H4"/></svg>
                    </button>
                  </form>
                </span>

                @if($it->meli_item_id)
                  <span class="tt iconbtn-wrap">
                    <span class="tt-bubble">ML: Ver</span>
                    <a class="iconbtn" href="{{ route('admin.catalog.meli.view', $it) }}">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3h7v7"/><path d="M10 14L21 3"/><path d="M21 14v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6"/></svg>
                    </a>
                  </span>

                  <span class="tt iconbtn-wrap">
                    <span class="tt-bubble">ML: Pausar</span>
                    <form method="POST" action="{{ route('admin.catalog.meli.pause', $it) }}" class="js-sa-confirm"
                          data-sa-title="¿Pausar en Mercado Libre?" data-sa-text="El anuncio quedará pausado." data-sa-icon="warning">
                      @csrf
                      <button class="iconbtn" type="submit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                      </button>
                    </form>
                  </span>

                  <span class="tt iconbtn-wrap">
                    <span class="tt-bubble">ML: Activar</span>
                    <form method="POST" action="{{ route('admin.catalog.meli.activate', $it) }}" class="js-sa-confirm"
                          data-sa-title="¿Activar en Mercado Libre?" data-sa-text="El anuncio volverá a estar activo." data-sa-icon="success">
                      @csrf
                      <button class="iconbtn" type="submit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="8 5 19 12 8 19 8 5"/></svg>
                      </button>
                    </form>
                  </span>
                @endif
              @endunless

              <span class="tt iconbtn-wrap">
                <span class="tt-bubble">Eliminar</span>
                <form method="POST" action="{{ route('admin.catalog.destroy', $it) }}" class="js-sa-confirm"
                      data-sa-title="¿Eliminar producto?" data-sa-text="Esta acción no se puede deshacer." data-sa-icon="error">
                  @csrf @method('DELETE')
                  <button class="iconbtn is-danger" type="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                  </button>
                </form>
              </span>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7">
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
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

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
