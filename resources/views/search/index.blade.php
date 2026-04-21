@extends('layouts.web')

@section('title', 'Resultados de búsqueda - ' . $query)

@section('content')
<style>
  :root {
    /* Paleta Minimalista y Premium */
    --surface-bg: #f9f9fb;
    --surface-card: #ffffff;
    --surface-image: #f4f5f7;
    
    --text-primary: #09090b;
    --text-secondary: #71717a;
    --text-tertiary: #a1a1aa;
    
    --border-subtle: rgba(0, 0, 0, 0.04);
    --border-focus: rgba(0, 0, 0, 0.1);
    
    --accent: #000000;
    --accent-hover: #27272a;
    
    --tag-exact: #18181b;
    --tag-exact-text: #ffffff;
    --tag-related: #f4f4f5;
    --tag-related-text: #52525b;

    --radius-sm: 8px;
    --radius-md: 16px;
    --radius-lg: 24px;

    /* Sombras sedosas (Stripe/Vercel style) */
    --shadow-rest: 0 2px 8px -2px rgba(0, 0, 0, 0.02), 0 1px 2px rgba(0, 0, 0, 0.01);
    --shadow-hover: 0 20px 40px -8px rgba(0, 0, 0, 0.08), 0 10px 16px -4px rgba(0, 0, 0, 0.04);
    
    --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
  }

  /* Tipografía de sistema para máxima legibilidad y modernidad */
  .search-page {
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Inter", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    background-color: var(--surface-bg);
    min-height: 100vh;
    padding: 60px 0 100px;
    color: var(--text-primary);
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
  }

  .container-fluid {
    max-width: 1320px;
    margin: 0 auto;
    padding: 0 24px;
  }

  /* --- Seamless Hero --- */
  .hero-section {
    max-width: 800px;
    margin-bottom: 56px;
  }

  .hero-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    background: var(--surface-card);
    border: 1px solid var(--border-focus);
    border-radius: 99px;
    font-size: 13px;
    font-weight: 500;
    color: var(--text-secondary);
    margin-bottom: 20px;
    box-shadow: var(--shadow-rest);
  }

  .hero-title {
    font-size: clamp(36px, 5vw, 56px);
    font-weight: 700;
    line-height: 1.05;
    letter-spacing: -0.03em;
    margin: 0 0 16px 0;
    color: var(--text-primary);
  }

  .hero-title span {
    color: var(--text-secondary);
    font-weight: 400;
  }

  .hero-subtitle {
    font-size: 18px;
    line-height: 1.6;
    color: var(--text-secondary);
    margin: 0;
  }

  /* --- Section Headers --- */
  .results-section {
    margin-bottom: 64px;
  }

  .section-header {
    display: flex;
    align-items: baseline;
    gap: 16px;
    margin-bottom: 32px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border-focus);
  }

  .section-title {
    font-size: 24px;
    font-weight: 600;
    letter-spacing: -0.02em;
    margin: 0;
  }

  .section-count {
    font-size: 15px;
    color: var(--text-tertiary);
    font-weight: 500;
  }

  /* --- Product Grid & Cards --- */
  .grid-layout {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 32px 24px;
  }

  .product-card {
    display: flex;
    flex-direction: column;
    background: var(--surface-card);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-rest);
    border: 1px solid var(--border-subtle);
    text-decoration: none;
    overflow: hidden;
    transition: transform 0.4s var(--ease-out), box-shadow 0.4s var(--ease-out);
    height: 100%;
  }

  .product-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-hover);
  }

  .card-image-wrapper {
    position: relative;
    width: 100%;
    aspect-ratio: 1 / 1;
    background-color: var(--surface-image);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32px;
    overflow: hidden;
  }

  .card-image-wrapper img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    transition: transform 0.6s var(--ease-out);
    mix-blend-mode: darken; /* Ayuda a integrar imágenes con fondo blanco */
  }

  .product-card:hover .card-image-wrapper img {
    transform: scale(1.08);
  }

  .image-placeholder {
    color: var(--text-tertiary);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    font-weight: 500;
  }

  .card-content {
    padding: 24px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
  }

  .card-tag {
    align-self: flex-start;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 16px;
  }

  .card-tag.exact {
    background: var(--tag-exact);
    color: var(--tag-exact-text);
  }

  .card-tag.related {
    background: var(--tag-related);
    color: var(--tag-related-text);
  }

  .card-title {
    font-size: 17px;
    font-weight: 600;
    color: var(--text-primary);
    line-height: 1.4;
    margin: 0 0 12px 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .card-meta {
    margin-bottom: 24px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  .meta-chip {
    font-size: 13px;
    color: var(--text-secondary);
    background: var(--surface-bg);
    padding: 4px 8px;
    border-radius: 6px;
    border: 1px solid var(--border-subtle);
  }

  .card-price {
    margin-top: auto;
    font-size: 22px;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--text-primary);
  }

  /* --- Empty State --- */
  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 100px 24px;
    background: var(--surface-card);
    border-radius: var(--radius-lg);
    border: 1px dashed var(--border-focus);
  }

  .empty-icon-wrapper {
    width: 80px;
    height: 80px;
    background: var(--surface-bg);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24px;
    color: var(--text-tertiary);
  }

  .empty-title {
    font-size: 24px;
    font-weight: 600;
    margin: 0 0 12px 0;
  }

  .empty-desc {
    color: var(--text-secondary);
    font-size: 16px;
    max-width: 400px;
    line-height: 1.5;
    margin: 0 0 32px 0;
  }

  .btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 24px;
    background: var(--accent);
    color: #fff;
    font-weight: 500;
    font-size: 15px;
    border-radius: 99px;
    text-decoration: none;
    transition: background 0.2s ease;
  }

  .btn-primary:hover {
    background: var(--accent-hover);
  }

  /* --- Responsive --- */
  @media (max-width: 768px) {
    .search-page { padding: 40px 0; }
    .hero-title { font-size: 32px; }
    .grid-layout { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 24px; }
    .card-image-wrapper { padding: 24px; }
    .card-content { padding: 20px; }
  }

  @media (max-width: 480px) {
    .container-fluid { padding: 0 16px; }
    .grid-layout { grid-template-columns: 1fr; }
    .section-header { flex-direction: column; gap: 4px; }
  }
