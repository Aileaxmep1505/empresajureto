<tr>
    <td>
        <span class="vp-badge vp-badge-{{ $row['status_class'] ?? 'info' }}">
            {{ $row['status_label'] ?? 'Pendiente' }}
        </span>
        <div class="vp-muted" style="margin-top:8px;">
            {{ $row['warehouse_name'] ?? '' }}
        </div>
    </td>

    <td>
        <div class="vp-product">
            <div class="vp-product-name">
                {{ $row['product_name'] ?? 'Producto virtual' }}
            </div>
            <div class="vp-product-meta">
                SKU: {{ $row['sku'] ?: '—' }} · Cant: {{ number_format((int)($row['qty'] ?? 0)) }}
            </div>
            <span class="vp-badge vp-badge-info">
                Virtual
            </span>
        </div>
    </td>

    <td>
        <div class="vp-strong">
            {{ $row['task_number'] ?: '—' }}
        </div>
        <div class="vp-muted">
            Pedido: {{ $row['order_number'] ?: '—' }}
        </div>
        <div class="vp-muted">
            Creado: {{ $row['created_at'] ?: '—' }}
        </div>
    </td>

    <td>
        <div class="vp-strong">
            {{ $row['origin'] ?: 'Origen externo' }}
        </div>
        <div class="vp-muted">
            {{ $row['notes'] ?? '' }}
        </div>
    </td>

    <td>
        <div class="vp-strong">
            {{ $row['collected_at'] ?: 'Pendiente' }}
        </div>
        <div class="vp-muted">
            {{ $row['collected_by'] ?? '' }}
        </div>
    </td>

    <td>
        <div class="vp-strong">
            {{ $row['staged_at'] ?: 'Pendiente' }}
        </div>
        <div class="vp-muted">
            Destino: {{ $row['staging_location_code'] ?: 'PICKING' }}
        </div>
        <div class="vp-muted">
            {{ $row['staged_by'] ?? '' }}
        </div>
    </td>

    <td>
        <div class="vp-strong">
            {{ $row['shipment_number'] ?: 'Sin embarque' }}
        </div>
        <div class="vp-muted">
            {{ $row['shipment_status'] ?? '' }}
        </div>
        <div class="vp-muted">
            {{ $row['shipped_at'] ?? '' }}
        </div>
    </td>

    <td>
        <div class="vp-row-actions">
            @if(($row['computed_status'] ?? '') === 'pending')
                <button type="button"
                        class="vp-btn vp-btn-outline"
                        onclick="markVirtualCollected('{{ $row['pick_wave_id'] }}', '{{ $row['line_id'] }}')">
                    Marcar recolectado
                </button>
            @elseif(($row['computed_status'] ?? '') === 'collected')
                <button type="button"
                        class="vp-btn vp-btn-primary"
                        onclick="markVirtualStaged('{{ $row['pick_wave_id'] }}', '{{ $row['line_id'] }}')">
                    Dejar en picking
                </button>
            @else
                <span class="vp-muted">
                    Sin acción pendiente
                </span>
            @endif
        </div>
    </td>
</tr>