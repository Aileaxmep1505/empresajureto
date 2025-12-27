@extends('layouts.app')

@section('title', 'Imprimir QR')

@section('content')
@php
  // $locations viene del route (batch u one)
  $locations = $locations ?? collect();
@endphp

<div class="wms-wrap">
  <div class="wms-top">
    <a class="back" href="{{ route('admin.wms.home') }}">← WMS</a>

    <div class="ttl">
      <div class="h1">Imprimir QR</div>
      <div class="sub">Formato limpio para pegar en pasillo/stand/bin.</div>
    </div>

    <button class="print" onclick="window.print()">🖨️ Imprimir</button>
  </div>

  <div class="grid">
    @foreach($locations as $loc)
      @php
        $url = route('admin.wms.location.page', ['location' => $loc->id]);
        $title = $loc->code ?? ('LOC-'.$loc->id);
        $name = $loc->name ?? ('Ubicación '.$title);
      @endphp

      <div class="card">
        <div class="code">{{ $title }}</div>
        <div class="name">{{ $name }}</div>

        <div class="qrbox">
          <div class="qr" data-url="{{ $url }}"></div>
        </div>

        <div class="url">{{ $url }}</div>
      </div>
    @endforeach
  </div>
</div>

{{-- QR generator (CDN). Si no quieres CDN: descarga qrcode.min.js a /public/js y cambia el src --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
(function(){
  const nodes = document.querySelectorAll('.qr[data-url]');
  nodes.forEach(el => {
    const url = el.getAttribute('data-url');
    el.innerHTML = ''; // limpia
    new QRCode(el, {
      text: url,
      width: 220,
      height: 220,
      correctLevel: QRCode.CorrectLevel.M
    });
  });
})();
</script>

<style>
  :root{
    --bg:#eef3fb;
    --card:#fff;
    --ink:#0f172a;
    --muted:#64748b;
    --line:#e5e7eb;
    --shadow:0 18px 55px rgba(2,6,23,.08);
    --r:18px;
    --brand:#2563eb;
  }

  .wms-wrap{max-width:1200px;margin:0 auto;padding:18px 14px 26px}
  .wms-top{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
  .back{
    background:#fff;border:1px solid var(--line);border-radius:999px;padding:10px 14px;
    text-decoration:none;color:var(--ink);font-weight:800;
    box-shadow:0 10px 25px rgba(2,6,23,.05);
  }
  .ttl .h1{font-weight:950;color:var(--ink);font-size:1.15rem}
  .ttl .sub{color:var(--muted);margin-top:2px;font-size:.9rem}
  .print{
    border:0;border-radius:999px;padding:10px 16px;font-weight:900;cursor:pointer;
    background:var(--brand);color:#fff;box-shadow:0 14px 30px rgba(37,99,235,.22);
  }

  .grid{
    margin-top:14px;
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:14px;
  }
  @media(max-width: 980px){ .grid{grid-template-columns:repeat(2,1fr)} }
  @media(max-width: 640px){ .grid{grid-template-columns:1fr} }

  .card{
    background:var(--card);
    border:1px solid var(--line);
    border-radius:var(--r);
    box-shadow:var(--shadow);
    padding:14px;
    overflow:hidden;
  }
  .code{font-weight:950;color:var(--ink);font-size:1.1rem;letter-spacing:.3px}
  .name{color:var(--muted);margin-top:2px;font-size:.88rem}

  .qrbox{
    margin-top:10px;
    border:2px dashed #cbd5e1;
    border-radius:18px;
    height:260px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#fbfdff;
  }
  .qr{
    width:220px;height:220px;
    display:flex;align-items:center;justify-content:center;
  }
  .qr img, .qr canvas { width:220px !important; height:220px !important; }

  .url{
    margin-top:10px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size:.78rem;
    color:#475569;
    word-break:break-all;
  }

  /* impresión */
  @media print{
    .back,.print{display:none !important;}
    body{background:#fff !important;}
    .wms-wrap{max-width:none;padding:0}
    .grid{gap:10px}
    .card{box-shadow:none}
  }
</style>
@endsection


