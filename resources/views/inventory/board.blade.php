@extends('layouts.app')
@section('title','Activos e Inventario')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<style>
  :root{
    --bg:#f9fafb; --card:#fff; --ink:#111; --text:#333; --muted:#888; --line:#ebebeb;
    --blue:#007aff; --blue-soft:#e6f0ff; --success:#15803d; --success-soft:#e6ffe6;
    --danger:#ff4a4a; --danger-soft:#ffebeb; --warning:#d97706; --warning-soft:#fef3c7;
    --ease-out:cubic-bezier(0.23,1,0.32,1); --ease-in-out:cubic-bezier(0.77,0,0.175,1);
  }
  html,body{ background:var(--bg); color:var(--text); font-family:'Quicksand',sans-serif; font-weight:500; overflow-x:hidden; }
  @keyframes fadeSlideUp{ from{opacity:0;transform:translateY(14px);} to{opacity:1;transform:translateY(0);} }
  .reveal-item{ opacity:0; animation:fadeSlideUp .5s var(--ease-out) forwards; }

  .inventory-page{ width:100%; padding:32px 24px 64px; max-width:1400px; margin:0 auto; }
  .page-head{ display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:32px; will-change:transform; }
  .page-head-left{ display:flex; align-items:center; gap:16px; }
  .head-actions{ display:flex; gap:8px; flex-wrap:wrap; }
  .back-btn{ height:40px; border-radius:999px; padding:0 16px; background:transparent; border:1px solid var(--line); color:var(--muted); display:inline-flex; align-items:center; gap:8px; text-decoration:none; font-size:14px; font-weight:600; transition:all .2s var(--ease-out); }
  @media (hover:hover) and (pointer:fine){
    .back-btn:hover{ background:var(--bg); color:var(--ink); }
    .back-btn:active{ transform:scale(.98); }
    .asset-card:hover{ transform:translateY(-2px); box-shadow:0 8px 24px rgba(0,0,0,.04); }
  }
  .page-title{ margin:0; font-size:28px; font-weight:700; color:var(--ink); letter-spacing:-.02em; }
  .page-sub{ margin:4px 0 0; color:var(--muted); font-size:14px; }

  .btn-new, .na-btn-save{ border:none; background:var(--blue); color:#fff; padding:0 24px; height:44px; border-radius:8px; font-weight:600; font-size:14px; display:inline-flex; align-items:center; justify-content:center; gap:8px; transition:transform .2s var(--ease-out),background .2s var(--ease-out); text-decoration:none; cursor:pointer; }
  .btn-new:active, .na-btn-save:active{ transform:scale(.98); }
  .btn-soft{ background:var(--card); border:1px solid var(--line); color:var(--text); padding:0 16px; height:44px; border-radius:8px; font-weight:600; font-size:14px; display:inline-flex; align-items:center; gap:8px; cursor:pointer; transition:background .2s; }
  @media (hover:hover){ .btn-soft:hover{ background:var(--bg); } }

  .top-tabs{ display:flex; gap:4px; margin-bottom:24px; }
  .top-tabs .tab-btn{ border:none; background:transparent; color:var(--muted); font-weight:600; font-size:15px; padding:8px 16px; border-radius:8px; transition:all .2s var(--ease-out); }
  .top-tabs .tab-btn.active{ background:var(--card); color:var(--blue); border:1px solid var(--line); box-shadow:0 2px 8px rgba(0,0,0,.02); }

  .filters-wrap{ background:var(--card); border:1px solid var(--line); border-radius:12px; padding:16px; margin-bottom:32px; box-shadow:0 4px 12px rgba(0,0,0,.02); }
  .search-box{ position:relative; }
  .search-box .bi{ position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:16px; }
  .search-box input, .na-input, .na-textarea{ width:100%; padding:0 16px; padding-left:48px; height:44px; border-radius:8px; border:1px solid var(--line); background:var(--card); font-size:14px; font-family:'Quicksand',sans-serif; color:var(--text); transition:border-color .2s var(--ease-out),box-shadow .2s var(--ease-out); }
  .na-input, .na-textarea{ padding-left:16px; margin-bottom:16px; }
  .na-textarea{ min-height:100px; padding-top:12px; resize:vertical; }
  .search-box input:focus, .na-input:focus, .na-textarea:focus{ outline:none; border-color:var(--blue); box-shadow:0 0 0 3px var(--blue-soft); }

  .custom-select-wrapper{ position:relative; width:100%; font-family:'Quicksand',sans-serif; }
  .custom-select-trigger{ display:flex; align-items:center; justify-content:space-between; height:44px; padding:0 16px; background:var(--card); border:1px solid var(--line); border-radius:8px; font-size:14px; color:var(--text); cursor:pointer; transition:all .2s var(--ease-out); }
  .custom-select-trigger:focus, .custom-select-trigger.open{ border-color:var(--blue); box-shadow:0 0 0 3px var(--blue-soft); outline:none; }
  .custom-select-trigger .chevron{ transition:transform .2s var(--ease-out); color:var(--muted); }
  .custom-select-trigger.open .chevron{ transform:rotate(180deg); }
  .custom-select-panel{ position:absolute; top:calc(100% + 4px); left:0; right:0; background:var(--card); border:1px solid var(--line); border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,.06); opacity:0; visibility:hidden; transform:translateY(-6px); transition:all .2s var(--ease-out); z-index:100; max-height:250px; overflow-y:auto; transform-origin:top; }
  .custom-select-wrapper.open .custom-select-panel{ opacity:1; visibility:visible; transform:translateY(0); }
  .custom-select-option{ padding:10px 16px; font-size:14px; color:var(--text); cursor:pointer; transition:background .15s ease,color .15s ease; display:flex; justify-content:space-between; align-items:center; }
  .custom-select-option.active-key{ background:var(--bg); }
  @media (hover:hover){ .custom-select-option:hover{ background:var(--bg); } }
  .custom-select-option.selected{ color:var(--blue); font-weight:600; }

  .cards-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:24px; }
  .asset-card{ background:var(--card); border:1px solid var(--line); border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,.02); cursor:pointer; transition:transform .25s var(--ease-out),box-shadow .25s var(--ease-out); will-change:transform,opacity; }
  .asset-media{ position:relative; height:180px; background:var(--bg); overflow:hidden; }
  .asset-media img{ width:100%; height:100%; object-fit:cover; }
  .asset-media-placeholder{ width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:var(--muted); font-size:40px; background:var(--bg); }
  .top-badges{ position:absolute; top:12px; left:12px; right:12px; display:flex; justify-content:space-between; align-items:center; gap:8px; }
  .chip{ display:inline-flex; align-items:center; gap:4px; border-radius:6px; padding:4px 8px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.03em; border:1px solid transparent; }
  .chip-cat{ background:rgba(255,255,255,.9); color:var(--ink); backdrop-filter:blur(4px); }
  .state-disponible{ color:var(--success); background:var(--success-soft); }
  .state-asignado{ color:var(--blue); background:var(--blue-soft); }
  .state-en_reparacion{ color:var(--warning); background:var(--warning-soft); }
  .state-dado_de_baja, .state-bajo_stock{ color:var(--danger); background:var(--danger-soft); }

  .asset-body{ padding:16px; }
  .asset-name{ margin:0 0 4px; font-size:16px; font-weight:700; color:var(--ink); }
  .asset-model{ margin:0; color:var(--muted); font-size:13px; font-weight:500; }
  .asset-tag{ margin:12px 0; color:var(--muted); font-size:12px; font-weight:600; }
  .asset-loc{ display:flex; align-items:center; gap:6px; color:var(--muted); font-size:13px; }
  .stock-meta{ margin-top:16px; }
  .stock-line{ display:flex; justify-content:space-between; font-size:13px; margin-bottom:8px; color:var(--muted); }
  .stock-line strong{ color:var(--ink); font-weight:600; }
  .stock-bar{ height:4px; border-radius:2px; background:var(--line); overflow:hidden; }
  .stock-fill{ height:100%; border-radius:2px; transition:width .4s var(--ease-out); }
  .fill-green{ background:var(--success); } .fill-amber{ background:var(--warning); } .fill-red{ background:var(--danger); }
  .stock-min{ margin-top:8px; font-size:12px; color:var(--danger); font-weight:600; }

  .empty-state{ background:var(--card); border:1px dashed var(--line); border-radius:12px; padding:60px 24px; text-align:center; color:var(--muted); font-size:15px; }
  .d-none-force{ display:none !important; }
  .load-more-wrap{ text-align:center; margin-top:28px; }

  .screen-overlay{ position:fixed; inset:0; background:rgba(17,17,17,.4); opacity:0; visibility:hidden; transition:all .3s var(--ease-out); z-index:1040; backdrop-filter:blur(2px); }
  .screen-overlay.show{ opacity:1; visibility:visible; }
  .custom-drawer{ position:fixed; top:0; right:0; width:min(440px,100vw); height:100vh; background:var(--card); z-index:1050; transform:translateX(100%); transition:transform .4s var(--ease-in-out); display:flex; flex-direction:column; box-shadow:-8px 0 30px rgba(0,0,0,.05); }
  .custom-drawer.show{ transform:translateX(0); }
  .drawer-head{ display:flex; align-items:center; justify-content:space-between; padding:24px; border-bottom:1px solid var(--line); }
  .drawer-title{ font-size:18px; font-weight:700; margin:0; color:var(--ink); }
  .drawer-close{ width:32px; height:32px; border-radius:8px; border:none; background:transparent; color:var(--muted); display:grid; place-items:center; font-size:18px; cursor:pointer; transition:background .2s; }
  .drawer-close:hover{ background:var(--bg); color:var(--ink); }
  .drawer-body{ padding:24px; overflow:auto; flex:1; }
  .drawer-img{ width:100%; height:220px; border-radius:12px; overflow:hidden; background:var(--bg); margin-bottom:24px; }
  .drawer-img img{ width:100%; height:100%; object-fit:cover; }
  .drawer-img .ph{ width:100%; height:100%; display:grid; place-items:center; color:var(--muted); font-size:42px; }
  .drawer-chips{ display:flex; flex-wrap:wrap; gap:8px; margin-bottom:24px; }
  .info-grid{ display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:32px; }
  .info-label{ color:var(--muted); font-size:12px; font-weight:600; text-transform:uppercase; margin-bottom:4px; }
  .info-value{ color:var(--text); font-size:14px; font-weight:500; }
  .drawer-section-title{ font-size:12px; font-weight:700; text-transform:uppercase; color:var(--muted); margin-bottom:12px; }
  .drawer-desc{ color:var(--text); font-size:14px; line-height:1.6; margin-bottom:24px; }
  .mnt-list{ margin-bottom:24px; }
  .mnt-item{ display:flex; justify-content:space-between; gap:8px; padding:8px 0; border-bottom:1px solid var(--line); font-size:13px; }
  .mnt-item:last-child{ border-bottom:none; }
  .drawer-actions{ display:flex; flex-direction:column; gap:12px; padding-top:24px; border-top:1px solid var(--line); }
  .drawer-row{ display:flex; gap:12px; width:100%; }
  
  .btn-outline{ flex:1; background:var(--card); border:1px solid var(--blue); color:var(--blue); font-weight:600; border-radius:8px; height:44px; display:flex; align-items:center; justify-content:center; gap:8px; text-decoration:none; transition:all .2s; cursor:pointer; width:100%; }
  @media (hover:hover){ .btn-outline:hover{ background:var(--blue-soft); } }
  .btn-ghost{ flex:0 0 44px; background:transparent; border:1px solid var(--line); color:var(--muted); border-radius:8px; height:44px; display:grid; place-items:center; transition:all .2s; cursor:pointer; }
  @media (hover:hover){ .btn-ghost:hover{ background:var(--bg); color:var(--ink); } }
  .btn-danger-ghost{ background:transparent; border:1px solid var(--danger-soft); color:var(--danger); border-radius:8px; height:44px; width:100%; display:grid; place-items:center; transition:all .2s; cursor:pointer; }
  @media (hover:hover){ .btn-danger-ghost:hover{ background:var(--danger-soft); } }

  .modal-content{ border:none; border-radius:16px; box-shadow:0 12px 40px rgba(0,0,0,.08); }
  .na-head{ display:flex; align-items:center; justify-content:space-between; padding:24px 24px 12px; }
  .na-head h5{ margin:0; font-size:20px; font-weight:700; color:var(--ink); }
  .na-close{ background:transparent; border:none; color:var(--muted); font-size:20px; cursor:pointer; transition:color .2s; }
  .na-close:hover{ color:var(--ink); }
  .na-tabs{ display:flex; gap:16px; margin:0 24px; border-bottom:1px solid var(--line); }
  .na-tab{ background:transparent; border:none; color:var(--muted); font-weight:600; font-size:14px; padding:12px 0; border-bottom:2px solid transparent; transition:all .2s; }
  .na-tab.active{ color:var(--blue); border-color:var(--blue); }
  .na-body{ padding:24px; max-height:60vh; overflow-y:auto; }
  .na-panel{ display:none; animation:fadeSlideUp .3s var(--ease-out); }
  .na-panel.active{ display:block; }
  .na-label{ font-weight:600; font-size:13px; color:var(--text); margin-bottom:8px; display:block; }
  .na-label .req{ color:var(--danger); }
  .na-uploader{ display:flex; align-items:center; gap:16px; margin-bottom:16px; }
  .na-drop{ width:80px; height:80px; border:1px dashed var(--muted); border-radius:12px; display:grid; place-items:center; color:var(--muted); font-size:24px; background:var(--bg); overflow:hidden; }
  .na-drop img{ width:100%; height:100%; object-fit:cover; }
  .na-upload-btn{ border:1px solid var(--line); background:var(--card); color:var(--text); font-weight:600; font-size:13px; border-radius:8px; padding:8px 16px; cursor:pointer; transition:background .2s; }
  .na-upload-btn:hover{ background:var(--bg); }
  .na-remove-photo{ font-size:13px; color:var(--muted); margin-bottom:16px; display:none; align-items:center; gap:6px; }
  .na-cat-row{ display:flex; gap:8px; align-items:flex-start; margin-bottom:16px; }
  .na-cat-add{ flex:0 0 44px; height:44px; border:1px solid var(--line); background:var(--card); border-radius:8px; color:var(--blue); font-size:18px; cursor:pointer; transition:background .2s; }
  .na-cat-add:hover{ background:var(--blue-soft); }
  .na-foot{ display:flex; justify-content:flex-end; gap:12px; padding:16px 24px 24px; border-top:1px solid var(--line); }
  .na-btn-cancel{ border:1px solid var(--line); background:transparent; color:var(--text); font-weight:600; font-size:14px; border-radius:8px; padding:0 20px; height:44px; cursor:pointer; transition:background .2s; }
  .na-btn-cancel:hover{ background:var(--bg); }
  .field-error{ color:var(--danger); font-size:12px; margin:-10px 0 14px; }

  /* Toasts */
  .toast-wrap{ position:fixed; top:20px; right:20px; z-index:2000; display:flex; flex-direction:column; gap:10px; }
  .toast-msg{ background:var(--card); border:1px solid var(--line); border-left:4px solid var(--blue); border-radius:10px; padding:14px 18px; box-shadow:0 8px 24px rgba(0,0,0,.08); font-size:14px; min-width:240px; animation:fadeSlideUp .3s var(--ease-out); }
  .toast-msg.ok{ border-left-color:var(--success); }
  .toast-msg.bad{ border-left-color:var(--danger); }

  @media (prefers-reduced-motion:reduce){
    *{ animation-duration:.01ms !important; transition-duration:.01ms !important; }
    .reveal-item{ opacity:1; transform:none; animation:none; }
  }
  @media (max-width:767.98px){ .info-grid{ grid-template-columns:1fr; } }
