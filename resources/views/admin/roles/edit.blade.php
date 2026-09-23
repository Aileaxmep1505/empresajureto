@extends('layouts.app')
@section('title', 'Rol: ' . $role->name)
@section('tema_oscuro', '1')

@push('styles')
@include('partials.ui-tokens')
<style>
  .rp-wrap{ max-width:920px; margin-inline:auto; padding:0 16px 96px; color:var(--ui-ink); font-family:'Inter',system-ui,sans-serif; }
  .rp-head{ display:flex; align-items:center; gap:12px; margin:10px 0 18px; flex-wrap:wrap; }
  .rp-back{ display:inline-flex; align-items:center; gap:6px; height:34px; padding:0 10px; border-radius:10px; background:none; color:var(--ui-muted); font-size:13.5px; font-weight:600; text-decoration:none; }
  .rp-back:hover{ background:var(--ui-surface-3); color:var(--ui-ink); }
  .rp-title{ margin:0; font-size:22px; font-weight:700; letter-spacing:-.02em; text-transform:capitalize; }
  .rp-note{ padding:12px 14px; border-radius:12px; background:var(--ui-ok-soft); color:var(--ui-ok-ink); border:1px solid var(--ui-ok); font-size:13.5px; font-weight:600; margin-bottom:16px; }

  .rp-group{ background:var(--ui-surface); border:1px solid var(--ui-border); border-radius:16px; padding:14px 16px; margin-bottom:12px; }
  .rp-group-head{ display:flex; align-items:baseline; gap:10px; margin-bottom:10px; flex-wrap:wrap; }
  .rp-group-head h3{ margin:0; font-size:15px; font-weight:600; }
  .rp-group-head .d{ font-size:12.5px; color:var(--ui-muted); }
  .rp-group-head .all{ margin-left:auto; font-size:12.5px; font-weight:600; color:var(--ui-accent-ink); background:none; border:0; cursor:pointer; }
  .rp-perms{ display:grid; grid-template-columns:repeat(auto-fit, minmax(240px,1fr)); gap:6px 16px; }
  .rp-perm{ display:flex; align-items:center; gap:10px; padding:8px 6px; border-radius:10px; cursor:pointer; }
  .rp-perm:hover{ background:var(--ui-surface-2); }
  .rp-perm input{ width:18px; height:18px; accent-color:var(--ui-accent); flex:0 0 auto; }
  .rp-perm span{ font-size:13.5px; color:var(--ui-ink-2); }
  .rp-perm .k{ display:block; font-size:11.5px; color:var(--ui-muted); }

  .rp-bar{ position:sticky; bottom:0; margin-top:16px; padding:14px 0; display:flex; gap:10px; justify-content:flex-end; align-items:center;
           background:linear-gradient(0deg, var(--ui-surface-2) 70%, transparent); }
  .btn{ display:inline-flex; align-items:center; gap:7px; height:40px; padding:0 20px; border:0; border-radius:12px; background:var(--ui-accent); color:#fff; font:inherit; font-size:14px; font-weight:600; cursor:pointer; text-decoration:none; }
  .btn:hover{ background:var(--ui-accent-hover); }
  .btn.soft{ background:var(--ui-surface); color:var(--ui-ink-2); border:1px solid var(--ui-border-strong); }
  .btn.soft:hover{ background:var(--ui-surface-3); color:var(--ui-ink); }
  .rp-count{ margin-right:auto; font-size:13px; color:var(--ui-muted); }
</style>
@endpush

@section('content')
<div class="rp-wrap">
  <div class="rp-head">
    <a href="{{ route('admin.roles.index') }}" class="rp-back">‹ Roles</a>
    <h1 class="rp-title">{{ $role->name }}</h1>
  </div>

  @if($esAdmin)
    <div class="rp-note">El rol <b>admin</b> puede todo por diseño (no depende de estas casillas). Aquí solo se muestran de referencia.</div>
  @endif

  <form method="POST" action="{{ route('admin.roles.update', $role->name) }}" id="rolForm">
    @csrf @method('PUT')

    @foreach($grupos as $clave => $g)
      <div class="rp-group" data-grupo>
        <div class="rp-group-head">
          <h3>{{ $g['titulo'] }}</h3>
          <span class="d">{{ $g['descripcion'] }}</span>
          @unless($esAdmin)<button type="button" class="all" data-toggle-grupo>Marcar todo</button>@endunless
        </div>
        <div class="rp-perms">
          @foreach($g['permisos'] as $llave => $etiqueta)
            <label class="rp-perm">
              <input type="checkbox" name="permisos[]" value="{{ $llave }}"
                     @checked($esAdmin || in_array($llave, $asignados, true)) @disabled($esAdmin)>
              <span>{{ $etiqueta }}<span class="k">{{ $llave }}</span></span>
            </label>
          @endforeach
        </div>
      </div>
    @endforeach

    @unless($esAdmin)
      <div class="rp-bar">
        <span class="rp-count" id="rpCount"></span>
        <a href="{{ route('admin.roles.index') }}" class="btn soft">Cancelar</a>
        <button type="submit" class="btn">Guardar permisos</button>
      </div>
    @endunless
  </form>
</div>

@unless($esAdmin)
<script>
(function(){
  var form = document.getElementById('rolForm');
  if(!form) return;
  var count = document.getElementById('rpCount');
  function actualizar(){
    var n = form.querySelectorAll('input[name="permisos[]"]:checked').length;
    var t = form.querySelectorAll('input[name="permisos[]"]').length;
    if(count) count.textContent = n + ' de ' + t + ' permisos marcados';
  }
  form.addEventListener('change', actualizar);
  document.querySelectorAll('[data-toggle-grupo]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var grupo = btn.closest('[data-grupo]');
      var chks = grupo.querySelectorAll('input[type="checkbox"]');
      var todos = Array.prototype.every.call(chks, function(c){ return c.checked; });
      chks.forEach(function(c){ c.checked = !todos; });
      btn.textContent = todos ? 'Marcar todo' : 'Quitar todo';
      actualizar();
    });
  });
  actualizar();
})();
</script>
@endunless
@endsection
