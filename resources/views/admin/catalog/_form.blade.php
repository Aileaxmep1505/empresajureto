@php
  /** @var \App\Models\CatalogItem|null $item */
  $isEdit = isset($item);

  $has1 = !empty($item->photo_1 ?? null);
  $has2 = !empty($item->photo_2 ?? null);
  $has3 = !empty($item->photo_3 ?? null);

  $categories = $categories ?? config('catalog.product_categories', []);

  $hasAmazonSku = !empty($item->amazon_sku ?? null) || !empty($item->sku ?? null);

  $amzStatus = $item->amz_status ?? ($item->amazon_status ?? null);
  $hasAmazonListing = !empty($item->amazon_asin ?? null)
      || in_array((string)$amzStatus, ['active','paused','inactive','error'], true);
@endphp

@csrf
@if($isEdit)
  @method('PUT')
@endif

{{-- =========================================================
   ✅ IA (minimalista) — botones a la derecha, sin ruido
   ========================================================= --}}
<div class="ai-bar" id="ai-helper">
  <div class="ai-left">
    <div class="ai-badge">
      <span class="ai-dot"></span>
      IA
      <span class="ai-beta">Beta</span>
    </div>

    <div id="ai-dropzone" class="ai-dropzone">
      <div class="ai-dropzone-icon">📄</div>
      <div class="ai-dropzone-body">
        <div class="ai-dropzone-title">Arrastra PDF/imagenes o toca para elegir</div>
        <div class="ai-dropzone-hint">JPG · PNG · WEBP · PDF</div>
      </div>

      <input id="ai_files"
             name="ai_files[]"
             type="file"
             multiple
             accept="image/*,.pdf"
             class="ai-dropzone-input">
    </div>

    <div id="ai-files-list" class="ai-files-list"></div>
  </div>

  <div class="ai-right">
    <button type="button" id="btn-ai-analyze" class="btn btn-primary ai-cta">
      <span class="ai-cta-spinner" aria-hidden="true"></span>
      <span class="ai-cta-text">Analizar</span>
    </button>

    <button type="button" id="btn-ai-fill-empty" class="btn btn-ghost">
      <span class="i material-symbols-outlined" aria-hidden="true">auto_fix_high</span>
      Rellenar vacíos
    </button>

    <div id="ai-helper-status" class="ai-status">Sube un PDF/imagenes y analiza.</div>
  </div>
</div>

{{-- =========================================================
   ✅ Productos IA (compacto)
   ========================================================= --}}
<div id="ai-items-panel" class="ai-items-panel" style="display:none;">
  <div class="ai-items-top">
    <div class="ai-items-title">
      Productos detectados
      <span class="ai-items-badge" id="ai-items-count"></span>
    </div>

    <button type="button" id="ai-clear-list" class="btn btn-ghost btn-xs">
      Limpiar
    </button>
  </div>

  <div class="ai-items-table-wrapper">
    <table class="ai-items-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Nombre</th>
          <th>Precio</th>
          <th>Marca</th>
          <th>GTIN</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="ai-items-tbody"></tbody>
    </table>
  </div>
</div>

{{-- =========================================================
   ✅ FORMULARIO
   ========================================================= --}}
