@php
  /** @var \App\Models\CatalogItem|null $item */
  $isEdit = isset($item);

  // Para labels en edit: si ya existen fotos guardadas
  $has1 = !empty($item->photo_1 ?? null);
  $has2 = !empty($item->photo_2 ?? null);
  $has3 = !empty($item->photo_3 ?? null);

  // Categorías internas (papelería, cómputo, etc.)
  $categories = $categories ?? config('catalog.product_categories', []);

  // Bandera simple: si ya tiene SKU (Amazon usa SKU sí o sí)
  $hasSku = !empty($item->sku ?? null);

  // ✅ defaults para ML
  $currentMeliCategory = old('meli_category_id', $item->meli_category_id ?? '');
  $currentListingType  = old('meli_listing_type_id', $item->meli_listing_type_id ?? '');

  // ✅ si quieres “sugerir” algo visual (no obligatorio)
  $meliHint = $currentMeliCategory
    ? "Usando meli_category_id: {$currentMeliCategory}"
    : "Deja esto vacío y ML puede forzar catálogo (y te saldrá error de title).";
@endphp

{{-- ✅ OJO: este bloque debe vivir DENTRO de tu <form id="catalog-form" ...> principal.
     Si tu form no tiene id="catalog-form", agrega el id en el form padre. --}}
@csrf
@if($isEdit)
  @method('PUT')
@endif

{{-- =========================================================
   🔹 BLOQUE DE CAPTURA ASISTIDA POR IA (ARCHIVOS / PDF) ✅ NO CAMBIAR
   ========================================================= --}}
<div class="ai-helper" id="ai-helper">
  <div class="ai-helper-icon-wrapper">
    <div class="ai-helper-icon" id="ai-helper-icon">🤖</div>
  </div>

  <div class="ai-helper-main">
    <div class="ai-helper-header">
      <div>
        <div class="ai-helper-title">Captura asistida por IA</div>
        <p class="ai-helper-subtitle">Sube tickets, remisiones o PDFs con varios productos y deja que la IA escriba por ti.</p>
      </div>
      <span class="ai-helper-chip">Beta</span>
    </div>

    <p class="ai-helper-text">
      La IA te sugiere:
      <strong>nombre, descripción, extracto, precio, marca, modelo, GTIN y stock</strong>.
      Revisa y ajusta antes de guardar.
    </p>

    <div class="ai-helper-row">
      <div class="ai-helper-input">
        <label class="lbl" style="margin-top:0;">Archivos para IA</label>

        <div id="ai-dropzone" class="ai-dropzone">
          <div class="ai-dropzone-icon">📄</div>
          <div class="ai-dropzone-body">
            <div class="ai-dropzone-title">Arrastra aquí PDFs o imágenes</div>
            <div class="ai-dropzone-sub">
              o
              <button type="button" class="ai-dropzone-btn" id="ai-pick-files">Elegir archivos</button>
            </div>
            <div class="ai-dropzone-hint">JPG, PNG, WEBP o PDF · máx. ~8 MB c/u</div>
          </div>

          <input id="ai_files"
                 name="ai_files[]"
                 type="file"
                 multiple
                 accept="image/*,.pdf"
                 class="ai-dropzone-input">
        </div>

        <div id="ai-files-list" class="ai-files-list"></div>

        <p class="hint">
          Se usan solo para sugerencias; <strong>no se guardan</strong>.
        </p>
      </div>

      <div class="ai-helper-actions">
        <button type="button" id="btn-ai-analyze" class="btn btn-primary ai-cta">
          <span class="ai-cta-spinner" aria-hidden="true"></span>
          <span class="ai-cta-text">Analizar con IA</span>
        </button>

        {{-- ✅ NUEVO: rellenar vacíos con lo último detectado (sin re-subir) --}}
        <button type="button" id="btn-ai-fill-missing" class="btn btn-ghost">
          <span class="i material-symbols-outlined" aria-hidden="true">auto_fix_high</span>
          Rellenar vacíos
        </button>

        @if($isEdit)
          <button type="button" id="btn-restore-original" class="btn btn-ghost">
            <span class="i material-symbols-outlined" aria-hidden="true">history</span>
            Rellenar desde original
          </button>
        @endif

        <p id="ai-helper-status" class="hint ai-helper-status">
          La IA solo te ahorra tecleo ✨
        </p>

        {{-- ✅ NUEVO: resumen de campos detectados por IA (sin “ruido” debajo de inputs) --}}
        <div id="ai-detected" class="ai-detected" style="display:none;">
          <div class="ai-detected-title">Detectado por IA</div>
          <div id="ai-detected-chips" class="ai-detected-chips"></div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- =========================================================
   🔹 TABLA DE PRODUCTOS DETECTADOS POR IA
   ========================================================= --}}
