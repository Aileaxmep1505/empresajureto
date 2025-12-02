@php
    use Illuminate\Support\Str;

    $isEdit = isset($invoice) && $invoice->exists;

    // rows = old() -> items del modelo -> fila vacía
    $rows = old('items');
    if (!$rows) {
        if ($isEdit) {
            $rows = $invoice->items->map(function($it){
                return [
                    'id'          => $it->id,
                    'product_id'  => $it->product_id,
                    'description' => $it->description,
                    'quantity'    => (float)$it->quantity,
                    'unit_price'  => (float)$it->unit_price,
                    'discount'    => (float)$it->discount,
                    'tax_rate'    => (float)$it->tax_rate,
                    'unit'        => $it->unit,
                    'unit_code'   => $it->unit_code,
                    'product_key' => $it->product_key,
                ];
            })->toArray();
        }
    }
    if (!$rows || !count($rows)) {
        $rows = [[
            'id'          => null,
            'product_id'  => null,
            'description' => '',
            'quantity'    => 1,
            'unit_price'  => 0,
            'discount'    => 0,
            'tax_rate'    => 16,
            'unit'        => null,
            'unit_code'   => null,
            'product_key' => null,
        ]];
    }

    $currentClientId = old('client_id', $isEdit ? $invoice->client_id : null);
    $currentType     = old('type', $isEdit ? $invoice->type : 'I');
@endphp

