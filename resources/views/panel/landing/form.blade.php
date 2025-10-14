@extends('layouts.app')
@section('title', $mode==='create' ? 'Nueva Sección' : 'Editar Sección')

@section('content')
<div class="container">
  @if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif
  @if($errors->any()) <div class="alert alert-danger">Hay errores en el formulario.</div> @endif

  <form method="POST" enctype="multipart/form-data"
        action="{{ $mode==='create' ? route('panel.landing.store') : route('panel.landing.update',$section) }}">
    @csrf
    @if($mode==='edit') @method('PUT') @endif

    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nombre de la sección</label>
            <input class="form-control" name="name" value="{{ old('name',$section->name) }}" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Layout</label>
            <select class="form-select" name="layout" id="layoutSelect" required>
              @foreach($layouts as $val => $label)
                <option value="{{ $val }}" @selected(old('layout',$section->layout)===$val)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <label class="form-check ms-2">
              <input type="checkbox" class="form-check-input" name="is_active"
                     @checked(old('is_active',$section->is_active))> Activa
            </label>
          </div>
        </div>
      </div>
    </div>

    {{-- Repeater de items --}}
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <strong>Bloques (imágenes + botón)</strong>
          <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn">Agregar bloque</button>
        </div>

        <div id="itemsContainer" class="row g-3">
          @php $items = old('items', $section->items?->toArray() ?? []); @endphp
          @foreach($items as $idx => $it)
            @include('panel.landing.partials.item', ['idx'=>$idx, 'it'=>$it])
          @endforeach
        </div>

        <small class="text-muted">Tip: en <em>banner ancho</em> usa 1 ítem; en <em>grid-3</em> usa 3 ítems.</small>
      </div>
    </div>

    <div class="text-end">
      <a href="{{ route('panel.landing.index') }}" class="btn btn-outline-secondary">Cancelar</a>
      <button class="btn btn-primary">{{ $mode==='create' ? 'Crear' : 'Guardar cambios' }}</button>
    </div>
  </form>
</div>

{{-- Template oculto para nuevos items --}}
<template id="tplItem">
  @include('panel.landing.partials.item', ['idx'=>'__IDX__','it'=>[]])
</template>

{{-- Preview rápido del layout (CSS simple minimalista) --}}
<style>
  .preview-grid{ display:grid; gap:12px; }
  .preview-grid.grid-1{ grid-template-columns:1fr; }
  .preview-grid.grid-2{ grid-template-columns:repeat(2,1fr); }
  .preview-grid.grid-3{ grid-template-columns:repeat(3,1fr); }
  .preview-card{ border:1px dashed #cbd5e1; border-radius:12px; padding:8px; background:#f8fafc; }
  .preview-img{ height:120px; width:100%; object-fit:cover; border-radius:8px; }
</style>

<script>
(function(){
  const itemsContainer = document.getElementById('itemsContainer');
  const addItemBtn = document.getElementById('addItemBtn');
  const tpl = document.getElementById('tplItem').innerHTML;

  addItemBtn?.addEventListener('click', () => {
    const nextIdx = itemsContainer.querySelectorAll('[data-item]').length;
    const html = tpl.replaceAll('__IDX__', nextIdx);
    itemsContainer.insertAdjacentHTML('beforeend', html);
  });

  // preview de imagen al seleccionar archivo
  document.addEventListener('change', (e) => {
    if(e.target.matches('input[type=file][data-preview]')){
      const input = e.target;
      const url = URL.createObjectURL(input.files[0]);
      const img = input.closest('[data-item]').querySelector('[data-preview-img]');
      if(img){ img.src = url; }
    }
    if(e.target.matches('[data-delete-item]')){
      const wrap = e.target.closest('[data-item]');
      if(wrap){
        // soft delete si existe id
        const idInput = wrap.querySelector('input[name$="[id]"]');
        if(idInput && idInput.value){
          wrap.querySelector('input[name$="[_delete]"]').value = 1;
          wrap.style.opacity = .4;
        } else {
          wrap.remove();
        }
      }
    }
  });

})();
</script>
@endsection