<div id="ai-items-panel" class="ai-items-panel" style="display:none;">
  <div class="ai-items-header">
    <div>
      <div class="ai-items-title">Productos detectados por IA</div>
      <p class="ai-items-text">
        Selecciona <strong>“Usar este”</strong> para rellenar sin volver a subir el archivo.
      </p>
    </div>

    <div class="ai-items-header-right">
      <span class="ai-items-badge" id="ai-items-count"></span>
      <button type="button" id="ai-clear-list" class="btn btn-ghost btn-xs ai-clear-btn">
        Limpiar lista
      </button>
    </div>
  </div>

  <div class="ai-items-table-wrapper">
    <table class="ai-items-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Nombre</th>
          <th>Precio</th>
          <th>Marca</th>
          <th>Modelo</th>
          <th>GTIN</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="ai-items-tbody"></tbody>
    </table>
  </div>
</div>

{{-- =========================================================
   🔹 FORMULARIO PRINCIPAL
   ========================================================= --}}
<div class="catalog-grid">
  {{-- Columna izquierda --}}
  <div class="catalog-main">
    <div class="card-section">
      <label class="lbl">Nombre *</label>
      <input name="name" class="inp" required
             placeholder="Ejemplo: Cinta de empaque Kyma 48mm x 150m canela (27 pzas)"
             value="{{ old('name', $item->name ?? '') }}">
    </div>

    <div class="card-section">
      <label class="lbl">Slug (opcional)</label>
      <input name="slug" class="inp"
             placeholder="cinta-de-empaque-kyma-48mm-150m-canela"
             value="{{ old('slug', $item->slug ?? '') }}">
    </div>

    <div class="card-section">
      <label class="lbl">Descripción</label>
      <textarea name="description" class="inp" rows="6"
                placeholder="Incluye medidas, color, cantidad, usos…">{{ old('description', $item->description ?? '') }}</textarea>
    </div>

    <div class="card-section">
      <label class="lbl">Extracto</label>
      <textarea name="excerpt" class="inp" rows="3"
                placeholder="Resumen corto para listados y ML.">{{ old('excerpt', $item->excerpt ?? '') }}</textarea>
    </div>

    {{-- =========================================================
       🔹 FOTOS (3 archivos)
       ========================================================= --}}
    <div class="side-card" style="margin-top:6px;">
      <h3 class="side-title">Fotos del producto (3)</h3>

      <div class="photos-grid">
        {{-- FOTO 1 --}}
        <div class="photo-card" data-photo-card="photo_1_file">
          <div class="photo-head">
            <div class="photo-title">Foto 1 (principal) *</div>
            <span class="photo-badge {{ ($isEdit && $has1) ? 'ok' : '' }}" data-photo-badge="photo_1_file">
              {{ ($isEdit && $has1) ? 'Cargada' : 'Pendiente' }}
            </span>
          </div>

          <label class="photo-drop" for="photo_1_file">
            <div class="photo-icon">📷</div>
            <div class="photo-text">
              <div class="photo-strong" data-photo-strong="photo_1_file">Seleccionar foto</div>
              <div class="photo-sub" data-photo-sub="photo_1_file">JPG / PNG / WEBP</div>
            </div>

            <input id="photo_1_file" name="photo_1_file" type="file"
                   class="photo-input" accept="image/*"
                   @if(!$isEdit) required @endif
                   capture="environment">
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
              <div class="photo-strong" data-photo-strong="photo_2_file">Seleccionar foto</div>
              <div class="photo-sub" data-photo-sub="photo_2_file">Frente / empaque</div>
            </div>

            <input id="photo_2_file" name="photo_2_file" type="file"
                   class="photo-input" accept="image/*"
                   @if(!$isEdit) required @endif
                   capture="environment">
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
              <div class="photo-strong" data-photo-strong="photo_3_file">Seleccionar foto</div>
              <div class="photo-sub" data-photo-sub="photo_3_file">Detalle / etiqueta</div>
            </div>

            <input id="photo_3_file" name="photo_3_file" type="file"
                   class="photo-input" accept="image/*"
                   @if(!$isEdit) required @endif
                   capture="environment">
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

  {{-- Columna derecha --}}
  <div class="catalog-side">
    <div class="side-card">
      <div class="card-section">
        <label class="lbl">SKU</label>
        <input name="sku" class="inp"
               placeholder="K-1"
               value="{{ old('sku', $item->sku ?? '') }}">
      </div>

      <div class="card-section card-inline">
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
        <label class="lbl">Precio oferta</label>
        <input name="sale_price" type="number" step="0.01" min="0" class="inp"
               value="{{ old('sale_price', $item->sale_price ?? '') }}">
      </div>

      <div class="card-section">
        <label class="lbl">Categoría (interna)</label>
        @php $currentCategory = old('category_key', $item->category_key ?? ''); @endphp
        <select name="category_key" class="inp">
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

    {{-- =========================================================
       ✅ MERCADO LIBRE
       ========================================================= --}}
    <div class="side-card">
      <h3 class="side-title">Mercado Libre</h3>

      <div class="card-section">
        <label class="lbl">Categoría ML (meli_category_id) ✅</label>
        <input name="meli_category_id" class="inp"
               placeholder="Ej: MLM167986"
               value="{{ $currentMeliCategory }}">
        <div class="hint">{{ $meliHint }}</div>
      </div>

      <div class="card-section">
        <label class="lbl">Listing type (opcional)</label>
        <input name="meli_listing_type_id" class="inp"
               placeholder="Ej: gold_special"
               value="{{ $currentListingType }}">
      </div>

      <div class="card-section">
        <label class="lbl">Marca</label>
        <input name="brand_name" class="inp"
               placeholder="Kyma"
               value="{{ old('brand_name', $item->brand_name ?? '') }}">
      </div>

      <div class="card-section">
        <label class="lbl">Modelo</label>
        <input name="model_name" class="inp"
               placeholder="48mmx150m canela"
               value="{{ old('model_name', $item->model_name ?? '') }}">
      </div>

      <div class="card-section">
        <label class="lbl">GTIN</label>
        <input name="meli_gtin" class="inp"
               placeholder="750…"
               value="{{ old('meli_gtin', $item->meli_gtin ?? '') }}">
      </div>

      <div class="card-section" style="margin-top:6px;">
        <div class="ml-help">
          <div class="ml-help-title">Tip rápido</div>
          <div class="ml-help-text">
            Si te sale <b>“title invalid / flujo de catálogo”</b>, normalmente es porque tu categoría/dominio es de catálogo.
            Solución: <b>pon aquí un meli_category_id correcto</b> (no catálogo) o publica con <b>?catalog=1</b>.
          </div>
        </div>
      </div>
    </div>

    {{-- Amazon (datos) --}}
    <div class="side-card">
      <h3 class="side-title">Amazon</h3>

      <div class="card-section">
        <label class="lbl">Seller SKU (amazon_sku)</label>
        <input name="amazon_sku" class="inp"
               placeholder="asd"
               value="{{ old('amazon_sku', $item->amazon_sku ?? '') }}">
      </div>

      <div class="card-section">
        <label class="lbl">ASIN</label>
        <input name="amazon_asin" class="inp"
               placeholder="B0..."
               value="{{ old('amazon_asin', $item->amazon_asin ?? '') }}">
      </div>

      <div class="card-section">
        <label class="lbl">Product Type</label>
        <input name="amazon_product_type" class="inp"
               placeholder="OFFICE_PRODUCTS…"
               value="{{ old('amazon_product_type', $item->amazon_product_type ?? '') }}">
      </div>
    </div>

    {{-- =========================================================
       ✅ Publicación (ARREGLADO)
       - NO @csrf dentro de botones
       - Forms separados (válido HTML)
       ========================================================= --}}
    @if($isEdit)
      <div class="side-card">
        <h3 class="side-title">Publicación</h3>

        <div class="pub-grid">
          {{-- Mercado Libre --}}
          <div class="pub-block pub-ml">
            <div class="pub-head">
              <div class="pub-title">Mercado Libre</div>
              <div class="pub-sub">Publica, pausa o activa.</div>
            </div>

            <div class="pub-actions">
              {{-- ✅ SI O SÍ --}}
              <form method="POST" action="{{ route('admin.catalog.meli.publish', $item) }}?force=1" class="pub-form">
                @csrf
                <button type="submit" class="btn btn-pill btn-ml">
                  <span class="i material-symbols-outlined" aria-hidden="true">rocket_launch</span>
                  Publicar (SI O SÍ)
                </button>
              </form>

              {{-- ✅ NORMAL --}}
              <form method="POST" action="{{ route('admin.catalog.meli.publish', $item) }}" class="pub-form">
                @csrf
                <button type="submit" class="btn btn-pill btn-soft btn-ml-soft">
                  <span class="i material-symbols-outlined" aria-hidden="true">cloud_upload</span>
                  Publicar / Actualizar (normal)
                </button>
              </form>

              {{-- ✅ CATÁLOGO --}}
              <form method="POST" action="{{ route('admin.catalog.meli.publish', $item) }}?catalog=1" class="pub-form">
                @csrf
                <button type="submit" class="btn btn-pill btn-soft btn-ml-soft">
                  <span class="i material-symbols-outlined" aria-hidden="true">inventory_2</span>
                  Publicar (catálogo)
                </button>
              </form>

              <div class="pub-row">
                <form method="POST" action="{{ route('admin.catalog.meli.pause', $item) }}" class="pub-form">
                  @csrf
                  <button type="submit" class="btn btn-pill btn-soft btn-ml-soft">
                    <span class="i material-symbols-outlined" aria-hidden="true">pause_circle</span>
                    Pausar
                  </button>
                </form>

                <form method="POST" action="{{ route('admin.catalog.meli.activate', $item) }}" class="pub-form">
                  @csrf
                  <button type="submit" class="btn btn-pill btn-soft btn-ml-soft">
                    <span class="i material-symbols-outlined" aria-hidden="true">play_circle</span>
                    Activar
                  </button>
                </form>

                <a class="btn btn-pill btn-soft btn-ml-soft"
                   href="{{ route('admin.catalog.meli.view', $item) }}"
                   target="_blank" rel="noopener">
                  <span class="i material-symbols-outlined" aria-hidden="true">open_in_new</span>
                  Ver
                </a>
              </div>
            </div>
          </div>

          {{-- Amazon --}}
          <div class="pub-block pub-amz">
            <div class="pub-head">
              <div class="pub-title">Amazon (SP-API)</div>
              <div class="pub-sub">Publica, pausa o activa.</div>
            </div>

            @if(!$hasSku)
              <div class="pub-warn">
                <span class="material-symbols-outlined" aria-hidden="true">info</span>
                <div>
                  <div class="pub-warn-title">Falta SKU</div>
                  <div class="pub-warn-text">Guarda primero un SKU para publicar.</div>
                </div>
              </div>
            @endif

            <div class="pub-actions">
              <form method="POST" action="{{ route('admin.catalog.amazon.publish', $item) }}" class="pub-form">
                @csrf
                <button type="submit" class="btn btn-pill btn-amz" @disabled(!$hasSku)>
                  <span class="i material-symbols-outlined" aria-hidden="true">cloud_upload</span>
                  Publicar / Actualizar
                </button>
              </form>

              <div class="pub-row">
                <form method="POST" action="{{ route('admin.catalog.amazon.pause', $item) }}" class="pub-form">
                  @csrf
                  <button type="submit" class="btn btn-pill btn-soft btn-amz-soft" @disabled(!$hasSku)>
                    <span class="i material-symbols-outlined" aria-hidden="true">pause_circle</span>
                    Pausar
                  </button>
                </form>

                <form method="POST" action="{{ route('admin.catalog.amazon.activate', $item) }}" class="pub-form">
                  @csrf
                  <button type="submit" class="btn btn-pill btn-soft btn-amz-soft" @disabled(!$hasSku)>
                    <span class="i material-symbols-outlined" aria-hidden="true">play_circle</span>
                    Activar
                  </button>
                </form>

                <a class="btn btn-pill btn-soft btn-amz-soft"
                   href="{{ route('admin.catalog.amazon.view', $item) }}"
                   target="_blank" rel="noopener"
                   @if(!$hasSku) aria-disabled="true" onclick="return false;" @endif>
                  <span class="i material-symbols-outlined" aria-hidden="true">open_in_new</span>
                  Ver
                </a>
              </div>
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
<link rel="stylesheet" href="{{ asset('css/form.css') }}?v={{ time() }}">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  // ✅ Toggle clasificación interna (NO tocar)
  document.addEventListener('DOMContentLoaded', function(){
    const chk = document.getElementById('use_internal');
    const box = document.getElementById('internal-box');
    if (chk && box){
      const sync = () => box.classList.toggle('is-disabled', !chk.checked);
      chk.addEventListener('change', sync);
      sync();
    }
  });

  // ✅ Fotos: preview + badges + botón Quitar (NO tocar)
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
      if (strong) strong.textContent = file ? file.name : 'Seleccionar foto';
      if (sub) sub.textContent = file ? `${Math.round(file.size/1024)} KB` : 'JPG / PNG / WEBP';
    }

    function renderPreview(previewId, file) {
      const prev = document.getElementById(previewId);
      if (!prev) return;

      if (objectUrls.has(previewId)) {
        URL.revokeObjectURL(objectUrls.get(previewId));
        objectUrls.delete(previewId);
      }

      if (!file) {
        return; // deja la imagen guardada intacta
      }

      const url = URL.createObjectURL(file);
      objectUrls.set(previewId, url);
      prev.innerHTML = `<img src="${url}" alt="preview">`;
    }

    map.forEach(({ input, preview }) => {
      const inp = document.getElementById(input);
      if (!inp) return;

      const prevEl = document.getElementById(preview);
      const alreadyHasImg = !!(prevEl && prevEl.querySelector('img'));
      if (alreadyHasImg) {
        setFilledState(input, true);
        setFilename(input, null);
      }

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
        });
      }
    });

    window.addEventListener('beforeunload', () => {
      for (const url of objectUrls.values()) URL.revokeObjectURL(url);
    });
  });

  // ================================
  // 🔹 SweetAlert UI
  // ================================
  const uiToast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3200,
    timerProgressBar: true,
    customClass: { popup: 'swal2-popup-compact' }
  });

  const AiAlerts = {
    success(title, text){ uiToast.fire({ icon:'success', title: title || 'Listo', text: text || '' }); },
    error(title, text){ uiToast.fire({ icon:'error', title: title || 'Error', text: text || '' }); },
    info(title, text){ uiToast.fire({ icon:'info', title: title || 'Info', text: text || '' }); }
  };

  // ================================
  // ✅ Rellenar desde original (EDIT)
  // ================================
  document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('btn-restore-original');
    if (!btn) return;

    const fieldNames = [
      'name','slug','description','excerpt','sku',
      'price','stock','sale_price','category_key','status','published_at',
      'brand_name','model_name','meli_gtin','meli_category_id','meli_listing_type_id',
      'amazon_sku','amazon_asin','amazon_product_type'
    ];

    const original = {};
    fieldNames.forEach(n=>{
      const el = document.querySelector(`[name="${n}"]`);
      if (!el) return;
      original[n] = (el.type === 'checkbox') ? el.checked : (el.value ?? '');
    });

    btn.addEventListener('click', function () {
      let changed = 0;

      fieldNames.forEach(n=>{
        const el = document.querySelector(`[name="${n}"]`);
        if (!el) return;
        if (el.type === 'checkbox') return;

        const cur = (el.value ?? '').toString().trim();
        const orig = (original[n] ?? '').toString();

        if (cur === '' && orig !== '') {
          el.value = orig;
          try { el.dispatchEvent(new Event('change', { bubbles:true })); } catch(e){}
          el.classList.add('ai-suggested');
          setTimeout(() => el.classList.remove('ai-suggested'), 2500);
          changed++;
        }
      });

      if (changed) AiAlerts.success('Listo', `Se rellenaron ${changed} campo(s).`);
      else AiAlerts.info('Sin cambios', 'No hay campos vacíos para rellenar.');
    });
  });

  // ================================
  // 🔹 IA: subir archivos + dropzone + rellenar campos
  // ================================
  document.addEventListener('DOMContentLoaded', function () {
    const btnAi      = document.getElementById('btn-ai-analyze');
    const btnFill    = document.getElementById('btn-ai-fill-missing');
    const inputFiles = document.getElementById('ai_files');
    const statusEl   = document.getElementById('ai-helper-status');
    const helperBox  = document.getElementById('ai-helper');

    const panel      = document.getElementById('ai-items-panel');
    const tbody      = document.getElementById('ai-items-tbody');
    const countEl    = document.getElementById('ai-items-count');

    const dropzone   = document.getElementById('ai-dropzone');
    const filesList  = document.getElementById('ai-files-list');
    const clearBtn   = document.getElementById('ai-clear-list');

    const pickBtn    = document.getElementById('ai-pick-files');

    const detectedBox   = document.getElementById('ai-detected');
    const detectedChips = document.getElementById('ai-detected-chips');

    const LS_KEY_ITEMS = 'catalog_ai_items';
    const LS_KEY_INDEX = 'catalog_ai_index';
    const LS_KEY_LAST  = 'catalog_ai_last_suggestions';

    let aiItems = [];
    let lastSuggestions = null;

    if (pickBtn && inputFiles) {
      pickBtn.addEventListener('click', function(e){
        e.preventDefault();
        e.stopPropagation();
        inputFiles.click();
      });
    }

    function saveAiItemsToStorage() {
      try { localStorage.setItem(LS_KEY_ITEMS, JSON.stringify(aiItems || [])); } catch (e) {}
    }
    function saveAiIndexToStorage(idx) {
      try { localStorage.setItem(LS_KEY_INDEX, String(idx ?? 0)); } catch (e) {}
    }
    function loadAiItemsFromStorage() {
      try {
        const raw = localStorage.getItem(LS_KEY_ITEMS);
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
      } catch (e) { return []; }
    }
    function loadAiIndexFromStorage() {
      try {
        const raw = localStorage.getItem(LS_KEY_INDEX);
        const idx = parseInt(raw ?? '0', 10);
        return isNaN(idx) ? 0 : Math.max(0, idx);
      } catch (e) { return 0; }
    }
    function saveLastSuggestions(s) {
      lastSuggestions = s && typeof s === 'object' ? s : null;
      try { localStorage.setItem(LS_KEY_LAST, JSON.stringify(lastSuggestions || null)); } catch(e){}
    }
    function loadLastSuggestions() {
      try {
        const raw = localStorage.getItem(LS_KEY_LAST);
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        return (parsed && typeof parsed === 'object') ? parsed : null;
      } catch(e){ return null; }
    }

    function escapeHtml(str) {
      if (str === null || str === undefined) return '';
      return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }

    function refreshFileChips(files) {
      if (!filesList) return;
      filesList.innerHTML = '';
      if (!files || !files.length) return;

      Array.from(files).forEach(file => {
        const chip = document.createElement('div');
        chip.className = 'ai-file-chip';
        chip.innerHTML = `<span>${escapeHtml(file.name)}</span>`;
        filesList.appendChild(chip);
      });
    }

    if (inputFiles) {
      inputFiles.addEventListener('change', function () {
        refreshFileChips(inputFiles.files);
      });
    }

    if (dropzone && inputFiles) {
      ['dragenter','dragover'].forEach(evt => {
        dropzone.addEventListener(evt, function (e) {
          e.preventDefault(); e.stopPropagation();
          dropzone.classList.add('is-dragover');
        });
      });

      ['dragleave','dragend','drop'].forEach(evt => {
        dropzone.addEventListener(evt, function (e) {
          e.preventDefault(); e.stopPropagation();
          dropzone.classList.remove('is-dragover');
        });
      });

      dropzone.addEventListener('drop', function (e) {
        const dt = new DataTransfer();
        Array.from(e.dataTransfer.files || []).forEach(file => {
          if (file.type.startsWith('image/') || file.type === 'application/pdf') dt.items.add(file);
        });
        if (dt.files.length) {
          inputFiles.files = dt.files;
          refreshFileChips(dt.files);
        }
      });
    }

    function renderDetectedChips(item) {
      if (!detectedBox || !detectedChips) return;

      const pairs = [];
      const pick = (k, v) => {
        if (v === undefined || v === null) return;
        const s = String(v).trim();
        if (!s) return;
        pairs.push([k, s]);
      };

      pick('Nombre', item.name ?? item.title);
      pick('Slug', item.slug);
      pick('Descripción', item.description ?? item.descripcion_larga ?? item.desc);
      pick('Extracto', item.excerpt ?? item.resumen);
      pick('Precio', item.price ?? item.unit_price ?? item.precio ?? item.precio_unitario);
      pick('Stock', item.stock ?? item.quantity ?? item.qty ?? item.cantidad ?? item.cant);
      pick('Marca', item.brand_name ?? item.brand ?? item.marca);
      pick('Modelo', item.model_name ?? item.model ?? item.modelo);
      pick('GTIN', item.meli_gtin ?? item.gtin ?? item.ean ?? item.upc ?? item.barcode ?? item.codigo_barras);
      pick('ML categoría', item.meli_category_id ?? item.category_id ?? item.ml_category_id ?? item.meli_category);

      detectedChips.innerHTML = '';
      if (!pairs.length) {
        detectedBox.style.display = 'none';
        return;
      }

      pairs.slice(0, 10).forEach(([k,v]) => {
        const el = document.createElement('div');
        el.className = 'ai-chip';
        el.innerHTML = `<span class="k">${escapeHtml(k)}:</span> <span class="v">${escapeHtml(v)}</span>`;
        detectedChips.appendChild(el);
      });

      detectedBox.style.display = 'block';
    }

    if (clearBtn) {
      clearBtn.addEventListener('click', function () {
        aiItems = [];
        lastSuggestions = null;
        try {
          localStorage.removeItem(LS_KEY_ITEMS);
          localStorage.removeItem(LS_KEY_INDEX);
          localStorage.removeItem(LS_KEY_LAST);
        } catch (e) {}
        if (tbody) tbody.innerHTML = '';
        if (panel) panel.style.display = 'none';
        if (filesList) filesList.innerHTML = '';
        if (inputFiles) inputFiles.value = '';
        if (detectedBox) detectedBox.style.display = 'none';
        if (statusEl) statusEl.textContent = 'Lista IA limpia. Sube un nuevo PDF o imágenes.';
        AiAlerts.info('Lista limpia', 'Se reinició la lista de productos IA.');
      });
    }

    function attachUseButtons() {
      if (!tbody) return;
      tbody.querySelectorAll('button[data-ai-index]').forEach(btn => {
        btn.addEventListener('click', function () {
          const i = parseInt(this.getAttribute('data-ai-index'), 10);
          const item = aiItems[i];
          if (!item) return;
          saveAiIndexToStorage(i);
          saveLastSuggestions(item);
          fillFromItem(item, { markSuggested: true, onlyMissing: false });
          renderDetectedChips(item);
          if (statusEl) statusEl.textContent = 'Se cargó el producto #' + (i + 1) + ' desde la lista IA.';
          AiAlerts.info('Producto cargado', 'Se llenó el formulario con el producto #' + (i + 1) + '.');
        });
      });
    }

    function renderAiTable() {
      if (!tbody || !panel) return;
      tbody.innerHTML = '';

      aiItems.forEach((item, idx) => {
        const price = item.price ?? item.unit_price ?? item.precio ?? item.precio_unitario;
        const precio = (price != null && price !== '') ? '$ ' + Number(price).toFixed(2) : '—';

        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${idx + 1}</td>
          <td>${escapeHtml(item.name || item.title || '')}</td>
          <td>${escapeHtml(precio)}</td>
          <td>${escapeHtml(item.brand_name || item.brand || item.marca || '')}</td>
          <td>${escapeHtml(item.model_name || item.model || item.modelo || '')}</td>
          <td>${escapeHtml(item.meli_gtin || item.gtin || item.ean || '')}</td>
          <td><button type="button" class="btn btn-ghost btn-xs" data-ai-index="${idx}">Usar este</button></td>
        `;
        tbody.appendChild(tr);
      });

      if (countEl) countEl.textContent = aiItems.length === 1 ? '1 producto' : (aiItems.length + ' productos');
      panel.style.display = aiItems.length ? 'block' : 'none';
      attachUseButtons();
    }

    aiItems = loadAiItemsFromStorage();
    lastSuggestions = loadLastSuggestions();

    if (aiItems.length) {
      renderAiTable();
      if (statusEl) statusEl.textContent = 'Productos IA restaurados. Puedes continuar sin re-subir.';
      const idx = loadAiIndexFromStorage();
      const item = aiItems[idx] || aiItems[0];
      if (item) {
        saveLastSuggestions(item);
        renderDetectedChips(item);
        fillFromItem(item, { markSuggested: false, onlyMissing: true });
      }
    } else if (lastSuggestions) {
      renderDetectedChips(lastSuggestions);
    }

    if (btnFill) {
      btnFill.addEventListener('click', function () {
        const base = lastSuggestions || loadLastSuggestions();
        if (!base) {
          AiAlerts.info('Sin sugerencias', 'Primero analiza con IA o selecciona un producto detectado.');
          return;
        }
        const changed = fillFromItem(base, { markSuggested: true, onlyMissing: true });
        renderDetectedChips(base);
        if (changed > 0) AiAlerts.success('Listo', `Se rellenaron ${changed} campo(s) vacío(s).`);
        else AiAlerts.info('Sin cambios', 'No hay campos vacíos para rellenar.');
      });
    }

    if (!btnAi || !inputFiles) return;

    btnAi.addEventListener('click', function () {
      if (!inputFiles.files || !inputFiles.files.length) {
        AiAlerts.info('Sube un archivo', 'Necesito al menos una imagen o PDF.');
        if (statusEl) statusEl.textContent = 'Sube un archivo para analizar.';
        return;
      }

      const formData = new FormData();
      Array.from(inputFiles.files).forEach(f => formData.append('files[]', f));
      formData.append('_token', '{{ csrf_token() }}');

      btnAi.disabled = true;
      const labelEl = btnAi.querySelector('.ai-cta-text');
      const originalText = labelEl ? labelEl.textContent : btnAi.textContent;

      if (labelEl) labelEl.textContent = 'Analizando...';
      else btnAi.textContent = 'Analizando...';

      if (helperBox) helperBox.classList.add('ai-busy');
      if (statusEl) statusEl.textContent = 'Enviando a IA...';

      aiItems = [];
      if (tbody) tbody.innerHTML = '';
      if (panel) panel.style.display = 'none';

      fetch("{{ route('admin.catalog.ai-from-upload') }}", {
        method: "POST",
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.error) {
          if (statusEl) statusEl.textContent = 'Error: ' + (data.error || 'No se pudo obtener sugerencias.');
          AiAlerts.error('Error', data.error || 'No se pudo obtener sugerencias.');
          return;
        }

        const s = data.suggestions || {};
        saveLastSuggestions(s);
        renderDetectedChips(s);

        fillFromItem(s, { markSuggested: true, onlyMissing: false });

        aiItems = Array.isArray(data.items) ? data.items : [];
        saveAiItemsToStorage();
        saveAiIndexToStorage(0);

        if (aiItems.length) renderAiTable();

        if (statusEl) statusEl.textContent = 'Listo: revisa las sugerencias.';
        AiAlerts.success('Sugerencias listas', 'Campos completados.');
      })
      .catch(err => {
        console.error(err);
        if (statusEl) statusEl.textContent = 'Error al contactar la IA.';
        AiAlerts.error('Error de conexión', 'No se pudo contactar la IA.');
      })
      .finally(() => {
        btnAi.disabled = false;
        if (labelEl) labelEl.textContent = originalText;
        else btnAi.textContent = originalText;
        if (helperBox) helperBox.classList.remove('ai-busy');
      });
    });

    function isEmptyField(el){
      if (!el) return true;
      if (el.type === 'checkbox') return !el.checked;
      const v = (el.value ?? '').toString().trim();
      return v === '';
    }

    function applyAiSuggestion(fieldName, value, markSuggested, onlyMissing) {
      if (value === undefined || value === null || value === '') return 0;

      const el = document.querySelector('[name="' + fieldName + '"]');
      if (!el) return 0;

      if (onlyMissing && !isEmptyField(el)) return 0;

      el.value = value;
      try { el.dispatchEvent(new Event('change', { bubbles:true })); } catch(e){}

      if (markSuggested) {
        el.classList.add('ai-suggested');
        setTimeout(() => el.classList.remove('ai-suggested'), 7000);
      }
      return 1;
    }

    function fillFromItem(item, opts = {}) {
      const markSuggested = !!opts.markSuggested;
      const onlyMissing   = !!opts.onlyMissing;
      if (!item || typeof item !== 'object') return 0;

      const name        = item.name ?? item.title ?? item.descripcion ?? item.description;
      const slug        = item.slug;
      const description = item.description ?? item.descripcion_larga ?? item.desc;
      const excerpt     = item.excerpt ?? item.resumen ?? item.short_description;
      const price       = item.price ?? item.unit_price ?? item.precio ?? item.precio_unitario;

      const brand       = item.brand_name ?? item.brand ?? item.marca;
      const model       = item.model_name ?? item.model ?? item.modelo;
      const gtin        = item.meli_gtin ?? item.gtin ?? item.ean ?? item.upc ?? item.barcode ?? item.codigo_barras;
      const qty         = item.stock ?? item.quantity ?? item.qty ?? item.cantidad ?? item.cant;

      const meliCat      = item.meli_category_id ?? item.category_id ?? item.ml_category_id ?? item.meli_category;
      const listingType  = item.meli_listing_type_id ?? item.listing_type_id ?? item.ml_listing_type_id;

      let changed = 0;

      changed += applyAiSuggestion('name', name, markSuggested, onlyMissing);
      changed += applyAiSuggestion('slug', slug, markSuggested, onlyMissing);
      changed += applyAiSuggestion('description', description, markSuggested, onlyMissing);
      changed += applyAiSuggestion('excerpt', excerpt, markSuggested, onlyMissing);
      changed += applyAiSuggestion('price', price, markSuggested, onlyMissing);
      changed += applyAiSuggestion('brand_name', brand, markSuggested, onlyMissing);
      changed += applyAiSuggestion('model_name', model, markSuggested, onlyMissing);
      changed += applyAiSuggestion('meli_gtin', gtin, markSuggested, onlyMissing);
      changed += applyAiSuggestion('stock', qty, markSuggested, onlyMissing);

      if (item.category_key) changed += applyAiSuggestion('category_key', item.category_key, markSuggested, onlyMissing);

      changed += applyAiSuggestion('meli_category_id', meliCat, markSuggested, onlyMissing);
      changed += applyAiSuggestion('meli_listing_type_id', listingType, markSuggested, onlyMissing);

      changed += applyAiSuggestion('amazon_sku', item.amazon_sku, markSuggested, onlyMissing);
      changed += applyAiSuggestion('amazon_asin', item.amazon_asin, markSuggested, onlyMissing);
      changed += applyAiSuggestion('amazon_product_type', item.amazon_product_type, markSuggested, onlyMissing);

      return changed;
    }
  });
</script>

@if(session('ok'))
<script>
  document.addEventListener('DOMContentLoaded', function () {
    try {
      const LS_KEY_ITEMS = 'catalog_ai_items';
      const LS_KEY_INDEX = 'catalog_ai_index';
      const rawItems = localStorage.getItem(LS_KEY_ITEMS);
      if (rawItems) {
        let items = JSON.parse(rawItems);
        if (!Array.isArray(items)) items = [];
        const rawIdx = localStorage.getItem(LS_KEY_INDEX);
        let idx = parseInt(rawIdx ?? '0', 10);
        if (isNaN(idx) || idx < 0 || idx >= items.length) idx = 0;
        if (items.length) {
          items.splice(idx, 1);
          localStorage.setItem(LS_KEY_ITEMS, JSON.stringify(items));
          localStorage.setItem(LS_KEY_INDEX, '0');
        }
      }
    } catch (e) {}

    Swal.fire({
      icon: 'success',
      title: 'Listo ✨',
      text: @json(session('ok')),
      customClass: { popup: 'swal2-popup-compact' },
      confirmButtonText: 'Continuar'
    });
  });
</script>
@endif

@if($errors->any())
<script>
  document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
      icon: 'error',
      title: 'Hay campos por revisar',
      html: `{!! implode('<br>', $errors->all()) !!}`,
      customClass: { popup: 'swal2-popup-compact' },
      confirmButtonText: 'Entendido'
    });
  });
</script>
@endif
@endpush