<style>
    .fi-layout{
        display:grid;
        grid-template-columns: minmax(0,2.2fr) minmax(0,1.3fr);
        gap:18px;
        max-width:1120px;
        margin:24px auto 40px;
        padding:0 16px;
        font-family:system-ui,-apple-system,BlinkMacSystemFont,"SF Pro Text","Segoe UI",sans-serif;
    }
    .fi-card{
        background:#ffffff;
        border-radius:18px;
        border:1px solid rgba(148,163,184,0.3);
        box-shadow:0 18px 40px rgba(15,23,42,0.06);
        padding:18px 18px 14px;
    }
    .fi-card-header{
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:10px;
    }
    .fi-card-title{
        font-size:.92rem;
        font-weight:600;
    }
    .fi-card-sub{
        font-size:.78rem;
        color:#6b7280;
    }

    .fi-field{
        margin-bottom:10px;
    }
    .fi-label{
        display:block;
        font-size:.78rem;
        font-weight:600;
        color:#4b5563;
        margin-bottom:4px;
    }
    .fi-input, .fi-select, .fi-textarea{
        width:100%;
        border-radius:10px;
        border:1px solid #e5e7eb;
        padding:7px 10px;
        font-size:.84rem;
    }
    .fi-select{background:#ffffff;}
    .fi-input:focus, .fi-select:focus, .fi-textarea:focus{
        outline:none;
        border-color:#2563eb;
        box-shadow:0 0 0 1px rgba(37,99,235,0.18);
    }

    .fi-items-header{
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-top:6px;
        margin-bottom:4px;
    }
    .fi-items-table{
        width:100%;
        border-collapse:collapse;
        font-size:.8rem;
    }
    .fi-items-table th,
    .fi-items-table td{
        border-bottom:1px solid #e5e7eb;
        padding:6px 6px;
        vertical-align:middle;
    }
    .fi-items-table th{
        font-size:.7rem;
        text-transform:uppercase;
        letter-spacing:.04em;
        color:#6b7280;
    }

    .fi-items-table input{
        width:100%;
        border-radius:8px;
        border:1px solid #e5e7eb;
        padding:4px 6px;
        font-size:.8rem;
    }
    .fi-items-table select{
        width:100%;
        border-radius:8px;
        border:1px solid #e5e7eb;
        padding:4px 6px;
        font-size:.78rem;
        background:#fff;
    }

    .fi-btn-add-row{
        border-radius:999px;
        border:none;
        background:#eef2ff;
        color:#4f46e5;
        font-size:.78rem;
        padding:6px 10px;
        font-weight:600;
        cursor:pointer;
    }
    .fi-remove-row{
        border:none;
        background:none;
        font-size:1rem;
        cursor:pointer;
        color:#9ca3af;
    }
    .fi-remove-row:hover{color:#ef4444;}

    .fi-summary-row{
        display:flex;
        justify-content:space-between;
        font-size:.82rem;
        margin-bottom:4px;
    }
    .fi-summary-row span:first-child{color:#6b7280;}
    .fi-summary-row span:last-child{font-weight:600;}
    .fi-summary-row.total span:last-child{font-size:1rem;}

    .fi-actions-main{
        display:flex;
        justify-content:flex-end;
        gap:10px;
        margin-top:10px;
    }
    .btn-outline{
        border-radius:999px;
        border:1px solid #e5e7eb;
        padding:7px 14px;
        font-size:.82rem;
        background:#fff;
        cursor:pointer;
    }
    .btn-primary{
        border-radius:999px;
        border:none;
        padding:8px 18px;
        font-size:.84rem;
        font-weight:600;
        background:#2563eb;
        color:#fff;
        cursor:pointer;
        box-shadow:0 10px 24px rgba(37,99,235,0.35);
    }
</style>

<div class="fi-layout">
    {{-- Columna izquierda: cliente + conceptos --}}
    <div class="fi-card">
        <div class="fi-card-header">
            <div>
                <div class="fi-card-title">Datos de la factura</div>
                <div class="fi-card-sub">Selecciona el cliente y agrega los conceptos a facturar.</div>
            </div>
        </div>

        {{-- Cliente + tipo --}}
        <div class="row">
            <div class="col-md-8">
                <div class="fi-field">
                    <label class="fi-label">Cliente</label>
                    <select name="client_id" class="fi-select" required>
                        <option value="">Selecciona un cliente…</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}"
                                {{ (int)$currentClientId === $client->id ? 'selected' : '' }}>
                                {{ $client->nombre }} — {{ $client->rfc }}
                            </option>
                        @endforeach
                    </select>
                    @error('client_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="fi-field">
                    <label class="fi-label">Tipo</label>
                    <select name="type" class="fi-select">
                        <option value="I" {{ $currentType === 'I' ? 'selected' : '' }}>Ingreso</option>
                        <option value="E" {{ $currentType === 'E' ? 'selected' : '' }}>Egreso</option>
                        <option value="P" {{ $currentType === 'P' ? 'selected' : '' }}>Pago</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Conceptos --}}
        <div class="fi-items-header">
            <div class="fi-card-title" style="font-size:.9rem;">Conceptos</div>
            <button type="button" class="fi-btn-add-row" id="btn-add-row">+ Agregar concepto</button>
        </div>

        <table class="fi-items-table" id="items-table">
            <thead>
            <tr>
                <th style="width:22%;">Producto</th>
                <th style="width:26%;">Descripción</th>
                <th style="width:8%;">Cant.</th>
                <th style="width:10%;">P. unit.</th>
                <th style="width:9%;">Desc.</th>
                <th style="width:8%;">IVA%</th>
                <th style="width:10%;">Total</th>
                <th style="width:4%;"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($rows as $i => $row)
                <tr data-row="{{ $i }}">
                    {{-- id de item para UPDATE --}}
                    @if(!empty($row['id']))
                        <input type="hidden" name="items[{{ $i }}][id]" value="{{ $row['id'] }}">
                    @endif
                    <td>
                        <select name="items[{{ $i }}][product_id]" class="fi-select fi-product-select">
                            <option value="">Sin vincular</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}"
                                    data-sku="{{ $p->sku }}"
                                    data-unit="{{ $p->unit }}"
                                    data-price="{{ $p->price ?? $p->market_price ?? $p->bid_price ?? 0 }}"
                                    data-product-key="{{ $p->clave_sat }}"
                                    {{ (isset($row['product_id']) && (int)$row['product_id'] === $p->id) ? 'selected' : '' }}>
                                    {{ $p->sku }} — {{ Str::limit($p->name, 60) }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text"
                               name="items[{{ $i }}][description]"
                               value="{{ $row['description'] }}"
                               placeholder="Descripción"
                               required>
                        <input type="hidden" name="items[{{ $i }}][unit]" value="{{ $row['unit'] }}">
                        <input type="hidden" name="items[{{ $i }}][unit_code]" value="{{ $row['unit_code'] }}">
                        <input type="hidden" name="items[{{ $i }}][product_key]" value="{{ $row['product_key'] }}">
                    </td>
                    <td>
                        <input type="number" step="0.001" min="0.001"
                               name="items[{{ $i }}][quantity]"
                               class="fi-qty"
                               value="{{ $row['quantity'] ?? 1 }}">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0"
                               name="items[{{ $i }}][unit_price]"
                               class="fi-price"
                               value="{{ $row['unit_price'] ?? 0 }}">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0"
                               name="items[{{ $i }}][discount]"
                               class="fi-discount"
                               value="{{ $row['discount'] ?? 0 }}">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0"
                               name="items[{{ $i }}][tax_rate]"
                               class="fi-tax-rate"
                               value="{{ $row['tax_rate'] ?? 16 }}">
                    </td>
                    <td>
                        <input type="text" class="fi-line-total" readonly value="0.00">
                    </td>
                    <td style="text-align:center;">
                        <button type="button" class="fi-remove-row">&times;</button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="fi-field mt-2">
            <label class="fi-label">Notas internas</label>
            <textarea name="notes" rows="2" class="fi-textarea"
                      placeholder="Texto libre para uso interno (no se envía al SAT)">{{ old('notes', $isEdit ? $invoice->notes : '') }}</textarea>
        </div>
    </div>

    {{-- Columna derecha: resumen --}}
    <div class="fi-card">
        <div class="fi-card-header">
            <div>
                <div class="fi-card-title">Resumen</div>
                <div class="fi-card-sub">Totales calculados en tiempo real.</div>
            </div>
        </div>

        <div class="fi-summary-row">
            <span>Subtotal</span>
            <span id="summary-subtotal">$0.00</span>
        </div>
        <div class="fi-summary-row">
            <span>Descuento</span>
            <span id="summary-discount">$0.00</span>
        </div>
        <div class="fi-summary-row">
            <span>Impuestos</span>
            <span id="summary-tax">$0.00</span>
        </div>
        <hr>
        <div class="fi-summary-row total">
            <span>Total</span>
            <span id="summary-total">$0.00</span>
        </div>

        <div class="fi-actions-main">
            <a href="{{ route('manual_invoices.index') }}" class="btn-outline">Cancelar</a>
            <button type="submit" class="btn-primary">
                {{ $isEdit ? 'Guardar cambios' : 'Guardar borrador' }}
            </button>
        </div>
    </div>
</div>

<script>
    (function(){
        const table    = document.getElementById('items-table');
        const addRowBtn= document.getElementById('btn-add-row');

        function recalcRow(tr){
            const qty      = parseFloat(tr.querySelector('.fi-qty')?.value || '0') || 0;
            const price    = parseFloat(tr.querySelector('.fi-price')?.value || '0') || 0;
            const discount = parseFloat(tr.querySelector('.fi-discount')?.value || '0') || 0;
            const taxRate  = parseFloat(tr.querySelector('.fi-tax-rate')?.value || '0') || 0;

            let subtotal = Math.max(qty * price - discount, 0);
            let tax      = subtotal * (taxRate / 100);
            let total    = subtotal + tax;

            tr.querySelector('.fi-line-total').value = total.toFixed(2);

            return {subtotal, discount, tax, total};
        }

        function recalcAll(){
            let subtotal = 0, discount = 0, tax = 0, total = 0;
            table.querySelectorAll('tbody tr').forEach(function(tr){
                const vals = recalcRow(tr);
                subtotal += vals.subtotal;
                discount += vals.discount;
                tax      += vals.tax;
                total    += vals.total;
            });

            document.getElementById('summary-subtotal').innerText = '$' + subtotal.toFixed(2);
            document.getElementById('summary-discount').innerText = '$' + discount.toFixed(2);
            document.getElementById('summary-tax').innerText      = '$' + tax.toFixed(2);
            document.getElementById('summary-total').innerText    = '$' + total.toFixed(2);
        }

        function bindRowEvents(tr){
            tr.querySelectorAll('.fi-qty, .fi-price, .fi-discount, .fi-tax-rate')
                .forEach(function(input){
                    input.addEventListener('input', recalcAll);
                });

            const select = tr.querySelector('.fi-product-select');
            if (select){
                select.addEventListener('change', function(){
                    const opt = select.options[select.selectedIndex];
                    if (!opt) return;
                    const price = opt.getAttribute('data-price') || '0';
                    const unit  = opt.getAttribute('data-unit')  || '';
                    const pkey  = opt.getAttribute('data-product-key') || '';

                    tr.querySelector('input[name*="[unit_price]"]').value = price;
                    tr.querySelector('input[name*="[unit]"]').value       = unit;
                    tr.querySelector('input[name*="[product_key]"]').value = pkey;

                    if (!tr.querySelector('input[name*="[description]"]').value.trim()) {
                        tr.querySelector('input[name*="[description]"]').value = opt.textContent.trim();
                    }
                    recalcAll();
                });
            }

            const removeBtn = tr.querySelector('.fi-remove-row');
            removeBtn.addEventListener('click', function(){
                const rows = table.querySelectorAll('tbody tr');
                if (rows.length <= 1){
                    // limpiar en vez de borrar
                    tr.querySelectorAll('input').forEach(i => i.value = '');
                    tr.querySelector('.fi-line-total').value = '0.00';
                } else {
                    tr.parentNode.removeChild(tr);
                }
                recalcAll();
            });
        }

        // Inicializar filas existentes
        table.querySelectorAll('tbody tr').forEach(bindRowEvents);
        recalcAll();

        // Agregar nueva fila
        addRowBtn.addEventListener('click', function(){
            const tbody = table.querySelector('tbody');
            const index = tbody.querySelectorAll('tr').length;
            const proto = tbody.querySelector('tr');
            const clone = proto.cloneNode(true);

            clone.setAttribute('data-row', index);
            clone.querySelectorAll('input, select').forEach(function(el){
                if (!el.name) return;
                el.name = el.name.replace(/items\[\d+]/, 'items['+index+']');
                if (el.tagName === 'INPUT'){
                    if (el.type === 'hidden'){
                        el.value = '';
                    } else {
                        if (el.classList.contains('fi-qty')) el.value = '1';
                        else if (el.classList.contains('fi-tax-rate')) el.value = '16';
                        else {
                            el.value = '';
                        }
                    }
                }
                if (el.tagName === 'SELECT'){
                    el.selectedIndex = 0;
                }
            });

            clone.querySelector('.fi-line-total').value = '0.00';

            tbody.appendChild(clone);
            bindRowEvents(clone);
            recalcAll();
        });
    })();
</script>
