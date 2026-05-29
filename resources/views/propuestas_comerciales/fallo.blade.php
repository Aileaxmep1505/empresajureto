@extends('layouts.app')
@section('content_class', 'content--flush')
@section('content')
<link rel="stylesheet" href="{{ asset('css/cotizacion.css') }}?v={{ time() }}">

@php
  $resLabels = ['pending' => 'Pendiente', 'won' => 'Ganado', 'lost' => 'Perdido', 'partial' => 'Parcial'];
  $resCls = ['pending' => 'badge-muted', 'won' => 'badge-success', 'lost' => 'badge-danger', 'partial' => 'badge-warning'];
  $ganadasCount = $fallo->partidas->where('ganador', 'jureto')->count();
@endphp

<style>
  .fallo-table { width:100%; border-collapse:collapse; font-size:13px; }
  .fallo-table th, .fallo-table td { border:1px solid #ececec; padding:8px; text-align:left; vertical-align:top; }
  .fallo-table th { background:#f9fafb; font-weight:700; }
  .fallo-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
  @media(max-width:900px){ .fallo-grid2{ grid-template-columns:1fr; } }
  .pill { display:inline-block; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:700; }
</style>

<div class="jureto-quote-page">
  <div class="quote-wrap">
    <a href="{{ route('propuestas-comerciales.show', $propuestaComercial) }}" class="back-link"><span>←</span><span>Volver a la cotización</span></a>

    @if(session('status'))
      <div class="notice show" style="border-color:#bbf7d0; background:#f0fdf4; color:#166534;">
        <span class="notice-dot" style="background:#16a34a;"></span><span>{{ session('status') }}</span>
      </div>
    @endif
    @if(session('error'))
      <div class="notice show" style="border-color:#fecaca; background:#fef2f2; color:#b91c1c;">
        <span class="notice-dot" style="background:#dc2626;"></span><span>{{ session('error') }}</span>
      </div>
    @endif

    <div class="topbar">
      <div class="topbar-main">
        <div class="quote-code">ACTA DE FALLO · {{ $propuestaComercial->folio ?: ('TEOA'.str_pad($propuestaComercial->id,8,'0',STR_PAD_LEFT)) }}</div>
        <h1 class="quote-title">{{ $propuestaComercial->titulo ?: 'Propuesta #'.$propuestaComercial->id }}</h1>
        <p class="quote-subtitle">
          Resultado:
          <span class="badge {{ $resCls[$fallo->resultado] ?? 'badge-muted' }}">{{ $resLabels[$fallo->resultado] ?? 'Pendiente' }}</span>
          · {{ $ganadasCount }} partida(s) ganada(s) por JURETO
        </p>
      </div>
    </div>

    <div class="fallo-grid2">
      {{-- Subir acta + preview + extracción Azure --}}
      <div class="item-card" style="padding:18px;">
        <div class="section-title">PDF del acta de fallo</div>

        <form method="POST" action="{{ route('propuestas-comerciales.fallo.upload', $propuestaComercial) }}" enctype="multipart/form-data" class="action-row" style="margin-bottom:12px;">
          @csrf
          <input type="file" name="file" accept="application/pdf" class="input" style="height:auto; padding:8px;" required>
          <button class="btn btn-primary btn-small" type="submit">Subir / reemplazar</button>
        </form>

        @if($fallo->file_path)
          <iframe src="{{ asset('storage/'.$fallo->file_path) }}" style="width:100%; height:420px; border:1px solid #ececec; border-radius:12px;"></iframe>

          {{-- Extraer con Azure (IA) --}}
          <form method="POST" action="{{ route('propuesta-fallos.ocr', $fallo) }}" style="margin-top:12px;"
                onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerText='Procesando con Azure...';">
            @csrf
            <label class="result-meta" style="display:flex; align-items:center; gap:6px; margin-bottom:8px;">
              <input type="checkbox" name="reset" value="1"> Borrar las partidas IA previas y volver a extraer
            </label>
            <button class="btn btn-primary btn-small" type="submit">⚡ Extraer acta con Azure (IA)</button>
          </form>

          @if($fallo->ocr_status)
            <div class="result-meta" style="margin-top:6px;">Estado OCR: <strong>{{ $fallo->ocr_status }}</strong></div>
          @endif
        @else
          <p class="result-meta">Aún no se ha subido el acta.</p>
        @endif
      </div>

      {{-- Datos del acta + acciones --}}
      <div class="item-card" style="padding:18px;">
        <div class="section-title">Datos del acta</div>

        <form method="POST" action="{{ route('propuesta-fallos.header', $fallo) }}" style="display:grid; gap:12px;">
          @csrf
          <div class="field"><label>Número de acta</label><input class="input" name="numero_acta" value="{{ $fallo->numero_acta }}"></div>
          <div class="field"><label>Fecha de fallo</label><input class="input" type="date" name="fecha_fallo" value="{{ optional($fallo->fecha_fallo)->format('Y-m-d') }}"></div>
          <div class="field">
            <label>Resultado (se recalcula solo, pero puedes forzarlo)</label>
            <select class="input" name="resultado">
              @foreach($resLabels as $k => $v)
                <option value="{{ $k }}" @selected($fallo->resultado === $k)>{{ $v }}</option>
              @endforeach
            </select>
          </div>
          <div class="action-row"><button class="btn btn-ghost btn-small" type="submit">Guardar datos</button></div>
        </form>

        <hr style="border:none; border-top:1px solid #ececec; margin:16px 0;">

        <div class="section-title">Acciones</div>
        <div class="action-row">
          <form method="POST" action="{{ route('propuesta-fallos.seed', $fallo) }}" onsubmit="return confirm('¿Generar partidas desde la cotización?');">
            @csrf
            <button class="btn btn-soft btn-small" type="submit">↻ Generar partidas desde la cotización</button>
          </form>
          <form method="POST" action="{{ route('propuesta-fallos.seed', $fallo) }}" onsubmit="return confirm('Esto BORRA las partidas actuales y las regenera. ¿Continuar?');">
            @csrf
            <input type="hidden" name="reset" value="1">
            <button class="btn btn-danger btn-small" type="submit">⟳ Regenerar (borra)</button>
          </form>
        </div>
      </div>
    </div>

    {{-- Agregar partida manual --}}
    <div class="item-card" style="padding:18px; margin-top:16px;">
      <div class="section-title">Agregar partida manual</div>
      <form method="POST" action="{{ route('propuesta-fallos.partidas.store', $fallo) }}" style="display:grid; grid-template-columns:2fr 1fr 1fr auto; gap:10px; align-items:end;">
        @csrf
        <div class="field"><label>Descripción</label><input class="input" name="descripcion" required></div>
        <div class="field"><label>Cantidad</label><input class="input" type="number" step="0.01" name="cantidad" value="1"></div>
        <div class="field"><label>Nuestro precio</label><input class="input" type="number" step="0.01" name="nuestro_precio" value="0"></div>
        <button class="btn btn-primary btn-small" type="submit">＋ Agregar</button>
      </form>
    </div>

    {{-- Partidas + comparación --}}
    <h2 style="margin:24px 0 12px; font-size:18px;">Comparación por partida</h2>

    @forelse($fallo->partidas as $partida)
      @php
        $gCls = $partida->ganador === 'jureto' ? 'badge-success' : ($partida->ganador === 'competidor' ? 'badge-danger' : 'badge-muted');
        $gTxt = $partida->ganador === 'jureto' ? 'Ganó JURETO' : ($partida->ganador === 'competidor' ? 'Ganó competidor' : ($partida->ganador === 'desierto' ? 'Desierto' : 'Sin definir'));
        $dif = $partida->diferencia;
      @endphp
      <div class="item-card status-{{ $partida->ganador === 'jureto' ? 'exact' : ($partida->ganador === 'competidor' ? 'not_found' : 'similar') }}" style="margin-bottom:14px;">
        <div class="item-details" style="display:block; background:#fff;">
          <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <strong>#{{ $partida->partida_label ?: $loop->iteration }} · {{ $partida->descripcion }}</strong>
            <span class="badge {{ $gCls }}">{{ $gTxt }}</span>
          </div>

          {{-- Form editar partida --}}
          <form method="POST" action="{{ route('propuesta-fallo-partidas.update', $partida) }}" style="margin-top:12px;">
            @csrf @method('PUT')
            <div style="display:grid; grid-template-columns:2fr 1fr 1fr 1fr; gap:10px;">
              <div class="field"><label>Descripción</label><input class="input" name="descripcion" value="{{ $partida->descripcion }}"></div>
              <div class="field"><label>Cantidad</label><input class="input" type="number" step="0.01" name="cantidad" value="{{ $partida->cantidad }}"></div>
              <div class="field"><label>Nuestro precio</label><input class="input" type="number" step="0.01" name="nuestro_precio" value="{{ $partida->nuestro_precio }}"></div>
              <div class="field"><label>Precio ganador</label><input class="input" type="number" step="0.01" name="precio_ganador" value="{{ $partida->precio_ganador }}"></div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 2fr 2fr; gap:10px; margin-top:10px;">
              <div class="field">
                <label>Ganador</label>
                <select class="input" name="ganador">
                  <option value="" @selected(!$partida->ganador)>—</option>
                  <option value="jureto" @selected($partida->ganador==='jureto')>JURETO</option>
                  <option value="competidor" @selected($partida->ganador==='competidor')>Competidor</option>
                  <option value="desierto" @selected($partida->ganador==='desierto')>Desierto</option>
                </select>
              </div>
              <div class="field"><label>Empresa ganadora</label><input class="input" name="empresa_ganadora" value="{{ $partida->empresa_ganadora }}"></div>
              <div class="field"><label>Motivo (por qué ganaron)</label><input class="input" name="motivo" value="{{ $partida->motivo }}"></div>
            </div>

            <div class="result-meta" style="margin-top:8px;">
              Diferencia (nuestro − ganador):
              @if($dif === null) <strong>—</strong>
              @elseif($dif > 0) <strong class="text-danger">+${{ number_format($dif,2) }} (ofrecimos más caro)</strong>
              @elseif($dif < 0) <strong class="text-success">${{ number_format($dif,2) }} (ofrecimos más barato)</strong>
              @else <strong>$0.00 (igual)</strong>
              @endif
              · <span class="pill" style="background:#f3f4f6;">{{ $partida->source === 'manual' ? 'Manual' : 'IA' }}</span>
            </div>

            <div class="action-row" style="margin-top:10px;">
              <button class="btn btn-primary btn-small" type="submit">✓ Guardar partida (manual)</button>
          </form>
              <form method="POST" action="{{ route('propuesta-fallo-partidas.destroy', $partida) }}" onsubmit="return confirm('¿Eliminar esta partida?');">
                @csrf @method('DELETE')
                <button class="btn btn-danger btn-small" type="submit">× Eliminar partida</button>
              </form>
            </div>

          {{-- Ofertas / competidores --}}
          <div class="section" style="margin-top:16px;">
            <div class="section-title">Ofertas de las empresas</div>
            <table class="fallo-table">
              <thead><tr><th>Empresa</th><th>JURETO</th><th>Precio</th><th>Ganó</th><th></th></tr></thead>
              <tbody>
              @foreach($partida->ofertas as $oferta)
                <tr>
                  <td>
                    <form method="POST" action="{{ route('propuesta-fallo-ofertas.update', $oferta) }}" id="oferta-{{ $oferta->id }}">
                      @csrf @method('PUT')
                      <input class="input" name="empresa" value="{{ $oferta->empresa }}" style="height:34px;">
                  </td>
                  <td style="text-align:center;"><input type="checkbox" name="es_jureto" value="1" @checked($oferta->es_jureto)></td>
                  <td><input class="input" type="number" step="0.01" name="precio" value="{{ $oferta->precio }}" style="height:34px; width:120px;"></td>
                  <td style="text-align:center;"><input type="checkbox" name="gano" value="1" @checked($oferta->gano)></td>
                  <td class="action-row">
                      <button class="btn btn-ghost btn-small" type="submit">Guardar</button>
                    </form>
                    <form method="POST" action="{{ route('propuesta-fallo-ofertas.destroy', $oferta) }}" onsubmit="return confirm('¿Eliminar oferta?');">
                      @csrf @method('DELETE')
                      <button class="btn btn-danger btn-small" type="submit">×</button>
                    </form>
                  </td>
                </tr>
              @endforeach
              </tbody>
            </table>

            {{-- Agregar oferta --}}
            <form method="POST" action="{{ route('propuesta-fallo-partidas.ofertas.store', $partida) }}" style="display:grid; grid-template-columns:2fr auto 1fr auto auto; gap:8px; align-items:center; margin-top:10px;">
              @csrf
              <input class="input" name="empresa" placeholder="Empresa competidora" required style="height:34px;">
              <label class="result-meta" style="display:flex; align-items:center; gap:6px;"><input type="checkbox" name="es_jureto" value="1"> JURETO</label>
              <input class="input" type="number" step="0.01" name="precio" placeholder="Precio" style="height:34px;">
              <label class="result-meta" style="display:flex; align-items:center; gap:6px;"><input type="checkbox" name="gano" value="1"> Ganó</label>
              <button class="btn btn-soft btn-small" type="submit">＋ Oferta</button>
            </form>
          </div>
        </div>
      </div>
    @empty
      <div class="item-card" style="padding:18px;"><p class="result-meta">No hay partidas. Sube el acta y usa "⚡ Extraer con Azure", o "Generar partidas desde la cotización", o agrégalas manualmente.</p></div>
    @endforelse

    {{-- Convertir a adjudicación --}}
    <div class="item-card" style="padding:18px; margin-top:16px;">
      <div class="section-title">Convertir a adjudicación</div>
      <p class="result-meta">Se creará una <strong>Adjudicación</strong> solo con las {{ $ganadasCount }} partida(s) que ganó JURETO.</p>
      <form method="POST" action="{{ route('propuesta-fallos.convertir', $fallo) }}" onsubmit="return confirm('¿Crear la adjudicación con las partidas ganadas?');" style="margin-top:10px;">
        @csrf
        <button class="btn btn-primary" type="submit" @disabled($ganadasCount === 0)>⚑ Convertir ganadas a adjudicación</button>
      </form>
    </div>

    {{-- Adjudicaciones existentes --}}
    @if($adjudicaciones->count())
      <div class="item-card" style="padding:18px; margin-top:16px;">
        <div class="section-title">Adjudicaciones de esta cotización</div>
        <table class="fallo-table">
          <thead><tr><th>Folio</th><th>Fecha</th><th>Total</th><th>Estatus</th><th></th></tr></thead>
          <tbody>
          @foreach($adjudicaciones as $adj)
            <tr>
              <td>{{ $adj->folio ?: ('ADJ-'.$adj->id) }}</td>
              <td>{{ optional($adj->fecha)->format('d/m/Y') }}</td>
              <td>${{ number_format($adj->total,2) }}</td>
              <td><span class="badge badge-info">{{ ucfirst($adj->status) }}</span></td>
              <td><a class="btn btn-outline btn-small" href="{{ route('adjudicaciones.show', $adj) }}">Abrir</a></td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    @endif

  </div>
</div>
@endsection