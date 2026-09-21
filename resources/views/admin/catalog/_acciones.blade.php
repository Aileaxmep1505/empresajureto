{{-- Menú de acciones (tres puntitos) reutilizable en tabla y tarjetas.
     Requiere $it. Los handlers (js-open-stock, js-sa-confirm, data-ajax) están
     delegados en index.blade.php, así que funciona tras cada recarga AJAX. --}}
@php
  $accEsMuestra = (bool) ($it->is_sample ?? false);
  $accTieneMl   = !$accEsMuestra && $it->meli_item_id;
@endphp
<div class="rowmenu">
  <button type="button" class="rowmenu-btn" aria-haspopup="true" aria-expanded="false" aria-label="Acciones de {{ $it->name }}">
    <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
  </button>

  <div class="rowmenu-pop" role="menu">
    <a class="rm-item" role="menuitem" href="{{ route('catalog.preview', $it) }}" target="_blank" rel="noopener">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
      Vista previa
    </a>

    <button type="button" class="rm-item js-open-stock" role="menuitem"
            data-name="{{ $it->name }}" data-stock="{{ (float) ($it->stock ?? 0) }}"
            data-action="{{ route('admin.catalog.stock.update', $it) }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M8 9h8M8 13h4"/></svg>
      Actualizar stock
    </button>

    <a class="rm-item" role="menuitem" href="{{ route('admin.catalog.edit', $it) }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
      Editar
    </a>

    <form method="POST" action="{{ route('admin.catalog.toggle', $it) }}" class="js-sa-confirm"
          data-sa-title="¿Cambiar estado de publicación?"
          data-sa-text="Se actualizará el estado de este producto en el sitio web." data-sa-icon="question">
      @csrf @method('PATCH')
      <button type="submit" class="rm-item" role="menuitem">
        @if($it->status == 1)
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12z"/><path d="M3 3l18 18"/></svg>
          Ocultar del sitio
        @else
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11v2"/><path d="M5 10v4"/><path d="M7 9v6"/><path d="M9 8l10-3v14l-10-3V8z"/></svg>
          Publicar en el sitio
        @endif
      </button>
    </form>

    @unless($accEsMuestra)
      <div class="rm-sep"></div>
      <div class="rm-lbl">Mercado Libre</div>

      <form method="POST" action="{{ route('admin.catalog.meli.publish', $it) }}" class="js-sa-confirm"
            data-sa-title="¿Enviar a Mercado Libre?"
            data-sa-text="Se publicará o actualizará el anuncio en Mercado Libre." data-sa-icon="info">
        @csrf
        <button type="submit" class="rm-item" role="menuitem">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21V8"/><path d="M7 12l5-5 5 5"/><path d="M20 21H4"/></svg>
          {{ $it->meli_item_id ? 'Actualizar en ML' : 'Publicar en ML' }}
        </button>
      </form>

      @if($it->meli_item_id)
        <a class="rm-item" role="menuitem" href="{{ route('admin.catalog.meli.view', $it) }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3h7v7"/><path d="M10 14L21 3"/><path d="M21 14v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6"/></svg>
          Ver en ML
        </a>

        <form method="POST" action="{{ route('admin.catalog.meli.pause', $it) }}" class="js-sa-confirm"
              data-sa-title="¿Pausar en Mercado Libre?" data-sa-text="El anuncio quedará pausado." data-sa-icon="warning">
          @csrf
          <button type="submit" class="rm-item" role="menuitem">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
            Pausar en ML
          </button>
        </form>

        <form method="POST" action="{{ route('admin.catalog.meli.activate', $it) }}" class="js-sa-confirm"
              data-sa-title="¿Activar en Mercado Libre?" data-sa-text="El anuncio volverá a estar activo." data-sa-icon="success">
          @csrf
          <button type="submit" class="rm-item" role="menuitem">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="8 5 19 12 8 19 8 5"/></svg>
            Activar en ML
          </button>
        </form>
      @endif
    @endunless

    <div class="rm-sep"></div>
    <form method="POST" action="{{ route('admin.catalog.destroy', $it) }}" class="js-sa-confirm"
          data-sa-title="¿Eliminar producto?" data-sa-text="Esta acción no se puede deshacer." data-sa-icon="error">
      @csrf @method('DELETE')
      <button type="submit" class="rm-item is-danger" role="menuitem">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
        Eliminar
      </button>
    </form>
  </div>
</div>
