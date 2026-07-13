{{-- resources/views/checkout/success.blade.php --}}
@extends('layouts.web')
@section('title','Pago recibido')

@section('content')
@php
  use App\Models\Venta;
  use App\Models\CatalogItem;

  /** =========================
   * 1) RESOLVER ORIGEN DE DATOS
   * ==========================*/
  /** @var \App\Models\Order|null $order */
  $hasOrder = isset($order) && $order;

  $sessionId = $sessionId
      ?? request('session_id')
      ?? ($hasOrder ? ($order->stripe_session_id ?? null) : null);

  /*
  |--------------------------------------------------------------------------
  | Helpers internos de esta vista
  |--------------------------------------------------------------------------
  | Esta vista debe mostrar artículos aunque la sesión ya se haya limpiado.
  | Por eso intenta leer en este orden:
  | 1) $order->items / order_items
  | 2) $cart enviado desde el controlador success()
  | 3) session('checkout.cart_snapshot')
  | 4) session('cart')
  */
  $normalizeCheckoutItem = function ($row) {
      $rowArr = is_array($row) ? $row : (array) $row;

      $metaRaw = $rowArr['meta'] ?? null;
      $meta = [];
      if (is_array($metaRaw)) {
          $meta = $metaRaw;
      } elseif (is_string($metaRaw) && $metaRaw !== '') {
          $decoded = json_decode($metaRaw, true);
          $meta = is_array($decoded) ? $decoded : [];
      }

      /*
       * Buscar producto real si la partida solo trae ID.
       * Esto ayuda cuando order_items guarda product_id/catalog_item_id,
       * pero no guarda nombre, imagen o precio.
       */
      $catalogId = $rowArr['catalog_item_id']
          ?? $rowArr['product_id']
          ?? $rowArr['item_id']
          ?? data_get($rowArr, 'item.id')
          ?? null;

      $catalog = null;
      if ($catalogId) {
          try {
              $catalog = CatalogItem::find($catalogId);
          } catch (\Throwable $e) {
              $catalog = null;
          }
      }

      $name = $rowArr['name']
          ?? $rowArr['product_name']
          ?? $rowArr['description']
          ?? $rowArr['item_name']
          ?? $rowArr['title']
          ?? data_get($rowArr, 'item.name')
          ?? ($catalog->name ?? null)
          ?? 'Producto';

      $qty = (int) (
          $rowArr['qty']
          ?? $rowArr['quantity']
          ?? $rowArr['cantidad']
          ?? 1
      );
      $qty = max(1, $qty);

      $price = (float) (
          $rowArr['price']
          ?? $rowArr['unit_price']
          ?? $rowArr['unit_amount']
          ?? $rowArr['precio']
          ?? data_get($rowArr, 'item.price')
          ?? data_get($rowArr, 'item.sale_price')
          ?? ($catalog ? ($catalog->sale_price ?? $catalog->price ?? 0) : 0)
      );

      $amount = (float) (
          $rowArr['amount']
          ?? $rowArr['total']
          ?? $rowArr['line_total']
          ?? $rowArr['subtotal']
          ?? ($price * $qty)
      );

      if ($price <= 0 && $amount > 0 && $qty > 0) {
          $price = round($amount / $qty, 2);
      }

      if ($amount <= 0 && $price > 0) {
          $amount = round($price * $qty, 2);
      }

      $image = $rowArr['image']
          ?? $rowArr['image_url']
          ?? $rowArr['thumbnail']
          ?? data_get($meta, 'image')
          ?? data_get($meta, 'image_url')
          ?? data_get($rowArr, 'item.image_url')
          ?? ($catalog->image_url ?? null)
          ?? ($catalog->image ?? null)
          ?? null;

      return (object) [
          'catalog_item_id' => $catalogId,
          'name' => $name,
          'qty' => $qty,
          'price' => $price,
          'amount' => $amount,
          'meta' => ['image' => $image],
      ];
  };

  $itemsFromArray = function ($rows) use ($normalizeCheckoutItem) {
      return collect((array) $rows)
          ->filter(fn ($row) => is_array($row) || is_object($row))
          ->map(fn ($row) => $normalizeCheckoutItem($row))
          ->values();
  };

  /*
  |--------------------------------------------------------------------------
  | Envío seleccionado
  |--------------------------------------------------------------------------
  */
  $checkoutShipping = (array) session('checkout.shipping', []);
  $legacyShipping = (array) session('shipping', []);
  $incomingShipping = is_array($shipping ?? null) ? $shipping : [];

  $sessionShipping = array_filter($incomingShipping)
      ? $incomingShipping
      : (array_filter($checkoutShipping) ? $checkoutShipping : $legacyShipping);

  $sessionShipPrice = (float) ($sessionShipping['price'] ?? 0);
  $sessionShipName = $sessionShipping['name']
      ?? $sessionShipping['carrier']
      ?? $sessionShipping['label']
      ?? null;
  $sessionShipSrv = $sessionShipping['service_label']
      ?? $sessionShipping['service_description']
      ?? $sessionShipping['service']
      ?? null;
  $sessionShipEta = $sessionShipping['eta'] ?? null;
  $sessionShipLogo = $sessionShipping['logo_url'] ?? null;
  $storePays = (bool) ($sessionShipping['store_pays'] ?? false);
  $carrierCost = (float) ($sessionShipping['carrier_cost'] ?? $sessionShipPrice);

  /*
  |--------------------------------------------------------------------------
  | Artículos enviados por el controlador
  |--------------------------------------------------------------------------
  */
  $incomingCart = isset($cart) && is_array($cart) ? $cart : [];
  $snapshotCart = (array) session('checkout.cart_snapshot', []);
  $sessionCart = (array) session('cart', []);

  // --------- Cuando VIENE ORDEN ----------
  if ($hasOrder) {
      $items = collect();

      try {
          if (method_exists($order, 'items')) {
              $itemsCol = $order->relationLoaded('items') ? $order->items : $order->items()->get();
              $items = $itemsFromArray($itemsCol ?? []);
          }
      } catch (\Throwable $e) {
          $items = collect();
      }

      /*
       * Fallback directo a DB:
       * Si la relación del modelo no leyó las partidas, buscamos manualmente en order_items.
       */
      if ($items->isEmpty()) {
          try {
              if (\Illuminate\Support\Facades\Schema::hasTable('order_items')) {
                  $dbItems = \Illuminate\Support\Facades\DB::table('order_items')
                      ->where('order_id', $order->id)
                      ->get();

                  $items = $itemsFromArray($dbItems ?? []);
              }
          } catch (\Throwable $e) {
              $items = collect();
          }
      }

      /*
       * Fallback de pantalla:
       * Si por alguna razón la orden todavía no tiene partidas guardadas,
       * usamos el carrito que el controlador envía antes de limpiar la sesión.
       */
      if ($items->isEmpty() && !empty($incomingCart)) {
          $items = $itemsFromArray($incomingCart);
      }

      if ($items->isEmpty() && !empty($snapshotCart)) {
          $items = $itemsFromArray($snapshotCart);
      }

      if ($items->isEmpty() && !empty($sessionCart)) {
          $items = $itemsFromArray($sessionCart);
      }

      /*
       * Si la tabla order_items trae una partida genérica en ceros
       * pero todavía tenemos el carrito entrante/snapshot, usamos ese carrito.
       * Esto corrige el caso visual: Producto x1 $0.00.
       */
      $itemsLookEmpty = $items->count()
          && (float) $items->sum('amount') <= 0
          && $items->filter(fn ($i) => ($i->name ?? 'Producto') !== 'Producto')->isEmpty();

      if ($itemsLookEmpty && !empty($incomingCart)) {
          $items = $itemsFromArray($incomingCart);
      } elseif ($itemsLookEmpty && !empty($snapshotCart)) {
          $items = $itemsFromArray($snapshotCart);
      } elseif ($itemsLookEmpty && !empty($sessionCart)) {
          $items = $itemsFromArray($sessionCart);
      }

      $subtotal = (float) ($order->subtotal ?? 0);

      if ($subtotal <= 0 && $items->count()) {
          $subtotal = (float) $items->sum('amount');
      }

      /*
       * Último respaldo:
       * Si solo tenemos una partida pero vino en ceros, usamos el subtotal real de la orden
       * para que no se muestre $0.00.
       */
      if ($subtotal > 0 && $items->count() === 1 && (float) $items->sum('amount') <= 0) {
          $only = $items->first();
          $qty = max(1, (int)($only->qty ?? 1));
          $only->amount = round($subtotal, 2);
          $only->price = round($subtotal / $qty, 2);
          $items = collect([$only]);
      }

      /*
       * Si la orden todavía no guardó el envío pero la sesión sí lo trae,
       * usamos la sesión para no mostrar GRATIS incorrectamente.
       */
      $orderShipAmount = (float) ($order->shipping_amount ?? 0);
      $shipAmount = $orderShipAmount > 0 ? $orderShipAmount : $sessionShipPrice;

      $orderTotal = (float) ($order->total ?? 0);
      $total = $orderTotal > 0
          ? (($orderShipAmount <= 0 && $sessionShipPrice > 0) ? ($subtotal + $sessionShipPrice) : $orderTotal)
          : ($subtotal + $shipAmount);

      $shipName = $order->shipping_name
          ?? $order->shipping_carrier
          ?? $sessionShipName
          ?? 'Envío estándar';

      $shipSrv = $order->shipping_service
          ?? $sessionShipSrv;

      $shipEta = $order->shipping_eta
          ?? $sessionShipEta;

      $shipLogoUrl = $order->shipping_logo_url
          ?? $sessionShipLogo;

      $addrArr = (array) ($order->address_json ?? []);
      $addrTx = trim(
          ($addrArr['street'] ?? '') . ' ' .
          ($addrArr['ext_number'] ?? '') . ' ' .
          ($addrArr['colony'] ?? '') . ', ' .
          ($addrArr['municipality'] ?? '') . ', ' .
          ($addrArr['state'] ?? '') . ' CP ' .
          ($addrArr['postal_code'] ?? '')
      );
  }
  // --------- Fallback: usar CARRITO / SESIÓN ----------
  else {
      $cart = !empty($incomingCart)
          ? $incomingCart
          : (!empty($snapshotCart) ? $snapshotCart : $sessionCart);

      $items = $itemsFromArray($cart);

      $subtotal = (float) $items->sum('amount');

      $shipAmount = $sessionShipPrice;
      $total = $subtotal + $shipAmount;

      $shipName = $sessionShipName ?: 'Envío estándar';
      $shipSrv = $sessionShipSrv;
      $shipEta = $sessionShipEta;
      $shipLogoUrl = $sessionShipLogo;

      $addr = (array) session('checkout.address', []);
      $addrTx = trim(
          ($addr['street'] ?? '') . ' ' .
          ($addr['ext_number'] ?? '') . ' ' .
          ($addr['colony'] ?? '') . ', ' .
          ($addr['municipality'] ?? '') . ', ' .
          ($addr['state'] ?? '') . ' CP ' .
          ($addr['postal_code'] ?? '')
      );
  }

  /** =========================
   * 2) FACTURA (si se timbró)
   * ==========================*/
  $invoice = (array) ($invoice ?? session('checkout.invoice', []));
  $invoiceId = $invoice['id'] ?? null;
  $invoiceUUID = $invoice['uuid'] ?? null;
  $series = $invoice['series'] ?? null;
  $folioNum = $invoice['folio_number'] ?? null;
  $folioFull = trim(($series ? $series : '').'-'.($folioNum ? $folioNum : ''), '-');

  $ventaIdFromSession = session('venta_id');
  $venta = null;

  if ($ventaIdFromSession) {
      $venta = Venta::query()
        ->where('user_id', auth()->id())
        ->where('id', $ventaIdFromSession)
        ->first();
  }

  if (!$venta && auth()->check()) {
      $venta = Venta::query()
        ->where('user_id', auth()->id())
        ->orderByDesc('timbrada_en')
        ->orderByDesc('id')
        ->first();
  }

  $facturaPDF = $venta->factura_pdf_url ?? null;
  $facturaXML = $venta->factura_xml_url ?? null;
  $facturaId = $venta->factura_id ?? null;
  $facturaUUID = $venta->factura_uuid ?? null;

  $tagFolio = $folioFull ?: ($venta->serie ?? '');
  $tagFolio = trim($tagFolio . (isset($venta->folio) ? '-'.$venta->folio : ''), '-');
  $tagUUID = $invoiceUUID ?: $facturaUUID;
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet"/>

