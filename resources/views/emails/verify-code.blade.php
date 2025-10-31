<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Código de verificación</title>
</head>
<body style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Helvetica Neue',Arial,sans-serif; background:#f6f8fc; padding:24px;">
  <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e8eef6;border-radius:16px;padding:28px;">
    <h2 style="margin:0 0 8px 0;color:#0e1726;">Verifica tu correo</h2>
    <p style="color:#334155;line-height:1.5;margin:0 0 18px 0;">
      Usa este código para verificar tu cuenta:
    </p>

    <div style="font-size:32px;letter-spacing:8px;font-weight:700;color:#0e1726;margin:16px 0 24px 0;text-align:center;">
      {{ $code }}
    </div>

    <p style="color:#334155;line-height:1.5;margin:0;">
      Este código expira en <strong>{{ $minutes }} minutos</strong>. Si no solicitaste este correo, puedes ignorarlo.
    </p>
  </div>
  <p style="color:#64748b;font-size:12px;text-align:center;margin-top:16px;">
    © {{ date('Y') }} — {{ config('app.name') }}
  </p>
</body>
</html>
