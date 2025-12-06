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
      La IA leerá lo que vea y te sugerirá automáticamente: <strong>nombre, descripción, extracto, precio, marca, modelo y GTIN</strong>.
      Siempre puedes revisar, corregir y complementar antes de guardar.
    </p>

    <div class="ai-helper-row">
      <div class="ai-helper-input">
        <label class="lbl" style="margin-top:0;">Archivos para IA</label>
        <input id="ai_files" name="ai_files[]" type="file" multiple
               accept="image/*,.pdf"
               class="inp">
        <p class="hint">
          Acepta varias imágenes y PDFs (máx. ~8 MB c/u). Se usan solo para generar sugerencias, no se guardan en tu sistema.
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
   🔹 TABLA DE PRODUCTOS DETECTADOS POR IA (CAPTURAR CON IA)
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
    <span class="ai-items-badge" id="ai-items-count"></span>
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
            Existencias disponibles. Puedes ajustarlo más adelante desde tu módulo de inventarios.
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
</style>
@endpush

@push('scripts')
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
  // 🔹 IA: subir archivos y rellenar campos + lista de items
  // ================================
  document.addEventListener('DOMContentLoaded', function () {
    const btnAi      = document.getElementById('btn-ai-analyze');
    const inputFiles = document.getElementById('ai_files');
    const statusEl   = document.getElementById('ai-helper-status');
    const helperBox  = document.getElementById('ai-helper');

    const panel      = document.getElementById('ai-items-panel');
    const tbody      = document.getElementById('ai-items-tbody');
    const countEl    = document.getElementById('ai-items-count');

    let aiItems = [];

    if (!btnAi || !inputFiles) return;

    btnAi.addEventListener('click', function () {
      if (!inputFiles.files || !inputFiles.files.length) {
        alert('Sube al menos un archivo (imagen o PDF) para que la IA pueda analizarlo.');
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

      // Limpiar tabla anterior
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
          return;
        }

        // 1) Rellenar el formulario con el primer producto (suggestions)
        const s = data.suggestions || {};
        fillFromItem(s, { markSuggested: true });

        // 2) Pintar la lista completa de productos (items[])
        aiItems = Array.isArray(data.items) ? data.items : [];

        if (aiItems.length && tbody && panel) {
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
                        data-ai-index="${idx}">
                  Usar este
                </button>
              </td>
            `;
            tbody.appendChild(tr);
          });

          if (countEl) {
            countEl.textContent = aiItems.length === 1
              ? '1 producto detectado'
              : aiItems.length + ' productos detectados';
          }

          panel.style.display = 'block';

          // Delegar eventos "Usar este"
          tbody.querySelectorAll('button[data-ai-index]').forEach(btn => {
            btn.addEventListener('click', function () {
              const i = parseInt(this.getAttribute('data-ai-index'), 10);
              const item = aiItems[i];
              if (!item) return;
              fillFromItem(item, { markSuggested: true });
              if (statusEl) {
                statusEl.textContent = 'Se cargó el producto #' + (i + 1) + ' desde la lista IA. Revisa y ajusta antes de guardar.';
              }
            });
          });
        }

        if (statusEl) {
          statusEl.textContent = 'Listo: revisa y ajusta las sugerencias marcadas en verde antes de guardar.';
        }
      })
      .catch(err => {
        console.error(err);
        if (statusEl) statusEl.textContent = 'Ocurrió un error al llamar a la IA.';
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
        // quitar highlight suave después de unos segundos
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
@endpush
