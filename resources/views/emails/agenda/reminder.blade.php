<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recordatorio de agenda</title>
</head>
<body style="margin:0; padding:0; background:#f5f7fb; font-family:Arial, Helvetica, sans-serif; color:#111827;">
    <div style="max-width:640px; margin:30px auto; background:#ffffff; border-radius:16px; overflow:hidden; border:1px solid #e5e7eb;">
        <div style="background:#111827; color:#ffffff; padding:24px 28px;">
            <h1 style="margin:0; font-size:22px; font-weight:700;">Recordatorio de agenda</h1>
            <p style="margin:8px 0 0; font-size:14px; color:#d1d5db;">
                Tienes una actividad próxima programada.
            </p>
        </div>

        <div style="padding:28px;">
            <p style="margin:0 0 16px; font-size:15px;">
                Hola <strong>{{ $user->name }}</strong>,
            </p>

            <p style="margin:0 0 20px; font-size:15px;">
                Este es un recordatorio para tu evento:
            </p>

            <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:14px; padding:18px 20px; margin-bottom:20px;">
                <p style="margin:0 0 10px; font-size:18px; font-weight:700; color:#111827;">
                    {{ $event->title }}
                </p>

                @if(!empty($event->description))
                    <p style="margin:0 0 12px; font-size:14px; color:#4b5563;">
                        {{ $event->description }}
                    </p>
                @endif

                <p style="margin:0 0 8px; font-size:14px;">
                    <strong>Fecha:</strong>
                    {{ \Carbon\Carbon::parse($event->start_at, $event->timezone ?: 'America/Mexico_City')->format('d/m/Y') }}
                </p>

                <p style="margin:0 0 8px; font-size:14px;">
                    <strong>Hora:</strong>
                    {{ \Carbon\Carbon::parse($event->start_at, $event->timezone ?: 'America/Mexico_City')->format('h:i A') }}
                </p>

                @if(!empty($event->location))
                    <p style="margin:0 0 8px; font-size:14px;">
                        <strong>Ubicación:</strong> {{ $event->location }}
                    </p>
                @endif

                @if(!empty($event->notes))
                    <p style="margin:0; font-size:14px;">
                        <strong>Notas:</strong> {{ $event->notes }}
                    </p>
                @endif
            </div>

            <p style="margin:0; font-size:13px; color:#6b7280;">
                Este correo fue generado automáticamente por el sistema de agenda.
            </p>
        </div>
    </div>
</body>
</html>