{{-- Plantilla básica de cuerpo: imprime texto con saltos de línea --}}
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>{{ $subject ?? 'Mensaje' }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial; color:#0f172a; line-height:1.6; margin:0; padding:24px; background:#f6f8fb;">
  <div style="max-width:720px; margin:0 auto; background:#fff; border:1px solid #e8eef6; border-radius:14px; padding:20px;">
    {!! nl2br(e($body)) !!}
  </div>
</body>
</html>
