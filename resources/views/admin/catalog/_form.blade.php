@php 
  /** @var \App\Models\CatalogItem|null $item */
  $isEdit = isset($item);
@endphp

@csrf
@if($isEdit)
  @method('PUT')
@endif

{{-- 🔹 BLOQUE DE CAPTURA ASISTIDA POR IA (ARCHIVOS / IMÁGENES / PDF) --}}
<div class="ai-helper">
  <div class="ai-helper-icon">🤖</div>

  <div class="ai-helper-main">
    <div class="ai-helper-title">Captura asistida por IA (archivos / imágenes / PDF)</div>
    <p class="ai-helper-text">
      Sube fotos del producto, tickets, remisiones o PDFs con imágenes. La IA leerá lo que vea
      y te sugerirá automáticamente: nombre, descripción, extracto, precio, marca, modelo y GTIN.
      Tú siempre puedes revisar y corregir antes de guardar.
    </p>

    <div class="ai-helper-row">
      <div class="ai-helper-input">
        <label class="lbl" style="margin-top:0;">Archivos para IA</label>
        <input id="ai_files" name="ai_files[]" type="file" multiple
               accept="image/*,.pdf"
               class="inp">
        <p class="hint">
          Acepta varias imágenes y PDFs (max. ~8 MB c/u). Se usan solo para generar sugerencias.
        </p>
      </div>

      <div class="ai-helper-actions">
        <button type="button" id="btn-ai-analyze" class="btn btn-primary">
          Analizar con IA
        </button>
        <p id="ai-helper-status" class="hint" style="margin-top:6px;">
          La IA no sustituye tu revisión, solo te ahorra tecleo repetitivo.
        </p>
      </div>
    </div>
  </div>
</div>

