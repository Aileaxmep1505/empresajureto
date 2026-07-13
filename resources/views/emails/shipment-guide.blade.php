{{-- resources/views/emails/shipment-guide.blade.php --}}
@php
  $tracking = $shipment->tracking_number ?: 'Pendiente';
@endphp

<div style="font-family:Arial,sans-serif;color:#333;line-height:1.5">
  <h2 style="color:#111;margin-bottom:8px;">Tu guía de envío JURETO</h2>

  <p>Hola, tu pedido ya tiene guía de envío.</p>

  <ul>
    <li><strong>Paquetería:</strong> {{ strtoupper($shipment->carrier ?? 'Paquetería') }}</li>
    <li><strong>Servicio:</strong> {{ $shipment->service }}</li>
    <li><strong>Número de guía:</strong> {{ $tracking }}</li>
    <li><strong>Estatus:</strong> {{ $shipment->status_label }}</li>
  </ul>

  @if($shipment->tracking_url)
    <p>
      <a href="{{ $shipment->tracking_url }}" style="display:inline-block;background:#007aff;color:#fff;text-decoration:none;padding:10px 16px;border-radius:999px;font-weight:bold;">
        Rastrear envío
      </a>
    </p>
  @endif

  @if($shipment->label_url)
    <p>
      <a href="{{ $shipment->label_url }}">Descargar guía PDF</a>
    </p>
  @endif

  <p>Gracias por comprar en JURETO.</p>
</div>
