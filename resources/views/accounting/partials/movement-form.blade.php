@php
  $relatedType = old('related_type', $relatedType ?? 'receivable');
  $relatedId   = old('related_id', $relatedId ?? '');
  $movementDate = old('movement_date', now()->toDateString());
  $amount = old('amount', '');
  $currency = old('currency', 'MXN');
  $method = old('method', 'transferencia');
  $reference = old('reference', '');
  $notes = old('notes', '');
@endphp

<style>
  .mv-wrap{
    margin-top:18px;
    border:1px solid #e5e7eb;
    border-radius:22px;
    background:#fff;
    box-shadow:0 10px 30px rgba(15,23,42,.06);
    overflow:hidden;
  }

  .mv-head{
    padding:18px 20px;
    border-bottom:1px solid #eef2f7;
    background:linear-gradient(180deg,#fbfdff,#f8fbff);
  }

  .mv-title{
    font-size:1.08rem;
    font-weight:900;
    color:#0f172a;
    margin:0;
  }

  .mv-sub{
    margin-top:4px;
    color:#64748b;
    font-size:.93rem;
  }

  .mv-body{
    padding:20px;
  }

  .mv-grid{
    display:grid;
    grid-template-columns:repeat(2, minmax(0,1fr));
    gap:16px;
  }

  .mv-col-2{
    grid-column:span 2;
  }

  .mv-field label{
    display:block;
    margin-bottom:7px;
    font-weight:800;
    color:#334155;
    font-size:.93rem;
  }

  .mv-input,
  .mv-select,
  .mv-textarea,
  .mv-file{
    width:100%;
    border:1px solid #dbe3ee;
    background:#fff;
    border-radius:14px;
    padding:12px 14px;
    outline:none;
    transition:.18s ease;
    font-size:.96rem;
    color:#0f172a;
  }

  .mv-input:focus,
  .mv-select:focus,
  .mv-textarea:focus,
  .mv-file:focus{
    border-color:#93c5fd;
    box-shadow:0 0 0 4px rgba(37,99,235,.10);
  }

  .mv-textarea{
    resize:vertical;
    min-height:100px;
  }

  .mv-type{
    display:grid;
    grid-template-columns:repeat(2, minmax(0,1fr));
    gap:10px;
    margin-bottom:18px;
  }

  .mv-chip{
    position:relative;
  }

  .mv-chip input{
    position:absolute;
    inset:0;
    opacity:0;
    cursor:pointer;
  }

  .mv-chip span{
    display:flex;
    align-items:center;
    justify-content:center;
    padding:13px 14px;
    border-radius:16px;
    border:1px solid #dbe3ee;
    background:#fff;
    font-weight:900;
    color:#334155;
    transition:.18s ease;
  }

  .mv-chip input:checked + span{
    background:#eff6ff;
    border-color:#93c5fd;
    color:#1d4ed8;
  }

  .mv-upload{
    border:2px dashed #dbe3ee;
    border-radius:18px;
    padding:14px;
    background:#fafcff;
  }

  .mv-actions{
    display:flex;
    justify-content:flex-end;
    gap:12px;
    margin-top:22px;
  }

  .mv-btn{
    border:none;
    border-radius:14px;
    padding:12px 18px;
    font-weight:900;
    cursor:pointer;
  }

  .mv-btn-primary{
    background:linear-gradient(180deg,#2563eb,#1d4ed8);
    color:#fff;
    min-width:180px;
  }

  .mv-btn-secondary{
    background:#fff;
    color:#0f172a;
    border:1px solid #dbe3ee;
    text-decoration:none;
  }

  @media (max-width: 720px){
    .mv-grid,
    .mv-type{
      grid-template-columns:1fr;
    }

    .mv-col-2{
      grid-column:span 1;
    }

    .mv-actions{
      flex-direction:column-reverse;
    }

    .mv-btn,
    .mv-btn-secondary{
      width:100%;
      text-align:center;
    }
  }
</style>

<div class="mv-wrap">
  <div class="mv-head">
    <h3 class="mv-title">Registrar movimiento</h3>
    <div class="mv-sub">Usa el mismo formulario para cobros y pagos, sin campos revueltos.</div>
  </div>

  <form method="POST" action="{{ route('accounting.movements.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="mv-body">
      <div class="mv-type">
        <label class="mv-chip">
          <input type="radio" name="related_type" value="receivable" {{ $relatedType === 'receivable' ? 'checked' : '' }}>
          <span>Cuenta por cobrar</span>
        </label>

        <label class="mv-chip">
          <input type="radio" name="related_type" value="payable" {{ $relatedType === 'payable' ? 'checked' : '' }}>
          <span>Cuenta por pagar</span>
        </label>
      </div>

      <div class="mv-grid">
        <div class="mv-field">
          <label for="related_id">ID relacionado</label>
          <input
            id="related_id"
            name="related_id"
            type="number"
            min="1"
            class="mv-input"
            value="{{ $relatedId }}"
            required
          >
        </div>

        <div class="mv-field">
          <label for="movement_date">Fecha del movimiento</label>
          <input
            id="movement_date"
            name="movement_date"
            type="date"
            class="mv-input"
            value="{{ $movementDate }}"
            required
          >
        </div>

        <div class="mv-field">
          <label for="amount">Monto</label>
          <input
            id="amount"
            name="amount"
            type="number"
            step="0.01"
            min="0.01"
            class="mv-input"
            value="{{ $amount }}"
            placeholder="0.00"
            required
          >
        </div>

        <div class="mv-field">
          <label for="currency">Moneda</label>
          <select id="currency" name="currency" class="mv-select" required>
            <option value="MXN" {{ $currency === 'MXN' ? 'selected' : '' }}>MXN</option>
            <option value="USD" {{ $currency === 'USD' ? 'selected' : '' }}>USD</option>
            <option value="EUR" {{ $currency === 'EUR' ? 'selected' : '' }}>EUR</option>
          </select>
        </div>

        <div class="mv-field">
          <label for="method">Método</label>
          <select id="method" name="method" class="mv-select">
            <option value="">Selecciona</option>
            <option value="transferencia" {{ $method === 'transferencia' ? 'selected' : '' }}>Transferencia</option>
            <option value="efectivo" {{ $method === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
            <option value="tarjeta" {{ $method === 'tarjeta' ? 'selected' : '' }}>Tarjeta</option>
            <option value="cheque" {{ $method === 'cheque' ? 'selected' : '' }}>Cheque</option>
            <option value="otro" {{ $method === 'otro' ? 'selected' : '' }}>Otro</option>
          </select>
        </div>

        <div class="mv-field">
          <label for="reference">Referencia</label>
          <input
            id="reference"
            name="reference"
            type="text"
            class="mv-input"
            value="{{ $reference }}"
            placeholder="Folio, transferencia, cheque..."
          >
        </div>

        <div class="mv-field mv-col-2">
          <label for="notes">Notas</label>
          <textarea
            id="notes"
            name="notes"
            class="mv-textarea"
            placeholder="Detalles del movimiento..."
          >{{ $notes }}</textarea>
        </div>

        <div class="mv-field mv-col-2">
          <label for="evidence">Comprobante</label>
          <div class="mv-upload">
            <input id="evidence" name="evidence" type="file" class="mv-file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xlsx">
          </div>
        </div>

        <div class="mv-field mv-col-2">
          <label for="documents">Documentos adicionales</label>
          <div class="mv-upload">
            <input id="documents" name="documents[]" type="file" class="mv-file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xlsx,.zip">
          </div>
        </div>
      </div>

      <div class="mv-actions">
        <a href="{{ url()->previous() }}" class="mv-btn mv-btn-secondary">Cancelar</a>
        <button type="submit" class="mv-btn mv-btn-primary">Guardar movimiento</button>
      </div>
    </div>
  </form>
</div>