<div class="grid" style="display:grid;gap:16px;grid-template-columns:repeat(12,1fr)">
  {{-- Columna izquierda: contenido principal --}}
  <div style="grid-column:span 8;display:flex;flex-direction:column;gap:10px;">
    <div>
      <label class="lbl">Nombre *</label>
      <input name="name" class="inp" required
             placeholder="Ejemplo: Lapicero bolígrafo azul Bic punta fina 0.7mm"
             value="{{ old('name', $item->name ?? '') }}">
      <p class="hint">
        Usa un nombre completo: tipo de producto + marca + modelo + característica clave.
        Esto ayuda al SEO y evita rechazos en Mercado Libre.
      </p>
    </div>

    <div>
      <label class="lbl">Slug (opcional)</label>
      <input name="slug" class="inp"
             placeholder="lapicero-bic-azul-07mm"
             value="{{ old('slug', $item->slug ?? '') }}">
      <p class="hint">
        Déjalo vacío para generarlo automáticamente a partir del nombre, salvo que necesites un slug específico.
      </p>
    </div>

    <div>
      <label class="lbl">Descripción</label>
      <textarea name="description" class="inp" rows="6"
                placeholder="Describe el producto, sus usos, materiales, medidas, garantía, etc.">{{ old('description', $item->description ?? '') }}</textarea>
      <p class="hint">
        Es la descripción larga que verán tus clientes. Evita mayúsculas excesivas y texto repetitivo.
      </p>
    </div>

    <div>
      <label class="lbl">Extracto</label>
      <textarea name="excerpt" class="inp" rows="3"
                placeholder="Resumen corto para listados y Mercado Libre (ej. Caja con 12 piezas, tinta azul, punta 0.7mm).">{{ old('excerpt', $item->excerpt ?? '') }}</textarea>
      <p class="hint">
        Un resumen breve con la información más importante: presentación, cantidad, color o medida.
      </p>
    </div>
  </div>

  {{-- Columna derecha: datos comerciales + ML tips --}}
  <div style="grid-column:span 4;display:flex;flex-direction:column;gap:10px;">
    <div class="side-card">
      <div>
        <label class="lbl">SKU</label>
        <input name="sku" class="inp"
               placeholder="Código interno o del proveedor"
               value="{{ old('sku', $item->sku ?? '') }}">
        <p class="hint">
          Usa un SKU claro y único. Te ayuda a localizar el producto rápidamente en tu catálogo.
        </p>
      </div>

      <div>
        <label class="lbl">Precio *</label>
        <input name="price" type="number" step="0.01" min="0" class="inp" required
               value="{{ old('price', $item->price ?? 0) }}">
        <p class="hint">
          Precio base en MXN. Algunas categorías de Mercado Libre requieren un mínimo (por ejemplo, desde 35 MXN).
        </p>
      </div>

      <div>
        <label class="lbl">Precio oferta</label>
        <input name="sale_price" type="number" step="0.01" min="0" class="inp"
               value="{{ old('sale_price', $item->sale_price ?? '') }}">
        <p class="hint">
          Solo si hay promoción. Si lo dejas vacío, se usará el precio base.
        </p>
      </div>

      <div>
        <label class="lbl">Estado *</label>
        <select name="status" class="inp" required>
          @php $st = (string)old('status', isset($item)? (string)$item->status : '0'); @endphp
          <option value="0" @selected($st==='0')>Borrador (no visible)</option>
          <option value="1" @selected($st==='1')>Publicado</option>
          <option value="2" @selected($st==='2')>Oculto (no listado, pero accesible por link)</option>
        </select>
      </div>

      <div>
        <label class="lbl">Publicado en</label>
        <input name="published_at" type="datetime-local" class="inp"
               value="{{ old('published_at', isset($item->published_at)? $item->published_at->format('Y-m-d\TH:i') : '') }}">
        <p class="hint">
          Si lo dejas vacío, se asignará automáticamente al momento de publicar.
        </p>
      </div>

      <div>
        <label class="lbl">Destacado (para Home)</label>
        <label style="display:flex;gap:8px;align-items:center;font-size:.9rem;color:#4b5563;">
          <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $item->is_featured ?? false))> Mostrar en secciones destacadas
        </label>
      </div>
    </div>

    <div class="side-card">
      <h3 class="side-title">Datos de clasificación</h3>
      <label class="lbl">Marca (ID interno)</label>
      <input name="brand_id" type="number" class="inp"
             value="{{ old('brand_id', $item->brand_id ?? '') }}"
             placeholder="Opcional: ID en tu sistema de marcas">
      <p class="hint">
        Solo si manejas un catálogo de marcas interno por ID.
      </p>

      <label class="lbl">Categoría (ID interno)</label>
      <input name="category_id" type="number" class="inp"
             value="{{ old('category_id', $item->category_id ?? '') }}"
             placeholder="Opcional: ID de categoría interna">
      <p class="hint">
        Se usa para tu menú / filtro de categorías en el sitio.
      </p>
    </div>

    <div class="side-card">
      <h3 class="side-title">Ayuda para Mercado Libre</h3>

      <label class="lbl">Marca (texto para ML)</label>
      <input name="brand_name" class="inp"
             placeholder="Ejemplo: Bic, Azor, Maped"
             value="{{ old('brand_name', $item->brand_name ?? '') }}">
      <p class="hint">
        Este nombre se envía al atributo <strong>BRAND</strong> de Mercado Libre. Usa la marca comercial tal como la buscan tus clientes.
      </p>

      <label class="lbl">Modelo (texto para ML)</label>
      <input name="model_name" class="inp"
             placeholder="Ejemplo: Cristal 1.0mm, Office Pro"
             value="{{ old('model_name', $item->model_name ?? '') }}">
      <p class="hint">
        Se envía al atributo <strong>MODEL</strong>. Si no tienes modelo, puedes dejarlo vacío y usaremos el SKU como respaldo.
      </p>

      <label class="lbl">GTIN / Código de barras</label>
      <input name="meli_gtin" class="inp"
             placeholder="Ejemplo: 7501035910107"
             value="{{ old('meli_gtin', $item->meli_gtin ?? '') }}">
      <p class="hint">
        En varias categorías de Mercado Libre es obligatorio el código de barras (GTIN, EAN, UPC, etc.).
        Lo encuentras impreso junto al código de barras del producto o la caja.
      </p>

      <div class="ml-tips">
        <p class="hint-title">Para evitar errores al publicar en Mercado Libre:</p>
        <ul class="hint-list">
          <li>El título debe incluir tipo, marca y modelo. Evita nombres genéricos como “Lapicero” solamente.</li>
          <li>Revisa que el precio sea suficiente para la categoría (algunas exigen un mínimo).</li>
          <li>Asegúrate de tener al menos una imagen válida y accesible por URL.</li>
          <li>Completa el GTIN cuando el sistema te lo pida; si falta, verás un mensaje que lo menciona.</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<hr class="divi">

<label class="lbl">Imagen de portada (URL)</label>
<input name="image_url" class="inp"
       placeholder="https://tusitio.com/imagenes/lapicero-azul.jpg"
       value="{{ old('image_url', $item->image_url ?? '') }}">
<p class="hint">
  Usa una imagen limpia, bien iluminada y con fondo neutro. Es la principal que verá el cliente.
</p>

<label class="lbl">Imágenes adicionales (URLs)</label>
<div id="images-list" style="display:flex; flex-direction:column; gap:8px;">
  @php
    $imgs = old('images', $item->images ?? []);
    if (!is_array($imgs)) { $imgs = []; }
  @endphp
  @forelse($imgs as $i => $url)
    <div class="img-row">
      <input name="images[{{ $i }}]" class="inp" value="{{ $url }}" placeholder="https://...">
      <button type="button" class="btn btn-ghost" onclick="this.parentElement.remove()">Quitar</button>
    </div>
  @empty
    <div class="img-row">
      <input name="images[0]" class="inp" placeholder="https://...">
      <button type="button" class="btn btn-ghost" onclick="this.parentElement.remove()">Quitar</button>
    </div>
  @endforelse
</div>
<p class="hint">
  Puedes añadir varias vistas del producto (frente, reverso, detalle, empaque). Mercado Libre recomienda buena resolución y fondo claro.
</p>
<button type="button" class="btn" onclick="addImageRow()">+ Agregar imagen</button>

<div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap;justify-content:flex-end;">
  <button class="btn btn-primary" type="submit">{{ $isEdit ? 'Guardar cambios' : 'Crear producto' }}</button>
  <a class="btn btn-ghost" href="{{ route('admin.catalog.index') }}">Cancelar</a>
</div>

@push('styles')
<style>
  :root{
    --ink:#0e1726;--muted:#65748b;--line:#e8eef6;--surface:#fff;
    --brand:#6ea8fe;--shadow:0 10px 24px rgba(13,23,38,.06);
  }
  .lbl{display:block;font-weight:800;color:var(--ink);margin:10px 0 4px;font-size:.9rem;}
  .inp{
    width:100%;background:#fff;border:1px solid var(--line);border-radius:12px;
    padding:10px 12px;min-height:42px;font-size:.92rem;color:#0f172a;
  }
  .inp:focus{outline:none;border-color:#93c5fd;box-shadow:0 0 0 1px #bfdbfe;}
  .btn{border:0;border-radius:12px;padding:10px 14px;font-weight:700;cursor:pointer;font-size:.9rem;}
  .btn-primary{background:var(--brand);box-shadow:0 8px 18px rgba(29,78,216,.12);color:#0b1220;}
  .btn-ghost{background:#fff;border:1px solid var(--line);color:#0f172a;}
  .btn:hover{transform:translateY(-1px);transition:.15s transform;}
  .divi{border:none;border-top:1px solid var(--line);margin:16px 0}
  .img-row{display:flex; gap:8px; align-items:center}
  .hint{
    margin:4px 0 0;
    font-size:.78rem;
    color:var(--muted);
  }
  .side-card{
    background:#f9fafb;
    border-radius:14px;
    border:1px solid var(--line);
    padding:10px 12px;
  }
  .side-title{
    margin:0 0 6px;
    font-size:.9rem;
    font-weight:700;
    color:#0f172a;
  }
  .ml-tips{
    margin-top:6px;
    padding-top:6px;
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

  /* 🔹 Estilos IA */
  .ai-helper{
    margin-bottom:18px;
    padding:12px 14px;
    background:#eff6ff;
    border-radius:16px;
    border:1px dashed #93c5fd;
    display:flex;
    gap:10px;
    align-items:flex-start;
    flex-wrap:wrap;
  }
  .ai-helper-icon{
    font-size:1.6rem;
    line-height:1;
  }
  .ai-helper-main{
    flex:1 1 260px;
  }
  .ai-helper-title{
    font-size:.9rem;
    font-weight:700;
    color:#1d4ed8;
    margin-bottom:2px;
  }
  .ai-helper-text{
    margin:0 0 8px;
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
  }
  .ai-suggested{
    border-color:#22c55e !important;
    box-shadow:0 0 0 1px rgba(34,197,94,.35);
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
      <button type="button" class="btn btn-ghost" onclick="this.parentElement.remove()">Quitar</button>`;
    list.appendChild(wrap);
  }

  // ================================
  // 🔹 IA: subir archivos y rellenar campos
  // ================================
  document.addEventListener('DOMContentLoaded', function () {
    const btnAi = document.getElementById('btn-ai-analyze');
    const inputFiles = document.getElementById('ai_files');
    const statusEl = document.getElementById('ai-helper-status');

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
      const originalText = btnAi.textContent;
      btnAi.textContent = 'Analizando...';
      statusEl.textContent = 'Enviando archivos a la IA, esto puede tardar unos segundos...';

      fetch("{{ route('admin.catalog.ai-from-upload') }}", {
        method: "POST",
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.error) {
          statusEl.textContent = 'Error: ' + (data.error || 'No fue posible obtener sugerencias.');
          return;
        }

        const s = data.suggestions || {};
        applyAiSuggestion('name', s.name);
        applyAiSuggestion('slug', s.slug);
        applyAiSuggestion('description', s.description);
        applyAiSuggestion('excerpt', s.excerpt);
        applyAiSuggestion('price', s.price);
        applyAiSuggestion('brand_name', s.brand_name);
        applyAiSuggestion('model_name', s.model_name);
        applyAiSuggestion('meli_gtin', s.meli_gtin);

        statusEl.textContent = 'Listo: revisa y ajusta las sugerencias marcadas en verde antes de guardar.';
      })
      .catch(err => {
        console.error(err);
        statusEl.textContent = 'Ocurrió un error al llamar a la IA.';
      })
      .finally(() => {
        btnAi.disabled = false;
        btnAi.textContent = originalText;
      });
    });

    function applyAiSuggestion(fieldName, value) {
      if (value === undefined || value === null || value === '') return;
      const el = document.querySelector('[name="' + fieldName + '"]');
      if (!el) return;

      // Si el campo está vacío, lo llenamos. Si ya trae algo, lo sobrescribimos
      // (puedes cambiar este comportamiento si prefieres no sobrescribir).
      el.value = value;
      el.classList.add('ai-suggested');
    }
  });
</script>
@endpush
