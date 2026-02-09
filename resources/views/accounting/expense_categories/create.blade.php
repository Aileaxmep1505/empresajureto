@extends('layouts.app')
@section('title','Nueva categoría')
@section('titulo','Nueva categoría')

@section('content')
<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700" rel="stylesheet">

@php
  $v = function($key, $default=null){
    return old($key) ?? $default;
  };
@endphp

<style>
:root{
  --mint:#48cfad; --mint-dark:#34c29e;
  --ink:#2a2e35; --muted:#7a7f87; --line:#e9ecef; --card:#ffffff;
}
*{box-sizing:border-box}
body{font-family:"Open Sans",sans-serif;background:#eaebec}

.edit-wrap{ max-width:980px; margin:10px auto 40px; padding:0 16px; }
.panel{ background:var(--card); border-radius:16px; box-shadow:0 16px 40px rgba(18,38,63,.12); overflow:hidden; }
.panel-head{ padding:18px 22px; border-bottom:1px solid var(--line); display:flex; align-items:center; gap:12px; justify-content:space-between; }
.hgroup h2{ margin:0; font-weight:700; color:var(--ink); letter-spacing:-.02em }
.hgroup p{ margin:2px 0 0; color:var(--muted); font-size:14px }
.back-link{ display:inline-flex; align-items:center; gap:8px; color:var(--muted); text-decoration:none; padding:8px 12px; border-radius:10px; border:1px solid var(--line); background:#fff; }
.back-link:hover{ color:#111; border-color:#e3e6eb; box-shadow:0 8px 18px rgba(0,0,0,.08) }

.form{ padding:22px; }
.section-gap{ margin-top:8px; }

.row{ display:flex; flex-wrap:wrap; margin-left:-10px; margin-right:-10px; }
.col{ padding:0 10px; }
.col-12{ width:100% }
@media (min-width: 768px){
  .col-md-6{ width:50% } .col-md-4{ width:33.3333% } .col-md-3{ width:25% }
}
.gy-3 > .col{ margin-top:12px }

.field{
  position:relative; background:#fff; border:1px solid var(--line);
  border-radius:12px; padding:12px 12px 6px;
  transition:box-shadow .2s, border-color .2s;
}
.field:focus-within{ border-color:#d8dee6; box-shadow:0 6px 18px rgba(18,38,63,.08) }
.field input,.field select{
  width:100%; border:0; outline:0; background:transparent;
  font-size:14px; color:var(--ink); padding-top:8px;
}
.field label{
  position:absolute; left:12px; top:10px; color:var(--muted); font-size:12px;
  transition:transform .15s ease, color .15s ease, font-size .15s ease, top .15s ease;
  pointer-events:none;
}
.field input::placeholder{ color:transparent; }
.field input:focus + label,
.field input:not(:placeholder-shown) + label,
.field select:focus + label{
  top:4px; transform:translateY(-8px); font-size:10.5px; color:var(--mint-dark);
}

.status-row{
  display:flex; align-items:center; justify-content:space-between;
  border:1px solid var(--line); background:#fff; border-radius:12px; padding:10px 12px;
}
.status-row .label{ font-size:13px; color:var(--ink); font-weight:700 }
.status-row .state{ font-size:12px; color:var(--muted); margin-right:10px }
.switch{ display:inline-flex; align-items:center; gap:10px; user-select:none; }
.switch input{ display:none }
.switch .track{ width:44px; height:24px; border-radius:999px; background:#e9edf2; position:relative; transition:background .2s; }
.switch .thumb{ width:20px; height:20px; border-radius:50%; background:#fff; position:absolute; top:2px; left:2px; box-shadow:0 2px 8px rgba(0,0,0,.15); transition:left .18s ease; }
.switch input:checked + .track{ background:var(--mint) }
.switch input:checked + .track .thumb{ left:22px }

.actions{ display:flex; gap:10px; justify-content:flex-end; margin-top:8px; }
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

.is-invalid{ border-color:#f9c0c0 !important }
.error{ color:#cc4b4b; font-size:12px; margin-top:6px }
</style>

<div class="edit-wrap">
  <div class="panel">
    <div class="panel-head">
      <div class="hgroup">
        <h2>Nueva categoría</h2>
        <p class="subtitle">Crea categorías para clasificar tus gastos (gasolina, renta, nómina, etc.).</p>
      </div>
      <a href="{{ route('expense-categories.index') }}" class="back-link" title="Volver">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Volver
      </a>
    </div>

    <form class="form" action="{{ route('expense-categories.store') }}" method="POST">
      @csrf

      <div class="row gy-3 section-gap">
        <div class="col col-12 col-md-6">
          <div class="field @error('name') is-invalid @enderror">
            <input type="text" name="name" id="f-name" value="{{ $v('name') }}" placeholder=" " required>
            <label for="f-name">Nombre *</label>
          </div>
          @error('name')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="col col-12 col-md-6">
          <div class="field @error('slug') is-invalid @enderror">
            <input type="text" name="slug" id="f-slug" value="{{ $v('slug') }}" placeholder=" ">
            <label for="f-slug">Slug (opcional)</label>
          </div>
          @error('slug')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="row gy-3 section-gap">
        <div class="col col-12 col-md-6">
          <div class="field @error('type') is-invalid @enderror">
            <input type="text" name="type" id="f-type" value="{{ $v('type') }}" placeholder=" ">
            <label for="f-type">Tipo (opcional)</label>
          </div>
          @error('type')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="col col-12 col-md-6">
          <div class="status-row">
            <div class="label">Estado</div>
            <div class="d-flex align-items-center">
              <span class="state" id="stateText">{{ $v('active', 1) ? 'Activo' : 'Inactivo' }}</span>
              <label class="switch mb-0">
                <input type="checkbox" name="active" id="activeToggle" value="1" {{ $v('active', 1) ? 'checked' : '' }}>
                <span class="track"><span class="thumb"></span></span>
              </label>
            </div>
          </div>
          @error('active')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="actions">
        <a href="{{ route('expense-categories.index') }}" class="btn btn-ghost">Cancelar</a>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
const activeToggle = document.getElementById('activeToggle');
const stateText    = document.getElementById('stateText');
const formEl       = document.querySelector('form');

activeToggle?.addEventListener('change', ()=>{
  stateText.textContent = activeToggle.checked ? 'Activo' : 'Inactivo';
});

formEl?.addEventListener('submit', ()=>{
  // Si está apagado, enviar 0 (porque checkbox apagado no manda nada)
  if (activeToggle && !activeToggle.checked) {
    const h = document.createElement('input');
    h.type='hidden'; h.name='active'; h.value='0';
    formEl.appendChild(h);
  }
});
</script>
@endsection
