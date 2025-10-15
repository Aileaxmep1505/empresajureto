@php
  /** @var \App\Models\CatalogItem|null $item */
  $isEdit = isset($item);
@endphp

@csrf
@if($isEdit)
  @method('PUT')
@endif

<div class="grid" style="display:grid;gap:16px;grid-template-columns:repeat(12,1fr)">
  <div style="grid-column:span 8;">
    <label class="lbl">Nombre *</label>
    <input name="name" class="inp" required value="{{ old('name', $item->name ?? '') }}">

    <label class="lbl">Slug (opcional)</label>
    <input name="slug" class="inp" value="{{ old('slug', $item->slug ?? '') }}">

    <label class="lbl">Descripción</label>
    <textarea name="description" class="inp" rows="6">{{ old('description', $item->description ?? '') }}</textarea>

    <label class="lbl">Extracto</label>
    <textarea name="excerpt" class="inp" rows="3">{{ old('excerpt', $item->excerpt ?? '') }}</textarea>
  </div>

  <div style="grid-column:span 4;">
    <label class="lbl">SKU</label>
    <input name="sku" class="inp" value="{{ old('sku', $item->sku ?? '') }}">

    <label class="lbl">Precio *</label>
    <input name="price" type="number" step="0.01" min="0" class="inp" required value="{{ old('price', $item->price ?? 0) }}">

    <label class="lbl">Precio oferta</label>
    <input name="sale_price" type="number" step="0.01" min="0" class="inp" value="{{ old('sale_price', $item->sale_price ?? '') }}">

    <label class="lbl">Estado *</label>
    <select name="status" class="inp" required>
      @php $st = (string)old('status', isset($item)? (string)$item->status : '0'); @endphp
      <option value="0" @selected($st==='0')>Borrador</option>
      <option value="1" @selected($st==='1')>Publicado</option>
      <option value="2" @selected($st==='2')>Oculto</option>
    </select>

    <label class="lbl">Publicado en</label>
    <input name="published_at" type="datetime-local" class="inp"
           value="{{ old('published_at', isset($item->published_at)? $item->published_at->format('Y-m-d\TH:i') : '') }}">

    <label class="lbl">Destacado (para Home)</label>
    <label style="display:flex;gap:8px;align-items:center">
      <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $item->is_featured ?? false))> Sí
    </label>

    <label class="lbl">Marca (ID)</label>
    <input name="brand_id" type="number" class="inp" value="{{ old('brand_id', $item->brand_id ?? '') }}">

    <label class="lbl">Categoría (ID)</label>
    <input name="category_id" type="number" class="inp" value="{{ old('category_id', $item->category_id ?? '') }}">
  </div>
</div>

<hr class="divi">

<label class="lbl">Imagen de portada (URL)</label>
<input name="image_url" class="inp" value="{{ old('image_url', $item->image_url ?? '') }}">

<label class="lbl">Imágenes adicionales (URLs)</label>
<div id="images-list" style="display:flex; flex-direction:column; gap:8px;">
  @php
    $imgs = old('images', $item->images ?? []);
    if (!is_array($imgs)) { $imgs = []; }
  @endphp
  @forelse($imgs as $i => $url)
    <div class="img-row">
      <input name="images[{{ $i }}]" class="inp" value="{{ $url }}">
      <button type="button" class="btn btn-ghost" onclick="this.parentElement.remove()">Quitar</button>
    </div>
  @empty
    <div class="img-row">
      <input name="images[0]" class="inp" placeholder="https://...">
      <button type="button" class="btn btn-ghost" onclick="this.parentElement.remove()">Quitar</button>
    </div>
  @endforelse
</div>
<button type="button" class="btn" onclick="addImageRow()">+ Agregar imagen</button>

<div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap;">
  <button class="btn btn-primary" type="submit">{{ $isEdit ? 'Guardar cambios' : 'Crear producto' }}</button>
  <a class="btn btn-ghost" href="{{ route('admin.catalog.index') }}">Cancelar</a>
</div>

@push('styles')
<style>
  :root{--ink:#0e1726;--muted:#65748b;--line:#e8eef6;--surface:#fff;--brand:#6ea8fe;--shadow:0 10px 24px rgba(13,23,38,.06)}
  .lbl{display:block;font-weight:800;color:var(--ink);margin:10px 0 6px}
  .inp{width:100%;background:#fff;border:1px solid var(--line);border-radius:12px;padding:10px 12px;min-height:42px}
  .btn{border:0;border-radius:12px;padding:10px 14px;font-weight:700;cursor:pointer}
  .btn-primary{background:var(--brand);box-shadow:0 8px 18px rgba(29,78,216,.12)}
  .btn-ghost{background:#fff;border:1px solid var(--line)}
  .divi{border:none;border-top:1px solid var(--line);margin:16px 0}
  .img-row{display:flex; gap:8px; align-items:center}
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
</script>
@endpush
