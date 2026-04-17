<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bitácora de Recepción - {{ $reception->folio }}</title>
    <style>
        /* Configuración de Página para PDF */
        @page {
            /* Margen superior amplio (110px) para dejar espacio al logo en todas las hojas */
            margin: 110px 40px 50px 40px; 
        }

        /* Reset y Base */
        * { box-sizing: border-box; }
        
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* HEADER FIJO (Se repite en todas las páginas) */
        header {
            position: fixed;
            top: -80px; /* Sube el header hacia el margen del @page */
            left: 0;
            right: 0;
            height: 60px;
            border-bottom: 2px solid #003b73; /* Azul fuerte */
            padding-bottom: 10px;
        }

        header img {
            max-height: 45px;
            width: auto;
        }

        /* Cabecera de Datos (Solo aparece en la primera hoja) */
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
            color: #0f172a; /* Azul fuerte */
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
            color: #b91c1c; /* Rojo corporativo */
        }

        /* Títulos de sección */
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #003b73; /* Azul fuerte */
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }

        /* Tabla de Datos Generales */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .info-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #f8fafc;
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

        /* Tabla de Productos Premium */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table th {
            background-color: #003b73; /* AZUL FUERTE */
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
            background-color: #f8fafc; /* Efecto cebra */
        }

        .items-table tr:last-child td {
            border-bottom: 2px solid #003b73;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; color: #475569; font-size: 10px; }

        /* Observaciones */
        .obs-box {
            background-color: #f8fafc;
            border-left: 3px solid #003b73;
            padding: 14px 18px;
            margin-bottom: 40px;
            color: #334155;
            font-size: 11px;
            line-height: 1.5;
        }

        /* Firmas tipo Contrato */
        .signatures-wrapper {
            margin-top: 50px;
            width: 100%;
            page-break-inside: avoid; /* Evita que las firmas se partan a la mitad en dos hojas */
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signatures-table td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 40px;
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
                        <h1 class="document-title">Bitácora de Recepción</h1>
                        <p class="document-subtitle">Comprobante validado por sistema WMS</p>
                    </td>
                    <td style="width: 40%;" class="folio-box">
                        <div class="folio-label">Folio de Registro</div>
                        <div class="folio-number">{{ $reception->folio }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section-title">Detalles de Operación</div>
        <table class="info-table">
            <tr>
                <td class="info-label">Fecha y Hora</td>
                <td class="info-value">{{ optional($reception->reception_date)->format('d/m/Y H:i:s') }}</td>
                <td class="info-label">Estado</td>
                <td class="info-value" style="text-transform: uppercase; font-weight: bold; color: #003b73;">
                    {{ $reception->status }}
                </td>
            </tr>
            <tr>
                <td class="info-label">Entregado Por</td>
                <td class="info-value">{{ $reception->deliverer_name }}</td>
                <td class="info-label">Recibido Por</td>
                <td class="info-value">{{ $reception->receiver_name }}</td>
            </tr>
        </table>

        <div class="section-title">Desglose de Mercancía</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 16%;">SKU</th>
                    <th style="width: 32%;">Descripción / Producto</th>
                    <th style="width: 14%; text-align: center;">Cantidad</th>
                    <th style="width: 20%;">Lote</th>
                    <th style="width: 18%;">Condición</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reception->lines as $line)
                    <tr>
                        <td class="font-mono">{{ $line->sku ?: '—' }}</td>
                        <td>
                            <strong style="color: #003b73;">{{ $line->name ?: 'Sin título' }}</strong><br>
                            <span style="font-size: 10px; color: #64748b;">{{ $line->description ?: 'Sin descripción' }}</span>
                        </td>
                        <td class="text-center"><strong>{{ $line->quantity }}</strong></td>
                        <td>{{ $line->lot ?: 'N/A' }}</td>
                        <td style="text-transform: capitalize;">{{ $line->condition ?: 'Revisión' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted" style="padding: 20px;">No hay líneas de producto registradas en esta recepción.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if(!empty($reception->observations))
            <div class="section-title">Notas Adicionales</div>
            <div class="obs-box">
                {{ $reception->observations }}
            </div>
        @endif

        <div class="signatures-wrapper">
            <table class="signatures-table">
                <tr>
                    <td>
                        <div class="sig-container">
                            @if(!empty($reception->delivered_signature))
                                <img src="{{ $reception->delivered_signature }}" alt="Firma entrega">
                            @else
                                <span class="sig-placeholder">PENDIENTE DE FIRMA</span>
                            @endif
                        </div>
                        <div class="sig-line">{{ $reception->deliverer_name }}</div>
                        <div class="sig-role">Firma de Entrega (Proveedor)</div>
                    </td>
                    
                    <td>
                        <div class="sig-container">
                            @if(!empty($reception->received_signature))
                                <img src="{{ $reception->received_signature }}" alt="Firma recepción">
                            @else
                                <span class="sig-placeholder">PENDIENTE DE FIRMA</span>
                            @endif
                        </div>
                        <div class="sig-line">{{ $reception->receiver_name }}</div>
                        <div class="sig-role">Firma de Recepción (Almacén)</div>
                    </td>
                </tr>
            </table>
        </div>
    </main>

</body>
</html>