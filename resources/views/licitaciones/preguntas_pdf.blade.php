<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Preguntas - {{ $licitacion->titulo }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin-top: 20px; margin-bottom: 8px; }
        .small { font-size: 11px; color: #6B7280; }
        .question { border: 1px solid #E5E7EB; border-radius: 6px; padding: 8px 10px; margin-bottom: 8px; }
        .meta { font-size: 10px; color: #6B7280; margin-top: 4px; display: flex; justify-content: space-between;}
    </style>
</head>
<body>
    <h1>Preguntas de la licitación</h1>
    <p class="small">{{ $licitacion->titulo }}</p>

    <h2>Listado de preguntas</h2>

    @forelse($preguntas as $index => $pregunta)
        <div class="question">
            <strong>{{ $index + 1 }}. Pregunta</strong><br>
            {{ $pregunta->texto_pregunta }}

            @if($pregunta->notas_internas)
                <br><br>
                <strong>Notas internas:</strong><br>
                {{ $pregunta->notas_internas }}
            @endif

            @if($pregunta->texto_respuesta)
                <br><br>
                <strong>Respuesta:</strong><br>
                {{ $pregunta->texto_respuesta }}
            @endif

            <div class="meta">
                <span>Fecha: {{ optional($pregunta->fecha_pregunta)->format('d/m/Y H:i') }}</span>
                <span>Usuario: {{ $pregunta->usuario->name ?? 'Usuario' }}</span>
            </div>
        </div>
    @empty
        <p>No hay preguntas registradas para esta licitación.</p>
    @endforelse
</body>
</html>
