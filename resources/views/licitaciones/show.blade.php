@extends('layouts.app')
@section('title', 'Detalle licitación')

@section('content')
<style>
:root{
  --mint:#48cfad;
  --mint-dark:#34c29e;
  --ink:#111827;
  --muted:#6b7280;
  --line:#e6eef6;
  --card:#ffffff;
  --tag:#eef2ff;
  --shadow:0 12px 34px rgba(12,18,30,0.06);
}

/* Layout general */
.licitacion-show-wrap{
  max-width:1120px;
  margin:56px auto;
  padding:18px;
  box-sizing:border-box;
  font-family:"Open Sans",sans-serif;
  color:var(--ink);
}
.l-header{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:18px;
  margin-bottom:22px;
}
.l-title{
  margin:0;
  font-size:24px;
  font-weight:700;
  letter-spacing:-0.02em;
}
.l-sub{
  margin:6px 0 0;
  font-size:13px;
  color:var(--muted);
  max-width:520px;
}
.l-chips{
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  margin-top:10px;
}
.chip{
  display:inline-flex;
  align-items:center;
  border-radius:999px;
  background:#f3f4ff;
  border:1px solid var(--line);
  padding:5px 10px;
  font-size:11px;
  color:var(--muted);
}

/* Botones header derecha */
.l-actions{
  display:flex;
  flex-direction:column;
  align-items:flex-end;
  gap:6px;
}
.btn-primary{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding:8px 14px;
  border-radius:999px;
  border:0;
  background:var(--mint);
  color:#fff;
  font-size:13px;
  font-weight:600;
  text-decoration:none;
  box-shadow:0 10px 24px rgba(72,207,173,0.18);
  cursor:pointer;
}
.btn-primary:hover{
  background:var(--mint-dark);
}
.btn-outline{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding:7px 12px;
  border-radius:999px;
  border:1px solid var(--line);
  background:#fff;
  color:var(--ink);
  font-size:12px;
  font-weight:600;
  text-decoration:none;
}
.btn-outline:hover{
  border-color:#cbd5e1;
}
.link-back{
  font-size:11px;
  color:var(--muted);
  text-decoration:none;
}
.link-back:hover{
  color:var(--ink);
}

/* Badge flujo cerrado */
.badge-closed{
  font-size:11px;
  padding:4px 10px;
  border-radius:999px;
  background:#fef2f2;
  color:#b91c1c;
  border:1px solid #fecaca;
}

/* Grid columnas */
.l-grid{
  display:grid;
  grid-template-columns: minmax(0,2fr) minmax(0,1.2fr);
  gap:18px;
}
@media(max-width:900px){
  .l-grid{
    grid-template-columns:1fr;
  }
}

/* Tarjetas */
.card{
  background:var(--card);
  border-radius:16px;
  border:1px solid var(--line);
  box-shadow:var(--shadow);
  padding:18px 18px 16px 18px;
  box-sizing:border-box;
}
.card-title{
  font-size:14px;
  font-weight:600;
  margin:0 0 10px 0;
  color:var(--ink);
}

/* Fechas clave */
.dl-grid{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:10px 24px;
  font-size:13px;
}
@media(max-width:720px){
  .dl-grid{
    grid-template-columns:1fr;
  }
}
.dl-label{
  font-size:12px;
  color:var(--muted);
}
.dl-value{
  font-size:13px;
  color:var(--ink);
  margin-top:2px;
}

/* Archivos */
.file-row{
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:8px 10px;
  border-radius:12px;
  border:1px solid #edf1f7;
  margin-bottom:6px;
  font-size:12px;
  background:#fff;
}
.file-main{
  display:flex;
  flex-direction:column;
  gap:2px;
}
.file-name{
  font-weight:600;
}
.file-meta{
  color:var(--muted);
  font-size:11px;
}
.file-link{
  font-size:11px;
  color:#4f46e5;
  text-decoration:none;
  font-weight:600;
}
.file-link:hover{
  color:#3730a3;
}

