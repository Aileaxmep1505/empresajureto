<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ficha técnica</title>
  <style>
    body{font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; background:#f6f7fb; margin:0; padding:24px;}
    .card{max-width:980px;margin:0 auto;background:#fff;border:1px solid #e7ebf0;border-radius:16px;padding:20px;box-shadow:0 18px 44px rgba(9,30,66,.08);}
    h1{margin:0 0 10px;font-size:20px}
    .muted{color:#64748b}
    .row{display:flex;gap:14px;flex-wrap:wrap}
    .pill{padding:8px 10px;border:1px solid #e7ebf0;border-radius:999px;background:#fafbff}
    img{max-width:100%;border-radius:12px;border:1px solid #e7ebf0}
  </style>
</head>
<body>
  <div class="card">
    <h1>Ficha técnica</h1>
    <div class="muted">Vista pública</div>

    <hr style="border:none;border-top:1px solid #e7ebf0;margin:16px 0">

    {{-- Ajusta según lo que mandes desde el controller --}}
    <div class="row">
      @isset($techSheet)
        <div class="pill"><b>Título:</b> {{ $techSheet->title ?? '—' }}</div>
        <div class="pill"><b>SKU:</b> {{ $techSheet->sku ?? '—' }}</div>
      @endisset
    </div>

    @isset($techSheet)
      @if(!empty($techSheet->image_url))
        <div style="margin-top:16px">
          <img src="{{ $techSheet->image_url }}" alt="Imagen">
        </div>
      @endif

      @if(!empty($techSheet->content))
        <div style="margin-top:16px; white-space:pre-wrap">{{ $techSheet->content }}</div>
      @endif
    @endisset
  </div>
</body>
</html>
