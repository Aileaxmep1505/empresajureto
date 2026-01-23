<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">

  <style>
    /* Página EXACTAMENTE 2 x 2 pulgadas */
    @page{
      size: 2in 2in;
      margin: 6pt 6pt 4pt 6pt; /* un poco más de margen arriba/lados */
    }

    html, body{
      margin: 0;
      padding: 0;
    }

    body{
      font-family: DejaVu Sans, sans-serif;
      font-size: 7px;
    }

    .label{
      box-sizing: border-box;
      width: 100%;
      text-align: center;
    }

    .top-text{
      margin-top: 2pt;
      margin-bottom: 2pt;
      line-height: 1.2;
      max-width: 100%;
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis;
    }

    .top-text .name{
      font-size: 7px;
    }

    .top-text .brand{
      font-size: 6.5px;
    }

    /* Bajamos el QR y lo centramos */
    .qr-box{
      margin-top: 8pt;   /* ← súbelo/bájalo cambiando este valor */
      text-align: center;
    }

    .qr-box img{
      width: 1.05in;
      height: 1.05in;
      display: inline-block;
    }
  </style>
</head>
<body>
  <div class="label">
    {{-- Nombre del producto --}}
    <div class="top-text">
      <div class="name">{{ $name }}</div>
      @if($brandLine !== '')
        <div class="brand">{{ $brandLine }}</div>
      @endif
    </div>

    {{-- QR centrado y un poco más abajo --}}
    <div class="qr-box">
      <img src="{{ $qrBase64 }}" alt="QR">
    </div>
  </div>
</body>
</html>
