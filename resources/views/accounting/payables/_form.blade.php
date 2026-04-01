<div class="ac-form">
  <div class="ac-grid">
    <div class="ac-col6">
      <label>Compañía *</label>
      <select name="company_id" class="ac-inp" required>
        <option value="">-- Seleccionar --</option>
        @foreach($companies as $c)
          <option value="{{ $c->id }}" @selected(old('company_id',$item->company_id)==$c->id)>{{ $c->name }}</option>
        @endforeach
      </select>
      @error('company_id')<div class="ac-muted">{{ $message }}</div>@enderror
    </div>

    <div class="ac-col6">
      <label>Nombre del pago / cuenta *</label>
      <input class="ac-inp" name="title" required value="{{ old('title',$item->title) }}" placeholder="Ej: Renta Marzo">
    </div>

    <div class="ac-col6">
      <label>Proveedor</label>
      <input class="ac-inp" name="supplier_name" value="{{ old('supplier_name',$item->supplier_name) }}" placeholder="Nombre del proveedor">
    </div>

    <div class="ac-col6">
      <label>Folio</label>
      <input class="ac-inp" name="folio" value="{{ old('folio',$item->folio) }}" placeholder="Ej: FAC-889">
    </div>

    <div class="ac-col6">
      <label>Categoría *</label>
      <select class="ac-inp" name="category" required>
        @foreach(['impuestos','cuentas_por_pagar','servicios','nomina','seguros','retenciones','otros'] as $c)
          <option value="{{ $c }}" @selected(old('category',$item->category)===$c)>{{ $c }}</option>
        @endforeach
      </select>
    </div>

    <div class="ac-col6">
      <label>Frecuencia *</label>
      <select class="ac-inp" name="frequency" required>
        @foreach(['unico','mensual','bimestral','trimestral','semestral','anual'] as $f)
          <option value="{{ $f }}" @selected(old('frequency',$item->frequency)===$f)>{{ $f }}</option>
        @endforeach
      </select>
    </div>

    <div class="ac-col6">
      <label>Estado *</label>
      <select class="ac-inp" name="status" required>
        @foreach(['pendiente','urgente','parcial','pagado','atrasado','cancelado'] as $s)
          <option value="{{ $s }}" @selected(old('status',$item->status)===$s)>{{ $s }}</option>
        @endforeach
      </select>
    </div>

    <div class="ac-col6">
      <label>Monto *</label>
      <input class="ac-inp" type="number" step="0.01" name="amount" required value="{{ old('amount',$item->amount) }}">
    </div>

    <div class="ac-col6">
      <label>Moneda</label>
      <select class="ac-inp" name="currency">
        @foreach(['MXN','USD','EUR'] as $m)
          <option value="{{ $m }}" @selected(old('currency',$item->currency)===$m)>{{ $m }}</option>
        @endforeach
      </select>
    </div>

    <div class="ac-col6">
      <label>Fecha de emisión</label>
      <input class="ac-inp" type="date" name="issue_date" value="{{ old('issue_date', optional($item->issue_date)->toDateString()) }}">
    </div>

    <div class="ac-col6">
      <label>Fecha de vencimiento *</label>
      <input class="ac-inp" type="date" name="due_date" required value="{{ old('due_date', optional($item->due_date)->toDateString()) }}">
    </div>

    <div class="ac-col6">
      <label>Recordar (días antes)</label>
      <input class="ac-inp" type="number" name="reminder_days_before" value="{{ old('reminder_days_before',$item->reminder_days_before ?? 3) }}">
    </div>

    <div class="ac-col6">
      <label>Vencimiento retención (opcional)</label>
      <input class="ac-inp" type="date" name="retention_expiry" value="{{ old('retention_expiry', optional($item->retention_expiry)->toDateString()) }}">
    </div>

    <div class="ac-col12">
      <label>Descripción</label>
      <textarea class="ac-inp" name="description">{{ old('description',$item->description) }}</textarea>
    </div>

    <div class="ac-col12">
      <label>Notas</label>
      <textarea class="ac-inp" name="notes">{{ old('notes',$item->notes) }}</textarea>
    </div>

    <div class="ac-col6">
      <label>Comprobante (evidence) (opcional)</label>
      <input class="ac-inp" type="file" name="evidence" />
    </div>

    <div class="ac-col6">
      <label>Documentos</label>
      <input class="ac-inp" type="file" name="documents[]" multiple />
    </div>

    <div class="ac-col12">
      <label>expense_id (opcional para ligar gasto)</label>
      <input class="ac-inp" type="number" name="expense_id" value="{{ old('expense_id',$item->expense_id) }}" placeholder="ID del gasto en tu sistema (si aplica)">
    </div>
  </div>
</div>