</style>

<div class="search-page">
  <div class="container-fluid">
    
    <header class="hero-section">
      <div class="hero-badge">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        Resultados de búsqueda
      </div>

      <h1 class="hero-title">
        Resultados para <span>"{{ $query }}"</span>
      </h1>

      <p class="hero-subtitle">
        @if($total > 0)
          Explora {{ $total }} producto(s) encontrados.
        @else
          No encontramos el término exacto, pero hemos seleccionado alternativas para ti.
        @endif
      </p>
    </header>

    @if($products->count())
      <section class="results-section">
        <div class="section-header">
          <h2 class="section-title">Coincidencias</h2>
          <span class="section-count">{{ $products->count() }} artículos</span>
        </div>

        <div class="grid-layout">
          @foreach($products as $product)
            <a href="{{ $product->search_url }}" class="product-card">
              <div class="card-image-wrapper">
                @if($product->search_image)
                  <img src="{{ asset('storage/' . $product->search_image) }}" alt="{{ $product->name }}" loading="lazy">
                @else
                  <div class="image-placeholder">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    Sin imagen
                  </div>
                @endif
              </div>

              <div class="card-content">
                <span class="card-tag exact">Exacto</span>
                <h3 class="card-title">{{ $product->name }}</h3>

                <div class="card-meta">
                  @if(!empty($product->brand))
                    <span class="meta-chip">{{ $product->brand }}</span>
                  @endif
                  @if(!empty($product->model))
                    <span class="meta-chip">Mod: {{ $product->model }}</span>
                  @endif
                  @if(!empty($product->sku))
                    <span class="meta-chip">SKU: {{ $product->sku }}</span>
                  @endif
                </div>

                @if(isset($product->price) && $product->price !== null)
                  <div class="card-price">${{ number_format((float) $product->price, 2) }}</div>
                @endif
              </div>
            </a>
          @endforeach
        </div>
      </section>
    @endif

    @if($related->count())
      <section class="results-section">
        <div class="section-header">
          <h2 class="section-title">Alternativas sugeridas</h2>
          <span class="section-count">{{ $related->count() }} artículos</span>
        </div>

        <div class="grid-layout">
          @foreach($related as $product)
            <a href="{{ $product->search_url }}" class="product-card">
              <div class="card-image-wrapper">
                @if($product->search_image)
                  <img src="{{ asset('storage/' . $product->search_image) }}" alt="{{ $product->name }}" loading="lazy">
                @else
                  <div class="image-placeholder">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    Sin imagen
                  </div>
                @endif
              </div>

              <div class="card-content">
                <span class="card-tag related">Sugerencia</span>
                <h3 class="card-title">{{ $product->name }}</h3>

                <div class="card-meta">
                  @if(!empty($product->brand))
                    <span class="meta-chip">{{ $product->brand }}</span>
                  @endif
                  @if(!empty($product->model))
                    <span class="meta-chip">Mod: {{ $product->model }}</span>
                  @endif
                  @if(!empty($product->sku))
                    <span class="meta-chip">SKU: {{ $product->sku }}</span>
                  @endif
                </div>

                @if(isset($product->price) && $product->price !== null)
                  <div class="card-price">${{ number_format((float) $product->price, 2) }}</div>
                @endif
              </div>
            </a>
          @endforeach
        </div>
      </section>
    @endif

    @if(!$products->count() && !$related->count())
      <div class="empty-state">
        <div class="empty-icon-wrapper">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            <line x1="9" y1="9" x2="13" y2="13"></line>
            <line x1="13" y1="9" x2="9" y2="13"></line>
          </svg>
        </div>
        <h2 class="empty-title">Sin resultados para "{{ $query }}"</h2>
        <p class="empty-desc">
          Verifica la ortografía o intenta usar términos más generales como la marca o el tipo de producto.
        </p>
        <a href="/" class="btn-primary">Volver a la tienda</a>
      </div>
    @endif
    
  </div>
</div>
@endsection