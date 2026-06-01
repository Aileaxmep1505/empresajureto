@extends('layouts.app')
@section('title','Editar Activo')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
  :root{
    --bg:#f6f8fc;
    --card:#ffffff;
    --ink:#0f172a;
    --muted:#6b7280;
    --stroke:#e7ecf5;

    --blue-50:#eef4ff;
    --blue-100:#dbe9ff;
    --blue-600:#2f6fec;
    --blue-700:#245fcd;

    --green-50:#edfdf3;
    --green-100:#d9fbe7;
    --green-600:#198754;
    --green-700:#157347;

    --danger-bg:#fff1f2;
    --danger-stroke:#ffd5da;
    --danger-ink:#b42318;

    --soft:#f8faff;

    --radius-xl:24px;
    --radius-lg:18px;
    --radius-md:14px;
    --radius-sm:12px;

    --shadow:0 18px 40px rgba(15,23,42,.07);
    --shadow-soft:0 12px 26px rgba(15,23,42,.06);
    --ease:cubic-bezier(.2,.8,.2,1);
  }

  html,body{
    width:100%;
    overflow-x:hidden;
  }

  body{
    background:
      radial-gradient(circle at top left, rgba(47,111,236,.05), transparent 24%),
      radial-gradient(circle at top right, rgba(25,135,84,.04), transparent 22%),
      var(--bg);
  }

  @keyframes inUp{
    from{opacity:0; transform:translateY(12px);}
    to{opacity:1; transform:none;}
  }

  .view{
    width:100%;
    max-width:1450px;
    margin:28px auto;
    padding:0 20px 30px;
    animation:inUp .35s var(--ease) both;
  }

  .head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    margin-bottom:18px;
  }

  .title{
    margin:0;
    font-size:32px;
    letter-spacing:-.03em;
    color:var(--ink);
    font-weight:700;
  }

  .sub{
    margin:6px 0 0;
    color:var(--muted);
    font-size:14px;
  }

  .cardx{
    background:var(--card);
    border:1px solid rgba(15,23,42,.05);
    border-radius:var(--radius-xl);
    box-shadow:var(--shadow);
    padding:28px;
  }

  .grid{
    display:grid;
    grid-template-columns:1fr;
    gap:24px;
  }

  @media (min-width: 992px){
    .grid{
      grid-template-columns: minmax(0, 1.4fr) minmax(380px, .95fr);
      align-items:start;
    }
  }

  .panel-lite{
    background:linear-gradient(180deg,#ffffff 0%, #fbfcff 100%);
    border:1px solid var(--stroke);
    border-radius:22px;
    padding:22px;
  }

  .section-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-bottom:14px;
    flex-wrap:wrap;
  }

  .section-title{
    margin:0;
    color:var(--ink);
    font-size:16px;
    font-weight:700;
    letter-spacing:.01em;
  }

  .section-sub{
    margin:4px 0 0;
    color:var(--muted);
    font-size:13px;
  }

  .btn-soft{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    min-height:46px;
    background:var(--blue-50);
    color:var(--blue-700);
    border:1px solid var(--blue-100);
    border-radius:999px;
    padding:10px 16px;
    font-size:14px;
    font-weight:700;
    line-height:1;
    transition:all .14s var(--ease);
    user-select:none;
    text-decoration:none;
    box-shadow:0 8px 18px rgba(47,111,236,.06);
  }

  .btn-soft:hover{
    color:var(--blue-700);
    transform:translateY(-1px);
    box-shadow:0 14px 24px rgba(47,111,236,.10);
  }

  .btn-soft .icon{
    width:30px;
    height:30px;
    border-radius:999px;
    display:grid;
    place-items:center;
    background:#fff;
    border:1px solid var(--blue-100);
    color:var(--blue-700);
    font-size:17px;
    font-weight:800;
    line-height:1;
    flex:0 0 auto;
  }

  .btn-danger-soft{
    background:var(--danger-bg);
    color:var(--danger-ink);
    border:1px solid var(--danger-stroke);
  }

  .btn-danger-soft:hover{
    color:var(--danger-ink);
  }

  .btn-danger-soft .icon{
    border:1px solid var(--danger-stroke);
    color:var(--danger-ink);
  }

  .btn-main{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    min-height:56px;
    padding:0 22px;
    border:none;
    border-radius:16px;
    background:#2f6fec;
    color:#fff;
    font-size:15px;
    font-weight:800;
    letter-spacing:.01em;
    box-shadow:0 16px 30px rgba(47,111,236,.20);
    transition:transform .14s var(--ease), box-shadow .14s var(--ease), background .14s var(--ease);
  }

  .btn-main:hover{
    color:#fff;
    background:#245fcd;
    transform:translateY(-1px);
    box-shadow:0 18px 34px rgba(47,111,236,.24);
  }

  .btn-main .icon{
    width:34px;
    height:34px;
    border-radius:999px;
    display:grid;
    place-items:center;
    background:rgba(255,255,255,.14);
    border:1px solid rgba(255,255,255,.18);
    font-size:18px;
    line-height:1;
  }

  .btn-muted{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    min-height:56px;
    padding:0 18px;
    border-radius:16px;
    border:1px solid var(--stroke);
    background:#fff;
    color:var(--muted);
    text-decoration:none;
    font-size:14px;
    font-weight:700;
    box-shadow:var(--shadow-soft);
    transition:all .14s var(--ease);
  }

  .btn-muted:hover{
    color:var(--ink);
    transform:translateY(-1px);
    border-color:#d7e0ef;
  }

  .btn-muted .icon{
    width:32px;
    height:32px;
    border-radius:999px;
    display:grid;
    place-items:center;
    border:1px solid var(--stroke);
    background:#f8fafc;
    font-size:16px;
    line-height:1;
  }

  .btn-modal-save{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    min-height:48px;
    padding:0 20px;
    border:none;
    border-radius:14px;
    background:#2f6fec;
    color:#fff;
    font-size:14px;
    font-weight:800;
    box-shadow:0 12px 24px rgba(47,111,236,.18);
    transition:all .14s var(--ease);
  }

  .btn-modal-save:hover{
    background:#245fcd;
    color:#fff;
    transform:translateY(-1px);
  }

  .btn-modal-save:disabled{
    opacity:.7;
    cursor:not-allowed;
    transform:none;
  }

  .btn-modal-save .icon{
    width:28px;
    height:28px;
    border-radius:999px;
    display:grid;
    place-items:center;
    background:rgba(255,255,255,.14);
    border:1px solid rgba(255,255,255,.18);
    font-size:16px;
    line-height:1;
  }

  .link-add-category{
    display:inline-block;
    margin-top:10px;
    padding:0;
    border:none;
    background:transparent;
    color:#111111;
    font-size:13px;
    font-weight:700;
    text-decoration:underline;
    text-underline-offset:3px;
    transition:opacity .14s var(--ease);
    cursor:pointer;
  }

  .link-add-category:hover{
    color:#111111;
    opacity:.78;
  }

  .form-grid{
    display:grid;
    grid-template-columns:1fr;
    gap:14px;
  }

  @media (min-width: 768px){
    .form-grid.two{
      grid-template-columns:repeat(2, minmax(0, 1fr));
    }
  }

  .fg{
    position:relative;
  }

  .ctl{
    width:100%;
    min-height:60px;
    border:1px solid var(--stroke);
    border-radius:16px;
    padding:22px 14px 10px;
    background:var(--soft);
    color:var(--ink);
    font-size:14px;
    font-weight:600;
    outline:none;
    transition:border-color .16s var(--ease), box-shadow .16s var(--ease), transform .16s var(--ease), background .16s var(--ease);
  }

  .ctl:hover{
    background:#fff;
    border-color:#dbe5f1;
  }

  textarea.ctl{
    min-height:126px;
    resize:vertical;
    padding-top:24px;
  }

  select.ctl{
    padding-top:23px;
    padding-bottom:9px;
  }

  .ctl:focus{
    background:#fff;
    border-color:rgba(47,111,236,.28);
    box-shadow:0 0 0 5px rgba(47,111,236,.12);
    transform:translateY(-1px);
  }

  .fg label{
    position:absolute;
    left:12px;
    top:17px;
    padding:0 7px;
    border-radius:999px;
    color:var(--muted);
    font-size:13px;
    font-weight:600;
    pointer-events:none;
    transition:all .14s var(--ease);
    background:transparent;
  }

  .ctl:focus + label,
  .ctl:not(:placeholder-shown) + label,
  select.ctl.has-value + label{
    top:7px;
    font-size:11px;
    background:rgba(255,255,255,.97);
    box-shadow:0 4px 14px rgba(15,23,42,.06);
    color:var(--blue-700);
  }

  .ctl::placeholder{
    color:transparent;
  }

  select.ctl:invalid{
    color:transparent;
  }

  select.ctl option{
    color:var(--ink);
  }

  select.ctl:focus{
    color:var(--ink);
  }

  .hint{
    margin-top:8px;
    color:var(--muted);
    font-size:12px;
    line-height:1.4;
  }

  .divider{
    height:1px;
    background:linear-gradient(90deg, transparent 0%, var(--stroke) 20%, var(--stroke) 80%, transparent 100%);
    margin:8px 0 4px;
  }

  .type-badge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 12px;
    border-radius:999px;
    background:#f8fbff;
    border:1px solid var(--stroke);
    color:var(--muted);
    font-size:12px;
    font-weight:700;
  }

  .upload-card{
    background:linear-gradient(180deg,#ffffff 0%, #fbfcff 100%);
    border:1px solid var(--stroke);
    border-radius:22px;
    padding:22px;
    position:sticky;
    top:18px;
  }

  .upload{
    border:1.6px dashed rgba(15,23,42,.16);
    background:#fff;
    border-radius:18px;
    padding:18px;
    transition:border-color .16s var(--ease), box-shadow .16s var(--ease), transform .16s var(--ease), background .16s var(--ease);
    cursor:pointer;
  }

  .upload:hover{
    border-color:rgba(47,111,236,.34);
    box-shadow:0 0 0 5px rgba(47,111,236,.08);
    transform:translateY(-1px);
    background:#fcfdff;
  }

  .preview{
    border:1px solid var(--stroke);
    border-radius:18px;
    overflow:hidden;
    background:#f7f9ff;
    aspect-ratio:1 / 1;
    display:flex;
    align-items:center;
    justify-content:center;
    margin-top:16px;
    width:100%;
    max-width:100%;
    min-height:360px;
  }

  .preview img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:none;
  }

  .ph{
    text-align:center;
    color:var(--muted);
    font-size:13px;
    padding:14px;
  }

  .current-image-wrap{
    border:1px solid var(--stroke);
    border-radius:18px;
    overflow:hidden;
    background:#fff;
    margin-top:16px;
  }

  .current-image-wrap img{
    width:100%;
    height:260px;
    object-fit:cover;
    display:block;
  }

  .actions{
    margin-top:22px;
    padding-top:18px;
    border-top:1px solid var(--stroke);
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    align-items:center;
  }

  .modal-pro .modal-content{
    border:none;
    border-radius:24px;
    box-shadow:0 28px 64px rgba(15,23,42,.18);
    overflow:hidden;
  }

  .modal-pro .modal-header{
    border-bottom:1px solid var(--stroke);
    background:linear-gradient(180deg,#fff 0%, #fbfcff 100%);
    padding:18px 18px 14px;
  }

  .modal-pro .modal-title{
    margin:0;
    font-weight:700;
    font-size:17px;
    color:var(--ink);
  }

  .modal-pro .modal-sub{
    margin:6px 0 0;
    color:var(--muted);
    font-size:13px;
  }

  .modal-pro .modal-body{
    padding:18px;
  }

  .modal-pro .modal-footer{
    border-top:1px solid var(--stroke);
    padding:14px 18px 18px;
    background:#fff;
    gap:10px;
  }

  .msg-err{
    display:none;
    margin-top:12px;
    padding:11px 13px;
    border-radius:14px;
    border:1px solid var(--danger-stroke);
    background:var(--danger-bg);
    color:var(--danger-ink);
    font-size:13px;
    font-weight:600;
  }

  .hidden-block{
    display:none !important;
  }

  .toast-wrap{
    position:fixed;
    top:20px;
    right:20px;
    z-index:1095;
    display:flex;
    flex-direction:column;
    gap:10px;
  }

  .toast-soft{
    min-width:290px;
    max-width:360px;
    background:#fff;
    border:1px solid #dbe9ff;
    color:#1e3a8a;
    border-radius:16px;
    box-shadow:0 18px 40px rgba(15,23,42,.12);
    overflow:hidden;
  }

  .toast-soft .toast-body{
    display:flex;
    align-items:flex-start;
    gap:12px;
    padding:14px 16px;
    font-size:14px;
    font-weight:600;
  }

  .toast-soft .ticon{
    width:34px;
    height:34px;
    border-radius:999px;
    display:grid;
    place-items:center;
    background:var(--blue-50);
    border:1px solid var(--blue-100);
    color:var(--blue-700);
    flex:0 0 auto;
    font-size:18px;
    line-height:1;
    font-weight:800;
  }

  .alert-custom{
    background:var(--danger-bg);
    border:1px solid var(--danger-stroke);
    color:var(--danger-ink);
    border-radius:16px;
    padding:12px 14px;
    margin-bottom:16px;
  }

  .mini-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:10px;
  }

  .checkline{
    display:flex;
    align-items:flex-start;
    gap:10px;
    padding:12px 14px;
    border-radius:16px;
    border:1px solid var(--stroke);
    background:#fff;
    margin-top:12px;
  }

  .checkline input{
    width:18px;
    height:18px;
    margin-top:2px;
  }

  @media (max-width: 767.98px){
    .view{
      padding:0 12px 22px;
    }

    .cardx{
      padding:16px;
    }

    .panel-lite,
    .upload-card{
      padding:15px;
    }

    .title{
      font-size:26px;
    }

    .btn-main,
    .btn-muted{
      width:100%;
    }

    .actions{
      align-items:stretch;
    }

    .preview{
      min-height:250px;
    }

    .current-image-wrap img{
      height:220px;
    }
  }
</style>

<div class="toast-wrap" id="toastWrap"></div>

<div class="view">

  <div class="head">
    <div>
      <h2 class="title">Editar Activo</h2>
      <div class="sub">Actualiza activos fijos y consumibles con la misma estructura de la vista de creación.</div>
    </div>
  </div>

  @if($errors->any())
    <div class="alert-custom">
      <div style="font-weight:700; margin-bottom:6px;">Revisa estos campos:</div>
      <ul class="mb-0">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @php
    $currentType = old('type', $item->type ?? 'activo_fijo');
  @endphp

  <form id="assetEditForm" method="POST" action="{{ route('assets.update',$item->id) }}" enctype="multipart/form-data" class="cardx">
    @csrf
    @method('PUT')

    <div class="grid">

      {{-- LEFT --}}
      <div>
        <div class="panel-lite">
          <div class="section-top">
            <div>
              <h3 class="section-title">Datos del registro</h3>
              <div class="section-sub">Completa los campos principales del activo o consumible.</div>
            </div>
          </div>

          <div class="form-grid two">
            <div class="fg">
              <select class="ctl" name="type" id="type" required placeholder=" ">
                <option value="" disabled {{ $currentType ? '' : 'selected' }}></option>
                <option value="activo_fijo" {{ $currentType === 'activo_fijo' ? 'selected' : '' }}>Activo Fijo</option>
                <option value="consumible" {{ $currentType === 'consumible' ? 'selected' : '' }}>Consumible</option>
              </select>
              <label>Tipo *</label>
            </div>

            <div>
              <div class="fg">
                <select class="ctl" name="inventory_category_id" id="inventory_category_id" required placeholder=" ">
                  <option value="" disabled {{ old('inventory_category_id', $item->inventory_category_id) ? '' : 'selected' }}></option>
                  @foreach($categories as $c)
                    <option value="{{ $c->id }}" {{ (string)old('inventory_category_id', $item->inventory_category_id) === (string)$c->id ? 'selected' : '' }}>
                      {{ $c->name }}
                    </option>
                  @endforeach
                </select>
                <label>Categoría *</label>
              </div>

              <button type="button" class="link-add-category" data-bs-toggle="modal" data-bs-target="#categoryModal">
                Agregar nueva categoría
              </button>
            </div>
          </div>

          <div class="divider"></div>

          <div class="form-grid" style="margin-top:14px;">
            <div class="fg">
              <input class="ctl" name="name" required placeholder=" " value="{{ old('name', $item->name) }}">
              <label>Nombre *</label>
            </div>
          </div>

          <div class="form-grid two" style="margin-top:14px;">
            <div class="fg">
              <input class="ctl" name="unit" placeholder=" " value="{{ old('unit', $item->unit ?? 'piezas') }}">
              <label>Unidad</label>
            </div>

            <div class="fg">
              <input class="ctl" type="number" min="0" name="stock" value="{{ old('stock', $item->stock ?? 0) }}" placeholder=" ">
              <label>Stock actual *</label>
            </div>

            <div class="fg" style="grid-column:1 / -1;">
              <input class="ctl" name="location" placeholder=" " value="{{ old('location', $item->location) }}">
              <label>Ubicación</label>
            </div>
          </div>

          <div class="mini-row" style="margin-top:16px;">
            <span class="type-badge" id="typeBadge">Configuración según el tipo seleccionado</span>
          </div>

          {{-- SOLO ACTIVO --}}
          <div id="fixedFields" class="form-grid two">
            <div class="fg">
              <select class="ctl" name="asset_status" id="asset_status" placeholder=" ">
                <option value="" disabled {{ old('asset_status', $item->asset_status) ? '' : 'selected' }}></option>
                <option value="disponible" {{ old('asset_status', $item->asset_status) === 'disponible' ? 'selected' : '' }}>Disponible</option>
                <option value="asignado" {{ old('asset_status', $item->asset_status) === 'asignado' ? 'selected' : '' }}>Asignado</option>
                <option value="en_reparacion" {{ old('asset_status', $item->asset_status) === 'en_reparacion' ? 'selected' : '' }}>En reparación</option>
                <option value="dado_de_baja" {{ old('asset_status', $item->asset_status) === 'dado_de_baja' ? 'selected' : '' }}>Dado de baja</option>
              </select>
              <label>Estado</label>
            </div>

            <div class="fg">
              <select class="ctl" name="condition" id="condition" placeholder=" ">
                <option value="" disabled {{ old('condition', $item->condition) ? '' : 'selected' }}></option>
                <option value="nuevo" {{ old('condition', $item->condition) === 'nuevo' ? 'selected' : '' }}>Nuevo</option>
                <option value="bueno" {{ old('condition', $item->condition) === 'bueno' ? 'selected' : '' }}>Bueno</option>
                <option value="regular" {{ old('condition', $item->condition) === 'regular' ? 'selected' : '' }}>Regular</option>
                <option value="malo" {{ old('condition', $item->condition) === 'malo' ? 'selected' : '' }}>Malo</option>
              </select>
              <label>Condición</label>
            </div>

            <div class="fg">
              <input class="ctl" name="brand" placeholder=" " value="{{ old('brand', $item->brand) }}">
              <label>Marca</label>
            </div>

            <div class="fg">
              <input class="ctl" name="model" placeholder=" " value="{{ old('model', $item->model) }}">
              <label>Modelo</label>
            </div>

            <div class="fg">
              <input class="ctl" name="serial_number" placeholder=" " value="{{ old('serial_number', $item->serial_number) }}">
              <label>Número de serie</label>
            </div>
          </div>

          {{-- SOLO CONSUMIBLE --}}
          <div id="consumableFields" class="form-grid two">
            <div class="fg">
              <input class="ctl" type="number" min="0" name="stock_min" value="{{ old('stock_min', $item->stock_min ?? 0) }}" placeholder=" ">
              <label>Stock mínimo *</label>
            </div>

            <div class="fg">
              <input class="ctl" type="number" min="0" name="stock_max" value="{{ old('stock_max', $item->stock_max ?? 0) }}" placeholder=" ">
              <label>Stock máximo *</label>
            </div>
          </div>

          <div class="form-grid" style="margin-top:14px;">
            <div class="fg">
              <textarea class="ctl" name="notes" rows="4" placeholder=" ">{{ old('notes', $item->notes) }}</textarea>
              <label>Notas</label>
            </div>
          </div>

          <div class="actions">
            <button type="submit" class="btn-main">
              <span class="icon">✓</span>
              <span>Guardar cambios</span>
            </button>

            <a href="{{ route('assets.index') }}" class="btn-muted">
              <span class="icon">←</span>
              <span>Cancelar</span>
            </a>
          </div>
        </div>
      </div>

      {{-- RIGHT --}}
      <div>
        <div class="upload-card">
          <div class="section-top" style="margin-bottom:12px;">
            <div>
              <h3 class="section-title">Imagen</h3>
              <div class="section-sub">Sube una fotografía para identificar mejor el registro.</div>
            </div>
          </div>

          <div class="d-flex gap-2 flex-wrap mb-2">
            <button type="button" class="btn-soft" id="choosePhotoBtn">
              <span class="icon">+</span>
              <span>Cambiar imagen</span>
            </button>

            <button type="button" class="btn-soft btn-danger-soft" id="removePhotoBtn" style="display:none;">
              <span class="icon">×</span>
              <span>Quitar nueva</span>
            </button>
          </div>

          <div class="upload" id="uploadZone">
            <div class="sub" style="margin:0; color:var(--muted);">
              Haz clic para subir cualquier imagen.
            </div>

            <div class="preview">
              <img id="previewImg" alt="Vista previa">
              <div class="ph" id="previewPh">
                @if($item->photo)
                  Imagen actual cargada
                  <div class="hint" style="margin:6px 0 0;">Si subes una nueva, reemplazará la actual.</div>
                @else
                  Sin imagen
                  <div class="hint" style="margin:6px 0 0;">La vista previa aparecerá aquí.</div>
                @endif
              </div>
            </div>
          </div>

          <input id="photoInput" class="d-none" type="file" name="photo" accept="image/*">

          @if($item->photo)
            <div class="current-image-wrap">
              <img src="{{ asset('storage/'.$item->photo) }}" alt="Imagen actual">
            </div>

            <div class="checkline">
              <input type="checkbox" name="remove_photo" id="remove_photo" value="1">
              <label for="remove_photo" style="margin:0; color:var(--ink); font-weight:700;">
                Quitar imagen actual
                <span style="display:block; font-weight:500; color:var(--muted); font-size:12px; margin-top:3px;">
                  Si marcas esto, se eliminará la foto actual al guardar.
                </span>
              </label>
            </div>
          @endif
        </div>
      </div>

    </div>
  </form>
</div>

{{-- MODAL NUEVA CATEGORÍA --}}
<div class="modal fade modal-pro" id="categoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:560px;">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title">Nueva categoría</h5>
          <div class="modal-sub">Agrégala y se seleccionará automáticamente.</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div class="fg">
          <input class="ctl" id="categoryName" placeholder=" " autocomplete="off">
          <label>Nombre de la categoría *</label>
        </div>

        <div class="msg-err" id="catErr"></div>
      </div>

      <div class="modal-footer d-flex justify-content-end">
        <button type="button" class="btn-muted" data-bs-dismiss="modal" style="min-height:46px;">
          <span class="icon">←</span>
          <span>Cancelar</span>
        </button>

        <button type="button" class="btn-modal-save" id="catSaveBtn">
          <span class="icon">✓</span>
          <span>Guardar categoría</span>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  const photoInput = document.getElementById('photoInput');
  const previewImg = document.getElementById('previewImg');
  const previewPh = document.getElementById('previewPh');
  const uploadZone = document.getElementById('uploadZone');
  const choosePhotoBtn = document.getElementById('choosePhotoBtn');
  const removePhotoBtn = document.getElementById('removePhotoBtn');

  function setPreview(file){
    const url = URL.createObjectURL(file);
    previewImg.src = url;
    previewImg.style.display = 'block';
    previewPh.style.display = 'none';
    removePhotoBtn.style.display = 'inline-flex';
  }

  function resetPreview(){
    previewImg.src = '';
    previewImg.style.display = 'none';
    previewPh.style.display = 'block';
    removePhotoBtn.style.display = 'none';
    photoInput.value = '';
  }

  choosePhotoBtn.addEventListener('click', () => photoInput.click());

  uploadZone.addEventListener('click', () => {
    photoInput.click();
  });

  photoInput.addEventListener('change', () => {
    const file = photoInput.files && photoInput.files[0];
    if(!file) return resetPreview();
    setPreview(file);
  });

  removePhotoBtn.addEventListener('click', () => resetPreview());

  const typeSelect = document.getElementById('type');
  const fixedFields = document.getElementById('fixedFields');
  const consumableFields = document.getElementById('consumableFields');
  const typeBadge = document.getElementById('typeBadge');

  function syncTypeUI() {
    const type = typeSelect.value;

    if (type === 'consumible') {
      fixedFields.classList.add('hidden-block');
      consumableFields.classList.remove('hidden-block');
      typeBadge.textContent = 'Mostrando campos para consumible';
    } else {
      fixedFields.classList.remove('hidden-block');
      consumableFields.classList.add('hidden-block');
      typeBadge.textContent = 'Mostrando campos para activo fijo';
    }

    syncSelectFloating();
  }

  typeSelect.addEventListener('change', syncTypeUI);

  function syncSelectFloating() {
    document.querySelectorAll('select.ctl').forEach(sel => {
      if (sel.value && sel.value !== '') {
        sel.classList.add('has-value');
      } else {
        sel.classList.remove('has-value');
      }
    });
  }

  document.querySelectorAll('select.ctl').forEach(sel => {
    sel.addEventListener('change', syncSelectFloating);
  });

  syncTypeUI();
  syncSelectFloating();

  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const catSaveBtn = document.getElementById('catSaveBtn');
  const categoryName = document.getElementById('categoryName');
  const catErr = document.getElementById('catErr');
  const selectCat = document.getElementById('inventory_category_id');
  const categoryModalEl = document.getElementById('categoryModal');
  const toastWrap = document.getElementById('toastWrap');

  function norm(s){
    return (s || '').trim().replace(/\s+/g,' ');
  }

  function showCatErr(msg){
    catErr.textContent = msg;
    catErr.style.display = 'block';
  }

  function clearCatErr(){
    catErr.textContent = '';
    catErr.style.display = 'none';
  }

  function resetCategoryModal(){
    clearCatErr();
    categoryName.value = '';
    catSaveBtn.disabled = false;
    const label = catSaveBtn.querySelector('span:last-child');
    if(label) label.textContent = 'Guardar categoría';
  }

  function cleanupModalArtifacts(){
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('padding-right');
    document.body.style.overflow = '';
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
  }

  function showToast(message){
    const el = document.createElement('div');
    el.className = 'toast toast-soft border-0';
    el.setAttribute('role', 'alert');
    el.setAttribute('aria-live', 'assertive');
    el.setAttribute('aria-atomic', 'true');

    el.innerHTML = `
      <div class="toast-body">
        <div class="ticon">✓</div>
        <div>${message}</div>
      </div>
    `;

    toastWrap.appendChild(el);

    const bsToast = new bootstrap.Toast(el, { delay: 2600 });

    el.addEventListener('hidden.bs.toast', () => {
      el.remove();
    });

    bsToast.show();
  }

  categoryModalEl.addEventListener('shown.bs.modal', () => {
    resetCategoryModal();
    setTimeout(() => categoryName.focus(), 80);
  });

  categoryModalEl.addEventListener('hidden.bs.modal', () => {
    resetCategoryModal();
    cleanupModalArtifacts();
  });

  async function saveCategory(){
    clearCatErr();

    const name = norm(categoryName.value);

    if(!name){
      showCatErr('El nombre de la categoría es obligatorio.');
      categoryName.focus();
      return;
    }

    catSaveBtn.disabled = true;
    const label = catSaveBtn.querySelector('span:last-child');
    if(label) label.textContent = 'Guardando...';

    try{
      const response = await fetch("{{ route('assets.categories.store') }}", {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ name: name })
      });

      let data = {};
      try {
        data = await response.json();
      } catch (_) {
        data = {};
      }

      if (!response.ok) {
        throw new Error(
          data.message ||
          (data.errors && data.errors.name && data.errors.name[0]) ||
          'No se pudo guardar la categoría.'
        );
      }

      let alreadyExists = false;
      Array.from(selectCat.options).forEach(opt => {
        if(String(opt.value) === String(data.id)){
          alreadyExists = true;
          opt.selected = true;
        }
      });

      if(!alreadyExists){
        const option = new Option(data.name, data.id, true, true);
        selectCat.add(option);
      }

      selectCat.value = data.id;
      selectCat.dispatchEvent(new Event('change'));

      const modalInstance = bootstrap.Modal.getOrCreateInstance(categoryModalEl);
      modalInstance.hide();

      setTimeout(() => {
        cleanupModalArtifacts();
      }, 250);

      showToast(`Categoría "${data.name}" creada correctamente.`);
    } catch (error) {
      showCatErr(error.message || 'Error al guardar la categoría.');
      console.error(error);
    } finally {
      catSaveBtn.disabled = false;
      const finalLabel = catSaveBtn.querySelector('span:last-child');
      if(finalLabel) finalLabel.textContent = 'Guardar categoría';
    }
  }

  catSaveBtn.addEventListener('click', saveCategory);

  categoryName.addEventListener('keydown', function(e){
    if(e.key === 'Enter'){
      e.preventDefault();
      saveCategory();
    }
  });
</script>
@endsection
