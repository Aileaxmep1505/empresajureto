@extends('layouts.app')
@section('content_class', 'content--flush')
@section('title', $project->name)

@include('projects.partials.analysis.styles')

@section('content')
@php
  use Illuminate\Support\Str;

  $sd = $project->structured_data ?? [];
  $ficha = $sd['ficha'] ?? [];
  $fechas = $sd['fechas_clave'] ?? [];
  $resumenEjec = $sd['resumen_ejecutivo'] ?? [];
  $partidas = $sd['partidas'] ?? [];
  $citas = $sd['citas'] ?? [];

  /*
   |--------------------------------------------------------------------------
   | Fallback inteligente de Ficha de Resumen
   |--------------------------------------------------------------------------
   | Cuando la IA no llena structured_data.ficha, la vista intenta recuperar
   | datos desde extracted_text / extracted_raw de los documentos ya procesados.
   | Esto evita mostrar "Sin dato" cuando el texto sí existe en la convocatoria.
   */
  $__pjdFlat = null;
  $__pjdFlat = function ($value) use (&$__pjdFlat) {
      if (is_null($value)) {
          return '';
      }

      if (is_scalar($value)) {
          return (string) $value;
      }

      if (is_object($value)) {
          $value = (array) $value;
      }

      if (is_array($value)) {
          return collect($value)
              ->map(fn ($item) => $__pjdFlat($item))
              ->filter(fn ($item) => trim((string) $item) !== '')
              ->implode(' ');
      }

      return '';
  };

  $__pjdDocParts = [];

  foreach (($project->documents ?? collect()) as $__doc) {
      $__pjdDocParts[] = $__doc->filename ?? '';

      if (!empty($__doc->extracted_text)) {
          $__pjdDocParts[] = $__doc->extracted_text;
      }

      $__raw = $__doc->extracted_raw ?? null;

      if (is_string($__raw)) {
          $__decoded = json_decode($__raw, true);
          $__raw = json_last_error() === JSON_ERROR_NONE ? $__decoded : $__raw;
      }

      $__pjdDocParts[] = $__pjdFlat($__raw);
  }

  foreach (collect($documentLibrary ?? []) as $__docItem) {
      $__pjdDocParts[] = $__docItem['summary'] ?? '';
      $__pjdDocParts[] = $__docItem['title'] ?? '';
      $__pjdDocParts[] = $__docItem['filename'] ?? '';
  }

  $__pjdDocText = trim(preg_replace('/\s+/u', ' ', strip_tags(collect($__pjdDocParts)->filter()->implode(' '))));

  $__pjdClean = function ($value) {
      return trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)));
  };

  $__pjdMissing = function ($value) use ($__pjdClean) {
      $clean = Str::lower($__pjdClean($value));

      return $clean === ''
          || in_array($clean, [
              '-',
              '—',
              'sin dato',
              'no se encontro informacion',
              'no se encontró información',
              'no se encontro información',
              'no se encontró informacion',
              'no se encontró información expresa',
          ], true);
  };

  $__pjdFirstMatch = function (array $patterns, int $limit = 260) use ($__pjdDocText, $__pjdClean) {
      foreach ($patterns as $pattern) {
          if (preg_match($pattern, $__pjdDocText, $match)) {
              $value = $__pjdClean($match[1] ?? $match[0] ?? '');
              $value = trim($value, " \t\n\r\0\x0B:-–—.");

              if ($value !== '') {
                  return Str::limit($value, $limit, '');
              }
          }
      }

      return null;
  };

  $__pjdPutFicha = function ($key, $value) use (&$ficha, $__pjdMissing, $__pjdClean) {
      if ($__pjdMissing($ficha[$key] ?? null) && !$__pjdMissing($value)) {
          $ficha[$key] = $__pjdClean($value);
      }
  };

  if ($__pjdDocText !== '') {
      $__numeroLicitacion = $__pjdFirstMatch([
          '~(?:n[uú]mero\s+de\s+licitaci[oó]n|no\.?\s*(?:de\s*)?(?:licitaci[oó]n|procedimiento)|procedimiento\s*(?:no\.?|n[uú]m\.?)|expediente)\s*[:#]?\s*([A-Z0-9][A-Z0-9\/\-.]{4,})~iu',
          '~\b((?:IA|LA|LPN|LPI|AA|AD)[\-\/]?[A-Z0-9\-\/\.]{5,})\b~iu',
      ], 80);

      $__tipoEvento = null;
      $__tipoNeedles = [
          'Invitación a cuando menos tres personas',
          'Licitación Pública Nacional',
          'Licitación Pública Internacional',
          'Licitación Pública',
          'Adjudicación Directa',
      ];

      foreach ($__tipoNeedles as $__needle) {
          if (Str::contains(Str::lower($__pjdDocText), Str::lower($__needle))) {
              $__tipoEvento = $__needle;
              break;
          }
      }

      $__organismo = $__pjdFirstMatch([
          '~(Consejo\s+Nacional\s+de\s+Fomento\s+Educativo)~iu',
          '~\b(CONAFE)\b~iu',
          '~(Secretar[ií]a\s+de\s+Educaci[oó]n\s+P[uú]blica)~iu',
          '~(Secretar[ií]a\s+de\s+Marina)~iu',
          '~(Instituto\s+[A-ZÁÉÍÓÚÑ][A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]{8,120})~u',
      ], 160);

      if (Str::upper((string) $__organismo) === 'CONAFE') {
          $__organismo = 'Consejo Nacional de Fomento Educativo';
      }

      $__objeto = $__pjdFirstMatch([
          '~objeto\s+(?:de\s+)?(?:la\s+)?(?:licitaci[oó]n|contrataci[oó]n|procedimiento)\s*[:\-\n]\s*([^.;]{12,260})~iu',
          '~(adquisici[oó]n\s+de\s+(?:materiales|bienes|servicios|[úu]tiles)[^.;]{5,220})~iu',
          '~(contrataci[oó]n\s+de\s+(?:servicios|bienes|materiales)[^.;]{5,220})~iu',
          '~(servicio\s+de\s+[^.;]{10,220})~iu',
      ], 260);

      $__medioParticipacion = null;

      if (preg_match('~\b(electr[oó]nic[ao]|CompraNet)\b~iu', $__pjdDocText)) {
          $__medioParticipacion = 'Electrónica';
      } elseif (preg_match('~\bpresencial\b~iu', $__pjdDocText)) {
          $__medioParticipacion = 'Presencial';
      } elseif ($__tipoEvento) {
          $__medioParticipacion = $__tipoEvento;
      }

      $__monedaPago = null;

      if (preg_match('~moneda\s+nacional\s*\(([^)]+)\)~iu', $__pjdDocText, $__m)) {
          $__monedaPago = 'Moneda nacional (' . $__pjdClean($__m[1]) . ')';
      } elseif (preg_match('~\bpesos\s+mexicanos\b~iu', $__pjdDocText)) {
          $__monedaPago = 'Pesos mexicanos';
      } elseif (preg_match('~\bmoneda\s+nacional\b~iu', $__pjdDocText)) {
          $__monedaPago = 'Moneda nacional';
      }

      $__condicionesPago = $__pjdFirstMatch([
          '~CONDICIONES\s+Y\s+FORMAS\s+DE\s+PAGO\s*(.+?)(?=\s+(?:\d+\.\s+[A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s]{6,}|P[aá]gina\s+\d+|$))~isu',
          '~(?:forma|condiciones)\s+de\s+pago\s*[:\-\n]\s*(.+?)(?=\s+(?:\d+\.\s+[A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s]{6,}|P[aá]gina\s+\d+|$))~isu',
          '~(el\s+pago\s+correspondiente\s+se\s+realizar[aá]\s+.+?)(?=\s+(?:\d+\.\s+[A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s]{6,}|P[aá]gina\s+\d+|$))~isu',
      ], 520);

      $__pjdPutFicha('numero_licitacion', $__numeroLicitacion);
      $__pjdPutFicha('tipo_evento', $__tipoEvento);
      $__pjdPutFicha('organismo', $__organismo);
      $__pjdPutFicha('objeto_licitacion', $__objeto);
      $__pjdPutFicha('objeto', $__objeto);
      $__pjdPutFicha('medio_participacion', $__medioParticipacion);
      $__pjdPutFicha('moneda_pago', $__monedaPago);
      $__pjdPutFicha('condiciones_pago', $__condicionesPago);
  }

  $checklistRaw = $project->relationLoaded('checklistItems') && $project->checklistItems->count()
      ? $project->checklistItems->map(fn ($it) => method_exists($it, 'toChecklistArray') ? $it->toChecklistArray() : [
          'id'                    => $it->id,
          'requisito'             => $it->requirement,
          'descripcion'           => $it->description,
          'criterio_cumplimiento' => $it->compliance_criteria,
          'formato'               => $it->format ?: 'No aplica',
          'categoria'             => $it->category ?: 'Legal-Administrativo',
          'aplicabilidad'         => $it->applicability ?: 'Único',
          'obligatorio'           => $it->mandatory ? 'Sí' : 'No',
          'cumplimiento'          => match($it->compliance_status) { 'cumple' => 'Cumple', 'parcial' => 'Parcial', 'no_cumple' => 'No Cumple', default => '-' },
          'status'                => match($it->review_status) { 'en_revision' => 'En revisión', 'aprobado' => 'Aprobado', default => 'Pendiente' },
          'prioridad'             => match($it->priority) { 'alta' => 'Alta', 'baja' => 'Baja', default => 'Media' },
          'fecha_limite'          => optional($it->due_date)->format('Y-m-d'),
          'responsable_id'        => $it->responsible_user_id,
          'responsable'           => $it->responsible?->name ?: data_get($it->metadata, 'responsable_text', ''),
          'revisor_id'            => $it->reviewer_user_id,
          'revisor'               => $it->reviewer?->name ?: data_get($it->metadata, 'revisor_text', ''),
          'fuente'                => $it->source_name,
          'pagina'                => $it->source_page,
          'cita'                  => $it->source_quote,
          'notas'                 => $it->notes->map(fn($n) => ['id'=>$n->id,'body'=>$n->body,'user_name'=>$n->user?->name,'created_at'=>optional($n->created_at)->format('Y-m-d H:i:s')])->values()->all(),
          'adjuntos'              => $it->attachments->map(fn($a) => ['id'=>$a->id,'name'=>$a->original_name,'url'=>$a->url,'mime'=>$a->mime_type,'size'=>$a->size,'uploaded_at'=>optional($a->created_at)->format('Y-m-d H:i:s')])->values()->all(),
      ])->values()->all()
      : ($project->checklist ?: ($sd['checklist_sugerido'] ?? []));

  $checklist = collect($checklistRaw)->map(function ($it, $i) {
      if (!is_array($it)) return null;
      return [
          'id'                    => $it['id'] ?? ('item-'.$i),
          'requisito'             => $it['requisito'] ?? $it['item'] ?? $it['text'] ?? 'Sin nombre',
          'descripcion'           => $it['descripcion'] ?? '',
          'criterio_cumplimiento' => $it['criterio_cumplimiento'] ?? '',
          'formato'               => $it['formato'] ?? 'No aplica',
          'categoria'             => $it['categoria'] ?? 'Legal-Administrativo',
          'aplicabilidad'         => $it['aplicabilidad'] ?? 'Único',
          'obligatorio'           => $it['obligatorio'] ?? 'Sí',
          'cumplimiento'          => $it['cumplimiento'] ?? '-',
          'status'                => $it['status'] ?? 'Pendiente',
          'prioridad'             => $it['prioridad'] ?? 'Media',
          'fecha_limite'          => $it['fecha_limite'] ?? null,
          'responsable'           => $it['responsable'] ?? '',
          'responsable_id'        => $it['responsable_id'] ?? null,
          'revisor'               => $it['revisor'] ?? '',
          'revisor_id'            => $it['revisor_id'] ?? null,
          'notas'                 => $it['notas'] ?? [],
          'adjuntos'              => $it['adjuntos'] ?? [],
          'fuente'                => $it['fuente'] ?? '',
          'pagina'                => $it['pagina'] ?? null,
          'cita'                  => $it['cita'] ?? $it['evidencia'] ?? $it['fragmento'] ?? '',
      ];
  })->filter()->values()->all();

  $statusClass = match($project->status) {
      'ready' => 'is-ready',
      'processing' => 'is-processing',
      'error','partial' => 'is-error',
      default => '',
  };
  $statusLabel = match($project->status) {
      'ready' => 'Listo',
      'processing' => 'Procesando…',
      'error' => 'Error',
      'partial' => 'Parcial',
      default => $project->status,
  };

  $normalizaFuente = function ($text) {
      $text = Str::ascii((string) $text);
      $text = mb_strtolower($text, 'UTF-8');
      $text = preg_replace('/[^a-z0-9]+/', ' ', $text);
      return trim(preg_replace('/\s+/', ' ', $text));
  };

  $resolverCita = function ($citas, $key, $value = null, $label = null) use ($normalizaFuente) {
      if (!is_array($citas) || empty($citas)) return null;

      $tieneEvidencia = function ($c) {
          return is_array($c) && (!empty($c['cita']) || !empty($c['fuente']) || !empty($c['pagina']));
      };

      if ($tieneEvidencia($citas[$key] ?? null)) {
          return $citas[$key];
      }

      $keyBase = preg_replace('/^ficha\.|^fechas_clave\.|^resumen_ejecutivo\./', '', (string) $key);
      $aliases = array_unique([
          $key,
          $keyBase,
          str_replace('_', ' ', $keyBase),
          str_replace('_', '.', $keyBase),
          Str::snake((string) $label),
          Str::slug((string) $label, '_'),
      ]);

      $aliasesNorm = array_filter(array_map($normalizaFuente, $aliases));

      foreach ($citas as $citaKey => $citaData) {
          if (!$tieneEvidencia($citaData)) continue;
          $citaKeyNorm = $normalizaFuente($citaKey);
          foreach ($aliasesNorm as $aliasNorm) {
              if ($citaKeyNorm === $aliasNorm || str_ends_with($citaKeyNorm, ' '.$aliasNorm) || str_contains($citaKeyNorm, $aliasNorm)) {
                  return $citaData;
              }
          }
      }

      $needle = $normalizaFuente(trim(($value ?? '').' '.($label ?? '')));
      $words = array_values(array_unique(array_filter(explode(' ', $needle), fn($w) => mb_strlen($w) >= 4)));
      if (count($words) < 2) return null;

      $best = null;
      $bestScore = 0;
      foreach ($citas as $citaData) {
          if (!$tieneEvidencia($citaData)) continue;
          $haystack = $normalizaFuente(($citaData['cita'] ?? '').' '.($citaData['fuente'] ?? ''));
          if (!$haystack) continue;

          $score = 0;
          foreach ($words as $w) {
              if (str_contains($haystack, $w)) $score++;
          }

          if ($score > $bestScore) {
              $bestScore = $score;
              $best = $citaData;
          }
      }

      $minScore = max(2, min(4, (int) ceil(count($words) * 0.30)));
      return $bestScore >= $minScore ? $best : null;
  };

  $citaPayload = function ($citas, $key, $value = null, $label = null) use ($resolverCita) {
      $c = $resolverCita($citas, $key, $value, $label);
      if (!is_array($c) || (empty($c['cita']) && empty($c['fuente']) && empty($c['pagina']))) return null;
      return json_encode([
          'cita'   => $c['cita'] ?? '',
          'fuente' => $c['fuente'] ?? '',
          'pagina' => $c['pagina'] ?? null,
      ], JSON_UNESCAPED_UNICODE);
  };

  $checklistCitaPayload = function ($it) {
      $hasSource = !empty($it['fuente']) || !empty($it['pagina']) || !empty($it['cita']) || !empty($it['descripcion']);
      if (!$hasSource) return null;

      return json_encode([
          'cita'        => $it['cita'] ?: ($it['descripcion'] ?? ''),
          'fuente'      => $it['fuente'] ?? '',
          'pagina'      => $it['pagina'] ?? null,
          'requisito'   => $it['requisito'] ?? '',
          'descripcion' => $it['descripcion'] ?? '',
      ], JSON_UNESCAPED_UNICODE);
  };
