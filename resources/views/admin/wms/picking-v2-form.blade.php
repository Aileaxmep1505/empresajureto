@php
  $isEdit = ($mode ?? 'create') === 'edit';
  $taskData = $task ?? null;

  $taskNumber = old('task_number', $taskData['task_number'] ?? $nextTaskNumber ?? '');
  $orderNumber = old('order_number', $taskData['order_number'] ?? '');
  $assignedUserId = old('assigned_user_id', $taskData['assigned_user_id'] ?? '');
  $priority = old('priority', $taskData['priority'] ?? 'normal');
  $notes = old('notes', $taskData['notes'] ?? '');
  $totalPhases = (int) old('total_phases', $taskData['total_phases'] ?? 1);

  $deliveries = old('deliveries', $taskData['deliveries'] ?? []);
  $items = $taskData['items'] ?? [];

  $itemsByPhase = [];
  foreach ($items as $it) {
      $phase = (int) data_get($it, 'delivery_phase', 1);
      $itemsByPhase[$phase] ??= [];
      $itemsByPhase[$phase][] = $it;
  }
@endphp

<div class="pk-form-wrap">
  <div class="pk-form-head">
    <div>
      <h1 class="pk-form-title">{{ $isEdit ? 'Editar tarea de picking' : 'Nueva tarea de picking' }}</h1>
      <div class="pk-form-sub">Configura operario, fases y productos por entrega</div>
    </div>

    <div class="pk-form-actions-top">
      <a href="{{ route('admin.wms.picking.v2') }}" class="pk-btn pk-btn-ghost">← Volver</a>
    </div>
  </div>

  @if ($errors->any())
    <div class="pk-alert-error">
      <strong>Revisa los datos del formulario.</strong>
    </div>
  @endif

  <form method="POST" action="{{ $action }}" class="pk-shell" id="phaseForm">
    @csrf
    @if($isEdit)
      @method('PATCH')
    @endif

    <div class="pk-grid-top">
      <div class="pk-field">
        <label>Número de tarea</label>
        <input type="text" name="task_number" value="{{ $taskNumber }}" {{ $isEdit ? 'readonly' : '' }}>
      </div>

      <div class="pk-field">
        <label>Orden de referencia</label>
        <input type="text" name="order_number" value="{{ $orderNumber }}">
      </div>

      <div class="pk-field">
        <label>Operario</label>
        <select name="assigned_user_id">
          <option value="">Seleccionar operario</option>
          @foreach($users as $user)
            <option value="{{ $user['id'] }}" @selected((string)$assignedUserId === (string)$user['id'])>{{ $user['name'] }}</option>
          @endforeach
        </select>
      </div>

      <div class="pk-field">
        <label>Prioridad</label>
        <select name="priority">
          <option value="low" @selected($priority === 'low')>Baja</option>
          <option value="normal" @selected($priority === 'normal')>Normal</option>
          <option value="high" @selected($priority === 'high')>Alta</option>
          <option value="urgent" @selected($priority === 'urgent')>Urgente</option>
        </select>
      </div>

      <div class="pk-field">
        <label>Total de fases</label>
        <input type="number" min="1" max="12" name="total_phases" id="totalPhasesInput" value="{{ $totalPhases }}">
      </div>

      <div class="pk-field pk-field-full">
        <label>Notas</label>
        <textarea name="notes" rows="3">{{ $notes }}</textarea>
      </div>
    </div>

    <div class="pk-phase-box">
      <div class="pk-phase-head">
        <div class="pk-phase-title">Entregas y productos por fase</div>
        <div class="pk-phase-sub">Cada fase puede tener productos distintos. También puedes duplicar un producto a todas las fases.</div>
      </div>

      <div id="phasesContainer">
        @for($phase = 1; $phase <= $totalPhases; $phase++)
          @php
            $delivery = $deliveries[$phase - 1] ?? ['phase' => $phase, 'title' => 'Entrega '.$phase, 'scheduled_for' => null, 'notes' => ''];
            $phaseItems = $itemsByPhase[$phase] ?? [];
          @endphp

          <div class="pk-phase-card" data-phase="{{ $phase }}">
            <div class="pk-phase-card-top">
              <div class="pk-phase-card-title">Fase {{ $phase }}</div>
            </div>

            <div class="pk-phase-meta">
              <div class="pk-field">
                <label>Título</label>
                <input type="text" name="deliveries[{{ $phase - 1 }}][title]" value="{{ old('deliveries.'.($phase - 1).'.title', $delivery['title'] ?? 'Entrega '.$phase) }}">
              </div>

              <div class="pk-field">
                <label>Programada para</label>
                <input type="datetime-local"
                       name="deliveries[{{ $phase - 1 }}][scheduled_for]"
                       value="{{ old('deliveries.'.($phase - 1).'.scheduled_for', !empty($delivery['scheduled_for']) ? \Carbon\Carbon::parse($delivery['scheduled_for'])->format('Y-m-d\TH:i') : '') }}">
              </div>

              <div class="pk-field">
                <label>Notas de fase</label>
                <input type="text" name="deliveries[{{ $phase - 1 }}][notes]" value="{{ old('deliveries.'.($phase - 1).'.notes', $delivery['notes'] ?? '') }}">
              </div>

              <input type="hidden" name="deliveries[{{ $phase - 1 }}][phase]" value="{{ $phase }}">
            </div>

            <div class="pk-add-product-box">
              <div class="pk-add-grid">
                <div class="pk-field">
                  <label>Producto</label>
                  <input type="text" class="phase-product-search" list="productsList" placeholder="SKU o nombre">
                </div>

                <div class="pk-field">
                  <label>Cantidad</label>
                  <input type="number" class="phase-product-qty" min="1" value="1">
                </div>

                <div class="pk-field">
                  <label>Ubicación</label>
                  <input type="text" class="phase-product-location" placeholder="Ubicación">
                </div>

                <div class="pk-field">
                  <label>Duplicar</label>
                  <select class="phase-duplicate-mode">
                    <option value="single">Solo esta fase</option>
                    <option value="all">Agregar a todas las fases</option>
                  </select>
                </div>

                <div class="pk-field pk-field-btn">
                  <label>&nbsp;</label>
                  <button type="button" class="pk-btn pk-btn-primary add-phase-product-btn" data-phase="{{ $phase }}">Agregar producto</button>
                </div>
              </div>

              <div class="pk-preview-mini">
                <div><strong>SKU:</strong> <span class="preview-sku">—</span></div>
                <div><strong>Marca:</strong> <span class="preview-brand">—</span></div>
                <div><strong>Modelo:</strong> <span class="preview-model">—</span></div>
                <div><strong>Descripción:</strong> <span class="preview-description">—</span></div>
              </div>
            </div>

            <div class="phase-items-list" id="phase-items-{{ $phase }}">
              @forelse($phaseItems as $index => $item)
                <div class="phase-item-row">
                  <div class="phase-item-main">
                    <div class="phase-item-name">{{ $item['product_name'] }}</div>
                    <div class="phase-item-sub">{{ $item['product_sku'] ?: 'Sin SKU' }}</div>
                  </div>

                  <input type="hidden" name="phase_items[{{ $phase }}][{{ $index }}][product_id]" value="{{ $item['product_id'] }}">
                  <input type="hidden" name="phase_items[{{ $phase }}][{{ $index }}][product_name]" value="{{ $item['product_name'] }}">
                  <input type="hidden" name="phase_items[{{ $phase }}][{{ $index }}][product_sku]" value="{{ $item['product_sku'] }}">
                  <input type="hidden" name="phase_items[{{ $phase }}][{{ $index }}][description]" value="{{ $item['description'] }}">
                  <input type="hidden" name="phase_items[{{ $phase }}][{{ $index }}][brand]" value="{{ $item['brand'] }}">
                  <input type="hidden" name="phase_items[{{ $phase }}][{{ $index }}][model]" value="{{ $item['model'] }}">

                  <input type="text" class="phase-inline-input" name="phase_items[{{ $phase }}][{{ $index }}][location_code]" value="{{ $item['location_code'] }}" placeholder="Ubicación">
                  <input type="number" min="1" class="phase-inline-input" name="phase_items[{{ $phase }}][{{ $index }}][quantity_required]" value="{{ $item['quantity_required'] }}">

                  <button type="button" class="pk-remove-btn remove-phase-item-btn">×</button>
                </div>
              @empty
                <div class="phase-empty">Sin productos en esta fase.</div>
              @endforelse
            </div>
          </div>
        @endfor
      </div>

      <datalist id="productsList">
        @foreach($products as $product)
          <option value="{{ $product['sku'] ? $product['sku'].' · '.$product['name'] : $product['name'] }}"></option>
        @endforeach
      </datalist>
    </div>

    <div class="pk-submit-row">
      <button type="submit" class="pk-btn pk-btn-primary">{{ $isEdit ? 'Guardar cambios' : 'Crear tarea' }}</button>
    </div>
  </form>
