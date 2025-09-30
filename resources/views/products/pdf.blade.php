<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Productos</title>
  <style>
    @page { margin: 20px; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color:#0f172a }
    h1{ font-size:20px; margin:0 0 6px }
    .sub{ color:#64748b; font-size:12px; margin-bottom:10px }
    table{ width:100%; border-collapse: collapse }
    th, td{ border:1px solid #e5e7eb; padding:6px 8px; vertical-align:top }
    th{ background:#eef2ff; text-align:left }
    .muted{ color:#6b7280; font-size:11px }
  </style>
</head>
<body>
  <h1>Listado de productos</h1>
  <div class="sub">
    Generado: {{ $now->format('Y-m-d H:i') }}
    @if($q) &nbsp;|&nbsp; Filtro: "{{ $q }}" @endif
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:22%">Nombre</th>
        <th style="width:10%">SKU</th>
        <th style="width:12%">SKU Prov.</th>
        <th style="width:10%">Marca</th>
        <th style="width:12%">Categoría</th>
        <th style="width:8%">Precio</th>
        <th style="width:8%">Activo</th>
        <th>Etiquetas</th>
      </tr>
    </thead>
    <tbody>
      @forelse($items as $p)
      <tr>
        <td>
          <div><strong>{{ $p->name ?: '—' }}</strong></div>
          @if($p->description)<div class="muted">{{ \Illuminate\Support\Str::limit($p->description, 80) }}</div>@endif
        </td>
        <td>{{ $p->sku }}</td>
        <td>{{ $p->supplier_sku }}</td>
        <td>{{ $p->brand }}</td>
        <td>{{ $p->category }}</td>
        <td>${{ number_format((float)($p->price ?? 0),2) }}</td>
        <td>{{ $p->active ? 'Sí' : 'No' }}</td>
        <td>{{ $p->tags }}</td>
      </tr>
      @empty
      <tr><td colspan="8" style="text-align:center; color:#6b7280">Sin resultados</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
