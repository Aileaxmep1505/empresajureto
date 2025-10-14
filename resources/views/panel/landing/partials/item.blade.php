@php
  $id = $it['id'] ?? null;
  $title = $it['title'] ?? '';
  $subtitle = $it['subtitle'] ?? '';
  $cta_text = $it['cta_text'] ?? '';
  $cta_url = $it['cta_url'] ?? '';
  $imgUrl = isset($it['image_path']) && $it['image_path'] ? asset('storage/'.$it['image_path']) : asset('images/placeholder.png');
@endphp

<div class="col-md-4" data-item>
  <div class="preview-card p-2 h-100">
    <div class="mb-2">
      <img data-preview-img class="preview-img" src="{{ $imgUrl }}" alt="preview">
    </div>

    @if($id)
      <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $id }}">
      <input type="hidden" name="items[{{ $idx }}][_delete]" value="0">
    @endif

    <div class="mb-2">
      <label class="form-label small">Imagen</label>
      <input type="file" class="form-control" name="items[{{ $idx }}][image]" accept="image/*" data-preview>
    </div>

    <div class="mb-2">
      <label class="form-label small">Título</label>
      <input class="form-control" name="items[{{ $idx }}][title]" value="{{ $title }}">
    </div>

    <div class="mb-2">
      <label class="form-label small">Subtítulo</label>
      <input class="form-control" name="items[{{ $idx }}][subtitle]" value="{{ $subtitle }}">
    </div>

    <div class="mb-2">
      <label class="form-label small">Texto del botón</label>
      <input class="form-control" name="items[{{ $idx }}][cta_text]" value="{{ $cta_text }}" placeholder="Conoce más">
    </div>

    <div class="mb-2">
      <label class="form-label small">URL del botón</label>
      <input class="form-control" name="items[{{ $idx }}][cta_url]" value="{{ $cta_url }}" placeholder="https://...">
    </div>

    <button type="button" class="btn btn-sm btn-outline-danger w-100" data-delete-item>Quitar</button>
  </div>
</div>
