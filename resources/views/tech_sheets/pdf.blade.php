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
      font-size:11px;
      color:#111827;
      margin:0;
      padding:18px;
      background:#ffffff; /* sin fondo azul */
    }

    .card{
      background:#ffffff;
      border-radius:18px;
      border:1px solid #d4dde9;
      overflow:hidden;
    }

    /* ===== HEADER CLARO, LEGIBLE EN PDF ===== */
    .header{
      padding:10px 14px 10px;
      background:#f3f4ff;
      border-bottom:1px solid #d4dde9;
    }
    .header-table{
      width:100%;
      border-collapse:collapse;
    }
    .header-table td{
      padding:4px 6px;
      vertical-align:top;
      border:0;
    }

    .img-box{
      width:32%;
      max-width:210px;
      height:120px;
      background:#020617;
      border-radius:16px;
      text-align:center;
      overflow:hidden;
    }
    .img-box img{
      max-width:100%;
      max-height:120px;
    }
    .img-ph{
      color:#e5e7eb;
      font-size:9px;
      line-height:120px;
    }

    /* Logo empresarial destacado */
    .logo-wrap{
      text-align:right;
      font-size:8px;
      padding-bottom:4px;
    }
    .logo-pill{
      display:inline-block;
      padding:4px 10px 4px 6px;
      border-radius:999px;
      background:#020617;
      border:1px solid #1d4ed8;
    }
    .logo-pill img{
      height:20px;
      vertical-align:middle;
      margin-right:5px;
    }
    .logo-pill span{
      text-transform:uppercase;
      letter-spacing:.14em;
      color:#e5e7eb;
      font-weight:700;
      font-size:7px;
      vertical-align:middle;
    }

    .ref-line{
      font-size:8px;
      letter-spacing:.13em;
      text-transform:uppercase;
      color:#6b7280;
      margin-top:2px;
    }
    .title-main{
      font-size:15px;
      font-weight:800;
      margin:2px 0 0;
      color:#020617;
    }
    .ident{
      font-size:9px;
      color:#4b5563;
      margin-top:2px;
    }

    .pills-row{
      margin-top:6px;
    }
    .pill{
      display:inline-block;
      padding:3px 8px;
      border-radius:999px;
      font-size:8px;
      margin-right:4px;
      margin-top:2px;
      background:#e5e7ff;
      border:1px solid #c7d2fe;
      color:#1e3a8a;
    }

    .description{
      font-size:9px;
      color:#111827;
      margin-top:7px;
    }

    /* ===== BODY ===== */
    .body{
      padding:10px 14px 14px;
      background:#ffffff;
    }

    h2{
      font-size:11px;
      margin:10px 0 4px;
      padding-left:6px;
      border-left:3px solid #4f46e5;
      color:#111827;
    }

    .tag-row{ margin-bottom:4px; }
    .tag{
      font-size:8px;
      border-radius:999px;
      padding:2px 6px;
      border:1px solid #d1d5db;
      display:inline-block;
      margin-right:4px;
      margin-top:2px;
      background:#f9fafb;
    }

    ul{ padding-left:16px; margin:4px 0; }
    ul li{ margin-bottom:2px; }

    table{ width:100%; border-collapse:collapse; margin-top:4px; }
    th,td{ border:1px solid #d1d5db; padding:4px; font-size:9px; }
    th{ background:#e5e7eb; text-align:left; }

    .footer{
      margin-top:8px;
      padding-top:6px;
      border-top:1px solid #e5e7eb;
      font-size:8px;
      color:#6b7280;
      display:flex;
      justify-content:space-between;
    }
  </style>
</head>
<body>
  <div class="card">
    {{-- HEADER: imagen + datos principales + logo --}}
    <div class="header">
      <table class="header-table">
        <tr>
          {{-- Imagen del producto --}}
          <td class="img-box">
            @if($sheet->image_path)
              <img src="{{ public_path('storage/'.$sheet->image_path) }}" alt="Imagen {{ $sheet->product_name }}">
            @else
              <span class="img-ph">Sin imagen</span>
            @endif
          </td>

          {{-- Datos principales --}}
          <td>
            <div class="logo-wrap">
              <div class="logo-pill">
                <img src="{{ public_path('images/logo-mail.png') }}" alt="Logo">
                <span>FICHA TÉCNICA</span>
              </div>
            </div>

            @if($sheet->reference)
              <div class="ref-line">{{ strtoupper($sheet->reference) }}</div>
            @endif

            <h1 class="title-main">{{ $sheet->product_name }}</h1>

            @if($sheet->identification)
              <div class="ident">{{ $sheet->identification }}</div>
            @endif

            <div class="pills-row">
              @if($sheet->brand)
                <span class="pill">Marca: {{ $sheet->brand }}</span>
              @endif
              @if($sheet->model)
                <span class="pill">Modelo: {{ $sheet->model }}</span>
              @endif
            </div>

            @php
              $desc = $sheet->ai_description ?: $sheet->user_description;
            @endphp
            @if($desc)
              <div class="description">{{ $desc }}</div>
            @endif
          </td>
        </tr>
      </table>
    </div>

    {{-- CUERPO: características y especificaciones --}}
    <div class="body">
      {{-- Tags inferiores (código, marca, modelo) --}}
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

      @if(!empty($sheet->ai_features))
        <h2>Características destacadas</h2>
        <ul>
          @foreach($sheet->ai_features as $f)
            <li>{{ $f }}</li>
          @endforeach
        </ul>
      @endif

      @if(!empty($sheet->ai_specs))
        <h2>Especificaciones técnicas</h2>
        <table>
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
      @endif

      {{-- Si no hay listas, al menos mostramos descripción como sección --}}
      @if(empty($sheet->ai_features) && empty($sheet->ai_specs) && $desc)
        <h2>Descripción</h2>
        <p style="margin:4px 0 0 0; font-size:9px;">{{ $desc }}</p>
      @endif

      <div class="footer">
        <div>Creado: {{ optional($sheet->created_at)->format('d/m/Y H:i') ?? '—' }}</div>
        <div>Producto: {{ $sheet->product_name }}</div>
      </div>
    </div>
  </div>
</body>
</html>
