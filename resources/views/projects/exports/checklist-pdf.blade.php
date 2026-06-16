<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Checklist - {{ $project->name }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #111;
            font-size: 10px;
            margin: 20px;
        }
        h1 {
            font-size: 18px;
            margin: 0 0 6px;
        }
        .meta {
            color: #666;
            margin-bottom: 14px;
        }
        .counters {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .counters td {
            border: 1px solid #ebebeb;
            padding: 7px;
            background: #f9fafb;
            font-weight: bold;
        }
        table.checklist {
            width: 100%;
            border-collapse: collapse;
        }
        table.checklist th {
            background: #f3f4f6;
            border: 1px solid #dfe3e8;
            padding: 6px;
            text-align: left;
            font-size: 9px;
        }
        table.checklist td {
            border: 1px solid #ebebeb;
            padding: 6px;
            vertical-align: top;
        }
        .success { color: #15803d; font-weight: bold; }
        .danger { color: #ff4a4a; font-weight: bold; }
        .warning { color: #b45309; font-weight: bold; }
        .muted { color: #777; }
    </style>
</head>
<body>
    <h1>Checklist - {{ $project->name }}</h1>
    <div class="meta">Generado el {{ now()->format('d/m/Y H:i') }}</div>

    <table class="counters">
        <tr>
            <td>Total: {{ $counters['total'] }}</td>
            <td>Cumple: {{ $counters['cumple'] }}</td>
            <td>Parcial: {{ $counters['parcial'] }}</td>
            <td>No cumple: {{ $counters['no_cumple'] }}</td>
            <td>Sin revisar: {{ $counters['sin_revisar'] }}</td>
        </tr>
        <tr>
            <td>Pendiente: {{ $counters['pendiente'] }}</td>
            <td>En revisión: {{ $counters['revision'] }}</td>
            <td>Aprobado: {{ $counters['aprobado'] }}</td>
            <td colspan="2"></td>
        </tr>
    </table>

    <table class="checklist">
        <thead>
            <tr>
                <th>Requisito</th>
                <th>Descripción</th>
                <th>Formato</th>
                <th>Categoría</th>
                <th>Aplicación</th>
                <th>Oblig.</th>
                <th>Cumpl.</th>
                <th>Status</th>
                <th>Prioridad</th>
                <th>Fecha límite</th>
                <th>Responsable</th>
                <th>Revisor</th>
                <th>Adjuntos</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td>{{ $item['requisito'] ?? '-' }}</td>
                    <td>{{ $item['descripcion'] ?? '-' }}</td>
                    <td>{{ $item['formato'] ?? '-' }}</td>
                    <td>{{ $item['categoria'] ?? '-' }}</td>
                    <td>{{ $item['aplicabilidad'] ?? '-' }}</td>
                    <td>{{ $item['obligatorio'] ?? '-' }}</td>
                    <td class="@if(($item['cumplimiento'] ?? '') === 'Cumple') success @elseif(($item['cumplimiento'] ?? '') === 'No Cumple') danger @elseif(($item['cumplimiento'] ?? '') === 'Parcial') warning @else muted @endif">
                        {{ $item['cumplimiento'] ?? '-' }}
                    </td>
                    <td>{{ $item['status'] ?? 'Pendiente' }}</td>
                    <td>{{ $item['prioridad'] ?? 'Media' }}</td>
                    <td>{{ $item['fecha_limite'] ?? '-' }}</td>
                    <td>{{ $item['responsable'] ?? '-' }}</td>
                    <td>{{ $item['revisor'] ?? '-' }}</td>
                    <td>{{ collect($item['adjuntos'] ?? [])->pluck('name')->implode(', ') ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13">Sin requisitos registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
