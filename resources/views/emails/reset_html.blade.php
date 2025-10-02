@php
  $logo = 'https://ai.jureto.com.mx/images/logo-mail.png';

  $brandInk = '#14206a';
  $muted    = '#667085';
  $ink      = '#0f172a';
  $line     = '#e6e8ef';
  $pre      = 'Solicitud para restablecer tu contraseña en '.config('app.name').'.';
@endphp
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Restablecer contraseña</title>
  <meta name="color-scheme" content="light">
  <meta name="supported-color-schemes" content="light">
  <style>a{ text-decoration:none }</style>
</head>
<body style="margin:0;background:#f6f7fb;">
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">{{ $pre }}</div>

  <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr><td align="center" style="padding:32px 12px;">
      <table width="620" cellpadding="0" cellspacing="0" role="presentation" style="max-width:620px;">

        {{-- MASTHEAD mejorado --}}
        <tr><td align="center" style="padding:0 0 16px;">
          <table role="presentation" cellpadding="0" cellspacing="0" style="border:1px solid {{ $line }};border-radius:14px;background:#fff;">
            <tr>
              <td style="padding:10px 14px;">
                <table role="presentation" cellpadding="0" cellspacing="0">
                  <tr>
                    <td valign="middle" style="padding-right:10px;">
                      <img
                        src="{{ $logo }}"
                        alt="{{ config('app.name') }}"
                        width="120"
                        style="display:block;height:36px;width:auto;line-height:100%;border:0;outline:none;text-decoration:none;border-radius:8px;"
                      >
                    </td>
                    <td valign="middle">
                      <span style="font-weight:700;font-size:18px;color:{{ $brandInk }};letter-spacing:.2px;white-space:nowrap;">
                        {{ config('app.name') }}
                      </span>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td></tr>

        <tr><td style="background:#fff;border:1px solid {{ $line }};border-radius:18px;box-shadow:0 28px 70px rgba(18,38,63,.10);overflow:hidden;">
          <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:
            radial-gradient(650px 120px at 10% 0%, rgba(126,162,255,.15), transparent 60%),
            radial-gradient(650px 120px at 90% 0%, rgba(20,32,106,.08), transparent 60%),#fff;">
            <tr><td align="center" style="padding:26px 26px 8px;">
              <div style="display:inline-block;padding:8px 12px;border:1px solid {{ $line }};border-radius:999px;color:{{ $brandInk }};font-size:12px;background:#f9fbff;">
                Seguridad de cuenta
              </div>
              <h1 style="margin:14px 0 6px;color:{{ $ink }};font-size:22px;line-height:1.25;">
                Restablece tu contraseña
              </h1>
              <p style="margin:0 auto 4px;max-width:520px;color:{{ $muted }};line-height:1.65;">
                Hola, {{ $user->name }}. Recibimos una solicitud para actualizar tu contraseña.
              </p>
            </td></tr>
          </table>

          <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr><td align="center" style="padding:20px 26px 6px;">
              <a href="{{ $url }}" target="_blank" rel="noopener"
                 style="display:inline-block;background:{{ $brandInk }};color:#fff;font-weight:700;
                        padding:14px 22px;border-radius:12px;letter-spacing:.2px;border:1px solid {{ $brandInk }};
                        box-shadow:0 8px 24px rgba(20,32,106,.22);">
                Crear contraseña nueva
              </a>

              <p style="margin:14px 0 0;color:{{ $muted }};font-size:13px;">
                Este enlace caduca en 60 minutos. Si no solicitaste el cambio, puedes ignorar este correo.
              </p>

              <div style="height:1px;background:{{ $line }};margin:18px 0;"></div>

              <p style="margin:0;color:{{ $muted }};font-size:12px;">Si el botón no funciona, usa este enlace:</p>
              <div style="word-break:break-all;color:{{ $brandInk }};font-size:12px;margin:6px 0 0;">
                {{ $url }}
              </div>
            </td></tr>
          </table>

          <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr><td align="center" style="color:#8a93a6;font-size:12px;padding:8px 26px 20px;">
              Consejo: utiliza una contraseña larga y única (frase con espacios).
            </td></tr>
          </table>
        </td></tr>

        <tr><td align="center" style="color:#8a93a6;font-size:12px;padding:16px 6px;">
          © {{ date('Y') }} {{ config('app.name') }} — Todos los derechos reservados.
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