<div class="catalog-grid">
  {{-- IZQUIERDA --}}
  <div class="catalog-main">
    <div class="card-block">
      <label class="lbl">Nombre *</label>
      <input name="name" class="inp" required
             placeholder="Ej: Lapicero bolígrafo azul Bic 0.7mm"
             value="{{ old('name', $item->name ?? '') }}">
    </div>

    <div class="card-block">
      <label class="lbl">Slug</label>
      <input name="slug" class="inp"
             placeholder="lapicero-bic-azul-07mm"
             value="{{ old('slug', $item->slug ?? '') }}">
    </div>

    <div class="card-block">
      <label class="lbl">Descripción</label>
      <textarea name="description" class="inp" rows="6"
        placeholder="Detalle del producto, medidas, materiales, etc.">{{ old('description', $item->description ?? '') }}</textarea>
    </div>

    <div class="card-block">
      <label class="lbl">Extracto</label>
      <textarea name="excerpt" class="inp" rows="3"
        placeholder="Resumen corto para listados.">{{ old('excerpt', $item->excerpt ?? '') }}</textarea>
    </div>

    {{-- Fotos (queda igual, pero UI limpia) --}}
    <div class="card-block">
      <div class="block-head">
        <div class="block-title">Fotos (3)</div>
        <div class="block-sub">Principal · Empaque · Detalle</div>
      </div>

      <div class="photos-grid">
        {{-- FOTO 1 --}}
        <div class="photo-card" data-photo-card="photo_1_file">
          <div class="photo-head">
            <div class="photo-title">Foto 1 *</div>
            <span class="photo-badge {{ ($isEdit && $has1) ? 'ok' : '' }}" data-photo-badge="photo_1_file">
              {{ ($isEdit && $has1) ? 'Cargada' : 'Pendiente' }}
            </span>
          </div>

          <label class="photo-drop" for="photo_1_file">
            <div class="photo-icon">📷</div>
            <div class="photo-text">
              <div class="photo-strong" data-photo-strong="photo_1_file">Seleccionar</div>
              <div class="photo-sub" data-photo-sub="photo_1_file">JPG/PNG/WEBP</div>
            </div>

            <input id="photo_1_file" name="photo_1_file" type="file" class="photo-input" accept="image/*"
                   @if(!$isEdit) required @endif capture="environment">
          </label>

          <div class="photo-preview" id="photo_1_preview">
            @if($isEdit && $has1)
              <img src="{{ \Illuminate\Support\Facades\Storage::url($item->photo_1) }}" alt="Foto 1">
            @endif
          </div>

          <div class="photo-actions">
            <button type="button" class="btn btn-ghost btn-xs" data-photo-clear="photo_1_file">Quitar</button>
          </div>
        </div>

        {{-- FOTO 2 --}}
        <div class="photo-card" data-photo-card="photo_2_file">
          <div class="photo-head">
            <div class="photo-title">Foto 2 *</div>
            <span class="photo-badge {{ ($isEdit && $has2) ? 'ok' : '' }}" data-photo-badge="photo_2_file">
              {{ ($isEdit && $has2) ? 'Cargada' : 'Pendiente' }}
            </span>
          </div>

          <label class="photo-drop" for="photo_2_file">
            <div class="photo-icon">📷</div>
            <div class="photo-text">
              <div class="photo-strong" data-photo-strong="photo_2_file">Seleccionar</div>
              <div class="photo-sub" data-photo-sub="photo_2_file">Frente/empaque</div>
            </div>

            <input id="photo_2_file" name="photo_2_file" type="file" class="photo-input" accept="image/*"
                   @if(!$isEdit) required @endif capture="environment">
          </label>

          <div class="photo-preview" id="photo_2_preview">
            @if($isEdit && $has2)
              <img src="{{ \Illuminate\Support\Facades\Storage::url($item->photo_2) }}" alt="Foto 2">
            @endif
          </div>

          <div class="photo-actions">
            <button type="button" class="btn btn-ghost btn-xs" data-photo-clear="photo_2_file">Quitar</button>
          </div>
        </div>

        {{-- FOTO 3 --}}
        <div class="photo-card" data-photo-card="photo_3_file">
          <div class="photo-head">
            <div class="photo-title">Foto 3 *</div>
            <span class="photo-badge {{ ($isEdit && $has3) ? 'ok' : '' }}" data-photo-badge="photo_3_file">
              {{ ($isEdit && $has3) ? 'Cargada' : 'Pendiente' }}
            </span>
          </div>

          <label class="photo-drop" for="photo_3_file">
            <div class="photo-icon">📷</div>
            <div class="photo-text">
              <div class="photo-strong" data-photo-strong="photo_3_file">Seleccionar</div>
              <div class="photo-sub" data-photo-sub="photo_3_file">Detalle/etiqueta</div>
            </div>

            <input id="photo_3_file" name="photo_3_file" type="file" class="photo-input" accept="image/*"
                   @if(!$isEdit) required @endif capture="environment">
          </label>

          <div class="photo-preview" id="photo_3_preview">
            @if($isEdit && $has3)
              <img src="{{ \Illuminate\Support\Facades\Storage::url($item->photo_3) }}" alt="Foto 3">
            @endif
          </div>

          <div class="photo-actions">
            <button type="button" class="btn btn-ghost btn-xs" data-photo-clear="photo_3_file">Quitar</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- DERECHA --}}
  <div class="catalog-side">

    <div class="side-card">
      <div class="side-title">General</div>

      <div class="card-section">
        <label class="lbl">SKU</label>
        <input name="sku" class="inp" placeholder="Código interno o proveedor"
               value="{{ old('sku', $item->sku ?? '') }}">
      </div>

      <div class="card-inline">
        <div class="card-inline-item">
          <label class="lbl">Precio *</label>
          <input name="price" type="number" step="0.01" min="0" class="inp" required
                 value="{{ old('price', $item->price ?? 0) }}">
        </div>

        <div class="card-inline-item">
          <label class="lbl">Stock</label>
          <input name="stock" type="number" step="1" min="0" class="inp"
                 value="{{ old('stock', $item->stock ?? 0) }}">
        </div>
      </div>

      <div class="card-section">
        <label class="lbl">Oferta</label>
        <input name="sale_price" type="number" step="0.01" min="0" class="inp"
               value="{{ old('sale_price', $item->sale_price ?? '') }}">
      </div>

      <div class="card-section">
        <label class="lbl">Categoría</label>
        @php $currentCategory = old('category', $item->category ?? ''); @endphp
        <select name="category" class="inp">
          <option value="">Sin categoría</option>
          @foreach($categories as $key => $label)
            <option value="{{ $key }}" @selected($currentCategory === $key)>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <div class="card-section">
        <label class="lbl">Estado *</label>
        <select name="status" class="inp" required>
          @php $st = (string)old('status', isset($item)? (string)$item->status : '0'); @endphp
          <option value="0" @selected($st==='0')>Borrador</option>
          <option value="1" @selected($st==='1')>Publicado</option>
          <option value="2" @selected($st==='2')>Oculto</option>
        </select>
      </div>

      <div class="card-section">
        <label class="lbl">Publicado en</label>
        <input name="published_at" type="datetime-local" class="inp"
               value="{{ old('published_at', isset($item->published_at)? $item->published_at->format('Y-m-d\TH:i') : '') }}">
      </div>

      <div class="card-section">
        <label class="lbl">Destacado</label>
        <label class="toggle-row">
          <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $item->is_featured ?? false))>
          <span>Mostrar en Home</span>
        </label>
      </div>
    </div>

    <div class="side-card">
      <div class="side-title">Mercado Libre</div>

      <div class="card-section">
        <label class="lbl">Marca</label>
        <input name="brand_name" class="inp" placeholder="Bic, Maped..."
               value="{{ old('brand_name', $item->brand_name ?? '') }}">
      </div>

      <div class="card-section">
        <label class="lbl">Modelo</label>
        <input name="model_name" class="inp" placeholder="Cristal 1.0mm..."
               value="{{ old('model_name', $item->model_name ?? '') }}">
      </div>

      <div class="card-section">
        <label class="lbl">GTIN</label>
        <input name="meli_gtin" class="inp" placeholder="750..."
               value="{{ old('meli_gtin', $item->meli_gtin ?? '') }}">
      </div>
    </div>

    <div class="side-card">
      <div class="side-title">Amazon</div>

      <div class="card-section">
        <label class="lbl">Seller SKU (amazon_sku)</label>
        <input name="amazon_sku" class="inp" placeholder="MY-SKU-AMZ-001"
               value="{{ old('amazon_sku', $item->amazon_sku ?? '') }}">
      </div>

      <div class="card-section">
        <label class="lbl">ASIN</label>
        <input name="amazon_asin" class="inp" placeholder="B0..."
               value="{{ old('amazon_asin', $item->amazon_asin ?? '') }}">
      </div>

      <div class="card-section">
        <label class="lbl">Product Type</label>
        <input name="amazon_product_type" class="inp" placeholder="OFFICE_PRODUCTS"
               value="{{ old('amazon_product_type', $item->amazon_product_type ?? '') }}">
      </div>
    </div>

    {{-- ✅ Acciones (UN solo bloque, sin duplicados, todo a la derecha) --}}
    @if($isEdit)
      <div class="side-card">
        <div class="side-title">Publicación</div>

        <div class="actions-grid">
          {{-- ML --}}
          <div class="actions-col">
            <div class="actions-head">
              <div class="actions-brand">Mercado Libre</div>
            </div>

            <div class="actions-row">
              <form method="POST" action="{{ route('admin.catalog.meli.publish', $item) }}">
                @csrf
                <button type="submit" class="btn btn-ml w100">
                  <span class="i material-symbols-outlined">cloud_upload</span>
                  Publicar
                </button>
              </form>

              <div class="actions-mini">
                <form method="POST" action="{{ route('admin.catalog.meli.pause', $item) }}">
                  @csrf
                  <button type="submit" class="btn btn-ml-soft">
                    <span class="i material-symbols-outlined">pause_circle</span>
                  </button>
                </form>

                <form method="POST" action="{{ route('admin.catalog.meli.activate', $item) }}">
                  @csrf
                  <button type="submit" class="btn btn-ml-soft">
                    <span class="i material-symbols-outlined">play_circle</span>
                  </button>
                </form>

                <a class="btn btn-ml-soft" href="{{ route('admin.catalog.meli.view', $item) }}" target="_blank" rel="noopener">
                  <span class="i material-symbols-outlined">open_in_new</span>
                </a>
              </div>
            </div>
          </div>

          {{-- AMZ --}}
          <div class="actions-col">
            <div class="actions-head">
              <div class="actions-brand">Amazon</div>
              @if(!$hasAmazonSku)
                <div class="actions-note">Falta Seller SKU</div>
              @endif
            </div>

            <div class="actions-row">
              <form method="POST" action="{{ route('admin.catalog.amazon.publish', $item) }}">
                @csrf
                <button type="submit" class="btn btn-amz w100" @disabled(!$hasAmazonSku)>
                  <span class="i material-symbols-outlined">cloud_upload</span>
                  Publicar
                </button>
              </form>

              @if($hasAmazonListing)
                <div class="actions-mini">
                  <form method="POST" action="{{ route('admin.catalog.amazon.pause', $item) }}">
                    @csrf
                    <button type="submit" class="btn btn-amz-soft" @disabled(!$hasAmazonSku)>
                      <span class="i material-symbols-outlined">pause_circle</span>
                    </button>
                  </form>

                  <form method="POST" action="{{ route('admin.catalog.amazon.activate', $item) }}">
                    @csrf
                    <button type="submit" class="btn btn-amz-soft" @disabled(!$hasAmazonSku)>
                      <span class="i material-symbols-outlined">play_circle</span>
                    </button>
                  </form>

                  <a class="btn btn-amz-soft"
                     href="{{ route('admin.catalog.amazon.view', $item) }}"
                     target="_blank" rel="noopener"
                     @if(!$hasAmazonSku) aria-disabled="true" onclick="return false;" @endif>
                    <span class="i material-symbols-outlined">open_in_new</span>
                  </a>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    @endif

  </div>
