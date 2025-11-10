@extends('layouts.app')
@section('title','Indicadores de Tickets')
@section('content')
<div class="container py-4" id="tktdash">
  <style>#tktdash canvas{max-width:520px}</style>
  <h1 class="h">Dashboard</h1>
  <p>Tiempo prom. resolución: <strong>{{ number_format($avgResolution,1) }}</strong> h</p>
  <div style="display:flex;gap:24px;flex-wrap:wrap">
    <canvas id="statusChart"></canvas>
    <canvas id="priorityChart"></canvas>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const statusData = @json($byStatus);
const priorityData = @json($byPriority);
new Chart(document.getElementById('statusChart'), {
  type:'doughnut', data:{ labels:Object.keys(statusData), datasets:[{ data:Object.values(statusData) }]},
});
new Chart(document.getElementById('priorityChart'), {
  type:'bar', data:{ labels:Object.keys(priorityData), datasets:[{ data:Object.values(priorityData) }]},
});
</script>
@endsection