</div>

@push('styles')
<style>
.pk-form-wrap{max-width:1320px;margin:0 auto;padding:18px 14px 30px}
.pk-form-head{display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:18px}
.pk-form-title{margin:0;font-size:2rem;font-weight:950;color:#0f172a}
.pk-form-sub{margin-top:4px;color:#64748b}
.pk-shell{display:grid;gap:18px}
.pk-grid-top{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:18px;box-shadow:0 10px 24px rgba(15,23,42,.05)}
.pk-field{display:flex;flex-direction:column;gap:8px}
.pk-field-full{grid-column:1/-1}
.pk-field label{font-weight:800;color:#0f172a}
.pk-field input,.pk-field select,.pk-field textarea{width:100%;border:1px solid #d1d5db;border-radius:12px;background:#fff;min-height:46px;padding:11px 13px}
.pk-phase-box{background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:18px;box-shadow:0 10px 24px rgba(15,23,42,.05)}
.pk-phase-head{margin-bottom:16px}
.pk-phase-title{font-size:1.05rem;font-weight:950;color:#0f172a}
.pk-phase-sub{margin-top:4px;color:#64748b}
#phasesContainer{display:grid;gap:16px}
.pk-phase-card{border:1px solid #e5e7eb;border-radius:16px;background:#f8fafc;padding:16px}
.pk-phase-card-top{margin-bottom:12px}
.pk-phase-card-title{font-size:1rem;font-weight:950;color:#0f172a}
.pk-phase-meta{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:16px}
.pk-add-product-box{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px;margin-bottom:14px}
.pk-add-grid{display:grid;grid-template-columns:minmax(0,1.8fr) 120px 180px 180px 160px;gap:10px}
.pk-field-btn{display:flex;flex-direction:column}
.pk-preview-mini{margin-top:12px;display:grid;gap:6px;color:#475569;font-size:.9rem}
.phase-items-list{display:grid;gap:10px}
.phase-item-row{display:grid;grid-template-columns:minmax(0,1.5fr) 170px 120px 48px;gap:10px;align-items:center;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:10px}
.phase-item-main{min-width:0}
.phase-item-name{font-weight:900;color:#0f172a}
.phase-item-sub{margin-top:3px;color:#64748b;font-size:.86rem}
.phase-inline-input{width:100%;border:1px solid #d1d5db;border-radius:10px;padding:10px 12px;min-height:42px}
.phase-empty{padding:16px;text-align:center;color:#64748b;background:#fff;border:1px dashed #cbd5e1;border-radius:12px}
.pk-remove-btn{width:42px;height:42px;border:1px solid #fecaca;background:#fff1f2;color:#e11d48;border-radius:10px;cursor:pointer;font-weight:900;font-size:1.1rem}
.pk-submit-row{display:flex;justify-content:flex-end}
.pk-btn{border:0;border-radius:12px;padding:11px 16px;font-weight:900;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;cursor:pointer}
.pk-btn-primary{background:#2563eb;color:#fff}
.pk-btn-ghost{background:#fff;color:#0f172a;border:1px solid #dbe3ef}
.pk-alert-error{background:#fff1f2;border:1px solid #fecdd3;color:#9f1239;padding:12px 14px;border-radius:12px}
@media (max-width:1000px){
  .pk-grid-top,.pk-phase-meta,.pk-add-grid{grid-template-columns:1fr}
  .phase-item-row{grid-template-columns:1fr}
}
</style>
@endpush

@push('scripts')
<script>
(function(){
  const products = @json($products ?? []);
  const totalPhasesInput = document.getElementById('totalPhasesInput');
  const phasesContainer = document.getElementById('phasesContainer');

  function normalize(str){
    return String(str || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .trim();
  }

  function guessProduct(search){
    const q = normalize(search);
    if(!q) return null;

    return products.find(p => {
      const name = normalize(p.name);
      const sku = normalize(p.sku);
      const combo = normalize(`${p.sku} ${p.name}`);
      return q === name || q === sku || q === combo || combo.includes(q);
    }) || null;
  }

  function updatePreview(card, product){
    if(!card) return;

    card.querySelector('.preview-sku').textContent = product?.sku || '—';
    card.querySelector('.preview-brand').textContent = product?.brand || product?.brand_name || '—';
    card.querySelector('.preview-model').textContent = product?.model || product?.model_name || '—';
    card.querySelector('.preview-description').textContent = product?.description || '—';
  }

  function makeItemRow(phase, idx, data){
    const row = document.createElement('div');
    row.className = 'phase-item-row';

    row.innerHTML = `
      <div class="phase-item-main">
        <div class="phase-item-name">${escapeHtml(data.product_name || 'Producto')}</div>
        <div class="phase-item-sub">${escapeHtml(data.product_sku || 'Sin SKU')}</div>
      </div>

      <input type="hidden" name="phase_items[${phase}][${idx}][product_id]" value="${escapeHtml(data.product_id || '')}">
      <input type="hidden" name="phase_items[${phase}][${idx}][product_name]" value="${escapeHtml(data.product_name || '')}">
      <input type="hidden" name="phase_items[${phase}][${idx}][product_sku]" value="${escapeHtml(data.product_sku || '')}">
      <input type="hidden" name="phase_items[${phase}][${idx}][description]" value="${escapeHtml(data.description || '')}">
      <input type="hidden" name="phase_items[${phase}][${idx}][brand]" value="${escapeHtml(data.brand || '')}">
      <input type="hidden" name="phase_items[${phase}][${idx}][model]" value="${escapeHtml(data.model || '')}">

      <input type="text" class="phase-inline-input" name="phase_items[${phase}][${idx}][location_code]" value="${escapeHtml(data.location_code || '')}" placeholder="Ubicación">
      <input type="number" min="1" class="phase-inline-input" name="phase_items[${phase}][${idx}][quantity_required]" value="${Number(data.quantity_required || 1)}">
      <button type="button" class="pk-remove-btn remove-phase-item-btn">×</button>
    `;

    row.querySelector('.remove-phase-item-btn').addEventListener('click', function(){
      row.remove();
      const list = document.getElementById(`phase-items-${phase}`);
      if(list && !list.querySelector('.phase-item-row')){
        const empty = document.createElement('div');
        empty.className = 'phase-empty';
        empty.textContent = 'Sin productos en esta fase.';
        list.appendChild(empty);
      }
    });

    return row;
  }

  function nextIndexForPhase(phase){
    const list = document.getElementById(`phase-items-${phase}`);
    if(!list) return 0;
    return list.querySelectorAll('.phase-item-row').length;
  }

  function addItemToPhase(phase, data){
    const list = document.getElementById(`phase-items-${phase}`);
    if(!list) return;

    const empty = list.querySelector('.phase-empty');
    if(empty) empty.remove();

    const idx = nextIndexForPhase(phase);
    list.appendChild(makeItemRow(phase, idx, data));
  }

  function escapeHtml(str){
    return String(str ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  document.addEventListener('input', function(e){
    if(e.target.matches('.phase-product-search')){
      const card = e.target.closest('.pk-phase-card');
      const product = guessProduct(e.target.value);
      updatePreview(card, product);

      const locationInput = card.querySelector('.phase-product-location');
      if(product && locationInput && !locationInput.value){
        locationInput.value = product.default_location_code || '';
      }
    }
  });

  document.addEventListener('click', function(e){
    const btn = e.target.closest('.add-phase-product-btn');
    if(!btn) return;

    const phase = Number(btn.dataset.phase);
    const card = btn.closest('.pk-phase-card');
    const search = card.querySelector('.phase-product-search');
    const qty = card.querySelector('.phase-product-qty');
    const location = card.querySelector('.phase-product-location');
    const mode = card.querySelector('.phase-duplicate-mode');

    const product = guessProduct(search.value);
    let payload = null;

    if(product){
      payload = {
        product_id: product.id || '',
        product_name: product.name || '',
        product_sku: product.sku || '',
        location_code: location.value || product.default_location_code || '',
        quantity_required: Number(qty.value || 1),
        description: product.description || '',
        brand: product.brand || product.brand_name || '',
        model: product.model || product.model_name || '',
      };
    } else {
      const raw = String(search.value || '').trim();
      if(!raw) return;

      const parts = raw.split('·');
      payload = {
        product_id: '',
        product_name: parts.length > 1 ? parts.slice(1).join('·').trim() : raw,
        product_sku: parts.length > 1 ? parts[0].trim() : '',
        location_code: location.value || '',
        quantity_required: Number(qty.value || 1),
        description: '',
        brand: '',
        model: '',
      };
    }

    if(mode.value === 'all'){
      document.querySelectorAll('.pk-phase-card').forEach(phaseCard => {
        addItemToPhase(Number(phaseCard.dataset.phase), payload);
      });
    } else {
      addItemToPhase(phase, payload);
    }

    search.value = '';
    qty.value = 1;
    location.value = '';
    updatePreview(card, null);
  });
})();
</script>
@endpush