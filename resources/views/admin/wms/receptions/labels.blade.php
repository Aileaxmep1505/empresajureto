<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etiquetas de recepción</title>
    <style>
        @page {
            size: 2cm 2cm;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            font-family: DejaVu Sans, sans-serif;
            color: #111111;
        }

        body {
            overflow: hidden;
        }

        .page-break {
            page-break-after: always;
        }

        .label {
            width: 100%;
            height: 100%;
            overflow: hidden;
            padding: 0;
            margin: 0;
        }

        .card {
            width: 100%;
            height: 100%;
            overflow: hidden;
            text-align: center;
            padding: 1mm 1mm 0.5mm 1mm;
        }

        .qr-box {
            width: 100%;
            margin: 0;
            padding: 0;
            line-height: 0;
            text-align: center;
        }

        .qr-box img {
            width: 11mm;
            height: 11mm;
            display: block;
            margin: 0 auto;
        }

        .name {
            margin: 0.6mm 0 0 0;
            padding: 0;
            font-size: 5pt;
            line-height: 1;
            font-weight: bold;
            text-align: center;
            max-height: 3mm;
            overflow: hidden;
            word-break: break-word;
        }
    </style>
</head>
<body>
    @foreach($reception->lines as $line)
        @php
            $name = $line->name ?: 'Producto';

            $qrText = implode(' | ', array_filter([
                'Nombre: '.$name,
            ]));

            $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(90)
                ->margin(0)
                ->generate($qrText);

            $svgBase64 = 'data:image/svg+xml;base64,' . base64_encode($svg);
        @endphp

        <div class="label @if(!$loop->last) page-break @endif">
            <div class="card">
                <div class="qr-box">
                    <img src="{{ $svgBase64 }}" alt="QR">
                </div>

                <div class="name">{{ \Illuminate\Support\Str::limit($name, 22) }}</div>
            </div>
        </div>
    @endforeach
</body>
</html>