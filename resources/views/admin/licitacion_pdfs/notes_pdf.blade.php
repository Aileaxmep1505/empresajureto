<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Notas — {{ $pdf->original_filename }}</title>
  <style>
    body{ font-family: DejaVu Sans, sans-serif; color:#0b1220; }
    h1{ font-size:16px; margin:0 0 6px; }
    .sub{ color:#667085; font-size:11px; margin:0 0 14px; }
    pre{
      white-space:pre-wrap;
      border:1px solid #e6eaf2;
      padding:12px;
      border-radius:10px;
      background:#f8fafc;
      font-size:12px;
      line-height:1.4;
    }
  </style>
</head>
<body>
  <h1>Notas — {{ $pdf->original_filename }}</h1>
  <p class="sub">PDF #{{ $pdf->id }} · Generado: {{ now()->format('d/m/Y H:i') }}</p>
  <pre>{{ $notes }}</pre>
</body>
</html>
