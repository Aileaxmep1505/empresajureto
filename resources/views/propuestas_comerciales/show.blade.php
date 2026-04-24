@extends('layouts.app')

@section('content')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  :root {
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
  }

  * { box-sizing: border-box; }

  .pc-page {
    font-family: 'Quicksand', sans-serif;
    background: var(--bg);
    min-height: 100vh;
    padding: 32px;
    color: var(--ink);
  }

  .pc-wrap {
    max-width: 1440px;
    margin: 0 auto;
  }

  .pc-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 18px;
    margin-bottom: 24px;
  }

  .pc-title {
    margin: 0;
    font-size: 30px;
    font-weight: 700;
    color: #111;
  }

  .pc-subtitle {
    margin: 8px 0 0;
    font-size: 14px;
    color: var(--muted);
    line-height: 1.6;
  }

  .pc-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  .btn {
    appearance: none;
    border: 1px solid transparent;
    border-radius: 10px;
    padding: 11px 16px;
    font-family: 'Quicksand', sans-serif;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: .2s ease;
    background: transparent;
  }

  .btn:active { transform: scale(.98); }

  .btn-primary {
    background: var(--blue);
    color: #fff;
  }

  .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 22px rgba(0,122,255,.12);
  }

  .btn-outline {
    background: #fff;
    color: var(--blue);
    border-color: var(--blue);
  }

  .btn-outline:hover {
    background: var(--blue-soft);
    transform: translateY(-1px);
  }

  .btn-ghost {
    background: transparent;
    color: #555;
    border-color: var(--line);
  }

  .btn-ghost:hover {
    background: #f9fafb;
  }

  .pc-alert {
    margin-bottom: 18px;
    padding: 14px 16px;
    border-radius: 12px;
    font-weight: 600;
    border: 1px solid var(--line);
  }

  .pc-alert-success {
    background: var(--success-soft);
    color: var(--success);
  }

  .pc-alert-danger {
    background: var(--danger-soft);
    color: var(--danger);
  }

  .pc-grid-top {
    display: grid;
    grid-template-columns: 1.2fr .8fr;
    gap: 18px;
    margin-bottom: 18px;
  }

  .pc-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    transition: .2s ease;
  }

  .pc-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(0,0,0,0.04);
  }

  .pc-card-head {
    padding: 18px 20px 10px;
  }

  .pc-card-title {
    margin: 0;
    font-size: 17px;
    font-weight: 700;
    color: #111;
  }

  .pc-card-subtitle {
    margin: 8px 0 0;
    color: var(--muted);
    font-size: 13px;
  }

  .pc-card-body {
    padding: 18px 20px 20px;
  }

  .meta-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
  }

  .meta-item {
    padding: 14px;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: #fff;
  }

  .meta-item span {
    display: block;
    color: var(--muted);
    font-size: 12px;
    margin-bottom: 6px;
    font-weight: 700;
  }

  .meta-item strong {
    color: #111;
    font-size: 14px;
    line-height: 1.5;
  }

  .field {
    margin-bottom: 14px;
  }

  .field label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: var(--muted);
    margin-bottom: 8px;
  }

  .field input {
    width: 100%;
    border: 1px solid var(--line);
    background: #fff;
    border-radius: 10px;
    padding: 12px 14px;
    font-family: 'Quicksand', sans-serif;
    font-size: 14px;
    color: var(--ink);
    outline: none;
    transition: .2s ease;
  }

  .field input:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .badge {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
  }

  .badge-info {
    background: var(--blue-soft);
    color: var(--blue);
  }

  .badge-success {
    background: var(--success-soft);
    color: var(--success);
  }

  .badge-danger {
    background: var(--danger-soft);
    color: var(--danger);
  }

  .badge-muted {
    background: #f3f4f6;
    color: #666;
  }

  .pc-section {
    margin-top: 18px;
  }

  .pc-table-card {
    overflow: hidden;
  }

  .pc-table-wrap {
    overflow-x: auto;
  }

  .pc-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1320px;
  }

  .pc-table th,
  .pc-table td {
    padding: 14px 16px;
    border-bottom: 1px solid var(--line);
    text-align: left;
    vertical-align: top;
    font-size: 13px;
  }

  .pc-table th {
    color: var(--muted);
    font-size: 12px;
    font-weight: 700;
    background: #fcfcfc;
  }

  .pc-table tr:last-child td { border-bottom: none; }

  .desc {
    min-width: 260px;
  }

  .desc strong {
    display: block;
    font-size: 14px;
    color: #111;
    margin-bottom: 6px;
  }

  .desc small {
    color: var(--muted);
    line-height: 1.5;
  }

  .match-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 260px;
  }

  .match-card {
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 12px;
    background: #fff;
  }

  .match-name {
    font-weight: 700;
    color: #111;
    margin-bottom: 5px;
  }

  .match-mini {
    color: var(--muted);
    font-size: 12px;
    line-height: 1.5;
    margin-bottom: 10px;
  }

  .inline-form {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .inline-form input {
    width: 100%;
    border: 1px solid var(--line);
    border-radius: 8px;
    padding: 10px 12px;
    font-family: 'Quicksand', sans-serif;
    font-size: 13px;
    outline: none;
  }

  .inline-form input:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .stack-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }

  .pc-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
  }

  .list-box {
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 14px;
    background: #fff;
  }

  .list-box ul {
    margin: 0;
    padding-left: 18px;
  }

  .list-box li {
    margin: 0 0 10px;
    line-height: 1.6;
    font-size: 14px;
  }

  .empty-note {
    color: var(--muted);
    font-size: 14px;
  }

  @media (max-width: 1100px) {
    .pc-page { padding: 20px; }
    .pc-head,
    .pc-grid-top,
    .pc-columns {
      grid-template-columns: 1fr;
      flex-direction: column;
    }
  }