<style>
  /* ====================== VARIABLES ====================== */
  :root {
    --bg: #f9fafb;
    --card: #ffffff;
    --ink: #111111;
    --text: #333333;
    --muted: #888888;
    --line: #ebebeb;
    --blue: #007aff;
    --blue-soft: #e6f0ff;
    --success: #15803d;
    --success-soft: #e6ffe6;
  }

  body {
    font-family: "Quicksand", system-ui, -apple-system, sans-serif;
    background: var(--bg);
    color: var(--text);
    margin: 0;
  }

  /* ====== Estilos aislados para esta vista ====== */
  .sx-wrap {
    max-width: 980px;
    margin: clamp(32px, 5vw, 64px) auto;
    padding: 0 20px;
    box-sizing: border-box;
  }
  
  .sx-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 20px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.04);
    overflow: hidden;
  }
  
  .sx-hero {
    padding: 40px 32px;
    background: radial-gradient(circle at top right, var(--success-soft) 0%, var(--card) 60%);
    text-align: center;
    border-bottom: 1px solid var(--line);
  }
  
  .sx-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--success-soft); color: var(--success);
    font-weight: 700; border-radius: 999px; padding: 6px 14px;
    font-size: 0.95rem; margin-bottom: 20px;
  }
  
  .sx-title {
    margin: 0; font-size: 2.2rem; font-weight: 700; color: var(--ink);
    display: flex; gap: 12px; align-items: center; justify-content: center;
  }
  
  .sx-sub {
    color: var(--muted); margin-top: 12px; font-size: 1.05rem; font-weight: 500;
  }

  .sx-body {
    padding: 32px; display: grid; grid-template-columns: 2fr 1fr; gap: 32px;
  }
  @media(max-width: 980px){ .sx-body{ grid-template-columns: 1fr; } }

  .btn {
    display: inline-flex; align-items: center; gap: 10px; justify-content: center;
    border-radius: 8px; padding: 10px 18px; font-weight: 600; font-size: 0.95rem;
    text-decoration: none; border: none; cursor: pointer; font-family: inherit;
    transition: transform 0.15s ease, box-shadow 0.2s ease, background 0.2s ease;
  }
  .btn:active { transform: scale(0.98); }
  
  .btn-primary { background: var(--blue); color: #ffffff; }
  .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0, 122, 255, 0.15); }
  
  .btn-outline { background: var(--card); border: 1px solid var(--blue); color: var(--blue); }
  .btn-outline:hover { background: var(--blue-soft); transform: translateY(-1px); }
  
  .btn-ghost { background: transparent; color: #555555; border: 1px solid var(--line); }
  .btn-ghost:hover { background: var(--bg); color: var(--ink); }

  .muted { color: var(--muted); }
  
  .sum { display: flex; justify-content: space-between; margin: 12px 0; font-weight: 600; color: var(--text); }
  .line { border: 0; border-top: 1px solid var(--line); margin: 24px 0; }
  
  .sx-id {
    display: flex; gap: 12px; align-items: center; justify-content: space-between;
    background: var(--bg); border: 1px dashed var(--line); color: var(--ink);
    padding: 12px 16px; border-radius: 12px; word-break: break-all; margin-top: 8px;
  }
  
  .sx-grid-items { display: grid; gap: 0; }
  .sx-item {
    display: grid; grid-template-columns: auto 1fr auto; gap: 16px; align-items: center;
    padding: 16px 0; border-bottom: 1px solid var(--line);
  }
  .sx-item:last-child { border-bottom: none; }
  
  .sx-receipt {
    background: var(--bg); border: 1px solid var(--line); border-radius: 16px; padding: 20px;
  }
  
  .sx-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px; }
  
  .sx-ship-card {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    background: var(--bg);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 16px;
    margin-top: 10px;
  }

  .sx-ship-logo {
    width: 92px;
    height: 58px;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: none;
  }

  .sx-ship-logo img {
    max-width: 78px;
    max-height: 38px;
    object-fit: contain;
    display: block;
  }

  .sx-pill {
    display: inline-flex; gap: 8px; align-items: center;
    background: var(--card); border: 1px solid var(--line); color: var(--ink);
    padding: 6px 12px; border-radius: 999px; font-weight: 600; font-size: 0.85rem;
  }

  canvas#confetti { position: fixed; inset: 0; pointer-events: none; z-index: 9999; }