</style>

@php
  $fixedAssets = $fixedAssets ?? collect();
  $consumables = $consumables ?? collect();
  $fixedCount = $fixedCount ?? $fixedAssets->count();
  $consumableCount = $consumableCount ?? $consumables->count();

  function statusBadgeClass($status){
      return match($status){
          'asignado' => 'state-asignado',
          'en_reparacion' => 'state-en_reparacion',
          'dado_de_baja' => 'state-dado_de_baja',
          default => 'state-disponible'
      };
  }
  function statusLabel($status){
      return match($status){
          'asignado' => 'Asignado',
          'en_reparacion' => 'Mantenimiento',
          'dado_de_baja' => 'Baja',
          default => 'Disponible'
      };
  }
@endphp

<div class="inventory-page">
  <div class="page-head">
    <div class="page-head-left">
      <a href="{{ url('/internal-assets') }}" class="back-btn"><i class="bi bi-arrow-left"></i><span>Regresar</span></a>
      <div>
        <h1 class="page-title">Activos e Inventario</h1>
        <p class="page-sub">{{ $fixedCount + $consumableCount }} elementos registrados</p>
      </div>
    </div>
    <div class="head-actions">
      <a href="{{ route('maintenance.index') }}" class="btn-soft"><i class="bi bi-tools"></i><span>Mantenimiento</span></a>
      <button type="button" class="btn-soft" id="btnExport"><i class="bi bi-filetype-csv"></i><span>Exportar</span></button>
      <button type="button" class="btn-soft" id="btnPrintLabels"><i class="bi bi-printer"></i><span>Etiquetas</span></button>
      <button type="button" class="btn-new" id="btnNew"><i class="bi bi-plus-lg"></i><span>Nuevo Registro</span></button>
    </div>
  </div>

  <div class="top-tabs">
    <button type="button" class="tab-btn active" data-tab="activo_fijo">Activos Fijos</button>
    <button type="button" class="tab-btn" data-tab="consumible">Consumibles / Stock</button>
  </div>

  <div class="filters-wrap">
    <div class="row g-3 align-items-center">
      <div class="col-lg-5">
        <div class="search-box"><i class="bi bi-search"></i><input type="text" id="boardSearch" placeholder="Buscar por nombre o marca..."></div>
      </div>
      <div class="col-md-4 col-lg-3">
        <select id="boardCategory" class="custom-select-target">
          <option value="">Todas las categorías</option>
          @foreach($categories as $cat)<option value="{{ strtolower($cat->name) }}">{{ $cat->name }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-4 col-lg-2">
        <select id="boardStatus" class="custom-select-target">
          <option value="">Todos los estados</option>
          <option value="disponible">Disponible</option>
          <option value="asignado">Asignado</option>
          <option value="en_reparacion">Mantenimiento</option>
          <option value="dado_de_baja">Baja</option>
          <option value="bajo_stock">Bajo stock</option>
        </select>
      </div>
      <div class="col-md-4 col-lg-2">
        <select id="boardSort" class="custom-select-target">
          <option value="recientes">Más recientes</option>
          <option value="nombre">Nombre (A-Z)</option>
          <option value="stock">Stock (menor)</option>
        </select>
      </div>
    </div>
  </div>

  <div class="tab-panel" id="panel-activo_fijo">
    @if($fixedAssets->isEmpty())
      <div class="empty-state"><i class="bi bi-laptop" style="font-size:32px;display:block;margin-bottom:12px;"></i>No hay activos fijos registrados.</div>
    @else
      <div class="cards-grid" id="grid-activo_fijo">
        @foreach($fixedAssets as $item)
          @php
            $status = $item->asset_status ?: 'disponible';
            $tag = $item->serial_number ?: ('ID-'.$item->id);
            $mnts = $item->maintenances->map(fn($m)=>[
              'date'=>optional($m->maintenance_date)->format('Y-m-d'),
              'type'=>ucfirst($m->type),
              'status'=>$m->status,
            ])->values();
          @endphp
          <div class="asset-card js-item-card reveal-item"
               data-tab="activo_fijo" data-id="{{ $item->id }}" data-name="{{ $item->name }}"
               data-type="activo_fijo" data-type-label="Activo Fijo"
               data-category="{{ $item->category->name ?? 'Sin categoría' }}" data-category-id="{{ $item->inventory_category_id }}"
               data-status="{{ $status }}" data-status-label="{{ statusLabel($status) }}"
               data-status-class="{{ statusBadgeClass($status) }}"
               data-brand="{{ $item->brand }}" data-model="{{ $item->model }}"
               data-serial="{{ $item->serial_number }}" data-internal-code="{{ $item->internal_code }}"
               data-location="{{ $item->location }}" data-department="{{ $item->department }}"
               data-supplier="{{ $item->supplier }}" data-purchase-date="{{ optional($item->purchase_date)->format('Y-m-d') }}"
               data-purchase-cost="{{ $item->purchase_cost }}" data-warranty-until="{{ optional($item->warranty_until)->format('Y-m-d') }}"
               data-processor="{{ $item->processor }}" data-ram="{{ $item->ram }}" data-storage="{{ $item->storage }}"
               data-operating-system="{{ $item->operating_system }}" data-mac-address="{{ $item->mac_address }}"
               data-condition="{{ $item->condition }}" data-notes="{{ $item->notes }}"
               data-unit="{{ $item->unit }}" data-stock="{{ (int)$item->stock }}" data-stock-min="0" data-stock-max="0"
               data-photo="{{ $item->photo ? asset('storage/'.$item->photo) : '' }}"
               data-delete-url="{{ route('assets.destroy', $item->id) }}"
               data-assign-url="{{ url('/assets/asignar', $item->id) }}" {{-- URL ajustada para redirigir a una vista --}}
               data-qr-text="{{ route('assets.board').'?item='.$item->id }}"
               data-tag="{{ $tag }}" data-is-consumable="0"
               data-maintenances='@json($mnts)'
               data-search="{{ strtolower(trim(($item->name).' '.($item->brand).' '.($item->model).' '.($item->serial_number))) }}"
               data-sort-name="{{ strtolower($item->name) }}">
            <div class="asset-media">
              @if($item->photo)<img src="{{ asset('storage/'.$item->photo) }}" alt="{{ $item->name }}" loading="lazy">
              @else<div class="asset-media-placeholder"><i class="bi bi-laptop"></i></div>@endif
              <div class="top-badges">
                <span class="chip chip-cat">{{ $item->category->name ?? 'Activo' }}</span>
                <span class="chip {{ statusBadgeClass($status) }}">{{ statusLabel($status) }}</span>
              </div>
            </div>
            <div class="asset-body">
              <h3 class="asset-name">{{ $item->name }}</h3>
              <p class="asset-model">{{ $item->brand }} · {{ $item->model }}</p>
              <div class="asset-tag">{{ $tag }}</div>
              <div class="asset-loc"><i class="bi bi-geo-alt"></i><span>{{ $item->location ?: 'Sin ubicación' }}</span></div>
            </div>
          </div>
        @endforeach
      </div>
      <div class="load-more-wrap"><button type="button" class="btn-soft js-load-more" data-tab="activo_fijo">Cargar más</button></div>
    @endif
  </div>

  <div class="tab-panel d-none-force" id="panel-consumible">
    @if($consumables->isEmpty())
      <div class="empty-state"><i class="bi bi-box-seam" style="font-size:32px;display:block;margin-bottom:12px;"></i>No hay consumibles registrados.</div>
    @else
      <div class="cards-grid" id="grid-consumible">
        @foreach($consumables as $item)
          @php
            $stock=(int)$item->stock; $max=max(1,(int)$item->stock_max); $min=(int)$item->stock_min;
            $pct=max(0,min(100,round(($stock/$max)*100)));
            $fillClass=$stock<=$min?'fill-red':($pct<=40?'fill-amber':'fill-green');
            $stockBadge=$stock<=$min?'bajo_stock':'disponible';
          @endphp
          <div class="asset-card js-item-card reveal-item"
               data-tab="consumible" data-id="{{ $item->id }}" data-name="{{ $item->name }}"
               data-type="consumible" data-type-label="Consumible"
               data-category="{{ $item->category->name ?? 'Sin categoría' }}" data-category-id="{{ $item->inventory_category_id }}"
               data-status="{{ $stockBadge }}" data-status-label="Disponible" data-status-class="state-disponible"
               data-brand="{{ $item->brand }}" data-location="{{ $item->location }}" data-department="{{ $item->department }}"
               data-supplier="{{ $item->supplier }}" data-purchase-date="{{ optional($item->purchase_date)->format('Y-m-d') }}"
               data-purchase-cost="{{ $item->purchase_cost }}" data-warranty-until="{{ optional($item->warranty_until)->format('Y-m-d') }}"
               data-notes="{{ $item->notes }}" data-unit="{{ $item->unit ?: 'piezas' }}"
               data-stock="{{ $stock }}" data-stock-min="{{ $min }}" data-stock-max="{{ $max }}"
               data-photo="{{ $item->photo ? asset('storage/'.$item->photo) : '' }}"
               data-delete-url="{{ route('assets.destroy', $item->id) }}"
               data-stock-url="{{ route('assets.stock-move', $item->id) }}"
               data-tag="ID-{{ $item->id }}" data-is-consumable="1"
               data-search="{{ strtolower(trim(($item->name).' '.($item->brand))) }}"
               data-sort-name="{{ strtolower($item->name) }}">
            <div class="asset-media">
              @if($item->photo)<img src="{{ asset('storage/'.$item->photo) }}" alt="{{ $item->name }}" loading="lazy">
              @else<div class="asset-media-placeholder"><i class="bi bi-box-seam"></i></div>@endif
              <div class="top-badges">
                <span class="chip chip-cat">{{ $item->category->name ?? 'Consumible' }}</span>
                <span class="chip {{ $stock<=$min ? 'state-bajo_stock' : 'state-disponible' }}">{{ $stock<=$min ? 'Bajo stock' : 'Disponible' }}</span>
              </div>
            </div>
            <div class="asset-body">
              <h3 class="asset-name">{{ $item->name }}</h3>
              <p class="asset-model">{{ $item->brand ?: ($item->category->name ?? 'Consumible') }}</p>
              <div class="asset-loc mb-2"><i class="bi bi-geo-alt"></i><span>{{ $item->location ?: 'Sin ubicación' }}</span></div>
              <div class="stock-meta">
                <div class="stock-line"><span>Stock</span>
                  <strong>@if($stock<=$min)<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>@endif{{ $stock }} / {{ $max }} {{ $item->unit ?: 'pzs' }}</strong>
                </div>
                <div class="stock-bar"><div class="stock-fill {{ $fillClass }}" style="width:{{ $pct }}%;"></div></div>
                @if($stock<=$min)<div class="stock-min">Mínimo alcanzado: {{ $min }}</div>@endif
              </div>
            </div>
          </div>
        @endforeach
      </div>
      <div class="load-more-wrap"><button type="button" class="btn-soft js-load-more" data-tab="consumible">Cargar más</button></div>
    @endif
  </div>
</div>

{{-- ===== MODAL ACTIVO FIJO (crear/editar) ===== --}}
<div class="modal fade" id="newAssetModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="POST" action="{{ route('assets.save') }}" enctype="multipart/form-data" id="assetForm">
        @csrf
        <input type="hidden" name="type" value="activo_fijo">
        <input type="hidden" name="item_id" id="assetItemId" value="{{ old('item_id') }}">
        <input type="hidden" name="_form_context" value="activo">
        <input type="hidden" name="stock" value="1">
        <div class="na-head"><h5 id="assetModalTitle">Nuevo Activo</h5><button type="button" class="na-close" data-bs-dismiss="modal"><i class="bi bi-x"></i></button></div>
        <div class="na-tabs">
          <button type="button" class="na-tab active" data-na-tab="general">General</button>
          <button type="button" class="na-tab" data-na-tab="detalles">Detalles</button>
          <button type="button" class="na-tab" data-na-tab="tecnico">Técnico</button>
        </div>
        <div class="na-body">
          <div class="na-panel active" data-na-panel="general">
            <div class="na-uploader">
              <div class="na-drop"><i class="bi bi-camera"></i></div>
              <label class="na-upload-btn">Subir imagen<input type="file" name="photo" accept="image/*" hidden></label>
            </div>
            <label class="na-remove-photo"><input type="checkbox" name="remove_photo" value="1"> Quitar foto actual</label>
            <label class="na-label">Nombre <span class="req">*</span></label>
            <input type="text" name="name" class="na-input" value="{{ old('name') }}" placeholder="Ej. Laptop Dell XPS 15" required>
            @error('name')<div class="field-error">{{ $message }}</div>@enderror
            <div class="row">
              <div class="col-md-6"><label class="na-label">Tipo</label><select class="custom-select-target" disabled><option>Activo Fijo</option></select></div>
              <div class="col-md-6">
                <label class="na-label">Categoría <span class="req">*</span></label>
                <div class="na-cat-row">
                  <select name="inventory_category_id" class="custom-select-target js-category-select" required>
                    <option value="">Seleccionar...</option>
                    @foreach($categories as $cat)<option value="{{ $cat->id }}" @selected(old('inventory_category_id')==$cat->id)>{{ $cat->name }}</option>@endforeach
                  </select>
                  <button type="button" class="na-cat-add js-add-category"><i class="bi bi-plus"></i></button>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6"><label class="na-label">Estado</label>
                <select name="asset_status" class="custom-select-target">
                  <option value="disponible" @selected(old('asset_status')=='disponible')>Disponible</option>
                  <option value="asignado" @selected(old('asset_status')=='asignado')>Asignado</option>
                  <option value="en_reparacion" @selected(old('asset_status')=='en_reparacion')>En reparación</option>
                  <option value="dado_de_baja" @selected(old('asset_status')=='dado_de_baja')>Dado de baja</option>
                </select>
              </div>
              <div class="col-md-6"><label class="na-label">Condición</label>
                <select name="condition" class="custom-select-target">
                  <option value="nuevo" @selected(old('condition')=='nuevo')>Nuevo</option>
                  <option value="bueno" @selected(old('condition')=='bueno')>Bueno</option>
                  <option value="regular" @selected(old('condition')=='regular')>Regular</option>
                  <option value="malo" @selected(old('condition')=='malo')>Malo</option>
                </select>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6"><label class="na-label">Marca</label><input type="text" name="brand" class="na-input" value="{{ old('brand') }}"></div>
              <div class="col-md-6"><label class="na-label">Modelo</label><input type="text" name="model" class="na-input" value="{{ old('model') }}"></div>
            </div>
            <div class="row">
              <div class="col-md-6"><label class="na-label">No. Serie</label><input type="text" name="serial_number" class="na-input" value="{{ old('serial_number') }}">@error('serial_number')<div class="field-error">{{ $message }}</div>@enderror</div>
              <div class="col-md-6"><label class="na-label">Código Interno</label><input type="text" name="internal_code" class="na-input" value="{{ old('internal_code') }}">@error('internal_code')<div class="field-error">{{ $message }}</div>@enderror</div>
            </div>
          </div>
          <div class="na-panel" data-na-panel="detalles">
            <div class="row">
              <div class="col-md-6"><label class="na-label">Ubicación</label><input type="text" name="location" class="na-input" value="{{ old('location') }}"></div>
              <div class="col-md-6"><label class="na-label">Departamento</label><input type="text" name="department" class="na-input" value="{{ old('department') }}"></div>
            </div>
            <div class="row">
              <div class="col-md-6"><label class="na-label">Proveedor</label><input type="text" name="supplier" class="na-input" value="{{ old('supplier') }}"></div>
              <div class="col-md-6"><label class="na-label">Fecha Compra</label><input type="date" name="purchase_date" class="na-input" value="{{ old('purchase_date') }}"></div>
            </div>
            <div class="row">
              <div class="col-md-6"><label class="na-label">Costo ($)</label><input type="number" step="0.01" name="purchase_cost" class="na-input" value="{{ old('purchase_cost') }}"></div>
              <div class="col-md-6"><label class="na-label">Garantía hasta</label><input type="date" name="warranty_until" class="na-input" value="{{ old('warranty_until') }}"></div>
            </div>
            <label class="na-label">Notas</label><textarea name="notes" class="na-textarea">{{ old('notes') }}</textarea>
          </div>
          <div class="na-panel" data-na-panel="tecnico">
            <div class="row">
              <div class="col-md-6"><label class="na-label">Procesador</label><input type="text" name="processor" class="na-input" value="{{ old('processor') }}"></div>
              <div class="col-md-6"><label class="na-label">RAM</label><input type="text" name="ram" class="na-input" value="{{ old('ram') }}"></div>
            </div>
            <div class="row">
              <div class="col-md-6"><label class="na-label">Almacenamiento</label><input type="text" name="storage" class="na-input" value="{{ old('storage') }}"></div>
              <div class="col-md-6"><label class="na-label">SO</label><input type="text" name="operating_system" class="na-input" value="{{ old('operating_system') }}"></div>
            </div>
            <label class="na-label">MAC Address</label><input type="text" name="mac_address" class="na-input" value="{{ old('mac_address') }}">
          </div>
        </div>
        <div class="na-foot">
          <button type="button" class="na-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="na-btn-save" id="assetSubmitBtn">Guardar activo</button>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="modal fade" id="newConsumableModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="POST" action="{{ route('assets.save') }}" enctype="multipart/form-data" id="consumableForm">
        @csrf
        <input type="hidden" name="type" value="consumible">
        <input type="hidden" name="item_id" id="consItemId" value="{{ old('item_id') }}">
        <input type="hidden" name="_form_context" value="consumible">
        <div class="na-head"><h5 id="consModalTitle">Nuevo Consumible</h5><button type="button" class="na-close" data-bs-dismiss="modal"><i class="bi bi-x"></i></button></div>
        <div class="na-tabs">
          <button type="button" class="na-tab active" data-na-tab="general">General</button>
          <button type="button" class="na-tab" data-na-tab="detalles">Detalles</button>
        </div>
        <div class="na-body">
          <div class="na-panel active" data-na-panel="general">
            <div class="na-uploader">
              <div class="na-drop"><i class="bi bi-camera"></i></div>
              <label class="na-upload-btn">Subir imagen<input type="file" name="photo" accept="image/*" hidden></label>
            </div>
            <label class="na-remove-photo"><input type="checkbox" name="remove_photo" value="1"> Quitar foto actual</label>
            <label class="na-label">Nombre <span class="req">*</span></label>
            <input type="text" name="name" class="na-input" value="{{ old('name') }}" required>
            @error('name')<div class="field-error">{{ $message }}</div>@enderror
            <div class="row">
              <div class="col-md-6"><label class="na-label">Tipo</label><select class="custom-select-target" disabled><option>Consumible</option></select></div>
              <div class="col-md-6">
                <label class="na-label">Categoría <span class="req">*</span></label>
                <div class="na-cat-row">
                  <select name="inventory_category_id" class="custom-select-target js-category-select" required>
                    <option value="">Seleccionar...</option>
                    @foreach($categories as $cat)<option value="{{ $cat->id }}" @selected(old('inventory_category_id')==$cat->id)>{{ $cat->name }}</option>@endforeach
                  </select>
                  <button type="button" class="na-cat-add js-add-category"><i class="bi bi-plus"></i></button>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6"><label class="na-label">Stock actual <span class="req">*</span></label><input type="number" min="0" name="stock" class="na-input" value="{{ old('stock', 0) }}" required></div>
              <div class="col-md-6"><label class="na-label">Stock mínimo</label><input type="number" min="0" name="stock_min" class="na-input" value="{{ old('stock_min', 0) }}"></div>
            </div>
            <div class="row">
              <div class="col-md-6"><label class="na-label">Stock máximo</label><input type="number" min="0" name="stock_max" class="na-input" value="{{ old('stock_max', 0) }}"></div>
              <div class="col-md-6"><label class="na-label">Unidad</label><input type="text" name="unit" class="na-input" value="{{ old('unit', 'piezas') }}"></div>
            </div>
          </div>
          <div class="na-panel" data-na-panel="detalles">
            <div class="row">
              <div class="col-md-6"><label class="na-label">Ubicación</label><input type="text" name="location" class="na-input" value="{{ old('location') }}"></div>
              <div class="col-md-6"><label class="na-label">Departamento</label><input type="text" name="department" class="na-input" value="{{ old('department') }}"></div>
            </div>
            <div class="row">
              <div class="col-md-6"><label class="na-label">Proveedor</label><input type="text" name="supplier" class="na-input" value="{{ old('supplier') }}"></div>
              <div class="col-md-6"><label class="na-label">Fecha Compra</label><input type="date" name="purchase_date" class="na-input" value="{{ old('purchase_date') }}"></div>
            </div>
            <div class="row">
              <div class="col-md-6"><label class="na-label">Costo ($)</label><input type="number" step="0.01" name="purchase_cost" class="na-input" value="{{ old('purchase_cost') }}"></div>
              <div class="col-md-6"><label class="na-label">Garantía hasta</label><input type="date" name="warranty_until" class="na-input" value="{{ old('warranty_until') }}"></div>
            </div>
            <label class="na-label">Notas</label><textarea name="notes" class="na-textarea">{{ old('notes') }}</textarea>
          </div>
        </div>
        <div class="na-foot">
          <button type="button" class="na-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="na-btn-save" id="consSubmitBtn">Guardar consumible</button>
        </div>
      </form>
    </div>
  </div>
</div>
{{-- Categoría --}}
<div class="modal fade" id="newCategoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="na-head"><h5>Nueva categoría</h5><button type="button" class="na-close" data-bs-dismiss="modal"><i class="bi bi-x"></i></button></div>
      <div class="na-body">
        <label class="na-label">Nombre de la categoría</label>
        <input type="text" id="newCategoryInput" class="na-input" placeholder="Ej. Equipo de cómputo" autocomplete="off">
      </div>
      <div class="na-foot">
        <button type="button" class="na-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="na-btn-save" id="saveCategoryBtn">Crear</button>
      </div>
    </div>
  </div>
</div>

{{-- Movimiento de stock --}}
<div class="modal fade" id="stockMoveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <form method="POST" action="" id="stockMoveForm">
        @csrf
        <div class="na-head"><h5>Movimiento de stock</h5><button type="button" class="na-close" data-bs-dismiss="modal"><i class="bi bi-x"></i></button></div>
        <div class="na-body">
          <label class="na-label">Tipo de movimiento</label>
          <select name="movement_type" class="custom-select-target">
            <option value="entrada">Entrada (+)</option>
            <option value="salida">Salida (−)</option>
          </select>
          <label class="na-label">Cantidad <span class="req">*</span></label>
          <input type="number" min="1" name="quantity" class="na-input" value="1" required>
          <label class="na-label">Motivo</label>
          <input type="text" name="reason" class="na-input" placeholder="Ej. Compra, entrega a oficina...">
        </div>
        <div class="na-foot">
          <button type="button" class="na-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="na-btn-save">Registrar</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Confirmar borrado --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="na-head"><h5>¿Eliminar registro?</h5><button type="button" class="na-close" data-bs-dismiss="modal"><i class="bi bi-x"></i></button></div>
      <div class="na-body"><p style="color:var(--muted);margin:0;">Esta acción no se puede deshacer.</p></div>
      <div class="na-foot">
        <button type="button" class="na-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="na-btn-save" id="confirmDeleteBtn" style="background:var(--danger);">Eliminar</button>
      </div>
    </div>
  </div>
</div>

<div class="toast-wrap" id="toastWrap"></div>
<div class="screen-overlay" id="screenOverlay"></div>

<div class="custom-drawer" id="itemDrawer">
  <div class="drawer-head">
    <h5 class="drawer-title" id="itemDrawerLabel">Detalle</h5>
    <button type="button" class="drawer-close" id="drawerCloseBtn"><i class="bi bi-x"></i></button>
  </div>
  <div class="drawer-body">
    <div class="drawer-img" id="drawerImageWrap"></div>
    <div class="drawer-chips">
      <span class="chip" id="drawerStatusChip">Disponible</span>
      <span class="chip chip-cat" id="drawerTypeChip">Activo Fijo</span>
      <span class="chip chip-cat" id="drawerCategoryChip">Categoría</span>
    </div>
    <div class="info-grid" id="fixedInfoGrid">
      <div><div class="info-label">Marca</div><div class="info-value" id="drawerBrand">—</div></div>
      <div><div class="info-label">Modelo</div><div class="info-value" id="drawerModel">—</div></div>
      <div><div class="info-label">No. Serie</div><div class="info-value" id="drawerSerial">—</div></div>
      <div><div class="info-label">Ubicación</div><div class="info-value" id="drawerLocation">—</div></div>
    </div>
    <div id="consumableStockBox" class="d-none-force" style="background:var(--bg);border-radius:8px;padding:16px;margin-bottom:24px;">
      <div class="info-label" style="color:var(--ink);">Control de Stock</div>
      <div style="display:flex;justify-content:space-between;margin:8px 0;font-size:14px;"><span>Actual: <strong id="drawerStockNow">0</strong></span><span>Máx: <strong id="drawerStockMax">0</strong></span></div>
      <div class="stock-bar mb-2"><div id="drawerStockFill" class="stock-fill fill-green" style="width:0%;"></div></div>
      <div class="small" style="color:var(--muted);">Mínimo: <span id="drawerStockMin">0</span></div>
      <div id="drawerStockWarning" class="small text-danger fw-semibold d-none-force mt-1"><i class="bi bi-exclamation-triangle-fill"></i> Stock bajo</div>
    </div>
    <div class="drawer-section-title">Descripción</div>
    <div class="drawer-desc" id="drawerDescription">Sin descripción</div>

    <div id="drawerMntWrap" class="d-none-force">
      <div class="drawer-section-title">Mantenimientos</div>
      <div class="mnt-list" id="drawerMntList"></div>
    </div>

    <div class="drawer-actions">
      <div class="drawer-row">
        <button type="button" class="btn-outline" id="drawerEditBtn"><i class="bi bi-pencil"></i> Editar</button>
        <button type="button" class="btn-ghost" id="drawerQrBtn" data-bs-toggle="modal" data-bs-target="#qrModal"><i class="bi bi-qr-code"></i></button>
      </div>
      <a href="#" class="btn-outline d-none-force" id="drawerAssignBtn"><i class="bi bi-person-check"></i> Asignar</a>
      <button type="button" class="btn-outline d-none-force" id="drawerStockBtn"><i class="bi bi-arrow-left-right"></i> Movimiento de stock</button>
      <a href="{{ route('maintenance.index') }}" class="btn-outline d-none-force" id="drawerMntBtn"><i class="bi bi-tools"></i> Programar mantenimiento</a>
      <button type="button" class="btn-danger-ghost" id="drawerDeleteBtn"><i class="bi bi-trash"></i></button>
    </div>
  </div>
</div>

{{-- form oculto de borrado --}}
<form id="deleteForm" method="POST" action="" class="d-none">@csrf @method('DELETE')</form>

{{-- QR --}}
<div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content" style="padding:24px;">
      <div class="d-flex justify-content-between mb-3">
        <h6 class="fw-bold m-0" id="qrModalTitle" style="color:var(--ink);">Etiqueta QR</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div id="printableQrArea" style="text-align:center;padding:16px;border:1px solid var(--line);border-radius:12px;">
        <div id="qrCodeBox" style="display:inline-block;margin-bottom:12px;"></div>
        <div id="qrName" style="font-weight:700;font-size:16px;color:var(--ink);">Activo</div>
        <div id="qrSerial" style="font-size:12px;color:var(--muted);">S/N</div>
      </div>
      <button type="button" class="na-btn-save w-100 mt-3" onclick="printQrLabel()"><i class="bi bi-printer"></i> Imprimir</button>
    </div>
  </div>
</div>

@php
  $flashOk = session('ok'); $flashBad = session('bad');
@endphp
<script>
  window.__flashOk = @json($flashOk);
  window.__flashBad = @json($flashBad);
  window.__reopenContext = @json(old('_form_context'));
  window.__reopenIsEdit = @json((bool) old('item_id'));
</script>
<script>
/* ============ Toasts ============ */
function toast(msg, type='ok'){
  const wrap=document.getElementById('toastWrap'); if(!wrap||!msg) return;
  const el=document.createElement('div'); el.className=`toast-msg ${type}`; el.textContent=msg;
  wrap.appendChild(el);
  setTimeout(()=>{ el.style.opacity='0'; el.style.transform='translateY(-8px)'; setTimeout(()=>el.remove(),300); },3200);
}
if(window.__flashOk) toast(window.__flashOk,'ok');
if(window.__flashBad) toast(window.__flashBad,'bad');

/* ============ Custom Select (teclado + ARIA) ============ */
class CustomSelect{
  constructor(select){
    this.select=select; this.select.style.display='none';
    this.wrapper=document.createElement('div'); this.wrapper.className='custom-select-wrapper';
    select.parentNode.insertBefore(this.wrapper,select); this.wrapper.appendChild(select);
    this.trigger=document.createElement('div'); this.trigger.className='custom-select-trigger';
    this.trigger.tabIndex=0; this.trigger.setAttribute('role','combobox'); this.trigger.setAttribute('aria-haspopup','listbox'); this.trigger.setAttribute('aria-expanded','false');
    this.panel=document.createElement('div'); this.panel.className='custom-select-panel'; this.panel.setAttribute('role','listbox');
    this.wrapper.appendChild(this.trigger); this.wrapper.appendChild(this.panel);
    this.activeIndex=-1; this.render(); this.bind(); select._customSelect=this;
  }
  render(){
    this.panel.innerHTML='';
    const sel=this.select.options[this.select.selectedIndex];
    this.trigger.innerHTML=`<span>${sel?sel.text:''}</span><i class="bi bi-chevron-down chevron"></i>`;
    Array.from(this.select.options).forEach((opt,i)=>{
      const it=document.createElement('div');
      it.className=`custom-select-option ${opt.selected?'selected':''}`;
      it.setAttribute('role','option'); it.dataset.index=i;
      it.innerHTML=`<span>${opt.text}</span> ${opt.selected?'<i class="bi bi-check2"></i>':''}`;
      it.addEventListener('click',e=>{ e.stopPropagation(); this.pick(i); });
      this.panel.appendChild(it);
    });
  }
  pick(i){ this.select.selectedIndex=i; this.select.dispatchEvent(new Event('change')); this.close(); this.render(); this.trigger.focus(); }
  open(){ this.wrapper.classList.add('open'); this.trigger.classList.add('open'); this.trigger.setAttribute('aria-expanded','true'); this.activeIndex=this.select.selectedIndex<0?0:this.select.selectedIndex; this.highlight(); }
  close(){ this.wrapper.classList.remove('open'); this.trigger.classList.remove('open'); this.trigger.setAttribute('aria-expanded','false'); }
  toggle(){ this.wrapper.classList.contains('open')?this.close():this.open(); }
  highlight(){ const opts=this.panel.querySelectorAll('.custom-select-option'); opts.forEach((o,i)=>o.classList.toggle('active-key',i===this.activeIndex)); const a=opts[this.activeIndex]; if(a) a.scrollIntoView({block:'nearest'}); }
  bind(){
    this.trigger.addEventListener('click',e=>{ e.stopPropagation(); this.toggle(); });
    this.trigger.addEventListener('keydown',e=>{
      if(['ArrowDown','ArrowUp','Enter',' '].includes(e.key)) e.preventDefault();
      if(!this.wrapper.classList.contains('open') && (e.key==='ArrowDown'||e.key==='Enter'||e.key===' ')){ this.open(); return; }
      if(e.key==='ArrowDown'){ this.activeIndex=Math.min(this.select.options.length-1,this.activeIndex+1); this.highlight(); }
      else if(e.key==='ArrowUp'){ this.activeIndex=Math.max(0,this.activeIndex-1); this.highlight(); }
      else if(e.key==='Enter'||e.key===' '){ if(this.activeIndex>=0) this.pick(this.activeIndex); }
      else if(e.key==='Escape'){ this.close(); }
    });
    document.addEventListener('click',e=>{ if(!this.wrapper.contains(e.target)) this.close(); });
  }
  refresh(){ this.render(); }
}
document.querySelectorAll('.custom-select-target').forEach(s=>new CustomSelect(s));

/* ============ Tabs, filtros, orden, paginación ============ */
const PAGE_SIZE=12;
let activeTab='activo_fijo';
const limits={activo_fijo:PAGE_SIZE,consumible:PAGE_SIZE};
const tabButtons=document.querySelectorAll('.tab-btn');
const panels={activo_fijo:document.getElementById('panel-activo_fijo'),consumible:document.getElementById('panel-consumible')};
const boardSearch=document.getElementById('boardSearch');
const boardCategory=document.getElementById('boardCategory');
const boardStatus=document.getElementById('boardStatus');
const boardSort=document.getElementById('boardSort');

function setTab(t){
  activeTab=t;
  tabButtons.forEach(b=>b.classList.toggle('active',b.dataset.tab===t));
  Object.keys(panels).forEach(k=>panels[k]&&panels[k].classList.toggle('d-none-force',k!==t));
  limits[t]=PAGE_SIZE; applyFilters();
}
tabButtons.forEach(b=>b.addEventListener('click',()=>setTab(b.dataset.tab)));

function getCards(tab){ const g=document.getElementById('grid-'+tab); return g?Array.from(g.querySelectorAll('.js-item-card')):[]; }

function sortCards(){
  const mode=boardSort?boardSort.value:'recientes';
  ['activo_fijo','consumible'].forEach(tab=>{
    const g=document.getElementById('grid-'+tab); if(!g) return;
    Array.from(g.children).sort((a,b)=>{
      if(mode==='nombre') return (a.dataset.sortName||'').localeCompare(b.dataset.sortName||'');
      if(mode==='stock') return (Number(a.dataset.stock)||0)-(Number(b.dataset.stock)||0);
      return (Number(b.dataset.id)||0)-(Number(a.dataset.id)||0);
    }).forEach(c=>g.appendChild(c));
  });
}

function applyFilters(){
  const q=(boardSearch.value||'').trim().toLowerCase();
  const cat=(boardCategory.value||'').trim().toLowerCase();
  const st=(boardStatus.value||'').trim().toLowerCase();
  let shown=0;
  getCards(activeTab).forEach(card=>{
    const ok=(!q||(card.dataset.search||'').includes(q))
      && (!cat||(card.dataset.category||'').toLowerCase()===cat)
      && (!st||(card.dataset.status||'').toLowerCase()===st);
    card.__matches=ok;
    if(ok && shown<limits[activeTab]){ card.classList.remove('d-none-force'); shown++; }
    else card.classList.add('d-none-force');
  });
  const total=getCards(activeTab).filter(c=>c.__matches).length;
  const btn=document.querySelector(`.js-load-more[data-tab="${activeTab}"]`);
  if(btn) btn.parentElement.style.display = total>limits[activeTab] ? '' : 'none';
}
boardSearch.addEventListener('input',()=>{limits[activeTab]=PAGE_SIZE;applyFilters();});
[boardCategory,boardStatus].forEach(el=>el.addEventListener('change',()=>{limits[activeTab]=PAGE_SIZE;applyFilters();}));
if(boardSort) boardSort.addEventListener('change',()=>{sortCards();applyFilters();});
document.querySelectorAll('.js-load-more').forEach(b=>b.addEventListener('click',()=>{limits[b.dataset.tab]+=PAGE_SIZE;applyFilters();}));

sortCards(); setTab('activo_fijo');

/* ============ Drawer ============ */
const overlay=document.getElementById('screenOverlay');
const drawer=document.getElementById('itemDrawer');
let currentCard=null;
function openDrawer(){ overlay.classList.add('show'); drawer.classList.add('show'); document.body.style.overflow='hidden'; }
function closeDrawer(){ overlay.classList.remove('show'); drawer.classList.remove('show'); document.body.style.overflow=''; }
overlay.addEventListener('click',closeDrawer);
document.getElementById('drawerCloseBtn').addEventListener('click',closeDrawer);
document.addEventListener('keydown',e=>{ if(e.key==='Escape') closeDrawer(); });

document.querySelectorAll('.js-item-card').forEach(card=>card.addEventListener('click',()=>fillDrawer(card)));

function fillDrawer(card){
  currentCard=card; const d=card.dataset; const isCons=d.isConsumable==='1';
  document.getElementById('itemDrawerLabel').textContent=d.name||'Detalle';
  document.getElementById('drawerImageWrap').innerHTML = d.photo
    ? `<img src="${d.photo}" alt="">`
    : `<div class="ph"><i class="bi ${isCons?'bi-box-seam':'bi-laptop'}"></i></div>`;
  const sc=document.getElementById('drawerStatusChip'); sc.className=`chip ${d.statusClass||'state-disponible'}`; sc.textContent=d.statusLabel||'Disponible';
  document.getElementById('drawerTypeChip').textContent=d.typeLabel||'';
  document.getElementById('drawerCategoryChip').textContent=d.category||'';
  document.getElementById('drawerBrand').textContent=d.brand||'—';
  document.getElementById('drawerModel').textContent=d.model||'—';
  document.getElementById('drawerSerial').textContent=d.serial||'—';
  document.getElementById('drawerLocation').textContent=d.location||'—';
  document.getElementById('drawerDescription').textContent=d.notes||'Sin descripción';

  const fixedGrid=document.getElementById('fixedInfoGrid'), stockBox=document.getElementById('consumableStockBox');
  const qrBtn=document.getElementById('drawerQrBtn'), assignBtn=document.getElementById('drawerAssignBtn');
  const stockBtn=document.getElementById('drawerStockBtn'), mntBtn=document.getElementById('drawerMntBtn'), mntWrap=document.getElementById('drawerMntWrap');

  if(isCons){
    fixedGrid.classList.add('d-none-force'); stockBox.classList.remove('d-none-force');
    qrBtn.classList.add('d-none-force'); assignBtn.classList.add('d-none-force');
    stockBtn.classList.remove('d-none-force'); mntBtn.classList.add('d-none-force'); mntWrap.classList.add('d-none-force');
    const stock=Number(d.stock),min=Number(d.stockMin),max=Number(d.stockMax);
    document.getElementById('drawerStockNow').textContent=stock;
    document.getElementById('drawerStockMax').textContent=max;
    document.getElementById('drawerStockMin').textContent=min;
    document.getElementById('drawerStockFill').style.width = max>0?`${Math.min(100,(stock/max)*100)}%`:'0%';
    document.getElementById('drawerStockFill').className=`stock-fill ${stock<=min?'fill-red':((stock/max)<=0.4?'fill-amber':'fill-green')}`;
    document.getElementById('drawerStockWarning').classList.toggle('d-none-force',stock>min);
  } else {
    fixedGrid.classList.remove('d-none-force'); stockBox.classList.add('d-none-force');
    qrBtn.classList.remove('d-none-force'); assignBtn.classList.remove('d-none-force');
    
    // Le asignamos el href al botón (anchor) del drawer tomando el valor guardado en la card
    assignBtn.href = d.assignUrl;
    
    stockBtn.classList.add('d-none-force'); mntBtn.classList.remove('d-none-force');
    let mnts=[]; try{ mnts=JSON.parse(d.maintenances||'[]'); }catch(e){}
    if(mnts.length){
      mntWrap.classList.remove('d-none-force');
      document.getElementById('drawerMntList').innerHTML=mnts.map(m=>`<div class="mnt-item"><span>${m.date||''} · ${m.type||''}</span><span>${m.status||''}</span></div>`).join('');
    } else mntWrap.classList.add('d-none-force');
    const box=document.getElementById('qrCodeBox'); box.innerHTML='';
    new QRCode(box,{text:d.qrText||('item:'+d.id),width:128,height:128,correctLevel:QRCode.CorrectLevel.H});
    document.getElementById('qrName').textContent=d.name;
    document.getElementById('qrSerial').textContent='S/N: '+(d.serial||d.tag||'—');
    document.getElementById('qrModalTitle').textContent='Etiqueta — '+d.name;
  }
  openDrawer();
}

/* ============ Acciones del drawer ============ */
document.getElementById('drawerEditBtn').addEventListener('click',()=>{ if(currentCard) openEditModal(currentCard); });
document.getElementById('drawerDeleteBtn').addEventListener('click',()=>{
  if(!currentCard) return;
  document.getElementById('deleteForm').action=currentCard.dataset.deleteUrl;
  bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmModal')).show();
});
document.getElementById('confirmDeleteBtn').addEventListener('click',()=>document.getElementById('deleteForm').submit());

document.getElementById('drawerStockBtn').addEventListener('click',()=>{
  if(!currentCard) return;
  document.getElementById('stockMoveForm').action=currentCard.dataset.stockUrl;
  bootstrap.Modal.getOrCreateInstance(document.getElementById('stockMoveModal')).show();
});

/* ============ Modales crear/editar ============ */
function fillModalFields(modal,d){
  const set=(name,val)=>{ const el=modal.querySelector(`[name="${name}"]`); if(el){ el.value=val??''; if(el._customSelect) el._customSelect.refresh(); } };
  set('name',d.name); set('inventory_category_id',d.categoryId); set('asset_status',d.status); set('condition',d.condition);
  set('brand',d.brand); set('model',d.model); set('serial_number',d.serial); set('internal_code',d.internalCode);
  set('location',d.location); set('department',d.department); set('supplier',d.supplier); set('purchase_date',d.purchaseDate);
  set('purchase_cost',d.purchaseCost); set('warranty_until',d.warrantyUntil); set('processor',d.processor); set('ram',d.ram);
  set('storage',d.storage); set('operating_system',d.operatingSystem); set('mac_address',d.macAddress); set('notes',d.notes);
  set('stock',d.stock); set('stock_min',d.stockMin); set('stock_max',d.stockMax); set('unit',d.unit);
  const drop=modal.querySelector('.na-drop'); if(drop) drop.innerHTML = d.photo?`<img src="${d.photo}">`:'<i class="bi bi-camera"></i>';
  const rm=modal.querySelector('.na-remove-photo'); if(rm){ rm.style.display=d.photo?'flex':'none'; const cb=rm.querySelector('input'); if(cb) cb.checked=false; }
}
function openEditModal(card){
  const d=card.dataset; const isCons=d.isConsumable==='1';
  const modal=document.getElementById(isCons?'newConsumableModal':'newAssetModal');
  modal.querySelector('[name="item_id"]').value=d.id;
  fillModalFields(modal,d);
  if(isCons){ document.getElementById('consModalTitle').textContent='Editar Consumible'; document.getElementById('consSubmitBtn').textContent='Guardar cambios'; }
  else { document.getElementById('assetModalTitle').textContent='Editar Activo'; document.getElementById('assetSubmitBtn').textContent='Guardar cambios'; }
  closeDrawer();
  bootstrap.Modal.getOrCreateInstance(modal).show();
}

function clearForm(modal){
  modal.querySelectorAll('input[type="text"],input[type="number"],input[type="date"],textarea').forEach(el=>el.value='');
  modal.querySelectorAll('select').forEach(s=>{ if(!s.disabled) s.selectedIndex=0; });
  modal.querySelector('[name="item_id"]').value='';
  if(modal.id==='newConsumableModal'){
    ['stock','stock_min','stock_max'].forEach(n=>{ const e=modal.querySelector(`[name="${n}"]`); if(e) e.value=0; });
    const u=modal.querySelector('[name="unit"]'); if(u) u.value='piezas';
  }
  const file=modal.querySelector('input[type="file"]'); if(file) file.value='';
  const drop=modal.querySelector('.na-drop'); if(drop) drop.innerHTML='<i class="bi bi-camera"></i>';
  const rm=modal.querySelector('.na-remove-photo'); if(rm) rm.style.display='none';
  modal.querySelectorAll('.custom-select-target').forEach(s=>{ if(s._customSelect) s._customSelect.refresh(); });
}

['newAssetModal','newConsumableModal'].forEach(id=>{
  const modal=document.getElementById(id); if(!modal) return;
  const tabs=modal.querySelectorAll('.na-tab'), ps=modal.querySelectorAll('.na-panel');
  tabs.forEach(t=>t.addEventListener('click',()=>{ tabs.forEach(x=>x.classList.toggle('active',x===t)); ps.forEach(p=>p.classList.toggle('active',p.dataset.naPanel===t.dataset.naTab)); }));
  const photo=modal.querySelector('input[type="file"]'), drop=modal.querySelector('.na-drop');
  if(photo&&drop) photo.addEventListener('change',()=>{ if(photo.files[0]){ const r=new FileReader(); r.onload=e=>drop.innerHTML=`<img src="${e.target.result}">`; r.readAsDataURL(photo.files[0]); } });
  modal.addEventListener('hidden.bs.modal',()=>{
    if(modal.dataset.keepOpen==='1'){ modal.dataset.keepOpen=''; return; }
    clearForm(modal);
    if(id==='newAssetModal'){ document.getElementById('assetModalTitle').textContent='Nuevo Activo'; document.getElementById('assetSubmitBtn').textContent='Guardar activo'; }
    else { document.getElementById('consModalTitle').textContent='Nuevo Consumible'; document.getElementById('consSubmitBtn').textContent='Guardar consumible'; }
    tabs.forEach((t,i)=>t.classList.toggle('active',i===0)); ps.forEach((p,i)=>p.classList.toggle('active',i===0));
  });
});

document.getElementById('btnNew').addEventListener('click',()=>{
  const id=activeTab==='consumible'?'newConsumableModal':'newAssetModal';
  bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).show();
});

