{{-- resources/views/accounting/vehicles/edit.blade.php --}}
@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700" rel="stylesheet">

@php
  use Illuminate\Support\Facades\Storage;

  $isEdit = isset($vehicle) && $vehicle instanceof \App\Models\Vehicle && $vehicle->exists;

  $v = function($key, $default = null) use ($vehicle) {
    return old($key, isset($vehicle) ? ($vehicle->{$key} ?? null) : null) ?? $default;
  };

  $dateVal = function($key) use ($vehicle) {
    $val = old($key, isset($vehicle) ? ($vehicle->{$key} ?? null) : null);
    if(!$val) return '';
    try{
      if($val instanceof \Carbon\Carbon) return $val->format('Y-m-d');
      return substr((string)$val, 0, 10);
    }catch(\Throwable $e){
      return '';
    }
  };

  // ✅ urls de imágenes reales (DB guarda paths en public disk)
  $imgLeftUrl  = !empty($vehicle?->image_left)  ? Storage::url($vehicle->image_left)  : null;
  $imgRightUrl = !empty($vehicle?->image_right) ? Storage::url($vehicle->image_right) : null;

  // ✅ docs desde relación documents (VehicleController@edit ya hace $vehicle->load('documents'))
  $docsByType = collect($vehicle->documents ?? [])->keyBy('type');
  $docsMap = [
    'tarjeta_circulacion' => 'Tarjeta de Circulación',
    'seguro'              => 'Póliza de Seguro',
    'tenencia'            => 'Tenencia',
    'verificacion'        => 'Verificación',
    'factura'             => 'Factura',
  ];

  $docUrl = function($type) use ($docsByType){
    $d = $docsByType->get($type);
    if(!$d) return null;
    return Storage::url($d->path);
  };

  $docName = function($type) use ($docsByType){
    $d = $docsByType->get($type);
    return $d?->original_name ?? null;
  };
@endphp