@endphp

@include('projects.partials.control-sidebar', ['project' => $project ?? null])

<div class="pjd-wrap has-control-sidebar">

  @include('projects.partials.analysis.topbar', [
    'project' => $project,
    'statusClass' => $statusClass,
    'statusLabel' => $statusLabel,
  ])

  <div class="pjd-body">

    @include('projects.partials.analysis.chat', ['project' => $project])

    <div class="pjd-resizer" id="pjdResizer" role="separator" aria-orientation="vertical" aria-label="Ajustar ancho del chat y del contenido" tabindex="0"></div>

    <div class="pjd-right">

      <div class="pjd-pane" data-pane="inicio">
        <div class="pjd-inicio-card">
          <h4>Estado del proyecto</h4>
          <p>Status: <strong>{{ $statusLabel }}</strong></p>
          <p>Documentos: <strong>{{ $project->documents->count() }}</strong></p>
          <p>Creado: <strong>{{ $project->created_at->format('d M Y H:i') }}</strong></p>
        </div>
        @if($project->status === 'error' && $project->error_message)
          <div class="pjd-inicio-card" style="border-color:#fecaca;background:#fff5f5;">
            <h4 style="color:var(--danger)">Error de procesamiento</h4>
            <p>{{ $project->error_message }}</p>
          </div>
        @endif
      </div>

      @include('projects.partials.analysis.ficha', [
        'project' => $project,
        'ficha' => $ficha,
        'fechas' => $fechas,
        'sd' => $sd,
        'citas' => $citas,
        'checklist' => $checklist,
        'citaPayload' => $citaPayload,
        'resolverCita' => $resolverCita,
      ])

      @include('projects.partials.analysis.alcance', [
        'project' => $project,
        'sd' => $sd,
        'partidas' => $partidas,
        'citas' => $citas,
      ])

      @include('projects.partials.analysis.observaciones', [
        'project' => $project,
        'sd' => $sd,
        'citas' => $citas,
      ])

      @include('projects.partials.analysis.eventos', [
        'project' => $project,
        'sd' => $sd,
        'fechas' => $fechas,
        'citas' => $citas,
      ])

      @include('projects.partials.analysis.matriz', [
        'project' => $project,
        'sd' => $sd,
        'checklist' => $checklist,
        'checklistCitaPayload' => $checklistCitaPayload,
      ])

      @include('projects.partials.analysis.financiero', [
        'project' => $project,
        'sd' => $sd,
        'citas' => $citas,
      ])

      @include('projects.partials.analysis.resumen', [
        'project' => $project,
        'resumenEjec' => $resumenEjec,
        'citas' => $citas,
        'citaPayload' => $citaPayload,
        'resolverCita' => $resolverCita,
      ])

      @include('projects.partials.analysis.checklist', [
        'project' => $project,
        'checklist' => $checklist,
        'checklistCitaPayload' => $checklistCitaPayload,
      ])

      @include('projects.partials.analysis.borrador', ['project' => $project])

      @include('projects.partials.analysis.documentos', [
        'project' => $project,
        'documentLibrary' => $documentLibrary ?? [],
      ])

    </div>
  </div>

  @include('projects.partials.analysis.studio')
</div>

@include('projects.partials.analysis.modals')
@endsection

@include('projects.partials.analysis.scripts')