</style>

<canvas id="confetti"></canvas>

<div class="sx-wrap">
  <div class="sx-card">
    <div class="sx-hero">
      <div class="sx-badge">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M5 12l4 4L19 6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Pago Procesado con Éxito
      </div>
      <h1 class="sx-title">
        ¡Gracias por tu compra! <span aria-hidden="true">🎉</span>
      </h1>
      <p class="sx-sub">Hemos registrado tu orden correctamente. En breve recibirás un correo con el detalle completo.</p>
    </div>

    <div class="sx-body">
      {{-- Columna principal --}}
      <div>
        {{-- ID de sesión de Stripe --}}
        @if($sessionId)
        <div style="margin-bottom: 24px;">
          <label style="font-weight:700; color: var(--ink);">ID de Transacción (Stripe)</label>
          <div class="sx-id" id="idBox">
            <code id="sid" style="font-family: monospace; font-size: 0.95rem;">{{ $sessionId }}</code>
            <button class="btn btn-ghost" id="copyBtn" type="button" title="Copiar ID" style="padding: 6px 12px; font-size: 0.85rem;">Copiar</button>
          </div>
          <div class="muted" id="copyMsg" style="display:none; margin-top:8px; font-size: 0.85rem; color: var(--success); font-weight: 600;">¡Copiado al portapapeles!</div>
        </div>
        @endif

        {{-- Envío + dirección (si existen) --}}
        @if(($shipName ?? null) || ($addrTx ?? null))
          <div class="line"></div>
          <h3 style="font-weight:700; margin: 0 0 12px 0; font-size: 1.1rem; color: var(--ink);">Detalles de Envío</h3>

          <div class="sx-ship-card">
            @if($shipLogoUrl)
              <div class="sx-ship-logo">
                <img src="{{ $shipLogoUrl }}" alt="{{ $shipName }}" onerror="this.style.display='none'">
              </div>
            @endif

            <div style="min-width:0;">
              <div style="margin-bottom:6px; font-weight: 700; color: var(--ink);">
                {{ $shipName ?: 'Envío estándar' }}
                @if($shipSrv)
                  <span class="muted" style="font-weight:600;">— {{ $shipSrv }}</span>
                @endif
              </div>

              @if($shipEta)
                <div class="muted" style="font-weight:600; margin-bottom:6px;">{{ $shipEta }}</div>
              @endif

              @if(!empty($addrTx))
                <div style="line-height: 1.6; color: var(--text);">{{ $addrTx }}</div>
              @endif

              @if($storePays && $carrierCost > 0)
                <div class="muted" style="font-size:.85rem; margin-top:8px;">
                  Costo de paquetería: ${{ number_format($carrierCost, 2) }} MXN cubierto por tienda.
                </div>
              @endif
            </div>
          </div>
        @endif

        {{-- Resumen del pedido --}}
        <div class="line"></div>
        <h3 style="margin: 0 0 16px 0; font-weight:700; font-size: 1.1rem; color: var(--ink);">Resumen de tu pedido</h3>

        @if($items->count())
          <div class="sx-grid-items">
            @foreach($items as $it)
              @php
                $img = data_get($it, 'meta.image');
                if (is_string($it->meta ?? null)) {
                  $decoded = json_decode($it->meta, true);
                  $img = $img ?: data_get($decoded, 'image');
                }
              @endphp
              <div class="sx-item">
                <img src="{{ $img ?: asset('images/placeholder.png') }}" alt="" 
                     style="width:56px; height:56px; border-radius:8px; border:1px solid var(--line); object-fit:cover; background: var(--bg);">
                <div>
                  <div style="font-weight:600; color: var(--ink);">{{ $it->name ?? 'Producto' }}</div>
                  <div class="muted" style="font-size: 0.9rem; margin-top: 4px;">x{{ (int)($it->qty ?? 1) }}</div>
                </div>
                <div style="text-align:right">
                  <div class="muted" style="font-size:0.85rem; margin-bottom: 2px;">
                    ${{ number_format((float)($it->price ?? 0), 2) }} c/u
                  </div>
                  <div style="font-weight:700; color: var(--ink);">
                    ${{ number_format((float)($it->amount ?? ((float)($it->price ?? 0) * (int)($it->qty ?? 1))), 2) }}
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        @else
          <div class="sx-item" style="grid-template-columns:1fr;">
            <div>
              <div style="font-weight:600; color: var(--ink);">Pedido registrado correctamente</div>
              <div class="muted" style="font-size: 0.92rem; margin-top: 4px;">
                No encontramos el detalle de partidas en la sesión, pero tu orden quedó guardada y el total pagado se muestra abajo.
              </div>
            </div>
          </div>
        @endif

        {{-- Totales --}}
        <div class="line" style="margin-top: 16px;"></div>
        <div class="sum"><span>Subtotal</span><span style="color: var(--ink);">${{ number_format($subtotal,2) }} MXN</span></div>
        <div class="sum">
          <span>Envío</span>
          <span class="muted">{{ $shipAmount > 0 ? '$'.number_format($shipAmount,2).' MXN' : 'GRATIS' }}</span>
        </div>
        @if($storePays && $carrierCost > 0)
          <div class="muted" style="font-size:.85rem; text-align:right; margin-top:-6px;">
            Paquetería ${{ number_format($carrierCost, 2) }} MXN cubierta por tienda
          </div>
        @endif
        <div class="line" style="margin-bottom: 16px;"></div>
        <div class="sum" style="font-size:1.2rem; font-weight: 700; color: var(--ink);">
          <span>Total Pagado</span>
          <span>${{ number_format($total,2) }} MXN</span>
        </div>

        {{-- CTAs --}}
        <div class="sx-actions" style="margin-top: 40px;">
          <a href="{{ route('web.home') }}" class="btn btn-primary">Volver al Inicio</a>
          {{-- Si tienes ruta de órdenes, descomenta esto --}}
          {{-- <a href="{{ route('orders.index') }}" class="btn btn-outline">Ver Mis Pedidos</a> --}}
        </div>
      </div>

      {{-- Sidebar: comprobantes --}}
      <aside>
        <div class="sx-receipt">
          <h3 style="font-weight:700; font-size: 1.05rem; margin: 0 0 8px 0; color: var(--ink);">Comprobante de Orden</h3>
          <p class="muted" style="margin:0; line-height: 1.5; font-size: 0.95rem;">
            Se ha enviado una copia de tu recibo a tu correo electrónico asociado.
          </p>
        </div>

        {{-- FACTURA CFDI --}}
        <div class="sx-receipt" style="margin-top:16px">
          <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom: 12px;">
            <h3 style="font-weight:700; font-size: 1.05rem; margin: 0; color: var(--ink);">Facturación CFDI</h3>
          </div>
          
          @if($tagFolio || $tagUUID)
            <div style="margin-bottom: 16px;">
              <span class="sx-pill">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ $tagFolio ?: 'Emitida' }}
              </span>
            </div>
          @endif

          {{-- Prioriza descarga vía rutas del controlador si tenemos $invoiceId --}}
          @if($invoiceId)
            <div style="display:flex; gap:8px; flex-direction: column;">
              <a class="btn btn-outline" href="{{ route('checkout.invoice.pdf', $invoiceId) }}" target="_blank" rel="noopener" style="width: 100%;">
                Descargar PDF
              </a>
              <a class="btn btn-outline" href="{{ route('checkout.invoice.xml', $invoiceId) }}" style="width: 100%;">
                Descargar XML
              </a>
            </div>
            <p class="muted" style="margin: 12px 0 0 0; font-size: 0.85rem; text-align: center;">Documentos con validez fiscal oficial.</p>

          {{-- Fallback: usa URLs guardadas en tu tabla ventas --}}
          @elseif($venta && ($facturaPDF || $facturaXML))
            <div style="display:flex; gap:8px; flex-direction: column;">
              @if($facturaPDF)
                <a class="btn btn-outline" href="{{ $facturaPDF }}" target="_blank" rel="noopener" style="width: 100%;">Descargar PDF</a>
              @endif
              @if($facturaXML)
                <a class="btn btn-outline" href="{{ $facturaXML }}" target="_blank" rel="noopener" style="width: 100%;">Descargar XML</a>
              @endif
            </div>
            <p class="muted" style="margin: 12px 0 0 0; font-size: 0.85rem; text-align: center;">Documentos con validez fiscal oficial.</p>

          {{-- Si ya hay folio/UUID pero aún no hay links, sugiere autorefresh --}}
          @elseif($venta && ($facturaId || $facturaUUID))
            <p class="muted" id="noFacturaYet" style="margin:0; font-size: 0.95rem; line-height: 1.5;">
              <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--blue); margin-right:6px; animation: pulse 1.5s infinite;"></span>
              Procesando timbrado del SAT. En breves segundos podrás descargar tus archivos aquí.
            </p>

          {{-- Mensaje neutro si no solicitó factura --}}
          @else
            <p class="muted" style="margin:0; font-size: 0.95rem; line-height: 1.5;">
              Si solicitaste factura durante el checkout, tus enlaces de descarga aparecerán en esta sección en breve.
            </p>
          @endif
        </div>
      </aside>
    </div>
  </div>