</style>

<div class="pc-page">
  <div class="pc-wrap">
    <div class="pc-head">
      <div>
        <h1 class="pc-title">{{ $propuestaComercial->titulo ?: 'Propuesta comercial' }}</h1>
        <p class="pc-subtitle">
          Folio: <strong>{{ $propuestaComercial->folio ?: '—' }}</strong> ·
          Cliente: <strong>{{ $propuestaComercial->cliente ?: '—' }}</strong> ·
          AI Run: <strong>{{ $propuestaComercial->document_ai_run_id ?: '—' }}</strong>
        </p>
      </div>

      <div class="pc-actions">
        <a href="{{ route('propuestas-comerciales.index') }}" class="btn btn-ghost">Volver</a>

        <form method="POST" action="{{ route('propuestas-comerciales.suggest-all', $propuestaComercial) }}">
          @csrf
          <button type="submit" class="btn btn-outline">Buscar coincidencias</button>
        </form>

        <a href="#" class="btn btn-primary">Exportar Word</a>
        <a href="#" class="btn btn-outline">Exportar Excel</a>
      </div>
    </div>

    @if(session('status'))
      <div class="pc-alert pc-alert-success">{{ session('status') }}</div>
    @endif

    @if(session('error'))
      <div class="pc-alert pc-alert-danger">{{ session('error') }}</div>
    @endif

    <div class="pc-grid-top">
      <div class="pc-card">
        <div class="pc-card-head">
          <h3 class="pc-card-title">Datos generales</h3>
          <p class="pc-card-subtitle">Información base de la propuesta y del documento analizado.</p>
        </div>
        <div class="pc-card-body">
          <div class="meta-grid">
            <div class="meta-item">
              <span>Folio</span>
              <strong>{{ $propuestaComercial->folio ?: '—' }}</strong>
            </div>
            <div class="meta-item">
              <span>Cliente</span>
              <strong>{{ $propuestaComercial->cliente ?: '—' }}</strong>
            </div>
            <div class="meta-item">
              <span>Estatus</span>
              @php
                $statusClass = match($propuestaComercial->status) {
                  'completed' => 'badge-success',
                  'priced' => 'badge-info',
                  'matched' => 'badge-info',
                  'draft' => 'badge-muted',
                  default => 'badge-danger',
                };
              @endphp
              <strong><span class="badge {{ $statusClass }}">{{ strtoupper($propuestaComercial->status) }}</span></strong>
            </div>
            <div class="meta-item">
              <span>Creada</span>
              <strong>{{ optional($propuestaComercial->created_at)->format('d/m/Y H:i') }}</strong>
            </div>
          </div>
        </div>
      </div>

      <div class="pc-card">
        <div class="pc-card-head">
          <h3 class="pc-card-title">Parámetros comerciales</h3>
          <p class="pc-card-subtitle">Utilidad, descuento e impuesto general.</p>
        </div>
        <div class="pc-card-body">
          <form method="POST" action="{{ route('propuestas-comerciales.update-pricing', $propuestaComercial) }}">
            @csrf

            <div class="field">
              <label>Porcentaje de utilidad</label>
              <input type="number" step="0.01" name="porcentaje_utilidad" value="{{ $propuestaComercial->porcentaje_utilidad }}">
            </div>

            <div class="field">
              <label>Porcentaje de descuento</label>
              <input type="number" step="0.01" name="porcentaje_descuento" value="{{ $propuestaComercial->porcentaje_descuento }}">
            </div>

            <div class="field">
              <label>Porcentaje de impuesto</label>
              <input type="number" step="0.01" name="porcentaje_impuesto" value="{{ $propuestaComercial->porcentaje_impuesto }}">
            </div>

            <button class="btn btn-primary" type="submit" style="width:100%;">Guardar parámetros</button>
          </form>
        </div>
      </div>
    </div>

    <div class="pc-columns">
      <div class="pc-card">
        <div class="pc-card-head">
          <h3 class="pc-card-title">Anexos</h3>
        </div>
        <div class="pc-card-body">
          <div class="list-box">
            @if(!empty($propuestaComercial->meta['anexos']))
              <ul>
                @foreach($propuestaComercial->meta['anexos'] as $anexo)
                  <li>
                    <strong>{{ is_array($anexo) ? ($anexo['nombre'] ?? 'Anexo') : $anexo }}</strong>
                    @if(is_array($anexo) && !empty($anexo['descripcion']))
                      <br><span class="empty-note">{{ $anexo['descripcion'] }}</span>
                    @endif
                  </li>
                @endforeach
              </ul>
            @else
              <div class="empty-note">No hay anexos registrados.</div>
            @endif
          </div>
        </div>
      </div>

      <div class="pc-card">
        <div class="pc-card-head">
          <h3 class="pc-card-title">Fechas clave</h3>
        </div>
        <div class="pc-card-body">
          <div class="list-box">
            @if(!empty($propuestaComercial->meta['fechas_clave']))
              <ul>
                @foreach($propuestaComercial->meta['fechas_clave'] as $fecha)
                  <li>
                    <strong>{{ $fecha['tipo'] ?? 'Fecha' }}</strong><br>
                    {{ $fecha['descripcion'] ?? '—' }}<br>
                    <span class="empty-note">
                      Fecha: {{ $fecha['fecha'] ?? '—' }} · Hora: {{ $fecha['hora'] ?? '—' }}
                    </span>
                  </li>
                @endforeach
              </ul>
            @else
              <div class="empty-note">No hay fechas clave registradas.</div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="pc-section pc-card pc-table-card">
      <div class="pc-card-head">
        <h3 class="pc-card-title">Renglones de propuesta</h3>
        <p class="pc-card-subtitle">
          Revisa coincidencias sugeridas, selecciona el producto correcto y aplica precio con utilidad.
        </p>
      </div>

      <div class="pc-table-wrap">
        <table class="pc-table">
          <thead>
            <tr>
              <th>Renglón</th>
              <th>Solicitud</th>
              <th>Coincidencias sugeridas</th>
              <th>Seleccionado</th>
              <th>Precio</th>
            </tr>
          </thead>
          <tbody>
            @forelse($propuestaComercial->items as $item)
              <tr>
                <td>
                  <strong>#{{ $item->sort }}</strong><br>
                  <span class="empty-note">
                    Partida: {{ $item->partida_numero ?: '—' }}<br>
                    Subpartida: {{ $item->subpartida_numero ?: '—' }}
                  </span>
                </td>

                <td class="desc">
                  <strong>{{ $item->descripcion_original }}</strong>
                  <small>
                    Unidad: {{ $item->unidad_solicitada ?: '—' }}<br>
                    Cant. mínima: {{ $item->cantidad_minima ?: '—' }}<br>
                    Cant. máxima: {{ $item->cantidad_maxima ?: '—' }}<br>
                    Cant. cotizada: {{ $item->cantidad_cotizada ?: '—' }}
                  </small>

                  <div style="margin-top:10px;">
                    <form method="POST" action="{{ route('propuesta-comercial-items.suggest', $item) }}">
                      @csrf
                      <button class="btn btn-outline" type="submit">Buscar top 3</button>
                    </form>
                  </div>
                </td>

                <td>
                  <div class="match-list">
                    @forelse($item->matches as $match)
                      <div class="match-card">
                        <div class="match-name">
                          {{ $match->product->name ?? ('Producto #' . $match->product_id) }}
                        </div>
                        <div class="match-mini">
                          SKU: {{ $match->product->sku ?? '—' }}<br>
                          Score: {{ number_format((float)$match->score, 2) }}%<br>
                          Unidad coincide:
                          @if($match->unidad_coincide)
                            <span class="badge badge-success">Sí</span>
                          @else
                            <span class="badge badge-danger">No</span>
                          @endif
                        </div>

                        <form method="POST" action="{{ route('propuesta-comercial-items.matches.select', [$item, $match]) }}">
                          @csrf
                          <button class="btn {{ $match->seleccionado ? 'btn-primary' : 'btn-outline' }}" type="submit" style="width:100%;">
                            {{ $match->seleccionado ? 'Seleccionado' : 'Elegir opción' }}
                          </button>
                        </form>
                      </div>
                    @empty
                      <div class="empty-note">Aún no hay sugerencias para este renglón.</div>
                    @endforelse
                  </div>
                </td>

                <td>
                  @if($item->productoSeleccionado)
                    <div class="match-card">
                      <div class="match-name">{{ $item->productoSeleccionado->name }}</div>
                      <div class="match-mini">
                        SKU: {{ $item->productoSeleccionado->sku ?? '—' }}<br>
                        Score: {{ number_format((float)$item->match_score, 2) }}%
                      </div>
                    </div>
                  @else
                    <div class="empty-note">Sin producto seleccionado.</div>
                  @endif
                </td>

                <td>
                  <form method="POST" action="{{ route('propuesta-comercial-items.price', $item) }}" class="inline-form">
                    @csrf

                    <input type="number" step="0.01" name="cantidad_cotizada" value="{{ $item->cantidad_cotizada ?: 1 }}" placeholder="Cantidad">
                    <input type="number" step="0.01" name="costo_unitario" value="{{ $item->costo_unitario }}" placeholder="Costo unitario">
                    <input type="number" step="0.01" name="porcentaje_utilidad" value="{{ $propuestaComercial->porcentaje_utilidad }}" placeholder="% utilidad">

                    <button class="btn btn-primary" type="submit" style="width:100%;">Aplicar precio</button>
                  </form>

                  <div style="margin-top:12px;" class="match-mini">
                    Precio unitario: <strong>${{ number_format((float)$item->precio_unitario, 2) }}</strong><br>
                    Subtotal: <strong>${{ number_format((float)$item->subtotal, 2) }}</strong>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5">
                  <div class="empty-note" style="padding:20px;">
                    Esta propuesta aún no tiene renglones.
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection