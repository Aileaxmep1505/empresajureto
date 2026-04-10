{{-- ✅ resources/views/admin/catalog/_form.blade.php --}}

@php
  /** @var \App\Models\CatalogItem|null $item */
  $isEdit = isset($item) && $item;
  $has1 = !empty($item->photo_1 ?? null);
  $has2 = !empty($item->photo_2 ?? null);
  $has3 = !empty($item->photo_3 ?? null);
  $hasSku = !empty($item->sku ?? null);

  /**
   * Espera un arreglo plano o jerárquico de categorías.
   * Recomendado desde controller:
   * $categories = \App\Models\CategoryProduct::orderBy('name')->get();
   */
  $categories = $categories ?? collect();

  $currentCategoryId = old('category_product_id', $item->category_product_id ?? '');
@endphp

<form
  id="catalogItemForm"
  method="POST"
  action="{{ $isEdit ? route('admin.catalog.update', $item) : route('admin.catalog.store') }}"
  enctype="multipart/form-data"
  class="w-full enterprise-ui fade-in-up"
>
  @csrf
  @if($isEdit) @method('PUT') @endif

  {{-- =========================================================
       🔹 MÓDULO COPILOTO IA
       ========================================================= --}}
  <div class="ai-copilot-wrapper mb-8 animate-enter" style="--stagger: 1;">
    <div class="ai-copilot-border"></div>
    <div class="ai-copilot-content flex flex-col xl:flex-row gap-6 items-center justify-between p-6 md:p-8 relative bg-white">

      <div class="flex-1 w-full">
        <div class="flex items-center gap-3 mb-2">
          <span class="material-symbols-outlined text-transparent bg-clip-text bg-gradient-to-r from-indigo-500 to-purple-500 animate-pulse">magic_button</span>
          <h2 class="text-xl font-semibold m-0 tracking-tight">Copiloto de IA</h2>
          <span class="badge-ai">BETA</span>
        </div>
        <p class="text-sm text-slate-500 m-0 max-w-2xl">
          Arrastra tu documento (PDF, JPG) y la inteligencia artificial extraerá y categorizará la información automáticamente.
        </p>

        <div id="ai-files-list" class="flex flex-wrap gap-2 mt-4 empty:hidden"></div>
      </div>

      <div class="flex-1 w-full flex flex-col sm:flex-row gap-4 items-center xl:justify-end">
        <div id="ai-dropzone" class="dropzone-minimal w-full xl:w-80 group">
          <input id="ai_files" name="ai_files[]" type="file" multiple accept="image/*,.pdf" class="hidden-input">
          <div class="flex items-center justify-center gap-2 pointer-events-none transition-transform group-hover:scale-105">
            <span class="material-symbols-outlined text-slate-400 group-hover:text-indigo-500 transition-colors">upload_file</span>
            <span class="text-sm font-medium text-slate-600 group-hover:text-indigo-600 transition-colors" id="ai-drop-text">Cargar archivos</span>
          </div>
        </div>

        <button type="button" id="btn-ai-analyze" class="btn-ai-action w-full sm:w-auto shrink-0 disabled:opacity-50">
          <span class="material-symbols-outlined spinner hidden" id="ai-spinner">progress_activity</span>
          <span id="ai-btn-text">Analizar Documento</span>
        </button>
      </div>
    </div>
  </div>

  {{-- RESULTADOS IA --}}
  <div id="ai-items-panel" class="mb-8 hidden-collapse" style="display:none;">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-semibold text-slate-800 uppercase tracking-wider m-0">Resultados de Extracción</h3>
      <button type="button" id="ai-clear-list" class="text-xs text-red-500 hover:text-red-700 font-medium transition-colors bg-transparent border-none cursor-pointer">
        Descartar resultados
      </button>
    </div>
    <div class="table-container bg-white border border-slate-200 rounded-xl overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-200">
            <th class="p-4 text-xs font-semibold text-slate-500 uppercase">#</th>
            <th class="p-4 text-xs font-semibold text-slate-500 uppercase">Producto</th>
            <th class="p-4 text-xs font-semibold text-slate-500 uppercase">Precio</th>
            <th class="p-4 text-xs font-semibold text-slate-500 uppercase">Marca / Modelo</th>
            <th class="p-4 text-xs font-semibold text-slate-500 uppercase">GTIN</th>
            <th class="p-4 text-xs font-semibold text-slate-500 uppercase text-right">Acción</th>
          </tr>
        </thead>
        <tbody id="ai-items-tbody"></tbody>
      </table>
    </div>
  </div>

  {{-- =========================================================
       🔹 FORMULARIO PRINCIPAL
       ========================================================= --}}
  <div class="grid grid-cols-1 xl:grid-cols-3 gap-8 w-full">

    {{-- COLUMNA PRINCIPAL --}}
    <div class="xl:col-span-2 flex flex-col gap-8">

      {{-- Información principal --}}
      <div class="form-section animate-enter" style="--stagger: 2;">
        <h3 class="section-heading">Información Principal</h3>

        <div class="form-group">
          <label class="form-label flex justify-between">
            <span>Nombre del Producto <span class="text-red-500">*</span></span>
            <span class="ai-badge hidden">✨ Rellenado por IA</span>
          </label>
          <input
            name="name"
            class="form-input text-lg"
            required
            placeholder="Ej. Bolígrafo Azul Bic Punta Fina 0.7mm"
            value="{{ old('name', $item->name ?? '') }}"
          >
        </div>

        <div class="form-group">
          <label class="form-label flex justify-between">
            <span>Descripción Técnica</span>
            <span class="ai-badge hidden">✨ Rellenado por IA</span>
          </label>
          <textarea
            name="description"
            class="form-input min-h-[160px] resize-y"
            placeholder="Describe características técnicas, beneficios y contenido."
          >{{ old('description', $item->description ?? '') }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="form-group m-0">
            <label class="form-label flex justify-between">
              <span>Slug (URL)</span>
              <span class="ai-badge hidden">✨ Rellenado por IA</span>
            </label>
            <input
              name="slug"
              class="form-input text-sm text-slate-500"
              placeholder="Se generará automáticamente"
              value="{{ old('slug', $item->slug ?? '') }}"
            >
          </div>

          <div class="form-group m-0">
            <label class="form-label flex justify-between">
              <span>Extracto Corto</span>
              <span class="ai-badge hidden">✨ Rellenado por IA</span>
            </label>
            <textarea
              name="excerpt"
              class="form-input min-h-[42px] resize-none"
              rows="1"
              placeholder="Breve resumen de 1 línea"
            >{{ old('excerpt', $item->excerpt ?? '') }}</textarea>
          </div>
        </div>
      </div>

      {{-- Multimedia --}}
      <div class="form-section animate-enter" style="--stagger: 3;">
        <div class="flex justify-between items-center mb-6">
          <h3 class="section-heading m-0">Multimedia <span class="text-red-500">*</span></h3>
          <span class="text-xs text-slate-400">JPG, PNG, WEBP (Máx. 5MB)</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          @foreach([1 => 'Frente (Fondo Blanco)', 2 => 'Ángulo / Empaque', 3 => 'Detalle / Etiqueta'] as $i => $label)
            @php
              $hasPic = ${"has$i"};
              $picField = "photo_{$i}_file";
            @endphp

            <div class="media-box {{ ($isEdit && $hasPic) ? 'has-media' : '' }}" data-photo-card="{{ $picField }}">
              <label class="media-area" for="{{ $picField }}">
                <div class="media-preview" id="photo_{{ $i }}_preview">
                  @if($isEdit && $hasPic)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($item->{"photo_$i"}) }}" alt="Foto {{ $i }}">
                  @else
                    <span class="material-symbols-outlined text-slate-300 text-3xl transition-transform">add_photo_alternate</span>
                  @endif
                </div>
                <div class="media-info">
                  <span class="media-title" data-photo-strong="{{ $picField }}">Foto {{ $i }}</span>
                  <span class="media-subtitle" data-photo-sub="{{ $picField }}">{{ $label }}</span>
                </div>
              </label>

              <input
                id="{{ $picField }}"
                name="{{ $picField }}"
                type="file"
                class="hidden-input"
                accept="image/*"
                @if(!$isEdit) required @endif
              >

              <button type="button" class="media-clear" data-photo-clear="{{ $picField }}">
                <span class="material-symbols-outlined text-[16px]">close</span>
              </button>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    {{-- COLUMNA LATERAL --}}
    <div class="xl:col-span-1 flex flex-col gap-8 w-full">

      {{-- Comercial --}}
      <div class="form-section animate-enter" style="--stagger: 4;">
        <h3 class="section-heading">Comercial</h3>

        <div class="grid grid-cols-2 gap-4 mb-5">
          <div class="form-group m-0">
            <label class="form-label">
              <span>Precio Base <span class="text-red-500">*</span></span>
            </label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-medium pointer-events-none">$</span>
              <input
                name="price"
                type="number"
                step="0.01"
                min="0"
                class="form-input pl-8 font-semibold text-slate-900"
                required
                value="{{ old('price', $item->price ?? 0) }}"
              >
            </div>
          </div>

          <div class="form-group m-0">
            <label class="form-label flex justify-between">
              <span>Stock</span>
              <span class="ai-badge hidden">✨</span>
            </label>
            <input
              name="stock"
              type="number"
              step="1"
              min="0"
              class="form-input"
              value="{{ old('stock', $item->stock ?? 0) }}"
            >
          </div>
        </div>

        <div class="form-group mb-5">
          <label class="form-label text-slate-500">Precio Oferta (Opcional)</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-medium pointer-events-none">$</span>
            <input
              name="sale_price"
              type="number"
              step="0.01"
              min="0"
              class="form-input pl-8"
              placeholder="0.00"
              value="{{ old('sale_price', $item->sale_price ?? '') }}"
            >
          </div>
        </div>

        {{-- ✅ CATEGORÍA NUEVA --}}
        <div class="form-group mb-5">
          <label class="form-label">Categoría</label>
          <select name="category_product_id" class="form-select">
            <option value="">Selecciona una categoría...</option>

            @foreach($categories as $cat)
              <option value="{{ $cat->id }}" @selected((string)$currentCategoryId === (string)$cat->id)>
                {{ $cat->full_path ?? $cat->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="form-group m-0">
          <label class="form-label">Estado de Visibilidad <span class="text-red-500">*</span></label>
          <select name="status" class="form-select font-medium" required>
            @php $st = (string) old('status', isset($item) ? (string) $item->status : '0'); @endphp
            <option value="1" @selected($st === '1')>🟢 Publicado</option>
            <option value="0" @selected($st === '0')>⚪ Borrador</option>
            <option value="2" @selected($st === '2')>🟡 Privado (Solo Link)</option>
          </select>
        </div>
      </div>

      {{-- Marketplaces --}}
      <div class="form-section animate-enter" style="--stagger: 5;">
        <h3 class="section-heading">Marketplaces (SKU & Meta)</h3>

        <div class="form-group mb-5">
          <label class="form-label">SKU Interno</label>
          <input
            name="sku"
            class="form-input text-sm uppercase"
            placeholder="Requerido para Amazon"
            value="{{ old('sku', $item->sku ?? '') }}"
          >
        </div>

        <div class="grid grid-cols-2 gap-4 mb-5">
          <div class="form-group m-0">
            <label class="form-label flex justify-between">
              <span>Marca</span>
              <span class="ai-badge hidden">✨</span>
            </label>
            <input
              name="brand_name"
              class="form-input"
              placeholder="Ej. Sony"
              value="{{ old('brand_name', $item->brand_name ?? '') }}"
            >
          </div>

          <div class="form-group m-0">
            <label class="form-label flex justify-between">
              <span>Modelo</span>
              <span class="ai-badge hidden">✨</span>
            </label>
            <input
              name="model_name"
              class="form-input"
              placeholder="Ej. PS5"
              value="{{ old('model_name', $item->model_name ?? '') }}"
            >
          </div>
        </div>

        <div class="form-group m-0">
          <label class="form-label flex justify-between">
            <span>Código (GTIN/EAN)</span>
            <span class="ai-badge hidden">✨</span>
          </label>
          <input
            name="meli_gtin"
            class="form-input text-sm tracking-widest"
            placeholder="Ej. 7501035910107"
            value="{{ old('meli_gtin', $item->meli_gtin ?? '') }}"
          >
        </div>
      </div>
    </div>
  </div>

  {{-- ACCIONES --}}
  <div class="sticky-footer w-full flex flex-col-reverse sm:flex-row items-center sm:justify-end gap-4 animate-enter" style="--stagger: 6;">
    <a href="{{ route('admin.catalog.index') }}" class="btn-cancel w-full sm:w-auto">Descartar</a>
    <button type="submit" class="btn-submit w-full sm:w-auto">
      {{ $isEdit ? 'Guardar Cambios' : 'Registrar Producto' }}
    </button>
  </div>
</form>

@if($isEdit)
  <div class="w-full mt-12 animate-enter" style="--stagger: 7;">
    <h3 class="section-heading mb-6">Sincronización Multicanal</h3>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 w-full">

      {{-- Mercado Libre --}}
      <div class="integration-panel group">
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
              <img src="https://http2.mlstatic.com/frontend-assets/ml-web-navigation/ui-navigation/5.21.22/mercadolibre/logo__small.png" alt="ML" class="h-4 object-contain">
            </div>
            <div>
              <h4 class="font-semibold text-slate-900 text-base m-0">Mercado Libre</h4>
              <span class="text-xs text-emerald-600 font-medium">Conectado</span>
            </div>
          </div>

          <a href="{{ route('admin.catalog.meli.view', $item) }}" target="_blank" class="text-slate-400 hover:text-indigo-600 transition-colors">
            <span class="material-symbols-outlined">open_in_new</span>
          </a>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
          <form method="POST" action="{{ route('admin.catalog.meli.publish', $item) }}" class="flex-1">
            @csrf
            <button type="submit" class="btn-sync bg-yellow-400 hover:bg-yellow-500 text-yellow-900">Sincronizar Listado</button>
          </form>

          <div class="flex gap-3">
            <form method="POST" action="{{ route('admin.catalog.meli.pause', $item) }}">
              @csrf
              <button type="submit" class="btn-action-sm">
                <span class="material-symbols-outlined">pause</span>
              </button>
            </form>

            <form method="POST" action="{{ route('admin.catalog.meli.activate', $item) }}">
              @csrf
              <button type="submit" class="btn-action-sm">
                <span class="material-symbols-outlined">play_arrow</span>
              </button>
            </form>
          </div>
        </div>
      </div>

      {{-- Amazon --}}
      <div class="integration-panel relative overflow-hidden group">
        @if(!$hasSku)
          <div class="absolute inset-0 bg-white/90 backdrop-blur-sm z-10 flex flex-col justify-center items-center text-center p-6">
            <span class="material-symbols-outlined text-slate-400 text-3xl mb-1">lock</span>
            <p class="text-sm font-semibold text-slate-900 m-0">SKU Requerido</p>
          </div>
        @endif

        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-slate-900 flex items-center justify-center">
              <svg viewBox="0 0 100 30" class="h-3 w-auto">
                <path d="M60.2 18.6c-2.4 1.8-5.8 2.8-9.4 2.8-6.6 0-11.9-3.4-15.6-8.9-.6-.9-1.8-1-2.6-.2l-2.7 2.7c-.8.8-.9 2-.2 2.9 4.8 6.7 11.9 10.9 20.3 10.9 4.9 0 9.7-1.4 13.9-4.2 1-.7 1.2-2 .5-2.9l-2-2.3c-.6-.7-1.5-.9-2.2-.4z" fill="#FF9900"/>
                <path d="M84.2 22.4c-1.3-.7-2.9-1.2-4.6-1.5-2-.3-3.6-.9-4.7-1.6-1-.7-1.6-1.6-1.6-2.8 0-1.4.6-2.6 1.8-3.3 1.2-.7 2.8-1.1 4.7-1.1 1.9 0 3.6.4 4.8 1.1 1.1.7 1.8 1.8 1.9 3.2.1 1 .9 1.8 1.9 1.8h3.4c1.1 0 1.9-.9 1.8-2-.2-2.7-1.6-4.9-3.9-6.3-2.3-1.4-5.3-2.1-8.9-2.1-3.7 0-6.8.8-9.1 2.2-2.3 1.5-3.5 3.6-3.5 6.4 0 2.2.8 4 2.4 5.3 1.6 1.3 4 2.3 7 2.8 2.6.5 4.5 1.1 5.7 1.8 1.2.7 1.8 1.8 1.8 3 0 1.5-.7 2.8-2.1 3.6-1.4.8-3.2 1.2-5.4 1.2-2.2 0-4.2-.5-5.6-1.4-1.4-.9-2.3-2.3-2.5-4-.1-1-.9-1.8-1.9-1.8h-3.6c-1.1 0-1.9.9-1.8 2 .3 3.1 2 5.5 4.8 7 2.8 1.5 6.4 2.3 10.7 2.3 4.2 0 7.6-.8 10-2.3 2.5-1.5 3.8-3.7 3.8-6.5 0-2-.8-3.7-2.4-5z" fill="#ffffff"/>
              </svg>
            </div>
            <div>
              <h4 class="font-semibold text-slate-900 text-base m-0">Amazon Seller</h4>
              <span class="text-xs text-slate-500 font-medium">SP-API V2</span>
            </div>
          </div>

          <a href="{{ route('admin.catalog.amazon.view', $item) }}" target="_blank" class="text-slate-400 hover:text-indigo-600 transition-colors" @if(!$hasSku) style="pointer-events:none;" @endif>
            <span class="material-symbols-outlined">open_in_new</span>
          </a>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
          <form method="POST" action="{{ route('admin.catalog.amazon.publish', $item) }}" class="flex-1">
            @csrf
            <button type="submit" class="btn-sync bg-slate-900 hover:bg-slate-800 text-white" @disabled(!$hasSku)>Sincronizar Listado</button>
          </form>

          <div class="flex gap-3">
            <form method="POST" action="{{ route('admin.catalog.amazon.pause', $item) }}">
              @csrf
              <button type="submit" class="btn-action-sm" @disabled(!$hasSku)>
                <span class="material-symbols-outlined">pause</span>
              </button>
            </form>

            <form method="POST" action="{{ route('admin.catalog.amazon.activate', $item) }}">
              @csrf
              <button type="submit" class="btn-action-sm" @disabled(!$hasSku)>
                <span class="material-symbols-outlined">play_arrow</span>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
@endif

@push('styles')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<style>
  .enterprise-ui {
    font-family: inherit;
    color: #0f172a;
    box-sizing: border-box;
  }
  .enterprise-ui *, .enterprise-ui *::before, .enterprise-ui *::after { box-sizing: inherit; }

  .w-full { width: 100%; } .flex { display: flex; } .flex-col { flex-direction: column; } .items-center { align-items: center; } .justify-between { justify-content: space-between; } .flex-1 { flex: 1 1 0%; } .flex-wrap { flex-wrap: wrap; } .shrink-0 { flex-shrink: 0; }
  .grid { display: grid; } .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); } .grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  @media (min-width: 640px) { .sm\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); } .sm\:flex-row { flex-direction: row; } .sm\:w-auto { width: auto; } .sm\:justify-end { justify-content: flex-end; } }
  @media (min-width: 768px) { .md\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); } .md\:p-8 { padding: 2rem; } }
  @media (min-width: 1024px) { .lg\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  @media (min-width: 1280px) { .xl\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); } .xl\:col-span-2 { grid-column: span 2 / span 3; } .xl\:col-span-1 { grid-column: span 1 / span 3; } .xl\:flex-row { flex-direction: row; } .xl\:w-80 { width: 20rem; } .xl\:justify-end { justify-content: flex-end; } }

  .gap-2 { gap: 0.5rem; } .gap-3 { gap: 0.75rem; } .gap-4 { gap: 1rem; } .gap-6 { gap: 1.5rem; } .gap-8 { gap: 2rem; }
  .m-0 { margin: 0; } .mt-4 { margin-top: 1rem; } .mt-12 { margin-top: 3rem; } .mb-2 { margin-bottom: 0.5rem; } .mb-4 { margin-bottom: 1rem; } .mb-5 { margin-bottom: 1.25rem; } .mb-6 { margin-bottom: 1.5rem; } .mb-8 { margin-bottom: 2rem; }
  .p-4 { padding: 1rem; } .p-6 { padding: 1.5rem; }

  .text-xs { font-size: 0.75rem; line-height: 1rem; } .text-sm { font-size: 0.875rem; line-height: 1.25rem; } .text-base { font-size: 1rem; line-height: 1.5rem; } .text-lg { font-size: 1.125rem; line-height: 1.75rem; } .text-xl { font-size: 1.25rem; line-height: 1.75rem; }
  .font-medium { font-weight: 500; } .font-semibold { font-weight: 600; } .tracking-tight { letter-spacing: -0.025em; } .tracking-wider { letter-spacing: 0.05em; } .uppercase { text-transform: uppercase; } .text-right { text-align: right; }
  .text-slate-300 { color: #cbd5e1; } .text-slate-400 { color: #94a3b8; } .text-slate-500 { color: #64748b; } .text-slate-600 { color: #475569; } .text-slate-800 { color: #1e293b; } .text-slate-900 { color: #0f172a; } .text-red-500 { color: #ef4444; } .text-red-700 { color: #b91c1c; } .text-emerald-600 { color: #059669; } .text-indigo-500 { color: #6366f1; } .text-indigo-600 { color: #4f46e5; }

  .bg-white { background-color: #ffffff; } .bg-slate-50 { background-color: #f8fafc; } .bg-slate-100 { background-color: #f1f5f9; } .bg-slate-900 { background-color: #0f172a; } .bg-yellow-100 { background-color: #fef9c3; } .bg-yellow-400 { background-color: #facc15; } .bg-yellow-500 { background-color: #eab308; } .bg-transparent { background-color: transparent; }
  .border { border-width: 1px; border-style: solid; } .border-slate-200 { border-color: #e2e8f0; } .border-b { border-bottom-width: 1px; } .border-collapse { border-collapse: collapse; }
  .rounded-xl { border-radius: 0.75rem; } .rounded-full { border-radius: 9999px; } .overflow-x-auto { overflow-x: auto; } .overflow-hidden { overflow: hidden; }

  .relative { position: relative; } .absolute { position: absolute; } .inset-0 { top: 0; right: 0; bottom: 0; left: 0; }

  .hidden { display: none; } .empty\:hidden:empty { display: none; } .pointer-events-none { pointer-events: none; } .cursor-pointer { cursor: pointer; } .resize-y { resize: vertical; } .resize-none { resize: none; } .object-contain { object-fit: contain; }
  .transition-transform { transition-property: transform; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 200ms; } .transition-colors { transition-property: color, background-color, border-color, text-decoration-color, fill, stroke; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 200ms; }
  .group:hover .group-hover\:scale-105 { transform: scale(1.05); } .group:hover .group-hover\:text-indigo-500 { color: #6366f1; } .group:hover .group-hover\:text-indigo-600 { color: #4f46e5; }

  .ai-copilot-wrapper { position: relative; border-radius: 16px; overflow: hidden; padding: 1px; }
  .ai-copilot-border { position: absolute; inset: 0; background: linear-gradient(90deg, #e2e8f0, #e2e8f0); z-index: 0; transition: background 0.5s ease; }
  .ai-copilot-wrapper:hover .ai-copilot-border, .ai-copilot-wrapper.is-active .ai-copilot-border { background: linear-gradient(90deg, #818cf8, #c084fc, #34d399); animation: borderGlow 3s linear infinite; }
  .ai-copilot-content { border-radius: 15px; z-index: 1; height: 100%; }

  @keyframes borderGlow { 0% { filter: hue-rotate(0deg); } 100% { filter: hue-rotate(360deg); } }
  .bg-clip-text { -webkit-background-clip: text; background-clip: text; } .text-transparent { color: transparent; } .from-indigo-500 { --tw-gradient-from: #6366f1; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(99, 102, 241, 0)); } .to-purple-500 { --tw-gradient-to: #a855f7; } .bg-gradient-to-r { background-image: linear-gradient(to right, var(--tw-gradient-stops)); }
  .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; } @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }

  .badge-ai { font-size: 0.65rem; font-weight: 700; background: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px; border: 1px solid #e2e8f0; }

  .dropzone-minimal { border: 1px dashed #cbd5e1; border-radius: 8px; background: #f8fafc; padding: 0.75rem; position: relative; cursor: pointer; transition: all 0.2s; }
  .dropzone-minimal:hover, .dropzone-minimal.is-dragover { border-color: #6366f1; background: #eef2ff; }
  .hidden-input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 10; }

  .btn-ai-action { background: #0f172a; color: #fff; font-family: inherit; font-size: 0.875rem; font-weight: 500; border: none; border-radius: 8px; padding: 0.75rem 1.25rem; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; }
  .btn-ai-action:hover:not(:disabled) { background: #1e293b; transform: translateY(-1px); }

  .form-section { background: transparent; border-top: 1px solid #e2e8f0; padding-top: 1.5rem; }
  .form-section:first-child { border-top: none; padding-top: 0; }
  .section-heading { font-size: 0.875rem; font-weight: 600; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 1.5rem 0; }

  .form-group { margin-bottom: 1.5rem; position: relative; }
  .form-label { display: block; font-size: 0.8125rem; font-weight: 500; color: #475569; margin-bottom: 0.5rem; }
  .form-input, .form-select { width: 100%; background: transparent; border: none; border-bottom: 1px solid #cbd5e1; padding: 0.5rem 0; font-family: inherit; font-size: 0.95rem; color: #0f172a; transition: all 0.2s; border-radius: 0; box-shadow: none; outline: none; }
  .form-input::placeholder { color: #94a3b8; }
  .form-input:focus, .form-select:focus { border-bottom-color: #0f172a; }
  .form-input.pl-8 { padding-left: 2rem; }

  .form-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0 center;
    background-repeat: no-repeat;
    background-size: 1.2em 1.2em;
    padding-right: 2rem;
  }

  .ai-badge { font-size: 0.7rem; color: #059669; font-weight: 500; background: #ecfdf5; padding: 2px 6px; border-radius: 4px; animation: fadeIn 0.3s ease; }
  .ai-suggested { border-bottom-color: #10b981 !important; background: linear-gradient(0deg, rgba(16, 185, 129, 0.05) 0%, transparent 100%); transition: all 0.5s ease; }

  .media-box { position: relative; }
  .media-area { display: flex; flex-direction: column; cursor: pointer; }
  .media-preview { width: 100%; aspect-ratio: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 0.75rem; transition: background 0.2s; }
  .media-area:hover .media-preview { background: #f1f5f9; }
  .media-area:hover .media-preview .material-symbols-outlined { transform: scale(1.1); color: #64748b; }
  .media-preview img { width: 100%; height: 100%; object-fit: contain; }
  .media-info { display: flex; flex-direction: column; }
  .media-title { font-size: 0.875rem; font-weight: 500; color: #0f172a; }
  .media-subtitle { font-size: 0.75rem; color: #64748b; }

  .media-clear { position: absolute; top: 8px; right: 8px; width: 24px; height: 24px; border-radius: 50%; background: #ffffff; border: 1px solid #e2e8f0; color: #0f172a; display: none; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
  .media-clear:hover { background: #f1f5f9; }
  .media-box.has-media .media-clear { display: flex; }
  .media-box.has-media .media-preview { background: transparent; border: 1px solid #e2e8f0; }

  .sticky-footer { position: sticky; bottom: 0; padding: 1.5rem 0; background: linear-gradient(0deg, #ffffff 50%, rgba(255,255,255,0.8) 100%); backdrop-filter: blur(8px); border-top: 1px solid #e2e8f0; z-index: 40; margin-top: 2rem; }
  .btn-submit { background: #0f172a; color: #fff; border: none; font-family: inherit; font-size: 0.95rem; font-weight: 500; padding: 0.875rem 2rem; border-radius: 8px; cursor: pointer; transition: 0.2s; }
  .btn-submit:hover { background: #1e293b; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(15,23,42,0.15); }
  .btn-cancel { background: transparent; color: #475569; border: none; font-family: inherit; font-size: 0.95rem; font-weight: 500; padding: 0.875rem 1.5rem; cursor: pointer; transition: 0.2s; text-decoration: none; text-align: center; }
  .btn-cancel:hover { color: #0f172a; background: #f8fafc; border-radius: 8px; }

  .integration-panel { border: 1px solid #e2e8f0; border-radius: 16px; padding: 1.5rem; background: #ffffff; transition: border-color 0.2s; }
  .integration-panel:hover { border-color: #cbd5e1; }
  .btn-sync { display: flex; align-items: center; justify-content: center; width: 100%; border: none; font-family: inherit; font-size: 0.875rem; font-weight: 500; padding: 0.625rem 1rem; border-radius: 8px; cursor: pointer; transition: 0.2s; }
  .btn-action-sm { display: flex; align-items: center; justify-content: center; background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; width: 2.5rem; height: 2.5rem; border-radius: 8px; cursor: pointer; transition: 0.2s; }
  .btn-action-sm:hover { background: #f1f5f9; color: #0f172a; border-color: #cbd5e1; }

  .animate-enter { opacity: 0; animation: enterSlide 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; animation-delay: calc(var(--stagger) * 0.08s); }
  @keyframes enterSlide { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
  .fade-in { animation: fadeIn 0.3s ease forwards; } @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
  .spinner { animation: spin 1s linear infinite; } @keyframes spin { 100% { transform: rotate(360deg); } }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  const UI = {
    toast: Swal.mixin({
      toast: true,
      position: 'bottom-center',
      showConfirmButton: false,
      timer: 4000,
      background: '#0f172a',
      color: '#fff',
      customClass: { popup: 'rounded-xl shadow-lg border border-slate-700' }
    }),
    success: (msg) => UI.toast.fire({ icon: 'success', title: msg, iconColor: '#34d399' }),
    error: (msg) => UI.toast.fire({ icon: 'error', title: msg, iconColor: '#f87171' }),
    escape: (str) => String(str || '').replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m])),
    money: (v) => isNaN(Number(v)) ? '—' : `$${Number(v).toFixed(2)}`
  };

  document.addEventListener('DOMContentLoaded', () => {
    ['1','2','3'].forEach(i => {
      const inp = document.getElementById(`photo_${i}_file`);
      const box = document.querySelector(`[data-photo-card="photo_${i}_file"]`);
      const prev = document.getElementById(`photo_${i}_preview`);
      const text = document.querySelector(`[data-photo-strong="photo_${i}_file"]`);
      const clrBtn = document.querySelector(`[data-photo-clear="photo_${i}_file"]`);
      let objUrl = null;

      const updateUI = (hasFile, file = null) => {
        if (box) box.classList.toggle('has-media', hasFile);
        if (text) text.textContent = file ? file.name : `Foto ${i}`;
      };

      if (prev && prev.querySelector('img')) updateUI(true);

      inp?.addEventListener('change', e => {
        const file = e.target.files[0];
        updateUI(!!file, file);

        if (objUrl) URL.revokeObjectURL(objUrl);

        if (!file) {
          if (prev) prev.innerHTML = '<span class="material-symbols-outlined text-slate-300 text-3xl transition-transform">add_photo_alternate</span>';
          return;
        }

        objUrl = URL.createObjectURL(file);
        if (prev) prev.innerHTML = `<img src="${objUrl}" class="fade-in">`;
      });

      clrBtn?.addEventListener('click', e => {
        e.preventDefault();
        if (inp) inp.value = '';
        updateUI(false);
        if (prev) prev.innerHTML = '<span class="material-symbols-outlined text-slate-300 text-3xl transition-transform">add_photo_alternate</span>';
      });
    });
  });

  document.addEventListener('DOMContentLoaded', () => {
    const els = {
      wrapper: document.querySelector('.ai-copilot-wrapper'),
      dropzone: document.getElementById('ai-dropzone'),
      input: document.getElementById('ai_files'),
      list: document.getElementById('ai-files-list'),
      btnAnalyze: document.getElementById('btn-ai-analyze'),
      spinner: document.getElementById('ai-spinner'),
      btnText: document.getElementById('ai-btn-text'),
      dropText: document.getElementById('ai-drop-text'),
      panel: document.getElementById('ai-items-panel'),
      tbody: document.getElementById('ai-items-tbody'),
      btnClear: document.getElementById('ai-clear-list')
    };

    if (!els.dropzone) return;

    let aiState = JSON.parse(localStorage.getItem('cat_ai') || '{"items":[]}');
    const saveState = () => localStorage.setItem('cat_ai', JSON.stringify(aiState));

    const renderFiles = (files) => {
      els.list.innerHTML = '';

      if (!files.length) {
        els.dropText.textContent = 'Cargar archivos';
        return;
      }

      els.dropText.textContent = `${files.length} archivo(s)`;
      els.wrapper.classList.add('is-active');

      Array.from(files).forEach(f => {
        els.list.insertAdjacentHTML('beforeend',
          `<span class="text-xs font-medium bg-slate-100 text-slate-600 px-2 py-1 rounded fade-in">${f.name}</span>`
        );
      });
    };

    els.dropzone.addEventListener('click', () => els.input.click());
    els.input.addEventListener('change', () => renderFiles(els.input.files));

    ['dragenter','dragover'].forEach(e =>
      els.dropzone.addEventListener(e, ev => {
        ev.preventDefault();
        els.dropzone.classList.add('is-dragover');
      })
    );

    ['dragleave','dragend','drop'].forEach(e =>
      els.dropzone.addEventListener(e, ev => {
        ev.preventDefault();
        els.dropzone.classList.remove('is-dragover');
      })
    );

    els.dropzone.addEventListener('drop', e => {
      const dt = new DataTransfer();
      Array.from(e.dataTransfer.files)
        .filter(f => f.type.match(/image.*|pdf/))
        .forEach(f => dt.items.add(f));

      if (dt.files.length) {
        els.input.files = dt.files;
        renderFiles(dt.files);
      }
    });

    const renderTable = () => {
      els.tbody.innerHTML = '';

      if (!aiState.items.length) {
        els.panel.style.display = 'none';
        return;
      }

      aiState.items.forEach((item, i) => {
        const brandModel = [item.brand_name, item.model_name].filter(Boolean).join(' / ') || '—';

        els.tbody.insertAdjacentHTML('beforeend', `
          <tr class="fade-in border-b border-slate-100 last:border-none" style="animation-delay: ${i * 0.05}s">
            <td class="p-4 text-sm text-slate-400 font-medium">${i + 1}</td>
            <td class="p-4 text-sm font-medium text-slate-900">${UI.escape(item.name) || 'Sin título'}</td>
            <td class="p-4 text-sm font-semibold">${UI.money(item.price)}</td>
            <td class="p-4 text-sm text-slate-500">${brandModel}</td>
            <td class="p-4"><span class="font-mono text-xs bg-slate-100 px-2 py-1 rounded text-slate-600">${UI.escape(item.meli_gtin) || '—'}</span></td>
            <td class="p-4 text-right">
              <button type="button" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium bg-transparent border-none cursor-pointer btn-use" data-idx="${i}">
                Seleccionar
              </button>
            </td>
          </tr>
        `);
      });

      els.panel.style.display = 'block';

      document.querySelectorAll('.btn-use').forEach(btn => {
        btn.addEventListener('click', e => {
          applyToForm(aiState.items[e.target.dataset.idx]);
          UI.success('Datos transferidos');
        });
      });
    };

    const applyToForm = (item) => {
      if (!item) return;

      const map = {
        name: item.name,
        slug: item.slug,
        description: item.description,
        excerpt: item.excerpt,
        price: item.price,
        brand_name: item.brand_name,
        model_name: item.model_name,
        meli_gtin: item.meli_gtin,
        stock: item.stock ?? item.quantity
      };

      Object.entries(map).forEach(([name, val]) => {
        if (val == null || val === '') return;

        const el = document.querySelector(`[name="${name}"]`);
        if (el) {
          el.value = val;
          el.classList.add('ai-suggested');

          const badge = el.closest('.form-group')?.querySelector('.ai-badge');
          if (badge) {
            badge.classList.remove('hidden');
            setTimeout(() => badge.classList.add('hidden'), 5000);
          }

          setTimeout(() => el.classList.remove('ai-suggested'), 2000);
        }
      });
    };

    els.btnAnalyze.addEventListener('click', async () => {
      if (!els.input.files.length) {
        return UI.toast.fire({ icon: 'info', title: 'Agrega un archivo' });
      }

      const fd = new FormData();
      Array.from(els.input.files).forEach(f => fd.append('files[]', f));
      fd.append('_token', '{{ csrf_token() }}');

      els.btnAnalyze.disabled = true;
      els.spinner.classList.remove('hidden');
      els.btnText.textContent = 'Procesando...';
      els.wrapper.classList.add('is-active');
      els.panel.style.display = 'none';

      try {
        const res = await fetch("{{ route('admin.catalog.ai-from-upload') }}", {
          method: 'POST',
          body: fd
        });

        const data = await res.json();
        if (data.error) throw new Error(data.error);

        if (data.suggestions) applyToForm(data.suggestions);

        aiState.items = Array.isArray(data.items) ? data.items : [];
        saveState();
        renderTable();

        UI.success('Análisis exitoso');
      } catch (e) {
        UI.error(e.message || 'Error al analizar');
      } finally {
        els.btnAnalyze.disabled = false;
        els.spinner.classList.add('hidden');
        els.btnText.textContent = 'Analizar Documento';
        els.wrapper.classList.remove('is-active');
      }
    });

    els.btnClear?.addEventListener('click', () => {
      aiState.items = [];
      saveState();
      renderTable();
      els.input.value = '';
      els.list.innerHTML = '';
      els.dropText.textContent = 'Cargar archivos';
    });

    if (aiState.items.length) {
      renderTable();
      applyToForm(aiState.items[0]);
    }
  });
</script>

@if(session('ok'))
<script>
  document.addEventListener('DOMContentLoaded', () => {
    localStorage.removeItem('cat_ai');
    Swal.fire({
      icon: 'success',
      title: 'Guardado',
      text: @json(session('ok')),
      confirmButtonText: 'Continuar',
      confirmButtonColor: '#0f172a',
      customClass: { popup: 'rounded-xl shadow-2xl border border-slate-100 font-sans' }
    });
  });
</script>
@endif

@if($errors->any())
<script>
  document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({
      icon: 'error',
      title: 'Revisa los campos',
      html: `<div class="text-left text-sm text-slate-600 mt-2 space-y-1">• {!! implode('<br>• ', $errors->all()) !!}</div>`,
      confirmButtonText: 'Entendido',
      confirmButtonColor: '#0f172a',
      customClass: { popup: 'rounded-xl shadow-2xl border border-slate-100 font-sans' }
    });
  });
</script>
@endif
@endpush