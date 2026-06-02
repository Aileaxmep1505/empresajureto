@php
  $item = $assignment->item;
  $isFixed = $item && (($item->type ?? null) !== 'consumible');

  // Helper: saber si un valor existe y no está vacío
  $hasValue = function($value) {
      return isset($value) && trim((string) $value) !== '' && trim((string) $value) !== '—';
  };

  // Helper: imprimir valor o no usarlo
  $valueOrNull = function($value) use ($hasValue) {
      return $hasValue($value) ? $value : null;
  };

  // Quién entrega / quién recibe
  $entrega = optional($assignment->deliveredBy)->name ?? optional($assignment->user)->name ?? null;
  $recibe  = optional($assignment->receivedBy)->name ?? null;

  // Checklist de entrega
  $deliveryChecklist = $assignment->delivery_checklist;
  if (is_string($deliveryChecklist)) {
      $deliveryChecklist = json_decode($deliveryChecklist, true) ?: [];
  }
  if (!is_array($deliveryChecklist)) {
      $deliveryChecklist = [];
  }

  $deliveryChecklist = array_values(array_filter($deliveryChecklist, function($c) {
      if (is_array($c)) {
          return !empty($c['label']);
      }
      return trim((string) $c) !== '';
  }));

  // Checklist de devolución
  $returnChecklist = $assignment->return_checklist;
  if (is_string($returnChecklist)) {
      $returnChecklist = json_decode($returnChecklist, true) ?: [];
  }
  if (!is_array($returnChecklist)) {
      $returnChecklist = [];
  }

  $returnLabels = [
      'enciende'       => '¿Enciende / prende?',
      'sin_contrasena' => '¿Está sin contraseña?',
      'rayones'        => '¿Tiene rayones / golpes?',
      'completo'       => '¿Viene completo (accesorios)?',
      'funcional'      => '¿Funciona correctamente?',
      'limpio'         => '¿Está limpio?',
  ];

  $valLabel = function($v) {
      return $v === 'si' ? 'Sí' : ($v === 'no' ? 'No' : ($v === 'na' ? 'N/A' : null));
  };

  // Filtrar checklist devolución para que solo aparezcan los que sí tengan valor
  $filteredReturnLabels = [];
  foreach ($returnLabels as $key => $label) {
      if (!empty($returnChecklist[$key])) {
          $filteredReturnLabels[$key] = $label;
      }
  }

  // Imágenes de devolución
  $returnImages = $assignment->return_images;
  if (is_string($returnImages)) {
      $returnImages = json_decode($returnImages, true) ?: [];
  }
  if (!is_array($returnImages)) {
      $returnImages = [];
  }

  $returnImages = array_values(array_filter($returnImages, function($imgPath) {
      return !empty($imgPath) && file_exists(public_path('storage/'.$imgPath));
  }));

  // Logo
  $logoPath = public_path('images/logo-mail.png');
  $logoExists = file_exists($logoPath);

  // Foto del artículo
  $itemPhotoExists = $item && !empty($item->photo) && file_exists(public_path('storage/'.$item->photo));

  // Saber si hay datos generales para mostrar
  $hasGeneralData =
      !empty($assignment->assigned_at) ||
      !empty($assignment->quantity) ||
      !empty($entrega) ||
      !empty($recibe) ||
      !empty(optional($assignment->user)->name) ||
      !empty(optional($assignment->user)->email);

  // Saber si hay datos del artículo para mostrar
  $hasItemData =
      $item &&
      (
          !empty($item->name) ||
          !empty($item->type) ||
          !empty($item->brand) ||
          !empty($item->model) ||
          !empty($item->serial_number) ||
          !empty($item->internal_code) ||
          !empty($item->processor) ||
          !empty($item->ram) ||
          !empty($item->storage) ||
          !empty($item->operating_system) ||
          !empty($item->mac_address) ||
          !empty($item->condition) ||
          $itemPhotoExists
      );

  // Saber si hay datos de devolución
  $hasReturnData =
      $assignment->status === 'devuelta' &&
      (
          !empty($assignment->returned_at) ||
          !empty($assignment->return_condition) ||
          !empty($assignment->return_reason) ||
          !empty($assignment->return_details) ||
          count($filteredReturnLabels) ||
          count($returnImages)
      );
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Carta Responsiva {{ $assignment->folio ?? '' }}</title>

  <style>
    * {
      box-sizing: border-box;
    }

    body {
      font-family: DejaVu Sans, sans-serif;
      color: #1f2937;
      font-size: 11px;
      line-height: 1.5;
      margin: 0;
      padding: 28px 34px;
    }

    h1, h2, h3 {
      margin: 0;
    }

    .header {
      position: relative;
      border-bottom: 2px solid #0f172a;
      padding-bottom: 12px;
      margin-bottom: 18px;
      min-height: 72px;
    }

    .header-logo {
      position: absolute;
      left: 0;
      top: 0;
    }

    .header-logo img {
      width: 95px;
      height: auto;
    }

    .header-center {
      text-align: center;
      padding-top: 10px;
      padding-left: 115px;
      padding-right: 115px;
    }

    .header h1 {
      font-size: 18px;
      color: #0f172a;
      letter-spacing: .5px;
      text-align: center;
    }

    .header .sub {
      font-size: 10px;
      color: #6b7280;
      margin-top: 3px;
      text-align: center;
    }

    .folio {
      position: absolute;
      right: 0;
      top: 0;
      font-size: 10px;
      color: #6b7280;
    }

    .section {
      margin-bottom: 16px;
    }

    .section-title {
      font-size: 11px;
      font-weight: bold;
      color: #0f172a;
      text-transform: uppercase;
      letter-spacing: .5px;
      border-bottom: 1px solid #e5e7eb;
      padding-bottom: 4px;
      margin-bottom: 8px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    .info td {
      padding: 4px 6px;
      vertical-align: top;
    }

    .info .k {
      color: #6b7280;
      width: 130px;
      font-weight: bold;
    }

    .info .v {
      color: #111827;
    }

    .asset-box {
      display: table;
      width: 100%;
    }

    .asset-box .photo-cell {
      display: table-cell;
      width: 110px;
      vertical-align: top;
      padding-right: 12px;
    }

    .asset-box .photo-cell img {
      width: 100px;
      height: 100px;
      object-fit: cover;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
    }

    .asset-box .data-cell {
      display: table-cell;
      vertical-align: top;
    }

    .specs {
      width: 100%;
    }

    .specs td {
      padding: 3px 6px;
      border-bottom: 1px solid #f1f5f9;
    }

    .specs .k {
      color: #6b7280;
      font-weight: bold;
      width: 120px;
    }

    .chk-list {
      width: 100%;
    }

    .chk-list td {
      padding: 3px 6px;
      width: 50%;
    }

    .badge {
      display: inline-block;
      padding: 1px 6px;
      border-radius: 8px;
      font-size: 9px;
      font-weight: bold;
    }

    .badge.si {
      background: #dcfce7;
      color: #15803d;
    }

    .badge.no {
      background: #fee2e2;
      color: #b91c1c;
    }

    .badge.na {
      background: #e5e7eb;
      color: #374151;
    }

    .terms {
      font-size: 9.5px;
      color: #374151;
      text-align: justify;
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      padding: 10px 12px;
    }

    .sign-grid {
      display: table;
      width: 100%;
      margin-top: 30px;
    }

    .sign-cell {
      display: table-cell;
      width: 50%;
      text-align: center;
      padding: 0 18px;
      vertical-align: bottom;
    }

    .sign-img {
      max-height: 70px;
      margin-bottom: 4px;
    }

    .sign-line {
      border-top: 1px solid #0f172a;
      margin-top: 6px;
      padding-top: 4px;
      font-size: 10px;
    }

    .muted {
      color: #6b7280;
    }

    .img-row {
      width: 100%;
    }

    .img-row td {
      width: 33.33%;
      padding: 4px;
      vertical-align: top;
    }

    .img-row img {
      width: 100%;
      height: 120px;
      object-fit: cover;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
    }
  </style>
</head>

<body>

  {{-- Encabezado --}}
  <div class="header">
    @if($logoExists)
      <div class="header-logo">
        <img src="{{ $logoPath }}" alt="Logo">
      </div>
    @endif

    @if(!empty($assignment->folio))
      <div class="folio">Folio: <b>{{ $assignment->folio }}</b></div>
    @endif

    <div class="header-center">
      <h1>CARTA RESPONSIVA</h1>
      <div class="sub">Resguardo de equipo / artículo asignado</div>
    </div>
  </div>

  {{-- Datos generales --}}
  @if($hasGeneralData)
    <div class="section">
      <div class="section-title">Datos de la asignación</div>

      <table class="info">
        @if(!empty($assignment->assigned_at) || !empty($assignment->quantity))
          <tr>
            @if(!empty($assignment->assigned_at))
              <td class="k">Fecha de entrega</td>
              <td class="v">{{ optional($assignment->assigned_at)->format('d/m/Y H:i') }}</td>
            @endif

            @if(!empty($assignment->quantity))
              <td class="k">Cantidad</td>
              <td class="v">{{ $assignment->quantity }}</td>
            @endif
          </tr>
        @endif

        @if(!empty($entrega) || !empty($recibe))
          <tr>
            @if(!empty($entrega))
              <td class="k">Quién entrega</td>
              <td class="v">{{ $entrega }}</td>
            @endif

            @if(!empty($recibe))
              <td class="k">Quién recibe</td>
              <td class="v">{{ $recibe }}</td>
            @endif
          </tr>
        @endif

        @if(!empty(optional($assignment->user)->name) || !empty(optional($assignment->user)->email))
          <tr>
            @if(!empty(optional($assignment->user)->name))
              <td class="k">Usuario asignado</td>
              <td class="v">{{ optional($assignment->user)->name }}</td>
            @endif

            @if(!empty(optional($assignment->user)->email))
              <td class="k">Correo</td>
              <td class="v">{{ optional($assignment->user)->email }}</td>
            @endif
          </tr>
        @endif
      </table>
    </div>
  @endif

  {{-- Ficha del artículo --}}
  @if($hasItemData)
    <div class="section">
      <div class="section-title">Artículo asignado</div>

      <div class="asset-box">
        @if($itemPhotoExists)
          <div class="photo-cell">
            <img src="{{ public_path('storage/'.$item->photo) }}" alt="Foto">
          </div>
        @endif

        <div class="data-cell">
          <table class="specs">
            @if(!empty($item->name))
              <tr>
                <td class="k">Nombre</td>
                <td>{{ $item->name }}</td>
              </tr>
            @endif

            @if(!empty($item->type))
              <tr>
                <td class="k">Tipo</td>
                <td>{{ $isFixed ? 'Activo fijo' : 'Consumible' }}</td>
              </tr>
            @endif

            @if($isFixed)
              @if(!empty($item->brand))
                <tr>
                  <td class="k">Marca</td>
                  <td>{{ $item->brand }}</td>
                </tr>
              @endif

              @if(!empty($item->model))
                <tr>
                  <td class="k">Modelo</td>
                  <td>{{ $item->model }}</td>
                </tr>
              @endif

              @if(!empty($item->serial_number))
                <tr>
                  <td class="k">No. de serie</td>
                  <td>{{ $item->serial_number }}</td>
                </tr>
              @endif

              @if(!empty($item->internal_code))
                <tr>
                  <td class="k">Código interno</td>
                  <td>{{ $item->internal_code }}</td>
                </tr>
              @endif

              @if(!empty($item->processor))
                <tr>
                  <td class="k">Procesador</td>
                  <td>{{ $item->processor }}</td>
                </tr>
              @endif

              @if(!empty($item->ram))
                <tr>
                  <td class="k">RAM</td>
                  <td>{{ $item->ram }}</td>
                </tr>
              @endif

              @if(!empty($item->storage))
                <tr>
                  <td class="k">Almacenamiento</td>
                  <td>{{ $item->storage }}</td>
                </tr>
              @endif

              @if(!empty($item->operating_system))
                <tr>
                  <td class="k">Sistema operativo</td>
                  <td>{{ $item->operating_system }}</td>
                </tr>
              @endif

              @if(!empty($item->mac_address))
                <tr>
                  <td class="k">Dirección MAC</td>
                  <td>{{ $item->mac_address }}</td>
                </tr>
              @endif

              @if(!empty($item->condition))
                <tr>
                  <td class="k">Condición</td>
                  <td>{{ $item->condition_label ?? $item->condition }}</td>
                </tr>
              @endif
            @endif
          </table>
        </div>
      </div>
    </div>
  @endif

  {{-- Checklist de entrega --}}
  @if($isFixed && count($deliveryChecklist))
    <div class="section">
      <div class="section-title">Se entrega con</div>

      <table class="chk-list">
        @foreach(array_chunk($deliveryChecklist, 2) as $pair)
          <tr>
            @foreach($pair as $c)
              @php
                $label = is_array($c) ? ($c['label'] ?? '') : $c;
                $checked = is_array($c) ? !empty($c['checked']) : true;
              @endphp

              @if(!empty($label))
                <td>{!! $checked ? '&#9745;' : '&#9744;' !!} {{ $label }}</td>
              @endif
            @endforeach

            @if(count($pair) === 1)
              <td></td>
            @endif
          </tr>
        @endforeach
      </table>
    </div>
  @endif

  {{-- Términos --}}
  <div class="section">
    <div class="section-title">Términos de resguardo</div>

    <div class="terms">
      El usuario reconoce haber recibido el artículo descrito en buen estado y se compromete a usarlo
      exclusivamente para fines laborales, a conservarlo en óptimas condiciones y a reportar cualquier
      falla, robo o extravío de manera inmediata. En caso de daño por mal uso o negligencia, el usuario
      acepta la responsabilidad correspondiente. El equipo deberá ser devuelto al término de la relación
      laboral o cuando la institución así lo requiera.

      @if(!empty($assignment->notes))
        <br><br><b>Notas:</b> {{ $assignment->notes }}
      @endif
    </div>
  </div>

  {{-- Información de devolución --}}
  @if($hasReturnData)
    <div class="section">
      <div class="section-title">Devolución</div>

      @if(!empty($assignment->returned_at) || !empty($assignment->return_condition))
        <table class="info">
          <tr>
            @if(!empty($assignment->returned_at))
              <td class="k">Fecha devolución</td>
              <td class="v">{{ optional($assignment->returned_at)->format('d/m/Y H:i') }}</td>
            @endif

            @if(!empty($assignment->return_condition))
              <td class="k">Condición final</td>
              <td class="v">{{ ucfirst($assignment->return_condition) }}</td>
            @endif
          </tr>
        </table>
      @endif

      @if(!empty($assignment->return_reason) || !empty($assignment->return_details))
        <table class="info">
          <tr>
            @if(!empty($assignment->return_reason))
              <td class="k">Motivo</td>
              <td class="v">{{ $assignment->return_reason }}</td>
            @endif

            @if(!empty($assignment->return_details))
              <td class="k">Detalles</td>
              <td class="v">{{ $assignment->return_details }}</td>
            @endif
          </tr>
        </table>
      @endif

      @if(count($filteredReturnLabels))
        <table class="chk-list" style="margin-top:8px;">
          @foreach(array_chunk($filteredReturnLabels, 2, true) as $pair)
            <tr>
              @foreach($pair as $key => $label)
                @php
                  $v = $returnChecklist[$key] ?? null;
                  $labelValue = $valLabel($v);
                @endphp

                @if($labelValue)
                  <td>
                    {{ $label }}
                    <span class="badge {{ $v }}">{{ $labelValue }}</span>
                  </td>
                @endif
              @endforeach

              @if(count($pair) === 1)
                <td></td>
              @endif
            </tr>
          @endforeach
        </table>
      @endif

      @if(count($returnImages))
        <div style="margin-top:10px;">
          <div class="muted" style="margin-bottom:4px;">
            <b>Evidencia fotográfica:</b>
          </div>

          <table class="img-row">
            <tr>
              @foreach($returnImages as $index => $imgPath)
                <td>
                  <img src="{{ public_path('storage/'.$imgPath) }}" alt="Evidencia">
                </td>

                @if(($index + 1) % 3 === 0)
                  </tr><tr>
                @endif
              @endforeach
            </tr>
          </table>
        </div>
      @endif
    </div>
  @endif

  {{-- Firmas --}}
  <div class="sign-grid">
    <div class="sign-cell">
      @if(!empty($assignment->signature_image))
        <img class="sign-img" src="{{ $assignment->signature_image }}" alt="Firma">
      @endif

      <div class="sign-line">
        @if(!empty($recibe))
          {{ $recibe }}
        @elseif(!empty(optional($assignment->user)->name))
          {{ optional($assignment->user)->name }}
        @else
          Recibe
        @endif

        <div class="muted">
          Recibe / Responsable

          @if($assignment->signature_status === 'signed' && !empty($assignment->signed_at))
            <br>Firmado: {{ $assignment->signed_at->format('d/m/Y H:i') }}
          @endif
        </div>
      </div>
    </div>

    <div class="sign-cell">
      <div class="sign-line">
        @if(!empty($entrega))
          {{ $entrega }}
        @else
          Entrega
        @endif

        <div class="muted">Entrega / Autoriza</div>
      </div>
    </div>
  </div>

</body>
</html>