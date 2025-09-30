@extends('layouts.app')

@section('title', $provider->exists ? 'Editar proveedor' : 'Nuevo proveedor')
@section('header', $provider->exists ? 'Editar proveedor' : 'Nuevo proveedor')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700" rel="stylesheet">

@php
  $isEdit = $provider->exists;
  $v = function($key,$default=null) use ($provider){ return old($key, $provider->{$key} ?? $default); };
@endphp

<style>
:root{ --mint:#48cfad; --mint-dark:#34c29e; --ink:#2a2e35; --muted:#7a7f87; --line:#e9ecef; --card:#ffffff; }
*{box-sizing:border-box}
.page{ max-width:1100px; margin:10px auto 28px; padding:0 12px; }

.panel{ background:var(--card); border-radius:16px; box-shadow:0 16px 40px rgba(18,38,63,.12); overflow:hidden; }
.panel-head{ padding:20px 24px; border-bottom:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:12px; }
.hgroup h2{ margin:0; font-weight:800; color:var(--ink); letter-spacing:-.02em }
.hgroup p{ margin:6px 0 0; color:var(--muted); font-size:14px }
@media (max-width:640px){ .hgroup p{ display:none } }

.back-link{
  display:inline-flex; align-items:center; gap:8px; color:var(--muted);
  text-decoration:none; padding:8px 12px; border-radius:10px; border:1px solid var(--line); background:#fff;
}
.back-link:hover{ color:var(--ink); border-color:#dfe3e8 }

.form{ padding:26px; }

/* === Inputs con label flotante === */
.field{
  position:relative; background:#fff; border:1px solid var(--line); border-radius:12px;
  padding:16px 16px 10px; transition:box-shadow .2s, border-color .2s;
}
.field:focus-within{ border-color:#d8dee6; box-shadow:0 8px 24px rgba(18,38,63,.08) }
.field input{ width:100%; border:0; outline:0; background:transparent; font-size:15px; color:var(--ink); padding-top:8px; }
.field label{
  position:absolute; left:16px; top:13px; color:var(--muted); font-size:13px;
  transition:transform .15s, color .15s, font-size .15s, top .15s; pointer-events:none;
}
.field input::placeholder{ color:transparent; }
.field input:focus + label,
.field input:not(:placeholder-shown) + label{ top:6px; transform:translateY(-9px); font-size:11px; color:var(--mint-dark); }

/* ===== Switch con estado Inactivo/Activo ===== */
.switch-wrap{
  display:flex; align-items:center; justify-content:space-between;
  background:#fff; border:1px solid var(--line); border-radius:12px; padding:12px 14px;
}
.sw{
  display:inline-flex; align-items:center; gap:10px; user-select:none;
}
.sw input{ display:none }
.sw .track{
  width:46px; height:26px; border-radius:999px; background:#e9edf2; position:relative; transition:background .2s, box-shadow .2s;
  box-shadow: inset 0 0 0 1px rgba(0,0,0,.06);
}
.sw .thumb{ width:22px; height:22px; border-radius:50%; background:#fff; position:absolute; top:2px; left:2px; box-shadow:0 2px 8px rgba(0,0,0,.15); transition:left .18s ease; }
.sw input:checked + .track{ background:var(--mint) }
.sw input:checked + .track .thumb{ left:22px }

/* Etiquetas de estado que cambian con :checked */
.sw-state{
  font-weight:700; font-size:0.9rem; letter-spacing:.2px; min-width:78px; text-align:right;
}
.sw .state-inactivo{ color:#9aa1aa; display:inline }
.sw .state-activo{ color:#24a67f; display:none }
.sw input:checked ~ .state-inactivo{ display:none }
.sw input:checked ~ .state-activo{ display:inline }

/* Acciones */
.actions{ display:flex; gap:12px; justify-content:flex-end; margin-top:14px; padding:0 26px 26px; }
.btn{ border:0; border-radius:12px; padding:12px 18px; font-weight:800; cursor:pointer; transition:transform .05s, box-shadow .2s, background .2s,color .2s; }
.btn:active{ transform:translateY(1px) }
.btn-primary{ background:var(--mint); color:#fff; box-shadow:0 12px 22px rgba(72,207,173,.26) }
.btn-primary:hover{ background:#fff; color:#111; box-shadow:0 16px 32px rgba(0,0,0,.18) }

/* Cancelar sin línea (como pediste) */
.btn-ghost{ background:#fff; color:#111; border:0 !important; }
.btn-ghost:hover{ background:#fff; color:#111; border:0 !important; box-shadow:0 12px 26px rgba(0,0,0,.14); }

.is-invalid{ border-color:#f9c0c0 !important }
.error{ color:#cc4b4b; font-size:12px; margin-top:6px }

/* ===== Extra de espacios (más separación) ===== */
.row+.row{ margin-top:1px }              /* separación mínima entre filas adyacentes */
@media (min-width:768px){ .row.g-4{ --bs-gutter-x:1.25rem; --bs-gutter-y:1.25rem; } } /* un poco más de gap en md+ */
</style>

<div class="page">
  <div class="panel">
    <div class="panel-head">
      <div class="hgroup">
        <h2>{{ $isEdit ? 'Editar proveedor' : 'Agregar proveedor' }}</h2>
        <p>{{ $isEdit ? 'Actualiza los datos del proveedor.' : 'Crea un nuevo proveedor.' }}</p>
      </div>
      <a href="{{ route('providers.index') }}" class="back-link" title="Volver">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Volver
      </a>
    </div>

    <form class="form"
      action="{{ $isEdit ? route('providers.update',$provider) : route('providers.store') }}"
      method="POST">
      @csrf
      @if($isEdit) @method('PUT') @endif

      {{-- ============ Fila 1: Nombre / Email (más espacio) ============ --}}
      <div class="row g-4 mb-2">
        <div class="col-md-6">
          <div class="field @error('nombre') is-invalid @enderror">
            <input type="text" name="nombre" id="f-nombre" value="{{ $v('nombre') }}" placeholder=" " required>
            <label for="f-nombre">Nombre (requerido)</label>
          </div>
          @error('nombre')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
          <div class="field @error('email') is-invalid @enderror">
            <input type="email" name="email" id="f-email" value="{{ $v('email') }}" placeholder=" " required>
            <label for="f-email">Correo (requerido)</label>
          </div>
          @error('email')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- ============ Fila 2 (cortos, juntos de 3): RFC / Tipo / Teléfono ============ --}}
      <div class="row g-4 mb-2">
        <div class="col-lg-4 col-md-6">
          <div class="field @error('rfc') is-invalid @enderror">
            <input type="text" name="rfc" id="f-rfc" value="{{ $v('rfc') }}" placeholder=" ">
            <label for="f-rfc">RFC / Número fiscal</label>
          </div>
          @error('rfc')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="field @error('tipo_persona') is-invalid @enderror">
            <input type="text" name="tipo_persona" id="f-tipo" value="{{ $v('tipo_persona') }}" placeholder=" ">
            <label for="f-tipo">Tipo de persona (física/moral)</label>
          </div>
          @error('tipo_persona')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="field @error('telefono') is-invalid @enderror">
            <input type="text" name="telefono" id="f-telefono" value="{{ $v('telefono') }}" placeholder=" ">
            <label for="f-telefono">Teléfono</label>
          </div>
          @error('telefono')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- ============ Fila 3 (medianos): Calle / Colonia ============ --}}
      <div class="row g-4 mb-2">
        <div class="col-md-6">
          <div class="field @error('calle') is-invalid @enderror">
            <input type="text" name="calle" id="f-calle" value="{{ $v('calle') }}" placeholder=" ">
            <label for="f-calle">Calle</label>
          </div>
          @error('calle')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
          <div class="field @error('colonia') is-invalid @enderror">
            <input type="text" name="colonia" id="f-colonia" value="{{ $v('colonia') }}" placeholder=" ">
            <label for="f-colonia">Colonia</label>
          </div>
          @error('colonia')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- ============ Fila 4 (cortos, juntos de 3): CP / Ciudad / Estado ============ --}}
      <div class="row g-4 mb-2">
        <div class="col-lg-4 col-md-6">
          <div class="field @error('cp') is-invalid @enderror">
            <input type="text" name="cp" id="f-cp" value="{{ $v('cp') }}" placeholder=" ">
            <label for="f-cp">Código postal</label>
          </div>
          @error('cp')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="field @error('ciudad') is-invalid @enderror">
            <input type="text" name="ciudad" id="f-ciudad" value="{{ $v('ciudad') }}" placeholder=" ">
            <label for="f-ciudad">Ciudad</label>
          </div>
          @error('ciudad')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="field @error('estado') is-invalid @enderror">
            <input type="text" name="estado" id="f-estado" value="{{ $v('estado') }}" placeholder=" ">
            <label for="f-estado">Estado</label>
          </div>
          @error('estado')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- ============ Fila 5: Estatus con estado Inactivo/Activo ============ --}}
      <div class="row g-4 mb-2">
        <div class="col-md-6 col-lg-4">
          <div class="switch-wrap">
            <span style="font-size:14px;color:var(--ink);font-weight:700">Estatus</span>
            <label class="sw mb-0">
              <input type="checkbox" name="estatus" value="1" {{ $v('estatus', $isEdit ? (int)$provider->estatus : 1) ? 'checked' : '' }}>
              <span class="track"><span class="thumb"></span></span>
              <span class="sw-state state-inactivo">Inactivo</span>
              <span class="sw-state state-activo">Activo</span>
            </label>
          </div>
        </div>
      </div>

      {{-- Acciones --}}
      <div class="actions">
        <a href="{{ route('providers.index') }}" class="btn btn-ghost">Cancelar</a>
        <button class="btn btn-primary" type="submit">{{ $isEdit ? 'Actualizar' : 'Guardar' }}</button>
      </div>
    </form>
  </div>
</div>
@endsection
