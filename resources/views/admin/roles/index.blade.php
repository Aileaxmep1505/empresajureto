@extends('layouts.app')
@section('title', 'Roles y permisos')
@section('tema_oscuro', '1')

@push('styles')
@include('partials.ui-tokens')
<style>
  .rp-wrap{ max-width:1080px; margin-inline:auto; padding:0 16px 48px; color:var(--ui-ink); font-family:'Inter',system-ui,sans-serif; }
  .rp-head{ display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap; margin:10px 0 18px; }
  .rp-title{ margin:0; font-size:22px; font-weight:700; letter-spacing:-.02em; }
  .rp-sub{ margin:6px 0 0; font-size:13.5px; color:var(--ui-muted); }
  .rp-flash{ display:flex; align-items:center; gap:10px; padding:11px 14px; border-radius:12px; margin-bottom:16px; font-size:13.5px; font-weight:600;
             background:var(--ui-ok-soft); color:var(--ui-ok-ink); border:1px solid var(--ui-ok); }
  .rp-flash.err{ background:var(--ui-danger-soft); color:var(--ui-danger-ink); border-color:var(--ui-danger); }
  .rp-new{ display:flex; gap:8px; align-items:center; background:var(--ui-surface); border:1px solid var(--ui-border); border-radius:14px; padding:10px 12px; margin-bottom:18px; flex-wrap:wrap; }
  .rp-new input{ flex:1; min-width:200px; height:38px; border:1px solid var(--ui-border-strong); border-radius:10px; background:var(--ui-surface); color:var(--ui-ink); padding:0 12px; font:inherit; font-size:14px; outline:0; }
  .rp-new input:focus{ border-color:var(--ui-accent); box-shadow:0 0 0 3px var(--ui-accent-ring); }
  .btn{ display:inline-flex; align-items:center; gap:7px; height:38px; padding:0 16px; border:0; border-radius:10px; background:var(--ui-accent); color:#fff; font:inherit; font-size:13.5px; font-weight:600; cursor:pointer; text-decoration:none; }
  .btn:hover{ background:var(--ui-accent-hover); }
  .btn.soft{ background:var(--ui-surface); color:var(--ui-ink-2); border:1px solid var(--ui-border-strong); }
  .btn.soft:hover{ background:var(--ui-surface-3); color:var(--ui-ink); }
  .rp-grid{ display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr)); gap:12px; }
  .rp-card{ background:var(--ui-surface); border:1px solid var(--ui-border); border-radius:16px; padding:16px 18px; display:flex; flex-direction:column; gap:10px; }
  .rp-card h3{ margin:0; font-size:16px; font-weight:600; text-transform:capitalize; }
  .rp-card .meta{ font-size:12.5px; color:var(--ui-muted); }
  .rp-badge{ display:inline-block; padding:2px 9px; border-radius:999px; background:var(--ui-accent-soft); color:var(--ui-accent-ink); font-size:12px; font-weight:600; }
  .rp-badge.all{ background:var(--ui-ok-soft); color:var(--ui-ok-ink); }
  .rp-card .row{ display:flex; align-items:center; gap:8px; margin-top:auto; }
  .rp-del{ margin-left:auto; background:none; border:0; color:var(--ui-muted); cursor:pointer; font-size:12.5px; }
  .rp-del:hover{ color:var(--ui-danger-ink); }
</style>
@endpush

@section('content')
<div class="rp-wrap">
  <div class="rp-head">
    <div>
      <h1 class="rp-title">Roles y permisos</h1>
      <p class="rp-sub">Define qué puede hacer cada rol. El rol <b>admin</b> siempre puede todo.</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn soft">Ir a Usuarios</a>
  </div>

  @if(session('ok'))<div class="rp-flash">✓ {{ session('ok') }}</div>@endif
  @if(session('error'))<div class="rp-flash err">⚠ {{ session('error') }}</div>@endif

  <form method="POST" action="{{ route('admin.roles.store') }}" class="rp-new">
    @csrf
    <input type="text" name="name" placeholder="Nombre de un rol nuevo (ej. supervisor)" required>
    <button type="submit" class="btn">+ Crear rol</button>
  </form>

  <div class="rp-grid">
    @foreach($roles as $r)
      <div class="rp-card">
        <div style="display:flex;align-items:center;gap:10px;">
          <h3>{{ $r->name }}</h3>
          @if($r->name === 'admin')
            <span class="rp-badge all">Todo</span>
          @else
            <span class="rp-badge">{{ $r->permissions_count }} / {{ $totalPermisos }}</span>
          @endif
        </div>
        <div class="meta">{{ $r->users_count }} usuario(s) con este rol</div>
        <div class="row">
          <a href="{{ route('admin.roles.edit', $r->name) }}" class="btn soft">Editar permisos</a>
          @unless(in_array($r->name, ['admin','user','cliente_web'], true))
            <form method="POST" action="{{ route('admin.roles.destroy', $r->name) }}" onsubmit="return confirm('¿Eliminar el rol {{ $r->name }}?')" style="margin-left:auto;">
              @csrf @method('DELETE')
              <button type="submit" class="rp-del">Eliminar</button>
            </form>
          @endunless
        </div>
      </div>
    @endforeach
  </div>
</div>
@endsection
