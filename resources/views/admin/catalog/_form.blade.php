{{-- ✅ resources/views/admin/catalog/_form.blade.php (o donde tengas este formulario) --}}

@php
  /** @var \App\Models\CatalogItem|null $item */
  $isEdit = isset($item);

  // Para labels en edit: si ya existen fotos guardadas
  $has1 = !empty($item->photo_1 ?? null);
  $has2 = !empty($item->photo_2 ?? null);
  $has3 = !empty($item->photo_3 ?? null);

  // Categorías legibles (papelería, cómputo, etc.)
  $categories = $categories ?? config('catalog.product_categories', []);

  // Bandera simple: si ya tiene SKU (Amazon usa SKU sí o sí)
  $hasSku = !empty($item->sku ?? null);
@endphp

{{-- ✅ FORM PRINCIPAL: SOLO captura/edición (SIN forms anidados dentro) --}}
<form
  id="catalogItemForm"
  method="POST"
  action="{{ $isEdit ? route('admin.catalog.update', $item) : route('admin.catalog.store') }}"
  enctype="multipart/form-data"
>
  @csrf
  @if($isEdit)
    @method('PUT')
  @endif

  {{-- =========================================================
     🔹 BLOQUE DE CAPTURA ASISTIDA POR IA (ARCHIVOS / PDF)
     ========================================================= --}}
  <div class="ai-helper" id="ai-helper">
    <div class="ai-helper-icon-wrapper">
      <div class="ai-helper-glow"></div>
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
        La IA leerá lo que vea y te sugerirá automáticamente:
        <strong>nombre, descripción, extracto, precio, marca, modelo, GTIN y cantidad (stock)</strong>.
        Siempre puedes revisar, corregir y complementar antes de guardar.
      </p>

      <div class="ai-helper-row">
        <div class="ai-helper-input">
          <label class="lbl" style="margin-top:0;">Archivos para IA</label>

          <div id="ai-dropzone" class="ai-dropzone">
            <div class="ai-dropzone-icon">📄</div>
            <div class="ai-dropzone-body">
              <div class="ai-dropzone-title">Arrastra aquí tus PDFs o imágenes</div>
              <div class="ai-dropzone-sub">
                o
                <button type="button" class="ai-dropzone-btn">Elegir archivos</button>
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
            Se usan solo para generar sugerencias, <strong>no se guardan</strong> en tu sistema.
          </p>
        </div>

        <div class="ai-helper-actions">
          <button type="button" id="btn-ai-analyze" class="btn btn-primary ai-cta">
            <span class="ai-cta-spinner" aria-hidden="true"></span>
            <span class="ai-cta-text">Analizar con IA</span>
          </button>
          <p id="ai-helper-status" class="hint ai-helper-status">
            La IA no sustituye tu criterio, solo te ahorra tecleo repetitivo ✨
          </p>
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
          Estos son los productos que la IA encontró en el PDF/imágenes.
          Haz clic en <strong>“Usar este”</strong> para rellenar el formulario con ese producto sin volver a subir el archivo.
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
               placeholder="Ejemplo: Lapicero bolígrafo azul Bic punta fina 0.7mm"
               value="{{ old('name', $item->name ?? '') }}">
        <p class="hint">
          Usa un nombre completo: tipo de producto + marca + modelo + característica clave.
          Esto ayuda al SEO y evita rechazos en Mercado Libre.
        </p>
      </div>

      <div class="card-section">
        <label class="lbl">Slug (opcional)</label>
        <input name="slug" class="inp"
               placeholder="lapicero-bic-azul-07mm"
               value="{{ old('slug', $item->slug ?? '') }}">
        <p class="hint">
          Déjalo vacío para generarlo automáticamente a partir del nombre, salvo que necesites un slug específico.
        </p>
      </div>

      <div class="card-section">
        <label class="lbl">Descripción</label>
        <textarea name="description" class="inp" rows="6"
                  placeholder="Describe el producto, sus usos, materiales, medidas, garantía, etc.">{{ old('description', $item->description ?? '') }}</textarea>
        <p class="hint">
          Es la descripción larga que verán tus clientes. Evita mayúsculas excesivas y texto repetitivo.
        </p>
      </div>

      <div class="card-section">
        <label class="lbl">Extracto</label>
        <textarea name="excerpt" class="inp" rows="3"
                  placeholder="Resumen corto para listados y Mercado Libre (ej. Caja con 12 piezas, tinta azul, punta 0.7mm).">{{ old('excerpt', $item->excerpt ?? '') }}</textarea>
        <p class="hint">
          Un resumen breve con la información más importante: presentación, cantidad, color o medida.
        </p>
      </div>

      {{-- =========================================================
         🔹 FOTOS (3 archivos) — NO URLs, UI mejorada
         ========================================================= --}}
      <div class="side-card" style="margin-top:6px;">
        <h3 class="side-title">Fotos del producto (3)</h3>

        <div class="hint" style="margin-top:0;">
          Toca cada tarjeta para seleccionar una foto. Se guarda en tu sistema.
        </div>

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

              <input
                id="photo_1_file"
                name="photo_1_file"
                type="file"
                class="photo-input"
                accept="image/*"
                @if(!$isEdit) required @endif
                capture="environment"
              >
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

              <input
                id="photo_2_file"
                name="photo_2_file"
                type="file"
                class="photo-input"
                accept="image/*"
                @if(!$isEdit) required @endif
                capture="environment"
              >
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

              <input
                id="photo_3_file"
                name="photo_3_file"
                type="file"
                class="photo-input"
                accept="image/*"
                @if(!$isEdit) required @endif
                capture="environment"
              >
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

        <div class="hint" style="margin-top:10px;">
          Tip: fondo claro + buena luz = se ven más pro en la ficha con QR.
        </div>
      </div>
    </div>

    {{-- Columna derecha --}}
    <div class="catalog-side">
      <div class="side-card">
        <div class="card-section">
          <label class="lbl">SKU</label>
          <input name="sku" class="inp"
                 placeholder="Código interno o del proveedor"
                 value="{{ old('sku', $item->sku ?? '') }}">
          <p class="hint">
            Usa un SKU claro y único. Te ayuda a localizar el producto rápidamente en tu catálogo.
            <span class="hint" style="display:block;margin-top:3px;">
              Nota: Amazon requiere SKU para publicar.
            </span>
          </p>
        </div>

        <div class="card-section card-inline">
          <div class="card-inline-item">
            <label class="lbl">Precio *</label>
            <input name="price" type="number" step="0.01" min="0" class="inp" required
                   value="{{ old('price', $item->price ?? 0) }}">
            <p class="hint">
              Precio base en MXN. Algunas categorías de Mercado Libre requieren un mínimo.
            </p>
          </div>

          <div class="card-inline-item">
            <label class="lbl">Stock</label>
            <input name="stock" type="number" step="1" min="0" class="inp"
                   value="{{ old('stock', $item->stock ?? 0) }}">
            <p class="hint">
              Unidades disponibles. La IA puede sugerir la cantidad comprada según el documento.
            </p>
          </div>
        </div>

        <div class="card-section">
          <label class="lbl">Precio oferta</label>
          <input name="sale_price" type="number" step="0.01" min="0" class="inp"
                 value="{{ old('sale_price', $item->sale_price ?? '') }}">
          <p class="hint">
            Solo si hay promoción. Si lo dejas vacío, se usará el precio base.
          </p>
        </div>

        {{-- 🔹 Categoría legible (string) --}}
        <div class="card-section">
          <label class="lbl">Categoría</label>
          @php
            $currentCategory = old('category', $item->category ?? '');
          @endphp
          <select name="category" class="inp">
            <option value="">Sin categoría</option>
            @foreach($categories as $key => $label)
              <option value="{{ $key }}" @selected($currentCategory === $key)>{{ $label }}</option>
            @endforeach
          </select>
          <p class="hint">
            Sirve para agrupar en el catálogo web (Papelería, Escritura, Cómputo, Oficina, etc.).
          </p>
        </div>

        <div class="card-section">
          <label class="lbl">Estado *</label>
          <select name="status" class="inp" required>
            @php $st = (string)old('status', isset($item)? (string)$item->status : '0'); @endphp
            <option value="0" @selected($st==='0')>Borrador (no visible)</option>
            <option value="1" @selected($st==='1')>Publicado</option>
            <option value="2" @selected($st==='2')>Oculto (no listado, pero accesible por link)</option>
          </select>
        </div>

        <div class="card-section">
          <label class="lbl">Publicado en</label>
          <input name="published_at" type="datetime-local" class="inp"
                 value="{{ old('published_at', isset($item->published_at)? $item->published_at->format('Y-m-d\TH:i') : '') }}">
          <p class="hint">
            Si lo dejas vacío, se asignará automáticamente al momento de publicar.
          </p>
        </div>

        <div class="card-section">
          <label class="lbl">Destacado (para Home)</label>
          <label class="toggle-row">
            <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $item->is_featured ?? false))>
            <span>Mostrar en secciones destacadas</span>
          </label>
        </div>
      </div>

      <div class="side-card">
        <h3 class="side-title">Ayuda para Mercado Libre</h3>

        <div class="card-section">
          <label class="lbl">Marca (texto para ML)</label>
          <input name="brand_name" class="inp"
                 placeholder="Ejemplo: Bic, Azor, Maped"
                 value="{{ old('brand_name', $item->brand_name ?? '') }}">
          <p class="hint">
            Se envía al atributo <strong>BRAND</strong>.
          </p>
        </div>

        <div class="card-section">
          <label class="lbl">Modelo (texto para ML)</label>
          <input name="model_name" class="inp"
                 placeholder="Ejemplo: Cristal 1.0mm, Office Pro"
                 value="{{ old('model_name', $item->model_name ?? '') }}">
          <p class="hint">
            Se envía al atributo <strong>MODEL</strong>.
          </p>
        </div>

        <div class="card-section">
          <label class="lbl">GTIN / Código de barras</label>
          <input name="meli_gtin" class="inp"
                 placeholder="Ejemplo: 7501035910107"
                 value="{{ old('meli_gtin', $item->meli_gtin ?? '') }}">
          <p class="hint">
            En varias categorías de Mercado Libre es obligatorio.
          </p>
        </div>

        <div class="ml-tips">
          <p class="hint-title">Tips para evitar errores al publicar en Mercado Libre:</p>
          <ul class="hint-list">
            <li>Incluye tipo, marca y modelo en el título.</li>
            <li>Verifica que el precio cumpla con el mínimo.</li>
            <li>Con estas 3 fotos ya cumples “mínimo imágenes” del catálogo.</li>
            <li>Completa el GTIN cuando sea obligatorio.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <hr class="divi">

  <div class="form-actions">
    <button class="btn btn-primary" type="submit">
      {{ $isEdit ? 'Guardar cambios' : 'Crear producto' }}
    </button>
    <a class="btn btn-ghost" href="{{ route('admin.catalog.index') }}">Cancelar</a>
  </div>
