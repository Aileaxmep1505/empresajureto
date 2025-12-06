@php
  /** @var \App\Models\CatalogItem|null $item */
  $isEdit = isset($item);
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
      <span class="ai-helper-chip">
        Beta
      </span>
    </div>

    <p class="ai-helper-text">
      La IA leerá lo que vea y te sugerirá automáticamente:
      <strong>nombre, descripción, extracto, precio, marca, modelo, GTIN y cantidad (stock)</strong>.
      Siempre puedes revisar, corregir y complementar antes de guardar.
    </p>

    <div class="ai-helper-row">
      <div class="ai-helper-input">
        <label class="lbl" style="margin-top:0;">Archivos para IA</label>

        {{-- 🔹 Dropzone moderna / arrastrar y soltar --}}
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

          {{-- input real (oculto visualmente pero funcional) --}}
          <input id="ai_files"
                 name="ai_files[]"
                 type="file"
                 multiple
                 accept="image/*,.pdf"
                 class="ai-dropzone-input">
        </div>

        <div id="ai-files-list" class="ai-files-list">
          {{-- chips con archivos seleccionados --}}
        </div>

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
      <button type="button"
              id="ai-clear-list"
              class="btn btn-ghost btn-xs ai-clear-btn">
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
      <tbody id="ai-items-tbody">
        {{-- Filas generadas por JS --}}
      </tbody>
    </table>
  </div>
</div>

{{-- =========================================================
   🔹 FORMULARIO PRINCIPAL
   ========================================================= --}}
<div class="catalog-grid">
  {{-- Columna izquierda: contenido principal --}}
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
  </div>

  {{-- Columna derecha: datos comerciales + ML tips --}}
  <div class="catalog-side">
    <div class="side-card">
      <div class="card-section">
        <label class="lbl">SKU</label>
        <input name="sku" class="inp"
               placeholder="Código interno o del proveedor"
               value="{{ old('sku', $item->sku ?? '') }}">
        <p class="hint">
          Usa un SKU claro y único. Te ayuda a localizar el producto rápidamente en tu catálogo.
        </p>
      </div>

      <div class="card-section card-inline">
        <div class="card-inline-item">
          <label class="lbl">Precio *</label>
          <input name="price" type="number" step="0.01" min="0" class="inp" required
                 value="{{ old('price', $item->price ?? 0) }}">
          <p class="hint">
            Precio base en MXN. Algunas categorías de Mercado Libre requieren un mínimo (ej. desde 35 MXN).
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
      <h3 class="side-title">Datos de clasificación</h3>

      <div class="card-section">
        <label class="lbl">Marca (ID interno)</label>
        <input name="brand_id" type="number" class="inp"
               value="{{ old('brand_id', $item->brand_id ?? '') }}"
               placeholder="Opcional: ID en tu sistema de marcas">
        <p class="hint">
          Solo si manejas un catálogo de marcas interno por ID.
        </p>
      </div>

      <div class="card-section">
        <label class="lbl">Categoría (ID interno)</label>
        <input name="category_id" type="number" class="inp"
               value="{{ old('category_id', $item->category_id ?? '') }}"
               placeholder="Opcional: ID de categoría interna">
        <p class="hint">
          Se usa para tu menú / filtro de categorías en el sitio.
        </p>
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
          Este nombre se envía al atributo <strong>BRAND</strong> de Mercado Libre. Usa la marca comercial tal como la buscan tus clientes.
        </p>
      </div>

      <div class="card-section">
        <label class="lbl">Modelo (texto para ML)</label>
        <input name="model_name" class="inp"
               placeholder="Ejemplo: Cristal 1.0mm, Office Pro"
               value="{{ old('model_name', $item->model_name ?? '') }}">
        <p class="hint">
          Se envía al atributo <strong>MODEL</strong>. Si no tienes modelo, puedes dejarlo vacío y usaremos el SKU como respaldo.
        </p>
      </div>

      <div class="card-section">
        <label class="lbl">GTIN / Código de barras</label>
        <input name="meli_gtin" class="inp"
               placeholder="Ejemplo: 7501035910107"
               value="{{ old('meli_gtin', $item->meli_gtin ?? '') }}">
        <p class="hint">
          En varias categorías de Mercado Libre es obligatorio el código de barras (GTIN, EAN, UPC, etc.).
          Lo encuentras impreso junto al código de barras del producto o la caja.
        </p>
      </div>

      <div class="ml-tips">
        <p class="hint-title">Tips para evitar errores al publicar en Mercado Libre:</p>
        <ul class="hint-list">
          <li>Incluye tipo, marca y modelo en el título (evita “Lapicero” solamente).</li>
          <li>Verifica que el precio cumpla con el mínimo de la categoría.</li>
          <li>Asegúrate de tener al menos una imagen válida y accesible por URL.</li>
          <li>Completa el GTIN cuando sea obligatorio; si falta, Mercado Libre lo marcará como error.</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<hr class="divi">

