{{-- resources/views/tech_sheets/pdf.blade.php --}}
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Ficha técnica - {{ $sheet->product_name }}</title>
  <style>
    *{ box-sizing:border-box; }

    body{
      font-family: DejaVu Sans, sans-serif;
      font-size:10px;
      color:#111827;
      margin:0;
      padding:18px;
      background:#ffffff; /* TODO blanco, sin fondo gris */
    }

    .card{
      background:#ffffff;
      border-radius:16px;
      border:1px solid #d4dde9;
      overflow:hidden;
    }

    /* ===== HEADER (oscuro, pero sin sombras ni degradados para evitar rayas) ===== */
    .header{
      background:#020617;
      color:#f9fafb;
      padding:10px 14px 10px;
    }
    .header-table{
      width:100%;
      border-collapse:collapse;
    }
    .header-table td{
      padding:0;
      vertical-align:top;
    }

    /* Imagen producto */
    .img-cell{
      width:32%;
      max-width:210px;
    }
    .img-box{
      width:100%;
      height:120px;
      background:#020617;
      border-radius:14px;
      text-align:center;
      overflow:hidden;
      border:1px solid #111827;
    }
    .img-box img{
      max-width:100%;
      max-height:120px;
    }
    .img-ph{
      color:#9ca3af;
      font-size:8px;
      line-height:120px;
    }

    /* Columna derecha */
    .main-cell{
      padding-left:10px;
    }

    /* Fila superior: logo marca + partida + logo empresa */
    .top-row{
      width:100%;
      border-collapse:collapse;
      margin-bottom:4px;
    }
    .top-row td{
      padding:0 2px;
      vertical-align:middle;
    }

    .brand-pill{
      height:22px;
      border-radius:999px;
      border:1px solid #e5e7eb;
      background:#ffffff;
      padding:2px 8px 2px 4px;
      white-space:nowrap;
    }
    .brand-pill img{
      height:18px;
      vertical-align:middle;
    }

    .partida-pill{
      border-radius:999px;
      border:1px solid #facc15;
      background:#111827;
      color:#fef9c3;
      font-size:8px;
      padding:2px 8px;
      white-space:nowrap;
    }
    .partida-pill .label{
      text-transform:uppercase;
      letter-spacing:.08em;
      margin-right:2px;
    }
    .partida-pill .value{
      font-weight:700;
    }

    .company-pill{
      border-radius:999px;
      border:1px solid #0ea5e9;
      background:#020617;
      padding:2px 7px 2px 4px;
      white-space:nowrap;
      text-align:right;
    }
    .company-pill img{
      height:25px;
      vertical-align:middle;
      margin-right:4px;
    }
    .company-pill span{
      font-size:8px;
      text-transform:uppercase;
      letter-spacing:.12em; 
      color:#e5e7eb;
      vertical-align:middle;
    }

    /* Ref + título + subtítulo */
    .ref-line{
      font-size:7px;
      letter-spacing:.14em;
      text-transform:uppercase;
      color:#9ca3af;
      margin-top:2px;
    }
    .title-main{
      font-size:13px;
      font-weight:800;
      margin:2px 0 0;
      color:#f9fafb;
    }
    .subtitle{
      font-size:8.5px;
      color:#cbd5f5;
      text-transform:uppercase;
      margin-top:2px;
    }

    /* Pills de marca / modelo / referencia en header */
    .header-pills{
      margin-top:4px;
    }
    .header-pill{
      display:inline-block;
      padding:3px 7px;
      border-radius:999px;
      font-size:7.5px;
      margin-right:3px;
      margin-top:2px;
      background:#020617;
      border:1px solid #4b5563;
      color:#e5e7eb;
    }

    .description{
      font-size:8.5px;
      color:#e5e7eb;
      margin-top:6px;
      line-height:1.35;
    }

    /* ===== BODY ===== */
    .body{
      padding:10px 14px 14px;
      background:#ffffff;
    }

    /* Tags bajo el header */
    .tag-row{
      margin-bottom:4px;
    }
    .tag{
      font-size:7.5px;
      border-radius:999px;
      padding:2px 6px;
      border:1px solid #d1d5db;
      display:inline-block;
      margin-right:4px;
      margin-top:2px;
      background:#f9fafb;
    }

    /* Secciones tipo "card" claras */
    .section{
      border-radius:14px;
      border:1px solid #e5e7eb;
      background:#f9fafb;
      margin-top:8px;
      padding:6px 8px 8px;
    }
    .section-title-row{
      font-size:8px;
      font-weight:700;
      margin-bottom:4px;
    }

    /* Tabla especificaciones */
    table.specs{
      width:100%;
      border-collapse:collapse;
      margin-top:4px;
    }
    table.specs th,
    table.specs td{
      border:1px solid #d1d5db;
      padding:3px 4px;
      font-size:8px;
    }
    table.specs th{
      background:#e5e7eb;
      text-align:left;
    }

    /* Lista características */
    ul{
      padding-left:14px;
      margin:4px 0;
    }
    ul li{
      margin-bottom:2px;
      font-size:8px;
    }

    /* Footer */
    .footer{
      margin-top:6px;
      padding-top:4px;
      border-top:1px solid #e5e7eb;
      font-size:7.5px;
      color:#6b7280;
      display:flex;
      justify-content:space-between;
    }
  </style>