/* ============ Categoría (modal que reemplaza al actual y regresa) ============ */
const CSRF_TOKEN='{{ csrf_token() }}';
const CATEGORY_STORE_URL='{{ route('assets.categories.store') }}';
const catModalEl=document.getElementById('newCategoryModal');
let catReturnModalEl=null, catReturnSelect=null;

document.querySelectorAll('.js-add-category').forEach(btn=>{
  btn.addEventListener('click',()=>{
    const parent=btn.closest('.modal');
    catReturnModalEl=parent||null;
    catReturnSelect=btn.closest('.na-cat-row').querySelector('.js-category-select');
    document.getElementById('newCategoryInput').value='';
    if(parent){
      parent.dataset.keepOpen='1';
      parent.addEventListener('hidden.bs.modal',()=>bootstrap.Modal.getOrCreateInstance(catModalEl).show(),{once:true});
      bootstrap.Modal.getInstance(parent).hide();
    } else {
      bootstrap.Modal.getOrCreateInstance(catModalEl).show();
    }
  });
});

async function createCategory(){
  const name=document.getElementById('newCategoryInput').value.trim();
  if(!name){ toast('Escribe un nombre','bad'); return; }
  try{
    const res=await fetch(CATEGORY_STORE_URL,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF_TOKEN},body:JSON.stringify({name})});
    if(!res.ok) throw new Error();
    const cat=await res.json();
    document.querySelectorAll('.js-category-select').forEach(sel=>{
      if(![...sel.options].some(o=>o.value==cat.id)) sel.add(new Option(cat.name,cat.id));
      if(sel._customSelect) sel._customSelect.refresh();
    });
    if(catReturnSelect){ catReturnSelect.value=cat.id; if(catReturnSelect._customSelect) catReturnSelect._customSelect.refresh(); }
    catModalEl.addEventListener('hidden.bs.modal',()=>{ if(catReturnModalEl) bootstrap.Modal.getOrCreateInstance(catReturnModalEl).show(); },{once:true});
    bootstrap.Modal.getInstance(catModalEl).hide();
    toast('Categoría creada','ok');
  }catch(e){ toast('No se pudo crear la categoría','bad'); }
}
document.getElementById('saveCategoryBtn').addEventListener('click',createCategory);
document.getElementById('newCategoryInput').addEventListener('keydown',e=>{ if(e.key==='Enter'){ e.preventDefault(); createCategory(); } });

