<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Recolección virtual {{ $task['task_number'] }}</title>
  <style>
    body{font-family:DejaVu Sans,Arial,sans-serif;color:#111;margin:28px;font-size:12px;}
    h1{font-size:22px;margin:0 0 6px;} h2{font-size:15px;margin:24px 0 8px;} p{margin:0 0 4px;}
    .muted{color:#666}.grid{display:table;width:100%;margin-top:16px}.cell{display:table-cell;width:25%;border:1px solid #ddd;padding:10px}.label{font-size:10px;text-transform:uppercase;color:#666}.value{font-size:18px;font-weight:bold;margin-top:4px}
    table{width:100%;border-collapse:collapse;margin-top:8px} th,td{border:1px solid #ddd;padding:8px;text-align:left;vertical-align:top} th{background:#f6f6f6;font-size:10px;text-transform:uppercase;color:#555}.badge{display:inline-block;padding:4px 8px;border-radius:999px;background:#e6f0ff;color:#007aff;font-weight:bold;font-size:10px}.ok{background:#e6ffe6;color:#15803d}.danger{background:#ffebeb;color:#ff4a4a}.signatures{display:table;width:100%;margin-top:50px}.sig{display:table-cell;width:50%;padding:0 20px;text-align:center}.line{border-top:1px solid #111;padding-top:8px}.no-print{margin-bottom:16px}@media print{.no-print{display:none}}
  </style>
</head>
<body>
  @empty($isPdf)<div class="no-print"><button onclick="window.print()">Imprimir / Guardar PDF</button></div>@endempty
  <h1>Reporte de recolección virtual</h1>
  <p><strong>Tarea:</strong> {{ $task['task_number'] }} · <strong>Pedido:</strong> {{ $task['order_number'] ?: '—' }}</p>
  <p><strong>Almacén:</strong> {{ $task['warehouse_name'] }} · <strong>Recolector:</strong> {{ $task['collector_name'] ?: '—' }}</p>
  <p><strong>Fecha:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>

  <div class="grid">
    <div class="cell"><div class="label">Físico en scanner</div><div class="value">{{ number_format($task['physical_total']) }}</div></div>
    <div class="cell"><div class="label">Virtual requerido</div><div class="value">{{ number_format($task['virtual_total']) }}</div></div>
    <div class="cell"><div class="label">Virtual recolectado</div><div class="value">{{ number_format($task['virtual_collected']) }}</div></div>
    <div class="cell"><div class="label">Matches</div><div class="value">{{ number_format(count($matches)) }}</div></div>
  </div>

  <h2>Resumen vinculado físico + virtual</h2>
  <table>
    <thead><tr><th>Producto</th><th>SKU</th><th>Match</th><th>Scanner</th><th>Virtual</th><th>Total</th></tr></thead>
    <tbody>
      @forelse($matches as $match)
        <tr><td>{{ $match['product_name'] }}</td><td>{{ $match['sku'] ?: '—' }}</td><td>{{ $match['fulfillment_group_id'] }}</td><td>{{ number_format($match['physical_qty']) }}</td><td>{{ number_format($match['virtual_qty']) }}</td><td>{{ number_format($match['total_qty']) }}</td></tr>
      @empty
        <tr><td colspan="6">Sin matches.</td></tr>
      @endforelse
    </tbody>
  </table>

  <h2>Detalle de checklist virtual</h2>
  <table>
    <thead><tr><th>Producto</th><th>SKU</th><th>Requerido</th><th>Recolectado</th><th>Estado</th><th>Nota</th></tr></thead>
    <tbody>
      @forelse($virtualItems as $item)
        @php
          $required = max(1, (int)($item['quantity_required'] ?? 1));
          $collected = max(0, (int)($item['quantity_collected'] ?? $item['quantity_picked'] ?? 0));
          $status = strtolower((string)($item['pickup_status'] ?? 'pending'));
          $cls = in_array($status, ['collected','staged'], true) ? 'ok' : (in_array($status, ['pending','not_collected'], true) ? 'danger' : '');
        @endphp
        <tr>
          <td>{{ $item['product_name'] ?? 'Producto virtual' }}</td>
          <td>{{ $item['product_sku'] ?? '—' }}</td>
          <td>{{ number_format($required) }}</td>
          <td>{{ number_format($collected) }}</td>
          <td><span class="badge {{ $cls }}">{{ $status }}</span></td>
          <td>{{ $item['pickup_checklist_note'] ?? $item['pickup_notes'] ?? '' }}</td>
        </tr>
      @empty
        <tr><td colspan="6">Sin líneas virtuales.</td></tr>
      @endforelse
    </tbody>
  </table>

  <h2>Notas generales</h2>
  <p>{{ $task['virtual_pickup_notes'] ?: 'Sin notas.' }}</p>

  <div class="signatures">
    <div class="sig"><div class="line">Recolector</div></div>
    <div class="sig"><div class="line">Recibe / valida</div></div>
  </div>
</body>
</html>
