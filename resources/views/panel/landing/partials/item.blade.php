@php
  // idx: índice del bloque
  // it:  array con datos del bloque (title, subtitle, cta_text, cta_url, image_path, id)
  $existing = $it['image_path'] ?? '';
@endphp

<div class="item" data-item draggable="true" @if($existing) data-existing="{{ Storage::disk('public')->url($existing) }}" @endif>
  <div class="item-head">
    <div class="item-handle" title="Arrastrar para reordenar">
      <span class="mi">drag_indicator</span>
      <span class="dots">•••</span>
      <strong>Bloque</strong>
    </div>
    <div>
      <button type="button" class="item-del" data-delete-item>
        <span class="mi">delete</span> Eliminar
      </button>
    </div>
  </div>

  <div class="item-body">
    {{-- Dropzone imagen --}}
    <div class="drop @if($existing) has-img @endif">
      <input type="file" accept="image/*" name="items[{{ $idx }}][image]" data-preview>
      <img src="@if($existing){{ Storage::disk('public')->url($existing) }}@endif" alt="Imagen">
      <div class="ph">
        <div class="mi" style="font-size:28px">image</div>
        <div><strong>Arrastra una imagen</strong> o haz clic para seleccionar</div>
        <small>JPG/PNG hasta 4 MB</small>
      </div>
    </div>

    {{-- Campos --}}
    <div>
      <div class="ls-field">
        <label>Título</label>
        <input class="ls-input" name="items[{{ $idx }}][title]" value="{{ old("items.$idx.title", $it['title'] ?? '') }}" placeholder="Ej. Equipos de laparoscopía">
      </div>

      <div class="ls-field">
        <label>Subtítulo</label>
        <textarea class="ls-textarea" rows="2" name="items[{{ $idx }}][subtitle]" placeholder="Texto descriptivo corto">{{ old("items.$idx.subtitle", $it['subtitle'] ?? '') }}</textarea>
      </div>

      <div class="subgrid">
        <div class="ls-field">
          <label>Texto del botón</label>
          <input class="ls-input" name="items[{{ $idx }}][cta_text]" value="{{ old("items.$idx.cta_text", $it['cta_text'] ?? '') }}" placeholder="Ver más / Comprar ahora">
        </div>
        <div class="ls-field">
          <label>URL del botón</label>
          <input class="ls-input" type="url" name="items[{{ $idx }}][cta_url]" value="{{ old("items.$idx.cta_url", $it['cta_url'] ?? '') }}" placeholder="https://tusitio.com/ruta">
        </div>
      </div>

      {{-- Hidden: id + _delete para soft delete visual --}}
      <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $it['id'] ?? '' }}">
      <input type="hidden" name="items[{{ $idx }}][_delete]" value="">
    </div>
  </div>
</div>