</form>

{{-- ✅ IMPORTANTE: acciones de publicación van FUERA del form principal (evita forms anidados) --}}
@if($isEdit)
  <div class="side-card" style="margin-top:16px;">
    <h3 class="side-title">Acciones de publicación</h3>

    <div class="pub-grid">
      {{-- Mercado Libre --}}
      <div class="pub-block">
        <div class="pub-head">
          <div class="pub-title">Mercado Libre</div>
          <div class="pub-sub">Publica, pausa o abre la publicación.</div>
        </div>

        <div class="pub-actions">
          <form method="POST" action="{{ route('admin.catalog.meli.publish', $item) }}">
            @csrf
            <button type="submit" class="btn btn-pill btn-ml">
              <span class="i material-symbols-outlined" aria-hidden="true">cloud_upload</span>
              Publicar / Actualizar
            </button>
          </form>

          <div class="pub-row">
            <form method="POST" action="{{ route('admin.catalog.meli.pause', $item) }}">
              @csrf
              <button type="submit" class="btn btn-pill btn-soft btn-ml-soft">
                <span class="i material-symbols-outlined" aria-hidden="true">pause_circle</span>
                Pausar
              </button>
            </form>

            <form method="POST" action="{{ route('admin.catalog.meli.activate', $item) }}">
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

        <p class="hint pub-hint">
          Mercado Libre usa tu marca/modelo/GTIN para atributos.
        </p>
      </div>

      {{-- Amazon --}}
      <div class="pub-block">
        <div class="pub-head">
          <div class="pub-title">Amazon (SP-API)</div>
          <div class="pub-sub">Envía solicitud de listing por SKU.</div>
        </div>

        @if(!$hasSku)
          <div class="pub-warn">
            <span class="material-symbols-outlined" aria-hidden="true">info</span>
            <div>
              <div class="pub-warn-title">Falta SKU</div>
              <div class="pub-warn-text">Para publicar en Amazon necesitas guardar primero un SKU.</div>
            </div>
          </div>
        @endif

        <div class="pub-actions">
          <form method="POST" action="{{ route('admin.catalog.amazon.publish', $item) }}">
            @csrf
            <button type="submit" class="btn btn-pill btn-amz" @disabled(!$hasSku)>
              <span class="i material-symbols-outlined" aria-hidden="true">cloud_upload</span>
              Publicar / Actualizar
            </button>
          </form>

          <div class="pub-row">
            <form method="POST" action="{{ route('admin.catalog.amazon.pause', $item) }}">
              @csrf
              <button type="submit" class="btn btn-pill btn-soft btn-amz-soft" @disabled(!$hasSku)>
                <span class="i material-symbols-outlined" aria-hidden="true">pause_circle</span>
                Pausar
              </button>
            </form>

            <form method="POST" action="{{ route('admin.catalog.amazon.activate', $item) }}">
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

        <p class="hint pub-hint">
          Amazon requiere SKU y atributos por categoría. Si te devuelve validaciones, es normal: se ajustan por productType.
        </p>
      </div>
    </div>
  </div>