{{-- =========================================================
   🔹 IMÁGENES
   ========================================================= --}}
<div class="card-section">
  <label class="lbl">Imagen de portada (URL)</label>
  <input name="image_url" class="inp"
         placeholder="https://tusitio.com/imagenes/lapicero-azul.jpg"
         value="{{ old('image_url', $item->image_url ?? '') }}">
  <p class="hint">
    Usa una imagen limpia, bien iluminada y con fondo neutro. Es la principal que verá el cliente.
  </p>
</div>

<div class="card-section">
  <label class="lbl">Imágenes adicionales (URLs)</label>
  <div id="images-list" class="images-list">
    @php
      $imgs = old('images', $item->images ?? []);
      if (!is_array($imgs)) { $imgs = []; }
    @endphp
    @forelse($imgs as $i => $url)
      <div class="img-row">
        <input name="images[{{ $i }}]" class="inp" value="{{ $url }}" placeholder="https://...">
        <button type="button" class="btn btn-ghost btn-xs" onclick="this.parentElement.remove()">Quitar</button>
      </div>
    @empty
      <div class="img-row">
        <input name="images[0]" class="inp" placeholder="https://...">
        <button type="button" class="btn btn-ghost btn-xs" onclick="this.parentElement.remove()">Quitar</button>
      </div>
    @endforelse
  </div>
  <p class="hint">
    Añade varias vistas del producto (frente, reverso, detalle, empaque). Mercado Libre recomienda buena resolución y fondo claro.
  </p>
  <button type="button" class="btn btn-ghost" onclick="addImageRow()">+ Agregar imagen</button>
</div>

<div class="form-actions">
  <button class="btn btn-primary" type="submit">
    {{ $isEdit ? 'Guardar cambios' : 'Crear producto' }}
  </button>
  <a class="btn btn-ghost" href="{{ route('admin.catalog.index') }}">Cancelar</a>
</div>

