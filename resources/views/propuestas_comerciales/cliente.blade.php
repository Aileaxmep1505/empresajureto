@extends('layouts.app')

@section('content')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  .client-quote-page {
    --bg: #f9fafb;
    --card: #ffffff;
    --ink: #333333;
    --muted: #888888;
    --line: #ebebeb;
    --blue: #007aff;
    --blue-soft: #e6f0ff;
    --success: #15803d;
    --success-soft: #e6ffe6;
    --danger: #ff4a4a;
    --danger-soft: #ffebeb;

    min-height: 100vh;
    background: var(--bg);
    font-family: 'Quicksand', sans-serif;
    color: var(--ink);
    padding: 36px 0 70px;
  }

  .client-quote-page,
  .client-quote-page * {
    box-sizing: border-box;
  }

  .client-quote-page .wrap {
    width: 90vw;
    max-width: 1500px;
    margin: 0 auto;
  }

  .client-quote-page .topbar {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
    margin-bottom: 30px;
  }

  .client-quote-page .back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #111;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    margin-bottom: 28px;
  }

  .client-quote-page .back-link:hover {
    color: var(--blue);
  }

  .client-quote-page .title {
    margin: 0;
    color: #111;
    font-size: 28px;
    font-weight: 700;
    letter-spacing: -.03em;
  }

  .client-quote-page .subtitle {
    margin-top: 8px;
    color: var(--muted);
    font-size: 15px;
    font-weight: 600;
  }

  .client-quote-page .actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    padding-top: 54px;
    justify-content: flex-end;
  }

  .client-quote-page .btn {
    min-height: 42px;
    border-radius: 13px;
    border: 1px solid var(--line);
    background: #fff;
    color: #111;
    padding: 0 17px;
    font-family: 'Quicksand', sans-serif;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: .22s ease;
  }

  .client-quote-page .btn:hover {
    transform: translateY(-1px);
    background: #f9fafb;
  }

  .client-quote-page .btn:active {
    transform: scale(.98);
  }

  .client-quote-page .btn-primary {
    background: var(--blue);
    border-color: var(--blue);
    color: #fff;
    box-shadow: 0 10px 24px rgba(0,122,255,.15);
  }

  .client-quote-page .btn-primary:hover {
    background: var(--blue);
    box-shadow: 0 16px 34px rgba(0,122,255,.22);
  }

  .client-quote-page .alert-success {
    background: var(--success-soft);
    color: var(--success);
    border: 1px solid rgba(21,128,61,.18);
    border-radius: 14px;
    padding: 14px 16px;
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 18px;
  }

  .client-quote-page .document-card {
    width: min(780px, 100%);
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 18px;
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
    padding: 50px 46px;
  }

  .client-quote-page .document-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 28px;
    border-bottom: 1px solid var(--line);
    padding-bottom: 30px;
    margin-bottom: 28px;
  }

  .client-quote-page .brand {
    display: flex;
    align-items: center;
    gap: 14px;
  }

  .client-quote-page .brand-icon {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: var(--blue);
    color: #fff;
    display: grid;
    place-items: center;
    font-weight: 700;
    font-size: 20px;
  }

  .client-quote-page .brand-name {
    color: #111;
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 4px;
  }

  .client-quote-page .brand-info,
  .client-quote-page .quote-info,
  .client-quote-page .client-info {
    color: var(--muted);
    font-size: 10.5px;
    line-height: 1.55;
    font-weight: 500;
  }

  .client-quote-page .quote-box {
    text-align: right;
  }

  .client-quote-page .quote-label {
    color: var(--muted);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    margin-bottom: 7px;
  }

  .client-quote-page .quote-folio {
    color: var(--blue);
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 8px;
  }

  .client-quote-page .section-label {
    color: var(--muted);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .13em;
    text-transform: uppercase;
    margin-bottom: 10px;
  }

  .client-quote-page .client-name {
    color: #111;
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 4px;
  }

  .client-quote-page .client-block {
    margin-bottom: 38px;
  }

  .client-quote-page table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 16px;
  }

  .client-quote-page th {
    text-align: left;
    color: #687082;
    font-size: 11px;
    font-weight: 700;
    padding: 11px 0;
    border-bottom: 1px solid var(--line);
  }

  .client-quote-page td {
    color: #111;
    font-size: 11px;
    font-weight: 600;
    padding: 14px 0;
    border-bottom: 1px solid var(--line);
    vertical-align: top;
  }

  .client-quote-page .text-right {
    text-align: right;
  }

  .client-quote-page .totals {
    width: min(300px, 100%);
    margin-left: auto;
    margin-top: 26px;
  }

  .client-quote-page .total-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 16px;
    padding: 7px 0;
    color: var(--muted);
    font-size: 13px;
    font-weight: 600;
  }

  .client-quote-page .total-row strong {
    color: #111;
  }

  .client-quote-page .total-row.final {
    border-top: 1px solid var(--line);
    margin-top: 6px;
    padding-top: 13px;
    color: #111;
    font-size: 17px;
    font-weight: 700;
  }

  .client-quote-page .total-row.final strong {
    color: var(--blue);
    font-size: 17px;
  }

  .client-quote-page .footer-note {
    border-top: 1px solid var(--line);
    margin-top: 42px;
    padding-top: 20px;
    color: var(--muted);
    font-size: 11px;
    line-height: 1.7;
  }

  .client-quote-page .modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(0,0,0,.18);
    backdrop-filter: blur(6px);
  }

  .client-quote-page .modal-backdrop.show {
    display: flex;
  }

  .client-quote-page .modal {
    width: min(520px, 100%);
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 18px;
    box-shadow: 0 24px 80px rgba(0,0,0,.12);
    padding: 22px;
  }

  .client-quote-page .modal-title {
    margin: 0;
    color: #111;
    font-size: 20px;
    font-weight: 700;
  }

  .client-quote-page .modal-subtitle {
    color: var(--muted);
    font-size: 13px;
    margin: 7px 0 18px;
  }

  .client-quote-page .field {
    display: grid;
    gap: 7px;
    margin-bottom: 13px;
  }

  .client-quote-page .field label {
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
  }

  .client-quote-page .input,
  .client-quote-page .textarea {
    width: 100%;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: #fff;
    color: #111;
    font-family: 'Quicksand', sans-serif;
    font-size: 14px;
    font-weight: 600;
    outline: none;
    transition: .2s ease;
  }

  .client-quote-page .input {
    height: 42px;
    padding: 0 13px;
  }

  .client-quote-page .textarea {
    min-height: 110px;
    padding: 12px 13px;
    resize: vertical;
  }

  .client-quote-page .input:focus,
  .client-quote-page .textarea:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  @media print {
    .client-quote-page {
      background: #fff;
      padding: 0;
    }

    .client-quote-page .topbar,
    .client-quote-page .actions,
    .client-quote-page .back-link,
    .client-quote-page .modal-backdrop {
      display: none !important;
    }

    .client-quote-page .wrap {
      width: 100%;
      max-width: none;
    }

    .client-quote-page .document-card {
      border: 0;
      box-shadow: none;
      width: 100%;
      padding: 0;
    }
  }

  @media (max-width: 900px) {
    .client-quote-page .topbar {
      flex-direction: column;
    }

    .client-quote-page .actions {
      padding-top: 0;
      justify-content: flex-start;
    }

    .client-quote-page .document-card {
      width: 100%;
      padding: 34px 24px;
    }
  }

  @media (max-width: 640px) {
    .client-quote-page {
      padding: 24px 0 46px;
    }

    .client-quote-page .wrap {
      width: calc(100vw - 24px);
    }

    .client-quote-page .document-head {
      flex-direction: column;
    }

    .client-quote-page .quote-box {
      text-align: left;
    }

    .client-quote-page .btn {
      width: 100%;
    }

    .client-quote-page table {
      min-width: 620px;
    }

    .client-quote-page .table-scroll {
      overflow-x: auto;
    }
  }
