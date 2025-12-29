<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Movimiento #{{ $movement->id }}</title>
  <style>
    @page { margin: 24px 26px; }
    body{ font-family: DejaVu Sans, Arial, sans-serif; color:#0f172a; font-size:12px; }

    .top{ display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:14px; }
    .h1{ font-size:16px; font-weight:700; margin:0 0 4px; }
    .muted{ color:#64748b; }
    .box{ border:1px solid #e5e7eb; border-radius:10px; padding:10px; }
    .grid{ display:flex; gap:10px; }
    .col{ flex:1; }
    .kv{ width:100%; border-collapse:collapse; }
    .kv td{ padding:3px 0; vertical-align:top; }
    .k{ color:#64748b; width:160px; }
    .tbl{ width:100%; border-collapse:collapse; margin-top:10px; }
    .tbl th{ background:#f8fafc; text-align:left; border-bottom:1px solid #e5e7eb; padding:8px; font-weight:700; }
    .tbl td{ border-bottom:1px solid #eef2f7; padding:8px; vertical-align:top; }
    .right{ text-align:right; }

    .route{ margin-top:12px; }
    .route h3{ margin:0 0 6px; font-size:13px; }

    .pill{ display:inline-block; padding:3px 8px; border-radius:999px; font-weight:700; font-size:11px; border:1px solid #e5e7eb; }
    .in{ background:#dcfce7; color:#166534; border-color:#bbf7d0; }
    .out{ background:#fee2e2; color:#991b1b; border-color:#fecaca; }

    /* ✅ Firmas formales (centradas y compactas, PDF-safe) */
    .signs{ margin-top:18px; }
    .sign-wrap{
      width: 520px;           /* ancho fijo para verse formal */
      margin: 0 auto;         /* centrado */
    }
    .sign-table{ width:100%; border-collapse:collapse; margin-top:8px; }
    .sign-td{ width:50%; padding:0; vertical-align:top; }
    .sign-td.left{ padding-right:18px; }
    .sign-td.right{ padding-left:18px; }

    .sign-title{
      font-weight:700;
      font-size:11px;
      text-transform:uppercase;
      letter-spacing:.6px;
      color:#0f172a;
      text-align:center;
      margin-bottom:8px;
    }
    .sign-line{
      width:100%;
      border-top:1px solid #94a3b8;
      margin-top:36px; /* altura para firma */
    }
    .sign-small{
      font-size:10px;
      color:#64748b;
      text-align:center;
      margin-top:4px;
    }
  </style>
</head>
<body>

  <div class="top">
    <div>
      <div class="h1">Movimiento WMS #{{ $movement->id }}</div>
      <div class="muted">{{ $movement->created_at?->format('Y-m-d H:i:s') }}</div>
    </div>

    <div class="pill {{ $movement->type === 'in' ? 'in' : 'out' }}">
      {{ $movement->type === 'in' ? 'ENTRADA' : 'SALIDA' }}
    </div>
  </div>

  <div class="box">
    <div class="grid">
      <div class="col">
        <table class="kv">
          <tr><td class="k">Bodega</td><td><b>{{ $movement->warehouse->name ?? '—' }}</b></td></tr>
          <tr><td class="k">Registra</td><td>{{ $movement->user->name ?? '—' }}</td></tr>
          <tr><td class="k">Nota</td><td>{{ $movement->note ?? '—' }}</td></tr>
        </table>
      </div>
    </div>
  </div>

  <table class="tbl">
    <thead>
      <tr>
        <th style="width:36%">Producto</th>
        <th style="width:12%">SKU</th>
        <th style="width:14%">GTIN</th>
        <th style="width:18%">Ubicación</th>
        <th class="right" style="width:8%">Qty</th>
        <th class="right" style="width:12%">Stock (antes→después)</th>
      </tr>
    </thead>
    <tbody>
      @foreach($movement->lines as $l)
        <tr>
          <td>
            <b>{{ $l->item->name ?? '—' }}</b>
            <div class="muted">ID {{ $l->catalog_item_id }}</div>
          </td>
          <td>{{ $l->item->sku ?? '—' }}</td>
          <td>{{ $l->item->meli_gtin ?? '—' }}</td>
          <td>{{ $l->location->code ?? '—' }}</td>
          <td class="right"><b>{{ (int)$l->qty }}</b></td>
          <td class="right">{{ (int)$l->stock_before }} → {{ (int)$l->stock_after }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="route">
    <h3>Ruta sugerida</h3>
    <div class="muted">Ve por ubicaciones en este orden.</div>

    <table class="tbl">
      <thead>
        <tr>
          <th>Ubicación</th>
          <th class="right">Total</th>
          <th>Productos</th>
        </tr>
      </thead>
      <tbody>
        @foreach(($route ?? []) as $r)
          <tr>
            <td><b>{{ $r['code'] ?? '—' }}</b></td>
            <td class="right"><b>{{ (int)($r['total_qty'] ?? 0) }}</b></td>
            <td>
              @foreach(($r['lines'] ?? []) as $x)
                <div>• {{ $x['name'] ?? '—' }} ({{ (int)($x['qty'] ?? 0) }})</div>
              @endforeach
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="signs">
    <div class="muted">Firmas (físicas)</div>

    <div class="sign-wrap">
      <table class="sign-table">
        <tr>
          <td class="sign-td left">
            <div class="sign-title">Autoriza</div>
            <div class="sign-line"></div>
            <div class="sign-small">Nombre y firma</div>
          </td>

          <td class="sign-td right">
            <div class="sign-title">Recibió</div>
            <div class="sign-line"></div>
            <div class="sign-small">Nombre y firma</div>
          </td>
        </tr>
      </table>
    </div>
  </div>

</body>
</html>