</head>
<body>
@php
  $desc    = $sheet->ai_description ?: $sheet->user_description;
  $partida = $sheet->partida_number ?? null;
@endphp

<div class="card">
  {{-- ===== HEADER ===== --}}
  <div class="header">
    <table class="header-table">
      <tr>
        {{-- Imagen producto --}}
        <td class="img-cell">
          <div class="img-box">
            @if($sheet->image_path)
              <img src="{{ public_path('storage/'.$sheet->image_path) }}" alt="Imagen {{ $sheet->product_name }}">
            @else
              <span class="img-ph">Sin imagen</span>
            @endif
          </div>
        </td>

        {{-- Datos principales --}}
        <td class="main-cell">
          {{-- fila superior: logo marca / partida / logo empresa --}}
          <table class="top-row">
            <tr>
              <td>
                @if($sheet->brand_image_path)
                  <div class="brand-pill">
                    <img src="{{ public_path('storage/'.$sheet->brand_image_path) }}" alt="Logo marca">
                  </div>
                @endif
              </td>
              <td style="text-align:center;">
                @if($partida)
             
                    <span class="label">N° Partida:</span>
                    <span class="value">{{ $partida }}</span>
                 
                @endif
              </td>
              <td style="text-align:right;">
                <div class="company-pill">
                  <img src="{{ public_path('images/logo-mail.png') }}" alt="Jureto">
                  <span>JURETO S.A. DE C.V.</span>
                </div>
              </td>
            </tr>
          </table>

          @if($sheet->reference)
            <div class="ref-line">{{ strtoupper($sheet->reference) }}</div>
          @endif

          <div class="title-main">{{ $sheet->product_name }}</div>

          @if($sheet->identification)
            <div class="subtitle">{{ strtoupper($sheet->identification) }}</div>
          @endif

          {{-- pills en header --}}
          <div class="header-pills">
            @if($sheet->brand)
              <span class="header-pill">Marca: {{ $sheet->brand }}</span>
            @endif
            @if($sheet->model)
              <span class="header-pill">Modelo: {{ $sheet->model }}</span>
            @endif
            @if($sheet->reference)
              <span class="header-pill">{{ $sheet->reference }}</span>
            @endif
          </div>

          @if($desc)
            <div class="description">{{ $desc }}</div>
          @endif
        </td>
      </tr>
    </table>
  </div>

  {{-- ===== CUERPO ===== --}}
  <div class="body">
    {{-- tags pequeños abajo del header --}}
    <div class="tag-row">
      @if($sheet->reference)
        <span class="tag">Código: {{ $sheet->reference }}</span>
      @endif
      @if($sheet->brand)
        <span class="tag">Marca: {{ $sheet->brand }}</span>
      @endif
      @if($sheet->model)
        <span class="tag">Modelo: {{ $sheet->model }}</span>
      @endif
    </div>

    {{-- Especificaciones técnicas --}}
    @if(!empty($sheet->ai_specs))
      <div class="section">
        <div class="section-title-row">Especificaciones técnicas</div>
        <table class="specs">
          <thead>
            <tr>
              <th style="width:40%;">Característica</th>
              <th>Valor</th>
            </tr>
          </thead>
          <tbody>
            @foreach($sheet->ai_specs as $spec)
              <tr>
                <td>{{ $spec['nombre'] ?? '' }}</td>
                <td>{{ $spec['valor'] ?? '' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif

    {{-- Características destacadas --}}
    @if(!empty($sheet->ai_features))
      <div class="section">
        <div class="section-title-row">Características destacadas</div>
        <ul>
          @foreach($sheet->ai_features as $f)
            <li>{{ $f }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    {{-- Si no hay specs ni features, mostramos descripción en sección --}}
    @if(empty($sheet->ai_specs) && empty($sheet->ai_features) && $desc)
      <div class="section">
        <div class="section-title-row">Descripción</div>
        <p style="margin:2px 0 0 0; font-size:8.5px;">{{ $desc }}</p>
      </div>
    @endif

    <div class="footer">
      <div>Creado: {{ optional($sheet->created_at)->format('d/m/Y H:i') ?? '—' }}</div>
      <div>Producto: {{ $sheet->product_name }}</div>
    </div>
  </div>
</div>
</body>
</html>
