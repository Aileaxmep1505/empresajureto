@extends('layouts.web')
@section('title', $item->name)

@section('content')
<style>
  :root{--ink:#0e1726;--muted:#6b7280;--line:#e8eef6;--surface:#fff;--brand:#6ea8fe;--shadow:0 12px 30px rgba(13,23,38,.06)}
  .wrap{max-width:1100px;margin-inline:auto}
  .grid{display:grid;gap:18px;grid-template-columns:repeat(12,1fr)}
  .card{background:var(--surface);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);overflow:hidden}
  .thumb{width:100%;aspect-ratio:4/3;object-fit:cover;background:#f6f8fc}
  .p16{padding:16px}
  .muted{color:var(--muted)}
  .price{font-weight:800;color:var(--ink);font-size:1.4rem}
  .sale{color:#16a34a;font-weight:800;font-size:1.4rem}
  .btn{display:inline-flex;align-items:center;gap:8px;border:0;border-radius:12px;padding:12px 16px;font-weight:700;background:var(--brand);color:#0b1220;text-decoration:none}
  @media (max-width:900px){ .colL{grid-column:span 12} .colR{grid-column:span 12} }
</style>

<div class="wrap">
  <div class="grid">
    <div class="card colL" style="grid-column:span 6;">
      <img class="thumb" src="{{ $item->image_url ?: asset('images/placeholder.png') }}"
           alt="{{ $item->name }}" onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
      @if(is_array($item->images) && count($item->images))
        <div class="p16" style="display:flex;gap:8px;flex-wrap:wrap;">
          @foreach($item->images as $u)
            <img src="{{ $u }}" alt="" style="width:84px;height:84px;object-fit:cover;border-radius:10px;border:1px solid var(--line);"
                 onerror="this.style.display='none'">
          @endforeach
        </div>
      @endif
    </div>

    <div class="card colR" style="grid-column:span 6;">
      <div class="p16">
        <h1 style="margin:0 0 6px;font-weight:800;">{{ $item->name }}</h1>
        <div class="muted">SKU: {{ $item->sku ?: '—' }}</div>

        <div style="margin:12px 0 14px;">
          @if(!is_null($item->sale_price))
            <div class="sale">${{ number_format($item->sale_price,2) }}</div>
            <div class="muted" style="text-decoration:line-through;">${{ number_format($item->price,2) }}</div>
          @else
            <div class="price">${{ number_format($item->price,2) }}</div>
          @endif
        </div>

        @if($item->excerpt)
          <p class="muted">{{ $item->excerpt }}</p>
        @endif

        @if($item->description)
          <div style="margin-top:10px;">{!! nl2br(e($item->description)) !!}</div>
        @endif

        <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
          <a class="btn" href="{{ route('web.contacto') }}">Cotizar / Comprar</a>
          <a class="btn" style="background:#fff;border:1px solid var(--line)" href="{{ route('web.catalog.index') }}">← Ver más</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
