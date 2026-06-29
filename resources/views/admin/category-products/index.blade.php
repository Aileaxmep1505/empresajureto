@extends('layouts.app')

@section('title', 'Categorías de productos')

@section('content')

@php
  $tree = isset($treeCategories) ? collect($treeCategories) : collect($categories);
  $childrenByParent = $tree->groupBy(function ($item) {
      return $item->parent_id ?: 'root';
  });

  $roots = $childrenByParent->get('root', collect())->values();

  $totalCategories = $totalAllCategories ?? (method_exists($categories, 'total') ? $categories->total() : $tree->count());
  $activeCategories = $activeAllCategories ?? $tree->where('is_active', true)->count();
  $rootCategories = $rootAllCategories ?? $roots->count();

  $maxDepth = 0;

  $depthResolver = function ($category) use ($tree) {
      $depth = 0;
      $parentId = $category->parent_id;

      while ($parentId) {
          $parent = $tree->firstWhere('id', $parentId);
          if (!$parent) break;
          $depth++;
          $parentId = $parent->parent_id;
      }
      return $depth;
  };

  foreach ($tree as $node) {
      $maxDepth = max($maxDepth, $depthResolver($node));
  }

  // Función recursiva para Drag & Drop.
  // Importante: cada categoría SIEMPRE tiene un .tree-group hijo, aunque esté vacío.
  // Así puedes mover categorías dentro de cualquier categoría, incluso si todavía no tiene hijos.
  $renderTree = function ($parentKey, $level = 0) use (&$renderTree, $childrenByParent) {
      $children = $childrenByParent->get($parentKey, collect())->values();

      $html = '<div class="tree-group" data-parent-id="'.$parentKey.'">';

      foreach ($children as $category) {
          $childKey = $category->id;
          $childrenForNode = $childrenByParent->get($childKey, collect())->values();
          $hasChildren = $childrenForNode->count() > 0;
          $childrenCount = $childrenForNode->count();

          $html .= '<div class="tree-item" data-id="'.$category->id.'">';

          $html .= '<div class="tree-node '.($level === 0 ? 'root-node' : '').'">';

          $html .= '<div class="node-info">';
          $html .= '<span class="drag-handle" title="Arrastrar para mover">⋮⋮</span>';

          if ($level === 0) {
              $html .= '<span class="node-icon">📦</span>';
          }

          $html .= '<span class="node-name">'.e($category->name).'</span>';

          $html .= '<div class="node-badges">';
          $html .= '<span class="cat-badge cat-badge-muted">'.(int)($category->catalog_items_count ?? 0).' prods</span>';

          if ($hasChildren) {
              $html .= '<span class="cat-badge cat-badge-info">'.$childrenCount.' subcats</span>';
          } else {
              $html .= '<span class="cat-badge cat-badge-muted">Sin hijos</span>';
          }

          if ($category->is_active) {
              $html .= '<span class="cat-badge cat-badge-success">Activa</span>';
          } else {
              $html .= '<span class="cat-badge cat-badge-danger">Oculta</span>';
          }

          $html .= '</div>';
          $html .= '</div>';

          $html .= '<div class="node-actions">';
          $html .= '<a href="'.route('admin.category-products.edit', $category).'" class="cat-btn-ghost cat-btn-sm">Editar</a>';

          $html .= '<form method="POST" action="'.route('admin.category-products.destroy', $category).'" class="delete-category-form" style="margin:0;">';
          $html .= csrf_field();
          $html .= method_field('DELETE');
          $html .= '<button type="button" class="cat-btn-ghost cat-btn-danger-text cat-btn-sm btn-delete" data-name="'.e($category->name).'">Eliminar</button>';
          $html .= '</form>';

          $html .= '</div>';
          $html .= '</div>';

          $html .= '<div class="tree-children">';
          $html .= $renderTree($childKey, $level + 1);
          $html .= '</div>';

          $html .= '</div>';
      }

      $html .= '</div>';
      return $html;
  };