/* Preguntas */
.questions-box{
  max-height:260px;
  overflow-y:auto;
  padding-right:4px;
}
.q-item{
  border-radius:12px;
  border:1px solid #edf1f7;
  padding:8px 9px;
  margin-bottom:6px;
  font-size:12px;
  background:#fff;
  display:flex;
  gap:8px;
  align-items:flex-start;
}
.q-idx{
  flex:0 0 auto;
  width:22px;height:22px;
  border-radius:999px;
  display:inline-flex;
  align-items:center;justify-content:center;
  font-size:11px;font-weight:700;
  background:#f1f5f9;border:1px solid #e2e8f0;
  color:#0f172a;
  margin-top:1px;
}
.q-body{ flex:1 1 auto; }
.q-text{
  color:var(--ink);
}
.q-meta{
  margin-top:3px;
  display:flex;
  justify-content:space-between;
  font-size:11px;
  color:var(--muted);
}
.q-notes{
  margin-top:3px;
  font-size:11px;
  color:var(--muted);
}

/* Resumen contable */
.dl-vertical{
  display:flex;
  flex-direction:column;
  gap:6px;
  font-size:13px;
}
.dl-row{
  display:flex;
  justify-content:space-between;
  gap:10px;
}
.dl-row dt{
  color:var(--muted);
}
.dl-row dd{
  margin:0;
  font-weight:600;
  text-align:right;
}

/* Etiquetas pequeñas */
.section-empty{
  font-size:13px;
  color:var(--muted);
}
.section-header-actions{
  display:flex;
  align-items:center;
  justify-content:space-between;
  margin-bottom:8px;
}
.link-mini{
  font-size:11px;
  color:#4f46e5;
  text-decoration:none;
  font-weight:600;
}
.link-mini:hover{
  color:#3730a3;
}
.mini-actions{
  display:flex;
  align-items:center;
  gap:8px;
}
</style>

@php
    // Detectar si el usuario es admin (Spatie o campo is_admin)
    $user = auth()->user();
    $esAdmin = false;
    if ($user) {
        if (method_exists($user, 'hasRole')) {
            $esAdmin = $user->hasRole('admin') || $user->hasRole('Admin');
        } elseif (isset($user->is_admin)) {
            $esAdmin = (bool) $user->is_admin;
        }
    }

    $cont = $licitacion->contabilidad;
    $detalle = $cont && is_array($cont->detalle_costos ?? null) ? $cont->detalle_costos : [];

    $monto = $cont ? (float)($cont->monto_inversion_estimado ?? 0) : 0;
    $costoTotal = $cont ? (float)($cont->costo_total ?? 0) : 0;
    $utilidad = $cont ? (float)($cont->utilidad_estimada ?? ($monto - $costoTotal)) : 0;

    $gastoProductos = isset($detalle['productos']) && is_numeric($detalle['productos'])
        ? (float)$detalle['productos'] : 0;

    $keysOperativos = [
        'renta','luz','agua','nominas','imss','gasolina','viaticos',
        'casetas','pagos_gobierno','mantenimiento_camionetas','libre_1','libre_2'
    ];
    $gastosOperativos = 0.0;
    foreach ($keysOperativos as $k) {
        $gastosOperativos += (isset($detalle[$k]) && is_numeric($detalle[$k])) ? (float)$detalle[$k] : 0;
    }

    $margen = $monto > 0 ? ($utilidad / $monto) * 100 : 0;
@endphp

