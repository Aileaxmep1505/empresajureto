@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto py-10 space-y-8">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $licitacion->titulo }}</h1>
            <p class="mt-1 text-sm text-gray-500 max-w-xl">
                {{ $licitacion->descripcion }}
            </p>
            <div class="mt-3 flex flex-wrap gap-3 text-xs text-gray-500">
                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1">
                    Convocatoria: {{ optional($licitacion->fecha_convocatoria)->format('d/m/Y') ?? '—' }}
                </span>
                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1">
                    Modalidad: {{ ucfirst($licitacion->modalidad) }}
                </span>
                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1">
                    Estatus: {{ ucfirst(str_replace('_', ' ', $licitacion->estatus)) }}
                </span>
                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1">
                    Paso actual: {{ $licitacion->current_step }}
                </span>
            </div>
        </div>

        <div class="flex flex-col items-end gap-2">
            @php
                $step = $licitacion->current_step ?? 1;

                if ($step <= 9) {
                    // Pasos 1 a 9 del wizard principal
                    $continuarRoute = route('licitaciones.edit.step'.$step, $licitacion);
                } elseif ($step === 10) {
                    // Paso 10: checklist de compras
                    $continuarRoute = route('licitaciones.checklist.compras.edit', $licitacion);
                } elseif ($step === 11) {
                    // Paso 11: checklist de facturación
                    $continuarRoute = route('licitaciones.checklist.facturacion.edit', $licitacion);
                } else {
                    // Paso 12 o superior: contabilidad
                    $continuarRoute = route('licitaciones.contabilidad.edit', $licitacion);
                }
            @endphp

            <a href="{{ $continuarRoute }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 transition">
                Continuar wizard
            </a>
            <a href="{{ route('licitaciones.index') }}"
               class="text-xs text-gray-500 hover:text-gray-700">
                Volver al listado
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Columna izquierda: Fechas clave --}}
        <div class="md:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Fechas clave</h2>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Junta de aclaraciones</dt>
                        <dd class="text-gray-900">
                            {{ optional($licitacion->fecha_junta_aclaraciones)->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Límite de preguntas</dt>
                        <dd class="text-gray-900">
                            {{ optional($licitacion->fecha_limite_preguntas)->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Apertura de propuesta</dt>
                        <dd class="text-gray-900">
                            {{ optional($licitacion->fecha_apertura_propuesta)->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Entrega de muestras</dt>
                        <dd class="text-gray-900">
                            {{ optional($licitacion->fecha_entrega_muestras)->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Fallo</dt>
                        <dd class="text-gray-900">
                            {{ optional($licitacion->fecha_fallo)->format('d/m/Y') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Presentación de fallo</dt>
                        <dd class="text-gray-900">
                            {{ optional($licitacion->fecha_presentacion_fallo)->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Emisión de contrato</dt>
                        <dd class="text-gray-900">
                            {{ optional($licitacion->fecha_emision_contrato)->format('d/m/Y') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Fianza</dt>
                        <dd class="text-gray-900">
                            {{ optional($licitacion->fecha_fianza)->format('d/m/Y') ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Archivos --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-900">Archivos</h2>
                </div>
                <div class="space-y-2 text-sm">
                    @forelse($licitacion->archivos as $archivo)
                        <div class="flex items-center justify-between px-3 py-2 rounded-lg border border-gray-100 hover:bg-gray-50">
                            <div>
                                <div class="font-medium text-gray-900">
                                    {{ ucfirst(str_replace('_', ' ', $archivo->tipo)) }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $archivo->nombre_original }}
                                </div>
                            </div>
                            <a href="{{ Storage::disk('public')->url($archivo->path) }}"
                               target="_blank"
                               class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                Descargar
                            </a>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">No hay archivos vinculados aún.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Columna derecha: Preguntas y finanzas --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-gray-900">Preguntas</h2>
                    <a href="{{ route('licitaciones.preguntas.exportPdf', $licitacion) }}"
                       class="text-xs text-indigo-600 hover:text-indigo-800">
                        Descargar PDF
                    </a>
                </div>
                <div class="max-h-64 overflow-y-auto space-y-2 text-sm">
                    @forelse($licitacion->preguntas as $pregunta)
                        <div class="border border-gray-100 rounded-lg px-3 py-2">
                            <div class="text-gray-900">
                                {{ $pregunta->texto_pregunta }}
                            </div>
                            <div class="text-xs text-gray-500 mt-1 flex justify-between">
                                <span>{{ optional($pregunta->fecha_pregunta)->format('d/m/Y H:i') }}</span>
                                <span>Por: {{ $pregunta->usuario->name ?? 'Usuario' }}</span>
                            </div>
                            @if($pregunta->notas_internas)
                                <div class="mt-1 text-xs text-gray-500">
                                    Notas: {{ $pregunta->notas_internas }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">Aún no hay preguntas registradas.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-3">Resumen contable</h2>
                @if($licitacion->contabilidad)
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Inversión estimada</dt>
                            <dd class="text-gray-900">
                                ${{ number_format($licitacion->contabilidad->monto_inversion_estimado, 2) }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Costo total</dt>
                            <dd class="text-gray-900">
                                ${{ number_format($licitacion->contabilidad->costo_total, 2) }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Utilidad estimada</dt>
                            <dd class="text-gray-900">
                                ${{ number_format($licitacion->contabilidad->utilidad_estimada, 2) }}
                            </dd>
                        </div>
                    </dl>
                @else
                    <p class="text-gray-500 text-sm">Aún no se ha registrado la información contable.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