/* ============ Etiquetas QR ============ */
function printQrLabel(){
  const area=document.getElementById('printableQrArea').cloneNode(true);
  const cv=document.getElementById('qrCodeBox').querySelector('canvas');
  if(cv) area.querySelector('#qrCodeBox').innerHTML=`<img src="${cv.toDataURL()}" width="128" height="128">`;
  const w=window.open('','_blank','width=400,height=520');
  w.document.write(`<html><head><title>Etiqueta</title><style>body{font-family:Arial,sans-serif;text-align:center;padding:20px;}img{max-width:100%;}</style></head><body>${area.outerHTML}<scr`+`ipt>window.onload=function(){window.print();window.close();}</scr`+`ipt></body></html>`);
  w.document.close();
}
window.printQrLabel=printQrLabel;

document.getElementById('btnPrintLabels').addEventListener('click',()=>{
  const cards=getCards('activo_fijo').filter(c=>c.__matches!==false);
  if(!cards.length){ toast('No hay activos para imprimir','bad'); return; }
  const temp=document.createElement('div');
  cards.forEach(c=>{
    const d=c.dataset;
    const box=document.createElement('div'); box.style.cssText='display:inline-block;border:1px solid #ccc;border-radius:8px;padding:10px;margin:6px;text-align:center;width:160px;vertical-align:top;';
    const holder=document.createElement('div');
    new QRCode(holder,{text:d.qrText||('item:'+d.id),width:110,height:110,correctLevel:QRCode.CorrectLevel.H});
    const cv=holder.querySelector('canvas'); if(cv){ holder.innerHTML=`<img src="${cv.toDataURL()}" width="110" height="110">`; }
    box.appendChild(holder);
    const nm=document.createElement('div'); nm.style.cssText='font-weight:700;font-size:13px;margin-top:6px;'; nm.textContent=d.name; box.appendChild(nm);
    const sn=document.createElement('div'); sn.style.cssText='font-size:11px;color:#666;'; sn.textContent='S/N: '+(d.serial||d.tag||'—'); box.appendChild(sn);
    temp.appendChild(box);
  });
  const w=window.open('','_blank','width=900,height=650');
  w.document.write(`<html><head><title>Etiquetas</title><style>body{font-family:Arial,sans-serif;}img{max-width:100%;}</style></head><body>${temp.innerHTML}<scr`+`ipt>window.onload=function(){window.print();window.close();}</scr`+`ipt></body></html>`);
  w.document.close();
});

