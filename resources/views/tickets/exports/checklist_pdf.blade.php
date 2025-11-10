<!doctype html>
<html><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans, sans-serif;font-size:12px}
h1{font-size:18px}
.item{padding:4px 0;border-bottom:1px solid #eee}
</style></head>
<body>
<h1>Checklist: {{ $checklist->title }}</h1>
<p>Ticket: {{ $checklist->ticket->folio }}</p>
@foreach($checklist->items as $it)
  <div class="item">{{ $it->is_done ? '✅' : '☐' }} {{ $it->label }} — {{ $it->value }}</div>
@endforeach
{{-- Si usas SimpleSoftwareIO\QrCode --}}
@if(class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class))
  <div style="margin-top:12px">
    {!! QrCode::size(100)->generate(route('tickets.show',$checklist->ticket)) !!}
  </div>
@endif
</body></html>
