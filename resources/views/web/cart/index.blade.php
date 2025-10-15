@extends('layouts.web')
@section('title','Carrito')

@section('content')
<style>
  :root{--ink:#0e1726;--muted:#6b7280;--line:#e8eef6;--surface:#fff;--brand:#6ea8fe;--shadow:0 12px 30px rgba(13,23,38,.06)}
  .wrap{max-width:1100px;margin-inline:auto}
  .grid{display:grid;gap:18px;grid-template-columns:repeat(12,1fr)}
  .card{background:var(--surface);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow)}
  .thumb{width:84px;height:84px;object-fit:cover;border-radius:12px;border:1px solid var(--line);background:#f6f8fc}
  .btn{border:0;border-radius:12px;padding:10px 14px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:10px;text-decoration:none}
  .btn-primary{background:var(--brand);color:#0b1220;box-shadow:0 8px 18px rgba(29,78,216,.12)}
  .btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink)}
  .muted{color:var(--muted)}
  .qty{display:inline-flex;align-items:center;border:1px solid var(--line);border-radius:12px;overflow:hidden}
  .qty button{border:0;background:#fff;padding:8px 12px}
  .qty input{width:52px;text-align:center;border:0;border-left:1px solid var(--line);border-right:1px solid var(--line);height:36px;outline:0}
  .table{width:100%;border-collapse:collapse}
  .table th,.table td{padding:12px;border-bottom:1px solid var(--line);vertical-align:middle}
  .sum{position:sticky; top:12px}
</style>

<div class="wrap">
  <h1 style="margin:10px 0 14px;font-weight:800;">Tu carrito</h1>

  @if(session('ok'))
    <div class="card" style="padding:10px 12px;margin-bottom:12px;background:#f8fffb;color:#0b6b3a;border-color:#d4f3e3">
      {{ session('ok') }}
    </div>
  @endif

  <div class="grid">
    {{-- Lista de productos --}}
    <div class="card" style="grid-column:span 8;">
      @if(count($cart) === 0)
        <div style="padding:16px;">
          <p class="muted" style="margin:0 0 10px;">Tu carrito está vacío.</p>
          <a class="btn btn-ghost" href="{{ route('web.catalog.index') }}">← Ir al catálogo</a>
        </div>
      @else
        <table class="table">
          <thead>
            <tr>
              <th>Producto</th>
              <th style="text-align:right;">Precio</th>
              <th style="text-align:center;">Cantidad</th>
              <th style="text-align:right;">Importe</th>
              <th style="width:1%;"></th>
            </tr>
          </thead>
          <tbody id="cartRows">
            @foreach($cart as $row)
              <tr data-id="{{ $row['id'] }}">
                <td>
                  <div style="display:flex;gap:10px;align-items:center;">
                    <img class="thumb" src="{{ $row['image'] ?: asset('images/placeholder.png') }}"
                         alt="{{ $row['name'] }}"
                         onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
                    <div>
                      <a href="{{ route('web.catalog.show', $row['slug']) }}" style="font-weight:800;color:var(--ink);text-decoration:none;">
                        {{ $row['name'] }}
                      </a>
                      <div class="muted">SKU: {{ $row['sku'] ?: '—' }}</div>
                    </div>
                  </div>
                </td>
                <td style="text-align:right;">${{ number_format($row['price'],2) }}</td>
                <td style="text-align:center;">
                  <div class="qty">
                    <button type="button" onclick="cartMinus({{ $row['id'] }})">−</button>
                    <input type="number" min="1" max="999" value="{{ $row['qty'] }}" onchange="cartSet({{ $row['id'] }}, this.value)">
                    <button type="button" onclick="cartPlus({{ $row['id'] }})">+</button>
                  </div>
                </td>
                <td style="text-align:right;" class="row-total">
                  ${{ number_format($row['price'] * $row['qty'], 2) }}
                </td>
                <td>
                  <form method="POST" action="{{ route('web.cart.remove') }}" onsubmit="return confirm('¿Quitar del carrito?')">
                    @csrf
                    <input type="hidden" name="catalog_item_id" value="{{ $row['id'] }}">
                    <button class="btn btn-ghost" type="submit">Quitar</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
        <div style="padding:12px; display:flex; gap:10px; justify-content:space-between;">
          <form method="POST" action="{{ route('web.cart.clear') }}" onsubmit="return confirm('¿Vaciar carrito por completo?')">
            @csrf
            <button class="btn btn-ghost" type="submit">Vaciar carrito</button>
          </form>
          <a class="btn btn-ghost" href="{{ route('web.catalog.index') }}">← Seguir comprando</a>
        </div>
      @endif
    </div>

    {{-- Resumen --}}
    <div class="card sum" style="grid-column:span 4; padding:14px;">
      <h3 style="margin:0 0 8px;font-weight:800;">Resumen</h3>
      <div style="display:grid;grid-template-columns:1fr auto;gap:6px;">
        <div class="muted">Artículos</div><div id="sumCount">{{ $totals['count'] }}</div>
        <div class="muted">Subtotal</div><div id="sumSubtotal">${{ number_format($totals['subtotal'],2) }}</div>
        <div class="muted">IVA (16%)</div><div id="sumIva">${{ number_format($totals['iva'],2) }}</div>
        <hr style="grid-column:1/-1;border:none;border-top:1px solid var(--line);margin:6px 0">
        <div style="font-weight:800;">Total</div><div id="sumTotal" style="font-weight:800;">${{ number_format($totals['total'],2) }}</div>
      </div>

      <div style="display:flex;flex-direction:column;gap:8px;margin-top:12px;">
        <a class="btn btn-primary" href="{{ route('web.cart.checkout') }}">Proceder</a>
        <a class="btn btn-ghost" href="{{ route('web.contacto') }}">Cotizar por WhatsApp/Correo</a>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  async function postJson(url, data) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data)
    });
    return res.json();
  }

  function updateSummary(totals){
    document.getElementById('sumCount').textContent    = totals.count;
    document.getElementById('sumSubtotal').textContent = '$' + (totals.subtotal).toFixed(2);
    document.getElementById('sumIva').textContent      = '$' + (totals.iva).toFixed(2);
    document.getElementById('sumTotal').textContent    = '$' + (totals.total).toFixed(2);
    // si tienes un badge en el header, actualízalo:
    const badge = document.querySelector('[data-cart-badge]');
    if (badge) badge.textContent = totals.count;
  }

  async function cartSet(id, qty){
    qty = Math.max(1, parseInt(qty||1,10));
    const json = await postJson('{{ route('web.cart.update') }}', { catalog_item_id: id, qty });
    if (!json.ok) return alert(json.msg || 'Error al actualizar');
    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (row){
      row.querySelector('input[type="number"]').value = qty;
      const price = parseFloat(row.querySelector('td:nth-child(2)').textContent.replace(/[^0-9.]/g,'') || '0');
      row.querySelector('.row-total').textContent = '$' + (price * qty).toFixed(2);
    }
    updateSummary(json.totals);
  }

  function cartPlus(id){
    const row = document.querySelector(`tr[data-id="${id}"]`);
    const input = row?.querySelector('input[type="number"]');
    if (!input) return;
    cartSet(id, (parseInt(input.value||'1',10)+1));
  }

  function cartMinus(id){
    const row = document.querySelector(`tr[data-id="${id}"]`);
    const input = row?.querySelector('input[type="number"]');
    if (!input) return;
    cartSet(id, Math.max(1,(parseInt(input.value||'1',10)-1)));
  }
</script>
@endpush
@endsection
