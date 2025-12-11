<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de productos</title>
    <style>
        @page { margin: 20mm 15mm; }
        * { box-sizing:border-box; }
        body{
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 11px;
            color:#111827;
        }
        .header{
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            margin-bottom:14px;
            border-bottom:1px solid #e5e7eb;
            padding-bottom:8px;
        }
        .title{
            font-size:16px;
            font-weight:700;
            letter-spacing:-0.02em;
            margin:0;
        }
        .subtitle{
            font-size:11px;
            color:#6b7280;
            margin-top:2px;
        }
        .meta{
            text-align:right;
            font-size:10px;
            color:#6b7280;
        }
        table{
            width:100%;
            border-collapse:collapse;
            margin-top:6px;
        }
        th, td{
            padding:4px 6px;
            border-bottom:1px solid #e5e7eb;
        }
        th{
            font-size:10px;
            text-align:left;
            background:#f9fafb;
            font-weight:600;
        }
        tbody tr:nth-child(even){
            background:#fdfdfd;
        }
        .text-right{text-align:right;}
        .text-muted{color:#6b7280;}
        .sku{
            font-family: ui-monospace, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size:10px;
        }
    </style>
</head>
<body>
<div class="header">
    <div>
        <h1 class="title">Listado de productos</h1>
        <p class="subtitle">
            Resumen general del catálogo
            @isset($totalCount)
                <br>
                @php $shown = isset($products) ? $products->count() : 0; @endphp
                Mostrando {{ $shown }} de {{ $totalCount }} productos.
            @endisset
        </p>
    </div>
    <div class="meta">
        @if(!empty($q))
            <div>Filtro: "{{ $q }}"</div>
        @endif
        <div>Generado: {{ $generated_at->format('d/m/Y H:i') }}</div>
    </div>
</div>

<table>
    <thead>
    <tr>
        <th style="width:35px;">ID</th>
        <th>Nombre</th>
        <th style="width:90px;">SKU</th>
        <th style="width:75px;">Marca</th>
        <th style="width:80px;">Categoría</th>
        <th style="width:55px;" class="text-right">Costo</th>
        <th style="width:55px;" class="text-right">Precio</th>
        <th style="width:70px;">Unidad</th>
        <th style="width:70px;">Color</th>
        <th style="width:70px;">Clave SAT</th>
    </tr>
    </thead>
    <tbody>
    @foreach($products as $p)
        <tr>
            <td>{{ $p->id }}</td>
            <td>
                <strong>{{ $p->name }}</strong>
            </td>
            <td class="sku">{{ $p->sku }}</td>
            <td>{{ $p->brand }}</td>
            <td>{{ $p->category }}</td>
            <td class="text-right">
                @if(!is_null($p->cost))
                    ${{ number_format((float)$p->cost, 2) }}
                @endif
            </td>
            <td class="text-right">
                @if(!is_null($p->price))
                    ${{ number_format((float)$p->price, 2) }}
                @endif
            </td>
            <td>{{ $p->unit }}</td>
            <td>{{ $p->color }}</td>
            <td>{{ $p->clave_sat }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
