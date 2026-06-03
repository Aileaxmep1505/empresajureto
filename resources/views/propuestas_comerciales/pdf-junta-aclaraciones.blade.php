<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Junta de aclaraciones</title>
  <style>
    body {
      font-family: DejaVu Sans, Arial, sans-serif;
      color: #111;
      font-size: 12px;
      line-height: 1.45;
      margin: 28px;
    }

    h1 {
      font-size: 20px;
      margin: 0 0 6px;
      text-align: center;
    }

    .meta {
      color: #555;
      text-align: center;
      margin-bottom: 24px;
      font-size: 11px;
    }

    .question {
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 14px;
      margin-bottom: 14px;
      page-break-inside: avoid;
    }

    .question-title {
      font-weight: bold;
      margin-bottom: 8px;
    }

    .item {
      color: #555;
      font-size: 11px;
      margin-bottom: 8px;
    }

    .text {
      font-size: 12px;
      text-align: justify;
    }

    .suggested {
      margin-top: 10px;
      font-size: 11px;
      color: #333;
      background: #f7f7f7;
      padding: 8px;
      border-radius: 6px;
    }
  </style>
</head>
<body>
  <h1>Preguntas para Junta de Aclaraciones</h1>

  <div class="meta">
    Folio: {{ $propuestaComercial->folio ?? ('Propuesta #' . $propuestaComercial->id) }}
    @if(!empty($propuestaComercial->titulo))
      · {{ $propuestaComercial->titulo }}
    @endif
    · Generado: {{ now()->format('d/m/Y H:i') }}
  </div>

  @forelse($preguntas as $index => $pregunta)
    <div class="question">
      <div class="question-title">
        Pregunta {{ $index + 1 }}
      </div>

      @if($pregunta->producto_solicitado)
        <div class="item">
          Partida / producto solicitado: {{ $pregunta->producto_solicitado }}
        </div>
      @endif

      <div class="text">
        {{ $pregunta->pregunta_generada }}
      </div>

      @if($pregunta->producto_sugerido)
        <div class="suggested">
          Producto sugerido para aclaración:
          <strong>{{ $pregunta->producto_sugerido }}</strong>
          @if($pregunta->sku_sugerido)
            · SKU: {{ $pregunta->sku_sugerido }}
          @endif
          @if($pregunta->marca_sugerida)
            · Marca: {{ $pregunta->marca_sugerida }}
          @endif
        </div>
      @endif
    </div>
  @empty
    <p>No hay preguntas registradas para esta propuesta.</p>
  @endforelse
</body>
</html>