@php
  /** @var \App\Models\CatalogItem|null $item */
  $isEdit = isset($item);

  // Para labels en edit: si ya existen fotos guardadas
  $has1 = !empty($item->photo_1 ?? null);
  $has2 = !empty($item->photo_2 ?? null);
  $has3 = !empty($item->photo_3 ?? null);

  // Categorías legibles (papelería, cómputo, etc.)
  $categories = $categories ?? config('catalog.product_categories', []);

  // ✅ Amazon: requiere AMAZON SKU real (seller sku), NO el sku genérico
  $hasAmazonSku = !empty($item->amazon_sku ?? null);
@endphp

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
        <label class="lbl">SKU (interno)</label>
        <input name="sku" class="inp"
               placeholder="Código interno o del proveedor"
               value="{{ old('sku', $item->sku ?? '') }}">
        <p class="hint">
          Este SKU es tu referencia interna. Para Amazon usa el campo “AMAZON SKU”.
        </p>
      </div>

      {{-- ✅ AMAZON SKU (Seller SKU real) --}}
      <div class="card-section">
        <label class="lbl">AMAZON SKU (Seller SKU real)</label>
        <input name="amazon_sku" class="inp"
               placeholder="Ejemplo: JUR-1234-AZUL (tal cual en Seller Central)"
               value="{{ old('amazon_sku', $item->amazon_sku ?? '') }}">
        <p class="hint">
          Debe coincidir EXACTO con tu Seller SKU en Seller Central (Marketplace MX). Sin esto, Amazon no publica.
        </p>
      </div>

      {{-- (Opcional pero útil) ASIN / productType --}}
      <div class="card-section card-inline">
        <div class="card-inline-item">
          <label class="lbl">ASIN (opcional)</label>
          <input name="amazon_asin" class="inp"
                 placeholder="Ej. B0XXXXXXX"
                 value="{{ old('amazon_asin', $item->amazon_asin ?? '') }}">
          <p class="hint">Si ya existe el ASIN, ayuda para abrir el link directo.</p>
        </div>

        <div class="card-inline-item">
          <label class="lbl">productType (opcional)</label>
          <input name="amazon_product_type" class="inp"
                 placeholder="Ej. OFFICE_PRODUCTS"
                 value="{{ old('amazon_product_type', $item->amazon_product_type ?? '') }}">
          <p class="hint">Si Amazon te valida, este campo ayuda a corregir.</p>
        </div>
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
            Unidades disponibles. La IA puede sugerir la cantidad según el documento.
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

    {{-- =========================================================
       🔹 ACCIONES: MERCADO LIBRE + AMAZON
       ========================================================= --}}
    @if($isEdit)
      <div class="side-card">
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

                <a class="btn btn-pill btn-soft btn-ml-soft" href="{{ route('admin.catalog.meli.view', $item) }}" target="_blank" rel="noopener">
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
              <div class="pub-sub">Envía solicitud de listing por AMAZON SKU.</div>
            </div>

            @if(!$hasAmazonSku)
              <div class="pub-warn">
                <span class="material-symbols-outlined" aria-hidden="true">info</span>
                <div>
                  <div class="pub-warn-title">Falta AMAZON SKU</div>
                  <div class="pub-warn-text">Captura el Seller SKU real de Amazon y guarda el producto antes de publicar.</div>
                </div>
              </div>
            @endif

            <div class="pub-actions">
              <form method="POST" action="{{ route('admin.catalog.amazon.publish', $item) }}">
                @csrf
                <button type="submit" class="btn btn-pill btn-amz" @disabled(!$hasAmazonSku)>
                  <span class="i material-symbols-outlined" aria-hidden="true">cloud_upload</span>
                  Publicar / Actualizar
                </button>
              </form>

              <div class="pub-row">
                <form method="POST" action="{{ route('admin.catalog.amazon.pause', $item) }}">
                  @csrf
                  <button type="submit" class="btn btn-pill btn-soft btn-amz-soft" @disabled(!$hasAmazonSku)>
                    <span class="i material-symbols-outlined" aria-hidden="true">pause_circle</span>
                    Pausar
                  </button>
                </form>

                <form method="POST" action="{{ route('admin.catalog.amazon.activate', $item) }}">
                  @csrf
                  <button type="submit" class="btn btn-pill btn-soft btn-amz-soft" @disabled(!$hasAmazonSku)>
                    <span class="i material-symbols-outlined" aria-hidden="true">play_circle</span>
                    Activar
                  </button>
                </form>

                <a class="btn btn-pill btn-soft btn-amz-soft"
                   href="{{ route('admin.catalog.amazon.view', $item) }}"
                   target="_blank" rel="noopener"
                   @if(!$hasAmazonSku) aria-disabled="true" onclick="return false;" @endif>
                  <span class="i material-symbols-outlined" aria-hidden="true">open_in_new</span>
                  Ver
                </a>
              </div>
            </div>

            <p class="hint pub-hint">
              Amazon requiere AMAZON SKU y atributos por categoría. Si te devuelve validaciones, es normal: se ajustan por productType.
            </p>
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
    --brand-soft:#eff6ff;
    --accent:#22c55e;
    --shadow-soft:0 18px 40px rgba(15,23,42,.06);
    --radius-lg:18px;
    --radius-md:12px;

    --ml:#10b981;
    --ml-soft: rgba(16,185,129,.14);

    --amz:#f59e0b;
    --amz-soft: rgba(245,158,11,.16);
  }

  .lbl{
    display:block;
    font-weight:700;
    color:var(--ink);
    margin:10px 0 4px;
    font-size:.9rem;
  }

  .inp{
    width:100%;
    background:#f8fafc;
    border:1px solid var(--line);
    border-radius:var(--radius-md);
    padding:10px 12px;
    min-height:42px;
    font-size:.92rem;
    color:#0f172a;
    transition:border-color .15s ease, box-shadow .15s ease, background .15s ease, transform .08s ease;
  }
  .inp:focus{
    outline:none;
    border-color:#93c5fd;
    box-shadow:0 0 0 1px #bfdbfe;
    background:#ffffff;
    transform:translateY(-1px);
  }

  .btn{
    border:0;
    border-radius:999px;
    padding:10px 16px;
    font-weight:600;
    cursor:pointer;
    font-size:.9rem;
    display:inline-flex;
    align-items:center;
    gap:6px;
    transition:transform .12s ease, box-shadow .12s ease, background .15s ease, border-color .15s ease, opacity .15s ease;
    white-space:nowrap;
    text-decoration:none;
  }
  .btn[disabled], .btn[aria-disabled="true"]{
    opacity:.5;
    cursor:not-allowed;
    pointer-events:none;
  }

  .btn-primary{
    background:var(--brand);
    color:#eff6ff;
    box-shadow:0 12px 30px rgba(37,99,235,.35);
  }
  .btn-primary:hover{
    transform:translateY(-1px);
    box-shadow:0 16px 32px rgba(37,99,235,.35);
  }
  .btn-ghost{
    background:#ffffff;
    border:1px solid var(--line);
    color:#0f172a;
  }
  .btn-ghost:hover{
    transform:translateY(-1px);
    box-shadow:0 8px 20px rgba(15,23,42,.06);
  }

  .btn-xs{ padding:5px 10px; font-size:.75rem; }

  .divi{
    border:none;
    border-top:1px dashed #e5e7eb;
    margin:18px 0;
  }

  .hint{ margin:4px 0 0; font-size:.78rem; color:var(--muted); }
  .card-section{ margin-bottom:12px; }

  .catalog-grid{
    display:grid;
    gap:18px;
    grid-template-columns:repeat(12,1fr);
  }
  .catalog-main{ grid-column:span 8; display:flex; flex-direction:column; gap:12px; }
  .catalog-side{ grid-column:span 4; display:flex; flex-direction:column; gap:12px; }

  .side-card{
    background:#f9fafb;
    border-radius:var(--radius-lg);
    border:1px solid var(--line);
    padding:12px 14px;
    box-shadow:0 10px 25px rgba(15,23,42,.03);
  }
  .side-title{
    margin:0 0 8px;
    font-size:.9rem;
    font-weight:700;
    color:#0f172a;
  }

  .ml-tips{
    margin-top:6px;
    padding-top:8px;
    border-top:1px dashed #e5e7eb;
  }
  .hint-title{
    margin:0 0 4px;
    font-size:.8rem;
    font-weight:700;
    color:#111827;
  }
  .hint-list{
    margin:0 0 0 16px;
    padding:0;
    font-size:.78rem;
    color:#4b5563;
  }

  .card-inline{ display:flex; gap:10px; flex-wrap:wrap; }
  .card-inline-item{ flex:1 1 140px; }

  .toggle-row{
    display:flex;
    gap:8px;
    align-items:center;
    font-size:.9rem;
    color:#4b5563;
  }

  .form-actions{
    margin-top:18px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    justify-content:flex-end;
  }

  /* ✅ Bloque interno disabled */
  #internal-box.is-disabled{
    opacity:.55;
    filter:grayscale(.2);
    pointer-events:none;
  }

  /* =========================================================
     🔹 FOTOS 3-UP
     ========================================================= */
  .photos-grid{
    display:grid;
    grid-template-columns: repeat(3, 1fr);
    gap:10px;
    margin-top:10px;
  }
  .photo-card{
    background:#ffffff;
    border:1px solid var(--line);
    border-radius:16px;
    padding:10px;
    transition:border-color .15s ease, box-shadow .15s ease, background .15s ease;
  }
  .photo-card.is-filled{
    border:1px solid rgba(34,197,94,.35);
    box-shadow:0 12px 26px rgba(22,163,74,.08);
  }
  .photo-card.is-filled .photo-drop{
    border-color: rgba(34,197,94,.55);
    background:linear-gradient(135deg, rgba(240,253,244,.95), #ffffff);
  }
  .photo-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    margin-bottom:8px;
  }
  .photo-title{
    font-weight:800;
    color:#0f172a;
    font-size:.86rem;
  }
  .photo-badge{
    font-size:.72rem;
    padding:3px 8px;
    border-radius:999px;
    border:1px solid var(--line);
    background:#f8fafc;
    color:#334155;
    font-weight:800;
    white-space:nowrap;
  }
  .photo-badge.ok{
    border-color:#bbf7d0;
    background:#dcfce7;
    color:#15803d;
  }

  .photo-drop{
    position:relative;
    border:1.5px dashed rgba(148,163,184,.95);
    border-radius:14px;
    padding:10px;
    display:flex;
    gap:10px;
    align-items:center;
    background:linear-gradient(135deg, rgba(239,246,255,.9), #ffffff);
    cursor:pointer;
    transition:transform .12s ease, box-shadow .15s ease, border-color .15s ease;
    min-height:62px;
  }
  .photo-drop:hover{
    border-color:#60a5fa;
    box-shadow:0 10px 25px rgba(37,99,235,.14);
    transform:translateY(-1px);
  }
  .photo-icon{
    width:38px; height:38px;
    border-radius:999px;
    background:#1d4ed8;
    color:#e0f2fe;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 12px 24px rgba(30,64,175,.55);
    flex:0 0 auto;
    font-size:1.1rem;
  }
  .photo-strong{
    font-weight:900;
    color:#0f172a;
    font-size:.85rem;
    margin-bottom:2px;
  }
  .photo-sub{
    color:var(--muted);
    font-size:.75rem;
    font-weight:700;
  }
  .photo-actions{
    display:flex;
    justify-content:flex-end;
    margin-top:8px;
  }

  .photo-input{ display:none; }

  .photo-preview{
    margin-top:8px;
    border-radius:14px;
    overflow:hidden;
    border:1px solid var(--line);
    background:#f1f5f9;
    aspect-ratio: 4/3;
    display:flex;
    align-items:center;
    justify-content:center;
  }
  .photo-preview img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
  }

  /* =========================================================
     🔹 Acciones publicación ML / Amazon
     ========================================================= */
  .pub-grid{
    display:grid;
    grid-template-columns:1fr;
    gap:12px;
    margin-top:10px;
  }

  .pub-block{
    background:#ffffff;
    border:1px solid var(--line);
    border-radius:16px;
    padding:12px;
    box-shadow:0 10px 24px rgba(15,23,42,.03);
  }

  .pub-head{ display:flex; flex-direction:column; gap:2px; margin-bottom:8px; }
  .pub-title{ font-weight:800; color:#0f172a; font-size:.9rem; }
  .pub-sub{ color:#64748b; font-size:.78rem; }

  .pub-actions{ display:flex; flex-direction:column; gap:8px; }
  .pub-row{ display:flex; gap:8px; flex-wrap:wrap; }

  .btn-pill{
    width:100%;
    justify-content:center;
    padding:9px 14px;
    font-size:.84rem;
    border-radius:999px;
  }
  .btn-soft{
    width:auto;
    padding:7px 12px;
    font-size:.78rem;
  }

  .i.material-symbols-outlined{
    font-size:18px;
    line-height:1;
  }

  /* ML */
  .btn-ml{
    background:linear-gradient(135deg, rgba(16,185,129,.18), rgba(16,185,129,.08));
    color:#065f46;
    border:1px solid rgba(16,185,129,.35);
    box-shadow:0 14px 28px rgba(16,185,129,.14);
  }
  .btn-ml:hover{
    transform:translateY(-1px);
    box-shadow:0 18px 34px rgba(16,185,129,.18);
  }

  .btn-ml-soft{
    background:rgba(16,185,129,.10);
    color:#065f46;
    border:1px solid rgba(16,185,129,.25);
  }
  .btn-ml-soft:hover{
    transform:translateY(-1px);
    box-shadow:0 12px 26px rgba(16,185,129,.14);
  }

  /* Amazon */
  .btn-amz{
    background:linear-gradient(135deg, rgba(245,158,11,.22), rgba(245,158,11,.10));
    color:#92400e;
    border:1px solid rgba(245,158,11,.35);
    box-shadow:0 14px 28px rgba(245,158,11,.14);
  }
  .btn-amz:hover{
    transform:translateY(-1px);
    box-shadow:0 18px 34px rgba(245,158,11,.18);
  }

  .btn-amz-soft{
    background:rgba(245,158,11,.12);
    color:#92400e;
    border:1px solid rgba(245,158,11,.25);
  }
  .btn-amz-soft:hover{
    transform:translateY(-1px);
    box-shadow:0 12px 26px rgba(245,158,11,.14);
  }

  .pub-hint{ margin-top:10px; }

  .pub-warn{
    display:flex;
    gap:10px;
    align-items:flex-start;
    padding:10px 10px;
    border-radius:14px;
    border:1px dashed rgba(245,158,11,.45);
    background:linear-gradient(135deg, rgba(255,251,235,.9), #ffffff);
    color:#92400e;
    margin-bottom:10px;
  }
  .pub-warn .material-symbols-outlined{ font-size:20px; }
  .pub-warn-title{ font-weight:800; font-size:.82rem; }
  .pub-warn-text{ font-size:.76rem; color:#92400e; opacity:.9; }

  /* =========================================================
     🔹 Estilos IA (panel principal) + tabla
     ========================================================= */
  .ai-helper{
    margin-bottom:18px;
    padding:14px 16px;
    background:radial-gradient(circle at top left, #dbeafe 0, #eff6ff 25%, #ffffff 80%);
    border-radius:22px;
    border:1px solid #dbeafe;
    display:flex;
    gap:12px;
    align-items:flex-start;
    flex-wrap:wrap;
    position:relative;
    overflow:hidden;
  }
  .ai-helper::before{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(120deg, rgba(59,130,246,.12), transparent 40%, transparent 60%, rgba(59,130,246,.12));
    opacity:0;
    pointer-events:none;
    transition:opacity .25s ease;
  }
  .ai-helper.ai-busy::before{
    opacity:1;
    animation:aiSweep 2.2s linear infinite;
  }

  .ai-helper-icon-wrapper{ position:relative; width:46px; height:46px; flex:0 0 auto; }
  .ai-helper-glow{
    position:absolute; inset:0; border-radius:999px;
    background:radial-gradient(circle, rgba(59,130,246,.28), transparent 60%);
    opacity:.85; filter:blur(6px);
  }
  .ai-helper-icon{
    position:relative;
    width:46px; height:46px;
    border-radius:999px;
    background:#1d4ed8;
    display:flex; align-items:center; justify-content:center;
    font-size:1.5rem;
    box-shadow:0 14px 30px rgba(30,64,175,.55);
    transition:transform .2s ease, box-shadow .2s ease, background .2s ease;
  }
  .ai-helper.ai-busy .ai-helper-icon{
    box-shadow:0 20px 40px rgba(30,64,175,.6);
    animation:aiBob 1.1s ease-in-out infinite;
  }

  .ai-helper-main{ flex:1 1 260px; position:relative; z-index:1; }
  .ai-helper-header{ display:flex; justify-content:space-between; gap:8px; align-items:flex-start; margin-bottom:4px; }
  .ai-helper-title{ font-size:.95rem; font-weight:700; color:#0f172a; }
  .ai-helper-subtitle{ margin:0; font-size:.8rem; color:#475569; }
  .ai-helper-chip{
    align-self:flex-start;
    font-size:.7rem; padding:3px 9px; border-radius:999px;
    background:rgba(236,252,203,.9); color:#4d7c0f; font-weight:600;
  }
  .ai-helper-text{ margin:6px 0 10px; font-size:.8rem; color:#334155; }
  .ai-helper-row{ display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; }
  .ai-helper-input{ flex:1 1 260px; }
  .ai-helper-actions{ display:flex; flex-direction:column; gap:4px; align-items:flex-start; }
  .ai-helper-status{ min-height:18px; }

  .ai-cta{ position:relative; overflow:hidden; }
  .ai-cta-spinner{
    width:16px;height:16px;border-radius:999px;
    border:2px solid rgba(191,219,254,.7);
    border-top-color:#eff6ff;
    opacity:0; transform:scale(.6);
    transition:opacity .15s ease, transform .15s ease;
  }
  .ai-helper.ai-busy .ai-cta-spinner{
    opacity:1; transform:scale(1); animation:aiSpin .8s linear infinite;
  }

  .ai-dropzone{
    position:relative;
    border-radius:16px;
    border:1.5px dashed rgba(148,163,184,.9);
    background:linear-gradient(135deg, rgba(239,246,255,.9), #ffffff);
    padding:10px 12px;
    display:flex; align-items:center; gap:10px;
    cursor:pointer;
    transition:border-color .18s ease, background .18s ease, box-shadow .18s ease, transform .1s ease;
  }
  .ai-dropzone:hover{
    border-color:#60a5fa;
    box-shadow:0 10px 25px rgba(37,99,235,.16);
    transform:translateY(-1px);
  }
  .ai-dropzone.is-dragover{
    border-color:#2563eb;
    background:radial-gradient(circle at top left,#dbeafe 0,#eff6ff 45%,#ffffff 100%);
    box-shadow:0 14px 32px rgba(37,99,235,.25);
  }
  .ai-dropzone-icon{
    width:36px;height:36px;border-radius:999px;
    background:#1d4ed8; display:flex; align-items:center; justify-content:center;
    font-size:1.2rem; color:#e0f2fe;
    box-shadow:0 12px 24px rgba(30,64,175,.55);
    flex:0 0 auto;
  }
  .ai-dropzone-body{ display:flex; flex-direction:column; gap:2px; }
  .ai-dropzone-title{ font-size:.86rem; font-weight:600; color:#0f172a; }
  .ai-dropzone-sub{ font-size:.8rem; color:#475569; }
  .ai-dropzone-btn{
    border:0; border-radius:999px; padding:4px 10px;
    font-size:.78rem; font-weight:600; margin-left:4px;
    background:#0f172a; color:#f9fafb; cursor:pointer;
  }
  .ai-dropzone-hint{ font-size:.75rem; color:#6b7280; }
  .ai-dropzone-input{ position:absolute; inset:0; opacity:0; cursor:pointer; }

  .ai-files-list{ margin-top:6px; display:flex; flex-wrap:wrap; gap:6px; }
  .ai-file-chip{
    display:inline-flex; align-items:center; gap:6px; padding:3px 8px;
    font-size:.75rem; border-radius:999px;
    background:#eff6ff; color:#1e293b; border:1px solid #dbeafe;
    max-width:100%;
  }
  .ai-file-chip span{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:180px; }

  .ai-items-panel{
    margin-bottom:18px;
    padding:12px 14px;
    background:#f9fafb;
    border-radius:18px;
    border:1px solid #e5e7eb;
    box-shadow:0 14px 30px rgba(15,23,42,.03);
    animation:fadeInUp .25s ease-out;
  }
  .ai-items-header{ display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:10px; }
  .ai-items-header-right{ display:flex; align-items:center; gap:8px; }
  .ai-items-title{ font-size:.9rem; font-weight:700; color:#0f172a; }
  .ai-items-text{ margin:2px 0 0; font-size:.8rem; color:#4b5563; }
  .ai-items-badge{
    align-self:flex-start; font-size:.75rem; padding:3px 8px; border-radius:999px;
    background:#dcfce7; color:#15803d; font-weight:600; white-space:nowrap;
  }
  .ai-items-table-wrapper{ width:100%; overflow:auto; }
  .ai-items-table{ width:100%; border-collapse:collapse; font-size:.8rem; }
  .ai-items-table thead{ background:#eff6ff; }
  .ai-items-table th, .ai-items-table td{
    padding:6px 8px; border-bottom:1px solid #e5e7eb; text-align:left; vertical-align:top;
  }
  .ai-items-table th{ font-weight:700; color:#0f172a; white-space:nowrap; }
  .ai-items-table td{ color:#4b5563; }
  .ai-items-table tr:hover{ background:#f8fafc; }

  .ai-suggested{
    border-color:rgba(34,197,94,.9) !important;
    box-shadow:0 0 0 1px rgba(34,197,94,.4), 0 10px 25px rgba(22,163,74,.12);
    background:#f0fdf4;
  }

  @keyframes aiSpin{ to{ transform:rotate(360deg); } }
  @keyframes aiBob{ 0%,100%{ transform:translateY(0); } 50%{ transform:translateY(-3px); } }
  @keyframes aiSweep{
    0%{ transform:translateX(-30%); opacity:.2; }
    50%{ opacity:.5; }
    100%{ transform:translateX(30%); opacity:.2; }
  }
  @keyframes fadeInUp{
    from{ opacity:0; transform:translateY(6px); }
    to{ opacity:1; transform:translateY(0); }
  }

  @media (max-width: 992px){
    .catalog-grid{ grid-template-columns:1fr; }
    .catalog-main, .catalog-side{ grid-column:span 12; }
    .photos-grid{ grid-template-columns:1fr; }
  }

  @media (max-width: 768px){
    .ai-items-table th:nth-child(3), .ai-items-table td:nth-child(3),
    .ai-items-table th:nth-child(5), .ai-items-table td:nth-child(5){
      display:none;
    }
  }

  /* SweetAlert */
  .swal2-popup-compact{
    border-radius:18px !important;
    padding:12px 16px !important;
    box-shadow:0 18px 40px rgba(15,23,42,.18) !important;
    backdrop-filter:blur(16px);
    background:radial-gradient(circle at top left,#eff6ff 0,#ffffff 60%) !important;
    border:1px solid rgba(148,163,184,.35);
    font-family:system-ui,-apple-system,"Segoe UI","Helvetica Neue",Arial,sans-serif;
  }
  .swal2-title{ font-size:.9rem !important; font-weight:700 !important; color:#0f172a !important; }
  .swal2-html-container{ font-size:.8rem !important; color:#4b5563 !important; margin-top:4px !important; }
  .swal2-icon{ margin:0 0 8px 0 !important; }
  .swal2-actions{ margin-top:10px !important; }
  .swal2-styled.swal2-confirm{
    border-radius:999px !important;
    padding:7px 16px !important;
    font-size:.78rem !important;
    font-weight:600 !important;
    background:#2563eb !important;
  }
</style>
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

      if (!file) {
        prev.innerHTML = '';
        return;
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
  // 🔹 IA: subir archivos + dropzone + rellenar campos (MEJORADO)
  // - No pisa campos si ya tienen valor
  // - Acepta alias (seller_sku, productType, etc.)
  // - Sugiere category (select) y productType si faltan
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

    // ✅ keys reales del select de categoría para sugerir correctamente
    const CATEGORY_KEYS = @json(array_keys($categories ?? []));

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
        chip.innerHTML = `<span>${escapeHtml(file.name)}</span>`;
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

    // ----------------------------
    // Helpers: normalización + alias
    // ----------------------------
    function norm(s){ return String(s ?? '').trim(); }
    function lower(s){ return norm(s).toLowerCase(); }

    function pick(obj, keys){
      for (const k of keys){
        const v = obj?.[k];
        if (v !== undefined && v !== null && String(v).trim() !== '') return v;
      }
      return null;
    }

    function guessProductType(text){
      const t = lower(text);
      if (!t) return '';
      if (t.includes('clip') || t.includes('grapa') || t.includes('engrap') || t.includes('papel') || t.includes('oficina')) return 'OFFICE_PRODUCTS';
      if (t.includes('lapic') || t.includes('pluma') || t.includes('bolig') || t.includes('marcador')) return 'OFFICE_PRODUCTS';
      if (t.includes('cable') || t.includes('usb') || t.includes('cargador') || t.includes('comput')) return 'ELECTRONICS';
      return '';
    }

    function guessCategoryKey(text){
      const t = lower(text);
      if (!t) return '';

      let want = '';
      if (t.includes('clip') || t.includes('grapa') || t.includes('papel') || t.includes('oficina')) want = 'papel';
      if (t.includes('usb') || t.includes('cable') || t.includes('cargador') || t.includes('comput')) want = 'comput';

      if (!want) return '';

      const found = (CATEGORY_KEYS || []).find(k => lower(k).includes(want));
      return found || '';
    }

    // ----------------------------
    // Tabla (mejorada con alias)
    // ----------------------------
    function attachUseButtons() {
      if (!tbody) return;
      tbody.querySelectorAll('button[data-ai-index]').forEach(btn => {
        btn.addEventListener('click', function () {
          const i = parseInt(this.getAttribute('data-ai-index'), 10);
          const item = aiItems[i];
          if (!item) return;
          saveAiIndexToStorage(i);

          // ✅ No pisa campos llenos
          fillFromItem(item, { markSuggested: true, onlyIfEmpty: true });

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

        const price = pick(item, ['price','unit_price','precio','precio_unitario']);
        const precio = (price != null && price !== '') ? ('$ ' + Number(price).toFixed(2)) : '—';

        const name  = pick(item, ['name','title','descripcion','description']) || '';
        const brand = pick(item, ['brand_name','brand','marca']) || '';
        const model = pick(item, ['model_name','model','modelo']) || '';
        const gtin  = pick(item, ['meli_gtin','gtin','ean','upc','barcode','codigo_barras']) || '';

        tr.innerHTML = `
          <td>${idx + 1}</td>
          <td>${escapeHtml(name)}</td>
          <td>${escapeHtml(precio)}</td>
          <td>${escapeHtml(brand)}</td>
          <td>${escapeHtml(model)}</td>
          <td>${escapeHtml(gtin)}</td>
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

    // Restore
    aiItems = loadAiItemsFromStorage();
    if (aiItems.length) {
      renderAiTable();
      if (statusEl) statusEl.textContent = 'Se restauraron los productos detectados por IA. Puedes seguir capturando sin volver a subir el PDF.';
      const idx = loadAiIndexFromStorage();
      const item = aiItems[idx] || aiItems[0];
      if (item) fillFromItem(item, { markSuggested: true, onlyIfEmpty: true });
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

        // ✅ No pisa campos llenos
        fillFromItem(s, { markSuggested: true, onlyIfEmpty: true });

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

    function applyAiSuggestion(fieldName, value, markSuggested, onlyIfEmpty) {
      if (value === undefined || value === null || value === '') return;
      const el = document.querySelector('[name="' + fieldName + '"]');
      if (!el) return;

      if (onlyIfEmpty) {
        const current = (el.value ?? '').toString().trim();
        if (current !== '') return;
      }

      el.value = value;

      // ✅ si es select, dispara change
      try { el.dispatchEvent(new Event('change', { bubbles:true })); } catch(e){}

      if (markSuggested) {
        el.classList.add('ai-suggested');
        setTimeout(() => el.classList.remove('ai-suggested'), 7000);
      }
    }

    function fillFromItem(item, opts = {}) {
      const markSuggested = !!opts.markSuggested;
      const onlyIfEmpty   = !!opts.onlyIfEmpty;
      if (!item || typeof item !== 'object') return;

      const name = pick(item, ['name','title','descripcion','description']);
      const slug = pick(item, ['slug']);
      const desc = pick(item, ['description','descripcion_larga','desc']);
      const ex   = pick(item, ['excerpt','resumen','short_description']);
      const price= pick(item, ['price','unit_price','precio','precio_unitario']);

      const brand= pick(item, ['brand_name','brand','marca']);
      const model= pick(item, ['model_name','model','modelo']);
      const gtin = pick(item, ['meli_gtin','gtin','ean','upc','barcode','codigo_barras']);

      const amazonSku = pick(item, [
        'amazon_sku','seller_sku','sellerSku','amazonSellerSku','amazon_seller_sku','amz_sku','amzSellerSku'
      ]);
      const asin  = pick(item, ['amazon_asin','asin']);
      let ptype   = pick(item, ['amazon_product_type','productType','product_type','amz_product_type']);

      let category = pick(item, ['category','categoria','category_key','categoryKey']);

      applyAiSuggestion('name',        name,  markSuggested, onlyIfEmpty);
      applyAiSuggestion('slug',        slug,  markSuggested, onlyIfEmpty);
      applyAiSuggestion('description', desc,  markSuggested, onlyIfEmpty);
      applyAiSuggestion('excerpt',     ex,    markSuggested, onlyIfEmpty);
      applyAiSuggestion('price',       price, markSuggested, onlyIfEmpty);
      applyAiSuggestion('brand_name',  brand, markSuggested, onlyIfEmpty);
      applyAiSuggestion('model_name',  model, markSuggested, onlyIfEmpty);
      applyAiSuggestion('meli_gtin',   gtin,  markSuggested, onlyIfEmpty);

      applyAiSuggestion('amazon_sku',          amazonSku, markSuggested, onlyIfEmpty);
      applyAiSuggestion('amazon_asin',         asin,      markSuggested, onlyIfEmpty);
      applyAiSuggestion('amazon_product_type', ptype,     markSuggested, onlyIfEmpty);

      const qty = pick(item, ['stock','quantity','qty','cantidad','cant']);
      applyAiSuggestion('stock', qty, markSuggested, onlyIfEmpty);

      // ✅ Category: valida contra keys reales, si no, intenta adivinar por texto
      if (category && CATEGORY_KEYS.length && !CATEGORY_KEYS.includes(String(category))) {
        const tryKey = CATEGORY_KEYS.find(k => lower(k) === lower(category));
        category = tryKey || '';
      }

      if (!category) {
        const t = `${name || ''} ${desc || ''} ${ex || ''}`;
        category = guessCategoryKey(t);
      }

      if (category) applyAiSuggestion('category', category, markSuggested, onlyIfEmpty);

      // ✅ productType fallback si no vino
      if (!ptype) {
        const t = `${name || ''} ${desc || ''} ${ex || ''}`;
        const guess = guessProductType(t);
        if (guess) applyAiSuggestion('amazon_product_type', guess, markSuggested, onlyIfEmpty);
      }
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