<div class="licitacion-show-wrap">
    <div class="l-header">
        <div>
            <h1 class="l-title">{{ $licitacion->titulo }}</h1>
            <p class="l-sub">
                {{ $licitacion->descripcion }}
            </p>
            <div class="l-chips">
                <span class="chip">
                    Convocatoria:
                    &nbsp;{{ optional($licitacion->fecha_convocatoria)->format('d/m/Y') ?? '—' }}
                </span>
                <span class="chip">
                    Modalidad:
                    &nbsp;{{ ucfirst($licitacion->modalidad) }}
                </span>
                <span class="chip">
                    Resultado:
                    &nbsp;{{ $licitacion->resultado ? ucfirst(str_replace('_',' ',$licitacion->resultado)) : '—' }}
                </span>
                <span class="chip">
                    Estatus:
                    &nbsp;{{ ucfirst(str_replace('_', ' ', $licitacion->estatus)) }}
                </span>
                <span class="chip">
                    Paso actual:
                    &nbsp;{{ $licitacion->current_step }}
                </span>
            </div>
        </div>

        <div class="l-actions">
            @php
                $cerrada = ($licitacion->resultado === 'no_ganado') || ($licitacion->estatus === 'cerrado');

                if ($cerrada) {
                    $continuarRoute = null;
                } else {
                    // current_step = último paso completado
                    $lastStep = (int) ($licitacion->current_step ?? 0);
                    $nextStep = max(1, min($lastStep + 1, 12));

                    if ($nextStep === 4) {
                        // Paso 4 lógico = preguntas
                        $continuarRoute = route('licitaciones.preguntas.index', $licitacion);
                    } elseif ($nextStep <= 3 || ($nextStep >= 5 && $nextStep <= 9)) {
                        $continuarRoute = route('licitaciones.edit.step'.$nextStep, $licitacion);
                    } elseif ($nextStep === 10) {
                        $continuarRoute = route('licitaciones.checklist.compras.edit', $licitacion);
                    } elseif ($nextStep === 11) {
                        $continuarRoute = route('licitaciones.checklist.facturacion.edit', $licitacion);
                    } else {
                        $continuarRoute = route('licitaciones.contabilidad.edit', $licitacion);
                    }
                }
            @endphp

            {{-- Resumen general PDF (visible para todos) --}}
            <a href="{{ route('licitaciones.resumen.pdf', $licitacion) }}" class="btn-outline" target="_blank">
                Descargar resumen PDF
            </a>

            {{-- PDF de estado financiero SOLO ADMIN --}}
            @if($esAdmin && $cont)
                <a href="{{ route('licitaciones.contabilidad.pdf', $licitacion) }}"
                   class="btn-outline" target="_blank">
                    Descargar estado financiero (PDF)
                </a>
            @endif

            @if(!$cerrada && $continuarRoute)
                <a href="{{ $continuarRoute }}" class="btn-primary">
                    Continuar wizard
                </a>
            @else
                <span class="badge-closed">
                    Flujo cerrado (licitación no ganada o concluida)
                </span>
            @endif

            <a href="{{ route('licitaciones.index') }}" class="link-back">
                Volver al listado
            </a>
        </div>
    </div>

    <div class="l-grid">
        {{-- Columna izquierda: Fechas clave y archivos --}}
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div class="card">
                <h2 class="card-title">Fechas clave</h2>
                <dl class="dl-grid">
                    <div>
                        <dt class="dl-label">Junta de aclaraciones</dt>
                        <dd class="dl-value">
                            {{ optional($licitacion->fecha_junta_aclaraciones)->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="dl-label">Límite de preguntas</dt>
                        <dd class="dl-value">
                            {{ optional($licitacion->fecha_limite_preguntas)->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="dl-label">Apertura de propuesta</dt>
                        <dd class="dl-value">
                            {{ optional($licitacion->fecha_apertura_propuesta)->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="dl-label">Entrega de muestras</dt>
                        <dd class="dl-value">
                            {{ optional($licitacion->fecha_entrega_muestras)->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="dl-label">Fecha acta de apertura</dt>
                        <dd class="dl-value">
                            {{ optional($licitacion->fecha_acta_apertura)->format('d/m/Y') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="dl-label">Fallo</dt>
                        <dd class="dl-value">
                            {{ optional($licitacion->fecha_fallo)->format('d/m/Y') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="dl-label">Presentación de fallo</dt>
                        <dd class="dl-value">
                            {{ optional($licitacion->fecha_presentacion_fallo)->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="dl-label">Emisión de contrato</dt>
                        <dd class="dl-value">
                            {{ optional($licitacion->fecha_emision_contrato)->format('d/m/Y') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="dl-label">Fianza</dt>
                        <dd class="dl-value">
                            {{ optional($licitacion->fecha_fianza)->format('d/m/Y') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="dl-label">Tipo de fianza</dt>
                        <dd class="dl-value">
                            {{ $licitacion->tipo_fianza ? ucfirst(str_replace('_',' ', $licitacion->tipo_fianza)) : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="dl-label">Fechas de cobro</dt>
                        <dd class="dl-value">
                            @if(is_array($licitacion->fechas_cobro) && count($licitacion->fechas_cobro))
                                @foreach($licitacion->fechas_cobro as $fc)
                                    {{ \Carbon\Carbon::parse($fc)->format('d/m/Y') }}@if(!$loop->last), @endif
                                @endforeach
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="card">
                <div class="section-header-actions">
                    <h2 class="card-title">Archivos</h2>
                </div>

                <div>
                    @forelse($licitacion->archivos as $archivo)
                        <div class="file-row">
                            <div class="file-main">
                                <div class="file-name">
                                    {{ ucfirst(str_replace('_', ' ', $archivo->tipo)) }}
                                </div>
                                <div class="file-meta">
                                    {{ $archivo->nombre_original }}
                                </div>
                            </div>
                            <a
                                href="{{ Storage::disk('public')->url($archivo->path) }}"
                                target="_blank"
                                class="file-link"
                            >
                                Descargar
                            </a>
                        </div>
                    @empty
                        <p class="section-empty">No hay archivos vinculados aún.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Columna derecha: Preguntas y resumen contable --}}
        <div style="display:flex;flex-direction:column;gap:16px;">

            {{-- Preguntas --}}
            <div class="card">
                @php
                    $allPreguntas = $licitacion->preguntas->sortByDesc('fecha_pregunta');
                    $topPreguntas = $allPreguntas->take(7);
                    $hayMas = $allPreguntas->count() > 7;
                @endphp

                <div class="section-header-actions">
                    <h2 class="card-title">Preguntas</h2>

                    <div class="mini-actions">
                        <a href="{{ route('licitaciones.preguntas.exportPdf', $licitacion) }}" class="link-mini">
                            PDF
                        </a>
                        <a href="{{ route('licitaciones.preguntas.exportWord', $licitacion) }}" class="link-mini">
                            Word
                        </a>
                        @if($hayMas)
                            <a href="{{ route('licitaciones.preguntas.index', $licitacion) }}" class="link-mini">
                                Ver todas
                            </a>
                        @endif
                    </div>
                </div>

                <div class="questions-box">
                    @forelse($topPreguntas as $pregunta)
                        <div class="q-item">
                            @if($hayMas)
                                <div class="q-idx">{{ $loop->iteration }}</div>
                            @endif

                            <div class="q-body">
                                <div class="q-text">
                                    {{ $pregunta->texto_pregunta }}
                                </div>

                                <div class="q-meta">
                                    <span>{{ optional($pregunta->fecha_pregunta)->format('d/m/Y H:i') }}</span>
                                    <span>Por: {{ $pregunta->usuario->name ?? 'Usuario' }}</span>
                                </div>

                                @if($pregunta->notas_internas)
                                    <div class="q-notes">
                                        Referencia a bases: {{ $pregunta->notas_internas }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="section-empty">
                            Aún no hay preguntas registradas.
                        </p>
                    @endforelse
                </div>

                @if($hayMas)
                    <p class="section-empty" style="margin-top:6px;">
                        Mostrando 7 de {{ $allPreguntas->count() }} preguntas.
                    </p>
                @endif
            </div>

            {{-- Resumen contable (solo visible completo para admin) --}}
            <div class="card">
                <div class="section-header-actions">
                    <h2 class="card-title">Resumen contable</h2>

                    @if($esAdmin && $cont)
                        <a href="{{ route('licitaciones.contabilidad.edit', $licitacion) }}" class="link-mini">
                            Editar contabilidad
                        </a>
                    @endif
                </div>

                @if(!$cont)
                    <p class="section-empty">
                        Aún no se ha registrado la información contable.
                    </p>
                @else
                    @if(!$esAdmin)
                        <p class="section-empty" style="margin-bottom:10px;">
                            La información contable solo es visible para administradores.
                        </p>
                    @endif

                    @if($esAdmin)
                        <dl class="dl-vertical">
                            <div class="dl-row">
                                <dt>Importe adjudicado (ingreso)</dt>
                                <dd>${{ number_format($monto, 2) }}</dd>
                            </div>
                            <div class="dl-row">
                                <dt>Inversión en productos</dt>
                                <dd>${{ number_format($gastoProductos, 2) }}</dd>
                            </div>
                            <div class="dl-row">
                                <dt>Gastos operativos</dt>
                                <dd>${{ number_format($gastosOperativos, 2) }}</dd>
                            </div>
                            <div class="dl-row">
                                <dt>Costo total estimado</dt>
                                <dd>${{ number_format($costoTotal, 2) }}</dd>
                            </div>
                            <div class="dl-row">
                                <dt>Utilidad estimada</dt>
                                <dd>${{ number_format($utilidad, 2) }}</dd>
                            </div>
                            <div class="dl-row">
                                <dt>Margen sobre licitación</dt>
                                <dd>{{ number_format($margen, 1) }} %</dd>
                            </div>
                            <div class="dl-row">
                                <dt>Observaciones contrato / fianza</dt>
                                <dd style="max-width:260px;">
                                    {{ $licitacion->observaciones_contrato ?: '—' }}
                                </dd>
                            </div>
                        </dl>

                        <div style="margin-top:10px;">
                            <a href="{{ route('licitaciones.contabilidad.pdf', $licitacion) }}"
                               target="_blank"
                               class="link-mini">
                                Descargar estado financiero (PDF)
                            </a>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