<style>
:root{ --mint:#48cfad; --mint-dark:#34c29e; --ink:#2a2e35; --muted:#7a7f87; --line:#e9ecef; --card:#ffffff; }
*{box-sizing:border-box}
body{font-family:"Open Sans",sans-serif;background:#eaebec}

/* Panel */
.edit-wrap{ max-width:1100px; margin:10px auto 40px; padding:0 16px; }
.panel{ background:var(--card); border-radius:16px; box-shadow:0 16px 40px rgba(18,38,63,.12); overflow:hidden; }
.panel-head{ padding:18px 22px; border-bottom:1px solid var(--line); display:flex; align-items:center; gap:12px; justify-content:space-between; }
.hgroup h2{ margin:0; font-weight:700; color:var(--ink); letter-spacing:-.02em }
.hgroup p{ margin:2px 0 0; color:var(--muted); font-size:14px }
.back-link{ display:inline-flex; align-items:center; gap:8px; color:var(--muted); text-decoration:none; padding:8px 12px; border-radius:10px; border:1px solid var(--line); background:#fff; }
.back-link:hover{ color:#111; border-color:#e3e6eb; box-shadow:0 8px 18px rgba(0,0,0,.08) }

/* Form + campos compactos */
.form{ padding:22px; }
.section-gap{ margin-top:8px; }
.field{
  position:relative; background:#fff; border:1px solid var(--line);
  border-radius:12px; padding:12px 12px 6px;
  transition:box-shadow .2s, border-color .2s;
}
.field:focus-within{ border-color:#d8dee6; box-shadow:0 6px 18px rgba(18,38,63,.08) }
.field input,.field textarea{
  width:100%; border:0; outline:0; background:transparent;
  font-size:14px; color:var(--ink); padding-top:8px; resize:vertical;
}
.field textarea{ min-height:90px; }
.field label{
  position:absolute; left:12px; top:10px; color:var(--muted); font-size:12px;
  transition:transform .15s ease, color .15s ease, font-size .15s ease, top .15s ease;
  pointer-events:none;
}
.field input::placeholder,.field textarea::placeholder{ color:transparent; }
.field input:focus + label,
.field input:not(:placeholder-shown) + label,
.field textarea:focus + label,
.field textarea:not(:placeholder-shown) + label{
  top:4px; transform:translateY(-8px); font-size:10.5px; color:var(--mint-dark);
}

/* Grid fluido sin bootstrap */
.row{ display:flex; flex-wrap:wrap; margin-left:-10px; margin-right:-10px; }
.col{ padding:0 10px; }
.col-12{ width:100% }
@media (min-width: 768px){
  .col-md-6{ width:50% } .col-md-4{ width:33.3333% } .col-md-8{ width:66.6666% } .col-md-3{ width:25% }
}
.gy-3 > .col{ margin-top:12px }

/* Bloques */
.block{ border:1px dashed #dfe3e8; border-radius:14px; padding:14px; background:#fafbfc; }
.block-title{ display:flex; align-items:center; justify-content:space-between; gap:10px; margin:0 0 10px; }
.block-title .t{ font-weight:700; color:var(--ink); font-size:14px; }
.block-title .s{ color:var(--muted); font-size:12px; }

/* Dropzone */
.dropzone{ display:grid; grid-template-columns:150px 1fr; gap:14px; align-items:center; }
@media (max-width: 620px){ .dropzone{ grid-template-columns:1fr } }
.preview{
  width:150px; height:150px; border-radius:12px; overflow:hidden; background:#f6f7f9;
  display:grid; place-items:center; border:1px solid #edf0f3;
}
.preview img{ width:100%; height:100%; object-fit:cover; display:none }
.preview .placeholder{
  display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; color:#6b7280; font-size:12px;
  padding:10px; text-align:center;
}
.placeholder svg{ width:28px; height:28px; opacity:.8 }
.drop-actions{ display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.input-file{ display:none }
.btn-upload{
  background:var(--mint); color:#fff; border:none; border-radius:999px; padding:8px 14px;
  cursor:pointer; box-shadow:0 8px 18px rgba(0,0,0,.12);
}
.btn-upload:hover{ background:var(--mint-dark) }
.drop-box{ border:1px dashed #cfd6e0; border-radius:12px; padding:10px 12px; background:#fff; color:#60708a; font-size:12px; }
.dropzone.dragover .drop-box{ border-color:#93a3c5; background:#f2f6ff }
.file-meta{ font-size:12px; color:#6b7280 }

/* Chips de docs existentes */
.doc-list{ display:flex; flex-wrap:wrap; gap:10px; margin-top:10px; }
.doc-chip{
  display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:999px;
  background:#fff; border:1px solid #e5e7eb; color:#111; text-decoration:none;
  box-shadow:0 10px 22px rgba(0,0,0,.06);
}
.doc-chip:hover{ box-shadow:0 14px 30px rgba(0,0,0,.10); border-color:transparent; }
.doc-chip small{ color:var(--muted); font-weight:600; }

/* Acciones */
.actions{ display:flex; gap:10px; justify-content:flex-end; margin-top:8px; align-items:center; flex-wrap:wrap; }
.btn{
  border:1px solid transparent; border-radius:12px; padding:10px 16px; font-weight:700; cursor:pointer;
  transition:transform .05s ease, box-shadow .2s ease, background .2s ease, color .2s ease, border-color .2s ease;
  text-decoration:none; display:inline-flex; align-items:center; gap:8px;
}
.btn:active{ transform:translateY(1px) }
.btn-primary{ background:var(--mint); color:#fff; }
.btn-primary:hover{ background:#fff; color:#111; border-color:transparent; box-shadow:0 14px 34px rgba(0,0,0,.18); }
.btn-ghost{ background:#fff; color:#111; border:1px solid #e5e7eb; }
.btn-ghost:hover{ background:#fff; color:#111; border-color:transparent; box-shadow:0 12px 26px rgba(0,0,0,.12); }
.btn-danger-soft{ background:#fff; color:#b42318; border:1px solid #fecaca; }
.btn-danger-soft:hover{ border-color:transparent; box-shadow:0 12px 26px rgba(0,0,0,.12); }

.is-invalid{ border-color:#f9c0c0 !important }
.error{ color:#cc4b4b; font-size:12px; margin-top:6px }
@media (max-width: 768px){
  .hgroup .subtitle{ display:none; }
}
</style>

<div class="edit-wrap">
  <div class="panel">
    <div class="panel-head">
      <div class="hgroup">
        <h2>Editar camioneta</h2>
        <p class="subtitle">Actualiza datos, fechas y documentos. Al guardar, se actualiza la agenda.</p>
      </div>
      <a href="{{ route('vehicles.index') }}" class="back-link" title="Volver">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Volver
      </a>
    </div>

    <form class="form"
          action="{{ route('vehicles.update', $vehicle->getKey()) }}"
          method="POST" enctype="multipart/form-data">
      @csrf @method('PUT')

      {{-- ===== Datos básicos ===== --}}
      <div class="row gy-3 section-gap">
        <div class="col col-12 col-md-4">
          <div class="field @error('plate') is-invalid @enderror">
            <input type="text" name="plate" id="f-plate" value="{{ $v('plate') }}" placeholder=" " required>
            <label for="f-plate">Placa *</label>
          </div>
          @error('plate')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="col col-12 col-md-4">
          <div class="field @error('brand') is-invalid @enderror">
            <input type="text" name="brand" id="f-brand" value="{{ $v('brand') }}" placeholder=" ">
            <label for="f-brand">Marca</label>
          </div>
          @error('brand')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="col col-12 col-md-4">
          <div class="field @error('model') is-invalid @enderror">
            <input type="text" name="model" id="f-model" value="{{ $v('model') }}" placeholder=" " required>
            <label for="f-model">Modelo *</label>
          </div>
          @error('model')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="row gy-3 section-gap">
        <div class="col col-12 col-md-4">
          <div class="field @error('year') is-invalid @enderror">
            <input type="number" name="year" id="f-year" value="{{ $v('year', date('Y')) }}" placeholder=" " min="1950" max="2100" required>
            <label for="f-year">Año *</label>
          </div>
          @error('year')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="col col-12 col-md-4">
          <div class="field @error('nickname') is-invalid @enderror">
            <input type="text" name="nickname" id="f-nickname" value="{{ $v('nickname') }}" placeholder=" ">
            <label for="f-nickname">Alias</label>
          </div>
          @error('nickname')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="col col-12 col-md-4">
          <div class="field @error('vin') is-invalid @enderror">
            <input type="text" name="vin" id="f-vin" value="{{ $v('vin') }}" placeholder=" ">
            <label for="f-vin">VIN</label>
          </div>
          @error('vin')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- ===== Fechas (Agenda) ===== --}}
      <div class="block section-gap">
        <div class="block-title">
          <div class="t">Fechas</div>
          <div class="s">Se registran/actualizan en la agenda</div>
        </div>

        {{-- ✅ ÚLTIMAS (para que show no muestre —) --}}
        <div class="row gy-3">
          <div class="col col-12 col-md-3">
            <div class="field @error('last_verification_at') is-invalid @enderror">
              <input type="date" name="last_verification_at" id="f-last-verif" value="{{ $dateVal('last_verification_at') }}" placeholder=" ">
              <label for="f-last-verif">Últ. verificación</label>
            </div>
            @error('last_verification_at')<div class="error">{{ $message }}</div>@enderror
          </div>

          <div class="col col-12 col-md-3">
            <div class="field @error('last_service_at') is-invalid @enderror">
              <input type="date" name="last_service_at" id="f-last-serv" value="{{ $dateVal('last_service_at') }}" placeholder=" ">
              <label for="f-last-serv">Últ. servicio</label>
            </div>
            @error('last_service_at')<div class="error">{{ $message }}</div>@enderror
          </div>

          <div class="col col-12 col-md-3">
            <div class="field @error('next_verification_due_at') is-invalid @enderror">
              <input type="date" name="next_verification_due_at" id="f-verif" value="{{ $dateVal('next_verification_due_at') }}" placeholder=" ">
              <label for="f-verif">Próx. verificación</label>
            </div>
            @error('next_verification_due_at')<div class="error">{{ $message }}</div>@enderror
          </div>

          <div class="col col-12 col-md-3">
            <div class="field @error('next_service_due_at') is-invalid @enderror">
              <input type="date" name="next_service_due_at" id="f-serv" value="{{ $dateVal('next_service_due_at') }}" placeholder=" ">
              <label for="f-serv">Próx. servicio</label>
            </div>
            @error('next_service_due_at')<div class="error">{{ $message }}</div>@enderror
          </div>
        </div>

        {{-- ✅ VENCIMIENTOS --}}
        <div class="row gy-3 section-gap" style="margin-top:4px;">
          <div class="col col-12 col-md-3">
            <div class="field @error('tenencia_due_at') is-invalid @enderror">
              <input type="date" name="tenencia_due_at" id="f-ten" value="{{ $dateVal('tenencia_due_at') }}" placeholder=" ">
              <label for="f-ten">Tenencia</label>
            </div>
            @error('tenencia_due_at')<div class="error">{{ $message }}</div>@enderror
          </div>

          <div class="col col-12 col-md-3">
            <div class="field @error('circulation_card_due_at') is-invalid @enderror">
              <input type="date" name="circulation_card_due_at" id="f-tar" value="{{ $dateVal('circulation_card_due_at') }}" placeholder=" ">
              <label for="f-tar">Tarjeta circulación</label>
            </div>
            @error('circulation_card_due_at')<div class="error">{{ $message }}</div>@enderror
          </div>

          {{-- ✅ Seguro (tu controller ya lo valida y lo mete a agenda_insurance_id) --}}
          <div class="col col-12 col-md-3">
            <div class="field @error('insurance_due_at') is-invalid @enderror">
              <input type="date" name="insurance_due_at" id="f-ins" value="{{ $dateVal('insurance_due_at') }}" placeholder=" ">
              <label for="f-ins">Seguro</label>
            </div>
            @error('insurance_due_at')<div class="error">{{ $message }}</div>@enderror
          </div>

          <div class="col col-12 col-md-3"></div>
        </div>
      </div>

      {{-- ===== Notas ===== --}}
      <div class="row gy-3 section-gap">
        <div class="col col-12">
          <div class="field @error('notes') is-invalid @enderror">
            <textarea name="notes" id="f-notes" placeholder=" ">{{ $v('notes') }}</textarea>
            <label for="f-notes">Notas</label>
          </div>
          @error('notes')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- ===== Imágenes (2) ===== --}}
      <div class="block section-gap">
        <div class="block-title">
          <div class="t">Imágenes</div>
          <div class="s">Al subir una nueva, reemplaza la anterior</div>
        </div>

        <div class="row gy-3">
          <div class="col col-12 col-md-6">
            <div class="dropzone" data-dz="img_left">
              <div class="preview" id="pv-img_left">
                <div class="placeholder" id="ph-img_left">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path d="M3 6h18v12H3z"/><path d="M3 14l4-4 4 4 4-4 4 4"/>
                  </svg>
                  <div>Imagen izquierda</div>
                </div>
                <img id="im-img_left" alt="preview">
              </div>
              <div class="drop-actions">
                <label class="btn-upload" for="in-img_left">Seleccionar</label>
                <input id="in-img_left" class="input-file" type="file" name="image_left" accept="*/*">
                <div class="drop-box">o arrastra y suelta aquí</div>
                <div class="file-meta" id="mt-img_left"></div>
              </div>
            </div>

            @if($imgLeftUrl)
              <div style="margin-top:10px;">
                <a class="doc-chip" href="{{ $imgLeftUrl }}" target="_blank">
                  <small>Actual</small> Imagen izquierda
                </a>
              </div>
            @endif
            @error('image_left')<div class="error" style="margin-top:8px;">{{ $message }}</div>@enderror
          </div>

          <div class="col col-12 col-md-6">
            <div class="dropzone" data-dz="img_right">
              <div class="preview" id="pv-img_right">
                <div class="placeholder" id="ph-img_right">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path d="M3 6h18v12H3z"/><path d="M3 14l4-4 4 4 4-4 4 4"/>
                  </svg>
                  <div>Imagen derecha</div>
                </div>
                <img id="im-img_right" alt="preview">
              </div>
              <div class="drop-actions">
                <label class="btn-upload" for="in-img_right">Seleccionar</label>
                <input id="in-img_right" class="input-file" type="file" name="image_right" accept="*/*">
                <div class="drop-box">o arrastra y suelta aquí</div>
                <div class="file-meta" id="mt-img_right"></div>
              </div>
            </div>

            @if($imgRightUrl)
              <div style="margin-top:10px;">
                <a class="doc-chip" href="{{ $imgRightUrl }}" target="_blank">
                  <small>Actual</small> Imagen derecha
                </a>
              </div>
            @endif
            @error('image_right')<div class="error" style="margin-top:8px;">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>

      {{-- ===== Documentación (5) ===== --}}
      <div class="block section-gap">
        <div class="block-title">
          <div class="t">Documentación</div>
          <div class="s">Puedes subir versiones nuevas cuando quieras</div>
        </div>

        <div class="row gy-3">
          <div class="col col-12 col-md-6">
            <div class="dropzone" data-dz="doc_tarjeta">
              <div class="preview" id="pv-doc_tarjeta">
                <div class="placeholder" id="ph-doc_tarjeta">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>
                  </svg>
                  <div>Tarjeta de circulación</div>
                </div>
                <img id="im-doc_tarjeta" alt="preview">
              </div>
              <div class="drop-actions">
                <label class="btn-upload" for="in-doc_tarjeta">Seleccionar</label>
                <input id="in-doc_tarjeta" class="input-file" type="file" name="doc_tarjeta" accept="*/*">
                <div class="drop-box">o arrastra y suelta aquí</div>
                <div class="file-meta" id="mt-doc_tarjeta"></div>
              </div>
            </div>

            @if($docUrl('tarjeta_circulacion'))
              <div style="margin-top:10px;">
                <a class="doc-chip" href="{{ $docUrl('tarjeta_circulacion') }}" target="_blank">
                  <small>Actual</small> {{ $docName('tarjeta_circulacion') ?? 'Tarjeta de circulación' }}
                </a>
              </div>
            @endif
          </div>

          <div class="col col-12 col-md-6">
            <div class="dropzone" data-dz="doc_seguro">
              <div class="preview" id="pv-doc_seguro">
                <div class="placeholder" id="ph-doc_seguro">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                  </svg>
                  <div>Seguro</div>
                </div>
                <img id="im-doc_seguro" alt="preview">
              </div>
              <div class="drop-actions">
                <label class="btn-upload" for="in-doc_seguro">Seleccionar</label>
                <input id="in-doc_seguro" class="input-file" type="file" name="doc_seguro" accept="*/*">
                <div class="drop-box">o arrastra y suelta aquí</div>
                <div class="file-meta" id="mt-doc_seguro"></div>
              </div>
            </div>

            @if($docUrl('seguro'))
              <div style="margin-top:10px;">
                <a class="doc-chip" href="{{ $docUrl('seguro') }}" target="_blank">
                  <small>Actual</small> {{ $docName('seguro') ?? 'Seguro' }}
                </a>
              </div>
            @endif
          </div>

          <div class="col col-12 col-md-6">
            <div class="dropzone" data-dz="doc_tenencia">
              <div class="preview" id="pv-doc_tenencia">
                <div class="placeholder" id="ph-doc_tenencia">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path d="M3 6h18"/><path d="M7 6V4h10v2"/><path d="M6 10h12"/><path d="M6 14h12"/><path d="M6 18h8"/>
                  </svg>
                  <div>Tenencia</div>
                </div>
                <img id="im-doc_tenencia" alt="preview">
              </div>
              <div class="drop-actions">
                <label class="btn-upload" for="in-doc_tenencia">Seleccionar</label>
                <input id="in-doc_tenencia" class="input-file" type="file" name="doc_tenencia" accept="*/*">
                <div class="drop-box">o arrastra y suelta aquí</div>
                <div class="file-meta" id="mt-doc_tenencia"></div>
              </div>
            </div>

            @if($docUrl('tenencia'))
              <div style="margin-top:10px;">
                <a class="doc-chip" href="{{ $docUrl('tenencia') }}" target="_blank">
                  <small>Actual</small> {{ $docName('tenencia') ?? 'Tenencia' }}
                </a>
              </div>
            @endif
          </div>

          <div class="col col-12 col-md-6">
            <div class="dropzone" data-dz="doc_verificacion">
              <div class="preview" id="pv-doc_verificacion">
                <div class="placeholder" id="ph-doc_verificacion">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path d="M9 12l2 2 4-4"/><path d="M7 3h10v4H7z"/><path d="M7 7h10v14H7z"/>
                  </svg>
                  <div>Verificación</div>
                </div>
                <img id="im-doc_verificacion" alt="preview">
              </div>
              <div class="drop-actions">
                <label class="btn-upload" for="in-doc_verificacion">Seleccionar</label>
                <input id="in-doc_verificacion" class="input-file" type="file" name="doc_verificacion" accept="*/*">
                <div class="drop-box">o arrastra y suelta aquí</div>
                <div class="file-meta" id="mt-doc_verificacion"></div>
              </div>
            </div>

            @if($docUrl('verificacion'))
              <div style="margin-top:10px;">
                <a class="doc-chip" href="{{ $docUrl('verificacion') }}" target="_blank">
                  <small>Actual</small> {{ $docName('verificacion') ?? 'Verificación' }}
                </a>
              </div>
            @endif
          </div>

          <div class="col col-12 col-md-6">
            <div class="dropzone" data-dz="doc_factura">
              <div class="preview" id="pv-doc_factura">
                <div class="placeholder" id="ph-doc_factura">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h6"/>
                  </svg>
                  <div>Factura</div>
                </div>
                <img id="im-doc_factura" alt="preview">
              </div>
              <div class="drop-actions">
                <label class="btn-upload" for="in-doc_factura">Seleccionar</label>
                <input id="in-doc_factura" class="input-file" type="file" name="doc_factura" accept="*/*">
                <div class="drop-box">o arrastra y suelta aquí</div>
                <div class="file-meta" id="mt-doc_factura"></div>
              </div>
            </div>

            @if($docUrl('factura'))
              <div style="margin-top:10px;">
                <a class="doc-chip" href="{{ $docUrl('factura') }}" target="_blank">
                  <small>Actual</small> {{ $docName('factura') ?? 'Factura' }}
                </a>
              </div>
            @endif
          </div>
        </div>

        <div class="doc-list">
          @php
            $anyDoc = false;
            foreach(array_keys($docsMap) as $t){ if($docUrl($t)){ $anyDoc = true; break; } }
          @endphp

          @if($anyDoc)
            @foreach($docsMap as $type => $label)
              @if($docUrl($type))
                <a class="doc-chip" href="{{ $docUrl($type) }}" target="_blank">
                  <small>{{ $label }}</small> {{ $docName($type) ?? 'Archivo' }}
                </a>
              @endif
            @endforeach
          @else
            <span style="color:var(--muted); font-size:12px;">Sin documentos guardados</span>
          @endif
        </div>

        @if($errors->any())
          <div class="error" style="margin-top:10px;">Revisa los campos marcados.</div>
        @endif
      </div>

      <div class="actions">
        <a href="{{ route('vehicles.index') }}" class="btn btn-ghost">Cancelar</a>

        <form action="{{ route('vehicles.destroy', $vehicle->getKey()) }}" method="POST" onsubmit="return confirm('¿Eliminar esta camioneta?');" style="display:inline;">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-danger-soft">Eliminar</button>
        </form>

        <button type="submit" class="btn btn-primary">Actualizar</button>
      </div>
    </form>
  </div>
</div>

<script>
function humanSize(bytes){
  if(!bytes) return '';
  const i = Math.floor(Math.log(bytes)/Math.log(1024));
  return (bytes/Math.pow(1024, i)).toFixed(1) + ' ' + ['B','KB','MB','GB','TB'][i];
}

function fileIconHtml(type){
  return `
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
      <path d="M14 2v6h6"/>
    </svg>
    <div>${type || 'Archivo'}</div>
  `;
}

function bindDropzone(key, inputId){
  const dz = document.querySelector(`.dropzone[data-dz="${key}"]`);
  const input = document.getElementById(inputId);
  const img = document.getElementById(`im-${key}`);
  const ph = document.getElementById(`ph-${key}`);
  const meta = document.getElementById(`mt-${key}`);

  if(!dz || !input || !img || !ph || !meta) return;

  function renderFile(file){
    meta.textContent = `${file.name} • ${humanSize(file.size)}`;
    if (/^image\//.test(file.type)) {
      const rd = new FileReader();
      rd.onload = ev => {
        img.src = ev.target.result;
        img.style.display = 'block';
        ph.style.display = 'none';
      };
      rd.readAsDataURL(file);
    } else {
      img.style.display = 'none';
      ph.innerHTML = fileIconHtml(file.type);
      ph.style.display = 'flex';
    }
  }

  input.addEventListener('change', e=>{
    const f = e.target.files?.[0];
    if(!f) return;
    renderFile(f);
  });

  ['dragenter','dragover'].forEach(evt=>{
    dz.addEventListener(evt, e=>{
      e.preventDefault(); e.stopPropagation();
      dz.classList.add('dragover');
    });
  });
  ['dragleave','drop'].forEach(evt=>{
    dz.addEventListener(evt, e=>{
      e.preventDefault(); e.stopPropagation();
      dz.classList.remove('dragover');
    });
  });

  dz.addEventListener('drop', e=>{
    const f = e.dataTransfer?.files?.[0];
    if(!f) return;
    const dt = new DataTransfer();
    dt.items.add(f);
    input.files = dt.files;
    renderFile(f);
  });
}

bindDropzone('img_left', 'in-img_left');
bindDropzone('img_right', 'in-img_right');

bindDropzone('doc_tarjeta', 'in-doc_tarjeta');
bindDropzone('doc_seguro', 'in-doc_seguro');
bindDropzone('doc_tenencia', 'in-doc_tenencia');
bindDropzone('doc_verificacion', 'in-doc_verificacion');
bindDropzone('doc_factura', 'in-doc_factura');
</script>
@endsection