@endif

@push('styles')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400..700&display=swap"/>
<link rel="stylesheet" href="{{ asset('css/form.css') }}?v={{ time() }}">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  // ✅ Toggle clasificación interna
  document.addEventListener('DOMContentLoaded', function(){
    const chk = document.getElementById('use_internal');
    const box = document.getElementById('internal-box');
    if (chk && box){
      const sync = () => box.classList.toggle('is-disabled', !chk.checked);
      chk.addEventListener('change', sync);
      sync();
    }
  });

  // ✅ Fotos: preview + badges + botón Quitar
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
          renderPreview(preview, null);
        });
      }
    });

    window.addEventListener('beforeunload', () => {
      for (const url of objectUrls.values()) URL.revokeObjectURL(url);
    });
  });

  // ================================
  // 🔹 Config base de SweetAlert UI
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
  // 🔹 IA: subir archivos + dropzone + rellenar campos
  // ================================
  document.addEventListener('DOMContentLoaded', function () {
    const btnAi      = document.getElementById('btn-ai-analyze');
    const inputFiles = document.getElementById('ai_files');
    const statusEl   = document.getElementById('ai-helper-status');
    const helperBox  = document.getElementById('ai-helper');

    const panel      = document.getElementById('ai-items-panel');
    const tbody      = document.getElementById('ai-items-tbody');
    const countEl    = document.getElementById('ai-items-count');

    const dropzone   = document.getElementById('ai-dropzone');
    const filesList  = document.getElementById('ai-files-list');
    const clearBtn   = document.getElementById('ai-clear-list');

    const LS_KEY_ITEMS = 'catalog_ai_items';
    const LS_KEY_INDEX = 'catalog_ai_index';

    let aiItems = [];

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

    function refreshFileChips(files) {
      if (!filesList) return;
      filesList.innerHTML = '';
      if (!files || !files.length) return;

      Array.from(files).forEach(file => {
        const chip = document.createElement('div');
        chip.className = 'ai-file-chip';
        chip.innerHTML = `<span>${file.name}</span>`;
        filesList.appendChild(chip);
      });
    }

    if (dropzone && inputFiles) {
      dropzone.addEventListener('click', function (e) {
        if (e.target.closest('.ai-dropzone-btn')) e.preventDefault();
        inputFiles.click();
      });

      inputFiles.addEventListener('change', function () {
        refreshFileChips(inputFiles.files);
      });

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

    if (clearBtn) {
      clearBtn.addEventListener('click', function () {
        aiItems = [];
        try {
          localStorage.removeItem(LS_KEY_ITEMS);
          localStorage.removeItem(LS_KEY_INDEX);
        } catch (e) {}
        if (tbody) tbody.innerHTML = '';
        if (panel) panel.style.display = 'none';
        if (filesList) filesList.innerHTML = '';
        if (inputFiles) inputFiles.value = '';
        if (statusEl) statusEl.textContent = 'Se limpió la lista de productos IA. Puedes subir un nuevo PDF o imágenes.';
        AiAlerts.info('Lista limpia', 'La lista de productos sugeridos por IA se reinició.');
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
          fillFromItem(item, { markSuggested: true });
          if (statusEl) statusEl.textContent = 'Se cargó el producto #' + (i + 1) + ' desde la lista IA. Revisa y ajusta antes de guardar.';
          AiAlerts.info('Producto cargado', 'Se llenó el formulario con el producto #' + (i + 1) + '.');
        });
      });
    }

    function renderAiTable() {
      if (!tbody || !panel) return;
      tbody.innerHTML = '';

      aiItems.forEach((item, idx) => {
        const tr = document.createElement('tr');
        const precio = item.price != null && item.price !== ''
          ? '$ ' + Number(item.price).toFixed(2)
          : '—';

        tr.innerHTML = `
          <td>${idx + 1}</td>
          <td>${escapeHtml(item.name || '')}</td>
          <td>${precio}</td>
          <td>${escapeHtml(item.brand_name || '')}</td>
          <td>${escapeHtml(item.model_name || '')}</td>
          <td>${escapeHtml(item.meli_gtin || '')}</td>
          <td>
            <button type="button" class="btn btn-ghost btn-xs" data-ai-index="${idx}">Usar este</button>
          </td>
        `;
        tbody.appendChild(tr);
      });

      if (countEl) {
        countEl.textContent = aiItems.length === 1 ? '1 producto detectado' : (aiItems.length + ' productos detectados');
      }

      panel.style.display = aiItems.length ? 'block' : 'none';
      attachUseButtons();
    }

    aiItems = loadAiItemsFromStorage();
    if (aiItems.length) {
      renderAiTable();
      if (statusEl) statusEl.textContent = 'Se restauraron los productos detectados por IA. Puedes seguir capturando sin volver a subir el PDF.';
      const idx = loadAiIndexFromStorage();
      const item = aiItems[idx] || aiItems[0];
      if (item) fillFromItem(item, { markSuggested: true });
    }

    if (!btnAi || !inputFiles) return;

    btnAi.addEventListener('click', function () {
      if (!inputFiles.files || !inputFiles.files.length) {
        AiAlerts.info('Sube un archivo', 'Necesito al menos una imagen o PDF para analizar con IA.');
        if (statusEl) statusEl.textContent = 'Sube un archivo para que la IA pueda analizarlo.';
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
      if (statusEl) statusEl.textContent = 'Enviando archivos a la IA, esto puede tardar unos segundos...';

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
          if (statusEl) statusEl.textContent = 'Error: ' + (data.error || 'No fue posible obtener sugerencias.');
          AiAlerts.error('Error al analizar con IA', data.error || 'No fue posible obtener sugerencias.');
          return;
        }

        const s = data.suggestions || {};
        fillFromItem(s, { markSuggested: true });

        aiItems = Array.isArray(data.items) ? data.items : [];
        saveAiItemsToStorage();
        saveAiIndexToStorage(0);

        if (aiItems.length) renderAiTable();

        if (statusEl) statusEl.textContent = 'Listo: revisa y ajusta las sugerencias marcadas en verde antes de guardar.';
        AiAlerts.success('Sugerencias listas', 'La IA completó los campos principales del producto.');
      })
      .catch(err => {
        console.error(err);
        if (statusEl) statusEl.textContent = 'Ocurrió un error al llamar a la IA.';
        AiAlerts.error('Error de conexión', 'Ocurrió un problema al contactar la IA.');
      })
      .finally(() => {
        btnAi.disabled = false;
        if (labelEl) labelEl.textContent = originalText;
        else btnAi.textContent = originalText;
        if (helperBox) helperBox.classList.remove('ai-busy');
      });
    });

    function applyAiSuggestion(fieldName, value, markSuggested) {
      if (value === undefined || value === null || value === '') return;
      const el = document.querySelector('[name="' + fieldName + '"]');
      if (!el) return;

      el.value = value;
      if (markSuggested) {
        el.classList.add('ai-suggested');
        setTimeout(() => el.classList.remove('ai-suggested'), 7000);
      }
    }

    function fillFromItem(item, opts = {}) {
      const markSuggested = !!opts.markSuggested;
      if (!item || typeof item !== 'object') return;

      applyAiSuggestion('name',        item.name,        markSuggested);
      applyAiSuggestion('slug',        item.slug,        markSuggested);
      applyAiSuggestion('description', item.description, markSuggested);
      applyAiSuggestion('excerpt',     item.excerpt,     markSuggested);
      applyAiSuggestion('price',       item.price,       markSuggested);
      applyAiSuggestion('brand_name',  item.brand_name,  markSuggested);
      applyAiSuggestion('model_name',  item.model_name,  markSuggested);
      applyAiSuggestion('meli_gtin',   item.meli_gtin,   markSuggested);

      const qty = item.stock ?? item.quantity ?? item.qty ?? item.cantidad;
      applyAiSuggestion('stock', qty, markSuggested);
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