</style>

<div class="client-quote-page">
  <div class="wrap">
    <a href="{{ route('propuestas-comerciales.show', $propuestaComercial) }}" class="back-link">
      ← Regresar
    </a>

    <div class="topbar">
      <div>
        <h1 class="title">Cotización para el cliente</h1>
        <div class="subtitle">{{ $folio }}</div>
      </div>

      <div class="actions">
        <button type="button" class="btn" onclick="window.print()">▣ Imprimir</button>
        <button type="button" class="btn" onclick="openEmailModal()">✉ Enviar por correo</button>
        <a href="{{ route('propuestas-comerciales.cliente.pdf', $propuestaComercial) }}" class="btn btn-primary">
          ↓ Descargar PDF
        </a>
      </div>
    </div>

    @if(session('status'))
      <div class="alert-success">{{ session('status') }}</div>
    @endif

    <div class="document-card">
      <div class="document-head">
        <div class="brand">
          <div class="brand-icon">✦</div>
          <div>
            <div class="brand-name">{{ $company['name'] }}</div>
            <div class="brand-info">
              {{ $company['address'] }}<br>
              {{ $company['phone'] }} · {{ $company['email'] }}<br>
              RFC: {{ $company['rfc'] }}
            </div>
          </div>
        </div>

        <div class="quote-box">
          <div class="quote-label">Cotización</div>
          <div class="quote-folio">{{ $folio }}</div>
          <div class="quote-info">
            {{ $createdAt->format('d/m/Y') }}<br>
            Vigencia: 15 días
          </div>
        </div>
      </div>

      <div class="client-block">
        <div class="section-label">Datos del cliente</div>
        <div class="client-name">{{ $client['name'] }}</div>
        <div class="client-info">
          {{ $client['attention'] }}<br>
          @if($client['email']) {{ $client['email'] }}<br> @endif
          @if($client['phone']) {{ $client['phone'] }}<br> @endif
          @if($client['address']) {{ $client['address'] }}<br> @endif
          @if($client['rfc']) RFC: {{ $client['rfc'] }} @endif
        </div>
      </div>

      <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th style="width:50px;">#</th>
              <th style="width:90px;" class="text-right">Cantidad</th>
              <th style="width:90px;">Unidad</th>
              <th>Descripción</th>
              <th style="width:120px;" class="text-right">P. Unitario</th>
              <th style="width:120px;" class="text-right">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            @foreach($items as $item)
              <tr>
                <td>{{ $item['number'] }}</td>
                <td class="text-right">{{ number_format($item['quantity'], 0) }}</td>
                <td>{{ $item['unit'] }}</td>
                <td>{{ $item['description'] }}</td>
                <td class="text-right">${{ number_format($item['price'], 2) }}</td>
                <td class="text-right"><strong>${{ number_format($item['subtotal'], 2) }}</strong></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="totals">
        <div class="total-row">
          <span>Subtotal</span>
          <strong>${{ number_format($subtotal, 2) }}</strong>
        </div>

        @if($discount > 0)
          <div class="total-row">
            <span>Descuento</span>
            <strong>-${{ number_format($discount, 2) }}</strong>
          </div>
        @endif

        <div class="total-row">
          <span>IVA ({{ number_format($taxPercent, 0) }}%)</span>
          <strong>${{ number_format($tax, 2) }}</strong>
        </div>

        <div class="total-row final">
          <span>Total</span>
          <strong>${{ number_format($total, 2) }}</strong>
        </div>
      </div>

      <div class="footer-note">
        Esta cotización tiene una vigencia de 15 días naturales a partir de su fecha de emisión.
        Precios sujetos a disponibilidad y validación final. Gracias por considerar nuestra propuesta.
      </div>
    </div>
  </div>

  <div class="modal-backdrop" id="emailModal">
    <div class="modal">
      <h2 class="modal-title">Enviar cotización por correo</h2>
      <p class="modal-subtitle">Se enviará la cotización en PDF como archivo adjunto.</p>

      <form method="POST" action="{{ route('propuestas-comerciales.cliente.email', $propuestaComercial) }}">
        @csrf

        <div class="field">
          <label>Correo del cliente</label>
          <input class="input" type="email" name="email" value="{{ $client['email'] }}" required>
        </div>

        <div class="field">
          <label>Asunto</label>
          <input class="input" name="subject" value="Cotización {{ $folio }}">
        </div>

        <div class="field">
          <label>Mensaje</label>
          <textarea class="textarea" name="message">Hola, adjuntamos la cotización solicitada. Quedamos atentos a tus comentarios.</textarea>
        </div>

        <div style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap; margin-top:16px;">
          <button class="btn" type="button" onclick="closeEmailModal()">Cancelar</button>
          <button class="btn btn-primary" type="submit">Enviar cotización</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function openEmailModal() {
    document.getElementById('emailModal').classList.add('show');
  }

  function closeEmailModal() {
    document.getElementById('emailModal').classList.remove('show');
  }
</script>
@endsection