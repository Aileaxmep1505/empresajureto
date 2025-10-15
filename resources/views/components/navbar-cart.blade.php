@php
  $cart = session('cart', []);
  $count = array_sum(array_column($cart, 'qty'));
@endphp

<a href="{{ route('web.cart.index') }}" style="position:relative; display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
  🛒
  <span data-cart-badge
        style="min-width:18px;height:18px;line-height:18px;text-align:center;font-size:.78rem;background:#ef4444;color:#fff;border-radius:999px;display:inline-block;padding:0 6px;">
    {{ $count }}
  </span>
</a>