@push('styles')
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
    transition:transform .12s ease, box-shadow .12s ease, background .15s ease, border-color .15s ease;
    white-space:nowrap;
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
  .btn-xs{
    padding:5px 10px;
    font-size:.75rem;
  }

  .divi{
    border:none;
    border-top:1px dashed #e5e7eb;
    margin:18px 0;
  }

  .hint{
    margin:4px 0 0;
    font-size:.78rem;
    color:var(--muted);
  }

  .card-section{
    margin-bottom:12px;
  }

  .catalog-grid{
    display:grid;
    gap:18px;
    grid-template-columns:repeat(12,1fr);
  }
  .catalog-main{
    grid-column:span 8;
    display:flex;
    flex-direction:column;
    gap:12px;
  }
  .catalog-side{
    grid-column:span 4;
    display:flex;
    flex-direction:column;
    gap:12px;
  }

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

  .card-inline{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
  }
  .card-inline-item{
    flex:1 1 140px;
  }

  .toggle-row{
    display:flex;
    gap:8px;
    align-items:center;
    font-size:.9rem;
    color:#4b5563;
  }

  .images-list{
    display:flex;
    flex-direction:column;
    gap:8px;
  }
  .img-row{
    display:flex;
    gap:8px;
    align-items:center;
  }

  .form-actions{
    margin-top:18px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    justify-content:flex-end;
  }

  /* =========================================================
     🔹 Estilos IA (panel principal)
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

  .ai-helper-icon-wrapper{
    position:relative;
    width:46px;
    height:46px;
    flex:0 0 auto;
  }
  .ai-helper-glow{
    position:absolute;
    inset:0;
    border-radius:999px;
    background:radial-gradient(circle, rgba(59,130,246,.28), transparent 60%);
    opacity:.85;
    filter:blur(6px);
  }
  .ai-helper-icon{
    position:relative;
    width:46px;
    height:46px;
    border-radius:999px;
    background:#1d4ed8;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:1.5rem;
    box-shadow:0 14px 30px rgba(30,64,175,.55);
    transform:translateY(0);
    transition:transform .2s ease, box-shadow .2s ease, background .2s ease;
  }
  .ai-helper.ai-busy .ai-helper-icon{
    background:#1d4ed8;
    box-shadow:0 20px 40px rgba(30,64,175,.6);
    animation:aiBob 1.1s ease-in-out infinite;
  }

  .ai-helper-main{
    flex:1 1 260px;
    position:relative;
    z-index:1;
  }
  .ai-helper-header{
    display:flex;
    justify-content:space-between;
    gap:8px;
    align-items:flex-start;
    margin-bottom:4px;
  }
  .ai-helper-title{
    font-size:.95rem;
    font-weight:700;
    color:#0f172a;
  }
  .ai-helper-subtitle{
    margin:0;
    font-size:.8rem;
    color:#475569;
  }
  .ai-helper-chip{
    align-self:flex-start;
    font-size:.7rem;
    padding:3px 9px;
    border-radius:999px;
    background:rgba(236,252,203,.9);
    color:#4d7c0f;
    font-weight:600;
  }
  .ai-helper-text{
    margin:6px 0 10px;
    font-size:.8rem;
    color:#334155;
  }
  .ai-helper-row{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    align-items:flex-end;
  }
  .ai-helper-input{
    flex:1 1 260px;
  }
  .ai-helper-actions{
    display:flex;
    flex-direction:column;
    gap:4px;
    align-items:flex-start;
  }
  .ai-helper-status{
    min-height:18px;
  }

  .ai-cta{
    position:relative;
    overflow:hidden;
  }
  .ai-cta-spinner{
    width:16px;
    height:16px;
    border-radius:999px;
    border:2px solid rgba(191,219,254,.7);
    border-top-color:#eff6ff;
    opacity:0;
    transform:scale(.6);
    transition:opacity .15s ease, transform .15s ease;
  }
  .ai-cta-text{
    transition:transform .15s ease, opacity .15s ease;
  }
  .ai-helper.ai-busy .ai-cta-spinner{
    opacity:1;
    transform:scale(1);
    animation:aiSpin .8s linear infinite;
  }
  .ai-helper.ai-busy .ai-cta-text{
    opacity:.9;
  }

  /* =========================================================
     🔹 Dropzone IA
     ========================================================= */
  .ai-dropzone{
    position:relative;
    border-radius:16px;
    border:1.5px dashed rgba(148,163,184,.9);
    background:linear-gradient(135deg, rgba(239,246,255,.9), #ffffff);
    padding:10px 12px;
    display:flex;
    align-items:center;
    gap:10px;
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
    width:36px;
    height:36px;
    border-radius:999px;
    background:#1d4ed8;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:1.2rem;
    color:#e0f2fe;
    box-shadow:0 12px 24px rgba(30,64,175,.55);
    flex:0 0 auto;
  }
  .ai-dropzone-body{
    display:flex;
    flex-direction:column;
    gap:2px;
  }
  .ai-dropzone-title{
    font-size:.86rem;
    font-weight:600;
    color:#0f172a;
  }
  .ai-dropzone-sub{
    font-size:.8rem;
    color:#475569;
  }
  .ai-dropzone-btn{
    border:0;
    border-radius:999px;
    padding:4px 10px;
    font-size:.78rem;
    font-weight:600;
    margin-left:4px;
    background:#0f172a;
    color:#f9fafb;
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    gap:4px;
  }
  .ai-dropzone-btn:hover{
    background:#111827;
  }
  .ai-dropzone-hint{
    font-size:.75rem;
    color:#6b7280;
  }
  .ai-dropzone-input{
    position:absolute;
    inset:0;
    opacity:0;
    cursor:pointer;
  }

  .ai-files-list{
    margin-top:6px;
    display:flex;
    flex-wrap:wrap;
    gap:6px;
  }
  .ai-file-chip{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:3px 8px;
    font-size:.75rem;
    border-radius:999px;
    background:#eff6ff;
    color:#1e293b;
    border:1px solid #dbeafe;
    max-width:100%;
  }
  .ai-file-chip span{
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    max-width:180px;
  }

  /* =========================================================
     🔹 Panel de productos IA (lista)
     ========================================================= */
  .ai-items-panel{
    margin-bottom:18px;
    padding:12px 14px;
    background:#f9fafb;
    border-radius:18px;
    border:1px solid #e5e7eb;
    box-shadow:0 14px 30px rgba(15,23,42,.03);
    animation:fadeInUp .25s ease-out;
  }
  .ai-items-header{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:10px;
    margin-bottom:10px;
  }
  .ai-items-header-right{
    display:flex;
    align-items:center;
    gap:8px;
  }
  .ai-items-title{
    font-size:.9rem;
    font-weight:700;
    color:#0f172a;
  }
  .ai-items-text{
    margin:2px 0 0;
    font-size:.8rem;
    color:#4b5563;
  }
  .ai-items-badge{
    align-self:flex-start;
    font-size:.75rem;
    padding:3px 8px;
    border-radius:999px;
    background:#dcfce7;
    color:#15803d;
    font-weight:600;
    white-space:nowrap;
  }
  .ai-clear-btn{
    font-size:.75rem;
  }
  .ai-items-table-wrapper{
    width:100%;
    overflow:auto;
  }
  .ai-items-table{
    width:100%;
    border-collapse:collapse;
    font-size:.8rem;
  }
  .ai-items-table thead{
    background:#eff6ff;
  }
  .ai-items-table th,
  .ai-items-table td{
    padding:6px 8px;
    border-bottom:1px solid #e5e7eb;
    text-align:left;
    vertical-align:top;
  }
  .ai-items-table th{
    font-weight:700;
    color:#0f172a;
    white-space:nowrap;
  }
  .ai-items-table td{
    color:#4b5563;
  }
  .ai-items-table tr:hover{
    background:#f8fafc;
  }

  /* Campos autocompletados por IA */
  .ai-suggested{
    border-color:rgba(34,197,94,.9) !important;
    box-shadow:0 0 0 1px rgba(34,197,94,.4), 0 10px 25px rgba(22,163,74,.12);
    background:#f0fdf4;
  }

  @keyframes aiSpin{
    to{ transform:rotate(360deg); }
  }
  @keyframes aiBob{
    0%,100%{ transform:translateY(0); }
    50%{ transform:translateY(-3px); }
  }
  @keyframes aiSweep{
    0%{ transform:translateX(-30%); opacity:.2; }
    50%{ opacity:.5; }
    100%{ transform:translateX(30%); opacity:.2; }
  }
  @keyframes fadeInUp{
    from{
      opacity:0;
      transform:translateY(6px);
    }
    to{
      opacity:1;
      transform:translateY(0);
    }
  }

  @media (max-width: 992px){
    .catalog-grid{
      grid-template-columns:1fr;
    }
    .catalog-main,
    .catalog-side{
      grid-column:span 12;
    }
  }

  @media (max-width: 768px){
    .ai-items-table th:nth-child(3),
    .ai-items-table td:nth-child(3),
    .ai-items-table th:nth-child(5),
    .ai-items-table td:nth-child(5){
      display:none;
    }
  }

  /* =========================================================
     🔹 SweetAlert minimalista / moderno
     ========================================================= */
  .swal2-popup-compact{
    border-radius:18px !important;
    padding:12px 16px !important;
    box-shadow:0 18px 40px rgba(15,23,42,.18) !important;
    backdrop-filter:blur(16px);
    background:radial-gradient(circle at top left,#eff6ff 0,#ffffff 60%) !important;
    border:1px solid rgba(148,163,184,.35);
    font-family:"Söhne","Circular Std","Poppins",system-ui,-apple-system,"Segoe UI","Helvetica Neue",Arial,sans-serif;
  }
  .swal2-title{
    font-size:.9rem !important;
    font-weight:700 !important;
    color:#0f172a !important;
  }
  .swal2-html-container{
    font-size:.8rem !important;
    color:#4b5563 !important;
    margin-top:4px !important;
  }
  /* solo ajustamos márgenes, NO tamaños del ícono para que la palomita/tache se vean bien */
  .swal2-icon{
    margin:0 0 8px 0 !important;
  }
  .swal2-actions{
    margin-top:10px !important;
  }
  .swal2-styled.swal2-confirm{
    border-radius:999px !important;
    padding:7px 16px !important;
    font-size:.78rem !important;
    font-weight:600 !important;
    background:#2563eb !important;
  }
  .swal2-styled.swal2-cancel{
    border-radius:999px !important;
    padding:7px 16px !important;
    font-size:.78rem !important;
    font-weight:500 !important;
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  function addImageRow(){
    const list = document.getElementById('images-list');
    const idx = list.querySelectorAll('.img-row').length;
    const wrap = document.createElement('div');
    wrap.className = 'img-row';
    wrap.innerHTML = `
      <input name="images[${idx}]" class="inp" placeholder="https://...">
      <button type="button" class="btn btn-ghost btn-xs" onclick="this.parentElement.remove()">Quitar</button>`;
    list.appendChild(wrap);
  }

  // ================================
  // 🔹 Config base de SweetAlert UI
  // ================================
  const uiToast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3200,
    timerProgressBar: true,
    customClass: {
      popup: 'swal2-popup-compact',
    }
  });

  const AiAlerts = {
    success(title, text){
      uiToast.fire({
        icon: 'success',
        title: title || 'Listo',
        text: text || ''
      });
    },
    error(title, text){
      uiToast.fire({
        icon: 'error',
        title: title || 'Error',
        text: text || ''
      });
    },
    info(title, text){
      uiToast.fire({
        icon: 'info',
        title: title || 'Info',
        text: text || ''
      });
    }
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

    // 🔐 claves para localStorage
    const LS_KEY_ITEMS = 'catalog_ai_items';
    const LS_KEY_INDEX = 'catalog_ai_index';

    let aiItems = [];

    // ========= helpers de almacenamiento =========
    function saveAiItemsToStorage() {
      try {
        localStorage.setItem(LS_KEY_ITEMS, JSON.stringify(aiItems || []));
      } catch (e) {
        console.error('No se pudo guardar ai_items en localStorage', e);
      }
    }

    function saveAiIndexToStorage(idx) {
      try {
        localStorage.setItem(LS_KEY_INDEX, String(idx ?? 0));
      } catch (e) {}
    }

    function loadAiItemsFromStorage() {
      try {
        const raw = localStorage.getItem(LS_KEY_ITEMS);
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
      } catch (e) {
        console.error('No se pudieron leer ai_items de localStorage', e);
        return [];
      }
    }

    function loadAiIndexFromStorage() {
      try {
        const raw = localStorage.getItem(LS_KEY_INDEX);
        const idx = parseInt(raw ?? '0', 10);
        return isNaN(idx) ? 0 : Math.max(0, idx);
      } catch (e) {
        return 0;
      }
    }

    // ========= Dropzone: arrastrar / soltar =========
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
        // evitamos doble apertura, pero siempre que hagas click en el área abre el picker
        if (e.target.closest('.ai-dropzone-btn')) {
          e.preventDefault();
        }
        inputFiles.click();
      });

      inputFiles.addEventListener('change', function () {
        refreshFileChips(inputFiles.files);
      });

      ['dragenter','dragover'].forEach(evt => {
        dropzone.addEventListener(evt, function (e) {
          e.preventDefault();
          e.stopPropagation();
          dropzone.classList.add('is-dragover');
        });
      });

      ['dragleave','dragend','drop'].forEach(evt => {
        dropzone.addEventListener(evt, function (e) {
          e.preventDefault();
          e.stopPropagation();
          dropzone.classList.remove('is-dragover');
        });
      });

      dropzone.addEventListener('drop', function (e) {
        const dt = new DataTransfer();
        Array.from(e.dataTransfer.files || []).forEach(file => {
          if (file.type.startsWith('image/') || file.type === 'application/pdf') {
            dt.items.add(file);
          }
        });
        if (dt.files.length) {
          inputFiles.files = dt.files;
          refreshFileChips(dt.files);
        }
      });
    }

    // ========= limpiar lista IA manualmente =========
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

    // ========= reconstruir lista desde localStorage al cargar =========
    function attachUseButtons() {
      if (!tbody) return;
      tbody.querySelectorAll('button[data-ai-index]').forEach(btn => {
        btn.addEventListener('click', function () {
          const i = parseInt(this.getAttribute('data-ai-index'), 10);
          const item = aiItems[i];
          if (!item) return;
          saveAiIndexToStorage(i); // recordamos cuál usaste
          fillFromItem(item, { markSuggested: true });
          if (statusEl) {
            statusEl.textContent = 'Se cargó el producto #' + (i + 1) + ' desde la lista IA. Revisa y ajusta antes de guardar.';
          }
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
            <button type="button"
                    class="btn btn-ghost btn-xs"
                    data-ai-index="${idx}">Usar este</button>
          </td>
        `;
        tbody.appendChild(tr);
      });

      if (countEl) {
        countEl.textContent = aiItems.length === 1
          ? '1 producto detectado'
          : aiItems.length + ' productos detectados';
      }

      panel.style.display = aiItems.length ? 'block' : 'none';
      attachUseButtons();
    }

    aiItems = loadAiItemsFromStorage();
    if (aiItems.length) {
      renderAiTable();
      if (statusEl) {
        statusEl.textContent = 'Se restauraron los productos detectados por IA. Puedes seguir capturando sin volver a subir el PDF.';
      }
      const idx = loadAiIndexFromStorage();
      const item = aiItems[idx] || aiItems[0];
      if (item) {
        fillFromItem(item, { markSuggested: true });
      }
    }

    // ========= botón Analizar con IA =========
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
      const originalText = btnAi.querySelector('.ai-cta-text')
        ? btnAi.querySelector('.ai-cta-text').textContent
        : btnAi.textContent;

      if (btnAi.querySelector('.ai-cta-text')) {
        btnAi.querySelector('.ai-cta-text').textContent = 'Analizando...';
      } else {
        btnAi.textContent = 'Analizando...';
      }

      if (helperBox) helperBox.classList.add('ai-busy');
      if (statusEl) {
        statusEl.textContent = 'Enviando archivos a la IA, esto puede tardar unos segundos...';
      }

      // Limpiar tabla anterior (en memoria, pero aún no tocamos localStorage)
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

        if (aiItems.length) {
          renderAiTable();
        }

        if (statusEl) {
          statusEl.textContent = 'Listo: revisa y ajusta las sugerencias marcadas en verde antes de guardar.';
        }
        AiAlerts.success('Sugerencias listas', 'La IA completó los campos principales del producto.');
      })
      .catch(err => {
        console.error(err);
        if (statusEl) statusEl.textContent = 'Ocurrió un error al llamar a la IA.';
        AiAlerts.error('Error de conexión', 'Ocurrió un problema al contactar la IA.');
      })
      .finally(() => {
        btnAi.disabled = false;
        if (btnAi.querySelector('.ai-cta-text')) {
          btnAi.querySelector('.ai-cta-text').textContent = originalText;
        } else {
          btnAi.textContent = originalText;
        }
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

      // 🔹 llenar stock/cantidad sugerida por la IA si viene
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
    // 🔹 Consumir el último producto usado de localStorage (se asume que sí se guardó)
    try {
      const LS_KEY_ITEMS = 'catalog_ai_items';
      const LS_KEY_INDEX = 'catalog_ai_index';
      const rawItems = localStorage.getItem(LS_KEY_ITEMS);
      if (rawItems) {
        let items = JSON.parse(rawItems);
        if (!Array.isArray(items)) items = [];
        const rawIdx = localStorage.getItem(LS_KEY_INDEX);
        let idx = parseInt(rawIdx ?? '0', 10);
        if (isNaN(idx) || idx < 0 || idx >= items.length) {
          idx = 0;
        }
        if (items.length) {
          items.splice(idx, 1); // quitamos el que se acaba de guardar
          localStorage.setItem(LS_KEY_ITEMS, JSON.stringify(items));
          localStorage.setItem(LS_KEY_INDEX, '0');
        }
      }
    } catch (e) {}

    Swal.fire({
      icon: 'success',
      title: 'Listo ✨',
      text: @json(session('ok')),
      customClass: {
        popup: 'swal2-popup-compact'
      },
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
      customClass: {
        popup: 'swal2-popup-compact'
      },
      confirmButtonText: 'Entendido'
    });
  });
</script>
@endif
@endpush
