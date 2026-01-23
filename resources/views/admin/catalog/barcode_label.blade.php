<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Etiqueta código de barras</title>
  <style>
    @page{
      margin:0;
      padding:0;
    }

    *{
      box-sizing:border-box;
      margin:0;
      padding:0;
    }

    html, body{
      margin:0;
      padding:0;
      width:144pt;   /* 2" de ancho */
      height:72pt;   /* 1" de alto */
    }

    body{
      font-family:"Quicksand", system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, "Helvetica Neue", Arial;
      font-size:7pt;
    }

    /* Contenedor de toda la etiqueta (ligeramente menos que 1" para no rebasar) */
    .label{
      width:144pt;
      height:64pt;               /* un poco menor que 72pt para evitar segunda página */
      padding:4pt 6pt 3pt;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:flex-start;
      text-align:center;
    }

    .name{
      font-size:7pt;
      font-weight:700;
      margin-bottom:4pt;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }

    /* Zona del código de barras centrada */
    .barcode-wrapper{
      flex:1;
      display:flex;
      align-items:center;
      justify-content:center;
      margin:0;
    }

    /* Tamaño normal (códigos cortos/medios) */
    .barcode-wrapper img{
      max-width:70%;
      height:auto;
      display:block;
    }

    /* Cuando el código es largo, se expande más a los lados */
    .barcode-wrapper.is-long img{
      max-width:100%;
    }

    .code{
      font-size:6.5pt;
      letter-spacing:2px;
      margin-top:2pt;
    }
  </style>
</head>
<body>
  @php
    // Si el código es largo (por ejemplo > 12 caracteres) lo marcamos para expandirlo
    $isLong = strlen($code ?? '') > 12;
  @endphp

  <div class="label">
    {{-- Solo nombre del producto --}}
    <div class="name">{{ $name }}</div>

    {{-- Código de barras centrado --}}
    <div class="barcode-wrapper {{ $isLong ? 'is-long' : '' }}">
      <img src="{{ $barcodeBase64 }}" alt="Código de barras">
    </div>

    {{-- Número legible debajo --}}
    <div class="code">{{ $code }}</div>
  </div>
</body>
</html>
