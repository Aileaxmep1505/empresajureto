{{-- Una tarjeta de producto (vista "Tarjetas"). Requiere $it, $resaltar, $unitLabel.
     $i (índice) se usa para el retraso escalonado de la animación de entrada. --}}
@php
  $i = $i ?? 0;
  $imgPath = $it->photo_1 ?: ($it->photo_2 ?: $it->photo_3);
  $imgUrl  = $imgPath ? \Illuminate\Support\Facades\Storage::url($imgPath) : asset('images/placeholder.png');

  $stockActual = (float) ($it->stock ?? 0);
  $stockMinimo = $it->stock_min !== null ? (float) $it->stock_min : null;
  $stockCritico = $stockMinimo !== null && $stockActual <= $stockMinimo;
  $sinStock = $stockActual <= 0;
  $stockUnit = $unitLabel($it);

  $esMuestra = (bool) ($it->is_sample ?? false);
  $muestraLabel = ($esMuestra && method_exists($it, 'sampleStatusLabel')) ? $it->sampleStatusLabel() : null;

  $cardClass = trim(($stockCritico ? 'is-critical ' : '') . ($esMuestra ? 'is-sample' : ''));
@endphp

<article class="pcard reveal {{ $cardClass }}" style="--i:{{ $i }}">
  <div class="pcard-media">
    <img src="{{ $imgUrl }}" alt="Imagen de {{ $it->name }}" loading="lazy"
         onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}';">

    <div class="pcard-badges">
      @if($esMuestra)
        <span class="badge b-sample"><span class="dot"></span>Muestra{{ $muestraLabel ? ' · '.$muestraLabel : '' }}</span>
      @elseif($it->status === 1)
        <span class="badge b-live"><span class="dot"></span>Publicado</span>
      @elseif($it->status === 2)
        <span class="badge b-hidden"><span class="dot"></span>Oculto</span>
      @else
        <span class="badge b-draft"><span class="dot"></span>Borrador</span>
      @endif
      @if($it->is_featured)<span class="badge b-star"><span class="dot"></span>Destacado</span>@endif
      @if($stockCritico)<span class="badge b-crit"><span class="dot"></span>Crítico</span>@endif
    </div>

    <div class="pcard-menu">
      @include('admin.catalog._acciones')
    </div>
  </div>

  <div class="pcard-body">
    <a class="pcard-title" href="{{ route('admin.catalog.edit', $it) }}">{!! $resaltar($it->name) !!}</a>

    <div class="pcard-meta">
      <span><span class="k">SKU:</span> <span class="v">{!! $it->sku ? $resaltar($it->sku) : '—' !!}</span></span>
      <span class="v">{{ $it->categoryProduct->name ?? ($it->category_label ?? 'Sin categoría') }}</span>
    </div>

    <div class="pcard-meta">
      <span class="stock-pill {{ $stockCritico ? 'is-critical' : '' }} {{ $sinStock ? 'is-empty' : '' }}">
        <span class="dot"></span>{{ number_format($stockActual, 0) }} {{ $stockUnit }}
      </span>
      @if($it->meli_item_id)<span class="badge b-chan">ML</span>@endif
      @if($it->amazon_sku)<span class="badge b-chan">Amazon</span>@endif
      @if($it->shopify_product_id)<span class="badge b-chan">Shopify</span>@endif
    </div>

    <div class="pcard-foot">
      <div class="pcard-price">
        @if(!is_null($it->sale_price))
          <span class="sale">${{ number_format($it->sale_price, 2) }}</span>
          <span class="was">${{ number_format($it->price, 2) }}</span>
        @else
          ${{ number_format($it->price, 2) }}
        @endif
      </div>
      <span class="pcard-date" title="Modificado {{ optional($it->updated_at)->format('d/m/Y H:i') }} · Creado {{ optional($it->created_at)->format('d/m/Y') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
        {{ $it->updated_at ? $it->updated_at->locale('es')->diffForHumans() : '—' }}
      </span>
    </div>
  </div>
</article>