@endphp

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  :root {
    --bg: #f9fafb;
    --card: #ffffff;
    --ink: #333333;
    --muted: #888888;
    --line: #ebebeb;
    --blue: #007aff;
    --blue-soft: #e6f0ff;
    --success: #15803d;
    --success-soft: #e6ffe6;
    --danger: #ff4a4a;
    --danger-soft: #ffebeb;
  }

  body { margin: 0; background: var(--bg); }
  
  .cat-page { 
      min-height: 100vh; 
      padding: 48px; 
      color: var(--ink); 
      font-family: 'Quicksand', sans-serif; 
  }
  
  .cat-shell { 
      max-width: 1200px; 
      margin: 0 auto; 
  }

  /* Typography */
  h1, h2, h3, p { margin: 0; }
  .cat-title { color: #111111; font-size: 32px; font-weight: 700; letter-spacing: -0.02em; }
  .cat-subtitle { margin-top: 8px; color: var(--muted); font-size: 15px; font-weight: 500; line-height: 1.5; }
  
  /* Header Layout */
  .cat-header { 
      display: flex; 
      align-items: flex-start; 
      justify-content: space-between; 
      gap: 24px; 
      margin-bottom: 40px; 
  }
  
  /* Buttons */
  .cat-btn-primary, .cat-btn-ghost, .cat-btn-outline {
      display: inline-flex; align-items: center; justify-content: center; gap: 8px; 
      min-height: 44px; padding: 0 20px; border-radius: 8px; 
      font-family: 'Quicksand', sans-serif; font-size: 14px; font-weight: 700; 
      text-decoration: none; border: 0; cursor: pointer; transition: all 0.2s ease; 
      white-space: nowrap;
  }
  .cat-btn-sm { min-height: 36px; padding: 0 14px; font-size: 13px; }
  
  .cat-btn-primary { background: var(--blue); color: #ffffff; }
  .cat-btn-primary:hover { filter: brightness(1.05); box-shadow: 0 4px 12px rgba(0, 122, 255, 0.2); }
  
  .cat-btn-ghost { background: transparent; color: #555555; }
  .cat-btn-ghost:hover { background: #f9fafb; color: var(--ink); }
  
  .cat-btn-danger-text:hover { background: var(--danger-soft); color: var(--danger); }
  
  .cat-btn-outline { background: var(--card); color: var(--blue); border: 1px solid var(--blue); }
  .cat-btn-outline:hover { background: var(--blue-soft); }

  /* Active states */
  .cat-btn-primary:active, .cat-btn-ghost:active, .cat-btn-outline:active {
      transform: scale(0.98);
  }

  /* Cards (Resumen) */
  .cat-summary-grid { 
      display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); 
      gap: 20px; margin-bottom: 32px; 
  }
  .cat-summary-card { 
      background: var(--card); border: 1px solid var(--line); border-radius: 16px; 
      padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); 
      transition: all 0.2s ease; 
  }
  .cat-summary-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.06);
  }
  .cat-summary-label { color: var(--muted); font-size: 13px; font-weight: 600; margin-bottom: 8px; }
  .cat-summary-value { color: #111111; font-size: 28px; font-weight: 700; letter-spacing: -0.02em; }

  /* Contenedor Principal Árbol */
  .cat-flow-card { 
      background: var(--card); border: 1px solid var(--line); border-radius: 16px; 
      box-shadow: 0 4px 12px rgba(0,0,0,0.02); padding: 32px; overflow: hidden; 
  }
  .cat-flow-head { 
      display: flex; justify-content: space-between; align-items: center; 
      margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--line); 
  }
  .cat-flow-title { color: #111111; font-size: 20px; font-weight: 700; }
  .cat-flow-scroll { overflow: auto; padding-right: 12px; padding-bottom: 12px; max-height: 65vh; }

  /* Árbol Drag & Drop */
  .tree-group { display: flex; flex-direction: column; gap: 10px; min-height: 10px; }
  .tree-item { display: flex; flex-direction: column; }
  
  .tree-node {
      display: flex; justify-content: space-between; align-items: center; 
      background: var(--card); border: 1px solid var(--line); border-radius: 12px; 
      padding: 14px 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.01);
      transition: all 0.2s ease;
  }
  .tree-node:hover { 
      transform: translateY(-2px);
      border-color: #d1d5db; 
      box-shadow: 0 8px 16px rgba(0,0,0,0.04); 
  }
  .tree-node.root-node { border-color: #e5e7eb; background: #fafafa; }

  .sortable-ghost .tree-node { background: var(--blue-soft); border: 1px dashed var(--blue); opacity: 0.6; box-shadow: none; transform: none; }
  .sortable-drag { cursor: grabbing !important; }
  
  .drag-handle { 
      cursor: grab; color: #d1d5db; font-size: 18px; line-height: 1; 
      padding: 0 12px 0 0; user-select: none; transition: color 0.2s; 
  }
  .drag-handle:hover { color: var(--muted); }
  .drag-handle:active { cursor: grabbing; color: var(--blue); }

  .node-info { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
  .node-name { font-weight: 700; font-size: 15px; color: #111111; }
  .node-badges { display: flex; gap: 8px; align-items: center; margin-left: 8px; }
  
  .node-actions { display: flex; gap: 4px; opacity: 0; transition: opacity 0.2s; }
  .tree-node:hover .node-actions { opacity: 1; }

  .tree-children {
      margin-left: 28px; padding-left: 20px; border-left: 1px solid var(--line); margin-top: 10px;
      display: flex; flex-direction: column; gap: 10px;
  }

  /* Etiquetas (Badges) */
  .cat-badge { 
      display: inline-flex; padding: 4px 10px; border-radius: 8px; 
      font-size: 12px; font-weight: 700; 
  }
  .cat-badge-success { background: var(--success-soft); color: var(--success); }
  .cat-badge-danger { background: var(--danger-soft); color: var(--danger); }
  .cat-badge-info { background: var(--blue-soft); color: var(--blue); }
  .cat-badge-muted { background: var(--bg); color: var(--muted); }

  /* =========================================
     CUSTOMIZACIÓN PREMIUM SWEETALERT2
     ========================================= */
  div:where(.swal2-container) div:where(.swal2-popup.premium-modal) {
      border-radius: 16px;
      padding: 32px;
      width: 420px;
      background: var(--card);
      box-shadow: 0 24px 48px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.04);
      border: 1px solid var(--line);
      font-family: 'Quicksand', sans-serif;
  }
  
  div:where(.swal2-container) h2:where(.swal2-title.premium-title) {
      text-align: left; font-size: 20px; font-weight: 700; color: #111111;
      padding: 0; margin-bottom: 16px;
  }
  
  div:where(.swal2-container) div:where(.swal2-html-container.premium-text) {
      text-align: left; font-size: 15px; color: var(--ink);
      padding: 0; margin: 0 0 32px 0; line-height: 1.5; font-weight: 500;
  }

  .swal-secondary-text { display: block; margin-top: 12px; font-size: 14px; color: var(--muted); }
  
  div:where(.swal2-container) div:where(.swal2-actions.premium-actions) {
      justify-content: flex-end; gap: 12px; margin: 0; padding: 0;
  }
  
  div:where(.swal2-container) button:where(.swal2-cancel.premium-btn-cancel) {
      background: transparent; color: #111111; border: 1px solid var(--line);
      border-radius: 999px; padding: 10px 20px; font-weight: 700; font-size: 14px;
      font-family: 'Quicksand', sans-serif; transition: all 0.2s;
  }
  div:where(.swal2-container) button:where(.swal2-cancel.premium-btn-cancel):hover {
      background: var(--bg);
  }
  div:where(.swal2-container) button:where(.swal2-cancel.premium-btn-cancel):active {
      transform: scale(0.98);
  }
  
  div:where(.swal2-container) button:where(.swal2-confirm.premium-btn-confirm) {
      background: #ff0033; color: #ffffff; border: none;
      border-radius: 999px; padding: 10px 24px; font-weight: 700; font-size: 14px;
      font-family: 'Quicksand', sans-serif; 
      box-shadow: 0 0 0 2px var(--card), 0 0 0 4px #ff0033;
      transition: transform 0.1s;
  }
  div:where(.swal2-container) button:where(.swal2-confirm.premium-btn-confirm):active {
      transform: scale(0.96);
  }

  /* Toast Premium */
  div:where(.swal2-container) div:where(.swal2-popup.premium-toast) {
      background: var(--card); border: 1px solid var(--line);
      box-shadow: 0 12px 32px rgba(0,0,0,0.06); border-radius: 12px;
      padding: 16px 24px; font-family: 'Quicksand', sans-serif;
      color: #111111; font-weight: 600; font-size: 14px;
  }
  div:where(.swal2-container) div:where(.swal2-icon.premium-toast-icon) {
      transform: scale(0.7); margin: 0 12px 0 0 !important;
  }

  .tree-children > .tree-group:empty {
      min-height: 18px;
      border-radius: 12px;
      margin-left: 18px;
  }

  .tree-children > .tree-group:empty::before {
      content: "Suelta aquí para convertir en subcategoría";
      display: none;
      margin: 8px 0 0;
      padding: 10px 12px;
      border: 1px dashed #c9dafb;
      border-radius: 12px;
      color: var(--blue);
      background: #f8fbff;
      font-size: 12px;
      font-weight: 700;
  }

  .tree-children:hover > .tree-group:empty::before {
      display: block;
  }

  .tree-group.sortable-ghost,
  .tree-item.sortable-ghost {
      opacity: 0.45;
  }

  .tree-item.sortable-chosen .tree-node {
      border-color: var(--blue);
      box-shadow: 0 0 0 3px var(--blue-soft);
  }

</style>

<div class="cat-page">
  <div class="cat-shell">
    
    <div class="cat-header">
      <div>
        <h1 class="cat-title">Categorías</h1>
        <p class="cat-subtitle">Arrastra y suelta (⋮⋮) para reorganizar la estructura del catálogo.</p>
      </div>
      <div>
        <a href="{{ route('admin.category-products.create') }}" class="cat-btn-primary">Nueva Categoría</a>
      </div>
    </div>

    <div class="cat-summary-grid">
      <div class="cat-summary-card">
          <div class="cat-summary-label">Total en catálogo</div>
          <div class="cat-summary-value">{{ $totalCategories }}</div>
      </div>
      <div class="cat-summary-card">
          <div class="cat-summary-label">Principales</div>
          <div class="cat-summary-value">{{ $rootCategories }}</div>
      </div>
      <div class="cat-summary-card">
          <div class="cat-summary-label">Activas</div>
          <div class="cat-summary-value">{{ $activeCategories }}</div>
      </div>
      <div class="cat-summary-card">
          <div class="cat-summary-label">Profundidad de niveles</div>
          <div class="cat-summary-value">{{ $maxDepth + 1 }}</div>
      </div>
    </div>

    <div class="cat-flow-card">
      <div class="cat-flow-head">
        <h2 class="cat-flow-title">Mapa de jerarquías</h2>
        <a href="{{ route('admin.category-products.index') }}" class="cat-btn-ghost cat-btn-sm">Recargar vista</a>
      </div>

      @if($tree->count())
        <div class="cat-flow-scroll" id="tree-container">
          {!! $renderTree('root', 0) !!}
        </div>
      @else
        <div style="text-align:center; padding: 80px 20px; color: var(--muted);">
          <p style="margin: 0 0 20px; font-size: 16px; font-weight: 500;">Tu catálogo aún no tiene categorías.</p>
          <a href="{{ route('admin.category-products.create') }}" class="cat-btn-primary">Crear la primera</a>
        </div>
      @endif
    </div>
    
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // --- 1. CONFIGURACIÓN DEL TOAST PREMIUM ---
    const Toast = Swal.mixin({
        toast: true,
        position: 'bottom-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: false, 
        customClass: {
            popup: 'premium-toast',
            icon: 'premium-toast-icon'
        },
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    // --- 2. ELIMINAR CON MODAL PREMIUM MINIMALISTA ---
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            const categoryName = this.getAttribute('data-name');

            Swal.fire({
                title: '¿Eliminar categoría?',
                html: `
                    Esto eliminará <b>${categoryName}</b>.
                    <span class="swal-secondary-text">Verifica los productos asociados para evitar que queden sin categoría asignada en tu inventario.</span>
                `,
                icon: undefined, 
                showCancelButton: true,
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar',
                buttonsStyling: false, 
                customClass: {
                    popup: 'premium-modal',
                    title: 'premium-title',
                    htmlContainer: 'premium-text',
                    actions: 'premium-actions',
                    confirmButton: 'premium-btn-confirm',
                    cancelButton: 'premium-btn-cancel'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    // --- 3. DRAG & DROP (SortableJS) ---
    const nestedSortables = document.querySelectorAll('.tree-group');
    
    nestedSortables.forEach(el => {
        new Sortable(el, {
            group: 'nested',
            animation: 200,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            handle: '.drag-handle',
            
            onEnd: function (evt) {
                const itemEl = evt.item;
                const toList = evt.to;
                
                const categoryId = itemEl.getAttribute('data-id');
                let newParentId = toList.getAttribute('data-parent-id');
                if (newParentId === 'root') newParentId = null; 

                const siblingIds = Array.from(toList.children).map(child => child.getAttribute('data-id'));

                saveNewOrder(categoryId, newParentId, siblingIds);
            }
        });
    });

    // --- 4. PETICIÓN AJAX ---
    function saveNewOrder(categoryId, parentId, orderArray) {
        const url = "{{ route('admin.category-products.reorder') }}"; 

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                category_id: categoryId,
                parent_id: parentId,
                order: orderArray
            })
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));

            if (!response.ok || data.ok === false) {
                throw new Error(data.message || 'Error al actualizar el orden');
            }

            return data;
        })
        .then(data => {
            Toast.fire({
                icon: 'success',
                title: data.message || 'Organización actualizada'
            });

            setTimeout(() => {
                window.location.reload();
            }, 650);
        })
        .catch(error => {
            console.error('Error:', error);
            Toast.fire({
                icon: 'error',
                title: error.message || 'Error al actualizar el orden'
            });
        });
    }
});
</script>
@endsection