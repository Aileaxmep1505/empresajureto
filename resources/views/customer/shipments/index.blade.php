{{-- resources/views/customer/shipments/index.blade.php --}}
@extends('layouts.web')

@section('title','Mis envíos')

@section('content')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');
  :root{--bg:#f9fafb;--card:#fff;--ink:#111;--text:#333;--muted:#888;--line:#ebebeb;--blue:#007aff;--blue-soft:#e6f0ff;--success:#15803d;--success-soft:#e6ffe6;}
  .ship-page{max-width:1100px;margin:0 auto;padding:36px 20px;font-family:"Quicksand",system-ui,sans-serif;color:var(--text);}
  .ship-head{margin-bottom:22px;}
  .ship-head h1{margin:0;color:var(--ink);font-size:34px;font-weight:700;letter-spacing:-.03em;}
  .ship-head p{margin:8px 0 0;color:var(--muted);font-weight:500;}
  .ship-card{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 4px 12px rgba(0,0,0,.02);overflow:hidden;}
  .ship-row{display:grid;grid-template-columns:70px 1fr auto;gap:16px;align-items:center;padding:18px 20px;border-bottom:1px solid var(--line);text-decoration:none;color:inherit;transition:.18s ease;}
  .ship-row:hover{background:#f9fafb;transform:translateY(-1px);}
  .ship-row:last-child{border-bottom:0;}
  .ship-logo{width:64px;height:48px;border:1px solid var(--line);border-radius:12px;background:#fff;display:grid;place-items:center;}
  .ship-logo img{max-width:82%;max-height:30px;}
  .ship-title{font-weight:700;color:var(--ink);}
  .ship-meta{font-size:13px;color:var(--muted);font-weight:600;margin-top:3px;}
  .badge{display:inline-flex;border-radius:999px;padding:6px 10px;background:var(--blue-soft);color:var(--blue);font-size:12px;font-weight:700;}
  .empty{padding:34px;text-align:center;color:var(--muted);font-weight:600;}
</style>

<div class="ship-page">
  <div class="ship-head">
    <h1>Mis envíos</h1>
    <p>Consulta el estatus de tus pedidos y guías generadas.</p>
  </div>

  <div class="ship-card">
    @forelse($shipments as $shipment)
      @php
        $logo = $shipment->carrier_key ? $shipment->carrier_key.'.svg' : 'generic-shipping.svg';
      @endphp
      <a class="ship-row" href="{{ route('customer.shipments.show', $shipment) }}">
        <span class="ship-logo">
          <img src="{{ asset('images/carriers/'.$logo) }}" onerror="this.src='{{ asset('images/carriers/generic-shipping.svg') }}'" alt="{{ $shipment->carrier }}">
        </span>
        <span>
          <span class="ship-title">{{ strtoupper($shipment->carrier ?? 'Paquetería') }} · {{ $shipment->service }}</span>
          <span class="ship-meta">Guía: {{ $shipment->tracking_number ?: 'Pendiente' }} · {{ $shipment->created_at?->format('d/m/Y H:i') }}</span>
        </span>
        <span class="badge">{{ $shipment->status_label ?: 'Guía generada' }}</span>
      </a>
    @empty
      <div class="empty">Aún no tienes envíos registrados.</div>
    @endforelse
  </div>

  <div style="margin-top:18px;">
    {{ $shipments->links() }}
  </div>
</div>
@endsection
