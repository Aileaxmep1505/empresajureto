@extends('layouts.app')

@section('title', 'WMS · Checklist recolección virtual')
@section('content_class', 'content--flush')

@section('content')
@php
  $hasActiveShipment = (bool) data_get($task, 'has_active_shipment', false);
  $shipmentNumber = (string) data_get($task, 'shipment_number', '');
  $shipmentStatus = (string) data_get($task, 'shipment_status', '');
  $receptionCreateUrl = \Illuminate\Support\Facades\Route::has('admin.wms.receptions.create')
      ? route('admin.wms.receptions.create')
      : url('/admin/wms/receptions/create');
@endphp
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');
  :root{--bg:#f9fafb;--card:#ffffff;--ink:#333333;--muted:#888888;--line:#ebebeb;--blue:#007aff;--blue-soft:#e6f0ff;--success:#15803d;--success-soft:#e6ffe6;--danger:#ff4a4a;--danger-soft:#ffebeb;--warning:#f59e0b;--warning-soft:#fef3c7;}
  .vs-page{min-height:100vh;background:var(--bg);font-family:'Quicksand',system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:var(--ink);padding:28px;}
  .vs-shell{max-width:1380px;margin:0 auto;}
  .vs-header{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:22px;}
  .vs-eyebrow{display:inline-flex;width:fit-content;padding:7px 12px;border-radius:999px;background:var(--blue-soft);color:var(--blue);font-size:12px;font-weight:700;margin-bottom:12px;}
  .vs-title{margin:0;color:#111;font-size:30px;line-height:1.1;letter-spacing:-.03em;font-weight:700;}
  .vs-subtitle{margin:10px 0 0;color:var(--muted);font-size:15px;line-height:1.55;font-weight:600;max-width:820px;}
  .vs-card{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 4px 12px rgba(0,0,0,.02);transition:transform .18s ease,box-shadow .18s ease;}
  .vs-card:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(0,0,0,.04);}
  .vs-btn{border:0;outline:0;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:40px;padding:10px 16px;border-radius:999px;font-size:13px;font-weight:700;text-decoration:none;transition:transform .18s ease,box-shadow .18s ease,background .18s ease;white-space:nowrap;font-family:'Quicksand',sans-serif;}
  .vs-btn:active{transform:scale(.98);}.vs-btn-primary{background:var(--blue);color:#fff;box-shadow:0 4px 12px rgba(0,122,255,.14);}.vs-btn-primary:hover{color:#fff;transform:translateY(-1px);box-shadow:0 8px 20px rgba(0,122,255,.16);}.vs-btn-outline{background:#fff;border:1px solid var(--blue);color:var(--blue);}.vs-btn-outline:hover{background:var(--blue-soft);color:var(--blue);transform:translateY(-1px);}.vs-btn-ghost{background:transparent;color:#555;}.vs-btn-ghost:hover{background:#f9fafb;color:#111;transform:translateY(-1px);}
  .vs-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:flex-end;}
  .vs-grid{display:grid;grid-template-columns:1fr 400px;gap:18px;align-items:start;}
  .vs-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px;}
  .vs-stat{padding:18px;}.vs-stat-label{color:var(--muted);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;}.vs-stat-value{color:#111;font-size:24px;font-weight:700;letter-spacing:-.03em;line-height:1;}
  .vs-panel-head{padding:18px 20px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;gap:14px;align-items:center;}
  .vs-panel-title{margin:0;color:#111;font-size:18px;font-weight:700;}.vs-panel-body{padding:18px 20px;}
  .vs-field{margin-bottom:14px;}.vs-field label{display:block;margin-bottom:7px;font-size:12px;font-weight:700;color:#555;}.vs-input,.vs-select,.vs-textarea{width:100%;background:#fff;border:1px solid var(--line);border-radius:8px;padding:10px 12px;font-size:14px;font-weight:600;color:var(--ink);outline:none;transition:border-color .18s ease,box-shadow .18s ease;font-family:'Quicksand',sans-serif;}.vs-textarea{min-height:88px;resize:vertical;}.vs-input:focus,.vs-select:focus,.vs-textarea:focus{border-color:var(--blue);box-shadow:0 0 0 3px var(--blue-soft);}
  .vs-line{padding:16px;border-bottom:1px solid var(--line);display:grid;grid-template-columns:1fr 135px 170px 170px 1fr;gap:12px;align-items:start;}.vs-line:last-child{border-bottom:0;}
  .vs-product{color:#111;font-weight:700;font-size:14px;line-height:1.35;}.vs-muted{color:var(--muted);font-size:12px;font-weight:600;line-height:1.45;}.vs-strong{color:#111;font-weight:700;}.vs-badge{display:inline-flex;align-items:center;width:fit-content;gap:6px;padding:7px 11px;border-radius:999px;font-size:12px;font-weight:700;line-height:1;white-space:nowrap;}.vs-badge-info{color:var(--blue);background:var(--blue-soft);}.vs-badge-success{color:var(--success);background:var(--success-soft);}.vs-badge-danger{color:var(--danger);background:var(--danger-soft);}.vs-badge-warning{color:var(--warning);background:var(--warning-soft);}
  .vs-sold{margin-top:10px;padding:10px 12px;border-radius:12px;background:var(--danger-soft);color:var(--danger);font-size:12px;font-weight:800;line-height:1.4;}
  .vs-mode-help{margin-top:8px;padding:10px 12px;border-radius:12px;background:#f9fafb;border:1px solid var(--line);color:var(--muted);font-size:12px;font-weight:600;line-height:1.45;}
  .vs-match{padding:14px;border:1px solid var(--line);border-radius:12px;margin-bottom:12px;background:#fff;}.vs-match-title{color:#111;font-size:13px;font-weight:700;margin-bottom:8px;}.vs-match-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;}.vs-match-box{background:#f9fafb;border:1px solid var(--line);border-radius:10px;padding:10px;}.vs-match-label{color:var(--muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;}.vs-match-value{color:#111;font-size:16px;font-weight:700;margin-top:4px;}
  .vs-alert{padding:14px 16px;border-radius:12px;margin-bottom:16px;font-weight:700;}.vs-alert-ok{border:1px solid var(--success-soft);background:var(--success-soft);color:var(--success);}.vs-alert-error{border:1px solid var(--danger-soft);background:var(--danger-soft);color:var(--danger);}
  .vs-footer{position:sticky;bottom:0;background:rgba(249,250,251,.9);backdrop-filter:blur(8px);padding:14px 0;margin-top:18px;display:flex;justify-content:flex-end;gap:10px;}
  @media(max-width:1180px){.vs-page{padding:18px}.vs-header{flex-direction:column}.vs-grid{grid-template-columns:1fr}.vs-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.vs-line{grid-template-columns:1fr}}
</style>

<div class="vs-page">
  <div class="vs-shell">
    @if(session('ok'))<div class="vs-alert vs-alert-ok">{{ session('ok') }}</div>@endif
    @if($errors->any())<div class="vs-alert vs-alert-error">{{ $errors->first() }}</div>@endif

    @if($hasActiveShipment)
      <div class="vs-alert vs-alert-ok">
        Este picking ya tiene embarque activo{{ $shipmentNumber !== '' ? ' (' . $shipmentNumber . ')' : '' }}.
        Las piezas virtuales completas se marcarán automáticamente como <strong>entrega directa</strong> y se cargarán al embarque sin pedir escaneo.
      </div>
    @else
      <div class="vs-alert vs-alert-error" style="background:#fff7e6;color:#b45309;border-color:#fde68a;">
        Este picking todavía no tiene embarque. Aquí solo confirma la recolección.
        Si el recolector trae el producto a almacén, regístralo desde <strong>Recepciones</strong> como vendido / no inventariar e indica ahí dónde se dejó.
      </div>
    @endif

    <div class="vs-header">
      <div>
        <div class="vs-eyebrow">Checklist del recolector</div>
        <h1 class="vs-title">{{ $task['task_number'] }} · Recolección virtual</h1>
        <p class="vs-subtitle">
          Pedido: {{ $task['order_number'] ?: '—' }} · Almacén: {{ $task['warehouse_name'] }}.
          Estas piezas ya están vendidas para esta orden/picking; no deben entrar a inventario libre.
        </p>
      </div>
      <div class="vs-actions">
        <a href="{{ route('admin.wms.virtual-pickups.index') }}" class="vs-btn vs-btn-ghost">Volver</a>
        <a href="{{ route('admin.wms.virtual-pickups.pdf', $pickWave) }}" class="vs-btn vs-btn-outline">Descargar PDF</a>
      </div>
    </div>

    <div class="vs-summary">
      <div class="vs-card vs-stat"><div class="vs-stat-label">Físico en scanner</div><div class="vs-stat-value">{{ number_format($task['physical_total']) }}</div></div>
      <div class="vs-card vs-stat"><div class="vs-stat-label">Virtual requerido</div><div class="vs-stat-value">{{ number_format($task['virtual_total']) }}</div></div>
      <div class="vs-card vs-stat"><div class="vs-stat-label">Virtual recolectado</div><div class="vs-stat-value">{{ number_format($task['virtual_collected']) }}</div></div>
      <div class="vs-card vs-stat"><div class="vs-stat-label">Matches</div><div class="vs-stat-value">{{ number_format(count($matches)) }}</div></div>
    </div>

    <div class="vs-grid">
      <form method="POST" action="{{ route('admin.wms.virtual-pickups.checklist', $pickWave) }}" class="vs-card">
        @csrf
        <div class="vs-panel-head">
          <h2 class="vs-panel-title">Checklist de productos virtuales vendidos</h2>
          <span class="vs-badge vs-badge-info">{{ count($virtualItems) }} líneas</span>
        </div>

        <div class="vs-panel-body">
          <div class="vs-field">
            <label>Nombre del recolector / chofer</label>
            <input class="vs-input" type="text" name="collector_name" value="{{ old('collector_name', $task['collector_name'] ?: auth()->user()?->name) }}" placeholder="Nombre de quien recolecta">
          </div>
          <div class="vs-field">
            <label>Notas generales</label>
            <textarea class="vs-textarea" name="general_notes" placeholder="Comentarios generales de la recolección">{{ old('general_notes', $task['virtual_pickup_notes']) }}</textarea>
          </div>
        </div>

        @forelse($virtualItems as $i => $item)
          @php
            $required = max(1, (int)($item['quantity_required'] ?? 1));
            $collected = max(0, (int)($item['quantity_collected'] ?? $item['quantity_picked'] ?? 0));
            $status = strtolower((string)($item['pickup_status'] ?? 'pending'));
            $flowMode = strtolower((string)($item['virtual_flow_mode'] ?? 'staging_before_shipping'));
            if (!in_array($flowMode, ['direct_to_delivery','staging_before_shipping'], true)) $flowMode = 'staging_before_shipping';
            $stagingCode = (string)($item['staging_location_code'] ?? 'PICKING');
            $soldQtyForReception = max(0, (int)($item['virtual_sold_quantity'] ?? $item['sold_quantity'] ?? $item['quantity_collected'] ?? $item['quantity_picked'] ?? 0));
            $receptionLineUrl = $receptionCreateUrl . '?' . http_build_query([
              'is_virtual_sold' => 1,
              'no_inventory' => 1,
              'source_type' => 'virtual_sold',
              'source' => 'virtual_pickup_sold',
              'pick_wave_id' => $pickWave->id,
              'line_id' => $item['line_id'] ?? '',
              'virtual_pick_line_id' => $item['line_id'] ?? '',
              'task_number' => $task['task_number'] ?? '',
              'order_number' => $task['order_number'] ?? '',
              'fulfillment_group_id' => $item['fulfillment_group_id'] ?? ($item['line_id'] ?? ''),
              'catalog_item_id' => $item['product_id'] ?? '',
              'product_sku' => $item['product_sku'] ?? '',
              'product_name' => $item['product_name'] ?? '',
              'quantity_collected' => $soldQtyForReception,
              'sold_quantity' => $soldQtyForReception,
              'virtual_sold_quantity' => $soldQtyForReception,
              'virtual_flow_mode' => 'staging_before_shipping',
              'staging_location_code' => $stagingCode ?: 'PICKING',
              'sold_label' => 'VENDIDO / NO INVENTARIAR',
            ]);
            $badgeClass = in_array($status, ['collected','staged'], true) ? 'success' : (in_array($status, ['pending','not_collected'], true) ? 'danger' : 'info');
          @endphp
          <div class="vs-line">
            <div>
              <div class="vs-product">{{ $item['product_name'] ?? 'Producto virtual' }}</div>
              <div class="vs-muted">SKU: {{ $item['product_sku'] ?? '—' }} · Match: {{ $item['fulfillment_group_id'] ?? $item['line_id'] }}</div>
              <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap;">
                <span class="vs-badge vs-badge-{{ $badgeClass }}">{{ $status ?: 'pending' }}</span>
                <span class="vs-badge vs-badge-warning">Vendido</span>
              </div>
              <div class="vs-sold">
                VENDIDO / NO INVENTARIAR<br>
                Orden: {{ $task['order_number'] ?: '—' }} · Picking: {{ $task['task_number'] }}
              </div>
              <input type="hidden" name="lines[{{ $i }}][line_id]" value="{{ $item['line_id'] }}">
            </div>

            <div>
              <label class="vs-muted">Cantidad</label>
              <input class="vs-input" type="number" min="0" max="{{ $required }}" name="lines[{{ $i }}][quantity_collected]" value="{{ old("lines.$i.quantity_collected", $collected) }}">
              <div class="vs-muted" style="margin-top:6px;">Requerido: {{ number_format($required) }}</div>
            </div>

            <div>
              <label class="vs-muted">Estado</label>
              <select class="vs-select" name="lines[{{ $i }}][status]">
                <option value="pending" @selected($status === 'pending')>Pendiente</option>
                <option value="collected" @selected($status === 'collected')>Completo</option>
                <option value="partial" @selected($status === 'partial')>Parcial</option>
                <option value="not_collected" @selected($status === 'not_collected')>No recolectado</option>
                <option value="staged" @selected($status === 'staged')>Dejado en staging</option>
              </select>
            </div>

            <div>
              <label class="vs-muted">Flujo operativo</label>
              @if($hasActiveShipment)
                <input type="hidden" name="lines[{{ $i }}][virtual_flow_mode]" value="direct_to_delivery">
                <div class="vs-sold" style="background:var(--success-soft);color:var(--success);">
                  ENTREGA DIRECTA AUTOMÁTICA<br>
                  Ya existe embarque{{ $shipmentNumber !== '' ? ': ' . $shipmentNumber : '' }}. Al guardar completo se suma al embarque sin escaneo.
                </div>
              @else
                <input type="hidden" name="lines[{{ $i }}][virtual_flow_mode]" value="pending_reception_or_delivery">
                <div class="vs-sold" style="background:#fff7e6;color:#b45309;">
                  SIN EMBARQUE ACTIVO<br>
                  Si vuelve al almacén, crea recepción vendida / no inventariar.
                </div>
                @if($soldQtyForReception > 0)
                  <a href="{{ $receptionLineUrl }}" class="vs-btn vs-btn-outline" style="margin-top:10px;width:100%;" target="_blank">
                    Abrir Recepciones para {{ number_format($soldQtyForReception) }} vendido(s)
                  </a>
                @else
                  <button type="button" class="vs-btn vs-btn-outline" style="margin-top:10px;width:100%;opacity:.55;cursor:not-allowed;" disabled>
                    Primero captura cantidad recolectada
                  </button>
                @endif
              @endif
              <input class="vs-input" style="margin-top:10px;" type="text" name="lines[{{ $i }}][note]" value="{{ old("lines.$i.note", $item['pickup_checklist_note'] ?? '') }}" placeholder="Nota de línea">
            </div>
          </div>
        @empty
          <div class="vs-panel-body"><div class="vs-muted">Esta tarea no tiene líneas virtuales.</div></div>
        @endforelse

        <div class="vs-footer">
          <button type="submit" name="action" value="checklist" class="vs-btn vs-btn-primary">Guardar checklist</button>
        </div>
      </form>

      <aside>
        <div class="vs-card" style="margin-bottom:18px;">
          <div class="vs-panel-head"><h2 class="vs-panel-title">Vinculación físico + virtual</h2></div>
          <div class="vs-panel-body">
            @forelse($matches as $match)
              <div class="vs-match">
                <div class="vs-match-title">{{ $match['product_name'] }} · {{ $match['sku'] ?: 'SKU —' }}</div>
                <div class="vs-muted" style="margin-bottom:8px;">Match: {{ $match['fulfillment_group_id'] }}</div>
                <div class="vs-match-grid">
                  <div class="vs-match-box"><div class="vs-match-label">Scanner</div><div class="vs-match-value">{{ number_format($match['physical_qty']) }}</div></div>
                  <div class="vs-match-box"><div class="vs-match-label">Virtual</div><div class="vs-match-value">{{ number_format($match['virtual_qty']) }}</div></div>
                  <div class="vs-match-box"><div class="vs-match-label">Total</div><div class="vs-match-value">{{ number_format($match['total_qty']) }}</div></div>
                </div>
              </div>
            @empty
              <div class="vs-muted">No hay matches todavía.</div>
            @endforelse
          </div>
        </div>

        <div class="vs-card">
          <div class="vs-panel-head"><h2 class="vs-panel-title">Regla operativa</h2></div>
          <div class="vs-panel-body">
            <p class="vs-muted">
              Si el picking ya tiene embarque activo, la recolección virtual completa se convierte automáticamente en entrega directa y se suma al embarque.
              Si todavía no existe embarque y el recolector trae el producto al almacén, ese movimiento debe capturarse en Recepciones como vendido / no inventariar.
            </p>
            <p class="vs-muted">
              La etiqueta debe decir: <strong>VENDIDO / NO INVENTARIAR</strong>, orden <strong>{{ $task['order_number'] ?: '—' }}</strong> y picking <strong>{{ $task['task_number'] }}</strong>.
            </p>
          </div>
        </div>
      </aside>
    </div>
  </div>
</div>

<script>
  // El flujo se decide automáticamente: embarque activo = entrega directa; sin embarque = recepción vendida si vuelve a almacén.
</script>
@endsection
