{{-- resources/views/customer/shipments/show.blade.php --}}
@extends('layouts.web')

@section('title','Estatus del envío')

@section('content')
@php
  $logo = $shipment->carrier_key ? $shipment->carrier_key.'.svg' : 'generic-shipping.svg';
@endphp

<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');
  :root{--bg:#f9fafb;--card:#fff;--ink:#111;--text:#333;--muted:#888;--line:#ebebeb;--blue:#007aff;--blue-soft:#e6f0ff;--success:#15803d;--success-soft:#e6ffe6;}
  .ship-page{max-width:900px;margin:0 auto;padding:36px 20px;font-family:"Quicksand",system-ui,sans-serif;color:var(--text);}
  .card{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 4px 12px rgba(0,0,0,.02);padding:24px;}
  .head{display:flex;gap:18px;align-items:center;margin-bottom:24px;}
  .logo{width:76px;height:56px;border:1px solid var(--line);border-radius:14px;background:#fff;display:grid;place-items:center;flex:none;}
  .logo img{max-width:84%;max-height:34px;}
  h1{margin:0;color:var(--ink);font-size:30px;font-weight:700;letter-spacing:-.03em;}
  .muted{color:var(--muted);font-weight:600;margin-top:4px;}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:18px;}
  @media(max-width:700px){.grid{grid-template-columns:1fr}}
  .box{border:1px solid var(--line);border-radius:12px;padding:16px;background:#fff;}
  .label{font-size:12px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px;}
  .value{font-weight:700;color:var(--ink);word-break:break-word;}
  .badge{display:inline-flex;border-radius:999px;padding:8px 12px;background:var(--blue-soft);color:var(--blue);font-weight:700;}
  .btn{display:inline-flex;align-items:center;justify-content:center;border-radius:999px;padding:10px 18px;font-weight:700;text-decoration:none;border:0;cursor:pointer;font-family:inherit;transition:.16s ease;}
  .btn:active{transform:scale(.98)}
  .btn-primary{background:var(--blue);color:#fff;}
  .btn-outline{background:#fff;color:var(--blue);border:1px solid var(--blue);}
  .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:22px;}
</style>

<div class="ship-page">
  <div class="card">
    <div class="head">
      <span class="logo">
        <img src="{{ asset('images/carriers/'.$logo) }}" onerror="this.src='{{ asset('images/carriers/generic-shipping.svg') }}'" alt="{{ $shipment->carrier }}">
      </span>
      <div>
        <h1>{{ strtoupper($shipment->carrier ?? 'Paquetería') }}</h1>
        <div class="muted">{{ $shipment->service }} · {{ $shipment->created_at?->format('d/m/Y H:i') }}</div>
      </div>
    </div>

    <span class="badge" id="statusLabel">{{ $shipment->status_label ?: 'Guía generada' }}</span>

    <div class="grid">
      <div class="box">
        <div class="label">Número de guía</div>
        <div class="value">{{ $shipment->tracking_number ?: 'Pendiente' }}</div>
      </div>
      <div class="box">
        <div class="label">Pedido</div>
        <div class="value">{{ $shipment->order_id ? '#'.$shipment->order_id : 'Sin pedido vinculado' }}</div>
      </div>
      <div class="box">
        <div class="label">Costo</div>
        <div class="value">${{ number_format((float)$shipment->price, 2) }} {{ $shipment->currency }}</div>
      </div>
      <div class="box">
        <div class="label">Última actualización</div>
        <div class="value" id="lastTracked">{{ $shipment->last_tracked_at ? $shipment->last_tracked_at->format('d/m/Y H:i') : 'No consultado' }}</div>
      </div>
    </div>

    <div class="actions">
      @if($shipment->tracking_url)
        <a class="btn btn-primary" href="{{ $shipment->tracking_url }}" target="_blank" rel="noopener">Rastrear en paquetería</a>
      @endif

      @if($shipment->label_url)
        <a class="btn btn-outline" href="{{ $shipment->label_url }}" target="_blank" rel="noopener">Descargar guía</a>
      @endif

      <button class="btn btn-outline" type="button" id="btnRefresh">Actualizar estatus</button>
    </div>
  </div>
</div>

<script>
(function(){
  const btn = document.getElementById('btnRefresh');
  const statusLabel = document.getElementById('statusLabel');
  const lastTracked = document.getElementById('lastTracked');
  const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

  btn?.addEventListener('click', async () => {
    btn.disabled = true;
    btn.textContent = 'Actualizando...';

    try {
      const response = await fetch(@json(route('customer.shipments.refresh', $shipment)), {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': token
        }
      });

      const data = await response.json();

      if(data.ok && data.shipment){
        statusLabel.textContent = data.shipment.status_label || 'Actualizado';
        lastTracked.textContent = 'Ahora';
      } else {
        alert(data.error || 'No se pudo actualizar.');
      }
    } catch(e) {
      alert('No se pudo actualizar el estatus.');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Actualizar estatus';
    }
  });
})();
</script>
@endsection
