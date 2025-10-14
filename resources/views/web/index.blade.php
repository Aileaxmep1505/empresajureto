@extends('layouts.web')
@section('title','Ventas')
@section('content')
  <h2 style="color:var(--ink); margin-bottom:12px;">Ventas</h2>

  <form method="GET" class="card" style="margin-bottom:16px; display:flex; gap:8px; align-items:center;">
    <input type="text" name="q" value="{{ $q }}" placeholder="Buscar producto..." style="flex:1;padding:10px;border-radius:12px;border:1px solid #ddd">
    <button class="btn">Buscar</button>
  </form>

  @if($productos->count())
    <div class="grid">
      @foreach($productos as $p)
        <div class="col-4">
          <div class="card">
            <img src="{{ $p->image_src ?? asset('images/placeholder.png') }}" alt="{{ $p->name }}" style="width:100%; height:180px; object-fit:cover; border-radius:12px; margin-bottom:10px;">
            <div style="font-weight:700;">{{ $p->name }}</div>
            <div style="opacity:.8; margin:6px 0;">{{ Str::limit($p->description, 80) }}</div>
            <div style="display:flex; align-items:center; justify-content:space-between;">
              <strong>${{ number_format($p->price ?? 0, 2) }}</strong>
              <a class="btn-line" href="{{ route('web.ventas.show', $p->id) }}">Detalles</a>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <div style="margin-top:16px;">{{ $productos->links() }}</div>
  @else
    <div class="card">No hay productos.</div>
  @endif
@endsection