/* ============ Exportar CSV ============ */
document.getElementById('btnExport').addEventListener('click',()=>{
  const rows=[['Nombre','Categoria','Marca','Modelo','Serie','Ubicacion','Stock','Estado']];
  getCards(activeTab).forEach(c=>{
    if(c.__matches===false) return;
    const d=c.dataset;
    rows.push([d.name,d.category,d.brand||'',d.model||'',d.serial||'',d.location||'',d.stock||'',d.statusLabel||'']);
  });
  const csv=rows.map(r=>r.map(v=>`"${String(v).replace(/"/g,'""')}"`).join(',')).join('\n');
  const blob=new Blob(['\ufeff'+csv],{type:'text/csv;charset=utf-8;'});
  const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=`inventario_${activeTab}.csv`; a.click();
});

/* ============ Reabrir modal si hubo error de validación ============ */
if(window.__reopenContext){
  const id=window.__reopenContext==='consumible'?'newConsumableModal':'newAssetModal';
  if(window.__reopenIsEdit){
    if(id==='newAssetModal'){ document.getElementById('assetModalTitle').textContent='Editar Activo'; document.getElementById('assetSubmitBtn').textContent='Guardar cambios'; }
    else { document.getElementById('consModalTitle').textContent='Editar Consumible'; document.getElementById('consSubmitBtn').textContent='Guardar cambios'; }
  }
  bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).show();
}

/* ============ Atajos por URL (desde el dashboard) ============ */
const params=new URLSearchParams(location.search);
if(params.get('tab')==='consumibles') setTab('consumible');
if(params.get('filter')==='low_stock'){ setTab('consumible'); boardStatus.value='bajo_stock'; if(boardStatus._customSelect) boardStatus._customSelect.refresh(); applyFilters(); }
if(params.get('new')==='consumible'){ setTab('consumible'); bootstrap.Modal.getOrCreateInstance(document.getElementById('newConsumableModal')).show(); }
else if(params.get('new')){ bootstrap.Modal.getOrCreateInstance(document.getElementById('newAssetModal')).show(); }
</script>
@endsection