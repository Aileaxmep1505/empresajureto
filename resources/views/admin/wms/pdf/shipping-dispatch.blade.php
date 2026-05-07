<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Salida - {{ $shipment->shipment_number }}</title>
    <style>
        @page {
            margin: 110px 40px 50px 40px;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        header {
            position: fixed;
            top: -80px;
            left: 0;
            right: 0;
            height: 60px;
            border-bottom: 2px solid #003b73;
            padding-bottom: 10px;
        }

        header img {
            max-height: 45px;
            width: auto;
        }

        .info-header {
            width: 100%;
            margin-bottom: 30px;
            margin-top: 10px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .document-title {
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0f172a;
            margin: 0 0 4px 0;
        }

        .document-subtitle {
            font-size: 11px;
            color: #64748b;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .folio-box {
            text-align: right;
        }

        .folio-label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .folio-number {
            font-size: 18px;
            font-weight: bold;
            color: #b91c1c;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #003b73;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .info-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }

        .info-label {
            width: 18%;
            font-weight: bold;
            color: #64748b;
            font-size: 10px;
            text-transform: uppercase;
            background-color: #f8fafc;
        }

        .info-value {
            width: 32%;
            color: #0f172a;
            font-size: 12px;
            font-weight: 500;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .items-table th {
            background-color: #003b73;
            color: #ffffff;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 8px;
            text-align: left;
            border: 1px solid #003b73;
        }

        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e2e8f0;
            border-left: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            color: #1e293b;
            vertical-align: top;
        }

        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .items-table tr:last-child td {
            border-bottom: 2px solid #003b73;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-muted { color: #64748b; }
        .font-mono { font-family: monospace; color: #475569; font-size: 10px; }

        .summary-box {
            width: 100%;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 14px 16px;
            margin-bottom: 24px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 6px 8px;
            border: none;
        }

        .summary-label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
        }

        .summary-value {
            font-size: 14px;
            color: #0f172a;
            font-weight: bold;
        }

        .obs-box {
            background-color: #f8fafc;
            border-left: 3px solid #003b73;
            padding: 14px 18px;
            margin-bottom: 40px;
            color: #334155;
            font-size: 11px;
            line-height: 1.5;
        }

        .signatures-wrapper {
            margin-top: 50px;
            width: 100%;
            page-break-inside: avoid;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signatures-table td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 30px;
        }

        .sig-container {
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
        }

        .sig-container img {
            max-height: 80px;
            max-width: 100%;
            mix-blend-mode: multiply;
        }

        .sig-placeholder {
            color: #cbd5e1;
            font-style: italic;
            font-size: 10px;
            line-height: 90px;
        }

        .sig-line {
            border-top: 1px solid #003b73;
            margin-top: 5px;
            padding-top: 6px;
            font-weight: bold;
            color: #0f172a;
            font-size: 12px;
        }

        .sig-role {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            margin-top: 2px;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

<header>
    <img src="{{ public_path('images/logo-mail.png') }}" alt="Logo Empresa">
</header>

<main>
    <div class="info-header">
        <table class="header-table">
            <tr>
                <td style="width: 60%;">
                    <h1 class="document-title">Comprobante de Salida</h1>
                    <p class="document-subtitle">Documento generado por sistema WMS</p>
                </td>
                <td style="width: 40%;" class="folio-box">
                    <div class="folio-label">Folio / Embarque</div>
                    <div class="folio-number">{{ $shipment->shipment_number ?: ('EMB-' . $shipment->id) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Datos Generales</div>
    <table class="info-table">
        <tr>
            <td class="info-label">Pedido</td>
            <td class="info-value">{{ $shipment->order_number ?: '—' }}</td>
            <td class="info-label">Tarea Picking</td>
            <td class="info-value">{{ $shipment->task_number ?: '—' }}</td>
        </tr>
        <tr>
            <td class="info-label">Almacén</td>
            <td class="info-value">{{ $warehouseLabel }}</td>
            <td class="info-label">Estado</td>
            <td class="info-value" style="text-transform: uppercase; font-weight: bold; color: #003b73;">
                {{ $shipment->status ?: 'draft' }}
            </td>
        </tr>
        <tr>
            <td class="info-label">Ruta</td>
            <td class="info-value">{{ $shipment->route_name ?: '—' }}</td>
            <td class="info-label">Fecha de creación</td>
            <td class="info-value">
                {{ $shipment->created_at ? \Illuminate\Support\Carbon::parse($shipment->created_at)->format('d/m/Y H:i:s') : '—' }}
            </td>
        </tr>
        <tr>
            <td class="info-label">Fecha de salida</td>
            <td class="info-value">
                {{
                    $shipment->dispatched_at
                        ? \Illuminate\Support\Carbon::parse($shipment->dispatched_at)->format('d/m/Y H:i:s')
                        : 'Pendiente'
                }}
            </td>
            <td class="info-label">Observación</td>
            <td class="info-value">{{ $shipment->notes ?: '—' }}</td>
        </tr>
    </table>

    <div class="section-title">Responsables y Transporte</div>
    <table class="info-table">
        <tr>
            <td class="info-label">Entrega</td>
            <td class="info-value">{{ $delivererName }}</td>
            <td class="info-label">Asignado a</td>
            <td class="info-value">{{ $assignedTo }}</td>
        </tr>
        <tr>
            <td class="info-label">Chofer</td>
            <td class="info-value">{{ $shipment->driver_name ?: '—' }}</td>
            <td class="info-label">Teléfono</td>
            <td class="info-value">{{ $shipment->driver_phone ?: '—' }}</td>
        </tr>
        <tr>
            <td class="info-label">Vehículo</td>
            <td class="info-value">{{ $shipment->vehicle_name ?: '—' }}</td>
            <td class="info-label">Placas</td>
            <td class="info-value">{{ $shipment->vehicle_plate ?: '—' }}</td>
        </tr>
    </table>

    <div class="section-title">Resumen Operativo</div>
    <div class="summary-box">
        <table class="summary-table">
            <tr>
                <td style="width:25%;">
                    <div class="summary-label">Piezas esperadas</div>
                    <div class="summary-value">{{ (int) ($shipment->expected_qty ?? 0) }}</div>
                </td>
                <td style="width:25%;">
                    <div class="summary-label">Piezas cargadas</div>
                    <div class="summary-value">{{ (int) ($shipment->loaded_qty ?? 0) }}</div>
                </td>
                <td style="width:25%;">
                    <div class="summary-label">Piezas faltantes</div>
                    <div class="summary-value">{{ (int) ($shipment->missing_qty ?? 0) }}</div>
                </td>
                <td style="width:25%;">
                    <div class="summary-label">Cajas cargadas</div>
                    <div class="summary-value">{{ (int) ($shipment->loaded_boxes ?? 0) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Detalle de Mercancía</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 14%;">SKU</th>
                <th style="width: 30%;">Producto / Descripción</th>
                <th style="width: 10%; text-align:center;">Esp.</th>
                <th style="width: 10%; text-align:center;">Carg.</th>
                <th style="width: 10%; text-align:center;">Falt.</th>
                <th style="width: 10%; text-align:center;">Cajas</th>
                <th style="width: 16%;">Ubicación / Lote</th>
            </tr>
        </thead>
        <tbody>
            @forelse($shipment->lines as $line)
                @php
                    $sku = $line->product_sku ?? $line->sku ?? '—';
                    $name = $line->product_name ?? $line->name ?? 'Sin título';
                    $description = $line->description ?? '';
                    $expectedQty = (int) ($line->expected_qty ?? $line->qty ?? 0);
                    $loadedQty = (int) ($line->loaded_qty ?? 0);
                    $missingQty = (int) ($line->missing_qty ?? max(0, $expectedQty - $loadedQty));
                    $loadedBoxes = (int) ($line->loaded_boxes ?? 0);
                    $locationCode = $line->location_code ?? '—';
                    $batchCode = $line->batch_code ?? '';
                @endphp
                <tr>
                    <td class="font-mono">{{ $sku }}</td>
                    <td>
                        <strong style="color:#003b73;">{{ $name }}</strong><br>
                        <span style="font-size: 10px; color: #64748b;">
                            {{ $description ?: 'Sin descripción adicional' }}
                        </span>
                    </td>
                    <td class="text-center"><strong>{{ $expectedQty }}</strong></td>
                    <td class="text-center"><strong>{{ $loadedQty }}</strong></td>
                    <td class="text-center">{{ $missingQty }}</td>
                    <td class="text-center">{{ $loadedBoxes }}</td>
                    <td>
                        {{ $locationCode }}
                        @if($batchCode)
                            <br><span class="font-mono">Lote: {{ $batchCode }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted" style="padding: 20px;">
                        No hay líneas registradas en este embarque.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(!empty($shipment->notes))
        <div class="section-title">Notas Adicionales</div>
        <div class="obs-box">
            {{ $shipment->notes }}
        </div>
    @endif

    <div class="signatures-wrapper">
        <table class="signatures-table">
            <tr>
                <td>
                    <div class="sig-container">
                        @if(!empty($deliverySignature))
                            <img src="{{ $deliverySignature }}" alt="Firma entrega">
                        @else
                            <span class="sig-placeholder">PENDIENTE DE FIRMA</span>
                        @endif
                    </div>
                    <div class="sig-line">{{ $delivererName }}</div>
                    <div class="sig-role">Firma de Entrega</div>
                </td>

                <td>
                    <div class="sig-container">
                        @if(!empty($receiverSignature))
                            <img src="{{ $receiverSignature }}" alt="Firma recepción">
                        @else
                            <span class="sig-placeholder">PENDIENTE DE FIRMA</span>
                        @endif
                    </div>
                    <div class="sig-line">{{ $assignedTo }}</div>
                    <div class="sig-role">Firma de Recibe / Asignado</div>
                </td>
            </tr>
        </table>
    </div>
</main>

</body>
</html>