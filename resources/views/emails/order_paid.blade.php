@component('mail::message')
# ¡Gracias por tu compra!

**Pedido:** #{{ $order->id }}  
**Total:** ${{ number_format($order->total,2) }} MXN  
**Estado:** {{ strtoupper($order->status) }}

@component('mail::panel')
**Envío:** {{ $order->shipping_name ?? 'Paquetería' }} {{ $order->shipping_service ? "({$order->shipping_service})" : '' }}  
**ETA:** {{ $order->shipping_eta ?? '—' }}  
**Dirección:** {{ $order->customer_address }}
@endcomponent

**Artículos**
@component('mail::table')
| Producto | Cant. | Precio | Importe |
|:--|:--:|--:|--:|
@foreach($order->items as $it)
| {{ $it->name }} | {{ $it->qty }} | ${{ number_format($it->price,2) }} | ${{ number_format($it->amount,2) }} |
@endforeach
@endcomponent

Gracias,<br>
{{ config('app.name') }}
@endcomponent
