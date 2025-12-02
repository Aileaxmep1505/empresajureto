@extends('layouts.app')

@section('title', 'Detalle factura manual')

@section('content')
<style>
.mi-page{
    max-width: 1100px;
    margin: 20px auto 40px;
    padding: 0 16px;
}
.mi-card{
    background:#ffffff;
    border-radius:16px;
    box-shadow:0 18px 45px rgba(15,23,42,0.12);
    padding:20px 20px 24px;
}
.mi-header{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:16px;
    border-bottom:1px solid #e5e7eb;
    padding-bottom:12px;
    margin-bottom:16px;
}
.mi-title h1{
    margin:0;
    font-size:22px;
    font-weight:800;
    letter-spacing:-0.02em;
}
.mi-title small{
    display:block;
    color:#6b7280;
    margin-top:4px;
}
.mi-tags{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    justify-content:flex-end;
}
.mi-pill{
    padding:5px 10px;
    border-radius:999px;
    font-size:11px;
    font-weight:600;
    text-transform:uppercase;
    letter-spacing:.06em;
}
.mi-pill--draft{ background:#fef3c7; color:#92400e; }
.mi-pill--valid{ background:#dcfce7; color:#166534; }
.mi-pill--cancelled{ background:#fee2e2; color:#b91c1c; }

.mi-subgrid{
    display:grid;
    grid-template-columns: minmax(0,2fr) minmax(0,2fr) minmax(0,1.5fr);
    gap:16px;
    margin-bottom:14px;
}
.mi-block{
    border-radius:14px;
    border:1px solid rgba(226,232,240,0.9);
    padding:12px 14px;
    background:#f9fafb;
}
.mi-block h3{
    margin:0 0 6px;
    font-size:13px;
    text-transform:uppercase;
    letter-spacing:.08em;
    color:#6b7280;
}
.mi-block p{
    margin:0;
    font-size:13px;
    color:#111827;
}
.mi-block p + p{ margin-top:2px; }

.mi-table-wrap{
    margin-top:10px;
    border-radius:16px;
    border:1px solid #e5e7eb;
    overflow:hidden;
}
.mi-table{
    width:100%;
    border-collapse:collapse;
    font-size:13px;
}
.mi-table thead{
    background:#f3f4f6;
}
.mi-table th,
.mi-table td{
    padding:8px 10px;
    border-bottom:1px solid #e5e7eb;
}
.mi-table th{
    text-align:left;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.08em;
    color:#6b7280;
}
.mi-table td.num{
    text-align:right;
    font-variant-numeric: tabular-nums;
}
.mi-table tfoot td{
    background:#f9fafb;
    font-weight:600;
}

.mi-summary{
    margin-top:16px;
    display:flex;
    flex-wrap:wrap;
    gap:16px;
    justify-content:flex-end;
}
.mi-totals{
    min-width:260px;
    border-radius:14px;
    border:1px solid #e5e7eb;
    padding:10px 14px;
    background:#f9fafb;
}
.mi-totals-row{
    display:flex;
    justify-content:space-between;
    font-size:13px;
    margin-bottom:4px;
}
.mi-totals-row span:last-child{
    font-variant-numeric:tabular-nums;
}
.mi-totals-row--strong{
    margin-top:6px;
    padding-top:6px;
    border-top:1px dashed #d1d5db;
    font-weight:700;
}
.mi-actions-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    margin-bottom:14px;
}
.mi-back-link{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:7px 11px;
    font-size:13px;
    border-radius:999px;
    border:1px solid #e5e7eb;
    color:#4b5563;
    text-decoration:none;
    background:#fff;
}
.mi-back-link:hover{
    border-color:#d1d5db;
    color:#111827;
}
.mi-btn{
    border-radius:999px;
    border:0;
    padding:8px 14px;
    font-size:13px;
    font-weight:600;
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    gap:6px;
}
.mi-btn-primary{
    background:#22c55e;
    color:#ffffff;
}
.mi-btn-primary:hover{
    background:#16a34a;
}
.mi-btn-ghost{
    background:#ffffff;
    color:#374151;
    border:1px solid #e5e7eb;
}
.mi-btn-ghost:hover{
    background:#f3f4f6;
}
.mi-badge-small{
    display:inline-block;
    padding:2px 6px;
    border-radius:999px;
    font-size:10px;
    text-transform:uppercase;
    letter-spacing:.08em;
    background:#e5e7eb;
    color:#4b5563;
}

@media (max-width:900px){
    .mi-subgrid{
        grid-template-columns: minmax(0,1fr);
    }
}
</style>

@php
    $client = $invoice->client;
    $status = $invoice->status ?? 'draft';
    $statusLabel = [
        'draft'     => 'Borrador',
        'valid'     => 'Timbrada',
        'cancelled' => 'Cancelada',
    ][$status] ?? ucfirst($status);

    $statusClass = match ($status) {
        'valid'     => 'mi-pill mi-pill--valid',
        'cancelled' => 'mi-pill mi-pill--cancelled',
        default     => 'mi-pill mi-pill--draft',
    };
@endphp

<div class="mi-page">
    <div class="mi-actions-top">
        <a href="{{ route('manual_invoices.index') }}" class="mi-back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
            Volver a facturas
        </a>

        {{-- Acciones derecha --}}
        <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
            @if($status === 'draft')
                {{-- Botón para timbrar --}}
                <form action="{{ route('manual_invoices.stamp', $invoice) }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="mi-btn mi-btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 2 11 13 6 8"></polyline>
                            <polyline points="16 2 22 2 22 8"></polyline>
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                        Timbrar con Facturapi
                    </button>
                </form>
            @elseif($status === 'valid' && $invoice->facturapi_id)
                {{-- Botones para descargar PDF y XML --}}
                <a href="{{ route('manual_invoices.download_pdf', $invoice) }}"
                   class="mi-btn mi-btn-ghost" target="_blank">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3v14"></path>
                        <path d="M5 10l7 7 7-7"></path>
                        <path d="M5 21h14"></path>
                    </svg>
                    Descargar PDF
                </a>

                <a href="{{ route('manual_invoices.download_xml', $invoice) }}"
                   class="mi-btn mi-btn-ghost">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16v16H4z"></path>
                        <path d="M7 9l3 3-3 3"></path>
                        <path d="M17 9l-3 3 3 3"></path>
                    </svg>
                    Descargar XML
                </a>
            @endif
        </div>
    </div>

    <div class="mi-card">
        <div class="mi-header">
            <div class="mi-title">
                <h1>
                    Factura manual
                    @if($invoice->serie || $invoice->folio)
                        <span class="mi-badge-small">
                            {{ $invoice->serie }}{{ $invoice->serie && $invoice->folio ? '-' : '' }}{{ $invoice->folio }}
                        </span>
                    @endif
                </h1>
                <small>
                    {{ $invoice->created_at?->format('d/m/Y H:i') }}
                    &middot;
                    Total: {{ number_format($invoice->total ?? 0, 2) }} {{ $invoice->currency ?? 'MXN' }}
                </small>
            </div>

            <div class="mi-tags">
                <span class="{{ $statusClass }}">{{ $statusLabel }}</span>

                @if($invoice->facturapi_uuid)
                    <span class="mi-pill mi-pill--valid">
                        UUID: {{ $invoice->facturapi_uuid }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Bloques de info --}}
        <div class="mi-subgrid">
            {{-- Receptor --}}
            <div class="mi-block">
                <h3>Receptor</h3>
                <p><strong>{{ $client?->razon_social ?: $client?->nombre }}</strong></p>
                <p>RFC: {{ $client?->rfc ?: '—' }}</p>
                <p>Correo: {{ $client?->email ?: '—' }}</p>
                <p>
                    @if($client)
                        Tipo persona: {{ $client->etiqueta_persona }}<br>
                        Régimen: {{ $client->regimen_fiscal ?: '—' }}
                    @endif
                </p>
            </div>

            {{-- CFDI / Forma pago --}}
            <div class="mi-block">
                <h3>CFDI</h3>
                <p>Uso CFDI: {{ $invoice->cfdi_uso ?? 'G03' }}</p>
                <p>Forma de pago: {{ $invoice->payment_form ?? '03' }} (Transferencia)</p>
                <p>Método de pago: {{ $invoice->payment_method ?? 'PUE' }}</p>
                <p>Moneda: {{ $invoice->currency ?? 'MXN' }}</p>
            </div>

            {{-- Timbrado --}}
            <div class="mi-block">
                <h3>Timbrado</h3>
                <p>Estatus Facturapi: {{ $invoice->facturapi_status ?? '—' }}</p>
                <p>Cancelación: {{ $invoice->cancellation_status ?? '—' }}</p>
                <p>
                    Timbrada:
                    {{ $invoice->stamped_at ? $invoice->stamped_at->format('d/m/Y H:i') : '—' }}
                </p>
                @if($invoice->verification_url)
                    <p style="margin-top:4px;">
                        <a href="{{ $invoice->verification_url }}" target="_blank">
                            Ver en SAT / Facturapi
                        </a>
                    </p>
                @endif
            </div>
        </div>

        {{-- Notas --}}
        @if($invoice->notes)
            <div class="mi-block" style="margin-bottom:14px;">
                <h3>Notas internas</h3>
                <p>{{ $invoice->notes }}</p>
            </div>
        @endif

        {{-- Tabla de conceptos --}}
        <div class="mi-table-wrap">
            <table class="mi-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Descripción</th>
                    <th>Clave SAT</th>
                    <th>Unidad</th>
                    <th class="text-end">Cantidad</th>
                    <th class="text-end">P. unitario</th>
                    <th class="text-end">Descuento</th>
                    <th class="text-end">Impuesto</th>
                    <th class="text-end">Importe</th>
                </tr>
                </thead>
                <tbody>
                @foreach($invoice->items as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item->description }}</td>
                        <td>{{ $item->product_key ?: '01010101' }}</td>
                        <td>{{ $item->unit ?: 'PZA' }}</td>
                        <td class="num">{{ number_format($item->quantity, 3) }}</td>
                        <td class="num">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="num">{{ number_format($item->discount ?? 0, 2) }}</td>
                        <td class="num">{{ number_format($item->tax ?? 0, 2) }}</td>
                        <td class="num">{{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Totales --}}
        <div class="mi-summary">
            <div class="mi-totals">
                <div class="mi-totals-row">
                    <span>Subtotal</span>
                    <span>{{ number_format($invoice->subtotal ?? 0, 2) }} {{ $invoice->currency ?? 'MXN' }}</span>
                </div>
                <div class="mi-totals-row">
                    <span>Descuento</span>
                    <span>- {{ number_format($invoice->discount_total ?? 0, 2) }} {{ $invoice->currency ?? 'MXN' }}</span>
                </div>
                <div class="mi-totals-row">
                    <span>Impuestos</span>
                    <span>{{ number_format($invoice->tax_total ?? 0, 2) }} {{ $invoice->currency ?? 'MXN' }}</span>
                </div>
                <div class="mi-totals-row mi-totals-row--strong">
                    <span>Total</span>
                    <span>{{ number_format($invoice->total ?? 0, 2) }} {{ $invoice->currency ?? 'MXN' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
