<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Propuesta {{ $propuesta->codigo }}</title>
    <style>
        body{
            font-family: Arial, sans-serif;
            font-size: 11px;
            color:#111827;
            margin:20px 30px;
        }
        .brand{
            font-size:14px;
            font-weight:bold;
            letter-spacing:.05em;
            text-transform:uppercase;
        }
        .muted{ color:#6b7280; font-size:10px; }
        .title{
            font-size:16px;
            font-weight:bold;
            margin-top:10px;
            margin-bottom:4px;
        }
        .meta{
            margin-top:6px;
            font-size:10px;
        }
        .meta span{
            display:inline-block;
            margin-right:14px;
        }
        hr{
            border:0;
            border-top:1px solid #e5e7eb;
            margin:10px 0 14px;
        }
        table{
            width:100%;
            border-collapse:collapse;
            margin-top:8px;
        }
        thead th{
            font-size:10px;
            text-align:left;
            padding:6px 5px;
            border-bottom:1px solid #e5e7eb;
            background:#f3f4f6;
        }
        tbody td{
            padding:5px;
            border-bottom:1px solid #f3f4f6;
            vertical-align:top;
            font-size:9.5px;
        }
        .text-right{ text-align:right; }
        .text-center{ text-align:center; }
        .totals{
            margin-top:14px;
            width:100%;
            border-collapse:collapse;
        }
        .totals td{
            font-size:10px;
            padding:4px 5px;
        }
        .totals .label{
            text-align:right;
            color:#6b7280;
        }
        .totals .value{
            text-align:right;
            font-weight:bold;
        }
        .totals tr.total-row td{
            border-top:1px solid #e5e7eb;
            padding-top:6px;
            font-size:11px;
        }
        .section-title{
            margin-top:18px;
            font-size:12px;
            font-weight:bold;
        }
    </style>
</head>
<body>

    {{-- Puedes pegar aquí EXACTAMENTE el mismo cuerpo del PDF --}}
    @include('admin.licitacion_propuestas.export_pdf', ['propuesta' => $propuesta])

</body>
</html>
