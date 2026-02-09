@extends('layouts.app')
@section('title','Categorías de gasto')
@section('titulo','Categorías de gasto')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  #cat{
    --bg:#f9fafb; --card:#fff; --ink:#111827; --muted:#6b7280; --line:rgba(17,24,39,.10);
    --radius:20px; --shadow:0 18px 40px -10px rgba(0,0,0,.10);
  }
  #cat .page{ background:var(--bg); min-height: calc(100vh - 120px); padding: 22px; }
  #cat .head{ display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:14px; }
  #cat h1{ margin:0; font-weight:300; color:var(--ink); letter-spacing:-.02em; }
  #cat .sub{ color:var(--muted); margin-top:6px; }
  #cat .search{
    border:none; outline:none; background:#fff; border-radius:999px; padding:12px 18px; width:320px; max-width:100%;
    box-shadow:0 6px 18px rgba(0,0,0,.06);
  }
  #cat .search:focus{ box-shadow:0 10px 24px rgba(0,0,0,.10); }
  #cat .btn-black{
    background:#000; color:#fff; border:0; border-radius:999px; padding:12px 18px; font-weight:600;
    display:inline-flex; align-items:center; gap:10px; text-decoration:none;
    transition: transform .2s ease, background .2s ease;
    white-space:nowrap;
  }
  #cat .btn-black:hover{ transform:scale(1.03); background:#222; }
  #cat .card{
    background:var(--card); border:1px solid var(--line); border-radius:var(--radius);
    box-shadow:var(--shadow); overflow:hidden;
  }
  #cat table{ margin:0; }
  #cat thead th{ font-size:12px; color:var(--muted); font-weight:700; border-bottom:1px solid var(--line); }
  #cat tbody td{ border-bottom:1px solid var(--line); color:var(--ink); }
  #cat .pill{
    display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; font-size:12px;
    background:#f3f4f6; border:1px solid var(--line); color:var(--muted);
  }
  #cat .actions a{
    border-radius:999px; padding:8px 12px; text-decoration:none; font-weight:600; font-size:13px;
    display:inline-flex; align-items:center; gap:8px;
  }
  #cat .a-dark{ background:#000; color:#fff; }
  #cat .a-dark:hover{ background:#222; }
  #cat .a-soft{ background:#fff; color:#111; border:1px solid var(--line); }
  #cat .a-soft:hover{ box-shadow:0 10px 24px rgba(0,0,0,.10); border-color:transparent; }
</style>

<div id="cat">
  <div class="page">
    <div class="head">
      <div>
        <h1>Categorías</h1>
        <div class="sub">Administra categorías para tus gastos.</div>
      </div>

      <div class="d-flex gap-2 align-items-center flex-wrap">
        <form method="GET" action="{{ route('expense-categories.index') }}">
          <input class="search" name="q" value="{{ $q ?? '' }}" placeholder="Buscar por nombre, tipo o slug...">
        </form>
        <a class="btn-black" href="{{ route('expense-categories.create') }}">
          <i class="bi bi-plus-lg"></i> Nueva
        </a>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Slug</th>
              <th>Tipo</th>
              <th>Activa</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse($categories as $c)
              <tr>
                <td class="fw-semibold">{{ $c->name }}</td>
                <td class="text-muted">{{ $c->slug }}</td>
                <td class="text-muted">{{ $c->type ?? '—' }}</td>
                <td>
                  <span class="pill">{{ $c->active ? 'Sí' : 'No' }}</span>
                </td>
                <td class="text-end actions">
                  <a class="a-soft" href="{{ route('expense-categories.show',$c) }}">
                    <i class="bi bi-eye"></i> Ver
                  </a>
                  <a class="a-dark" href="{{ route('expense-categories.edit',$c) }}">
                    <i class="bi bi-pencil"></i> Editar
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted py-5">No hay categorías aún.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="p-3">
        {{ $categories->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
