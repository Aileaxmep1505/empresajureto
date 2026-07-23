<!DOCTYPE html>
<html lang="es">
<head>
 <meta charset="UTF-8">
 <title>Solicitud de asesor</title>
</head>
<body>
 <h2>Un cliente solicitó hablar con un asesor</h2>
 <p>
 <strong>Conversación:</strong>
 {{ $conversation->title ?: 'Sin asunto' }}
 </p>
 <p>
 <strong>Cliente:</strong>
 {{ optional($conversation->customer)->name ?: 'Cliente invitado' }}
 </p>
 <p>
 <strong>Correo del cliente:</strong>
 {{ optional($conversation->customer)->email ?: 'No disponible' }}
 </p>
 <p>
 <a href="{{ route('admin.web-assistant.index', ['status' => 'waiting']) }}">
 Abrir panel y responder
 </a>
 </p>
</body>
</html>