</div>

<hr class="divi">

<div class="form-actions">
  <button class="btn btn-primary" type="submit">
    {{ $isEdit ? 'Guardar cambios' : 'Crear producto' }}
  </button>
  <a class="btn btn-ghost" href="{{ route('admin.catalog.index') }}">Cancelar</a>
</div>

@push('styles')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400..700&display=swap"/>
<style>
  :root{
    --ink:#0e1726;
    --muted:#64748b;
    --line:#e2e8f0;
    --surface:#ffffff;
    --brand:#2563eb;
    --radius-lg:18px;
    --radius-md:12px;

    --ml:#10b981;
    --ml-soft: rgba(16,185,129,.12);

    --amz:#f59e0b;
    --amz-soft: rgba(245,158,11,.14);
  }

  .lbl{ display:block; font-weight:800; color:var(--ink); margin:10px 0 4px; font-size:.9rem; }
  .inp{
    width:100%; background:#f8fafc; border:1px solid var(--line); border-radius:var(--radius-md);
    padding:10px 12px; min-height:42px; font-size:.92rem; color:#0f172a;
    transition:border-color .15s ease, box-shadow .15s ease, background .15s ease, transform .08s ease;
  }
  .inp:focus{ outline:none; border-color:#93c5fd; box-shadow:0 0 0 1px #bfdbfe; background:#fff; transform:translateY(-1px); }

  .btn{
    border:0; border-radius:999px; padding:10px 16px; font-weight:700; cursor:pointer; font-size:.9rem;
    display:inline-flex; align-items:center; gap:6px; white-space:nowrap; text-decoration:none;
    transition:transform .12s ease, box-shadow .12s ease, background .15s ease, border-color .15s ease, opacity .15s ease;
  }
  .btn[disabled], .btn[aria-disabled="true"]{ opacity:.5; cursor:not-allowed; pointer-events:none; }
  .btn-primary{ background:var(--brand); color:#eff6ff; box-shadow:0 12px 30px rgba(37,99,235,.25); }
  .btn-primary:hover{ transform:translateY(-1px); box-shadow:0 16px 32px rgba(37,99,235,.28); }
  .btn-ghost{ background:#fff; border:1px solid var(--line); color:#0f172a; }
  .btn-ghost:hover{ transform:translateY(-1px); box-shadow:0 10px 22px rgba(15,23,42,.06); }
  .btn-xs{ padding:6px 10px; font-size:.76rem; }

  .divi{ border:none; border-top:1px dashed #e5e7eb; margin:18px 0; }

  .catalog-grid{ display:grid; gap:18px; grid-template-columns:repeat(12,1fr); }
  .catalog-main{ grid-column:span 8; display:flex; flex-direction:column; gap:12px; }
  .catalog-side{ grid-column:span 4; display:flex; flex-direction:column; gap:12px; }

  .card-block{
    background:#fff; border:1px solid var(--line); border-radius:var(--radius-lg);
    padding:12px 14px; box-shadow:0 10px 25px rgba(15,23,42,.03);
  }

  .side-card{
    background:#fff; border-radius:var(--radius-lg); border:1px solid var(--line);
    padding:12px 14px; box-shadow:0 10px 25px rgba(15,23,42,.03);
  }
  .side-title{ font-weight:900; color:#0f172a; font-size:.9rem; margin:0 0 6px; }

  .block-head{ display:flex; align-items:baseline; justify-content:space-between; gap:10px; margin-bottom:6px; }
  .block-title{ font-weight:900; color:#0f172a; }
  .block-sub{ color:var(--muted); font-size:.78rem; font-weight:700; }

  .card-inline{ display:flex; gap:10px; flex-wrap:wrap; }
  .card-inline-item{ flex:1 1 140px; }

  .toggle-row{ display:flex; gap:8px; align-items:center; color:#475569; font-weight:700; font-size:.86rem; }

  .form-actions{ margin-top:18px; display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap; }

  /* IA barra */
  .ai-bar{
    display:flex; gap:12px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap;
    padding:12px 14px; border:1px solid #dbeafe; border-radius:20px;
    background:radial-gradient(circle at top left, #dbeafe 0, #eff6ff 30%, #ffffff 80%);
    margin-bottom:14px;
  }
  .ai-left{ flex:1 1 520px; display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
  .ai-right{ flex:0 0 auto; display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end; }

  .ai-badge{
    display:inline-flex; align-items:center; gap:8px;
    font-weight:900; color:#0f172a; font-size:.85rem;
    padding:6px 10px; border-radius:999px; border:1px solid rgba(37,99,235,.18); background:#fff;
  }
  .ai-dot{ width:8px; height:8px; border-radius:999px; background:#2563eb; box-shadow:0 0 0 4px rgba(37,99,235,.12); }
  .ai-beta{ font-size:.7rem; font-weight:900; color:#4d7c0f; background:rgba(236,252,203,.9); padding:2px 8px; border-radius:999px; }

  .ai-dropzone{
    position:relative; display:flex; align-items:center; gap:10px;
    border:1.5px dashed rgba(148,163,184,.9); border-radius:16px;
    padding:10px 12px; background:#fff; cursor:pointer;
    min-width:280px;
  }
  .ai-dropzone:hover{ border-color:#60a5fa; box-shadow:0 10px 25px rgba(37,99,235,.12); }
  .ai-dropzone-icon{
    width:34px; height:34px; border-radius:999px; background:#1d4ed8; color:#e0f2fe;
    display:flex; align-items:center; justify-content:center; box-shadow:0 12px 24px rgba(30,64,175,.55);
    flex:0 0 auto;
  }
  .ai-dropzone-title{ font-weight:800; color:#0f172a; font-size:.82rem; }
  .ai-dropzone-hint{ color:var(--muted); font-size:.74rem; font-weight:700; }
  .ai-dropzone-input{ position:absolute; inset:0; opacity:0; cursor:pointer; }

  .ai-files-list{ display:flex; gap:6px; flex-wrap:wrap; }
  .ai-file-chip{
    display:inline-flex; align-items:center; padding:3px 8px; border-radius:999px;
    font-size:.74rem; font-weight:700; border:1px solid #dbeafe; background:#eff6ff; color:#1e293b;
    max-width:210px;
  }
  .ai-file-chip span{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

  .ai-status{ color:#64748b; font-size:.78rem; font-weight:700; min-height:18px; }

  .ai-cta-spinner{
    width:16px;height:16px;border-radius:999px;border:2px solid rgba(191,219,254,.7);
    border-top-color:#eff6ff; opacity:0; transform:scale(.6);
    transition:opacity .15s ease, transform .15s ease;
  }
  #ai-helper.ai-busy .ai-cta-spinner{ opacity:1; transform:scale(1); animation:aiSpin .8s linear infinite; }

  /* Tabla IA compacta */
  .ai-items-panel{
    margin-bottom:14px; background:#fff; border:1px solid var(--line); border-radius:18px;
    padding:12px 14px; box-shadow:0 10px 25px rgba(15,23,42,.03);
  }
  .ai-items-top{ display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:8px; }
  .ai-items-title{ font-weight:900; color:#0f172a; display:flex; gap:8px; align-items:center; }
  .ai-items-badge{
    font-size:.74rem; font-weight:900; padding:2px 10px; border-radius:999px;
    background:#dcfce7; color:#15803d;
  }
  .ai-items-table-wrapper{ width:100%; overflow:auto; }
  .ai-items-table{ width:100%; border-collapse:collapse; font-size:.8rem; }
  .ai-items-table thead{ background:#eff6ff; }
  .ai-items-table th, .ai-items-table td{ padding:6px 8px; border-bottom:1px solid #e5e7eb; text-align:left; }
  .ai-items-table tr:hover{ background:#f8fafc; }

  /* Fotos */
  .photos-grid{ display:grid; grid-template-columns: repeat(3, 1fr); gap:10px; }
  .photo-card{ background:#fff; border:1px solid var(--line); border-radius:16px; padding:10px; }
  .photo-card.is-filled{ border-color: rgba(34,197,94,.35); box-shadow:0 12px 26px rgba(22,163,74,.08); }
  .photo-head{ display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px; }
  .photo-title{ font-weight:900; color:#0f172a; font-size:.84rem; }
  .photo-badge{ font-size:.72rem; padding:3px 8px; border-radius:999px; border:1px solid var(--line); background:#f8fafc; color:#334155; font-weight:900; }
  .photo-badge.ok{ border-color:#bbf7d0; background:#dcfce7; color:#15803d; }
  .photo-drop{
    border:1.5px dashed rgba(148,163,184,.95); border-radius:14px; padding:10px;
    display:flex; gap:10px; align-items:center; background:#fff; cursor:pointer;
  }
  .photo-drop:hover{ border-color:#60a5fa; box-shadow:0 10px 25px rgba(37,99,235,.10); transform:translateY(-1px); }
  .photo-icon{ width:38px; height:38px; border-radius:999px; background:#1d4ed8; color:#e0f2fe; display:flex; align-items:center; justify-content:center; box-shadow:0 12px 24px rgba(30,64,175,.55); }
  .photo-strong{ font-weight:900; color:#0f172a; font-size:.84rem; }
  .photo-sub{ color:var(--muted); font-size:.74rem; font-weight:700; }
  .photo-input{ display:none; }
  .photo-preview{ margin-top:8px; border-radius:14px; overflow:hidden; border:1px solid var(--line); background:#f1f5f9; aspect-ratio: 4/3; }
  .photo-preview img{ width:100%; height:100%; object-fit:cover; display:block; }
  .photo-actions{ display:flex; justify-content:flex-end; margin-top:8px; }

  /* Acciones */
  .actions-grid{ display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:8px; }
  .actions-col{ background:#fff; border:1px solid var(--line); border-radius:16px; padding:10px; }
  .actions-head{ display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:8px; }
  .actions-brand{ font-weight:900; color:#0f172a; font-size:.85rem; }
  .actions-note{ font-size:.74rem; font-weight:900; color:#92400e; background:rgba(245,158,11,.14); border:1px solid rgba(245,158,11,.25); padding:2px 8px; border-radius:999px; }
  .actions-row{ display:flex; flex-direction:column; gap:8px; }
  .actions-mini{ display:flex; gap:8px; justify-content:flex-end; }

  .w100{ width:100%; justify-content:center; }
  .i.material-symbols-outlined{ font-size:18px; line-height:1; }

  .btn-ml{ background:linear-gradient(135deg, rgba(16,185,129,.18), rgba(16,185,129,.08)); color:#065f46; border:1px solid rgba(16,185,129,.35); }
  .btn-ml-soft{ background:rgba(16,185,129,.10); color:#065f46; border:1px solid rgba(16,185,129,.25); padding:8px 10px; }
  .btn-amz{ background:linear-gradient(135deg, rgba(245,158,11,.22), rgba(245,158,11,.10)); color:#92400e; border:1px solid rgba(245,158,11,.35); }
  .btn-amz-soft{ background:rgba(245,158,11,.12); color:#92400e; border:1px solid rgba(245,158,11,.25); padding:8px 10px; }

  @keyframes aiSpin{ to{ transform:rotate(360deg); } }

  @media (max-width: 992px){
    .catalog-grid{ grid-template-columns:1fr; }
    .catalog-main, .catalog-side{ grid-column:span 12; }
    .photos-grid{ grid-template-columns:1fr; }
    .actions-grid{ grid-template-columns:1fr; }
  }

  @media (max-width: 768px){
    .ai-right{ width:100%; justify-content:flex-start; }
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // ✅ Fotos: preview + badges + botón Quitar (sin cambios)
  document.addEventListener('DOMContentLoaded', function () {
    const map = [
      { input: 'photo_1_file', preview: 'photo_1_preview' },
      { input: 'photo_2_file', preview: 'photo_2_preview' },
      { input: 'photo_3_file', preview: 'photo_3_preview' },
    ];
    const objectUrls = new Map();

    function setFilledState(inputId, filled) {
      const card  = document.querySelector(`[data-photo-card="${inputId}"]`);
      const badge = document.querySelector(`[data-photo-badge="${inputId}"]`);
      if (card) card.classList.toggle('is-filled', !!filled);
      if (badge) {
        badge.classList.toggle('ok', !!filled);
        badge.textContent = filled ? 'Lista' : 'Pendiente';
      }
    }
    function setFilename(inputId, file) {
      const strong = document.querySelector(`[data-photo-strong="${inputId}"]`);
      const sub    = document.querySelector(`[data-photo-sub="${inputId}"]`);
      if (strong) strong.textContent = file ? file.name : 'Seleccionar';
      if (sub) sub.textContent = file ? `${Math.round(file.size/1024)} KB` : 'JPG/PNG/WEBP';
    }
    function renderPreview(previewId, file) {
      const prev = document.getElementById(previewId);
      if (!prev) return;

      if (objectUrls.has(previewId)) { URL.revokeObjectURL(objectUrls.get(previewId)); objectUrls.delete(previewId); }
      if (!file) { prev.innerHTML = ''; return; }

      const url = URL.createObjectURL(file);
      objectUrls.set(previewId, url);
      prev.innerHTML = `<img src="${url}" alt="preview">`;
    }

    map.forEach(({ input, preview }) => {
      const inp = document.getElementById(input);
      if (!inp) return;

      const prevEl = document.getElementById(preview);
      const alreadyHasImg = !!(prevEl && prevEl.querySelector('img'));
      if (alreadyHasImg) setFilledState(input, true);

      inp.addEventListener('change', function () {
        const file = inp.files && inp.files[0] ? inp.files[0] : null;
        setFilledState(input, !!file || alreadyHasImg);
        setFilename(input, file);
        renderPreview(preview, file);
      });

      const clearBtn = document.querySelector(`[data-photo-clear="${input}"]`);
      if (clearBtn) {
        clearBtn.addEventListener('click', function () {
          inp.value = '';
          setFilledState(input, false);
          setFilename(input, null);
          renderPreview(preview, null);
        });
      }
    });

    window.addEventListener('beforeunload', () => {
      for (const url of objectUrls.values()) URL.revokeObjectURL(url);
    });
  });

  // ✅ SweetAlert mini-toast
  const uiToast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2800,
    timerProgressBar: true
  });
  const AiAlerts = {
    success(t, x){ uiToast.fire({ icon:'success', title: t || 'Listo', text: x || '' }); },
    error(t, x){ uiToast.fire({ icon:'error', title: t || 'Error', text: x || '' }); },
    info(t, x){ uiToast.fire({ icon:'info', title: t || 'Info', text: x || '' }); }
  };

  // ✅ IA (misma lógica, solo UI más limpia)
  document.addEventListener('DOMContentLoaded', function () {
    const btnAi        = document.getElementById('btn-ai-analyze');
    const btnFillEmpty = document.getElementById('btn-ai-fill-empty');
    const inputFiles   = document.getElementById('ai_files');
    const statusEl     = document.getElementById('ai-helper-status');
    const helperBox    = document.getElementById('ai-helper');

    const panel   = document.getElementById('ai-items-panel');
    const tbody   = document.getElementById('ai-items-tbody');
    const countEl = document.getElementById('ai-items-count');
    const dropzone  = document.getElementById('ai-dropzone');
    const filesList = document.getElementById('ai-files-list');
    const clearBtn  = document.getElementById('ai-clear-list');

    const LS_KEY_ITEMS = 'catalog_ai_items';
    const LS_KEY_INDEX = 'catalog_ai_index';

    let aiItems = [];

    const saveItems = () => { try{ localStorage.setItem(LS_KEY_ITEMS, JSON.stringify(aiItems||[])); }catch(e){} };
    const saveIndex = (i) => { try{ localStorage.setItem(LS_KEY_INDEX, String(i??0)); }catch(e){} };
    const loadItems = () => { try{ const r=localStorage.getItem(LS_KEY_ITEMS); const p=r?JSON.parse(r):[]; return Array.isArray(p)?p:[]; }catch(e){ return []; } };
    const loadIndex = () => { try{ const r=localStorage.getItem(LS_KEY_INDEX); const n=parseInt(r??'0',10); return isNaN(n)?0:Math.max(0,n); }catch(e){ return 0; } };

    function refreshFileChips(files){
      if(!filesList) return;
      filesList.innerHTML='';
      if(!files||!files.length) return;
      Array.from(files).forEach(f=>{
        const chip=document.createElement('div');
        chip.className='ai-file-chip';
        chip.innerHTML=`<span>${escapeHtml(f.name)}</span>`;
        filesList.appendChild(chip);
      });
    }

    // click/drop
    if (dropzone && inputFiles){
      dropzone.addEventListener('click', (e)=> inputFiles.click());
      inputFiles.addEventListener('change', ()=> refreshFileChips(inputFiles.files));

      ['dragenter','dragover'].forEach(evt=>{
        dropzone.addEventListener(evt, (e)=>{ e.preventDefault(); e.stopPropagation(); dropzone.classList.add('is-dragover'); });
      });
      ['dragleave','dragend','drop'].forEach(evt=>{
        dropzone.addEventListener(evt, (e)=>{ e.preventDefault(); e.stopPropagation(); dropzone.classList.remove('is-dragover'); });
      });
      dropzone.addEventListener('drop', (e)=>{
        const dt=new DataTransfer();
        Array.from(e.dataTransfer.files||[]).forEach(file=>{
          if(file.type.startsWith('image/') || file.type==='application/pdf') dt.items.add(file);
        });
        if(dt.files.length){
          inputFiles.files=dt.files;
          refreshFileChips(dt.files);
        }
      });
    }

    if (clearBtn){
      clearBtn.addEventListener('click', ()=>{
        aiItems=[];
        try{ localStorage.removeItem(LS_KEY_ITEMS); localStorage.removeItem(LS_KEY_INDEX);}catch(e){}
        if(tbody) tbody.innerHTML='';
        if(panel) panel.style.display='none';
        if(filesList) filesList.innerHTML='';
        if(inputFiles) inputFiles.value='';
        if(statusEl) statusEl.textContent='Lista IA limpia.';
        AiAlerts.info('Listo', 'Se limpió la lista IA.');
      });
    }

    function attachUseButtons(){
      if(!tbody) return;
      tbody.querySelectorAll('button[data-ai-index]').forEach(btn=>{
        btn.addEventListener('click', ()=>{
          const i=parseInt(btn.getAttribute('data-ai-index'),10);
          const item=aiItems[i];
          if(!item) return;
          saveIndex(i);
          fillFromItem(item,{markSuggested:true,onlyIfEmpty:true});
          if(statusEl) statusEl.textContent=`Producto #${i+1} aplicado (solo vacíos).`;
          AiAlerts.success('Aplicado', 'Se rellenaron solo vacíos.');
        });
      });
    }

    function renderAiTable(){
      if(!tbody||!panel) return;
      tbody.innerHTML='';
      aiItems.forEach((item,idx)=>{
        const price=item.price ?? item.unit_price ?? item.precio ?? item.precio_unitario;
        const precio=(price!=null && price!=='') ? ('$ '+Number(price).toFixed(2)) : '—';
        const tr=document.createElement('tr');
        tr.innerHTML=`
          <td>${idx+1}</td>
          <td>${escapeHtml(item.name||item.title||'')}</td>
          <td>${escapeHtml(precio)}</td>
          <td>${escapeHtml(item.brand_name||item.brand||'')}</td>
          <td>${escapeHtml(item.meli_gtin||item.gtin||'')}</td>
          <td><button type="button" class="btn btn-ghost btn-xs" data-ai-index="${idx}">Usar</button></td>
        `;
        tbody.appendChild(tr);
      });
      if(countEl) countEl.textContent = `${aiItems.length}`;
      panel.style.display = aiItems.length ? 'block' : 'none';
      attachUseButtons();
    }

    // restore
    aiItems = loadItems();
    if (aiItems.length){
      renderAiTable();
      const idx = loadIndex();
      const item = aiItems[idx] || aiItems[0];
      if(item) fillFromItem(item,{markSuggested:true,onlyIfEmpty:true});
      if(statusEl) statusEl.textContent='Captura IA restaurada.';
    }

    if(btnFillEmpty){
      btnFillEmpty.addEventListener('click', ()=>{
        const items=loadItems();
        if(!items.length){
          AiAlerts.info('Sin captura IA', 'Primero analiza un PDF/imagenes.');
          if(statusEl) statusEl.textContent='No hay captura IA guardada.';
          return;
        }
        const idx=loadIndex();
        fillFromItem(items[idx]||items[0],{markSuggested:true,onlyIfEmpty:true});
        AiAlerts.success('Listo', 'Se rellenaron solo vacíos.');
        if(statusEl) statusEl.textContent='Rellenado de vacíos aplicado.';
      });
    }

    if(!btnAi || !inputFiles) return;
    btnAi.addEventListener('click', ()=>{
      if(!inputFiles.files || !inputFiles.files.length){
        AiAlerts.info('Sube un archivo', 'PDF/imagenes requeridos.');
        if(statusEl) statusEl.textContent='Sube un PDF/imagenes.';
        return;
      }
      const fd=new FormData();
      Array.from(inputFiles.files).forEach(f=>fd.append('files[]',f));
      fd.append('_token','{{ csrf_token() }}');

      btnAi.disabled=true;
      helperBox && helperBox.classList.add('ai-busy');
      if(statusEl) statusEl.textContent='Analizando...';

      aiItems=[]; if(tbody) tbody.innerHTML=''; if(panel) panel.style.display='none';

      fetch("{{ route('admin.catalog.ai-from-upload') }}",{method:'POST',body:fd})
        .then(r=>r.json())
        .then(data=>{
          if(data.error){
            AiAlerts.error('Error IA', data.error || 'No fue posible.');
            if(statusEl) statusEl.textContent='Error al analizar.';
            return;
          }
          const s=data.suggestions||{};
          fillFromItem(s,{markSuggested:true,onlyIfEmpty:true});

          aiItems=Array.isArray(data.items)?data.items:[];
          saveItems(); saveIndex(0);
          if(aiItems.length) renderAiTable();

          AiAlerts.success('Listo', 'Sugerencias aplicadas.');
          if(statusEl) statusEl.textContent='Listo. Revisa y guarda.';
        })
        .catch(()=>{ AiAlerts.error('Error', 'Problema de conexión.'); if(statusEl) statusEl.textContent='Error de conexión.'; })
        .finally(()=>{
          btnAi.disabled=false;
          helperBox && helperBox.classList.remove('ai-busy');
        });
    });

    function applyAiSuggestion(fieldName, value, markSuggested, onlyIfEmpty){
      if(value===undefined || value===null || value==='') return;
      const el=document.querySelector('[name="'+fieldName+'"]');
      if(!el) return;
      if(onlyIfEmpty){
        const cur=(el.value??'').toString().trim();
        if(cur!=='') return;
      }
      el.value=value;
      try{ el.dispatchEvent(new Event('change',{bubbles:true})); }catch(e){}
      if(markSuggested){
        el.classList.add('ai-suggested');
        setTimeout(()=>el.classList.remove('ai-suggested'), 5000);
      }
    }

    function fillFromItem(item, opts={}){
      const markSuggested=!!opts.markSuggested;
      const onlyIfEmpty=!!opts.onlyIfEmpty;
      if(!item || typeof item!=='object') return;

      const name=item.name ?? item.title ?? item.descripcion ?? item.description;
      const slug=item.slug;
      const description=item.description ?? item.descripcion_larga ?? item.desc;
      const excerpt=item.excerpt ?? item.resumen ?? item.short_description;
      const price=item.price ?? item.unit_price ?? item.precio ?? item.precio_unitario;
      const brand=item.brand_name ?? item.brand ?? item.marca;
      const model=item.model_name ?? item.model ?? item.modelo;
      const gtin=item.meli_gtin ?? item.gtin ?? item.ean ?? item.upc ?? item.barcode ?? item.codigo_barras;
      const qty=item.stock ?? item.quantity ?? item.qty ?? item.cantidad ?? item.cant;

      applyAiSuggestion('name', name, markSuggested, onlyIfEmpty);
      applyAiSuggestion('slug', slug, markSuggested, onlyIfEmpty);
      applyAiSuggestion('description', description, markSuggested, onlyIfEmpty);
      applyAiSuggestion('excerpt', excerpt, markSuggested, onlyIfEmpty);
      applyAiSuggestion('price', price, markSuggested, onlyIfEmpty);
      applyAiSuggestion('brand_name', brand, markSuggested, onlyIfEmpty);
      applyAiSuggestion('model_name', model, markSuggested, onlyIfEmpty);
      applyAiSuggestion('meli_gtin', gtin, markSuggested, onlyIfEmpty);
      applyAiSuggestion('stock', qty, markSuggested, onlyIfEmpty);

      if(item.category) applyAiSuggestion('category', item.category, markSuggested, onlyIfEmpty);
      if(item.amazon_sku) applyAiSuggestion('amazon_sku', item.amazon_sku, markSuggested, onlyIfEmpty);
      if(item.amazon_asin) applyAiSuggestion('amazon_asin', item.amazon_asin, markSuggested, onlyIfEmpty);
      if(item.amazon_product_type) applyAiSuggestion('amazon_product_type', item.amazon_product_type, markSuggested, onlyIfEmpty);
    }

    function escapeHtml(str){
      if(str===null || str===undefined) return '';
      return String(str).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;");
    }
  });
</script>
@endpush
