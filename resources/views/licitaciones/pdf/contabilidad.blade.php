{{-- resources/views/licitaciones/pdf/contabilidad.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Estado financiero - Licitación #{{ $licitacion->id }}</title>
    <style>
        @page {
            size: letter;
            margin: 20mm 18mm 22mm 18mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #111827;
            margin: 0;
            padding: 0;
        }
        h1,h2,h3,h4,p { margin: 0; padding: 0; }

        .page {
            width: 100%;
        }
        .header {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .empresa {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6b7280;
        }
        .titulo {
            margin-top: 4px;
            font-size: 18px;
            font-weight: 700;
        }
        .subtitulo {
            margin-top: 2px;
            font-size: 11px;
            color: #6b7280;
        }

        .grid-2 {
            width: 100%;
            margin-top: 10px;
        }
        .grid-2 td {
            vertical-align: top;
            width: 50%;
        }

        .box {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 8px;
        }
        .box-title {
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .box-sub {
            font-size: 10px;
            color: #6b7280;
            margin-bottom: 6px;
        }

        table.simple {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        table.simple th,
        table.simple td {
            padding: 4px 5px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10.5px;
        }
        table.simple th {
            background: #f3f4f6;
            text-align: left;
            font-weight: 600;
        }
        table.simple td.monto {
            text-align: right;
            white-space: nowrap;
        }

        .tag {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 10px;
            border: 1px solid #d1fae5;
            background: #ecfdf3;
            color: #166534;
        }
        .tag.neg {
            border-color: #fee2e2;
            background: #fef2f2;
            color: #b91c1c;
        }

        .mt-4 { margin-top: 4px; }
        .mt-6 { margin-top: 6px; }
        .mt-10{ margin-top: 10px; }

        .small {
            font-size: 10px;
            color: #6b7280;
        }
    </style>
</head>
<body>
@php
    /** @var \App\Models\LicitacionContabilidad|null $contabilidad */

    $cont = $contabilidad ?? null;
    $dc = $cont && is_array($cont->detalle_costos ?? null) ? $cont->detalle_costos : [];

    $num = function($arr, $key) {
        if (!is_array($arr) || !array_key_exists($key, $arr)) return 0.0;
        return is_numeric($arr[$key]) ? (float)$arr[$key] : 0.0;
    };

    $montoLicitado   = $cont ? (float)($cont->monto_inversion_estimado ?? 0) : 0.0;
    $gastoProductos  = $num($dc, 'productos');

    $keysOperativos = [
        'renta',
        'luz',
        'agua',
        'nominas',
        'imss',
        'gasolina',
        'viaticos',
        'casetas',
        'pagos_gobierno',
        'mantenimiento_camionetas',
        'libre_1',
        'libre_2',
    ];

    $gastosOperativos = 0.0;
    foreach ($keysOperativos as $k) {
        $gastosOperativos += $num($dc, $k);
    }

    $costoTotal = $gastoProductos + $gastosOperativos;
    $utilidad   = $montoLicitado - $costoTotal;
    $margen     = $montoLicitado > 0 ? ($utilidad / $montoLicitado) * 100 : 0;

    $libre1Label = $dc['libre_1_label'] ?? 'Otro gasto 1';
    $libre2Label = $dc['libre_2_label'] ?? 'Otro gasto 2';
@endphp

<div class="page">
    {{-- ENCABEZADO --}}
    <div class="header">
        <div class="empresa">Estado financiero de licitación</div>
        <div class="titulo">
            Licitación #{{ $licitacion->id }} &mdash; {{ $licitacion->titulo }}
        </div>
        <div class="subtitulo">
            Resultado: {{ $licitacion->resultado === 'ganado' ? 'GANADA' : strtoupper($licitacion->resultado ?? 'N/D') }}
            @if($licitacion->fecha_fallo)
                &nbsp;|&nbsp; Fecha fallo: {{ \Carbon\Carbon::parse($licitacion->fecha_fallo)->format('d/m/Y') }}
            @endif
        </div>
    </div>

    {{-- RESUMEN SUPERIOR --}}
    <table class="grid-2">
        <tr>
            <td>
                <div class="box">
                    <div class="box-title">
                        Resumen financiero
                        <span class="tag {{ $utilidad < 0 ? 'neg' : '' }}" style="margin-left:4px;">
                            {{ $utilidad >= 0 ? 'Ganancia estimada' : 'Pérdida estimada' }}
                        </span>
                    </div>
                    <table class="simple">
                        <tr>
                            <th>Concepto</th>
                            <th class="monto">Monto</th>
                        </tr>
                        <tr>
                            <td>Monto licitado / ingreso</td>
                            <td class="monto">${{ number_format($montoLicitado, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Inversión en productos</td>
                            <td class="monto">${{ number_format($gastoProductos, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Gastos operativos</td>
                            <td class="monto">${{ number_format($gastosOperativos, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Costo total</strong></td>
                            <td class="monto"><strong>${{ number_format($costoTotal, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td><strong>Resultado (utilidad neta)</strong></td>
                            <td class="monto">
                                <strong>${{ number_format($utilidad, 2) }}</strong>
                            </td>
                        </tr>
                        <tr>
                            <td>Margen sobre monto licitado</td>
                            <td class="monto">{{ number_format($margen, 1) }} %</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td>
                <div class="box">
                    <div class="box-title">Datos generales</div>
                    <table class="simple">
                        <tr>
                            <th>Campo</th>
                            <th>Valor</th>
                        </tr>
                        <tr>
                            <td>Modalidad</td>
                            <td>{{ ucfirst(str_replace('_',' ', $licitacion->modalidad ?? '')) }}</td>
                        </tr>
                        <tr>
                            <td>Fecha convocatoria principal</td>
                            <td>
                                @if($licitacion->fecha_convocatoria)
                                    {{ \Carbon\Carbon::parse($licitacion->fecha_convocatoria)->format('d/m/Y') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Fecha emisión contrato</td>
                            <td>
                                @if($licitacion->fecha_emision_contrato)
                                    {{ \Carbon\Carbon::parse($licitacion->fecha_emision_contrato)->format('d/m/Y') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Fecha fianza</td>
                            <td>
                                @if($licitacion->fecha_fianza)
                                    {{ \Carbon\Carbon::parse($licitacion->fecha_fianza)->format('d/m/Y') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Tipo de fianza</td>
                            <td>{{ $licitacion->tipo_fianza ? str_replace('_',' ', $licitacion->tipo_fianza) : '—' }}</td>
                        </tr>
                        <tr>
                            <td>Fechas de cobro</td>
                            <td>
                                @if(is_array($licitacion->fechas_cobro) && count($licitacion->fechas_cobro))
                                    @foreach($licitacion->fechas_cobro as $fc)
                                        {{ \Carbon\Carbon::parse($fc)->format('d/m/Y') }}@if(!$loop->last), @endif
                                    @endforeach
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- DETALLE DE GASTOS --}}
    <div class="box mt-10">
        <div class="box-title">Detalle de gastos operativos</div>
        <div class="box-sub">Desglose de los principales rubros de gasto.</div>

        <table class="simple">
            <tr>
                <th>Rubro</th>
                <th class="monto">Monto</th>
            </tr>
            <tr><td>Renta</td><td class="monto">${{ number_format($num($dc,'renta'),2) }}</td></tr>
            <tr><td>Luz</td><td class="monto">${{ number_format($num($dc,'luz'),2) }}</td></tr>
            <tr><td>Agua</td><td class="monto">${{ number_format($num($dc,'agua'),2) }}</td></tr>
            <tr><td>Nóminas</td><td class="monto">${{ number_format($num($dc,'nominas'),2) }}</td></tr>
            <tr><td>IMSS</td><td class="monto">${{ number_format($num($dc,'imss'),2) }}</td></tr>
            <tr><td>Gasolina</td><td class="monto">${{ number_format($num($dc,'gasolina'),2) }}</td></tr>
            <tr><td>Viáticos</td><td class="monto">${{ number_format($num($dc,'viaticos'),2) }}</td></tr>
            <tr><td>Casetas</td><td class="monto">${{ number_format($num($dc,'casetas'),2) }}</td></tr>
            <tr><td>Pagos gobierno / declaraciones</td><td class="monto">${{ number_format($num($dc,'pagos_gobierno'),2) }}</td></tr>
            <tr><td>Mantenimiento camionetas</td><td class="monto">${{ number_format($num($dc,'mantenimiento_camionetas'),2) }}</td></tr>
            <tr><td>{{ $libre1Label }}</td><td class="monto">${{ number_format($num($dc,'libre_1'),2) }}</td></tr>
            <tr><td>{{ $libre2Label }}</td><td class="monto">${{ number_format($num($dc,'libre_2'),2) }}</td></tr>
            <tr>
                <td><strong>Total gastos operativos</strong></td>
                <td class="monto"><strong>${{ number_format($gastosOperativos,2) }}</strong></td>
            </tr>
        </table>
    </div>

    {{-- NOTAS --}}
    @if($cont && $cont->notas)
        <div class="box mt-10">
            <div class="box-title">Notas contables</div>
            <p class="mt-4" style="font-size:10.5px; line-height:1.4;">
                {!! nl2br(e($cont->notas)) !!}
            </p>
        </div>
    @endif

    <p class="mt-10 small">
        Generado automáticamente el {{ now()->format('d/m/Y H:i') }}.
    </p>
</div>
</body>
</html>
