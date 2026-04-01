@php
  $isEdit = !empty($item->id);
@endphp

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
      <label>Cliente *</label>
      <input class="ac-inp" name="client_name" required value="{{ old('client_name',$item->client_name) }}" placeholder="Nombre del cliente">
      @error('client_name')<div class="ac-muted">{{ $message }}</div>@enderror
    </div>

    <div class="ac-col6">
      <label>Folio / Factura</label>
      <input class="ac-inp" name="folio" value="{{ old('folio',$item->folio) }}" placeholder="Ej: FAC-001">
    </div>

    <div class="ac-col6">
      <label>Tipo de documento *</label>
      <select class="ac-inp" name="document_type" required>
        @foreach(['factura','nota_credito','cargo_adicional','anticipo'] as $t)
          <option value="{{ $t }}" @selected(old('document_type',$item->document_type)===$t)>{{ $t }}</option>
        @endforeach
      </select>
    </div>

    <div class="ac-col6">
      <label>Categoría *</label>
      <select class="ac-inp" name="category" required>
        @foreach(['factura','honorarios','renta','servicios','producto','otro'] as $t)
          <option value="{{ $t }}" @selected(old('category',$item->category)===$t)>{{ ucfirst($t) }}</option>
        @endforeach
      </select>
    </div>

    <div class="ac-col6">
      <label>Estado *</label>
      <select class="ac-inp" name="status" required>
        @foreach(['pendiente','parcial','cobrado','vencido','cancelado'] as $t)
          <option value="{{ $t }}" @selected(old('status',$item->status)===$t)>{{ ucfirst($t) }}</option>
        @endforeach
      </select>
    </div>

    <div class="ac-col6">
      <label>Monto total *</label>
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
      <label>Prioridad</label>
      <select class="ac-inp" name="priority">
        @foreach(['alta','media','baja'] as $p)
          <option value="{{ $p }}" @selected(old('priority',$item->priority)===$p)>{{ ucfirst($p) }}</option>
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
      <input class="ac-inp" type="number" name="reminder_days_before" value="{{ old('reminder_days_before',$item->reminder_days_before ?? 5) }}">
    </div>

    <div class="ac-col12">
      <label>Concepto</label>
      <textarea class="ac-inp" name="description" placeholder="Descripción del servicio o producto">{{ old('description',$item->description) }}</textarea>
    </div>

    <div class="ac-col12">
      <label>Notas</label>
      <textarea class="ac-inp" name="notes" placeholder="Notas internas, seguimiento...">{{ old('notes',$item->notes) }}</textarea>
    </div>

    <div class="ac-col6">
      <label>Comprobante (evidence) (opcional)</label>
      <input class="ac-inp" type="file" name="evidence" />
    </div>

    <div class="ac-col6">
      <label>Documentos (facturas, contratos, etc.)</label>
      <input class="ac-inp" type="file" name="documents[]" multiple />
    </div>
  </div>
</div>