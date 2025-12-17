<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Propuesta {{ $propuesta->codigo }}</title>
    <style>
        @page { margin: 30px 40px; }
        body{
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color:#111827;
        }
        .brand{
            font-size:14px;
            font-weight:bold;
            letter-spacing:.05em;
            text-transform:uppercase;
        }
        .muted{ color:#6b7280; font-size:10px; }
        .title{
            font-size:16px;
            font-weight:bold;
            margin-top:10px;
            margin-bottom:4px;
        }
        .meta{
            margin-top:6px;
            font-size:10px;
        }
        .meta span{
            display:inline-block;
            margin-right:14px;
        }
        hr{
            border:0;
            border-top:1px solid #e5e7eb;
            margin:10px 0 14px;
        }
        table{
            width:100%;
            border-collapse:collapse;
            margin-top:8px;
        }
        thead th{
            font-size:10px;
            text-align:left;
            padding:6px 5px;
            border-bottom:1px solid #e5e7eb;
            background:#f3f4f6;
        }
        tbody td{
            padding:5px;
            border-bottom:1px solid #f3f4f6;
            vertical-align:top;
            font-size:9.5px;
        }
        .text-right{ text-align:right; }
        .text-center{ text-align:center; }
        .totals{
            margin-top:14px;
            width:100%;
            border-collapse:collapse;
        }
        .totals td{
            font-size:10px;
            padding:4px 5px;
        }
        .totals .label{
            text-align:right;
            color:#6b7280;
        }
        .totals .value{
            text-align:right;
            font-weight:bold;
        }
        .totals tr.total-row td{
            border-top:1px solid #e5e7eb;
            padding-top:6px;
            font-size:11px;
        }
        .section-title{
            margin-top:18px;
            font-size:12px;
            font-weight:bold;
        }
    </style>
</head>
<body>

    {{-- Encabezado --}}
    <div class="brand">{{ config('app.name', 'Jureto') }}</div>
    <div class="muted">Propuesta económica</div>

    <div class="title">{{ $propuesta->codigo }}</div>

    <div class="meta">
        <span><strong>Fecha:</strong> {{ optional($propuesta->fecha)->format('d/m/Y') }}</span>
        @if($propuesta->licitacion_id)
            <span><strong>Licitación:</strong> #{{ $propuesta->licitacion_id }}</span>
        @endif
        @if($propuesta->requisicion_id)
            <span><strong>Requisición:</strong> #{{ $propuesta->requisicion_id }}</span>
        @endif
        <span><strong>Moneda:</strong> {{ $propuesta->moneda ?? 'MXN' }}</span>
    </div>

    <hr>

    <div class="section-title">Detalle de productos ofertados</div>

    <table>
        <thead>
            <tr>
                <th style="width:25px;">#</th>
                <th style="width:22%;">Solicitado</th>
                <th style="width:28%;">Producto ofertado</th>
                <th style="width:40px;" class="text-right">Cant.</th>
                <th style="width:60px;" class="text-right">Unitario</th>
                <th style="width:40px;" class="text-right">Utl.%</th>
                <th style="width:65px;" class="text-right">Utilidad</th>
                <th style="width:75px;" class="text-right">Subt. + utl.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($propuesta->items as $i => $item)
                @php
                    $req  = $item->requestItem;
                    $prod = $item->product;

                    $qty   = (float)($item->cantidad_propuesta ?? ($req->cantidad ?? 0));
                    $unit  = $item->unidad_propuesta ?? ($prod->unit ?? '');
                    $pu    = (float)($item->precio_unitario ?? 0);
                    $moneda = $propuesta->moneda ?? 'MXN';

                    $utilPct   = (float)($item->utilidad_pct ?? 0);
                    $utilMonto = (float)($item->utilidad_monto ?? 0);
                    $subUtil   = (float)($item->subtotal_con_utilidad ?? (($item->subtotal ?? 0) + $utilMonto));
                @endphp
                <tr>
                    <td class="text-center">{{ $req->renglon ?? ($i+1) }}</td>
                    <td>
                        @if($req)
                            {{ $req->line_raw }}
                        @else
                            {{ $item->descripcion_raw }}
                        @endif
                    </td>
                    <td>
                        @if($prod)
                            <strong>{{ trim(($prod->sku ? $prod->sku.' - ' : '').$prod->name) }}</strong><br>
                            @if($prod->brand)
                                <span class="muted">Marca: {{ $prod->brand }}</span><br>
                            @endif
                            @if($unit)
                                <span class="muted">Unidad: {{ $unit }}</span>
                            @endif
                        @else
                            <span class="muted">Sin producto seleccionado</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @if($qty>0)
                            {{ number_format($qty, 0) }}
                        @endif
                    </td>
                    <td class="text-right">
                        @if($pu>0)
                            {{ $moneda }} {{ number_format($pu,2) }}
                        @endif
                    </td>
                    <td class="text-right">
                        @if($utilPct>0)
                            {{ number_format($utilPct,2) }}
                        @endif
                    </td>
                    <td class="text-right">
                        @if($utilMonto>0)
                            {{ $moneda }} {{ number_format($utilMonto,2) }}
                        @endif
                    </td>
                    <td class="text-right">
                        @if($subUtil>0)
                            {{ $moneda }} {{ number_format($subUtil,2) }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totales --}}
    @php
        $moneda = $propuesta->moneda ?? 'MXN';
        $subtotalBase = (float)($propuesta->subtotal_base ?? $propuesta->items->sum('subtotal'));
        $utilidadTotal = (float)($propuesta->utilidad_total ?? $propuesta->items->sum('utilidad_monto'));
        $subtotal = (float)$propuesta->subtotal;
        $iva = (float)$propuesta->iva;
        $total = (float)$propuesta->total;
    @endphp

    <table class="totals">
        <tr>
            <td class="label" style="width:80%;">Subtotal base:</td>
            <td class="value">{{ $moneda }} {{ number_format($subtotalBase,2) }}</td>
        </tr>
        <tr>
            <td class="label">Utilidad total:</td>
            <td class="value">{{ $moneda }} {{ number_format($utilidadTotal,2) }}</td>
        </tr>
        <tr>
            <td class="label">Subtotal + utilidad:</td>
            <td class="value">{{ $moneda }} {{ number_format($subtotal,2) }}</td>
        </tr>
        <tr>
            <td class="label">IVA:</td>
            <td class="value">{{ $moneda }} {{ number_format($iva,2) }}</td>
        </tr>
        <tr class="total-row">
            <td class="label"><strong>Total a pagar:</strong></td>
            <td class="value">{{ $moneda }} {{ number_format($total,2) }}</td>
        </tr>
    </table>

</body>
</html>