</div>

<style>
  @keyframes pulse {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(0.8); }
    100% { opacity: 1; transform: scale(1); }
  }
</style>

@push('scripts')
<script>
(() => {
  // Copiar ID de sesión
  const btn = document.getElementById('copyBtn');
  if (btn) {
    btn.addEventListener('click', async () => {
      const t = document.getElementById('sid')?.textContent || '';
      try { await navigator.clipboard.writeText(t); } catch {}
      const m = document.getElementById('copyMsg');
      if (m){ m.style.display='block'; setTimeout(()=>m.style.display='none', 2500); }
    });
  }

  // Autorefresh si estamos esperando timbrado (fallback Venta)
  if (document.getElementById('noFacturaYet')) {
    setTimeout(() => { window.location.reload(); }, 8000);
  }

  // Confetti liviano corporativo (Tonos azules y éxito)
  const cvs = document.getElementById('confetti');
  if (!cvs) return;
  const ctx = cvs.getContext('2d');
  const pieces = [];
  function resize(){ cvs.width = innerWidth; cvs.height = innerHeight; }
  addEventListener('resize', resize); resize();

  for (let i=0;i<100;i++){
    pieces.push({
      x: Math.random()*cvs.width,
      y: -20 - Math.random()*cvs.height,
      s: 4 + Math.random()*5,
      v: 1 + Math.random()*2.5,
      r: Math.random()*Math.PI,
      dr: (Math.random()-.5)*0.15
    });
  }
  let t0 = performance.now();
  function draw(t){
    const dt = (t - t0)/16; t0 = t;
    ctx.clearRect(0,0,cvs.width,cvs.height);
    pieces.forEach(p=>{
      p.y += p.v*dt; p.r += p.dr*dt; p.x += Math.sin(p.y*0.01)*0.5;
      if(p.y > cvs.height+20){ p.y = -20; p.x = Math.random()*cvs.width; }
      ctx.save(); ctx.translate(p.x,p.y); ctx.rotate(p.r);
      // Paleta corporativa: Azules, verdes, platas
      const palette = ['#007aff','#e6f0ff','#15803d','#e6ffe6','#ebebeb'];
      ctx.fillStyle = palette[(p.s|0) % palette.length];
      ctx.fillRect(-p.s/2,-p.s/2,p.s,p.s*0.6);
      ctx.restore();
    });
    requestAnimationFrame(draw);
  }
  requestAnimationFrame(draw);
})();
</script>
@endpush
@endsection
{{-- force_clear_front_cart_after_payment --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  ['cart', 'checkout.cart', 'cart.items', 'cart_count', 'cart_totals', 'shopping_cart'].forEach(function (key) {
    localStorage.removeItem(key);
    sessionStorage.removeItem(key);
  });

  document.querySelectorAll('[data-cart-count], .cart-count, #cart-count, .cart-badge').forEach(function (el) {
    el.textContent = '0';
  });
});
</script>
