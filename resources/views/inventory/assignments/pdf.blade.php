<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carta responsiva</title>
    <style>
        @page {
            margin: 0;
        }

        body{
            margin:0;
            font-family: DejaVu Sans, sans-serif;
            color:#0b1f44;
            background:#ffffff;
        }

        .sheet{
            width:100%;
            min-height:100vh;
        }

        .header{
            background:#071736;
            color:#fff;
            text-align:center;
            padding:38px 40px 30px;
        }

        .header h1{
            margin:0;
            font-size:28px;
            font-weight:700;
            letter-spacing:.5px;
        }

        .header p{
            margin:12px 0 0;
            font-size:14px;
        }

        .content{
            padding:28px 44px 40px;
        }

        .topline{
            width:100%;
            margin-bottom:28px;
            font-size:14px;
            color:#6b7ea3;
        }

        .topline td{
            width:50%;
        }

        .topline td:last-child{
            text-align:right;
        }

        .paragraph{
            font-size:15px;
            line-height:1.45;
            margin-bottom:28px;
        }

        .box{
            background:#f4f7fb;
            border:1px solid #d9e2ef;
            border-radius:12px;
            padding:22px;
            margin-bottom:26px;
        }

        .box h3{
            margin:0 0 14px;
            font-size:16px;
            font-weight:700;
        }

        .box p{
            margin:8px 0;
            font-size:15px;
        }

        .section-title{
            margin:0 0 12px;
            font-size:16px;
            font-weight:700;
        }

        ul{
            margin:0 0 26px 18px;
            padding:0;
        }

        li{
            margin-bottom:10px;
            font-size:15px;
            line-height:1.35;
        }

        .signatures{
            margin-top:60px;
            width:100%;
        }

        .signatures td{
            width:50%;
            vertical-align:top;
            padding-right:26px;
        }

        .sign-line{
            border-top:1px solid #cfd8e3;
            padding-top:10px;
            font-size:14px;
            color:#6b7ea3;
        }

        .digital-sign{
            margin-top:8px;
            color:#0f9f8d;
            font-size:13px;
        }

        .signature-img{
            margin-top:10px;
            height:70px;
        }

        .signature-img img{
            height:70px;
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="header">
            <h1>CARTA RESPONSIVA</h1>
            <p>Control de Activos Internos</p>
        </div>

        <div class="content">
            <table class="topline" cellspacing="0" cellpadding="0">
                <tr>
                    <td>Folio: {{ $assignment->folio }}</td>
                    <td>Fecha: {{ optional($assignment->assigned_at)->translatedFormat('d \\d\\e F \\d\\e Y') }}</td>
                </tr>
            </table>

            <div class="paragraph">
                Por medio de la presente, yo <strong>{{ $assignment->user->name ?? 'Usuario' }}</strong>,
                con correo electrónico <strong>{{ $assignment->user->email ?? 'Sin correo' }}</strong>,
                declaro haber recibido de conformidad el siguiente activo/material de la empresa,
                comprometiéndome a su uso adecuado, cuidado y resguardo.
            </div>

            <div class="box">
                <h3>DATOS DEL ACTIVO</h3>
                <p><strong>Activo:</strong> {{ $assignment->item->name ?? 'Activo' }}</p>
                <p><strong>Cantidad:</strong> {{ $assignment->quantity }}</p>
                @if(!empty($assignment->item->category->name))
                    <p><strong>Categoría:</strong> {{ $assignment->item->category->name }}</p>
                @endif
                @if(!empty($assignment->notes))
                    <p><strong>Notas:</strong> {{ $assignment->notes }}</p>
                @endif
            </div>

            <div class="section-title">OBLIGACIONES DEL RESPONSABLE:</div>
            <ul>
                <li>Usar el activo exclusivamente para fines laborales.</li>
                <li>Mantener el activo en buen estado de conservación.</li>
                <li>Reportar inmediatamente cualquier daño, pérdida o robo.</li>
                <li>Devolver el activo en las mismas condiciones al término de su uso.</li>
                <li>No realizar modificaciones sin autorización previa.</li>
            </ul>

            <table class="signatures" cellspacing="0" cellpadding="0">
                <tr>
                    <td>
                        <div class="sign-line">Firma del responsable</div>
                        @if(!empty($assignment->signature))
                            <div class="signature-img">
                                <img src="{{ $assignment->signature }}" alt="Firma">
                            </div>
                        @endif
                        <div class="digital-sign">[Firma digital registrada en sistema]</div>
                    </td>
                    <td>
                        <div class="sign-line">Firma de quien entrega</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>