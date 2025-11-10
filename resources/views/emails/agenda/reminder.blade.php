@php $tz = $event->timezone ?? 'America/Mexico_City'; @endphp
<!doctype html>
<html lang="es">
  <body style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;background:#f6f8fc;padding:32px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;margin:auto;background:#fff;border-radius:16px;box-shadow:0 12px 30px rgba(15,23,42,.08);overflow:hidden;">
      <tr>
        <td style="padding:28px 28px 12px;">
          <h1 style="margin:0;color:#0f172a;font-size:20px;">Recordatorio de agenda</h1>
          <p style="color:#667085;margin:8px 0 0;">Hola {{ $event->attendee_name ?? '' }}, tienes un evento próximo.</p>
        </td>
      </tr>
      <tr>
        <td style="padding:0 28px 24px;">
          <div style="border:1px solid #e8eef6;border-radius:12px;padding:16px;">
            <p style="margin:0 0 6px;color:#0f172a;font-weight:700;">{{ $event->title }}</p>
            @if($event->description)
              <p style="margin:0 0 10px;color:#667085;">{{ $event->description }}</p>
            @endif
            <p style="margin:0;color:#0f172a;">
              <strong>Fecha y hora:</strong>
              {{ $event->start_at->setTimezone($tz)->format('d/m/Y H:i') }} ({{ $tz }})
            </p>
          </div>
          <p style="color:#98a2b3;font-size:12px;margin-top:16px;">Este mensaje fue enviado automáticamente por la agenda.</p>
        </td>
      </tr>
    </table>
  </body>
</html>
